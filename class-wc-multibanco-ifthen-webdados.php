<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Multibanco IfThen Class.
 *
 */
if ( ! class_exists( 'WC_Multibanco_IfThen_Webdados' ) ) {

	class WC_Multibanco_IfThen_Webdados extends WC_Payment_Gateway {

		/* Single instance */
		protected static $_instance = null;
		public static $instances = 0;
		
		/**
		 * Constructor for your payment class
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {

			self::$instances++;

			$this->id = WC_IfthenPay_Webdados()->multibanco_id;
	
			// Logs
			$this->debug = ( $this->get_option( 'debug' )=='yes' ? true : false );
			$this->debug_email = $this->get_option( 'debug_email' );
			
			//Check version and upgrade
			$this->version = WC_IfthenPay_Webdados()->version;
			$this->upgrade();

			$this->has_fields = false;

			$this->method_title = __( 'Pagamento de Serviços no Multibanco (IfthenPay)', 'multibanco-ifthen-software-gateway-for-woocommerce' );
			$this->method_description = __( 'Easy and simple payment using “Pagamento de Serviços” at any “Multibanco” ATM terminal or your Home Banking service. (Only available to customers of Portuguese banks. Payment service provided by IfthenPay.)', 'multibanco-ifthen-software-gateway-for-woocommerce' );
			if ( $this->get_option( 'support_woocommerce_subscriptions' ) == 'yes' ) {
				$this->supports = array(
					'products',
					'subscriptions',
					'subscription_suspension',
					'subscription_reactivation',
					'subscription_date_changes',
					'subscription_payment_method_change_admin'
				); //products is by default
			}
			$this->secret_key = $this->get_option( 'secret_key' );
			if ( trim( $this->secret_key )=='' ) {
				//First load?
				$this->secret_key = md5( home_url().time().rand(0,999) );
				$this->update_option( 'secret_key', $this->secret_key );
				//Let's set the callback activation email as NOT sent
				update_option( $this->id . '_callback_email_sent', 'no' );
			}
	
			//Plugin options and settings
			$this->init_form_fields();
			$this->init_settings();
	
			//User settings
			$this->title = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );
			$this->extra_instructions = $this->get_option( 'extra_instructions' );
			$this->ent = $this->get_option( 'ent' );
			$this->subent = $this->get_option( 'subent' );
			$this->settings_saved = $this->get_option( 'settings_saved' );
			$this->send_to_admin = ( $this->get_option( 'send_to_admin' )=='yes' ? true : false );
			$this->only_portugal = ( $this->get_option( 'only_portugal' )=='yes' ? true : false );
			$this->only_above = $this->get_option( 'only_above' );
			$this->only_bellow = $this->get_option( 'only_bellow' );
			$this->stock_when = $this->get_option( 'stock_when' );
	 	
			// Actions and filters
			if ( self::$instances == 1 ) { //Avoid duplicate actions and filters if it's initiated more than once (if WooCommerce loads after us)
				add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array( $this, 'process_admin_options' ) );
				add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array( $this, 'send_callback_email' ) );
				if ( WC_IfthenPay_Webdados()->wpml_active ) add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'register_wpml_strings' ) );
				add_action( 'woocommerce_thankyou_'.$this->id, array( $this, 'thankyou' ) );
				add_action( 'woocommerce_order_details_after_order_table', array( $this, 'order_details_after_order_table' ), 9 );
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_if_currency_not_euro' ) );
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_unless_portugal' ) );
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_only_above_or_bellow' ) );
		
				// APG SMS Notifications Integration
				// https://wordpress.org/plugins/woocommerce-apg-sms-notifications/
				//add_filter( 'apg_sms_message', array( $this, 'sms_instructions_apg' ), 10, 2 ); // (Removed from the class because now APG also sends scheduled SMS and the payment class may not be initiated)
				// Twilio SMS Notifications
				// https://woocommerce.com/products/twilio-sms-notifications/
				add_filter( 'wc_twilio_sms_customer_sms_before_variable_replace', array( $this, 'sms_instructions_twilio' ), 10, 2 );
				// YITH WooCommerce SMS Notifications
				// https://yithemes.com/themes/plugins/yith-woocommerce-sms-notifications/
				add_filter( 'ywsn_sms_placeholders', array( $this, 'sms_instructions_yith' ), 10, 2 );
		 		
				// Customer Emails
				//add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 ); - "Hyyan WooCommerce Polylang Integration" removes this action
				add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions_1' ), 10, 3 ); //Avoid "Hyyan WooCommerce Polylang Integration" remove_action
		
				// Payment listener/API hook
				add_action( 'woocommerce_api_wc_multibanco_ifthen_webdados', array( $this, 'callback' ) );
		
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
				$current_options = get_option( 'woocommerce_'.$this->id.'_settings', '' );
				if ( !is_array($current_options) ) $current_options = array();
				//Upgrade
				$this->debug_log( 'Upgrade to '.$this->version.' started' );
				if ( $this->version == '1.0.1' ) {
					//Only change is to set the version on the database. It's done below
				}
				if ( $this->get_option( 'version' ) < '1.7.9.2' && $this->version >= '1.7.9.2' ) {
					/* THIS SHOULD BE ABSTRACTED FROM POST / POST META - START - Not really because on these versions orders will always be posts */
					/* https://github.com/woocommerce/woocommerce/issues/12677 */
					//Update all order totals
					$args = array(
						'post_type' => 'shop_order',
						'post_status' => array_keys( wc_get_order_statuses() ),
						'posts_per_page' => -1,
						'meta_query' => array(
							array(
								'key'=>'_payment_method',
								'value'=>$this->id,
								'compare'=>'LIKE'
							)
						)
					);
					//This will need to change when the order is no longer a WP post
					$orders = get_posts( $args );
					/* THIS SHOULD BE ABSTRACTED FROM POST / POST META - END - Not really because on these versions orders will always be posts */
					foreach ( $orders as $orderpost ) {
						$order = new WC_Order_MB_Ifthen( $orderpost->ID );
						$order->mb_update_meta_data( '_'.$this->id.'_val', $order->mb_get_total() );
					}
				}
				if ( $this->version >= '3.4.3' ) {
					//Activate the resend new order option by default
					if ( ! isset( $current_options['resend_new_order_when_paid'] ) ) {
						$current_options['resend_new_order_when_paid'] = 'yes';
					}
				}
				//Upgrade on the database - Risky?
				$current_options['version'] = $this->version;
				update_option( 'woocommerce_'.$this->id.'_settings', $current_options );
				$this->debug_log( 'Upgrade to '.$this->version.' finished' );
			}
		}

		/**
		 * WPML compatibility
		 */
		function register_wpml_strings() {
			//These are already registered by WooCommerce Multilingual
			/*$to_register=array(
				'title',
				'description',
			);*/
			$to_register = array(
				'extra_instructions'
			);
			foreach( $to_register as $string ) {
				icl_register_string( $this->id, $this->id.'_'.$string, $this->settings[$string] );
			}
		}

		/**
		 * Initialise Gateway Settings Form Fields
		 * 'setting-name' => array(
		 *		'title' => __( 'Title for setting', 'woothemes' ),
		 *		'type' => 'checkbox|text|textarea',
		 *		'label' => __( 'Label for checkbox setting', 'woothemes' ),
		 *		'description' => __( 'Description for setting' ),
		 *		'default' => 'default value'
		 *	),
		 */
		function init_form_fields() {
		
			$this->form_fields = array(
				'enabled' => array(
								'title' => __( 'Enable/Disable', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
								'type' => 'checkbox', 
								'label' => __( 'Enable “Pagamento de Serviços no Multibanco” (using IfthenPay)', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
								'default' => 'no'
							),
				'ent' => array(
								'title' => __( 'Entity', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
								'type' => 'number',
								'description' => __( 'Entity provided by IfthenPay when signing the contract. (E.g.: 10559, 11202, 11473, 11604)', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
								'default' => '',
								'css' => 'width: 80px;',
								'custom_attributes' => array(
									'maxlength'	=> 5,
									'size' => 5,
									'max' => 99999
								)
							),
				'subent' => array(
								'title' => __( 'Subentity', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
								'type' => 'number', 
								'description' => __( 'Subentity provided by IfthenPay when signing the contract. (E.g.: 999)', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
								'default' => '',
								'css' => 'width: 60px;',
								'custom_attributes' => array(
									'maxlength'	=> 3,
									'size' => 3,
									'max' => 999
								)
							),
			);
			if ( in_array( intval( $this->get_option( 'ent' ) ), WC_IfthenPay_Webdados()->multibanco_ents_no_check_digit ) ) {
				$this->form_fields = array_merge( $this->form_fields, array(
					'use_order_id' => array(
									'title' => __( 'Use order id?', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'type' => 'checkbox', 
									'label' => __( 'Should the reference be generated using the order id instead of a random number?', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'default' => 'no'
								),
				) );
			}
			//if( strlen( $this->get_option( 'ent' ) ) == 5 && trim( strlen( $this->get_option( 'subent' ) ) )<=3 && intval( $this->get_option( 'ent' ) )>0 && intval( $this->get_option( 'subent' ) )>0 && trim( $this->secret_key ) !='' ) {
				$this->form_fields = array_merge( $this->form_fields, array(
					'secret_key' => array(
									'title' => __( 'Anti-phishing key', 'multibanco-ifthen-software-gateway-for-woocommerce' ).' (Multibanco)', 
									'type' => 'hidden', 
									'description' => '<strong id="woocommerce_'.$this->id.'_secret_key_label">'.$this->secret_key.'</strong><br/>'.__( 'To ensure callback security, generated by the system and which must be provided to IfthenPay when asking for the callback activation.', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'default' => $this->secret_key 
								),
					'title' => array(
									'title' => __( 'Title', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'type' => 'text', 
									'description' => __( 'This controls the title which the user sees during checkout.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
													.( WC_IfthenPay_Webdados()->wpml_active ? ' '.__( 'You should translate this string in <a href="admin.php?page=wpml-string-translation%2Fmenu%2Fstring-translation.php">WPML - String Translation</a> after saving the settings', 'multibanco-ifthen-software-gateway-for-woocommerce' ) : '' ), 
									'default' => 'Multibanco'
								),
					'description' => array(
									'title' => __( 'Description', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'type' => 'textarea',
									'description' => __( 'This controls the description which the user sees during checkout.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
													.( WC_IfthenPay_Webdados()->wpml_active ? ' '.__( 'You should translate this string in <a href="admin.php?page=wpml-string-translation%2Fmenu%2Fstring-translation.php">WPML - String Translation</a> after saving the settings', 'multibanco-ifthen-software-gateway-for-woocommerce' ) : '' ), 
									'default' => version_compare( WC_VERSION, '2.6', '>=' ) ? $this->get_method_description() : $this->method_description
								),
					'small_icon' => array(
									'title' => __( 'Use small icon?', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'type' => 'checkbox', 
									'label' => __( 'Use a small Multibanco icon on the checkout', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'default' => 'yes'
								),
					'extra_instructions' => array(
									'title' => __( 'Extra instructions', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'type' => 'textarea',
									'description' => __( 'This controls the text which the user sees below the payment details on the “Thank you” page and “New order” email.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
													.( WC_IfthenPay_Webdados()->wpml_active ? ' '.__( 'You should translate this string in <a href="admin.php?page=wpml-string-translation%2Fmenu%2Fstring-translation.php">WPML - String Translation</a> after saving the settings', 'multibanco-ifthen-software-gateway-for-woocommerce' ) : '' ), 
									'default' => __( 'The receipt issued by the ATM machine is a proof of payment. Keep it.', 'multibanco-ifthen-software-gateway-for-woocommerce' )
								),
					'only_portugal' => array(
									'title' => __( 'Only for Portuguese customers?', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'type' => 'checkbox', 
									'label' => __( 'Enable only for customers whose address is in Portugal', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'default' => 'no'
								),
					'only_above' => array(
									'title' => __( 'Only for orders above', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'type' => 'number', 
									'description' => __( 'Enable only for orders above x &euro;. Leave blank (or zero) to allow for any order value.', 'multibanco-ifthen-software-gateway-for-woocommerce' ).' <br/> '.sprintf(
										__( 'By design, %1$s only allows payments from %2$s to %3$s (inclusive). You can use this option to further limit this range.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
										'Multibanco',
										wc_price( WC_IfthenPay_Webdados()->multibanco_min_value, array( 'currency' => 'EUR' ) ),
										wc_price( WC_IfthenPay_Webdados()->multibanco_max_value, array( 'currency' => 'EUR' ) )
									), 
									'default' => ''
								),
					'only_bellow' => array(
									'title' => __( 'Only for orders below', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'type' => 'number', 
									'description' => __( 'Enable only for orders below x &euro; (exclusive). Leave blank (or zero) to allow for any order value.', 'multibanco-ifthen-software-gateway-for-woocommerce' ).' <br/> '.sprintf(
										__( 'By design, %1$s only allows payments from %2$s to %3$s (inclusive). You can use this option to further limit this range.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
										'Multibanco',
										wc_price( WC_IfthenPay_Webdados()->multibanco_min_value, array( 'currency' => 'EUR' ) ),
										wc_price( WC_IfthenPay_Webdados()->multibanco_max_value, array( 'currency' => 'EUR' ) )
									), 
									'default' => ''
								),
					'stock_when' => array(
									'title' => __( 'Reduce stock', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'type' => 'select',
									'description' => __( 'Choose when to reduce stock.', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'default' => '',
									'options'	=> array(
										'order'	=> __( 'when order is placed (before payment, WooCommerce default)', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
										''		=> __( 'when order is paid (requires active callback)', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									),
								),
					'resend_new_order_when_paid' => array(
									'title' => __( 'Notify store owner of payment', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'type' => 'checkbox', 
									'label' => __( 'Force resending the “New order” email to the store owner upon payment', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									'description' => sprintf(
										__( 'If the %1$s“New order” email notification%2$s is active', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
										'<a href="admin.php?page=wc-settings&amp;tab=email&section=wc_email_new_order" target="_blank">',
										'</a>'
									),
									'default' => 'yes'
								),
					'support_woocommerce_subscriptions' => array(
									'title' => __( 'WooCommerce Subscriptions', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'type' => 'checkbox',
									'label' => __( 'Enable WooCommerce Subscriptions (experimental) support.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									'description' => __( 'Shows “Pagamento de Serviços no Multibanco” (using IfthenPay) as a supported payment gateway, and automatically sets subscription renewal orders to be paid with Multibanco if the original subscription used this payment method. If this option is not activated, Multibanco will only be available as a payment method for subscriptions if the “Manual Renewal Payments” option is enabled on WooCommerce Subscriptions settings.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									'default' => 'no'
								),
					'send_to_admin' => array(
									'title' => __( 'Send instructions to admin?', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'type' => 'checkbox', 
									'label' => __( 'Should the payment details also be sent to admin?', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'default' => 'yes'
								),
					'update_ref_client' => array(
									'title' => __( 'Email reference update to client?', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'type' => 'checkbox', 
									'label' => __( 'If the payment details change because of an update on the backend, should the client be notified?', 'multibanco-ifthen-software-gateway-for-woocommerce' ), 
									'default' => 'no'
								),
					'debug' => array(
									'title' => __( 'Debug Log', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									'type' => 'checkbox',
									'label' => __( 'Enable logging', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									'default' => 'no',
									'description' => sprintf(
														__( 'Log plugin events, such as callback requests, in %s', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
														( version_compare( WC_VERSION, '3.0', '>=' ) && defined( 'WC_LOG_HANDLER' ) && 'WC_Log_Handler_DB' === WC_LOG_HANDLER )
														?
														'<a href="admin.php?page=wc-status&tab=logs&source='.esc_attr( $this->id ).'" target="_blank">'.__( 'WooCommerce &gt; Status &gt; Logs', 'multibanco-ifthen-software-gateway-for-woocommerce' ).'</a>'
														:
														'<code>'.wc_get_log_file_path( $this->id ).'</code>'
													),
								),
					'debug_email' => array(
									'title' => __( 'Debug to email', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									'type' => 'email',
									'label' => __( 'Enable email logging', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
									'default' => '',
									'description' => __( 'Send plugin events to this email address, such as callback requests.', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
								)
				)	);
			//}
			$this->form_fields = array_merge( $this->form_fields , array(
				'settings_saved' => array(
								'title' => '', 
								'type' => 'hidden',
								'default' => 0
							),
			) );

			//Allow other plugins to add settings fields
			$this->form_fields = array_merge( $this->form_fields , apply_filters( 'multibanco_ifthen_multibanco_settings_fields', array( ) ) );
		
		}
		public function admin_options() {
			$title = esc_html( version_compare( WC_VERSION, '2.6', '>=' ) ? $this->get_method_title() : $this->method_title );
			?>
			<div id="wc_ifthen">
				<?php if ( ! apply_filters( 'multibanco_ifthen_hide_settings_right_bar', false ) ) WC_IfthenPay_Webdados()->admin_right_bar(); ?>
				<div id="wc_ifthen_settings">
					<h2>
						<img src="<?php echo plugins_url( 'images/banner_multibanco.png', __FILE__ ); ?>" alt="<?php echo esc_attr( $title ); ?>"/>
						<br/>
						<?php echo $title; ?>
						<small>v.<?php echo $this->version; ?></small>
						<?php if ( function_exists('wc_back_link') ) echo wc_back_link( __( 'Return to payments', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ); ?>
					</h2>
					<?php echo wp_kses_post( wpautop( version_compare( WC_VERSION, '2.6', '>=' ) ? $this->get_method_description() : $this->method_description ) ); ?>
					<p><strong><?php _e( 'In order to use this plugin you <u>must</u>:', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong></p>
					<ul class="wc_ifthen_list">
						<li><?php printf( __( 'Set WooCommerce currency to <strong>Euros (&euro;)</strong> %1$s', 'multibanco-ifthen-software-gateway-for-woocommerce' ), '<a href="admin.php?page=wc-settings&amp;tab=general">&gt;&gt;</a>.' ); ?></li>
						<li><?php printf( __( 'Sign a contract with %1$s. To know more about this service, please go to %2$s.', 'multibanco-ifthen-software-gateway-for-woocommerce' ), '<strong><a href="https://ifthenpay.com/'.esc_attr( WC_IfthenPay_Webdados()->out_link_utm ).'" target="_blank">IfthenPay</a></strong>', '<a href="https://ifthenpay.com/'.esc_attr( WC_IfthenPay_Webdados()->out_link_utm ).'" target="_blank">https://ifthenpay.com</a>' ); ?></li>
						<li><?php _e( 'Fill out all the details (Entity and Subentity) provided by <strong>IfthenPay</strong> in the fields below.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
						<li class="mb_hide_extra_fields"><?php printf( __( 'Ask IfthenPay to activate “Multibanco Callback” on your account using this exact URL: %1$s and this Anti-phishing key: %2$s', 'multibanco-ifthen-software-gateway-for-woocommerce' ), '<br/><code><strong>'.WC_IfthenPay_Webdados()->multibanco_notify_url.'</strong></code><br/>', '<br/><code><strong>'.$this->secret_key.'</strong></code>' ); ?></li>
					</ul>
					<?php
					$hide_extra_fields=false;
					if(
						trim( strlen( $this->ent ) ) == 5
						&&
						trim( strlen( $this->subent ) ) <= 3
						&&
						intval( $this->ent ) > 0
						&&
						intval( $this->subent ) > 0
						&&
						trim( $this->secret_key ) != ''
					) {
						if ( $callback_email_sent = get_option( $this->id . '_callback_email_sent' ) ) { //No notice for older versions
							if ( $callback_email_sent == 'no' ) {
								if ( !isset( $_GET['callback_warning'] ) ) {
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
							<p><?php _e( 'This will submit a request to IfthenPay, asking them to activate the “Callback” on your account. If you have already asked for the “Callback” activation, wait for their feedback before submiting a new request. The following details will be sent via email to IfthenPay (with CC to your email address):', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></p>
							<table class="form-table">
								<tr valign="top">
									<th scope="row" class="titledesc"><?php _e( 'Email', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></th>
									<td class="forminp">
										<?php echo get_option( 'admin_email' ); ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc"><?php _e( 'Entity', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?> / <?php _e( 'Subentity', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></th>
									<td class="forminp">
										<?php echo $this->ent; ?> / <?php echo $this->subent; ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc"><?php _e( 'Anti-phishing key', 'multibanco-ifthen-software-gateway-for-woocommerce' ).' (Multibanco)'; ?></th>
									<td class="forminp">
										<?php echo $this->secret_key; ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc"><?php _e( 'Callback URL', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></th>
									<td class="forminp">
										<?php echo WC_IfthenPay_Webdados()->multibanco_notify_url; ?>
									</td>
								</tr>
							</table>
							<p style="text-align: center;">
								<strong><?php _e( 'Attention: if you ever change from HTTP to HTTPS or vice versa, or the permalinks structure,<br/>you may have to ask IfthenPay to update the callback URL.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong>
							</p>
							<p style="text-align: center; margin-bottom: 0px;">
								<input type="hidden" id="wc_ifthen_callback_send" name="wc_ifthen_callback_send" value="0"/>
								<button id="wc_ifthen_callback_submit" class="button-primary" type="button" value="<?php _e( 'Ask for Callback activation', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>"><?php _e( 'Ask for Callback activation', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></button>
								<input id="wc_ifthen_callback_cancel" class="button" type="button" value="<?php _e( 'Cancel', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>"/>
								<input type="hidden" name="save" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"/> <!-- Force action woocommerce_update_options_payment_gateways_ to run, from WooCommerce 3.5.5 -->
							</p>
						</div>
						<?php
					} else {
						$hide_extra_fields = true;
						if ( $this->settings_saved == 1 ) {
							?>
							<div id="message" class="error">
								<p><strong><?php _e( 'Invalid Entity (exactly 5 numeric characters) and/or Subentity (1 to 3 numeric characters).', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong></p>
							</div>
							<?php
						} else {
							?>
							<div id="message" class="error">
								<p><strong><?php _e( 'Set the Entity/Subentity and Save changes to set other plugin options.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong></p>
							</div>
							<?php
						}
					}
					?>
					<hr/>
					<?php
					if ( trim( get_woocommerce_currency() ) == 'EUR' ) {
						?>
						<table class="form-table">
							<?php if ( WC_IfthenPay_Webdados()->get_multibanco_ref_mode() == 'incremental_expire' ) { ?>
								<tr valign="top">
									<th>
										<label><?php _e( 'Mode', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></label>
									</th>
									<td>
										<?php printf(
											__( 'Incremental references with expiration date (%d days)', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
											intval( apply_filters( 'multibanco_ifthen_incremental_expire_days', 0 ) )
										); ?>
									</td>
								</tr>
							<?php } ?>
							<?php $this->generate_settings_html(); ?>
						</table>
						<?php
					} else {
						?>
						<p><strong><?php _e( 'ERROR!', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?> <?php printf( __( 'Set WooCommerce currency to <strong>Euros (&euro;)</strong> %1$s', 'multibanco-ifthen-software-gateway-for-woocommerce' ), '<a href="admin.php?page=wc-settings&amp;tab=general">'.__( 'here', 'multibanco-ifthen-software-gateway-for-woocommerce' ).'</a>.' ); ?></strong></p>
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
			if ( isset( $_POST['wc_ifthen_callback_send'] ) && intval( $_POST['wc_ifthen_callback_send'] ) == 1 ) {
				$to = WC_IfthenPay_Webdados()->multibanco_callback_email;
				$cc = get_option( 'admin_email' );
				$subject = 'Activação de Callback Multibanco (Ent.: '.$this->ent.' Subent.: '.$this->subent.')';
				$message = 'Por favor activar Callback Multibanco com os seguintes dados:

Entidade:
'.$this->ent.'

Sub-entidade:
'.$this->subent.'

Chave anti-phishing (Multibanco):
'.$this->secret_key.'

URL:
'.WC_IfthenPay_Webdados()->multibanco_notify_url.'

Email enviado automaticamente do plugin WordPress “Multibanco, MBWAY and Payshop (IfthenPay) for WooCommerce” para '.$to.' com CC para '.$cc;
				$headers = array(
					'From: '.get_option( 'admin_email' ).' <'.get_option( 'admin_email' ).'>',
					'Cc: '.$cc
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
		public function get_icon_url() {
			return ( $this->get_option( 'small_icon' ) == 'yes' ? plugins_url( 'images/icon_multibanco_48_small.png', __FILE__ ) : plugins_url( 'images/icon_multibanco_48.png', __FILE__ ) );
		}
		public function get_icon() {
			$alt = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id.'_title', $this->title ) : $this->title );
			$icon_html = ( $this->get_option( 'small_icon' ) == 'yes' ? '<img src="'.esc_attr( $this->get_icon_url() ).'" alt="'.esc_attr( $alt ).'" width="25" height="24"/>' : '<img src="'.esc_attr( $this->get_icon_url() ).'" alt="'.esc_attr( $alt ).'" width="114" height="24"/>' );
			return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
		}

		/**
		 * Thank you page
		 */
		function thankyou( $order_id ) {
			if ( is_object( $order_id ) ) {
				$order_id = version_compare( WC_VERSION, '3.0', '>=' ) ? $order_id->get_id() : $order_id->id;
			}
			$order = new WC_Order_MB_Ifthen( $order_id );
			if ( $this->id === $order->mb_get_payment_method() ) {
				if ( $order->mb_has_status( 'on-hold' ) || $order->mb_has_status( 'pending' ) ) {
					$ref = WC_IfthenPay_Webdados()->multibanco_get_ref( $order_id );
					if ( is_array( $ref ) ) {
						echo $this->thankyou_instructions_table_html( $ref['ent'], $ref['ref'], $order->mb_get_total(), $order_id );
					} else {
						?>
						<p><strong><?php _e( 'Error getting Multibanco payment details', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>.</strong></p>
						<?php
						if ( is_string( $ref ) ) {
							?>
							<p><?php echo $ref; ?></p>
							<?php
						}
					}
				} else {
					//Processing - Not needed
					//if ( $order->mb_has_status( 'processing' ) && !is_wc_endpoint_url( 'view-order') ) {
					//	echo $this->email_instructions_payment_received( $order_id );
					//}
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
				}
				table.<?php echo $this->id; ?>_table td.extra_instructions {
					font-size: small;
					white-space: normal;
				}
			</style>
			<?php
			return ob_get_clean();
		}
		function thankyou_instructions_table_html( $ent, $ref, $order_total, $order_id ) {
			$alt = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id.'_title', $this->title ) : $this->title );
			$extra_instructions = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id.'_extra_instructions', $this->extra_instructions ) : $this->extra_instructions );
			//We actually do not use $ent, $ref or $order_total - We'll just get the details
			$multibanco_order_details = WC_IfthenPay_Webdados()->get_multibanco_order_details( $order_id );
			ob_start();
			echo $this->thankyou_instructions_table_html_css();
			?>
			<table class="<?php echo $this->id; ?>_table" cellpadding="0" cellspacing="0">
				<tr>
					<th colspan="2">
						<?php _e( 'Payment instructions', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
						<br/>
						<img src="<?php echo plugins_url( 'images/banner_multibanco.png', __FILE__ ); ?>" alt="<?php echo esc_attr( $alt); ?>" title="<?php echo esc_attr( $alt); ?>"/>
					</th>
				</tr>
				<tr>
					<td><?php _e( 'Entity', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
					<td class="mb_value"><?php echo $multibanco_order_details['ent']; ?></td>
				</tr>
				<tr>
					<td><?php _e( 'Reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
					<td class="mb_value"><?php echo WC_IfthenPay_Webdados()->format_multibanco_ref( $multibanco_order_details['ref'] ); ?></td>
				</tr>
				<tr>
					<td><?php _e( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
					<td class="mb_value"><?php echo wc_price( $multibanco_order_details['val'], array( 'currency' => 'EUR' ) ); ?></td>
				</tr>
				<?php
				if ( isset( $multibanco_order_details['exp'] ) && trim( $multibanco_order_details['exp'] ) != '' ) {
					?>
					<tr>
						<td><?php _e( 'Expiration', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
						<td class="mb_value"><?php echo WC_IfthenPay_Webdados()->multibanco_format_expiration( $multibanco_order_details['exp'], $order_id ); ?></td>
					</tr>
					<?php
				}
				?>
				<tr>
					<td colspan="2" class="extra_instructions"><?php echo nl2br( $extra_instructions ); ?></td>
				</tr>
			</table>
			<?php
			return apply_filters( 'multibanco_ifthen_thankyou_instructions_table_html', ob_get_clean(), $ent, $ref, $order_total, $order_id );
		}
		function order_details_after_order_table( $order ) {
			if( is_wc_endpoint_url( 'view-order') ) {
				$this->thankyou( $order );
			}
		}
		



		/**
		 * Email instructions
		 */
		function email_instructions_1( $order, $sent_to_admin, $plain_text ) { //"Hyyan WooCommerce Polylang" Integration removes "email_instructions" so we use "email_instructions_1"
			$this->email_instructions( $order, $sent_to_admin, $plain_text );
		}
		function email_instructions( $order, $sent_to_admin, $plain_text ) {
			//Avoid duplicate email instructions on some edge cases
			$send = false;
			if ( ( $sent_to_admin ) ) {
			//if ( ( $sent_to_admin ) && ( !WC_IfthenPay_Webdados()->instructions_sent_to_admin ) ) { //Fixed by checking class instances
				//WC_IfthenPay_Webdados()->instructions_sent_to_admin = true;
				$send = true;
			} else {
				if ( ( !$sent_to_admin ) ) {
				//if ( ( !$sent_to_admin ) && ( !WC_IfthenPay_Webdados()->instructions_sent_to_client ) ) { //Fixed by checking class instances
					//WC_IfthenPay_Webdados()->instructions_sent_to_client = true;
					$send = true;
				}
			}
			//$this->debug_log( 'Email instructions send: '.( $send ? 'true' : 'false' ) );
			//Send
			if ( $send ) {
				$order_id = version_compare( WC_VERSION, '3.0', '>=' ) ? $order->get_id() : $order->id;
				$order = new WC_Order_MB_Ifthen( $order_id );
				//Go
				if ( $this->id === $order->mb_get_payment_method() ) {
					$show = false;
					if ( !$sent_to_admin ) {
						$show = true;
					} else {
						if ( $this->send_to_admin ) {
							$show = true;
						}
					}
					if ( $show ) {
						//WPML - Force correct language (?)
						if ( WC_IfthenPay_Webdados()->wpml_active ) {
							global $sitepress;
							if ( $sitepress ) {
								$lang = $order->mb_get_meta( 'wpml_language' );
								if( !empty( $lang ) ){
									WC_IfthenPay_Webdados()->change_email_language( $lang );
								}
							}
						}
						//On Hold or pending
						if ( $order->mb_has_status( 'on-hold' ) || $order->mb_has_status( 'pending' ) || $order->mb_has_status( 'partially-paid' ) ) {
							if ( WC_IfthenPay_Webdados()->wc_deposits_active && $order->mb_get_status() == 'partially-paid' ) {
								//WooCommerce deposits - No instructions
							} else {
								$ref = WC_IfthenPay_Webdados()->multibanco_get_ref( $order_id );
								if ( is_array( $ref) ) {
									if ( apply_filters( 'multibanco_ifthen_email_instructions_pending_send', true, $order_id ) ) {
										echo $this->email_instructions_table_html( $ref['ent'], $ref['ref'], $order->mb_get_total(), $order_id );
									}
								} else {
									?>
									<p><strong><?php _e( 'Error getting Multibanco payment details', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>.</strong></p>
									<?php
									if ( is_string( $ref ) ) {
										?>
										<p><?php echo $ref; ?></p>
										<?php
									}
								}
							}
						} else {
							//Processing
							if ( $order->mb_has_status( 'processing' ) ) {
								if ( apply_filters( 'multibanco_ifthen_email_instructions_payment_received_send', true, $order_id ) ) {
									echo $this->email_instructions_payment_received( $order_id );
								}
							}
						}
					}
					//$this->debug_log( 'Email instructions show: '.( $show ? 'true' : 'false' ) );
				}
			}
		}
		function email_instructions_table_html( $ent, $ref, $order_total, $order_id ) {
			$alt = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id.'_title', $this->title ) : $this->title );
			$extra_instructions = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id.'_extra_instructions', $this->extra_instructions ) : $this->extra_instructions );
			//We actually do not use $ent, $ref or $order_total - We'll just get the details
			$multibanco_order_details = WC_IfthenPay_Webdados()->get_multibanco_order_details( $order_id );
			ob_start();
			?>
			<table cellpadding="10" cellspacing="0" align="center" border="0" style="margin: auto; margin-top: 2em; margin-bottom: 2em; border-collapse: collapse; border: 1px solid #1465AA; border-radius: 4px !important; background-color: #FFFFFF;">
				<tr>
					<td style="border: 1px solid #1465AA; border-top-right-radius: 4px !important; border-top-left-radius: 4px !important; text-align: center; color: #000000; font-weight: bold;" colspan="2">
						<?php _e( 'Payment instructions', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
						<br/>
						<img src="<?php echo plugins_url( 'images/banner_multibanco.png', __FILE__ ); ?>" alt="<?php echo esc_attr( $alt); ?>" title="<?php echo esc_attr( $alt); ?>" style="margin-top: 10px;"/>
					</td>
				</tr>
				<tr>
					<td style="border-top: 1px solid #1465AA; color: #000000;"><?php _e( 'Entity', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
					<td style="border-top: 1px solid #1465AA; color: #000000; white-space: nowrap; text-align: right;"><?php echo $multibanco_order_details['ent']; ?></td>
				</tr>
				<tr>
					<td style="border-top: 1px solid #1465AA; color: #000000;"><?php _e( 'Reference', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
					<td style="border-top: 1px solid #1465AA; color: #000000; white-space: nowrap; text-align: right;"><?php echo WC_IfthenPay_Webdados()->format_multibanco_ref( $multibanco_order_details['ref'] ); ?></td>
				</tr>
				<tr>
					<td style="border-top: 1px solid #1465AA; color: #000000;"><?php _e( 'Value', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
					<td style="border-top: 1px solid #1465AA; color: #000000; white-space: nowrap; text-align: right;"><?php echo wc_price( $multibanco_order_details['val'], array( 'currency' => 'EUR' ) ); ?></td>
				</tr>
				<?php
				if ( isset( $multibanco_order_details['exp'] ) && trim( $multibanco_order_details['exp'] ) != '' ) {
					?>
					<tr>
						<td style="border-top: 1px solid #1465AA; color: #000000;"><?php _e( 'Expiration', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>:</td>
						<td style="border-top: 1px solid #1465AA; color: #000000; white-space: nowrap; text-align: right;"><?php echo WC_IfthenPay_Webdados()->multibanco_format_expiration( $multibanco_order_details['exp'], $order_id ); ?></td>
					</tr>
					<?php
				}
				?>
				<tr>
					<td style="font-size: x-small; border: 1px solid #1465AA; border-bottom-right-radius: 4px !important; border-bottom-left-radius: 4px !important; color: #000000; text-align: center;" colspan="2"><?php echo nl2br( $extra_instructions ); ?></td>
				</tr>
			</table>
			<?php
			return apply_filters( 'multibanco_ifthen_email_instructions_table_html', ob_get_clean(), $ent, $ref, $order_total, $order_id );
		}
		function email_instructions_payment_received( $order_id ) {
			$alt = ( WC_IfthenPay_Webdados()->wpml_active ? icl_t( $this->id, $this->id.'_title', $this->title ) : $this->title );
			ob_start();
			?>
			<p style="text-align: center; margin: auto; margin-top: 2em; margin-bottom: 2em;">
				<img src="<?php echo plugins_url( 'images/banner_multibanco.png', __FILE__ ); ?>" alt="<?php echo esc_attr( $alt ); ?>" title="<?php echo esc_attr( $alt ); ?>" style="margin: auto;"/>
				<br/>
				<strong><?php _e( 'Multibanco payment received.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></strong>
				<br/>
				<?php _e( 'We will now process your order.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
			</p>
			<?php
			return apply_filters( 'multibanco_ifthen_email_instructions_payment_received', ob_get_clean(), $order_id );
		}

		/**
		 * SMS instructions for Twilio SMS Notifications
		 */
		function sms_instructions_twilio( $message, $order_id ) {
			$replace = WC_IfthenPay_Webdados()->multibanco_sms_instructions( $message, $order_id );
			return trim( preg_replace('/\s+/', ' ', str_replace( '%multibanco_ifthen%', $replace, $message ) ) ); //Return message with %multibanco_ifthen% replaced by the instructions
		}
		/**
		 * SMS instructions for Twilio SMS Notifications
		 */
		function sms_instructions_yith( $placeholders, $order ) {
			if ( is_array($placeholders) ) {
				$order_id = version_compare( WC_VERSION, '3.0', '>=' ) ? $order->get_id() : $order->id;
				$placeholders['{multibanco_ifthen}'] = WC_IfthenPay_Webdados()->multibanco_sms_instructions( '', $order_id );
			}
			return $placeholders;
		}

		/**
		 * Process it
		 */
		function process_payment( $order_id ) {
			$order = new WC_Order_MB_Ifthen( $order_id );
			//WooCommerce Deposits - When generating second payment reference the order goes from partially paid to on hold, and that has an email (??!)
			if ( WC_IfthenPay_Webdados()->wc_deposits_active && $order->mb_get_status() == 'partially-paid' ) {
				add_filter( 'woocommerce_email_enabled_customer_processing_order', '__return_false' );
				add_filter( 'woocommerce_email_enabled_full_payment', '__return_false' );
			}
			// Mark as on-hold
			if ( apply_filters( 'multibanco_ifthen_set_on_hold', true, $order_id ) ) $order->update_status( 'on-hold', __( 'Awaiting Multibanco payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) );
			// Reduce stock levels
			if ( $this->stock_when == 'order' && version_compare( WC_VERSION, '3.4.0', '<' ) ) $order->mb_reduce_order_stock();
			// Remove cart
			WC()->cart->empty_cart();
			// Empty awaiting payment session
			if ( isset( $_SESSION['order_awaiting_payment'] ) ) unset($_SESSION['order_awaiting_payment'] );
			// Return thankyou redirect
			return array(
				'result' => 'success',
				'redirect' => $this->get_return_url( $order )
			);
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
		 * Just above/bellow certain amounts
		 */
		function disable_only_above_or_bellow( $available_gateways ) {
			return WC_IfthenPay_Webdados()->disable_only_above_or_bellow( $available_gateways, $this->id, WC_IfthenPay_Webdados()->multibanco_min_value, WC_IfthenPay_Webdados()->multibanco_max_value );
		}


		/* Payment complete - Stolen from PayPal method */
		function payment_complete( $order, $txn_id = '', $note = '' ) {
			$order->add_order_note( $note );
			$order->payment_complete( $txn_id );
		}
		/* Reduce stock on 'wc_maybe_reduce_stock_levels'? */
		function woocommerce_payment_complete_reduce_order_stock( $bool, $order_id ) {
			$order = new WC_Order_MB_Ifthen( $order_id );
			if ( $order->mb_get_payment_method() == $this->id ) {
				return ( WC_IfthenPay_Webdados()->woocommerce_payment_complete_reduce_order_stock( $bool, $order_id, $this->id, $this->stock_when ) );
			} else {
				return $bool;
			}
		}

		/**
		 * Callback
		 */
		function callback() {
			@ob_clean();
			//We must 1st check the situation and then process it and send email to the store owner in case of error.
			if (
				isset( $_GET['chave'] )
				&&
				isset( $_GET['entidade'] )
				&&
				isset( $_GET['referencia'] )
				&&
				isset( $_GET['valor'] )
			) {
				//Let's process it
				$this->debug_log( '- Callback ('.$_SERVER['REQUEST_URI'].') with all arguments from '.$_SERVER['REMOTE_ADDR'] );
				$ref = trim( str_replace( ' ', '', $_GET['referencia'] ) );
				$ent = trim( $_GET['entidade'] );
				$val = floatval( $_GET['valor'] );
				$arguments_ok = true;
				$arguments_error = '';
				if ( trim( $_GET['chave'] )!=trim( $this->secret_key) ) {
					$arguments_ok = false;
					$arguments_error .= ' - Key';
				}
				if ( !is_numeric( $ref ) ) {
					$arguments_ok = false;
					$arguments_error .= ' - Ref (numeric)';
				}
				if ( strlen( $ref ) != 9 ) {
					$arguments_ok = false;
					$arguments_error .= ' - Ref (length)';
				}
				if ( !is_numeric( $ent ) ) {
					$arguments_ok = false;
					$arguments_error .= ' - Ent (numeric)';
				}
				if ( strlen( $ent ) != 5 ) {
					$arguments_ok = false;
					$arguments_error .= ' - Ent (length)';
				}
				if ( !$val >= 1 ) {
					$arguments_ok = false;
					$arguments_error .= ' - Value';
				}
				if ( $arguments_ok ) { // Isto deve ser separado em vários IFs para melhor se identificar o erro
					$orders_exist = false;
					$orders_count = 0;
					if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
						if ( version_compare( WC_VERSION, '2.2', '<' ) ) {
							//The old way before WooCommerce 2.2
							$args = array(
								'post_type'	=> 'shop_order',
								'post_status' => 'publish',
								'posts_per_page' => -1,
								'tax_query' => array(
									//We should also look for pending because of the filter added on 2017-12-15, but people should not be using WC < 2.2 by now...
									array(
										'taxonomy' => 'shop_order_status',
										'field' => 'slug',
										'terms' => array( 'on-hold', 'pending' ),
									)
								),
								'meta_query' => array(
									array(
										'key' => '_'.$this->id.'_ent',
										'value' => $ent,
										'compare' => 'LIKE'
									),
									array(
										'key' => '_'.$this->id.'_ref',
										'value' => $ref,
										'compare' => 'LIKE'
									)
								)
							);
						} else {
							//The old way From WooCommerce 2.2
							$args = array(
								'post_type' => 'shop_order',
								'post_status' => array( 'wc-on-hold', 'wc-pending' ),
								'posts_per_page' => -1,
								'meta_query' => array(
									array(
										'key' => '_'.$this->id.'_ent',
										'value' => $ent,
										'compare' => 'LIKE'
									),
									array(
										'key' => '_'.$this->id.'_ref',
										'value' => $ref,
										'compare' => 'LIKE'
									)
								)
							);
						}
						$the_query = new WP_Query( $args );
						if ( $the_query->have_posts() ) {
							$orders_exist = true;
							$orders_count = $the_query->post_count;
							while ( $the_query->have_posts() ) : $the_query->the_post();
								$order = new WC_Order_MB_Ifthen( $the_query->post->ID );
							endwhile;
						}
					} else {
						$pending_status = apply_filters( 'multibanco_ifthen_valid_callback_pending_status', array( 'on-hold', 'pending', 'partially-paid' ) );
						$args = array(
							'type'               => array( 'shop_order' ),
							'status'             => $pending_status,
							'limit'              => -1,
							'_'.$this->id.'_ent' => $ent,
							'_'.$this->id.'_ref' => $ref,
						);
						$orders = wc_get_orders( $args );
						if ( count($orders)>0 ) {
							$orders_exist = true;
							$orders_count = count($orders);
							foreach ( $orders as $order ) {
								$order = new WC_Order_MB_Ifthen( $order->get_id() );
							}
						}
					}

					if ( $orders_exist ) {
						if ( $orders_count == 1 ) {
							$value_ok = ( $val == floatval( $order->mb_get_total() ) );
							if ( $value_ok ) {
								
								if ( version_compare( WC_VERSION, '2.6', '<' ) ) {
									//We must first change the order status to "pending" and then to "processing" or no email will be sent to the client (and store owner)
									include_once( ABSPATH.'wp-admin/includes/plugin.php' );
									if ( !is_plugin_active( 'order-status-emails-for-woocommerce/order-status-emails-for-woocommerce.php' ) ) //Only if this plugin is not active
										if ( $order->mb_get_status() != 'pending' ) $order->update_status( 'pending', __( 'Temporary status. Used to force an email on the next order status change.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) );
								}
								
								$note=__( 'Multibanco payment received.', 'multibanco-ifthen-software-gateway-for-woocommerce' );
								if ( isset( $_GET['datahorapag'] ) && trim( $_GET['datahorapag'] )!='' ) {
									$note.=' '.trim( $_GET['datahorapag'] );
								}
								if ( isset( $_GET['terminal'] ) && trim( $_GET['terminal'] )!='' ) {
									$note.=' '.trim( $_GET['terminal'] );
								}
								//WooCommerce Deposits second payment?
								if ( WC_IfthenPay_Webdados()->wc_deposits_active ) {
									if ( $order->mb_get_meta( '_wc_deposits_order_has_deposit' ) == 'yes' ) { //Has deposit
										if ( $order->mb_get_meta( '_wc_deposits_deposit_paid' ) == 'yes' ) { //First payment - OK!
											if ( $order->mb_get_meta( '_wc_deposits_second_payment_paid' ) != 'yes' ) { //Second payment - not ok
												if ( floatval( $order->mb_get_meta( '_wc_deposits_second_payment' ) ) == floatval( $val ) ) { //This really seems like the second payment
													//Set the current order status temporarly back to partially-paid, but first stop the emails
													add_filter( 'woocommerce_email_enabled_customer_partially_paid', '__return_false' );
													add_filter( 'woocommerce_email_enabled_partial_payment', '__return_false' );
													$order->update_status( 'partially-paid', __( 'Temporary status. Used to force WooCommerce Deposits to correctly set the order to processing.', 'multibanco-ifthen-software-gateway-for-woocommerce' ) );
												}
											}
										}
									}
								}
								$this->payment_complete( $order, '', $note );
								//Force resending "New Order" email to the store owner (before 3.4.2 we had a "bug" that made this email duplicate - and people are used to it)
								if ( version_compare( WC_VERSION, '2.6', '>=' ) ) {
									if ( apply_filters( 'multibanco_ifthen_set_on_hold', true, $order->mb_get_id() ) ) { //Only if we set it on hold in the first place
										if ( $this->get_option( 'resend_new_order_when_paid' ) == 'yes' ) { //And the option is activated
											WC()->mailer()->emails['WC_Email_New_Order']->trigger( $order->mb_get_id(), $order );
										}
									}
								}
								do_action( 'multibanco_ifthen_callback_payment_complete', $order->mb_get_id() );
								
								header( 'HTTP/1.1 200 OK' );
								$this->debug_log( '-- Multibanco payment received - Order '.$order->mb_get_id(), 'notice', true, 'Callback ('.$_SERVER['HTTP_HOST'].' '.$_SERVER['REQUEST_URI'].') from '.$_SERVER['REMOTE_ADDR'].' - Multibanco payment received' );
								echo 'OK - Multibanco payment received';
							} else {
								header( 'HTTP/1.1 200 OK' );
								$err = 'Error: The value does not match';
								$this->debug_log( '-- '.$err.' - Order '.$order->mb_get_id(), 'warning', true, 'Callback ('.$_SERVER['HTTP_HOST'].' '.$_SERVER['REQUEST_URI'].') from '.$_SERVER['REMOTE_ADDR'].' - The value does not match' );
								echo $err;
								do_action( 'multibanco_ifthen_callback_payment_failed', $order->mb_get_id(), $err );
							}
						} else {
							header( 'HTTP/1.1 200 OK' );
							$err = 'Error: More than 1 order found awaiting payment with these details';
							$this->debug_log( '-- '.$err, 'warning', true, 'Callback ('.$_SERVER['HTTP_HOST'].' '.$_SERVER['REQUEST_URI'].') from '.$_SERVER['REMOTE_ADDR'].' - More than 1 order found awaiting payment with these details' );
							echo $err;
							do_action( 'multibanco_ifthen_callback_payment_failed', 0, $err );
						}
					} else {
						header( 'HTTP/1.1 200 OK' );
						$err = 'Error: No orders found awaiting payment with these details';
						$this->debug_log( '-- '.$err, 'warning', true, 'Callback ('.$_SERVER['HTTP_HOST'].' '.$_SERVER['REQUEST_URI'].') from '.$_SERVER['REMOTE_ADDR'].' - No orders found awaiting payment with these details' );
						echo $err;
						do_action( 'multibanco_ifthen_callback_payment_failed', 0, $err );
					}
				} else {
					//header("Status: 400");
					$err = 'Argument errors';
					$this->debug_log( '-- '.$err, 'warning', true, 'Callback ('.$_SERVER['HTTP_HOST'].' '.$_SERVER['REQUEST_URI'].') with argument errors from '.$_SERVER['REMOTE_ADDR'].$arguments_error );
					do_action( 'multibanco_ifthen_callback_payment_failed', 0, $err );
					wp_die( $err, 'WC_Multibanco_IfThen_Webdados', array( 'response' => 500 ) ); //Sends 500
				}
			} else {
				//header("Status: 400");
				$err = 'Callback ('.$_SERVER['REQUEST_URI'].') with missing arguments from '.$_SERVER['REMOTE_ADDR'];
				$this->debug_log( '- '.$err, 'warning', true, 'Callback ('.$_SERVER['HTTP_HOST'].' '.$_SERVER['REQUEST_URI'].') with missing arguments from '.$_SERVER['REMOTE_ADDR'] );
				do_action( 'multibanco_ifthen_callback_payment_failed', 0, $err );
				wp_die( 'Error: Something is missing...', 'WC_Multibanco_IfThen_Webdados', array( 'response' => 500 ) ); //Sends 500
			}
		}

		/* Debug / Log - MOVED TO WC_IfthenPay_Webdados with gateway id as first argument */
		public function debug_log( $message, $level = 'debug', $to_email = false, $email_message = '' ) {
			if ( $this->debug ) {
				WC_IfthenPay_Webdados()->debug_log( $this->id, $message, $level, ( trim( $this->debug_email ) != '' && $to_email ? $this->debug_email : false ) , $email_message );
			}
		}

		/* Global admin notices - For example if callback email activation is still not sent */
		function admin_notices() {
			if (
				trim( $this->enabled ) == 'yes'
				&&
				strlen( trim( $this->ent ) ) == 5
				&&
				strlen( trim( $this->subent ) ) <= 3
				&&
				intval( $this->ent ) > 0
				&&
				intval( $this->subent ) > 0
				&&
				trim( $this->secret_key ) != ''
			) {
				if ( $callback_email_sent = get_option( $this->id . '_callback_email_sent' ) ) { //No notice for older versions
					if ( $callback_email_sent == 'no' ) {
						if ( !isset( $_GET['callback_warning'] ) ) {
							if ( apply_filters( 'multibanco_ifthen_show_callback_notice', true ) ) {
								?>
								<div id="multibanco_ifthen_callback_notice" class="notice notice-error" style="padding-right: 38px; position: relative;">
									<p>
										<strong>Multibanco (IfthenPay)</strong>
										<br/>
										<?php _e( 'You haven’t yet asked IfthenPay for the “Callback” activation. The orders will NOT be automatically updated upon payment.', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>
										<br/>
										<strong><?php _e( 'This is important', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?>! <a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=multibanco_ifthen_for_woocommerce&amp;callback_warning=1"><?php _e( 'Do it here', 'multibanco-ifthen-software-gateway-for-woocommerce' ); ?></a>!</strong>
									</p>
								</div>
								<?php
							}
						}
					}
				}
			}
		}

	}
}