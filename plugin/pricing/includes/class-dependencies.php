<?php
/**
 * WooCommerce dependency guard.
 *
 * @package Pricing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Checks WooCommerce and fails activation cleanly when missing.
 */
class Pricing_Dependencies {

	/**
	 * Activation callback: require WooCommerce or fail cleanly.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( self::is_woocommerce_active() ) {
			return;
		}

		deactivate_plugins( PRICING_PLUGIN_BASENAME );

		$message = esc_html__(
			'Pricing requires WooCommerce to be installed and active. The plugin has been deactivated.',
			'pricing'
		);

		wp_die(
			$message,
			esc_html__( 'Plugin dependency check', 'pricing' ),
			array( 'back_link' => true )
		);
	}

	/**
	 * Whether WooCommerce is available.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Runtime guard: if WooCommerce disappears while Pricing is active, warn admins.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'admin_notices', array( __CLASS__, 'maybe_show_missing_notice' ) );
	}

	/**
	 * Admin notice when WooCommerce is not available at runtime.
	 *
	 * @return void
	 */
	public static function maybe_show_missing_notice() {
		if ( self::is_woocommerce_active() ) {
			return;
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>';
		echo esc_html__(
			'Pricing requires WooCommerce to be installed and active.',
			'pricing'
		);
		echo '</p></div>';
	}
}
