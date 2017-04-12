/**
 * Functions used when configuring the Bison/Flex/GCC tools.
 */

// Updates the short options list (single hyphen options).
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

// Updates the long options list (double hyphen options).
updateLongOptions = function (tool) {
    var options = '';
    $('.' + tool + '-long-option').each(function () {
        if (this.checked) {
            options += '--' + $(this).attr('data-option') + ' ';
        }
    });
    $('#' + tool + '-long-options').text(options);
};

// Updates the list of options with arguments (single or double hyphen options). Arguments might be optional.
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
    // Single-hyphen bison options handler.
    $('.bison-short-option').change(function () {
        updateShortOptions('bison');
    });
    // Single-hyphen bison options handler.
    $('.flex-short-option').change(function () {
        updateShortOptions('flex');
    });
    // Single-hyphen gcc options handler.
    $('.gcc-short-option').change(function () {
        updateShortOptions('gcc');
    });

    // Double-hyphen bison options handler.
    $('.bison-long-option').change(function () {
        updateLongOptions('bison');
    });
    // Double-hyphen flex options handler.
    $('.flex-long-option').change(function () {
        updateLongOptions('flex');
    });
    // Double-hyphen gcc options handler.
    $('.gcc-long-option').change(function () {
        updateLongOptions('gcc');
    });

    // Bison options with arguments change listener.
    $('.bison-arg-input').on('change paste keyup', function () {
        updateOptionsWithArguments('bison');
    });
    // Flex options with arguments change listener.
    $('.flex-arg-input').on('change paste keyup', function () {
        updateOptionsWithArguments('flex');
    });
    // Gcc options with arguments change listener.
    $('.gcc-arg-input').on('change paste keyup', function () {
        updateOptionsWithArguments('gcc');
    });

    // Listener for changes in option arguments input elements
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
