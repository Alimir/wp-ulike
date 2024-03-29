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
			<p><?php echo WP_ULIKE_NAME . ' ' . WP_ULIKE_VERSION; ?></p>
		</div>
		<div class="ulf-about-header-text"><?php esc_html_e( 'A professional WordPress plugin that makes your website more attractive and user-friendly.', WP_ULIKE_SLUG) ; ?></div>
		<nav class="ulf-about-header-navigation nav-tab-wrapper wp-clearfix">
			<a class="nav-tab nav-tab-active" href="admin.php?page=wp-ulike-about"><?php esc_html_e('About',WP_ULIKE_SLUG); ?></a>
			<a target="_blank" class="nav-tab" href="admin.php?page=wp-ulike-settings#tab=configuration"><?php esc_html_e('Configuration',WP_ULIKE_SLUG); ?></a>
			<a target="_blank" class="nav-tab" href="<?php echo esc_url( self_admin_url( 'customize.php' ) ); ?>"><?php esc_html_e('Customize',WP_ULIKE_SLUG); ?></a>
			<a target="_blank" class="nav-tab" href="https://wpulike.com/blog/?utm_source=about-page&utm_campaign=blog-nav&utm_medium=wp-dash"><?php esc_html_e('Our Blog',WP_ULIKE_SLUG); ?></a>
			<a target="_blank" class="nav-tab go-pro" href="https://wpulike.com/pricing/?utm_source=about-page&utm_campaign=gopro-nav&utm_medium=wp-dash"><?php esc_html_e('Go Pro',WP_ULIKE_SLUG); ?><img alt="Go Pro" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/rocket-pro.gif"></a>
		</nav>
	</div>

	<div class="ulf-about-section is-feature has-subtle-background-color">
		<div class="column">
			<h1 class="is-smaller-heading"><?php esc_html_e('About',WP_ULIKE_SLUG) . ' ' . WP_ULIKE_NAME; ?></h1>
			<p><?php esc_html_e( 'Looking to increase user engagement on your WordPress site? WP ULike plugin lets you easily add voting buttons to your content. With customizable settings and detailed analytics, you can track user engagement, optimize your content, and build a loyal following.', WP_ULIKE_SLUG) ; ?></p>
		</div>
	</div>

	<div class="ulf-about-section ulf-box has-3-columns">
		<div class="column">
			<div class="column-header">
				<img alt="Need Some Help?" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/col-support.svg">
				<h2 class="is-smaller-heading"><?php esc_html_e('Need Some Help?',WP_ULIKE_SLUG); ?></h2>
				<p><?php esc_html_e('We would love to be of any assistance.',WP_ULIKE_SLUG); ?></p>
			</div>
			<div class="column-footer">
				<?php
					echo wp_ulike_widget_button_callback( array(
						'label'         => esc_html__( 'Send Ticket', WP_ULIKE_SLUG ),
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
				<h2 class="is-smaller-heading"><?php esc_html_e('Documentation',WP_ULIKE_SLUG); ?></h2>
				<p><?php esc_html_e('Learn about any aspect of WP ULike Plugin.',WP_ULIKE_SLUG); ?></p>
			</div>
			<div class="column-footer">
				<?php
					echo wp_ulike_widget_button_callback( array(
						'label'         => esc_html__( 'Start Reading', WP_ULIKE_SLUG ),
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
				<h2 class="is-smaller-heading"><?php esc_html_e('Rate Us',WP_ULIKE_SLUG); ?></h2>
				<p><?php esc_html_e('By spreading the love, we can push WP ULike forward.',WP_ULIKE_SLUG); ?></p>
			</div>
			<div class="column-footer">
			<?php
				echo wp_ulike_widget_button_callback( array(
					'label'         => esc_html__( 'Submit Review', WP_ULIKE_SLUG ),
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