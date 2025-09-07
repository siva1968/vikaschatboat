<?php
/**
 * School Settings View
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('edubot_school_settings'); ?>
        
        <div class="edubot-settings">
            <div class="edubot-card">
                <h2>School Information</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">School Name</th>
                        <td>
                            <input type="text" name="edubot_school_name" value="<?php echo esc_attr(get_option('edubot_school_name', '')); ?>" class="regular-text" />
                            <p class="description">Enter your school's full name</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">School Logo</th>
                        <td>
                            <div class="edubot-logo-upload">
                                <input type="hidden" name="edubot_school_logo" id="edubot_school_logo" value="<?php echo esc_attr(get_option('edubot_school_logo', '')); ?>" />
                                <div class="logo-preview">
                                    <?php
                                    $logo_url = get_option('edubot_school_logo', '');
                                    if ($logo_url) {
                                        echo '<img src="' . esc_url($logo_url) . '" style="max-width: 200px; max-height: 100px; display: block; margin-bottom: 10px;" />';
                                    }
                                    ?>
                                </div>
                                <button type="button" class="button edubot-upload-logo-btn">
                                    <?php echo $logo_url ? 'Change Logo' : 'Select Logo'; ?>
                                </button>
                                <?php if ($logo_url): ?>
                                    <button type="button" class="button edubot-remove-logo-btn" style="margin-left: 10px;">Remove Logo</button>
                                <?php endif; ?>
                                <p class="description">Upload or select your school logo from the media library</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Contact Phone</th>
                        <td>
                            <input type="tel" name="edubot_school_phone" value="<?php echo esc_attr(get_option('edubot_school_phone', '')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Contact Email</th>
                        <td>
                            <input type="email" name="edubot_school_email" value="<?php echo esc_attr(get_option('edubot_school_email', '')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">School Address</th>
                        <td>
                            <textarea name="edubot_school_address" rows="3" class="large-text"><?php echo esc_textarea(get_option('edubot_school_address', '')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Website URL</th>
                        <td>
                            <input type="url" name="edubot_school_website" value="<?php echo esc_attr(get_option('edubot_school_website', '')); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
            </div>

            <div class="edubot-card">
                <h2>Branding & Colors</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Primary Color</th>
                        <td>
                            <input type="color" name="edubot_primary_color" value="<?php echo esc_attr(get_option('edubot_primary_color', '#4facfe')); ?>" />
                            <p class="description">Main color for buttons and highlights</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Secondary Color</th>
                        <td>
                            <input type="color" name="edubot_secondary_color" value="<?php echo esc_attr(get_option('edubot_secondary_color', '#00f2fe')); ?>" />
                            <p class="description">Accent color for gradients and highlights</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="edubot-card">
                <h2>Chatbot Behavior</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Welcome Message</th>
                        <td>
                            <textarea name="edubot_welcome_message" rows="3" class="large-text"><?php 
                                $welcome_msg = get_option('edubot_welcome_message', 'Hi! I\'m here to help you with school admissions. Let\'s get started!');
                                // Fix any escaping issues before displaying
                                $welcome_msg = str_replace("\\\\\\\\'", "'", $welcome_msg);
                                $welcome_msg = str_replace("\\\\\'", "'", $welcome_msg);
                                $welcome_msg = str_replace("\\'", "'", $welcome_msg);
                                echo esc_textarea($welcome_msg); 
                            ?></textarea>
                            <p class="description">First message visitors see when they start chatting</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Completion Message</th>
                        <td>
                            <textarea name="edubot_completion_message" rows="3" class="large-text"><?php 
                                $completion_msg = get_option('edubot_completion_message', 'Thank you! Your application has been submitted successfully. We\'ll contact you soon.');
                                // Fix any escaping issues before displaying
                                $completion_msg = str_replace("\\\\\\\\'", "'", $completion_msg);
                                $completion_msg = str_replace("\\\\\'", "'", $completion_msg);
                                $completion_msg = str_replace("\\'", "'", $completion_msg);
                                echo esc_textarea($completion_msg); 
                            ?></textarea>
                            <p class="description">Message shown after successful application submission</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="edubot-card">
                <h2>Notification Settings</h2>
                <p class="description">Configure how parents receive admission enquiry confirmations</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Email Notifications</th>
                        <td>
                            <label>
                                <input type="checkbox" name="edubot_email_notifications" value="1" <?php checked(get_option('edubot_email_notifications', 1)); ?> />
                                Send email confirmations to parents
                            </label>
                            <p class="description">Parents will receive HTML email confirmations with enquiry details</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">WhatsApp Notifications</th>
                        <td>
                            <label>
                                <input type="checkbox" name="edubot_whatsapp_notifications" value="1" <?php checked(get_option('edubot_whatsapp_notifications', 0)); ?> />
                                Send WhatsApp confirmations to parents
                            </label>
                            <p class="description">
                                Parents will receive WhatsApp messages with enquiry confirmation. 
                                <strong>Note:</strong> You must configure WhatsApp API settings in 
                                <a href="<?php echo admin_url('admin.php?page=edubot-api-settings'); ?>">API Integrations</a> first.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">School Email Notifications</th>
                        <td>
                            <label>
                                <input type="checkbox" name="edubot_school_notifications" value="1" <?php checked(get_option('edubot_school_notifications', 1)); ?> />
                                Send email notifications to school admission team
                            </label>
                            <p class="description">School will receive detailed enquiry notifications with all submitted information</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">School WhatsApp Notifications</th>
                        <td>
                            <label>
                                <input type="checkbox" name="edubot_school_whatsapp_notifications" value="1" <?php checked(get_option('edubot_school_whatsapp_notifications', 0)); ?> />
                                Send WhatsApp notifications to school admission team
                            </label>
                            <p class="description">
                                School will receive WhatsApp notifications for new enquiries. 
                                <strong>Note:</strong> You must configure WhatsApp API settings and add school phone number in 
                                <a href="<?php echo admin_url('admin.php?page=edubot-api-settings'); ?>">API Integrations</a> first.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">WhatsApp Template Type</th>
                        <td>
                            <select name="edubot_whatsapp_template_type">
                                <option value="freeform" <?php selected(get_option('edubot_whatsapp_template_type', 'freeform'), 'freeform'); ?>>Free-form Message</option>
                                <option value="business_template" <?php selected(get_option('edubot_whatsapp_template_type', 'freeform'), 'business_template'); ?>>Business API Template</option>
                            </select>
                            <p class="description">Choose how WhatsApp messages are sent. Business API templates require pre-approval from Meta/Facebook.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">WhatsApp Template Name</th>
                        <td>
                            <input type="text" name="edubot_whatsapp_template_name" value="<?php echo esc_attr(get_option('edubot_whatsapp_template_name', 'admission_confirmation')); ?>" class="regular-text" />
                            <p class="description">Template name registered with WhatsApp Business API (only required for Business API Templates)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">WhatsApp Template Language</th>
                        <td>
                            <select name="edubot_whatsapp_template_language">
                                <option value="en" <?php selected(get_option('edubot_whatsapp_template_language', 'en'), 'en'); ?>>English</option>
                                <option value="hi" <?php selected(get_option('edubot_whatsapp_template_language', 'en'), 'hi'); ?>>Hindi</option>
                                <option value="te" <?php selected(get_option('edubot_whatsapp_template_language', 'en'), 'te'); ?>>Telugu</option>
                                <option value="ta" <?php selected(get_option('edubot_whatsapp_template_language', 'en'), 'ta'); ?>>Tamil</option>
                                <option value="kn" <?php selected(get_option('edubot_whatsapp_template_language', 'en'), 'kn'); ?>>Kannada</option>
                                <option value="ml" <?php selected(get_option('edubot_whatsapp_template_language', 'en'), 'ml'); ?>>Malayalam</option>
                                <option value="bn" <?php selected(get_option('edubot_whatsapp_template_language', 'en'), 'bn'); ?>>Bengali</option>
                                <option value="gu" <?php selected(get_option('edubot_whatsapp_template_language', 'en'), 'gu'); ?>>Gujarati</option>
                                <option value="mr" <?php selected(get_option('edubot_whatsapp_template_language', 'en'), 'mr'); ?>>Marathi</option>
                                <option value="pa" <?php selected(get_option('edubot_whatsapp_template_language', 'en'), 'pa'); ?>>Punjabi</option>
                            </select>
                            <p class="description">Template language code for WhatsApp Business API</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">WhatsApp Message Template</th>
                        <td>
                            <textarea name="edubot_whatsapp_template" rows="12" cols="80" class="large-text" placeholder="Enter your WhatsApp message template..."><?php 
                            $default_template = "Admission Enquiry Confirmation
Dear {parent_name},

Thank you for your enquiry at {school_name}. Your enquiry number is {enquiry_number} for Grade {grade}.

We have received your application on {submission_date} and will contact you within 24-48 hours with the next steps.

Best regards,
Admissions Team
Reply STOP to unsubscribe";
                            echo esc_textarea(get_option('edubot_whatsapp_template', $default_template)); 
                            ?></textarea>
                            <p class="description">
                                <strong>Available placeholders (Free-form):</strong> {school_name}, {parent_name}, {student_name}, {enquiry_number}, {grade}, {board}, {academic_year}, {submission_date}, {phone}, {email}<br>
                                <strong>For Free-form:</strong> Use *text* for bold, _text_ for italic, ```code``` for monospace<br>
                                <strong>For Business API Template:</strong> Parameters are sent in order: {{1}}=Parent Name, {{2}}=Enquiry Number, {{3}}=School Name, {{4}}=Grade, {{5}}=Submission Date<br>
                                <strong>Your Template Format:</strong> admission_confirmation template with Header, Body ({{1}}-{{5}} parameters), Footer<br>
                                <br>
                                <strong>Template Parameter Order for Business API:</strong><br>
                                1: {school_name}, 2: {parent_name}, 3: {student_name}, 4: {enquiry_number}, 
                                5: {grade}, 6: {board}, 7: {academic_year}, 8: {submission_date}
                            </p>
                            <div style="background: #f0f8ff; border: 1px solid #0073aa; padding: 15px; border-radius: 5px; margin-top: 10px;">
                                <strong>📋 WhatsApp Business Template Setup Guide:</strong><br><br>
                                <strong>For Meta WhatsApp Business:</strong><br>
                                1. Create template in Meta Business Manager<br>
                                2. Get template approval from Facebook<br>
                                3. Use approved template name in "Template Name" field<br>
                                4. Select correct language code<br><br>
                                <strong>For Twilio:</strong><br>
                                1. Create Content Template in Twilio Console<br>
                                2. Get Content SID after approval<br>
                                3. Use Content SID in "Template Name" field<br><br>
                                <strong>⚠️ Important:</strong> Business API templates require pre-approval and can only be sent to opted-in users.
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="edubot-card">
                <h2>🏫 School Admission Team WhatsApp Templates</h2>
                <p>Configure separate WhatsApp templates for notifications sent to the school admission team. Uses the same API configuration as parent notifications.</p>
                <table class="form-table">
                    <tr>
                        <th scope="row">School WhatsApp Template Type</th>
                        <td>
                            <select name="edubot_school_whatsapp_template_type">
                                <option value="freeform" <?php selected(get_option('edubot_school_whatsapp_template_type', 'freeform'), 'freeform'); ?>>Free-form Message</option>
                                <option value="business_template" <?php selected(get_option('edubot_school_whatsapp_template_type', 'freeform'), 'business_template'); ?>>Business API Template</option>
                            </select>
                            <p class="description">Choose template type for school admission team notifications. Uses same WhatsApp API configuration as parent notifications.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">School WhatsApp Template Name</th>
                        <td>
                            <input type="text" name="edubot_school_whatsapp_template_name" value="<?php echo esc_attr(get_option('edubot_school_whatsapp_template_name', 'school_notification')); ?>" class="regular-text" />
                            <p class="description">Template name for school notifications (only required for Business API Templates)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">School WhatsApp Template Language</th>
                        <td>
                            <select name="edubot_school_whatsapp_template_language">
                                <option value="en" <?php selected(get_option('edubot_school_whatsapp_template_language', 'en'), 'en'); ?>>English</option>
                                <option value="hi" <?php selected(get_option('edubot_school_whatsapp_template_language', 'en'), 'hi'); ?>>Hindi</option>
                                <option value="te" <?php selected(get_option('edubot_school_whatsapp_template_language', 'en'), 'te'); ?>>Telugu</option>
                                <option value="ta" <?php selected(get_option('edubot_school_whatsapp_template_language', 'en'), 'ta'); ?>>Tamil</option>
                                <option value="kn" <?php selected(get_option('edubot_school_whatsapp_template_language', 'en'), 'kn'); ?>>Kannada</option>
                                <option value="ml" <?php selected(get_option('edubot_school_whatsapp_template_language', 'en'), 'ml'); ?>>Malayalam</option>
                                <option value="bn" <?php selected(get_option('edubot_school_whatsapp_template_language', 'en'), 'bn'); ?>>Bengali</option>
                                <option value="gu" <?php selected(get_option('edubot_school_whatsapp_template_language', 'en'), 'gu'); ?>>Gujarati</option>
                                <option value="mr" <?php selected(get_option('edubot_school_whatsapp_template_language', 'en'), 'mr'); ?>>Marathi</option>
                                <option value="pa" <?php selected(get_option('edubot_school_whatsapp_template_language', 'en'), 'pa'); ?>>Punjabi</option>
                            </select>
                            <p class="description">Template language for school notifications</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">School WhatsApp Message Template</th>
                        <td>
                            <textarea name="edubot_school_whatsapp_template" rows="12" cols="80" class="large-text" placeholder="Enter school WhatsApp notification template..."><?php 
                            $school_default_template = "🎓 *New Admission Enquiry - {school_name}*

📋 *Enquiry Number:* {enquiry_number}
👶 *Student:* {student_name}
🎯 *Grade:* {grade}
📚 *Board:* {board}
👨‍👩‍👧 *Parent:* {parent_name}
📱 *Phone:* {phone}
📧 *Email:* {email}
📅 *Submitted:* {submission_date}

Please review and contact the family for next steps.

EduBot Pro - Admission Management";
                            echo esc_textarea(get_option('edubot_school_whatsapp_template', $school_default_template)); 
                            ?></textarea>
                            <p class="description">
                                <strong>Available placeholders (Free-form):</strong> {school_name}, {parent_name}, {student_name}, {enquiry_number}, {grade}, {board}, {academic_year}, {submission_date}, {phone}, {email}<br>
                                <strong>For Business API Template:</strong> Parameters are sent in order: {{1}}=School Name, {{2}}=Enquiry Number, {{3}}=Student Name, {{4}}=Grade, {{5}}=Board, {{6}}=Parent Name, {{7}}=Phone, {{8}}=Email, {{9}}=Submission Date<br>
                                <br>
                                <strong>School Template Parameter Order for Business API:</strong><br>
                                1: {school_name}, 2: {enquiry_number}, 3: {student_name}, 4: {grade}, 5: {board}, 
                                6: {parent_name}, 7: {phone}, 8: {email}, 9: {submission_date}
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="edubot-card">
                <h2>Educational Boards Configuration</h2>
                <p class="description">Configure the educational boards your school offers. Students can select their preferred board during application.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Available Boards</th>
                        <td>
                            <div id="edubot-boards-container">
                                <?php
                                $configured_boards = get_option('edubot_configured_boards', array(
                                    array(
                                        'code' => 'CBSE', 
                                        'name' => 'Central Board of Secondary Education', 
                                        'description' => 'A national level board of education in India, providing systematic and comprehensive education focusing on science and mathematics.',
                                        'grades' => 'I to XII',
                                        'features' => 'NCERT curriculum, National level competitive exam preparation, Structured assessment pattern, Focus on conceptual learning',
                                        'enabled' => true
                                    ),
                                    array(
                                        'code' => 'ICSE', 
                                        'name' => 'Indian Certificate of Secondary Education', 
                                        'description' => 'A comprehensive English-medium education curriculum designed to provide balanced and holistic education.',
                                        'grades' => 'I to XII (ISC for XI-XII)',
                                        'features' => 'Detailed syllabus, English proficiency focus, Arts and literature emphasis, Comprehensive skill development',
                                        'enabled' => false
                                    ),
                                    array(
                                        'code' => 'IGCSE', 
                                        'name' => 'International General Certificate of Secondary Education', 
                                        'description' => 'An internationally recognized English-language curriculum offering a flexible study programme for students aged 14-16.',
                                        'grades' => 'IX to X (A-Levels for XI-XII)',
                                        'features' => 'International curriculum, Cambridge assessment, Critical thinking development, Global university recognition',
                                        'enabled' => false
                                    ),
                                    array(
                                        'code' => 'CAIE', 
                                        'name' => 'Cambridge Assessment International Education', 
                                        'description' => 'A comprehensive international curriculum offering Cambridge Primary, Secondary, IGCSE, and A Level programmes with global recognition.',
                                        'grades' => 'Primary to A-Level (Ages 5-19)',
                                        'features' => 'International curriculum, Cambridge qualifications, Critical thinking and problem-solving focus, Global university recognition, Flexible subject combinations',
                                        'enabled' => false
                                    ),
                                    array(
                                        'code' => 'BSE TELANGANA', 
                                        'name' => 'Board of Secondary Education, Telangana', 
                                        'description' => 'State education board of Telangana providing education in multiple languages with focus on regional needs.',
                                        'grades' => 'I to XII',
                                        'features' => 'Telugu/English medium options, State-specific curriculum, Local cultural integration, Government college admission preference',
                                        'enabled' => false
                                    )
                                ));
                                
                                foreach ($configured_boards as $index => $board): ?>
                                    <div class="edubot-board-item" data-index="<?php echo $index; ?>">
                                        <div class="board-controls">
                                            <label>
                                                <input type="checkbox" 
                                                       name="edubot_boards[<?php echo $index; ?>][enabled]" 
                                                       value="1" 
                                                       <?php checked($board['enabled']); ?> />
                                                Enable Board
                                            </label>
                                            <button type="button" class="button button-small remove-board" style="float: right;">Remove</button>
                                        </div>
                                        <div class="board-details">
                                            <div class="board-field">
                                                <label>Board Code:</label>
                                                <input type="text" 
                                                       name="edubot_boards[<?php echo $index; ?>][code]" 
                                                       value="<?php echo esc_attr($board['code']); ?>" 
                                                       placeholder="e.g., CBSE, ICSE, IGCSE, CAIE, BSE TELANGANA" 
                                                       class="regular-text board-code-input" />
                                                <p class="description">Type: CBSE, ICSE, IGCSE, CAIE, CAMBRIDGE, or BSE TELANGANA for auto-population</p>
                                            </div>
                                            <div class="board-field">
                                                <label>Full Name:</label>
                                                <input type="text" 
                                                       name="edubot_boards[<?php echo $index; ?>][name]" 
                                                       value="<?php echo esc_attr($board['name']); ?>" 
                                                       placeholder="e.g., Central Board of Secondary Education" 
                                                       class="large-text" />
                                            </div>
                                            <div class="board-field">
                                                <label>Description:</label>
                                                <textarea name="edubot_boards[<?php echo $index; ?>][description]" 
                                                          rows="2" 
                                                          class="large-text" 
                                                          placeholder="Brief description of this educational board"><?php echo esc_textarea(isset($board['description']) ? $board['description'] : ''); ?></textarea>
                                            </div>
                                            <div class="board-field">
                                                <label>Grades Offered:</label>
                                                <input type="text" 
                                                       name="edubot_boards[<?php echo $index; ?>][grades]" 
                                                       value="<?php echo esc_attr(isset($board['grades']) ? $board['grades'] : 'Pre-K to XII'); ?>" 
                                                       placeholder="e.g., Pre-K to XII, I to X, VI to XII" 
                                                       class="regular-text" />
                                            </div>
                                            <div class="board-field">
                                                <label>Curriculum Features:</label>
                                                <textarea name="edubot_boards[<?php echo $index; ?>][features]" 
                                                          rows="2" 
                                                          class="large-text" 
                                                          placeholder="Key features and highlights of this curriculum"><?php echo esc_textarea(isset($board['features']) ? $board['features'] : ''); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button type="button" id="add-new-board" class="button button-secondary">
                                <span class="dashicons dashicons-plus-alt"></span> Add New Board
                            </button>
                            
                            <p class="description">
                                Configure all educational boards your school offers. Enabled boards will be available for selection during student applications.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Default Board</th>
                        <td>
                            <select name="edubot_default_board" class="regular-text">
                                <option value="">Select Default Board</option>
                                <?php 
                                $default_board = get_option('edubot_default_board', '');
                                foreach ($configured_boards as $board): 
                                    if ($board['enabled']): ?>
                                        <option value="<?php echo esc_attr($board['code']); ?>" <?php selected($default_board, $board['code']); ?>>
                                            <?php echo esc_html($board['name']); ?>
                                        </option>
                                    <?php endif;
                                endforeach; ?>
                            </select>
                            <p class="description">Default board pre-selected in application forms</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Board Selection Required</th>
                        <td>
                            <label>
                                <input type="checkbox" name="edubot_board_selection_required" value="1" <?php checked(get_option('edubot_board_selection_required', true)); ?> />
                                Require students to select a board during application
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="edubot-card">
                <h2>Academic Year Configuration</h2>
                <p class="description">Configure academic years for admissions. The system automatically manages current and next year based on your calendar settings.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Academic Calendar Type</th>
                        <td>
                            <select name="edubot_academic_calendar_type" id="academic-calendar-type" class="regular-text">
                                <?php 
                                $calendar_type = get_option('edubot_academic_calendar_type', 'april-march');
                                $calendar_types = array(
                                    'april-march' => 'April to March',
                                    'june-may' => 'June to May', 
                                    'september-august' => 'September to August',
                                    'january-december' => 'January to December',
                                    'custom' => 'Custom Period'
                                );
                                ?>
                                <?php foreach ($calendar_types as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($calendar_type, $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Select your academic calendar pattern</p>
                        </td>
                    </tr>
                    
                    <tr id="custom-calendar-row" style="<?php echo $calendar_type !== 'custom' ? 'display: none;' : ''; ?>">
                        <th scope="row">Custom Calendar Period</th>
                        <td>
                            <div class="custom-calendar-inputs">
                                <label>Start Month:</label>
                                <select name="edubot_custom_start_month" class="regular-text">
                                    <?php 
                                    $custom_start = get_option('edubot_custom_start_month', 4);
                                    $months = array(
                                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                    );
                                    foreach ($months as $num => $name): ?>
                                        <option value="<?php echo $num; ?>" <?php selected($custom_start, $num); ?>><?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <label>End Month:</label>
                                <select name="edubot_custom_end_month" class="regular-text">
                                    <?php 
                                    $custom_end = get_option('edubot_custom_end_month', 3);
                                    foreach ($months as $num => $name): ?>
                                        <option value="<?php echo $num; ?>" <?php selected($custom_end, $num); ?>><?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Current Academic Years</th>
                        <td>
                            <div id="academic-years-display">
                                <?php
                                // Calculate current and next academic years
                                $current_year = date('Y');
                                $current_month = date('n');
                                
                                // Determine academic years based on calendar type
                                switch ($calendar_type) {
                                    case 'april-march':
                                        $start_month = 4;
                                        break;
                                    case 'june-may':
                                        $start_month = 6;
                                        break;
                                    case 'september-august':
                                        $start_month = 9;
                                        break;
                                    case 'january-december':
                                        $start_month = 1;
                                        break;
                                    case 'custom':
                                        $start_month = get_option('edubot_custom_start_month', 4);
                                        break;
                                    default:
                                        $start_month = 4;
                                }
                                
                                // Calculate academic year strings
                                if ($current_month >= $start_month) {
                                    $current_academic_year = $current_year . '-' . substr($current_year + 1, 2);
                                    $next_academic_year = ($current_year + 1) . '-' . substr($current_year + 2, 2);
                                } else {
                                    $current_academic_year = ($current_year - 1) . '-' . substr($current_year, 2);
                                    $next_academic_year = $current_year . '-' . substr($current_year + 1, 2);
                                }
                                
                                $available_years = get_option('edubot_available_academic_years', array($current_academic_year, $next_academic_year));
                                ?>
                                
                                <div class="academic-year-item">
                                    <label>
                                        <input type="checkbox" name="edubot_available_academic_years[]" 
                                               value="<?php echo esc_attr($current_academic_year); ?>" 
                                               <?php checked(in_array($current_academic_year, $available_years)); ?> />
                                        <strong><?php echo esc_html($current_academic_year); ?></strong> 
                                        <span class="year-label current-year">Current Academic Year</span>
                                    </label>
                                </div>
                                
                                <div class="academic-year-item">
                                    <label>
                                        <input type="checkbox" name="edubot_available_academic_years[]" 
                                               value="<?php echo esc_attr($next_academic_year); ?>" 
                                               <?php checked(in_array($next_academic_year, $available_years)); ?> />
                                        <strong><?php echo esc_html($next_academic_year); ?></strong> 
                                        <span class="year-label next-year">Next Academic Year</span>
                                    </label>
                                </div>
                                
                                <input type="hidden" id="current-academic-year" value="<?php echo esc_attr($current_academic_year); ?>" />
                                <input type="hidden" id="next-academic-year" value="<?php echo esc_attr($next_academic_year); ?>" />
                            </div>
                            
                            <p class="description">
                                Academic years are automatically calculated based on your calendar type. 
                                Current date: <strong><?php echo date('F j, Y'); ?></strong>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Default Academic Year</th>
                        <td>
                            <select name="edubot_default_academic_year" id="default-academic-year" class="regular-text">
                                <?php 
                                $default_year = get_option('edubot_default_academic_year', $next_academic_year);
                                ?>
                                <option value="<?php echo esc_attr($current_academic_year); ?>" <?php selected($default_year, $current_academic_year); ?>>
                                    <?php echo esc_html($current_academic_year); ?> (Current)
                                </option>
                                <option value="<?php echo esc_attr($next_academic_year); ?>" <?php selected($default_year, $next_academic_year); ?>>
                                    <?php echo esc_html($next_academic_year); ?> (Next)
                                </option>
                            </select>
                            <p class="description">Default academic year pre-selected in application forms</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Admission Open For</th>
                        <td>
                            <label>
                                <input type="radio" name="edubot_admission_period" value="current" 
                                       <?php checked(get_option('edubot_admission_period', 'next'), 'current'); ?> />
                                Current Academic Year Only
                            </label><br>
                            
                            <label>
                                <input type="radio" name="edubot_admission_period" value="next" 
                                       <?php checked(get_option('edubot_admission_period', 'next'), 'next'); ?> />
                                Next Academic Year Only
                            </label><br>
                            
                            <label>
                                <input type="radio" name="edubot_admission_period" value="both" 
                                       <?php checked(get_option('edubot_admission_period', 'next'), 'both'); ?> />
                                Both Current and Next Academic Year
                            </label>
                            
                            <p class="description">Control which academic years are available for new admissions</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <?php submit_button('Save School Settings'); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    let boardIndex = <?php echo count($configured_boards); ?>;
    
    // Academic calendar type change handler
    $('#academic-calendar-type').on('change', function() {
        const calendarType = $(this).val();
        
        if (calendarType === 'custom') {
            $('#custom-calendar-row').slideDown();
        } else {
            $('#custom-calendar-row').slideUp();
        }
        
        // Update academic years display
        updateAcademicYears(calendarType);
    });
    
    // Custom calendar change handlers
    $('select[name="edubot_custom_start_month"], select[name="edubot_custom_end_month"]').on('change', function() {
        if ($('#academic-calendar-type').val() === 'custom') {
            updateAcademicYears('custom');
        }
    });
    
    // Function to update academic years
    function updateAcademicYears(calendarType) {
        let startMonth;
        
        switch (calendarType) {
            case 'april-march':
                startMonth = 4;
                break;
            case 'june-may':
                startMonth = 6;
                break;
            case 'september-august':
                startMonth = 9;
                break;
            case 'january-december':
                startMonth = 1;
                break;
            case 'custom':
                startMonth = parseInt($('select[name="edubot_custom_start_month"]').val());
                break;
            default:
                startMonth = 4;
        }
        
        const currentYear = new Date().getFullYear();
        const currentMonth = new Date().getMonth() + 1;
        
        let currentAcademicYear, nextAcademicYear;
        
        if (currentMonth >= startMonth) {
            currentAcademicYear = currentYear + '-' + (currentYear + 1).toString().substr(2);
            nextAcademicYear = (currentYear + 1) + '-' + (currentYear + 2).toString().substr(2);
        } else {
            currentAcademicYear = (currentYear - 1) + '-' + currentYear.toString().substr(2);
            nextAcademicYear = currentYear + '-' + (currentYear + 1).toString().substr(2);
        }
        
        // Update hidden fields
        $('#current-academic-year').val(currentAcademicYear);
        $('#next-academic-year').val(nextAcademicYear);
        
        // Update checkboxes
        const $currentCheckbox = $('input[name="edubot_available_academic_years[]"]:first');
        const $nextCheckbox = $('input[name="edubot_available_academic_years[]"]:last');
        
        $currentCheckbox.val(currentAcademicYear);
        $currentCheckbox.next('strong').text(currentAcademicYear);
        
        $nextCheckbox.val(nextAcademicYear);
        $nextCheckbox.next('strong').text(nextAcademicYear);
        
        // Update default year dropdown
        const $defaultSelect = $('#default-academic-year');
        $defaultSelect.html(`
            <option value="${currentAcademicYear}">${currentAcademicYear} (Current)</option>
            <option value="${nextAcademicYear}">${nextAcademicYear} (Next)</option>
        `);
    }
    
    // Predefined board data for auto-population
    const predefinedBoards = {
        'CBSE': {
            name: 'Central Board of Secondary Education',
            description: 'A national level board of education in India, providing systematic and comprehensive education focusing on science and mathematics.',
            grades: 'I to XII',
            features: 'NCERT curriculum, National level competitive exam preparation, Structured assessment pattern, Focus on conceptual learning'
        },
        'ICSE': {
            name: 'Indian Certificate of Secondary Education',
            description: 'A comprehensive English-medium education curriculum designed to provide balanced and holistic education.',
            grades: 'I to XII (ISC for XI-XII)',
            features: 'Detailed syllabus, English proficiency focus, Arts and literature emphasis, Comprehensive skill development'
        },
        'IGCSE': {
            name: 'International General Certificate of Secondary Education',
            description: 'An internationally recognized English-language curriculum offering a flexible study programme for students aged 14-16.',
            grades: 'IX to X (A-Levels for XI-XII)',
            features: 'International curriculum, Cambridge assessment, Critical thinking development, Global university recognition'
        },
        'CAIE': {
            name: 'Cambridge Assessment International Education',
            description: 'A comprehensive international curriculum offering Cambridge Primary, Secondary, IGCSE, and A Level programmes with global recognition.',
            grades: 'Primary to A-Level (Ages 5-19)',
            features: 'International curriculum, Cambridge qualifications, Critical thinking and problem-solving focus, Global university recognition, Flexible subject combinations'
        },
        'CAMBRIDGE': {
            name: 'Cambridge Assessment International Education',
            description: 'A comprehensive international curriculum offering Cambridge Primary, Secondary, IGCSE, and A Level programmes with global recognition.',
            grades: 'Primary to A-Level (Ages 5-19)',
            features: 'International curriculum, Cambridge qualifications, Critical thinking and problem-solving focus, Global university recognition, Flexible subject combinations'
        },
        'BSE TELANGANA': {
            name: 'Board of Secondary Education, Telangana',
            description: 'State education board of Telangana providing education in multiple languages with focus on regional needs.',
            grades: 'I to XII',
            features: 'Telugu/English medium options, State-specific curriculum, Local cultural integration, Government college admission preference'
        },
        'STATE': {
            name: 'State Board',
            description: 'State government education board providing curriculum as per state educational policies.',
            grades: 'I to XII',
            features: 'State curriculum, Regional language options, Local educational standards, State university preparation'
        }
    };

    // Function to auto-populate board fields
    function autoPopulateBoard(codeInput, boardData) {
        const boardItem = $(codeInput).closest('.edubot-board-item');
        const index = boardItem.data('index');
        
        // Populate fields if they are empty
        const nameInput = boardItem.find(`input[name="edubot_boards[${index}][name]"]`);
        const descInput = boardItem.find(`textarea[name="edubot_boards[${index}][description]"]`);
        const gradesInput = boardItem.find(`input[name="edubot_boards[${index}][grades]"]`);
        const featuresInput = boardItem.find(`textarea[name="edubot_boards[${index}][features]"]`);
        
        if (nameInput.val().trim() === '' || confirm('Auto-populate fields for ' + boardData.name + '? This will overwrite existing data.')) {
            nameInput.val(boardData.name);
            descInput.val(boardData.description);
            gradesInput.val(boardData.grades);
            featuresInput.val(boardData.features);
            
            // Show success message
            const successMsg = $('<div class="notice notice-success is-dismissible" style="margin: 10px 0;"><p><strong>Auto-populated:</strong> ' + boardData.name + '</p></div>');
            boardItem.prepend(successMsg);
            setTimeout(function() {
                successMsg.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    }

    // Handle board code changes for auto-population
    $(document).on('input blur', 'input[name*="[code]"]', function() {
        const codeValue = $(this).val().toUpperCase().trim();
        
        if (predefinedBoards[codeValue]) {
            // Add visual indicator that auto-population is available
            if (!$(this).hasClass('auto-populate-available')) {
                $(this).addClass('auto-populate-available');
                
                // Add auto-populate button
                const autoBtn = $('<button type="button" class="button button-small auto-populate-btn" style="margin-left: 5px;" title="Auto-populate fields for ' + codeValue + '">Auto-fill</button>');
                $(this).after(autoBtn);
                
                autoBtn.on('click', function() {
                    autoPopulateBoard($(this).prev('input'), predefinedBoards[codeValue]);
                    $(this).remove();
                    $(this).prev('input').removeClass('auto-populate-available');
                });
            }
        } else {
            // Remove auto-populate option if code doesn't match
            $(this).removeClass('auto-populate-available');
            $(this).next('.auto-populate-btn').remove();
        }
    });

    // Add new board
    $('#add-new-board').on('click', function() {
        const boardHtml = `
            <div class="edubot-board-item" data-index="${boardIndex}">
                <div class="board-controls">
                    <label>
                        <input type="checkbox" name="edubot_boards[${boardIndex}][enabled]" value="1" checked />
                        Enable Board
                    </label>
                    <button type="button" class="button button-small remove-board" style="float: right;">Remove</button>
                </div>
                <div class="board-details">
                    <div class="board-field">
                        <label>Board Code:</label>
                        <input type="text" name="edubot_boards[${boardIndex}][code]" placeholder="e.g., CBSE, ICSE, IGCSE, CAIE, BSE TELANGANA" class="regular-text board-code-input" />
                        <p class="description">Type: CBSE, ICSE, IGCSE, CAIE, CAMBRIDGE, or BSE TELANGANA for auto-population</p>
                    </div>
                    <div class="board-field">
                        <label>Full Name:</label>
                        <input type="text" name="edubot_boards[${boardIndex}][name]" placeholder="e.g., Central Board of Secondary Education" class="large-text" />
                    </div>
                    <div class="board-field">
                        <label>Description:</label>
                        <textarea name="edubot_boards[${boardIndex}][description]" rows="2" class="large-text" placeholder="Brief description of this educational board"></textarea>
                    </div>
                    <div class="board-field">
                        <label>Grades Offered:</label>
                        <input type="text" name="edubot_boards[${boardIndex}][grades]" placeholder="e.g., Pre-K to XII, I to X, VI to XII" class="regular-text" />
                    </div>
                    <div class="board-field">
                        <label>Curriculum Features:</label>
                        <textarea name="edubot_boards[${boardIndex}][features]" rows="2" class="large-text" placeholder="Key features and highlights of this curriculum"></textarea>
                    </div>
                </div>
            </div>
        `;
        
        $('#edubot-boards-container').append(boardHtml);
        boardIndex++;
    });
    
    // Remove board
    $(document).on('click', '.remove-board', function() {
        if (confirm('Are you sure you want to remove this board?')) {
            $(this).closest('.edubot-board-item').remove();
        }
    });
    
    // Toggle board details based on enabled status
    $(document).on('change', 'input[name*="[enabled]"]', function() {
        const boardItem = $(this).closest('.edubot-board-item');
        const details = boardItem.find('.board-details');
        
        if ($(this).is(':checked')) {
            details.slideDown();
        } else {
            details.slideUp();
        }
    });
    
    // Initialize board details visibility
    $('input[name*="[enabled]"]').each(function() {
        const boardItem = $(this).closest('.edubot-board-item');
        const details = boardItem.find('.board-details');
        
        if (!$(this).is(':checked')) {
            details.hide();
        }
    });

    // Initialize auto-population for existing board codes
    $('input[name*="[code]"]').each(function() {
        const codeValue = $(this).val().toUpperCase().trim();
        if (codeValue && predefinedBoards[codeValue]) {
            $(this).trigger('input');
        }
    });
});
</script>

<style>
.edubot-settings {
    margin-top: 20px;
}
.edubot-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 4px;
}
.edubot-card h2 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

/* Board Configuration Styles */
.edubot-board-item {
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 4px;
    position: relative;
}

.board-controls {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}

.board-controls label {
    font-weight: 600;
    color: #0073aa;
}

.board-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

/* Auto-population styles */
.board-code-input.auto-populate-available {
    border-left: 3px solid #00a32a;
    background-color: #f0f9ff;
}

.auto-populate-btn {
    background: linear-gradient(135deg, #00a32a, #008a20);
    color: white;
    border: none;
    padding: 3px 8px;
    font-size: 11px;
    border-radius: 3px;
    cursor: pointer;
    animation: pulse 2s infinite;
}

.auto-populate-btn:hover {
    background: linear-gradient(135deg, #008a20, #006d1a);
    transform: translateY(-1px);
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(0, 163, 42, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(0, 163, 42, 0); }
    100% { box-shadow: 0 0 0 0 rgba(0, 163, 42, 0); }
}

.board-field .description {
    font-size: 12px;
    color: #666;
    margin-top: 3px;
    font-style: italic;
}

.board-field {
    display: flex;
    flex-direction: column;
}

.board-field label {
    font-weight: 600;
    margin-bottom: 5px;
    color: #23282d;
}

.board-field:nth-child(2),
.board-field:nth-child(3),
.board-field:nth-child(5) {
    grid-column: 1 / -1;
}

#add-new-board {
    margin-top: 10px;
}

#add-new-board .dashicons {
    vertical-align: middle;
}

/* Academic Year Configuration Styles */
.academic-year-item {
    margin: 10px 0;
    padding: 10px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
}

.academic-year-item label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    margin: 0;
}

.year-label {
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 500;
}

.year-label.current-year {
    background: #d4edda;
    color: #155724;
}

.year-label.next-year {
    background: #cce5ff;
    color: #004085;
}

.custom-calendar-inputs {
    display: grid;
    grid-template-columns: auto 1fr auto 1fr;
    gap: 10px;
    align-items: center;
    max-width: 400px;
}

.custom-calendar-inputs label {
    font-weight: 600;
    color: #555;
}

@media (max-width: 782px) {
    .board-details {
        grid-template-columns: 1fr;
    }
    
    .custom-calendar-inputs {
        grid-template-columns: 1fr;
        gap: 5px;
    }
    
    .custom-calendar-inputs label {
        margin-top: 10px;
        margin-bottom: 2px;
    }
}
</style>
