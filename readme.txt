=== Plugin Name ===
Contributors: blogger323
Donate link: http://en.hetarena.com/donate
Tags: revisions, revision, posts, admin
Requires at least: 3.2
Tested up to: 3.4.2
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Thin Out Revisions is a plugin to thin out post/page revisions manually. 

== Description ==

As its default behavior, WordPress always makes a new revision when you save your post. 
In addition, this also happens when you do a preview because you implicitly save your post when you press the preview button.
This is too often even if you like revision control. Thin Out Revisions (TOR), a plugin 
for WordPress, will help you thin out revisions manually.

After activating this plugin, you will see a button to thin out revisions below the table 
on revision.php, where you compare revisions. To thin out, simply click the button.

If you have selected revisions having intermediate revisions between them, TOR button is to remove the intermediates. 
Or if you have selected revisions next to each other, TOR button is to remove the older selected one. 
To thin out intermediates between selected revisions is very intuitive operation and easy to use. 
But you may also want to remove the oldest revision (somtimes a enpty revision!), so I have made these two behaviors. 
Please carefully check a message on the button and displayed revisions to remove.

TOR uses a standard function, wp_delete_post_revision, to delete revisions. 
So, it also deletes related data and works fine in multisite environment. 
If you like revision control, you will like it. Download TOR today for your happy blogging.

Related Links:

* [Plugin Homepage](http://en.hetarena.com/thin-out-revisions "Plugin Homepage")

== Installation ==

You can install Thin Out Revisions in a standard manner.

1. Go to Plugins - Add New page of your WordPress and install Thin Out Revisions plugin.
1. Or unzip `thin-out-revisions.*.zip` to your `/wp-content/plugins/` directory. The asterisk is a version number.

Thin Out Revisions comes with no setting options.
Don't forget to activate the plugin before use it.

== Frequently Asked Questions ==

= I can't see any changes after an activation of the plugin. =
Change is only a new button on revision.php.

= Where is revision.php? =
You can go to revision.php by choosing a revision on 'Edit Post'/'Edit Page' screen.
If you can't see any revisions on the 'Edit Post'/'Edit Page' screen, check the 'Revisions' option in Screen Options at top-right of the page.
If you can't see the 'Revisions' option in the menu, I guess you still don't have any revisions. So edit post/page and save it first.

== Screenshots ==

1. A button to remove intermediate revisions
2. A button to remove a single revision
3. A screen after deleting some revisions

== Changelog ==

= 1.0 =
* The version number is now 1.0. Though it's not a substantial change, many people will feel it sounds very formal.
* Better visual effects on deleted revisions. Now you can easily identify them.
* [Bug fix] The number of deleted revisions is corrected to show a real deleted number.
* Now the code is using a class. No impact for end users.
* Other some minor changes for stable operation.

= 0.8 =
* The first version introduced in wordpress.org repository.

== Upgrade Notice ==

= 1.0 =
Better visual effects on deleted revisions. Now you can easily identify them.
For more information, check the change log on the plugin page.

