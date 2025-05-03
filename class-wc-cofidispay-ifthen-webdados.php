<?php
/**
 * Cofidis Pay class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'COFIDISPAY_IFTHEN_DESC_LEN', 200 );

if ( ! class_exists( 'WC_CofidisPay_IfThen_Webdados' ) ) {

	/**
	 * CofidisPay IfThen Class.
	 */
	class WC_CofidisPay_IfThen_Webdados extends WC_Payment_Gateway {

		/* Single instance */
		protected static $_instance = null;
		public static $instances    = 0;

		/* Properties */
		public $debug;
		public $debug_email;
		public $version;
		public $secret_key;
		public $api_url;
		public $limits_api_url;
		public $cofidispaykey;
		public $settings_saved;
		public $send_to_admin;
		public $only_portugal;
		public $only_above;
		public $only_below;

		/**
		 * Constructor for your payment class
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {

			++self::$instances;

			$this->id = WC_IfthenPay_Webdados()->cofidispay_id;

			// Logs
			$this->debug       = ( $this->get_option( 'debug' ) === 'yes' ? true : false );
			$this->debug_email = $this->get_option( 'debug_email' );

			// Check version and upgrade
			$this->version = WC_IfthenPay_Webdados()->get_version();
			$this->upgrade();

			$this->has_fields = false;

			$this->method_title       = __( 'Cofidis Pay', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' (ifthenpay)';
			$this->method_description = __( 'Pay for your order in 3 to 12 interest-free and fee-free installments using your debit or credit card.', 'multibanco-ifthen-software-gateway-for-woocommerce' );
			$this->secret_key         = $this->get_option( 'secret_key' );
			if ( trim( $this->secret_key ) === '' ) {
				// First load?
				$this->secret_key = md5( home_url() . time() . wp_rand( 0, 999 ) );
				// Save
				$this->update_option( 'secret_key', $this->secret_key );
				$this->update_option( 'debug', 'yes' );
				// Let's set the callback activation email as NOT sent
				update_option( $this->id . '_callback_email_sent', 'no' );
			}

			// Webservice
			$this->api_url        = 'https://api.ifthenpay.com/cofidis/init/'; // production and test mode, depends on Cofidis Pay Key
			$this->limits_api_url = 'https://api.ifthenpay.com/cofidis/limits/';

			// Plugin options and settings
			$this->init_form_fields();
			$this->init_settings();

			// User settings
			$this->title          = $this->get_option( 'title' );
			$this->description    = $this->get_option( 'description' );
			$this->cofidispaykey  = $this->get_option( 'cofidispaykey' );
			$this->settings_saved = $this->get_option( 'settings_saved' );
			$this->send_to_admin  = ( $this->get_option( 'send_to_admin' ) === 'yes' ? true : false );
			$this->only_portugal  = ( $this->get_option( 'only_portugal' ) === 'yes' ? true : false );
			$this->only_above     = $this->get_option( 'only_above' );
			$this->only_below     = $this->get_option( 'only_bellow' );

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

				// NO SMS Integrations for Cofidis Pay

				// Customer Emails
				// Regular orders
				add_action(
					apply_filters( 'cofidispay_ifthen_email_hook', 'woocommerce_email_before_order_table' ),
					array( $this, 'email_instructions_1' ), // Avoid "Hyyan WooCommerce Polylang Integration" remove_action
					apply_filters( 'cofidispay_ifthen_email_hook_priority', 10 ),
					4
				);

				// Payment listener - Return from payment gateway
				add_action( 'woocommerce_api_wc_cofidispayreturn_ifthen_webdados', array( $this, 'return_payment_gateway' ) );

				// Payment listener - ifthenpay callback
				add_action( 'woocommerce_api_wc_cofidispay_ifthen_webdados', array( $this, 'callback' ) );

				// Admin notices
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );

				// Sandbox mode for known keys
				add_filter(
					'cofidispay_ifthen_sandbox',
					function ( $is_sandbox ) {
						$known_sandbox_keys = array(
							'AAA-000001',
						);
						if ( in_array( $this->cofidispaykey, $known_sandbox_keys, true ) ) {
							return true;
						}
						return $is_sandbox;
					}
				);

				// Method title in sandbox mode
				if ( apply_filters( 'cofidispay_ifthen_sandbox', false ) ) {
					$this->title .= ' - SANDBOX (TEST MODE)';
				}

				// Add info to description
				$this->description = sprintf(
					'%1$s<br/><small>%2$s<br/>%3$s</small>',
					$this->description,
					__( 'You will be redirected to a secure page to make the payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					__( 'Payment of installments will be made to the customer’s debit or credit card through a payment solution based on a factoring contract between Cofidis and the Merchant. Find out more at Cofidis, registered with Banco de Portugal under number 921.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
				);
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
				'enabled'       => array(
					'title'       => __( 'Enable/Disable', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => sprintf(
						/* translators: %s: Gateway name */
						__( 'Enable “%s” (using ifthenpay)', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						__( 'Cofidis Pay', 'multibanco-ifthen-software-gateway-for-woocommerce' )
					),
					'description' => __( 'Requires a separate contract with Cofidis.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'default'     => 'no',
				),
				'cofidispaykey' => array(
					'title'             => __( 'Cofidis Pay Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'type'              => 'text',
					'description'       => sprintf(
						/* translators: %s: Gateway key name */
						__( '%s provided by ifthenpay when signing the contract.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						__( 'Cofidis Pay Key', 'multibanco-ifthen-software-gateway-for-woocommerce' )
					) . ( apply_filters( 'cofidispay_ifthen_sandbox', false ) ? '<br><span style="color: red;">Sandbox</span>' : '' ),
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
					'secret_key'    => array(
						'title'       => __( 'Anti-phishing key', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' (Cofidis Pay)',
						'type'        => 'hidden',
						'description' => '<strong id="woocommerce_' . $this->id . '_secret_key_label">' . $this->secret_key . '</strong><br/>' . __( 'To ensure callback security, generated by the system and which must be provided to ifthenpay when asking for the callback activation.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'default'     => $this->secret_key,
					),
					'title'         => array(
						'title'       => __( 'Title', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'type'        => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
										. ( WC_IfthenPay_Webdados()->wpml_active ? '<br/>' . WC_IfthenPay_Webdados()->wpml_translation_info : '' ),
						'default'     => 'Cofidis Pay - Up to 12x interest-free',
						// PT: Cofidis Pay - Até 12x sem juros
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
						'description' =>
							__( 'Enable only for orders with a value from x &euro;. Leave blank (or zero) to allow for any order value.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
							.
							' <br/> '
							.
							__( 'This was set automatically based on your Cofidis contract, but you can adjust if the contract was changed or you want to further limit the values interval.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'default'     => '',
					),
					'only_bellow'   => array(
						'title'       => __( 'Only for orders up to', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'type'        => 'number',
						'description' =>
							__( 'Enable only for orders with a value up to x &euro;. Leave blank (or zero) to allow for any order value.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
							.
							' <br/> '
							.
							__( 'This was set automatically based on your Cofidis contract, but you can adjust if the contract was changed or you want to further limit the values interval.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
						'default'     => '',
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
			// PRO fake fields
			$pro_fake_fields = array(
				// Product banner
				'_pro_show_product_banner' => array(
					'type'     => 'checkbox',
					'title'    => __( 'Show product banner', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
					'label'    => __( 'Show Cofidis payment information banner below the price on product pages, with the number of instalments and price to pay per month', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
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
					'<a href="https://nakedcatplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/' . esc_attr( WC_IfthenPay_Webdados()->out_link_utm ) . '" target="_blank">',
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
			$this->form_fields = array_merge( $this->form_fields, apply_filters( 'multibanco_ifthen_cofidispay_settings_fields', array() ) );
			// And to manipulate them
			$this->form_fields = apply_filters( 'multibanco_ifthen_cofidispay_settings_fields_all', $this->form_fields );
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
						<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->cofidispay_banner ); ?>" alt="<?php echo esc_attr( $title ); ?>" width="200" height="48"/>
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
						<li>
							<?php
							printf(
								/* translators: %s: link to Cofidis */
								esc_html__( 'Sign a contract with %s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								'<strong><a href="https://www.cofidis.pt/' . esc_attr( WC_IfthenPay_Webdados()->out_link_utm ) . '" target="_blank">Cofidis</a></strong>'
							);
							?>
						</li>
						<li>
							<?php
								echo esc_html(
									sprintf(
										/* translators: %s: Gateway key name */
										__( 'Fill out all the details (%s) provided by ifthenpay in the fields below.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
										__( 'Cofidis Pay Key', 'multibanco-ifthen-software-gateway-for-woocommerce' )
									)
								);
							?>
						</li>
						<?php
						echo wp_kses_post(
							sprintf(
								/* translators: %1$s: payment method keys, %2$s: link to ifthenpay */
								esc_html__( 'Do not use the same %1$s on multiple websites or any other system, online or offline. Ask %2$s for new ones for every single platform.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								esc_html__( 'Cofidis Pay Key', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
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
									__( 'Ask ifthenpay to activate “Cofidis Pay Callback” on your account using this exact URL: %1$s and this Anti-phishing key: %2$s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									'<br/><code><strong>' . WC_IfthenPay_Webdados()->cofidispay_notify_url . '</strong></code><br/>',
									'<br/><code><strong>' . $this->secret_key . '</strong></code>'
								)
							);
							?>
						</li>
					</ul>
					<?php
					if (
						strlen( trim( $this->cofidispaykey ) ) === 10
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
									<th scope="row" class="titledesc">Cofidis Pay Key</th>
									<td class="forminp">
										<?php echo esc_html( $this->cofidispaykey ); ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc"><?php esc_html_e( 'Anti-phishing key', 'multibanco-ifthen-software-gateway-for-woocommerce' ) . ' (Cofidis Pay)'; ?></th>
									<td class="forminp">
										<?php echo esc_html( $this->secret_key ); ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc"><?php esc_html_e( 'Callback URL', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></th>
									<td class="forminp">
										<?php echo esc_url( WC_IfthenPay_Webdados()->cofidispay_notify_url ); ?>
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
								<p><strong><?php esc_html_e( 'Invalid Cofidis Pay Key (exactly 10 characters).', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong></p>
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
													__( 'Cofidis Pay Key', 'multibanco-ifthen-software-gateway-for-woocommerce' )
												)
											);
										?>
									</strong>
								</p>
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
		 * Intercept process_admin_options and set min and max values for this gateway from the ifthenpay limits API endpoint
		 */
		public function process_admin_options() {
			// WooCommerce already took care of Nonces
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST[ 'woocommerce_' . $this->id . '_cofidispaykey' ] ) ) {
				$new_key = trim( sanitize_text_field( wp_unslash( $_POST[ 'woocommerce_' . $this->id . '_cofidispaykey' ] ) ) );
				if ( strlen( $new_key ) === 10 && $new_key !== trim( $this->get_option( 'cofidispaykey' ) ) ) {
					$url      = $this->limits_api_url . $new_key;
					$response = wp_remote_get( $url );
					if ( ! is_wp_error( $response ) ) {
						if ( isset( $response['response']['code'] ) && intval( $response['response']['code'] ) === 200 && isset( $response['body'] ) && trim( $response['body'] ) !== '' ) {
							$body = json_decode( trim( $response['body'] ) );
							if ( $body ) {
								if ( isset( $body->message ) && $body->message === 'success' && isset( $body->limits->maxAmount ) && intval( $body->limits->maxAmount ) > 0 && isset( $body->limits->minAmount ) && intval( $body->limits->minAmount ) > 0 ) {
									// Override min and max values
									$_POST[ 'woocommerce_' . $this->id . '_only_above' ]  = intval( $body->limits->minAmount );
									$_POST[ 'woocommerce_' . $this->id . '_only_bellow' ] = intval( $body->limits->maxAmount );
								}
							}
						}
					}
				}
			}
			return parent::process_admin_options();
			// phpcs:enable WordPress.Security.NonceVerification.Missing
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
				$result = WC_IfthenPay_Webdados()->callback_webservice( $bo_key, 'COFIDIS', $this->cofidispaykey, $this->secret_key, WC_IfthenPay_Webdados()->cofidispay_notify_url );
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
				$subject = 'Activação de Callback Cofidis Pay (Key: ' . $this->cofidispaykey . ')';
				$message = 'Por favor activar Callback Cofidis Pay com os seguintes dados:

Cofidis Pay Key:
' . $this->cofidispaykey . '

Chave anti-phishing (Cofidis Pay):
' . $this->secret_key . '

URL:
' . WC_IfthenPay_Webdados()->cofidispay_notify_url . '

Email enviado automaticamente do plugin WordPress “ifthenpay for WooCommerce” para ' . $to . ' com CC para ' . $cc;
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
			$icon_html = '<img src="' . esc_attr( WC_IfthenPay_Webdados()->cofidispay_icon ) . '" alt="' . esc_attr( $alt ) . '" width="24" height="24"/>';
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
						do_action( 'cofidispay_ifthen_after_thankyou_instructions_table', $order );
					}

					if ( is_wc_endpoint_url( 'order-received' ) ) {
						if ( apply_filters( 'cofidispay_ifthen_enable_check_order_status_thankyou', true, $order->get_id() ) ) { // return false to cofidispay_ifthen_enable_check_order_status_thankyou in order to stop the ajax checking
							// Check order status
							?>
							<input type="hidden" id="cofidispay-order-id" value="<?php echo intval( $order->get_id() ); ?>"/>
							<input type="hidden" id="cofidispay-order-key" value="<?php echo esc_attr( $order->get_order_key() ); ?>"/>
							<?php
							wp_enqueue_script( 'cofidispay-ifthenpay', plugins_url( 'assets/cofidispay.js', __FILE__ ), array( 'jquery' ), $this->version . ( WP_DEBUG ? '.' . wp_rand( 0, 99999 ) : '' ), true );
							wp_localize_script(
								'cofidispay-ifthenpay',
								'cofidispay_ifthenpay',
								array(
									'interval'           => apply_filters( 'cofidispay_ifthen_check_order_status_thankyou_interval', 10 ),
									'cofidispay_minutes' => 5,
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
			$alt                      = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_title', $this->title ) : $this->title );
			$cofidispay_order_details = WC_IfthenPay_Webdados()->get_cofidispay_order_details( $order_id );
			$order                    = wc_get_order( $order_id );
			ob_start();
			echo $this->thankyou_instructions_table_html_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			<table class="<?php echo esc_attr( $this->id ); ?>_table" cellpadding="0" cellspacing="0">
				<tr>
					<th colspan="2">
						<?php esc_html_e( 'Payment information', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
						<br/>
						<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->cofidispay_banner ); ?>" alt="<?php echo esc_attr( $alt ); ?>" title="<?php echo esc_attr( $alt ); ?>"/>
					</th>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
					<td class="mb_value"><?php echo wc_price( $cofidispay_order_details['val'], array( 'currency' => 'EUR' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
				</tr>
				<tr>
					<td colspan="2" class="extra_instructions">
						<?php esc_html_e( 'Waiting for confirmation from Cofidis Pay', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
					</td>
				</tr>
			</table>
			<?php
			return apply_filters( 'cofidispay_ifthen_thankyou_instructions_table_html', ob_get_clean(), round( $order_total, 2 ), $order_id );
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
			$send = apply_filters( 'cofidispay_ifthen_send_email_instructions', true, $order, $sent_to_admin, $plain_text, $email );
			// Send
			if ( $send ) {
				// Go
				if ( $this->id === $order->get_payment_method() ) {
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
							// Show instructions - We should never get here as we're not allowing to change the status to On hold.
							if ( apply_filters( 'cofidispay_ifthen_email_instructions_pending_send', true, $order->get_id() ) ) {
								echo $this->email_instructions_table_html( $order->get_id(), round( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ), 2 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
						} elseif ( $order->has_status( 'processing' ) || $order->has_status( 'completed' ) ) { // Processing
							if ( apply_filters( 'cofidispay_ifthen_email_instructions_payment_received_send', true, $order->get_id() ) ) {
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
			$alt = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id . '_title', $this->title ) : $this->title );
			// We actually do not use $ent, $ref or $order_total - We'll just get the details
			$cofidispay_order_details = WC_IfthenPay_Webdados()->get_cofidispay_order_details( $order_id );
			$order                    = wc_get_order( $order_id );
			ob_start();
			?>
			<table cellpadding="10" cellspacing="0" align="center" border="0" style="margin: auto; margin-top: 2em; margin-bottom: 2em; border-collapse: collapse; border: 1px solid #1465AA; border-radius: 4px !important; background-color: #FFFFFF;">
				<tr>
					<td style="border: 1px solid #1465AA; border-top-right-radius: 4px !important; border-top-left-radius: 4px !important; text-align: center; color: #000000; font-weight: bold;" colspan="2">
						<?php esc_html_e( 'Payment instructions', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
						<br/>
						<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->cofidispay_banner_email ); ?>" alt="<?php echo esc_attr( $alt ); ?>" title="<?php echo esc_attr( $alt ); ?>" style="margin-top: 10px; max-height: 48px"/>
					</td>
				</tr>
				<tr>
					<td style="border-top: 1px solid #1465AA; color: #000000;"><?php esc_html_e( 'Information', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
					<td style="border-top: 1px solid #1465AA; color: #000000; white-space: nowrap; text-align: right;"><?php echo esc_html( apply_filters( 'cofidispay_ifthen_webservice_desc', get_bloginfo( 'name' ) . ' #' . $order->get_order_number(), $order_id ) ); ?></td>
				</tr>
				<tr>
					<td style="border-top: 1px solid #1465AA; color: #000000;"><?php esc_html_e( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
					<td style="border-top: 1px solid #1465AA; color: #000000; white-space: nowrap; text-align: right;"><?php echo wc_price( $cofidispay_order_details['val'], array( 'currency' => 'EUR' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
				</tr>
				<tr>
					<td style="font-size: x-small; border: 1px solid #1465AA; border-bottom-right-radius: 4px !important; border-bottom-left-radius: 4px !important; color: #000000; text-align: center;" colspan="2">
						<?php esc_html_e( 'Waiting for confirmation from Cofidis Pay', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
					</td>
				</tr>
			</table>
			<?php
			return apply_filters( 'cofidispay_ifthen_email_instructions_table_html', ob_get_clean(), round( $order_total, 2 ), $order_id );
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
				<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->cofidispay_banner_email ); ?>" alt="<?php echo esc_attr( $alt ); ?>" title="<?php echo esc_attr( $alt ); ?>" style="margin: auto; margin-top: 10px; max-height: 48px;"/>
				<br/>
				<strong><?php esc_html_e( 'Cofidis Pay payment pre-approved.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong>
				<br/>
				<?php esc_html_e( 'We will process your order after submitting the payment request to Cofidis, and get it definitely approved.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
			</p>
			<?php
			return apply_filters( 'cofidispay_ifthen_email_instructions_payment_received', ob_get_clean(), $order_id );
		}

		/**
		 * API Init Payment
		 *
		 * @param integer $order_id The Order ID.
		 * @return url or false
		 */
		private function api_init_payment( $order_id ) {
			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$id                = $order_id;
			$order             = wc_get_order( $order_id );
			$valor             = WC_IfthenPay_Webdados()->get_order_total_to_pay_for_gateway( $order );
			$cofidispaykey     = apply_filters( 'multibanco_ifthen_base_cofidispaykey', $this->cofidispaykey, $order );
			$wd_secret         = substr( strrev( md5( time() ) ), 0, 10 ); // Set a secret on our end for extra validation
			$id_for_backoffice = apply_filters( 'ifthen_webservice_send_order_number_instead_id', false ) ? $order->get_order_number() : $order->get_id();
			$desc              = trim( get_bloginfo( 'name' ) );
			$desc              = substr( $desc, 0, COFIDISPAY_IFTHEN_DESC_LEN - strlen( ' #' . $order->get_order_number() ) );
			$desc             .= ' #' . $order->get_order_number();
			$url               = $this->api_url . $cofidispaykey;
			$return_url        = WC_IfthenPay_Webdados()->cofidispay_return_url;
			$return_url        = add_query_arg( 'id', $id_for_backoffice, $return_url );
			$return_url        = add_query_arg( 'wd_secret', $wd_secret, $return_url );
			$return_url        = add_query_arg( 'amount', $valor, $return_url );
			$args              = array(
				'method'   => 'POST',
				'timeout'  => apply_filters( 'cofidispay_ifthen_api_timeout', 15 ),
				'blocking' => true,
				'body'     => array(
					'orderId'         => (string) $id_for_backoffice,
					'amount'          => (string) $valor,
					'description'     => WC_IfthenPay_Webdados()->mb_webservice_filter_descricao( apply_filters( 'cofidispay_ifthen_webservice_desc', $desc, $order->get_id() ) ),
					'returnUrl'       => $return_url,
					'customerName'    => trim( $order->get_formatted_billing_full_name() ),
					'customerVat'     => apply_filters( 'cofidispay_ifthen_customer_vat', '', $order ), // Add to PRO add-on
					'customerEmail'   => trim( $order->get_billing_email() ),
					'customerPhone'   => trim( $order->get_billing_phone() ),
					'billingAddress'  => trim( trim( $order->get_billing_address_1() ) . ' ' . trim( $order->get_billing_address_2() ) ),
					'billingZipCode'  => trim( $order->get_billing_postcode() ),
					'billingCity'     => trim( $order->get_billing_city() ),
					'deliveryAddress' => trim( trim( $order->get_shipping_address_1() ) . ' ' . trim( $order->get_shipping_address_2() ) ),
					'deliveryZipCode' => trim( $order->get_shipping_postcode() ),
					'deliveryCity'    => trim( $order->get_shipping_city() ),
				),
			);
			$this->debug_log_extra( '- Request payment with args: ' . wp_json_encode( $args ) );
			$args['body'] = wp_json_encode( $args['body'] ); // Json not post variables
			$response     = wp_remote_post( $url, $args );
			if ( is_wp_error( $response ) ) {
				$debug_msg       = '- Error contacting the ifthenpay servers - Order ' . $order->get_id() . ' - ' . $response->get_error_message();
				$debug_msg_email = $debug_msg . ' - Args: ' . wp_json_encode( $args ) . ' - Response: ' . wp_json_encode( $response );
				$this->debug_log( $debug_msg, 'error', true, $debug_msg_email );
				return false;
			} elseif ( isset( $response['response']['code'] ) && intval( $response['response']['code'] ) === 200 && isset( $response['body'] ) && trim( $response['body'] ) !== '' ) {
				$body = json_decode( trim( $response['body'] ) );
				if ( $body ) {
					if ( intval( $body->status ) === 0 ) {
						WC_IfthenPay_Webdados()->set_order_cofidispay_details(
							$order->get_id(),
							array(
								'cofidispaykey' => $cofidispaykey,
								'request_id'    => $body->requestId,
								'id'            => $id_for_backoffice,
								'val'           => $valor,
								'payment_url'   => $body->paymentUrl,
								'wd_secret'     => $wd_secret,
							)
						);
						$this->debug_log( '- Cofidis Pay payment request created on ifthenpay servers - Redirecting to payment gateway - Order ' . $order->get_id() . ' - requestId: ' . $body->requestId );
						do_action( 'cofidispay_ifthen_created_reference', $body->requestId, $order->get_id() );
						return $body->paymentUrl;
					} else {
						$debug_msg = '- Error contacting the ifthenpay servers - Order ' . $order->get_id() . ' - Error code and message: ' . $body->status . ' / ' . $body->Message;
						$this->debug_log( $debug_msg, 'error', true, $debug_msg );
						return false;
					}
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
			// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
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
			do_action( 'cofidispay_ifthen_before_process_payment', $order );
			if ( $order->get_total() > 0 ) {
				$redirect_url = $this->api_init_payment( $order->get_id() );
				if ( $redirect_url ) {
					// Mark pending
					WC_IfthenPay_Webdados()->set_initial_order_status( $order, 'pending', 'Cofidis Pay' );
				} else {
					throw new Exception(
						sprintf(
							/* translators: %s: payment method */
							esc_html__( 'An error occurred processing the %s Payment request - please try again', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
							'Cofidis Pay'
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
				strlen( trim( $this->cofidispaykey ) ) !== 10
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
		 * Callback - Return from payment gateway
		 */
		public function return_payment_gateway() {
			//phpcs:disable WordPress.Security.NonceVerification.Recommended
			$redirect_url = '';
			$error        = false;
			$order_id     = 0;
			$orders_exist = false;

			if (
				isset( $_GET['id'] )
				&&
				isset( $_GET['amount'] )
				&&
				isset( $_GET['wd_secret'] )
			) {
				$this->debug_log( '- Return from payment gateway (' . WC_IfthenPay_Webdados()->get_request_uri() . ') with all arguments' );
				$id        = trim( sanitize_text_field( wp_unslash( $_GET['id'] ) ) );
				$val       = trim( sanitize_text_field( wp_unslash( $_GET['amount'] ) ) ); // Não fazemos float porque 7.40 passaria a 7.4 e depois não validava a hash
				$wd_secret = trim( sanitize_text_field( wp_unslash( $_GET['wd_secret'] ) ) );
				$get_order = $this->callback_helper_get_pending_order( $id, $val, $wd_secret );
				$success   = isset( $_GET['Success'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['Success'] ) ) ) : '';
				switch ( $success ) {

					case 'True':
						if ( $get_order['success'] && $get_order['order'] ) {
							$order = $get_order['order'];
							$this->debug_log_extra( 'Order found: ' . $order->get_id() . ' - Status: ' . $order->get_status() );
							$order_id      = $order->get_id();
							$order_details = WC_IfthenPay_Webdados()->get_cofidispay_order_details( $order->get_id() );
							$note          = __( 'Cofidis Pay payment pre-approval received.', 'multibanco-ifthen-software-gateway-for-woocommerce' );
							$url           = $this->get_return_url( $order );
							do_action( 'cofidispay_ifthen_return_payment_gateway_complete', $order->get_id(), $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							$debug_order = wc_get_order( $order->get_id() );
							$this->debug_log( '-- Cofidis Pay payment pre-approval received - Order ' . $order->get_id(), 'notice' );
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
				$error = 'Return from payment gateway (' . WC_IfthenPay_Webdados()->get_request_uri() . ') with missing arguments';
			}

			// Error and redirect
			if ( $error ) {
				$order->update_meta_data( '_' . $this->id . '_checkouterror', $error ); // To show error on blocks checkout
				$order->save();
				$this->debug_log( '- ' . $error, 'warning', true, $error );
				do_action( 'cofidispay_ifthen_callback_payment_failed', $order_id, $error, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( $redirect_url ) { // What if we don't have a redirect?
					wp_safe_redirect( $redirect_url );
				}
				exit;
			}
			//phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Callback - ifthenpay callback
		 */
		public function callback() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			@ob_clean(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			// We must 1st check the situation and then process it and send email to the store owner in case of error.
			if (
				isset( $_GET['key'] )
				&&
				isset( $_GET['orderId'] )
				&&
				isset( $_GET['amount'] )
				&&
				isset( $_GET['requestId'] )
			) {
				// Let's process it
				$this->debug_log( '- Callback (' . WC_IfthenPay_Webdados()->get_request_uri() . ') with all arguments from ' . WC_IfthenPay_Webdados()->get_remote_addr() );
				$request_id      = str_replace( ' ', '+', trim( sanitize_text_field( wp_unslash( $_GET['requestId'] ) ) ) ); // If there's a plus sign on the URL We'll get it as a space, so we need to get it back
				$id              = trim( sanitize_text_field( wp_unslash( $_GET['orderId'] ) ) );
				$val             = floatval( $_GET['amount'] );
				$key             = trim( sanitize_text_field( wp_unslash( $_GET['key'] ) ) );
				$datahorapag     = isset( $_GET['datahorapag'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['datahorapag'] ) ) ) : '';
				$arguments_ok    = true;
				$arguments_error = '';
				if ( $key !== trim( $this->secret_key ) ) {
					$arguments_ok     = false;
					$arguments_error .= ' - Key';
				}
				if ( trim( $request_id ) === '' ) {
					$arguments_ok     = false;
					$arguments_error .= ' - IdPedido';
				}
				if ( abs( $val ) < WC_IfthenPay_Webdados()->cofidispay_min_value ) {
					$arguments_ok     = false;
					$arguments_error .= ' - Value';
				}
				if ( $arguments_ok ) { // Isto deve ser separado em vários IFs para melhor se identificar o erro
					// Payments
					$orders_exist   = false;
					$pending_status = apply_filters( 'cofidispay_ifthen_valid_callback_pending_status', WC_IfthenPay_Webdados()->unpaid_statuses ); // Double filter - Should we deprectate this one?
					$args           = array(
						'type'                          => array( 'shop_order' ), // Regular order
						'status'                        => $pending_status,
						'limit'                         => -1,
						'_' . $this->id . '_request_id' => $request_id,
						'_' . $this->id . '_id'         => $id,
					);
					$orders         = wc_get_orders( WC_IfthenPay_Webdados()->maybe_translate_order_query_args( $args ) );
					if ( count( $orders ) > 0 ) {
						$orders_exist = true;
						$orders_count = count( $orders );
						foreach ( $orders as $order ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedForeach
							// Just getting the last one
						}
					} else {
						$err = 'Error: No orders found awaiting payment with these details';
						$this->debug_log( '-- ' . $err, 'warning', true, 'Callback (' . WC_IfthenPay_Webdados()->get_http_host() . ' ' . WC_IfthenPay_Webdados()->get_request_uri() . ') from ' . WC_IfthenPay_Webdados()->get_remote_addr() );
					}
					if ( $orders_exist ) {
						if ( $orders_count === 1 ) {
							if ( floatval( $val ) === floatval( WC_IfthenPay_Webdados()->get_order_total_to_pay( $order ) ) ) {
								$note = __( 'Cofidis Pay payment approval received.', 'multibanco-ifthen-software-gateway-for-woocommerce' );
								if ( ! empty( $datahorapag ) ) {
									$note .= ' ' . $datahorapag;
								}
								$this->payment_complete( $order, '', $note );
								do_action( 'cofidispay_ifthen_callback_payment_complete', $order->get_id(), $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
								header( 'HTTP/1.1 200 OK' );
								$this->debug_log( '-- Cofidis Pay payment approval received - Order ' . $order->get_id(), 'notice' );
								echo 'OK - Cofidis Pay payment approval received';
							} else {
								header( 'HTTP/1.1 200 OK' );
								$err = 'Error: The value does not match';
								$this->debug_log( '-- ' . $err . ' - Order ' . $order->get_id(), 'warning', true, 'Callback (' . WC_IfthenPay_Webdados()->get_http_host() . ' ' . WC_IfthenPay_Webdados()->get_request_uri() . ') from ' . WC_IfthenPay_Webdados()->get_remote_addr() . ' - The value does not match' );
								echo esc_html( $err );
								do_action( 'cofidispay_ifthen_callback_payment_failed', $order->get_id(), $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							}
						} else {
							header( 'HTTP/1.1 200 OK' );
							$err = 'Error: More than 1 order found awaiting payment with these details';
							$this->debug_log( '-- ' . $err, 'warning', true, 'Callback (' . WC_IfthenPay_Webdados()->get_http_host() . ' ' . WC_IfthenPay_Webdados()->get_request_uri() . ') from ' . WC_IfthenPay_Webdados()->get_remote_addr() . ' - More than 1 order found awaiting payment with these details' );
							echo esc_html( $err );
							do_action( 'cofidispay_ifthen_callback_payment_failed', 0, $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						}
					} else {
						header( 'HTTP/1.1 200 OK' );
						$err = 'Error: No orders found awaiting payment with these details';
						$this->debug_log( '-- ' . $err, 'warning', true, 'Callback (' . WC_IfthenPay_Webdados()->get_http_host() . ' ' . WC_IfthenPay_Webdados()->get_request_uri() . ') from ' . WC_IfthenPay_Webdados()->get_remote_addr() . ' - No orders found awaiting payment with these details' );
						echo esc_html( $err );
						do_action( 'cofidispay_ifthen_callback_payment_failed', 0, $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					}
				} else {
					$this->debug_log( '-- ' . $err . $arguments_error, 'warning', true, 'Callback (' . WC_IfthenPay_Webdados()->get_http_host() . ' ' . WC_IfthenPay_Webdados()->get_request_uri() . ') with argument errors from ' . WC_IfthenPay_Webdados()->get_remote_addr() . $arguments_error );
					do_action( 'cofidispay_ifthen_callback_payment_failed', 0, $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					wp_die( esc_html( $err ), 'WC_CofidisPay_IfThen_Webdados', array( 'response' => 500 ) ); // Sends 500
				}
			} else {
				$err = 'Callback (' . WC_IfthenPay_Webdados()->get_request_uri() . ') with missing arguments from ' . WC_IfthenPay_Webdados()->get_remote_addr();
				$this->debug_log( '- ' . $err, 'warning', true, 'Callback (' . WC_IfthenPay_Webdados()->get_http_host() . ' ' . WC_IfthenPay_Webdados()->get_request_uri() . ') with missing arguments from ' . WC_IfthenPay_Webdados()->get_remote_addr() );
				do_action( 'cofidispay_ifthen_callback_payment_failed', 0, $err, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wp_die( 'Error: Something is missing...', 'WC_CofidisPay_IfThen_Webdados', array( 'response' => 500 ) ); // Sends 500
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
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
			$pending_status = apply_filters( 'cofidispay_ifthen_valid_callback_pending_status', WC_IfthenPay_Webdados()->unpaid_statuses ); // Double filter - Should we deprectate this one?
			$args           = array(
				'type'                         => array( 'shop_order' ), // Regular order
				'status'                       => $pending_status,
				'limit'                        => -1,
				'_' . $this->id . '_wd_secret' => $wd_secret,
				'_' . $this->id . '_id'        => $id,
			);
			$orders_exist   = false;
			$orders         = WC_IfthenPay_Webdados()->wc_get_orders( $args, $this->id );
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
			// Callback email
			if (
				trim( $this->enabled ) === 'yes'
				&&
				strlen( trim( $this->cofidispaykey ) ) === 10
				&&
				trim( $this->secret_key ) !== ''
			) {
				$callback_email_sent = get_option( $this->id . '_callback_email_sent' );
				if ( $callback_email_sent === 'no' || $callback_email_sent === false ) {
					if ( ! isset( $_GET['callback_warning'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						if ( apply_filters( 'cofidispay_ifthen_show_callback_notice', true ) ) {
							?>
							<div id="cofidispay_ifthen_callback_notice" class="notice notice-error" style="padding-right: 38px; position: relative;">
								<p>
									<strong>Cofidis Pay (ifthenpay)</strong>
									<br/>
									<?php esc_html_e( 'You haven’t yet asked ifthenpay for the “Callback” activation. The orders will NOT be automatically updated upon payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
									<br/>
									<strong><?php esc_html_e( 'This is important', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>! <a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=cofidispay_ifthen_for_woocommerce&amp;callback_warning=1"><?php esc_html_e( 'Do it here', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></a>!</strong>
								</p>
							</div>
							<?php
						}
					}
				}
			}
			// New method
			if (
				// Only show to users who can manage the shop
				current_user_can( 'manage_woocommerce' )
				&&
				// Key not set or method not enabled
				(
					strlen( trim( $this->cofidispaykey ) ) !== 10
					||
					trim( $this->enabled ) !== 'yes'
				)
				&&
				// Not prevented by filter
				( ! apply_filters( 'multibanco_ifthen_hide_newmethod_notifications', false ) )
				&&
				// Check if 90-day dismissal is active
				( ! get_transient( $this->id . '_newmethod_notice_dismiss_' . get_current_user_id() ) )
			) {
				?>
				<div id="<?php echo esc_attr( $this->id ); ?>_newmethod_notice" class="notice notice-info is-dismissible" style="padding-right: 38px; position: relative;">
					<img src="<?php echo esc_url( WC_IfthenPay_Webdados()->cofidispay_banner ); ?>" style="float: left; margin-top: 0.5em; margin-bottom: 0.5em; margin-right: 1em; max-height: 48px; max-width: 200px;"/>
					<p>
						<?php
							echo wp_kses_post(
								sprintf(
									/* translators: %s: payment method */
									__( 'There’s a new payment method available: %s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									'<strong>Cofidis Pay (ifthenpay)</strong>'
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
