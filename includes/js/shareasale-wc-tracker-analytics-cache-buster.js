jQuery(document).on("added_to_cart", function(){
	if(sessionStorage.wc_fragments){
		var fragments = JSON.parse(sessionStorage.wc_fragments);
		delete fragments['#shareasale-wc-tracker-analytics-add-to-cart-ajax-model'];
		delete fragments['#shareasale-wc-tracker-analytics-add-to-cart-ajax'];
		delete fragments['#shareasale-wc-tracker-analytics-add-to-cart-ajax-cb'];
		sessionStorage.setItem( wc_cart_fragments_params.fragment_name, JSON.stringify( fragments ) );
	}
	console.log('cache busted!');
});