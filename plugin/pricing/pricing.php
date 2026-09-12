<?php
/**
 * Plugin Name:       Pricing
 * Plugin URI:        https://pricing.example
 * Description:       WooCommerce connector for Pricing SaaS (admin shell).
 * Version:           0.1.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Pricing
 * Text Domain:       pricing
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 *
 * @package Pricing
 */

defined( 'ABSPATH' ) || exit;

define( 'PRICING_VERSION', '0.1.1' );
define( 'PRICING_PLUGIN_FILE', __FILE__ );
define( 'PRICING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PRICING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PRICING_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once PRICING_PLUGIN_DIR . 'includes/class-dependencies.php';
require_once PRICING_PLUGIN_DIR . 'includes/class-admin-pages.php';
require_once PRICING_PLUGIN_DIR . 'includes/class-admin-menu.php';
require_once PRICING_PLUGIN_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'Pricing_Dependencies', 'activate' ) );

/**
 * Boot the plugin after all plugins are loaded.
 *
 * @return void
 */
function pricing_bootstrap() {
	Pricing_Plugin::instance()->init();
}
add_action( 'plugins_loaded', 'pricing_bootstrap' );
