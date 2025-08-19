<?php
/**
 * The ifthenpay Gateway class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_IfThen_Webdados' ) ) {

	/**
	 * Gateway IfThen Class.
	 */
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
		public $methods_keys;
		public $settings_saved;
		public $send_to_admin;
		public $only_portugal;
		public $only_above;
		public $only_below;
		public $stock_when;
		public $do_refunds;

		/**
		 * Constructor for your payment class
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {

			++self::$instances;

			$this->id = WC_IfthenPay_Webdados()->gateway_ifthen_id;

			// Logs
			$this->debug       = ( $this->get_option( 'debug' ) === 'yes' ? true : false );
			$this->debug_email = $this->get_option( 'debug_email' );

			// Check version and upgrade
			$this->version = WC_IfthenPay_Webdados()->get_version();
			$this->upgrade();

			$this->has_fields = false;

			$this->method_title       = __( 'ifthenpay Gateway', 'multibanco-ifthen-software-gateway-for-woocommerce' );
			$this->method_description = __( 'Easy and simple payment using Apple Pay, Google Pay, or PIX. (Via the ifthenpay Gateway)', 'multibanco-ifthen-software-gateway-for-woocommerce' );

			// Anti-phishing key
			$this->secret_key = $this->get_option( 'secret_key' );
			if ( trim( $this->secret_key ) === '' ) {
				// First load?
				$this->secret_key = md5( home_url() . time() . wp_rand( 0, 999 ) );
				// Save
				$this->update_option( 'secret_key', $this->secret_key );
				$this->update_option( 'debug', 'yes' );
			}

			// Webservice
			$this->api_url_production       = 'https://api.ifthenpay.com/gateway/pinpay/'; // production mode
			$this->api_url_sandbox          = ''; // test mode?
			$this->api_url                  = '';
			$this->gateways_api_url         = 'https://www.ifthenpay.com/IfmbWS/ifthenpaymobile.asmx/GetGatewayKeys';
			$this->gateways_methods_api_url = 'https://www.ifthenpay.com/IfmbWS/ifthenpaymobile.asmx/GetAccountsByGatewayKey';

			// Plugin options and settings
			$this->init_form_fields();
			$this->init_settings();

			// User settings
			$this->title          = trim( $this->get_option( 'title' ) );
			$this->description    = trim( $this->get_option( 'description' ) );
			$this->backoffice_key = trim( $this->get_option( 'backoffice_key' ) );
			$this->gatewaykey     = trim( $this->get_option( 'gatewaykey' ) );
			$this->settings_saved = $this->get_option( 'settings_saved' );
			$this->send_to_admin  = ( $this->get_option( 'send_to_admin' ) === 'yes' ? true : false );
			$this->only_portugal  = ( $this->get_option( 'only_portugal' ) === 'yes' ? true : false );
			$this->only_above     = $this->get_option( 'only_above' );
			$this->only_below     = $this->get_option( 'only_bellow' );
			$this->do_refunds     = ( $this->get_option( 'do_refunds' ) === 'yes' ? true : false );
			if ( $this->do_refunds && trim( $this->backoffice_key ) !== '' ) {
				$this->supports[] = 'refunds';
			}
			$this->methods_keys = array();
			foreach ( $this->get_available_gateway_methods() as $method => $accounts ) {
				if ( $this->get_option( 'method_' . $method ) !== '' ) {
					$this->methods_keys[ $method ] = $this->get_option( 'method_' . $method );
				}
			}

			// Actions and filters
			if ( self::$instances === 1 ) { // Avoid duplicate actions and filters if it's initiated more than once (if WooCommerce loads after us)

				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options_update_gateways_and_methods' ), 20 ); // After saved and after pro
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
				add_action( 'woocommerce_api_wc_gatewayreturn_ifthen_webdados', array( $this, 'return_payment_gateway' ) );

				// Payment listener - Callback
				add_action( 'woocommerce_api_wc_gateway_ifthen_webdados', array( $this, 'callback' ) );

				// Admin notices
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );

				// API URL
				$this->api_url = apply_filters( 'gateway_ifthen_sandbox', false ) ? $this->api_url_sandbox : $this->api_url_production;

				// Method title in frontend
				if ( apply_filters( 'gateway_ifthen_add_frontend_title', true ) ) {
					$this->title .= ' - ' . __( 'ifthenpay Gateway', 'multibanco-ifthen-software-gateway-for-woocommerce' );
				}

				// Method title in sandbox mode
				if ( apply_filters( 'gateway_ifthen_sandbox', false ) ) {
					$this->title .= ' - SANDBOX (TEST MODE)';
				}

				// Frontend availability checker for Apple and Google Pay - Maybe later
				// add_action( 'wp_enqueue_scripts', array( $this, 'frontend_classic_checkout_availability_check' ) );
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
			$to_register = array();
			foreach ( $to_register as $string ) {
				icl_register_string( $this->id, $this->id . '_' . $string, $this->settings[ $string ] );
			}
		}

		/**
		 * Get available gateway methods
		 */
		private function get_available_gateway_methods() {
			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$available_methods = array();
			$gateway_methods   = get_option( $this->id . '_gateway_methods' );
			if ( ! empty( $gateway_methods ) && is_array( $gateway_methods ) ) {
				if ( count( $gateway_methods ) > 0 ) {
					if ( is_array( $gateway_methods ) && count( $gateway_methods ) > 0 ) {
						foreach ( $gateway_methods as $gateway_method ) {
							// Multibanco legacy is not available
							if ( ! is_numeric( $gateway_method->Entidade ) ) {
								if ( ! in_array(
									trim( $gateway_method->Entidade ),
									apply_filters( 'gateway_ifthen_unavailable_methods', array( 'MB', 'MBWAY', 'PAYSHOP', 'CCARD', 'COFIDIS' ) ),
									true
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
			// phpcs:enable
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
			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			$this->form_fields = array(
				'enabled'        => array(
					'title'   => __( 'Enable/Disable', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => sprintf(
						/* translators: %s: Gateway name */
						__( 'Enable “%s”', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						__( 'ifthenpay Gateway', 'multibanco-ifthen-software-gateway-for-woocommerce' )
					),
					'default' => 'no',
				),
				'backoffice_key' => array(
					'title'             => __( 'Backoffice key', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'type'              => 'text',
					'default'           => '',
					'description'       => __( 'The ifthenpay backoffice key you got after signing the contract.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ( apply_filters( 'gateway_ifthen_sandbox', false ) ? '<br><span style="color: red;">Sandbox</span>' : '' ),
					'placeholder'       => '0000-0000-0000-0000',
					'custom_attributes' => array(
						'maxlength' => 19,
						'size'      => 22,
					),
				),
				'gatewaykey'     => array(
					'title'             => __( 'Gateway Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'type'              => 'text',
					'description'       => sprintf(
						/* translators: %s: Gateway key name */
						__( '%s provided by ifthenpay when signing the contract.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						__( 'Gateway Key', 'multibanco-ifthen-software-gateway-for-woocommerce' )
					) . ( apply_filters( 'gateway_ifthen_sandbox', false ) ? '<br><span style="color: red;">Sandbox</span>' : '' ),
					'default'           => '',
					'css'               => 'width: 130px;',
					'placeholder'       => 'XXXX-000000',
					'custom_attributes' => array(
						'maxlength' => 11,
						'size'      => 14,
					),
				),
			);
			$gateways          = get_option( $this->id . '_gateways' );
			if ( ! empty( $gateways ) ) {
				if ( is_array( $gateways ) && count( $gateways ) > 0 ) {
					$this->form_fields['gatewaykey'] = array(
						'title'       => __( 'Gateway Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'type'        => 'select',
						'description' => __( 'Gateway Key provided by ifthenpay when signing the contract.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ( apply_filters( 'gateway_ifthen_sandbox', false ) ? '<br><span style="color: red;">Sandbox</span>' : '' ),
						'default'     => '',
						'options'     => array(
							'' => '- ' . __( 'Select', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' -',
						),
					);
					$count_gateways                  = 0;
					foreach ( $gateways as $gateway ) {
						if ( $gateway->Tipo === 'Estáticas' || apply_filters( 'gateway_ifthen_allow_dynamic_gateways', false ) ) {
							$this->form_fields['gatewaykey']['options'][ $gateway->GatewayKey ] = $gateway->Alias . ( $gateway->Tipo !== 'Estáticas' ? ' (' . trim( $gateway->Tipo ) . ')' : '' );
							++$count_gateways;
						}
					}
					if ( $count_gateways === 0 ) {
						$this->form_fields['gatewaykey']['description'] .= '<br/>' . __( 'If no gateways are available, or the available gateways do not have payment methods available, you need to request ifthenpay to create a static gateway on your account, specifically for WooCommerce, with the payment methods you want to use (Apple Pay, Google Pay, or PIX).', 'multibanco-ifthen-software-gateway-for-woocommerce' );
					}
					$available_methods = array();
					if ( count( $this->form_fields['gatewaykey']['options'] ) > 1 ) {
						$available_methods = $this->get_available_gateway_methods();
						if ( count( $available_methods ) > 0 ) {
							foreach ( $available_methods as $method => $accounts ) {
								$this->form_fields[ 'method_' . $method ] = array(
									'title'       => sprintf(
										/* translators: %s: payment method */
										__( '%s Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
										WC_IfthenPay_Webdados()->helper_format_method( $method )
									),
									'type'        => 'select',
									'description' => sprintf(
										/* translators: %s: payment method */
										__( '%s Key, that you want to use for this gateway, provided by ifthenpay when signing the contract.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
										WC_IfthenPay_Webdados()->helper_format_method( $method )
									) . '<br/>' . __( 'The callback will automatically be set for this key, on this website, so make sure you are not using it anywhere else.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									'default'     => '',
									'options'     => array(
										'' => '- ' . __( 'Select or leave blank to not use this method', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' -',
									),
								);
								foreach ( $accounts as $key => $alias ) {
									$this->form_fields[ 'method_' . $method ]['options'][ $key ] = $alias;
								}
							}
						}
					}
					if ( count( $available_methods ) === 0 ) {
						$this->form_fields['no_methods'] = array(
							'title'       => __( 'No methods available', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'description' => __( 'There are no payment methods available on this Gateway. Please choose another one or request ifthenpay to create a static gateway on your account, specifically for WooCommerce, with the payment methods you want to use (Apple Pay, Google Pay, or PIX).', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'type'        => 'hidden',
							'value'       => '1',
						);
					}
				}
			}

			$this->form_fields = array_merge(
				$this->form_fields,
				array(
					'secret_key'    => array(
						'title'       => __( 'Anti-phishing key', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' (' . __( 'ifthenpay Gateway', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ')',
						'type'        => 'hidden',
						'description' => '<strong id="woocommerce_' . $this->id . '_secret_key_label">' . $this->secret_key . '</strong><br/>' . __( 'To ensure callback security, generated by the system and which must be provided to ifthenpay when asking for the callback activation.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'default'     => $this->secret_key,
					),
					'title'         => array(
						'title'       => __( 'Title', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'type'        => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
										. ( WC_IfthenPay_Webdados()->wpml_active ? '<br/>' . WC_IfthenPay_Webdados()->wpml_translation_info : '' ),
						'default'     => 'Apple Pay, Google Pay, or PIX',
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
							/* translators: %1$s: payment method, %2$s: minimum value, %3$s: maximum value */
							__( 'By design, %1$s only allows payments from %2$s to %3$s. You can use this option to further limit this range.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							__( 'Apple Pay, Google Pay, or PIX', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							wc_price( WC_IfthenPay_Webdados()->gateway_ifthen_min_value, array( 'currency' => 'EUR' ) ),
							wc_price( WC_IfthenPay_Webdados()->gateway_ifthen_max_value, array( 'currency' => 'EUR' ) )
						),
						'default'     => '',
					),
					'only_bellow'   => array(
						'title'       => __( 'Only for orders up to', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'type'        => 'number',
						'description' => __( 'Enable only for orders with a value up to x &euro;. Leave blank (or zero) to allow for any order value.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' <br/> ' . sprintf(
							/* translators: %1$s: payment method, %2$s: minimum value, %3$s: maximum value */
							__( 'By design, %1$s only allows payments from %2$s to %3$s. You can use this option to further limit this range.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							__( 'Apple Pay, Google Pay, or PIX', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							wc_price( WC_IfthenPay_Webdados()->gateway_ifthen_min_value, array( 'currency' => 'EUR' ) ),
							wc_price( WC_IfthenPay_Webdados()->gateway_ifthen_max_value, array( 'currency' => 'EUR' ) )
						),
						'default'     => '',
					),
					// phpcs:disable
					// Not implemented yet
					// 'validity'      => array(
					// 	'title'       => __( 'Validity in days', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					// ),
					// phpcs:enable
				)
			);
			// Not implemented yet
			// phpcs:disable
			// if ( WC_IfthenPay_Webdados()->wc_subscriptions_active ) {
			// }
			// phpcs:enable
			$this->form_fields = array_merge(
				$this->form_fields,
				array(
					'do_refunds' => array(
						'title' => __( 'Process refunds?', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'type'  => 'checkbox',
						'label' => __( 'Allow to refund via Apple Pay, Google Pay, or PIX when the order is completely or partially refunded in WooCommerce', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
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
							/* translators: %s: file name or link to logs */
							__( 'Log payment method events in %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
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
			// phpcs:enable
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
								/* translators: %s: link to WooCommerce settings */
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
						<li><?php esc_html_e( 'Enter your backoffice key and select a gateway.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'If no gateways are available, or the available gateways do not have payment methods available, you need to request ifthenpay to create a static gateway on your account, specifically for WooCommerce, with the payment methods you want to use (Apple Pay, Google Pay, or PIX).', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Select the payment methods you want to make available.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'The callback for each of the chosen payment methods will automatically be activated once you save the options.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></li>
						<li>
							<?php
							echo wp_kses_post(
								sprintf(
									/* translators: %1$s: payment method keys, %2$s: link to ifthenpay */
									esc_html__( 'Do not use the same %1$s on multiple websites or any other system, online or offline. Ask %2$s for new ones for every single platform.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									esc_html__( 'payment method keys', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									'<a href="https://ifthenpay.com/' . esc_attr( WC_IfthenPay_Webdados()->out_link_utm ) . '" target="_blank">ifthenpay</a>'
								)
							);
							?>
						</li>
					</ul>
					<?php
					if ( strlen( trim( $this->gatewaykey ) ) !== 11 ) {
						if ( intval( $this->settings_saved ) === 1 ) {
							?>
							<div id="message" class="error">
								<p><strong><?php esc_html_e( 'Invalid Gateway Key (exactly 11 characters).', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong></p>
							</div>
							<?php
						} else {
							?>
							<div id="message" class="error">
								<p>
									<strong>
										<?php
											echo esc_html(
												sprintf(
													/* translators: %s: Gateway key name */
													__( 'Set the %s and Save changes to set other payment method options.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
													__( 'Gateway Key', 'multibanco-ifthen-software-gateway-for-woocommerce' )
												)
											);
										?>
									</strong>
								</p>
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
		 * Process gateways and methods after saving the settings
		 */
		public function process_admin_options_update_gateways_and_methods() {
			// WooCommerce already took care of Nonces
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST[ 'woocommerce_' . $this->id . '_backoffice_key' ] ) ) {
				$backoffice_key = trim( sanitize_text_field( wp_unslash( $_POST[ 'woocommerce_' . $this->id . '_backoffice_key' ] ) ) );
				if ( strlen( $backoffice_key ) === 19 ) {
					if ( $backoffice_key !== $this->backoffice_key ) {
						// Update gateways
						$url      = $this->gateways_api_url . '?backofficekey=' . $backoffice_key;
						$response = wp_remote_get( $url );
						if ( ! is_wp_error( $response ) ) {
							if ( isset( $response['response']['code'] ) && intval( $response['response']['code'] ) === 200 && isset( $response['body'] ) && trim( $response['body'] ) !== '' ) {
								$body = json_decode( trim( $response['body'], true ) );
								if ( ! empty( $body ) ) {
									update_option( $this->id . '_gateways', $body );
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
					} elseif ( isset( $_POST[ 'woocommerce_' . $this->id . '_gatewaykey' ] ) ) {
						$gatewaykey = trim( sanitize_text_field( wp_unslash( $_POST[ 'woocommerce_' . $this->id . '_gatewaykey' ] ) ) );
						if ( strlen( $gatewaykey ) === 11 ) {
							if ( trim( $gatewaykey ) !== $this->gatewaykey ) {
								// Update gateway methods
								$url      = $this->gateways_methods_api_url . '?backofficekey=' . $backoffice_key . '&gatewayKey=' . $gatewaykey;
								$response = wp_remote_get( $url );
								if ( ! is_wp_error( $response ) ) {
									if ( isset( $response['response']['code'] ) && intval( $response['response']['code'] ) === 200 && isset( $response['body'] ) && trim( $response['body'] ) !== '' ) {
										$body = json_decode( trim( $response['body'], true ) );
										if ( ! empty( $body ) ) {
											update_option( $this->id . '_gateway_methods', $body );
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
								// Delete chosen methods
								foreach ( $this->settings as $key => $value ) {
									if ( substr( $key, 0, 7 ) === 'method_' ) {
										unset( $this->settings[ $key ] );
									}
								}
								update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' );
							} else {
								// Set gateway callbacks
								$available_methods = $this->get_available_gateway_methods();
								foreach ( $available_methods as $method => $accounts ) {
									if ( isset( $_POST[ 'woocommerce_' . $this->id . '_method_' . $method ] ) ) {
										$method_key = trim( sanitize_text_field( wp_unslash( $_POST[ 'woocommerce_' . $this->id . '_method_' . $method ] ) ) );
										if ( $method_key !== '' ) {
											if (
												// Changed account
												(
													isset( $this->methods_keys[ $method ] )
													&&
													$method_key !== $this->methods_keys[ $method ]
												)
												||
												// Set new account from no account
												(
													! isset( $this->methods_keys[ $method ] )
												)
												||
												// Forced
												(
													apply_filters( 'gateway_ifthen_force_callback_reactivation', false )
												)
											) {
												// Activate callback for this account.
												$method_key_temp = explode( '|', $method_key );
												$result          = WC_IfthenPay_Webdados()->callback_webservice(
													$this->backoffice_key,
													trim( $method_key_temp[0] ),
													trim( $method_key_temp[1] ),
													$this->secret_key,
													WC_IfthenPay_Webdados()->gateway_ifthen_notify_url
												);
												$ok_messages     = array();
												$error_messages  = array();
												if ( $result['success'] ) {
													WC_Admin_Settings::add_message(
														sprintf(
															/* translators: %s: payment account */
															__( 'The “Callback” activation request for account %s has been submited to ifthenpay via API and is now active.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
															$method_key
														)
													);
												} else {
													// phpcs:disable
													// https://github.com/woocommerce/woocommerce/issues/53397
													// WC_Admin_Settings::add_error(
													// phpcs:enabled
													WC_Admin_Settings::add_message(
														'ERROR: '
														.
														sprintf(
															/* translators: %s: payment account */
															__( 'The “Callback” activation request for account %s via API has failed.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
															$method_key
														)
														.
														' - '
														.
														$result['message']
													);
												}
												do_action( 'gateway_ifthen_after_callback_activation', $this );
											}
										}
									}
								}
							}
						} elseif ( strlen( $gatewaykey ) === 0 ) {
							delete_option( $this->id . '_gateway_methods' );
						}
					}
				} elseif ( strlen( $backoffice_key ) === 0 ) {
					delete_option( $this->id . '_gateways' );
					delete_option( $this->id . '_gateway_methods' );
				}
			}
			// phpcs:enable
		}

		/**
		 * Icon HTML
		 */
		public function get_icon() {
			$alt       = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_title', $this->title ) : $this->title );
			$icon_html = '<img src="' . esc_attr( WC_IfthenPay_Webdados()->gateway_ifthen_icon ) . '" alt="' . esc_attr( $alt ) . '" width="24" height="24"/>';
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
					echo $this->thankyou_instructions_table_html( $order->get_id(), round( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ), 2 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					if ( is_wc_endpoint_url( 'order-received' ) ) {
						do_action( 'gateway_ifthen_after_thankyou_instructions_table', $order );
					}

					if ( is_wc_endpoint_url( 'order-received' ) ) {
						if ( apply_filters( 'gateway_ifthen_enable_check_order_status_thankyou', true, $order->get_id() ) ) { // return false to gateway_ifthen_enable_check_order_status_thankyou in order to stop the ajax checking
							// Check order status
							?>
							<input type="hidden" id="gatewayifthenpay-order-id" value="<?php echo intval( $order->get_id() ); ?>"/>
							<input type="hidden" id="gatewayifthenpay-order-key" value="<?php echo esc_attr( $order->get_order_key() ); ?>"/>
							<?php
							wp_enqueue_script( 'gateway-ifthenpay', plugins_url( 'assets/gateway.js', __FILE__ ), array( 'jquery' ), $this->version . ( WP_DEBUG ? '.' . wp_rand( 0, 99999 ) : '' ), true );
							wp_localize_script(
								'gateway-ifthenpay',
								'gateway_ifthenpay',
								array(
									'interval'        => apply_filters( 'gateway_ifthen_check_order_status_thankyou_interval', 5 ),
									'gateway_minutes' => 5,
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
				table.<?php echo esc_html( $this->id ); ?>_table td.barcode_img {
					text-align: center;
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
			$alt                   = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_title', $this->title ) : $this->title );
			$gateway_order_details = WC_IfthenPay_Webdados()->get_gatewayifthenpay_order_details( $order_id );
			$order                 = wc_get_order( $order_id );
			ob_start();
			echo $this->thankyou_instructions_table_html_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			<table class="<?php echo esc_attr( $this->id ); ?>_table" cellpadding="0" cellspacing="0">
				<tr>
					<th colspan="2">
						<?php esc_html_e( 'Payment information', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
						<br/>
						<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->gateway_ifthen_banner ); ?>" alt="<?php echo esc_attr( $alt ); ?>" title="<?php echo esc_attr( $alt ); ?>"/>
					</th>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
					<td class="mb_value"><?php echo wc_price( $gateway_order_details['val'], array( 'currency' => 'EUR' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
				</tr>
				<tr>
					<td colspan="2" class="extra_instructions">
						<?php esc_html_e( 'Waiting for confirmation from ifthenpay', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
					</td>
				</tr>
			</table>
			<?php
			return apply_filters( 'gateway_ifthen_thankyou_instructions_table_html', ob_get_clean(), round( $order_total, 2 ), $order_id );
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
			$send = apply_filters( 'gateway_ifthen_send_email_instructions', true, $order, $sent_to_admin, $plain_text, $email );
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
							} else { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedElse
								// We should not be here because there's no email for pending orders
							}
						} elseif ( $order->has_status( 'processing' ) || $order->has_status( 'completed' ) ) { // Processing
							if ( apply_filters( 'gateway_ifthen_email_instructions_payment_received_send', true, $order->get_id() ) ) {
								echo $this->email_instructions_payment_received( $order->get_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
						}
					}
				}
			}
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
				<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->gateway_ifthen_banner_email ); ?>" alt="<?php echo esc_attr( $alt ); ?>" title="<?php echo esc_attr( $alt ); ?>" style="margin: auto; margin-top: 10px; max-height: 48px;"/>
				<br/>
				<strong>
					<?php
						$order_details = WC_IfthenPay_Webdados()->get_gatewayifthenpay_order_details( $order_id );
						echo esc_html(
							sprintf(
								/* translators: %s: payment method used on the ifthenpay Gateway */
								__( '%s payment received.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								WC_IfthenPay_Webdados()->helper_format_method( $order_details['payment_method'] )
							)
						);
					?>
					</strong>
			</p>
			<?php
			return apply_filters( 'gateway_ifthen_email_instructions_payment_received', ob_get_clean(), $order_id );
		}

		/**
		 * API Init Payment
		 * https://ifthenpay.com/docs/en/api/pbl/#tag/pay-by-link--pinpay/POST/{GATEWAY_KEY}
		 *
		 * @param int $order_id The order ID.
		 * @return url or false
		 */
		private function api_init_payment( $order_id ) {
			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$id                = $order_id;
			$order             = wc_get_order( $order_id );
			$valor             = WC_IfthenPay_Webdados()->get_order_total_to_pay_for_gateway( $order );
			$gatewaykey        = apply_filters( 'multibanco_ifthen_base_gatewaykey', $this->gatewaykey, $order );
			$wd_secret         = substr( strrev( md5( time() ) ), 0, 10 ); // Set a secret on our end for extra validation
			$id_for_backoffice = (string) apply_filters( 'ifthen_webservice_send_order_number_instead_id', false ) ? $order->get_order_number() : $order->get_id();
			$desc              = trim( get_bloginfo( 'name' ) ) . ' #' . $order->get_order_number();
			$accounts          = $this->methods_keys;
			$lang              = strtolower( substr( trim( get_locale() ), 0, 2 ) );
			if (
				! in_array(
					$lang,
					array(
						'pt',
						'en',
						'es',
						'fr',
					),
					true
				)
			) {
				$lang = 'en';
			}
			$url        = $this->api_url . $gatewaykey;
			$return_url = WC_IfthenPay_Webdados()->gateway_ifthen_return_url;
			$return_url = add_query_arg( 'id', $id_for_backoffice, $return_url );
			$return_url = add_query_arg( 'wd_secret', $wd_secret, $return_url );
			$return_url = add_query_arg( 'amount', $valor, $return_url );
			$args       = array(
				'method'   => 'POST',
				'timeout'  => apply_filters( 'gateway_ifthen_api_timeout', 15 ),
				'blocking' => true,
				'body'     => array(
					'id'            => (string) $id_for_backoffice,
					'amount'        => (string) $valor,
					'description'   => $desc,
					'accounts'      => str_replace( ' ', '', implode( ';', $accounts ) ),
					'expiredate'    => '', // To set in a future version?
					'success_url'   => add_query_arg( 'status', 'success', $return_url ),
					'error_url'     => add_query_arg( 'status', 'error', $return_url ), // We should add an error notice
					'cancel_url'    => wc_get_checkout_url(),
					'btnCloseUrl'   => wc_get_checkout_url(),
					'btnCloseLabel' => __( 'Close', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'lang'          => $lang,
				),
			);
			$this->debug_log_extra( '- Request payment with args: ' . wp_json_encode( $args ) );
			$debug_start_time = microtime( true );
			$args['body']     = wp_json_encode( $args['body'] ); // Json not post variables
			$response         = wp_remote_post( $url, $args );
			if ( is_wp_error( $response ) ) {
				$debug_msg       = '- Error contacting the ifthenpay servers - Order ' . $order->get_id() . ' - ' . $response->get_error_message();
				$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
				$this->debug_log( $debug_msg, 'error', true, $debug_msg_email );
				return false;
			} elseif ( isset( $response['response']['code'] ) && intval( $response['response']['code'] ) === 200 && isset( $response['body'] ) && trim( $response['body'] ) !== '' ) {
				$body = json_decode( trim( $response['body'] ) );
				if ( $body ) {
					WC_IfthenPay_Webdados()->set_order_gatewayifthenpay_details(
						$order->get_id(),
						array(
							'gatewaykey'  => $gatewaykey,
							'pincode'     => $body->PinCode,
							'id'          => apply_filters( 'ifthen_webservice_send_order_number_instead_id', false ) ? $order->get_order_number() : $order->get_id(),
							'val'         => $valor,
							'payment_url' => $body->RedirectUrl,
							'wd_secret'   => $wd_secret,
						)
					);
					$this->debug_log( '- ifthenpay Gateway payment request created on ifthenpay servers - Redirecting to payment gateway - Order ' . $order->get_id() . ' - Pincode: ' . $body->PinCode );
					do_action( 'gateway_ifthen_created_reference', $body->PinCode, $order->get_id() );
					$debug_elapsed_time = microtime( true ) - $debug_start_time;
					$this->debug_log_extra( 'wp_remote_post + response handling took: ' . $debug_elapsed_time . ' seconds.' );
					return $body->RedirectUrl;
				} else {
					$debug_msg = '- Error contacting the ifthenpay servers - Order ' . $order->get_id() . ' - Can not json_decode body';
					$this->debug_log( $debug_msg, 'error', true, $debug_msg );
					return false;
				}
			} else {
				$debug_msg       = '- Error contacting the ifthenpay servers - Order ' . $order->get_id() . ' - Incorrect response code: ' . $response['response']['code'];
				$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
				$this->debug_log( $debug_msg, 'error', true, $debug_msg_email );
				return false;
			}
			return false;
			// phpcs:enable
		}

		/**
		 * Process it
		 *
		 * @param  int $order_id Order ID.
		 * @throws Exception     Error message.
		 */
		public function process_payment( $order_id ) {
			// Webservice
			$order = wc_get_order( $order_id );
			do_action( 'gateway_ifthen_before_process_payment', $order );
			if ( $order->get_total() > 0 ) {
				$redirect_url = $this->api_init_payment( $order->get_id() );
				if ( ! empty( $redirect_url ) ) {
					// WooCommerce Deposits - When generating second payment reference the order goes from partially paid to on hold, and that has an email (??!)
					if ( WC_IfthenPay_Webdados()->wc_deposits_active && $order->get_status() === 'partially-paid' ) {
						add_filter( 'woocommerce_email_enabled_customer_processing_order', '__return_false' );
						add_filter( 'woocommerce_email_enabled_full_payment', '__return_false' );
					}
					// Mark pending
					WC_IfthenPay_Webdados()->set_initial_order_status( $order, 'pending', __( 'ifthenpay Gateway', 'multibanco-ifthen-software-gateway-for-woocommerce' ) );
				} else {
					throw new Exception(
						sprintf(
							/* translators: %s: payment method */
							esc_html__( 'An error occurred processing the %s Payment request - please try again', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							esc_html__( 'ifthenpay Gateway', 'multibanco-ifthen-software-gateway-for-woocommerce' )
						)
					);
				}
				// Remove cart - not now, only after paid
			} else {
				// Value = 0
				$order->payment_complete();
				// Remove cart
				if ( isset( WC()->cart ) ) {
					WC()->cart->empty_cart();
				}
				// Empty awaiting payment session - not now, only after paid
				unset( WC()->session->order_awaiting_payment );
			}
			// Return payment url redirect
			return array(
				'result'   => 'success',
				'redirect' => $redirect_url, // Payment gateway URL
			);
		}

		/**
		 * Disable if key not correctly set
		 *
		 * @param array $available_gateways The available payment gateways.
		 */
		public function disable_if_settings_missing( $available_gateways ) {
			if (
				// Backoffice Key
				strlen( trim( $this->backoffice_key ) ) !== 19
				||
				// Gateway key
				strlen( trim( $this->gatewaykey ) ) !== 11
				||
				// Methods set
				empty( $this->methods_keys )
				||
				// Enabled
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
		 * Payment complete
		 *
		 * @param  WC_Order $order Order object.
		 * @param  string   $txn_id Transaction ID.
		 * @param  string   $note Payment note.
		 */
		public function payment_complete( $order, $txn_id = '', $note = '' ) {
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
		 * Return from the payment gateway
		 */
		public function return_payment_gateway() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended

			$redirect_url = '';
			$error        = false;
			$order_id     = 0;
			$orders_exist = false;

			$server_request_uri = WC_IfthenPay_Webdados()->get_request_uri();

			if (
				isset( $_GET['id'] )
				&&
				isset( $_GET['amount'] )
				&&
				isset( $_GET['wd_secret'] )
			) {
				$this->debug_log( '- Return from payment gateway (' . $server_request_uri . ') with all arguments' );
				$id        = trim( sanitize_text_field( wp_unslash( $_GET['id'] ) ) );
				$val       = trim( sanitize_text_field( wp_unslash( $_GET['amount'] ) ) ); // Não fazemos float porque 7.40 passaria a 7.4 e depois não validava a hash
				$wd_secret = trim( sanitize_text_field( wp_unslash( $_GET['wd_secret'] ) ) );
				$get_order = $this->callback_helper_get_pending_order( $id, $val, $wd_secret );
				$success   = isset( $_GET['status'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['status'] ) ) ) === 'success' : false;
				switch ( $success ) {

					case true:
						if ( $get_order['success'] && $get_order['order'] ) {
							$order = $get_order['order'];
							$this->debug_log_extra( 'Order found: ' . $order->get_id() . ' - Status: ' . $order->get_status() );
							$order_id      = $order->get_id();
							$order_details = WC_IfthenPay_Webdados()->get_gatewayifthenpay_order_details( $order->get_id() );
							$note          = __( 'ifthenpay Gateway payment pre-approval received.', 'multibanco-ifthen-software-gateway-for-woocommerce' );
							$url           = $this->get_return_url( $order );
							do_action( 'gateway_ifthen_return_payment_gateway_complete', $order->get_id(), $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							$debug_order = wc_get_order( $order->get_id() );
							$this->debug_log( '-- ifthenpay Gateway payment pre-approval received - Order ' . $order->get_id(), 'notice' );
							$this->debug_log_extra( 'Redirect to thank you page: ' . $url . ' - Order ' . $order->get_id() . ' - Status: ' . $debug_order->get_status() );
							$order->delete_meta_data( '_' . $this->id . '_checkouterror' );
							$order->save();
							wp_safe_redirect( $url );
							exit;
						} else {
							$error        = $get_order['error'];
							$redirect_url = wc_get_checkout_url();
						}
						break;

					default:
						// No additional $_GET field with the error code or message?
						if ( $get_order['success'] && $get_order['order'] ) {
							$order    = $get_order['order'];
							$order_id = $order->get_id();
							$error    = __( 'Payment failed on the gateway. Please try again.', 'multibanco-ifthen-software-gateway-for-woocommerce' );
							$order->update_status( 'failed', $error );
							switch ( $order->get_created_via() ) {
								case 'store-api':
									// Blocks checkout - Store the error and show it later, as the block based checkout does not have wc_add_notice();
									// wc_add_notice( $error, 'error' );
									break;
								case 'checkout':
								default:
									// Classic checkout - Directly show the error - OK!
									wc_add_notice( $error, 'error' );
									break;
							}
						} else {
							$error = __( 'Payment failed on the gateway. Please try again.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' - ' . $get_order['error'];
						}
						$redirect_url = wc_get_checkout_url();
						break;

				}
			} else {
				$error  = 'Return from payment gateway (' . $server_request_uri . ') with missing arguments';
				$error .= ' - ' . wp_json_encode( $_GET );
			}

			// Error and redirect
			if ( $error ) {
				if ( ! empty( $order ) ) {
					$order->update_meta_data( '_' . $this->id . '_checkouterror', $error ); // To show error on blocks checkout
					$order->save();
					do_action( 'gateway_ifthen_return_payment_gateway_failed', $order_id, $error, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}
				$this->debug_log( '- ' . $error, 'warning', true, $error );
				if ( $redirect_url ) { // What if we don't have a redirect?
					wp_safe_redirect( $redirect_url );
				}
				exit;
			}
			// phpcs:enable
		}

		/**
		 * Callback
		 */
		public function callback() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended

			$orders_exist       = false;
			$server_http_host   = WC_IfthenPay_Webdados()->get_http_host();
			$server_request_uri = WC_IfthenPay_Webdados()->get_request_uri();
			$server_remote_addr = WC_IfthenPay_Webdados()->get_remote_addr();

			if (
				isset( $_GET['key'] )
				&&
				isset( $_GET['id'] )
				&&
				isset( $_GET['amount'] )
				&&
				isset( $_GET['payment_method'] )
				&&
				isset( $_GET['payment_method_key'] )
				&&
				isset( $_GET['request_id'] )
				&&
				isset( $_GET['status'] )
			) {
				$this->debug_log( '- Callback (' . $server_request_uri . ') with all arguments from ' . $server_remote_addr );
				$id                 = trim( sanitize_text_field( wp_unslash( $_GET['id'] ) ) );
				$val                = floatval( $_GET['amount'] );
				$status             = trim( sanitize_text_field( wp_unslash( $_GET['status'] ) ) );
				$payment_method     = trim( sanitize_text_field( wp_unslash( $_GET['payment_method'] ) ) );
				$payment_method_key = trim( sanitize_text_field( wp_unslash( $_GET['payment_method_key'] ) ) );
				$request_id         = trim( sanitize_text_field( wp_unslash( $_GET['request_id'] ) ) ); // This is what we'll use for refunds later
				$arguments_ok       = true;
				$arguments_error    = '';
				if ( trim( sanitize_text_field( wp_unslash( $_GET['key'] ) ) ) !== trim( $this->secret_key ) ) {
					$arguments_ok     = false;
					$arguments_error .= ' - Key';
				}
				if ( trim( $id ) === '' ) { // If using ifthen_webservice_send_order_number_instead_id, this can be a non-numeric value
					$arguments_ok     = false;
					$arguments_error .= ' - ID (numeric)';
				}
				if ( trim( $request_id ) === '' ) {
					$arguments_ok     = false;
					$arguments_error .= ' - request_id';
				}
				if ( abs( $val ) < WC_IfthenPay_Webdados()->gateway_ifthen_min_value ) {
					$arguments_ok     = false;
					$arguments_error .= ' - Value';
				}
				if ( ! in_array( $status, array( 'PAGO', 'DEVOLVIDO' ), true ) ) {
					$arguments_ok     = false;
					$arguments_error .= ' - Estado';
				}
				if ( $arguments_ok ) { // Isto deve ser separado em vários IFs para melhor se identificar o erro
					if ( trim( $status ) === 'PAGO' ) {
						$orders_exist   = false;
						$pending_status = apply_filters( 'gateway_ifthen_valid_callback_pending_status', WC_IfthenPay_Webdados()->unpaid_statuses ); // Double filter - Should we deprectate this one?
						$args           = array(
							'type'                  => array( 'shop_order', 'wcdp_payment' ), // Regular order or deposit
							'status'                => $pending_status,
							'limit'                 => -1,
							'_' . $this->id . '_id' => $id,
						);
						$orders         = WC_IfthenPay_Webdados()->wc_get_orders( $args, $this->id );
						if ( count( $orders ) > 0 ) {
							$orders_exist = true;
							$orders_count = count( $orders );
							foreach ( $orders as $order ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedForeach
								// Just getting the last one
							}
						}
						if ( $orders_exist ) {
							if ( $orders_count === 1 ) {
								if (
									trim( $order->get_id() ) === trim( $id )
									||
									trim( $order->get_order_number() ) === trim( $id ) // because ifthen_webservice_send_order_number_instead_id
								) {
									if ( floatval( $val ) === floatval( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ) ) ) {
										$note = sprintf(
											/* translators: %s payment method */
											__( 'ifthenpay Gateway payment received via %s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
											$payment_method
										);
										if ( isset( $_GET['payment_datetime'] ) ) {
											$note .= ' ' . trim( sanitize_text_field( wp_unslash( $_GET['payment_datetime'] ) ) );
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
										// Payment method
										$order->update_meta_data( '_' . $this->id . '_payment_method', $payment_method );
										$order->update_meta_data( '_' . $this->id . '_payment_method_key', $payment_method_key );
										$order->update_meta_data( '_' . $this->id . '_request_id', $request_id );
										$order->save();
										$this->payment_complete( $order, '', $note );
										do_action( 'gateway_ifthen_callback_payment_complete', $order->get_id(), $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
										header( 'HTTP/1.1 200 OK' );
										$this->debug_log( '-- ifthenpay Gateway payment received - Order ' . $order->get_id(), 'notice' );
										echo 'OK - ifthenpay Gateway payment received';
									} else {
										header( 'HTTP/1.1 200 OK' );
										$err = 'Error: The value does not match';
										$this->debug_log( '-- ' . $err . ' - Order ' . $order->get_id(), 'warning', true, 'Callback (' . $server_http_host . ' ' . $server_request_uri . ') from ' . $server_remote_addr . ' - The value does not match' );
										echo esc_html( $err );
										do_action( 'gateway_ifthen_callback_payment_failed', $order->get_id(), $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
									}
								} else {
									header( 'HTTP/1.1 200 OK' );
									$err = 'Error: ifthenpay Gateway payment received but order id or number does not match ID - Order callbak ID ' . $id . ' - Order id ' . $order->get_id() . ' - Order number ' . $order->get_order_number();
									$this->debug_log( '-- ' . $err, 'warning', true, 'Callback (' . $server_http_host . ' ' . $server_request_uri . ') from ' . $server_remote_addr );
									echo esc_html( $err );
									do_action( 'gateway_ifthen_callback_payment_failed', 0, $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
								}
							} else {
								header( 'HTTP/1.1 200 OK' );
								$err = 'Error: More than 1 order found awaiting payment with these details';
								$this->debug_log( '-- ' . $err, 'warning', true, 'Callback (' . $server_http_host . ' ' . $server_request_uri . ') from ' . $server_remote_addr . ' - More than 1 order found awaiting payment with these details' );
								echo esc_html( $err );
								do_action( 'gateway_ifthen_callback_payment_failed', 0, $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							}
						} else {
							header( 'HTTP/1.1 200 OK' );
							$err = 'Error: No orders found awaiting payment with these details';
							$this->debug_log( '-- ' . $err, 'warning', true, 'Callback (' . $server_http_host . ' ' . $server_request_uri . ') from ' . $server_remote_addr . ' - No orders found awaiting payment with these details' );
							echo esc_html( $err );
							do_action( 'gateway_ifthen_callback_payment_failed', 0, $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						}
					} elseif ( trim( $status ) === 'DEVOLVIDO' && $this->do_refunds ) {

						// Porque não é compatível com o novo filtro ifthen_webservice_send_order_number_instead_id temos de ir buscar primeiro a order através do idPedido que é o mesmo e depois ir buscar os refunds que são childs dessa order
						$order_exist   = false;
						$refunds_exist = false;
						// First, find the order, using $request_id as $id may not be a order id because of ifthen_webservice_send_order_number_instead_id
						$args   = array(
							'type'  => array( 'shop_order' ),
							'limit' => -1,
							'_' . $this->id . '_request_id' => $request_id,
						);
						$orders = wc_get_orders( WC_IfthenPay_Webdados()->maybe_translate_order_query_args( $args ) );
						if ( ! empty( $orders ) ) {
							if ( count( $orders ) === 1 ) {
								$order       = $orders[0];
								$order_exist = true;
							} else {
								$err = 'Error: More than 1 order found with the same request_id';
							}
						} else {
							$err = 'Error: No orders found with this request_id';
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
								if ( $refund->get_meta( '_' . WC_IfthenPay_Webdados()->gateway_ifthen_id . '_callback_received' ) === '' ) {
									if ( abs( floatval( $val ) ) === abs( floatval( WC_IfthenPay_Webdados()->get_order_total_to_pay( $refund ) ) ) ) {
										$note = sprintf(
											/* translators: %s: refund id */
											__( 'ifthenpay Gateway callback received for successfully processed refund #%s by ifthenpay.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
											$refund->get_id()
										);
										$order->add_order_note( $note );
										// Set as callback received so we do not process it again
										$refund->update_meta_data( '_' . WC_IfthenPay_Webdados()->gateway_ifthen_id . '_callback_received', date_i18n( 'Y-m-d H:i:s' ) );
										$refund->save();
										$refunds_exist = true;
									}
								}
							}
						}
						if ( $refunds_exist ) {
							// We're done!
							header( 'HTTP/1.1 200 OK' );
							$this->debug_log( '-- ifthenpay Gateway refund received - Order ' . $order->get_id() . ' - Refund ' . $refund->get_id(), 'notice' );
							echo 'OK - ifthenpay Gateway refund received';
							do_action( 'gateway_ifthen_callback_refund_complete', $order->get_id() );
						} else {
							header( 'HTTP/1.1 200 OK' );
							if ( ! isset( $err ) ) {
								$err = 'Error: No unprocessed refunds found with these details';
							}
							$this->debug_log( '-- ' . $err, 'warning', true, 'Callback (' . WC_IfthenPay_Webdados()->get_http_host() . ' ' . WC_IfthenPay_Webdados()->get_request_uri() . ') from ' . WC_IfthenPay_Webdados()->get_remote_addr() . ' - No refunds found with these details' );
							echo esc_html( $err );
							do_action( 'gateway_ifthen_callback_refund_failed', 0, $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						}
						// ???

					} else {
						header( 'HTTP/1.1 200 OK' );
						$err = 'Error: Cannot process ' . trim( $status ) . ' status';
						$this->debug_log( '-- ' . $err, 'warning', true, 'Callback (' . $server_http_host . ' ' . $server_request_uri . ') from ' . $server_remote_addr . ' - Cannot process ' . trim( $status ) . ' status' );
						echo esc_html( $err );
						do_action( 'gateway_ifthen_callback_payment_failed', 0, $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					}
				} else {
					$err = 'Argument errors';
					$this->debug_log( '-- ' . $err . $arguments_error, 'warning', true, 'Callback (' . $server_http_host . ' ' . $server_request_uri . ') with argument errors from ' . $server_remote_addr . $arguments_error );
					do_action( 'gateway_ifthen_callback_payment_failed', 0, $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					wp_die( esc_html( $err ), 'WC_Gateway_IfThen_Webdados', array( 'response' => 500 ) ); // Sends 500
				}
			} else {
				$err = 'Callback (' . $server_request_uri . ') with missing arguments from ' . $server_remote_addr;
				$this->debug_log( '- ' . $err, 'warning', true, 'Callback (' . $server_http_host . ' ' . $server_request_uri . ') with missing arguments from ' . $server_remote_addr );
				do_action( 'gateway_ifthen_callback_payment_failed', 0, $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wp_die( 'Error: Something is missing...', 'WC_Gateway_IfThen_Webdados', array( 'response' => 500 ) ); // Sends 500
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Can the order be refunded via this gateway?
		 *
		 * Extended to do our own tests
		 *
		 * @param  WC_Order $order Order object.
		 * @return bool If false, the automatic refund button is hidden in the UI.
		 */
		public function can_refund_order( $order ) {
			if ( $order && $this->supports( 'refunds' ) ) {
				$order_details = WC_IfthenPay_Webdados()->get_gatewayifthenpay_order_details( $order->get_id() );
				if (
					// Apple, Google and PIX (And eventually MBWAY and CCARD if the gateway_ifthen_unavailable_methods filter was used)
					in_array( $order_details['payment_method'], array( 'APPLE', 'GOOGLE', 'PIX', 'MBWAY', 'CCARD' ), true )
					&&
					// Has request ID = Already paid for
					! empty( $order_details['request_id'] )
				) {
					return true;
				}
			}
			return false;
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
				// Add note because there will be no callback
				$order = wc_get_order( $order_id );
				$order->add_order_note( __( 'Apple Pay, Google Pay, or PIX refund successfully processed by ifthenpay.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) );
			}
			return $result;
		}

		/**
		 * Helper to get pending order on calback
		 *
		 * @param mixed  $id        The unique ID, normally Order ID.
		 * @param float  $val       The order value.
		 * @param string $wd_secret The secret set to validate callbacks.
		 */
		private function callback_helper_get_pending_order( $id, $val, $wd_secret = null ) {
			$return         = array(
				'success' => false,
				'error'   => false,
				'order'   => false,
			);
			$pending_status = apply_filters( 'gateway_ifthen_valid_callback_pending_status', WC_IfthenPay_Webdados()->unpaid_statuses ); // Double filter - Should we deprectate this one?
			$args           = array(
				'type'                  => array( 'shop_order', 'wcdp_payment' ), // Regular order or deposit
				'status'                => $pending_status,
				'limit'                 => -1,
				'_' . $this->id . '_id' => $id,
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
				if ( $orders_count === 1 ) {
					if ( floatval( $val ) === floatval( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ) ) ) {
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
			// New method
			if (
				// Only show to users who can manage the shop
				current_user_can( 'manage_woocommerce' )
				&&
				// Key not set or method not enabled
				(
					strlen( trim( $this->gatewaykey ) ) !== 11
					||
					trim( $this->enabled ) !== 'yes'
				)
				&&
				// Not prevented by filter
				( ! apply_filters( 'multibanco_ifthen_hide_newmethod_notifications', false ) )
				&&
				// Check if dismissed in the last 180 days
				( intval( get_user_meta( get_current_user_id(), $this->id . '_newmethod_notice_dismiss_until', true ) ) < time() )
				&&
				// Check if 90-day dismissal is active - Legacy support
				( ! get_transient( $this->id . '_newmethod_notice_dismiss_' . get_current_user_id() ) )
			) {
				?>
				<div id="<?php echo esc_attr( $this->id ); ?>_newmethod_notice" class="notice notice-info is-dismissible" style="padding-right: 38px; position: relative;">
					<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->gateway_ifthen_banner ); ?>" style="float: left; margin-top: 1.25em; margin-bottom: 0.5em; margin-right: 1em; max-height: 24px; max-width: 93px;"/>
					<p>
						<?php
							echo wp_kses_post(
								sprintf(
									/* translators: %s: payment method */
									__( 'There’s a new payment method available: %s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									'<strong>' . __( 'ifthenpay Gateway', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' (Apple Pay, Google Pay, PIX)</strong>'
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
				<?php
				WC_IfthenPay_Webdados()->dismiss_newmethod_notice_javascript( $this->id );
			}
		}
	}
}
