<?php
/**
 * Back-end AJAX Functionalities
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

/*******************************************************
  Start AJAX From Here
*******************************************************/

/**
 * AJAX handler to store the state of dismissible notices.
 *
 * @return			Void
 */
function wp_ulike_ajax_notice_handler() {
    // Store it in the options table
	if ( ! isset( $_POST['id'] ) ||  ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), '_notice_nonce' ) ) {
		wp_send_json_error(  esc_html__( 'Token Error.', 'wp-ulike' ) );
	} else {
		wp_ulike_set_transient( 'wp-ulike-notice-' . sanitize_text_field( $_POST['id' ] ), 1, absint( $_POST['expiration'] ) );
		wp_send_json_success( esc_html__( 'It\'s OK.', 'wp-ulike' ) );
	}
}
add_action( 'wp_ajax_wp_ulike_dismissed_notice', 'wp_ulike_ajax_notice_handler' );


/**
 * Dashboard api
 *
 * @return void
 */
function wp_ulike_stats_api(){
	// @if DEV
	/*
	// @endif
	if( ! current_user_can( wp_ulike_get_user_access_capability('stats') ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

    $stats = wp_ulike_stats::get_instance()->get_all_data();
    return wp_send_json($stats);
}
add_action('wp_ajax_wp_ulike_stats_api','wp_ulike_stats_api');
// @if DEV
add_action('wp_ajax_nopriv_wp_ulike_stats_api', 'wp_ulike_stats_api');
// @endif

/**
 * Engagement history api
 *
 * @return void
 */
function wp_ulike_history_api(){
	// @if DEV
	/*
	// @endif
	if( ! current_user_can( wp_ulike_get_user_access_capability('stats') ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	$type    = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : 'post';
	$page    = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
	$perPage = isset( $_GET['perPage'] ) ? absint( $_GET['perPage'] ) : 15;

	$settings = new wp_ulike_setting_type( $type );
	$instance = new wp_ulike_logs( $settings->getTableName(), $page, $perPage  );
	$output   = $instance->get_rows();

	wp_send_json( $output );
}
add_action('wp_ajax_wp_ulike_history_api','wp_ulike_history_api');
// @if DEV
add_action('wp_ajax_nopriv_wp_ulike_history_api', 'wp_ulike_history_api');
// @endif

/**
 * Localization api
 *
 * @return void
 */
function wp_ulike_localization_api(){
	wp_send_json( [
		'Dashboard'                    => esc_html__( 'Dashboard', 'wp-ulike' ),
		'Engagement History'           => esc_html__( 'Engagement History', 'wp-ulike' ),
		'View All History'             => esc_html__( 'View All History', 'wp-ulike' ),
		'All-Time Interaction Summary' => esc_html__( 'All-Time Interaction Summary', 'wp-ulike' ),
		'Today\'s Activity Snapshot'   => esc_html__( 'Today\'s Activity Snapshot', 'wp-ulike' ),
		'Yesterday\'s Activity Recap'  => esc_html__( 'Yesterday\'s Activity Recap', 'wp-ulike' ),
		'This Week\'s Engagement'      => esc_html__( 'This Week\'s Engagement', 'wp-ulike' ),
		'Monthly Engagement Report'    => esc_html__( 'Monthly Engagement Report', 'wp-ulike' ),
		'Yearly Engagement Analysis'   => esc_html__( 'Yearly Engagement Analysis', 'wp-ulike' ),
		'All-Time Performance'         => esc_html__( 'All-Time Performance', 'wp-ulike' ),
		'Engagement Statistics'        => esc_html__( 'Engagement Statistics', 'wp-ulike' ),
		'Top'                          => esc_html__( 'Top', 'wp-ulike' ),

		// Filters
		'Like'          => esc_html__( 'Like', 'wp-ulike' ),
		'Unlike'        => esc_html__( 'Unlike', 'wp-ulike' ),
		'Dislike'       => esc_html__( 'Dislike', 'wp-ulike' ),
		'Undislike'     => esc_html__( 'Undislike', 'wp-ulike' ),
		'Start Date'    => esc_html__( 'Start Date', 'wp-ulike' ),
		'End Date'      => esc_html__( 'End Date', 'wp-ulike' ),
		'Status Filter' => esc_html__( 'Status Filter', 'wp-ulike' ),

		// Charts
		'Dates'              => esc_html__( 'Dates', 'wp-ulike' ),
		'Totals'             => esc_html__( 'Totals', 'wp-ulike' ),
		'No data to display' => esc_html__( 'No data to display', 'wp-ulike' ),

		// History
		'Date'            => esc_html__( 'Date', 'wp-ulike' ),
		'User'            => esc_html__( 'User', 'wp-ulike' ),
		'IP'              => esc_html__( 'IP', 'wp-ulike' ),
		'Status'          => esc_html__( 'Status', 'wp-ulike' ),
		'Comment Author'  => esc_html__( 'Comment Author', 'wp-ulike' ),
		'Comment Content' => esc_html__( 'Comment Content', 'wp-ulike' ),
		'Activity Title'  => esc_html__( 'Activity Title', 'wp-ulike' ),
		'Topic Title'     => esc_html__( 'Topic Title', 'wp-ulike' ),
		'Post Title'      => esc_html__( 'Post Title', 'wp-ulike' ),
		'Category'        => esc_html__( 'Category', 'wp-ulike' ),
		'Showing'         => esc_html__( 'Showing', 'wp-ulike' ),
		'to'              => esc_html__( 'to', 'wp-ulike' ),
		'of'              => esc_html__( 'of', 'wp-ulike' ),
		'results'         => esc_html__( 'results', 'wp-ulike' ),

		// Types
		'Posts'      => esc_html__('Posts', 'wp-ulike'),
		'Comments'   => esc_html__('Comments', 'wp-ulike'),
		'Activities' => esc_html__('Activities', 'wp-ulike'),
		'Topics'     => esc_html__('Topics', 'wp-ulike'),

		// 404
		'No data found!'                                           => esc_html__( 'No data found!', 'wp-ulike' ),
		'This is because there is still no data in your database.' => esc_html__( 'This is because there is still no data in your database.', 'wp-ulike' ),

		// Banners
		'Discover Voting Insights, Top Likers & Best Content' => esc_html__( 'Discover Voting Insights, Top Likers & Best Content', 'wp-ulike'),
		'Unlock the full power of WP ULike Pro with our advanced Statistics tools. Instantly see what your users love and what they don\'t. Easily generate detailed reports using various chart options with a simple date range picker and status selector. No complicated settings or coding required.' => esc_html__( 'Unlock the full power of WP ULike Pro with our advanced Statistics tools. Instantly see what your users love and what they don\'t. Easily generate detailed reports using various chart options with a simple date range picker and status selector. No complicated settings or coding required.', 'wp-ulike'),
		'Discover Voting Insights, Top Likers & Best Content' => esc_html__( 'Discover Voting Insights, Top Likers & Best Content', 'wp-ulike'),
		'Get WP ULike Pro'  => esc_html__('Get WP ULike Pro', 'wp-ulike'),
		'Learn More' 		=> esc_html__('Learn More', 'wp-ulike'),

	] );
}
add_action('wp_ajax_wp_ulike_localization','wp_ulike_localization_api');
// @if DEV
add_action('wp_ajax_nopriv_wp_ulike_localization', 'wp_ulike_localization_api');
// @endif

