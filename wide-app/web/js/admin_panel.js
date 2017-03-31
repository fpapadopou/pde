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
            "{{ url('update_setting') }}",
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

    $('#team-search-btn').click(function (event) {
        event.preventDefault();

        var email = $('#search-email').val();
        var date = $('#search-date').val();

        doAjaxRequest(
            "{{ url('admin_search') }}",
            'POST',
            function (response) {
                var resultsList = $('#teams-list-group');
                resultsList.html('');
                for (i = 0; i < response.teams.length; i++) {
                    resultsList.append(response.teams[i]);
                }
            },
            { email : email, date : date }
        );
    });
});
