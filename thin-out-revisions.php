<?php
  /*
Plugin Name: Thin Out Revisions
Plugin URI: http://en.hetarena.com/thin-out-revisions
Description: A plugin to thin out post/page revisions manually. 
Version: 1.0
Author: Hirokazu Matsui
Author URI: http://en.hetarena.com/
License: GPLv2
  */

define( 'HM_TOR_VERSION', '1.0' );



class HM_TOR_Plugin_Loader {
  function __construct() {
    register_activation_hook( __FILE__,   array( &$this, 'hm_tor_install' ) );
    add_action( 'plugins_loaded',         array( &$this, 'hm_tor_loaded' ) );
    add_action( 'admin_head',             array( &$this, 'hm_tor_message' ), 20 );
    add_action( 'wp_enqueue_scripts',     array( &$this, 'hm_tor_scripts' ), 20);
    add_action( 'wp_ajax_hm_tor_do_ajax', array( &$this, 'hm_tor_do_ajax' ) );
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
        $msg_reload = sprintf( __( "Once you return <a href=\"%s\">post-edit page</a>, you could continue working with revisions. Or reselect existing revisions and press &quot;%s&quot; button.", 'thin-out-revisions' ), get_edit_post_link( $post->ID ), __( "Compare Revisions" ) );
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
      + '<div id="hm_tor_msg" style="margin: 1em 0; padding: 0 1em;">$msg_info'
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
        jQuery('#hm_tor_msg').html(response.msg + ' $msg_reload');
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
        jQuery('#hm_tor_msg').html('$msg_error');
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
  
} // end of class HM_TOR_Plugin_Loader

$hm_tor_plugin_loader = new HM_TOR_Plugin_Loader();

