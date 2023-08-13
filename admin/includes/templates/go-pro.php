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

<div class="wrap about-wrap wp-ulike-go-pro-page full-width-layout">
	<div class="has-1-columns">
		<div class="column">
			<h1><?php esc_html_e( 'WP ULike Premium', WP_ULIKE_SLUG ); ?></h1>
			<div class="is-feature">
			<p>WP ULike Pro is our ultimate solution to cast voting to any type of content you may have on your website. With outstanding and eye-catching widgets, you can have Like and Dislike Button on all of your contents would it be a Post, Comment, BuddyPress Activities, bbPress Topics, WooCommerce products, you name it. Now you can feel your users Love for each part of your content.</p>
			<p>Besides, this plugin includes detailed logs and analytic reports of all the like and dislike activities on your pages. If you’ like to have complete control and a wide variety of options available to customize your plugins, then WP Ulike Pro is the best choice for you.</p>
			<?php
				echo wp_ulike_widget_button_callback( array(
					'label'         => esc_html__( 'Buy WP ULike Premium', WP_ULIKE_SLUG ),
					'color_name'    => 'default',
					'link'          => WP_ULIKE_PLUGIN_URI . 'pricing/?utm_source=go-pro-page&utm_campaign=gopro&utm_medium=wp-dash',
					'target'        => '_blank'
				) );
				echo wp_ulike_widget_button_callback( array(
					'label'         => esc_html__( 'More information', WP_ULIKE_SLUG ),
					'color_name'    => 'info',
					'link'          => WP_ULIKE_PLUGIN_URI . '?utm_source=go-pro-page&utm_campaign=gopro&utm_medium=wp-dash',
					'target'        => '_blank'
				) );
			?>
			</div>
		</div>
	</div>
	<div class="has-1-columns">
		<h2 class="is-section-header">Compare WP ULike Free Vs. Pro</h2>
		<div class="column">
			<table class="wp-ulike-simple-table table-bordered">
				<thead>
					<tr>
						<th>Features</th>
						<th>Free</th>
						<th>PRO</th>
					</tr>
				</thead>
				<tbody>

					<tr>
						<td>WP plugin: Boost Interactions with Top-rated WordPress Plugin.</td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
					</tr>
					<tr>
						<td>Enhance Engagement: Support for Flexible Votings Amplifies User Interaction.</td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/cross-remove.svg" alt="Cross Remove" /></td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
					</tr>
					<tr>
						<td>Dynamic User Profiles: Effortlessly Crafted via Versatile Builder Options.</td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/cross-remove.svg" alt="Cross Remove" /></td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
					</tr>
					<tr>
						<td>Streamlined Interactions: AJAX Forms for Smooth Login, Registration, Management.</td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/cross-remove.svg" alt="Cross Remove" /></td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
					</tr>
					<tr>
						<td>Stronger Security: Add Two-Factor Authentication for Enhanced Account Safety.</td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/cross-remove.svg" alt="Cross Remove" /></td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
					</tr>
					<tr>
						<td>Effortless Access: Log In Easily via Seamless Social Integration.</td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/cross-remove.svg" alt="Cross Remove" /></td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
					</tr>
					<tr>
						<td>Simplify Sharing: Intuitive Social Buttons for WordPress Content.</td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/cross-remove.svg" alt="Cross Remove" /></td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
					</tr>
					<tr>
						<td>Extensive API Support: Diverse Routes for App Integration.</td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/cross-remove.svg" alt="Cross Remove" /></td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
					</tr>
					<tr>
  						<td>Manage with Ease: Advanced Settings, Backup, Customization Choices.</td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/cross-remove.svg" alt="Cross Remove" /></td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
					</tr>
					<tr>
						<td>Manage Avatars: Simple Local Avatars for Effortless User Image Management.</td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/cross-remove.svg" alt="Cross Remove" /></td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
					</tr>
					<tr>
						<td>Boost Credibility: Generate Schema.org Markup and Star Ratings.</td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/cross-remove.svg" alt="Cross Remove" /></td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
					</tr>
					<tr>
						<td>Advanced Tracking: Utilize Progressive Log Management for Insights.</td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/cross-remove.svg" alt="Cross Remove" /></td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
					</tr>
					<tr>
						<td>Optimize Performance: Versatile Database Tools Panel for Efficiency.</td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/cross-remove.svg" alt="Cross Remove" /></td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
					</tr>
					<tr>
						<td>Elevate Site Design: Choose from our PRO Templates for Enhanced Aesthetics.</td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/cross-remove.svg" alt="Cross Remove" /></td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
					</tr>
					<tr>
  						<td>Tailored Customization: Metabox Options for Individual Content.</td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/cross-remove.svg" alt="Cross Remove" /></td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
					</tr>
					<tr>
						<td>Professional Insights: Stats Panel with Date Range and More Controls.</td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/cross-remove.svg" alt="Cross Remove" /></td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
					</tr>
					<tr>
						<td>Easy Integration: Apply Shortcodes via User-Friendly Generator.</td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/cross-remove.svg" alt="Cross Remove" /></td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
					</tr>
					<tr>
						<td>Seamless Support: Elementor Page Builder with Functional Widgets.</td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/cross-remove.svg" alt="Cross Remove" /></td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
					</tr>
					<tr>
						<td>Swift Assistance: Comprehensive Support Services for Your Needs.</td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/cross-remove.svg" alt="Cross Remove" /></td>
						<td><img class="wp-ulike-table-icon" src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/svg/checkmark.svg" alt="Checkmark" /></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	<div class="has-2-columns">
		<div class="column is-edge-to-edge has-accent-background-color">
			<img src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/elementor-support.png" alt="Elementor Support" />
		</div>
		<div class="column is-vertically-aligned-center">
			<h2>Customize Every Part of Your WP ULlike Pro Plugin</h2>
			<?php
				echo sprintf('<p>%s</p><p>%s</p>', esc_html__('Elementor is the #1 WordPress page builder. In the new version of the WP ULike PRO plugin we fully support this page builder and have a variety of widgets & controllers that make life much easier for you.',WP_ULIKE_SLUG), esc_html__('Just drag your desired widget and drop it in your Elementor sections, customize as you go and enjoy your like and dislike buttons on your contents.',WP_ULIKE_SLUG) );
			?>
		</div>
	</div>
	<div class="has-2-columns">
		<div class="column is-vertically-aligned-center">
			<h2>Boost Your SEO by Using Our Professional Schema Generator</h2>
			<p>WP ULike Pro is an innovative and powerful SEO Plugin which can manage +13 types of Schema Markups to make a better connection between your webpages and search engines. Now you can talk in search engine language and tell them which type of content you are promoting</p>
			<p>By using of our professional Schema Generator, search engines like Google, Yahoo, Bing and etc. can easily understand your content, whether it be a Media, Organization, Movie, Book or anything else.</p>
			<p>We also fully support Aggregate ratings which is a collection of reviews from users. This enables Google to feature your review ratings and attract customers with it.</p>
		</div>
		<div class="column is-edge-to-edge has-accent-background-color">
			<img src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/schema-generator.svg" alt="Schema Generator" />
		</div>
	</div>
	<div class="has-1-columns">
		<h2 class="is-section-header">Frequently Asked Questions</h2>
	</div>
	<div class="has-3-columns">
		<div class="column">
			<h3>What's the difference between WP ULike Pro vs free?</h3>
			<p>WP ULike's Free version offers limitless  possibilities. WP ULike Pro, however, empowers you with more professional tools, up/down vote support and provide you a professional stats panel. See <a target="_blank" href="https://wpulike.com/blog/wp-ulike-pro-version-vs-free/?utm_source=go-pro-page&utm_campaign=gopro&utm_medium=wp-dash" title=" WP ULike Pro Vs. Free: Which One Helps You Better?
">full comparison here</a>.</p>
		</div>
		<div class="column">
			<h3>What happens to buttons created in the free version after I upgrade?</h3>
			<p>WP ULike Pro is an extension of free version. After you upgrade to Pro, you continue just where you left off, without any interruptions. In fact, you'll be able to leverage those buttons using the Pro features.</p>
		</div>
		<div class="column">
			<h3>Do you offer a Demo version? Can I request a refund?</h3>
			<p>We don't offer a demo version of WP ULike Pro for trial. However, we have a <a target="_blank" href="https://wpulike.com/refund-rules/?utm_source=go-pro-page&utm_campaign=gopro&utm_medium=wp-dash" title="My Account">refund policy</a> which lets you cancel your purchase and get a refund during the first 30 days.</p>
		</div>
		<div class="column">
			<h3>What happens if I don't renew my license after one year? Will WP ULike Pro still work?</h3>
			<p>Your existing project will remain intact. The only difference is support, updates and access to the new premium templates which constantly added each month.</p>
		</div>
		<div class="column">
			<h3>Can I transfer the WP ULike Pro license key from one domain to another?</h3>
			<p>Of course! You can manage it in your <a target="_blank" href="https://wpulike.com/user/?utm_source=go-pro-page&utm_campaign=gopro&utm_medium=wp-dash" title="My Account">account</a> via My Account &gt; Licenses tab.</p>
		</div>
		<div class="column">
			<h3>Can I ask a question before buying?</h3>
			<p>Sure! If you have any questions about the bundle or the extensions, you can submit a pre-purchase question <a target="_blank" href="https://wpulike.com/support/?utm_source=go-pro-page&utm_campaign=gopro&utm_medium=wp-dash"  title="Support">here</a>.</p>
		</div>
	</div>
</div>