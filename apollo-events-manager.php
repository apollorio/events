<?php
// phpcs:ignoreFile

/**
 * Plugin Name: Apollo Events Manager
 * Plugin URI: https://apollo.rio.br
 * Description: Modern event management with Motion.dev, ShadCN, Tailwind. Canvas mode, Statistics, Line Graphs.
 * Version: 1.0.0
 * Author: Apollo::Rio Team
 * Author URI: https://apollo.rio.br
 * License: GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: apollo-events-manager
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.3
 */

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

// Define constants
define('APOLLO_APRIO_VERSION', '1.0.0');
// APOLLO_AEM_VERSION removido - usar apenas APOLLO_APRIO_VERSION
define('APOLLO_APRIO_PATH', plugin_dir_path(__FILE__));
define('APOLLO_APRIO_URL', plugin_dir_url(__FILE__));

// Debug mode (enable in wp-config.php with: define('APOLLO_DEBUG', true);)
if (! defined('APOLLO_DEBUG')) {
    define('APOLLO_DEBUG', false);
}

// Portal debug mode (enabled when APOLLO_DEBUG is true)
if (! defined('APOLLO_PORTAL_DEBUG')) {
    define('APOLLO_PORTAL_DEBUG', (defined('WP_DEBUG') && WP_DEBUG && defined('APOLLO_DEBUG') && APOLLO_DEBUG));
}

/**
 * Fallback function for logging missing files
 * Safe fallback when Apollo Core is not active
 *
 * @param string $path Path to the missing file
 * @return void
 */
if (! function_exists('apollo_log_missing_file')) {
    function apollo_log_missing_file($path)
    {
        // Only log if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Apollo Events: missing include: ' . esc_html($path));
        }
        // Never throw fatal error - just log silently
    }
}

/**
 * Check if Apollo Core dependency is met
 *
 * @return bool True if Apollo Core is active and available
 */
function apollo_events_dependency_ok()
{
    // Check if function exists (WordPress loaded)
    if (function_exists('is_plugin_active')) {
        // Check if apollo-core is active
        if (! is_plugin_active('apollo-core/apollo-core.php')) {
            return false;
        }
    }

    // Check if Apollo Core is bootstrapped
    if (! class_exists('Apollo_Core') && ! defined('APOLLO_CORE_BOOTSTRAPPED')) {
        return false;
    }

    return true;
}

/**
 * Display admin notice when Apollo Core is missing
 */
function apollo_events_missing_core_notice()
{
    ?>
	<div class="notice notice-error is-dismissible">
		<p>
			<strong><?php esc_html_e('Apollo Events Manager', 'apollo-events-manager'); ?></strong>:
			<?php esc_html_e('O plugin "Apollo Core" não está ativo. Por favor, ative o plugin "apollo-core" para usar o Apollo Events Manager.', 'apollo-events-manager'); ?>
		</p>
	</div>
	<?php
}

// Early dependency check - show notice but don't block plugin loading
// This allows the plugin to activate even if Core loads after
if (! apollo_events_dependency_ok()) {
    add_action('admin_notices', 'apollo_events_missing_core_notice');
    // Continue loading - plugin will work in limited mode
    // Core can be activated later and plugin will function normally
}

if (! function_exists('apollo_eve_parse_start_date')) {
    /**
     * Helper function: Parse event start date.
     *
     * Accepts raw event start dates in "Y-m-d", "Y-m-d H:i:s" or any format supported by strtotime().
     *
     * @param mixed $raw Raw event start date value.
     * @return array{
     *     timestamp:int|null,
     *     day:string,
     *     month_pt:string,
     *     iso_date:string,
     *     iso_dt:string
     * }
     */
    function apollo_eve_parse_start_date($raw)
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return [
                'timestamp' => null,
                'day'       => '',
                'month_pt'  => '',
                'iso_date'  => '',
                'iso_dt'    => '',
            ];
        }

        $ts = strtotime($raw);

        if (! $ts) {
            $dt = DateTime::createFromFormat('Y-m-d', $raw);
            if ($dt instanceof DateTime) {
                $ts = $dt->getTimestamp();
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
}//end if

// apollo-events-manager.php (top-level helper) - DEFENSIVE VERSION
if (! function_exists('apollo_cfg')) {
    function apollo_cfg(): array
    {
        static $cfg = null;
        if ($cfg !== null) {
            return $cfg;
        }

        $path = plugin_dir_path(__FILE__) . 'includes/config.php';
        if (! file_exists($path)) {
            return [];
        }

        // Capture output buffer to prevent leaks
        ob_start();
        $loaded = include $path;
        $leaked = ob_get_clean();

        // Log if config leaked content (only in debug mode)
        if (! empty($leaked) && defined('WP_DEBUG') && WP_DEBUG && defined('APOLLO_DEBUG') && APOLLO_DEBUG) {
            error_log('Apollo Config leaked content: ' . $leaked);
        }

        $cfg = is_array($loaded) ? $loaded : [];

        return $cfg;
    }
}//end if

if (! function_exists('apollo_aem_bootstrap_versioning')) {
    function apollo_aem_bootstrap_versioning()
    {
        $stored_version = get_option('apollo_aprio_version');

        if ($stored_version !== APOLLO_APRIO_VERSION) {
            /**
             * Fires when the Apollo Events Manager version changes.
             *
             * @param string|null $stored_version Previously stored version (null on first run).
             * @param string      $target_version Target plugin version.
             */
            do_action('apollo_aprio_version_upgrade', $stored_version, APOLLO_APRIO_VERSION);

            update_option('apollo_aprio_version', APOLLO_APRIO_VERSION, false);
        }
    }

    add_action('plugins_loaded', 'apollo_aem_bootstrap_versioning', 5);
}

if (! function_exists('apollo_disable_legacy_event_saver')) {
    function apollo_disable_legacy_event_saver()
    {
        remove_action('event_manager_save_event_listing', 'save_custom_event_fields', 10);
    }

    add_action('init', 'apollo_disable_legacy_event_saver', 1);
}

if (! function_exists('apollo_sanitize_timetable')) {
    function apollo_sanitize_timetable($raw)
    {
        if (is_string($raw)) {
            $raw     = wp_unslash($raw);
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $raw = $decoded;
            }
        }

        if (is_array($raw)) {
            $raw = wp_unslash($raw);
        }

        $out = [];

        if (! is_array($raw)) {
            return $out;
        }

        foreach ($raw as $slot) {
            if (! is_array($slot)) {
                continue;
            }

            $dj = isset($slot['dj']) ? intval($slot['dj']) : 0;
            if (! $dj) {
                continue;
            }

            $from = isset($slot['from']) ? sanitize_text_field($slot['from']) : '';
            if ($from === '' && isset($slot['start'])) {
                $from = sanitize_text_field($slot['start']);
            }

            $to = isset($slot['to']) ? sanitize_text_field($slot['to']) : '';
            if ($to === '' && isset($slot['end'])) {
                $to = sanitize_text_field($slot['end']);
            }

            // ✅ ALWAYS include DJ in output, even without time
            // This ensures DJs are displayed even if times are not set
            $entry = [ 'dj' => $dj ];

            if ($from !== '') {
                $entry['from'] = $from;
            }
            if ($to !== '') {
                $entry['to'] = $to;
            }

            // Preserve custom order if set
            if (isset($slot['order']) && is_numeric($slot['order'])) {
                $entry['order'] = intval($slot['order']);
            }

            $out[] = $entry;
        }//end foreach

        if (! empty($out)) {
            usort(
                $out,
                static function ($a, $b) {
                    // ✅ PRIORITY 1: Use custom order if available
                    $a_order = isset($a['order']) && is_numeric($a['order']) ? intval($a['order']) : null;
                    $b_order = isset($b['order']) && is_numeric($b['order']) ? intval($b['order']) : null;

                    if ($a_order !== null && $b_order !== null) {
                        return $a_order <=> $b_order;
                    }
                    if ($a_order !== null) {
                        return -1;
                        // Items with order come first
                    }
                    if ($b_order !== null) {
                        return 1;
                    }

                    // ✅ PRIORITY 2: Sort by time if available (fallback)
                    $a_time = isset($a['from']) && $a['from'] !== '' ? $a['from'] : 'zzz';
                    $b_time = isset($b['from']) && $b['from'] !== '' ? $b['from'] : 'zzz';

                    if ($a_time !== 'zzz' && $b_time !== 'zzz') {
                        return strcmp($a_time, $b_time);
                    }

                    // If one has time and other doesn't, time comes first
                    if ($a_time !== 'zzz' && $b_time === 'zzz') {
                        return -1;
                    }
                    if ($a_time === 'zzz' && $b_time !== 'zzz') {
                        return 1;
                    }

                    // ✅ PRIORITY 3: Both without time/order, sort by DJ ID (last resort)
                    return ($a['dj'] ?? 0) <=> ($b['dj'] ?? 0);
                }
            );
        }//end if

        return $out;
    }
}//end if

// Migration helpers
$migrations_file = plugin_dir_path(__FILE__) . 'includes/migrations.php';
if (file_exists($migrations_file)) {
    require_once $migrations_file;
} elseif (defined('WP_DEBUG') && WP_DEBUG && defined('APOLLO_DEBUG') && APOLLO_DEBUG) {
    apollo_log_missing_file($migrations_file);
}

// Core helpers
$cache_file = plugin_dir_path(__FILE__) . 'includes/cache.php';
if (file_exists($cache_file)) {
    require_once $cache_file;
} elseif (defined('WP_DEBUG') && WP_DEBUG && defined('APOLLO_DEBUG') && APOLLO_DEBUG) {
    apollo_log_missing_file($cache_file);
}

$shortcodes_submit_file = plugin_dir_path(__FILE__) . 'includes/shortcodes-submit.php';
if (file_exists($shortcodes_submit_file)) {
    require_once $shortcodes_submit_file;
} elseif (defined('WP_DEBUG') && WP_DEBUG && defined('APOLLO_DEBUG') && APOLLO_DEBUG) {
    apollo_log_missing_file($shortcodes_submit_file);
}

$event_helpers_file = plugin_dir_path(__FILE__) . 'includes/event-helpers.php';
$geocoding_helper_file = plugin_dir_path(__FILE__) . 'includes/helpers/geocoding-helper.php';
if (file_exists($geocoding_helper_file)) {
	require_once $geocoding_helper_file;
}
if (file_exists($event_helpers_file)) {
    require_once $event_helpers_file;
} elseif (defined('WP_DEBUG') && WP_DEBUG && defined('APOLLO_DEBUG') && APOLLO_DEBUG) {
    apollo_log_missing_file($event_helpers_file);
}

$ajax_favorites_file = plugin_dir_path(__FILE__) . 'includes/ajax-favorites.php';
if (file_exists($ajax_favorites_file)) {
    require_once $ajax_favorites_file;
} elseif (defined('WP_DEBUG') && WP_DEBUG && defined('APOLLO_DEBUG') && APOLLO_DEBUG) {
    apollo_log_missing_file($ajax_favorites_file);
}

// Include REST API system (integrated from aprio-rest-api)
$rest_api_file = plugin_dir_path(__FILE__) . 'includes/class-rest-api.php';
if (file_exists($rest_api_file)) {
    require_once $rest_api_file;
} elseif (defined('WP_DEBUG') && WP_DEBUG && defined('APOLLO_DEBUG') && APOLLO_DEBUG) {
    apollo_log_missing_file($rest_api_file);
}

// Include AJAX handlers
$ajax_handlers_file = plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php';
if (file_exists($ajax_handlers_file)) {
    require_once $ajax_handlers_file;
} elseif (defined('WP_DEBUG') && WP_DEBUG && defined('APOLLO_DEBUG') && APOLLO_DEBUG) {
    apollo_log_missing_file($ajax_handlers_file);
}

// Load Motion.dev loader
$motion_loader_file = plugin_dir_path(__FILE__) . 'includes/motion-loader.php';
if (file_exists($motion_loader_file)) {
    require_once $motion_loader_file;
} elseif (defined('WP_DEBUG') && WP_DEBUG && defined('APOLLO_DEBUG') && APOLLO_DEBUG) {
    apollo_log_missing_file($motion_loader_file);
}

// Load Event Statistics class
$statistics_class_file = plugin_dir_path(__FILE__) . 'includes/class-event-statistics.php';
if (file_exists($statistics_class_file)) {
    require_once $statistics_class_file;
} elseif (defined('WP_DEBUG') && WP_DEBUG && defined('APOLLO_DEBUG') && APOLLO_DEBUG) {
    apollo_log_missing_file($statistics_class_file);
}

// Load AJAX Statistics handlers
$ajax_statistics_file = plugin_dir_path(__FILE__) . 'includes/ajax-statistics.php';
if (file_exists($ajax_statistics_file)) {
    require_once $ajax_statistics_file;
} elseif (defined('WP_DEBUG') && WP_DEBUG && defined('APOLLO_DEBUG') && APOLLO_DEBUG) {
    apollo_log_missing_file($ajax_statistics_file);
}

// TODO 130: Load Security Audit helper
$security_audit_file = plugin_dir_path(__FILE__) . 'includes/security-audit.php';
if (file_exists($security_audit_file)) {
    require_once $security_audit_file;
}

// TODO 131: Load Performance Optimizer helper
$performance_optimizer_file = plugin_dir_path(__FILE__) . 'includes/performance-optimizer.php';
if (file_exists($performance_optimizer_file)) {
    require_once $performance_optimizer_file;
}

// TODO 132: Load Accessibility Audit helper
$accessibility_audit_file = plugin_dir_path(__FILE__) . 'includes/accessibility-audit.php';
if (file_exists($accessibility_audit_file)) {
    require_once $accessibility_audit_file;
}

// TODO 133: Load API Documentation helper
$api_documentation_file = plugin_dir_path(__FILE__) . 'includes/api-documentation.php';
if (file_exists($api_documentation_file)) {
    require_once $api_documentation_file;
}

// TODO 135: Load Integration Tests helper
$integration_tests_file = plugin_dir_path(__FILE__) . 'includes/integration-tests.php';
if (file_exists($integration_tests_file)) {
    require_once $integration_tests_file;
}

// TODO 136: Load Performance Tests helper
$performance_tests_file = plugin_dir_path(__FILE__) . 'includes/performance-tests.php';
if (file_exists($performance_tests_file)) {
    require_once $performance_tests_file;
}

// TODO 137: Load Release Preparation helper
$release_preparation_file = plugin_dir_path(__FILE__) . 'includes/release-preparation.php';
if (file_exists($release_preparation_file)) {
    require_once $release_preparation_file;
}

// TODO 138: Load Backup & Migration helper
$backup_migration_file = plugin_dir_path(__FILE__) . 'includes/backup-migration.php';
if (file_exists($backup_migration_file)) {
    require_once $backup_migration_file;
}

// Load Admin Statistics Menu
$admin_statistics_menu_file = plugin_dir_path(__FILE__) . 'includes/admin-statistics-menu.php';
if (file_exists($admin_statistics_menu_file)) {
    require_once $admin_statistics_menu_file;
} else {
    apollo_log_missing_file($admin_statistics_menu_file);
}

// Load Admin Apollo Hub (Documentation & Settings)
$admin_hub_file = plugin_dir_path(__FILE__) . 'includes/admin-apollo-hub.php';
if (file_exists($admin_hub_file)) {
    require_once $admin_hub_file;
}

// Load Context Menu class
$context_menu_file = plugin_dir_path(__FILE__) . 'includes/class-context-menu.php';
if (file_exists($context_menu_file)) {
    require_once $context_menu_file;
} else {
    apollo_log_missing_file($context_menu_file);
}

// Load Tracking Footer
$tracking_footer_file = plugin_dir_path(__FILE__) . 'includes/tracking-footer.php';
if (file_exists($tracking_footer_file)) {
    require_once $tracking_footer_file;
} else {
    apollo_log_missing_file($tracking_footer_file);
}

// Load placeholder registry and access API
$placeholders_file = plugin_dir_path(__FILE__) . 'includes/class-apollo-events-placeholders.php';
if (file_exists($placeholders_file)) {
    require_once $placeholders_file;
} else {
    apollo_log_missing_file($placeholders_file);
}

// Load analytics and statistics
$analytics_file = plugin_dir_path(__FILE__) . 'includes/class-apollo-events-analytics.php';
if (file_exists($analytics_file)) {
    require_once $analytics_file;
} else {
    apollo_log_missing_file($analytics_file);
}

// Load organized shortcodes and widgets
$shortcodes_file = plugin_dir_path(__FILE__) . 'includes/shortcodes/class-apollo-events-shortcodes.php';
if (file_exists($shortcodes_file)) {
    require_once $shortcodes_file;
} else {
    apollo_log_missing_file($shortcodes_file);
}

$widgets_file = plugin_dir_path(__FILE__) . 'includes/widgets/class-apollo-events-widgets.php';
if (file_exists($widgets_file)) {
    require_once $widgets_file;
} else {
    apollo_log_missing_file($widgets_file);
}

// Load Save-Date cleaner
$save_date_cleaner_file = plugin_dir_path(__FILE__) . 'includes/save-date-cleaner.php';
if (file_exists($save_date_cleaner_file)) {
    require_once $save_date_cleaner_file;
} else {
    apollo_log_missing_file($save_date_cleaner_file);
}

// Load public event form
$public_event_form_file = plugin_dir_path(__FILE__) . 'includes/public-event-form.php';
if (file_exists($public_event_form_file)) {
    require_once $public_event_form_file;
} else {
    apollo_log_missing_file($public_event_form_file);
}

// Load role badges system
$role_badges_file = plugin_dir_path(__FILE__) . 'includes/role-badges.php';
if (file_exists($role_badges_file)) {
    require_once $role_badges_file;
} else {
    apollo_log_missing_file($role_badges_file);
}

// Load admin settings
$admin_settings_file = plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
if (file_exists($admin_settings_file)) {
    require_once $admin_settings_file;
} else {
    apollo_log_missing_file($admin_settings_file);
}

// Load sanitization system (STRICT MODE)
$sanitization_file = plugin_dir_path(__FILE__) . 'includes/sanitization.php';
if (file_exists($sanitization_file)) {
    require_once $sanitization_file;
} else {
    apollo_log_missing_file($sanitization_file);
}

// Load admin shortcodes page
$shortcodes_page_file = plugin_dir_path(__FILE__) . 'includes/admin-shortcodes-page.php';
if (file_exists($shortcodes_page_file)) {
    require_once $shortcodes_page_file;
} else {
    apollo_log_missing_file($shortcodes_page_file);
}

// Load admin meta keys page
$metakeys_page_file = plugin_dir_path(__FILE__) . 'includes/admin-metakeys-page.php';
if (file_exists($metakeys_page_file)) {
    require_once $metakeys_page_file;
} else {
    apollo_log_missing_file($metakeys_page_file);
}

// Load meta helpers (wrappers for sanitization)
$meta_helpers_file = plugin_dir_path(__FILE__) . 'includes/meta-helpers.php';
if (file_exists($meta_helpers_file)) {
    require_once $meta_helpers_file;
} else {
    apollo_log_missing_file($meta_helpers_file);
}

add_action(
    'init',
    static function () {
        // ✅ ONLY [events] shortcode - manter compat com [apollo_events] legado
        add_shortcode('events', 'apollo_events_shortcode_handler');

        // Legacy alias para evitar quebra em páginas antigas
        if (! shortcode_exists('apollo_events')) {
            add_shortcode('apollo_events', 'apollo_events_shortcode_handler');
        }

        if (shortcode_exists('apollo_eventos')) {
            remove_shortcode('apollo_eventos');
        }

        if (shortcode_exists('eventos')) {
            remove_shortcode('eventos');
        }

        if (shortcode_exists('apollo_register')) {
            remove_shortcode('apollo_register');
        }
    }
);

/**
 * Get interested user IDs for an event
 * Returns array of user IDs who have expressed interest in the event
 *
 * @param int $event_id The event post ID
 * @return array Array of user IDs
 */
function apollo_event_get_interested_user_ids($event_id)
{
    $event_id = absint($event_id);
    if (! $event_id) {
        return [];
    }

    // Get interested users from post meta
    $interested_users = get_post_meta($event_id, '_event_interested_users', true);

    if (! is_array($interested_users)) {
        $interested_users = [];
    }

    // Filter out invalid user IDs and ensure uniqueness
    $interested_users = array_filter(array_unique(array_map('absint', $interested_users)));
    $interested_users = array_values($interested_users); // Re-index array

    return $interested_users;
}

/**
 * Main Plugin Class
 */
class Apollo_Events_Manager_Plugin
{
    private $initialized = false;

    /**
     * Constructor - MANDATORY for all Apollo plugins
     * Automatically initializes the plugin
     */
    public function __construct()
    {
        // Prevent double initialization
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        // Register with Apollo Core Integration Bridge when ready
        add_action('apollo_core_ready', [ $this, 'on_core_ready' ]);

        // WordPress 6.7.0+ requires translations to load at 'init' or later
        add_action('init', [ $this, 'load_textdomain' ], 1);
        add_action('init', [ $this, 'ensure_events_page' ]);
        add_action('admin_init', [ $this, 'init_legacy_migration' ], 5);
        // Run migration early
        add_filter('template_include', [ $this, 'canvas_template' ], 99);
        add_action('wp_enqueue_scripts', [ $this, 'enqueue_assets' ]);

        // NOTE: Most shortcodes are registered by Apollo_Events_Shortcodes class
        // See: includes/shortcodes/class-apollo-events-shortcodes.php
        // Only plugin-specific shortcodes are registered here

        // Plugin-specific shortcodes (not in class)
        add_shortcode('apollo_event', [ $this, 'apollo_event_shortcode' ]);
        add_shortcode('apollo_event_user_overview', [ $this, 'apollo_event_user_overview_shortcode' ]);

        // New Tailwind-based shortcodes
        add_shortcode('apollo_dj_profile', [ $this, 'apollo_dj_profile_shortcode' ]);
        add_shortcode('apollo_user_dashboard', [ $this, 'apollo_user_dashboard_shortcode' ]);
        add_shortcode('apollo_cena_rio', [ $this, 'apollo_cena_rio_shortcode' ]);

        // FASE 2: Shortcode oficial de submissão de eventos
        add_shortcode('apollo_event_submit', [ $this, 'render_submit_form' ]);

        // Aliases para backward compatibility
        // NOTE: submit_event_form is registered by Apollo_Events_Shortcodes class
        // This is kept for backward compatibility only
        if (! shortcode_exists('submit_event_form')) {
            add_shortcode('submit_event_form', [ $this, 'render_submit_form' ]);
        }
        add_shortcode('apollo_eventos', [ $this, 'render_submit_form' ]);

        // AJAX handlers
        add_action('wp_ajax_filter_events', [ $this, 'ajax_filter_events' ]);
        add_action('wp_ajax_nopriv_filter_events', [ $this, 'ajax_filter_events' ]);

        // Profile update AJAX
        add_action('wp_ajax_apollo_save_profile', [ $this, 'ajax_save_profile' ]);
        add_action('wp_ajax_load_event_single', [ $this, 'ajax_load_event_single' ]);
        add_action('wp_ajax_nopriv_load_event_single', [ $this, 'ajax_load_event_single' ]);

        // Modal AJAX handler
        add_action('wp_ajax_apollo_get_event_modal', [ $this, 'ajax_get_event_modal' ]);
        add_action('wp_ajax_nopriv_apollo_get_event_modal', [ $this, 'ajax_get_event_modal' ]);

        // Clear portal cache when event is published/updated/deleted
        add_action('save_post_event_listing', [ $this, 'clear_portal_cache' ], 20);
        add_action('transition_post_status', [ $this, 'clear_portal_cache_on_status_change' ], 10, 3);
        add_action('trashed_post', [ $this, 'clear_portal_cache' ], 10);
        add_action('deleted_post', [ $this, 'clear_portal_cache' ], 10);

        // Moderation AJAX handlers
        add_action('wp_ajax_apollo_mod_approve_event', [ $this, 'ajax_mod_approve_event' ]);
        add_action('wp_ajax_apollo_mod_reject_event', [ $this, 'ajax_mod_reject_event' ]);

        // Force Brazil as default country
        add_filter('submit_event_form_fields', [ $this, 'force_brazil_country' ]);

        // Add custom fields to event submission form
        add_filter('submit_event_form_fields', [ $this, 'add_custom_event_fields' ]);

        // Validate custom fields
        add_filter('submit_event_form_validate_fields', [ $this, 'validate_custom_event_fields' ]);

        // Save custom fields (using native WordPress hook instead of APRIO hook)
        add_action('save_post_event_listing', [ $this, 'save_custom_event_fields' ], 10, 2);

        // Auto-geocoding for event_local posts
        add_action('save_post_event_local', [ $this, 'auto_geocode_local' ], 10, 2);

        // Load post types registration (INDEPENDENT)
        $post_types_file = APOLLO_APRIO_PATH . 'includes/post-types.php';
        if (file_exists($post_types_file)) {
            require_once $post_types_file;
        } else {
            apollo_log_missing_file($post_types_file);
        }

        // Load dashboards
        $dashboards_file = APOLLO_APRIO_PATH . 'includes/dashboards.php';
        if (file_exists($dashboards_file)) {
            require_once $dashboards_file;
        } else {
            apollo_log_missing_file($dashboards_file);
        }

        // Configure Co-Authors Plus support
        add_action('init', [ $this, 'configure_gestao_support' ], 20);

        // Migrate legacy meta keys (run once on admin_init)
        add_action('admin_init', [ $this, 'migrate_legacy_meta_keys' ], 5);

        // Load data migration utilities (for WP-CLI and maintenance)
        $data_migration_file = APOLLO_APRIO_PATH . 'includes/data-migration.php';
        if (file_exists($data_migration_file)) {
            require_once $data_migration_file;
        } else {
            apollo_log_missing_file($data_migration_file);
        }

        // FASE 4: Load event stats class
        $stats_file = APOLLO_APRIO_PATH . 'includes/class-event-stats.php';
        if (file_exists($stats_file)) {
            require_once $stats_file;
        }

        // FASE 4: Register view tracking hooks
        add_action('wp', [ $this, 'track_event_view' ]);

        // FASE 4: Register click_out AJAX handler
        if (class_exists('Apollo_Event_Stats')) {
            add_action('wp_ajax_apollo_record_click_out', [ 'Apollo_Event_Stats', 'ajax_record_click_out' ]);
            add_action('wp_ajax_nopriv_apollo_record_click_out', [ 'Apollo_Event_Stats', 'ajax_record_click_out' ]);
        }

        // Register event comment (Registros) AJAX handler
        add_action('wp_ajax_apollo_submit_event_comment', [ $this, 'ajax_submit_event_comment' ]);

        // Load admin metaboxes
        if (is_admin()) {
            $admin_file = APOLLO_APRIO_PATH . 'includes/admin-metaboxes.php';
            if (file_exists($admin_file)) {
                require_once $admin_file;
            }

            // Load migration validator
            // Legacy micromodal scripts are intentionally skipped for the Step 2 release.
        }

        // Load authentication shortcodes
        $auth_shortcodes_file = APOLLO_APRIO_PATH . 'includes/shortcodes-auth.php';
        if (file_exists($auth_shortcodes_file)) {
            require_once $auth_shortcodes_file;
        }

        // Load My Apollo dashboard shortcode
        $my_apollo_file = APOLLO_APRIO_PATH . 'includes/shortcodes-my-apollo.php';
        if (file_exists($my_apollo_file)) {
            require_once $my_apollo_file;
        }

        // Backward compatibility layer (prevents fatal errors if APRIO reactivated)
        add_filter('event_manager_event_listing_templates', [ $this, 'aprio_compatibility_notice' ], 1);
        add_filter('event_manager_single_event_templates', [ $this, 'aprio_compatibility_notice' ], 1);

        // Admin notices
        add_action('admin_notices', [ $this, 'admin_notices' ]);

        // Admin menu for placeholders documentation and analytics
        // DISABLED: Consolidated into admin-apollo-hub.php to avoid duplicate menus
        // add_action('admin_menu', [ $this, 'add_admin_menu' ]);

        // Track event views when modal is opened
        add_action('wp_ajax_apollo_get_event_modal', [ $this, 'track_event_view_on_modal' ], 5);
        add_action('wp_ajax_nopriv_apollo_get_event_modal', [ $this, 'track_event_view_on_modal' ], 5);

        // Debug footer
        if (APOLLO_DEBUG) {
            add_action('wp_footer', [ $this, 'debug_footer' ]);
        }

        // Content injector for single events
        add_filter('the_content', [ $this, 'inject_event_content' ], 10);
    }

    /**
     * APRIO Compatibility Notice
     * Prevents fatal errors if APRIO is accidentally reactivated
     */
    public function aprio_compatibility_notice($templates)
    {
        // Apollo Events Manager is now standalone - no WP Event Manager dependency
        return $templates;
    }

    /**
     * Admin Notices
     */
    public function admin_notices()
    {
        // Notice if APRIO is still active
        if (class_exists('WP_Event_Manager')) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>⚠️ Apollo Events Manager</strong> is now independent and no longer requires WP Event Manager.</p>';
            echo '<p>You can safely <a href="' . admin_url('plugins.php') . '">deactivate WP Event Manager</a> to improve performance.</p>';
            echo '</div>';
        }

        // Notice if CPTs not registered
        if (! post_type_exists('event_listing')) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>❌ Apollo Events Manager:</strong> CPTs not registered. Please deactivate and reactivate the plugin.</p>';
            echo '</div>';
        }
    }

    /**
     * Debug Footer
     * Shows debug info in HTML comments (admin only)
     */
    public function debug_footer()
    {
        if (! current_user_can('administrator')) {
            return;
        }

        echo '<!-- Apollo Debug Info -->' . "\n";
        // Removed WP_Event_Manager dependency - Apollo Events Manager is standalone
        echo '<!-- Apollo Events Manager: Standalone Mode ✅ -->' . "\n";
        echo '<!-- CPTs Registered: ' . (post_type_exists('event_listing') ? 'YES ✅' : 'NO ❌') . ' -->' . "\n";
        echo '<!-- event_listing: ' . (post_type_exists('event_listing') ? 'YES' : 'NO') . ' -->' . "\n";
        echo '<!-- event_dj: ' . (post_type_exists('event_dj') ? 'YES' : 'NO') . ' -->' . "\n";
        echo '<!-- event_local: ' . (post_type_exists('event_local') ? 'YES' : 'NO') . ' -->' . "\n";
        echo '<!-- Taxonomies: event_listing_category=' . (taxonomy_exists('event_listing_category') ? 'YES' : 'NO');
        echo ', event_sounds=' . (taxonomy_exists('event_sounds') ? 'YES' : 'NO') . ' -->' . "\n";
        echo '<!-- Total Events: ' . wp_count_posts('event_listing')->publish . ' -->' . "\n";
        echo '<!-- Total DJs: ' . wp_count_posts('event_dj')->publish . ' -->' . "\n";
        echo '<!-- Total Locals: ' . wp_count_posts('event_local')->publish . ' -->' . "\n";
        echo '<!-- /Apollo Debug Info -->' . "\n";
    }

    /**
     * Auto-geocode Local posts when saved
     * Uses OpenStreetMap Nominatim API to get coordinates from address
     */
    public function auto_geocode_local($post_id, $post)
    {
        // Skip autosave/revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Throttle requests to Nominatim (max 1 req/sec shared across site)
        $throttle_key = 'apollo_geocode_last_request';
        $last_request = get_transient($throttle_key);
        if ($last_request) {
            $elapsed = microtime(true) - (float) $last_request;
            if ($elapsed < 1) {
                usleep((int) ((1 - $elapsed) * 1000000));
            }
        }

        set_transient($throttle_key, microtime(true), 1);

        // Get address and city
        $addr = apollo_get_post_meta($post_id, '_local_address', true);
        $city = apollo_get_post_meta($post_id, '_local_city', true);

        // Need at least city
        if (empty($city)) {
            return;
        }

        // Check if already has coordinates
        $lat = apollo_get_post_meta($post_id, '_local_latitude', true);
        $lng = apollo_get_post_meta($post_id, '_local_longitude', true);
        if (! empty($lat) && ! empty($lng)) {
            return;
            // Already has coords
        }

        // Build search query
        $query_parts = array_filter([ $addr, $city, 'Brasil' ]);
        $query       = urlencode(implode(', ', $query_parts));
        $url         = "https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=BR&q={$query}";

        // Call Nominatim API
        $res = wp_remote_get(
            $url,
            [
                'timeout'    => 10,
                'user-agent' => 'Apollo::Rio/2.0 (WordPress Event Manager)',
            ]
        );

        if (is_wp_error($res)) {
            error_log("❌ Geocoding failed for local {$post_id}: " . $res->get_error_message());

            return;
        }

        $data = json_decode(wp_remote_retrieve_body($res), true);

        if (! empty($data[0]['lat']) && ! empty($data[0]['lon'])) {
            apollo_update_post_meta($post_id, '_local_latitude', $data[0]['lat']);
            apollo_update_post_meta($post_id, '_local_longitude', $data[0]['lon']);
            error_log("✅ Auto-geocoded local {$post_id}: {$data[0]['lat']}, {$data[0]['lon']}");
        } else {
            error_log("⚠️ No coordinates found for local {$post_id}: {$query}");
        }
    }

    /**
     * Called when Apollo Core Integration Bridge is ready
     * Sets up cross-plugin integrations
     */
    public function on_core_ready()
    {
        /**
         * Apollo Events Manager is now connected to Core ecosystem
         * Core provides: shared utilities, template system, canvas mode, notifications
         *
         * Use Core utilities:
         * - apollo_get_asset_url() for unified assets
         * - apollo_get_template() for template hierarchy
         * - apollo_add_notification() for user notifications
         * - apollo_is_canvas_mode() for canvas detection
         */
        do_action('apollo_events_connected');

        // Log connection in debug mode
        if (function_exists('apollo_is_debug_mode') && apollo_is_debug_mode()) {
            error_log('Apollo Events Manager connected to Core ecosystem');
        }
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain()
    {
        load_plugin_textdomain('apollo-events-manager', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * Ensure the Eventos page exists (self-healing)
     * ✅ MODIFIED: Now optional - only creates if option is enabled
     * Uses helper function to check trash status - does NOT create if in trash
     */
    public function ensure_events_page()
    {
        if (is_admin()) {
            return;
        }

        // ✅ Check if auto-create is enabled (default: false for strict mode)
        $auto_create = get_option('apollo_events_auto_create_eventos_page', false);

        if (! $auto_create) {
            // Auto-create disabled - user must create manually via Shortcodes page
            return;
        }

        $events_page = apollo_em_get_events_page();

        // Only create if page doesn't exist at all (not in trash)
        // Restoration from trash should only happen on activation
        if (! $events_page) {
            $page_id = wp_insert_post(
                [
                    'post_title'   => 'Eventos',
                    'post_name'    => 'eventos',
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_content' => '[events]',
                // ✅ Use [events] shortcode
                ]
            );
            if ($page_id && ! is_wp_error($page_id)) {
                // Set canvas template if available
                if (defined('APOLLO_PATH')) {
                    apollo_update_post_meta($page_id, '_wp_page_template', 'pagx_appclean');
                }
                flush_rewrite_rules(false);
                error_log('✅ Apollo: Auto-created /eventos/ page (ID: ' . $page_id . ')');
            }
        }
    }

    /**
     * Force Apollo templates for events - STRICT MODE
     * Plugin templates ALWAYS override theme, regardless of active theme
     *
     * This ensures visual consistency matching CodePens:
     * - /eventos/ → templates/portal-discover.php (raxqVGR)
     * - /evento/{slug} → templates/single-event-standalone.php (JoGvgaY)
     */
    public function canvas_template($template)
    {
        // Don't override in admin
        if (is_admin()) {
            return $template;
        }

        // FORCE EVENT DASHBOARD TEMPLATE
        // Intercept page with slug 'event-dashboard'
        if (is_page('event-dashboard')) {
            $plugin_template = APOLLO_APRIO_PATH . 'templates/page-event-dashboard.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        // FORCE SINGLE DJ TEMPLATE
        // Any single event_dj MUST use our DJ template
        if (is_singular('event_dj')) {
            $plugin_template = APOLLO_APRIO_PATH . 'templates/single-event_dj.php';
            if (file_exists($plugin_template)) {
                error_log('🎯 Apollo: Forcing single-event_dj.php for DJ: ' . get_the_ID());

                return $plugin_template;
            }
        }

        // FORCE SINGLE LOCAL TEMPLATE
        // Any single event_local MUST use our Local template
        if (is_singular('event_local')) {
            $plugin_template = APOLLO_APRIO_PATH . 'templates/single-event_local.php';
            if (file_exists($plugin_template)) {
                error_log('🎯 Apollo: Forcing single-event_local.php for Local: ' . get_the_ID());

                return $plugin_template;
            }
        }

        // FORCE SINGLE EVENT TEMPLATE
        // Any single event_listing MUST use our custom Apollo template
        if (is_singular('event_listing')) {
            $plugin_template = APOLLO_APRIO_PATH . 'templates/single-event_listing.php';
            if (file_exists($plugin_template)) {
                // Log for debugging
                error_log('🎯 Apollo: Forcing single-event_listing.php for event: ' . get_the_ID());

                return $plugin_template;
            }
        }

        // FORCE ARCHIVE/LIST TEMPLATE
        // /eventos/ page OR event_listing archive MUST use portal-discover
        if (is_page('eventos') || is_post_type_archive('event_listing')) {
            $plugin_template = APOLLO_APRIO_PATH . 'templates/portal-discover.php';
            if (file_exists($plugin_template)) {
                // Log for debugging
                error_log('🎯 Apollo: Forcing portal-discover.php for /eventos/');

                return $plugin_template;
            }
        }

        // FORCE CENARIO NEW EVENT TEMPLATE
        // Page with slug 'cenario-new-event' uses custom template
        if (is_page('cenario-new-event')) {
            $plugin_template = APOLLO_APRIO_PATH . 'templates/page-cenario-new-event.php';
            if (file_exists($plugin_template)) {
                error_log('🎯 Apollo: Forcing page-cenario-new-event.php');

                return $plugin_template;
            }
        }

        // FORCE MOD EVENTS TEMPLATE
        // Page with slug 'mod-events' uses mod template
        if (is_page('mod-events')) {
            $plugin_template = APOLLO_APRIO_PATH . 'templates/page-mod-events.php';
            if (file_exists($plugin_template)) {
                error_log('🎯 Apollo: Forcing page-mod-events.php');

                return $plugin_template;
            }
        }

        return $template;
    }

    /**
     * Enqueue plugin assets
     * FORCE LOAD from assets.apollo.rio.br
     *
     * CRITICAL: uni.css is UNIVERSAL and MAIN CSS
     * It MUST load on ALL pages and OVERRIDE all other CSS
     * It will be enqueued LAST via force_uni_css_last() hook
     */
    public function enqueue_assets()
    {
        // ============================================
        // CRITICAL: uni.css MUST be registered FIRST
        // UNIVERSAL CSS - loads on ALL pages, not just event pages
        // It will be enqueued LAST via force_uni_css_last() hook
        // This ensures it OVERRIDES all other CSS (Tailwind, ShadCN, etc.)
        // ============================================
        if (! wp_style_is('apollo-uni-css', 'registered') && ! wp_style_is('apollo-uni-css', 'enqueued')) {
            wp_register_style(
                'apollo-uni-css',
                'https://assets.apollo.rio.br/uni.css',
                [],
                // No dependencies - UNIVERSAL CSS
                '2.0.0',
                'all'
            );
            // Enqueue with highest priority hook in wp_head (CSS must be in <head>)
            // This ensures uni.css loads LAST and OVERRIDES everything
            add_action('wp_head', [ $this, 'force_uni_css_last' ], 999999);
        }

        // Enqueue DJ template CSS if on DJ single page
        if (is_singular('event_dj')) {
            wp_enqueue_style(
                'apollo-dj-template',
                APOLLO_APRIO_URL . 'assets/dj-template.css',
                [],
                APOLLO_APRIO_VERSION
            );
        }

        // ✅ SEMPRE CARREGAR RemixIcon quando eventos são exibidos
        global $post;
        $is_event_page = false;

        // Verificar tipos de página de eventos
        if (is_singular('event_listing') || is_post_type_archive('event_listing') || is_page('eventos')) {
            $is_event_page = true;
        }

        // Verificar shortcodes no conteúdo
        if (! $is_event_page && isset($post) && ! empty($post->post_content)) {
            if (
                has_shortcode($post->post_content, 'events') || has_shortcode($post->post_content, 'apollo_events') || has_shortcode($post->post_content, 'eventos-page')
            ) {
                $is_event_page = true;
            }
        }

        if ($is_event_page) {

            // FORCE LOAD: Loading Animation JS
            wp_enqueue_script(
                'apollo-loading-animation',
                APOLLO_APRIO_URL . 'assets/js/apollo-loading-animation.js',
                [],
                APOLLO_APRIO_VERSION,
                true
            );

            // FORCE LOAD: Loading Animation CSS (inline)
            // NOTE: Only plugin-specific animations/functions, NOT universal styles
            // uni.css handles ALL universal styles (.event_listing, .mobile-container, etc.)
            $loading_css = '
            /* Plugin-specific: Rocket Favorite Button (NOT in uni.css) */
            .event-favorite-rocket {
                position: absolute;
                top: 10px;
                right: 10px;
                z-index: 100;
                background: rgba(255, 255, 255, 0.95);
                border: none;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            }
            .event-favorite-rocket:hover {
                transform: scale(1.1);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            }
            .event-favorite-rocket .rocket-icon {
                font-size: 20px;
                color: #FF6B6B;
                transition: all 0.3s ease;
            }
            .event-favorite-rocket[data-favorited="1"] .rocket-icon {
                color: #FF3838;
                animation: rocketPulse 0.5s ease;
            }
            @keyframes rocketPulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.2); }
            }

            /* Plugin-specific: Loading Animation Container (NOT in uni.css) */
            .apollo-loader-container {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                z-index: 999999;
                opacity: 1;
                transition: opacity 0.3s ease;
            }
            .apollo-loader-container.fade-out {
                opacity: 0;
            }
            .apollo-loader {
                position: relative;
                width: 80px;
                height: 80px;
            }
            .apollo-loader-ring {
                position: absolute;
                border: 4px solid transparent;
                border-top-color: #FF6B6B;
                border-radius: 50%;
                animation: apolloSpin 1.5s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
            }
            .apollo-loader-ring:nth-child(1) {
                width: 80px;
                height: 80px;
                animation-delay: 0s;
            }
            .apollo-loader-ring:nth-child(2) {
                width: 60px;
                height: 60px;
                top: 10px;
                left: 10px;
                border-top-color: #4ECDC4;
                animation-delay: 0.2s;
            }
            .apollo-loader-ring:nth-child(3) {
                width: 40px;
                height: 40px;
                top: 20px;
                left: 20px;
                border-top-color: #FFE66D;
                animation-delay: 0.4s;
            }
            .apollo-loader-pulse {
                position: absolute;
                top: 50%;
                left: 50%;
                width: 20px;
                height: 20px;
                margin: -10px 0 0 -10px;
                background: linear-gradient(135deg, #FF6B6B, #4ECDC4);
                border-radius: 50%;
                animation: apolloPulse 1.5s ease-in-out infinite;
            }
            @keyframes apolloSpin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            @keyframes apolloPulse {
                0%, 100% { transform: scale(1); opacity: 1; }
                50% { transform: scale(1.5); opacity: 0.5; }
            }
            .apollo-loader-text {
                color: white;
                margin-top: 20px;
                font-size: 16px;
                font-weight: 500;
                letter-spacing: 1px;
            }

            /* Plugin-specific: Image Loading States (NOT in uni.css) */
            .picture.apollo-image-loading {
                position: relative;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 200px;
            }
            .picture.apollo-image-loading::after {
                content: "";
                position: absolute;
                top: 50%;
                left: 50%;
                width: 40px;
                height: 40px;
                margin: -20px 0 0 -20px;
                border: 3px solid rgba(255, 255, 255, 0.3);
                border-top-color: white;
                border-radius: 50%;
                animation: apolloSpin 0.8s linear infinite;
            }
            .picture.apollo-image-loaded img {
                animation: apolloFadeIn 0.5s ease;
            }
            @keyframes apolloFadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            ';
            // Add inline styles AFTER uni.css is enqueued (via head hook)
            // Only plugin-specific animations/functions, NOT universal styles
            add_action(
                'wp_head',
                function () use ($loading_css) {
                    if (wp_style_is('apollo-uni-css', 'enqueued')) {
                        wp_add_inline_style('apollo-uni-css', $loading_css);
                    }
                },
                999998
            );

            // FORCE LOAD: RemixIcon (before uni.css loads)
            if (! wp_style_is('remixicon', 'enqueued')) {
                wp_enqueue_style(
                    'remixicon',
                    'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
                    [],
                    // No dependency on uni.css (loads before it)
                    '4.7.0',
                    'all'
                );
            }
        }//end if

        // Only enqueue other assets on event pages or shortcode pages
        if (! $this->should_enqueue_assets()) {
            return;
        }

        // Carregar sistema centralizado ShadCN/Tailwind (se apollo-social estiver ativo)
        if (function_exists('apollo_shadcn_init')) {
            apollo_shadcn_init();
        } elseif (defined('APOLLO_SOCIAL_PLUGIN_DIR')) {
            // Fallback: carregar loader diretamente se apollo-social não estiver ativo mas arquivo existe
            $shadcn_loader = APOLLO_SOCIAL_PLUGIN_DIR . 'includes/apollo-shadcn-loader.php';
            if (file_exists($shadcn_loader)) {
                require_once $shadcn_loader;
                if (class_exists('Apollo_ShadCN_Loader')) {
                    Apollo_ShadCN_Loader::get_instance();
                }
            }
        } elseif (class_exists('Apollo_ShadCN_Loader')) {
            // Se classe já existe (carregada por outro plugin), usar diretamente
            Apollo_ShadCN_Loader::get_instance();
        }

        // Get config to determine page type
        $config          = apollo_cfg();
        $event_post_type = isset($config['cpt']['event']) ? $config['cpt']['event'] : 'event_listing';
        $is_single_event = is_singular($event_post_type);

        // ============================================
        // FORCE LOAD: RemixIcon (BEFORE uni.css)
        // ============================================
        if (! wp_style_is('remixicon', 'enqueued')) {
            wp_enqueue_style(
                'remixicon',
                'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
                [],
                // No dependency on uni.css (loads before it)
                '4.7.0',
                'all'
            );
        }

        // ============================================
        // FORCE LOAD: Apollo ShadCN Components + modal shell
        // NOTE: These load BEFORE uni.css
        // uni.css will OVERRIDE all universal styles (.event_listing, .mobile-container, etc.)
        // These CSS files should ONLY contain plugin-specific functionality, NOT universal styles
        // ============================================
        wp_enqueue_style(
            'apollo-shadcn-components',
            APOLLO_APRIO_URL . 'assets/css/apollo-shadcn-components.css',
            [ 'remixicon' ],
            // Removed uni.css dependency (loads before it)
            APOLLO_APRIO_VERSION,
            'all'
        );

        wp_enqueue_style(
            'apollo-event-modal-css',
            APOLLO_APRIO_URL . 'assets/css/event-modal.css',
            [ 'apollo-shadcn-components' ],
            // Loads before uni.css
            APOLLO_APRIO_VERSION,
            'all'
        );

        // Legacy micromodal scripts intentionally skipped for Step 2 release.

        // ============================================
        // base.js is now enqueued globally by apollo-core on ALL pages
        // Removed conditional enqueue - base.js loads everywhere for global behaviors
        // ============================================

        // ✅ CRITICAL: Force load Leaflet.js for OSM maps (ALWAYS - for modals and single pages)
        // Load on all pages that might show events (including modal)
        // CRITICAL: Load in footer but ensure it's available before inline scripts
        wp_enqueue_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            '1.9.4',
            false
            // CRITICAL: Load in header to ensure it's available before inline scripts
        );
        wp_enqueue_style(
            'leaflet-css',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            '1.9.4'
        );

        // Portal modal handler (local JS) - ALWAYS ENQUEUE FOR PORTAL
        wp_enqueue_script(
            'apollo-events-portal',
            APOLLO_APRIO_URL . 'assets/js/apollo-events-portal.js',
            [ 'jquery' ],
            APOLLO_APRIO_VERSION,
            true
        );

        // Map settings (OSM)
        $osm_default_zoom = (int) get_option('event_manager_osm_default_zoom', 14);
        if ($osm_default_zoom < 8 || $osm_default_zoom > 24) {
            $osm_default_zoom = 14;
        }
        $osm_tile_style     = get_option('event_manager_osm_tile_style', 'default');
        $osm_allowed_styles = [ 'default', 'light', 'dark' ];
        if (! in_array($osm_tile_style, $osm_allowed_styles, true)) {
            $osm_tile_style = 'default';
        }

        // Localize script for AJAX (MUST be after enqueue)
        wp_localize_script(
            'apollo-events-portal',
            'apolloPortalAjax',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('apollo_events_nonce'),
            ]
        );

        // Localize OSM defaults for maps
        wp_localize_script(
            'apollo-events-portal',
            'apolloOSM',
            [
                'defaultZoom' => $osm_default_zoom,
                'tileStyle'   => $osm_tile_style,
            ]
        );

        // Localize debug flag
        wp_localize_script('apollo-events-portal', 'apolloPortalDebug', [
            'enabled' => (defined('APOLLO_PORTAL_DEBUG') && APOLLO_PORTAL_DEBUG) ? true : false
        ]);

        // Motion.dev animations for event cards
        wp_enqueue_script(
            'apollo-motion-event-card',
            APOLLO_APRIO_URL . 'assets/js/motion-event-card.js',
            [ 'apollo-events-portal' ],
            // Depends on portal script
            APOLLO_APRIO_VERSION,
            true
        );

        // Motion.dev modal animations
        wp_enqueue_script(
            'apollo-motion-modal',
            APOLLO_APRIO_URL . 'assets/js/motion-modal.js',
            [ 'apollo-events-portal' ],
            APOLLO_APRIO_VERSION,
            true
        );

        // Infinite scroll for list view
        wp_enqueue_script(
            'apollo-infinite-scroll',
            APOLLO_APRIO_URL . 'assets/js/infinite-scroll.js',
            [ 'apollo-events-portal' ],
            APOLLO_APRIO_VERSION,
            true
        );

        // Infinite scroll CSS (BEFORE uni.css)
        // NOTE: Should ONLY contain .event-list-item styles (list view template)
        // uni.css handles ALL universal styles (.event_listings, .event_listing, etc.)
        wp_enqueue_style(
            'apollo-infinite-scroll-css',
            APOLLO_APRIO_URL . 'assets/css/infinite-scroll.css',
            [ 'apollo-shadcn-components' ],
            // Removed uni.css dependency
            APOLLO_APRIO_VERSION,
            'all'
        );

        // Motion.dev dashboard tabs
        wp_enqueue_script(
            'apollo-motion-dashboard',
            APOLLO_APRIO_URL . 'assets/js/motion-dashboard.js',
            [ 'jquery' ],
            APOLLO_APRIO_VERSION,
            true
        );

        // Line graph for statistics (TODO 98)
        wp_enqueue_script(
            'apollo-chart-line-graph',
            APOLLO_APRIO_URL . 'assets/js/chart-line-graph.js',
            [],
            APOLLO_APRIO_VERSION,
            true
        );

        // Context menu
        wp_enqueue_script(
            'apollo-motion-context-menu',
            APOLLO_APRIO_URL . 'assets/js/motion-context-menu.js',
            [ 'jquery' ],
            APOLLO_APRIO_VERSION,
            true
        );

        // Character counter
        wp_enqueue_script(
            'apollo-character-counter',
            APOLLO_APRIO_URL . 'assets/js/character-counter.js',
            [ 'jquery' ],
            APOLLO_APRIO_VERSION,
            true
        );

        // Form validation
        wp_enqueue_script(
            'apollo-form-validation',
            APOLLO_APRIO_URL . 'assets/js/form-validation.js',
            [ 'jquery' ],
            APOLLO_APRIO_VERSION,
            true
        );

        // Image modal (fullscreen with zoom/pan)
        wp_enqueue_script(
            'apollo-image-modal',
            APOLLO_APRIO_URL . 'assets/js/image-modal.js',
            [ 'jquery' ],
            APOLLO_APRIO_VERSION,
            true
        );

        // Gallery animations (for single event pages)
        if ($is_single_event) {
            wp_enqueue_script(
                'apollo-motion-gallery',
                APOLLO_APRIO_URL . 'assets/js/motion-gallery.js',
                [ 'jquery' ],
                APOLLO_APRIO_VERSION,
                true
            );

            wp_enqueue_script(
                'apollo-motion-local-page',
                APOLLO_APRIO_URL . 'assets/js/motion-local-page.js',
                [ 'jquery' ],
                APOLLO_APRIO_VERSION,
                true
            );
        }

        // P0-6: Unified favorites system (REST API) - for portal pages
        if (! $is_single_event) {
            wp_enqueue_style(
                'apollo-event-favorites',
                APOLLO_APRIO_URL . 'assets/css/apollo-event-favorites.css',
                [],
                APOLLO_APRIO_VERSION
            );
            wp_enqueue_script(
                'apollo-event-favorites',
                APOLLO_APRIO_URL . 'assets/js/apollo-event-favorites.js',
                [ 'apollo-events-portal' ],
                APOLLO_APRIO_VERSION,
                true
            );
            wp_localize_script(
                'apollo-event-favorites',
                'apolloEventsData',
                [
                    'restUrl'       => rest_url('apollo/v1'),
                    'nonce'         => wp_create_nonce('wp_rest'),
                    'currentUserId' => get_current_user_id(),
                ]
            );

            // Legacy favorites script (keep for backward compatibility)
            wp_enqueue_script(
                'apollo-events-favorites',
                APOLLO_APRIO_URL . 'assets/js/apollo-favorites.js',
                [ 'apollo-events-portal' ],
                APOLLO_APRIO_VERSION,
                true
            );

            // Localize for AJAX (shared between base.js and portal.js)
            wp_localize_script(
                'apollo-events-portal',
                'apollo_events_ajax',
                [
                    'url'      => admin_url('admin-ajax.php'),
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce'    => wp_create_nonce('apollo_events_nonce'),
                ]
            );
        }//end if

        // ============================================
        // CONDITIONAL: event-page.js (single event + lightbox)
        // ============================================
        if ($is_single_event) {
            wp_enqueue_script(
                'apollo-event-page-js',
                'https://assets.apollo.rio.br/event-page.js',
                [ 'jquery' ],
                '2.0.0',
                true
            );

            // P0-6: Unified favorites system (REST API) - for single event pages
            wp_enqueue_style(
                'apollo-event-favorites',
                APOLLO_APRIO_URL . 'assets/css/apollo-event-favorites.css',
                [],
                APOLLO_APRIO_VERSION
            );
            wp_enqueue_script(
                'apollo-event-favorites',
                APOLLO_APRIO_URL . 'assets/js/apollo-event-favorites.js',
                [ 'apollo-event-page-js' ],
                APOLLO_APRIO_VERSION,
                true
            );
            wp_localize_script(
                'apollo-event-favorites',
                'apolloEventsData',
                [
                    'restUrl'       => rest_url('apollo/v1'),
                    'nonce'         => wp_create_nonce('wp_rest'),
                    'currentUserId' => get_current_user_id(),
                ]
            );

            // Legacy favorites script (keep for backward compatibility)
            wp_enqueue_script(
                'apollo-events-favorites',
                APOLLO_APRIO_URL . 'assets/js/apollo-favorites.js',
                [ 'apollo-event-page-js' ],
                APOLLO_APRIO_VERSION,
                true
            );

            // Localize for AJAX
            wp_localize_script(
                'apollo-event-page-js',
                'apollo_events_ajax',
                [
                    'url'      => admin_url('admin-ajax.php'),
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce'    => wp_create_nonce('apollo_events_nonce'),
                ]
            );
        }//end if

        // ============================================
        // NOTE: uni.css was already registered at the start of enqueue_assets()
        // It will be enqueued LAST via force_uni_css_last() hook
        // ============================================

        // ============================================
        // DEBUG: Log asset loading
        // ============================================
        if (APOLLO_DEBUG) {
            error_log('🎨 Apollo Assets Loaded: ' . ($is_single_event ? 'SINGLE EVENT' : 'EVENTS PORTAL'));
        }
    }

    /**
     * Force uni.css to load LAST (highest priority)
     *
     * CRITICAL: uni.css is UNIVERSAL and MAIN CSS
     * It OVERRIDES all other CSS (Tailwind, ShadCN, plugin CSS, etc.)
     * This ensures uni.css from https://assets.apollo.rio.br/uni.css
     * is the single source of truth for all universal styles
     */
    public function force_uni_css_last()
    {
        if (! wp_style_is('apollo-uni-css', 'enqueued')) {
            wp_enqueue_style('apollo-uni-css');
        }
    }

    /**
     * Remove ALL theme CSS and JS when shortcode is active
     * Creates CANVAS pages (blank, independent)
     *
     * CRITICAL: Makes shortcodes POWERFUL and INDEPENDENT
     */
    private function remove_theme_assets_if_shortcode()
    {
        global $post;

        // Check if we're on a page with Apollo Events shortcode
        $has_apollo_shortcode = false;

        // Check event pages
        if (is_singular('event_listing') || is_post_type_archive('event_listing')) {
            $has_apollo_shortcode = true;
        }

        // Check specific pages
        if (is_page([ 'eventos', 'djs', 'locais', 'dashboard-eventos', 'mod-eventos' ])) {
            $has_apollo_shortcode = true;
        }

        // Check shortcodes in content
        if (! $has_apollo_shortcode && isset($post) && ! empty($post->post_content)) {
            if (
                has_shortcode($post->post_content, 'events') || has_shortcode($post->post_content, 'apollo_events') || has_shortcode($post->post_content, 'eventos-page') || has_shortcode($post->post_content, 'apollo_djs') || has_shortcode($post->post_content, 'apollo_locais')
            ) {
                $has_apollo_shortcode = true;
            }
        }

        if ($has_apollo_shortcode) {
            // Remove ALL theme CSS
            add_action('wp_enqueue_scripts', [ $this, 'dequeue_theme_assets' ], 999999);

            // Remove admin bar CSS (optional - cleaner canvas)
            add_action('wp_enqueue_scripts', [ $this, 'dequeue_admin_bar_assets' ], 999999);

            // Add body class for canvas mode
            add_filter('body_class', [ $this, 'add_canvas_body_class' ]);
        }
    }

    /**
     * Dequeue ALL theme CSS and JS
     * Creates blank canvas for shortcode pages
     */
    public function dequeue_theme_assets()
    {
        global $wp_styles, $wp_scripts;

        // List of handles to KEEP (Apollo assets only)
        $keep_styles = [
            'apollo-uni-css',
            'remixicon',
            'leaflet-css',
            'apollo-shadcn-components',
            'apollo-event-modal-css',
            'apollo-infinite-scroll-css',
            'admin-bar',
            // Keep admin bar for logged-in users
                            'dashicons',
        // Keep dashicons for admin bar
        ];

        $keep_scripts = [
            'jquery',
            'jquery-core',
            'jquery-migrate',
            'leaflet',
            'framer-motion',
            'apollo-base-js',
            'apollo-event-page-js',
            'apollo-loading-animation',
            'apollo-events-portal',
            'apollo-motion-event-card',
            'apollo-motion-modal',
            'apollo-infinite-scroll',
            'apollo-motion-dashboard',
            'apollo-motion-context-menu',
            'apollo-character-counter',
            'apollo-form-validation',
            'apollo-image-modal',
            'apollo-motion-gallery',
            'apollo-motion-local-page',
            'apollo-event-favorites',
            // P0-6: Unified favorites system
                            'apollo-events-favorites',
            // Legacy favorites
                            'admin-bar',
            // Keep admin bar for logged-in users
                            'hoverIntent',
        // Keep for admin bar
        ];

        // Dequeue ALL theme styles
        if (isset($wp_styles->registered)) {
            foreach ($wp_styles->registered as $handle => $style) {
                if (! in_array($handle, $keep_styles)) {
                    wp_dequeue_style($handle);
                    wp_deregister_style($handle);
                }
            }
        }

        // Dequeue ALL theme scripts
        if (isset($wp_scripts->registered)) {
            foreach ($wp_scripts->registered as $handle => $script) {
                if (! in_array($handle, $keep_scripts)) {
                    wp_dequeue_script($handle);
                    wp_deregister_script($handle);
                }
            }
        }
    }

    /**
     * Dequeue admin bar assets for cleaner canvas
     * (Optional - only if user wants completely clean pages)
     */
    public function dequeue_admin_bar_assets()
    {
        // Uncomment to remove admin bar completely
        // remove_action('wp_head', '_admin_bar_bump_cb');
        // wp_dequeue_style('admin-bar');
        // wp_dequeue_script('admin-bar');
    }

    /**
     * Add body class for canvas mode
     */
    public function add_canvas_body_class($classes)
    {
        $classes[] = 'apollo-canvas-mode';
        $classes[] = 'apollo-independent-page';

        return $classes;
    }

    /**
     * Check if assets should be enqueued
     */
    private function should_enqueue_assets()
    {
        global $post;

        // Get config safely
        $config = apollo_cfg();
        if (! is_array($config) || ! isset($config['cpt']) || ! is_array($config['cpt']) || ! isset($config['cpt']['event'])) {
            return false;
        }

        $event_post_type = $config['cpt']['event'];

        // Check if we're on the eventos page, event archive, or single event
        if (is_page('eventos') || is_post_type_archive($event_post_type) || is_singular($event_post_type)) {
            return true;
        }

        // Check if shortcode is present
        if (isset($post) && (has_shortcode($post->post_content, 'apollo_events') || has_shortcode($post->post_content, 'eventos-page'))) {
            return true;
        }

        return false;
    }

    /**
     * Events shortcode
     */
    public function events_shortcode($atts)
    {
        ob_start();

        // Get config safely
        $config = apollo_cfg();
        if (! is_array($config) || ! isset($config['cpt']) || ! is_array($config['cpt']) || ! isset($config['cpt']['event'])) {
            return '<p>' . esc_html__('Configuration error.', 'apollo-events-manager') . '</p>';
        }

        $event_post_type = $config['cpt']['event'];

        // Get events data (optimized with cache and limit)
        $cache_key = 'apollo_events_shortcode_' . md5(serialize($atts));
        $events    = wp_cache_get($cache_key, 'apollo_events');

        if ($events === false) {
            $events = get_posts(
                [
                    'post_type'      => $event_post_type,
                    'posts_per_page' => 50,
                    // Limit to prevent performance issues
                                                        'no_found_rows' => true,
                    // Skip pagination count for better performance
                                                        'update_post_meta_cache' => true,
                    // Cache meta for better performance
                                                        'update_post_term_cache' => false,
                    // Skip term cache if not needed
                                                        'meta_query' => [
                                                            [
                                                                'key'     => '_event_start_date',
                                                                'value'   => date('Y-m-d'),
                                                                'compare' => '>=',
                                                                'type'    => 'DATE',
                                                            ],
                                                        ],
                    'orderby'  => 'meta_value',
                    'meta_key' => '_event_start_date',
                    'order'    => 'ASC',
                ]
            );

            // Cache for 5 minutes
            wp_cache_set($cache_key, $events, 'apollo_events', 300);
        }//end if

        // Include template parts
        include APOLLO_APRIO_PATH . 'templates/event-listings-start.php';

        if ($events) {
            global $post;
            foreach ($events as $post) {
                setup_postdata($post);
                include APOLLO_APRIO_PATH . 'templates/event-card.php';
            }
            wp_reset_postdata();
        } else {
            echo '<p class="no-events-found">Nenhum evento encontrado.</p>';
        }

        include APOLLO_APRIO_PATH . 'templates/event-listings-end.php';

        return ob_get_clean();
    }

    /**
     * AJAX event filtering
     */
    public function ajax_filter_events()
    {
        check_ajax_referer('apollo_events_nonce', '_ajax_nonce');

        // Get config safely
        $config = apollo_cfg();
        if (! is_array($config) || ! isset($config['cpt']) || ! is_array($config['cpt']) || ! isset($config['cpt']['event'])) {
            wp_send_json_error('Configuration error');

            return;
        }

        $event_post_type = $config['cpt']['event'];

        // SECURITY: Sanitize all filter inputs with proper unslashing
        $category   = isset($_POST['category']) ? sanitize_text_field(wp_unslash($_POST['category'])) : '';
        $search     = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $date       = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';
        $local_slug = isset($_POST['local']) ? sanitize_text_field(wp_unslash($_POST['local'])) : '';
        // Filter by local slug
        $filter_type = isset($_POST['filter_type']) ? sanitize_text_field(wp_unslash($_POST['filter_type'])) : '';
        // SECURITY: Validate filter_type against whitelist
        if (! in_array($filter_type, [ 'local', 'category', '' ], true)) {
            $filter_type = '';
        }

        $args = [
            'post_type'      => $event_post_type,
            'posts_per_page' => 100,
            // Limit AJAX results to prevent performance issues
                            'meta_query' => [
                                [
                                    'key'     => '_event_start_date',
                                    'value'   => date('Y-m-d'),
                                    'compare' => '>=',
                                    'type'    => 'DATE',
                                ],
                            ],
            'orderby'  => 'meta_value',
            'meta_key' => '_event_start_date',
            'order'    => 'ASC',
        ];

        // Add local filter (priority over category if both are set)
        if ($filter_type === 'local' && $local_slug && $local_slug !== 'all') {
            // Find local post by slug
            $local_posts = get_posts(
                [
                    'post_type'      => 'event_local',
                    'post_status'    => 'publish',
                    'name'           => $local_slug,
                    'posts_per_page' => 1,
                ]
            );

            if (! empty($local_posts)) {
                $local_id = $local_posts[0]->ID;

                // Filter events that reference this local
                $args['meta_query'][] = [
                    'relation' => 'OR',
                    [
                        'key'     => '_event_local_ids',
                        'value'   => $local_id,
                        'compare' => '=',
                    ],
                    [
                        'key'     => '_event_local_ids',
                        'value'   => $local_id,
                        'compare' => '=',
                    ],
                    // Legacy fallback for migration
                    [
                        'key'     => '_event_local',
                        'value'   => $local_id,
                        'compare' => '=',
                    ],
                ];
            } else {
                // Local not found, return empty results
                $args['post__in'] = [ 0 ];
                // Force no results
            }//end if
        }//end if
        // Add category filter (only if not filtering by local)
        elseif ($category && $category !== 'all' && $filter_type !== 'local') {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'event_listing_category',
                    'field'    => 'slug',
                    'terms'    => $category,
                ],
            ];
        }

        // Add search filter
        if ($search) {
            $args['s'] = $search;
        }

        // Add date filter
        if ($date) {
            $args['meta_query'][] = [
                'key'     => '_event_start_date',
                'value'   => [ $date . '-01', $date . '-31' ],
                'compare' => 'BETWEEN',
                'type'    => 'DATE',
            ];
        }

        // Optimize query for performance
        if (! isset($args['no_found_rows'])) {
            $args['no_found_rows'] = true;
        }
        if (! isset($args['update_post_meta_cache'])) {
            $args['update_post_meta_cache'] = true;
        }
        if (! isset($args['update_post_term_cache'])) {
            $args['update_post_term_cache'] = false;
            // Skip if not needed
        }

        $events = get_posts($args);

        ob_start();
        if ($events) {
            foreach ($events as $event) {
                global $post;
                $post = $event;
                setup_postdata($post);
                include APOLLO_APRIO_PATH . 'templates/content-event_listing.php';
            }
            wp_reset_postdata();
        } else {
            echo '<p>' . esc_html__('No events found.', 'apollo-events-manager') . '</p>';
        }

        $response = ob_get_clean();
        wp_send_json_success($response);
    }

    /**
     * Get event location
     */
    private function get_event_location($event)
    {
        $event_id = is_object($event) ? $event->ID : $event;
        $location = apollo_get_post_meta($event_id, '_event_location', true);

        return $location ?: __('Location TBA', 'apollo-events-manager');
    }

    /**
     * Get event banner
     */
    private function get_event_banner($event_id)
    {
        $banner_id = apollo_get_post_meta($event_id, '_event_banner', true);
        if ($banner_id) {
            return wp_get_attachment_image_src($banner_id, 'full');
        }

        if (has_post_thumbnail($event_id)) {
            return wp_get_attachment_image_src(get_post_thumbnail_id($event_id), 'full');
        }

        return false;
    }

    /**
     * Eventos Page Shortcode (Complete Portal)
     */
    public function eventos_page_shortcode($atts)
    {
        $config = apollo_cfg();
        if (! is_array($config) || ! isset($config['cpt']['event'])) {
            return '<p>' . esc_html__('Configuration error.', 'apollo-events-manager') . '</p>';
        }

        $template = APOLLO_APRIO_PATH . 'templates/portal-discover.php';
        if (file_exists($template)) {
            ob_start();

            /**
             * STEP-2 RELEASE NOTE:
             * /eventos/ always renders `portal-discover.php` and relies on the
             * iframe modal logic inside that template. Legacy hash/lightbox
             * flows stay disabled until a dedicated follow-up.
             */
            include $template;

            return ob_get_clean();
        }

        ob_start();

        // Legacy fallback kept for safety if template is missing
        $event_post_type = $config['cpt']['event'];

        // Get all events
        $args = [
            'post_type'      => $event_post_type,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'no_found_rows'  => true,
            // Skip pagination count for better performance
                            'update_post_meta_cache' => true,
            // Cache meta for better performance
                            'update_post_term_cache' => false,
            // Skip term cache if not needed
                            'meta_query' => [
                                [
                                    'key'     => '_event_start_date',
                                    'value'   => date('Y-m-d'),
                                    'compare' => '>=',
                                    'type'    => 'DATE',
                                ],
                            ],
            'orderby'  => 'meta_value',
            'meta_key' => '_event_start_date',
            'order'    => 'ASC',
        ];

        $events_query = new WP_Query($args);

        // Get unique months for filtering
        $event_months = [];
        if ($events_query->have_posts()) {
            while ($events_query->have_posts()) {
                $events_query->the_post();
                $start_date = apollo_get_post_meta(get_the_ID(), '_event_start_date', true);
                if ($start_date) {
                    $month_key   = date('M', strtotime($start_date));
                    $month_lower = strtolower($month_key);
                    if ($month_lower == 'oct') {
                        $month_lower = 'out';
                    }
                    if ($month_lower == 'dec') {
                        $month_lower = 'dez';
                    }
                    $event_months[ $month_lower ] = true;
                }
            }
            wp_reset_postdata();
        }
        ?>
		<div class="event-manager-shortcode-wrapper discover-events-now-shortcode">

			<section class="hero-section">
				<h1 class="title-page">Experience Tomorrow's Events</h1>
				<p class="subtitle-page">Um novo <mark>&nbsp;hub digital que conecta cultura,&nbsp;</mark> tecnologia e experiências em tempo real... <mark>&nbsp;O futuro da cultura carioca começa aqui!&nbsp;</mark></p>
			</section>

			<!-- Filters & Search -->
			<div class="filters-and-search">
				<div class="menutags event_types">
					<button class="menutag event-category active" data-slug="all">All</button>
					<?php
                    $categories = get_terms(
                        [
                            'taxonomy'   => 'event_listing_category',
                            'hide_empty' => false,
                        ]
                    );
        foreach ($categories as $cat) {
            $cat_name = $cat->name;
            // Custom labels
            if ($cat->slug == 'music') {
                $cat_name = 'Underground';
            }
            if ($cat->slug == 'art-culture') {
                $cat_name = 'Art & Cultur<font style="position:absolute;transform:rotate(210deg);margin-left:0px">a</font>';
            }
            if ($cat->slug == 'mainstream') {
                $cat_name = 'Mainstream';
            }
            if ($cat->slug == 'workshops') {
                $cat_name = 'D-Edge club';
            }

            echo '<button class="menutag event-category" data-slug="' . esc_attr($cat->slug) . '">' . $cat_name . '</button>';
        }
        ?>
				</div>

				<div class="search-date-controls">
					<form class="box-search" role="search" id="eventSearchForm">
						<label for="eventSearchInput" class="visually-hidden">Procurar</label>
						<i class="ri-search-line"></i>
						<input type="text" name="search_keywords" id="eventSearchInput" placeholder="">
						<input type="hidden" name="post_type" value="event_listing">
					</form>

					<div class="box-rio" id="eventDatePicker">
						<button type="button" class="date-arrow" id="datePrev" aria-label="Previous month">‹</button>
						<span class="date-display" id="dateDisplay"></span>
						<button type="button" class="date-arrow" id="dateNext" aria-label="Next month">›</button>
					</div>
				</div>
			</div>

			<!-- Layout Toggle -->
			<div class="aprio-col aprio-col-12 aprio-col-sm-6 aprio-col-md-6 aprio-col-lg-4">
				<div class="aprio-event-layout-action-wrapper">
					<div class="aprio-event-layout-action">
						<div class="aprio-event-layout-icon aprio-event-list-layout aprio-active-layout" title="Events List View" id="aprio-event-toggle-layout" onclick="toggleLayout(this)">
							<i class="aprio-icon-menu"></i>
						</div>
					</div>
				</div>
			</div>

			<!-- Event Listings Grid -->
			<div class="event_listings">
				<?php
                if ($events_query->have_posts()) {
                    while ($events_query->have_posts()) {
                        $events_query->the_post();
                        include APOLLO_APRIO_PATH . 'templates/event-card.php';
                    }
                    wp_reset_postdata();
                } else {
                    echo '<p>Nenhum evento encontrado.</p>';
                }
        ?>
			</div>

			<!-- Highlight Banner -->
			<section class="banner-ario-1-wrapper" style="margin-top: 80px;">
				<img src="https://images.unsplash.com/photo-1506157786151-b8491531f063?q=80&w=2070&auto=format&fit=crop" class="ban-ario-1-img" alt="Upcoming Festival">
				<div class="ban-ario-1-content">
					<h3 class="ban-ario-1-subtit">Extra! Extra!</h3>
					<h2 class="ban-ario-1-titl">Retrospectiva Clubbe::rio 2026</h2>
					<p class="ban-ario-1-txt">
						A Retrospectiva Clubber 2026 está chegando! E em breve vamos liberar as primeiras novidades... Fique ligado, porque essa publicação promete celebrar tudo o que fez o coração da pista bater mais forte! Spoilers?
					</p>
					<a href="#" class="ban-ario-1-btn">
						Saiba Mais <i class="ri-arrow-right-long-line"></i>
					</a>
				</div>
			</section>

		</div>

		<!-- Legacy fallback modal markup. Step 2 canonical flow uses portal-discover.php iframe modal. -->
		<!-- Lightbox Modal for Single Event -->
		<div id="eventLightbox" class="event-lightbox" style="display:none;">
			<div class="event-lightbox-overlay"></div>
			<div class="event-lightbox-content">
				<button class="event-lightbox-close"><i class="ri-close-line"></i></button>
				<div id="eventLightboxBody"></div>
			</div>
		</div>

		<script>
			jQuery(document).ready(function($) {
				var ajaxUrl = (window.apollo_events_ajax &&
						(window.apollo_events_ajax.url || window.apollo_events_ajax.ajax_url)) ||
					'<?php echo esc_js(admin_url('admin-ajax.php')); ?>';

				var ajaxNonce = (window.apollo_events_ajax && window.apollo_events_ajax.nonce) ||
					'<?php echo wp_create_nonce('apollo_events_nonce'); ?>';

				// Event card click handler for lightbox
				$(document).on('click', '.event_listing', function(e) {
					e.preventDefault();
					var eventId = $(this).data('event-id');

					if (!eventId) {
						return;
					}

					$.ajax({
						url: ajaxUrl,
						type: 'POST',
						data: {
							action: 'load_event_single',
							event_id: eventId,
							_ajax_nonce: ajaxNonce
						},
						success: function(response) {
							$('#eventLightboxBody').html(response);
							$('#eventLightbox').fadeIn(300);
							$('body').css('overflow', 'hidden');
						}
					});
				});

				// Close lightbox
				$(document).on('click', '.event-lightbox-close, .event-lightbox-overlay', function() {
					$('#eventLightbox').fadeOut(300);
					$('body').css('overflow', '');
				});
			});
		</script>

		<style>
			.event-lightbox {
				position: fixed;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				z-index: 99999;
			}

			.event-lightbox-overlay {
				position: absolute;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				background: rgba(0, 0, 0, 0.85);
				backdrop-filter: blur(10px);
			}

			.event-lightbox-content {
				position: absolute;
				top: 50%;
				left: 50%;
				transform: translate(-50%, -50%);
				width: 90%;
				max-width: 500px;
				max-height: 90vh;
				overflow-y: auto;
				background: var(--bg-color, #fff);
				border-radius: 20px;
				box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
			}

			.event-lightbox-close {
				position: sticky;
				top: 10px;
				right: 10px;
				float: right;
				z-index: 10;
				background: rgba(0, 0, 0, 0.5);
				border: none;
				border-radius: 50%;
				width: 40px;
				height: 40px;
				display: flex;
				align-items: center;
				justify-content: center;
				cursor: pointer;
				color: #fff;
				font-size: 24px;
			}
		</style>
		<?php

        return ob_get_clean();
    }

    /**
     * AJAX handler for submitting event comments (Registros)
     */
    public function ajax_submit_event_comment()
    {
        // Require login
        if (! is_user_logged_in()) {
            wp_send_json_error(
                [
                    'message' => __('Faça login para deixar um registro.', 'apollo-events-manager'),
                ],
                401
            );

            return;
        }

        // SECURITY: Verify nonce with proper unslashing
        $nonce = isset($_POST['apollo_comment_nonce']) ? sanitize_text_field(wp_unslash($_POST['apollo_comment_nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'apollo_event_comment')) {
            wp_send_json_error(
                [
                    'message' => __('Sessão expirada. Recarregue a página.', 'apollo-events-manager'),
                ],
                403
            );

            return;
        }

        // SECURITY: Sanitize inputs with proper unslashing
        $event_id = isset($_POST['event_id']) ? absint(wp_unslash($_POST['event_id'])) : 0;
        $content  = isset($_POST['registro_content']) ? sanitize_textarea_field(wp_unslash($_POST['registro_content'])) : '';

        if ($event_id <= 0) {
            wp_send_json_error([ 'message' => __('Evento inválido.', 'apollo-events-manager') ]);

            return;
        }

        if (empty($content) || strlen($content) < 3) {
            wp_send_json_error([ 'message' => __('O registro precisa ter pelo menos 3 caracteres.', 'apollo-events-manager') ]);

            return;
        }

        if (strlen($content) > 500) {
            wp_send_json_error([ 'message' => __('O registro não pode exceder 500 caracteres.', 'apollo-events-manager') ]);

            return;
        }

        // Verify event exists
        $event = get_post($event_id);
        if (! $event || $event->post_type !== 'event_listing') {
            wp_send_json_error([ 'message' => __('Evento não encontrado.', 'apollo-events-manager') ]);

            return;
        }

        // Get current user
        $user = wp_get_current_user();

        // Insert comment
        $comment_data = [
            'comment_post_ID'      => $event_id,
            'comment_author'       => $user->display_name,
            'comment_author_email' => $user->user_email,
            'comment_author_url'   => $user->user_url,
            'comment_content'      => $content,
            'comment_type'         => 'comment',
            'comment_approved'     => 1,
            // Auto-approve for logged-in users
                            'user_id' => $user->ID,
        ];

        $comment_id = wp_insert_comment($comment_data);

        if ($comment_id) {
            wp_send_json_success(
                [
                    'message'    => __('Registro enviado com sucesso!', 'apollo-events-manager'),
                    'comment_id' => $comment_id,
                ]
            );
        } else {
            wp_send_json_error([ 'message' => __('Erro ao enviar o registro. Tente novamente.', 'apollo-events-manager') ]);
        }
    }

    /**
     * AJAX handler for loading single event
     */
    public function ajax_load_event_single()
    {
        check_ajax_referer('apollo_events_nonce', '_ajax_nonce');

        // SECURITY: Sanitize event_id with absint
        $event_id = isset($_POST['event_id']) ? absint(wp_unslash($_POST['event_id'])) : 0;

        if ($event_id <= 0) {
            wp_send_json_error('Evento inválido');

            return;
        }

        global $post;
        $post = get_post($event_id);

        if (! $post || $post->post_type !== 'event_listing') {
            wp_send_json_error('Evento não encontrado');

            return;
        }

        // SECURITY: Verify event is published (or user can edit it)
        if ($post->post_status !== 'publish' && ! current_user_can('edit_post', $event_id)) {
            wp_send_json_error('Evento não disponível');

            return;
        }

        setup_postdata($post);

        include APOLLO_APRIO_PATH . 'templates/single-event.php';

        wp_reset_postdata();
        wp_die();
    }

    /**
     * Handle favorite toggle AJAX
     */
    public function ajax_toggle_favorite()
    {
        // Require login for favorites
        if (! is_user_logged_in()) {
            wp_send_json_error(
                [
                    'message'   => __('Entre na sua conta para salvar favoritos.', 'apollo-events-manager'),
                    'login_url' => esc_url(wp_login_url(get_permalink())),
                ],
                401
            );

            return;
        }

        check_ajax_referer('apollo_events_nonce', '_ajax_nonce');

        // SECURITY: Sanitize event_id with absint
        $event_id = isset($_POST['event_id']) ? absint(wp_unslash($_POST['event_id'])) : 0;

        if ($event_id <= 0) {
            wp_send_json_error('Evento inválido');

            return;
        }

        // Verify event exists
        $event = get_post($event_id);
        if (! $event || $event->post_type !== 'event_listing') {
            wp_send_json_error('Evento não encontrado');

            return;
        }

        // Get current favorites count
        $current_count = apollo_get_post_meta($event_id, '_favorites_count', true);
        $current_count = $current_count ? absint($current_count) : 0;

        // SECURITY: Sanitize action_type and validate against whitelist
        $action = isset($_POST['action_type']) ? sanitize_text_field(wp_unslash($_POST['action_type'])) : 'add';
        if (! in_array($action, [ 'add', 'remove' ], true)) {
            $action = 'add';
        }

        if (class_exists('Apollo_Event_Stats')) {
            $stats_class = 'Apollo_Event_Stats';
            if ($action === 'add') {
                call_user_func_array([ $stats_class, 'record_action' ], [ $event_id, 'favorite', get_current_user_id() ]);
            } else {
                call_user_func_array([ $stats_class, 'record_action' ], [ $event_id, 'unfavorite', get_current_user_id() ]);
            }
        }

        if ($action === 'add') {
            $new_count = $current_count + 1;
        } else {
            $new_count = max(0, $current_count - 1);
        }

        apollo_update_post_meta($event_id, '_favorites_count', $new_count);

        wp_send_json_success(
            [
                'count'  => $new_count,
                'action' => $action,
            ]
        );
    }

    /**
     * AJAX handler for event modal content
     * Returns HTML for the lightbox modal
     */
    /**
     * AJAX: Approve event (mod)
     */
    public function ajax_mod_approve_event()
    {
        // SECURITY: Verify nonce with proper unslashing
        $nonce = isset($_POST['apollo_mod_nonce']) ? sanitize_text_field(wp_unslash($_POST['apollo_mod_nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'apollo_mod_events')) {
            wp_send_json_error(__('Nonce inválido.', 'apollo-events-manager'), 403);

            return;
        }

        // Check capabilities for mod
        if (! current_user_can('edit_posts') && ! current_user_can('edit_event_listings')) {
            wp_send_json_error(__('Você não tem permissão para aprovar eventos.', 'apollo-events-manager'), 403);

            return;
        }

        // SECURITY: Sanitize input
        $event_id = isset($_POST['event_id']) ? absint(wp_unslash($_POST['event_id'])) : 0;

        if (! $event_id) {
            wp_send_json_error(__('ID do evento inválido.', 'apollo-events-manager'), 400);

            return;
        }

        $result = wp_update_post(
            [
                'ID'          => $event_id,
                'post_status' => 'publish',
            ]
        );

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());

            return;
        }

        // Mark as approved
        apollo_update_post_meta($event_id, '_apollo_mod_approved', '1');
        apollo_update_post_meta($event_id, '_apollo_mod_approved_date', current_time('mysql'));
        apollo_update_post_meta($event_id, '_apollo_mod_approved_by', get_current_user_id());

        // Clear rejection meta if exists
        apollo_delete_post_meta($event_id, '_apollo_mod_rejected');
        apollo_delete_post_meta($event_id, '_apollo_mod_rejected_date');

        wp_send_json_success(__('Evento aprovado e publicado com sucesso!', 'apollo-events-manager'));
    }

    /**
     * AJAX: Reject event (mod)
     */
    public function ajax_mod_reject_event()
    {
        // SECURITY: Verify nonce with proper unslashing
        $nonce = isset($_POST['apollo_mod_nonce']) ? sanitize_text_field(wp_unslash($_POST['apollo_mod_nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'apollo_mod_events')) {
            wp_send_json_error(__('Nonce inválido.', 'apollo-events-manager'), 403);

            return;
        }

        // Check capabilities for mod
        if (! current_user_can('edit_posts') && ! current_user_can('edit_event_listings')) {
            wp_send_json_error(__('Você não tem permissão para rejeitar eventos.', 'apollo-events-manager'), 403);

            return;
        }

        // SECURITY: Sanitize input
        $event_id = isset($_POST['event_id']) ? absint(wp_unslash($_POST['event_id'])) : 0;

        if (! $event_id) {
            wp_send_json_error(__('ID do evento inválido.', 'apollo-events-manager'), 400);

            return;
        }

        // Mark as rejected (keeps as draft)
        apollo_update_post_meta($event_id, '_apollo_mod_rejected', '1');
        apollo_update_post_meta($event_id, '_apollo_mod_rejected_date', current_time('mysql'));
        apollo_update_post_meta($event_id, '_apollo_mod_rejected_by', get_current_user_id());

        wp_send_json_success(__('Evento rejeitado. Removido da lista de moderação.', 'apollo-events-manager'));
    }

    public function ajax_get_event_modal()
    {
        try {
            // Verify nonce (standardized - JS sends 'nonce' field)
            check_ajax_referer('apollo_events_nonce', 'nonce');

            // Validar ID (support both POST and GET for debugging)
            $event_id = 0;
            if (isset($_POST['event_id'])) {
                $event_id = absint(sanitize_text_field(wp_unslash($_POST['event_id'])));
            } elseif (isset($_GET['event_id'])) {
                $event_id = absint(sanitize_text_field(wp_unslash($_GET['event_id'])));
            }

            if (! $event_id) {
                $error_data = [ 'message' => 'ID do evento inválido' ];
                // Only include debug info in debug mode
                if (defined('WP_DEBUG') && WP_DEBUG && defined('APOLLO_PORTAL_DEBUG') && APOLLO_PORTAL_DEBUG) {
                    $error_data['debug'] = [
                        'POST' => isset($_POST['event_id']) ? absint($_POST['event_id']) : 0,
                        'GET'  => isset($_GET['event_id']) ? absint($_GET['event_id']) : 0,
                    ];
                }
                wp_send_json_error($error_data);

                return;
            }

            // Verificar se evento existe
            $event_post = get_post($event_id);
            if (! $event_post || $event_post->post_type !== 'event_listing' || $event_post->post_status !== 'publish') {
                wp_send_json_error([ 'message' => 'Evento não encontrado' ]);

                return;
            }

            // Load helper if not already loaded
            if (! class_exists('Apollo_Event_Data_Helper')) {
                require_once APOLLO_APRIO_PATH . 'includes/helpers/event-data-helper.php';
            }

            // Build local context for template overrides using helper
            $local         = Apollo_Event_Data_Helper::get_local_data($event_id);
            $local_context = [
                'local_name'    => $local ? $local['name'] : '',
                'local_address' => $local ? $local['address'] : '',
                'local_images'  => [],
                'local_lat'     => $local ? (string) $local['lat'] : '',
                'local_long'    => $local ? (string) $local['lng'] : '',
            ];

            // Get local images if local exists
            if ($local && $local['id']) {
                for ($i = 1; $i <= 5; $i++) {
                    $img = apollo_get_post_meta($local['id'], '_local_image_' . $i, true);
                    if (! empty($img)) {
                        $local_context['local_images'][] = is_numeric($img) ? wp_get_attachment_url($img) : $img;
                    }
                }
            }

            // Fallbacks
            if ($local_context['local_name'] === '') {
                $fallback_location = apollo_get_post_meta($event_id, '_event_location', true);
                if ($fallback_location !== '') {
                    $local_context['local_name'] = $fallback_location;
                }
            }

            if ($local_context['local_address'] === '') {
                $event_address = apollo_get_post_meta($event_id, '_event_address', true);
                if ($event_address !== '') {
                    $local_context['local_address'] = $event_address;
                }
            }

            if ($local_context['local_lat'] === '' || ! is_numeric($local_context['local_lat'])) {
                $event_lat = apollo_get_post_meta($event_id, '_event_latitude', true);
                if ($event_lat !== '' && is_numeric($event_lat)) {
                    $local_context['local_lat'] = $event_lat;
                }
            }

            if ($local_context['local_long'] === '' || ! is_numeric($local_context['local_long'])) {
                $event_long = apollo_get_post_meta($event_id, '_event_longitude', true);
                if ($event_long !== '' && is_numeric($event_long)) {
                    $local_context['local_long'] = $event_long;
                }
            }

            $GLOBALS['apollo_modal_context'] = [
                'is_modal'      => true,
                'event_url'     => get_permalink($event_id),
                'local_name'    => $local_context['local_name'],
                'local_address' => $local_context['local_address'],
                'local_images'  => $local_context['local_images'],
                'local_lat'     => $local_context['local_lat'],
                'local_long'    => $local_context['local_long'],
            ];

            // Set up global post for template
            global $post;
            $post = $event_post;
            setup_postdata($post);

            // Load the single event page template (CodePen EaPpjXP design)
            ob_start();

            // Wrap template content in modal structure
            echo '<div class="apollo-event-modal-overlay" data-apollo-close></div>';
            echo '<div class="apollo-event-modal-content" role="dialog" aria-modal="true" aria-labelledby="modal-title-' . $event_id . '">';
            echo '<button class="apollo-event-modal-close" type="button" data-apollo-close aria-label="Fechar">';
            echo '<i class="ri-close-line"></i>';
            echo '</button>';

            $template_file = APOLLO_APRIO_PATH . 'templates/single-event-page.php';
            if (file_exists($template_file)) {
                include $template_file;
            } else {
                echo '<div class="apollo-error">Template não encontrado</div>';
            }

            echo '</div>';

            $html = ob_get_clean();

            wp_reset_postdata();

            $response = [ 'html' => $html ];

            unset($GLOBALS['apollo_modal_context']);

            wp_send_json_success($response);
        } catch (Exception $e) {
            // Log error in debug mode
            if (defined('WP_DEBUG') && WP_DEBUG && defined('APOLLO_PORTAL_DEBUG') && APOLLO_PORTAL_DEBUG) {
                error_log('Apollo Events: Error in ajax_get_event_modal - ' . $e->getMessage());
            }

            // Return graceful error
            wp_send_json_error(
                [
                    'message' => 'Erro ao carregar evento. Tente novamente mais tarde.',
                ]
            );
        }//end try
    }

    /**
     * Clear portal cache when event is saved
     */
    public function clear_portal_cache($post_id)
    {
        // Skip autosaves and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Only clear cache if it's an event_listing post type
        $post = get_post($post_id);
        if (! $post || $post->post_type !== 'event_listing') {
            return;
        }

        // FASE 1: Limpar cache do helper centralizado primeiro
        if (class_exists('Apollo_Event_Data_Helper')) {
            Apollo_Event_Data_Helper::flush_events_cache();
        }

        // Clear today's cache (backward compatibility)
        $cache_key = 'apollo_all_event_ids_' . date('Ymd');
        delete_transient($cache_key);

        // Clear previous days cache (cleanup - only last 3 days for performance)
        for ($i = 1; $i <= 3; $i++) {
            $old_date      = date('Ymd', strtotime("-{$i} days"));
            $old_cache_key = 'apollo_all_event_ids_' . $old_date;
            delete_transient($old_cache_key);
        }

        // Clear object cache if available
        if (function_exists('wp_cache_delete_group')) {
            call_user_func('wp_cache_delete_group', 'apollo_events');
        }

        if (defined('APOLLO_PORTAL_DEBUG') && APOLLO_PORTAL_DEBUG) {
            error_log("Apollo Portal: Cache cleared for event {$post_id}");
        }
    }

    /**
     * Clear portal cache on status change
     */
    public function clear_portal_cache_on_status_change($new_status, $old_status, $post)
    {
        if ($post->post_type === 'event_listing') {
            $this->clear_portal_cache($post->ID);
        }
    }

    /**
     * Force Brazil as the default country
     */
    public function force_brazil_country($fields)
    {
        if (isset($fields['event']['event_country'])) {
            $fields['event']['event_country']['default'] = 'BR';
            $fields['event']['event_country']['options'] = [ 'BR' => 'Brazil' ];
            $fields['event']['event_country']['type']    = 'hidden';
            // Hide the field since it's always Brazil
        }

        return $fields;
    }

    /**
     * Add custom fields to event submission form
     */
    public function add_custom_event_fields($fields)
    {
        // Add DJ selection field (multiple)
        $fields['event']['event_djs'] = [
            'label'       => __('DJs', 'apollo-events-manager'),
            'type'        => 'multiselect',
            'required'    => false,
            'options'     => $this->get_dj_options(),
            'placeholder' => __('Select DJs', 'apollo-events-manager'),
            'description' => __('Select the DJs performing at this event', 'apollo-events-manager'),
            'priority'    => 7,
        ];

        // Add timetable field
        $fields['event']['timetable'] = [
            'label'       => __('Timetable', 'apollo-events-manager'),
            'type'        => 'timetable',
            'required'    => false,
            'placeholder' => '',
            'description' => __('Add DJs and their performance times', 'apollo-events-manager'),
            'priority'    => 8,
        ];

        // Add local selection field
        $fields['event']['event_local'] = [
            'label'       => __('Local', 'apollo-events-manager'),
            'type'        => 'select',
            'required'    => false,
            'options'     => $this->get_local_options(),
            'placeholder' => __('Selecione um local', 'apollo-events-manager'),
            'description' => __('Escolha o local do evento', 'apollo-events-manager'),
            'priority'    => 9,
        ];

        // Add promotional images field
        $fields['event']['_3_imagens_promo'] = [
            'label'       => __('Promotional Images', 'apollo-events-manager'),
            'type'        => 'file',
            'required'    => false,
            'multiple'    => true,
            'placeholder' => '',
            'description' => __('Upload up to 3 promotional images', 'apollo-events-manager'),
            'priority'    => 10,
        ];

        // Add final image field
        $fields['event']['_imagem_final'] = [
            'label'       => __('Final Image', 'apollo-events-manager'),
            'type'        => 'file',
            'required'    => false,
            'placeholder' => '',
            'description' => __('Upload the final promotional image', 'apollo-events-manager'),
            'priority'    => 11,
        ];

        // Add coupon field
        $fields['event']['cupom_ario'] = [
            'label'       => __('Coupon Code', 'apollo-events-manager'),
            'type'        => 'text',
            'required'    => false,
            'placeholder' => __('Enter coupon code', 'apollo-events-manager'),
            'description' => __('Special coupon code for this event', 'apollo-events-manager'),
            'priority'    => 12,
        ];

        return $fields;
    }

    /**
     * Get DJ options for select field
     */
    private function get_dj_options()
    {
        $options = [];

        $djs = get_posts(
            [
                'post_type'      => 'event_dj',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            ]
        );

        foreach ($djs as $dj) {
            $dj_name            = apollo_get_post_meta($dj->ID, '_dj_name', true) ?: $dj->post_title;
            $options[ $dj->ID ] = $dj_name;
        }

        return $options;
    }

    /**
     * Get local options for select field
     */
    private function get_local_options()
    {
        $options = [ '' => __('Selecione um local', 'apollo-events-manager') ];

        $locals = get_posts(
            [
                'post_type'      => 'event_local',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            ]
        );

        foreach ($locals as $local) {
            $options[ $local->ID ] = $local->post_title;
        }

        return $options;
    }

    /**
     * Validate custom event fields
     */
    public function validate_custom_event_fields($validation_errors)
    {
        // Validate timetable format
        if (isset($_POST['timetable']) && ! empty($_POST['timetable'])) {
            $timetable = wp_unslash($_POST['timetable']);
            if (is_array($timetable)) {
                foreach ($timetable as $slot) {
                    $slot_time = isset($slot['time']) ? sanitize_text_field(wp_unslash($slot['time'])) : '';
                    $slot_dj   = isset($slot['dj']) ? sanitize_text_field(wp_unslash($slot['dj'])) : '';
                    if ($slot_time === '' || $slot_dj === '') {
                        $validation_errors[] = __('Invalid timetable format', 'apollo-events-manager');

                        break;
                    }
                }
            }
        }

        // Validate coupon code format
        if (isset($_POST['cupom_ario']) && ! empty($_POST['cupom_ario'])) {
            $coupon = sanitize_text_field(wp_unslash($_POST['cupom_ario']));
            if (strlen($coupon) > 20) {
                $validation_errors[] = __('Coupon code must be less than 20 characters', 'apollo-events-manager');
            }
        }

        return $validation_errors;
    }

    /**
     * Save custom event fields
     */
    public function save_custom_event_fields($post_id, $post)
    {
        // Security checks
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'update-post_' . $post_id)) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        // Front-end submission uses distinct field names. Bail early on admin saves
        // to prevent running in parallel with the metabox handler.
        $has_frontend_fields = isset($_POST['event_djs'])
            || isset($_POST['event_local'])
            || isset($_POST['timetable'])
            || isset($_POST['_3_imagens_promo'])
            || isset($_POST['_imagem_final'])
            || isset($_POST['cupom_ario']);

        if (! $has_frontend_fields) {
            return;
        }

        // Save DJs (WordPress handles serialization automatically)
        $posted_djs = isset($_POST['event_djs']) ? wp_unslash($_POST['event_djs']) : null;
        if ($posted_djs !== null) {
            $dj_ids = array_values(array_filter(array_map('intval', (array) $posted_djs)));
            if (! empty($dj_ids)) {
                apollo_update_post_meta($post_id, '_event_dj_ids', $dj_ids);

                // Debug log (temporary for validation)
                if (defined('WP_DEBUG') && WP_DEBUG && defined('APOLLO_PORTAL_DEBUG') && APOLLO_PORTAL_DEBUG) {
                    error_log(sprintf('[Apollo Events] save_custom_event_fields: Event %d saved DJ IDs: %s', $post_id, implode(', ', $dj_ids)));
                }
            } else {
                apollo_delete_post_meta($post_id, '_event_dj_ids');
            }
        }

        // Save local relationship as single integer (not array)
        $posted_local = isset($_POST['event_local']) ? sanitize_text_field(wp_unslash($_POST['event_local'])) : null;
        if ($posted_local !== null) {
            // Handle both single value and array (for backward compatibility)
            $local_id = is_array($posted_local) ? (int) reset($posted_local) : (int) $posted_local;
            if ($local_id > 0) {
                // Use unified connection manager
                if (class_exists('Apollo_Local_Connection')) {
                    $connection = Apollo_Local_Connection::get_instance();
                    $connection->set_local_id($post_id, $local_id);
                } else {
                    apollo_update_post_meta($post_id, '_event_local_ids', $local_id);
                }

                // Debug log (temporary for validation)
                if (defined('WP_DEBUG') && WP_DEBUG && defined('APOLLO_PORTAL_DEBUG') && APOLLO_PORTAL_DEBUG) {
                    error_log(sprintf('[Apollo Events] save_custom_event_fields: Event %d saved Local ID: %d', $post_id, $local_id));
                }
            } else {
                // Use unified connection manager
                if (class_exists('Apollo_Local_Connection')) {
                    $connection = Apollo_Local_Connection::get_instance();
                    $connection->set_local_id($post_id, 0);
                } else {
                    apollo_delete_post_meta($post_id, '_event_local_ids');
                }
            }//end if
        }//end if

        // Save timetable
        if (array_key_exists('timetable', $_POST)) {
            $clean_timetable = apollo_sanitize_timetable(wp_unslash($_POST['timetable']));
            if (! empty($clean_timetable)) {
                apollo_update_post_meta($post_id, '_event_timetable', $clean_timetable);

                // Debug log (temporary for validation)
                if (defined('WP_DEBUG') && WP_DEBUG && defined('APOLLO_PORTAL_DEBUG') && APOLLO_PORTAL_DEBUG) {
                    error_log(sprintf('[Apollo Events] save_custom_event_fields: Event %d saved timetable with %d slots', $post_id, count($clean_timetable)));
                }
            } else {
                apollo_delete_post_meta($post_id, '_event_timetable');
            }
        }

        // Save promotional images (array of URLs)
        if (isset($_POST['_3_imagens_promo']) && is_array($_POST['_3_imagens_promo'])) {
            $clean_images = array_map('esc_url_raw', array_map('wp_unslash', array_filter($_POST['_3_imagens_promo'])));
            apollo_update_post_meta($post_id, '_3_imagens_promo', $clean_images);
        }

        // Save final image (ID or URL)
        if (isset($_POST['_imagem_final'])) {
            $raw_final_image = wp_unslash($_POST['_imagem_final']);
            $final_image     = is_numeric(sanitize_text_field($raw_final_image))
                ? absint($raw_final_image)
                : esc_url_raw($raw_final_image);

            apollo_update_post_meta($post_id, '_imagem_final', $final_image);
        }

        // Save coupon
        if (isset($_POST['cupom_ario'])) {
            apollo_update_post_meta($post_id, '_cupom_ario', sanitize_text_field(wp_unslash($_POST['cupom_ario'])));
        }

        // Clear cache after saving (safe for any WordPress installation)
        clean_post_cache($post_id);

        // Limpar todos os caches relacionados usando função centralizada
        if (function_exists('apollo_clear_events_cache')) {
            apollo_clear_events_cache($post_id);
        } else {
            // Fallback: limpar transients conhecidos diretamente
            delete_transient('apollo_events_portal_cache');
            delete_transient('apollo_events_home_cache');
            delete_transient('apollo_upcoming_event_ids_' . date('Ymd'));

            // Limpar cache do grupo apollo_events
            if (function_exists('wp_cache_delete_group')) {
                call_user_func('wp_cache_delete_group', 'apollo_events');
            } elseif (function_exists('wp_cache_flush_group')) {
                call_user_func('wp_cache_flush_group', 'apollo_events');
            }
        }
    }

    /**
     * Inject content in single event pages (prepend/append)
     * Only on singular, main query, in the loop
     */
    public function inject_event_content($content)
    {
        // Get config safely
        $config = apollo_cfg();
        if (! is_array($config) || ! isset($config['cpt']) || ! isset($config['cpt']['event'])) {
            return $content;
        }

        $event_post_type = $config['cpt']['event'];

        // Only inject on single event pages in main query
        if (! is_singular($event_post_type) || ! in_the_loop() || ! is_main_query()) {
            return $content;
        }

        ob_start();
        ?>
		<div class="apollo-single__compact">
			<?php
            $event_id = get_the_ID();
        $startD       = apollo_get_post_meta($event_id, '_event_start_date', true);
        $startT       = apollo_get_post_meta($event_id, '_event_start_time', true);
        ?>
			<div class="compact-datetime">
				<strong><?php echo esc_html($startD); ?></strong>
				<?php if ($startT) : ?>
					<span><?php echo esc_html($startT); ?></span>
				<?php endif; ?>
			</div>
			<div class="compact-actions">
				<a class="btn share" href="#" onclick="if(navigator.share){navigator.share({title:document.title, url:location.href});return false;}">
					<i class="ri-share-forward-line"></i> Compartilhar
				</a>
			</div>
		</div>
		<?php
        $prepend = ob_get_clean();

        ob_start();
        ?>
		<div class="apollo-single__extra">
			<?php do_action('apollo_single_after_content'); ?>
		</div>
		<?php
        $append = ob_get_clean();

        return $prepend . $content . $append;
    }

    /**
     * Apollo Event Shortcode
     *
     * Usage: [apollo_event field="dj_list" id="123"]
     *        [apollo_event field="location"]
     *
     * @param array $atts Shortcode attributes
     * @return string Placeholder value
     */
    public function apollo_event_shortcode($atts)
    {
        $atts = shortcode_atts(
            [
                'field' => '',
                'id'    => 0,
            ],
            $atts,
            'apollo_event'
        );

        if (empty($atts['field'])) {
            return '';
        }

        $event_id = $atts['id'] ? (int) $atts['id'] : get_the_ID();
        if (! $event_id) {
            return '';
        }

        if (! function_exists('apollo_event_get_placeholder_value')) {
            return '';
        }

        $value = apollo_event_get_placeholder_value($atts['field'], $event_id);

        return $value;
    }

    /**
     * Add admin menu for placeholders documentation
     * DISABLED: Consolidated into admin-apollo-hub.php to avoid duplicate menus
     */
    /*
    public function add_admin_menu()
    {
        add_menu_page(
            __('Apollo Events', 'apollo-events-manager'),
            __('Apollo Events', 'apollo-events-manager'),
            'manage_options',
            'apollo-events',
            [ $this, 'render_admin_dashboard' ],
            'dashicons-calendar-alt',
            30
        );

        add_submenu_page(
            'apollo-events',
            __('Dashboard', 'apollo-events-manager'),
            __('Dashboard', 'apollo-events-manager'),
            'view_apollo_event_stats',
            'apollo-events-dashboard',
            [ $this, 'render_analytics_dashboard' ]
        );

        add_submenu_page(
            'apollo-events',
            __('User Overview', 'apollo-events-manager'),
            __('User Overview', 'apollo-events-manager'),
            'view_apollo_event_stats',
            'apollo-events-user-overview',
            [ $this, 'render_user_overview' ]
        );

        add_submenu_page(
            'apollo-events',
            __('Shortcodes & Placeholders', 'apollo-events-manager'),
            __('Shortcodes & Placeholders', 'apollo-events-manager'),
            'manage_apollo',
            'apollo-events-placeholders',
            [ $this, 'render_placeholders_page' ]
        );
    }
    */

    /**
     * Configure Co-Authors Plus support for event_listing and event_dj
     */
    public function configure_gestao_support()
    {
        // Check if Co-Authors Plus is active
        if (! function_exists('coauthors_support_theme')) {
            // Plugin not active, log warning only in debug mode
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Apollo: Co-Authors Plus não está ativo. Instale o plugin para suporte a múltiplos autores.');
            }

            return;
        }

        // Add co-authors support to event_listing
        add_post_type_support('event_listing', 'co-authors');

        // Add co-authors support to event_dj
        add_post_type_support('event_dj', 'co-authors');

        // Register event_listing with Co-Authors Plus
        if (function_exists('coauthors_plus_init')) {
            // Ensure Co-Authors Plus recognizes our post types
            add_filter('coauthors_supported_post_types', [ $this, 'add_gestao_supported_post_types' ]);
        }

        // Log success
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Apollo: Co-Authors Plus configurado para event_listing e event_dj');
        }
    }

    /**
     * Add Apollo post types to Co-Authors Plus supported types
     */
    public function add_gestao_supported_post_types($post_types)
    {
        if (! is_array($post_types)) {
            $post_types = [];
        }

        $post_types[] = 'event_listing';
        $post_types[] = 'event_dj';

        return array_unique($post_types);
    }

    /**
     * Migrate legacy meta keys to canonical keys
     * Runs on admin_init to migrate old data automatically
     */
    public function migrate_legacy_meta_keys()
    {
        // Only run migration once per admin session
        $migration_done = get_transient('apollo_meta_migration_done');
        if ($migration_done) {
            return;
        }

        // Get all event_listing posts
        $events = get_posts(
            [
                'post_type'              => 'event_listing',
                'posts_per_page'         => -1,
                'post_status'            => 'any',
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ]
        );

        $migrated_count = 0;

        foreach ($events as $event) {
            $post_id  = $event->ID;
            $migrated = false;

            // Migrate _event_djs to _event_dj_ids
            $legacy_djs = get_post_meta($post_id, '_event_djs', true);
            if ($legacy_djs !== '' && $legacy_djs !== false) {
                $canonical_djs = get_post_meta($post_id, '_event_dj_ids', true);
                if ($canonical_djs === '' || $canonical_djs === false || empty($canonical_djs)) {
                    // Migrate only if canonical doesn't exist or is empty
                    $djs_array = maybe_unserialize($legacy_djs);
                    if (! is_array($djs_array)) {
                        $djs_array = [ $djs_array ];
                    }
                    $djs_array = array_map('absint', $djs_array);
                    $djs_array = array_filter($djs_array);
                    if (! empty($djs_array)) {
                        apollo_update_post_meta($post_id, '_event_dj_ids', $djs_array);
                        $migrated = true;

                        // Log migration in debug mode
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log("Apollo: Migrated _event_djs to _event_dj_ids for event {$post_id}");
                        }
                    }
                }
            }//end if

            // Migrate _event_local to _event_local_ids
            $legacy_local = get_post_meta($post_id, '_event_local', true);
            if ($legacy_local !== '' && $legacy_local !== false) {
                $canonical_local = get_post_meta($post_id, '_event_local_ids', true);
                if ($canonical_local === '' || $canonical_local === false || empty($canonical_local)) {
                    // Migrate only if canonical doesn't exist or is empty
                    $local_id = absint($legacy_local);
                    if ($local_id > 0) {
                        apollo_update_post_meta($post_id, '_event_local_ids', $local_id);
                        $migrated = true;

                        // Log migration in debug mode
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log("Apollo: Migrated _event_local to _event_local_ids for event {$post_id}");
                        }
                    }
                }
            }

            // Migrate _timetable to _event_timetable
            $legacy_timetable = get_post_meta($post_id, '_timetable', true);
            if ($legacy_timetable !== '' && $legacy_timetable !== false) {
                $canonical_timetable = get_post_meta($post_id, '_event_timetable', true);
                if ($canonical_timetable === '' || $canonical_timetable === false || empty($canonical_timetable)) {
                    // Migrate only if canonical doesn't exist or is empty
                    $timetable = apollo_sanitize_timetable($legacy_timetable);
                    if (! empty($timetable)) {
                        apollo_update_post_meta($post_id, '_event_timetable', $timetable);
                        $migrated = true;

                        // Log migration in debug mode
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log("Apollo: Migrated _timetable to _event_timetable for event {$post_id}");
                        }
                    }
                }
            }

            if ($migrated) {
                ++$migrated_count;
            }
        }//end foreach

        wp_reset_postdata();

        // Set transient to prevent running again this session (5 minutes)
        set_transient('apollo_meta_migration_done', true, 5 * MINUTE_IN_SECONDS);

        // Log completion in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG && $migrated_count > 0) {
            error_log("Apollo: Migrated {$migrated_count} events from legacy meta keys to canonical keys");
        }

        return $migrated_count;
    }

    /**
     * Hook migration to admin_init
     */
    public function init_legacy_migration()
    {
        // Run migration on admin_init (only in admin)
        if (is_admin() && current_user_can('manage_options')) {
            $this->migrate_legacy_meta_keys();
        }
    }

    /**
     * Render admin dashboard (main menu page)
     */
    public function render_admin_dashboard()
    {
        ?>
		<div class="wrap">
			<h1><?php echo esc_html__('Apollo Events Manager', 'apollo-events-manager'); ?></h1>
			<p><?php echo esc_html__('Welcome to Apollo Events Manager. Use the submenus to access dashboards, analytics, and documentation.', 'apollo-events-manager'); ?></p>

			<div class="card" style="max-width: 800px; margin-top: 20px;">
				<h2><?php echo esc_html__('Quick Links', 'apollo-events-manager'); ?></h2>
				<ul>
					<li><a href="<?php echo admin_url('admin.php?page=apollo-events-dashboard'); ?>"><?php echo esc_html__('Dashboard & Analytics', 'apollo-events-manager'); ?></a></li>
					<li><a href="<?php echo admin_url('admin.php?page=apollo-events-user-overview'); ?>"><?php echo esc_html__('User Overview', 'apollo-events-manager'); ?></a></li>
					<li><a href="<?php echo admin_url('admin.php?page=apollo-events-placeholders'); ?>"><?php echo esc_html__('Shortcodes & Placeholders', 'apollo-events-manager'); ?></a></li>
				</ul>
			</div>
		</div>
		<?php
    }

    /**
     * Track event view when modal is opened
     */
    public function track_event_view_on_modal()
    {
        // This runs before ajax_get_event_modal processes the request
        // Extract event_id from POST data
        if (isset($_POST['event_id']) && is_numeric($_POST['event_id'])) {
            $event_id = absint($_POST['event_id']);
            if (function_exists('apollo_record_event_view')) {
                apollo_record_event_view($event_id);
            }
        }
    }

    /**
     * Render analytics dashboard page (Enhanced with shadcn-style components)
     */
    public function render_analytics_dashboard()
    {
        // Allow manage_options as fallback for admin
        if (! current_user_can('view_apollo_event_stats') && ! current_user_can('manage_options')) {
            wp_die(__('You do not have permission to view this page.', 'apollo-events-manager'));
        }

        if (! function_exists('apollo_get_global_event_stats')) {
            echo '<div class="wrap"><p>' . esc_html__('Analytics system not loaded.', 'apollo-events-manager') . '</p></div>';

            return;
        }

        $stats = apollo_get_global_event_stats();

        // Verificar se função existe antes de chamar
        $top_users = function_exists('apollo_get_top_users_by_interactions')
            ? apollo_get_top_users_by_interactions(10)
            : [];

        // Load dashboard widgets
        $dashboard_widgets_file = APOLLO_APRIO_PATH . 'includes/dashboard-widgets.php';
        if (file_exists($dashboard_widgets_file)) {
            require_once $dashboard_widgets_file;
        } else {
            apollo_log_missing_file($dashboard_widgets_file);
        }

        // Get current user role badge
        $current_user = wp_get_current_user();
        $user_badge   = function_exists('apollo_get_role_badge') ? apollo_get_role_badge($current_user) : '';
        ?>
		<div class="wrap apollo-dashboard-wrap">
			<!-- Dashboard Header -->
			<div class="apollo-dashboard-header">
				<div>
					<h1 class="apollo-dashboard-title">
						<?php echo esc_html__('Apollo Events Dashboard', 'apollo-events-manager'); ?>
					</h1>
					<p class="apollo-dashboard-subtitle">
						<?php echo esc_html__('Analytics, statistics, and management tools', 'apollo-events-manager'); ?>
					</p>
				</div>
				<div class="apollo-dashboard-user-info">
					<?php echo wp_kses_post($user_badge); ?>
					<span class="apollo-user-name"><?php echo esc_html($current_user->display_name); ?></span>
				</div>
			</div>

			<!-- Key Metrics Cards (shadcn-style) -->
			<div class="apollo-metrics-grid">
				<div class="apollo-metric-card">
					<div class="metric-icon">
						<i class="ri-calendar-event-line"></i>
					</div>
					<div class="metric-content">
						<p class="metric-label"><?php echo esc_html__('Total Events', 'apollo-events-manager'); ?></p>
						<p class="metric-value"><?php echo esc_html(number_format_i18n($stats['total_events'])); ?></p>
					</div>
				</div>

				<div class="apollo-metric-card">
					<div class="metric-icon">
						<i class="ri-calendar-check-line"></i>
					</div>
					<div class="metric-content">
						<p class="metric-label"><?php echo esc_html__('Future Events', 'apollo-events-manager'); ?></p>
						<p class="metric-value"><?php echo esc_html(number_format_i18n($stats['future_events'])); ?></p>
					</div>
				</div>

				<div class="apollo-metric-card">
					<div class="metric-icon">
						<i class="ri-eye-line"></i>
					</div>
					<div class="metric-content">
						<p class="metric-label"><?php echo esc_html__('Total Views', 'apollo-events-manager'); ?></p>
						<p class="metric-value"><?php echo esc_html(number_format_i18n($stats['total_views'])); ?></p>
					</div>
				</div>

				<div class="apollo-metric-card">
					<div class="metric-icon">
						<i class="ri-user-heart-line"></i>
					</div>
					<div class="metric-content">
						<p class="metric-label"><?php echo esc_html__('Top Users', 'apollo-events-manager'); ?></p>
						<p class="metric-value"><?php echo esc_html(count($top_users)); ?></p>
					</div>
				</div>
			</div>

			<!-- Dashboard Grid: Main Content + Widgets -->
			<div class="apollo-dashboard-grid">
				<!-- Main Content Column -->
				<div class="apollo-dashboard-main">

					<!-- Top Events Table -->
					<div class="apollo-dashboard-section">
						<div class="apollo-section-header">
							<h2>
								<i class="ri-bar-chart-line"></i>
								<?php echo esc_html__('Top Events by Views', 'apollo-events-manager'); ?>
							</h2>
						</div>
						<div class="apollo-table-wrapper">
							<table class="apollo-table">
								<thead>
									<tr>
										<th><?php echo esc_html__('ID', 'apollo-events-manager'); ?></th>
										<th><?php echo esc_html__('Event Title', 'apollo-events-manager'); ?></th>
										<th><?php echo esc_html__('Views', 'apollo-events-manager'); ?></th>
										<th><?php echo esc_html__('Actions', 'apollo-events-manager'); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php if (! empty($stats['top_events_by_views'])) : ?>
										<?php foreach ($stats['top_events_by_views'] as $event) : ?>
											<tr>
												<td><span class="apollo-badge apollo-badge-secondary"><?php echo esc_html($event['id']); ?></span></td>
												<td><strong><?php echo esc_html($event['title']); ?></strong></td>
												<td>
													<span class="apollo-badge apollo-badge-primary">
														<i class="ri-eye-line"></i> <?php echo esc_html(number_format_i18n($event['views'])); ?>
													</span>
												</td>
												<td>
													<a href="<?php echo esc_url($event['permalink']); ?>"
														target="_blank"
														class="apollo-btn apollo-btn-sm apollo-btn-link"
														title="<?php esc_attr_e('View Event', 'apollo-events-manager'); ?>">
														<i class="ri-external-link-line"></i>
													</a>
													<a href="<?php echo esc_url(admin_url('post.php?post=' . $event['id'] . '&action=edit')); ?>"
														class="apollo-btn apollo-btn-sm apollo-btn-link"
														title="<?php esc_attr_e('Edit Event', 'apollo-events-manager'); ?>">
														<i class="ri-edit-line"></i>
													</a>
												</td>
											</tr>
										<?php endforeach; ?>
									<?php else : ?>
										<tr>
											<td colspan="4" class="apollo-empty-state">
												<i class="ri-inbox-line"></i>
												<?php echo esc_html__('No events with views yet.', 'apollo-events-manager'); ?>
											</td>
										</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>

					<!-- Top Sounds & Locations (Side by Side) -->
					<div class="apollo-dashboard-section-grid">
						<div class="apollo-dashboard-section">
							<div class="apollo-section-header">
								<h2>
									<i class="ri-music-2-line"></i>
									<?php echo esc_html__('Top Sounds', 'apollo-events-manager'); ?>
								</h2>
							</div>
							<div class="apollo-table-wrapper">
								<table class="apollo-table">
									<thead>
										<tr>
											<th><?php echo esc_html__('Sound', 'apollo-events-manager'); ?></th>
											<th><?php echo esc_html__('Count', 'apollo-events-manager'); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php if (! empty($stats['top_sounds'])) : ?>
											<?php foreach ($stats['top_sounds'] as $sound) : ?>
												<tr>
													<td><strong><?php echo esc_html($sound['name']); ?></strong></td>
													<td><span class="apollo-badge"><?php echo esc_html(number_format_i18n($sound['count'])); ?></span></td>
												</tr>
											<?php endforeach; ?>
										<?php else : ?>
											<tr>
												<td colspan="2" class="apollo-empty-state">
													<?php echo esc_html__('No sounds found.', 'apollo-events-manager'); ?>
												</td>
											</tr>
										<?php endif; ?>
									</tbody>
								</table>
							</div>
						</div>

						<div class="apollo-dashboard-section">
							<div class="apollo-section-header">
								<h2>
									<i class="ri-map-pin-2-line"></i>
									<?php echo esc_html__('Top Locations', 'apollo-events-manager'); ?>
								</h2>
							</div>
							<div class="apollo-table-wrapper">
								<table class="apollo-table">
									<thead>
										<tr>
											<th><?php echo esc_html__('Location', 'apollo-events-manager'); ?></th>
											<th><?php echo esc_html__('Count', 'apollo-events-manager'); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php if (! empty($stats['top_locations'])) : ?>
											<?php foreach ($stats['top_locations'] as $location) : ?>
												<tr>
													<td><strong><?php echo esc_html($location['name']); ?></strong></td>
													<td><span class="apollo-badge"><?php echo esc_html(number_format_i18n($location['count'])); ?></span></td>
												</tr>
											<?php endforeach; ?>
										<?php else : ?>
											<tr>
												<td colspan="2" class="apollo-empty-state">
													<?php echo esc_html__('No locations found.', 'apollo-events-manager'); ?>
												</td>
											</tr>
										<?php endif; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>

					<!-- Top Users Table -->
					<div class="apollo-dashboard-section">
						<div class="apollo-section-header">
							<h2>
								<i class="ri-user-star-line"></i>
								<?php echo esc_html__('Top Users by Interactions', 'apollo-events-manager'); ?>
							</h2>
						</div>
						<div class="apollo-table-wrapper">
							<table class="apollo-table">
								<thead>
									<tr>
										<th><?php echo esc_html__('User', 'apollo-events-manager'); ?></th>
										<th><?php echo esc_html__('Gestão', 'apollo-events-manager'); ?></th>
										<th><?php echo esc_html__('Favorited', 'apollo-events-manager'); ?></th>
										<th><?php echo esc_html__('Total', 'apollo-events-manager'); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php if (! empty($top_users)) : ?>
										<?php
                                        foreach ($top_users as $user_data) :
                                            $user_badge = function_exists('apollo_get_role_badge') ? apollo_get_role_badge($user_data['id']) : '';
                                            ?>
											<tr>
												<td>
													<div class="apollo-user-cell">
														<?php echo wp_kses_post($user_badge); ?>
														<div>
															<strong><?php echo esc_html($user_data['name']); ?></strong>
															<small><?php echo esc_html($user_data['email']); ?></small>
														</div>
													</div>
												</td>
												<td><span class="apollo-badge"><?php echo esc_html(number_format_i18n($user_data['gestao_count'])); ?></span></td>
												<td><span class="apollo-badge"><?php echo esc_html(number_format_i18n($user_data['favorited_count'])); ?></span></td>
												<td><span class="apollo-badge apollo-badge-primary"><?php echo esc_html(number_format_i18n($user_data['total_interactions'])); ?></span></td>
											</tr>
										<?php endforeach; ?>
									<?php else : ?>
										<tr>
											<td colspan="4" class="apollo-empty-state">
												<?php echo esc_html__('No user interactions found.', 'apollo-events-manager'); ?>
											</td>
										</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>

				<!-- Sidebar Widgets Column -->
				<div class="apollo-dashboard-sidebar">
					<?php apollo_render_reminders_widget(); ?>
					<?php apollo_render_personal_todo_widget(); ?>
					<?php apollo_render_nucleo_todo_widget(); ?>
					<?php apollo_render_pre_save_date_calendar_widget(); ?>
				</div>
			</div>
		</div>

		<style>
			/* Apollo Dashboard Styles (shadcn-inspired) */
			.apollo-dashboard-wrap {
				max-width: 1400px;
				margin: 0;
				padding: 20px;
			}

			.apollo-dashboard-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 32px;
				padding-bottom: 20px;
				border-bottom: 2px solid var(--border-color, #e2e8f0);
			}

			.apollo-dashboard-title {
				font-size: 2rem;
				font-weight: 700;
				margin: 0 0 8px 0;
			}

			.apollo-dashboard-subtitle {
				color: var(--text-secondary, #666);
				margin: 0;
			}

			.apollo-dashboard-user-info {
				display: flex;
				align-items: center;
				gap: 12px;
			}

			.apollo-user-name {
				font-weight: 500;
			}

			.apollo-metrics-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
				gap: 20px;
				margin-bottom: 32px;
			}

			.apollo-metric-card {
				display: flex;
				align-items: center;
				gap: 16px;
				padding: 20px;
				background: var(--bg-primary, #fff);
				border: 2px solid var(--border-color, #e2e8f0);
				border-radius: 12px;
				transition: all 0.2s ease;
			}

			.apollo-metric-card:hover {
				border-color: var(--primary-color, #0078d4);
				box-shadow: 0 4px 12px rgba(0, 120, 212, 0.1);
			}

			.metric-icon {
				width: 48px;
				height: 48px;
				display: flex;
				align-items: center;
				justify-content: center;
				background: var(--bg-secondary, #f5f5f5);
				border-radius: 8px;
				font-size: 24px;
				color: var(--primary-color, #0078d4);
			}

			.metric-content {
				flex: 1;
			}

			.metric-label {
				margin: 0 0 4px 0;
				font-size: 0.875rem;
				color: var(--text-secondary, #666);
			}

			.metric-value {
				margin: 0;
				font-size: 1.75rem;
				font-weight: 700;
				color: var(--text-primary, #1a1a1a);
			}

			.apollo-dashboard-grid {
				display: grid;
				grid-template-columns: 2fr 1fr;
				gap: 24px;
			}

			.apollo-dashboard-main {
				display: flex;
				flex-direction: column;
				gap: 24px;
			}

			.apollo-dashboard-sidebar {
				display: flex;
				flex-direction: column;
				gap: 20px;
			}

			.apollo-dashboard-section {
				background: var(--bg-primary, #fff);
				border: 2px solid var(--border-color, #e2e8f0);
				border-radius: 12px;
				padding: 20px;
			}

			.apollo-dashboard-section-grid {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 20px;
			}

			.apollo-section-header {
				margin-bottom: 16px;
				padding-bottom: 12px;
				border-bottom: 1px solid var(--border-color, #e2e8f0);
			}

			.apollo-section-header h2 {
				margin: 0;
				font-size: 1.25rem;
				font-weight: 600;
				display: flex;
				align-items: center;
				gap: 8px;
			}

			.apollo-table-wrapper {
				overflow-x: auto;
			}

			.apollo-table {
				width: 100%;
				border-collapse: collapse;
			}

			.apollo-table thead {
				background: var(--bg-secondary, #f5f5f5);
			}

			.apollo-table th {
				padding: 12px;
				text-align: left;
				font-weight: 600;
				font-size: 0.875rem;
				text-transform: uppercase;
				letter-spacing: 0.5px;
			}

			.apollo-table td {
				padding: 12px;
				border-top: 1px solid var(--border-color, #e2e8f0);
			}

			.apollo-table tbody tr:hover {
				background: var(--bg-secondary, #f5f5f5);
			}

			.apollo-badge {
				display: inline-flex;
				align-items: center;
				gap: 4px;
				padding: 4px 12px;
				border-radius: 12px;
				font-size: 0.75rem;
				font-weight: 600;
				background: var(--bg-secondary, #f5f5f5);
				color: var(--text-primary, #1a1a1a);
			}

			.apollo-badge-primary {
				background: var(--primary-color, #0078d4);
				color: #fff;
			}

			.apollo-badge-secondary {
				background: var(--bg-secondary, #f5f5f5);
				color: var(--text-secondary, #666);
			}

			.apollo-btn {
				display: inline-flex;
				align-items: center;
				gap: 6px;
				padding: 6px 12px;
				border: 1px solid var(--border-color, #e2e8f0);
				background: var(--bg-primary, #fff);
				border-radius: 6px;
				text-decoration: none;
				font-size: 0.875rem;
				transition: all 0.2s ease;
			}

			.apollo-btn:hover {
				background: var(--bg-secondary, #f5f5f5);
			}

			.apollo-btn-sm {
				padding: 4px 8px;
				font-size: 0.75rem;
			}

			.apollo-btn-link {
				border: none;
				background: transparent;
			}

			.apollo-user-cell {
				display: flex;
				align-items: center;
				gap: 8px;
			}

			.apollo-user-cell small {
				display: block;
				font-size: 0.75rem;
				color: var(--text-secondary, #666);
			}

			.apollo-empty-state {
				text-align: center;
				padding: 40px 20px;
				color: var(--text-secondary, #666);
			}

			.apollo-empty-state i {
				font-size: 2rem;
				display: block;
				margin-bottom: 8px;
				opacity: 0.5;
			}

			/* Dashboard Widgets */
			.apollo-dashboard-widget {
				background: var(--bg-primary, #fff);
				border: 2px solid var(--border-color, #e2e8f0);
				border-radius: 12px;
				overflow: hidden;
			}

			.apollo-widget-header {
				padding: 16px;
				background: var(--bg-secondary, #f5f5f5);
				border-bottom: 1px solid var(--border-color, #e2e8f0);
			}

			.apollo-widget-header h3 {
				margin: 0;
				font-size: 1rem;
				font-weight: 600;
				display: flex;
				align-items: center;
				gap: 8px;
			}

			.apollo-widget-body {
				padding: 16px;
			}

			.apollo-reminders-list,
			.apollo-todo-list,
			.apollo-calendar-list {
				list-style: none;
				margin: 0;
				padding: 0;
			}

			.apollo-reminder-item,
			.apollo-todo-item,
			.apollo-calendar-item {
				padding: 12px 0;
				border-bottom: 1px solid var(--border-color, #e2e8f0);
			}

			.apollo-reminder-item:last-child,
			.apollo-todo-item:last-child,
			.apollo-calendar-item:last-child {
				border-bottom: none;
			}

			.reminder-content {
				display: flex;
				flex-direction: column;
				gap: 4px;
			}

			.reminder-date {
				font-size: 0.75rem;
				color: var(--text-secondary, #666);
				display: flex;
				align-items: center;
				gap: 4px;
			}

			.todo-checkbox {
				display: flex;
				align-items: center;
				gap: 8px;
				cursor: pointer;
			}

			.todo-text {
				flex: 1;
			}

			.calendar-item {
				display: flex;
				align-items: center;
				gap: 12px;
			}

			.calendar-date {
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				width: 48px;
				height: 48px;
				background: var(--bg-secondary, #f5f5f5);
				border-radius: 8px;
				flex-shrink: 0;
			}

			.date-day {
				font-size: 1.25rem;
				font-weight: 700;
				line-height: 1;
			}

			.date-month {
				font-size: 0.75rem;
				text-transform: uppercase;
				color: var(--text-secondary, #666);
			}

			.calendar-content {
				flex: 1;
				display: flex;
				flex-direction: column;
				gap: 4px;
			}

			.calendar-date-full {
				font-size: 0.75rem;
				color: var(--text-secondary, #666);
			}

			@media (max-width: 1200px) {
				.apollo-dashboard-grid {
					grid-template-columns: 1fr;
				}
			}
		</style>
		<?php
    }

    /**
     * Render user overview page
     */
    public function render_user_overview()
    {
        // Allow manage_options as fallback for admin
        if (! current_user_can('view_apollo_event_stats') && ! current_user_can('manage_options')) {
            wp_die(__('You do not have permission to view this page.', 'apollo-events-manager'));
        }

        if (! function_exists('apollo_get_user_event_stats')) {
            // Try to load analytics file
            $analytics_file = plugin_dir_path(__FILE__) . 'includes/class-apollo-events-analytics.php';
            if (file_exists($analytics_file)) {
                require_once $analytics_file;
            }

            if (! function_exists('apollo_get_user_event_stats')) {
                echo '<div class="wrap"><p>' . esc_html__('Analytics system not loaded.', 'apollo-events-manager') . '</p></div>';
                return;
            }
        }

        // Get user ID from request or use current user
        $target_user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : get_current_user_id();
        $user           = get_user_by('id', $target_user_id);

        if (! $user) {
            $target_user_id = get_current_user_id();
            $user           = get_user_by('id', $target_user_id);
        }

        $stats = apollo_get_user_event_stats($target_user_id);
        ?>
		<div class="wrap">
			<h1><?php echo esc_html__('Apollo Events – User Overview', 'apollo-events-manager'); ?></h1>

			<form method="get" style="margin: 20px 0;">
				<input type="hidden" name="page" value="apollo-events-user-overview">
				<label for="user_id"><?php echo esc_html__('Select User:', 'apollo-events-manager'); ?></label>
				<select name="user_id" id="user_id">
					<?php
                    $users = get_users([ 'number' => 100 ]);
        foreach ($users as $u) {
            $selected = ($u->ID == $target_user_id) ? 'selected' : '';
            echo '<option value="' . esc_attr($u->ID) . '" ' . $selected . '>' . esc_html($u->display_name . ' (' . $u->user_email . ')') . '</option>';
        }
        ?>
				</select>
				<button type="submit" class="button"><?php echo esc_html__('Load User Stats', 'apollo-events-manager'); ?></button>
			</form>

			<h2><?php echo esc_html__('User Statistics', 'apollo-events-manager'); ?></h2>
			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
				<div class="card">
					<h3><?php echo esc_html__('Eventos em Gestão', 'apollo-events-manager'); ?></h3>
					<p style="font-size: 32px; font-weight: bold; margin: 0;"><?php echo esc_html(number_format_i18n($stats['gestao_count'])); ?></p>
				</div>
				<div class="card">
					<h3><?php echo esc_html__('Favorited Events', 'apollo-events-manager'); ?></h3>
					<p style="font-size: 32px; font-weight: bold; margin: 0;"><?php echo esc_html(number_format_i18n($stats['favorited_count'])); ?></p>
				</div>
			</div>

			<?php if (! empty($stats['sounds_distribution'])) : ?>
				<h2><?php echo esc_html__('Sounds Distribution', 'apollo-events-manager'); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width: 40%;"><?php echo esc_html__('Sound', 'apollo-events-manager'); ?></th>
							<th style="width: 20%;"><?php echo esc_html__('Count', 'apollo-events-manager'); ?></th>
							<th style="width: 40%;"><?php echo esc_html__('Percentage', 'apollo-events-manager'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($stats['sounds_distribution'] as $sound) : ?>
							<tr>
								<td><strong><?php echo esc_html($sound['name']); ?></strong></td>
								<td><?php echo esc_html(number_format_i18n($sound['count'])); ?></td>
								<td>
									<div style="display: flex; align-items: center; gap: 10px;">
										<div style="flex: 1; background: #f0f0f0; height: 20px; border-radius: 3px; overflow: hidden;">
											<div style="background: #0073aa; height: 100%; width: <?php echo esc_attr($sound['percentage']); ?>%;"></div>
										</div>
										<span><strong><?php echo esc_html($sound['percentage']); ?>%</strong></span>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if (! empty($stats['locations_distribution'])) : ?>
				<h2><?php echo esc_html__('Locations Distribution', 'apollo-events-manager'); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width: 40%;"><?php echo esc_html__('Location', 'apollo-events-manager'); ?></th>
							<th style="width: 20%;"><?php echo esc_html__('Count', 'apollo-events-manager'); ?></th>
							<th style="width: 40%;"><?php echo esc_html__('Percentage', 'apollo-events-manager'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($stats['locations_distribution'] as $location) : ?>
							<tr>
								<td><strong><?php echo esc_html($location['name']); ?></strong></td>
								<td><?php echo esc_html(number_format_i18n($location['count'])); ?></td>
								<td>
									<div style="display: flex; align-items: center; gap: 10px;">
										<div style="flex: 1; background: #f0f0f0; height: 20px; border-radius: 3px; overflow: hidden;">
											<div style="background: #0073aa; height: 100%; width: <?php echo esc_attr($location['percentage']); ?>%;"></div>
										</div>
										<span><strong><?php echo esc_html($location['percentage']); ?>%</strong></span>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
    }

    /**
     * Front-end user overview shortcode
     */
    public function apollo_event_user_overview_shortcode($atts)
    {
        if (! is_user_logged_in()) {
            return '<p>' . esc_html__('This content is only available for logged-in users.', 'apollo-events-manager') . '</p>';
        }

        if (! function_exists('apollo_get_user_event_stats')) {
            return '';
        }

        $user_id = get_current_user_id();
        $stats   = apollo_get_user_event_stats($user_id);

        ob_start();
        ?>
		<div class="apollo-user-overview">
			<h3><?php echo esc_html__('My Event Statistics', 'apollo-events-manager'); ?></h3>

			<div class="apollo-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;">
				<div class="apollo-stat-card" style="background: #f5f5f5; padding: 15px; border-radius: 5px;">
					<strong><?php echo esc_html__('Eventos em Gestão', 'apollo-events-manager'); ?></strong>
					<p style="font-size: 24px; margin: 10px 0 0 0; font-weight: bold;"><?php echo esc_html(number_format_i18n($stats['gestao_count'])); ?></p>
				</div>
				<div class="apollo-stat-card" style="background: #f5f5f5; padding: 15px; border-radius: 5px;">
					<strong><?php echo esc_html__('Favorited Events', 'apollo-events-manager'); ?></strong>
					<p style="font-size: 24px; margin: 10px 0 0 0; font-weight: bold;"><?php echo esc_html(number_format_i18n($stats['favorited_count'])); ?></p>
				</div>
			</div>

			<?php if (! empty($stats['sounds_distribution'])) : ?>
				<h4><?php echo esc_html__('My Top Sounds', 'apollo-events-manager'); ?></h4>
				<ul style="list-style: none; padding: 0;">
					<?php foreach (array_slice($stats['sounds_distribution'], 0, 5) as $sound) : ?>
						<li style="padding: 10px; background: #f9f9f9; margin: 5px 0; border-radius: 3px;">
							<strong><?php echo esc_html($sound['name']); ?></strong>
							<span style="color: #666;">(<?php echo esc_html($sound['count']); ?> eventos, <?php echo esc_html($sound['percentage']); ?>%)</span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if (! empty($stats['locations_distribution'])) : ?>
				<h4><?php echo esc_html__('My Top Locations', 'apollo-events-manager'); ?></h4>
				<ul style="list-style: none; padding: 0;">
					<?php foreach (array_slice($stats['locations_distribution'], 0, 5) as $location) : ?>
						<li style="padding: 10px; background: #f9f9f9; margin: 5px 0; border-radius: 3px;">
							<strong><?php echo esc_html($location['name']); ?></strong>
							<span style="color: #666;">(<?php echo esc_html($location['count']); ?> eventos, <?php echo esc_html($location['percentage']); ?>%)</span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
        return ob_get_clean();
    }

    /**
     * Shortcode: [event id="123"] - Full event content for lightbox
     */
    public function event_single_shortcode($atts)
    {
        $atts = shortcode_atts(
            [
                'id' => 0,
            ],
            $atts,
            'event'
        );

        $event_id = $atts['id'] ? absint($atts['id']) : get_the_ID();
        if (! $event_id) {
            return '<p>' . esc_html__('Event ID not provided.', 'apollo-events-manager') . '</p>';
        }

        $event = get_post($event_id);
        if (! $event || $event->post_type !== 'event_listing' || $event->post_status !== 'publish') {
            return '<p>' . esc_html__('Event not found.', 'apollo-events-manager') . '</p>';
        }

        // Setup post data
        global $post;
        $original_post = $post;
        $post          = $event;
        setup_postdata($post);

        // Get event data
        $start_date_raw = apollo_get_post_meta($event_id, '_event_start_date', true);
        $date_info      = apollo_eve_parse_start_date($start_date_raw);
        $banner         = apollo_get_post_meta($event_id, '_event_banner', true);
        $video_url      = apollo_get_post_meta($event_id, '_event_video_url', true);
        $tickets_url    = apollo_get_post_meta($event_id, '_tickets_ext', true);

        // Get DJs
        $dj_list = apollo_event_get_placeholder_value('dj_list', $event_id);

        // Get Location
        $location      = apollo_event_get_placeholder_value('location', $event_id);
        $location_area = apollo_event_get_placeholder_value('location_area', $event_id);

        // Get Banner URL
        $banner_url = apollo_event_get_placeholder_value('banner_url', $event_id);

        ob_start();
        ?>
		<div class="apollo-event-lightbox-content">
			<div class="apollo-event-hero">
				<div class="apollo-event-hero-media">
					<?php if ($banner_url) : ?>
						<img src="<?php echo esc_url($banner_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy">
					<?php endif; ?>

					<?php if ($date_info['day'] && $date_info['month_pt']) : ?>
						<div class="apollo-event-date-chip">
							<span class="d"><?php echo esc_html($date_info['day']); ?></span>
							<span class="m"><?php echo esc_html($date_info['month_pt']); ?></span>
						</div>
					<?php endif; ?>
				</div>

				<div class="apollo-event-hero-info">
					<h1 class="apollo-event-title"><?php echo esc_html(get_the_title()); ?></h1>

					<?php if ($dj_list) : ?>
						<p class="apollo-event-djs">
							<i class="ri-sound-module-fill"></i>
							<span><?php echo wp_kses_post($dj_list); ?></span>
						</p>
					<?php endif; ?>

					<?php if ($location) : ?>
						<p class="apollo-event-location">
							<i class="ri-map-pin-2-line"></i>
							<span><?php echo esc_html($location); ?></span>
							<?php if ($location_area) : ?>
								<span style="opacity: 0.5;">&nbsp;(<?php echo esc_html($location_area); ?>)</span>
							<?php endif; ?>
						</p>
					<?php endif; ?>

					<?php if ($date_info['iso_date']) : ?>
						<p class="apollo-event-date">
							<i class="ri-calendar-event-line"></i>
							<span><?php echo esc_html(date_i18n('l, F j, Y', strtotime($date_info['iso_date']))); ?></span>
						</p>
					<?php endif; ?>
				</div>
			</div>

			<div class="apollo-event-body">
				<?php echo apply_filters('the_content', get_the_content()); ?>

				<?php if ($video_url) : ?>
					<div class="apollo-event-video" style="margin: 20px 0;">
						<?php
                        // Simple YouTube embed detection
                        if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $video_url, $matches);
                            if (! empty($matches[1])) {
                                echo '<div class="video-wrapper" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; background: #000;">';
                                echo '<iframe src="https://www.youtube.com/embed/' . esc_attr($matches[1]) . '" frameborder="0" allowfullscreen style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></iframe>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p><a href="' . esc_url($video_url) . '" target="_blank" rel="noopener">' . esc_html__('Watch Video', 'apollo-events-manager') . '</a></p>';
                        }
        ?>
					</div>
				<?php endif; ?>

				<?php if ($tickets_url) : ?>
					<div class="apollo-event-tickets" style="margin: 20px 0;">
						<a href="<?php echo esc_url($tickets_url); ?>" target="_blank" rel="noopener" class="button button-primary" style="display: inline-block; padding: 12px 24px; background: #0078d4; color: #fff; text-decoration: none; border-radius: 4px;">
							<?php echo esc_html__('Buy Tickets', 'apollo-events-manager'); ?>
							<i class="ri-external-link-line"></i>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php

        // Restore original post
        $post = $original_post;
        wp_reset_postdata();

        return ob_get_clean();
    }

    /**
     * Shortcode: [event_djs] - Lista de DJs
     */
    public function event_djs_shortcode($atts)
    {
        $atts = shortcode_atts(
            [
                'limit'   => -1,
                'orderby' => 'title',
                'order'   => 'ASC',
            ],
            $atts,
            'event_djs'
        );

        $args = [
            'post_type'      => 'event_dj',
            'posts_per_page' => absint($atts['limit']),
            'orderby'        => $atts['orderby'],
            'order'          => $atts['order'],
            'post_status'    => 'publish',
        ];

        $djs = get_posts($args);

        if (empty($djs)) {
            return '<p>' . esc_html__('No DJs found.', 'apollo-events-manager') . '</p>';
        }

        ob_start();
        echo '<div class="apollo-djs-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">';
        foreach ($djs as $dj) {
            $dj_name  = apollo_get_post_meta($dj->ID, '_dj_name', true) ?: $dj->post_title;
            $dj_image = apollo_get_post_meta($dj->ID, '_dj_image', true);
            $dj_bio   = apollo_get_post_meta($dj->ID, '_dj_bio', true);

            echo '<div class="dj-card glass" style="padding: 20px; border-radius: 8px;">';
            if ($dj_image) {
                $image_url = filter_var($dj_image, FILTER_VALIDATE_URL) ? $dj_image : wp_get_attachment_url($dj_image);
                if ($image_url) {
                    echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($dj_name) . '" style="width: 100%; border-radius: 4px; margin-bottom: 10px;">';
                }
            }
            echo '<h3 style="margin: 10px 0;">' . esc_html($dj_name) . '</h3>';
            if ($dj_bio) {
                echo '<p style="color: var(--text-main, #666); font-size: 0.9em;">' . esc_html(wp_trim_words($dj_bio, 20)) . '</p>';
            }
            echo '<a href="' . get_permalink($dj->ID) . '" class="button" style="margin-top: 10px;">Ver Perfil</a>';
            echo '</div>';
        }
        echo '</div>';

        return ob_get_clean();
    }

    /**
     * FASE 2: Render event submission form
     * Shortcode oficial: [apollo_event_submit]
     * Gateway único para submissão de eventos
     */
    public function render_submit_form($atts = [])
    {
        // Carregar helper se necessário
        if (! class_exists('Apollo_Event_Data_Helper')) {
            require_once APOLLO_APRIO_PATH . 'includes/helpers/event-data-helper.php';
        }

        // FASE 2: Verificar se usuário está logado
        if (! is_user_logged_in()) {
            $login_url    = wp_login_url(get_permalink());
            $register_url = wp_registration_url();

            return '<div class="aem-auth-required glass p-6 rounded-lg text-center">
                <h3 class="text-xl font-semibold mb-4">' . esc_html__('Login Necessário', 'apollo-events-manager') . '</h3>
                <p class="mb-4">' . esc_html__('Você precisa estar logado para enviar um evento.', 'apollo-events-manager') . '</p>
                <div class="flex gap-4 justify-center">
                    <a href="' . esc_url($login_url) . '" class="btn btn-primary">' . esc_html__('Entrar', 'apollo-events-manager') . '</a>
                    <a href="' . esc_url($register_url) . '" class="btn btn-secondary">' . esc_html__('Criar Conta', 'apollo-events-manager') . '</a>
                </div>
            </div>';
        }

        // FASE 2: Verificar capabilities
        $current_user_id = get_current_user_id();
        if (! $current_user_id || ! current_user_can('edit_posts')) {
            return '<div class="aem-error glass p-4 rounded-lg bg-red-50 border border-red-200">
                <p class="text-red-700">' . esc_html__('Você não tem permissão para criar eventos.', 'apollo-events-manager') . '</p>
            </div>';
        }

        // FASE 2: Processar submissão do formulário
        $event_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        $is_edit  = $event_id > 0;

        // FASE 2: Validar permissões de edição usando helper
        if ($is_edit) {
            $event = get_post($event_id);
            if (! $event || $event->post_type !== 'event_listing') {
                return '<div class="aem-error glass p-4 rounded-lg bg-red-50 border border-red-200">
                    <p class="text-red-700">' . esc_html__('Evento não encontrado.', 'apollo-events-manager') . '</p>
                </div>';
            }

            // FASE 2: Usar função helper para verificar permissões
            if (! function_exists('apollo_can_user_edit_event')) {
                require_once APOLLO_APRIO_PATH . 'includes/helpers/event-data-helper.php';
            }

            if (! apollo_can_user_edit_event($event_id, $current_user_id)) {
                return '<div class="aem-error glass p-4 rounded-lg bg-red-50 border border-red-200">
                    <p class="text-red-700">' . esc_html__('Você não tem permissão para editar este evento.', 'apollo-events-manager') . '</p>
                </div>';
            }
        }

        // Processar formulário se foi submetido
        if (! empty($_POST['aem_submit']) && isset($_POST['apollo_submit_event_nonce']) && wp_verify_nonce($_POST['apollo_submit_event_nonce'], 'apollo_submit_event')) {
            return $this->process_event_submission($event_id);
        }

        // Renderizar formulário
        return $this->render_submit_form_html($event_id);
    }

    /**
     * FASE 2: Processar submissão do formulário
     * Validação rigorosa e sanitização
     */
    private function process_event_submission($event_id = 0)
    {
        $current_user_id = get_current_user_id();
        $errors          = [];

        // FASE 2: Sanitizar e validar campos obrigatórios
        $title      = isset($_POST['post_title']) ? sanitize_text_field(wp_unslash($_POST['post_title'])) : '';
        $content    = isset($_POST['post_content']) ? wp_kses_post(wp_unslash($_POST['post_content'])) : '';
        $start_date = isset($_POST['event_start_date']) ? sanitize_text_field(wp_unslash($_POST['event_start_date'])) : '';
        $start_time = isset($_POST['event_start_time']) ? sanitize_text_field(wp_unslash($_POST['event_start_time'])) : '';
        $end_date   = isset($_POST['event_end_date']) ? sanitize_text_field(wp_unslash($_POST['event_end_date'])) : '';
        $end_time   = isset($_POST['event_end_time']) ? sanitize_text_field(wp_unslash($_POST['event_end_time'])) : '';
        $local_id   = isset($_POST['event_local']) ? absint($_POST['event_local']) : 0;
        $dj_ids     = isset($_POST['event_djs'])    && is_array($_POST['event_djs']) ? array_map('absint', $_POST['event_djs']) : [];
        $sounds     = isset($_POST['event_sounds']) && is_array($_POST['event_sounds']) ? array_map('sanitize_text_field', $_POST['event_sounds']) : [];

        // FASE 2: Campos de links (sanitizados como URL)
        $links = [
            'instagram' => isset($_POST['event_link_instagram']) ? esc_url_raw(wp_unslash($_POST['event_link_instagram'])) : '',
            'ra'        => isset($_POST['event_link_ra']) ? esc_url_raw(wp_unslash($_POST['event_link_ra'])) : '',
            'sympla'    => isset($_POST['event_link_sympla']) ? esc_url_raw(wp_unslash($_POST['event_link_sympla'])) : '',
            'shotgun'   => isset($_POST['event_link_shotgun']) ? esc_url_raw(wp_unslash($_POST['event_link_shotgun'])) : '',
        ];

        // FASE 2: Co-autores (sanitizados)
        $gestao = isset($_POST['event_gestao']) && is_array($_POST['event_gestao']) ? array_map('absint', $_POST['event_gestao']) : [];

        // Validações
        if (empty($title)) {
            $errors[] = __('Título do evento é obrigatório.', 'apollo-events-manager');
        }

        if (empty($start_date)) {
            $errors[] = __('Data de início é obrigatória.', 'apollo-events-manager');
        }

        if (empty($local_id)) {
            $errors[] = __('Local é obrigatório.', 'apollo-events-manager');
        }

        if (! empty($errors)) {
            $error_html = '<div class="aem-error glass p-4 rounded-lg mb-4 bg-red-50 border border-red-200">
                <h4 class="font-semibold text-red-800 mb-2">' . esc_html__('Erros encontrados:', 'apollo-events-manager') . '</h4>
                <ul class="list-disc list-inside text-red-700">';
            foreach ($errors as $error) {
                $error_html .= '<li>' . esc_html($error) . '</li>';
            }
            $error_html .= '</ul></div>';

            // Retornar formulário com erros
            return $error_html . $this->render_submit_form_html($event_id);
        }

        try {
            // Combinar data e hora
            $start_datetime = $start_date;
            if (! empty($start_time)) {
                $start_datetime .= ' ' . $start_time . ':00';
            } else {
                $start_datetime .= ' 20:00:00';
            }

            // Criar ou atualizar evento
            $post_data = [
                'post_type'   => 'event_listing',
                'post_status' => 'pending',
                // FASE 2: Sempre requer moderação
                                    'post_title' => $title,
                'post_content'                   => $content,
                'post_author'                    => $current_user_id,
            ];

            if ($event_id > 0) {
                $post_data['ID'] = $event_id;
                $post_id         = wp_update_post($post_data, true);
            } else {
                $post_id = wp_insert_post($post_data, true);
            }

            if (is_wp_error($post_id)) {
                throw new Exception($post_id->get_error_message());
            }

            // FASE 2: Salvar meta keys usando apollo_update_post_meta
            apollo_update_post_meta($post_id, '_event_start_date', $start_datetime);
            if (! empty($start_time)) {
                apollo_update_post_meta($post_id, '_event_start_time', $start_time);
            }

            if (! empty($end_date)) {
                $end_datetime = $end_date;
                if (! empty($end_time)) {
                    $end_datetime .= ' ' . $end_time . ':00';
                }
                apollo_update_post_meta($post_id, '_event_end_date', $end_datetime);
                if (! empty($end_time)) {
                    apollo_update_post_meta($post_id, '_event_end_time', $end_time);
                }
            }

            // Local
            if ($local_id > 0) {
                if (class_exists('Apollo_Local_Connection')) {
                    $connection = Apollo_Local_Connection::get_instance();
                    $connection->set_local_id($post_id, $local_id);
                } else {
                    apollo_update_post_meta($post_id, '_event_local_ids', $local_id);
                }
            }

            // DJs
            if (! empty($dj_ids)) {
                apollo_update_post_meta($post_id, '_event_dj_ids', $dj_ids);
            }

            // FASE 2: Gestão
            if (! empty($gestao)) {
                apollo_update_post_meta($post_id, '_event_gestao', $gestao);
            }

            // FASE 2: Links
            foreach ($links as $key => $url) {
                if (! empty($url)) {
                    apollo_update_post_meta($post_id, '_event_link_' . $key, $url);
                }
            }

            // FASE 2: Estilos de som (taxonomia)
            if (! empty($sounds)) {
                wp_set_object_terms($post_id, $sounds, 'event_sounds', false);
            }

            // Timetable (auto-gerado se não fornecido)
            if (empty($_POST['apollo_event_timetable']) && ! empty($dj_ids)) {
                $auto_timetable = [];
                // Bug fix: DateTime precisa de data completa ou usar createFromFormat para apenas hora
                if (! empty($start_time)) {
                    // Se start_time já tem data completa, usar diretamente
                    if (strpos($start_time, ' ') !== false) {
                        $start_time_obj = new DateTime($start_time);
                    } else {
                        // Se é apenas hora, criar com data de hoje
                        $start_time_obj = DateTime::createFromFormat('H:i', $start_time);
                        if (! $start_time_obj) {
                            $start_time_obj = DateTime::createFromFormat('H:i:s', $start_time);
                        }
                        if (! $start_time_obj) {
                            // Fallback: usar hora padrão 20:00
                            $start_time_obj = DateTime::createFromFormat('H:i', '20:00');
                        }
                    }
                } else {
                    // Hora padrão: 20:00
                    $start_time_obj = DateTime::createFromFormat('H:i', '20:00');
                }

                foreach ($dj_ids as $index => $dj_id) {
                    $slot_time = clone $start_time_obj;
                    $slot_time->modify('+' . ($index * 60) . ' minutes');

                    $auto_timetable[] = [
                        'dj'   => $dj_id,
                        'from' => $slot_time->format('H:i'),
                        'to'   => $slot_time->modify('+60 minutes')->format('H:i'),
                    ];
                }

                if (! empty($auto_timetable)) {
                    apollo_update_post_meta($post_id, '_event_timetable', $auto_timetable);
                }
            } elseif (! empty($_POST['apollo_event_timetable'])) {
                $timetable_json = sanitize_text_field(wp_unslash($_POST['apollo_event_timetable']));
                $timetable      = json_decode(stripslashes($timetable_json), true);
                if (is_array($timetable) && function_exists('apollo_sanitize_timetable')) {
                    $clean_timetable = apollo_sanitize_timetable($timetable);
                    if (! empty($clean_timetable)) {
                        apollo_update_post_meta($post_id, '_event_timetable', $clean_timetable);
                    }
                }
            }//end if

            // Marcar como submissão frontend
            apollo_update_post_meta($post_id, '_apollo_frontend_submission', '1');
            apollo_update_post_meta($post_id, '_apollo_submission_date', current_time('mysql'));

            // FASE 1: Limpar cache
            if (class_exists('Apollo_Event_Data_Helper')) {
                Apollo_Event_Data_Helper::flush_events_cache();
            }

            $success_message = $event_id > 0
                ? __('Evento atualizado com sucesso! Está em revisão e será publicado em breve.', 'apollo-events-manager')
                : __('Evento enviado com sucesso! Obrigado! Seu evento está em revisão e será publicado em breve.', 'apollo-events-manager');

            return '<div class="aem-success glass p-6 rounded-lg text-center bg-green-50 border border-green-200">
                <h3 class="text-xl font-semibold text-green-800 mb-2">✓ ' . esc_html__('Sucesso!', 'apollo-events-manager') . '</h3>
                <p class="text-green-700 mb-4">' . esc_html($success_message) . '</p>
                <a href="' . esc_url(get_permalink()) . '" class="btn btn-primary">' . esc_html__('Enviar Outro Evento', 'apollo-events-manager') . '</a>
            </div>';
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Apollo Events: Error in submit form - ' . $e->getMessage());
            }

            $error_html = '<div class="aem-error glass p-4 rounded-lg mb-4 bg-red-50 border border-red-200">
                <p class="text-red-700">' . esc_html__('Erro ao criar evento:', 'apollo-events-manager') . ' ' . esc_html($e->getMessage()) . '</p>
                <p class="text-sm text-red-600 mt-2">' . esc_html__('Tente novamente ou entre em contato com o suporte.', 'apollo-events-manager') . '</p>
            </div>';

            return $error_html . $this->render_submit_form_html($event_id);
        }//end try
    }

    /**
     * FASE 2: Renderizar HTML do formulário
     * Campos mínimos conforme especificação
     */
    private function render_submit_form_html($event_id = 0)
    {
        $is_edit = $event_id > 0;
        $event   = $is_edit ? get_post($event_id) : null;

        // Carregar dados existentes se editando
        $title      = $is_edit ? $event->post_title : (isset($_POST['post_title']) ? sanitize_text_field($_POST['post_title']) : '');
        $content    = $is_edit ? $event->post_content : (isset($_POST['post_content']) ? wp_kses_post($_POST['post_content']) : '');
        $start_date = $is_edit ? apollo_get_post_meta($event_id, '_event_start_date', true) : (isset($_POST['event_start_date']) ? sanitize_text_field($_POST['event_start_date']) : '');
        $start_time = $is_edit ? apollo_get_post_meta($event_id, '_event_start_time', true) : (isset($_POST['event_start_time']) ? sanitize_text_field($_POST['event_start_time']) : '20:00');
        $end_date   = $is_edit ? apollo_get_post_meta($event_id, '_event_end_date', true) : (isset($_POST['event_end_date']) ? sanitize_text_field($_POST['event_end_date']) : '');
        $end_time   = $is_edit ? apollo_get_post_meta($event_id, '_event_end_time', true) : (isset($_POST['event_end_time']) ? sanitize_text_field($_POST['event_end_time']) : '');
        $local_id   = $is_edit ? apollo_get_post_meta($event_id, '_event_local_ids', true) : (isset($_POST['event_local']) ? absint($_POST['event_local']) : 0);
        $dj_ids     = $is_edit ? apollo_get_post_meta($event_id, '_event_dj_ids', true) : (isset($_POST['event_djs']) ? array_map('absint', $_POST['event_djs']) : []);
        $dj_ids     = is_array($dj_ids) ? $dj_ids : [];
        $gestao     = $is_edit ? apollo_get_post_meta($event_id, '_event_gestao', true) : (isset($_POST['event_gestao']) ? array_map('absint', $_POST['event_gestao']) : []);
        $gestao     = is_array($gestao) ? $gestao : [];

        // Links
        $links = [
            'instagram' => $is_edit ? apollo_get_post_meta($event_id, '_event_link_instagram', true) : (isset($_POST['event_link_instagram']) ? esc_url_raw($_POST['event_link_instagram']) : ''),
            'ra'        => $is_edit ? apollo_get_post_meta($event_id, '_event_link_ra', true) : (isset($_POST['event_link_ra']) ? esc_url_raw($_POST['event_link_ra']) : ''),
            'sympla'    => $is_edit ? apollo_get_post_meta($event_id, '_event_link_sympla', true) : (isset($_POST['event_link_sympla']) ? esc_url_raw($_POST['event_link_sympla']) : ''),
            'shotgun'   => $is_edit ? apollo_get_post_meta($event_id, '_event_link_shotgun', true) : (isset($_POST['event_link_shotgun']) ? esc_url_raw($_POST['event_link_shotgun']) : ''),
        ];

        // Estilos de som (taxonomia)
        $sounds_terms = $is_edit ? wp_get_post_terms($event_id, 'event_sounds', [ 'fields' => 'slugs' ]) : (isset($_POST['event_sounds']) ? array_map('sanitize_text_field', $_POST['event_sounds']) : []);

        // Buscar DJs e Locais para dropdowns
        $all_djs = get_posts(
            [
                'post_type'      => 'event_dj',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
                'post_status'    => 'publish',
            ]
        );

        $all_locals = get_posts(
            [
                'post_type'      => 'event_local',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
                'post_status'    => 'publish',
            ]
        );

        // Buscar usuários para co-autores
        $all_users = get_users(
            [
                'orderby' => 'display_name',
                'order'   => 'ASC',
                'number'  => 500,
            // Limitar para performance
            ]
        );

        // Buscar termos de estilos de som
        $all_sounds = get_terms(
            [
                'taxonomy'   => 'event_sounds',
                'hide_empty' => false,
            ]
        );
        $all_sounds = is_wp_error($all_sounds) ? [] : $all_sounds;

        ob_start();
        ?>
		<div class="aem-submit-form-wrapper glass p-6 rounded-lg max-w-4xl mx-auto">
			<h2 class="text-2xl font-bold mb-6"><?php echo $is_edit ? esc_html__('Editar Evento', 'apollo-events-manager') : esc_html__('Enviar Novo Evento', 'apollo-events-manager'); ?></h2>

			<form class="aem-submit-form space-y-6" method="post" enctype="multipart/form-data">
				<?php wp_nonce_field('apollo_submit_event', 'apollo_submit_event_nonce'); ?>
				<?php if ($is_edit) : ?>
					<input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
				<?php endif; ?>

				<!-- Título -->
				<div>
					<label for="post_title" class="block text-sm font-medium mb-2">
						<?php esc_html_e('Título do Evento', 'apollo-events-manager'); ?> <span class="text-red-500">*</span>
					</label>
					<input
						type="text"
						id="post_title"
						name="post_title"
						class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
						required
						value="<?php echo esc_attr($title); ?>">
				</div>

				<!-- Descrição -->
				<div>
					<label for="post_content" class="block text-sm font-medium mb-2">
						<?php esc_html_e('Descrição do Evento', 'apollo-events-manager'); ?>
					</label>
					<textarea
						id="post_content"
						name="post_content"
						rows="6"
						class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
						placeholder="<?php esc_attr_e('Descreva o evento, line-up, informações importantes...', 'apollo-events-manager'); ?>"><?php echo esc_textarea($content); ?></textarea>
				</div>

				<!-- Data e Hora -->
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<label for="event_start_date" class="block text-sm font-medium mb-2">
							<?php esc_html_e('Data de Início', 'apollo-events-manager'); ?> <span class="text-red-500">*</span>
						</label>
						<input
							type="date"
							id="event_start_date"
							name="event_start_date"
							class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
							required
							value="<?php echo esc_attr($start_date); ?>">
					</div>
					<div>
						<label for="event_start_time" class="block text-sm font-medium mb-2">
							<?php esc_html_e('Hora de Início', 'apollo-events-manager'); ?>
						</label>
						<input
							type="time"
							id="event_start_time"
							name="event_start_time"
							class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
							value="<?php echo esc_attr($start_time); ?>">
					</div>
				</div>

				<!-- Data e Hora de Término (Opcional) -->
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<label for="event_end_date" class="block text-sm font-medium mb-2">
							<?php esc_html_e('Data de Término', 'apollo-events-manager'); ?> <span class="text-gray-500 text-xs">(<?php esc_html_e('Opcional', 'apollo-events-manager'); ?>)</span>
						</label>
						<input
							type="date"
							id="event_end_date"
							name="event_end_date"
							class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
							value="<?php echo esc_attr($end_date); ?>">
					</div>
					<div>
						<label for="event_end_time" class="block text-sm font-medium mb-2">
							<?php esc_html_e('Hora de Término', 'apollo-events-manager'); ?> <span class="text-gray-500 text-xs">(<?php esc_html_e('Opcional', 'apollo-events-manager'); ?>)</span>
						</label>
						<input
							type="time"
							id="event_end_time"
							name="event_end_time"
							class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
							value="<?php echo esc_attr($end_time); ?>">
					</div>
				</div>

				<!-- Local -->
				<div>
					<label for="event_local" class="block text-sm font-medium mb-2">
						<?php esc_html_e('Local', 'apollo-events-manager'); ?> <span class="text-red-500">*</span>
					</label>
					<select
						id="event_local"
						name="event_local"
						class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
						required>
						<option value=""><?php esc_html_e('Selecione um local', 'apollo-events-manager'); ?></option>
						<?php
                        foreach ($all_locals as $local) {
                            $local_name = apollo_get_post_meta($local->ID, '_local_name', true) ?: $local->post_title;
                            $selected   = ($local_id == $local->ID) ? 'selected' : '';
                            echo '<option value="' . esc_attr($local->ID) . '" ' . $selected . '>' . esc_html($local_name) . '</option>';
                        }
        ?>
					</select>
				</div>

				<!-- DJs -->
				<div>
					<label for="event_djs" class="block text-sm font-medium mb-2">
						<?php esc_html_e('DJs', 'apollo-events-manager'); ?> <span class="text-gray-500 text-xs">(<?php esc_html_e('Selecione múltiplos', 'apollo-events-manager'); ?>)</span>
					</label>
					<select
						multiple
						id="event_djs"
						name="event_djs[]"
						size="8"
						class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
						<?php
        foreach ($all_djs as $dj) {
            $dj_name  = apollo_get_post_meta($dj->ID, '_dj_name', true) ?: $dj->post_title;
            $selected = in_array($dj->ID, $dj_ids) ? 'selected' : '';
            echo '<option value="' . esc_attr($dj->ID) . '" ' . $selected . '>' . esc_html($dj_name) . '</option>';
        }
        ?>
					</select>
					<p class="mt-2 text-sm text-gray-600">
						<i class="ri-information-line"></i> <?php esc_html_e('Segure Ctrl (Windows) ou Cmd (Mac) para selecionar múltiplos DJs', 'apollo-events-manager'); ?>
					</p>
				</div>

				<!-- FASE 2: Estilos de Som -->
				<div>
					<label for="event_sounds" class="block text-sm font-medium mb-2">
						<?php esc_html_e('Estilos de Som / Cena', 'apollo-events-manager'); ?>
					</label>
					<select
						multiple
						id="event_sounds"
						name="event_sounds[]"
						size="5"
						class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
						<?php
        foreach ($all_sounds as $sound) {
            $selected = in_array($sound->slug, $sounds_terms) ? 'selected' : '';
            echo '<option value="' . esc_attr($sound->slug) . '" ' . $selected . '>' . esc_html($sound->name) . '</option>';
        }
        ?>
					</select>
					<p class="mt-2 text-sm text-gray-600">
						<i class="ri-information-line"></i> <?php esc_html_e('Selecione os estilos musicais do evento', 'apollo-events-manager'); ?>
					</p>
				</div>

				<!-- FASE 2: Links Principais -->
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<label for="event_link_instagram" class="block text-sm font-medium mb-2">
							<i class="ri-instagram-line"></i> <?php esc_html_e('Instagram', 'apollo-events-manager'); ?>
						</label>
						<input
							type="url"
							id="event_link_instagram"
							name="event_link_instagram"
							class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
							placeholder="https://instagram.com/..."
							value="<?php echo esc_attr($links['instagram']); ?>">
					</div>
					<div>
						<label for="event_link_ra" class="block text-sm font-medium mb-2">
							<i class="ri-global-line"></i> <?php esc_html_e('Resident Advisor', 'apollo-events-manager'); ?>
						</label>
						<input
							type="url"
							id="event_link_ra"
							name="event_link_ra"
							class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
							placeholder="https://ra.co/..."
							value="<?php echo esc_attr($links['ra']); ?>">
					</div>
					<div>
						<label for="event_link_sympla" class="block text-sm font-medium mb-2">
							<i class="ri-ticket-line"></i> <?php esc_html_e('Sympla', 'apollo-events-manager'); ?>
						</label>
						<input
							type="url"
							id="event_link_sympla"
							name="event_link_sympla"
							class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
							placeholder="https://sympla.com.br/..."
							value="<?php echo esc_attr($links['sympla']); ?>">
					</div>
					<div>
						<label for="event_link_shotgun" class="block text-sm font-medium mb-2">
							<i class="ri-ticket-line"></i> <?php esc_html_e('Shotgun', 'apollo-events-manager'); ?>
						</label>
						<input
							type="url"
							id="event_link_shotgun"
							name="event_link_shotgun"
							class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
							placeholder="https://shotgun.live/..."
							value="<?php echo esc_attr($links['shotgun']); ?>">
					</div>
				</div>

				<!-- FASE 2: Co-autores -->
				<div>
					<label for="event_gestao" class="block text-sm font-medium mb-2">
						<?php esc_html_e('Colaboradores / Co-autores', 'apollo-events-manager'); ?> <span class="text-gray-500 text-xs">(<?php esc_html_e('Opcional', 'apollo-events-manager'); ?>)</span>
					</label>
					<select
						multiple
						id="event_gestao"
						name="event_gestao[]"
						size="6"
						class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
						<?php
        foreach ($all_users as $user) {
            $selected = in_array($user->ID, $gestao) ? 'selected' : '';
            echo '<option value="' . esc_attr($user->ID) . '" ' . $selected . '>' . esc_html($user->display_name . ' (' . $user->user_email . ')') . '</option>';
        }
        ?>
					</select>
					<p class="mt-2 text-sm text-gray-600">
						<i class="ri-information-line"></i> <?php esc_html_e('Selecione usuários que podem editar este evento', 'apollo-events-manager'); ?>
					</p>
				</div>

				<!-- Timetable (Hidden - populated by JS if needed) -->
				<input
					type="hidden"
					id="apollo_event_timetable"
					name="apollo_event_timetable"
					value="">
				<p class="text-sm text-gray-600">
					<i class="ri-information-line"></i> <?php esc_html_e('A programação (line-up) será criada automaticamente com base nos DJs selecionados.', 'apollo-events-manager'); ?>
				</p>

				<!-- Submit Button -->
				<div class="flex justify-end gap-4">
					<button
						type="submit"
						name="aem_submit"
						value="1"
						class="btn btn-primary px-6 py-3">
						<i class="ri-send-plane-line"></i> <?php echo $is_edit ? esc_html__('Atualizar Evento', 'apollo-events-manager') : esc_html__('Enviar Evento para Revisão', 'apollo-events-manager'); ?>
					</button>
				</div>
			</form>
		</div>
		<?php
        return ob_get_clean();
    }

    /**
     * Shortcode: [event_locals] - Lista de Locais
     */
    public function event_locals_shortcode($atts)
    {
        $atts = shortcode_atts(
            [
                'limit'   => -1,
                'orderby' => 'title',
                'order'   => 'ASC',
            ],
            $atts,
            'event_locals'
        );

        $args = [
            'post_type'      => 'event_local',
            'posts_per_page' => absint($atts['limit']),
            'orderby'        => $atts['orderby'],
            'order'          => $atts['order'],
            'post_status'    => 'publish',
        ];

        $locals = get_posts($args);

        if (empty($locals)) {
            return '<p>' . esc_html__('No locals found.', 'apollo-events-manager') . '</p>';
        }

        ob_start();
        echo '<div class="apollo-locals-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">';
        foreach ($locals as $local) {
            $local_name    = apollo_get_post_meta($local->ID, '_local_name', true) ?: $local->post_title;
            $local_address = apollo_get_post_meta($local->ID, '_local_address', true);
            $local_city    = apollo_get_post_meta($local->ID, '_local_city', true);

            echo '<div class="local-card glass" style="padding: 20px; border-radius: 8px;">';
            echo '<h3 style="margin: 0 0 10px;">' . esc_html($local_name) . '</h3>';
            if ($local_address) {
                echo '<p style="color: var(--text-main, #666); font-size: 0.9em;"><i class="ri-map-pin-line"></i> ' . esc_html($local_address) . '</p>';
            }
            if ($local_city) {
                echo '<p style="color: var(--text-main, #666); font-size: 0.9em;">' . esc_html($local_city) . '</p>';
            }
            echo '<a href="' . get_permalink($local->ID) . '" class="button" style="margin-top: 10px;">Ver Local</a>';
            echo '</div>';
        }
        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Shortcode: [event_summary id="123"] - Resumo do evento
     */
    public function event_summary_shortcode($atts)
    {
        $atts = shortcode_atts(
            [
                'id' => 0,
            ],
            $atts,
            'event_summary'
        );

        $event_id = $atts['id'] ? absint($atts['id']) : get_the_ID();
        if (! $event_id) {
            return '';
        }

        $event = get_post($event_id);
        if (! $event || $event->post_type !== 'event_listing') {
            return '';
        }

        ob_start();
        echo '<div class="event-summary glass" style="padding: 20px; border-radius: 8px;">';
        echo '<h2>' . esc_html(get_the_title($event_id)) . '</h2>';
        echo '<p>' . esc_html(get_the_excerpt($event_id)) . '</p>';
        echo '<a href="' . get_permalink($event_id) . '" class="button">Ver Evento</a>';
        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Shortcode: [local_dashboard id="95"] - Dashboard do local
     */
    public function local_dashboard_shortcode($atts)
    {
        $atts = shortcode_atts(
            [
                'id' => 0,
            ],
            $atts,
            'local_dashboard'
        );

        $local_id = $atts['id'] ? absint($atts['id']) : get_the_ID();
        if (! $local_id) {
            return '';
        }

        $local = get_post($local_id);
        if (! $local || $local->post_type !== 'event_local') {
            return '';
        }

        // Get events for this local
        $events = get_posts(
            [
                'post_type'      => 'event_listing',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'meta_query'     => [
                    [
                        'key'     => '_event_local_ids',
                        'value'   => $local_id,
                        'compare' => '=',
                    ],
                ],
            ]
        );

        $local_name = apollo_get_post_meta($local_id, '_local_name', true) ?: $local->post_title;

        ob_start();
        echo '<div class="local-dashboard glass" style="padding: 20px; border-radius: 8px;">';
        echo '<h2>' . esc_html($local_name) . '</h2>';
        echo '<p>' . sprintf(esc_html__('Total Events: %d', 'apollo-events-manager'), count($events)) . '</p>';
        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Shortcode: [past_events] - Eventos passados
     */
    public function past_events_shortcode($atts)
    {
        $atts = shortcode_atts(
            [
                'limit' => 10,
            ],
            $atts,
            'past_events'
        );

        $args = [
            'post_type'      => 'event_listing',
            'posts_per_page' => absint($atts['limit']),
            'post_status'    => 'publish',
            'meta_key'       => '_event_start_date',
            'orderby'        => 'meta_value',
            'order'          => 'DESC',
            'meta_query'     => [
                [
                    'key'     => '_event_start_date',
                    'value'   => current_time('mysql'),
                    'compare' => '<',
                    'type'    => 'DATETIME',
                ],
            ],
        ];

        $events = get_posts($args);

        if (empty($events)) {
            return '<p>' . esc_html__('No past events found.', 'apollo-events-manager') . '</p>';
        }

        ob_start();
        echo '<div class="past-events-list" style="display: grid; gap: 15px;">';
        foreach ($events as $event) {
            echo '<div class="past-event-item glass" style="padding: 15px; border-radius: 6px;">';
            echo '<h3 style="margin: 0;"><a href="' . get_permalink($event->ID) . '">' . esc_html(get_the_title($event->ID)) . '</a></h3>';
            $start_date = apollo_get_post_meta($event->ID, '_event_start_date', true);
            if ($start_date) {
                echo '<p style="color: var(--text-main, #666); font-size: 0.9em;">' . esc_html(date_i18n('F j, Y', strtotime($start_date))) . '</p>';
            }
            echo '</div>';
        }
        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Shortcode: [single_event_dj id="92"] - Single DJ do evento
     */
    public function single_event_dj_shortcode($atts)
    {
        $atts = shortcode_atts(
            [
                'id' => 0,
            ],
            $atts,
            'single_event_dj'
        );

        $dj_id = $atts['id'] ? absint($atts['id']) : get_the_ID();
        if (! $dj_id) {
            return '';
        }

        $dj = get_post($dj_id);
        if (! $dj || $dj->post_type !== 'event_dj') {
            return '';
        }

        $dj_name  = apollo_get_post_meta($dj_id, '_dj_name', true) ?: $dj->post_title;
        $dj_bio   = apollo_get_post_meta($dj_id, '_dj_bio', true);
        $dj_image = apollo_get_post_meta($dj_id, '_dj_image', true);

        ob_start();
        echo '<div class="single-dj-profile glass" style="padding: 30px; border-radius: 12px; max-width: 600px; margin: 0 auto;">';
        if ($dj_image) {
            $image_url = filter_var($dj_image, FILTER_VALIDATE_URL) ? $dj_image : wp_get_attachment_url($dj_image);
            if ($image_url) {
                echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($dj_name) . '" style="width: 100%; max-width: 300px; border-radius: 8px; display: block; margin: 0 auto 20px;">';
            }
        }
        echo '<h1 style="text-align: center; margin-bottom: 20px;">' . esc_html($dj_name) . '</h1>';
        if ($dj_bio) {
            echo '<div class="dj-bio" style="line-height: 1.6;">' . wp_kses_post(wpautop($dj_bio)) . '</div>';
        }
        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Shortcode: [single_event_local id="95"] - Single local do evento
     */
    public function single_event_local_shortcode($atts)
    {
        $atts = shortcode_atts(
            [
                'id' => 0,
            ],
            $atts,
            'single_event_local'
        );

        $local_id = $atts['id'] ? absint($atts['id']) : get_the_ID();
        if (! $local_id) {
            return '';
        }

        $local = get_post($local_id);
        if (! $local || $local->post_type !== 'event_local') {
            return '';
        }

        $local_name        = apollo_get_post_meta($local_id, '_local_name', true) ?: $local->post_title;
        $local_description = apollo_get_post_meta($local_id, '_local_description', true);
        $local_address     = apollo_get_post_meta($local_id, '_local_address', true);
        $local_city        = apollo_get_post_meta($local_id, '_local_city', true);

        ob_start();
        echo '<div class="single-local-profile glass" style="padding: 30px; border-radius: 12px;">';
        echo '<h1 style="margin-bottom: 20px;">' . esc_html($local_name) . '</h1>';
        if ($local_description) {
            echo '<div class="local-description" style="margin-bottom: 20px; line-height: 1.6;">' . wp_kses_post(wpautop($local_description)) . '</div>';
        }
        if ($local_address || $local_city) {
            echo '<div class="local-info" style="background: var(--bg-secondary, #f8fafc); padding: 15px; border-radius: 6px;">';
            if ($local_address) {
                echo '<p style="margin: 5px 0;"><i class="ri-map-pin-line"></i> ' . esc_html($local_address) . '</p>';
            }
            if ($local_city) {
                echo '<p style="margin: 5px 0;"><i class="ri-building-line"></i> ' . esc_html($local_city) . '</p>';
            }
            echo '</div>';
        }
        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Shortcode: [submit_event_form] - Formulário de submissão de evento
     */
    public function submit_event_form_shortcode($atts)
    {
        if (! is_user_logged_in()) {
            return '<p>' . esc_html__('You must be logged in to submit an event.', 'apollo-events-manager') . '</p>';
        }

        ob_start();
        echo '<div class="submit-event-form-wrapper glass" style="padding: 30px; border-radius: 12px; max-width: 800px; margin: 0 auto;">';
        echo '<h2>' . esc_html__('Submit New Event', 'apollo-events-manager') . '</h2>';
        echo '<form method="post" action="' . admin_url('post-new.php?post_type=event_listing') . '">';
        echo '<p style="background: var(--bg-secondary, #f8fafc); padding: 15px; border-radius: 6px;">';
        echo esc_html__('Use the WordPress admin to create events with full functionality.', 'apollo-events-manager');
        echo '</p>';
        echo '<a href="' . admin_url('post-new.php?post_type=event_listing') . '" class="button button-primary" style="margin-top: 15px;">' . esc_html__('Create Event', 'apollo-events-manager') . '</a>';
        echo '</form>';
        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Shortcode: [submit_dj_form] - Formulário de submissão de DJ
     */
    public function submit_dj_form_shortcode($atts)
    {
        if (! is_user_logged_in()) {
            return '<p>' . esc_html__('You must be logged in to submit a DJ.', 'apollo-events-manager') . '</p>';
        }

        ob_start();
        echo '<div class="submit-dj-form-wrapper glass" style="padding: 30px; border-radius: 12px; max-width: 600px; margin: 0 auto;">';
        echo '<h2>' . esc_html__('Submit New DJ', 'apollo-events-manager') . '</h2>';
        echo '<p style="background: var(--bg-secondary, #f8fafc); padding: 15px; border-radius: 6px;">';
        echo esc_html__('Use the WordPress admin to create DJ profiles.', 'apollo-events-manager');
        echo '</p>';
        echo '<a href="' . admin_url('post-new.php?post_type=event_dj') . '" class="button button-primary" style="margin-top: 15px;">' . esc_html__('Create DJ Profile', 'apollo-events-manager') . '</a>';
        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Shortcode: [submit_local_form] - Formulário de submissão de local
     */
    public function submit_local_form_shortcode($atts)
    {
        if (! is_user_logged_in()) {
            return '<p>' . esc_html__('You must be logged in to submit a local.', 'apollo-events-manager') . '</p>';
        }

        ob_start();
        echo '<div class="submit-local-form-wrapper glass" style="padding: 30px; border-radius: 12px; max-width: 600px; margin: 0 auto;">';
        echo '<h2>' . esc_html__('Submit New Local', 'apollo-events-manager') . '</h2>';
        echo '<p style="background: var(--bg-secondary, #f8fafc); padding: 15px; border-radius: 6px;">';
        echo esc_html__('Use the WordPress admin to create local profiles.', 'apollo-events-manager');
        echo '</p>';
        echo '<a href="' . admin_url('post-new.php?post_type=event_local') . '" class="button button-primary" style="margin-top: 15px;">' . esc_html__('Create Local', 'apollo-events-manager') . '</a>';
        echo '</div>';

        return ob_get_clean();
    }

    /**
     * FASE 4: Track event view
     */
    public function track_event_view()
    {
        if (! is_singular('event_listing')) {
            return;
        }

        if (! class_exists('Apollo_Event_Stats')) {
            return;
        }

        $event_id = get_the_ID();
        if (! $event_id) {
            return;
        }

        // Registrar view (com limite automático de spam)
        if (class_exists('Apollo_Event_Stats')) {
            $stats_class = 'Apollo_Event_Stats';
            call_user_func_array([ $stats_class, 'record_action' ], [ $event_id, 'view' ]);
        }
    }

    /**
     * FASE 4: Shortcode Industry Overview Dashboard
     */
    public function industry_overview_shortcode($atts)
    {
        if (! is_user_logged_in()) {
            return '<div class="apollo-auth-required"><p>' . esc_html__('Você precisa estar logado para ver seu Industry Overview.', 'apollo-events-manager') . '</p></div>';
        }

        if (! class_exists('Apollo_Event_Stats')) {
            return '<div class="apollo-error"><p>' . esc_html__('Sistema de estatísticas não disponível.', 'apollo-events-manager') . '</p></div>';
        }

        $user_id             = get_current_user_id();
        $stats_class         = 'Apollo_Event_Stats';
        $stats               = call_user_func([ $stats_class, 'get_user_stats' ], $user_id);
        $sounds_interactions = call_user_func([ $stats_class, 'get_sounds_interactions' ], $user_id);
        $top_locals          = call_user_func_array([ $stats_class, 'get_top_locals' ], [ $user_id, 5 ]);

        ob_start();
        include APOLLO_APRIO_PATH . 'templates/industry-overview.php';

        return ob_get_clean();
    }

    /**
     * Render placeholders documentation page
     * Organized by CPT with uni.css styling
     */
    public function render_placeholders_page()
    {
        if (! function_exists('apollo_events_get_placeholders')) {
            echo '<div class="wrap"><p>' . esc_html__('Placeholder system not loaded.', 'apollo-events-manager') . '</p></div>';

            return;
        }

        $placeholders = apollo_events_get_placeholders();

        // Organize placeholders by CPT
        $organized = [
            'event_listing' => [],
            'event_local'   => [],
            'event_dj'      => [],
        ];

        foreach ($placeholders as $id => $placeholder) {
            // Determine CPT based on key patterns
            if (strpos($id, 'local_') === 0) {
                $organized['event_local'][ $id ] = $placeholder;
            } elseif (strpos($id, 'dj_') === 0) {
                $organized['event_dj'][ $id ] = $placeholder;
            } else {
                $organized['event_listing'][ $id ] = $placeholder;
            }
        }

        // Enqueue uni.css and RemixIcon for admin page
        // NOTE: uni.css loads LAST via force_uni_css_last() hook
        if (! wp_style_is('apollo-uni-css', 'enqueued')) {
            wp_register_style('apollo-uni-css', 'https://assets.apollo.rio.br/uni.css', [], '2.0.0');
            add_action('admin_head', [ $this, 'force_uni_css_last' ], 999999);
        }
        if (! wp_style_is('remixicon', 'enqueued')) {
            wp_enqueue_style('remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', [], '4.7.0');
        }
        ?>
		<div class="wrap" style="padding: 20px;">
			<h1 style="margin-bottom: 10px;"><?php echo esc_html__('Apollo Events – Shortcodes & Placeholders', 'apollo-events-manager'); ?></h1>
			<p><?php echo esc_html__('Complete reference of all available placeholders organized by Custom Post Type.', 'apollo-events-manager'); ?></p>

			<div class="container p-10" style="max-width: 1400px; margin: 30px auto 0;">

				<!-- EVENT LISTING PLACEHOLDERS -->
				<div class="glass-table-card glass" style="margin-bottom: 30px;">
					<div class="table-header p-10" style="border-bottom: 1px solid var(--border-color, #e2e8f0);">
						<h3 style="margin: 0; font-size: 1.3rem; color: var(--text-primary, #1e293b); display: flex; align-items: center; gap: 10px;">
							<i class="ri-calendar-event-line" style="font-size: 1.5rem;"></i>
							<?php echo esc_html__('Event Listing (event_listing)', 'apollo-events-manager'); ?>
							<span style="font-size: 0.9rem; font-weight: normal; color: var(--text-main, #64748b); margin-left: 10px;">
								(<?php echo count($organized['event_listing']); ?> placeholders)
							</span>
						</h3>
					</div>
					<div class="table-wrapper">
						<table class="table">
							<thead>
								<tr>
									<th style="width: 15%;"><?php echo esc_html__('Placeholder ID', 'apollo-events-manager'); ?></th>
									<th style="width: 18%;"><?php echo esc_html__('Label', 'apollo-events-manager'); ?></th>
									<th style="width: 30%;"><?php echo esc_html__('Description', 'apollo-events-manager'); ?></th>
									<th style="width: 10%;"><?php echo esc_html__('Source', 'apollo-events-manager'); ?></th>
									<th style="width: 17%;"><?php echo esc_html__('Meta Key / Taxonomy', 'apollo-events-manager'); ?></th>
									<th style="width: 10%;"><?php echo esc_html__('Example', 'apollo-events-manager'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if (! empty($organized['event_listing'])) : ?>
									<?php foreach ($organized['event_listing'] as $placeholder) : ?>
										<tr>
											<td><code style="background: var(--bg-secondary, #f8fafc); padding: 4px 8px; border-radius: 4px; font-size: 0.9em;"><?php echo esc_html($placeholder['id']); ?></code></td>
											<td><strong><?php echo esc_html($placeholder['label']); ?></strong></td>
											<td><?php echo esc_html($placeholder['description']); ?></td>
											<td>
												<span style="display: inline-flex; align-items: center; gap: 5px;">
													<?php
                                                    $icon = 'ri-file-text-line';
									    $color                                       = '#64748b';
									    if ($placeholder['source'] === 'meta') {
									        $icon  = 'ri-database-line';
									        $color = '#3b82f6';
									    } elseif ($placeholder['source'] === 'taxonomy') {
									        $icon  = 'ri-price-tag-3-line';
									        $color = '#10b981';
									    }
									    ?>
													<i class="<?php echo esc_attr($icon); ?>" style="color: <?php echo esc_attr($color); ?>;"></i>
													<?php echo esc_html(ucfirst($placeholder['source'])); ?>
												</span>
											</td>
											<td><code style="background: var(--bg-secondary, #f8fafc); padding: 4px 8px; border-radius: 4px; font-size: 0.85em;"><?php echo esc_html($placeholder['key']); ?></code></td>
											<td><code style="background: var(--bg-secondary, #f8fafc); padding: 4px 8px; border-radius: 4px; font-size: 0.85em; color: var(--text-main, #64748b);"><?php echo esc_html($placeholder['example']); ?></code></td>
										</tr>
									<?php endforeach; ?>
								<?php else : ?>
									<tr>
										<td colspan="6" style="text-align: center; padding: 40px; color: var(--text-main, #64748b);">
											<?php echo esc_html__('No placeholders found for this CPT.', 'apollo-events-manager'); ?>
										</td>
									</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>

				<!-- EVENT LOCAL PLACEHOLDERS -->
				<div class="glass-table-card glass" style="margin-bottom: 30px;">
					<div class="table-header p-10" style="border-bottom: 1px solid var(--border-color, #e2e8f0);">
						<h3 style="margin: 0; font-size: 1.3rem; color: var(--text-primary, #1e293b); display: flex; align-items: center; gap: 10px;">
							<i class="ri-map-pin-line" style="font-size: 1.5rem;"></i>
							<?php echo esc_html__('Local (event_local)', 'apollo-events-manager'); ?>
							<span style="font-size: 0.9rem; font-weight: normal; color: var(--text-main, #64748b); margin-left: 10px;">
								(<?php echo count($organized['event_local']); ?> placeholders)
							</span>
						</h3>
					</div>
					<div class="table-wrapper">
						<table class="table">
							<thead>
								<tr>
									<th style="width: 15%;"><?php echo esc_html__('Placeholder ID', 'apollo-events-manager'); ?></th>
									<th style="width: 18%;"><?php echo esc_html__('Label', 'apollo-events-manager'); ?></th>
									<th style="width: 30%;"><?php echo esc_html__('Description', 'apollo-events-manager'); ?></th>
									<th style="width: 10%;"><?php echo esc_html__('Source', 'apollo-events-manager'); ?></th>
									<th style="width: 17%;"><?php echo esc_html__('Meta Key / Taxonomy', 'apollo-events-manager'); ?></th>
									<th style="width: 10%;"><?php echo esc_html__('Example', 'apollo-events-manager'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if (! empty($organized['event_local'])) : ?>
									<?php foreach ($organized['event_local'] as $placeholder) : ?>
										<tr>
											<td><code style="background: var(--bg-secondary, #f8fafc); padding: 4px 8px; border-radius: 4px; font-size: 0.9em;"><?php echo esc_html($placeholder['id']); ?></code></td>
											<td><strong><?php echo esc_html($placeholder['label']); ?></strong></td>
											<td><?php echo esc_html($placeholder['description']); ?></td>
											<td>
												<span style="display: inline-flex; align-items: center; gap: 5px;">
													<i class="ri-database-line" style="color: #3b82f6;"></i>
													<?php echo esc_html(ucfirst($placeholder['source'])); ?>
												</span>
											</td>
											<td><code style="background: var(--bg-secondary, #f8fafc); padding: 4px 8px; border-radius: 4px; font-size: 0.85em;"><?php echo esc_html($placeholder['key']); ?></code></td>
											<td><code style="background: var(--bg-secondary, #f8fafc); padding: 4px 8px; border-radius: 4px; font-size: 0.85em; color: var(--text-main, #64748b);"><?php echo esc_html($placeholder['example']); ?></code></td>
										</tr>
									<?php endforeach; ?>
								<?php else : ?>
									<tr>
										<td colspan="6" style="text-align: center; padding: 40px; color: var(--text-main, #64748b);">
											<?php echo esc_html__('No placeholders found for this CPT.', 'apollo-events-manager'); ?>
										</td>
									</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>

				<!-- EVENT DJ PLACEHOLDERS -->
				<div class="glass-table-card glass" style="margin-bottom: 30px;">
					<div class="table-header p-10" style="border-bottom: 1px solid var(--border-color, #e2e8f0);">
						<h3 style="margin: 0; font-size: 1.3rem; color: var(--text-primary, #1e293b); display: flex; align-items: center; gap: 10px;">
							<i class="ri-music-line" style="font-size: 1.5rem;"></i>
							<?php echo esc_html__('DJ (event_dj)', 'apollo-events-manager'); ?>
							<span style="font-size: 0.9rem; font-weight: normal; color: var(--text-main, #64748b); margin-left: 10px;">
								(<?php echo count($organized['event_dj']); ?> placeholders)
							</span>
						</h3>
					</div>
					<div class="table-wrapper">
						<table class="table">
							<thead>
								<tr>
									<th style="width: 15%;"><?php echo esc_html__('Placeholder ID', 'apollo-events-manager'); ?></th>
									<th style="width: 18%;"><?php echo esc_html__('Label', 'apollo-events-manager'); ?></th>
									<th style="width: 30%;"><?php echo esc_html__('Description', 'apollo-events-manager'); ?></th>
									<th style="width: 10%;"><?php echo esc_html__('Source', 'apollo-events-manager'); ?></th>
									<th style="width: 17%;"><?php echo esc_html__('Meta Key / Taxonomy', 'apollo-events-manager'); ?></th>
									<th style="width: 10%;"><?php echo esc_html__('Example', 'apollo-events-manager'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if (! empty($organized['event_dj'])) : ?>
									<?php foreach ($organized['event_dj'] as $placeholder) : ?>
										<tr>
											<td><code style="background: var(--bg-secondary, #f8fafc); padding: 4px 8px; border-radius: 4px; font-size: 0.9em;"><?php echo esc_html($placeholder['id']); ?></code></td>
											<td><strong><?php echo esc_html($placeholder['label']); ?></strong></td>
											<td><?php echo esc_html($placeholder['description']); ?></td>
											<td>
												<span style="display: inline-flex; align-items: center; gap: 5px;">
													<i class="ri-database-line" style="color: #3b82f6;"></i>
													<?php echo esc_html(ucfirst($placeholder['source'])); ?>
												</span>
											</td>
											<td><code style="background: var(--bg-secondary, #f8fafc); padding: 4px 8px; border-radius: 4px; font-size: 0.85em;"><?php echo esc_html($placeholder['key']); ?></code></td>
											<td><code style="background: var(--bg-secondary, #f8fafc); padding: 4px 8px; border-radius: 4px; font-size: 0.85em; color: var(--text-main, #64748b);"><?php echo esc_html($placeholder['example']); ?></code></td>
										</tr>
									<?php endforeach; ?>
								<?php else : ?>
									<tr>
										<td colspan="6" style="text-align: center; padding: 40px; color: var(--text-main, #64748b);">
											<?php echo esc_html__('No placeholders found for this CPT.', 'apollo-events-manager'); ?>
										</td>
									</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>

			</div>

			<!-- Usage Examples Section -->
			<div class="container p-10" style="max-width: 1400px; margin: 30px auto;">
				<div class="glass-table-card glass">
					<div class="table-header p-10" style="border-bottom: 1px solid var(--border-color, #e2e8f0);">
						<h3 style="margin: 0; font-size: 1.3rem; color: var(--text-primary, #1e293b);">
							<i class="ri-code-s-slash-line"></i>
							<?php echo esc_html__('Usage Examples', 'apollo-events-manager'); ?>
						</h3>
					</div>
					<div style="padding: 20px;">
						<h4 style="margin-top: 0;"><?php echo esc_html__('Template Tags', 'apollo-events-manager'); ?></h4>
						<pre style="background: var(--bg-secondary, #f8fafc); padding: 15px; border-radius: 8px; overflow-x: auto; border: 1px solid var(--border-color, #e2e8f0);"><code>
						<?php
									                                                                                                                                            echo esc_html("<?php\n");
        echo esc_html("// Get placeholder value for current event\n");
        echo esc_html("echo apollo_event_get_placeholder_value('start_month_pt');\n");
        echo esc_html("\n");
        echo esc_html("// Get placeholder value for specific event\n");
        echo esc_html("echo apollo_event_get_placeholder_value('dj_list', 123);\n");
        echo esc_html("\n");
        echo esc_html("// Get all placeholders registry\n");
        echo esc_html("\$placeholders = apollo_events_get_placeholders();\n");
        ?>
																																															</code></pre>

						<h4><?php echo esc_html__('Shortcodes', 'apollo-events-manager'); ?></h4>
						<pre style="background: var(--bg-secondary, #f8fafc); padding: 15px; border-radius: 8px; overflow-x: auto; border: 1px solid var(--border-color, #e2e8f0);"><code>
						<?php
                                                                                                                                                                            echo esc_html("[apollo_event field=\"start_month_pt\"]\n");
        echo esc_html("[apollo_event field=\"dj_list\" id=\"123\"]\n");
        echo esc_html("[apollo_event field=\"location\"]\n");
        echo esc_html("[apollo_event field=\"banner_url\"]\n");
        echo esc_html("[apollo_event field=\"video_url\"]\n");
        echo esc_html("[apollo_event field=\"dj_set_url\"]\n");
        ?>
																																															</code></pre>

						<h4><?php echo esc_html__('Common Use Cases', 'apollo-events-manager'); ?></h4>
						<ul style="line-height: 2;">
							<li><strong><?php echo esc_html__('Display DJ line-up:', 'apollo-events-manager'); ?></strong> <code>[apollo_event field="dj_list"]</code></li>
							<li><strong><?php echo esc_html__('Display event date:', 'apollo-events-manager'); ?></strong> <code>[apollo_event field="start_day"]</code> <code>[apollo_event field="start_month_pt"]</code></li>
							<li><strong><?php echo esc_html__('Display location:', 'apollo-events-manager'); ?></strong> <code>[apollo_event field="location"]</code> or <code>[apollo_event field="location_full"]</code></li>
							<li><strong><?php echo esc_html__('Display banner image:', 'apollo-events-manager'); ?></strong> <code>&lt;img src="[apollo_event field="banner_url"]" alt="Event Banner"&gt;</code></li>
							<li><strong><?php echo esc_html__('Display YouTube video:', 'apollo-events-manager'); ?></strong> <code>[apollo_event field="video_url"]</code></li>
							<li><strong><?php echo esc_html__('Display DJ set URL:', 'apollo-events-manager'); ?></strong> <code>[apollo_event field="dj_set_url"]</code></li>
							<li><strong><?php echo esc_html__('Display DJ original projects:', 'apollo-events-manager'); ?></strong> <code>[apollo_event field="dj_original_project_1"]</code></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<?php
    }

    /**
     * DJ Profile Shortcode
     * [apollo_dj_profile dj_id="123"]
     */
    public function apollo_dj_profile_shortcode($atts)
    {
        $atts = shortcode_atts(
            [
                'dj_id' => 0,
            ],
            $atts,
            'apollo_dj_profile'
        );

        ob_start();
        include APOLLO_APRIO_PATH . 'templates/shortcode-dj-profile.php';

        return ob_get_clean();
    }

    /**
     * User Dashboard Shortcode
     * [apollo_user_dashboard]
     */
    public function apollo_user_dashboard_shortcode($atts)
    {
        if (! is_user_logged_in()) {
            return '<div class="apollo-auth-required glass p-6 rounded-lg text-center">
                <h3 class="text-xl font-semibold mb-4">Login Necessário</h3>
                <p class="mb-4">Você precisa estar logado para acessar seu dashboard.</p>
                <a href="' . esc_url(wp_login_url(get_permalink())) . '" class="btn btn-primary">Entrar</a>
            </div>';
        }

        ob_start();
        include APOLLO_APRIO_PATH . 'templates/shortcode-user-dashboard.php';

        return ob_get_clean();
    }

    /**
     * Cena Rio Calendar Shortcode
     * [apollo_cena_rio]
     */
    public function apollo_cena_rio_shortcode($atts)
    {
        ob_start();
        include APOLLO_APRIO_PATH . 'templates/shortcode-cena-rio.php';

        return ob_get_clean();
    }

    /**
     * AJAX handler for saving profile data
     */
    public function ajax_save_profile()
    {
        // Verify nonce
        if (! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'apollo_profile_nonce')) {
            wp_send_json_error([ 'message' => 'Nonce inválido' ]);

            return;
        }

        // Check if user is logged in
        if (! is_user_logged_in()) {
            wp_send_json_error([ 'message' => 'Usuário não autenticado' ]);

            return;
        }

        $user_id        = get_current_user_id();
        $updated_fields = [];

        // Update bio
        if (isset($_POST['bio'])) {
            $bio = sanitize_textarea_field(wp_unslash($_POST['bio']));
            update_user_meta($user_id, 'bio_full', $bio);
            $updated_fields[] = 'bio';
        }

        // Update location
        if (isset($_POST['location'])) {
            $location = sanitize_text_field(wp_unslash($_POST['location']));
            update_user_meta($user_id, 'location', $location);
            $updated_fields[] = 'location';
        }

        // Update roles display
        if (isset($_POST['roles_display'])) {
            $roles = sanitize_text_field(wp_unslash($_POST['roles_display']));
            update_user_meta($user_id, 'roles_display', $roles);
            $updated_fields[] = 'roles_display';
        }

        wp_send_json_success(
            [
                'message'        => 'Perfil atualizado com sucesso',
                'updated_fields' => $updated_fields,
            ]
        );
    }
}

// Load Event Statistics CPT
$stat_cpt_file = plugin_dir_path(__FILE__) . 'includes/class-event-stat-cpt.php';
if (file_exists($stat_cpt_file)) {
    require_once $stat_cpt_file;
}

// Initialize the plugin
global $apollo_events_manager;
$apollo_events_manager = new Apollo_Events_Manager_Plugin();

if (! function_exists('apollo_events_shortcode_handler')) {
    /**
     * Handler for [events] shortcode - displays event listing
     *
     * @param array $atts
     *
     * @return string
     */
    function apollo_events_shortcode_handler($atts = [])
    {
        global $apollo_events_manager;

        if ($apollo_events_manager instanceof Apollo_Events_Manager_Plugin) {
            return $apollo_events_manager->events_shortcode((array) $atts);
        }

        return '<p class="apollo-events-error">' . esc_html__('Temporariamente indisponível. Recarregue a página em instantes.', 'apollo-events-manager') . '</p>';
    }
}

// Log verification completion (only in debug mode)
if (defined('WP_DEBUG') && WP_DEBUG && defined('APOLLO_DEBUG') && APOLLO_DEBUG) {
    error_log('Apollo Events Manager ' . APOLLO_APRIO_VERSION . ': Plugin loaded successfully - ' . date('Y-m-d H:i:s'));
}

/**
 * Helper: Get events page (published or trash)
 * Returns page object if found, null otherwise
 *
 * ✅ CORRIGIDO: Verifica todos os status possíveis para evitar duplicatas
 */
function apollo_em_get_events_page()
{
    // Try published page first
    $page = get_page_by_path('eventos');
    if ($page && $page->post_status === 'publish') {
        return $page;
    }

    // ✅ Verificar diretamente no banco para garantir que não há duplicatas
    global $wpdb;
    $all_pages = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, post_status
        FROM {$wpdb->posts}
        WHERE post_name = %s
        AND post_type = 'page'
        ORDER BY
            CASE post_status
                WHEN 'publish' THEN 1
                WHEN 'trash' THEN 2
                ELSE 3
            END,
            ID DESC
        LIMIT 5",
            'eventos'
        )
    );

    if (! empty($all_pages)) {
        // Retornar a primeira página encontrada (prioridade: publish > trash > outros)
        foreach ($all_pages as $page_data) {
            $found_page = get_post($page_data->ID);
            if ($found_page) {
                return $found_page;
            }
        }
    }

    return null;
}

/**
 * P0-1: Improved activation hook with idempotency checks
 */
register_activation_hook(__FILE__, 'apollo_events_manager_activate');
function apollo_events_manager_activate()
{
    // During activation, WordPress may not have all plugins loaded yet
    // So we check dependency more leniently and just warn instead of blocking

    // Load WordPress functions if not available
    if (! function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    // Check if Apollo Core is active (lenient check during activation)
    $core_active = false;
    if (function_exists('is_plugin_active')) {
        $core_active = is_plugin_active('apollo-core/apollo-core.php');
    }

    // If Core is not active, set a flag but don't block activation
    // The plugin will show admin notice instead
    if (! $core_active) {
        update_option('apollo_events_manager_missing_core', true);
    } else {
        delete_option('apollo_events_manager_missing_core');
    }

    // Check if already activated recently (prevent double runs)
    $activation_key  = 'apollo_events_manager_activation_' . APOLLO_APRIO_VERSION;
    $last_activation = get_option($activation_key, false);

    // If activated in last 5 minutes, skip (might be double-click or refresh)
    if ($last_activation && (time() - $last_activation) < 300) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('✅ Apollo Events Manager: Activation skipped (already activated recently)');
        }

        return;
    }

    // Mark activation start
    update_option($activation_key, time());

    try {
        // Define flag to prevent auto-instantiation during activation
        if (! defined('APOLLO_EVENTS_MANAGER_ACTIVATING')) {
            define('APOLLO_EVENTS_MANAGER_ACTIVATING', true);
        }

        // CRITICAL: Create pages with CANVAS template for independent display
        // Register CPTs and flush rewrite rules
        $post_types_file = plugin_dir_path(__FILE__) . 'includes/post-types.php';
        if (file_exists($post_types_file)) {
            // Load file - auto-instantiation is prevented by flag
            require_once $post_types_file;

            // Check if class exists after loading
            if (class_exists('Apollo_Post_Types')) {
                // Call static method - this registers CPTs and flushes rules
                Apollo_Post_Types::flush_rewrite_rules_on_activation();
            } else {
                // Fallback: manually register CPTs if class doesn't exist
                // This should not happen, but provides safety
                if (function_exists('register_post_type')) {
                    // Create temporary instance to register CPTs
                    $temp_instance = new Apollo_Post_Types();
                    // Trigger registration
                    $temp_instance->register_post_types();
                    $temp_instance->register_taxonomies();
                    // Flush rules
                    flush_rewrite_rules(false);
                }
            }
        } else {
            // File missing - log but don't fail activation
            if (function_exists('apollo_log_missing_file')) {
                apollo_log_missing_file($post_types_file);
            }
            // Still flush rules to prevent 404 errors
            if (function_exists('flush_rewrite_rules')) {
                flush_rewrite_rules(false);
            }
        }//end if

        // FASE 4: Create stats table (idempotent - checks existence)
        $stats_file = plugin_dir_path(__FILE__) . 'includes/class-event-stats.php';
        if (file_exists($stats_file)) {
            require_once $stats_file;
            if (class_exists('Apollo_Event_Stats')) {
                $stats_class = 'Apollo_Event_Stats';
                call_user_func([ $stats_class, 'create_table' ]);
            }
        }

        // Register analytics capability (idempotent - checks before adding)
        $roles = [ 'administrator', 'editor' ];
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role && ! $role->has_cap('view_apollo_event_stats')) {
                $role->add_cap('view_apollo_event_stats');
            }
        }

        // Handle events page creation/restoration (idempotent - checks existence)
        $events_page = apollo_em_get_events_page();

        if ($events_page && 'trash' === $events_page->post_status) {
            // Restore from trash
            wp_update_post(
                [
                    'ID'          => $events_page->ID,
                    'post_status' => 'publish',
                ]
            );
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('✅ Apollo: Restored /eventos/ page from trash (ID: ' . $events_page->ID . ')');
            }
        } elseif (! $events_page) {
            // ✅ Verificar se existe página com mesmo slug em qualquer status (incluindo lixeira)
            // Buscar diretamente no banco para garantir que não há duplicatas
            global $wpdb;
            $existing_page = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts}
            WHERE post_name = %s
            AND post_type = 'page'
            LIMIT 1",
                    'eventos'
                )
            );

            if ($existing_page) {
                // Página existe mas não foi encontrada pela função helper - pode estar em status diferente
                $existing_post = get_post($existing_page);
                if ($existing_post) {
                    error_log('⚠️ Apollo: Página /eventos/ já existe (ID: ' . $existing_page . ', Status: ' . $existing_post->post_status . ') - não criando duplicata');

                    return;
                }
            }

            // Create new only if doesn't exist at all
            $page_id = wp_insert_post(
                [
                    'post_title'    => 'Eventos',
                    'post_name'     => 'eventos',
                    'post_status'   => 'publish',
                    'post_type'     => 'page',
                    'post_content'  => '[events]',
                    'page_template' => 'canvas',
                    // CRITICAL: Canvas template for independent display
                        'meta_input' => [
                            'apollo_canvas_mode' => '1',
                    // Flag for canvas mode
                        ],
                ]
            );

            if ($page_id && ! is_wp_error($page_id)) {
                error_log('✅ Apollo: Created /eventos/ page (ID: ' . $page_id . ')');
            } elseif (is_wp_error($page_id)) {
                error_log('❌ Apollo: Erro ao criar página /eventos/: ' . $page_id->get_error_message());
            }
        } else {
            // Page already exists and is published
            error_log('✅ Apollo: /eventos/ page already exists (ID: ' . $events_page->ID . ')');
        }//end if

        // Create /djs/ page if shortcode exists
        if (shortcode_exists('event_djs') || shortcode_exists('djs_listing') || shortcode_exists('apollo_djs')) {
            $djs_page = get_page_by_path('djs');
            if (! $djs_page) {
                $djs_page_id = wp_insert_post(
                    [
                        'post_title'    => 'DJs',
                        'post_name'     => 'djs',
                        'post_status'   => 'publish',
                        'post_type'     => 'page',
                        'post_content'  => '[event_djs]',
                        'page_template' => 'canvas',
                        // CRITICAL: Canvas template
                            'meta_input' => [
                                'apollo_canvas_mode' => '1',
                        // Flag for canvas mode
                            ],
                    ]
                );

                if ($djs_page_id && ! is_wp_error($djs_page_id)) {
                    error_log('✅ Apollo: Created /djs/ page (ID: ' . $djs_page_id . ')');
                }
            } else {
                error_log('✅ Apollo: /djs/ page already exists (ID: ' . $djs_page->ID . ')');
            }//end if
        }//end if

        // Create /loc/ page if shortcode exists
        if (shortcode_exists('event_locals') || shortcode_exists('locals_listing')) {
            $locais_page = get_page_by_path('locais');
            if (! $locais_page) {
                $locais_page_id = wp_insert_post(
                    [
                        'post_title'    => 'Locais',
                        'post_name'     => 'locais',
                        'post_status'   => 'publish',
                        'post_type'     => 'page',
                        'post_content'  => '[event_locals]',
                        'page_template' => 'canvas',
                        // CRITICAL: Canvas template
                            'meta_input' => [
                                'apollo_canvas_mode' => '1',
                        // Flag for canvas mode
                            ],
                    ]
                );

                if ($locais_page_id && ! is_wp_error($locais_page_id)) {
                    error_log('✅ Apollo: Created /loc/ page (ID: ' . $locais_page_id . ')');
                }
            } else {
                error_log('✅ Apollo: /loc/ page already exists (ID: ' . $locais_page->ID . ')');
            }//end if
        }//end if

        // Create /dashboard-eventos/ page
        $dashboard_page = get_page_by_path('dashboard-eventos');
        if (! $dashboard_page) {
            $dashboard_page_id = wp_insert_post(
                [
                    'post_title'    => 'Dashboard de Eventos',
                    'post_name'     => 'dashboard-eventos',
                    'post_status'   => 'publish',
                    'post_type'     => 'page',
                    'post_content'  => '[apollo_event_user_overview]',
                    'page_template' => 'canvas',
                    // CRITICAL: Canvas template
                        'meta_input' => [
                            'apollo_canvas_mode' => '1',
                    // Flag for canvas mode
                        ],
                ]
            );

            if ($dashboard_page_id && ! is_wp_error($dashboard_page_id)) {
                error_log('✅ Apollo: Created /dashboard-eventos/ page (ID: ' . $dashboard_page_id . ')');
            }
        } else {
            error_log('✅ Apollo: /dashboard-eventos/ page already exists (ID: ' . $dashboard_page->ID . ')');
        }//end if

        // Create /mod-eventos/ page (moderators only)
        $mod_page = get_page_by_path('mod-eventos');
        if (! $mod_page) {
            $mod_page_id = wp_insert_post(
                [
                    'post_title'    => 'Moderação de Eventos',
                    'post_name'     => 'mod-eventos',
                    'post_status'   => 'publish',
                    'post_type'     => 'page',
                    'post_content'  => '<!-- Moderação de eventos - restrito a editores -->',
                    'page_template' => 'page-mod-eventos-enhanced.php',
                ]
            );

            if ($mod_page_id && ! is_wp_error($mod_page_id)) {
                // Set page to require editor role
                update_post_meta($mod_page_id, '_apollo_require_capability', 'edit_others_posts');
                error_log('✅ Apollo: Created /mod-eventos/ page (ID: ' . $mod_page_id . ')');
            }
        } else {
            error_log('✅ Apollo: /mod-eventos/ page already exists (ID: ' . $mod_page->ID . ')');
        }

        // Create 'clubber' role if it doesn't exist
        if (! get_role('clubber')) {
            add_role(
                'clubber',
                __('Clubber', 'apollo-events-manager'),
                [
                    'read'          => true,
                    'upload_files'  => true,
                    'edit_posts'    => false,
                    'publish_posts' => false,
                ]
            );
        }

        // Mark activation complete
        update_option('apollo_events_manager_activated_version', APOLLO_APRIO_VERSION);

        // Log activation (only in debug mode)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('✅ Apollo Events Manager ' . APOLLO_APRIO_VERSION . ' activated successfully');
        }
    } catch (\Exception $e) {
        // Log error but don't break activation
        error_log('❌ Apollo Events Manager: Activation error - ' . $e->getMessage());
        // Still mark as activated to prevent retry loops
        update_option($activation_key, time());
    }//end try
}

register_deactivation_hook(__FILE__, 'apollo_events_manager_deactivate');
function apollo_events_manager_deactivate()
{
    // Flush rewrite rules
    flush_rewrite_rules();

    // Log deactivation
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('⚠️ Apollo Events Manager 2.0.0 deactivated');
    }
}

/**
 * Ensure clubber role exists on init (for existing installations)
 */
add_action('init', 'apollo_ensure_clubber_role', 1);
function apollo_ensure_clubber_role()
{
    if (! get_role('clubber')) {
        add_role(
            'clubber',
            __('Clubber', 'apollo-events-manager'),
            [
                'read'          => true,
                'upload_files'  => true,
                'edit_posts'    => false,
                'publish_posts' => false,
            ]
        );
    }
}
