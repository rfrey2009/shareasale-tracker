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

    var datastring = jQuery(this).find('input[value!="undefined"]').serialize();

    jQuery.post( ajaxurl, datastring, function(data) {
      jQuery('#generate-datafeed-results').html(data);
      jQuery('#tracker-options-save').prop('disabled', false);
      jQuery('#generate-datafeed-confirmation').css('display', 'inline');
      window.scrollTo(0,0);
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
});