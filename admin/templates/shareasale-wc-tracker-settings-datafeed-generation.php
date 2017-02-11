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
			<a href="?page=shareasale_wc_tracker_datafeed_generation" class="nav-tab nav-tab-active">
				Datafeed Generation
			</a>
		</h2>
		<form action="admin-post.php" method="post">
			<div id="shareasale-wc-tracker-options">
			  <h2>Product Datafeed</h2>
			  <p>Use this tool to automatically export your products into ShareASale's product datafeed .csv format.</p>
			  <input id="datafeed-action-hidden" class="shareasale-wc-tracker-option-hidden" type="hidden" name="action" value="generate_datafeed" size="1">
			<?php wp_nonce_field( 'generate-datafeed', '_wpnonce', false ) ?>
			</div>
			<button id="tracker-options-save" class="button" name="Submit">Generate Datafeed</button>
		</form>
	</div>
</div>
