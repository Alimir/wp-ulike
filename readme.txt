=== WP ULike ===
Contributors: alimir
Donate link: http://alimir.ir
Author: Ali Mirzaei
Tags: wp ulike, wordpress youlike plugin, like button, rating, vote, voting, most liked posts, wordpress like page, wordpress like post, wordpress vote page, wordpress vote post, wp like page, wp like post, wp like plugin, buddypress like system, buddypress votes, comment like system, voting button, wordpress, statistics, stats likes, bbpress, bbPress like, WP-Translations, forums
Requires at least: 3.5
Tested up to: 4.1.1
Stable tag: 2.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WP ULike enables you to add Ajax Like button into your WP and allowing your visitors to Like/unLike the posts, comments, activities & topics.

== Description ==

WP ULike plugin allows to integrate a beautiful Ajax Like Button into your wordPress website to allow your visitors to like and unlike pages, posts, comments, buddypress activities & bbPress Topics. Its very simple to use and supports many options + full Statistics tools. And all of these are free :)

= More Information =
*   Visit Our <a target="_blank" href="http://preview.alimir.ir/developer/wp-ulike/">Home Page</a>.
*   See Online <a target="_blank" href="http://preview.alimir.ir/wp-ulike-plugin">Demo</a>.
*   Fork Us In <a target="_blank" href="https://github.com/Alimir/wp-ulike">Github</a>.
*   WP Ulike <a target="_blank" href="http://alimir.github.io/wp-ulike/">Github</a> Page.

= Features =
*   Clean Design.
*   Support full Statistics tools.
*   Ajax feature to update the data without reloading.
*   Visitors do not have to register or log in to use the Like Button.
*   Compatible with WP version 3.5 & above.
*   Added automatically with filtering options (no Code required).
*   Different logging method options.
*   Shortcode support.
*   Support custom templates with separate variables.
*   Comment likes support.
*   Supporting the date in localized format. (date_i18n)
*   Full likes logs support.
*   BuddyPress activity support.
*   Simple user like box with avatar support.
*   Custom Like-UnLike Texts fields.
*   Simple custom style with color picker settings.
*   Widget to show 'Most Liked Posts'.
*   Widget to show 'Most Liked Comments'.
*   Widget to show 'Most Liked Activities'.
*   Widget to show 'Most Liked Users' with avatar.
*   Widget to show 'Last Posts Liked By Current User'.
*   Powerful configuration panel.
*   Support RTL & language file.
*   And so on...

= Translations =
WP ULike has been translated into the following languages:

*   English (United States)
*   Persian (Iran)
*   French (France)
*   Chinese (China)
*   Chinese (Taiwan)
*   Dutch (Netherlands) 
*   Arabic
*   Portuguese (Brazil)
*   Turkish (Turkey)
*   Greek
*   Russian (Russia)
*   Spanish (Spain)
*   German (Germany)
*   Japanese
*   Romanian (Romania)
*   Slovak (Slovakia)
*   Czech (Czech Republic)
*   Hebrew (Israel)
*   Italian (Italy)
*   Polish (Poland)
*   Finnish
*   Hungarian (Hungary)
*   Lithuanian (Lithuania)
*   Indonesian (Indonesia)
*   Khmer
*   Norwegian Bokmal (Norway)
*   Portuguese (Portugal)
*   Swedish (Sweden)
*   Danish (Denmark)
*   Estonian
*   Korean (Korea)
*   Vietnamese
*   Basque
*   Bosnian (Bosnia and Herzegovina)
*   English (United Kingdom)

Would you like to help translate the plugin into more languages? [Join our WP-Translations Community](https://www.transifex.com/projects/p/wp-ulike/).

= About Author =
*   My personal website: <a href="http://about.alimir.ir" target="_blank">Ali Mirzaei</a>
*   Follow me on <a href="https://www.facebook.com/alimir1993" target="_blank">Facebook</a>
*   Catch me on twitter as @alimirir
*   And Follow My Instagram Activities With <a href="https://instagram.com/alimir71/" target="_blank">Alimir71</a>

== Installation ==

1. Open `wp-content/plugins` Folder
2. Put: `Folder: wp-ulike`
3. Activate `WP ULike` Plugin
4. Go to `WP-Admin -> WP ULike`

== Screenshots ==

1. Screenshot 1
2. Screenshot 2
3. Screenshot 3
4. Screenshot 4

== Frequently Asked Questions ==

= How To Use this plugin? =
Just install the plugin and activate the "automatic display" in plugin configuration panel. (WP ULike has three auto options for the post, comments, buddypress activities & bbPress Topics.)

Also you can use this function and shortcode for the post likes:

*   Function:
`<?php if(function_exists('wp_ulike')) wp_ulike('get'); ?>`
*   Shortcode:
`[wp_ulike]`

= How To Change Format Number Function? =
* You can adding your changes on `wp_ulike_format_number` function with a simple filter. for example, if you want to remove the "+" character you can use this filter:
<code> 
<?php
add_filter('wp_ulike_format_number','wp_ulike_new_format_number',10,3);
function wp_ulike_new_format_number($value, $num, $plus){
	if ($num >= 1000 && get_option('wp_ulike_format_number') == '1'):
	$value = round($num/1000, 2) . 'K';
	else:
	$value = $num;
	endif;
	return $value;
}
?>
</code>

= How To Get Posts Likes Number? =
* Use this function on WP Loop:
<code> 
<?php
if (function_exists('wp_ulike_get_post_likes')):
	echo wp_ulike_get_post_likes(get_the_ID());
endif;
?>
</code>

= How To Sort Most Liked Posts?  =
* Use this query on your theme:
<code> 
<?php
	$the_query = new WP_Query(array(
	'post_status' =>'published',
	'post_type' =>'post',
	'orderby' => 'meta_value_num',
	'meta_key' => '_liked',
	'paged' => (get_query_var('paged')) ? get_query_var('paged') : 1
	));
?>
</code>

= How Can I Create Custom Template In Users Liked Box?  =
* We have provided some variables in setting panel. You can use them in textarea and then save the new options. 
* Attention: `%START_WHILE%` And `%END_WHILE%` variables are very important and you should use them out of the frequent string. (Such as `<li></li>` tags sample in default template)

== Changelog ==

= 2.2 =
* Added: bbPress Likes Support + All Options & Statistics Tools.
* Added: New JQuery process with optimized methods. (wp-ulike-scripts.js)
* Added: Minified Script/CSS files.
* Added: Delete ULike Logs/Data Buttons In The Settings Page.
* Added: Portuguese (Brazil) Language File.
* Added: Turkish (Turkey) Language File.
* Added: Greek Language File.
* Added: Russian (Russia) Language File.
* Added: Spanish (Spain) Language File.
* Added: German (Germany) Language File.
* Added: Japanese Language File.
* Added: Romanian (Romania) Language File.
* Added: Slovak (Slovakia) Language File.
* Added: Czech (Czech Republic) Language File.
* Added: Hebrew (Israel) Language File.
* Added: Italian (Italy) Language File.
* Added: Polish (Poland) Language File.
* Added: Finnish Language File.
* Added: Hungarian (Hungary) Language File.
* Added: Lithuanian (Lithuania) Language File.
* Added: Indonesian (Indonesia) Language File.
* Added: Khmer Language File. Language File.
* Added: Norwegian Bokmal (Norway) Language File.
* Added: Portuguese (Portugal) Language File.
* Added: Swedish (Sweden) Language File.
* Added: Danish (Denmark) Language File.
* Added: Estonian Language File.
* Added: Korean (Korea) Language File.
* Added: Vietnamese Language File.
* Added: Basque Language File.
* Added: Bosnian (Bosnia and Herzegovina) Language File.
* Fixes: Small Bugs.
* Updated: French Language File. (Thanks WP-Translations)
* Updated: Persian language file. (Thanks Me :))

= 2.1 =
* Added: New statistics design with screen options.
* Added: New "Auto Display Position" setting in the budypress activities.
* Added: Days pick option in the statistics page.
* Added: "Screen Options" in the logs page.
* Added: Help screen tabs in the settings page.
* Added: Ajax button to remove the items from log pages.
* Added: supporting the date (date_i18n) in localized format. (Statistics Pages)
* Added: Arabic Language File. (Thanks to Ahmad Ahwazi)
* Fixes: Button visibility problem in the BuddyPress ajax loading.
* Fixes: Toggle switch problem in admin area.
* Fixes: Second parameter warning in json_encode()
* Fixes: Small Bugs
* Updated: Persian language file. (Thanks Me :))

= 2.0 =
* Added: New Statistics Page with many useful tools ("Line Charts", "Pie Chart", "Summary Stats" ) :)
* Added: New Class-based programming  :)
* Added: Custom text option for BP Add Activity (Posts/Comments).
* Added: Custom template setting for the "Users Like Box".
* Added: New option to setting the "Number Of Users" in liked box.
* Added: "Last Posts Liked By Current User" widget.
* Added: "Most liked activities" widget.
* Added: Logs menu links in the statistics page.
* Added: New option for the "only registered users" with selecting login type.
* Modified: Widgets in one packet.
* Modified: "Most Liked Users" widget to get data from all the tables (posts/comments/activities).
* Removed: Some old functions (Such as wp_ulike_reutrn_userID, get_status functions, get_user_data functions, ...)
* Updated: Plugin FAQ page.
* Updated: Persian language file. (Thanks Me :))

= 1.9 =
* Added: New logging method options.
* Added: Option for auto display position.
* Added: Most liked comments widget.
* Added: Option to return initial like button after unlike.
* Added: unlike ability for the guest users.
* Added: Comment text column to the comments logs page.
* Added: supporting the date (date_i18n) in localized format. (Logs Pages)
* Added: New changes in to the logs pages.
* Fixed: ToolTip problem with BuddyPress activities in the chrome browser.
* Updated: Plugin FAQ page.
* Updated: Persian language file. (Thanks Me :))
* Updated: Chinese language file. (Thanks cmhello)
* Updated: Dutch language file. (Thanks Joey)

= 1.8 =
* Added: New setting system with separate tabs.
* Added: Option to upload button icon.
* Added: Option to upload loading animation.
* Added: Dutch (nl_NL) language. (Thanks Joey)
* Added: Avatar size option for the users liked box.
* Modified: New names for some functions.
* Modified: plugin dislike setting to unlike.
* Updated: Persian language file.
* Updated: Chinese language file.

= 1.7 =
* Added: Buddypress likes support.
* Added: Post likes logs.
* Added: Comment likes logs.
* Added: Buddypress likes logs.
* Added: pagination for the logs pages.
* Added: FAQ document on wordpress.org
* Added: get post likes function.
* Modified: New setting menu.
* Updated: language files.

= 1.6 =
* Added: Comment likes support.
* Added: BuddyPress activity support.
* Updated: language files.

= 1.5 =
* Added: Number format option to convert numbers of Likes with string (kilobyte) format.
* Updated: Persian language.

= 1.4 =
* Added: Shortcode support.

= 1.3 =
* Added: Custom style with color picker setting. (for button and counter box)
* Added: Chinese Tradition (zh_TW) language. (Thanks to Arefly)
* Updated: Persian language.

= 1.2 =
* Added: most liked users widget.
* Added: Chinese (ZH_CN) language. (Thanks to Changmeng Hu) 

= 1.1 =
* Added: loading spinner.
* Added: new database table.
* Added: user dislike support.
* Added: Simple "user avatar box" at the bottom of every post.
* Fixes: plugin security and authentication.
* Updated: language files.

= 1.0 =
* The initial version

== Upgrade Notice ==

= 2.2 =
In this version, we made some changes in the plugin scripts (wp-ulike-scripts.js)! So, please clear your browser cache after the plugin update. Also, After the plugin update If the new database table won't fixed, try deactivating the plugin and reactivating that one at a time.

= 2.1 =
In this version, we made some changes in the plugin scripts! So, please clear your browser cache after the plugin update.

= 2.0 =
In this version, we have mixed widgets in one packet. So you should upgrade your last widgets with new one. Have fun :) 

= 1.8 =
In this version, we have made many changes on plugin functions and settings. So if you lose your last settings, try to add them again. :)

= 1.7 =
After plugin update: If the new database table won't fixed, try deactivating the plugin and reactivating that one at a time.

= 1.6 =
After plugin update: If the new database table won't fixed, try deactivating the plugin and reactivating that one at a time.

= 1.0 =
The initial version