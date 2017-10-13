jQuery(document).ready(function() {
	jQuery( '#shareasale-wc-tracker-ftp-failed' ).on('click', '.notice-dismiss', function ( event ) {
	    event.preventDefault();
	    jQuery.post( ajaxurl, { action: 'shareasale_wc_tracker_ftp_failed_dismiss_notice' } );
	});
});