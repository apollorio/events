<?php

// phpcs:ignoreFile
/**
 * Apollo Events Manager - Meta Helpers
 *
 * Wrapper functions that use the sanitization system
 * Use these instead of direct get_post_meta/update_post_meta calls
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Get post meta with sanitization (STRICT MODE)
 *
 * @param int    $post_id Post ID
 * @param string $meta_key Meta key
 * @param bool   $single Single value
 * @return mixed Sanitized meta value
 */
function apollo_get_post_meta($post_id, $meta_key, $single = true)
{
    if (! class_exists('Apollo_Events_Sanitization')) {
        // Fallback to WordPress native if sanitization class not available
        return get_post_meta($post_id, $meta_key, $single);
    }

    return Apollo_Events_Sanitization::get_post_meta($post_id, $meta_key, $single);
}

/**
 * Update post meta with sanitization (STRICT MODE)
 *
 * @param int    $post_id Post ID
 * @param string $meta_key Meta key
 * @param mixed  $meta_value Meta value
 * @return int|false Meta ID on success, false on failure
 */
function apollo_update_post_meta($post_id, $meta_key, $meta_value)
{
    if (! class_exists('Apollo_Events_Sanitization')) {
        // Fallback to WordPress native if sanitization class not available
        return update_post_meta($post_id, $meta_key, $meta_value);
    }

    return Apollo_Events_Sanitization::update_post_meta($post_id, $meta_key, $meta_value);
}

/**
 * Delete post meta with validation (STRICT MODE)
 *
 * @param int    $post_id Post ID
 * @param string $meta_key Meta key
 * @return bool True on success, false on failure
 */
function apollo_delete_post_meta($post_id, $meta_key)
{
    if (! class_exists('Apollo_Events_Sanitization')) {
        // Fallback to WordPress native if sanitization class not available
        return delete_post_meta($post_id, $meta_key);
    }

    return Apollo_Events_Sanitization::delete_post_meta($post_id, $meta_key);
}

/**
 * Sanitize meta key slug (STRICT MODE)
 *
 * @param string $meta_key Raw meta key
 * @return string Sanitized meta key
 */
function apollo_sanitize_meta_key($meta_key)
{
    if (! class_exists('Apollo_Events_Sanitization')) {
        return sanitize_key($meta_key);
    }

    return Apollo_Events_Sanitization::sanitize_meta_key($meta_key);
}

/**
 * Validate meta key (STRICT MODE)
 *
 * @param string $meta_key Meta key to validate
 * @return bool True if valid
 */
function apollo_validate_meta_key($meta_key)
{
    if (! class_exists('Apollo_Events_Sanitization')) {
        return true;
        // Allow all if sanitization not available
    }

    return Apollo_Events_Sanitization::validate_meta_key($meta_key);
}
