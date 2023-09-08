jQuery(document).ready(function ($) {
    $('#pp_status_all_types').on('click', function () {
        if ($('#pp_status_all_types').is(':checked')) {
            $('input.pp_status_post_types').prop('disabled', true);
            $('input.pp_status_post_types').prop('checked', false);
        } else {
            $('input.pp_status_post_types').prop('disabled', false);
        }
    });
});