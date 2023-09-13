jQuery(document).ready(function($){$(document).on('click','button.editor-post-publish-button,button.editor-post-save-draft',function(){var redirectCheckSaveInterval=setInterval(function(){let saving=wp.data.select('core/editor').isSavingPost();if(saving){clearInterval(redirectCheckSaveInterval);var redirectCheckSaveDoneInterval=setInterval(function(){let saving=wp.data.select('core/editor').isSavingPost();if(!saving){let status=wp.data.select('core/editor').getEditedPostAttribute('status');var redirectProp='redirectURL'+status;if(typeof ppObjEdit[redirectProp]!=undefined){$(location).attr("href",ppObjEdit[redirectProp]);}
clearInterval(redirectCheckSaveDoneInterval);}},50);}},10);});$(document).on('click','span.presspermit-editor-button button',function(){$(this).parent().prev('button').trigger('click').hide();});function PP_RecaptionButton(btnName,btnSelector,btnCaption){if(ppObjEdit.disableRecaption){return;}
$('span.presspermit-editor-button').remove();var node=$(btnSelector);if(!$(btnSelector).length&&('button.editor-post-publish-button'==btnSelector)&&ppObjEdit.prePublish){btnSelector='button.editor-post-publish-panel__toggle';btnCaption=ppObjEdit.prePublish;node=$(btnSelector);$(btnSelector).show();}
if($(btnSelector).length&&btnCaption&&(btnCaption!=$(btnSelector).html()||!$('span.presspermit-editor-button:visible').length)){if($(btnSelector).html()==ppObjEdit.submitRevisionCaption){return;}
$('.presspermit-editor-hidden').not($(btnSelector)).show();node.addClass('presspermit-editor-hidden').hide().css('z-index',-999);node.after('<span class="presspermit-editor-button">'+node.clone().css('z-index',0).removeClass('presspermit-editor-hidden').removeAttr('aria-disabled').css('position','relative').css('background-color','var(--wp-admin-theme-color)').show().html(btnCaption).wrap('<span>').parent().html()+'</span>');node.not('.editor-post-publish-panel__toggle').addClass('presspermit-editor-hidden').css('background-color','inherit').css('position','fixed').attr('aria-disabled',true);}
PP_InitializeStatuses();}
setInterval(function(){if($('span.presspermit-editor-button button.is-busy').length){let saving=wp.data.select('core/editor').isSavingPost();if(!saving){$('span.presspermit-editor-button button.is-busy').removeClass('is-busy');}}},200);function PP_SetPublishButtonCaption(caption,waitForSaveDraftButton){if(ppObjEdit.publishCaptionCurrent==ppObjEdit.saveDraftCaption){return;}
if(caption==''&&(typeof ppObjEdit['publishCaptionCurrent']!=undefined)){caption=ppObjEdit.publishCaptionCurrent;}else{ppObjEdit.publishCaptionCurrent=caption;}
if(typeof waitForSaveDraftButton=='undefined'){waitForSaveDraftButton=false;}
if((!waitForSaveDraftButton||($('button.editor-post-save-draft').filter(':visible').length||!$('.is-saving').length))&&$('button.editor-post-publish-button').length){PP_RecaptionButton('publish','button.editor-post-publish-button',caption);$('span.presspermit-editor-button button').removeAttr('aria-disabled');}else{var RecaptionInterval=setInterval(WaitForRecaption,100);var RecaptionTimeout=setTimeout(function(){clearInterval(RecaptionInterval);},20000);function WaitForRecaption(){if(!waitForSaveDraftButton||$('button.editor-post-save-draft').filter(':visible').length||!$('.is-saving').length){clearInterval(RecaptionInterval);clearTimeout(RecaptionTimeout);PP_RecaptionButton('publish','button.editor-post-publish-button',caption);$('.publishpress-extended-post-status-note').hide();$('span.presspermit-editor-button button').removeAttr('aria-disabled');}}}}
var __=wp.i18n.__;var DetectPublishOptionsDivClosureInterval='';var DetectPublishOptionsDiv=function(){if($('div.components-modal__header').length){clearInterval(DetectPublishOptionsDivInterval);var DetectPublishOptionsClosure=function(){if(!$('div.components-modal__header').length){clearInterval(DetectPublishOptionsDivClosureInterval);$('span.presspermit-editor-button').remove();$('.presspermit-editor-hidden').show();initInterval=setInterval(PP_InitializeBlockEditorModifications,50);DetectPublishOptionsDivInterval=setInterval(DetectPublishOptionsDiv,1000);}}
DetectPublishOptionsDivClosureInterval=setInterval(DetectPublishOptionsClosure,200);}}
var DetectPublishOptionsDivInterval=setInterval(DetectPublishOptionsDiv,1000);ppObjEdit.publishCaptionCurrent=ppObjEdit.publish;$(document).on('click','div.editor-post-publish-panel__header-cancel-button button',function(){setTimeout(function(){$('button.editor-post-publish-panel__toggle').removeClass('presspermit-editor-hidden').css('z-index',1);PP_RecaptionButton('prePublish','button.editor-post-publish-panel__toggle',ppObjEdit.prePublish);},100);});var PP_InitializeBlockEditorModifications=function(forceRefresh){if((typeof forceRefresh!="undefined"&&forceRefresh)||(($('button.editor-post-publish-button').length||$('button.editor-post-publish-panel__toggle').length)&&($('button.editor-post-save-draft').length||$('button.editor-post-saved-state').length||($('div.publishpress-extended-post-status select option[value="_pending"]').length&&('pending'==$('div.publishpress-extended-post-status select').val()||'_pending'==$('div.publishpress-extended-post-status select').val()))))){clearInterval(initInterval);initInterval=null;if($('button.editor-post-publish-panel__toggle').length){if(typeof ppObjEdit.prePublish!='undefined'&&ppObjEdit.prePublish&&($('button.editor-post-publish-panel__toggle').html()!=__('Schedule…'))){PP_RecaptionButton('prePublish','button.editor-post-publish-panel__toggle',ppObjEdit.prePublish);}
$(document).on('click','button.editor-post-publish-panel__toggle,span.pp-recaption-prepublish-button',function(){PP_SetPublishButtonCaption('',false);});}else{PP_SetPublishButtonCaption(ppObjEdit.publish,false);}}}
var initInterval=setInterval(PP_InitializeBlockEditorModifications,50);var rvyLastStatus=false;setInterval(function(){if($('div.editor-post-publish-panel__header-cancel-button').length){PP_SetPublishButtonCaption(ppObjEdit.publish,false);}
if(ppObjEdit.workflowSequence&&!wp.data.select('core/editor').isSavingPost()){let status=wp.data.select('core/editor').getEditedPostAttribute('status');if(status!=rvyLastStatus){if(ppObjEdit.advanceStatus!=''){if(status==ppObjEdit.maxStatus){ppObjEdit.publish=ppObjEdit.update;ppObjEdit.saveAs=ppObjEdit.update;}else{ppObjEdit.publish=ppObjEdit.advanceStatus;ppObjEdit.publishCaptionCurrent=ppObjEdit.advanceStatus;ppObjEdit.saveAs=ppObjEdit.advanceStatus;if($('button.editor-post-publish-panel__toggle').length){if(typeof ppObjEdit.prePublish!='undefined'&&ppObjEdit.prePublish&&($('button.editor-post-publish-panel__toggle').html()!=__('Schedule…'))){PP_RecaptionButton('prePublish','button.editor-post-publish-panel__toggle',ppObjEdit.prePublish);}}else{PP_SetPublishButtonCaption(ppObjEdit.publish,false);}}}
if(ppObjEdit.publishCaptionCurrent!=ppObjEdit.publish){setTimeout(function(){PP_InitializeBlockEditorModifications();},100);var intRvyRefreshPublishCaption=setInterval(function(){PP_InitializeBlockEditorModifications(true);},1000);setTimeout(function(){clearInterval(intRvyRefreshPublishCaption)},10000);}
ppObjEdit.publishCaptionCurrent=ppObjEdit.publish;rvyLastStatus=status;}}},500);var PP_InitializeStatuses=function(){if($('div.publishpress-extended-post-status select').length){clearInterval(initStatusInterval);if($('div.publishpress-extended-post-status select option[value="_pending"]').length){if($('div.publishpress-extended-post-status select').val()=='pending'){$('div.publishpress-extended-post-status select').val('_pending');}
$('div.publishpress-extended-post-status select option[value="pending"]').html('').hide();$(document).on('click','div.publishpress-extended-post-status select option[value="pending"]',function(){$('div.publishpress-extended-post-status select').val('_pending');});}}}
var initStatusInterval=setInterval(PP_InitializeStatuses,50);$(document).on('click','div.publishpress-extended-post-status select',function(){if($('div.publishpress-extended-post-status select option[value="_pending"]').length&&$('div.publishpress-extended-post-status select option[value="pending"]').length){$('div.publishpress-extended-post-status select option[value="pending"]').hide();}});setInterval(function(){if('pending'==$('div.publishpress-extended-post-status select').val()){$('div.publishpress-extended-post-status select').val('_pending');}},200);$(document).on('click','div.editor-post-publish-panel__header button.components-icon-button',function(){setTimeout(function(){PP_InitializeBlockEditorModifications();},100);});$(document).on('click','button.editor-post-publish-button',function(){if($('div.editor-post-publish-panel__prepublish').length||$('button.editor-post-publish-panel__toggle').length){$('span.presspermit-editor-button button').remove();var RvyRecaptionPrepub=function(){if($('button.editor-post-publish-panel__toggle').not('[aria-disabled="true"]').length){clearInterval(RvyRecaptionPrepubInterval);PP_RecaptionButton('prePublish','button.editor-post-publish-panel__toggle',ppObjEdit.prePublish);}else{if($('button.editor-post-publish-panel__toggle').length){if(!$('span.presspermit-editor-button').length){PP_RecaptionButton('prePublish','button.editor-post-publish-panel__toggle',ppObjEdit.prePublish);}}else{if(!$('span.presspermit-editor-button button').length){PP_SetPublishButtonCaption(ppObjEdit.publish,false);$('span.presspermit-editor-button button').attr('aria-disabled','true');}}}}
var RvyRecaptionPrepubInterval=setInterval(RvyRecaptionPrepub,100);}else{PP_SetPublishButtonCaption(ppObjEdit.saveAs,true);$('span.presspermit-editor-button button').attr('aria-disabled','true');}
setTimeout(function(){PP_SetPublishButtonCaption(ppObjEdit.saveAs,true);},100);});$(document).on('click','button.editor-post-save-draft',function(){$('span.presspermit-editor-button button').attr('aria-disabled','true');setTimeout(function(){PP_SetPublishButtonCaption(ppObjEdit.publish,true);},50);});$(document).on('click','div.publishpress-extended-post-status select',function(){$(document).on('click','button.editor-post-save-draft',function(){ppObjEdit.publishCaptionCurrent=ppObjEdit.publish;});});});