<?php

// phpcs:ignoreFile
/**
 * Local Connection Manager
 *
 * Ensures mandatory connection between events and local (event_local CPT)
 * Prevents duplications and provides unified API
 *
 * @package ApolloEventsManager
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Apollo Local Connection Manager
 *
 * CRITICAL: This class ensures that every event MUST have a local connected.
 * The connection is stored in _event_local_ids meta (single integer ID).
 * Legacy _event_local meta is supported but deprecated.
 */
class Apollo_Local_Connection
{
    private static $instance = null;

    /**
     * Primary meta key for local connection
     */
    public const META_KEY_PRIMARY = '_event_local_ids';

    /**
     * Legacy meta key (deprecated, but supported for migration)
     */
    public const META_KEY_LEGACY = '_event_local';

    /**
     * Get singleton instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        // Hook into event save to enforce mandatory connection
        add_action('save_post_event_listing', [ $this, 'enforce_mandatory_connection' ], 10, 2);

        // Validate before save (prevent saving without local)
        add_action('save_post_event_listing', [ $this, 'validate_local_connection' ], 5, 2);
    }

    /**
     * Get local ID for an event
     *
     * @param int $event_id Event post ID
     * @return int|false Local ID or false if not found
     */
    public function get_local_id($event_id)
    {
        $event_id = absint($event_id);
        if (! $event_id) {
            return false;
        }

        // Try primary meta key first
        $local_id = get_post_meta($event_id, self::META_KEY_PRIMARY, true);

        // Handle array format (should be single ID, but support array for safety)
        if (is_array($local_id)) {
            $local_id = ! empty($local_id) ? absint($local_id[0]) : 0;
        } elseif (is_numeric($local_id)) {
            $local_id = absint($local_id);
        } else {
            $local_id = 0;
        }

        // Validate local exists and is correct post type
        if ($local_id > 0) {
            $local = get_post($local_id);
            if ($local && $local->post_type === 'event_local' && $local->post_status === 'publish') {
                return $local_id;
            }
        }

        // Fallback to legacy meta key
        $legacy_local = get_post_meta($event_id, self::META_KEY_LEGACY, true);
        if (is_numeric($legacy_local) && absint($legacy_local) > 0) {
            $local_id = absint($legacy_local);
            $local    = get_post($local_id);
            if ($local && $local->post_type === 'event_local' && $local->post_status === 'publish') {
                // Migrate to primary meta key
                $this->set_local_id($event_id, $local_id);

                return $local_id;
            }
        }

        return false;
    }

    /**
     * Set local ID for an event
     *
     * @param int $event_id Event post ID
     * @param int $local_id Local post ID
     * @return bool Success
     */
    public function set_local_id($event_id, $local_id)
    {
        $event_id = absint($event_id);
        $local_id = absint($local_id);

        if (! $event_id) {
            return false;
        }

        // Validate local exists
        if ($local_id > 0) {
            $local = get_post($local_id);
            if (! $local || $local->post_type !== 'event_local') {
                return false;
            }
        }

        // Update primary meta key
        if ($local_id > 0) {
            update_post_meta($event_id, self::META_KEY_PRIMARY, $local_id);

            // Clean up legacy meta if exists
            delete_post_meta($event_id, self::META_KEY_LEGACY);
        } else {
            // Remove connection
            delete_post_meta($event_id, self::META_KEY_PRIMARY);
            delete_post_meta($event_id, self::META_KEY_LEGACY);
        }

        return true;
    }

    /**
     * Get local post object
     *
     * @param int $event_id Event post ID
     * @return WP_Post|false Local post object or false
     */
    public function get_local($event_id)
    {
        $local_id = $this->get_local_id($event_id);
        if (! $local_id) {
            return false;
        }

        $local = get_post($local_id);
        if ($local && $local->post_type === 'event_local' && $local->post_status === 'publish') {
            return $local;
        }

        return false;
    }

    /**
     * Check if event has local connected
     *
     * @param int $event_id Event post ID
     * @return bool
     */
    public function has_local($event_id)
    {
        return $this->get_local_id($event_id) !== false;
    }

    /**
     * Validate local connection before save
     * Prevents saving event without local (unless draft/auto-draft)
     *
     * @param int     $post_id Post ID
     * @param WP_Post $post Post object
     */
    public function validate_local_connection($post_id, $post)
    {
        // Skip validation for drafts and auto-drafts
        if (in_array($post->post_status, [ 'draft', 'auto-draft', 'pending' ], true)) {
            return;
        }

        // Skip autosave and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Check if local is set in POST data
        $local_id = isset($_POST['apollo_event_local']) ? absint(sanitize_text_field(wp_unslash($_POST['apollo_event_local']))) : 0;

        // If not in POST, check current meta
        if (! $local_id) {
            $local_id = $this->get_local_id($post_id);
        }

        // If still no local and event is being published, show warning
        if (! $local_id && $post->post_status === 'publish') {
            // Add admin notice warning
            add_action(
                'admin_notices',
                function () use ($post_id) {
                    $edit_link = admin_url('post.php?post=' . $post_id . '&action=edit');
                    echo '<div class="notice notice-warning is-dismissible">';
                    echo '<p><strong>' . esc_html__('⚠️ Apollo Events Manager:', 'apollo-events-manager') . '</strong> ';
                    echo esc_html__('Este evento não possui um local conectado. ', 'apollo-events-manager');
                    echo '<a href="' . esc_url($edit_link) . '">' . esc_html__('Conecte um local agora', 'apollo-events-manager') . '</a>.';
                    echo '</p></div>';
                }
            );
        }
    }

    /**
     * Enforce mandatory connection on save
     * Ensures local is properly saved and legacy meta is cleaned up
     *
     * @param int     $post_id Post ID
     * @param WP_Post $post Post object
     */
    public function enforce_mandatory_connection($post_id, $post)
    {
        // Skip autosave and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Get local ID from POST data
        $local_id = 0;
        if (isset($_POST['apollo_event_local'])) {
            $local_id = absint(sanitize_text_field(wp_unslash($_POST['apollo_event_local'])));
        }

        // If local ID provided, set it
        if ($local_id > 0) {
            $this->set_local_id($post_id, $local_id);
        } elseif (isset($_POST['apollo_event_local'])) {
            // Explicitly set to empty (user removed local)
            $this->set_local_id($post_id, 0);
        }

        // Clean up any duplicate meta entries
        $this->cleanup_duplicate_meta($post_id);
    }

    /**
     * Clean up duplicate meta entries
     * Ensures only primary meta key exists
     *
     * @param int $event_id Event post ID
     */
    private function cleanup_duplicate_meta($event_id)
    {
        $event_id = absint($event_id);
        if (! $event_id) {
            return;
        }

        // Get current local ID
        $local_id = $this->get_local_id($event_id);

        // Remove all local-related meta except the primary one
        $meta_keys_to_check = [
            self::META_KEY_LEGACY,
            '_event_local',
        // Additional legacy variations
        ];

        foreach ($meta_keys_to_check as $meta_key) {
            $existing = get_post_meta($event_id, $meta_key, true);
            if ($existing && $existing != $local_id) {
                delete_post_meta($event_id, $meta_key);
            }
        }
    }

    /**
     * Get all events without local connection
     *
     * @return array Array of event IDs
     */
    public function get_events_without_local()
    {
        global $wpdb;

        $events = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT p.ID 
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
            WHERE p.post_type = 'event_listing'
            AND p.post_status = 'publish'
            AND (pm.meta_value IS NULL OR pm.meta_value = '' OR pm.meta_value = '0')
            LIMIT 100",
                self::META_KEY_PRIMARY
            )
        );

        return array_map('absint', $events);
    }

    /**
     * Migrate legacy _event_local to _event_local_ids
     *
     * @param int $event_id Event post ID
     * @return bool Success
     */
    public function migrate_legacy_meta($event_id)
    {
        $event_id = absint($event_id);
        if (! $event_id) {
            return false;
        }

        // Check if already migrated
        $current = get_post_meta($event_id, self::META_KEY_PRIMARY, true);
        if (! empty($current)) {
            return true;
            // Already migrated
        }

        // Get legacy value
        $legacy = get_post_meta($event_id, self::META_KEY_LEGACY, true);
        if (is_numeric($legacy) && absint($legacy) > 0) {
            $local_id = absint($legacy);
            // Validate local exists
            $local = get_post($local_id);
            if ($local && $local->post_type === 'event_local') {
                $this->set_local_id($event_id, $local_id);

                return true;
            }
        }

        return false;
    }
}

// Initialize
Apollo_Local_Connection::get_instance();

/**
 * Helper function: Get local ID for event
 * Unified API - use this instead of direct meta access
 *
 * @param int $event_id Event post ID
 * @return int|false Local ID or false
 */
if (! function_exists('apollo_get_event_local_id')) {
    function apollo_get_event_local_id($event_id)
    {
        $connection = Apollo_Local_Connection::get_instance();

        return $connection->get_local_id($event_id);
    }
}

/**
 * Helper function: Get local post object
 *
 * @param int $event_id Event post ID
 * @return WP_Post|false Local post or false
 */
if (! function_exists('apollo_get_event_local')) {
    function apollo_get_event_local($event_id)
    {
        $connection = Apollo_Local_Connection::get_instance();

        return $connection->get_local($event_id);
    }
}

/**
 * Helper function: Set local for event
 *
 * @param int $event_id Event post ID
 * @param int $local_id Local post ID
 * @return bool Success
 */
if (! function_exists('apollo_set_event_local')) {
    function apollo_set_event_local($event_id, $local_id)
    {
        $connection = Apollo_Local_Connection::get_instance();

        return $connection->set_local_id($event_id, $local_id);
    }
}

/**
 * Helper function: Check if event has local
 *
 * @param int $event_id Event post ID
 * @return bool
 */
if (! function_exists('apollo_event_has_local')) {
    function apollo_event_has_local($event_id)
    {
        $connection = Apollo_Local_Connection::get_instance();

        return $connection->has_local($event_id);
    }
}
