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

<div class="wrap about-wrap wp-ulike-go-pro-page full-width-layout">
	<div class="has-1-columns">
		<div class="column">
			<h1><?php echo _e( 'WP ULike Premium', WP_ULIKE_SLUG ); ?></h1>
			<div class="is-feature">
			<?php
				echo sprintf('<p>%s</p>', __('WP ULike Pro plugin is our ultimate solution to cast voting to any type of content you may have in your website. With outstanding and eye-catching widgets, you can have Like and Dislike Button on all of your contents would it be a Post, Comment, Activities, Forum Topics, WooCommerce products, you name it. Now you can really feel your users Love ❤ for each part of your contents.',WP_ULIKE_SLUG) );

				echo wp_ulike_widget_button_callback( array(
					'label'         => __( 'Buy WP ULike Premium', WP_ULIKE_SLUG ),
					'color_name'    => 'default',
					'link'          => WP_ULIKE_PLUGIN_URI . 'pricing/?utm_source=go-pro-page&utm_campaign=gopro&utm_medium=wp-dash',
					'target'        => '_blank'
				) );
				echo wp_ulike_widget_button_callback( array(
					'label'         => __( 'More information', WP_ULIKE_SLUG ),
					'color_name'    => 'info',
					'link'          => WP_ULIKE_PLUGIN_URI . '?utm_source=go-pro-page&utm_campaign=plugin-uri&utm_medium=wp-dash',
					'target'        => '_blank'
				) );
			?>
			</div>
		</div>
	</div>
	<div class="has-3-columns">
		<h2 class="is-section-header">Our Features</h2>

		<div class="column">
			<h3><?php echo _e( 'Extensions', WP_ULIKE_SLUG ); ?></h3>
			<?php
				echo sprintf( '<p>%s</p>', __('WP ULike support posts, comments, BuddyPress activities & bbPress Topics.',WP_ULIKE_SLUG) );
			?>
		</div>
		<div class="column">
			<h3><?php echo _e( 'Languages', WP_ULIKE_SLUG ); ?></h3>
			<?php
				echo sprintf( '<p>%s</p>', __('WP ULike is already translated into +20 languages, with more always in progress.',WP_ULIKE_SLUG) );
			?>
		</div>
		<div class="column">
			<h3><?php echo _e( 'Clean & Simple', WP_ULIKE_SLUG ); ?></h3>
			<?php
				echo sprintf( '<p>%s</p>', __('We’ve used the simplest and prettiest styles for designing like buttons.',WP_ULIKE_SLUG) );
			?>
		</div>
		<div class="column">
			<h3><?php echo _e( 'Easy Develop', WP_ULIKE_SLUG ); ?></h3>
			<?php
				echo sprintf( '<p>%s</p>', __('Using various hooks and functions, you can easily customize this plugin.',WP_ULIKE_SLUG) );
			?>
		</div>
		<div class="column">
			<h3><?php echo _e( 'Custom Templates', WP_ULIKE_SLUG ); ?></h3>
			<?php
				echo sprintf( '<p>%s</p>', __('You can choose from multiple gorgeous and professional designed templates.',WP_ULIKE_SLUG) );
			?>
		</div>
		<div class="column">
			<h3><?php echo _e( 'Statistics', WP_ULIKE_SLUG ); ?></h3>
			<?php
				echo sprintf( '<p>%s</p>', __('extract detailed reports and beautiful, useful and simple charts in an instant.',WP_ULIKE_SLUG) );
			?>
		</div>
	</div>
	<div class="has-2-columns">
		<div class="column is-edge-to-edge has-accent-background-color">
			<img src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/elementor-support.png" alt="Elementor Support" />
		</div>
		<div class="column is-vertically-aligned-center">
			<h2><?php echo _e( 'Customize Every Part of Your YouLike Plugin', WP_ULIKE_SLUG ); ?></h2>
			<?php
				echo sprintf('<p>%s</p><p>%s</p>', __('Elementor is the #1 WordPress page builder. In the new version of the WP ULike PRO plugin we fully support this page builder and have a variety of widgets & controllers that make life much easier for you.',WP_ULIKE_SLUG), __('Just drag your desired widget and drop it in your Elementor sections, customize as you go and enjoy your like and dislike buttons on your contents.',WP_ULIKE_SLUG) );
			?>
		</div>
	</div>
</div>