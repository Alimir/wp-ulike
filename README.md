# WP ULike

**The Ultimate WordPress Engagement Plugin** - Add Like & Dislike buttons to posts, comments, WooCommerce products, BuddyPress activities, and bbPress topics. Track engagement with comprehensive statistics and analytics.

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.2.5%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

---

## 🚀 Features

- **Universal Support** - Works with Posts, Comments, WooCommerce Products, BuddyPress Activities, and bbPress Topics
- **Fast & Lightweight** - Vanilla JavaScript (no jQuery), optimized for performance
- **Customizable Templates** - Multiple button styles and templates
- **Statistics Dashboard** - Track engagement with detailed analytics
- **Developer-Friendly** - Extensive hooks, filters, and functions for customization
- **GDPR Ready** - IP anonymization, no personal data stored
- **RTL Support** - Full right-to-left language support
- **Multilingual** - 20+ language translations included

---

## 📦 Installation

### Via WordPress Admin
1. Go to **Plugins → Add New**
2. Search for "WP ULike"
3. Click **Install Now** and then **Activate**

### Manual Installation
1. Download the latest release: `https://github.com/Alimir/wp-ulike/archive/master.zip`
2. Extract and upload to `/wp-content/plugins/`
3. Activate through the **Plugins** menu

### Via Git
```bash
git clone https://github.com/Alimir/wp-ulike.git wp-content/plugins/wp-ulike
```

---

## 🎯 Quick Start

After activation, go to **WP ULike → Settings** and enable "Auto Display" for your desired content types (Posts, Comments, etc.).

### Basic Usage

**Display Like Button:**
```
[wp_ulike]
```

**In PHP Templates:**
```php
echo do_shortcode('[wp_ulike for="post" id="123"]');
```

**💡 Need Custom HTML Structure?** You can create fully customizable templates with complete control over HTML structure and button positioning. See the [Custom Templates section](#-custom-templates---full-html-control) in Developer Functions or jump to [Template & Query Filters](#template--query-filters) for complete examples.

---

## 📚 Shortcodes

### `[wp_ulike]` - Main Like Button

Display the like/dislike button for any content type.

**Parameters:**
- `for` (string) - Content type: `post`, `comment`, `activity`, `topic`
- `id` (integer) - Specific item ID (optional, auto-detected in loops)
- `style` (string) - Template style name
- `button_type` (string) - Button type: `image` or `text`
- `wrapper_class` (string) - Additional CSS class

**Examples:**
```
[wp_ulike for="post"]
[wp_ulike for="comment" style="wpulike-heart"]
[wp_ulike for="post" id="123" button_type="text"]
```

### `[wp_ulike_counter]` - Display Counter

Show the number of likes/dislikes.

**Parameters:**
- `id` (integer) - Item ID (optional, auto-detected)
- `type` (string) - Content type: `post`, `comment`, `activity`, `topic`
- `status` (string) - Vote status: `like`, `unlike`, `dislike`, `undislike`
- `date_range` (string) - Date range filter
- `past_time` (integer) - Hours in the past

**Examples:**
```
[wp_ulike_counter type="post" status="like"]
[wp_ulike_counter type="comment" past_time="24"]
```

### `[wp_ulike_likers_box]` - Display Likers List

Show a list of users who liked the content.

**Parameters:**
- `id` (integer) - Item ID (optional, auto-detected)
- `type` (string) - Content type: `post`, `comment`, `activity`, `topic`
- `counter` (integer) - Number of users to display (default: 10)
- `template` (string) - Custom template HTML
- `style` (string) - Display style
- `avatar_size` (integer) - Avatar size in pixels (default: 64)

**Example:**
```
[wp_ulike_likers_box type="post" counter="5" avatar_size="48"]
```

---

## 💻 Developer Functions

> **Note:** All functions are prefixed with `wp_ulike_` and should be checked for existence before use in themes/plugins for compatibility.

### 🎨 Custom Templates - Full HTML Control

**Need complete control over button HTML structure and positioning?** Create custom templates with full control over the HTML output. This allows you to place like/dislike buttons anywhere in your code with any HTML structure you need.

**Quick Example:**
```php
// Register custom template
add_filter('wp_ulike_add_templates_list', 'my_custom_template', 10, 1);
function my_custom_template($templates) {
    $templates['my-template'] = array(
        'name'            => 'My Custom Template',
        'callback'        => 'my_template_callback',
        'is_text_support' => true
    );
    return $templates;
}

// Custom template with full HTML control
function my_template_callback($args) {
    extract($args);
    ob_start();
    ?>
    <div class="my-custom-wrapper">
        <button class="my-like-btn" 
                data-ulike-id="<?php echo $ID; ?>"
                data-ulike-type="<?php echo $type; ?>"
                data-ulike-nonce="<?php echo wp_create_nonce($type . $ID); ?>">
            <?php echo $up_vote_inner_text; ?>
        </button>
    </div>
    <?php
    return ob_get_clean();
}

// Use anywhere in your HTML
echo do_shortcode('[wp_ulike for="post" style="my-template"]');
```

**See the complete guide in the [Custom Templates section](#template--query-filters) below for detailed examples including horizontal layouts, custom positioning, and advanced styling.**

### Core Functions Reference

#### Counter Functions
- `wp_ulike_get_counter_value($ID, $type, $status, $is_distinct, $date_range)` - Get counter value
  - **Parameters:** `$ID` (int) - Item ID, `$type` (string) - Content type, `$status` (string) - Vote status, `$is_distinct` (bool) - Distinct count, `$date_range` (string|array) - Date filter
  - **Returns:** (int) Counter value
- `wp_ulike_update_meta_counter_value($ID, $value, $type, $status, $is_distinct, $prev_value)` - Update counter meta
  - **Parameters:** `$ID` (int), `$value` (int), `$type` (string), `$status` (string), `$is_distinct` (bool), `$prev_value` (string) - Previous value
  - **Returns:** (int|bool) Meta ID or false

#### Query Functions
- `wp_ulike_get_popular_items_info($args)` - Get popular items with counters
  - **Parameters:** `$args` (array) - Options: type, rel_type, status, user_id, order, is_popular, period, offset, limit
  - **Returns:** (array|null) Array of objects with item_ID and counter, or null
- `wp_ulike_get_popular_items_ids($args)` - Get popular item IDs only
  - **Parameters:** Same as above
  - **Returns:** (array) Array of item IDs
- `wp_ulike_get_popular_items_total_number($args)` - Get total popular items count
  - **Parameters:** `$args` (array) - Options: type, status, period, user_id, rel_type
  - **Returns:** (int|null) Total count or null
- `wp_ulike_get_likers_list_per_post($table, $column, $item_ID, $limit)` - Get likers list
  - **Parameters:** `$table` (string) - Table name, `$column` (string) - Column name, `$item_ID` (int), `$limit` (int) - Max users
  - **Returns:** (array) Array of user IDs
- `wp_ulike_get_best_likers_info($limit, $period, $offset, $status)` - Get best likers
  - **Parameters:** `$limit` (int), `$period` (string), `$offset` (int), `$status` (string|array) - Vote status(es)
  - **Returns:** (array) Array of user objects with vote counts
- `wp_ulike_get_top_enagers_total_number($period, $status)` - Get top engagers count
  - **Parameters:** `$period` (string|array), `$status` (string|array)
  - **Returns:** (int) Total number of unique engagers
- `wp_ulike_get_user_item_history($args)` - Get user voting history
  - **Parameters:** `$args` (array) - Options: item_id, item_type, current_user, settings, is_user_logged_in
  - **Returns:** (array) Array of item_id => status pairs
- `wp_ulike_get_user_latest_activity($item_id, $user_id, $type)` - Get user latest activity
  - **Parameters:** `$item_id` (int), `$user_id` (int), `$type` (string) - Content type
  - **Returns:** (array|null) Array with date_time and status, or null
- `wp_ulike_get_user_item_count_per_day($args)` - Get daily user activity count
  - **Parameters:** `$args` (array) - Options: item_id, current_user, settings
  - **Returns:** (int) Count of votes today
- `wp_ulike_get_user_data($user_ID, $args)` - Get comprehensive user data
  - **Parameters:** `$user_ID` (int), `$args` (array) - Options: type, period, order, status, page, per_page
  - **Returns:** (array|null) Array of user activity objects
- `wp_ulike_get_users($args)` - Get users with voting data
  - **Parameters:** `$args` (array) - Options: type, period, order, status, page, per_page
  - **Returns:** (array|null) Array of user objects with vote statistics
#### Meta Data Functions
- `wp_ulike_add_meta_data($object_id, $meta_group, $meta_key, $meta_value, $unique)` - Add meta
  - **Parameters:** `$object_id` (int), `$meta_group` (string) - post/comment/activity/topic/user/statistics, `$meta_key` (string), `$meta_value` (mixed), `$unique` (bool)
  - **Returns:** (int|false) Meta ID or false
- `wp_ulike_update_meta_data($object_id, $meta_group, $meta_key, $meta_value, $prev_value)` - Update meta
  - **Parameters:** `$object_id` (int), `$meta_group` (string), `$meta_key` (string), `$meta_value` (mixed), `$prev_value` (mixed) - Previous value
  - **Returns:** (int|bool) Meta ID if new, true if updated, false on failure
- `wp_ulike_get_meta_data($object_id, $meta_group, $meta_key, $single)` - Get meta
  - **Parameters:** `$object_id` (int), `$meta_group` (string), `$meta_key` (string), `$single` (bool) - Return single value
  - **Returns:** (mixed) Meta value(s)
- `wp_ulike_delete_meta_data($meta_group, $object_id, $meta_key, $meta_value, $delete_all)` - Delete meta
  - **Parameters:** `$meta_group` (string), `$object_id` (int), `$meta_key` (string), `$meta_value` (mixed), `$delete_all` (bool)
  - **Returns:** (bool) True on success

#### Utility Functions
- `wp_ulike_get_the_id($post_id)` - Get post ID (WPML compatible)
  - **Parameters:** `$post_id` (int) - Optional post ID
  - **Returns:** (int) Post ID
- `wp_ulike_get_rating_value($post_ID, $is_decimal)` - Get rating value
  - **Parameters:** `$post_ID` (int), `$is_decimal` (bool) - Include decimals
  - **Returns:** (string|float) Rating value (1-5)
- `wp_ulike_is_user_liked($item_ID, $user_ID, $type)` - Check if user liked item
  - **Parameters:** `$item_ID` (int), `$user_ID` (int), `$type` (string) - Content type
  - **Returns:** (int) Count of likes (0 if not liked)

#### Content Type Functions
- `wp_ulike($type, $args)` - Display post like button
  - **Parameters:** `$type` (string) - 'get' or 'put', `$args` (array) - Options: id, wrapper_class, etc.
  - **Returns:** (string|void) HTML output or echoes
- `wp_ulike_comments($type, $args)` - Display comment like button
  - **Parameters:** Same as above
  - **Returns:** (string|void) HTML output or echoes
- `wp_ulike_buddypress($type, $args)` - Display activity like button
  - **Parameters:** Same as above
  - **Returns:** (string|void) HTML output or echoes
- `wp_ulike_bbpress($type, $args)` - Display topic like button
  - **Parameters:** Same as above
  - **Returns:** (string|void) HTML output or echoes
- `wp_ulike_get_post_likes($post_ID, $status)` - Get post likes count
  - **Parameters:** `$post_ID` (int), `$status` (string) - Vote status
  - **Returns:** (int) Like count
- `wp_ulike_get_comment_likes($comment_ID, $status)` - Get comment likes count
  - **Parameters:** `$comment_ID` (int), `$status` (string)
  - **Returns:** (int) Like count
- `wp_ulike_get_most_liked_posts($numberposts, $post_type, $method, $period, $status, $is_normal, $offset, $user_id)` - Get most liked posts
  - **Parameters:** `$numberposts` (int), `$post_type` (string|array), `$method` (string), `$period` (string), `$status` (string), `$is_normal` (bool), `$offset` (int), `$user_id` (string)
  - **Returns:** (array|false) Array of WP_Post objects or false
- `wp_ulike_get_most_liked_comments($numbercomments, $post_type, $period, $status, $offset, $user_id)` - Get most liked comments
  - **Parameters:** `$numbercomments` (int), `$post_type` (string), `$period` (string), `$status` (string), `$offset` (int), `$user_id` (string)
  - **Returns:** (array|false) Array of WP_Comment objects or false
- `wp_ulike_get_most_liked_activities($number, $period, $status, $offset, $user_id)` - Get most liked activities
  - **Parameters:** `$number` (int), `$period` (string), `$status` (string), `$offset` (int), `$user_id` (string)
  - **Returns:** (array|false) Array of activity objects or false

### Get Like Count

```php
// Get post likes
if (function_exists('wp_ulike_get_post_likes')):
    echo wp_ulike_get_post_likes(get_the_ID());
endif;

// Get comment likes
if (function_exists('wp_ulike_get_comment_likes')):
    echo wp_ulike_get_comment_likes(get_comment_ID());
endif;

// Get counter value (universal)
$count = wp_ulike_get_counter_value($item_id, 'post', 'like', true);
```

### Check User Status

```php
// Check if user liked an item
$is_liked = wp_ulike_is_user_liked($item_id, $user_id, 'likeThis');
// Returns count (0 if not liked, >0 if liked)

// Check user voting status from history
$args = array(
    'item_id'           => $item_id,
    'item_type'         => 'post',
    'current_user'      => get_current_user_id(),
    'settings'          => $settings,
    'is_user_logged_in' => is_user_logged_in()
);
$history = wp_ulike_get_user_item_history($args);
$user_status = isset($history[$item_id]) ? $history[$item_id] : false;
```

### Get Popular Items

```php
// Get most liked posts
$args = array(
    'type'     => 'post',
    'rel_type' => 'post',
    'status'   => 'like',
    'period'   => 'all',
    'limit'    => 10
);
$popular = wp_ulike_get_popular_items_info($args);

// Get popular item IDs only
$popular_ids = wp_ulike_get_popular_items_ids($args);

// Get total number of popular items
$total = wp_ulike_get_popular_items_total_number($args);

// Legacy function
$wp_query = wp_ulike_get_most_liked_posts(10, array('post'), 'post', 'all', 'like');
```

### Get User Data & History

```php
// Get user item history (all items user has liked)
$args = array(
    'item_id'           => 123,
    'item_type'         => 'post',
    'current_user'      => get_current_user_id(),
    'settings'          => $settings,
    'is_user_logged_in' => true
);
$history = wp_ulike_get_user_item_history($args);

// Check if user liked an item
$is_liked = wp_ulike_is_user_liked($item_id, $user_id, 'likeThis');

// Get user latest activity
$latest = wp_ulike_get_user_latest_activity($item_id, $user_id, 'post');

// Get user item count per day
$daily_count = wp_ulike_get_user_item_count_per_day($args);

// Get user data
$user_data = wp_ulike_get_user_data($user_id, array(
    'period' => 'all',
    'status' => array('like', 'dislike')
));
```

### Get Likers List

```php
// Get likers list for an item
$likers = wp_ulike_get_likers_list_per_post($table_name, $column_name, $item_id, $limit);

// Get best likers (most active users)
$best_likers = wp_ulike_get_best_likers_info($limit, $period, $offset, $status);

// Get top engagers total number
$total_engagers = wp_ulike_get_top_enagers_total_number($period, $status);
```

### Utility Functions

```php
// Get counter value info (with error handling)
$counter_info = wp_ulike_get_counter_value_info($item_id, 'post', 'like', true, $date_range);

// Format number (with K, M suffixes)
$formatted = wp_ulike_format_number(1234, 'like');

// Get button text
$button_text = wp_ulike_get_button_text('like', 'posts_group');

// Get post ID (with WPML support)
$post_id = wp_ulike_get_the_id($post_id);

// Check if WPML is active
$is_wpml = wp_ulike_is_wpml_active();

// Get rating value
$rating = wp_ulike_get_rating_value($post_id, true);

```

### Meta Data Functions

```php
// Add meta data
wp_ulike_add_meta_data($object_id, $meta_group, $meta_key, $meta_value, $unique);

// Update meta data
wp_ulike_update_meta_data($object_id, $meta_group, $meta_key, $meta_value, $prev_value);

// Get meta data
$meta_value = wp_ulike_get_meta_data($object_id, $meta_group, $meta_key, $single);

// Delete meta data
wp_ulike_delete_meta_data($meta_group, $object_id, $meta_key, $meta_value, $delete_all);

// Update meta counter value
wp_ulike_update_meta_counter_value($ID, $value, $type, $status, $is_distinct, $prev_value);

// Get meta counter value
$counter = wp_ulike_meta_counter_value($ID, $type, $status, $is_distinct);

// Delete vote data
wp_ulike_delete_vote_data($ID, $type);
```

### Content Type Functions

```php
// Display like button for posts
wp_ulike('put', array('id' => 123));

// Display like button for comments
wp_ulike_comments('put', array('id' => 456));

// Display like button for BuddyPress activities
wp_ulike_buddypress('put', array('id' => 789));

// Display like button for bbPress topics
wp_ulike_bbpress('put', array('id' => 101));

// Get most liked comments
$comments = wp_ulike_get_most_liked_comments(10, 'post', 'all', 'like');

// Get most liked activities
$activities = wp_ulike_get_most_liked_activities(10, 'all', 'like');
```

---

## 🎨 Customization Hooks

### Data & Process Actions

**Before Like/Dislike Process:**
```php
add_action('wp_ulike_before_process', 'my_before_process_action', 10, 1);
function my_before_process_action($data) {
    // $data contains: id, type, nonce, factor, template, displayLikers, likersTemplate, client_address
    // Fired before the like/dislike process begins
    // Use this to validate, log, or modify data before processing
}
```

**After Like/Dislike Process:**
```php
add_action('wp_ulike_after_process', 'my_after_process_action', 10, 6);
function my_after_process_action($id, $key, $user_id, $status, $has_log, $slug) {
    // Fired after the like/dislike process completes
    // $id = item ID, $key = item key, $user_id = user ID, $status = like/unlike status
    // $has_log = whether previous log exists, $slug = content type (post/comment/activity/topic)
    // Example: Send notifications, update external systems, etc.
}
```

**After Like/Dislike Data is Inserted:**
```php
add_action('wp_ulike_data_inserted', 'my_custom_action_on_like', 10, 1);
function my_custom_action_on_like($args) {
    // $args contains: id, item_id, table, related_column, type, user_id, status, ip
    // Example: Send email notification, update custom meta, etc.
    if ($args['status'] === 'like') {
        // Do something when user likes
    }
}
```

**After Like/Dislike Data is Updated:**
```php
add_action('wp_ulike_data_updated', 'my_custom_action_on_update', 10, 1);
function my_custom_action_on_update($args) {
    // $args contains: item_id, table, related_column, type, user_id, status, ip
    // Example: Track status changes, update analytics, etc.
}
```

**When Counter Value is Generated:**
```php
add_action('wp_ulike_counter_value_generated', 'my_counter_action');
function my_counter_action() {
    // Fired when counter value is calculated
}
```

**When Vote Data is Deleted:**
```php
add_action('wp_ulike_delete_vote_data', 'my_delete_action', 10, 3);
function my_delete_action($ID, $type, $settings) {
    // Fired when vote data is deleted
    // $ID = item ID, $type = content type, $settings = settings object
}
```

**Plugin Loaded:**
```php
add_action('wp_ulike_loaded', 'my_plugin_loaded_action');
function my_plugin_loaded_action() {
    // Fired when plugin is fully loaded
}
```


**Inline Likers Box Display:**
```php
add_action('wp_ulike_inline_display_likers_box', 'my_inline_likers', 10, 2);
function my_inline_likers($args, $get_settings) {
    // Customize inline likers box display
}
```

**Meta Data Actions (Dynamic):**
```php
// These actions are fired for each meta group (post, comment, activity, topic, user, statistics)
// Replace {meta_group} with: post, comment, activity, topic, user, or statistics

// Before meta is added
add_action('wp_ulike_add_{meta_group}_meta', 'my_add_meta', 10, 4);
function my_add_meta($object_id, $meta_key, $meta_value, $unique) {
    // Fired before meta is added
}

// After meta is added
add_action('wp_ulike_added_{meta_group}_meta', 'my_added_meta', 10, 3);
function my_added_meta($mid, $object_id, $meta_key) {
    // Fired after meta is added
}

// Before meta is updated
add_action('wp_ulike_update_{meta_group}_meta', 'my_update_meta', 10, 3);
function my_update_meta($meta_id, $object_id, $meta_key) {
    // Fired before meta is updated
}

// After meta is updated
add_action('wp_ulike_updated_{meta_group}_meta', 'my_updated_meta', 10, 3);
function my_updated_meta($meta_id, $object_id, $meta_key) {
    // Fired after meta is updated
}

// Before meta is deleted
add_action('wp_ulike_delete_{meta_group}_meta', 'my_delete_meta', 10, 3);
function my_delete_meta($meta_ids, $object_id, $meta_key) {
    // Fired before meta is deleted
}

// After meta is deleted
add_action('wp_ulike_deleted_{meta_group}_meta', 'my_deleted_meta', 10, 3);
function my_deleted_meta($meta_ids, $object_id, $meta_key) {
    // Fired after meta is deleted
}
```

**Template Rendering Hooks:**
```php
// Before template is rendered
add_action('wp_ulike_before_template', 'add_before_template_content', 10, 1);
function add_before_template_content($template_args) {
    // Add custom content before the like button
}

// Inside the like button
add_action('wp_ulike_inside_like_button', 'add_inside_button_content', 10, 1);

// After up vote button
add_action('wp_ulike_after_up_vote_button', 'add_after_button_content', 10, 1);

// Inside template wrapper
add_action('wp_ulike_inside_template', 'add_inside_template_content', 10, 1);

// After template is rendered
add_action('wp_ulike_after_template', 'add_after_template_content', 10, 1);
```

### Template & Display Filters

**Customize Template Arguments:**
```php
add_filter('wp_ulike_add_templates_args', 'modify_template_args', 10, 1);
function modify_template_args($args) {
    // Modify template arguments before rendering
    return $args;
}
```

**Customize Final Template Output:**
```php
add_filter('wp_ulike_return_final_templates', 'modify_final_template', 10, 2);
function modify_final_template($template, $args) {
    // Modify the final HTML output
    return $template;
}
```

**Customize Count Box Template:**
```php
add_filter('wp_ulike_count_box_template', 'custom_count_box', 10, 2);
function custom_count_box($string, $counter) {
    // Customize the count display HTML
    return '<span class="my-custom-count">' . $counter . '</span>';
}
```

**Customize Login Alert:**
```php
add_filter('wp_ulike_login_alert_template', 'custom_login_alert');
function custom_login_alert() {
    return '<div class="alert">Please login to like this content!</div>';
}
```

**Customize Button Text:**
```php
add_filter('wp_ulike_button_text', 'custom_button_text', 10, 3);
function custom_button_text($value, $option_name, $setting_key) {
    // Customize button text based on context
    return $value;
}
```

**Customize CSS Output:**
```php
add_filter('wp_ulike_custom_css', 'add_custom_styles');
function add_custom_styles($css) {
    return $css . '.wp_ulike_btn { border-radius: 20px; }';
}
```

**Customize Content Output:**
```php
add_filter('wp_ulike_the_content', 'modify_content_output', 10, 2);
function modify_content_output($output, $content) {
    // Modify the content with like button
    return $output;
}
```

**Customize Comment Text Output:**
```php
add_filter('wp_ulike_comment_text', 'modify_comment_text', 10, 3);
function modify_comment_text($output, $content, $comment) {
    // Modify comment text with like button
    return $output;
}
```

**Customize Format Number:**
```php
add_filter('wp_ulike_format_number', 'custom_format_number', 10, 3);
function custom_format_number($value, $number, $status) {
    // Customize number formatting (e.g., 1K, 1M)
    return $value;
}
```

**Customize Likers Box Shortcode:**
```php
add_filter('wp_ulike_likers_box_shortcode', 'custom_likers_shortcode', 10, 3);
function custom_likers_shortcode($output, $id, $type) {
    // Customize likers box shortcode output
    return $output;
}
```

**Customize Listener Data:**
```php
add_filter('wp_ulike_listener_data', 'modify_listener_data', 10, 1);
function modify_listener_data($data) {
    // Modify AJAX listener data before processing
    return $data;
}
```

**Customize Get The ID:**
```php
add_filter('wp_ulike_get_the_id', 'modify_get_id', 10, 1);
function modify_get_id($post_id) {
    // Modify post ID retrieval (useful for WPML, etc.)
    return $post_id;
}
```

**Customize Cookie Settings:**
```php
// Enable/disable cookie setting
add_filter('wp_ulike_set_cookie_enabled', 'control_cookie_setting', 10, 4);
function control_cookie_setting($enabled, $name, $value, $expire) {
    // Return false to prevent cookie from being set
    return $enabled;
}

// Modify cookie httponly flag
add_filter('wp_ulike_cookie_httponly', 'modify_cookie_httponly', 10, 4);
function modify_cookie_httponly($httponly, $name, $value, $expire) {
    return $httponly;
}
```

**Customize Auto Display Filter List:**
```php
add_filter('wp_ulike_auto_diplay_filter_list', 'add_auto_display_filters', 10, 1);
function add_auto_display_filters($defaults) {
    // Add custom conditions for auto display
    $defaults['is_custom_page'] = is_page('custom-page');
    return $defaults;
}
```

**Customize Supported Post Types:**
```php
add_filter('wp_ulike_supported_post_types_for_top_posts_list', 'add_post_types', 10, 1);
function add_post_types($post_types) {
    // Add custom post types to popular posts list
    $post_types[] = 'custom_post_type';
    return $post_types;
}
```

**Customize Top Posts Query:**
```php
add_filter('wp_ulike_get_top_posts_query', 'modify_top_posts_query', 10, 1);
function modify_top_posts_query($args) {
    // Modify WP_Query arguments for top posts
    return $args;
}
```

**Customize Top Comments Query:**
```php
add_filter('wp_ulike_get_top_comments_query', 'modify_top_comments_query', 10, 1);
function modify_top_comments_query($args) {
    // Modify WP_Comment_Query arguments for top comments
    return $args;
}
```

**Customize Comments Add Attributes:**
```php
add_filter('wp_ulike_comments_add_attr', 'add_comment_attributes');
function add_comment_attributes($attributes) {
    return 'data-custom="value"';
}
```

**Customize Activities Add Attributes:**
```php
add_filter('wp_ulike_activities_add_attr', 'add_activity_attributes');
function add_activity_attributes($attributes) {
    return 'data-custom="value"';
}
```

**Customize Topics Add Attributes:**
```php
add_filter('wp_ulike_topics_add_attr', 'add_topic_attributes');
function add_topic_attributes($attributes) {
    return 'data-custom="value"';
}
```



**Rich Snippets/Microdata Filters:**
```php
// Posts microdata
add_filter('wp_ulike_posts_microdata', 'custom_posts_microdata');
function custom_posts_microdata($microdata) {
    return '<script type="application/ld+json">{"@type": "Article"}</script>';
}

// Comments microdata
add_filter('wp_ulike_comments_microdata', 'custom_comments_microdata');

// Activities microdata
add_filter('wp_ulike_activities_microdata', 'custom_activities_microdata');

// Topics microdata
add_filter('wp_ulike_topics_microdata', 'custom_topics_microdata');
```

**Posts Add Attributes:**
```php
add_filter('wp_ulike_posts_add_attr', 'add_custom_attributes');
function add_custom_attributes($attributes) {
    return 'data-custom="value"';
}
```

**Rating Value Filter:**
```php
add_filter('wp_ulike_rating_value', 'customize_rating', 10, 2);
function customize_rating($rating_value, $post_ID) {
    // Customize rating calculation
    return $rating_value;
}
```

**Meta Data Filters (Dynamic):**
```php
// These filters are available for each meta group
// Replace {meta_group} with: post, comment, activity, topic, user, or statistics

// Override meta add operation
add_filter('wp_ulike_add_{meta_group}_metadata', 'override_add_meta', 10, 5);
function override_add_meta($check, $object_id, $meta_key, $meta_value, $unique) {
    // Return non-null to override default behavior
    return $check;
}

// Override meta update operation
add_filter('wp_ulike_update_{meta_group}_metadata', 'override_update_meta', 10, 4);
function override_update_meta($check, $object_id, $meta_key, $meta_value, $prev_value) {
    return $check;
}

// Override meta delete operation
add_filter('wp_ulike_delete_{meta_group}_metadata', 'override_delete_meta', 10, 4);
function override_delete_meta($check, $object_id, $meta_key, $meta_value, $delete_all) {
    return $check;
}

// Default meta value
add_filter('wp_ulike_default_{meta_group}_metadata', 'default_meta_value', 10, 5);
function default_meta_value($value, $object_id, $meta_key, $single, $meta_group) {
    // Return default value if meta doesn't exist
    return $value;
}
```

### Permission & Validation Filters

**Check User Permission:**
```php
add_filter('wp_ulike_permission_status', 'custom_permission_check', 10, 3);
function custom_permission_check($status, $args, $settings) {
    // Return true/false to allow/deny voting
    // $args contains item_id, type, user_id, etc.
    return $status;
}
```

**Validate Blacklist:**
```php
// Note: This filter is referenced but implemented via wp_ulike_blacklist_validator class
// Use the permission status filter for custom validation
```

**Get User Current Status:**
```php
add_filter('wp_ulike_user_current_status', 'modify_user_status', 10, 3);
function modify_user_status($currentStatus, $prevStatus, $args) {
    // Modify the current user voting status
    return $currentStatus;
}
```

**Get User Previous Status:**
```php
add_filter('wp_ulike_user_prev_status', 'modify_prev_status', 10, 2);
function modify_prev_status($prevStatus, $args) {
    // Modify the previous user voting status
    return $prevStatus;
}
```

### AJAX & Response Filters

**Modify AJAX Response:**
```php
add_filter('wp_ulike_ajax_respond', 'customize_ajax_response', 10, 4);
function customize_ajax_response($response, $item_id, $status, $atts) {
    // Modify AJAX response data
    $response['custom_data'] = 'my custom value';
    return $response;
}
```

**Modify Counter Value in AJAX:**
```php
add_filter('wp_ulike_ajax_counter_value', 'custom_ajax_counter', 10, 6);
function custom_ajax_counter($counter_val, $item_id, $item_type, $status, $is_distinct, $template) {
    // Modify counter value in AJAX response
    return $counter_val;
}
```

**Modify AJAX Process Attributes:**
```php
add_filter('wp_ulike_ajax_process_atts', 'modify_ajax_atts', 10, 1);
function modify_ajax_atts($atts) {
    // Modify AJAX process attributes
    return $atts;
}
```

**Customize Response for Different Statuses:**
```php
// When user likes (status 1)
add_filter('wp_ulike_respond_for_not_liked_data', 'custom_like_response', 10, 2);

// When user unlikes (status 2)
add_filter('wp_ulike_respond_for_unliked_data', 'custom_unlike_response', 10, 2);

// When user re-likes (status 3)
add_filter('wp_ulike_respond_for_liked_data', 'custom_relike_response', 10, 2);

// When no limit mode (status 4)
add_filter('wp_ulike_respond_for_no_limit_data', 'custom_no_limit_response', 10, 2);
```

### Counter & Data Filters

**Modify Counter Value:**
```php
add_filter('wp_ulike_counter_value', 'modify_counter', 10, 5);
function modify_counter($counter_value, $ID, $type, $status, $date_range) {
    // Modify the counter value
    return $counter_value;
}
```

**Modify Likers List:**
```php
add_filter('wp_ulike_get_likers_list', 'customize_likers_list', 10, 3);
function customize_likers_list($output, $item_type, $item_ID) {
    // Customize the likers list output
    return $output;
}
```

**Modify Likers Template:**
```php
add_filter('wp_ulike_get_likers_template', 'custom_likers_template', 10, 6);
function custom_likers_template($template, $get_users, $item_ID, $parsed_args, $table_name, $column_name) {
    // Return custom likers template HTML
    return $template;
}
```

### Integration & Utility Filters

**Modify User IP:**
```php
add_filter('wp_ulike_get_user_ip', 'custom_user_ip', 10, 1);
function custom_user_ip($ip) {
    // Modify or override user IP detection
    return $ip;
}
```

**Modify Auto Display Settings:**
```php
add_filter('wp_ulike_enable_auto_display', 'control_auto_display', 10, 2);
function control_auto_display($auto_display, $type) {
    // Control auto display per content type
    return $auto_display;
}
```

**Modify Display Capabilities:**
```php
add_filter('wp_ulike_display_capabilities', 'custom_display_caps', 10, 2);
function custom_display_caps($allowed_roles, $type) {
    // Modify which roles can see like buttons
    return $allowed_roles;
}
```

### BuddyPress Integration Filters

**Modify BP Activity Notification Args:**
```php
add_filter('wp_ulike_post_bp_notification_args', 'custom_bp_post_args', 10, 1);
add_filter('wp_ulike_comment_bp_notification_args', 'custom_bp_comment_args', 10, 1);
add_filter('wp_ulike_bp_add_notification_args', 'custom_bp_notification_args', 10, 1);
```

**Modify BP Notification Template:**
```php
add_filter('wp_ulike_bp_notifications_template', 'custom_bp_template', 10, 4);
function custom_bp_template($content, $custom_link, $total_items, $item_id) {
    // Customize BuddyPress notification template
    return $content;
}
```

### Template & Query Filters

**Add Custom Templates:**

You can create fully customizable templates with complete control over HTML structure, CSS classes, and button placement. This allows you to integrate like/dislike buttons anywhere in your HTML code.

**Basic Template Registration:**
```php
add_filter('wp_ulike_add_templates_list', 'add_custom_template', 10, 1);
function add_custom_template($templates) {
    $templates['my-custom-template'] = array(
        'name'            => 'My Custom Template',
        'callback'        => 'my_template_callback',
        'symbol'          => 'path/to/icon.svg',
        'is_text_support' => true
    );
    return $templates;
}
```

**Complete Custom Template Example:**

This example shows how to create a fully customizable template where you have complete control over the HTML structure, allowing you to place buttons anywhere in your code:

```php
/**
 * Register custom template
 *
 * @param array $templates
 * @return array
 */
function wp_ulike_register_custom_template( $templates ) {
    $templates['wpulike-custom-template'] = array(
        'name'            => __('Custom Template', 'wp-ulike'),
        'callback'        => 'wp_ulike_custom_template_content',
        'symbol'          => WP_ULIKE_ASSETS_URL . '/img/svg/default.svg',
        'is_text_support' => true
    );
    return $templates;
}
add_filter( 'wp_ulike_add_templates_list', 'wp_ulike_register_custom_template', 10, 1 );

/**
 * Custom template content with full HTML control
 *
 * @param array $wp_ulike_template Template arguments array
 * @return string HTML output
 */
function wp_ulike_custom_template_content( array $wp_ulike_template ) {
    // Start output buffering
    ob_start();
    
    // Fire before template hook
    do_action( 'wp_ulike_before_template', $wp_ulike_template );
    
    // Extract template variables for easier access
    extract( $wp_ulike_template );
    
    // Available variables:
    // $ID - Item ID
    // $type - Content type (post, comment, activity, topic)
    // $style - Template style name
    // $button_type - Button type (image or text)
    // $wrapper_class - Additional CSS classes
    // $general_class - General wrapper class
    // $button_class - Button CSS class
    // $button_text - Button text
    // $up_vote_inner_text - Up vote button inner HTML
    // $down_vote_inner_text - Down vote button inner HTML (if enabled)
    // $display_likers - Whether to display likers
    // $likers_style - Likers display style
    // $attributes - Additional HTML attributes
    // $counter_value - Current counter value
    // $is_liked - Whether current user liked the item
    // $is_unliked - Whether current user unliked the item
    ?>
    
    <!-- Custom HTML Structure - You have full control here! -->
    <div class="wpulike wpulike-custom-template <?php echo esc_attr( $wrapper_class ); ?>" <?php echo $attributes; ?>>
        
        <!-- Custom wrapper with your own classes -->
        <div class="<?php echo esc_attr( $general_class ); ?> custom-like-wrapper">
            
            <!-- Like Button - Fully customizable -->
            <button type="button"
                aria-label="<?php echo esc_attr( wp_ulike_get_option( 'like_button_aria_label', __( 'Like Button', 'wp-ulike' ) ) ); ?>"
                data-ulike-id="<?php echo esc_attr( $ID ); ?>"
                data-ulike-nonce="<?php echo wp_create_nonce( $type . $ID ); ?>"
                data-ulike-type="<?php echo esc_attr( $type ); ?>"
                data-ulike-template="<?php echo esc_attr( $style ); ?>"
                data-ulike-display-likers="<?php echo esc_attr( $display_likers ); ?>"
                data-ulike-likers-style="<?php echo esc_attr( $likers_style ); ?>"
                class="<?php echo esc_attr( $button_class ); ?> custom-like-btn">
                
                <?php
                    // Display up vote icon/text
                    echo $up_vote_inner_text;
                    
                    // Fire inside button hook for additional content
                    do_action( 'wp_ulike_inside_like_button', $wp_ulike_template );
                    
                    // Display button text if text mode
                    if ( $button_type == 'text' ) {
                        echo '<span class="button-text">' . esc_html( $button_text ) . '</span>';
                    }
                ?>
            </button>
            
            <!-- Counter Display - Optional, position it anywhere -->
            <span class="custom-counter" data-counter="<?php echo esc_attr( $ID ); ?>">
                <?php echo esc_html( $counter_value ); ?>
            </span>
            
            <!-- Additional custom content -->
            <?php if ( $is_liked ) : ?>
                <span class="liked-indicator">✓ Liked</span>
            <?php endif; ?>
        </div>
        
        <?php
        // Fire inside template hook for additional content
        do_action( 'wp_ulike_inside_template', $wp_ulike_template );
        ?>
    </div>
    
    <?php
    // Fire after template hook
    do_action( 'wp_ulike_after_template', $wp_ulike_template );
    
    // Return buffered content
    return ob_get_clean();
}
```

**Usage in Your HTML/PHP Templates:**

Once registered, you can use your custom template anywhere:

```php
// In your theme template files
echo do_shortcode('[wp_ulike for="post" style="wpulike-custom-template"]');

// Or using PHP function
wp_ulike('put', array(
    'id'    => get_the_ID(),
    'style' => 'wpulike-custom-template',
    'wrapper_class' => 'my-custom-class'
));

// In custom HTML structure
?>
<div class="my-custom-layout">
    <h2>Post Title</h2>
    <div class="post-content">...</div>
    
    <!-- Place your custom like button anywhere -->
    <?php echo do_shortcode('[wp_ulike for="post" style="wpulike-custom-template"]'); ?>
    
    <div class="post-footer">...</div>
</div>
<?php
```

**Advanced: Multiple Button Layouts**

You can create templates with side-by-side buttons, vertical layouts, or any custom arrangement:

```php
function wp_ulike_horizontal_template( array $wp_ulike_template ) {
    ob_start();
    extract( $wp_ulike_template );
    ?>
    <div class="wpulike-horizontal-layout <?php echo esc_attr( $wrapper_class ); ?>">
        <div class="like-section">
            <button type="button"
                class="<?php echo esc_attr( $button_class ); ?> like-btn"
                data-ulike-id="<?php echo esc_attr( $ID ); ?>"
                data-ulike-nonce="<?php echo wp_create_nonce( $type . $ID ); ?>"
                data-ulike-type="<?php echo esc_attr( $type ); ?>"
                data-ulike-template="<?php echo esc_attr( $style ); ?>">
                <?php echo $up_vote_inner_text; ?>
                <span>Like</span>
            </button>
            <span class="like-count"><?php echo esc_html( $counter_value ); ?></span>
        </div>
        
        <!-- Add dislike button if enabled -->
        <?php if ( wp_ulike_get_option( 'enable_down_vote' ) ) : ?>
        <div class="dislike-section">
            <button type="button" class="dislike-btn">
                <!-- Dislike button HTML -->
            </button>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
```

**CSS Styling:**

Add custom CSS to style your template:

```css
.wpulike-custom-template .custom-like-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
}

.wpulike-custom-template .custom-like-btn {
    padding: 10px 20px;
    border: 2px solid #0073aa;
    background: transparent;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s;
}

.wpulike-custom-template .custom-like-btn:hover {
    background: #0073aa;
    color: white;
}

.wpulike-custom-template .custom-counter {
    font-weight: bold;
    font-size: 16px;
}
```

**Modify Button Selector:**
```php
add_filter('wp_ulike_button_selector', 'custom_button_selector', 10, 1);
function custom_button_selector($selector) {
    return 'my-custom-button-class';
}
```

**Modify General Selector:**
```php
add_filter('wp_ulike_general_selector', 'custom_general_selector', 10, 1);
function custom_general_selector($selector) {
    return 'my-custom-wrapper-class';
}
```

**Modify Method ID:**
```php
add_filter('wp_ulike_method_id', 'custom_method_id', 10, 2);
function custom_method_id($method_id, $instance) {
    // Customize the method ID
    return $method_id;
}
```

---

## 📡 JavaScript Events

WP ULike triggers custom JavaScript events that you can listen to for extending functionality. All events work with both vanilla JavaScript and jQuery.

### Core Like/Dislike Events

**When Like Process Starts:**
```javascript
document.addEventListener('WordpressUlikeLoading', function(event) {
    // event.detail contains the like button element
    const likeElement = event.detail;
    console.log('Like process started');
});
```

**When Like Process Completes:**
```javascript
document.addEventListener('WordpressUlikeUpdated', function(event) {
    // event.detail contains the like button element
    const likeElement = event.detail;
    console.log('Like process completed');
});
```

**When Counter is Updated:**
```javascript
document.addEventListener('WordpressUlikeCounterUpdated', function(event) {
    // event.detail contains array with button element
    const buttonElement = event.detail[0];
    console.log('Counter updated');
});
```

**When Likers Markup is Updated:**
```javascript
document.addEventListener('WordpressUlikeLikersMarkupUpdated', function(event) {
    // event.detail[0] = likers element
    // event.detail[1] = likers template type
    // event.detail[2] = template content
    const likersElement = event.detail[0];
    const templateType = event.detail[1];
    const templateContent = event.detail[2];
    console.log('Likers list updated');
});
```

### Notification Events

**When Notification is Appended:**
```javascript
document.addEventListener('WordpressUlikeNotificationAppend', function(event) {
    // event.detail.messageElement contains the notification element
    const notificationElement = event.detail.messageElement;
    console.log('Notification added');
});
```

**When Notification is Removed:**
```javascript
document.addEventListener('WordpressUlikeRemoveNotification', function(event) {
    // event.detail.messageElement contains the removed notification element
    const notificationElement = event.detail.messageElement;
    console.log('Notification removed');
});
```

### Tooltip Events

**When Tooltip is Shown:**
```javascript
document.addEventListener('ulf-show', function(event) {
    // event.detail.tooltip contains the tooltip element
    const tooltipElement = event.detail.tooltip;
    console.log('Tooltip shown');
});
```

**When Tooltip is Hidden:**
```javascript
document.addEventListener('ulf-hide', function(event) {
    // event.target contains the element that had the tooltip
    const element = event.target;
    console.log('Tooltip hidden');
});
```

**When Tooltip Content is Updated:**
```javascript
document.addEventListener('tooltip-content-updated', function(event) {
    // event.detail.element = wrapper element
    // event.detail.content = tooltip content
    const wrapperElement = event.detail.element;
    const content = event.detail.content;
    console.log('Tooltip content updated');
});
```

**When Tooltip Requests Data:**
```javascript
document.addEventListener('tooltip-request-data', function(event) {
    // Triggered when tooltip needs to fetch data
    // You can use this to provide custom data fetching logic
    console.log('Tooltip requesting data');
});
```

### jQuery Compatibility

All events also work with jQuery for backward compatibility:

```javascript
// jQuery syntax
jQuery(document).on('WordpressUlikeUpdated', function(event, data) {
    console.log('Like process completed');
});

// Or on specific element
jQuery('.wp_ulike_btn').on('WordpressUlikeCounterUpdated', function(event, data) {
    console.log('Counter updated');
});
```

### Example: Track All Likes

```javascript
document.addEventListener('WordpressUlikeUpdated', function(event) {
    const likeElement = event.detail;
    const itemId = likeElement.getAttribute('data-ulike-id');
    const itemType = likeElement.getAttribute('data-ulike-type');

    // Send to analytics
    if (typeof gtag !== 'undefined') {
        gtag('event', 'like', {
            'item_id': itemId,
            'item_type': itemType
        });
    }
});
```

### Example: Custom Notification Handler

```javascript
document.addEventListener('WordpressUlikeNotificationAppend', function(event) {
    const notification = event.detail.messageElement;

    // Add custom styling or behavior
    notification.addEventListener('click', function() {
        // Custom click handler
    });
});
```

---

## 🔗 Resources

- **Website:** [wpulike.com](https://wpulike.com/?utm_source=github-repo&utm_medium=link&utm_campaign=readme)
- **Documentation:** [docs.wpulike.com](https://docs.wpulike.com/?utm_source=github-repo&utm_medium=link&utm_campaign=readme)
- **Codex:** [docs.wpulike.com/category/36-codex](https://docs.wpulike.com/category/36-codex)
- **Demo:** [wpulike.com/templates](https://wpulike.com/templates/?utm_source=github-repo&utm_medium=link&utm_campaign=readme)
- **Support:** [WordPress.org Support Forums](https://wordpress.org/support/plugin/wp-ulike)
- **Issues:** [GitHub Issues](https://github.com/Alimir/wp-ulike/issues)

---

## 📋 Requirements

- **WordPress:** 6.0 or higher
- **PHP:** 7.2.5 or higher (8.1+ recommended)
- **MySQL:** 5.0 or higher (5.6+ recommended)

---

## 🤝 Contributing

Found a bug or have a feature request? Please [open an issue](https://github.com/Alimir/wp-ulike/issues) on GitHub.

---

## 📄 License

WP ULike is licensed under the GPL v2 or later.

```
Copyright (C) 2024 TechnoWich

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

---

## ⭐ Credits

Developed with ❤️ by [TechnoWich](https://technowich.com/?utm_source=github-repo&utm_medium=link&utm_campaign=readme)

**WP ULike** - Turn Visitors into Fans!
