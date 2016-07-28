<?php
global $wpdb;
$logs_table   = $wpdb->prefix . 'shareasale_tracker_logs';
$page_num     = filter_input( INPUT_GET, 'page_num' ) ? absint( filter_input( INPUT_GET, 'page_num' ) ) : 1;
$limit        = 10;
$offset       = ( $page_num - 1 ) * $limit;
$total        = $wpdb->get_var( "SELECT COUNT(id) FROM $logs_table" );
$num_of_pages = ceil( $total / $limit );

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
		DATE_FORMAT( refund_date, '%%m/%%d/%%Y %%h:%%m %%p' )
		FROM $logs_table
		ORDER BY order_number DESC, refund_date ASC
		LIMIT %d, %d
		",
		$offset, $limit
	)
);
?>
<h2>Recently Reconciled Affiliate Transactions</h2>
<table class = "shareasale-tracker-logs-table">
	<thead class = "shareasale-tracker-logs-head">
		<tr class = "shareasale-tracker-logs-row">
			<th class = "shareasale-tracker-logs-header shareasale-tracker-logs-header-align-right">Order Number</th>
			<th class = "shareasale-tracker-logs-header shareasale-tracker-logs-header-align-left">Action</th>
			<th class = "shareasale-tracker-logs-header shareasale-tracker-logs-header-align-left">Reason</th>
			<th class = "shareasale-tracker-logs-header shareasale-tracker-logs-header-align-right">Subtotal Before</th>
			<th class = "shareasale-tracker-logs-header shareasale-tracker-logs-header-align-right">Refunded</th>
			<th class = "shareasale-tracker-logs-header shareasale-tracker-logs-header-align-right">Subtotal After</th>
			<th class = "shareasale-tracker-logs-header shareasale-tracker-logs-header-align-left">ShareASale Response</th>
			<th class = "shareasale-tracker-logs-header shareasale-tracker-logs-header-align-left">Refund Date</th>
		</tr>
	</thead>
<?php
$order_number = '';
foreach ( $logs as $log ) : ?>
	<?php if ( $log->order_number !== $order_number ) : ?>
		<tbody class = "shareasale-tracker-logs-body">
	<?php endif; ?>
			<tr class = "shareasale-tracker-logs-row">
	<?php
	foreach ( $log as $value ) : ?>
	<?php
		$value        = $value === $order_number ? '' : $value;
		$order_number = $log->order_number;
		$class        = is_numeric( $value ) ? 'shareasale-tracker-logs-cell-align-right' : 'shareasale-tracker-logs-cell-align-left';
	?>
				<td class = "shareasale-tracker-logs-cell <?php echo $class; ?>"><?php echo esc_html( $value ); ?></td>
	<?php endforeach; ?>
			</tr>
<?php endforeach; ?>
</table>
