<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

global $wpdb;
$datafeeds_table = $wpdb->prefix . 'shareasale_wc_tracker_datafeeds';
$page_num        = filter_input( INPUT_GET, 'page_num' ) ? absint( filter_input( INPUT_GET, 'page_num' ) ) : 1;
$limit           = 10;
$offset          = ( $page_num - 1 ) * $limit;
$total           = $wpdb->get_var( "SELECT COUNT(DISTINCT file) FROM $datafeeds_table" );
$num_of_pages    = ceil( $total / $limit );

$datafeeds = $wpdb->get_results(
	$wpdb->prepare(
		"
		SELECT
		MAX(DATE_FORMAT( generation_date, '%%m/%%d/%%Y %%h:%%i %%p' )) as generation_date,
		file,
		product_count,
		warnings
		FROM $datafeeds_table
		GROUP BY file
		ORDER BY generation_date DESC
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
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-left">Date</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-left">Link</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-right">Product Count</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-left">Warnings</th>
		</tr>
	</thead>
<?php
foreach ( $datafeeds as $datafeed ) : ?>
	<?php if ( ! file_exists( $datafeed->file ) ) continue; ?>
		<tbody class="shareasale-wc-tracker-datafeeds-body">
			<tr class="shareasale-wc-tracker-datafeeds-row">
	<?php foreach ( $datafeed as $detail => $value ) : ?>
				<td class="shareasale-wc-tracker-datafeeds-cell">

					<?php switch ( $detail ) :
					case 'generation_date' : ?>
						<?php echo esc_html( $value ); ?>
					<?php break; ?>
					<?php case 'file' : ?>
						<a href="<?php echo esc_url( plugins_url( 'datafeeds/' . basename( $datafeed->file ), __DIR__ ) ); ?>">Download</a>
					<?php break; ?>
					<?php case 'product_count' : ?>
						<?php echo esc_html( $value ); ?>
					<?php break; ?>
					<?php case 'warnings' : ?>
						<?php $warnings = maybe_unserialize( $datafeed->warnings );	?>
						<?php foreach ( $warnings as $type => $warning ) : ?>
							<?php echo $type; ?>
						<?php endforeach; ?>
					<?php break; ?>
					<?php endswitch ?>

				</td>
	<?php endforeach; ?>
			</tr>
<?php endforeach; ?>
</table>
