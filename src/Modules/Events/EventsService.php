<?php
/**
 * Events Service
 *
 * Core events management
 *
 * @package Apollo_Events_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Events Service Class
 */
class Apollo_Events_Service {

	/**
	 * Initialize events
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_event_post_type' ) );
	}

	/**
	 * Register event post type
	 */
	public static function register_event_post_type() {
		// Use centralized registry instead of direct registration
		add_filter(
			'apollo_core_registry',
			function ( $defs ) {
				$defs['post_types']['event'] = array(
					'label'        => 'Events',
					'public'       => true,
					'show_in_rest' => true,
					'supports'     => array( 'title', 'editor', 'thumbnail' ),
				);
				return $defs;
			}
		);
	}
}

// Initialize
Apollo_Events_Service::init();
