<?php
/**
 * Menu pointer after activation.
 *
 * @package WP_ULike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings_url   = class_exists( 'WP_Ulike_Overview' )
	? WP_Ulike_Overview::get_settings_url( 'content-types' )
	: admin_url( 'admin.php?page=wp-ulike-settings&settings-page=content-types' );
$wizard_pending = class_exists( 'WP_Ulike_Setup_Wizard' ) && WP_Ulike_Setup_Wizard::is_pending();
?>
<div id="wp-ulike-activation-pointer-template" hidden>
	<div class="wp-ulike-activation-pointer__panel">
		<button type="button" class="wp-ulike-activation-pointer__close" aria-label="<?php esc_attr_e( 'Dismiss', 'wp-ulike' ); ?>">
			<span aria-hidden="true">&times;</span>
		</button>
		<p class="wp-ulike-activation-pointer__kicker"><?php esc_html_e( 'WP ULike', 'wp-ulike' ); ?></p>
		<h3 class="wp-ulike-activation-pointer__title">
			<?php echo $wizard_pending
				? esc_html__( 'Likes are ready.', 'wp-ulike' )
				: esc_html__( 'Likes are back.', 'wp-ulike' ); ?>
		</h3>
		<p class="wp-ulike-activation-pointer__lead">
			<?php echo $wizard_pending
				? esc_html__( 'You choose where likes live. Anyone can leave one without signing in.', 'wp-ulike' )
				: esc_html__( 'Likes are on your posts again. Home and Pages wait until you invite them.', 'wp-ulike' ); ?>
		</p>
		<p class="wp-ulike-activation-pointer__actions">
			<a class="button button-primary" href="<?php echo esc_url( $settings_url ); ?>">
				<?php echo $wizard_pending
					? esc_html__( 'Continue', 'wp-ulike' )
					: esc_html__( 'Open settings', 'wp-ulike' ); ?>
			</a>
			<button type="button" class="wp-ulike-activation-pointer__dismiss">
				<?php echo $wizard_pending
					? esc_html__( 'Later', 'wp-ulike' )
					: esc_html__( 'Got it', 'wp-ulike' ); ?>
			</button>
		</p>
	</div>
</div>
