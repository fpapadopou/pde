/**
 * Functions used when configuring the Bison/Flex/GCC tools.
 */

updateOptions = function (tool) {
    var options = '';
    $('.' + tool + '-option').each(function () {
        if (this.checked) {
            var option = $(this).attr('data-option');
            options = options + option;
        }
    });
    if (options !== '') {
        options = '-' + options + ' ';
    }
    $('#' + tool + '-options').text(options);
};

updateOptionsWithArguments = function(tool) {
    var options = '';
    $('.' + tool + '-arg-option').each(function () {
        if (this.checked) {
            var targetId = $(this).attr('data-target');
            var target = $('#' + targetId);
            options = options + '--' + target.attr('data-option') + '=' + target.val() + ' ';
        }
    });
    $('#' + tool + '-arg-options').text(options);
};

$(document).ready(function () {
    $('.bison-option').change(function () {
        updateOptions('bison');
    });

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
