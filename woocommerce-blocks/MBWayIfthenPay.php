<?php
/**
 * MB WAY blocks class
 */

namespace Automattic\WooCommerce\Blocks\Payments\Integrations;

use Automattic\WooCommerce\Blocks\Assets\Api;

/**
 * MB WAY payment method integration
 */
final class MBWayIfthenPay extends AbstractPaymentMethodType {
	/**
	 * Payment method name defined by payment methods extending this class.
	 *
	 * @var string
	 */
	protected $name = 'mbway_ifthen_for_woocommerce';

	/**
	 * Constructor
	 */
	public function __construct() {
	}

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = WC_IfthenPay_Webdados()->mbway_settings;
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
		$script_handle = 'wc-payment-method-mbway-ifthenpay';
		// Register the script
		wp_register_script(
			$script_handle,
			plugins_url( 'build/mbway-block.js', __FILE__ ),
			array(),
			WC_IfthenPay_Webdados()->get_version() . ( WP_DEBUG ? '.' . wp_rand( 0, 9999 ) : '' ),
			true
		);
		return array( $script_handle );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		// Set data
		$allow_international = isset( $this->settings['allow_international'] ) ? $this->settings['allow_international'] === 'yes' : true;
		$data                = array(
			'title'                => WC_IfthenPay_Webdados()->get_gateway_title_or_description_for_blocks( $this->name, $this->settings, 'title' ),
			'description'          => WC_IfthenPay_Webdados()->get_gateway_title_or_description_for_blocks( $this->name, $this->settings, 'description' ),
			'icon'                 => WC_IfthenPay_Webdados()->mbway_icon,
			'icon_width'           => 28,
			'icon_height'          => 24,
			'only_portugal'        => $this->settings['only_portugal'] === 'yes',
			'allow_international'  => $allow_international,
			'only_above'           => floatval( $this->settings['only_above'] ) > 0 ? floatval( $this->settings['only_above'] ) : null,
			'only_bellow'          => floatval( $this->settings['only_bellow'] ) > 0 ? floatval( $this->settings['only_bellow'] ) : null,
			'phonenumbertext'      => __( 'Your phone number linked to MB WAY', 'multibanco-ifthen-software-gateway-for-woocommerce' ),
			'id'                   => $this->name,
			'default_number'       => apply_filters( 'mbway_ifthen_checkout_default_phone_number', '' ),
			'default_country_code' => apply_filters( 'mbway_ifthen_checkout_default_country_code', WC()->countries->get_base_country() ),
			// We do not declare subscriptions support on MB WAY
			// 'support_woocommerce_subscriptions' => isset( $this->settings['support_woocommerce_subscriptions'] ) && ( 'yes' === $this->settings['support_woocommerce_subscriptions'] ), // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
			// More settings needed?
		);
		if ( $allow_international ) {
			$data['country_code_options'] = \WC_IfthenPay_Webdados::get_countries_with_phone_prefixes();
		}
		// Return it
		return apply_filters(
			'mbway_ifthen_blocks_payment_method_data',
			$data
		);
	}
}
