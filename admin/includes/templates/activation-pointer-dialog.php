<?php
/**
 * Hidden markup for the activation welcome pointer.
 *
 * @package WP_ULike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="wp-ulike-activation-pointer-template" hidden>
	<div class="wp-ulike-activation-pointer__panel">
		<button type="button" class="wp-ulike-activation-pointer__close" aria-label="<?php esc_attr_e( 'Dismiss', 'wp-ulike' ); ?>">
			<span aria-hidden="true">&times;</span>
		</button>
		<h3 class="wp-ulike-activation-pointer__title"><?php esc_html_e( 'Thanks for installing WP ULike!', 'wp-ulike' ); ?></h3>
		<p class="wp-ulike-activation-pointer__lead">
			<?php esc_html_e( 'Like buttons can appear on your posts automatically. Open WP ULike to customize templates, choose where buttons show up, and view engagement stats.', 'wp-ulike' ); ?>
		</p>
		<p class="wp-ulike-activation-pointer__links">
			<?php
			printf(
				/* translators: 1: Help page URL, 2: Documentation URL. */
				wp_kses_post( __( 'Need a hand? Visit <a href="%1$s">Help</a> in this menu or read the <a href="%2$s" target="_blank" rel="noopener noreferrer">documentation</a>.', 'wp-ulike' ) ),
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
			<a class="button button-primary" href="<?php echo esc_url( class_exists( 'WP_Ulike_Overview' ) ? WP_Ulike_Overview::get_settings_url( 'content-types' ) : admin_url( 'admin.php?page=wp-ulike-settings&settings-page=content-types' ) ); ?>">
				<?php esc_html_e( 'Open Settings', 'wp-ulike' ); ?>
			</a>
			<button type="button" class="button button-secondary wp-ulike-activation-pointer__dismiss">
				<?php esc_html_e( 'Got it', 'wp-ulike' ); ?>
			</button>
		</p>
	</div>
</div>
