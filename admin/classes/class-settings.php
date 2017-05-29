<?php
if ( ! class_exists( 'wp_ulike_settings' ) ) {

class wp_ulike_settings {

  private $page,
    $title,
    $menu,
	$admin_screen,
    $settings = array(),
    $empty = true,
    $notices = array();

  public function __construct( $page = 'custom_settings', $title = null, $menu = array(), $settings = array(), $args = array() )
  {
    $this->page = $page;
    $this->title = $title ? $title : __( 'Custom Settings', WP_ULIKE_SLUG );
    $this->menu = is_array( $menu ) ? array_merge( array(
      'parent'     => 'themes.php',
      'title'      => $this->title,
      'capability' => 'manage_options',
      'icon_url'   => null,
      'position'   => null
    ), $menu ) : false;
    $this->apply_settings( $settings );
    $this->args  = array_merge( array(
      'description' => null,
      'submit'      => __( 'Save Settings', WP_ULIKE_SLUG ),
      'reset'       => __( 'Reset Settings', WP_ULIKE_SLUG ),
      'tabs'        => false,
      'updated'     => null
    ), $args );
    add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    add_action( 'admin_init', array( $this, 'admin_init' ) );
  }
  

  public function create_help_screen()
  {
	$current_screen = get_current_screen();
	$this->admin_screen = WP_Screen::get($current_screen);
	$this->admin_screen->add_help_tab(
		array(
			'title'    => __('Similar Settings',WP_ULIKE_SLUG),
			'id'       => 'overview_tab',
			'content'  => '<p>' . __('WP ULike plugin allows to integrate a beautiful Ajax Like Button into your wordPress website to allow your visitors to like and unlike pages, posts, comments AND buddypress activities. Its very simple to use and supports many options.', WP_ULIKE_SLUG) . '</p>'.
			
			'<p>'.'<strong>'.__( 'Logging Method',WP_ULIKE_SLUG).' : </strong></p>'.
			'<ul>'.
			'<li>'.__('If you select <strong>"Do Not Log"</strong> method: Any data logs can\'t save, There is no limitation in the like/dislike, unlike/undislike capacity do not work', WP_ULIKE_SLUG).'</li>'.
			'<li>'.__('If you select <strong>"Logged By Cookie"</strong> method: Any data logs can\'t save, The like/dislike condition will be limited by SetCookie, unlike/undislike capacity do not work', WP_ULIKE_SLUG).'</li>'.
			'<li>'.__('If you select <strong>"Logged By IP"</strong> method: Data logs will save for all users, the convey of like/dislike condition will check by user IP', WP_ULIKE_SLUG).'</li>'.
			'<li>'.__('If you select <strong>"Logged By Cookie & IP"</strong> method: Data logs will save for all users, the convey of like/dislike condition will check by user IP & SetCookie', WP_ULIKE_SLUG).'</li>'.
			'<li>'.__('If you select <strong>"Logged By Username"</strong> method: data logs only is saved for registered users, the convey of like/dislike condition will check by username, There is no permission for guest users to unlike/undislike', WP_ULIKE_SLUG).'</li>
			</ul>'.
			
			'<p>'.'<strong>'.__( 'Template Variables',WP_ULIKE_SLUG).' : </strong></p>'.
			'<ul>'.
			'<li>'.'<code>%START_WHILE%</code> : '		. __('Start the loop of logs',WP_ULIKE_SLUG) .' <span style="color:red">('.__( 'required',WP_ULIKE_SLUG).')</span></li>'.
			'<li>'.'<code>%END_WHILE%</code> : '		. __('End of the while loop',WP_ULIKE_SLUG) .' <span style="color:red">('.__( 'required',WP_ULIKE_SLUG).')</span></li>'.
			'<li>'.'<code>%USER_NAME%</code> : '		. __('Display the liker name',WP_ULIKE_SLUG) .'</li>'.
			'<li>'.'<code>%USER_AVATAR%</code> : '		. __('Display the liker avatar (By Gravatar)',WP_ULIKE_SLUG) .'</li>'.
			'<li>'.'<code>%BP_PROFILE_URL%</code> : '	. __('Display the BuddyPress user profile url',WP_ULIKE_SLUG) .'</li>'.
			'<li>'.'<code>%UM_PROFILE_URL%</code> : '	. __('Display the UltimateMemebr user profile url',WP_ULIKE_SLUG) .'</li><hr>'.
			'<li>'.'<code>%POST_LIKER%</code> : '		. __('Display the liker name',WP_ULIKE_SLUG) .'</li>'.
			'<li>'.'<code>%POST_PERMALINK%</code> : '	. __('Display the permalink',WP_ULIKE_SLUG) .'</li>'.
			'<li>'.'<code>%POST_COUNT%</code> : '		. __('Display the likes count number',WP_ULIKE_SLUG) .'</li>'.
			'<li>'.'<code>%POST_TITLE%</code> : '		. __('Display the post title',WP_ULIKE_SLUG) .'</li><hr>'.
			'<li>'.'<code>%COMMENT_LIKER%</code> : '	. __('Display the liker name',WP_ULIKE_SLUG) .'</li>'.
			'<li>'.'<code>%COMMENT_PERMALINK%</code> : '. __('Display the permalink',WP_ULIKE_SLUG) .'</li>'.
			'<li>'.'<code>%COMMENT_AUTHOR%</code> : '	. __('Display the comment author name',WP_ULIKE_SLUG) .'</li>'.
			'<li>'.'<code>%COMMENT_COUNT%</code> : '	. __('Display the likes count number',WP_ULIKE_SLUG) .'</li>'.
			'</ul>'			
			,
			'callback' => false
		)
	);
	$this->admin_screen->add_help_tab(
		array(
			'title'    => __( 'Posts',WP_ULIKE_SLUG),
			'id'       => 'posts_tab',
			'content'  => '<p>'.'<strong>'.__('Automatic display', WP_ULIKE_SLUG).' : </strong></p><ul><li>'.__('If you disable this option, you have to put manually this code on wordpress while loop', WP_ULIKE_SLUG) . '<br /><code dir="ltr">&lt;?php if(function_exists(\'wp_ulike\')) wp_ulike(\'get\'); ?&gt;</code>'.'</li></ul>'.'<p>'.'<strong>'.__('Users Like Box Template', WP_ULIKE_SLUG) . ' - ' . __('Default Template:', WP_ULIKE_SLUG) .' </strong></p><ul><li><code>&lt;p style="margin-top:5px"&gt; '.__('Users who have LIKED this post:',WP_ULIKE_SLUG).'&lt;/p&gt; &lt;ul class="tiles"&gt;%START_WHILE%&lt;li&gt;&lt;a  href="%BP_PROFILE_URL%" class="user-tooltip" title="%USER_NAME%"&gt;%USER_AVATAR%&lt;/a&gt;&lt;/li&gt;%END_WHILE%&lt;/ul&gt;</code>'.'</li></ul>',
			
			'callback' => false
		)
	);
	$this->admin_screen->add_help_tab(
		array(
			'title'    => __( 'Comments',WP_ULIKE_SLUG),
			'id'       => 'comments_tab',
			'content'  => '<p>'.'<strong>'.__('Automatic display', WP_ULIKE_SLUG).' : </strong></p><ul><li>'.__('If you disable this option, you have to put manually this code on comments text', WP_ULIKE_SLUG) . '<br /><code dir="ltr">&lt;?php if(function_exists(\'wp_ulike_comments\')) wp_ulike_comments(\'get\'); ?&gt;</code>'.'</li></ul>' . '<p>'.'<strong>'.__('Users Like Box Template', WP_ULIKE_SLUG) . ' - ' . __('Default Template:', WP_ULIKE_SLUG) .' </strong></p><ul><li><code>&lt;p style="margin-top:5px"&gt; '.__('Users who have LIKED this comment:',WP_ULIKE_SLUG).'&lt;/p&gt; &lt;ul class="tiles"&gt;%START_WHILE%&lt;li&gt;&lt;a  href="%BP_PROFILE_URL%" class="user-tooltip" title="%USER_NAME%"&gt;%USER_AVATAR%&lt;/a&gt;&lt;/li&gt;%END_WHILE%&lt;/ul&gt;</code>'.'</li></ul>',
			'callback' => false
		)
	);
	$this->admin_screen->add_help_tab(
		array(
			'title'    => __( 'BuddyPress',WP_ULIKE_SLUG),
			'id'       => 'bp_tab',
			'content'  => '<p>'.'<strong>'.__('Automatic display', WP_ULIKE_SLUG).' : </strong></p><ul><li>'.__('If you disable this option, you have to put manually this code on buddypres activities content', WP_ULIKE_SLUG) . '<br /><code dir="ltr">&lt;?php if(function_exists(\'wp_ulike_buddypress\')) wp_ulike_buddypress(\'get\'); ?&gt;</code>'.'</li></ul>' . '<p>'.'<strong>'.__('Users Like Box Template', WP_ULIKE_SLUG) . ' - ' . __('Default Template:', WP_ULIKE_SLUG) .' </strong></p><ul><li><code>&lt;p style="margin-top:5px"&gt; '.__('Users who have liked this activity:',WP_ULIKE_SLUG).'&lt;/p&gt; &lt;ul class="tiles"&gt;%START_WHILE%&lt;li&gt;&lt;a  href="%BP_PROFILE_URL%" class="user-tooltip" title="%USER_NAME%"&gt;%USER_AVATAR%&lt;/a&gt;&lt;/li&gt;%END_WHILE%&lt;/ul&gt;</code>'.'</li></ul>'.'<p>'.'<strong>'.__('Post Activity Text', WP_ULIKE_SLUG) . ' - ' . __('Default Template:', WP_ULIKE_SLUG) .' </strong></p><ul><li><code>&lt;strong&gt;%POST_LIKER%&lt;/strong&gt; liked &lt;a href="%POST_PERMALINK%" title="%POST_TITLE%"&gt;%POST_TITLE%&lt;/a&gt;. (So far, This post has &lt;span class="badge"&gt;%POST_COUNT%&lt;/span&gt; likes)</code>'.'</li></ul>' . '<p>'.'<strong>'.__('Comment Activity Text', WP_ULIKE_SLUG) . ' - ' . __('Default Template:', WP_ULIKE_SLUG) .' </strong></p><ul><li><code>&lt;strong&gt;%COMMENT_LIKER%&lt;/strong&gt; liked &lt;strong&gt;%COMMENT_AUTHOR%&lt;/strong&gt; comment. (So far, %COMMENT_AUTHOR% has &lt;span class="badge"&gt;%COMMENT_COUNT%&lt;/span&gt; likes for this comment)</code>'.'</li></ul>',
			'callback' => false
		)
	);
	$this->admin_screen->add_help_tab(
		array(
			'title'    => __( 'bbPress',WP_ULIKE_SLUG),
			'id'       => 'bb_tab',
			'content'  => '<p>'.'<strong>'.__('Automatic display', WP_ULIKE_SLUG).' : </strong></p><ul><li>'.__('If you disable this option, you have to put manually this code on buddypres activities content', WP_ULIKE_SLUG) . '<br /><code dir="ltr">&lt;?php if(function_exists(\'wp_ulike_bbpress\')) wp_ulike_bbpress(\'get\'); ?&gt;</code>'.'</li></ul>' . '<p>'.'<strong>'.__('Users Like Box Template', WP_ULIKE_SLUG) . ' - ' . __('Default Template:', WP_ULIKE_SLUG) .' </strong></p><ul><li><code>&lt;p style="margin-top:5px"&gt; '.__('Users who have liked this activity:',WP_ULIKE_SLUG).'&lt;/p&gt; &lt;ul class="tiles"&gt;%START_WHILE%&lt;li&gt;&lt;a  href="%BP_PROFILE_URL%" class="user-tooltip" title="%USER_NAME%"&gt;%USER_AVATAR%&lt;/a&gt;&lt;/li&gt;%END_WHILE%&lt;/ul&gt;</code>'.'</li></ul>',
			'callback' => false
		)
	);
	$this->admin_screen->set_help_sidebar(
		'<p><strong>'.__('For more information:').'</strong></p><p><a href="https://wordpress.org/plugins/wp-ulike/faq/" target="_blank">'.__('FAQ',WP_ULIKE_SLUG).'</a></p><p><a href="https://wordpress.org/support/plugin/wp-ulike" target="_blank">'.__('Support',WP_ULIKE_SLUG).'</a></p>'
	);
  }

  public function apply_settings( $settings )
  {
    if ( is_array( $settings ) ) {
      foreach ( $settings as $setting => $section ) {
        $section = array_merge( array(
          'title'       => null,
          'description' => null,
          'fields'      => array()
        ), $section );
        foreach ( $section['fields'] as $name => $field ) {
          $field = array_merge( array(
            'type'        	 => 'text',
            'label'       	 => null,
            'checkboxlabel'  => null,
            'description' 	 => null,
            'default'     	 => null,
            'sanitize'    	 => null,
            'attributes'  	 => array(),
            'options'     	 => null,
            'action'      	 => null
          ), $field );
          $section['fields'][$name] = $field;
        }
        $this->settings[$setting] = $section;
        if ( ! get_option( $setting ) ) {
          add_option( $setting, $this->get_defaults( $setting ) );
        }
      }
    }
  }

  public function add_notice( $message, $type = 'info' )
  {
    $this->notices[] = array(
      'message' => $message,
      'type'    => $type
    );
  }

  private function get_defaults( $setting )
  {
    $defaults = array();
    foreach ( $this->settings[$setting]['fields'] as $name => $field ) {
      if ( $field['default'] !== null ) {
        $defaults[$name] = $field['default'];
      }
    }
    return $defaults;
  }

  private function reset()
  {
    foreach ( $this->settings as $setting => $section ) {
      $_POST[$setting] = array_merge( $_POST[$setting], $this->get_defaults( $setting ) );
    }
    add_settings_error( $this->page, 'settings_reset', __( 'Default settings have been reset.', WP_ULIKE_SLUG ), 'updated' );
  }

  public function admin_menu()
  {
    if ( $this->menu ) {
      if ( $this->menu['parent'] ) {
        $page = add_submenu_page( $this->menu['parent'], $this->title, $this->menu['title'], $this->menu['capability'], $this->page, array( $this, 'do_page' ) );
      } else {
        $page = add_menu_page( $this->title, $this->menu['title'], $this->menu['capability'], $this->page, array( $this, 'do_page' ), $this->menu['icon_url'], $this->menu['position'] );
        if ( $this->title !== $this->menu['title'] ) {
          add_submenu_page( $this->page, $this->title, $this->title, $this->menu['capability'], $this->page );
        }
      }
      add_action( 'load-' . $page, array( $this, 'load_page' ) );
	  add_action( 'load-' . $page, array(&$this, 'create_help_screen'));
    }
  }

  public function load_page()
  {
    global $wp_settings_errors;
    foreach ( $this->notices as $notice ) {
      $wp_settings_errors[] = array_merge( $notice, array(
        'setting' => $this->page,
        'code'    => $notice['type'] . '_notice'
      ) );
    }
    if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
      if ( $this->args['updated'] !== null && $notices = get_transient( 'settings_errors' ) ) {
        delete_transient( 'settings_errors' );
        foreach ( $notices as $i => $notice ) {
          if ( $notice['setting'] === 'general' && $notice['code'] === 'settings_updated' ) {
            if ( $this->args['updated'] ) {
              $notice['message'] = (string) $this->args['updated'];
            } else {
              continue;
            }
          }
          $wp_settings_errors[] = $notice;
        }
      }
      do_action( "{$this->page}_settings_updated" );
    }
    add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
  }

  public static function admin_enqueue_scripts()
  {
    wp_enqueue_media();
    wp_enqueue_script( 'wm-settings', plugins_url( 'js/settings.js' , __FILE__ ), array( 'jquery', 'wp-color-picker' ) );
	  wp_enqueue_script("jquery-effects-core");
    wp_localize_script( 'wm-settings', 'ajax', array(
      'url' => admin_url( 'admin-ajax.php' ),
      'spinner' => admin_url( 'images/spinner.gif' )
    ) );
	if(!is_rtl())
    wp_enqueue_style( 'wm-settings', plugins_url( 'css/settings.css' , __FILE__ ) );
	else
	wp_enqueue_style( 'wm-settings', plugins_url( 'css/settings-rtl.css' , __FILE__ ) );
    wp_enqueue_style( 'wp-color-picker' );
  }

  public function do_page()
  { ?>
    <form action="options.php" method="POST" enctype="multipart/form-data" class="wrap">
      <h2><?php echo $this->title; ?></h2>
      <?php
        settings_errors();
        if ( $text = $this->args['description'] ) { echo wpautop( $text ); }
        do_settings_sections( $this->page );
        if ( ! $this->empty ) {
          settings_fields( $this->page );
          if ( $this->args['tabs'] && count( $this->settings ) > 1 ) { ?>
            <div class="wm-settings-tabs"></div>
          <?php }
          submit_button( $this->args['submit'], 'large primary' );
          if ( $this->args['reset'] ) {
            submit_button( $this->args['reset'], 'small', "{$this->page}_reset", true, array( 'onclick' => "return confirm('" . __( 'Do you really want to reset all these settings to their default values ?', WP_ULIKE_SLUG ) . "');" ) );
          }
        }
      ?>
    </form>
  <?php }

  public function admin_init()
  {
    foreach ( $this->settings as $setting => $section ) {
      register_setting( $this->page, $setting, array( $this, 'sanitize_setting' ) );
      add_settings_section( $setting, $section['title'], array( $this, 'do_section' ), $this->page );
      if ( ! empty( $section['fields'] ) ) {
        $this->empty = false;
        $values = wp_ulike_get_setting( $setting );
        foreach ( $section['fields'] as $name => $field ) {
          $id = $setting . '_' . $name;
          $field = array_merge( array(
            'id'    => $id,
            'name'    => $setting . '[' . $name . ']',
            'value'   => isset( $values[$name] ) ? $values[$name] : null,
            'label_for' => $field['label'] === false ? 'hidden' : $id
          ), $field );
          add_settings_field( $name, $field['label'], array( __CLASS__, 'do_field' ), $this->page, $setting, $field );
          if ( $field['type'] === 'action' && is_callable( $field['action'] ) ) {
            add_action( "wp_ajax_{$setting}_{$name}", $field['action'] );
          }
        }
      }
    }
    if ( isset( $_POST["{$this->page}_reset"] ) ) {
      $this->reset();
    }
  }

  public function do_section( $args )
  {
    extract( $args );
    echo "<input name='{$id}[{$this->page}_setting]' type='hidden' value='{$id}' class='wm-settings-section' />";
    if ( $text = $this->settings[$id]['description'] ) {
      echo wpautop( $text );
    }
  }

  public static function do_field( $args )
  {
    extract( $args );
    $attrs = "name='{$name}'";
    foreach ( $attributes as $k => $v ) {
      $k = sanitize_key( $k );
      $v = esc_attr( $v );
      $attrs .= " {$k}='{$v}'";
    }
    $desc = $description ? "<p class='description'>{$description}</p>" : '';
    switch ( $type )
    {
      case 'checkbox':
        $check = checked( 1, $value, false );
        echo "<label><input {$attrs} id='{$id}' type='checkbox' value='1' {$check} />";
        if ( $checkboxlabel ) { echo " {$checkboxlabel}"; }
        echo "</label>";
		if ( $desc ) { echo " {$desc}"; }
        break;

      case 'radio':
        if ( ! $options ) { _e( 'No options defined.', WP_ULIKE_SLUG ); }
        echo "<fieldset id='{$id}'>";
        foreach ( $options as $v => $label ) {
          $check = checked( $v, $value, false );
          $options[$v] = "<label><input {$attrs} class='wp_ulike_check_{$v}' type='radio' value='{$v}' {$check} /> {$label}</label>";
        }
        echo implode( '<br />', $options );
        echo "{$desc}</fieldset>";
        break;

      case 'select':
        if ( ! $options ) { _e( 'No options defined.', WP_ULIKE_SLUG ); }
        echo "<select {$attrs} id='{$id}'>";
        foreach ( $options as $v => $label ) {
          $select = selected( $v, $value, false );
          echo "<option value='{$v}' {$select} />{$label}</option>";
        }
        echo "</select>{$desc}";
        break;

      case 'media':
        echo "<fieldset class='wm-settings-media' id='{$id}'><input {$attrs} type='hidden' value='{$value}' />";
        echo "<p><a class='button button-large wm-select-media' title='{$label}'>" . sprintf( __( 'Select %s', WP_ULIKE_SLUG ), $label ) . "</a> ";
        echo "<a class='button button-small wm-remove-media' title='{$label}'>" . sprintf( __( 'Remove %s', WP_ULIKE_SLUG ), $label ) . "</a></p>";
        if ( $value ) {
          echo wpautop( wp_get_attachment_image( $value, 'medium' ) );
        }
        echo "{$desc}</fieldset>";
        break;

      case 'textarea':
        echo "<textarea {$attrs} id='{$id}' class='large-text'>{$value}</textarea>{$desc}";
        break;

      case 'multi':
        if ( ! $options ) { _e( 'No options defined.', WP_ULIKE_SLUG ); }
        echo "<fieldset id='{$id}'>";
        foreach ( $options as $n => $label ) {
          $a = preg_replace( "/name\=\'(.+)\'/", "name='$1[{$n}]'", $attrs );
          $check = checked( 1, $value[$n], false );
          $options[$n] = "<label><input {$a} type='checkbox' value='1' {$check} /> {$label}</label>";
        }
        echo implode( '<br />', $options );
        echo "{$desc}</fieldset>";
        break;

      case 'action':
        if ( ! $action ) { _e( 'No action defined.', WP_ULIKE_SLUG ); }
        echo "<p class='wm-settings-action'><input {$attrs} id='{$id}' type='button' class='button button-large' value='{$label}' /></p>{$desc}";
        break;

      case 'color':
        $v = esc_attr( $value );
        echo "<input {$attrs} id='{$id}' type='text' value='{$v}' class='wm-settings-color' />{$desc}";
        break;

      default:
        $v = esc_attr( $value );
        echo "<input {$attrs} id='{$id}' type='{$type}' value='{$v}' class='regular-text' />{$desc}";
        break;
    }
  }

  public function sanitize_setting( $inputs )
  {
    $values = array();
    if ( ! empty( $inputs["{$this->page}_setting"] ) ) {
      $setting = $inputs["{$this->page}_setting"];
      foreach ( $this->settings[$setting]['fields'] as $name => $field ) {
        $input = array_key_exists( $name, $inputs ) ? $inputs[$name] : null;
        if ( $field['sanitize'] ) {
          $values[$name] = call_user_func( $field['sanitize'], $input, $name );
        } else {
          switch ( $field['type'] )
          {
            case 'checkbox':
              $values[$name] = $input ? 1 : 0;
              break;

            case 'radio':
            case 'select':
              $values[$name] = sanitize_key( $input );
              break;

            case 'media':
              $values[$name] = absint( $input );
              break;

            case 'color':
              $values[$name] = preg_match( '/^#[a-f0-9]{6}$/i', $input ) ? $input : '';
              break;

            case 'textarea':
              $text = '';
              $nl = "WM-SETTINGS-NEW-LINE";
              $tb = "WM-SETTINGS-TABULATION";
              $lines = explode( $nl, str_replace( "\t", $tb, str_replace( "\n", $nl, $input ) ) );
              foreach ( $lines as $line ) {
                $text .= str_replace( $tb, "\t", trim( $line ) ) . "\n";
              }
              $values[$name] = trim( $text );
              break;

            case 'multi':
              if ( ! $input || empty( $field['options'] ) ) { break; }
              foreach ( $field['options'] as $n => $opt ) {
                $input[$n] = empty( $input[$n] ) ? 0 : 1;
              }
              $values[$name] = json_encode( $input );
              break;

            case 'action':
              break;

            case 'email':
              $values[$name] = sanitize_email( $input );
              break;

            case 'url':
              $values[$name] = esc_url_raw( $input );
              break;

            case 'number':
              $values[$name] = floatval( $input );
              break;

            default:
              $values[$name] = html_entity_decode( $input );
              break;
          }
        }
      }
      return $values;
    }
    return $inputs;
  }

  public static function parse_multi( $result )
  {
    // Check if the result was recorded as JSON, and if so, returns an array instead
    return ( is_string( $result ) && $array = json_decode( $result, true ) ) ? $array : $result;
  }

  public static function plugin_priority()
  {
    $wp_ulike_settings = plugin_basename( __FILE__ );
    $active_plugins = get_option( 'active_plugins' );
    if ( $order = array_search( $wp_ulike_settings, $active_plugins ) ) {
      array_splice( $active_plugins, $order, 1 );
      array_unshift( $active_plugins, $wp_ulike_settings );
      update_option( 'active_plugins', $active_plugins );
    }
  }
}
add_action( 'activated_plugin', array( 'wp_ulike_settings', 'plugin_priority' ) );

function wp_ulike_get_setting( $setting, $option = false )
{
  $setting = get_option( $setting );
  if ( is_array( $setting ) ) {
    if ( $option ) {
      return isset( $setting[$option] ) ? wp_ulike_settings::parse_multi( $setting[$option] ) : false;
    }
    foreach ( $setting as $k => $v ) {
      $setting[$k] = wp_ulike_settings::parse_multi( $v );
    }
    return $setting;
  }
  return $option ? false : $setting;
}

function wp_ulike_create_settings_page( $page = 'custom_settings', $title = null, $menu = array(), $settings = array(), $args = array() )
{
  return new wp_ulike_settings( $page, $title, $menu, $settings, $args );
}

}

?>
