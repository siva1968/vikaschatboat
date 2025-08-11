<?php
/**
 * Academic Configuration Manager
 * Handles school-specific academic structures, grades, and board configurations
 *
 * @package EdubotPro
 * @subpackage EdubotPro/includes
 */

class Edubot_Academic_Config {

    /**
     * Get predefined grade/class systems
     */
    public static function get_grade_systems() {
        return array(
            'us_k12' => array(
                'name' => 'US K-12 System',
                'grades' => array(
                    'kindergarten' => 'Kindergarten',
                    'grade_1' => 'Grade 1',
                    'grade_2' => 'Grade 2',
                    'grade_3' => 'Grade 3',
                    'grade_4' => 'Grade 4',
                    'grade_5' => 'Grade 5',
                    'grade_6' => 'Grade 6',
                    'grade_7' => 'Grade 7',
                    'grade_8' => 'Grade 8',
                    'grade_9' => 'Grade 9',
                    'grade_10' => 'Grade 10',
                    'grade_11' => 'Grade 11',
                    'grade_12' => 'Grade 12'
                )
            ),
            'indian_class' => array(
                'name' => 'Indian Class System',
                'grades' => array(
                    'nursery' => 'Nursery',
                    'lkg' => 'LKG',
                    'ukg' => 'UKG',
                    'class_1' => 'Class I',
                    'class_2' => 'Class II',
                    'class_3' => 'Class III',
                    'class_4' => 'Class IV',
                    'class_5' => 'Class V',
                    'class_6' => 'Class VI',
                    'class_7' => 'Class VII',
                    'class_8' => 'Class VIII',
                    'class_9' => 'Class IX',
                    'class_10' => 'Class X',
                    'class_11' => 'Class XI',
                    'class_12' => 'Class XII'
                )
            ),
            'uk_year' => array(
                'name' => 'UK Year System',
                'grades' => array(
                    'reception' => 'Reception',
                    'year_1' => 'Year 1',
                    'year_2' => 'Year 2',
                    'year_3' => 'Year 3',
                    'year_4' => 'Year 4',
                    'year_5' => 'Year 5',
                    'year_6' => 'Year 6',
                    'year_7' => 'Year 7',
                    'year_8' => 'Year 8',
                    'year_9' => 'Year 9',
                    'year_10' => 'Year 10',
                    'year_11' => 'Year 11',
                    'year_12' => 'Year 12',
                    'year_13' => 'Year 13'
                )
            ),
            'early_childhood' => array(
                'name' => 'Early Childhood Education',
                'grades' => array(
                    'infant' => 'Infant (6-18 months)',
                    'toddler' => 'Toddler (18-36 months)',
                    'preschool' => 'Preschool (3-4 years)',
                    'pre_k' => 'Pre-K (4-5 years)',
                    'kindergarten' => 'Kindergarten (5-6 years)'
                )
            ),
            'custom' => array(
                'name' => 'Custom Grade System',
                'grades' => array() // Will be defined by school
            )
        );
    }

    /**
     * Get available educational boards
     */
    public static function get_educational_boards() {
        return array(
            'cbse' => array(
                'name' => 'Central Board of Secondary Education (CBSE)',
                'country' => 'India',
                'website' => 'https://cbse.gov.in',
                'requirements' => array(
                    'birth_certificate' => 'Birth Certificate',
                    'transfer_certificate' => 'Transfer Certificate',
                    'mark_sheets' => 'Previous Year Mark Sheets',
                    'conduct_certificate' => 'Conduct Certificate',
                    'photo' => 'Passport Size Photos',
                    'aadhar' => 'Aadhar Card (for Indian students)'
                ),
                'subjects' => array(
                    'mathematics', 'science', 'social_science', 'english', 'hindi',
                    'computer_science', 'physical_education', 'art_education'
                )
            ),
            'icse' => array(
                'name' => 'Indian Certificate of Secondary Education (ICSE)',
                'country' => 'India',
                'website' => 'https://cisce.org',
                'requirements' => array(
                    'birth_certificate' => 'Birth Certificate',
                    'transfer_certificate' => 'Transfer Certificate',
                    'mark_sheets' => 'Previous Year Mark Sheets',
                    'conduct_certificate' => 'Conduct Certificate',
                    'photo' => 'Recent Photographs'
                ),
                'subjects' => array(
                    'english', 'mathematics', 'physics', 'chemistry', 'biology',
                    'history', 'geography', 'computer_applications'
                )
            ),
            'ib' => array(
                'name' => 'International Baccalaureate (IB)',
                'country' => 'International',
                'website' => 'https://ibo.org',
                'requirements' => array(
                    'academic_transcripts' => 'Academic Transcripts',
                    'language_proficiency' => 'Language Proficiency Certificate',
                    'recommendation_letters' => 'Teacher Recommendation Letters',
                    'personal_statement' => 'Personal Statement',
                    'passport_copy' => 'Passport Copy'
                ),
                'subjects' => array(
                    'language_literature', 'language_acquisition', 'individuals_societies',
                    'sciences', 'mathematics', 'arts', 'extended_essay', 'tok', 'cas'
                )
            ),
            'cambridge' => array(
                'name' => 'Cambridge International',
                'country' => 'UK/International',
                'website' => 'https://cambridgeinternational.org',
                'requirements' => array(
                    'academic_records' => 'Previous Academic Records',
                    'english_proficiency' => 'English Language Proficiency',
                    'recommendation' => 'School Recommendation',
                    'photo' => 'Recent Photographs'
                ),
                'subjects' => array(
                    'english', 'mathematics', 'sciences', 'humanities', 'languages',
                    'creative_arts', 'practical_skills'
                )
            ),
            'state_board' => array(
                'name' => 'State Board',
                'country' => 'India (Various States)',
                'website' => 'Varies by state',
                'requirements' => array(
                    'birth_certificate' => 'Birth Certificate',
                    'domicile_certificate' => 'Domicile Certificate',
                    'transfer_certificate' => 'Transfer Certificate',
                    'mark_sheets' => 'Mark Sheets',
                    'caste_certificate' => 'Caste Certificate (if applicable)'
                ),
                'subjects' => array() // Varies by state
            ),
            'none' => array(
                'name' => 'No Specific Board',
                'country' => 'Any',
                'website' => '',
                'requirements' => array(),
                'subjects' => array()
            )
        );
    }

    /**
     * Get academic year configurations
     */
    public static function get_academic_year_configs() {
        return array(
            'april_march' => array(
                'name' => 'April - March',
                'start_month' => 4,
                'end_month' => 3,
                'common_in' => array('India', 'Japan', 'Thailand')
            ),
            'august_june' => array(
                'name' => 'August - June',
                'start_month' => 8,
                'end_month' => 6,
                'common_in' => array('USA', 'Canada')
            ),
            'september_july' => array(
                'name' => 'September - July',
                'start_month' => 9,
                'end_month' => 7,
                'common_in' => array('UK', 'Australia', 'New Zealand')
            ),
            'january_december' => array(
                'name' => 'January - December',
                'start_month' => 1,
                'end_month' => 12,
                'common_in' => array('Philippines', 'Brazil')
            ),
            'february_november' => array(
                'name' => 'February - November',
                'start_month' => 2,
                'end_month' => 11,
                'common_in' => array('South Africa')
            )
        );
    }

    /**
     * Get current academic year based on school configuration
     */
    public static function get_current_academic_year($school_id) {
        $school_config = self::get_school_academic_config($school_id);
        $year_config = $school_config['academic_year_type'] ?? 'april_march';
        
        $configs = self::get_academic_year_configs();
        $config = $configs[$year_config];
        
        $current_month = date('n');
        $current_year = date('Y');
        
        if ($current_month >= $config['start_month']) {
            $start_year = $current_year;
            $end_year = $current_year + 1;
        } else {
            $start_year = $current_year - 1;
            $end_year = $current_year;
        }
        
        return array(
            'start_year' => $start_year,
            'end_year' => $end_year,
            'display' => $start_year . '-' . $end_year,
            'config' => $config
        );
    }

    /**
     * Save school academic configuration
     */
    public static function save_school_academic_config($school_id, $config) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_school_configs';
        
        // Get existing config
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT academic_structure FROM $table WHERE id = %d",
            $school_id
        ));
        
        $academic_structure = array();
        if ($existing && !empty($existing->academic_structure)) {
            $academic_structure = json_decode($existing->academic_structure, true);
        }
        
        // Merge new config
        $academic_structure = array_merge($academic_structure, $config);
        
        // Update database
        return $wpdb->update(
            $table,
            array('academic_structure' => json_encode($academic_structure)),
            array('id' => $school_id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Get school academic configuration
     */
    public static function get_school_academic_config($school_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_school_configs';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT academic_structure FROM $table WHERE id = %d",
            $school_id
        ));
        
        if ($result && !empty($result->academic_structure)) {
            return json_decode($result->academic_structure, true);
        }
        
        // Return default configuration
        return array(
            'grade_system' => 'custom',
            'custom_grades' => array(),
            'academic_year_type' => 'april_march',
            'board' => 'none',
            'admission_cycles' => array(
                array(
                    'name' => 'Regular Admission',
                    'start_date' => '',
                    'end_date' => '',
                    'grades_available' => array()
                )
            )
        );
    }

    /**
     * Get board configuration for school
     */
    public static function get_school_board_config($school_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_school_configs';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT board_settings FROM $table WHERE id = %d",
            $school_id
        ));
        
        if ($result && !empty($result->board_settings)) {
            return json_decode($result->board_settings, true);
        }
        
        return array(
            'board_type' => 'none',
            'board_custom_name' => '',
            'additional_requirements' => array(),
            'subjects_offered' => array(),
            'board_specific_fields' => array()
        );
    }

    /**
     * Save board configuration
     */
    public static function save_school_board_config($school_id, $config) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_school_configs';
        
        return $wpdb->update(
            $table,
            array('board_settings' => json_encode($config)),
            array('id' => $school_id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Get academic year settings for school
     */
    public static function get_school_academic_year_config($school_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_school_configs';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT academic_year_settings FROM $table WHERE id = %d",
            $school_id
        ));
        
        if ($result && !empty($result->academic_year_settings)) {
            return json_decode($result->academic_year_settings, true);
        }
        
        return array(
            'academic_year_type' => 'april_march',
            'custom_start_month' => 4,
            'custom_end_month' => 3,
            'auto_update_year' => true,
            'admission_windows' => array()
        );
    }

    /**
     * Save academic year configuration
     */
    public static function save_school_academic_year_config($school_id, $config) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'edubot_school_configs';
        
        return $wpdb->update(
            $table,
            array('academic_year_settings' => json_encode($config)),
            array('id' => $school_id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Get available grades for admission in current year
     */
    public static function get_available_grades_for_admission($school_id) {
        $academic_config = self::get_school_academic_config($school_id);
        $grade_system = $academic_config['grade_system'] ?? 'us_k12';
        
        if ($grade_system === 'custom') {
            $custom_grades = $academic_config['custom_grades'] ?? array();
            
            // If custom grades is empty, provide some default options
            if (empty($custom_grades)) {
                return array(
                    'nursery' => 'Nursery',
                    'kindergarten' => 'Kindergarten',
                    'grade_1' => 'Grade 1',
                    'grade_2' => 'Grade 2',
                    'grade_3' => 'Grade 3',
                    'grade_4' => 'Grade 4',
                    'grade_5' => 'Grade 5'
                );
            }
            
            return $custom_grades;
        }
        
        $grade_systems = self::get_grade_systems();
        if (isset($grade_systems[$grade_system])) {
            return $grade_systems[$grade_system]['grades'];
        }
        
        // Fallback to US K-12 system if nothing else works
        $fallback_grades = self::get_grade_systems();
        return $fallback_grades['us_k12']['grades'] ?? array(
            'kindergarten' => 'Kindergarten',
            'grade_1' => 'Grade 1',
            'grade_2' => 'Grade 2',
            'grade_3' => 'Grade 3',
            'grade_4' => 'Grade 4',
            'grade_5' => 'Grade 5'
        );
    }

    /**
     * Validate academic configuration
     */
    public static function validate_academic_config($config) {
        $errors = array();
        
        // Validate grade system
        if (empty($config['grade_system'])) {
            $errors[] = 'Grade system is required';
        }
        
        // If custom grade system, validate custom grades
        if ($config['grade_system'] === 'custom') {
            if (empty($config['custom_grades']) || !is_array($config['custom_grades'])) {
                $errors[] = 'Custom grades must be defined for custom grade system';
            }
        }
        
        // Validate academic year type
        $year_configs = self::get_academic_year_configs();
        if (!empty($config['academic_year_type']) && !isset($year_configs[$config['academic_year_type']])) {
            $errors[] = 'Invalid academic year type';
        }
        
        return $errors;
    }

    /**
     * Get formatted grade display name
     */
    public static function get_grade_display_name($school_id, $grade_key) {
        $academic_config = self::get_school_academic_config($school_id);
        $grade_system = $academic_config['grade_system'];
        
        if ($grade_system === 'custom') {
            $custom_grades = $academic_config['custom_grades'] ?? array();
            return $custom_grades[$grade_key] ?? $grade_key;
        }
        
        $grade_systems = self::get_grade_systems();
        if (isset($grade_systems[$grade_system]['grades'][$grade_key])) {
            return $grade_systems[$grade_system]['grades'][$grade_key];
        }
        
        return $grade_key;
    }
}
