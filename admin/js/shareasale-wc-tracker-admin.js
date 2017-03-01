jQuery(document).ready(function() {

  jQuery(document).on('submit', '#generate-datafeed, #generate-datafeed-results > form', function(e) {
    e.preventDefault();
    /*
    I have to do this because WP 'SSH SFTP Updater Support' plugin's JS code below is broken...
    if(typeof(Storage)!=="undefined" && localStorage.privateKeyFile) {
      jQuery("#private_key").val(localStorage.privateKeyFile);
    }
    is setting the fields' values to string 'undefined'...
    */
    jQuery('#tracker-options-save').prop('disabled', true);
    jQuery('#generate-datafeed-confirmation').css('display', 'none');

    var datastring = jQuery(this).find('input[value!="undefined"]').serialize();

    jQuery.post( ajaxurl, datastring, function(data) {
      jQuery('#generate-datafeed-results').html(data);
      window.scrollTo(0,0);
      jQuery('#tracker-options-save').prop('disabled', false);
      jQuery('#generate-datafeed-confirmation').css('display', 'inline');
      jQuery('.shareasale-wc-tracker-datafeeds-table').find('tbody > tr').first().fadeOut().fadeIn();

    });

  });

  //disable customization controls if toolbar not enabled
  jQuery('#reconciliation-setting').click(function(){
    if(!this.checked){
      jQuery('#api-token').prop('disabled', true);
      jQuery('#api-secret').prop('disabled', true);
    }else if(this.checked){
      jQuery('#api-token').prop('disabled', false);
      jQuery('#api-secret').prop('disabled', false);
    }
  });

  jQuery(document).on('click', '.shareasale-wc-tracker-datafeeds-error-count', function(e){
    e.preventDefault();
    jQuery(this).siblings('.shareasale-wc-tracker-datafeeds-error-message').toggleClass('shareasale-wc-tracker-datafeeds-error-message-hidden');
  });
});