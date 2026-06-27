<?php
/**
 * Pulse Storage admin template.
 *
 * @var array  $progress
 * @var float  $percent
 * @var array  $cli_commands
 * @var string $sync_status
 * @var bool   $sync_complete
 * @var bool   $is_running
 * @var bool   $is_pulse
 * @var string $status_label
 * @var bool   $show_cleanup
 * @var bool   $can_drop_legacy
 * @var array  $legacy_tables
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$can_enable  = ! $is_pulse;
$show_start  = ! $sync_complete && ! $is_pulse;
$show_migrate = ! $is_pulse;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Pulse Storage', 'wp-ulike' ); ?></h1>

	<?php if ( $show_cleanup ) : ?>
	<p><?php esc_html_e( 'Migration is complete. Pulse is now your active storage. You can optionally remove the old like tables to free disk space.', 'wp-ulike' ); ?></p>

	<div class="notice notice-success inline" style="max-width:560px;margin-top:1.5em;padding:12px;">
		<p style="margin:0;"><?php esc_html_e( 'Pulse is active. All likes are read from the new storage.', 'wp-ulike' ); ?></p>
	</div>

	<?php if ( $can_drop_legacy ) : ?>
	<div class="notice notice-warning inline" style="max-width:560px;margin-top:1em;padding:12px;">
		<p style="margin:0;">
			<?php esc_html_e( 'Old tables are still on your server. Removing them is optional and permanent — make a database backup first.', 'wp-ulike' ); ?>
		</p>
		<ul style="margin:0.5em 0 0 1.2em;font-size:12px;">
			<?php foreach ( $legacy_tables as $table_name ) : ?>
				<li><code><?php echo esc_html( $table_name ); ?></code></li>
			<?php endforeach; ?>
		</ul>
	</div>

	<p class="submit" style="margin-top:1.5em;">
		<button type="button" class="button button-primary" id="wp-ulike-pulse-drop-legacy">
			<?php esc_html_e( 'Remove old tables', 'wp-ulike' ); ?>
		</button>
		<button type="button" class="button" id="wp-ulike-pulse-dismiss">
			<?php esc_html_e( 'Keep old tables & close', 'wp-ulike' ); ?>
		</button>
	</p>
	<?php else : ?>
	<p class="description" style="max-width:560px;margin-top:1em;">
		<?php esc_html_e( 'Old tables were detected but could not be verified for safe removal yet. You can close this page — nothing else is required.', 'wp-ulike' ); ?>
	</p>
	<p class="submit" style="margin-top:1.5em;">
		<button type="button" class="button button-primary" id="wp-ulike-pulse-dismiss">
			<?php esc_html_e( 'Close', 'wp-ulike' ); ?>
		</button>
	</p>
	<?php endif; ?>

	<?php else : ?>
	<p><?php esc_html_e( 'Pulse stores likes in a single, faster table. Your site keeps working while old data is copied over — safely and in the background. Nothing is deleted.', 'wp-ulike' ); ?></p>

	<?php if ( $sync_complete && ! $is_pulse ) : ?>
	<div class="notice notice-success inline" id="wp-ulike-pulse-next-step" style="max-width:560px;margin-top:1.5em;padding:12px;">
		<p style="margin:0;">
			<strong><?php esc_html_e( 'Copy complete.', 'wp-ulike' ); ?></strong>
			<?php esc_html_e( 'One last step: click “Finish migration” to use Pulse for all reads. Your old tables stay in place.', 'wp-ulike' ); ?>
		</p>
	</div>
	<?php elseif ( $is_running ) : ?>
	<div class="notice notice-info inline" style="max-width:560px;margin-top:1.5em;padding:12px;">
		<p style="margin:0;"><?php esc_html_e( 'Copy in progress. You can leave this page — sync continues in the background.', 'wp-ulike' ); ?></p>
	</div>
	<?php endif; ?>

	<table class="widefat striped" style="max-width:560px;margin-top:1.5em;">
		<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Status', 'wp-ulike' ); ?></th>
				<td><span id="wp-ulike-pulse-sync-status"><?php echo esc_html( $status_label ); ?></span></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Progress', 'wp-ulike' ); ?></th>
				<td id="wp-ulike-pulse-progress-text">
					<?php
					printf(
						esc_html__( '%1$d of %2$d rows (%3$s%%)', 'wp-ulike' ),
						(int) $progress['total_imported'],
						(int) $progress['total_legacy'],
						esc_html( (string) $percent )
					);
					?>
				</td>
			</tr>
		</tbody>
	</table>

	<div style="max-width:560px;margin-top:0.75em;background:#f0f0f1;border-radius:4px;overflow:hidden;height:8px;">
		<div id="wp-ulike-pulse-progress-bar" style="width:<?php echo esc_attr( (string) $percent ); ?>%;height:100%;background:#2271b1;transition:width 0.3s;"></div>
	</div>

	<p class="submit" style="margin-top:1.5em;">
		<?php if ( $show_start ) : ?>
		<button type="button" class="button button-primary" id="wp-ulike-pulse-start" <?php disabled( $is_running ); ?>>
			<?php esc_html_e( 'Start sync', 'wp-ulike' ); ?>
		</button>
		<?php endif; ?>
		<button type="button" class="button" id="wp-ulike-pulse-pause" <?php disabled( ! $is_running ); ?><?php echo $show_start ? '' : ' style="display:none;"'; ?>>
			<?php esc_html_e( 'Pause', 'wp-ulike' ); ?>
		</button>
		<?php if ( $can_enable ) : ?>
		<button type="button" class="button<?php echo $sync_complete ? ' button-primary' : ''; ?>" id="wp-ulike-pulse-enable" <?php disabled( ! $sync_complete ); ?>>
			<?php esc_html_e( 'Finish migration', 'wp-ulike' ); ?>
		</button>
		<?php endif; ?>
	</p>
	<?php endif; ?>

	<div id="wp-ulike-pulse-log" style="max-width:560px;margin-top:1em;font-size:13px;color:#646970;"></div>

	<?php if ( $show_migrate ) : ?>
	<details class="wp-ulike-pulse-cli" style="max-width:560px;margin-top:2em;">
		<summary style="cursor:pointer;color:#646970;font-size:13px;">
			<?php esc_html_e( 'Advanced: WP-CLI commands (optional)', 'wp-ulike' ); ?>
		</summary>
		<div style="padding:12px 0 0;">
			<p class="description" style="margin-top:0;">
				<?php esc_html_e( 'For developers or very large sites with SSH access. The buttons above are enough for most installations.', 'wp-ulike' ); ?>
			</p>
			<ul style="margin:0.75em 0 0;padding:0;list-style:none;font-size:12px;line-height:1.8;">
				<?php foreach ( $cli_commands as $cli ) : ?>
					<li>
						<code style="background:#f6f7f7;padding:2px 6px;border-radius:3px;"><?php echo esc_html( $cli['cmd'] ); ?></code>
						<span style="color:#646970;"> — <?php echo esc_html( $cli['desc'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</details>
	<?php endif; ?>
</div>
