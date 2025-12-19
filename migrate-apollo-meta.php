<?php

// phpcs:ignoreFile
/**
 * Migration Script: migrate old wrong meta keys to correct ones
 * Run via CLI or browser (admin only)
 */

// wp-load is located at app/public/wp-load.php; plugin __DIR__ is .../wp-content/plugins/apollo-events-manager
require_once dirname(__DIR__, 3) . '/wp-load.php';

if (php_sapi_name() !== 'cli') {
    if (! current_user_can('administrator')) {
        die('Access denied - Admin only');
    }
}

echo '<pre>Migration started: ' . date('Y-m-d H:i:s') . "\n";

$processed = [
    'djs_migrated'       => 0,
    'locals_migrated'    => 0,
    'timetable_migrated' => 0,
    'errors'             => [],
];

$events = get_posts(
    [
        'post_type'      => 'event_listing',
        'posts_per_page' => -1,
        'post_status'    => 'any',
    ]
);

foreach ($events as $event) {
    $id = $event->ID;

    // 1) DJs
    if (metadata_exists('post', $id, '_event_djs') && ! metadata_exists('post', $id, '_event_dj_ids')) {
        $old = get_post_meta($id, '_event_djs', true);
        // Normalize to array of strings
        $arr = (array) $old;
        $arr = array_map('intval', $arr);
        $arr = array_map('strval', $arr);
        $ok  = update_post_meta($id, '_event_dj_ids', $arr);
        if ($ok !== false) {
            ++$processed['djs_migrated'];
            echo "Migrated DJs for post $id -> _event_dj_ids (" . count($arr) . " IDs)\n";
            // optional: delete old key
            delete_post_meta($id, '_event_djs');
        } else {
            $processed['errors'][] = "Failed migrate DJs for $id";
        }
    }

    // 2) Local
    if (metadata_exists('post', $id, '_event_local') && ! metadata_exists('post', $id, '_event_local_ids')) {
        $old = get_post_meta($id, '_event_local', true);
        if (is_numeric($old)) {
            $ok = update_post_meta($id, '_event_local_ids', intval($old));
            if ($ok !== false) {
                ++$processed['locals_migrated'];
                echo "Migrated Local for post $id -> _event_local_ids ($old)\n";
                delete_post_meta($id, '_event_local');
            } else {
                $processed['errors'][] = "Failed migrate local for $id";
            }
        } else {
            // if the old is non-numeric, skip but note
            $processed['errors'][] = "_event_local for $id not numeric, skipped";
        }
    }

    // 3) Timetable
    if (metadata_exists('post', $id, '_timetable') && ! metadata_exists('post', $id, '_event_timetable')) {
        $old = get_post_meta($id, '_timetable', true);
        // If old is numeric, skip. If array or serialized array, migrate
        if (is_array($old)) {
            $ok = update_post_meta($id, '_event_timetable', $old);
            if ($ok !== false) {
                ++$processed['timetable_migrated'];
                echo "Migrated timetable array for post $id -> _event_timetable\n";
                delete_post_meta($id, '_timetable');
            } else {
                $processed['errors'][] = "Failed migrate timetable array for $id";
            }
        } elseif (is_string($old) && strpos($old, 'a:') === 0) {
            $maybe = @unserialize($old);
            if (is_array($maybe)) {
                $ok = update_post_meta($id, '_event_timetable', $maybe);
                if ($ok !== false) {
                    ++$processed['timetable_migrated'];
                    echo "Migrated timetable serialized for post $id -> _event_timetable\n";
                    delete_post_meta($id, '_timetable');
                } else {
                    $processed['errors'][] = "Failed migrate timetable serialized for $id";
                }
            } else {
                $processed['errors'][] = "_timetable for $id is string but not serialized array, skipped";
            }
        } else {
            $processed['errors'][] = "_timetable for $id is numeric or unknown, skipped";
        }//end if
    }//end if
}//end foreach

// Clear object cache if possible
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "Cache flushed\n";
}

echo "\nSummary:\n";
echo 'DJs migrated: ' . $processed['djs_migrated'] . "\n";
echo 'Locals migrated: ' . $processed['locals_migrated'] . "\n";
echo 'Timetables migrated: ' . $processed['timetable_migrated'] . "\n";
if (! empty($processed['errors'])) {
    echo "Errors:\n" . implode("\n", $processed['errors']) . "\n";
}

echo 'Migration finished: ' . date('Y-m-d H:i:s') . "\n";

return 0;
