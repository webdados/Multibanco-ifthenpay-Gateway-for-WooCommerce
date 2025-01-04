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

const settings = getSetting( 'gateway_ifthen_ifthen_for_woocommerce_data', {} );
const defaultLabel = __(
	'ifthenpay Gateway',
	'multibanco-ifthen-software-gateway-for-woocommerce'
);
const label = decodeEntities( settings.title ) || defaultLabel;

/**
 * Content component
 *
 * @param {*} props Props from payment API.
 */
const Content = ( props ) => {
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
	var error_notice = checkoutData?.cart?.extensions?.ifthenpay?.gatewayFailedPayment;
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
const ifthenpayGatewayPaymentMethod = {
	name: 'gateway_ifthen_ifthen_for_woocommerce',
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

registerPaymentMethod( ifthenpayGatewayPaymentMethod );
