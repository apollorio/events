<?php
// phpcs:ignoreFile
/**
 * Bookmarks System for Apollo Events Manager
 * Integrated from aprio-bookmarks functionality
 *
 * @package ApolloEventsManager
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Apollo Events Bookmarks Class
 * Handles user bookmarks/favorites for events
 */
class Apollo_Events_Bookmarks
{
    private static $instance = null;
    private $table_name;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'apollo_event_bookmarks';

        $this->init_hooks();
        $this->create_table();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        // AJAX handlers
        add_action('wp_ajax_apollo_toggle_bookmark', [ $this, 'ajax_toggle_bookmark' ]);
        add_action('wp_ajax_nopriv_apollo_toggle_bookmark', [ $this, 'ajax_toggle_bookmark' ]);

        // REST API endpoints
        add_action('rest_api_init', [ $this, 'register_rest_routes' ]);

        // Shortcode
        add_shortcode('apollo_bookmarks', [ $this, 'bookmarks_shortcode' ]);

        // Admin menu
        add_action('admin_menu', [ $this, 'add_admin_menu' ]);
    }

    /**
     * Create bookmarks table
     */
    private function create_table()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            event_id bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_event (user_id, event_id),
            KEY event_id (event_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Add bookmark
     */
    public function add_bookmark($user_id, $event_id)
    {
        global $wpdb;

        $user_id  = absint($user_id);
        $event_id = absint($event_id);

        if (! $user_id || ! $event_id) {
            return false;
        }

        // Verify event exists
        $event = get_post($event_id);
        if (! $event || $event->post_type !== 'event_listing') {
            return false;
        }

        // Check if already bookmarked
        if ($this->is_bookmarked($user_id, $event_id)) {
            return true;
            // Already bookmarked
        }

        $result = $wpdb->insert(
            $this->table_name,
            [
                'user_id'    => $user_id,
                'event_id'   => $event_id,
                'created_at' => current_time('mysql'),
            ],
            [ '%d', '%d', '%s' ]
        );

        if ($result) {
            // Update event meta count
            $this->update_bookmark_count($event_id);

            // Fire action
            do_action('apollo_event_bookmarked', $event_id, $user_id);

            return true;
        }

        return false;
    }

    /**
     * Remove bookmark
     */
    public function remove_bookmark($user_id, $event_id)
    {
        global $wpdb;

        $user_id  = absint($user_id);
        $event_id = absint($event_id);

        if (! $user_id || ! $event_id) {
            return false;
        }

        $result = $wpdb->delete(
            $this->table_name,
            [
                'user_id'  => $user_id,
                'event_id' => $event_id,
            ],
            [ '%d', '%d' ]
        );

        if ($result !== false) {
            // Update event meta count
            $this->update_bookmark_count($event_id);

            // Fire action
            do_action('apollo_event_unbookmarked', $event_id, $user_id);

            return true;
        }

        return false;
    }

    /**
     * Toggle bookmark
     */
    public function toggle_bookmark($user_id, $event_id)
    {
        if ($this->is_bookmarked($user_id, $event_id)) {
            return $this->remove_bookmark($user_id, $event_id) ? 'removed' : false;
        } else {
            return $this->add_bookmark($user_id, $event_id) ? 'added' : false;
        }
    }

    /**
     * Check if event is bookmarked by user
     */
    public function is_bookmarked($user_id, $event_id)
    {
        global $wpdb;

        $user_id  = absint($user_id);
        $event_id = absint($event_id);

        if (! $user_id || ! $event_id) {
            return false;
        }

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d AND event_id = %d",
                $user_id,
                $event_id
            )
        );

        return $count > 0;
    }

    /**
     * Get user bookmarks
     */
    public function get_user_bookmarks($user_id, $limit = 20, $offset = 0)
    {
        global $wpdb;

        $user_id = absint($user_id);
        $limit   = absint($limit);
        $offset  = absint($offset);

        $event_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT event_id FROM {$this->table_name} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $user_id,
                $limit,
                $offset
            )
        );

        if (empty($event_ids)) {
            return [];
        }

        $events = [];
        foreach ($event_ids as $event_id) {
            $event = get_post($event_id);
            if ($event && $event->post_status === 'publish') {
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * Get bookmark count for event
     */
    public function get_bookmark_count($event_id)
    {
        global $wpdb;

        $event_id = absint($event_id);

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE event_id = %d",
                $event_id
            )
        );

        return absint($count);
    }

    /**
     * Update bookmark count in post meta
     */
    private function update_bookmark_count($event_id)
    {
        $count = $this->get_bookmark_count($event_id);
        update_post_meta($event_id, '_apollo_bookmark_count', $count);
    }

    /**
     * AJAX: Toggle bookmark
     */
    public function ajax_toggle_bookmark()
    {
        // Verify nonce
        if (! isset($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'apollo_bookmark_nonce')) {
            wp_send_json_error([ 'message' => 'Nonce inválido' ]);

            return;
        }

        // Check if user is logged in
        if (! is_user_logged_in()) {
            wp_send_json_error([ 'message' => 'Você precisa estar logado para favoritar eventos' ]);

            return;
        }

        $user_id  = get_current_user_id();
        $event_id = isset($_POST['event_id']) ? absint(sanitize_text_field(wp_unslash($_POST['event_id']))) : 0;

        if (! $event_id) {
            wp_send_json_error([ 'message' => 'ID do evento inválido' ]);

            return;
        }

        // Verify event exists
        $event = get_post($event_id);
        if (! $event || $event->post_type !== 'event_listing') {
            wp_send_json_error([ 'message' => 'Evento não encontrado' ]);

            return;
        }

        $action = $this->toggle_bookmark($user_id, $event_id);

        if ($action) {
            wp_send_json_success(
                [
                    'action'        => $action,
                    'is_bookmarked' => ($action === 'added'),
                    'count'         => $this->get_bookmark_count($event_id),
                    'message'       => $action === 'added' ? 'Evento favoritado!' : 'Favorito removido!',
                ]
            );
        } else {
            wp_send_json_error([ 'message' => 'Erro ao atualizar favorito' ]);
        }
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes()
    {
        register_rest_route(
            'apollo/v1',
            'salvos',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'rest_get_bookmarks' ],
                'permission_callback' => [ $this, 'rest_check_permission' ],
            ]
        );

        register_rest_route(
            'apollo/v1',
            'salvos/(?P<id>\d+)',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'rest_toggle_bookmark' ],
                'permission_callback' => [ $this, 'rest_check_permission' ],
                'args'                => [
                    'id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ]
        );
    }

    /**
     * REST: Get user bookmarks
     */
    public function rest_get_bookmarks($request)
    {
        $user_id = get_current_user_id();
        $limit   = isset($request['limit']) ? absint($request['limit']) : 20;
        $offset  = isset($request['offset']) ? absint($request['offset']) : 0;

        // SECURITY: Ensure limit is reasonable to prevent DoS
        $limit = min($limit, 100);

        $bookmarks = $this->get_user_bookmarks($user_id, $limit, $offset);

        $formatted = [];
        foreach ($bookmarks as $event) {
            $formatted[] = [
                'id'            => absint($event->ID),
                'title'         => esc_html($event->post_title),
                'permalink'     => esc_url(get_permalink($event->ID)),
                'bookmarked_at' => sanitize_text_field(get_user_meta($user_id, '_apollo_bookmark_' . absint($event->ID) . '_date', true)),
            ];
        }

        return new WP_REST_Response(
            [
                'bookmarks' => $formatted,
                'total'     => count($formatted),
            ],
            200
        );
    }

    /**
     * REST: Toggle bookmark
     */
    public function rest_toggle_bookmark($request)
    {
        $user_id  = get_current_user_id();
        $event_id = absint($request['id']);

        $action = $this->toggle_bookmark($user_id, $event_id);

        if ($action) {
            return new WP_REST_Response(
                [
                    'action'        => $action,
                    'is_bookmarked' => ($action === 'added'),
                    'count'         => $this->get_bookmark_count($event_id),
                ],
                200
            );
        }

        return new WP_Error('toggle_failed', 'Erro ao atualizar favorito', [ 'status' => 500 ]);
    }

    /**
     * REST: Check permission
     */
    public function rest_check_permission()
    {
        return is_user_logged_in();
    }

    /**
     * Bookmarks shortcode
     */
    public function bookmarks_shortcode($atts)
    {
        if (! is_user_logged_in()) {
            return '<p>Você precisa estar logado para ver seus favoritos.</p>';
        }

        $atts = shortcode_atts(
            [
                'limit'      => 20,
                'show_count' => true,
            ],
            $atts
        );

        $user_id   = get_current_user_id();
        $bookmarks = $this->get_user_bookmarks($user_id, absint($atts['limit']));

        if (empty($bookmarks)) {
            return '<p>Você ainda não favoritou nenhum evento.</p>';
        }

        ob_start();
        ?>
		<div class="apollo-bookmarks-list">
			<?php foreach ($bookmarks as $event) : ?>
				<div class="apollo-bookmark-item">
					<h3><a href="<?php echo esc_url(get_permalink($event->ID)); ?>"><?php echo esc_html($event->post_title); ?></a></h3>
					<?php if ($atts['show_count']) : ?>
						<span class="bookmark-count"><?php echo esc_html($this->get_bookmark_count($event->ID)); ?> favoritos</span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
        return ob_get_clean();
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'edit.php?post_type=event_listing',
            __('Bookmarks', 'apollo-events-manager'),
            __('Bookmarks', 'apollo-events-manager'),
            'manage_options',
            'apollo-bookmarks',
            [ $this, 'admin_page' ]
        );
    }

    /**
     * Admin page
     */
    public function admin_page()
    {
        global $wpdb;

        // SECURITY: Using direct table reference is safe here as table name is hardcoded in constructor
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $total_bookmarks = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i", $this->table_name)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $total_users = $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT user_id) FROM %i", $this->table_name)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $total_events = $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT event_id) FROM %i", $this->table_name)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        ?>
		<div class="wrap">
			<h1><?php esc_html_e('Bookmarks Statistics', 'apollo-events-manager'); ?></h1>
			<div class="apollo-bookmarks-stats">
				<p><strong><?php esc_html_e('Total de Favoritos:', 'apollo-events-manager'); ?></strong> <?php echo esc_html($total_bookmarks); ?></p>
				<p><strong><?php esc_html_e('Usuários com Favoritos:', 'apollo-events-manager'); ?></strong> <?php echo esc_html($total_users); ?></p>
				<p><strong><?php esc_html_e('Eventos Favoritados:', 'apollo-events-manager'); ?></strong> <?php echo esc_html($total_events); ?></p>
			</div>
		</div>
		<?php
    }
}

// Initialize
Apollo_Events_Bookmarks::get_instance();
