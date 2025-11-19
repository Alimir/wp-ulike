<?php
/**
 * Go Pro page template
 * // @echo HEADER
 */

	// no direct access allowed
	if ( ! defined('ABSPATH') ) {
	    die();
	}

	// Dynamic values (update these as needed)
	$installations_count = '80,000+';
	$premium_templates_count = '25+';
	$schema_types_count = '13+';
?>

<div class="wrap wp-ulike-go-pro-page">

	<!-- Hero Section -->
	<div class="wp-ulike-pro-hero">
		<div class="wp-ulike-pro-hero-content">
			<div class="wp-ulike-pro-hero-badge">
				<span class="dashicons dashicons-star-filled"></span>
				<span><?php echo sprintf( esc_html__( 'Trusted by %s Websites', 'wp-ulike' ), esc_html( $installations_count ) ); ?></span>
			</div>
			<h1><?php esc_html_e( 'Unlock the Full Power of WP ULike', 'wp-ulike' ); ?></h1>
			<p class="wp-ulike-pro-hero-subtitle"><?php esc_html_e( 'Transform your website engagement with professional-grade features designed to boost interaction, improve SEO, and provide deeper insights into your audience.', 'wp-ulike' ); ?></p>
			<div class="wp-ulike-pro-hero-actions">
				<a href="<?php echo esc_url( WP_ULIKE_PLUGIN_URI . 'pricing/?utm_source=go-pro-page&utm_campaign=gopro&utm_medium=wp-dash' ); ?>" target="_blank" class="wp-ulike-pro-btn wp-ulike-pro-btn-primary wp-ulike-pro-btn-large">
					<span class="dashicons dashicons-cart"></span>
					<span><?php esc_html_e( 'Get WP ULike Pro', 'wp-ulike' ); ?></span>
				</a>
				<a href="<?php echo esc_url( WP_ULIKE_PLUGIN_URI . '?utm_source=go-pro-page&utm_campaign=gopro&utm_medium=wp-dash' ); ?>" target="_blank" class="wp-ulike-pro-btn wp-ulike-pro-btn-secondary">
					<span><?php esc_html_e( 'View All Features', 'wp-ulike' ); ?></span>
				</a>
			</div>
			<div class="wp-ulike-pro-hero-trust">
				<div class="wp-ulike-pro-trust-item">
					<span class="dashicons dashicons-yes-alt"></span>
					<span><?php esc_html_e( '30-Day Money-Back Guarantee', 'wp-ulike' ); ?></span>
				</div>
				<div class="wp-ulike-pro-trust-item">
					<span class="dashicons dashicons-yes-alt"></span>
					<span><?php esc_html_e( 'Priority Support', 'wp-ulike' ); ?></span>
				</div>
			</div>
		</div>
	</div>

	<!-- Key Features Grid -->
	<div class="wp-ulike-pro-features-section">
		<div class="wp-ulike-pro-container">
			<h2 class="wp-ulike-pro-section-title"><?php esc_html_e( 'Why Upgrade to Pro?', 'wp-ulike' ); ?></h2>
			<p class="wp-ulike-pro-section-description"><?php esc_html_e( 'Discover powerful features that help you understand your audience better and drive more engagement.', 'wp-ulike' ); ?></p>

			<div class="wp-ulike-pro-features-grid">
				<div class="wp-ulike-pro-feature-card">
					<div class="wp-ulike-pro-feature-icon">
						<span class="dashicons dashicons-chart-line"></span>
					</div>
					<h3><?php esc_html_e( 'Advanced Analytics', 'wp-ulike' ); ?></h3>
					<p><?php esc_html_e( 'Deep insights with date range filters, exportable reports, and comprehensive statistics to understand what content resonates with your audience.', 'wp-ulike' ); ?></p>
				</div>

				<div class="wp-ulike-pro-feature-card">
					<div class="wp-ulike-pro-feature-icon">
						<span class="dashicons dashicons-thumbs-up"></span>
					</div>
					<h3><?php esc_html_e( 'Up & Down Voting', 'wp-ulike' ); ?></h3>
					<p><?php esc_html_e( 'Enable flexible voting options with upvote and downvote support to get more nuanced feedback from your community.', 'wp-ulike' ); ?></p>
				</div>

				<div class="wp-ulike-pro-feature-card">
					<div class="wp-ulike-pro-feature-icon">
						<span class="dashicons dashicons-admin-users"></span>
					</div>
					<h3><?php esc_html_e( 'User Profiles', 'wp-ulike' ); ?></h3>
					<p><?php esc_html_e( 'Create personalized user profiles showcasing likes, activity, and engagement history with a versatile builder.', 'wp-ulike' ); ?></p>
				</div>

				<div class="wp-ulike-pro-feature-card">
					<div class="wp-ulike-pro-feature-icon">
						<span class="dashicons dashicons-admin-appearance"></span>
					</div>
					<h3><?php esc_html_e( 'Premium Templates', 'wp-ulike' ); ?></h3>
					<p><?php echo sprintf( esc_html__( 'Choose from %s professionally designed button templates to match your brand and elevate your site design.', 'wp-ulike' ), esc_html( $premium_templates_count ) ); ?></p>
				</div>

				<div class="wp-ulike-pro-feature-card">
					<div class="wp-ulike-pro-feature-icon">
						<span class="dashicons dashicons-admin-network"></span>
					</div>
					<h3><?php esc_html_e( 'AJAX Login & Registration', 'wp-ulike' ); ?></h3>
					<p><?php esc_html_e( 'Beautiful, seamless login and registration forms with social integration and two-factor authentication for enhanced security.', 'wp-ulike' ); ?></p>
				</div>

				<div class="wp-ulike-pro-feature-card">
					<div class="wp-ulike-pro-feature-icon">
						<span class="dashicons dashicons-share"></span>
					</div>
					<h3><?php esc_html_e( 'Social Sharing', 'wp-ulike' ); ?></h3>
					<p><?php esc_html_e( 'Boost your content reach with one-click social sharing buttons integrated seamlessly with your like buttons.', 'wp-ulike' ); ?></p>
				</div>

				<div class="wp-ulike-pro-feature-card">
					<div class="wp-ulike-pro-feature-icon">
						<span class="dashicons dashicons-search"></span>
					</div>
					<h3><?php esc_html_e( 'SEO Schema Generator', 'wp-ulike' ); ?></h3>
					<p><?php echo sprintf( esc_html__( 'Improve search rankings with structured data markup. Support for %s schema types including aggregate ratings for Google rich snippets.', 'wp-ulike' ), esc_html( $schema_types_count ) ); ?></p>
				</div>

				<div class="wp-ulike-pro-feature-card">
					<div class="wp-ulike-pro-feature-icon">
						<span class="dashicons dashicons-admin-tools"></span>
					</div>
					<h3><?php esc_html_e( 'REST API Access', 'wp-ulike' ); ?></h3>
					<p><?php esc_html_e( 'Full API access with diverse routes for custom integrations, mobile apps, and third-party services.', 'wp-ulike' ); ?></p>
				</div>

				<div class="wp-ulike-pro-feature-card">
					<div class="wp-ulike-pro-feature-icon">
						<span class="dashicons dashicons-edit"></span>
					</div>
					<h3><?php esc_html_e( 'Elementor Widgets', 'wp-ulike' ); ?></h3>
					<p><?php esc_html_e( 'Drag-and-drop widgets for Elementor page builder. Customize every part of your like buttons with visual controls.', 'wp-ulike' ); ?></p>
				</div>

				<div class="wp-ulike-pro-feature-card">
					<div class="wp-ulike-pro-feature-icon">
						<span class="dashicons dashicons-admin-settings"></span>
					</div>
					<h3><?php esc_html_e( 'Advanced Customization', 'wp-ulike' ); ?></h3>
					<p><?php esc_html_e( 'Metabox options for individual content, database optimization tools, and granular control over every aspect of your engagement system.', 'wp-ulike' ); ?></p>
				</div>

				<div class="wp-ulike-pro-feature-card">
					<div class="wp-ulike-pro-feature-icon">
						<span class="dashicons dashicons-admin-users"></span>
					</div>
					<h3><?php esc_html_e( 'Local Avatars', 'wp-ulike' ); ?></h3>
					<p><?php esc_html_e( 'Simple local avatar management for effortless user image handling without relying on external services.', 'wp-ulike' ); ?></p>
				</div>

				<div class="wp-ulike-pro-feature-card">
					<div class="wp-ulike-pro-feature-icon">
						<span class="dashicons dashicons-sos"></span>
					</div>
					<h3><?php esc_html_e( 'Priority Support', 'wp-ulike' ); ?></h3>
					<p><?php esc_html_e( 'Get comprehensive support services with faster response times and dedicated assistance for your needs.', 'wp-ulike' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<!-- Benefits Section -->
	<div class="wp-ulike-pro-benefits-section">
		<div class="wp-ulike-pro-container">
			<div class="wp-ulike-pro-benefits-grid">
				<div class="wp-ulike-pro-benefit-item">
					<div class="wp-ulike-pro-benefit-number"><?php echo esc_html( $installations_count ); ?></div>
					<div class="wp-ulike-pro-benefit-label"><?php esc_html_e( 'Active Installations', 'wp-ulike' ); ?></div>
				</div>
				<div class="wp-ulike-pro-benefit-item">
					<div class="wp-ulike-pro-benefit-number"><?php echo esc_html( $premium_templates_count ); ?></div>
					<div class="wp-ulike-pro-benefit-label"><?php esc_html_e( 'Premium Templates', 'wp-ulike' ); ?></div>
				</div>
				<div class="wp-ulike-pro-benefit-item">
					<div class="wp-ulike-pro-benefit-number"><?php echo esc_html( $schema_types_count ); ?></div>
					<div class="wp-ulike-pro-benefit-label"><?php esc_html_e( 'Schema Types', 'wp-ulike' ); ?></div>
				</div>
				<div class="wp-ulike-pro-benefit-item">
					<div class="wp-ulike-pro-benefit-number">24/7</div>
					<div class="wp-ulike-pro-benefit-label"><?php esc_html_e( 'Priority Support', 'wp-ulike' ); ?></div>
				</div>
			</div>
		</div>
	</div>

	<!-- CTA Section -->
	<div class="wp-ulike-pro-cta-section">
		<div class="wp-ulike-pro-container">
			<div class="wp-ulike-pro-cta-card">
				<h2><?php esc_html_e( 'Ready to Transform Your Website Engagement?', 'wp-ulike' ); ?></h2>
				<p><?php esc_html_e( 'Join thousands of satisfied users who have upgraded to WP ULike Pro and are seeing real results.', 'wp-ulike' ); ?></p>
				<a href="<?php echo esc_url( WP_ULIKE_PLUGIN_URI . 'pricing/?utm_source=go-pro-page&utm_campaign=gopro&utm_medium=wp-dash' ); ?>" target="_blank" class="wp-ulike-pro-btn wp-ulike-pro-btn-primary wp-ulike-pro-btn-large">
					<span class="dashicons dashicons-cart"></span>
					<span><?php esc_html_e( 'Get WP ULike Pro Now', 'wp-ulike' ); ?></span>
				</a>
				<div class="wp-ulike-pro-cta-guarantee">
					<span class="dashicons dashicons-shield"></span>
					<span><?php esc_html_e( '30-Day Money-Back Guarantee', 'wp-ulike' ); ?></span>
				</div>
			</div>
		</div>
	</div>

	<!-- FAQ Section -->
	<div class="wp-ulike-pro-faq-section">
		<div class="wp-ulike-pro-container">
			<h2 class="wp-ulike-pro-section-title"><?php esc_html_e( 'Frequently Asked Questions', 'wp-ulike' ); ?></h2>
			<p class="wp-ulike-pro-section-description"><?php esc_html_e( 'Everything you need to know about WP ULike Pro', 'wp-ulike' ); ?></p>

			<div class="wp-ulike-pro-faq-list">
				<div class="wp-ulike-pro-faq-item">
					<div class="wp-ulike-pro-faq-question">
						<h3><?php esc_html_e( 'What\'s included in WP ULike Pro?', 'wp-ulike' ); ?></h3>
						<span class="wp-ulike-pro-faq-toggle dashicons dashicons-arrow-down-alt2"></span>
					</div>
					<div class="wp-ulike-pro-faq-answer">
						<p><?php echo sprintf( esc_html__( 'WP ULike Pro includes advanced analytics with date range filters and exportable reports, up/down voting support, user profile builder, %s premium templates, AJAX login and registration forms with social integration, SEO schema generator with %s schema types, REST API access, Elementor widgets, database optimization tools, local avatars, and priority support.', 'wp-ulike' ), esc_html( $premium_templates_count ), esc_html( $schema_types_count ) ); ?></p>
					</div>
				</div>

				<div class="wp-ulike-pro-faq-item">
					<div class="wp-ulike-pro-faq-question">
						<h3><?php esc_html_e( 'Will my existing buttons work after upgrading?', 'wp-ulike' ); ?></h3>
						<span class="wp-ulike-pro-faq-toggle dashicons dashicons-arrow-down-alt2"></span>
					</div>
					<div class="wp-ulike-pro-faq-answer">
						<p><?php esc_html_e( 'Absolutely! WP ULike Pro is an extension of the free version. After upgrading, you continue exactly where you left off without any interruptions. Your existing buttons, settings, and data remain intact and will work seamlessly with all Pro features enabled.', 'wp-ulike' ); ?></p>
					</div>
				</div>

				<div class="wp-ulike-pro-faq-item">
					<div class="wp-ulike-pro-faq-question">
						<h3><?php esc_html_e( 'Do you offer a money-back guarantee?', 'wp-ulike' ); ?></h3>
						<span class="wp-ulike-pro-faq-toggle dashicons dashicons-arrow-down-alt2"></span>
					</div>
					<div class="wp-ulike-pro-faq-answer">
						<p><?php esc_html_e( 'Yes! We offer a 30-day money-back guarantee. If you\'re not completely satisfied with WP ULike Pro for any reason, simply contact us within 30 days of purchase for a full refund.', 'wp-ulike' ); ?></p>
					</div>
				</div>

				<div class="wp-ulike-pro-faq-item">
					<div class="wp-ulike-pro-faq-question">
						<h3><?php esc_html_e( 'What happens if I don\'t renew my license?', 'wp-ulike' ); ?></h3>
						<span class="wp-ulike-pro-faq-toggle dashicons dashicons-arrow-down-alt2"></span>
					</div>
					<div class="wp-ulike-pro-faq-answer">
						<p><?php esc_html_e( 'Your existing installation will continue to work perfectly. However, you won\'t receive automatic updates, new premium templates, or priority support. You can renew your license at any time to regain access to all benefits, including the latest features and improvements.', 'wp-ulike' ); ?></p>
					</div>
				</div>

				<div class="wp-ulike-pro-faq-item">
					<div class="wp-ulike-pro-faq-question">
						<h3><?php esc_html_e( 'Can I use my license on multiple websites?', 'wp-ulike' ); ?></h3>
						<span class="wp-ulike-pro-faq-toggle dashicons dashicons-arrow-down-alt2"></span>
					</div>
					<div class="wp-ulike-pro-faq-answer">
						<p><?php esc_html_e( 'License usage depends on your plan. Single-site licenses work on one domain, while multi-site licenses allow usage on multiple domains. You can manage and transfer your license between domains through your account dashboard at any time.', 'wp-ulike' ); ?></p>
					</div>
				</div>

				<div class="wp-ulike-pro-faq-item">
					<div class="wp-ulike-pro-faq-question">
						<h3><?php esc_html_e( 'What kind of support do I get with Pro?', 'wp-ulike' ); ?></h3>
						<span class="wp-ulike-pro-faq-toggle dashicons dashicons-arrow-down-alt2"></span>
					</div>
					<div class="wp-ulike-pro-faq-answer">
						<p><?php esc_html_e( 'Pro users receive priority support with faster response times, dedicated assistance for setup and customization, access to exclusive documentation and tutorials, and direct communication with our development team for feature requests and bug reports.', 'wp-ulike' ); ?></p>
					</div>
				</div>

				<div class="wp-ulike-pro-faq-item">
					<div class="wp-ulike-pro-faq-question">
						<h3><?php esc_html_e( 'Can I test Pro features before purchasing?', 'wp-ulike' ); ?></h3>
						<span class="wp-ulike-pro-faq-toggle dashicons dashicons-arrow-down-alt2"></span>
					</div>
					<div class="wp-ulike-pro-faq-answer">
						<p><?php esc_html_e( 'While we don\'t offer a trial version, our 30-day money-back guarantee allows you to try WP ULike Pro risk-free. If it doesn\'t meet your needs, you can get a full refund. You can also view our feature demos and documentation before purchasing.', 'wp-ulike' ); ?></p>
					</div>
				</div>

				<div class="wp-ulike-pro-faq-item">
					<div class="wp-ulike-pro-faq-question">
						<h3><?php esc_html_e( 'How often do you release updates?', 'wp-ulike' ); ?></h3>
						<span class="wp-ulike-pro-faq-toggle dashicons dashicons-arrow-down-alt2"></span>
					</div>
					<div class="wp-ulike-pro-faq-answer">
						<p><?php esc_html_e( 'We release regular updates with new features, improvements, and security patches. Pro users receive automatic updates and get early access to new premium templates and features. We typically release major updates monthly with minor updates and bug fixes as needed.', 'wp-ulike' ); ?></p>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Final CTA -->
	<div class="wp-ulike-pro-final-cta">
		<div class="wp-ulike-pro-container">
			<h2><?php esc_html_e( 'Still Have Questions?', 'wp-ulike' ); ?></h2>
			<p><?php esc_html_e( 'Our support team is here to help. Contact us anytime!', 'wp-ulike' ); ?></p>
			<div class="wp-ulike-pro-final-cta-actions">
				<a href="<?php echo esc_url( WP_ULIKE_PLUGIN_URI . 'support/?utm_source=go-pro-page&utm_campaign=gopro&utm_medium=wp-dash' ); ?>" target="_blank" class="wp-ulike-pro-btn wp-ulike-pro-btn-secondary">
					<span class="dashicons dashicons-email"></span>
					<span><?php esc_html_e( 'Contact Support', 'wp-ulike' ); ?></span>
				</a>
				<a href="<?php echo esc_url( WP_ULIKE_PLUGIN_URI . 'pricing/?utm_source=go-pro-page&utm_campaign=gopro&utm_medium=wp-dash' ); ?>" target="_blank" class="wp-ulike-pro-btn wp-ulike-pro-btn-primary">
					<span class="dashicons dashicons-cart"></span>
					<span><?php esc_html_e( 'Get Started Now', 'wp-ulike' ); ?></span>
				</a>
			</div>
		</div>
	</div>

</div>

<script>
(function() {
	document.addEventListener('DOMContentLoaded', function() {
		const faqItems = document.querySelectorAll('.wp-ulike-pro-faq-item');

		if (faqItems.length === 0) return;

		faqItems.forEach(item => {
			const question = item.querySelector('.wp-ulike-pro-faq-question');

			if (!question) return;

			question.addEventListener('click', function() {
				const isActive = item.classList.contains('wp-ulike-pro-faq-active');

				// Close all items
				faqItems.forEach(faqItem => {
					faqItem.classList.remove('wp-ulike-pro-faq-active');
				});

				// Open clicked item if it wasn't active
				if (!isActive) {
					item.classList.add('wp-ulike-pro-faq-active');
				}
			});
		});
	});
})();
</script>
