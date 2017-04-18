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
        // Set an empty value in order to reset the deadline setting, if necessary.
        if ($(this).attr('id') === 'reset-deadline') {
            value = '';
        }
        ajaxRequestWithSuccessHandler(
            updateSettingUrl,
            'POST',
            function (response) {
                $('#' + setting + '-current').html(response.value);
            },
            { setting: setting, value:  value }
        );
    });

});
