=== Post Status Scheduler ===
Contributors: farne
Tags: posts, categories, postmeta, poststatus, change, schedule, scheduling
Requires at least: 3.9
Tested up to: 4.0
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Change status, category or postmeta of any post type at a scheduled timestamp. 

== Description ==

Post Status Scheduler allows for scheduling of post status changes, category adding or removing and 
removing of postmeta on any given date or time. It can be activated on any post type and shows 
up on the post edit screen in the publish section. From version 1.0.0 it has a feature for sending
an email notification to the post author on the scheduled update.

= Shortcodes =

* [pss_scheduled_time post_id="<your post id>"] can be used to get the post's scheduled date and time.

= Filters =
Scheduled Update:
* post_status_scheduler_before_execution
* post_status_scheduler_after_execution

Email Notification ( version 1.0.0 ):
* post_status_scheduler_email_notification_recipient_email
* post_status_scheduler_email_notification_subject
* post_status_scheduler_email_notification_date
* post_status_scheduler_email_notification_body

== Installation ==

1. Upload `post-status-scheduler` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the Post Status Scheduler in Settings > Post Status Scheduler menu.

== Screenshots ==

1. Settings page.
2. Edit page with no options activated.
3. Edit page without post meta option activated.
4. Edit page with post meta option activated.
5. Edit page with email notification option.

= What Translations are included? =

* Swedish

== Changelog ==

= 1.0.2 = 
* Fixed to use the correct textdomain. Translations should now work correctly.

= 1.0.1 =
* Fixed bug where, in settings, you could only choose public post types to show scheduler on (Reported on Github).

= 1.0.0 =
* New feature for sending email notification to post author when executing a scheduled update.
* New feature makes it possible to show/remove the "Scheduled date" column on posttype edit page.
* Code cleanup.

= 0.3.0 =
* Rewritten plugin to support setting multiple categories on scheduled time. I have tried to make sure that it will still work with previously scheduled changes in previous versions of the plugin.
* A little bit of code clean up and refactoring.

= 0.2.1 = 
* Added shortcode for getting the date and time for the scheduled post change

= 0.1.1 =
* Removed unnecessary assets folder.

= 0.1 =
* Initial version

== Upgrade Notice ==

= 1.0.2 =
* Upgrade to this version to get translations to work 100%.

= 1.0.1 =
* Gives you more post types to choose from.
