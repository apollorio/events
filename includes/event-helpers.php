<?php

// phpcs:ignoreFile
/**
 * Apollo Events Manager - Event helper functions
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('apollo_aem_normalize_ids')) {
    function apollo_aem_normalize_ids($ids)
    {
        if (! is_array($ids)) {
            $ids = [ $ids ];
        }

        $normalized = [];

        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $normalized[ $id ] = true;
            }
        }

        return array_keys($normalized);
    }
}

if (! function_exists('apollo_aem_parse_ids')) {
    function apollo_aem_parse_ids($raw)
    {
        if (empty($raw) && $raw !== '0') {
            return [];
        }

        if (is_array($raw)) {
            return apollo_aem_normalize_ids($raw);
        }

        if (is_string($raw)) {
            $trim = trim($raw);
            if ($trim === '') {
                return [];
            }

            $json = json_decode($trim, true);
            if (is_array($json)) {
                return apollo_aem_normalize_ids($json);
            }

            $maybe_serialized = maybe_unserialize($trim);
            if (is_array($maybe_serialized)) {
                return apollo_aem_normalize_ids($maybe_serialized);
            }

            if (strpos($trim, ',') !== false) {
                $parts = array_map('trim', explode(',', $trim));

                return apollo_aem_normalize_ids($parts);
            }

            if (is_numeric($trim)) {
                return apollo_aem_normalize_ids([ $trim ]);
            }
        }//end if

        if (is_numeric($raw)) {
            return apollo_aem_normalize_ids([ $raw ]);
        }

        return [];
    }
}//end if

if (! function_exists('apollo_get_primary_local_id')) {
    /**
     * Get primary local ID for an event
     * DEPRECATED: Use apollo_get_event_local_id() instead
     *
     * @param int $event_id Event post ID
     * @return int|false Local ID or false if not found
     * @deprecated Use apollo_get_event_local_id() instead
     */
    function apollo_get_primary_local_id($event_id)
    {
        // Use unified connection manager
        if (class_exists('Apollo_Local_Connection')) {
            $connection = Apollo_Local_Connection::get_instance();

            return $connection->get_local_id($event_id);
        }

        // Fallback to direct meta access (legacy)
        $event_id = absint($event_id);
        if (! $event_id) {
            return false;
        }

        // Try _event_local_ids first (primary method)
        $local_ids_raw = get_post_meta($event_id, '_event_local_ids', true);
        $normalized    = apollo_aem_parse_ids($local_ids_raw);

        if (! empty($normalized)) {
            return (int) $normalized[0];
        }

        // Fallback to legacy _event_local
        $legacy_local = get_post_meta($event_id, '_event_local', true);
        if (is_numeric($legacy_local) && absint($legacy_local) > 0) {
            return absint($legacy_local);
        }

        return false;
    }
}//end if

if (! function_exists('apollo_get_event_favorite_user_ids')) {
    function apollo_get_event_favorite_user_ids($event_id)
    {
        $event_id = absint($event_id);
        if (! $event_id) {
            return [];
        }

        $stored = get_post_meta($event_id, '_apollo_favorited_users', true);
        if (! is_array($stored)) {
            $stored = maybe_unserialize($stored);
        }

        $user_ids = apollo_aem_normalize_ids($stored);
        if (empty($user_ids)) {
            return [];
        }

        $users = get_users(
            [
                'include' => $user_ids,
                'orderby' => 'include',
                'fields'  => [ 'ID' ],
            ]
        );

        $valid_ids = [];
        foreach ($users as $user) {
            $valid_ids[] = (int) $user->ID;
        }

        if (count($valid_ids) !== count($user_ids)) {
            apollo_store_event_favorite_user_ids($event_id, $valid_ids);
        }

        return $valid_ids;
    }
}//end if

if (! function_exists('apollo_store_event_favorite_user_ids')) {
    function apollo_store_event_favorite_user_ids($event_id, $user_ids)
    {
        $event_id = absint($event_id);
        if (! $event_id) {
            return [];
        }

        $user_ids = apollo_aem_normalize_ids($user_ids);

        if (! empty($user_ids)) {
            update_post_meta($event_id, '_apollo_favorited_users', $user_ids);
            update_post_meta($event_id, '_favorites_count', count($user_ids));
        } else {
            delete_post_meta($event_id, '_apollo_favorited_users');
            update_post_meta($event_id, '_favorites_count', 0);
        }

        return $user_ids;
    }
}

if (! function_exists('apollo_aem_extract_initials')) {
    function apollo_aem_extract_initials($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        $parts    = preg_split('/\s+/', $name);
        $initials = '';

        foreach ($parts as $part) {
            $letter = mb_substr($part, 0, 1);
            if ($letter !== '') {
                $initials .= mb_strtoupper($letter);
            }
            if (mb_strlen($initials) >= 2) {
                break;
            }
        }

        return mb_substr($initials, 0, 2);
    }
}//end if

if (! function_exists('apollo_get_event_favorites_snapshot')) {
    function apollo_get_event_favorites_snapshot($event_id, $limit = 8)
    {
        $event_id = absint($event_id);
        if (! $event_id) {
            return [
                'count'                      => 0,
                'avatars'                    => [],
                'remaining'                  => 0,
                'current_user_has_favorited' => false,
            ];
        }

        $user_ids = apollo_get_event_favorite_user_ids($event_id);
        $count    = count($user_ids);

        if ((int) get_post_meta($event_id, '_favorites_count', true) !== $count) {
            update_post_meta($event_id, '_favorites_count', $count);
        }

        $avatars = [];
        if ($count > 0) {
            $slice = array_slice($user_ids, 0, max(1, (int) $limit));

            $users = get_users(
                [
                    'include' => $slice,
                    'orderby' => 'include',
                ]
            );

            $map = [];
            foreach ($users as $user) {
                $map[ $user->ID ] = $user;
            }

            foreach ($slice as $user_id) {
                if (! isset($map[ $user_id ])) {
                    continue;
                }

                $user       = $map[ $user_id ];
                $name       = $user->display_name ? $user->display_name : $user->user_login;
                $avatar_url = get_avatar_url($user->ID, [ 'size' => 96 ]);

                $avatars[] = [
                    'id'       => (int) $user->ID,
                    'name'     => $name,
                    'avatar'   => $avatar_url ? $avatar_url : '',
                    'initials' => apollo_aem_extract_initials($name),
                ];
            }
        }//end if

        $remaining                  = max(0, $count - count($avatars));
        $current_user_has_favorited = is_user_logged_in() && in_array(get_current_user_id(), $user_ids, true);

        return [
            'count'                      => $count,
            'avatars'                    => $avatars,
            'remaining'                  => $remaining,
            'current_user_has_favorited' => $current_user_has_favorited,
        ];
    }
}//end if

if (! function_exists('apollo_aem_build_lineup_entry')) {
    function apollo_aem_build_lineup_entry($slot, $event_id = 0)
    {
        if (! is_array($slot)) {
            $slot = [ 'dj' => $slot ];
        }

        $dj_ref = $slot['dj'] ?? ($slot['dj_id'] ?? ($slot['id'] ?? null));

        $from = $slot['from'] ?? ($slot['dj_time_in'] ?? ($slot['time_in'] ?? ($slot['start'] ?? ($slot['time_start'] ?? ''))));
        $to   = $slot['to']   ?? ($slot['dj_time_out'] ?? ($slot['time_out'] ?? ($slot['end'] ?? ($slot['time_end'] ?? ''))));

        if ((! $from || ! $to) && ! empty($slot['time'])) {
            $parts = preg_split('/\s*-\s*/', $slot['time']);
            if ($from === '' && isset($parts[0])) {
                $from = $parts[0];
            }
            if ($to === '' && isset($parts[1])) {
                $to = $parts[1];
            }
        }

        $entry = [
            'id'        => 0,
            'name'      => '',
            'from'      => $from ? sanitize_text_field($from) : '',
            'to'        => $to ? sanitize_text_field($to) : '',
            'photo'     => '',
            'permalink' => '',
        ];

        if (is_numeric($dj_ref) && $dj_ref > 0) {
            $dj_id       = absint($dj_ref);
            $entry['id'] = $dj_id;

            $dj_post = get_post($dj_id);
            if (! $dj_post || $dj_post->post_status !== 'publish') {
                return null;
            }

            $name = get_post_meta($dj_id, '_dj_name', true);
            if ($name === '') {
                $name = $dj_post->post_title;
            }
            if ($name === '') {
                return null;
            }

            $entry['name']      = $name;
            $entry['permalink'] = get_permalink($dj_id);

            $photo = get_post_meta($dj_id, '_photo', true);
            if ($photo) {
                $entry['photo'] = is_numeric($photo) ? wp_get_attachment_url($photo) : esc_url_raw($photo);
            }

            if ($entry['photo'] === '' && has_post_thumbnail($dj_id)) {
                $entry['photo'] = get_the_post_thumbnail_url($dj_id, 'medium');
            }
        } else {
            $name = '';
            if (is_string($dj_ref)) {
                $name = $dj_ref;
            } elseif (! empty($slot['name'])) {
                $name = $slot['name'];
            } elseif (! empty($slot['dj_name'])) {
                $name = $slot['dj_name'];
            }

            $name = sanitize_text_field($name);
            if ($name === '') {
                return null;
            }

            $entry['name'] = $name;
        }//end if

        if ($entry['photo'] === '' && ! empty($slot['photo'])) {
            $entry['photo'] = esc_url_raw($slot['photo']);
        }
        if ($entry['photo'] === '' && ! empty($slot['image'])) {
            $entry['photo'] = esc_url_raw($slot['image']);
        }

        if ($entry['permalink'] === '' && ! empty($slot['permalink'])) {
            $entry['permalink'] = esc_url_raw($slot['permalink']);
        }

        return $entry;
    }
}//end if

if (! function_exists('apollo_get_event_lineup')) {
    function apollo_get_event_lineup($event_id)
    {
        $event_id = absint($event_id);
        if (! $event_id) {
            return [];
        }

        $entries    = [];
        $seen_ids   = [];
        $seen_names = [];

        $append = static function ($entry) use (&$entries, &$seen_ids, &$seen_names) {
            if (! is_array($entry) || empty($entry['name'])) {
                return;
            }

            $id = isset($entry['id']) ? (int) $entry['id'] : 0;
            if ($id > 0) {
                if (isset($seen_ids[ $id ])) {
                    return;
                }
                $seen_ids[ $id ] = true;
            } else {
                $key = strtolower(remove_accents($entry['name']));
                if ($key === '' || isset($seen_names[ $key ])) {
                    return;
                }
                $seen_names[ $key ] = true;
            }

            $entries[] = $entry;
        };

        // ✅ PRIORITY 1: Try _event_timetable (with times)
        $timetable_raw = get_post_meta($event_id, '_event_timetable', true);
        if (! empty($timetable_raw)) {
            $timetable = apollo_sanitize_timetable($timetable_raw);
            if (! empty($timetable) && is_array($timetable)) {
                foreach ($timetable as $slot) {
                    $entry = apollo_aem_build_lineup_entry($slot, $event_id);
                    if ($entry) {
                        $append($entry);
                    }
                }
            }
        }

        // ✅ PRIORITY 2: Try legacy _timetable
        if (empty($entries)) {
            $legacy = get_post_meta($event_id, '_timetable', true);
            if (! empty($legacy)) {
                if (is_string($legacy)) {
                    $legacy = maybe_unserialize($legacy);
                }
                if (is_array($legacy)) {
                    foreach ($legacy as $slot) {
                        $entry = apollo_aem_build_lineup_entry($slot, $event_id);
                        if ($entry) {
                            $append($entry);
                        }
                    }
                }
            }
        }

        // ✅ PRIORITY 3: Get DJs from _event_dj_ids (even without timetable)
        // This ensures DJs are shown even if timetable is empty
        $dj_ids_raw = get_post_meta($event_id, '_event_dj_ids', true);
        $dj_ids     = apollo_aem_parse_ids($dj_ids_raw);

        if (! empty($dj_ids)) {
            // Check which DJs are already in entries
            $existing_dj_ids = [];
            foreach ($entries as $entry) {
                if (! empty($entry['id']) && $entry['id'] > 0) {
                    $existing_dj_ids[] = (int) $entry['id'];
                }
            }

            // Add DJs that are not yet in entries
            foreach ($dj_ids as $dj_id) {
                $dj_id_int = is_numeric($dj_id) ? (int) $dj_id : 0;
                if ($dj_id_int > 0 && ! in_array($dj_id_int, $existing_dj_ids, true)) {
                    // Create entry without time (will show DJ name only)
                    $entry = apollo_aem_build_lineup_entry([ 'dj' => $dj_id_int ], $event_id);
                    if ($entry) {
                        $append($entry);
                    }
                }
            }
        }//end if

        return $entries;
    }
}//end if

/**
 * Get DJ display name
 * Uses post_title as primary source (no more duplicate _dj_name meta)
 *
 * @param int|WP_Post $dj DJ post ID or WP_Post object
 * @return string DJ name
 */
if (! function_exists('apollo_get_dj_name')) {
    function apollo_get_dj_name($dj)
    {
        if (is_numeric($dj)) {
            $dj = get_post($dj);
        }

        if (! $dj instanceof WP_Post || $dj->post_type !== 'event_dj') {
            return '';
        }

        // Legacy fallback: check meta first (for old data)
        $meta_name = get_post_meta($dj->ID, '_dj_name', true);
        if (! empty($meta_name)) {
            return $meta_name;
        }

        // Primary: use post_title
        return $dj->post_title;
    }
}

/**
 * Get DJ bio/description
 * Uses post_content as primary source (no more duplicate _dj_bio meta)
 *
 * @param int|WP_Post $dj DJ post ID or WP_Post object
 * @return string DJ bio
 */
if (! function_exists('apollo_get_dj_bio')) {
    function apollo_get_dj_bio($dj)
    {
        if (is_numeric($dj)) {
            $dj = get_post($dj);
        }

        if (! $dj instanceof WP_Post || $dj->post_type !== 'event_dj') {
            return '';
        }

        // Legacy fallback: check meta first (for old data)
        $meta_bio = get_post_meta($dj->ID, '_dj_bio', true);
        if (! empty($meta_bio)) {
            return $meta_bio;
        }

        // Primary: use post_content
        return $dj->post_content;
    }
}

/**
 * Get Local display name
 * Uses post_title as primary source (no more duplicate _local_name meta)
 *
 * @param int|WP_Post $local Local post ID or WP_Post object
 * @return string Local name
 */
if (! function_exists('apollo_get_local_name')) {
    function apollo_get_local_name($local)
    {
        if (is_numeric($local)) {
            $local = get_post($local);
        }

        if (! $local instanceof WP_Post || $local->post_type !== 'event_local') {
            return '';
        }

        // Legacy fallback: check meta first (for old data)
        $meta_name = get_post_meta($local->ID, '_local_name', true);
        if (! empty($meta_name)) {
            return $meta_name;
        }

        // Primary: use post_title
        return $local->post_title;
    }
}

/**
 * Get Local description
 * Uses post_content as primary source (no more duplicate _local_description meta)
 *
 * @param int|WP_Post $local Local post ID or WP_Post object
 * @return string Local description
 */
if (! function_exists('apollo_get_local_description')) {
    function apollo_get_local_description($local)
    {
        if (is_numeric($local)) {
            $local = get_post($local);
        }

        if (! $local instanceof WP_Post || $local->post_type !== 'event_local') {
            return '';
        }

        // Legacy fallback: check meta first (for old data)
        $meta_desc = get_post_meta($local->ID, '_local_description', true);
        if (! empty($meta_desc)) {
            return $meta_desc;
        }

        // Primary: use post_content
        return $local->post_content;
    }
}
