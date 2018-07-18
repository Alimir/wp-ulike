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
 * @return			Integer
 */
function wp_ulike_logs_return_per_page(){
	$user     = get_current_user_id();
	$screen   = get_current_screen();
	$option   = $screen->get_option( 'per_page', 'option' );
	$per_page = get_user_meta( $user, $option, true );

	return ( empty( $per_page ) || $per_page < 1 ) ? 30 : $per_page;
}

/**
 * Get paginated logs datasets
 *
 * @author       	Alimir
 * @since           3.5
 * @return			Array
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
 * @return			String
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