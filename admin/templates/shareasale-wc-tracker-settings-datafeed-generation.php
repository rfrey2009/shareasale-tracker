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
			<a href="?page=shareasale_wc_tracker_datafeed_generation&amp;page_num=1" class="nav-tab nav-tab-active">
				Datafeed Generation
			</a>
			<!-- <a href="?page=shareasale_wc_tracker_advanced_analytics" class="nav-tab nav-tab-analytics">
	    		Advanced Analytics
	    	</a> -->
		</h2>
		<form action="options.php" method="post">
			<h2>Product Datafeed</h2>
			<p>Use this tool to automatically export your products into ShareASale's product datafeed .csv format.</p>
			<div>
			<?php
			  settings_fields( 'shareasale_wc_tracker_options' );
			  do_settings_sections( 'shareasale_wc_tracker_datafeed_generation' );
			?>     
			</div>
			<button class="button" name="Submit">Save Settings</button>
		</form>
		<hr>
		<form id="generate-datafeed" action="" method="post">
			<div id="shareasale-wc-tracker-options">
			  <input class="shareasale-wc-tracker-option-hidden" type="hidden" name="action" value="shareasale_wc_tracker_generate_datafeed">
			<?php wp_nonce_field( 'generate-datafeed', '_sas_wc_gdf', false ) ?>
			</div>
			<button id="tracker-options-save" class="button" name="Submit">Generate Datafeed</button>
		</form>
		<div id="generate-datafeed-results">
		<?php
			require_once plugin_dir_path( __FILE__ ) . 'shareasale-wc-tracker-settings-datafeed-generation-table.php';
			require_once plugin_dir_path( __FILE__ ) . 'shareasale-wc-tracker-settings-datafeed-generation-pagination.php';
		?>
		</div>
	</div>
</div>
