<?php
/**
 * Admin Functions
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

/**
 * Return per_page option value
 *
 * @author       	Alimir
 * @since           2.1
 * @return			integer
 */
function wp_ulike_logs_return_per_page(){
	$user     = get_current_user_id();
	$screen   = get_current_screen();
	$option   = $screen->get_option( 'per_page', 'option' );
	$per_page = get_user_meta( $user, $option, true );

	return ( empty( $per_page ) || $per_page < 1 ) ? 30 : $per_page;
}

/**
 * Get paginated logs dataset
 *
 * @since 3.5
 * @param string $table
 * @param string $type
 * @return array
 */
function wp_ulike_get_paginated_logs( $table, $type ){
	global $wpdb;

	// Make new sql request
	$query   = sprintf( "
		SELECT COUNT(*)
		FROM %s",
		$wpdb->prefix . $table
	);

	$num_rows = $wpdb->get_var( $query );

	if( empty( $num_rows ) ) {
		return;
	}

	$per_page   = wp_ulike_logs_return_per_page();

	$pagination = new wp_ulike_pagination;
	$pagination->items( $num_rows );
	$pagination->limit( $per_page ); // Limit entries per page
	$pagination->target( 'admin.php?page=wp-ulike-' . $type . '-logs'  );
	$pagination->calculate(); // Calculates what to show
	$pagination->parameterName( 'page_number' );
	$pagination->adjacents(1); //No. of page away from the current page

	if( ! isset( $_GET['page_number'] ) ) {
		$pagination->page = 1;
	} else {
		$pagination->page = (int) $_GET['page_number'];
	}

	// Make new sql request
	$query  = sprintf( '
		SELECT *
		FROM %s
		ORDER BY id
		DESC
		LIMIT %s',
		$wpdb->prefix . $table,
		($pagination->page - 1) * $pagination->limit  . ", " . $pagination->limit
	);

	return array(
		'data_rows' => $wpdb->get_results( $query ),
		'paginate'  => $pagination,
		'num_rows'  => $num_rows
	);

}

/**
 * The counter of last likes by the admin last login time.
 *
 * @author       	Alimir
 * @since           2.4.2
 * @return			string
 */
function wp_ulike_get_number_of_new_likes() {
	global $wpdb;

	if( isset( $_GET["page"] ) && stripos( $_GET["page"], "wp-ulike-statistics" ) !== false && is_super_admin() ) {
		update_option( 'wpulike_lastvisit', current_time( 'mysql' ) );
	}

	$query = sprintf( '
		SELECT
		( SELECT COUNT(*) FROM `%1$sulike` WHERE ( date_time <= NOW() AND date_time >= "%2$s" ) ) +
		( SELECT COUNT(*) FROM `%1$sulike_activities` WHERE ( date_time <= NOW() AND date_time >= "%2$s" ) ) +
		( SELECT COUNT(*) FROM `%1$sulike_comments` WHERE ( date_time <= NOW() AND date_time >= "%2$s" ) ) +
		( SELECT COUNT(*) FROM `%1$sulike_forums` WHERE ( date_time <= NOW() AND date_time >= "%2$s" ) )
		',
		$wpdb->prefix,
		get_option( 'wpulike_lastvisit')
	);

	$result = $wpdb->get_var( $query );

	return empty( $result ) ? 0 : $result;
}


/**
 * Get badge counter in html format
 *
 * @param integer $number
 * @return string
 */
function wp_ulike_badge_count_format( $number ){
	return sprintf( ' <span class="update-plugins count-%1$s"><span class="update-count">%1$s</span></span>',
		number_format_i18n( $number )
	);
}

/**
 * Get plugin downloads info from wordpress.org
 *
 * @return void
 */
function wp_ulike_get_repository_downloads_info(){

	$key = sanitize_key( 'wp_ulike_repository_downloads_info' );

	if ( false === ( $info = wp_ulike_get_transient( $key ) ) ) {
		$request = wp_remote_get( 'https://api.wordpress.org/stats/plugin/1.0/downloads.php?slug=wp-ulike&limit=30' );
		if( is_wp_error( $request ) ) {
			return NULL;
		}
		// get body info
		$body = wp_remote_retrieve_body( $request );
		$data = json_decode( $body, true );
		$info = is_array( $data ) ? array(
			'labels' => array_keys( $data ),
			'data' => array_values( $data ),
		) : NULL;
		wp_ulike_set_transient( $key, $info, 3 * HOUR_IN_SECONDS );
	}

	return $info;
}

/**
 * Button generator for admin usage
 *
 * @param array $atts
 * @return string
 */
function wp_ulike_widget_button_callback( $atts = array() ){

    // Defining default attributes
    $default_atts = array(
        'label'         => '',
        'color_name'    => 'default',
        'link'          => '',
        'target'        => '_self',
        'nofollow'      => false,
        'btn_attrs'     => '', // data-attr1{val1};data-attr2{val2}
        'custom_styles' => array(),
        'extra_classes' => '', // custom css class names for this element
    );

    $result = $parsed_args = wp_parse_args( $atts, $default_atts );
	extract( $result );

    // --------------------------------------------
    $btn_css_classes = array( 'wp-ulike-btn' );
    $btn_css_classes[] = 'wp-ulike-btn-' . $color_name;   // appearance

    // add extra attributes to button element if defined
    $btn_other_attrs = '';

    if( $btn_attrs = trim( $btn_attrs, ';' ) ){
        preg_match_all('/([\-|\w]+)(?!{})([\w]+)/s', $btn_attrs, $btn_attr_matches );

        if( ! empty( $btn_attr_matches[0] ) && is_array( $btn_attr_matches[0] ) ){
            foreach( $btn_attr_matches[0] as $i => $attr_name_value ){
                if( 0 == $i % 2 ){
                    $btn_other_attrs .= sprintf(' %s', $attr_name_value);
                } else {
                    $btn_other_attrs .= sprintf('="%s"', esc_attr( trim( $attr_name_value ) ) );
                }
            }
            $btn_other_attrs = trim( $btn_other_attrs );
        }
    }

    $extra_styles  = '';

    if ( isset( $custom_styles ) && ! empty( $custom_styles )  ) {

        foreach( $custom_styles as $property => $value ) {
            if ( 'custom' === $property ) {
                $extra_styles .= $value;
            } else {
                $extra_styles  .=  $property . ':' . $value . ';';
            }
        }

        $extra_styles = 'style="' . $extra_styles . '"';

    }

    if( ! empty( $extra_classes ) ) {
        $btn_css_classes[] =  $extra_classes;
    }

    // get escaped class attributes
    $button_class_attr = wp_ulike_make_html_class_attribute( $btn_css_classes );

    $label = empty( $label ) ? __( "Button", WP_ULIKE_SLUG ) : $label;

    $btn_content = '<span class="wp-ulike-text">'. wp_ulike_do_cleanup_shortcode( $label ) .'</span>';
    $btn_tag     = empty( $link ) ? 'button' : 'a';
    $btn_rel     = wp_ulike_is_true ( $nofollow ) ? ' rel="nofollow"' : '';
    $btn_href    = empty( $link ) ? '' : ' href="'. esc_url( $link ) .'" target="'. esc_attr( $target ) .'" ' . $btn_rel;

    $output   = '';

    // widget custom output -----------------------

    $output .= "<$btn_tag $btn_href $btn_other_attrs $button_class_attr $extra_styles>";
    $output .= $btn_content;
    $output .= "</$btn_tag>";

    return $output;
}


/**
 * Creates and returns an HTML class attribute
 *
 * @param  array        $classes   List of current classes
 * @param  string|array $class     One or more classes to add to the class list.
 *
 * @return string                  HTML class attribute
 */
function wp_ulike_make_html_class_attribute( $classes = '', $class = '' ){

    if( ! $merged_classes = wp_ulike_merge_css_classes( $classes, $class ) ){
        return '';
    }

    return 'class="' . esc_attr( trim( join( ' ', array_unique( $merged_classes ) ) ) ) . '"';
}

/**
 * Merge new css classes in current list
 *
 * @param  array        $classes   List of current classes
 * @param  string|array $class     One or more classes to add to the class list.
 *
 * @return                         Array of classes
 */
function wp_ulike_merge_css_classes( $classes = array(), $class = '' ){

    if( empty( $classes ) && empty( $class ) )
        return array();

    if ( ! empty( $class ) ) {
        if ( !is_array( $class ) )
            $class = preg_split( '#\s+#', $class );

        $classes = array_merge( $class, $classes );
    }

    return $classes;
}

/**
 * remove all auto generated p tags from shortcode content
 *
 * @param string $content
 * @return string
 */
function wp_ulike_do_cleanup_shortcode( $content ) {

	/* Parse nested shortcodes and add formatting. */
	$content = trim( wpautop( do_shortcode( $content ) ) );

	/* Remove any instances of '<p>' '</p>'. */
	$content = wp_ulike_cleanup_content( $content );

	return $content;
}

/**
 * remove all p tags from string
 *
 * @param string $content
 * @return string
 */
function wp_ulike_cleanup_content( $content ) {
	/* Remove any instances of '<p>' '</p>'. */
	return str_replace( array('<p>','</p>'), array('','') , $content );
}

/**
 * Simple convertor for old option values
 * @param array $data
 * @return array
 */
function wp_ulike_convert_old_options_array( $data ){
	$output = array();
	foreach ($data as $key => $value) {
		if( wp_ulike_is_true( $value ) ){
			$output[] = $key;
		}
	}
	return $output;
}