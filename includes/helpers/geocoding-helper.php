<?php
/**
 * Geocoding Helper for Apollo Events Manager
 * Automatically geocodes addresses to lat/lng using Nominatim (OpenStreetMap)
 * 
 * @package Apollo_Events_Manager
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Geocode address to lat/lng using Nominatim (OpenStreetMap)
 * 
 * @param string $address Full address (street + city)
 * @return array ['lat' => float|null, 'lng' => float|null, 'error' => string|null]
 */
function apollo_geocode_address($address) {
    $address = trim((string) $address);
    if (empty($address)) {
        return ['lat' => null, 'lng' => null, 'error' => 'Empty address'];
    }

    // Build query URL for Nominatim
    $query = urlencode($address . ', Rio de Janeiro, Brasil');
    $url = "https://nominatim.openstreetmap.org/search?q={$query}&format=json&limit=1&addressdetails=1";
    
    // Add User-Agent header (required by Nominatim)
    $args = [
        'timeout' => 10,
        'headers' => [
            'User-Agent' => 'Apollo Events Manager/1.0 (WordPress Plugin)'
        ]
    ];

    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        return [
            'lat' => null,
            'lng' => null,
            'error' => $response->get_error_message()
        ];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!is_array($data) || empty($data)) {
        return [
            'lat' => null,
            'lng' => null,
            'error' => 'No results found'
        ];
    }

    $result = $data[0];
    $lat = isset($result['lat']) ? (float) $result['lat'] : null;
    $lng = isset($result['lon']) ? (float) $result['lon'] : null;

    return [
        'lat' => $lat,
        'lng' => $lng,
        'error' => null
    ];
}

/**
 * Auto-geocode event address on save
 * 
 * @param int $post_id Post ID
 * @param WP_Post $post Post object
 * @param bool $update Whether this is an update
 */
function apollo_auto_geocode_event($post_id, $post, $update) {
    // Skip autosaves and revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id)) {
        return;
    }

    // Only for event_listing
    if ($post->post_type !== 'event_listing') {
        return;
    }

    // Check if coordinates already exist
    $existing_lat = get_post_meta($post_id, '_event_lat', true);
    $existing_lng = get_post_meta($post_id, '_event_lng', true);
    
    if (!empty($existing_lat) && !empty($existing_lng)) {
        return; // Already geocoded
    }

    // Get address from event or venue
    $address = get_post_meta($post_id, '_event_address', true);
    $city = get_post_meta($post_id, '_event_city', true);
    
    if (empty($address) && empty($city)) {
        // Try to get from venue
        $venue_id = get_post_meta($post_id, '_event_venue_id', true);
        if (!$venue_id) {
            $venue_id = get_post_meta($post_id, '_event_local_id', true);
        }
        
        if ($venue_id) {
            $address = get_post_meta($venue_id, '_venue_address', true);
            $city = get_post_meta($venue_id, '_venue_city', true);
        }
    }

    // Build full address
    $full_address = trim($address . ' ' . $city);
    if (empty($full_address)) {
        return; // No address to geocode
    }

    // Geocode
    $coords = apollo_geocode_address($full_address);
    
    if ($coords['lat'] && $coords['lng']) {
        update_post_meta($post_id, '_event_lat', $coords['lat']);
        update_post_meta($post_id, '_event_lng', $coords['lng']);
        
        // Log success (only in debug mode)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Apollo Events: Geocoded event {$post_id} - {$full_address} => {$coords['lat']}, {$coords['lng']}");
        }
    } else {
        // Log error (only in debug mode)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Apollo Events: Failed to geocode event {$post_id} - {$full_address}: " . ($coords['error'] ?? 'Unknown error'));
        }
    }
}

/**
 * Auto-geocode venue address on save
 * 
 * @param int $post_id Post ID
 * @param WP_Post $post Post object
 * @param bool $update Whether this is an update
 */
function apollo_auto_geocode_venue($post_id, $post, $update) {
    // Skip autosaves and revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id)) {
        return;
    }

    // Only for venue/local post types
    $venue_post_types = ['event_local', 'venue', 'local'];
    if (!in_array($post->post_type, $venue_post_types, true)) {
        return;
    }

    // Check if coordinates already exist
    $existing_lat = get_post_meta($post_id, '_venue_lat', true);
    $existing_lng = get_post_meta($post_id, '_venue_lng', true);
    
    if (!empty($existing_lat) && !empty($existing_lng)) {
        return; // Already geocoded
    }

    // Get address
    $address = get_post_meta($post_id, '_venue_address', true);
    $city = get_post_meta($post_id, '_venue_city', true);
    
    if (empty($address) && empty($city)) {
        return; // No address to geocode
    }

    // Build full address
    $full_address = trim($address . ' ' . $city);
    if (empty($full_address)) {
        return;
    }

    // Geocode
    $coords = apollo_geocode_address($full_address);
    
    if ($coords['lat'] && $coords['lng']) {
        update_post_meta($post_id, '_venue_lat', $coords['lat']);
        update_post_meta($post_id, '_venue_lng', $coords['lng']);
        
        // Log success (only in debug mode)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Apollo Events: Geocoded venue {$post_id} - {$full_address} => {$coords['lat']}, {$coords['lng']}");
        }
    } else {
        // Log error (only in debug mode)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Apollo Events: Failed to geocode venue {$post_id} - {$full_address}: " . ($coords['error'] ?? 'Unknown error'));
        }
    }
}

// Hook into save_post for events
add_action('save_post_event_listing', 'apollo_auto_geocode_event', 20, 3);

// Hook into save_post for venues (multiple post types)
add_action('save_post_event_local', 'apollo_auto_geocode_venue', 20, 3);
add_action('save_post_venue', 'apollo_auto_geocode_venue', 20, 3);
add_action('save_post_local', 'apollo_auto_geocode_venue', 20, 3);

