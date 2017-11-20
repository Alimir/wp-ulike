<?php

	if ( ! defined( 'ABSPATH' ) ) exit; // No direct access allowed

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
	?>
		<div id="wp-ulike-<?php echo $wp_ulike_template['slug'] . '-' . $wp_ulike_template['ID']; ?>" class="wpulike wpulike-default" <?php echo $wp_ulike_template['attributes']; ?>>
			<div class="<?php echo $wp_ulike_template['general_class']; ?>">
				<a data-ulike-id="<?php echo $wp_ulike_template['ID']; ?>" data-ulike-nonce="<?php echo wp_create_nonce( $wp_ulike_template['type'] . $wp_ulike_template['ID'] ); ?>" data-ulike-type="<?php echo $wp_ulike_template['type']; ?>"
				data-ulike-status="<?php echo $wp_ulike_template['status']; ?>" class="<?php echo $wp_ulike_template['button_class']; ?>">
					<?php
						if($wp_ulike_template['button_type'] == 'text'){
							echo '<span>' . $wp_ulike_template['button_text'] . '</span>';
						}
					?>
				</a>
				<?php echo $wp_ulike_template['counter']; ?>
			</div>
			<?php echo $wp_ulike_template['microdata']; ?>
		</div>
	<?php
		do_action( 'wp_ulike_after_template' );
		return ob_get_clean(); // data is now in here
	}

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
	?>
		<div id="wp-ulike-<?php echo $wp_ulike_template['slug'] . '-' . $wp_ulike_template['ID']; ?>" class="wpulike wpulike-heart" <?php echo $wp_ulike_template['attributes']; ?>>
			<div class="<?php echo $wp_ulike_template['general_class']; ?>">
				<a data-ulike-id="<?php echo $wp_ulike_template['ID']; ?>" data-ulike-nonce="<?php echo wp_create_nonce( $wp_ulike_template['type']  . $wp_ulike_template['ID'] ); ?>" data-ulike-type="<?php echo $wp_ulike_template['type']; ?>"
				data-ulike-status="<?php echo $wp_ulike_template['status']; ?>" class="<?php echo $wp_ulike_template['button_class']; ?>">
					<?php
						if($wp_ulike_template['button_type'] == 'text'){
							echo '<span>' . $wp_ulike_template['button_text'] . '</span>';
						}
					?>
				</a>
				<?php echo $wp_ulike_template['counter']; ?>
			</div>
			<?php echo $wp_ulike_template['microdata']; ?>
		</div>
	<?php
		do_action( 'wp_ulike_after_template' );
		return ob_get_clean(); // data is now in here
	}

	/**
	 * Create Robeen (Animated Heart) template
	 *
	 * @author       	Alimir	 	
	 * @since           2.8
	 * @return			Void
	 */
	function wp_ulike_set_robeen_template( array $wp_ulike_template ){
		$checked = '';
		if( $wp_ulike_template['status'] == 2 ){
			$checked = 'checked="checked"';
		}
		//This function will turn output buffering on
		ob_start();
		do_action( 'wp_ulike_before_template' );	
	?>
		<div id="wp-ulike-<?php echo $wp_ulike_template['slug'] . '-' . $wp_ulike_template['ID']; ?>" class="wpulike wpulike-robeen" <?php echo $wp_ulike_template['attributes']; ?>>
			<div class="<?php echo $wp_ulike_template['general_class']; ?>">
					<label>
					<input type="checkbox" data-ulike-id="<?php echo $wp_ulike_template['ID']; ?>" data-ulike-nonce="<?php echo wp_create_nonce( $wp_ulike_template['type'] . $wp_ulike_template['ID'] ); ?>" data-ulike-type="<?php echo $wp_ulike_template['type']; ?>"
				data-ulike-status="<?php echo $wp_ulike_template['status']; ?>" class="<?php echo $wp_ulike_template['button_class']; ?>" <?php echo  $checked; ?> />
					<svg class="heart-svg" viewBox="467 392 58 57" xmlns="http://www.w3.org/2000/svg"><g class="Group" fill="none" fill-rule="evenodd" transform="translate(467 392)"><path d="M29.144 20.773c-.063-.13-4.227-8.67-11.44-2.59C7.63 28.795 28.94 43.256 29.143 43.394c.204-.138 21.513-14.6 11.44-25.213-7.214-6.08-11.377 2.46-11.44 2.59z" class="heart" fill="#AAB8C2"/><circle class="main-circ" fill="#E2264D" opacity="0" cx="29.5" cy="29.5" r="1.5"/><g class="grp7" opacity="0" transform="translate(7 6)"><circle class="oval1" fill="#9CD8C3" cx="2" cy="6" r="2"/><circle class="oval2" fill="#8CE8C3" cx="5" cy="2" r="2"/></g><g class="grp6" opacity="0" transform="translate(0 28)"><circle class="oval1" fill="#CC8EF5" cx="2" cy="7" r="2"/><circle class="oval2" fill="#91D2FA" cx="3" cy="2" r="2"/></g><g class="grp3" opacity="0" transform="translate(52 28)"><circle class="oval2" fill="#9CD8C3" cx="2" cy="7" r="2"/><circle class="oval1" fill="#8CE8C3" cx="4" cy="2" r="2"/></g><g class="grp2" opacity="0" transform="translate(44 6)" fill="#CC8EF5"><circle class="oval2" transform="matrix(-1 0 0 1 10 0)" cx="5" cy="6" r="2"/><circle class="oval1" transform="matrix(-1 0 0 1 4 0)" cx="2" cy="2" r="2"/></g><g class="grp5" opacity="0" transform="translate(14 50)" fill="#91D2FA"><circle class="oval1" transform="matrix(-1 0 0 1 12 0)" cx="6" cy="5" r="2"/><circle class="oval2" transform="matrix(-1 0 0 1 4 0)" cx="2" cy="2" r="2"/></g><g class="grp4" opacity="0" transform="translate(35 50)" fill="#F48EA7"><circle class="oval1" transform="matrix(-1 0 0 1 12 0)" cx="6" cy="5" r="2"/><circle class="oval2" transform="matrix(-1 0 0 1 4 0)" cx="2" cy="2" r="2"/></g><g class="grp1" opacity="0" transform="translate(24)" fill="#9FC7FA"><circle class="oval1" cx="2.5" cy="3" r="2"/><circle class="oval2" cx="7.5" cy="2" r="2"/></g></g></svg>
					<?php echo $wp_ulike_template['counter']; ?>
					</label>
			</div>
			<?php echo $wp_ulike_template['microdata']; ?>
		</div>
	<?php
		do_action( 'wp_ulike_after_template' );
		return ob_get_clean(); // data is now in here
	}