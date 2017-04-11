/**
 * Functions used when configuring the Bison/Flex/GCC tools.
 */

updateShortOptions = function (tool) {
    var options = '';
    $('.' + tool + '-short-option').each(function () {
        if (this.checked) {
            options += $(this).attr('data-option');
        }
    });
    if (options !== '') {
        options = '-' + options + ' ';
    }
    $('#' + tool + '-short-options').text(options);
};

updateLongOptions = function (tool) {
    var options = '';
    $('.' + tool + '-long-option').each(function () {
        if (this.checked) {
            options += '--' + $(this).attr('data-option') + ' ';
        }
    });
    $('#' + tool + '-long-options').text(options);
};

updateOptionsWithArguments = function(tool) {
    var options = '';
    $('.' + tool + '-arg-option').each(function () {
        if (this.checked) {
            var targetId = $(this).attr('data-target');
            var target = $('#' + targetId);
            var dashes = '--';
            if (target.hasClass('single-hyphen')) {
                dashes = '-';
            }
            options += dashes + target.attr('data-option');
            if (target.val() !== '') {
                options += '=' + target.val();
            }
            options += ' ';
        }
    });
    $('#' + tool + '-arg-options').text(options);
};

$(document).ready(function () {
    // Single-hyphen bison options
    $('.bison-short-option').change(function () {
        updateShortOptions('bison');
    });

    // Double-hyphen bison options
    $('.bison-long-option').change(function () {
        updateLongOptions('bison');
    });

    // Bison options with arguments
    $('.bison-arg-input').on('change paste keyup', function () {
        updateOptionsWithArguments('bison');
    });

    // $('.flex-option').change(function () {
    //     updateOptions('flex');
    // });
    // $('.gcc-option').change(function () {
    //     updateOptions('gcc');
    // });

    $("[class$='-arg-option']").change(function () {
        var targetId = $(this).attr('data-target');
        var target = $('#' + targetId);
        var tool = $(this).attr('class').replace('-arg-option', '');
        if (this.checked) {
            updateOptionsWithArguments(tool);
            target.show(100);
            return;
        }
        updateOptionsWithArguments(tool);
        target.hide(100);
    });
});
