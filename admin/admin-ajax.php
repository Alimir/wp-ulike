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
		wp_send_json_error( __( 'Permission denied.', 'wp-ulike' ) );
	}

	if ( ! isset( $_POST['id'] ) || ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), '_notice_nonce' ) ) {
		wp_send_json_error( __( 'Token Error.', 'wp-ulike' ) );
	}

	$expiration = isset( $_POST['expiration'] ) ? absint( $_POST['expiration'] ) : YEAR_IN_SECONDS;

	wp_ulike_set_transient( 'wp-ulike-notice-' . sanitize_text_field( wp_unslash( $_POST['id'] ) ), 1, $expiration );
	wp_send_json_success( __( 'It\'s OK.', 'wp-ulike' ) );
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
		wp_send_json_error( __( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
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
	$nonce_valid = wp_ulike_is_valid_nonce( WP_ULIKE_SLUG );
	if ( ! $nonce_valid && defined( 'WP_ULIKE_PRO_DOMAIN' ) ) {
		$nonce_valid = wp_ulike_is_valid_nonce( WP_ULIKE_PRO_DOMAIN );
	}

	if ( ! current_user_can( wp_ulike_get_user_access_capability( 'stats' ) ) || ! $nonce_valid ) {
		wp_send_json_error( __( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	$raw  = isset( $_POST['prefs'] ) ? wp_unslash( $_POST['prefs'] ) : '';
	$data = json_decode( $raw, true );

	if ( ! is_array( $data ) ) {
		wp_send_json_error( __( 'Invalid preferences payload.', 'wp-ulike' ) );
	}

	if ( ! class_exists( 'WP_Ulike_Stats_User_Prefs' ) ) {
		wp_send_json_error( __( 'Preferences storage is unavailable.', 'wp-ulike' ) );
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
		wp_send_json_error( __( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
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
		wp_send_json_error( __( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	$type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
	$data = wp_ulike_stats::get_instance()->get_engagement_api_data( $type );

	if ( null === $data ) {
		wp_send_json_error( __( 'Invalid content type.', 'wp-ulike' ) );
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
		wp_send_json_error( __( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	$type  = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
	$limit = isset( $_GET['limit'] ) ? absint( $_GET['limit'] ) : 10;
	$data  = wp_ulike_stats::get_instance()->get_tops_api_data( $type, $limit );

	if ( null === $data ) {
		wp_send_json_error( __( 'Invalid content type.', 'wp-ulike' ) );
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
		wp_send_json_error( __( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	$type    = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : 'post';
	$page    = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
	$perPage = isset( $_GET['perPage'] ) ? absint( $_GET['perPage'] ) : 20;

	$settings = wp_ulike_setting_type::get_instance( $type );
	$instance = new wp_ulike_logs( $settings->getLogIdentifier(), $page, $perPage  );
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
		wp_send_json_error( __( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	$item_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$type    = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';

	if( empty( $item_id ) || empty( $type ) ){
		wp_send_json_error( __( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}

	$settings = wp_ulike_setting_type::get_instance( $type );
	$instance = new wp_ulike_logs( $settings->getLogIdentifier()  );

	if( ! $instance->delete_row( $item_id ) ){
		wp_send_json_error( __( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
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
		wp_send_json_error( __( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
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
		'Overview'             => __( 'Overview', 'wp-ulike' ),
		'Reports'              => __( 'Reports', 'wp-ulike' ),
		'Engagement'           => __( 'Engagement', 'wp-ulike' ),
		'Intelligence'         => __( 'Intelligence', 'wp-ulike' ),
		'Content intelligence' => __( 'Content intelligence', 'wp-ulike' ),
		'Audience'             => __( 'Audience', 'wp-ulike' ),
		'Countries'         => __( 'Countries', 'wp-ulike' ),
		'Technology'           => __( 'Technology', 'wp-ulike' ),
		'Devices'            => __( 'Devices', 'wp-ulike' ),
		'Logs'                 => __( 'Logs', 'wp-ulike' ),
		'Insights'             => __( 'Insights', 'wp-ulike' ),
		'View'                 => __( 'View', 'wp-ulike' ),
		'Filters'              => __( 'Filters', 'wp-ulike' ),
		'Clear all'            => __( 'Clear all', 'wp-ulike' ),
		'Apply'                => __( 'Apply', 'wp-ulike' ),
		'Cancel'               => __( 'Cancel', 'wp-ulike' ),
		'Clear'                => __( 'Clear', 'wp-ulike' ),
		'Loading...'             => __( 'Loading...', 'wp-ulike' ),

		// Page descriptions
		'Actionable engagement intelligence for your site' => __( 'Actionable engagement intelligence for your site', 'wp-ulike' ),
		'Your engagement dashboard at a glance'            => __( 'Your engagement dashboard at a glance', 'wp-ulike' ),
		'Publishing schedule, categories, and commerce insights' => __( 'Publishing schedule, categories, and commerce insights', 'wp-ulike' ),
		'Publishing schedule and category performance' => __( 'Publishing schedule and category performance', 'wp-ulike' ),
		'Connect product engagement with sales to spot opportunities and plan campaigns' => __( 'Connect product engagement with sales to spot opportunities and plan campaigns', 'wp-ulike' ),
		'See where your audience engages from'           => __( 'See where your audience engages from', 'wp-ulike' ),
		'Audience by location'                           => __( 'Audience by location', 'wp-ulike' ),
		'Device, OS and browser breakdown by unique voters' => __( 'Device, OS and browser breakdown by unique voters', 'wp-ulike' ),
		'Voters by device & browser'                     => __( 'Voters by device & browser', 'wp-ulike' ),
		'Trends and totals for {{type}}'                 => __( 'Trends and totals for {{type}}', 'wp-ulike' ),
		'Top {{type}} your audience engages with most'   => __( 'Top {{type}} your audience engages with most', 'wp-ulike' ),
		'Browse and manage vote history'                 => __( 'Browse and manage vote history', 'wp-ulike' ),
		'Browse and manage vote history for {{type}}'    => __( 'Browse and manage vote history for {{type}}', 'wp-ulike' ),

		// KPI metrics
		'Engagement This Week'        => __( 'Engagement This Week', 'wp-ulike' ),
		'Monthly Engagement Overview' => __( 'Monthly Engagement Overview', 'wp-ulike' ),
		'Yearly Engagement Trends'    => __( 'Yearly Engagement Trends', 'wp-ulike' ),
		'Overall Performance'         => __( 'Overall Performance', 'wp-ulike' ),
		'All time'                    => __( 'All time', 'wp-ulike' ),
		'This Week'                   => __( 'This week', 'wp-ulike' ),
		'This Month'                  => __( 'This month', 'wp-ulike' ),
		'This Year'                   => __( 'This year', 'wp-ulike' ),
		'Today'                       => __( 'Today', 'wp-ulike' ),
		'Yesterday'                   => __( 'Yesterday', 'wp-ulike' ),
		'vs last week'                => __( 'vs last week', 'wp-ulike' ),
		'vs last month'               => __( 'vs last month', 'wp-ulike' ),
		'vs last year'                => __( 'vs last year', 'wp-ulike' ),
		'Total Interactions To Date'  => __( 'Total Interactions To Date', 'wp-ulike' ),
		'Engagement Summary'          => __( 'Engagement Summary', 'wp-ulike' ),
		'Totals at a glance'          => __( 'Totals at a glance', 'wp-ulike' ),
		'{{total}} total · {{today}} today' => __( '{{total}} total · {{today}} today', 'wp-ulike' ),

		// Engagement reports
		'Trends'              => __( 'Trends', 'wp-ulike' ),
		'Top content'         => __( 'Top content', 'wp-ulike' ),
		'positive'            => __( 'positive', 'wp-ulike' ),
		'Vote history · {{type}}' => __( 'Vote history · {{type}}', 'wp-ulike' ),
		'Only {{ratio}}% positive this week — check Top content for items getting dislikes.' => __( 'Only {{ratio}}% positive this week — check Top content for items getting dislikes.', 'wp-ulike' ),
		'Use Content intelligence to find the best publish times for your audience.' => __( 'Use Content intelligence to find the best publish times for your audience.', 'wp-ulike' ),
		'{{likes}} likes this week — use Top content to find what resonates.' => __( '{{likes}} likes this week — use Top content to find what resonates.', 'wp-ulike' ),
		'{{likes}} likes and {{dislikes}} dislikes — use Top content to find what resonates.' => __( '{{likes}} likes and {{dislikes}} dislikes — use Top content to find what resonates.', 'wp-ulike' ),

		// Content types (keys match capitalizeFirstLetter( type ) in Logs)
		'Posts'      => __( 'Posts', 'wp-ulike' ),
		'Comments'   => __( 'Comments', 'wp-ulike' ),
		'Activities' => __( 'Activities', 'wp-ulike' ),
		'Topics'     => __( 'Topics', 'wp-ulike' ),
		'Engagers'   => __( 'Engagers', 'wp-ulike' ),
		'Top members' => __( 'Top members', 'wp-ulike' ),
		'Most active engagers recently' => __( 'Most active engagers recently', 'wp-ulike' ),
		'Most active members recently'  => __( 'Most active members recently', 'wp-ulike' ),
		'Most active visitors recently' => __( 'Most active visitors recently', 'wp-ulike' ),
		'{{count}} actions this week — reward top engagers to build loyalty.' => __( '{{count}} actions this week — reward top engagers to build loyalty.', 'wp-ulike' ),
		'{{count}} actions this week — reward top members to build loyalty.' => __( '{{count}} actions this week — reward top members to build loyalty.', 'wp-ulike' ),
		'{{count}} actions this week — see which visitors engage most often.' => __( '{{count}} actions this week — see which visitors engage most often.', 'wp-ulike' ),

		// Filters & status
		'Like'           => __( 'Like', 'wp-ulike' ),
		'Unlike'         => __( 'Unlike', 'wp-ulike' ),
		'Dislike'        => __( 'Dislike', 'wp-ulike' ),
		'Undislike'      => __( 'Undislike', 'wp-ulike' ),
		'Status Filter'  => __( 'Status Filter', 'wp-ulike' ),
		'Status'         => __( 'Status', 'wp-ulike' ),
		'Date Range'     => __( 'Date Range', 'wp-ulike' ),
		'Start date'     => __( 'Start date', 'wp-ulike' ),
		'End date'       => __( 'End date', 'wp-ulike' ),
		'Select...'        => __( 'Select...', 'wp-ulike' ),
		'Content type'   => __( 'Content type', 'wp-ulike' ),
		'Content Types'  => __( 'Content Types', 'wp-ulike' ),
		'View By'        => __( 'View By', 'wp-ulike' ),
		'OS'             => __( 'OS', 'wp-ulike' ),
		'Browser'        => __( 'Browser', 'wp-ulike' ),
		'Post type'      => __( 'Post type', 'wp-ulike' ),
		'Taxonomy'       => __( 'Taxonomy', 'wp-ulike' ),
		'Sort by'        => __( 'Sort by', 'wp-ulike' ),
		'Highest first'  => __( 'Highest first', 'wp-ulike' ),
		'Lowest first'   => __( 'Lowest first', 'wp-ulike' ),
		'Search'         => __( 'Search', 'wp-ulike' ),
		'{{count}} selected' => __( '{{count}} selected', 'wp-ulike' ),

		// Date presets
		'Custom'                 => __( 'Custom', 'wp-ulike' ),
		'This week'              => __( 'This week', 'wp-ulike' ),
		'Last week'              => __( 'Last week', 'wp-ulike' ),
		'Last {{count}} days'    => __( 'Last {{count}} days', 'wp-ulike' ),
		'Last {{count}} months'  => __( 'Last {{count}} months', 'wp-ulike' ),
		'This month'             => __( 'This month', 'wp-ulike' ),
		'Last month'             => __( 'Last month', 'wp-ulike' ),
		'Quarter to date'        => __( 'Quarter to date', 'wp-ulike' ),
		'This year'              => __( 'This year', 'wp-ulike' ),
		'Last calendar year'     => __( 'Last calendar year', 'wp-ulike' ),

		// Tables & lists
		'Content'         => __( 'Content', 'wp-ulike' ),
		'Performance'     => __( 'Performance', 'wp-ulike' ),
		'Published'       => __( 'Published', 'wp-ulike' ),
		'Views'           => __( 'Views', 'wp-ulike' ),
		'Dislikes'        => __( 'Dislikes', 'wp-ulike' ),
		'Date'            => __( 'Date', 'wp-ulike' ),
		'User'            => __( 'User', 'wp-ulike' ),
		'IP'              => __( 'IP', 'wp-ulike' ),
		'Comment Author'  => __( 'Comment Author', 'wp-ulike' ),
		'Comment Content' => __( 'Comment Content', 'wp-ulike' ),
		'Activity Title'  => __( 'Activity Title', 'wp-ulike' ),
		'Topic Title'     => __( 'Topic Title', 'wp-ulike' ),
		'Post Title'      => __( 'Post Title', 'wp-ulike' ),
		'Categories'      => __( 'Categories', 'wp-ulike' ),
		'Category'        => __( 'Category', 'wp-ulike' ),
		'Actions'         => __( 'Actions', 'wp-ulike' ),
		'Name'            => __( 'Name', 'wp-ulike' ),
		'Share'           => __( 'Share', 'wp-ulike' ),
		'Growth'          => __( 'Growth', 'wp-ulike' ),
		'Country'         => __( 'Country', 'wp-ulike' ),
		'Device'          => __( 'Device', 'wp-ulike' ),
		'Voters'          => __( 'Voters', 'wp-ulike' ),
		'User(s)'         => __( 'User(s)', 'wp-ulike' ),
		'Engaged Users'   => __( 'Engaged Users', 'wp-ulike' ),
		'Unique users'    => __( 'Unique users', 'wp-ulike' ),
		'Selected period' => __( 'Selected period', 'wp-ulike' ),
		'Untitled'        => __( 'Untitled', 'wp-ulike' ),
		'Unknown User'    => __( 'Unknown User', 'wp-ulike' ),

		// Pagination & logs
		'Showing {{from}} to {{to}} of {{total}} results' => __( 'Showing {{from}} to {{to}} of {{total}} results', 'wp-ulike' ),
		'per page'        => __( 'per page', 'wp-ulike' ),
		'Total records'   => __( 'Total records', 'wp-ulike' ),
		'Delete Selected' => __( 'Delete Selected', 'wp-ulike' ),
		'Download CSV'    => __( 'Download CSV', 'wp-ulike' ),
		'Failed to delete the log entry.' => __( 'Failed to delete the log entry.', 'wp-ulike' ),
		'An error occurred while deleting the log entry.' => __( 'An error occurred while deleting the log entry.', 'wp-ulike' ),
		'No logs found for this category' => __( 'No logs found for this category', 'wp-ulike' ),

		// Empty & error states
		'No data for this period' => __( 'No data for this period', 'wp-ulike' ),
		'Try changing your filters or search.' => __( 'Try changing your filters or search.', 'wp-ulike' ),
		'Unable to load data. Please try again.' => __( 'Unable to load data. Please try again.', 'wp-ulike' ),
		'Something went wrong'    => __( 'Something went wrong', 'wp-ulike' ),
		'Unable to load data. Refresh the page or contact support.' => __( 'Unable to load data. Refresh the page or contact support.', 'wp-ulike' ),
		'Page Not Found'          => __( 'Page Not Found', 'wp-ulike' ),
		'This page does not exist or was moved.' => __( 'This page does not exist or was moved.', 'wp-ulike' ),
		'No Data Available'       => __( 'No Data Available', 'wp-ulike' ),
		'No data yet. Records will appear here once engagement starts.' => __( 'No data yet. Records will appear here once engagement starts.', 'wp-ulike' ),
		'Go to Home'              => __( 'Go to Home', 'wp-ulike' ),
		'Refresh Page'            => __( 'Refresh Page', 'wp-ulike' ),

		// Geography
		'User Engagement by Country' => __( 'User Engagement by Country', 'wp-ulike' ),
		'Activity by country'        => __( 'Activity by country', 'wp-ulike' ),
		'Top countries'              => __( 'Top countries', 'wp-ulike' ),
		'No country data yet'        => __( 'No country data yet', 'wp-ulike' ),
		'{{country}} is your top market with {{share}}% of engaged voters.' => __( '{{country}} is your top market with {{share}}% of engaged voters.', 'wp-ulike' ),

		// Intelligence & performance
		'Performance snapshot' => __( 'Performance snapshot', 'wp-ulike' ),
		'Engagement rate'      => __( 'Engagement rate', 'wp-ulike' ),
		'Positive sentiment'   => __( 'Positive sentiment', 'wp-ulike' ),
		'Total likes'          => __( 'Total likes', 'wp-ulike' ),
		'Engagement trend'     => __( 'Engagement trend', 'wp-ulike' ),
		'Button impressions'   => __( 'Button impressions', 'wp-ulike' ),
		'Total views'          => __( 'Total views', 'wp-ulike' ),
		'Reach'                => __( 'Reach', 'wp-ulike' ),
		'Daily activity'       => __( 'Daily activity', 'wp-ulike' ),
		'Likes this month'     => __( 'Likes this month', 'wp-ulike' ),
		'Interactions this month' => __( 'Interactions this month', 'wp-ulike' ),
		'Likes per view'       => __( 'Likes per view', 'wp-ulike' ),
		'Likes + dislikes per view' => __( 'Likes + dislikes per view', 'wp-ulike' ),
		'Engagements per view' => __( 'Engagements per view', 'wp-ulike' ),
		'Reactions per view'   => __( 'Reactions per view', 'wp-ulike' ),
		'Ratings per view'     => __( 'Ratings per view', 'wp-ulike' ),
		'Like vs dislike ratio' => __( 'Like vs dislike ratio', 'wp-ulike' ),
		'Positive vs negative engagements' => __( 'Positive vs negative engagements', 'wp-ulike' ),
		'Like-only template'   => __( 'Like-only template', 'wp-ulike' ),
		'{{likes}} likes · {{dislikes}} dislikes' => __( '{{likes}} likes · {{dislikes}} dislikes', 'wp-ulike' ),
		'{{positive}} positive · {{negative}} negative' => __( '{{positive}} positive · {{negative}} negative', 'wp-ulike' ),
		'All likes in this period.' => __( 'All likes in this period.', 'wp-ulike' ),
		'Daily counts for this period.' => __( 'Daily counts for this period.', 'wp-ulike' ),
		'Enable it in General settings to see engagement rate and button impressions.' => __( 'Enable it in General settings to see engagement rate and button impressions.', 'wp-ulike' ),
		'Enable view tracking in General settings if you have not already to see engagement rate and button impressions.' => __( 'Enable view tracking in General settings if you have not already to see engagement rate and button impressions.', 'wp-ulike' ),
		'Engagement as a share of page views.' => __( 'Engagement as a share of page views.', 'wp-ulike' ),
		'Impressions will appear as visitors view pages with your like button.' => __( 'Impressions will appear as visitors view pages with your like button.', 'wp-ulike' ),
		'How page views convert to engagement.' => __( 'How page views convert to engagement.', 'wp-ulike' ),
		'No button impressions in this period yet.' => __( 'No button impressions in this period yet.', 'wp-ulike' ),
		'Likes as a share of all reactions.' => __( 'Likes as a share of all reactions.', 'wp-ulike' ),
		'Positive share of polarized engagements (votes, reactions, ratings).' => __( 'Positive share of polarized engagements (votes, reactions, ratings).', 'wp-ulike' ),
		'{{likes}} of {{total}} reactions' => __( '{{likes}} of {{total}} reactions', 'wp-ulike' ),
		'Change compared to the previous period.' => __( 'Change compared to the previous period.', 'wp-ulike' ),
		'{{rate}}% engagement rate' => __( '{{rate}}% engagement rate', 'wp-ulike' ),
		'How often your like button was shown. Compare with engagements.' => __( 'How often your like button was shown. Compare with engagements.', 'wp-ulike' ),
		'Reaction and voter metrics.' => __( 'Reaction and voter metrics.', 'wp-ulike' ),
		'View tracking is off.' => __( 'View tracking is off.', 'wp-ulike' ),

		// Content intelligence report
		'When to publish'     => __( 'When to publish', 'wp-ulike' ),
		'When your audience is most likely to react.' => __( 'When your audience is most likely to react.', 'wp-ulike' ),
		'Sweet spot'          => __( 'Sweet spot', 'wp-ulike' ),
		'Peak'                => __( 'Peak', 'wp-ulike' ),
		'Hourly pattern'      => __( 'Hourly pattern', 'wp-ulike' ),
		'Full report'         => __( 'Full report', 'wp-ulike' ),
		'{{day}} · {{time}}'  => __( '{{day}} · {{time}}', 'wp-ulike' ),
		'{{share}}% of weekly activity' => __( '{{share}}% of weekly activity', 'wp-ulike' ),
		'{{share}}% of all activity · {{range}}' => __( '{{share}}% of all activity · {{range}}', 'wp-ulike' ),
		'{{count}} engagements in this slot' => __( '{{count}} engagements in this slot', 'wp-ulike' ),
		'Activity heatmap'    => __( 'Activity heatmap', 'wp-ulike' ),
		'Engagement by day and hour' => __( 'Engagement by day and hour', 'wp-ulike' ),
		'When your audience engages' => __( 'When your audience engages', 'wp-ulike' ),
		'Time windows'        => __( 'Time windows', 'wp-ulike' ),
		'Best day(s)'         => __( 'Best day(s)', 'wp-ulike' ),
		'Share of weekly activity' => __( 'Share of weekly activity', 'wp-ulike' ),
		'Best hour'           => __( 'Best hour', 'wp-ulike' ),
		'Best hours to post'  => __( 'Best hours to post', 'wp-ulike' ),
		'Category performance' => __( 'Category performance', 'wp-ulike' ),
		'Engagements'         => __( 'Engagements', 'wp-ulike' ),
		'Hour'                => __( 'hour', 'wp-ulike' ),
		'Top categories'      => __( 'Top categories', 'wp-ulike' ),
		'Shop spotlight'      => __( 'Shop spotlight', 'wp-ulike' ),
		'Less'                => __( 'Less', 'wp-ulike' ),
		'More'                => __( 'More', 'wp-ulike' ),
		'Publish on {{day}} around {{time}} for maximum engagement.' => __( 'Publish on {{day}} around {{time}} for maximum engagement.', 'wp-ulike' ),
		'Your audience is most active in the {{window}} window ({{range}}).' => __( 'Your audience is most active in the {{window}} window ({{range}}).', 'wp-ulike' ),
		'{{share}}% of engagers use mobile — optimize for small screens.' => __( '{{share}}% of engagers use mobile — optimize for small screens.', 'wp-ulike' ),
		'{{share}}% engage from desktop — long-form content may perform better.' => __( '{{share}}% engage from desktop — long-form content may perform better.', 'wp-ulike' ),

		// WooCommerce intelligence report
		'WooCommerce' => __( 'WooCommerce', 'wp-ulike' ),
		'WooCommerce report unavailable' => __( 'WooCommerce report unavailable', 'wp-ulike' ),
		'Enable likes on products or reviews, or wait for store orders, to unlock commerce intelligence.' => __( 'Enable likes on products or reviews, or wait for store orders, to unlock commerce intelligence.', 'wp-ulike' ),
		'Store snapshot' => __( 'Store snapshot', 'wp-ulike' ),
		'Engagement correlated with sales' => __( 'Engagement correlated with sales', 'wp-ulike' ),
		'Unique view: see whether shopper reactions align with orders and revenue.' => __( 'Unique view: see whether shopper reactions align with orders and revenue.', 'wp-ulike' ),
		'Product likes' => __( 'Product likes', 'wp-ulike' ),
		'Likes and dislikes on WooCommerce products.' => __( 'Likes and dislikes on WooCommerce products.', 'wp-ulike' ),
		'Product engagement' => __( 'Product engagement', 'wp-ulike' ),
		'Reactions on verified customer product reviews.' => __( 'Reactions on verified customer product reviews.', 'wp-ulike' ),
		'Review engagement' => __( 'Review engagement', 'wp-ulike' ),
		'Completed and processing orders.' => __( 'Completed and processing orders.', 'wp-ulike' ),
		'Orders' => __( 'Orders', 'wp-ulike' ),
		'Net product revenue in this period.' => __( 'Net product revenue in this period.', 'wp-ulike' ),
		'Revenue' => __( 'Revenue', 'wp-ulike' ),
		'Product units sold in this period.' => __( 'Product units sold in this period.', 'wp-ulike' ),
		'Units sold' => __( 'Units sold', 'wp-ulike' ),
		'Average revenue per order in this period.' => __( 'Average revenue per order in this period.', 'wp-ulike' ),
		'Average order value' => __( 'Average order value', 'wp-ulike' ),
		'Average product and review reactions per order — a pre-purchase interest signal.' => __( 'Average product and review reactions per order — a pre-purchase interest signal.', 'wp-ulike' ),
		'Engagement per order' => __( 'Engagement per order', 'wp-ulike' ),
		'Revenue generated per like or dislike — helps compare campaign ROI.' => __( 'Revenue generated per like or dislike — helps compare campaign ROI.', 'wp-ulike' ),
		'Revenue per engagement' => __( 'Revenue per engagement', 'wp-ulike' ),
		'Engagement vs orders trend' => __( 'Engagement vs orders trend', 'wp-ulike' ),
		'Daily product reactions compared with store orders' => __( 'Daily product reactions compared with store orders', 'wp-ulike' ),
		'Look for days when engagement rises before orders — a sign your social proof is working.' => __( 'Look for days when engagement rises before orders — a sign your social proof is working.', 'wp-ulike' ),
		'Top products' => __( 'Top products', 'wp-ulike' ),
		'Engagement and sales side by side' => __( 'Engagement and sales side by side', 'wp-ulike' ),
		'No product engagement yet' => __( 'No product engagement yet', 'wp-ulike' ),
		'Product' => __( 'Product', 'wp-ulike' ),
		'Match score' => __( 'Match score', 'wp-ulike' ),
		'Opportunities' => __( 'Opportunities', 'wp-ulike' ),
		'Where engagement and sales diverge' => __( 'Where engagement and sales diverge', 'wp-ulike' ),
		'High interest, lower sales — optimize merchandising, pricing, or checkout.' => __( 'High interest, lower sales — optimize merchandising, pricing, or checkout.', 'wp-ulike' ),
		'Strong sellers with few likes — surface social proof with like buttons or badges.' => __( 'Strong sellers with few likes — surface social proof with like buttons or badges.', 'wp-ulike' ),
		'Engagement and revenue by product category' => __( 'Engagement and revenue by product category', 'wp-ulike' ),
		'Engagement vs sales intelligence' => __( 'Engagement vs sales intelligence', 'wp-ulike' ),
		'See how product likes and review reactions relate to orders and revenue — available in Pro.' => __( 'See how product likes and review reactions relate to orders and revenue — available in Pro.', 'wp-ulike' ),

		// Growth tips (overview)
		'Actionable recommendations based on your data' => __( 'Actionable recommendations based on your data', 'wp-ulike' ),
		'Best time to publish' => __( 'Best time to publish', 'wp-ulike' ),
		'{{day}} around {{time}} gets the most engagement — schedule content then.' => __( '{{day}} around {{time}} gets the most engagement — schedule content then.', 'wp-ulike' ),
		'{{category}} drives {{share}}% of engagement — create more on this topic.' => __( '{{category}} drives {{share}}% of engagement — create more on this topic.', 'wp-ulike' ),
		'Low conversion' => __( 'Low conversion', 'wp-ulike' ),
		'Only {{rate}}% of viewers engage — improve button placement and CTAs.' => __( 'Only {{rate}}% of viewers engage — improve button placement and CTAs.', 'wp-ulike' ),
		'Momentum' => __( 'Momentum', 'wp-ulike' ),
		'Sentiment drop' => __( 'Sentiment drop', 'wp-ulike' ),
		'Positive reactions fell to {{ratio}}% — review content getting dislikes.' => __( 'Positive reactions fell to {{ratio}}% — review content getting dislikes.', 'wp-ulike' ),

		// Top content insights
		'{{title}} leads with {{count}} likes in this period.' => __( '{{title}} leads with {{count}} likes in this period.', 'wp-ulike' ),
		'{{title}} converts best at {{rate}}% — replicate this format.' => __( '{{title}} converts best at {{rate}}% — replicate this format.', 'wp-ulike' ),
		'{{name}} is your most active user — consider a loyalty perk.' => __( '{{name}} is your most active user — consider a loyalty perk.', 'wp-ulike' ),

		// Header & metrics
		'Refresh data' => __( 'Refresh data', 'wp-ulike' ),
		'Total engagements' => __( 'Total engagements', 'wp-ulike' ),
		'Distinct people who engaged in this period.' => __( 'Distinct people who engaged in this period.', 'wp-ulike' ),
		'Current period' => __( 'Current period', 'wp-ulike' ),
		'Previous period' => __( 'Previous period', 'wp-ulike' ),
		'Likes'          => __( 'Likes', 'wp-ulike' ),
		'Interactions'   => __( 'Interactions', 'wp-ulike' ),
		'Impressions'    => __( 'Impressions', 'wp-ulike' ),

		// Notifications
		'Dismiss'             => __( 'Dismiss', 'wp-ulike' ),
		'New Notifications'   => __( 'New Notifications', 'wp-ulike' ),
		'No new notifications' => __( 'No new notifications', 'wp-ulike' ),
		'{{current}} of {{total}}' => __( '{{current}} of {{total}}', 'wp-ulike' ),

		// License
		'License Not Found!' => __( 'License Not Found!', 'wp-ulike' ),
		'Your license is invalid or missing. Enter a valid key or purchase Pro to continue.' => __( 'Your license is invalid or missing. Enter a valid key or purchase Pro to continue.', 'wp-ulike' ),
		'Get License'        => __( 'Get License', 'wp-ulike' ),

		// Pro preview, sidebar & free shell
		'Upgrade to Pro' => __( 'Upgrade to Pro', 'wp-ulike' ),
		'Learn more'     => __( 'Learn more', 'wp-ulike' ),
		'Pro'            => __( 'Pro', 'wp-ulike' ),
		'Settings'       => __( 'Settings', 'wp-ulike' ),
		'Help'           => __( 'Help', 'wp-ulike' ),
		'Get Pro'        => __( 'Get Pro', 'wp-ulike' ),
		'Get Pro reports' => __( 'Get Pro reports', 'wp-ulike' ),
		'Unlock live audience maps, top fans, and publishing insights with Pro.' => __( 'Unlock live audience maps, top fans, and publishing insights with Pro.', 'wp-ulike' ),
		"See who's engaging most" => __( "See who's engaging most", 'wp-ulike' ),
		'{{count}} engagements so far — unlock audience maps, top fans, and publishing insights.' => __( '{{count}} engagements so far — unlock audience maps, top fans, and publishing insights.', 'wp-ulike' ),
		'Minimize Pro promo' => __( 'Minimize Pro promo', 'wp-ulike' ),
		'Find your best posting times and top-performing topics' => __( 'Find your best posting times and top-performing topics', 'wp-ulike' ),
		'Learn which devices and browsers your voters use' => __( 'Learn which devices and browsers your voters use', 'wp-ulike' ),
		'Sidebar Pro card' => __( 'Sidebar Pro card', 'wp-ulike' ),
		'Minimized to “Get Pro” in the sidebar.' => __( 'Minimized to “Get Pro” in the sidebar.', 'wp-ulike' ),
		'Full card visible in the sidebar.' => __( 'Full card visible in the sidebar.', 'wp-ulike' ),
		'Show full card' => __( 'Show full card', 'wp-ulike' ),
		'Milestone'      => __( 'Milestone', 'wp-ulike' ),
		'You have passed {{count}} total engagements — {{remaining}} to go until {{next}}.' => __( 'You have passed {{count}} total engagements — {{remaining}} to go until {{next}}.', 'wp-ulike' ),
		'Your community has reached {{count}} total engagements!' => __( 'Your community has reached {{count}} total engagements!', 'wp-ulike' ),
		'Total'          => __( 'Total', 'wp-ulike' ),
		'Engagement is up {{percent}}% compared to last week.' => __( 'Engagement is up {{percent}}% compared to last week.', 'wp-ulike' ),
		'Engagement is down {{percent}}% compared to last week.' => __( 'Engagement is down {{percent}}% compared to last week.', 'wp-ulike' ),
		'Getting started' => __( 'Getting started', 'wp-ulike' ),
		'{{count}} engagements so far — {{remaining}} more to reach {{target}}.' => __( '{{count}} engagements so far — {{remaining}} more to reach {{target}}.', 'wp-ulike' ),
		'{{count}} engagements today — keep it going.' => __( '{{count}} engagements today — keep it going.', 'wp-ulike' ),
		'No engagements yet today — yesterday had {{count}}.' => __( 'No engagements yet today — yesterday had {{count}}.', 'wp-ulike' ),
		'Engagement is up {{percent}}% compared to yesterday.' => __( 'Engagement is up {{percent}}% compared to yesterday.', 'wp-ulike' ),
		'Engagement is down {{percent}}% compared to yesterday.' => __( 'Engagement is down {{percent}}% compared to yesterday.', 'wp-ulike' ),
		'Around {{time}} gets the most engagement — schedule content then.' => __( 'Around {{time}} gets the most engagement — schedule content then.', 'wp-ulike' ),
		'A quick look at where your audience engages from.' => __( 'A quick look at where your audience engages from.', 'wp-ulike' ),
		'See full map in Pro' => __( 'See full map in Pro', 'wp-ulike' ),
		'Explore'        => __( 'Explore', 'wp-ulike' ),
		'Preferences for your stats dashboard experience.' => __( 'Preferences for your stats dashboard experience.', 'wp-ulike' ),
		'Announcements'  => __( 'Announcements', 'wp-ulike' ),
		'Show announcement modals' => __( 'Show announcement modals', 'wp-ulike' ),
		'Display popup announcements when you open the stats dashboard. Multiple announcements are shown one at a time.' => __( 'Display popup announcements when you open the stats dashboard. Multiple announcements are shown one at a time.', 'wp-ulike' ),
		'Dismissed announcements' => __( 'Dismissed announcements', 'wp-ulike' ),
		'No dismissed announcements.' => __( 'No dismissed announcements.', 'wp-ulike' ),
		'{{count}} dismissed announcement(s) stored for your account.' => __( '{{count}} dismissed announcement(s) stored for your account.', 'wp-ulike' ),
		'Clear dismissed' => __( 'Clear dismissed', 'wp-ulike' ),
		'Dismissals were cleared. Popup announcements will appear again the next time you open the stats dashboard.' => __( 'Dismissals were cleared. Popup announcements will appear again the next time you open the stats dashboard.', 'wp-ulike' ),
		'Plugin configuration' => __( 'Plugin configuration', 'wp-ulike' ),
		'Open plugin settings' => __( 'Open plugin settings', 'wp-ulike' ),
		'Help & documentation' => __( 'Help & documentation', 'wp-ulike' ),
		'Appearance'     => __( 'Appearance', 'wp-ulike' ),
		'Theme preference is stored locally in this browser, not in your WordPress user account.' => __( 'Theme preference is stored locally in this browser, not in your WordPress user account.', 'wp-ulike' ),
		'Publishing schedule, category insights, and commerce analytics — available in Pro.' => __( 'Publishing schedule, category insights, and commerce analytics — available in Pro.', 'wp-ulike' ),
		'Publishing schedule and category insights — available in Pro.' => __( 'Publishing schedule and category insights — available in Pro.', 'wp-ulike' ),
		'See where your audience engages from with country breakdowns and trends.' => __( 'See where your audience engages from with country breakdowns and trends.', 'wp-ulike' ),
		'Device, OS, and browser breakdowns for every vote.' => __( 'Device, OS, and browser breakdowns for every vote.', 'wp-ulike' ),
		'Discover your most active members and reward loyal engagers.' => __( 'Discover your most active members and reward loyal engagers.', 'wp-ulike' ),
		'See exactly who engaged with each piece of content.' => __( 'See exactly who engaged with each piece of content.', 'wp-ulike' ),
		'Users who engaged with this content' => __( 'Users who engaged with this content', 'wp-ulike' ),
		'Member'         => __( 'Member', 'wp-ulike' ),
		'Last active'    => __( 'Last active', 'wp-ulike' ),
		'Reactions'      => __( 'Reactions', 'wp-ulike' ),
		'Back to engagement' => __( 'Back to engagement', 'wp-ulike' ),
		'No registered engagers yet' => __( 'No registered engagers yet', 'wp-ulike' ),
		'No registered engagers for {{title}}' => __( 'No registered engagers for {{title}}', 'wp-ulike' ),
		'Guest votes are counted in totals but only registered members appear here.' => __( 'Guest votes are counted in totals but only registered members appear here.', 'wp-ulike' ),
		'Like / Dislike Buttons' => __( 'Like / Dislike Buttons', 'wp-ulike' ),
		'{{count}} engaged users' => __( '{{count}} engaged users', 'wp-ulike' ),
		'{{count}} users engaged with {{title}}' => __( '{{count}} users engaged with {{title}}', 'wp-ulike' ),
		'{{count}} engager(s)' => __( '{{count}} engager(s)', 'wp-ulike' ),
		'No engagers'    => __( 'No engagers', 'wp-ulike' ),
		'No engagers yet' => __( 'No engagers yet', 'wp-ulike' ),
		'{{count}} total' => __( '{{count}} total', 'wp-ulike' ),
		'Totals and charts for {{type}}' => __( 'Totals and charts for {{type}}', 'wp-ulike' ),
		'Trends and top content for {{type}}' => __( 'Trends and top content for {{type}}', 'wp-ulike' ),
		'Morning'        => __( 'Morning', 'wp-ulike' ),
		'Afternoon'      => __( 'Afternoon', 'wp-ulike' ),
		'Evening'        => __( 'Evening', 'wp-ulike' ),
		'Night'          => __( 'Night', 'wp-ulike' ),

		// Engagement modes & engagers UI
		'Close'          => __( 'Close', 'wp-ulike' ),
		'See who'        => __( 'See who', 'wp-ulike' ),
		'Ratings'        => __( 'Ratings', 'wp-ulike' ),
		'Average rating' => __( 'Average rating', 'wp-ulike' ),
		'Star Rating'    => __( 'Star Rating', 'wp-ulike' ),
		'Star rating template' => __( 'Star rating template', 'wp-ulike' ),
		'Star ratings in this period.' => __( 'Star ratings in this period.', 'wp-ulike' ),
		'Emoji Reactions' => __( 'Emoji Reactions', 'wp-ulike' ),
		'Emoji template'  => __( 'Emoji template', 'wp-ulike' ),
		'Emoji reactions in this period.' => __( 'Emoji reactions in this period.', 'wp-ulike' ),
		'Reactions are up {{percent}}% compared to last week.' => __( 'Reactions are up {{percent}}% compared to last week.', 'wp-ulike' ),
		'Ratings are up {{percent}}% compared to last week.' => __( 'Ratings are up {{percent}}% compared to last week.', 'wp-ulike' ),
		'{{count}} emoji reactions this week — use Top content to find what resonates.' => __( '{{count}} emoji reactions this week — use Top content to find what resonates.', 'wp-ulike' ),
		'{{count}} star ratings this week — use Top content to find what resonates.' => __( '{{count}} star ratings this week — use Top content to find what resonates.', 'wp-ulike' ),
		'{{name}} is your top rater — consider a loyalty perk.' => __( '{{name}} is your top rater — consider a loyalty perk.', 'wp-ulike' ),
		'{{title}} averages ★ {{avg}} — replicate this format.' => __( '{{title}} averages ★ {{avg}} — replicate this format.', 'wp-ulike' ),
		'{{title}} leads with {{count}} reactions in this period.' => __( '{{title}} leads with {{count}} reactions in this period.', 'wp-ulike' ),
		'★ {{avg}} site average' => __( '★ {{avg}} site average', 'wp-ulike' ),
		'Device Insights' => __( 'Device Insights', 'wp-ulike' ),
		'Intelligence Report' => __( 'Intelligence Report', 'wp-ulike' ),
		'WooCommerce Report' => __( 'WooCommerce Report', 'wp-ulike' ),
		'Upgrade like storage' => 'Upgrade like storage',
		'Unable to load data' => __( 'Unable to load data', 'wp-ulike' ),
		'Please refresh the page or try again later.' => __( 'Please refresh the page or try again later.', 'wp-ulike' ),

		// Time ago
		'timeAgo'       => __( '{{count}} {{interval}} ago', 'wp-ulike' ),
		'year'          => __( 'year', 'wp-ulike' ),
		'year_plural'   => __( 'years', 'wp-ulike' ),
		'month'         => __( 'month', 'wp-ulike' ),
		'month_plural'  => __( 'months', 'wp-ulike' ),
		'week'          => __( 'week', 'wp-ulike' ),
		'week_plural'   => __( 'weeks', 'wp-ulike' ),
		'day'           => __( 'day', 'wp-ulike' ),
		'day_plural'    => __( 'days', 'wp-ulike' ),
		'hour'          => __( 'hour', 'wp-ulike' ),
		'hour_plural'   => __( 'hours', 'wp-ulike' ),
		'minute'        => __( 'minute', 'wp-ulike' ),
		'minute_plural' => __( 'minutes', 'wp-ulike' ),
		'second'        => __( 'second', 'wp-ulike' ),
		'second_plural' => __( 'seconds', 'wp-ulike' ),
		'just now'      => __( 'just now', 'wp-ulike' ),
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
		wp_send_json_error( __( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
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
		wp_send_json_error( __( 'Error: API not available.', 'wp-ulike' ) );
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
		wp_send_json_error( __( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
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
		wp_send_json_error( __( 'Error: API not available.', 'wp-ulike' ) );
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
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Error: You do not have permission to save settings.', 'wp-ulike' ) );
	}
	if ( ! wp_ulike_is_valid_nonce( WP_ULIKE_SLUG ) ) {
		wp_send_json_error( __( 'Your session expired. Please refresh the page and try saving again.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	$max_body = defined( 'MB_IN_BYTES' ) ? 2 * MB_IN_BYTES : 2097152;
	$json     = wp_ulike_read_php_input_capped( $max_body );
	if ( is_wp_error( $json ) ) {
		wp_send_json_error( $json->get_error_message() );
	}

	if ( '' === trim( (string) $json ) ) {
		wp_send_json_error( __( 'No settings data was received. Please refresh the page and try again.', 'wp-ulike' ) );
	}

	$values = json_decode( $json, true );

	if ( ! is_array( $values ) ) {
		$json_error = function_exists( 'json_last_error_msg' ) ? json_last_error_msg() : '';
		wp_send_json_error(
			'' !== $json_error
				? sprintf(
					/* translators: %s: JSON parser error message */
					__( 'Could not read settings data (%s). Please refresh the page and try again.', 'wp-ulike' ),
					esc_html( $json_error )
				)
				: __( 'Invalid request data.', 'wp-ulike' )
		);
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
		wp_send_json_error( __( 'Error: API not available.', 'wp-ulike' ) );
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
		wp_send_json_error( __( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
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
		wp_send_json_error( __( 'Error: API not available.', 'wp-ulike' ) );
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
		wp_send_json_error( __( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
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
		wp_send_json_error( __( 'Error: API not available.', 'wp-ulike' ) );
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
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Error: You do not have permission to save settings.', 'wp-ulike' ) );
	}
	if ( ! wp_ulike_is_valid_nonce( WP_ULIKE_SLUG ) ) {
		wp_send_json_error( __( 'Your session expired. Please refresh the page and try saving again.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	$max_body = defined( 'MB_IN_BYTES' ) ? 2 * MB_IN_BYTES : 2097152;
	$json     = wp_ulike_read_php_input_capped( $max_body );
	if ( is_wp_error( $json ) ) {
		wp_send_json_error( $json->get_error_message() );
	}

	if ( '' === trim( (string) $json ) ) {
		wp_send_json_error( __( 'No settings data was received. Please refresh the page and try again.', 'wp-ulike' ) );
	}

	$values = json_decode( $json, true );

	if ( ! is_array( $values ) ) {
		$json_error = function_exists( 'json_last_error_msg' ) ? json_last_error_msg() : '';
		wp_send_json_error(
			'' !== $json_error
				? sprintf(
					/* translators: %s: JSON parser error message */
					__( 'Could not read settings data (%s). Please refresh the page and try again.', 'wp-ulike' ),
					esc_html( $json_error )
				)
				: __( 'Invalid request data.', 'wp-ulike' )
		);
	}

	// Get customizer API instance
	if ( class_exists( 'wp_ulike_customizer_api' ) ) {
		$customizer_api = new wp_ulike_customizer_api();
		$customizer_api->save_values( $values );
	} else {
		wp_send_json_error( __( 'Error: API not available.', 'wp-ulike' ) );
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
		wp_send_json_error( __( 'Error: You do not have permission to do that.', 'wp-ulike' ) );
	}
	// @if DEV
	*/
	// @endif

	// Get customizer API instance
	if ( class_exists( 'wp_ulike_customizer_api' ) ) {
		$customizer_api = new wp_ulike_customizer_api();
		$customizer_api->get_preview( null );
	} else {
		wp_send_json_error( __( 'Error: API not available.', 'wp-ulike' ) );
	}
}
add_action('wp_ajax_wp_ulike_customizer_preview_api','wp_ulike_customizer_preview_api');
// @if DEV
add_action('wp_ajax_nopriv_wp_ulike_customizer_preview_api', 'wp_ulike_customizer_preview_api');
// @endif

