<?php

// phpcs:ignoreFile
/**
 * Admin Statistics Menu
 *
 * Adds "Estatísticas" submenu to Events menu
 *
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

/**
 * Add Statistics submenu to Events menu
 */
add_action('admin_menu', 'apollo_add_statistics_submenu', 20);

function apollo_add_statistics_submenu()
{
    add_submenu_page(
        'edit.php?post_type=event_listing',
        __('Estatísticas', 'apollo-events-manager'),
        __('Estatísticas', 'apollo-events-manager'),
        'manage_options',
        'apollo-event-statistics',
        'apollo_render_statistics_page'
    );
}

/**
 * Render statistics page
 */
function apollo_render_statistics_page()
{
    $template_path = APOLLO_APRIO_PATH . 'templates/admin-event-statistics.php';

    if (file_exists($template_path)) {
        include $template_path;
    } else {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Estatísticas', 'apollo-events-manager') . '</h1>';
        echo '<p>' . esc_html__('Template não encontrado.', 'apollo-events-manager') . '</p>';
        echo '</div>';
    }
}

/**
 * Enqueue admin statistics assets
 */
add_action('admin_enqueue_scripts', 'apollo_enqueue_statistics_assets');

function apollo_enqueue_statistics_assets($hook)
{
    // Only load on statistics page
    if ($hook !== 'event_listing_page_apollo-event-statistics') {
        return;
    }

    // Enqueue Chart.js for graphs (optional)
    wp_enqueue_script(
        'chart-js',
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
        [],
        '4.4.0',
        true
    );
}
