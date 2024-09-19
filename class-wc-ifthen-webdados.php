<?php
/**
 * Main plugin class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Our main class
 */
final class WC_IfthenPay_Webdados {

	/* Version */
	public $version = false;

	/* IDs */
	public $multibanco_id = 'multibanco_ifthen_for_woocommerce';
	public $mbway_id      = 'mbway_ifthen_for_woocommerce';
	public $payshop_id    = 'payshop_ifthen_for_woocommerce';
	public $creditcard_id = 'creditcard_ifthen_for_woocommerce';
	public $cofidispay_id = 'cofidispay_ifthen_for_woocommerce';

	/* Debug */
	public $log = null;

	/* Internal variables */
	public $pro_add_on_active       = false;
	public $wpml_active             = false;
	public $wpml_translation_info   = '';
	public $wc_deposits_active      = false;
	public $wc_subscriptions_active = false;
	public $wc_blocks_active        = false;
	public $mb_ifthen_locale        = null;
	public $out_link_utm            = '';
	public $is_pay_form             = false;
	public $callback_email          = 'callback@ifthenpay.com';
	public $callback_webservice     = 'https://www.ifthenpay.com/api/endpoint/callback/activation';
	public $unpaid_statuses         = array( 'on-hold', 'pending', 'partially-paid' );
	public $hpos_enabled            = false;
	public $refunds_url             = 'https://ifthenpay.com/api/endpoint/payments/refund';
	private $gateways_loaded        = false;

	/* Internal variables - For Multibanco */
	public $multibanco_settings                    = null;
	public $multibanco_notify_url                  = '';
	public $multibanco_ents_no_check_digit         = array( // Special entities with no check digit
		21721,
	);
	public $multibanco_ents_no_repeat              = array( // Special entities with no repetition allowed in "x" days, no matter the order status - Only WooCommerce 3.0 and above
		'11687' => 180,
	);
	public $multibanco_action_deposits_set         = false;
	public $multibanco_deposits_already_forced     = false;
	public $multibanco_ref_mode                    = 'random';
	public $multibanco_last_incremental_expire_ref = null;
	public $multibanco_min_value                   = 0;
	public $multibanco_max_value                   = 99999.99;
	public $multibanco_banner_email                = '';
	public $multibanco_banner                      = '';
	public $multibanco_icon                        = '';
	public $multibanco_api_mode_available          = true;
	public $multibanco_api_mode_enabled            = false;
	public $multibanco_api_url                     = 'https://ifthenpay.com/api/multibanco/reference/init';


	/* Internal variables - For MB WAY */
	public $mbway_settings               = null;
	public $mbway_notify_url             = '';
	public $mbway_minutes                = 5;
	public $mbway_multiplier_new_payment = 1.2;
	public $mbway_min_value              = 0;
	public $mbway_max_value              = 99999.99;
	public $mbway_banner_email           = '';
	public $mbway_banner                 = '';
	public $mbway_icon                   = '';
	public $mbway_webservice_url         = 'https://mbway.ifthenpay.com/IfthenPayMBW.asmx';


	/* Internal variables - For Payshop */
	public $payshop_settings                = null;
	public $payshop_notify_url              = '';
	public $payshop_action_deposits_set     = false;
	public $payshop_deposits_already_forced = false;
	public $payshop_min_value               = 0;
	public $payshop_max_value               = 4000;
	public $payshop_banner_email            = '';
	public $payshop_banner                  = '';
	public $payshop_icon                    = '';


	/* Internal variables - For Credit card */
	public $creditcard_settings     = null;
	public $creditcard_notify_url   = '';
	public $creditcard_min_value    = 0; /* No limit in theory */
	public $creditcard_max_value    = 99999.99; /* No limit in theory */
	public $creditcard_banner_email = ''; /* Needed ? */
	public $creditcard_banner       = ''; /* Needed ? */
	public $creditcard_icon         = '';


	/* Internal variables - For Cofidis Pay */
	public $cofidispay_settings     = null;
	public $cofidispay_notify_url   = '';
	public $cofidispay_return_url   = '';
	public $cofidispay_min_value    = 0; /* No limit in theory */
	public $cofidispay_max_value    = 99999.99; /* No limit in theory */
	public $cofidispay_banner_email = ''; /* Needed ? */
	public $cofidispay_banner       = ''; /* Needed ? */
	public $cofidispay_icon         = '';

	/* Single instance */
	protected static $_instance = null;

	/* Constructor */
	public function __construct( $version ) {
		$this->version           = $version;
		$this->pro_add_on_active = function_exists( 'WC_IfthenPay_Pro' );
		$this->wpml_active       = function_exists( 'icl_object_id' ) && function_exists( 'icl_register_string' );
		if ( $this->wpml_active ) {
			$this->wpml_translation_info = sprintf(
				/* translators: %1$s: link opening tag, %2$s: link closing tag */
				__( 'You should translate this string in %1$sWPML - String Translation%2$s after saving the settings', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
				'<a href="admin.php?page=wpml-string-translation%2Fmenu%2Fstring-translation.php">',
				'</a>'
			);
		}
		$this->wc_deposits_active      = function_exists( 'wc_deposits_woocommerce_is_active' ) || function_exists( '\Webtomizer\WCDP\wc_deposits_woocommerce_is_active' );
		$this->wc_subscriptions_active = function_exists( 'wcs_get_subscription' );
		$this->wc_blocks_active        = class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' );
		$this->out_link_utm            = '?utm_source=' . rawurlencode( esc_url( home_url( '/' ) ) ) . '&amp;utm_medium=link&amp;utm_campaign=mb_ifthen_plugin';
		if ( version_compare( WC_VERSION, '7.1', '>=' ) ) {
			if ( wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ) {
				$this->hpos_enabled = true;
			}
		}
		// Multibanco
		$this->multibanco_settings         = get_option( 'woocommerce_multibanco_ifthen_for_woocommerce_settings', '' );
		$this->multibanco_notify_url       = (
			get_option( 'permalink_structure' ) === ''
			?
			home_url( '/?wc-api=WC_Multibanco_IfThen_Webdados&chave=[CHAVE_ANTI_PHISHING]&entidade=[ENTIDADE]&referencia=[REFERENCIA]&valor=[VALOR]&datahorapag=[DATA_HORA_PAGAMENTO]&terminal=[TERMINAL]' )
			:
			home_url( '/wc-api/WC_Multibanco_IfThen_Webdados/?chave=[CHAVE_ANTI_PHISHING]&entidade=[ENTIDADE]&referencia=[REFERENCIA]&valor=[VALOR]&datahorapag=[DATA_HORA_PAGAMENTO]&terminal=[TERMINAL]' )
		);
		$this->multibanco_api_mode_enabled = isset( $this->multibanco_settings['api_mode'] ) && $this->multibanco_settings['api_mode'] === 'yes';
		if ( $this->multibanco_api_mode_enabled && $this->multibanco_settings['mbkey'] === 'YAK-504589' ) {
			$this->multibanco_api_url = 'https://ifthenpay.com/api/multibanco/reference/sandbox'; // Sandbox
		}
		// MB WAY
		$this->mbway_settings   = get_option( 'woocommerce_mbway_ifthen_for_woocommerce_settings', '' );
		$this->mbway_notify_url = (
			get_option( 'permalink_structure' ) === ''
			?
			home_url( '/?wc-api=WC_MBWAY_IfThen_Webdados&chave=[CHAVE_ANTI_PHISHING]&referencia=[REFERENCIA]&idpedido=[ID_TRANSACAO]&valor=[VALOR]&datahorapag=[DATA_HORA_PAGAMENTO]&estado=[ESTADO]' )
			:
			home_url( '/wc-api/WC_MBWAY_IfThen_Webdados/?chave=[CHAVE_ANTI_PHISHING]&referencia=[REFERENCIA]&idpedido=[ID_TRANSACAO]&valor=[VALOR]&datahorapag=[DATA_HORA_PAGAMENTO]&estado=[ESTADO]' )
		);
		// Payshop
		$this->payshop_settings   = get_option( 'woocommerce_payshop_ifthen_for_woocommerce_settings', '' );
		$this->payshop_notify_url = (
			get_option( 'permalink_structure' ) === ''
			?
			home_url( '/?wc-api=WC_Payshop_IfThen_Webdados&chave=[CHAVE_ANTI_PHISHING]&id_cliente=[ID_CLIENTE]&id_transacao=[ID_TRANSACAO]&referencia=[REFERENCIA]&valor=[VALOR]&estado=[ESTADO]&datahorapag=[DATA_HORA_PAGAMENTO]' )
			:
			home_url( '/wc-api/WC_Payshop_IfThen_Webdados/?chave=[CHAVE_ANTI_PHISHING]&id_cliente=[ID_CLIENTE]&id_transacao=[ID_TRANSACAO]&referencia=[REFERENCIA]&valor=[VALOR]&estado=[ESTADO]&datahorapag=[DATA_HORA_PAGAMENTO]' )
		);
		// Credit card
		$this->creditcard_settings   = get_option( 'woocommerce_creditcard_ifthen_for_woocommerce_settings', '' );
		$this->creditcard_notify_url = (
			get_option( 'permalink_structure' ) === ''
			?
			home_url( '/?wc-api=WC_CreditCard_IfThen_Webdados' )
			:
			home_url( '/wc-api/WC_CreditCard_IfThen_Webdados/' )
		);
		// Cofidis Pay
		$this->cofidispay_settings   = get_option( 'woocommerce_cofidispay_ifthen_for_woocommerce_settings', '' );
		$this->cofidispay_notify_url = (
			get_option( 'permalink_structure' ) === ''
			?
			home_url( '/?wc-api=WC_CofidisPay_IfThen_Webdados&key=[ANTI_PHISHING_KEY]&orderId=[ORDER_ID]&amount=[AMOUNT]&requestId=[REQUEST_ID]' )
			:
			home_url( '/wc-api/WC_CofidisPay_IfThen_Webdados/?key=[ANTI_PHISHING_KEY]&orderId=[ORDER_ID]&amount=[AMOUNT]&requestId=[REQUEST_ID]' )
		);
		$this->cofidispay_return_url = (
			get_option( 'permalink_structure' ) === ''
			?
			home_url( '/?wc-api=WC_CofidisPayReturn_IfThen_Webdados' )
			:
			home_url( '/wc-api/WC_CofidisPayReturn_IfThen_Webdados/' )
		);
		// Hooks
		$this->init_hooks();
	}

	/* Ensures only one instance of our plugin is loaded or can be loaded */
	public static function instance( $version ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $version );
		}
		return self::$_instance;
	}

	/* Hooks */
	private function init_hooks() {
		add_filter( 'woocommerce_payment_gateways', array( $this, 'woocommerce_add_payment_gateways' ) );
		add_action( 'woocommerce_blocks_loaded', array( $this, 'woocommerce_add_payment_gateways_woocommerce_blocks' ) ); // WooCommerce Blocks
		add_action( 'add_meta_boxes', array( $this, 'multibanco_order_metabox' ) );
		add_filter( 'woocommerce_shop_order_search_fields', array( $this, 'shop_order_search' ) );
		add_filter( 'woocommerce_order_table_search_query_meta_keys', array( $this, 'shop_order_search' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'multibanco_woocommerce_checkout_update_order_meta' ) );    // Frontend
		add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( $this, 'multibanco_woocommerce_order_data_store_cpt_get_orders_query' ), 10, 3 );
		add_action( 'woocommerce_cancel_unpaid_orders', array( $this, 'multibanco_woocommerce_cancel_unpaid_orders' ), 99 );
		add_filter( 'apg_sms_message', array( $this, 'multibanco_apg_sms_message' ), 10, 2 );
		add_filter( 'apg_sms_message', array( $this, 'payshop_apg_sms_message' ), 10, 2 );
		add_filter( 'wcs_renewal_order_meta', array( $this, 'multibanco_wcs_filter_meta' ), 10, 3 );
		add_filter( 'wcs_resubscribe_order_meta', array( $this, 'multibanco_wcs_filter_meta' ), 10, 3 );
		add_filter( 'wcs_renewal_order_created', array( $this, 'multibanco_wcs_renewal_order_created' ), 11, 2 );
		add_action( 'plugins_loaded', array( $this, 'wpml_ajax_fix_locale' ) );
		add_action( 'woocommerce_new_customer_note', array( $this, 'woocommerce_new_customer_note_fix_wpml' ), 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'after_setup_theme', array( $this, 'set_images' ) );
		// Order status listener/Ajax hook
		add_action( 'wp_ajax_wc_mbway_ifthen_order_status', array( $this, 'mbway_ajax_order_status' ) );
		add_action( 'wp_ajax_wc_cofidispay_ifthenpay_order_status', array( $this, 'cofidispay_ajax_order_status' ) );
		// Request MB WAY payment again
		add_action( 'wp_ajax_mbway_ifthen_request_payment_again', array( $this, 'wp_ajax_mbway_ifthen_request_payment_again' ) );
		// Order value changed?
		add_action( 'woocommerce_order_item_add_action_buttons', array( $this, 'multibanco_maybe_value_changed' ) );
		// Admin notices to warn about old technology
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		// Order needs payment for all our methods
		add_action(
			'init',
			function() {
				$this->unpaid_statuses = apply_filters( 'ifthen_unpaid_statuses', $this->unpaid_statuses );
			}
		);
		add_filter( 'woocommerce_valid_order_statuses_for_payment', array( $this, 'woocommerce_valid_order_statuses_for_payment' ), PHP_INT_MAX, 2 );
		// Create cron
		if ( ! wp_next_scheduled( 'wc_ifthen_hourly_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'wc_ifthen_hourly_cron' );
		}
		// Cancel orders with expired references - Multibanco (after_setup_theme so it runs after theme's functions.php file)
		add_action(
			'after_setup_theme',
			function() {
				if (
					( $this->get_multibanco_ref_mode() === 'incremental_expire' )
					&&
					isset( $this->multibanco_settings['cancel_expired'] )
					&&
					( $this->multibanco_settings['cancel_expired'] === 'yes' )
				) {
					add_action( 'wc_ifthen_hourly_cron', array( $this, 'multibanco_cancel_expired_orders' ) );
				}
			}
		);
		// Identify pay form form existing orders
		add_action(
			'woocommerce_before_pay_action',
			function() {
				$this->is_pay_form = true;
			}
		);
		// Remove pay button for Multibanco, MB WAY or Payshop
		add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'woocommerce_my_account_my_orders_actions' ), 10, 2 );
		// REST API - Not needed: https://wordpress.org/support/topic/obter-referencia-multibanco-atraves-da-api/#post-15815758
		// add_filter( 'woocommerce_api_order_response', array( $this, 'woocommerce_api_order_response', 11, 2 );
		// Allow filtering of notify URLs
		add_action( 'plugins_loaded', array( $this, 'filter_notify_urls' ), 20 );
	}

	/* Get version */
	public function get_version() {
		return $this->version;
	}

	/* Set images */
	public function set_images() {
		$this->multibanco_banner_email = plugins_url( 'images/multibanco_banner.png', __FILE__ );
		$this->multibanco_banner       = plugins_url( 'images/multibanco_banner.svg', __FILE__ );
		$this->multibanco_icon         = plugins_url( 'images/multibanco_icon.svg', __FILE__ );

		$this->mbway_banner_email = plugins_url( 'images/mbway_banner.png', __FILE__ );
		$this->mbway_banner       = plugins_url( 'images/mbway_banner.svg', __FILE__ );
		$this->mbway_icon         = plugins_url( 'images/mbway_icon.svg', __FILE__ );

		$this->payshop_banner_email = plugins_url( 'images/payshop_banner.png', __FILE__ );
		$this->payshop_banner       = plugins_url( 'images/payshop_banner.svg', __FILE__ );
		$this->payshop_icon         = plugins_url( 'images/payshop_icon.svg', __FILE__ );

		$this->creditcard_banner_email = plugins_url( 'images/creditcard_banner_and_icon.png', __FILE__ );
		$this->creditcard_banner       = plugins_url( 'images/creditcard_banner_and_icon.svg', __FILE__ );
		$this->creditcard_icon         = plugins_url( 'images/creditcard_banner_and_icon.svg', __FILE__ );

		$this->cofidispay_banner_email = plugins_url( 'images/cofidispay_banner.png', __FILE__ );
		$this->cofidispay_banner       = plugins_url( 'images/cofidispay_banner.svg', __FILE__ );
		$this->cofidispay_icon         = plugins_url( 'images/cofidispay_icon.svg', __FILE__ );
	}

	/* Add settings link to plugin actions */
	public function add_settings_link( $links ) {
		$action_links = array();
		if ( ! $this->pro_add_on_active ) {
			$action_links['gopro'] = '<a href="https://ptwooplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/' . esc_attr( $this->out_link_utm ) . '" target="_blank" style="font-weight: bold;">' . __( 'Get the PRO add-on', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</a>';
		}
		$settings_links           = __( 'Settings:', 'multibanco-ifthen-software-gateway-for-woocommerce' );
		$settings_links          .= ' <a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=' . $this->multibanco_id . '">Multibanco</a>';
		$settings_links          .= ' - <a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=' . $this->mbway_id . '">MB WAY</a>';
		$settings_links          .= ' - <a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=' . $this->creditcard_id . '">' . __( 'Credit card', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</a>';
		$settings_links          .= ' - <a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=' . $this->payshop_id . '">Payshop</a>';
		$settings_links          .= ' - <a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=' . $this->cofidispay_id . '">Cofidis Pay</a>';
		$action_links['settings'] = $settings_links;
		$action_links['support']  = '<a href="https://wordpress.org/support/plugin/multibanco-ifthen-software-gateway-for-woocommerce/" target="_blank">' . __( 'Technical support', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</a>';

		return array_merge( $action_links, $links );
	}

	/* Add to WooCommerce */
	public function woocommerce_add_payment_gateways( $methods ) {
		$our_gateways = array(
			'WC_Multibanco_IfThen_Webdados',
			'WC_MBWAY_IfThen_Webdados',
			'WC_CreditCard_IfThen_Webdados',
			'WC_Payshop_IfThen_Webdados',
			'WC_CofidisPay_IfThen_Webdados',
		);
		// Avoid loading gateways more than once
		foreach( $our_gateways as $our_gateway ) {
			if ( ! in_array( $our_gateway, $methods ) ) {
				$methods[] = $our_gateway;
			}
		}
		// Avoid loading gateways more than once - Breaks plugins that call new \WC_Payment_Gateways()
		/*if ( ! $this->gateways_loaded ) {
			$this->gateways_loaded = true;
			foreach( $our_gateways as $our_gateway ) {
				$methods[] = $our_gateway;
			}
		}*/
		return $methods;
	}

	/* Add to WooCommerce Blocks */
	public function woocommerce_add_payment_gateways_woocommerce_blocks() {
		if ( WC_IfthenPay_Webdados()->wc_blocks_active ) {
			// Multibanco
			require_once 'woocommerce-blocks/MultibancoIfthenPay.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new \Automattic\WooCommerce\Blocks\Payments\Integrations\MultibancoIfthenPay() );
				}
			);
			// MB WAY
			require_once 'woocommerce-blocks/MBWayIfthenPay.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new \Automattic\WooCommerce\Blocks\Payments\Integrations\MBWayIfthenPay() );
				}
			);
			// Credit card
			require_once 'woocommerce-blocks/CreditCardIfthenPay.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new \Automattic\WooCommerce\Blocks\Payments\Integrations\CreditCardIfthenPay() );
				}
			);
			// Payshop
			require_once 'woocommerce-blocks/PayshopIfthenPay.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new \Automattic\WooCommerce\Blocks\Payments\Integrations\PayshopIfthenPay() );
				}
			);
			// Cofidis Pay
			require_once 'woocommerce-blocks/CofidisPayIfthenPay.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new \Automattic\WooCommerce\Blocks\Payments\Integrations\CofidisPayIfthenPay() );
				}
			);
		}
	}

	/* Debug / Log */
	public function debug_log( $gateway_id, $message, $level = 'debug', $debug_email = '', $email_message = '' ) {
		if ( ! $this->log ) {
			$this->log = wc_get_logger(); // Init log
		}
		$this->log->$level( $message, array( 'source' => $gateway_id ) );
		if ( $debug_email ) {
			if ( empty( $email_message ) ) {
				$email_message = $message;
			}
			wp_mail(
				trim( $debug_email ),
				$gateway_id . ' - ' . $message,
				$email_message
			);
		}
	}

	/* Debug / Log - Extra */
	public function debug_log_extra( $gateway_id, $message, $level = 'debug', $debug_email = '', $email_message = '' ) {
		if ( apply_filters( 'ifthen_debug_log_extra', false ) ) {
			$url = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : 'Unknown request URI';
			$this->debug_log( $gateway_id, 'EXTRA (' . $url . ') - ' . $message, $level, $debug_email, $email_message );
		}
	}

	/* Get Multibanco reference mode */
	public function get_multibanco_ref_mode() {
		return apply_filters( 'multibanco_ifthen_ref_mode', $this->multibanco_ref_mode );
	}

	/* Get Multibanco reference seed */
	public function get_multibanco_ref_seed( $first = true ) {
		if ( $this->get_multibanco_ref_mode() === 'incremental_expire' ) {
			return $this->get_multibanco_incremental_expire_next_seed( $first );
		}
		return wp_rand( 0, 9999 );
	}

	/* Get Multibanco incremental reference seed */
	public function get_multibanco_incremental_expire_next_seed( $first ) {
		if ( is_null( $this->multibanco_last_incremental_expire_ref ) ) {
			$multibanco_last_incremental_expire_ref = intval( get_option( 'multibanco_last_incremental_expire_ref', 0 ) );
			if ( intval( $multibanco_last_incremental_expire_ref ) > 0 && intval( $multibanco_last_incremental_expire_ref ) < 9999 ) {
				$this->multibanco_last_incremental_expire_ref = intval( $multibanco_last_incremental_expire_ref );
			} else {
				// Start again
				$this->multibanco_last_incremental_expire_ref = 0;
			}
		}
		if ( ! $first ) {
			$this->multibanco_last_incremental_expire_ref++;
		}
		return intval( $this->multibanco_last_incremental_expire_ref ) + 1;
	}

	/* Format MB reference */
	public function format_multibanco_ref( $ref ) {
		return apply_filters( 'multibanco_ifthen_format_ref', trim( chunk_split( trim( $ref ), 3, '&nbsp;' ) ) );
	}

	/* Format Payshop reference */
	public function format_payshop_ref( $ref ) {
		return apply_filters( 'payshop_ifthen_format_ref', trim( chunk_split( trim( $ref ), 3, '&nbsp;' ) ) );
	}

	/* Disable payment gateway if not € */
	public function disable_if_currency_not_euro( $available_gateways, $gateway_id ) {
		if ( isset( $available_gateways[ $gateway_id ] ) ) {
			if ( trim( get_woocommerce_currency() ) !== 'EUR' ) {
				unset( $available_gateways[ $gateway_id ] );
			}
		}
		return $available_gateways;
	}

	/* Customer billing country */
	public function get_customer_billing_country() {
		return trim( WC()->customer->get_billing_country() );
	}

	/* Customer shipping country */
	public function get_customer_shipping_country() {
		return trim( WC()->customer->get_shipping_country() );
	}

	/* Disable unless Portugal */
	public function disable_unless_portugal( $available_gateways, $gateway_id ) {
		if ( isset( $available_gateways[ $gateway_id ] ) ) {
			if (
				$available_gateways[ $gateway_id ]->only_portugal
				&&
				WC()->customer
				&&
				$this->get_customer_billing_country() !== 'PT'
				&&
				$this->get_customer_shipping_country() !== 'PT'
			) {
				unset( $available_gateways[ $gateway_id ] );
			}
		}
		return $available_gateways;
	}

	/* Just above/below certain amounts */
	public function disable_only_above_or_below( $available_gateways, $gateway_id, $default_only_above = null, $default_only_below = null ) {
		$value_to_pay = null;
		// Order total or cart total?
		$pay_slug = get_option( 'woocommerce_checkout_pay_endpoint', 'order-pay' );
		$order_id = absint( get_query_var( $pay_slug ) );
		if ( $order_id > 0 ) {
			// Pay screen on My Account
			$order        = wc_get_order( $order_id );
			$value_to_pay = $this->get_order_total_to_pay( $order );
		} else {
			// Checkout?
			if ( ! is_null( WC()->cart ) ) {
				$value_to_pay = WC()->cart->total; // We're not checking if we're paying just a deposit...
			} else { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedElse
				// No cart? Where are we? We shouldn't unset our payment gateway
			}
		}

		if ( isset( $available_gateways[ $gateway_id ] ) && ! is_null( $value_to_pay ) ) {
			// Only above
			$only_above = $default_only_above ? $default_only_above : 0;
			if ( isset( $available_gateways[ $gateway_id ]->only_above ) ) {
				if (
					floatval( $available_gateways[ $gateway_id ]->only_above ) > 0
					&&
					floatval( $available_gateways[ $gateway_id ]->only_above ) > $only_above
				) {
					$only_above = floatval( $available_gateways[ $gateway_id ]->only_above );
				}
			}
			if ( $only_above > 0 && $value_to_pay < floatval( $only_above ) ) {
				unset( $available_gateways[ $gateway_id ] );
			}
			// Only below
			$only_below = $default_only_below ? $default_only_below : 0;
			if ( isset( $available_gateways[ $gateway_id ]->only_below ) ) {
				if (
					floatval( $available_gateways[ $gateway_id ]->only_below ) > 0
					&&
					floatval( $available_gateways[ $gateway_id ]->only_below ) < $only_below
				) {
					$only_below = floatval( $available_gateways[ $gateway_id ]->only_below );
				}
			}
			if ( $only_below > 0 && $value_to_pay > floatval( $only_below ) ) {
				unset( $available_gateways[ $gateway_id ] );
			}
		}
		return $available_gateways;
	}

	/* Get Multibanco order details */
	public function get_multibanco_order_details( $order_id ) {
		$order      = wc_get_order( $order_id );
		$mbkey      = $order->get_meta( '_' . $this->multibanco_id . '_mbkey' );
		$request_id = $order->get_meta( '_' . $this->multibanco_id . '_RequestId' );
		$ent        = $order->get_meta( '_' . $this->multibanco_id . '_ent' );
		$ref        = $order->get_meta( '_' . $this->multibanco_id . '_ref' );
		$val        = $order->get_meta( '_' . $this->multibanco_id . '_val' );
		$exp        = $order->get_meta( '_' . $this->multibanco_id . '_exp' );
		if ( ! empty( $ent ) && ! empty( $ref ) && ! empty( $val ) ) {
			return array(
				'mbkey'     => $mbkey,
				'RequestId' => $request_id,
				'ent'       => $ent,
				'ref'       => $ref,
				'val'       => $val,
				'exp'       => $exp,
			);
		}
		return false;
	}

	/* Get MB WAY order details */
	public function get_mbway_order_details( $order_id ) {
		$order     = wc_get_order( $order_id );
		$mbwaykey  = $order->get_meta( '_' . $this->mbway_id . '_mbwaykey' );
		$id_pedido = $order->get_meta( '_' . $this->mbway_id . '_id_pedido' );
		$phone     = $order->get_meta( '_' . $this->mbway_id . '_phone' );
		$val       = $order->get_meta( '_' . $this->mbway_id . '_val' );
		$time      = $order->get_meta( '_' . $this->mbway_id . '_time' );
		$exp       = $order->get_meta( '_' . $this->mbway_id . '_exp' );
		if ( ! empty( $mbwaykey ) && ! empty( $id_pedido ) && ! empty( $val ) ) {
			return array(
				'mbwaykey'  => $mbwaykey,
				'id_pedido' => $id_pedido,
				'phone'     => $phone,
				'val'       => $val,
				'time'      => $time,
				'exp'       => $exp,
			);
		}
		return false;
	}

	/* Get Payshop order details */
	public function get_payshop_order_details( $order_id ) {
		$order      = wc_get_order( $order_id );
		$payshopkey = $order->get_meta( '_' . $this->payshop_id . '_payshopkey' );
		$ref        = $order->get_meta( '_' . $this->payshop_id . '_ref' );
		$request_id = $order->get_meta( '_' . $this->payshop_id . '_request_id' );
		$id         = $order->get_meta( '_' . $this->payshop_id . '_id' );
		$val        = $order->get_meta( '_' . $this->payshop_id . '_val' );
		$time       = $order->get_meta( '_' . $this->payshop_id . '_time' );
		$exp        = $order->get_meta( '_' . $this->payshop_id . '_exp' );
		if ( ! empty( $payshopkey ) && ! empty( $ref ) && ! empty( $request_id ) && ! empty( $val ) ) {
			return array(
				'payshopkey' => $payshopkey,
				'ref'        => $ref,
				'request_id' => $request_id,
				'id'         => $id,
				'val'        => $val,
				'time'       => $time,
				'exp'        => $exp,
			);
		}
		return false;
	}

	/* Get Credit card order details */
	public function get_creditcard_order_details( $order_id ) {
		$order         = wc_get_order( $order_id );
		$creditcardkey = $order->get_meta( '_' . $this->creditcard_id . '_creditcardkey' );
		$id            = $order->get_meta( '_' . $this->creditcard_id . '_id' );
		$request_id    = $order->get_meta( '_' . $this->creditcard_id . '_request_id' );
		$val           = $order->get_meta( '_' . $this->creditcard_id . '_val' );
		$time          = $order->get_meta( '_' . $this->creditcard_id . '_time' );
		$payment_url   = $order->get_meta( '_' . $this->creditcard_id . '_payment_url' );
		$wd_secret     = $order->get_meta( '_' . $this->creditcard_id . '_wd_secret' );
		if ( ! empty( $creditcardkey ) && ! empty( $id ) && ! empty( $request_id ) && ! empty( $val ) ) {
			return array(
				'creditcardkey' => $creditcardkey,
				'request_id'    => $request_id,
				'id'            => $id,
				'val'           => $val,
				'time'          => $time,
				'payment_url'   => $payment_url,
				'wd_secret'     => $wd_secret,
			);
		}
		return false;
	}

	/* Get Cofidis Pay order details */
	public function get_cofidispay_order_details( $order_id ) {
		$order         = wc_get_order( $order_id );
		$cofidispaykey = $order->get_meta( '_' . $this->cofidispay_id . '_cofidispaykey' );
		$id            = $order->get_meta( '_' . $this->cofidispay_id . '_id' );
		$request_id    = $order->get_meta( '_' . $this->cofidispay_id . '_request_id' );
		$val           = $order->get_meta( '_' . $this->cofidispay_id . '_val' );
		$time          = $order->get_meta( '_' . $this->cofidispay_id . '_time' );
		$payment_url   = $order->get_meta( '_' . $this->cofidispay_id . '_payment_url' );
		$wd_secret     = $order->get_meta( '_' . $this->cofidispay_id . '_wd_secret' );
		if ( ! empty( $cofidispaykey ) && ! empty( $id ) && ! empty( $request_id ) && ! empty( $val ) ) {
			return array(
				'cofidispaykey' => $cofidispaykey,
				'request_id'    => $request_id,
				'id'            => $id,
				'val'           => $val,
				'time'          => $time,
				'payment_url'   => $payment_url,
				'wd_secret'     => $wd_secret,
			);
		}
		return false;
	}

	/* Order metabox registration to show IfthenPay payment details - HPOS compatible */
	public function multibanco_order_metabox() {
		$screen = $this->hpos_enabled ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		add_meta_box(
			$this->multibanco_id,
			'IfthenPay',
			array( $this, 'multibanco_order_metabox_html' ),
			$screen,
			'side',
			'core'
		);
		// WooCommerce Deposits newer versions - Not HPOS tested because Deposits is not yet HPOS compatible (4.1.15)
		if ( $this->wc_deposits_active ) {
			add_meta_box(
				$this->multibanco_id,
				'IfthenPay',
				array( $this, 'multibanco_order_metabox_html' ),
				'wcdp_payment',
				'side',
				'core'
			);
		}
	}

	/* Order metabox HTML output to show IfthenPay payment details - HPOS compatible */
	public function multibanco_order_metabox_html( $post_or_order_object ) {
		$order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
		if ( $date_paid = $order->get_date_paid() ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
			$date_paid = sprintf(
				'%1$s %2$s',
				wc_format_datetime( $date_paid, 'Y-m-d' ),
				wc_format_datetime( $date_paid, 'H:i' )
			);
		}
		switch ( $order->get_payment_method() ) {
			// Multibanco
			case $this->multibanco_id:
				if ( $order_mb_details = $this->get_multibanco_order_details( $order->get_id() ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
					echo '<p><img src="' . esc_url( $this->multibanco_banner ) . '" style="display: block; margin: auto; max-width: auto; max-height: 48px;" alt="Multibanco" title="Multibanco"/></p>';
					echo '<p>' . __( 'Entity', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . trim( $order_mb_details['ent'] ) . '<br/>';
					echo __( 'Reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . $this->format_multibanco_ref( $order_mb_details['ref'] ) . '<br/>';
					echo __( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . wc_price( $order_mb_details['val'], array( 'currency' => $order->get_currency() ) ) . '</p>';
					if ( $this->order_needs_payment( $order ) ) {
						if ( trim( $order_mb_details['exp'] ) != '' ) {
							echo '<p>' . __( 'Expiration', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . $this->multibanco_format_expiration( $order_mb_details['exp'], $order->get_id() ) . '</p>';
						}
						$show_debug = true;
						if ( $this->wc_deposits_active && ( $order->get_status() == 'partially-paid' || ( $order->get_status() == 'on-hold' && $order->get_meta( '_wc_deposits_deposit_paid' ) == 'yes' ) ) ) {
							echo '<p><strong>' . __( 'Partially paid.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							if ( $order->get_meta( '_wc_deposits_second_payment_paid' ) != 'yes' && floatval( $order->get_meta( '_wc_deposits_second_payment' ) ) == floatval( $order_mb_details['val'] ) ) {
								echo '<p><strong>' . __( 'Awaiting second Multibanco payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							} else {
								$show_debug = false;
							}
						} else {
							echo '<p><strong>' . __( 'Awaiting Multibanco payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
						}
						if ( $show_debug && WP_DEBUG ) {
							$callback_url = $this->multibanco_notify_url;
							$callback_url = str_replace( '[CHAVE_ANTI_PHISHING]', $this->multibanco_settings['secret_key'], $callback_url );
							$callback_url = str_replace( '[ENTIDADE]', trim( $order_mb_details['ent'] ), $callback_url );
							$callback_url = str_replace( '[REFERENCIA]', trim( $order_mb_details['ref'] ), $callback_url );
							$callback_url = str_replace( '[VALOR]', $order_mb_details['val'], $callback_url );
							$callback_url = str_replace( '[DATA_HORA_PAGAMENTO]', '', $callback_url );
							$callback_url = str_replace( '[TERMINAL]', 'Testing', $callback_url );
							?>
							<hr/>
							<p>
								<?php _e( 'Callback URL', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:<br/>
								<textarea readonly type="text" class="input-text" cols="20" rows="5" style="width: 100%; height: 50%; font-size: 10px;"><?php echo $callback_url; ?></textarea>
							</p>
							<script type="text/javascript">
							jQuery( document ).ready( function() {
								jQuery( '#multibanco_ifthen_for_woocommerce_simulate_callback' ).on( 'click', function() {
									if ( confirm( '<?php _e( 'This is a testing tool and will set the order as paid. Are you sure you want to proceed?', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' ) ) {
										jQuery.get( '<?php echo $callback_url; ?>', '', function( response ) {
											alert( '<?php _e( 'This page will now reload. If the order is not set as paid and processing (or completed, if it only contains virtual and downloadable products) please check the debug logs.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
											window.location.reload();
										}).fail( function() {
											alert( '<?php _e( 'Error: Could not set the order as paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
										});
									}
								});
							});
							</script>
							<p align="center">
								<input type="button" class="button" id="multibanco_ifthen_for_woocommerce_simulate_callback" value="<?php echo esc_attr( __( 'Simulate callback payment', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ); ?>"/>
							</p>
							<?php
						}
					} else {
						// PAID?
						if ( $date_paid ) {
							echo '<p><strong>' . __( 'Paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . $date_paid . '</strong></p>';
						}
					}
				} else {
					echo '<p>' . __( 'No details available', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '.</p><p>' . sprintf(
						__( 'This must be an error because the payment method of this order is %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'Multibanco'
					) . '.</p>';
				}
				break;
			// MB WAY
			case $this->mbway_id:
				if ( $order_mbway_details = $this->get_mbway_order_details( $order->get_id() ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
					echo '<p><img src="' . esc_url( $this->mbway_banner ) . '" style="display: block; margin: auto; max-width: auto; max-height: 48px;" alt="MB WAY" title="MB WAY"/></p>';
					echo '<p>' . __( 'MB WAY Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . trim( $order_mbway_details['mbwaykey'] ) . '<br/>';
					echo __( 'Request ID', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . trim( $order_mbway_details['id_pedido'] ) . '<br/>';
					echo __( 'Phone', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . trim( $order->get_meta( '_' . $this->mbway_id . '_phone' ) ) . '<br/>';
					echo __( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . wc_price( $order_mbway_details['val'], array( 'currency' => $order->get_currency() ) ) . '</p>';
					if ( $this->order_needs_payment( $order ) ) {
						if ( trim( $order_mbway_details['exp'] ) != '' ) {
							echo '<p>' . __( 'Expiration', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . $this->mbway_format_expiration( $order_mbway_details['exp'], $order->get_id() ) . '</p>';
						}
						$show_debug = true;
						if ( $this->wc_deposits_active && $order->get_status() == 'partially-paid' ) {
							echo '<p><strong>' . __( 'Partially paid.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							if ( $order->get_meta( '_wc_deposits_second_payment_paid' ) != 'yes' && floatval( $order->get_meta( '_wc_deposits_second_payment' ) ) == floatval( $order_mbway_details['val'] ) ) {
								echo '<p><strong>' . __( 'Awaiting second MB WAY payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							} else {
								$show_debug = false;
							}
						} else {
							echo '<p><strong>' . __( 'Awaiting MB WAY payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							if ( date_i18n( 'Y-m-d H:i:s', strtotime( '-' . intval( $this->mbway_minutes * $this->mbway_multiplier_new_payment * 60 ) . ' SECONDS', current_time( 'timestamp' ) ) ) > $order_mbway_details['time'] ) {
								?>
									<p align="center">
										<input type="button" class="button" id="mbway_ifthen_request_payment_again" value="<?php echo esc_attr( __( 'Request MB WAY payment again', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ); ?>"/>
									</p>
									<script type="text/javascript">
									jQuery( document ).ready( function() {
										jQuery( '#mbway_ifthen_request_payment_again' ).on( 'click', function() {
											if ( confirm( '<?php echo __( 'Are you sure you want to request the payment again? Don’t do it unless your client asks you to.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' ) ) {
												jQuery( '#mbway_ifthen_request_payment_again' ).val( '<?php _e( 'Wait...', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
												var phone = '<?php echo $order->get_meta( '_' . $this->mbway_id . '_phone' ); ?>';
												phone = prompt( '<?php echo __( 'MB WAY phone number', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>', phone );
												if ( phone.length == 9 ) {
													var data = {
														'action'  : 'mbway_ifthen_request_payment_again',
														'order_id': <?php echo $order->get_id(); ?>,
														'nonce'   : '<?php echo wp_create_nonce( 'mbway_ifthen_request_payment_again' ); ?>',
														'phone'   : phone
													};
													jQuery.post( ajaxurl, data, function( response ) {
														if ( response.status == 1 ) {
															jQuery( '#mbway_ifthen_request_payment_again' ).val( '<?php _e( 'Done!', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
															alert( response.message );
															window.location.reload();
														} else {
															jQuery( '#mbway_ifthen_request_payment_again' ).val( '<?php _e( 'Request MB WAY payment again', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
															alert( response.error );
														}
													}, 'json' ).fail( function() {
														jQuery( '#mbway_ifthen_request_payment_again' ).val( '<?php _e( 'Request MB WAY payment again', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
														alert( '<?php _e( 'Unknown error.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
													} );
												} else {
													alert( '<?php _e( 'Invalid phone number', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
												}
											}
										});
									});
									</script>
									<?php
							}
						}
						if ( $show_debug && WP_DEBUG ) {
							$callback_url = $this->mbway_notify_url;
							$callback_url = str_replace( '[CHAVE_ANTI_PHISHING]', $this->mbway_settings['secret_key'], $callback_url );
							$callback_url = str_replace( '[REFERENCIA]', apply_filters( 'ifthen_webservice_send_order_number_instead_id', false ) ? $order->get_order_number() : $order->get_id(), $callback_url );
							$callback_url = str_replace( '[ID_TRANSACAO]', trim( $order_mbway_details['id_pedido'] ), $callback_url );
							$callback_url = str_replace( '[VALOR]', $order_mbway_details['val'], $callback_url );
							$callback_url = str_replace( '[DATA_HORA_PAGAMENTO]', '', $callback_url );
							$callback_url = str_replace( '[ESTADO]', 'PAGO', $callback_url );
							?>
								<hr/>
								<p>
								<?php _e( 'Callback URL', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:<br/>
									<textarea readonly type="text" class="input-text" cols="20" rows="5" style="width: 100%; height: 50%; font-size: 10px;"><?php echo $callback_url; ?></textarea>
								</p>
								<script type="text/javascript">
								jQuery( document ).ready( function() {
									jQuery( '#multibanco_ifthen_for_woocommerce_simulate_callback' ).on( 'click', function() {
										if ( confirm( '<?php _e( 'This is a testing tool and will set the order as paid. Are you sure you want to proceed?', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' ) ) {
											jQuery.get( '<?php echo $callback_url; ?>', '', function( response ) {
												alert( '<?php _e( 'This page will now reload. If the order is not set as paid and processing (or completed, if it only contains virtual and downloadable products) please check the debug logs.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
												window.location.reload();
											}).fail( function() {
												alert( '<?php _e( 'Error: Could not set the order as paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
											});
										}
									});
								});
								</script>
								<p align="center">
									<input type="button" class="button" id="multibanco_ifthen_for_woocommerce_simulate_callback" value="<?php echo esc_attr( __( 'Simulate callback payment', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ); ?>"/>
								</p>
								<?php
						}
					} else {
						// PAID?
						if ( $date_paid ) {
							echo '<p><strong>' . __( 'Paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . $date_paid . '</strong></p>';
						}
					}
				} else {
					echo '<p>' . __( 'No details available', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '.</p><p>' . sprintf(
						__( 'This must be an error because the payment method of this order is %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'MB WAY'
					) . '.</p>';
				}
				break;
			// Payshop
			case $this->payshop_id:
				if ( $order_mb_details = $this->get_payshop_order_details( $order->get_id() ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
					echo '<p><img src="' . esc_url( $this->payshop_banner ) . '" style="display: block; margin: auto; max-width: auto; max-height: 48px;" alt="Payshop" title="Payshop"/></p>';
					echo '<p>' . __( 'Reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . $this->format_payshop_ref( $order_mb_details['ref'] ) . '<br/>';
					echo __( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . wc_price( $order_mb_details['val'], array( 'currency' => $order->get_currency() ) ) . '</p>';
					if ( $this->order_needs_payment( $order ) ) {
						if ( trim( $order_mb_details['exp'] ) != '' ) {
							echo '<p>' . __( 'Expiration', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . $this->payshop_format_expiration( $order_mb_details['exp'], $order->get_id() ) . '</p>';
						}
						$show_debug = true;
						if ( $this->wc_deposits_active && ( $order->get_status() == 'partially-paid' || ( $order->get_status() == 'on-hold' && $order->get_meta( '_wc_deposits_deposit_paid' ) == 'yes' ) ) ) {
							echo '<p><strong>' . __( 'Partially paid.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							if ( $order->get_meta( '_wc_deposits_second_payment_paid' ) != 'yes' && floatval( $order->get_meta( '_wc_deposits_second_payment' ) ) == floatval( $order_mb_details['val'] ) ) {
								echo '<p><strong>' . __( 'Awaiting second Payshop payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							} else {
								$show_debug = false;
							}
						} else {
							echo '<p><strong>' . __( 'Awaiting Payshop payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
						}
						if ( $show_debug && WP_DEBUG ) {
							$callback_url = $this->payshop_notify_url;
							$callback_url = str_replace( '[CHAVE_ANTI_PHISHING]', $this->payshop_settings['secret_key'], $callback_url );
							$callback_url = str_replace( '[ID_CLIENTE]', trim( $order_mb_details['id'] ), $callback_url );
							$callback_url = str_replace( '[ID_TRANSACAO]', trim( $order_mb_details['request_id'] ), $callback_url );
							$callback_url = str_replace( '[REFERENCIA]', trim( $order_mb_details['ref'] ), $callback_url );
							$callback_url = str_replace( '[VALOR]', $order_mb_details['val'], $callback_url );
							$callback_url = str_replace( '[ESTADO]', 'PAGO', $callback_url );
							$callback_url = str_replace( '[DATA_HORA_PAGAMENTO]', '', $callback_url );
							?>
							<hr/>
							<p>
								<?php _e( 'Callback URL', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:<br/>
								<textarea readonly type="text" class="input-text" cols="20" rows="5" style="width: 100%; height: 50%; font-size: 10px;"><?php echo $callback_url; ?></textarea>
							</p>
							<script type="text/javascript">
							jQuery( document ).ready( function() {
								jQuery( '#multibanco_ifthen_for_woocommerce_simulate_callback' ).on( 'click', function() {
									if ( confirm( '<?php _e( 'This is a testing tool and will set the order as paid. Are you sure you want to proceed?', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' ) ) {
										jQuery.get( '<?php echo $callback_url; ?>', '', function( response ) {
											alert( '<?php _e( 'This page will now reload. If the order is not set as paid and processing (or completed, if it only contains virtual and downloadable products) please check the debug logs.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
											window.location.reload();
										}).fail( function() {
											alert( '<?php _e( 'Error: Could not set the order as paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
										});
									}
								});
							});
							</script>
							<p align="center">
								<input type="button" class="button" id="multibanco_ifthen_for_woocommerce_simulate_callback" value="<?php echo esc_attr( __( 'Simulate callback payment', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ); ?>"/>
							</p>
							<?php
						}
					} else {
						// PAID?
						if ( $date_paid ) {
							echo '<p><strong>' . __( 'Paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . $date_paid . '</strong></p>';
						}
					}
				} else {
					echo '<p>' . __( 'No details available', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '.</p><p>' . sprintf(
						__( 'This must be an error because the payment method of this order is %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'Payshop'
					) . '.</p>';
				}
				break;
			// Credit card
			case $this->creditcard_id:
				if ( $order_mb_details = $this->get_creditcard_order_details( $order->get_id() ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
					echo '<p><img src="' . esc_url( $this->creditcard_banner ) . '" style="display: block; margin: auto; max-width: auto; max-height: 48px;" alt="Credit or debit card" title="Credit or debit card"/></p>';
					echo '<p>' . __( 'Credit card Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . trim( $order_mb_details['creditcardkey'] ) . '<br/>';
					echo __( 'Request ID', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . trim( $order_mb_details['request_id'] ) . '<br/>';
					echo __( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . wc_price( $order_mb_details['val'], array( 'currency' => $order->get_currency() ) ) . '</p>';
					if ( $this->order_needs_payment( $order ) ) {
						$show_debug = true;
						if ( $this->wc_deposits_active && $order->get_status() == 'partially-paid' ) {
							echo '<p><strong>' . __( 'Partially paid.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							if ( $order->get_meta( '_wc_deposits_second_payment_paid' ) != 'yes' && floatval( $order->get_meta( '_wc_deposits_second_payment' ) ) == floatval( $order_mb_details['val'] ) ) {
								echo '<p><strong>' . __( 'Awaiting second Credit or debit card payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							} else {
								$show_debug = false;
							}
						} else {
							echo '<p><strong>' . __( 'Awaiting Credit or debit card payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
						}
						/*
						echo sprintf(
							'<p>%1$s: %2$s</p>',
							__( 'Payment link', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							sprintf(
								'<a href="%1$s" target="_blank">%2$s</a>',
								esc_attr( $order_mb_details['payment_url'] ),
								__( 'Open', 'multibanco-ifthen-software-gateway-for-woocommerce' )
							)
						);*/ // Returns duplicate payment, maybe because it expires on the first hit
						if ( $show_debug && WP_DEBUG ) {
							$val          = number_format( $order_mb_details['val'], 2, '.', '' );
							$callback_url = add_query_arg( 'status', 'success', $this->creditcard_notify_url );
							$callback_url = add_query_arg( 'wd_secret', $order_mb_details['wd_secret'], $callback_url );
							$callback_url = add_query_arg( 'id', $order_mb_details['id'], $callback_url );
							$callback_url = add_query_arg( 'amount', $val, $callback_url );
							$callback_url = add_query_arg( 'requestId', $order_mb_details['request_id'], $callback_url );
							$callback_url = add_query_arg( 'sk', hash_hmac( 'sha256', $order_mb_details['id'] . $val . $order_mb_details['request_id'], $order_mb_details['creditcardkey'] ), $callback_url );
							?>
							<hr/>
							<p>
								<?php _e( 'Callback URL', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:<br/>
								<textarea readonly type="text" class="input-text" cols="20" rows="5" style="width: 100%; height: 50%; font-size: 10px;"><?php echo $callback_url; ?></textarea>
							</p>
							<script type="text/javascript">
							jQuery( document ).ready( function() {
								jQuery( '#multibanco_ifthen_for_woocommerce_simulate_callback' ).on( 'click', function() {
									if ( confirm( '<?php _e( 'This is a testing tool and will set the order as paid. Are you sure you want to proceed?', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' ) ) {
										jQuery.get( '<?php echo $callback_url; ?>', '', function( response ) {
											alert( '<?php _e( 'This page will now reload. If the order is not set as paid and processing (or completed, if it only contains virtual and downloadable products) please check the debug logs.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
											window.location.reload();
										}).fail( function() {
											alert( '<?php _e( 'Error: Could not set the order as paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
										});
									}
								});
							});
							</script>
							<p align="center">
								<input type="button" class="button" id="multibanco_ifthen_for_woocommerce_simulate_callback" value="<?php echo esc_attr( __( 'Simulate callback payment', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ); ?>"/>
							</p>
							<?php
						}
					} else {
						// PAID?
						if ( $date_paid ) {
							echo '<p><strong>' . __( 'Paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . $date_paid . '</strong></p>';
						}
					}
				} else {
					echo '<p>' . __( 'No details available', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '.</p><p>' . sprintf(
						__( 'This must be an error because the payment method of this order is %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'Credit card'
					) . '.</p>';

				}
				break;
			// Cofidis Pay
			case $this->cofidispay_id:
				if ( $order_mb_details = $this->get_cofidispay_order_details( $order->get_id() ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
					echo '<p><img src="' . esc_url( $this->cofidispay_banner ) . '" style="display: block; margin: auto; max-width: auto; max-height: 48px;" alt="Cofidis Pay" title="Cofidis Pay"/></p>';
					echo '<p>' . __( 'Cofidis Pay Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . trim( $order_mb_details['cofidispaykey'] ) . '<br/>';
					echo __( 'Request ID', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . trim( $order_mb_details['request_id'] ) . '<br/>';
					echo __( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . wc_price( $order_mb_details['val'], array( 'currency' => $order->get_currency() ) ) . '</p>';
					if ( $this->order_needs_payment( $order ) ) {
						$show_debug = true;
						if ( $this->wc_deposits_active && $order->get_status() == 'partially-paid' ) {
							echo '<p><strong>' . __( 'Partially paid.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							if ( $order->get_meta( '_wc_deposits_second_payment_paid' ) != 'yes' && floatval( $order->get_meta( '_wc_deposits_second_payment' ) ) == floatval( $order_mb_details['val'] ) ) {
								echo '<p><strong>' . __( 'Awaiting second Cofidis Pay payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							} else {
								$show_debug = false;
							}
						} else {
							echo '<p><strong>' . __( 'Awaiting Cofidis Pay approval.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
						}
						/*
						echo sprintf(
							'<p>%1$s: %2$s</p>',
							__( 'Payment link', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							sprintf(
								'<a href="%1$s" target="_blank">%2$s</a>',
								esc_attr( $order_mb_details['payment_url'] ),
								__( 'Open', 'multibanco-ifthen-software-gateway-for-woocommerce' )
							)
						);*/ // Returns duplicate payment, maybe because it expires on the first hit
						if ( $show_debug && WP_DEBUG ) {
							$callback_url = $this->cofidispay_notify_url;
							$callback_url = str_replace( '[ANTI_PHISHING_KEY]', $this->cofidispay_settings['secret_key'], $callback_url );
							$callback_url = str_replace( '[ORDER_ID]', trim( $order_mb_details['id'] ), $callback_url );
							$callback_url = str_replace( '[AMOUNT]', $order_mb_details['val'], $callback_url );
							$callback_url = str_replace( '[REQUEST_ID]', $order_mb_details['request_id'], $callback_url );
							?>
							<hr/>
							<p>
								<?php _e( 'Callback URL', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:<br/>
								<textarea readonly type="text" class="input-text" cols="20" rows="5" style="width: 100%; height: 50%; font-size: 10px;"><?php echo $callback_url; ?></textarea>
							</p>
							<script type="text/javascript">
							jQuery( document ).ready( function() {
								jQuery( '#multibanco_ifthen_for_woocommerce_simulate_callback' ).on( 'click', function() {
									if ( confirm( '<?php _e( 'This is a testing tool and will set the order as paid. Are you sure you want to proceed?', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' ) ) {
										jQuery.get( '<?php echo $callback_url; ?>', '', function( response ) {
											alert( '<?php _e( 'This page will now reload. If the order is not set as paid and processing (or completed, if it only contains virtual and downloadable products) please check the debug logs.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
											window.location.reload();
										}).fail( function() {
											alert( '<?php _e( 'Error: Could not set the order as paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
										});
									}
								});
							});
							</script>
							<p align="center">
								<input type="button" class="button" id="multibanco_ifthen_for_woocommerce_simulate_callback" value="<?php echo esc_attr( __( 'Simulate callback payment', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ); ?>"/>
							</p>
							<?php
						}
					} else {
						// PAID?
						if ( $date_paid ) {
							echo '<p><strong>' . __( 'Paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . $date_paid . '</strong></p>';
						}
					}
				} else {
					echo '<p>' . __( 'No details available', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '.</p><p>' . sprintf(
						__( 'This must be an error because the payment method of this order is %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'Cofidis Pay'
					) . '.</p>';

				}
				break;
			// None
			default:
				echo '<p>' . __( 'No details available', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '.</p><p>' . __( 'The payment method of this order is not Multibanco, MB WAY, Credit card or Payshop', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '.</p>';
				echo '<style type="text/css">#' . $this->multibanco_id . ' { display: none; }</style>';
				$deleted = false;
				// If we have Multibanco data, we should delete it
				if ( $order_mb_details = $this->get_multibanco_order_details( $order->get_id() ) ) {
					foreach ( $order_mb_details as $key => $value ) {
						$order->delete_meta_data( '_' . $this->multibanco_id . '_' . $key );
						$deleted = true;
					}
				}
				// If we have MB WAY data, we should delete it
				if ( $order_mb_details = $this->get_mbway_order_details( $order->get_id() ) ) {
					foreach ( $order_mb_details as $key => $value ) {
						$order->delete_meta_data( '_' . $this->mbway_id . '_' . $key );
						$deleted = true;
					}
				}
				// If we have Payshop data, we should delete it
				if ( $order_mb_details = $this->get_payshop_order_details( $order->get_id() ) ) {
					foreach ( $order_mb_details as $key => $value ) {
						$order->delete_meta_data( '_' . $this->payshop_id . '_' . $key );
						$deleted = true;
					}
				}
				// If we have Credit card data, we should delete it
				if ( $order_mb_details = $this->get_creditcard_order_details( $order->get_id() ) ) {
					foreach ( $order_mb_details as $key => $value ) {
						$order->delete_meta_data( '_' . $this->creditcard_id . '_' . $key );
						$deleted = true;
					}
				}
				// If we have Cofidis Pay data, we should delete it
				if ( $order_mb_details = $this->get_cofidispay_order_details( $order->get_id() ) ) {
					foreach ( $order_mb_details as $key => $value ) {
						$order->delete_meta_data( '_' . $this->creditcard_id . '_' . $key );
						$deleted = true;
					}
				}
				if ( $deleted ) {
					$order->save();
				}
				break;
		}

	}

	/* Allow searching orders by Multibanco or Payshop reference - CPT and HPOS compatible */
	public function shop_order_search( $search_fields ) {
		$search_fields[] = '_' . $this->multibanco_id . '_ref';
		$search_fields[] = '_' . $this->payshop_id . '_ref';
		return $search_fields;
	}

	/* Set new order Multibanco Entity/Reference/Value on meta */
	public function multibanco_set_order_mb_details( $order_id, $order_mb_details ) {
		$order = wc_get_order( $order_id );
		$order->update_meta_data( '_' . $this->multibanco_id . '_ent', $order_mb_details['ent'] );
		$order->update_meta_data( '_' . $this->multibanco_id . '_ref', $order_mb_details['ref'] );
		$order->update_meta_data( '_' . $this->multibanco_id . '_val', $order_mb_details['val'] );
		if ( $this->get_multibanco_ref_mode() == 'incremental_expire' ) {
			// Update last seed
			$this->multibanco_last_incremental_expire_ref++;
			update_option( 'multibanco_last_incremental_expire_ref', intval( $this->multibanco_last_incremental_expire_ref ) );
			// Update order reference expiration
			$order->update_meta_data( '_' . $this->multibanco_id . '_exp', $this->get_reference_expiration_days( intval( apply_filters( 'multibanco_ifthen_incremental_expire_days', 0 ) ) ) );
		}
		if ( isset( $order_mb_details['exp'] ) ) {
			$order->update_meta_data( '_' . $this->multibanco_id . '_exp', $order_mb_details['exp'] );
		}
		if ( isset( $order_mb_details['RequestId'] ) ) {
			$order->update_meta_data( '_' . $this->multibanco_id . '_RequestId', $order_mb_details['RequestId'] );
		}
		$order->save();
		$this->debug_log_extra( $this->multibanco_id, 'multibanco_set_order_mb_details - Details updated on the database: ' . wp_json_encode( $order_mb_details ) . ' - Order: ' . $order->get_id() );
	}

	/* Clear Multibanco Entity/Reference/Value on meta */
	public function multibanco_clear_order_mb_details( $order_id ) {
		$order = wc_get_order( $order_id );
		$order->delete_meta_data( '_' . $this->multibanco_id . '_ent' );
		$order->delete_meta_data( '_' . $this->multibanco_id . '_ref' );
		$order->delete_meta_data( '_' . $this->multibanco_id . '_val' );
		$order->delete_meta_data( '_' . $this->multibanco_id . '_exp' );
		$order->delete_meta_data( '_' . $this->multibanco_id . '_RequestId' );
		$order->save();
	}

	/* Get Reference expiration date/time in days */
	public function get_reference_expiration_days( $days ) {
		$d = date_create( date_i18n( DateTime::ISO8601 ) );
		date_add( $d, date_interval_create_from_date_string( '+' . $days . ' days' ) );
		$d->modify( 'tomorrow' );
		$d->modify( '1 second ago' );
		return date_format( $d, 'Y-m-d H:i:s' );
	}

	/* Format Multibanco Reference expiration date/time */
	public function multibanco_format_expiration( $exp, $order_id ) {
		// $d = strtotime( $exp );
		// date_i18n( get_option( 'date_format' ), $d ) . ', '.date_i18n( get_option( 'time_format' ), $d )
		$exp_formated = substr( $exp, 0, 16 );
		if ( $exp < date_i18n( 'Y-m-d H:i:s' ) ) {
			$exp_formated = '<s>' . $exp_formated . '</s> ' . __( '(expired)', 'multibanco-ifthen-software-gateway-for-woocommerce' );
		}
		return apply_filters( 'multibanco_ifthen_format_expiration', $exp_formated, $exp, $order_id );
	}

	/* Format MB WAY expiration date/time */
	public function mbway_format_expiration( $exp, $order_id ) {
		if ( $exp < date_i18n( 'Y-m-d H:i:s' ) ) {
			$exp_formated = substr( $exp, 0, 16 );
			$exp_formated = '<s>' . $exp_formated . '</s> ' . __( '(expired)', 'multibanco-ifthen-software-gateway-for-woocommerce' );
		} else {
			$exp_formated = substr( $exp, 11, 5 );
		}
		return apply_filters( 'mbway_ifthen_format_expiration', $exp_formated, $exp, $order_id );
	}

	/* Format Payshop expiration date/time */
	public function payshop_format_expiration( $exp, $order_id ) {
		if ( $exp < date_i18n( 'Y-m-d' ) ) {
			$exp_formated = $exp; // Only date
			$exp_formated = '<s>' . $exp_formated . '</s> ' . __( '(expired)', 'multibanco-ifthen-software-gateway-for-woocommerce' );
		} else {
			$exp_formated = $exp; // Only date
		}
		return apply_filters( 'payshop_ifthen_format_expiration', $exp_formated, $exp, $order_id );
	}

	/* Set new order MB WAY details on meta */
	public function multibanco_set_order_mbway_details( $order_id, $order_mbway_details ) {
		$order = wc_get_order( $order_id );
		$order->update_meta_data( '_' . $this->mbway_id . '_mbwaykey', $order_mbway_details['mbwaykey'] );
		$order->update_meta_data( '_' . $this->mbway_id . '_id_pedido', $order_mbway_details['id_pedido'] );
		$order->update_meta_data( '_' . $this->mbway_id . '_phone', $order_mbway_details['phone'] );
		$order->update_meta_data( '_' . $this->mbway_id . '_val', $order_mbway_details['val'] );
		$order->update_meta_data( '_' . $this->mbway_id . '_time', date_i18n( 'Y-m-d H:i:s' ) );
		$order->update_meta_data( '_' . $this->mbway_id . '_exp', $this->get_mbway_expiration() );
		$order->save();
	}

	/* Get MB WAY expiration date/time */
	public function get_mbway_expiration() {
		$d = date_create( date_i18n( DateTime::ISO8601 ) );
		date_add( $d, date_interval_create_from_date_string( '+' . $this->mbway_minutes . ' minutes' ) );
		return date_format( $d, 'Y-m-d H:i:s' );
	}

	/* Set new order Payshop Reference details on meta */
	public function multibanco_set_order_payshop_details( $order_id, $order_payshop_details ) {
		$order = wc_get_order( $order_id );
		$order->update_meta_data( '_' . $this->payshop_id . '_payshopkey', $order_payshop_details['payshopkey'] );
		$order->update_meta_data( '_' . $this->payshop_id . '_ref', $order_payshop_details['ref'] );
		$order->update_meta_data( '_' . $this->payshop_id . '_request_id', $order_payshop_details['request_id'] );
		$order->update_meta_data( '_' . $this->payshop_id . '_id', $order_payshop_details['id'] );
		$order->update_meta_data( '_' . $this->payshop_id . '_val', $order_payshop_details['val'] );
		$order->update_meta_data( '_' . $this->payshop_id . '_time', date_i18n( 'Y-m-d H:i:s' ) );
		if ( isset( $order_payshop_details['exp'] ) ) {
			$order->update_meta_data( '_' . $this->payshop_id . '_exp', $order_payshop_details['exp'] );
		}
		$order->save();
	}

	/* Set new order Credit card details on meta */
	public function multibanco_set_order_creditcard_details( $order_id, $order_creditcard_details ) {
		$order = wc_get_order( $order_id );
		$order->update_meta_data( '_' . $this->creditcard_id . '_creditcardkey', $order_creditcard_details['creditcardkey'] );
		$order->update_meta_data( '_' . $this->creditcard_id . '_request_id', $order_creditcard_details['request_id'] );
		$order->update_meta_data( '_' . $this->creditcard_id . '_id', $order_creditcard_details['id'] );
		$order->update_meta_data( '_' . $this->creditcard_id . '_val', $order_creditcard_details['val'] );
		$order->update_meta_data( '_' . $this->creditcard_id . '_payment_url', $order_creditcard_details['payment_url'] );
		$order->update_meta_data( '_' . $this->creditcard_id . '_wd_secret', $order_creditcard_details['wd_secret'] );
		$order->update_meta_data( '_' . $this->creditcard_id . '_time', date_i18n( 'Y-m-d H:i:s' ) );
		$order->save();
	}

	/* Set new order Credit card details on meta */
	public function multibanco_set_order_cofidispay_details( $order_id, $order_cofidispay_details ) {
		$order = wc_get_order( $order_id );
		$order->update_meta_data( '_' . $this->cofidispay_id . '_cofidispaykey', $order_cofidispay_details['cofidispaykey'] );
		$order->update_meta_data( '_' . $this->cofidispay_id . '_request_id', $order_cofidispay_details['request_id'] );
		$order->update_meta_data( '_' . $this->cofidispay_id . '_id', $order_cofidispay_details['id'] );
		$order->update_meta_data( '_' . $this->cofidispay_id . '_val', $order_cofidispay_details['val'] );
		$order->update_meta_data( '_' . $this->cofidispay_id . '_payment_url', $order_cofidispay_details['payment_url'] );
		$order->update_meta_data( '_' . $this->cofidispay_id . '_wd_secret', $order_cofidispay_details['wd_secret'] );
		$order->update_meta_data( '_' . $this->cofidispay_id . '_time', date_i18n( 'Y-m-d H:i:s' ) );
		$order->save();
	}

	/* Get/Create Multibanco Reference */
	public function mb_webservice_filter_descricao( $desc ) {
		// Trim and decode
		$desc = htmlspecialchars_decode( trim( $desc ), ENT_QUOTES );
		// Remove '
		$desc = str_replace( "'", '', $desc );
		// Remove "
		$desc = str_replace( '"', '', $desc );
		// Limit
		$desc = trim( $desc );
		$desc = substr( $desc, 0, MBWAY_IFTHEN_DESC_LEN );
		return $desc;
	}
	public function multibanco_get_ref( $order_id, $force_change = false, $throw_exception = false ) {
		$order = wc_get_order( $order_id );
		$this->debug_log_extra( $this->multibanco_id, 'multibanco_get_ref - Force change: ' . ( $force_change ? 'true' : 'false' ) . ' - Order ' . $order->get_id() );
		if ( $this->wc_deposits_active ) {
			if ( ! $this->multibanco_deposits_already_forced ) {
				if ( $order->get_meta( '_wc_deposits_order_has_deposit' ) == 'yes' && ( is_checkout() || has_block( 'woocommerce/checkout' ) ) && has_action( 'woocommerce_thankyou' ) ) {
					if ( $order->get_meta( '_wc_deposits_deposit_paid' ) == 'yes' ) {
						if ( $order->get_meta( '_wc_deposits_second_payment_paid' ) == 'no' ) {
							$force_change                             = true;
							$this->multibanco_deposits_already_forced = true;
							$this->debug_log_extra( $this->multibanco_id, 'multibanco_get_ref - Force change: true, because of WC Deposits - Order ' . $order->get_id() );
						}
					}
				}
			}
		}
		$order_currency = $order->get_currency();
		if ( trim( $order_currency ) === 'EUR' ) {
			if (
				! $force_change
				&&
				$order_mb_details = $this->get_multibanco_order_details( $order->get_id() )
			) {
				$this->debug_log_extra( $this->multibanco_id, 'multibanco_get_ref - Got reference from database ' . wp_json_encode( $order_mb_details ) . ' - Order ' . $order->get_id() );
				// Already created, return it!
				return array(
					'ent' => $order_mb_details['ent'],
					'ref' => $order_mb_details['ref'],
					'val' => $order_mb_details['val'],
				);
			} else {
				// Value ok?
				if ( $this->get_order_total_to_pay( $order ) < $this->multibanco_min_value ) {
					return sprintf(
						__( 'It’s not possible to use %1$s to pay values under %2$s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'Multibanco',
						wc_price( $this->multibanco_min_value, array( 'currency' => 'EUR' ) )
					);
				} else {
					// Value ok? (again)
					if ( $this->get_order_total_to_pay( $order ) > $this->multibanco_max_value ) {
						return sprintf(
							__( 'It’s not possible to use %1$s to pay values above %2$s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'Multibanco',
							wc_price( $this->multibanco_max_value, array( 'currency' => 'EUR' ) )
						);
					} else {
						// Create a new reference
						if ( $this->multibanco_api_mode_enabled ) {

							// API Mode
							$mbkey = apply_filters( 'multibanco_ifthen_base_mbkey', $this->multibanco_settings['mbkey'], $order );
							if (
								strlen( trim( $mbkey ) ) == 10
								&&
								trim( $this->multibanco_settings['secret_key'] ) != ''
							) {
								$url         = $this->multibanco_api_url;
								$desc        = trim( get_bloginfo( 'name' ) );
								$args        = array(
									'method'   => 'POST',
									'timeout'  => apply_filters( 'multibanco_ifthen_webservice_timeout', 30 ),
									'blocking' => true,
									'headers'  => array( 'Content-Type' => 'application/json; charset=utf-8' ),
									'body'     => array(
										'mbKey'       => $mbkey,
										'orderId'     => (string) apply_filters( 'ifthen_webservice_send_order_number_instead_id', false ) ? $order->get_order_number() : $order->get_id(),
										'amount'      => (string) round( floatval( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ) ), 2 ),
										'description' => $this->mb_webservice_filter_descricao( apply_filters( 'multibanco_ifthen_webservice_desc', $desc, $order->get_id() ) ),
									),
								);
								$expire_days = apply_filters( 'multibanco_ifthen_webservice_expire_days', trim( $this->multibanco_settings['api_expiry'] ), $order );
								if ( $expire_days != '' ) {
									$args['body']['expiryDays'] = trim( $expire_days );
									// Temos de calcular a data e guardar mais lá à frente
								}
								$args['body'] = json_encode( $args['body'] );
								$response     = wp_remote_post( $url, $args );
								if ( is_wp_error( $response ) ) {
									$debug_msg       = '- Error contacting the IfthenPay servers - Order ' . $order->get_id() . ' - ' . $response->get_error_message();
									$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
									$this->debug_log( $this->multibanco_id, $debug_msg, 'error', true, $debug_msg_email );
									return false;
								} else {
									if ( isset( $response['response']['code'] ) && intval( $response['response']['code'] ) == 200 && isset( $response['body'] ) && trim( $response['body'] ) != '' ) {
										if ( $body = json_decode( $response['body'] ) ) {
											if ( trim( $body->Status ) == '0' ) {
												$details = array(
													'mbkey'     => $mbkey,
													'ent'       => $body->Entity,
													'ref'       => $body->Reference,
													'val'       => $this->get_order_total_to_pay( $order ),
													'RequestId' => $body->RequestId,
												);
												if ( trim( $body->ExpiryDate ) != '' ) {
													$temp           = explode( '-', trim( $body->ExpiryDate ) );
													$date           = implode( '-', array_reverse( $temp ) ) . ' 23:59:59';
													$details['exp'] = $date;
												}
												// Store on the order for later use (like the email)
												$this->multibanco_set_order_mb_details( $order->get_id(), $details );
												$this->debug_log( $this->multibanco_id, 'Multibanco payment details (' . $details['ent'] . ' / ' . $details['ref'] . ' / ' . $details['val'] . ') generated for Order ' . $order->get_id() );
												// Return it!
												do_action(
													'multibanco_ifthen_created_reference',
													array(
														'ent' => $details['ent'],
														'ref' => $details['ref'],
													),
													$order->get_id(),
													$force_change
												);
												// WooCommerce Deposits support - force ref creation again
												if ( ! $force_change && $this->wc_deposits_active && ! $this->multibanco_action_deposits_set ) {
													add_action( 'woocommerce_checkout_order_processed', array( $this, 'multibanco_get_ref_deposit' ), 20, 1 );
													$this->debug_log( $this->multibanco_id, 'Because of WooCommerce Deposits a new reference will be generated for Order ' . $order->get_id() );
													$this->multibanco_action_deposits_set = true;
												}
												return $details;
											} else {
												$debug_msg       = '- Error: ' . trim( $body->Message ) . ' - Order ' . $order->get_id();
												$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
												$this->debug_log( $this->multibanco_id, $debug_msg, 'error', true, $debug_msg_email );
												if ( $throw_exception ) {
													throw new Exception(
														sprintf(
															/* translators: %s: payment method */
															__( 'An error occurred processing the %s Payment request - please try again', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
															'Multibanco'
														)
													);
												}
												return false;
											}
										} else {
											$debug_msg       = '- Response body is not JSON - Order ' . $order->get_id();
											$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
											$this->debug_log( $this->multibanco_id, $debug_msg, 'error', true, $debug_msg_email );
											if ( $throw_exception ) {
												throw new Exception(
													sprintf(
														/* translators: %s: payment method */
														__( 'An error occurred processing the %s Payment request - please try again', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
														'Multibanco'
													)
												);
											}
											return false;
										}
									} else {
										$debug_msg       = '- Error contacting the IfthenPay servers - Order ' . $order->get_id() . ' - Incorrect response code: ' . $response['response']['code'];
										$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
										$this->debug_log( $this->multibanco_id, $debug_msg, 'error', true, $debug_msg_email );
										if ( $throw_exception ) {
											throw new Exception(
												sprintf(
													/* translators: %s: payment method */
													__( 'An error occurred processing the %s Payment request - please try again', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
													'Multibanco'
												)
											);
										}
										return false;
									}
								}
							} else {
								$error_details = '';
								if ( strlen( trim( $base['mbkey'] ) ) != 10 ) {
									$error_details = __( 'MB Key', 'multibanco-ifthen-software-gateway-for-woocommerce' );
								} else {
									if ( trim( $this->multibanco_settings['secret_key'] ) == '' ) {
										$error_details = __( 'Anti-phishing key', 'multibanco-ifthen-software-gateway-for-woocommerce' );
									}
								}
								if ( $throw_exception ) {
									throw new Exception(
										__( 'Configuration error. This payment method is disabled because required information was not set.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' ' . $error_details . '.'
									);
								}
								return __( 'Configuration error. This payment method is disabled because required information was not set.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' ' . $error_details . '.';
							}
						} else {

							// LOCAL MODE
							// Filters to be able to override the Entity and Sub-entity - Can be usefull for marketplaces
							$base = apply_filters(
								'multibanco_ifthen_base_ent_subent',
								array(
									'ent'    => $this->multibanco_settings['ent'],
									'subent' => $this->multibanco_settings['subent'],
								),
								$order
							);
							if (
								strlen( trim( $base['ent'] ) ) == 5
								&&
								strlen( trim( $base['subent'] ) ) <= 3
								&&
								intval( $base['ent'] ) > 0
								&&
								intval( $base['subent'] ) > 0
								&&
								trim( $this->multibanco_settings['secret_key'] ) != ''
							) {
								if ( isset( $this->multibanco_ents_no_repeat[ $base['ent'] ] ) && intval( $this->multibanco_ents_no_repeat[ $base['ent'] ] ) > 0 ) {
									// No repeat in x days
									$this->debug_log_extra( $this->multibanco_id, 'multibanco_get_ref - will create reference (No repeat in x days) - Order ' . $order->get_id() );
									$ref = $this->multibanco_create_ref( $base['ent'], $base['subent'], $this->get_multibanco_ref_seed(), $this->get_order_total_to_pay( $order ), intval( $this->multibanco_ents_no_repeat[ $base['ent'] ] ) );
								} else {
									if ( in_array( intval( $base['ent'] ), $this->multibanco_ents_no_check_digit ) && ( $this->multibanco_settings['use_order_id'] == 'yes' ) ) {
										// Special entities with no check digit and (eventually) expiration date - We can use the order ID
										$this->debug_log_extra( $this->multibanco_id, 'multibanco_get_ref - Will create reference (Special entities with no check digit and (eventually) expiration date) - Order ' . $order->get_id() );
										$ref = $this->multibanco_create_ref_no_check_digit( $base['ent'], $base['subent'], $order->get_id(), $this->get_order_total_to_pay( $order ) );
									} else {
										$this->debug_log_extra( $this->multibanco_id, 'multibanco_get_ref - Will create reference (Default mode) - Order ' . $order->get_id() );
										$ref = $this->multibanco_create_ref( $base['ent'], $base['subent'], $this->get_multibanco_ref_seed(), $this->get_order_total_to_pay( $order ) ); // For random mode - Less loop possibility
									}
								}
								// Store on the order for later use (like the email)
								$this->multibanco_set_order_mb_details(
									$order->get_id(),
									array(
										'ent' => $base['ent'],
										'ref' => $ref,
										'val' => $this->get_order_total_to_pay( $order ),
									)
								);
								$this->debug_log( $this->multibanco_id, 'Multibanco payment details (' . $base['ent'] . ' / ' . $ref . ' / ' . $this->get_order_total_to_pay( $order ) . ') generated for Order ' . $order->get_id() );
								// Return it!
								do_action(
									'multibanco_ifthen_created_reference',
									array(
										'ent' => $base['ent'],
										'ref' => $ref,
									),
									$order->get_id(),
									$force_change
								);
								// WooCommerce Deposits support - force ref creation again
								if ( ! $force_change && $this->wc_deposits_active && ! $this->multibanco_action_deposits_set ) {
									add_action( 'woocommerce_checkout_order_processed', array( $this, 'multibanco_get_ref_deposit' ), 20, 1 );
									$this->debug_log( $this->multibanco_id, 'Because of WooCommerce Deposits a new reference will be generated for Order ' . $order->get_id() );
									$this->multibanco_action_deposits_set = true;
								}
								return array(
									'ent' => $base['ent'],
									'ref' => $ref,
									'val' => $this->get_order_total_to_pay( $order ),
								);
							} else {
								$error_details = '';
								if ( strlen( trim( $base['ent'] ) ) != 5 || ( ! intval( $base['ent'] ) > 0 ) ) {
									$error_details = __( 'Entity', 'multibanco-ifthen-software-gateway-for-woocommerce' );
								} else {
									if ( strlen( trim( $base['subent'] ) ) > 3 || ( ! intval( $base['subent'] ) > 0 ) ) {
										$error_details = __( 'Subentity', 'multibanco-ifthen-software-gateway-for-woocommerce' );
									} else {
										if ( trim( $this->multibanco_settings['secret_key'] ) == '' ) {
											$error_details = __( 'Anti-phishing key', 'multibanco-ifthen-software-gateway-for-woocommerce' );
										}
									}
								}
								return __( 'Configuration error. This payment method is disabled because required information was not set.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' ' . $error_details . '.';
							}
						}
					}
				}
			}
		} else {
			return __( 'Configuration error. This order currency is not Euros (&euro;).', 'multibanco-ifthen-software-gateway-for-woocommerce' );
		}
	}
	public function multibanco_get_ref_deposit( $order_id ) {
		// WooCommerce Deposits support - force ref creation again
		if ( $this->wc_deposits_active ) {
			$ref = $this->multibanco_get_ref( $order_id, true );
		}
	}
	public function multibanco_create_ref( $ent, $subent, $seed, $total, $no_repeat_days = 0, $just_create_no_check = false ) {
		$subent    = str_pad( intval( $subent ), 3, '0', STR_PAD_LEFT );
		$seed      = str_pad( intval( $seed ), 4, '0', STR_PAD_LEFT );
		$chk_str   = sprintf( '%05u%03u%04u%08u', $ent, $subent, $seed, round( $total * 100 ) );
		$chk_array = array( 3, 30, 9, 90, 27, 76, 81, 34, 49, 5, 50, 15, 53, 45, 62, 38, 89, 17, 73, 51 );
		$chk_val   = 0;
		for ( $i = 0; $i < 20; $i++ ) {
			$chk_int  = substr( $chk_str, 19 - $i, 1 );
			$chk_val += ( $chk_int % 10 ) * $chk_array[ $i ];
		}
		$chk_val   %= 97;
		$chk_digits = sprintf( '%02u', 98 - $chk_val );
		$ref        = $subent . $seed . $chk_digits;
		// Does it exists already? Let's browse the database!
		if ( ! $just_create_no_check ) {
			$exists = false;
			$orders = WC_IfthenPay_Webdados()->wc_get_orders(
				array(
					'type'                              => array( 'shop_order' ),
					'limit'                             => 1, // If there's one, it's enough
					'_' . $this->multibanco_id . '_ent' => $ent,
					'_' . $this->multibanco_id . '_ref' => $ref,
					'status'                            => array( 'wc-on-hold', 'wc-pending' ), // Should we be checking for our valid statuses?
				),
				$this->multibanco_id
			);
			if ( count( $orders ) > 0 ) {
				$exists = true;
			} else {
				// No open orders but also check for special entities that do not allow references to be repeated on x days
				if ( intval( $no_repeat_days ) > 0 ) {
					$orders = WC_IfthenPay_Webdados()->wc_get_orders(
						array(
							'type'       => array( 'shop_order' ),
							'limit'      => 1, // If there's one, it's enough
							'_' . $this->multibanco_id . '_ent' => $ent,
							'_' . $this->multibanco_id . '_ref' => $ref,
							'date_after' => date_i18n( 'Y-m-d', strtotime( '-' . intval( $no_repeat_days ) . ' days ' ) ),
						),
						$this->multibanco_id
					);
					if ( count( $orders ) > 0 ) {
						$exists = true;
					}
				}
			}
			if ( $exists ) {
				// Reference exists - Let's try again
				$seed = $this->get_multibanco_ref_seed( false );
				$ref  = $this->multibanco_create_ref( $ent, $subent, $seed, $total, intval( $no_repeat_days ) );
			}
		} else {
			// No checking - for tests only
		}
		$this->debug_log_extra( $this->multibanco_id, 'multibanco_create_ref - Reference generated: ' . $ent . ' ' . $ref . ' ' . $total );
		return $ref;
	}
	public function multibanco_create_ref_no_check_digit( $ent, $subent, $id, $total ) {
		$subent = str_pad( intval( $subent ), 3, '0', STR_PAD_LEFT );
		$id     = str_pad( intval( $id ), 6, '0', STR_PAD_LEFT );
		$ref    = $subent . $id;
		return $ref;
	}



	/* Get/Create Payshop Reference */
	public function payshop_get_ref( $order_id, $force_change = false ) {
		$order          = wc_get_order( $order_id );
		$order_currency = $order->get_currency();
		if ( trim( $order_currency ) === 'EUR' ) {
			if (
				! $force_change
				&&
				$order_mb_details = $this->get_payshop_order_details( $order->get_id() )

			) {
				// Already created, return it!
				return $order_mb_details;
			} else {
				// Value ok?
				if ( $this->get_order_total_to_pay( $order ) < $this->payshop_min_value ) {
					return sprintf(
						__( 'It’s not possible to use %1$s to pay values under %2$s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'Payshop',
						wc_price( $this->payshop_min_value, array( 'currency' => 'EUR' ) )
					);
				} else {
					// Value ok? (again)
					if ( $this->get_order_total_to_pay( $order ) > $this->payshop_max_value ) {
						return sprintf(
							__( 'It’s not possible to use %1$s to pay values above %2$s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'Payshop',
							wc_price( $this->payshop_max_value, array( 'currency' => 'EUR' ) )
						);
					} else {
						$payshop = new WC_Payshop_IfThen_Webdados();
						if ( $payshop->webservice_set_pedido( $order->get_id() ) ) {
							return $this->get_payshop_order_details( $order->get_id() );
						} else {
							return __( 'Error contacting IfthenPay servers to create Payshop Payment', 'multibanco-ifthen-software-gateway-for-woocommerce' );
						}
					}
				}
			}
		} else {
			return __( 'Configuration error. This order currency is not Euros (&euro;).', 'multibanco-ifthen-software-gateway-for-woocommerce' );
		}
	}

	public function mbway_webservice_set_pedido( $order_id, $phone ) {
		$debug       = $this->mbway_settings['debug'] == 'yes';
		$debug_email = false;
		if ( $debug ) {
			$debug_email = trim( $this->mbway_settings['debug_email'] ) != '' ? trim( $this->mbway_settings['debug_email'] ) : false;
		}
		$url               = $this->mbway_webservice_url . '/SetPedido';
		$order             = wc_get_order( $order_id );
		$mbwaykey          = apply_filters( 'multibanco_ifthen_base_mbwaykey', $this->mbway_settings['mbwaykey'], $order );
		$id_for_backoffice = apply_filters( 'ifthen_webservice_send_order_number_instead_id', false ) ? $order->get_order_number() : $order->get_id();
		$desc              = trim( get_bloginfo( 'name' ) );
		$desc              = substr( $desc, 0, MBWAY_IFTHEN_DESC_LEN - strlen( ' #' . $order->get_order_number() ) );
		$desc             .= ' #' . $order->get_order_number();
		$args              = array(
			'method'   => 'POST',
			'timeout'  => apply_filters( 'mbway_ifthen_webservice_timeout', 30 ),
			'blocking' => true,
			'body'     => array(
				'MbWayKey'   => $mbwaykey,
				'canal'      => '03', // Online
				'referencia' => (string) $id_for_backoffice,
				'valor'      => (string) round( floatval( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ) ), 2 ),
				'nrtlm'      => $phone,
				'email'      => '', // Não usamos
				'descricao'  => $this->mb_webservice_filter_descricao( apply_filters( 'mbway_ifthen_webservice_desc', $desc, $order->get_id() ) ),
			),
		);
		$this->debug_log_extra( $this->mbway_id, '- Request payment with args: ' . wp_json_encode( $args ) );
		$response = wp_remote_post( $url, $args );
		if ( is_wp_error( $response ) ) {
			$debug_msg       = '- Error contacting the IfthenPay servers - Order ' . $order->get_id() . ' - ' . $response->get_error_message();
			$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
			$this->debug_log( $this->mbway_id, $debug_msg, 'error', $debug_email, $debug_msg_email );
			return false;
		} else {
			if ( isset( $response['response']['code'] ) && intval( $response['response']['code'] ) == 200 && isset( $response['body'] ) && trim( $response['body'] ) != '' ) {
				if ( function_exists( 'simplexml_load_string' ) ) {
					$xmlData = simplexml_load_string( $response['body'] );
					// if ( $debug ) $this->debug_log( $this->mbway_id, '- MB WAY payment request response - Order '.$order->get_id().' - '.$xmlData->asXML() ); //Desnecessário - vai para o email
					// Verificar primeiro se temos o Estado correcto ou não
					if ( trim( $xmlData->Estado ) == '000' ) {
						if ( trim( $xmlData->IdPedido ) != '' && floatval( $xmlData->Valor ) > 0 ) {
							$id_pedido = trim( $xmlData->IdPedido );
							$valor     = floatval( $xmlData->Valor );
							if ( $valor == round( floatval( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ) ), 2 ) ) {
								WC_IfthenPay_Webdados()->multibanco_set_order_mbway_details(
									$order->get_id(),
									array(
										'mbwaykey'  => $mbwaykey,
										'id_pedido' => $id_pedido,
										'phone'     => $phone,
										'val'       => $valor,
									)
								);
								if ( $debug ) $this->debug_log( $this->mbway_id, '- MB WAY payment request created on IfthenPay servers - Order ' . $order->get_id() . ' - id_pedido: ' . $id_pedido );
								do_action( 'mbway_ifthen_created_reference', $id_pedido, $order->get_id(), $phone );
								return true;
							} else {
								$debug_msg       = '- Error contacting the IfthenPay servers - Order ' . $order->get_id() . ' - Incorrect "Valor"';
								$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
								if ( $debug ) $this->debug_log( $this->mbway_id, $debug_msg, 'error', $debug_email, $debug_msg_email );
								return false;
							}
						} else {
							$debug_msg       = '- Error contacting the IfthenPay servers - Order ' . $order->get_id() . ' - Missing "IdPedido" or "Valor"';
							$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
							if ( $debug ) $this->debug_log( $this->mbway_id, $debug_msg, 'error', $debug_email, $debug_msg_email );
							return false;
						}
					} else {
						$debug_msg       = '- Error contacting the IfthenPay servers - Order ' . $order->get_id() . ' - ' . trim( $xmlData->Estado ) . ' ' . trim( $xmlData->MsgDescricao );
						$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
						if ( $debug ) $this->debug_log( $this->mbway_id, $debug_msg, 'error', $debug_email, $debug_msg_email );
						return false;
					}
				} else {
					$debug_msg = '- Error contacting the IfthenPay servers - Order ' . $order->get_id() . ' - "simplexml_load_string" function does not exist';
					if ( $debug ) $this->debug_log( $this->mbway_id, $debug_msg, 'error', $debug_email );
					return false;
				}
			} else {
				$debug_msg       = '- Error contacting the IfthenPay servers - Order ' . $order->get_id() . ' - Incorrect response code: ' . $response['response']['code'];
				$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
				if ( $debug ) $this->debug_log( $this->mbway_id, $debug_msg, 'error', $debug_email, $debug_msg_email );
				return false;
			}
		}
		return false;
	}

	/* Force Reference creation on New Order (not the British Synthpop band) - Classic checkout by the woocommerce_checkout_update_order_meta action and forced on the blocks checkout */
	public function multibanco_woocommerce_checkout_update_order_meta( $order_id ) {
		$order = wc_get_order( $order_id );
		// Avoid duplicate instructions on the email...
		if ( $order->get_payment_method() === $this->multibanco_id ) {
			$this->debug_log_extra( $this->multibanco_id, 'multibanco_woocommerce_checkout_update_order_meta - Force ref generation before anything - Order ' . $order->get_id() );
			$ref = $this->multibanco_get_ref( $order->get_id(), false, true );
			// That should do it...
			$this->debug_log_extra( $this->multibanco_id, 'multibanco_woocommerce_checkout_update_order_meta - Ref: ' . wp_json_encode( $ref ) . ' - Order ' . $order->get_id() );
		}
	}

	/* Get total to pay */
	public function get_order_total_to_pay( $order ) {
		$order_total_to_pay = $order->get_total();
		if ( $this->wc_deposits_active ) {
			// Has deposit
			if ( $order->get_meta( '_wc_deposits_order_has_deposit' ) == 'yes' ) {
				// First payment?
				if ( $order->get_meta( '_wc_deposits_deposit_paid' ) != 'yes' && $order->get_status() != 'partially-paid' ) {
					$order_total_to_pay = floatval( $order->get_meta( '_wc_deposits_deposit_amount' ) );
				} else {
					// Second payment
					$order_total_to_pay = floatval( $order->get_meta( '_wc_deposits_second_payment' ) );
				}
			}
		}
		return $order_total_to_pay;
	}

	/* Check if order type is valid for payments */
	public function is_valid_order_type( $order_object ) {
		if ( in_array(
			get_class( $order_object ),
			apply_filters(
				'multibanco_ifthen_valid_order_classes',
				array(
					'WC_Order',
					'Automattic\WooCommerce\Admin\Overrides\Order',
				)
			)
		) ) {
			return true;
		}
		return false;
	}

	// phpcs:disable PEAR.Functions.FunctionCallSignature.Indent
	/* Change Ref if order total is changed on wp-admin */
	public function multibanco_maybe_value_changed( $order ) {

		if ( is_admin() ) {

			// We only do it for regular orders, not subscriptions or other special types of orders
			if ( ! $this->is_valid_order_type( $order ) ) {
				return;
			}

			switch ( $order->get_payment_method() ) {

				// Multibanco
				case $this->multibanco_id:
					$order_status = $order->get_status();
					if ( $this->order_needs_payment( $order ) ) {

						$order_total_to_pay = $this->get_order_total_to_pay( $order );
						if (
							( ! $order_mb_details = $this->get_multibanco_order_details( $order->get_id() ) )
							||
							(
								floatval( $order_total_to_pay ) != floatval( $order_mb_details['val'] )
								&&
								$order_status != 'partially-paid' // If it's partially paid the value will be diferent and we need to ignore it
							)
						) {
							// WPML?
							if ( $this->wpml_active ) {
								$this->woocommerce_new_customer_note_fix_wpml_do_it( $order->get_id() );
							}
							$ref = $this->multibanco_get_ref( $order->get_id(), true );
							$this->debug_log( $this->multibanco_id, 'Order ' . $order->get_id() . ' value changed' );
							if ( is_array( $ref ) ) {
								$order->add_order_note(
									sprintf(
										sprintf(
											__( 'The %s payment details have changed', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
											'Multibanco'
										) . ':
– – – – – – – – – – – – – – – – – - - - -
' . __( 'Previous entity', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
' . __( 'Previous reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
' . __( 'Previous value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
– – – – – – – – – – – – – – – – – - - - -
' . __( 'New entity', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
' . __( 'New reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
' . __( 'New value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
– – – – – – – – – – – – – – – – – - - - -
' . sprintf( __( 'If the customer pays using the previous details, the payment will be accepted by the %s system, but the order will not be updated via callback.', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 'Multibanco' ),
										isset( $order_mb_details['ent'] ) ? trim( $order_mb_details['ent'] ) : '',
										isset( $order_mb_details['ref'] ) ? $this->format_multibanco_ref( $order_mb_details['ref'] ) : '',
										isset( $order_mb_details['val'] ) ? wc_price( $order_mb_details['val'], array( 'currency' => $order->get_currency() ) ) : '',
										trim( $ref['ent'] ),
										$this->format_multibanco_ref( $ref['ref'] ),
										wc_price( $order_total_to_pay, array( 'currency' => $order->get_currency() ) )
									)
								);
								// Notify client?
								if ( $this->multibanco_settings['update_ref_client'] == 'yes' ) {
									WC()->payment_gateways(); // Just in case...
									$order->add_order_note(
										sprintf(
											sprintf(
												__( 'The %s payment details have changed', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
												'Multibanco'
											) . ':
' . __( 'New entity', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
' . __( 'New reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
' . __( 'New value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s',
											trim( $ref['ent'] ),
											$this->format_multibanco_ref( $ref['ref'] ),
											wc_price( $order_total_to_pay, array( 'currency' => $order->get_currency() ) )
										),
										1
									);
								}
								// Alert and reload script
								// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterOpen, Squiz.PHP.EmbeddedPhp.ContentBeforeOpen, Squiz.PHP.EmbeddedPhp.ContentBeforeEnd
								?>
								<script type="text/javascript">
									alert( '<?php printf(
										__( 'The %s payment details have changed', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
										'Multibanco'
											); ?>. <?php echo ( $this->multibanco_settings['update_ref_client'] == 'yes' ? __( 'The customer will be notified', 'multibanco-ifthen-software-gateway-for-woocommerce' ) : __( 'You should notify the customer', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ); ?>. <?php _e( 'The page will now reload.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
									location.reload(); // We could just update our metabox...
								</script>
								<?php
								// phpcs:enable Squiz.PHP.EmbeddedPhp.ContentAfterOpen, Squiz.PHP.EmbeddedPhp.ContentBeforeOpen, Squiz.PHP.EmbeddedPhp.ContentBeforeEnd
							}
						}
					}
					break;

					// Payshop
				case $this->payshop_id:
					$order_status = $order->get_status();
					if ( $this->order_needs_payment( $order ) ) {

						$order_total_to_pay = $this->get_order_total_to_pay( $order );
						if (
								( ! $order_mb_details = $this->get_payshop_order_details( $order->get_id() ) )
								||
								(
									floatval( $order_total_to_pay ) != floatval( $order_mb_details['val'] )
									&&
									$order_status != 'partially-paid' // If it's partially paid the value will be diferent and we need to ignore it
								)
							) {
							// WPML?
							if ( $this->wpml_active ) {
								$this->woocommerce_new_customer_note_fix_wpml_do_it( $order->get_id() );
							}
							$ref = $this->payshop_get_ref( $order->get_id(), true );
							$this->debug_log( $this->payshop_id, 'Order ' . $order->get_id() . ' value changed' );
							if ( is_array( $ref ) ) {
								$order->add_order_note(
									sprintf(
										sprintf(
											__( 'The %s payment details have changed', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
											'Payshop'
										) . ':
– – – – – – – – – – – – – – – – – - - - -
' . __( 'Previous reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
' . __( 'Previous value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
– – – – – – – – – – – – – – – – – - - - -
' . __( 'New reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
' . __( 'New value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
– – – – – – – – – – – – – – – – – - - - -
' . sprintf(
										__( 'If the customer pays using the previous details, the payment will be accepted by the %s system, but the order will not be updated via callback.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
										'Payshop'
										),
										isset( $order_mb_details['ref'] ) ? $this->format_payshop_ref( $order_mb_details['ref'] ) : '',
										isset( $order_mb_details['val'] ) ? wc_price( $order_mb_details['val'], array( 'currency' => $order->get_currency() ) ) : '',
										$this->format_payshop_ref( $ref['ref'] ),
										wc_price( $order_total_to_pay, array( 'currency' => $order->get_currency() ) )
									)
								);
								// Notify client?
								if ( $this->payshop_settings['update_ref_client'] == 'yes' ) {
									WC()->payment_gateways(); // Just in case...
									$order->add_order_note(
										sprintf(
											sprintf(
												__( 'The %s payment details have changed', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
												'Payshop'
											) . ':
' . __( 'New reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
' . __( 'New value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s',
											$this->format_payshop_ref( $ref['ref'] ),
											wc_price( $order_total_to_pay, array( 'currency' => $order->get_currency() ) )
										),
										1
									);
								}
								// Alert and reload script
								// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterOpen, Squiz.PHP.EmbeddedPhp.ContentBeforeOpen, Squiz.PHP.EmbeddedPhp.ContentBeforeEnd
								?>
									<script type="text/javascript">
										alert( '<?php  printf(
											__( 'The %s payment details have changed', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
											'Payshop'
												); ?>. <?php echo ( $this->payshop_settings['update_ref_client'] == 'yes' ? __( 'The customer will be notified', 'multibanco-ifthen-software-gateway-for-woocommerce' ) : __( 'You should notify the customer', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ); ?>. <?php _e( 'The page will now reload.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
										location.reload(); // We could just update our metabox...
									</script>
									<?php
									// phpcs:enable Squiz.PHP.EmbeddedPhp.ContentAfterOpen, Squiz.PHP.EmbeddedPhp.ContentBeforeOpen, Squiz.PHP.EmbeddedPhp.ContentBeforeEnd
							}
						}
					}
					break;

				// Default
				default:
					break;
			}
		}
	}
	// phpcs:enable PEAR.Functions.FunctionCallSignature.Indent

	/*
	 Filter to be able to use wc_get_orders with our Multibanco and MB WAY references */
	// HPOS compatibility via the maybe_translate_order_query_args function
	public function multibanco_woocommerce_order_data_store_cpt_get_orders_query( $query, $query_vars, $object, $clear_key = false ) {
		if ( ! isset( $query['meta_query'] ) ) {
			$query['meta_query'] = array();
		}

		if ( is_array( $query_vars ) ) {
			foreach ( $query_vars as $key => $value ) {
				switch ( $key ) {
					// =
					case '_' . $this->multibanco_id . '_ent':
					case '_' . $this->multibanco_id . '_ref':
					case '_' . $this->mbway_id . '_mbwaykey':
					case '_' . $this->mbway_id . '_id_pedido':
					case '_' . $this->payshop_id . '_request_id':
					case '_' . $this->payshop_id . '_ref':
					case '_' . $this->payshop_id . '_id':
					case '_' . $this->creditcard_id . '_id':
					case '_' . $this->creditcard_id . '_request_id':
					case '_' . $this->creditcard_id . '_wd_secret':
					case '_' . $this->cofidispay_id . '_id':
					case '_' . $this->cofidispay_id . '_request_id':
					case '_' . $this->cofidispay_id . '_wd_secret':
						$query['meta_query'][] = array(
							'key'   => $key,
							'value' => esc_attr( $value ), // WHY esc_attr?
						);
						// Translation for HPOS
						if ( $clear_key ) {
							unset( $query[ $key ] );
						}
						break;
					// <
					case '_' . $this->multibanco_id . '_exp':
					case '_' . $this->mbway_id . '_exp':
					case '_' . $this->payshop_id . '_exp':
						// unset( $args[$key] ); // WHY?
						$query['meta_query'][] = array(
							'key'     => $key,
							'value'   => esc_attr( $value ), // WHY esc_attr?
							'compare' => '<',
						);
						// Translation for HPOS
						if ( $clear_key ) {
							unset( $query[ $key ] );
						}
						break;
				}
			}
		}

		return $query;
	}

	/**
	 * Maybe translate query args for HPOS
	 *
	 * @since 7.0.0
	 */
	public function maybe_translate_order_query_args( $args ) {
		if ( $this->hpos_enabled ) {
			if ( ! isset( $args['meta_query'] ) ) {
				$args['meta_query'] = array();
			}
			$args = $this->multibanco_woocommerce_order_data_store_cpt_get_orders_query( $args, $args, null, true );
		}
		return $args;
	}

	/* Reduce stock - on 'woocommerce_payment_complete_reduce_order_stock' */
	public function woocommerce_payment_complete_reduce_order_stock( $reduce, $order_id, $payment_method, $stock_when ) {
		if ( $reduce ) {
			$order = wc_get_order( $order_id );
			if ( $order->get_payment_method() == $payment_method ) {
				// After 3.4.0
				if ( $this->order_needs_payment( $order ) ) {
					// Pending payment
					if ( $stock_when == 'order' ) {
						// Yes, because we want to reduce on the order
						return true;
					} else {
						return false;
					}
				} else {
					// Payment done
					if ( $stock_when == '' ) {
						// Yes, because we want to reduce on payment
						return true;
					} else {
						return false;
					}
				}
			} else {
				return $reduce;
			}
		} else {
			// Already reduced
			return false;
		}
	}


	/*
	 Cancel our orders when WooCommerce cancels pending orders (even if ours are on-hold) */
	/* Cancel unpaid orders - See WooCommerce wc_cancel_unpaid_orders() */
	public function multibanco_woocommerce_cancel_unpaid_orders() {
		$methods = array();
		// Falta Cartão de crédito?
		if ( apply_filters( 'multibanco_ifthen_cancel_unpaid_orders', false ) ) {
			$methods[] = $this->multibanco_id;
		}
		if ( apply_filters( 'payshop_ifthen_cancel_unpaid_orders', false ) ) {
			$methods[] = $this->payshop_id;
		}
		if ( apply_filters( 'mbway_ifthen_cancel_unpaid_orders', false ) ) { // Doesn't make sense, but the developer could set it to on-hold...
			$methods[] = $this->mbway_id;
		}
		if ( apply_filters( 'cofidispay_ifthen_cancel_unpaid_orders', false ) ) { // Doesn't make sense, but the developer could set it to on-hold - although we have no filter for it...
			$methods[] = $this->cofidispay_id;
		}
		if ( count( $methods ) > 0 ) {
			$held_duration = get_option( 'woocommerce_hold_stock_minutes' );
			if ( $held_duration < 1 || 'yes' !== get_option( 'woocommerce_manage_stock' ) ) {
				return;
			}
			$date_before = '-' . absint( $held_duration ) . ' MINUTES';
			foreach ( $methods as $method_id ) {
				$unpaid_orders = WC_IfthenPay_Webdados()->wc_get_orders(
					array(
						'status'         => array( 'on-hold', 'pending' ), // Aqui não usamos os unpaid statuses porque podemos entrar num loop se alguém adicionar o estado cancelada e também porque não faz sentido para parcialmente pagas
						'type'           => array( 'shop_order' ),
						'limit'          => -1,
						'date_modified'  => '<' . strtotime( $date_before ),
						'payment_method' => $method_id,
					),
					$method_id
				);
				if ( $unpaid_orders ) {
					foreach ( $unpaid_orders as $unpaid_order ) {
						if ( apply_filters( 'woocommerce_cancel_unpaid_order', 'checkout' === $unpaid_order->get_created_via(), $unpaid_order ) ) {
							$unpaid_order->update_status( 'cancelled', __( 'Unpaid order cancelled - time limit exceeded.', 'woocommerce' ) );
							// Restore stock levels
							switch ( $method_id ) {
								case $this->multibanco_id:
									$filter_stock = 'multibanco_ifthen_cancel_unpaid_orders_restore_stock';
									$action       = 'multibanco_ifthen_unpaid_order_cancelled';
									break;
								case $this->payshop_id:
									$filter_stock = 'payshop_ifthen_cancel_unpaid_orders_restore_stock';
									$action       = 'payshop_ifthen_unpaid_order_cancelled';
									break;
								case $this->mbway_id:
									$filter_stock = 'mbway_ifthen_cancel_unpaid_orders_restore_stock';
									$action       = 'mbway_ifthen_unpaid_order_cancelled';
									break;
								case $this->cofidispay_id:
									$filter_stock = 'cofidispay_ifthen_cancel_unpaid_orders_restore_stock';
									$action       = 'cofidispay_ifthen_unpaid_order_cancelled';
									break;
							}
							if ( apply_filters( $filter_stock, false, $unpaid_order->get_id() ) && $unpaid_order->get_data_store()->get_stock_reduced( $unpaid_order->get_id() ) ) {
								foreach ( $unpaid_order->get_items() as $item_id => $item ) {
									// Get an instance of corresponding the WC_Product object
									if ( $product = $item->get_product() ) {
										$qty = $item->get_quantity(); // Get the item quantity
										wc_update_product_stock( $product, $qty, 'increase' );
									}
								}
							}
							do_action( $action, $unpaid_order->get_id() );
						}
					}
				}
			}
		}
	}

	public function cancel_expired_orders( $method_id ) {
		// We are not doing this on the gateway itself because the cron doesn't always load the gateways
		$args           = array(
			'status'                  => array( 'on-hold', 'pending' ), // Aqui não usamos os unpaid statuses porque podemos entrar num loop se alguém adicionar o estado cancelada e também porque não faz sentido para parcialmente pagas
			'type'                    => array( 'shop_order' ),
			'limit'                   => -1,
			'payment_method'          => $method_id,
			'_' . $method_id . '_exp' => date_i18n( 'Y-m-d H:i:s' ),
		);
		$expired_orders = WC_IfthenPay_Webdados()->wc_get_orders( $args, $method_id );
		if ( $expired_orders ) {
			foreach ( $expired_orders as $expired_order ) {
				$expired_order->update_status( 'cancelled', __( 'Unpaid order cancelled - Payment reference expired.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) );
				// The stocks are automatically restored by wc_maybe_increase_stock_levels via the 'woocommerce_order_status_cancelled' action
			}
		}
	}

	/* Multibanco cancel expired orders if incremental_expire mode is active */
	public function multibanco_cancel_expired_orders() {
		$this->cancel_expired_orders( $this->multibanco_id );
	}

	/* Multibanco SMS instructions - General. Can be used to feed any SMS gateway/plugin */
	public function multibanco_sms_instructions( $message, $order_id ) {
		$order        = wc_get_order( $order_id );
		$instructions = ''; // We return an empty string so that we always replace our placeholder, even if it's not our gateway
		if ( $order->get_payment_method() == $this->multibanco_id ) {
			if ( $this->order_needs_payment( $order ) ) {
				$ref = $this->multibanco_get_ref( $order->get_id() );
				if ( is_array( $ref ) ) {
					$instructions =
						'Multibanco'
						. ' '
						. __( 'Ent.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
						. ' '
						. $ref['ent']
						. ' '
						. __( 'Ref.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
						. ' ' . $this->format_multibanco_ref( $ref['ref'] )
						. ' '
						. __( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' )
						. ' '
						. $ref['val'];
					// Filters in case the website owner wants to customize the message
					$instructions = apply_filters( 'multibanco_ifthen_sms_instructions', $instructions, $ref['ent'], $ref['ref'], $ref['val'], $order->get_id() );
				} else {
					// error getting ref
				}
			} else {
				// No instructions
			}
		}
		// Clean
		$instructions = trim( preg_replace( '/\s+/', ' ', str_replace( '&nbsp;', ' ', $instructions ) ) );
		// Return
		return $instructions;
	}

	/* Multibanco APG SMS integration (Removed from the class because now APG also sends scheduled SMS and the payment class may not be initiated) */
	public function multibanco_apg_sms_message( $message, $order_id ) {
		$replace = $this->multibanco_sms_instructions( $message, $order_id ); // Get instructions
		return trim( preg_replace( '/\s+/', ' ', str_replace( '%multibanco_ifthen%', $replace, $message ) ) ); // Return message with %multibanco_ifthen% replaced by the instructions
	}

	/* Payshop SMS instructions - General. Can be used to feed any SMS gateway/plugin */
	public function payshop_sms_instructions( $message, $order_id ) {
		$order        = wc_get_order( $order_id );
		$instructions = ''; // We return an empty string so that we always replace our placeholder, even if it's not our gateway
		if ( $order->get_payment_method() == $this->payshop_id ) {
			if ( $this->order_needs_payment( $order ) ) {
				$ref = $this->payshop_get_ref( $order->get_id() );
				if ( is_array( $ref ) ) {
					$instructions =
						'Payshop'
						. ' '
						. __( 'Ref.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
						. ' ' . $this->format_payshop_ref( $ref['ref'] )
						. ' '
						. __( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' )
						. ' '
						. $ref['val'];
					if ( isset( $ref['exp'] ) && trim( $ref['exp'] ) != '' ) {
						$instructions .= ' ' . __( 'Valid.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' ' . $ref['exp'];
					}
					// Filters in case the website owner wants to customize the message
					$instructions = apply_filters( 'payshop_ifthen_sms_instructions', $instructions, $ref['ref'], $ref['val'], $ref['exp'], $order->get_id() );
				} else {
					// error getting ref
				}
			} else {
				// No instructions
			}
		}
		// Clean
		$instructions = trim( preg_replace( '/\s+/', ' ', str_replace( '&nbsp;', ' ', $instructions ) ) );
		// Return
		return $instructions;
	}

	/* Payshop APG SMS integration (Removed from the class because now APG also sends scheduled SMS and the payment class may not be initiated) */
	public function payshop_apg_sms_message( $message, $order_id ) {
		$replace = $this->payshop_sms_instructions( $message, $order_id ); // Get instructions
		return trim( preg_replace( '/\s+/', ' ', str_replace( '%payshop_ifthen%', $replace, $message ) ) ); // Return message with %multibanco_ifthen% replaced by the instructions
	}


	/* WooCommece Subscriptions - Do not copy our fields for renewal and resubscribe orders */
	public function multibanco_wcs_filter_meta( $meta, $to_order, $from_order ) {
		$mb_fields = array(
			// Multibanco
			'_' . $this->multibanco_id,
			// MB WAY
			'_' . $this->mbway_id,
			// Payshop
			'_' . $this->payshop_id,
			// Credit card
			'_' . $this->creditcard_id,
			// Cofidis Pay
			'_' . $this->cofidispay_id,
		);
		foreach ( $meta as $key => $value ) {
			// if ( isset( $value['meta_key'] ) && in_array( $value['meta_key'], $mb_fields ) ) {
			if ( isset( $value['meta_key'] ) ) {
				foreach ( $mb_fields as $field ) {
					if ( strpos( $value['meta_key'], $field ) !== false && strpos( $value['meta_key'], $field ) === 0 ) { // Check if it starts with our field names
						unset( $meta[ $key ] );
						break;
					}
				}
			}
		}
		return $meta;
	}
	/* WooCommerce Subscriptions - Set renewal order on hold */
	public function multibanco_wcs_renewal_order_created( $renewal_order, $subscription ) {
		if ( ! is_object( $subscription ) ) {
			$subscription = wcs_get_subscription( $subscription );
		}
		if ( ! is_object( $renewal_order ) ) {
			$renewal_order = wc_get_order( $renewal_order );
		}
		if ( is_a( $renewal_order, 'WC_Order' ) && wcs_is_subscription( $subscription ) ) {
			$subscription_payment_method = $subscription->get_payment_method();
			if ( $subscription_payment_method == $this->multibanco_id ) { // Subscription was inially paid by Multibanco?
				if ( $this->multibanco_settings['support_woocommerce_subscriptions'] == 'yes' ) {
					// Set payment method
					$renewal_order->set_payment_method( $this->multibanco_id );
					// Forces MB Ref creation
					$renewal_order_id = $renewal_order->get_id();
					$ref              = $this->multibanco_get_ref( $renewal_order_id, true );
					if ( is_array( $ref ) ) {
						// Changes to "on hold" - Forces email sending
						$this->set_initial_order_status( $renewal_order, 'on-hold', 'Multibanco', '(WooCommerce Subscriptions)' );
					}
				}
			}
		}
		return $renewal_order;
	}

	/* Maybe change locale */
	public function maybe_change_locale( $order ) {
		if ( $this->wpml_active ) {
			// Just for WPML
			global $sitepress;
			if ( $sitepress ) {
				$lang = $order->get_meta( 'wpml_language' );
				if ( ! empty( $lang ) ) {
					$this->locale = $sitepress->get_locale( $lang );
				}
			}
		} else {
			// Store language != current user/admin language?
			if ( is_admin() ) {
				$current_user_lang = get_user_locale( wp_get_current_user() );
				if ( $current_user_lang != get_locale() ) {
					$this->locale = get_locale();
				}
			}
		}
		if ( ! empty( $this->locale ) ) {
			// Unload
			unload_textdomain( 'multibanco-ifthen-software-gateway-for-woocommerce' );
			add_filter( 'plugin_locale', array( $this, 'set_locale_for_emails' ), 10, 2 );
			load_plugin_textdomain( 'multibanco-ifthen-software-gateway-for-woocommerce' );
			remove_filter( 'plugin_locale', array( $this, 'set_locale_for_emails' ), 10, 2 );
		}
	}
	public function set_locale_for_emails( $locale, $domain ) {
		if ( $domain == 'multibanco-ifthen-software-gateway-for-woocommerce' && $this->locale ) {
			$locale = $this->locale;
		}
		return $locale;
	}

	/* WPML AJAX fix locale */
	public function wpml_ajax_fix_locale() {
		// If WPML is present and we're loading via ajax, let's try to fix the locale
		if ( $this->wpml_active ) {
			if ( function_exists( 'wpml_is_ajax' ) && wpml_is_ajax() ) {  // We check the function because we may be using Polylang
				if ( ICL_LANGUAGE_CODE != 'en' ) {
					add_filter( 'plugin_locale', array( $this, 'wpml_ajax_fix_locale_do_it' ), 1, 2 );
				}
			}
		}
	}
	/* This should NOT be needed! - Check with WooCommerce Multilingual team */
	public function wpml_ajax_fix_locale_do_it( $locale, $domain ) {
		if ( $domain == 'multibanco-ifthen-software-gateway-for-woocommerce' ) {
			global $sitepress;
			$locales = icl_get_languages_locales();
			if ( isset( $locales[ ICL_LANGUAGE_CODE ] ) ) {
				$locale = $locales[ ICL_LANGUAGE_CODE ];
			}
			// But if it's notes
			if ( $this->mb_ifthen_locale ) {
				$locale = $this->mb_ifthen_locale;
			}
		}
		return $locale;
	}
	/* Languages on Notes emails - We need to check if it's our order (Multibanco or MBWay) */
	public function woocommerce_new_customer_note_fix_wpml( $order_id ) {
		if ( is_array( $order_id ) ) {
			if ( isset( $order_id['order_id'] ) ) {
				$order_id = $order_id['order_id'];
			} else {
				return;
			}
		}
		if ( $this->wpml_active ) {
			$this->woocommerce_new_customer_note_fix_wpml_do_it( $order_id );
		}
	}
	public function woocommerce_new_customer_note_fix_wpml_do_it( $order_id ) {
		global $sitepress;
		$order = wc_get_order( $order_id );
		$lang  = $order->get_meta( 'wpml_language' );
		if ( ! empty( $lang ) && $lang != $sitepress->get_default_language() ) {
			$this->mb_ifthen_locale = $sitepress->get_locale( $lang ); // Set global to be used on wpml_ajax_fix_locale_do_it above
			add_filter( 'plugin_locale', array( $this, 'wpml_ajax_fix_locale_do_it' ), 1, 2 );
			load_plugin_textdomain( 'multibanco-ifthen-software-gateway-for-woocommerce' );
		}
	}

	/* Right sidebar on payment gateway settings */
	public function admin_right_bar() {
		?>
		<div id="wc_ifthen_rightbar">
			<h4><?php _e( 'Commercial information', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</h4>
			<p>
				<a href="https://ifthenpay.com/<?php echo esc_attr( $this->out_link_utm ); ?>" title="<?php echo esc_attr( sprintf( __( 'Please contact %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 'IfthenPay' ) ); ?>" target="_blank">
					<img src="<?php echo plugins_url( 'images/ifthenpay.svg', __FILE__ ); ?>" width="200"/>
				</a>
			</p>
			<h4><?php _e( 'Development and premium technical support', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</h4>
			<p>
				<a href="https://ptwooplugins.com<?php echo esc_attr( $this->out_link_utm ); ?>" title="<?php echo esc_attr( sprintf( __( 'Please contact %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 'PT Woo Plugins' ) ); ?>" target="_blank">
					<img src="<?php echo plugins_url( 'images/ptwooplugins.svg', __FILE__ ); ?>" width="200"/>
				</a>
			</p>
			<p>
				<h4><?php _e( 'Custom WordPress/WooCommerce development', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</h4>
				<a href="https://www.webdados.pt/contactos/<?php echo esc_attr( $this->out_link_utm ); ?>" title="<?php echo esc_attr( sprintf( __( 'Please contact %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 'Webdados' ) ); ?>" target="_blank">
					<img src="<?php echo plugins_url( 'images/webdados.svg', __FILE__ ); ?>" width="200"/>
				</a>
			</p>
			<h4><?php _e( 'Free technical support', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</h4>
			<p style="text-align: center">
				<a href="https://wordpress.org/support/plugin/multibanco-ifthen-software-gateway-for-woocommerce/" target="_blank">
					<?php _e( 'WordPress.org forum', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
				</a>
			</p>
			<h4><?php _e( 'Please rate our plugin', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</h4>
			<a href="https://wordpress.org/support/view/plugin-reviews/multibanco-ifthen-software-gateway-for-woocommerce?filter=5#postform" target="_blank" style="text-align: center;">
				<div class="star-rating"><div class="star star-full"></div><div class="star star-full"></div><div class="star star-full"></div><div class="star star-full"></div><div class="star star-full"></div></div>
			</a>
			<div class="clear"></div>
			<hr/>
			<h4><?php _e( 'Other premium plugins', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</h4>
			<ul id="wc_ifthen_premium_plugins">
				<?php
				// Custom fields
				$premium_plugins = array(
					array(
						'url'         => 'https://ptwooplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/',
						'title'       => __( 'Multibanco, MBWAY, Credit card and Payshop for WooCommerce – PRO add-on', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'short_title' => __( 'PRO add-on', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'image'       => 'multibanco.png',
					),
					array(
						'url'         => 'https://invoicewoo.com/',
						'title'       => __( 'Invoicing with InvoiceXpress for WooCommerce – Pro', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'short_title' => __( 'InvoiceXpress', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'image'       => 'invoicexpress-woocommerce.png',
					),
					array(
						'url'         => 'https://ptwooplugins.com/product/dpd-portugal-for-woocommerce/',
						'title'       => __( 'DPD (Chronopost/SEUR) Portugal for WooCommerce', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'short_title' => __( 'DPD Portugal', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'image'       => 'dpd-portugal.png',
					),
					array(
						'url'         => 'https://ptwooplugins.com/product/dpd-seur-geopost-pickup-and-lockers-network-for-woocommerce/',
						'title'       => __( 'DPD / SEUR / Geopost Pickup and Lockers network for WooCommerce', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'short_title' => __( 'DPD Pickup', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'image'       => 'dpd-pickup.png',
					),
					array(
						'url'         => 'https://ptwooplugins.com/product/portuguese-postcodes-for-woocommerce-technical-support/',
						'title'       => __( 'Portuguese Postcodes for WooCommerce', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'short_title' => __( 'Portuguese Postcodes', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'image'       => 'postcodes.png',
					),
					array(
						'url'         => 'https://www.webdados.pt/wordpress/plugins/feed-kuantokusta-para-woocommerce/',
						'title'       => __( 'Feed KuantoKusta for WooCommerce PRO add-on', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'short_title' => __( 'KuantoKusta', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'image'       => 'kuantokusta.png',
					),
					array(
						'url'         => 'https://ptwooplugins.com/product/simple-custom-fields-for-woocommerce-blocks-checkout/',
						'title'       => __( 'Simple Custom Fields for WooCommerce Blocks Checkout', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'short_title' => __( 'Blocks Checkout Custom Fields', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'image'       => 'woo-custom-fields.png',
					),
					array(
						'url'         => 'https://ptwooplugins.com/product/simple-woocommerce-order-approval/',
						'title'       => __( 'Simple WooCommerce Order Approval', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'short_title' => __( 'Order Approval', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'image'       => 'simple-woocommerce-order-approval-logo.png',
					),
					array(
						'url'         => 'https://ptwooplugins.com/product/shop-as-client-for-woocommerce-pro-add-on/',
						'title'       => __( 'Shop as Client for WooCommerce PRO add-on', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'short_title' => __( 'Shop as Client', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'image'       => 'shop-as-client.png',
					),
					array(
						'url'         => 'https://ptwooplugins.com/product/taxonomy-term-and-role-based-discounts-for-woocommerce-pro-add-on/',
						'title'       => __( 'Taxonomy/Term and Role based Discounts for WooCommerce', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'short_title' => __( 'Taxonomy based Discounts', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'image'       => 'taxonomy-discounts.png',
					),
				);
				foreach ( $premium_plugins as $premium_plugin ) {
					?>
					<li>
						<a href="<?php echo esc_url( $premium_plugin['url'] . $this->out_link_utm ); ?>" target="_blank" title="<?php echo esc_attr( $premium_plugin['title'] ); ?>">
							<img src="<?php echo esc_url( plugins_url( 'images/premium_plugins/' . $premium_plugin['image'], __FILE__ ) ); ?>" width="200" height="200" alt="<?php echo esc_attr( $premium_plugin['title'] ); ?>"/>
							<small><?php echo $premium_plugin['short_title']; ?></small>
						</a>
					</li>
					<?php
				}
				?>
			</ul>
			<div class="clear"></div>
		</div>
		<?php
	}

	/* Pro add-on banner */
	public function admin_pro_banner() {
		if ( ! $this->pro_add_on_active ) {
			?>
			<div class="wc_ifthen_pro_ad">
				<h4><?php _e( 'Want more features?', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</h4>
				<p>
					<a href="https://ptwooplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/<?php echo esc_attr( $this->out_link_utm ); ?>" target="_blank" style="font-weight: bold;">
						<?php _e( 'Get the PRO add-on of Multibanco, MB WAY, Credit card, Payshop and Cofidis Pay (IfthenPay) for WooCommerce', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
					</a>
				</p>
			</div>
			<div class="clear"></div>
			<?php
		}
	}

	/* MB WAY Ajax order status */
	public function mbway_ajax_order_status() {
		$order_id = wc_get_order_id_by_order_key( trim( $_POST['order_key'] ) );
		if ( intval( $order_id ) > 0 && intval( $_POST['order_id'] ) == intval( $order_id ) ) {
			$order = wc_get_order( intval( $order_id ) );
			echo json_encode(
				array( 'order_status' => $order->get_status() )
			);
		} else {
			echo json_encode(
				array( 'order_status' => '' )
			);
		}
		die();
	}

	/* Cofidis Pay Ajax order status */
	public function cofidispay_ajax_order_status() {
		$order_id = wc_get_order_id_by_order_key( trim( $_POST['order_key'] ) );
		if ( intval( $order_id ) > 0 && intval( $_POST['order_id'] ) == intval( $order_id ) ) {
			$order = wc_get_order( intval( $order_id ) );
			echo json_encode(
				array( 'order_status' => $order->get_status() )
			);
		} else {
			echo json_encode(
				array( 'order_status' => '' )
			);
		}
		die();
	}

	/* MB WAY - Request payment again */
	public function wp_ajax_mbway_ifthen_request_payment_again() {
		if ( wp_verify_nonce( $_REQUEST['nonce'], 'mbway_ifthen_request_payment_again' ) ) {
			if ( isset( $_REQUEST['order_id'] ) && intval( $_REQUEST['order_id'] ) > 0 && isset( $_REQUEST['order_id'] ) ) {
				$order = wc_get_order( intval( $_REQUEST['order_id'] ) );
				$mbway = new WC_MBWAY_IfThen_Webdados();
				$phone = isset( $_REQUEST['phone'] ) ? trim( sanitize_text_field( $_REQUEST['phone'] ) ) : '';
				if ( $mbway->webservice_set_pedido( $order->get_id(), $phone ) ) {
					echo json_encode(
						array(
							'status'  => 1,
							'message' => __( 'MB WAY Payment has been requested', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						)
					);
				} else {
					echo json_encode(
						array(
							'status' => 0,
							'error'  => __( 'Error contacting IfthenPay servers to create MB WAY Payment', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						)
					);
				}
			} else {
				echo json_encode(
					array(
						'status' => 0,
						'error'  => __( 'Invalid parameters', 'mbway_ifthen_request_payment_again' ),
					)
				);
			}
		} else {
			echo json_encode(
				array(
					'status' => 0,
					'error'  => __( 'Error', 'mbway_ifthen_request_payment_again' ),
				)
			);
		}
		wp_die();
	}


	/**
	 * Order needs payment - valid statuses
	 *
	 * @since 4.4.0
	 */
	public function woocommerce_valid_order_statuses_for_payment( $statuses, $order ) {
		if ( $this->order_has_ifthenpay_method( $order ) ) {
			$statuses = array_unique( array_merge( $statuses, $this->unpaid_statuses ) );
		}
		return $statuses;
	}

	/**
	 * Valid IfthenPay method
	 */
	public function order_has_ifthenpay_method( $order ) {
		return in_array( $order->get_payment_method(), array( $this->multibanco_id, $this->mbway_id, $this->creditcard_id, $this->payshop_id, $this->cofidispay_id ) );
	}


	/**
	 * Hide Pay button on orders list
	 *
	 * @since 4.4.0
	 */
	public function woocommerce_my_account_my_orders_actions( $actions, $order ) {
		if ( isset( $actions['pay'] ) ) {
			switch ( $order->get_payment_method() ) {
				case $this->multibanco_id:
					if ( apply_filters( 'multibanco_ifthen_hide_my_account_pay_button', false ) ) {
						unset( $actions['pay'] );
					}
					break;
				case $this->mbway_id:
					if ( apply_filters( 'mbway_ifthen_hide_my_account_pay_button', false ) ) {
						unset( $actions['pay'] );
					}
					break;
				case $this->creditcard_id:
					if ( apply_filters( 'creditcard_ifthen_hide_my_account_pay_button', false ) ) {
						unset( $actions['pay'] );
					}
				case $this->payshop_id:
					if ( apply_filters( 'payshop_ifthen_hide_my_account_pay_button', false ) ) {
						unset( $actions['pay'] );
					}
					break;
				case $this->cofidispay_id:
					if ( apply_filters( 'cofidispay_ifthen_hide_my_account_pay_button', false ) ) {
						unset( $actions['pay'] );
					}
					break;
			}
		}
		return $actions;

	}

	/**
	 * Activate callback via webservice
	 *
	 * @since 4.2.3
	 */
	public function callback_webservice( $bo_key, $ent, $subent, $secret_key, $callback_url ) {
		$result   = array(
			'success' => false,
			'message' => '',
		);
		$args     = array(
			'method'   => 'POST',
			'timeout'  => apply_filters( 'ifthen_callback_webservice_timeout', 30 ),
			'blocking' => true,
			'headers'  => array(
				'content-type' => 'application/json',
			),
			'body'     => json_encode(
				array(
					'chave'       => $bo_key,
					'entidade'    => $ent,
					'subentidade' => $subent,
					'apKey'       => $secret_key,
					'urlCb'       => $callback_url,
				)
			),
		);
		$response = wp_remote_post( $this->callback_webservice, $args );
		if ( is_wp_error( $response ) ) {
			$result['message'] = __( 'Unknown error 1', 'multibanco-ifthen-software-gateway-for-woocommerce' );
		} else {
			if ( isset( $response['response']['code'] ) ) {
				switch ( $response['response']['code'] ) {
					case 200:
						$result['success'] = true;
						break;
					default:
						$result['message'] = trim( $response['body'] );
						break;
				}
			} else {
				$result['message'] = __( 'Unknown error 2', 'multibanco-ifthen-software-gateway-for-woocommerce' );
			}
		}
		return $result;
	}

	/**
	 * Load admin scripts.
	 *
	 * @since 4.4.1
	 */
	public function order_needs_payment( $order ) {
		return $order->needs_payment() || $order->get_status() == 'on-hold' || $order->get_status() == 'pending';
	}

	/**
	 * Process MBWAY and CC refunds
	 *
	 * @since 7.0
	 */
	public function process_refund( $order_id, $amount, $reason, $method_id ) {
		$order = wc_get_order( $order_id );
		$this->debug_log( $method_id, '-- Processing refund - Order ' . $order->get_id(), 'notice' );
		/*
		if ( ! $this->can_refund_order( $order ) ) {
			$this->debug_log( $method_id, '-- Failed refund - Order ' . $order->get_id(), 'error', true, 'Cannot refund order' );
			return new WP_Error( 'error', __( 'Refund failed.', 'woocommerce' ) );
		}*/ // Only works at method level
		switch ( $method_id ) {
			case $this->mbway_id:
				$order_details  = WC_IfthenPay_Webdados()->get_mbway_order_details( $order->get_id() );
				$request_id     = trim( $order_details['id_pedido'] );
				$backoffice_key = trim( $this->mbway_settings['do_refunds_backoffice_key'] );
				break;
			case $this->creditcard_id:
				$order_details  = WC_IfthenPay_Webdados()->get_creditcard_order_details( $order->get_id() );
				$request_id     = trim( $order_details['request_id'] );
				$backoffice_key = trim( $this->creditcard_settings['do_refunds_backoffice_key'] );
				break;
		}
		$args         = array(
			'method'   => 'POST',
			'timeout'  => apply_filters( 'mbway_ifthen_webservice_timeout', 30 ), // Should use the method own filter, but this will do...
			'blocking' => true,
			'headers'  => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'     => array(
				'backofficekey' => $backoffice_key,
				'requestId'     => $request_id,
				'amount'        => (string) round( floatval( $amount ), 2 ),
			),
		);
		$args['body'] = json_encode( $args['body'] );
		$response     = wp_remote_post( $this->refunds_url, $args );
		if ( is_wp_error( $response ) ) {
			$debug_msg       = '- Error contacting the IfthenPay servers - Order ' . $order->get_id() . ' - ' . $response->get_error_message();
			$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
			$this->debug_log( $method_id, '-- ' . $debug_msg, 'error', true, $debug_msg_email );
			return new WP_Error( 'error', $response->get_error_message() );
		} else {
			if ( isset( $response['response']['code'] ) && intval( $response['response']['code'] ) == 200 && isset( $response['body'] ) && trim( $response['body'] ) != '' ) {
				if ( $body = json_decode( $response['body'] ) ) {
					if ( trim( $body->Code ) == '1' ) {
						return true;
					} else {
						$debug_msg       = '- Error from IfthenPay: ' . trim( $body->Message ) . ' - Order ' . $order->get_id();
						$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
						$this->debug_log( $method_id, $debug_msg, 'error', true, $debug_msg_email );
						return new WP_Error( 'error', $debug_msg . ' - ' . __( 'Do not contact the plugin support. You need to check with IfthenPay why this refund could not be issued.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) );
					}
				} else {
					$debug_msg       = '- Response body is not JSON - Order ' . $order->get_id();
					$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
					$this->debug_log( $method_id, $debug_msg, 'error', true, $debug_msg_email );
					return new WP_Error( 'error', $debug_msg );
				}
			} else {
				$debug_msg       = '- Error contacting the IfthenPay servers - Order ' . $order->get_id() . ' - Incorrect response code: ' . $response['response']['code'];
				$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
				$this->debug_log( $method_id, $debug_msg, 'error', true, $debug_msg_email );
				return new WP_Error( 'error', $debug_msg );
			}
		}
	}

	/**
	 * Check if Deposit has our payment method
	 *
	 * @since 8.4.0
	 */
	public function deposit_is_ifthenpay( $order, $method_id ) {
		if ( $this->wc_deposits_active ) {
			if ( $order->get_meta( '_wc_deposits_order_has_deposit' ) === 'yes' ) {
				$payment_schedule = $order->get_meta( '_wc_deposits_payment_schedule', true );
				// Deposit
				if ( is_array( $payment_schedule ) && isset( $payment_schedule['deposit'] ) && isset( $payment_schedule['deposit']['id'] ) && intval( $payment_schedule['deposit']['id'] ) > 0 ) {
					if ( $order_deposit = wc_get_order( intval( $payment_schedule['deposit']['id'] ) ) ) {
						if ( $method_id === $order_deposit->get_payment_method() ) {
							if ( $this->order_needs_payment( $order_deposit ) ) {
								return $order_deposit;
							}
						}
					}
				}
				// Future payments
				if ( is_array( $payment_schedule ) ) {
					$stop = false;
					$i    = 0;
					while ( $stop == false ) {
						if ( isset( $payment_schedule[ $i ] ) ) {
							if ( $order_deposit = wc_get_order( intval( $payment_schedule[ $i ]['id'] ) ) ) {
								if ( $method_id === $order_deposit->get_payment_method() ) {
									if ( $this->order_needs_payment( $order_deposit ) ) {
										return $order_deposit;
									}
								}
							}
							$i++;
						} else {
							$stop = true;
						}
					}
				}
			}
		}
		return false;
	}

	/**
	 * Helper to set initial order status for all the payment methods
	 *
	 * @since 9.5.0
	 */
	public function set_initial_order_status( $order, $status, $payment_method, $additional_message = '' ) {
		$note = sprintf(
			/* translators: %s: payment method */
			__( 'Awaiting %s payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
			$payment_method
		);
		if ( ! empty( trim( $additional_message ) ) ) {
			$note .= ' - ' . trim( $additional_message );
		}
		if ( $order->has_status( $status ) ) {
			// Just set a note
			$order->add_order_note( $note );
		} else {
			// Update status with note
			$order->update_status(
				$status,
				$note
			);
		}
	}

	/**
	 * Wrapper for wc_get_orders
	 * Maybe translate args for HPOS
	 * Disable Polylang filter and may be others in the future
	 *
	 * @since 8.9.0
	 */
	public function wc_get_orders( $args, $gateway_id ) {
		// Remove Polylang filter - https://wordpress.org/support/topic/encomenda-cancelada-mas-cobrada/
		if ( function_exists( 'PLL' ) ) {
			$this->debug_log_extra( $gateway_id, 'wc_get_orders - Disabling Polylang filter' );
			remove_action( 'parse_query', array( PLL(), 'parse_query' ), 6 );
		}
		// Get the orders
		$orders = wc_get_orders( $this->maybe_translate_order_query_args( $args ) );
		// Log last query
		global $wpdb;
		$this->debug_log_extra( $gateway_id, 'wc_get_orders - Last query: ' . $wpdb->last_query );
		// Re-instate Polylang filter - https://wordpress.org/support/topic/encomenda-cancelada-mas-cobrada/
		if ( function_exists( 'PLL' ) ) {
			$this->debug_log_extra( $gateway_id, 'wc_get_orders - Re-enabling Polylang filter' );
			add_action( 'parse_query', array( PLL(), 'parse_query' ), 6 );
		}
		return $orders;
	}

	/**
	 * Filter notify URLs
	 */
	public function filter_notify_urls() {
		$this->multibanco_notify_url = apply_filters( 'multibanco_ifthen_notify_url', $this->multibanco_notify_url );
		$this->mbway_notify_url      = apply_filters( 'mbway_ifthen_notify_url', $this->mbway_notify_url );
		$this->payshop_notify_url    = apply_filters( 'payshop_ifthen_notify_url', $this->payshop_notify_url );
		$this->creditcard_notify_url = apply_filters( 'credicard_ifthen_notify_url', $this->creditcard_notify_url );
		$this->cofidispay_notify_url = apply_filters( 'cofidispay_ifthen_notify_url', $this->cofidispay_notify_url );
	}

	/**
	 * Load admin scripts.
	 *
	 * @since 4.0.0
	 */
	public function admin_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( 'woocommerce_page_wc-settings' !== $screen_id ) {
			return;
		}
		if ( ! isset( $_GET['tab'] ) || ! isset( $_GET['section'] ) || $_GET['tab'] !== 'checkout' || ! strpos( $_GET['section'], 'ifthen_for_woocommerce' ) ) {
			return;
		}

		wp_enqueue_style( 'woocommerce_multibanco_ifthen_admin_css', plugins_url( 'assets/admin.css', __FILE__ ), array(), $this->get_version() . ( WP_DEBUG ? '.' . wp_rand( 0, 99999 ) : '' ) );

		wp_enqueue_script( 'woocommerce_multibanco_ifthen_admin_js', plugins_url( 'assets/admin.js', __FILE__ ), array( 'jquery' ), $this->get_version() . ( WP_DEBUG ? '.' . wp_rand( 0, 99999 ) : '' ), true );

		// Javascript variables
		$gateway             = str_replace( '_ifthen_for_woocommerce', '', $_GET['section'] );
		$callback_email_sent = get_option( $gateway . '_ifthen_for_woocommerce_callback_email_sent' );
		$callback_auto_open  = 0;
		if ( $callback_email_sent == 'no' && isset( $_GET['callback_warning'] ) && intval( $_GET['callback_warning'] ) == 1 ) {
			$callback_auto_open = 1;
		}
		wp_localize_script(
			'woocommerce_multibanco_ifthen_admin_js',
			'ifthenpay',
			array(
				'gateway'             => $gateway,
				'callback_confirm'    => strip_tags( __( 'Are you sure you want to ask IfthenPay to activate the “Callback”?', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ),
				'callback_bo_key'     => strip_tags( __( 'Please provide the IfthenPay backoffice key you got after signing the contract', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ),
				'callback_email_sent' => $callback_email_sent,
				'callback_auto_open'  => $callback_auto_open,
				'backoffice_key'      => apply_filters( 'ifthen_backoffice_key', '' ),
			)
		);
	}

	/* Admin notices to warn about old technology */
	function admin_notices() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		if ( apply_filters( 'ifthen_show_old_techonology_notice', true ) ) {
			if (
				isset( $_GET['page'] ) && trim( $_GET['page'] ) != '' && in_array(
					trim( $_GET['page'] ),
					array(
						'wc-settings',
						'wc-status',
						'wc-admin',
						'wc-reports',
						'wc-addons',
					)
				)
				||
				in_array(
					$screen_id,
					array(
						'dashboard',
						'plugins',
						'edit-shop_order',
						'edit-product',
						'woocommerce_page_wc-orders',
					)
				)
			) {
				$notices = array();
				// WordPress below 6.0
				if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
					$notices[] = sprintf(
						__( '%1$s - Your version: %2$s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'<strong>WordPress 6.0</strong>',
						sprintf(
							'<strong style="color:red;">%s</strong>',
							get_bloginfo( 'version' )
						)
					);
				}
				// WooCommerce below 6.0
				if ( version_compare( WC_VERSION, '6.0', '<' ) ) {
					$notices[] = sprintf(
						__( '%1$s - Your version: %2$s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'<strong>WooCommerce 6.0</strong>',
						sprintf(
							'<strong style="color:red;">%s</strong>',
							WC_VERSION
						)
					)
					.
					' - <strong>' . __( 'Support for WooCommerce &lt; 5.4 will end VERY SOON!', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong>';
				}
				// PHP below 7.4
				if ( version_compare( phpversion(), '7.4', '<' ) ) {
					$notices[] = sprintf(
						__( '%1$s - Your version: %2$s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'<strong>PHP 7.4</strong>',
						sprintf(
							'<strong style="color:red;">%s</strong>',
							phpversion()
						)
					);
				}
				if ( count( $notices ) > 0 ) {
					?>
					<div class="notice notice-error notice-alt">
						<p><strong><?php _e( 'Multibanco, MB WAY, Credit card, Payshop and Cofidis Pay (IfthenPay) for WooCommerce', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong></p>
						<p>
							<?php _e( 'We are working on implementing the latest and safest technology, so you will soon need:', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
						</p>
						<ul>
							<?php foreach ( $notices as $notice ) { ?>
								<li>- <?php echo $notice; ?></li>
							<?php } ?>
						</ul>
					</div>
					<?php
				}
			}
		}
	}

}
