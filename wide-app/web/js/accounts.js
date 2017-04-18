/**
 * Functions used in the account page of the application.
 */

function revealAction(id) {
    element = $('#' + id);
    if (element.is(":visible")) {
        return;
    }
    $('div.team-action').hide(150);
    element.show(150);
}

function updateOnTeamDelete(response) {
    modalResponseHandler(response);
    if (response.success === true) {
        $('#go-to-editor-btn').hide();
    }
}

function updateOnTeamCreate(response) {
    modalResponseHandler(response);
    if (response.success === true) {
        $('#go-to-editor-btn').show();
    }
}

function modalResponseHandler(response) {
    body = $('#info-modal-body');
    modalDiv = $('#info-modal');
    if (response.success === true) {
        body.html('<i class="fa fa-check"></i> Operation successful.');
        modalDiv.modal('show');
        return;
    }
    body.html('<i class="fa fa-times"></i> ' + response.error);
    modalDiv.modal('show');
}

$(document).ready(function() {
    $('a.list-group-item').click(function () {
        revealAction('action-' + this.id);
    });

    // Team button handlers
    $('#create-team-btn').click(function () {
        ajaxRequestWithSuccessHandler(createTeamUrl, 'POST', updateOnTeamCreate);
    });
    $('#delete-team-btn').click(function () {
        ajaxRequestWithSuccessHandler(deleteTeamUrl, 'POST', updateOnTeamDelete);
    });
    $('#leave-team-btn').click(function () {
        ajaxRequestWithSuccessHandler(leaveTeamUrl, 'POST', updateOnTeamDelete);
    });
    $('#add-member-btn').click(function (event) {
        event.preventDefault();
        ajaxRequestWithSuccessHandler(addMemberUrl, 'POST', modalResponseHandler, {'email' : $('#add-to-team-input').val()});
    });

    // Account deletion modal trigger
    $('#delete-account-btn').click(function () {
        $('#account-delete-modal').modal('show');
    });
});