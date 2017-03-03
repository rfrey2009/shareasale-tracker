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
	border-color: green green #ccc green !important;
}
.nav-tab-analytics-active{
	border-color: green green #f1f1f1 green !important;
}
</style>
<hr>
<p>Advanced analytics is enabled for your account. Go to ShareASale.com to login, then Tools >> Conversion Lines to configure your Affiliate attribution and commission strategy.</p>
<p>If you have any questions, contact <a href="mailto:shareasale@shareasale.com?Subject=ATTN%3A%20Client%20Services%20Team%2C%20Conversion%20Lines&body=<?php echo esc_attr( $prefixed ) ?> I need assistance with my Conversion Lines setup." target="_blank">ShareASale Client Services Team</a> for assistance.