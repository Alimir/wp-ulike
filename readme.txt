=== WP ULike – Like & Dislike Buttons for Engagement and Feedback ===
Contributors: alimir
Donate link: https://wpulike.com/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme
Author: TechnoWich
Tags: like, engagement, feedback, voting, reactions
Requires PHP: 7.2.5
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 5.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add one-click reactions to WordPress. Stats, top lists, and privacy tools included. No signup required.

== Description ==

= Like buttons for WordPress =

Most visitors read without commenting. [WP ULike](https://wpulike.com/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme) adds one-click voting to your site so you get a clear signal on what resonates, without signup walls or heavy setup.

Install, activate, and voting goes live. Works out of the box, with a simple dashboard and optional customization when you need it.

Optional IP anonymization, WordPress personal data export/erase, and flexible logging options help you align the plugin with your privacy policy.

WP ULike supports posts out of the box and integrates with WooCommerce, BuddyPress, and bbPress. The frontend uses vanilla JavaScript (no jQuery) and works with major caching plugins.

[youtube https://www.youtube.com/watch?v=nxQto2Yj_yc]

= Built for =

**Bloggers & publishers:** See which content earns appreciation, not just traffic.

**Online stores:** Learn which products visitors value.

**Communities:** Add voting to forums and activity feeds.

**Agencies & developers:** Reliable on client sites, quick to deploy, straightforward to maintain.

= Free version: complete, no limits =

The free plugin is a full voting solution, not a trial. No caps on votes, posts, or usage. Most sites never need to upgrade.

Included with free:

* Like buttons on posts via auto-display, `[wp_ulike]` shortcodes, or the **ULike Button** block
* **Top List** block for ranked posts, comments, users, BuddyPress activities, and bbPress topics
* **Statistics dashboard** with popular content and vote counts
* **Button customizer** with live preview, multiple templates, and RTL support
* **Settings backup** (JSON export/import) and **Site Health** table check
* **Privacy tools** for user-linked vote data and optional IP anonymization

Regular updates, WordPress coding standards, and security best practices (nonces, hardened AJAX, and more).

= WP ULike Pro =

Pro is optional. It extends the free plugin when you need more depth, design, or scale. Both plugins stay installed and active.

* **Dislike buttons** and **25+ premium templates** for richer feedback and branded UI
* **Advanced analytics** with filters, date ranges, maps, device insights, and CSV/PNG/SVG exports
* **View tracking & engagement rates** to measure votes against page views
* **Schema.org markup** for star ratings and FAQ rich results
* **Display automation & bulk actions** for posts, WooCommerce, EDD, and more
* **User profiles, login forms, social login, and share buttons**
* **Elementor widgets** and **REST API** for custom builds and integrations
* **Advanced GDPR tools**, **email notifications**, and **priority support**

[Compare Free vs Pro](https://wpulike.com/upgrade/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme) | [Browse templates](https://wpulike.com/templates/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme) | [Documentation](https://docs.wpulike.com/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme)

== Installation ==

= Minimum Requirements =

* WordPress 6.0 or greater
* PHP 7.2 or greater
* MySQL 5.0 or greater

= Recommended =

* PHP 8.1+ (faster and more secure)
* MySQL 5.6+
* 128 MB WordPress memory on busier sites

= How to install =

1. **From WordPress admin:** Plugins → Add New, search "WP ULike", Install, Activate.
2. **Manual:** unzip the download, upload to `wp-content/plugins/`, activate from Plugins.

Works out of the box. [Documentation](https://docs.wpulike.com/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme) | Support

== Screenshots ==

1. **Settings panel:** pick a button style and set up voting for posts, comments, and more.

2. **Live customizer:** change colors, spacing, and typography with real-time preview.

3. **Metrics dashboard:** see what's getting votes and how engagement trends over time.

4. **Insights dashboard:** spot your best-performing content at a glance.

5. **Gutenberg blocks:** drop like buttons and Top List leaderboards from the editor.

6. **Display automation:** place buttons by rule on posts, products, and more.

== Frequently Asked Questions ==

= Do visitors need to register or login to like content? =
No. Voting is one click by default. You can require login in Settings if your site is members-only.

= Does WP ULike work with any theme? =
Yes. WP ULike adapts to your theme automatically. Contact support if you need help with styling.

= Is WP ULike compatible with other plugins and caching? =
Yes. Compatible with major caching plugins (WP Rocket, W3 Total Cache, LiteSpeed, and others) and popular WordPress plugins including WooCommerce, BuddyPress, bbPress, Elementor, GamiPress, and myCRED.

= Can I customize the button appearance? =
Yes. Use built-in templates and the live customizer. No code required. Pro adds 25+ premium templates.

= Where do like buttons appear by default? =
On single posts. Use blocks, shortcodes, or display options to place buttons elsewhere.

= What's the difference between WP ULike Free and Pro? =
Free includes the full voting experience: like buttons, dashboard, blocks, customizer, backup, and privacy tools, with no vote limits. Pro adds dislikes, advanced analytics, view tracking, premium templates, schema markup, display automation, profiles and login, Elementor widgets, REST API, advanced GDPR tools, email notifications, and priority support. Pro extends Free; both plugins stay active.

= Can I use WP ULike on a multisite setup? =
Yes. Each site gets its own settings and stats.

= Is WP ULike secure? =
We follow WordPress security practices: nonces, hardened AJAX, optional IP anonymization. Details at wpulike.com/security.

== Changelog ==

= 5.1.1 =
* Added: Redesigned **Statistics** screen — vote totals, engagement charts, top content, and an hourly activity view to see when your audience votes most.
* Improved: Statistics load faster and feel snappier when switching between views.
* Improved: More reliable vote counts across your site.
* Fixed: Several minor admin issues.

= 5.1.0 =
* Improved: Redesigned Settings and Customizer panels for better UX.
* Fixed: Several small issues for a more reliable experience.

= 5.0.7 =
* Added: One-click **Repair database tables** on Help when tables are missing.
* Improved: Frontend init batches DOM updates with `requestAnimationFrame` for AJAX-loaded content (BuddyPress, bbPress, load-more).
* Improved: Button styles resist theme width/transform conflicts; bbPress layout fixes.
* Improved: Settings import on Help now asks for confirmation before overwriting.

= 5.0.6 =
* Removed: Legacy settings framework from the plugin — previously kept for WP ULike Pro post metaboxes.
* Improved: Settings and Customizer panels now follow your WordPress admin color scheme (light, dark, and high-contrast).
* Improved: Smoother voting and admin behavior under the hood.
* Fixed: Several small issues for a more reliable experience.

= 5.0.5 =
* Added: **Help** screen under WP ULike — quick status, shortcuts, documentation links, troubleshooting, and settings backup/restore.
* Added: Optional feedback when deactivating the plugin (Plugins screen).
* Added: **Site Health** test for WP ULike database tables.
* Fixed: No longer redirects to the plugin screen right after activation.
* Fixed: Minor admin and usability issues reported by users.

= 5.0.4 =
* Added: **Top List** Gutenberg block — ranked leaderboard for most liked posts, comments, users, BuddyPress activities, and bbPress topics.
* Added: Declared and verified compatibility with **WordPress 7.0** (Armstrong), including Block API v3 and the iframed block editor.
* Improved: Deferred frontend script loading and safer block rendering.
* Improved: Admin notices restyled for WordPress 7 — minimal layout, fixed button underlines, and clearer text contrast.
* Improved: Admin notices now use vanilla JavaScript instead of inline jQuery.
* Improved: Plugin config passed to scripts via inline JSON instead of `wp_localize_script`.

= 5.0.3 =
* Added: Support for WordPress Export Personal Data and Erase Personal Data tools for votes stored under a user account.
* Added: Declared compatibility with WooCommerce’s newer order storage option, so shops using it see fewer “incompatible plugin” notices.
* Improved: Like button block updated for current Gutenberg / block editor expectations.
* Improved: Safer handling of unusually large saves in Settings and the button customizer (admin only).
* Improved: Better compatibility with recent PHP versions when the plugin loads its code.

= 5.0.2 =
* Added: Kinsta purge cache support.
* Fixed: Small sanitization improvement.

= 5.0.1 =
* Improved: Performance optimizations across page loading, statistics display, and data processing for faster response times.
* Improved: Enhanced security and reliability improvements for widgets and statistics functionality.
* Improved: Optimized user experience when viewing likers lists and activity data.
* Removed: Composer dependency replaced with native WordPress solution for improved compatibility and reduced plugin footprint.

= 5.0.0 =
* Added: Redesigned settings panel, live-preview customizer, and statistics dashboard.
* Improved: Faster frontend and admin performance; removed jQuery from frontend scripts.
* Fixed: Stability fixes across the 5.0 release.

== Upgrade Notice ==

= 5.0.6 =
Safe update — your votes, settings, and data stay as they are. **WP ULike Pro below 2.1.4?** The old post schema box moves to **Tools → Schema Generator** in Pro 2.1.4+. Update Pro when you can; nothing breaks on the front end.

= 5.0.5 =
If you use a full-page cache plugin, purge your site cache after updating.