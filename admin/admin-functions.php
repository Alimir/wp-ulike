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
 * Get new votes counter
 *
 * @return integer
 */
function wp_ulike_get_number_of_new_likes() {
    if( ! apply_filters( 'wp_ulike_display_admin_new_likes', true ) ){
        return 0;
    }

    // Get cache key
    $cache_key = sanitize_key( 'calculate_new_votes' );
    // Get new votes
    $calculate_new_votes = wp_ulike_get_meta_data( 1, 'statistics', $cache_key, true );

    if( empty( $calculate_new_votes ) ){
        if( $calculate_new_votes === '' ){
            wp_ulike_update_meta_data( 1, 'statistics', $cache_key, 0 );
        }

        return 0;
    }

    // Refresh likes
	if( isset( $_GET["page"] ) && stripos( sanitize_text_field( wp_unslash( $_GET["page"] ) ), "wp-ulike-statistics" ) !== false && is_super_admin() ) {
        wp_ulike_update_meta_data( 1, 'statistics', $cache_key, 0 );

        return 0;
    }

	return $calculate_new_votes;
}

/**
 * Get badge counter in html format
 *
 * @param integer $number
 * @return string
 */
function wp_ulike_badge_count_format( $number ){
	return ! empty( $number ) ? sprintf( ' <span class="update-plugins wp-ulike-notification-count-container count-%1$s"><span class="update-count wp-ulike-notification-count-value">%1$s</span></span>',
		number_format_i18n( $number )
	) : '';
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

    $parsed_args = wp_parse_args( $atts, $default_atts );
	$label = isset( $parsed_args['label'] ) ? $parsed_args['label'] : '';
	$color_name = isset( $parsed_args['color_name'] ) ? $parsed_args['color_name'] : 'default';
	$link = isset( $parsed_args['link'] ) ? $parsed_args['link'] : '';
	$target = isset( $parsed_args['target'] ) ? $parsed_args['target'] : '_self';
	$nofollow = isset( $parsed_args['nofollow'] ) ? $parsed_args['nofollow'] : false;
	$btn_attrs = isset( $parsed_args['btn_attrs'] ) ? $parsed_args['btn_attrs'] : '';
	$custom_styles = isset( $parsed_args['custom_styles'] ) ? $parsed_args['custom_styles'] : array();
	$extra_classes = isset( $parsed_args['extra_classes'] ) ? $parsed_args['extra_classes'] : '';

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

        $extra_styles = 'style="' . esc_attr( $extra_styles ) . '"';

    }

    if( ! empty( $extra_classes ) ) {
        $btn_css_classes[] = $extra_classes;
    }

    // get escaped class attributes
    $button_class_attr = wp_ulike_make_html_class_attribute( $btn_css_classes );

    $label = empty( $label ) ? esc_html__( "Button", 'wp-ulike' ) : $label;

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

/**
 * Check plugin admin pages
 *
 * @return bool
 */
function wp_ulike_is_plugin_screen(){
    $screen = get_current_screen();

	if( strpos( $screen->base, WP_ULIKE_SLUG ) === false ){
        if( defined( 'WP_ULIKE_PRO_DOMAIN' ) && in_array( $screen->base, array( 'post' ) ) ){
            return true;
        }
        return false;
    }

    return true;
}

/**
 * Create stylish admin notices
 *
 * @param array $args
 * @return void
 */
function wp_ulike_get_notice_render( $args = array() ){
    $defaults   = array(
        'id'             => NULL,
        'title'          => '',
        'skin'           => 'default',
        'image'          => '',
        'screen_filter'  => array(),
        'description'    => '',
        'initial_snooze' => '',          // snooze time in milliseconds
        'has_close'      => false,       // Whether it has close button or not
        'buttons'        => array()
    );
    $parsed_args = wp_parse_args( $args, $defaults );

    // Create notice instance
    $notice_instance = new wp_ulike_notices($parsed_args);
	$notice_instance->render();
}

/**
 * Stores css content in custom css file (#admin)
 *
 * @return boolean            Returns true if the file is created and updated successfully, false on failure
 */
function wp_ulike_save_custom_css(){
    $css_string = wp_ulike_get_custom_style();
    $css_string = wp_ulike_minify_css( $css_string );

    if ( ! empty( $css_string ) && wp_ulike_put_contents_dir( $css_string, 'custom.css' ) ) {
        update_option( 'wp_ulike_use_inline_custom_css' , 0 ); // disable inline css output
        return true;
    // if the directory is not writable, try inline css fallback
    } else {
        update_option( 'wp_ulike_use_inline_custom_css' , 1 ); // save css rules as option to print as inline css
        return false;
    }
}

/**
 * Minify CSS
 *
 * @param string $input
 * @return string
 */
function wp_ulike_minify_css( $input ) {
    if( trim( $input ) === "" ){
        return $input;
    }

    return preg_replace(
        array(
            // Remove comment(s)
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
            // Remove unused white-space(s)
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~]|\s(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
            // Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
            '#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
            // Replace `:0 0 0 0` with `:0`
            '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
            // Replace `background-position:0` with `background-position:0 0`
            '#(background-position):0(?=[;\}])#si',
            // Replace `0.6` with `.6`, but only when preceded by `:`, `,`, `-` or a white-space
            '#(?<=[\s:,\-])0+\.(\d+)#s',
            // Minify string value
            '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
            '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
            // Minify HEX color code
            '#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
            // Replace `(border|outline):none` with `(border|outline):0`
            '#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
            // Remove empty selector(s)
            '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s'
        ),
        array(
            '$1',
            '$1$2$3$4$5$6$7',
            '$1',
            ':0',
            '$1:0 0',
            '.$1',
            '$1$3',
            '$1$2$4$5',
            '$1$2$3',
            '$1:0',
            '$1$2'
        ),
    $input);
}

/**
 * Fix multiple select issue
 *
 * @param   array  $value
 *
 * @return  array
 */
function wp_ulike_sanitize_multiple_select( $value ) {
    $multiple_selects = array(
        'auto_display_filter',
        'auto_display_filter_post_types',
    );

    foreach ( $multiple_selects as $id ) {
        if ( ! isset( $value[$id] ) ) {
            $value[$id] = array();
        }
    }

    return $value;
}