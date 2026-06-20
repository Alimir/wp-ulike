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
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( esc_html__( 'Permission denied.', 'wp-ulike' ) );
	}

	if ( ! isset( $_POST['id'] ) || ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), '_notice_nonce' ) ) {
		wp_send_json_error( esc_html__( 'Token Error.', 'wp-ulike' ) );
	}

	$expiration = isset( $_POST['expiration'] ) ? absint( $_POST['expiration'] ) : YEAR_IN_SECONDS;

	wp_ulike_set_transient( 'wp-ulike-notice-' . sanitize_text_field( wp_unslash( $_POST['id'] ) ), 1, $expiration );
	wp_send_json_success( esc_html__( 'It\'s OK.', 'wp-ulike' ) );
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
 * Save per-user stats dashboard preferences.
 *
 * @return void
 */
function wp_ulike_stats_save_user_prefs() {
	// @if DEV
	/*
	// @endif
	if( ! current_user_can( wp_ulike_get_user_access_capability('stats') ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_SLUG ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	$raw  = isset( $_POST['prefs'] ) ? wp_unslash( $_POST['prefs'] ) : '';
	$data = json_decode( $raw, true );

	if ( ! is_array( $data ) ) {
		wp_send_json_error( esc_html__( 'Invalid preferences payload.', 'wp-ulike' ) );
	}

	if ( ! class_exists( 'WP_Ulike_Stats_User_Prefs' ) ) {
		wp_send_json_error( esc_html__( 'Preferences storage is unavailable.', 'wp-ulike' ) );
	}

	WP_Ulike_Stats_User_Prefs::save_prefs( $data );

	wp_send_json_success( WP_Ulike_Stats_User_Prefs::get_prefs() );
}
add_action( 'wp_ajax_wp_ulike_stats_save_user_prefs', 'wp_ulike_stats_save_user_prefs' );

/**
 * Overview dashboard API (free).
 *
 * @return void
 */
function wp_ulike_overview_api() {
	// @if DEV
	/*
	// @endif
	if ( ! current_user_can( wp_ulike_get_user_access_capability( 'stats' ) ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_SLUG ) ) {
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	$stats = wp_ulike_stats::get_instance()->get_overview_api_data();
	return wp_send_json( $stats );
}
add_action( 'wp_ajax_wp_ulike_overview_api', 'wp_ulike_overview_api' );
// @if DEV
add_action( 'wp_ajax_nopriv_wp_ulike_overview_api', 'wp_ulike_overview_api' );
// @endif

/**
 * Engagement data for a single content type (free).
 *
 * @return void
 */
function wp_ulike_engagement_api() {
	// @if DEV
	/*
	// @endif
	if ( ! current_user_can( wp_ulike_get_user_access_capability( 'stats' ) ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_SLUG ) ) {
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	$type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
	$data = wp_ulike_stats::get_instance()->get_engagement_api_data( $type );

	if ( null === $data ) {
		wp_send_json_error( esc_html__( 'Invalid content type.', 'wp-ulike' ) );
	}

	return wp_send_json( $data );
}
add_action( 'wp_ajax_wp_ulike_engagement_api', 'wp_ulike_engagement_api' );
// @if DEV
add_action( 'wp_ajax_nopriv_wp_ulike_engagement_api', 'wp_ulike_engagement_api' );
// @endif

/**
 * Top content for a single type (free — no filters).
 *
 * @return void
 */
function wp_ulike_tops_api() {
	// @if DEV
	/*
	// @endif
	if ( ! current_user_can( wp_ulike_get_user_access_capability( 'stats' ) ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_SLUG ) ) {
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	$type  = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
	$limit = isset( $_GET['limit'] ) ? absint( $_GET['limit'] ) : 8;
	$data  = wp_ulike_stats::get_instance()->get_tops_api_data( $type, $limit );

	if ( null === $data ) {
		wp_send_json_error( esc_html__( 'Invalid content type.', 'wp-ulike' ) );
	}

	return wp_send_json( $data );
}
add_action( 'wp_ajax_wp_ulike_tops_api', 'wp_ulike_tops_api' );
// @if DEV
add_action( 'wp_ajax_nopriv_wp_ulike_tops_api', 'wp_ulike_tops_api' );
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

	$settings = wp_ulike_setting_type::get_instance( $type );
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

	$settings = wp_ulike_setting_type::get_instance( $type );
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

	wp_send_json( array(
		// Template variables (not shown in UI)
		'{{site_name}}'    => get_bloginfo( 'name' ),
		'{{language}}'     => substr( get_bloginfo( 'language' ), 0, 2 ),
		'{{display_name}}' => esc_attr( $current_user->display_name ),

		// Navigation & shell
		'Overview'             => esc_html__( 'Overview', 'wp-ulike' ),
		'Reports'              => esc_html__( 'Reports', 'wp-ulike' ),
		'Engagement'           => esc_html__( 'Engagement', 'wp-ulike' ),
		'Intelligence'         => esc_html__( 'Intelligence', 'wp-ulike' ),
		'Content intelligence' => esc_html__( 'Content intelligence', 'wp-ulike' ),
		'Audience'             => esc_html__( 'Audience', 'wp-ulike' ),
		'Countries'            => esc_html__( 'Countries', 'wp-ulike' ),
		'Technology'           => esc_html__( 'Technology', 'wp-ulike' ),
		'Devices'              => esc_html__( 'Devices', 'wp-ulike' ),
		'Logs'                 => esc_html__( 'Logs', 'wp-ulike' ),
		'Insights'             => esc_html__( 'Insights', 'wp-ulike' ),
		'View'                 => esc_html__( 'View', 'wp-ulike' ),
		'Filters'              => esc_html__( 'Filters', 'wp-ulike' ),
		'Clear all'            => esc_html__( 'Clear all', 'wp-ulike' ),
		'Apply'                => esc_html__( 'Apply', 'wp-ulike' ),
		'Cancel'               => esc_html__( 'Cancel', 'wp-ulike' ),
		'Clear'                => esc_html__( 'Clear', 'wp-ulike' ),
		'Loading...'             => esc_html__( 'Loading...', 'wp-ulike' ),

		// Page descriptions
		'Actionable engagement intelligence for your site' => esc_html__( 'Actionable engagement intelligence for your site', 'wp-ulike' ),
		'Your engagement dashboard at a glance'            => esc_html__( 'Your engagement dashboard at a glance', 'wp-ulike' ),
		'Publishing schedule, categories, and commerce insights' => esc_html__( 'Publishing schedule, categories, and commerce insights', 'wp-ulike' ),
		'See where your audience engages from'           => esc_html__( 'See where your audience engages from', 'wp-ulike' ),
		'Audience by location'                           => esc_html__( 'Audience by location', 'wp-ulike' ),
		'Device, OS and browser breakdown by unique voters' => esc_html__( 'Device, OS and browser breakdown by unique voters', 'wp-ulike' ),
		'Voters by device & browser'                     => esc_html__( 'Voters by device & browser', 'wp-ulike' ),
		'Trends and totals for {{type}}'                 => esc_html__( 'Trends and totals for {{type}}', 'wp-ulike' ),
		'Top {{type}} your audience engages with most'   => esc_html__( 'Top {{type}} your audience engages with most', 'wp-ulike' ),
		'Browse and manage vote history'                 => esc_html__( 'Browse and manage vote history', 'wp-ulike' ),
		'Browse and manage vote history for {{type}}'    => esc_html__( 'Browse and manage vote history for {{type}}', 'wp-ulike' ),

		// KPI metrics
		'Engagement This Week'        => esc_html__( 'Engagement This Week', 'wp-ulike' ),
		'Monthly Engagement Overview' => esc_html__( 'Monthly Engagement Overview', 'wp-ulike' ),
		'Yearly Engagement Trends'    => esc_html__( 'Yearly Engagement Trends', 'wp-ulike' ),
		'Overall Performance'         => esc_html__( 'Overall Performance', 'wp-ulike' ),
		'All time'                    => esc_html__( 'All time', 'wp-ulike' ),
		'This Week'                   => esc_html__( 'This Week', 'wp-ulike' ),
		'This month'                  => esc_html__( 'This month', 'wp-ulike' ),
		'This Year'                   => esc_html__( 'This Year', 'wp-ulike' ),
		'Today'                       => esc_html__( 'Today', 'wp-ulike' ),
		'Yesterday'                   => esc_html__( 'Yesterday', 'wp-ulike' ),
		'vs last week'                => esc_html__( 'vs last week', 'wp-ulike' ),
		'vs last month'               => esc_html__( 'vs last month', 'wp-ulike' ),
		'vs last year'                => esc_html__( 'vs last year', 'wp-ulike' ),
		'Total Interactions To Date'  => esc_html__( 'Total Interactions To Date', 'wp-ulike' ),
		'Engagement Summary'          => esc_html__( 'Engagement Summary', 'wp-ulike' ),
		'Totals at a glance'          => esc_html__( 'Totals at a glance', 'wp-ulike' ),
		'{{total}} total · {{today}} today' => esc_html__( '{{total}} total · {{today}} today', 'wp-ulike' ),

		// Engagement reports
		'Trends'              => esc_html__( 'Trends', 'wp-ulike' ),
		'Top content'         => esc_html__( 'Top content', 'wp-ulike' ),
		'positive'            => esc_html__( 'positive', 'wp-ulike' ),
		'likes'               => esc_html__( 'likes', 'wp-ulike' ),
		'Vote history · {{type}}' => esc_html__( 'Vote history · {{type}}', 'wp-ulike' ),
		'Only {{ratio}}% positive this week — check Top content for items getting dislikes.' => esc_html__( 'Only {{ratio}}% positive this week — check Top content for items getting dislikes.', 'wp-ulike' ),
		'Use Content intelligence to find the best publish times for your audience.' => esc_html__( 'Use Content intelligence to find the best publish times for your audience.', 'wp-ulike' ),
		'{{likes}} likes this week — use Top content to find what resonates.' => esc_html__( '{{likes}} likes this week — use Top content to find what resonates.', 'wp-ulike' ),
		'{{likes}} likes and {{dislikes}} dislikes — use Top content to find what resonates.' => esc_html__( '{{likes}} likes and {{dislikes}} dislikes — use Top content to find what resonates.', 'wp-ulike' ),

		// Content types
		'Posts'      => esc_html__( 'Posts', 'wp-ulike' ),
		'Comments'   => esc_html__( 'Comments', 'wp-ulike' ),
		'Activities' => esc_html__( 'Activities', 'wp-ulike' ),
		'Topics'     => esc_html__( 'Topics', 'wp-ulike' ),
		'Engagers'   => esc_html__( 'Engagers', 'wp-ulike' ),
		'Top members' => esc_html__( 'Top members', 'wp-ulike' ),
		'Most active engagers recently' => esc_html__( 'Most active engagers recently', 'wp-ulike' ),
		'Most active members recently'  => esc_html__( 'Most active members recently', 'wp-ulike' ),
		'Most active visitors recently' => esc_html__( 'Most active visitors recently', 'wp-ulike' ),
		'{{count}} actions this week — reward top engagers to build loyalty.' => esc_html__( '{{count}} actions this week — reward top engagers to build loyalty.', 'wp-ulike' ),
		'{{count}} actions this week — reward top members to build loyalty.' => esc_html__( '{{count}} actions this week — reward top members to build loyalty.', 'wp-ulike' ),
		'{{count}} actions this week — see which visitors engage most often.' => esc_html__( '{{count}} actions this week — see which visitors engage most often.', 'wp-ulike' ),

		// Filters & status
		'Like'           => esc_html__( 'Like', 'wp-ulike' ),
		'Unlike'         => esc_html__( 'Unlike', 'wp-ulike' ),
		'Dislike'        => esc_html__( 'Dislike', 'wp-ulike' ),
		'Undislike'      => esc_html__( 'Undislike', 'wp-ulike' ),
		'Status Filter'  => esc_html__( 'Status Filter', 'wp-ulike' ),
		'Status'         => esc_html__( 'Status', 'wp-ulike' ),
		'Date Range'     => esc_html__( 'Date Range', 'wp-ulike' ),
		'Start date'     => esc_html__( 'Start date', 'wp-ulike' ),
		'End date'       => esc_html__( 'End date', 'wp-ulike' ),
		'Select...'        => esc_html__( 'Select...', 'wp-ulike' ),
		'Content type'   => esc_html__( 'Content type', 'wp-ulike' ),
		'View By'        => esc_html__( 'View By', 'wp-ulike' ),
		'Device'         => esc_html__( 'Device', 'wp-ulike' ),
		'OS'             => esc_html__( 'OS', 'wp-ulike' ),
		'Browser'        => esc_html__( 'Browser', 'wp-ulike' ),
		'Post type'      => esc_html__( 'Post type', 'wp-ulike' ),
		'Taxonomy'       => esc_html__( 'Taxonomy', 'wp-ulike' ),
		'Sort by'        => esc_html__( 'Sort by', 'wp-ulike' ),
		'Search'         => esc_html__( 'Search', 'wp-ulike' ),
		'{{count}} selected' => esc_html__( '{{count}} selected', 'wp-ulike' ),

		// Date presets
		'Custom'                    => esc_html__( 'Custom', 'wp-ulike' ),
		'This week (Sun – Today)'   => esc_html__( 'This week (Sun – Today)', 'wp-ulike' ),
		'Last 7 days'               => esc_html__( 'Last 7 days', 'wp-ulike' ),
		'Last week (Sun – Sat)'     => esc_html__( 'Last week (Sun – Sat)', 'wp-ulike' ),
		'Last 28 days'              => esc_html__( 'Last 28 days', 'wp-ulike' ),
		'Last 30 days'              => esc_html__( 'Last 30 days', 'wp-ulike' ),
		'Last month'                => esc_html__( 'Last month', 'wp-ulike' ),

		// Tables & lists
		'Content'         => esc_html__( 'Content', 'wp-ulike' ),
		'Date'            => esc_html__( 'Date', 'wp-ulike' ),
		'User'            => esc_html__( 'User', 'wp-ulike' ),
		'IP'              => esc_html__( 'IP', 'wp-ulike' ),
		'Comment Author'  => esc_html__( 'Comment Author', 'wp-ulike' ),
		'Comment Content' => esc_html__( 'Comment Content', 'wp-ulike' ),
		'Activity Title'  => esc_html__( 'Activity Title', 'wp-ulike' ),
		'Topic Title'     => esc_html__( 'Topic Title', 'wp-ulike' ),
		'Post Title'      => esc_html__( 'Post Title', 'wp-ulike' ),
		'Category'        => esc_html__( 'Category', 'wp-ulike' ),
		'Actions'         => esc_html__( 'Actions', 'wp-ulike' ),
		'Name'            => esc_html__( 'Name', 'wp-ulike' ),
		'Share'           => esc_html__( 'Share', 'wp-ulike' ),
		'Growth'          => esc_html__( 'Growth', 'wp-ulike' ),
		'Country'         => esc_html__( 'Country', 'wp-ulike' ),
		'Voters'          => esc_html__( 'Voters', 'wp-ulike' ),
		'User(s)'         => esc_html__( 'User(s)', 'wp-ulike' ),
		'Engaged Users'   => esc_html__( 'Engaged Users', 'wp-ulike' ),
		'Unique users'    => esc_html__( 'Unique users', 'wp-ulike' ),
		'Selected period' => esc_html__( 'Selected period', 'wp-ulike' ),
		'Untitled'        => esc_html__( 'Untitled', 'wp-ulike' ),
		'Unknown User'    => esc_html__( 'Unknown User', 'wp-ulike' ),

		// Pagination & logs
		'Showing {{from}} to {{to}} of {{total}} results' => esc_html__( 'Showing {{from}} to {{to}} of {{total}} results', 'wp-ulike' ),
		'per page'        => esc_html__( 'per page', 'wp-ulike' ),
		'Total records'   => esc_html__( 'Total records', 'wp-ulike' ),
		'Delete Selected' => esc_html__( 'Delete Selected', 'wp-ulike' ),
		'Download CSV'    => esc_html__( 'Download CSV', 'wp-ulike' ),
		'Failed to delete the log entry.' => esc_html__( 'Failed to delete the log entry.', 'wp-ulike' ),
		'An error occurred while deleting the log entry.' => esc_html__( 'An error occurred while deleting the log entry.', 'wp-ulike' ),
		'No logs found for this category' => esc_html__( 'No logs found for this category', 'wp-ulike' ),

		// Empty & error states
		'No data for this period' => esc_html__( 'No data for this period', 'wp-ulike' ),
		'No data to display'      => esc_html__( 'No data to display', 'wp-ulike' ),
		'Try changing your filters or search.' => esc_html__( 'Try changing your filters or search.', 'wp-ulike' ),
		'Unable to load data. Please try again.' => esc_html__( 'Unable to load data. Please try again.', 'wp-ulike' ),
		'Something Went Wrong'    => esc_html__( 'Something Went Wrong', 'wp-ulike' ),
		'Unable to load data. Refresh the page or contact support.' => esc_html__( 'Unable to load data. Refresh the page or contact support.', 'wp-ulike' ),
		'Page Not Found'          => esc_html__( 'Page Not Found', 'wp-ulike' ),
		'This page does not exist or was moved.' => esc_html__( 'This page does not exist or was moved.', 'wp-ulike' ),
		'No Data Available'       => esc_html__( 'No Data Available', 'wp-ulike' ),
		'No data yet. Records will appear here once engagement starts.' => esc_html__( 'No data yet. Records will appear here once engagement starts.', 'wp-ulike' ),
		'Go to Home'              => esc_html__( 'Go to Home', 'wp-ulike' ),
		'Refresh Page'            => esc_html__( 'Refresh Page', 'wp-ulike' ),

		// Geography
		'User Engagement by Country' => esc_html__( 'User Engagement by Country', 'wp-ulike' ),
		'Activity by country'        => esc_html__( 'Activity by country', 'wp-ulike' ),
		'Top countries'              => esc_html__( 'Top countries', 'wp-ulike' ),
		'No country data yet'        => esc_html__( 'No country data yet', 'wp-ulike' ),
		'{{country}} is your top market with {{share}}% of engaged voters.' => esc_html__( '{{country}} is your top market with {{share}}% of engaged voters.', 'wp-ulike' ),

		// Intelligence & performance
		'Performance snapshot' => esc_html__( 'Performance snapshot', 'wp-ulike' ),
		'Engagement rate'      => esc_html__( 'Engagement rate', 'wp-ulike' ),
		'Positive sentiment'   => esc_html__( 'Positive sentiment', 'wp-ulike' ),
		'Total likes'          => esc_html__( 'Total likes', 'wp-ulike' ),
		'Engagement trend'     => esc_html__( 'Engagement trend', 'wp-ulike' ),
		'Button impressions'   => esc_html__( 'Button impressions', 'wp-ulike' ),
		'Total views'          => esc_html__( 'Total views', 'wp-ulike' ),
		'Reach'                => esc_html__( 'Reach', 'wp-ulike' ),
		'Daily activity'       => esc_html__( 'Daily activity', 'wp-ulike' ),
		'Likes this month'     => esc_html__( 'Likes this month', 'wp-ulike' ),
		'Interactions this month' => esc_html__( 'Interactions this month', 'wp-ulike' ),
		'Likes per view'       => esc_html__( 'Likes per view', 'wp-ulike' ),
		'Likes + dislikes per view' => esc_html__( 'Likes + dislikes per view', 'wp-ulike' ),
		'Like vs dislike ratio' => esc_html__( 'Like vs dislike ratio', 'wp-ulike' ),
		'Like-only template'   => esc_html__( 'Like-only template', 'wp-ulike' ),
		'{{likes}} likes · {{dislikes}} dislikes' => esc_html__( '{{likes}} likes · {{dislikes}} dislikes', 'wp-ulike' ),
		'Enable view tracking to measure conversion' => esc_html__( 'Enable view tracking to measure conversion', 'wp-ulike' ),
		'Page views that became reactions. Requires view tracking.' => esc_html__( 'Page views that became reactions. Requires view tracking.', 'wp-ulike' ),
		'Page views that became likes. Requires view tracking.' => esc_html__( 'Page views that became likes. Requires view tracking.', 'wp-ulike' ),
		'Likes as a share of all reactions.' => esc_html__( 'Likes as a share of all reactions.', 'wp-ulike' ),
		'Total likes in the last 30 days.' => esc_html__( 'Total likes in the last 30 days.', 'wp-ulike' ),
		'Daily reactions over the last 30 days.' => esc_html__( 'Daily reactions over the last 30 days.', 'wp-ulike' ),
		'Daily likes over the last 30 days.' => esc_html__( 'Daily likes over the last 30 days.', 'wp-ulike' ),
		'How often your like button was shown. Compare with engagements.' => esc_html__( 'How often your like button was shown. Compare with engagements.', 'wp-ulike' ),
		'How views convert into likes and dislikes.' => esc_html__( 'How views convert into likes and dislikes.', 'wp-ulike' ),
		'How views convert into likes.' => esc_html__( 'How views convert into likes.', 'wp-ulike' ),

		// Content intelligence report
		'When to publish'     => esc_html__( 'When to publish', 'wp-ulike' ),
		'When your audience is most likely to react.' => esc_html__( 'When your audience is most likely to react.', 'wp-ulike' ),
		'Sweet spot'          => esc_html__( 'Sweet spot', 'wp-ulike' ),
		'Peak'                => esc_html__( 'Peak', 'wp-ulike' ),
		'Hourly pattern'      => esc_html__( 'Hourly pattern', 'wp-ulike' ),
		'Full report'         => esc_html__( 'Full report', 'wp-ulike' ),
		'{{day}} · {{time}}'  => esc_html__( '{{day}} · {{time}}', 'wp-ulike' ),
		'{{share}}% of weekly activity' => esc_html__( '{{share}}% of weekly activity', 'wp-ulike' ),
		'{{share}}% of all activity · {{range}}' => esc_html__( '{{share}}% of all activity · {{range}}', 'wp-ulike' ),
		'{{count}} engagements in this slot' => esc_html__( '{{count}} engagements in this slot', 'wp-ulike' ),
		'Activity heatmap'    => esc_html__( 'Activity heatmap', 'wp-ulike' ),
		'Engagement by day and hour' => esc_html__( 'Engagement by day and hour', 'wp-ulike' ),
		'When your audience engages' => esc_html__( 'When your audience engages', 'wp-ulike' ),
		'Time windows'        => esc_html__( 'Time windows', 'wp-ulike' ),
		'Best day'            => esc_html__( 'Best day', 'wp-ulike' ),
		'Best days'           => esc_html__( 'Best days', 'wp-ulike' ),
		'Share of weekly activity' => esc_html__( 'Share of weekly activity', 'wp-ulike' ),
		'Best hour'           => esc_html__( 'Best hour', 'wp-ulike' ),
		'Category performance' => esc_html__( 'Category performance', 'wp-ulike' ),
		'Top categories'      => esc_html__( 'Top categories', 'wp-ulike' ),
		'Shop spotlight'      => esc_html__( 'Shop spotlight', 'wp-ulike' ),
		'Less'                => esc_html__( 'Less', 'wp-ulike' ),
		'More'                => esc_html__( 'More', 'wp-ulike' ),
		'Publish on {{day}} around {{time}} for maximum engagement.' => esc_html__( 'Publish on {{day}} around {{time}} for maximum engagement.', 'wp-ulike' ),
		'Your audience is most active in the {{window}} window ({{range}}).' => esc_html__( 'Your audience is most active in the {{window}} window ({{range}}).', 'wp-ulike' ),
		'{{share}}% of engagers use mobile — optimize for small screens.' => esc_html__( '{{share}}% of engagers use mobile — optimize for small screens.', 'wp-ulike' ),
		'{{share}}% engage from desktop — long-form content may perform better.' => esc_html__( '{{share}}% engage from desktop — long-form content may perform better.', 'wp-ulike' ),

		// Growth tips (overview)
		'Actionable recommendations based on your data' => esc_html__( 'Actionable recommendations based on your data', 'wp-ulike' ),
		'Best time to publish' => esc_html__( 'Best time to publish', 'wp-ulike' ),
		'{{day}} around {{time}} gets the most engagement — schedule content then.' => esc_html__( '{{day}} around {{time}} gets the most engagement — schedule content then.', 'wp-ulike' ),
		'Top category' => esc_html__( 'Top category', 'wp-ulike' ),
		'{{category}} drives {{share}}% of engagement — create more on this topic.' => esc_html__( '{{category}} drives {{share}}% of engagement — create more on this topic.', 'wp-ulike' ),
		'Low conversion' => esc_html__( 'Low conversion', 'wp-ulike' ),
		'Only {{rate}}% of viewers engage — improve button placement and CTAs.' => esc_html__( 'Only {{rate}}% of viewers engage — improve button placement and CTAs.', 'wp-ulike' ),
		'Momentum' => esc_html__( 'Momentum', 'wp-ulike' ),
		'Engagement is up {{percent}}% this week.' => esc_html__( 'Engagement is up {{percent}}% this week.', 'wp-ulike' ),
		'Sentiment drop' => esc_html__( 'Sentiment drop', 'wp-ulike' ),
		'Positive reactions fell to {{ratio}}% — review content getting dislikes.' => esc_html__( 'Positive reactions fell to {{ratio}}% — review content getting dislikes.', 'wp-ulike' ),

		// Top content insights
		'{{title}} leads with {{count}} likes in this period.' => esc_html__( '{{title}} leads with {{count}} likes in this period.', 'wp-ulike' ),
		'{{title}} converts best at {{rate}}% — replicate this format.' => esc_html__( '{{title}} converts best at {{rate}}% — replicate this format.', 'wp-ulike' ),
		'{{name}} is your most active user — consider a loyalty perk.' => esc_html__( '{{name}} is your most active user — consider a loyalty perk.', 'wp-ulike' ),

		// Header & metrics
		'Refresh data' => esc_html__( 'Refresh data', 'wp-ulike' ),
		'Total engagements' => esc_html__( 'Total engagements', 'wp-ulike' ),
		'Distinct people who engaged in this period.' => esc_html__( 'Distinct people who engaged in this period.', 'wp-ulike' ),

		// Notifications
		'Dismiss'             => esc_html__( 'Dismiss', 'wp-ulike' ),
		'New Notifications'   => esc_html__( 'New Notifications', 'wp-ulike' ),
		'No new notifications' => esc_html__( 'No new notifications', 'wp-ulike' ),

		// License
		'License Not Found!' => esc_html__( 'License Not Found!', 'wp-ulike' ),
		'Your license is invalid or missing. Enter a valid key or purchase Pro to continue.' => esc_html__( 'Your license is invalid or missing. Enter a valid key or purchase Pro to continue.', 'wp-ulike' ),
		'Get License'        => esc_html__( 'Get License', 'wp-ulike' ),

		// Pro preview, sidebar & free shell
		'Upgrade to Pro' => esc_html__( 'Upgrade to Pro', 'wp-ulike' ),
		'Learn more'     => esc_html__( 'Learn more', 'wp-ulike' ),
		'Get Pro'        => esc_html__( 'Get Pro', 'wp-ulike' ),
		'Pro'            => esc_html__( 'Pro', 'wp-ulike' ),
		'Settings'       => esc_html__( 'Settings', 'wp-ulike' ),
		'Help'           => esc_html__( 'Help', 'wp-ulike' ),
		'Intelligence, maps, and advanced filters.' => esc_html__( 'Intelligence, maps, and advanced filters.', 'wp-ulike' ),
		'Unlock deeper insights' => esc_html__( 'Unlock deeper insights', 'wp-ulike' ),
		'Preview Pro reports — upgrade anytime for live data.' => esc_html__( 'Preview Pro reports — upgrade anytime for live data.', 'wp-ulike' ),
		'Publishing schedule, category insights, and commerce analytics — available in Pro.' => esc_html__( 'Publishing schedule, category insights, and commerce analytics — available in Pro.', 'wp-ulike' ),
		'See where your audience engages from with country breakdowns and trends.' => esc_html__( 'See where your audience engages from with country breakdowns and trends.', 'wp-ulike' ),
		'Device, OS, and browser breakdowns for every vote.' => esc_html__( 'Device, OS, and browser breakdowns for every vote.', 'wp-ulike' ),
		'Device, OS, and browser breakdowns.' => esc_html__( 'Device, OS, and browser breakdowns.', 'wp-ulike' ),
		'Discover your most active members and reward loyal engagers.' => esc_html__( 'Discover your most active members and reward loyal engagers.', 'wp-ulike' ),
		'See exactly who engaged with each piece of content.' => esc_html__( 'See exactly who engaged with each piece of content.', 'wp-ulike' ),
		'Users who engaged with this content' => esc_html__( 'Users who engaged with this content', 'wp-ulike' ),
		'Member'         => esc_html__( 'Member', 'wp-ulike' ),
		'Last active'    => esc_html__( 'Last active', 'wp-ulike' ),
		'Reactions'      => esc_html__( 'Reactions', 'wp-ulike' ),
		'Back to engagement' => esc_html__( 'Back to engagement', 'wp-ulike' ),
		'{{count}} engaged users' => esc_html__( '{{count}} engaged users', 'wp-ulike' ),
		'{{count}} users engaged with {{title}}' => esc_html__( '{{count}} users engaged with {{title}}', 'wp-ulike' ),
		'{{count}} engagers' => esc_html__( '{{count}} engagers', 'wp-ulike' ),
		'No engagers'    => esc_html__( 'No engagers', 'wp-ulike' ),
		'{{count}} total' => esc_html__( '{{count}} total', 'wp-ulike' ),
		'Engagement up {{percent}}% this week.' => esc_html__( 'Engagement up {{percent}}% this week.', 'wp-ulike' ),
		'Totals and charts for {{type}}' => esc_html__( 'Totals and charts for {{type}}', 'wp-ulike' ),
		'Trends and top content for {{type}}' => esc_html__( 'Trends and top content for {{type}}', 'wp-ulike' ),
		'{{share}}% of engagers are in the United States — consider localized content.' => esc_html__( '{{share}}% of engagers are in the United States — consider localized content.', 'wp-ulike' ),
		'Morning'        => esc_html__( 'Morning', 'wp-ulike' ),
		'Afternoon'      => esc_html__( 'Afternoon', 'wp-ulike' ),
		'Evening'        => esc_html__( 'Evening', 'wp-ulike' ),
		'Night'          => esc_html__( 'Night', 'wp-ulike' ),

		// Time ago
		'timeAgo'       => esc_html__( '{{count}} {{interval}} ago', 'wp-ulike' ),
		'year'          => esc_html__( 'year', 'wp-ulike' ),
		'year_plural'   => esc_html__( 'years', 'wp-ulike' ),
		'month'         => esc_html__( 'month', 'wp-ulike' ),
		'month_plural'  => esc_html__( 'months', 'wp-ulike' ),
		'week'          => esc_html__( 'week', 'wp-ulike' ),
		'week_plural'   => esc_html__( 'weeks', 'wp-ulike' ),
		'day'           => esc_html__( 'day', 'wp-ulike' ),
		'day_plural'    => esc_html__( 'days', 'wp-ulike' ),
		'hour'          => esc_html__( 'hour', 'wp-ulike' ),
		'hour_plural'   => esc_html__( 'hours', 'wp-ulike' ),
		'minute'        => esc_html__( 'minute', 'wp-ulike' ),
		'minute_plural' => esc_html__( 'minutes', 'wp-ulike' ),
		'second'        => esc_html__( 'second', 'wp-ulike' ),
		'second_plural' => esc_html__( 'seconds', 'wp-ulike' ),
		'Just Now'      => esc_html__( 'just now', 'wp-ulike' ),
	) );
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

	$max_body = defined( 'MB_IN_BYTES' ) ? 2 * MB_IN_BYTES : 2097152;
	$json     = wp_ulike_read_php_input_capped( $max_body );
	if ( is_wp_error( $json ) ) {
		wp_send_json_error( $json->get_error_message() );
	}
	$values = json_decode( $json, true );

	if ( ! is_array( $values ) ) {
		wp_send_json_error( esc_html__( 'Invalid request data. Expected an object with setting values.', 'wp-ulike' ) );
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

	$max_body = defined( 'MB_IN_BYTES' ) ? 2 * MB_IN_BYTES : 2097152;
	$json     = wp_ulike_read_php_input_capped( $max_body );
	if ( is_wp_error( $json ) ) {
		wp_send_json_error( $json->get_error_message() );
	}
	$values = json_decode( $json, true );

	if ( ! is_array( $values ) ) {
		wp_send_json_error( esc_html__( 'Invalid request data. Expected an object with customizer values.', 'wp-ulike' ) );
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

