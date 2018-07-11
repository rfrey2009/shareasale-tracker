var shareasaleWcTrackerSSCID    = shareasaleWcTrackerGetParameterByName( 'sscid' );
if( shareasaleWcTrackerSSCID ) {
	shareasaleWcTrackerSetCookie(
		'shareasaleWcTrackerSSCID',
		shareasaleWcTrackerSSCID,
		946707780000, //30 years
		'/'
	);
}

function shareasaleWcTrackerSetCookie(name, value, ms, path, domain) {
	if (!name || !value) {
		return;
	}
	var d;
	var cpath = path ? '; path=' + path : '';
	var cdomain = domain ? '; domain=' + domain : '';
	var expires = '';
	if (ms) {
		d = new Date();
		d.setTime(d.getTime() + ms);
		expires = '; expires=' + d.toUTCString();
	}
	document.cookie = name + "=" + value + expires + cpath + cdomain;
}

function shareasaleWcTrackerGetParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}