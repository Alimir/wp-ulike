<?php
/**
 * About page template
 * // @echo HEADER
 */

	// no direct access allowed
	if ( ! defined('ABSPATH') ) {
	    die();
	}
?>

<div class="wrap wp-ulike-about-page">

	<!-- Hero Section -->
	<div class="wp-ulike-about-hero">
		<div class="wp-ulike-about-hero-content">
			<h1><?php echo esc_html__( 'WP ULike', 'wp-ulike' ) . ' ' . WP_ULIKE_VERSION; ?></h1>
			<p class="wp-ulike-about-hero-subtitle"><?php esc_html_e( 'Turn visitors into engaged fans. Add Like & Dislike buttons to your content and discover what your audience loves with real-time analytics.', 'wp-ulike' ); ?></p>
		</div>
	</div>

	<!-- Navigation Tabs -->
	<div class="wp-ulike-about-nav">
		<div class="wp-ulike-about-container">
			<nav class="wp-ulike-about-nav-tabs">
				<a class="wp-ulike-about-nav-tab wp-ulike-about-nav-tab-active" href="admin.php?page=wp-ulike-about">
					<span class="dashicons dashicons-info"></span>
					<span><?php esc_html_e('About','wp-ulike'); ?></span>
				</a>
				<a class="wp-ulike-about-nav-tab" href="admin.php?page=wp-ulike-settings#tab=configuration" target="_blank">
					<span class="dashicons dashicons-admin-settings"></span>
					<span><?php esc_html_e('Configuration','wp-ulike'); ?></span>
				</a>
				<a class="wp-ulike-about-nav-tab" href="<?php echo esc_url( self_admin_url( 'customize.php' ) ); ?>" target="_blank">
					<span class="dashicons dashicons-admin-appearance"></span>
					<span><?php esc_html_e('Customize','wp-ulike'); ?></span>
				</a>
				<a class="wp-ulike-about-nav-tab" href="https://wpulike.com/blog/?utm_source=about-page&utm_campaign=blog-nav&utm_medium=wp-dash" target="_blank">
					<span class="dashicons dashicons-admin-post"></span>
					<span><?php esc_html_e('Our Blog','wp-ulike'); ?></span>
				</a>
				<a class="wp-ulike-about-nav-tab wp-ulike-about-nav-tab-pro" href="https://wpulike.com/pricing/?utm_source=about-page&utm_campaign=gopro-nav&utm_medium=wp-dash" target="_blank">
					<span class="dashicons dashicons-star-filled"></span>
					<span><?php esc_html_e('Go Pro','wp-ulike'); ?></span>
				</a>
			</nav>
		</div>
	</div>

	<!-- About Section -->
	<div class="wp-ulike-about-intro-section">
		<div class="wp-ulike-about-container">
			<div class="wp-ulike-about-intro-card">
				<div class="wp-ulike-about-intro-icon">
					<span class="dashicons dashicons-heart"></span>
				</div>
				<h2><?php esc_html_e('Transform Your Website Engagement', 'wp-ulike'); ?></h2>
				<p><?php esc_html_e( 'WP ULike is a powerful WordPress engagement plugin that helps you understand your audience better. Add Like & Dislike buttons to posts, comments, WooCommerce products, BuddyPress activities, and bbPress topics. Track what content resonates with your visitors through detailed analytics and build a more engaging website.', 'wp-ulike' ); ?></p>
			</div>
		</div>
	</div>

	<!-- Problems Solved Section -->
	<div class="wp-ulike-about-problems-section">
		<div class="wp-ulike-about-container">
			<div class="wp-ulike-about-section-header">
				<h2><?php esc_html_e('What Problems Does WP ULike Solve?', 'wp-ulike'); ?></h2>
				<p><?php esc_html_e('Discover how WP ULike addresses common challenges website owners face', 'wp-ulike'); ?></p>
			</div>
			<div class="wp-ulike-about-problems-grid">
				<div class="wp-ulike-about-problem-card">
					<div class="wp-ulike-about-problem-icon">
						<span class="dashicons dashicons-chart-line"></span>
					</div>
					<h3><?php esc_html_e('Low User Engagement', 'wp-ulike'); ?></h3>
					<p><?php esc_html_e('Visitors can quickly show appreciation with one click, increasing interaction and time spent on your site.', 'wp-ulike'); ?></p>
				</div>

				<div class="wp-ulike-about-problem-card">
					<div class="wp-ulike-about-problem-icon">
						<span class="dashicons dashicons-visibility"></span>
					</div>
					<h3><?php esc_html_e('No Content Insights', 'wp-ulike'); ?></h3>
					<p><?php esc_html_e('Track which content performs best with detailed statistics and analytics to optimize your strategy.', 'wp-ulike'); ?></p>
				</div>

				<div class="wp-ulike-about-problem-card">
					<div class="wp-ulike-about-problem-icon">
						<span class="dashicons dashicons-groups"></span>
					</div>
					<h3><?php esc_html_e('Limited User Feedback', 'wp-ulike'); ?></h3>
					<p><?php esc_html_e('Get instant feedback from your audience without requiring comments or complex forms.', 'wp-ulike'); ?></p>
				</div>

				<div class="wp-ulike-about-problem-card">
					<div class="wp-ulike-about-problem-icon">
						<span class="dashicons dashicons-performance"></span>
					</div>
					<h3><?php esc_html_e('Slow Website Performance', 'wp-ulike'); ?></h3>
					<p><?php esc_html_e('Built with vanilla JavaScript (no jQuery), optimized for speed and fully compatible with caching plugins.', 'wp-ulike'); ?></p>
				</div>

				<div class="wp-ulike-about-problem-card">
					<div class="wp-ulike-about-problem-icon">
						<span class="dashicons dashicons-privacy"></span>
					</div>
					<h3><?php esc_html_e('Privacy Concerns', 'wp-ulike'); ?></h3>
					<p><?php esc_html_e('GDPR compliant with IP anonymization. No personal data stored, respecting user privacy.', 'wp-ulike'); ?></p>
				</div>

				<div class="wp-ulike-about-problem-card">
					<div class="wp-ulike-about-problem-icon">
						<span class="dashicons dashicons-admin-site-alt3"></span>
					</div>
					<h3><?php esc_html_e('Limited Content Types', 'wp-ulike'); ?></h3>
					<p><?php esc_html_e('Works with posts, comments, WooCommerce products, BuddyPress activities, bbPress topics, and more.', 'wp-ulike'); ?></p>
				</div>
			</div>
		</div>
	</div>

	<!-- Key Features Section -->
	<div class="wp-ulike-about-features-section">
		<div class="wp-ulike-about-container">
			<div class="wp-ulike-about-section-header">
				<h2><?php esc_html_e('Everything You Need to Boost Engagement', 'wp-ulike'); ?></h2>
				<p><?php esc_html_e('Powerful features designed to help you understand and engage your audience', 'wp-ulike'); ?></p>
			</div>
			<div class="wp-ulike-about-features-grid">
				<div class="wp-ulike-about-feature-card">
					<span class="dashicons dashicons-thumbs-up"></span>
					<span><?php esc_html_e('Like & Dislike buttons for all content types', 'wp-ulike'); ?></span>
				</div>
				<div class="wp-ulike-about-feature-card">
					<span class="dashicons dashicons-chart-bar"></span>
					<span><?php esc_html_e('Real-time statistics and analytics dashboard', 'wp-ulike'); ?></span>
				</div>
				<div class="wp-ulike-about-feature-card">
					<span class="dashicons dashicons-admin-appearance"></span>
					<span><?php esc_html_e('Multiple customizable button templates', 'wp-ulike'); ?></span>
				</div>
				<div class="wp-ulike-about-feature-card">
					<span class="dashicons dashicons-performance"></span>
					<span><?php esc_html_e('Fast & lightweight (vanilla JavaScript, no jQuery)', 'wp-ulike'); ?></span>
				</div>
				<div class="wp-ulike-about-feature-card">
					<span class="dashicons dashicons-shield"></span>
					<span><?php esc_html_e('GDPR ready with IP anonymization', 'wp-ulike'); ?></span>
				</div>
				<div class="wp-ulike-about-feature-card">
					<span class="dashicons dashicons-editor-rtl"></span>
					<span><?php esc_html_e('Full RTL (right-to-left) language support', 'wp-ulike'); ?></span>
				</div>
				<div class="wp-ulike-about-feature-card">
					<span class="dashicons dashicons-admin-plugins"></span>
					<span><?php esc_html_e('Seamless integration with WooCommerce, BuddyPress, bbPress, Elementor', 'wp-ulike'); ?></span>
				</div>
				<div class="wp-ulike-about-feature-card">
					<span class="dashicons dashicons-yes-alt"></span>
					<span><?php esc_html_e('Zero configuration required - works out of the box', 'wp-ulike'); ?></span>
				</div>
			</div>
		</div>
	</div>

	<!-- Quick Links Section -->
	<div class="wp-ulike-about-links-section">
		<div class="wp-ulike-about-container">
			<div class="wp-ulike-about-section-header">
				<h2><?php esc_html_e('Get Help & Resources', 'wp-ulike'); ?></h2>
				<p><?php esc_html_e('We\'re here to help you get the most out of WP ULike', 'wp-ulike'); ?></p>
			</div>
			<div class="wp-ulike-about-links-grid">
				<div class="wp-ulike-about-link-card">
					<div class="wp-ulike-about-link-icon">
						<span class="dashicons dashicons-sos"></span>
					</div>
					<h3><?php esc_html_e('Need Some Help?','wp-ulike'); ?></h3>
					<p><?php esc_html_e('We would love to be of any assistance.','wp-ulike'); ?></p>
					<a href="https://wpulike.com/support/?utm_source=about-page&utm_campaign=support-column&utm_medium=wp-dash" target="_blank" class="wp-ulike-about-btn wp-ulike-about-btn-primary">
						<span class="dashicons dashicons-email"></span>
						<span><?php esc_html_e( 'Send Ticket', 'wp-ulike' ); ?></span>
					</a>
				</div>

				<div class="wp-ulike-about-link-card">
					<div class="wp-ulike-about-link-icon">
						<span class="dashicons dashicons-book"></span>
					</div>
					<h3><?php esc_html_e('Documentation','wp-ulike'); ?></h3>
					<p><?php esc_html_e('Learn about any aspect of WP ULike Plugin.','wp-ulike'); ?></p>
					<a href="https://docs.wpulike.com/?utm_source=about-page&utm_campaign=document-column&utm_medium=wp-dash" target="_blank" class="wp-ulike-about-btn wp-ulike-about-btn-secondary">
						<span class="dashicons dashicons-arrow-right-alt"></span>
						<span><?php esc_html_e( 'Start Reading', 'wp-ulike' ); ?></span>
					</a>
				</div>

				<div class="wp-ulike-about-link-card">
					<div class="wp-ulike-about-link-icon">
						<span class="dashicons dashicons-star-filled"></span>
					</div>
					<h3><?php esc_html_e('Rate Us','wp-ulike'); ?></h3>
					<p><?php esc_html_e('By spreading the love, we can push WP ULike forward.','wp-ulike'); ?></p>
					<a href="https://wordpress.org/support/plugin/wp-ulike/reviews/?filter=5" target="_blank" class="wp-ulike-about-btn wp-ulike-about-btn-secondary">
						<span class="dashicons dashicons-arrow-right-alt"></span>
						<span><?php esc_html_e( 'Submit Review', 'wp-ulike' ); ?></span>
					</a>
				</div>
			</div>
		</div>
	</div>

</div>
