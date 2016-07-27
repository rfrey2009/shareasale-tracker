jQuery(document).ready(function() {
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