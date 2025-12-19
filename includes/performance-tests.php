<?php

// phpcs:ignoreFile
/**
 * Performance Tests Helper
 * TODO 136: Performance testing and optimization
 *
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

/**
 * Performance test helper class
 * TODO 136: Load testing, stress testing, profiling
 */
class Apollo_Events_Performance_Tests
{
    /**
     * Test query performance
     * TODO 136: Database query optimization
     *
     * @param int $event_count Number of events to test
     * @return array Performance metrics
     */
    public static function test_query_performance($event_count = 100)
    {
        $start = microtime(true);

        $query = new WP_Query(
            [
                'post_type'              => 'event_listing',
                'posts_per_page'         => $event_count,
                'no_found_rows'          => true,
                'update_post_meta_cache' => true,
                'update_post_term_cache' => true,
            ]
        );

        $end      = microtime(true);
        $duration = ($end - $start) * 1000;
        // Convert to milliseconds

        return [
            'duration_ms' => round($duration, 2),
            'posts_found' => $query->post_count,
            'queries'     => get_num_queries(),
        ];
    }

    /**
     * Test AJAX performance
     * TODO 136: AJAX stress testing
     *
     * @return array Performance metrics
     */
    public static function test_ajax_performance()
    {
        // Placeholder for AJAX performance testing
        return [
            'avg_response_time_ms' => 150,
            'success_rate'         => 99.9,
        ];
    }

    /**
     * Get memory usage
     * TODO 136: Memory profiling
     *
     * @return array Memory metrics
     */
    public static function get_memory_usage()
    {
        return [
            'current' => size_format(memory_get_usage(true)),
            'peak'    => size_format(memory_get_peak_usage(true)),
            'limit'   => ini_get('memory_limit'),
        ];
    }
}
