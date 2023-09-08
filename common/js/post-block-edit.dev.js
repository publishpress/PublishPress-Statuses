/**
 * Block Editor Modifications for PressPermit / PublishPress
 *
 * By Kevin Behrens
 *
 * Copyright 2023, PublishPress
 */
jQuery(document).ready(function ($) {
    /***** Redirect back to edit.php if user won't be able to futher edit after changing post status *******/
    $(document).on('click', 'button.editor-post-publish-button,button.editor-post-save-draft', function () {
        var redirectCheckSaveInterval = setInterval(function () {
            let saving = wp.data.select('core/editor').isSavingPost();

            if (saving) {
                clearInterval(redirectCheckSaveInterval);

                var redirectCheckSaveDoneInterval = setInterval(function () {
                    let saving = wp.data.select('core/editor').isSavingPost();

                    if (!saving) {
                        //let goodsave = wp.data.select('core/editor').didPostSaveRequestSucceed();
                        let status = wp.data.select('core/editor').getEditedPostAttribute('status');

                        var redirectProp = 'redirectURL' + status;
                        if (typeof ppObjEdit[redirectProp] != undefined) {
                            $(location).attr("href", ppObjEdit[redirectProp]);
                        }

                        clearInterval(redirectCheckSaveDoneInterval);
                    }
                }, 50);
            }
        }, 10);
    });

    /*******************************************************************************************************/


    /******************** JQUERY SUPPORT FOR RECAPTIONING PUBLISH AND PRE-PUBLISH BUTTONS **************************/

    $(document).on('click', 'span.presspermit-editor-button button', function() {
        $(this).parent().prev('button').trigger('click').hide();
    });

    /*
     * The goal is to allow recaptioning "Publish..." to "Workflow...",  "Submit for Review" to "Submit as Pitch" etc.
     */
    function PP_RecaptionButton(btnName, btnSelector, btnCaption) {
        if (ppObjEdit.disableRecaption) {
            return;
        }

        $('span.presspermit-editor-button').remove();

        var node = $(btnSelector);

        // WP may implicitly suppress Pre-pub following status save
        if (!$(btnSelector).length && ('button.editor-post-publish-button' == btnSelector) && ppObjEdit.prePublish) {
            btnSelector = 'button.editor-post-publish-panel__toggle';
            btnCaption = ppObjEdit.prePublish;
            node = $(btnSelector);
            $(btnSelector).show();
        }

        if ($(btnSelector).length && btnCaption && (btnCaption != $(btnSelector).html() || !$('span.presspermit-editor-button:visible').length)) {
            if ($(btnSelector).html() == ppObjEdit.submitRevisionCaption) {
                return;
            }

            $('.presspermit-editor-hidden').not($(btnSelector)).show();

            node.addClass('presspermit-editor-hidden').hide().css('z-index', -999);

            node.after('<span class="presspermit-editor-button">' + node.clone().css('z-index', 0).removeClass('presspermit-editor-hidden').removeAttr('aria-disabled').css('position', 'relative').css('background-color', 'var(--wp-admin-theme-color)').show().html(btnCaption).wrap('<span>').parent().html() + '</span>');
        
            node.not('.editor-post-publish-panel__toggle').addClass('presspermit-editor-hidden').css('background-color', 'inherit').css('position', 'fixed').attr('aria-disabled', true);
        }

        PP_InitializeStatuses();
    }

    setInterval(function() {
        if ($('span.presspermit-editor-button button.is-busy').length) {
            let saving = wp.data.select('core/editor').isSavingPost();

            if (!saving) {
                $('span.presspermit-editor-button button.is-busy').removeClass('is-busy');
            }
        }
    }, 200);

    // Update main publish ("Publish" / "Submit Pending") button width and span caption
    function PP_SetPublishButtonCaption(caption, waitForSaveDraftButton) {
        if (ppObjEdit.publishCaptionCurrent == ppObjEdit.saveDraftCaption) {
            return;
        }

        if (caption == '' && (typeof ppObjEdit['publishCaptionCurrent'] != undefined)) {
            caption = ppObjEdit.publishCaptionCurrent;
        } else {
            ppObjEdit.publishCaptionCurrent = caption;
        }

        if (typeof waitForSaveDraftButton == 'undefined') {
            waitForSaveDraftButton = false;
        }

        if ((!waitForSaveDraftButton 
        || ($('button.editor-post-save-draft').filter(':visible').length || !$('.is-saving').length)) 
        && $('button.editor-post-publish-button').length) {  // indicates save operation (or return from Pre-Publish) is done
            PP_RecaptionButton('publish', 'button.editor-post-publish-button', caption);
            $('span.presspermit-editor-button button').removeAttr('aria-disabled');

        } else {
            var RecaptionInterval = setInterval(WaitForRecaption, 100);
            var RecaptionTimeout = setTimeout(function () {
                clearInterval(RecaptionInterval);
            }, 20000);

            function WaitForRecaption() {
                if (!waitForSaveDraftButton || $('button.editor-post-save-draft').filter(':visible').length || !$('.is-saving').length) { // indicates save operation (or return from Pre-Publish) is done
                    //if ($('button.editor-post-publish-button').length) {			  // indicates Pre-Publish is disabled
                        clearInterval(RecaptionInterval);
                        clearTimeout(RecaptionTimeout);

                        // will set Pre-pub button instead when applicable
                        PP_RecaptionButton('publish', 'button.editor-post-publish-button', caption);

                        $('.publishpress-extended-post-status-note').hide();
                    //} else {
                    //    if (waitForSaveDraftButton) {  // case of execution following publish click with Pre-Publish active
                    //        clearInterval(RecaptionInterval);
                    //        clearTimeout(RecaptionTimeout);
                    //    }
                    //}

                    $('span.presspermit-editor-button button').removeAttr('aria-disabled');
                }
            }
        }
    }

    var __ = wp.i18n.__;

    // Force button copies to be refreshed following modal settings window access
    var DetectPublishOptionsDivClosureInterval = '';
    var DetectPublishOptionsDiv = function () {
        if ($('div.components-modal__header').length) {
            clearInterval(DetectPublishOptionsDivInterval);

            var DetectPublishOptionsClosure = function () {
                if (!$('div.components-modal__header').length) {
                    clearInterval(DetectPublishOptionsDivClosureInterval);

                    $('span.presspermit-editor-button').remove();
                    $('.presspermit-editor-hidden').show();

                    initInterval = setInterval(PP_InitializeBlockEditorModifications, 50);
                    DetectPublishOptionsDivInterval = setInterval(DetectPublishOptionsDiv, 1000);
                }
            }
            DetectPublishOptionsDivClosureInterval = setInterval(DetectPublishOptionsClosure, 200);
        }
    }
    var DetectPublishOptionsDivInterval = setInterval(DetectPublishOptionsDiv, 1000);
    /*****************************************************************************************************************/

    /************* RECAPTION PRE-PUBLISH AND PUBLISH BUTTONS ****************/
    ppObjEdit.publishCaptionCurrent = ppObjEdit.publish;

    $(document).on('click', 'div.editor-post-publish-panel__header-cancel-button button', function() {
        setTimeout(function () {
            $('button.editor-post-publish-panel__toggle').removeClass('presspermit-editor-hidden').css('z-index', 1);
            PP_RecaptionButton('prePublish', 'button.editor-post-publish-panel__toggle', ppObjEdit.prePublish);
        }, 100);
    });

    // Initialization operations to perform once React loads the relevant elements
    var PP_InitializeBlockEditorModifications = function (forceRefresh) {
        if ((typeof forceRefresh != "undefined" && forceRefresh) || (($('button.editor-post-publish-button').length || $('button.editor-post-publish-panel__toggle').length) 
        && ($('button.editor-post-save-draft').length || ($('div.publishpress-extended-post-status select option[value="_pending"]').length && ('pending' == $('div.publishpress-extended-post-status select').val() || '_pending' == $('div.publishpress-extended-post-status select').val())))
        )) {
            clearInterval(initInterval);
            initInterval = null;

            if ($('button.editor-post-publish-panel__toggle').length) {
                if (typeof ppObjEdit.prePublish != 'undefined' && ppObjEdit.prePublish && ($('button.editor-post-publish-panel__toggle').html() != __('Schedule…'))) {
                    PP_RecaptionButton('prePublish', 'button.editor-post-publish-panel__toggle', ppObjEdit.prePublish);
                }

                // Presence of pre-publish button means publish button is not loaded yet. Start looking for it once Pre-Publish button is clicked.
                $(document).on('click', 'button.editor-post-publish-panel__toggle,span.pp-recaption-prepublish-button', function () {
                    PP_SetPublishButtonCaption('', false); // nullstring: set caption to value queued in ppObjEdit.publishCaptionCurrent 
                });
            } else {
                PP_SetPublishButtonCaption(ppObjEdit.publish, false);
            }

            //PublishPressQueueCorrectDefaultSaveAsCaption();
        }
    }
    var initInterval = setInterval(PP_InitializeBlockEditorModifications, 50);

    var rvyLastStatus = false;

    setInterval(
        function() {
            if ($('div.editor-post-publish-panel__header-cancel-button').length) {
                PP_SetPublishButtonCaption(ppObjEdit.publish, false);
            }

            if (ppObjEdit.workflowSequence && !wp.data.select('core/editor').isSavingPost()) {
                let status = wp.data.select('core/editor').getEditedPostAttribute('status');

                if (status != rvyLastStatus) {
                    if (ppObjEdit.advanceStatus != '') {
                        if (status == ppObjEdit.maxStatus) {
                            ppObjEdit.publish = ppObjEdit.update;
                            ppObjEdit.saveAs = ppObjEdit.update;
                        } else {
                            ppObjEdit.publish = ppObjEdit.advanceStatus;
                            //ppObjEdit.prePublish = ppObjEdit.advanceStatus;
                            ppObjEdit.publishCaptionCurrent = ppObjEdit.advanceStatus;
                            ppObjEdit.saveAs = ppObjEdit.advanceStatus;

                            if ($('button.editor-post-publish-panel__toggle').length) {
                                if (typeof ppObjEdit.prePublish != 'undefined' && ppObjEdit.prePublish && ($('button.editor-post-publish-panel__toggle').html() != __('Schedule…'))) {
                                    PP_RecaptionButton('prePublish', 'button.editor-post-publish-panel__toggle', ppObjEdit.prePublish);
                                }
                            } else {
                                PP_SetPublishButtonCaption(ppObjEdit.publish, false);
                            }
                        }
                    }

                    if (ppObjEdit.publishCaptionCurrent != ppObjEdit.publish) {
                        setTimeout(function () {
                            PP_InitializeBlockEditorModifications();
                        }, 100);

                        // Keep refreshing the button caption for 10 seconds  // @todo where/why is previous caption re-applied after initial change?
                        var intRvyRefreshPublishCaption = setInterval(function() {PP_InitializeBlockEditorModifications(true);}, 1000);
                        setTimeout(function() {clearInterval(intRvyRefreshPublishCaption)}, 10000);
                    }

                    ppObjEdit.publishCaptionCurrent = ppObjEdit.publish;

                    rvyLastStatus = status;
                }
            }
        },
        500
    );

    var PP_InitializeStatuses = function () {
        if ($('div.publishpress-extended-post-status select').length) {
            clearInterval(initStatusInterval);

            // Users without the publish capability get an alternate 'pending' option item 
            // to allow a "Save as Pending Review" button which does not trigger automatic workflow status progression.
            if ($('div.publishpress-extended-post-status select option[value="_pending"]').length) {
                if ($('div.publishpress-extended-post-status select').val() == 'pending') {
                    $('div.publishpress-extended-post-status select').val('_pending');
                }

                // Blank option for Safari, which cannot hide it
                $('div.publishpress-extended-post-status select option[value="pending"]').html('').hide();

                $(document).on('click', 'div.publishpress-extended-post-status select option[value="pending"]', function() {
                    $('div.publishpress-extended-post-status select').val('_pending');
                });
            }
        }
    }
    var initStatusInterval = setInterval(PP_InitializeStatuses, 50);

    // Fallback safeguard against redundant visible Pending options
    $(document).on('click', 'div.publishpress-extended-post-status select', function() {
        if ($('div.publishpress-extended-post-status select option[value="_pending"]').length && $('div.publishpress-extended-post-status select option[value="pending"]').length) {
            $('div.publishpress-extended-post-status select option[value="pending"]').hide();
        }
    });

    setInterval(function() {
        // Pending Review checkbox selects "pending" option
        if ('pending' == $('div.publishpress-extended-post-status select').val()) {
            $('div.publishpress-extended-post-status select').val('_pending');
        }
    }, 200);

    $(document).on('click', 'div.editor-post-publish-panel__header button.components-icon-button', function() {
        setTimeout(function () {
            PP_InitializeBlockEditorModifications();
        }, 100);
    });

    // If Publish button is clicked, current post status will be set to [user's next/max status progression]
    // So set Publish button caption to "Save As %s" to show that no further progression is needed / offered.
    $(document).on('click', 'button.editor-post-publish-button', function () {
        if ($('div.editor-post-publish-panel__prepublish').length || $('button.editor-post-publish-panel__toggle').length) {
            $('span.presspermit-editor-button button').remove();
            
            var RvyRecaptionPrepub = function () {
                if ($('button.editor-post-publish-panel__toggle').not('[aria-disabled="true"]').length) {
                    clearInterval(RvyRecaptionPrepubInterval);

                    PP_RecaptionButton('prePublish', 'button.editor-post-publish-panel__toggle', ppObjEdit.prePublish);
                } else {
                    if ($('button.editor-post-publish-panel__toggle').length) {
                        if (!$('span.presspermit-editor-button').length) {
                            PP_RecaptionButton('prePublish', 'button.editor-post-publish-panel__toggle', ppObjEdit.prePublish);
                        }
                    } else {
                        if (!$('span.presspermit-editor-button button').length) {
                            PP_SetPublishButtonCaption(ppObjEdit.publish, false);
                            $('span.presspermit-editor-button button').attr('aria-disabled', 'true');
                        }
                    }
                }
            }
            var RvyRecaptionPrepubInterval = setInterval(RvyRecaptionPrepub, 100);
        } else {
            PP_SetPublishButtonCaption(ppObjEdit.saveAs, true);
            $('span.presspermit-editor-button button').attr('aria-disabled', 'true');
        }

        // Wait for Save Draft button to reappear; this will have no effect on Publish Button if Pre-Publish is enabled (but will update ppObjEdit property for next button refresh)
        setTimeout(function () {
            PP_SetPublishButtonCaption(ppObjEdit.saveAs, true);
        }, 100);
    });

    $(document).on('click', 'button.editor-post-save-draft', function () {
        $('span.presspermit-editor-button button').attr('aria-disabled', 'true');

        // Wait for Save Draft button; this will have no effect on Publish Button if Pre-Publish is enabled 
        // (but will clear disabled attribute on current button and update ppObjEdit property with current button caption)
        setTimeout(function () {
            PP_SetPublishButtonCaption(ppObjEdit.publish, true);
        }, 50);
    });

    // If the status dropdown is changed, current post status will potentially be different from [user's next/max workflow status progression]
    // So make any subsequent "Save As" link click cause the Submit button to be recaptioned to "Submit as %s" (instead of "Save As %s")
    // to show that a progression is offered.
    $(document).on('click', 'div.publishpress-extended-post-status select', function () {
        $(document).on('click', 'button.editor-post-save-draft', function () {
            ppObjEdit.publishCaptionCurrent = ppObjEdit.publish;
        });
    });
});
