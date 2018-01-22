=== WP Mailster ===
Contributors: brandtoss, svelon
Tags: mailing list manager, emails, newsletter, listserv, discussion list, group communication
Requires at least: 4.3
Tested up to: 4.9
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WP Mailster allows your users to be part of a group and communicate by email without having to log into a website.

== Description ==

WP Mailster allows your users to be part of a group and communicate by email without having to log into a website.
Similar to programs like Mailman or Listserv this plugin allows you to run a discussion mailing list.

That means that every email sent to a central email address is forwarded to the rest of the members of the list.
When members reply to such emails WP Mailster again forwards them to the other list recipients.
Unlike newsletter plugins this allows true two-way communication via email.

Features include:

*   group communication through email
*   usable with any POP3/IMAP email account
*   recipients can be managed in the WordPress admin area
*   users can subscribe/unsubscribe through widgets on the website
*   all WP users can be chosen as recipients, additional recipients can be stored (without having to create them as WP users)
*   users can be organized in groups
*   single users or whole groups can be added as recipients of a mailing list
*   replies to mailing list messages can be forwarded to all recipients (or only the sender)
*   email archive for browsing the mails
*   full support of HTML emails and attachments
*   custom headers and footers
*   subject prefixes
*   many more features

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-mailster` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the WP Mailster screen to configure the plugin

== Frequently Asked Questions ==

= How do I send an email? =
When you want to use WP Mailster you don't need to browse to a website, login and do something to send the message there - you just use your favorite mail client.
Simply write an email to the mailing list's address - and nothing else. So use Gmail, Outlook, Thunderbird, a Webmailer, any way you like - just send it to the mailing list address you have setup in WP Mailster.

= Why take the emails so long to be delivered? How can I speed up sending?  =
WP Mailster is a part of WordPress which is a PHP based web application. That means: it can not act/run without being triggered and it can not run forever when triggered. This is a technical limitation coming from PHP, not from WP Mailster or WordPress.
Triggering means that somebody accesses the site. During the page load Mailster is handling the jobs to do (mail retrieving/sending). Thus mails can only be send/retrieved when somebody is browsing your site, otherwise the delivery is delayed or never done. As your site might not be browsed every few minutes 24×7 we recommend you to use a cronjob that opens the site periodically. We have a guide on our website on how to set that up.

= What are send errors?  =
The send errors are messages your email server is giving back to WP Mailster basically saying "I will not forward this message". Then WP Mailster sending for some time but eventually stops which is what you see happening.
The cause can be a lot of things, e.g. hitting send limits (per hour/day) or sending email with content that the server does not like.
You need to find out what your email servers are telling WP Mailster. Please follow our troubleshooting guide on our site.

== Changelog ==

= 1.5.5 =
*Release Date - 5th December 2017*

*   [Bug Fix] Security Fix XSS (Cross Site Scripting) issue in unsubscribe handler (thank you for your help, Ricardo Sanchez)

= 1.5.3 / 1.5.4 =
*Release Date - 19th November 2017*

*   [Improvement] Make plugin work in Multiuser / Network site context
*   [Improvement] Recipients names from WP users are coming from first name / last name instead of login names
*   [Improvement] Queue entries can be deleted
*   [Improvement] The shortcode [mst_emails] has new parameter to control the order the messages are dispalyed, e.g. [mst_emails lid=1 order=oldfirst] or [mst_emails lid=1 order=newfirst]
*   [Improvement] Various interface improvements to show #users per list/group and for better navigation
*   [Bug Fix] All list members (recipients) can be removed
*   [Bug Fix] Make unsubscribe (with and without double-opt-out) work
*   [Bug Fix] Subject prefixes can have a blank character at the end
*   [Bug Fix] Unsubscribe URL placeholder (for custom header/footer) works in typcial situations where a text editor adds unneeded http/https before the {unsubscribe} placeholder
*   [Bug Fix] Catch runtime exception in case log file cannot be generated
*   [Bug Fix] Improved logo naming so that browser caching will not prevent correct logo to be displayed after upgrade


= 1.5.0 =
*Release Date - 26th September 2017*

*   [Feature] Shortcode mst_emails allows to only select a specific mailing list by it's ID, e.g. [mst_emails lid=2]
*   [Feature] Mailing lists can be duplicated
*   [Improvement] Subscribe / unsubscribe forms work without page reload
*   [Improvement] Introduce reCAPTCHA v2, remove v1
*   [Improvement] PHP 5.3 and PHP 5.4 are supported
*   [Bug Fix] Resolve PHP 7.0 compatibility issue
*   [Bug Fix] Pagination fix for admin lists
*   [Bug Fix] Log file of installation is not written to the root directory
*   [Bug Fix] Fixed typos


= 1.4.19 =
*Release Date - 13th June 2017*

*   [Improvement] Speed optimization (remove unneeded DB schema checks)
*   [Improvement] Plugin Update Checker updated to latest version
*   [Bug Fix] Pagination fix for threads shortcode
*   [Bug Fix] Added default page size for mails in mails shortcode
*   [Bug Fix] CSV Import: check on PHP's max_input_vars setting to detect when too much entries would be present to inform the user (and skip the ones too much)


= 1.4.18 =
*Release Date - 4th May 2017*

*   [Bug Fix] Fix error when mailing lists are saved
*   [Bug Fix] Remove unnecessary "Show User description" setting
*   [Bug Fix] No whitespace in front of text-area settings


= 1.4.17 =
*Release Date - 20th April 2017*

*   [Improvement] Set date/time format displayed in email header/footer according to the WordPress installation's settings
*   [Bug Fix] Only show mailing list settings available in the respective product edition
*   [Bug Fix] Trigger Source setting can be saved
*   [Bug Fix] Language fixes


= 1.4.13 - 1.4.16 (9th April 2017) =
*   [Bug Fix] Fixed Profile shortcode


= 1.4.12 =
*Release Date - 8th April 2017*

*   [Improvement] Email archive in admin has now buttons to removing remaining queue entries and resetting send error count
*   [Bug Fix] Captcha and "add to group" selections can be made in the subscribe widget
*   [Bug Fix] Fixed Profile shortcode in Free edition (Users can subscribe and unsubscribe from lists)


= 1.4.11 =
*Release Date - 7th March 2017*

*   [Feature] CSV import and export of users/email addresses
*   [Bug Fix] Fix some warning messages


= 1.4.10 =
*Release Date - 19th February 2017*

*   [Feature] Introduce shortcodes to display email archives, mailing list and subscription profile
*   [Feature] Add duplicate bulk action to mailing list screen for copying lists
*   [Improvement] Show available shortcodes in dashboard
*   [Bug Fix] Don't remove white spaces in subject prefix, and plain header/footer
*   [Bug Fix] Remove CSS styling from site area and unneeded styling


= 1.4.9 =
*Release Date - 2nd February 2017*

*   [Bug Fix] Make inline images show up in the email archive message view
*   [Bug Fix] Attachment download in the backend email archive works again


= 1.4.8 =
*Release Date - 31th January 2017*

*   [Bug Fix] Automatically fix remaining DB collation differences


= 1.4.7 =
*Release Date - 31th January 2017*

*   [Bug Fix] Make some important options (e.g. max run time, minimum time between retrieving/sending, ...) available in the admin settings GUI


= 1.4.6 =
*Release Date - 30th January 2017*

*   [Improvement] Add search functionality to all admin area lists/tables
*   [Improvement] Improve some GUI elements on the edit mailing list screen
*   [Bug Fix] Existing log file is not overwritten during plugin updates


= 1.4.5 =
*Release Date - 25th January 2017*

*   [Improvement] Show recipient count used in mailing list VS max recipients
*   [Improvement] Better show active GUI elements in edit mailing list screen
*   [Bug Fix] Various small fixes


= 1.4.4 =
*Release Date - 24th January 2017*

*   [Bug Fix] Saving of multiple mailing list settings fixed
*   [Bug Fix] Emails can be deleted in the email archive


= 1.4.3 =
*Release Date - 23rd January 2017*

*   [Improvement] GUI improvements in the mailing list management
*   [Bug Fix] Automatically fixes DB collation differences
*   [Bug Fix] Log file works when WP is installed in subdirectory
*   [Bug Fix] Fixed wrong textual file size representations (KB VS MB)


= 1.4.0 =
*Release Date - 3rd January 2017*

*   Initial release


== Upgrade Notice ==

= 1.5.5 =
XSS Security Fix

= 1.5.3 =
Multiuser site compatibility, Subscribe/Unsubscribing fixed, user interface improvements

= 1.5.0 =
Shortcode mst_emails allows single list selection, Subscribe forms improved, PHP 5.3 and 5.4 compatibility

= 1.4.19 =
Speed optimizations, Shortcode pagination fixed, CSV import fixes

= 1.4.18 =
Fix error when mailing lists are saved, fix issues in settings screen

