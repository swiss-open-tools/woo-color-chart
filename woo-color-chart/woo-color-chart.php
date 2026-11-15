<?php
/**
 * Plugin Name:       Color Chart for WooCommerce
 * Plugin URI:        https://github.com/swiss-open-tools/woo-color-chart
 * Description:       Shows all colours of a variable product as a sortable, filterable colour chart with swatches, colour code, stock status and add-to-cart per colour. Shortcode [color_chart] and block.
 * Version:           0.1.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            swiss-open-tools
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woo-color-chart
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 * WC requires at least: 8.0
 *
 * @package WooColorChart
 */

defined( 'ABSPATH' ) || exit;

define( 'WCC_VERSION', '0.1.0' );
define( 'WCC_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCC_URL', plugin_dir_url( __FILE__ ) );

require_once WCC_DIR . 'includes/class-wcc-color.php';

// Declare HPOS compatibility (the plugin does not touch orders).
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		load_plugin_textdomain( 'woo-color-chart', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Color Chart for WooCommerce requires WooCommerce.', 'woo-color-chart' ) . '</p></div>';
				}
			);
			return;
		}
		require_once WCC_DIR . 'includes/class-wcc-admin.php';
		require_once WCC_DIR . 'includes/class-wcc-frontend.php';
		WCC_Admin::init();
		WCC_Frontend::init();
	}
);
