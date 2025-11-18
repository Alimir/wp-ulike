=== WP ULike - All-in-One Engagement Toolkit ===
Contributors: alimir
Donate link: https://wpulike.com/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme
Author: TechnoWich
Tags: marketing, performance, analytics, feedback, conversion
Requires PHP: 7.2.5
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 4.8.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add Like & Dislike buttons to your WordPress site and turn visitors into engaged fans. Track what your audience loves with built-in analytics.

== Description ==

**Turn Visitors into Fans!** WP ULike helps you build a more engaging website by letting visitors show their appreciation with Like & Dislike buttons. See what content resonates with your audience through real-time statistics and insights.

[youtube https://www.youtube.com/watch?v=nxQto2Yj_yc]

**What You Get (Free Version):**

✨ **Like & Dislike Buttons** – Add voting buttons to posts, comments, WooCommerce products, BuddyPress activities, and bbPress topics
📊 **Statistics Dashboard** – Track engagement with detailed analytics and see your most popular content
🎨 **Customizable Templates** – Multiple button styles to match your website design
⚡ **Fast & Lightweight** – Vanilla JavaScript (no jQuery), optimized for speed and performance
🔒 **GDPR Ready** – IP anonymization, no personal data stored
🌍 **Multilingual** – 20+ language translations included
📱 **RTL Support** – Full right-to-left language support
🔌 **Plugin Integrations** – Works seamlessly with WooCommerce, BuddyPress, bbPress, Elementor, myCRED, and more
🎯 **Easy Setup** – No coding required, works out of the box
📈 **Auto Display** – Automatically adds buttons to your content, or use shortcodes for manual placement

**Why Choose WP ULike?**

* **Trusted by 80,000+ websites** – Join a community of successful sites using WP ULike
* **Zero Configuration** – Start engaging your audience in seconds
* **Performance First** – Built for speed, works with all caching plugins
* **Privacy Focused** – GDPR compliant, respects user privacy
* **Always Improving** – Regular updates with new features and improvements

**WP ULike Pro – Take It to the Next Level**

The free version gives you everything you need to start engaging your audience. WP ULike Pro extends your capabilities with professional-grade features:

🚀 **Advanced Analytics** – Deep insights with filters, date ranges, and exportable reports
🎨 **Premium Templates** – 25+ professionally designed button styles
👤 **User Profiles** – Personalized profiles showing user likes and activity
🔑 **Login/Registration Forms** – Beautiful, AJAX-powered forms with social login
📢 **Social Sharing** – One-click sharing to boost your content reach
⚡ **SEO Schema** – Structured data to improve search rankings
📡 **REST API** – Full API access for custom integrations
🎯 **Elementor Widgets** – Drag-and-drop widgets for Elementor page builder

[See the full comparison](https://wpulike.com/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme) | [View Templates](https://wpulike.com/templates/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme) | [Documentation](https://docs.wpulike.com/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme)

== Installation ==

= Minimum Requirements =

* WordPress 6.0 or greater
* PHP version 7.2 or greater
* MySQL version 5.0 or greater

= Recommended Requirements =

* PHP version 8.1 or greater
* MySQL version 5.6 or greater
* WordPress Memory limit of 64 MB or greater (128 MB or higher preferred)

= Installation =

1. Install via the WordPress Plugin Installer, or unzip the file and drop the contents into the `wp-content/plugins/` directory of your WordPress installation.
2. Activate the plugin through the 'Plugins' menu in WordPress.

For more guidance, visit our [Knowledge Base](https://docs.wpulike.com/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme).

== Screenshots ==

1. **Like & Dislike Templates – A collection of customizable like and dislike button templates for seamless integration.**
2. **Metrics Dashboard (Stats Panel) – A detailed analytics dashboard displaying voting statistics and engagement trends.**
3. **Top Trends & Insights (Stats Panel) – A visual breakdown of top-performing content with engagement insights.**
4. **Engagement Logs (Stats Panel) – A comprehensive log tracking user interactions, votes, and activity history.**
5. **Elementor Widgets – WP ULike’s interactive widgets designed for Elementor page builder integration.**
6. **Customizer Panel – Flexible customization options for fine-tuning voting styles and behaviors.**

== Frequently Asked Questions ==

= Does WP ULike work with any theme? =
Yes! WP ULike is compatible with all WordPress themes that follow coding standards. The buttons automatically adapt to your theme's styling.

= Is WP ULike compatible with other plugins? =
Absolutely! WP ULike integrates seamlessly with popular plugins including Elementor, BuddyPress, bbPress, WooCommerce, GamiPress, myCRED, and most caching plugins. If you encounter any compatibility issues, please let us know!

= What's the difference between WP ULike Free and Pro? =
The free version includes all essential features: like/dislike buttons, statistics dashboard, customizable templates, auto-display, shortcodes, and full plugin integrations. WP ULike Pro adds professional features like advanced analytics with filters, premium templates, user profiles, login/registration forms, social sharing, SEO schema, and REST API access. Both versions are fully functional—Pro simply extends your capabilities for more advanced use cases.

= Can I use WP ULike on a multisite setup? =
Yes! WP ULike works perfectly across WordPress multisite networks. Each site can have its own settings and statistics.

= Is WP ULike secure? =
Yes, absolutely. WP ULike is developed by TechnoWich following WordPress security best practices and coding standards. With over 80,000 active installations and a high WordPress.org rating, security is our top priority.

= Which web servers does WP ULike support? =
WP ULike runs smoothly on all popular web servers including Apache, Nginx, LiteSpeed, Microsoft IIS, and others. No special server configuration required.

== Changelog ==

= 4.8.0 =
* Improved: Removed jQuery dependency from front-end scripts. All JavaScript functionality has been rewritten in vanilla JavaScript for better performance, reduced file size, and faster page load times.
* Improved: Admin panel settings descriptions for better clarity and user understanding.
* Improved: Updated translation files for multiple languages.
* Fixed: Tooltip syncing and sibling element issues.
* Fixed: Various styling improvements and bug fixes.

= 4.7.11 =
* Improved: Admin panel settings descriptions for better clarity.
* Improved: Removed several outdated admin functions.
* Fixed: Correct MySQL time format is now used when inserting or updating datetime values in the database.
* Fixed: Various minor bug fixes and stability improvements.

= 4.7.10 =
* Improved: Added additional fingerprint + cookie presence check for 'cookie' & 'no limit' logging methods to further reduce the chance of automated/bot submissions without valid cookies. This was not a security vulnerability, but a preventive hardening measure to improve vote integrity.

= 4.7.9.1 =
Fixed: Critical HTML sanitization issue.

= 4.7.9 =
* Added: Global engagement insights with world map and device type charts in the stats panel. [PRO]
* Added: New action hooks to enhance data insertion and updates.
* Improved: Internationalization (i18n) support for more accurate translations.
* Improved: Optimized stats panel codebase for better performance and efficiency.
* Fixed: Addressed a security vulnerability by implementing a sanitization method.
* Fixed: Resolved various minor issues and applied performance enhancements.

= 4.7.8 =
* Improved: UI enhancements in the Stats Panel.
* Fixed: Various small issues and optimizations.

= 4.7.7 =
* Added: Support for NitroPack cache plugin to enhance caching capabilities.
* Fixed: Improved compatibility with the FlyingPress plugin.
* Fixed: Resolved several minor issues for smoother functionality.

= 4.7.6 =
* Improved: WPML compatibility to better handle multilingual setups.
* Fixed: Addressed several minor bugs to improve overall stability.

= 4.7.5 =
* Improved: Enhanced functionalities of the stats panel for better performance.
* Improved: Addressed remaining security issues by properly escaping values.
* Fixed: Resolved several minor bugs.

= 4.7.4 =
* Added: Added email verification support for newly registered users. [PRO]
* Improved: Enhanced stats panel datepicker with a modern, stylish design, including support for preset date ranges. [PRO]
* Improved: Optimized select fields for better compatibility with dark mode.
* Improved: Improved sorting functionality on the logs page.
* Fixed: Addressed several minor bugs and performance issues.

= 4.7.3 =
* Added: Dark mode support on the stats panel for a more comfortable viewing experience.
* Added: New overview data sections to show monthly & daily performance, including CAGR calculations for more detailed trend analysis. [PRO]
* Added: User roles engagement displayed through pie charts, offering insights into user interactions. [PRO]
* Added: Option to download charts in CSV, PNG, or SVG formats, making it easier to export and share data. [PRO]
* Improved: Replaced the stats charts library with a lighter and faster alternative, improving load times and performance.
* Improved: General improvements in the stats code for better reliability and accuracy.
* Fixed: Resolved all ESLint issues for cleaner code.

= 4.7.2.1 =
* Fixed: Resolved a minor bug in the stats panel that caused incorrect data display under certain conditions.

= 4.7.2 =
* Added: Launched an advanced, React-based statistics panel.
* Improved: Removed deprecated menus and scripts.
* Improved: Enhanced SQL queries for date ranges.
* Fixed: Addressed various minor bugs.

= 4.7.1 =
* Added   : Compatibility with WordPress 6.5 for seamless integration.
* Updated : Minimum PHP version requirement increased to 7.2 and WordPress to version 6.0.
* Improved: Comprehensive security enhancement with sanitization and escaping across all methods.
* Improved: Optimized queries for better performance.
* Removed : Deprecated class contents for cleaner code.
* Replaced: Some PHP methods with standard WordPress ones for better compatibility.
* Enhanced: Admin logs pagination functionality for improved usability.
* Fixed   : Minor bug fixes for a smoother experience.

= 4.7.0 =
* Fixed   : security vulnerabilities by implementing proper sanitization and escaping methods throughout the plugin codebase.
* Improved: overall performance and reliability by optimizing SQL queries and utilizing WordPress API functions where applicable.
* Updated : setting panel to enhance user experience and provide better customization options.

== Upgrade Notice ==

= 4.5.3 =
Important: If page cache is enabled on your site, clear the cache immediately after updating.