<?php

// phpcs:ignoreFile
/**
 * Event Statistics CPT
 * TODO 97: Criar CPT apollo_event_stat para armazenar estatísticas
 *
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

class Apollo_Event_Stat_CPT
{
    public function __construct()
    {
        add_action('init', [ $this, 'register_cpt' ]);
    }

    /**
     * Register apollo_event_stat CPT
     */
    public function register_cpt()
    {
        $labels = [
            'name'          => __('Event Statistics', 'apollo-events-manager'),
            'singular_name' => __('Event Statistic', 'apollo-events-manager'),
            'menu_name'     => __('Event Stats', 'apollo-events-manager'),
            'all_items'     => __('All Statistics', 'apollo-events-manager'),
            'view_item'     => __('View Statistic', 'apollo-events-manager'),
            'search_items'  => __('Search Statistics', 'apollo-events-manager'),
            'not_found'     => __('No statistics found', 'apollo-events-manager'),
        ];

        $args = [
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'edit.php?post_type=event_listing',
            'capability_type' => 'post',
            'capabilities'    => [
                'create_posts' => 'view_apollo_event_stats',
                'edit_post'    => 'view_apollo_event_stats',
                'edit_posts'   => 'view_apollo_event_stats',
                'delete_post'  => 'view_apollo_event_stats',
            ],
            'hierarchical' => false,
            'supports'     => [ 'title' ],
            'has_archive'  => false,
            'rewrite'      => false,
            'query_var'    => false,
        ];

        register_post_type('apollo_event_stat', $args);
    }

    /**
     * Track event view
     *
     * @param int    $event_id
     * @param string $type 'page' or 'popup'
     */
    public static function track_view($event_id, $type = 'page')
    {
        $event_id = absint($event_id);
        if (! $event_id) {
            return false;
        }

        $type = in_array($type, [ 'page', 'popup' ]) ? $type : 'page';

        // Get or create stat post for this event
        $stat_post_id = self::get_or_create_stat_post($event_id);
        if (! $stat_post_id) {
            return false;
        }

        // Increment counters
        $page_count  = (int) get_post_meta($stat_post_id, '_page_views', true);
        $popup_count = (int) get_post_meta($stat_post_id, '_popup_views', true);
        $total_count = (int) get_post_meta($stat_post_id, '_total_views', true);

        if ($type === 'page') {
            update_post_meta($stat_post_id, '_page_views', $page_count + 1);
        } else {
            update_post_meta($stat_post_id, '_popup_views', $popup_count + 1);
        }

        update_post_meta($stat_post_id, '_total_views', $total_count + 1);
        update_post_meta($stat_post_id, '_last_view_date', current_time('mysql'));

        // Track daily views for graph
        self::track_daily_view($stat_post_id, $type);

        return true;
    }

    /**
     * Track daily views for line-graph
     */
    private static function track_daily_view($stat_post_id, $type)
    {
        $today      = current_time('Y-m-d');
        $daily_data = get_post_meta($stat_post_id, '_daily_views', true);

        if (! is_array($daily_data)) {
            $daily_data = [];
        }

        if (! isset($daily_data[ $today ])) {
            $daily_data[ $today ] = [
                'page'  => 0,
                'popup' => 0,
                'total' => 0,
            ];
        }

        ++$daily_data[ $today ][ $type ];
        ++$daily_data[ $today ]['total'];

        // Keep only last 90 days
        if (count($daily_data) > 90) {
            $daily_data = array_slice($daily_data, -90, 90, true);
        }

        update_post_meta($stat_post_id, '_daily_views', $daily_data);
    }

    /**
     * Get or create stat post for event
     */
    private static function get_or_create_stat_post($event_id)
    {
        // Try to find existing stat post
        $existing = get_posts(
            [
                'post_type'      => 'apollo_event_stat',
                'meta_key'       => '_event_id',
                'meta_value'     => $event_id,
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ]
        );

        if (! empty($existing)) {
            return $existing[0];
        }

        // Create new stat post
        $event_title  = get_the_title($event_id);
        $stat_post_id = wp_insert_post(
            [
                'post_type'   => 'apollo_event_stat',
                'post_title'  => 'Stats: ' . $event_title,
                'post_status' => 'publish',
                'meta_input'  => [
                    '_event_id'    => $event_id,
                    '_page_views'  => 0,
                    '_popup_views' => 0,
                    '_total_views' => 0,
                    '_daily_views' => [],
                ],
            ]
        );

        return $stat_post_id;
    }

    /**
     * Get stats for event
     */
    public static function get_stats($event_id)
    {
        $event_id = absint($event_id);
        if (! $event_id) {
            return [
                'page_views'   => 0,
                'popup_views'  => 0,
                'popup_count'  => 0,
                'page_count'   => 0,
                'total_views'  => 0,
                'daily_views'  => [],
                'last_view'    => '',
                'last_updated' => '',
            ];
        }

        $stat_post_id = self::get_or_create_stat_post($event_id);
        if (! $stat_post_id) {
            return [];
        }

        $page_views  = (int) get_post_meta($stat_post_id, '_page_views', true);
        $popup_views = (int) get_post_meta($stat_post_id, '_popup_views', true);

        return [
            'page_views'   => $page_views,
            'popup_views'  => $popup_views,
            'page_count'   => $page_views,
            'popup_count'  => $popup_views,
            'total_views'  => $page_views + $popup_views,
            'daily_views'  => get_post_meta($stat_post_id, '_daily_views', true) ?: [],
            'last_view'    => get_post_meta($stat_post_id, '_last_view_date', true),
            'last_updated' => get_post_meta($stat_post_id, '_last_view_date', true),
        ];
    }

    /**
     * Get all stats (for admin dashboard)
     */
    public static function get_all_stats()
    {
        $stat_posts = get_posts(
            [
                'post_type'      => 'apollo_event_stat',
                'posts_per_page' => -1,
                'orderby'        => 'meta_value_num',
                'meta_key'       => '_total_views',
                'order'          => 'DESC',
            ]
        );

        $stats = [];
        foreach ($stat_posts as $stat_post) {
            $event_id           = get_post_meta($stat_post->ID, '_event_id', true);
            $stats[ $event_id ] = self::get_stats($event_id);
        }

        return $stats;
    }
}

// Initialize only if not already initialized
if (! class_exists('Apollo_Event_Stat_CPT_Initialized')) {
    new Apollo_Event_Stat_CPT();
    class Apollo_Event_Stat_CPT_Initialized
    {
    }
}
