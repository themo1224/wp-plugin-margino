<?php
/**
 * Connection options and admin-post connect/disconnect handlers.
 *
 * @package Pricing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Persists API URL/key/shop stub and handles Connection form posts.
 */
class Pricing_Connection {

	/**
	 * Option: sanitized API base URL (no trailing slash).
	 *
	 * @var string
	 */
	const OPTION_BASE_URL = 'pricing_api_base_url';

	/**
	 * Option: API key (autoload disabled). Never echo or log.
	 *
	 * @var string
	 */
	const OPTION_API_KEY = 'pricing_api_key';

	/**
	 * Option: last successful validate stub (shop/plan).
	 *
	 * @var string
	 */
	const OPTION_CONNECTION = 'pricing_connection';

	/**
	 * Nonce / action group for connection forms.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'pricing_connection';

	/**
	 * Register admin-post handlers.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'admin_post_pricing_connect', array( __CLASS__, 'handle_connect' ) );
		add_action( 'admin_post_pricing_disconnect', array( __CLASS__, 'handle_disconnect' ) );
	}

	/**
	 * Whether a successful validate is stored with a key.
	 *
	 * @return bool
	 */
	public static function is_connected() {
		$key = (string) get_option( self::OPTION_API_KEY, '' );
		if ( '' === $key ) {
			return false;
		}

		$connection = get_option( self::OPTION_CONNECTION, null );
		return is_array( $connection ) && ! empty( $connection );
	}

	/**
	 * Status payload for the admin strip.
	 *
	 * @return array{
	 *     connected: bool,
	 *     shop_name: string,
	 *     plan_label: string,
	 *     reason: string
	 * }
	 */
	public static function status() {
		if ( self::is_connected() ) {
			$connection = get_option( self::OPTION_CONNECTION, array() );
			$shop_name  = isset( $connection['shop_name'] ) ? (string) $connection['shop_name'] : '';
			$plan_label = isset( $connection['plan_label'] ) ? (string) $connection['plan_label'] : '';

			return array(
				'connected'  => true,
				'shop_name'  => $shop_name,
				'plan_label' => $plan_label,
				'reason'     => '',
			);
		}

		return array(
			'connected'  => false,
			'shop_name'  => '',
			'plan_label' => '',
			'reason'     => 'disconnected',
		);
	}

	/**
	 * Whether an API key option exists (may be disconnected after failed reconnect).
	 *
	 * @return bool
	 */
	public static function has_api_key() {
		return '' !== (string) get_option( self::OPTION_API_KEY, '' );
	}

	/**
	 * Stored base URL (may be empty).
	 *
	 * @return string
	 */
	public static function get_base_url() {
		return (string) get_option( self::OPTION_BASE_URL, '' );
	}

	/**
	 * Map error code to Farsi admin notice text.
	 *
	 * @param string $code Error slug.
	 * @return string
	 */
	public static function error_message_for_code( $code ) {
		switch ( $code ) {
			case 'invalid_api_key':
				return __( 'کلید نامعتبر است.', 'pricing' );
			case 'plan_inactive':
				return __( 'فروشگاه یا پلن اجازه اتصال ندارد.', 'pricing' );
			case 'http_error':
				return __( 'به سرویس Pricing دسترسی نیست. API را روشن کنید.', 'pricing' );
			case 'validation_error':
				return __( 'آدرس یا کلید درست نیست.', 'pricing' );
			default:
				return __( 'اتصال برقرار نشد. دوباره تلاش کنید.', 'pricing' );
		}
	}

	/**
	 * Handle Connect form submission.
	 *
	 * @return void
	 */
	public static function handle_connect() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'شما اجازه این کار را ندارید.', 'pricing' ), 403 );
		}

		check_admin_referer( self::NONCE_ACTION );

		$raw_url = isset( $_POST['pricing_api_base_url'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			? wp_unslash( (string) $_POST['pricing_api_base_url'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing
			: '';
		$posted_key = isset( $_POST['pricing_api_key'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			? trim( (string) wp_unslash( $_POST['pricing_api_key'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing
			: '';

		$url = self::sanitize_base_url( $raw_url );
		if ( '' === $url ) {
			self::clear_connection_stub();
			self::redirect_with_notice( 'error', 'validation_error' );
		}

		update_option( self::OPTION_BASE_URL, $url, true );

		$key_to_use = '' !== $posted_key ? $posted_key : (string) get_option( self::OPTION_API_KEY, '' );
		if ( '' === $key_to_use ) {
			self::clear_connection_stub();
			self::redirect_with_notice( 'error', 'validation_error' );
		}

		$result = Pricing_Http_Client::request( 'POST', '/connector/validate', array(), $key_to_use, $url );

		if ( ! $result['ok'] ) {
			// Clear shop stub (disconnected). Persist URL; do not write a rejected key.
			self::clear_connection_stub();
			$code = is_string( $result['error_code'] ) && '' !== $result['error_code']
				? $result['error_code']
				: 'http_error';
			self::redirect_with_notice( 'error', $code );
		}

		$data = is_array( $result['data'] ) ? $result['data'] : array();
		$shop = ( isset( $data['shop'] ) && is_array( $data['shop'] ) ) ? $data['shop'] : array();
		$plan = ( isset( $data['plan'] ) && is_array( $data['plan'] ) ) ? $data['plan'] : array();

		if ( '' !== $posted_key ) {
			self::store_api_key( $posted_key );
		}

		$connection = array(
			'shop_id'     => isset( $shop['id'] ) ? sanitize_text_field( (string) $shop['id'] ) : '',
			'shop_name'   => isset( $shop['name'] ) ? sanitize_text_field( (string) $shop['name'] ) : '',
			'plan_id'     => isset( $plan['id'] ) ? sanitize_text_field( (string) $plan['id'] ) : '',
			'plan_status' => isset( $plan['status'] ) ? sanitize_text_field( (string) $plan['status'] ) : '',
			'plan_label'  => isset( $plan['label'] ) ? sanitize_text_field( (string) $plan['label'] ) : '',
			'connected_at' => gmdate( 'Y-m-d H:i:s' ),
		);

		update_option( self::OPTION_CONNECTION, $connection, true );

		self::redirect_with_notice( 'connected' );
	}

	/**
	 * Handle Disconnect form submission.
	 *
	 * @return void
	 */
	public static function handle_disconnect() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'شما اجازه این کار را ندارید.', 'pricing' ), 403 );
		}

		check_admin_referer( self::NONCE_ACTION );

		delete_option( self::OPTION_API_KEY );
		delete_option( self::OPTION_CONNECTION );

		self::redirect_with_notice( 'disconnected' );
	}

	/**
	 * Sanitize and validate http(s) base URL; strip trailing slash.
	 *
	 * @param string $raw Raw URL from POST.
	 * @return string Empty string if invalid.
	 */
	private static function sanitize_base_url( $raw ) {
		$url = esc_url_raw( trim( (string) $raw ) );
		if ( '' === $url ) {
			return '';
		}

		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return '';
		}

		$scheme = strtolower( (string) $parts['scheme'] );
		if ( 'http' !== $scheme && 'https' !== $scheme ) {
			return '';
		}

		return untrailingslashit( $url );
	}

	/**
	 * Store API key with autoload disabled.
	 *
	 * @param string $key Plaintext key.
	 * @return void
	 */
	private static function store_api_key( $key ) {
		$existing = get_option( self::OPTION_API_KEY, false );
		if ( false === $existing ) {
			add_option( self::OPTION_API_KEY, $key, '', 'no' );
		} else {
			update_option( self::OPTION_API_KEY, $key, false );
		}
	}

	/**
	 * Remove last successful connection stub (marks disconnected).
	 *
	 * @return void
	 */
	private static function clear_connection_stub() {
		delete_option( self::OPTION_CONNECTION );
	}

	/**
	 * Redirect back to Connection page with a notice slug.
	 *
	 * @param string      $notice connected|disconnected|error.
	 * @param string|null $error  Optional error slug (not the API key).
	 * @return void
	 */
	private static function redirect_with_notice( $notice, $error = null ) {
		$args = array(
			'page'           => 'pricing-connection',
			'pricing_notice' => $notice,
		);

		if ( null !== $error && '' !== $error ) {
			$args['pricing_error'] = sanitize_key( $error );
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}
}
