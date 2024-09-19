<?php
/**
 * Credit Card class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CreditCard IfThen Class.
 */
if ( ! class_exists( 'WC_CreditCard_IfThen_Webdados' ) ) {

	class WC_CreditCard_IfThen_Webdados extends WC_Payment_Gateway {

		/* Single instance */
		protected static $_instance = null;
		public static $instances    = 0;

		/* Properties */
		public $debug;
		public $debug_email;
		public $version;
		public $api_url_production;
		public $api_url_sandbox;
		public $api_url;
		public $creditcardkey;
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

			self::$instances++;

			$this->id = WC_IfthenPay_Webdados()->creditcard_id;

			// Logs
			$this->debug       = ( $this->get_option( 'debug' ) == 'yes' ? true : false );
			$this->debug_email = $this->get_option( 'debug_email' );

			// Check version and upgrade
			$this->version = WC_IfthenPay_Webdados()->get_version();
			$this->upgrade();

			$this->has_fields = false;

			$this->method_title       = __( 'Credit or debit card (IfthenPay)', 'multibanco-ifthen-software-gateway-for-woocommerce' );
			$this->method_description = __( 'Easy and simple payment using a Credit or debit card. (Payment service provided by IfthenPay)', 'multibanco-ifthen-software-gateway-for-woocommerce' );
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

			// Webservice
			$this->api_url_production = 'https://ifthenpay.com/api/creditcard/init/'; // production mode
			$this->api_url_sandbox    = 'https://ifthenpay.com/api/creditcard/sandbox/init/'; // test mode
			$this->api_url            = '';

			// Plugin options and settings
			$this->init_form_fields();
			$this->init_settings();

			// User settings
			$this->title                     = $this->get_option( 'title' );
			$this->description               = $this->get_option( 'description' );
			$this->creditcardkey             = $this->get_option( 'creditcardkey' );
			$this->settings_saved            = $this->get_option( 'settings_saved' );
			$this->send_to_admin             = ( $this->get_option( 'send_to_admin' ) == 'yes' ? true : false );
			$this->only_portugal             = ( $this->get_option( 'only_portugal' ) == 'yes' ? true : false );
			$this->only_above                = $this->get_option( 'only_above' );
			$this->only_below                = $this->get_option( 'only_bellow' );
			$this->do_refunds                = ( $this->get_option( 'do_refunds' ) == 'yes' ? true : false );
			$this->do_refunds_backoffice_key = $this->get_option( 'do_refunds_backoffice_key' );
			if ( $this->do_refunds && trim( $this->do_refunds_backoffice_key ) != '' ) {
				$this->supports[] = 'refunds';
			}

			// Actions and filters
			if ( self::$instances === 1 ) { // Avoid duplicate actions and filters if it's initiated more than once (if WooCommerce loads after us)

				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
				if ( WC_IfthenPay_Webdados()->wpml_active ) {
					add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'register_wpml_strings' ) );
				}
				add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou' ) );
				add_action( 'woocommerce_order_details_after_order_table', array( $this, 'order_details_after_order_table' ), 9 );
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_if_settings_missing' ) );
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_if_currency_not_euro' ) );
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_unless_portugal' ) );
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_only_above_or_below' ) );

				// NO SMS Integrations for Credit cards

				// Customer Emails
				// Regular orders
				add_action(
					apply_filters( 'creditcard_ifthen_email_hook', 'woocommerce_email_before_order_table' ),
					array( $this, 'email_instructions_1' ), // Avoid "Hyyan WooCommerce Polylang Integration" remove_action
					apply_filters( 'creditcard_ifthen_email_hook_priority', 10 ),
					4
				);
				// Subscriptions
				add_action(
					apply_filters( 'creditcard_ifthen_subscription_email_hook', 'woocommerce_email_before_subscription_table' ),
					array( $this, 'email_instructions_1' ), // Avoid "Hyyan WooCommerce Polylang Integration" remove_action
					apply_filters( 'creditcard_ifthen_subscription_email_hook_priority', 10 ),
					4
				);

				// Payment listener - Return from payment gateway
				add_action( 'woocommerce_api_wc_creditcard_ifthen_webdados', array( $this, 'callback' ) );

				// Admin notices
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );

				// API URL
				$this->api_url = apply_filters( 'creditcard_ifthen_sandbox', false ) ? $this->api_url_sandbox : $this->api_url_production;

				// Method title in sandbox mode
				if ( apply_filters( 'creditcard_ifthen_sandbox', false ) ) {
					$this->title .= ' - SANDBOX (TEST MODE)';
				}
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
				// Nothing so far
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
		function register_wpml_strings() {
			// These are already registered by WooCommerce Multilingual
			/*
			$to_register=array(
				'title',
				'description',
			);*/
			$to_register = array();
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
				'enabled'       => array(
					'title'   => __( 'Enable/Disable', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable “Credit or debit card” (using IfthenPay)', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'default' => 'no',
				),
				'creditcardkey' => array(
					'title'             => __( 'Credit card Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'type'              => 'text',
					'description'       => __( 'Credit card Key provided by IfthenPay when signing the contract.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ( apply_filters( 'creditcard_ifthen_sandbox', false ) ? '<br><span style="color: red;">Sandbox</span>' : '' ),
					'default'           => '',
					'css'               => 'width: 130px;',
					'placeholder'       => 'XXX-000000',
					'custom_attributes' => array(
						'maxlength' => 10,
						'size'      => 14,
					),
				),
			);
			// if ( strlen( trim( $this->get_option( 'creditcardkey' ) ) ) == 10 && trim( $this->secret_key ) != '' ) {
				$this->form_fields = array_merge(
					$this->form_fields,
					array(
						'title'         => array(
							'title'       => __( 'Title', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'text',
							'description' => __( 'This controls the title which the user sees during checkout.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
											. ( WC_IfthenPay_Webdados()->wpml_active ? '<br/>' . WC_IfthenPay_Webdados()->wpml_translation_info : '' ),
							'default'     => 'Credit or debit card',
						),
						'description'   => array(
							'title'       => __( 'Description', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'textarea',
							'description' => __( 'This controls the description which the user sees during checkout.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
											. ( WC_IfthenPay_Webdados()->wpml_active ? '<br/>' . WC_IfthenPay_Webdados()->wpml_translation_info : '' ),
							'default'     => $this->get_method_description(),
						),
						'only_portugal' => array(
							'title'   => __( 'Only for Portuguese customers?', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'    => 'checkbox',
							'label'   => __( 'Enable only for customers whose billing or shipping address is in Portugal', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'default' => 'no',
						),
						'only_above'    => array(
							'title'       => __( 'Only for orders from', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'number',
							'description' => __( 'Enable only for orders with a value from x &euro;. Leave blank (or zero) to allow for any order value.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' <br/> ' . sprintf(
								__( 'By design, %1$s only allows payments from %2$s to %3$s. You can use this option to further limit this range.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								__( 'Credit or debit card', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								wc_price( WC_IfthenPay_Webdados()->creditcard_min_value, array( 'currency' => 'EUR' ) ),
								wc_price( WC_IfthenPay_Webdados()->creditcard_max_value, array( 'currency' => 'EUR' ) )
							),
							'default'     => '',
						),
						'only_bellow'   => array(
							'title'       => __( 'Only for orders up to', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'number',
							'description' => __( 'Enable only for orders with a value up to x &euro;. Leave blank (or zero) to allow for any order value.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' <br/> ' . sprintf(
								__( 'By design, %1$s only allows payments from %2$s to %3$s. You can use this option to further limit this range.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								__( 'Credit or debit card', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								wc_price( WC_IfthenPay_Webdados()->creditcard_min_value, array( 'currency' => 'EUR' ) ),
								wc_price( WC_IfthenPay_Webdados()->creditcard_max_value, array( 'currency' => 'EUR' ) )
							),
							'default'     => '',
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
										'description' => __( 'Shows “Credit or debit card” (using IfthenPay) as a supported payment gateway, and automatically sets subscription renewal orders to be paid with Credit or debit card if the original subscription used this payment method. If this option is not activated, Credit or debit card will only be available as a payment method for subscriptions if the “Manual Renewal Payments” option is enabled on WooCommerce Subscriptions settings.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
										'default' => 'no'
									),
					) );
				}*/
				$this->form_fields = array_merge(
					$this->form_fields,
					array(
						'do_refunds'                => array(
							'title' => __( 'Process refunds?', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'  => 'checkbox',
							'label' => __( 'Allow to refund via Credit or debit card when the order is completely or partially refunded in WooCommerce', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						),
						'do_refunds_backoffice_key' => array(
							'title'       => __( 'Backoffice key', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'text',
							'default'     => '',
							'description' => __( 'The IfthenPay backoffice key you got after signing the contract is needed to process refunds', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						),
					)
				);
				$this->form_fields = array_merge(
					$this->form_fields,
					array(
						'debug'       => array(
							'title'       => __( 'Debug Log', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'checkbox',
							'label'       => __( 'Enable logging', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'default'     => 'yes',
							'description' => sprintf(
								__( 'Log plugin events in %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								( ( defined( 'WC_LOG_HANDLER' ) && 'WC_Log_Handler_DB' === WC_LOG_HANDLER ) || version_compare( WC_VERSION, '8.6', '>=' ) )
								?
								'<a href="admin.php?page=wc-status&tab=logs&source=' . esc_attr( $this->id ) . '" target="_blank">' . __( 'WooCommerce &gt; Status &gt; Logs', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . '</a>'
								:
								'<code>' . wc_get_log_file_path( $this->id ) . '</code>'
							),
						),
						'debug_email' => array(
							'title'       => __( 'Debug to email', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'email',
							'label'       => __( 'Enable email logging', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'default'     => '',
							'description' => __( 'Send main plugin events to this email address.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						),
					)
				);
			// }
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
			$this->form_fields = array_merge( $this->form_fields, apply_filters( 'multibanco_ifthen_creditcard_settings_fields', array() ) );
			// And to manipulate them
			$this->form_fields = apply_filters( 'multibanco_ifthen_creditcard_settings_fields_all', $this->form_fields );

		}
		public function admin_options() {
			$title = esc_html( $this->get_method_title() );
			?>
			<div id="wc_ifthen">
				<?php
				if ( ! apply_filters( 'multibanco_ifthen_hide_settings_right_bar', false ) ) {
					WC_IfthenPay_Webdados()->admin_pro_banner();
				}
				?>
				<?php
				if ( ! apply_filters( 'multibanco_ifthen_hide_settings_right_bar', false ) ) {
					WC_IfthenPay_Webdados()->admin_right_bar();
				}
				?>
				<div id="wc_ifthen_settings">
					<h2>
						<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->creditcard_banner ); ?>" alt="<?php echo esc_attr( $title ); ?>" width="56" height="48"/>
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
						<li><?php _e( 'Fill out all the details (Credit card Key) provided by <strong>IfthenPay</strong> in the fields below.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
						<li>
						<?php
						printf(
							__( 'Never use the same %1$s on more than one website or any other system, online or offline. Ask %2$s for new ones for each single platform.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							__( 'Credit card Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'<a href="https://ifthenpay.com/' . esc_attr( WC_IfthenPay_Webdados()->out_link_utm ) . '" target="_blank">IfthenPay</a>'
						);
						?>
						</li>
					</ul>
					<?php
					if (
						strlen( trim( $this->creditcardkey ) ) == 10
					) {
						// OK
					} else {
						if ( $this->settings_saved == 1 ) {
							?>
							<div id="message" class="error">
								<p><strong><?php _e( 'Invalid Credit card Key (exactly 10 characters).', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong></p>
							</div>
							<?php
						} else {
							?>
							<div id="message" class="error">
								<p><strong><?php _e( 'Set the Credit card Key and Save changes to set other plugin options.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong></p>
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

		/**
		 * Icon HTML
		 */
		public function get_icon() {
			$alt       = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_title', $this->title ) : $this->title );
			$icon_html = '<img src="' . esc_attr( WC_IfthenPay_Webdados()->creditcard_icon ) . '" alt="' . esc_attr( $alt ) . '" width="28" height="24"/>';
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

					// We are only going to be here if it's a deposit payment. We might have to deal with it...

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
			</style>
			<?php
			return ob_get_clean();
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
			// Apply filter
			$send = apply_filters( 'creditcard_ifthen_send_email_instructions', $send, $order, $sent_to_admin, $plain_text, $email );
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
								// We should not be here because there's no email for pending orders
							}
						} else {
							// Processing
							if ( $order->has_status( 'processing' ) || $order->has_status( 'completed' ) ) {
								if ( apply_filters( 'creditcard_ifthen_email_instructions_payment_received_send', true, $order->get_id() ) ) {
									echo $this->email_instructions_payment_received( $order->get_id() );
								}
							}
						}
					}
				}
			}
		}
		/*
		function email_instructions_table_html( $order_id, $order_total ) {
			return apply_filters( 'creditcard_ifthen_email_instructions_table_html', ob_get_clean(), round( $order_total, 2 ), $order_id );
		}*/
		function email_instructions_payment_received( $order_id ) {
			$alt = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_title', $this->title ) : $this->title );
			ob_start();
			?>
			<p style="text-align: center; margin: auto; margin-top: 2em; margin-bottom: 2em;">
				<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->creditcard_banner_email ); ?>" alt="<?php echo esc_attr( $alt ); ?>" title="<?php echo esc_attr( $alt ); ?>" style="margin: auto; margin-top: 10px; max-height: 48px;"/>
				<br/>
				<strong><?php _e( 'Credit or debit card payment received.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong>
				<br/>
				<?php _e( 'We will now process your order.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
			</p>
			<?php
			return apply_filters( 'creditcard_ifthen_email_instructions_payment_received', ob_get_clean(), $order_id );
		}

		/**
		 * API Init Payment
		 */
		function api_init_payment( $order_id ) {
			$id            = $order_id; // We could randomize this...
			$order         = wc_get_order( $order_id );
			$valor         = round( floatval( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ) ), 2 );
			$creditcardkey = apply_filters( 'multibanco_ifthen_base_creditcardkey', $this->creditcardkey, $order );
			$wd_secret     = substr( strrev( md5( time() ) ), 0, 10 ); // Set a secret on our end for extra validation
			$url           = $this->api_url . $creditcardkey;
			$args          = array(
				'method'   => 'POST',
				'timeout'  => apply_filters( 'creditcard_ifthen_api_timeout', 30 ),
				'blocking' => true,
				'body'     => array(
					'orderId'    => (string) apply_filters( 'ifthen_webservice_send_order_number_instead_id', false ) ? $order->get_order_number() : $order->get_id(),
					'amount'     => (string) $valor,
					'successUrl' => add_query_arg( 'wd_secret', $wd_secret, add_query_arg( 'status', 'success', WC_IfthenPay_Webdados()->creditcard_notify_url ) ),
					'errorUrl'   => add_query_arg( 'status', 'error', WC_IfthenPay_Webdados()->creditcard_notify_url ),
					'cancelUrl'  => add_query_arg( 'status', 'cancel', WC_IfthenPay_Webdados()->creditcard_notify_url ),
					'language'   => substr( trim( get_locale() ), 0, 2 ),
				),
			);
			$args['body']  = json_encode( $args['body'] ); // Json not post variables
			$response      = wp_remote_post( $url, $args );
			if ( is_wp_error( $response ) ) {
				$debug_msg       = '- Error contacting the IfthenPay servers - Order ' . $order->get_id() . ' - ' . $response->get_error_message();
				$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
				$this->debug_log( $debug_msg, 'error', true, $debug_msg_email );
				return false;
			} else {
				if ( isset( $response['response']['code'] ) && intval( $response['response']['code'] ) == 200 && isset( $response['body'] ) && trim( $response['body'] ) != '' ) {
					if ( $body = json_decode( trim( $response['body'] ) ) ) {
						if ( intval( $body->Status ) == 0 ) {
							WC_IfthenPay_Webdados()->multibanco_set_order_creditcard_details(
								$order->get_id(),
								array(
									'creditcardkey' => $creditcardkey,
									'request_id'    => $body->RequestId,
									'id'            => apply_filters( 'ifthen_webservice_send_order_number_instead_id', false ) ? $order->get_order_number() : $order->get_id(),
									'val'           => $valor,
									'payment_url'   => $body->PaymentUrl,
									'wd_secret'     => $wd_secret,
								)
							);
							$this->debug_log( '- Credit card payment request created on IfthenPay servers - Redirecting to payment gateway - Order ' . $order->get_id() . ' - RequestId: ' . $body->RequestId );
							do_action( 'creditcard_ifthen_created_reference', $body->RequestId, $order->get_id() );
							return $body->PaymentUrl;
						} else {
							$debug_msg = '- Error contacting the IfthenPay servers - Order ' . $order->get_id() . ' - Error code and message: ' . $body->Status . ' / ' . $body->Message;
							$this->debug_log( $debug_msg, 'error', true, $debug_msg );
							return false;
						}
					} else {
						$debug_msg = '- Error contacting the IfthenPay servers - Order ' . $order->get_id() . ' - Can not json_decode body';
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
		 * Process it
		 */
		function process_payment( $order_id ) {
			// Webservice
			$order = wc_get_order( $order_id );
			do_action( 'creditcard_ifthen_before_process_payment', $order );
			if ( $order->get_total() > 0 ) {
				if ( $redirect_url = $this->api_init_payment( $order->get_id() ) ) {
					// WooCommerce Deposits - When generating second payment reference the order goes from partially paid to on hold, and that has an email (??!)
					if ( WC_IfthenPay_Webdados()->wc_deposits_active && $order->get_status() == 'partially-paid' ) {
						add_filter( 'woocommerce_email_enabled_customer_processing_order', '__return_false' );
						add_filter( 'woocommerce_email_enabled_full_payment', '__return_false' );
					}
					// Mark pending
					WC_IfthenPay_Webdados()->set_initial_order_status( $order, 'pending', __( 'Credit or debit card', 'multibanco-ifthen-software-gateway-for-woocommerce' ) );
				} else {
					throw new Exception(
						sprintf(
							/* translators: %s: payment method */
							__( 'An error occurred processing the %s Payment request - please try again', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							__( 'Credit or debit card', 'multibanco-ifthen-software-gateway-for-woocommerce' )
						)
					);
				}
			} else {
				// Value = 0
				$order->payment_complete();
			}
			// Remove cart - not now, only after paid
			// if ( isset( WC()->cart ) ) {
			// WC()->cart->empty_cart();
			// }
			// Empty awaiting payment session - not now, only after paid
			// unset( WC()->session->order_awaiting_payment );
			// Return payment url redirect
			return array(
				'result'   => 'success',
				'redirect' => $redirect_url, // Payment gateway URL
			);
		}


		/**
		 * Disable if key not correctly set
		 */
		function disable_if_settings_missing( $available_gateways ) {
			if (
				strlen( trim( $this->creditcardkey ) ) != 10
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
			return WC_IfthenPay_Webdados()->disable_only_above_or_below( $available_gateways, $this->id, WC_IfthenPay_Webdados()->creditcard_min_value, WC_IfthenPay_Webdados()->creditcard_max_value );
		}

		/* Payment complete - Stolen from PayPal method */
		function payment_complete( $order, $txn_id = '', $note = '' ) {
			$order->add_order_note( $note );
			$order->payment_complete( $txn_id );
			// As in PayPal, we only empty the cart if it was paid
			if ( isset( WC()->cart ) ) {
				WC()->cart->empty_cart();
			}
			// Empty awaiting payment session - Only now
			unset( WC()->session->order_awaiting_payment );
		}

		/**
		 * Callback - Return from the payment gateway
		 */
		function callback() {

			$redirect_url = '';
			$error        = false;
			$order_id     = 0;
			$orders_exist = false;

			if (
				isset( $_GET['status'] )
				&&
				isset( $_GET['id'] )
				&&
				isset( $_GET['amount'] )
				&&
				isset( $_GET['requestId'] )
			) {
				$this->debug_log( '- Callback (' . $_SERVER['REQUEST_URI'] . ') with all arguments' );
				$request_id = trim( sanitize_text_field( $_GET['requestId'] ) );
				$id         = trim( sanitize_text_field( $_GET['id'] ) );
				$val        = trim( sanitize_text_field( $_GET['amount'] ) ); // Não fazemos float porque 7.40 passaria a 7.4 e depois não validava a hash
				$wd_secret  = isset( $_GET['wd_secret'] ) ? trim( sanitize_text_field( $_GET['wd_secret'] ) ) : '_';
				switch ( trim( $_GET['status'] ) ) {

					case 'success':
						$get_order = $this->callback_helper_get_pending_order( $request_id, $id, $val, $wd_secret );
						if ( $get_order['success'] && $get_order['order'] ) {
							$order = $get_order['order'];
							$this->debug_log_extra( 'Order found: ' . $order->get_id() . ' - Status: ' . $order->get_status() );
							$order_id      = $order->get_id();
							$order_details = WC_IfthenPay_Webdados()->get_creditcard_order_details( $order->get_id() );
							$sk            = isset( $_GET['sk'] ) ? trim( sanitize_text_field( $_GET['sk'] ) ) : '';
							$hash          = hash_hmac( 'sha256', $id . $val . $request_id, $order_details['creditcardkey'] );
							if ( $sk == $hash ) {
								$this->debug_log_extra( 'Order found: ' . $order->get_id() . ' - Hash ok' );
								$note = __( 'Credit or debit card payment received.', 'multibanco-ifthen-software-gateway-for-woocommerce' );
								// WooCommerce Deposits second payment?
								if ( WC_IfthenPay_Webdados()->wc_deposits_active ) {
									if ( $order->get_meta( '_wc_deposits_order_has_deposit' ) == 'yes' ) { // Has deposit
										if ( $order->get_meta( '_wc_deposits_deposit_paid' ) == 'yes' ) { // First payment - OK!
											if ( $order->get_meta( '_wc_deposits_second_payment_paid' ) != 'yes' ) { // Second payment - not ok
												if ( floatval( $order->get_meta( '_wc_deposits_second_payment' ) ) == floatval( $val ) ) { // This really seems like the second payment
													// Set the current order status temporarly back to partially-paid, but first stop the emails
													add_filter( 'woocommerce_email_enabled_customer_partially_paid', '__return_false' );
													add_filter( 'woocommerce_email_enabled_partial_payment', '__return_false' );
													$order->update_status( 'partially-paid', __( 'Temporary status. Used to force WooCommerce Deposits to correctly set the order to processing.', '	multibanco-ifthen-software-gateway-for-woocommerce' ) );
												}
											}
										}
									}
								}
								$url = $this->get_return_url( $order );
								$this->payment_complete( $order, '', $note );
								do_action( 'creditcard_ifthen_callback_payment_complete', $order->get_id(), $_GET );
								$debug_order = wc_get_order( $order->get_id() );
								$this->debug_log( '-- Credit card payment received - Order ' . $order->get_id(), 'notice' );
								$this->debug_log_extra( 'payment_complete - Redirect to thank you page: ' . $url . ' - Order ' . $order->get_id() . ' - Status: ' . $debug_order->get_status() );
								wp_redirect( $url );
								exit;
							} else {
								$error = 'Error: IfthenPay security hash validation failed';
								// We should set a $redirect_url
							}
						} else {
							$error = $get_order['error'];
							// We should set a $redirect_url
						}
						break;

					case 'error':
						// No additional $_GET field with the error code or message?
						$get_order = $this->callback_helper_get_pending_order( $request_id, $id, $val );
						if ( $get_order['success'] && $get_order['order'] ) {
							$order    = $get_order['order'];
							$order_id = $order->get_id();
							$error    = __( 'Payment failed on the gateway. Please try again.', 'multibanco-ifthen-software-gateway-for-woocommerce' );
							$order->update_status( 'failed', $error );
						} else {
							$error = __( 'Payment failed on the gateway. Please try again.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' - ' . $get_order['error'];
						}
						wc_add_notice( $error, 'error' );
						$redirect_url = wc_get_checkout_url();
						break;

					case 'cancel':
						// This is called also when the user clicks the "Back" button on the gateway.
						// Maybe we can make this an option to cancel or just go back to the checkout.
						$get_order = $this->callback_helper_get_pending_order( $request_id, $id, $val );
						if ( $get_order['success'] && $get_order['order'] ) {
							if ( apply_filters( 'creditcard_ifthen_cancel_order_on_back', false, $get_order['order'] ) ) {
								$order    = $get_order['order'];
								$order_id = $order->get_id();
								$error    = __( 'Payment cancelled by the customer at the gateway.', 'multibanco-ifthen-software-gateway-for-woocommerce' );
								$order->update_status( 'failed', $error );
								$redirect_url = $order->get_cancel_order_url_raw();
								wc_add_notice( $error, 'error' ); // Notice OK, not block based page
							} else {
								// We got the order but are not going to cancel it - Default behavior since 9.4.1
								$error = __( 'Payment cancelled by the customer at the gateway. Please try again.', 'multibanco-ifthen-software-gateway-for-woocommerce' );
								$redirect_url = wc_get_checkout_url();
								wc_add_notice( $error, 'error' ); // Not working on the blocks checkout, we need to check how we did it on the Cofidis gateway
							}
						} else {
							// We can't get the order so we just redirect the customer to the checkout
							$error = __( 'Payment cancelled by the customer at the gateway. Please try again.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' - ' . $get_order['error'];
							$redirect_url = wc_get_checkout_url();
							wc_add_notice( $error, 'error' ); // Not working on the blocks checkout, we need to check how we did it on the Cofidis gateway
						}
						break;

					default:
						$error = 'Callback with invalid status';
						break;

				}
			} else {
				$error = 'Callback (' . $_SERVER['REQUEST_URI'] . ') with missing arguments';
			}

			// Error and redirect
			if ( $error ) {
				$this->debug_log( '- ' . $error, 'warning', true, $error );
				do_action( 'creditcard_ifthen_callback_payment_failed', $order_id, $error, $_GET );
				if ( $redirect_url ) {
					wp_redirect( $redirect_url );
				} else {
					// ???
				}
				exit;
			}

		}

		/* Do refunds */
		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			$result = WC_IfthenPay_Webdados()->process_refund( $order_id, $amount, $reason, $this->id );
			if ( $result === true ) {
				// Add note because there will be no callback
				$order = wc_get_order( $order_id );
				$order->add_order_note( __( 'Credit or debit card refund successfully processed by IfthenPay.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) );
			}
			return $result;
		}

		function callback_helper_get_pending_order( $request_id, $id, $val, $wd_secret = null ) {
			$return         = array(
				'success' => false,
				'error'   => false,
				'order'   => false,
			);
			$pending_status = apply_filters( 'creditcard_ifthen_valid_callback_pending_status', WC_IfthenPay_Webdados()->unpaid_statuses ); // Double filter - Should we deprectate this one?
			$args           = array(
				'type'                          => array( 'shop_order', 'wcdp_payment' ), // Regular order or deposit
				'status'                        => $pending_status,
				'limit'                         => -1,
				'_' . $this->id . '_request_id' => $request_id,
				'_' . $this->id . '_id'         => $id,
			);
			if ( ! is_null( $wd_secret ) ) {
				$args[ '_' . $this->id . '_wd_secret' ] = $wd_secret;
			}
			$orders_exist = false;
			$orders       = WC_IfthenPay_Webdados()->wc_get_orders( $args, $this->id );
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
						$return['success'] = true;
						$return['order']   = $order;
						return $return;
					} else {
						$return['error'] = 'Error: The value does not match';
						return $return;
					}
				} else {
					$return['error'] = 'Error: More than 1 order found awaiting payment with these details';
					return $return;
				}
			} else {
				$return['error'] = 'Error: No orders found awaiting payment with these details';
				return $return;
			}
		}

		/* Debug / Log - MOVED TO WC_IfthenPay_Webdados with gateway id as first argument */
		public function debug_log( $message, $level = 'debug', $to_email = false, $email_message = '' ) {
			if ( $this->debug ) {
				WC_IfthenPay_Webdados()->debug_log( $this->id, $message, $level, ( trim( $this->debug_email ) != '' && $to_email ? $this->debug_email : false ), $email_message );
			}
		}
		public function debug_log_extra( $message, $level = 'debug', $to_email = false, $email_message = '' ) {
			if ( $this->debug ) {
				WC_IfthenPay_Webdados()->debug_log_extra( $this->id, $message, $level, ( trim( $this->debug_email ) != '' && $to_email ? $this->debug_email : false ), $email_message );
			}
		}

		/* Global admin notices */
		function admin_notices() {
			// New method
			if (
				(
					strlen( trim( $this->creditcardkey ) ) != 10
					||
					trim( $this->enabled ) != 'yes'
				)
				&&
				( ! apply_filters( 'multibanco_ifthen_hide_newmethod_notifications', false ) )
			) {
				?>
				<div id="creditcard_ifthen_newmethod_notice" class="notice notice-info is-dismissible" style="padding-right: 38px; position: relative; display: none;">
					<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->creditcard_banner ); ?>" style="float: left; margin-top: 0.5em; margin-bottom: 0.5em; margin-right: 1em; max-height: 48px; max-width: 68px;"/>
					<p>
						<?php
							echo sprintf(
								__( 'There’s a new payment method available: %s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								'<strong>Credit or debit card (IfthenPay)</strong>'
							);
						?>
						<br/>
						<?php
						echo sprintf(
							__( 'Ask IfthenPay to activate it on your account and then %1$sconfigure it here%2$s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'<strong><a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=creditcard_ifthen_for_woocommerce">',
							'</a></strong>'
						);
						?>
					</p>
				</div>
				<script type="text/javascript">
				(function () {
					notice    = jQuery( '#creditcard_ifthen_newmethod_notice');
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
