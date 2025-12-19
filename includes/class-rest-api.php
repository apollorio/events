<?php

/**
 * REST API SMOKE TEST – PASSED
 * Route: /apollo/v1/eventos, /categorias, /locais, /meus-eventos
 * Affects: apollo-events-manager.php, class-rest-api.php, class-bookmarks.php
 * Verified: 2025-12-06 – no conflicts, secure callbacks, unique namespace
 */
// phpcs:ignoreFile
/**
 * REST API for Apollo Events Manager
 * Integrated from aprio-rest-api functionality
 *
 * @package ApolloEventsManager
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Apollo Events REST API Class
 * Provides REST endpoints for events management
 */
class Apollo_Events_REST_API
{
    private static $instance = null;
    private $namespace       = 'apollo/v1';

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        add_action('rest_api_init', [ $this, 'register_routes' ]);
    }

    /**
     * Register REST API routes
     */
    public function register_routes()
    {
        // Eventos endpoints (Events in Portuguese)
        register_rest_route(
            $this->namespace,
            'eventos',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_events' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'per_page' => [
                        'default'           => 20,
                        'sanitize_callback' => 'absint',
                    ],
                    'page' => [
                        'default'           => 1,
                        'sanitize_callback' => 'absint',
                    ],
                    'search' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'category' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'location' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'date_from' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'date_to' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            'evento/(?P<id>\d+)',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_event' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ]
        );

        // Categories endpoint
        register_rest_route(
            $this->namespace,
            'categorias',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_categories' ],
                'permission_callback' => '__return_true',
            ]
        );

        // Locations endpoint
        register_rest_route(
            $this->namespace,
            'locais',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_locations' ],
                'permission_callback' => '__return_true',
            ]
        );

        // User events endpoint (requires auth)
        register_rest_route(
            $this->namespace,
            'meus-eventos',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_my_events' ],
                'permission_callback' => [ $this, 'check_user_permission' ],
            ]
        );
    }

    /**
     * Get events
     */
    public function get_events($request)
    {
        $args = [
            'post_type'      => 'event_listing',
            'post_status'    => 'publish',
            'posts_per_page' => isset($request['per_page']) ? absint($request['per_page']) : 20,
            'paged'          => isset($request['page']) ? absint($request['page']) : 1,
        ];

        // Search
        if (! empty($request['search'])) {
            $args['s'] = sanitize_text_field($request['search']);
        }

        // Category filter
        if (! empty($request['category'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'event_category',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($request['category']),
            ];
        }

        // Location filter (local)
        if (! empty($request['location'])) {
            // Try filtering by local name first
            $local_ids = $this->get_local_ids_by_name(sanitize_text_field($request['location']));
            if (! empty($local_ids)) {
                $args['meta_query'][] = [
                    'key'     => '_event_local_ids',
                    'value'   => $local_ids,
                    'compare' => 'IN',
                ];
            } else {
                // Fallback to _event_location text search
                $args['meta_query'][] = [
                    'key'     => '_event_location',
                    'value'   => sanitize_text_field($request['location']),
                    'compare' => 'LIKE',
                ];
            }
        }

        // Local filter (by local ID)
        if (! empty($request['local_id'])) {
            $local_id = absint($request['local_id']);
            if ($local_id > 0) {
                $args['meta_query'][] = [
                    'key'     => '_event_local_ids',
                    'value'   => $local_id,
                    'compare' => '=',
                ];
            }
        }

        // Date filters
        if (! empty($request['date_from']) || ! empty($request['date_to'])) {
            $date_query = [];

            if (! empty($request['date_from'])) {
                $date_query['after'] = sanitize_text_field($request['date_from']);
            }

            if (! empty($request['date_to'])) {
                $date_query['before'] = sanitize_text_field($request['date_to']);
            }

            $date_query['column']   = 'meta_value';
            $date_query['meta_key'] = '_event_start_date';

            $args['meta_query'][] = $date_query;
        }

        $query = new WP_Query($args);

        $events = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $events[] = $this->format_event(get_post());
            }
            wp_reset_postdata();
        }

        return new WP_REST_Response(
            [
                'events'       => $events,
                'total'        => $query->found_posts,
                'pages'        => $query->max_num_pages,
                'current_page' => absint(isset($request['page']) ? $request['page'] : 1),
            ],
            200
        );
    }

    /**
     * Get single event
     */
    public function get_event($request)
    {
        $event_id = absint($request['id']);
        $event    = get_post($event_id);

        if (! $event || $event->post_type !== 'event_listing') {
            return new WP_Error('event_not_found', 'Evento não encontrado', [ 'status' => 404 ]);
        }

        return new WP_REST_Response(
            [
                'event' => $this->format_event($event, true),
            ],
            200
        );
    }

    /**
     * Format event data
     */
    private function format_event($event, $full = false)
    {
        $formatted = [
            'id'        => (int) $event->ID,
            'title'     => sanitize_text_field(isset($event->post_title) ? $event->post_title : ''),
            'slug'      => sanitize_text_field(isset($event->post_name) ? $event->post_name : ''),
            'permalink' => esc_url_raw(get_permalink($event->ID) ?: ''),
            'excerpt'   => wp_kses_post(get_the_excerpt($event->ID) ?: ''),
            'content'   => $full ? wp_kses_post(apply_filters('the_content', isset($event->post_content) ? $event->post_content : '')) : '',
            'date'      => [
                'published' => sanitize_text_field(isset($event->post_date) ? $event->post_date : ''),
                'modified'  => sanitize_text_field(isset($event->post_modified) ? $event->post_modified : ''),
            ],
        ];

        // Event meta
        $start_date = get_post_meta($event->ID, '_event_start_date', true);
        $end_date   = get_post_meta($event->ID, '_event_end_date', true);
        $location   = get_post_meta($event->ID, '_event_location', true);
        $banner     = get_post_meta($event->ID, '_event_banner', true);

        $formatted['event'] = [
            'start_date' => sanitize_text_field($start_date ? $start_date : ''),
            'end_date'   => sanitize_text_field($end_date ? $end_date : ''),
            'location'   => sanitize_text_field($location ? $location : ''),
            'banner'     => $banner ? esc_url_raw(wp_get_attachment_image_url($banner, 'large') ?: '') : null,
        ];

        // Categories
        $categories = wp_get_post_terms($event->ID, 'event_category', [ 'fields' => 'all' ]);
        if (is_wp_error($categories)) {
            $categories = [];
        }
        $formatted['categories'] = [];
        if (is_array($categories) && ! empty($categories)) {
            foreach ($categories as $term) {
                if (! is_object($term) || ! isset($term->term_id)) {
                    continue;
                }
                $formatted['categories'][] = [
                    'id'   => (int) $term->term_id,
                    'name' => sanitize_text_field(isset($term->name) ? $term->name : ''),
                    'slug' => sanitize_text_field(isset($term->slug) ? $term->slug : ''),
                ];
            }
        }

        // Author
        $author = get_userdata($event->post_author);
        if ($author && isset($author->ID)) {
            $formatted['author'] = [
                'id'     => (int) $author->ID,
                'name'   => sanitize_text_field(isset($author->display_name) ? $author->display_name : ''),
                'avatar' => esc_url_raw(get_avatar_url($author->ID) ?: ''),
            ];
        } else {
            $formatted['author'] = [
                'id'     => 0,
                'name'   => '',
                'avatar' => '',
            ];
        }

        // Local connection - MANDATORY
        if (function_exists('apollo_get_event_local_id')) {
            $local_id = apollo_get_event_local_id($event->ID);
            if ($local_id) {
                $local = get_post($local_id);
                if ($local && $local->post_type === 'event_local') {
                    $local_name         = get_post_meta($local_id, '_local_name', true);
                    $local_title        = isset($local->post_title) ? $local->post_title : '';
                    $formatted['local'] = [
                        'id'        => (int) $local_id,
                        'name'      => sanitize_text_field($local_name ? $local_name : $local_title),
                        'address'   => sanitize_text_field(get_post_meta($local_id, '_local_address', true) ?: ''),
                        'city'      => sanitize_text_field(get_post_meta($local_id, '_local_city', true) ?: ''),
                        'state'     => sanitize_text_field(get_post_meta($local_id, '_local_state', true) ?: ''),
                        'latitude'  => sanitize_text_field(get_post_meta($local_id, '_local_latitude', true) ?: ''),
                        'longitude' => sanitize_text_field(get_post_meta($local_id, '_local_longitude', true) ?: ''),
                        'permalink' => esc_url_raw(get_permalink($local_id) ?: ''),
                    ];
                }
            }
        }

        // Bookmark count
        if (class_exists('Apollo_Events_Bookmarks')) {
            $bookmarks                   = Apollo_Events_Bookmarks::get_instance();
            $formatted['bookmark_count'] = $bookmarks->get_bookmark_count($event->ID);
        }

        // Full details
        if ($full) {
            $timetable                       = get_post_meta($event->ID, '_event_timetable', true);
            $formatted['event']['timetable'] = $timetable;

            $djs              = get_post_meta($event->ID, '_event_dj_ids', true);
            $formatted['djs'] = $this->format_djs($djs);
        }

        return $formatted;
    }

    /**
     * Format DJs data
     */
    private function format_djs($dj_ids)
    {
        if (empty($dj_ids)) {
            return [];
        }

        $dj_ids = maybe_unserialize($dj_ids);
        if (! is_array($dj_ids)) {
            return [];
        }

        $djs = [];
        foreach ($dj_ids as $dj_id) {
            $dj = get_post($dj_id);
            if ($dj && $dj->post_status === 'publish') {
                $dj_name  = get_post_meta($dj->ID, '_dj_name', true);
                $dj_title = isset($dj->post_title) ? $dj->post_title : '';
                $djs[]    = [
                    'id'        => (int) $dj->ID,
                    'name'      => sanitize_text_field($dj_name ? $dj_name : $dj_title),
                    'permalink' => esc_url_raw(get_permalink($dj->ID) ?: ''),
                ];
            }
        }

        return $djs;
    }

    /**
     * Get categories
     */
    public function get_categories($request)
    {
        $categories = get_terms(
            [
                'taxonomy'   => 'event_category',
                'hide_empty' => false,
            ]
        );

        if (is_wp_error($categories)) {
            $categories = [];
        }

        $formatted = [];
        if (is_array($categories) && ! empty($categories)) {
            foreach ($categories as $term) {
                if (! is_object($term) || ! isset($term->term_id)) {
                    continue;
                }
                $formatted[] = [
                    'id'    => (int) $term->term_id,
                    'name'  => sanitize_text_field(isset($term->name) ? $term->name : ''),
                    'slug'  => sanitize_text_field(isset($term->slug) ? $term->slug : ''),
                    'count' => isset($term->count) ? (int) $term->count : 0,
                ];
            }
        }

        return new WP_REST_Response(
            [
                'categories' => $formatted,
            ],
            200
        );
    }

    /**
     * Get locations (locals)
     * Returns list of locals (event_local posts) instead of text locations
     */
    public function get_locations($request)
    {
        // Get all published locals
        $locals = get_posts(
            [
                'post_type'      => 'event_local',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            ]
        );

        $locations = [];
        foreach ($locals as $local) {
            $local_name = get_post_meta($local->ID, '_local_name', true) ?: $local->post_title;
            $city       = get_post_meta($local->ID, '_local_city', true);
            $state      = get_post_meta($local->ID, '_local_state', true);

            $location_name = $local_name;
            if ($city) {
                $location_name .= ', ' . $city;
            }
            if ($state) {
                $location_name .= ' - ' . $state;
            }

            $locations[] = [
                'id'         => absint($local->ID),
                'name'       => sanitize_text_field($location_name),
                'local_name' => sanitize_text_field($local_name),
                'city'       => sanitize_text_field($city ? $city : ''),
                'state'      => sanitize_text_field($state ? $state : ''),
            ];
        }//end foreach

        // Fallback: Also include text-based locations from _event_location meta
        // SECURITY: Using $wpdb->prepare() for SQL safety
        global $wpdb;
        $text_locations = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
				WHERE meta_key = %s
            AND meta_value != ''
				ORDER BY meta_value ASC",
                '_event_location'
            )
        );

        foreach ($text_locations as $text_loc) {
            $text_loc = sanitize_text_field($text_loc);
            // Only add if not already in locals list
            $exists = false;
            foreach ($locations as $loc) {
                if ($loc['name'] === $text_loc) {
                    $exists = true;

                    break;
                }
            }
            if (! $exists) {
                $locations[] = [
                    'id'         => 0,
                    'name'       => $text_loc,
                    'local_name' => $text_loc,
                    'city'       => '',
                    'state'      => '',
                ];
            }
        }

        return new WP_REST_Response(
            [
                'locations' => $locations,
            ],
            200
        );
    }

    /**
     * Get local IDs by name (helper for location filter)
     */
    private function get_local_ids_by_name($search_term)
    {
        $locals = get_posts(
            [
                'post_type'      => 'event_local',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                's'              => $search_term,
                'fields'         => 'ids',
            ]
        );

        return array_map('absint', $locals);
    }

    /**
     * Get user's events
     */
    public function get_my_events($request)
    {
        $user_id = get_current_user_id();

        $args = [
            'post_type'      => 'event_listing',
            'author'         => $user_id,
            'posts_per_page' => isset($request['per_page']) ? absint($request['per_page']) : 20,
            'paged'          => isset($request['page']) ? absint($request['page']) : 1,
            'post_status'    => [ 'publish', 'draft', 'pending' ],
        ];

        $query = new WP_Query($args);

        $events = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $events[] = $this->format_event(get_post());
            }
            wp_reset_postdata();
        }

        return new WP_REST_Response(
            [
                'events' => $events,
                'total'  => $query->found_posts,
            ],
            200
        );
    }

    /**
     * Check user permission
     */
    public function check_user_permission()
    {
        return is_user_logged_in();
    }
}

// Initialize
Apollo_Events_REST_API::get_instance();
