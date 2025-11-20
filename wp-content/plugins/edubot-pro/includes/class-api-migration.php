<?php

/**
 * API Configuration Migration Helper
 * Handles migration from WordPress options to wp_edubot_api_integrations table
 */
class EduBot_API_Migration {

    /**
     * WordPress database handler
     */
    private static $wpdb;

    /**
     * API options that need to be migrated
     */
    private static $email_options = array(
        'edubot_email_service',
        'edubot_email_from_address',
        'edubot_email_from_name',
        'edubot_email_api_key',
        'edubot_email_domain',
    );

    private static $sms_options = array(
        'edubot_sms_provider',
        'edubot_sms_api_key',
        'edubot_sms_sender_id',
    );

    private static $whatsapp_options = array(
        'edubot_whatsapp_provider',
        'edubot_whatsapp_token',
        'edubot_whatsapp_phone_id',
        'edubot_whatsapp_template_namespace',
        'edubot_whatsapp_template_name',
        'edubot_whatsapp_template_language',
    );

    /**
     * Initialize migration
     */
    public static function init() {
        self::$wpdb = $GLOBALS['wpdb'];
    }

    /**
     * Migrate API settings from WordPress options to database table
     * 
     * @param int $site_id Site ID (default: 1)
     * @return array Migration result with status and details
     */
    public static function migrate_api_settings($site_id = 1) {
        self::init();

        $result = array(
            'success' => false,
            'message' => '',
            'migrated_fields' => array(),
            'errors' => array(),
        );

        try {
            // Check if API integrations table exists
            $table = self::$wpdb->prefix . 'edubot_api_integrations';
            if (!self::table_exists($table)) {
                $result['errors'][] = 'API integrations table does not exist';
                return $result;
            }

            // Start transaction
            self::$wpdb->query('START TRANSACTION');

            // Get or create API config record
            $api_config = self::$wpdb->get_row(
                self::$wpdb->prepare("SELECT id FROM $table WHERE site_id = %d", $site_id),
                OBJECT
            );

            if (!$api_config) {
                // Create new record
                $insert_result = self::$wpdb->insert(
                    $table,
                    array('site_id' => $site_id, 'status' => 'active'),
                    array('%d', '%s')
                );

                if ($insert_result === false) {
                    self::$wpdb->query('ROLLBACK');
                    $result['errors'][] = 'Failed to create API config record: ' . self::$wpdb->last_error;
                    return $result;
                }

                $config_id = self::$wpdb->insert_id;
            } else {
                $config_id = $api_config->id;
            }

            // Migrate email settings
            $email_config = array();
            foreach (self::$email_options as $option) {
                $value = get_option($option, '');
                if (!empty($value)) {
                    $db_column = str_replace('edubot_email_', '', $option);
                    if ($db_column === 'service') {
                        $db_column = 'email_provider';
                    } else {
                        $db_column = 'email_' . $db_column;
                    }
                    $email_config[$option] = array(
                        'db_column' => $db_column,
                        'value' => $value,
                    );
                    $result['migrated_fields'][] = $option;
                }
            }

            // Update email settings in table
            if (!empty($email_config)) {
                $update_data = array();
                $update_format = array();

                foreach ($email_config as $option => $data) {
                    $db_column = $data['db_column'];
                    $update_data[$db_column] = $data['value'];
                    $update_format[] = '%s';
                }

                if (!empty($update_data)) {
                    $update_data['updated_at'] = current_time('mysql');
                    $update_format[] = '%s';

                    $update_result = self::$wpdb->update(
                        $table,
                        $update_data,
                        array('id' => $config_id),
                        $update_format,
                        array('%d')
                    );

                    if ($update_result === false) {
                        self::$wpdb->query('ROLLBACK');
                        $result['errors'][] = 'Failed to update email settings: ' . self::$wpdb->last_error;
                        return $result;
                    }
                }
            }

            // Migrate SMS settings
            $sms_config = array();
            foreach (self::$sms_options as $option) {
                $value = get_option($option, '');
                if (!empty($value)) {
                    $db_column = str_replace('edubot_sms_', '', $option);
                    $db_column = 'sms_' . $db_column;
                    $sms_config[$option] = array(
                        'db_column' => $db_column,
                        'value' => $value,
                    );
                    $result['migrated_fields'][] = $option;
                }
            }

            // Update SMS settings in table
            if (!empty($sms_config)) {
                $update_data = array();
                $update_format = array();

                foreach ($sms_config as $option => $data) {
                    $db_column = $data['db_column'];
                    $update_data[$db_column] = $data['value'];
                    $update_format[] = '%s';
                }

                if (!empty($update_data)) {
                    $update_data['updated_at'] = current_time('mysql');
                    $update_format[] = '%s';

                    $update_result = self::$wpdb->update(
                        $table,
                        $update_data,
                        array('id' => $config_id),
                        $update_format,
                        array('%d')
                    );

                    if ($update_result === false) {
                        self::$wpdb->query('ROLLBACK');
                        $result['errors'][] = 'Failed to update SMS settings: ' . self::$wpdb->last_error;
                        return $result;
                    }
                }
            }

            // Migrate WhatsApp settings
            $whatsapp_config = array();
            foreach (self::$whatsapp_options as $option) {
                $value = get_option($option, '');
                if (!empty($value)) {
                    $db_column = str_replace('edubot_whatsapp_', '', $option);
                    $db_column = 'whatsapp_' . $db_column;
                    $whatsapp_config[$option] = array(
                        'db_column' => $db_column,
                        'value' => $value,
                    );
                    $result['migrated_fields'][] = $option;
                }
            }

            // Update WhatsApp settings in table
            if (!empty($whatsapp_config)) {
                $update_data = array();
                $update_format = array();

                foreach ($whatsapp_config as $option => $data) {
                    $db_column = $data['db_column'];
                    $update_data[$db_column] = $data['value'];
                    $update_format[] = '%s';
                }

                if (!empty($update_data)) {
                    $update_data['updated_at'] = current_time('mysql');
                    $update_format[] = '%s';

                    $update_result = self::$wpdb->update(
                        $table,
                        $update_data,
                        array('id' => $config_id),
                        $update_format,
                        array('%d')
                    );

                    if ($update_result === false) {
                        self::$wpdb->query('ROLLBACK');
                        $result['errors'][] = 'Failed to update WhatsApp settings: ' . self::$wpdb->last_error;
                        return $result;
                    }
                }
            }

            // Commit transaction
            self::$wpdb->query('COMMIT');

            $result['success'] = true;
            $result['message'] = sprintf(
                'Successfully migrated %d settings to wp_edubot_api_integrations table',
                count($result['migrated_fields'])
            );

            // Log migration
            error_log('EduBot API Migration: ' . $result['message']);
            error_log('EduBot API Migration: Migrated fields: ' . implode(', ', $result['migrated_fields']));

        } catch (Exception $e) {
            self::$wpdb->query('ROLLBACK');
            $result['errors'][] = 'Migration exception: ' . $e->getMessage();
            error_log('EduBot API Migration Error: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Check if migration is needed (has options but no table record)
     * 
     * @param int $site_id Site ID (default: 1)
     * @return bool True if migration is needed
     */
    public static function migration_needed($site_id = 1) {
        self::init();

        // Check if any email/SMS/WhatsApp options exist
        $all_options = array_merge(self::$email_options, self::$sms_options, self::$whatsapp_options);
        $has_options = false;

        foreach ($all_options as $option) {
            if (!empty(get_option($option))) {
                $has_options = true;
                break;
            }
        }

        if (!$has_options) {
            return false;
        }

        // Check if already in table
        $table = self::$wpdb->prefix . 'edubot_api_integrations';
        $record = self::$wpdb->get_row(
            self::$wpdb->prepare(
                "SELECT email_provider, sms_provider, whatsapp_provider FROM $table WHERE site_id = %d",
                $site_id
            ),
            OBJECT
        );

        // Migration needed if options exist but table record doesn't have them
        if (!$record) {
            return true;
        }

        // Check if any critical fields are missing in table but exist in options
        if ((get_option('edubot_email_provider') && !$record->email_provider) ||
            (get_option('edubot_sms_provider') && !$record->sms_provider) ||
            (get_option('edubot_whatsapp_provider') && !$record->whatsapp_provider)) {
            return true;
        }

        return false;
    }

    /**
     * Get API settings from table
     * Falls back to WordPress options if table has no data
     * 
     * @param int $site_id Site ID (default: 1)
     * @return array API settings array
     */
    public static function get_api_settings($site_id = 1) {
        self::init();

        $table = self::$wpdb->prefix . 'edubot_api_integrations';
        $settings = array();

        // Try to get from table first
        $record = self::$wpdb->get_row(
            self::$wpdb->prepare("SELECT * FROM $table WHERE site_id = %d", $site_id),
            OBJECT
        );

        if ($record) {
            // Email settings
            $settings['email_provider'] = $record->email_provider ?? get_option('edubot_email_service', 'zeptomail');
            $settings['email_from_address'] = $record->email_from_address ?? get_option('edubot_email_from_address', '');
            $settings['email_from_name'] = $record->email_from_name ?? get_option('edubot_email_from_name', '');
            $settings['email_api_key'] = $record->email_api_key ?? get_option('edubot_email_api_key', '');
            $settings['email_domain'] = $record->email_domain ?? get_option('edubot_email_domain', '');

            // SMS settings
            $settings['sms_provider'] = $record->sms_provider ?? get_option('edubot_sms_provider', '');
            $settings['sms_api_key'] = $record->sms_api_key ?? get_option('edubot_sms_api_key', '');
            $settings['sms_sender_id'] = $record->sms_sender_id ?? get_option('edubot_sms_sender_id', '');

            // WhatsApp settings
            $settings['whatsapp_provider'] = $record->whatsapp_provider ?? get_option('edubot_whatsapp_provider', '');
            $settings['whatsapp_token'] = $record->whatsapp_token ?? get_option('edubot_whatsapp_token', '');
            $settings['whatsapp_phone_id'] = $record->whatsapp_phone_id ?? get_option('edubot_whatsapp_phone_id', '');
        } else {
            // Fallback to WordPress options
            $settings['email_provider'] = get_option('edubot_email_service', 'zeptomail');
            $settings['email_from_address'] = get_option('edubot_email_from_address', '');
            $settings['email_from_name'] = get_option('edubot_email_from_name', '');
            $settings['email_api_key'] = get_option('edubot_email_api_key', '');
            $settings['email_domain'] = get_option('edubot_email_domain', '');

            $settings['sms_provider'] = get_option('edubot_sms_provider', '');
            $settings['sms_api_key'] = get_option('edubot_sms_api_key', '');
            $settings['sms_sender_id'] = get_option('edubot_sms_sender_id', '');

            $settings['whatsapp_provider'] = get_option('edubot_whatsapp_provider', '');
            $settings['whatsapp_token'] = get_option('edubot_whatsapp_token', '');
            $settings['whatsapp_phone_id'] = get_option('edubot_whatsapp_phone_id', '');
        }

        return $settings;
    }

    /**
     * Save API settings to table (new method for table storage)
     * 
     * @param array $settings API settings to save
     * @param int $site_id Site ID (default: 1)
     * @return bool True if successful
     */
    public static function save_api_settings($settings, $site_id = 1) {
        self::init();

        $table = self::$wpdb->prefix . 'edubot_api_integrations';

        // Check if record exists
        $record = self::$wpdb->get_row(
            self::$wpdb->prepare("SELECT id FROM $table WHERE site_id = %d", $site_id),
            OBJECT
        );

        $update_data = array(
            'updated_at' => current_time('mysql'),
        );

        // Map settings to table columns
        if (isset($settings['email_provider'])) {
            $update_data['email_provider'] = $settings['email_provider'];
        }
        if (isset($settings['email_from_address'])) {
            $update_data['email_from_address'] = $settings['email_from_address'];
        }
        if (isset($settings['email_from_name'])) {
            $update_data['email_from_name'] = $settings['email_from_name'];
        }
        if (isset($settings['email_api_key'])) {
            $update_data['email_api_key'] = $settings['email_api_key'];
        }
        if (isset($settings['email_domain'])) {
            $update_data['email_domain'] = $settings['email_domain'];
        }

        if (isset($settings['sms_provider'])) {
            $update_data['sms_provider'] = $settings['sms_provider'];
        }
        if (isset($settings['sms_api_key'])) {
            $update_data['sms_api_key'] = $settings['sms_api_key'];
        }
        if (isset($settings['sms_sender_id'])) {
            $update_data['sms_sender_id'] = $settings['sms_sender_id'];
        }

        if (isset($settings['whatsapp_provider'])) {
            $update_data['whatsapp_provider'] = $settings['whatsapp_provider'];
        }
        if (isset($settings['whatsapp_token'])) {
            $update_data['whatsapp_token'] = $settings['whatsapp_token'];
        }
        if (isset($settings['whatsapp_phone_id'])) {
            $update_data['whatsapp_phone_id'] = $settings['whatsapp_phone_id'];
        }

        if ($record) {
            // Update existing
            $result = self::$wpdb->update(
                $table,
                $update_data,
                array('id' => $record->id),
                array_fill(0, count($update_data), '%s'),
                array('%d')
            );
        } else {
            // Create new
            $update_data['site_id'] = $site_id;
            $update_data['status'] = 'active';
            $result = self::$wpdb->insert(
                $table,
                $update_data,
                array_fill(0, count($update_data), '%s')
            );
        }

        if ($result === false) {
            error_log('EduBot: Failed to save API settings to table: ' . self::$wpdb->last_error);
            return false;
        }

        return true;
    }

    /**
     * Check if table exists
     */
    private static function table_exists($table) {
        $result = self::$wpdb->query("SHOW TABLES LIKE '$table'");
        return $result == 1;
    }
}
