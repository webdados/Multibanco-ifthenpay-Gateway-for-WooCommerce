<?php
/**
 * Cofidis Pay blocks class
 */

namespace Automattic\WooCommerce\Blocks\Payments\Integrations;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;

/**
 * Cofidis Pay payment method integration
 */
final class CofidisPayIfthenPay extends AbstractPaymentMethodType {
	/**
	 * Payment method name defined by payment methods extending this class.
	 *
	 * @var string
	 */
	protected $name = 'cofidispay_ifthen_for_woocommerce';

	/**
	 * Constructor
	 */
	public function __construct() {
	}

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = WC_IfthenPay_Webdados()->cofidispay_settings;
		// WooCommerce Blocks - Store API
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => 'ifthenpay',
				'schema_callback' => array( $this, 'store_api_schema_callback' ),
				'data_callback'   => array( $this, 'store_api_data_callback' ),
				'schema_type'     => ARRAY_A,
			)
		);
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'];
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		wp_register_script(
			'wc-payment-method-cofidispay-ifthenpay',
			plugins_url( 'build/cofidispay-block.js', __FILE__ ),
			array(),
			WC_IfthenPay_Webdados()->get_version() . ( WP_DEBUG ? '.' . wp_rand( 0, 9999 ) : '' ),
			true
		);
		return array( 'wc-payment-method-cofidispay-ifthenpay' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return apply_filters(
			'cofidispay_ifthen_blocks_payment_method_data',
			array(
				'title'         => WC_IfthenPay_Webdados()->get_gateway_title_or_description_for_blocks( $this->name, $this->settings, 'title' ),
				'description'   => WC_IfthenPay_Webdados()->get_gateway_title_or_description_for_blocks( $this->name, $this->settings, 'description' ),
				'icon'          => WC_IfthenPay_Webdados()->cofidispay_icon,
				'icon_width'    => 24,
				'icon_height'   => 24,
				'only_portugal' => $this->settings['only_portugal'] === 'yes',
				'only_above'    => floatval( $this->settings['only_above'] ) > 0 ? floatval( $this->settings['only_above'] ) : null,
				'only_bellow'   => floatval( $this->settings['only_bellow'] ) > 0 ? floatval( $this->settings['only_bellow'] ) : null,
				// We do not declare subscriptions support on Cofidis
				// 'support_woocommerce_subscriptions' => isset( $this->settings['support_woocommerce_subscriptions'] ) && ( 'yes' === $this->settings['support_woocommerce_subscriptions'] ), // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
				// More settings needed?
			)
		);
	}

	/**
	 * Add Store API schema data.
	 *
	 * @return array
	 */
	public function store_api_schema_callback() {
		return array(
			'cofidisFailedPayment' => array(
				'description' => 'If the payment failed at Cofidis',
				'type'        => array( 'float', 'null' ),
				'readonly'    => true,
			),
		);
	}

	/**
	 * Add Store API endpoint data.
	 *
	 * @return array
	 */
	public function store_api_data_callback() {
		$failed_payment = false;
		$order_id       = absint( WC()->session->get( 'store_api_draft_order' ) );
		if ( ! empty( $order_id ) ) {
			$order = wc_get_order( $order_id );
			if ( ! empty( $order ) ) {
				$meta  = '_' . $this->name . '_checkouterror';
				$error = $order->get_meta( '_' . $this->name . '_checkouterror' );
				if ( ! empty( $error ) ) {
					$failed_payment = $error;
				}
			}
		}
		return array(
			'cofidisFailedPayment' => $failed_payment,
		);
	}
}
