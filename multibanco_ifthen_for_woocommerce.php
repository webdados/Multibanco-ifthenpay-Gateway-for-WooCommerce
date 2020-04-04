<?php
/**
 * Plugin Name: Multibanco, MBWAY and Payshop (IfthenPay) for WooCommerce
 * Plugin URI: https://www.webdados.pt/wordpress/plugins/multibanco-ifthen-software-gateway-woocommerce-wordpress/
 * Description: This plugin allows customers with a Portuguese bank account to pay WooCommerce orders using Multibanco (Pag. Serviços) and MB WAY, through IfthenPay’s payment gateway.
 * Version: 4.2.1
 * Author: Webdados
 * Author URI: https://www.webdados.pt
 * Text Domain: multibanco-ifthen-software-gateway-for-woocommerce
 * Domain Path: /lang
 * WC requires at least: 2.5.0
 * WC tested up to: 4.0.1
**/

/* WooCommerce CRUD ready */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/* Localization */
add_action( 'plugins_loaded', 'mbifthen_load_textdomain', 0 );
function mbifthen_load_textdomain() {
	//We keep looking for translations on our local folder because we are unable to load French and Spanish from GlotPress. Portuguese (AO90 and non-AO90) are still loaded from GlotPress)
	load_plugin_textdomain( 'multibanco-ifthen-software-gateway-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ).'/lang/' );
}

/* Check if WooCommerce is active - Get active network plugins - "Stolen" from Novalnet Payment Gateway */
function mbifthen_active_nw_plugins() {
	if ( !is_multisite() )
		return false;
	$mbifthen_activePlugins = ( get_site_option('active_sitewide_plugins' ) ) ? array_keys( get_site_option('active_sitewide_plugins' ) ) : array();
	return $mbifthen_activePlugins;
}
if ( in_array( 'woocommerce/woocommerce.php', (array) get_option( 'active_plugins' ) ) || in_array( 'woocommerce/woocommerce.php', (array) mbifthen_active_nw_plugins() ) ) {


	/* Our own order class and the main classes */
	add_action( 'plugins_loaded', 'mbifthen_init', 1 );
	function mbifthen_init() {
		if ( class_exists( 'WooCommerce' ) && defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.2.0', '>=' ) ) { //We check again because WooCommerce could have "died", also wee need to check the WooCommerce version
			require_once( dirname( __FILE__ ) . '/class-wc-ifthen-webdados.php' );
			require_once( dirname( __FILE__ ) . '/class-wc-order-mb-ifthen.php' );
			require_once( dirname( __FILE__ ) . '/class-wc-multibanco-ifthen-webdados.php' );
			require_once( dirname( __FILE__ ) . '/class-wc-mbway-ifthen-webdados.php' );
			if ( version_compare( WC_VERSION, '3.0', '>=' ) ) {
				require_once( dirname( __FILE__ ) . '/class-wc-payshop-ifthen-webdados.php' );
			}
			$GLOBALS['WC_IfthenPay_Webdados'] = WC_IfthenPay_Webdados();
			/* Add settings links - This is here because inside the main class we cannot call the correct plugin_basename( __FILE__ ) */
			add_filter( 'plugin_action_links_'.plugin_basename( __FILE__ ), array( WC_IfthenPay_Webdados(), 'add_settings_link' ) );
		} else {
			add_action( 'admin_notices', 'admin_notices_mbifthen_woocommerce_not_active' );
		}
	}

	/* Main class */
	function WC_IfthenPay_Webdados() {
		return WC_IfthenPay_Webdados::instance(); 
	}

	/* Format MB reference - We keep it because someone may be using it externally */
	function mbifthen_format_ref( $ref ) {
		return WC_IfthenPay_Webdados()->format_multibanco_ref( $ref );
	}

	
} else {


	add_action( 'admin_notices', 'admin_notices_mbifthen_woocommerce_not_active' );


}


function admin_notices_mbifthen_woocommerce_not_active() {
	?>
	<div class="notice notice-error is-dismissible">
		<p><?php _e( '<strong>Multibanco, MBWAY and Payshop (IfthenPay) for WooCommerce</strong> is installed and active but <strong>WooCommerce (2.5.0 or above)</strong> is not.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></p>
	</div>
	<?php
}


/* If you're reading this you must know what you're doing ;-) Greetings from sunny Portugal! */

