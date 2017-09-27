function shareasale_wc_tracker_triggered(){
	jQuery.post( shareasaleWcTrackerTriggered.ajaxurl,
		{ action: 'shareasale_wc_tracker_triggered', post_id: shareasaleWcTrackerTriggered.post_id },
		function(data) {
			if(data.order_id){
				console.log('Marked ' + data.order_id + ' as ShareASale pixel triggered!');
			}else{
				console.log('Not marked as ShareASale pixel triggered! Order_id/Post_id was not valid. Check shareasaleWcTrackerTriggered.post_id object property.');
			}
		},
	);
}