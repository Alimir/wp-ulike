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
				<?php echo __( 'No data found! This is because there is still no data in your database.', WP_ULIKE_SLUG ); ?>
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
			<h2 class="wp-ulike-page-title"><?php echo _e( 'WP ULike Statistics', WP_ULIKE_SLUG ); ?></h2>
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
						<span class="wp-ulike-text"><?php echo _e( 'Total', WP_ULIKE_SLUG ); ?></span>
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
						<span class="wp-ulike-text"><?php echo _e( 'Today', WP_ULIKE_SLUG ); ?></span>
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
						<span class="wp-ulike-text"><?php echo _e( 'Yesterday', WP_ULIKE_SLUG ); ?></span>
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
						<?php echo $type . ' ' .  __( 'Statistics', WP_ULIKE_SLUG ); ?>
		    		</h3>
		    		<a target="_blank" href="admin.php?page=wp-ulike-<?php echo $type; ?>-logs" class="wp-ulike-button">
		    			<?php echo _e( 'View All Logs', WP_ULIKE_SLUG ); ?>
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
		                        <span class="wp-ulike-text"><?php echo _e( 'Weekly', WP_ULIKE_SLUG ); ?></span>
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
		                        <span class="wp-ulike-text"><?php echo _e( 'Monthly', WP_ULIKE_SLUG ); ?></span>
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
		                        <span class="wp-ulike-text"><?php echo _e( 'Yearly', WP_ULIKE_SLUG ); ?></span>
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
		                        <span class="wp-ulike-text"><?php echo _e( 'Totally',WP_ULIKE_SLUG ); ?></span>
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
						<?php echo _e( 'Allocation Statistics', WP_ULIKE_SLUG ); ?>
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
						<?php echo _e( 'Top Likers', WP_ULIKE_SLUG ); ?>
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
						<?php echo _e( 'Top', WP_ULIKE_SLUG ) . ' ' .  $type; ?>
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