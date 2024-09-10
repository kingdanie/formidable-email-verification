<?php
/**
 * Plugin Name: Formidable Email Verification
 * Description: Adds email verification via OTP for Formidable Forms.
 * Version: 1.0
 * Author: Danie D'mola
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class FormidableEmailVerification {

    private static $instance = null;

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
        add_action( 'wp_ajax_send_email_otp', array( $this, 'send_email_otp' ) );
        add_action( 'wp_ajax_nopriv_send_email_otp', array( $this, 'send_email_otp' ) );
        add_action( 'wp_ajax_validate_email_otp', array( $this, 'validate_email_otp' ) );
        add_action( 'wp_ajax_nopriv_validate_email_otp', array( $this, 'validate_email_otp' ) );
        add_filter( 'frm_available_fields', array( $this, 'register_otp_field' ) );
        add_action( 'frm_before_field_created', array( $this, 'add_otp_field_settings' ) );
        add_action( 'frm_form_fields', array( $this, 'render_otp_field' ), 10, 2 );
		add_filter( 'frm_validate_field_entry', array( $this, 'validate_otp' ), 10, 3 );
    }

    public function register_scripts() {
        wp_register_script( 'formidable-email-verification', plugin_dir_url( __FILE__ ) . 'includes/js/formidable-email-verification.js', array( 'jquery' ), '1.1', true );
        wp_localize_script( 'formidable-email-verification', 'fev_ajax', array( 
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'fev_nonce' ) 
            ) );
        wp_enqueue_script( 'formidable-email-verification' );
    }

    public function send_email_otp() {
        check_ajax_referer( 'fev_nonce', 'nonce' );

        $email = sanitize_email( $_POST['email'] );
        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => 'Invalid email address' ) );
        }

        $otp = rand( 100000, 999999 );
        set_transient( 'fev_otp_' . $email, $otp, 10 * MINUTE_IN_SECONDS );


        $headers = array(
    'From: Eko Flavours <no-reply@ekoflavours.philians.com>',
    'Content-Type: text/html; charset=UTF-8'
);

		
		$subject = "Email Verification Is Required to Complete Your Voting Process";
		      $message = "<html>
        <head>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    color: #333;
                    background: #fff;
                }
                .container {
                    width: 80%;
                    margin: auto;
                    max-with: 400px;
                    padding: 20px;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    background-color: #f9f9f9;
                }
                .header {
                    background-color: #D9013C;
                    color: #fff;
                    padding: 10px;
                    text-align: center;
                }
                .content {
                    margin: 20px 0;
                    padding: 10px 20px;
                    background: #fff;
                }
                .otp {
                    font-size: 18px;
                    font-weight: bold;
                    color: #d9534f;
                    background-color: #fff;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    display: inline-block;
                }
                .footer {
                    margin-top: 20px;
                    font-size: 14px;
                    color: #777;
                    padding: 10px 20px;
                    background: #fff;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Eko Flavours Voting Email Verification</h2>
                </div>
                <div class='content'>
                    <p>Dear Valued Voter,</p>
                    <p>To maintain the integrity and security of our voting process, we require all voters to verify their email addresses. This step helps us ensure that each vote is cast only once.</p>
                    <p>Please use the following One-Time Password (OTP) to complete the verification process:</p>
                    <p class='otp'>$otp</p>
                    <p>This OTP will be valid for the next 10 minutes. Please keep it confidential and do not share it with anyone.</p>
                    <p>If you did not initiate this request, please disregard this email. Your email verification is crucial for ensuring a fair and secure voting experience.</p>
                </div>
                <div class='footer'>
                    <p>Thank you for your understanding and cooperation.</p>
                    <p>Best regards,<br><strong style='color:#D9013C; padding-top: 5px;'>Eko Flavours Team</strong></p>
                </div>
            </div>
        </body>
        </html>
        ";
		
		wp_mail( $email, $subject, $message, $headers );
		

        wp_send_json_success( array( 'message' => 'OTP sent to ' . $email ) );
    }

    public function validate_email_otp() {
        check_ajax_referer( 'fev_nonce', 'nonce' );

        // $email = sanitize_email( $_POST['email'] );
        $email = sanitize_email( $_POST['email'] );
        $otp = sanitize_text_field( $_POST['otp'] );
        $stored_otp = get_transient( 'fev_otp_' . $email );

        if ( $stored_otp && $otp === $stored_otp ) {
            delete_transient( 'fev_otp_' . $email );
            set_transient( 'fev_otp_verified_' . $email, true, HOUR_IN_SECONDS );
            wp_send_json_success( array( 'message' => 'OTP validated' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Invalid OTP' ) );
        }
    }

    public function register_otp_field($fields) {
        $fields['otp_verification'] = 'OTP Verification';
        return $fields;
    }

    public function add_otp_field_settings($field_data) {
        if ($field_data['type'] == 'otp_verification') {
            $field_data['name'] = 'OTPVerification';
        }
        return $field_data;
    }

    public function render_otp_field($field, $field_name) {
        if ($field['type'] == 'otp_verification') {
            ?>
            <div class="frm_otp_verification_wrapper">
                <input type="email" id="email_for_otp" name="email_for_otp" placeholder="Enter your email" class="frm_input" value=""/>
                <button type="button" id="send_otp_button" class="frm_button">Send OTP</button>
                <div id="otp_input_wrapper" style="display:none;">
                    <input type="text" id="otp_input" placeholder="Enter OTP" class="frm_input"/>
                    <button type="button" id="verify_otp_button" class="frm_button">Verify OTP</button>
                </div>
                <div id="otp_message"></div>
                <input type="hidden" name="verified_email_<?php echo esc_attr($field_name); ?>" id="otp_verification_status" value="0"/>
                <!-- <input type="hidden" name="<?php echo esc_attr($field_name); ?>" id="verified_email" value=""/> -->
                <input type="hidden" name="verified_email_val" id="verified_email" value=""/>
            </div>
            <?php
        }
    }

    public function validate_otp($errors, $field, $value) {
        
        
        if ($field->type == 'otp_verification') {
            $field_name = 'verified_email_' . $field->name;
            if (isset($_POST['verified_email_val']) && $_POST['verified_email_val'] == '') {
            // var_dump( $field->id);
            // var_dump( $field->name);
            $isEmail = sanitize_email($value); 
            $email = $_POST['verified_email_val']; // Make sure to sanitize this in a real-world scenario
        //    $email = $_POST["item_meta[$field->id]"]; // Make sure to sanitize this in a real-world scenario
            $verified = get_transient( 'fev_otp_verified_' . $email );
            if (!$verified) {
                $errors['field' . $field->id] = 'Please verify your email with the OTP sent to you.';
            }
        }
        }
        return $errors;
    }
}

FormidableEmailVerification::get_instance();