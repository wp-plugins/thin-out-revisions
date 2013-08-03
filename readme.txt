=== Plugin Name ===
Contributors: blogger323
Donate link: http://en.hetarena.com/donate
Tags: revisions, revision, posts, admin
Requires at least: 3.2
Tested up to: 3.6
Stable tag: 1.3.5
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

You can remove intermediate revisions during comparing in 'compare two revisions' mode in revision.php. To do it, simply press the 'Thin Out' button (fig. 1 in the screenshots page). 
And you can also remove each single revision from the Edit Post/Page screen (fig. 2).

If you are a user of WordPress prior to version 3.6, see [3.5 Revision Screen with TOR](http://en.hetarena.com/thin-out-revisions/wp35 "3.5 Revision Screen")

= When you publish a post =

If you are a careful author, I guess you have many revisions as a result of preview checks.
TOR can clean them.
Just simply enable the following option in the 'Settings' - 'Thin Out Revisions' admin page (fig. 3).

* Delete revisions on initial publication

= Revision Memo =

The Revision Memo feature enables you to put a short text note on revisions. See the last pictures (fig. 4) in screenshots page.
Make sure that you check the 'Revision Memo' screen option in Edit Post (Edit Page) screen.

= More to Describe =

* TOR works fine in multisite environment. 

If you like it, please share it among your friends by doing Tweet or Like from the plugin home page.
It will encourage the author a lot.

Related Links:

* [Plugin Homepage](http://en.hetarena.com/thin-out-revisions "Plugin Homepage")

== Installation ==

You can install Thin Out Revisions in a standard manner.

1. Go to Plugins - Add New page of your WordPress and install Thin Out Revisions plugin.
1. Or unzip `thin-out-revisions.*.zip` to your `/wp-content/plugins/` directory. The asterisk is a version number.

Thin Out Revisions comes with no setting options.
Don't forget to activate the plugin before use it.

== Frequently Asked Questions ==

= I got a warning to save the post without modification. What does it mean? =

From WordPress 3.6, proper posts have a copy revision of the current post.
Without a copy revision, updating a post results in loss of current content.
So when TOR find a post without copy revision, it will warn you to create a copy.

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

= Why did some options disappear in TOR 1.3 =
In version 3.6, WordPress has improved its revision management feature. 
As a result, WordPress 3.6 stops creating unnecessary revisions during quick editing and bulk editing.
So some options became needless.

== Screenshots ==

1. fig. 1. The 'Thin Out' button to remove intermediate revisions
2. fig. 2. A button to remove a single revision
3. fig. 3. An option
4. fig. 4. Memos are displayed with brackets


== Changelog ==

= 1.3.5 - Aug 3, 2013 =
* [Fixed] Broken behavior in WP 3.5. Sorry for frequent update.

= 1.3.3 - Aug 3, 2013 =
* Adjusting Revision Memo behavior for better handling in posts published prior to 3.6

= 1.3.2 - Aug 3, 2013 =
* Following latest changes in WP 3.6.
* More stable operation.
* Fixed a bug of duplicate memos.

= 1.3.1 - July 17, 2013 =
* Following latest changes in WP 3.6.
* No improper warnings on records other than pages and posts (WP 3.6).

= 1.3 - Jun 2, 2013 =
* WordPress 3.6 compliant. WP 3.6 users have to upgrade to this version. Also WP 3.5 users can use this version.

= 1.2 - Feb 23, 2013 =
* New feature called 'Revision Memo'
* Some minor fixes
* Now screenshots are not included in the ZIP file.

= 1.1.1 - Feb 17, 2013 =
* [Fixed] more proper operation of once-published flag. Update needed for users who use the feature of 'Delete revisions on initial publication'
* A better interactive message to avoid unintended execution

= 1.1 - Dec 14, 2012 =
* Added the 'Disable revisioning while quick editing' feature
* Added the 'Disable revisioning while bulk editing' feature
* Added the 'Delete revisions on initial publication' feature
* The result message after deleting revisions is now colored

= 1.0 - Nov 11, 2012 =
* The version number is now 1.0. Though it's not a substantial change, many people will feel it sounds very formal.
* Better visual effects on deleted revisions. Now you can easily identify them.
* [Bug fix] The number of deleted revisions is corrected to show a real deleted number.
* Now the code is using a class. No impact for end users.
* Other some minor changes for stable operation.

= 0.8 - Oct 1, 2013 =
* The first version introduced in wordpress.org repository.

== Upgrade Notice ==

= 1.3.5 =
[Fixed] Broken behavior in WP 3.5.

= 1.3.3 =
Adjusting Revision Memo behavior for better handling in posts published prior to 3.6

= 1.3.2 =
Following latest changes in WP 3.6. WP 3.6 users have to upgrade to this version. Also WP 3.5 users can use this version.


