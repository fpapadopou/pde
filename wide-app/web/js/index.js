/**
 * Functions that are necessary in the index page of the application.
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
