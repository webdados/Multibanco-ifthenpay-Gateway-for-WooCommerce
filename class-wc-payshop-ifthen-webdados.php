<?php
/**
 * Payshop class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Payshop IfThen Class.
 */
if ( ! class_exists( 'WC_Payshop_IfThen_Webdados' ) ) {

	class WC_Payshop_IfThen_Webdados extends WC_Payment_Gateway {

		/* Single instance */
		protected static $_instance = null;
		public static $instances    = 0;

		/* Properties */
		public $debug;
		public $debug_email;
		public $version;
		public $secret_key;
		public $webservice_url;
		public $extra_instructions;
		public $payshopkey;
		public $settings_saved;
		public $send_to_admin;
		public $only_portugal;
		public $only_above;
		public $only_below;
		public $stock_when;
		public $validity;

		/**
		 * Constructor for your payment class
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {

			self::$instances++;

			$this->id = WC_IfthenPay_Webdados()->payshop_id;

			// Logs
			$this->debug       = ( $this->get_option( 'debug' ) == 'yes' ? true : false );
			$this->debug_email = $this->get_option( 'debug_email' );

			// Check version and upgrade
			$this->version = WC_IfthenPay_Webdados()->get_version();
			$this->upgrade();

			$this->has_fields = false;

			$this->method_title       = __( 'Pagamento na rede de agentes Payshop (IfthenPay)', 'multibanco-ifthen-software-gateway-for-woocommerce' );
			$this->method_description = __( 'Easy and simple payment on the “Payshop” agents network, CTT stores or post offices. (Payment service provided by IfthenPay)', 'multibanco-ifthen-software-gateway-for-woocommerce' );
			/*
			if ( $this->get_option( 'support_woocommerce_subscriptions' ) == 'yes' ) {
				$this->supports = array(
					'products',
					'subscription_suspension',
					'subscription_reactivation',
					'subscription_date_changes',
					'subscriptions',                           //Deprecated?
					'subscription_payment_method_change_admin' //Deprecated?
				); //products is by default
			}*/
			$this->secret_key = $this->get_option( 'secret_key' );
			if ( trim( $this->secret_key ) == '' ) {
				// First load?
				$this->secret_key = md5( home_url() . time() . wp_rand( 0, 999 ) );
				// Save
				$this->update_option( 'secret_key', $this->secret_key );
				$this->update_option( 'debug', 'yes' );
				// Let's set the callback activation email as NOT sent
				update_option( $this->id . '_callback_email_sent', 'no' );
			}

			// Webservice
			$this->webservice_url = 'https://ifthenpay.com/api/payshop/reference/';

			// Plugin options and settings
			$this->init_form_fields();
			$this->init_settings();

			// User settings
			$this->title              = $this->get_option( 'title' );
			$this->description        = $this->get_option( 'description' );
			$this->extra_instructions = $this->get_option( 'extra_instructions' );
			$this->payshopkey         = $this->get_option( 'payshopkey' );
			$this->settings_saved     = $this->get_option( 'settings_saved' );
			$this->send_to_admin      = ( $this->get_option( 'send_to_admin' ) == 'yes' ? true : false );
			$this->only_portugal      = ( $this->get_option( 'only_portugal' ) == 'yes' ? true : false );
			$this->only_above         = $this->get_option( 'only_above' );
			$this->only_below         = $this->get_option( 'only_bellow' );
			$this->stock_when         = $this->get_option( 'stock_when' );
			$this->validity           = $this->get_option( 'validity' );

			// Actions and filters
			if ( self::$instances === 1 ) { // Avoid duplicate actions and filters if it's initiated more than once (if WooCommerce loads after us)
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'send_callback_email' ) );
				if ( WC_IfthenPay_Webdados()->wpml_active ) {
					add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'register_wpml_strings' ) );
				}
				add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou' ) );
				add_action( 'woocommerce_order_details_after_order_table', array( $this, 'order_details_after_order_table' ), 9 );
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_if_settings_missing' ) );
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_if_currency_not_euro' ) );
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_unless_portugal' ) );
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_only_above_or_below' ) );

				// APG SMS Notifications Integration
				// https://wordpress.org/plugins/woocommerce-apg-sms-notifications/
				// add_filter( 'apg_sms_message', array( $this, 'sms_instructions_apg' ), 10, 2 ); // (Removed from the class because now APG also sends scheduled SMS and the payment class may not be initiated)
				// Twilio SMS Notifications
				// https://woocommerce.com/products/twilio-sms-notifications/
				add_filter( 'wc_twilio_sms_customer_sms_before_variable_replace', array( $this, 'sms_instructions_twilio' ), 10, 2 );
				// YITH WooCommerce SMS Notifications
				// https://yithemes.com/themes/plugins/yith-woocommerce-sms-notifications/
				add_filter( 'ywsn_sms_placeholders', array( $this, 'sms_instructions_yith' ), 10, 2 );

				// Customer Emails
				// Regular orders
				add_action(
					apply_filters( 'payshop_ifthen_email_hook', 'woocommerce_email_before_order_table' ),
					array( $this, 'email_instructions_1' ), // Avoid "Hyyan WooCommerce Polylang Integration" remove_action
					apply_filters( 'payshop_ifthen_email_hook_priority', 10 ),
					4
				);
				// Subscriptions
				add_action(
					apply_filters( 'payshop_ifthen_subscription_email_hook', 'woocommerce_email_before_subscription_table' ),
					array( $this, 'email_instructions_1' ), // Avoid "Hyyan WooCommerce Polylang Integration" remove_action
					apply_filters( 'payshop_ifthen_subscription_email_hook_priority', 10 ),
					4
				);

				// Payment listener/API hook
				add_action( 'woocommerce_api_wc_payshop_ifthen_webdados', array( $this, 'callback' ) );

				// Filter to decide if payment_complete reduces stock, or not
				add_filter( 'woocommerce_payment_complete_reduce_order_stock', array( $this, 'woocommerce_payment_complete_reduce_order_stock' ), 10, 2 );

				// Admin notice if callback activation email is still not sent
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );

			}

			// Ensures only one instance of our plugin is loaded or can be loaded - works if WooCommerce loads the payment gateways before we do
			if ( is_null( self::$_instance ) ) {
				self::$_instance = $this;
			}

		}

		/* Ensures only one instance of our plugin is loaded or can be loaded */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Upgrades (if needed)
		 */
		function upgrade() {
			if ( $this->get_option( 'version' ) < $this->version ) {
				$current_options = get_option( 'woocommerce_' . $this->id . '_settings', '' );
				if ( ! is_array( $current_options ) ) {
					$current_options = array();
				}
				// Upgrade
				$this->debug_log( 'Upgrade to ' . $this->version . ' started' );
				if ( $this->version >= '5.0.0' ) {
					// Activate the resend new order option by default
					if ( ! isset( $current_options['resend_new_order_when_paid'] ) ) {
						$current_options['resend_new_order_when_paid'] = 'yes';
					}
				}
				// Upgrade on the database - Risky?
				$current_options['version'] = $this->version;
				update_option( 'woocommerce_' . $this->id . '_settings', $current_options );
				$this->debug_log( 'Upgrade to ' . $this->version . ' finished' );
			}
		}

		/**
		 * WPML compatibility
		 */
		function register_wpml_strings() {
			// These are already registered by WooCommerce Multilingual
			/*
			$to_register=array(
				'title',
				'description',
			);*/
			$to_register = array(
				'extra_instructions',
			);
			foreach ( $to_register as $string ) {
				icl_register_string( $this->id, $this->id . '_' . $string, $this->settings[ $string ] );
			}
		}

		/**
		 * Initialise Gateway Settings Form Fields
		 * 'setting-name' => array(
		 *      'title' => __( 'Title for setting', 'woothemes' ),
		 *      'type' => 'checkbox|text|textarea',
		 *      'label' => __( 'Label for checkbox setting', 'woothemes' ),
		 *      'description' => __( 'Description for setting' ),
		 *      'default' => 'default value'
		 *  ),
		 */
		function init_form_fields() {

			$this->form_fields = array(
				'enabled'    => array(
					'title'   => __( 'Enable/Disable', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable “Payshop” (using IfthenPay)', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'default' => 'no',
				),
				'payshopkey' => array(
					'title'             => __( 'Payshop Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'type'              => 'text',
					'description'       => __( 'Payshop Key provided by IfthenPay when signing the contract.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'default'           => '',
					'css'               => 'width: 130px;',
					'placeholder'       => 'XXX-000000',
					'custom_attributes' => array(
						'maxlength' => 10,
						'size'      => 14,
					),
				),
			);
			// if ( strlen( trim( $this->get_option( 'payshopkey' ) ) ) == 10 && trim( $this->secret_key ) != '' ) {
				$this->form_fields = array_merge(
					$this->form_fields,
					array(
						'secret_key'         => array(
							'title'       => __( 'Anti-phishing key', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' (Payshop)',
							'type'        => 'hidden',
							'description' => '<strong id="woocommerce_' . $this->id . '_secret_key_label">' . $this->secret_key . '</strong><br/>' . __( 'To ensure callback security, generated by the system and which must be provided to IfthenPay when asking for the callback activation.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'default'     => $this->secret_key,
						),
						'title'              => array(
							'title'       => __( 'Title', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'text',
							'description' => __( 'This controls the title which the user sees during checkout.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
											. ( WC_IfthenPay_Webdados()->wpml_active ? '<br/>' . WC_IfthenPay_Webdados()->wpml_translation_info : '' ),
							'default'     => 'Payshop',
						),
						'description'        => array(
							'title'       => __( 'Description', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'textarea',
							'description' => __( 'This controls the description which the user sees during checkout.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
											. ( WC_IfthenPay_Webdados()->wpml_active ? '<br/>' . WC_IfthenPay_Webdados()->wpml_translation_info : '' ),
							'default'     => $this->get_method_description(),
						),
						'extra_instructions' => array(
							'title'       => __( 'Extra instructions', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'textarea',
							'description' => __( 'This controls the text which the user sees below the payment details on the “Thank you” page and “New order” email.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
											. ( WC_IfthenPay_Webdados()->wpml_active ? '<br/>' . WC_IfthenPay_Webdados()->wpml_translation_info : '' ),
							'default'     => __( 'Payable at any <a href="https://www.payshop.pt/fepsapl/app/open/showSearchAgent.jspx" target="_blank">Payshop agent</a>, <a href="https://www.ctt.pt/feapl_2/app/open/stationSearch/stationSearch.jspx?request_locale=en" target="_blank">CTT store or post office</a>.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'css'         => 'height: 8em;',
						),
						'only_portugal'      => array(
							'title'   => __( 'Only for Portuguese customers?', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'    => 'checkbox',
							'label'   => __( 'Enable only for customers whose billing or shipping address is in Portugal', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'default' => 'no',
						),
						'only_above'         => array(
							'title'       => __( 'Only for orders from', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'number',
							'description' => __( 'Enable only for orders with a value from x &euro;. Leave blank (or zero) to allow for any order value.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' <br/> ' . sprintf(
								__( 'By design, %1$s only allows payments from %2$s to %3$s. You can use this option to further limit this range.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								'Payshop',
								wc_price( WC_IfthenPay_Webdados()->payshop_min_value, array( 'currency' => 'EUR' ) ),
								wc_price( WC_IfthenPay_Webdados()->payshop_max_value, array( 'currency' => 'EUR' ) )
							),
							'default'     => '',
						),
						'only_bellow'        => array(
							'title'       => __( 'Only for orders up to', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'number',
							'description' => __( 'Enable only for orders with a value up to x &euro;. Leave blank (or zero) to allow for any order value.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' <br/> ' . sprintf(
								__( 'By design, %1$s only allows payments from %2$s to %3$s. You can use this option to further limit this range.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								'Payshop',
								wc_price( WC_IfthenPay_Webdados()->payshop_min_value, array( 'currency' => 'EUR' ) ),
								wc_price( WC_IfthenPay_Webdados()->payshop_max_value, array( 'currency' => 'EUR' ) )
							),
							'default'     => '',
						),
					)
				);
				$validity_options  = array(
					'0' => __( 'no validity', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
				);
				for ( $i = 1; $i <= 10; $i++ ) {
					$validity_options[ $i ] = sprintf( esc_html( _n( '%d day', '%d days', $i, 'multibanco-ifthen-software-gateway-for-woocommerce' ) ), $i );
				}
				$validity_options[15] = sprintf( esc_html( _n( '%d day', '%d days', 15, 'multibanco-ifthen-software-gateway-for-woocommerce' ) ), 15 );
				for ( $i = 2; $i <= 10; $i++ ) {
					$validity_options[ $i * 10 ] = sprintf( esc_html( _n( '%d day', '%d days', $i * 10, 'multibanco-ifthen-software-gateway-for-woocommerce' ) ), $i * 10 );
				}
				$this->form_fields = array_merge(
					$this->form_fields,
					array(
						'stock_when'                 => array(
							'title'       => __( 'Reduce stock', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'select',
							'description' => __( 'Choose when to reduce stock.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'default'     => '',
							'options'     => array(
								'order' => __( 'when order is placed (before payment, WooCommerce default)', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								''      => __( 'when order is paid (requires active callback)', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							),
						),
						'resend_new_order_when_paid' => array(
							'title'       => __( 'Notify store owner of payment', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'checkbox',
							'label'       => __( 'Force resending the “New order” email to the store owner upon payment', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'description' => sprintf(
								__( 'If the %1$s“New order” email notification%2$s is active', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								'<a href="admin.php?page=wc-settings&amp;tab=email&section=wc_email_new_order" target="_blank">',
								'</a>'
							),
							'default'     => 'yes',
						),
						'validity'                   => array(
							'title'       => __( 'Reference validity', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'select',
							'description' => __( 'How many days must the reference be valid for payment?', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'default'     => '',
							'options'     => apply_filters( 'payshop_ifthen_validity_options', $validity_options ),
						),
					)
				);
				// Not implemented yet
				/*
				if ( WC_IfthenPay_Webdados()->wc_subscriptions_active ) {
					$this->form_fields = array_merge( $this->form_fields, array(
						'support_woocommerce_subscriptions' => array(
										'title' => __( 'WooCommerce Subscriptions', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
										'type' => 'checkbox',
										'label' => __( 'Enable WooCommerce Subscriptions (experimental) support.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
										'description' => __( 'Shows “Payshop” (using IfthenPay) as a supported payment gateway, and automatically sets subscription renewal orders to be paid with Payshop if the original subscription used this payment method. If this option is not activated, Payshop will only be available as a payment method for subscriptions if the “Manual Renewal Payments” option is enabled on WooCommerce Subscriptions settings.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
										'default' => 'no'
									),
					) );
				}*/
				$this->form_fields = array_merge(
					$this->form_fields,
					array(
						'send_to_admin'     => array(
							'title'   => __( 'Send instructions to admin?', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'    => 'checkbox',
							'label'   => __( 'Should the payment details also be sent to admin?', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'default' => 'yes',
						),
						'update_ref_client' => array(
							'title'   => __( 'Email reference update to client?', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'    => 'checkbox',
							'label'   => __( 'If the payment details change because of an update on the backend, should the client be notified?', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'default' => 'no',
						),
						'debug'             => array(
							'title'       => __( 'Debug Log', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'checkbox',
							'label'       => __( 'Enable logging', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'default'     => 'yes',
							'description' => sprintf(
								__( 'Log plugin events, such as callback requests, in %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								( ( defined( 'WC_LOG_HANDLER' ) && 'WC_Log_Handler_DB' === WC_LOG_HANDLER ) || version_compare( WC_VERSION, '8.6', '>=' ) )
								?
								'<a href="admin.php?page=wc-status&tab=logs&source=' . esc_attr( $this->id ) . '" target="_blank">' . __( 'WooCommerce &gt; Status &gt; Logs', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</a>'
								:
								'<code>' . wc_get_log_file_path( $this->id ) . '</code>'
							),
						),
						'debug_email'       => array(
							'title'       => __( 'Debug to email', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'email',
							'label'       => __( 'Enable email logging', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'default'     => '',
							'description' => __( 'Send main plugin events to this email address, such as callback requests.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						),
					)
				);
			// }
			// PRO fake fields
			$pro_fake_fields = array(
				// Cancel upaid
				'_pro_payshop_cancel_unpaid' => array(
					'type'     => 'checkbox',
					'title'    => __( 'Cancel unpaid orders', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'label'    => __( 'Cancel Payshop unpaid orders after the reference expires', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'disabled' => true,
				),
			);
			foreach( $pro_fake_fields as $key => $temp ) {
				$pro_fake_fields[$key]['title'] = '⭐️ ' . $pro_fake_fields[$key]['title'];
				if ( isset( $pro_fake_fields[$key]['description'] ) ) {
					$pro_fake_fields[$key]['description'] .= '<br/>';
				} else {
					$pro_fake_fields[$key]['description'] = '';
				}
				$pro_fake_fields[$key]['description'] .= sprintf(
					__( 'Available on the %sPRO Add-on%s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'<a href="https://ptwooplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/'.esc_attr( WC_IfthenPay_Webdados()->out_link_utm ).'" target="_blank">',
					'</a>'
				);
			}
			$this->form_fields = array_merge(
				$this->form_fields,
				$pro_fake_fields
			);
			$this->form_fields = array_merge(
				$this->form_fields,
				array(
					'settings_saved' => array(
						'title'   => '',
						'type'    => 'hidden',
						'default' => 0,
					),
				)
			);

			// Allow other plugins to add settings fields
			$this->form_fields = array_merge( $this->form_fields, apply_filters( 'multibanco_ifthen_payshop_settings_fields', array() ) );
			// And to manipulate them
			$this->form_fields = apply_filters( 'multibanco_ifthen_payshop_settings_fields_all', $this->form_fields );

		}
		public function admin_options() {
			$title = esc_html( $this->get_method_title() );
			?>
			<div id="wc_ifthen">
				<?php
				if ( ! apply_filters( 'multibanco_ifthen_hide_settings_right_bar', false ) ) {
					WC_IfthenPay_Webdados()->admin_pro_banner();}
				?>
				<?php
				if ( ! apply_filters( 'multibanco_ifthen_hide_settings_right_bar', false ) ) {
					WC_IfthenPay_Webdados()->admin_right_bar();}
				?>
				<div id="wc_ifthen_settings">
					<h2>
						<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->payshop_banner ); ?>" alt="<?php echo esc_attr( $title ); ?>" width="182" height="48"/>
						<br/>
						<?php echo $title; ?>
						<small>v.<?php echo $this->version; ?></small>
						<?php
						if ( function_exists( 'wc_back_link' ) ) {
							echo wc_back_link( __( 'Return to payments', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );}
						?>
					</h2>
					<?php echo wp_kses_post( wpautop( $this->get_method_description() ) ); ?>
					<p><strong><?php _e( 'In order to use this plugin you <u>must</u>:', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong></p>
					<ul class="wc_ifthen_list">
						<li><?php printf( __( 'Set WooCommerce currency to <strong>Euros (&euro;)</strong> %1$s', 'multibanco-ifthen-software-gateway-for-woocommerce' ), '<a href="admin.php?page=wc-settings&amp;tab=general">&gt;&gt;</a>.' ); ?></li>
						<li><?php printf( __( 'Sign a contract with %1$s. To know more about this service, please go to %2$s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ), '<strong><a href="https://ifthenpay.com/' . esc_attr( WC_IfthenPay_Webdados()->out_link_utm ) . '" target="_blank">IfthenPay</a></strong>', '<a href="https://ifthenpay.com/' . esc_attr( WC_IfthenPay_Webdados()->out_link_utm ) . '" target="_blank">https://ifthenpay.com</a>' ); ?></li>
						<li><?php _e( 'Fill out all the details (Payshop Key) provided by <strong>IfthenPay</strong> in the fields below.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
						<li>
						<?php
						printf(
							__( 'Never use the same %1$s on more than one website or any other system, online or offline. Ask %2$s for new ones for each single platform.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							__( 'Payshop Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'<a href="https://ifthenpay.com/' . esc_attr( WC_IfthenPay_Webdados()->out_link_utm ) . '" target="_blank">IfthenPay</a>'
						);
						?>
						</li>
						<li class="mb_hide_extra_fields"><?php printf( __( 'Ask IfthenPay to activate “Payshop Callback” on your account using this exact URL: %1$s and this Anti-phishing key: %2$s', 'multibanco-ifthen-software-gateway-for-woocommerce' ), '<br/><code><strong>' . WC_IfthenPay_Webdados()->payshop_notify_url . '</strong></code><br/>', '<br/><code><strong>' . $this->secret_key . '</strong></code>' ); ?></li>
					</ul>
					<?php
					if (
						strlen( trim( $this->payshopkey ) ) == 10
						&&
						trim( $this->secret_key ) != ''
					) {
						if ( $callback_email_sent = get_option( $this->id . '_callback_email_sent' ) ) { // No notice for older versions
							if ( $callback_email_sent == 'no' ) {
								if ( ! isset( $_GET['callback_warning'] ) ) {
									?>
									<div id="message" class="error">
										<p><strong><?php _e( 'You haven’t yet asked IfthenPay for the “Callback” activation. The orders will NOT be automatically updated upon payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong></p>
									</div>
									<?php
								}
							}
						}
						?>
						<p id="wc_ifthen_callback_open_p"><a href="#" id="wc_ifthen_callback_open" class="button button-small"><?php _e( 'Click here to ask IfthenPay to activate the “Callback”', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></a></p>
						<div id="wc_ifthen_callback_div">
							<p><?php _e( 'This will submit a request to IfthenPay, asking them to activate the “Callback” on your account. The following details will be sent to IfthenPay:', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></p>
							<table class="form-table">
								<tr valign="top">
									<th scope="row" class="titledesc"><?php _e( 'Email', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></th>
									<td class="forminp">
										<?php echo get_option( 'admin_email' ); ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc">Payshop Key</th>
									<td class="forminp">
										<?php echo $this->payshopkey; ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc"><?php _e( 'Anti-phishing key', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' (Payshop)'; ?></th>
									<td class="forminp">
										<?php echo $this->secret_key; ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc"><?php _e( 'Callback URL', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></th>
									<td class="forminp">
										<?php echo WC_IfthenPay_Webdados()->payshop_notify_url; ?>
									</td>
								</tr>
							</table>
							<p style="text-align: center;">
								<strong><?php _e( 'Attention: if you ever change from HTTP to HTTPS or vice versa, or the permalinks structure,<br/>you may have to ask IfthenPay to update the callback URL.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong>
							</p>
							<p style="text-align: center; margin-bottom: 0px;">
								<input type="hidden" id="wc_ifthen_callback_send" name="wc_ifthen_callback_send" value="0"/>
								<input type="hidden" id="wc_ifthen_callback_bo_key" name="wc_ifthen_callback_bo_key" value=""/>
								<button id="wc_ifthen_callback_submit_webservice" class="button-primary" type="button"><?php _e( 'Ask for Callback activation', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?> - <?php _e( 'Via webservice (recommended)', '' ); ?></button>
								<br/><br/>
								<button id="wc_ifthen_callback_submit" class="button" type="button"><?php _e( 'Ask for Callback activation', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?> - <?php _e( 'Via email (old method)', '' ); ?></button>
								<input id="wc_ifthen_callback_cancel" class="button" type="button" value="<?php _e( 'Cancel', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>"/>
								<input type="hidden" name="save" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"/> <!-- Force action woocommerce_update_options_payment_gateways_ to run, from WooCommerce 3.5.5 -->
							</p>
						</div>
						<?php
					} else {
						if ( $this->settings_saved == 1 ) {
							?>
							<div id="message" class="error">
								<p><strong><?php _e( 'Invalid Payshop Key (exactly 10 characters).', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong></p>
							</div>
							<?php
						} else {
							?>
							<div id="message" class="error">
								<p><strong><?php _e( 'Set the Payshop Key and Save changes to set other plugin options.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong></p>
							</div>
							<?php
						}
					}
					?>
					<hr/>
					<?php
					if ( trim( get_woocommerce_currency() ) === 'EUR' || apply_filters( 'ifthen_allow_settings_woocommerce_not_euro', false ) ) {
						?>
						<table class="form-table">
							<?php $this->generate_settings_html(); ?>
						</table>
						<?php
					} else {
						?>
						<p><strong><?php _e( 'ERROR!', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?> <?php printf( __( 'Set WooCommerce currency to <strong>Euros (&euro;)</strong> %1$s', 'multibanco-ifthen-software-gateway-for-woocommerce' ), '<a href="admin.php?page=wc-settings&amp;tab=general">' . __( 'here', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</a>.' ); ?></strong></p>
						<style type="text/css">
							#mainform .submit,
							.wp-core-ui .button-primary.woocommerce-save-button {
								display: none;
							}
						</style>
						<?php
					}
					?>
				</div>
			</div>
			<div class="clear"></div>
			<?php
		}

		public function send_callback_email() {
			if ( isset( $_POST['wc_ifthen_callback_send'] ) && intval( $_POST['wc_ifthen_callback_send'] ) == 2 && trim( $_POST['wc_ifthen_callback_bo_key'] ) != '' ) {
				// Webservice
				$result = WC_IfthenPay_Webdados()->callback_webservice( trim( $_POST['wc_ifthen_callback_bo_key'] ), 'PAYSHOP', $this->payshopkey, $this->secret_key, WC_IfthenPay_Webdados()->payshop_notify_url );
				if ( $result['success'] ) {
					update_option( $this->id . '_callback_email_sent', 'yes' );
					WC_Admin_Settings::add_message( __( 'The “Callback” activation request has been submited to IfthenPay via webservice and is now active.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) );
				} else {
					WC_Admin_Settings::add_error(
						__( 'The “Callback” activation request via webservice has failed.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
						. ' - ' .
						$result['message']
					);
				}
			} elseif ( isset( $_POST['wc_ifthen_callback_send'] ) && intval( $_POST['wc_ifthen_callback_send'] ) == 1 ) {
				// Email
				$to      = WC_IfthenPay_Webdados()->callback_email;
				$cc      = get_option( 'admin_email' );
				$subject = 'Activação de Callback Payshop (Key: ' . $this->payshopkey . ')';
				$message = 'Por favor activar Callback Payshop com os seguintes dados:

Payshop Key:
' . $this->payshopkey . '

Chave anti-phishing (Payshop):
' . $this->secret_key . '

URL:
' . WC_IfthenPay_Webdados()->payshop_notify_url . '

Email enviado automaticamente do plugin WordPress “Multibanco, MB WAY, Credit card, Payshop and Cofidis Pay (IfthenPay) for WooCommerce” para ' . $to . ' com CC para ' . $cc;
				$headers = array(
					'From: ' . get_option( 'admin_email' ) . ' <' . get_option( 'admin_email' ) . '>',
					'Cc: ' . $cc,
				);
				if ( wp_mail( $to, $subject, $message, $headers ) ) {
					update_option( $this->id . '_callback_email_sent', 'yes' );
					WC_Admin_Settings::add_message( __( 'The “Callback” activation request has been submited to IfthenPay. Wait for their feedback.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) );
				} else {
					WC_Admin_Settings::add_error( __( 'The “Callback” activation request could not be sent. Check if your WordPress install can send emails.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) );
				}
			}
		}

		/**
		 * Icon HTML
		 */
		public function get_icon() {
			$alt       = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_title', $this->title ) : $this->title );
			$icon_html = '<img src="' . esc_attr( WC_IfthenPay_Webdados()->payshop_icon ) . '" alt="' . esc_attr( $alt ) . '" width="28" height="24"/>';
			return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
		}

		/**
		 * Thank you page
		 */
		function thankyou( $order_id ) {
			if ( is_object( $order_id ) ) {
				$order = $order_id;
			} else {
				$order = wc_get_order( $order_id );
			}
			if ( $this->id === $order->get_payment_method() ) {
				if ( WC_IfthenPay_Webdados()->order_needs_payment( $order ) ) {
					// We might have to deal with deposits...
					if ( $order->get_meta( '_' . WC_IfthenPay_Webdados()->payshop_id . '_exp' ) != '' && date_i18n( 'Y-m-d' ) > $order->get_meta( '_' . WC_IfthenPay_Webdados()->payshop_id . '_exp' ) ) {
						// Expired
						$expired = true;
						echo $this->thankyou_instructions_table_html_expired( $order->get_id(), round( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ), 2 ) );
					} else {
						// Not expired
						$expired = false;
						echo $this->thankyou_instructions_table_html( $order->get_id(), round( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ), 2 ) );
					}
				} else {
					// Processing
					if ( ( $order->has_status( 'processing' ) || $order->has_status( 'completed' ) ) && ! is_wc_endpoint_url( 'view-order' ) ) {
						echo $this->email_instructions_payment_received( $order->get_id() );
					}
				}
			}
		}
		function thankyou_instructions_table_html_css() {
			ob_start();
			?>
			<style type="text/css">
				table.<?php echo $this->id; ?>_table {
					width: auto !important;
					margin: auto;
					margin-top: 2em;
					margin-bottom: 2em;
					max-width: 325px !important;
				}
				table.<?php echo $this->id; ?>_table td,
				table.<?php echo $this->id; ?>_table th {
					background-color: #FFFFFF;
					color: #000000;
					padding: 10px;
					vertical-align: middle;
					white-space: nowrap;
				}
				table.<?php echo $this->id; ?>_table td.mb_value {
					text-align: right;
				}
				@media only screen and (max-width: 450px)  {
					table.<?php echo $this->id; ?>_table td,
					table.<?php echo $this->id; ?>_table th {
						white-space: normal;
					}
				}
				table.<?php echo $this->id; ?>_table th {
					text-align: center;
					font-weight: bold;
				}
				table.<?php echo $this->id; ?>_table th img {
					margin: auto;
					margin-top: 10px;
					max-height: 48px;
				}
				table.<?php echo $this->id; ?>_table td.barcode_img {
					text-align: center;
				}
				table.<?php echo $this->id; ?>_table td.extra_instructions {
					font-size: small;
					white-space: normal;
				}
			</style>
			<?php
			return ob_get_clean();
		}
		function thankyou_instructions_table_html( $order_id, $order_total ) {
			// Missing Payshop email or phone number?
			$alt                = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_title', $this->title ) : $this->title );
			$extra_instructions = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_extra_instructions', $this->extra_instructions ) : $this->extra_instructions );
			// We actually do not use $ent, $ref or $order_total - We'll just get the details
			$payshop_order_details = WC_IfthenPay_Webdados()->get_payshop_order_details( $order_id );
			ob_start();
			echo $this->thankyou_instructions_table_html_css();
			?>
			<table class="<?php echo $this->id; ?>_table" cellpadding="0" cellspacing="0">
				<tr>
					<th colspan="2">
						<?php _e( 'Payment instructions', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
						<br/>
						<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->payshop_banner ); ?>" alt="<?php echo esc_attr( $alt ); ?>" title="<?php echo esc_attr( $alt ); ?>"/>
					</th>
				</tr>
				<tr>
					<td><?php _e( 'Reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
					<td class="mb_value"><?php echo WC_IfthenPay_Webdados()->format_payshop_ref( $payshop_order_details['ref'] ); ?></td>
				</tr>
				<tr>
					<td><?php _e( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
					<td class="mb_value"><?php echo wc_price( $payshop_order_details['val'], array( 'currency' => 'EUR' ) ); ?></td>
				</tr>
				<?php
				if ( isset( $payshop_order_details['exp'] ) && trim( $payshop_order_details['exp'] ) != '' ) {
					?>
					<tr>
						<td><?php _e( 'Expiration', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
						<td class="mb_value"><?php echo WC_IfthenPay_Webdados()->payshop_format_expiration( $payshop_order_details['exp'], $order_id ); ?></td>
					</tr>
					<?php
				}
				?>
				<tr>
					<td colspan="2" class="extra_instructions">
						<?php echo nl2br( $extra_instructions ); ?>
					</td>
				</tr>
			</table>
			<?php
			return apply_filters( 'payshop_ifthen_thankyou_instructions_table_html', ob_get_clean(), round( $order_total, 2 ), $order_id );
		}
		function thankyou_instructions_table_html_expired( $order_id, $order_total ) {
			$alt   = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_title', $this->title ) : $this->title );
			$order = wc_get_order( $order_id );
			ob_start();
			echo $this->thankyou_instructions_table_html_css();
			?>
			<table class="<?php echo $this->id; ?>_table" cellpadding="0" cellspacing="0">
				<tr>
					<th colspan="2">
						<?php _e( 'Payment instructions', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
						<br/>
						<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->payshop_banner ); ?>" alt="<?php echo esc_attr( $alt ); ?>" title="<?php echo esc_attr( $alt ); ?>"/>
					</th>
				</tr>
				<tr>
					<td colspan="2" class="extra_instructions">
					<?php
					printf(
						__( 'The payment deadline expired. %1$sPlease try again%2$s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'<a href="' . esc_url( $order->get_checkout_payment_url() ) . '">',
						'</a>'
					);
					?>
					</td>
				</tr>
			</table>
			<?php
			return apply_filters( 'payshop_ifthen_thankyou_instructions_table_html_expired', ob_get_clean(), round( $order_total, 2 ), $order->get_id() );
		}
		function order_details_after_order_table( $order ) {
			if ( is_wc_endpoint_url( 'view-order' ) ) {
				$this->thankyou( $order );
			}
		}

		/**
		 * Email instructions
		 */
		function email_instructions_1( $order, $sent_to_admin, $plain_text, $email = null ) {
			// "Hyyan WooCommerce Polylang" Integration removes "email_instructions" so we use "email_instructions_1"
			$this->email_instructions( $order, $sent_to_admin, $plain_text, $email );
		}
		function email_instructions( $order, $sent_to_admin, $plain_text, $email = null ) {
			// Avoid duplicate email instructions on some edge cases
			$send = false;
			if ( ( $sent_to_admin ) ) {
				// if ( ( $sent_to_admin ) && ( !WC_IfthenPay_Webdados()->instructions_sent_to_admin ) ) { //Fixed by checking class instances
				// WC_IfthenPay_Webdados()->instructions_sent_to_admin = true;
				$send = true;
			} else {
				if ( ( ! $sent_to_admin ) ) {
					// if ( ( !$sent_to_admin ) && ( !WC_IfthenPay_Webdados()->instructions_sent_to_client ) ) { //Fixed by checking class instances
					// WC_IfthenPay_Webdados()->instructions_sent_to_client = true;
					$send = true;
				}
			}
			// $this->debug_log( 'Email instructions send: '.( $send ? 'true' : 'false' ) );
			// Apply filter
			$send = apply_filters( 'payshop_ifthen_send_email_instructions', $send, $order, $sent_to_admin, $plain_text, $email );
			// Send
			if ( $send ) {
				// Go
				if ( $this->id === $order->get_payment_method() || $order_deposit = WC_IfthenPay_Webdados()->deposit_is_ifthenpay( $order, $this->id ) ) {
					if ( isset( $order_deposit ) && $order_deposit ) {
						$order = $order_deposit;
					}
					$show = false;
					if ( ! $sent_to_admin ) {
						$show = true;
					} else {
						if ( $this->send_to_admin ) {
							$show = true;
						}
					}
					if ( $show ) {
						// Force correct language
						WC_IfthenPay_Webdados()->maybe_change_locale( $order );
						// On Hold or pending
						if ( WC_IfthenPay_Webdados()->order_needs_payment( $order ) ) {
							if ( WC_IfthenPay_Webdados()->wc_deposits_active && $order->get_status() == 'partially-paid' ) {
								// WooCommerce deposits - No instructions
							} else {
								if ( apply_filters( 'payshop_ifthen_email_instructions_pending_send', true, $order->get_id() ) ) {
									echo $this->email_instructions_table_html( $order->get_id(), round( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ), 2 ) );
								}
							}
						} else {
							// Processing
							if ( $order->has_status( 'processing' ) || $order->has_status( 'completed' ) ) {
								if ( apply_filters( 'payshop_ifthen_email_instructions_payment_received_send', true, $order->get_id() ) ) {
									echo $this->email_instructions_payment_received( $order->get_id() );
								}
							}
						}
					}
					// $this->debug_log( 'Email instructions show: '.( $show ? 'true' : 'false' ) );
				}
			}
		}
		function email_instructions_table_html( $order_id, $order_total ) {
			$alt                = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_title', $this->title ) : $this->title );
			$extra_instructions = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_extra_instructions', $this->extra_instructions ) : $this->extra_instructions );
			// We actually do not use $ent, $ref or $order_total - We'll just get the details
			$payshop_order_details = WC_IfthenPay_Webdados()->get_payshop_order_details( $order_id );
			ob_start();
			?>
			<table cellpadding="10" cellspacing="0" align="center" border="0" style="margin: auto; margin-top: 2em; margin-bottom: 2em; border-collapse: collapse; border: 1px solid #E60000; border-radius: 4px !important; background-color: #FFFFFF;">
				<tr>
					<td style="border: 1px solid #E60000; border-top-right-radius: 4px !important; border-top-left-radius: 4px !important; text-align: center; color: #000000; font-weight: bold;" colspan="2">
						<?php _e( 'Payment instructions', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
						<br/>
						<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->payshop_banner_email ); ?>" alt="<?php echo esc_attr( $alt ); ?>" title="<?php echo esc_attr( $alt ); ?>" style="margin-top: 10px; max-height: 48px;"/>
					</td>
				</tr>
				<tr>
					<td style="border-top: 1px solid #E60000; color: #000000;"><?php _e( 'Reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
					<td style="border-top: 1px solid #E60000; color: #000000; white-space: nowrap; text-align: right;"><?php echo WC_IfthenPay_Webdados()->format_payshop_ref( $payshop_order_details['ref'] ); ?></td>
				</tr>
				<tr>
					<td style="border-top: 1px solid #E60000; color: #000000;"><?php _e( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
					<td style="border-top: 1px solid #E60000; color: #000000; white-space: nowrap; text-align: right;"><?php echo wc_price( $payshop_order_details['val'], array( 'currency' => 'EUR' ) ); ?></td>
				</tr>
				<?php
				if ( isset( $payshop_order_details['exp'] ) && trim( $payshop_order_details['exp'] ) != '' ) {
					?>
					<tr>
						<td style="border-top: 1px solid #E60000; color: #000000;"><?php _e( 'Expiration', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
						<td style="border-top: 1px solid #E60000; color: #000000; white-space: nowrap; text-align: right;"><?php echo WC_IfthenPay_Webdados()->payshop_format_expiration( $payshop_order_details['exp'], $order_id ); ?></td>
					</tr>
					<?php
				}
				?>
				<tr>
					<td style="font-size: x-small; border: 1px solid #E60000; border-bottom-right-radius: 4px !important; border-bottom-left-radius: 4px !important; color: #000000; text-align: center;" colspan="2">
						<?php echo nl2br( $extra_instructions ); ?>
					</td>
				</tr>
			</table>
			<?php
			return apply_filters( 'payshop_ifthen_email_instructions_table_html', ob_get_clean(), round( $order_total, 2 ), $order_id );
		}
		function email_instructions_payment_received( $order_id ) {
			$alt = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_title', $this->title ) : $this->title );
			ob_start();
			?>
			<p style="text-align: center; margin: auto; margin-top: 2em; margin-bottom: 2em;">
				<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->payshop_banner_email ); ?>" alt="<?php echo esc_attr( $alt ); ?>" title="<?php echo esc_attr( $alt ); ?>" style="margin: auto; margin-top: 10px; max-height: 48px;"/>
				<br/>
				<strong><?php _e( 'Payshop payment received.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong>
				<br/>
				<?php _e( 'We will now process your order.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
			</p>
			<?php
			return apply_filters( 'payshop_ifthen_email_instructions_payment_received', ob_get_clean(), $order_id );
		}

		/**
		 * Webservice SetPedido
		 */
		function webservice_set_pedido( $order_id ) {

			$date_exp = false;
			if ( intval( $this->validity ) > 0 ) {
				$date_exp = \DateTime::createFromFormat( 'Y-m-d H:i:s', date_i18n( 'Y-m-d H:i:s' ) );
				$add      = new \DateInterval( 'P' . intval( $this->validity ) . 'D' );
				$date_exp->add( $add );
			}

			$order             = wc_get_order( $order_id );
			$payshopkey        = apply_filters( 'multibanco_ifthen_base_payshopkey', $this->payshopkey, $order );
			$id_for_backoffice = apply_filters( 'ifthen_webservice_send_order_number_instead_id', false ) ? $order->get_order_number() : $order->get_id();
			$args              = array(
				'method'   => 'POST',
				'timeout'  => apply_filters( 'payshop_ifthen_webservice_timeout', 30 ),
				'blocking' => true,
				'body'     => array(
					'payshopkey' => $payshopkey,
					'id'         => (string) $id_for_backoffice,
					'valor'      => (string) round( floatval( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ) ), 2 ),
				),
			);
			if ( $date_exp ) {
				$args['body']['validade'] = $date_exp->format( 'Ymd' );
			}
			$args['body'] = json_encode( $args['body'] ); // Json not post variables

			$response = wp_remote_post( $this->webservice_url, $args );
			if ( is_wp_error( $response ) ) {
				$debug_msg       = '- Error contacting the IfthenPay servers - Order ' . $order->get_id() . ' - ' . $response->get_error_message();
				$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
				$this->debug_log( $debug_msg, 'error', true, $debug_msg_email );
				return false;
			} else {
				if ( isset( $response['response']['code'] ) && intval( $response['response']['code'] ) == 200 && isset( $response['body'] ) && trim( $response['body'] ) != '' ) {

					if ( $response_data = json_decode( $response['body'] ) ) {
						if ( trim( $response_data->Reference ) != '' && trim( $response_data->RequestId ) != '' ) {
							$details = array(
								'payshopkey' => $payshopkey,
								'ref'        => trim( $response_data->Reference ),
								'request_id' => trim( $response_data->RequestId ),
								'id'         => $id_for_backoffice,
								'val'        => WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ),
							);
							if ( $date_exp ) {
								$details['exp'] = $date_exp->format( 'Y-m-d' );
							}
							WC_IfthenPay_Webdados()->multibanco_set_order_payshop_details( $order->get_id(), $details );
							$this->debug_log( '- Payshop payment request created on IfthenPay servers - Order ' . $order->get_id() );
							do_action( 'payshop_ifthen_created_reference', trim( $response_data->Reference ), $order->get_id() );
							return true;
						} else {
							$debug_msg       = '- Error contacting the IfthenPay servers - Order ' . $order->get_id() . ' - Missing "Reference" or "RequestId"';
							$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
							$this->debug_log( $debug_msg, 'error', true, $debug_msg_email );
							return false;
						}
					} else {
						$debug_msg = '- Error contacting the IfthenPay servers - Order ' . $order->get_id() . ' - "json_decode" failed';
						$this->debug_log( $debug_msg, 'error', true, $debug_msg );
						return false;
					}
				} else {
					$debug_msg       = '- Error contacting the IfthenPay servers - Order ' . $order->get_id() . ' - Incorrect response code: ' . $response['response']['code'];
					$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
					$this->debug_log( $debug_msg, 'error', true, $debug_msg_email );
					return false;
				}
			}
			return false;
		}

		/**
		 * SMS instructions for Twilio SMS Notifications
		 */
		function sms_instructions_twilio( $message, $order_id ) {
			$replace = WC_IfthenPay_Webdados()->payshop_sms_instructions( $message, $order_id );
			return trim( preg_replace( '/\s+/', ' ', str_replace( '%payshop_ifthen%', $replace, $message ) ) ); // Return message with %payshop_ifthen% replaced by the instructions
		}
		/**
		 * SMS instructions for Twilio SMS Notifications
		 */
		function sms_instructions_yith( $placeholders, $order ) {
			if ( is_array( $placeholders ) ) {
				$placeholders['{payshop_ifthen}'] = WC_IfthenPay_Webdados()->payshop_sms_instructions( '', $order->get_id() );
			}
			return $placeholders;
		}

		/**
		 * Process it
		 */
		function process_payment( $order_id ) {
			// Webservice
			$order = wc_get_order( $order_id );
			do_action( 'payshop_ifthen_before_process_payment', $order );
			if ( $this->webservice_set_pedido( $order->get_id() ) ) {
				// WooCommerce Deposits - When generating second payment reference the order goes from partially paid to on hold, and that has an email (??!)
				if ( WC_IfthenPay_Webdados()->wc_deposits_active && $order->get_status() == 'partially-paid' ) {
					add_filter( 'woocommerce_email_enabled_customer_processing_order', '__return_false' );
					add_filter( 'woocommerce_email_enabled_full_payment', '__return_false' );
				}
				// Mark as on-hold
				if ( $order->get_total() > 0 ) {
					if ( apply_filters( 'payshop_ifthen_set_on_hold', true, $order->get_id() ) ) {
						WC_IfthenPay_Webdados()->set_initial_order_status( $order, 'on-hold', 'Payshop' );
					}
				} else {
					$order->payment_complete();
				}
				// Remove cart
				if ( isset( WC()->cart ) ) {
					WC()->cart->empty_cart();
				}
				// Empty awaiting payment session
				unset( WC()->session->order_awaiting_payment );
				// Return thankyou redirect
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			} else {
				throw new Exception(
					sprintf(
						/* translators: %s: payment method */
						__( 'An error occurred processing the %s Payment request - please try again', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'Payshop'
					)
				);
			}
			return;
		}


		/**
		 * Disable if key not correctly set
		 */
		function disable_if_settings_missing( $available_gateways ) {
			if (
				strlen( trim( $this->payshopkey ) ) != 10
				||
				trim( $this->enabled ) != 'yes'
			) {
				unset( $available_gateways[ $this->id ] );
			}
			return $available_gateways;
		}

		/**
		 * Just for €
		 */
		function disable_if_currency_not_euro( $available_gateways ) {
			return WC_IfthenPay_Webdados()->disable_if_currency_not_euro( $available_gateways, $this->id );
		}

		/**
		 * Just for Portugal
		 */
		function disable_unless_portugal( $available_gateways ) {
			return WC_IfthenPay_Webdados()->disable_unless_portugal( $available_gateways, $this->id );
		}

		/**
		 * Just above/below certain amounts
		 */
		function disable_only_above_or_below( $available_gateways ) {
			return WC_IfthenPay_Webdados()->disable_only_above_or_below( $available_gateways, $this->id, WC_IfthenPay_Webdados()->payshop_min_value, WC_IfthenPay_Webdados()->payshop_max_value );
		}

		/* Payment complete - Stolen from PayPal method */
		function payment_complete( $order, $txn_id = '', $note = '' ) {
			$order->add_order_note( $note );
			$order->payment_complete( $txn_id );
		}
		/* Reduce stock on 'wc_maybe_reduce_stock_levels'? */
		function woocommerce_payment_complete_reduce_order_stock( $bool, $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order->get_payment_method() == $this->id ) {
				return ( WC_IfthenPay_Webdados()->woocommerce_payment_complete_reduce_order_stock( $bool, $order->get_id(), $this->id, $this->stock_when ) );
			} else {
				return $bool;
			}
		}

		/**
		 * Callback
		 */
		function callback() {
			@ob_clean();
			// We must 1st check the situation and then process it and send email to the store owner in case of error.
			if (
				isset( $_GET['chave'] )
				&&
				isset( $_GET['id_cliente'] )
				&&
				isset( $_GET['id_transacao'] )
				&&
				isset( $_GET['referencia'] )
				&&
				isset( $_GET['valor'] )
				&&
				isset( $_GET['estado'] )
			) {
				// Let's process it
				$this->debug_log( '- Callback (' . $_SERVER['REQUEST_URI'] . ') with all arguments from ' . $_SERVER['REMOTE_ADDR'] );
				$referencia      = trim( sanitize_text_field( $_GET['referencia'] ) );
				$id_cliente      = trim( sanitize_text_field( $_GET['id_cliente'] ) );
				$id_transacao    = str_replace( ' ', '+', trim( sanitize_text_field( $_GET['id_transacao'] ) ) ); // If there's a plus sign on the URL We'll get it as a space, so we need to get it back
				$val             = floatval( $_GET['valor'] );
				$estado          = trim( $_GET['estado'] );
				$arguments_ok    = true;
				$arguments_error = '';
				if ( trim( $_GET['chave'] ) != trim( $this->secret_key ) ) {
					$arguments_ok     = false;
					$arguments_error .= ' - Key';
				}
				if ( ! is_numeric( $referencia ) ) {
					$arguments_ok     = false;
					$arguments_error .= ' - Referencia (numeric)';
				}
				if ( trim( $id_cliente ) == '' ) {
					$arguments_ok     = false;
					$arguments_error .= ' - id_cliente';
				}
				if ( trim( $id_transacao ) == '' ) {
					$arguments_ok     = false;
					$arguments_error .= ' - id_transacao';
				}
				if ( ! $val >= 1 ) {
					$arguments_ok     = false;
					$arguments_error .= ' - Value';
				}
				/*
				if ( trim( $estado ) != 'PAGO' ) {
					$arguments_ok = false;
					$arguments_error .= ' - Estado';
				}*/
				if ( $arguments_ok ) { // Isto deve ser separado em vários IFs para melhor se identificar o erro
					if ( trim( $estado ) == 'PAGO' ) {
						$orders_exist = false;

						/* Aguardamos resposta do significado dos parâmetros para sabermos o que temos de guardar e como pesquisar depois */
						$pending_status = apply_filters( 'payshop_ifthen_valid_callback_pending_status', WC_IfthenPay_Webdados()->unpaid_statuses ); // Double filter - Should we deprectate this one?
						$args           = array(
							'type'                   => array( 'shop_order', 'wcdp_payment' ), // Regular order or deposit
							'status'                 => $pending_status,
							'limit'                  => -1,
							'_' . $this->id . '_request_id' => $id_transacao,
							'_' . $this->id . '_ref' => $referencia,
							'_' . $this->id . '_id'  => $id_cliente,
						);
						$orders         = WC_IfthenPay_Webdados()->wc_get_orders( $args, $this->id );
						if ( count( $orders ) > 0 ) {
							$orders_exist = true;
							$orders_count = count( $orders );
							foreach ( $orders as $order ) {
								$order = wc_get_order( $order->get_id() );
							}
						}

						if ( $orders_exist ) {
							if ( $orders_count == 1 ) {
								if ( floatval( $val ) == floatval( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ) ) ) {
									$note = __( 'Payshop payment received.', 'multibanco-ifthen-software-gateway-for-woocommerce' );
									if ( isset( $_GET['datahorapag'] ) && trim( $_GET['datahorapag'] ) != '' ) {
										$note .= ' ' . trim( $_GET['datahorapag'] );
									}
									// WooCommerce Deposits second payment?
									if ( WC_IfthenPay_Webdados()->wc_deposits_active ) {
										if ( $order->get_meta( '_wc_deposits_order_has_deposit' ) == 'yes' ) { // Has deposit
											if ( $order->get_meta( '_wc_deposits_deposit_paid' ) == 'yes' ) { // First payment - OK!
												if ( $order->get_meta( '_wc_deposits_second_payment_paid' ) != 'yes' ) { // Second payment - not ok
													if ( floatval( $order->get_meta( '_wc_deposits_second_payment' ) ) == floatval( $val ) ) { // This really seems like the second payment
														// Set the current order status temporarly back to partially-paid, but first stop the emails
														add_filter( 'woocommerce_email_enabled_customer_partially_paid', '__return_false' );
														add_filter( 'woocommerce_email_enabled_partial_payment', '__return_false' );
														$order->update_status( 'partially-paid', __( 'Temporary status. Used to force WooCommerce Deposits to correctly set the order to processing.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) );
													}
												}
											}
										}
									}
									$this->payment_complete( $order, '', $note );
									// Force resending "New Order" email to the store owner (before 3.4.2 we had a "bug" that made this email duplicate - and people are used to it)
									if ( apply_filters( 'payshop_ifthen_set_on_hold', true, $order->get_id() ) ) { // Only if we set it on hold in the first place
										if ( $this->get_option( 'resend_new_order_when_paid' ) == 'yes' ) { // And the option is activated
											// From WooCommerce 5.0 we need to force it
											add_filter( 'woocommerce_new_order_email_allows_resend', '__return_true' );
											WC()->mailer()->emails['WC_Email_New_Order']->trigger( $order->get_id(), $order );
											remove_filter( 'woocommerce_new_order_email_allows_resend', '__return_true' );
										}
									}
									do_action( 'payshop_ifthen_callback_payment_complete', $order->get_id(), $_GET );

									header( 'HTTP/1.1 200 OK' );
									$this->debug_log( '-- Payshop payment received - Order ' . $order->get_id(), 'notice' );
									echo 'OK - Payshop payment received';
								} else {
									header( 'HTTP/1.1 200 OK' );
									$err = 'Error: The value does not match';
									$this->debug_log( '-- ' . $err . ' - Order ' . $order->get_id(), 'warning', true, 'Callback (' . $_SERVER['HTTP_HOST'] . ' ' . $_SERVER['REQUEST_URI'] . ') from ' . $_SERVER['REMOTE_ADDR'] );
									echo $err;
									do_action( 'payshop_ifthen_callback_payment_failed', $order->get_id(), $err, $_GET );
								}
							} else {
								header( 'HTTP/1.1 200 OK' );
								$err = 'Error: More than 1 order found awaiting payment with these details';
								$this->debug_log( '-- ' . $err, 'warning', true, 'Callback (' . $_SERVER['HTTP_HOST'] . ' ' . $_SERVER['REQUEST_URI'] . ') from ' . $_SERVER['REMOTE_ADDR'] );
								echo $err;
								do_action( 'payshop_ifthen_callback_payment_failed', 0, $err, $_GET );
							}
						} else {
							header( 'HTTP/1.1 200 OK' );
							$err = 'Error: No orders found awaiting payment with these details';
							$this->debug_log( '-- ' . $err, 'warning', true, 'Callback (' . $_SERVER['HTTP_HOST'] . ' ' . $_SERVER['REQUEST_URI'] . ') from ' . $_SERVER['REMOTE_ADDR'] );
							echo $err;
							do_action( 'payshop_ifthen_callback_payment_failed', 0, $err, $_GET );
						}
					} else {
						header( 'HTTP/1.1 200 OK' );
						$err = 'Error: Cannot process ' . trim( $estado ) . ' status';
						$this->debug_log( '-- ' . $err, 'warning', true, 'Callback (' . $_SERVER['HTTP_HOST'] . ' ' . $_SERVER['REQUEST_URI'] . ') from ' . $_SERVER['REMOTE_ADDR'] );
						echo $err;
						do_action( 'payshop_ifthen_callback_payment_failed', 0, $err, $_GET );
					}
				} else {
					// header("Status: 400");
					$err = 'Argument errors';
					$this->debug_log( '-- ' . $err . $arguments_error, 'warning', true, 'Callback (' . $_SERVER['HTTP_HOST'] . ' ' . $_SERVER['REQUEST_URI'] . ') with argument errors from ' . $_SERVER['REMOTE_ADDR'] . $arguments_error );
					do_action( 'payshop_ifthen_callback_payment_failed', 0, $err, $_GET );
					wp_die( $err, 'WC_Payshop_IfThen_Webdados', array( 'response' => 500 ) ); // Sends 500
				}
			} else {
				// header("Status: 400");
				$err = 'Callback (' . $_SERVER['REQUEST_URI'] . ') with missing arguments from ' . $_SERVER['REMOTE_ADDR'];
				$this->debug_log( '- ' . $err, 'warning', true );
				do_action( 'payshop_ifthen_callback_payment_failed', 0, $err, $_GET );
				wp_die( 'Error: Something is missing...', 'WC_Payshop_IfThen_Webdados', array( 'response' => 500 ) ); // Sends 500
			}
		}

		/* Debug / Log - MOVED TO WC_IfthenPay_Webdados with gateway id as first argument */
		public function debug_log( $message, $level = 'debug', $to_email = false, $email_message = '' ) {
			if ( $this->debug ) {
				WC_IfthenPay_Webdados()->debug_log( $this->id, $message, $level, ( trim( $this->debug_email ) != '' && $to_email ? $this->debug_email : false ), $email_message );
			}
		}

		/* Global admin notices - For example if callback email activation is still not sent */
		function admin_notices() {
			// Callback email
			if (
				trim( $this->enabled ) == 'yes'
				&&
				strlen( trim( $this->payshopkey ) ) == 10
				&&
				trim( $this->secret_key ) != ''
			) {
				if ( $callback_email_sent = get_option( $this->id . '_callback_email_sent' ) ) { // No notice for older versions
					if ( $callback_email_sent == 'no' ) {
						if ( ! isset( $_GET['callback_warning'] ) ) {
							if ( apply_filters( 'payshop_ifthen_show_callback_notice', true ) ) {
								?>
								<div id="payshop_ifthen_callback_notice" class="notice notice-error" style="padding-right: 38px; position: relative;">
									<p>
										<strong>Payshop (IfthenPay)</strong>
										<br/>
										<?php _e( 'You haven’t yet asked IfthenPay for the “Callback” activation. The orders will NOT be automatically updated upon payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
										<br/>
										<strong><?php _e( 'This is important', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>! <a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=payshop_ifthen_for_woocommerce&amp;callback_warning=1"><?php _e( 'Do it here', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></a>!</strong>
									</p>
								</div>
								<?php
							}
						}
					}
				}
			}
			// New method
			if (
				(
					strlen( trim( $this->payshopkey ) ) != 10
					||
					trim( $this->enabled ) != 'yes'
				)
				&&
				( ! apply_filters( 'multibanco_ifthen_hide_newmethod_notifications', false ) )
			) {
				?>
				<div id="payshop_ifthen_newmethod_notice" class="notice notice-info is-dismissible" style="padding-right: 38px; position: relative; display: none;">
					<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->payshop_banner ); ?>" style="float: left; margin-top: 0.5em; margin-bottom: 0.5em; margin-right: 1em; max-height: 48px; max-width: 182px;"/>
					<p>
						<?php
							echo sprintf(
								__( 'There’s a new payment method available: %s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								'<strong>Payshop (IfthenPay)</strong>'
							);
						?>
						<br/>
						<?php
						echo sprintf(
							__( 'Ask IfthenPay to activate it on your account and then %1$sconfigure it here%2$s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'<strong><a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=payshop_ifthen_for_woocommerce">',
							'</a></strong>'
						);
						?>
					</p>
				</div>
				<script type="text/javascript">
				(function () {
					notice    = jQuery( '#payshop_ifthen_newmethod_notice');
					dismissed = localStorage.getItem( '<?php echo $this->id; ?>_newmethod_notice_dismiss' );
					if ( !dismissed ) {
						jQuery( notice ).show();
						jQuery( notice ).on( 'click', 'button.notice-dismiss', function() {
							localStorage.setItem( '<?php echo $this->id; ?>_newmethod_notice_dismiss', 1 );
						});
					}
				}());
				</script>
				<?php
			}
		}

	}
}
