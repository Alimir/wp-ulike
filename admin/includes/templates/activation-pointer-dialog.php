<?php
/**
 * One-line menu pointer after activation.
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
		<h3 class="wp-ulike-activation-pointer__title"><?php esc_html_e( 'WP ULike is on', 'wp-ulike' ); ?></h3>
		<p class="wp-ulike-activation-pointer__lead">
			<?php
			echo $wizard_pending
				? esc_html__( 'Three answers and likes are live. Open Settings to finish.', 'wp-ulike' )
				: esc_html__( 'Buttons stay on single posts. Home and Pages stay off until you turn them on in Content Types.', 'wp-ulike' );
			?>
		</p>
		<p class="wp-ulike-activation-pointer__actions">
			<a class="button button-primary" href="<?php echo esc_url( $settings_url ); ?>">
				<?php echo $wizard_pending ? esc_html__( 'Finish setup', 'wp-ulike' ) : esc_html__( 'Content Types', 'wp-ulike' ); ?>
			</a>
			<button type="button" class="wp-ulike-activation-pointer__dismiss">
				<?php esc_html_e( 'Got it', 'wp-ulike' ); ?>
			</button>
		</p>
	</div>
</div>
