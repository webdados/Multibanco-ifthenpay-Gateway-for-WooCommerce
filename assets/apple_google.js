jQuery(
	function( $ ) {

		// Check if user can use Apple Pay
		function ifthenpay_check_apple_pay() {
			console.log( window.ApplePaySession );
			if ( window.ApplePaySession ) {
				console.log( 'ApplePaySession OK' );
				var canApplePay = ApplePaySession.canMakePayments();
				console.log( canApplePay );
				if ( canApplePay ) {
					// Show payment option
					console.log( 'ApplePaySession canMakePayments OK' );
				} else {
					console.log( 'ApplePaySession canMakePayments ERROR' );
				}
			} else {
				console.log( 'ApplePaySession ERROR' );
			}
		}

		function ifthenpay_check_google_pay() {
			
		}

		ifthenpay_check_apple_pay();

	}
);
