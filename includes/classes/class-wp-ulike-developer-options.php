<?php
/**
 * Custom CSS, spinner, and code snippets.
 *
 * Stored apart from settings and from the visual customizer so a normal
 * settings save cannot drop them, and so the customizer's HTML sanitizer
 * cannot rewrite CSS, JavaScript, or PHP.
 *
 * @package WP_ULike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'wp_ulike_developer_options' ) ) {

	class wp_ulike_developer_options {

		const OPTION = 'wp_ulike_developer';

		const MIGRATED = 'wp_ulike_developer_migrated';

		const CSS_STYLE_ID = 'wp-ulike-custom-css-preview';

		const SPINNER_STYLE_ID = 'wp-ulike-spinner-preview';

		/**
		 * Field id => storage type.
		 */
		const SCHEMA = array(
			'custom_css'               => 'css',
			'custom_spinner'           => 'url',
			'enable_inline_custom_css' => 'bool',
			'php_snippets'             => 'php',
			'js_snippets'              => 'js',
		);

		/**
		 * @return array<string,string> Field id => storage type.
		 */
		public static function schema() {
			return self::SCHEMA;
		}

		/**
		 * @return string[]
		 */
		public static function keys() {
			return array_keys( self::schema() );
		}

		/**
		 * @param string $key Option key.
		 * @return bool
		 */
		public static function owns( $key ) {
			return is_string( $key ) && isset( self::SCHEMA[ $key ] );
		}

		/**
		 * @param string $key     Field id.
		 * @param mixed  $default Value when unset.
		 * @return mixed
		 */
		public static function get( $key, $default = null ) {
			self::migrate();

			if ( ! self::owns( $key ) ) {
				return $default;
			}

			$stored = self::read();

			if ( array_key_exists( $key, $stored ) ) {
				return $stored[ $key ];
			}

			// Migration has not finished. Keep serving the old copy.
			if ( ! get_option( self::MIGRATED ) ) {
				$legacy = self::legacy_value( $key );
				if ( null !== $legacy ) {
					return self::sanitize( self::schema()[ $key ], $legacy );
				}
			}

			return $default;
		}

		/**
		 * Values for the customizer form. Missing keys stay missing so the
		 * schema default is what the field shows.
		 *
		 * @return array<string,mixed>
		 */
		public static function form_values() {
			self::migrate();

			$stored = self::read();
			$values = array();

			$migrated = (bool) get_option( self::MIGRATED );

			foreach ( self::keys() as $key ) {
				if ( array_key_exists( $key, $stored ) ) {
					$values[ $key ] = $stored[ $key ];
					continue;
				}
				if ( $migrated ) {
					continue;
				}
				$legacy = self::legacy_value( $key );
				if ( null !== $legacy ) {
					$values[ $key ] = self::sanitize( self::schema()[ $key ], $legacy );
				}
			}

			return $values;
		}

		/**
		 * Update only the keys present in $values. An empty payload does nothing,
		 * so a customizer "reset" (which saves {}) cannot wipe snippets.
		 *
		 * @param array<string,mixed> $values Incoming values.
		 * @return void
		 */
		public static function save( $values ) {
			if ( ! is_array( $values ) ) {
				return;
			}

			$stored = self::read();

			foreach ( self::schema() as $key => $type ) {
				if ( ! array_key_exists( $key, $values ) ) {
					continue;
				}
				if ( self::is_snippet( $type ) && ! self::can_store_snippets() ) {
					continue;
				}
				$stored[ $key ] = self::sanitize( $type, $values[ $key ] );
			}

			update_option( self::OPTION, $stored, 'no' );
		}

		/**
		 * Replace the whole store. Used by backup import.
		 *
		 * @param array<string,mixed> $values Incoming values.
		 * @return void
		 */
		public static function replace( $values ) {
			$previous = self::read();
			$stored   = array();

			if ( is_array( $values ) ) {
				foreach ( self::schema() as $key => $type ) {
					if ( ! array_key_exists( $key, $values ) ) {
						continue;
					}
					if ( self::is_snippet( $type ) && ! self::can_store_snippets() ) {
						continue;
					}
					$stored[ $key ] = self::sanitize( $type, $values[ $key ] );
				}
			}

			// A save from an account that cannot publish code must not wipe snippets.
			if ( ! self::can_store_snippets() ) {
				foreach ( array( 'php_snippets', 'js_snippets' ) as $key ) {
					if ( array_key_exists( $key, $previous ) ) {
						$stored[ $key ] = $previous[ $key ];
					}
				}
			}

			update_option( self::OPTION, $stored, 'no' );

			// An import is the site's chosen state. A later pass must never copy the old options over it.
			update_option( self::MIGRATED, 1, true );
		}

		/**
		 * Pull known keys out of a values array.
		 *
		 * @param array<string,mixed> $values Modified by reference.
		 * @return array<string,mixed>|null Null when none of the keys were present.
		 */
		public static function extract( &$values ) {
			if ( ! is_array( $values ) ) {
				return null;
			}

			$found = array();

			foreach ( self::keys() as $key ) {
				if ( ! array_key_exists( $key, $values ) ) {
					continue;
				}
				$found[ $key ] = $values[ $key ];
				unset( $values[ $key ] );
			}

			return empty( $found ) ? null : $found;
		}

		/**
		 * Copy custom CSS, the spinner, and snippets into their own option.
		 *
		 * Does not write wp_ulike_settings or wp_ulike_customize. Those stay
		 * as they are, so an update cannot wipe the rest of a site's settings
		 * and a rollback can still read the old copy.
		 *
		 * @return void
		 */
		public static function migrate() {
			static $done = false;

			if ( $done ) {
				return;
			}

			$done = true;

			if ( get_option( self::MIGRATED ) ) {
				return;
			}

			$readable = false;

			foreach ( self::legacy_sources() as $source ) {
				if ( is_array( $source ) ) {
					$readable = true;
					break;
				}
			}

			// Missing options on a brand new site. An existing site whose
			// options could not be read is left unmarked so the next request retries.
			if ( ! $readable ) {
				$version = get_option( 'wp_ulike_version', '' );
				if ( false === $version || '' === $version ) {
					update_option( self::MIGRATED, 1, true );
				}
				return;
			}

			$stored  = self::read();
			$write   = $stored;
			$blocked = false;

			foreach ( self::schema() as $key => $type ) {
				// A key that already exists is the user's value, even when it is empty.
				if ( array_key_exists( $key, $stored ) ) {
					continue;
				}

				$raw = self::legacy_value( $key );

				if ( null === $raw ) {
					continue;
				}

				$clean = self::sanitize( $type, $raw );

				// Sanitizing would erase it (for example a disallowed URL scheme).
				// The old output was empty too, so leave the old copy and move on.
				if ( self::is_blank( $type, $clean ) ) {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'WP ULike: legacy value for ' . $key . ' sanitizes to empty and was not migrated.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					}
					continue;
				}

				$write[ $key ] = $clean;
			}

			if ( $write !== $stored ) {
				update_option( self::OPTION, $write, 'no' );
				$verified = self::read();

				foreach ( $write as $key => $value ) {
					if ( ! isset( self::schema()[ $key ] ) ) {
						continue;
					}
					if ( ! array_key_exists( $key, $verified ) || ! self::same_value( self::schema()[ $key ], $verified[ $key ], $value ) ) {
						$blocked = true;
						break;
					}
				}
			}

			if ( ! $blocked ) {
				update_option( self::MIGRATED, 1, true );
			}
		}

		/**
		 * Spinner selectors. Filtered so themes can extend the list in one place.
		 *
		 * @return string[]
		 */
		public static function spinner_selectors() {
			$selectors = apply_filters(
				'wp_ulike_custom_spinner_selectors',
				array(
					'.wpulike .wp_ulike_is_loading button.wp_ulike_btn',
					'#buddypress .activity-content .wpulike .wp_ulike_is_loading button.wp_ulike_btn',
					'#bbpress-forums .bbp-reply-content .wpulike .wp_ulike_is_loading button.wp_ulike_btn',
				)
			);

			if ( ! is_array( $selectors ) ) {
				return array();
			}

			$clean = array();

			foreach ( $selectors as $selector ) {
				if ( ! is_string( $selector ) ) {
					continue;
				}
				$selector = trim( wp_strip_all_tags( $selector ) );
				if ( '' !== $selector ) {
					$clean[] = $selector;
				}
			}

			return $clean;
		}

		/**
		 * Spinner rule plus custom CSS, for the front end and the preview.
		 *
		 * @return string
		 */
		public static function additional_css() {
			return self::spinner_css() . self::custom_css();
		}

		/**
		 * @return string
		 */
		public static function spinner_css() {
			$url = self::get( 'custom_spinner', '' );

			if ( ! is_string( $url ) ) {
				return '';
			}

			// Raw URL, with the characters that could leave url("...") encoded.
			$url = str_replace(
				array( '"', '\\', "\n", "\r", '(', ')' ),
				array( '%22', '%5C', '', '', '%28', '%29' ),
				esc_url_raw( $url )
			);

			if ( '' === $url ) {
				return '';
			}

			$selectors = self::spinner_selectors();

			if ( empty( $selectors ) ) {
				return '';
			}

			return implode( ',', $selectors ) . '{background-image:url("' . $url . '") !important;}';
		}

		/**
		 * @return string
		 */
		public static function custom_css() {
			$css = self::get( 'custom_css', '' );

			return is_string( $css ) ? $css : '';
		}

		/**
		 * Rules the customizer preview can apply while the form changes.
		 *
		 * Optiwich reads this list. Field ids stay here so the editor does not
		 * hardcode them.
		 *
		 * @return array<int,array<string,mixed>>
		 */
		public static function preview_styles() {
			return array(
				array(
					'id'    => self::CSS_STYLE_ID,
					'field' => 'custom_css',
				),
				array(
					'id'        => self::SPINNER_STYLE_ID,
					'field'     => 'custom_spinner',
					'property'  => 'background-image',
					'selectors' => self::spinner_selectors(),
				),
			);
		}

		/**
		 * Style tags for the customizer preview document.
		 *
		 * @return string
		 */
		public static function preview_style_tags() {
			return '<style id="' . esc_attr( self::SPINNER_STYLE_ID ) . '">' . self::spinner_css() . '</style>'
				. '<style id="' . esc_attr( self::CSS_STYLE_ID ) . '">' . self::custom_css() . '</style>';
		}

		/**
		 * Footer section under the template list. The loading image lives on Button.
		 *
		 * @return array<string,mixed>
		 */
		public static function customizer_section() {
			$snippets_on = ! class_exists( 'wp_ulike_setting_repo' ) || ! wp_ulike_setting_repo::isCodeSnippetsDisabled();
			$editable    = self::snippets_editable();

			$fields = array(
				array(
					'type'    => 'heading',
					'content' => esc_html__( 'Styles', 'wp-ulike' ),
				),
				array(
					'id'       => 'custom_css',
					'type'     => 'code_editor',
					'title'    => esc_html__( 'Custom CSS', 'wp-ulike' ),
					'desc'     => esc_html__( 'Extra CSS for the like button. It loads on every page.', 'wp-ulike' ),
					'settings' => array(
						'mode' => 'css',
						'rows' => 14,
					),
				),
				array(
					'id'      => 'enable_inline_custom_css',
					'type'    => 'switcher',
					'default' => false,
					'title'   => esc_html__( 'Load CSS in the page', 'wp-ulike' ),
					'desc'    => esc_html__( 'Print the styles in the page instead of a separate file. The file is still saved so you can inspect it.', 'wp-ulike' ),
				),
			);

			if ( $snippets_on ) {
				$locked_desc = esc_html__( 'Your account cannot change this. The saved snippet stays as it is.', 'wp-ulike' );

				$fields[] = array(
					'type'    => 'heading',
					'content' => esc_html__( 'Snippets', 'wp-ulike' ),
				);
				$fields[] = array(
					'id'       => 'php_snippets',
					'type'     => 'code_editor',
					'title'    => esc_html__( 'PHP', 'wp-ulike' ),
					'desc'     => $editable
						? esc_html__( 'Runs on your site for every visitor. Leave out the opening and closing PHP tags. For a lot of snippets, the Code Snippets plugin is a better home.', 'wp-ulike' )
						: $locked_desc,
					'settings' => array(
						'mode'     => 'php',
						'rows'     => 12,
						'readonly' => ! $editable,
					),
				);
				$fields[] = array(
					'id'       => 'js_snippets',
					'type'     => 'code_editor',
					'title'    => esc_html__( 'JavaScript', 'wp-ulike' ),
					'desc'     => $editable
						? esc_html__( 'Runs at the bottom of every page. Leave out the script tag.', 'wp-ulike' )
						: $locked_desc,
					'settings' => array(
						'mode'     => 'javascript',
						'rows'     => 12,
						'readonly' => ! $editable,
					),
				);
			}

			return array(
				'parent'        => WP_ULIKE_SLUG,
				'id'            => 'developer',
				'title'         => esc_html__( 'Settings', 'wp-ulike' ),
				'subtitle'      => $snippets_on
					? esc_html__( 'Custom CSS & snippets', 'wp-ulike' )
					: esc_html__( 'Custom CSS', 'wp-ulike' ),
				'desc'          => $snippets_on
					? esc_html__( 'Custom CSS loads on every page, for every template. Snippets run on the public site.', 'wp-ulike' )
					: esc_html__( 'Custom CSS loads on every page, for every template.', 'wp-ulike' ),
				'template'      => 'scripts',
				'icon'          => 'cog-6-tooth',
				'nav_placement' => 'footer',
				'panel'         => 'editor',
				'fields'        => self::filtered_fields( $fields ),
			);
		}

		/**
		 * Fields are filterable. The old settings filter still runs, but it is
		 * deprecated: its fields are saved with the customizer, not with settings.
		 *
		 * @param array $fields Section fields.
		 * @return array
		 */
		protected static function filtered_fields( $fields ) {
			$fields = apply_filters( 'wp_ulike_developer_section_fields', $fields );
			$fields = apply_filters_deprecated( 'wp_ulike_panel_customization', array( $fields ), '5.2.5', 'wp_ulike_developer_section_fields' );

			return is_array( $fields ) ? $fields : array();
		}

		/**
		 * @return array<string,mixed>
		 */
		protected static function read() {
			$stored = get_option( self::OPTION, array() );

			return is_array( $stored ) ? $stored : array();
		}

		/**
		 * @param string $type  Schema type.
		 * @param mixed  $value Raw value.
		 * @return mixed
		 */
		protected static function sanitize( $type, $value ) {
			switch ( $type ) {
				case 'bool':
					return wp_ulike_is_true( $value );

				case 'url':
					return is_string( $value ) ? esc_url_raw( $value ) : '';

				case 'css':
					return self::clean_text( $value, true );

				case 'php':
				case 'js':
					return self::clean_text( $value, false );
			}

			return is_string( $value ) ? self::clean_text( $value, false ) : '';
		}

		/**
		 * Keep newlines. Strip tags only for CSS, which is printed in a style tag.
		 *
		 * @param mixed $value    Raw value.
		 * @param bool  $strip_tags Whether to remove HTML tags.
		 * @return string
		 */
		protected static function clean_text( $value, $strip_tags ) {
			if ( ! is_string( $value ) ) {
				return '';
			}

			$value = wp_check_invalid_utf8( $value );
			$value = str_replace( "\0", '', $value );

			if ( $strip_tags ) {
				$value = wp_strip_all_tags( $value );
				$value = str_ireplace( '</style', '', $value );
			}

			return $value;
		}

		/**
		 * PHP and JavaScript are site code. Only an account that can publish
		 * unfiltered HTML may change them, and never while snippets are disabled.
		 *
		 * @return bool
		 */
		protected static function can_store_snippets() {
			if ( class_exists( 'wp_ulike_setting_repo' ) && wp_ulike_setting_repo::isCodeSnippetsDisabled() ) {
				return false;
			}

			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				return true;
			}

			return self::snippets_editable();
		}

		/**
		 * @return bool
		 */
		protected static function snippets_editable() {
			return current_user_can( 'unfiltered_html' );
		}

		/**
		 * @param string $type Schema type.
		 * @return bool
		 */
		protected static function is_snippet( $type ) {
			return 'php' === $type || 'js' === $type;
		}

		/**
		 * Old settings first, then the visual customizer. Neither is modified.
		 *
		 * @return array<int,mixed>
		 */
		protected static function legacy_sources() {
			static $sources = null;

			if ( null === $sources ) {
				$sources = array(
					'settings'  => get_option( 'wp_ulike_settings', null ),
					'customize' => get_option( 'wp_ulike_customize', null ),
				);
			}

			return $sources;
		}

		/**
		 * @param string $key Field id.
		 * @return mixed|null Null when the old options have no usable value.
		 */
		protected static function legacy_value( $key ) {
			$type = self::schema()[ $key ];

			foreach ( self::legacy_sources() as $name => $source ) {
				if ( ! is_array( $source ) || ! array_key_exists( $key, $source ) ) {
					continue;
				}

				$raw = $source[ $key ];

				if ( self::is_blank( $type, $raw ) ) {
					continue;
				}

				// Only the customizer copy went through the HTML sanitizer. Settings
				// were read back as stored, so decoding them would change working code.
				if ( 'customize' === $name && is_string( $raw ) ) {
					$raw = html_entity_decode( $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				}

				return $raw;
			}

			return null;
		}

		/**
		 * @param string $type  Schema type.
		 * @param mixed  $value Value.
		 * @return bool
		 */
		protected static function is_blank( $type, $value ) {
			if ( 'bool' === $type ) {
				return null === $value || '' === $value;
			}

			return ! is_string( $value ) || '' === $value;
		}

		/**
		 * @param string $type  Schema type.
		 * @param mixed  $left  Stored value.
		 * @param mixed  $right Expected value.
		 * @return bool
		 */
		protected static function same_value( $type, $left, $right ) {
			if ( 'bool' === $type ) {
				return wp_ulike_is_true( $left ) === wp_ulike_is_true( $right );
			}

			return (string) $left === (string) $right;
		}
	}
}
