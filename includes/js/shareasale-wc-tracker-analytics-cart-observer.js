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
		'updated_wc_div',
		function(e){
			console.log('observer saw that cart update!');
			//somewhat hacky way of stopping analytics script injection on simple shipping cost calculations and coupon applies...
			if( jQuery('div.woocommerce-info').text() == 'Shipping costs updated.' ){ return; }
			if( jQuery('div.woocommerce-message').text() == 'Coupon code applied successfully.' || jQuery('div.woocommerce-message').text() == 'Coupon has been removed.' ) { return; }
			jQuery.get( shareasaleWcTrackerAnalyticsCartObserver.ajaxurl, { action: 'shareasale_wc_tracker_update_cart_action_cart_updated' }, function(data) {
    			jQuery.each( data, function( key, value ) {
					jQuery(key).replaceWith(value);
				});
   			});
		} );
});