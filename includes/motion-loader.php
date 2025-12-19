<?php

// phpcs:ignoreFile
/**
 * Motion.dev Loader for Apollo Events Manager
 *
 * Loads Framer Motion library for animations
 *
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

/**
 * Load Framer Motion library
 *
 * @param string $plugin_url Optional plugin URL
 * @param string $plugin_version Optional plugin version
 */
function apollo_load_motion_dev($plugin_url = null, $plugin_version = null)
{
    // Default to apollo-events-manager if not specified
    if (! $plugin_url) {
        $plugin_url = defined('APOLLO_APRIO_URL') ? APOLLO_APRIO_URL : plugin_dir_url(__FILE__) . '../';
    }

    if (! $plugin_version) {
        $plugin_version = defined('APOLLO_APRIO_VERSION') ? APOLLO_APRIO_VERSION : '0.1.0';
    }

    // Only enqueue if not already enqueued
    if (! wp_script_is('framer-motion', 'enqueued')) {
        // Option 1: CDN (faster, no build needed)
        wp_enqueue_script(
            'framer-motion',
            'https://cdn.jsdelivr.net/npm/framer-motion@11.0.0/dist/framer-motion.js',
            [],
            '11.0.0',
            true
        );

        // Make Motion available globally after script loads
        wp_add_inline_script(
            'framer-motion',
            '
            if (typeof window.motion === "undefined" && typeof window.Motion !== "undefined") {
                window.motion = window.Motion;
            }
        ',
            'after'
        );

        // Option 2: Local bundle (uncomment if using build)
        /*
        wp_enqueue_script(
            'framer-motion',
            $plugin_url . 'assets/js/framer-motion.min.js',
            array(),
            $plugin_version,
            true
        );
        */
    }//end if
}

/**
 * Hook to load Motion.dev on frontend
 */
add_action(
    'wp_enqueue_scripts',
    function () {
        // Load Motion.dev
        apollo_load_motion_dev();

        // Ensure it loads after jQuery if needed
        wp_script_add_data('framer-motion', 'group', 1);
    },
    20
);
// Priority 20 to load after ShadCN loader

/**
 * Hook to load Motion.dev in admin (if needed)
 */
add_action(
    'admin_enqueue_scripts',
    function ($hook) {
        // Only load on event-related admin pages
        if (in_array($hook, [ 'post.php', 'post-new.php', 'edit.php' ])) {
            global $post_type;
            if ($post_type === 'event_listing') {
                apollo_load_motion_dev();
            }
        }
    },
    20
);
