<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

global $wpdb;
$datafeeds_table = $wpdb->prefix . 'shareasale_wc_tracker_datafeeds';
$page_num        = filter_input( INPUT_GET, 'page_num' ) ? absint( filter_input( INPUT_GET, 'page_num' ) ) : 1;
$limit           = 5;
$offset          = ( $page_num - 1 ) * $limit;
$total           = $wpdb->get_var(
	"SELECT COUNT(DISTINCT DATE_FORMAT(generation_date, '%Y-%m-%d'))
	FROM $datafeeds_table"
);
$num_of_pages    = ceil( $total / $limit );

$datafeeds = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT
		DATE_FORMAT( generation_date, '%%m/%%d/%%Y %%h:%%i %%p' ) as generation_date,
		file,
		product_count,
		warnings,
		ftp_uploaded
		FROM $datafeeds_table
		WHERE id IN (
			SELECT MAX(id)
			FROM $datafeeds_table
			WHERE generation_date > NOW() - INTERVAL %d DAY
			GROUP BY DATE_FORMAT( generation_date, '%%m/%%d/%%Y' )
		)
		ORDER BY id DESC
		LIMIT %d, %d
		",
		SHAREASALE_WC_TRACKER_MAX_DATAFEED_AGE_DAYS, $offset, $limit
	)
);
?>
<h2>Past <?php echo esc_html( SHAREASALE_WC_TRACKER_MAX_DATAFEED_AGE_DAYS ) ?> Days Generated Datafeeds by Day</h2>
<table class="shareasale-wc-tracker-datafeeds-table">
	<thead class="shareasale-wc-tracker-datafeeds-head">
		<tr class="shareasale-wc-tracker-datafeeds-row">
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-left">Date</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-left">File</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-right">Product Count</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-left shareasale-wc-tracker-datafeeds-header-warning">Column | # Warnings</th>
			<th class="shareasale-wc-tracker-datafeeds-header shareasale-wc-tracker-datafeeds-header-align-left">FTP Uploaded?</th>			
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
					if( $warnings ) {
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
					}
					break;
				case 'ftp_uploaded':
					if ( 1 == $value ) {
						echo '<span class="dashicons dashicons-yes"></span>';
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
