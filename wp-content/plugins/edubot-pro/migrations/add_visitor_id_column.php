<?php
/**
 * Migration: Add visitor_id column to wp_edubot_visitors table
 * 
 * This migration adds the missing visitor_id column to the existing visitors table
 * It should be run manually if the plugin was already installed before this update
 * 
 * Usage: Include this file in WordPress to run the migration
 * Or access: wp-admin and deactivate/reactivate the plugin to trigger automatic migration
 */

// If this file is being run directly from WordPress
if (!defined('ABSPATH')) {
    // Load WordPress if needed
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
}

global $wpdb;

$visitors_table = $wpdb->prefix . 'edubot_visitors';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$visitors_table}'") === $visitors_table;

if (!$table_exists) {
    echo "Error: Table {$visitors_table} does not exist. Plugin may not be activated.";
    exit;
}

// Check if visitor_id column already exists
$has_visitor_id = $wpdb->get_var("SHOW COLUMNS FROM {$visitors_table} LIKE 'visitor_id'");

if ($has_visitor_id) {
    echo "Success: visitor_id column already exists in {$visitors_table}";
    exit;
}

// Generate visitor_id for all existing records
echo "Starting migration...\n";

// First, add the column with a temporary default (will be updated)
$result = $wpdb->query("ALTER TABLE {$visitors_table} ADD COLUMN visitor_id varchar(255) UNIQUE DEFAULT NULL AFTER id");

if ($result === false) {
    echo "Error adding column: " . $wpdb->last_error;
    exit;
}

echo "Step 1: Column added (with NULL default)\n";

// Now generate unique visitor_id for each existing row
$records = $wpdb->get_results("SELECT id, ip_address, user_agent FROM {$visitors_table} WHERE visitor_id IS NULL");

if (empty($records)) {
    echo "No records to update\n";
} else {
    $updated = 0;
    foreach ($records as $record) {
        // Generate visitor_id based on IP and user agent
        $visitor_id = md5($record->ip_address . '-' . substr($record->user_agent, 0, 50) . '-' . $record->id);
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$visitors_table} SET visitor_id = %s WHERE id = %d",
            $visitor_id,
            $record->id
        ));
        
        $updated++;
    }
    
    echo "Step 2: Updated {$updated} records with visitor_id\n";
}

// Make the column NOT NULL and ensure it has a unique constraint
$wpdb->query("ALTER TABLE {$visitors_table} MODIFY COLUMN visitor_id varchar(255) NOT NULL");

echo "Step 3: Column modified to NOT NULL\n";

// Check if unique constraint exists
$constraints = $wpdb->get_results("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = '{$visitors_table}' AND COLUMN_NAME = 'visitor_id'");

if (empty($constraints)) {
    // Add unique constraint if it doesn't exist
    $wpdb->query("ALTER TABLE {$visitors_table} ADD UNIQUE KEY unique_visitor_id (visitor_id)");
    echo "Step 4: Unique constraint added\n";
} else {
    echo "Step 4: Unique constraint already exists\n";
}

echo "Migration completed successfully!\n";
echo "The visitor_id column has been added to {$visitors_table}\n";
