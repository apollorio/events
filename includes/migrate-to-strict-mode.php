<?php

// phpcs:ignoreFile
/**
 * Apollo Events Manager - Migration to Strict Mode
 *
 * Script to migrate all get_post_meta/update_post_meta calls to use sanitization
 * Run via WP-CLI: wp eval-file includes/migrate-to-strict-mode.php
 */

if (! defined('ABSPATH')) {
    require_once '../../../wp-load.php';
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "🔄 APOLLO EVENTS MANAGER - MIGRATION TO STRICT MODE\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

$files_to_migrate = [
    APOLLO_APRIO_PATH . 'apollo-events-manager.php',
    APOLLO_APRIO_PATH . 'includes/admin-metaboxes.php',
    APOLLO_APRIO_PATH . 'includes/event-helpers.php',
    APOLLO_APRIO_PATH . 'includes/ajax-handlers.php',
];

$stats = [
    'files_processed'           => 0,
    'get_post_meta_replaced'    => 0,
    'update_post_meta_replaced' => 0,
    'delete_post_meta_replaced' => 0,
    'errors'                    => [],
];

foreach ($files_to_migrate as $file) {
    if (! file_exists($file)) {
        $stats['errors'][] = "File not found: {$file}";

        continue;
    }

    echo '📄 Processing: ' . basename($file) . "\n";

    $content          = file_get_contents($file);
    $original_content = $content;

    // Replace get_post_meta (but not apollo_get_post_meta)
    $content = preg_replace(
        '/(?<!apollo_)get_post_meta\s*\(/',
        'apollo_get_post_meta(',
        $content
    );
    $get_count = substr_count($content, 'apollo_get_post_meta(') - substr_count($original_content, 'apollo_get_post_meta(');

    // Replace update_post_meta (but not apollo_update_post_meta)
    $content = preg_replace(
        '/(?<!apollo_)update_post_meta\s*\(/',
        'apollo_update_post_meta(',
        $content
    );
    $update_count = substr_count($content, 'apollo_update_post_meta(') - substr_count($original_content, 'apollo_update_post_meta(');

    // Replace delete_post_meta (but not apollo_delete_post_meta)
    $content = preg_replace(
        '/(?<!apollo_)delete_post_meta\s*\(/',
        'apollo_delete_post_meta(',
        $content
    );
    $delete_count = substr_count($content, 'apollo_delete_post_meta(') - substr_count($original_content, 'apollo_delete_post_meta(');

    if ($content !== $original_content) {
        // Backup original file
        $backup_file = $file . '.backup.' . date('Y-m-d-His');
        copy($file, $backup_file);

        // Write migrated content
        file_put_contents($file, $content);

        ++$stats['files_processed'];
        $stats['get_post_meta_replaced']    += $get_count;
        $stats['update_post_meta_replaced'] += $update_count;
        $stats['delete_post_meta_replaced'] += $delete_count;

        echo "   ✅ Migrated: {$get_count} get_post_meta, {$update_count} update_post_meta, {$delete_count} delete_post_meta\n";
        echo '   💾 Backup saved: ' . basename($backup_file) . "\n";
    } else {
        echo "   ⚠️ No changes needed\n";
    }

    echo "\n";
}//end foreach

// Summary
echo "════════════════════════════════════════════════════════════════\n";
echo "📊 MIGRATION SUMMARY\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";
echo "Files processed: {$stats['files_processed']}\n";
echo "get_post_meta() replaced: {$stats['get_post_meta_replaced']}\n";
echo "update_post_meta() replaced: {$stats['update_post_meta_replaced']}\n";
echo "delete_post_meta() replaced: {$stats['delete_post_meta_replaced']}\n";
echo "\n";

if (! empty($stats['errors'])) {
    echo "❌ ERRORS:\n";
    foreach ($stats['errors'] as $error) {
        echo "   {$error}\n";
    }
    echo "\n";
}

if ($stats['files_processed'] > 0) {
    echo "✅ Migration completed! Backups saved.\n";
    echo "⚠️ Please review the changes and test thoroughly.\n";
} else {
    echo "ℹ️ No files needed migration.\n";
}

echo "\n";
