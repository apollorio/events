<?php

// phpcs:ignoreFile

/**
 * FILE: apollo-events-manager/includes/helpers/event-data-helper.php
 * Purpose: Centralize all event data retrieval logic (DJs, Local, Banner)
 * Eliminates 300+ lines of duplicated code across templates
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

class Apollo_Event_Data_Helper
{
    /**
     * Get DJ lineup with fallback chain
     *
     * @param int $event_id Event post ID
     * @return array DJ names array
     */
    public static function get_dj_lineup($event_id)
    {
        $dj_names = [];

        // Strategy 1: _event_dj_ids (primary)
        $dj_ids = apollo_aem_parse_ids(
            apollo_get_post_meta($event_id, '_event_dj_ids', true)
        );

        if (! empty($dj_ids)) {
            foreach ($dj_ids as $dj_id) {
                $dj_post = get_post($dj_id);
                if ($dj_post && $dj_post->post_status === 'publish' && $dj_post->post_type === 'event_dj') {
                    $name = apollo_get_post_meta($dj_id, '_dj_name', true) ?: $dj_post->post_title;
                    if ($name) {
                        $dj_names[] = trim($name);
                    }
                }
            }
        }

        // Strategy 2: _event_timetable (fallback)
        if (empty($dj_names)) {
            $timetable = apollo_sanitize_timetable(
                apollo_get_post_meta($event_id, '_event_timetable', true)
            );
            if (empty($timetable)) {
                $timetable = apollo_sanitize_timetable(
                    apollo_get_post_meta($event_id, '_timetable', true)
                );
            }

            if (! empty($timetable)) {
                foreach ($timetable as $slot) {
                    $dj_id = isset($slot['dj']) ? (int) $slot['dj'] : 0;
                    if (! $dj_id) {
                        continue;
                    }

                    $dj_post = get_post($dj_id);
                    if ($dj_post && $dj_post->post_status === 'publish') {
                        $name = apollo_get_post_meta($dj_id, '_dj_name', true) ?: $dj_post->post_title;
                        if ($name) {
                            $dj_names[] = trim($name);
                        }
                    }
                }
            }
        }//end if

        // Strategy 3: Direct _dj_name (last fallback)
        if (empty($dj_names)) {
            $direct = apollo_get_post_meta($event_id, '_dj_name', true);
            if ($direct) {
                $dj_names[] = trim($direct);
            }
        }

        return array_values(array_unique(array_filter($dj_names)));
    }

    /**
     * Format DJ display string with +N indicator
     *
     * @param array $dj_names DJ names
     * @param int   $max_visible Max to show (default 6)
     * @return string HTML formatted DJ list
     */
    public static function format_dj_display($dj_names, $max_visible = 6)
    {
        if (empty($dj_names)) {
            return __('Line-up em breve', 'apollo-events-manager');
        }

        $visible   = array_slice($dj_names, 0, $max_visible);
        $remaining = max(count($dj_names) - $max_visible, 0);

        $display = '<strong>' . esc_html($visible[0]) . '</strong>';
        if (count($visible) > 1) {
            $display .= ', ' . esc_html(implode(', ', array_slice($visible, 1)));
        }
        if ($remaining > 0) {
            $display .= sprintf(' <span style="opacity:0.7">+%d DJs</span>', $remaining);
        }

        return $display;
    }

    /**
     * Get primary local data with comprehensive validation
     *
     * @param int $event_id Event post ID
     * @return array|false ['id' => int, 'name' => string, 'slug' => string, 'address' => string, 'city' => string, 'state' => string, 'lat' => float, 'lng' => float]
     */
    public static function get_local_data($event_id)
    {
        $local_id = 0;

        // Get local ID: primary method
        $local_ids = apollo_aem_parse_ids(
            apollo_get_post_meta($event_id, '_event_local_ids', true)
        );
        $local_id = ! empty($local_ids) ? (int) $local_ids[0] : 0;

        // Fallback: legacy _event_local
        if (! $local_id) {
            $legacy   = apollo_get_post_meta($event_id, '_event_local', true);
            $local_id = $legacy ? (int) $legacy : 0;
        }

        if (! $local_id) {
            return false;
        }

        $local_post = get_post($local_id);
        if (! $local_post || $local_post->post_status !== 'publish') {
            return false;
        }

        // Build comprehensive local data
        $name    = apollo_get_post_meta($local_id, '_local_name', true) ?: $local_post->post_title;
        $address = apollo_get_post_meta($local_id, '_local_address', true);
        $city    = apollo_get_post_meta($local_id, '_local_city', true);
        $state   = apollo_get_post_meta($local_id, '_local_state', true);

        // Get coordinates (multiple key variations)
        $lat = apollo_get_post_meta($local_id, '_local_latitude', true);
        if (! $lat) {
            $lat = apollo_get_post_meta($local_id, '_local_lat', true);
        }

        $lng = apollo_get_post_meta($local_id, '_local_longitude', true);
        if (! $lng) {
            $lng = apollo_get_post_meta($local_id, '_local_lng', true);
        }

        $lat = is_numeric($lat) ? floatval($lat) : 0;
        $lng = is_numeric($lng) ? floatval($lng) : 0;

        // Slug for filtering
        $slug = $local_post->post_name ?: sanitize_title($name);

        return [
            'id'         => $local_id,
            'name'       => $name,
            'slug'       => $slug,
            'address'    => $address,
            'city'       => $city,
            'state'      => $state,
            'region'     => trim(implode(', ', array_filter([ $city, $state ]))),
            'lat'        => $lat,
            'lng'        => $lng,
            'has_coords' => $lat !== 0 && $lng !== 0 && abs($lat) <= 90 && abs($lng) <= 180,
        ];
    }

    /**
     * Get valid banner URL with comprehensive fallbacks
     *
     * @param int $event_id Event post ID
     * @return string Valid image URL
     */
    public static function get_banner_url($event_id)
    {
        $banner = apollo_get_post_meta($event_id, '_event_banner', true);

        // Try 1: Valid URL
        if ($banner && filter_var($banner, FILTER_VALIDATE_URL)) {
            return $banner;
        }

        // Try 2: Attachment ID
        if ($banner && is_numeric($banner)) {
            $url = wp_get_attachment_url($banner);
            if ($url) {
                return $url;
            }
        }

        // Try 3: String URL (even if filter fails)
        if ($banner && is_string($banner)) {
            return $banner;
        }

        // Try 4: Featured image
        if (has_post_thumbnail($event_id)) {
            $url = get_the_post_thumbnail_url($event_id, 'large');
            if ($url) {
                return $url;
            }
        }

        // Default fallback
        return 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070';
    }

    /**
     * Parse and format start date
     *
     * @param string $raw Date string (Y-m-d or Y-m-d H:i:s format)
     * @return array ['timestamp' => int, 'day' => string, 'month_pt' => string, 'iso_date' => string, 'iso_dt' => string]
     */
    public static function parse_event_date($raw)
    {
        $raw = trim((string) $raw);

        if (empty($raw)) {
            return [
                'timestamp' => null,
                'day'       => '',
                'month_pt'  => '',
                'iso_date'  => '',
                'iso_dt'    => '',
            ];
        }

        $ts = strtotime($raw);

        // Fallback: Try Y-m-d format explicitly
        if (! $ts) {
            try {
                $dt = DateTime::createFromFormat('Y-m-d', $raw);
                $ts = $dt instanceof DateTime ? $dt->getTimestamp() : 0;
            } catch (Exception $e) {
                $ts = 0;
            }
        }

        if (! $ts) {
            return [
                'timestamp' => null,
                'day'       => '',
                'month_pt'  => '',
                'iso_date'  => '',
                'iso_dt'    => '',
            ];
        }

        $pt_months = [ 'jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez' ];
        $month_idx = (int) date_i18n('n', $ts) - 1;

        return [
            'timestamp' => $ts,
            'day'       => date_i18n('d', $ts),
            'month_pt'  => $pt_months[ $month_idx ] ?? '',
            'iso_date'  => date_i18n('Y-m-d', $ts),
            'iso_dt'    => date_i18n('Y-m-d H:i:s', $ts),
        ];
    }

    /**
     * Extract YouTube video ID from URL
     *
     * @param string $url YouTube URL
     * @return string|false Video ID or false
     */
    public static function get_youtube_video_id($url)
    {
        if (empty($url)) {
            return false;
        }

        $patterns = [
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
            '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return false;
    }

    /**
     * Build YouTube embed URL
     *
     * @param string $video_id YouTube video ID
     * @return string Embed URL
     */
    public static function build_youtube_embed_url($video_id)
    {
        if (empty($video_id)) {
            return '';
        }

        return sprintf(
            'https://www.youtube.com/embed/%s?autoplay=1&mute=1&loop=1&playlist=%s&controls=0&showinfo=0&modestbranding=1',
            esc_attr($video_id),
            esc_attr($video_id)
        );
    }

    /**
     * Get event coordinates with fallback chain
     *
     * @param int $event_id Event post ID
     * @param int $local_id Local post ID (optional)
     * @return array ['lat' => float, 'lng' => float, 'valid' => bool]
     */
    public static function get_coordinates($event_id, $local_id = 0)
    {
        $lat = $lng = 0;

        // Try 1: Local coordinates
        if ($local_id) {
            foreach ([ '_local_latitude', '_local_lat' ] as $key) {
                if ($val = apollo_get_post_meta($local_id, $key, true)) {
                    $lat = is_numeric($val) ? floatval($val) : 0;
                    if ($lat) {
                        break;
                    }
                }
            }
            foreach ([ '_local_longitude', '_local_lng' ] as $key) {
                if ($val = apollo_get_post_meta($local_id, $key, true)) {
                    $lng = is_numeric($val) ? floatval($val) : 0;
                    if ($lng) {
                        break;
                    }
                }
            }
        }

        // Try 2: Event coordinates
        if (! $lat) {
            foreach ([ '_event_latitude', 'geolocation_lat' ] as $key) {
                if ($val = apollo_get_post_meta($event_id, $key, true)) {
                    $lat = is_numeric($val) ? floatval($val) : 0;
                    if ($lat) {
                        break;
                    }
                }
            }
        }

        if (! $lng) {
            foreach ([ '_event_longitude', 'geolocation_long' ] as $key) {
                if ($val = apollo_get_post_meta($event_id, $key, true)) {
                    $lng = is_numeric($val) ? floatval($val) : 0;
                    if ($lng) {
                        break;
                    }
                }
            }
        }

        // Validate
        $valid = $lat !== 0 && $lng !== 0 && abs($lat) <= 90 && abs($lng) <= 180;

        return [
            'lat'   => $lat,
            'lng'   => $lng,
            'valid' => $valid,
        ];
    }

    /**
     * Get events query with strict validation and proper ordering
     * FASE 1: Listagem confiável e pronta para deploy
     *
     * @param array $args Additional query arguments
     * @return WP_Query Query object with validated events
     */
    public static function get_events_query($args = [])
    {
        $defaults = [
            'post_type'   => 'event_listing',
            'post_status' => 'publish',
            // SEMPRE apenas publicados (excluir drafts, privados, etc)
                            'posts_per_page' => -1,
            'meta_key'                       => '_event_start_date',
            'orderby'                        => 'meta_value',
            'order'                          => 'ASC',
            'update_post_meta_cache'         => true,
            'update_post_term_cache'         => true,
            'no_found_rows'                  => true,
            'meta_query'                     => [
                [
                    'key'     => '_event_start_date',
                    'compare' => 'EXISTS',
                ],
            ],
        ];

        $args = wp_parse_args($args, $defaults);

        // Garantir que apenas eventos publicados sejam retornados
        if (! isset($args['post_status']) || $args['post_status'] !== 'publish') {
            $args['post_status'] = 'publish';
        }

        // Ordenação: meta _event_start_date primeiro, fallback para post_date
        if (! isset($args['orderby']) || $args['orderby'] !== 'meta_value') {
            $args['orderby']  = 'meta_value';
            $args['meta_key'] = '_event_start_date';
        }

        // Se não tiver meta de data, ordenar por post_date
        $query = new WP_Query($args);

        if (is_wp_error($query)) {
            error_log('Apollo Events: WP_Query error - ' . $query->get_error_message());

            return new WP_Query([ 'post__in' => [ 0 ] ]);
            // Retorna query vazia
        }

        return $query;
    }

    /**
     * Get cached event IDs list
     * FASE 1: Cache seguro com transient
     *
     * @param bool $future_only Only future events
     * @param int  $cache_ttl Cache TTL in seconds
     * @return array Array of event post IDs
     */
    public static function get_cached_event_ids($future_only = true, $cache_ttl = null)
    {
        $cache_key    = 'aem_events_transient_list' . ($future_only ? '_future' : '_all');
        $bypass_cache = defined('APOLLO_PORTAL_DEBUG_BYPASS_CACHE') && constant('APOLLO_PORTAL_DEBUG_BYPASS_CACHE');

        if ($bypass_cache) {
            $event_ids = false;
        } else {
            $event_ids = get_transient($cache_key);
        }

        if (false === $event_ids) {
            $query_args = [
                'post_type'      => 'event_listing',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_key'       => '_event_start_date',
                'orderby'        => 'meta_value',
                'order'          => 'ASC',
                'fields'         => 'ids',
                // Apenas IDs para performance
                                    'update_post_meta_cache' => false,
                'update_post_term_cache'                     => false,
                'no_found_rows'                              => true,
            ];

            if ($future_only) {
                $query_args['meta_query'] = [
                    [
                        'key'     => '_event_start_date',
                        'value'   => date('Y-m-d'),
                        'compare' => '>=',
                        'type'    => 'DATE',
                    ],
                ];
            }

            $query = new WP_Query($query_args);

            if (is_wp_error($query)) {
                error_log('Apollo Events: Cache query error - ' . $query->get_error_message());
                $event_ids = [];
            } else {
                $event_ids = array_map('absint', $query->posts);

                // Fallback: se não tiver meta de data, ordenar por post_date
                if (empty($event_ids)) {
                    $fallback_query = new WP_Query(
                        [
                            'post_type'      => 'event_listing',
                            'post_status'    => 'publish',
                            'posts_per_page' => -1,
                            'orderby'        => 'date',
                            'order'          => 'ASC',
                            'fields'         => 'ids',
                            'no_found_rows'  => true,
                        ]
                    );

                    if (! is_wp_error($fallback_query)) {
                        $event_ids = array_map('absint', $fallback_query->posts);
                    }
                }
            }//end if

            // Salvar no cache
            if ($cache_ttl === null) {
                $cache_ttl = defined('APOLLO_PORTAL_CACHE_TTL') ? absint(constant('APOLLO_PORTAL_CACHE_TTL')) : (2 * MINUTE_IN_SECONDS);
            }

            set_transient($cache_key, $event_ids, $cache_ttl);
        }//end if

        return is_array($event_ids) ? $event_ids : [];
    }

    /**
     * Flush events cache
     * FASE 1: Limpar cache quando eventos são salvos
     */
    public static function flush_events_cache()
    {
        delete_transient('aem_events_transient_list_future');
        delete_transient('aem_events_transient_list_all');
        delete_transient('apollo_all_event_ids_' . date('Ymd'));

        // Limpar cache do WordPress também
        wp_cache_delete('apollo_events', 'apollo_events');
    }

    /**
     * FASE 3: Filtrar eventos por período
     *
     * @param array  $event_ids Array of event IDs
     * @param string $period 'today', 'weekend', 'next7days', 'all'
     * @return array Filtered event IDs
     */
    public static function filter_events_by_period($event_ids, $period = 'all')
    {
        if (empty($event_ids) || $period === 'all') {
            return $event_ids;
        }

        $today    = date('Y-m-d');
        $filtered = [];

        foreach ($event_ids as $event_id) {
            $start_date = apollo_get_post_meta($event_id, '_event_start_date', true);
            if (! $start_date) {
                continue;
            }

            $event_date = date('Y-m-d', strtotime($start_date));

            switch ($period) {
                case 'today':
                    if ($event_date === $today) {
                        $filtered[] = $event_id;
                    }

                    break;

                case 'weekend':
                    // Próxima sexta a domingo
                    $today_timestamp = strtotime($today);
                    $today_day       = date('w', $today_timestamp);

                    // Se hoje é sexta, sábado ou domingo, incluir eventos deste fim de semana
                    if ($today_day >= 5) {
                        $friday = date('Y-m-d', strtotime('this friday', $today_timestamp));
                        $sunday = date('Y-m-d', strtotime('this sunday', $today_timestamp));
                    } else {
                        // Se não, pegar próximo fim de semana
                        $friday = date('Y-m-d', strtotime('next friday', $today_timestamp));
                        $sunday = date('Y-m-d', strtotime('next sunday', $today_timestamp));
                    }

                    if ($event_date >= $friday && $event_date <= $sunday) {
                        $filtered[] = $event_id;
                    }

                    break;

                case 'next7days':
                    $next_week = date('Y-m-d', strtotime('+7 days'));
                    if ($event_date >= $today && $event_date <= $next_week) {
                        $filtered[] = $event_id;
                    }

                    break;
            }//end switch
        }//end foreach

        return $filtered;
    }

    /**
     * FASE 3: Obter eventos recomendados (featured)
     *
     * @param array $event_ids Array of event IDs.
     * @return array Featured event IDs
     */
    public static function get_featured_events($event_ids)
    {
        if (empty($event_ids)) {
            return [];
        }

        $featured = [];
        foreach ($event_ids as $event_id) {
            if (apollo_get_post_meta($event_id, '_event_featured', true) === '1') {
                $featured[] = $event_id;
            }
        }

        return $featured;
    }

    /**
     * Get comprehensive event data for single event template
     *
     * @param int $event_id Event post ID.
     * @return array|false Event data array or false if not found.
     */
    public static function get_event_data($event_id)
    {
        $event_id = absint($event_id);
        if (! $event_id) {
            return false;
        }

        $post = get_post($event_id);
        if (! $post || 'event_listing' !== $post->post_type) {
            return false;
        }

        // Basic info.
        $title       = get_the_title($event_id);
        $description = apply_filters('the_content', $post->post_content);

        // Dates.
        $start_date     = apollo_get_post_meta($event_id, '_event_start_date', true);
        $start_time     = apollo_get_post_meta($event_id, '_event_start_time', true);
        $end_time       = apollo_get_post_meta($event_id, '_event_end_time', true);
        $parsed_date    = self::parse_event_date($start_date);
        $formatted_date = '';
        $formatted_time = '';

        if ($parsed_date['timestamp']) {
            $formatted_date = $parsed_date['day'] . ' ' . ucfirst($parsed_date['month_pt']) . " '" . gmdate('y', $parsed_date['timestamp']);
        }

        if ($start_time) {
            $time_obj = DateTime::createFromFormat('H:i', $start_time);
            if (! $time_obj) {
                $time_obj = DateTime::createFromFormat('H:i:s', $start_time);
            }
            $formatted_time = $time_obj ? $time_obj->format('H\hi') : $start_time;
        }

        // Venue data.
        $local_data    = self::get_local_data($event_id);
        $venue_name    = $local_data ? $local_data['name'] : '';
        $venue_address = $local_data ? $local_data['address'] : '';
        $venue_region  = $local_data ? $local_data['region'] : '';
        $venue_images  = [];

        if ($local_data && $local_data['id']) {
            $gallery = apollo_get_post_meta($local_data['id'], '_local_gallery', true);
            if (is_array($gallery)) {
                foreach ($gallery as $img) {
                    if (is_numeric($img)) {
                        $url = wp_get_attachment_url((int) $img);
                        if ($url) {
                            $venue_images[] = $url;
                        }
                    } elseif (is_string($img) && filter_var($img, FILTER_VALIDATE_URL)) {
                        $venue_images[] = $img;
                    }
                }
            }
        }

        // Coordinates.
        $local_id = $local_data ? $local_data['id'] : 0;
        $coords   = self::get_coordinates($event_id, $local_id);

        // Banner and gallery.
        $banner      = self::get_banner_url($event_id);
        $gallery     = [];
        $raw_gallery = apollo_get_post_meta($event_id, '_event_gallery', true);
        if (is_array($raw_gallery)) {
            foreach ($raw_gallery as $img) {
                if (is_numeric($img)) {
                    $url = wp_get_attachment_url((int) $img);
                    if ($url) {
                        $gallery[] = $url;
                    }
                } elseif (is_string($img) && filter_var($img, FILTER_VALIDATE_URL)) {
                    $gallery[] = $img;
                }
            }
        }

        // Video URL.
        $video_url = apollo_get_post_meta($event_id, '_event_video_url', true);
        if (! $video_url) {
            $video_url = apollo_get_post_meta($event_id, '_event_youtube', true);
        }

        // Categories and tags.
        $categories = [];
        $terms_cats = wp_get_post_terms($event_id, 'event_listing_category', [ 'fields' => 'names' ]);
        if (! is_wp_error($terms_cats)) {
            $categories = $terms_cats;
        }

        $tags       = [];
        $terms_tags = wp_get_post_terms($event_id, 'event_listing_tag', [ 'fields' => 'names' ]);
        if (! is_wp_error($terms_tags)) {
            $tags = $terms_tags;
        }

        // Sounds/genres.
        $sounds       = [];
        $terms_sounds = wp_get_post_terms($event_id, 'event_sounds', [ 'fields' => 'names' ]);
        if (! is_wp_error($terms_sounds)) {
            $sounds = $terms_sounds;
        }

        // Event type.
        $event_type = apollo_get_post_meta($event_id, '_event_type', true);

        // Special badge.
        $special_badge = '';
        if (apollo_get_post_meta($event_id, '_event_featured', true) === '1') {
            $special_badge = __('Destaque', 'apollo-events-manager');
        }
        $custom_badge = apollo_get_post_meta($event_id, '_event_special_badge', true);
        if ($custom_badge) {
            $special_badge = $custom_badge;
        }

        // Lineup.
        $lineup    = [];
        $dj_names  = self::get_dj_lineup($event_id);
        $timetable = apollo_get_post_meta($event_id, '_event_timetable', true);

        if (! empty($timetable) && is_array($timetable)) {
            foreach ($timetable as $slot) {
                $dj_id    = isset($slot['dj']) ? (int) $slot['dj'] : 0;
                $dj_post  = $dj_id ? get_post($dj_id) : null;
                $dj_name  = '';
                $dj_photo = '';
                $dj_slug  = '';

                if ($dj_post) {
                    $dj_name  = apollo_get_post_meta($dj_id, '_dj_name', true) ?: $dj_post->post_title;
                    $dj_photo = apollo_get_post_meta($dj_id, '_dj_image', true);
                    if (! $dj_photo && has_post_thumbnail($dj_id)) {
                        $dj_photo = get_the_post_thumbnail_url($dj_id, 'thumbnail');
                    }
                    $dj_slug = $dj_post->post_name;
                }

                $lineup[] = [
                    'name'       => $dj_name,
                    'photo'      => $dj_photo,
                    'slug'       => $dj_slug,
                    'start_time' => isset($slot['start']) ? $slot['start'] : '',
                    'end_time'   => isset($slot['end']) ? $slot['end'] : '',
                ];
            }//end foreach
        } elseif (! empty($dj_names)) {
            foreach ($dj_names as $name) {
                $lineup[] = [
                    'name'       => $name,
                    'photo'      => '',
                    'slug'       => '',
                    'start_time' => '',
                    'end_time'   => '',
                ];
            }
        }//end if

        // Tickets.
        $tickets_url            = apollo_get_post_meta($event_id, '_event_tickets_url', true);
        $alternative_access_url = apollo_get_post_meta($event_id, '_event_alternative_access_url', true);
        $coupon                 = apollo_get_post_meta($event_id, '_event_coupon', true);

        // Final image.
        $final_image = apollo_get_post_meta($event_id, '_event_final_image', true);

        return [
            'id'                     => $event_id,
            'title'                  => $title,
            'description'            => $description,
            'banner'                 => $banner,
            'gallery'                => $gallery,
            'video_url'              => $video_url,
            'start_date'             => $start_date,
            'start_time'             => $start_time,
            'end_time'               => $end_time,
            'formatted_date'         => $formatted_date,
            'formatted_time'         => $formatted_time,
            'venue_name'             => $venue_name,
            'venue_address'          => $venue_address,
            'venue_region'           => $venue_region,
            'venue_images'           => $venue_images,
            'coords'                 => $coords,
            'categories'             => $categories,
            'tags'                   => $tags,
            'sounds'                 => $sounds,
            'type'                   => $event_type,
            'special_badge'          => $special_badge,
            'lineup'                 => $lineup,
            'tickets_url'            => $tickets_url,
            'alternative_access_url' => $alternative_access_url,
            'coupon'                 => $coupon,
            'final_image'            => $final_image,
        ];
    }
}

// Register as available globally
if (! function_exists('apollo_get_event_data')) {
    function apollo_get_event_data($event_id)
    {
        return new stdClass(
            [
                'djs'    => Apollo_Event_Data_Helper::get_dj_lineup($event_id),
                'local'  => Apollo_Event_Data_Helper::get_local_data($event_id),
                'banner' => Apollo_Event_Data_Helper::get_banner_url($event_id),
            ]
        );
    }
}

// FASE 2: Verificar se usuário pode editar evento (autor ou co-autor)
if (! function_exists('apollo_can_user_edit_event')) {
    /**
     * FASE 2: Verificar se usuário pode editar evento
     *
     * @param int $event_id ID do evento
     * @param int $user_id ID do usuário (opcional, usa current_user se não fornecido)
     * @return bool True se usuário pode editar
     */
    function apollo_can_user_edit_event($event_id, $user_id = null)
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return false;
        }

        $event = get_post($event_id);
        if (! $event || $event->post_type !== 'event_listing') {
            return false;
        }

        // Administradores e editores sempre podem editar
        $user = get_user_by('ID', $user_id);
        if ($user && (user_can($user_id, 'edit_others_posts') || user_can($user_id, 'administrator'))) {
            return true;
        }

        // Verificar se é autor
        if ($event->post_author == $user_id) {
            return true;
        }

        // Verificar se está em gestão
        $gestao = apollo_get_post_meta($event_id, '_event_gestao', true);
        $gestao = is_array($gestao) ? array_map('absint', $gestao) : [];

        if (in_array($user_id, $gestao, true)) {
            return true;
        }

        return false;
    }
}//end if

// FASE 2: Filtro WordPress para permitir que usuários em gestão editem eventos
add_filter(
    'user_has_cap',
    function ($allcaps, $cap, $args) {
        // Bug fix: $cap é o array de capabilities do usuário, não a capability sendo verificada
        // A capability sendo verificada está em $args[0]
        if (! isset($args[0]) || $args[0] !== 'edit_post') {
            return $allcaps;
        }

        // $args[1] contém o post_id quando verificando edit_post
        $post_id = isset($args[1]) ? absint($args[1]) : 0;
        if (! $post_id) {
            return $allcaps;
        }

        $post = get_post($post_id);
        if (! $post || $post->post_type !== 'event_listing') {
            return $allcaps;
        }

        // $args[2] contém o user_id quando especificado, senão usar current user
        $user_id = isset($args[2]) ? absint($args[2]) : get_current_user_id();

        // Se usuário pode editar (autor ou co-autor), permitir
        if (apollo_can_user_edit_event($post_id, $user_id)) {
            $allcaps['edit_post'] = true;
        }

        return $allcaps;
    },
    10,
    3
);

// FASE 1: Hook para limpar cache quando eventos são salvos
if (! function_exists('aem_flush_events_cache')) {
    function aem_flush_events_cache($post_id, $post, $update)
    {
        // Ignorar autosaves e revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Apenas para eventos
        if ($post->post_type !== 'event_listing') {
            return;
        }

        // Limpar cache
        Apollo_Event_Data_Helper::flush_events_cache();
    }

    add_action('save_post_event_listing', 'aem_flush_events_cache', 10, 3);
    add_action(
        'delete_post',
        function ($post_id) {
            $post = get_post($post_id);
            if ($post && $post->post_type === 'event_listing') {
                Apollo_Event_Data_Helper::flush_events_cache();
            }
        }
    );
}//end if
