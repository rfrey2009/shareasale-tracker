function shareasaleWcTrackerTriggered(){
	jQuery('#_SHRSL_img_1').after('<script type="text/javascript" src="https://shareasale-analytics.com/j.js" defer async data-noptimize></script>');
	jQuery.post( shareasaleWcTrackerTriggeredData.ajaxurl,
		{ action: 'shareasale_wc_tracker_triggered', post_id: shareasaleWcTrackerTriggeredData.post_id, nonce: shareasaleWcTrackerTriggeredData.nonce },
		function(data) {
			if(data.order_id){
				console.log('Marked ' + data.order_id + ' as ShareASale pixel triggered!');
			}else{
				console.log('Not marked as ShareASale pixel triggered! Order_id/Post_id was not valid. Check shareasaleWcTrackerTriggeredData.post_id object property.');
			}
		},
	);
}