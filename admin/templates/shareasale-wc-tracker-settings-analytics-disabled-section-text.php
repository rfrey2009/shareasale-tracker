<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}
$options     = get_option( 'shareasale_wc_tracker_options' );
$merchant_id = $options['merchant-id'];
$prefixed    = 'MID ' . $merchant_id;
?>
<style>
.nav-tab-analytics{
	border-color: red red #ccc red !important;
}
.nav-tab-analytics-active{
	border-color: red red #f1f1f1 red !important;
}
.shareasale-wc-tracker-advanced-analytics-warning{
	color: red;
}
</style>
<p>
<span class="shareasale-wc-tracker-advanced-analytics-warning">This is an advanced feature.</span>
<span>Contact the 
	<a href="mailto:shareasale@shareasale.com?Subject=ATTN%3A%20Tech%20Team%2C%20WooCommerce%20Plugin%20Advanced%20Analytics&body=<?php echo esc_attr( $prefixed ) ?> Interested in setting up advanced analytics." target="_blank">ShareASale Tech Team</a> to request a passkey.
</span>
</p>
<hr>
<p>Go beyond just basic sale tracking!</p>
<p>Advanced analytics allows ShareASale to capture when products are added to cart, coupons applied, checkout sessions started, and other events. Use these to fine tune your Affiliate attribution and commissions.</p>
<p>Already have a passkey? Check the box below to enable advanced analytics and then insert the passkey.</p>
