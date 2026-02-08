<?php
/**
 * Class for logs process
 * // @echo HEADER
 */

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'wp_ulike_logs' ) ) {

	class wp_ulike_logs{
		// Private variables
		private $wpdb, $table, $page, $per_page, $sort;

		/**
		 * Constructor
		 */
		function __construct( $table, $page = 1, $per_page = 15, $sort = array(
			'type'  => 'DESC',
			'field' => 'id'
		) ){
			global $wpdb;
			$this->wpdb     = $wpdb;
			$this->table    = $table;
			$this->page     = $page;
			$this->per_page = $per_page;
			$this->sort     = $sort;
		}

		/**
		 * get SQL results
		 *
		 * @return object
		 */
		public function get_results(){
			$table = esc_sql( $this->wpdb->prefix . $this->table );
			$paged = absint( ( $this->page - 1 ) * $this->per_page );
			$per_page = absint( $this->per_page );
			
			// Whitelist allowed order fields
			$allowed_fields = array( 'id', 'date_time', 'user_id', 'ip', 'status' );
			$orderBy = in_array( $this->sort['field'], $allowed_fields, true ) ? esc_sql( $this->sort['field'] ) : 'id';
			$orderType = strtoupper( $this->sort['type'] ) === 'ASC' ? 'ASC' : 'DESC';

			return $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM `{$table}` ORDER BY `{$orderBy}` {$orderType} LIMIT %d, %d", $paged, $per_page ) );
		}

		/**
		 * get SQL row
		 *
		 * @return object
		 */
		public function get_row( $item_ID ){
			$table = esc_sql( $this->wpdb->prefix . $this->table );
			$item_ID = absint( $item_ID );

			return $this->wpdb->get_row( $this->wpdb->prepare( "
				SELECT *
				FROM `{$table}`
				WHERE `id` = %d",
				$item_ID
			) );
		}

		/**
		 * get all SQL results
		 *
		 * @return object
		 */
		public function get_all_rows(){
			$table = esc_sql( $this->wpdb->prefix . $this->table );
			
			// Whitelist allowed order fields
			$allowed_fields = array( 'id', 'date_time', 'user_id', 'ip', 'status' );
			$orderBy = in_array( $this->sort['field'], $allowed_fields, true ) ? esc_sql( $this->sort['field'] ) : 'id';
			$orderType = strtoupper( $this->sort['type'] ) === 'ASC' ? 'ASC' : 'DESC';

			return $this->wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY `{$orderBy}` {$orderType}" );
		}

		/**
		 * Delete selected rows
		 *
		 * @param array $items
		 * @return void
		 */
		public function delete_rows( $items ){
			$table = esc_sql( $this->wpdb->prefix . $this->table );
			$selectedIds = array();

			foreach ($items as $key => $item) {
				if( ! empty( $item['id'] ) ){
					$selectedIds[] = absint( $item['id'] );
				}
			}
			if( ! empty( $selectedIds ) ){
				$placeholders = implode( ',', array_fill( 0, count( $selectedIds ), '%d' ) );
				$query = "DELETE FROM `{$table}` WHERE ID IN({$placeholders})";
				$this->wpdb->query( $this->wpdb->prepare( $query, $selectedIds ) );
			}
		}

		/**
		 * Delete single row
		 *
		 * @param integer $item_id
		 * @return integer|false
		 */
		public function delete_row( $item_id ){
			$table   = $this->wpdb->prefix . $this->table;
			$item_id = absint( $item_id );

			return $this->wpdb->delete(
				$table,
				array( 'ID' => $item_id ),
				array( '%d' )
			);
		}

		/**
		 * Get total rows per table
		 *
		 * @return string
		 */
		private function get_total_records(){
			$table  = esc_sql( $this->wpdb->prefix . $this->table );
			return $this->wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
		}

		/**
		 * Get response in JSON
		 *
		 * @return array
		 */
		public function get_rows(){
			$records = $this->get_trnasformed_rows();
			return array(
				'rows'         => $records,
				'totalRecords' => $this->get_total_records()
			);
		}

		/**
		 * get transformed rows
		 *
		 * @return object
		 */
		private function get_trnasformed_rows(){
			$dataset = $this->get_results();
			return $this->get_formatted_data( $dataset );
		}

		/**
		 * formate inputted dataset
		 *
		 * @param array $dataset
		 * @return array
		 */
		private function get_formatted_data( $dataset ){
			$output = $dataset;

			// CRITICAL OPTIMIZATION: Batch load all data to avoid N+1 queries
			// Collect all IDs first, then batch load them
			$user_ids = array();
			$post_ids = array();
			$topic_ids = array();
			$comment_ids = array();
			$activity_ids = array();

			foreach ($dataset as $row) {
				if( isset( $row->user_id ) && $row->user_id ){
					$user_ids[] = absint( $row->user_id );
				}
				if( isset( $row->post_id ) && $row->post_id ){
					$post_ids[] = absint( $row->post_id );
				}
				if( isset( $row->topic_id ) && $row->topic_id ){
					$topic_ids[] = absint( $row->topic_id );
				}
				if( isset( $row->comment_id ) && $row->comment_id ){
					$comment_ids[] = absint( $row->comment_id );
				}
				if( isset( $row->activity_id ) && $row->activity_id ){
					$activity_ids[] = absint( $row->activity_id );
				}
			}

			// Batch prime caches
			$user_ids = array_unique( $user_ids );
			$post_ids = array_unique( $post_ids );
			$topic_ids = array_unique( $topic_ids );
			$comment_ids = array_unique( $comment_ids );
			$activity_ids = array_unique( $activity_ids );

			// Prime user cache - batch load all users to avoid N+1 queries
			$users_cache = array();
			if ( ! empty( $user_ids ) ) {
				// Batch load all users in a single query
				$users = get_users( array( 'include' => $user_ids ) );
				foreach ( $users as $user ) {
					$users_cache[ $user->ID ] = $user;
				}
			}

			// Prime post cache (WordPress does this automatically, but we ensure it)
			if ( ! empty( $post_ids ) ) {
				_prime_post_caches( $post_ids, false, false );
			}

			// Prime topic cache (same as posts)
			if ( ! empty( $topic_ids ) ) {
				_prime_post_caches( $topic_ids, false, false );
			}

			// Prime comment cache to avoid N+1 queries
			$comments_cache = array();
			if ( ! empty( $comment_ids ) ) {
				// get_comments() automatically primes the comment cache
				$comments = get_comments( array( 'comment__in' => $comment_ids ) );
				foreach ( $comments as $comment ) {
					$comments_cache[ $comment->comment_ID ] = $comment;
				}
			}

			// Prime activity cache (BuddyPress) to avoid N+1 queries
			$activities_cache = array();
			if ( ! empty( $activity_ids ) && function_exists( 'bp_activity_get_specific' ) ) {
				$activities = bp_activity_get_specific( array( 'activity_ids' => $activity_ids ) );
				if ( ! empty( $activities['activities'] ) ) {
					foreach ( $activities['activities'] as $activity ) {
						$activities_cache[ $activity->id ] = $activity;
					}
				}
			}

			// Collect all category IDs from all posts and batch load them
			$all_category_ids = array();
			foreach ( $post_ids as $post_id ) {
				$post_cats = wp_get_post_categories( $post_id );
				if ( ! empty( $post_cats ) ) {
					$all_category_ids = array_merge( $all_category_ids, $post_cats );
				}
			}
			$all_category_ids = array_unique( $all_category_ids );
			
			// Batch load all categories
			$categories_cache = array();
			if ( ! empty( $all_category_ids ) ) {
				$categories = get_categories( array( 'include' => $all_category_ids ) );
				foreach ( $categories as $category ) {
					$categories_cache[ $category->term_id ] = $category;
				}
			}

			// Process each row with cached data
			foreach ($dataset as $key => $row) {
				if( isset( $row->date_time ) ){
					$output[$key]->date_time = wp_date( 'Y-m-d H:i:s', strtotime( $row->date_time ) );
				}
				if( isset( $row->user_id ) ){
					$user_id = absint( $row->user_id );
					$user_info = isset( $users_cache[ $user_id ] ) ? $users_cache[ $user_id ] : null;
					if( $user_info ){
						$output[$key]->user_id = '@' . $user_info->user_login;
					} else {
						$output[$key]->user_id = '#'. esc_html__( 'Guest User', 'wp-ulike' );
					}
				}
				if( isset( $row->post_id ) ){
					$post_id = absint( $row->post_id );
					$title = get_the_title( $post_id );
					if( !empty( $title ) ){
						$output[$key]->post_type = get_post_type( $post_id );

						$post_categories = wp_get_post_categories( $post_id );
						$cats = '';

						foreach($post_categories as $k => $c){
							// Use cached category instead of get_category() to avoid N+1 queries
							$cat = isset( $categories_cache[ $c ] ) ? $categories_cache[ $c ] : get_category( $c );
							if ( $cat ) {
								$cats.= sprintf( '%s<a href="%s">%s</a>', $k ? ' , ' : '', get_category_link($cat), $cat->name );
							}
						}

						$output[$key]->category = $cats;

						$output[$key]->post_title   = sprintf( "<a href='%s'> %s </a>" , esc_url( get_permalink($post_id) ), esc_html( $title ) );
					}
				}
				if( isset( $row->topic_id ) ){
					$topic_id = absint( $row->topic_id );
					$topic_title = function_exists('bbp_get_forum_title') ? bbp_get_forum_title( $topic_id ) : get_the_title( $topic_id );
					if( !empty( $topic_title ) ){
						$output[$key]->topic_title = sprintf( "<a href='%s'> %s </a>" , esc_url( get_permalink($topic_id) ), esc_html( $topic_title ) );
					}
				}
				if( isset( $row->activity_id ) ){
					$activity_id = absint( $row->activity_id );
					// Activity link
					$activity_link  = function_exists('bp_activity_get_permalink') ? bp_activity_get_permalink( $activity_id ) : '';
					// Activity title
					$activity_title = esc_html__('Activity Permalink','wp-ulike');
					
					// Use cached activity instead of creating new object to avoid N+1 queries
					if( isset( $activities_cache[ $activity_id ] ) ){
						$activity_obj = $activities_cache[ $activity_id ];
						if ( isset( $activity_obj->current_comment ) ) {
							$activity_obj = $activity_obj->current_comment;
						}
						$activity_title = ! empty( $activity_obj->content ) ? $activity_obj->content : $activity_obj->action;
					} elseif( class_exists('BP_Activity_Activity') ){
						$activity_obj = new BP_Activity_Activity( $activity_id );
						if ( isset( $activity_obj->current_comment ) ) {
							$activity_obj = $activity_obj->current_comment;
						}
						$activity_title = ! empty( $activity_obj->content ) ? $activity_obj->content : $activity_obj->action;
					}

 					$output[$key]->activity_title = sprintf( "<a href='%s'> %s </a>" , esc_url( $activity_link ), esc_html( wp_strip_all_tags( $activity_title ) ) );
				}
				if( isset( $row->comment_id ) ){
					$comment_id = absint( $row->comment_id );
					// Use cached comment instead of get_comment() to avoid N+1 queries
					$comment = isset( $comments_cache[ $comment_id ] ) ? $comments_cache[ $comment_id ] : get_comment( $comment_id );
					if( NULL != $comment ){
						$output[$key]->comment_author  = esc_html( $comment->comment_author );
						$output[$key]->comment_content = sprintf( "<a href='%s'> %s </a>" , esc_url( get_comment_link( $comment ) ), esc_html( wp_strip_all_tags( $comment->comment_content ) ) );
					} else {
						$output[$key]->comment_author  = $output[$key]->comment_content = esc_html__( 'Not Found!', 'wp-ulike' );
					}
				}
			}

			return apply_filters( 'wp_ulike_get_trnasformed_rows', $output );
		}

	}


}