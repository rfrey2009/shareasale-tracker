<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

global $wpdb;
$datafeeds_table = $wpdb->prefix . 'shareasale_wc_tracker_datafeeds';
$page_num       = filter_input( INPUT_GET, 'page_num' ) ? absint( filter_input( INPUT_GET, 'page_num' ) ) : 1;
$limit          = 10;
$offset         = ( $page_num - 1 ) * $limit;
$total          = $wpdb->get_var( "SELECT COUNT(id) FROM $datafeeds_table" );
$num_of_pages   = ceil( $total / $limit );

$logs       = $wpdb->get_results(
	$wpdb->prepare(
		"
		SELECT 
		order_number,
		action,
		reason,
		ROUND( subtotal_before, 2 ),
		ROUND( deducted, 2 ),
		ROUND( subtotal_after, 2 ),
		response,
		DATE_FORMAT( refund_date, '%%m/%%d/%%Y %%h:%%i %%p' )
		FROM $datafeeds_table
		ORDER BY order_number DESC, refund_date ASC
		LIMIT %d, %d
		",
		$offset, $limit
	)
);
?>
<h2>Recently Reconciled Affiliate Transactions</h2>
<table class="shareasale-wc-tracker-datafeeds-table">
	<thead class="shareasale-wc-tracker-datafeeds-head">
		<tr class="shareasale-wc-tracker-datafeeds-row">
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-right">Order Number</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-left">Action</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-left">Reason</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-right">Subtotal Before</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-right">Refunded</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-right">Subtotal After</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-left">ShareASale Response</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-left">Refund Date</th>
		</tr>
	</thead>
<?php
$order_number = '';
foreach ( $logs as $log ) : ?>
	<?php if ( $log->order_number !== $order_number ) : ?>
		<tbody class="shareasale-wc-tracker-datafeeds-body">
	<?php endif; ?>
			<tr class="shareasale-wc-tracker-datafeeds-row">
	<?php
	foreach ( $log as $value ) : ?>
	<?php
		$value        = $value === $order_number ? '' : $value;
		$order_number = $log->order_number;
	?>
				<td class="shareasale-wc-tracker-datafeeds-cell"><?php echo esc_html( $value ); ?></td>
	<?php endforeach; ?>
			</tr>
<?php endforeach; ?>
</table>
