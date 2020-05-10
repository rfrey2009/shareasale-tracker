//observe product removals, product restores, updates, and empties of cart
//custom WordPress actions for AJAX are named after their WooCommerce hooks (woocommerce_cart_item_removed and woocommerce_update_cart_action_cart_updated)
console.log('observing!');

jQuery(document).ready(function() {
	//item restored, somewhat fragile since there's no good WooCommerce selector for the undo item link...
	/*
	jQuery( document ).on(
		'click',
		'a[href*="undo_item"]',
		function(e){
			e.preventDefault();
			console.log('observer saw that restore!');
			jQuery.get( shareasaleWcTrackerAnalyticsCartObserver.ajaxurl, { action: 'shareasale_wc_tracker_cart_item_restored' }, function(data) {
      			console.log(data);
   			});
		} );
	*/

	//any cart update
	jQuery( document ).on(
		'updated_wc_div removed_from_cart',
		function(e){
			//somewhat hacky way of stopping analytics script injection on simple shipping cost calculations, coupon applies or errors...
			if( jQuery('.woocommerce-error').length ){ return; } 
			if( jQuery('div.woocommerce-info:contains("Shipping costs updated")').length > 0 ){ return; }
			if( jQuery('div.woocommerce-message:contains("Coupon code applied successfully")').length > 0 || jQuery('div.woocommerce-message:contains("Coupon has been removed")').length > 0 ) { return; }
			jQuery.get( shareasaleWcTrackerAnalyticsCartObserver.ajaxurl, { action: 'shareasale_wc_tracker_update_cart_action_cart_updated' }, function(data) {
				console.log('observer saw that cart update!');
    			jQuery.each( data, function( key, value ) {
					jQuery(key).replaceWith(value);
				});
   			});
		});
});