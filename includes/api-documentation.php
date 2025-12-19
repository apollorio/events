<?php

// phpcs:ignoreFile
/**
 * API Documentation Generator
 * TODO 133: Public API documentation
 *
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

/**
 * Generate API documentation
 * TODO 133: Public API hooks, filters, actions
 */
class Apollo_Events_API_Docs
{
    /**
     * Get all public hooks
     * TODO 133: List all hooks
     *
     * @return array List of hooks
     */
    public static function get_hooks()
    {
        return [
            'apollo_events_before_event_card' => [
                'description' => 'Fires before event card output',
                'params'      => [ '$event_id', '$event_data' ],
            ],
            'apollo_events_after_event_card' => [
                'description' => 'Fires after event card output',
                'params'      => [ '$event_id', '$event_data' ],
            ],
            // Add more hooks...
        ];
    }

    /**
     * Get all public filters
     * TODO 133: List all filters
     *
     * @return array List of filters
     */
    public static function get_filters()
    {
        return [
            'apollo_events_event_card_classes' => [
                'description' => 'Filter event card CSS classes',
                'params'      => [ '$classes', '$event_id' ],
            ],
            // Add more filters...
        ];
    }

    /**
     * Get all public actions
     * TODO 133: List all actions
     *
     * @return array List of actions
     */
    public static function get_actions()
    {
        return [
            'apollo_events_event_viewed' => [
                'description' => 'Fires when an event is viewed',
                'params'      => [ '$event_id', '$view_type' ],
            ],
            // Add more actions...
        ];
    }
}
