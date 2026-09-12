<?php
/**
 * Admin menu registration for Pricing shell.
 *
 * @package Pricing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers top-level Pricing menu, submenus, and admin assets.
 */
class Pricing_Admin_Menu {

	/**
	 * Overview (top-level) page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'pricing';

	/**
	 * Connection submenu slug.
	 *
	 * @var string
	 */
	const PAGE_CONNECTION = 'pricing-connection';

	/**
	 * Products submenu slug.
	 *
	 * @var string
	 */
	const PAGE_PRODUCTS = 'pricing-products';

	/**
	 * Register menu and assets hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Top-level Pricing menu + Overview / Connection / Products.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page(
			__( 'Pricing', 'pricing' ),
			__( 'Pricing', 'pricing' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( 'Pricing_Admin_Pages', 'render_overview' ),
			'dashicons-tag',
			56
		);

		add_submenu_page(
			self::PAGE_SLUG,
			__( 'نمای کلی', 'pricing' ),
			__( 'نمای کلی', 'pricing' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( 'Pricing_Admin_Pages', 'render_overview' )
		);

		add_submenu_page(
			self::PAGE_SLUG,
			__( 'اتصال', 'pricing' ),
			__( 'اتصال', 'pricing' ),
			'manage_woocommerce',
			self::PAGE_CONNECTION,
			array( 'Pricing_Admin_Pages', 'render_connection' )
		);

		add_submenu_page(
			self::PAGE_SLUG,
			__( 'محصولات', 'pricing' ),
			__( 'محصولات', 'pricing' ),
			'manage_woocommerce',
			self::PAGE_PRODUCTS,
			array( 'Pricing_Admin_Pages', 'render_products' )
		);
	}

	/**
	 * Enqueue brand + shell CSS only on Pricing admin screens.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( ! $this->is_pricing_screen( $hook_suffix ) ) {
			return;
		}

		wp_enqueue_style(
			'pricing-admin-tokens',
			PRICING_PLUGIN_URL . 'assets/css/admin-tokens.css',
			array(),
			PRICING_VERSION
		);

		wp_enqueue_style(
			'pricing-admin',
			PRICING_PLUGIN_URL . 'assets/css/admin.css',
			array( 'pricing-admin-tokens' ),
			PRICING_VERSION
		);
	}

	/**
	 * Whether the current admin screen belongs to this plugin.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return bool
	 */
	private function is_pricing_screen( $hook_suffix ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && isset( $screen->id ) && false !== strpos( $screen->id, 'pricing' ) ) {
			return true;
		}

		$hook = (string) $hook_suffix;
		return (
			false !== strpos( $hook, self::PAGE_SLUG )
			|| false !== strpos( $hook, self::PAGE_CONNECTION )
			|| false !== strpos( $hook, self::PAGE_PRODUCTS )
		);
	}
}
