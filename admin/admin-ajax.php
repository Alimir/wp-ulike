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
	if ( ! isset( $_POST['id'] ) ||  ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), '_notice_nonce' ) ) {
		wp_send_json_error(  esc_html__( 'Token Error.', 'wp-ulike' ) );
	} else {
		wp_ulike_set_transient( 'wp-ulike-notice-' . sanitize_text_field( wp_unslash( $_POST['id' ] ) ), 1, absint( $_POST['expiration'] ) );
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
	if( ! current_user_can( wp_ulike_get_user_access_capability('stats') ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_SLUG ) ){
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
	if( ! current_user_can( wp_ulike_get_user_access_capability('stats') ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_SLUG )  ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	$type    = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : 'post';
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
 * Engagement history api
 *
 * @return void
 */
function wp_ulike_delete_history_api(){
	// @if DEV
	/*
	// @endif
	if( ! current_user_can( 'manage_options' ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_SLUG ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	$item_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$type    = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';

	if( empty( $item_id ) || empty( $type ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}

	$settings = new wp_ulike_setting_type( $type );
	$instance = new wp_ulike_logs( $settings->getTableName()  );

	if( ! $instance->delete_row( $item_id ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}

	wp_send_json_success();
}
add_action('wp_ajax_wp_ulike_delete_history_api','wp_ulike_delete_history_api');
// @if DEV
add_action('wp_ajax_nopriv_wp_ulike_delete_history_api', 'wp_ulike_delete_history_api');
// @endif

/**
 * Localization api
 *
 * @return void
 */
function wp_ulike_localization_api(){
	// @if DEV
	/*
	// @endif
	if( ! current_user_can( wp_ulike_get_user_access_capability('stats') ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	global $current_user;

	wp_send_json( [
		// variables
		'{{site_name}}'    => get_bloginfo('name'),
		'{{language}}'     => substr(get_bloginfo('language'), 0, 2),
		'{{display_name}}' => esc_attr( $current_user->display_name ),

		// dashboard
		'Metrics Dashboard'                                        => esc_html__( 'Metrics Dashboard', 'wp-ulike' ),
		'Engagement Logs'                                          => esc_html__( 'Engagement Logs', 'wp-ulike' ),
		'Top Trends'                                               => esc_html__( 'Top Trends', 'wp-ulike' ),
		'View Full History'                                        => esc_html__( 'View Full History', 'wp-ulike' ),
		'Total Interactions To Date'                               => esc_html__( 'Total Interactions To Date', 'wp-ulike' ),
		'Today\'s Engagement Overview'                             => esc_html__( 'Today\'s Engagement Overview', 'wp-ulike' ),
		'Yesterday\'s Engagement Summary'                          => esc_html__( 'Yesterday\'s Engagement Summary', 'wp-ulike' ),
		'Device Insights'                                          => esc_html__( 'Device Insights', 'wp-ulike' ),
		'Track devices, systems and browsers'                      => esc_html__( 'Track devices, systems and browsers', 'wp-ulike' ),
		'Engagement This Week'                                     => esc_html__( 'Engagement This Week', 'wp-ulike' ),
		'Monthly Engagement Overview'                              => esc_html__( 'Monthly Engagement Overview', 'wp-ulike' ),
		'Yearly Engagement Trends'                                 => esc_html__( 'Yearly Engagement Trends', 'wp-ulike' ),
		'Overall Performance'                                      => esc_html__( 'Overall Performance', 'wp-ulike' ),
		'Daily Interaction Overview'                               => esc_html__( 'Daily Interaction Overview', 'wp-ulike' ),
		'User Engagement by Country'                               => esc_html__( 'User Engagement by Country', 'wp-ulike' ),
		'See where your audience is most active across the globe.' => esc_html__( 'See where your audience is most active across the globe.', 'wp-ulike' ),
		'Engagement Summary'                                       => esc_html__( 'Engagement Summary', 'wp-ulike' ),
		'Top'                                                      => esc_html__( 'Top', 'wp-ulike' ),
		'Showing data for'                                         => esc_html__( 'Showing data for', 'wp-ulike' ),
		'Overview'                                                 => esc_html__( 'Overview', 'wp-ulike' ),
		'Insights'                                                 => esc_html__( 'Insights', 'wp-ulike' ),
		'Logs'                                                     => esc_html__( 'Logs', 'wp-ulike' ),
		'No new notifications'                                     => esc_html__( 'No new notifications', 'wp-ulike' ),
		'New Notifications'                                        => esc_html__( 'New Notifications', 'wp-ulike' ),
		'Tablet'                                                   => esc_html__( 'Tablet', 'wp-ulike' ),
		'Mobile'                                                   => esc_html__( 'Mobile', 'wp-ulike' ),
		'Desktop'                                                  => esc_html__( 'Desktop', 'wp-ulike' ),
		'Unknown'                                                  => esc_html__( 'Unknown', 'wp-ulike' ),
		'Country'                                                  => esc_html__( 'Country', 'wp-ulike' ),
		'Active Users'                                             => esc_html__( 'Active Users', 'wp-ulike' ),
		'User(s)'                                                  => esc_html__( 'User(s)', 'wp-ulike' ),
		'Other Countries'                                          => esc_html__( 'Other Countries', 'wp-ulike' ),
		'for'                                                      => esc_html__( 'for', 'wp-ulike' ),
		'Growth'                                                   => esc_html__( 'Growth', 'wp-ulike' ),

		// Filters
		'Like'                   => esc_html__( 'Like', 'wp-ulike' ),
		'Unlike'                 => esc_html__( 'Unlike', 'wp-ulike' ),
		'Dislike'                => esc_html__( 'Dislike', 'wp-ulike' ),
		'Undislike'              => esc_html__( 'Undislike', 'wp-ulike' ),
		'Select...'              => esc_html__( 'Select...', 'wp-ulike' ),
		'Date Range'             => esc_html__( 'Date Range', 'wp-ulike' ),
		'This Week'              => esc_html__( 'This Week', 'wp-ulike' ),
		'Last Week'              => esc_html__( 'Last Week', 'wp-ulike' ),
		'Last {{days}} Days'     => esc_html__( 'Last {{days}} Days', 'wp-ulike' ),
		'Last {{months}} Months' => esc_html__( 'Last {{months}} Months', 'wp-ulike' ),
		'This Year'              => esc_html__( 'This Year', 'wp-ulike' ),
		'Last Year'              => esc_html__( 'Last Year', 'wp-ulike' ),
		'Status Filter'          => esc_html__( 'Status Filter', 'wp-ulike' ),
		'Content Types'          => esc_html__( 'Content Types', 'wp-ulike' ),
		'Post Types'             => esc_html__( 'Post Types', 'wp-ulike' ),
		'View By'                => esc_html__( 'View By', 'wp-ulike' ),
		'Device'                 => esc_html__( 'Device', 'wp-ulike' ),
		'OS'                     => esc_html__( 'OS', 'wp-ulike' ),
		'Browser'                => esc_html__( 'Browser', 'wp-ulike' ),
		'Show Filters'           => esc_html__( 'Show Filters', 'wp-ulike' ),
		'Hide Filters'           => esc_html__( 'Hide Filters', 'wp-ulike' ),
		'Clear all'              => esc_html__( 'Clear all', 'wp-ulike' ),
		'Apply'                  => esc_html__( 'Apply', 'wp-ulike' ),

		// Charts
		'Dates'                                    => esc_html__( 'Dates', 'wp-ulike' ),
		'Totals'                                   => esc_html__( 'Totals', 'wp-ulike' ),
		'No data to display'                       => esc_html__( 'No data to display', 'wp-ulike' ),
		'Daily Interactions'                       => esc_html__( 'Daily Interactions', 'wp-ulike' ),
		'Monthly Interactions'                     => esc_html__( 'Monthly Interactions', 'wp-ulike' ),

		// Items
		'Engaged Users' => esc_html__( 'Engaged Users', 'wp-ulike' ),
		'Published'     => esc_html__( 'Published', 'wp-ulike' ),
		'Comments'      => esc_html__( 'Comments', 'wp-ulike' ),
		'Engagement'    => esc_html__( 'Engagement', 'wp-ulike' ),
		'Comments'      => esc_html__( 'Comments', 'wp-ulike' ),
		'By'            => esc_html__( 'By', 'wp-ulike' ),
		'Views'         => esc_html__( 'Views', 'wp-ulike' ),

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
		'Actions'         => esc_html__( 'Actions', 'wp-ulike' ),
		'Showing'         => esc_html__( 'Showing', 'wp-ulike' ),
		'to'              => esc_html__( 'to', 'wp-ulike' ),
		'of'              => esc_html__( 'of', 'wp-ulike' ),
		'results'         => esc_html__( 'results', 'wp-ulike' ),
		'Back'            => esc_html__( 'Back', 'wp-ulike' ),
		'Search'          => esc_html__( 'Search', 'wp-ulike' ),
		'Loading...'      => esc_html__( 'Loading...', 'wp-ulike' ),
		'Download CSV'    => esc_html__( 'Download CSV', 'wp-ulike' ),
		'Delete Selected' => esc_html__( 'Delete Selected', 'wp-ulike' ),
		'Filter by type:' => esc_html__( 'Filter by type:', 'wp-ulike' ),
		'per page'        => esc_html__( 'per page', 'wp-ulike' ),
		'View all'        => esc_html__( 'View all', 'wp-ulike' ),

		// Types
		'Posts'      => esc_html__('Posts', 'wp-ulike'),
		'Comments'   => esc_html__('Comments', 'wp-ulike'),
		'Activities' => esc_html__('Activities', 'wp-ulike'),
		'Topics'     => esc_html__('Topics', 'wp-ulike'),
		'Engagers'   => esc_html__('Engagers', 'wp-ulike'),

		// Not found
		'Something Went Wrong'	=> esc_html__( 'Something Went Wrong', 'wp-ulike' ),
		'We encountered an error while loading the data. Please try refreshing the page or contact support if the problem persists.' => esc_html__( 'We encountered an error while loading the data. Please try refreshing the page or contact support if the problem persists.', 'wp-ulike' ),
		'Page Not Found'	=> esc_html__( 'Page Not Found', 'wp-ulike' ),
		'The page you are looking for does not exist. It may have been moved or deleted.' => esc_html__( 'The page you are looking for does not exist. It may have been moved or deleted.', 'wp-ulike' ),
		'No Data Available'	=> esc_html__( 'No Data Available', 'wp-ulike' ),
		'There is no data to display at the moment. This could mean there are no records in the database yet, or the data is still being processed.' => esc_html__( 'There is no data to display at the moment. This could mean there are no records in the database yet, or the data is still being processed.', 'wp-ulike' ),
		'Go to Home'	=> esc_html__( 'Go to Home', 'wp-ulike' ),
		'Refresh Page'	=> esc_html__( 'Refresh Page', 'wp-ulike' ),
		'If this problem continues, please contact support.'	=> esc_html__( 'If this problem continues, please contact support.', 'wp-ulike' ),
		'Data will appear here once it becomes available.'	=> esc_html__( 'Data will appear here once it becomes available.', 'wp-ulike' ),
		'Check the URL or return to the homepage.'	=> esc_html__( 'Check the URL or return to the homepage.', 'wp-ulike' ),

		// License
		'License Not Found!'	=> esc_html__( 'License Not Found!', 'wp-ulike' ),
		'The license you provided is invalid or could not be found. Please verify your license key or purchase a new license to continue using the pro features.' => esc_html__( 'The license you provided is invalid or could not be found. Please verify your license key or purchase a new license to continue using the pro features.', 'wp-ulike' ),
		'Get License'	=> esc_html__( 'Get License', 'wp-ulike' ),
		'If you believe this is an error, please contact support or try refreshing the page.'	=> esc_html__( 'If you believe this is an error, please contact support or try refreshing the page.', 'wp-ulike' ),

		// Banners
		'Unlock Your Website\'s True Potential!' => esc_html__( 'Unlock Your Website\'s True Potential!', 'wp-ulike'),
		'Imagine knowing exactly what makes your content shine and how to connect with the fans who\'ll help you grow. Picture having the tools to make smarter decisions, boost your engagement, and take your website to the next level. Ready to uncover what\'s possible?' => esc_html__( 'Imagine knowing exactly what makes your content shine and how to connect with the fans who\'ll help you grow. Picture having the tools to make smarter decisions, boost your engagement, and take your website to the next level. Ready to uncover what\'s possible?', 'wp-ulike'),
		'Get Started'   => esc_html__('Get Started', 'wp-ulike'),
		'Discover More' => esc_html__('Discover More', 'wp-ulike'),

		// TimeAgo
		"timeAgo"       => esc_html__('{{count}} {{interval}} ago', 'wp-ulike'),
		"year"          => esc_html__('year', 'wp-ulike'),
		"year_plural"   => esc_html__('years', 'wp-ulike'),
		"month"         => esc_html__('month', 'wp-ulike'),
		"month_plural"  => esc_html__('months', 'wp-ulike'),
		"week"          => esc_html__('week', 'wp-ulike'),
		"week_plural"   => esc_html__('weeks', 'wp-ulike'),
		"day"           => esc_html__('day', 'wp-ulike'),
		"day_plural"    => esc_html__('days', 'wp-ulike'),
		"hour"          => esc_html__('hour', 'wp-ulike'),
		"hour_plural"   => esc_html__('hours', 'wp-ulike'),
		"minute"        => esc_html__('minute', 'wp-ulike'),
		"minute_plural" => esc_html__('minutes', 'wp-ulike'),
		"second"        => esc_html__('second', 'wp-ulike'),
		"second_plural" => esc_html__('seconds', 'wp-ulike'),
		"Just Now"      => esc_html__('just now', 'wp-ulike'),
	] );
}
add_action('wp_ajax_wp_ulike_localization','wp_ulike_localization_api');
// @if DEV
add_action('wp_ajax_nopriv_wp_ulike_localization', 'wp_ulike_localization_api');
// @endif

/**
 * Settings schema api
 *
 * @return void
 */
function wp_ulike_schema_api(){
	// @if DEV
	/*
	// @endif
	if( ! current_user_can( 'manage_options' ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_SLUG ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	// Get settings API instance
	if ( class_exists( 'wp_ulike_settings_api' ) ) {
		$settings_api = new wp_ulike_settings_api();
		$schema = $settings_api->get_schema();
		wp_send_json_success( $schema );
	} else {
		wp_send_json_error( esc_html__( 'Error: Settings API not available.', 'wp-ulike' ) );
	}
}
add_action('wp_ajax_wp_ulike_schema_api','wp_ulike_schema_api');
// @if DEV
add_action('wp_ajax_nopriv_wp_ulike_schema_api', 'wp_ulike_schema_api');
// @endif

/**
 * Settings values api
 *
 * @return void
 */
function wp_ulike_settings_api(){
	// @if DEV
	/*
	// @endif
	if( ! current_user_can( 'manage_options' ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_SLUG ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	// Get settings API instance
	if ( class_exists( 'wp_ulike_settings_api' ) ) {
		$settings_api = new wp_ulike_settings_api();
		$values = $settings_api->get_settings( null );
		wp_send_json_success( $values );
	} else {
		wp_send_json_error( esc_html__( 'Error: Settings API not available.', 'wp-ulike' ) );
	}
}
add_action('wp_ajax_wp_ulike_settings_api','wp_ulike_settings_api');
// @if DEV
add_action('wp_ajax_nopriv_wp_ulike_settings_api', 'wp_ulike_settings_api');
// @endif

/**
 * Save settings api
 *
 * @return void
 */
function wp_ulike_save_settings_api(){
	// @if DEV
	/*
	// @endif
	if( ! current_user_can( 'manage_options' ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_SLUG ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	// Get JSON data from request body
	$json = file_get_contents( 'php://input' );
	$values = json_decode( $json, true );

	if ( ! is_array( $values ) ) {
		wp_send_json_error( esc_html__( 'Error: Invalid request data. Expected an object with setting values.', 'wp-ulike' ) );
	}

	// Get settings API instance
	if ( class_exists( 'wp_ulike_settings_api' ) ) {
		$settings_api = new wp_ulike_settings_api();
		$result = $settings_api->save_settings( $values );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		} else {
			wp_send_json_success( $result );
		}
	} else {
		wp_send_json_error( esc_html__( 'Error: Settings API not available.', 'wp-ulike' ) );
	}
}
add_action('wp_ajax_wp_ulike_save_settings_api','wp_ulike_save_settings_api');
// @if DEV
add_action('wp_ajax_nopriv_wp_ulike_save_settings_api', 'wp_ulike_save_settings_api');
// @endif


/**
 * Customizer schema api
 *
 * @return void
 */
function wp_ulike_customizer_schema_api(){
	// @if DEV
	/*
	// @endif
	if( ! current_user_can( 'manage_options' ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_SLUG ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	// Get customizer API instance
	if ( class_exists( 'wp_ulike_customizer_api' ) ) {
		$customizer_api = new wp_ulike_customizer_api();
		$schema = $customizer_api->get_schema();
		wp_send_json_success( $schema );
	} else {
		wp_send_json_error( esc_html__( 'Error: Customizer API not available.', 'wp-ulike' ) );
	}
}
add_action('wp_ajax_wp_ulike_customizer_schema_api','wp_ulike_customizer_schema_api');
// @if DEV
add_action('wp_ajax_nopriv_wp_ulike_customizer_schema_api', 'wp_ulike_customizer_schema_api');
// @endif

/**
 * Customizer values api
 *
 * @return void
 */
function wp_ulike_customizer_values_api(){
	// @if DEV
	/*
	// @endif
	if( ! current_user_can( 'manage_options' ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_SLUG ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	// Get customizer API instance
	if ( class_exists( 'wp_ulike_customizer_api' ) ) {
		$customizer_api = new wp_ulike_customizer_api();
		$values = $customizer_api->get_values( null );
		wp_send_json_success( $values );
	} else {
		wp_send_json_error( esc_html__( 'Error: Customizer API not available.', 'wp-ulike' ) );
	}
}
add_action('wp_ajax_wp_ulike_customizer_values_api','wp_ulike_customizer_values_api');
// @if DEV
add_action('wp_ajax_nopriv_wp_ulike_customizer_values_api', 'wp_ulike_customizer_values_api');
// @endif

/**
 * Save customizer api
 *
 * @return void
 */
function wp_ulike_save_customizer_api(){
	// @if DEV
	/*
	// @endif
	if( ! current_user_can( 'manage_options' ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_SLUG ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	// Get JSON data from request body
	$json = file_get_contents( 'php://input' );
	$values = json_decode( $json, true );

	if ( ! is_array( $values ) ) {
		wp_send_json_error( esc_html__( 'Error: Invalid request data. Expected an object with customizer values.', 'wp-ulike' ) );
	}

	// Get customizer API instance
	if ( class_exists( 'wp_ulike_customizer_api' ) ) {
		$customizer_api = new wp_ulike_customizer_api();
		$customizer_api->save_values( $values );
	} else {
		wp_send_json_error( esc_html__( 'Error: Customizer API not available.', 'wp-ulike' ) );
	}
}
add_action('wp_ajax_wp_ulike_save_customizer_api','wp_ulike_save_customizer_api');
// @if DEV
add_action('wp_ajax_nopriv_wp_ulike_save_customizer_api', 'wp_ulike_save_customizer_api');
// @endif

/**
 * Customizer preview api
 *
 * @return void
 */
function wp_ulike_customizer_preview_api(){
	// @if DEV
	/*
	// @endif
	if( ! current_user_can( 'manage_options' ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_SLUG ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	// Get customizer API instance
	if ( class_exists( 'wp_ulike_customizer_api' ) ) {
		$customizer_api = new wp_ulike_customizer_api();
		$customizer_api->get_preview( null );
	} else {
		wp_send_json_error( esc_html__( 'Error: Customizer API not available.', 'wp-ulike' ) );
	}
}
add_action('wp_ajax_wp_ulike_customizer_preview_api','wp_ulike_customizer_preview_api');
// @if DEV
add_action('wp_ajax_nopriv_wp_ulike_customizer_preview_api', 'wp_ulike_customizer_preview_api');
// @endif

