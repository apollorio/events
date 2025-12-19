<?php
/**
 * Template: Single Event (CPT: event_listing)
 * Project: Apollo::rio
 * FIXED VERSION - Matches HTML design exactly
 */

defined( 'ABSPATH' ) || exit;

global $post;

if ( ! have_posts() ) {
	status_header( 404 );
	nocache_headers();
	include get_404_template();
	exit;
}

/**
 * ------------------------------------------------------------------------
 * ENQUEUE (Leaflet)
 * ------------------------------------------------------------------------
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! wp_style_is( 'leaflet', 'enqueued' ) ) {
			wp_enqueue_style(
				'leaflet',
				'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
				array(),
				'1.9.4'
			);
		}

		if ( ! wp_script_is( 'leaflet', 'enqueued' ) ) {
			wp_enqueue_script(
				'leaflet',
				'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
				array(),
				'1.9.4',
				true
			);
		}
	},
	20
);

/**
 * ------------------------------------------------------------------------
 * HELPERS
 * ------------------------------------------------------------------------
 */
function apollo_safe_text( $value ) {
	if ( is_array( $value ) || is_object( $value ) ) {
		return '';
	}
	return wp_kses_post( (string) $value );
}

function apollo_safe_url( $url ) {
	$url = (string) $url;
	if ( $url === '' ) {
		return '';
	}
	return esc_url( $url );
}

/**
 * Extract YouTube ID from url
 */
function apollo_youtube_id_from_url( $url ) {
	$url = trim( (string) $url );
	if ( $url === '' ) {
		return '';
	}

	if ( preg_match( '~^[a-zA-Z0-9_-]{10,15}$~', $url ) ) {
		return $url;
	}

	$patterns = array(
		'~youtube\.com/watch\?v=([^&]+)~',
		'~youtu\.be/([^?&/]+)~',
		'~youtube\.com/embed/([^?&/]+)~',
		'~youtube\.com/shorts/([^?&/]+)~',
	);
	foreach ( $patterns as $p ) {
		if ( preg_match( $p, $url, $m ) ) {
			return $m[1];
		}
	}
	return '';
}

function apollo_build_youtube_embed_url( $youtube_url_or_id ) {
	$vid = apollo_youtube_id_from_url( $youtube_url_or_id );
	if ( $vid === '' ) {
		return '';
	}

	$params = array(
		'autoplay'       => '1',
		'mute'           => '1',
		'controls'       => '0',
		'loop'           => '1',
		'playlist'       => $vid,
		'playsinline'    => '1',
		'modestbranding' => '1',
		'rel'            => '0',
		'fs'             => '0',
		'disablekb'      => '1',
		'iv_load_policy' => '3',
		'origin'         => home_url(),
	);
	return 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $vid ) . '?' . http_build_query( $params, '', '&', PHP_QUERY_RFC3986 );
}

/**
 * Coordinates resolver
 */
function apollo_event_get_coords( $event_id ) {
	$event_id = (int) $event_id;

	$candidates = array(
		array( '_event_lat', '_event_lng' ),
		array( '_event_location_lat', '_event_location_lng' ),
		array( 'event_lat', 'event_lng' ),
		array( 'lat', 'lng' ),
	);

	$lat = null;
	$lng = null;

	foreach ( $candidates as $pair ) {
		$la = get_post_meta( $event_id, $pair[0], true );
		$ln = get_post_meta( $event_id, $pair[1], true );
		if ( $la !== '' && $ln !== '' ) {
			$lat = (float) $la;
			$lng = (float) $ln;
			break;
		}
	}

	// Venue fallback
	if ( $lat === null || $lng === null ) {
		$venue_id = (int) get_post_meta( $event_id, '_event_venue_id', true );
		if ( ! $venue_id ) {
			$venue_id = (int) get_post_meta( $event_id, '_event_local_id', true );
		}
		if ( $venue_id ) {
			$la = get_post_meta( $venue_id, '_venue_lat', true );
			$ln = get_post_meta( $venue_id, '_venue_lng', true );
			if ( $la !== '' && $ln !== '' ) {
				$lat = (float) $la;
				$lng = (float) $ln;
			}
		}
	}

	$coords = array(
		'lat' => $lat,
		'lng' => $lng,
	);

	$coords = apply_filters( 'apollo_event_map_coords', $coords, $event_id );

	if ( ! isset( $coords['lat'], $coords['lng'] ) ) {
		$coords = array(
			'lat' => null,
			'lng' => null,
		);
	}

	return $coords;
}

/**
 * Lineup + timetable resolver
 */
function apollo_event_get_dj_slots( $event_id ) {
	$event_id = (int) $event_id;

	$slots = get_post_meta( $event_id, '_event_dj_slots', true );
	if ( ! is_array( $slots ) ) {
		$slots = array();
	}

	$clean = array();

	foreach ( $slots as $slot ) {
		if ( ! is_array( $slot ) ) {
			continue;
		}

		$dj_id = isset( $slot['dj_id'] ) ? (int) $slot['dj_id'] : 0;
		if ( ! $dj_id ) {
			continue;
		}

		$start = isset( $slot['start'] ) ? sanitize_text_field( (string) $slot['start'] ) : '';
		$end   = isset( $slot['end'] ) ? sanitize_text_field( (string) $slot['end'] ) : '';

		$clean[] = array(
			'dj_id' => $dj_id,
			'start' => $start,
			'end'   => $end,
		);
	}

	// If empty, fallback to simple list
	if ( empty( $clean ) ) {
		$djs = get_post_meta( $event_id, '_event_djs', true );
		if ( is_array( $djs ) ) {
			foreach ( $djs as $dj_id ) {
				$dj_id = (int) $dj_id;
				if ( $dj_id > 0 ) {
					$clean[] = array(
						'dj_id' => $dj_id,
						'start' => '',
						'end'   => '',
					);
				}
			}
		}
	}

	$clean = apply_filters( 'apollo_event_dj_slots', $clean, $event_id );

	if ( ! is_array( $clean ) ) {
		$clean = array();
	}

	// Sort by start time if available
	usort(
		$clean,
		function ( $a, $b ) {
			$as = isset( $a['start'] ) ? $a['start'] : '';
			$bs = isset( $b['start'] ) ? $b['start'] : '';
			if ( $as === '' && $bs === '' ) {
				return 0;
			}
			if ( $as === '' ) {
				return 1;
			}
			if ( $bs === '' ) {
				return -1;
			}
			return strcmp( $as, $bs );
		}
	);

	return $clean;
}

function apollo_initials( $name ) {
	$name = trim( (string) $name );
	if ( $name === '' ) {
		return 'DJ';
	}
	$parts   = preg_split( '/\s+/', $name );
	$letters = '';
	foreach ( $parts as $p ) {
		$letters .= mb_substr( $p, 0, 1 );
		if ( mb_strlen( $letters ) >= 2 ) {
			break;
		}
	}
	return mb_strtoupper( $letters );
}

while ( have_posts() ) :
	the_post();

	$event_id = get_the_ID();

	// Core fields
	$event_title = get_the_title();

	// Meta candidates
	$event_city    = get_post_meta( $event_id, '_event_city', true );
	$event_address = get_post_meta( $event_id, '_event_address', true );

	$event_date_display = get_post_meta( $event_id, '_event_date_display', true );
	$event_start_time   = get_post_meta( $event_id, '_event_start_time', true );
	$event_end_time     = get_post_meta( $event_id, '_event_end_time', true );

	$youtube_url = get_post_meta( $event_id, '_event_youtube_url', true );
	if ( ! $youtube_url ) {
		$youtube_url = get_post_meta( $event_id, '_event_video_url', true );
	}
	$youtube_embed = apollo_build_youtube_embed_url( $youtube_url );

	$featured_img = get_the_post_thumbnail_url( $event_id, 'full' );
	if ( ! $featured_img ) {
		$featured_img = 'https://assets.apollo.rio.br/img/default-event.jpg';
	}

	// Tickets / links
	$tickets_url   = get_post_meta( $event_id, '_event_ticket_url', true );
	$guestlist_url = get_post_meta( $event_id, '_event_guestlist_url', true );

	// Coupon
	$coupon_code = get_post_meta( $event_id, '_event_coupon_code', true );
	if ( $coupon_code === '' ) {
		$coupon_code = 'APOLLO';
	}

	// Interested users
	$interested_ids   = apollo_event_get_interested_user_ids( $event_id );
	$total_interested = count( $interested_ids );
	$max_visible      = 10;
	$visible_ids      = array_slice( $interested_ids, 0, $max_visible );
	$hidden_count     = max( 0, $total_interested - count( $visible_ids ) );

	// Map coords
	$coords = apollo_event_get_coords( $event_id );

	// DJ slots
	$dj_slots = apollo_event_get_dj_slots( $event_id );

	// Sounds tags (taxonomy)
	$sounds = array();
	$terms  = get_the_terms( $event_id, 'event_sounds' );
	if ( is_array( $terms ) ) {
		foreach ( $terms as $t ) {
			if ( $t && isset( $t->name ) ) {
				$sounds[] = $t->name;
			}
		}
	}
	// Duplicate for marquee
	if ( count( $sounds ) > 0 && count( $sounds ) < 6 ) {
		$orig = $sounds;
		while ( count( $sounds ) < 8 ) {
			$sounds = array_merge( $sounds, $orig );
		}
		$sounds = array_slice( $sounds, 0, 8 );
	}

	// PROMO GALLERY IMAGES (NEW)
	$promo_images = get_post_meta( $event_id, '_event_promo_gallery', true );
	if ( ! is_array( $promo_images ) ) {
		$promo_images = array();
	}
	// Limit to 5 images
	$promo_images = array_slice( $promo_images, 0, 5 );

	// VENUE IMAGES (NEW)
	$venue_images = get_post_meta( $event_id, '_event_venue_gallery', true );
	if ( ! is_array( $venue_images ) ) {
		$venue_images = array();
	}
	// Limit to 5 images
	$venue_images = array_slice( $venue_images, 0, 5 );

	// FINAL EVENT IMAGE (NEW)
	$final_image = get_post_meta( $event_id, '_event_final_image', true );

	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5, user-scalable=yes">
	<title><?php echo esc_html( $event_title ); ?> - <?php bloginfo( 'name' ); ?></title>

	<link rel="icon" href="https://assets.apollo.rio.br/img/neon-green.webp" type="image/webp">
	<link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet">
	<link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css">

	<?php wp_head(); ?>

	<style>
		* {
			-webkit-tap-highlight-color: transparent; corner-shape:squircle;
			box-sizing: border-box;
			margin: 0;
			padding: 0;
		}

		:root {
			--font-primary: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
			--bg-main: #fff;
			--bg-surface: #f5f5f5;
			--text-primary: rgba(19, 21, 23, .85);
			--text-secondary: rgba(19, 21, 23, .7);
			--border-color: #e0e2e4;
			--border-color-2: #e0e2e454;
			--radius-main: 12px;
			--radius-card: 16px;
			--transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
			--card-shadow-light: rgba(0, 0, 0, 0.05);
		}
		i {margin:1px 0 -1px 0}

		html, body {
			font-family: var(--font-primary);
			background: var(--bg-surface);
			color: var(--text-primary);
			line-height: 1.5;
			-webkit-font-smoothing: antialiased;
			-moz-osx-font-smoothing: grayscale;
		}

		@media (min-width: 888px) {
			body {
				display: flex;
				justify-content: center;
				align-items: flex-start;
				min-height: 100vh;
				padding: 5rem 0 0rem;
				background: var(--bg-surface);
			}
			.mobile-container {
				max-width: 500px;
				width: 100%;
				background: var(--bg-main);
				box-shadow: 0 0 60px rgba(0,0,0,0.1);
				border-radius: 2rem;
				overflow: hidden;
			}
		}

		@media (max-width: 888px) {
			.mobile-container {
				width: 100%;
				min-height: 100vh;
				background: var(--bg-main);
			}
		}

		/* HERO */
		.hero-media {
			position: relative;
			width: 100%;
			height: 75vh;
			overflow: hidden;
			background: #000;
			border-radius: 20px;
		}

		.video-cover {
			position: absolute;
			top: 50%;
			left: 50%;
			width: 100vw;
			height: 56.25vw; /* 16:9 ratio */
			min-height: 100vh;
			min-width: 177.77vh; /* ensures full coverage regardless of aspect ratio */
			transform: translate(-50%, -50%);
			overflow: hidden;
		}

		.video-cover iframe,
		.video-cover img {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			border: 0;
			pointer-events: none;
		}

		.hero-overlay {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: linear-gradient(
				to bottom,
				rgba(0,0,0,0.3) 0%,
				rgba(0,0,0,0.5) 50%,
				rgba(0,0,0,0.8) 100%
			);
			z-index: 1;
		}

		.hero-content {
			position: absolute;
			bottom: 0;
			left: 0;
			width: 100%;
			padding: 2rem 1.5rem;
			z-index: 2;
			color: white;
		}

		.event-tag-pill {
			display: inline-flex;
			align-items: center;
			gap: 0.5rem;
			background: rgba(255,255,255,0.2);
			backdrop-filter: blur(10px);
			border: 1px solid rgba(255,255,255,0.3);
			border-radius: 20px;
			padding: 0.5rem 1rem;
			margin: 0.25rem;
			font-size: 0.875rem;
			font-weight: 500;
			color: white;
			user-select: none;
			-webkit-user-select: none;
			-moz-user-select: none;
			-ms-user-select: none;
		}

		.event-tag-pill i {
			font-size: 1rem;
			opacity: 0.9;
		}

		.hero-title {
			font-size: 2rem;
			font-weight: 700;
			margin-bottom: 0.5rem;
			color: white;
			line-height: 1.2;
		}

		.hero-meta {
			display: flex;
			flex-direction: column;
			gap: 0.5rem;
			margin: 0 auto;
		}

		.hero-meta-item {
			display: flex;
			align-items: center;
			gap: 0.5rem;
			opacity: 0.9;
		}

		.hero-meta-item .yoha {
			margin-left: .8rem;
		}

		.hero-meta-item i {
			opacity: 0.5;
			font-size: 1.12rem;
		}

		/* BODY */
		.event-body {
			padding: 0;
		}

		.quick-actions {
			display: flex;
			justify-content: space-around;
			align-items: center;
			padding: 1.5rem;
			background: var(--bg-main);
			border-bottom: 1px solid var(--border-color);
		}

		.quick-action {
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 0.5rem;
			padding: 0.75rem;
			border-radius: var(--radius-main);
			transition: var(--transition);
			cursor: pointer;
			user-select: none;
			-webkit-user-select: none;
			-moz-user-select: none;
			-ms-user-select: none;
		}

		.quick-action:hover {
			background: var(--bg-surface);
			color: var(--text-primary);
		}

		.quick-action-icon {
			width: 48px;
			height: 48px;
			border-radius: 50%;
			background: var(--bg-surface);
			border: 2px solid var(--border-color);
			display: flex;
			align-items: center;
			justify-content: center;
			transition: var(--transition);
		}

		.quick-action-icon:hover {
			transform: scale(1.1);
			border-color: var(--text-secondary);
			background: var(--bg-main);
		}

		.quick-action-icon i {
			font-size: 1.5rem;
			color: var(--text-primary);
		}

		/* RSVP */
		.rsvp-row {
			padding: 1rem 1.5rem;
			background: var(--bg-main);
		}

		.avatars-explosion {
			display: flex;
			align-items: center;
			gap: 0.5rem;
		}

		.avatar {
			width: 40px;
			height: 40px;
			border-radius: 50%;
			border: 3px solid var(--bg-main);
			overflow: hidden;
			transition: var(--transition);
			cursor: pointer;
			user-select: none;
			-webkit-user-select: none;
			-moz-user-select: none;
			-ms-user-select: none;
		}

		.avatar:first-child {
			margin-left: 0;
		}

		.avatar:hover {
			transform: scale(1.15) translateY(-4px);
			z-index: 10;
		}

		.avatar img {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		.avatar-count {
			width: 40px;
			height: 40px;
			border-radius: 50%;
			background: var(--bg-surface);
			border: 2px solid var(--border-color);
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 0.75rem;
			font-weight: 600;
			color: var(--text-secondary);
			user-select: none;
			-webkit-user-select: none;
			-moz-user-select: none;
			-ms-user-select: none;
		}

		.interested-text {
			margin-left: 1rem;
			font-size: 0.875rem;
			color: var(--text-secondary);
		}

		.section {
			padding: 1.5rem;
			background: var(--bg-main);
			border-bottom: 1px solid var(--border-color);
		}

		.section-title {
			font-size: 1.5rem;
			font-weight: 700;
			margin-bottom: 1rem;
			display: flex;
			align-items: center;
			gap: 0.5rem;
		}

		.section-title i {
			font-size: 1.75rem;
			opacity: 0.7;
		}

		.info-card {
			background: var(--bg-surface);
			border-radius: var(--radius-card);
			padding: 1rem;
			margin-bottom: 1rem;
		}

		.info-text {
			line-height: 1.6;
			color: var(--text-primary);
		}

		/* Sounds marquee */
		.music-tags-marquee {
			overflow: hidden;
			white-space: nowrap;
			position: relative;
		}

		.music-tags-track {
			display: inline-block;
			animation: marquee 20s linear infinite;
		}

		@keyframes marquee {
			0% { transform: translateX(0); }
			100% { transform: translateX(-50%); }
		}

		.music-tag {
			display: inline-block;
			background: var(--bg-surface);
			border: 1px solid var(--border-color);
			border-radius: 20px;
			padding: 0.5rem 1rem;
			margin: 0 0.5rem;
			font-size: 0.875rem;
			font-weight: 500;
			color: var(--text-primary);
			user-select: none;
			-webkit-user-select: none;
			-moz-user-select: none;
			-ms-user-select: none;
		}

		/* PROMO GALLERY SLIDER */
		.promo-gallery-slider {
			position: relative;
			width: 100%;
			overflow: hidden;
			border-radius: var(--radius-card);
		}

		.promo-track {
			display: flex;
			width: 100%;
			transition: transform 0.3s ease;
		}

		.promo-slide {
			flex-shrink: 0;
			width: 100%;
			height: 200px;
		}

		.promo-slide img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			border-radius: var(--radius-card);
		}

		.promo-controls {
			position: absolute;
			top: 50%;
			left: 0;
			right: 0;
			transform: translateY(-50%);
			display: flex;
			justify-content: space-between;
			padding: 0 1rem;
			pointer-events: none;
		}

		.promo-prev, .promo-next {
			width: 40px;
			height: 40px;
			border-radius: 50%;
			background: rgba(255,255,255,0.9);
			backdrop-filter: blur(10px);
			border: none;
			display: flex;
			align-items: center;
			justify-content: center;
			cursor: pointer;
			transition: var(--transition);
			pointer-events: auto;
			box-shadow: 0 2px 10px rgba(0,0,0,0.1);
		}

		.promo-prev:hover, .promo-next:hover {
			background: white;
			transform: scale(1.1);
		}

		/* Lineup */
		.lineup-list {
			display: flex;
			flex-direction: column;
			gap: 1rem;
		}

		.lineup-card {
			display: flex;
			align-items: center;
			background: var(--bg-surface);
			border-radius: var(--radius-card);
			padding: 1rem;
			transition: var(--transition);
			cursor: pointer;
		}

		.lineup-card:hover {
			box-shadow: 0 4px 12px var(--card-shadow-light);
		}

		.lineup-avatar-img {
			width: 60px;
			height: 60px;
			border-radius: 50%;
			object-fit: cover;
			margin-right: 1rem;
			border: 2px solid var(--border-color);
		}

		.lineup-avatar-fallback {
			width: 60px;
			height: 60px;
			border-radius: 50%;
			background: var(--bg-surface);
			border: 2px solid var(--border-color);
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 1.25rem;
			font-weight: 700;
			color: var(--text-primary);
			margin-right: 1rem;
		}

		.lineup-info {
			flex: 1;
			margin-left: 16px;
		}

		.lineup-name {
			font-size: 1.125rem;
			font-weight: 700;
			margin-bottom: 0.25rem;
		}

		.dj-link {
			color: var(--text-primary);
			text-decoration: none;
			transition: color .3s ease;
		}

		.dj-link:hover {
			color: var(--text-secondary);
		}

		.lineup-time {
			display: flex;
			align-items: center;
			gap: 0.25rem;
			font-size: 0.875rem;
			color: var(--text-secondary);
		}

		.lineup-time i {
			opacity: 0.7;
		}

		/* Venue images slider */
		.local-images-slider {
			position: relative;
			width: 100%;
			height: 200px;
			overflow: hidden;
			border-radius: var(--radius-card);
		}

		.local-images-track {
			display: flex;
			width: 100%;
			height: 100%;
			transition: transform 0.5s ease;
		}

		.local-image {
			flex-shrink: 0;
			width: 100%;
			height: 100%;
		}

		.local-image img {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		.slider-nav {
			position: absolute;
			bottom: 1rem;
			left: 50%;
			transform: translateX(-50%);
			display: flex;
			gap: 0.5rem;
		}

		.slider-dot {
			width: 8px;
			height: 8px;
			border-radius: 50%;
			background: rgba(255,255,255,0.5);
			border: none;
			cursor: pointer;
			transition: var(--transition);
		}

		.slider-dot.active {
			background: white;
		}

		/* MAP */
		.map-view {
			width: 100%;
			height: 285px;
			border-radius: var(--radius-card);
			background: #f0f0f0;
			display: flex;
			align-items: center;
			justify-content: center;
			color: var(--text-secondary);
			font-size: 0.875rem;
		}

		/* Route controls */
		.route-controls {
			display: flex;
			align-items: center;
			gap: 0.5rem;
			padding: 0 1.5rem;
			margin-top: -2rem;
			position: relative;
			z-index: 10;
		}

		.route-input {
			flex: 1;
			display: flex;
			align-items: center;
			gap: 0.5rem;
			background: rgba(255,255,255,0.95);
			backdrop-filter: blur(10px);
			border: 1px solid var(--border-color);
			border-radius: 25px;
			padding: 0.75rem 1rem;
			transition: var(--transition);
		}

		.route-input:focus-within {
			border-color: var(--text-secondary);
			box-shadow: 0 0 0 3px rgba(0,0,0,0.1);
		}

		.route-input input {
			flex: 1;
			border: none;
			background: transparent;
			font-size: 0.875rem;
			color: var(--text-primary);
		}

		.route-input input::placeholder {
			color: var(--text-secondary);
		}

		.route-input i {
			font-size: 1.8rem;
			opacity: 0.5;
		}

		.route-button {
			width: 50px;
			height: 50px;
			border-radius: 50%;
			background: var(--bg-main);
			border: 2px solid var(--border-color);
			display: flex;
			align-items: center;
			justify-content: center;
			cursor: pointer;
			transition: var(--transition);
			box-shadow: 0 2px 10px rgba(0,0,0,0.1);
		}

		.route-button:hover {
			transform: scale(1.05);
			border-color: var(--text-secondary);
		}

		.route-button:hover:active {
			transform: scale(0.95);
		}

		/* Tickets */
		.tickets-grid {
			display: flex;
			flex-direction: column;
			gap: 1rem;
		}

		.ticket-card {
			background: var(--bg-surface);
			border: 1px solid var(--border-color);
			border-radius: var(--radius-card);
			padding: 1rem;
			margin-top: 1.45rem;
			text-decoration: none;
			display: flex;
			flex-direction: row;
			align-items: center;
			justify-content: flex-start;
			text-align: left;
			transition: var(--transition);
			filter: contrast(0.8);
			user-select: none;
			-webkit-user-select: none;
			-moz-user-select: none;
			-ms-user-select: none;
		}

		.ticket-card:hover {
			transform: translateY(4px);
			box-shadow: 0 10px 30px var(--card-shadow-light);
			border: 1px solid #3434341a;
			filter: contrast(1) brightness(1.02);
		}

		.disabled {
			cursor: default;
			pointer-events: none;
			touch-action: none;
			transition: none;
			opacity: .5;
			filter: contrast(1) brightness(1);
		}

		.disabled:hover {
			filter: contrast(1) brightness(1);
		}

		.disabled.ticket-name {
			opacity: .4;
		}

		.ticket-icon {
			width: 50px;
			height: 50px;
			border-radius: 50%;
			background: var(--bg-surface);
			border: 2px solid var(--border-color);
			display: flex;
			align-items: center;
			justify-content: center;
			margin-right: 1rem;
		}

		.ticket-icon i {
			font-size: 1.75rem;
			color: var(--text-primary);
		}

		.ticket-info {
			display: flex;
			flex-direction: column;
			align-items: flex-start;
		}

		.ticket-name {
			font-size: 1.25rem;
			font-weight: 700;
			margin-bottom: 0.25rem;
			color: var(--text-primary);
		}

		.ticket-cta {
			font-size: 0.875rem;
			font-weight: 600;
			color: var(--text-secondary);
		}

		.apollo-coupon-detail {
			display: flex;
			align-items: center;
			justify-content: space-between;
			background: var(--bg-surface);
			border: 1px solid var(--border-color);
			border-radius: var(--radius-card);
			padding: 0.75rem 1rem;
			margin-top: 1rem;
			font-size: 0.875rem;
		}

		.apollo-coupon-detail span {
			margin: auto;
			text-align: center;
		}

		.apollo-coupon-detail strong {
			color: var(--text-primary);
			font-weight: 700;
		}

		.apollo-coupon-detail .ri-coupon-3-line {
			color: var(--text-secondary);
			margin-right: 0.5rem;
		}

		.copy-code-mini {
			background: var(--bg-main);
			border: 1px solid var(--border-color);
			border-radius: 6px;
			padding: 0.25rem 0.5rem;
			cursor: pointer;
			transition: var(--transition);
			display: flex;
			align-items: center;
			gap: 0.25rem;
		}

		.copy-code-mini i {
			font-size: 1.05rem;
		}

		.copy-code-mini:hover {
			background: var(--text-secondary);
		}

		/* Secondary/Final Image */
		.secondary-image {
			width: 100%;
			text-align: center;
			padding: 1.5rem;
		}

		.secondary-image img {
			width: 100%;
			max-width: 400px;
			height: auto;
			border-radius: var(--radius-card);
		}

		/* Bottom bar */
		.bottom-bar {
			position: fixed;
			bottom: 0;
			left: 0;
			right: 0;
			background: var(--bg-main);
			border-top: 1px solid var(--border-color);
			padding: 1rem 1.5rem;
			display: flex;
			justify-content: space-between;
			align-items: center;
			z-index: 1000;
		}

		.bottom-btn {
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 0.25rem;
			padding: 0.75rem 1rem;
			border-radius: var(--radius-main);
			text-decoration: none;
			transition: var(--transition);
			cursor: pointer;
			user-select: none;
			-webkit-user-select: none;
			-moz-user-select: none;
			-ms-user-select: none;
		}

		.bottom-btn:active {
			transform: scale(0.97);
		}

		.bottom-btn:hover {
			background: var(--bg-surface);
			border-color: var(--text-secondary);
		}

		.bottom-btn.primary {
			background: var(--bg-main);
			border: 2px solid var(--border-color);
			color: var(--text-primary);
		}

		.bottom-btn.primary:hover {
			background: var(--text-secondary);
			color: white;
		}

		/* Responsive */
		@media (max-width: 768px) {
			.hero-media {
				height: 60vh;
				border-radius: 0;
			}

			.hero-title {
				font-size: 1.5rem;
			}

			.hero-content {
				padding: 1.5rem;
			}

			.event-body {
				padding-bottom: 100px;
			}

			.quick-actions {
				padding: 1rem;
			}

			.section {
				padding: 1rem;
			}

			.route-controls {
				padding: 0 1rem;
			}

			.bottom-bar {
				padding: 0.75rem 1rem;
			}
		}
	</style>
</head>

<body>
<div class="mobile-container">

	<!-- HERO -->
	<div class="hero-media">
		<div class="video-cover">
			<?php if ( $youtube_embed ) : ?>
				<iframe
					src="<?php echo esc_url( $youtube_embed ); ?>"
					frameborder="0"
					allow="autoplay; encrypted-media"
					allowfullscreen
				></iframe>
			<?php else : ?>
				<img src="<?php echo esc_url( $featured_img ); ?>" alt="<?php echo esc_attr( $event_title ); ?>">
			<?php endif; ?>
		</div>

		<div class="hero-overlay"></div>

		<div class="hero-content">
			<!-- Event Tags -->
			<?php
			$tags = get_the_terms( $event_id, 'event_listing_type' );
			if ( $tags && ! is_wp_error( $tags ) ) :
				foreach ( $tags as $tag ) :
					$icon_class = 'ri-star-fill'; // default
					switch ( $tag->slug ) {
						case 'featured':
							$icon_class = 'ri-verified-badge-fill';
							break;
						case 'recommended':
							$icon_class = 'ri-award-fill';
							break;
						case 'hot':
							$icon_class = 'ri-fire-fill';
							break;
					}
					?>
				<span class="event-tag-pill"><i class="<?php echo esc_attr( $icon_class ); ?>"></i> <?php echo esc_html( $tag->name ); ?></span>
					<?php
				endforeach;
			endif;
			?>

			<div class="hero-title"><?php echo esc_html( $event_title ); ?></div>

			<div class="hero-meta">
				<?php if ( $event_date_display ) : ?>
					<div class="hero-meta-item">
						<i class="ri-calendar-line"></i>
						<span><?php echo esc_html( $event_date_display ); ?></span>
					</div>
				<?php endif; ?>

				<?php if ( $event_city || $event_address ) : ?>
					<div class="hero-meta-item">
						<i class="ri-map-pin-line"></i>
						<span><?php echo esc_html( $event_city ?: $event_address ); ?></span>
						<?php if ( $event_start_time ) : ?>
							<span class="yoha"><?php echo esc_html( $event_start_time ); ?><?php echo $event_end_time ? ' - ' . esc_html( $event_end_time ) : ''; ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- BODY -->
	<div class="event-body">

		<!-- Quick actions -->
		<div class="quick-actions">
			<div class="quick-action" id="favoriteTrigger">
				<div class="quick-action-icon">
					<i class="ri-rocket-line"></i>
				</div>
				<div class="quick-action-label">Interessado</div>
			</div>

			<div class="quick-action" onclick="navigator.share({title: '<?php echo esc_js( $event_title ); ?>', url: window.location.href})">
				<div class="quick-action-icon">
					<i class="ri-share-forward-line"></i>
				</div>
				<div class="quick-action-label">Compartilhar</div>
			</div>
		</div>

		<!-- Interested avatars -->
		<div class="rsvp-row">
			<div class="avatars-explosion">
				<?php
				$result_count = $total_interested;
				foreach ( $visible_ids as $user_id ) :
					$user = get_userdata( $user_id );
					if ( ! $user ) {
						continue;
					}
					$avatar_url = get_avatar_url( $user_id, array( 'size' => 40 ) );
					?>
					<div class="avatar" title="<?php echo esc_attr( $user->display_name ); ?>">
						<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $user->display_name ); ?>">
					</div>
				<?php endforeach; ?>

				<?php if ( $hidden_count > 0 ) : ?>
					<div class="avatar-count">+<?php echo esc_html( $hidden_count ); ?></div>
				<?php endif; ?>

				<div class="interested-text" id="result"><?php echo esc_html( $result_count ); ?> interessado<?php echo $result_count !== 1 ? 's' : ''; ?></div>
			</div>
		</div>

		<!-- Info -->
		<section class="section">
			<div class="info-card">
				<div class="info-text">
					<?php the_content(); ?>
				</div>
			</div>

			<!-- Music Tags Marquee -->
			<?php if ( ! empty( $sounds ) ) : ?>
				<div class="music-tags-marquee">
					<div class="music-tags-track">
						<?php foreach ( $sounds as $sound ) : ?>
							<span class="music-tag"><?php echo esc_html( $sound ); ?></span>
						<?php endforeach; ?>
						<?php foreach ( $sounds as $sound ) : ?>
							<span class="music-tag"><?php echo esc_html( $sound ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</section>

		<!-- PROMO GALLERY (NEW SECTION) -->
		<?php if ( ! empty( $promo_images ) ) : ?>
		<section class="section">
			<h2 class="section-title"><i class="ri-image-line"></i> Galeria</h2>
			<div class="promo-gallery-slider">
				<div class="promo-track" id="promoTrack">
					<?php foreach ( $promo_images as $image_url ) : ?>
						<div class="promo-slide">
							<img src="<?php echo esc_url( $image_url ); ?>" alt="Promo">
						</div>
					<?php endforeach; ?>
				</div>

				<?php if ( count( $promo_images ) > 1 ) : ?>
				<div class="promo-controls">
					<button class="promo-prev"><i class="ri-arrow-left-s-line"></i></button>
					<button class="promo-next"><i class="ri-arrow-right-s-line"></i></button>
				</div>
				<?php endif; ?>
			</div>
		</section>
		<?php endif; ?>

		<!-- Line-up -->
		<section class="section" id="route_LINE">
			<h2 class="section-title"><i class="ri-disc-line"></i> Line-up</h2>
			<div class="lineup-list">
				<?php
				foreach ( $dj_slots as $slot ) :
					$dj = get_post( $slot['dj_id'] );
					if ( ! $dj ) {
						continue;
					}

					$dj_name      = $dj->post_title;
					$dj_permalink = get_permalink( $dj->ID );
					$dj_image     = get_the_post_thumbnail_url( $dj->ID, 'thumbnail' );
					?>
					<div class="lineup-card">
						<?php if ( $dj_image ) : ?>
							<img src="<?php echo esc_url( $dj_image ); ?>" alt="<?php echo esc_attr( $dj_name ); ?>" class="lineup-avatar-img">
						<?php else : ?>
							<div class="lineup-avatar-fallback"><?php echo esc_html( apollo_initials( $dj_name ) ); ?></div>
						<?php endif; ?>

						<div class="lineup-info">
							<h3 class="lineup-name">
								<a href="<?php echo esc_url( $dj_permalink ); ?>" target="_blank" class="dj-link"><?php echo esc_html( $dj_name ); ?></a>
							</h3>
							<?php if ( $slot['start'] && $slot['end'] ) : ?>
								<div class="lineup-time">
									<i class="ri-time-line"></i>
									<span><?php echo esc_html( $slot['start'] ); ?> - <?php echo esc_html( $slot['end'] ); ?></span>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<!-- Route + Venue + Map -->
		<section class="section" id="route_ROUTE">
			<h2 class="section-title"><i class="ri-map-pin-line"></i> Local</h2>

			<!-- Venue Images Slider -->
			<?php if ( ! empty( $venue_images ) ) : ?>
			<div class="local-images-slider">
				<div class="local-images-track" id="localTrack">
					<?php foreach ( $venue_images as $image_url ) : ?>
						<div class="local-image">
							<img src="<?php echo esc_url( $image_url ); ?>" alt="Venue">
						</div>
					<?php endforeach; ?>
				</div>

				<?php if ( count( $venue_images ) > 1 ) : ?>
				<div class="slider-nav" id="localDots"></div>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<!-- Map -->
			<div class="map-view" id="mapView">
				<?php if ( $coords['lat'] && $coords['lng'] ) : ?>
					<div id="eventMap" style="width: 100%; height: 100%; border-radius: var(--radius-card);"></div>
				<?php else : ?>
					Mapa não disponível
				<?php endif; ?>
			</div>

			<!-- Route Input -->
			<div class="route-controls">
				<div class="route-input glass">
					<i class="ri-map-pin-line"></i>
					<input type="text" id="origin-input" placeholder="Seu endereço de partida">
				</div>

				<button id="route-btn" class="route-button">
					<i class="ri-send-plane-line"></i>
				</button>
			</div>
		</section>

		<!-- Tickets -->
		<section class="section" id="route_TICKETS">
			<h2 class="section-title">
				<i class="ri-ticket-2-line"></i> Acessos
			</h2>

			<div class="tickets-grid">
				<?php if ( $tickets_url ) : ?>
				<a href="<?php echo esc_url( $tickets_url ); ?>?ref=apollo.rio.br" class="ticket-card" target="_blank">
					<div class="ticket-icon"><i class="ri-ticket-line"></i></div>
					<div class="ticket-info">
						<h3 class="ticket-name"><span id="changingword">Biglietti</span></h3>
						<span class="ticket-cta">Acessar Bilheteria Digital →</span>
					</div>
				</a>
				<?php endif; ?>

				<!-- Apollo Coupon Detail -->
				<div class="apollo-coupon-detail">
					<i class="ri-coupon-3-line"></i>
					<span>Verifique se o cupom <strong>APOLLO</strong> está ativo com desconto</span>
					<button class="copy-code-mini" onclick="copyPromoCode()">
						<i class="ri-file-copy-fill"></i>
					</button>
				</div>

				<!-- Apollo Lista Amiga -->
				<?php if ( $guestlist_url ) : ?>
				<a href="<?php echo esc_url( $guestlist_url ); ?>" class="ticket-card" target="_blank">
					<div class="ticket-icon">
						<i class="ri-list-check"></i>
					</div>
					<div class="ticket-info">
						<h3 class="ticket-name">Lista Amiga</h3>
						<span class="ticket-cta">Ver Lista Amiga →</span>
					</div>
				</a>
				<?php else : ?>
				<div class="ticket-card disabled">
					<div class="ticket-icon">
						<i class="ri-list-check"></i>
					</div>
					<div class="ticket-info">
						<h3 class="ticket-name">Lista Amiga</h3>
						<span class="ticket-cta">Ver Lista Amiga →</span>
					</div>
				</div>
				<?php endif; ?>
			</div>
		</section>

		<!-- FINAL EVENT IMAGE (NEW) -->
		<?php if ( $final_image ) : ?>
		<section class="section">
			<div class="secondary-image">
				<img src="<?php echo esc_url( $final_image ); ?>" alt="Event Final">
			</div>
		</section>
		<?php endif; ?>

		<!-- Spacer for bottom bar -->
		<div style="height:120px;"></div>

	</div><!-- /event-body -->

	<!-- Bottom Bar -->
	<div class="bottom-bar">
		<a href="#route_TICKETS" class="bottom-btn primary" id="bottomTicketBtn">
			<i class="ri-ticket-fill"></i>
			<span id="changingword">Tickets</span>
		</a>

		<button class="bottom-btn secondary" id="bottomShareBtn">
			<i class="ri-share-forward-line"></i>
		</button>
	</div>

</div><!-- /mobile-container -->

<script>
(function(){
	'use strict';

	// Bottom Ticket word animation
	var words = [
		'Entradas',
		'Ingressos',
		'Billets',
		'Ticket',
		'Acessos',
		'Biglietti'
	], i = 0;
	var elem = document.getElementById('changingword');
	// set initial word
	if (elem) {
		elem.textContent = words[i];

		function fadeOut(el, duration, callback) {
			el.style.opacity = 1;
			var start = performance.now();
			function step(timestamp) {
				var progress = (timestamp - start) / duration;
				el.style.opacity = Math.max(1 - progress, 0);
				if (progress < 1) {
					requestAnimationFrame(step);
				} else {
					callback();
				}
			}
			requestAnimationFrame(step);
		}

		function fadeIn(el, duration, callback) {
			el.style.opacity = 0;
			el.style.display = 'inline-block';
			var start = performance.now();
			function step(timestamp) {
				var progress = (timestamp - start) / duration;
				el.style.opacity = Math.min(progress, 1);
				if (progress < 1) {
					requestAnimationFrame(step);
				} else {
					callback();
				}
			}
			requestAnimationFrame(step);
		}

		setInterval(function(){
			fadeOut(elem, 500, function() {
				i = (i + 1) % words.length;
				elem.textContent = words[i];
				fadeIn(elem, 500, function() {});
			});
		}, 4000);
	}

	// Copy Promo Code
	window.copyPromoCode = function() {
		const code = '<?php echo esc_js( $coupon_code ); ?>';
		navigator.clipboard.writeText(code).then(() => {
			// Simple feedback
			const btn = event.target.closest('.copy-code-mini');
			const originalIcon = btn.innerHTML;
			btn.innerHTML = '<i class="ri-check-line"></i>';
			setTimeout(() => {
				btn.innerHTML = originalIcon;
			}, 2000);
		});
	}

	// Share Function
	document.getElementById('bottomShareBtn')?.addEventListener('click', () => {
		if (navigator.share) {
			navigator.share({
				title: '<?php echo esc_js( $event_title ); ?>',
				url: window.location.href
			});
		} else {
			// Fallback: copy to clipboard
			navigator.clipboard.writeText(window.location.href);
		}
	});

	// Route Button
	const routeBtn = document.getElementById('route-btn');
	if (routeBtn) {
		routeBtn.addEventListener('click', () => {
			const origin = document.getElementById('origin-input')?.value;
			if (origin && window.eventCoords) {
				const url = `https://www.google.com/maps/dir/${encodeURIComponent(origin)}/${window.eventCoords.lat},${window.eventCoords.lng}`;
				window.open(url, '_blank');
			}
		});
	}

	// PROMO GALLERY SLIDER
	const promoTrack = document.getElementById('promoTrack');
	const promoSlides = promoTrack?.children.length || 0;
	let currentPromo = 0;

	document.querySelector('.promo-prev')?.addEventListener('click', () => {
		currentPromo = (currentPromo - 1 + promoSlides) % promoSlides;
		promoTrack.style.transform = `translateX(-${currentPromo * 100}%)`;
	});

	document.querySelector('.promo-next')?.addEventListener('click', () => {
		currentPromo = (currentPromo + 1) % promoSlides;
		promoTrack.style.transform = `translateX(-${currentPromo * 100}%)`;
	});

	// VENUE IMAGES SLIDER (5 images with infinite loop)
	const localTrack = document.getElementById('localTrack');
	const localDots = document.getElementById('localDots');

	if (localTrack && localTrack.children.length > 0) {
		const slideCount = localTrack.children.length;
		let currentSlide = 0;

		// Create dots
		for (let i = 0; i < slideCount; i++) {
			const dot = document.createElement('button');
			dot.className = 'slider-dot' + (i === 0 ? ' active' : '');
			dot.addEventListener('click', () => goToSlide(i));
			localDots.appendChild(dot);
		}

		function goToSlide(index) {
			currentSlide = index;
			localTrack.style.transform = `translateX(-${index * 100}%)`;
			updateDots();
		}

		function updateDots() {
			document.querySelectorAll('.slider-dot').forEach((dot, i) => {
				dot.classList.toggle('active', i === currentSlide);
			});
		}

		// Auto-advance with true infinite loop
		setInterval(() => {
			currentSlide++;
			if(currentSlide >= slideCount) {
				localTrack.style.transition = 'none';
				currentSlide = 0;
				localTrack.style.transform = `translateX(0)`;
				localTrack.offsetHeight;
				setTimeout(() => {
					currentSlide = 1;
					localTrack.style.transition = 'transform 0.5s ease';
					localTrack.style.transform = `translateX(-100%)`;
					updateDots();
				}, 50);
			} else {
				localTrack.style.transform = `translateX(-${currentSlide * 100}%)`;
				updateDots();
			}
		}, 4000);
	}

	// Map initialization
	<?php if ( $coords['lat'] && $coords['lng'] ) : ?>
	if (typeof L !== 'undefined') {
		const map = L.map('eventMap').setView([<?php echo esc_js( $coords['lat'] ); ?>, <?php echo esc_js( $coords['lng'] ); ?>], 15);
		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '© OpenStreetMap contributors'
		}).addTo(map);

		L.marker([<?php echo esc_js( $coords['lat'] ); ?>, <?php echo esc_js( $coords['lng'] ); ?>]).addTo(map)
			.bindPopup('<?php echo esc_js( $event_title ); ?>');

		window.eventCoords = { lat: <?php echo esc_js( $coords['lat'] ); ?>, lng: <?php echo esc_js( $coords['lng'] ); ?> };
	}
	<?php endif; ?>

	// Interesse toggle
	document.getElementById('favoriteTrigger')?.addEventListener('click', function(event) {
		event.preventDefault();

		const iconContainer = this.querySelector('.quick-action-icon');
		const icon = iconContainer.querySelector('i');
		const avatarsContainer = document.querySelector('.avatars-explosion');
		const countEl = avatarsContainer.querySelector('.avatar-count');
		const resultEl = document.getElementById('result');
		const maxVisible = 10;

		function updateResult() {
			const visibleCount = avatarsContainer.querySelectorAll('.avatar').length;
			const hiddenCount = parseInt(countEl?.textContent.replace('+', '')) || 0;
			resultEl.textContent = visibleCount + hiddenCount + ' interessado' + (visibleCount + hiddenCount !== 1 ? 's' : '');
		}

		if (icon.classList.contains('ri-rocket-line')) {
			// Add interest
			icon.classList.remove('ri-rocket-line');
			icon.classList.add('ri-rocket-fill');
			iconContainer.style.background = 'var(--text-secondary)';
			icon.style.color = 'white';

			// Add current user avatar (placeholder for now)
			const newAvatar = document.createElement('div');
			newAvatar.className = 'avatar';
			newAvatar.innerHTML = '<img src="https://via.placeholder.com/40" alt="You">';
			avatarsContainer.insertBefore(newAvatar, avatarsContainer.firstElementChild);

			// Update count
			updateResult();
		} else {
			// Remove interest
			icon.classList.remove('ri-rocket-fill');
			icon.classList.add('ri-rocket-line');
			iconContainer.style.background = '';
			icon.style.color = '';

			// Remove user avatar
			const firstAvatar = avatarsContainer.querySelector('.avatar');
			if (firstAvatar) {
				firstAvatar.remove();
			}

			// Update count
			updateResult();
		}
	});

	// Initial update for interested count
	const avatarsContainer = document.querySelector('.avatars-explosion');
	const countEl = avatarsContainer?.querySelector('.avatar-count');
	const resultEl = document.getElementById('result');

	function initialUpdateResult() {
		const visibleCount = avatarsContainer.querySelectorAll('.avatar').length;
		const hiddenCount = parseInt(countEl?.textContent.replace('+', '')) || 0;
		resultEl.textContent = visibleCount + hiddenCount + ' interessado' + (visibleCount + hiddenCount !== 1 ? 's' : '');
	}

	initialUpdateResult();

	// Bottom Ticket Button Smooth Scroll
	document.getElementById('bottomTicketBtn')?.addEventListener('click', (e) => {
		e.preventDefault();
		document.getElementById('route_TICKETS').scrollIntoView({
			behavior: 'smooth',
			block: 'start'
		});
	});
})();
</script>

	<?php wp_footer(); ?>
</body>
</html>
<?php endwhile; ?>
