<?php
/**
 * Main plugin bootstrap class.
 *
 * @package Pricing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates dependency checks and feature registration.
 */
class Pricing_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Pricing_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return Pricing_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Wire hooks. Admin menu only when WooCommerce is present.
	 *
	 * @return void
	 */
	public function init() {
		Pricing_Dependencies::register_hooks();

		load_plugin_textdomain(
			'pricing',
			false,
			dirname( PRICING_PLUGIN_BASENAME ) . '/languages'
		);

		if ( ! Pricing_Dependencies::is_woocommerce_active() ) {
			return;
		}

		Pricing_Connection::register_hooks();
		Pricing_Product_Sync::register_hooks();
		Pricing_Price_Apply::register_hooks();

		$admin_menu = new Pricing_Admin_Menu();
		$admin_menu->register();
	}
}
