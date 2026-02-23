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
	public $id                = 'ifthen_for_woocommerce'; // Plugin ID
	public $multibanco_id     = 'multibanco_ifthen_for_woocommerce';
	public $mbway_id          = 'mbway_ifthen_for_woocommerce';
	public $payshop_id        = 'payshop_ifthen_for_woocommerce';
	public $creditcard_id     = 'creditcard_ifthen_for_woocommerce';
	public $cofidispay_id     = 'cofidispay_ifthen_for_woocommerce';
	public $gateway_ifthen_id = 'gateway_ifthen_ifthen_for_woocommerce';

	/* Debug */
	public $log = null;

	/* Internal variables */
	public $pro_add_on_active         = false;
	public $wpml_active               = false;
	public $wpml_translation_info     = '';
	public $polylang_active           = false;
	public $polylang_current_language = false;
	public $wc_deposits_active        = false;
	public $wc_subscriptions_active   = false;
	public $wc_blocks_active          = false;
	public $mb_ifthen_locale          = null;
	public $out_link_utm              = '';
	public $is_pay_form               = false;
	public $callback_email            = 'callback@ifthenpay.com';
	public $callback_webservice       = 'https://www.ifthenpay.com/api/endpoint/callback/activation';
	public $unpaid_statuses           = array( 'on-hold', 'pending', 'partially-paid', 'failed' );
	public $hpos_enabled              = false;
	public $refunds_url               = 'https://ifthenpay.com/api/endpoint/payments/refund';
	private $gateways_loaded          = false;
	private $locale                   = '';

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
	public $multibanco_min_value                   = 0.01;
	public $multibanco_max_value                   = 99999.99;
	public $multibanco_banner_email                = '';
	public $multibanco_banner                      = '';
	public $multibanco_icon                        = '';
	public $multibanco_api_mode_available          = true;
	public $multibanco_api_mode_enabled            = false;
	public $multibanco_api_url                     = 'https://api.ifthenpay.com/multibanco/reference/init';


	/* Internal variables - For MB WAY */
	public $mbway_settings               = null;
	public $mbway_notify_url             = '';
	public $mbway_minutes                = 4;
	public $mbway_multiplier_new_payment = 1.2;
	public $mbway_min_value              = 0.01;
	public $mbway_max_value              = 99999.99;
	public $mbway_banner_email           = '';
	public $mbway_banner                 = '';
	public $mbway_icon                   = '';
	public $mbway_api_url                = 'https://api.ifthenpay.com/spg/payment/mbway';


	/* Internal variables - For Payshop */
	public $payshop_settings                = null;
	public $payshop_notify_url              = '';
	public $payshop_action_deposits_set     = false;
	public $payshop_deposits_already_forced = false;
	public $payshop_min_value               = 0.01;
	public $payshop_max_value               = 4000;
	public $payshop_banner_email            = '';
	public $payshop_banner                  = '';
	public $payshop_icon                    = '';


	/* Internal variables - For Credit card */
	public $creditcard_settings     = null;
	public $creditcard_notify_url   = '';
	public $creditcard_return_url   = '';
	public $creditcard_min_value    = 0.01; /* No limit in theory */
	public $creditcard_max_value    = 99999.99; /* No limit in theory */
	public $creditcard_banner_email = ''; /* Needed ? */
	public $creditcard_banner       = ''; /* Needed ? */
	public $creditcard_icon         = '';


	/* Internal variables - For Cofidis Pay */
	public $cofidispay_settings     = null;
	public $cofidispay_notify_url   = '';
	public $cofidispay_return_url   = '';
	public $cofidispay_min_value    = 0.01; /* No limit in theory */
	public $cofidispay_max_value    = 99999.99; /* No limit in theory */
	public $cofidispay_banner_email = ''; /* Needed ? */
	public $cofidispay_banner       = ''; /* Needed ? */
	public $cofidispay_icon         = '';


	/* Internal variables - For Apple and Google Pay */
	public $gateway_ifthen_settings     = null;
	public $gateway_ifthen_notify_url   = '';
	public $gateway_ifthen_return_url   = '';
	public $gateway_ifthen_min_value    = 0.01; /* No limit in theory */
	public $gateway_ifthen_max_value    = 99999.99; /* No limit in theory */
	public $gateway_ifthen_banner_email = ''; /* Needed ? */
	public $gateway_ifthen_banner       = ''; /* Needed ? */
	public $gateway_ifthen_icon         = '';

	/* Single instance */
	protected static $_instance = null;

	/**
	 * Constructor
	 *
	 * @param string $version The plugin version.
	 */
	public function __construct( $version ) {
		// Check version
		$this->version           = $version;
		$this->pro_add_on_active = function_exists( 'WC_IfthenPay_Pro' );
		$this->wpml_active       = function_exists( 'icl_object_id' ) && function_exists( 'icl_register_string' );
		if ( $this->wpml_active ) {
			// Since WordPress 6.7 avoid textdomain warnings
			add_action(
				'init',
				function () {
					$this->wpml_translation_info = sprintf(
						/* translators: %1$s: link opening tag, %2$s: link closing tag */
						esc_html__( 'You should translate this string in %1$sWPML - String Translation%2$s after saving the settings', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'<a href="admin.php?page=wpml-string-translation%2Fmenu%2Fstring-translation.php">',
						'</a>'
					);
				}
			);
		}
		// Polylang?
		$this->polylang_active = function_exists( 'PLL' );
		if ( $this->polylang_active ) {
			$this->polylang_current_language = $this->polylang_active ? pll_current_language() : '';
			if ( empty( $this->polylang_current_language ) && function_exists( 'pll_default_language' ) ) {
				$this->polylang_current_language = pll_default_language();
			}
		}
		$this->wc_deposits_active      = function_exists( 'wc_deposits_woocommerce_is_active' ) || function_exists( '\Webtomizer\WCDP\wc_deposits_woocommerce_is_active' );
		$this->wc_subscriptions_active = function_exists( 'wcs_get_subscription' );
		$this->wc_blocks_active        = class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' );
		$this->out_link_utm            = '?utm_source=' . rawurlencode( esc_url( home_url( '/' ) ) ) . '&amp;utm_medium=link&amp;utm_campaign=mb_ifthen_plugin';
		if ( wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ) {
			$this->hpos_enabled = true;
		}
		// Multibanco
		$this->multibanco_settings         = get_option( 'woocommerce_multibanco_ifthen_for_woocommerce_settings', '' );
		$this->multibanco_notify_url       = (
			get_option( 'permalink_structure' ) === ''
			?
			$this->home_url( '/?wc-api=WC_Multibanco_IfThen_Webdados&chave=[CHAVE_ANTI_PHISHING]&entidade=[ENTIDADE]&referencia=[REFERENCIA]&valor=[VALOR]&datahorapag=[DATA_HORA_PAGAMENTO]&terminal=[TERMINAL]&ifthenpayfee=[FEE]' )
			:
			$this->home_url( '/wc-api/WC_Multibanco_IfThen_Webdados/?chave=[CHAVE_ANTI_PHISHING]&entidade=[ENTIDADE]&referencia=[REFERENCIA]&valor=[VALOR]&datahorapag=[DATA_HORA_PAGAMENTO]&terminal=[TERMINAL]&ifthenpayfee=[FEE]' )
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
			$this->home_url( '/?wc-api=WC_MBWAY_IfThen_Webdados&chave=[CHAVE_ANTI_PHISHING]&referencia=[REFERENCIA]&idpedido=[ID_TRANSACAO]&valor=[VALOR]&datahorapag=[DATA_HORA_PAGAMENTO]&estado=[ESTADO]&ifthenpayfee=[FEE]' )
			:
			$this->home_url( '/wc-api/WC_MBWAY_IfThen_Webdados/?chave=[CHAVE_ANTI_PHISHING]&referencia=[REFERENCIA]&idpedido=[ID_TRANSACAO]&valor=[VALOR]&datahorapag=[DATA_HORA_PAGAMENTO]&estado=[ESTADO]&ifthenpayfee=[FEE]' )
		);
		// Payshop
		$this->payshop_settings   = get_option( 'woocommerce_payshop_ifthen_for_woocommerce_settings', '' );
		$this->payshop_notify_url = (
			get_option( 'permalink_structure' ) === ''
			?
			$this->home_url( '/?wc-api=WC_Payshop_IfThen_Webdados&chave=[CHAVE_ANTI_PHISHING]&id_cliente=[ID_CLIENTE]&id_transacao=[ID_TRANSACAO]&referencia=[REFERENCIA]&valor=[VALOR]&estado=[ESTADO]&datahorapag=[DATA_HORA_PAGAMENTO]&ifthenpayfee=[FEE]' )
			:
			$this->home_url( '/wc-api/WC_Payshop_IfThen_Webdados/?chave=[CHAVE_ANTI_PHISHING]&id_cliente=[ID_CLIENTE]&id_transacao=[ID_TRANSACAO]&referencia=[REFERENCIA]&valor=[VALOR]&estado=[ESTADO]&datahorapag=[DATA_HORA_PAGAMENTO]&ifthenpayfee=[FEE]' )
		);
		// Credit card
		$this->creditcard_settings   = get_option( 'woocommerce_creditcard_ifthen_for_woocommerce_settings', '' );
		$this->creditcard_notify_url = ( // Fallback callback - Just in case
			get_option( 'permalink_structure' ) === ''
			?
			$this->home_url( '/?wc-api=WC_CreditCard_IfThen_Webdados&key=[ANTI_PHISHING_KEY]&id=[ID]&amount=[AMOUNT]&payment_datetime=[PAYMENT_DATETIME]&status=[STATUS]&request_id=[REQUEST_ID]' )
			:
			$this->home_url( '/wc-api/WC_CreditCard_IfThen_Webdados/?key=[ANTI_PHISHING_KEY]&id=[ID]&amount=[AMOUNT]&payment_datetime=[PAYMENT_DATETIME]&status=[STATUS]&request_id=[REQUEST_ID]' )
		);
		$this->creditcard_return_url = ( // Classic return URL - Should take care of everything
			get_option( 'permalink_structure' ) === ''
			?
			$this->home_url( '/?wc-api=WC_CreditCardReturn_IfThen_Webdados' )
			:
			$this->home_url( '/wc-api/WC_CreditCardReturn_IfThen_Webdados/' )
		);
		// Cofidis Pay
		$this->cofidispay_settings   = get_option( 'woocommerce_cofidispay_ifthen_for_woocommerce_settings', '' );
		$this->cofidispay_notify_url = (
			get_option( 'permalink_structure' ) === ''
			?
			$this->home_url( '/?wc-api=WC_CofidisPay_IfThen_Webdados&key=[ANTI_PHISHING_KEY]&orderId=[ORDER_ID]&amount=[AMOUNT]&requestId=[REQUEST_ID]' )
			:
			$this->home_url( '/wc-api/WC_CofidisPay_IfThen_Webdados/?key=[ANTI_PHISHING_KEY]&orderId=[ORDER_ID]&amount=[AMOUNT]&requestId=[REQUEST_ID]' )
		);
		$this->cofidispay_return_url = (
			get_option( 'permalink_structure' ) === ''
			?
			$this->home_url( '/?wc-api=WC_CofidisPayReturn_IfThen_Webdados' )
			:
			$this->home_url( '/wc-api/WC_CofidisPayReturn_IfThen_Webdados/' )
		);
		// Gateway
		$this->gateway_ifthen_settings   = get_option( 'woocommerce_gateway_ifthen_ifthen_for_woocommerce_settings', '' );
		$this->gateway_ifthen_notify_url = (
			get_option( 'permalink_structure' ) === ''
			?
			$this->home_url( '/?wc-api=WC_Gateway_IfThen_Webdados&key=[ANTI_PHISHING_KEY]&id=[ID]&amount=[AMOUNT]&payment_datetime=[PAYMENT_DATETIME]&status=[STATUS]&payment_method=[PAYMENT_METHOD]&payment_method_key=[PAYMENT_METHOD_KEY]&request_id=[REQUEST_ID]&ifthenpayfee=[FEE]' )
			:
			$this->home_url( '/wc-api/WC_Gateway_IfThen_Webdados/?key=[ANTI_PHISHING_KEY]&id=[ID]&amount=[AMOUNT]&payment_datetime=[PAYMENT_DATETIME]&status=[STATUS]&payment_method=[PAYMENT_METHOD]&payment_method_key=[PAYMENT_METHOD_KEY]&request_id=[REQUEST_ID]&ifthenpayfee=[FEE]' )
		);
		$this->gateway_ifthen_return_url = (
			get_option( 'permalink_structure' ) === ''
			?
			$this->home_url( '/?wc-api=WC_GatewayReturn_IfThen_Webdados' )
			:
			$this->home_url( '/wc-api/WC_GatewayReturn_IfThen_Webdados/' )
		);
		// Upgrade
		$this->upgrade();
		// Hooks
		$this->init_hooks();
	}

	/**
	 * Ensures only one instance of our plugin is loaded or can be loaded
	 *
	 * @param string $version The plugin version.
	 * @return WC_IfthenPay_Webdados
	 */
	public static function instance( $version ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $version );
		}
		return self::$_instance;
	}

	/**
	 * Upgrades (if needed)
	 */
	private function upgrade() {
		$db_version = get_option( $this->id . '_version', '' );
		if ( version_compare( $db_version, $this->version, '<' ) ) {
			$this->debug_log( $this->id, 'Upgrade from ' . $db_version . ' to ' . $this->version . ' started' );
			// Update routines when upgrading to 11.3 or above - Remove old cron
			if ( version_compare( $db_version, '11.3', '<' ) ) {
				wp_clear_scheduled_hook( 'wc_ifthen_hourly_cron' );
			}
			// Update routines when upgrading to 11.3.2 or above - Clear Action Scheduler errors
			if ( version_compare( $db_version, '11.3.2', '<' ) ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				global $wpdb;
				// Get action IDs for wc_ifthen_hourly_cron with failed status
				$failed_action_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT action_id FROM {$wpdb->prefix}actionscheduler_actions 
						WHERE hook = %s AND status = 'failed'",
						'wc_ifthen_hourly_cron'
					)
				);
				if ( ! empty( $failed_action_ids ) ) {
					// Delete the failed actions
					$wpdb->query(
						$wpdb->prepare(
							"DELETE FROM {$wpdb->prefix}actionscheduler_actions 
							WHERE hook = %s AND status = 'failed'",
							'wc_ifthen_hourly_cron'
						)
					);
					// Delete associated logs
					$ids_placeholder = implode( ',', array_fill( 0, count( $failed_action_ids ), '%d' ) );
					$wpdb->query(
						$wpdb->prepare(
							"DELETE FROM {$wpdb->prefix}actionscheduler_logs 
							WHERE action_id IN ($ids_placeholder)", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							$failed_action_ids
						)
					);
					$this->debug_log( $this->id, 'Cleared ' . count( $failed_action_ids ) . ' failed Action Scheduler entries for wc_ifthen_hourly_cron' );
				} else {
					$this->debug_log( $this->id, 'No failed Action Scheduler entries found for wc_ifthen_hourly_cron' );
				}
				// phpcs:enable
			}
			update_option( $this->id . '_version', $this->version );
			$this->debug_log( $this->id, 'Upgrade from ' . $db_version . ' to ' . $this->version . ' finished' );
		}
	}

	/**
	 * Intialize hooks
	 */
	private function init_hooks() {
		add_filter( 'woocommerce_payment_gateways', array( $this, 'woocommerce_add_payment_gateways' ) );
		add_action( 'woocommerce_blocks_loaded', array( $this, 'woocommerce_add_payment_gateways_woocommerce_blocks' ) ); // WooCommerce Blocks
		add_action( 'add_meta_boxes', array( $this, 'multibanco_order_metabox' ) );
		add_filter( 'woocommerce_shop_order_search_fields', array( $this, 'shop_order_search' ) );
		add_filter( 'woocommerce_order_table_search_query_meta_keys', array( $this, 'shop_order_search' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'multibanco_woocommerce_checkout_update_order_meta' ) ); // Frontend
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
		add_action( 'wp_ajax_nopriv_wc_mbway_ifthen_order_status', array( $this, 'mbway_ajax_order_status' ) );
		add_action( 'wp_ajax_wc_cofidispay_ifthenpay_order_status', array( $this, 'cofidispay_ajax_order_status' ) );
		add_action( 'wp_ajax_nopriv_wc_cofidispay_ifthenpay_order_status', array( $this, 'cofidispay_ajax_order_status' ) );
		add_action( 'wp_ajax_wc_gateway_ifthenpay_order_status', array( $this, 'gatewayifthenpay_ajax_order_status' ) );
		add_action( 'wp_ajax_nopriv_wc_gateway_ifthenpay_order_status', array( $this, 'gatewayifthenpay_ajax_order_status' ) );
		// Request MB WAY payment again
		add_action( 'wp_ajax_mbway_ifthen_request_payment_again', array( $this, 'wp_ajax_mbway_ifthen_request_payment_again' ) );
		// Order value changed?
		add_action( 'woocommerce_order_item_add_action_buttons', array( $this, 'multibanco_maybe_value_changed' ) );
		// Dismiss new method notices
		add_action( 'wp_ajax_ifthenpay_dismiss_newmethod_notice', array( $this, 'dismiss_newmethod_notice_handler' ) );
		// Admin notices to warn about old technology
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		// Order needs payment for all our methods
		add_action(
			'init',
			function () {
				$this->unpaid_statuses = apply_filters( 'ifthen_unpaid_statuses', $this->unpaid_statuses );
			}
		);
		add_filter( 'woocommerce_valid_order_statuses_for_payment', array( $this, 'woocommerce_valid_order_statuses_for_payment' ), PHP_INT_MAX, 2 );
		// Create Action Scheduler recurring action instead of WP Cron
		add_action(
			'init',
			function () {
				if ( ! as_next_scheduled_action( 'wc_ifthen_hourly_cron' ) ) {
					as_schedule_recurring_action( time(), HOUR_IN_SECONDS, 'wc_ifthen_hourly_cron', array(), 'wc-ifthen' );
				}
			}
		);
		add_action( 'wc_ifthen_hourly_cron', array( $this, 'action_scheduler_do_nothing' ) );
		// Cancel orders with expired references - Multibanco (after_setup_theme so it runs after theme's functions.php file)
		add_action(
			'after_setup_theme',
			function () {
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
			function () {
				$this->is_pay_form = true;
			}
		);
		// Remove pay button for our payment methods
		add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'woocommerce_my_account_my_orders_actions' ), 10, 2 );
		// REST API - Not needed: https://wordpress.org/support/topic/obter-referencia-multibanco-atraves-da-api/#post-15815758
		// add_filter( 'woocommerce_api_order_response', array( $this, 'woocommerce_api_order_response', 11, 2 );
		// Allow filtering of notify URLs
		add_action( 'plugins_loaded', array( $this, 'filter_notify_urls' ), 20 );
	}

	/**
	 * Get version
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get the site home URL, with optional Polylang language-aware handling.
	 *
	 * Uses `pll_home_url()` when Polylang is active and the fix filter allows it,
	 * otherwise falls back to WordPress `home_url()`.
	 *
	 * @param string $path Optional path to append to the home URL.
	 * @return string Home URL with the optional path appended.
	 */
	private function home_url( $path = '' ) {
		// Polylang home URL - Maybe not compatible with Multisite, but we can only do so much
		if (
			$this->polylang_active
			&&
			( ! empty( $this->polylang_current_language ) )
			&&
			apply_filters( 'ifthen_fix_polylang_home_url', true )
		) {
			$url = pll_home_url( $this->polylang_current_language );
			if ( $path && is_string( $path ) ) {
				// Avoid double slashes by trimming / on both sides and adding a single one in between
				$url = rtrim( $url, '/' ) . '/' . ltrim( $path, '/' );
			}
			return $url;
		}
		// Default
		return home_url( $path );
	}

	/**
	 * Set payment method images
	 */
	public function set_images() {
		$this->multibanco_banner_email = plugins_url( 'images/multibanco_banner.png', __FILE__ );
		$this->multibanco_banner       = plugins_url( 'images/multibanco_banner.svg', __FILE__ );
		$this->multibanco_icon         = plugins_url( 'images/multibanco_icon.svg', __FILE__ );
		$this->multibanco_icon_path    = plugin_dir_path( __FILE__ ) . 'images/multibanco_icon.svg';

		$this->mbway_banner_email = plugins_url( 'images/mbway_banner.png', __FILE__ );
		$this->mbway_banner       = plugins_url( 'images/mbway_banner.svg', __FILE__ );
		$this->mbway_icon         = plugins_url( 'images/mbway_icon.svg', __FILE__ );
		$this->mbway_icon_path    = plugin_dir_path( __FILE__ ) . 'images/mbway_icon.svg';

		$this->payshop_banner_email = plugins_url( 'images/payshop_banner.png', __FILE__ );
		$this->payshop_banner       = plugins_url( 'images/payshop_banner.svg', __FILE__ );
		$this->payshop_icon         = plugins_url( 'images/payshop_icon.svg', __FILE__ );
		$this->payshop_icon_path    = plugin_dir_path( __FILE__ ) . 'images/payshop_icon.svg';

		$this->creditcard_banner_email = plugins_url( 'images/creditcard_banner_and_icon.png', __FILE__ );
		$this->creditcard_banner       = plugins_url( 'images/creditcard_banner_and_icon.svg', __FILE__ );
		$this->creditcard_icon         = plugins_url( 'images/creditcard_banner_and_icon.svg', __FILE__ );
		$this->creditcard_icon_path    = plugin_dir_path( __FILE__ ) . 'images/creditcard_banner_and_icon.svg';

		$this->cofidispay_banner_email = plugins_url( 'images/cofidispay_banner.png', __FILE__ );
		$this->cofidispay_banner       = plugins_url( 'images/cofidispay_banner.svg', __FILE__ );
		$this->cofidispay_icon         = plugins_url( 'images/cofidispay_icon.svg', __FILE__ );
		$this->cofidispay_icon_path    = plugin_dir_path( __FILE__ ) . 'images/cofidispay_icon.svg';

		$this->gateway_ifthen_banner_email = plugins_url( 'images/gateway_ifthen_banner.png', __FILE__ );
		$this->gateway_ifthen_banner       = plugins_url( 'images/gateway_ifthen_banner.svg', __FILE__ );
		$this->gateway_ifthen_icon         = plugins_url( 'images/gateway_ifthen_icon.svg', __FILE__ );
		$this->gateway_ifthen_icon_path    = plugin_dir_path( __FILE__ ) . 'images/gateway_ifthen_icon.svg';
	}

	/**
	 * Add settings link to plugin actions
	 *
	 * @param array $links The current links.
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$action_links = array();
		if ( ! $this->pro_add_on_active ) {
			$action_links['gopro'] = '<a href="https://nakedcatplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/' . esc_attr( $this->out_link_utm ) . '" target="_blank" style="font-weight: bold;">' . esc_html__( 'Get the PRO add-on', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</a>';
		}
		$settings_links           = esc_html__( 'Settings:', 'multibanco-ifthen-software-gateway-for-woocommerce' );
		$settings_links          .= ' <a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=' . $this->multibanco_id . '">Multibanco</a>';
		$settings_links          .= ' - <a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=' . $this->mbway_id . '">MB WAY</a>';
		$settings_links          .= ' - <a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=' . $this->creditcard_id . '">' . esc_html__( 'Credit card', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</a>';
		$settings_links          .= ' - <a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=' . $this->payshop_id . '">Payshop</a>';
		$settings_links          .= ' - <a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=' . $this->gateway_ifthen_id . '">' . esc_html__( 'ifthenpay Gateway', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</a>';
		$settings_links          .= ' - <a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=' . $this->cofidispay_id . '">Cofidis Pay</a>';
		$action_links['settings'] = $settings_links;
		$action_links['support']  = '<a href="https://wordpress.org/support/plugin/multibanco-ifthen-software-gateway-for-woocommerce/" target="_blank">' . esc_html__( 'Technical support', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</a>';

		return array_merge( $action_links, $links );
	}

	/**
	 * Add our payment methods to WooCommerce
	 *
	 * @param array $methods The current payment methods.
	 * @return array
	 */
	public function woocommerce_add_payment_gateways( $methods ) {
		$our_gateways = array(
			'WC_Multibanco_IfThen_Webdados',
			'WC_MBWAY_IfThen_Webdados',
			'WC_CreditCard_IfThen_Webdados',
			'WC_Gateway_IfThen_Webdados',
			'WC_Payshop_IfThen_Webdados',
			'WC_CofidisPay_IfThen_Webdados',
		);
		// Avoid loading gateways more than once
		foreach ( $our_gateways as $our_gateway ) {
			if ( ! in_array( $our_gateway, $methods, true ) ) {
				$methods[] = $our_gateway;
			}
		}
		return $methods;
	}

	/**
	 * Add our payment methods to WooCommerce Blocks Checkout
	 */
	public function woocommerce_add_payment_gateways_woocommerce_blocks() {
		if ( WC_IfthenPay_Webdados()->wc_blocks_active ) {
			// Multibanco
			require_once 'woocommerce-blocks/MultibancoIfthenPay.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new \Automattic\WooCommerce\Blocks\Payments\Integrations\MultibancoIfthenPay() );
				}
			);
			// MB WAY
			require_once 'woocommerce-blocks/MBWayIfthenPay.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new \Automattic\WooCommerce\Blocks\Payments\Integrations\MBWayIfthenPay() );
				}
			);
			// Credit card
			require_once 'woocommerce-blocks/CreditCardIfthenPay.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new \Automattic\WooCommerce\Blocks\Payments\Integrations\CreditCardIfthenPay() );
				}
			);
			// Payshop
			require_once 'woocommerce-blocks/PayshopIfthenPay.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new \Automattic\WooCommerce\Blocks\Payments\Integrations\PayshopIfthenPay() );
				}
			);
			// Cofidis Pay
			require_once 'woocommerce-blocks/CofidisPayIfthenPay.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new \Automattic\WooCommerce\Blocks\Payments\Integrations\CofidisPayIfthenPay() );
				}
			);
			// ifthenpay Gateway
			require_once 'woocommerce-blocks/GatewayIfthenPay.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new \Automattic\WooCommerce\Blocks\Payments\Integrations\GatewayIfthenPay() );
				}
			);
		}
	}

	/**
	 * Debug / Log
	 *
	 * @param string $gateway_id    The payment gateway ID or the main plugin ID.
	 * @param string $message       The message to debug.
	 * @param string $level         The debug level.
	 * @param bool   $debug_email   Email address, if to send to email.
	 * @param string $email_message Email message.
	 */
	public function debug_log( $gateway_id, $message, $level = 'debug', $debug_email = '', $email_message = '' ) {
		if ( ! $this->log ) {
			$this->log = wc_get_logger(); // Init log
		}
		$log_message = $message;
		if ( ! empty( $email_message ) ) {
			$log_message .= ' - Details:
' . trim( $email_message );
		}
		$this->log->$level( $log_message, array( 'source' => $gateway_id ) );
		if ( ! empty( $debug_email ) ) {
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


	/**
	 * Debug / Log - Extra
	 *
	 * @param string $gateway_id    The payment gateway ID.
	 * @param string $message       The message to debug.
	 * @param string $level         The debug level.
	 * @param bool   $debug_email   Email address, if to send to email.
	 * @param string $email_message Email message.
	 */
	public function debug_log_extra( $gateway_id, $message, $level = 'debug', $debug_email = '', $email_message = '' ) {
		if ( apply_filters( 'ifthen_debug_log_extra', false ) ) {
			$url = WC_IfthenPay_Webdados()->get_request_uri() ? WC_IfthenPay_Webdados()->get_request_uri() : 'Unknown request URI';
			$this->debug_log( $gateway_id, 'EXTRA (' . $url . ') - ' . $message, $level, $debug_email, $email_message );
		}
	}

	/**
	 * Get Multibanco reference mode
	 */
	public function get_multibanco_ref_mode() {
		return apply_filters( 'multibanco_ifthen_ref_mode', $this->multibanco_ref_mode );
	}

	/**
	 * Get Multibanco reference seed
	 *
	 * @param boolean $first If it's the first.
	 * @return int
	 */
	public function get_multibanco_ref_seed( $first = true ) {
		if ( $this->get_multibanco_ref_mode() === 'incremental_expire' ) {
			return $this->get_multibanco_incremental_expire_next_seed( $first );
		}
		return wp_rand( 0, 9999 );
	}

	/**
	 * Get Multibanco incremental reference seed
	 *
	 * @param boolean $first If it's the first.
	 * @return int
	 */
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
			++$this->multibanco_last_incremental_expire_ref;
		}
		return intval( $this->multibanco_last_incremental_expire_ref ) + 1;
	}

	/**
	 * Format MB reference - We keep it because someone may be using it externally
	 *
	 * @param  string $ref Multibanco reference.
	 * @return string
	 */
	public function format_multibanco_ref( $ref ) {
		return apply_filters( 'multibanco_ifthen_format_ref', trim( chunk_split( trim( $ref ), 3, '&nbsp;' ) ) );
	}

	/**
	 * Format Payshop reference
	 *
	 * @param  string $ref Multibanco reference.
	 * @return string
	 */
	public function format_payshop_ref( $ref ) {
		return apply_filters( 'payshop_ifthen_format_ref', trim( chunk_split( trim( $ref ), 3, '&nbsp;' ) ) );
	}

	/**
	 * Disable payment gateway if not €
	 *
	 * @param array  $available_gateways The available payment gateways.
	 * @param string $gateway_id         Our payment gateway ID.
	 * @return array
	 */
	public function disable_if_currency_not_euro( $available_gateways, $gateway_id ) {
		if ( isset( $available_gateways[ $gateway_id ] ) ) {
			if ( trim( get_woocommerce_currency() ) !== 'EUR' ) {
				unset( $available_gateways[ $gateway_id ] );
			}
		}
		return $available_gateways;
	}

	/**
	 * Get Customer billing country
	 */
	public function get_customer_billing_country() {
		return trim( WC()->customer->get_billing_country() );
	}

	/**
	 * Get Customer shipping country
	 */
	public function get_customer_shipping_country() {
		return trim( WC()->customer->get_shipping_country() );
	}

	/**
	 * Disable payment gateway unless customer is from Portugal
	 *
	 * @param array  $available_gateways The available payment gateways.
	 * @param string $gateway_id         Our payment gateway ID.
	 * @return array
	 */
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

	/**
	 * Disable payment gateway above or below certain order amount
	 *
	 * @param array  $available_gateways The available payment gateways.
	 * @param string $gateway_id         Our payment gateway ID.
	 * @param mixed  $default_only_above Upper limit, if set.
	 * @param mixed  $default_only_below Lower limit, if set.
	 * @return array
	 */
	public function disable_only_above_or_below( $available_gateways, $gateway_id, $default_only_above = null, $default_only_below = null ) {
		$value_to_pay = null;
		// Order total or cart total?
		$pay_slug = get_option( 'woocommerce_checkout_pay_endpoint', 'order-pay' );
		$order_id = absint( get_query_var( $pay_slug ) );
		if ( $order_id > 0 ) {
			// Pay screen on My Account
			$order        = wc_get_order( $order_id );
			$value_to_pay = $this->get_order_total_to_pay( $order );
		} elseif ( ! is_null( WC()->cart ) ) {
			// Checkout?
			$value_to_pay = WC()->cart->total; // We're not checking if we're paying just a deposit...
		} else { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedElse
			// No cart? Where are we? We shouldn't unset our payment gateway
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

	/**
	 * Get Multibanco order details
	 *
	 * @param integer $order_id The order ID.
	 * @return array or false
	 */
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

	/**
	 * Get MB WAY order details
	 *
	 * @param integer $order_id The order ID.
	 * @return array or false
	 */
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

	/**
	 * Get Payshop order details
	 *
	 * @param integer $order_id The order ID.
	 * @return array or false
	 */
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

	/**
	 * Get Credit card order details
	 *
	 * @param integer $order_id The order ID.
	 * @return array or false
	 */
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

	/**
	 * Get Cofidis Pay order details
	 *
	 * @param integer $order_id The order ID.
	 * @return array or false
	 */
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

	/**
	 * Get ifthenpay Gateway order details
	 *
	 * @param integer $order_id The order ID.
	 * @return array or false
	 */
	public function get_gatewayifthenpay_order_details( $order_id ) {
		$order              = wc_get_order( $order_id );
		$gatewaykey         = $order->get_meta( '_' . $this->gateway_ifthen_id . '_gatewaykey' );
		$pincode            = $order->get_meta( '_' . $this->gateway_ifthen_id . '_pincode' );
		$id                 = $order->get_meta( '_' . $this->gateway_ifthen_id . '_id' );
		$val                = $order->get_meta( '_' . $this->gateway_ifthen_id . '_val' );
		$time               = $order->get_meta( '_' . $this->gateway_ifthen_id . '_time' );
		$payment_url        = $order->get_meta( '_' . $this->gateway_ifthen_id . '_payment_url' );
		$wd_secret          = $order->get_meta( '_' . $this->gateway_ifthen_id . '_wd_secret' );
		$payment_method     = $order->get_meta( '_' . $this->gateway_ifthen_id . '_payment_method' );
		$payment_method_key = $order->get_meta( '_' . $this->gateway_ifthen_id . '_payment_method_key' );
		$request_id         = $order->get_meta( '_' . $this->gateway_ifthen_id . '_request_id' );
		if ( ! empty( $gatewaykey ) && ! empty( $id ) && ! empty( $pincode ) && ! empty( $val ) ) {
			return array(
				'gatewaykey'         => $gatewaykey,
				'pincode'            => $pincode,
				'id'                 => $id,
				'val'                => $val,
				'time'               => $time,
				'payment_url'        => $payment_url,
				'wd_secret'          => $wd_secret,
				'payment_method'     => $payment_method,
				'payment_method_key' => $payment_method_key,
				'request_id'         => $request_id,
			);
		}
		return false;
	}

	/**
	 * Order metabox registration to show ifthenpay payment details - HPOS compatible
	 */
	public function multibanco_order_metabox() {
		$screen = $this->hpos_enabled ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		add_meta_box(
			$this->multibanco_id,
			'ifthenpay',
			array( $this, 'multibanco_order_metabox_html' ),
			$screen,
			'side',
			'core'
		);
		// WooCommerce Deposits newer versions - Not HPOS tested because Deposits is not yet HPOS compatible (4.1.15)
		if ( $this->wc_deposits_active ) {
			add_meta_box(
				$this->multibanco_id,
				'ifthenpay',
				array( $this, 'multibanco_order_metabox_html' ),
				'wcdp_payment',
				'side',
				'core'
			);
		}
	}

	/**
	 * Order metabox HTML output to show ifthenpay payment details - HPOS compatible
	 *
	 * @param mixed $post_or_order_object Post or Order.
	 * @return void
	 */
	public function multibanco_order_metabox_html( $post_or_order_object ) {
		$order     = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
		$date_paid = $order->get_date_paid();
		if ( ! empty( $order_paid ) ) {
			$date_paid = sprintf(
				'%1$s %2$s',
				wc_format_datetime( $date_paid, 'Y-m-d' ),
				wc_format_datetime( $date_paid, 'H:i' )
			);
		}
		switch ( $order->get_payment_method() ) {
			// Multibanco
			case $this->multibanco_id:
				$order_mb_details = $this->get_multibanco_order_details( $order->get_id() );
				if ( ! empty( $order_mb_details ) ) {
					echo '<p><img src="' . esc_url( $this->multibanco_banner ) . '" style="display: block; margin: auto; max-width: auto; max-height: 24px;" alt="Multibanco" title="Multibanco"/></p>';
					echo '<p>' . esc_html__( 'Entity', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . esc_html( trim( $order_mb_details['ent'] ) ) . '<br/>';
					echo esc_html__( 'Reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . esc_html( $this->format_multibanco_ref( $order_mb_details['ref'] ) ) . '<br/>';
					echo esc_html__( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . wc_price( $order_mb_details['val'], array( 'currency' => $order->get_currency() ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					if ( $this->order_needs_payment( $order ) ) {
						if ( trim( $order_mb_details['exp'] ) !== '' ) {
							echo '<p>' . esc_html__( 'Expiration', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . wp_kses_post( $this->multibanco_format_expiration( $order_mb_details['exp'], $order->get_id() ) ) . '</p>';
						}
						$show_debug = true;
						if ( $this->wc_deposits_active && ( $order->get_status() === 'partially-paid' || ( $order->get_status() === 'on-hold' && $order->get_meta( '_wc_deposits_deposit_paid' ) === 'yes' ) ) ) {
							echo '<p><strong>' . esc_html__( 'Partially paid.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							if ( $order->get_meta( '_wc_deposits_second_payment_paid' ) !== 'yes' && floatval( $order->get_meta( '_wc_deposits_second_payment' ) ) === floatval( $order_mb_details['val'] ) ) {
								echo '<p><strong>' . esc_html__( 'Awaiting second Multibanco payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							} else {
								$show_debug = false;
							}
						} else {
							echo '<p><strong>' . esc_html__( 'Awaiting Multibanco payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
						}
						if ( $show_debug && WP_DEBUG ) {
							$callback_url = $this->multibanco_notify_url;
							$callback_url = str_replace( '[CHAVE_ANTI_PHISHING]', $this->multibanco_settings['secret_key'], $callback_url );
							$callback_url = str_replace( '[ENTIDADE]', trim( $order_mb_details['ent'] ), $callback_url );
							$callback_url = str_replace( '[REFERENCIA]', trim( $order_mb_details['ref'] ), $callback_url );
							$callback_url = str_replace( '[VALOR]', $order_mb_details['val'], $callback_url );
							$callback_url = str_replace( '[DATA_HORA_PAGAMENTO]', '', $callback_url );
							$callback_url = str_replace( '[TERMINAL]', 'Testing', $callback_url );
							$callback_url = str_replace( '[FEE]', 0, $callback_url );
							?>
							<hr/>
							<p>
								<?php esc_html_e( 'Callback URL', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:<br/>
								<textarea readonly type="text" class="input-text" cols="20" rows="5" style="width: 100%; height: 50%; font-size: 10px;"><?php echo esc_html( $callback_url ); ?></textarea>
							</p>
							<script type="text/javascript">
							jQuery( document ).ready( function() {
								jQuery( '#multibanco_ifthen_for_woocommerce_simulate_callback' ).on( 'click', function() {
									if ( confirm( '<?php esc_html_e( 'This is a testing tool and will set the order as paid. Are you sure you want to proceed?', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' ) ) {
										jQuery.get( '<?php echo $callback_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>', '', function( response ) {
											alert( '<?php esc_html_e( 'This page will now reload. If the order is not set as paid and processing (or completed, if it only contains virtual and downloadable products) please check the debug logs.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
											window.location.reload();
										}).fail( function() {
											alert( '<?php esc_html_e( 'Error: Could not set the order as paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
										});
									}
								});
							});
							</script>
							<p style="text-align: center;">
								<input type="button" class="button" id="multibanco_ifthen_for_woocommerce_simulate_callback" value="<?php echo esc_attr( __( 'Simulate callback payment', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ); ?>"/>
							</p>
							<?php
						}
					} elseif ( $date_paid ) {
						// PAID?
						echo '<p><strong>' . esc_html__( 'Paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . esc_html( $date_paid ) . '</strong></p>';
					}
				} else {
					echo '<p>' . esc_html__( 'No details available', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '.</p><p>' . sprintf(
						/* translators: $s: payment method */
						esc_html__( 'This must be an error because the payment method of this order is %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'Multibanco'
					) . '.</p>';
				}
				break;
			// MB WAY
			case $this->mbway_id:
				$order_mbway_details = $this->get_mbway_order_details( $order->get_id() );
				if ( ! empty( $order_mbway_details ) ) {
					echo '<p><img src="' . esc_url( $this->mbway_banner ) . '" style="display: block; margin: auto; max-width: auto; max-height: 24px;" alt="MB WAY" title="MB WAY"/></p>';
					echo '<p>' . esc_html__( 'MB WAY Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . esc_html( trim( $order_mbway_details['mbwaykey'] ) ) . '<br/>';
					echo esc_html__( 'Request ID', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . esc_html( trim( $order_mbway_details['id_pedido'] ) ) . '<br/>';
					echo esc_html__( 'Phone', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . esc_html( trim( $order->get_meta( '_' . $this->mbway_id . '_phone' ) ) ) . '<br/>';
					echo esc_html__( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . wc_price( $order_mbway_details['val'], array( 'currency' => $order->get_currency() ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					if ( $this->order_needs_payment( $order ) ) {
						if ( trim( $order_mbway_details['exp'] ) !== '' ) {
							echo '<p>' . esc_html__( 'Expiration', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . wp_kses_post( $this->mbway_format_expiration( $order_mbway_details['exp'], $order->get_id() ) ) . '</p>';
						}
						$show_debug = true;
						if ( $this->wc_deposits_active && $order->get_status() === 'partially-paid' ) {
							echo '<p><strong>' . esc_html__( 'Partially paid.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							if ( $order->get_meta( '_wc_deposits_second_payment_paid' ) !== 'yes' && floatval( $order->get_meta( '_wc_deposits_second_payment' ) ) === floatval( $order_mbway_details['val'] ) ) {
								echo '<p><strong>' . esc_html__( 'Awaiting second MB WAY payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							} else {
								$show_debug = false;
							}
						} else {
							echo '<p><strong>' . esc_html__( 'Awaiting MB WAY payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							if ( date_i18n( 'Y-m-d H:i:s', strtotime( '-' . intval( $this->mbway_minutes * $this->mbway_multiplier_new_payment * 60 ) . ' SECONDS', current_time( 'timestamp' ) ) ) > $order_mbway_details['time'] ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
								?>
								<p style="text-align: center;">
									<input type="button" class="button" id="mbway_ifthen_request_payment_again" value="<?php echo esc_attr( __( 'Request MB WAY payment again', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ); ?>"/>
								</p>
								<script type="text/javascript">
								jQuery( document ).ready( function() {
									jQuery( '#mbway_ifthen_request_payment_again' ).on( 'click', function() {
										if ( confirm( '<?php echo esc_html__( 'Are you sure you want to request the payment again? Don’t do it unless your client asks you to.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' ) ) {
											var phone = '<?php echo esc_attr( $order->get_meta( '_' . $this->mbway_id . '_phone' ) ); ?>';
											phone = prompt( '<?php echo esc_html__( 'MB WAY phone number', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>', phone );
											if ( phone.length === 9 ) {
												jQuery( '#mbway_ifthen_request_payment_again' ).val( '<?php esc_html_e( 'Wait...', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
												var data = {
													'action'  : 'mbway_ifthen_request_payment_again',
													'order_id': <?php echo intval( $order->get_id() ); ?>,
													'nonce'   : '<?php echo esc_attr( wp_create_nonce( 'mbway_ifthen_request_payment_again' ) ); ?>',
													'phone'   : phone
												};
												jQuery.post( ajaxurl, data, function( response ) {
													if ( response.status === 1 ) {
														jQuery( '#mbway_ifthen_request_payment_again' ).val( '<?php esc_html_e( 'Done!', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
														alert( response.message );
														window.location.reload();
													} else {
														jQuery( '#mbway_ifthen_request_payment_again' ).val( '<?php esc_html_e( 'Request MB WAY payment again', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
														alert( response.error );
													}
												}, 'json' ).fail( function() {
													jQuery( '#mbway_ifthen_request_payment_again' ).val( '<?php esc_html_e( 'Request MB WAY payment again', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
													alert( '<?php esc_html_e( 'Unknown error.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
												} );
											} else {
												alert( '<?php esc_html_e( 'Invalid phone number', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
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
							$callback_url = str_replace( '[FEE]', 0, $callback_url );
							?>
							<hr/>
							<p>
								<?php esc_html_e( 'Callback URL', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:<br/>
								<textarea readonly type="text" class="input-text" cols="20" rows="5" style="width: 100%; height: 50%; font-size: 10px;"><?php echo esc_html( $callback_url ); ?></textarea>
							</p>
							<script type="text/javascript">
							jQuery( document ).ready( function() {
								jQuery( '#multibanco_ifthen_for_woocommerce_simulate_callback' ).on( 'click', function() {
									if ( confirm( '<?php esc_html_e( 'This is a testing tool and will set the order as paid. Are you sure you want to proceed?', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' ) ) {
										jQuery.get( '<?php echo $callback_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>', '', function( response ) {
											alert( '<?php esc_html_e( 'This page will now reload. If the order is not set as paid and processing (or completed, if it only contains virtual and downloadable products) please check the debug logs.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
											window.location.reload();
										}).fail( function() {
											alert( '<?php esc_html_e( 'Error: Could not set the order as paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
										});
									}
								});
							});
							</script>
							<p style="text-align: center;">
								<input type="button" class="button" id="multibanco_ifthen_for_woocommerce_simulate_callback" value="<?php echo esc_attr( __( 'Simulate callback payment', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ); ?>"/>
							</p>
							<?php
						}
					} elseif ( $date_paid ) {
						// PAID?
						echo '<p><strong>' . esc_html__( 'Paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . esc_html( $date_paid ) . '</strong></p>';
					}
				} else {
					echo '<p>' . esc_html__( 'No details available', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '.</p><p>' . sprintf(
						/* translators: $s: payment method */
						esc_html__( 'This must be an error because the payment method of this order is %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'MB WAY'
					) . '.</p>';
				}
				break;
			// Payshop
			case $this->payshop_id:
				$order_mb_details = $this->get_payshop_order_details( $order->get_id() );
				if ( ! empty( $order_mb_details ) ) {
					echo '<p><img src="' . esc_url( $this->payshop_banner ) . '" style="display: block; margin: auto; max-width: auto; max-height: 24px;" alt="Payshop" title="Payshop"/></p>';
					echo '<p>' . esc_html__( 'Reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . esc_html( $this->format_payshop_ref( $order_mb_details['ref'] ) ) . '<br/>';
					echo esc_html__( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . wc_price( $order_mb_details['val'], array( 'currency' => $order->get_currency() ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					if ( $this->order_needs_payment( $order ) ) {
						if ( trim( $order_mb_details['exp'] ) !== '' ) {
							echo '<p>' . esc_html__( 'Expiration', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . wp_kses_post( $this->payshop_format_expiration( $order_mb_details['exp'], $order->get_id() ) ) . '</p>';
						}
						$show_debug = true;
						if ( $this->wc_deposits_active && ( $order->get_status() === 'partially-paid' || ( $order->get_status() === 'on-hold' && $order->get_meta( '_wc_deposits_deposit_paid' ) === 'yes' ) ) ) {
							echo '<p><strong>' . esc_html__( 'Partially paid.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							if ( $order->get_meta( '_wc_deposits_second_payment_paid' ) !== 'yes' && floatval( $order->get_meta( '_wc_deposits_second_payment' ) ) === floatval( $order_mb_details['val'] ) ) {
								echo '<p><strong>' . esc_html__( 'Awaiting second Payshop payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							} else {
								$show_debug = false;
							}
						} else {
							echo '<p><strong>' . esc_html__( 'Awaiting Payshop payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
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
							$callback_url = str_replace( '[FEE]', 0, $callback_url );
							?>
							<hr/>
							<p>
								<?php esc_html_e( 'Callback URL', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:<br/>
								<textarea readonly type="text" class="input-text" cols="20" rows="5" style="width: 100%; height: 50%; font-size: 10px;"><?php echo esc_html( $callback_url ); ?></textarea>
							</p>
							<script type="text/javascript">
							jQuery( document ).ready( function() {
								jQuery( '#multibanco_ifthen_for_woocommerce_simulate_callback' ).on( 'click', function() {
									if ( confirm( '<?php esc_html_e( 'This is a testing tool and will set the order as paid. Are you sure you want to proceed?', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' ) ) {
										jQuery.get( '<?php echo $callback_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>', '', function( response ) {
											alert( '<?php esc_html_e( 'This page will now reload. If the order is not set as paid and processing (or completed, if it only contains virtual and downloadable products) please check the debug logs.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
											window.location.reload();
										}).fail( function() {
											alert( '<?php esc_html_e( 'Error: Could not set the order as paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
										});
									}
								});
							});
							</script>
							<p style="text-align: center;">
								<input type="button" class="button" id="multibanco_ifthen_for_woocommerce_simulate_callback" value="<?php echo esc_attr( __( 'Simulate callback payment', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ); ?>"/>
							</p>
							<?php
						}
					} elseif ( $date_paid ) {
						// PAID?
						echo '<p><strong>' . esc_html__( 'Paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . esc_html( $date_paid ) . '</strong></p>';
					}
				} else {
					echo '<p>' . esc_html__( 'No details available', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '.</p><p>' . sprintf(
						/* translators: $s: payment method */
						esc_html__( 'This must be an error because the payment method of this order is %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'Payshop'
					) . '.</p>';
				}
				break;
			// Credit card
			case $this->creditcard_id:
				$order_mb_details = $this->get_creditcard_order_details( $order->get_id() );
				if ( ! empty( $order_mb_details ) ) {
					echo '<p><img src="' . esc_url( $this->creditcard_banner ) . '" style="display: block; margin: auto; max-width: auto; max-height: 24px;" alt="Credit or debit card" title="Credit or debit card"/></p>';
					echo '<p>' . esc_html__( 'Credit card Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . esc_html( trim( $order_mb_details['creditcardkey'] ) ) . '<br/>';
					echo esc_html__( 'Request ID', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . esc_html( trim( $order_mb_details['request_id'] ) ) . '<br/>';
					echo esc_html__( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . wc_price( $order_mb_details['val'], array( 'currency' => $order->get_currency() ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					if ( $this->order_needs_payment( $order ) ) {
						$show_debug = true;
						if ( $this->wc_deposits_active && $order->get_status() === 'partially-paid' ) {
							echo '<p><strong>' . esc_html__( 'Partially paid.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							if ( $order->get_meta( '_wc_deposits_second_payment_paid' ) !== 'yes' && floatval( $order->get_meta( '_wc_deposits_second_payment' ) ) === floatval( $order_mb_details['val'] ) ) {
								echo '<p><strong>' . esc_html__( 'Awaiting second Credit or debit card payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							} else {
								$show_debug = false;
							}
						} else {
							echo '<p><strong>' . esc_html__( 'Awaiting Credit or debit card payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
						}
						if ( $show_debug && WP_DEBUG ) {
							$val          = number_format( $order_mb_details['val'], 2, '.', '' );
							$callback_url = $this->creditcard_notify_url;
							$callback_url = str_replace( '[ANTI_PHISHING_KEY]', $this->creditcard_settings['secret_key'], $callback_url );
							$callback_url = str_replace( '[ID]', trim( $order_mb_details['id'] ), $callback_url );
							$callback_url = str_replace( '[AMOUNT]', $val, $callback_url );
							$callback_url = str_replace( '[PAYMENT_DATETIME]', rawurlencode( date_i18n( 'Y-m-d H:i:s' ) ), $callback_url );
							$callback_url = str_replace( '[STATUS]', 'PAGO', $callback_url );
							$callback_url = str_replace( '[REQUEST_ID]', $order_mb_details['request_id'], $callback_url );
							?>
							<hr/>
							<p>
								<?php esc_html_e( 'Callback URL', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:<br/>
								<textarea readonly type="text" class="input-text" cols="20" rows="5" style="width: 100%; height: 50%; font-size: 10px;"><?php echo esc_html( $callback_url ); ?></textarea>
							</p>
							<script type="text/javascript">
							jQuery( document ).ready( function() {
								jQuery( '#multibanco_ifthen_for_woocommerce_simulate_callback' ).on( 'click', function() {
									if ( confirm( '<?php esc_html_e( 'This is a testing tool and will set the order as paid. Are you sure you want to proceed?', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' ) ) {
										jQuery.get( '<?php echo $callback_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>', '', function( response ) {
											alert( '<?php esc_html_e( 'This page will now reload. If the order is not set as paid and processing (or completed, if it only contains virtual and downloadable products) please check the debug logs.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
											window.location.reload();
										}).fail( function() {
											alert( '<?php esc_html_e( 'Error: Could not set the order as paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
										});
									}
								});
							});
							</script>
							<p style="text-align: center;">
								<input type="button" class="button" id="multibanco_ifthen_for_woocommerce_simulate_callback" value="<?php echo esc_attr( __( 'Simulate callback payment', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ); ?>"/>
							</p>
							<?php
						}
					} elseif ( $date_paid ) {
						// PAID?
						echo '<p><strong>' . esc_html__( 'Paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . esc_html( $date_paid ) . '</strong></p>';
					}
				} else {
					echo '<p>' . esc_html__( 'No details available', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '.</p><p>' . sprintf(
						/* translators: $s: payment method */
						esc_html__( 'This must be an error because the payment method of this order is %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'Credit card'
					) . '.</p>';

				}
				break;
			// Cofidis Pay
			case $this->cofidispay_id:
				$order_mb_details = $this->get_cofidispay_order_details( $order->get_id() );
				if ( ! empty( $order_mb_details ) ) {
					echo '<p><img src="' . esc_url( $this->cofidispay_banner ) . '" style="display: block; margin: auto; max-width: auto; max-height: 24px;" alt="Cofidis Pay" title="Cofidis Pay"/></p>';
					echo '<p>' . esc_html__( 'Cofidis Pay Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . esc_html( trim( $order_mb_details['cofidispaykey'] ) ) . '<br/>';
					echo esc_html__( 'Request ID', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . esc_html( trim( $order_mb_details['request_id'] ) ) . '<br/>';
					echo esc_html__( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . wc_price( $order_mb_details['val'], array( 'currency' => $order->get_currency() ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					if ( $this->order_needs_payment( $order ) ) {
						$show_debug = true;
						if ( $this->wc_deposits_active && $order->get_status() === 'partially-paid' ) {
							echo '<p><strong>' . esc_html__( 'Partially paid.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							if ( $order->get_meta( '_wc_deposits_second_payment_paid' ) !== 'yes' && floatval( $order->get_meta( '_wc_deposits_second_payment' ) ) === floatval( $order_mb_details['val'] ) ) {
								echo '<p><strong>' . esc_html__( 'Awaiting second Cofidis Pay payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							} else {
								$show_debug = false;
							}
						} else {
							echo '<p><strong>' . esc_html__( 'Awaiting Cofidis Pay approval.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
						}
						if ( $show_debug && WP_DEBUG ) {
							$callback_url = $this->cofidispay_notify_url;
							$callback_url = str_replace( '[ANTI_PHISHING_KEY]', $this->cofidispay_settings['secret_key'], $callback_url );
							$callback_url = str_replace( '[ORDER_ID]', trim( $order_mb_details['id'] ), $callback_url );
							$callback_url = str_replace( '[AMOUNT]', $order_mb_details['val'], $callback_url );
							$callback_url = str_replace( '[REQUEST_ID]', $order_mb_details['request_id'], $callback_url );
							?>
							<hr/>
							<p>
								<?php esc_html_e( 'Callback URL', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:<br/>
								<textarea readonly type="text" class="input-text" cols="20" rows="5" style="width: 100%; height: 50%; font-size: 10px;"><?php echo esc_html( $callback_url ); ?></textarea>
							</p>
							<script type="text/javascript">
							jQuery( document ).ready( function() {
								jQuery( '#multibanco_ifthen_for_woocommerce_simulate_callback' ).on( 'click', function() {
									if ( confirm( '<?php esc_html_e( 'This is a testing tool and will set the order as paid. Are you sure you want to proceed?', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' ) ) {
										jQuery.get( '<?php echo $callback_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>', '', function( response ) {
											alert( '<?php esc_html_e( 'This page will now reload. If the order is not set as paid and processing (or completed, if it only contains virtual and downloadable products) please check the debug logs.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
											window.location.reload();
										}).fail( function() {
											alert( '<?php esc_html_e( 'Error: Could not set the order as paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
										});
									}
								});
							});
							</script>
							<p style="text-align: center;">
								<input type="button" class="button" id="multibanco_ifthen_for_woocommerce_simulate_callback" value="<?php echo esc_attr( __( 'Simulate callback payment', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ); ?>"/>
							</p>
							<?php
						}
					} elseif ( $date_paid ) {
						// PAID?
						echo '<p><strong>' . esc_html__( 'Paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . esc_html( $date_paid ) . '</strong></p>';
					}
				} else {
					echo '<p>' . esc_html__( 'No details available', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '.</p><p>' . sprintf(
						/* translators: $s: payment method */
						esc_html__( 'This must be an error because the payment method of this order is %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'Cofidis Pay'
					) . '.</p>';

				}
				break;
			// ifthenpay Gateway
			case $this->gateway_ifthen_id:
				$order_mb_details = $this->get_gatewayifthenpay_order_details( $order->get_id() );
				if ( ! empty( $order_mb_details ) ) {
					// This could be moved to a global part of the code later
					$payment_method_icons = array(
						'APPLE'  => '<img src="' . esc_url( plugins_url( 'images/apple_pay_banner.svg', __FILE__ ) ) . '" style="display: block; margin: auto; max-width: auto; max-height: 24px;" alt="Apple Pay" title="Apple Pay"/>',
						'GOOGLE' => '<img src="' . esc_url( plugins_url( 'images/google_pay_banner.svg', __FILE__ ) ) . '" style="display: block; margin: auto; max-width: auto; max-height: 24px;" alt="Google Pay" title="Google Pay"/>',
						'PIX'    => '<img src="' . esc_url( plugins_url( 'images/pix_banner.svg', __FILE__ ) ) . '" style="display: block; margin: auto; max-width: auto; max-height: 24px;" alt="PIX" title="PIX"/>',
					);
					if ( trim( $order_mb_details['payment_method'] ) !== '' ) {
						echo '<p><img src="' . esc_url( $this->gateway_ifthen_banner ) . '" style="display: block; margin: auto; max-width: auto; max-height: 14px;" alt="ifthenpay Gateway" title="ifthenpay Gateway"/></p>';
						echo '<p style="text-align: center;">';
						if ( isset( $payment_method_icons[ trim( $order_mb_details['payment_method'] ) ] ) ) {
							echo wp_kses_post( $payment_method_icons[ trim( $order_mb_details['payment_method'] ) ] );
						} else {
							echo '<span style="font-size: 1.5em; font-weight: bold;">' . esc_html( trim( $order_mb_details['payment_method'] ) ) . '</span>';
						}
						echo '</p>';
					} else {
						echo '<p><img src="' . esc_url( $this->gateway_ifthen_banner ) . '" style="display: block; margin: auto; max-width: auto; max-height: 24px;" alt="ifthenpay Gateway" title="ifthenpay Gateway"/></p>';
					}
					echo '<p>' . esc_html__( 'Gateway Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . esc_html( trim( $order_mb_details['gatewaykey'] ) ) . '<br/>';
					echo esc_html__( 'Pincode', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . esc_html( trim( $order_mb_details['pincode'] ) ) . '<br/>';
					if ( trim( $order_mb_details['payment_method_key'] ) !== '' ) {
						echo esc_html__( 'Payment Method Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . esc_html( trim( $order_mb_details['payment_method_key'] ) ) . '<br/>';
					}if ( trim( $order_mb_details['request_id'] ) !== '' ) {
						echo esc_html__( 'Request ID', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . esc_html( trim( $order_mb_details['request_id'] ) ) . '<br/>';
					}
					echo esc_html__( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': ' . wc_price( $order_mb_details['val'], array( 'currency' => $order->get_currency() ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					if ( $this->order_needs_payment( $order ) ) {
						$show_debug = true;
						if ( $this->wc_deposits_active && $order->get_status() === 'partially-paid' ) {
							echo '<p><strong>' . esc_html__( 'Partially paid.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							if ( $order->get_meta( '_wc_deposits_second_payment_paid' ) !== 'yes' && floatval( $order->get_meta( '_wc_deposits_second_payment' ) ) === floatval( $order_mb_details['val'] ) ) {
								echo '<p><strong>' . esc_html__( 'Awaiting second ifthenpay Gateway payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
							} else {
								$show_debug = false;
							}
						} else {
							echo '<p><strong>' . esc_html__( 'Awaiting ifthenpay Gateway confirmation.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong></p>';
						}
						if ( $show_debug && WP_DEBUG ) {
							$callback_url = $this->gateway_ifthen_notify_url;
							$callback_url = str_replace( '[ANTI_PHISHING_KEY]', $this->gateway_ifthen_settings['secret_key'], $callback_url );
							$callback_url = str_replace( '[ID]', trim( $order_mb_details['id'] ), $callback_url );
							$callback_url = str_replace( '[AMOUNT]', $order_mb_details['val'], $callback_url );
							$callback_url = str_replace( '[PAYMENT_DATETIME]', rawurlencode( date_i18n( 'Y-m-d H:i:s' ) ), $callback_url );
							$callback_url = str_replace( '[STATUS]', 'PAGO', $callback_url );
							$callback_url = str_replace( '[PAYMENT_METHOD]', 'TEST_METHOD', $callback_url );
							$callback_url = str_replace( '[PAYMENT_METHOD_KEY]', 'TEST_METHOD_KEY', $callback_url );
							$callback_url = str_replace( '[REQUEST_ID]', 'TEST_REQUEST_ID', $callback_url );
							$callback_url = str_replace( '[FEE]', 0, $callback_url );
							?>
							<hr/>
							<p>
								<?php esc_html_e( 'Callback URL', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:<br/>
								<textarea readonly type="text" class="input-text" cols="20" rows="5" style="width: 100%; height: 50%; font-size: 10px;"><?php echo esc_html( $callback_url ); ?></textarea>
							</p>
							<script type="text/javascript">
							jQuery( document ).ready( function() {
								jQuery( '#multibanco_ifthen_for_woocommerce_simulate_callback' ).on( 'click', function() {
									if ( confirm( '<?php esc_html_e( 'This is a testing tool and will set the order as paid. Are you sure you want to proceed?', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' ) ) {
										jQuery.get( '<?php echo $callback_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>', '', function( response ) {
											alert( '<?php esc_html_e( 'This page will now reload. If the order is not set as paid and processing (or completed, if it only contains virtual and downloadable products) please check the debug logs.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
											window.location.reload();
										}).fail( function() {
											alert( '<?php esc_html_e( 'Error: Could not set the order as paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
										});
									}
								});
							});
							</script>
							<p style="text-align: center;">
								<input type="button" class="button" id="multibanco_ifthen_for_woocommerce_simulate_callback" value="<?php echo esc_attr( __( 'Simulate callback payment', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ); ?>"/>
							</p>
							<?php
						}
					} elseif ( $date_paid ) {
						// PAID?
						echo '<p><strong>' . esc_html__( 'Paid', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' - ' . esc_html( $this->helper_format_method( $order_mb_details['payment_method'] ) ) . ': ' . esc_html( $date_paid ) . '</strong></p>';
					}
				} else {
					echo '<p>' . esc_html__( 'No details available', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '.</p><p>' . sprintf(
						/* translators: $s: payment method */
						esc_html__( 'This must be an error because the payment method of this order is %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'ifthenpay Gateway'
					) . '.</p>';

				}
				break;
			// None
			default:
				echo '<p>' . esc_html__( 'No details available', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '.</p><p>' . esc_html__( 'The payment method of this order is not ifthenpay', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '.</p>';
				echo '<style type="text/css">#' . esc_html( $this->multibanco_id ) . ' { display: none; }</style>';
				$deleted = false;
				// If we have Multibanco data, we should delete it
				$order_mb_details = $this->get_multibanco_order_details( $order->get_id() );
				if ( ! empty( $order_mb_details ) ) {
					foreach ( $order_mb_details as $key => $value ) {
						$order->delete_meta_data( '_' . $this->multibanco_id . '_' . $key );
						$deleted = true;
					}
				}
				// If we have MB WAY data, we should delete it
				$order_mb_details = $this->get_mbway_order_details( $order->get_id() );
				if ( ! empty( $order_mb_details ) ) {
					foreach ( $order_mb_details as $key => $value ) {
						$order->delete_meta_data( '_' . $this->mbway_id . '_' . $key );
						$deleted = true;
					}
				}
				// If we have Payshop data, we should delete it
				$order_mb_details = $this->get_payshop_order_details( $order->get_id() );
				if ( ! empty( $order_mb_details ) ) {
					foreach ( $order_mb_details as $key => $value ) {
						$order->delete_meta_data( '_' . $this->payshop_id . '_' . $key );
						$deleted = true;
					}
				}
				// If we have Credit card data, we should delete it
				$order_mb_details = $this->get_creditcard_order_details( $order->get_id() );
				if ( ! empty( $order_mb_details ) ) {
					foreach ( $order_mb_details as $key => $value ) {
						$order->delete_meta_data( '_' . $this->creditcard_id . '_' . $key );
						$deleted = true;
					}
				}
				// If we have Cofidis Pay data, we should delete it
				$order_mb_details = $this->get_cofidispay_order_details( $order->get_id() );
				if ( ! empty( $order_mb_details ) ) {
					foreach ( $order_mb_details as $key => $value ) {
						$order->delete_meta_data( '_' . $this->cofidispay_id . '_' . $key );
						$deleted = true;
					}
				}
				// If we have ifthenpay Gateway data, we should delete it
				$order_mb_details = $this->get_gatewayifthenpay_order_details( $order->get_id() );
				if ( ! empty( $order_mb_details ) ) {
					foreach ( $order_mb_details as $key => $value ) {
						$order->delete_meta_data( '_' . $this->gateway_ifthen_id . '_' . $key );
						$deleted = true;
					}
				}
				if ( $deleted ) {
					$order->save();
				}
				break;
		}
	}

	/**
	 * Allow searching orders by Multibanco or Payshop reference - CPT and HPOS compatible
	 *
	 * @param array $search_fields Current fields for search.
	 * @return array
	 */
	public function shop_order_search( $search_fields ) {
		$search_fields[] = '_' . $this->multibanco_id . '_ref';
		$search_fields[] = '_' . $this->payshop_id . '_ref';
		return $search_fields;
	}

	/**
	 * Set new order Multibanco Entity/Reference/Value on meta
	 *
	 * @param integer $order_id         The order ID.
	 * @param array   $order_mb_details The Multibanco details.
	 */
	public function set_order_multibanco_details( $order_id, $order_mb_details ) {
		$order = wc_get_order( $order_id );
		$order->update_meta_data( '_' . $this->multibanco_id . '_ent', $order_mb_details['ent'] );
		$order->update_meta_data( '_' . $this->multibanco_id . '_ref', $order_mb_details['ref'] );
		$order->update_meta_data( '_' . $this->multibanco_id . '_val', $order_mb_details['val'] );
		if ( $this->get_multibanco_ref_mode() === 'incremental_expire' ) {
			// Update last seed
			++$this->multibanco_last_incremental_expire_ref;
			update_option( 'multibanco_last_incremental_expire_ref', intval( $this->multibanco_last_incremental_expire_ref ) );
			// Update order reference expiration
			$order->update_meta_data( '_' . $this->multibanco_id . '_exp', $this->get_reference_expiration_days( intval( apply_filters( 'multibanco_ifthen_incremental_expire_days', 0 ) ) ) );
		}
		if ( isset( $order_mb_details['exp'] ) ) {
			$order->update_meta_data( '_' . $this->multibanco_id . '_exp', $order_mb_details['exp'] );
		}
		if ( isset( $order_mb_details['RequestId'] ) ) {
			$order->update_meta_data( '_' . $this->multibanco_id . '_mbkey', apply_filters( 'multibanco_ifthen_base_mbkey', $this->multibanco_settings['mbkey'], $order ) );
			$order->update_meta_data( '_' . $this->multibanco_id . '_RequestId', $order_mb_details['RequestId'] );
		}
		$order->save();
		$this->debug_log_extra( $this->multibanco_id, 'set_order_multibanco_details - Details updated on the database: ' . wp_json_encode( $order_mb_details ) . ' - Order: ' . $order->get_id() );
	}

	/**
	 * Clear Multibanco Entity/Reference/Value on meta
	 *
	 * @param integer $order_id The order ID.
	 */
	public function multibanco_clear_order_mb_details( $order_id ) {
		$order = wc_get_order( $order_id );
		$order->delete_meta_data( '_' . $this->multibanco_id . '_mbkey' );
		$order->delete_meta_data( '_' . $this->multibanco_id . '_RequestId' );
		$order->delete_meta_data( '_' . $this->multibanco_id . '_ent' );
		$order->delete_meta_data( '_' . $this->multibanco_id . '_ref' );
		$order->delete_meta_data( '_' . $this->multibanco_id . '_val' );
		$order->delete_meta_data( '_' . $this->multibanco_id . '_exp' );
		$order->save();
	}

	/**
	 * Get Reference expiration date/time in days
	 *
	 * @param integer $days Number of days.
	 */
	public function get_reference_expiration_days( $days ) {
		$d = date_create( date_i18n( DateTime::ISO8601 ) );
		date_add( $d, date_interval_create_from_date_string( '+' . $days . ' days' ) );
		$d->modify( 'tomorrow' );
		$d->modify( '1 second ago' );
		return date_format( $d, 'Y-m-d H:i:s' );
	}

	/**
	 * Format Multibanco Reference expiration date/time
	 *
	 * @param string  $exp      Expiration date in Y-m- H:i:s format.
	 * @param integer $order_id The Order ID.
	 * @return string
	 */
	public function multibanco_format_expiration( $exp, $order_id ) {
		$exp_formated = substr( $exp, 0, 16 );
		if ( $exp < date_i18n( 'Y-m-d H:i:s' ) ) {
			$exp_formated = '<s>' . $exp_formated . '</s> ' . esc_html__( '(expired)', 'multibanco-ifthen-software-gateway-for-woocommerce' );
		}
		return apply_filters( 'multibanco_ifthen_format_expiration', $exp_formated, $exp, $order_id );
	}

	/**
	 * Format MB WAY expiration date/time
	 *
	 * @param string  $exp      Expiration date in Y-m-d H:i:s format.
	 * @param integer $order_id The Order ID.
	 * @return string
	 */
	public function mbway_format_expiration( $exp, $order_id ) {
		if ( $exp < date_i18n( 'Y-m-d H:i:s' ) ) {
			$exp_formated = substr( $exp, 0, 16 );
			$exp_formated = '<s>' . $exp_formated . '</s> ' . esc_html__( '(expired)', 'multibanco-ifthen-software-gateway-for-woocommerce' );
		} else {
			$exp_formated = substr( $exp, 11, 5 );
		}
		return apply_filters( 'mbway_ifthen_format_expiration', $exp_formated, $exp, $order_id );
	}

	/**
	 * Format Payshop expiration date/time
	 *
	 * @param string  $exp      Expiration date in Y-m- H:i:s format.
	 * @param integer $order_id The Order ID.
	 * @return string
	 */
	public function payshop_format_expiration( $exp, $order_id ) {
		if ( $exp < date_i18n( 'Y-m-d H:i:s' ) ) {
			$exp_formated = $exp; // Date + Time
			$exp_formated = '<s>' . $exp_formated . '</s> ' . esc_html__( '(expired)', 'multibanco-ifthen-software-gateway-for-woocommerce' );
		} else {
			$exp_formated = substr( $exp, 0, 10 ); // Only date
		}
		return apply_filters( 'payshop_ifthen_format_expiration', $exp_formated, $exp, $order_id );
	}

	/**
	 * Set new order MB WAY details on meta
	 *
	 * @param integer $order_id            The order ID.
	 * @param array   $order_mbway_details The Multibanco details.
	 */
	public function set_order_mbway_details( $order_id, $order_mbway_details ) {
		$order = wc_get_order( $order_id );
		$order->update_meta_data( '_' . $this->mbway_id . '_mbwaykey', $order_mbway_details['mbwaykey'] );
		$order->update_meta_data( '_' . $this->mbway_id . '_id_pedido', $order_mbway_details['id_pedido'] );
		$order->update_meta_data( '_' . $this->mbway_id . '_phone', $order_mbway_details['phone'] );
		$order->update_meta_data( '_' . $this->mbway_id . '_val', $order_mbway_details['val'] );
		$order->update_meta_data( '_' . $this->mbway_id . '_time', date_i18n( 'Y-m-d H:i:s' ) );
		$order->update_meta_data( '_' . $this->mbway_id . '_exp', $this->get_mbway_expiration() );
		$order->save();
	}

	/**
	 * Get MB WAY expiration date/time
	 */
	public function get_mbway_expiration() {
		$d = date_create( date_i18n( DateTime::ISO8601 ) );
		date_add( $d, date_interval_create_from_date_string( '+' . $this->mbway_minutes . ' minutes' ) );
		return date_format( $d, 'Y-m-d H:i:s' );
	}

	/**
	 * Set new order Payshop details on meta
	 *
	 * @param integer $order_id              The order ID.
	 * @param array   $order_payshop_details The Multibanco details.
	 */
	public function set_order_payshop_details( $order_id, $order_payshop_details ) {
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

	/**
	 * Set new order Credit card details on meta
	 *
	 * @param integer $order_id                 The order ID.
	 * @param array   $order_creditcard_details The Multibanco details.
	 */
	public function set_order_creditcard_details( $order_id, $order_creditcard_details ) {
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

	/**
	 * Set new order Cofidis Pay details on meta
	 *
	 * @param integer $order_id                 The order ID.
	 * @param array   $order_cofidispay_details The Multibanco details.
	 */
	public function set_order_cofidispay_details( $order_id, $order_cofidispay_details ) {
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

	/**
	 * Set new order ifthenpay Gateway details on meta
	 *
	 * @param integer $order_id                 The order ID.
	 * @param array   $order_gateway_details The Multibanco details.
	 */
	public function set_order_gatewayifthenpay_details( $order_id, $order_gateway_details ) {
		$order = wc_get_order( $order_id );
		$order->update_meta_data( '_' . $this->gateway_ifthen_id . '_gatewaykey', $order_gateway_details['gatewaykey'] );
		$order->update_meta_data( '_' . $this->gateway_ifthen_id . '_pincode', $order_gateway_details['pincode'] );
		$order->update_meta_data( '_' . $this->gateway_ifthen_id . '_id', $order_gateway_details['id'] );
		$order->update_meta_data( '_' . $this->gateway_ifthen_id . '_val', $order_gateway_details['val'] );
		$order->update_meta_data( '_' . $this->gateway_ifthen_id . '_payment_url', $order_gateway_details['payment_url'] );
		$order->update_meta_data( '_' . $this->gateway_ifthen_id . '_wd_secret', $order_gateway_details['wd_secret'] );
		$order->update_meta_data( '_' . $this->gateway_ifthen_id . '_time', date_i18n( 'Y-m-d H:i:s' ) );
		$order->save();
	}

	/**
	 * Filter payment description to send to API
	 *
	 * @param string $desc The description.
	 * @return string
	 */
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

	/**
	 * Get/Create Multibanco Reference
	 *
	 * @param integer $order_id        The Order ID.
	 * @param boolean $force_change    If we should force a reference recreation.
	 * @param boolean $throw_exception If we should throw an exception instead of returning false.
	 * @throws Exception               Error message.
	 * @return array or string with error.
	 */
	public function multibanco_get_ref( $order_id, $force_change = false, $throw_exception = false ) {
		$debug       = $this->multibanco_settings['debug'] === 'yes';
		$debug_email = false;
		if ( $debug ) {
			$debug_email = trim( $this->multibanco_settings['debug_email'] ) !== '' ? trim( $this->multibanco_settings['debug_email'] ) : false;
		}
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$order = wc_get_order( $order_id );
		$this->debug_log_extra( $this->multibanco_id, 'multibanco_get_ref - Force change: ' . ( $force_change ? 'true' : 'false' ) . ' - Order ' . $order->get_id() );
		if ( $this->wc_deposits_active ) {
			if ( ! $this->multibanco_deposits_already_forced ) {
				if ( $order->get_meta( '_wc_deposits_order_has_deposit' ) === 'yes' && ( is_checkout() || has_block( 'woocommerce/checkout' ) ) && has_action( 'woocommerce_thankyou' ) ) {
					if ( $order->get_meta( '_wc_deposits_deposit_paid' ) === 'yes' ) {
						if ( $order->get_meta( '_wc_deposits_second_payment_paid' ) === 'no' ) {
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
				$order_mb_details = $this->get_multibanco_order_details( $order->get_id() ) // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure, Generic.CodeAnalysis.AssignmentInCondition.Found
			) {
				$this->debug_log_extra( $this->multibanco_id, 'multibanco_get_ref - Got reference from database ' . wp_json_encode( $order_mb_details ) . ' - Order ' . $order->get_id() );
				// Already created, return it!
				return array(
					'ent' => $order_mb_details['ent'],
					'ref' => $order_mb_details['ref'],
					'val' => $order_mb_details['val'],
				);
			} elseif ( $this->get_order_total_to_pay( $order ) < $this->multibanco_min_value ) {
				// Value ok?
				return sprintf(
					/* translators: %1$s: payment method, %2$s: maximum value */
					esc_html__( 'It’s not possible to use %1$s to pay values under %2$s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'Multibanco',
					wc_price( $this->multibanco_min_value, array( 'currency' => 'EUR' ) )
				);
			} elseif ( $this->get_order_total_to_pay( $order ) > $this->multibanco_max_value ) {
				// Value ok? (again)
				return sprintf(
					/* translators: %1$s: payment method, %2$s: minium value */
					esc_html__( 'It’s not possible to use %1$s to pay values above %2$s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'Multibanco',
					wc_price( $this->multibanco_max_value, array( 'currency' => 'EUR' ) )
				);
			} elseif ( $this->multibanco_api_mode_enabled ) {
				// Create a new reference
				// API Mode
				$mbkey = apply_filters( 'multibanco_ifthen_base_mbkey', $this->multibanco_settings['mbkey'], $order );
				if (
					strlen( trim( $mbkey ) ) === 10
					&&
					trim( $this->multibanco_settings['secret_key'] ) !== ''
				) {
					$url         = $this->multibanco_api_url;
					$desc        = trim( get_bloginfo( 'name' ) );
					$args        = array(
						'method'   => 'POST',
						'timeout'  => apply_filters( 'multibanco_ifthen_webservice_timeout', 15 ),
						'blocking' => true,
						'headers'  => array( 'Content-Type' => 'application/json; charset=utf-8' ),
						'body'     => array(
							'mbKey'       => $mbkey,
							'orderId'     => (string) apply_filters( 'ifthen_webservice_send_order_number_instead_id', false ) ? $order->get_order_number() : $order->get_id(),
							'amount'      => (string) WC_IfthenPay_Webdados()->get_order_total_to_pay_for_gateway( $order ),
							'description' => $this->mb_webservice_filter_descricao( apply_filters( 'multibanco_ifthen_webservice_desc', $desc, $order->get_id() ) ),
						),
					);
					$expire_days = apply_filters( 'multibanco_ifthen_webservice_expire_days', trim( $this->multibanco_settings['api_expiry'] ), $order );
					if ( trim( $expire_days ) !== '' ) {
						$args['body']['expiryDays'] = trim( $expire_days );
						// Temos de calcular a data e guardar mais lá à frente
					}
					$this->debug_log_extra( $this->multibanco_id, '- Request payment with args: ' . wp_json_encode( $args ) );
					$debug_start_time = microtime( true );
					$args['body']     = wp_json_encode( $args['body'] );
					$response         = wp_remote_post( $url, $args );
					if ( is_wp_error( $response ) ) {
						$debug_msg       = '- Error contacting the ifthenpay servers - Order ' . $order->get_id() . ' - ' . $response->get_error_message();
						$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
						$this->debug_log( $this->multibanco_id, $debug_msg, 'error', $debug_email, $debug_msg_email );
						return false;
					} elseif ( isset( $response['response']['code'] ) && intval( $response['response']['code'] ) === 200 && isset( $response['body'] ) && trim( $response['body'] ) !== '' ) {
						$body = json_decode( $response['body'] );
						if ( ! empty( $body ) ) {
							if ( trim( $body->Status ) === '0' ) {
								$details = array(
									'mbkey'     => $mbkey,
									'ent'       => $body->Entity,
									'ref'       => $body->Reference,
									'val'       => $this->get_order_total_to_pay( $order ),
									'RequestId' => $body->RequestId,
								);
								if ( trim( $body->ExpiryDate ) !== '' ) {
									$temp           = explode( '-', trim( $body->ExpiryDate ) );
									$date           = implode( '-', array_reverse( $temp ) ) . ' 23:59:59';
									$details['exp'] = $date;
								}
								// Store on the order for later use (like the email)
								$this->set_order_multibanco_details( $order->get_id(), $details );
								$this->debug_log( $this->multibanco_id, 'Multibanco payment details (' . $details['ent'] . ' / ' . $details['ref'] . ' / ' . $details['val'] . ') generated for Order ' . $order->get_id() );
								// Return the reference
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
								$debug_elapsed_time = microtime( true ) - $debug_start_time;
								$this->debug_log_extra( $this->multibanco_id, 'wp_remote_post + response handling took: ' . $debug_elapsed_time . ' seconds.' );
								return $details;
							} else {
								$debug_msg       = '- Error: ' . trim( $body->Message ) . ' - Order ' . $order->get_id();
								$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
								$this->debug_log( $this->multibanco_id, $debug_msg, 'error', $debug_email, $debug_msg_email );
								if ( $throw_exception ) {
									throw new Exception(
										sprintf(
											/* translators: %s: payment method */
											esc_html__( 'An error occurred processing the %s Payment request - please try again', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
											'Multibanco'
										)
									);
								}
								return false;
							}
						} else {
							$debug_msg       = '- Response body is not JSON - Order ' . $order->get_id();
							$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
							$this->debug_log( $this->multibanco_id, $debug_msg, 'error', $debug_email, $debug_msg_email );
							if ( $throw_exception ) {
								throw new Exception(
									sprintf(
										/* translators: %s: payment method */
										esc_html__( 'An error occurred processing the %s Payment request - please try again', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
										'Multibanco'
									)
								);
							}
							return false;
						}
					} else {
						$debug_msg       = '- Error contacting the ifthenpay servers - Order ' . $order->get_id() . ' - Incorrect response code: ' . $response['response']['code'];
						$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
						$this->debug_log( $this->multibanco_id, $debug_msg, 'error', $debug_email, $debug_msg_email );
						if ( $throw_exception ) {
							throw new Exception(
								sprintf(
									/* translators: %s: payment method */
									esc_html__( 'An error occurred processing the %s Payment request - please try again', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									'Multibanco'
								)
							);
						}
						return false;
					}
				} else {
					$error_details = '';
					if ( strlen( trim( $base['mbkey'] ) ) !== 10 ) {
						$error_details = esc_html__( 'MB Key', 'multibanco-ifthen-software-gateway-for-woocommerce' );
					} elseif ( trim( $this->multibanco_settings['secret_key'] ) === '' ) {
						$error_details = __( 'Anti-phishing key', 'multibanco-ifthen-software-gateway-for-woocommerce' );
					}
					if ( $throw_exception ) {
						throw new Exception(
							esc_html__( 'Configuration error. This payment method is disabled because required information was not set.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' ' . esc_html( $error_details ) . '.'
						);
					}
					return esc_html__( 'Configuration error. This payment method is disabled because required information was not set.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' ' . esc_html( $error_details ) . '.';
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
					strlen( trim( $base['ent'] ) ) === 5
					&&
					strlen( trim( $base['subent'] ) ) <= 3
					&&
					intval( $base['ent'] ) > 0
					&&
					intval( $base['subent'] ) > 0
					&&
					trim( $this->multibanco_settings['secret_key'] ) !== ''
				) {
					if ( isset( $this->multibanco_ents_no_repeat[ $base['ent'] ] ) && intval( $this->multibanco_ents_no_repeat[ $base['ent'] ] ) > 0 ) {
						// No repeat in x days
						$this->debug_log_extra( $this->multibanco_id, 'multibanco_get_ref - will create reference (No repeat in x days) - Order ' . $order->get_id() );
						$ref = $this->multibanco_create_ref( $base['ent'], $base['subent'], $this->get_multibanco_ref_seed(), $this->get_order_total_to_pay( $order ), intval( $this->multibanco_ents_no_repeat[ $base['ent'] ] ) );
					} elseif ( in_array( intval( $base['ent'] ), $this->multibanco_ents_no_check_digit, true ) && ( $this->multibanco_settings['use_order_id'] === 'yes' ) ) {
						// Special entities with no check digit and (eventually) expiration date - We can use the order ID
						$this->debug_log_extra( $this->multibanco_id, 'multibanco_get_ref - Will create reference (Special entities with no check digit and (eventually) expiration date) - Order ' . $order->get_id() );
						$ref = $this->multibanco_create_ref_no_check_digit( $base['ent'], $base['subent'], $order->get_id() );
					} else {
						$this->debug_log_extra( $this->multibanco_id, 'multibanco_get_ref - Will create reference (Default mode) - Order ' . $order->get_id() );
						$ref = $this->multibanco_create_ref( $base['ent'], $base['subent'], $this->get_multibanco_ref_seed(), $this->get_order_total_to_pay( $order ) ); // For random mode - Less loop possibility
					}
					// Store on the order for later use (like the email)
					$this->set_order_multibanco_details(
						$order->get_id(),
						array(
							'ent' => $base['ent'],
							'ref' => $ref,
							'val' => $this->get_order_total_to_pay( $order ),
						)
					);
					$this->debug_log( $this->multibanco_id, 'Multibanco payment details (' . $base['ent'] . ' / ' . $ref . ' / ' . $this->get_order_total_to_pay( $order ) . ') generated for Order ' . $order->get_id() );
					// Return the reference
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
					if ( strlen( trim( $base['ent'] ) ) !== 5 || ( ! intval( $base['ent'] ) > 0 ) ) {
						$error_details = esc_html__( 'Entity', 'multibanco-ifthen-software-gateway-for-woocommerce' );
					} elseif ( strlen( trim( $base['subent'] ) ) > 3 || ( ! intval( $base['subent'] ) > 0 ) ) {
						$error_details = esc_html__( 'Subentity', 'multibanco-ifthen-software-gateway-for-woocommerce' );
					} elseif ( trim( $this->multibanco_settings['secret_key'] ) === '' ) {
						$error_details = esc_html__( 'Anti-phishing key', 'multibanco-ifthen-software-gateway-for-woocommerce' );
					}
					return esc_html__( 'Configuration error. This payment method is disabled because required information was not set.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' ' . $error_details . '.';
				}
			}
		} else {
			return esc_html__( 'Configuration error. This order currency is not Euros (&euro;).', 'multibanco-ifthen-software-gateway-for-woocommerce' );
		}
		// phpcs:enable
	}

	/**
	 * Create Multibanco reference for deposit
	 *
	 * @param integer $order_id The Order ID.
	 */
	public function multibanco_get_ref_deposit( $order_id ) {
		// WooCommerce Deposits support - force ref creation again
		if ( $this->wc_deposits_active ) {
			$ref = $this->multibanco_get_ref( $order_id, true );
		}
	}

	/**
	 * Create a Multibanco reference, locally, with legacy Entity / Subentity
	 *
	 * @param string  $ent                  Entity.
	 * @param string  $subent               Subentity.
	 * @param string  $seed                 Seed to create the reference.
	 * @param float   $total                Value for the reference.
	 * @param integer $no_repeat_days       So not repeat the same reference in this amout of days.
	 * @param boolean $just_create_no_check Just create the reference and do not check if it exists already.
	 * @return array
	 */
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
			} elseif ( intval( $no_repeat_days ) > 0 ) {
				// No open orders but also check for special entities that do not allow references to be repeated on x days
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
			if ( $exists ) {
				// Reference exists - Let's try again
				$seed = $this->get_multibanco_ref_seed( false );
				$ref  = $this->multibanco_create_ref( $ent, $subent, $seed, $total, intval( $no_repeat_days ) );
			}
		}
		$this->debug_log_extra( $this->multibanco_id, 'multibanco_create_ref - Reference generated: ' . $ent . ' ' . $ref . ' ' . $total );
		return $ref;
	}

	/**
	 * Create a Multibanco reference, locally, with legacy Entity / Subentity
	 *
	 * @param string  $ent    Entity.
	 * @param string  $subent Subentity.
	 * @param integer $id     Seed to create the reference, normally the order ID.
	 * @return string
	 */
	public function multibanco_create_ref_no_check_digit( $ent, $subent, $id ) {
		$subent = str_pad( intval( $subent ), 3, '0', STR_PAD_LEFT );
		$id     = str_pad( intval( $id ), 6, '0', STR_PAD_LEFT );
		$ref    = $subent . $id;
		return $ref;
	}

	/**
	 * Get/Create Payshop Reference from the ifthenpay API
	 *
	 * @param integer $order_id     The Order ID.
	 * @param boolean $force_change If we should force a reference recreation.
	 * @return array or string with error.
	 */
	public function payshop_get_ref( $order_id, $force_change = false ) {
		$order          = wc_get_order( $order_id );
		$order_currency = $order->get_currency();
		if ( trim( $order_currency ) === 'EUR' ) {
			if (
				! $force_change
				&&
				$order_mb_details = $this->get_payshop_order_details( $order->get_id() ) // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure, Generic.CodeAnalysis.AssignmentInCondition.Found
			) {
				// Already created, return it!
				return $order_mb_details;
			} elseif ( $this->get_order_total_to_pay( $order ) < $this->payshop_min_value ) {
				// Value ok?
				return sprintf(
					/* translators: %1$s: payment method, %2$s: maximum value */
					esc_html__( 'It’s not possible to use %1$s to pay values under %2$s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'Payshop',
					wc_price( $this->payshop_min_value, array( 'currency' => 'EUR' ) )
				);
			} elseif ( $this->get_order_total_to_pay( $order ) > $this->payshop_max_value ) {
				// Value ok? (again)
				return sprintf(
					/* translators: %1$s: payment method, %2$s: minimum value */
					esc_html__( 'It’s not possible to use %1$s to pay values above %2$s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'Payshop',
					wc_price( $this->payshop_max_value, array( 'currency' => 'EUR' ) )
				);
			} else {
				$payshop = new WC_Payshop_IfThen_Webdados();
				if ( $payshop->webservice_set_pedido( $order->get_id() ) ) {
					return $this->get_payshop_order_details( $order->get_id() );
				} else {
					return esc_html__( 'Error contacting ifthenpay servers to create Payshop Payment', 'multibanco-ifthen-software-gateway-for-woocommerce' );
				}
			}
		} else {
			return esc_html__( 'Configuration error. This order currency is not Euros (&euro;).', 'multibanco-ifthen-software-gateway-for-woocommerce' );
		}
	}

	/**
	 * Create MB WAY payment on the ifthenpay API
	 *
	 * @param integer $order_id        The Order ID.
	 * @param string  $phone           The phone number.
	 * @param boolean $throw_exception If we should throw an exception instead of returning false.
	 * @throws Exception               Error message.
	 * @return bool
	 */
	public function mbway_webservice_set_pedido( $order_id, $phone, $throw_exception = false ) {
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase, WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$debug       = $this->mbway_settings['debug'] === 'yes';
		$debug_email = false;
		if ( $debug ) {
			$debug_email = trim( $this->mbway_settings['debug_email'] ) !== '' ? trim( $this->mbway_settings['debug_email'] ) : false;
		}
		$url               = $this->mbway_api_url;
		$order             = wc_get_order( $order_id );
		$mbwaykey          = apply_filters( 'multibanco_ifthen_base_mbwaykey', $this->mbway_settings['mbwaykey'], $order );
		$id_for_backoffice = apply_filters( 'ifthen_webservice_send_order_number_instead_id', false ) ? $order->get_order_number() : $order->get_id();
		$desc              = trim( get_bloginfo( 'name' ) );
		$desc              = substr( $desc, 0, MBWAY_IFTHEN_DESC_LEN - strlen( ' #' . $order->get_order_number() ) );
		$desc             .= ' #' . $order->get_order_number();
		$args              = array(
			'method'   => 'POST',
			'timeout'  => apply_filters( 'mbway_ifthen_webservice_timeout', 15 ),
			'blocking' => true,
			'headers'  => array(
				'Content-Type' => 'application/json; charset=utf-8',
			),
			'body'     => array(
				'mbWayKey'     => $mbwaykey,
				'orderId'      => (string) $id_for_backoffice,
				'amount'       => (string) WC_IfthenPay_Webdados()->get_order_total_to_pay_for_gateway( $order ),
				'mobileNumber' => $phone,
				'email'        => '', // Não usamos
				'description'  => $this->mb_webservice_filter_descricao( apply_filters( 'mbway_ifthen_webservice_desc', $desc, $order->get_id() ) ),
			),
		);
		$args['body']      = wp_json_encode( $args['body'] ); // Json not post variables
		$this->debug_log_extra( $this->mbway_id, '- Request payment with args: ' . wp_json_encode( $args ) );
		$debug_start_time = microtime( true );
		$response         = wp_remote_post( $url, $args );
		if ( is_wp_error( $response ) ) {
			$debug_msg       = '- Error contacting the ifthenpay servers - Order ' . $order->get_id() . ' - ' . $response->get_error_message();
			$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
			$this->debug_log( $this->mbway_id, $debug_msg, 'error', $debug_email, $debug_msg_email );
			if ( $throw_exception ) {
				throw new Exception(
					sprintf(
						/* translators: %s: payment method */
						esc_html__( 'An error occurred processing the %s Payment request - please try again', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'MB WAY'
					)
				);
			}
			return false;
		} elseif ( isset( $response['response']['code'] ) && intval( $response['response']['code'] ) === 200 && isset( $response['body'] ) && trim( $response['body'] ) !== '' ) {
			$body = json_decode( $response['body'] );
			if ( ! empty( $body ) ) {
				if ( trim( $body->Status ) === '000' ) {
					$id_pedido = trim( $body->RequestId );
					$valor     = floatval( $body->Amount );
					if ( $valor === round( floatval( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ) ), 2 ) ) {
						WC_IfthenPay_Webdados()->set_order_mbway_details(
							$order->get_id(),
							array(
								'mbwaykey'  => $mbwaykey,
								'id_pedido' => $id_pedido,
								'phone'     => $phone,
								'val'       => $valor,
							)
						);
						if ( $debug ) {
							$this->debug_log( $this->mbway_id, '- MB WAY payment request created on ifthenpay servers - Order ' . $order->get_id() . ' - id_pedido: ' . $id_pedido );
						}
						do_action( 'mbway_ifthen_created_reference', $id_pedido, $order->get_id(), $phone );
						$debug_elapsed_time = microtime( true ) - $debug_start_time;
						$this->debug_log_extra( $this->mbway_id, 'wp_remote_post + response handling took: ' . $debug_elapsed_time . ' seconds.' );
						return true;
					} else {
						$debug_msg       = '- Error contacting the ifthenpay servers - Order ' . $order->get_id() . ' - Incorrect "Valor"';
						$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
						if ( $debug ) {
							$this->debug_log( $this->mbway_id, $debug_msg, 'error', $debug_email, $debug_msg_email );
						}
						if ( $throw_exception ) {
							throw new Exception(
								sprintf(
									/* translators: %s: payment method */
									esc_html__( 'An error occurred processing the %s Payment request - please try again', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									'MB WAY'
								)
							);
						}
						return false;
					}
				} else {
					$debug_msg       = '- Error: ' . trim( $body->Status ) . ' ' . trim( $body->Message ) . ' - Order ' . $order->get_id();
					$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
					$this->debug_log( $this->mbway_id, $debug_msg, 'error', $debug_email, $debug_msg_email );
					if ( $throw_exception ) {
						throw new Exception(
							sprintf(
								/* translators: %s: payment method */
								esc_html__( 'An error occurred processing the %s Payment request - please try again', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								'MB WAY'
							)
							.
							(
								trim( $body->Status ) === '999'
								?
								' - ' . esc_html__( 'The provided phone number is probably not a MB WAY subscriber', 'multibanco-ifthen-software-gateway-for-woocommerce' )
								:
								''
							)
						);
					}
					return false;
				}
			} else {
				$debug_msg       = '- Response body is not JSON - Order ' . $order->get_id();
				$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
				$this->debug_log( $this->mbway_id, $debug_msg, 'error', $debug_email, $debug_msg_email );
				if ( $throw_exception ) {
					throw new Exception(
						sprintf(
							/* translators: %s: payment method */
							esc_html__( 'An error occurred processing the %s Payment request - please try again', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'MB WAY'
						)
					);
				}
				return false;
			}
		} else {
			$debug_msg       = '- Error contacting the ifthenpay servers - Order ' . $order->get_id() . ' - Incorrect response code: ' . $response['response']['code'];
			$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
			$this->debug_log( $this->mbway_id, $debug_msg, 'error', $debug_email, $debug_msg_email );
			if ( $throw_exception ) {
				throw new Exception(
					sprintf(
						/* translators: %s: payment method */
						esc_html__( 'An error occurred processing the %s Payment request - please try again', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'MB WAY'
					)
				);
			}
			return false;
		}
		return false;
		// phpcs:enable
	}

	/**
	 * Force Reference creation on New Order (not the British Synthpop band)
	 * Classic checkout by the woocommerce_checkout_update_order_meta action and forced on the blocks checkout
	 *
	 * @param integer $order_id The order ID.
	 */
	public function multibanco_woocommerce_checkout_update_order_meta( $order_id ) {
		$order = wc_get_order( $order_id );
		// Avoid duplicate instructions on the email...
		if ( $order ) {
			if ( $order->get_payment_method() === $this->multibanco_id ) {
				$this->debug_log_extra( $this->multibanco_id, 'multibanco_woocommerce_checkout_update_order_meta - Force ref generation before anything - Order ' . $order->get_id() );
				$ref = $this->multibanco_get_ref( $order->get_id(), false, true );
				// That should do it...
				$this->debug_log_extra( $this->multibanco_id, 'multibanco_woocommerce_checkout_update_order_meta - Ref: ' . wp_json_encode( $ref ) . ' - Order ' . $order->get_id() );
			}
		} else {
			$this->debug_log_extra( $this->multibanco_id, 'multibanco_woocommerce_checkout_update_order_meta - Could not get order - Order ' . $order_id );
		}
	}

	/**
	 * Get total to pay for an order
	 *
	 * @param WC_Order $order The order.
	 * @return float
	 */
	public function get_order_total_to_pay( $order ) {
		$order_total_to_pay = $order->get_total();
		if ( $this->wc_deposits_active ) {
			// Has deposit
			if ( $order->get_meta( '_wc_deposits_order_has_deposit' ) === 'yes' ) {
				// First payment?
				if ( $order->get_meta( '_wc_deposits_deposit_paid' ) !== 'yes' && $order->get_status() !== 'partially-paid' ) {
					$order_total_to_pay = floatval( $order->get_meta( '_wc_deposits_deposit_amount' ) );
				} else {
					// Second payment
					$order_total_to_pay = floatval( $order->get_meta( '_wc_deposits_second_payment' ) );
				}
			}
		}
		return round( $order_total_to_pay, 2 );
	}

	/**
	 * Get total to pay for an order, formated for gateway
	 *
	 * @since 10.0.0
	 * @param WC_Order $order The order.
	 * @return float
	 */
	public function get_order_total_to_pay_for_gateway( $order ) {
		$value = $this->get_order_total_to_pay( $order );
		// Floatval and round
		$value = round( floatval( $value ), 2 );
		// Number format, just in case some very weird LOCALE is setting "," as decimal separator
		$value = number_format( $value, 2, '.', '' );
		// Return
		return $value;
	}

	/**
	 * Check if order type is valid for payments
	 *
	 * @param WC_Order $order_object The order.
	 * @return boolean
	 */
	public function is_valid_order_type( $order_object ) {
		if ( in_array(
			get_class( $order_object ),
			apply_filters(
				'multibanco_ifthen_valid_order_classes',
				array(
					'WC_Order',
					'Automattic\WooCommerce\Admin\Overrides\Order',
				)
			),
			true
		) ) {
			return true;
		}
		return false;
	}

	/**
	 * Change Ref if order total is changed on wp-admin
	 *
	 * @param WC_Order $order The order.
	 */
	public function multibanco_maybe_value_changed( $order ) {
		// phpcs:disable PEAR.Functions.FunctionCallSignature.Indent

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
						$order_mb_details   = $this->get_multibanco_order_details( $order->get_id() );
						if (
							( empty( $order_mb_details ) )
							||
							(
								round( floatval( $order_total_to_pay ), 2 ) !== round( floatval( $order_mb_details['val'] ), 2 )
								&&
								$order_status !== 'partially-paid' // If it's partially paid the value will be diferent and we need to ignore it
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
											/* translators: %s: payment method */
											esc_html__( 'The %s payment details have changed', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
											'Multibanco'
										) . ':
- - - - - - - - - - - - - - - - - - - - -
' . esc_html__( 'Previous entity', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
' . esc_html__( 'Previous reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
' . esc_html__( 'Previous value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
- - - - - - - - - - - - - - - - - - - - -
' . esc_html__( 'New entity', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
' . esc_html__( 'New reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
' . esc_html__( 'New value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
- - - - - - - - - - - - - - - - - - - - -
' . sprintf(
									/* translators: %s: payment method */
									esc_html__( 'If the customer pays using the previous details, the payment will be accepted by the %s system, but the order will not be updated via callback.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									'Multibanco'
									),
										isset( $order_mb_details['ent'] ) ? trim( $order_mb_details['ent'] ) : '',
										isset( $order_mb_details['ref'] ) ? $this->format_multibanco_ref( $order_mb_details['ref'] ) : '',
										isset( $order_mb_details['val'] ) ? wc_price( $order_mb_details['val'], array( 'currency' => $order->get_currency() ) ) : '',
										trim( $ref['ent'] ),
										$this->format_multibanco_ref( $ref['ref'] ),
										wc_price( $order_total_to_pay, array( 'currency' => $order->get_currency() ) )
									)
								);
								// Notify client?
								if ( $this->multibanco_settings['update_ref_client'] === 'yes' ) {
									WC()->payment_gateways(); // Just in case...
									$order->add_order_note(
										sprintf(
											sprintf(
												/* translators: %s: payment method */
												esc_html__( 'The %s payment details have changed', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
												'Multibanco'
											) . ':
' . esc_html__( 'New entity', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
' . esc_html__( 'New reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
' . esc_html__( 'New value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s',
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
										/* translators: %s: payment method */
										esc_html__( 'The %s payment details have changed', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
										'Multibanco'
											); ?>. <?php echo ( $this->multibanco_settings['update_ref_client'] === 'yes' ? esc_html__( 'The customer will be notified', 'multibanco-ifthen-software-gateway-for-woocommerce' ) : esc_html__( 'You should notify the customer', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ); ?>. <?php esc_html_e( 'The page will now reload.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
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
						$order_mb_details   = $this->get_payshop_order_details( $order->get_id() );
						if (
							( empty( $order_mb_details ) )
							||
							(
								round( floatval( $order_total_to_pay ), 2 ) !== round( floatval( $order_mb_details['val'] ), 2 )
								&&
								$order_status !== 'partially-paid' // If it's partially paid the value will be diferent and we need to ignore it
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
											/* translators: %s: payment method */
											esc_html__( 'The %s payment details have changed', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
											'Payshop'
										) . ':
- - - - - - - - - - - - - - - - - - - - -
' . esc_html__( 'Previous reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
' . esc_html__( 'Previous value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
- - - - - - - - - - - - - - - - - - - - -
' . esc_html__( 'New reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
' . esc_html__( 'New value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
- - - - - - - - - - - - - - - - - - - - -
' . sprintf(
									/* translators: %s: payment method */
									esc_html__( 'If the customer pays using the previous details, the payment will be accepted by the %s system, but the order will not be updated via callback.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
										'Payshop'
										),
										isset( $order_mb_details['ref'] ) ? $this->format_payshop_ref( $order_mb_details['ref'] ) : '',
										isset( $order_mb_details['val'] ) ? wc_price( $order_mb_details['val'], array( 'currency' => $order->get_currency() ) ) : '',
										$this->format_payshop_ref( $ref['ref'] ),
										wc_price( $order_total_to_pay, array( 'currency' => $order->get_currency() ) )
									)
								);
								// Notify client?
								if ( $this->payshop_settings['update_ref_client'] === 'yes' ) {
									WC()->payment_gateways(); // Just in case...
									$order->add_order_note(
										sprintf(
											sprintf(
												/* translators: %s: payment method */
												esc_html__( 'The %s payment details have changed', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
												'Payshop'
											) . ':
' . esc_html__( 'New reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s
' . esc_html__( 'New value', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ': %s',
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
										/* translators: %s: payment method */
										esc_html__( 'The %s payment details have changed', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
										'Payshop'
											); ?>. <?php echo ( $this->payshop_settings['update_ref_client'] === 'yes' ? esc_html__( 'The customer will be notified', 'multibanco-ifthen-software-gateway-for-woocommerce' ) : esc_html__( 'You should notify the customer', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ); ?>. <?php esc_html_e( 'The page will now reload.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>' );
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
		// phpcs:enable PEAR.Functions.FunctionCallSignature.Indent
	}

	/**
	 * Filter to be able to use wc_get_orders with our Multibanco and MB WAY references
	 * HPOS compatibility via the maybe_translate_order_query_args function
	 *
	 * @param array   $query      The query.
	 * @param array   $query_vars The query variables.
	 * @param object  $the_object Normally the order.
	 * @param boolean $clear_key  If key should be removed - for HPOS.
	 * @return array
	 */
	public function multibanco_woocommerce_order_data_store_cpt_get_orders_query( $query, $query_vars, $the_object, $clear_key = false ) {
		if ( ! isset( $query['meta_query'] ) ) {
			$query['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
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
					case '_' . $this->gateway_ifthen_id . '_id':
					case '_' . $this->gateway_ifthen_id . '_wd_secret':
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
	 * @param array $args The arguments.
	 * @return array
	 */
	public function maybe_translate_order_query_args( $args ) {
		if ( $this->hpos_enabled ) {
			if ( ! isset( $args['meta_query'] ) ) {
				$args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			}
			$args = $this->multibanco_woocommerce_order_data_store_cpt_get_orders_query( $args, $args, null, true );
		}
		return $args;
	}

	/**
	 * Reduce stock - on 'woocommerce_payment_complete_reduce_order_stock'
	 *
	 * @param bool    $reduce         If the stock should be reduced.
	 * @param integer $order_id       The order ID.
	 * @param string  $payment_method The payment methid used.
	 * @param string  $stock_when     Setting of when the stock should be reduced.
	 * @return bool
	 */
	public function woocommerce_payment_complete_reduce_order_stock( $reduce, $order_id, $payment_method, $stock_when ) {
		if ( $reduce ) {
			$order = wc_get_order( $order_id );
			if ( $order->get_payment_method() === $payment_method ) {
				// After 3.4.0
				if ( $this->order_needs_payment( $order ) ) {
					// Pending payment
					if ( $stock_when === 'order' ) {
						// Yes, because we want to reduce on the order
						return true;
					} else {
						return false;
					}
				} elseif ( $stock_when === '' ) {
					// Payment done
					// Yes, because we want to reduce on payment
					return true;
				} else {
					return false;
				}
			} else {
				return $reduce;
			}
		} else {
			// Already reduced
			return false;
		}
	}

	/**
	 * Cancel our orders when WooCommerce cancels pending orders (even if ours are on-hold)
	 * Cancel unpaid orders - See WooCommerce wc_cancel_unpaid_orders()
	 */
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
							$unpaid_order->update_status( 'cancelled', esc_html__( 'Unpaid order cancelled - time limit exceeded.', 'woocommerce' ) ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
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
									$product = $item->get_product();
									if ( ! empty( $product ) ) {
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

	/**
	 * Cancel expired orders for a specific payment method
	 *
	 * @param string $method_id The payment methid ID.
	 * @param string $datetime  The datetime to compare with the expiration meta (in Y-m-d H:i:s format) - if null, it will use the current datetime, allowing for time delays.
	 */
	public function cancel_expired_orders( $method_id, $datetime = null ) {
		// Accept time delays
		if ( ! $datetime ) {
			$datetime = date_i18n( 'Y-m-d H:i:s' );
		}
		// We are not doing this on the gateway itself because the cron doesn't always load the gateways
		$args           = array(
			'status'                  => array( 'on-hold', 'pending' ), // Aqui não usamos os unpaid statuses porque podemos entrar num loop se alguém adicionar o estado cancelada e também porque não faz sentido para parcialmente pagas
			'type'                    => array( 'shop_order' ),
			'limit'                   => -1,
			'payment_method'          => $method_id,
			'_' . $method_id . '_exp' => $datetime,
		);
		$expired_orders = WC_IfthenPay_Webdados()->wc_get_orders( $args, $method_id );
		if ( $expired_orders ) {
			foreach ( $expired_orders as $expired_order ) {
				$expired_order->update_status( 'cancelled', esc_html__( 'Unpaid order cancelled - Payment reference expired.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) );
				// The stocks are automatically restored by wc_maybe_increase_stock_levels via the 'woocommerce_order_status_cancelled' action
			}
		}
	}

	/**
	 * Multibanco cancel expired orders if incremental_expire mode is active
	 */
	public function multibanco_cancel_expired_orders() {
		$this->cancel_expired_orders( $this->multibanco_id );
	}

	/**
	 * Multibanco SMS instructions - General. Can be used to feed any SMS gateway/plugin
	 *
	 * @param string  $message  The SMS message.
	 * @param integer $order_id The order ID.
	 */
	public function multibanco_sms_instructions( $message, $order_id ) {
		$order        = wc_get_order( $order_id );
		$instructions = ''; // We return an empty string so that we always replace our placeholder, even if it's not our gateway
		if ( $order->get_payment_method() === $this->multibanco_id ) {
			if ( $this->order_needs_payment( $order ) ) {
				$ref = $this->multibanco_get_ref( $order->get_id() );
				if ( is_array( $ref ) ) {
					$instructions =
						'Multibanco'
						. ' '
						. esc_html__( 'Ent.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
						. ' '
						. $ref['ent']
						. ' '
						. esc_html__( 'Ref.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
						. ' ' . $this->format_multibanco_ref( $ref['ref'] )
						. ' '
						. esc_html__( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' )
						. ' '
						. $ref['val'];
					// Filters in case the website owner wants to customize the message
					$instructions = apply_filters( 'multibanco_ifthen_sms_instructions', $instructions, $ref['ent'], $ref['ref'], $ref['val'], $order->get_id() );
				}
			}
		}
		// Clean
		$instructions = trim( preg_replace( '/\s+/', ' ', str_replace( '&nbsp;', ' ', $instructions ) ) );
		// Return
		return $instructions;
	}

	/**
	 * Multibanco APG SMS integration (Removed from the class because now APG also sends scheduled SMS and the payment class may not be initiated)
	 *
	 * @param string  $message  The SMS message.
	 * @param integer $order_id The order ID.
	 */
	public function multibanco_apg_sms_message( $message, $order_id ) {
		$replace = $this->multibanco_sms_instructions( $message, $order_id ); // Get instructions
		return trim( preg_replace( '/\s+/', ' ', str_replace( '%multibanco_ifthen%', $replace, $message ) ) ); // Return message with %multibanco_ifthen% replaced by the instructions
	}

	/**
	 * Payshop SMS instructions - General. Can be used to feed any SMS gateway/plugin
	 *
	 * @param string  $message  The SMS message.
	 * @param integer $order_id The order ID.
	 */
	public function payshop_sms_instructions( $message, $order_id ) {
		$order        = wc_get_order( $order_id );
		$instructions = ''; // We return an empty string so that we always replace our placeholder, even if it's not our gateway
		if ( $order->get_payment_method() === $this->payshop_id ) {
			if ( $this->order_needs_payment( $order ) ) {
				$ref = $this->payshop_get_ref( $order->get_id() );
				if ( is_array( $ref ) ) {
					$instructions =
						'Payshop'
						. ' '
						. esc_html__( 'Ref.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
						. ' ' . $this->format_payshop_ref( $ref['ref'] )
						. ' '
						. esc_html__( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' )
						. ' '
						. $ref['val'];
					if ( isset( $ref['exp'] ) && trim( $ref['exp'] ) !== '' ) {
						$instructions .= ' ' . esc_html__( 'Valid.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' ' . $ref['exp'];
					}
					// Filters in case the website owner wants to customize the message
					$instructions = apply_filters( 'payshop_ifthen_sms_instructions', $instructions, $ref['ref'], $ref['val'], $ref['exp'], $order->get_id() );
				}
			}
		}
		// Clean
		$instructions = trim( preg_replace( '/\s+/', ' ', str_replace( '&nbsp;', ' ', $instructions ) ) );
		// Return
		return $instructions;
	}

	/**
	 * Payshop APG SMS integration (Removed from the class because now APG also sends scheduled SMS and the payment class may not be initiated)
	 *
	 * @param string  $message  The SMS message.
	 * @param integer $order_id The order ID.
	 */
	public function payshop_apg_sms_message( $message, $order_id ) {
		$replace = $this->payshop_sms_instructions( $message, $order_id ); // Get instructions
		return trim( preg_replace( '/\s+/', ' ', str_replace( '%payshop_ifthen%', $replace, $message ) ) ); // Return message with %multibanco_ifthen% replaced by the instructions
	}

	/**
	 * WooCommece Subscriptions - Do not copy our fields for renewal and resubscribe orders
	 *
	 * @param string   $meta_query The SQL query to fetch the meta data to be copied.
	 * @param WC_Order $to_order   The object to copy data to.
	 * @param WC_Order $from_order The object to copy data from.
	 */
	public function multibanco_wcs_filter_meta( $meta_query, $to_order, $from_order ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
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
			// ifthenpay Gateway
			'_' . $this->gateway_ifthen_id,
		);
		foreach ( $meta_query as $key => $value ) {
			if ( isset( $value['meta_key'] ) ) {
				foreach ( $mb_fields as $field ) {
					if ( strpos( $value['meta_key'], $field ) !== false && strpos( $value['meta_key'], $field ) === 0 ) { // Check if it starts with our field names
						unset( $meta_query[ $key ] );
						break;
					}
				}
			}
		}
		return $meta_query;
	}

	/**
	 * WooCommerce Subscriptions - Set renewal order on hold
	 *
	 * @param WC_Order        $renewal_order The renewal order.
	 * @param WC_Subscription $subscription  The parent subscription.
	 */
	public function multibanco_wcs_renewal_order_created( $renewal_order, $subscription ) {
		if ( ! is_object( $subscription ) ) {
			$subscription = wcs_get_subscription( $subscription );
		}
		if ( ! is_object( $renewal_order ) ) {
			$renewal_order = wc_get_order( $renewal_order );
		}
		if ( is_a( $renewal_order, 'WC_Order' ) && wcs_is_subscription( $subscription ) ) {
			$subscription_payment_method = $subscription->get_payment_method();
			if ( $subscription_payment_method === $this->multibanco_id ) { // Subscription was inially paid by Multibanco?
				if ( $this->multibanco_settings['support_woocommerce_subscriptions'] === 'yes' ) {
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

	/**
	 * Maybe change locale on emails
	 *
	 * @param WC_Order $order The order.
	 */
	public function maybe_change_locale( $order ) {
		if ( apply_filters( 'multibanco_ifthen_maybe_change_email_locale', false ) ) { // Since 2025-03-14 only try this if forced by filter
			if ( $this->wpml_active ) {
				// Just for WPML
				global $sitepress;
				if ( $sitepress ) {
					$lang = $order->get_meta( 'wpml_language' );
					if ( ! empty( $lang ) ) {
						$this->locale = $sitepress->get_locale( $lang );
					}
				}
			} elseif ( is_admin() ) {
				// Store language !== current user/admin language?
				$current_user_lang = get_user_locale( wp_get_current_user() );
				if ( $current_user_lang !== get_locale() ) {
					$this->locale = get_locale();
				}
			}
			if ( ! empty( $this->locale ) ) {
				// Unload
				unload_textdomain( 'multibanco-ifthen-software-gateway-for-woocommerce' );
				add_filter( 'plugin_locale', array( $this, 'set_locale_for_emails' ), 10, 2 ); // This is not running, it's probably too late
				load_plugin_textdomain( 'multibanco-ifthen-software-gateway-for-woocommerce' );
				remove_filter( 'plugin_locale', array( $this, 'set_locale_for_emails' ), 10, 2 );
			}
		}
	}
	/**
	 * Set locale for emails
	 * Just like WooCommerce Multilingual WCML_Emails
	 *
	 * @param string $locale The locale.
	 * @param string $domain The textdomain.
	 */
	public function set_locale_for_emails( $locale, $domain ) {
		if ( $domain === 'multibanco-ifthen-software-gateway-for-woocommerce' && $this->locale ) {
			$locale = $this->locale;
		}
		return $locale;
	}

	/**
	 * WPML AJAX fix locale
	 */
	public function wpml_ajax_fix_locale() {
		// If WPML is present and we're loading via ajax, let's try to fix the locale
		if ( $this->wpml_active ) {
			if ( function_exists( 'wpml_is_ajax' ) && wpml_is_ajax() ) {  // We check the function because we may be using Polylang
				if ( ICL_LANGUAGE_CODE !== 'en' ) {
					add_filter( 'plugin_locale', array( $this, 'wpml_ajax_fix_locale_do_it' ), 1, 2 );
				}
			}
		}
	}

	/**
	 * WPML AJAX fix locale
	 * This should NOT be needed! - Check with WooCommerce Multilingual team
	 *
	 * @param string $locale The locale.
	 * @param string $domain The textdomain.
	 */
	public function wpml_ajax_fix_locale_do_it( $locale, $domain ) {
		if ( $domain === 'multibanco-ifthen-software-gateway-for-woocommerce' ) {
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

	/**
	 * Languages on Notes emails - We need to check if it's our order (Multibanco or MBWay)
	 *
	 * @param integer $order_id The order ID.
	 */
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

	/**
	 * Languages on Notes emails - Really do it
	 *
	 * @param integer $order_id The order ID.
	 */
	public function woocommerce_new_customer_note_fix_wpml_do_it( $order_id ) {
		global $sitepress;
		$order = wc_get_order( $order_id );
		$lang  = $order->get_meta( 'wpml_language' );
		if ( ! empty( $lang ) && $lang !== $sitepress->get_default_language() ) {
			$this->mb_ifthen_locale = $sitepress->get_locale( $lang ); // Set global to be used on wpml_ajax_fix_locale_do_it above
			add_filter( 'plugin_locale', array( $this, 'wpml_ajax_fix_locale_do_it' ), 1, 2 );
			load_plugin_textdomain( 'multibanco-ifthen-software-gateway-for-woocommerce' );
		}
	}

	/**
	 * Right sidebar on payment gateway settings
	 */
	public function admin_right_bar() {
		// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterOpen, Squiz.PHP.EmbeddedPhp.ContentBeforeOpen, Squiz.PHP.EmbeddedPhp.ContentAfterEnd
		?>
		<div id="wc_ifthen_rightbar">
			<h4><?php esc_html_e( 'Commercial information', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</h4>
			<p>
				<?php
				$title = sprintf(
					/* translators: %s: company name */
					esc_html__( 'Please contact %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'ifthenpay'
				);
				?>
				<a href="https://ifthenpay.com/<?php echo esc_attr( $this->out_link_utm ); ?>" title="<?php echo esc_attr( $title ); ?>" target="_blank">
					<img src="<?php echo esc_url( plugins_url( 'images/ifthenpay.svg', __FILE__ ) ); ?>" width="200"/>
				</a>
			</p>
			<h4><?php esc_html_e( 'Development and premium technical support', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</h4>
			<p>
				<?php
				$title = sprintf(
					/* translators: %s: company name */
					esc_html__( 'Please contact %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'PT Woo Plugins'
				);
				?>
				<a href="https://nakedcatplugins.com<?php echo esc_attr( $this->out_link_utm ); ?>" title="<?php echo esc_attr( $title ); ?>" target="_blank">
					<img src="<?php echo esc_url( plugins_url( 'images/nakedcatplugins-plugins-for-wordpress.svg', __FILE__ ) ); ?>" width="200"/>
				</a>
			</p>
			<h4><?php esc_html_e( 'Custom WordPress/WooCommerce development', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</h4>
			<p>
				<?php
				$title = sprintf(
					/* translators: %s: company name */
					esc_html__( 'Please contact %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'Webdados'
				);
				?>
				<a href="https://www.webdados.pt/contactos/<?php echo esc_attr( $this->out_link_utm ); ?>" title="<?php echo esc_attr( $title ); ?>" target="_blank">
					<img src="<?php echo esc_url( plugins_url( 'images/webdados.svg', __FILE__ ) ); ?>" width="200"/>
				</a>
			</p>
			<h4><?php esc_html_e( 'Free technical support', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</h4>
			<p style="text-align: center">
				<a href="https://wordpress.org/support/plugin/multibanco-ifthen-software-gateway-for-woocommerce/" target="_blank">
					<?php esc_html_e( 'WordPress.org forum', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
				</a>
			</p>
			<h4><?php esc_html_e( 'Please rate our plugin', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</h4>
			<a href="https://wordpress.org/support/view/plugin-reviews/multibanco-ifthen-software-gateway-for-woocommerce?filter=5#postform" target="_blank" style="text-align: center;">
				<div class="star-rating"><div class="star star-full"></div><div class="star star-full"></div><div class="star star-full"></div><div class="star star-full"></div><div class="star star-full"></div></div>
			</a>
			<div class="clear"></div>
			<div id="wc_ifthen_rightbar_premium_plugins">
				<hr/>
				<h4><?php esc_html_e( 'Other premium plugins', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</h4>
				<ul id="wc_ifthen_premium_plugins">
					<?php
					// Custom fields
					$premium_plugins = array(
						array(
							'url'         => 'https://nakedcatplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/',
							'title'       => esc_html__( 'Multibanco, MB WAY, Credit card, Apple Pay, Google Pay, Payshop, Cofidis Pay, and PIX (ifthenpay) for WooCommerce - PRO add-on', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'short_title' => esc_html__( 'PRO add-on', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'image'       => 'addonpro.gif',
						),
						array(
							'url'         => 'https://invoicewoo.com/',
							'title'       => esc_html__( 'Invoicing with InvoiceXpress for WooCommerce - Pro', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'short_title' => esc_html__( 'InvoiceXpress', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'image'       => 'invoicexpress-woocommerce.png',
						),
						array(
							'url'         => 'https://nakedcatplugins.com/product/dpd-portugal-for-woocommerce/',
							'title'       => esc_html__( 'DPD (Chronopost/SEUR) Portugal for WooCommerce', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'short_title' => esc_html__( 'DPD Portugal', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'image'       => 'dpd-portugal.png',
						),
						array(
							'url'         => 'https://nakedcatplugins.com/product/dpd-seur-geopost-pickup-and-lockers-network-for-woocommerce/',
							'title'       => esc_html__( 'DPD / SEUR / Geopost Pickup and Lockers network for WooCommerce', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'short_title' => esc_html__( 'DPD Pickup', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'image'       => 'dpd-pickup.png',
						),
						array(
							'url'         => 'https://nakedcatplugins.com/product/portuguese-postcodes-for-woocommerce-technical-support/',
							'title'       => esc_html__( 'Portuguese Postcodes for WooCommerce', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'short_title' => esc_html__( 'Portuguese Postcodes', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'image'       => 'postcodes.png',
						),
						array(
							'url'         => 'https://www.webdados.pt/wordpress/plugins/feed-kuantokusta-para-woocommerce/',
							'title'       => esc_html__( 'Feed KuantoKusta for WooCommerce PRO add-on', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'short_title' => esc_html__( 'KuantoKusta', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'image'       => 'kuantokusta.png',
						),
						array(
							'url'         => 'https://nakedcatplugins.com/product/simple-custom-fields-for-woocommerce-blocks-checkout/',
							'title'       => esc_html__( 'Simple Checkout Fields Manager for WooCommerce', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'short_title' => esc_html__( 'Blocks Checkout Custom Fields', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'image'       => 'woo-custom-fields.png',
						),
						array(
							'url'         => 'https://nakedcatplugins.com/product/simple-woocommerce-order-approval/',
							'title'       => esc_html__( 'Simple WooCommerce Order Approval', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'short_title' => esc_html__( 'Order Approval', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'image'       => 'simple-woocommerce-order-approval-logo.png',
						),
						array(
							'url'         => 'https://nakedcatplugins.com/product/shop-as-client-for-woocommerce-pro-add-on/',
							'title'       => esc_html__( 'Shop as Client for WooCommerce PRO add-on', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'short_title' => esc_html__( 'Shop as Client', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'image'       => 'shop-as-client.png',
						),
						array(
							'url'         => 'https://nakedcatplugins.com/product/taxonomy-term-and-role-based-discounts-for-woocommerce-pro-add-on/',
							'title'       => esc_html__( 'Taxonomy/Term and Role based Discounts for WooCommerce', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'short_title' => esc_html__( 'Taxonomy based Discounts', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'image'       => 'taxonomy-discounts.png',
						),
					);
					foreach ( $premium_plugins as $premium_plugin ) {
						?>
						<li>
							<a href="<?php echo esc_url( $premium_plugin['url'] . $this->out_link_utm ); ?>" target="_blank" title="<?php echo esc_attr( $premium_plugin['title'] ); ?>">
								<img src="<?php echo esc_url( plugins_url( 'images/premium_plugins/' . $premium_plugin['image'], __FILE__ ) ); ?>" width="200" height="200" alt="<?php echo esc_attr( $premium_plugin['title'] ); ?>"/>
								<small><?php echo esc_html( $premium_plugin['short_title'] ); ?></small>
							</a>
						</li>
						<?php
					}
					?>
				</ul>
				<div class="clear"></div>
			</div>
		</div>
		<?php
		// phpcs:enable
	}

	/**
	 * Pro add-on banner
	 */
	public function admin_pro_banner() {
		if ( ! $this->pro_add_on_active ) {
			?>
			<div class="wc_ifthen_pro_ad">
				<h4><?php esc_html_e( 'Want more features?', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</h4>
				<p>
					<a href="https://nakedcatplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/<?php echo esc_attr( $this->out_link_utm ); ?>" target="_blank" style="font-weight: bold;">
						<?php esc_html_e( 'Get the PRO add-on of Multibanco, MB WAY, Credit card, Apple Pay, Google Pay, Payshop, Cofidis Pay, and PIX (ifthenpay) for WooCommerce', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
					</a>
				</p>
			</div>
			<div class="clear"></div>
			<?php
		}
	}

	/**
	 * MB WAY Ajax order status, for the thank you page
	 */
	public function mbway_ajax_order_status() {
		$order_key = isset( $_POST['order_key'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['order_key'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $order_key ) ) {
			$order_id      = wc_get_order_id_by_order_key( $order_key );
			$post_order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( intval( $order_id ) > 0 && $post_order_id === intval( $order_id ) ) {
				$order   = wc_get_order( intval( $order_id ) );
				$expired = false;
				if ( ! in_array( $order->get_status(), array( 'processing', 'completed' ), true ) ) {
					if ( date_i18n( 'Y-m-d H:i:s', strtotime( '-' . intval( $this->mbway_minutes * $this->mbway_multiplier_new_payment * 60 ) . ' SECONDS', current_time( 'timestamp' ) ) ) > $order->get_meta( '_' . $this->mbway_id . '_time' ) ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
						$expired = true;
					}
				}
				echo wp_json_encode(
					array(
						'order_status' => $order->get_status(),
						'expired'      => $expired,
					)
				);
			} else {
				echo wp_json_encode(
					array(
						'order_status' => '',
						'expirde'      => '',
					)
				);
			}
		}
		die();
	}

	/**
	 * Cofidis Pay Ajax order status, for the thank you page
	 */
	public function cofidispay_ajax_order_status() {
		$order_key = isset( $_POST['order_key'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['order_key'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $order_key ) ) {
			$order_id      = wc_get_order_id_by_order_key( $order_key );
			$post_order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( intval( $order_id ) > 0 && $post_order_id === intval( $order_id ) ) {
				$order = wc_get_order( intval( $order_id ) );
				echo wp_json_encode(
					array( 'order_status' => $order->get_status() )
				);
			} else {
				echo wp_json_encode(
					array( 'order_status' => '' )
				);
			}
		}
		die();
	}

	/**
	 * The ifthenpay Gateway Ajax order status, for the thank you page
	 */
	public function gatewayifthenpay_ajax_order_status() {
		$order_key = isset( $_POST['order_key'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['order_key'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $order_key ) ) {
			$order_id      = wc_get_order_id_by_order_key( $order_key );
			$post_order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( intval( $order_id ) > 0 && $post_order_id === intval( $order_id ) ) {
				$order = wc_get_order( intval( $order_id ) );
				echo wp_json_encode(
					array( 'order_status' => $order->get_status() )
				);
			} else {
				echo wp_json_encode(
					array( 'order_status' => '' )
				);
			}
		}
		die();
	}

	/**
	 * MB WAY - Request payment again
	 */
	public function wp_ajax_mbway_ifthen_request_payment_again() {
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'mbway_ifthen_request_payment_again' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( isset( $_REQUEST['order_id'] ) && intval( $_REQUEST['order_id'] ) > 0 && isset( $_REQUEST['order_id'] ) ) {
				$order = wc_get_order( intval( $_REQUEST['order_id'] ) );
				$mbway = new WC_MBWAY_IfThen_Webdados();
				$phone = isset( $_REQUEST['phone'] ) ? trim( sanitize_text_field( wp_unslash( $_REQUEST['phone'] ) ) ) : '';
				if ( ( ! empty( $phone ) ) && $mbway->webservice_set_pedido( $order->get_id(), $phone ) ) {
					echo wp_json_encode(
						array(
							'status'  => 1,
							'message' => esc_html__( 'MB WAY Payment has been requested', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						)
					);
				} else {
					echo wp_json_encode(
						array(
							'status' => 0,
							'error'  => esc_html__( 'Error contacting ifthenpay servers to create MB WAY Payment', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						)
					);
				}
			} else {
				echo wp_json_encode(
					array(
						'status' => 0,
						'error'  => esc_html__( 'Invalid parameters', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					)
				);
			}
		} else {
			echo wp_json_encode(
				array(
					'status' => 0,
					'error'  => esc_html__( 'Error', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
				)
			);
		}
		wp_die();
	}


	/**
	 * Order needs payment - valid statuses
	 *
	 * @since 4.4.0
	 * @param array    $statuses The valid statuses.
	 * @param WC_Order $order The order.
	 */
	public function woocommerce_valid_order_statuses_for_payment( $statuses, $order ) {
		if ( $this->order_has_ifthenpay_method( $order ) ) {
			$statuses = array_unique( array_merge( $statuses, $this->unpaid_statuses ) );
		}
		return $statuses;
	}

	/**
	 * Valid ifthenpay method
	 *
	 * @param WC_Order $order The order.
	 */
	public function order_has_ifthenpay_method( $order ) {
		return in_array(
			$order->get_payment_method(),
			array(
				$this->multibanco_id,
				$this->mbway_id,
				$this->creditcard_id,
				$this->payshop_id,
				$this->cofidispay_id,
				$this->gateway_ifthen_id,
			),
			true
		);
	}


	/**
	 * Hide Pay button on orders list
	 * Actually it's onlye hidden if thesite owner passes true to our filters
	 *
	 * @since 4.4.0
	 * @param array    $actions The current actions.
	 * @param WC_Order $order The order.
	 * @return array
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
					break;
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
				case $this->gateway_ifthen_id:
					if ( apply_filters( 'gateway_ifthen_hide_my_account_pay_button', false ) ) {
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
	 * @param string $bo_key       The backoffice key.
	 * @param string $ent          The entity or payment method.
	 * @param string $subent       The subentity or payment method key.
	 * @param string $secret_key   The secret key to be set.
	 * @param string $callback_url The callback url to be set.
	 */
	public function callback_webservice( $bo_key, $ent, $subent, $secret_key, $callback_url ) {
		$result   = array(
			'success' => false,
			'message' => '',
		);
		$args     = array(
			'method'   => 'POST',
			'timeout'  => apply_filters( 'ifthen_callback_webservice_timeout', 15 ),
			'blocking' => true,
			'headers'  => array(
				'content-type' => 'application/json',
			),
			'body'     => wp_json_encode(
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
			$result['message'] = esc_html__( 'Unknown error 1', 'multibanco-ifthen-software-gateway-for-woocommerce' );
		} elseif ( isset( $response['response']['code'] ) ) {
			switch ( $response['response']['code'] ) {
				case 200:
					$result['success'] = true;
					break;
				default:
					$result['message'] = trim( $response['body'] );
					break;
			}
		} else {
			$result['message'] = esc_html__( 'Unknown error 2', 'multibanco-ifthen-software-gateway-for-woocommerce' );
		}
		return $result;
	}

	/**
	 * Check if order needs payment
	 *
	 * @since 4.4.1
	 * @param WC_Order $order The order.
	 * @return bool
	 */
	public function order_needs_payment( $order ) {
		return $order->needs_payment() || $order->get_status() === 'on-hold' || $order->get_status() === 'pending';
	}

	/**
	 * Process MBWAY and CC refunds
	 *
	 * @since 7.0
	 * @param integer $order_id  The order ID.
	 * @param float   $amount    Amount to refund.
	 * @param string  $reason    The reason to make the refund.
	 * @param string  $method_id The payment method.
	 */
	public function process_refund( $order_id, $amount, $reason, $method_id ) {
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase, WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$order = wc_get_order( $order_id );
		$this->debug_log( $method_id, '-- Processing refund - Order ' . $order->get_id(), 'notice' );
		// Only works at method level
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
			case $this->gateway_ifthen_id:
				$order_details  = WC_IfthenPay_Webdados()->get_gatewayifthenpay_order_details( $order->get_id() );
				$request_id     = trim( $order_details['request_id'] );
				$backoffice_key = trim( $this->gateway_ifthen_settings['backoffice_key'] );
				break;
		}
		$args = array(
			'method'   => 'POST',
			'timeout'  => apply_filters( 'refund_ifthen_api_timeout', 30 ),
			'blocking' => true,
			'headers'  => array(
				'Content-Type' => 'application/json; charset=utf-8',
			),
			'body'     => array(
				'backofficekey' => $backoffice_key,
				'requestId'     => $request_id,
				'amount'        => (string) round( floatval( $amount ), 2 ),
			),
		);
		$this->debug_log_extra( $method_id, '- Request refund with args: ' . wp_json_encode( $args ) );
		$args['body'] = wp_json_encode( $args['body'] );
		$response     = wp_remote_post( $this->refunds_url, $args );
		if ( is_wp_error( $response ) ) {
			$debug_msg       = '- Error contacting the ifthenpay servers - Order ' . $order->get_id() . ' - ' . $response->get_error_message();
			$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
			$this->debug_log( $method_id, '-- ' . $debug_msg, 'error', true, $debug_msg_email );
			return new WP_Error( 'error', $response->get_error_message() );
		} elseif ( isset( $response['response']['code'] ) && intval( $response['response']['code'] ) === 200 && isset( $response['body'] ) && trim( $response['body'] ) !== '' ) {
			$body = json_decode( $response['body'] );
			if ( ! empty( $body ) ) {
				if ( trim( $body->Code ) === '1' ) {
					return true;
				} else {
					$debug_msg       = '- Error from ifthenpay: ' . trim( $body->Message ) . ' (' . $body->Code . ') - Order ' . $order->get_id();
					$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
					$this->debug_log( $method_id, $debug_msg, 'error', true, $debug_msg_email );
					return new WP_Error(
						'error',
						__( 'We are sorry, but it was not possible to issue the refund. Please contact the ifthenpay support.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' - (' . trim( $body->Message ) . ')'
					);
				}
			} else {
				$debug_msg       = '- Response body is not JSON - Order ' . $order->get_id();
				$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
				$this->debug_log( $method_id, $debug_msg, 'error', true, $debug_msg_email );
				return new WP_Error( 'error', $debug_msg );
			}
		} else {
			$debug_msg       = '- Error contacting the ifthenpay servers - Order ' . $order->get_id() . ' - Incorrect response code: ' . $response['response']['code'];
			$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
			$this->debug_log( $method_id, $debug_msg, 'error', true, $debug_msg_email );
			return new WP_Error( 'error', $debug_msg );
		}
		// phpcs:enable
	}

	/**
	 * Check if Deposit has our payment method
	 *
	 * @since 8.4.0
	 * @param WC_Order $order The order.
	 * @param string   $method_id The payment method.
	 * @return WC_Order or false
	 */
	public function deposit_is_ifthenpay( $order, $method_id ) {
		if ( $this->wc_deposits_active ) {
			if ( $order->get_meta( '_wc_deposits_order_has_deposit' ) === 'yes' ) {
				$payment_schedule = $order->get_meta( '_wc_deposits_payment_schedule', true );
				// Deposit
				if ( is_array( $payment_schedule ) && isset( $payment_schedule['deposit'] ) && isset( $payment_schedule['deposit']['id'] ) && intval( $payment_schedule['deposit']['id'] ) > 0 ) {
					$order_deposit = wc_get_order( intval( $payment_schedule['deposit']['id'] ) );
					if ( $order_deposit ) {
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
					while ( $stop === false ) {
						if ( isset( $payment_schedule[ $i ] ) ) {
							$order_deposit = wc_get_order( intval( $payment_schedule[ $i ]['id'] ) );
							if ( $order_deposit ) {
								if ( $method_id === $order_deposit->get_payment_method() ) {
									if ( $this->order_needs_payment( $order_deposit ) ) {
										return $order_deposit;
									}
								}
							}
							++$i;
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
	 * @param WC_Order $order              The order.
	 * @param string   $status             The order status to be set.
	 * @param string   $payment_method     The payment methiod.
	 * @param string   $additional_message Additional information for the order notes.
	 */
	public function set_initial_order_status( $order, $status, $payment_method, $additional_message = '' ) {
		$note = sprintf(
			/* translators: %s: payment method */
			esc_html__( 'Awaiting %s payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
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
	 * @param array  $args       The arguments.
	 * @param string $gateway_id The payment gateway.
	 * @return array of WC_Order
	 */
	public function wc_get_orders( $args, $gateway_id ) {
		// Remove Polylang filter - https://wordpress.org/support/topic/encomenda-cancelada-mas-cobrada/
		if ( $this->polylang_active ) {
			$this->debug_log_extra( $gateway_id, 'wc_get_orders - Disabling Polylang filter' );
			remove_action( 'parse_query', array( PLL(), 'parse_query' ), 6 );
		}
		// Get the orders
		$orders = wc_get_orders( $this->maybe_translate_order_query_args( $args ) );
		// Log last query
		global $wpdb;
		$this->debug_log_extra( $gateway_id, 'wc_get_orders - Last query: ' . $wpdb->last_query );
		// Re-instate Polylang filter - https://wordpress.org/support/topic/encomenda-cancelada-mas-cobrada/
		if ( $this->polylang_active ) {
			$this->debug_log_extra( $gateway_id, 'wc_get_orders - Re-enabling Polylang filter' );
			add_action( 'parse_query', array( PLL(), 'parse_query' ), 6 );
		}
		return $orders;
	}

	/**
	 * Helper to find order in another state if not found pending payment or on hold
	 *
	 * @param string $gateway_id   The gateway ID.
	 * @param array  $args         The arguments used to search the order.
	 * @param string $gateway_name The gateway name.
	 * @return string Error message to be shown in the logs and order notes
	 */
	public function callback_helper_order_not_found_error( $gateway_id, $args, $gateway_name ) {
		$error = 'Error: No orders found awaiting payment with these details';
		// We should repeat the search without the status to see if the order is already paid or cancelled and warn the store owner + add order note
		unset( $args['status'] );
		$orders = WC_IfthenPay_Webdados()->wc_get_orders( $args, $gateway_id );
		if ( count( $orders ) > 0 ) {
			// Order(s) found but not pending
			$this->debug_log_extra( $gateway_id, '-- Callback search without pending statuses found ' . count( $orders ) . ' order(s)' );
			foreach ( $orders as $order ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedForeach
				// Just getting the last one
			}
			$error = sprintf(
				/* translators: 1: Payment method name. 2: Order status. */
				__( 'ifthenpay %1$s callback received but the order is not pending payment. Order status: %2$s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
				$gateway_name,
				wc_get_order_status_name( $order->get_status() )
			);
			$order->add_order_note( $error );
		}
		// Output
		return $error;
	}

	/**
	 * Get gateway title or description for blocks checkout
	 *
	 * Retrieves the payment gateway title or description that is properly translated
	 * when WPML is active. This helps ensure consistent payment method display
	 * across different languages in WooCommerce Blocks checkout.
	 *
	 * @param string $gateway_id       The payment gateway ID.
	 * @param array  $gateway_settings The gateway settings array containing titles and descriptions.
	 * @param string $field           The gateway field to get: 'title' or 'description'.
	 * @return string The translated title or description, or original if WPML is not active.
	 */
	public function get_gateway_title_or_description_for_blocks( $gateway_id, $gateway_settings, $field ) {
		if ( $this->wpml_active ) {
			$gateway_title_or_description = apply_filters(
				'wpml_translate_single_string',
				$gateway_settings[ $field ],
				'admin_texts_woocommerce_gateways',
				$gateway_id . '_gateway_' . $field
			);
			return trim( $gateway_title_or_description );
		}
		return $gateway_settings[ $field ];
	}

	/**
	 * Filter notify URLs
	 */
	public function filter_notify_urls() {
		$this->multibanco_notify_url     = apply_filters( 'multibanco_ifthen_notify_url', $this->multibanco_notify_url );
		$this->mbway_notify_url          = apply_filters( 'mbway_ifthen_notify_url', $this->mbway_notify_url );
		$this->payshop_notify_url        = apply_filters( 'payshop_ifthen_notify_url', $this->payshop_notify_url );
		$this->creditcard_notify_url     = apply_filters( 'credicard_ifthen_notify_url', $this->creditcard_notify_url );
		$this->cofidispay_notify_url     = apply_filters( 'cofidispay_ifthen_notify_url', $this->cofidispay_notify_url );
		$this->gateway_ifthen_notify_url = apply_filters( 'gateway_ifthen_notify_url', $this->gateway_ifthen_notify_url );
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
		$tab     = isset( $_GET['tab'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$section = isset( $_GET['section'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['section'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $tab !== 'checkout' || ! strpos( $section, 'ifthen_for_woocommerce' ) ) {
			return;
		}

		wp_enqueue_style( 'woocommerce_multibanco_ifthen_admin_css', plugins_url( 'assets/admin.css', __FILE__ ), array(), $this->get_version() . ( WP_DEBUG ? '.' . wp_rand( 0, 99999 ) : '' ) );

		wp_enqueue_script( 'woocommerce_multibanco_ifthen_admin_js', plugins_url( 'assets/admin.js', __FILE__ ), array( 'jquery' ), $this->get_version() . ( WP_DEBUG ? '.' . wp_rand( 0, 99999 ) : '' ), true );

		// Javascript variables
		$gateway             = str_replace( '_ifthen_for_woocommerce', '', $section );
		$callback_email_sent = get_option( $gateway . '_ifthen_for_woocommerce_callback_email_sent' );
		if ( $callback_email_sent === false ) {
			$callback_email_sent = 'no';
		}
		$callback_auto_open = 0;
		$callback_warning   = isset( $_GET['callback_warning'] ) ? intval( $_GET['callback_warning'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $callback_email_sent === 'no' && $callback_warning === 1 ) {
			$callback_auto_open = 1;
		}
		wp_localize_script(
			'woocommerce_multibanco_ifthen_admin_js',
			'ifthenpay',
			array(
				'gateway'             => $gateway,
				'callback_confirm'    => esc_html__( 'Are you sure you want to ask ifthenpay to activate the “Callback”?', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
				'callback_bo_key'     => esc_html__( 'Please provide the ifthenpay backoffice key you got after signing the contract', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
				'callback_email_sent' => $callback_email_sent,
				'callback_auto_open'  => $callback_auto_open,
				'backoffice_key'      => apply_filters( 'ifthen_backoffice_key', '' ),
			)
		);
	}

	/**
	 * Get countries with their phone prefixes for use in MB WAY phone selection
	 *
	 * Merges WooCommerce's allowed countries with international calling codes
	 * and sorts them alphabetically with the store's base country first.
	 *
	 * @since 11.0.0
	 * @return array Array of countries with their phone prefixes
	 */
	public static function get_countries_with_phone_prefixes() {
		// Get WooCommerce allowed countries
		$wc_countries = WC()->countries->get_allowed_countries();

		// Store base country - will be placed at the top
		$base_country = WC()->countries->get_base_country();

		$countries_with_prefixes = array();

		// Merge countries and calling codes
		foreach ( $wc_countries as $country_code => $country_name ) {
			$calling_code = WC()->countries->get_country_calling_code( $country_code );
			if ( ! empty( $calling_code ) ) {
				$prefix                                   = $calling_code;
				$countries_with_prefixes[ $country_code ] = array(
					'name'    => $country_name,
					'prefix'  => $prefix,
					'display' => sprintf( '%s (%s)', $country_name, $prefix ),
					'code'    => $country_code,
				);
			}
		}

		// Sort countries alphabetically
		uasort(
			$countries_with_prefixes,
			function ( $a, $b ) use ( $base_country ) {
				// Always put base country first
				if ( $a['code'] === $base_country ) {
					return -1;
				}
				if ( $b['code'] === $base_country ) {
					return 1;
				}
				// Use Collator if available (better Unicode support)
				if ( class_exists( 'Collator' ) ) {
					$collator = new Collator( get_locale() );
					return $collator->compare( $a['name'], $b['name'] );
				}
				// Fallback to case-insensitive comparison with UTF-8 support
				return strcmp(
					iconv( 'UTF-8', 'ASCII//TRANSLIT', $a['name'] ),
					iconv( 'UTF-8', 'ASCII//TRANSLIT', $b['name'] )
				);
			}
		);

		return $countries_with_prefixes;
	}

	/**
	 * Helper for outputing payment methods names from gateways
	 *
	 * @param string $method The payment method.
	 * @return string
	 */
	public function helper_format_method( $method ) {
		$method = trim( $method );
		if ( strlen( $method ) > 3 ) {
			$method = ucwords( strtolower( $method ) );
		}
		return $method;
	}

	/**
	 * Get $_SERVER['REQUEST_URI'] properly sanitized
	 *
	 * @return string
	 */
	public function get_request_uri() {
		return isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	}

	/**
	 * Get $_SERVER['REMOTE_ADDR'] properly sanitized
	 *
	 * @return string
	 */
	public function get_remote_addr() {
		if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) && filter_var( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ), FILTER_VALIDATE_IP ) ) {
			$_SERVER['REMOTE_ADDR'] = filter_var( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ), FILTER_VALIDATE_IP );
		}
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}

	/**
	 * Get $_SERVER['HTTP_HOST'] properly sanitized
	 *
	 * @return string
	 */
	public function get_http_host() {
		return isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
	}

	/**
	 * Handler for dismissing new payment method notification
	 *
	 * This method processes the AJAX request when a user dismisses the notification
	 * about a new payment method being available. It:
	 * 1. Verifies the security nonce
	 * 2. Gets the payment method ID from the request
	 * 3. Sets a transient with 90-day expiration to prevent showing the notice again
	 *    to the current user for that period
	 *
	 * The transient is user-specific and payment method-specific, allowing different
	 * users to see or dismiss notices independently.
	 *
	 * @since 10.4.0
	 * @return void Sends JSON response and exits
	 */
	public function dismiss_newmethod_notice_handler() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ifthenpay_dismiss_newmethod_notice' ) ) {
			wp_send_json_error( 'Invalid security token' );
			exit;
		}
		// Get method ID
		$method_id = isset( $_POST['method_id'] ) ? sanitize_text_field( wp_unslash( $_POST['method_id'] ) ) : '';
		if ( empty( $method_id ) ) {
			wp_send_json_error( 'Missing method ID' );
			exit;
		}
		// Set transient with 90-day expiration (90 days * 24 hours * 60 minutes * 60 seconds) - Now 180 days
		// Removed in favor of user meta, to avoid showing again when cleaning cache
		// This needs to be a option per user because transients are cleared when the cache is cleared
		$days                 = 180;
		$expiration_timestamp = time() + ( $days * DAY_IN_SECONDS );
		update_user_meta( get_current_user_id(), $method_id . '_newmethod_notice_dismiss_until', $expiration_timestamp );
		wp_send_json_success(
			array( 'result' => true )
		);
		exit;
	}

	/**
	 * Outputs JavaScript code to handle notice dismissals for new payment methods
	 *
	 * This method renders inline JavaScript that attaches a click handler to the dismiss
	 * button of notification banners for new payment methods. When a user clicks the dismiss
	 * button, it:
	 *
	 * 1. Sends an AJAX request to the server to store the dismissal state
	 * 2. Passes the payment method ID to identify which notice was dismissed
	 * 3. Uses a security nonce to validate the request
	 *
	 * The AJAX call triggers the dismiss_newmethod_notice_handler() method, which saves
	 * a user-specific transient that prevents the notice from showing again for 90 days.
	 *
	 * @since 10.4.0
	 * @param string $id The payment gateway ID used to identify which notice was dismissed.
	 * @return void Outputs inline JavaScript
	 */
	public function dismiss_newmethod_notice_javascript( $id ) {
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				$( '#<?php echo esc_attr( $id ); ?>_newmethod_notice' ).on( 'click', 'button.notice-dismiss', function() {
					var method_id = '<?php echo esc_js( $id ); ?>';
					var data = {
						action: 'ifthenpay_dismiss_newmethod_notice',
						nonce: '<?php echo esc_js( wp_create_nonce( 'ifthenpay_dismiss_newmethod_notice' ) ); ?>',
						method_id: method_id
					};
					$.post( ajaxurl, data, function( response ) {
						if ( response.success ) {
							console.log( 'Notice dismissed successfully.' );
						} else {
							console.log( 'Error dismissing notice: ' + response.data );
						}
					} );
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Admin notices to warn about old technology
	 */
	public function admin_notices() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$page      = isset( $_GET['page'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( apply_filters( 'ifthen_show_old_techonology_notice', true ) ) {
			if (
				(
					( ! empty( $page ) ) && in_array(
						$page,
						array(
							'wc-settings',
							'wc-status',
							'wc-admin',
							'wc-reports',
							'wc-addons',
						),
						true
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
					),
					true
				)
			) {
				$notices = array();
				// WordPress below 6.0
				if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
					$notices[] = sprintf(
						/* translators: %1$s: required software name and version, %2$s: current version */
						esc_html__( '%1$s - Your version: %2$s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'<strong>WordPress 6.0</strong>',
						sprintf(
							'<strong style="color:red;">%s</strong>',
							get_bloginfo( 'version' )
						)
					);
				}
				// WooCommerce below 8.0
				if ( version_compare( WC_VERSION, '8.0', '<' ) ) {
					$notices[] = sprintf(
						/* translators: %1$s: required software name and version, %2$s: current version */
						esc_html__( '%1$s - Your version: %2$s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'<strong>WooCommerce 8.0</strong>',
						sprintf(
							'<strong style="color:red;">%s</strong>',
							WC_VERSION
						)
					)
					.
					' - <strong>' . esc_html__( 'Support for WooCommerce &lt; 8.0 will end soon!', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong>';
				}
				// PHP below 7.4
				if ( version_compare( phpversion(), '7.4', '<' ) ) {
					$notices[] = sprintf(
						/* translators: %1$s: required software name and version, %2$s: current version */
						esc_html__( '%1$s - Your version: %2$s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'<strong>PHP 7.4</strong>',
						sprintf(
							'<strong style="color:red;">%s</strong>',
							phpversion()
						)
					);
				}
				if ( count( $notices ) > 0 ) {
					if ( ! function_exists( 'get_plugin_data' ) ) {
						require_once ABSPATH . 'wp-admin/includes/plugin.php'; // Should not be necessary, but we never know...
					}
					$plugin_data = get_plugin_data( WC_IFTHENPAY_WEBDADOS_PLUGIN_FILE, false, false );
					?>
					<div class="notice notice-error notice-alt">
						<p>
							<strong>
								<?php echo esc_html( $plugin_data['Name'] ); ?>
							</strong>
						<p>
							<?php esc_html_e( 'We are working on implementing the latest and safest technology, so you will soon need at least:', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
						</p>
						<ul>
							<?php foreach ( $notices as $notice ) { ?>
								<li>- <?php echo wp_kses_post( $notice ); ?></li>
							<?php } ?>
						</ul>
					</div>
					<?php
				}
			}
		}
	}

	/**
	 * Action scheduler task that does nothing
	 *
	 * Used to keep the Action Scheduler running and not looping
	 */
	public function action_scheduler_do_nothing() {
		// Do nothing - Make sure the task does not fail for lack of a hook
	}
}
