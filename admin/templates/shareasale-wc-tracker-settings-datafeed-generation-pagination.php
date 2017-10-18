<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

$page_links = paginate_links(
	array(
		'base' => add_query_arg(
			array(
	    		'page_num' => '%#%',
	    		'page'     => 'shareasale_wc_tracker_datafeed_generation',
			),
			esc_url( admin_url( 'admin.php' ) )
		),
		'format'    => '',
		'prev_text' => __( '&laquo;', 'text-domain' ),
		'next_text' => __( '&raquo;', 'text-domain' ),
		'total'     => $num_of_pages,
		'current'   => $page_num,
	)
);
?>
<div class="tablenav">
	<div class="tablenav-pages shareasale-wc-tracker-datafeeds-pagination">
		<?php
		echo wp_kses( $page_links, array(
			'div' => array( 'class' => true ),
			'span' => array( 'class' => true ),
			'a' => array(
				'class'  => true,
				'href'    => true,
				),
			)
		);
		?>
	</div>
</div>

