<div id="shareasale-tracker">
<?php
include_once 'options-head.php';

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You do not have sufficient permissions to access this page.' );
}
?>
	<div>    
		<h2><img id = "shareasale-logo" src = <?php echo '"' . plugin_dir_url( __FILE__ ) . '../images/star_logo.png"' ?>> ShareASale Tracker Settings</h2>
		<form action="options.php" method="post">
			<div id = 'tracker-options'>
			<?php
			  settings_fields( 'tracker_options' );
			  do_settings_sections( 'shareasale-tracker' );
			?>     
			</div>
			<button id = "tracker-options-save" name="Submit">Save Settings</button>
		</form>
	</div> 
</div><!-- #shareasale-tracker -->
