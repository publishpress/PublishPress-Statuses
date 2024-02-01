=== PublishPress Statuses ===
Contributors: publishpress, kevinB, stevejburge, andergmartins
Author: publishpress
Author URI: https://publishpress.com
Tags: statuses
Requires at least: 5.5
Requires PHP: 7.2.5
Tested up to: 6.4
Stable tag: 1.0.3
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

PublishPress Statuses allows you to customize the publication workflow by creating and configuring custom post statuses.

== Description ==

PublishPress Statuses allows you to customize the publication workflow by creating and configuring custom post statuses.

= Bug Reports =

Bug reports for PublishPress Statuses are welcomed in our [repository on GitHub](https://github.com/publishpress/publishpress-statuses). Please note that GitHub is not a support forum, and that issues that are not properly qualified as bugs will be closed.

== Installation ==

This section describes how to install the plugin and get it working.

1. Unzip the plugin contents to the `/wp-content/plugins/publishpress-statuses/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

== Frequently Asked Questions ==

== Changelog ==

= [1.0.3] - 6 Jan 2024 =
* Fixed : PublishPress Planner status properties (color, icon, position, description) were not imported
* Compat : Pods - Could not enable Pods-defined custom post types for custom statuses
* Fixed : Classic Editor - Custom statuses were not available if Classic mode is triggered in a non-standard way
* Feature : Classic Editor - When defaulting to next status, checkbox under publish button allows bypassing sequence; default-select after future date selection
* Feature : Classic Editor - Implement capability pp_bypass_status_sequence to regulate availability of sequence bypass checkbox
* Fixed : Classic Editor - For currently published posts, publish button was captioned as "Publish" instead of "Update"
* Fixed : Classic Editor - After selecting a future date, publish button was captioned as "Publish" instead of "Schedule"
* Fixed : Classic Editor - Redundant Save As Scheduled button was displayed for currently scheduled posts
* Fixed : Classic Editor - Publish button had a needlessly wide left margin
* Fixed : Classic Editor - Hide obsolete Pro upgrade prompt displayed by PublishPress Permissions 3.x inside post publish metabox
* Fixed : Posts / Pages screen - Post Status dropdown in Quick Edit displayed blank for Published posts
* Change : Posts / Pages screen - If Private checkbox in Quick Edit is clicked, set Status dropdown to Published
* Change : Posts / Pages screen - If Status dropdown in Quick Edit is set to something other than Published, uncheck Private checkbox
* Compat : PublishPress Permissions Pro - Status Edit screen did not update Set Status capability assignment correctly under some conditions
* Lang : A few string had wrong text domain

= [1.0.2.4] - 4 Jan 2024 =
* Change : Don't allow pre-publish checks to be disabled (unless forced by constant)

= [1.0.2.2] - 20 Dec 2023 =
* Change : In Workflow (Pre-Publish) panel, display selectable radio option for next status even if not defaulting to it
* Change : Force usage of Pre-Publish panel (unless disabled by constant)
* Change : New plugin setting "De-clutter status dropdown by hiding statuses outside current branch"; no longer do this by default
* Fixed : Explicitly selected Pending Review status did not save correctly (since 1.0.2.1)
* Fixed : Classic Editor - Visibility selector was missing
* Fixed : Classic Editor - Explicit selection of Published status was ignored if using Default to Next Status mode
* Fixed : Classic Editor - Numerous captioning and display toggle issues in post publish metabox

= [1.0.2.1] - 19 Dec 2023 =
* Fixed : Non-Administrator login caused Auto Draft publication
* Fixed : Pending status draggable to Disabled even though disabling is prevented
* Fixed : Edit Status - First update overrides Roles selection with defaults
* Fixed : Non-Administrator login causes Auto Draft publication
* Fixed : Safari - Post Status dropdown shows a blank item
* Fixed : Permissions Pro - Visibility Status button, form displayed without required Permissions Pro module
* Fixed : Permissions Pro - Disabled Visibility Statuses still available

= [1.0.2] - 13 Dec 2023 =
* Fixed : Redirect back to Planner Calendar settings after editing a status
* Fixed : Statuses Admin UI - Minor styling fix for tabs
* Fixed (Pro) : Visibility Statuses - workflow statuses filtering interfered with selection in some cases
* Change (Pro) : Visibility Statuses - allow selection of Post Types in Edit Status screen
* Compat : Permissions / Capabilities - Avoid redundant execution of status capabilities update handler

= [1.0.1] - 17 Oct 2023 =
* Fixed : If running without Permissions Pro, users who cannot set a status were not blocked from editing or deleting posts of that status
* Fixed : Capabilities Pro integration - Typo in PublishPress Statuses tab caption
* Code : Improved scan results

= [1.0.0] - 10 Oct 2023 =
* Added : Initial wordpress.org submission
