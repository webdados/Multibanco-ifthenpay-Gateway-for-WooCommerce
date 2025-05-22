<?php
/**
 * Plugin Name:          Multibanco, MB WAY, Credit card, Apple Pay, Google Pay, Payshop, Cofidis Pay, and PIX (ifthenpay) for WooCommerce
 * Plugin URI:           https://www.webdados.pt/wordpress/plugins/multibanco-ifthen-software-gateway-woocommerce-wordpress/
 * Description:          Secure WooCommerce payments with Multibanco, MB WAY, Credit card, Apple Pay, Google Pay, Payshop, Cofidis, and PIX via ifthenpay’s payment gateway.
 * Version:              10.4.1
 * Author:               Naked Cat Plugins (by Webdados)
 * Author URI:           https://nakedcatplugins.com
 * Text Domain:          multibanco-ifthen-software-gateway-for-woocommerce
 * Requires at least:    5.8
 * Tested up to:         6.8
 * Requires PHP:         7.2
 * WC requires at least: 7.1
 * WC tested up to:      9.9
 * Requires Plugins:     woocommerce
 * License:              GPLv3
 **/

/* WooCommerce CRUD ready */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'WC_IFTHENPAY_WEBDADOS_REQUIRED_WC_VERSION', '7.1' );
define( 'WC_IFTHENPAY_WEBDADOS_PLUGIN_FILE', __FILE__ );

/**
 * Our own order class and the main classes
 *
 * @return void
 */
function mbifthen_init() {
	if ( class_exists( 'WooCommerce' ) && defined( 'WC_VERSION' ) && version_compare( WC_VERSION, WC_IFTHENPAY_WEBDADOS_REQUIRED_WC_VERSION, '>=' ) ) {
		require_once dirname( WC_IFTHENPAY_WEBDADOS_PLUGIN_FILE ) . '/class-wc-ifthen-webdados.php';
		require_once dirname( WC_IFTHENPAY_WEBDADOS_PLUGIN_FILE ) . '/class-wc-multibanco-ifthen-webdados.php';
		require_once dirname( WC_IFTHENPAY_WEBDADOS_PLUGIN_FILE ) . '/class-wc-mbway-ifthen-webdados.php';
		require_once dirname( WC_IFTHENPAY_WEBDADOS_PLUGIN_FILE ) . '/class-wc-creditcard-ifthen-webdados.php';
		require_once dirname( WC_IFTHENPAY_WEBDADOS_PLUGIN_FILE ) . '/class-wc-payshop-ifthen-webdados.php';
		require_once dirname( WC_IFTHENPAY_WEBDADOS_PLUGIN_FILE ) . '/class-wc-cofidispay-ifthen-webdados.php';
		require_once dirname( WC_IFTHENPAY_WEBDADOS_PLUGIN_FILE ) . '/class-wc-gateway-ifthen-webdados.php';
		$GLOBALS['WC_IfthenPay_Webdados'] = WC_IfthenPay_Webdados();
		/* Add settings links - This is here because inside the main class we cannot call the correct plugin_basename( WC_IFTHENPAY_WEBDADOS_PLUGIN_FILE ) */
		add_filter( 'plugin_action_links_' . plugin_basename( WC_IFTHENPAY_WEBDADOS_PLUGIN_FILE ), array( WC_IfthenPay_Webdados(), 'add_settings_link' ) );
	} else {
		add_action( 'admin_notices', 'mbifthen_woocommerce_not_active_admin_notices' );
	}
}
add_action( 'plugins_loaded', 'mbifthen_init', 1 );

/**
 * Main class
 *
 * @return WC_IfthenPay_Webdados
 */
function WC_IfthenPay_Webdados() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	if ( ! defined( 'WC_IFTHENPAY_WEBDADOS_VERSION' ) ) {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php'; // Should not be necessary, but we never know...
		}
		$plugin_data = get_plugin_data( __FILE__, false, false );
		define( 'WC_IFTHENPAY_WEBDADOS_VERSION', $plugin_data['Version'] );
	}
	return WC_IfthenPay_Webdados::instance( WC_IFTHENPAY_WEBDADOS_VERSION );
}

/**
 * Format MB reference - We keep it because someone may be using it externally
 *
 * @param  string $ref Multibanco reference.
 * @return string
 */
function mbifthen_format_ref( $ref ) {
	return class_exists( 'WC_IfthenPay_Webdados' ) ? WC_IfthenPay_Webdados()->format_multibanco_ref( $ref ) : $ref;
}

/**
 * Admin notice if dependecies not met
 *
 * @return void
 */
function mbifthen_woocommerce_not_active_admin_notices() {
	if ( ! function_exists( 'get_plugin_data' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php'; // Should not be necessary, but we never know...
	}
	$plugin_data = get_plugin_data( __FILE__, false, false );
	?>
	<div class="notice notice-error is-dismissible">
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %1$s:plugin name, %2$s: required WooCommerce version */
					__( '<strong>%1$s</strong> is installed and active but <strong>WooCommerce (%2$s or above)</strong> is not.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					esc_html( $plugin_data['Name'] ),
					esc_html( WC_IFTHENPAY_WEBDADOS_REQUIRED_WC_VERSION )
				)
			);
			?>
		</p>
	</div>
	<?php
}

/* HPOS & Blocks Compatible */
add_action(
	'before_woocommerce_init',
	function () {
		if ( version_compare( WC_VERSION, WC_IFTHENPAY_WEBDADOS_REQUIRED_WC_VERSION, '>=' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WC_IFTHENPAY_WEBDADOS_PLUGIN_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', WC_IFTHENPAY_WEBDADOS_PLUGIN_FILE, true );
		}
	}
);

/* If you're reading this you must know what you're doing ;-) Greetings from sunny Portugal! */
