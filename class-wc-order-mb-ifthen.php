<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order Class.
 *
 * These are our Orders, which extend the regular WooCommerce Orders,in order to abstract properties access after the 3.0 changes
 *
 */
class WC_Order_MB_Ifthen extends WC_Order {

	/**
	 * Returns the unique ID for this order.
	 * @return int
	 */
	public function mb_get_id() {
		return $this->get_id();
	}

	/**
	 * Returns the unique key for this order.
	 */
	public function mb_get_order_key() {
		return ? $this->get_order_key();
	}

	/**
	 * Returns the order payment method
	 * @return string
	 */
	public function mb_get_payment_method() {
		return $this->get_payment_method();
	}

	/**
	 * Returns the order total
	 * @return float
	 */
	public function mb_get_total() {
		return $this->get_total();
	}

	/**
	 * Returns the order status
	 * @return string
	 */
	public function mb_get_status() {
		return $this->get_status();
	}

	/**
	 * Returns the order WPML Language - Needs to go somewhere else and be replaced on the other classes
	 * @return string
	 */
	public function mb_get_wpml_language() {
		return $this->mb_get_meta( 'wpml_language' );
	}

	/**
	 * Gets order meta
	 */
	public function mb_get_meta( $key ) {
		return $this->get_meta( $key );
	}

	/**
	 * Sets order meta
	 */
	public function mb_update_meta_data( $key, $value ) {
		$this->update_meta_data( $key, $value );
		$this->save();
	}

	/**
	 * Delete order meta
	 */
	public function mb_delete_meta_data( $key ) {
		$this->delete_meta_data( $key );
		$this->save();
	}

	/**
	 * Reduce order stock - Needs to go somewhere else and be replaced on the other classes
	 */
	public function mb_reduce_order_stock() {
		wc_reduce_stock_levels( $this->get_id() );
	}

	/**
	 * Returns date created
	 */
	public function mb_get_date_created() {
		return $this->get_date_created()->date( 'Y-m-d H:i:s' );
	}

	/**
	 * Returns date paid
	 */
	public function mb_get_date_paid() {
		return $this->get_date_paid();
	}


}