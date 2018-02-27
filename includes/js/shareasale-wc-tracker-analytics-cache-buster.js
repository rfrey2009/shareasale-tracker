var fragment_name = wc_cart_fragments_params.fragment_name;
if(sessionStorage[fragment_name]){
	var fragments = JSON.parse(sessionStorage[fragment_name]);
	delete fragments['#shareasale-wc-tracker-analytics-add-to-cart-ajax-model'];
	delete fragments['#shareasale-wc-tracker-analytics-add-to-cart-ajax'];
	delete fragments['#shareasale-wc-tracker-analytics-add-to-cart-ajax-cb'];
	sessionStorage.setItem( fragment_name, JSON.stringify( fragments ) );
    //jQuery( document.body ).trigger( 'wc_fragment_refresh' );
    document.body.dispatchEvent(new Event('wc_fragment_refresh'));
	console.log('cache busted!');
}