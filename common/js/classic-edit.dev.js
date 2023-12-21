jQuery(document).ready(function ($) {
    $('#submitdiv').addClass("submitdiv-pps");

    updateStatusDropdownElements();

    // Advanced Custom Fields compat
    if (typeof acf != 'undefined') {
        if (typeof acf.add_filter == 'function') { // ACF 5 API
            acf.add_filter('validation_complete', function (json, $form) {
                // remove disabled classes
                $('#post-body .submitdiv-pps').find('.disabled').removeClass('disabled');
                $('#post-body .submitdiv-pps').find('.button-disabled').removeClass('button-disabled');
                $('#post-body .submitdiv-pps').find('.button-primary-disabled').removeClass('button-primary-disabled');

                // remove spinner
                $('#post-body .submitdiv-pps .spinner').hide();

                return json;
            });
        } else {
            $(document).on('submit', '#post', function () {
                // hide ajax stuff on submit button
                if ($('#post-body .submitdiv-pps').exists()) {

                    // remove disabled classes
                    $('#post-body .submitdiv-pps').find('.disabled').removeClass('disabled');
                    $('#post-body .submitdiv-pps').find('.button-disabled').removeClass('button-disabled');
                    $('#post-body .submitdiv-pps').find('.button-primary-disabled').removeClass('button-primary-disabled');

                    // remove spinner
                    $('#post-body .submitdiv-pps .spinner').hide();
                }
            });
        }
    }
});

function updateStatusDropdownElements() {
    jQuery(document).ready(function ($) {
        var postStatus = $('#post_status'), optPublish = $('option[value=publish]', postStatus);
        var status_val = $('input:radio:checked', '#post-visibility-select').val();

        var is_private = false;
        var pvt_stati = jQuery.parseJSON(ppObjEdit.pvtStati.replace(/&quot;/g, '"'));

        $(pvt_stati).each(function (i) {
            if (pvt_stati[i].name == status_val) {
                is_private = true;
            }
        });

        if (is_private) {
            $('#publish').val(ppObjEdit.update);

            if (optPublish.length == 0) {
                postStatus.append('<option value="publish">' + ppObjEdit.privatelyPublished + '</option>');
            } else {
                optPublish.html(ppObjEdit.privatelyPublished);
            }

            $('option[value="publish"]', postStatus).prop('selected', true);
            $('.edit-post-status', '#misc-publishing-actions').hide();
        } else {
			if (postL10n.publish) {
				$('#publish').val(postL10n.publish);
			} else {
            	$('#publish').val(ppObjEdit.publish);
			}

            if ($('#original_post_status').val() == 'future') {
                if (optPublish.length) {
                    optPublish.remove();
                    postStatus.val($('#hidden_post_status').val());
                }
            } else {
                optPublish.html(ppObjEdit.published);
            }
            if (postStatus.is(':hidden'))
                $('.edit-post-status', '#misc-publishing-actions').show();
        }

        return true;
    });
}

// set "Status:" caption; show/hide Save As button and set caption
function updateStatusCaptions() {
    jQuery(document).ready(function ($) {
        postStatus = $('#post_status');
        var status_val = $('option:selected', postStatus).val();

        var status_caption = $('option:selected', postStatus).text();
        status_caption = status_caption.replace('—', '');

        if (status_caption) {
            $('#post-status-display').html(status_caption);
        }

        var status_type = '';
        var save_as = '';
        var pub_stati = jQuery.parseJSON(ppObjEdit.pubStati.replace(/&quot;/g, '"'));
        var pvt_stati = jQuery.parseJSON(ppObjEdit.pvtStati.replace(/&quot;/g, '"'));
        var mod_stati = jQuery.parseJSON(ppObjEdit.modStati.replace(/&quot;/g, '"'));

        $(mod_stati).each(function (i) {
            if (mod_stati[i].name == status_val) {
                status_type = 'moderation';
                save_as = mod_stati[i].save_as;
            }
        });

        $(pub_stati).each(function (i) {
            if (pub_stati[i].name == status_val) {
                status_type = 'public';
            }
        });

        $(pvt_stati).each(function (i) {
            if (pvt_stati[i].name == status_val) {
                status_type = 'private';
            }
        });

        $('#publish').toggle(status_val != '_public');

        switch (status_type) {
            case 'public':
            case 'private':
                $('#save-post').hide();
                break;

            case 'moderation':
                $('#save-post').show().val(save_as);
                break;

            default :
                $('#save-post').show().val(ppObjEdit.draftSaveAs);
        }
    });
}

jQuery(document).ready(function ($) {
    var stamp = $('#timestamp').html();

    if (typeof ppObjEdit != 'undefined')
        var pvt_stati = jQuery.parseJSON(ppObjEdit.pvtStati.replace(/&quot;/g, '"'));
    else
        var pvt_stati = [];

    $('.save-post-status', '#post-status-select').on('click', function (e) {
        updateStatusCaptions();
        $('#post-status-select').siblings('a.edit-post-status').show();
        e.preventDefault();
    });

    function ppUpdateText() {
        var attemptedDate, originalDate, currentDate, publishOn,
            postStatus = $('#post_status'), optPublish = $('option[value=publish]', postStatus), aa = $('#aa').val(),
            mm = $('#mm').val(), jj = $('#jj').val(), hh = $('#hh').val(), mn = $('#mn').val();

        attemptedDate = new Date(aa, mm - 1, jj, hh, mn);
        originalDate = new Date($('#hidden_aa').val(), $('#hidden_mm').val() - 1, $('#hidden_jj').val(), $('#hidden_hh').val(), $('#hidden_mn').val());
        currentDate = new Date($('#cur_aa').val(), $('#cur_mm').val() - 1, $('#cur_jj').val(), $('#cur_hh').val(), $('#cur_mn').val());

        if (attemptedDate.getFullYear() != aa || (1 + attemptedDate.getMonth()) != mm || attemptedDate.getDate() != jj || attemptedDate.getMinutes() != mn) {
            $('.timestamp-wrap', '#timestampdiv').addClass('form-invalid');
            return false;
        } else {
            $('.timestamp-wrap', '#timestampdiv').removeClass('form-invalid');
        }

        if ((typeof postL10n != 'undefined') && (postL10n.publishOn != '')) {
            if (attemptedDate > currentDate && $('#original_post_status').val() != 'future') {
                publishOn = postL10n.publishOnFuture;
                $('#publish').val(ppObjEdit.schedule);
            } else if (attemptedDate <= currentDate && $('#original_post_status').val() != 'publish') {
                publishOn = postL10n.publishOn;
                $('#publish').val(ppObjEdit.publish);
            } else {
                publishOn = postL10n.publishOnPast;
                $('#publish').val(ppObjEdit.update);
            }
        } else {
            var __ = wp.i18n.__;

            if (attemptedDate > currentDate && $('#original_post_status').val() != 'future') {
                publishOn = __('Schedule for:');
                $('#publish').val(ppObjEdit.schedule);
            } else if (attemptedDate <= currentDate && $('#original_post_status').val() != 'publish') {
                publishOn = __('Publish On:');
                $('#publish').val(ppObjEdit.publish);
            } else {
                publishOn = __('Published On:');
                $('#publish').val(ppObjEdit.update);
            }
        }

        if (originalDate.toUTCString() == attemptedDate.toUTCString()) { //hack
            $('#timestamp').html(stamp);
        } else {
            $('#timestamp').html(
                publishOn + ' <b>' +
                $('option[value=' + $('#mm').val() + ']', '#mm').text() + ' ' +
                jj + ', ' +
                aa + ' @ ' +
                hh + ':' +
                mn + '</b> '
            );
        }

        var val = $('input:radio:checked', '#post-visibility-select').val();

        var is_private = false;
        $(pvt_stati).each(function (i) {
            if (pvt_stati[i].name == val) {
                is_private = true;
            }
        });

        if (is_private) {
            if (attemptedDate <= currentDate) {
                $('#publish').val(ppObjEdit.update);
            }

            $('#publish').val(ppObjEdit.update);
            if (optPublish.length == 0) {
                postStatus.append('<option value="publish">' + ppObjEdit.privatelyPublished + '</option>');
            } else {
                optPublish.html(ppObjEdit.privatelyPublished);
            }
            $('option[value="publish"]', postStatus).prop('selected', true);
            $('.edit-post-status', '#misc-publishing-actions').hide();
        } else {
            if ($('#original_post_status').val() == 'future') {
                if (optPublish.length) {
                    optPublish.remove();
                    postStatus.val($('#hidden_post_status').val());
                }
            } else {
                if ((typeof postL10n != 'undefined') && (postL10n.published != '')) {
                    optPublish.html(postL10n.published);
                } else {
                    optPublish.html(__('Published'));
                }
            }
            if (postStatus.is(':hidden'))
                $('.edit-post-status', '#misc-publishing-actions').show();
        }

        return true;
    }

    $('.cancel-timestamp', '#timestampdiv').on('click', function () {
        $('#timestampdiv').slideUp("fast");
        $('#mm').val($('#hidden_mm').val());
        $('#jj').val($('#hidden_jj').val());
        $('#aa').val($('#hidden_aa').val());
        $('#hh').val($('#hidden_hh').val());
        $('#mn').val($('#hidden_mn').val());
        $('#timestampdiv').siblings('a.edit-timestamp').show();
        ppUpdateText();
        return false;
    });

    $('.save-timestamp', '#timestampdiv').on('click', function () { // crazyhorse - multiple ok cancels
        if (ppUpdateText()) {
            $('#timestampdiv').slideUp("fast");
            $('#timestampdiv').siblings('a.edit-timestamp').show();
        }
        return false;
    });

    if (!$('#timestampdiv a.now-timestamp').length) {
        $('#timestampdiv a.cancel-timestamp').after('<a href="#timestamp_now" class="now-timestamp now-pp-statuses hide-if-no-js button-now">' + ppObjEdit.nowCaption + '</a>');
    }

    $('#timestampdiv a.now-pp-statuses').on('click', function () {
        var nowDate = new Date();
        var month = nowDate.getMonth() + 1;
        if (month.toString().length < 2) {
            month = '0' + month;
        }
        $('#mm').val(month);
        $('#jj').val(nowDate.getDate());
        $('#aa').val(nowDate.getFullYear());
        $('#hh').val(nowDate.getHours());

        var minutes = nowDate.getMinutes();
        if (minutes.toString().length < 2) {
            minutes = '0' + minutes;
        }
        $('#mn').val(minutes);
    });

    $('.save-post-status', '#post-status-select').on('click', function () {
        $('#post-status-select').slideUp("fast");
        $('#post-status-select').siblings('a.edit-post-status').show();
        return false;
    });

    $('.pp-cancel-post-status').on('click', function (e) {
        $('#post-status-select').slideUp("fast");

        $('#post_status option').removeAttr('selected');
        $('#post_status option[value="' + $('#hidden_post_status').val() + '"]').attr('selected', 'selected');

        $('#post-status-select').siblings('a.edit-post-status').show();
        updateStatusCaptions();

        return false;
    });

    $('#save-post-status').on('click', function (e) {
        updateStatusCaptions();
        return false;
    });

    $('#timestampdiv').siblings('a.edit-timestamp').on('click', function () {
        if ($('#timestampdiv').is(":hidden")) {
            $('#timestampdiv').slideDown('fast');
            $(this).hide();
        }
        return false;
    });

    $('#post-status-select').siblings('a.edit-post-status').on('click', function () {
        if ($('#post-status-select').is(":hidden")) {
            $('#post-status-select').slideDown('fast');
            $(this).hide();
        }
        return false;
    });
});