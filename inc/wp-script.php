<?php

	if ( ! defined( 'ABSPATH' ) ) exit; // No direct access allowed

	/**
	 * Add Plugin script files + Creating Localize Objects
	 *
	 * @author       	Alimir
	 * @since           1.0		 
	 * @updated         2.2	 
	 * @updated         2.4.1	 
	 * @updated         2.8 //Removed  like_notice & unlike_notice variables of 'wp_localize_script' function + Dequeued 'wp_ulike_plugins' script
	 * @return          void
	 */
	add_action('init', 'wp_ulike_enqueue_scripts');
	
	function wp_ulike_enqueue_scripts() {
		//enqueue JQuery script
		wp_enqueue_script( 'jquery' );
		//Add wp_ulike script file with special functions.
		wp_enqueue_script('wp_ulike', WP_ULIKE_ASSETS_URL . '/js/wp-ulike.min.js', array('jquery'), '2.8.1', true);
		//localize script
		wp_localize_script( 'wp_ulike', 'wp_ulike_params', array(
			'ajax_url' 			=> admin_url( 'admin-ajax.php' ),
			'counter_selector' 	=> apply_filters('wp_ulike_counter_selector', '.count-box'),
			'button_selector' 	=> apply_filters('wp_ulike_button_selector', '.wp_ulike_btn'),
			'general_selector' 	=> apply_filters('wp_ulike_general_selector', '.wp_ulike_general_class'),
			'button_type' 		=> wp_ulike_get_setting( 'wp_ulike_general', 'button_type'),
			'notifications' 	=> wp_ulike_get_setting( 'wp_ulike_general', 'notifications')
		));
		//wp_ajax hooks for the custom AJAX requests
		add_action('wp_ajax_wp_ulike_process','wp_ulike_process');
		add_action('wp_ajax_nopriv_wp_ulike_process', 'wp_ulike_process');
	}

	/**
	 * Add Plugin CSS styles + Custom Style Support
	 *
	 * @author       	Alimir
	 * @since           1.0		 
	 * @updated         2.3	 
	 * @updated         2.4
	 * @return          Void (Enqueue CSS styles)
	 */
	add_action('wp_enqueue_scripts', 'wp_ulike_enqueue_style');
	
	function wp_ulike_enqueue_style() {
		wp_enqueue_style( 'wp-ulike', WP_ULIKE_ASSETS_URL . '/css/wp-ulike.min.css' );
		//add your custom style from setting panel.
		wp_add_inline_style( 'wp-ulike', wp_ulike_get_custom_style() );
	}