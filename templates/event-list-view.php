<?php
// phpcs:ignoreFile
/**
 * Event List View Template
 *
 * Vertical layout: Data, nome, evento, local em linha (sem imagem)
 * Used for infinite-loading list view
 *
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../includes/helpers/event-data-helper.php';

// Get event data (same as event-card.php)
$event_id = $event_object instanceof WP_Post ? $event_object->ID : get_the_ID();

// Get event data via helper
$event_title = apollo_get_post_meta($event_id, '_event_title', true) ?: get_the_title();
$date_info   = Apollo_Event_Data_Helper::parse_event_date(
    apollo_get_post_meta($event_id, '_event_start_date', true)
);
$local = Apollo_Event_Data_Helper::get_local_data($event_id);

// Parse date
$day       = $date_info['day']      ?? '';
$month_str = $date_info['month_pt'] ?? '';
$iso_date  = $date_info['iso_date'] ?? '';

// Get local name
$local_name = $local ? $local['name'] : '';
if (empty($local_name)) {
    $local_name = apollo_get_post_meta($event_id, '_event_location', true);
}

// Get category
$categories    = wp_get_post_terms($event_id, 'event_listing_category');
$category_slug = ! empty($categories) && ! is_wp_error($categories) ? $categories[0]->slug : '';

// Get local slug
$local_slug = $local ? $local['slug'] : '';
if (empty($local_slug) && ! empty($local_name)) {
    $local_slug = sanitize_title($local_name);
}
?>

<a href="<?php echo esc_url(get_permalink($event_id)); ?>" 
	class="event-list-item transition-all duration-300 hover:bg-gray-50 dark:hover:bg-gray-800" 
	data-motion-card="true"
	data-event-id="<?php echo esc_attr($event_id); ?>" 
	data-category="<?php echo esc_attr($category_slug); ?>" 
	data-local-slug="<?php echo esc_attr($local_slug); ?>"
	data-month-str="<?php echo esc_attr($month_str); ?>"
	data-event-start-date="<?php echo esc_attr($iso_date); ?>"
	style="display: flex; align-items: center; padding: 1rem; border-bottom: 1px solid rgba(0,0,0,0.1); text-decoration: none; color: inherit;">
   
	<!-- Date Column -->
	<div class="event-list-date" style="min-width: 60px; margin-right: 1rem; text-align: center;">
		<div class="date-day" style="font-size: 1.5rem; font-weight: bold; line-height: 1.2;">
			<?php echo esc_html($day ?: '--'); ?>
		</div>
		<div class="date-month" style="font-size: 0.875rem; text-transform: uppercase; color: #666;">
			<?php echo esc_html($month_str ?: '---'); ?>
		</div>
	</div>
	
	<!-- Content Column -->
	<div class="event-list-content" style="flex: 1;">
		<div class="event-title" style="font-weight: 600; font-size: 1rem; margin-bottom: 0.25rem;">
			<?php echo esc_html($event_title); ?>
		</div>
		<?php if (! empty($local_name)) : ?>
		<div class="event-location" style="font-size: 0.875rem; color: #666;">
			<?php echo esc_html($local_name); ?>
		</div>
		<?php endif; ?>
	</div>
	
	<!-- Arrow Icon -->
	<div class="event-list-arrow" style="margin-left: 1rem; color: #999;">
		<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M9 18l6-6-6-6"/>
		</svg>
	</div>
</a>

