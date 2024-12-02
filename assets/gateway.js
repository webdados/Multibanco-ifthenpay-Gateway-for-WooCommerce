jQuery(
	function( $ ) {

		var order_id;
		var order_key;
		var interval       = gateway_ifthenpay.interval * 1000;
		var gateway_expire = ( parseInt( gateway_ifthenpay.gateway_minutes ) + 1 ) * 60 * 1000;
		var total_interval = 0;

		function gateway_ifthen_order_check_status_init() {
			order_id  = $( '#gatewayifthenpay-order-id' ).val();
			order_key = $( '#gatewayifthenpay-order-key' ).val();
			setTimeout(
				function(){
					gateway_ifthen_order_check_status();
				},
				interval
			);
		}

		function gateway_ifthen_order_check_status() {
			total_interval = total_interval + interval;
			console.log( 'Checking Gateway IfthenPay payment status, after ' + interval + 'ms (total: ' + total_interval + 'ms)' );
			var data = {
				action: 'wc_gateway_ifthenpay_order_status',
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
						if ( total_interval <= gateway_expire ) {
							setTimeout(
								function(){
									gateway_ifthen_order_check_status();
								},
								interval
							);
						} else {
							console.log( 'Stopped checking MB WAY payment status, after ' + total_interval + 'ms' );
						}
					}
				}
			);
		}

		if ( $( '.gateway_ifthen_ifthen_for_woocommerce_table' ).length ) {
			gateway_ifthen_order_check_status_init();
		}

	}
);
