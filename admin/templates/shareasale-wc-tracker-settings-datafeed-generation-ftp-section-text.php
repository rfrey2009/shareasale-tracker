<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}
$options     = get_option( 'shareasale_wc_tracker_options' );
$merchant_id = $options['merchant-id'];
$prefixed    = 'MID ' . $merchant_id;
$ip          = $_SERVER['SERVER_ADDR'];
?>
<p>
You can send this file to ShareASale's FTP server upon generating and then automatically once per day.
</p>
<p>
<span>Contact the 
	<a href="mailto:shareasale@shareasale.com?Subject=ATTN%3A%20Tech%20Team%2C%20WooCommerce%20Plugin%20FTP%20Credentials&body=<?php echo esc_attr( $prefixed ) ?> Needs FTP credentials for automatic product datafeed uploads. IP address <?php echo $ip; ?>" target="_blank">ShareASale Tech Team</a> to request FTP credentials, and if asked provide your webhost IP address:
	<br>
	<br>
	<code><?php echo $ip; ?></code>
</span>
</p>