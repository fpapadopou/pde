$(document).ready(function () {
    // Listener for delete team button, triggers the confirmation modal
    $(".delete-team-btn").click(function () {
        var targetTeamId = $(this).attr('data-team-id');
        // change modal button target, pop-up modal
        $('#team-del-modal-btn').attr('data-team-id', targetTeamId);
        $('#team-id').text(targetTeamId);
        $('#team-delete-modal').modal('show');
    });

    // Listener for the confirmation modal button
    $('#team-del-modal-btn').click(function () {
        var targetTeamId = $(this).attr('data-team-id');
        ajaxRequestWithSuccessHandler(
            deleteTeamUrl,
            'DELETE',
            function () {
                $('#team-delete-modal').modal('hide');
                $('#team-' + targetTeamId + '-list-group-item').hide(100);
            },
            {'team' : targetTeamId}
        );
    });
});
