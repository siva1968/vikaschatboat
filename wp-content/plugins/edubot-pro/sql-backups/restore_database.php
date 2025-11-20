<?php
/**
 * EduBot Database Restore Script
 * Usage: php restore_database.php backup_filename.sql
 */

if ($argc < 2) {
    echo "Usage: php restore_database.php <backup_filename.sql>\n";
    echo "Example: php restore_database.php sql-backups/edubot-plugin-backup-2025-11-08.sql\n";
    exit(1);
}

$backup_file = $argv[1];

if (!file_exists($backup_file)) {
    echo "❌ Error: Backup file not found: $backup_file\n";
    exit(1);
}

require_once('wp-load.php');
global $wpdb;

echo "📂 Restoring from: $backup_file\n";
echo "📊 File size: " . number_format(filesize($backup_file) / 1024, 2) . " KB\n";

// Read SQL file
$sql_content = file_get_contents($backup_file);

if (empty($sql_content)) {
    echo "❌ Error: Backup file is empty\n";
    exit(1);
}

// Split by semicolon and execute
$queries = array_filter(array_map('trim', preg_split('/;[\r\n]/', $sql_content)));

echo "🔄 Executing " . count($queries) . " SQL statements...\n";

$executed = 0;
$errors = 0;

foreach ($queries as $query) {
    if (empty($query) || strpos($query, '--') === 0) {
        continue;
    }
    
    if ($wpdb->query($query)) {
        $executed++;
    } else {
        $errors++;
        error_log("SQL Error: " . $wpdb->last_error . " | Query: " . substr($query, 0, 100));
    }
}

echo "\n✅ Restore Complete!\n";
echo "📈 Executed: $executed statements\n";
echo "⚠️ Errors: $errors\n";

if ($errors === 0) {
    echo "\n✅ Database restored successfully!\n";
} else {
    echo "\n⚠️ Restore completed with errors. Check wp-content/debug.log for details.\n";
}
?>
