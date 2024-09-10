<?php

if (!class_exists('FrmFieldType')) {
    return;
}

class FrmEmailVerificationField extends FrmFieldType {

    protected function field_settings_for_type() {
        $settings = parent::field_settings_for_type();
        $settings['unique'] = false;
        $settings['required'] = true;
        return $settings;
    }

    public function show_on_form_builder($field) {
        $input = '<input type="email" id="field_'. esc_attr($field['id']) .'" 
                    name="item_meta['. esc_attr($field['id']) .']" 
                    value="'. esc_attr($field['default_value']) .'" 
                    class="frm_email_verification_input" />';
        $button = '<button type="button" class="frm_send_verification_email" 
                        data-email-field="field_'. esc_attr($field['id']) .'">Send Verification Code</button>';
        $verification_code_input = '<div class="frm_verification_code_wrapper" style="display: none;">
                                        <input type="text" id="verification_code_field_'. esc_attr($field['id']) .'" 
                                            class="frm_verification_code_input" 
                                            placeholder="Enter Verification Code" />
                                        <button type="button" class="frm_verify_code_button" 
                                            data-email-field="field_'. esc_attr($field['id']) .'">Verify Code</button>
                                    </div>';
        
        return '<div class="frm_email_verification_wrapper">' . $input . $button . $verification_code_input . '</div>';
    }

    public function validate($args) {
        $errors = parent::validate($args);
        $email = sanitize_email($args['value']);

        $verified = get_transient('email_verified_' . $email);
        if (!$verified) {
            $errors['field' . $args['id']] = 'Email not verified. Please verify your email.';
        }

        return $errors;
    }
}
