/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';
import React, { useEffect } from 'react';
//import { CART_STORE_KEY } from '@woocommerce/block-data';
//import { useSelect } from '@wordpress/data';

const settings = getSetting( 'cofidispay_ifthen_for_woocommerce_data', {} );
const defaultLabel = __(
	'Cofidis Pay (ifthenpay)',
	'multibanco-ifthen-software-gateway-for-woocommerce'
);
const label = decodeEntities( settings.title ) || defaultLabel;

/**
 * Notices
 */
// Testing notices
/*const { dispatch } = window.wp.data;
dispatch( 'core/notices' ).createErrorNotice(
	__(
		'Payment failed on the gateway. Please try again.',
		'multibanco-ifthen-software-gateway-for-woocommerce'
	),
	{ context: 'wc/checkout' }
);*/
// Testing getting data from the Store API - We need to do this only on page load and show the notice
/*const { cofidisFailedPayment } = useSelect(
	( select ) => select( 'wc/store/cart' ).getCartData().extensions.ifthenpay
);
console.log( cofidisFailedPayment );*/
//useEffect(() => {
//	console.log( 'useEffect' );
//});

/**
 * Content component
 *
 * @param {*} props Props from payment API.
 */
const Content = ( props ) => {
	// Only runs when the payment method is selected
	//console.log( props );
	// Are we returning from a failed payment?
	//const { extensions } = useSelect((select) => {
	//	const store = select(CART_STORE_KEY);
	//	const { extensions } = store.getCartData();
	//	return {
	//		extensions,
	//	};
	//});
	//console.log( extensions.ifthenpay.cofidisFailedPayment );
	//if ( extensions?.ifthenpay?.cofidisFailedPayment ) {
	//	console.log( extensions.ifthenpay.cofidisFailedPayment );
	//}
	// Description
	var description = React.createElement( 'div', null, decodeEntities( settings.description || '' ) );
	return description;
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = ( props ) => {
	var icon = React.createElement( 'img', { src: settings.icon, width: settings.icon_width, height: settings.icon_height, style: { display: 'inline' } } );
	var span = React.createElement( 'span', { className: 'wc-block-components-payment-method-label wc-block-components-payment-method-label--with-icon' }, icon, decodeEntities( settings.title ) || defaultLabel );
	return span;
};

/**
 * CanMakePayment function
 *
 * @param checkoutData Checkout details.
 */
const CanMakePayment = ( checkoutData ) => {
	// Error notice?
	var error_notice = checkoutData?.cart?.extensions?.ifthenpay?.cofidisFailedPayment;
	if ( error_notice ) {
		const { dispatch } = window.wp.data;
		dispatch( 'core/notices' ).createErrorNotice(
			error_notice,
			{ context: 'wc/checkout' }
		);
	}
	//Euro?
	if ( checkoutData.cartTotals.currency_code != 'EUR' ) {
		return false;
	}
	//Portugal?
	if ( settings.only_portugal ) {
		if ( checkoutData.billingData.country != 'PT' && checkoutData.shippingAddress.country != 'PT' ) {
			return false;
		}
	}
	//Minimum and maximum value
	var cart_total = checkoutData.cartTotals.total_price / 100; //It's return in cents (?)
	if ( settings.only_above ) {
		if ( cart_total < settings.only_above ) {
			return false;
		}
	}
	if ( settings.only_bellow ) {
		if ( cart_total > settings.only_bellow ) {
			return false;
		}
	}
	return true;
}

/**
 * Payshop payment method config object.
 */
const ifthenpayCofidisPaymentMethod = {
	name: 'cofidispay_ifthen_for_woocommerce',
	label: React.createElement( Label, null ),
	content: React.createElement( Content, null ),
	edit: React.createElement( Content, null ),
	icons: null,
	canMakePayment: CanMakePayment,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( ifthenpayCofidisPaymentMethod );
