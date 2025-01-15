<?php
/**
 * MBWAY class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MBWAY_IFTHEN_DESC_LEN', 70 );

if ( ! class_exists( 'WC_MBWAY_IfThen_Webdados' ) ) {

	/**
	 * MB WAY IfThen Class.
	 */
	class WC_MBWAY_IfThen_Webdados extends WC_Payment_Gateway {

		/* Single instance */
		protected static $_instance = null;
		public static $instances    = 0;

		/* Properties */
		public $debug;
		public $debug_email;
		public $version;
		public $secret_key;
		public $order_initial_status_pending;
		public $extra_instructions;
		public $mbwaykey;
		public $settings_saved;
		public $send_to_admin;
		public $only_portugal;
		public $only_above;
		public $only_below;
		public $stock_when;
		public $do_refunds;
		public $do_refunds_backoffice_key;

		/**
		 * Constructor for your payment class
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {

			++self::$instances;

			$this->id = WC_IfthenPay_Webdados()->mbway_id;

			// Logs
			$this->debug       = ( $this->get_option( 'debug' ) === 'yes' ? true : false );
			$this->debug_email = $this->get_option( 'debug_email' );

			// Check version and upgrade
			$this->version = WC_IfthenPay_Webdados()->get_version();
			$this->upgrade();

			$this->has_fields = true;

			$this->method_title       = __( 'Pagamento MB WAY no telemóvel (ifthenpay)', 'multibanco-ifthen-software-gateway-for-woocommerce' );
			$this->method_description = __( 'Easy and simple payment using “MB WAY” on your mobile phone. (Only available for customers of Portuguese banks with MB WAY app installed - Payment service provided by ifthenpay)', 'multibanco-ifthen-software-gateway-for-woocommerce' );
			if ( WC_IfthenPay_Webdados()->wc_subscriptions_active && $this->get_option( 'support_woocommerce_subscriptions' ) === 'yes' ) { // Deprecated on version 6.5
				$this->supports = array(
					'products',
					'subscription_suspension',
					'subscription_reactivation',
					'subscription_date_changes',
					'subscriptions',                           // Deprecated?
					'subscription_payment_method_change_admin', // Deprecated?
				); // products is by default
			}
			$this->secret_key = $this->get_option( 'secret_key' );
			if ( trim( $this->secret_key ) === '' ) {
				// First load?
				$this->secret_key = md5( home_url() . time() . wp_rand( 0, 999 ) );
				// Save
				$this->update_option( 'secret_key', $this->secret_key );
				$this->update_option( 'debug', 'yes' );
				// Let's set the callback activation email as NOT sent
				update_option( $this->id . '_callback_email_sent', 'no' );
			}

			// on hold or pending?
			$this->order_initial_status_pending = apply_filters( 'mbway_ifthen_order_initial_status_pending', true );

			// Plugin options and settings
			$this->init_form_fields();
			$this->init_settings();

			// User settings
			$this->title                     = $this->get_option( 'title' );
			$this->description               = $this->get_option( 'description' );
			$this->extra_instructions        = $this->get_option( 'extra_instructions' );
			$this->mbwaykey                  = $this->get_option( 'mbwaykey' );
			$this->settings_saved            = $this->get_option( 'settings_saved' );
			$this->send_to_admin             = ( $this->get_option( 'send_to_admin' ) === 'yes' ? true : false );
			$this->only_portugal             = ( $this->get_option( 'only_portugal' ) === 'yes' ? true : false );
			$this->only_above                = $this->get_option( 'only_above' );
			$this->only_below                = $this->get_option( 'only_bellow' );
			$this->stock_when                = $this->get_option( 'stock_when' );
			$this->do_refunds                = ( $this->get_option( 'do_refunds' ) === 'yes' ? true : false );
			$this->do_refunds_backoffice_key = $this->get_option( 'do_refunds_backoffice_key' );
			if ( $this->do_refunds && trim( $this->do_refunds_backoffice_key ) !== '' ) {
				$this->supports[] = 'refunds';
			}

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

				// NO SMS Integrations for MB WAY

				// Customer Emails
				// Regular orders
				add_action(
					apply_filters( 'mbway_ifthen_email_hook', 'woocommerce_email_before_order_table' ),
					array( $this, 'email_instructions_1' ), // Avoid "Hyyan WooCommerce Polylang Integration" remove_action
					apply_filters( 'mbway_ifthen_email_hook_priority', 10 ),
					4
				);
				// WooCommerce Subscriptions
				add_action(
					apply_filters( 'mbway_ifthen_subscription_email_hook', 'woocommerce_email_before_subscription_table' ),
					array( $this, 'email_instructions_1' ), // Avoid "Hyyan WooCommerce Polylang Integration" remove_action
					apply_filters( 'mbway_ifthen_subscription_email_hook_priority', 10 ),
					4
				);

				// Payment listener/API hook
				add_action( 'woocommerce_api_wc_mbway_ifthen_webdados', array( $this, 'callback' ) );

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

		/**
		 * Ensures only one instance of our plugin is loaded or can be loaded
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Upgrades (if needed)
		 */
		private function upgrade() {
			if ( $this->get_option( 'version' ) < $this->version ) {
				$current_options = get_option( 'woocommerce_' . $this->id . '_settings', '' );
				if ( ! is_array( $current_options ) ) {
					$current_options = array();
				}
				// Upgrade
				$this->debug_log( 'Upgrade to ' . $this->version . ' started' );
				// Specific versions upgrades should be here
				// ...
				// Upgrade on the database - Risky?
				$current_options['version'] = $this->version;
				update_option( 'woocommerce_' . $this->id . '_settings', $current_options );
				$this->debug_log( 'Upgrade to ' . $this->version . ' finished' );
			}
		}

		/**
		 * WPML compatibility
		 */
		public function register_wpml_strings() {
			// Title and Descriptions are already registered by WooCommerce Multilingual
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
		public function init_form_fields() {

			$this->form_fields = array(
				'enabled'  => array(
					'title'   => __( 'Enable/Disable', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable “MB WAY” (using ifthenpay)', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'default' => 'no',
				),
				'mbwaykey' => array(
					'title'             => __( 'MB WAY Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'type'              => 'text',
					'description'       => __( 'MB WAY Key provided by ifthenpay when signing the contract.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'default'           => '',
					'css'               => 'width: 130px;',
					'placeholder'       => 'XXX-000000',
					'custom_attributes' => array(
						'maxlength' => 10,
						'size'      => 14,
					),
				),
			);
			$this->form_fields = array_merge(
				$this->form_fields,
				array(
					'secret_key'         => array(
						'title'       => __( 'Anti-phishing key', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' (MB WAY)',
						'type'        => 'hidden',
						'description' => '<strong id="woocommerce_' . $this->id . '_secret_key_label">' . $this->secret_key . '</strong><br/>' . __( 'To ensure callback security, generated by the system and which must be provided to ifthenpay when asking for the callback activation.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'default'     => $this->secret_key,
					),
					'title'              => array(
						'title'       => __( 'Title', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'type'        => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
										. ( WC_IfthenPay_Webdados()->wpml_active ? '<br/>' . WC_IfthenPay_Webdados()->wpml_translation_info : '' ),
						'default'     => 'MB WAY',
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
						'default'     => __( 'Use the MB WAY app on your phone to approve the payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
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
							/* translators: %1$s: payment method, %2$s: minimum value, %3$s: maximum value */
							__( 'By design, %1$s only allows payments from %2$s to %3$s. You can use this option to further limit this range.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'MB WAY',
							wc_price( WC_IfthenPay_Webdados()->mbway_min_value, array( 'currency' => 'EUR' ) ),
							wc_price( WC_IfthenPay_Webdados()->mbway_max_value, array( 'currency' => 'EUR' ) )
						),
						'default'     => '',
					),
					'only_bellow'        => array(
						'title'       => __( 'Only for orders up to', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'type'        => 'number',
						'description' => __( 'Enable only for orders with a value up to x &euro;. Leave blank (or zero) to allow for any order value.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' <br/> ' . sprintf(
							/* translators: %1$s: payment method, %2$s: minimum value, %3$s: maximum value */
							__( 'By design, %1$s only allows payments from %2$s to %3$s. You can use this option to further limit this range.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'MB WAY',
							wc_price( WC_IfthenPay_Webdados()->mbway_min_value, array( 'currency' => 'EUR' ) ),
							wc_price( WC_IfthenPay_Webdados()->mbway_max_value, array( 'currency' => 'EUR' ) )
						),
						'default'     => '',
					),
				)
			);
			if ( ! $this->order_initial_status_pending ) {
				$this->form_fields = array_merge(
					$this->form_fields,
					array(
						'stock_when' => array(
							'title'       => __( 'Reduce stock', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'select',
							'description' => __( 'Choose when to reduce stock.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'default'     => '',
							'options'     => array(
								'order' => __( 'when order is placed (before payment, WooCommerce default)', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								''      => __( 'when order is paid (requires active callback)', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							),
						),
					)
				);
			}
			if ( WC_IfthenPay_Webdados()->wc_subscriptions_active && $this->get_option( 'support_woocommerce_subscriptions' ) === 'yes' ) {
				$this->form_fields = array_merge(
					$this->form_fields,
					array(
						'support_woocommerce_subscriptions' => array(
							'title'       => __( 'WooCommerce Subscriptions', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' ' . __( 'DEPRECATED', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'checkbox',
							'label'       => __( 'Enable WooCommerce Subscriptions (experimental) support.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'description' => '<strong>' . __( 'Will be removed after disabled.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong><br/>' . __( 'Shows “MB WAY” (using ifthenpay) as a supported payment gateway, and automatically sets subscription renewal orders to be paid with MB WAY if the original subscription used this payment method. If this option is not activated, MB WAY will only be available as a payment method for subscriptions if the “Manual Renewal Payments” option is enabled on WooCommerce Subscriptions settings.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'default'     => 'no',
						),
					)
				);
			}
				$this->form_fields = array_merge(
					$this->form_fields,
					array(
						'do_refunds'                => array(
							'title' => __( 'Process refunds?', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'  => 'checkbox',
							'label' => __( 'Allow to refund via MB WAY when the order is completely or partially refunded in WooCommerce', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						),
						'do_refunds_backoffice_key' => array(
							'title'       => __( 'Backoffice key', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'text',
							'default'     => '',
							'description' => __( 'The ifthenpay backoffice key you got after signing the contract is needed to process refunds', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						),
					)
				);
				$this->form_fields = array_merge(
					$this->form_fields,
					array(
						'send_to_admin' => array(
							'title'   => __( 'Send instructions to admin?', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'    => 'checkbox',
							'label'   => __( 'Should the payment details also be sent to admin?', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'default' => 'yes',
						),
						'debug'         => array(
							'title'       => __( 'Debug Log', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'checkbox',
							'label'       => __( 'Enable logging', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'default'     => 'yes',
							'description' => sprintf(
								/* translators: %s: file name or link to logs */
								__( 'Log payment method events in %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								( ( defined( 'WC_LOG_HANDLER' ) && 'WC_Log_Handler_DB' === WC_LOG_HANDLER ) || version_compare( WC_VERSION, '8.6', '>=' ) )
								?
								'<a href="admin.php?page=wc-status&tab=logs&source=' . esc_attr( $this->id ) . '" target="_blank">' . __( 'WooCommerce &gt; Status &gt; Logs', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</a>'
								:
								'<code>' . wc_get_log_file_path( $this->id ) . '</code>'
							),
						),
						'debug_email'   => array(
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
				'_pro_mbway_cancel_expired'        => array(
					'type'     => 'checkbox',
					'title'    => __( 'Cancel or Recover unpaid orders', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'label'    => __( 'Cancel MB WAY unpaid orders after the reference expires or set them to Multibanco and send new payment details to customer', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'disabled' => true,
				),
				// Save number
				'_pro_checkout_save_number'        => array(
					'type'     => 'checkbox',
					'title'    => __( 'Save number to user profile', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'label'    => __( 'Offer the option to save the MB WAY mobile number to the user profile for future orders', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'disabled' => true,
				),
				// Enable countdown
				'_mbway_thankyou_enable_countdown' => array(
					'type'     => 'checkbox',
					'title'    => __( 'Enable payment countdown', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'label'    => __( 'Enable a MB WAY payment countdown on the "Thank you" page to create urgency and get paid faster', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'disabled' => true,
				),
			);
			foreach ( $pro_fake_fields as $key => $temp ) {
				$pro_fake_fields[ $key ]['title'] = '⭐️ ' . $pro_fake_fields[ $key ]['title'];
				if ( isset( $pro_fake_fields[ $key ]['description'] ) ) {
					$pro_fake_fields[ $key ]['description'] .= '<br/>';
				} else {
					$pro_fake_fields[ $key ]['description'] = '';
				}
				$pro_fake_fields[ $key ]['description'] .= sprintf(
					/* translators: %1$s: link open, %2$s: link close */
					__( 'Available on the %1$sPRO Add-on%2$s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'<a href="https://ptwooplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/' . esc_attr( WC_IfthenPay_Webdados()->out_link_utm ) . '" target="_blank">',
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
			$this->form_fields = array_merge( $this->form_fields, apply_filters( 'multibanco_ifthen_mbway_settings_fields', array() ) );
			// And to manipulate them
			$this->form_fields = apply_filters( 'multibanco_ifthen_mbway_settings_fields_all', $this->form_fields );
		}

		/**
		 * Admin options screen
		 */
		public function admin_options() {
			$title = $this->get_method_title();
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
						<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->mbway_banner ); ?>" alt="<?php echo esc_attr( $title ); ?>" width="114" height="48"/>
						<br/>
						<?php echo esc_html( $title ); ?>
						<small>v.<?php echo esc_html( $this->version ); ?></small>
						<?php
						if ( function_exists( 'wc_back_link' ) ) {
							wc_back_link( __( 'Return to payments', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
						}
						?>
					</h2>
					<?php echo wp_kses_post( wpautop( $this->get_method_description() ) ); ?>
					<p><strong><?php esc_html_e( 'In order to use this plugin you need to:', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong></p>
					<ul class="wc_ifthen_list">
						<li>
							<?php
							printf(
								/* translators: %1$s: Euro, %2$s: link to WooCommerce settings */
								esc_html__( 'Set WooCommerce currency to %1$s %2$s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								'<strong>Euro (&euro;)</strong>',
								'<a href="admin.php?page=wc-settings&amp;tab=general">' . esc_html__( 'here', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</a>.'
							);
							?>
						</li>
						<li>
							<?php
							printf(
								/* translators: %1$s: link to Ifthenpay, %2$s: link to ifthenpay */
								esc_html__( 'Sign a contract with %1$s. To learn more about this service, please go to %2$s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								'<strong><a href="https://ifthenpay.com/' . esc_attr( WC_IfthenPay_Webdados()->out_link_utm ) . '" target="_blank">ifthenpay</a></strong>',
								'<a href="https://ifthenpay.com/' . esc_attr( WC_IfthenPay_Webdados()->out_link_utm ) . '" target="_blank">https://ifthenpay.com</a>'
							);
							?>
						</li>
						<li><?php echo wp_kses_post( __( 'Fill out all the details (MB WAY Key) provided by <strong>ifthenpay</strong> in the fields below.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ); ?>
						<li>
						<?php
						echo wp_kses_post(
							sprintf(
								/* translators: %1$s: payment method keys, %2$s: link to ifthenpay */
								esc_html__( 'Do not use the same %1$s on multiple websites or any other system, online or offline. Ask %2$s for new ones for every single platform.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								esc_html__( 'MB WAY Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								'<a href="https://ifthenpay.com/' . esc_attr( WC_IfthenPay_Webdados()->out_link_utm ) . '" target="_blank">ifthenpay</a>'
							)
						);
						?>
						</li>
						<li class="mb_hide_extra_fields">
							<?php
							echo wp_kses_post(
								sprintf(
									/* translators: %1$s: Callback URL, %2$s: Anti-phishing key */
									__( 'Ask ifthenpay to activate “MB WAY Callback” on your account using this exact URL: %1$s and this Anti-phishing key: %2$s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									'<br/><code><strong>' . WC_IfthenPay_Webdados()->mbway_notify_url . '</strong></code><br/>',
									'<br/><code><strong>' . $this->secret_key . '</strong></code>'
								)
							);
							?>
						</li>
					</ul>
					<?php
					do_action( 'mbway_ifthen_after_settings_intro' );
					if (
						strlen( trim( $this->mbwaykey ) ) === 10
						&&
						trim( $this->secret_key ) !== ''
					) {
						$callback_email_sent = get_option( $this->id . '_callback_email_sent' );
						if ( $callback_email_sent === 'no' || $callback_email_sent === false ) {
							if ( ! isset( $_GET['callback_warning'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
								?>
								<div id="message" class="error">
									<p><strong><?php esc_html_e( 'You haven’t yet asked ifthenpay for the “Callback” activation. The orders will NOT be automatically updated upon payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong></p>
								</div>
								<?php
							}
						}
						?>
						<p id="wc_ifthen_callback_open_p"><a href="#" id="wc_ifthen_callback_open" class="button button-small"><?php esc_html_e( 'Click here to ask ifthenpay to activate the “Callback”', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></a></p>
						<div id="wc_ifthen_callback_div">
							<p><?php esc_html_e( 'This will submit a request to ifthenpay, asking them to activate the “Callback” on your account. The following details will be sent to ifthenpay:', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></p>
							<table class="form-table">
								<tr valign="top">
									<th scope="row" class="titledesc"><?php esc_html_e( 'Email', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></th>
									<td class="forminp">
										<?php echo esc_html( get_option( 'admin_email' ) ); ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc">
										<?php esc_html_e( 'MB WAY Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
									</th>
									<td class="forminp">
										<?php echo esc_html( $this->mbwaykey ); ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc"><?php esc_html_e( 'Anti-phishing key', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' (MB WAY)'; ?></th>
									<td class="forminp">
										<?php echo esc_html( $this->secret_key ); ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc"><?php esc_html_e( 'Callback URL', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></th>
									<td class="forminp">
										<?php echo esc_url( WC_IfthenPay_Webdados()->mbway_notify_url ); ?>
									</td>
								</tr>
							</table>
							<p style="text-align: center;">
								<strong><?php echo wp_kses_post( __( 'Attention: if you ever change from HTTP to HTTPS or vice versa, or the permalinks structure,<br/>you may have to ask ifthenpay to update the callback URL.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ); ?></strong>
							</p>
							<p style="text-align: center; margin-bottom: 0px;">
								<input type="hidden" id="wc_ifthen_callback_send" name="wc_ifthen_callback_send" value="0"/>
								<input type="hidden" id="wc_ifthen_callback_bo_key" name="wc_ifthen_callback_bo_key" value=""/>
								<button id="wc_ifthen_callback_submit_webservice" class="button-primary" type="button"><?php esc_html_e( 'Ask for Callback activation', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?> - <?php esc_html_e( 'Via API (recommended)', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></button>
								<br/><br/>
								<button id="wc_ifthen_callback_submit" class="button" type="button"><?php esc_html_e( 'Ask for Callback activation', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?> - <?php esc_html_e( 'Via email (old method)', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></button>
								<input id="wc_ifthen_callback_cancel" class="button" type="button" value="<?php esc_html_e( 'Cancel', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>"/>
								<input type="hidden" name="save" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"/> <!-- Force action woocommerce_update_options_payment_gateways_ to run, from WooCommerce 3.5.5 -->
							</p>
						</div>
						<?php
					} elseif ( intval( $this->settings_saved ) === 1 ) {
						?>
							<div id="message" class="error">
								<p><strong><?php esc_html_e( 'Invalid MB WAY Key (exactly 10 characters).', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong></p>
							</div>
							<?php
					} else {
						?>
							<div id="message" class="error">
								<p><strong><?php esc_html_e( 'Set the MB WAY Key and Save changes to set other payment method options.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong></p>
							</div>
							<?php

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
						<p>
							<strong>
								<?php esc_html_e( 'ERROR!', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
								<?php
								printf(
									/* translators: %1$s: Euro, %2$s: link to WooCommerce settings */
									esc_html__( 'Set WooCommerce currency to %1$s %2$s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									'<strong>Euro (&euro;)</strong>',
									'<a href="admin.php?page=wc-settings&amp;tab=general">' . esc_html__( 'here', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</a>.'
								);
								?>
							</strong>
						</p>
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

		/**
		 * Activate callback at ifthenpay
		 */
		public function send_callback_email() {
			// WooCommerce took care of nonces
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$callback_send = isset( $_POST['wc_ifthen_callback_send'] ) ? intval( $_POST['wc_ifthen_callback_send'] ) : 0;
			$bo_key        = isset( $_POST['wc_ifthen_callback_bo_key'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['wc_ifthen_callback_bo_key'] ) ) ) : '';
			// phpcs:enable WordPress.Security.NonceVerification.Missing
			if ( $callback_send === 2 && ! empty( $bo_key ) ) {
				// Webservice
				$result = WC_IfthenPay_Webdados()->callback_webservice( $bo_key, 'MBWAY', $this->mbwaykey, $this->secret_key, WC_IfthenPay_Webdados()->mbway_notify_url );
				if ( $result['success'] ) {
					update_option( $this->id . '_callback_email_sent', 'yes' );
					WC_Admin_Settings::add_message( __( 'The “Callback” activation request has been submited to ifthenpay via API and is now active.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) );
				} else {
					WC_Admin_Settings::add_error(
						__( 'The “Callback” activation request via API has failed.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
						. ' - ' .
						$result['message']
					);
				}
			} elseif ( $callback_send === 1 ) {
				// Email
				$to      = WC_IfthenPay_Webdados()->callback_email;
				$cc      = get_option( 'admin_email' );
				$subject = 'Activação de Callback MB WAY (Key: ' . $this->mbwaykey . ')';
				$message = 'Por favor activar Callback MB WAY com os seguintes dados:

MB WAY Key:
' . $this->mbwaykey . '

Chave anti-phishing (MB WAY):
' . $this->secret_key . '

URL:
' . WC_IfthenPay_Webdados()->mbway_notify_url . '

Email enviado automaticamente do plugin WordPress “ifthenpay for WooCommerce” ' . $to . ' com CC para ' . $cc;
				$headers = array(
					'From: ' . get_option( 'admin_email' ) . ' <' . get_option( 'admin_email' ) . '>',
					'Cc: ' . $cc,
				);
				if ( wp_mail( $to, $subject, $message, $headers ) ) {
					update_option( $this->id . '_callback_email_sent', 'yes' );
					WC_Admin_Settings::add_message( __( 'The “Callback” activation request has been submited to ifthenpay. Wait for their feedback.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) );
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
			$icon_html = '<img src="' . esc_url( WC_IfthenPay_Webdados()->mbway_icon ) . '" alt="' . esc_attr( $alt ) . '" width="28" height="24"/>';
			return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
		}

		/**
		 * Thank you page
		 *
		 * @param mixed $order_id The order.
		 */
		public function thankyou( $order_id ) {
			if ( is_object( $order_id ) ) {
				$order = $order_id;
			} else {
				$order = wc_get_order( $order_id );
			}
			if ( $this->id === $order->get_payment_method() ) {
				if ( WC_IfthenPay_Webdados()->order_needs_payment( $order ) ) {
					// We might have to deal with deposits...
					if ( date_i18n( 'Y-m-d H:i:s', strtotime( '-' . intval( WC_IfthenPay_Webdados()->mbway_minutes * WC_IfthenPay_Webdados()->mbway_multiplier_new_payment * 60 ) . ' SECONDS', current_time( 'timestamp' ) ) ) > $order->get_meta( '_' . WC_IfthenPay_Webdados()->mbway_id . '_time' ) ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
						// Expired
						$expired = true;
						echo $this->thankyou_instructions_table_html_expired( $order->get_id(), round( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ), 2 ) );  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} else {
						// Not expired
						$expired = false;
						echo $this->thankyou_instructions_table_html( $order->get_id(), round( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ), 2 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						if ( is_wc_endpoint_url( 'order-received' ) ) {
							do_action( 'mbway_ifthen_after_thankyou_instructions_table', $order );
						}
					}
					// Another payment option
					if ( $expired || apply_filters( 'mbway_ifthen_enable_pay_another_method_thankyou', true, $order->get_id() ) ) {
						?>
						<p class="<?php echo esc_attr( $this->id ); ?>_pay_another_method <?php echo esc_attr( $this->id ); ?>_text_small">
							<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button">
								<?php echo esc_html( apply_filters( 'mbway_ifthen_pay_another_method_button_text', __( 'Click here if you wish to use another payment method', 'multibanco-ifthen-software-gateway-for-woocommerce' ) ) ); ?>
							</a>
						</p>
						<?php
					}
					if (
						(
							is_wc_endpoint_url( 'order-received' )
							||
							// https://wordpress.org/support/topic/pagina-checkout-nao-atualiza/
							apply_filters( 'mbway_ifthen_force_check_order_status_thankyou', false )
						)
						&&
						! $expired
					) {
						if ( apply_filters( 'mbway_ifthen_enable_check_order_status_thankyou', true, $order->get_id() ) ) { // return false to mbway_ifthen_enable_check_order_status_thankyou in order to stop the ajax checking
							// Check order status
							?>
							<input type="hidden" id="mbway-order-id" value="<?php echo intval( $order->get_id() ); ?>"/>
							<input type="hidden" id="mbway-order-key" value="<?php echo esc_attr( $order->get_order_key() ); ?>"/>
							<?php
							wp_enqueue_script( 'mbway-ifthenpay', plugins_url( 'assets/mbway.js', __FILE__ ), array( 'jquery' ), $this->version . ( WP_DEBUG ? '.' . wp_rand( 0, 99999 ) : '' ), true );
							wp_localize_script(
								'mbway-ifthenpay',
								'mbway_ifthenpay',
								array(
									'interval'      => apply_filters( 'mbway_ifthen_check_order_status_thankyou_interval', 10 ),
									'mbway_minutes' => WC_IfthenPay_Webdados()->mbway_minutes,
								)
							);
						}
					}
				} elseif ( ( $order->has_status( 'processing' ) || $order->has_status( 'completed' ) ) && ! is_wc_endpoint_url( 'view-order' ) ) { // Processing
					echo $this->email_instructions_payment_received( $order->get_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}
		}

		/**
		 * Thank you page instructions table CSS
		 */
		private function thankyou_instructions_table_html_css() {
			ob_start();
			?>
			<style type="text/css">
				table.<?php echo esc_html( $this->id ); ?>_table {
					width: auto !important;
					margin: auto;
					margin-top: 2em;
					margin-bottom: 2em;
					max-width: 325px !important;
				}
				table.<?php echo esc_html( $this->id ); ?>_table td,
				table.<?php echo esc_html( $this->id ); ?>_table th {
					background-color: #FFFFFF;
					color: #000000;
					padding: 10px;
					vertical-align: middle;
					white-space: nowrap;
				}
				table.<?php echo esc_html( $this->id ); ?>_table td.mb_value {
					text-align: right;
				}
				@media only screen and (max-width: 450px)  {
					table.<?php echo esc_html( $this->id ); ?>_table td,
					table.<?php echo esc_html( $this->id ); ?>_table th {
						white-space: normal;
					}
				}
				table.<?php echo esc_html( $this->id ); ?>_table th {
					text-align: center;
					font-weight: bold;
				}
				table.<?php echo esc_html( $this->id ); ?>_table th img {
					margin: auto;
					margin-top: 10px;
					max-height: 48px;
				}
				.<?php echo esc_html( $this->id ); ?>_text_small {
					font-size: small;
				}
				p.<?php echo esc_html( $this->id ); ?>_pay_another_method {
					text-align: center;
					margin-top: 0px;
					margin-bottom: 3em;
				}
				p#<?php echo esc_html( $this->id ); ?>_counter {
					text-align: center;
					font-size: 1.25em;
					margin: 2em;
				}
				p#<?php echo esc_html( $this->id ); ?>_counter #<?php echo esc_html( WC_IfthenPay_Webdados()->mbway_id ); ?>_counter_time {
					font-weight: bold;
				}
				table.<?php echo esc_html( $this->id ); ?>_table td.extra_instructions {
					font-size: small;
					white-space: normal;
				}
			</style>
			<?php
			return ob_get_clean();
		}

		/**
		 * Thank you page instructions table HTML
		 *
		 * @param int   $order_id    The order ID.
		 * @param float $order_total The order total.
		 */
		private function thankyou_instructions_table_html( $order_id, $order_total ) {
			// Missing MB WAY email or phone number?
			$alt                = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_title', $this->title ) : $this->title );
			$extra_instructions = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_extra_instructions', $this->extra_instructions ) : $this->extra_instructions );
			// We actually do not use $ent, $ref or $order_total - We'll just get the details
			$mbway_order_details = WC_IfthenPay_Webdados()->get_mbway_order_details( $order_id );
			$order               = wc_get_order( $order_id );
			ob_start();
			echo $this->thankyou_instructions_table_html_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			<table class="<?php echo esc_attr( $this->id ); ?>_table" cellpadding="0" cellspacing="0">
				<tr>
					<th colspan="2">
						<?php esc_html_e( 'Payment instructions', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
						<br/>
						<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->mbway_banner ); ?>" alt="<?php echo esc_attr( $alt ); ?>" title="<?php echo esc_attr( $alt ); ?>"/>
					</th>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Information', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
					<td class="mb_value"><?php echo esc_html( apply_filters( 'mbway_ifthen_webservice_desc', get_bloginfo( 'name' ) . ' #' . $order->get_order_number(), $order_id ) ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
					<td class="mb_value"><?php echo wc_price( $mbway_order_details['val'], array( 'currency' => 'EUR' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
				</tr>
				<?php
				if ( isset( $mbway_order_details['exp'] ) && trim( $mbway_order_details['exp'] ) !== '' ) {
					?>
					<tr>
						<td><?php esc_html_e( 'Expiration', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
						<td class="mb_value"><?php echo wp_kses_post( WC_IfthenPay_Webdados()->mbway_format_expiration( $mbway_order_details['exp'], $order_id ) ); ?></td>
					</tr>
					<?php
				}
				?>
				<tr>
					<td colspan="2" class="extra_instructions"><?php echo wp_kses_post( nl2br( $extra_instructions ) ); ?>
						<br/>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: number of minutes */
								esc_html__( 'You only have %d minutes to approve the payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								WC_IfthenPay_Webdados()->mbway_minutes
							)
						);
						?>
					</td>
				</tr>
			</table>
			<?php
			return apply_filters( 'mbway_ifthen_thankyou_instructions_table_html', ob_get_clean(), round( $order_total, 2 ), $order_id ); // Missing MB WAY email or phone number?
		}

		/**
		 * Thank you page instructions table HTML - Expired
		 *
		 * @param int   $order_id    The order ID.
		 * @param float $order_total The order total.
		 */
		private function thankyou_instructions_table_html_expired( $order_id, $order_total ) {
			// Missing MB WAY email or phone number?
			$alt   = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_title', $this->title ) : $this->title );
			$order = wc_get_order( $order_id );
			ob_start();
			echo $this->thankyou_instructions_table_html_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			<table class="<?php echo esc_attr( $this->id ); ?>_table" cellpadding="0" cellspacing="0">
				<tr>
					<th colspan="2">
						<?php esc_html_e( 'Payment instructions', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
						<br/>
						<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->mbway_banner ); ?>" alt="<?php echo esc_attr( $alt ); ?>" title="<?php echo esc_attr( $alt ); ?>"/>
					</th>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Information', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
					<td class="mb_value"><?php echo esc_html( apply_filters( 'mbway_ifthen_webservice_desc', get_bloginfo( 'name' ) . ' #' . $order->get_order_number(), $order->get_id() ) ); ?></td>
				</tr>
				<tr>
					<td colspan="2" class="extra_instructions">
						<?php
						echo wp_kses_post(
							sprintf(
								/* translators: %1$s: link opem, %2$s: link close */
								esc_html__( 'The payment deadline expired. %1$sPlease try again%2$s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								'<a href="' . esc_url( $order->get_checkout_payment_url() ) . '">',
								'</a>'
							)
						);
						?>
					</td>
				</tr>
			</table>
			<?php
			return apply_filters( 'mbway_ifthen_thankyou_instructions_table_html_expired', ob_get_clean(), round( $order_total, 2 ), $order->get_id() ); // Missing MB WAY email or phone number?
		}

		/**
		 * Thank you page instructions table HTML
		 *
		 * @param WC_Order $order The order.
		 */
		public function order_details_after_order_table( $order ) {
			if ( is_wc_endpoint_url( 'view-order' ) ) {
				$this->thankyou( $order );
			}
		}

		/**
		 * Email instructions
		 *
		 * @param WC_Order $order         The order.
		 * @param bool     $sent_to_admin If it's sent to admin.
		 * @param bool     $plain_text    If it's plain text format.
		 * @param WC_Email $email         The email being sent.
		 */
		public function email_instructions_1( $order, $sent_to_admin, $plain_text, $email = null ) {
			// "Hyyan WooCommerce Polylang" Integration removes "email_instructions" so we use "email_instructions_1"
			$this->email_instructions( $order, $sent_to_admin, $plain_text, $email );
		}

		/**
		 * Email instructions
		 *
		 * @param WC_Order $order         The order.
		 * @param bool     $sent_to_admin If it's sent to admin.
		 * @param bool     $plain_text    If it's plain text format.
		 * @param WC_Email $email         The email being sent.
		 */
		private function email_instructions( $order, $sent_to_admin, $plain_text, $email = null ) {
			// Apply filter
			$send = apply_filters( 'mbway_ifthen_send_email_instructions', true, $order, $sent_to_admin, $plain_text, $email );
			// Send
			if ( $send ) {
				// Go
				$order_deposit = WC_IfthenPay_Webdados()->deposit_is_ifthenpay( $order, $this->id );
				if ( $this->id === $order->get_payment_method() || $order_deposit ) {
					if ( isset( $order_deposit ) && $order_deposit ) {
						$order = $order_deposit;
					}
					$show = false;
					if ( ! $sent_to_admin ) {
						$show = true;
					} elseif ( $this->send_to_admin ) {
							$show = true;
					}
					if ( $show ) {
						// Force correct language
						WC_IfthenPay_Webdados()->maybe_change_locale( $order );
						// On Hold or pending
						if ( WC_IfthenPay_Webdados()->order_needs_payment( $order ) ) {
							if ( WC_IfthenPay_Webdados()->wc_deposits_active && $order->get_status() === 'partially-paid' ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
								// WooCommerce deposits - No instructions
							} elseif ( apply_filters( 'mbway_ifthen_email_instructions_pending_send', true, $order->get_id() ) ) {
								// Missing MB WAY email or phone number?
								echo $this->email_instructions_table_html( $order->get_id(), round( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ), 2 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

							}
						} elseif ( $order->has_status( 'processing' ) || $order->has_status( 'completed' ) ) { // Processing
							if ( apply_filters( 'mbway_ifthen_email_instructions_payment_received_send', true, $order->get_id() ) ) {
								echo $this->email_instructions_payment_received( $order->get_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
						}
					}
				}
			}
		}

		/**
		 * The instructions table
		 *
		 * @param integer $order_id    The order ID.
		 * @param float   $order_total The order total.
		 */
		private function email_instructions_table_html( $order_id, $order_total ) {
			// Missing MB WAY email or phone number?
			$alt                = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_title', $this->title ) : $this->title );
			$extra_instructions = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_extra_instructions', $this->extra_instructions ) : $this->extra_instructions );
			// We actually do not use $ent, $ref or $order_total - We'll just get the details
			$mbway_order_details = WC_IfthenPay_Webdados()->get_mbway_order_details( $order_id );
			$order               = wc_get_order( $order_id );
			ob_start();
			?>
			<table cellpadding="10" cellspacing="0" align="center" border="0" style="margin: auto; margin-top: 2em; margin-bottom: 2em; border-collapse: collapse; border: 1px solid #1465AA; border-radius: 4px !important; background-color: #FFFFFF;">
				<tr>
					<td style="border: 1px solid #1465AA; border-top-right-radius: 4px !important; border-top-left-radius: 4px !important; text-align: center; color: #000000; font-weight: bold;" colspan="2">
						<?php esc_html_e( 'Payment instructions', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
						<br/>
						<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->mbway_banner_email ); ?>" alt="<?php echo esc_attr( $alt ); ?>" title="<?php echo esc_attr( $alt ); ?>" style="margin-top: 10px; max-height: 48px"/>
					</td>
				</tr>
				<tr>
					<td style="border-top: 1px solid #1465AA; color: #000000;"><?php esc_html_e( 'Information', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
					<td style="border-top: 1px solid #1465AA; color: #000000; white-space: nowrap; text-align: right;"><?php echo esc_html( apply_filters( 'mbway_ifthen_webservice_desc', get_bloginfo( 'name' ) . ' #' . $order->get_order_number(), $order_id ) ); ?></td>
				</tr>
				<tr>
					<td style="border-top: 1px solid #1465AA; color: #000000;"><?php esc_html_e( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
					<td style="border-top: 1px solid #1465AA; color: #000000; white-space: nowrap; text-align: right;"><?php echo wc_price( $mbway_order_details['val'], array( 'currency' => 'EUR' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
				</tr>
				<?php
				if ( isset( $mbway_order_details['exp'] ) && trim( $mbway_order_details['exp'] ) !== '' ) {
					?>
					<tr>
						<td style="border-top: 1px solid #1465AA; color: #000000;"><?php esc_html_e( 'Expiration', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
						<td style="border-top: 1px solid #1465AA; color: #000000; white-space: nowrap; text-align: right;"><?php echo wp_kses_post( WC_IfthenPay_Webdados()->mbway_format_expiration( $mbway_order_details['exp'], $order_id ) ); ?></td>
					</tr>
					<?php
				}
				?>
				<tr>
					<td style="font-size: x-small; border: 1px solid #1465AA; border-bottom-right-radius: 4px !important; border-bottom-left-radius: 4px !important; color: #000000; text-align: center;" colspan="2">
						<?php echo wp_kses_post( nl2br( $extra_instructions ) ); ?>
						<br/>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: number of minutes */
								esc_html__( 'You only have %d minutes to approve the payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								WC_IfthenPay_Webdados()->mbway_minutes
							)
						);
						?>
					</td>
				</tr>
			</table>
			<?php
			return apply_filters( 'mbway_ifthen_email_instructions_table_html', ob_get_clean(), round( $order_total, 2 ), $order_id ); // Missing MB WAY email or phone number?
		}

		/**
		 * Email instructions - payment received
		 *
		 * @param int $order_id The order ID.
		 */
		private function email_instructions_payment_received( $order_id ) {
			$alt = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_title', $this->title ) : $this->title );
			ob_start();
			?>
			<p style="text-align: center; margin: auto; margin-top: 1em; margin-bottom: 1em; padding-top: 1em; padding-bottom: 1em;" id="ifthenpay_payment_received">
				<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->mbway_banner_email ); ?>" alt="<?php echo esc_attr( $alt ); ?>" title="<?php echo esc_attr( $alt ); ?>" style="margin: auto; margin-top: 10px; max-height: 48px;"/>
				<br/>
				<strong><?php esc_html_e( 'MB WAY payment received.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong>
			</p>
			<?php
			return apply_filters( 'mbway_ifthen_email_instructions_payment_received', ob_get_clean(), $order_id );
		}

		/**
		 * Create MB WAY payment on the ifthenpay API
		 *
		 * @param integer $order_id The Order ID.
		 * @param string  $phone    The phone number.
		 * @return bool
		 */
		public function webservice_set_pedido( $order_id, $phone ) {
			return WC_IfthenPay_Webdados()->mbway_webservice_set_pedido( $order_id, $phone );
		}

		/**
		 * Process it
		 * WooCommerce already took care of nonces
		 *
		 * @param  int $order_id Order ID.
		 * @throws Exception     Error message.
		 */
		public function process_payment( $order_id ) {
			// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
			// Webservice
			$order = wc_get_order( $order_id );
			do_action( 'mbway_ifthen_before_process_payment', $order );

			if ( $order->get_total() > 0 ) {
				$phone = isset( $_POST[ $this->id . '_phone' ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $this->id . '_phone' ] ) ) ) : '';
				if ( empty( $phone ) ) { // Ticket: https://wordpress.org/support/topic/erro-pagamentos/
					$phone = isset( $_REQUEST[ $this->id . '_phone' ] ) ? trim( sanitize_text_field( wp_unslash( $_REQUEST[ $this->id . '_phone' ] ) ) ) : '';
				}
				if ( ! empty( $phone ) ) {
					if ( $this->webservice_set_pedido( $order->get_id(), $phone ) ) {
						if ( ! $this->order_initial_status_pending ) {
							// Mark as on-hold
							WC_IfthenPay_Webdados()->set_initial_order_status( $order, 'on-hold', 'MB WAY' );
						} else {
							// Mark as pending
							WC_IfthenPay_Webdados()->set_initial_order_status( $order, 'pending', 'MB WAY' );
						}
					} else {
						throw new Exception(
							sprintf(
								/* translators: %s: payment method */
								esc_html__( 'An error occurred processing the %s Payment request - please try again', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								'MB WAY'
							)
						);
					}
				} else {
					throw new Exception(
						sprintf(
							/* translators: %s: payment method */
							esc_html__( 'An error occurred processing the %s Payment request - please try again', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'MB WAY'
						)
						.
						' - '
						.
						esc_html__( 'No phone number set', 'multibanco-ifthen-software-gateway-for-woocommerce' )
					);
				}
			} else {
				// Value = 0
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
			// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Disable if key not correctly set
		 *
		 * @param array $available_gateways The available payment gateways.
		 */
		public function disable_if_settings_missing( $available_gateways ) {
			if (
				strlen( trim( $this->mbwaykey ) ) !== 10
				||
				trim( $this->enabled ) !== 'yes'
			) {
				unset( $available_gateways[ $this->id ] );
			}
			return $available_gateways;
		}

		/**
		 * Just for €
		 *
		 * @param array $available_gateways The available payment gateways.
		 */
		public function disable_if_currency_not_euro( $available_gateways ) {
			return WC_IfthenPay_Webdados()->disable_if_currency_not_euro( $available_gateways, $this->id );
		}

		/**
		 * Just for Portugal
		 *
		 * @param array $available_gateways The available payment gateways.
		 */
		public function disable_unless_portugal( $available_gateways ) {
			return WC_IfthenPay_Webdados()->disable_unless_portugal( $available_gateways, $this->id );
		}

		/**
		 * Just above/below certain amounts
		 *
		 * @param array $available_gateways The available payment gateways.
		 */
		public function disable_only_above_or_below( $available_gateways ) {
			return WC_IfthenPay_Webdados()->disable_only_above_or_below( $available_gateways, $this->id, WC_IfthenPay_Webdados()->gateway_ifthen_min_value, WC_IfthenPay_Webdados()->gateway_ifthen_max_value );
		}

		/**
		 * Payment fields
		 */
		public function payment_fields() {
			echo wp_kses_post( wpautop( $this->description ) );
			?>
			<p class="form-row form-row-wide" id="<?php echo esc_attr( $this->id ); ?>_phone_field" style="display: block !important; margin-top: 1em;">
				<label for="<?php echo esc_attr( $this->id ); ?>_phone" style="display: block !important;">
					<?php esc_html_e( 'Your phone number linked to MB WAY', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
					<abbr class="required" title="<?php esc_html_e( 'required', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>">*</abbr>
				</label>
				<?php do_action( 'mbway_ifthen_checkout_before_phone_number' ); ?>
				<input type="tel" autocomplete="off" class="input-text" name="<?php echo esc_attr( $this->id ); ?>_phone" id="<?php echo esc_attr( $this->id ); ?>_phone" placeholder="9xxxxxxxx" maxlength="9" style="display: inline-block !important;" value="<?php echo esc_attr( apply_filters( 'mbway_ifthen_checkout_default_phone_number', '' ) ); ?>"/>
				<?php do_action( 'mbway_ifthen_checkout_after_phone_number' ); ?>
			</p>
			<?php
		}

		/**
		 * Validate frontend fields.
		 * WooCommerce already took care of nonces
		 *
		 * @return bool
		 */
		public function validate_fields() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$phone = isset( $_POST[ $this->id . '_phone' ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $this->id . '_phone' ] ) ) ) : '';
			if ( empty( $phone ) ) {
				wc_add_notice(
					sprintf(
						/* translators: %s: phone number */
						esc_html__( '%s is required', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'<strong>' . __( 'Phone number linked to MB WAY', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong>'
					),
					'error'
				);
				return false;
			} elseif ( strlen( $phone ) === 9 && intval( substr( $phone, 0, 1 ) ) === 9 && ctype_digit( $phone ) ) {
					return true;
			} else {
				wc_add_notice(
					sprintf(
						/* translators: %s: phone number */
						esc_html__( '%s must be a valid portuguese mobile phone number', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'<strong>' . __( 'Phone number linked to MB WAY', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</strong>'
					),
					'error'
				);
				return false;
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/**
		 * Payment complete
		 *
		 * @param  WC_Order $order Order object.
		 * @param  string   $txn_id Transaction ID.
		 * @param  string   $note Payment note.
		 */
		public function payment_complete( $order, $txn_id = '', $note = '' ) {
			$order->add_order_note( $note );
			$order->payment_complete( $txn_id );
		}

		/**
		 * Reduce stock on 'wc_maybe_reduce_stock_levels'?
		 *
		 * @param bool    $reduce_order_stock Reduce stock?.
		 * @param integer $order_id           The order ID.
		 */
		public function woocommerce_payment_complete_reduce_order_stock( $reduce_order_stock, $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order->get_payment_method() === $this->id ) {
				return ( WC_IfthenPay_Webdados()->woocommerce_payment_complete_reduce_order_stock( $reduce_order_stock, $order->get_id(), $this->id, $this->stock_when ) );
			} else {
				return $reduce_order_stock;
			}
		}

		/**
		 * Callback
		 */
		public function callback() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			@ob_clean(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			// We must 1st check the situation and then process it and send email to the store owner in case of error.
			if (
				isset( $_GET['chave'] )
				&&
				isset( $_GET['referencia'] )
				&&
				isset( $_GET['idpedido'] )
				&&
				isset( $_GET['valor'] )
				&&
				isset( $_GET['estado'] )
			) {
				// Let's process it
				$this->debug_log( '- Callback (' . WC_IfthenPay_Webdados()->get_request_uri() . ') with all arguments from ' . WC_IfthenPay_Webdados()->get_remote_addr() );
				$referencia      = trim( sanitize_text_field( wp_unslash( $_GET['referencia'] ) ) );
				$id_pedido       = str_replace( ' ', '+', trim( sanitize_text_field( wp_unslash( $_GET['idpedido'] ) ) ) ); // If there's a plus sign on the URL We'll get it as a space, so we need to get it back
				$val             = floatval( $_GET['valor'] );
				$estado          = trim( sanitize_text_field( wp_unslash( $_GET['estado'] ) ) );
				$chave           = trim( sanitize_text_field( wp_unslash( $_GET['chave'] ) ) );
				$datahorapag     = isset( $_GET['datahorapag'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['datahorapag'] ) ) ) : '';
				$arguments_ok    = true;
				$arguments_error = '';
				if ( $chave !== trim( $this->secret_key ) ) {
					$arguments_ok     = false;
					$arguments_error .= ' - Key';
				}
				if ( $referencia === '' ) { // If using ifthen_webservice_send_order_number_instead_id, this can be a non-numeric value
					$arguments_ok     = false;
					$arguments_error .= ' - Referencia (numeric)';
				}
				if ( $id_pedido === '' ) {
					$arguments_ok     = false;
					$arguments_error .= ' - IdPedido';
				}
				if ( abs( $val ) < WC_IfthenPay_Webdados()->mbway_min_value ) {
					$arguments_ok     = false;
					$arguments_error .= ' - Value';
				}
				if ( ! in_array( $estado, array( 'PAGO', 'DEVOLVIDO' ), true ) ) {
					$arguments_ok     = false;
					$arguments_error .= ' - Estado';
				}
				if ( $arguments_ok ) { // Isto deve ser separado em vários IFs para melhor se identificar o erro
					// Payments
					if ( trim( $estado ) === 'PAGO' ) {
						$orders_exist   = false;
						$pending_status = apply_filters( 'mbway_ifthen_valid_callback_pending_status', WC_IfthenPay_Webdados()->unpaid_statuses ); // Double filter - Should we deprectate this one?
						$args           = array(
							'type'                         => array( 'shop_order', 'wcdp_payment' ), // Regular order or deposit
							'status'                       => $pending_status,
							'limit'                        => -1,
							'_' . $this->id . '_id_pedido' => $id_pedido,
						);
						$orders         = wc_get_orders( WC_IfthenPay_Webdados()->maybe_translate_order_query_args( $args ) );
						if ( count( $orders ) > 0 ) {
							$orders_exist = true;
							$orders_count = count( $orders );
							foreach ( $orders as $order ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedForeach
								// Just getting the last one
							}
						} else {
							$err = 'Error: No orders found awaiting payment with these details - We are going to try by reference (order id) only';
							$this->debug_log( '-- ' . $err, 'warning', true, 'Callback (' . WC_IfthenPay_Webdados()->get_http_host() . ' ' . WC_IfthenPay_Webdados()->get_request_uri() . ') from ' . WC_IfthenPay_Webdados()->get_remote_addr() . ' - We are going to try by reference (order id) only (if, immediately after, you get the “MB WAY payment received” log entry, you can ignore this error)' );
							// Maybe the webservice timed-out and we are getting the payment anyway?
							// We only used this when the ifthenpay / SIBS webservice was timming out, but now that we have the ifthen_webservice_send_order_number_instead_id filter
							$order = wc_get_order( intval( $referencia ) );
							if ( $order ) { // Not compatible with the new ifthen_webservice_send_order_number_instead_id filter
								// Maybe we should check for failed?
								if ( WC_IfthenPay_Webdados()->order_needs_payment( $order ) ) {
									$orders_exist = true;
									$orders_count = 1;
								} else {
									$err = '-- MB WAY payment received but it does not need payment - Order callbak reference ' . $referencia;
									$this->debug_log( '-- ' . $err, 'warning', true, 'Callback (' . WC_IfthenPay_Webdados()->get_http_host() . ' ' . WC_IfthenPay_Webdados()->get_request_uri() . ') from ' . WC_IfthenPay_Webdados()->get_remote_addr() );
								}
							} else {
								$err = 'Error: No orders found awaiting payment with these details - Order callback reference ' . $referencia;
								$this->debug_log( '-- ' . $err, 'warning', true, 'Callback (' . WC_IfthenPay_Webdados()->get_http_host() . ' ' . WC_IfthenPay_Webdados()->get_request_uri() . ') from ' . WC_IfthenPay_Webdados()->get_remote_addr() );
							}
						}
						if ( $orders_exist ) {
							if ( $orders_count === 1 ) {
								if (
									(string) $order->get_id() === (string) $referencia
									||
									(string) $order->get_order_number() === (string) $referencia // because ifthen_webservice_send_order_number_instead_id
								) {
									if ( floatval( $val ) === floatval( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ) ) ) {
										$note = __( 'MB WAY payment received.', 'multibanco-ifthen-software-gateway-for-woocommerce' );
										if ( ! empty( $datahorapag ) ) {
											$note .= ' ' . $datahorapag;
										}
										// WooCommerce Deposits second payment?
										if ( WC_IfthenPay_Webdados()->wc_deposits_active ) {
											if ( $order->get_meta( '_wc_deposits_order_has_deposit' ) === 'yes' ) { // Has deposit
												if ( $order->get_meta( '_wc_deposits_deposit_paid' ) === 'yes' ) { // First payment - OK!
													if ( $order->get_meta( '_wc_deposits_second_payment_paid' ) !== 'yes' ) { // Second payment - not ok
														if ( floatval( $order->get_meta( '_wc_deposits_second_payment' ) ) === floatval( $val ) ) { // This really seems like the second payment
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
										do_action( 'mbway_ifthen_callback_payment_complete', $order->get_id(), $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
										header( 'HTTP/1.1 200 OK' );
										$this->debug_log( '-- MB WAY payment received - Order ' . $order->get_id(), 'notice' );
										echo 'OK - MB WAY payment received';
									} else {
										header( 'HTTP/1.1 200 OK' );
										$err = 'Error: The value does not match';
										$this->debug_log( '-- ' . $err . ' - Order ' . $order->get_id(), 'warning', true, 'Callback (' . WC_IfthenPay_Webdados()->get_http_host() . ' ' . WC_IfthenPay_Webdados()->get_request_uri() . ') from ' . WC_IfthenPay_Webdados()->get_remote_addr() . ' - The value does not match' );
										echo esc_html( $err );
										do_action( 'mbway_ifthen_callback_payment_failed', $order->get_id(), $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
									}
								} else {
									header( 'HTTP/1.1 200 OK' );
									$err = 'Error: MB WAY payment received but order id or number does not match reference - Order callbak reference ' . $referencia . ' - Order id ' . $order->get_id() . ' - Order number ' . $order->get_order_number();
									$this->debug_log( '-- ' . $err, 'warning', true, 'Callback (' . WC_IfthenPay_Webdados()->get_http_host() . ' ' . WC_IfthenPay_Webdados()->get_request_uri() . ') from ' . WC_IfthenPay_Webdados()->get_remote_addr() );
									echo esc_html( $err );
									do_action( 'mbway_ifthen_callback_payment_failed', 0, $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
								}
							} else {
								header( 'HTTP/1.1 200 OK' );
								$err = 'Error: More than 1 order found awaiting payment with these details';
								$this->debug_log( '-- ' . $err, 'warning', true, 'Callback (' . WC_IfthenPay_Webdados()->get_http_host() . ' ' . WC_IfthenPay_Webdados()->get_request_uri() . ') from ' . WC_IfthenPay_Webdados()->get_remote_addr() . ' - More than 1 order found awaiting payment with these details' );
								echo esc_html( $err );
								do_action( 'mbway_ifthen_callback_payment_failed', 0, $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							}
						} else {
							header( 'HTTP/1.1 200 OK' );
							$err = 'Error: No orders found awaiting payment with these details';
							$this->debug_log( '-- ' . $err, 'warning', true, 'Callback (' . WC_IfthenPay_Webdados()->get_http_host() . ' ' . WC_IfthenPay_Webdados()->get_request_uri() . ') from ' . WC_IfthenPay_Webdados()->get_remote_addr() . ' - No orders found awaiting payment with these details' );
							echo esc_html( $err );
							do_action( 'mbway_ifthen_callback_payment_failed', 0, $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						}
						// Refunds
					} elseif ( trim( $estado ) === 'DEVOLVIDO' && $this->do_refunds ) {
						// Porque não é compatível com o novo filtro ifthen_webservice_send_order_number_instead_id temos de ir buscar primeiro a order através do idPedido que é o mesmo e depois ir buscar os refunds que são childs dessa order
						$order_exist   = false;
						$refunds_exist = false;
						// First, find the order, using $id_pedido as $referencia may not be a order id because of ifthen_webservice_send_order_number_instead_id
						$args   = array(
							'type'                         => array( 'shop_order' ),
							'limit'                        => -1,
							'_' . $this->id . '_id_pedido' => $id_pedido,
						);
						$orders = wc_get_orders( WC_IfthenPay_Webdados()->maybe_translate_order_query_args( $args ) );
						if ( ! empty( $orders ) ) {
							if ( count( $orders ) === 1 ) {
								$order       = $orders[0];
								$order_exist = true;
							} else {
								$err = 'Error: More than 1 order found with the same id_pedido';
							}
						} else {
							$err = 'Error: No orders found with this id_pedido';
						}
						if ( $order_exist ) {
							// Find the exact refund
							$args    = array(
								'type'    => array( 'shop_order_refund' ), // Refund
								'limit'   => -1,
								'parent'  => intval( $order->get_id() ),
								'orderby' => 'modified',
								'order'   => 'ASC',                        // Oldest recent refunds first, so we process them in order if there are several
							);
							$refunds = wc_get_orders( WC_IfthenPay_Webdados()->maybe_translate_order_query_args( $args ) );
							foreach ( $refunds as $refund ) {
								if ( $refund->get_meta( '_' . WC_IfthenPay_Webdados()->mbway_id . '_callback_received' ) === '' ) {
									if ( abs( floatval( $val ) ) === abs( floatval( WC_IfthenPay_Webdados()->get_order_total_to_pay( $refund ) ) ) ) {
										$note = sprintf(
											/* translators: %s: refund id */
											__( 'MB WAY callback received for successfully processed refund #%s by ifthenpay.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
											$refund->get_id()
										);
										$order->add_order_note( $note );
										// Set as callback received so we do not process it again
										$refund->update_meta_data( '_' . WC_IfthenPay_Webdados()->mbway_id . '_callback_received', date_i18n( 'Y-m-d H:i:s' ) );
										$refund->save();
										$refunds_exist = true;
									}
								}
							}
						}
						if ( $refunds_exist ) {
							// We're done!
							header( 'HTTP/1.1 200 OK' );
							$this->debug_log( '-- MB WAY refund received - Order ' . $order->get_id() . ' - Refund ' . $refund->get_id(), 'notice' );
							echo 'OK - MB WAY refund received';
							do_action( 'mbway_ifthen_callback_refund_complete', $order->get_id() );
						} else {
							header( 'HTTP/1.1 200 OK' );
							if ( ! isset( $err ) ) {
								$err = 'Error: No unprocessed refunds found with these details';
							}
							$this->debug_log( '-- ' . $err, 'warning', true, 'Callback (' . WC_IfthenPay_Webdados()->get_http_host() . ' ' . WC_IfthenPay_Webdados()->get_request_uri() . ') from ' . WC_IfthenPay_Webdados()->get_remote_addr() . ' - No refunds found with these details' );
							echo esc_html( $err );
							do_action( 'mbway_ifthen_callback_refund_failed', 0, $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						}
						// ???
					} else {
						header( 'HTTP/1.1 200 OK' );
						$err = 'Error: Cannot process ' . trim( $estado ) . ' status';
						$this->debug_log( '-- ' . $err, 'warning', true, 'Callback (' . WC_IfthenPay_Webdados()->get_http_host() . ' ' . WC_IfthenPay_Webdados()->get_request_uri() . ') from ' . WC_IfthenPay_Webdados()->get_remote_addr() . ' - Cannot process ' . trim( $estado ) . ' status' );
						echo esc_html( $err );
						do_action( 'mbway_ifthen_callback_payment_failed', 0, $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					}
				} else {
					$err = 'Argument errors';
					$this->debug_log( '-- ' . $err . $arguments_error, 'warning', true, 'Callback (' . WC_IfthenPay_Webdados()->get_http_host() . ' ' . WC_IfthenPay_Webdados()->get_request_uri() . ') with argument errors from ' . WC_IfthenPay_Webdados()->get_remote_addr() . $arguments_error );
					do_action( 'mbway_ifthen_callback_payment_failed', 0, $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					wp_die( esc_html( $err ), 'WC_MBWAY_IfThen_Webdados', array( 'response' => 500 ) ); // Sends 500
				}
			} else {
				$err = 'Callback (' . WC_IfthenPay_Webdados()->get_request_uri() . ') with missing arguments from ' . WC_IfthenPay_Webdados()->get_remote_addr();
				$this->debug_log( '- ' . $err, 'warning', true, 'Callback (' . WC_IfthenPay_Webdados()->get_http_host() . ' ' . WC_IfthenPay_Webdados()->get_request_uri() . ') with missing arguments from ' . WC_IfthenPay_Webdados()->get_remote_addr() );
				do_action( 'mbway_ifthen_callback_payment_failed', 0, $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wp_die( 'Error: Something is missing...', 'WC_MBWAY_IfThen_Webdados', array( 'response' => 500 ) ); // Sends 500
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Do refunds
		 *
		 * @param integer       $order_id The order ID.
		 * @param float or null $amount   The amount to refund.
		 * @param string        $reason   The reason for refund.
		 */
		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			$result = WC_IfthenPay_Webdados()->process_refund( $order_id, $amount, $reason, $this->id );
			if ( $result === true ) {
				// Add note because there will be no callback - Actually there will be
				$order = wc_get_order( $order_id );
				$order->add_order_note( __( 'MB WAY refund successfully processed by ifthenpay.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) );
			}
			return $result;
		}

		/**
		 * Debug / Log - MOVED TO WC_IfthenPay_Webdados with gateway id as first argument
		 *
		 * @param string $message       The message to debug.
		 * @param string $level         The debug level.
		 * @param bool   $to_email      Send to email.
		 * @param string $email_message Email message.
		 */
		public function debug_log( $message, $level = 'debug', $to_email = false, $email_message = '' ) {
			if ( $this->debug ) {
				WC_IfthenPay_Webdados()->debug_log( $this->id, $message, $level, ( trim( $this->debug_email ) !== '' && $to_email ? $this->debug_email : false ), $email_message );
			}
		}

		/**
		 * Debug / Log Extra
		 *
		 * @param string $message       The message to debug.
		 * @param string $level         The debug level.
		 * @param bool   $to_email      Send to email.
		 * @param string $email_message Email message.
		 */
		public function debug_log_extra( $message, $level = 'debug', $to_email = false, $email_message = '' ) {
			if ( $this->debug ) {
				WC_IfthenPay_Webdados()->debug_log_extra( $this->id, $message, $level, ( trim( $this->debug_email ) !== '' && $to_email ? $this->debug_email : false ), $email_message );
			}
		}

		/**
		 * Global admin notices
		 */
		public function admin_notices() {
			// Callback email
			if (
				trim( $this->enabled ) === 'yes'
				&&
				strlen( trim( $this->mbwaykey ) ) === 10
				&&
				trim( $this->secret_key ) !== ''
			) {
				$callback_email_sent = get_option( $this->id . '_callback_email_sent' );
				if ( $callback_email_sent === 'no' || $callback_email_sent === false ) {
					if ( ! isset( $_GET['callback_warning'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						if ( apply_filters( 'mbway_ifthen_show_callback_notice', true ) ) {
							?>
							<div id="mbway_ifthen_callback_notice" class="notice notice-error" style="padding-right: 38px; position: relative;">
								<p>
									<strong>MB WAY (ifthenpay)</strong>
									<br/>
									<?php esc_html_e( 'You haven’t yet asked ifthenpay for the “Callback” activation. The orders will NOT be automatically updated upon payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
									<br/>
									<strong><?php esc_html_e( 'This is important', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>! <a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=mbway_ifthen_for_woocommerce&amp;callback_warning=1"><?php esc_html_e( 'Do it here', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></a>!</strong>
								</p>
							</div>
							<?php
						}
					}
				}
			}
			// New method
			if (
				(
					strlen( trim( $this->mbwaykey ) ) !== 10
					||
					trim( $this->enabled ) !== 'yes'
				)
				&&
				( ! apply_filters( 'multibanco_ifthen_hide_newmethod_notifications', false ) )
			) {
				?>
				<div id="mbway_ifthen_newmethod_notice" class="notice notice-info is-dismissible" style="padding-right: 38px; position: relative; display: none;">
					<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->mbway_banner ); ?>" style="float: left; margin-top: 0.5em; margin-bottom: 0.5em; margin-right: 1em; max-height: 48px; max-width: 114px;"/>
					<p>
						<?php
							echo wp_kses_post(
								sprintf(
									/* translators: %s: payment method */
									__( 'There’s a new payment method available: %s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									'<strong>MB WAY (ifthenpay)</strong>'
								)
							);
						?>
						<br/>
						<?php
						echo wp_kses_post(
							sprintf(
								/* translators: %1$s: open link, %2$s: close link */
								esc_html__( 'Ask ifthenpay to activate it on your account and then %1$sconfigure it here%2$s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								sprintf(
									'<strong><a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=%s">',
									$this->id
								),
								'</a></strong>'
							)
						);
						?>
					</p>
				</div>
				<script type="text/javascript">
				(function () {
					notice    = jQuery( '#mbway_ifthen_newmethod_notice');
					dismissed = localStorage.getItem( '<?php echo esc_attr( $this->id ); ?>_newmethod_notice_dismiss' );
					if ( !dismissed ) {
						jQuery( notice ).show();
						jQuery( notice ).on( 'click', 'button.notice-dismiss', function() {
							localStorage.setItem( '<?php echo esc_attr( $this->id ); ?>_newmethod_notice_dismiss', 1 );
						});
					}
				}());
				</script>
				<?php
			}
		}
	}
}
