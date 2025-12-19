<?php

// phpcs:ignoreFile
/**
 * Performance Optimization Helper
 * TODO 131: Centralized performance optimizations
 *
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

/**
 * Performance optimizer class
 */
class Apollo_Events_Performance_Optimizer
{
    /**
     * Cache group name
     */
    public const CACHE_GROUP = 'apollo_events';

    /**
     * Cache expiration (1 hour)
     */
    public const CACHE_EXPIRATION = 3600;

    /**
     * Get cached value
     * TODO 131: Database query caching
     *
     * @param string $key Cache key
     * @return mixed|false Cached value or false
     */
    public static function get_cache($key)
    {
        return wp_cache_get($key, self::CACHE_GROUP);
    }

    /**
     * Set cached value
     * TODO 131: Database query caching
     *
     * @param string $key Cache key
     * @param mixed  $value Value to cache
     * @param int    $expiration Expiration in seconds
     * @return bool True on success
     */
    public static function set_cache($key, $value, $expiration = null)
    {
        if ($expiration === null) {
            $expiration = self::CACHE_EXPIRATION;
        }

        return wp_cache_set($key, $value, self::CACHE_GROUP, $expiration);
    }

    /**
     * Delete cached value
     * TODO 131: Cache invalidation
     *
     * @param string $key Cache key
     * @return bool True on success
     */
    public static function delete_cache($key)
    {
        return wp_cache_delete($key, self::CACHE_GROUP);
    }

    /**
     * Clear all Apollo Events cache
     * TODO 131: Bulk cache invalidation
     *
     * @return bool True on success
     */
    public static function clear_cache()
    {
        wp_cache_flush_group(self::CACHE_GROUP);

        return true;
    }

    /**
     * Optimize database query
     * TODO 131: Query optimization
     *
     * @param array $args Query arguments
     * @return array Optimized arguments
     */
    public static function optimize_query($args)
    {
        // Limit posts per page if not set
        if (! isset($args['posts_per_page']) || $args['posts_per_page'] > 100) {
            $args['posts_per_page'] = 50;
            // Reasonable default
        }

        // Add no_found_rows for better performance when pagination not needed
        if (! isset($args['no_found_rows'])) {
            $args['no_found_rows'] = true;
        }

        // Add update_post_meta_cache for better performance
        if (! isset($args['update_post_meta_cache'])) {
            $args['update_post_meta_cache'] = true;
        }

        // Add update_post_term_cache for better performance
        if (! isset($args['update_post_term_cache'])) {
            $args['update_post_term_cache'] = true;
        }

        return $args;
    }

    /**
     * Lazy load images
     * TODO 131: Image lazy loading
     *
     * @param string $image_url Image URL
     * @param string $alt Alt text
     * @param array  $attributes Additional attributes
     * @return string Image HTML with lazy loading
     */
    public static function lazy_image($image_url, $alt = '', $attributes = [])
    {
        $default_attrs = [
            'loading'  => 'lazy',
            'decoding' => 'async',
            'src'      => esc_url($image_url),
            'alt'      => esc_attr($alt),
        ];

        $attrs        = array_merge($default_attrs, $attributes);
        $attrs_string = '';

        foreach ($attrs as $key => $value) {
            $attrs_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }

        return '<img' . $attrs_string . '>';
    }

    /**
     * Defer script loading
     * TODO 131: Script optimization
     *
     * @param string $handle Script handle
     * @param bool   $defer Use defer attribute
     * @param bool   $async Use async attribute
     */
    public static function defer_script($handle, $defer = true, $async = false)
    {
        add_filter(
            'script_loader_tag',
            function ($tag, $script_handle) use ($handle, $defer, $async) {
                if ($script_handle === $handle) {
                    if ($defer) {
                        $tag = str_replace(' src', ' defer src', $tag);
                    }
                    if ($async) {
                        $tag = str_replace(' src', ' async src', $tag);
                    }
                }

                return $tag;
            },
            10,
            2
        );
    }

    /**
     * Preload critical resources
     * TODO 131: Resource preloading
     *
     * @param string $url Resource URL
     * @param string $as Resource type (style, script, font, image)
     */
    public static function preload_resource($url, $as = 'style')
    {
        add_action(
            'wp_head',
            function () use ($url, $as) {
                echo '<link rel="preload" href="' . esc_url($url) . '" as="' . esc_attr($as) . '">' . "\n";
            },
            1
        );
    }

    /**
     * Minify inline CSS
     * TODO 131: CSS optimization
     *
     * @param string $css CSS to minify
     * @return string Minified CSS
     */
    public static function minify_css($css)
    {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);

        // Remove whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        $css = str_replace([ ' {', '{ ', ' }', '} ', ': ', ' :', '; ', ' ;' ], [ '{', '{', '}', '}', ':', ':', ';', ';' ], $css);

        return trim($css);
    }

    /**
     * Get optimized image size
     * TODO 131: Image optimization
     *
     * @param int    $attachment_id Attachment ID
     * @param string $size Image size
     * @return array|false Image data or false
     */
    public static function get_optimized_image($attachment_id, $size = 'medium')
    {
        $image = wp_get_attachment_image_src($attachment_id, $size);

        if (! $image) {
            return false;
        }

        // Use WebP if available
        $webp_url = str_replace('.jpg', '.webp', $image[0]);
        $webp_url = str_replace('.png', '.webp', $webp_url);

        // Check if WebP exists (simplified check)
        if (file_exists(str_replace(home_url(), ABSPATH, $webp_url))) {
            $image[0] = $webp_url;
        }

        return $image;
    }
}
