<?php
/*
 * @package Brevz - WP
 * @version 0.2
 * Plugin Name: Brevz - Keep your users in touch with Notifications and Changelogs.
 * Plugin URI: https://wordpress.org/plugins/brevz/
 * Description: With Brevz, keep your users connected effortlessly! You'll be able to notify and engage your visitors with a powerful solution and measure your visitors' engagement with easy analytics tools. We are RGPD compliant and we are running an eco-designed solution within a eco-designed datacenter in Paris.
 * Author: Brevz SAS
 * Author URI: https://brevz.io
 * Version: 0.2
 * Text Domain: brevz
 */

// Initialize variables for Brevz.
add_action('admin_init', 'brevz_init');

function brevz_init()
{
  register_setting('brevz', 'project_id');
}


// Warn is no projectID
add_action('admin_notices', 'brevz_warn');

function brevz_warn()
{
  if (is_admin()) {
    $brevz_project_id = get_option('project_id');

    if (!$brevz_project_id) {
      echo '<div><p><strong>Brevz:</strong> Project ID is required for Brevz to work!</p></div>';
    }
  }
}

// Add Core bundle to the head.
add_action('wp_head', 'brevz_js');
function brevz_js()
{
  $project_id = get_option('project_id');
  $path = strstr(plugin_dir_url(__FILE__), '/wp-content');
  if ($project_id) {
    wp_enqueue_script('brevz-core-bundle', 'https://bundle.static.brevz.io/bundle.min.js', array(), null, false);
    $upload_dir = wp_upload_dir();
    $project_dirname = $upload_dir['baseurl'].'/brevz';
    wp_add_inline_script('brevz-core-bundle', 'var brevzConfig = ' . json_encode( array(
      'projectId' => $project_id,
      'serviceWorker' => array(
        'location' => $path . 'sw.php',
        'manifestLocation' => $project_dirname . '/manifest.json',
      ),
    )), 'before');
      if (is_singular('post')) {
        wp_enqueue_script('brevz-wp-integration', 'https://plugins.static.brevz.io/wordpress.min.js', array(), null, false);
      }
  }
}

add_action('admin_menu', 'brevz_register_menu_page');
function brevz_register_menu_page()
{
  add_menu_page('Brevz - https://brevz.io', 'Brevz', 'manage_options', 'brevz-main-menu', 'brevz_general_settings', 'https://brevz.io/favicon-16x16.png');
}


/** Utility */
function brevz_sanitize($str)
{
  $filtered = wp_check_invalid_utf8($str);
  $filtered = trim(preg_replace('/[\r\n\t ]+/', ' ', $filtered));

  $found = false;
  while (preg_match('/%[a-f0-9]{2}/i', $filtered, $match)) {
    $filtered = str_replace($match[0], '', $filtered);
    $found = true;
  }

  if ($found) {
    // Strip out the whitespace that may now exist after removing the octets.
    $filtered = trim(preg_replace('/ +/', ' ', $filtered));
  }

  return $filtered;
}

/**
 * Menu page
 */
function brevz_general_settings()
{
  echo "<h2>Brevz</h2>";
  echo "<h6>Version: 0.2</h6>";
  ?>

  <?php
  if (isset($_POST['brevz-save'])) {
    if (!isset($_POST['brevz-nonce']) || (!wp_verify_nonce($_POST['brevz-nonce'], plugin_basename(__FILE__)))) {
      echo '<div class="error"><p>Something went wrong!</p></div>';
    } else {
      $project_id = brevz_sanitize(filter_input(INPUT_POST, 'project_id'));

      $base_url = dirname(__FILE__);
      $data = '{
      "gcm_sender_id": "595807243360",
      "display": "standalone",
      "start_url": "/"
}';

      $current_user = wp_get_current_user();
      $upload_dir   = wp_upload_dir();
 
      if (!empty( $upload_dir['basedir'] )) {
        $project_dirname = $upload_dir['basedir'].'/brevz';
        if ( !file_exists( $project_dirname ) ) {
          wp_mkdir_p( $project_dirname );
          echo '<div class="updated"><p>ProjectFileCreated</p></div>';
        }
      }

      $project_dirname = $upload_dir['basedir'].'/brevz';
      file_put_contents($upload_dir . "manifest.json", $data);

      update_option('project_id', $project_id);

      echo '<div class="updated"><p>Changes saved successfully!</p></div>';
    }
  }
  ?>
  <p>Configure options for Brevz, you can get your project ID from your Brevz Integration page. If you're not registered, signup for free at <a target="_blank" href="https://brevz.io/">https://brevz.io/</a>.</p>

  <form method="post" action="">
    <?php settings_fields('brevz'); ?>
    <table class="form-table">
      <tr>
        <th scope="row">
          <h3>Configuration</h3>
        </th>
      </tr>
      <tr>
        <th scope="row">Project ID</th>
        <td><input type="text" required name="project_id" size="64" value="<?php echo esc_attr(get_option('project_id')); ?>" placeholder="Project ID" /></td>
      </tr>
    </table>
    <?php
  submit_button('Save Changes', 'primary', 'brevz-save');
    wp_nonce_field(plugin_basename(__FILE__), 'brevz-nonce');
    ?>
  </form>
  <?php

  add_filter('admin_footer_text', 'brevz_footer');
}

function brevz_footer()
{

  echo 'Brevz SAS - SIREN: 898446141 - 4 Rue Voltaire, 44000 Nantes, France - You like <strong>Brevz</strong>? leave us a <a href="https://wordpress.org/support/plugin/brevz-keep-your-users-in-touch-with-notifications-and-changelogs/reviews/#new-post" target="_blank" class="wc-rating-link" data-rated="Thanks :)">rating</a>. Thank you!';
}
