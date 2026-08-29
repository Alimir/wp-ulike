<?php
/**
 * Overview — WordPress-native dashboard (free + Pro via filters).
 *
 * @package WP_ULike
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

$data = class_exists( 'WP_Ulike_Overview' ) ? WP_Ulike_Overview::get_about_view_data() : array();

$import_flash   = isset( $_GET['wp_ulike_import'] ) ? sanitize_key( wp_unslash( $_GET['wp_ulike_import'] ) ) : '';
$repair_flash   = isset( $_GET['wp_ulike_repair'] ) ? sanitize_key( wp_unslash( $_GET['wp_ulike_repair'] ) ) : '';
$stats_flash    = isset( $_GET['wp_ulike_stats_cache'] ) ? sanitize_key( wp_unslash( $_GET['wp_ulike_stats_cache'] ) ) : '';
$features_flash = isset( $_GET['wp_ulike_features'] ) ? sanitize_key( wp_unslash( $_GET['wp_ulike_features'] ) ) : '';
$cleanup_flash  = isset( $_GET['wp_ulike_cleanup'] ) ? sanitize_key( wp_unslash( $_GET['wp_ulike_cleanup'] ) ) : '';
$import_open = in_array( $import_flash, array( 'error_upload', 'error_json', 'error_payload', 'error' ), true );
$is_pro         = ! empty( $data['is_pro'] );
$health         = isset( $data['health'] ) ? $data['health'] : array();
$status_groups  = class_exists( 'WP_Ulike_Overview' ) ? WP_Ulike_Overview::group_status_rows( $data['status_rows'] ?? array() ) : array();
$group_labels   = $data['status_groups'] ?? array();
$group_order    = array( 'engagement', 'setup', 'pro' );
?>

<div class="wrap wp-ulike-about">

	<h1 class="wp-ulike-about__title">
		<?php esc_html_e( 'Overview', 'wp-ulike' ); ?>
		<?php if ( $is_pro && ! empty( $data['pro_version'] ) ) : ?>
			<span class="wp-ulike-about__badge wp-ulike-about__badge--pro"><?php echo esc_html( 'Pro ' . $data['pro_version'] ); ?></span>
		<?php else : ?>
			<span class="wp-ulike-about__badge"><?php echo esc_html( WP_ULIKE_VERSION ); ?></span>
		<?php endif; ?>
	</h1>

	<p class="wp-ulike-about__lead">
		<?php esc_html_e( 'Like buttons and a Statistics dashboard for your WordPress site. Open Statistics for charts and growth tips, or use the shortcuts below to configure display and check status.', 'wp-ulike' ); ?>
	</p>

	<?php if ( 'success' === $import_flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings imported and saved successfully!', 'wp-ulike' ); ?></p></div>
	<?php elseif ( 'error_upload' === $import_flash ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'No settings file was uploaded. Choose a JSON file and try again.', 'wp-ulike' ); ?></p></div>
	<?php elseif ( 'error_json' === $import_flash ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Invalid JSON format. Please check your JSON syntax.', 'wp-ulike' ); ?></p></div>
	<?php elseif ( 'error_payload' === $import_flash ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'This file does not look like a WP ULike settings export. Use a file exported from Settings backup in the Overview sidebar.', 'wp-ulike' ); ?></p></div>
	<?php elseif ( 'error' === $import_flash ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Settings import failed. Please try again.', 'wp-ulike' ); ?></p></div>
	<?php endif; ?>

	<?php if ( 'success' === $repair_flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Database tables repaired successfully.', 'wp-ulike' ); ?></p></div>
	<?php elseif ( 'failed' === $repair_flash ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Some database tables could not be created. See the database status section below (or Tools → Site Health) for the MySQL error details.', 'wp-ulike' ); ?></p></div>
	<?php endif; ?>

	<?php if ( 'flushed' === $stats_flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Statistics cache refreshed. Totals and charts will rebuild on the next view.', 'wp-ulike' ); ?></p></div>
	<?php endif; ?>

	<?php if ( in_array( $cleanup_flash, array( 'cleaned', 'scheduled' ), true ) ) : ?>
		<?php $removed = (int) get_transient( 'wp_ulike_guest_cleanup_result' ); ?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: number of rows removed */
						_n( 'Removed %s old guest lookup row. Votes and counts are untouched.', 'Removed %s old guest lookup rows. Votes and counts are untouched.', $removed, 'wp-ulike' ),
						number_format_i18n( $removed )
					)
				);
				?>
				<?php if ( 'scheduled' === $cleanup_flash ) : ?>
					<?php esc_html_e( 'WP ULike will keep trimming them once a day from now on.', 'wp-ulike' ); ?>
				<?php endif; ?>
			</p>
		</div>
	<?php elseif ( 'dismissed' === $cleanup_flash ) : ?>
		<div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'Left as it is. Nothing was removed and you will not be asked again.', 'wp-ulike' ); ?></p></div>
	<?php elseif ( 'stopped' === $cleanup_flash ) : ?>
		<div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'Daily cleanup is off. Nothing else changed.', 'wp-ulike' ); ?></p></div>
	<?php elseif ( 'resumed' === $cleanup_flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Daily cleanup is on again.', 'wp-ulike' ); ?></p></div>
	<?php endif; ?>

	<?php if ( 'saved' === $features_flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Features updated. Your settings were kept, so switching a feature back on restores it exactly as it was.', 'wp-ulike' ); ?></p></div>
	<?php endif; ?>

	<div class="wp-ulike-about__layout">

		<div class="wp-ulike-about__main">

			<?php $storage_upgrade = $data['storage_upgrade'] ?? null; ?>
			<?php if ( ! empty( $storage_upgrade ) ) : ?>
				<?php
				$task_modifier = 'cleanup' === ( $storage_upgrade['phase'] ?? '' )
					? ' wp-ulike-about-card--task-cleanup'
					: ' wp-ulike-about-card--task-optional';
				?>
				<div class="wp-ulike-about-card<?php echo esc_attr( $task_modifier ); ?>" role="region" aria-label="<?php echo esc_attr( $storage_upgrade['title'] ?? '' ); ?>">
					<div class="wp-ulike-about-task__header">
						<h2 class="wp-ulike-about-card__title"><?php echo esc_html( $storage_upgrade['title'] ?? '' ); ?></h2>
					</div>
					<?php if ( ! empty( $storage_upgrade['intro'] ) ) : ?>
						<p class="wp-ulike-about-task__intro"><?php echo esc_html( $storage_upgrade['intro'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $storage_upgrade['reassurance'] ) && is_array( $storage_upgrade['reassurance'] ) ) : ?>
						<ul class="wp-ulike-about-task__reassurance">
							<?php foreach ( $storage_upgrade['reassurance'] as $point ) : ?>
								<li><?php echo esc_html( $point ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<div class="wp-ulike-about-status wp-ulike-about-task__status" role="list">
						<div class="wp-ulike-about-status__item wp-ulike-about-status__item--<?php echo esc_attr( $storage_upgrade['state'] ?? 'neutral' ); ?>" role="listitem">
							<span class="wp-ulike-about-status__label"><?php esc_html_e( 'Status', 'wp-ulike' ); ?></span>
							<span class="wp-ulike-about-status__value"><?php echo esc_html( $storage_upgrade['status'] ?? '' ); ?></span>
							<?php if ( ! empty( $storage_upgrade['progress'] ) ) : ?>
								<span class="wp-ulike-about-status__hint"><?php echo esc_html( $storage_upgrade['progress'] ); ?></span>
							<?php endif; ?>
						</div>
					</div>
					<p class="wp-ulike-about-task__actions">
						<a class="button button-primary" href="<?php echo esc_url( $storage_upgrade['url'] ?? '#' ); ?>">
							<?php echo esc_html( $storage_upgrade['cta_label'] ?? 'Get started' ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<?php $cleanup = $data['guest_cleanup'] ?? null; ?>
			<?php if ( ! empty( $cleanup ) ) : ?>
				<div class="wp-ulike-about-card wp-ulike-about-card--task-cleanup" role="region" aria-label="<?php echo esc_attr( $cleanup['title'] ); ?>">
					<div class="wp-ulike-about-task__header">
						<h2 class="wp-ulike-about-card__title"><?php echo esc_html( $cleanup['title'] ); ?></h2>
					</div>
					<p class="wp-ulike-about-task__intro"><?php echo esc_html( $cleanup['intro'] ); ?></p>
					<form class="wp-ulike-about-task__actions" method="post" action="<?php echo esc_url( $cleanup['url'] ); ?>">
						<input type="hidden" name="action" value="wp_ulike_guest_cleanup" />
						<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $cleanup['nonce'] ); ?>" />
						<?php if ( 'enabled' === ( $cleanup['state'] ?? '' ) ) : ?>
							<?php if ( ! empty( $cleanup['stop_label'] ) ) : ?>
								<button type="submit" name="mode" value="disable" class="button"><?php echo esc_html( $cleanup['stop_label'] ); ?></button>
							<?php endif; ?>
						<?php elseif ( 'disabled' === ( $cleanup['state'] ?? '' ) ) : ?>
							<button type="submit" name="mode" value="enable" class="button button-primary"><?php echo esc_html( $cleanup['start_label'] ); ?></button>
						<?php else : ?>
							<button type="submit" name="mode" value="auto" class="button button-primary"><?php echo esc_html( $cleanup['auto_label'] ); ?></button>
							<button type="submit" name="mode" value="once" class="button button-secondary"><?php echo esc_html( $cleanup['run_label'] ); ?></button>
							<button type="submit" name="mode" value="dismiss" class="button-link"><?php echo esc_html( $cleanup['dismiss_label'] ); ?></button>
						<?php endif; ?>
					</form>
				</div>
			<?php endif; ?>

			<!-- Status -->
			<div class="wp-ulike-about-card">
				<div class="wp-ulike-about-card__header">
					<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'At a glance', 'wp-ulike' ); ?></h2>
					<span class="wp-ulike-about-card__links">
						<?php if ( ! empty( $health['preview_url'] ) ) : ?>
							<a class="wp-ulike-about-card__link" href="<?php echo esc_url( $health['preview_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View on site', 'wp-ulike' ); ?></a>
						<?php endif; ?>
						<?php if ( class_exists( 'WP_Ulike_Health' ) ) : ?>
							<a class="wp-ulike-about-card__link" href="<?php echo esc_url( WP_Ulike_Health::get_site_health_url() ); ?>"><?php echo esc_html( 'Site Health' ); ?></a>
						<?php endif; ?>
					</span>
				</div>
				<?php if ( ! empty( $data['summary'] ) ) : ?>
					<p class="wp-ulike-about-summary"><?php echo wp_kses_post( $data['summary'] ); ?></p>
				<?php endif; ?>
				<?php foreach ( $group_order as $group_key ) : ?>
					<?php if ( empty( $status_groups[ $group_key ] ) ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<div class="wp-ulike-about-status-group">
						<?php if ( ! empty( $group_labels[ $group_key ] ) ) : ?>
							<h3 class="wp-ulike-about-status-group__title"><?php echo esc_html( $group_labels[ $group_key ] ); ?></h3>
						<?php endif; ?>
						<div class="wp-ulike-about-status" role="list">
							<?php foreach ( $status_groups[ $group_key ] as $row ) : ?>
								<?php $state = isset( $row['state'] ) ? $row['state'] : 'neutral'; ?>
								<div class="wp-ulike-about-status__item wp-ulike-about-status__item--<?php echo esc_attr( $state ); ?>" role="listitem">
									<span class="wp-ulike-about-status__label"><?php echo esc_html( $row['label'] ?? '' ); ?></span>
									<span class="wp-ulike-about-status__value"><?php echo esc_html( $row['value'] ?? '' ); ?></span>
									<?php if ( ! empty( $row['hint'] ) ) : ?>
										<span class="wp-ulike-about-status__hint"><?php echo esc_html( $row['hint'] ); ?></span>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
				<?php if ( empty( $health['tables_ok'] ) ) : ?>
					<div class="wp-ulike-about-card__hint wp-ulike-about-card__hint--warn" role="alert">
						<p>
							<strong><?php esc_html_e( 'Database tables need repair', 'wp-ulike' ); ?></strong>
							<?php if ( ! empty( $health['missing_tables'] ) ) : ?>
								<?php
								echo esc_html(
									sprintf(
										/* translators: %s: comma-separated table labels */
										__( 'Missing tables: %s.', 'wp-ulike' ),
										implode( ', ', (array) $health['missing_tables'] )
									)
								);
								?>
							<?php else : ?>
								<?php esc_html_e( 'One or more WP ULike tables are missing.', 'wp-ulike' ); ?>
							<?php endif; ?>
						</p>
						<?php if ( ! empty( $health['install_errors'] ) && is_array( $health['install_errors'] ) ) : ?>
							<ul>
								<?php foreach ( $health['install_errors'] as $table_label => $db_error ) : ?>
									<li>
										<code><?php echo esc_html( (string) $table_label ); ?></code>:
										<?php echo esc_html( (string) $db_error ); ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<?php if ( ! empty( $data['repair_tables_url'] ) ) : ?>
							<p>
								<a class="button button-secondary" href="<?php echo esc_url( $data['repair_tables_url'] ); ?>">
									<?php esc_html_e( 'Repair database tables', 'wp-ulike' ); ?>
								</a>
							</p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ( ! empty( $data['flush_stats_cache_url'] ) ) : ?>
					<p>
						<a class="button button-secondary" href="<?php echo esc_url( $data['flush_stats_cache_url'] ); ?>">
							<?php esc_html_e( 'Refresh statistics cache', 'wp-ulike' ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>

			<!-- Features -->
			<?php $features = isset( $data['features'] ) && is_array( $data['features'] ) ? $data['features'] : array(); ?>
			<?php if ( ! empty( $features ) ) : ?>
				<div class="wp-ulike-about-card wp-ulike-features" role="region" aria-label="<?php esc_attr_e( 'Features', 'wp-ulike' ); ?>">
					<div class="wp-ulike-about-card__header">
						<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'Features', 'wp-ulike' ); ?></h2>
					</div>
					<p class="wp-ulike-features__intro"><?php esc_html_e( 'Switch off what you do not use — its settings and menu items disappear from WP ULike. Nothing is deleted, so switching a feature back on restores it exactly as you had it.', 'wp-ulike' ); ?></p>
					<form id="wp-ulike-features-form" method="post" action="<?php echo esc_url( isset( $data['features_url'] ) ? $data['features_url'] : admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="wp_ulike_save_features" />
						<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( isset( $data['features_nonce'] ) ? $data['features_nonce'] : '' ); ?>" />
						<ul class="wp-ulike-features__grid">
							<?php foreach ( $features as $feature ) : ?>
								<?php $field_id = 'wp-ulike-feature-' . sanitize_key( $feature['key'] ); ?>
								<li class="wp-ulike-features__item<?php echo empty( $feature['enabled'] ) ? ' wp-ulike-features__item--off' : ''; ?>">
									<span class="dashicons dashicons-<?php echo esc_attr( $feature['icon'] ); ?>" aria-hidden="true"></span>
									<span class="wp-ulike-features__body">
										<label class="wp-ulike-features__label" for="<?php echo esc_attr( $field_id ); ?>">
											<?php echo esc_html( $feature['label'] ); ?>
											<?php if ( ! empty( $feature['badge'] ) ) : ?>
												<span class="wp-ulike-about__badge wp-ulike-about__badge--pro"><?php echo esc_html( $feature['badge'] ); ?></span>
											<?php endif; ?>
										</label>
										<?php if ( ! empty( $feature['description'] ) ) : ?>
											<span class="wp-ulike-features__desc"><?php echo esc_html( $feature['description'] ); ?></span>
										<?php endif; ?>
										<?php if ( ! empty( $feature['url'] ) ) : ?>
											<a class="wp-ulike-features__link" href="<?php echo esc_url( $feature['url'] ); ?>"><?php esc_html_e( 'Open', 'wp-ulike' ); ?></a>
										<?php endif; ?>
									</span>
									<span class="wp-ulike-features__switch">
										<input
											type="checkbox"
											id="<?php echo esc_attr( $field_id ); ?>"
											name="wp_ulike_features[]"
											value="<?php echo esc_attr( $feature['key'] ); ?>"
											<?php if ( ! empty( $feature['warning'] ) ) : ?>
												data-wp-ulike-feature-warning="<?php echo esc_attr( $feature['warning'] ); ?>"
											<?php endif; ?>
											<?php checked( ! empty( $feature['enabled'] ) ); ?>
										/>
										<span aria-hidden="true"></span>
									</span>
								</li>
							<?php endforeach; ?>
						</ul>
						<noscript>
							<p class="wp-ulike-features__actions">
								<button type="submit" class="button button-secondary"><?php esc_html_e( 'Save Features', 'wp-ulike' ); ?></button>
							</p>
						</noscript>
					</form>
				</div>
				<script>
				/* Switching a feature adds or removes admin menu items and settings
				   tabs, so the page has to reload for the change to be visible.
				   Submitting the whole form does that; the inputs stay enabled
				   because disabled ones are left out of the POST. */
				( function () {
					var form = document.getElementById( 'wp-ulike-features-form' );

					if ( ! form ) {
						return;
					}

					form.addEventListener( 'change', function ( event ) {
						if ( ! event.target.matches( 'input[name="wp_ulike_features[]"]' ) ) {
							return;
						}

						/* Some modules stop recording data or stop resolving URLs
						   while off. Those carry a warning: confirm before the
						   switch takes effect, and put the switch back if the
						   answer is no. Turning a module back ON never asks. */
						var warning = event.target.getAttribute( 'data-wp-ulike-feature-warning' );

						if ( ! event.target.checked && warning && ! window.confirm( warning ) ) {
							event.target.checked = true;
							return;
						}

						form.classList.add( 'is-saving' );
						form.submit();
					} );
				} )();
				</script>
			<?php endif; ?>

			<?php if ( ! empty( $data['show_pro_upsell'] ) && ! empty( $data['pro_upsell'] ) ) : ?>
				<?php $upsell = $data['pro_upsell']; ?>
				<div class="wp-ulike-about-card wp-ulike-about-card--upsell">
					<div class="wp-ulike-about-upsell__header">
						<h2 class="wp-ulike-about-card__title"><?php echo esc_html( $upsell['headline'] ?? '' ); ?></h2>
						<?php if ( ! empty( $upsell['intro'] ) ) : ?>
							<p class="wp-ulike-about-upsell__intro"><?php echo esc_html( $upsell['intro'] ); ?></p>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $upsell['features'] ) && is_array( $upsell['features'] ) ) : ?>
						<ul class="wp-ulike-about-upsell__features">
							<?php foreach ( $upsell['features'] as $feature ) : ?>
								<li class="wp-ulike-about-upsell__feature<?php echo ! empty( $feature['highlight'] ) ? ' wp-ulike-about-upsell__feature--highlight' : ''; ?>">
									<span class="dashicons dashicons-<?php echo esc_attr( $feature['icon'] ?? 'yes-alt' ); ?>" aria-hidden="true"></span>
									<span class="wp-ulike-about-upsell__feature-body">
										<strong class="wp-ulike-about-upsell__feature-title"><?php echo esc_html( $feature['title'] ?? '' ); ?></strong>
										<span class="wp-ulike-about-upsell__feature-desc"><?php echo esc_html( $feature['description'] ?? '' ); ?></span>
									</span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php if ( ! empty( $upsell['footnote'] ) ) : ?>
						<p class="wp-ulike-about-upsell__footnote"><?php echo esc_html( $upsell['footnote'] ); ?></p>
					<?php endif; ?>
					<p class="wp-ulike-about-upsell__actions">
						<a class="button button-primary" href="<?php echo esc_url( $data['upgrade_url'] ?? add_query_arg( array( 'utm_source' => 'about-page', 'utm_campaign' => 'gopro', 'utm_medium' => 'wp-dash' ), WP_ULIKE_PLUGIN_URI . 'upgrade/' ) ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $upsell['cta_label'] ?? __( 'Get Pro', 'wp-ulike' ) ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<!-- Advanced (collapsed) -->
			<?php $troubleshooting = (array) ( $data['troubleshooting'] ?? array() ); ?>
			<details class="wp-ulike-about-card wp-ulike-about-card--details">
				<summary class="wp-ulike-about-card__title"><?php esc_html_e( 'Troubleshooting tips', 'wp-ulike' ); ?></summary>
				<div class="wp-ulike-about-card__body">
					<?php if ( ! empty( $troubleshooting ) ) : ?>
						<ul class="wp-ulike-about-troubleshoot__list">
							<?php foreach ( $troubleshooting as $item ) : ?>
								<li>
									<?php echo esc_html( $item['text'] ?? '' ); ?>
									<?php if ( ! empty( $item['url'] ) && ! empty( $item['link'] ) ) : ?>
										<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['link'] ); ?></a>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p><?php esc_html_e( 'No extra tips right now. Your setup looks good.', 'wp-ulike' ); ?></p>
					<?php endif; ?>
				</div>
			</details>

		</div>

		<aside class="wp-ulike-about__aside" aria-label="<?php echo esc_attr( 'Plugin details and settings tools' ); ?>">
			<div class="wp-ulike-about-card">
				<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'Plugin info', 'wp-ulike' ); ?></h2>
				<dl class="wp-ulike-about-meta">
					<div>
						<dt><?php esc_html_e( 'Edition', 'wp-ulike' ); ?></dt>
						<dd><?php echo $is_pro ? esc_html__( 'Pro', 'wp-ulike' ) : esc_html__( 'Free', 'wp-ulike' ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'WP ULike', 'wp-ulike' ); ?></dt>
						<dd><?php echo esc_html( WP_ULIKE_VERSION ); ?></dd>
					</div>
					<?php if ( $is_pro && ! empty( $data['pro_version'] ) ) : ?>
						<div>
							<dt><?php esc_html_e( 'Pro package', 'wp-ulike' ); ?></dt>
							<dd><?php echo esc_html( $data['pro_version'] ); ?></dd>
						</div>
					<?php endif; ?>
					<div>
						<dt><?php esc_html_e( 'WordPress', 'wp-ulike' ); ?></dt>
						<dd><?php echo esc_html( $data['wp_version'] ?? '' ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Database schema', 'wp-ulike' ); ?></dt>
						<dd><?php echo esc_html( $health['db_version'] ?? WP_ULIKE_DB_VERSION ); ?></dd>
					</div>
					<?php foreach ( (array) ( $data['sidebar_meta'] ?? array() ) as $meta ) : ?>
						<div>
							<dt><?php echo esc_html( $meta['label'] ?? '' ); ?></dt>
							<dd>
								<?php if ( ! empty( $meta['url'] ) ) : ?>
									<a href="<?php echo esc_url( $meta['url'] ); ?>"><?php echo esc_html( $meta['value'] ?? '' ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $meta['value'] ?? '' ); ?>
								<?php endif; ?>
							</dd>
						</div>
					<?php endforeach; ?>
				</dl>
			</div>

			<!-- Help -->
			<div class="wp-ulike-about-card">
				<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'Help & resources', 'wp-ulike' ); ?></h2>
				<ul class="wp-ulike-about-help">
					<?php foreach ( (array) ( $data['help_links'] ?? array() ) as $link ) : ?>
						<li>
							<a href="<?php echo esc_url( $link['url'] ?? '#' ); ?>" target="_blank" rel="noopener noreferrer">
								<span class="dashicons dashicons-<?php echo esc_attr( $link['icon'] ?? 'external' ); ?>" aria-hidden="true"></span>
								<span class="wp-ulike-about-help__text">
									<strong><?php echo esc_html( $link['title'] ?? '' ); ?></strong>
									<?php if ( ! empty( $link['desc'] ) ) : ?>
										<span><?php echo esc_html( $link['desc'] ); ?></span>
									<?php endif; ?>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="wp-ulike-about-card wp-ulike-about-card--muted wp-ulike-about-backup" id="wp-ulike-settings-backup">
				<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'Settings backup', 'wp-ulike' ); ?></h2>
				<div class="wp-ulike-about-backup__actions">
					<p class="wp-ulike-about-backup__intro"><?php echo esc_html( $data['backup_intro'] ?? '' ); ?></p>
					<a class="button button-secondary" href="<?php echo esc_url( $data['export_url'] ?? '#' ); ?>"><?php esc_html_e( 'Export', 'wp-ulike' ); ?></a>
					<details class="wp-ulike-about-backup__import"<?php echo $import_open ? ' open' : ''; ?>>
						<summary><?php esc_html_e( 'Import settings', 'wp-ulike' ); ?></summary>
						<form class="wp-ulike-about-backup__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" onsubmit='return window.confirm(<?php echo wp_json_encode( $data['backup_import_confirm'] ?? __( 'Import will replace your current WP ULike settings and customizer values. Continue?', 'wp-ulike' ) ); ?>);'>
							<input type="hidden" name="action" value="wp_ulike_import_settings" />
							<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $data['import_nonce'] ?? '' ); ?>" />
							<label class="wp-ulike-about-backup__label" for="wp-ulike-settings-file"><?php esc_html_e( 'JSON file', 'wp-ulike' ); ?></label>
							<input id="wp-ulike-settings-file" class="wp-ulike-about-backup__file" type="file" name="settings_file" accept="application/json,.json" required />
							<button type="submit" class="button button-secondary"><?php esc_html_e( 'Import settings', 'wp-ulike' ); ?></button>
						</form>
					</details>
				</div>
			</div>
		</aside>

	</div>
</div>
