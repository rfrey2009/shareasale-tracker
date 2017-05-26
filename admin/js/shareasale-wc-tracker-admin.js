jQuery(document).ready(function() {

  jQuery(document).on('submit', '#generate-datafeed, #generate-datafeed-results > form', function(e) {
    e.preventDefault();
    /*
    I have to do this datastring because WP 'SSH SFTP Updater Support' plugin's JS code below is broken...
    if(typeof(Storage)!=="undefined" && localStorage.privateKeyFile) {
      jQuery("#private_key").val(localStorage.privateKeyFile);
    }
    is setting the fields' values to string 'undefined'...
    */
    var datastring = jQuery(this).find('input[value!="undefined"]').serialize();
    jQuery('#tracker-options-save').prop('disabled', true);

    jQuery.post( ajaxurl, datastring, function(data) {
      jQuery('#generate-datafeed-results').html(data);
      window.scrollTo(0,0);
      jQuery('#tracker-options-save').prop('disabled', false);
      if(!jQuery('#setting-error-datafeed-csv').length){        
        jQuery('.shareasale-wc-tracker-datafeeds-table').find('tbody > tr').first().fadeOut().fadeIn();
      }
    });
  });

  jQuery('#reconciliation-setting').click(function(){
    if(!this.checked){
      jQuery('#api-token').prop('disabled', true);
      jQuery('#api-secret').prop('disabled', true);
    }else if(this.checked){
      jQuery('#api-token').prop('disabled', false);
      jQuery('#api-secret').prop('disabled', false);
    }
  });

  jQuery('#analytics-setting').click(function(){
    if(!this.checked){
      jQuery('#analytics-passkey').prop('disabled', true);
    }else if(this.checked){
      jQuery('#analytics-passkey').prop('disabled', false);
    }
  });

  jQuery(document).on('click', '.shareasale-wc-tracker-datafeeds-error-count', function(e){
    e.preventDefault();
    jQuery(this).siblings('.shareasale-wc-tracker-datafeeds-error-message').toggleClass('shareasale-wc-tracker-datafeeds-error-message-hidden');
  });
});