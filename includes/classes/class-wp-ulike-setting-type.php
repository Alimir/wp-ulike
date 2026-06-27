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
		$this->typeSettings = WP_Ulike_Pulse_Registry::setting_profile( $type );
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