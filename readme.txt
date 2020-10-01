=== The Final Word ===
Contributors: hlashbrooke
Tags: comments, comment moderation, discussion, o2, p2
Requires at least: 4.7
Tested up to: 5.5
Stable tag: 1.0.3
License: GPLv2 or later

Have the final word in a comment thread by marking a chosen comment as the 'top comment'.

== Description ==

This plugin is built for and requires [O2](https://github.com/Automattic/o2) to be installed.

This plugin allows you to mark a selected comment as the one that effectively sums up the conversation from the thread. This is great for disucssions that require a final decision to be made, support threads where one comment has the best solution, and a host of other uses.

Functionality includes:

* Marking a chosen comment as the 'top comment'
* The top comment is displayed at the top of the comment list with a 'view in context' anchor link
* The top comment is also highlighted in context in the thread
* Only one comment can be selected as the top comment
* The top comment flag can be removed
* Only users who are able to edit the post can select a top comment
* Includes basic styling for top comments
* 'Top comment' label can be translated and/or filtered

[Contribute on GitHub](https://github.com/hlashbrooke/The-Final-Word).

== Installation ==

Installing "The Final Word" can be done either by searching for "The Final Word" via the "Plugins > Add New" screen in your WordPress dashboard, or by using the following steps:

1. Download the plugin via WordPress.org
1. Upload the ZIP file through the 'Plugins > Add New > Upload' screen in your WordPress dashboard
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Will this work without O2? =

No. In the future it might be updated to  work with other themes, but O2 has such a specific way of handling comments that this won't work elsewhere just yet.

== Changelog ==

= 1.0.3 =
* 2020-10-01
* Preventing invalid comment ID errors (props @dd32)

= 1.0.2 =
* 2017-08-26
* Adding `top_comment_label` filter to top comment label display
* Improving code styling

= 1.0.1 =
* 2017-08-23
* Adding nonce and permissions checks to ajax requests
* Improving code styling

= 1.0 =
* 2017-08-23
* Initial release

== Upgrade Notice ==

= 1.0.2 =
* Adding `top_comment_label` filter to top comment label display
