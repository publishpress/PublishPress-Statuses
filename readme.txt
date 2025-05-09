=== PublishPress Statuses - Custom Post Status and Workflow ===
Contributors: publishpress, kevinB, stevejburge, andergmartins
Author: publishpress
Author URI: https://publishpress.com
Tags: custom statuses, workflow, pending review, status manager, archived status
Requires at least: 5.5
Requires PHP: 7.2.5
Tested up to: 6.7
Stable tag: 1.1.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

The PublishPress Statuses plugin allows you to create additional statuses for your posts. You can use each status to create publishing workflows.

== Description ==

Have you ever wanted to label a WordPress post something other than "Draft" or "Pending Review"? The [PublishPress Statuses](https://publishpress.com/statuses/) plugin can help. 

PublishPress Statuses allows you to create additional statuses for your posts. For example, you can add statuses such as "In Progress", "Needs Work", or "Rejected". You can also control which users can move posts to each status.

## Why Use PublishPress Statuses?

WordPress provides you with only two statuses for your post: "Draft" or "Pending Review". This means that before your content is published it can only be labeled as "Draft" or "Pending Review". 

Those statuses are too limiting for many publishers. For example, what label should you use for content that is assigned to a writer? What label should you use for a post that needs work, or has been rejected? With the PublishPress Statuses plugin, you can add new statuses that accurately describe the stages of your publishing process.

There are two types of statuses:

- **Pre-Publication Statues**: For posts that are unpublished.
- **Visibility Statuses**: For posts that are published.

## Pre-Publication Statuses

Go to the "Statuses" area in your WordPress site and you'll six different statuses. This is the [main workflow](https://publishpress.com/knowledge-base/main-workflow/). Every post on your site must use this workflow. However, with PublishPress Statuses, you can move, rerrange and add to this workflow. 

- **Draft**: This is the WordPress default status and can not be modified. 
- **Pitch**: This is a new status. You can use this status to indicate the post is just an idea.
- **Assigned**: This is a new status. You can use this status to show the post has been given to a writer.
- **In Progress**: This is a new status. You can use this status to if the post is being worked on.
- **Approved**: This is a new status. You can use this status to when the post has been accepted and it ready for publication.
- **Pending Review**: This is a core WordPress status and can not be modified.

[Click here to see how to create and use statuses](https://publishpress.com/knowledge-base/start-statuses/).

In addition to the default workflow, PublishPress Statuses allows you to create [alternate workflows](https://publishpress.com/knowledge-base/alternate-workflow/). These statuses are for content that is not on a direct path to publication. Examples of these alternate workflows include "Deferred", "Needs Work" and "Rejected".

## Custom Permissions for Pre-Published Statuses

PublishPress Statuses allows to decide which users can move content to which statuses. Go to "Statuses" then "Settings" and click the "Roles" tab. This allows you to choose which user roles can move a post to this status.

[See how control access to statuses](https://publishpress.com/knowledge-base/statuses-options/).

You can take this further and decide who assign, edit, and delete content in each status. This is possible if you also use the PublishPress Permissions Pro plugin. 

[Click here to see add advanced capabilities to statuses](https://publishpress.com/knowledge-base/extended-capabilities-statuses/).

## Visibility Statuses

[Visibility Statuses](https://publishpress.com/knowledge-base/custom-visibility-statuses/) allow you to control who can access published content on your WordPress site.

The PublishPress Statuses plugin integrates with the [PublishPress Permissions Pro](https://publishpress.com/permissions/) plugin. This integration allows you to create custom visibility statuses and control who can access the content on the front of your WordPress site.

We call this feature "Custom Visibility Statuses" because WordPress has three core visibility statuses:

- **Scheduled**: This post is scheduled for future publication.
- **Published**: This post is available to general public.
- **Private**: This post is published for users logged in to your WordPress site.

Using PublishPress Statuses and PublishPress Permissions Pro together, you can add your own custom visiblity statuses.

## Custom Permissions for Visibility Statuses

The PublishPress Statuses plugin integrates with the PublishPress Permissions Pro plugins and PublishPress Capabilities Pro plugins. These allow you to control capabilities for each visibility status. You can decide who can assign, read, edit and delete content in each status.

[See how control access to visibility statuses](https://publishpress.com/knowledge-base/custom-capabilities-visibility-statuses/).

You can take this further and decide who assign, edit, and delete content in each status. This is possible if you also use the PublishPress Permissions Pro plugin. 

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

== Screenshots ==

1. Using PublishPress Statuses you can add custom workflow options that are available when editing posts. You can build one main workflow for your posts. This workflow will be available to your users on the post editing screen. 
2. In addition to the default workflow, PublishPress Statuses allows you to create alternate workflows. These statuses are for content that is not on a direct path to publication. Examples of these alternate workflows include "Deferred", "Needs Work" and "Rejected".
3. PublishPress Statuses supports branches in your workflows. You can create parent and child statuses. This allows posts to move through the individual branch before returning to the main workflow.
4. PublishPress Statuses allows you to customize which roles can assign posts to each status. You can give a user role the ability to move a post to just a single statuses. Or you can give a user role full access to your whole workflow.
5. With the addition of the PublishPress Capabilities Pro plugin you can create highly custom permissions for each status. You can control who can set, edit, and delete posts in each status.

== Installation ==

This section describes how to install the plugin and get it working.

1. Unzip the plugin contents to the `/wp-content/plugins/publishpress-statuses/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Does PublishPress Statuses integrate with PublishPress Planner? =

Yes, the PublishPress Statuses plugin integrates with the PublishPress Planner plugin. This allows you to use custom statuses, icons, and colors on the "Content Calendar" screen.

[Click here to see the Planner integration](https://publishpress.com/knowledge-base/statuses-calendar/).

= Can I Have Different Statuses for Different Post Types? =

Yes, it is possible to have different statuses for different WordPress post types. The benefit of this approach is that allows you have different workflows for each post type.

[Click here to see how to different statuses for different post types](https://publishpress.com/knowledge-base/different-statuses-post-types/).

= Can I Create Nested Statuses / Workflow Branches? =

The PublishPress Statuses plugin allows you to create workflow branches. These are separate parts of the workflow are displayed shown as indented, away from the main workflow. These branches be used to take content away from the core approval process.

You can create workflow branches with any statuses except for "Draft" and "Pending Review" which are core WordPress statuses.

To create workflow branches, use the drag-and-drop handles next to each status to place them into branches

[Click here to see more about the Workflow Branches](https://publishpress.com/knowledge-base/workflow-branches/).

= Does PublishPress Statuses Support the Pending Review Status? =

Yes, you can use the Pending Review status with this plugin. "Pending" is one of the default post statuses available in WordPress. You will often see it called "Pending Review".

A post in the Pending Review status is not published and is not visible to the public. The "Pending review" checkbox is available when you edit posts in WordPress.

[Click here to see more about the Pending Review status](https://publishpress.com/knowledge-base/pending-review/).

= Does PublishPress Statuses Support the Draft Status? =

Yes, you can use the Draft status with this plugin. "Draft" is one of the default post statuses available in WordPress. Draft is often confused with the "Pending Review" status. However, these two statuses have different meanings. Draft means "This post is not ready to publish. I'm still working on it." Pending Review means "This post is ready. Someone else can approve and publish".

A post in the Draft status is not published and is not visible to the public. Once a post is published, the "Draft" status is the default choice for unpublishing the post.

[Click here to see more about the Draft status](https://publishpress.com/knowledge-base/draft/).

= Does PublishPress Statuses Support the Scheduled Status? =

Yes, you can use the Scheduled status with this plugin. "Future" is one of the default post statuses available in WordPress. You will often see it called "Scheduled".

A post in the Future status is not published yet, but is scheduled to be published in a future date. 

[Click here to see more about the Future status](https://publishpress.com/knowledge-base/future/).

= How does this compare to the Extended Post Status plugin? =

Extended Post Status is a very useful plugin. With PublshPress Statuses we're aiming to provide more advanced features such as custom capabilities, workflow branching, icons, colors, and much more.

== Changelog ==

= [1.1.3-beta] =
* Fixed : Post Editor - Javascript errors on some sites
* Fixed : Edit Status - Post Access tab not displayed under some conditions
* Fixed : Post Editor - Javascript errors on some sites

= [1.1.2] - 5 Mar 2025 =
* Fixed : Posts could not be scheduled (instead being published immediately)
* Fixed : Post Editor - Redundant post status update on status dropdown selection, post save
* Change : Statuses Pro promotional headers, sidebar and/or links on plugin screens, admin menu

= [1.1.0] - unreleased =
* Plugin API to support Statuses Pro

= [1.0.9] - 13 Nov 2024 =
* Compat : WP 6.7 - On translated sites, error loading textdomain too early

= [1.0.8] - 13 Nov 2024 =
* Compat : WP 6.7 - Some display issues with post editor integration

= [1.0.7] - 16 Sep 2024 =
* Compat : WP 6.6 - Gutenberg UI integration was partially broken
* Fixed : Publication Workflow caption showed new post defaulting to Scheduled, not Published
* Fixed : On translated sites, post permalink was forced to plain format
* Fixed : Pending Review status label could not be customized by Edit Status screen
* Fixed : Classic Editor - PHP Warning for undefined array index "moderation"

= [1.0.6.9] - 18 Jun 2024 =
* Fixed : Could not create new Statuses with Multibyte label
* Fixed : Status backup / restore / default operation was not applied to core statuses (Draft, Pending)
* Fixed : Status default operation did not restore default Planner colors and icons under some conditions
* Compat : Planner - If one or more post types have Statuses integration disabled, customized status colors are not applied to paged results on Planner Content Calendar
* Compat : Disable Gutenberg - Classic Editor mode was not detected under some configurations

= [1.0.6.8] - 5 Apr 2024 =
* Compat : WP 6.5 - Workflow labels in post editor sidebar were mis-aligned
* Fixed : Fatal error in PHP 8.2 if another plugin sets $plugin_page to array
* Lang : Brazilian Portuguese translation

= [1.0.6.7] - 7 Feb 2024 =
* Compat : The Events Calendar, other plugins - Avoid js errors due to scripts being loaded before jQuery

= [1.0.6.6] - 31 Jan 2024 =
* Compat : Advanced Custom Fields - Selected / Current / Next Workflow selection was not applied if a required ACF field is in the editor
* Compat : The Events Calendar, other plugins - Avoid js errors due to scripts being loaded before jQuery
* Compat : ShortPixel Critical CSS - Conflict with post_status taxonomy causes status value to be cleared in post editor
* Compat : Custom Fields plugins - Stop disabling Publish button on click, in case custom field plugin doesn't re-enable it after required entries

= [1.0.6.5] - 30 Jan 2024 =
* Fixed : Gutenberg publish button and workflow status captions were not changed from "Publish" to "Schedule" if a future date is selected
* Fixed : For authors with limited status permissions, Gutenberg Post Status dropdown intially included unavailable statuses, then refreshed to correct statuses
* Fixed : Unintended progression to next / max status could be applied under some conditions
* Fixed : Status filtering could possibly be applied to wrong post under some conditions
* Compat : Advanced Custom Fields - Update attempts with missing required fields left Publish / Update button hidden
* Compat : The Events Calendar + The Events Calendar Pro - Extensive javascript errors in Post Editor
* Compat : Permissions Pro - Pending status was restricted by capability check even if Statuses > Settings configured to make Pending status available to all users

= [1.0.6.4] - 24 Jan 2024 =
* Compat : PublishPress Checklists - Blockage / Warning messages for content requirements were not displayed on Pre-Publish panel
* Fixed : Some status filtering was still applied even if plugin is disabled for the post type
* Lang : Updated translations

= [1.0.6.3] - 22 Jan 2024 =
* Change : Edit Status, Add Status screens - Distinct html titles for browser tab navigation
* Change : Swap the position of Post Types, Roles tabs on Edit Status screen
* Change : Visibility Statuses - Acknowledge installation of updated Status Capabilities library (in Capabilities Pro or Permissions Pro) by labeling Custom Visibility Capabilities as "Custom" or "Custom Read"
* Change : Edit Status - Don't toggle selection of type-specific Set capabilities when basic set capability is selected. It is a separate capability, not a toggle button.
* Fixed : Blank error message displayed on attempt to edit a status that is not defined.

= [1.0.6.2] - 18 Jan 2024 =
* Feature : When completing an alternate workflow, offer to step back to last previously saved main workflow status
* Fixed : Non-Administrators could not view private pages authored by other users
* Fixed : Classic Editor - Canceling out of Status selection caused selection to default back to Draft
* Fixed : Classic Editor - Canceling out of Visibility selection caused wrong Publish button caption under some configurations
* Fixed : PHP Warning on Planner Import

= [1.0.6.1] - Unreleased =
* Compat : Permissions Pro - Status-specific editing access was not applied under some configurations
* Fixed : PHP Warning on user edit

= [1.0.6] - 17 Jan 2024 =
* Fixed : Post permalink for new posts defaulted to plain format regardless of permalink settings
* Fixed : Author could not change permalink
* Fixed : Default statuses did not show post count on Posts / Pages screen
* Fixed : PHP Warning on Edit Status screen
           
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

= [1.0.3.3] - Unreleased =
* Fixed : Colors were not displayed on Statuses management screen
* Change : Include default alternate workflow statuses: Deferred, Needs Work, Rejected
* Change : Include a sample alternate workflow (disabled by default): Committee, Committee Review, Committee Progress, Committee Approved
* Change : Recaption section titles on Statuses screen

= [1.0.3.2] - Unreleased =
* Change : PublishPress Planner import put some statuses into wrong section

= [1.0.3.1] - Unreleased =
* Change : PublishPress Planner import will execute again if Planner is re-activated and statuses added or modified

= [1.0.3] - Unreleased =
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
* Initial public release
* Change : Don't allow pre-publish checks to be disabled (unless forced by constant)

= [1.0.2.2] - 20 Dec 2023 =
* GitHub release
* Change : In Workflow (Pre-Publish) panel, display selectable radio option for next status even if not defaulting to it
* Change : Force usage of Pre-Publish panel (unless disabled by constant)
* Change : New plugin setting "De-clutter status dropdown by hiding statuses outside current branch"; no longer do this by default
* Fixed : Explicitly selected Pending Review status did not save correctly (since 1.0.2.1)
* Fixed : Classic Editor - Visibility selector was missing
* Fixed : Classic Editor - Explicit selection of Published status was ignored if using Default to Next Status mode
* Fixed : Classic Editor - Numerous captioning and display toggle issues in post publish metabox

= [1.0.2.1] - 19 Dec 2023 =
* GitHub release
* Fixed : Non-Administrator login caused Auto Draft publication
* Fixed : Pending status draggable to Disabled even though disabling is prevented
* Fixed : Edit Status - First update overrides Roles selection with defaults
* Fixed : Non-Administrator login causes Auto Draft publication
* Fixed : Safari - Post Status dropdown shows a blank item
* Fixed : Permissions Pro - Visibility Status button, form displayed without required Permissions Pro module
* Fixed : Permissions Pro - Disabled Visibility Statuses still available

= [1.0.2] - 13 Dec 2023 =
* GitHub release
* Fixed : Redirect back to Planner Calendar settings after editing a status
* Fixed : Statuses Admin UI - Minor styling fix for tabs
* Fixed (Pro) : Visibility Statuses - workflow statuses filtering interfered with selection in some cases
* Change (Pro) : Visibility Statuses - allow selection of Post Types in Edit Status screen
* Compat : Permissions / Capabilities - Avoid redundant execution of status capabilities update handler

= [1.0.1] - 17 Oct 2023 =
* GitHub release
* Fixed : If running without Permissions Pro, users who cannot set a status were not blocked from editing or deleting posts of that status
* Fixed : Capabilities Pro integration - Typo in PublishPress Statuses tab caption
* Code : Improved scan results

= [1.0.0] - 10 Oct 2023 =
* Initial wordpress.org submission
