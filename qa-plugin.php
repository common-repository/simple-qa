<?php
/**
 * Plugin Name: Simple Q&A
 * Plugin URI: http://wp.starcoms.ru/qa-plugin/
 * Description: Simple Plugin to let your users ask questions.
 * Version: 1.0
 * Author: jon4god
 * Author URI: http://starcoms.ru
 * Text Domain: qa-plugin
 * License: GPL2
 * Domain Path: /languages/
 */

function qa_init() {
  $plugin_dir = basename(dirname(__FILE__));
  load_plugin_textdomain( 'qa-plugin', false, $plugin_dir . '/languages/' );
  define('qa-plugin-dir', plugin_dir_path(__FILE__));
}
add_action('plugins_loaded', 'qa_init');

function qa_activate() {
  set_transient( 'qa-admin-notice', true, 5 );
}
register_activation_hook( __FILE__, 'qa_activate');

function qa_on_activation_note() {
  if( get_transient( 'qa-admin-notice' ) ){
      echo '<div class="updated notice is-dismissible">
      <p>' .__('Please, set setting for this plugin.', 'qa-plugin') . '</p> 
      </div>';
    delete_transient( 'qa-admin-notice' );
  }
}
add_action( 'admin_notices', 'qa_on_activation_note' );

$plugin_file = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin_file", 'qa_plugin_settings_link' );
function qa_plugin_settings_link($links) { 
	$settings_link = '<a href="options-general.php?page=qa-plugin">' . __('Settings', 'qa-plugin') . '</a>'; 
	array_unshift( $links, $settings_link ); 
	return $links; 
}

function create_qa_postype() {
  $labels = array(
    'name' => __('Q&A', 'qa-plugin'),
    'singular_name' => __('Q&A', 'qa-plugin'),
    'add_new' => __('New Q&A', 'qa-plugin'),
    'add_new_item' => __('Add new Q&A', 'qa-plugin'),
    'edit_item' => __('Edit Q&A', 'qa-plugin'),
    'new_item' => __('New Q&A', 'qa-plugin'),
    'view_item' => __('View Q&A', 'qa-plugin'),
    'search_items' => __('Search Q&A', 'qa-plugin'),
    'not_found' =>  __('No Q&A found', 'qa-plugin'),
    'not_found_in_trash' => __('No Q&A found in Trash', 'qa-plugin'),
    'parent_item_colon' => '',
  );
  $args = array(
    'label' => __('Q&A', 'qa-plugin'),
    'labels' => $labels,
    'public' => false,
    'can_export' => true,
    'show_ui' => true,
    'menu_position'     => 24,
    '_builtin' => false,
    'capability_type' => 'post',
    'menu_icon'         => 'dashicons-format-chat',
    'hierarchical' => false,
    'rewrite' => array( "slug" => "qa" ),
    'supports'=> array('title', 'editor', 'comments'),
    'show_in_nav_menus' => true
  );
  register_post_type( 'qa', $args);
}
add_action( 'init', 'create_qa_postype' );

function create_qa_tags() {
  $labels = array(
    'name'              => __( 'Q&A Tags', 'qa-plugin'),
    'singular_name'     => __( 'Q&A Tag', 'qa-plugin'),
    'search_items'      => __( 'Search Q&A Tags', 'qa-plugin'),
    'all_items'         => __( 'All Q&A Tags', 'qa-plugin'),
    'parent_item'       => __( 'Parent Q&A Tag', 'qa-plugin'),
    'parent_item_colon' => __( 'Parent Q&A Tag:', 'qa-plugin'),
    'edit_item'         => __( 'Edit Q&A Tag', 'qa-plugin'),
    'update_item'       => __( 'Update Q&A Tag', 'qa-plugin'),
    'add_new_item'      => __( 'Add New Q&A Tag', 'qa-plugin'),
    'new_item_name'     => __( 'New Q&A Tag Name', 'qa-plugin'),
    'menu_name'         => __( 'Q&A Tags', 'qa-plugin'),
  );
  $args = array(
    'hierarchical'      => false,
    'labels'            => $labels,
    'show_ui'           => true,
    'show_admin_column' => true,
    'query_var'         => true,
    'rewrite'           => array( 'slug' => 'qatag' ),
  );
  register_taxonomy( 'qatag', 'qa', $args );
}
add_action('init', 'create_qa_tags');

function qa_title_placeholder( $title ){

    $screen = get_current_screen();

    if ( 'qa' == $screen->post_type ){
        $title = __('your question here', 'qa-plugin');
    }

    return $title;
}
add_filter( 'enter_title_here', 'qa_title_placeholder' );

function show_qa(  ) {
  $isCaptcha = get_option('qa_setting_captcha');
  if($isCaptcha)
  {
    require_once(dirname(__FILE__).'/ReCaptcha/ReCaptcha.php');
    require_once(dirname(__FILE__).'/ReCaptcha/RequestMethod.php');
    require_once(dirname(__FILE__).'/ReCaptcha/RequestParameters.php');
    require_once(dirname(__FILE__).'/ReCaptcha/Response.php');
    require_once(dirname(__FILE__).'/ReCaptcha/RequestMethod/Post.php');
    require_once(dirname(__FILE__).'/ReCaptcha/RequestMethod/Socket.php');
    require_once(dirname(__FILE__).'/ReCaptcha/RequestMethod/SocketPost.php');
    $publickey = get_option( 'qa_setting_captcha_publickey' );
    $privatekey = get_option( 'qa_setting_captcha_privatekey' );
    $resp = null;
    $error = null;
    $lang = get_bloginfo('language');
  }
  ob_start();
  wp_enqueue_style( 'qa', plugins_url('qa-plugin.css',__FILE__) );
  if( 'POST' == $_SERVER['REQUEST_METHOD']
    && !empty( $_POST['action'] )
    && $_POST['post_type'] == 'qa' && $_POST['question'] != "")
  {
    if ($isCaptcha && $_POST['g-recaptcha-response'])
  {
    $recaptcha = new \ReCaptcha\ReCaptcha($privatekey);
    $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
    if ($resp->isSuccess()) {
      $title =  $_POST['question'];
      $post = array(
        'post_title'  => $title,
        'post_status' => 'draft',
        'post_type'   => 'qa'
      );
      $id = wp_insert_post($post);
      echo "<div class='alert success'>".__('<b>Success!</b> Q&A is now ready for approval.', 'qa-plugin')."</div>";
      if(isset($_POST['username']))
      {
        add_post_meta($id, 'qa_username', $_POST['username']);
      }
      if(isset($_POST['email']))
      {
        add_post_meta($id, 'qa_email', $_POST['email']);
      }
      if(isset($_POST['ip']))
      {
        add_post_meta($id, 'qa_ip', $_POST['ip']);
      }
      if(get_option('qa_setting_email') == true)
      {
        $mailtext = __('New Q&A Received', 'qa-plugin');
        $admin_email = get_option('qa_setting_default_email');
        if (empty($admin_email)) $admin_email = get_option('admin_email');
        wp_mail( $admin_email,  $mailtext, "Q&A: ".$title);
      }
    } 
    else {
      $resp->getErrorCodes();
      echo "<div class='alert danger'>".__('<b>Error!</b> The Captcha was wrong.', 'qa-plugin')."</div>";
    }
  }
  else if (!$isCaptcha)
  {
    $title =  $_POST['question'];
    $post = array(
      'post_title'  => $title,
      'post_status' => 'draft',
      'post_type'   => 'qa'
    );
    $id = wp_insert_post($post);
    echo "<div class='alert success'>".__('<b>Success!</b> Q&A is now ready for approval.', 'qa-plugin')."</div>";
    if(isset($_POST['username']))
    {
        add_post_meta($id, 'qa_username', $_POST['username']);
    }
    if(isset($_POST['email']))
    {
        add_post_meta($id, 'qa_email', $_POST['email']);
    }
    if(isset($_POST['ip']))
    {
        add_post_meta($id, 'qa_ip', $_POST['ip']);
    }
    if(get_option('qa_setting_email') == true)
    {
      $mailtext = __('New Q&A Received', 'qa-plugin');
      $admin_email = get_option('qa_setting_default_email');
      if (empty($admin_email)) $admin_email = get_option('admin_email');
      wp_mail( $admin_email,  $mailtext, "Q&A: ".$title);
    }
  }
  else
  {
    echo "<div class='alert danger'>".__('<b>Error!</b> You have to fill out the Captcha.', 'qa-plugin')."</div>";
  }
  }
  else if('POST' == $_SERVER['REQUEST_METHOD'] && !empty( $_POST['action'] ) && $_POST['question'] == "")
  {
    echo "<div class='alert danger'>".__('<b>Error!</b> You have to fill out the Question.', 'qa-plugin')."</div>";
  }
  ?>
  <div class="qa">
    <script type="text/javascript" src="<?php echo plugin_dir_url(__FILE__) ?>js/qa.js"></script>
<!--
    <?php $terms = get_terms("qatag");
          if( count($terms) > 0 ){
          echo "<ul class='qa_tags '>";
          echo '<li><a href="#"><span class="label">' . __('All', 'qa-plugin') . '</span></a></li>';
          foreach ($terms as $term) {
            echo '<li><a href="#"><span class="label">'. $term->name .'</span></a></li>'; 
            }
          echo "</ul>";
          } ?>
-->
    <form id="newqa" name="newqa" method="post" action="">
      <label for="question" id="questionLabel"><?php _e('Question to ask', 'qa-plugin'); ?></label><br />
      <input type="text" class="question" value="" tabindex="1" size="20" name="question" />
      <input type="hidden" class="ip" value="<?php echo $_SERVER['REMOTE_ADDR'] ?>" name="ip" />
      <?php
      if(get_option('qa_setting_user_response') == true)
      {
          echo display_userdatafields();
      }
      if($isCaptcha)
      {
          ?>
      <div class="g-recaptcha" data-sitekey="<?php echo $publickey; ?>"></div>
      <script type="text/javascript"
        src="https://www.google.com/recaptcha/api.js?hl=<?php echo $lang; ?>">
      </script>
      <?php } ?>
      <div><input class="btn btn-primary submit" type="submit" value="<?php _e('Submit', 'qa-plugin'); ?>" tabindex="6" name="submit" /></div>
      <input type="hidden" name="post_type" id="post_type" value="qa" />
      <input type="hidden" name="action" value="post" />
      <?php wp_nonce_field( 'new-post' ); ?>
    </form>
  </div>
  <?php
  qa_output_normal();
  $output = ob_get_contents();
  ob_end_clean();
  return $output;
}
add_shortcode('qa', 'show_qa');

function display_userdatafields() {
  ob_start(); ?>
  <div class="userdatafields">
      <div class="userDiv">
          <div class="userlabel"><label for="username"><?php _e('Name', 'qa-plugin'); ?></label></div>
          <div class="userinput"><input type="text" class="username" value="" tabindex="1" size="20" name="username" /></div>
      </div>
      <div class="userDiv">
          <div class="userlabel"><label for="email"><?php _e('Email', 'qa-plugin'); ?></label></div>
          <div class="userinput"><input type="email" class="email" value="" tabindex="1" size="20" name="email" /></div>
          <div class="emailmsg"><?php _e('If you provide an Email you will receive a message, once your Question is Answered.', 'qa-plugin'); ?></div>
      </div>
  </div>
  <?php $output = ob_get_contents();
  ob_end_clean();
  return $output;
}

function qa_output_normal() {
  global $wp_query;
    if ( get_query_var('paged') ) {
      $paged = get_query_var('paged');
    } else if ( get_query_var('page') ) {
      $paged = get_query_var('page');
    } else {
      $paged = 1;
    }
    $args = array(
      'post_type' => 'qa',
      'post_status' => 'publish',
      'paged' => $paged,
      'orderby' => 'date',
      'posts_per_page' => get_option( 'qa_setting_number_qa', 5 )
    );
    query_posts($args); ?>
    <div>
    <ul class='akkordeon'>
    <?php while ( have_posts() ) : the_post();
      $allterms = get_the_terms(get_the_ID(), "qatag");
        if(!empty($allterms))
        {
          $i = 0;
          foreach($allterms as $term)
          {
            $qa_tags = $term->name;
            $i++;
            if($i != count($allterms))
            {
              echo " ";
            }
          }
        }
        ?>
      <li class="<?php echo $qa_tags; ?>" style="border-bottom:1px solid #fff;">
        <p>
          <?php
            $customs = get_post_custom(get_the_ID());
            $username = ($customs['qa_username'][0]);
            
            if ($username) {
              echo $username; 
            } else {
              echo __('Anonymous', 'qa-plugin');
            }
          ?>
        <span class="date">
          <?php
          $allterms = get_the_terms(get_the_ID(), "qatag");

          if(!empty($allterms))
          {
            $i = 0;
            foreach($allterms as $term)
            {
              echo "<strong>" . $term->name . "</strong>";
              $i++;
              if($i != count($allterms))
              {
                echo ", ";
              }
            }
            echo " - ";
          }
          ?>
          <?php the_time('j F Y'); ?>
        </span>
        <br />
        <?php the_title(); ?>
        </p>
        <div>
          <?php 
            $content = get_the_content();
            if (!$content) {
              echo get_option( 'qa_setting_default_answer');
            } else {
              echo $content;
            };
          ?>
        </div>
      </li>
    <?php endwhile; ?>
    </ul>
    <?php qa_pagination($wp_query->max_num_pages); ?>
  </div>
  <?php
  wp_reset_query();
}

function add_qa_columns($qa_columns) {
    $new_columns['cb'] = '<input type="checkbox" />';
    $new_columns['date'] = __('Date', 'qa-plugin');
    $new_columns['title'] = __('Q&A', 'qa-plugin');
    $new_columns['answer'] = __('Answer', 'qa-plugin');
    $new_columns['username'] = __('Username', 'qa-plugin');
    $new_columns['ip'] = __('ip', 'qa-plugin');
    $new_columns['email'] = __('Email', 'qa-plugin');

    return $new_columns;
}
add_filter('manage_edit-qa_columns', 'add_qa_columns');

function manage_qa_columns($column_name, $id) {
  $customs = get_post_custom($id);
  switch ($column_name) {
    case 'id':
      echo $id;
      break;
    case 'username':
      if(isset($customs['qa_username']))
      {
        foreach( $customs['qa_username'] as $key => $value)
        echo $value;
      }
      break;
    case 'ip':
      if(isset($customs['qa_ip']))
      {
        foreach( $customs['qa_ip'] as $key => $value)
        echo $value;
      }
      break;
    case 'email':
      if(isset($customs['qa_email']))
      {
        foreach( $customs['qa_email'] as $key => $value)
        echo $value;
      }
      break;
    case 'answer':
      echo get_the_content($id);
      break;
    default:
      break;
  }
}
add_action('manage_qa_posts_custom_column', 'manage_qa_columns', 10, 2);

function qa_box_init() { 
  $qa_box_name = __('Name and Email', 'qa-plugin');
  add_meta_box('metatest', $qa_box_name, 'qa_showup', 'qa', 'side', 'core');
}
add_action('add_meta_boxes', 'qa_box_init'); 

function qa_showup($post, $box) {
  $username = get_post_meta($post->ID, 'qa_username', true); 
  $ip = get_post_meta($post->ID, 'qa_ip', true); 
  $email = get_post_meta($post->ID, 'qa_email', true);
  echo '<p>' .  __('Username: ', 'qa-plugin') . '<input type="text" id="qa_username" name="qa_username" value="' . esc_attr($username) . '"/></p>';
  echo '<p>' .  __('IP: ', 'qa-plugin') . '<strong>' . esc_attr($ip) . '</strong></p>';
  echo '<p>' .  __('E-mail: ', 'qa-plugin') . '<input type="text" id="qa_email" name="qa_email" value="' . esc_attr($email) . '"/></p>';
}

function qa_save($postID) { 
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return; 
  if (wp_is_post_revision($postID)) return; 

  if (isset($_POST['qa_username'])) {
    $username = sanitize_text_field($_POST['qa_username']);
    update_post_meta($postID, 'qa_username', $username);
  }
  if (isset($_POST['qa_ip'])) {
    $username = sanitize_text_field($_POST['qa_ip']);
    update_post_meta($postID, 'qa_ip', $username);
  }
  if (isset($_POST['qa_email'])) {
    $email = sanitize_text_field($_POST['qa_email']); 
    update_post_meta($postID, 'qa_email', $email); 
  }
}
add_action('save_post', 'qa_save');

function qa_pagination($pages = '', $range = 5) {
  $showitems = ($range * 2) + 1;
  global $paged;
  if (empty($paged))
    $paged = 1;
  if ($pages == '') {
    global $wp_query;
    $pages = $wp_query->max_num_pages;
    if (!$pages) {
      $pages = 1;
    }
  }
  if (1 != $pages) {
    echo '<ul class="qa_pagination">';
    echo '<li><a href="' . get_pagenum_link(1) . '" title="'.__('First','framework').'">«</a></li>';
    for ($i = 1; $i <= $pages; $i++) {
      if (1 != $pages && (!($i >= $paged + $range + 3 || $i <= $paged - $range - 3) || $pages <= $showitems )) {
        echo ($paged == $i) ? "<li class=\"active\"><span>" . $i . "</span></li>" : "<li><a href='" . get_pagenum_link($i) . "' class=\"\">" . $i . "</a></li>";
      }
    }
    echo '<li><a href="' . get_pagenum_link($pages) . '" title="'.__('Last','framework').'">»</a></li>';
    echo '</ul>';
  }
}

function qa_stats() {
?>
  <h4><?php _e('Q&A - Overview', 'qa-plugin'); ?></h4>
  <br />
  <ul>
    <li class="post-count">
      <?php
      $type = 'qa';
      $args = array(
        'post_type' => $type,
        'post_status' => 'publish',
        'posts_per_page' => -1);
      $my_query = query_posts( $args );
      ?>
      <a href="edit.php?post_type=qa&post_status=publish"><?php echo count($my_query); ?> <?php _e('published', 'qa-plugin'); ?></a>
    </li>
    <li class="page-count">
      <?php
      $args = array(
        'post_type' => $type,
        'post_status' => 'draft',
        'posts_per_page' => -1);
      $my_query = query_posts( $args );
      ?>
      <a href="edit.php?post_type=qa&post_status=draft"><?php echo count($my_query); ?> <?php _e('open', 'qa-plugin'); ?></a>

      </li>
  </ul>
<?php
  wp_reset_query();
}
add_action('activity_box_end', 'qa_stats');

function qa_settings_init() {

  add_settings_field(
    'qa_setting_email',
    __('E-Mail Alert on new Q&A', 'qa-plugin'),
    'qa_setting_callback',
    'reading',
    'qa_setting_section_menu'
  );
  register_setting( 'reading', 'qa_setting_email' );

  add_settings_field(
    'qa_setting_default_email',
    __('Default e-mail', 'qa-plugin'),
    'qa_setting_default_email',
    'reading',
    'qa_setting_section_menu'
  );
  register_setting( 'reading', 'qa_setting_default_email' );

  add_settings_field(
    'qa_setting_default_answer',
    __('Default answer', 'qa-plugin'),
    'qa_setting_default_answer',
    'reading',
    'qa_setting_section_menu'
  );
  register_setting( 'reading', 'qa_setting_default_answer' );

  add_settings_field(
    'qa_setting_user_response',
    __('User Fields', 'qa-plugin'),
    'qa_setting_user_response_callback',
    'reading',
    'qa_setting_section_menu'
  );
  register_setting( 'reading', 'qa_setting_user_response' );

  add_settings_field(
      'qa_setting_captcha',
      __('Show Captcha', 'qa-plugin'),
      'qa_captcha_callback',
      'reading',
      'qa_setting_section_menu'
  );
  register_setting( 'reading', 'qa_setting_captcha' );

  add_settings_field(
      'qa_setting_captcha_publickey',
      __('Captcha Public Key', 'qa-plugin'),
      'qa_captcha_puk_callback',
      'reading',
      'qa_setting_section_menu'
  );
  register_setting( 'reading', 'qa_setting_captcha_publickey' );

  add_settings_field(
    'qa_setting_captcha_privatekey',
    __('Captcha Private Key', 'qa-plugin'),
    'qa_captcha_prk_callback',
    'reading',
    'qa_setting_section_menu'
  );
  register_setting( 'reading', 'qa_setting_captcha_privatekey' );

  add_settings_field(
    'qa_setting_number_qa',
    __('Number of Q&A', 'qa-plugin'),
    'qa_number_callback',
    'reading',
    'qa_setting_section_menu'
  );
  register_setting( 'reading', 'qa_setting_number_qa' );

  add_settings_field(
    'qa_setting_background_open',
    __('Color background open Q&A', 'qa-plugin'),
    'qa_number_background_open',
    'reading',
    'qa_setting_section_menu'
  );
  register_setting( 'reading', 'qa_setting_background_open' );

  add_settings_field(
    'qa_setting_background_close',
    __('Color background close Q&A', 'qa-plugin'),
    'qa_number_background_close',
    'reading',
    'qa_setting_section_menu'
  );
  register_setting( 'reading', 'qa_setting_background_close' );
}
add_action( 'admin_init', 'qa_settings_init' );

function qa_setting_section_menu() {
  add_options_page(__('Q&A Settings', 'qa-plugin'), __('Q&A Settings', 'qa-plugin'), 'manage_options', 'qa-plugin', 'qa_plugin_page');
}
add_action('admin_menu', 'qa_setting_section_menu');

function qa_plugin_page(){
  wp_enqueue_script('wp-color-picker');
  wp_enqueue_style( 'wp-color-picker');
  echo '<div class="wrap">';
  echo "<h2>" . __('Setting for Q&A plugin', 'wp-ya-addurl') . "</h2>";
  echo "<h3>" . __('Configure your Q&A', 'qa-plugin') . "</h3>";
  
  echo '<form action="options.php" method="post">';
  wp_nonce_field('update-options');
  echo '<table class="form-table">
  <tr valign="top">
  <th scope="row">' . __('E-Mail Alert on new Q&A', 'qa-plugin') . '</th>
  <td>';
  echo '<input name="qa_setting_email" id="qa_setting_email" type="checkbox" value="1" class="code" ' . checked( 1, get_option( 'qa_setting_email' ), false ) . ' />';
  echo '</td>
  </tr>
  <tr valign="top">
  <th scope="row">' . __('Default e-mail', 'qa-plugin') . '</th>
  <td>';
  echo '<input name="qa_setting_default_email" id="qa_setting_default_email" size="80" type="text" class="code" value="' . get_option( 'qa_setting_default_email') . '" />
        <p class="description">' . __('Enter the default e-mail. If not entered, it is sent to the administrator.', 'qa-plugin') . "</p>";
  echo '</td>
  </tr>
  <tr valign="top">
  <th scope="row">' . __('Default answer', 'qa-plugin') . '</th>
  <td>';
  echo '<input name="qa_setting_default_answer" id="qa_setting_default_answer" size="80" type="text" class="code" value="' . get_option( 'qa_setting_default_answer') . '" />
        <p class="description">' . __('Enter the default answer. It shows if the question is published unanswered.', 'qa-plugin') . "</p>";
  echo '</td>
  </tr>
  <tr valign="top">
  <th scope="row">' . __('User Fields', 'qa-plugin') . '</th>
  <td>';
  echo '<input name="qa_setting_user_response" id="qa_setting_user_response" type="checkbox" value="1" class="code" ' . checked( 1, get_option( 'qa_setting_user_response' ), false ) . ' /> ' . __('Would you like to display User Fields, like E-Mail and Username? This would give your users the possibility to receive a Notification if an Answer is answered.', 'qa-plugin');
  echo '</td>
  </tr>
  <tr valign="top">
  <th scope="row">' . __('Show Captcha', 'qa-plugin') . '</th>
  <td>';
  echo '<input name="qa_setting_captcha" type="checkbox" value="1" class="code" ' . checked( 1, get_option( 'qa_setting_captcha' ), false ) . ' />';
  echo '</td>
  </tr>
  <tr valign="top">
  <th scope="row">' . __('Captcha Private Key', 'qa-plugin') . '</th>
  <td>';
  echo '<input name="qa_setting_captcha_privatekey" id="qa_setting_captcha_privatekey" type="text" class="code" value="' . get_option( 'qa_setting_captcha_privatekey' ) . '" />
        <p class="description">' . __('Get a key from <a href="https://www.google.com/recaptcha/admin/create" target="_blank">https://www.google.com/recaptcha/admin/create</a>', 'qa-plugin') . "</p>";
  echo '</td>
  </tr>
  <tr valign="top">
  <th scope="row">' . __('Captcha Public Key', 'qa-plugin') . '</th>
  <td>';
  echo '<input name="qa_setting_captcha_publickey" id="qa_setting_captcha_publickey" type="text" class="code" value="' . get_option( 'qa_setting_captcha_publickey' ) . '" />
        <p class="description">' . __('Get a key from <a href="https://www.google.com/recaptcha/admin/create" target="_blank">https://www.google.com/recaptcha/admin/create</a>', 'qa-plugin') . "</p>";
  echo '</td>
  </tr>
  <tr valign="top">
  <th scope="row">' . __('Number of Q&A', 'qa-plugin') . '</th>
  <td>';
  echo '<input name="qa_setting_number_qa" id="qa_setting_number_qa" type="text" class="code" value="' . get_option( 'qa_setting_number_qa', 5 ) . '" />
        <p class="description">' . __('How much questions you want to display on one page.', 'qa-plugin') . "</p>";
  echo '</td>
  </tr>
  <tr valign="top">
  <th scope="row">' . __('Color background open Q&A', 'qa-plugin') . '</th>
  <td>';
  echo '<input name="qa_setting_background_open" id="qa_setting_background_open" type="text" value="#369" data-default-color="#369"/>
        <p class="description">' . __('Set color background open tab Q&A.', 'qa-plugin') . "</p>";
  echo '</td>
  </tr>
  <tr valign="top">
  <th scope="row">' . __('Color background close Q&A', 'qa-plugin') . '</th>
  <td>';
  echo '<input name="qa_setting_background_close" id="qa_setting_background_close" type="text" value="#333" data-default-color="#333"/>
        <p class="description">' . __('Set color background close tab Q&A.', 'qa-plugin') . "</p>";
  echo '</td>
  </tr>
  </table>
  </div>
    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="page_options" value="qa_setting_default_answer,qa_setting_background_close,qa_setting_background_open,qa_setting_number_qa,qa_setting_captcha_publickey,qa_setting_captcha_privatekey,qa_setting_user_response,qa_setting_captcha,qa_setting_default_email,qa_setting_email" />';
echo '<p class="submit"><input type="submit" class="button-primary" value="' . __('Save setting', 'wp-ya-addurl') .'"></p>
  </form>';
  ?>
  <script type="text/javascript">
  jQuery(document).ready(function($) {   
    $('#qa_setting_background_open').wpColorPicker();
    $('#qa_setting_background_close').wpColorPicker();
  });
  </script>
  <?php
}

function qa_menu_bubble() {
  global $menu;
  foreach ( $menu as $key => $value ) {
    if ( $menu[$key][2] == 'edit.php?post_type=qa' ) {
      $type = 'qa';
      $args = array(
        'post_type' => $type,
        'post_status' => 'draft',
        'posts_per_page' => -1);
      $my_query = query_posts( $args );
      if(count($my_query) > 0)
      {
        $menu[$key][0] .= '    <span class="update-plugins"><span class="plugin-count">' . count($my_query) . '</span></span> ';
      }
      wp_reset_query();
      return;
    }
  }
}
add_action( 'admin_menu', 'qa_menu_bubble' );

function publish_qa_hook($id)
{
  $customs = get_post_custom($id);
  if(isset($customs['qa_email']))
    wp_mail( $customs['qa_email'],  get_bloginfo('name').__(' - Q&A - Answer Received', 'qa-plugin'), __('Your Q&A has been Answered!', 'qa-plugin'));
}
add_action( 'publish_qa', 'publish_qa_hook' );

function qa_css_hook( ) {
  $background_open = get_option( 'qa_setting_background_open');
  $background_close = get_option( 'qa_setting_background_close');
?>
  <style type='text/css'>
    ul.akkordeon li > p {background: <?php echo $background_close; ?>;}
    ul.akkordeon li > p:hover {background: <?php echo $background_close; ?>;}
    ul.akkordeon li > p.active {background: <?php echo $background_open; ?>;}
  </style>
<?php
}
add_action( 'wp_head', 'qa_css_hook' );
?>