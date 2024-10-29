jQuery(
	function( $ ) {

		console.log( apple_google_ifthenpay );

		// Check if user can use Apple Pay
		function ifthenpay_check_apple_pay() {
			console.log( window.ApplePaySession );
			if ( window.ApplePaySession ) {
				var canApplePay = ApplePaySession.canMakePayments();
				if ( canApplePay ) {
					return true;
				}
			}
			return false;
		}

		// Check if user can use Google Pay
		function ifthenpay_check_google_pay() {
			// https://developers.google.com/pay/api/web/guides/resources/demos - Not working
			/*const paymentsClient = getGooglePaymentsClient();
			paymentsClient.isReadyToPay( getGoogleIsReadyToPayRequest() )
				.then( function( response ) {
					if ( response.result ) {
						return true;
					}
				})
				.catch( function( err ) {
					// show error in developer console for debugging
					console.error(err);
	  			} );*/
			return true;
		}

		$( '#google-pay-js').on( 'load', function() {
			console.log('google loaded');
		});

		// We need to first know if the gateway has a Apple or Google Pay entity chosen and only run validations depending on that
		var available_methods = [];
		if ( apple_google_ifthenpay.apple.enabled ) {
			if ( ifthenpay_check_apple_pay() ) {
				available_methods.push( 'apple' );
			}
		}
		if ( apple_google_ifthenpay.google.enabled ) {
			if ( ifthenpay_check_google_pay() ) {
				available_methods.push( 'google' );
			}
		}
		console.log( available_methods );

		$( document.body ).on( 'updated_checkout', function() {

			// Manipulate payment method option - If === 2 we don't do anything?
			if ( available_methods.length === 0 ) {
				// Remove payment method
				$( '.payment_method_apple_google_ifthen_for_woocommerce' ).remove();
			} else if ( available_methods.length === 1 ) {
				// Change title and icon
				if ( available_methods[0] === 'apple' ) {
					console.log( 'apple' );
					$( 'label[for=payment_method_apple_google_ifthen_for_woocommerce]' ).html( $( 'label[for=payment_method_apple_google_ifthen_for_woocommerce]' ).html().replace( apple_google_ifthenpay.general.method_title, apple_google_ifthenpay.apple.method_title ) );
				} else if ( available_methods[0] === 'google' ) {
					console.log( 'google' );
					$( 'label[for=payment_method_apple_google_ifthen_for_woocommerce]' ).html( $( 'label[for=payment_method_apple_google_ifthen_for_woocommerce]' ).html().replace( apple_google_ifthenpay.general.method_title, apple_google_ifthenpay.google.method_title ) );
				}
			}

		} );

	}
);