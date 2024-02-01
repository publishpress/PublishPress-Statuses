=== PublishPress Statuses - Create Your WordPress Publishing Workflows ===
Contributors: publishpress, kevinB, stevejburge, andergmartins
Author: publishpress
Author URI: https://publishpress.com
Tags: statuses, custom statuses, workflow, draft, pending review
Requires at least: 5.5
Requires PHP: 7.2.5
Tested up to: 6.4
Stable tag: 1.0.5
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

PublishPress Statuses allows you to add custom statuses for your posts. You can use these statuses to create custom publishing workflows.

== Description ==

PublishPress Statuses allows you to add custom statuses for your posts. You can use these statuses to create custom publishing workflows.

WordPress provides "Draft" and "Pending Review". With the PublishPress Statuses plugin, you can add new statuses.

## How to Use PublishPress Statuses

Go to the "Statuses" area in your WordPress site and you'll see five different statuses. You can add, remove or re-arrange most of these statuses.

- **Draft**: This is the WordPress default status and can not be modified. 
- **Pitch**: This is a new status.
- **Assigned**: This is a new status.
- **In Progress**: This is a new status.
- **Pending Review**: This is a core WordPress status and can not be modified.

[Click here to see how to create and use statuses](https://publishpress.com/knowledge-base/start-statuses/).

## Custom Permissions for Statuses

PublishPress Statuses allows to decide which users can move content to which statuses. Go to "Statuses" then "Settings" and click the "Roles" tab. This allows you to choose which user roles can move a post to this status.

[Click here to see how control access to statuses](https://publishpress.com/knowledge-base/statuses-options/).

## Join PublishPress and get the Pro plugins

The Pro versions of the PublishPress plugins are well worth your investment. The Pro versions have extra features and faster support. [Click here to join PublishPress](https://publishpress.com/pricing/).

Join PublishPress and you'll get access to these nine Pro plugins:

* [PublishPress Authors Pro](https://publishpress.com/authors) allows you to add multiple authors and guest authors to WordPress posts.
* [PublishPress Blocks Pro](https://publishpress.com/blocks) has everything you need to build professional websites with the WordPress block editor.
* [PublishPress Capabilities Pro](https://publishpress.com/capabilities) is the plugin to manage your WordPress user roles, permissions, and capabilities.
* [PublishPress Checklists Pro](https://publishpress.com/checklists) enables you to define tasks that must be completed before content is published.
* [PublishPress Future Pro](https://publishpress.com/future) is the plugin for scheduling changes to your posts.
* [PublishPress Permissions Pro](https://publishpress.com/permissions) is the plugin for advanced WordPress permissions.
* [PublishPress Planner Pro](https://publishpress.com/publishpress) is the plugin for managing and scheduling WordPress content.
* [PublishPress Revisions Pro](https://publishpress.com/revisions) allows you to update your published pages with teamwork and precision.
* [PublishPress Series Pro](https://publishpress.com/series) enables you to group content together into a series.

Together, these plugins are a suite of powerful publishing tools for WordPress. If you need to create a professional workflow in WordPress, with moderation, revisions, permissions and more... then you should try PublishPress.

## Bug Reports 

Bug reports for PublishPress Statuses are welcomed in our [repository on GitHub](https://github.com/publishpress/publishpress-statuses). Please note that GitHub is not a support forum, and that issues that are not properly qualified as bugs will be closed.

== Installation ==

This section describes how to install the plugin and get it working.

1. Unzip the plugin contents to the `/wp-content/plugins/publishpress-statuses/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

== Frequently Asked Questions ==

== Changelog ==

= [1.0.5] - 16 Jan 2024 =
* Lang : Some Publish and Save As button labels were not translated if saved (in Statuses > Edit Status) with default values
* Fixed : Gutenberg editor - Using Post Status dropdown to select Pending status, followed by "Selected status" Workflow selection, caused post to be saved with an invalid status value, making it inaccessible
* Fixed : Restore Pending Review posts previously made inaccessible by Gutenberg UI integration glitch
* Fixed : Gutenberg editor - Button captions and workflow labels were non-standard after selecting Pending Review from Post Status dropdown
* Fixed : Improved Gutenberg / Classic detection is much simpler and more reliable
* Fixed : Classic Editor usage triggered by some 3rd party plugins required Statuses plugin setting change for compatibility
* Fixed : Editor usage setting (Gutenberg / Classic) was not effective under some conditions
* Fixed : Statuses > Settings could not disable all post types
* Import : On sites with imported PublishPress Planner statuses, plugin de/re-activation modified the position and enable / disable of some statuses
* Import : Planner 3.x import - some inconsistencies in the how status positions were imported
* Import : Permissions Pro 3.x import - status post types, nesting, labels were not imported
* Import : On deactivation, encoded status properties used by Planner 3.x were not restored
* Import : On deactivation, Planner 3.x post types settings  (using "on" / "off" value storage) were not restored
* Import : Option to re-import Planner configuration, with or without Permissions Pro Status Control properties
* Import : Failsafe mechanism disables auto-import if last attempt did not complete normally
* Feature : Automatic and Manual backup of colors, icons, labels and post types for all statuses
* Feature : Restore status colors, icons, labels or post types from automatic or manual backup
* Feature : Revert status  colors, icons, labels or post types to defaults
* Feature : Revert status  colors, icons, labels or post types to Planner defaults
* Change : Rearranged plugin settings UI and clarified some captions
* Change : Hide "Sub-status selection" option if Workflow Guidance is not set to "Sequence by default"
* Change : On new status creation, give status assignment capability to all roles that can edit Posts or Pages (not just standard roles)

= [1.0.4.1] - 11 Jan 2024 =
* Fixed : Status assignment capabilities for plugin-defined statuses were not granted to Editor, Author, Contributor by default
* Change : Don't enforce capability requirements for Pending Review status assignment by default, but introduce a plugin setting to do so
* Fixed : Using Post Status dropdown in Gutenberg editor to select the Pending status caused post to be saved with an invalid status value, making it inaccessible
* Fixed : Pending Review checkbox was still active in Gutenberg editor even if access has been removed from role
* Fixed : Classic Editor - Status of newly updated post was forced to Published (or highest status allowed) if Visibility Statuses are enabled by Permissions Pro
* Fixed : Classic Editor - Some button captions were not updated correctly after visibility / date selection
* Fixed : Labels tab not displayed on Edit Status screen for plugin-defined statuses if Label Storage mode set to "All plugin statuses"
* Fixed : PHP warnings on plugin install, status update

= [1.0.4] - 10 Jan 2024 =
* Fixed : Lang - Native WordPress status captions and editor button captions were not translated correctly
* Fixed : Lang - Statuses imported from Planner did not have translations applied
* Feature : Lang - Option to apply stored labels for user-defined statuses only
* Fixed : Classic Editor - Publish caption was missing if "default to next status" setting not enabled
* Fixed : Classic Editor - Some status and button captions did not refresh correctly based on new selections
* Fixed : Classic Editor - Bypass Sequence checkbox was displayed even if "default to next status" setting not enabled
* Fixed : Statuses disabled for post type were included in workflow sequence
* Compat : Permissions Pro - Prevent Permissions from causing a fatal error on Theme Customizer access
* Compat : Permissions Pro - Duplicate Visibility div in Classic Editor if Status Control enabled but Visibility Statuses disabled
* Compat : Permissions Pro - Current Visibility Status not displayed on load in Classic Editor

= [1.0.3.5] - 8 Jan 2024 =
* Compat : Yoast Duplicate Post - Rewrite & Republish function failed if PP Statuses is active
* Compat : General precaution to prevent inappropriate modification of post status
* Fixed : Classic Editor - When editing an unpublished post, Published option was displayed in Post Status dropdown for users who can publish

= [1.0.3.4] - 8 Jan 2024 =
* Fixed : If one of the default statuses was already user-defined in Planner, the import script changed its position

= [1.0.3.3] - 8 Jan 2024 =
* Fixed : Colors were not displayed on Statuses management screen
* Change : Include default alternate workflow statuses: Deferred, Needs Work, Rejected
* Change : Include a sample alternate workflow (disabled by default): Committee, Committee Review, Committee Progress, Committee Approved
* Change : Recaption section titles on Statuses screen

= [1.0.3.2] - 8 Jan 2024 =
* Change : PublishPress Planner import put some statuses into wrong section

= [1.0.3.1] - 8 Jan 2024 =
* Change : PublishPress Planner import will execute again if Planner is re-activated and statuses added or modified

= [1.0.3] - 8 Jan 2024 =
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
* Change : Posts / Pages screen - Eliminate redundant Status column
* Fixed : Posts / Pages screen - Quick Edit post status dropdown displayed blank for Published, Scheduled posts
* Fixed : Posts / Pages screen - Quick Edit caused columns to be offset
* Fixed : Posts / Pages screen - Quick Edit did not immediately update status caption
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
