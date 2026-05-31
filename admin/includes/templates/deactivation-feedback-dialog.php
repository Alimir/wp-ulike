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
?>
<div id="wp-ulike-deactivate-feedback-dialog-wrapper" hidden>
	<div class="wp-ulike-deactivate-feedback">
		<h2 class="wp-ulike-deactivate-feedback__title"><?php esc_html_e( 'Deactivate WP ULike', 'wp-ulike' ); ?></h2>
		<p class="wp-ulike-deactivate-feedback__lead">
			<?php esc_html_e( 'Mind sharing why? It helps us improve WP ULike. Totally optional.', 'wp-ulike' ); ?>
		</p>
		<form id="wp-ulike-deactivate-feedback-dialog-form" class="wp-ulike-deactivate-feedback-form">
			<fieldset>
				<legend class="screen-reader-text"><?php esc_html_e( 'Deactivation reason', 'wp-ulike' ); ?></legend>
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
				<?php endforeach; ?>
			</fieldset>
		</form>
	</div>
</div>
