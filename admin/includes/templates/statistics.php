<?php
/**
 * Statistics page template
 * // @echo HEADER
 */

	// no direct access allowed
	if ( ! defined('ABSPATH') ) {
	    die();
	}

	// wp_ulike_stats class instance
	$wp_ulike_stats = wp_ulike_stats::get_instance();
	// get tables info
	$get_tables     = $wp_ulike_stats->get_tables();

	if( empty( $get_tables ) ) {
?>
<div class="wrap wp-ulike-container">
	<div class="wp-ulike-row wp-ulike-empty-stats">
		<div class="col-12">
			<div class="wp-ulike-icon">
				<i class="wp-ulike-icons-hourglass"></i>
			</div>
			<div class="wp-ulike-info">
				<?php echo esc_html__( 'No data found! This is because there is still no data in your database.', 'wp-ulike' ); ?>
			</div>
		</div>
	</div>
</div>
<?php
		exit;
	}
?>
<div id="wp-ulike-stats-app" class="wrap wp-ulike-container">
	<div class="wp-ulike-row">
		<div class="col-12">
			<h2 class="wp-ulike-page-title"><?php esc_html_e( 'WP ULike Statistics', 'wp-ulike' ); ?></h2>
		</div>
	</div>
	<div class="wp-ulike-pro-stats-banner wp-ulike-row">
		<div class="col-12">
			<div class="wp-ulike-inner">
				<div class="wp-ulike-row">
					<h3><?php esc_html_e( 'Check Votings, Best Likers & Top contents', 'wp-ulike' ); ?></h3>
					<?php
						echo sprintf( '<p>%s</p><div class="wp-ulike-button-group">%s%s</div>', esc_html__('With WP ULike Pro comprehensive Statistics tools, you can track what your users love and what annoys them in an instance. You can extract reports of likes and dislikes in Linear Charts, Pie Charts or whichever you prefer with dateRange picker and status selector controllers, no confusing options and coding needed.','wp-ulike'), wp_ulike_widget_button_callback( array(
							'label'         => esc_html__( 'Buy WP ULike Premium', 'wp-ulike' ),
							'color_name'    => 'default',
							'link'          => WP_ULIKE_PLUGIN_URI . 'pricing/?utm_source=statistics-page&utm_campaign=gopro&utm_medium=wp-dash',
							'target'        => '_blank'
						) ), wp_ulike_widget_button_callback( array(
							'label'         => esc_html__( 'More information', 'wp-ulike' ),
							'color_name'    => 'info',
							'link'          => WP_ULIKE_PLUGIN_URI . 'blog/wp-ulike-pro-statistics/?utm_source=statistics-page&utm_campaign=gopro&utm_medium=wp-dash',
							'target'        => '_blank'
						) ) );
					?>
				</div>
			</div>
		</div>
	</div>
    <div class="wp-ulike-row wp-ulike-logs-count">
        <div class="col-4">
			<div class="wp-ulike-inner">
				<div class="wp-ulike-row wp-ulike-flex wp-ulike-is-loading">
					<div class="col-5">
						<div class="wp-ulike-icon">
							<i class="wp-ulike-icons-linegraph"></i>
						</div>
					</div>
					<div class="col-7">
						<get-var dataset="count_all_logs_all" inline-template>
							<span class="wp-ulike-var" v-html="output"></span>
						</get-var>
						<span class="wp-ulike-text"><?php esc_html_e( 'Total', 'wp-ulike' ); ?></span>
					</div>
				</div>
			</div>
        </div>
        <div class="col-4">
			<div class="wp-ulike-inner">
				<div class="wp-ulike-row wp-ulike-flex wp-ulike-is-loading">
					<div class="col-5">
						<div class="wp-ulike-icon">
							<i class="wp-ulike-icons-hourglass"></i>
						</div>
					</div>
					<div class="col-7">
						<get-var dataset="count_all_logs_today" inline-template>
							<span class="wp-ulike-var" v-html="output"></span>
						</get-var>
						<span class="wp-ulike-text"><?php esc_html_e( 'Today', 'wp-ulike' ); ?></span>
					</div>
				</div>
			</div>
        </div>
        <div class="col-4">
			<div class="wp-ulike-inner">
				<div class="wp-ulike-row wp-ulike-flex wp-ulike-is-loading">
					<div class="col-5">
						<div class="wp-ulike-icon">
							<i class="wp-ulike-icons-bargraph"></i>
						</div>
					</div>
					<div class="col-7">
						<get-var dataset="count_all_logs_yesterday" inline-template>
							<span class="wp-ulike-var" v-html="output"></span>
						</get-var>
						<span class="wp-ulike-text"><?php esc_html_e( 'Yesterday', 'wp-ulike' ); ?></span>
					</div>
				</div>
			</div>
        </div>
    </div>
<?php
	foreach ( $get_tables as $type => $table):
?>
	<div class="wp-ulike-row wp-ulike-summary-charts">
	    <div class="col-12">
	        <div class="wp-ulike-inner">
		    	<div class="wp-ulike-header">
		    		<h3 class="wp-ulike-widget-title">
						<?php echo $type . ' ' .  esc_html__( 'Statistics', 'wp-ulike' ); ?>
		    		</h3>
		    		<a target="_blank" href="admin.php?page=wp-ulike-<?php echo $type; ?>-logs" class="wp-ulike-button">
		    			<?php esc_html_e( 'View All Logs', 'wp-ulike' ); ?>
		    		</a>
		    	</div>
	            <div class="wp-ulike-row wp-ulike-flex wp-ulike-is-loading">
	                <div class="col-8">
						<get-chart type="line" identify="wp-ulike-<?php echo $type; ?>-chart" dataset="dataset_<?php echo $table; ?>" inline-template>
							<canvas id="wp-ulike-<?php echo $type; ?>-chart"></canvas>
						</get-chart>
	                </div>
	                <div class="col-4">
	                    <div class="wp-ulike-flex">
	                        <div class="wp-ulike-icon">
								<i class="wp-ulike-icons-magnifying-glass"></i>
							</div>
							<div class="wp-ulike-info">
		                        <get-var dataset="count_logs_<?php echo $table; ?>_week" inline-template>
									<span class="wp-ulike-var" v-html="output"></span>
		                        </get-var>
		                        <span class="wp-ulike-text"><?php esc_html_e( 'Weekly', 'wp-ulike' ); ?></span>
	                       	</div>
	                    </div>
	                    <div class="wp-ulike-flex">
	                        <div class="wp-ulike-icon">
								<i class="wp-ulike-icons-bargraph"></i>
							</div>
							<div class="wp-ulike-info">
		                        <get-var dataset="count_logs_<?php echo $table; ?>_month" inline-template>
									<span class="wp-ulike-var" v-html="output"></span>
		                        </get-var>
		                        <span class="wp-ulike-text"><?php esc_html_e( 'Monthly', 'wp-ulike' ); ?></span>
	                       	</div>
	                    </div>
	                    <div class="wp-ulike-flex">
	                        <div class="wp-ulike-icon">
								<i class="wp-ulike-icons-linegraph"></i>
							</div>
							<div class="wp-ulike-info">
		                        <get-var dataset="count_logs_<?php echo $table; ?>_year" inline-template>
									<span class="wp-ulike-var" v-html="output"></span>
		                        </get-var>
		                        <span class="wp-ulike-text"><?php esc_html_e( 'Yearly', 'wp-ulike' ); ?></span>
	                       	</div>
	                    </div>
	                    <div class="wp-ulike-flex">
	                        <div class="wp-ulike-icon">
								<i class="wp-ulike-icons-global"></i>
							</div>
							<div class="wp-ulike-info">
		                        <get-var dataset="count_logs_<?php echo $table; ?>_all" inline-template>
									<span class="wp-ulike-var" v-html="output"></span>
		                        </get-var>
		                        <span class="wp-ulike-text"><?php esc_html_e( 'Totally','wp-ulike' ); ?></span>
	                       	</div>
	                    </div>
	                </div>
	            </div>
	        </div>
	    </div>
	</div>
<?php
	endforeach;
?>
	<div class="wp-ulike-row wp-ulike-percent-charts wp-ulike-flex">
	    <div class="col-6">
	        <div class="wp-ulike-inner">
				<div class="wp-ulike-header">
		    		<h3 class="wp-ulike-widget-title">
						<?php esc_html_e( 'Allocation Statistics', 'wp-ulike' ); ?>
		    		</h3>
				</div>
            	<div class="wp-ulike-row wp-ulike-match-height wp-ulike-flex wp-ulike-is-loading">
                	<div class="col-12">
	                    <div class="wp-ulike-draw-chart">
							<get-chart type="pie" identify="wp-ulike-percent-chart" dataset="" inline-template>
								<canvas id="wp-ulike-percent-chart"></canvas>
							</get-chart>
	                    </div>
                	</div>
                </div>
	        </div>
	    </div>
	    <div class="col-6">
	        <div class="wp-ulike-inner">
				<div class="wp-ulike-header">
		    		<h3 class="wp-ulike-widget-title">
						<?php esc_html_e( 'Top Likers', 'wp-ulike' ); ?>
		    		</h3>
				</div>
            	<div class="wp-ulike-row wp-ulike-match-height wp-ulike-flex wp-ulike-is-loading">
                	<div class="col-12">
						<div class="wp-ulike-top-likers">
							<get-var dataset="display_top_likers" inline-template>
								<div class="wp-ulike-var" v-html="output"></div>
							</get-var>
	                	</div>
                	</div>
                </div>
	        </div>
	    </div>
	</div>
<?php
	foreach ( $get_tables as $type => $table):
?>
	<div class="wp-ulike-row wp-ulike-get-tops">
	    <div class="col-12">
	        <div class="wp-ulike-inner">
				<div class="wp-ulike-header">
		    		<h3 class="wp-ulike-widget-title">
						<?php esc_html_e( 'Top', 'wp-ulike' ) . ' ' .  $type; ?>
		    		</h3>
				</div>
				<div class="wp-ulike-row wp-ulike-is-loading">
					<div class="col-12">
						<div class="wp-ulike-tops-list wp-ulike-top-<?php echo $type; ?>">
							<get-var dataset="get_top_<?php echo $type; ?>" inline-template>
								<ul class="wp-ulike-var" v-html="output"></ul>
							</get-var>
						</div>
					</div>
            	</div>
	        </div>
	    </div>
	</div>
<?php
	endforeach;
?>
</div>