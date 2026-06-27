<?php
/**
 * Privacy (personal data export / erase) for WP ULike log tables.
 *
 * @package WP_ULike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log tables that store a WordPress user id in the user_id column.
 *
 * @return array<string, string> table_suffix => human label
 */
function wp_ulike_privacy_log_tables() {
	$labels = array(
		'ulike'            => __( 'Posts', 'wp-ulike' ),
		'ulike_comments'   => __( 'Comments', 'wp-ulike' ),
		'ulike_activities' => __( 'Activities', 'wp-ulike' ),
		'ulike_forums'     => __( 'Topics', 'wp-ulike' ),
	);

	global $wpdb;
	$tables = array();

	foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $source ) {
		$suffix            = str_replace( $wpdb->prefix, '', $source['table'] );
		$tables[ $suffix ] = isset( $labels[ $suffix ] ) ? $labels[ $suffix ] : $suffix;
	}

	return $tables;
}

/**
 * @param string $email_address User email.
 * @param int    $page          Page number (1-based).
 * @return array{data: array<int, array>, done: bool}
 */
function wp_ulike_privacy_exporter( $email_address, $page = 1 ) {
	$email_address = trim( $email_address );
	$page          = max( 1, (int) $page );

	$user = get_user_by( 'email', $email_address );
	if ( ! $user instanceof WP_User ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$uid    = (string) (int) $user->ID;
	$per_page = 100;
	$data     = array();
	$labels   = wp_ulike_privacy_log_tables();

	if ( wp_ulike_use_pulse_queries() ) {
		$rows = WP_Ulike_Pulse_Log_Bridge::get_privacy_rows( $uid, $page, $per_page );
	} else {
		global $wpdb;

		$offset       = ( $page - 1 ) * $per_page;
		$union_parts  = array();
		$prepare_args = array();

		foreach ( WP_Ulike_Pulse_Registry::legacy_sources() as $source ) {
			$suffix         = str_replace( $wpdb->prefix, '', $source['table'] );
			$table          = esc_sql( $source['table'] );
			$union_parts[]  = "(SELECT '{$suffix}' AS src, id, date_time, status, ip FROM `{$table}` WHERE user_id = %s)";
			$prepare_args[] = $uid;
		}

		if ( empty( $union_parts ) ) {
			$rows = array();
		} else {
			$sql            = 'SELECT * FROM ( ' . implode( ' UNION ALL ', $union_parts ) . ' ) AS combined ORDER BY date_time DESC, src ASC, id DESC LIMIT %d OFFSET %d';
			$prepare_args[] = $per_page;
			$prepare_args[] = $offset;
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table names from plugin registry.
			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $prepare_args ), ARRAY_A );
		}
	}

	if ( ! empty( $rows ) ) {
		foreach ( $rows as $row ) {
			$src   = isset( $row['src'] ) ? $row['src'] : '';
			$label = isset( $labels[ $src ] ) ? $labels[ $src ] : __( 'Logs', 'wp-ulike' );
			$data[] = array(
				'group_id'    => 'wp-ulike',
				'group_label' => __( 'WP ULike', 'wp-ulike' ),
				'item_id'     => $src . '-' . (int) $row['id'],
				'data'        => array(
					array(
						'name'  => $label,
						'value' => sprintf(
							/* translators: 1: datetime, 2: status, 3: IP */
							__( 'Date: %1$s, Status: %2$s, IP: %3$s', 'wp-ulike' ),
							isset( $row['date_time'] ) ? $row['date_time'] : '',
							isset( $row['status'] ) ? $row['status'] : '',
							isset( $row['ip'] ) ? $row['ip'] : ''
						),
					),
				),
			);
		}
	}

	$has_more = is_array( $rows ) && count( $rows ) === $per_page;

	return array(
		'data' => $data,
		'done' => ! $has_more,
	);
}

/**
 * @param string $email_address User email.
 * @param int    $page          Page (unused; single pass).
 * @return array{items_removed: bool, items_retained: bool, messages: string[], done: bool}
 */
function wp_ulike_privacy_eraser( $email_address, $page = 1 ) {
	$email_address = trim( $email_address );
	$user          = get_user_by( 'email', $email_address );

	if ( ! $user instanceof WP_User ) {
		return array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}

	$uid    = (string) (int) $user->ID;
	$total  = 0;

	if ( wp_ulike_use_pulse_queries() ) {
		$total = WP_Ulike_Pulse_Log_Bridge::erase_user_logs( $uid );
	} else {
		global $wpdb;

		foreach ( array_keys( wp_ulike_privacy_log_tables() ) as $suffix ) {
			$table = $wpdb->prefix . $suffix;
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from fixed list.
			$result = $wpdb->query( $wpdb->prepare( "DELETE FROM `{$table}` WHERE user_id = %s", $uid ) );
			if ( false !== $result ) {
				$total += (int) $result;
			}
		}
	}

	$messages = array();
	if ( $total > 0 ) {
		$messages[] = sprintf(
			/* translators: %d: number of rows removed */
			__( 'Removed %d WP ULike log row(s) for this user.', 'wp-ulike' ),
			$total
		);
	}

	return array(
		'items_removed'  => $total > 0,
		'items_retained' => false,
		'messages'       => $messages,
		'done'           => true,
	);
}

/**
 * @param array<string, array> $exporters Registered exporters.
 * @return array<string, array>
 */
function wp_ulike_privacy_register_exporters( $exporters ) {
	$exporters['wp-ulike'] = array(
		'exporter_friendly_name' => __( 'WP ULike', 'wp-ulike' ),
		'callback'               => 'wp_ulike_privacy_exporter',
	);
	return $exporters;
}

/**
 * @param array<string, array> $erasers Registered erasers.
 * @return array<string, array>
 */
function wp_ulike_privacy_register_erasers( $erasers ) {
	$erasers['wp-ulike'] = array(
		'eraser_friendly_name' => __( 'WP ULike vote logs', 'wp-ulike' ),
		'callback'             => 'wp_ulike_privacy_eraser',
	);
	return $erasers;
}

add_filter( 'wp_privacy_personal_data_exporters', 'wp_ulike_privacy_register_exporters' );
add_filter( 'wp_privacy_personal_data_erasers', 'wp_ulike_privacy_register_erasers' );
