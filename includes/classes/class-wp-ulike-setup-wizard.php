<?php
/**
 * One-time setup wizard after a fresh install.
 *
 * @package WP_ULike
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Setup_Wizard' ) ) {

	class WP_Ulike_Setup_Wizard {

		const OPTION_KEY         = 'wp_ulike_setup_wizard';
		const USER_META_KEY      = 'wp_ulike_show_setup_wizard';
		const AJAX_SAVE          = 'wp_ulike_setup_wizard_save';
		const AJAX_DISMISS       = 'wp_ulike_setup_wizard_dismiss';
		const SCRIPT_HANDLE      = 'wp-ulike-setup-wizard';
		const STYLE_HANDLE       = 'wp-ulike-setup-wizard';

		/**
		 * @return void
		 */
		public static function init() {
			if ( ! is_admin() ) {
				return;
			}

			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
			add_action( 'admin_footer', array( __CLASS__, 'render_template' ) );
			add_action( 'wp_ajax_' . self::AJAX_SAVE, array( __CLASS__, 'ajax_save' ) );
			add_action( 'wp_ajax_' . self::AJAX_DISMISS, array( __CLASS__, 'ajax_dismiss' ) );
		}

		/**
		 * Mark a true fresh install so the first admin who opens WP ULike sees setup.
		 * Safe for WP-CLI, AppSumo, hosts, and activates with no current user.
		 *
		 * @return void
		 */
		public static function mark_pending() {
			if ( 'done' === get_option( self::OPTION_KEY, '' ) ) {
				return;
			}

			update_option( self::OPTION_KEY, 'pending', false );
		}

		/**
		 * @param int $user_id Unused. Status is per-site so a second admin never sees it.
		 * @return bool
		 */
		public static function is_pending( $user_id = 0 ) {
			unset( $user_id );

			$status = get_option( self::OPTION_KEY, '' );

			if ( 'done' === $status ) {
				return false;
			}

			if ( 'pending' === $status ) {
				return true;
			}

			// First wizard build stored a user flag. Honor it once, then fold into the site option.
			$legacy_user = get_current_user_id();

			return $legacy_user && '1' === get_user_meta( $legacy_user, self::USER_META_KEY, true );
		}

		/**
		 * @param int $user_id User ID.
		 * @return bool
		 */
		protected static function user_can_setup( $user_id = 0 ) {
			if ( ! $user_id ) {
				$user_id = get_current_user_id();
			}

			return $user_id && user_can( $user_id, 'manage_options' );
		}

		/**
		 * @return bool
		 */
		protected static function should_show_on_screen() {
			if ( self::is_automated_context() || is_network_admin() ) {
				return false;
			}

			if ( ! self::is_pending() || ! self::user_can_setup() ) {
				return false;
			}

			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

			$on_plugin_screen = ( 0 === strpos( $page, 'wp-ulike' ) );

			return (bool) apply_filters( 'wp_ulike_setup_wizard_should_show', $on_plugin_screen );
		}

		/**
		 * Hosts, CLI, cron, imports, and REST must never paint admin UI.
		 *
		 * @return bool
		 */
		protected static function is_automated_context() {
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				return true;
			}

			if ( wp_doing_ajax() || wp_doing_cron() ) {
				return true;
			}

			if ( function_exists( 'wp_installing' ) && wp_installing() ) {
				return true;
			}

			if ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) {
				return true;
			}

			if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				return true;
			}

			return false;
		}

		/**
		 * @param string $hook Admin hook suffix.
		 * @return void
		 */
		public static function enqueue_assets( $hook ) {
			if ( ! self::should_show_on_screen() ) {
				return;
			}

			$css_path = WP_ULIKE_ADMIN_DIR . '/assets/css/setup-wizard.css';
			$js_path  = WP_ULIKE_ADMIN_DIR . '/assets/js/setup-wizard.js';
			$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : WP_ULIKE_VERSION;
			$js_ver   = file_exists( $js_path ) ? (string) filemtime( $js_path ) : WP_ULIKE_VERSION;

			wp_enqueue_style(
				self::STYLE_HANDLE,
				WP_ULIKE_ADMIN_URL . '/assets/css/setup-wizard.css',
				array(),
				$css_ver
			);

			wp_enqueue_script(
				self::SCRIPT_HANDLE,
				WP_ULIKE_ADMIN_URL . '/assets/js/setup-wizard.js',
				array(),
				$js_ver,
				true
			);

			wp_localize_script(
				self::SCRIPT_HANDLE,
				'wpUlikeSetupWizard',
				array(
					'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
					'saveAction'  => self::AJAX_SAVE,
					'dismissAction' => self::AJAX_DISMISS,
					'nonce'       => wp_create_nonce( self::AJAX_SAVE ),
					'afterUrl'    => self::settings_url(),
					'i18n'        => array(
						'back'        => __( 'Back', 'wp-ulike' ),
						'continue'    => __( 'Continue', 'wp-ulike' ),
						'finish'      => __( 'Start using WP ULike', 'wp-ulike' ),
						'saving'      => __( 'Saving…', 'wp-ulike' ),
						'error'       => __( 'Could not save. Please try again.', 'wp-ulike' ),
						'needOne'     => __( 'Pick at least one place so people can find the button.', 'wp-ulike' ),
						'listJoin'    => __( ', ', 'wp-ulike' ),
						'whoEveryone' => __( 'anyone can like', 'wp-ulike' ),
						'whoLoggedIn' => __( 'members only', 'wp-ulike' ),
						'likersOn'    => __( 'show who liked', 'wp-ulike' ),
						'likersOff'   => __( 'names stay hidden', 'wp-ulike' ),
						'tryNext'     => __( 'Open a matching page and try a like.', 'wp-ulike' ),
						'recap'       => __( 'Likes on %1$s. %2$s. %3$s. %4$s.', 'wp-ulike' ),
						'places'      => array(
							'post'       => __( 'posts', 'wp-ulike' ),
							'comment'    => __( 'comments', 'wp-ulike' ),
							'home'       => __( 'the homepage', 'wp-ulike' ),
							'page'       => __( 'pages', 'wp-ulike' ),
							'product'    => __( 'products', 'wp-ulike' ),
							'buddypress' => __( 'activity', 'wp-ulike' ),
							'bbpress'    => __( 'forums', 'wp-ulike' ),
						),
						'after'       => array(
							'once'  => __( 'unlike once, then lock', 'wp-ulike' ),
							'allow' => __( 'unlike anytime', 'wp-ulike' ),
							'lock'  => __( 'first like stays', 'wp-ulike' ),
						),
					),
				)
			);
		}

		/**
		 * @return void
		 */
		public static function render_template() {
			if ( ! self::should_show_on_screen() ) {
				return;
			}

			$template = WP_ULIKE_ADMIN_DIR . '/includes/templates/setup-wizard-dialog.php';

			if ( is_readable( $template ) ) {
				include $template;
			}
		}

		/**
		 * @return void
		 */
		public static function ajax_save() {
			check_ajax_referer( self::AJAX_SAVE, 'nonce' );

			if ( ! self::user_can_setup() ) {
				wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
			}

			$surfaces = isset( $_POST['surfaces'] ) ? (array) wp_unslash( $_POST['surfaces'] ) : array();
			$surfaces = array_values( array_intersect( array_map( 'sanitize_key', $surfaces ), self::allowed_surfaces() ) );

			if ( empty( $surfaces ) ) {
				wp_send_json_error( array( 'message' => 'need_one' ), 400 );
			}

			$who_can_vote = isset( $_POST['who_can_vote'] ) ? sanitize_key( wp_unslash( $_POST['who_can_vote'] ) ) : 'everyone';
			if ( ! in_array( $who_can_vote, array( 'everyone', 'logged_in' ), true ) ) {
				$who_can_vote = 'everyone';
			}

			$unlike_rule = isset( $_POST['unlike_rule'] ) ? sanitize_key( wp_unslash( $_POST['unlike_rule'] ) ) : 'once';
			if ( ! in_array( $unlike_rule, array( 'allow', 'once', 'lock' ), true ) ) {
				$unlike_rule = 'once';
			}

			$show_likers = isset( $_POST['show_likers'] ) ? sanitize_key( wp_unslash( $_POST['show_likers'] ) ) : 'no';
			$show_likers = 'yes' === $show_likers;

			self::persist_choices( $surfaces, $who_can_vote, $unlike_rule, $show_likers );
			self::clear_pending();

			wp_send_json_success();
		}

		/**
		 * @return void
		 */
		public static function ajax_dismiss() {
			self::ajax_save();
		}

		protected static function persist_choices( $surfaces, $who_can_vote, $unlike_rule, $show_likers ) {
			$settings = get_option( 'wp_ulike_settings', array() );

			if ( ! is_array( $settings ) ) {
				$settings = array();
			}

			$wants_posts    = in_array( 'post', $surfaces, true );
			$wants_home     = in_array( 'home', $surfaces, true );
			$wants_pages    = in_array( 'page', $surfaces, true );
			$wants_product  = in_array( 'product', $surfaces, true ) && class_exists( 'WooCommerce' );
			$wants_comments = in_array( 'comment', $surfaces, true );
			$wants_bp       = in_array( 'buddypress', $surfaces, true ) && function_exists( 'is_buddypress' );
			$wants_bb       = in_array( 'bbpress', $surfaces, true ) && function_exists( 'is_bbpress' );
			$wants_content  = $wants_posts || $wants_home || $wants_pages || $wants_product;

			$show_on = array();
			if ( $wants_posts || $wants_pages || $wants_product ) {
				$show_on[] = 'single';
			}
			if ( $wants_home ) {
				$show_on[] = 'home';
			}

			$post_types = array();
			if ( $wants_posts || $wants_home ) {
				$post_types[] = 'post';
			}
			if ( $wants_pages ) {
				$post_types[] = 'page';
			}
			if ( $wants_product ) {
				$post_types[] = 'product';
			}

			$posts = self::group( $settings, 'posts_group' );
			$posts['enable_auto_display'] = $wants_content;

			if ( $wants_content ) {
				$posts['auto_display_filter']            = class_exists( 'wp_ulike_setting_repo' )
					? wp_ulike_setting_repo::convertShowOnToHideFilters( $show_on )
					: array();
				$posts['auto_display_filter_post_types'] = $post_types;
			}

			$settings['posts_group'] = self::apply_vote_rules( $posts, $who_can_vote, $unlike_rule, $show_likers && $wants_content );

			$comments = self::group( $settings, 'comments_group' );
			$comments['enable_auto_display'] = $wants_comments;
			$settings['comments_group']      = self::apply_vote_rules( $comments, $who_can_vote, $unlike_rule, $show_likers && $wants_comments );

			if ( function_exists( 'is_buddypress' ) ) {
				$buddypress = self::group( $settings, 'buddypress_group' );
				$buddypress['enable_auto_display'] = $wants_bp;
				$buddypress['enable_comments']     = $wants_bp && $wants_comments;
				$settings['buddypress_group']      = self::apply_vote_rules( $buddypress, $who_can_vote, $unlike_rule, $show_likers && $wants_bp );
			}

			if ( function_exists( 'is_bbpress' ) ) {
				$bbpress = self::group( $settings, 'bbpress_group' );
				$bbpress['enable_auto_display'] = $wants_bb;
				$settings['bbpress_group']      = self::apply_vote_rules( $bbpress, $who_can_vote, $unlike_rule, $show_likers && $wants_bb );
			}

			update_option( 'wp_ulike_settings', $settings, false );
			do_action( 'wp_ulike_settings_saved', $settings );
		}

		protected static function allowed_surfaces() {
			$allowed = array( 'post', 'home', 'page', 'comment' );

			if ( function_exists( 'is_buddypress' ) ) {
				$allowed[] = 'buddypress';
			}

			if ( function_exists( 'is_bbpress' ) ) {
				$allowed[] = 'bbpress';
			}

			if ( class_exists( 'WooCommerce' ) ) {
				$allowed[] = 'product';
			}

			return $allowed;
		}

		protected static function group( $settings, $key ) {
			return isset( $settings[ $key ] ) && is_array( $settings[ $key ] ) ? $settings[ $key ] : array();
		}

		protected static function apply_vote_rules( $group, $who_can_vote, $unlike_rule, $show_likers ) {
			$group['enable_only_logged_in_users'] = $who_can_vote;
			$group['unlike_rule']                 = $unlike_rule;
			$group['enable_likers_box']           = (bool) $show_likers;

			return self::maybe_set_guest_display( $group, $who_can_vote );
		}

		protected static function settings_url() {
			if ( class_exists( 'WP_Ulike_Overview' ) ) {
				return WP_Ulike_Overview::get_settings_url( 'content-types' );
			}

			return admin_url( 'admin.php?page=wp-ulike-settings&settings-page=content-types' );
		}

		/**
		 * Set guest display only when unset. Never replace Pro “modal” or a saved value.
		 *
		 * @param array  $group        Content type group.
		 * @param string $who_can_vote everyone|logged_in.
		 * @return array
		 */
		protected static function maybe_set_guest_display( $group, $who_can_vote ) {
			if ( 'logged_in' !== $who_can_vote || array_key_exists( 'logged_out_display_type', $group ) ) {
				return $group;
			}

			$group['logged_out_display_type'] = defined( 'WP_ULIKE_PRO_VERSION' ) ? 'modal' : 'alert';

			return $group;
		}

		/**
		 * @return void
		 */
		protected static function clear_pending() {
			update_option( self::OPTION_KEY, 'done', false );

			$user_id = get_current_user_id();

			if ( $user_id ) {
				delete_user_meta( $user_id, self::USER_META_KEY );

				if ( class_exists( 'WP_Ulike_Activation_Pointer' ) ) {
					delete_user_meta( $user_id, WP_Ulike_Activation_Pointer::USER_META_KEY );
				}
			}
		}
	}
}
