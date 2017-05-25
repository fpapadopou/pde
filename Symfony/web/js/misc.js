/**
 * Miscellaneous functions that are used across the application.
 */

// Triggers an info modal with the provided message.
function infoModalMessage(message) {
    $('.modal').modal('hide');
    $('#info-modal-body').html('<i class="fa fa-times"></i> ' + message);
    $('#info-modal').modal('show');
}

// Performs an ajax request - the handler callback for successful responses is passed as a parameter.
function ajaxRequestWithSuccessHandler(url, method, callback, data) {
    ajaxRequestWithDoneCallback(
        url,
        method,
        function (response) {
            if (response.success === true) {
                callback(response);
                return;
            }
            infoModalMessage(response.error);
        },
        data
    );
}

// Performs an ajax request - the done callback is fully specified.
function ajaxRequestWithDoneCallback(url, method, callback, data) {
    // data is an optional parameter
    data = data || {};
    $.ajax({
        url: url,
        method: method,
        data: data
    }).done(function (response) {
        callback(response);
    }).fail(function () {
        infoModalMessage('An error occurred. Please try again.');
    });
}

// Enables tooltips across the site
$(document).ready(function() {
    // Tooltips must be manually initialized as soon as the DOM is ready.
    $('[data-toggle="tooltip"]').tooltip();
});