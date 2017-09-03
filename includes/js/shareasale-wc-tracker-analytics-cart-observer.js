//observe product removals, product restores, updates, and empties of cart
//custom WordPress actions for AJAX are named after their WooCommerce hooks (woocommerce_cart_item_removed and woocommerce_update_cart_action_cart_updated)
console.log('observing!');

jQuery(document).ready(function() {
	//remove
	jQuery( document ).on(
		'click',
		'.woocommerce-cart-form .product-remove > a',
		function(e){
			console.log('observer saw that removal!');
			jQuery.get( ajaxurl, { action: 'shareasale_wc_tracker_cart_item_removed' }, function(data) {
    			jQuery.each( data, function(key, value ) {
					jQuery(key).replaceWith(value);
				});
   			});
		} );

	//restore, somewhat fragile since there's no good WooCommerce selector for the undo item link...
	/*
	jQuery( document ).on(
		'click',
		'a[href*="undo_item"]',
		function(e){
			e.preventDefault();
			console.log('observer saw that restore!');
			jQuery.get( ajaxurl, { action: 'shareasale_wc_tracker_update_cart_action_cart_updated' }, function(data) {
      			console.log(data);
   			});
		} );
	*/

	//update
	jQuery( document ).on(
		'click',
		'input[name="update_cart"]',
		function(e){
			console.log('observer saw that update!');
			jQuery.get( ajaxurl, { action: 'shareasale_wc_tracker_update_cart_action_cart_updated' }, function(data) {
    			jQuery.each( data, function(key, value ) {
					jQuery(key).replaceWith(value);
				});
   			});
		} );
});