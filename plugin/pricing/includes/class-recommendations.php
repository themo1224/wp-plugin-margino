<?php
/**
 * Fetch recommended prices from the Pricing API.
 *
 * @package Pricing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Thin wrapper around GET /connector/products/{id}/recommendation.
 */
class Pricing_Recommendations {

	/**
	 * HTTP statuses / client codes that stop further GETs on a page.
	 *
	 * @var string[]
	 */
	const FATAL_CODES = array( 'http_error', 'invalid_api_key', 'plan_inactive', 'validation_error' );

	/**
	 * Fetch one product recommendation.
	 *
	 * @param string $external_id WooCommerce product ID as string.
	 * @return array{
	 *     ok: bool,
	 *     status: int,
	 *     data: array{
	 *         external_id: string,
	 *         recommended_price: string,
	 *         currency: string,
	 *         below_floor: bool,
	 *         floor_price: string|null,
	 *         updated_at: string
	 *     }|null,
	 *     error_code: string|null,
	 *     error_message: string|null
	 * }
	 */
	public static function fetch_for_product( $external_id ) {
		$id = (string) $external_id;
		if ( '' === $id ) {
			return self::result( false, 0, null, 'validation_error', 'external_id is missing.' );
		}

		$path = '/connector/products/' . rawurlencode( $id ) . '/recommendation';
		$response = Pricing_Http_Client::request( 'GET', $path, null );

		if ( ! empty( $response['ok'] ) && is_array( $response['data'] ) ) {
			return self::result( true, (int) $response['status'], self::normalize_data( $response['data'], $id ), null, null );
		}

		$status     = isset( $response['status'] ) ? (int) $response['status'] : 0;
		$error_code = isset( $response['error_code'] ) && is_string( $response['error_code'] )
			? $response['error_code']
			: 'http_error';
		$error_message = isset( $response['error_message'] ) && is_string( $response['error_message'] )
			? $response['error_message']
			: null;

		if ( 404 === $status || 'product_not_found' === $error_code ) {
			return self::result( false, $status, null, 'not_synced', $error_message );
		}

		return self::result( false, $status, null, $error_code, $error_message );
	}

	/**
	 * Fetch recommendations for a page of product ids (sequential).
	 * Stops further HTTP calls after the first fatal auth/transport error.
	 *
	 * @param string[] $ids External ids in display order.
	 * @return array{
	 *     results: array<string, array>,
	 *     page_error: string|null
	 * }
	 */
	public static function fetch_for_products( array $ids ) {
		$results    = array();
		$page_error = null;

		foreach ( $ids as $raw_id ) {
			$id = (string) $raw_id;
			if ( '' === $id ) {
				continue;
			}

			if ( null !== $page_error ) {
				$results[ $id ] = self::result( false, 0, null, $page_error, 'Skipped after page-level API error.' );
				continue;
			}

			$result         = self::fetch_for_product( $id );
			$results[ $id ] = $result;

			if ( self::is_fatal( $result ) ) {
				$page_error = isset( $result['error_code'] ) && is_string( $result['error_code'] )
					? $result['error_code']
					: 'http_error';
			}
		}

		return array(
			'results'    => $results,
			'page_error' => $page_error,
		);
	}

	/**
	 * Whether a result should stop further GETs on the page.
	 *
	 * @param array $result Normalized result.
	 * @return bool
	 */
	public static function is_fatal( array $result ) {
		if ( ! empty( $result['ok'] ) ) {
			return false;
		}

		$status = isset( $result['status'] ) ? (int) $result['status'] : 0;
		if ( 401 === $status || 403 === $status ) {
			return true;
		}

		$code = isset( $result['error_code'] ) && is_string( $result['error_code'] )
			? $result['error_code']
			: '';

		return in_array( $code, self::FATAL_CODES, true );
	}

	/**
	 * Normalize API recommendation payload.
	 *
	 * @param array  $data Decoded JSON.
	 * @param string $fallback_id External id fallback.
	 * @return array
	 */
	private static function normalize_data( array $data, $fallback_id ) {
		$below = ! empty( $data['below_floor'] );

		$floor = null;
		if ( array_key_exists( 'floor_price', $data ) && null !== $data['floor_price'] && '' !== $data['floor_price'] ) {
			$floor = (string) $data['floor_price'];
		}

		return array(
			'external_id'       => isset( $data['external_id'] ) ? (string) $data['external_id'] : (string) $fallback_id,
			'recommended_price' => isset( $data['recommended_price'] ) ? (string) $data['recommended_price'] : '',
			'currency'          => isset( $data['currency'] ) ? (string) $data['currency'] : '',
			'below_floor'       => $below,
			'floor_price'       => $floor,
			'updated_at'        => isset( $data['updated_at'] ) ? (string) $data['updated_at'] : '',
		);
	}

	/**
	 * Build a normalized result array.
	 *
	 * @param bool        $ok            Success.
	 * @param int         $status        HTTP status.
	 * @param array|null  $data          Normalized recommendation or null.
	 * @param string|null $error_code    Error slug.
	 * @param string|null $error_message Message.
	 * @return array
	 */
	private static function result( $ok, $status, $data, $error_code, $error_message ) {
		return array(
			'ok'            => (bool) $ok,
			'status'        => (int) $status,
			'data'          => $data,
			'error_code'    => $error_code,
			'error_message' => $error_message,
		);
	}
}
