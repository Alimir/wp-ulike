<?php
/**
 * About page template
 * // @echo HEADER
 */

	// no direct access allowed
	if ( ! defined('ABSPATH') ) {
	    die();
	}
?>

<div class="wrap ulf-about">

	<div class="ulf-about-header">
		<div class="ulf-about-header-image">
			<img alt="Need Some Help?" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/group.svg">
		</div>
		<div class="ulf-about-header-title">
			<p><?php echo esc_html__( 'WP ULike', 'wp-ulike' ) . ' ' . WP_ULIKE_VERSION; ?></p>
		</div>
		<div class="ulf-about-header-text"><?php esc_html_e( 'A professional WordPress plugin that makes your website more attractive and user-friendly.', 'wp-ulike') ; ?></div>
		<nav class="ulf-about-header-navigation nav-tab-wrapper wp-clearfix">
			<a class="nav-tab nav-tab-active" href="admin.php?page=wp-ulike-about"><?php esc_html_e('About','wp-ulike'); ?></a>
			<a target="_blank" class="nav-tab" href="admin.php?page=wp-ulike-settings#tab=configuration"><?php esc_html_e('Configuration','wp-ulike'); ?></a>
			<a target="_blank" class="nav-tab" href="<?php echo esc_url( self_admin_url( 'customize.php' ) ); ?>"><?php esc_html_e('Customize','wp-ulike'); ?></a>
			<a target="_blank" class="nav-tab" href="https://wpulike.com/blog/?utm_source=about-page&utm_campaign=blog-nav&utm_medium=wp-dash"><?php esc_html_e('Our Blog','wp-ulike'); ?></a>
			<a target="_blank" class="nav-tab go-pro" href="https://wpulike.com/pricing/?utm_source=about-page&utm_campaign=gopro-nav&utm_medium=wp-dash"><?php esc_html_e('Go Pro','wp-ulike'); ?><img alt="Go Pro" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/rocket-pro.gif"></a>
		</nav>
	</div>

	<div class="ulf-about-section is-feature has-subtle-background-color">
		<div class="column">
			<h1 class="is-smaller-heading"><?php esc_html_e('About','wp-ulike') . ' ' . esc_html__( 'WP ULike', 'wp-ulike' ); ?></h1>
			<p><?php esc_html_e( 'Looking to increase user engagement on your WordPress site? WP ULike plugin lets you easily add voting buttons to your content. With customizable settings and detailed analytics, you can track user engagement, optimize your content, and build a loyal following.', 'wp-ulike') ; ?></p>
		</div>
	</div>

	<div class="ulf-about-section ulf-box has-3-columns">
		<div class="column">
			<div class="column-header">
				<img alt="Need Some Help?" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/col-support.svg">
				<h2 class="is-smaller-heading"><?php esc_html_e('Need Some Help?','wp-ulike'); ?></h2>
				<p><?php esc_html_e('We would love to be of any assistance.','wp-ulike'); ?></p>
			</div>
			<div class="column-footer">
				<?php
					echo wp_ulike_widget_button_callback( array(
						'label'         => esc_html__( 'Send Ticket', 'wp-ulike' ),
						'color_name'    => 'default',
						'extra_classes' => 'green',
						'link'          => 'https://wpulike.com/support/?utm_source=about-page&utm_campaign=support-column&utm_medium=wp-dash',
						'target'        => '_blank'
					) );
				?>
			</div>
		</div>
		<div class="column">
			<div class="column-header">
				<img alt="Documentation" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/col-doc.svg">
				<h2 class="is-smaller-heading"><?php esc_html_e('Documentation','wp-ulike'); ?></h2>
				<p><?php esc_html_e('Learn about any aspect of WP ULike Plugin.','wp-ulike'); ?></p>
			</div>
			<div class="column-footer">
				<?php
					echo wp_ulike_widget_button_callback( array(
						'label'         => esc_html__( 'Start Reading', 'wp-ulike' ),
						'color_name'    => 'default',
						'extra_classes' => 'yellow',
						'link'          => 'https://docs.wpulike.com/?utm_source=about-page&utm_campaign=document-column&utm_medium=wp-dash',
						'target'        => '_blank'
					) );
				?>
			</div>
		</div>
		<div class="column">
			<div class="column-header">
				<img alt="Submit Review" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/col-review.svg">
				<h2 class="is-smaller-heading"><?php esc_html_e('Rate Us','wp-ulike'); ?></h2>
				<p><?php esc_html_e('By spreading the love, we can push WP ULike forward.','wp-ulike'); ?></p>
			</div>
			<div class="column-footer">
			<?php
				echo wp_ulike_widget_button_callback( array(
					'label'         => esc_html__( 'Submit Review', 'wp-ulike' ),
					'color_name'    => 'default',
					'extra_classes' => 'purple',
					'link'          => 'https://wordpress.org/support/plugin/wp-ulike/reviews/?filter=5',
					'target'        => '_blank'
				) );
			?>
			</div>
		</div>
	</div>


</div>