/**
 * Javascript used in the admin panel page.
 */
$(document).ready(function () {
    // List group item click function.
    $('.list-group-item').click(function () {
        var wellName = $(this).attr('data-btn') + '-well';
        var wellElement = $('#' + wellName);
        if (wellElement.is(":visible")) {
            wellElement.hide(100);
            return;
        }
        $('.well').hide(100);
        wellElement.show(200);
    });

    // Setting update function.
    $('.submit-setting').click(function (event) {
        event.preventDefault();
        var setting = $(this).attr('data-setting');
        var value = $('#' + setting + '-input').val();
        doAjaxRequest(
            updateSettingUrl,
            'POST',
            function () {
                $('#' + setting + '-current').html(value + ' <span class="label label-success">Updated</span>');
                setTimeout(function () {
                    $('#' + setting + '-current').html(value);
                }, 2000);
            },
            { setting: setting, value:  value }
        );
    });

});
