<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class wp_ulike_setting_type {

	protected $typeSettings;

	/**
	 * Static cache for setting type objects to avoid recreating them
	 * @var array
	 */
	private static $instances = array();

	/**
	 * Get or create a setting type instance (singleton pattern per type)
	 * 
	 * @param string $type
	 * @return wp_ulike_setting_type
	 */
	public static function get_instance( $type ) {
		// Normalize type to handle aliases
		$normalized_type = self::normalize_type( $type );
		
		if ( ! isset( self::$instances[ $normalized_type ] ) ) {
			self::$instances[ $normalized_type ] = new self( $normalized_type );
		}
		
		return self::$instances[ $normalized_type ];
	}

	function __construct( $type ){
		$this->setTypeSettings( $type );
	}
	
	/**
	 * Normalize type name to handle aliases
	 * 
	 * @param string $type
	 * @return string Normalized type
	 */
	private static function normalize_type( $type ) {
		// Map aliases to canonical types
		$aliases = array(
			'likeThisComment' => 'comment',
			'comments' => 'comment',
			'likeThisActivity' => 'activity',
			'buddypress' => 'activity',
			'activities' => 'activity',
			'likeThisTopic' => 'topic',
			'bbpress' => 'topic',
			'topics' => 'topic',
			'likeThis' => 'post',
		);
		
		return isset( $aliases[ $type ] ) ? $aliases[ $type ] : $type;
	}

	protected function setTypeSettings( $type ){
		switch ( $type ) {
			case 'likeThisComment':
			case 'comment':
			case 'comments':
				$this->typeSettings = array(
					'setting'  => 'comments_group',
					'table'    => 'ulike_comments',
					'column'   => 'comment_id',
					'key'      => '_commentliked',
					'slug'     => 'comment',
					'cookie'   => 'comment-liked-'
				);
				break;

			case 'likeThisActivity':
			case 'buddypress':
			case 'activity':
			case 'activities':
				$this->typeSettings = array(
					'setting'  => 'buddypress_group',
					'table'    => 'ulike_activities',
					'column'   => 'activity_id',
					'key'      => '_activityliked',
					'slug'     => 'activity',
					'cookie'   => 'activity-liked-',
				);
				break;

			case 'likeThisTopic':
			case 'bbpress':
			case 'topic':
			case 'topics':
				$this->typeSettings = array(
					'setting'  => 'bbpress_group',
					'table'    => 'ulike_forums',
					'column'   => 'topic_id',
					'key'      => '_topicliked',
					'slug'     => 'topic',
					'cookie'   => 'topic-liked-'
				);
				break;

			default:
				$this->typeSettings = array(
					'setting'  => 'posts_group',
					'table'    => 'ulike',
					'column'   => 'post_id',
					'key'      => '_liked',
					'slug'     => 'post',
					'cookie'   => 'liked-'
				);
				break;
		}
	}

	public function getType(){
		return ! empty( $this->typeSettings['slug'] ) ? $this->typeSettings['slug'] : NULL;
	}

	public function getKey(){
		return ! empty( $this->typeSettings['key'] ) ? $this->typeSettings['key'] : NULL;
	}

	public function getCookieName(){
		return ! empty( $this->typeSettings['cookie'] ) ? $this->typeSettings['cookie'] : NULL;
	}

	public function getSettingKey(){
		return ! empty( $this->typeSettings['setting'] ) ? $this->typeSettings['setting'] : NULL;
	}

	public function getTableName(){
		return ! empty( $this->typeSettings['table'] ) ? $this->typeSettings['table'] : NULL;
	}

	public function getColumnName(){
		return ! empty( $this->typeSettings['column'] ) ? $this->typeSettings['column'] : NULL;
	}

}