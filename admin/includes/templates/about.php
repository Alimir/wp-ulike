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
$import_open = in_array( $import_flash, array( 'error_upload', 'error_json', 'error_payload', 'error' ), true );
$is_pro         = ! empty( $data['is_pro'] );
$health         = isset( $data['health'] ) ? $data['health'] : array();
$status_groups  = class_exists( 'WP_Ulike_Overview' ) ? WP_Ulike_Overview::group_status_rows( $data['status_rows'] ?? array() ) : array();
$group_labels   = $data['status_groups'] ?? array();
$group_order    = array( 'engagement', 'setup', 'pro' );
?>

<div class="wrap wp-ulike-about">

	<h1 class="wp-ulike-about__title">
		<?php esc_html_e( 'Help', 'wp-ulike' ); ?>
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
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'This file does not look like a WP ULike settings export. Use a file exported from Settings backup in the Help sidebar.', 'wp-ulike' ); ?></p></div>
	<?php elseif ( 'error' === $import_flash ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Settings import failed. Please try again.', 'wp-ulike' ); ?></p></div>
	<?php endif; ?>

	<?php if ( 'success' === $repair_flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Database tables repaired successfully.', 'wp-ulike' ); ?></p></div>
	<?php elseif ( 'failed' === $repair_flash ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Some database tables could not be created. Please contact your host or try deactivating and reactivating the plugin.', 'wp-ulike' ); ?></p></div>
	<?php endif; ?>

	<div class="wp-ulike-about__layout">

		<div class="wp-ulike-about__main">

			<!-- Status -->
			<div class="wp-ulike-about-card">
				<div class="wp-ulike-about-card__header">
					<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'At a glance', 'wp-ulike' ); ?></h2>
					<a class="wp-ulike-about-card__link" href="<?php echo esc_url( $health['statistics_url'] ?? admin_url( 'admin.php?page=wp-ulike-statistics' ) ); ?>"><?php esc_html_e( 'Statistics', 'wp-ulike' ); ?></a>
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
						<?php if ( ! empty( $data['repair_tables_url'] ) ) : ?>
							<p>
								<a class="button button-secondary" href="<?php echo esc_url( $data['repair_tables_url'] ); ?>">
									<?php esc_html_e( 'Repair database tables', 'wp-ulike' ); ?>
								</a>
							</p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<!-- Quick actions -->
			<div class="wp-ulike-about-card">
				<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'Quick actions', 'wp-ulike' ); ?></h2>
				<div class="wp-ulike-about-actions">
					<?php foreach ( (array) ( $data['quick_actions'] ?? array() ) as $action ) : ?>
						<?php
						$btn_class = ! empty( $action['primary'] ) ? 'button-primary' : 'button-secondary';
						$external  = ! empty( $action['external'] );
						$icon      = ! empty( $action['icon'] ) ? $action['icon'] : 'arrow-right-alt';
						?>
						<a
							class="button <?php echo esc_attr( $btn_class ); ?> wp-ulike-about-actions__btn"
							href="<?php echo esc_url( $action['url'] ?? '#' ); ?>"
							<?php echo $external ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
						>
							<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
							<?php echo esc_html( $action['label'] ?? '' ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>

			<?php if ( ! empty( $data['pro_modules'] ) ) : ?>
				<div class="wp-ulike-about-card wp-ulike-about-card--pro">
					<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'Pro tools', 'wp-ulike' ); ?></h2>
					<ul class="wp-ulike-about-modules">
						<?php foreach ( $data['pro_modules'] as $module ) : ?>
							<li class="wp-ulike-about-modules__item">
								<span class="dashicons dashicons-<?php echo esc_attr( $module['icon'] ?? 'admin-generic' ); ?>" aria-hidden="true"></span>
								<div class="wp-ulike-about-modules__body">
									<strong>
										<?php echo esc_html( $module['title'] ?? '' ); ?>
										<?php if ( ! empty( $module['badge'] ) ) : ?>
											<span class="wp-ulike-about__badge wp-ulike-about__badge--pro"><?php echo esc_html( $module['badge'] ); ?></span>
										<?php endif; ?>
									</strong>
									<?php if ( ! empty( $module['description'] ) ) : ?>
										<p><?php echo esc_html( $module['description'] ); ?></p>
									<?php endif; ?>
								</div>
								<?php if ( ! empty( $module['url'] ) ) : ?>
									<a class="button button-secondary" href="<?php echo esc_url( $module['url'] ); ?>">
										<?php esc_html_e( 'Open', 'wp-ulike' ); ?>
									</a>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php elseif ( ! empty( $data['show_pro_upsell'] ) && ! empty( $data['pro_upsell'] ) ) : ?>
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
							<?php echo esc_html( $upsell['cta_label'] ?? __( 'Explore Pro', 'wp-ulike' ) ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

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

		<aside class="wp-ulike-about__aside" aria-label="<?php esc_attr_e( 'Plugin details and settings tools', 'wp-ulike' ); ?>">
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
							<button type="submit" class="button button-secondary"><?php esc_html_e( 'Import', 'wp-ulike' ); ?></button>
						</form>
					</details>
				</div>
			</div>
		</aside>

	</div>
</div>
