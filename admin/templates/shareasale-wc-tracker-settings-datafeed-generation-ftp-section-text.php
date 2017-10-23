<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}
$options     = get_option( 'shareasale_wc_tracker_options' );
$merchant_id = @$options['merchant-id'];
$prefixed    = 'MID ' . $merchant_id;
$ip          = $_SERVER['SERVER_ADDR'];
?>
<p>
You can directly upload the file to ShareASale's FTP server upon generating. Checking this box will also automatically generate a new file and upload it daily if you haven't already generated one yourself for the day.
</p>
<?php if ( ! @$options['ftp-upload'] ) : ?>
<p>
Contact the <a href="mailto:shareasale@shareasale.com?Subject=ATTN%3A%20Tech%20Team%2C%20WooCommerce%20Plugin%20FTP%20Credentials&body=<?php echo esc_attr( $prefixed ) ?> Needs FTP credentials for automatic product datafeed uploads. IP address <?php echo esc_html( $ip ); ?>" target="_blank">ShareASale Tech Team</a> to request FTP credentials, and if asked provide your webhost IP address:
<br>
<br>
<code><?php echo esc_html( $ip ); ?></code>
</p>
<?php endif; ?>
