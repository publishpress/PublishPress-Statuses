jQuery(document).ready(function($){var __=wp.i18n.__;var ppCurrentStatus='';var ppLastStatus=false;ppObjEdit.publishCaptionCurrent=ppObjEdit.publish;function PP_RecaptionButton(btnName,btnSelector,btnCaption){if(ppObjEdit.disableRecaption||wp.data.select('core/editor').isSavingPost()){return;}
var node=$(btnSelector);var ppClass;var hideClass;if('button.editor-post-publish-button'==btnSelector){ppClass='presspermit-editor-button';}else{ppClass='presspermit-editor-toggle';}
if($(btnSelector).length&&btnCaption&&(btnCaption!=$('span.'+ppClass+' button').html()||!$('span.'+ppClass+':visible').length)){if($(btnSelector).html()==ppObjEdit.submitRevisionCaption){return;}
$('span.presspermit-editor-toggle').remove();if(!$('div.editor-post-publish-panel__prepublish').length){$('span.presspermit-editor-button').remove();}
if((ppClass=='presspermit-editor-button')&&$('div.editor-post-publish-panel__prepublish').length&&$('span.'+ppClass+' button').length){$('span.'+ppClass+' button').html(btnCaption).show();}else{$('.presspermit-editor-hidden').not($(btnSelector)).show();if('button.editor-post-publish-button'==btnSelector){hideClass='presspermit-editor-hidden presspermit-editor-button-hidden';}else{hideClass='presspermit-editor-hidden presspermit-editor-toggle-hidden';}
node.addClass(hideClass).hide().css('z-index',-999);node.after('<span class="'+ppClass+'">'+node.clone().css('z-index',0).removeClass(hideClass).removeClass('editor-post-publish-button').removeAttr('aria-disabled').css('position','relative').css('background-color','var(--wp-admin-theme-color)').show().html(btnCaption).wrap('<span>').parent().html()+'</span>');if((typeof ppObjEdit['isGutenbergLegacy']!=undefined)&&ppObjEdit.isGutenbergLegacy){node.not('.editor-post-publish-panel__toggle').addClass(hideClass).css('background-color','inherit').css('position','fixed').attr('aria-disabled',true);}else{if('button.editor-post-publish-button'==btnSelector){if($('.presspermit-save-button:visible').length||$('div.editor-post-publish-panel__content:visible').length||($('.presspermit-editor-button button:visible').length&&$('.publishpress-extended-post-status select:visible').length)){node.not('.editor-post-publish-panel__toggle').addClass(hideClass).css('background-color','inherit').css('position','fixed').attr('aria-disabled',true);}}}}}
PP_InitializeStatuses();}
function PP_SetPublishButtonCaption(caption,waitForSaveDraftButton){if((ppObjEdit.publishCaptionCurrent==ppObjEdit.saveDraftCaption)||wp.data.select('core/editor').isSavingPost()){return;}
if(caption==''&&(typeof ppObjEdit['publishCaptionCurrent']!=undefined)){caption=ppObjEdit.publishCaptionCurrent;}else{ppObjEdit.publishCaptionCurrent=caption;}
if(typeof waitForSaveDraftButton=='undefined'){waitForSaveDraftButton=false;}
if((!waitForSaveDraftButton||($('button.editor-post-save-draft').filter(':visible').length||!$('.is-saving').length))&&$('button.editor-post-publish-button').length){PP_RecaptionButton('publish','button.editor-post-publish-button',caption);$('span.presspermit-editor-button button').removeAttr('aria-disabled');}else{var RecaptionInterval=setInterval(WaitForRecaption,100);var RecaptionTimeout=setTimeout(function(){clearInterval(RecaptionInterval);},20000);function WaitForRecaption(){if(!waitForSaveDraftButton||$('button.editor-post-save-draft').filter(':visible').length||!$('.is-saving').length){clearInterval(RecaptionInterval);clearTimeout(RecaptionTimeout);PP_RecaptionButton('publish','button.editor-post-publish-button',caption);$('.publishpress-extended-post-status-note').hide();$('span.presspermit-editor-button button').removeAttr('aria-disabled');}}}}
var PP_InitializeBlockEditorModifications=function(forceRefresh){if(ppObjEdit.hidePending){var wp_i8n_helper=window["wp"]["i18n"];if(typeof wp_i8n_helper!='undefined'){var pendingCaption=(0,wp_i8n_helper.__)('Pending review');if(pendingCaption){$('input.components-checkbox-control__input').closest('div.components-base-control__field').find('label:contains("")').closest('div.components-panel__row').hide();}}}
if((typeof forceRefresh!="undefined"&&forceRefresh)||(($('button.editor-post-publish-button').length||$('button.editor-post-publish-panel__toggle').length)&&($('button.editor-post-save-draft').length||($('div.publishpress-extended-post-status select option[value="_pending"]').length&&('pending'==$('div.publishpress-extended-post-status select').val()||'_pending'==$('div.publishpress-extended-post-status select').val()))))||((typeof window.PPCustomStatuses!='undefined')&&(typeof window.PPCustomStatuses['isRevision']!='undefined')&&(window.PPCustomStatuses.isRevision))){clearInterval(initInterval);initInterval=null;if($('button.editor-post-publish-panel__toggle').length){if(typeof ppObjEdit.prePublish!='undefined'&&ppObjEdit.prePublish){PP_RecaptionButton('prePublish','button.editor-post-publish-panel__toggle',ppObjEdit.prePublish);}
$(document).on('click','button.editor-post-publish-panel__toggle,span.pp-recaption-prepublish-button',function(){PP_SetPublishButtonCaption('',false);});}else{PP_SetPublishButtonCaption(ppObjEdit.publish,false);}}}
var initInterval=setInterval(PP_InitializeBlockEditorModifications,50);setInterval(function(){if($('span.presspermit-editor-button button.is-busy').length){let saving=wp.data.select('core/editor').isSavingPost();if(!saving){$('span.presspermit-editor-button button.is-busy').removeClass('is-busy');}}},200);var ppLastPublishCaption='';setInterval(function(){if(ppObjEdit.moveParentUI){$('div.editor-post-panel__row-label').each(function(i,e){if($(e).html()==ppObjEdit.parentLabel){$(e).closest('div.editor-post-panel__row').insertAfter($('div.editor-post-panel__row-label:contains('+ppObjEdit.publishLabel+')').closest('div.editor-post-panel__row').next());}});}
if($('div.editor-post-publish-panel__header-cancel-button').length){PP_SetPublishButtonCaption(ppObjEdit.publish,false);}
if(ppObjEdit.workflowSequence&&!wp.data.select('core/editor').isSavingPost()){let status=wp.data.select('core/editor').getEditedPostAttribute('status');if(ppObjEdit.publish!=ppLastPublishCaption){if(-1!==PPCustomStatuses.publishedStatuses.indexOf(status)){ppObjEdit.publish=ppObjEdit.update;ppObjEdit.saveAs='';}else{if(status==ppObjEdit.maxStatus){ppObjEdit.publish=ppObjEdit.update;ppObjEdit.saveAs=ppObjEdit.update;}else{if($('button.editor-post-publish-panel__toggle').length){if(typeof ppObjEdit.prePublish!='undefined'&&ppObjEdit.prePublish&&($('button.editor-post-publish-panel__toggle').html()!=__('Schedule…'))){var pendingStatusArr=new Array('pending','_pending');if(pendingStatusArr.indexOf(status)!=-1){PP_SetPublishButtonCaption(ppObjEdit.publish,false);}else{PP_RecaptionButton('prePublish','button.editor-post-publish-panel__toggle',ppObjEdit.prePublish);}}}else{PP_SetPublishButtonCaption(ppObjEdit.publish,false);}}}
ppLastStatus=status;ppLastPublishCaption=ppObjEdit.publish;if(ppObjEdit.publishCaptionCurrent!=ppObjEdit.publish){setTimeout(function(){PP_InitializeBlockEditorModifications(true);},100);}
ppObjEdit.publishCaptionCurrent=ppObjEdit.publish;}}},500);var PP_InitializeStatuses=function(){if($('div.publishpress-extended-post-status select').length){clearInterval(initStatusInterval);if($('div.publishpress-extended-post-status select option[value="_pending"]').length){if($('div.publishpress-extended-post-status select').val()=='pending'){$('div.publishpress-extended-post-status select').val('_pending');}
$('div.publishpress-extended-post-status select > option[value="pending"]').html('').hide();$(document).on('click','div.publishpress-extended-post-status select option[value="pending"]',function(){$('div.publishpress-extended-post-status select').val('_pending');});}
ppCurrentStatus=$('div.publishpress-extended-post-status select').val();}}
var initStatusInterval=setInterval(PP_InitializeStatuses,50);setInterval(function(){if('pending'==$('div.publishpress-extended-post-status select').val()){$('div.publishpress-extended-post-status select').val('_pending');}},200);$(document).on('click','div.publishpress-extended-post-status select',function(){$(document).on('click','button.editor-post-save-draft',function(){ppObjEdit.publishCaptionCurrent=ppObjEdit.publish;});});$(document).on('change','div.publishpress-extended-post-status select',function(){$('#ppcs_save_draft_label').hide();});$(document).on('click','div.publishpress-extended-post-status select',function(){if($('div.publishpress-extended-post-status select option[value="_pending"]').length&&$('div.publishpress-extended-post-status select option[value="pending"]').length){$('div.publishpress-extended-post-status select option[value="pending"]').hide();}});$(document).on('click','span.presspermit-editor-button button',function(){if(!wp.data.select('core/editor').isSavingPost()&&!$('span.presspermit-editor-button button').attr('aria-disabled')){$(this).parent().prev('button.editor-post-publish-button').trigger('click').hide();}});$(document).on('click','span.presspermit-editor-toggle button',function(){if(!wp.data.select('core/editor').isSavingPost()&&!$('span.presspermit-editor-toggle button').attr('aria-disabled')){$(this).parent().prev('button.editor-post-publish-panel__toggle').trigger('click').hide();}});let ppPostSavingDone=function(){$('div.publishpress-extended-post-status select').removeAttr('locked');ppcsEnablePostUpdate();let status=wp.data.select('core/editor').getEditedPostAttribute(PPCustomStatuses.statusRestProperty);var redirectProp='redirectURL'+status;if(typeof ppObjEdit[redirectProp]!=undefined){$(location).attr("href",ppObjEdit[redirectProp]);}else{ppCurrentStatus=status;}
ppLastStatus=false;setTimeout(function(){ppLastStatus=false;PP_RecaptionButton('prePublish','button.editor-post-publish-panel__toggle',ppObjEdit.prePublish);PP_SetPublishButtonCaption(ppObjEdit.publish,false);setTimeout(function(){PPCS_RecaptionOnDisplay('');},500);ppEnablePostUpdate();},500);querySelectableStatuses(status);ppLoggedPostSave=false;}
var ppDisablePostUpdate=function ppDisablePostUpdate(){$('span.presspermit-editor-button button').attr('aria-disabled',true);$('div.publishpress-extended-post-status select').attr('disabled',true);}
var ppEnablePostUpdate=function ppEnablePostUpdate(){$('div.publishpress-extended-post-status select').removeAttr('disabled');}
var ppLoggedPostSave=false;let ppPostSaveCheck=function(){let saving=wp.data.select('core/editor').isSavingPost();if(saving){if(wp.data.select('core/editor').isAutosavingPost()){return;}
if(!ppLoggedPostSave){ppLoggedPostSave=true;var redirectCheckSaveDoneInterval=setInterval(function(){let saving=wp.data.select('core/editor').isSavingPost();if(!saving){clearInterval(redirectCheckSaveDoneInterval);ppPostSavingDone();}},50);ppCurrentStatus=wp.data.select('core/editor').getEditedPostAttribute('status');$('div.publishpress-extended-post-status select').attr('locked',true);ppDisablePostUpdate();$('span.presspermit-editor-toggle button').attr('aria-disabled',true);}}else{ppLoggedPostSave=false;}}
$(document).on('click','button.editor-post-publish-button:not(.presspermit-editor-hidden),button.editor-post-save-draft',function(){ppPostSaveCheck();});var redirectCheckSaveInterval=setInterval(function(){ppPostSaveCheck();},100);$(document).on('click','button.editor-post-publish-button',function(){if($('div.editor-post-publish-panel__prepublish').length||$('button.editor-post-publish-panel__toggle').length){$('span.presspermit-editor-button button').remove();var RvyRecaptionPrepub=function(){if($('button.editor-post-publish-panel__toggle').not('[aria-disabled="true"]').length){clearInterval(RvyRecaptionPrepubInterval);PP_RecaptionButton('prePublish','button.editor-post-publish-panel__toggle',ppObjEdit.prePublish);}else{if($('button.editor-post-publish-panel__toggle').length){if(!$('span.presspermit-editor-toggle').length){PP_RecaptionButton('prePublish','button.editor-post-publish-panel__toggle',ppObjEdit.prePublish);}}else{if(!$('span.presspermit-editor-button button').length){PP_SetPublishButtonCaption(ppObjEdit.publish,false);$('span.presspermit-editor-button button').attr('aria-disabled','true');}}}}
var RvyRecaptionPrepubInterval=setInterval(RvyRecaptionPrepub,100);}else{PP_SetPublishButtonCaption(ppObjEdit.saveAs,true);$('span.presspermit-editor-button button').attr('aria-disabled','true');}
setTimeout(function(){PP_SetPublishButtonCaption(ppObjEdit.saveAs,true);},100);});$(document).on('click','div.editor-post-publish-panel__header-cancel-button button',function(){setTimeout(function(){$('button.editor-post-publish-panel__toggle').removeClass('presspermit-editor-hidden').css('z-index',1);PP_RecaptionButton('prePublish','button.editor-post-publish-panel__toggle',ppObjEdit.prePublish);PPCS_RecaptionOnDisplay('');},100);});$(document).on('click','button.editor-post-save-draft',function(){$('span.presspermit-editor-button button').attr('aria-disabled','true');setTimeout(function(){PP_SetPublishButtonCaption(ppObjEdit.publish,true);},50);});$(document).on('click','div.editor-post-publish-panel__header button.components-icon-button',function(){setTimeout(function(){PP_InitializeBlockEditorModifications();},100);});var DetectPublishOptionsDivClosureInterval='';var DetectPublishOptionsDiv=function(){if($('div.components-modal__header').length){clearInterval(DetectPublishOptionsDivInterval);var DetectPublishOptionsClosure=function(){if(!$('div.components-modal__header').length){clearInterval(DetectPublishOptionsDivClosureInterval);$('span.presspermit-editor-button').remove();$('span.presspermit-editor-toggle').remove();$('.presspermit-editor-hidden').show();PP_RecaptionButton('prePublish','button.editor-post-publish-panel__toggle',ppObjEdit.prePublish);PP_SetPublishButtonCaption(ppObjEdit.publish,true);initInterval=setInterval(PP_InitializeBlockEditorModifications,50);DetectPublishOptionsDivInterval=setInterval(DetectPublishOptionsDiv,1000);}}
DetectPublishOptionsDivClosureInterval=setInterval(DetectPublishOptionsClosure,200);}}
var DetectPublishOptionsDivInterval=setInterval(DetectPublishOptionsDiv,1000);});