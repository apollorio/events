<?php
// phpcs:ignoreFile
/**
 * Migration Validator
 * Validates data integrity after APRIO → Apollo migration
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 * @since 2.0.0
 */

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Apollo Migration Validator Class
 */
class Apollo_Migration_Validator
{
    /**
     * Run full validation
     *
     * @return array Array of issues found
     */
    public static function validate_migration()
    {
        $issues = [];

        // Validate Events
        $event_issues = self::validate_events();
        if (! empty($event_issues)) {
            $issues['events'] = $event_issues;
        }

        // Validate DJs
        $dj_issues = self::validate_djs();
        if (! empty($dj_issues)) {
            $issues['djs'] = $dj_issues;
        }

        // Validate Locals
        $local_issues = self::validate_locals();
        if (! empty($local_issues)) {
            $issues['locals'] = $local_issues;
        }

        // Validate Taxonomies
        $tax_issues = self::validate_taxonomies();
        if (! empty($tax_issues)) {
            $issues['taxonomies'] = $tax_issues;
        }

        // Validate CPT Registration
        $cpt_issues = self::validate_cpt_registration();
        if (! empty($cpt_issues)) {
            $issues['cpts'] = $cpt_issues;
        }

        return $issues;
    }

    /**
     * Validate Events
     */
    private static function validate_events()
    {
        $issues = [];

        $events = get_posts(
            [
                'post_type'      => 'event_listing',
                'posts_per_page' => -1,
                'post_status'    => 'any',
            ]
        );

        if (empty($events)) {
            $issues[] = '⚠️ No events found in database';

            return $issues;
        }

        foreach ($events as $event) {
            $event_id = $event->ID;

            // Check _event_dj_ids format
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (! empty($dj_ids)) {
                $unserialized = maybe_unserialize($dj_ids);
                if (! is_array($unserialized)) {
                    $issues[] = "❌ Event #{$event_id} ({$event->post_title}): Invalid _event_dj_ids format (expected array, got " . gettype($unserialized) . ')';
                } else {
                    // Validate each DJ ID exists
                    foreach ($unserialized as $dj_id) {
                        $dj_id = intval($dj_id);
                        if ($dj_id > 0 && ! get_post($dj_id)) {
                            $issues[] = "⚠️ Event #{$event_id} ({$event->post_title}): References non-existent DJ #{$dj_id}";
                        }
                    }
                }
            }

            // Check _event_local_ids exists and valid
            $local_id = get_post_meta($event_id, '_event_local_ids', true);
            if (empty($local_id)) {
                $issues[] = "⚠️ Event #{$event_id} ({$event->post_title}): Missing _event_local_ids";
            } else {
                $local_id = intval($local_id);
                if ($local_id > 0 && ! get_post($local_id)) {
                    $issues[] = "❌ Event #{$event_id} ({$event->post_title}): References non-existent Local #{$local_id}";
                }
            }

            // Check _event_timetable format
            $timetable = get_post_meta($event_id, '_event_timetable', true);
            if (! empty($timetable)) {
                $unserialized = maybe_unserialize($timetable);
                if (! is_array($unserialized)) {
                    $issues[] = "⚠️ Event #{$event_id} ({$event->post_title}): Invalid _event_timetable format (expected array)";
                } else {
                    // Validate timetable structure
                    foreach ($unserialized as $slot) {
                        if (! isset($slot['dj']) || ! isset($slot['start'])) {
                            $issues[] = "❌ Event #{$event_id} ({$event->post_title}): Invalid timetable slot structure";

                            break;
                        }
                    }
                }
            }

            // Check essential date fields
            $start_date = get_post_meta($event_id, '_event_start_date', true);
            if (empty($start_date)) {
                $issues[] = "⚠️ Event #{$event_id} ({$event->post_title}): Missing _event_start_date";
            }

            // Check banner format
            $banner = get_post_meta($event_id, '_event_banner', true);
            if (! empty($banner)) {
                // Should be URL or numeric ID
                if (! is_numeric($banner) && ! filter_var($banner, FILTER_VALIDATE_URL)) {
                    $issues[] = "⚠️ Event #{$event_id} ({$event->post_title}): Invalid _event_banner format (not URL or ID)";
                }
            }
        }//end foreach

        return $issues;
    }

    /**
     * Validate DJs
     */
    private static function validate_djs()
    {
        $issues = [];

        $djs = get_posts(
            [
                'post_type'      => 'event_dj',
                'posts_per_page' => -1,
                'post_status'    => 'any',
            ]
        );

        if (empty($djs)) {
            $issues[] = '⚠️ No DJs found in database';

            return $issues;
        }

        foreach ($djs as $dj) {
            $dj_id = $dj->ID;

            // Check if DJ has a name
            $dj_name = get_post_meta($dj_id, '_dj_name', true);
            if (empty($dj_name) && empty($dj->post_title)) {
                $issues[] = "⚠️ DJ #{$dj_id}: Missing name (both _dj_name and post_title are empty)";
            }
        }

        return $issues;
    }

    /**
     * Validate Locals
     */
    private static function validate_locals()
    {
        $issues = [];

        $locals = get_posts(
            [
                'post_type'      => 'event_local',
                'posts_per_page' => -1,
                'post_status'    => 'any',
            ]
        );

        if (empty($locals)) {
            $issues[] = '⚠️ No Locals found in database';

            return $issues;
        }

        foreach ($locals as $local) {
            $local_id = $local->ID;

            // Check coordinates
            $lat = get_post_meta($local_id, '_local_latitude', true);
            $lng = get_post_meta($local_id, '_local_longitude', true);

            if (empty($lat) || empty($lng)) {
                $issues[] = "⚠️ Local #{$local_id} ({$local->post_title}): Missing coordinates (lat: {$lat}, lng: {$lng})";
            } else {
                // Validate coordinate format
                if (! is_numeric($lat) || ! is_numeric($lng)) {
                    $issues[] = "❌ Local #{$local_id} ({$local->post_title}): Invalid coordinate format";
                }
            }

            // Check address
            $address = get_post_meta($local_id, '_local_address', true);
            if (empty($address)) {
                $issues[] = "⚠️ Local #{$local_id} ({$local->post_title}): Missing address";
            }
        }//end foreach

        return $issues;
    }

    /**
     * Validate Taxonomies
     */
    private static function validate_taxonomies()
    {
        $issues = [];

        $required_taxonomies = [
            'event_listing_category',
            'event_listing_type',
            'event_listing_tag',
            'event_sounds',
        ];

        foreach ($required_taxonomies as $taxonomy) {
            if (! taxonomy_exists($taxonomy)) {
                $issues[] = "❌ Taxonomy '{$taxonomy}' is not registered";
            } else {
                $terms = get_terms(
                    [
                        'taxonomy'   => $taxonomy,
                        'hide_empty' => false,
                    ]
                );
                if (is_wp_error($terms)) {
                    $issues[] = "❌ Error getting terms for '{$taxonomy}': " . $terms->get_error_message();
                }
            }
        }

        return $issues;
    }

    /**
     * Validate CPT Registration
     */
    private static function validate_cpt_registration()
    {
        $issues = [];

        $required_cpts = [
            'event_listing',
            'event_dj',
            'event_local',
        ];

        foreach ($required_cpts as $cpt) {
            if (! post_type_exists($cpt)) {
                $issues[] = "❌ CPT '{$cpt}' is not registered";
            }
        }

        return $issues;
    }

    /**
     * Generate HTML report
     *
     * @param array $issues Issues array from validate_migration()
     * @return string HTML report
     */
    public static function generate_html_report($issues)
    {
        if (empty($issues)) {
            return '<div class="notice notice-success"><p>✅ <strong>Validation Passed!</strong> No issues found.</p></div>';
        }

        $html = '<div class="notice notice-warning"><p><strong>⚠️ Validation Issues Found:</strong></p>';

        foreach ($issues as $category => $category_issues) {
            $html .= '<h4>' . ucfirst($category) . ' (' . count($category_issues) . ' issues)</h4>';
            $html .= '<ul>';
            foreach ($category_issues as $issue) {
                $html .= '<li>' . esc_html($issue) . '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Get validation summary
     *
     * @return array Summary stats
     */
    public static function get_summary()
    {
        return [
            'total_events'    => wp_count_posts('event_listing')->publish,
            'total_djs'       => wp_count_posts('event_dj')->publish,
            'total_locals'    => wp_count_posts('event_local')->publish,
            'aprio_active'    => class_exists('WP_Event_Manager'),
            'cpts_registered' => post_type_exists('event_listing') && post_type_exists('event_dj') && post_type_exists('event_local'),
        ];
    }
}

/**
 * Admin page for validation
 */
function apollo_migration_validator_page()
{
    if (! current_user_can('manage_options')) {
        return;
    }

    ?>
	<div class="wrap">
		<h1>🔍 Apollo Migration Validator</h1>
		
		<?php
        $summary = Apollo_Migration_Validator::get_summary();
    ?>
		
		<div class="card">
			<h2>📊 System Status</h2>
			<table class="widefat">
				<tr>
					<td><strong>Total Events:</strong></td>
					<td><?php echo esc_html($summary['total_events']); ?></td>
				</tr>
				<tr>
					<td><strong>Total DJs:</strong></td>
					<td><?php echo esc_html($summary['total_djs']); ?></td>
				</tr>
				<tr>
					<td><strong>Total Locals:</strong></td>
					<td><?php echo esc_html($summary['total_locals']); ?></td>
				</tr>
				<tr>
					<td><strong>WP Event Manager Active:</strong></td>
					<td><?php echo $summary['aprio_active'] ? '✅ Yes' : '❌ No'; ?></td>
				</tr>
				<tr>
					<td><strong>CPTs Registered:</strong></td>
					<td><?php echo $summary['cpts_registered'] ? '✅ Yes' : '❌ No'; ?></td>
				</tr>
			</table>
		</div>
		
		<?php
    if (isset($_GET['run_validation']) && $_GET['run_validation'] === '1') {
        check_admin_referer('apollo_run_validation');

        $issues = Apollo_Migration_Validator::validate_migration();
        echo Apollo_Migration_Validator::generate_html_report($issues);

        // Log to file
        $log_file = APOLLO_APRIO_PATH . 'validation-' . date('Y-m-d-H-i-s') . '.log';
        file_put_contents($log_file, print_r($issues, true));
        echo '<p><em>Validation log saved to: ' . basename($log_file) . '</em></p>';
    }
    ?>
		
		<p>
			<a href="
			<?php
        echo wp_nonce_url(
            admin_url('tools.php?page=apollo-migration-validator&run_validation=1'),
            'apollo_run_validation'
        );
    ?>
						" class="button button-primary">
				<?php esc_html_e('🔍 Run Validation Now', 'apollo-events-manager'); ?>
			</a>
		</p>
	</div>
	<?php
}

// Add admin menu
add_action(
    'admin_menu',
    function () {
        add_management_page(
            'Apollo Migration Validator',
            'Apollo Validator',
            'manage_options',
            'apollo-migration-validator',
            'apollo_migration_validator_page'
        );
    }
);
