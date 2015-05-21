<?php

	if ( ! defined( 'ABSPATH' ) ) exit; // No direct access allowed

	/**
	 * Add Plugin script files + Creating Localize Objects
	 *
	 * @author       	Alimir
	 * @since           1.0		 
	 * @updated         2.2	 
	 * @return          void
	 */
	add_action('init', 'wp_ulike_enqueue_scripts');
	
	function wp_ulike_enqueue_scripts() {
		//enqueue JQuery script
		wp_enqueue_script( 'jquery' );
		//Add ulike script file with special functions.
		wp_enqueue_script('wp_ulike', plugins_url('assets/js/wp-ulike-scripts.min.js', dirname(__FILE__)), array('jquery'), '1.2.1');
		//Add ulike plugin file, such as: tooltip, transaction, ...
		wp_enqueue_script('wp_ulike_plugins', plugins_url('assets/js/wp-ulike-plugins.js', dirname(__FILE__)), array('jquery'), '1.0.0', true);	
		//localize script
		wp_localize_script( 'wp_ulike', 'ulike_obj', array(
			'ajaxurl' 		=> admin_url( 'admin-ajax.php' ),
			'button_text_u' => wp_ulike_get_setting( 'wp_ulike_general', 'button_text_u'),
			'button_text' 	=> wp_ulike_get_setting( 'wp_ulike_general', 'button_text'),
			'button_type' 	=> wp_ulike_get_setting( 'wp_ulike_general', 'button_type')
		));
		//wp_ajax hooks for the custom AJAX requests
		add_action('wp_ajax_wp_ulike_process','wp_ulike_process');
		add_action('wp_ajax_nopriv_wp_ulike_process', 'wp_ulike_process');
	}

	/**
	 * Add Plugin styles for RTL & LTR languages + Custom Style Support
	 *
	 * @author       	Alimir
	 * @since           1.0		 
	 * @updated         2.3	 
	 * @return          Void (Enqueue CSS styles)
	 */
	add_action('wp_enqueue_scripts', 'wp_ulike_enqueue_style');
	
	function wp_ulike_enqueue_style() {
		if(!is_rtl()){
			wp_enqueue_style( 'wp-ulike', plugins_url('assets/css/wp-ulike.min.css', dirname(__FILE__)) );
		}
		else{
			wp_enqueue_style( 'wp-ulike', plugins_url('assets/css/wp-ulike-rtl.min.css', dirname(__FILE__)) );
		}
		//add your custom style from setting panel.
		wp_add_inline_style( 'wp-ulike', wp_ulike_get_custom_style() );
	}