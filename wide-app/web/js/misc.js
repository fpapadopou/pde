/**
 * This file contains several functions that will be
 * used across the application frontend.
 */

function validateLoginForm() {
    var emailInput = $('#ceid_email');
    var providedEmail = emailInput.val();

    if (providedEmail.indexOf('@ceid.upatras.gr') === -1) {
        emailInput.parent().addClass('has-error');
        $('#login-form-button').attr('disabled', 'disabled');
        return;
    }

    emailInput.parent().removeClass('has-error').addClass('has-success');
    $('#login-form-button').removeAttr('disabled');
}

$( document ).ready(function() {
    // Tooltips must be manually initialized as soon as the DOM is ready.
    $('[data-toggle="tooltip"]').tooltip();
    $('#ceid_email').on('input', function() {
        validateLoginForm();
    });
});