=== Plugin Name ===
Contributors: blogger323
Donate link: http://en.hetarena.com/donate
Tags: revisions, revision, posts, admin
Requires at least: 3.2
Tested up to: 3.5
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Thin Out Revisions is a plugin to thin out post/page revisions. 

== Description ==

= Abstract =

As its default behavior, WordPress always makes a new revision when you save your post. 
This also happens when you do a preview before publication.
This is too often even if you like revision control. Thin Out Revisions (TOR), a plugin 
for WordPress, will help you to keep unwanted revisions out.

= Basic Feature =

After activating this plugin, you will see a button to thin out revisions below the table 
on revision.php, where you compare revisions. To thin out, simply click the button.

If you have selected revisions having intermediate revisions between them, TOR button is to remove the intermediates. 
Or if you have selected revisions next to each other, TOR button is to remove the older selected one. 
Please carefully check a message on the button and displayed revisions to remove before press it (fig. 1, 2, 3).

= Additional Features in 1.1 =

TOR 1.1 introduced new features below.

* Disable revisioning while quick editing
* Disable revisioning while bulk editing
* Delete revisions on initial publication

To use these powerful new features, go to the 'Settings' - 'Thin Out Revisions' admin page and turn them on (fig. 4).

= Additional Feature in 1.2 =

Thin Out Revisions 1.2 introduced the 'Revision Memo' feature. It enables to put a short text note on revisions. See last two pictures (fig. 5, 6) in screenshots page.
Make sure that you check the 'Revision Memo' screen option in Edit Post (Edit Page) screen.

= More to Describe =

* TOR works fine in multisite environment. 

If you like it, please share it among your friends by doing Tweet or Like from the plugin home page.
It will encourage the author a lot.

Related Links:

* [Plugin Homepage](http://en.hetarena.com/thin-out-revisions "Plugin Homepage")
* Related Post: ['The truth of WordPress revision management'](http://en.hetarena.com/archives/139)

== Installation ==

You can install Thin Out Revisions in a standard manner.

1. Go to Plugins - Add New page of your WordPress and install Thin Out Revisions plugin.
1. Or unzip `thin-out-revisions.*.zip` to your `/wp-content/plugins/` directory. The asterisk is a version number.

Thin Out Revisions comes with no setting options.
Don't forget to activate the plugin before use it.

== Frequently Asked Questions ==

= Where is revision.php? =
You can go to revision.php by choosing a revision on 'Edit Post' or 'Edit Page' screen.
If you can't see any revisions on the screen, check the 'Revisions' option in Screen Options at top-right of the page.
If you can't see the 'Revisions' option in the menu, I guess you still don't have any revisions. So edit the post (page) and save it first.

= TOR doesn't remove revisions on publication. =
'Delete revisions on initial publication' feature is effective only when you first publish the post from 'draft' status.
It has no effects if you had once published the post and changed it to 'draft' status later.
Also no effects on auto-saved revisions.

= Where is text input form for Revision Memo? =
It will appear in 'Edit Post' ('Edit Page') screen. Make sure that you check the 'Revision Memo' screen option in the page.

== Screenshots ==

1. fig. 1. A button to remove intermediate revisions
2. fig. 2. A button to remove a single revision
3. fig. 3. A screen after deleting some revisions
4. fig. 4. New features introduced in 1.1
5. fig. 5. Memos are displayed with brackets
6. fig. 6. Make sure that you check the 'Revision Memo' screen option in Edit Post (Edit Page) screen

== Changelog ==

= 1.2 =
* New feature called 'Revision Memo'
* Some minor fixes
* Now screenshots are not included in the ZIP file.

= 1.1.1 =
* [Fixed] more proper operation of once-published flag. Update needed for users who use the feature of 'Delete revisions on initial publication'
* A better interactive message to avoid unintended execution

= 1.1 =
* Added the 'Disable revisioning while quick editing' feature
* Added the 'Disable revisioning while bulk editing' feature
* Added the 'Delete revisions on initial publication' feature
* The result message after deleting revisions is now colored

= 1.0 =
* The version number is now 1.0. Though it's not a substantial change, many people will feel it sounds very formal.
* Better visual effects on deleted revisions. Now you can easily identify them.
* [Bug fix] The number of deleted revisions is corrected to show a real deleted number.
* Now the code is using a class. No impact for end users.
* Other some minor changes for stable operation.

= 0.8 =
* The first version introduced in wordpress.org repository.

== Upgrade Notice ==

= 1.1 =
Introduced new features.

= 1.0 =
Better visual effects on deleted revisions. Now you can easily identify them.
For more information, check the change log on the plugin page.

