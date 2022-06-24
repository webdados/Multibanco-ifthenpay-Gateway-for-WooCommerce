<?php
/**
 * Multibanco IfthenPay gateway implementation.
 *
 */

namespace Automattic\WooCommerce\Blocks\Payments\Integrations;

use Exception;
use WC_Stripe_Payment_Request; //???
use WC_Stripe_Helper; //???
use Automattic\WooCommerce\Blocks\Assets\Api;

/**
 * Cheque payment method integration
 *
 * @since 2.6.0
 */
final class MultibancoIfthenPay extends AbstractPaymentMethodType {
	/**
	 * Payment method name defined by payment methods extending this class.
	 *
	 * @var string
	 */
	protected $name = 'multibanco_ifthen_for_woocommerce';

	/**
	 * Settings from the WP options table
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * An instance of the Asset Api
	 *
	 * @var Api
	 */
	//private $asset_api;

	/**
	 * Constructor
	 *
	 * @param Api $asset_api An instance of Api.
	 */
	//public function __construct( Api $asset_api ) {
	//	$this->asset_api = $asset_api;
	//}
	public function __construct() {
	}

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = WC_IfthenPay_Webdados()->multibanco_settings;
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
		wp_register_script( 'wc-payment-method-multibanco-ifthenpay', plugins_url( 'wc-payment-method-multibanco-ifthenpay.js', __FILE__ ), array(), WC_IfthenPay_Webdados()->get_version(), true );
		return [ 'wc-payment-method-multibanco-ifthenpay' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [
			'title'                             => isset( $this->settings['title'] ) ? $this->settings['title'] : '',
			'description'                       => isset( $this->settings['description'] ) ? $this->settings['description'] : '',
			'icon'                              => WC_IfthenPay_Webdados()->multibanco_icon,
			'only_portugal'                     => $this->settings['only_portugal'] == 'yes',
			'only_above'                        => floatval( $this->settings['only_above'] ) > 0 ? floatval( $this->settings['only_above'] ) : null,
			'only_bellow'                       => floatval( $this->settings['only_bellow'] ) > 0 ? floatval( $this->settings['only_bellow'] ) : null,
			'support_woocommerce_subscriptions' => $this->settings['support_woocommerce_subscriptions'] == 'yes',
			//More settings needed?
		];
	}
}
