<?php

$container = \SmartCAT\WP\Connector::get_container();
/** @var \SmartCAT\WP\DB\Repository\StatisticRepository $statistics_repository */
$statistics_repository = $container->get( 'entity.repository.statistic' );
$statistics_table      = new \SmartCAT\WP\Admin\StatisticsTable();

$from  = 0;
$limit = 100;

$total_elements = $statistics_repository->get_count();

$limit    = 100;
$max_page = ceil( $total_elements / $limit );
$page     = isset( $_GET['page-number'] ) ? abs( intval( $_GET['page-number'] ) ) : 1;
$page     = ( $page > $max_page ) ? $max_page : $page;
$page     = ( $page >= 1 ) ? $page : 1; //на всякий случай, хотя двумя строками выше это должно решаться
$from     = $limit * ( $page - 1 );

?>
<div class="smartcat-connector wrap">
	<h1><?php echo esc_html( $GLOBALS['title'] ); ?></h1>
	<?php
	$container = \SmartCAT\WP\Connector::get_container();

	/** @var \SmartCAT\WP\WP\Options $options */
	$options                    = $container->get( 'core.options' );
	$is_statistics_queue_active = boolval( $options->get( 'statistic_queue_active' ) );
	$button_status              = $is_statistics_queue_active ? 'disabled="disabled"' : '';
	?>
	<input type="button"
	       id="smartcat-connector-refresh-statistics"
	       value="<?php echo __( 'Refresh statistics', 'translation-connectors' ); ?>" <?php echo $button_status; ?>/>
	<?php

	$statistics_result = $statistics_repository->get_statistics( $from, $limit );

	if ( $statistics_result ) {
		$table = new \SmartCAT\WP\Admin\StatisticsTable();
		$table->set_data( $statistics_result )->display();
		?>
		<div class="pagination">
			<span class="title"><?php echo __( 'Pages', 'translation-connectors' ); ?>:</span>
			<?php

			$url = strtok( $_SERVER['REQUEST_URI'], '?' );

			for ( $page_number = 1; $page_number <= $max_page; $page_number ++ ) {
				if ( $page_number == $page ) {
					echo "<span>{$page_number}</span>";
				} else {
					echo '<a href="' . esc_html( $url . '?page=sc-translation-progress&page-number=' . $page_number ) . '">' . $page_number . '</a>';
				}
			}

			?>
		</div>

		<?php
	} else {
		echo '<div>' . __( 'Statistics is empty', 'translation-connectors' ) . '</div>';
	}

	?>
</div>