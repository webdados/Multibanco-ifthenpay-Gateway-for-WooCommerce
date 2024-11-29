<?php
/**
 * Apple and Google Play class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gateway IfThen Class.
 */
if ( ! class_exists( 'WC_Gateway_IfThen_Webdados' ) ) {

	class WC_Gateway_IfThen_Webdados extends WC_Payment_Gateway {

		/* Single instance */
		protected static $_instance = null;
		public static $instances    = 0;

		/* Properties */
		public $debug;
		public $debug_email;
		public $version;
		public $secret_key;
		public $api_url_production;
		public $api_url_sandbox;
		public $api_url;
		public $gateways_api_url;
		public $gateways_methods_api_url;
		public $backoffice_key;
		public $gatewaykey;
		public $settings_saved;
		public $send_to_admin;
		public $only_portugal;
		public $only_above;
		public $only_below;
		public $stock_when;

		/**
		 * Constructor for your payment class
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {

			self::$instances++;

			$this->id = WC_IfthenPay_Webdados()->gateway_ifthen_id;

			// Logs
			$this->debug       = ( $this->get_option( 'debug' ) == 'yes' ? true : false );
			$this->debug_email = $this->get_option( 'debug_email' );

			// Check version and upgrade
			$this->version = WC_IfthenPay_Webdados()->get_version();
			$this->upgrade();

			$this->has_fields = false;

			$this->method_title       = __( 'IfthenPay Gateway', 'multibanco-ifthen-software-gateway-for-woocommerce' );
			$this->method_description = __( 'Easy and simple payment using Apple Pay, Google Pay, or Pix. (Via the IfthenPay Gateway)', 'multibanco-ifthen-software-gateway-for-woocommerce' );

			// Anti-phishing key
			$this->secret_key = $this->get_option( 'secret_key' );
			if ( trim( $this->secret_key ) == '' ) {
				// First load?
				$this->secret_key = md5( home_url() . time() . wp_rand( 0, 999 ) );
				// Save
				$this->update_option( 'secret_key', $this->secret_key );
				$this->update_option( 'debug', 'yes' );
			}

			// Webservice
			$this->api_url_production       = 'https://api.ifthenpay.com/gateway/pinpay/'; // production mode
			$this->api_url_sandbox          = ''; // test mode
			$this->api_url                  = '';
			$this->gateways_api_url         = 'https://www.ifthenpay.com/IfmbWS/ifthenpaymobile.asmx/GetGatewayKeys';
			$this->gateways_methods_api_url = 'https://www.ifthenpay.com/IfmbWS/ifthenpaymobile.asmx/GetAccountsByGatewayKey';

			// Plugin options and settings
			$this->init_form_fields();
			$this->init_settings();

			// User settings
			$this->title          = $this->get_option( 'title' );
			$this->description    = $this->get_option( 'description' );
			$this->backoffice_key = $this->get_option( 'backoffice_key' );
			$this->gatewaykey     = $this->get_option( 'gatewaykey' );
			$this->settings_saved = $this->get_option( 'settings_saved' );
			$this->send_to_admin  = ( $this->get_option( 'send_to_admin' ) == 'yes' ? true : false );
			$this->only_portugal  = ( $this->get_option( 'only_portugal' ) == 'yes' ? true : false );
			$this->only_above     = $this->get_option( 'only_above' );
			$this->only_below     = $this->get_option( 'only_bellow' );

			// Actions and filters
			if ( self::$instances === 1 ) { // Avoid duplicate actions and filters if it's initiated more than once (if WooCommerce loads after us)

				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options_update_gateways_and_methods' ) );
				if ( WC_IfthenPay_Webdados()->wpml_active ) {
					add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'register_wpml_strings' ) );
				}
				add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou' ) );
				add_action( 'woocommerce_order_details_after_order_table', array( $this, 'order_details_after_order_table' ), 9 );
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_if_settings_missing' ) ); // To activate again
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_if_currency_not_euro' ) );
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_unless_portugal' ) );
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_only_above_or_below' ) );

				// NO SMS Integrations for the Gateway

				// Customer Emails
				// Regular orders
				add_action(
					apply_filters( 'gateway_ifthen_email_hook', 'woocommerce_email_before_order_table' ),
					array( $this, 'email_instructions_1' ), // Avoid "Hyyan WooCommerce Polylang Integration" remove_action
					apply_filters( 'gateway_ifthen_email_hook_priority', 10 ),
					4
				);

				// Payment listener - Return from payment gateway
				add_action( 'woocommerce_api_wc_gateway_ifthen_webdados', array( $this, 'callback' ) );

				// Admin notices
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );

				// API URL
				$this->api_url = apply_filters( 'gateway_ifthen_sandbox', false ) ? $this->api_url_sandbox : $this->api_url_production;

				// Method title in frontend
				$this->title .= ' - ' . __( 'IfthenPay Gateway', 'multibanco-ifthen-software-gateway-for-woocommerce' );

				// Method title in sandbox mode
				if ( apply_filters( 'gateway_ifthen_sandbox', false ) ) {
					$this->title .= ' - SANDBOX (TEST MODE)';
				}

				// Availability checker - Maybe later
				// add_action( 'wp_enqueue_scripts', array( $this, 'frontend_classic_checkout_availability_check' ) );
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
		 * Get available gateway methods
		 */
		function get_available_gateway_methods() {
			$available_methods = array();
			if ( $gateway_methods = get_option( $this->id . '_gateway_methods' ) ) {
				if ( count( $gateway_methods ) > 0 ) {
					if ( is_array( $gateway_methods ) && count( $gateway_methods ) > 0 ) {
						foreach( $gateway_methods as $gateway_method ) {
							if ( ! is_numeric( $gateway_method->Entidade ) ) { // Multibanco legacy is not available
								if ( ! in_array(
									trim( $gateway_method->Entidade ),
									apply_filters( 'gateway_ifthen_unavailable_methods', array( 'MB', 'MBWAY', 'PAYSHOP', 'CCARD', 'COFIDIS' ) )
								) ) {
									if ( ! isset( $available_methods[ trim( $gateway_method->Entidade ) ] ) ) {
										$available_methods[ trim( $gateway_method->Entidade ) ] = array();
									}
									$available_methods[ trim( $gateway_method->Entidade ) ][ trim( $gateway_method->Conta ) ] = trim( $gateway_method->Alias );
									if ( trim( $gateway_method->Alias ) !== trim( $gateway_method->Conta ) ) {
										$available_methods[ trim( $gateway_method->Entidade ) ][ trim( $gateway_method->Conta ) ] .= ' (' . trim( $gateway_method->Conta ) . ')';
									}
								}
							}
						}
					}
				}
			}
			return $available_methods;
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
					'label'   => __( 'Enable “IfthenPay Gateway” (using IfthenPay)', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'default' => 'no',
				),
				'backoffice_key' => array(
					'title'       => __( 'Backoffice key', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'type'        => 'text',
					'default'     => '',
					'description' => __( 'The IfthenPay backoffice key you got after signing the contract', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ( apply_filters( 'gateway_ifthen_sandbox', false ) ? '<br><span style="color: red;">Sandbox</span>' : '' ),
					'placeholder'       => '0000-0000-0000-0000',
					'custom_attributes' => array(
						'maxlength' => 19,
						'size'      => 22,
					),
				),
				'gatewaykey' => array(
					'title'             => __( 'Gateway Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'type'              => 'text',
					'description'       => __( 'Gateway Key provided by IfthenPay when signing the contract.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ( apply_filters( 'gateway_ifthen_sandbox', false ) ? '<br><span style="color: red;">Sandbox</span>' : '' ),
					'default'           => '',
					'css'               => 'width: 130px;',
					'placeholder'       => 'XXXX-000000',
					'custom_attributes' => array(
						'maxlength' => 11,
						'size'      => 14,
					),
				),
				/*'gateway_methods' => array(
					'title'       => __( 'Payment method Keys', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'type'        => 'multiselect',
					'description' => __( 'The payment methods to be made available on this gateway. Use CTRL/CMD to select several.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ( apply_filters( 'gateway_ifthen_sandbox', false ) ? '<br><span style="color: red;">Sandbox</span>' : '' ),
					'options'     => array(),
				),*/
			);
			if ( $gateways = get_option( $this->id . '_gateways' ) ) {
				if ( ! empty( $gateways ) ) {
					if ( is_array( $gateways ) && count( $gateways ) > 0 ) {
						$this->form_fields['gatewaykey'] = array(
							'title'       => __( 'Gateway Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'select',
							'description' => __( 'Gateway Key provided by IfthenPay when signing the contract.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ( apply_filters( 'gateway_ifthen_sandbox', false ) ? '<br><span style="color: red;">Sandbox</span>' : '' ),
							'default'     => '',
							'options'     => array(
								'' => '- ' . __( 'Select', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' -',
							),
						);
						foreach( $gateways as $gateway ) {
							if ( $gateway->Tipo === 'Estáticas' || apply_filters( 'gateway_ifthen_allow_dynamic_gateways', false ) ) {
								$this->form_fields['gatewaykey']['options'][ $gateway->GatewayKey ] = $gateway->Alias;
							}
						}
						$available_methods = array();
						if ( count( $this->form_fields['gatewaykey']['options'] ) > 1 ) {
							$available_methods = $this->get_available_gateway_methods();
							if ( count( $available_methods ) > 0 ) {
								foreach( $available_methods as $method => $accounts ) {
									$this->form_fields[ 'method_' . $method ] = array(
										'title'       => sprintf(
															__( '%s Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
															$method
										),
										'type'        => 'select',
										'description' => sprintf(
															__( '%s Key, that you want to use for this gateway, provided by IfthenPay when signing the contract.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
															$method
										) . '<br/>' . __( 'The callback will automatically be set for this key, on this website, so make sure you are not using it anywhere else.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
										'default'     => '',
										'options'     => array(
											'' => '- ' . __( 'Select or leave blank to not use this method', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' -',
										),
									);
									foreach( $accounts as $key => $alias ) {
										$this->form_fields[ 'method_' . $method ]['options'][ $key ] = $alias;
									}
								}
							}
						}
						if ( count( $available_methods ) === 0 ) {
							$this->form_fields[ 'no_methods' ] = array(
								'title'       => __( 'No methods available', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								'description' => __( 'There are no payment methods available on this Gateway. Please choose another one or request IfthenPay to create a static gateway on your account, specifically for WooCommerce, with the payment methods you want to use (Apple Pay, GooglePay, or Pix)', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								'type'        => 'hidden',
								'value'       => '1',
							);
						}
					}
				}
			}
			// if ( strlen( trim( $this->get_option( 'gatewaykey' ) ) ) == 11 && trim( $this->secret_key ) != '' ) {
				$this->form_fields = array_merge(
					$this->form_fields,
					array(
						'secret_key'         => array(
							'title'       => __( 'Anti-phishing key', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' (Gateway IfthenPay)',
							'type'        => 'hidden',
							'description' => '<strong id="woocommerce_' . $this->id . '_secret_key_label">' . $this->secret_key . '</strong><br/>' . __( 'To ensure callback security, generated by the system and which must be provided to IfthenPay when asking for the callback activation.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'default'     => $this->secret_key,
						),
						'title'         => array(
							'title'       => __( 'Title', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'text',
							'description' => __( 'This controls the title which the user sees during checkout.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
											. ( WC_IfthenPay_Webdados()->wpml_active ? '<br/>' . WC_IfthenPay_Webdados()->wpml_translation_info : '' ),
							'default'     => 'Apple Pay, Google Pay, or Pix',
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
								__( 'Apple Pay, Google Pay, or Pix', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								wc_price( WC_IfthenPay_Webdados()->gateway_ifthen_min_value, array( 'currency' => 'EUR' ) ),
								wc_price( WC_IfthenPay_Webdados()->gateway_ifthen_max_value, array( 'currency' => 'EUR' ) )
							),
							'default'     => '',
						),
						'only_bellow'   => array(
							'title'       => __( 'Only for orders up to', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'number',
							'description' => __( 'Enable only for orders with a value up to x &euro;. Leave blank (or zero) to allow for any order value.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' <br/> ' . sprintf(
								__( 'By design, %1$s only allows payments from %2$s to %3$s. You can use this option to further limit this range.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								__( 'Apple Pay, Google Pay, or Pix', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								wc_price( WC_IfthenPay_Webdados()->gateway_ifthen_min_value, array( 'currency' => 'EUR' ) ),
								wc_price( WC_IfthenPay_Webdados()->gateway_ifthen_max_value, array( 'currency' => 'EUR' ) )
							),
							'default'     => '',
						),
					)
				);
				// Not implemented yet
				/*
				if ( WC_IfthenPay_Webdados()->wc_subscriptions_active ) {
				}*/
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
			$this->form_fields = array_merge( $this->form_fields, apply_filters( 'multibanco_ifthen_gateway_ifthen_settings_fields', array() ) );
			// And to manipulate them
			$this->form_fields = apply_filters( 'multibanco_ifthen_gateway_ifthen_settings_fields_all', $this->form_fields );

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
						<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->gateway_ifthen_banner ); ?>" alt="<?php echo esc_attr( $title ); ?>" width="186" height="48"/>
						<br/>
						<?php echo $title; ?>
						<small>v.<?php echo $this->version; ?></small>
						<?php
						if ( function_exists( 'wc_back_link' ) ) {
							echo wc_back_link( __( 'Return to payments', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );}
						?>
					</h2>
					<?php echo wp_kses_post( wpautop( $this->get_method_description() ) ); ?>
					<p><strong><?php _e( 'In order to use this plugin you need to:', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong></p>
					<ul class="wc_ifthen_list">
						<li><?php printf( __( 'Set WooCommerce currency to <strong>Euros (&euro;)</strong> %1$s', 'multibanco-ifthen-software-gateway-for-woocommerce' ), '<a href="admin.php?page=wc-settings&amp;tab=general">&gt;&gt;</a>.' ); ?></li>
						<li><?php printf( __( 'Sign a contract with %1$s. To learn more about this service, please go to %2$s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ), '<strong><a href="https://ifthenpay.com/' . esc_attr( WC_IfthenPay_Webdados()->out_link_utm ) . '" target="_blank">IfthenPay</a></strong>', '<a href="https://ifthenpay.com/' . esc_attr( WC_IfthenPay_Webdados()->out_link_utm ) . '" target="_blank">https://ifthenpay.com</a>' ); ?></li>
						<li><?php _e( 'Enter your backoffice key and select a gateway.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></li>
						<li><?php _e( 'If no gateways are available, or the available gateways do not have payment methods available, you need to request IfthenPay to create a static gateway on your account, specifically for WooCommerce, with the payment methods you want to use (Apple Pay, GooglePay, or Pix).', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></li>
						<li><?php _e( 'Select the payment methods you want to make available.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></li>
						<li><?php _e( 'The callback for each of the chosen payment methods will automatically be activated once you save the options.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></li>
						<li>
						<?php
						printf(
							__( 'Do not use the same %1$s on multiple websites or any other system, online or offline. Ask %2$s for new ones for every single platform.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							__( 'payment method keys', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'<a href="https://ifthenpay.com/' . esc_attr( WC_IfthenPay_Webdados()->out_link_utm ) . '" target="_blank">IfthenPay</a>'
						);
						?>
						</li>
					</ul>
					<?php
					if (
						strlen( trim( $this->gatewaykey ) ) === 11
					) {
						// OK
					} else {
						if ( $this->settings_saved == 1 ) {
							?>
							<div id="message" class="error">
								<p><strong><?php _e( 'Invalid Gateway Key (exactly 11 characters).', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong></p>
							</div>
							<?php
						} else {
							?>
							<div id="message" class="error">
								<p><strong><?php _e( 'Set the Gateway Key and Save changes to set other plugin options.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong></p>
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
		 * Process gateways and methods after saving the settings
		 */
		public function process_admin_options_update_gateways_and_methods() {
			if ( isset( $_POST[ 'woocommerce_' . $this->id . '_backoffice_key' ] ) && strlen( trim( $_POST[ 'woocommerce_' . $this->id . '_backoffice_key' ] ) ) === 19 ) {
				if ( trim( $_POST[ 'woocommerce_' . $this->id . '_backoffice_key' ] ) !== $this->backoffice_key ) {
					// Update gateways
					$response = wp_remote_get( $this->gateways_api_url . '?backofficekey=' . trim( $_POST[ 'woocommerce_' . $this->id . '_backoffice_key' ] ) );
					if ( ! is_wp_error( $response ) ) {
						if ( isset( $response['response']['code'] ) && intval( $response['response']['code'] ) == 200 && isset( $response['body'] ) && trim( $response['body'] ) != '' ) {
							if ( $body = json_decode( trim( $response['body'], true ) ) ) {
								update_option( $this->id . '_gateways', $body );
								if ( wp_safe_redirect( 'admin.php?page=wc-settings&tab=checkout&section=' . $this->id ) ) {
									exit();
								}
							} else {
								// Error handling missing
								delete_option( $this->id . '_gateways' );
								delete_option( $this->id . '_gateway_methods' );
							}
						} else {
							// Error handling missing
							delete_option( $this->id . '_gateways' );
							delete_option( $this->id . '_gateway_methods' );
						}
					} else {
						// Error handling missing
						delete_option( $this->id . '_gateways' );
						delete_option( $this->id . '_gateway_methods' );
					}
				} elseif ( isset( $_POST[ 'woocommerce_' . $this->id . '_gatewaykey' ] ) && strlen( trim( $_POST[ 'woocommerce_' . $this->id . '_gatewaykey' ] ) ) === 11 ) {
					if ( trim( $_POST[ 'woocommerce_' . $this->id . '_gatewaykey' ] ) !== $this->gatewaykey ) {
						// Update gateway methods
						$response = wp_remote_get( $this->gateways_methods_api_url . '?backofficekey=' . trim( $_POST[ 'woocommerce_' . $this->id . '_backoffice_key' ] ) . '&gatewayKey=' . trim( $_POST[ 'woocommerce_' . $this->id . '_gatewaykey' ] ) );
						if ( ! is_wp_error( $response ) ) {
							if ( isset( $response['response']['code'] ) && intval( $response['response']['code'] ) == 200 && isset( $response['body'] ) && trim( $response['body'] ) != '' ) {
								if ( $body = json_decode( trim( $response['body'], true ) ) ) {
									update_option( $this->id . '_gateway_methods', $body );
									if ( wp_safe_redirect( 'admin.php?page=wc-settings&tab=checkout&section=' . $this->id ) ) {
										exit();
									}
								} else {
									// Error handling missing
									delete_option( $this->id . '_gateway_methods' );
								}
							} else {
								// Error handling missing
								delete_option( $this->id . '_gateway_methods' );
							}
						} else {
							// Error handling missing
							delete_option( $this->id . '_gateway_methods' );
						}
					} else {
						// Set gateway callbacks
						$available_methods = $this->get_available_gateway_methods();
						foreach( $available_methods as $key => $account ) {
							if ( isset( $_POST[ 'woocommerce_' . $this->id . '_method_' . $key ] ) && trim( $_POST[ 'woocommerce_' . $this->id . '_method_' . $key ] ) !== '' ) {
								// Update callback for account
								var_dump( $key, $_POST[ 'woocommerce_' . $this->id . '_method_' . $key ] );
								/*
								// Webservice
								$result = WC_IfthenPay_Webdados()->callback_webservice( trim( $_POST['wc_ifthen_callback_bo_key'] ), 'MBWAY', $this->mbwaykey, $this->secret_key, WC_IfthenPay_Webdados()->mbway_notify_url );
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
								*/
							}
						}
						wp_die();
					}
				} elseif ( isset( $_POST[ 'woocommerce_' . $this->id . '_gatewaykey' ] ) && strlen( trim( $_POST[ 'woocommerce_' . $this->id . '_gatewaykey' ] ) ) === 0 ) {
					delete_option( $this->id . '_gateway_methods' );
				}
			} elseif ( isset( $_POST[ 'woocommerce_' . $this->id . '_backoffice_key' ] ) && strlen( trim( $_POST[ 'woocommerce_' . $this->id . '_backoffice_key' ] ) ) === 0 ) {
				delete_option( $this->id . '_gateways' );
				delete_option( $this->id . '_gateway_methods' );
			}
		}

		/**
		 * Icon HTML
		 */
		public function get_icon() {
			$alt       = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_title', $this->title ) : $this->title );
			$icon_html = '<img src="' . esc_attr( WC_IfthenPay_Webdados()->gateway_ifthen_icon ) . '" alt="' . esc_attr( $alt ) . '" width="28" height="24"/>';
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
					// Maybe not on the Gateway...

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
			$send = apply_filters( 'gateway_ifthen_send_email_instructions', $send, $order, $sent_to_admin, $plain_text, $email );
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
								if ( apply_filters( 'gateway_ifthen_email_instructions_payment_received_send', true, $order->get_id() ) ) {
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
			return apply_filters( 'gateway_ifthen_email_instructions_table_html', ob_get_clean(), round( $order_total, 2 ), $order_id );
		}*/
		function email_instructions_payment_received( $order_id ) {
			$alt = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_title', $this->title ) : $this->title );
			ob_start();
			?>
			<p style="text-align: center; margin: auto; margin-top: 2em; margin-bottom: 2em;">
				<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->gateway_ifthen_banner_email ); ?>" alt="<?php echo esc_attr( $alt ); ?>" title="<?php echo esc_attr( $alt ); ?>" style="margin: auto; margin-top: 10px; max-height: 48px;"/>
				<br/>
				<strong><?php _e( 'IfthenPay Gateway payment received.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong>
				<br/>
				<?php _e( 'We will now process your order.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
			</p>
			<?php
			return apply_filters( 'gateway_ifthen_email_instructions_payment_received', ob_get_clean(), $order_id );
		}

		/**
		 * API Init Payment
		 * https://ifthenpay.com/docs/en/api/pbl/#tag/pay-by-link--pinpay/POST/{GATEWAY_KEY}
		 */
		function api_init_payment( $order_id ) {
			$id         = $order_id; // We could randomize this...
			$order      = wc_get_order( $order_id );
			$valor      = round( floatval( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ) ), 2 );
			$gatewaykey = apply_filters( 'multibanco_ifthen_base_gatewaykey', $this->gatewaykey, $order );
			$desc       = trim( get_bloginfo( 'name' ) ) . ' #' . $order->get_order_number();
			$accounts   = array();
			$lang       = strtolower( substr( trim( get_locale() ), 0, 2 ) );
			if (
				! in_array(
					$lang,
					array(
						'pt',
						'en',
						'es',
						'fr',
					)
				)
			) {
				$lang = 'en';
			}
			$url  = $this->api_url . $gatewaykey;
			$args = array(
				'method'   => 'POST',
				'timeout'  => apply_filters( 'gateway_ifthen_api_timeout', 15 ),
				'blocking' => true,
				'body'     => array(
					'id'            => (string) apply_filters( 'ifthen_webservice_send_order_number_instead_id', false ) ? $order->get_order_number() : $order->get_id(),
					'amount'        => (string) $valor,
					'description'   => $desc,
					'accounts'      => implode( ';', $accounts ),
					'expiredate'    => '',
					'success_url'   => $this->get_return_url( $order ),
					'error_url'     => wc_get_checkout_url(), // We should add an error notice
					'cancel_url'    => wc_get_checkout_url(),
					'btnCloseUrl'   => wc_get_checkout_url(),
					'btnCloseLabel' => __( 'Close', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'lang'          => $lang,
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
									'gatewaykey'  => $gatewaykey,
									'pincode'     => $body->PinCode,
									'id'          => apply_filters( 'ifthen_webservice_send_order_number_instead_id', false ) ? $order->get_order_number() : $order->get_id(),
									'val'         => $valor,
									'payment_url' => $body->RedirectUrl,
								)
							);
							$this->debug_log( '- IfthenPay Gateway payment request created on IfthenPay servers - Redirecting to payment gateway - Order ' . $order->get_id() . ' - RequestId: ' . $body->RequestId );
							do_action( 'gateway_ifthen_created_reference', $body->RequestId, $order->get_id() );
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
			do_action( 'gateway_ifthen_before_process_payment', $order );
			if ( $order->get_total() > 0 ) {
				if ( $redirect_url = $this->api_init_payment( $order->get_id() ) ) {
					// WooCommerce Deposits - When generating second payment reference the order goes from partially paid to on hold, and that has an email (??!)
					if ( WC_IfthenPay_Webdados()->wc_deposits_active && $order->get_status() == 'partially-paid' ) {
						add_filter( 'woocommerce_email_enabled_customer_processing_order', '__return_false' );
						add_filter( 'woocommerce_email_enabled_full_payment', '__return_false' );
					}
					// Mark pending
					WC_IfthenPay_Webdados()->set_initial_order_status( $order, 'pending', __( 'IfthenPay Gateway', 'multibanco-ifthen-software-gateway-for-woocommerce' ) );
				} else {
					throw new Exception(
						sprintf(
							/* translators: %s: payment method */
							__( 'An error occurred processing the %s Payment request - please try again', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							__( 'IfthenPay Gateway', 'multibanco-ifthen-software-gateway-for-woocommerce' )
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
				strlen( trim( $this->gatewaykey ) ) != 11
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
			return WC_IfthenPay_Webdados()->disable_only_above_or_below( $available_gateways, $this->id, WC_IfthenPay_Webdados()->gateway_ifthen_min_value, WC_IfthenPay_Webdados()->gateway_ifthen_max_value );
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
							$order_details = WC_IfthenPay_Webdados()->get_gateway_ifthen_order_details( $order->get_id() );
							$sk            = isset( $_GET['sk'] ) ? trim( sanitize_text_field( $_GET['sk'] ) ) : '';
							$hash          = hash_hmac( 'sha256', $id . $val . $request_id, $order_details['gatewaykey'] );
							if ( $sk == $hash ) {
								$this->debug_log_extra( 'Order found: ' . $order->get_id() . ' - Hash ok' );
								$note = __( 'IfthenPay Gateway Pay payment received.', 'multibanco-ifthen-software-gateway-for-woocommerce' );
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
								do_action( 'gateway_ifthen_callback_payment_complete', $order->get_id(), $_GET );
								$debug_order = wc_get_order( $order->get_id() );
								$this->debug_log( '-- IfthenPay Gateway payment received - Order ' . $order->get_id(), 'notice' );
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
							if ( apply_filters( 'gateway_ifthen_cancel_order_on_back', false, $get_order['order'] ) ) {
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
				do_action( 'gateway_ifthen_callback_payment_failed', $order_id, $error, $_GET );
				if ( $redirect_url ) {
					wp_redirect( $redirect_url );
				} else {
					// ???
				}
				exit;
			}

		}

		function callback_helper_get_pending_order( $request_id, $id, $val, $wd_secret = null ) {
			$return         = array(
				'success' => false,
				'error'   => false,
				'order'   => false,
			);
			$pending_status = apply_filters( 'gateway_ifthen_valid_callback_pending_status', WC_IfthenPay_Webdados()->unpaid_statuses ); // Double filter - Should we deprectate this one?
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

		/* Localize javascript */
		public function frontend_classic_checkout_availability_check() {
			if ( is_checkout() && $this->enabled === 'yes' && true ) { // Check if method settings are set
				//wp_enqueue_script( 'google-pay', 'https://pay.google.com/gp/p/js/pay.js', array(), $this->version . ( WP_DEBUG ? '.' . wp_rand( 0, 99999 ) : '' ), true );
				wp_enqueue_script(
					'gateway-ifthenpay',
					plugins_url( 'assets/gateway.js', __FILE__ ),
					array(
						'jquery',
						//'google-pay',
					),
					$this->version . ( WP_DEBUG ? '.' . wp_rand( 0, 99999 ) : '' ),
					true
				);
				wp_localize_script(
					'gateway-ifthenpay',
					'gateway_ifthenpay',
					array(
						'general' => array(
							'method_title'       => $this->title,
						),
						'apple' => array(
							'enabled'            => true, // There's a Apple Pay entity set
							'method_title'       => 'Apple Pay', // From settings
							//'method_description' => 'Apple Pay description', // From settings
							'icon'               => '', // From settings
						),
						'google' => array(
							'enabled'            => true, // There's a Google Pay entity set
							'method_title'       => 'Google Pay', // From settings
							//'method_description' => 'Google Pay description', // From settings
							'icon'               => '', // From settings
						),
					)
				);
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
					strlen( trim( $this->gatewaykey ) ) != 11
					||
					trim( $this->enabled ) != 'yes'
				)
				&&
				( ! apply_filters( 'multibanco_ifthen_hide_newmethod_notifications', false ) )
			) {
				?>
				<div id="gateway_ifthen_newmethod_notice" class="notice notice-info is-dismissible" style="padding-right: 38px; position: relative; display: none;">
					<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->gateway_ifthen_banner ); ?>" style="float: left; margin-top: 0.5em; margin-bottom: 0.5em; margin-right: 1em; max-height: 48px; max-width: 186px;"/>
					<p>
						<?php
							echo sprintf(
								__( 'There’s a new payment method available: %s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								'<strong>Apple Pay, Google Pay, or Pix (IfthenPay)</strong>'
							);
						?>
						<br/>
						<?php
						echo sprintf(
							__( 'Ask IfthenPay to activate it on your account and then %1$sconfigure it here%2$s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'<strong><a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=gateway_ifthen_for_woocommerce">',
							'</a></strong>'
						);
						?>
					</p>
				</div>
				<script type="text/javascript">
				(function () {
					notice    = jQuery( '#gateway_ifthen_ifthen_newmethod_notice');
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
