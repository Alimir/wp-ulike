<?php
/**
 * Fresh-install setup wizard markup.
 *
 * @package WP_ULike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_buddypress = function_exists( 'is_buddypress' );
$has_bbpress    = function_exists( 'is_bbpress' );
$has_woocommerce = class_exists( 'WooCommerce' );
$likers_default  = $has_buddypress || $has_bbpress;
$guest_hint      = defined( 'WP_ULIKE_PRO_VERSION' )
	? __( 'Guests see a login popup, not a working like.', 'wp-ulike' )
	: __( 'Guests see a login prompt, not a working like.', 'wp-ulike' );
?>
<div id="wp-ulike-setup-wizard-root" hidden>
	<div class="wp-ulike-setup" role="dialog" aria-modal="true" aria-labelledby="wp-ulike-setup-title">
		<div class="wp-ulike-setup__backdrop"></div>
		<div class="wp-ulike-setup__card">
			<button type="button" class="wp-ulike-setup__close" data-setup-dismiss aria-label="<?php esc_attr_e( 'Save these choices and close', 'wp-ulike' ); ?>">
				<span aria-hidden="true">&times;</span>
			</button>

			<p class="wp-ulike-setup__kicker"><?php esc_html_e( '30 seconds', 'wp-ulike' ); ?></p>
			<h2 id="wp-ulike-setup-title" class="wp-ulike-setup__title"><?php esc_html_e( 'Get likes working on your site', 'wp-ulike' ); ?></h2>
			<p class="wp-ulike-setup__lead"><?php esc_html_e( 'Three answers. We turn the rest on for you.', 'wp-ulike' ); ?></p>

			<ol class="wp-ulike-setup__steps" aria-label="<?php esc_attr_e( 'Setup progress', 'wp-ulike' ); ?>">
				<li class="is-current" data-setup-step-label="1"><?php esc_html_e( 'Where', 'wp-ulike' ); ?></li>
				<li data-setup-step-label="2"><?php esc_html_e( 'Who', 'wp-ulike' ); ?></li>
				<li data-setup-step-label="3"><?php esc_html_e( 'After', 'wp-ulike' ); ?></li>
			</ol>

			<form class="wp-ulike-setup__form" id="wp-ulike-setup-form">
				<section class="wp-ulike-setup__panel is-active" data-setup-panel="1" aria-labelledby="wp-ulike-setup-step-1-title">
					<h3 id="wp-ulike-setup-step-1-title" class="wp-ulike-setup__panel-title"><?php esc_html_e( 'What should people like?', 'wp-ulike' ); ?></h3>
					<p class="wp-ulike-setup__panel-desc"><?php esc_html_e( 'Unchecked places stay off. You will not hunt for this later.', 'wp-ulike' ); ?></p>
					<div class="wp-ulike-setup__choices wp-ulike-setup__choices--grid" role="group" aria-labelledby="wp-ulike-setup-step-1-title">
						<label class="wp-ulike-setup__choice">
							<input type="checkbox" name="surfaces[]" value="post" checked>
							<span class="wp-ulike-setup__choice-body">
								<span class="wp-ulike-setup__choice-top">
									<span class="wp-ulike-setup__choice-title"><?php esc_html_e( 'Posts', 'wp-ulike' ); ?></span>
									<span class="wp-ulike-setup__badge"><?php esc_html_e( 'Recommended', 'wp-ulike' ); ?></span>
								</span>
								<span class="wp-ulike-setup__choice-hint"><?php esc_html_e( 'Each blog post.', 'wp-ulike' ); ?></span>
							</span>
						</label>
						<label class="wp-ulike-setup__choice">
							<input type="checkbox" name="surfaces[]" value="comment" checked>
							<span class="wp-ulike-setup__choice-body">
								<span class="wp-ulike-setup__choice-title"><?php esc_html_e( 'Comments', 'wp-ulike' ); ?></span>
								<span class="wp-ulike-setup__choice-hint"><?php esc_html_e( 'Readers like a reply they agree with.', 'wp-ulike' ); ?></span>
							</span>
						</label>
						<label class="wp-ulike-setup__choice">
							<input type="checkbox" name="surfaces[]" value="home">
							<span class="wp-ulike-setup__choice-body">
								<span class="wp-ulike-setup__choice-title"><?php esc_html_e( 'Homepage', 'wp-ulike' ); ?></span>
								<span class="wp-ulike-setup__choice-hint"><?php esc_html_e( 'Latest-posts front page. Can look busy.', 'wp-ulike' ); ?></span>
							</span>
						</label>
						<label class="wp-ulike-setup__choice">
							<input type="checkbox" name="surfaces[]" value="page">
							<span class="wp-ulike-setup__choice-body">
								<span class="wp-ulike-setup__choice-title"><?php esc_html_e( 'Pages', 'wp-ulike' ); ?></span>
								<span class="wp-ulike-setup__choice-hint"><?php esc_html_e( 'About, Contact, and other pages.', 'wp-ulike' ); ?></span>
							</span>
						</label>
						<?php if ( $has_woocommerce ) : ?>
							<label class="wp-ulike-setup__choice">
								<input type="checkbox" name="surfaces[]" value="product" checked>
								<span class="wp-ulike-setup__choice-body">
									<span class="wp-ulike-setup__choice-title"><?php esc_html_e( 'Products', 'wp-ulike' ); ?></span>
									<span class="wp-ulike-setup__choice-hint"><?php esc_html_e( 'WooCommerce product pages.', 'wp-ulike' ); ?></span>
								</span>
							</label>
						<?php endif; ?>
						<?php if ( $has_buddypress ) : ?>
							<label class="wp-ulike-setup__choice">
								<input type="checkbox" name="surfaces[]" value="buddypress" checked>
								<span class="wp-ulike-setup__choice-body">
									<span class="wp-ulike-setup__choice-title"><?php esc_html_e( 'Activity', 'wp-ulike' ); ?></span>
									<span class="wp-ulike-setup__choice-hint"><?php esc_html_e( 'BuddyPress stream and activity comments.', 'wp-ulike' ); ?></span>
								</span>
							</label>
						<?php endif; ?>
						<?php if ( $has_bbpress ) : ?>
							<label class="wp-ulike-setup__choice">
								<input type="checkbox" name="surfaces[]" value="bbpress" checked>
								<span class="wp-ulike-setup__choice-body">
									<span class="wp-ulike-setup__choice-title"><?php esc_html_e( 'Forums', 'wp-ulike' ); ?></span>
									<span class="wp-ulike-setup__choice-hint"><?php esc_html_e( 'bbPress topics and replies.', 'wp-ulike' ); ?></span>
								</span>
							</label>
						<?php endif; ?>
					</div>
					<p class="wp-ulike-setup__error" data-setup-error hidden></p>
				</section>

				<section class="wp-ulike-setup__panel" data-setup-panel="2" hidden aria-labelledby="wp-ulike-setup-step-2-title">
					<h3 id="wp-ulike-setup-step-2-title" class="wp-ulike-setup__panel-title"><?php esc_html_e( 'Who can like?', 'wp-ulike' ); ?></h3>
					<p class="wp-ulike-setup__panel-desc"><?php esc_html_e( 'Pick members-only only if this is a private or membership site.', 'wp-ulike' ); ?></p>
					<div class="wp-ulike-setup__choices wp-ulike-setup__choices--single" role="radiogroup" aria-labelledby="wp-ulike-setup-step-2-title">
						<label class="wp-ulike-setup__choice">
							<input type="radio" name="who_can_vote" value="everyone" checked>
							<span class="wp-ulike-setup__choice-body">
								<span class="wp-ulike-setup__choice-title"><?php esc_html_e( 'Anyone visiting', 'wp-ulike' ); ?></span>
								<span class="wp-ulike-setup__choice-hint"><?php esc_html_e( 'Blogs, shops, and public sites. One click.', 'wp-ulike' ); ?></span>
							</span>
						</label>
						<label class="wp-ulike-setup__choice">
							<input type="radio" name="who_can_vote" value="logged_in">
							<span class="wp-ulike-setup__choice-body">
								<span class="wp-ulike-setup__choice-title"><?php esc_html_e( 'Members only', 'wp-ulike' ); ?></span>
								<span class="wp-ulike-setup__choice-hint"><?php echo esc_html( $guest_hint ); ?></span>
							</span>
						</label>
					</div>
				</section>

				<section class="wp-ulike-setup__panel" data-setup-panel="3" hidden aria-labelledby="wp-ulike-setup-step-3-title">
					<h3 id="wp-ulike-setup-step-3-title" class="wp-ulike-setup__panel-title"><?php esc_html_e( 'After they like', 'wp-ulike' ); ?></h3>
					<p class="wp-ulike-setup__panel-desc"><?php esc_html_e( 'Undo a mis-click, then keep the vote. Names are optional social proof.', 'wp-ulike' ); ?></p>
					<div class="wp-ulike-setup__block">
						<div class="wp-ulike-setup__choices wp-ulike-setup__choices--single" role="radiogroup" aria-label="<?php esc_attr_e( 'Can they take a like back?', 'wp-ulike' ); ?>">
							<label class="wp-ulike-setup__choice">
								<input type="radio" name="unlike_rule" value="once" checked>
								<span class="wp-ulike-setup__choice-body">
									<span class="wp-ulike-setup__choice-top">
										<span class="wp-ulike-setup__choice-title"><?php esc_html_e( 'Unlike once, then lock', 'wp-ulike' ); ?></span>
										<span class="wp-ulike-setup__badge"><?php esc_html_e( 'Recommended', 'wp-ulike' ); ?></span>
									</span>
									<span class="wp-ulike-setup__choice-hint"><?php esc_html_e( 'Undo a mis-click. Then the vote stays.', 'wp-ulike' ); ?></span>
								</span>
							</label>
							<label class="wp-ulike-setup__choice">
								<input type="radio" name="unlike_rule" value="allow">
								<span class="wp-ulike-setup__choice-body">
									<span class="wp-ulike-setup__choice-title"><?php esc_html_e( 'Unlike anytime', 'wp-ulike' ); ?></span>
									<span class="wp-ulike-setup__choice-hint"><?php esc_html_e( 'Click again whenever to remove the like.', 'wp-ulike' ); ?></span>
								</span>
							</label>
							<label class="wp-ulike-setup__choice">
								<input type="radio" name="unlike_rule" value="lock">
								<span class="wp-ulike-setup__choice-body">
									<span class="wp-ulike-setup__choice-title"><?php esc_html_e( 'Lock the first like', 'wp-ulike' ); ?></span>
									<span class="wp-ulike-setup__choice-hint"><?php esc_html_e( 'No undo. Best for contests.', 'wp-ulike' ); ?></span>
								</span>
							</label>
						</div>
					</div>
					<div class="wp-ulike-setup__block">
						<p class="wp-ulike-setup__block-title"><?php esc_html_e( 'Show who liked?', 'wp-ulike' ); ?></p>
						<div class="wp-ulike-setup__choices wp-ulike-setup__choices--single" role="radiogroup" aria-label="<?php esc_attr_e( 'Show who liked?', 'wp-ulike' ); ?>">
							<label class="wp-ulike-setup__choice">
								<input type="radio" name="show_likers" value="yes"<?php checked( $likers_default ); ?>>
								<span class="wp-ulike-setup__choice-body">
									<span class="wp-ulike-setup__choice-title"><?php esc_html_e( 'Yes, show names', 'wp-ulike' ); ?></span>
									<span class="wp-ulike-setup__choice-hint"><?php esc_html_e( 'Social proof. Best for communities and forums.', 'wp-ulike' ); ?></span>
								</span>
							</label>
							<label class="wp-ulike-setup__choice">
								<input type="radio" name="show_likers" value="no"<?php checked( ! $likers_default ); ?>>
								<span class="wp-ulike-setup__choice-body">
									<span class="wp-ulike-setup__choice-title"><?php esc_html_e( 'No, keep it clean', 'wp-ulike' ); ?></span>
									<span class="wp-ulike-setup__choice-hint"><?php esc_html_e( 'Just the button and count. Best for blogs and shops.', 'wp-ulike' ); ?></span>
								</span>
							</label>
						</div>
					</div>
				</section>
			</form>

			<p class="wp-ulike-setup__recap" data-setup-recap aria-live="polite"></p>

			<section class="wp-ulike-setup__success" data-setup-success hidden>
				<h3 class="wp-ulike-setup__panel-title"><?php esc_html_e( 'You are set', 'wp-ulike' ); ?></h3>
				<p class="wp-ulike-setup__panel-desc" data-setup-success-text></p>
			</section>

			<div class="wp-ulike-setup__footer">
				<button type="button" class="button-link wp-ulike-setup__skip" data-setup-dismiss><?php esc_html_e( 'Use these choices', 'wp-ulike' ); ?></button>
				<div class="wp-ulike-setup__nav">
					<button type="button" class="button" data-setup-back hidden><?php esc_html_e( 'Back', 'wp-ulike' ); ?></button>
					<button type="button" class="button button-primary" data-setup-next><?php esc_html_e( 'Continue', 'wp-ulike' ); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>
