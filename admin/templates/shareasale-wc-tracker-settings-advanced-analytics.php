<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div id="shareasale-wc-tracker">
	<div class="wrap">    
		<h2>
			<img id="shareasale-logo" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../images/star_logo.png' ) ?>">
			ShareASale WooCommerce Tracker Settings
		</h2>
		<h2 class="nav-tab-wrapper">
	    	<a href="?page=shareasale_wc_tracker" class="nav-tab">
	    		Tracking Settings
	    	</a>
	    	<a href="?page=shareasale_wc_tracker_automatic_reconciliation&amp;page_num=1" class="nav-tab">
	    		Automatic Reconciliation
	    	</a>
	    	<a href="?page=shareasale_wc_tracker_datafeed_generation&amp;page_num=1" class="nav-tab">
	    		Datafeed Generation
	    	</a>
	    	<!-- <a href="?page=shareasale_wc_tracker_advanced_analytics" class="nav-tab nav-tab-active nav-tab-analytics nav-tab-analytics-active">
	    		Advanced Analytics
	    	</a> -->
		</h2>
		<form action="options.php" method="post">
			<div id="shareasale-wc-tracker-options">
			<?php
			  settings_fields( 'shareasale_wc_tracker_options' );
			  do_settings_sections( 'shareasale_wc_tracker_advanced_analytics' );
			?>     
			</div>
			<button id="tracker-options-save" class="button" name="Submit">Toggle advanced analytics</button>
		</form>
	</div> 
</div>
