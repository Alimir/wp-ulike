<?php
/**
 * Hidden markup for the activation welcome pointer.
 *
 * @package WP_ULike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$preview_url = '';
if ( class_exists( 'WP_Ulike_Overview' ) ) {
	$health      = WP_Ulike_Overview::get_health_report();
	$preview_url = ! empty( $health['preview_url'] ) ? $health['preview_url'] : '';
}

if ( empty( $preview_url ) ) {
	$sample_post = get_posts(
		array(
			'numberposts' => 1,
			'post_status' => 'publish',
			'post_type'   => 'post',
		)
	);
	if ( ! empty( $sample_post[0] ) ) {
		$preview_url = get_permalink( $sample_post[0]->ID );
	}
}

$settings_url = class_exists( 'WP_Ulike_Overview' )
	? WP_Ulike_Overview::get_settings_url( 'content-types' )
	: admin_url( 'admin.php?page=wp-ulike-settings&settings-page=content-types' );
?>
<div id="wp-ulike-activation-pointer-template" hidden>
	<div class="wp-ulike-activation-pointer__panel">
		<button type="button" class="wp-ulike-activation-pointer__close" aria-label="<?php echo esc_attr( 'Dismiss' ); ?>">
			<span aria-hidden="true">&times;</span>
		</button>
		<h3 class="wp-ulike-activation-pointer__title"><?php esc_html_e( 'Thanks for installing WP ULike!', 'wp-ulike' ); ?></h3>
		<p class="wp-ulike-activation-pointer__lead">
			<?php esc_html_e( 'Like buttons appear on single posts by default. Home and blog lists stay off until you enable them under Content Types. Open a post to try a button, then adjust where they show.', 'wp-ulike' ); ?>
		</p>
		<p class="wp-ulike-activation-pointer__links">
			<?php
			printf(
				/* translators: 1: Overview page URL, 2: Documentation URL. */
				wp_kses_post( __( 'Need a hand? Visit <a href="%1$s">Overview</a> in this menu or read the <a href="%2$s" target="_blank" rel="noopener noreferrer">documentation</a>.', 'wp-ulike' ) ),
				esc_url( admin_url( 'admin.php?page=wp-ulike-about' ) ),
				esc_url( add_query_arg(
					array(
						'utm_source'   => 'activation-pointer',
						'utm_campaign' => 'plugin-uri',
						'utm_medium'   => 'wp-dash',
					),
					'https://docs.wpulike.com/'
				) )
			);
			?>
		</p>
		<p class="wp-ulike-activation-pointer__actions">
			<?php if ( ! empty( $preview_url ) ) : ?>
				<a class="button button-primary" href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'View sample post', 'wp-ulike' ); ?>
				</a>
				<a class="button button-secondary" href="<?php echo esc_url( $settings_url ); ?>">
					<?php esc_html_e( 'Settings', 'wp-ulike' ); ?>
				</a>
			<?php else : ?>
				<a class="button button-primary" href="<?php echo esc_url( $settings_url ); ?>">
					<?php esc_html_e( 'Open Settings', 'wp-ulike' ); ?>
				</a>
			<?php endif; ?>
			<button type="button" class="wp-ulike-activation-pointer__dismiss">
				<?php esc_html_e( 'Got it', 'wp-ulike' ); ?>
			</button>
		</p>
	</div>
</div>
