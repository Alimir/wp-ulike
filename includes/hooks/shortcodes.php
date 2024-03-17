<?php
/**
 * Shortcodes
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

if( ! function_exists( 'wp_ulike_shortcode' ) ){
	/**
	 * Create shortcode: [wp_ulike]
	 *
	 * @param array $atts
	 * @param string $content
	 * @return void
	 */
	function wp_ulike_shortcode( $atts, $content = null ){
		// Default Args
		$default_args = array(
			"for"           => 'post',    // shortcode Type (post, comment, activity, topic)
			"id"            => '',        // Item ID
			"slug"          => 'post',    // Slug Name
			"style"         => '',        // Get Default Theme
			"button_type"   => '',        // Set Button Type ('image' || 'text')
			"wrapper_class" => ''         // Extra Wrapper class
		);

		// Sanitize and filter the attributes
		$args = shortcode_atts( array_map('esc_attr', $default_args), $atts );

		// Prepare the attributes for filtering
		$attributes = array(
			'for'           => $args['for'],
			'id'            => $args['id'],
			'slug'          => $args['slug'],
			'style'         => $args['style'],
			'button_type'   => $args['button_type'],
			'wrapper_class' => $args['wrapper_class']
		);

		if( empty( $attributes['id'] ) ){
			unset( $attributes['id'] );
		}
		if( empty( $attributes['style'] ) ){
			unset( $attributes['style'] );
		}
		if( empty( $attributes['button_type'] ) ){
			unset( $attributes['button_type'] );
		}
		if( empty( $attributes['wrapper_class'] ) ){
			unset( $attributes['wrapper_class'] );
		}

		// Generate the shortcode content based on the 'for' attribute
		switch ( $args['for'] ) {
			case 'comment':
				$result = $content . wp_ulike_comments( 'put', $attributes );
				break;

			case 'activity':
				$result = $content . wp_ulike_buddypress( 'put', $attributes );
				break;

			case 'topic':
				$result = $content . wp_ulike_bbpress( 'put', $attributes );
				break;

			default:
				$result = $content . wp_ulike( 'put', $attributes );
		}

		return $result;
	}
	add_shortcode( 'wp_ulike', 'wp_ulike_shortcode' );
}

if( ! function_exists( 'wp_ulike_counter_shortcode' ) ){
    /**
     * Create shortcode: [wp_ulike_counter]
     *
     * @param   array   $atts
     * @param   string  $content
     *
     * @return  string
     */
    function wp_ulike_counter_shortcode( $atts, $content = null ){
        // Default Args
        $default_args = array(
            "id"         => '',
            "type"       => 'post',
            "status"     => 'like',
            "date_range" => '',
            "past_time"  => ''
        );

        // Sanitize and filter the attributes
        $args = shortcode_atts( array_map('esc_sql', $default_args), $atts );

        // Prepare the attributes for filtering
        $attributes = array(
            'id'         => $args['id'],
            'type'       => $args['type'],
            'status'     => $args['status'],
            'date_range' => $args['date_range'],
            'past_time'  => $args['past_time']
        );

        // Validate the "status" attribute
        $allowed_statuses = array('like', 'unlike', 'dislike', 'undislike');
        if (!in_array($attributes['status'], $allowed_statuses)) {
            $attributes['status'] = 'like'; // Default to 'like' if the status is not one of the allowed values
        }

        if( empty( $args['id'] ) ){
            switch ( $args['type'] ) {
                case 'comment':
                    $attributes['id'] = get_comment_ID();
                    break;

                case 'activity':
                    if( function_exists( 'bp_get_activity_comment_id' ) ){
                        $attributes['id'] = bp_get_activity_comment_id() !== NULL ? bp_get_activity_comment_id() : bp_get_activity_id();
                    }
                    break;

                default:
                    $attributes['id'] = wp_ulike_get_the_id();
                    break;
            }
        }

        if( ! empty( $args['past_time'] ) ){
            $attributes['date_range'] = array(
                'interval_value' => $args['past_time'],
                'interval_unit'  => 'HOUR'
            );
        }

        $is_distinct = wp_ulike_setting_repo::isDistinct( $attributes['type'] );

        return wp_ulike_get_counter_value( $attributes['id'], $attributes['type'], $attributes['status'], $is_distinct, $attributes['date_range'] );
    }
    add_shortcode( 'wp_ulike_counter', 'wp_ulike_counter_shortcode' );
}

if( ! function_exists( 'wp_ulike_likers_box_shortcode' ) ){
    /**
     * Create shortcode: [wp_ulike_likers_box]
     *
     * @param array $atts
     * @param string $content
     * @return string
     */
    function wp_ulike_likers_box_shortcode( $atts, $content = null ){
        // Default Args
        $default_args = array(
            "id"          => '',
            "type"        => 'post',
            "counter"     => 10,
            "template"    => '',
            "style"       => '',
            "avatar_size" => 64
        );

        // Sanitize and filter the attributes
        $args = shortcode_atts( array_map('esc_sql', $default_args), $atts );

        // Validate the "type" attribute
        $allowed_types = array('post', 'comment', 'activity','topic');
        if (!in_array($args['type'], $allowed_types)) {
            return esc_html__('Invalid type specified for [wp_ulike_likers_box] shortcode.', WP_ULIKE_SLUG);
        }

        if( empty( $args['id'] ) ){
            switch ( $args['type'] ) {
                case 'comment':
                    $args['id'] = get_comment_ID();
                    break;

                case 'activity':
                    if( function_exists( 'bp_get_activity_comment_id' ) ){
                        $args['id'] = bp_get_activity_comment_id() !== NULL ? bp_get_activity_comment_id() : bp_get_activity_id();
                    }
                    break;

                default:
                    $args['id'] = wp_ulike_get_the_id();
                    break;
            }
        }

        $get_settings = wp_ulike_get_post_settings_by_type( $args['type'] );

        // If method not exist, then return error message
        if( empty( $get_settings ) || empty( $args['id'] ) ) {
            return esc_html__( 'Error receiving input parameters', WP_ULIKE_SLUG );
        }

        if( ! empty( $args['template']  ) ){
            $args['template'] = html_entity_decode( $args['template']  );
        }

        $output = sprintf( '<div class="wp_ulike_manual_likers_wrapper wp_%s_likers_%d">%s</div>', esc_attr( $args['type'] ), esc_attr( $args['id'] ),
            wp_ulike_get_likers_template( $get_settings['table'], $get_settings['column'], $args['id'], $get_settings['setting'], $args ) );

        return apply_filters( 'wp_ulike_likers_box_shortcode', $output, $args['id'], $args['type'] );
    }
    add_shortcode( 'wp_ulike_likers_box', 'wp_ulike_likers_box_shortcode' );
}