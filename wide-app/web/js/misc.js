/**
 * Miscellaneous functions that are used across the application.
 */

function doAjaxRequest(url, method, callback, data) {
    // data is an optional parameter
    data = data || {};
    $.ajax({
        url: url,
        method: method,
        data: data
    }).done(function (response) {
        callback(response);
    }).fail(function () {
        $('#info-modal-body').html('<i class="fa fa-times"></i><p>An error occurred. Please try again.</p>');
    });
}

$( document ).ready(function() {
    // Tooltips must be manually initialized as soon as the DOM is ready.
    $('[data-toggle="tooltip"]').tooltip();
    $('#ceid_email').on('input', function() {
        validateLoginForm();
    });
});