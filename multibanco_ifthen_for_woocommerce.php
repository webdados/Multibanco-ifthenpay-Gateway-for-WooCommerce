<?php
/**
 * Plugin Name: Multibanco, MB WAY, Credit card and Payshop (IfthenPay) for WooCommerce
 * Plugin URI: https://www.webdados.pt/wordpress/plugins/multibanco-ifthen-software-gateway-woocommerce-wordpress/
 * Description: This plugin allows customers with a Portuguese bank account to pay WooCommerce orders using Multibanco (Pag. Serviços), MB WAY, Credit card and Payshop through IfthenPay’s payment gateway.
 * Version: 5.0.1
 * Author: Webdados
 * Author URI: https://www.webdados.pt
 * Text Domain: multibanco-ifthen-software-gateway-for-woocommerce
 * Domain Path: /lang
 * WC requires at least: 3.0
 * WC tested up to: 5.2
**/

/* WooCommerce CRUD ready */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/* Our own order class and the main classes */
add_action( 'plugins_loaded', 'mbifthen_init', 1 );
function mbifthen_init() {
	if ( class_exists( 'WooCommerce' ) && defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '3.0', '>=' ) ) {
		require_once( dirname( __FILE__ ) . '/class-wc-ifthen-webdados.php' );
		require_once( dirname( __FILE__ ) . '/class-wc-multibanco-ifthen-webdados.php' );
		require_once( dirname( __FILE__ ) . '/class-wc-mbway-ifthen-webdados.php' );
		if ( version_compare( WC_VERSION, '4.0', '>=' ) ) {
			require_once( dirname( __FILE__ ) . '/class-wc-creditcard-ifthen-webdados.php' );
		}
		require_once( dirname( __FILE__ ) . '/class-wc-payshop-ifthen-webdados.php' );
		$GLOBALS['WC_IfthenPay_Webdados'] = WC_IfthenPay_Webdados();
		/* Add settings links - This is here because inside the main class we cannot call the correct plugin_basename( __FILE__ ) */
		add_filter( 'plugin_action_links_'.plugin_basename( __FILE__ ), array( WC_IfthenPay_Webdados(), 'add_settings_link' ) );
	} else {
		add_action( 'admin_notices', 'mbifthen_woocommerce_not_active_admin_notices' );
	}
}

/* Main class */
function WC_IfthenPay_Webdados() {
	return WC_IfthenPay_Webdados::instance(); 
}

/* Format MB reference - We keep it because someone may be using it externally */
function mbifthen_format_ref( $ref ) {
	return class_exists( 'WC_IfthenPay_Webdados' ) ? WC_IfthenPay_Webdados()->format_multibanco_ref( $ref ) : $ref;
}

function mbifthen_woocommerce_not_active_admin_notices() {
	?>
	<div class="notice notice-error is-dismissible">
		<p><?php _e( '<strong>Multibanco, MB WAY, Credit card and Payshop (IfthenPay) for WooCommerce</strong> is installed and active but <strong>WooCommerce (3.0 or above)</strong> is not.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></p>
	</div>
	<?php
}

/* If you're reading this you must know what you're doing ;-) Greetings from sunny Portugal! */

