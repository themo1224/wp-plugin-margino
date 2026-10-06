<?php
/**
 * WooCommerce catalog sync to Pricing API.
 *
 * @package Pricing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds published-product payloads and handles admin-post sync.
 */
class Pricing_Product_Sync {

	/**
	 * Option: last sync summary (counts only).
	 *
	 * @var string
	 */
	const OPTION_LAST_SYNC = 'pricing_last_sync';

	/**
	 * Nonce action for sync form.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'pricing_sync';

	/**
	 * Max products per API request (matches api connector.sync_batch_max).
	 *
	 * @var int
	 */
	const BATCH_MAX = 500;

	/**
	 * Max rejected external_ids stored for the flash notice.
	 *
	 * @var int
	 */
	const REJECTED_IDS_CAP = 20;

	/**
	 * Register admin-post handler.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'admin_post_pricing_sync_products', array( __CLASS__, 'handle_sync' ) );
	}

	/**
	 * Last sync summary for the Products UI.
	 *
	 * @return array|null
	 */
	public static function get_last_sync() {
		$last = get_option( self::OPTION_LAST_SYNC, null );
		return is_array( $last ) ? $last : null;
	}

	/**
	 * Handle Sync form submission.
	 *
	 * @return void
	 */
	public static function handle_sync() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'شما اجازه این کار را ندارید.', 'pricing' ), 403 );
		}

		check_admin_referer( self::NONCE_ACTION );

		if ( ! Pricing_Connection::is_connected() ) {
			self::redirect_with_notice( 'error', 'not_connected' );
		}

		$result = self::sync();

		if ( ! empty( $result['ok'] ) ) {
			self::redirect_with_notice( 'synced' );
		}

		$code = isset( $result['error_code'] ) && is_string( $result['error_code'] ) && '' !== $result['error_code']
			? $result['error_code']
			: 'http_error';
		self::redirect_with_notice( 'error', $code );
	}

	/**
	 * Collect published products, POST in batches, persist summary.
	 *
	 * @return array{ok: bool, error_code: string|null}
	 */
	public static function sync() {
		$built    = self::build_catalog();
		$products = $built['products'];
		$skipped  = (int) $built['skipped'];

		$accepted_total = 0;
		$rejected_total = 0;
		$rejected_ids   = array();
		$error_code     = null;
		$ok             = true;

		if ( empty( $products ) ) {
			self::store_last_sync(
				array(
					'synced_at'    => gmdate( 'Y-m-d H:i:s' ),
					'synced'       => 0,
					'accepted'     => 0,
					'rejected'     => 0,
					'skipped'      => $skipped,
					'ok'           => true,
					'error_code'   => null,
					'partial'      => false,
					'rejected_ids' => array(),
				)
			);

			return array(
				'ok'         => true,
				'error_code' => null,
			);
		}

		$currency = function_exists( 'get_woocommerce_currency' ) ? (string) get_woocommerce_currency() : 'IRR';
		$chunks   = array_chunk( $products, self::BATCH_MAX );
		$partial  = false;

		foreach ( $chunks as $chunk ) {
			$body = array(
				'currency' => $currency,
				'products' => $chunk,
			);

			$response = Pricing_Http_Client::request( 'POST', '/connector/products/sync', $body );

			if ( ! $response['ok'] ) {
				$ok         = false;
				$error_code = is_string( $response['error_code'] ) && '' !== $response['error_code']
					? $response['error_code']
					: 'http_error';
				$partial = ( $accepted_total > 0 || $rejected_total > 0 );
				break;
			}

			$data = is_array( $response['data'] ) ? $response['data'] : array();

			if ( isset( $data['synced'] ) ) {
				$accepted_total += (int) $data['synced'];
			} elseif ( isset( $data['accepted'] ) && is_array( $data['accepted'] ) ) {
				$accepted_total += count( $data['accepted'] );
			}

			if ( isset( $data['rejected'] ) && is_array( $data['rejected'] ) ) {
				$rejected_total += count( $data['rejected'] );
				foreach ( $data['rejected'] as $item ) {
					if ( count( $rejected_ids ) >= self::REJECTED_IDS_CAP ) {
						break;
					}
					if ( is_array( $item ) && isset( $item['external_id'] ) ) {
						$rejected_ids[] = (string) $item['external_id'];
					}
				}
			}
		}

		self::store_last_sync(
			array(
				'synced_at'    => gmdate( 'Y-m-d H:i:s' ),
				'synced'       => $accepted_total,
				'accepted'     => $accepted_total,
				'rejected'     => $rejected_total,
				'skipped'      => $skipped,
				'ok'           => $ok,
				'error_code'   => $error_code,
				'partial'      => $partial || ( ! $ok && ( $accepted_total > 0 || $rejected_total > 0 ) ),
				'rejected_ids' => $rejected_ids,
			)
		);

		return array(
			'ok'         => $ok,
			'error_code' => $error_code,
		);
	}

	/**
	 * Build sync rows from published WooCommerce products (no variation children).
	 *
	 * @return array{products: list<array{external_id: string, sku: string|null, name: string, price: string, brand?: string, barcode?: string}>, skipped: int}
	 */
	public static function build_catalog() {
		$products = array();
		$skipped  = 0;

		if ( ! function_exists( 'wc_get_products' ) ) {
			return array(
				'products' => $products,
				'skipped'  => 0,
			);
		}

		$items = wc_get_products(
			array(
				'status' => 'publish',
				'limit'  => -1,
				'type'   => array( 'simple', 'variable', 'external', 'grouped' ),
				'return' => 'objects',
			)
		);

		if ( ! is_array( $items ) ) {
			return array(
				'products' => $products,
				'skipped'  => 0,
			);
		}

		foreach ( $items as $product ) {
			if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
				continue;
			}

			// Defense: never send variation children as separate rows.
			if ( method_exists( $product, 'is_type' ) && $product->is_type( 'variation' ) ) {
				continue;
			}

			$price = self::resolve_price( $product );
			if ( null === $price ) {
				$skipped++;
				continue;
			}

			$sku = method_exists( $product, 'get_sku' ) ? $product->get_sku() : '';
			$sku = is_string( $sku ) && '' !== $sku ? $sku : null;

			$name = method_exists( $product, 'get_name' ) ? (string) $product->get_name() : '';
			if ( '' === $name ) {
				$skipped++;
				continue;
			}

			$row = array(
				'external_id' => (string) $product->get_id(),
				'sku'         => $sku,
				'name'        => $name,
				'price'       => $price,
			);

			$brand = self::resolve_brand( $product );
			if ( null !== $brand ) {
				$row['brand'] = $brand;
			}

			$barcode = self::resolve_barcode( $product );
			if ( null !== $barcode ) {
				$row['barcode'] = $barcode;
			}

			$products[] = $row;
		}

		return array(
			'products' => $products,
			'skipped'  => $skipped,
		);
	}

	/**
	 * Best-effort brand from WC attributes / meta.
	 *
	 * @param object $product WC product.
	 * @return string|null
	 */
	private static function resolve_brand( $product ) {
		$candidates = array();

		if ( method_exists( $product, 'get_attribute' ) ) {
			foreach ( array( 'pa_brand', 'brand', 'برند' ) as $attr ) {
				$val = (string) $product->get_attribute( $attr );
				if ( '' !== trim( $val ) ) {
					$candidates[] = $val;
				}
			}
		}

		if ( method_exists( $product, 'get_meta' ) ) {
			foreach ( array( '_brand', 'brand', 'product_brand' ) as $meta_key ) {
				$val = $product->get_meta( $meta_key, true );
				if ( is_string( $val ) && '' !== trim( $val ) ) {
					$candidates[] = $val;
				}
			}
		}

		foreach ( $candidates as $raw ) {
			$clean = self::clean_text_field( $raw, 191 );
			if ( null !== $clean ) {
				return $clean;
			}
		}

		return null;
	}

	/**
	 * Best-effort GTIN/barcode from common WooCommerce metas.
	 *
	 * @param object $product WC product.
	 * @return string|null
	 */
	private static function resolve_barcode( $product ) {
		if ( ! method_exists( $product, 'get_meta' ) ) {
			return null;
		}

		$keys = array(
			'_global_unique_id',
			'_wpm_gtin_code',
			'_gtin',
			'_ean',
			'_barcode',
			'barcode',
			'gtin',
		);

		foreach ( $keys as $meta_key ) {
			$val = $product->get_meta( $meta_key, true );
			if ( ! is_string( $val ) && ! is_numeric( $val ) ) {
				continue;
			}
			$clean = self::clean_text_field( (string) $val, 64 );
			if ( null !== $clean ) {
				return $clean;
			}
		}

		return null;
	}

	/**
	 * @param string $value Raw value.
	 * @param int    $max   Max length.
	 * @return string|null
	 */
	private static function clean_text_field( $value, $max ) {
		$trimmed = trim( wp_strip_all_tags( $value ) );
		if ( '' === $trimmed ) {
			return null;
		}

		if ( function_exists( 'mb_substr' ) ) {
			$trimmed = mb_substr( $trimmed, 0, $max );
		} else {
			$trimmed = substr( $trimmed, 0, $max );
		}

		return '' !== $trimmed ? $trimmed : null;
	}

	/**
	 * Resolve a non-negative decimal price string, or null to skip locally.
	 *
	 * @param object $product WC product.
	 * @return string|null
	 */
	private static function resolve_price( $product ) {
		$raw = '';

		if ( method_exists( $product, 'is_type' ) && $product->is_type( 'variable' ) ) {
			if ( method_exists( $product, 'get_variation_price' ) ) {
				$raw = (string) $product->get_variation_price( 'min', false );
			}
			if ( '' === $raw && method_exists( $product, 'get_variation_regular_price' ) ) {
				$raw = (string) $product->get_variation_regular_price( 'min', false );
			}
		} else {
			if ( method_exists( $product, 'get_price' ) ) {
				$raw = (string) $product->get_price();
			}
			if ( '' === $raw && method_exists( $product, 'get_regular_price' ) ) {
				$raw = (string) $product->get_regular_price();
			}
		}

		$raw = trim( $raw );
		if ( '' === $raw ) {
			return null;
		}

		// Normalize to a plain decimal string the API accepts.
		if ( function_exists( 'wc_format_decimal' ) ) {
			$formatted = wc_format_decimal( $raw, false, true );
			if ( is_string( $formatted ) && '' !== $formatted ) {
				$raw = $formatted;
			}
		}

		if ( ! preg_match( '/^\d+(\.\d+)?$/', $raw ) ) {
			return null;
		}

		return $raw;
	}

	/**
	 * Persist last sync summary.
	 *
	 * @param array $summary Summary payload.
	 * @return void
	 */
	private static function store_last_sync( array $summary ) {
		update_option( self::OPTION_LAST_SYNC, $summary, true );
	}

	/**
	 * Redirect back to Products page with a notice slug.
	 *
	 * @param string      $notice synced|error.
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
