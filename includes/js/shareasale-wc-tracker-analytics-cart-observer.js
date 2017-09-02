//observe product removals, product restores, updates, and empties of cart
console.log('observing!');

jQuery(document).ready(function() {

	//remove
	jQuery( document ).on(
		'click',
		'.woocommerce-cart-form .product-remove > a',
		function(e){
			console.log('observer saw that removal!');
			//jQuery.get( ajaxurl, '', function(data) {
      
   			//});
		} );

	//restore

	//update
	jQuery( document ).on(
		'wc_update_cart',
		function(e){
			console.log('observer saw that update!');
			//jQuery.get( ajaxurl, '', function(data) {
      
   			//});
		} );

	//empty
});