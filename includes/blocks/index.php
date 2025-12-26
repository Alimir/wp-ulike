<?php
/**
 * Blocks Registration
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

/**
 * Register custom block category for WP ULike blocks
 */
function wp_ulike_block_category( $categories, $editor_context ) {
	$custom_category = array(
		'slug'  => 'wp-ulike',
		'title' => esc_html__( 'WP ULike', 'wp-ulike' )
	);

	array_unshift( $categories, $custom_category );
	return $categories;
}
add_filter( 'block_categories_all', 'wp_ulike_block_category', 10, 2 );

/**
 * Register WP ULike Gutenberg Blocks
 * Automatically registers all blocks found in the blocks directory
 */
function wp_ulike_register_blocks() {
	// Check if WordPress supports block registration
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	$blocks_dir = WP_ULIKE_INC_DIR . '/blocks';

	if ( ! is_dir( $blocks_dir ) ) {
		return;
	}

	// Scan blocks directory for block subdirectories
	$block_dirs = glob( $blocks_dir . '/*', GLOB_ONLYDIR );

	foreach ( $block_dirs as $block_dir ) {
		$block_json = $block_dir . '/block.json';

		// Skip if block.json doesn't exist
		if ( ! file_exists( $block_json ) ) {
			continue;
		}

		// Prepare block arguments
		$block_args = array();

		// Get block name from block.json to determine render callback
		$block_data = json_decode( file_get_contents( $block_json ), true );
		$block_name = isset( $block_data['name'] ) ? $block_data['name'] : '';

		$render_file = $block_dir . '/render.php';
		if ( file_exists( $render_file ) ) {
			$block_args['render_callback'] = function( $attributes, $content, $block_instance = null ) use ( $render_file, $block_name ) {
				return wp_ulike_block_render_callback( $attributes, $content, $block_instance, $render_file, $block_name );
			};
		}

		// Register the block using block.json (WordPress 5.8+ standard)
		register_block_type( $block_dir, $block_args );
	}
}
add_action( 'init', 'wp_ulike_register_blocks' );

/**
 * Enqueue WP ULike frontend styles
 * Only enqueues if not already enqueued by class-wp-ulike-frontend-assets
 */
function wp_ulike_block_enqueue_frontend_styles() {
	// Check if already enqueued by main class
	if ( wp_style_is( WP_ULIKE_SLUG, 'enqueued' ) || wp_style_is( WP_ULIKE_SLUG, 'registered' ) ) {
		return;
	}

	// Use existing class method to load styles
	if ( class_exists( 'wp_ulike_frontend_assets' ) ) {
		$assets = new wp_ulike_frontend_assets();
		$assets->load_styles();
	}

	// Enqueue Pro version CSS if Pro exists
	if ( defined( 'WP_ULIKE_PRO_VERSION' ) && defined( 'WP_ULIKE_PRO_PUBLIC_URL' ) && defined( 'WP_ULIKE_PRO_DOMAIN' ) ) {
		if ( ! wp_style_is( WP_ULIKE_PRO_DOMAIN, 'enqueued' ) && ! wp_style_is( WP_ULIKE_PRO_DOMAIN, 'registered' ) ) {
			wp_enqueue_style( WP_ULIKE_PRO_DOMAIN, WP_ULIKE_PRO_PUBLIC_URL . '/assets/css/wp-ulike-pro.min.css', array( WP_ULIKE_SLUG ), WP_ULIKE_PRO_VERSION );
		}
	}
}

/**
 * Enqueue WP ULike frontend JavaScript
 * Only enqueues if not already enqueued by class-wp-ulike-frontend-assets
 */
function wp_ulike_block_enqueue_frontend_scripts() {
	// Check if already enqueued by main class
	if ( wp_script_is( 'wp_ulike', 'enqueued' ) || wp_script_is( 'wp_ulike', 'registered' ) ) {
		return;
	}

	wp_enqueue_script( 'wp_ulike', WP_ULIKE_ASSETS_URL . '/js/wp-ulike.min.js', array(), WP_ULIKE_VERSION, true );

	wp_localize_script( 'wp_ulike', 'wp_ulike_params', array(
		'ajax_url'      => admin_url( 'admin-ajax.php' ),
		'notifications' => true
	) );

	// Ensure initialization runs in iframe - trigger after script loads
	wp_add_inline_script( 'wp_ulike', '
		(function() {
			function ensureUlikeInit() {
				if (typeof WordpressUlike !== "undefined" && document.body) {
					var elements = document.querySelectorAll(".wpulike:not([data-ulike-initialized])");
					if (elements.length > 0) {
						elements.forEach(function(el) {
							try {
								new WordpressUlike(el);
								el.setAttribute("data-ulike-initialized", "true");
							} catch(e) {}
						});
					}
				}
			}
			if (document.readyState === "loading") {
				document.addEventListener("DOMContentLoaded", ensureUlikeInit);
			} else {
				setTimeout(ensureUlikeInit, 100);
			}
			setInterval(ensureUlikeInit, 500);
		})();
	', 'after' );
}

/**
 * Enqueue block assets for editor iframe (WordPress 6.3+)
 * Only loads if not already loaded by class-wp-ulike-frontend-assets
 */
function wp_ulike_block_assets() {
	// Only load in editor iframe, not frontend (frontend handled by class-wp-ulike-frontend-assets)
	$is_editor = is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST );

	if ( $is_editor ) {
		wp_ulike_block_enqueue_frontend_styles();
		wp_ulike_block_enqueue_frontend_scripts();
	}
}
add_action( 'enqueue_block_assets', 'wp_ulike_block_assets' );

/**
 * Enqueue block editor assets
 */
function wp_ulike_block_editor_assets() {
	$blocks_dir = WP_ULIKE_INC_DIR . '/blocks';

	if ( ! is_dir( $blocks_dir ) ) {
		return;
	}
	$block_dirs = glob( $blocks_dir . '/*', GLOB_ONLYDIR );

	foreach ( $block_dirs as $block_dir ) {
		$block_json = $block_dir . '/block.json';

		if ( ! file_exists( $block_json ) ) {
			continue;
		}

		$block_data = json_decode( file_get_contents( $block_json ), true );
		$block_name = isset( $block_data['name'] ) ? $block_data['name'] : '';
		$block_slug = sanitize_key( str_replace( array( 'wp-ulike/', '/' ), array( '', '-' ), $block_name ) );

		$asset_file = $block_dir . '/build/index.asset.php';
		$js_file    = $block_dir . '/build/index.js';

		if ( file_exists( $asset_file ) && file_exists( $js_file ) ) {
			$asset = require $asset_file;
			$script_handle = 'wp-ulike-block-' . $block_slug . '-editor';
			$block_url = WP_ULIKE_INC_URL . '/blocks/' . basename( $block_dir );

			wp_enqueue_script(
				$script_handle,
				$block_url . '/build/index.js',
				$asset['dependencies'],
				$asset['version'] ? $asset['version'] : WP_ULIKE_VERSION,
				true
			);

			$editor_css = $block_dir . '/build/index.css';
			if ( file_exists( $editor_css ) ) {
				wp_enqueue_style(
					$script_handle,
					$block_url . '/build/index.css',
					array(),
					$asset['version'] ? $asset['version'] : WP_ULIKE_VERSION
				);
			}
		}
	}
}
add_action( 'enqueue_block_editor_assets', 'wp_ulike_block_editor_assets' );

/**
 * Enqueue frontend assets when block is used (fallback if main class doesn't load)
 */
function wp_ulike_block_frontend_assets() {
	// Only load if main class hasn't already loaded assets
	if ( wp_script_is( 'wp_ulike', 'enqueued' ) || ( defined( 'WP_ULIKE_PRO_DOMAIN' ) && wp_script_is( WP_ULIKE_PRO_DOMAIN, 'enqueued' ) ) ) {
		return;
	}

	$blocks_dir = WP_ULIKE_INC_DIR . '/blocks';
	if ( ! is_dir( $blocks_dir ) ) {
		return;
	}

	$block_dirs = glob( $blocks_dir . '/*', GLOB_ONLYDIR );
	$block_names = array();

	foreach ( $block_dirs as $block_dir ) {
		$block_json = $block_dir . '/block.json';
		if ( file_exists( $block_json ) ) {
			$block_data = json_decode( file_get_contents( $block_json ), true );
			if ( isset( $block_data['name'] ) ) {
				$block_names[] = $block_data['name'];
			}
		}
	}

	foreach ( $block_names as $block_name ) {
		if ( has_block( $block_name ) ) {
			wp_ulike_block_enqueue_frontend_styles();
			wp_ulike_block_enqueue_frontend_scripts();
			break;
		}
	}
}
add_action( 'wp_enqueue_scripts', 'wp_ulike_block_frontend_assets' );

/**
 * Block render callback
 */
function wp_ulike_block_render_callback( $attributes, $content = '', $block_instance = null, $render_file = '', $block_name = '' ) {
	$wrapper_class = '';

	if ( $block_instance instanceof WP_Block ) {
		$block_name = $block_instance->name;
		if ( ! empty( $block_instance->parsed_block['attrs']['className'] ) ) {
			$wrapper_class = $block_instance->parsed_block['attrs']['className'];
		}
	}

	if ( empty( $wrapper_class ) && isset( $attributes['className'] ) && ! empty( $attributes['className'] ) ) {
		$wrapper_class = $attributes['className'];
	}

	if ( empty( $render_file ) && ! empty( $block_name ) ) {
		$block_slug = str_replace( 'wp-ulike/', '', $block_name );
		$render_file = WP_ULIKE_INC_DIR . '/blocks/' . $block_slug . '/render.php';
	}

	if ( empty( $render_file ) || ! file_exists( $render_file ) ) {
		$render_file = WP_ULIKE_INC_DIR . '/blocks/button/render.php';
	}

	if ( ! file_exists( $render_file ) ) {
		return '';
	}

	$render_attributes = array(
		'for'           => isset( $attributes['for'] ) ? $attributes['for'] : 'post',
		'itemId'        => isset( $attributes['itemId'] ) ? $attributes['itemId'] : '',
		'useCurrentPostId' => isset( $attributes['useCurrentPostId'] ) ? $attributes['useCurrentPostId'] : true,
		'template'      => isset( $attributes['template'] ) ? $attributes['template'] : '',
		'buttonType'    => isset( $attributes['buttonType'] ) ? $attributes['buttonType'] : '',
		'wrapperClass'  => $wrapper_class,
	);

	// Extract attributes for render.php
	extract( $render_attributes, EXTR_SKIP );

	ob_start();
	include $render_file;
	return ob_get_clean();
}

/**
 * Register REST API endpoint for templates
 */
function wp_ulike_register_rest_routes() {
	register_rest_route( 'wp-ulike/v1', '/templates', array(
		'methods'             => 'GET',
		'callback'            => 'wp_ulike_get_templates_for_block',
		'permission_callback' => '__return_true',
	) );
}
add_action( 'rest_api_init', 'wp_ulike_register_rest_routes' );

/**
 * Get templates list for block editor
 *
 * @return array
 */
function wp_ulike_get_templates_for_block() {
	if ( ! function_exists( 'wp_ulike_generate_templates_list' ) ) {
		return array();
	}

	$templates = wp_ulike_generate_templates_list();
	$output = array();

	// Get default template from settings (for posts by default)
	$default_template_key = 'wpulike-default';
	if ( function_exists( 'wp_ulike_get_option' ) ) {
		$saved_template = wp_ulike_get_option( 'posts_group|template', 'wpulike-default' );
		if ( ! empty( $saved_template ) && isset( $templates[ $saved_template ] ) ) {
			$default_template_key = $saved_template;
		}
	}

	// Find the default template name
	$default_template_name = __( 'Use Settings Default', 'wp-ulike' );
	if ( isset( $templates[ $default_template_key ] ) && isset( $templates[ $default_template_key ]['name'] ) ) {
		$default_template_name = sprintf( __( 'Use Settings Default (%s)', 'wp-ulike' ), $templates[ $default_template_key ]['name'] );
	}

	if ( ! empty( $templates ) ) {
		foreach ( $templates as $key => $args ) {
			$output[] = array(
				'key'             => $key,
				'name'            => isset( $args['name'] ) ? $args['name'] : ucfirst( str_replace( array( 'wpulike-', 'wp-ulike-' ), '', $key ) ),
				'symbol'          => isset( $args['symbol'] ) ? $args['symbol'] : '',
				'is_text_support' => isset( $args['is_text_support'] ) ? $args['is_text_support'] : false
			);
		}
	}

	return array(
		'templates' => $output,
		'default_template_name' => $default_template_name,
		'default_template_key' => $default_template_key
	);
}
