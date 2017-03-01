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
		"SELECT 
		DATE_FORMAT( MAX(generation_date), '%%m/%%d/%%Y %%h:%%i %%p' ) as generation_date,
		file,
		product_count,
		warnings
		FROM (
			SELECT *
			FROM $datafeeds_table
			ORDER BY generation_date DESC
		) x
		GROUP BY file
		ORDER BY generation_date DESC
		LIMIT %d, %d
		",
		$offset, $limit
	)
);
?>
<h2>Past 30 Days Generated Datafeeds by Day</h2>
<table class="shareasale-wc-tracker-datafeeds-table">
	<thead class="shareasale-wc-tracker-datafeeds-head">
		<tr class="shareasale-wc-tracker-datafeeds-row">
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-left">Date</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-left">File</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-right">Product Count</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-left shareasale-wc-tracker-datafeeds-header-warning">Column | # Warnings</th>
		</tr>
	</thead>
<?php foreach ( $datafeeds as $datafeed ) : ?>
	<?php if ( ! file_exists( $datafeed->file ) ) { continue; } ?>
	<tbody class="shareasale-wc-tracker-datafeeds-body">
		<tr class="shareasale-wc-tracker-datafeeds-row">
	<?php foreach ( $datafeed as $detail => $value ) : ?>
			<td class="shareasale-wc-tracker-datafeeds-cell">
			<?php
			switch ( $detail ) {
				case 'file':
					echo
						'<a href="' . esc_url( plugins_url( 'datafeeds/' . basename( $datafeed->file ), __DIR__ ) ) . '" download>
							Download
						</a>';
					break;
				case 'warnings':
					$warnings = maybe_unserialize( $value );
					foreach ( $warnings as $code => $warning ) {
						$messages = $warning['messages'];
						$count    = count( $messages );
						if ( $count > 0 ) {
							echo '<div class="shareasale-wc-tracker-datafeeds-error-code">';
							echo '<b>' . esc_html( strtoupper( $code ) ) . '</b>';
							echo '<a class="shareasale-wc-tracker-datafeeds-error-count">'
									. esc_html( $count ) .
								 '</a><br>';
							foreach ( $messages as $message ) {
								echo '<div class="shareasale-wc-tracker-datafeeds-error-message shareasale-wc-tracker-datafeeds-error-message-hidden">';
								echo wp_kses( $message, array(
									'a' => array(
										'target'  => true,
										'href'    => true,
										),
									)
								) . '</div>';
							}
							echo '</div>';
						}
					}
					break;
				default :
					echo esc_html( $value );
			}
			?>
			</td>
	<?php endforeach; ?>
		</tr>
	</tbody>
<?php endforeach; ?>
</table>
