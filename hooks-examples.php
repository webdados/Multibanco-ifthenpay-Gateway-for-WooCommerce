<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// Multibanco - Hide non requested callback notice
add_filter( 'multibanco_ifthen_show_callback_notice', '__return_false' );


// MB WAY - Hide non requested callback notice
add_filter( 'mbway_ifthen_show_callback_notice', '__return_false' );


// Multibanco - Format Multibanco reference
add_filter( 'multibanco_ifthen_format_ref', 'my_multibanco_ifthen_format_ref' );
function my_multibanco_ifthen_format_ref( $ref ) {
	return str_replace( ' ', '', $ref ); //Remove spaces example
}


// MB WAY - Filter for the text shown on the mobile app
add_filter( 'mbway_ifthen_webservice_desc', 'my_mbway_ifthen_webservice_desc', 10, 2 );
function my_mbway_ifthen_webservice_desc( $text, $order_id ) {
	return 'Pay for Order #'.$order_id;
}


// Multibanco - Email payment instructions filter
add_filter( 'multibanco_ifthen_email_instructions_table_html', 'my_multibanco_ifthen_email_instructions_table_html', 1, 5 );
function my_multibanco_ifthen_email_instructions_table_html( $html, $ent, $ref, $order_total, $order_id ) {
	ob_start();
	?>
	<h2>Multibanco payment instructions for Order #<?php echo $order_id; ?></h2>
	<p>
		<b>Entity:</b> <?php echo $ent; ?>
		<br/>
		<b>Reference:</b> <?php echo WC_IfthenPay_Webdados()->format_multibanco_ref( $ref ); ?>
		<br/>
		<b>Value:</b> <?php echo $order_total; ?>
	</p>
	<p><?php
	//With WPML
	echo nl2br( function_exists( 'icl_object_id' ) ? icl_t( WC_IfthenPay_Webdados()->multibanco_id, WC_IfthenPay_Webdados()->multibanco_id.'_extra_instructions', WC_IfthenPay_Webdados()->multibanco_settings['extra_instructions'] ) : WC_IfthenPay_Webdados()->multibanco_settings['extra_instructions'] );
	?></p>
	<?php
	return ob_get_clean();
}


// MB WAY - Email payment instructions filter
add_filter( 'mbway_ifthen_email_instructions_table_html', 'my_mbway_ifthen_email_instructions_table_html', 1, 3 );
function my_mbway_ifthen_email_instructions_table_html( $html, $order_total, $order_id ) {
	ob_start();
	?>
	<h2>MB WAY payment instructions for Order #<?php echo $order_id; ?></h2>
	<p>
		<b>Value:</b> <?php echo $order_total; ?>
	</p>
	<p><?php
	//With WPML
	echo nl2br( function_exists( 'icl_object_id' ) ? icl_t( WC_IfthenPay_Webdados()->mbway_id, WC_IfthenPay_Webdados()->mbway_id.'_extra_instructions', WC_IfthenPay_Webdados()->mbway_settings['extra_instructions'] ) : WC_IfthenPay_Webdados()->mbway_settings['extra_instructions'] );
	?></p>
	<?php
	return ob_get_clean();
}


// Multibanco - Email payment received text filter
add_filter( 'multibanco_ifthen_email_instructions_payment_received', 'my_multibanco_ifthen_email_instructions_payment_received', 10, 2 );
function my_multibanco_ifthen_email_instructions_payment_received( $html, $order_id ) {
	//We can, for example, format and return just part of the text
	ob_start();
	?>
	<p style="color: #FF0000; font-weight: bold;">
		Multibanco payment received for order #<?php echo $order_id; ?>.
	</p>
	<?php
	return ob_get_clean();
}


// MB WAY - Email payment received text filter
add_filter( 'mbway_ifthen_email_instructions_payment_received', 'my_mbway_ifthen_email_instructions_payment_received', 10, 2 );
function my_mbway_ifthen_email_instructions_payment_received( $html ) {
	//We can, for example, format and return just part of the text
	ob_start();
	?>
	<p style="color: #FF0000; font-weight: bold;">
		MB WAY payment received for order #<?php echo $order_id; ?>.
	</p>
	<?php
	return ob_get_clean();
}


// Multibanco - Thank you page payment instructions filter
add_filter( 'multibanco_ifthen_thankyou_instructions_table_html', 'my_multibanco_ifthen_thankyou_instructions_table_html', 1, 5 );
function my_multibanco_ifthen_thankyou_instructions_table_html( $html, $ent, $ref, $order_total, $order_id ) {
	ob_start();
	?>
	<h2>Multibanco payment instructions for Order #<?php echo $order_id; ?></h2>
	<p>
		<b>Entity:</b> <?php echo $ent; ?>
		<br/>
		<b>Reference:</b> <?php echo WC_IfthenPay_Webdados()->format_multibanco_ref( $ref ); ?>
		<br/>
		<b>Value:</b> <?php echo $order_total; ?>
	</p>
	<p><?php
	//Without WPML
	echo WC_IfthenPay_Webdados()->multibanco_settings['extra_instructions'];
	?></p>
	<?php
	return ob_get_clean();
}


// MB WAY - Thank you page payment instructions filter
add_filter( 'mbway_ifthen_thankyou_instructions_table_html', 'my_mbway_ifthen_thankyou_instructions_table_html', 1, 3 );
function my_mbway_ifthen_thankyou_instructions_table_html( $html, $order_total, $order_id ) {
	ob_start();
	?>
	<h2>MB WAY payment instructions for Order #<?php echo $order_id; ?></h2>
	<p>
		<b>Value:</b> <?php echo $order_total; ?>
	</p>
	<p><?php
	//Without WPML
	echo WC_IfthenPay_Webdados()->mbway_settings['extra_instructions'];
	?></p>
	<?php
	return ob_get_clean();
}


// MB WAY - Disable Ajax checking on the order status on the Thank you page
add_filter( 'mbway_ifthen_enable_check_order_status_thankyou', 'my_mbway_ifthen_enable_check_order_status_thankyou', 10, 2 );
function my_mbway_ifthen_enable_check_order_status_thankyou( $bool, $order_id ) {
	return false;
}


// Multibanco - SMS Instructions filter
add_filter( 'multibanco_ifthen_sms_instructions', 'my_multibanco_ifthen_sms_instructions', 1, 5 );
function my_multibanco_ifthen_sms_instructions( $message, $ent, $ref, $order_total, $order_id ) {
	return 'Order #'.$order_id.' - Ent. '.$ent.' Ref. '.$ref.' Val. '.$order_total;
}


// Multibanco - Action when payment complete via callback
add_action( 'multibanco_ifthen_callback_payment_complete', 'my_multibanco_ifthen_callback_payment_complete', 10, 1 );
function my_multibanco_ifthen_callback_payment_complete( $order_id ) {
	wp_mail( 'email@domain', 'Multibanco order '.$order_id.' paid', 'Multibanco order '.$order_id.' paid' );
}


// MB WAY - Action when payment complete via callback
add_action( 'mbway_ifthen_callback_payment_complete', 'my_mbway_ifthen_callback_payment_complete', 10, 1 );
function my_mbway_ifthen_callback_payment_complete( $order_id ) {
	wp_mail( 'email@domain', 'MB WAY order '.$order_id.' paid', 'MB WAY order '.$order_id.' paid' );
}


// Multibanco - Callback call failed
add_action( 'multibanco_ifthen_callback_payment_failed', 'my_multibanco_ifthen_callback_payment_failed', 10, 1 );
function my_multibanco_ifthen_callback_payment_failed( $order_id ) {
	wp_mail( 'email@domain', 'Multibanco callback for order '.$order_id.' failed', 'Multibanco callback for order '.$order_id.' failed' );
}


// MB WAY - Callback call failed
add_action( 'mbway_ifthen_callback_payment_failed', 'my_mbway_ifthen_callback_payment_failed', 10, 1 );
function my_mbway_ifthen_callback_payment_failed( $order_id ) {
	wp_mail( 'email@domain', 'MB WAY callback for order '.$order_id.' failed', 'MB WAY callback for order '.$order_id.' failed' );
}


// Multibanco - Change the icon html
add_filter( 'woocommerce_gateway_icon', 'my_woocommerce_gateway_icon_mb', 1, 2 );
function my_woocommerce_gateway_icon_mb( $html, $id ) {
	if ( $id == WC_IfthenPay_Webdados()->multibanco_id ) {
		$html = 'No icon'; //Any html you want here
	}
	return $html;
}


// MB WAY - Change the icon html
add_filter( 'woocommerce_gateway_icon', 'my_woocommerce_gateway_icon_mbway', 1, 2 );
function my_woocommerce_gateway_icon_mbway( $html, $id ) {
	if ( $id == WC_IfthenPay_Webdados()->mbway_id ) {
		$html = 'No icon'; //Any html you want here
	}
	return $html;
}


// Multibanco - Use specific Entity and Subentity for some specific order details (Example: depending on the delivery method, or the items bought, the payment must be made with different Ent/Subent)
add_filter( 'multibanco_ifthen_base_ent_subent', 'testing_multibanco_ifthen_base_ent_subent', 10, 2 );
function testing_multibanco_ifthen_base_ent_subent( $base, $order ) {
	//$base is a array with 'ent' and 'subent' keys / values
	//Test whatever you want here related to the $order object
	if ( true ) {
		//Change Entity and Subentity
		$base['ent'] = '99999';
		$base['subent'] = '999';
	} else {
		//Just use the plugin settings
	}
	return $base;
}


// MB WAY - Use specific MB WAY Key for some specific order details (Example: depending on the delivery method, or the items bought, the payment must be made with different MB WAY Key)
add_filter( 'multibanco_ifthen_base_mbwaykey', 'testing_multibanco_ifthen_base_mbwaykey', 10, 2 );
function testing_multibanco_ifthen_base_mbwaykey( $mbwaykey, $order ) {
	//Test whatever you want here related to the $order object
	if ( true ) {
		//Change MB WAY Key
		$mbwaykey = 'XXX-999999';
	} else {
		//Just use the plugin settings
	}
	return $mbwaykey;
}


// Multibanco - Action when the reference is generated
add_action( 'multibanco_ifthen_created_reference', 'my_multibanco_ifthen_created_reference', 10, 3 );
function my_multibanco_ifthen_created_reference( $ref, $order_id, $force_change ) {
	wp_mail( 'email@domain', 'Multibanco reference generated for #'.$order_id, 'Ent: '.$ref['ent'].' Ref: '.$ref['ref'].' '.( $force_change ? 'Re-generation was forced' : 'Re-generation was not forced' ) );
}


// MB WAY - Action when the reference is generated
add_action( 'mbway_ifthen_created_reference', 'my_mbway_ifthen_created_reference', 10, 3 );
function my_mbway_ifthen_created_reference( $id_pedido, $order_id, $phone ) {
	wp_mail( 'email@domain', 'MB WAY reference generated for #'.$order_id, 'Id pedido: '.$id_pedido.' Phone: '.$phone );
}


// Multibanco - Keep order pending instead of on-hold - Available on (private) Webdados Toolbox plugin
add_filter( 'multibanco_ifthen_set_on_hold', 'my_multibanco_ifthen_set_on_hold', 10, 2 );
function my_multibanco_ifthen_set_on_hold( $bool, $order_id ) {
	return false;
}


// MB WAY - Set orders as on-hold instead of pending
add_filter( 'mbway_ifthen_order_initial_status_pending', '__return_false' );


// Multibanco - Cancel orders if "Manage stock" and "Hold stock (minutes)" are configured - Be advised that the Multibanco reference will still be active and can be paid - Available on Webdados Toolbox plugin
add_filter( 'multibanco_ifthen_cancel_unpaid_orders', '__return_true' );


// Multibanco - Restore stock on cancelled unpaid orders
add_filter( 'multibanco_ifthen_cancel_unpaid_orders_restore_stock', 'my_multibanco_ifthen_cancel_unpaid_orders_restore_stock', 10, 2 );
function my_multibanco_ifthen_cancel_unpaid_orders_restore_stock( $bool, $order_id ) {
	return true;
}


// Multibanco - Action when the unpaid orders is cancelled
add_action( 'multibanco_ifthen_unpaid_order_cancelled', 'my_multibanco_ifthen_unpaid_order_cancelled' );
function my_multibanco_ifthen_unpaid_order_cancelled( $order_id ) {
	wp_mail( 'email@domain', 'Multibanco unpaid order #'.$order_id.' cancelled', 'Multibanco unpaid order #'.$order_id.' cancelled' );
}


// MB WAY - Cancel orders if "Manage stock" and "Hold stock (minutes)" are configured - Be advised that the MB WAY reference will still be active and can be paid
add_filter( 'mbway_ifthen_cancel_unpaid_orders', '__return_true' );


// MB WAY - Restore stock on cancelled unpaid orders
add_filter( 'mbway_ifthen_cancel_unpaid_orders_restore_stock', 'my_mbway_ifthen_cancel_unpaid_orders_restore_stock', 10, 2 );
function my_mbway_ifthen_cancel_unpaid_orders_restore_stock( $bool, $order_id ) {
	return true;
}


// MB WAY - Action when the unpaid orders is cancelled
add_action( 'mbway_ifthen_unpaid_order_cancelled', 'my_mbway_ifthen_unpaid_order_cancelled' );
function my_mbway_ifthen_unpaid_order_cancelled( $order_id ) {
	wp_mail( 'email@domain', 'MB WAY unpaid order #'.$order_id.' cancelled', 'MB WAY unpaid order #'.$order_id.' cancelled' );
}


// Multibanco - Do not add the payment instructions to the new order email
add_filter( 'multibanco_ifthen_email_instructions_pending_send', 'my_multibanco_ifthen_email_instructions_pending_send', 10, 2 );
function my_multibanco_ifthen_email_instructions_pending_send( $bool, $order_id ) {
	return false;
}


// MB WAY - Do not add the payment instructions to the new order email
add_filter( 'mbway_ifthen_email_instructions_pending_send', 'my_mbway_ifthen_email_instructions_pending_send', 10, 2 );
function my_mbway_ifthen_email_instructions_pending_send( $bool, $order_id ) {
	return false;
}


// Multibanco - Do not add the payment received message on the processing email
add_filter( 'multibanco_ifthen_email_instructions_payment_received_send', 'my_multibanco_ifthen_email_instructions_payment_received_send', 10, 2 );
function my_multibanco_ifthen_email_instructions_payment_received_send( $bool, $order_id ) {
	return false;
}


// MB WAY - Do not add the payment received message on the processing email
add_filter( 'mbway_ifthen_email_instructions_payment_received_send', 'my_mbway_ifthen_email_instructions_payment_received_send', 10, 2 );
function my_mbway_ifthen_email_instructions_payment_received_send( $bool, $order_id ) {
	return false;
}


// Multibanco - Add fields to settings screen
add_filter( 'multibanco_ifthen_multibanco_settings_fields', 'my_multibanco_ifthen_multibanco_settings_fields' );
function my_multibanco_ifthen_multibanco_settings_fields( $fields ) {
	$fields['some_text_field'] = array(
		'type'	=> 'text',
		'title'	=> 'Some text field',
	);
	return $fields;
}


// MB WAY - Add fields to settings screen
add_filter( 'multibanco_ifthen_mbway_settings_fields', 'my_multibanco_ifthen_mbway_settings_fields' );
function my_multibanco_ifthen_mbway_settings_fields( $fields ) {
	$fields['some_text_field'] = array(
		'type'	=> 'text',
		'title'	=> 'Some text field',
	);
	return $fields;
}

