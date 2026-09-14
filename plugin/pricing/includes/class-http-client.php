<?php
/**
 * Shared HTTP client for Pricing API connector calls.
 *
 * @package Pricing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Thin wp_remote_* wrapper. No endpoint-specific logic.
 */
class Pricing_Http_Client {

	/**
	 * Default request timeout in seconds.
	 *
	 * @var int
	 */
	const TIMEOUT = 15;

	/**
	 * Perform an HTTP request against the Pricing API.
	 *
	 * @param string      $method   HTTP method (GET, POST, …).
	 * @param string      $path     Path with leading slash, e.g. /connector/validate.
	 * @param array|null  $body     JSON-serializable body; null for no body. Empty POST uses {}.
	 * @param string|null $api_key  Bearer key; falls back to stored option.
	 * @param string|null $base_url API base URL; falls back to stored option.
	 * @return array{
	 *     ok: bool,
	 *     status: int,
	 *     data: array|null,
	 *     error_code: string|null,
	 *     error_message: string|null
	 * }
	 */
	public static function request( $method, $path, $body = null, $api_key = null, $base_url = null ) {
		$base = is_string( $base_url ) && '' !== $base_url
			? $base_url
			: (string) get_option( 'pricing_api_base_url', '' );
		$key  = is_string( $api_key ) && '' !== $api_key
			? $api_key
			: (string) get_option( 'pricing_api_key', '' );

		$base = untrailingslashit( trim( $base ) );
		$path = '/' . ltrim( (string) $path, '/' );

		if ( '' === $base ) {
			return self::result( false, 0, null, 'validation_error', 'API base URL is missing.' );
		}

		if ( '' === $key ) {
			return self::result( false, 0, null, 'validation_error', 'API key is missing.' );
		}

		$url = $base . $path;

		$headers = array(
			'Authorization' => 'Bearer ' . $key,
			'Content-Type'  => 'application/json',
			'Accept'        => 'application/json',
		);

		$args = array(
			'method'  => strtoupper( (string) $method ),
			'timeout' => self::TIMEOUT,
			'headers' => $headers,
		);

		$method_upper = $args['method'];
		if ( null !== $body || 'POST' === $method_upper || 'PUT' === $method_upper || 'PATCH' === $method_upper ) {
			if ( null === $body || ( is_array( $body ) && array() === $body ) ) {
				$args['body'] = '{}';
			} else {
				$encoded = wp_json_encode( $body );
				if ( false === $encoded ) {
					return self::result( false, 0, null, 'validation_error', 'Request body could not be encoded.' );
				}
				$args['body'] = $encoded;
			}
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return self::result(
				false,
				0,
				null,
				'http_error',
				$response->get_error_message()
			);
		}

		$status   = (int) wp_remote_retrieve_response_code( $response );
		$raw_body = (string) wp_remote_retrieve_body( $response );
		$data     = null;

		if ( '' !== $raw_body ) {
			$decoded = json_decode( $raw_body, true );
			if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
				if ( $status >= 200 && $status < 300 ) {
					return self::result( false, $status, null, 'http_error', 'Response was not valid JSON.' );
				}
				return self::result( false, $status, null, 'http_error', 'Response was not valid JSON.' );
			}
			$data = $decoded;
		} elseif ( $status >= 200 && $status < 300 ) {
			return self::result( false, $status, null, 'http_error', 'Response was not valid JSON.' );
		}

		$ok = ( $status >= 200 && $status < 300 );

		if ( $ok ) {
			return self::result( true, $status, $data, null, null );
		}

		$error_code    = 'http_error';
		$error_message = 'Request failed.';

		if ( is_array( $data ) && isset( $data['error'] ) && is_array( $data['error'] ) ) {
			if ( ! empty( $data['error']['code'] ) && is_string( $data['error']['code'] ) ) {
				$error_code = $data['error']['code'];
			}
			if ( ! empty( $data['error']['message'] ) && is_string( $data['error']['message'] ) ) {
				$error_message = $data['error']['message'];
			}
		}

		return self::result( false, $status, $data, $error_code, $error_message );
	}

	/**
	 * Build a normalized result array.
	 *
	 * @param bool        $ok            Whether response was 2xx.
	 * @param int         $status        HTTP status (0 on transport error).
	 * @param array|null  $data          Decoded JSON body.
	 * @param string|null $error_code    Contract or transport error code.
	 * @param string|null $error_message Human-readable message (English; UI maps to Farsi).
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
