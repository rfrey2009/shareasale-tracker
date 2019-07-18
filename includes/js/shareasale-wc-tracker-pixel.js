try {
 	var shareasaleWcTrackerPixelImg     = new Image();
	shareasaleWcTrackerPixelImg.id      = shareasaleWcTrackerPixel.id;
	//shareasaleWcTrackerPixelImg.onload  = shareasaleWcTrackerTriggered; //fn defined in shareasale-wc-tracker-triggered.js
	shareasaleWcTrackerPixelImg.src     = shareasaleWcTrackerPixel.src;
	shareasaleWcTrackerPixelImg.width   = 1;
	shareasaleWcTrackerPixelImg.height  = 1;
	shareasaleWcTrackerPixelImg.setAttribute('data-no-lazy', 1);
	document.body.appendChild(shareasaleWcTrackerPixelImg);
	shareasaleWcTrackerTriggered();
} //this is necessary because some optimization/caching scripts disrupt WP localized data found in <script> tags, so shareasaleWcTrackerPixelImg assignments above fail.
catch (e) {
	console.log('ShareASale JS failed because ' + e + ', so unwrapped <noscript> as a fallback');
	var shareasaleWcTrackerPixelFallback = document.querySelector('#_SHRSL_noscript_1').textContent;
	document.body.insertAdjacentHTML('beforeend', shareasaleWcTrackerPixelFallback);
	//jQuery('body').append(jQuery('noscript#_SHRSL_noscript_1').text());
}