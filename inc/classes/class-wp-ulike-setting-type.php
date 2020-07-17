<?php

class wp_ulike_setting_type {

	protected $typeSettings;

	function __construct( $type ){
		$this->setTypeSettings( $type );
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