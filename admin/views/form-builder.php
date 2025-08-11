<?php
/**
 * Form Builder View
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="edubot-form-builder">
        <div class="edubot-card">
            <h2>Application Form Configuration</h2>
            <p>Configure the application form fields and layout.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('edubot_form_settings', '_wpnonce'); ?>
                
                <table class="edubot-form-table">
                    <tr>
                        <th scope="row">
                            <label for="form_title">Form Title</label>
                        </th>
                        <td>
                            <input type="text" id="form_title" name="form_title" value="<?php echo esc_attr($settings['form_title'] ?? 'Student Application Form'); ?>" class="regular-text" />
                            <p class="description">The title that appears at the top of the application form.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="form_description">Form Description</label>
                        </th>
                        <td>
                            <textarea id="form_description" name="form_description" rows="3" class="large-text"><?php echo esc_textarea($settings['form_description'] ?? 'Please fill out this form to apply for admission to our school.'); ?></textarea>
                            <p class="description">Brief description or instructions for the application form.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="required_fields">Required Fields</label>
                        </th>
                        <td>
                            <?php
                            $available_fields = array(
                                'student_name' => 'Student Name',
                                'date_of_birth' => 'Date of Birth',
                                'grade' => 'Grade/Class',
                                'gender' => 'Gender',
                                'parent_name' => 'Parent/Guardian Name',
                                'email' => 'Email Address',
                                'phone' => 'Phone Number',
                                'address' => 'Address',
                                'previous_school' => 'Previous School',
                                'medical_conditions' => 'Medical Conditions'
                            );
                            
                            $required_fields = $settings['required_fields'] ?? array('student_name', 'parent_name', 'email', 'phone', 'grade');
                            
                            foreach ($available_fields as $field_key => $field_label) {
                                $checked = in_array($field_key, $required_fields) ? 'checked' : '';
                                echo '<label style="display: block; margin-bottom: 5px;">';
                                echo '<input type="checkbox" name="required_fields[]" value="' . esc_attr($field_key) . '" ' . $checked . '> ';
                                echo esc_html($field_label);
                                echo '</label>';
                            }
                            ?>
                            <p class="description">Select which fields are required for form submission.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="form_layout">Form Layout</label>
                        </th>
                        <td>
                            <select id="form_layout" name="form_layout">
                                <option value="single_column" <?php selected($settings['form_layout'] ?? 'single_column', 'single_column'); ?>>Single Column</option>
                                <option value="two_column" <?php selected($settings['form_layout'] ?? 'single_column', 'two_column'); ?>>Two Column</option>
                            </select>
                            <p class="description">Choose the layout for the application form.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="success_message">Success Message</label>
                        </th>
                        <td>
                            <textarea id="success_message" name="success_message" rows="3" class="large-text"><?php echo esc_textarea($settings['success_message'] ?? 'Thank you for your application! We will contact you soon.'); ?></textarea>
                            <p class="description">Message displayed after successful form submission.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="notification_email">Notification Email</label>
                        </th>
                        <td>
                            <input type="email" id="notification_email" name="notification_email" value="<?php echo esc_attr($settings['notification_email'] ?? get_option('admin_email')); ?>" class="regular-text" />
                            <p class="description">Email address to receive new application notifications.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Form Settings'); ?>
            </form>
        </div>
        
        <div class="edubot-card">
            <h2>Form Preview</h2>
            <p>Preview how your application form will appear to visitors:</p>
            
            <div class="form-preview">
                <div style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                    <h3><?php echo esc_html($settings['form_title'] ?? 'Student Application Form'); ?></h3>
                    <p><?php echo esc_html($settings['form_description'] ?? 'Please fill out this form to apply for admission to our school.'); ?></p>
                    
                    <form style="margin-top: 20px;">
                        <?php
                        $required_fields = $settings['required_fields'] ?? array('student_name', 'parent_name', 'email', 'phone', 'grade');
                        $layout_class = ($settings['form_layout'] ?? 'single_column') === 'two_column' ? 'two-column' : 'single-column';
                        ?>
                        
                        <div class="form-fields <?php echo esc_attr($layout_class); ?>">
                            <?php foreach ($available_fields as $field_key => $field_label): ?>
                                <?php if (in_array($field_key, $required_fields)): ?>
                                    <div class="form-field">
                                        <label><?php echo esc_html($field_label); ?> *</label>
                                        <?php if ($field_key === 'address'): ?>
                                            <textarea rows="2" disabled></textarea>
                                        <?php else: ?>
                                            <input type="text" disabled />
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="button" disabled style="margin-top: 15px; padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 3px;">Submit Application</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-fields.two-column {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-field {
    margin-bottom: 15px;
}

.form-field label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
}

.form-field input,
.form-field textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

@media (max-width: 768px) {
    .form-fields.two-column {
        grid-template-columns: 1fr;
    }
}
</style>
