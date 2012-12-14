<?php
  /*
Plugin Name: Thin Out Revisions
Plugin URI: http://en.hetarena.com/thin-out-revisions
Description: A plugin to thin out post/page revisions manually. 
Version: 1.1
Author: Hirokazu Matsui
Author URI: http://en.hetarena.com/
License: GPLv2
  */

define( 'HM_TOR_VERSION', '1.1' );



class HM_TOR_Plugin_Loader {
  function __construct() {
    register_activation_hook( __FILE__,   array( &$this, 'hm_tor_install' ) );
    add_action( 'init',                   array( &$this, 'init' ) );
    add_action( 'plugins_loaded',         array( &$this, 'hm_tor_loaded' ) );
    add_action( 'admin_head',             array( &$this, 'hm_tor_message' ), 20 );
    add_action( 'wp_enqueue_scripts',     array( &$this, 'hm_tor_scripts' ), 20);
    add_action( 'wp_ajax_hm_tor_do_ajax', array( &$this, 'hm_tor_do_ajax' ) );
    add_action( 'post_updated',           array( &$this, 'delete_revisions_on_1st_publishment' ), 20, 3 );

    add_action( 'admin_init',             array( &$this, 'admin_init' ) );
    add_action( 'admin_menu',             array( &$this, 'admin_menu' ) );
  }

  function init() {
    // replare the default 'pre_post_update' handler
    remove_action( 'pre_post_update', 'wp_save_post_revision' );
    add_action( 'pre_post_update', array( &$this, 'save_post_revision' ) );
  }

  function hm_tor_loaded() {
    load_plugin_textdomain( 'thin-out-revisions', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
  }

  function hm_tor_install() {
    if ( version_compare( get_bloginfo( 'version' ), '3.2', '<' ) ) {
      deactivate_plugins( basename( __FILE__ ) ); // Deactivate this plugin
    }
  }

  function hm_tor_scripts() {
    wp_enqueue_script( 'jquery' );
  }

  function hm_tor_message() {

    global $left, $right, $post;

    $uri = parse_url( $_SERVER['REQUEST_URI'] );  

    if ( strpos( $uri['path'], '/revision.php' ) === false ) {
      return;
    }

    if ( !$revisions = wp_get_post_revisions( $post->ID ) )
      return;

    if ( !( $left != '' && $right != '' && $left != $right ) )
      return;

    array_unshift( $revisions, $post );

    $revs = array();
    $liststr = "<ul>";
    $in_range = false;
    $post_notselected = false;
    $posts2del = array();
    foreach ( $revisions as $revision ) {
      array_push( $revs, "'" . $revision->ID . "'" );
      if ( $revision->ID == $right ) {
        $in_range = true;
      }
      else if ( $revision->ID == $left ) {
        $in_range = false;
      }
      else if ( $in_range ) {
        $post_notselected = true;
        if ( current_user_can( 'edit_post', $revision->ID ) ) {
          $liststr .= '<li>&quot;' . wp_post_revision_title( $revision, false ) . "&quot;</li>";
          array_push( $posts2del, $revision->ID );
        }
      }
    }
    $revs_str = "[" . implode( $revs, "," ) ."]";
    $liststr .= "</ul>";

    if ( count( $posts2del ) == 0 ) {
      if ( !$post_notselected && current_user_can( 'edit_post', $left ) ) {
        $buttonval  = sprintf( __( "Remove a revision &quot;%s&quot;", 'thin-out-revisions' ), wp_post_revision_title( $left, false ) );
        $posts_data = $left;
        $msg_reload = sprintf( __( "Once you return to <a href=\"%s\">the edit page</a>, you could continue working with revisions. Or reselect existing revisions and press &quot;%s&quot; button.", 'thin-out-revisions' ), get_edit_post_link( $post->ID ), __( "Compare Revisions" ) );
        $liststr = '';
      }
      else {
        return;
      }
    }
    else {
      $buttonval = sprintf( __( "Remove revisions between &quot;%s&quot; and &quot;%s&quot; exclusively", 'thin-out-revisions' ),
        wp_post_revision_title( $right, false ), wp_post_revision_title( $left, false ) );
      $posts_data = implode( '-', $posts2del);
      $msg_reload = __( "To update the compare-revision table, reload this page.", 'thin-out-revisions' );
    }
    $ajaxnonce = wp_create_nonce( 'hm-rto-delete-' . $posts_data );  
    $ajaxurl = admin_url( 'admin-ajax.php', isset( $_SERVER["HTTPS"] ) ? 'https' : 'http' );

    $msg_confirm = __( "You really remove?", 'thin-out-revisions' );
    $msg_process = __( 'Processing ...', 'thin-out-revisions' );
    $msg_info    = count( $posts2del ) > 0 ? __( 'Following revisions will be removed.', 'thin-out-revisions' ) : "";
    $msg_info2   = sprintf( __( "To change revisions to remove, you have to press &quot;%s&quot; button after selection.",'thin-out-revisions' ), __( 'Compare Revisions' ) );
    $msg_error   = __( 'Error in communication with server', 'thin-out-revisions' );
    $msg_title   = __( 'Thin Out Revisions', 'thin-out-revisions' );

    $src = <<<JQSRC
<script type="text/javascript">
  jQuery(document).ready(function() {
    jQuery('#wpbody-content .wrap').append(
      '<h3>$msg_title</h3>'
      + "<form><input type='button' id='mh_rto_ajax' class='button-secondary' value='$buttonval' /></form>"
      + '<div id="hm_tor_msg" style="margin: 0.5em 0; padding: 0.5em 1em;">$msg_info'
      + '$liststr $msg_info2'
      + '</div>'
    );

    jQuery('#mh_rto_ajax').click(function() {
      if (confirm('$msg_confirm') != true) {
        return;
      }
      jQuery('#hm_tor_msg').html('$msg_process');
      jQuery.ajax({
        url: '$ajaxurl',
        dataType: 'json',
        data: {
          action: 'hm_tor_do_ajax',
          posts: '$posts_data',
          security: '$ajaxnonce'
        }
      })
      .success (function(response) {
        jQuery('#hm_tor_msg').html(response.msg + ' $msg_reload').addClass('updated');
        var revs = $revs_str;
        for (var i = 0; i < revs.length; i++) {
          for (var j = 0; j < response.deleted.length; j++) {
            if (revs[i] == response.deleted[j]) {
              jQuery('.post-revisions tr:eq(' + (i+1) + ')').css('text-decoration', 'line-through');
              jQuery('.post-revisions tr:eq(' + (i+1) + ') :radio').attr('disabled', 'disabled');
            }
          }
        }
      })
      .error (function() {
        jQuery('#hm_tor_msg').html('$msg_error').addClass('error');
      });
    });

  });
</script>

JQSRC;
    echo $src;
  }

  function hm_tor_do_ajax() {
  
    $posts  = explode( "-", $_REQUEST['posts'] );

    if ( check_ajax_referer( 'hm-rto-delete-' . $_REQUEST['posts'], 'security', false ) ) {
      $deleted = array();
      foreach ( $posts as $revid ) {
        // Without the 'get_post' check, WP makes warnings.
        if ( get_post($revid) && current_user_can( 'edit_post', $revid ) ) {
          if ( wp_delete_post_revision( $revid ) ) {
            array_push( $deleted, $revid );
          }
        }
      }
      echo json_encode( array(
        "result" => "success", 
        "msg" => sprintf( _n( '%s revision removed.', '%s revisions removed.', count( $deleted ), 'thin-out-revisions' ), count( $deleted ) ),
        "deleted" => $deleted
      ));
    }
    else {
      echo json_encode( array(
       "result" => "error",
       "msg" => __( "Wrong session. Unable to process.", 'thin-out-revisions' )
      ));
    }

    die();
  }

  function save_post_revision( $post ) {
    // do not make a revision while bulk editing nor quick editing.
    if ( isset( $_REQUEST['bulk_edit'] ) && $this->get_hm_tor_option( 'bulk_edit' ) == 'on' ) {
      return;
    }

    if ( isset( $_REQUEST['_inline_edit'] ) && $this->get_hm_tor_option( 'quick_edit' ) == 'on' ) {
      return;
    }

    // call the default handler
    wp_save_post_revision( $post );
  }

  function delete_revisions_on_1st_publishment($post_id, $post, $post_before) {
    if ( $this->get_hm_tor_option( 'del_on_publish' ) == 'on' &&
         $post->post_status == 'publish' && 
         get_post_meta( $post_id, '_hm_tor_status', true ) != 'published' ) {

      // do nothing if previous status is 'pending'
      if ( $post_before->post_status == 'draft' ) {
        $revisions = wp_get_post_revisions( $post_id );
        foreach ( $revisions as $rev ) {
          if ( false === strpos( $rev->post_name, 'autosave' ) ) {
            wp_delete_post_revision( $rev->ID );
          }
        }
      }

      add_post_meta( $post_id, '_hm_tor_status', 'published', true );
    }
  }

  function admin_init() {
    add_settings_section( 'hm_tor_main', 'Thin Out Revisions', array( &$this, 'main_section_text'), 'hm_tor_option_page' );

    add_settings_field( 'hm_tor_quick_edit', __('Disable revisioning while quick editing', 'thin-out-revisions'), 
      array( &$this, 'settings_field_quick_edit' ), 'hm_tor_option_page', 'hm_tor_main' );
    add_settings_field( 'hm_tor_bulk_edit', __('Disable revisioning while bulk editing', 'thin-out-revisions'), 
      array( &$this, 'settings_field_bulk_edit' ), 'hm_tor_option_page', 'hm_tor_main' );
    add_settings_field( 'hm_tor_del_on_publish', __('Delete all revisions on initial publication', 'thin-out-revisions'),
      array( &$this, 'settings_field_del_on_publish' ), 'hm_tor_option_page', 'hm_tor_main' );

    register_setting( 'hm_tor_option_group', 'hm_tor_options', array( &$this, 'validate_options' ) );
  }

  function get_hm_tor_option($key = NULL) {
    $default_hm_tor_option = array(
      'version'        => '1.1',
      'quick_edit'     => "off",
      'bulk_edit'      => "off",
      'del_on_publish' => "off",
    );

    // The get_option doesn't seem to merge retrieved values and default values.
    $options = get_option( 'hm_tor_options', $default_hm_tor_option );
    return $key ? $options[$key] : $options;
  }

  function main_section_text() {
    // do nothing
  }

  function settings_field($key, $text) {
    $val = $this->get_hm_tor_option($key);
    echo "<fieldset><legend class='screen-reader-text'><span>" . $text . "</span></legend>\n";
    echo "<label title='enable'><input type='radio' name='hm_tor_options[" . $key . "]' value='on' " . 
      ($val == "on" ? "checked='checked'" : "") .
      "/><span>On</span></label><br />\n";
    echo "<label title='disable'><input type='radio' name='hm_tor_options[" . $key . "]' value='off' " .
      ($val == "off" ? "checked='checked'" : "") .
      "/><span>Off</span></label><br />\n";
    echo "</fieldset>\n";
  }

  function settings_field_quick_edit() {
    $this->settings_field('quick_edit', __('Disable revisioning while quick editing', 'thin-out-revisions'));
  }

  function settings_field_bulk_edit() {
    $this->settings_field('bulk_edit', __('Disable revisioning while bulk editing', 'thin-out-revisions'));
  }

  function settings_field_del_on_publish() {
    $this->settings_field('del_on_publish', __('Delete all revisions on initial publication', 'thin-out-revisions'));
  }

  function validate_options( $input ) {
    $valid = array();

    $valid['quick_edit']     = ($input['quick_edit']     == "on" ? "on" : "off");
    $valid['bulk_edit']      = ($input['bulk_edit']      == "on" ? "on" : "off");
    $valid['del_on_publish'] = ($input['del_on_publish'] == "on" ? "on" : "off");

    return $valid;
  }

  function admin_menu() {
    add_options_page('Thin Out Revisions', 'Thin Out Revisions', 'manage_options', 
	'hm_tor_option_page', array( &$this, 'admin_page') );
  }

  function admin_page() {
?>
  <div class="wrap">
  <?php screen_icon(); ?>
  <h2>Thin Out Revisions</h2>
  <form action="options.php" method="post">
  <?php settings_fields('hm_tor_option_group'); ?>
  <?php do_settings_sections('hm_tor_option_page'); ?>
  <p class="submit"><input class="button-primary" name="Submit" type="submit" value="<?php echo __('Save Changes'); ?>" /></p>
  </form>
  </div>
<?php
  }

} // end of class HM_TOR_Plugin_Loader

$hm_tor_plugin_loader = new HM_TOR_Plugin_Loader();

