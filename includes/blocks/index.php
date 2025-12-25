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

		// Check if render.php exists - use generic callback that handles different blocks
		$render_file = $block_dir . '/render.php';
		if ( file_exists( $render_file ) ) {
			// Use a callback that includes the block-specific render file
			$block_args['render_callback'] = function( $attributes, $content ) use ( $render_file, $block_name ) {
				return wp_ulike_block_render_callback( $attributes, $content, $render_file, $block_name );
			};
		}

		// Register the block using block.json (WordPress 5.8+ standard)
		register_block_type( $block_dir, $block_args );
	}
}
add_action( 'init', 'wp_ulike_register_blocks' );

/**
 * Enqueue WP ULike frontend styles (for block editor preview and frontend)
 * Always loads free version CSS, and Pro CSS if Pro version exists
 */
function wp_ulike_block_enqueue_frontend_styles() {
	// Always enqueue free version CSS first (Pro depends on it)
	if ( ! wp_style_is( WP_ULIKE_SLUG, 'enqueued' ) && ! wp_style_is( WP_ULIKE_SLUG, 'registered' ) ) {
		// Determine which CSS file to use (minified in production, regular in dev)
		$css_file = WP_ULIKE_ASSETS_URL . '/css/wp-ulike.css';
		if ( file_exists( WP_ULIKE_ASSETS_DIR . '/css/wp-ulike.min.css' ) && ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
			$css_file = WP_ULIKE_ASSETS_URL . '/css/wp-ulike.min.css';
		}

		wp_enqueue_style( WP_ULIKE_SLUG, $css_file, array(), WP_ULIKE_VERSION );

		// Load custom CSS if needed
		if ( function_exists( 'wp_ulike_get_custom_style' ) ) {
			$custom_css = wp_ulike_get_custom_style();
			if ( ! empty( $custom_css ) ) {
				wp_add_inline_style( WP_ULIKE_SLUG, $custom_css );
			}
		}
	}

	// Enqueue Pro version CSS if Pro exists
	if ( defined( 'WP_ULIKE_PRO_VERSION' ) && defined( 'WP_ULIKE_PRO_PUBLIC_URL' ) && defined( 'WP_ULIKE_PRO_DOMAIN' ) ) {
		if ( ! wp_style_is( WP_ULIKE_PRO_DOMAIN, 'enqueued' ) && ! wp_style_is( WP_ULIKE_PRO_DOMAIN, 'registered' ) ) {
			// Determine which CSS file to use (minified in production, regular in dev)
			$pro_css_file = WP_ULIKE_PRO_PUBLIC_URL . '/assets/css/wp-ulike-pro.css';
			if ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
				$pro_css_file = WP_ULIKE_PRO_PUBLIC_URL . '/assets/css/wp-ulike-pro.min.css';
			}

			// Pro CSS depends on free version CSS
			wp_enqueue_style( WP_ULIKE_PRO_DOMAIN, $pro_css_file, array( WP_ULIKE_SLUG ), WP_ULIKE_PRO_VERSION );
		}
	}
}

/**
 * Enqueue WP ULike frontend JavaScript (for block editor preview and frontend)
 * Loads Pro JS if Pro exists, otherwise loads free JS
 */
function wp_ulike_block_enqueue_frontend_scripts() {
	// Enqueue Pro version JavaScript if Pro exists (Pro replaces free JS)
	if ( defined( 'WP_ULIKE_PRO_VERSION' ) && defined( 'WP_ULIKE_PRO_PUBLIC_URL' ) && defined( 'WP_ULIKE_PRO_DOMAIN' ) ) {
		// Only load Pro JS if version is >= 1.5.3 (older versions don't handle this properly)
		if ( version_compare( WP_ULIKE_PRO_VERSION, '1.5.3', '>=' ) ) {
			if ( ! wp_script_is( WP_ULIKE_PRO_DOMAIN, 'enqueued' ) && ! wp_script_is( WP_ULIKE_PRO_DOMAIN, 'registered' ) ) {
				// Determine which JS file to use (minified in production, regular in dev)
				$pro_js_file = WP_ULIKE_PRO_PUBLIC_URL . '/assets/js/wp-ulike-pro.js';
				if ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
					$pro_js_file = WP_ULIKE_PRO_PUBLIC_URL . '/assets/js/wp-ulike-pro.min.js';
				}

				wp_enqueue_script( WP_ULIKE_PRO_DOMAIN, $pro_js_file, array(), WP_ULIKE_PRO_VERSION, true );

				// Localize Pro script (similar to how Pro class does it)
				if ( function_exists( 'wp_ulike_get_option' ) ) {
					// Get view tracking enabled types
					$view_tracking_enabled = wp_ulike_get_option( 'view_tracking_enabled_types', array( 'post' ) );
					if ( empty( $view_tracking_enabled ) || ! is_array( $view_tracking_enabled ) ) {
						$view_tracking_enabled = array( 'post' );
					}

					$localize_args = array(
						'AjaxUrl' => admin_url( 'admin-ajax.php' ),
						'Nonce'   => wp_create_nonce( WP_ULIKE_PRO_DOMAIN ),
						'TabSide' => wp_ulike_get_option( 'user_profiles_appearance|tabs_side', 'top' ),
						'ViewTracking' => array(
							'enabledTypes' => $view_tracking_enabled
						)
					);

					wp_localize_script( WP_ULIKE_PRO_DOMAIN, 'UlikeProCommonConfig', $localize_args );
				}
			}
			// Pro JS handles everything, so return early
			return;
		}
	}

	// Load free version JavaScript if Pro doesn't exist or is older version
	if ( ! wp_script_is( 'wp_ulike', 'enqueued' ) && ! wp_script_is( 'wp_ulike', 'registered' ) ) {
		// Determine which JS file to use (minified in production, regular in dev)
		$js_file = WP_ULIKE_ASSETS_URL . '/js/wp-ulike.js';
		if ( file_exists( WP_ULIKE_ASSETS_DIR . '/js/wp-ulike.min.js' ) && ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
			$js_file = WP_ULIKE_ASSETS_URL . '/js/wp-ulike.min.js';
		}

		wp_enqueue_script( 'wp_ulike', $js_file, array(), WP_ULIKE_VERSION, true );

		// Localize script
		if ( function_exists( 'wp_ulike_get_option' ) ) {
			wp_localize_script( 'wp_ulike', 'wp_ulike_params', array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'notifications' => wp_ulike_get_option( 'enable_toast_notice' )
			) );
		}
	}
}

/**
 * Enqueue block editor assets
 * Automatically enqueues assets for all registered blocks
 */
function wp_ulike_block_editor_assets() {
	$blocks_dir = WP_ULIKE_INC_DIR . '/blocks';

	if ( ! is_dir( $blocks_dir ) ) {
		return;
	}

	// This hook only fires in block editor context, so no additional check needed

	// Enqueue WP ULike frontend styles and scripts for block preview
	wp_ulike_block_enqueue_frontend_styles();
	wp_ulike_block_enqueue_frontend_scripts();

	// Scan blocks directory and enqueue assets for each block
	$block_dirs = glob( $blocks_dir . '/*', GLOB_ONLYDIR );

	foreach ( $block_dirs as $block_dir ) {
		$block_json = $block_dir . '/block.json';

		if ( ! file_exists( $block_json ) ) {
			continue;
		}

		// Get block name for unique script/style handles
		$block_data = json_decode( file_get_contents( $block_json ), true );
		$block_name = isset( $block_data['name'] ) ? $block_data['name'] : '';
		$block_slug = str_replace( array( 'wp-ulike/', '/' ), array( '', '-' ), $block_name );
		$block_slug = sanitize_key( $block_slug );

		$asset_file = $block_dir . '/build/index.asset.php';
		$js_file    = $block_dir . '/build/index.js';

		if ( file_exists( $asset_file ) && file_exists( $js_file ) ) {
			// Use asset file if it exists (generated by @wordpress/scripts)
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

			// Enqueue editor CSS from build directory if it exists
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
	// Styles are also handled by block.json referencing build/index.css
}
add_action( 'enqueue_block_editor_assets', 'wp_ulike_block_editor_assets' );

/**
 * Enqueue frontend assets when any WP ULike block is used
 */
function wp_ulike_block_frontend_assets() {
	// Get all registered WP ULike blocks and check if any are used on the page
	$blocks_dir = WP_ULIKE_INC_DIR . '/blocks';

	if ( ! is_dir( $blocks_dir ) ) {
		return;
	}

	// Scan for all blocks
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

	// Check if any WP ULike block is used on the page
	$has_wp_ulike_block = false;
	foreach ( $block_names as $block_name ) {
		if ( has_block( $block_name ) ) {
			$has_wp_ulike_block = true;
			break;
		}
	}

	if ( $has_wp_ulike_block ) {
		wp_ulike_block_enqueue_frontend_styles();
		wp_ulike_block_enqueue_frontend_scripts();
	}
}
add_action( 'wp_enqueue_scripts', 'wp_ulike_block_frontend_assets' );

/**
 * Block render callback (generic - works for all blocks)
 *
 * @param array $attributes Block attributes
 * @param string $content Block content
 * @param string $render_file Path to the render.php file for this block
 * @param string $block_name The block name (e.g., 'wp-ulike/button')
 * @return string Rendered block HTML
 */
function wp_ulike_block_render_callback( $attributes, $content = '', $render_file = '', $block_name = '' ) {
	// If render_file not provided, try to determine from block name
	if ( empty( $render_file ) && ! empty( $block_name ) ) {
		// Extract block slug from block name (e.g., 'wp-ulike/button' -> 'button')
		$block_slug = str_replace( 'wp-ulike/', '', $block_name );
		$render_file = WP_ULIKE_INC_DIR . '/blocks/' . $block_slug . '/render.php';
	}

	// Final fallback to the button block (for backward compatibility)
	if ( empty( $render_file ) || ! file_exists( $render_file ) ) {
		$render_file = WP_ULIKE_INC_DIR . '/blocks/button/render.php';
	}

	if ( ! file_exists( $render_file ) ) {
		return '';
	}

	// Map camelCase attributes to the format expected by render.php
	// Each block's render.php can expect different attributes
	$render_attributes = array(
		'for'           => isset( $attributes['for'] ) ? $attributes['for'] : 'post',
		'itemId'        => isset( $attributes['itemId'] ) ? $attributes['itemId'] : '',
		'useCurrentPostId' => isset( $attributes['useCurrentPostId'] ) ? $attributes['useCurrentPostId'] : true,
		'template'      => isset( $attributes['template'] ) ? $attributes['template'] : '',
		'buttonType'    => isset( $attributes['buttonType'] ) ? $attributes['buttonType'] : '',
		'wrapperClass'  => isset( $attributes['wrapperClass'] ) ? $attributes['wrapperClass'] : '',
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
