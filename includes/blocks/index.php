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
 * Get all registered WP ULike block names
 * Cached to avoid repeated file system access
 */
function wp_ulike_get_block_names() {
	static $block_names = null;

	if ( $block_names !== null ) {
		return $block_names;
	}

	$block_names = array();
	$blocks_dir = WP_ULIKE_INC_DIR . '/blocks';

	if ( ! is_dir( $blocks_dir ) ) {
		return $block_names;
	}

	$block_dirs = glob( $blocks_dir . '/*', GLOB_ONLYDIR );

	foreach ( $block_dirs as $block_dir ) {
		$block_json = $block_dir . '/block.json';
		if ( file_exists( $block_json ) ) {
			$block_data = json_decode( file_get_contents( $block_json ), true );
			if ( isset( $block_data['name'] ) ) {
				$block_names[] = $block_data['name'];
			}
		}
	}

	return $block_names;
}

/**
 * Register WP ULike Gutenberg Blocks
 * Automatically registers all blocks found in the blocks directory
 */
function wp_ulike_register_blocks() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

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

		$block_args = array();
		$render_file = $block_dir . '/render.php';

		if ( file_exists( $render_file ) ) {
		$block_args['render_callback'] = function( $attributes, $content, $block = null ) use ( $render_file, $block_name ) {
			return wp_ulike_block_render_callback( $attributes, $content, $block, $render_file, $block_name );
		};
		}

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
 * Get initialization script for iframe
 */
function wp_ulike_get_iframe_init_script() {
	return '
		(function() {
			if (typeof WordpressUlike === "undefined") return;

			function initUlike(elements) {
				if (!elements || !elements.length) return;
				Array.prototype.forEach.call(elements, function(el) {
					if (el && !el.hasAttribute("data-ulike-initialized")) {
						try {
							new WordpressUlike(el);
							el.setAttribute("data-ulike-initialized", "true");
						} catch(e) {}
					}
				});
			}

			function setupObserver() {
				if (!document.body) {
					setTimeout(setupObserver, 50);
					return;
				}

				var observer = new MutationObserver(function(mutations) {
					var found = false;
					mutations.forEach(function(mutation) {
						if (mutation.addedNodes.length) {
							mutation.addedNodes.forEach(function(node) {
								if (node.nodeType === 1) {
									if (node.classList && node.classList.contains("wpulike")) {
										found = true;
									} else if (node.querySelector && node.querySelector(".wpulike")) {
										found = true;
									}
								}
							});
						}
					});
					if (found) {
						var elements = document.querySelectorAll(".wpulike:not([data-ulike-initialized])");
						initUlike(elements);
					}
				});

				observer.observe(document.body, { childList: true, subtree: true });

				var existing = document.querySelectorAll(".wpulike:not([data-ulike-initialized])");
				initUlike(existing);
			}

			if (document.readyState === "loading") {
				document.addEventListener("DOMContentLoaded", setupObserver);
			} else {
				setupObserver();
			}
		})();
	';
}

/**
 * Enqueue WP ULike frontend JavaScript
 * Only enqueues if not already enqueued by class-wp-ulike-frontend-assets
 */
function wp_ulike_block_enqueue_frontend_scripts() {
	// Handle Pro version first (Pro >= 1.5.3 includes free scripts, so use Pro instead)
	if ( defined( 'WP_ULIKE_PRO_VERSION' ) && defined( 'WP_ULIKE_PRO_PUBLIC_URL' ) && defined( 'WP_ULIKE_PRO_DOMAIN' ) ) {
		if ( version_compare( WP_ULIKE_PRO_VERSION, '1.5.3', '>=' ) ) {
			// Check if Pro script already enqueued
			if ( wp_script_is( WP_ULIKE_PRO_DOMAIN, 'enqueued' ) || wp_script_is( WP_ULIKE_PRO_DOMAIN, 'registered' ) ) {
				// Still add initialization script even if already enqueued
				wp_add_inline_script( WP_ULIKE_PRO_DOMAIN, wp_ulike_get_iframe_init_script(), 'after' );
				return;
			}

			// Use minified version (Pro uses DEV comments, but we'll use minified for consistency)
			wp_enqueue_script( WP_ULIKE_PRO_DOMAIN, WP_ULIKE_PRO_PUBLIC_URL . '/assets/js/wp-ulike-pro.min.js', array(), WP_ULIKE_PRO_VERSION, true );

			// Match Pro's exact localization logic
			if ( function_exists( 'wp_ulike_get_option' ) && class_exists( 'WP_Ulike_Pro' ) ) {
				$localize_args = array(
					'AjaxUrl' => admin_url( 'admin-ajax.php' ),
					'Nonce'   => wp_create_nonce( WP_ULIKE_PRO_DOMAIN ),
					'ViewTracking' => array(
						'enabledTypes' => false
					)
				);

				wp_localize_script( WP_ULIKE_PRO_DOMAIN, 'UlikeProCommonConfig', $localize_args );
			}

			// Add initialization script for Pro
			wp_add_inline_script( WP_ULIKE_PRO_DOMAIN, wp_ulike_get_iframe_init_script(), 'after' );
			return;
		}
	}

	// Check if already enqueued by main class
	if ( wp_script_is( 'wp_ulike', 'enqueued' ) || wp_script_is( 'wp_ulike', 'registered' ) ) {
		// Still add initialization script even if already enqueued
		wp_add_inline_script( 'wp_ulike', wp_ulike_get_iframe_init_script(), 'after' );
		return;
	}

	// Use existing class method to load scripts
	if ( class_exists( 'wp_ulike_frontend_assets' ) ) {
		$assets = new wp_ulike_frontend_assets();
		$assets->load_scripts();
	}

	// Add initialization script
	wp_add_inline_script( 'wp_ulike', wp_ulike_get_iframe_init_script(), 'after' );
}

/**
 * Enqueue block assets for editor iframe (WordPress 6.3+)
 * Only loads if not already loaded by class-wp-ulike-frontend-assets
 */
function wp_ulike_block_assets() {
	// Only load in editor iframe, not frontend (frontend handled by class-wp-ulike-frontend-assets)
	$is_editor = is_admin() || WpUlikeInit::is_rest();

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

		$asset_file = $block_dir . '/build/index.asset.php';
		$js_file    = $block_dir . '/build/index.js';

		if ( ! file_exists( $asset_file ) || ! file_exists( $js_file ) ) {
			continue;
		}

		$asset = require $asset_file;
		$block_data = json_decode( file_get_contents( $block_json ), true );
		$block_name = isset( $block_data['name'] ) ? $block_data['name'] : '';
		$block_slug = sanitize_key( str_replace( array( 'wp-ulike/', '/' ), array( '', '-' ), $block_name ) );
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
add_action( 'enqueue_block_editor_assets', 'wp_ulike_block_editor_assets' );

/**
 * Enqueue frontend assets when block is used (fallback if main class doesn't load)
 */
function wp_ulike_block_frontend_assets() {
	if ( wp_script_is( 'wp_ulike', 'enqueued' ) || ( defined( 'WP_ULIKE_PRO_DOMAIN' ) && wp_script_is( WP_ULIKE_PRO_DOMAIN, 'enqueued' ) ) ) {
		return;
	}

	$block_names = wp_ulike_get_block_names();
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
function wp_ulike_block_render_callback( $attributes, $content = '', $block = null, $render_file = '', $block_name = '' ) {
	$wrapper_class = '';

	if ( $block instanceof WP_Block ) {
		$block_name = $block->name;
		if ( ! empty( $block->parsed_block['attrs']['className'] ) ) {
			$wrapper_class = $block->parsed_block['attrs']['className'];
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
		'block' => $block, // Pass block for context access (WordPress standard parameter name)
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
				'is_text_support' => isset( $args['is_text_support'] ) ? $args['is_text_support'] : false,
				'is_locked'       => isset( $args['is_locked'] ) ? $args['is_locked'] : false
			);
		}
	}

	return array(
		'templates' => $output,
		'default_template_name' => $default_template_name,
		'default_template_key' => $default_template_key
	);
}
