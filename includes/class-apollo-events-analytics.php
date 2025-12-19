<?php

// phpcs:ignoreFile
/**
 * Apollo Events Analytics & Statistics
 *
 * Provides analytics functions for events, users, sounds, and locations.
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Record event view for analytics
 *
 * @param int      $event_id Event post ID
 * @param int|null $user_id User ID (defaults to current user)
 * @return void
 */
function apollo_record_event_view($event_id, $user_id = null)
{
    if (! $event_id || ! is_numeric($event_id)) {
        return;
    }

    $event_id = absint($event_id);
    $user_id  = $user_id ? absint($user_id) : (is_user_logged_in() ? get_current_user_id() : 0);

    // Increment total views for event
    $current_views = get_post_meta($event_id, '_apollo_event_views_total', true);
    $current_views = $current_views ? absint($current_views) : 0;
    update_post_meta($event_id, '_apollo_event_views_total', $current_views + 1);

    // Optionally track per-user views (for future use)
    if ($user_id > 0) {
        // Could store in user meta or separate table for detailed tracking
        // For now, just increment total
    }
}

/**
 * Get event views count
 *
 * @param int $event_id Event post ID
 * @return int View count
 */
function apollo_get_event_views($event_id)
{
    $views = get_post_meta($event_id, '_apollo_event_views_total', true);

    return $views ? absint($views) : 0;
}

/**
 * Get user's favorited events (using aprio-bookmarks if available)
 *
 * @param int|null $user_id User ID (defaults to current user)
 * @return array Array of event post IDs
 */
function apollo_get_user_favorited_events($user_id = null)
{
    if (! $user_id) {
        $user_id = get_current_user_id();
    }

    if (! $user_id) {
        return [];
    }

    // Try aprio-bookmarks first
    if (function_exists('get_user_favorites')) {
        $favorites = get_user_favorites($user_id, null, [ 'post_types' => 'event_listing' ]);

        return is_array($favorites) ? array_map('absint', $favorites) : [];
    }

    // Fallback: check user meta directly
    $favorites_raw = get_user_meta($user_id, 'simplefavorites', true);
    if (empty($favorites_raw) || ! is_array($favorites_raw)) {
        return [];
    }

    // Extract event_listing IDs from favorites structure
    $event_ids = [];
    foreach ($favorites_raw as $site_data) {
        if (isset($site_data['posts']) && is_array($site_data['posts'])) {
            foreach ($site_data['posts'] as $post_id) {
                $post = get_post($post_id);
                if ($post && $post->post_type === 'event_listing' && $post->post_status === 'publish') {
                    $event_ids[] = absint($post_id);
                }
            }
        }
    }

    return array_unique($event_ids);
}

/**
 * Get events where user is in gestão
 *
 * @param int|null $user_id User ID (defaults to current user)
 * @return array Array of event post IDs
 */
function apollo_get_user_gestao_events($user_id = null)
{
    if (! $user_id) {
        $user_id = get_current_user_id();
    }

    if (! $user_id) {
        return [];
    }

    // Query events where user is in _event_gestao meta
    $args = [
        'post_type'      => 'event_listing',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => [
            [
                'key'     => '_event_gestao',
                'value'   => $user_id,
                'compare' => 'LIKE',
            ],
        ],
        'fields' => 'ids',
    ];

    $query = new WP_Query($args);

    return $query->posts ? array_map('absint', $query->posts) : [];
}

/**
 * Get global event statistics
 *
 * @return array Statistics array
 */
function apollo_get_global_event_stats()
{
    $stats = [
        'total_events'        => 0,
        'future_events'       => 0,
        'total_views'         => 0,
        'top_events_by_views' => [],
        'top_sounds'          => [],
        'top_locations'       => [],
    ];

    // Total events
    $stats['total_events'] = wp_count_posts('event_listing')->publish;

    // Future events (date >= today)
    $today        = date('Y-m-d');
    $future_query = new WP_Query(
        [
            'post_type'      => 'event_listing',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'     => '_event_start_date',
                    'value'   => $today,
                    'compare' => '>=',
                    'type'    => 'DATE',
                ],
            ],
            'fields' => 'ids',
        ]
    );
    $stats['future_events'] = $future_query->found_posts;

    // Total views (sum of all event views)
    global $wpdb;
    $total_views = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT SUM(meta_value) FROM {$wpdb->postmeta} 
         WHERE meta_key = %s AND post_id IN (
             SELECT ID FROM {$wpdb->posts} 
             WHERE post_type = %s AND post_status = 'publish'
         )",
            '_apollo_event_views_total',
            'event_listing'
        )
    );
    $stats['total_views'] = $total_views ? absint($total_views) : 0;

    // Top 5 events by views
    $top_events_query = new WP_Query(
        [
            'post_type'      => 'event_listing',
            'posts_per_page' => 5,
            'post_status'    => 'publish',
            'meta_key'       => '_apollo_event_views_total',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
            'meta_query'     => [
                [
                    'key'     => '_apollo_event_views_total',
                    'value'   => 0,
                    'compare' => '>',
                ],
            ],
        ]
    );

    if ($top_events_query->have_posts()) {
        foreach ($top_events_query->posts as $event) {
            $stats['top_events_by_views'][] = [
                'id'        => $event->ID,
                'title'     => $event->post_title,
                'views'     => apollo_get_event_views($event->ID),
                'permalink' => get_permalink($event->ID),
            ];
        }
    }

    // Top sounds (event_sounds taxonomy)
    $sounds = get_terms(
        [
            'taxonomy'   => 'event_sounds',
            'hide_empty' => true,
            'number'     => 5,
            'orderby'    => 'count',
            'order'      => 'DESC',
        ]
    );

    if (! is_wp_error($sounds) && ! empty($sounds)) {
        foreach ($sounds as $sound) {
            $stats['top_sounds'][] = [
                'id'    => $sound->term_id,
                'name'  => $sound->name,
                'slug'  => $sound->slug,
                'count' => $sound->count,
            ];
        }
    }

    // Top locations (by _event_location meta)
    global $wpdb;
    $location_counts = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT meta_value as location, COUNT(*) as count 
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
         WHERE pm.meta_key = %s 
         AND p.post_type = %s 
         AND p.post_status = 'publish'
         AND pm.meta_value != ''
         GROUP BY meta_value
         ORDER BY count DESC
         LIMIT 5",
            '_event_location',
            'event_listing'
        )
    );

    if ($location_counts) {
        foreach ($location_counts as $loc) {
            // Parse location (may contain "Name | Area")
            $location_name = $loc->location;
            if (strpos($location_name, '|') !== false) {
                list($name, $area) = explode('|', $location_name, 2);
                $location_name     = trim($name);
            }

            $stats['top_locations'][] = [
                'name'  => $location_name,
                'full'  => $loc->location,
                'count' => absint($loc->count),
            ];
        }
    }

    return $stats;
}

/**
 * Get user event statistics
 *
 * @param int $user_id User ID
 * @return array User statistics
 */
function apollo_get_user_event_stats($user_id)
{
    if (! $user_id) {
        return [];
    }

    $user = get_user_by('id', $user_id);
    if (! $user) {
        return [];
    }

    $stats = [
        'user_id'                => $user_id,
        'user_name'              => $user->display_name,
        'gestao_count'           => 0,
        'favorited_count'        => 0,
        'sounds_distribution'    => [],
        'locations_distribution' => [],
    ];

    // Get gestão events
    $gestao_events         = apollo_get_user_gestao_events($user_id);
    $stats['gestao_count'] = count($gestao_events);

    // Get favorited events
    $favorited_events         = apollo_get_user_favorited_events($user_id);
    $stats['favorited_count'] = count($favorited_events);

    // Combine events for distribution analysis
    $all_user_events = array_unique(array_merge($gestao_events, $favorited_events));

    if (empty($all_user_events)) {
        return $stats;
    }

    // Calculate sounds distribution
    $sounds_counts = [];
    foreach ($all_user_events as $event_id) {
        $sounds = wp_get_post_terms($event_id, 'event_sounds');
        if (! is_wp_error($sounds) && ! empty($sounds)) {
            foreach ($sounds as $sound) {
                if (! isset($sounds_counts[ $sound->term_id ])) {
                    $sounds_counts[ $sound->term_id ] = [
                        'name'  => $sound->name,
                        'count' => 0,
                    ];
                }
                ++$sounds_counts[ $sound->term_id ]['count'];
            }
        }
    }

    // Calculate percentages for sounds
    $total_sound_events = array_sum(array_column($sounds_counts, 'count'));
    if ($total_sound_events > 0) {
        foreach ($sounds_counts as $term_id => $data) {
            $stats['sounds_distribution'][] = [
                'name'       => $data['name'],
                'count'      => $data['count'],
                'percentage' => round(($data['count'] / $total_sound_events) * 100, 1),
            ];
        }
        // Sort by count descending
        usort(
            $stats['sounds_distribution'],
            function ($a, $b) {
                return $b['count'] - $a['count'];
            }
        );
    }

    // Calculate locations distribution
    $location_counts = [];
    foreach ($all_user_events as $event_id) {
        $location = get_post_meta($event_id, '_event_location', true);
        if (! empty($location)) {
            // Parse location name (before | separator)
            $location_name = $location;
            if (strpos($location, '|') !== false) {
                list($name, $area) = explode('|', $location, 2);
                $location_name     = trim($name);
            } else {
                $location_name = trim($location);
            }

            if (! isset($location_counts[ $location_name ])) {
                $location_counts[ $location_name ] = 0;
            }
            ++$location_counts[ $location_name ];
        }
    }

    // Calculate percentages for locations
    $total_location_events = array_sum($location_counts);
    if ($total_location_events > 0) {
        foreach ($location_counts as $location_name => $count) {
            $stats['locations_distribution'][] = [
                'name'       => $location_name,
                'count'      => $count,
                'percentage' => round(($count / $total_location_events) * 100, 1),
            ];
        }
        // Sort by count descending
        usort(
            $stats['locations_distribution'],
            function ($a, $b) {
                return $b['count'] - $a['count'];
            }
        );
    }

    return $stats;
}

/**
 * Get top users by interactions
 *
 * @param int $limit Number of users to return
 * @return array Array of user data with interaction counts
 */
function apollo_get_top_users_by_interactions($limit = 10)
{
    $users_data = [];

    // Get all users
    $users = get_users(
        [
            'number' => $limit * 2,
            // Get more to filter
                                        'orderby' => 'registered',
            'order'                               => 'DESC',
        ]
    );

    foreach ($users as $user) {
        $gestao_count       = count(apollo_get_user_gestao_events($user->ID));
        $favorited_count    = count(apollo_get_user_favorited_events($user->ID));
        $total_interactions = $gestao_count + $favorited_count;

        if ($total_interactions > 0) {
            $users_data[] = [
                'id'                 => $user->ID,
                'name'               => $user->display_name,
                'email'              => $user->user_email,
                'gestao_count'       => $gestao_count,
                'favorited_count'    => $favorited_count,
                'total_interactions' => $total_interactions,
            ];
        }
    }

    // Sort by total interactions descending
    usort(
        $users_data,
        function ($a, $b) {
            return $b['total_interactions'] - $a['total_interactions'];
        }
    );

    // Return top N
    return array_slice($users_data, 0, $limit);
}
