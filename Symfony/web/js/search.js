$(document).ready(function () {
    // Listener for delete team button
    $(".delete-team-btn").click(function () {
        var targetTeamId = $(this).attr('data-team-id');
        ajaxRequestWithSuccessHandler(
            deleteTeamUrl,
            'DELETE',
            function () {
                $('#team-' + targetTeamId + '-list-group-item').hide(100);
            },
            {'team' : targetTeamId}
        );
    });
});
