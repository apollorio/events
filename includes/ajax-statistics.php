<?php

// phpcs:ignoreFile
/**
 * AJAX Handlers for Event Statistics
 *
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

// Track event view via AJAX
add_action('wp_ajax_apollo_track_event_view', 'apollo_ajax_track_event_view');
add_action('wp_ajax_nopriv_apollo_track_event_view', 'apollo_ajax_track_event_view');

function apollo_ajax_track_event_view()
{
    // SECURITY: Verify nonce with proper unslashing
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (! wp_verify_nonce($nonce, 'apollo_track_event_view')) {
        wp_send_json_error([ 'message' => 'Invalid nonce' ]);

        return;
    }

    // SECURITY: Sanitize inputs
    $event_id = isset($_POST['event_id']) ? absint(wp_unslash($_POST['event_id'])) : 0;
    $type     = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : 'page';

    // SECURITY: Validate type against whitelist
    $allowed_types = [ 'popup', 'page', 'modal' ];
    if (! in_array($type, $allowed_types, true)) {
        $type = 'page';
    }

    if (! $event_id) {
        wp_send_json_error([ 'message' => 'Invalid event ID' ]);

        return;
    }

    // Verify event exists
    $event = get_post($event_id);
    if (! $event || $event->post_type !== 'event_listing') {
        wp_send_json_error([ 'message' => 'Event not found' ]);

        return;
    }

    // TODO: Before production, consider adding rate-limiting for this public telemetry endpoint.

    // Track view
    if (class_exists('Apollo_Event_Statistics')) {
        $result = Apollo_Event_Statistics::track_event_view($event_id, $type);
        if ($result) {
            $stats = Apollo_Event_Statistics::get_event_stats($event_id);
            wp_send_json_success(
                [
                    'message' => 'View tracked',
                    'stats'   => [
                        'popup_count'  => absint(isset($stats['popup_count']) ? $stats['popup_count'] : 0),
                        'page_count'   => absint(isset($stats['page_count']) ? $stats['page_count'] : 0),
                        'total_views'  => absint(isset($stats['total_views']) ? $stats['total_views'] : 0),
                        'last_updated' => sanitize_text_field(isset($stats['last_updated']) ? $stats['last_updated'] : ''),
                    ],
                ]
            );
        } else {
            wp_send_json_error([ 'message' => 'Failed to track view' ]);
        }
    } else {
        wp_send_json_error([ 'message' => 'Statistics class not available' ]);
    }
}

// Get event statistics via AJAX
add_action('wp_ajax_apollo_get_event_stats', 'apollo_ajax_get_event_stats');
add_action('wp_ajax_nopriv_apollo_get_event_stats', 'apollo_ajax_get_event_stats');

function apollo_ajax_get_event_stats()
{
    // SECURITY: Verify nonce with proper unslashing
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (! wp_verify_nonce($nonce, 'apollo_get_event_stats')) {
        wp_send_json_error([ 'message' => 'Invalid nonce' ]);

        return;
    }

    // SECURITY: Sanitize event_id
    $event_id = isset($_POST['event_id']) ? absint(wp_unslash($_POST['event_id'])) : 0;

    if (! $event_id) {
        wp_send_json_error([ 'message' => 'Invalid event ID' ]);

        return;
    }

    // Verify event exists
    $event = get_post($event_id);
    if (! $event || $event->post_type !== 'event_listing') {
        wp_send_json_error([ 'message' => 'Event not found' ]);

        return;
    }

    // Get statistics (use CPT if available, fallback to meta)
    $stats = [];
    if (class_exists('Apollo_Event_Stat_CPT')) {
        $stats = Apollo_Event_Stat_CPT::get_stats($event_id);
    } elseif (class_exists('Apollo_Event_Statistics')) {
        $stats = Apollo_Event_Statistics::get_event_stats($event_id);
    }

    if (! empty($stats)) {
        // SECURITY: Sanitize output stats
        $safe_stats = [
            'popup_count'  => absint(isset($stats['popup_count']) ? $stats['popup_count'] : 0),
            'page_count'   => absint(isset($stats['page_count']) ? $stats['page_count'] : 0),
            'total_views'  => absint(isset($stats['total_views']) ? $stats['total_views'] : 0),
            'last_updated' => sanitize_text_field(isset($stats['last_updated']) ? $stats['last_updated'] : ''),
        ];
        wp_send_json_success([ 'stats' => $safe_stats ]);
    } else {
        wp_send_json_error([ 'message' => 'No statistics available' ]);
    }
}
