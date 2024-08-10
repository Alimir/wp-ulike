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
			'type'  => 'ASC',
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
			$paged = ( $this->page - 1 ) * $this->per_page;
			$orderBy   = $this->sort['field'];
			$orderType = $this->sort['type'];

			return $this->wpdb->get_results( "SELECT * FROM {$table} ORDER BY {$orderBy} {$orderType} LIMIT {$paged}, {$this->per_page}" );
		}

		/**
		 * get SQL row
		 *
		 * @return object
		 */
		public function get_row( $item_ID ){
			$table = esc_sql( $this->wpdb->prefix . $this->table );

			return $this->wpdb->get_row( "
				SELECT *
				FROM `$table`
				WHERE `id` = $item_ID"
			);
		}

		/**
		 * get all SQL results
		 *
		 * @return object
		 */
		public function get_all_rows(){
			$table = esc_sql( $this->wpdb->prefix . $this->table );
			$orderBy   = $this->sort['field'];
			$orderType = $this->sort['type'];

			return $this->wpdb->get_results( "
				SELECT *
				FROM `$table`
				ORDER BY $orderBy $orderType"
			);
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
					$selectedIds[] = $item['id'];
				}
			}
			if( ! empty( $selectedIds ) ){
				$selectedIds = implode( ',', array_map( 'absint', $selectedIds ) );
				$this->wpdb->query( "DELETE FROM $table WHERE ID IN($selectedIds)" );
			}
		}

		/**
		 * Delete single row
		 *
		 * @param integer $item_id
		 * @return integer|false
		 */
		public function delete_row( $item_id ){
			$table   = esc_sql( $this->wpdb->prefix . $this->table );

			return $this->wpdb->delete(
				$table,
				[ 'ID' => esc_sql( $item_id ) ],
				['%d'],
			);
		}

		/**
		 * Get total rows per table
		 *
		 * @return string
		 */
		private function get_total_records(){
			$table  = esc_sql( $this->wpdb->prefix . $this->table );
			return $this->wpdb->get_var( "SELECT COUNT(*) FROM `$table`" );
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

			foreach ($dataset as $key => $row) {
				if( isset( $row->date_time ) ){
					$output[$key]->date_time = wp_date( 'Y-m-d H:i:s', strtotime( $row->date_time ) );
				}
				if( isset( $row->user_id ) ){
					if( NULL != ( $user_info = get_userdata( $row->user_id ) ) ){
						$output[$key]->user_id = '@' . $user_info->user_login;
					} else {
						$output[$key]->user_id = '#'. esc_html__( 'Guest User', 'wp-ulike' );
					}
				}
				if( isset( $row->post_id ) ){
					$title = get_the_title( $row->post_id );
					if( !empty( $title ) ){
						$output[$key]->post_type = get_post_type( $row->post_id );

						$post_categories = wp_get_post_categories( $row->post_id );
						$cats = '';

						foreach($post_categories as $k => $c){
							$cat = get_category( $c );
							$cats.= sprintf( '%s<a href="%s">%s</a>', $k ? ' , ' : '', get_category_link($cat), $cat->name );
						}

						$output[$key]->category = $cats;

						$output[$key]->post_title   = sprintf( "<a href='%s'> %s </a>" , get_permalink($row->post_id), $title );
					}
				}
				if( isset( $row->topic_id ) ){
					$topic_title = function_exists('bbp_get_forum_title') ? bbp_get_forum_title( $row->topic_id ) : get_the_title( $row->topic_id );
					if( !empty( $topic_title ) ){
						$output[$key]->topic_title = sprintf( "<a href='%s'> %s </a>" , get_permalink($row->topic_id), $topic_title );
					}
				}
				if( isset( $row->activity_id ) ){
					// Activity link
					$activity_link  = function_exists('bp_activity_get_permalink') ? bp_activity_get_permalink( $row->activity_id ) : '';
					// Activity title
					$activity_title = esc_html__('Activity Permalink','wp-ulike');
					if( class_exists('BP_Activity_Activity') ){
						$activity_obj = new BP_Activity_Activity( $row->activity_id );

						if ( isset( $activity_obj->current_comment ) ) {
							$activity_obj = $activity_obj->current_comment;
						}

						$activity_title = ! empty( $activity_obj->content ) ? $activity_obj->content : $activity_obj->action;
					}

 					$output[$key]->activity_title = sprintf( "<a href='%s'> %s </a>" , $activity_link, wp_strip_all_tags( $activity_title ) );
				}
				if( isset( $row->comment_id ) ){
					if( NULL != ( $comment = get_comment( $row->comment_id ) ) ){
						$output[$key]->comment_author  = $comment->comment_author;
						$output[$key]->comment_content = sprintf( "<a href='%s'> %s </a>" , esc_url( get_comment_link( $comment ) ), wp_strip_all_tags( $comment->comment_content ) );
					} else {
						$output[$key]->comment_author  = $output[$key]->comment_content = esc_html__( 'Not Found!', 'wp-ulike' );
					}
				}
			}

			return apply_filters( 'wp_ulike_get_trnasformed_rows', $output );
		}

	}


}