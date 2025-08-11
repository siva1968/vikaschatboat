<?php
/**
 * Academic Configuration Admin Template
 *
 * @package EdubotPro
 * @subpackage EdubotPro/admin/partials
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$school_id = isset($_GET['school_id']) ? intval($_GET['school_id']) : 0;
$academic_config = Edubot_Academic_Config::get_school_academic_config($school_id);
$board_config = Edubot_Academic_Config::get_school_board_config($school_id);
$academic_year_config = Edubot_Academic_Config::get_school_academic_year_config($school_id);

$grade_systems = Edubot_Academic_Config::get_grade_systems();
$educational_boards = Edubot_Academic_Config::get_educational_boards();
$academic_year_configs = Edubot_Academic_Config::get_academic_year_configs();
?>

<div class="edubot-admin-panel">
    <div class="edubot-admin-header">
        <h1><?php esc_html_e('Academic Configuration', 'edubot-pro'); ?></h1>
        <p><?php esc_html_e('Configure grade systems, academic years, and educational boards for your school.', 'edubot-pro'); ?></p>
    </div>

    <form method="post" action="" class="edubot-admin-form" id="academic-config-form">
        <?php wp_nonce_field('edubot_save_academic_config', 'edubot_academic_nonce'); ?>
        <input type="hidden" name="school_id" value="<?php echo esc_attr($school_id); ?>" />

        <!-- Grade System Configuration -->
        <div class="edubot-form-section">
            <h3><?php esc_html_e('Grade/Class System', 'edubot-pro'); ?></h3>
            <p><?php esc_html_e('Choose how your school organizes students by grade or class level.', 'edubot-pro'); ?></p>

            <table class="edubot-form-table">
                <tr>
                    <th><?php esc_html_e('Grade System', 'edubot-pro'); ?></th>
                    <td>
                        <select name="academic_config[grade_system]" id="grade-system-select">
                            <?php foreach ($grade_systems as $key => $system): ?>
                                <option value="<?php echo esc_attr($key); ?>" 
                                        <?php selected($academic_config['grade_system'], $key); ?>>
                                    <?php echo esc_html($system['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Select the grade/class naming system used by your school.', 'edubot-pro'); ?></p>
                    </td>
                </tr>
            </table>

            <!-- Custom Grades Section -->
            <div id="custom-grades-section" style="<?php echo $academic_config['grade_system'] === 'custom' ? '' : 'display: none;'; ?>">
                <h4><?php esc_html_e('Custom Grade Configuration', 'edubot-pro'); ?></h4>
                <div class="edubot-custom-fields" id="custom-grades-container">
                    <?php 
                    $custom_grades = $academic_config['custom_grades'] ?? array();
                    if (!empty($custom_grades)): 
                        foreach ($custom_grades as $key => $label): ?>
                            <div class="edubot-field-item">
                                <input type="text" name="academic_config[custom_grades_keys][]" 
                                       value="<?php echo esc_attr($key); ?>" placeholder="Grade Key (e.g., grade_1)" required />
                                <input type="text" name="academic_config[custom_grades_labels][]" 
                                       value="<?php echo esc_attr($label); ?>" placeholder="Grade Display Name (e.g., Grade 1)" required />
                                <button type="button" class="edubot-remove-btn"><?php esc_html_e('Remove', 'edubot-pro'); ?></button>
                            </div>
                        <?php endforeach;
                    else: ?>
                        <div class="edubot-field-item">
                            <input type="text" name="academic_config[custom_grades_keys][]" placeholder="Grade Key (e.g., grade_1)" required />
                            <input type="text" name="academic_config[custom_grades_labels][]" placeholder="Grade Display Name (e.g., Grade 1)" required />
                            <button type="button" class="edubot-remove-btn"><?php esc_html_e('Remove', 'edubot-pro'); ?></button>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="edubot-add-btn" id="add-custom-grade">
                    <?php esc_html_e('Add Grade', 'edubot-pro'); ?>
                </button>
            </div>

            <!-- Predefined Grades Preview -->
            <div id="predefined-grades-preview">
                <?php foreach ($grade_systems as $key => $system): 
                    if ($key === 'custom') continue; ?>
                    <div class="grade-preview" id="preview-<?php echo esc_attr($key); ?>" 
                         style="<?php echo $academic_config['grade_system'] === $key ? '' : 'display: none;'; ?>">
                        <h4><?php echo esc_html($system['name']); ?> - <?php esc_html_e('Available Grades', 'edubot-pro'); ?></h4>
                        <div class="grades-list">
                            <?php foreach ($system['grades'] as $grade_key => $grade_label): ?>
                                <span class="grade-item"><?php echo esc_html($grade_label); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Academic Year Configuration -->
        <div class="edubot-form-section">
            <h3><?php esc_html_e('Academic Year Configuration', 'edubot-pro'); ?></h3>
            <p><?php esc_html_e('Configure your school\'s academic year cycle and admission windows.', 'edubot-pro'); ?></p>

            <table class="edubot-form-table">
                <tr>
                    <th><?php esc_html_e('Academic Year Type', 'edubot-pro'); ?></th>
                    <td>
                        <select name="academic_year_config[academic_year_type]" id="academic-year-type">
                            <?php foreach ($academic_year_configs as $key => $config): ?>
                                <option value="<?php echo esc_attr($key); ?>" 
                                        <?php selected($academic_year_config['academic_year_type'], $key); ?>>
                                    <?php echo esc_html($config['name']); ?>
                                    (<?php esc_html_e('Common in:', 'edubot-pro'); ?> <?php echo esc_html(implode(', ', $config['common_in'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Select when your academic year starts and ends.', 'edubot-pro'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Auto-Update Academic Year', 'edubot-pro'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="academic_year_config[auto_update_year]" value="1" 
                                   <?php checked($academic_year_config['auto_update_year'], true); ?> />
                            <?php esc_html_e('Automatically update academic year based on current date', 'edubot-pro'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('When enabled, the system will automatically determine the current academic year.', 'edubot-pro'); ?></p>
                    </td>
                </tr>
            </table>

            <!-- Current Academic Year Display -->
            <div class="edubot-help-text">
                <h4><?php esc_html_e('Current Academic Year', 'edubot-pro'); ?></h4>
                <p id="current-academic-year-display">
                    <?php 
                    $current_year = Edubot_Academic_Config::get_current_academic_year($school_id);
                    echo esc_html($current_year['display']);
                    ?>
                </p>
            </div>
        </div>

        <!-- Educational Board Configuration -->
        <div class="edubot-form-section">
            <h3><?php esc_html_e('Educational Board (Optional)', 'edubot-pro'); ?></h3>
            <p><?php esc_html_e('Configure the educational board/curriculum your school follows. This affects admission requirements and available subjects.', 'edubot-pro'); ?></p>

            <table class="edubot-form-table">
                <tr>
                    <th><?php esc_html_e('Educational Board', 'edubot-pro'); ?></th>
                    <td>
                        <select name="board_config[board_type]" id="board-type-select">
                            <?php foreach ($educational_boards as $key => $board): ?>
                                <option value="<?php echo esc_attr($key); ?>" 
                                        <?php selected($board_config['board_type'], $key); ?>>
                                    <?php echo esc_html($board['name']); ?>
                                    <?php if ($board['country'] !== 'Any'): ?>
                                        (<?php echo esc_html($board['country']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Select the educational board or leave as "No Specific Board" if not applicable.', 'edubot-pro'); ?></p>
                    </td>
                </tr>
            </table>

            <!-- Board-Specific Configuration -->
            <div id="board-specific-config">
                <?php foreach ($educational_boards as $key => $board): 
                    if ($key === 'none') continue; ?>
                    <div class="board-config" id="board-config-<?php echo esc_attr($key); ?>" 
                         style="<?php echo $board_config['board_type'] === $key ? '' : 'display: none;'; ?>">
                        
                        <h4><?php echo esc_html($board['name']); ?> - <?php esc_html_e('Configuration', 'edubot-pro'); ?></h4>
                        
                        <?php if (!empty($board['website'])): ?>
                            <p><strong><?php esc_html_e('Official Website:', 'edubot-pro'); ?></strong> 
                               <a href="<?php echo esc_url($board['website']); ?>" target="_blank"><?php echo esc_html($board['website']); ?></a></p>
                        <?php endif; ?>

                        <?php if (!empty($board['requirements'])): ?>
                            <h5><?php esc_html_e('Standard Requirements', 'edubot-pro'); ?></h5>
                            <div class="requirements-list">
                                <?php foreach ($board['requirements'] as $req_key => $req_name): ?>
                                    <label>
                                        <input type="checkbox" name="board_config[requirements][<?php echo esc_attr($req_key); ?>]" 
                                               value="<?php echo esc_attr($req_name); ?>" checked />
                                        <?php echo esc_html($req_name); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($board['subjects'])): ?>
                            <h5><?php esc_html_e('Available Subjects', 'edubot-pro'); ?></h5>
                            <div class="subjects-list">
                                <?php foreach ($board['subjects'] as $subject): ?>
                                    <label>
                                        <input type="checkbox" name="board_config[subjects][<?php echo esc_attr($subject); ?>]" 
                                               value="<?php echo esc_attr($subject); ?>" checked />
                                        <?php echo esc_html(ucwords(str_replace('_', ' ', $subject))); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Custom Board Name -->
            <div id="custom-board-section" style="<?php echo $board_config['board_type'] === 'custom' ? '' : 'display: none;'; ?>">
                <table class="edubot-form-table">
                    <tr>
                        <th><?php esc_html_e('Custom Board Name', 'edubot-pro'); ?></th>
                        <td>
                            <input type="text" name="board_config[board_custom_name]" 
                                   value="<?php echo esc_attr($board_config['board_custom_name'] ?? ''); ?>" 
                                   placeholder="Enter your board/curriculum name" />
                            <p class="description"><?php esc_html_e('Enter the name of your custom educational board or curriculum.', 'edubot-pro'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Admission Cycles -->
        <div class="edubot-form-section">
            <h3><?php esc_html_e('Admission Cycles', 'edubot-pro'); ?></h3>
            <p><?php esc_html_e('Configure when admissions are open for different grades throughout the academic year.', 'edubot-pro'); ?></p>

            <div class="edubot-custom-fields" id="admission-cycles-container">
                <?php 
                $admission_cycles = $academic_config['admission_cycles'] ?? array(
                    array('name' => 'Regular Admission', 'start_date' => '', 'end_date' => '', 'grades_available' => array())
                );
                
                foreach ($admission_cycles as $index => $cycle): ?>
                    <div class="edubot-field-item admission-cycle-item">
                        <input type="text" name="academic_config[admission_cycles][<?php echo $index; ?>][name]" 
                               value="<?php echo esc_attr($cycle['name']); ?>" placeholder="Admission Cycle Name" required />
                        <input type="date" name="academic_config[admission_cycles][<?php echo $index; ?>][start_date]" 
                               value="<?php echo esc_attr($cycle['start_date']); ?>" />
                        <input type="date" name="academic_config[admission_cycles][<?php echo $index; ?>][end_date]" 
                               value="<?php echo esc_attr($cycle['end_date']); ?>" />
                        <button type="button" class="edubot-remove-btn"><?php esc_html_e('Remove', 'edubot-pro'); ?></button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="edubot-add-btn" id="add-admission-cycle">
                <?php esc_html_e('Add Admission Cycle', 'edubot-pro'); ?>
            </button>
        </div>

        <!-- Save Button -->
        <div class="edubot-form-section">
            <button type="submit" class="button button-primary button-large">
                <?php esc_html_e('Save Academic Configuration', 'edubot-pro'); ?>
            </button>
            <span class="edubot-autosave-indicator" style="display: none;"></span>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Grade system change handler
    $('#grade-system-select').on('change', function() {
        var selectedSystem = $(this).val();
        
        // Hide all previews
        $('.grade-preview').hide();
        
        if (selectedSystem === 'custom') {
            $('#custom-grades-section').show();
        } else {
            $('#custom-grades-section').hide();
            $('#preview-' + selectedSystem).show();
        }
    });

    // Board type change handler
    $('#board-type-select').on('change', function() {
        var selectedBoard = $(this).val();
        
        // Hide all board configs
        $('.board-config').hide();
        
        if (selectedBoard !== 'none') {
            $('#board-config-' + selectedBoard).show();
        }
        
        if (selectedBoard === 'custom') {
            $('#custom-board-section').show();
        } else {
            $('#custom-board-section').hide();
        }
    });

    // Add custom grade
    $('#add-custom-grade').on('click', function() {
        var template = `
            <div class="edubot-field-item">
                <input type="text" name="academic_config[custom_grades_keys][]" placeholder="Grade Key (e.g., grade_1)" required />
                <input type="text" name="academic_config[custom_grades_labels][]" placeholder="Grade Display Name (e.g., Grade 1)" required />
                <button type="button" class="edubot-remove-btn"><?php esc_html_e('Remove', 'edubot-pro'); ?></button>
            </div>
        `;
        $('#custom-grades-container').append(template);
    });

    // Add admission cycle
    $('#add-admission-cycle').on('click', function() {
        var index = $('#admission-cycles-container .admission-cycle-item').length;
        var template = `
            <div class="edubot-field-item admission-cycle-item">
                <input type="text" name="academic_config[admission_cycles][${index}][name]" placeholder="Admission Cycle Name" required />
                <input type="date" name="academic_config[admission_cycles][${index}][start_date]" />
                <input type="date" name="academic_config[admission_cycles][${index}][end_date]" />
                <button type="button" class="edubot-remove-btn"><?php esc_html_e('Remove', 'edubot-pro'); ?></button>
            </div>
        `;
        $('#admission-cycles-container').append(template);
    });

    // Update academic year display when type changes
    $('#academic-year-type').on('change', function() {
        // This would make an AJAX call to update the current year display
        // Implementation depends on your AJAX setup
    });
});
</script>

<style>
.grades-list, .requirements-list, .subjects-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}

.grade-item {
    background: #f0f0f1;
    padding: 5px 10px;
    border-radius: 3px;
    font-size: 12px;
}

.requirements-list label, .subjects-list label {
    display: flex;
    align-items: center;
    gap: 5px;
    background: #f9f9f9;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
}

.admission-cycle-item {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 10px;
    align-items: center;
}

.board-config {
    border: 1px solid #e1e5e9;
    padding: 20px;
    margin-top: 15px;
    border-radius: 4px;
    background: #fafafa;
}
</style>
