function shareasaleWcTrackerPostAjax(url, data, success) {
    var params = typeof data == 'string' ? data : Object.keys(data).map(
            function(k){ return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]) }
        ).join('&');

    var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
    xhr.open('POST', url);
    xhr.onreadystatechange = function() {
        if (xhr.readyState>3 && xhr.status==200) { success(JSON.parse(xhr.responseText)); }
    };
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send(params);
    return xhr;
}

function shareasaleWcTrackerTriggered(){
	/*
	getting rid of second chancel pixel in 1.4.5. Will be triggered by Awin's master tag
	var shareasaleWcTrackerPixelImg = document.querySelector('#_SHRSL_img_1');
	var shareasaleWcTrackerPixelSecondChance = document.createElement('script');
	shareasaleWcTrackerPixelSecondChance.setAttribute('type','text/javascript');
	shareasaleWcTrackerPixelSecondChance.src = 'https://shareasale-analytics.com/j.js';
	shareasaleWcTrackerPixelSecondChance.setAttribute('defer', 1);
	shareasaleWcTrackerPixelSecondChance.setAttribute('async', 1);
	shareasaleWcTrackerPixelSecondChance.setAttribute('data-noptimize', 1);

	shareasaleWcTrackerPixelImg.parentNode.insertBefore(shareasaleWcTrackerPixelSecondChance,shareasaleWcTrackerPixelImg.nextSibling);
	*/
	shareasaleWcTrackerPostAjax(shareasaleWcTrackerTriggeredData.ajaxurl,
		{ action: 'shareasale_wc_tracker_triggered', post_id: shareasaleWcTrackerTriggeredData.post_id, nonce: shareasaleWcTrackerTriggeredData.nonce },
		function(data) {
			if(data.order_id){
				console.log('Marked ' + data.order_id + ' as ShareASale pixel triggered!');
			}else{
				console.log('Not marked as ShareASale pixel triggered! Order_id/Post_id was not valid. Check shareasaleWcTrackerTriggeredData.post_id object property.');
			}
		}
	);
	/*
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
	*/
}