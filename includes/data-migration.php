<?php

// phpcs:ignoreFile
/**
 * Apollo Events Manager - Data Migration Utilities
 *
 * Handles migration of inconsistent meta keys and data structures
 * Run via WP-CLI or admin maintenance page
 *
 * @package Apollo_Events_Manager
 * @version 2.0.1
 */

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

class Apollo_Data_Migration
{
    /**
     * Migration flag option name
     */
    public const MIGRATION_FLAG = 'apollo_data_migration_v2_completed';

    /**
     * Check if migration has been run
     */
    public static function is_migration_completed()
    {
        return get_option(self::MIGRATION_FLAG, false);
    }

    /**
     * Mark migration as completed
     */
    public static function mark_migration_completed()
    {
        update_option(self::MIGRATION_FLAG, true);
    }

    /**
     * Run all migrations
     *
     * @return array Results with counts and errors
     */
    public static function run_all_migrations()
    {
        $results = [
            'meta_keys'    => self::migrate_meta_keys(),
            'timetable'    => self::migrate_timetable_structure(),
            'local_coords' => self::migrate_local_coordinates(),
        ];

        self::mark_migration_completed();

        return $results;
    }

    /**
     * Migrate inconsistent meta keys to standard format
     *
     * Standard keys:
     * - _event_local_ids (not _event_local)
     * - _local_latitude (not _local_lat)
     * - _local_longitude (not _local_lng)
     */
    public static function migrate_meta_keys()
    {
        global $wpdb;

        $results = [
            'events_updated' => 0,
            'locals_updated' => 0,
            'errors'         => [],
        ];

        // Migrate _event_local -> _event_local_ids
        $events_with_old_key = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id, meta_value 
                 FROM {$wpdb->postmeta} 
                 WHERE meta_key = %s 
                 AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)",
                '_event_local',
                'event_listing'
            )
        );

        foreach ($events_with_old_key as $row) {
            $new_value_exists = get_post_meta($row->post_id, '_event_local_ids', true);

            // Only migrate if new key doesn't exist
            if (empty($new_value_exists)) {
                update_post_meta($row->post_id, '_event_local_ids', intval($row->meta_value));
                ++$results['events_updated'];
            }

            // Keep old key for backward compatibility (don't delete)
        }

        // Migrate _local_lat -> _local_latitude
        $locals_with_old_lat = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id, meta_value 
                 FROM {$wpdb->postmeta} 
                 WHERE meta_key = %s 
                 AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)",
                '_local_lat',
                'event_local'
            )
        );

        foreach ($locals_with_old_lat as $row) {
            $new_value_exists = get_post_meta($row->post_id, '_local_latitude', true);

            if (empty($new_value_exists)) {
                update_post_meta($row->post_id, '_local_latitude', $row->meta_value);
                ++$results['locals_updated'];
            }
        }

        // Migrate _local_lng -> _local_longitude
        $locals_with_old_lng = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id, meta_value 
                 FROM {$wpdb->postmeta} 
                 WHERE meta_key = %s 
                 AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)",
                '_local_lng',
                'event_local'
            )
        );

        foreach ($locals_with_old_lng as $row) {
            $new_value_exists = get_post_meta($row->post_id, '_local_longitude', true);

            if (empty($new_value_exists)) {
                update_post_meta($row->post_id, '_local_longitude', $row->meta_value);
                ++$results['locals_updated'];
            }
        }

        return $results;
    }

    /**
     * Migrate timetable to standardized structure
     *
     * Standard format:
     * array(
     *   array('dj' => int, 'start' => 'HH:MM', 'end' => 'HH:MM')
     * )
     */
    public static function migrate_timetable_structure()
    {
        $results = [
            'events_checked'  => 0,
            'events_migrated' => 0,
            'errors'          => [],
        ];

        $events = get_posts(
            [
                'post_type'      => 'event_listing',
                'posts_per_page' => -1,
                'post_status'    => 'any',
            ]
        );

        foreach ($events as $event) {
            ++$results['events_checked'];

            $timetable = get_post_meta($event->ID, '_event_timetable', true);

            // Skip if empty or not array
            if (empty($timetable) || ! is_array($timetable)) {
                continue;
            }

            $needs_migration    = false;
            $migrated_timetable = [];

            foreach ($timetable as $slot) {
                if (! is_array($slot)) {
                    continue;
                }

                $new_slot = [];

                // Normalize DJ field
                if (isset($slot['dj'])) {
                    $new_slot['dj'] = intval($slot['dj']);
                } else {
                    continue;
                    // Skip invalid slots
                }

                // Normalize time fields (multiple variations)
                if (isset($slot['start'])) {
                    $new_slot['start'] = sanitize_text_field($slot['start']);
                } elseif (isset($slot['time_start'])) {
                    $new_slot['start'] = sanitize_text_field($slot['time_start']);
                    $needs_migration   = true;
                } elseif (isset($slot['dj_time_in'])) {
                    $new_slot['start'] = sanitize_text_field($slot['dj_time_in']);
                    $needs_migration   = true;
                } else {
                    $new_slot['start'] = '';
                }

                if (isset($slot['end'])) {
                    $new_slot['end'] = sanitize_text_field($slot['end']);
                } elseif (isset($slot['time_end'])) {
                    $new_slot['end'] = sanitize_text_field($slot['time_end']);
                    $needs_migration = true;
                } elseif (isset($slot['dj_time_out'])) {
                    $new_slot['end'] = sanitize_text_field($slot['dj_time_out']);
                    $needs_migration = true;
                } else {
                    $new_slot['end'] = '';
                }

                $migrated_timetable[] = $new_slot;
            }//end foreach

            // Only update if structure changed
            if ($needs_migration && ! empty($migrated_timetable)) {
                // Sort by start time
                usort(
                    $migrated_timetable,
                    function ($a, $b) {
                        return strcmp($a['start'], $b['start']);
                    }
                );

                update_post_meta($event->ID, '_event_timetable', $migrated_timetable);
                ++$results['events_migrated'];
            }
        }//end foreach

        return $results;
    }

    /**
     * Migrate local coordinates to ensure proper format
     */
    public static function migrate_local_coordinates()
    {
        $results = [
            'locals_checked' => 0,
            'locals_fixed'   => 0,
            'errors'         => [],
        ];

        $locals = get_posts(
            [
                'post_type'      => 'event_local',
                'posts_per_page' => -1,
                'post_status'    => 'any',
            ]
        );

        foreach ($locals as $local) {
            ++$results['locals_checked'];

            $lat = get_post_meta($local->ID, '_local_latitude', true);
            $lng = get_post_meta($local->ID, '_local_longitude', true);

            $fixed = false;

            // Ensure coordinates are numeric strings
            if (! empty($lat) && ! is_numeric($lat)) {
                update_post_meta($local->ID, '_local_latitude', floatval($lat));
                $fixed = true;
            }

            if (! empty($lng) && ! is_numeric($lng)) {
                update_post_meta($local->ID, '_local_longitude', floatval($lng));
                $fixed = true;
            }

            if ($fixed) {
                ++$results['locals_fixed'];
            }
        }//end foreach

        return $results;
    }

    /**
     * Get migration status report
     */
    public static function get_migration_status()
    {
        global $wpdb;

        $status = [
            'completed' => self::is_migration_completed(),
            'stats'     => [],
        ];

        // Count events with old meta keys
        $status['stats']['events_with_old_local_key'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT post_id) 
                 FROM {$wpdb->postmeta} 
                 WHERE meta_key = %s 
                 AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)",
                '_event_local',
                'event_listing'
            )
        );

        // Count locals with old coordinate keys
        $status['stats']['locals_with_old_lat'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT post_id) 
                 FROM {$wpdb->postmeta} 
                 WHERE meta_key = %s 
                 AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)",
                '_local_lat',
                'event_local'
            )
        );

        $status['stats']['locals_with_old_lng'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT post_id) 
                 FROM {$wpdb->postmeta} 
                 WHERE meta_key = %s 
                 AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)",
                '_local_lng',
                'event_local'
            )
        );

        // Count total events and locals
        $status['stats']['total_events'] = wp_count_posts('event_listing')->publish;
        $status['stats']['total_locals'] = wp_count_posts('event_local')->publish;

        return $status;
    }
}

/**
 * WP-CLI Command for running migrations
 *
 * Usage:
 *   wp apollo migrate run
 *   wp apollo migrate status
 *   wp apollo migrate reset
 */
if (defined('WP_CLI') && WP_CLI) {
    class Apollo_Migration_CLI_Command
    {
        /**
         * Run all data migrations
         *
         * ## EXAMPLES
         *
         *     wp apollo migrate run
         */
        public function run($args, $assoc_args)
        {
            WP_CLI::log('Starting Apollo data migration...');

            $results = Apollo_Data_Migration::run_all_migrations();

            WP_CLI::success('Migration completed!');
            WP_CLI::log('');
            WP_CLI::log('Meta Keys Migration:');
            WP_CLI::log('  - Events updated: ' . $results['meta_keys']['events_updated']);
            WP_CLI::log('  - Locals updated: ' . $results['meta_keys']['locals_updated']);
            WP_CLI::log('');
            WP_CLI::log('Timetable Migration:');
            WP_CLI::log('  - Events checked: ' . $results['timetable']['events_checked']);
            WP_CLI::log('  - Events migrated: ' . $results['timetable']['events_migrated']);
            WP_CLI::log('');
            WP_CLI::log('Coordinates Migration:');
            WP_CLI::log('  - Locals checked: ' . $results['local_coords']['locals_checked']);
            WP_CLI::log('  - Locals fixed: ' . $results['local_coords']['locals_fixed']);
        }

        /**
         * Show migration status
         *
         * ## EXAMPLES
         *
         *     wp apollo migrate status
         */
        public function status($args, $assoc_args)
        {
            $status = Apollo_Data_Migration::get_migration_status();

            WP_CLI::log('Apollo Migration Status:');
            WP_CLI::log('  Completed: ' . ($status['completed'] ? 'YES' : 'NO'));
            WP_CLI::log('');
            WP_CLI::log('Items needing migration:');
            WP_CLI::log('  - Events with old _event_local key: ' . $status['stats']['events_with_old_local_key']);
            WP_CLI::log('  - Locals with old _local_lat key: ' . $status['stats']['locals_with_old_lat']);
            WP_CLI::log('  - Locals with old _local_lng key: ' . $status['stats']['locals_with_old_lng']);
            WP_CLI::log('');
            WP_CLI::log('Totals:');
            WP_CLI::log('  - Total events: ' . $status['stats']['total_events']);
            WP_CLI::log('  - Total locals: ' . $status['stats']['total_locals']);
        }

        /**
         * Reset migration flag
         *
         * ## EXAMPLES
         *
         *     wp apollo migrate reset
         */
        public function reset($args, $assoc_args)
        {
            delete_option(Apollo_Data_Migration::MIGRATION_FLAG);
            WP_CLI::success('Migration flag reset. You can now run migration again.');
        }
    }

    WP_CLI::add_command('apollo migrate', 'Apollo_Migration_CLI_Command');
}//end if
