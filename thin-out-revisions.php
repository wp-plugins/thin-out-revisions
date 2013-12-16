<?php
/*
Plugin Name: Thin Out Revisions
Plugin URI: http://en.hetarena.com/thin-out-revisions
Description: A plugin to thin out post/page revisions manually.
Version: 1.5
Author: Hirokazu Matsui (blogger323)
Author URI: http://en.hetarena.com/
License: GPLv2
*/


class HM_TOR_Plugin_Loader {
	const VERSION        = '1.5';
	const OPTION_VERSION = '1.4';
	const OPTION_KEY     = 'hm_tor_options';
	const I18N_DOMAIN    = 'thin-out-revisions';
	const PREFIX         = 'hm_tor_';

	public $page = ''; // 'revision.php' or 'post.php'

	function __construct() {
		register_activation_hook( __FILE__, array( &$this, 'plugin_activation' ) );
		register_deactivation_hook( __FILE__, array( &$this, 'plugin_deactivation' ) );
		add_action( 'init',                   array( &$this, 'init' ) );
		add_action( 'plugins_loaded',         array( &$this, 'plugins_loaded' ) );
		add_action( 'admin_enqueue_scripts',  array( &$this, 'admin_enqueue_scripts' ), 20 );
		add_action( 'wp_ajax_hm_tor_do_ajax', array( &$this, 'hm_tor_do_ajax' ) );
		add_action( 'wp_ajax_hm_tor_do_ajax_start_delete_old_revisions', array( &$this, 'do_ajax_start_delete_old_revisions' ) );
		add_action( 'post_updated',           array( &$this, 'post_updated' ), 20, 3 );
		add_action( 'transition_post_status', array( &$this, 'transition_post_status' ), 10, 3 );

		add_action( 'hm_tor_cron_hook', array( &$this, 'cron_hook' ) );

		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		add_action( 'admin_head', array( &$this, 'admin_head' ), 20 );

	}

	function init() {
		$uri = parse_url( $_SERVER['REQUEST_URI'] );

		if ( strpos( $uri['path'], '/revision.php' ) ) {
			$this->page = 'revision.php';
		}
		else if ( strpos( $uri['path'], '/post.php' ) ) {
			add_action( 'admin_notices', array( &$this, 'admin_notices' ) );
			$this->page = 'post.php';
		}
	}

	function plugins_loaded() {
		load_plugin_textdomain( self::I18N_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public static function plugin_activation( $network_wide ) {
		if ( version_compare( get_bloginfo( 'version' ), '3.2', '<' ) ) {
			deactivate_plugins( basename( __FILE__ ) ); // Deactivate this plugin
			return;
		}

		$option = self::get_hm_tor_option();
		if ( $option['schedule_enabled'] == 'enabled' && preg_match( '/^([0-9]{1,2}):([0-9]{2})$/', $option['del_at'], $matches )
				&& filter_var( $option['del_older_than'], FILTER_VALIDATE_INT ) !== FALSE ) {
			wp_schedule_event( self::get_timestamp_for_cron( $matches[1], $matches[2] ), 'daily', 'hm_tor_cron_hook', array( intval( $option['del_older_than'] ) ) );
		}
	}

	public static function plugin_deactivation() {
		$prev =self::get_hm_tor_option();
		$timestamp = wp_next_scheduled( 'hm_tor_cron_hook',  array( intval( $prev['del_older_than'] ) ) );
		if ( $timestamp !== false ) {
			wp_unschedule_event( $timestamp, 'hm_tor_cron_hook', array( intval( $prev['del_older_than'] ) ) );
		}

		// TODO
		// change to call wp_clear_scheduled_hook
	}

	function admin_enqueue_scripts() {
		// 'admin_enqueue_scripts'
		//trigger_error('enqueueing script');
		global $post;
		$latest_revision = 0;

		if ( $post && $post->ID ) {
			$revisions = wp_get_post_revisions( $post->ID );

			// COPY FROM CORE
			if ( ! empty( $revisions ) ) {
				// grab the last revision, but not an autosave (from wp_save_post_revision in WP 3.6)
				foreach ( $revisions as $revision ) {
					if ( false !== strpos( $revision->post_name, "{$revision->post_parent}-revision" ) ) {
						$latest_revision = $revision->ID;
						break;
					}
				}
			}
		}


		$params = array(
			'nonce'                    => wp_create_nonce( self::PREFIX . "nonce" ),
			'ajaxurl'                  => admin_url( 'admin-ajax.php', isset( $_SERVER["HTTPS"] ) ? 'https' : 'http' ),
			'latest_revision'          => $latest_revision,

			'msg_thinout_comfirmation' => esc_attr( __( 'Do you really want to remove this?', self::I18N_DOMAIN ) ),
			'msg_remove_completed'     => esc_attr( __( 'The revision(s) removed.', self::I18N_DOMAIN ) ),
			'msg_ajax_error'           => esc_attr( __( 'Error in communication with server', self::I18N_DOMAIN ) ),
			'msg_nothing_to_remove'    => esc_attr( __( 'Nothing to remove.', self::I18N_DOMAIN ) ),
			'msg_thin_out'             => esc_attr( __( 'Remove revisions between two revisions above', self::I18N_DOMAIN ) ),
			'msg_processing'           => esc_attr( __( 'Processing...', self::I18N_DOMAIN ) ),
			'msg_include_from'         => esc_attr( __( "Include the 'From' revision", self::I18N_DOMAIN ) )
		);

		if ( $this->page == 'revision.php' ) {
			// loading in footer
			wp_enqueue_script( 'thin-out-revisions', plugins_url( '/js/thin-out-revisions.js', __FILE__ ), array( 'revisions' ), false, true );
			wp_localize_script( 'thin-out-revisions', self::PREFIX . 'params', $params );
		}
		else if ( $this->page == 'post.php' ) {
			wp_enqueue_script( 'thin-out-revisions-post', plugins_url( '/js/thin-out-revisions-post.js', __FILE__ ), array(), false, true );
			wp_localize_script( 'thin-out-revisions-post', self::PREFIX . 'params', $params );
		}

	}


	function hm_tor_do_ajax() {

		$posts = explode( "-", $_REQUEST['posts'] );

		if ( check_ajax_referer( self::PREFIX . "nonce", 'security', false ) ) {
			$deleted = array();
			foreach ( $posts as $revid ) {
				// Without the 'get_post' check, WP makes warnings.
				$post = get_post( $revid );

				if ( $post ) {
					$post_type = get_post_type( $post->post_parent );
					if ( ( $post_type == 'post' && current_user_can( 'edit_post', $revid ) )
							|| ( $post_type == 'page' && current_user_can( 'edit_page', $revid ) )
					) {
						if ( wp_delete_post_revision( $revid ) ) {
							array_push( $deleted, $revid );
						}
					}
				}
			} // foreach
			echo json_encode( array(
				"result"  => "success",
				"msg"     => sprintf( _n( '%s revision removed.', '%s revisions removed.', count( $deleted ), self::I18N_DOMAIN ), count( $deleted ) ),
				"deleted" => $deleted
			) );
		}
		else {
			echo json_encode( array(
				"result" => "error",
				"msg"    => __( "Wrong session. Unable to process.", self::I18N_DOMAIN )
			) );
		}

		die();
	}

	function has_copy_revision() {
		return true;
	}

	function post_updated( $post_id, $post, $post_before ) {
		// delete_revisions_on_1st_publishment
		if ( $this->get_hm_tor_option( 'del_on_publish' ) == 'on' &&
				$post->post_status == 'publish' &&
				get_post_meta( $post_id, '_hm_tor_status', true ) != 'published'
		) {

			// do nothing if previous status is other than 'draft'
			if ( $post_before->post_status == 'draft' ) {

				$revisions = wp_get_post_revisions( $post_id );

				// don't remove the latest.
				$latest = $this->has_copy_revision();

				if ( ! empty( $revisions ) ) {

					foreach ( $revisions as $rev ) {
						if ( false !== strpos( $rev->post_name, "{$rev->post_parent}-revision" ) ) {
							if ($latest) {
								$latest = false;
							}
							else {
								wp_delete_post_revision( $rev->ID );
							}
						}
					}
				}

			}
		}

		if ( $post->post_status == 'publish' ) {
			add_post_meta( $post_id, '_hm_tor_status', 'published', true );
		}
	}

	function transition_post_status( $new_status, $old_status, $post ) {
		// This function is called before post_updated in wp_insert_post.
		// So I can't mark the _hm_tor_status depending on $new_status.
		// All I can do is to mark it for future update when the status is changed from 'publish' to something.

		if ( $old_status == 'publish' ) {
			add_post_meta( $post->ID, '_hm_tor_status', 'published', true );
		}
	}

	function admin_init() {
		add_settings_section( 'hm_tor_main', 'Thin Out Revisions', array( &$this, 'main_section_text' ), 'hm_tor_option_page' );

		add_settings_field( 'hm_tor_del_on_publish', __( 'Delete all revisions on initial publication', self::I18N_DOMAIN ),
			array( &$this, 'settings_field_del_on_publish' ), 'hm_tor_option_page', 'hm_tor_main' );

		add_settings_field( 'hm_tor_delete_old_revisions', __( 'Delete revisions as old as or older than', self::I18N_DOMAIN ),
		  array( &$this, 'settings_field_delete_old_revisions' ), 'hm_tor_option_page', 'hm_tor_main' );

		register_setting( 'hm_tor_option_group', 'hm_tor_options', array( &$this, 'validate_options' ) );
	}

	function admin_head() {
		// STYLE tag only for 3.6 or later
?>
<style>
.comparing-two-revisions .revisions-controls {
	height: 164px;
}
.comparing-two-revisions.pinned .revisions-controls {
	height: 148px;
}
.comparing-two-revisions .revisions-tooltip {
	bottom: 169px;
}
</style>
<?php

	}

	function admin_notices() {
		global $post;
		$rev = wp_get_post_revisions( $post->ID );
		if ( post_type_supports( $post->post_type, 'revisions' ) && empty( $rev ) ) {
			echo "<div class='updated' style='padding: 0.6em 0.6em'>" .
					__( 'You should press update button without modification to make a copy revision. Or you will lose current content after update.', self::I18N_DOMAIN ) .
					" <a href='" . __("http://wordpress.org/plugins/thin-out-revisions/faq/", self::I18N_DOMAIN) . "' target='_blank'>" .
					__( "See detail...", self::I18N_DOMAIN ) . "</a>" .
					"</div>\n";
		}
	}

	public static function get_hm_tor_option( $key = NULL ) {
		$default_hm_tor_option = array(
			'version'        => self::OPTION_VERSION,
			'quick_edit'     => "off", // deprecated
			'bulk_edit'      => "off", // deprecated
			'del_on_publish' => "off",
			'del_older_than' => "90",
			'schedule_enabled' => 'off',
			'del_at'         => "3:00",
		);

		// The get_option doesn't seem to merge retrieved values and default values.
		$options = array_merge( $default_hm_tor_option, (array) get_option( 'hm_tor_options', array() ) );
		return $key ? $options[$key] : $options;
	}

	function main_section_text() {
		// do nothing
	}

	function settings_field( $key, $text ) {
		$val = $this->get_hm_tor_option( $key );
		echo "<fieldset><legend class='screen-reader-text'><span>" . $text . "</span></legend>\n";
		echo "<label title='enable'><input type='radio' name='hm_tor_options[" . $key . "]' value='on' " .
				( $val == "on" ? "checked='checked'" : "" ) .
				"/><span>On</span></label><br />\n";
		echo "<label title='disable'><input type='radio' name='hm_tor_options[" . $key . "]' value='off' " .
				( $val == "off" ? "checked='checked'" : "" ) .
				"/><span>Off</span></label><br />\n";
		echo "</fieldset>\n";
	}

	function settings_field_del_on_publish() {
		$this->settings_field( 'del_on_publish', __( 'Delete all revisions on initial publication', self::I18N_DOMAIN ) );
	}

	function settings_field_delete_old_revisions() {

		echo "<p><input class='small-text' id='hm_tor_del_older_than' name='hm_tor_options[del_older_than]' type='text' value='" . esc_attr( $this->get_hm_tor_option( 'del_older_than' ) ) . "' /> " . __( 'days', self::I18N_DOMAIN )  . "\n";

		echo '<input id="hm_tor_rm_now_button" class="button button-primary" style="margin: 0 10px 0 50px;" type="submit" value="' . __( 'Remove NOW', self::I18N_DOMAIN )  . '" /><span id="hm_tor_rm_now_msg"></span></p>';

		echo '<fieldset><legend class="screen-reader-text"><span>' . __('Run as scheduled task', self::I18N_DOMAIN) . '</span></legend>' .
	        '<label for="hm_tor_schedule_enabled"><input name="hm_tor_options[schedule_enabled]" type="checkbox" id="hm_tor_schedule_enabled" value="enabled" ' .
				  checked( $this->get_hm_tor_option('schedule_enabled'), 'enabled', false ) . '/> ' .
	        __('Run as daily task. Run every day at', self::I18N_DOMAIN) . "</label>\n";

		echo  " <input class='small-text' id='hm_tor_del_at' name='hm_tor_options[del_at]' type='text' value='" . esc_attr( $this->get_hm_tor_option( 'del_at' ) ) . "' />";

		// for debug
		if ( $this->get_hm_tor_option( 'schedule_enabled' ) == 'enabled' ) {
			$next = wp_next_scheduled("hm_tor_cron_hook", array( intval( $this->get_hm_tor_option( 'del_older_than' ) ) ) );
			$t = time();
			$diff = intval(($next - $t) / 60);
			$msg = sprintf( __( "The task will begin after %d min.", self::I18N_DOMAIN ), $diff );
			echo "<div>" .  $msg . " (gmt_offset = " . get_option( 'gmt_offset' ) . ")</div>";

		}
		echo "</fieldset>\n";
	}

	public static function get_timestamp_for_cron( $hour, $min ) {
		$now = time();
		$t = ceil( $now / 86400 ) * 86400 + ($hour - get_option( 'gmt_offset') )  * 3600 + $min * 60;

		while ( $now < $t - 86400) {
			$t -= 86400;
		}

		while ( $now > $t ) {
			$t += 86400;
		}
		return $t;
	}



	function validate_options( $input ) {
		$valid = array();
		$prev  = $this->get_hm_tor_option();
		$valid_conf_for_cron = true;

		// reset schedule
		$timestamp = wp_next_scheduled( 'hm_tor_cron_hook',  array( intval( $prev['del_older_than'] ) ) );
		if ( $timestamp !== false ) {
			wp_unschedule_event( $timestamp, 'hm_tor_cron_hook', array( intval( $prev['del_older_than'] ) ) );
		}

		if ( filter_var( $input['del_older_than'], FILTER_VALIDATE_INT ) === FALSE ) {
			add_settings_error( 'hm_tor_delete_old_revisions', 'hm-tor-del-older-than-error', __( 'The day has to be an integer.', self::I18N_DOMAIN ) );
			$valid['del_older_than'] = $prev['del_older_than'];
			$valid_conf_for_cron = false;
		}
		else {
			$valid['del_older_than'] = $input['del_older_than'];
		}

		$valid['schedule_enabled'] = 'disabled';
		$valid['del_at'] = $prev['del_at'];
		if ( isset($input['schedule_enabled']) && $input['schedule_enabled'] == 'enabled' ) {
			$hour = $min = 0;
			if ( ! preg_match( '/^([0-9]{1,2}):([0-9]{2})$/', $input['del_at'], $matches ) ) {
				add_settings_error( 'hm_tor_delete_old_revisions', 'hm-tor-del-at-error', __( 'Wrong time format.', self::I18N_DOMAIN ) );
				$valid_conf_for_cron = false;
			}
			else {
				$valid['del_at'] = $input['del_at'];
				$hour = $matches[1];
				$min  = $matches[2];
			}

			if ( $valid_conf_for_cron  ) {
				$valid['schedule_enabled'] = 'enabled';
				wp_schedule_event( self::get_timestamp_for_cron( $hour, $min ), 'daily', 'hm_tor_cron_hook', array( intval( $valid['del_older_than'] ) ) );
			}
		}

		$valid['quick_edit']     = ( ( isset($input['quick_edit']) && $input['quick_edit'] == "on" ) ? "on" : "off" );
		$valid['bulk_edit']      = ( ( isset($input['bulk_edit']) && $input['bulk_edit'] == "on" ) ? "on" : "off" );
		$valid['del_on_publish'] = ( ( isset($input['del_on_publish']) && $input['del_on_publish'] == "on" ) ? "on" : "off" );

		return $valid;
	}

	function admin_menu() {
		add_options_page( 'Thin Out Revisions', 'Thin Out Revisions', 'manage_options',
			'hm_tor_option_page', array( &$this, 'admin_page' ) );
	}

	function admin_page() {
		?>
		<script type="text/javascript">
			(function($, window, document) {

				$(document).ready(function() {

					$('#hm_tor_rm_now_button').click(function() {
						if (! /^[0-9]+$/.test( $('#hm_tor_del_older_than').val())) {
							alert('<?php echo __( 'The day has to be an integer.', self::I18N_DOMAIN ); ?>');
							return false;
						}
						if (!confirm('<?php echo __( "Do you really want to remove this?", self::I18N_DOMAIN ); ?>' + ' (' + $('#hm_tor_del_older_than').val() + ' ' +
						              '<?php echo __( 'days', self::I18N_DOMAIN ); ?>' + ')')) {
							return false;
						}
						$('#hm_tor_rm_now_msg').html('<?php echo __( 'Processing...', self::I18N_DOMAIN ); ?>');
						$.ajax({
							url: '<?php echo admin_url( 'admin-ajax.php', isset( $_SERVER["HTTPS"] ) ? 'https' : 'http' ); ?>',
							dataType: 'json',
							data: {
								action: 'hm_tor_do_ajax_start_delete_old_revisions',
								days: $('#hm_tor_del_older_than').val(),
								security: '<?php echo wp_create_nonce( self::PREFIX . "nonce" ); ?>'
							}
						})
						.success (function(response) {
							$('#hm_tor_rm_now_msg').html(response.msg);
						})
						.error (function() {
							$('#hm_tor_rm_now_msg').html('<?php  __( 'Error in communication with server', self::I18N_DOMAIN ); ?>');
						});

						return false;
					});

				});
			})(jQuery, window, document);
		</script>

		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>Thin Out Revisions</h2>

			<form action="options.php" method="post">
				<?php settings_fields( 'hm_tor_option_group' ); ?>
				<?php do_settings_sections( 'hm_tor_option_page' ); ?>
				<p class="submit">
					<input class="button-primary" name="Submit" type="submit" value="<?php echo __( 'Save Changes' ); ?>" /></p>
			</form>
		</div>
	<?php
	}

	function delete_old_revisions( $days ) {
		global $wpdb;

		$revisions = $wpdb->get_results($wpdb->prepare(
			"SELECT ID, post_parent, post_name
      FROM $wpdb->posts
      WHERE post_type = 'revision'
			AND DATE_SUB(CURDATE(), INTERVAL %d DAY) >= post_date
			ORDER BY post_parent, post_date DESC", ( $days - 1 )
		) ); // Both CURDATE and post_date are local time.

		// COPY FROM
		// refer wp_save_post_revision
		$parent = 0;
		foreach ( $revisions as $revision ) {
			if ( $this->has_copy_revision() && $parent != $revision->post_parent &&
					false !== strpos( $revision->post_name, "{$revision->post_parent}-revision" ) ) {
				// avoid autosave

				$parent = $revision->post_parent;
			}
			else {
				// delete revisions
				wp_delete_post_revision( $revision->ID );
			}
		}

	} // end of bulk_delete

	function do_ajax_start_delete_old_revisions() {
		if ( check_ajax_referer( self::PREFIX . "nonce", 'security', false ) ) {

			wp_schedule_single_event( time(), 'hm_tor_cron_hook', array( intval($_REQUEST['days'] ) ) );
			echo json_encode( array(
				"result" => "success",
				"msg"    => __( "The task is successfully started.", self::I18N_DOMAIN )
			) );
		}
		else {
			echo json_encode( array(
				"result" => "error",
				"msg"    => __( "Wrong session. Unable to process.", self::I18N_DOMAIN )
			) );
		}
		die();
	}

	function cron_hook( $days ) {
		$this->delete_old_revisions( $days );
	}

} // end of class HM_TOR_Plugin_Loader

class HM_TOR_Plugin_Loader_3_5 extends HM_TOR_Plugin_Loader {
	function __construct() {
		parent::__construct();
	}

	function init() {
		parent::init();
		// replace the default 'pre_post_update' handler
		remove_action( 'pre_post_update', 'wp_save_post_revision' );
		add_action( 'pre_post_update', array( &$this, 'pre_post_update' ) );
	}

	function has_copy_revision() {
		return false;
	}

	function admin_enqueue_scripts() {
		wp_enqueue_script( 'jquery' );
	}

	function admin_notices() {
		// do nothing.
	}

	function admin_init() {
		parent::admin_init();

		add_settings_field( 'hm_tor_quick_edit', __( 'Disable revisioning while quick editing', self::I18N_DOMAIN ),
			array( &$this, 'settings_field_quick_edit' ), 'hm_tor_option_page', 'hm_tor_main' );
		add_settings_field( 'hm_tor_bulk_edit', __( 'Disable revisioning while bulk editing', self::I18N_DOMAIN ),
			array( &$this, 'settings_field_bulk_edit' ), 'hm_tor_option_page', 'hm_tor_main' );
	}

	function pre_post_update( $post ) {
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

	function settings_field_quick_edit() {
		$this->settings_field( 'quick_edit', __( 'Disable revisioning while quick editing', self::I18N_DOMAIN ) );
	}

	function settings_field_bulk_edit() {
		$this->settings_field( 'bulk_edit', __( 'Disable revisioning while bulk editing', self::I18N_DOMAIN ) );
	}

	function admin_head() {

		global $left, $right, $post;

		$uri = parse_url( $_SERVER['REQUEST_URI'] );

		if ( strpos( $uri['path'], '/revision.php' ) === false ) {
			return;
		}

		if ( ! $revisions = wp_get_post_revisions( $post->ID ) )
			return;

		if ( ! ( $left != '' && $right != '' && $left != $right ) )
			return;

		array_unshift( $revisions, $post );

		$revs             = array();
		$liststr          = "<ul>";
		$in_range         = false;
		$post_notselected = false;
		$posts2del        = array();
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
		$revs_str = "[" . implode( $revs, "," ) . "]";
		$liststr .= "</ul>";

		if ( count( $posts2del ) == 0 ) {
			if ( ! $post_notselected && current_user_can( 'edit_post', $left ) ) {
				$buttonval  = sprintf( __( "Remove a revision &quot;%s&quot;", self::I18N_DOMAIN ), wp_post_revision_title( $left, false ) );
				$posts_data = $left;
				$msg_reload = sprintf( __( "Once you return to <a href=\"%s\">the edit page</a>, you could continue working with revisions. Or reselect existing revisions and press &quot;%s&quot; button.", self::I18N_DOMAIN ), get_edit_post_link( $post->ID ), __( "Compare Revisions" ) );
				$liststr    = '';
			}
			else {
				return;
			}
		}
		else {
			$buttonval  = sprintf( __( "Remove revisions between &quot;%s&quot; and &quot;%s&quot; exclusively", self::I18N_DOMAIN ),
				wp_post_revision_title( $right, false ), wp_post_revision_title( $left, false ) );
			$posts_data = implode( '-', $posts2del );
			$msg_reload = __( "To update the compare-revision table, reload this page.", self::I18N_DOMAIN );
		}
		$ajaxnonce = wp_create_nonce( self::PREFIX . "nonce" );
		$ajaxurl   = admin_url( 'admin-ajax.php', isset( $_SERVER["HTTPS"] ) ? 'https' : 'http' );

		$msg_confirm         = __( "Do you really want to remove this?", self::I18N_DOMAIN );
		$msg_process         = __( 'Processing...', self::I18N_DOMAIN );
		$msg_info            = count( $posts2del ) > 0 ? __( 'Following revisions will be removed.', self::I18N_DOMAIN ) : "";
		$msg_info2           = sprintf( __( "To change revisions to remove, you have to press &quot;%s&quot; button after selection.", self::I18N_DOMAIN ), __( 'Compare Revisions' ) );
		$msg_error           = __( 'Error in communication with server', self::I18N_DOMAIN );
		$msg_title           = __( 'Thin Out Revisions', self::I18N_DOMAIN );
		$msg_after_selection = __( "You selected revisions after loading this page but revisions to remove were determined at the time of loading. Are you sure to proceed?", self::I18N_DOMAIN );

		$src = <<<JQSRC
<script type="text/javascript">
  jQuery(document).ready(function() {
    var modified = false;

    jQuery('.post-revisions input[type="radio"]').click(function() {
      modified = true;
    });

    jQuery('#wpbody-content .wrap').append(
      '<h3>$msg_title</h3>'
      + "<form><input type='button' id='mh_rto_ajax' class='button-secondary' value='$buttonval' /></form>"
      + '<div id="hm_tor_msg" style="margin: 0.5em 0; padding: 0.5em 1em;">$msg_info'
      + '$liststr $msg_info2'
      + '</div>'
    );

    jQuery('#mh_rto_ajax').click(function() {
      if (modified && !confirm('$msg_after_selection') ) {
        return;
      }
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

}


/*
  class to show memo for revisions
 */
class HM_TOR_RevisionMemo_Loader {
	const I18N_DOMAIN = 'thin-out-revisions';
	const PREFIX      = 'hm_tor_';

	private $no_new_revision  = false;
	private $last_revision_id = 0;

	// Constructor
	function __construct() {

		// Build user interface
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_box' ) );

		// Add metadata to a revisions to be saved
		add_action( 'save_post', array( &$this, 'save_post' ) );

		// Showing text input area for memo in revision.php.
		add_action( 'admin_head', array( &$this, 'admin_head' ) );

		// from WP3.6
		add_filter( 'wp_save_post_revision_check_for_changes', array( &$this, 'wp_save_post_revision_check_for_changes' ), 200, 3 );
	}

	function admin_head() {
		global $left, $right, $post, $wpdb;

		$uri = parse_url( $_SERVER['REQUEST_URI'] );

		$revision_php = false;
		if ( strpos( $uri['path'], '/revision.php' ) ) {
			$revision_php = true;
		}
		else if ( strpos( $uri['path'], '/post.php' ) ) {
		}
		else {
			return;
		}
		if ( !$post || !$post->ID ) {
			return;
		}

		$memos = $wpdb->get_results(
			"
      SELECT post_id, meta_value
      FROM $wpdb->posts, $wpdb->postmeta
      WHERE post_parent = $post->ID
      AND $wpdb->postmeta.post_id = $wpdb->posts.ID
      AND meta_key = '_hm_tor_memo'
      ORDER BY post_date DESC
      "
		);

		$postmemo = get_post_meta( $post->ID, "_hm_tor_memo", true ); // keep this line for pre 3.6 posts

		if ( ! $memos && ! $postmemo ) {
			return;
		}

		$latest_revision = $this->get_latest_revision( $post->ID );

		if ( ! $postmemo && $latest_revision != 0 ) {
			$postmemo = get_post_meta( $latest_revision, "_hm_tor_memo", true );
		}

		?>
		<script type='text/javascript'>
			var memos = {
				<?php
				    $has_latest = false;
						foreach ($memos as $m) {
						  if ($m->post_id == $latest_revision) {
						    $has_latest = true;
						  }
							echo "'$m->post_id': '" . esc_js($m->meta_value) . "',\n";
						}
						if ( (! $has_latest ) && $latest_revision != 0 ) {
						  echo "'$latest_revision': '" . esc_js($postmemo) . "',\n";
						}
						echo "'$post->ID': '" . esc_js($postmemo) . "'\n";
				?>
			};
			jQuery(document).ready(function () {
				jQuery('.post-revisions a').each(function () {
					var parse_url = /(post|revision)=([0-9]+)/;
					var result = parse_url.exec(jQuery(this).attr('href'));
					if (result && memos[result[2]]) {
						<?php
								if ( $revision_php ) {
						?>
						jQuery(this).parent().next().append(' [' + memos[result[2]] + ']');
						<?php
								}
								else {
						?>
						jQuery(this).after(' [' + memos[result[2]] + ']');
						<?php
								}
						?>
					}
				});

				jQuery('#hm_tor_memo_current').html(' <?php if ($postmemo) { echo "[" . esc_js($postmemo) . "]"; } ?>');
			});
		</script>
	<?php
	} // end of 'admin_head'

	// This function should be overrided for 3.5
	function get_latest_revision( $post_id ) {

		// COPY FROM CORE
		$latest_revision = 0;
		$revisions = wp_get_post_revisions( $post_id );

		if ( ! empty( $revisions ) ) {
			// grab the last revision, but not an autosave (from wp_save_post_revision in WP 3.6)
			foreach ( $revisions as $revision ) {
				if ( false !== strpos( $revision->post_name, "{$revision->post_parent}-revision" ) ) {
					$latest_revision = $revision->ID;
					break;
				}
			}
		}
	  return $latest_revision;
	}

	function add_meta_box() {
		global $post;
		if ( $post && post_type_supports( $post->post_type, 'revisions' ) ) {
			// add_meta_box( 'hm-he-revision', __('Revisions'), 'post_revisions_meta_box', null, 'normal', 'core' );
			add_meta_box( 'hm-he-memo', __( 'Revision Memo', self::I18N_DOMAIN ), array( &$this, 'hm_tor_mbfunction' ), null, 'normal', 'core' );
	  }
	}

	function hm_tor_mbfunction( $post ) {
		wp_nonce_field( plugin_basename( __FILE__ ), 'hm_tor_nonce' );
		$memo = ''; // always empty
		echo __( "Memo: ", self::I18N_DOMAIN );
		?>
		<input type="text" name="hm_tor_memo" value="<?php echo esc_attr( $memo ); ?>" style="width: 300px;" />
		<span id="hm_tor_memo_current"></span>
	<?php

	}

	function save_post( $post_id ) {
		global $wpdb;

		if ( isset( $_POST['hm_tor_nonce'] ) && wp_verify_nonce( $_POST['hm_tor_nonce'], plugin_basename( __FILE__ ) ) &&
				isset( $_POST['hm_tor_memo'] ) &&
				( ( $_POST['post_type'] == 'post' && current_user_can( 'edit_post', $post_id ) )
						|| ( $_POST['post_type'] == 'page' && current_user_can( 'edit_page', $post_id ) ) )
		) {
			if ( $parent = wp_is_post_revision( $post_id ) ) {
				// saving a revision

				if ( $_POST['hm_tor_memo'] !== '' ) {
					// We cannot use update_post_meta for revisions because it will add metadata to the parent.
					update_metadata( 'post', $post_id, '_hm_tor_memo', sanitize_text_field( $_POST['hm_tor_memo'] ) );
				}
			}
			else {
				// saving a post

				if ($this->last_revision_id != 0) {

					// for compatibility for WP3.5 and older.
					$postmemo = get_post_meta( $post_id, '_hm_tor_memo', true);
					if ( $postmemo ){
						update_metadata( 'post', $this->last_revision_id, '_hm_tor_memo', $postmemo );
						delete_post_meta( $post_id, '_hm_tor_memo' );
					}

					// If we have a new memo value, update the memo even no new revision is created.
					if ( $this->no_new_revision && $_POST['hm_tor_memo'] !== '' ) {
						// Attach the new memo to the latest revision
						update_metadata( 'post', $this->last_revision_id, '_hm_tor_memo', sanitize_text_field( $_POST['hm_tor_memo'] ) );
					}
				}
			}
		} // if ( isset ...
	}

	function wp_save_post_revision_check_for_changes( $val, $last_revision, $post) {
		// code from revision.php
		$post_has_changed = false;

		// COPY FROM CORE
		// from wp_save_post_revision
		foreach ( array_keys( _wp_post_revision_fields() ) as $field ) {
			if ( normalize_whitespace( $post->$field ) != normalize_whitespace( $last_revision->$field ) ) {
				$post_has_changed = true;
				break;
			}
		}

		$this->no_new_revision  = ( ( ! $post_has_changed ) && $val );
		$this->last_revision_id = $last_revision->ID;

		return $val;
	}

} // end of 'HM_TOR_RevisionMemo_Loader

// a class for WP 3.5
class HM_TOR_RevisionMemo_Loader_3_5 extends HM_TOR_RevisionMemo_Loader {

	function __construct() {
		parent::__construct();
	}

	function save_post( $post_id ) {
		global $wpdb;

		if ( wp_is_post_revision( $post_id ) ) {
			if ( ( $parent = $wpdb->get_col( $wpdb->prepare( "SELECT post_parent FROM $wpdb->posts WHERE ID = '%s'", $post_id ) ) ) ) {
				foreach ( (array) $parent as $p ) {
					$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->postmeta SET post_id = '%s' WHERE post_id = '%s' AND meta_key = '_hm_tor_memo' ", $post_id, $p ) );
				}
			}
		}
		else {
			if ( isset( $_POST['hm_tor_nonce'] ) && wp_verify_nonce( $_POST['hm_tor_nonce'], plugin_basename( __FILE__ ) ) &&
					isset( $_POST['hm_tor_memo'] ) && $_POST['hm_tor_memo'] !== '' &&
					( ( $_POST['post_type'] == 'post' && current_user_can( 'edit_post', $post_id ) )
							|| ( $_POST['post_type'] == 'page' && current_user_can( 'edit_page', $post_id ) ) )
			) {
				update_post_meta( $post_id, '_hm_tor_memo', sanitize_text_field( $_POST['hm_tor_memo'] ) );
			}
		}
	}

	function get_latest_revision( $post_id ) {
		return 0; // to indicate the version is 3.5
	}
}

$hm_tor_plugin_loader = null;
$hm_tor_revisionmemo_loader = null;

// Load HM_TOR_Plugin_Loader first.
if ( version_compare( get_bloginfo( 'version' ), '3.6-alpha' ) >= 0 ) {
	$hm_tor_plugin_loader = new HM_TOR_Plugin_Loader();
	$hm_tor_revisionmemo_loader = new HM_TOR_RevisionMemo_Loader();
}
else {
	$hm_tor_plugin_loader = new HM_TOR_Plugin_Loader_3_5();
	$hm_tor_revisionmemo_loader = new HM_TOR_RevisionMemo_Loader_3_5();
}

