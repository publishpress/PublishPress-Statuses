(function ($) {
    // Update the list visibility on sort (prevent lists from collapsing when nesting)
	var ppUpdateListVisibility = function(ui)
	{
		var parentList = $(ui.placeholder).parent('ol');
		if ( !$(parentList).is(':visible') ) {
			$(parentList).addClass('pp-nested-list');
			$(parentList).show();
		}
	}

    // Update the width of the placeholder ( width changes depending on level of nesting )
	var ppUpdatePlaceholderWidth = function(ui)
	{
        var parentCount = $(ui.placeholder).parents('ol').length;
        var listWidth = $('#the_status_list').width();
        var offset = ( parentCount * 30 ) - 30;
        var newWidth = listWidth - offset;
        $(ui.placeholder).width(newWidth).css('margin-left', offset + 'px');

		ppUpdateListVisibility(ui);
    }

    var ppSetNestedMargins = function()
	{
		var amount = 30;
		var indent_element = '.child-toggle';
		$.each($('div.row-inner'), function(i, v){
            
            var parent_count = $(v).parents('ol.pp-nested-list').length - 1;

			var defaultPadding = 0;

			if ( parent_count >= 0 ){
				var padding = ( parent_count * amount ) + defaultPadding;
                $(this).siblings(indent_element).css('width', padding + 'px');
				return;
            }
            
			if ( parent_count < 1 ){
				$(this).siblings(indent_element).css('width', '0px');
			}
		});
	}

    var ppRestoreElement = '';

    function ppInitializeNestedStatusList() {
        /**
         * Instantiate the drag and drop sorting functionality
         */
        $('#the_status_list').nestedSortable({
            items: '.page-row',
            toleranceElement: '> .row',
            placeholder: 'ui-sortable-placeholder',
            tabSize: 56,
            maxLevels: 2,
            handle: '.handle',
            isAllowed: function(placeholder, placeholderParent, currentItem){
                // Don't allow a status to be dragged below Published / Private, or above Draft
                var targetStatus = $(placeholderParent).prop('id');            

                isVisibility = (typeof (currentItem) != 'undefined') && $(currentItem).hasClass('private-status');

                if (typeof (targetStatus) != 'undefined') {
                    // Attempting to make this row a child status

                    targetStatus = targetStatus.replace('status_row_', '');

                    // Not currently allowing a core status to be a status parent                    
                    if (jQuery.inArray(targetStatus, ['draft', 'future', 'publish', 'private']) > -1) { // @todo: custom privacy statuses
                        return false;
                    }

                    // Section row headers cannot be status parent
                    if ($(placeholderParent).hasClass('section-row')) {
                        return false;
                    }

                    // Visibility statuses are not nested
                    if ($(currentItem).hasClass('private-status')) {
                        return false;
                    }

                    // Pending status cannot be nested as a child status
                    if ($(currentItem).attr('id') == 'status_row_pending') {
                        return false;
                    }

                } else {
                    // Attempting to move this row up or down trunk

                    // Don't allow anything before Draft
                    if (typeof (placeholder.prop('nextElementSibling') != 'undefined')) {
                        targetStatus = $(placeholder.prop('nextElementSibling')).prop('id');
                        
                        if (typeof targetStatus != 'undefined') {
                            targetStatus = targetStatus.replace('status_row_', '');
                        }

                        if (targetStatus == 'draft' || targetStatus == '_pre-publish') {
                            return false;
                        }
                    }

                    // Don't allow Pending to be disabled or moved to alternate
                    if (typeof (placeholder.prop('nextElementSibling') != 'undefined')) {
                        if ($(currentItem).attr('id') == 'status_row_pending') {
                            if ($(placeholder.prop('previousElementSibling')).hasClass('disabled-status')
                            || $(placeholder.prop('previousElementSibling')).hasClass('alternate-moderation-status')
                            ) {
                                return false;
                            }
                        }
                    }

                    if (typeof (placeholder.prop('previousElementSibling') != 'undefined')) {
                        targetStatus = $(placeholder.prop('previousElementSibling')).prop('id');

                        if (typeof (targetStatus) != 'undefined') {
                            targetStatus = targetStatus.replace('status_row_', '');
                        }

                        targetIsVisibility = $(placeholder.prop('previousElementSibling')).hasClass('private-status');
                        targetIsDisabled = $(placeholder.prop('previousElementSibling')).hasClass('disabled-status');
                        targetIsChild = $(placeholder.prop('previousElementSibling')).parents('ol.pp-nested-list').length;

                        if ((isVisibility != targetIsVisibility) && (! targetIsDisabled || ! targetIsChild)) {
                            return false;
                        }

                        // workaround @todo: why is li class modified to "moderation-status after drag into Disabled section?"
                        if (isVisibility && targetIsDisabled) {
                            ppRestoreElement = currentItem;

                            setTimeout(function() {
                                $(ppRestoreElement).removeClass('moderation-status').addClass('private-status');
                            }, 100);
                        }

                        // Pending status cannot be moved out of Pre-Publication workflow
                        if ($(currentItem).attr('id') == 'status_row_pending') {
                            if (!$(placeholder.prop('previousElementSibling')).hasClass('moderation-status')
                            && ($targetStatus != 'draft')
                            ) {
                                return false;
                            }
                        }
                    }
                }

                return true;
            },
            start: function(e, ui){
				ui.placeholder.height(ui.item.height());
			},
			sort: function(e, ui){
				ppUpdatePlaceholderWidth(ui);
			},
			stop: function(e, ui){
				setTimeout(
					function(){
						ppSetNestedMargins();
					}, 100
                );
                
				//ppSyncNesting();
            },
            update: function (event, ui) {
                //var affected_item = ui.item;

                if ($(ui.item).prev('li').hasClass('moderation-status')) {
                    $(ui.item).addClass('moderation-status').find('ol li').addClass('moderation-status');
                    $(ui.item).removeClass('alternate-moderation-status').find('ol li').removeClass('alternate-moderation-status');
                    $(ui.item).removeClass('private-status').find('ol li').removeClass('private-status');
                    $(ui.item).removeClass('disabled-status').find('ol li').removeClass('disabled-status');
                }

                if ($(ui.item).prev('li').hasClass('alternate-moderation-status')) {
                    $(ui.item).addClass('alternate-moderation-status').find('ol li').addClass('alternate-moderation-status');
                    $(ui.item).removeClass('moderation-status').find('ol li').removeClass('moderation-status');
                    $(ui.item).removeClass('private-status').find('ol li').removeClass('private-status');
                    $(ui.item).removeClass('disabled-status').find('ol li').removeClass('disabled-status');
                }

                if ($(ui.item).prev('li').hasClass('private-status')) {
                    $(ui.item).removeClass('disabled-status').find('ol li').removeClass('disabled-status');
                }

                if ($(ui.item).prev('li').hasClass('disabled-status')) {
                    $(ui.item).addClass('disabled-status').find('ol li').addClass('disabled-status');
                }

                $(ui.item).prop('style', '');

                ppUpdateStatusPositions();
            }
        });
    }

    function ppUpdateStatusPositions() {
        var statuses = [];

        $('#the_status_list li').each(function (index, value) {
            var status_name = $(this).find('div.status_name').html();
            
            statuses[index] = status_name;
            $('div.position', this).html(index + 1);
        });

        var hierarchy = $('#the_status_list').sortable("toHierarchy");

        // Prepare the POST
        var params = {
            action: 'pp_update_status_positions',
            status_positions: statuses,
            status_hierarchy: hierarchy,
            _wpnonce: $('#custom-status-sortable').val()
        };

        // Inform WordPress of our updated positions
        jQuery.post(ajaxurl, params, function (retval) {
            $('.pp-float-notice').remove();

            // If there's a success message, print it. Otherwise we assume we received an error message
            if (retval.status == 'success') {
                var message = '<div class="is-dismissible notice pp-float-notice notice-success"><p>' + retval.message + '</p></div>';
            } else {
                var message = '<div class="is-dismissible notice pp-float-notice notice-error"><p>' + retval.message + '</p></div>';
            }

            $('header > :first-child').before(message);

            $('.pp-float-notice').delay(1000).fadeOut(2000, function() {$(this).remove();});
        }).fail(function() {
            var message = '<div class="is-dismissible pp-notice pp-float-notice notice-error"><p>Error</p></div>';

            $('header > :first-child').before(message);
            $('.pp-float-notice').delay(1000).fadeOut(2000, function() {$(this).remove();});
        });
    }

    $(document).ready(function () {
        $('.delete-status a').on('click', function () {
            if (!confirm(objectL10ncustomstatus.pp_confirm_delete_status_string))
                return false;
        });

        ppInitializeNestedStatusList();

        $('#the_status_list td.name .disable').click(function() {
            $(this).hide().closest('li.page-row').addClass('disabled-status').insertAfter($('#the_status_list > li.disabled-status:last'));
            ppUpdateStatusPositions();
            return false;
        });

        $('#the_status_list td.name .delete').click(function() {
            // Prepare the POST
            var params = {
                action: 'pp_delete_custom_status',
                delete_status: $(this).closest('table.status-row tbody tr').find('td.status_name div.status_name').html(),
                _wpnonce: $('#custom-status-sortable').val()
            };

            jQuery.post(ajaxurl, params, function (retval) {
                // If there's a success message, print it. Otherwise we assume we received an error message
                if (retval.status == 'success') {
                    var message = '<div class="is-dismissible notice pp-float-notice notice-success"><p>' + retval.message + '</p></div>';
                } else {
                    var message = '<div class="is-dismissible notice pp-float-notice notice-error"><p>' + retval.message + '</p></div>';
                }

                $('header > :first-child').before(message);

                $('.pp-float-notice').delay(1000).fadeOut(2000, function() {$(this).remove();});

            }).fail(function() {
                var message = '<div class="is-dismissible pp-notice pp-float-notice notice-error"><p>Error</p></div>';
                $('header > :first-child').before(message);
                $('.pp-float-notice').delay(1000).fadeOut(2000, function() {$(this).remove();});
            });

           // also delete status children

           $(this).closest('li.page-row').find('ol.pp-nested-list li.page-row td.status_name div.status_name').each(function() {
                params = {
                    action: 'pp_delete_custom_status',
                    delete_status: $(this).html(),
                    _wpnonce: $('#custom-status-sortable').val()
                };

                jQuery.post(ajaxurl, params, function (retval) {});
           });
           
           $(this).hide().closest('li.page-row').fadeOut(500);
           
           setTimeout(() => {
               $(this).hide().closest('li.page-row').remove();
           }, 500);

           return false;
        });
        
        $('#pp_status_all_types').on('click', function () {
            if ($('#pp_status_all_types').is(':checked')) {
                $('input.pp_status_post_types').prop('disabled', true);
                $('input.pp_status_post_types').prop('checked', false);
            } else {
                $('input.pp_status_post_types').prop('disabled', false);
            }
        });
    });
})(jQuery);
