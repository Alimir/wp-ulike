<?php
/**
 * Shortcodes
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

if ( ! function_exists( 'wp_ulike_shortcode_resolve_item_id' ) ) {
	/**
	 * Resolve current item ID for shortcodes when `id` is omitted.
	 *
	 * @param string $type post|comment|activity|topic
	 * @return int
	 */
	function wp_ulike_shortcode_resolve_item_id( $type ) {
		$type = sanitize_key( (string) $type );

		switch ( $type ) {
			case 'comment':
				return (int) get_comment_ID();

			case 'activity':
				if ( function_exists( 'bp_get_activity_comment_id' ) ) {
					$comment_id = bp_get_activity_comment_id();
					return (int) ( null !== $comment_id ? $comment_id : bp_get_activity_id() );
				}
				return 0;

			case 'topic':
				if ( function_exists( 'bbp_get_reply_id' ) ) {
					$reply_id = (int) bbp_get_reply_id();
					if ( $reply_id ) {
						return $reply_id;
					}
				}
				if ( function_exists( 'bbp_get_topic_id' ) ) {
					return (int) bbp_get_topic_id();
				}
				return (int) wp_ulike_get_the_id();

			default:
				return (int) wp_ulike_get_the_id();
		}
	}
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
                $attributes['slug'] = 'comment';
				$result = $content . wp_ulike_comments( 'put', $attributes );
				break;

			case 'activity':
                $attributes['slug'] = 'activity';
				$result = $content . wp_ulike_buddypress( 'put', $attributes );
				break;

			case 'topic':
                $attributes['slug'] = 'topic';
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

        // Validate type + status
        $allowed_types = array( 'post', 'comment', 'activity', 'topic' );
        if ( ! in_array( $attributes['type'], $allowed_types, true ) ) {
            $attributes['type'] = 'post';
        }

        $allowed_statuses = array( 'like', 'unlike', 'dislike', 'undislike' );
        if ( ! in_array( $attributes['status'], $allowed_statuses, true ) ) {
            $attributes['status'] = 'like';
        }

        if ( empty( $args['id'] ) ) {
            $attributes['id'] = wp_ulike_shortcode_resolve_item_id( $attributes['type'] );
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
            return esc_html__('Invalid type specified for [wp_ulike_likers_box] shortcode.', 'wp-ulike');
        }

        if ( empty( $args['id'] ) ) {
            $args['id'] = wp_ulike_shortcode_resolve_item_id( $args['type'] );
        }

        $get_settings = wp_ulike_get_post_settings_by_type( $args['type'] );

        // If method not exist, then return error message
        if( empty( $get_settings ) || empty( $args['id'] ) ) {
            return esc_html__( 'Error receiving input parameters', 'wp-ulike' );
        }

        if( ! empty( $args['template']  ) ){
            $args['template'] = wp_ulike_kses( $args['template'] );
        }

        $output = sprintf( '<div class="wp_ulike_manual_likers_wrapper wp_%s_likers_%d">%s</div>', esc_attr( $args['type'] ), esc_attr( $args['id'] ),
            wp_ulike_get_likers_template( $get_settings['table'], $get_settings['column'], $args['id'], $get_settings['setting'], $args ) );

        return apply_filters( 'wp_ulike_likers_box_shortcode', $output, $args['id'], $args['type'] );
    }
    add_shortcode( 'wp_ulike_likers_box', 'wp_ulike_likers_box_shortcode' );
}

if ( ! function_exists( 'wp_ulike_shortcode_parse_bool' ) ) {
	/**
	 * Parse shortcode boolean-ish values.
	 *
	 * @param mixed $value   Raw attribute.
	 * @param bool  $default Default when empty.
	 * @return bool
	 */
	function wp_ulike_shortcode_parse_bool( $value, $default = false ) {
		if ( null === $value || '' === $value ) {
			return (bool) $default;
		}

		if ( is_bool( $value ) ) {
			return $value;
		}

		$normalized = strtolower( trim( (string) $value ) );

		if ( in_array( $normalized, array( '0', 'false', 'no', 'off' ), true ) ) {
			return false;
		}

		if ( in_array( $normalized, array( '1', 'true', 'yes', 'on' ), true ) ) {
			return true;
		}

		return (bool) $default;
	}
}

if ( ! function_exists( 'wp_ulike_load_top_content_renderer' ) ) {
	/**
	 * Ensure the Top List renderer class is available.
	 *
	 * @return bool
	 */
	function wp_ulike_load_top_content_renderer() {
		if ( class_exists( 'WP_Ulike_Top_Content_Renderer' ) ) {
			return true;
		}

		$file = WP_ULIKE_INC_DIR . '/blocks/top-content/class-top-content-renderer.php';
		if ( ! is_readable( $file ) ) {
			return false;
		}

		require_once $file;

		return class_exists( 'WP_Ulike_Top_Content_Renderer' );
	}
}

if ( ! function_exists( 'wp_ulike_enqueue_top_content_assets' ) ) {
	/**
	 * Enqueue Top List front-end CSS (same stylesheet as the Gutenberg block).
	 *
	 * @return void
	 */
	function wp_ulike_enqueue_top_content_assets() {
		$handle = 'wp-ulike-top-content-style';
		$src    = WP_ULIKE_INC_URL . '/blocks/top-content/build/style-style.css';
		$path   = WP_ULIKE_INC_DIR . '/blocks/top-content/build/style-style.css';

		if ( ! wp_style_is( $handle, 'registered' ) ) {
			$ver = file_exists( $path ) ? (string) filemtime( $path ) : WP_ULIKE_VERSION;
			wp_register_style( $handle, $src, array(), $ver );
		}

		wp_enqueue_style( $handle );
	}
}

if ( ! function_exists( 'wp_ulike_shortcode_parse_id_list' ) ) {
	/**
	 * Parse a comma/space-separated list of positive integers.
	 *
	 * @param string $raw Raw attribute.
	 * @return int[]
	 */
	function wp_ulike_shortcode_parse_id_list( $raw ) {
		if ( '' === (string) $raw ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map( 'absint', preg_split( '/[\s,]+/', (string) $raw ) )
			)
		);
	}
}

if ( ! function_exists( 'wp_ulike_shortcode_resolve_term_ids' ) ) {
	/**
	 * Resolve category/tag/term values (IDs or slugs) to term IDs.
	 *
	 * @param string $raw      Comma-separated IDs or slugs.
	 * @param string $taxonomy Taxonomy slug.
	 * @return int[]
	 */
	function wp_ulike_shortcode_resolve_term_ids( $raw, $taxonomy ) {
		if ( '' === (string) $raw || '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$parts = preg_split( '/[\s,]+/', (string) $raw );
		$ids   = array();

		foreach ( $parts as $part ) {
			$part = trim( (string) $part );
			if ( '' === $part ) {
				continue;
			}

			if ( ctype_digit( $part ) ) {
				$term = get_term( (int) $part, $taxonomy );
			} else {
				$term = get_term_by( 'slug', sanitize_title( $part ), $taxonomy );
			}

			if ( $term && ! is_wp_error( $term ) ) {
				$ids[] = (int) $term->term_id;
			}
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}
}

if ( ! function_exists( 'wp_ulike_top_content_atts_to_attributes' ) ) {
	/**
	 * Map [wp_ulike_top] / template-tag args onto Top List renderer attributes.
	 *
	 * @param array $atts Raw attributes (snake_case).
	 * @return array
	 */
	function wp_ulike_top_content_atts_to_attributes( $atts ) {
		$atts = is_array( $atts ) ? $atts : array();

		$defaults = array(
			// Content
			'type'           => 'post',
			'limit'          => '5',
			'post_type'      => '',
			// Time
			'period'         => 'all',
			'days'           => '',
			'hours'          => '',
			'date_start'     => '',
			'date_end'       => '',
			// Filters
			'cat'            => '',
			'tag'            => '',
			'taxonomy'       => '',
			'terms'          => '',
			'exclude_cat'    => '',
			'exclude_tag'    => '',
			'exclude'        => '',
			'exclude_current'=> '0',
			'author'         => '',
			// Display
			'heading'        => '',
			'show_heading'   => '1',
			'show_count'     => '1',
			'show_thumbnail' => '1',
			'show_rank'      => '1',
			'title_length'   => '12',
			'thumbnail_size' => '40',
			'sort_by'        => 'like',
			'order'          => 'DESC',
			'profile_url'    => 'wp',
			'show_engaged'   => '0',
			'wrapper_class'  => '',
		);

		$args = shortcode_atts( $defaults, $atts, 'wp_ulike_top' );

		$content_type = sanitize_key( $args['type'] );
		$type_map     = array(
			'posts'      => 'post',
			'post'       => 'post',
			'comments'   => 'comment',
			'comment'    => 'comment',
			'user'       => 'users',
			'users'      => 'users',
			'activities' => 'activity',
			'activity'   => 'activity',
			'topics'     => 'topic',
			'topic'      => 'topic',
			'forum'      => 'topic',
		);
		$content_type = isset( $type_map[ $content_type ] ) ? $type_map[ $content_type ] : 'post';

		$period_mode    = 'preset';
		$period_preset  = 'all';
		$interval_value = 30;
		$interval_unit  = 'DAY';
		$date_start     = sanitize_text_field( $args['date_start'] );
		$date_end       = sanitize_text_field( $args['date_end'] );

		$range = sanitize_key( $args['period'] );
		$range_map = array(
			'all'         => 'all',
			'alltime'     => 'all',
			'today'       => 'today',
			'daily'       => 'today',
			'yesterday'   => 'yesterday',
			'week'        => 'week',
			'weekly'      => 'week',
			'thisweek'    => 'week',
			'last7days'   => 'week',
			'last_week'   => 'last_week',
			'lastweek'    => 'last_week',
			'month'       => 'month',
			'monthly'     => 'month',
			'thismonth'   => 'month',
			'last30days'  => 'month',
			'last_month'  => 'last_month',
			'lastmonth'   => 'last_month',
			'year'        => 'year',
			'yearly'      => 'year',
			'thisyear'    => 'year',
			'last_year'   => 'last_year',
			'lastyear'    => 'last_year',
		);

		if ( $date_start ) {
			$period_mode = 'range';
		} elseif ( '' !== $args['days'] ) {
			$period_mode    = 'interval';
			$interval_value = max( 1, absint( $args['days'] ) );
			$interval_unit  = 'DAY';
		} elseif ( '' !== $args['hours'] ) {
			$period_mode    = 'interval';
			$interval_value = max( 1, absint( $args['hours'] ) );
			$interval_unit  = 'HOUR';
		} elseif ( isset( $range_map[ $range ] ) ) {
			$period_preset = $range_map[ $range ];
		} elseif ( '' !== $range && preg_match( '/^\d+$/', $range ) ) {
			$period_mode    = 'interval';
			$interval_value = max( 1, (int) $range );
			$interval_unit  = 'DAY';
		}

		$post_types = array();
		if ( '' !== $args['post_type'] ) {
			$post_types = array_values(
				array_filter(
					array_map( 'sanitize_key', preg_split( '/[\s,]+/', (string) $args['post_type'] ) )
				)
			);
		}

		// Include filters: cat / tag / custom taxonomy+terms (IDs or slugs).
		$taxonomy  = '';
		$term_ids  = array();
		if ( '' !== $args['cat'] ) {
			$taxonomy = 'category';
			$term_ids = wp_ulike_shortcode_resolve_term_ids( $args['cat'], 'category' );
		} elseif ( '' !== $args['tag'] ) {
			$taxonomy = 'post_tag';
			$term_ids = wp_ulike_shortcode_resolve_term_ids( $args['tag'], 'post_tag' );
		} elseif ( '' !== $args['taxonomy'] && '' !== $args['terms'] ) {
			$taxonomy = sanitize_key( $args['taxonomy'] );
			$term_ids = wp_ulike_shortcode_resolve_term_ids( $args['terms'], $taxonomy );
		}

		// Exclude filters.
		$exclude_taxonomy = '';
		$exclude_terms    = array();
		if ( '' !== $args['exclude_cat'] ) {
			$exclude_taxonomy = 'category';
			$exclude_terms    = wp_ulike_shortcode_resolve_term_ids( $args['exclude_cat'], 'category' );
		} elseif ( '' !== $args['exclude_tag'] ) {
			$exclude_taxonomy = 'post_tag';
			$exclude_terms    = wp_ulike_shortcode_resolve_term_ids( $args['exclude_tag'], 'post_tag' );
		}

		$sort_by = array_values(
			array_filter(
				array_map( 'sanitize_key', preg_split( '/[\s,]+/', (string) $args['sort_by'] ) )
			)
		);
		if ( empty( $sort_by ) ) {
			$sort_by = array( 'like' );
		}

		$attributes = array(
			'contentType'          => $content_type,
			'sortBy'               => $sort_by,
			'sortOrder'            => strtoupper( sanitize_key( $args['order'] ) ),
			'periodMode'           => $period_mode,
			'period'               => $period_preset,
			'intervalValue'        => $interval_value,
			'intervalUnit'         => $interval_unit,
			'dateStart'            => $date_start,
			'dateEnd'              => $date_end,
			'postTypes'            => $post_types,
			'taxonomy'             => $taxonomy,
			'taxonomyTerms'        => $term_ids,
			'excludeTaxonomy'      => $exclude_taxonomy,
			'excludeTaxonomyTerms' => $exclude_terms,
			'excludePostIds'       => wp_ulike_shortcode_parse_id_list( $args['exclude'] ),
			'excludeCurrent'       => wp_ulike_shortcode_parse_bool( $args['exclude_current'], false ),
			'authorIds'            => wp_ulike_shortcode_parse_id_list( $args['author'] ),
			'limit'                => absint( $args['limit'] ),
			'showCount'            => wp_ulike_shortcode_parse_bool( $args['show_count'], true ),
			'showThumbnail'        => wp_ulike_shortcode_parse_bool( $args['show_thumbnail'], true ),
			'showRank'             => wp_ulike_shortcode_parse_bool( $args['show_rank'], true ),
			'showHeading'          => wp_ulike_shortcode_parse_bool( $args['show_heading'], true ),
			'showEngagedUsers'     => wp_ulike_shortcode_parse_bool( $args['show_engaged'], false ),
			'titleTrim'            => absint( $args['title_length'] ) ? absint( $args['title_length'] ) : 12,
			'thumbnailSize'        => absint( $args['thumbnail_size'] ) ? absint( $args['thumbnail_size'] ) : 40,
			'heading'              => sanitize_text_field( $args['heading'] ),
			'profileUrl'           => sanitize_key( $args['profile_url'] ),
			'_wrapper_class'       => sanitize_html_class( $args['wrapper_class'] ),
		);

		/**
		 * Filter mapped Top List attributes from shortcode / template tag.
		 *
		 * @param array $attributes Block-style attributes.
		 * @param array $atts       Original shortcode attributes.
		 */
		return apply_filters( 'wp_ulike_top_content_shortcode_attributes', $attributes, $atts );
	}
}

if ( ! function_exists( 'wp_ulike_get_top_content' ) ) {
	/**
	 * Template tag: most liked / popular content list (same engine as Top List block).
	 *
	 * Example:
	 * `echo wp_ulike_get_top_content( array( 'limit' => 10, 'period' => 'weekly', 'cat' => 'news' ) );`
	 *
	 * @param array $args Shortcode-style attributes.
	 * @return string
	 */
	function wp_ulike_get_top_content( $args = array() ) {
		if ( ! wp_ulike_load_top_content_renderer() ) {
			return '';
		}

		$attributes    = wp_ulike_top_content_atts_to_attributes( $args );
		$wrapper_class = isset( $attributes['_wrapper_class'] ) ? $attributes['_wrapper_class'] : '';
		unset( $attributes['_wrapper_class'] );

		if ( ! is_admin() || wp_doing_ajax() ) {
			wp_ulike_enqueue_top_content_assets();
		}

		return WP_Ulike_Top_Content_Renderer::render( $attributes, $wrapper_class );
	}
}

if ( ! function_exists( 'wp_ulike_the_top_content' ) ) {
	/**
	 * Echo popular content list.
	 *
	 * @param array $args Shortcode-style attributes.
	 * @return void
	 */
	function wp_ulike_the_top_content( $args = array() ) {
		echo wp_ulike_get_top_content( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'wp_ulike_top_shortcode' ) ) {
	/**
	 * Shortcode: [wp_ulike_top] — most-liked / popular list (same engine as Top List block).
	 *
	 * Examples:
	 * `[wp_ulike_top]`
	 * `[wp_ulike_top limit="10" period="weekly" post_type="post,page"]`
	 * `[wp_ulike_top cat="news,3" days="7" heading="Trending"]`
	 * `[wp_ulike_top tag="recipes" exclude_current="1"]`
	 * `[wp_ulike_top taxonomy="product_cat" terms="shoes" post_type="product"]`
	 * `[wp_ulike_top type="comment" period="month"]`
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	function wp_ulike_top_shortcode( $atts ) {
		return wp_ulike_get_top_content( is_array( $atts ) ? $atts : array() );
	}
	add_shortcode( 'wp_ulike_top', 'wp_ulike_top_shortcode' );
}

if ( ! function_exists( 'wp_ulike_maybe_enqueue_top_content_from_content' ) ) {
	/**
	 * Pre-enqueue Top List CSS when [wp_ulike_top] is present in post content.
	 *
	 * @return void
	 */
	function wp_ulike_maybe_enqueue_top_content_from_content() {
		if ( is_admin() || ! is_singular() ) {
			return;
		}

		$post = get_post();
		if ( ! $post || empty( $post->post_content ) ) {
			return;
		}

		if ( has_shortcode( $post->post_content, 'wp_ulike_top' ) ) {
			wp_ulike_enqueue_top_content_assets();
		}
	}
	add_action( 'wp_enqueue_scripts', 'wp_ulike_maybe_enqueue_top_content_from_content', 20 );
}