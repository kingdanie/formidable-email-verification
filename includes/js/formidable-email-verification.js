jQuery(document).ready(function ($) {
    let isValidationRunning = false;
    $('#send_otp_button').on('click', function () {
        if (isValidationRunning) return;

        isValidationRunning = true;
		// $('#send_otp_button').prop('disabled', true);
        let originalText = $(this).html();
        $('#send_otp_button').prop('disabled', true).html('<span class="loader"></span> Sending...'); // Show spinner


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
                    // $('#otp_message').text(response.data.message);
                    $('#otp_message').text(response.data.message).removeClass('error-message').addClass('success-message').show();
                    $('#otp_input_wrapper').show();
//                     $('#email_for_otp').val(email);
                    $('#send_otp_button').hide();
					
                } else {
                    // $('#otp_message').text(response.data.message);
                    $('#otp_message').text(response.data.message).removeClass('success-message').addClass('error-message').show();
					$('#send_otp_button').prop('disabled', false);
                }
                
                 // Hide the message after 10 seconds
                 setTimeout(function () {
                    $('#otp_message').fadeOut(); // Or you can use `.hide()` to remove instantly
                }, 7000); // 10000 milliseconds = 10 seconds
            },
            complete: function () {
                isValidationRunning = false;
                $('#send_otp_button').prop('disabled', false).html(originalText); 
            }
        });
    });

    $('#verify_otp_button').on('click', function () {
        let originalText = $(this).html(); // Save the original button text
        $('#verify_otp_button').prop('disabled', true).html('<span class="loader"></span> Verifying...'); // Show spinner


        let email_for_otp = $('#email_for_otp').val();
        let otp = $('#otp_input').val();
		// $('#verify_otp_button').prop('disabled', true);
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
                    // $('#otp_message').text(response.data.message);
                    $('#otp_message').text(response.data.message).removeClass('error-message').addClass('success-message').show();
                    $('#verified_email').val(email_for_otp);
//                     $('#email_for_otp').val(email_for_otp);
                    $('#otp_verification_status').val('1');
                    // Enable the next step or submit button here
//                     $('.frm_next_page').prop('disabled', false);
//                     
//                      // Hide or remove the fields after successful OTP verification
                    $('#otp_input_wrapper').remove();   // Remove OTP input field
                    $('#email_for_otp').remove();       // Remove email input field
                    $('#verify_otp_button').remove();   // Remove verify button
					$('#send_otp_button').remove();
                   
                } else {
                    // $('#otp_message').text(response.data.message);
                    $('#otp_message').text(response.data.message).removeClass('success-message').addClass('error-message').show();
                    $('#send_otp_button').show();
                    $('#otp_input_wrapper').hide();
					$('#verify_otp_button').hide();
					$('#send_otp_button').prop('disabled', false);
                }
                 // Hide the message after 10 seconds
                 setTimeout(function () {
                    $('#otp_message').fadeOut(); // Or you can use `.hide()` to remove instantly
                }, 7000); // 10000 milliseconds = 10 seconds
            },
            complete: function () {
                // $('#verify_otp_button').prop('disabled', false).html(originalText); // Restore original text
            }
        });
    });

    // Disable next step button initially
//     $('.frm_next_page').prop('disabled', true);
});