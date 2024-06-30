<?php
/**
 * Posts logs page template
 * // @echo HEADER
 */

	// no direct access allowed
	if ( ! defined('ABSPATH') ) {
	    die();
	}

	// Alternate tanle colors
	$alternate = true;
	// Get datasets
	$datasets  = wp_ulike_get_paginated_logs( 'ulike', 'posts' );

	if( empty( $datasets ) ) {
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
<div class="wrap wp-ulike-container">
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
	<div class="wp-ulike-row">
		<div class="col-12">
			<h2 class="wp-ulike-page-title"><?php esc_html_e('WP ULike Logs', 'wp-ulike'); ?></h2>
			<h3><?php esc_html_e('Post Likes Logs', 'wp-ulike'); ?></h3>
			<?php echo $datasets['pagination_html'];  // Echo out the list of paging. ?>
			<table class="widefat">
				<thead>
					<tr>
						<th width="2%"><?php esc_html_e('ID', 'wp-ulike'); ?></th>
						<th width="10%"><?php esc_html_e('Username', 'wp-ulike'); ?></th>
						<th><?php esc_html_e('Status', 'wp-ulike'); ?></th>
						<th width="6%"><?php esc_html_e('Post ID', 'wp-ulike'); ?></th>
						<th><?php esc_html_e('Post Title', 'wp-ulike'); ?></th>
						<th width="20%"><?php esc_html_e('Date / Time', 'wp-ulike'); ?></th>
						<th><?php esc_html_e('IP', 'wp-ulike'); ?></th>
						<th><?php esc_html_e('Actions', 'wp-ulike'); ?></th>
					</tr>
				</thead>
				<tbody class="wp_ulike_logs">
					<?php
					foreach ( $datasets['data_rows'] as $data_row ) {
					?>
					<tr <?php if ($alternate == true) echo 'class="alternate"';?>>
						<td>
						<?php
							echo $data_row->id;
						?>
						</td>
						<td>
						<?php
							if( NULL != ( $user_info = get_userdata( $data_row->user_id ) ) ) {
								echo get_avatar( $user_info->user_email, 16, '' , 'avatar') . '<em> @' . $user_info->user_login . '</em>';
							}
							else {
								echo '<em> #'. esc_html__('Guest User','wp-ulike') .'</em>';
							}
						?>
						</td>
						<td>
						<?php
							echo $data_row->status;
						?>
						</td>
						<td>
						<?php
							echo $data_row->post_id;
						?>
						</td>
						<td>
						<?php
							echo '<a href="'.get_permalink($data_row->post_id).'" title="'.get_the_title($data_row->post_id).'">'.get_the_title($data_row->post_id).'</a>';
						?>
						</td>
						<td>
						<?php
							echo wp_ulike_date_i18n($data_row->date_time);
						?>
						</td>
						<td>
						<?php
							echo $data_row->ip;
						?>
						</td>
						<td>
							<button class="wp_ulike_delete button" type="button" data-nonce="<?php echo wp_create_nonce( 'ulike' . $data_row->id ); ?>" data-id="<?php echo $data_row->id;?>" data-table="ulike">
								<i class="dashicons dashicons-trash"></i>
							</button>
						</td>
						<?php
						$alternate = !$alternate;
						}
						?>
					</tr>
				</tbody>
			</table>
			<?php echo $datasets['pagination_html'];  // Echo out the list of paging. ?>
		</div>
	</div>
</div>