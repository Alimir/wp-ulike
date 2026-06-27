<?php
/**
 * Pulse Storage admin template.
 *
 * @var array $config
 * @var array $progress
 * @var float $percent
 * @var array $cli_commands
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sync_status = $config['migration']['status'] ?? 'idle';
$is_running  = 'running' === $sync_status;
$can_enable  = WP_Ulike_Pulse_Config::MODE_PULSE !== WP_Ulike_Pulse_Config::mode();
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Pulse Storage', 'wp-ulike' ); ?></h1>
	<p><?php esc_html_e( 'Pulse stores likes in a single, faster table. Your site keeps working while old data is copied over — safely and in the background.', 'wp-ulike' ); ?></p>

	<table class="widefat striped" style="max-width:560px;margin-top:1.5em;">
		<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Status', 'wp-ulike' ); ?></th>
				<td><code id="wp-ulike-pulse-sync-status"><?php echo esc_html( $sync_status ); ?></code></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Progress', 'wp-ulike' ); ?></th>
				<td id="wp-ulike-pulse-progress-text">
					<?php
					printf(
						/* translators: 1: imported rows, 2: total legacy rows, 3: percent */
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
		<button type="button" class="button button-primary" id="wp-ulike-pulse-start" <?php disabled( $is_running ); ?>>
			<?php esc_html_e( 'Start sync', 'wp-ulike' ); ?>
		</button>
		<button type="button" class="button" id="wp-ulike-pulse-pause" <?php disabled( ! $is_running ); ?>>
			<?php esc_html_e( 'Pause', 'wp-ulike' ); ?>
		</button>
		<?php if ( $can_enable ) : ?>
		<button type="button" class="button" id="wp-ulike-pulse-enable">
			<?php esc_html_e( 'Finish migration', 'wp-ulike' ); ?>
		</button>
		<?php endif; ?>
	</p>

	<p class="description" style="max-width:560px;">
		<?php esc_html_e( 'You can leave this page anytime — sync continues in the background. Staying on the page may speed things up.', 'wp-ulike' ); ?>
	</p>

	<div id="wp-ulike-pulse-log" style="max-width:560px;margin-top:1em;font-size:12px;color:#646970;"></div>

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
</div>
