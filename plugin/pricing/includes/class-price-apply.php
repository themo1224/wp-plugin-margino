<?php
/**
 * Manual apply of recommended price to WooCommerce + API ack.
 *
 * @package Pricing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Writes recommended price on simple products, then POST .../applied.
 */
class Pricing_Price_Apply {

	/**
	 * Option: last successful apply summary for flash notices.
	 *
	 * @var string
	 */
	const OPTION_LAST_APPLY = 'pricing_last_apply';

	/**
	 * Nonce action for apply form.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'pricing_apply';

	/**
	 * Register admin-post handler.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'admin_post_pricing_apply_price', array( __CLASS__, 'handle_apply' ) );
	}

	/**
	 * Last apply summary for flash notices.
	 *
	 * @return array|null
	 */
	public static function get_last_apply() {
		$last = get_option( self::OPTION_LAST_APPLY, null );
		return is_array( $last ) ? $last : null;
	}

	/**
	 * Handle Apply form submission.
	 *
	 * @return void
	 */
	public static function handle_apply() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'شما اجازه این کار را ندارید.', 'pricing' ), 403 );
		}

		check_admin_referer( self::NONCE_ACTION );

		if ( ! Pricing_Connection::is_connected() ) {
			self::redirect_with_notice( 'error', 'not_connected' );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		if ( $product_id <= 0 ) {
			self::redirect_with_notice( 'error', 'invalid_product' );
		}

		$result = self::apply( $product_id );

		if ( ! empty( $result['ok'] ) ) {
			self::redirect_with_notice( 'applied' );
		}

		$code = isset( $result['error_code'] ) && is_string( $result['error_code'] ) && '' !== $result['error_code']
			? $result['error_code']
			: 'http_error';
		self::redirect_with_notice( 'error', $code );
	}

	/**
	 * Apply recommended price to a simple WC product, then acknowledge API.
	 *
	 * @param int $product_id WooCommerce product ID.
	 * @return array{ok: bool, error_code: string|null}
	 */
	public static function apply( $product_id ) {
		$product_id = (int) $product_id;

		if ( ! function_exists( 'wc_get_product' ) ) {
			return array(
				'ok'         => false,
				'error_code' => 'http_error',
			);
		}

		$product = wc_get_product( $product_id );
		if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
			return array(
				'ok'         => false,
				'error_code' => 'invalid_product',
			);
		}

		if ( ! method_exists( $product, 'is_type' ) || ! $product->is_type( 'simple' ) ) {
			return array(
				'ok'         => false,
				'error_code' => 'unsupported_type',
			);
		}

		$external_id = (string) $product->get_id();
		$rec         = Pricing_Recommendations::fetch_for_product( $external_id );

		if ( empty( $rec['ok'] ) || ! is_array( $rec['data'] ) ) {
			$code = isset( $rec['error_code'] ) && is_string( $rec['error_code'] ) && '' !== $rec['error_code']
				? $rec['error_code']
				: 'http_error';
			return array(
				'ok'         => false,
				'error_code' => $code,
			);
		}

		$data              = $rec['data'];
		$recommended_price = isset( $data['recommended_price'] ) ? (string) $data['recommended_price'] : '';
		$currency          = isset( $data['currency'] ) ? (string) $data['currency'] : '';

		if ( ! empty( $data['below_floor'] ) ) {
			return array(
				'ok'         => false,
				'error_code' => 'below_floor',
			);
		}

		if ( '' === $recommended_price || ! preg_match( '/^\d+(\.\d+)?$/', $recommended_price ) ) {
			return array(
				'ok'         => false,
				'error_code' => 'validation_error',
			);
		}

		if ( '' === $currency ) {
			return array(
				'ok'         => false,
				'error_code' => 'validation_error',
			);
		}

		$write = self::write_woocommerce_price( $product, $recommended_price );
		if ( empty( $write['ok'] ) ) {
			return array(
				'ok'         => false,
				'error_code' => isset( $write['error_code'] ) ? $write['error_code'] : 'wc_write_failed',
			);
		}

		$ack = self::acknowledge_applied( $external_id, $recommended_price, $currency );
		if ( empty( $ack['ok'] ) ) {
			// One automatic retry for transient transport failures.
			$ack = self::acknowledge_applied( $external_id, $recommended_price, $currency );
		}

		if ( empty( $ack['ok'] ) ) {
			self::store_last_apply(
				array(
					'product_id'    => $product_id,
					'applied_price' => $recommended_price,
					'currency'      => $currency,
					'wc_updated'    => true,
					'api_acked'     => false,
					'applied_at'    => gmdate( 'Y-m-d H:i:s' ),
				)
			);

			return array(
				'ok'         => false,
				'error_code' => 'ack_failed',
			);
		}

		self::store_last_apply(
			array(
				'product_id'    => $product_id,
				'applied_price' => $recommended_price,
				'currency'      => $currency,
				'wc_updated'    => true,
				'api_acked'     => true,
				'applied_at'    => gmdate( 'Y-m-d H:i:s' ),
			)
		);

		return array(
			'ok'         => true,
			'error_code' => null,
		);
	}

	/**
	 * Write regular + active price on a simple product.
	 *
	 * @param object $product WC product.
	 * @param string $price   Decimal string.
	 * @return array{ok: bool, error_code: string|null}
	 */
	private static function write_woocommerce_price( $product, $price ) {
		try {
			if ( method_exists( $product, 'set_regular_price' ) ) {
				$product->set_regular_price( $price );
			}
			if ( method_exists( $product, 'set_price' ) ) {
				$product->set_price( $price );
			}
			if ( method_exists( $product, 'save' ) ) {
				$product->save();
			}
		} catch ( Exception $e ) {
			return array(
				'ok'         => false,
				'error_code' => 'wc_write_failed',
			);
		}

		return array(
			'ok'         => true,
			'error_code' => null,
		);
	}

	/**
	 * POST applied acknowledgement to the Pricing API.
	 *
	 * @param string $external_id Product id string.
	 * @param string $price       Applied price.
	 * @param string $currency    Currency code.
	 * @return array{ok: bool, error_code: string|null}
	 */
	private static function acknowledge_applied( $external_id, $price, $currency ) {
		$path = '/connector/products/' . rawurlencode( (string) $external_id ) . '/applied';
		$body = array(
			'applied_price' => (string) $price,
			'currency'      => (string) $currency,
			'source'        => 'manual',
			'applied_at'    => gmdate( 'Y-m-d\TH:i:s\Z' ),
		);

		$response = Pricing_Http_Client::request( 'POST', $path, $body );

		if ( ! empty( $response['ok'] ) ) {
			return array(
				'ok'         => true,
				'error_code' => null,
			);
		}

		$code = isset( $response['error_code'] ) && is_string( $response['error_code'] ) && '' !== $response['error_code']
			? $response['error_code']
			: 'http_error';

		return array(
			'ok'         => false,
			'error_code' => $code,
		);
	}

	/**
	 * Persist last apply summary.
	 *
	 * @param array $summary Summary payload.
	 * @return void
	 */
	private static function store_last_apply( array $summary ) {
		update_option( self::OPTION_LAST_APPLY, $summary, false );
	}

	/**
	 * Redirect back to Products page with a notice slug.
	 *
	 * @param string      $notice applied|error.
	 * @param string|null $error  Optional error slug.
	 * @return void
	 */
	private static function redirect_with_notice( $notice, $error = null ) {
		$args = array(
			'page'           => 'pricing-products',
			'pricing_notice' => $notice,
		);

		if ( null !== $error && '' !== $error ) {
			$args['pricing_error'] = sanitize_key( $error );
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}
}
