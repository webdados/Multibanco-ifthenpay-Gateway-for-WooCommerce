jQuery(
	function( $ ) {

		var order_id;
		var order_key;
		var interval          = cofidispay_ifthenpay.interval * 1000;
		var cofidispay_expire = ( parseInt( cofidispay_ifthenpay.cofidispay_minutes ) + 1 ) * 60 * 1000;
		var total_interval    = 0;

		function cofidispay_ifthenpay_order_check_status_init() {
			order_id  = $( '#cofidispay-order-id' ).val();
			order_key = $( '#cofidispay-order-key' ).val();
			setTimeout(
				function(){
					cofidispay_ifthenpay_order_check_status();
				},
				interval
			);
		}

		function cofidispay_ifthenpay_order_check_status() {
			total_interval = total_interval + interval;
			console.log( 'Checking Cofidis Pay payment status, after ' + interval + 'ms (total: ' + total_interval + 'ms)' );
			var data = {
				action: 'wc_cofidispay_ifthenpay_order_status',
				order_id: order_id,
				order_key: order_key
			};
			$.post(
				woocommerce_params.ajax_url,
				data,
				function( response ) {
					var response = JSON.parse( response );
					console.log( 'Status: ' + response.order_status );
					if ( response.order_status && ( response.order_status == 'processing' || response.order_status == 'completed' ) ) {
						  // DONE
						  location.reload();
					} else {
						interval = Math.round( interval * 1.2 );
						if ( total_interval <= cofidispay_expire ) {
							setTimeout(
								function(){
									cofidispay_ifthenpay_order_check_status();
								},
								interval
							);
						} else {
							console.log( 'Stopped checking Cofidis Pay payment status, after ' + total_interval + 'ms' );
						}
					}
				}
			);
		}

		if ( $( '.cofidispay_ifthen_for_woocommerce_table' ).length ) {
			cofidispay_ifthenpay_order_check_status_init();
		}

	}
);
