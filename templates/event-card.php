<?php
/**
 * Event Card Partial
 * PHASE 4: Updated to work with ViewModel data
 * DESIGN LIBRARY: Matches approved HTML from 'events discover event-card.html'
 * Uses uni.css classes for consistent styling
 *
 * @package Apollo_Events_Manager
 * @version 4.0.0 - ViewModel Integration
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Expect $event_data to be passed from ViewModel
if ( ! isset( $event_data ) || ! is_array( $event_data ) ) {
	return;
}

// Extract data from ViewModel array
$event_id         = $event_data['id'] ?? 0;
$event_title      = $event_data['title'] ?? '';
$event_permalink  = $event_data['permalink'] ?? '';
$banner_url       = $event_data['banner_url'] ?? '';
$day              = $event_data['date']['day'] ?? '--';
$month            = $event_data['date']['month'] ?? '---';
$month_str        = $event_data['date']['month_str'] ?? '';
$iso_date         = $event_data['date']['iso_date'] ?? '';
$dj_display       = $event_data['dj_display'] ?? '';
$dj_tooltip       = $event_data['dj_tooltip'] ?? '';
$location_display = $event_data['location_display'] ?? '';
$location_tooltip = $event_data['location_tooltip'] ?? '';
$genres_array     = $event_data['genres'] ?? array();
$category_slug    = $event_data['category_slug'] ?? 'uncategorized';
?>

<a href="<?php echo esc_url( $event_permalink ); ?>"
	class="event_listing"
	data-event-id="<?php echo esc_attr( $event_id ); ?>"
	data-category="<?php echo esc_attr( $category_slug ); ?>"
	data-month-str="<?php echo esc_attr( $month_str ); ?>"
	title="<?php echo esc_attr( $event_title ); ?>">

	<!-- [ELEMENT::DATE_BOX] Event Date Badge -->
	<div class="box-date-event" title="<?php echo esc_attr( $iso_date ); ?>">
		<span class="date-day"><?php echo esc_html( $day ); ?></span>
		<span class="date-month"><?php echo esc_html( $month ); ?></span>
	</div>

	<!-- [ELEMENT::EVENT_IMAGE] Event Thumbnail -->
	<div class="picture">
		<img src="<?php echo esc_url( $banner_url ); ?>"
			alt="<?php echo esc_attr( $event_title ); ?>"
			loading="lazy"
			title="<?php echo esc_attr( $event_title ); ?>">

		<!-- [ELEMENT::EVENT_TAGS] Category Tags -->
		<?php if ( ! empty( $genres_array ) ) : ?>
			<div class="event-card-tags">
				<?php foreach ( array_slice( $genres_array, 0, 3 ) as $genre ) : ?>
					<span title="<?php echo esc_attr( $genre ); ?>"><?php echo esc_html( $genre ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<!-- [ELEMENT::EVENT_INFO] Event Details -->
	<div class="event-line">
		<div class="box-info-event">
			<!-- [ELEMENT::EVENT_TITLE] -->
			<h2 class="event-li-title afasta-bmin" title="<?php echo esc_attr( $event_title ); ?>">
				<?php echo esc_html( $event_title ); ?>
			</h2>

			<!-- [ELEMENT::DJ_NAME] with tooltip -->
			<?php if ( $dj_display ) : ?>
				<p class="event-li-detail of-dj afasta-bmin"
					title="<?php echo esc_attr( $dj_tooltip ); ?>"
					data-tooltip="<?php echo esc_attr( $dj_tooltip ); ?>">
					<i class="ri-sound-module-fill" aria-hidden="true"></i>
					<span><?php echo esc_html( $dj_display ); ?></span>
				</p>
			<?php endif; ?>

			<!-- [ELEMENT::VENUE_NAME] with tooltip -->
			<p class="event-li-detail of-location afasta-bmin"
				title="<?php echo esc_attr( $location_tooltip ); ?>"
				data-tooltip="<?php echo esc_attr( $location_tooltip ); ?>">
				<i class="ri-map-pin-2-line" aria-hidden="true"></i>
				<span id="local_nome_<?php echo esc_attr( $event_id ); ?>">
					<?php echo esc_html( $location_display ); ?>
				</span>
			</p>
		</div>
	</div>
</a>
