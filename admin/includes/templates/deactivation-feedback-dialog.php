<?php
/**
 * Hidden markup for deactivation feedback modal.
 *
 * @package WP_ULike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$reasons = class_exists( 'WP_Ulike_Deactivation_Feedback' ) ? WP_Ulike_Deactivation_Feedback::get_reasons() : array();
$total_votes = function_exists( 'wp_ulike_count_all_logs' ) ? (int) wp_ulike_count_all_logs() : 0;
?>
<div id="wp-ulike-deactivate-feedback-dialog-wrapper" hidden>
	<div class="wp-ulike-deactivate-feedback">
		<h2 class="wp-ulike-deactivate-feedback__title"><?php esc_html_e( 'Deactivate WP ULike', 'wp-ulike' ); ?></h2>
		<p class="wp-ulike-deactivate-feedback__lead">
			<?php esc_html_e( 'Mind sharing why? It helps us improve WP ULike. Totally optional.', 'wp-ulike' ); ?>
		</p>
		<?php if ( $total_votes > 0 ) : ?>
			<p class="wp-ulike-deactivate-feedback__note">
				<?php esc_html_e( 'Your vote data stays on this site if you reactivate later.', 'wp-ulike' ); ?>
			</p>
		<?php endif; ?>
		<form id="wp-ulike-deactivate-feedback-dialog-form" class="wp-ulike-deactivate-feedback-form">
			<fieldset>
				<legend class="screen-reader-text"><?php esc_html_e( 'Deactivation reason', 'wp-ulike' ); ?></legend>
				<p class="wp-ulike-deactivate-feedback-reason-error" hidden role="alert">
					<?php esc_html_e( 'Please select a reason before submitting.', 'wp-ulike' ); ?>
				</p>
				<?php foreach ( $reasons as $reason_key => $reason ) : ?>
					<p>
						<label>
							<input type="radio" name="reason_key" value="<?php echo esc_attr( $reason_key ); ?>" />
							<?php echo esc_html( $reason['title'] ?? '' ); ?>
						</label>
					</p>
					<?php if ( ! empty( $reason['placeholder'] ) ) : ?>
						<p class="wp-ulike-deactivate-feedback-details" data-reason="<?php echo esc_attr( $reason_key ); ?>" hidden>
							<input
								type="text"
								name="details"
								class="regular-text widefat"
								placeholder="<?php echo esc_attr( $reason['placeholder'] ); ?>"
								autocomplete="off"
							/>
						</p>
					<?php endif; ?>
					<?php if ( 'not_working' === $reason_key ) : ?>
						<p class="wp-ulike-deactivate-feedback-context" data-reason="not_working" hidden>
							<?php
							echo wp_kses_post(
								sprintf(
									/* translators: %s: Help admin page link */
									__( 'Buttons appear on single posts by default—not on the homepage. %s for a quick setup checklist.', 'wp-ulike' ),
									'<a href="' . esc_url( class_exists( 'WP_Ulike_Overview' ) ? WP_Ulike_Overview::get_about_url() : admin_url( 'admin.php?page=wp-ulike-about' ) ) . '">' . esc_html__( 'Open Help', 'wp-ulike' ) . '</a>'
								)
							);
							?>
						</p>
					<?php endif; ?>
				<?php endforeach; ?>
			</fieldset>
		</form>
	</div>
</div>
