<?php
// phpcs:ignoreFile
/**
 * Apollo Events Manager - Admin Dashboard module
 * Path: apollo-events-manager/includes/admin-dashboard.php
 *
 * REST endpoints, admin UI, DB tables for analytics/likes/technotes.
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Apollo Admin Dashboard Class
 */
class Apollo_Admin_Dashboard
{
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('admin_menu', [ $this, 'add_admin_menu' ]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueue_assets' ]);
        add_action('rest_api_init', [ $this, 'register_rest_routes' ]);
    }

    /**
     * Install database tables
     */
    public static function install_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Analytics table
        $analytics_table = $wpdb->prefix . 'apollo_analytics';
        $analytics_sql   = "CREATE TABLE IF NOT EXISTS {$analytics_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT 0,
            event_type varchar(50) NOT NULL DEFAULT 'pageview',
            event_name varchar(100) DEFAULT NULL,
            properties longtext DEFAULT NULL,
            ip_hash varchar(64) DEFAULT NULL,
            ua_hash varchar(64) DEFAULT NULL,
            country varchar(2) DEFAULT NULL,
            device_type varchar(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) {$charset_collate};";

        // Likes table
        $likes_table = $wpdb->prefix . 'apollo_likes';
        $likes_sql   = "CREATE TABLE IF NOT EXISTS {$likes_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT 0,
            ip_hash varchar(64) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY event_user (event_id, user_id),
            KEY event_id (event_id),
            KEY user_id (user_id)
        ) {$charset_collate};";

        // Venue tech notes table
        $technotes_table = $wpdb->prefix . 'apollo_venue_technotes';
        $technotes_sql   = "CREATE TABLE IF NOT EXISTS {$technotes_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            venue_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            notes longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY venue_user (venue_id, user_id),
            KEY venue_id (venue_id),
            KEY user_id (user_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($analytics_sql);
        dbDelta($likes_sql);
        dbDelta($technotes_sql);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'apollo-events',
            __('Apollo Dashboard', 'apollo-events-manager'),
            __('Dashboard', 'apollo-events-manager'),
            'view_apollo_event_stats',
            'apollo-dashboard',
            [ $this, 'render_dashboard' ]
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook)
    {
        if (strpos($hook, 'apollo-dashboard') === false) {
            return;
        }

        // DataTables
        wp_enqueue_style(
            'datatables-css',
            'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
            [],
            '1.13.7'
        );
        wp_enqueue_script(
            'datatables-js',
            'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
            [ 'jquery' ],
            '1.13.7',
            true
        );

        // Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );

        // Admin dashboard assets
        wp_enqueue_style(
            'apollo-admin-dashboard',
            APOLLO_APRIO_URL . 'assets/admin-dashboard.css',
            [ 'apollo-shadcn-components' ],
            APOLLO_APRIO_VERSION
        );

        wp_enqueue_script(
            'apollo-admin-dashboard',
            APOLLO_APRIO_URL . 'assets/admin-dashboard.js',
            [ 'jquery', 'datatables-js', 'chartjs' ],
            APOLLO_APRIO_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'apollo-admin-dashboard',
            'apolloDashboard',
            [
                'restUrl' => rest_url('apollo/v1/'),
                'nonce'   => wp_create_nonce('wp_rest'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
            ]
        );
    }

    /**
     * Register REST routes
     */
    public function register_rest_routes()
    {
        register_rest_route(
            'apollo/v1',
            'estatisticas',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'rest_get_analytics' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ]
        );

        register_rest_route(
            'apollo/v1',
            'estatisticas',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'rest_post_analytics' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'event_id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                    'event_type' => [
                        'required'          => false,
                        'type'              => 'string',
                        'default'           => 'pageview',
                        'sanitize_callback' => 'sanitize_key',
                    ],
                    'event_name' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]
        );

        register_rest_route(
            'apollo/v1',
            'wow',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'rest_get_likes' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ]
        );

        register_rest_route(
            'apollo/v1',
            'wow',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'rest_post_like' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'event_id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ]
        );

        register_rest_route(
            'apollo/v1',
            'technotes/(?P<venue_id>\d+)',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'rest_get_technotes' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ]
        );

        register_rest_route(
            'apollo/v1',
            'technotes/(?P<venue_id>\d+)',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'rest_post_technotes' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ]
        );
    }

    /**
     * Check REST permissions
     */
    public function check_permissions()
    {
        return current_user_can('view_apollo_event_stats') || current_user_can('manage_options');
    }

    /**
     * REST: Get analytics
     */
    public function rest_get_analytics($request)
    {
        global $wpdb;

        $params     = $request->get_query_params();
        $event_id   = isset($params['event_id']) ? absint($params['event_id']) : 0;
        $start_date = isset($params['start_date']) ? sanitize_text_field($params['start_date']) : '';
        $end_date   = isset($params['end_date']) ? sanitize_text_field($params['end_date']) : '';

        $table        = $wpdb->prefix . 'apollo_analytics';
        $where        = [ '1=1' ];
        $where_values = [];

        if ($event_id > 0) {
            $where[]        = 'event_id = %d';
            $where_values[] = $event_id;
        }

        if ($start_date) {
            $where[]        = 'created_at >= %s';
            $where_values[] = $start_date;
        }

        if ($end_date) {
            $where[]        = 'created_at <= %s';
            $where_values[] = $end_date;
        }

        $where_sql = implode(' AND ', $where);

        if (! empty($where_values)) {
            $query = $wpdb->prepare(
                "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT 1000",
                $where_values
            );
        } else {
            // Validate table name matches expected pattern
            if ( ! preg_match( '/^' . preg_quote( $wpdb->prefix, '/' ) . 'apollo_\w+$/', $table ) ) {
                error_log( 'Apollo Events: Invalid table name in admin dashboard: ' . $table );
                return new WP_REST_Response( [], 200 );
            }
            // Use esc_sql for table name (safe after validation)
            $safe_table = esc_sql( $table );
            $query      = "SELECT * FROM {$safe_table} ORDER BY created_at DESC LIMIT 1000";
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $results = $wpdb->get_results( $query, ARRAY_A );

        return new WP_REST_Response($results, 200);
    }

    /**
     * REST: Post analytics
     */
    public function rest_post_analytics($request)
    {
        global $wpdb;

        $params     = $request->get_json_params();
        $event_id   = isset($params['event_id']) ? absint($params['event_id']) : 0;
        $event_type = isset($params['event_type']) ? sanitize_key($params['event_type']) : 'pageview';
        $event_name = isset($params['event_name']) ? sanitize_text_field($params['event_name']) : null;
        $properties = isset($params['properties']) ? wp_json_encode($params['properties']) : null;

        if (! $event_id) {
            return new WP_Error('missing_event_id', 'Event ID is required', [ 'status' => 400 ]);
        }

        $user_id = is_user_logged_in() ? get_current_user_id() : 0;
        $ip      = $this->get_client_ip();
        $ip_hash = $ip ? hash('sha256', $ip) : null;
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Only used for hashing.
        $ua      = isset($_SERVER['HTTP_USER_AGENT']) ? wp_unslash($_SERVER['HTTP_USER_AGENT']) : '';
        $ua_hash = $ua ? hash('sha256', $ua) : null;

        // Detect country and device (simplified)
        $country     = $this->detect_country();
        $device_type = $this->detect_device();

        $table  = $wpdb->prefix . 'apollo_analytics';
        $result = $wpdb->insert(
            $table,
            [
                'event_id'    => $event_id,
                'user_id'     => $user_id,
                'event_type'  => $event_type,
                'event_name'  => $event_name,
                'properties'  => $properties,
                'ip_hash'     => $ip_hash,
                'ua_hash'     => $ua_hash,
                'country'     => $country,
                'device_type' => $device_type,
            ],
            [ '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );

        if ($result) {
            return new WP_REST_Response(
                [
                    'success' => true,
                    'id'      => $wpdb->insert_id,
                ],
                201
            );
        }

        return new WP_Error('insert_failed', 'Failed to insert analytics', [ 'status' => 500 ]);
    }

    /**
     * REST: Get likes
     */
    public function rest_get_likes($request)
    {
        global $wpdb;

        $params   = $request->get_query_params();
        $event_id = isset($params['event_id']) ? absint($params['event_id']) : 0;

        $table = $wpdb->prefix . 'apollo_likes';

        if ($event_id > 0) {
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table} WHERE event_id = %d",
                    $event_id
                )
            );

            return new WP_REST_Response(
                [
                    'event_id' => $event_id,
                    'count'    => (int) $count,
                ],
                200
            );
        }

        // SECURITY: Validate table name and use prepared statement
        $safe_table = $wpdb->_escape( $table ); // phpcs:ignore WordPress.DB.RestrictedFunctions.restricted_db_escape
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT event_id, COUNT(*) as count FROM `{$safe_table}` GROUP BY event_id ORDER BY count DESC LIMIT %d",
                1000
            ),
            ARRAY_A
        );

        // SECURITY: Sanitize output
        $safe_results = [];
        foreach ($results as $row) {
            $safe_results[] = [
                'event_id' => absint($row['event_id']),
                'count'    => absint($row['count']),
            ];
        }

        return new WP_REST_Response($safe_results, 200);
    }

    /**
     * REST: Post like
     */
    public function rest_post_like($request)
    {
        global $wpdb;

        $params   = $request->get_json_params();
        $event_id = isset($params['event_id']) ? absint($params['event_id']) : 0;

        if (! $event_id) {
            return new WP_Error('missing_event_id', 'Event ID is required', [ 'status' => 400 ]);
        }

        $user_id = is_user_logged_in() ? get_current_user_id() : 0;
        $ip      = $this->get_client_ip();
        $ip_hash = $ip ? hash('sha256', $ip) : null;

        $table = $wpdb->prefix . 'apollo_likes';

        // Check if already liked
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE event_id = %d AND user_id = %d",
                $event_id,
                $user_id
            )
        );

        if ($exists) {
            // Unlike
            $wpdb->delete(
                $table,
                [
                    'event_id' => $event_id,
                    'user_id'  => $user_id,
                ],
                [ '%d', '%d' ]
            );

            return new WP_REST_Response(
                [
                    'success' => true,
                    'liked'   => false,
                ],
                200
            );
        }

        // Like
        $result = $wpdb->insert(
            $table,
            [
                'event_id' => $event_id,
                'user_id'  => $user_id,
                'ip_hash'  => $ip_hash,
            ],
            [ '%d', '%d', '%s' ]
        );

        if ($result) {
            return new WP_REST_Response(
                [
                    'success' => true,
                    'liked'   => true,
                ],
                201
            );
        }

        return new WP_Error('insert_failed', 'Failed to insert like', [ 'status' => 500 ]);
    }

    /**
     * REST: Get tech notes
     */
    public function rest_get_technotes($request)
    {
        global $wpdb;

        $venue_id = absint($request['venue_id']);
        $user_id  = get_current_user_id();

        if (! $user_id) {
            return new WP_Error('unauthorized', 'Authentication required', [ 'status' => 401 ]);
        }

        $table = $wpdb->prefix . 'apollo_venue_technotes';
        $notes = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE venue_id = %d AND user_id = %d",
                $venue_id,
                $user_id
            ),
            ARRAY_A
        );

        return new WP_REST_Response($notes ?: [], 200);
    }

    /**
     * REST: Post tech notes
     */
    public function rest_post_technotes($request)
    {
        global $wpdb;

        $venue_id = absint($request['venue_id']);
        $user_id  = get_current_user_id();

        if (! $user_id) {
            return new WP_Error('unauthorized', 'Authentication required', [ 'status' => 401 ]);
        }

        $params = $request->get_json_params();
        $notes  = isset($params['notes']) ? wp_kses_post($params['notes']) : '';

        $table = $wpdb->prefix . 'apollo_venue_technotes';

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE venue_id = %d AND user_id = %d",
                $venue_id,
                $user_id
            )
        );

        if ($exists) {
            $result = $wpdb->update(
                $table,
                [ 'notes' => $notes ],
                [
                    'venue_id' => $venue_id,
                    'user_id'  => $user_id,
                ],
                [ '%s' ],
                [ '%d', '%d' ]
            );
        } else {
            $result = $wpdb->insert(
                $table,
                [
                    'venue_id' => $venue_id,
                    'user_id'  => $user_id,
                    'notes'    => $notes,
                ],
                [ '%d', '%d', '%s' ]
            );
        }//end if

        if ($result !== false) {
            return new WP_REST_Response([ 'success' => true ], 200);
        }

        return new WP_Error('update_failed', 'Failed to save notes', [ 'status' => 500 ]);
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard()
    {
        if (! current_user_can('view_apollo_event_stats') && ! current_user_can('manage_options')) {
            wp_die(__('You do not have permission to view this page.', 'apollo-events-manager'));
        }

        ?>
		<div class="wrap apollo-dashboard-wrap">
			<h1><?php echo esc_html__('Apollo Events Dashboard', 'apollo-events-manager'); ?></h1>

			<div class="apollo-tabs">
				<button class="apollo-tab-btn active" data-tab="events"><?php esc_html_e('Events', 'apollo-events-manager'); ?></button>
				<button class="apollo-tab-btn" data-tab="analytics"><?php esc_html_e('Analytics', 'apollo-events-manager'); ?></button>
				<button class="apollo-tab-btn" data-tab="likes"><?php esc_html_e('Likes', 'apollo-events-manager'); ?></button>
				<button class="apollo-tab-btn" data-tab="technotes"><?php esc_html_e('Tech Notes', 'apollo-events-manager'); ?></button>
			</div>

			<div id="apollo-tab-events" class="apollo-tab active">
				<table id="apollo-events-table" class="display" style="width:100%">
					<thead>
						<tr>
							<th>ID</th>
							<th>Title</th>
							<th>Date</th>
							<th>Views</th>
							<th>Likes</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>

			<div id="apollo-tab-analytics" class="apollo-tab">
				<div class="apollo-charts-container">
					<canvas id="apollo-chart-views"></canvas>
					<canvas id="apollo-chart-countries"></canvas>
					<canvas id="apollo-chart-devices"></canvas>
				</div>
			</div>

			<div id="apollo-tab-likes" class="apollo-tab">
				<table id="apollo-likes-table" class="display" style="width:100%">
					<thead>
						<tr>
							<th>Event</th>
							<th>Likes</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>

			<div id="apollo-tab-technotes" class="apollo-tab">
				<p><?php esc_html_e('Tech notes management coming soon...', 'apollo-events-manager'); ?></p>
			</div>
		</div>
		<?php
    }

    /**
     * Helper: Get client IP
     */
    private function get_client_ip()
    {
        $ip_keys = [ 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ];
        foreach ($ip_keys as $key) {
            if (! empty($_SERVER[ $key ])) {
                return sanitize_text_field($_SERVER[ $key ]);
            }
        }

        return '';
    }

    /**
     * Helper: Detect country (simplified)
     */
    private function detect_country()
    {
        // Simplified - in production, use GeoIP service
        return 'BR';
        // Default to Brazil
    }

    /**
     * Helper: Detect device
     */
    private function detect_device()
    {
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
        if (preg_match('/mobile|android|iphone|ipad/', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }
}

// Initialize
Apollo_Admin_Dashboard::get_instance();
