<?php
/**
 * Hooks examples
 */

// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Multibanco - Hide non requested callback notice
 */
add_filter( 'multibanco_ifthen_show_callback_notice', '__return_false' );


/**
 * MB WAY - Hide non requested callback notice
 */
add_filter( 'mbway_ifthen_show_callback_notice', '__return_false' );


/**
 * Payshop - Hide non requested callback notice
 */
add_filter( 'payshop_ifthen_show_callback_notice', '__return_false' );


/**
 * Multibanco - Format Multibanco reference
 *
 * @param string $ref The Multibanco reference.
 */
function my_multibanco_ifthen_format_ref( $ref ) {
	return str_replace( ' ', '', $ref ); // Remove spaces example
}
add_filter( 'multibanco_ifthen_format_ref', 'my_multibanco_ifthen_format_ref' );


/**
 * MB WAY - Filter for the text shown on the mobile app
 *
 * @param string   $text The test.
 * @param WC_Order $order_id The order ID.
 * @return string
 */
function my_mbway_ifthen_webservice_desc( $text, $order_id ) {
	return 'Pay for Order #' . $order_id;
}
add_filter( 'mbway_ifthen_webservice_desc', 'my_mbway_ifthen_webservice_desc', 10, 2 );


/**
 * Multibanco - Email payment instructions filter
 *
 * @param string  $html        The original HTML.
 * @param string  $ent         The Multibanco entity.
 * @param string  $ref         The Multibanco reference.
 * @param float   $order_total The order total.
 * @param integer $order_id    The order ID.
 * @return string
 */
function my_multibanco_ifthen_email_instructions_table_html( $html, $ent, $ref, $order_total, $order_id ) {
	ob_start();
	?>
	<h2>Multibanco payment instructions for Order #<?php echo esc_html( $order_id ); ?></h2>
	<p>
		<b>Entity:</b> <?php echo esc_html( $ent ); ?>
		<br/>
		<b>Reference:</b> <?php echo esc_html( WC_IfthenPay_Webdados()->format_multibanco_ref( $ref ) ); ?>
		<br/>
		<b>Value:</b> <?php echo esc_html( $order_total ); ?>
	</p>
	<p>
	<?php
	// With WPML
	echo wp_kses_post( nl2br( function_exists( 'icl_object_id' ) ? icl_t( WC_IfthenPay_Webdados()->multibanco_id, WC_IfthenPay_Webdados()->multibanco_id . '_extra_instructions', WC_IfthenPay_Webdados()->multibanco_settings['extra_instructions'] ) : WC_IfthenPay_Webdados()->multibanco_settings['extra_instructions'] ) );
	?>
	</p>
	<?php
	return ob_get_clean();
}
add_filter( 'multibanco_ifthen_email_instructions_table_html', 'my_multibanco_ifthen_email_instructions_table_html', 1, 5 );


/**
 * MB WAY - Email payment instructions filter
 *
 * @param string  $html        The original HTML.
 * @param float   $order_total The order total.
 * @param integer $order_id    The order ID.
 * @return string
 */
function my_mbway_ifthen_email_instructions_table_html( $html, $order_total, $order_id ) {
	ob_start();
	?>
	<h2>MB WAY payment instructions for Order #<?php echo esc_html( $order_id ); ?></h2>
	<p>
		<b>Value:</b> <?php echo esc_html( $order_total ); ?>
	</p>
	<p>
	<?php
	// With WPML
	echo wp_kses_post( nl2br( function_exists( 'icl_object_id' ) ? icl_t( WC_IfthenPay_Webdados()->mbway_id, WC_IfthenPay_Webdados()->mbway_id . '_extra_instructions', WC_IfthenPay_Webdados()->mbway_settings['extra_instructions'] ) : WC_IfthenPay_Webdados()->mbway_settings['extra_instructions'] ) );
	?>
	</p>
	<?php
	return ob_get_clean();
}
add_filter( 'mbway_ifthen_email_instructions_table_html', 'my_mbway_ifthen_email_instructions_table_html', 1, 3 );


/**
 * Multibanco - Email payment received text filter
 *
 * @param string  $html        The original HTML.
 * @param integer $order_id    The order ID.
 * @return string
 */
function my_multibanco_ifthen_email_instructions_payment_received( $html, $order_id ) {
	// We can, for example, format and return just part of the text
	ob_start();
	?>
	<p style="color: #FF0000; font-weight: bold;">
		Multibanco payment received for order #<?php echo esc_html( $order_id ); ?>.
	</p>
	<?php
	return ob_get_clean();
}
add_filter( 'multibanco_ifthen_email_instructions_payment_received', 'my_multibanco_ifthen_email_instructions_payment_received', 10, 2 );


/**
 * MB WAY - Email payment received text filter
 *
 * @param string $html The original HTML.
 * @return string
 */
function my_mbway_ifthen_email_instructions_payment_received( $html ) {
	// We can, for example, format and return just part of the text
	ob_start();
	?>
	<p style="color: #FF0000; font-weight: bold;">
		MB WAY payment received for order #<?php echo esc_html( $order_id ); ?>.
	</p>
	<?php
	return ob_get_clean();
}
add_filter( 'mbway_ifthen_email_instructions_payment_received', 'my_mbway_ifthen_email_instructions_payment_received', 10, 2 );


/**
 * Multibanco - Thank you page payment instructions filter
 *
 * @param string  $html        The original HTML.
 * @param string  $ent         The Multibanco entity.
 * @param string  $ref         The Multibanco reference.
 * @param float   $order_total The order total.
 * @param integer $order_id    The order ID.
 * @return string
 */
function my_multibanco_ifthen_thankyou_instructions_table_html( $html, $ent, $ref, $order_total, $order_id ) {
	ob_start();
	?>
	<h2>Multibanco payment instructions for Order #<?php echo esc_html( $order_id ); ?></h2>
	<p>
		<b>Entity:</b> <?php echo esc_html( $ent ); ?>
		<br/>
		<b>Reference:</b> <?php echo esc_html( WC_IfthenPay_Webdados()->format_multibanco_ref( $ref ) ); ?>
		<br/>
		<b>Value:</b> <?php echo esc_html( $order_total ); ?>
	</p>
	<p>
	<?php
	// Without WPML
	echo esc_html( WC_IfthenPay_Webdados()->multibanco_settings['extra_instructions'] );
	?>
	</p>
	<?php
	return ob_get_clean();
}
add_filter( 'multibanco_ifthen_thankyou_instructions_table_html', 'my_multibanco_ifthen_thankyou_instructions_table_html', 1, 5 );


/**
 * MB WAY - Thank you page payment instructions filter
 *
 * @param string  $html        The original HTML.
 * @param float   $order_total The order total.
 * @param integer $order_id    The order ID.
 * @return string
 */
function my_mbway_ifthen_thankyou_instructions_table_html( $html, $order_total, $order_id ) {
	ob_start();
	?>
	<h2>MB WAY payment instructions for Order #<?php echo esc_html( $order_id ); ?></h2>
	<p>
		<b>Value:</b> <?php echo esc_html( $order_total ); ?>
	</p>
	<p>
	<?php
	// Without WPML
	echo esc_html( WC_IfthenPay_Webdados()->mbway_settings['extra_instructions'] );
	?>
	</p>
	<?php
	return ob_get_clean();
}
add_filter( 'mbway_ifthen_thankyou_instructions_table_html', 'my_mbway_ifthen_thankyou_instructions_table_html', 1, 3 );


/**
 * MB WAY - Disable Ajax checking on the order status on the Thank you page
 *
 * @param bool    $check_order_status Check order status?.
 * @param integer $order_id           The order ID.
 * @return bool
 */
function my_mbway_ifthen_enable_check_order_status_thankyou( $check_order_status, $order_id ) {
	return false;
}
add_filter( 'mbway_ifthen_enable_check_order_status_thankyou', 'my_mbway_ifthen_enable_check_order_status_thankyou', 10, 2 );


/**
 * Multibanco - SMS Instructions filter
 *
 * @param string  $message     The original message.
 * @param string  $ent         The Multibanco entity.
 * @param string  $ref         The Multibanco reference.
 * @param float   $order_total The order total.
 * @param integer $order_id    The order ID.
 * @return string
 */
function my_multibanco_ifthen_sms_instructions( $message, $ent, $ref, $order_total, $order_id ) {
	return 'Order #' . $order_id . ' - Ent. ' . $ent . ' Ref. ' . $ref . ' Val. ' . $order_total;
}
add_filter( 'multibanco_ifthen_sms_instructions', 'my_multibanco_ifthen_sms_instructions', 1, 5 );


/**
 * Multibanco - Action when payment complete via callback - Store a custom parameter
 *
 * @param integer $order_id The order ID.
 * @param array   $get      The callback $_GET parameters.
 */
function my_multibanco_ifthen_callback_payment_complete( $order_id, $get ) {
	if ( isset( $get['valor_liquido'] ) ) {
		$order = wc_get_order( $order_id );
		$order->update_meta_data( '_' . WC_IfthenPay_Webdados()->multibanco_id . '_valor_liquido', $get['valor_liquido'] );
		$order->save();
	}
}
add_action( 'multibanco_ifthen_callback_payment_complete', 'my_multibanco_ifthen_callback_payment_complete', 10, 2 );


/**
 * MB WAY - Action when payment complete via callback - Send an email
 *
 * @param integer $order_id The order ID.
 */
function my_mbway_ifthen_callback_payment_complete( $order_id ) {
	wp_mail( 'email@your.domain', 'MB WAY order ' . $order_id . ' paid', 'MB WAY order ' . $order_id . ' paid' );
}
add_action( 'mbway_ifthen_callback_payment_complete', 'my_mbway_ifthen_callback_payment_complete', 10, 1 );


/**
 * Multibanco - Callback call failed
 *
 * @param integer $order_id The order ID.
 * @param string  $error    The callback error.
 * @param array   $get      The callback $_GET parameters.
 */
function my_multibanco_ifthen_callback_payment_failed( $order_id, $error, $get ) {
	wp_mail( 'email@your.domain', 'Multibanco callback for order ' . $order_id . ' failed', 'Multibanco callback for order ' . $order_id . ' failed - ' . $error . ' - ' . wp_json_encode( $get ) );
}
add_action( 'multibanco_ifthen_callback_payment_failed', 'my_multibanco_ifthen_callback_payment_failed', 10, 3 );


/**
 * MB WAY - Callback call failed
 *
 * @param integer $order_id The order ID.
 * @param string  $error    The callback error.
 * @param array   $get      The callback $_GET parameters.
 */
function my_mbway_ifthen_callback_payment_failed( $order_id, $error, $get ) {
	wp_mail( 'email@your.domain', 'MB WAY callback for order ' . $order_id . ' failed', 'MB WAY callback for order ' . $order_id . ' failed - ' . $error . ' - ' . wp_json_encode( $get ) );
}
add_action( 'mbway_ifthen_callback_payment_failed', 'my_mbway_ifthen_callback_payment_failed', 10, 3 );


/**
 * Payshop - Callback call failed
 *
 * @param integer $order_id The order ID.
 * @param string  $error    The callback error.
 * @param array   $get      The callback $_GET parameters.
 */
function my_payshop_ifthen_callback_payment_failed( $order_id, $error, $get ) {
	wp_mail( 'email@your.domain', 'Payshop callback for order ' . $order_id . ' failed', 'Payshop callback for order ' . $order_id . ' failed - ' . $error . ' - ' . wp_json_encode( $get ) );
}
add_action( 'payshop_ifthen_callback_payment_failed', 'my_payshop_ifthen_callback_payment_failed', 10, 3 );


/**
 * Multibanco - Change the icon html
 *
 * @param string $html The original icon HTML.
 * @param string $id   The payment gateway ID.
 */
function my_woocommerce_gateway_icon_mb( $html, $id ) {
	if ( $id === WC_IfthenPay_Webdados()->multibanco_id ) {
		$html = 'No icon'; // Any html you want here
	}
	return $html;
}
add_filter( 'woocommerce_gateway_icon', 'my_woocommerce_gateway_icon_mb', 1, 2 );


/**
 * MB WAY - Change the icon html
 *
 * @param string $html The original icon HTML.
 * @param string $id   The payment gateway ID.
 */
function my_woocommerce_gateway_icon_mbway( $html, $id ) {
	if ( $id === WC_IfthenPay_Webdados()->mbway_id ) {
		$html = 'No icon'; // Any html you want here
	}
	return $html;
}
add_filter( 'woocommerce_gateway_icon', 'my_woocommerce_gateway_icon_mbway', 1, 2 );


/**
 * Multibanco - Use specific Entity and Subentity for some specific order details
 * Example: depending on the delivery method, or the items bought, the payment must be made with different Ent/Subent
 *
 * @param array    $base The original entity and subentity.
 * @param WC_Order $order The order.
 * @return array
 */
function testing_multibanco_ifthen_base_ent_subent( $base, $order ) {
	// $base is a array with 'ent' and 'subent' keys / values
	// Test whatever you want here related to the $order object
	if ( true ) { // phpcs:ignore Generic.CodeAnalysis.UnconditionalIfStatement.Found
		// Change Entity and Subentity
		$base['ent']    = '99999';
		$base['subent'] = '999';
	}
	return $base;
}
add_filter( 'multibanco_ifthen_base_ent_subent', 'testing_multibanco_ifthen_base_ent_subent', 10, 2 );


/**
 * MB WAY - Use specific MB WAY Key for some specific order details
 * Example: depending on the delivery method, or the items bought, the payment must be made with different MB WAY Key
 *
 * @param string   $mbwaykey The original key.
 * @param WC_Order $order The order.
 * @return string
 */
function testing_multibanco_ifthen_base_mbwaykey( $mbwaykey, $order ) {
	// Test whatever you want here related to the $order object
	if ( true ) { // phpcs:ignore Generic.CodeAnalysis.UnconditionalIfStatement.Found
		// Change MB WAY Key
		$mbwaykey = 'XXX-999999';
	}
	return $mbwaykey;
}
add_filter( 'multibanco_ifthen_base_mbwaykey', 'testing_multibanco_ifthen_base_mbwaykey', 10, 2 );


/**
 * Multibanco - Action when the reference is generated
 *
 * @param array   $ref          The payment details.
 * @param integer $order_id     The order ID.
 * @param bool    $force_change If the new reference was forced to be changed.
 */
function my_multibanco_ifthen_created_reference( $ref, $order_id, $force_change ) {
	wp_mail( 'email@your.domain', 'Multibanco reference generated for #' . $order_id, 'Ent: ' . $ref['ent'] . ' Ref: ' . $ref['ref'] . ' ' . ( $force_change ? 'Re-generation was forced' : 'Re-generation was not forced' ) );
}
add_action( 'multibanco_ifthen_created_reference', 'my_multibanco_ifthen_created_reference', 10, 3 );


/**
 * MB WAY - Action when the reference is generated
 *
 * @param string  $id_pedido The payment reference id.
 * @param integer $order_id  The order ID.
 * @param string  $phone     The phone number.
 */
function my_mbway_ifthen_created_reference( $id_pedido, $order_id, $phone ) {
	wp_mail( 'email@your.domain', 'MB WAY reference generated for #' . $order_id, 'Id pedido: ' . $id_pedido . ' Phone: ' . $phone );
}
add_action( 'mbway_ifthen_created_reference', 'my_mbway_ifthen_created_reference', 10, 3 );


/**
 * Multibanco - Keep order pending instead of on-hold - Available on (private) Webdados Toolbox plugin
 *
 * @param bool    $set_on_hold If the order should be set on hold.
 * @param integer $order_id    The order ID.
 * @return bool
 */
function my_multibanco_ifthen_set_on_hold( $set_on_hold, $order_id ) {
	return false;
}
add_filter( 'multibanco_ifthen_set_on_hold', 'my_multibanco_ifthen_set_on_hold', 10, 2 );


/**
 * MB WAY - Set orders as on-hold instead of pending
 */
add_filter( 'mbway_ifthen_order_initial_status_pending', '__return_false' );


/**
 * Multibanco - Cancel orders if "Manage stock" and "Hold stock (minutes)" are configured - Be advised that the Multibanco reference will still be active and can be paid - Available on Webdados Toolbox plugin
 */
add_filter( 'multibanco_ifthen_cancel_unpaid_orders', '__return_true' );


/**
 * Multibanco - Restore stock on cancelled unpaid orders
 *
 * @param bool    $restore_stock If the stock should be restored.
 * @param integer $order_id      The order ID.
 * @return bool
 */
function my_multibanco_ifthen_cancel_unpaid_orders_restore_stock( $restore_stock, $order_id ) {
	return true;
}
add_filter( 'multibanco_ifthen_cancel_unpaid_orders_restore_stock', 'my_multibanco_ifthen_cancel_unpaid_orders_restore_stock', 10, 2 );


/**
 * Multibanco - Action when the unpaid orders is cancelled
 *
 * @param integer $order_id The order ID.
 */
function my_multibanco_ifthen_unpaid_order_cancelled( $order_id ) {
	wp_mail( 'email@your.domain', 'Multibanco unpaid order #' . $order_id . ' cancelled', 'Multibanco unpaid order #' . $order_id . ' cancelled' );
}
add_action( 'multibanco_ifthen_unpaid_order_cancelled', 'my_multibanco_ifthen_unpaid_order_cancelled' );


/**
 * Payshop - Cancel orders if "Manage stock" and "Hold stock (minutes)" are configured - Be advised that the Payshop reference will still be active and can be paid
 */
add_filter( 'payshop_ifthen_cancel_unpaid_orders', '__return_true' );


/**
 * Payshop - Restore stock on cancelled unpaid orders
 *
 * @param bool    $restore_stock If the stock should be restored.
 * @param integer $order_id      The order ID.
 * @return bool
 */
function my_payshop_ifthen_cancel_unpaid_orders_restore_stock( $restore_stock, $order_id ) {
	return true;
}
add_filter( 'payshop_ifthen_cancel_unpaid_orders_restore_stock', 'my_payshop_ifthen_cancel_unpaid_orders_restore_stock', 10, 2 );


/**
 * Payshop - Action when the unpaid orders is cancelled
 *
 * @param integer $order_id The order ID.
 */
function my_payshop_ifthen_unpaid_order_cancelled( $order_id ) {
	wp_mail( 'email@your.domain', 'Payshop unpaid order #' . $order_id . ' cancelled', 'Payshop unpaid order #' . $order_id . ' cancelled' );
}
add_action( 'payshop_ifthen_unpaid_order_cancelled', 'my_payshop_ifthen_unpaid_order_cancelled' );


/**
 * MB WAY - Cancel orders if "Manage stock" and "Hold stock (minutes)" are configured
 * Should not be needed as MB WAY status is pending and WooCommerce should take care of it by itself
 */
add_filter( 'mbway_ifthen_cancel_unpaid_orders', '__return_true' );


/**
 * MB WAY - Restore stock on cancelled unpaid orders
 *
 * @param bool    $restore_stock If the stock should be restored.
 * @param integer $order_id      The order ID.
 * @return bool
 */
function my_mbway_ifthen_cancel_unpaid_orders_restore_stock( $restore_stock, $order_id ) {
	return true;
}
add_filter( 'mbway_ifthen_cancel_unpaid_orders_restore_stock', 'my_mbway_ifthen_cancel_unpaid_orders_restore_stock', 10, 2 );


/**
 * MB WAY - Action when the unpaid orders is cancelled
 *
 * @param integer $order_id The order ID.
 */
function my_mbway_ifthen_unpaid_order_cancelled( $order_id ) {
	wp_mail( 'email@your.domain', 'MB WAY unpaid order #' . $order_id . ' cancelled', 'MB WAY unpaid order #' . $order_id . ' cancelled' );
}
add_action( 'mbway_ifthen_unpaid_order_cancelled', 'my_mbway_ifthen_unpaid_order_cancelled' );


/**
 * Multibanco - Do not add the payment instructions to the new order email
 *
 * @param bool    $add_instructions If the stock should be restored.
 * @param integer $order_id         The order ID.
 * @return bool
 */
function my_multibanco_ifthen_email_instructions_pending_send( $add_instructions, $order_id ) {
	return false;
}
add_filter( 'multibanco_ifthen_email_instructions_pending_send', 'my_multibanco_ifthen_email_instructions_pending_send', 10, 2 );


/**
 * MB WAY - Do not add the payment instructions to the new order email
 *
 * @param bool    $add_instructions If the stock should be restored.
 * @param integer $order_id         The order ID.
 * @return bool
 */
function my_mbway_ifthen_email_instructions_pending_send( $add_instructions, $order_id ) {
	return false;
}
add_filter( 'mbway_ifthen_email_instructions_pending_send', 'my_mbway_ifthen_email_instructions_pending_send', 10, 2 );


/**
 * Multibanco - Do not add the payment received message on the processing email
 *
 * @param bool    $add_instructions If the stock should be restored.
 * @param integer $order_id         The order ID.
 * @return bool
 */
function my_multibanco_ifthen_email_instructions_payment_received_send( $add_instructions, $order_id ) {
	return false;
}
add_filter( 'multibanco_ifthen_email_instructions_payment_received_send', 'my_multibanco_ifthen_email_instructions_payment_received_send', 10, 2 );


/**
 * MB WAY - Do not add the payment received message on the processing email
 *
 * @param bool    $add_instructions If the stock should be restored.
 * @param integer $order_id         The order ID.
 * @return bool
 */
function my_mbway_ifthen_email_instructions_payment_received_send( $add_instructions, $order_id ) {
	return false;
}
add_filter( 'mbway_ifthen_email_instructions_payment_received_send', 'my_mbway_ifthen_email_instructions_payment_received_send', 10, 2 );


/**
 * Multibanco - Add fields to settings screen
 *
 * @param array $fields The settings fields.
 * @return array
 */
function my_multibanco_ifthen_multibanco_settings_fields( $fields ) {
	$fields['some_text_field'] = array(
		'type'  => 'text',
		'title' => 'Some text field',
	);
	return $fields;
}


/**
 * MB WAY - Add fields to settings screen
 *
 * @param array $fields The settings fields.
 * @return array
 */
function my_multibanco_ifthen_mbway_settings_fields( $fields ) {
	$fields['some_text_field'] = array(
		'type'  => 'text',
		'title' => 'Some text field',
	);
	return $fields;
}
add_filter( 'multibanco_ifthen_mbway_settings_fields', 'my_multibanco_ifthen_mbway_settings_fields' );

