<?php
/**
 * Custom Templates
 * // @echo HEADER
 */

 // If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

if( ! function_exists( 'wp_ulike_generate_templates_list' ) ){
	/**
	 * Generate templates list
	 *
	 * @author       	Alimir
	 * @since           2.8
	 * @return			Array
	 */
	function wp_ulike_generate_templates_list(){
		$default = array(
			'wpulike-default' => array(
				'name'            => __('Default', WP_ULIKE_SLUG),
				'callback'        => 'wp_ulike_set_default_template',
				'symbol'          => WP_ULIKE_ASSETS_URL . '/img/svg/default.svg',
				'is_text_support' => true
			),
			'wpulike-heart' => array(
				'name'            => __('Heart', WP_ULIKE_SLUG),
				'callback'        => 'wp_ulike_set_simple_heart_template',
				'symbol'          => WP_ULIKE_ASSETS_URL . '/img/svg/heart.svg',
				'is_text_support' => true
			),
			'wpulike-robeen' => array(
				'name'            => __('Twitter Heart', WP_ULIKE_SLUG),
				'callback'        => 'wp_ulike_set_robeen_template',
				'symbol'          => WP_ULIKE_ASSETS_URL . '/img/svg/twitter.svg',
				'is_text_support' => false
			),
			'wpulike-animated-heart' => array(
				'name'            => __('Animated Heart', WP_ULIKE_SLUG),
				'callback'        => 'wp_ulike_set_animated_heart_template',
				'symbol'          => WP_ULIKE_ASSETS_URL . '/img/svg/animated-heart.svg',
				'is_text_support' => false
			)
		);

		return apply_filters( 'wp_ulike_add_templates_list', $default );
	}
}

if( ! function_exists( 'wp_ulike_set_default_template' ) ){
	/**
	 * Create simple default template
	 *
	 * @author       	Alimir
	 * @since           2.8
	 * @return			Void
	 */
	function wp_ulike_set_default_template( array $wp_ulike_template ){
		//This function will turn output buffering on
		ob_start();
		do_action( 'wp_ulike_before_template' );
		// Extract input array
		extract( $wp_ulike_template );
	?>
		<div class="wpulike wpulike-default <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
			<div class="<?php echo $general_class; ?>">
				<button type="button"
					aria-label="<?php echo wp_ulike_get_option( 'like_button_aria_label', __( 'Like Button',WP_ULIKE_SLUG) ) ?>"
					data-ulike-id="<?php echo $ID; ?>"
					data-ulike-nonce="<?php echo wp_create_nonce( $type . $ID ); ?>"
					data-ulike-type="<?php echo $type; ?>"
					data-ulike-template="<?php echo $style; ?>"
					data-ulike-display-likers="<?php echo $display_likers; ?>"
					data-ulike-likers-style="<?php echo $likers_style; ?>"
					class="<?php echo $button_class; ?>">
					<?php
						echo $up_vote_inner_text;
						do_action( 'wp_ulike_inside_like_button', $wp_ulike_template );
						if($button_type == 'text'){
							echo '<span>' . $button_text . '</span>';
						}
					?>
				</button>
				<?php
					if( isset( $display_counters ) && $display_counters ){
						$status = wp_ulike_maybe_convert_status( $user_status, 'up' );
						echo sprintf( '<span class="count-box">%s</span>', wp_ulike_format_number( $total_likes, $status ) );
					}
					do_action( 'wp_ulike_after_up_vote_button', $wp_ulike_template );
				?>
			</div>
		<?php
			do_action( 'wp_ulike_inside_template', $wp_ulike_template );
		?>
		</div>
	<?php
		do_action( 'wp_ulike_after_template' );
		return ob_get_clean(); // data is now in here
	}
}

if( ! function_exists( 'wp_ulike_set_simple_heart_template' ) ){
	/**
	 * Create simple heart template
	 *
	 * @author       	Alimir
	 * @since           2.8
	 * @return			Void
	 */
	function wp_ulike_set_simple_heart_template( array $wp_ulike_template ){
		//This function will turn output buffering on
		ob_start();
		do_action( 'wp_ulike_before_template' );
		// Extract input array
		extract( $wp_ulike_template );
	?>
		<div class="wpulike wpulike-heart <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
			<div class="<?php echo $general_class; ?>">
				<button type="button"
					aria-label="<?php echo wp_ulike_get_option( 'like_button_aria_label', __( 'Like Button',WP_ULIKE_SLUG) ) ?>"
					data-ulike-id="<?php echo $ID; ?>"
					data-ulike-nonce="<?php echo wp_create_nonce( $type  . $ID ); ?>"
					data-ulike-type="<?php echo $type; ?>"
					data-ulike-template="<?php echo $style; ?>"
					data-ulike-display-likers="<?php echo $display_likers; ?>"
					data-ulike-likers-style="<?php echo $likers_style; ?>"
					class="<?php echo $button_class; ?>">
					<?php
						echo $up_vote_inner_text;
						do_action( 'wp_ulike_inside_like_button', $wp_ulike_template );
						if( $button_type == 'text' ){
							echo '<span>' . $button_text . '</span>';
						}
					?>
				</button>
				<?php
					if( isset( $display_counters ) && $display_counters ){
						$status = wp_ulike_maybe_convert_status( $user_status, 'up' );
						echo sprintf( '<span class="count-box">%s</span>', wp_ulike_format_number( $total_likes, $status ) );
					}
					do_action( 'wp_ulike_after_up_vote_button', $wp_ulike_template );
				?>
			</div>
		<?php
			do_action( 'wp_ulike_inside_template', $wp_ulike_template );
		?>
		</div>
	<?php
		do_action( 'wp_ulike_after_template' );
		return ob_get_clean(); // data is now in here
	}
}

if( ! function_exists( 'wp_ulike_set_robeen_template' ) ){
	/**
	 * Create Robeen (Animated Heart) template
	 *
	 * @author       	Alimir
	 * @since           2.8
	 * @return			Void
	 */
	function wp_ulike_set_robeen_template( array $wp_ulike_template ){
		//This function will turn output buffering on
		ob_start();
		do_action( 'wp_ulike_before_template' );
		// Extract input array
		extract( $wp_ulike_template );
	?>
		<div class="wpulike wpulike-robeen <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
			<div class="<?php echo $general_class; ?>">
				<button type="button"
					aria-label="<?php echo wp_ulike_get_option( 'like_button_aria_label', __( 'Like Button',WP_ULIKE_SLUG) ) ?>"
					data-ulike-id="<?php echo $ID; ?>"
					data-ulike-nonce="<?php echo wp_create_nonce( $type  . $ID ); ?>"
					data-ulike-type="<?php echo $type; ?>"
					data-ulike-template="<?php echo $style; ?>"
					data-ulike-display-likers="<?php echo $display_likers; ?>"
					data-ulike-likers-style="<?php echo $likers_style; ?>"
					class="<?php echo $button_class; ?>">
					<?php
						echo $up_vote_inner_text;
						do_action( 'wp_ulike_inside_like_button', $wp_ulike_template );
					?>
				</button>
				<?php
					if( isset( $display_counters ) && $display_counters ){
						$status = wp_ulike_maybe_convert_status( $user_status, 'up' );
						echo sprintf( '<span class="count-box">%s</span>', wp_ulike_format_number( $total_likes, $status ) );
					}
					do_action( 'wp_ulike_after_up_vote_button', $wp_ulike_template );
				?>
			</div>
		<?php
			do_action( 'wp_ulike_inside_template', $wp_ulike_template );
		?>
		</div>
	<?php
		do_action( 'wp_ulike_after_template' );
		return ob_get_clean(); // data is now in here
	}
}


if( ! function_exists( 'wp_ulike_set_animated_heart_template' ) ){
	/**
	 * Create Animated Heart template
	 *
	 * @author       	Alimir
	 * @since           3.6.2
	 * @return			Void
	 */
	function wp_ulike_set_animated_heart_template( array $wp_ulike_template ){
		//This function will turn output buffering on
		ob_start();
		do_action( 'wp_ulike_before_template' );
		// Extract input array
		extract( $wp_ulike_template );
	?>
		<div class="wpulike wpulike-animated-heart <?php echo $wrapper_class; ?>" <?php echo $attributes; ?>>
			<div class="<?php echo $general_class; ?>">
				<button type="button"
					aria-label="<?php echo wp_ulike_get_option( 'like_button_aria_label', __( 'Like Button',WP_ULIKE_SLUG) ) ?>"
					data-ulike-id="<?php echo $ID; ?>"
					data-ulike-nonce="<?php echo wp_create_nonce( $type  . $ID ); ?>"
					data-ulike-type="<?php echo $type; ?>"
					data-ulike-template="<?php echo $style; ?>"
					data-ulike-display-likers="<?php echo $display_likers; ?>"
					data-ulike-likers-style="<?php echo $likers_style; ?>"
					data-ulike-append="<?php echo htmlspecialchars( '<svg class="wpulike-svg-heart wpulike-svg-heart-pop one" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="wpulike-svg-heart wpulike-svg-heart-pop two" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="wpulike-svg-heart wpulike-svg-heart-pop three" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="wpulike-svg-heart wpulike-svg-heart-pop four" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="wpulike-svg-heart wpulike-svg-heart-pop five" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="wpulike-svg-heart wpulike-svg-heart-pop six" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="wpulike-svg-heart wpulike-svg-heart-pop seven" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="wpulike-svg-heart wpulike-svg-heart-pop eight" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg><svg class="wpulike-svg-heart wpulike-svg-heart-pop nine" viewBox="0 0 32 29.6"><path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/></svg>' ); ?>"
					class="<?php echo $button_class; ?>">
					<?php
						echo $up_vote_inner_text;
						do_action( 'wp_ulike_inside_like_button', $wp_ulike_template );
					?>
					<svg class="wpulike-svg-heart wpulike-svg-heart-icon" viewBox="0 -28 512.00002 512" xmlns="http://www.w3.org/2000/svg">
					<path
						d="m471.382812 44.578125c-26.503906-28.746094-62.871093-44.578125-102.410156-44.578125-29.554687 0-56.621094 9.34375-80.449218 27.769531-12.023438 9.300781-22.917969 20.679688-32.523438 33.960938-9.601562-13.277344-20.5-24.660157-32.527344-33.960938-23.824218-18.425781-50.890625-27.769531-80.445312-27.769531-39.539063 0-75.910156 15.832031-102.414063 44.578125-26.1875 28.410156-40.613281 67.222656-40.613281 109.292969 0 43.300781 16.136719 82.9375 50.78125 124.742187 30.992188 37.394531 75.535156 75.355469 127.117188 119.3125 17.613281 15.011719 37.578124 32.027344 58.308593 50.152344 5.476563 4.796875 12.503907 7.4375 19.792969 7.4375 7.285156 0 14.316406-2.640625 19.785156-7.429687 20.730469-18.128907 40.707032-35.152344 58.328125-50.171876 51.574219-43.949218 96.117188-81.90625 127.109375-119.304687 34.644532-41.800781 50.777344-81.4375 50.777344-124.742187 0-42.066407-14.425781-80.878907-40.617188-109.289063zm0 0" />
					</svg>
				</button>
				<?php
					if( isset( $display_counters ) && $display_counters ){
						$status = wp_ulike_maybe_convert_status( $user_status, 'up' );
						echo sprintf( '<span class="count-box">%s</span>', wp_ulike_format_number( $total_likes, $status ) );
					}
					do_action( 'wp_ulike_after_up_vote_button', $wp_ulike_template );
				?>
			</div>
		<?php
			do_action( 'wp_ulike_inside_template', $wp_ulike_template );
		?>
		</div>
	<?php
		do_action( 'wp_ulike_after_template' );
		return ob_get_clean(); // data is now in here
	}
}