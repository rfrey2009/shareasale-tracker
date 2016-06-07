<div id="shareasale-tracker">
<?php
	include_once 'options-head.php';

	if ( !current_user_can('manage_options') ) {
	  wp_die( 'You do not have sufficient permissions to access this page.' );
	}

?>
  <div>    
    <h2>ShareASale Tracker Settings</h2>
    <form action="options.php" method="post">
    <div id = 'tracker_options'>
    <?php
      settings_fields( 'tracker_options' );
      do_settings_sections( 'shareasale-tracker' );
    ?>     
    </div>
    <button id = "tracker_options_save" name="Submit">Save Settings</button>
    </form>
  </div> 
</div><!-- #shareasale-tracker -->