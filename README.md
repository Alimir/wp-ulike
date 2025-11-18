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

// Legacy function
$wp_query = wp_ulike_get_most_liked_posts(10, array('post'), 'post', 'all', 'like');
```

---

## 🎨 Customization Hooks

### Data & Process Actions

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
add_filter('wp_ulike_validate_blacklist', 'custom_blacklist_check', 10, 3);
function custom_blacklist_check($isValid, $target, $args) {
    // Custom blacklist validation logic
    return $isValid;
}
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

**Modify Database IP:**
```php
add_filter('wp_ulike_database_user_ip', 'modify_db_ip', 10, 1);
function modify_db_ip($ip) {
    // Modify IP before saving to database
    return $ip;
}
```

**Modify Client Identifier:**
```php
add_filter('wp_ulike_generate_client_identifier', 'custom_client_id', 10, 2);
function custom_client_id($identifier, $user_ip) {
    // Customize client identifier generation
    return $identifier;
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

**Modify Post Settings by Type:**
```php
add_filter('wp_ulike_get_post_settings_by_type', 'custom_post_settings', 10, 2);
function custom_post_settings($settings, $post_ID) {
    // Modify settings for specific post types
    return $settings;
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
