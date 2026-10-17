<?php
/**
 * WooCommerce Sync-Back: Syncs approved prices from SaaS panel back to WooCommerce.
 *
 * @package Pricing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles automated cron polling, instant REST push, and admin manual sync-back.
 */
class Pricing_Price_Sync_Back {

	/**
	 * Option: summary of last sync-back run.
	 *
	 * @var string
	 */
	const OPTION_LAST_SYNC_BACK = 'pricing_last_sync_back';

	/**
	 * WP-Cron action hook name.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'pricing_cron_sync_prices';

	/**
	 * Nonce action for admin form.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'pricing_sync_back';

	/**
	 * Register all actions, cron hooks, and REST endpoints.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'admin_post_pricing_sync_back', array( __CLASS__, 'handle_admin_sync' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'pull_and_apply_pending_prices' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Last sync-back summary for UI display.
	 *
	 * @return array{synced: int, total: int, last_run: string, ok: bool}|null
	 */
	public static function get_last_sync_back() {
		$data = get_option( self::OPTION_LAST_SYNC_BACK, null );
		return is_array( $data ) ? $data : null;
	}

	/**
	 * Handle admin button POST to manually trigger sync-back.
	 *
	 * @return void
	 */
	public static function handle_admin_sync() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'شما اجازه انجام این عملیات را ندارید.', 'pricing' ), 403 );
		}

		check_admin_referer( self::NONCE_ACTION );

		if ( ! Pricing_Connection::is_connected() ) {
			wp_safe_redirect( add_query_arg( array( 'pricing_notice' => 'not_connected' ), wp_get_referer() ) );
			exit;
		}

		$result = self::pull_and_apply_pending_prices();

		$redirect_url = wp_get_referer();
		if ( ! $redirect_url ) {
			$redirect_url = admin_url( 'admin.php?page=pricing-products' );
		}

		if ( ! empty( $result['ok'] ) ) {
			$redirect_url = add_query_arg(
				array(
					'pricing_notice' => 'sync_back_success',
					'synced_count'   => isset( $result['synced'] ) ? (int) $result['synced'] : 0,
				),
				$redirect_url
			);
		} else {
			$redirect_url = add_query_arg(
				array(
					'pricing_notice' => 'sync_back_error',
					'error_code'     => isset( $result['error_code'] ) ? (string) $result['error_code'] : 'unknown',
				),
				$redirect_url
			);
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Pulls pending price changes from API and writes them into WooCommerce.
	 *
	 * @return array{ok: bool, synced: int, total: int, error_code: string|null}
	 */
	public static function pull_and_apply_pending_prices() {
		if ( ! Pricing_Connection::is_connected() ) {
			return array(
				'ok'         => false,
				'synced'     => 0,
				'total'      => 0,
				'error_code' => 'not_connected',
			);
		}

		$response = Pricing_Http_Client::request( 'GET', '/connector/prices/pending' );

		if ( empty( $response['ok'] ) || ! is_array( $response['data'] ) ) {
			$code = isset( $response['error_code'] ) && is_string( $response['error_code'] )
				? $response['error_code']
				: 'http_error';

			return array(
				'ok'         => false,
				'synced'     => 0,
				'total'      => 0,
				'error_code' => $code,
			);
		}

		$items = isset( $response['data']['items'] ) && is_array( $response['data']['items'] )
			? $response['data']['items']
			: array();

		$total = count( $items );
		if ( 0 === $total ) {
			update_option(
				self::OPTION_LAST_SYNC_BACK,
				array(
					'synced'   => 0,
					'total'    => 0,
					'last_run' => gmdate( 'Y-m-d H:i:s' ),
					'ok'       => true,
				)
			);

			return array(
				'ok'         => true,
				'synced'     => 0,
				'total'      => 0,
				'error_code' => null,
			);
		}

		$synced_ids = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$external_id = isset( $item['external_id'] ) ? (string) $item['external_id'] : '';
			$sku         = isset( $item['sku'] ) ? (string) $item['sku'] : '';
			$price       = isset( $item['price'] ) ? (string) $item['price'] : '';

			if ( '' === $price || ! preg_match( '/^\d+(\.\d+)?$/', $price ) ) {
				continue;
			}

			$product = null;
			if ( '' !== $external_id && is_numeric( $external_id ) ) {
				$product = wc_get_product( (int) $external_id );
			}

			if ( ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) && '' !== $sku ) {
				$product_id_by_sku = wc_get_product_id_by_sku( $sku );
				if ( $product_id_by_sku > 0 ) {
					$product = wc_get_product( $product_id_by_sku );
				}
			}

			if ( ! is_object( $product ) || ! method_exists( $product, 'set_regular_price' ) ) {
				continue;
			}

			try {
				$old_price = method_exists( $product, 'get_regular_price' ) ? $product->get_regular_price() : '';
				$product->set_regular_price( $price );
				if ( method_exists( $product, 'set_price' ) ) {
					$product->set_price( $price );
				}
				$product->save();

				do_action( 'pricing_price_synced_back', $product->get_id(), $price, $old_price, $item );

				$synced_ids[] = $external_id;
			} catch ( Exception $e ) {
				// Continue with other products even if one fails.
			}
		}

		// Acknowledge back to API if any were updated.
		if ( ! empty( $synced_ids ) ) {
			Pricing_Http_Client::request(
				'POST',
				'/connector/prices/ack',
				array( 'synced_external_ids' => $synced_ids )
			);
		}

		$synced_count = count( $synced_ids );

		update_option(
			self::OPTION_LAST_SYNC_BACK,
			array(
				'synced'   => $synced_count,
				'total'    => $total,
				'last_run' => gmdate( 'Y-m-d H:i:s' ),
				'ok'       => true,
			)
		);

		return array(
			'ok'         => true,
			'synced'     => $synced_count,
			'total'      => $total,
			'error_code' => null,
		);
	}

	/**
	 * Register REST API route for instant push updates from SaaS engine.
	 *
	 * @return void
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'pricing/v1',
			'/update-price',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle_rest_update_price' ),
				'permission_callback' => array( __CLASS__, 'validate_rest_bearer' ),
			)
		);
	}

	/**
	 * Verify that incoming REST call provides matching API Bearer token.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	public static function validate_rest_bearer( $request ) {
		$auth_header = (string) $request->get_header( 'authorization' );
		if ( '' === $auth_header ) {
			return false;
		}

		if ( ! preg_match( '/^Bearer\s+(.+)$/i', $auth_header, $matches ) ) {
			return false;
		}

		$provided_key = trim( $matches[1] );
		$stored_key   = (string) get_option( Pricing_Connection::OPTION_API_KEY, '' );

		return '' !== $stored_key && hash_equals( $stored_key, $provided_key );
	}

	/**
	 * Handles direct REST price update for a single product.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function handle_rest_update_price( $request ) {
		$params      = $request->get_json_params();
		$external_id = isset( $params['external_id'] ) ? (string) $params['external_id'] : '';
		$price       = isset( $params['price'] ) ? (string) $params['price'] : '';

		if ( '' === $external_id || '' === $price || ! preg_match( '/^\d+(\.\d+)?$/', $price ) ) {
			return new WP_REST_Response(
				array(
					'ok'      => false,
					'message' => 'Invalid external_id or price.',
				),
				400
			);
		}

		$product = wc_get_product( (int) $external_id );
		if ( ! is_object( $product ) || ! method_exists( $product, 'set_regular_price' ) ) {
			return new WP_REST_Response(
				array(
					'ok'      => false,
					'message' => 'Product not found.',
				),
				404
			);
		}

		try {
			$old_price = method_exists( $product, 'get_regular_price' ) ? $product->get_regular_price() : '';
			$product->set_regular_price( $price );
			if ( method_exists( $product, 'set_price' ) ) {
				$product->set_price( $price );
			}
			$product->save();

			do_action( 'pricing_price_synced_back', $product->get_id(), $price, $old_price, $params );

			return new WP_REST_Response(
				array(
					'ok'          => true,
					'external_id' => $external_id,
					'price'       => $price,
				),
				200
			);
		} catch ( Exception $e ) {
			return new WP_REST_Response(
				array(
					'ok'      => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}
}
