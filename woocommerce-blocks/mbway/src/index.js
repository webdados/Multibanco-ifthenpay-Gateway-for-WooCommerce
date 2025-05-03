/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';
import { useEffect, useState } from 'react';
import { applyFilters } from '@wordpress/hooks';

const settings = getSetting( 'mbway_ifthen_for_woocommerce_data', {} );
const defaultLabel = __(
	'MB WAY mobile payment',
	'multibanco-ifthen-software-gateway-for-woocommerce'
) + ' (ifthenpay)';
const label = decodeEntities( settings.title ) || defaultLabel;

//const [mbwayPhoneNumber, setMbwayPhoneNumber] = useState(''); //If I set it here, i get "invalid hook call"

/**
 * Content component
 *
 * @param {*} props Props from payment API.
 */
const Content = ( props ) => {
	/* Data to send to the server - https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/internal-developers/block-client-apis/checkout/checkout-api.md#passing-a-value-from-the-client-through-to-server-side-payment-processing */
	const [ mbwayPhoneNumber, setMbwayPhoneNumber ] = useState( settings.default_number ); // This works but mbwayPhoneNumber is not available inside onPaymentProcessing below
	const { eventRegistration, emitResponse } = props;
	const { onPaymentProcessing } = eventRegistration;
	useEffect( () => {
		const unsubscribe = onPaymentProcessing( async () => {
			// Here we can do any processing we need, and then emit a response.
			// For example, we might validate a custom field, or perform an AJAX request, and then emit a response indicating it is valid or not.
			const mbway_ifthen_for_woocommerce_phone = mbwayPhoneNumber; // This will need to be the value of the input field
			const customDataIsValid = ( mbway_ifthen_for_woocommerce_phone.length === 9 );

			if ( customDataIsValid ) {
				return {
					type: emitResponse.responseTypes.SUCCESS,
					meta: {
						paymentMethodData: applyFilters( 'mbway_ifthen_blocks_checkout_payment_data', {
							mbway_ifthen_for_woocommerce_phone,
						} ),
					},
				};
			}

			return {
				type: emitResponse.responseTypes.ERROR,
				message: __(
					'Invalid MB WAY phone number',
					'multibanco-ifthen-software-gateway-for-woocommerce'
				),
			};
		} );
		// Unsubscribes when this component is unmounted.
		return () => {
			unsubscribe();
		};
	}, [
		emitResponse.responseTypes.ERROR,
		emitResponse.responseTypes.SUCCESS,
		onPaymentProcessing,
		mbwayPhoneNumber
	] );
	/* Input value */
	const HandleMBWayChange = ( event ) => {
		const value = event.target.value.replace(/\D/g, "");
		setMbwayPhoneNumber( value );
	};
	/* Content */
	// Description
	var description = React.createElement( 'p', null, decodeEntities( settings.description || '' ) );
	// Input field
	var phonenumberinput = React.createElement( 'input', {
		type:         'tel',
		name:         settings.id+'_phone',
		id:           settings.id+'_phone',
		placeholder:  '9xxxxxxxx',
		autoComplete: 'off',
		maxLength:    '9',
		required:     true,
		value:        mbwayPhoneNumber,
		onChange:     HandleMBWayChange
	} );
	// Label inside field
	var phonenumberlabel = React.createElement( 'label', {
		htmlFor: settings.id + '_phone'
	}, decodeEntities( settings.phonenumbertext || '' ) );
	// Extend before phone number
	var beforePhoneNumber = applyFilters( 'mbway_ifthen_blocks_checkout_before_phone_number', null );
	// Phone number: input + label
	var phonenumber = React.createElement( 'div', {
		className: 'wc-block-components-text-input is-active'
	}, '', phonenumberinput, phonenumberlabel );
	// Extend after phone number
	var afterPhoneNumber = applyFilters( 'mbway_ifthen_blocks_checkout_after_phone_number', null );
	// Return Content
	return React.createElement( 'div', null, description, beforePhoneNumber, phonenumber, afterPhoneNumber );
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
 * MBWAY payment method config object.
 */
const ifthenpayMbWayPaymentMethod = {
	name: 'mbway_ifthen_for_woocommerce',
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

registerPaymentMethod( ifthenpayMbWayPaymentMethod );
