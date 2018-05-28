# [WP ULike](https://wordpress.org/plugins/wp-ulike/) #

### WP ULike GitHub Repository

WP ULike is a WordPress plugin that also supports BuddyPress, bbPress and a number of other plugins. It aims to be a comprehensive “Like” system for your site and enables site users to like a wide range of content types, including posts, forum topics and replies, comments and activity updates. It’s very simple to use and supports many options and full Statistics tools. Also, All are free :)

More information can be found at [WP ULike](https://wpulike.com/).

<a href="https://wordpress.org/plugins/wp-ulike/"><img src="http://preview.alimir.ir/wp-content/uploads/wp-ulike-created-banner.png"></a>

## Features
*   Clean Design.
*   Full myCRED Points Support.
*   Full Statistics tools.
*   Supporting UltimateMember & BuddyPress Profiles.
*   Likers World Map & Top Likers Widget.
*   Ajax feature to update the data without reloading.
*   Visitors do not have to register or log in to use the Like Button.
*   Compatible with WP version 3.5 & above.
*   Added automatically with filtering options (no Code required).
*   Different logging method options.
*   Notifications System. (Custom toast messages after each activity)
*   Shortcode support.
*   Support custom templates with separate variables.
*   Comment likes support.
*   Supporting the date in localized format. (date_i18n)
*   Full likes logs support.
*   BuddyPress add activity & notifications support.
*   Simple user like box with avatar support.
*   Custom Like-UnLike Texts fields.
*   Simple custom style with color picker settings.
*   Advanced Widgets With Custom Tools. (Most Liked Posts,Comments,Users,Topics,…)
*   Powerful configuration panel.
*   Support RTL & language file.
*   And so on…


## Translations
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

## Installation ##

For detailed setup instructions, visit the official [Documentation](https://wordpress.org/plugins/wp-ulike/installation/) page.

1. You can clone the GitHub repository: `https://github.com/Alimir/wp-ulike.git`
2. Or download it directly as a ZIP file: `https://github.com/Alimir/wp-ulike/archive/master.zip`

This will download the latest developer copy of WP ULike.


## How To Use this plugin? ##
Just install the plugin and activate "automatic display" in plugin configuration panel. (WP ULike has four auto options for posts, comments, buddypress activities & bbPress Topics.)
Also you can use of the following function and shortcode for your posts:
*   Function:
```php
if(function_exists('wp_ulike')) wp_ulike('get');
```
*   Shortcode:
```
[wp_ulike]
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

## How To Change Schema Type? ##
The default schema type is `CreativeWork`, if you want to change it to `Article`, you need to make use of the `wp_ulike_posts_add_attr` filter as shown in the sample code below:
```php
add_filter('wp_ulike_posts_add_attr', 'wp_ulike_change_posts_microdata_itemtype', 10);
function wp_ulike_change_posts_microdata_itemtype() {
	return 'itemscope itemtype="http://schema.org/Article"';
}
```

## How To Add Extra Microdata? ##
Make use of the `wp_ulike_extra_structured_data` filter as shown in the sample code below:
```php
add_filter('wp_ulike_extra_structured_data', 'wp_ulike_add_extra_structured_data', 10);
function wp_ulike_add_extra_structured_data(){
	$post_meta = '<div style="display: none;" itemprop="publisher" itemscope itemtype="https://schema.org/Organization">';
	$post_meta .= '<meta itemprop="name" content="WordPress" />';
	$post_meta .= '<div itemprop="logo" itemscope itemtype="https://schema.org/ImageObject">';
	$post_meta .= '<meta itemprop="url" content="https://s.w.org/about/images/logos/wordpress-logo-hoz-rgb.png" />';
	$post_meta .= '</div>';
	$post_meta .= '</div>';
	return $post_meta;
}
```

## How To Remove All Schema Data Except Of aggregateRating? ##
Make use of the `wp_ulike_remove_microdata_post_meta` & `wp_ulike_posts_add_attr` filters as shown in the sample code below:
```php
add_filter('wp_ulike_remove_microdata_post_meta', '__return_true', 10);
add_filter('wp_ulike_posts_add_attr', '__return_null', 10);
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
$the_query = new WP_Query(array(
	'post_status' => 'published',
	'post_type' => 'post',
	'orderby' => 'meta_value_num',
	'meta_key' => '_liked',
	'paged' => (get_query_var('paged')) ? get_query_var('paged') : 1
));
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
