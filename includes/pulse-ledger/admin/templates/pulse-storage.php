<?php
/**
 * Storage upgrade admin template.
 *
 * Upgrade-only UI — strings are English-only (not in translation catalog).
 *
 * @var array  $progress
 * @var float  $percent
 * @var string $progress_label
 * @var array  $cli_commands
 * @var string $sync_status
 * @var bool   $sync_complete
 * @var bool   $is_running
 * @var bool   $is_pulse
 * @var string $status_label
 * @var bool   $show_cleanup
 * @var bool   $can_drop_legacy
 * @var array  $legacy_tables
 * @var string $page_title
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$can_enable   = ! $is_pulse;
$show_start   = ! $sync_complete && ! $is_pulse;
$show_migrate = ! $is_pulse;
?>
<div class="wrap wp-ulike-pulse-upgrade">
	<h1><?php echo esc_html( $page_title ); ?></h1>

	<?php if ( $show_cleanup ) : ?>
	<p><?php echo esc_html( 'The upgrade is complete. Your likes now use the faster storage. Remove the old like tables when you want to free disk space.' ); ?></p>

	<div class="notice notice-success inline" style="max-width:560px;margin-top:1.5em;padding:12px;">
		<p style="margin:0;"><?php echo esc_html( 'Faster storage is active. All likes are read from the new table.' ); ?></p>
	</div>

	<?php if ( $can_drop_legacy ) : ?>
	<div class="notice notice-warning inline" style="max-width:560px;margin-top:1em;padding:12px;">
		<p style="margin:0;">
			<?php echo esc_html( 'Old tables are still on your server. Removing them is permanent — back up your database first.' ); ?>
		</p>
		<ul style="margin:0.5em 0 0 1.2em;font-size:12px;">
			<?php foreach ( $legacy_tables as $table_name ) : ?>
				<li><code><?php echo esc_html( $table_name ); ?></code></li>
			<?php endforeach; ?>
		</ul>
	</div>

	<p class="submit" style="margin-top:1.5em;">
		<button type="button" class="button button-primary" id="wp-ulike-pulse-drop-legacy">
			<?php echo esc_html( 'Remove old tables' ); ?>
		</button>
		<button type="button" class="button" id="wp-ulike-pulse-dismiss">
			<?php echo esc_html( 'Keep old tables & close' ); ?>
		</button>
	</p>
	<?php else : ?>
	<p class="description" style="max-width:560px;margin-top:1em;">
		<?php echo esc_html( 'Old tables were detected but could not be verified for safe removal yet. You can close this page — nothing else is required.' ); ?>
	</p>
	<p class="submit" style="margin-top:1.5em;">
		<button type="button" class="button button-primary" id="wp-ulike-pulse-dismiss">
			<?php echo esc_html( 'Close' ); ?>
		</button>
	</p>
	<?php endif; ?>

	<p class="description" style="max-width:560px;margin-top:0.5em;">
		<a href="<?php echo esc_url( WP_Ulike_Pulse_Admin::get_help_url() ); ?>"><?php echo esc_html( '← Back to Help' ); ?></a>
	</p>

	<?php else : ?>
	<p><?php echo esc_html( 'Likes are stored in a single, faster table. Your site keeps working while old data is copied over — safely and in the background. Nothing is deleted, and you can leave this page at any time.' ); ?></p>

	<?php if ( $sync_complete && ! $is_pulse ) : ?>
	<div class="notice notice-success inline" id="wp-ulike-pulse-next-step" style="max-width:560px;margin-top:1.5em;padding:12px;">
		<p style="margin:0;">
			<strong><?php echo esc_html( 'Copy complete.' ); ?></strong>
			<?php echo esc_html( 'One last step: click “Finish upgrade” to use the faster storage for all reads. Your old tables stay in place.' ); ?>
		</p>
	</div>
	<?php elseif ( $is_running ) : ?>
	<div class="notice notice-info inline" style="max-width:560px;margin-top:1.5em;padding:12px;">
		<p style="margin:0;"><?php echo esc_html( 'Copy in progress. You can leave this page — sync continues in the background.' ); ?></p>
	</div>
	<?php endif; ?>

	<table class="widefat striped" style="max-width:560px;margin-top:1.5em;">
		<tbody>
			<tr>
				<th scope="row"><?php echo esc_html( 'Status' ); ?></th>
				<td><span id="wp-ulike-pulse-sync-status"><?php echo esc_html( $status_label ); ?></span></td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html( 'Progress' ); ?></th>
				<td id="wp-ulike-pulse-progress-text"><?php echo esc_html( $progress_label ); ?></td>
			</tr>
		</tbody>
	</table>

	<div style="max-width:560px;margin-top:0.75em;background:#f0f0f1;border-radius:4px;overflow:hidden;height:8px;">
		<div id="wp-ulike-pulse-progress-bar" style="width:<?php echo esc_attr( (string) $percent ); ?>%;height:100%;background:#2271b1;transition:width 0.3s;"></div>
	</div>

	<p class="submit" style="margin-top:1.5em;">
		<?php if ( $show_start ) : ?>
		<button type="button" class="button button-primary" id="wp-ulike-pulse-start" <?php disabled( $is_running ); ?>>
			<?php echo esc_html( 'Start copy' ); ?>
		</button>
		<?php endif; ?>
		<button type="button" class="button" id="wp-ulike-pulse-pause" <?php disabled( ! $is_running ); ?><?php echo $show_start ? '' : ' style="display:none;"'; ?>>
			<?php echo esc_html( 'Pause' ); ?>
		</button>
		<?php if ( $can_enable ) : ?>
		<button type="button" class="button<?php echo $sync_complete ? ' button-primary' : ''; ?>" id="wp-ulike-pulse-enable" <?php disabled( ! $sync_complete ); ?>>
			<?php echo esc_html( 'Finish upgrade' ); ?>
		</button>
		<?php endif; ?>
	</p>
	<p class="description" style="max-width:560px;margin-top:0.5em;">
		<a href="<?php echo esc_url( WP_Ulike_Pulse_Admin::get_help_url() ); ?>"><?php echo esc_html( '← Back to Help' ); ?></a>
	</p>
	<?php endif; ?>

	<div id="wp-ulike-pulse-log" style="max-width:560px;margin-top:1em;font-size:13px;color:#646970;"></div>

	<?php if ( $show_migrate ) : ?>
	<details class="wp-ulike-pulse-cli" style="max-width:560px;margin-top:2em;">
		<summary style="cursor:pointer;color:#646970;font-size:13px;">
			<?php echo esc_html( 'Advanced: WP-CLI commands' ); ?>
		</summary>
		<div style="padding:12px 0 0;">
			<p class="description" style="margin-top:0;">
				<?php echo esc_html( 'For developers or very large sites with SSH access. The buttons above are enough for most installations.' ); ?>
				<?php if ( 'WP-Cron' === WP_Ulike_Pulse_Sync_Scheduler::engine_label() ) : ?>
					<?php echo esc_html( ' Background sync uses WP-Cron — on production sites, configure a real system cron hitting wp-cron.php or use WP-CLI batches so sync does not stall.' ); ?>
				<?php endif; ?>
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
