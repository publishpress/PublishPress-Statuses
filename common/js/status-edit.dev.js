jQuery(document).ready(function ($) {
    $('#pp_status_all_types').on('click', function () {
        if ($('#pp_status_all_types').is(':checked')) {
            $('input.pp_status_post_types').prop('disabled', true);
            $('input.pp_status_post_types').prop('checked', false);
        } else {
            $('input.pp_status_post_types').prop('disabled', false);
        }
    });

    $('div.publishpress-admin-wrapper ul.nav-tab-wrapper li a').click(function(e) {
        $('input[type="hidden"][name="pp_tab"]').val($(this).attr('href'));
    });

    $("#addstatus #label").on("change", function(e) {
        if (!$("#addstatus #slug").val()) {
            $("#addstatus #slug").val($(this).val());
            $("#addstatus #slug").trigger("keyup");
        }
    });

    $("#editstatus #label").on("change", function(e) {
        if (!$("#editstatus #slug").val()) {
            $("#editstatus #slug").val($(this).val());
            $("#editstatus #slug").trigger("keyup");
        }
    });

    $("#addstatus #slug, #editstatus #slug").on("keyup", function(e) {
        var value, original_value;
        value = original_value = $(this).val();
        if (e.keyCode !== 9 && e.keyCode !== 37 && e.keyCode !== 38 && e.keyCode !== 39 && e.keyCode !== 40) {
            value = value.replace(/ /g, "_");
            value = value.toLowerCase();
            value = replaceDiacritics(value);
            value = transliterate(value);
            value = replaceSpecialCharacters(value);
            if (value !== original_value) {
                $(this).prop("value", value);
            }
        }
        if (typeof original_slug !== "undefined") {
            var $slugchanged = $("#slugchanged");
            if (value != original_slug) {
                $slugchanged.removeClass("hidemessage");
            } else {
                $slugchanged.addClass("hidemessage");
            }
        }
        var $slugexists = $("#slugexists");
        var $override_validation = $("#override_validation").is(":checked");
    });
    function replaceDiacritics(s) {
        var diacritics = [ /[\300-\306]/g, /[\340-\346]/g, /[\310-\313]/g, /[\350-\353]/g, /[\314-\317]/g, /[\354-\357]/g, /[\322-\330]/g, /[\362-\370]/g, /[\331-\334]/g, /[\371-\374]/g, /[\321]/g, /[\361]/g, /[\307]/g, /[\347]/g ];
        var chars = [ "A", "a", "E", "e", "I", "i", "O", "o", "U", "u", "N", "n", "C", "c" ];
        for (var i = 0; i < diacritics.length; i++) {
            s = s.replace(diacritics[i], chars[i]);
        }
        return s;
    }
    function replaceSpecialCharacters(s) {
        s = s.replace(/[^a-z0-9\s-]/gi, "_");
        return s;
    }

    var cyrillic = {
        "?": "YO",
        "?": "I",
        "?": "TS",
        "?": "U",
        "?": "K",
        "?": "E",
        "?": "N",
        "?": "G",
        "?": "SH",
        "?": "SCH",
        "?": "Z",
        "?": "H",
        "?": "'",
        "?": "yo",
        "?": "i",
        "?": "ts",
        "?": "u",
        "?": "k",
        "?": "e",
        "?": "n",
        "?": "g",
        "?": "sh",
        "?": "sch",
        "?": "z",
        "?": "h",
        "?": "'",
        "?": "F",
        "?": "I",
        "?": "V",
        "?": "a",
        "?": "P",
        "?": "R",
        "?": "O",
        "?": "L",
        "?": "D",
        "?": "ZH",
        "?": "E",
        "?": "f",
        "?": "i",
        "?": "v",
        "?": "a",
        "?": "p",
        "?": "r",
        "?": "o",
        "?": "l",
        "?": "d",
        "?": "zh",
        "?": "e",
        "?": "Ya",
        "?": "CH",
        "?": "S",
        "?": "M",
        "?": "I",
        "?": "T",
        "?": "'",
        "?": "B",
        "?": "YU",
        "?": "ya",
        "?": "ch",
        "?": "s",
        "?": "m",
        "?": "i",
        "?": "t",
        "?": "'",
        "?": "b",
        "?": "yu"
    };
    function transliterate(word) {
        return word.split("").map(function(char) {
            return cyrillic[char] || char;
        }).join("");
    }
});