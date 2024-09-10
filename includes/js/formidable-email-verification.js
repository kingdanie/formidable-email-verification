jQuery(document).ready(function ($) {
    let isValidationRunning = false;
    $('#send_otp_button').on('click', function () {
        if (isValidationRunning) return;

        isValidationRunning = true;

        let email = $('#email_for_otp').val();
        $.ajax({
            url: fev_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'send_email_otp',
                email: email,
                nonce: fev_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    $('#otp_message').text(response.data.message);
                    $('#otp_input_wrapper').show();
                    $('#email_for_otp').val(email);
                    $('#send_otp_button').hide();
                } else {
                    $('#otp_message').text(response.data.message);
                }
            },
            complete: function () {
                isValidationRunning = false;
            }
        });
    });

    $('#verify_otp_button').on('click', function () {
        let email_for_otp = $('#email_for_otp').val();
        let otp = $('#otp_input').val();
        $.ajax({
            url: fev_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'validate_email_otp',
                email: email_for_otp,
                otp: otp,
                nonce: fev_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    $('#otp_message').text(response.data.message);
                    $('#verified_email').val(email_for_otp);
                    $('#email_for_otp').val(email_for_otp);
                    $('#otp_verification_status').val('1');
                    // Enable the next step or submit button here
                    // $('.frm_next_page').prop('disabled', false);
                } else {
                    $('#otp_message').text(response.data.message);
                    $('#send_otp_button').show();
                    $('#otp_input_wrapper').hide();
                }
            }
        });
    });

    // Disable next step button initially
    // $('.frm_next_page').prop('disabled', true);
});