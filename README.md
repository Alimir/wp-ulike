# [WP ULike](https://wpulike.com/?utm_source=github-repo&utm_medium=link&utm_campaign=readme) #

### WP ULike GitHub Repository

If you’re looking for one of the best and fastest ways to add like and dislike functionality to your WordPress website, then the WP ULike plugin is for you! WP ULike is our ultimate solution to cast voting to any type of content you may have on your website. With outstanding and eye-catching widgets, you can have Like and Dislike Button on all of your contents would it be a Post, Comment, Activities, Forum Topics, WooCommerce products, you name it. Now you can feel your users Love :heart: for each part of your work.

It's time for **[WP ULike](https://wpulike.com/?utm_source=github-repo&utm_medium=link&utm_campaign=readme)**.

## Features
*   Clean Design + Some standard eye-catching templates.
*   Professional Schema.org generator for each post type. [PRO]
*   Full Elementor Page Builder Support. [PRO]
*   Dislike button support with +8 creative templates. [PRO]
*   Display the likers of each button in linear or pop-up mode.
*   Simple, Stylish and user-friendly settings to easily customize your plugin.
*   Extract detailed reports and beautiful, useful and simple charts in an instant.
*   Support Custom Post Types, Comments, Activities & Topics.
*   Using various hooks and functions, you can easily customize this plugin.
*   We’re light-weight, fast, responsive and compatible with Google Schemas.
*   Full myCRED (points management system) Points Support.
*   Supporting UltimateMember & BuddyPress Profiles.
*   Ajax feature to update the data without reloading.
*   Visitors do not have to register or log in to use the Like Button.
*   Added automatically with filtering options (no Code required).
*   Different logging method options. (Cookie, IP, Username)
*   Notifications System. (Custom toast messages after each activity)
*   Flexible Shortcode with variable support.
*   Supporting the date in a localized format. (date_i18n)
*   BuddyPress adds activity & notifications support.
*   Simple custom style with color picker settings.
*   Support RTL & +20 language files.

## More Information ##

*   Visit Our [Home Page](https://wpulike.com/?utm_source=github-repo&utm_medium=link&utm_campaign=readme).
*   See Online [Demo](https://wpulike.com/templates/?utm_source=github-repo&utm_medium=link&utm_campaign=readme).
*   For documentation and tutorials go to our [Documentation](https://docs.wpulike.com/?utm_source=github-repo&utm_medium=link&utm_campaign=readme).
*   Fork Us In [Github](https://github.com/Alimir/wp-ulike).

## Installation ##

For detailed setup instructions, visit the official [Documentation](https://wordpress.org/plugins/wp-ulike/installation/) page.

1. You can clone the GitHub repository: `https://github.com/Alimir/wp-ulike.git`
2. Or download it directly as a ZIP file: `https://github.com/Alimir/wp-ulike/archive/master.zip`

This will download the latest developer copy of WP ULike.

## How To Use this plugin? ##
After installing and activating the plugin, go to the Settings panel and enable the "Auto Display" option for your target section (Currently for: Posts, Comments, BuddyPress activities & bbPress Topics.). Otherwise, you can use the manual way.

In order to insert Like button inside a content use the following shortcode:
### Display CTA button ###
```
[wp_ulike]
```
**Parameters ( attributes ):**
* for (string) - select button type (Availabe Values: `post`, `comment`, `activity`, `topic`)
* id (integer) - select specific item ID. (For manual usage)
* button_type (string) - Set Button Type (Availabe Values: `image`, `text`)
* style (string) - Choose the default template from the available list.
* wrapper_class (string) - Extra Wrapper class

**Using shortcode in the PHP template:**
```php
echo do_shortcode('[wp_ulike for="post" id="1" style="wpulike-heart"]');
```

## How To Change The Counter Format? ##
Just add a filter on `wp_ulike_format_number`. e.g. If you want to remove `+` character, you need to make use of the sample code below:
```php
add_filter('wp_ulike_format_number','wp_ulike_new_format_number',10,3);
function wp_ulike_new_format_number($value, $num, $plus){
	if ($num >= 1000 && get_option('wp_ulike_format_number') == '1'):
	$value = round($num/1000, 2) . 'K';
	else:
	$value = $num;
	endif;
	return $value;
}
```

## How To Remove "0" Count If There Are No Likes? ##
Make use of the `wp_ulike_count_box_template` filter as shown in the sample code below:
```php
add_filter('wp_ulike_count_box_template', 'wp_ulike_change_my_count_box_template', 10, 2);
function wp_ulike_change_my_count_box_template($string, $counter) {
	$num = preg_replace("/[^0-9,.]/", "", $counter);
	if($num == 0) return;
	else return $string;
}
```

## How To Change The Login Alert Template? ##
Make use of the `wp_ulike_login_alert_template` filter as shown in the sample code below:
```php
add_filter('wp_ulike_login_alert_template', 'wp_ulike_change_login_alert_template', 10);
function wp_ulike_change_login_alert_template(){
	return '<p class="alert alert-info fade in" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>Please login to your account! :)</p>';
}
```

## How To Get Post Likes Number? ##
Make use of the following function in WP Loop:
```php
if (function_exists('wp_ulike_get_post_likes')):
	echo wp_ulike_get_post_likes(get_the_ID());
endif;
```

## How To Get Comment Likes Number? ##
Make use of the following function in your comments loop:
```php
if (function_exists('wp_ulike_get_comment_likes')):
	echo wp_ulike_get_comment_likes(get_comment_ID());
endif;
```

## How To Sort Most Liked Posts?  ##
Make use of the following query on a loop:
```php
/**
 * Get most liked posts in query
 *
 * @param integer $numberposts		The number of items
 * @param array|string $post_type	Select post type
 * @param string $method			keep this as default value (post, comment, activity, topic)
 * @param string $period			Date peroid (all|today|yeterday|week|month|year)
 * @param string $status			Log status (like|unlike|dislike|undislike)
 * @return WP_Post[]|int[] 			Array of post objects or post IDs.
 */
$wp_query = wp_ulike_get_most_liked_posts( 10, array( 'post' ), 'post', 'all', 'like' );
```

## How Can I Create Custom Template In Users Liked Box?  ##
We have provided some variables in setting panel. You can use them in textarea and then save the new options.
Attention: `%START_WHILE%` And `%END_WHILE%` variables are very important and you should use them out of the frequent string. (Such as `<li></li>` tags sample in default template)

## Receive HTTP ERROR 500 on WP ULike > Statistics   ##
Increasing Your WordPress Memory Limit in wp-config.php to fix this error. It is located in your WordPress site's root folder, and you will need to use an FTP client or file manager in your web hosting control panel.
Next, you need to paste this code in wp-config.php file just before the line that says `That's all, stop editing! Happy blogging.`
```php
define( 'WP_MEMORY_LIMIT', '256M' );
```

## Bugs ##
If you find an issue, let us know [here](https://github.com/Alimir/wp-ulike/issues?state=open)!

## Support ##
Please visit the [support forums](https://wordpress.org/support/plugin/wp-ulike).
