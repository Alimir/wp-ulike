<?php
/**
 * Comments logs page template
 * // @echo HEADER
 */

	// no direct access allowed
	if ( ! defined('ABSPATH') ) {
	    die();
	}

	// Alternate tanle colors
	$alternate = true;
	// Get datasets
	$datasets  = wp_ulike_get_paginated_logs( 'ulike_comments', 'comments' );

	if( empty( $datasets ) ) {
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
<div class="wrap wp-ulike-container">
	<div class="wp-ulike-row">
		<div class="col-12">
			<h2 class="wp-ulike-page-title"><?php _e('WP ULike Logs', WP_ULIKE_SLUG); ?></h2>
			<h3><?php _e('Comment Likes Logs', WP_ULIKE_SLUG); ?></h3>
			<div class="tablenav">
				<div class='tablenav-pages'>
					<span class="displaying-num"><?php echo $datasets['num_rows'] . ' ' .  __('Logs',WP_ULIKE_SLUG); ?></span>
					<?php echo $datasets['paginate']->show();  // Echo out the list of paging. ?>
				</div>
			</div>
			<table class="widefat">
				<thead>
					<tr>
						<th width="2%"><?php _e('ID', WP_ULIKE_SLUG); ?></th>
						<th width="10%"><?php _e('Username', WP_ULIKE_SLUG); ?></th>
						<th width="5%"><?php _e('Status', WP_ULIKE_SLUG); ?></th>
						<th width="6%"><?php _e('Comment ID', WP_ULIKE_SLUG); ?></th>
						<th><?php _e('Comment Author', WP_ULIKE_SLUG); ?></th>
						<th><?php _e('Comment Text', WP_ULIKE_SLUG); ?></th>
						<th width="20%"><?php _e('Date / Time', WP_ULIKE_SLUG); ?></th>
						<th><?php _e('IP', WP_ULIKE_SLUG); ?></th>
						<th><?php _e('Actions', WP_ULIKE_SLUG); ?></th>
					</tr>
				</thead>
				<tbody class="wp_ulike_logs">
					<?php
					foreach ( $datasets['data_rows'] as $data_row )
					{
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
								echo '<em> #'. __('Guest User',WP_ULIKE_SLUG) .'</em>';
							}
						?>
						</td>
						<td>
						<?php
							if( $data_row->status == 'like'){
								echo '<i class="wp-ulike-icons-thumb_up"></i>';
							}
							else{
								echo '<i class="wp-ulike-icons-thumb_down"></i>';
							}
						?>
						</td>
						<td>
						<?php
							echo $data_row->comment_id;
						?>
						</td>
						<td>
						<?php
							echo get_comment_author( ! empty( $data_row->comment_id ) ? $data_row->comment_id : 0 );
						?>
						</td>
						<td>
						<?php
							echo get_comment_text( ! empty( $data_row->comment_id ) ? $data_row->comment_id : 0 );
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
							<button class="wp_ulike_delete button" type="button" data-nonce="<?php echo wp_create_nonce( 'ulike_comments' . $data_row->id ); ?>" data-id="<?php echo $data_row->id;?>" data-table="ulike_comments">
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
			<div class="tablenav">
				<div class='tablenav-pages'>
					<span class="displaying-num"><?php echo $datasets['num_rows'] . ' ' .  __('Logs',WP_ULIKE_SLUG); ?></span>
					<?php echo $datasets['paginate']->show();  // Echo out the list of paging. ?>
				</div>
			</div>
		</div>
	</div>
</div>
