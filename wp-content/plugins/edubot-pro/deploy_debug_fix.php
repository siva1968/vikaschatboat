<?php
/**
 * Deploy Debug Fix for MCB Preview Button
 * 
 * This script copies the updated class-edubot-mcb-admin.php file to the WordPress installation
 * to add debugging for the $application array issue
 */

// Configuration
$source_file = __DIR__ . '/includes/class-edubot-mcb-admin.php';
$wordpress_root = 'D:/xampp/htdocs/demo'; // WordPress root directory
$dest_file = $wordpress_root . '/wp-content/plugins/edubot-pro/includes/class-edubot-mcb-admin.php';

echo "=== MCB Preview Button Debug Deployment ===\n\n";

// Verify source file exists
if (!file_exists($source_file)) {
    echo "❌ ERROR: Source file not found: $source_file\n";
    exit(1);
}
echo "✓ Source file found: $source_file\n";

// Verify destination directory exists
$dest_dir = dirname($dest_file);
if (!is_dir($dest_dir)) {
    echo "❌ ERROR: Destination directory not found: $dest_dir\n";
    exit(1);
}
echo "✓ Destination directory exists: $dest_dir\n";

// Check if destination file exists
if (file_exists($dest_file)) {
    echo "✓ Destination file exists (will be overwritten)\n";
    // Create backup
    $backup_file = $dest_file . '.backup.' . date('YmdHis');
    if (copy($dest_file, $backup_file)) {
        echo "✓ Backup created: $backup_file\n";
    } else {
        echo "⚠ Warning: Could not create backup\n";
    }
} else {
    echo "✓ Destination file does not exist (will be created)\n";
}

// Copy the file
if (copy($source_file, $dest_file)) {
    echo "\n✅ SUCCESS: File deployed successfully!\n";
    echo "✓ Destination: $dest_file\n";
    echo "\n📋 Next steps:\n";
    echo "1. Go to WordPress admin\n";
    echo "2. Navigate to Applications page\n";
    echo "3. Scroll down and click on any 'Preview' button\n";
    echo "4. Check WordPress debug.log for MCB debug output\n";
    echo "5. Debug log location: " . $wordpress_root . "/wp-content/debug.log\n";
} else {
    echo "\n❌ ERROR: Failed to copy file\n";
    exit(1);
}

echo "\n=== Deployment Complete ===\n";
?>
