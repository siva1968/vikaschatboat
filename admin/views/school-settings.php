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
                            <textarea name="edubot_welcome_message" rows="3" class="large-text"><?php echo esc_textarea(get_option('edubot_welcome_message', 'Hi! I\'m here to help you with school admissions. Let\'s get started!')); ?></textarea>
                            <p class="description">First message visitors see when they start chatting</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Completion Message</th>
                        <td>
                            <textarea name="edubot_completion_message" rows="3" class="large-text"><?php echo esc_textarea(get_option('edubot_completion_message', 'Thank you! Your application has been submitted successfully. We\'ll contact you soon.')); ?></textarea>
                            <p class="description">Message shown after successful application submission</p>
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
                                    array('code' => 'CBSE', 'name' => 'Central Board of Secondary Education', 'enabled' => true),
                                    array('code' => 'ICSE', 'name' => 'Indian Certificate of Secondary Education', 'enabled' => false),
                                    array('code' => 'IGCSE', 'name' => 'International General Certificate of Secondary Education', 'enabled' => false),
                                    array('code' => 'STATE', 'name' => 'State Board', 'enabled' => false)
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
                                                       placeholder="e.g., CBSE, ICSE, IB" 
                                                       class="regular-text" />
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
                        <input type="text" name="edubot_boards[${boardIndex}][code]" placeholder="e.g., CBSE, ICSE, IB" class="regular-text" />
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
