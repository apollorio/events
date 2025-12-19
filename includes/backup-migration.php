<?php

// phpcs:ignoreFile
/**
 * Backup & Migration Helper
 * TODO 138: Backup & migration strategy (export/import, backup configs, migration helper, rollback)
 *
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

/**
 * Backup & migration helper class
 * TODO 138: Export/import, backup, migration, rollback
 */
class Apollo_Events_Backup_Migration
{
    /**
     * Export events
     * TODO 138: Export events
     *
     * @param array $event_ids Event IDs to export
     * @return string JSON export data
     */
    public static function export_events($event_ids = [])
    {
        $events = [];

        foreach ($event_ids as $event_id) {
            $event = get_post($event_id);
            if ($event && $event->post_type === 'event_listing') {
                $events[] = [
                    'id'      => $event_id,
                    'title'   => $event->post_title,
                    'content' => $event->post_content,
                    'meta'    => get_post_meta($event_id),
                ];
            }
        }

        return json_encode($events, JSON_PRETTY_PRINT);
    }

    /**
     * Import events
     * TODO 138: Import events
     *
     * @param string $json_data JSON import data
     * @return array Import results
     */
    public static function import_events($json_data)
    {
        $events  = json_decode($json_data, true);
        $results = [
            'success' => 0,
            'failed'  => 0,
        ];

        foreach ($events as $event_data) {
            $post_id = wp_insert_post(
                [
                    'post_title'   => $event_data['title'],
                    'post_content' => $event_data['content'],
                    'post_type'    => 'event_listing',
                    'post_status'  => 'publish',
                ]
            );

            if ($post_id && ! is_wp_error($post_id)) {
                foreach ($event_data['meta'] as $key => $value) {
                    update_post_meta($post_id, $key, $value);
                }
                ++$results['success'];
            } else {
                ++$results['failed'];
            }
        }

        return $results;
    }

    /**
     * Backup settings
     * TODO 138: Backup configurations
     *
     * @return string JSON backup data
     */
    public static function backup_settings()
    {
        $settings = [
            'version' => APOLLO_APRIO_VERSION,
            'options' => get_option('apollo_events_settings', []),
        ];

        return json_encode($settings, JSON_PRETTY_PRINT);
    }

    /**
     * Restore settings
     * TODO 138: Restore from backup
     *
     * @param string $json_data JSON backup data
     * @return bool Success
     */
    public static function restore_settings($json_data)
    {
        $settings = json_decode($json_data, true);

        if (isset($settings['options'])) {
            update_option('apollo_events_settings', $settings['options']);

            return true;
        }

        return false;
    }
}
