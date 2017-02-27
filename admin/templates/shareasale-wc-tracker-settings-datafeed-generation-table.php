<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

global $wpdb;
$datafeeds_table = $wpdb->prefix . 'shareasale_wc_tracker_datafeeds';
$page_num        = filter_input( INPUT_GET, 'page_num' ) ? absint( filter_input( INPUT_GET, 'page_num' ) ) : 1;
$limit           = 10;
$offset          = ( $page_num - 1 ) * $limit;
$total           = $wpdb->get_var( "SELECT COUNT(id) FROM $datafeeds_table" );
$num_of_pages    = ceil( $total / $limit );

$datafeeds = $wpdb->get_results(
	$wpdb->prepare(
		"
		SELECT 
		file,
		warnings,
		product_count,
		DATE_FORMAT( generation_date, '%%m/%%d/%%Y %%h:%%i %%p' )
		FROM $datafeeds_table
		ORDER BY id DESC
		LIMIT %d, %d
		",
		$offset, $limit
	)
);
?>
<h2>Generated Datafeeds In The Past 30 Days</h2>
<table class="shareasale-wc-tracker-datafeeds-table">
	<thead class="shareasale-wc-tracker-datafeeds-head">
		<tr class="shareasale-wc-tracker-datafeeds-row">
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-right">Date</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-left">Download</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-left">Product Count</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-right">Warnings</th>
		</tr>
	</thead>
<?php
foreach ( $datafeeds as $datafeed ) : ?>
	<?php if ( $datafeeds->file !== $file ) : ?>
		<tbody class="shareasale-wc-tracker-datafeeds-body">
	<?php endif; ?>
			<tr class="shareasale-wc-tracker-datafeeds-row">
	<?php
	foreach ( $datafeed as $value ) : ?>
	<?php
		$value        = $value === $file ? '' : $value;
		$file = $datafeed->file;
	?>
				<td class="shareasale-wc-tracker-datafeeds-cell"><?php echo esc_html( $value ); ?></td>
	<?php endforeach; ?>
			</tr>
<?php endforeach; ?>
</table>
