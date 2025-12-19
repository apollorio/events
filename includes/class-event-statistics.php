<?php

// phpcs:ignoreFile
/**
 * Event Statistics Class
 *
 * Tracks event views (popup and page) and provides statistics
 *
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

class Apollo_Event_Statistics
{
    /**
     * Track event view
     *
     * @param int    $event_id Event post ID
     * @param string $type View type: 'popup' or 'page'
     * @return bool Success
     */
    public static function track_event_view($event_id, $type = 'page')
    {
        $event_id = absint($event_id);
        if (! $event_id) {
            return false;
        }

        $type = in_array($type, [ 'popup', 'page' ]) ? $type : 'page';

        // Use WordPress meta to store statistics
        $meta_key = '_apollo_event_stats';
        $stats    = get_post_meta($event_id, $meta_key, true);

        if (! is_array($stats)) {
            $stats = [
                'popup_count'  => 0,
                'page_count'   => 0,
                'total_views'  => 0,
                'last_updated' => current_time('mysql'),
            ];
        }

        // Increment appropriate counter
        if ($type === 'popup') {
            $stats['popup_count'] = isset($stats['popup_count']) ? (int) $stats['popup_count'] + 1 : 1;
        } else {
            $stats['page_count'] = isset($stats['page_count']) ? (int) $stats['page_count'] + 1 : 1;
        }

        $stats['total_views']  = (int) ($stats['popup_count'] ?? 0) + (int) ($stats['page_count'] ?? 0);
        $stats['last_updated'] = current_time('mysql');

        return update_post_meta($event_id, $meta_key, $stats);
    }

    /**
     * Get event statistics
     *
     * @param int $event_id Event post ID
     * @return array Statistics array with popup_count, page_count, total_views
     */
    public static function get_event_stats($event_id)
    {
        $event_id = absint($event_id);
        if (! $event_id) {
            return [
                'popup_count' => 0,
                'page_count'  => 0,
                'total_views' => 0,
            ];
        }

        $meta_key = '_apollo_event_stats';
        $stats    = get_post_meta($event_id, $meta_key, true);

        if (! is_array($stats)) {
            return [
                'popup_count' => 0,
                'page_count'  => 0,
                'total_views' => 0,
            ];
        }

        return [
            'popup_count'  => isset($stats['popup_count']) ? (int) $stats['popup_count'] : 0,
            'page_count'   => isset($stats['page_count']) ? (int) $stats['page_count'] : 0,
            'total_views'  => isset($stats['total_views']) ? (int) $stats['total_views'] : 0,
            'last_updated' => isset($stats['last_updated']) ? $stats['last_updated'] : '',
        ];
    }

    /**
     * Get statistics for multiple events
     *
     * @param array $event_ids Array of event IDs
     * @return array Associative array with event_id as key
     */
    public static function get_multiple_event_stats($event_ids)
    {
        if (! is_array($event_ids) || empty($event_ids)) {
            return [];
        }

        $results = [];
        foreach ($event_ids as $event_id) {
            $event_id = absint($event_id);
            if ($event_id) {
                $results[ $event_id ] = self::get_event_stats($event_id);
            }
        }

        return $results;
    }
}
