<div id = "shareasale-tracker">
	<div class = "wrap">    
		<h2>
			<img id = "shareasale-logo" src = "<?php echo plugin_dir_url( __FILE__ ) . '../images/star_logo.png' ?>">
			ShareASale Tracker Settings
		</h2>
		<h2 class = "nav-tab-wrapper">
	    	<a href = "?page=shareasale_tracker" class = "nav-tab">
	    		Tracking Settings
	    	</a>
	    	<a href = "?page=shareasale_tracker_automatic_reconciliation&amp;page_num=1" class = "nav-tab nav-tab-active">
	    		Automatic Reconciliation
	    	</a>
		</h2>
		<form action = "options.php" method = "post">
			<div id = 'tracker-options'>
			<?php
			  settings_fields( 'tracker_options' );
			  do_settings_sections( 'shareasale_tracker_automatic_reconciliation' );
			?>     
			</div>
			<button id = "tracker-options-save" name = "Submit">Save Settings</button>
		</form>
	</div>
</div>
