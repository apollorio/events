<?php

// phpcs:ignoreFile
/**
 * Apollo ShadCN Components Global Loader
 *
 * This file ensures shadcn-style components are loaded across all Apollo plugins
 * Call apollo_load_shadcn_components() from any plugin to load the CSS
 *
 * Now integrates with Apollo Core's Global Assets Manager for centralized asset management.
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Load Apollo ShadCN Components CSS globally
 *
 * This function can be called from any Apollo plugin to ensure
 * shadcn-style components are available
 *
 * @param string $plugin_url Optional plugin URL if called from another plugin
 * @param string $plugin_version Optional plugin version
 */
function apollo_load_shadcn_components($plugin_url = null, $plugin_version = null)
{
    // Default to apollo-events-manager if not specified
    if (! $plugin_url) {
        $plugin_url = defined('APOLLO_APRIO_URL') ? APOLLO_APRIO_URL : plugin_dir_url(__FILE__) . '../';
    }

    if (! $plugin_version) {
        $plugin_version = defined('APOLLO_APRIO_VERSION') ? APOLLO_APRIO_VERSION : '2.0.0';
    }

    // Only enqueue if not already enqueued
    if (! wp_style_is('apollo-shadcn-components', 'enqueued')) {
        wp_enqueue_style(
            'apollo-shadcn-components',
            $plugin_url . 'assets/css/apollo-shadcn-components.css',
            [ 'apollo-uni-css', 'apollo-remixicon' ],
            // Depends on uni.css and RemixIcon
            $plugin_version,
            'all'
        );
    }
}

/**
 * Ensure global Apollo assets are loaded
 * Uses Apollo Core's centralized asset management when available
 */
function apollo_ensure_global_assets_loaded()
{
    // Use Apollo Core's Global Assets Manager if available
    if (function_exists('apollo_enqueue_global_assets')) {
        apollo_enqueue_global_assets('css');

        return;
    }

    // Fallback: Load uni.css and RemixIcon directly
    if (! wp_style_is('apollo-uni-css', 'enqueued')) {
        wp_enqueue_style(
            'apollo-uni-css',
            'https://assets.apollo.rio.br/uni.css',
            [],
            '2.0.0',
            'all'
        );
    }

    if (! wp_style_is('apollo-remixicon', 'enqueued') && ! wp_style_is('remixicon', 'enqueued')) {
        wp_enqueue_style(
            'apollo-remixicon',
            'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
            [ 'apollo-uni-css' ],
            '4.7.0',
            'all'
        );
    }
}

/**
 * Hook to load shadcn components on frontend
 */
add_action(
    'wp_enqueue_scripts',
    function () {
        // Use centralized global assets loader
        apollo_ensure_global_assets_loaded();

        // Load shadcn components (depends on global assets)
        apollo_load_shadcn_components();
    },
    10
);
// Priority 10 to ensure Apollo Core loads first

/**
 * Hook to load shadcn components in admin
 */
add_action(
    'admin_enqueue_scripts',
    function () {
        // Use centralized global assets loader
        apollo_ensure_global_assets_loaded();

        // Load shadcn components (depends on global assets)
        apollo_load_shadcn_components();
    },
    10
);
// Priority 10 to ensure Apollo Core loads first
