<?php
/**
 * WP ULike purge plugin cache
 * // @echo HEADER
*/

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'wp_ulike_purge_cache' ) ) {
	/**
	 *  Class to purge third-party plugins cache
	 */
	class wp_ulike_purge_cache {


		public function purgeAll()
		{
			$this->purgeEnduranceCache();
			$this->purgeHummingbirdCache();
			$this->purgeLitespeedCache();
			$this->purgeSiteGroundCache();
			$this->purgeSwiftPerformanceCache();
			$this->purgeW3TotalCache();
			$this->purgeWPFastestCache();
			$this->purgeWPOptimizeCache();
			$this->purgeWPRocketCache();
			$this->purgeWPSuperCache();
			$this->purgeCacheEnablerCache();
			$this->purgeFlyingPressCache();
			$this->purgeNitropackCache();
		}


		public function purgeForPost($post_ids, $reffer_url = NULL)
		{
			if (!empty($post_ids)) {
				$this->purgeEnduranceCache($post_ids, $reffer_url);
				$this->purgeHummingbirdCache($post_ids, $reffer_url);
				$this->purgeLitespeedCache($post_ids, $reffer_url);
				$this->purgeSiteGroundCache($post_ids, $reffer_url);
				$this->purgeSwiftPerformanceCache($post_ids, $reffer_url);
				$this->purgeW3TotalCache($post_ids, $reffer_url);
				$this->purgeWPFastestCache($post_ids, $reffer_url);
				$this->purgeWPOptimizeCache($post_ids, $reffer_url);
				$this->purgeWPRocketCache($post_ids, $reffer_url);
				$this->purgeWPSuperCache($post_ids, $reffer_url);
				$this->purgeCacheEnablerCache($post_ids, $reffer_url);
				$this->purgeFlyingPressCache($post_ids, $reffer_url);
				$this->purgeNitropackCache($post_ids, $reffer_url);
			}
		}

		/**
		 * @see https://github.com/bluehost/endurance-page-cache/
		 */
		protected function purgeEnduranceCache($post_ids = [], $reffer_url = NULL)
		{
			// Check functionality existence
			if (!class_exists('Endurance_Page_Cache')) {
				return;
			}

			if (empty($post_ids)) {
				do_action('epc_purge');
			}
			foreach ($post_ids as $post_id) {
				$post_url = get_permalink($post_id);
				if( get_post_type( $post_id ) ){
					do_action('epc_purge_request', $post_url);
				}
				// purge reffer url if is not same as triggered post
				if( $reffer_url && ( wp_parse_url( $reffer_url ) != wp_parse_url( $post_url ) ) ){
					do_action( 'epc_purge_request', $reffer_url );
				}
			}
		}

		/**
		 * @see https://premium.wpmudev.org/docs/api-plugin-development/hummingbird-api-docs/#action-wphb_clear_page_cache
		 */
		protected function purgeHummingbirdCache($post_ids = [], $reffer_url = NULL)
		{
			// Check functionality existence
			if ( ! class_exists( '\Hummingbird\WP_Hummingbird' ) ){
				return;
			}

			if (empty($post_ids)) {
				do_action('wphb_clear_page_cache');
			}
			foreach ($post_ids as $post_id) {
				if( get_post_type( $post_id ) ){
					do_action('wphb_clear_page_cache', $post_id);
				}
			}
		}

		/**
		 * @see https://wordpress.org/plugins/litespeed-cache/
		 */
		protected function purgeLitespeedCache($post_ids = [], $reffer_url = NULL)
		{
			// Check functionality existence
			if( ! function_exists('run_litespeed_cache') ){
				return;
			}

			if (empty($post_ids)) {
				do_action('litespeed_purge_all');
			}
			foreach ($post_ids as $post_id ) {
				if( get_post_type( $post_id ) ){
					do_action('litespeed_purge_post', $post_id);
				}
				// purge reffer url if is not same as triggered post
				$post_url = get_permalink($post_id);
				if( $reffer_url && ( wp_parse_url( $reffer_url ) != wp_parse_url( $post_url ) ) ){
					do_action( 'litespeed_purge_url', $reffer_url );
				}
			}
		}

		/**
		 * @see https://wordpress.org/plugins/sg-cachepress/
		 */
		protected function purgeSiteGroundCache($post_ids = [], $reffer_url = NULL)
		{
			if (function_exists('sg_cachepress_purge_everything') && empty($post_ids)) {
				sg_cachepress_purge_everything();
			}
			if (function_exists('sg_cachepress_purge_cache')) {
				foreach ($post_ids as $post_id) {
					$post_url = get_permalink($post_id);
					if( get_post_type( $post_id ) ){
						sg_cachepress_purge_cache( $post_url );
					}
					// purge reffer url if is not same as triggered post
					if( $reffer_url && ( wp_parse_url( $reffer_url ) != wp_parse_url( $post_url ) ) ){
						sg_cachepress_purge_cache( $reffer_url );
					}
				}
			}
		}

		/**
		 * @see https://swiftperformance.io/
		 */
		protected function purgeSwiftPerformanceCache($post_ids = [], $reffer_url = NULL)
		{
			// Check functionality existence
			if (!class_exists('Swift_Performance_Cache')) {
				return;
			}

			if (empty($post_ids)) {
				\Swift_Performance_Cache::clear_all_cache();
			} else {
				foreach ($post_ids as $post_id) {
					if( get_post_type( $post_id ) ){
						\Swift_Performance_Cache::clear_post_cache($post_id);
					}
					// purge reffer url if is not same as triggered post
					$post_url = get_permalink($post_id);
					if( $reffer_url && ( wp_parse_url( $reffer_url ) != wp_parse_url( $post_url ) ) ){
						\Swift_Performance_Cache::clear_permalink_cache($reffer_url);
					}
				}
			}
		}

		/**
		 * @see https://wordpress.org/plugins/w3-total-cache/
		 */
		protected function purgeW3TotalCache($post_ids = [], $reffer_url = NULL)
		{
			if (function_exists('w3tc_flush_all') && empty($post_ids)) {
				w3tc_flush_all();
			}
			if (function_exists('w3tc_flush_post')) {
				foreach ($post_ids as $post_id) {
					if( get_post_type( $post_id ) ){
						w3tc_flush_post($post_id);
					}
					// purge reffer url if is not same as triggered post
					$post_url = get_permalink($post_id);
					if( $reffer_url && ( wp_parse_url( $reffer_url ) != wp_parse_url( $post_url ) ) ){
						w3tc_flush_url( $reffer_url );
					}
				}
			}
		}

		/**
		 * @see https://www.wpfastestcache.com/
		 */
		protected function purgeWPFastestCache($post_ids = [], $reffer_url = NULL)
		{
			// Check functionality existence
			if( ! class_exists( 'WpFastestCache' ) ){
				return;
			}

			if (empty($post_ids)) {
				do_action('wpfc_clear_all_cache');
			}
			foreach ($post_ids as $post_id) {
				if( get_post_type( $post_id ) ){
					do_action('wpfc_clear_post_cache_by_id', false, $post_id);
				}
			}
		}

		/**
		 * @see https://getwpo.com/documentation/#Purging-the-cache-from-an-other-plugin-or-theme
		 */
		protected function purgeWPOptimizeCache($post_ids = [], $reffer_url = NULL)
		{
			// Check functionality existence
			if (function_exists('WP_Optimize') && empty($post_ids)) {
				WP_Optimize()->get_page_cache()->purge();
			}

			if (class_exists('WPO_Page_Cache')) {
				foreach ($post_ids as $post_id) {
					if( get_post_type( $post_id ) ){
						\WPO_Page_Cache::delete_single_post_cache($post_id);
					}
					// purge reffer url if is not same as triggered post
					$post_url = get_permalink($post_id);
					if( $reffer_url && ( wp_parse_url( $reffer_url ) != wp_parse_url( $post_url ) ) ){
						\WPO_Page_Cache::delete_cache_by_url( $reffer_url );
					}
				}
			}
		}

		/**
		 * @see https://docs.wp-rocket.me/article/93-rocketcleanpost
		 */
		protected function purgeWPRocketCache($post_ids = [], $reffer_url = NULL)
		{
			// Check functionality existence
			if (function_exists('rocket_clean_home') && empty($post_ids)) {
				rocket_clean_home();
			}

			if (function_exists('rocket_clean_post')) {
				foreach ($post_ids as $post_id) {
					if( get_post_type( $post_id ) ){
						rocket_clean_post($post_id);
					}
					// purge reffer url if is not same as triggered post
					$post_url = get_permalink($post_id);
					if( $reffer_url && ( wp_parse_url( $reffer_url ) != wp_parse_url( $post_url ) ) ){
						rocket_clean_files( $reffer_url );
					}
				}
			}
		}

		/**
		 * @see https://wordpress.org/plugins/wp-super-cache/
		 */
		protected function purgeWPSuperCache($post_ids = [], $reffer_url = NULL)
		{
			// Check functionality existence
			if (function_exists('wp_cache_clear_cache') && empty($post_ids)) {
				wp_cache_clear_cache();
			}

			if (function_exists('wpsc_delete_post_cache')) {
				foreach ($post_ids as $post_id) {
					if( get_post_type( $post_id ) ){
						wpsc_delete_post_cache($post_id);
					}

					// purge reffer url if is not same as triggered post
					$post_url = get_permalink($post_id);
					if( $reffer_url && ( wp_parse_url( $reffer_url ) != wp_parse_url( $post_url ) ) ){
						wpsc_delete_url_cache( $reffer_url );
					}
				}
			}
		}

		/**
		 * @see https://nitropack.io/
		 */
		protected function purgeNitropackCache($post_ids = [], $reffer_url = NULL)
		{
			if (!function_exists('nitropack_invalidate') || !function_exists('nitropack_get_cacheable_object_types')) {
				return;
			}
			if (!get_option('nitropack-autoCachePurge', 1)) {
				return;
			}
			if (empty($post_ids)) {
				nitropack_invalidate(null, null, 'Invalidating all pages after user engagement (likes/dislikes) via WP ULike.');
				return;
			}
			foreach ($post_ids as $postId) {
				$cacheableTypes = nitropack_get_cacheable_object_types();
				$post = get_post($postId);
				$postType = $post->post_type ?? 'post';
				$postTitle = $post->post_title ?? '';
				if (in_array($postType, $cacheableTypes)) {
					nitropack_invalidate(null, "single:{$postId}", sprintf('Invalidating "%s" after user engagement (likes/dislikes) via WP ULike.', $postTitle));
				}
			}
		}

		/**
		 * @see https://www.keycdn.com/support/wordpress-cache-enabler-plugin
		 */
		protected function purgeCacheEnablerCache($post_ids = [], $reffer_url = NULL)
		{
			// Check functionality existence
			if( ! class_exists( 'Cache_Enabler' ) ){
				return;
			}

			if (empty($post_ids)) {
				do_action( 'cache_enabler_clear_site_cache' );
			}
			foreach ($post_ids as $post_id) {
				if( get_post_type( $post_id ) ){
					do_action( 'cache_enabler_clear_page_cache_by_post', $post_id );
				}
				// purge reffer url if is not same as triggered post
				$post_url = get_permalink($post_id);
				if( $reffer_url && ( wp_parse_url( $reffer_url ) != wp_parse_url( $post_url ) ) ){
					do_action( 'cache_enabler_clear_page_cache_by_url', $reffer_url );
				}
			}
		}

		/**
		 * @see https://docs.flying-press.com/article/220-purging-cache
		 */
		protected function purgeFlyingPressCache($post_ids = [], $reffer_url = NULL)
		{
			// Check functionality existence
			if( ! class_exists( '\FlyingPress\Purge' ) ){
				return;
			}

			if (empty($post_ids)) {
				\FlyingPress\Purge::purge_pages();
			}
			foreach ($post_ids as $post_id) {
				$post_url = get_permalink($post_id);
				if( get_post_type( $post_id ) ){
					\FlyingPress\Purge::purge_urls( [ $post_url ] );
				}
				// purge reffer url if is not same as triggered post
				if( $reffer_url && ( wp_parse_url( $reffer_url ) != wp_parse_url( $post_url ) ) ){
					\FlyingPress\Purge::purge_urls( [ $reffer_url ] );
				}
			}
		}

	}

}