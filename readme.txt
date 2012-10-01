=== Plugin Name ===
Contributors: blogger323
Donate link: http://en.hetarena.com/donate
Tags: revisions, revision, posts, admin
Requires at least: 3.2
Tested up to: 3.4.2
Stable tag: 0.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Thin Out Revisions is a plugin to thin out post/page revisions manually. 

== Description ==

As its default behavior, WordPress always makes a new revision when you save your post. 
This is too often even if you like revision control. Thin Out Revisions (TOR), a plugin 
for WordPress, will help you thin out revisions manually.

After activating this plugin, you will see a button to thin out revisions below the table 
on revision.php. To thin out, simply click the button.

If you have selected revisions having intermediate revisions between them, TOR button is to remove the intermediates. 
Or if you have selected revisions next to each other, TOR button is to remove the older selected one. 
To thin out intermediates between selected revisions is very intuitive operation and easy to use. 
But you may also want to remove the oldest revision, so I have made these two behaviors. 
Please carefully check a message on the button and displayed revisions to remove.

TOR uses a standard function, wp_delete_post_revision, to remove revisions. 
So, it also removes related data and works fine in multisite environment. 
If you like revision control, this is MUST HAVE. Happy blogging!

Related Links:

* [Plugin Homepage](http://en.hetarena.com/thin-out-revisions "Plugin Homepage")

== Installation ==

You can install Thin Out Revisions in a standard manner.

1. Go to Plugins - Add New page of your WordPress and install Thin Out Revisions plugin.
1. Or unzip `thin-out-revisions.zip` to your `/wp-content/plugins/` directory.

Thin Out Revisions comes with no setting options.
Don't forget to activate the plugin before use it.

== Frequently Asked Questions ==

This section will be updated soon.

== Screenshots ==

1. A Button to remove intermediate revisions
2. A Button to remove a single revision

== Changelog ==

= 0.8 =
* The first version introduced in wordpress.org repository.

== Upgrade Notice ==

This section will be updated when a new release comes.

