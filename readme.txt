=== WP ULike – Like Buttons, Voting & Engagement Analytics ===
Contributors: alimir
Donate link: https://wpulike.com/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme
Author: TechnoWich
Tags: like button, like, voting, engagement, popular posts
Requires PHP: 7.3.0
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 5.2.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add like buttons to posts, comments and WooCommerce products, then see what your readers love in a built-in stats dashboard. Free, no vote limits.

== Description ==

= Like buttons and post reactions for WordPress =

Most visitors read and leave without commenting, so you never learn what they liked. [WP ULike](https://wpulike.com/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme) adds one-click voting that takes no effort to use, giving you a clear signal on what resonates. No signup walls, no vote limits, no heavy setup.

Install, activate, and voting goes live on posts. Open **WP ULike → Statistics** inside WordPress to see totals, top content, growth tips, and when your audience is most active.

Optional IP anonymization, WordPress personal data export/erase, and flexible logging options help you align the plugin with your privacy policy.

WP ULike supports posts out of the box and integrates with WooCommerce, BuddyPress, and bbPress. The frontend uses vanilla JavaScript (no jQuery) and works with major caching plugins.

= Engagement analytics, built in =

Pageviews tell you who showed up. Reactions tell you who cared. WP ULike ships a real dashboard inside WordPress, so you don't need a separate analytics tool to read it.

**Free** includes Overview, growth tips, engagement reports, when-to-publish insights, and vote logs. Dark mode and focus mode are built in.

**Pro** (optional) adds who liked each post, deeper audience and content reports, and full WooCommerce store insights.

= Built for =

**Bloggers & publishers:** See which content earns appreciation, not just traffic.

**Online stores:** Learn which products visitors value.

**Communities:** Add voting to forums and activity feeds.

**Agencies & developers:** Reliable on client sites, quick to deploy, straightforward to maintain.

= Free version: complete, no limits =

The free plugin is a full voting solution, not a trial. No caps on votes, posts, or usage.

Included with free:

* Like buttons on posts via auto-display, `[wp_ulike]` shortcodes, or the **ULike Button** block
* **Top List** block and `[wp_ulike_top]` shortcode to showcase your most popular posts, comments, users, BuddyPress activities, and bbPress topics
* **Statistics dashboard** with Overview, growth tips, engagement reports, publish-timing insights, vote logs, dark mode, and focus mode
* **Button customizer** with real-time preview, four button styles, colors, icons, and RTL support
* **Auto-display settings** for posts, comments, BuddyPress, bbPress, and WooCommerce
* **Settings backup** (JSON export/import) and **Site Health** table check
* **Privacy tools** for user-linked vote data and optional IP anonymization

Regular updates, WordPress coding standards, and security best practices (nonces, hardened AJAX, and more).

= WP ULike Pro =

Pro is optional. It extends the free plugin when you need more depth or design. Both plugins stay installed and active.

Watch the 1-minute tour of what Pro adds:

https://www.youtube.com/watch?v=YAB-_dcvxLA

* **Swap likes for emoji reactions or 5-star ratings** and pick the reaction style that fits each page
* **See who liked each post** and how engaged readers really are (top fans, engagement vs page views)
* **Know what to publish next** with content intelligence, heatmaps, and top-country reports
* **Compare likes to sales** with full WooCommerce store reports
* **20 more button styles and Display Automation** to match your brand and place buttons without code
* **Rating and FAQ schema** published as schema.org markup so search engines can read your ratings

Need profiles, share buttons, bulk vote tools, Elementor widgets, or REST API? Pro includes those too. [Compare Free vs Pro](https://wpulike.com/upgrade/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme) for the full list. [Browse templates](https://wpulike.com/templates/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme) | [Documentation](https://docs.wpulike.com/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme)

== Installation ==

= Minimum Requirements =

* WordPress 6.0 or greater
* PHP 7.3.0 or greater
* MySQL 5.6 or greater

= Recommended =

* PHP 8.1+ (faster and more secure)
* MySQL 5.6+
* 128 MB WordPress memory on busier sites

= How to install =

1. **From WordPress admin:** Plugins → Add New, search "WP ULike", Install, Activate.
2. **Manual:** unzip the download, upload to `wp-content/plugins/`, activate from Plugins.

Works out of the box. [Documentation](https://docs.wpulike.com/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme) | [Support forum](https://wordpress.org/support/plugin/wp-ulike/)

== Screenshots ==

1. **Likes on posts and comments:** one click, no sign-up, and the count updates instantly.

2. **Top List block:** show your most liked posts on any page with the block or the `[wp_ulike_top]` shortcode.

3. **Statistics Overview:** totals, trends, top content, and the best time to publish, inside WordPress.

4. **Live customizer:** pick a button style and change colors, icons, and spacing with a real-time preview.

5. **Content settings:** choose where buttons appear on posts, comments, WooCommerce, BuddyPress, and bbPress.

== Frequently Asked Questions ==

= Do visitors need to register or login to like content? =
No. Voting is one click by default. You can require login in Settings if your site is members-only.

= Does WP ULike work with any theme? =
Yes. WP ULike adapts to your theme automatically. Contact support if you need help with styling.

= Is WP ULike compatible with other plugins and caching? =
Yes. Compatible with major caching plugins (WP Rocket, W3 Total Cache, LiteSpeed, and others) and popular WordPress plugins including WooCommerce, BuddyPress, bbPress, Elementor, GamiPress, and myCRED.

= Will it slow down my site? =
No. The like button loads minimal code on your pages, and vote responses are lightweight and optimized for speed. The frontend uses vanilla JavaScript with no jQuery. Statistics load in WordPress admin only and use smart caching as your site grows.

= Is WP ULike secure? =
We follow WordPress security practices: nonces, hardened AJAX, optional IP anonymization. Found a security issue? Please report it privately through [wpulike.com/support](https://wpulike.com/support/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme) rather than the public forum.

= All guest votes come from the same IP address. How do I fix it? =
Your site is behind a reverse proxy (such as nginx, Varnish, or a load balancer) that does not pass the visitor address to WordPress. Cloudflare works out of the box, and WP ULike shows a notice on its admin pages when it spots a proxy on your own network. For other proxies, add their address to `wp-config.php`, for example `define( 'WP_ULIKE_TRUSTED_PROXIES', '10.0.0.5' );`. Separate several addresses or ranges with commas, like `'10.0.0.0/8,127.0.0.1'`.

= Why can't I edit my PHP snippets? =
PHP snippets follow your site's rule for editing plugin files, so they are read-only when `DISALLOW_FILE_EDIT` or `DISALLOW_FILE_MODS` is set. Saved snippets keep running. To edit them, add `define( 'WP_ULIKE_ALLOW_PHP_SNIPPETS', true );` to `wp-config.php`.

= Can I use WP ULike on a multisite setup? =
Yes. Each site gets its own settings and stats.

= Can I customize the button appearance? =
Yes. Use the built-in customizer with real-time preview to change colors, icons, and spacing. No code required. Free ships 4 button styles, and Pro adds 20 more plus emoji reactions and star ratings, for 26 in total.

= Where do like buttons appear by default? =
On single posts. Use blocks, shortcodes, or auto-display settings to place buttons elsewhere.

= How do I show a list of my most popular posts? =
Three ways (same engine, free):
1. Add the **Top List** block in the block editor.
2. Use the shortcode anywhere (Classic Editor, Elementor, widgets): `[wp_ulike_top]`
3. Theme template tag: `<?php echo wp_ulike_get_top_content( array( 'limit' => 10, 'period' => 'weekly' ) ); ?>`

Common shortcode examples:
* `[wp_ulike_top limit="10" period="weekly"]`
* `[wp_ulike_top cat="news,3" days="7" heading="Trending"]` (category slug or ID)
* `[wp_ulike_top tag="recipes" exclude_current="1"]`
* `[wp_ulike_top taxonomy="product_cat" terms="shoes" post_type="product"]`
* `[wp_ulike_top author="12" exclude_cat="uncategorized" show_thumbnail="1"]`

Filters: `cat`, `tag`, `taxonomy`+`terms`, `exclude_cat`, `exclude_tag`, `exclude` (post IDs), `exclude_current`, `author`, `post_type`, `period` / `days` / `hours` / `date_start`+`date_end`, plus display toggles (`show_count`, `show_thumbnail`, `show_rank`, `heading`, …).

= Like buttons appear on my homepage, archive, or PostX block grid. How do I hide them? =
Open **WP ULike → Configuration → Content Types → Posts → Automatic Display** and use **Show Buttons On**. Uncheck **Home**, **Archives**, **Categories**, **Search Results**, **Tags**, or **Author Page**. Checked places are where the button appears. For block grids (PostX, etc.), also check the **Plugin & theme conflicts** section on the **Help** screen.

= Can visitors like images in a Gallery? =
Select the Gallery block and open **WP ULike** in the block sidebar. Turn on **Show a like button on each image**. Each image has its own count. Galleries left off stay unchanged.

= Can I use emoji reactions or star ratings instead of like buttons? =
Yes, with Pro. The free plugin ships 4 like-button styles. Pro adds emoji reactions and 5-star ratings as their own reaction types, so you can pick what fits each page. [Browse the templates](https://wpulike.com/templates/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme).

= Does it support dislike or down votes? =
Two-way voting uses a Pro template. The free plugin ships 4 like-button styles, and Pro adds up/down, arrow, thumb, and Positive/Negative templates when you want both signals.

= What's the difference between WP ULike Free and Pro? =
Free includes like buttons, a full statistics dashboard (Overview, reports, when-to-publish insights, logs, and tips), blocks, customizer, auto-display settings in Settings, backup, and privacy tools, with no vote limits. Pro adds emoji reactions and star ratings, who liked each post, engagement rates vs page views, full geography and content intelligence, WooCommerce sales comparisons, 20 more button styles, Display Automation, profiles, share buttons, and more. [See the full comparison](https://wpulike.com/upgrade/?utm_source=wp-repo&utm_medium=link&utm_campaign=readme). Both plugins stay active together.

= If I buy Pro, do I lose my existing likes? =
No. Pro installs beside the free plugin and reads the same data. Your existing votes, counts, and settings carry over untouched. Pro also comes with a 14-day money-back guarantee.

== External services ==

WP ULike works fully offline and stores all votes in your own database. It contacts two external services, each only when you choose to:

**Deactivation feedback (wpulike.com).** Deactivating shows an optional survey. Only if you click "Submit & deactivate" does it send your reason and note, site URL, WP ULike, WordPress and PHP versions, and days active, used only to improve the plugin. [Terms of service](https://wpulike.com/terms/) | [Privacy policy](https://wpulike.com/privacy-policy/)

**Support chat (HelpWich).** WP ULike's admin pages show a chat button. Nothing loads until you click it; after that, the HelpWich chat widget loads on WP ULike admin pages for your account and receives only what you type or send in the chat. It never loads on the front end or for visitors. [Terms of service](https://helpwich.com/terms) | [Privacy policy](https://helpwich.com/privacy)

== Changelog ==

= 5.2.7 =
* Security: Hardened public lists, voting, and guest address detection. Thanks to Patchstack for the responsible report.
* Changed: PHP snippets can be edited only by accounts that can edit plugin files. Saved snippets keep running.
* Tweak: Code brought in line with the latest WordPress coding standards.

= 5.2.6.1 =
* Fixed: Likers lists show again on buttons that use custom IDs, such as `[wp_ulike id="…"]` on items that are not posts. Hidden content (drafts, private and password-protected posts, unapproved comments, hidden activity, and private forums) stays hidden.

= 5.2.6 =
* Added: ULike Hub, the WP ULike icon in the admin bar with your new votes, the latest activity grouped by post ("Sara and 12 others liked…"), and a helpful tip. Each person sees only what is new to them (never their own votes), and the count updates live on admin screens. Turn it off in Settings → General.
* Improved: Block theme post lists that show only a title, image, or date (like the Twenty Twenty-Five photo and grid blogs) now get the automatic like button on the homepage, archives, and search.
* Improved: Choosing Home now also covers the posts listed on a static front page.
* Improved: Dismissed admin notices stay dismissed, even after a cleanup plugin clears transients.
* Fixed: Likers lists no longer show voters on drafts, private or password-protected posts, unapproved comments, or hidden activity to visitors who cannot see that item. Thanks to Nguyen Vu Thanh Danh (ngvuthdanh) for reporting it.

= 5.2.5 =
* Added: Custom CSS, the custom loading image, and PHP and JavaScript snippets now live in the Customizer, in a Settings section pinned at the bottom of the list, with a syntax-highlighted code editor and a live preview of the CSS and loading image.
* Added: Statistics lists have page numbers, and the free Statistics now include Top Members.
* Added: Collapse menu control on the Settings, Statistics, and Customizer sidebars, with hover submenus when collapsed.
* Improved: The Customizer fills the screen under the admin bar, and the sidebars share one look across Settings, Statistics, and the Customizer.
* Improved: Statistics lists keep the same order when likes are tied, so an item no longer shows up on two pages or none.
* Improved: Top posts now include posts without a title, shown as Untitled.
* Improved: Your existing custom CSS, loading image, and snippets are copied over automatically on update. The old copy is kept, and nothing is deleted.
* Improved: PHP and JavaScript snippets can only be changed by accounts that can post unfiltered HTML, and a PHP snippet that fails no longer stops the page.
* Improved: Backups include custom CSS and snippets, and backups made with older versions still restore.
* Improved: Custom CSS still loads when the Customize switch is off, and the Customize screen stays open for editing it.
* Developer: The `wp_ulike_panel_customization` filter is deprecated. Use `wp_ulike_developer_section_fields` for fields in the Customizer Settings section.

= 5.2.4 =
* Added: Gallery block control, Show a like button on each image. Each image has its own count. Existing galleries stay unchanged.
* Improved: Block post lists (Query Loop) on the homepage, archives, and search now get the same automatic button as a classic loop, including Excerpt blocks.
* Improved: The excerpts switch in Settings shows the value the site is actually using.
* Improved: Tested with WordPress 7.1.

= 5.2.3 =
* Added: A short setup on first install — choose where likes appear, who can vote, and what a second click does. Existing sites never see it.
* Added: Feature switches on Overview, so a site can turn off the parts of WP ULike it does not use. Anything that would stop recording data asks first.
* Added: Optional clean-up of cached vote data left behind by one-off guests, offered on Overview only when there is a worthwhile amount to reclaim.
* Added: A way to rebuild like and dislike totals from the stored votes — the Repair action on Overview, or `wp ulike pulse recount` for large sites.
* Improved: Overview rebuilt around storage, health, and the numbers people actually open it for, and it no longer reads the vote tables to measure storage.
* Improved: The per-visitor vote history kept for faster page loads is now capped, so it cannot grow without limit on busy sites.
* Improved: Calmer wording on the activation pointer, and it now appears outside WP ULike screens too.
* Improved: Suggestions for Pro on the dashboard wait until a site has real activity behind it.
* Improved: Optiwich heading colour is now available in Settings.
* Fixed: WP ULike is activated again on new sites added to a WordPress network.
* Fixed: An unrecognised counter status — a shortcode typo, for example — no longer leaves an unused row behind in the plugin's own table.
* Fixed: Personal data export could come back empty on sites whose tables were created with different database collations, instead of listing that person's votes.
* Fixed: The Logs screen could come back empty on sites whose older tables predate the fingerprint column.
* Fixed: Sites still on classic storage no longer write "table doesn't exist" errors to the debug log when totals are calculated.

= 5.2.2 =
* Added: `[wp_ulike_top]` shortcode (and template tag) for Top List — same popular-content filters as the block.
* Added: Editable empty-state messages in Settings for Top List / widgets, plus BuddyPress and Ultimate Member like notices (WPML-ready).
* Added: More flexible Button Customizer — dimensions, padding, margin, alignment, Icon Color (per state), and a dedicated Counter section, all with per-device (desktop / tablet / mobile) controls.
* Added: Toast Customizer for notice layout and placement (size, padding, radius, corner position, offsets) with live preview.
* Added: Likers Customizer options (including avatar size) with a corrected Likers Box / tooltip preview.
* Improved: Customizer preview safety (no accidental votes/navigation in the iframe) and clearer Active / Removed state styling.
* Improved: Settings Field Browser for translation fields, with search fixes; Optiwich RTL and panel polish.
* Improved: Faster legacy-table cleanup after Pulse storage upgrade on large sites (no slow COUNT(*) re-scans).
* Improved: Database install/repair now surfaces specific MySQL errors on Overview and Site Health instead of a generic failure.
* Fixed: Escaped quotes in translation strings used as form defaults and i18n/JSON data.

= 5.2.1 =
* Fixed: **Post Types** now correctly limits Singular auto-display. With the default Posts-only setting, like buttons no longer appear on pages (and other unlisted types).
* Improved: Clearer auto-display labels for Singular and Post Types in Settings.
* Improved: Admin RTL compatibility and several small stability fixes.

= 5.2.0 =
* Added: **Pulse**, a faster, unified like-storage engine. New installs start on Pulse automatically; existing sites keep working with an optional background upgrade.
* Added: **Storage upgrade** screen (WP ULike → Storage Upgrade) with progress bar, pause/resume, finish step, and optional cleanup of old tables. WP-CLI support: `wp ulike pulse status|start|pause|sync|verify|enable|drop-legacy`.
* Added: **Site Health** integration (Tools → Site Health) for tables, storage/migration, leftover log-table cleanup, and a copyable Info dump. Help links there for actions.
* Improved: Statistics, top lists, and vote logs now route through Pulse queries with mode-aware reads and a versioned, scoped query cache.
* Improved: GDPR export/erase now covers Pulse rows alongside legacy logs.
* Removed: Deprecated post rating value API (`wp_ulike_get_rating_value` now returns `null` with a deprecation notice).
* Fixed: Multiple stability and performance fixes across admin and frontend.
* Fixed: PHP 8 fatal (`Unknown named parameter $id`) on every vote when `wp_ulike_after_process` callbacks run. The vote saved, but AJAX returned 500.

= 5.1.2 =
* Fixed: Minor issue with the new Statistics dashboard.

= 5.1.1 =
* Added: **Statistics dashboard**. Open **WP ULike → Statistics** for a home screen with your key numbers up front.
* Added: **Overview**. See total engagement, today's activity, and how this week compares to last week.
* Added: **Growth tips**. Short suggestions based on your data (momentum, milestones, and helpful nudges as your site grows).
* Added: **Engagement reports**. Charts and top-content lists for posts, comments, and other content types you use.
* Added: **When to publish**. Peak hours and time windows so you can see when people vote most.
* Added: **WooCommerce store reports** in Statistics (Pro) when WooCommerce is active.
* Added: **Logs**. Browse and manage vote history by content type.
* Added: **Dashboard settings**. Turn announcement popups on or off, pick light/dark mode, and adjust sidebar options.
* Added: **Focus mode**. Hide the WordPress admin chrome for a distraction-free stats view.
* Improved: Faster, smoother navigation between Overview, reports, and logs.
* Improved: Clearer empty states and more reliable vote counts.
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
* Removed: Legacy settings framework from the plugin. It was previously kept for WP ULike Pro post metaboxes.
* Improved: Settings and Customizer panels now follow your WordPress admin color scheme (light, dark, and high-contrast).
* Improved: Smoother voting and admin behavior under the hood.
* Fixed: Several small issues for a more reliable experience.

= 5.0.5 =
* Added: **Help** screen under WP ULike with quick status, shortcuts, documentation links, troubleshooting, and settings backup/restore.
* Added: Optional feedback when deactivating the plugin (Plugins screen).
* Added: **Site Health** test for WP ULike database tables.
* Fixed: No longer redirects to the plugin screen right after activation.
* Fixed: Minor admin and usability issues reported by users.

= 5.0.4 =
* Added: **Top List** Gutenberg block, a ranked leaderboard for most liked posts, comments, users, BuddyPress activities, and bbPress topics.
* Added: Declared and verified compatibility with **WordPress 7.0** (Armstrong), including Block API v3 and the iframed block editor.
* Improved: Deferred frontend script loading and safer block rendering.
* Improved: Admin notices restyled for WordPress 7 with a minimal layout, fixed button underlines, and clearer text contrast.
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
= 5.2.7 =
Security update. Please update now, then purge your page cache if you use a full-page cache plugin. If guest votes behave differently afterwards, see the FAQ.

= 5.2.6 =
On block themes, post lists with Home or Archives turned on now show like buttons even without an excerpt. Purge your page cache after updating if you use a full-page cache plugin.

= 5.2.1 =
Includes Pulse, a faster like-storage engine. Counts, buttons, and stats stay the same. When ready, open **WP ULike → Storage Upgrade** to move old records in the background. Purge your page cache after updating.

= 5.1.1 =
New Statistics dashboard with Overview, engagement reports, when-to-publish insights, and vote logs. Your votes and settings stay as they are. Purge your site cache after updating if you use a full-page cache plugin.

= 5.0.6 =
Safe update. Your votes, settings, and data stay as they are. **WP ULike Pro below 2.1.4?** The old post schema box moves to **Tools → Schema Generator** in Pro 2.1.4+. Update Pro when you can; nothing breaks on the front end.

= 5.0.5 =
If you use a full-page cache plugin, purge your site cache after updating.
