<?php

// phpcs:ignoreFile

/**
 * Apollo Events Placeholder Registry and Access API
 *
 * Provides a centralized registry of all event placeholders and helper functions
 * to safely access placeholder values in templates and shortcodes.
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

// Load helper at top level for all functions
if (! class_exists('Apollo_Event_Data_Helper')) {
    require_once plugin_dir_path(__FILE__) . 'helpers/event-data-helper.php';
}

/**
 * Returns registry of Apollo Event placeholders.
 *
 * Each entry describes:
 *  - id          : machine-readable key
 *  - label       : human label
 *  - description : what it shows
 *  - source      : 'meta' | 'taxonomy' | 'computed'
 *  - key         : meta key or taxonomy name (when relevant)
 *  - example     : example value
 *
 * @return array Associative array of placeholder definitions
 */
function apollo_events_get_placeholders()
{
    return [
        'event_id' => [
            'id'          => 'event_id',
            'label'       => __('Event ID', 'apollo-events-manager'),
            'description' => __('Internal WordPress post ID of the event.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => 'ID',
            'example'     => '143',
        ],
        'title' => [
            'id'          => 'title',
            'label'       => __('Event Title', 'apollo-events-manager'),
            'description' => __('The event post title.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => 'post_title',
            'example'     => 'Apollo Sunset Sessions',
        ],
        'start_date' => [
            'id'          => 'start_date',
            'label'       => __('Start Date (Y-m-d)', 'apollo-events-manager'),
            'description' => __('Raw start date from _event_start_date meta normalized to Y-m-d format.', 'apollo-events-manager'),
            'source'      => 'meta',
            'key'         => '_event_start_date',
            'example'     => '2025-11-22',
        ],
        'start_day' => [
            'id'          => 'start_day',
            'label'       => __('Day (dd)', 'apollo-events-manager'),
            'description' => __('Day of month extracted from _event_start_date (1-31, no leading zero).', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_start_date',
            'example'     => '22',
        ],
        'start_month_pt' => [
            'id'          => 'start_month_pt',
            'label'       => __('Month (PT-BR: jan, fev, ...)', 'apollo-events-manager'),
            'description' => __('Portuguese month abbreviation extracted from _event_start_date.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_start_date',
            'example'     => 'nov',
        ],
        'start_time' => [
            'id'          => 'start_time',
            'label'       => __('Start Time (HH:MM:SS)', 'apollo-events-manager'),
            'description' => __('Event start time from _event_start_time meta.', 'apollo-events-manager'),
            'source'      => 'meta',
            'key'         => '_event_start_time',
            'example'     => '22:00:00',
        ],
        'location' => [
            'id'          => 'location',
            'label'       => __('Location Name', 'apollo-events-manager'),
            'description' => __('The venue name (before the "|" separator) from _event_location, or from related event_local post.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_location / _event_local_ids',
            'example'     => 'D-Edge Rio',
        ],
        'location_area' => [
            'id'          => 'location_area',
            'label'       => __('Location Area', 'apollo-events-manager'),
            'description' => __('Area / neighborhood after the "|" separator in _event_location, or city/state from event_local post.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_location / _event_local_ids',
            'example'     => 'Centro, RJ',
        ],
        'location_full' => [
            'id'          => 'location_full',
            'label'       => __('Location Full (name | area)', 'apollo-events-manager'),
            'description' => __('Complete location string with name and area combined.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_location / _event_local_ids',
            'example'     => 'D-Edge Rio | Centro, RJ',
        ],
        'dj_list' => [
            'id'          => 'dj_list',
            'label'       => __('DJ Line-up (formatted)', 'apollo-events-manager'),
            'description' => __('Final DJ list string: comma-separated names from _event_dj_ids, _timetable, or _dj_name.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids / _timetable / _dj_name',
            'example'     => 'DJ Alpha, DJ Beta, DJ Gamma',
        ],
        'dj_count' => [
            'id'          => 'dj_count',
            'label'       => __('DJ Count', 'apollo-events-manager'),
            'description' => __('Number of DJs performing at the event.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids / _timetable',
            'example'     => '3',
        ],
        'banner_url' => [
            'id'          => 'banner_url',
            'label'       => __('Banner Image URL', 'apollo-events-manager'),
            'description' => __('Event banner URL, with fallback to featured image or a default Unsplash image.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_banner',
            'example'     => 'https://example.com/banner.jpg',
        ],
        'category_slug' => [
            'id'          => 'category_slug',
            'label'       => __('Main Category Slug', 'apollo-events-manager'),
            'description' => __('The first event_listing_category slug.', 'apollo-events-manager'),
            'source'      => 'taxonomy',
            'key'         => 'event_listing_category',
            'example'     => 'music',
        ],
        'category_name' => [
            'id'          => 'category_name',
            'label'       => __('Main Category Name', 'apollo-events-manager'),
            'description' => __('The first event_listing_category name.', 'apollo-events-manager'),
            'source'      => 'taxonomy',
            'key'         => 'event_listing_category',
            'example'     => 'Music',
        ],
        'sounds_list' => [
            'id'          => 'sounds_list',
            'label'       => __('Sounds Tags', 'apollo-events-manager'),
            'description' => __('Comma-separated list of event_sounds term names (up to 3 shown in cards).', 'apollo-events-manager'),
            'source'      => 'taxonomy',
            'key'         => 'event_sounds',
            'example'     => 'House, Deep House, Techno',
        ],
        'permalink' => [
            'id'          => 'permalink',
            'label'       => __('Event Permalink', 'apollo-events-manager'),
            'description' => __('URL to the single event page.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => 'permalink',
            'example'     => 'https://example.com/evento/apollo-sunset-sessions/',
        ],
        'content' => [
            'id'          => 'content',
            'label'       => __('Event Content', 'apollo-events-manager'),
            'description' => __('The event post content (processed through the_content filter).', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => 'post_content',
            'example'     => 'Event description...',
        ],
        'excerpt' => [
            'id'          => 'excerpt',
            'label'       => __('Event Excerpt', 'apollo-events-manager'),
            'description' => __('The event post excerpt or auto-generated from content.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => 'post_excerpt',
            'example'     => 'Event description preview...',
        ],
        // === EVENT META FIELDS ===
        'video_url' => [
            'id'          => 'video_url',
            'label'       => __('YouTube Video URL', 'apollo-events-manager'),
            'description' => __('YouTube or video URL from _event_video_url meta.', 'apollo-events-manager'),
            'source'      => 'meta',
            'key'         => '_event_video_url',
            'example'     => 'https://www.youtube.com/watch?v=example',
        ],
        'end_date' => [
            'id'          => 'end_date',
            'label'       => __('End Date (Y-m-d)', 'apollo-events-manager'),
            'description' => __('Event end date from _event_end_date meta normalized to Y-m-d format.', 'apollo-events-manager'),
            'source'      => 'meta',
            'key'         => '_event_end_date',
            'example'     => '2025-11-23',
        ],
        'end_time' => [
            'id'          => 'end_time',
            'label'       => __('End Time (HH:MM:SS)', 'apollo-events-manager'),
            'description' => __('Event end time from _event_end_time meta.', 'apollo-events-manager'),
            'source'      => 'meta',
            'key'         => '_event_end_time',
            'example'     => '04:00:00',
        ],
        'country' => [
            'id'          => 'country',
            'label'       => __('Country', 'apollo-events-manager'),
            'description' => __('Event country from _event_country meta.', 'apollo-events-manager'),
            'source'      => 'meta',
            'key'         => '_event_country',
            'example'     => 'Brasil',
        ],
        'tickets_url' => [
            'id'          => 'tickets_url',
            'label'       => __('Tickets URL', 'apollo-events-manager'),
            'description' => __('External tickets URL from _tickets_ext meta.', 'apollo-events-manager'),
            'source'      => 'meta',
            'key'         => '_tickets_ext',
            'example'     => 'https://example.com/tickets',
        ],
        'cupom_ario' => [
            'id'          => 'cupom_ario',
            'label'       => __('Cupom Ario', 'apollo-events-manager'),
            'description' => __('Cupom Ario flag (0 or 1) from _cupom_ario meta.', 'apollo-events-manager'),
            'source'      => 'meta',
            'key'         => '_cupom_ario',
            'example'     => '1',
        ],
        'promo_images' => [
            'id'          => 'promo_images',
            'label'       => __('Promo Images (3)', 'apollo-events-manager'),
            'description' => __('Array of 3 promotional image URLs from _3_imagens_promo meta.', 'apollo-events-manager'),
            'source'      => 'meta',
            'key'         => '_3_imagens_promo',
            'example'     => 'https://example.com/img1.jpg, https://example.com/img2.jpg, https://example.com/img3.jpg',
        ],
        'final_image' => [
            'id'          => 'final_image',
            'label'       => __('Final Image URL', 'apollo-events-manager'),
            'description' => __('Final promotional image URL from _imagem_final meta.', 'apollo-events-manager'),
            'source'      => 'meta',
            'key'         => '_imagem_final',
            'example'     => 'https://example.com/final.jpg',
        ],
        'favorites_count' => [
            'id'          => 'favorites_count',
            'label'       => __('Favorites Count', 'apollo-events-manager'),
            'description' => __('Number of users who favorited this event from _favorites_count meta.', 'apollo-events-manager'),
            'source'      => 'meta',
            'key'         => '_favorites_count',
            'example'     => '42',
        ],
        'dj_ids' => [
            'id'          => 'dj_ids',
            'label'       => __('DJ IDs (array)', 'apollo-events-manager'),
            'description' => __('Serialized array of DJ post IDs from _event_dj_ids meta.', 'apollo-events-manager'),
            'source'      => 'meta',
            'key'         => '_event_dj_ids',
            'example'     => '92, 71',
        ],
        'local_id' => [
            'id'          => 'local_id',
            'label'       => __('Local ID', 'apollo-events-manager'),
            'description' => __('Related event_local post ID from _event_local_ids meta.', 'apollo-events-manager'),
            'source'      => 'meta',
            'key'         => '_event_local_ids',
            'example'     => '95',
        ],
        'timetable' => [
            'id'          => 'timetable',
            'label'       => __('Timetable (JSON)', 'apollo-events-manager'),
            'description' => __('Serialized timetable array with DJ slots from _event_timetable meta.', 'apollo-events-manager'),
            'source'      => 'meta',
            'key'         => '_event_timetable',
            'example'     => '[{"dj": 92, "start": "22:00", "end": "23:00"}]',
        ],
        // === TAXONOMIES ===
        'type_slug' => [
            'id'          => 'type_slug',
            'label'       => __('Event Type Slug', 'apollo-events-manager'),
            'description' => __('The first event_listing_type slug.', 'apollo-events-manager'),
            'source'      => 'taxonomy',
            'key'         => 'event_listing_type',
            'example'     => 'festival',
        ],
        'type_name' => [
            'id'          => 'type_name',
            'label'       => __('Event Type Name', 'apollo-events-manager'),
            'description' => __('The first event_listing_type name.', 'apollo-events-manager'),
            'source'      => 'taxonomy',
            'key'         => 'event_listing_type',
            'example'     => 'Festival',
        ],
        'tags_list' => [
            'id'          => 'tags_list',
            'label'       => __('Event Tags', 'apollo-events-manager'),
            'description' => __('Comma-separated list of event_listing_tag term names.', 'apollo-events-manager'),
            'source'      => 'taxonomy',
            'key'         => 'event_listing_tag',
            'example'     => 'underground, outdoor, techno',
        ],
        // === LOCAL (event_local) FIELDS ===
        'local_name' => [
            'id'          => 'local_name',
            'label'       => __('Local Name', 'apollo-events-manager'),
            'description' => __('Local venue name from related event_local post (_local_name meta or post title).', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_local_ids -> _local_name',
            'example'     => 'D-Edge Rio',
        ],
        'local_description' => [
            'id'          => 'local_description',
            'label'       => __('Local Description', 'apollo-events-manager'),
            'description' => __('Local venue description from related event_local post _local_description meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_local_ids -> _local_description',
            'example'     => 'Legendary nightclub in Rio...',
        ],
        'local_address' => [
            'id'          => 'local_address',
            'label'       => __('Local Address', 'apollo-events-manager'),
            'description' => __('Local venue address from related event_local post _local_address meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_local_ids -> _local_address',
            'example'     => 'Rua das Laranjeiras, 123',
        ],
        'local_city' => [
            'id'          => 'local_city',
            'label'       => __('Local City', 'apollo-events-manager'),
            'description' => __('Local venue city from related event_local post _local_city meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_local_ids -> _local_city',
            'example'     => 'Rio de Janeiro',
        ],
        'local_coordinates' => [
            'id'          => 'local_coordinates',
            'label'       => __('Local Coordinates (lat,lng)', 'apollo-events-manager'),
            'description' => __('Local venue coordinates from _local_latitude and _local_longitude meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_local_ids -> _local_latitude, _local_longitude',
            'example'     => '-22.9068, -43.1729',
        ],
        'local_website' => [
            'id'          => 'local_website',
            'label'       => __('Local Website', 'apollo-events-manager'),
            'description' => __('Local venue website from related event_local post _local_website meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_local_ids -> _local_website',
            'example'     => 'https://dedgerio.com.br',
        ],
        'local_instagram' => [
            'id'          => 'local_instagram',
            'label'       => __('Local Instagram', 'apollo-events-manager'),
            'description' => __('Local venue Instagram from related event_local post _local_instagram meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_local_ids -> _local_instagram',
            'example'     => '@dedgerio',
        ],
        'local_facebook' => [
            'id'          => 'local_facebook',
            'label'       => __('Local Facebook', 'apollo-events-manager'),
            'description' => __('Local venue Facebook from related event_local post _local_facebook meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_local_ids -> _local_facebook',
            'example'     => 'https://facebook.com/dedgerio',
        ],
        // === DJ (event_dj) FIELDS ===
        'dj_name' => [
            'id'          => 'dj_name',
            'label'       => __('DJ Name (first)', 'apollo-events-manager'),
            'description' => __('Name of the first DJ from related event_dj post (_dj_name meta or post title).', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_name',
            'example'     => 'DJ Alpha',
        ],
        'dj_bio' => [
            'id'          => 'dj_bio',
            'label'       => __('DJ Bio (first)', 'apollo-events-manager'),
            'description' => __('Bio of the first DJ from related event_dj post _dj_bio meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_bio',
            'example'     => 'Electronic music producer...',
        ],
        'dj_website' => [
            'id'          => 'dj_website',
            'label'       => __('DJ Website (first)', 'apollo-events-manager'),
            'description' => __('Website of the first DJ from related event_dj post _dj_website meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_website',
            'example'     => 'https://djalpha.com',
        ],
        'dj_soundcloud' => [
            'id'          => 'dj_soundcloud',
            'label'       => __('DJ SoundCloud (first)', 'apollo-events-manager'),
            'description' => __('SoundCloud URL of the first DJ from related event_dj post _dj_soundcloud meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_soundcloud',
            'example'     => 'https://soundcloud.com/djalpha',
        ],
        'dj_instagram' => [
            'id'          => 'dj_instagram',
            'label'       => __('DJ Instagram (first)', 'apollo-events-manager'),
            'description' => __('Instagram handle of the first DJ from related event_dj post _dj_instagram meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_instagram',
            'example'     => '@djalpha',
        ],
        'dj_facebook' => [
            'id'          => 'dj_facebook',
            'label'       => __('DJ Facebook (first)', 'apollo-events-manager'),
            'description' => __('Facebook URL of the first DJ from related event_dj post _dj_facebook meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_facebook',
            'example'     => 'https://facebook.com/djalpha',
        ],
        'dj_bandcamp' => [
            'id'          => 'dj_bandcamp',
            'label'       => __('DJ Bandcamp (first)', 'apollo-events-manager'),
            'description' => __('Bandcamp URL of the first DJ from related event_dj post _dj_bandcamp meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_bandcamp',
            'example'     => 'https://djalpha.bandcamp.com',
        ],
        'dj_spotify' => [
            'id'          => 'dj_spotify',
            'label'       => __('DJ Spotify (first)', 'apollo-events-manager'),
            'description' => __('Spotify artist URL of the first DJ from related event_dj post _dj_spotify meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_spotify',
            'example'     => 'https://open.spotify.com/artist/...',
        ],
        'dj_youtube' => [
            'id'          => 'dj_youtube',
            'label'       => __('DJ YouTube (first)', 'apollo-events-manager'),
            'description' => __('YouTube channel URL of the first DJ from related event_dj post _dj_youtube meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_youtube',
            'example'     => 'https://youtube.com/@djalpha',
        ],
        'dj_mixcloud' => [
            'id'          => 'dj_mixcloud',
            'label'       => __('DJ Mixcloud (first)', 'apollo-events-manager'),
            'description' => __('Mixcloud profile URL of the first DJ from related event_dj post _dj_mixcloud meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_mixcloud',
            'example'     => 'https://mixcloud.com/djalpha',
        ],
        'dj_beatport' => [
            'id'          => 'dj_beatport',
            'label'       => __('DJ Beatport (first)', 'apollo-events-manager'),
            'description' => __('Beatport artist page URL of the first DJ from related event_dj post _dj_beatport meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_beatport',
            'example'     => 'https://beatport.com/artist/dj-alpha/...',
        ],
        'dj_resident_advisor' => [
            'id'          => 'dj_resident_advisor',
            'label'       => __('DJ Resident Advisor (first)', 'apollo-events-manager'),
            'description' => __('Resident Advisor profile URL of the first DJ from related event_dj post _dj_resident_advisor meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_resident_advisor',
            'example'     => 'https://ra.co/dj/djalpha',
        ],
        'dj_twitter' => [
            'id'          => 'dj_twitter',
            'label'       => __('DJ Twitter/X (first)', 'apollo-events-manager'),
            'description' => __('Twitter/X handle of the first DJ from related event_dj post _dj_twitter meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_twitter',
            'example'     => '@djalpha',
        ],
        'dj_tiktok' => [
            'id'          => 'dj_tiktok',
            'label'       => __('DJ TikTok (first)', 'apollo-events-manager'),
            'description' => __('TikTok profile URL of the first DJ from related event_dj post _dj_tiktok meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_tiktok',
            'example'     => 'https://tiktok.com/@djalpha',
        ],
        'dj_image' => [
            'id'          => 'dj_image',
            'label'       => __('DJ Image (first)', 'apollo-events-manager'),
            'description' => __('Image URL of the first DJ from related event_dj post _dj_image meta (URL or attachment ID).', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_image',
            'example'     => 'https://example.com/dj-photo.jpg',
        ],
        'dj_original_project_1' => [
            'id'          => 'dj_original_project_1',
            'label'       => __('DJ Original Project 1 (first)', 'apollo-events-manager'),
            'description' => __('First original project name of the first DJ from related event_dj post _dj_original_project_1 meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_original_project_1',
            'example'     => 'Project Alpha',
        ],
        'dj_original_project_2' => [
            'id'          => 'dj_original_project_2',
            'label'       => __('DJ Original Project 2 (first)', 'apollo-events-manager'),
            'description' => __('Second original project name of the first DJ from related event_dj post _dj_original_project_2 meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_original_project_2',
            'example'     => 'Project Beta',
        ],
        'dj_original_project_3' => [
            'id'          => 'dj_original_project_3',
            'label'       => __('DJ Original Project 3 (first)', 'apollo-events-manager'),
            'description' => __('Third original project name of the first DJ from related event_dj post _dj_original_project_3 meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_original_project_3',
            'example'     => 'Project Gamma',
        ],
        'dj_set_url' => [
            'id'          => 'dj_set_url',
            'label'       => __('DJ Set URL (first)', 'apollo-events-manager'),
            'description' => __('DJ Set URL of the first DJ from related event_dj post _dj_set_url meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_set_url',
            'example'     => 'https://soundcloud.com/djalpha/set',
        ],
        'dj_media_kit_url' => [
            'id'          => 'dj_media_kit_url',
            'label'       => __('DJ Media Kit URL (first)', 'apollo-events-manager'),
            'description' => __('Media Kit URL of the first DJ from related event_dj post _dj_media_kit_url meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_media_kit_url',
            'example'     => 'https://example.com/djalpha-media-kit.pdf',
        ],
        'dj_rider_url' => [
            'id'          => 'dj_rider_url',
            'label'       => __('DJ Rider URL (first)', 'apollo-events-manager'),
            'description' => __('Rider URL of the first DJ from related event_dj post _dj_rider_url meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_rider_url',
            'example'     => 'https://example.com/djalpha-rider.pdf',
        ],
        'dj_mix_url' => [
            'id'          => 'dj_mix_url',
            'label'       => __('DJ Mix URL (first)', 'apollo-events-manager'),
            'description' => __('DJ Mix URL of the first DJ from related event_dj post _dj_mix_url meta.', 'apollo-events-manager'),
            'source'      => 'computed',
            'key'         => '_event_dj_ids[0] -> _dj_mix_url',
            'example'     => 'https://soundcloud.com/djalpha/mix',
        ],
    ];
}

/**
 * Returns associative array of placeholder defaults (string values).
 *
 * @return array
 */
function apollo_events_placeholder_defaults()
{
    $defaults = [
        // Visual placeholders (images/banners)
        'APOLLO_PLACEHOLDER_EVENT_BANNER'            => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070',
        'APOLLO_PLACEHOLDER_HIGHLIGHT_BANNER'        => 'https://images.unsplash.com/photo-1506157786151-b8491531f063?q=80&w=2070&auto=format&fit=crop',
        'APOLLO_PLACEHOLDER_HIGHLIGHT_BANNER_STATIC' => 'https://images.unsplash.com/photo-1506157786151-b8491531f063?q=80&w=2070&auto=format&fit=crop',
        'APOLLO_PLACEHOLDER_PROMO_IMAGE'             => 'https://via.placeholder.com/400x300',
        'APOLLO_PLACEHOLDER_DJ_IMAGE'                => 'https://via.placeholder.com/120x120?text=DJ',
        'APOLLO_PLACEHOLDER_LOCAL_IMAGE'             => 'https://via.placeholder.com/400x300?text=Local',

        // Textual placeholders
        'APOLLO_PLACEHOLDER_NO_EVENTS'       => __('Nenhum evento encontrado.', 'apollo-events-manager'),
        'APOLLO_PLACEHOLDER_EVENTS_ERROR'    => __('Erro ao carregar eventos. Tente novamente.', 'apollo-events-manager'),
        'APOLLO_PLACEHOLDER_SEARCH_INPUT'    => __('Buscar eventos...', 'apollo-events-manager'),
        'APOLLO_PLACEHOLDER_HIGHLIGHT_TEXT'  => __('A Retrospectiva Clubber 2026 está chegando! Em breve liberaremos novidades — fique ligado.', 'apollo-events-manager'),
        'APOLLO_PLACEHOLDER_EVENT_NOT_FOUND' => __('Evento não encontrado.', 'apollo-events-manager'),
        'APOLLO_PLACEHOLDER_LINEUP_TBA'      => __('Line-up em breve', 'apollo-events-manager'),
        'APOLLO_PLACEHOLDER_LOCATION_TBA'    => __('Local a confirmar', 'apollo-events-manager'),
        'APOLLO_PLACEHOLDER_ROUTE_INPUT'     => __('Seu endereço de partida', 'apollo-events-manager'),
        'APOLLO_PLACEHOLDER_MAP_UNAVAILABLE' => '<p style="color:#999;text-align:center;"><i class="ri-map-pin-line" style="font-size:2rem;"></i><br>Mapa disponível em breve</p>',
        'APOLLO_PLACEHOLDER_MAP_ERROR_LOG'   => '⚠️ Map not displayed - no coordinates found for event {event_id}',
        'APOLLO_PLACEHOLDER_DATE_DAY'        => '--',
        'APOLLO_PLACEHOLDER_DATE_MONTH'      => '---',
        'APOLLO_PLACEHOLDER_CURRENT_TIME'    => '--:--',
    ];

    return apply_filters('apollo_events_placeholder_defaults', $defaults);
}

/**
 * Retrieves a placeholder value with optional overrides stored in the database.
 *
 * @param string $key
 * @param string $default
 * @return string
 */
function apollo_get_placeholder($key, $default = '')
{
    $key = strtoupper(trim($key));
    if ($key === '') {
        return $default;
    }

    $overrides = get_option('apollo_events_custom_placeholders', []);
    if (! is_array($overrides)) {
        $overrides = [];
    }

    if (array_key_exists($key, $overrides) && $overrides[ $key ] !== '') {
        return apply_filters('apollo_get_placeholder_value', $overrides[ $key ], $key);
    }

    $defaults = apollo_events_placeholder_defaults();
    if (array_key_exists($key, $defaults) && $defaults[ $key ] !== '') {
        return apply_filters('apollo_get_placeholder_value', $defaults[ $key ], $key);
    }

    return apply_filters('apollo_get_placeholder_value', $default, $key);
}

/**
 * Updates (or clears) a placeholder override value.
 *
 * @param string $key
 * @param string $value
 * @return bool True on success, false on failure.
 */
function apollo_update_placeholder($key, $value)
{
    $key = strtoupper(trim($key));
    if ($key === '') {
        return false;
    }

    $overrides = get_option('apollo_events_custom_placeholders', []);
    if (! is_array($overrides)) {
        $overrides = [];
    }

    if ($value === '' || $value === null) {
        unset($overrides[ $key ]);
    } else {
        $overrides[ $key ] = $value;
    }

    return update_option('apollo_events_custom_placeholders', $overrides, false);
}

/**
 * Get a specific Apollo Event placeholder value for a given event.
 *
 * @param string   $placeholder_id One of the keys from apollo_events_get_placeholders().
 * @param int|null $event_id       Defaults to current post ID if not given.
 * @param array    $args           Optional extra args (for future extension).
 * @return string                   Safe string value (HTML or plain text depending on field).
 */
function apollo_event_get_placeholder_value($placeholder_id, $event_id = null, $args = [])
{
    // Resolve event ID
    if ($event_id === null) {
        $event_id = get_the_ID();
    }

    $event_id = absint($event_id);
    if ($event_id <= 0) {
        return '';
    }

    // Verify event exists and is correct post type
    $post = get_post($event_id);
    if (! $post || $post->post_type !== 'event_listing') {
        return '';
    }

    // Switch on placeholder ID
    switch ($placeholder_id) {
        case 'event_id':
            return (string) $event_id;

        case 'title':
            return esc_html(get_the_title($event_id));

        case 'start_date':
            $date = get_post_meta($event_id, '_event_start_date', true);
            if (empty($date)) {
                return '';
            }
            // Reuse existing helper function from plugin main file
            if (function_exists('apollo_eve_parse_start_date')) {
                $date_info = apollo_eve_parse_start_date($date);

                return $date_info['iso_date'];
            }
            // Fallback if helper not available
            $timestamp = strtotime($date);

            return $timestamp ? date_i18n('Y-m-d', $timestamp) : '';

        case 'start_day':
            $date = get_post_meta($event_id, '_event_start_date', true);
            if (empty($date)) {
                return '';
            }
            // Reuse existing helper function from plugin main file
            if (function_exists('apollo_eve_parse_start_date')) {
                $date_info = apollo_eve_parse_start_date($date);

                return $date_info['day'];
            }
            // Fallback if helper not available
            $timestamp = strtotime($date);

            return $timestamp ? date_i18n('j', $timestamp) : '';

        case 'start_month_pt':
            $date = get_post_meta($event_id, '_event_start_date', true);
            if (empty($date)) {
                return '';
            }
            // Reuse existing helper function from plugin main file
            if (function_exists('apollo_eve_parse_start_date')) {
                $date_info = apollo_eve_parse_start_date($date);

                return $date_info['month_pt'];
            }
            // Fallback if helper not available
            $timestamp = strtotime($date);
            if (! $timestamp) {
                return '';
            }
            $month       = date_i18n('M', $timestamp);
            $month_lower = strtolower($month);
            $month_map   = [
                'jan' => 'jan',
                'feb' => 'fev',
                'mar' => 'mar',
                'apr' => 'abr',
                'may' => 'mai',
                'jun' => 'jun',
                'jul' => 'jul',
                'aug' => 'ago',
                'sep' => 'set',
                'oct' => 'out',
                'nov' => 'nov',
                'dec' => 'dez',
            ];

            return isset($month_map[ $month_lower ]) ? $month_map[ $month_lower ] : $month_lower;

        case 'start_time':
            $time = get_post_meta($event_id, '_event_start_time', true);

            return $time ? esc_html($time) : '';

        case 'location':
            return apollo_event_get_location_name($event_id);

        case 'location_area':
            return apollo_event_get_location_area($event_id);

        case 'location_full':
            $name = apollo_event_get_location_name($event_id);
            $area = apollo_event_get_location_area($event_id);
            if (empty($name)) {
                return '';
            }
            if (empty($area)) {
                return esc_html($name);
            }

            return esc_html($name . ' | ' . $area);

        case 'dj_list':
            $djs = apollo_event_get_dj_names($event_id);

            return ! empty($djs) ? esc_html(implode(', ', $djs)) : '';

        case 'dj_count':
            $djs = apollo_event_get_dj_names($event_id);

            return (string) count($djs);

        case 'banner_url':
            return esc_url(apollo_event_get_banner_url($event_id));

        case 'category_slug':
            $categories = wp_get_post_terms($event_id, 'event_listing_category');
            if (is_wp_error($categories) || empty($categories)) {
                return 'general';
            }

            return esc_attr($categories[0]->slug);

        case 'category_name':
            $categories = wp_get_post_terms($event_id, 'event_listing_category');
            if (is_wp_error($categories) || empty($categories)) {
                return '';
            }

            return esc_html($categories[0]->name);

        case 'sounds_list':
            $sounds = wp_get_post_terms($event_id, 'event_sounds');
            if (is_wp_error($sounds) || empty($sounds)) {
                return '';
            }
            $names = array_map(
                function ($term) {
                    return $term->name;
                },
                $sounds
            );

            return esc_html(implode(', ', $names));

        case 'permalink':
            return esc_url(get_permalink($event_id));

        case 'content':
            $content = get_post_field('post_content', $event_id);

            return apply_filters('the_content', $content);

        case 'excerpt':
            $excerpt = get_post_field('post_excerpt', $event_id);
            if (empty($excerpt)) {
                $content = get_post_field('post_content', $event_id);
                $excerpt = wp_trim_words($content, 30, '...');
            }

            return esc_html($excerpt);

            // === EVENT META FIELDS ===
        case 'video_url':
            $video = get_post_meta($event_id, '_event_video_url', true);

            return $video ? esc_url($video) : '';

        case 'end_date':
            $date = get_post_meta($event_id, '_event_end_date', true);
            if (empty($date)) {
                return '';
            }
            if (function_exists('apollo_eve_parse_start_date')) {
                $date_info = apollo_eve_parse_start_date($date);

                return $date_info['iso_date'];
            }
            $timestamp = strtotime($date);

            return $timestamp ? date_i18n('Y-m-d', $timestamp) : '';

        case 'end_time':
            $time = get_post_meta($event_id, '_event_end_time', true);

            return $time ? esc_html($time) : '';

        case 'country':
            $country = get_post_meta($event_id, '_event_country', true);

            return $country ? esc_html($country) : '';

        case 'tickets_url':
            $tickets = get_post_meta($event_id, '_tickets_ext', true);

            return $tickets ? esc_url($tickets) : '';

        case 'cupom_ario':
            $cupom = get_post_meta($event_id, '_cupom_ario', true);

            return $cupom ? (string) absint($cupom) : '0';

        case 'promo_images':
            $images = get_post_meta($event_id, '_3_imagens_promo', true);
            if (empty($images)) {
                return '';
            }
            $images_array = maybe_unserialize($images);
            if (! is_array($images_array)) {
                return '';
            }
            $urls = array_filter(array_map('esc_url', $images_array));

            return implode(', ', $urls);

        case 'final_image':
            $image = get_post_meta($event_id, '_imagem_final', true);

            return $image ? esc_url($image) : '';

        case 'favorites_count':
            $count = get_post_meta($event_id, '_favorites_count', true);

            return $count ? (string) absint($count) : '0';

        case 'dj_ids':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array)) {
                return '';
            }

            return implode(', ', array_map('absint', $ids_array));

        case 'local_id':
            // Use unified connection manager (MANDATORY).
            $local_id = function_exists('apollo_get_event_local_id') ? apollo_get_event_local_id($event_id) : 0;

            return $local_id ? (string) absint($local_id) : '';

        case 'timetable':
            $timetable = get_post_meta($event_id, '_event_timetable', true);
            if (empty($timetable)) {
                return '';
            }
            $timetable_array = maybe_unserialize($timetable);
            if (! is_array($timetable_array)) {
                return '';
            }

            return wp_json_encode($timetable_array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            // === TAXONOMIES ===
        case 'type_slug':
            $types = wp_get_post_terms($event_id, 'event_listing_type');
            if (is_wp_error($types) || empty($types)) {
                return '';
            }

            return esc_attr($types[0]->slug);

        case 'type_name':
            $types = wp_get_post_terms($event_id, 'event_listing_type');
            if (is_wp_error($types) || empty($types)) {
                return '';
            }

            return esc_html($types[0]->name);

        case 'tags_list':
            $tags = wp_get_post_terms($event_id, 'event_listing_tag');
            if (is_wp_error($tags) || empty($tags)) {
                return '';
            }
            $names = array_map(
                function ($term) {
                    return $term->name;
                },
                $tags
            );

            return esc_html(implode(', ', $names));

            // === LOCAL (event_local) FIELDS ===
        case 'local_name':
            // Load helper if not already loaded
            if (! class_exists('Apollo_Event_Data_Helper')) {
                require_once plugin_dir_path(__FILE__) . 'helpers/event-data-helper.php';
            }
            $local = Apollo_Event_Data_Helper::get_local_data($event_id);

            return $local ? esc_html($local['name']) : '';

        case 'local_description':
            // Use unified connection manager.
            $local_id = function_exists('apollo_get_event_local_id') ? apollo_get_event_local_id($event_id) : 0;
            if (empty($local_id)) {
                return '';
            }
            $description = get_post_meta(absint($local_id), '_local_description', true);

            return $description ? esc_html($description) : '';

        case 'local_address':
            // Load helper if not already loaded
            if (! class_exists('Apollo_Event_Data_Helper')) {
                require_once plugin_dir_path(__FILE__) . 'helpers/event-data-helper.php';
            }
            $local = Apollo_Event_Data_Helper::get_local_data($event_id);

            return $local ? esc_html($local['address']) : '';

        case 'local_city':
            // Load helper if not already loaded
            if (! class_exists('Apollo_Event_Data_Helper')) {
                require_once plugin_dir_path(__FILE__) . 'helpers/event-data-helper.php';
            }
            $local = Apollo_Event_Data_Helper::get_local_data($event_id);

            return $local ? esc_html($local['city']) : '';

        case 'local_coordinates':
            // Load helper if not already loaded
            if (! class_exists('Apollo_Event_Data_Helper')) {
                require_once plugin_dir_path(__FILE__) . 'helpers/event-data-helper.php';
            }
            $local = Apollo_Event_Data_Helper::get_local_data($event_id);
            if ($local && $local['lat'] && $local['lng']) {
                return esc_html($local['lat'] . ', ' . $local['lng']);
            }

            return '';

        case 'local_website':
            // Use unified connection manager.
            $local_id = function_exists('apollo_get_event_local_id') ? apollo_get_event_local_id($event_id) : 0;
            if (empty($local_id)) {
                return '';
            }
            $website = get_post_meta(absint($local_id), '_local_website', true);

            return $website ? esc_url($website) : '';

        case 'local_instagram':
            // Use unified connection manager.
            $local_id = function_exists('apollo_get_event_local_id') ? apollo_get_event_local_id($event_id) : 0;
            if (empty($local_id)) {
                return '';
            }
            $instagram = get_post_meta(absint($local_id), '_local_instagram', true);

            return $instagram ? esc_html($instagram) : '';

        case 'local_facebook':
            // Use unified connection manager.
            $local_id = function_exists('apollo_get_event_local_id') ? apollo_get_event_local_id($event_id) : 0;
            if (empty($local_id)) {
                return '';
            }
            $facebook = get_post_meta(absint($local_id), '_local_facebook', true);

            return $facebook ? esc_url($facebook) : '';

            // === DJ (event_dj) FIELDS (first DJ) ===
        case 'dj_name':
            $djs = apollo_event_get_dj_names($event_id);

            return ! empty($djs) ? esc_html($djs[0]) : '';

        case 'dj_bio':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $bio         = get_post_meta($first_dj_id, '_dj_bio', true);

            return $bio ? esc_html($bio) : '';

        case 'dj_website':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $website     = get_post_meta($first_dj_id, '_dj_website', true);

            return $website ? esc_url($website) : '';

        case 'dj_soundcloud':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $soundcloud  = get_post_meta($first_dj_id, '_dj_soundcloud', true);

            return $soundcloud ? esc_url($soundcloud) : '';

        case 'dj_instagram':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $instagram   = get_post_meta($first_dj_id, '_dj_instagram', true);

            return $instagram ? esc_html($instagram) : '';

        case 'dj_facebook':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $facebook    = get_post_meta($first_dj_id, '_dj_facebook', true);

            return $facebook ? esc_url($facebook) : '';

        case 'dj_bandcamp':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $bandcamp    = get_post_meta($first_dj_id, '_dj_bandcamp', true);

            return $bandcamp ? esc_url($bandcamp) : '';

        case 'dj_spotify':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $spotify     = get_post_meta($first_dj_id, '_dj_spotify', true);

            return $spotify ? esc_url($spotify) : '';

        case 'dj_youtube':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $youtube     = get_post_meta($first_dj_id, '_dj_youtube', true);

            return $youtube ? esc_url($youtube) : '';

        case 'dj_mixcloud':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $mixcloud    = get_post_meta($first_dj_id, '_dj_mixcloud', true);

            return $mixcloud ? esc_url($mixcloud) : '';

        case 'dj_beatport':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $beatport    = get_post_meta($first_dj_id, '_dj_beatport', true);

            return $beatport ? esc_url($beatport) : '';

        case 'dj_resident_advisor':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $ra          = get_post_meta($first_dj_id, '_dj_resident_advisor', true);

            return $ra ? esc_url($ra) : '';

        case 'dj_twitter':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $twitter     = get_post_meta($first_dj_id, '_dj_twitter', true);

            return $twitter ? esc_html($twitter) : '';

        case 'dj_tiktok':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $tiktok      = get_post_meta($first_dj_id, '_dj_tiktok', true);

            return $tiktok ? esc_url($tiktok) : '';

        case 'dj_image':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $image       = get_post_meta($first_dj_id, '_dj_image', true);
            if (empty($image)) {
                return '';
            }
            // Check if it's a URL or attachment ID
            if (filter_var($image, FILTER_VALIDATE_URL)) {
                return esc_url($image);
            }
            if (is_numeric($image)) {
                $url = wp_get_attachment_url(absint($image));

                return $url ? esc_url($url) : '';
            }

            return '';

        case 'dj_original_project_1':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $project     = get_post_meta($first_dj_id, '_dj_original_project_1', true);

            return $project ? esc_html($project) : '';

        case 'dj_original_project_2':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $project     = get_post_meta($first_dj_id, '_dj_original_project_2', true);

            return $project ? esc_html($project) : '';

        case 'dj_original_project_3':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $project     = get_post_meta($first_dj_id, '_dj_original_project_3', true);

            return $project ? esc_html($project) : '';

        case 'dj_set_url':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $url         = get_post_meta($first_dj_id, '_dj_set_url', true);

            return $url ? esc_url($url) : '';

        case 'dj_media_kit_url':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $url         = get_post_meta($first_dj_id, '_dj_media_kit_url', true);

            return $url ? esc_url($url) : '';

        case 'dj_rider_url':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $url         = get_post_meta($first_dj_id, '_dj_rider_url', true);

            return $url ? esc_url($url) : '';

        case 'dj_mix_url':
            $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
            if (empty($dj_ids)) {
                return '';
            }
            $ids_array = maybe_unserialize($dj_ids);
            if (! is_array($ids_array) || empty($ids_array)) {
                return '';
            }
            $first_dj_id = absint($ids_array[0]);
            $url         = get_post_meta($first_dj_id, '_dj_mix_url', true);

            return $url ? esc_url($url) : '';

        default:
            return '';
    }//end switch
}

/**
 * Helper: Get event local IDs array.
 * Returns array of local (venue) post IDs associated with an event.
 *
 * @param int $event_id Event post ID
 * @return array Array of local post IDs
 */
function apollo_get_event_local_ids($event_id)
{
    // Use unified connection manager (MANDATORY).
    $local_id = function_exists('apollo_get_event_local_id') ? apollo_get_event_local_id($event_id) : 0;

    // Return as array for compatibility.
    return $local_id > 0 ? [ $local_id ] : [];
}

/**
 * Internal helper: Get location name from event.
 * Reuses logic from portal-discover.php and event-card.php.
 *
 * @param int $event_id Event post ID
 * @return string Location name (sanitized)
 */
function apollo_event_get_location_name($event_id)
{
    // Use unified connection manager (MANDATORY).
    $local_id = function_exists('apollo_get_event_local_id') ? apollo_get_event_local_id($event_id) : 0;

    if (! empty($local_id) && is_numeric($local_id)) {
        $local_post = get_post(absint($local_id));
        if ($local_post && $local_post->post_status === 'publish' && $local_post->post_type === 'event_local') {
            $local_name = get_post_meta($local_id, '_local_name', true);
            if (empty($local_name)) {
                $local_name = $local_post->post_title;
            }
            if (! empty($local_name)) {
                return sanitize_text_field($local_name);
            }
        }
    }

    // Fallback to _event_location (may contain "Name | Area")
    $location = get_post_meta($event_id, '_event_location', true);
    if (! empty($location)) {
        // Split by | if present
        if (strpos($location, '|') !== false) {
            list($name, $area) = array_map('trim', explode('|', $location, 2));

            return sanitize_text_field($name);
        }

        return sanitize_text_field($location);
    }

    return '';
}

/**
 * Internal helper: Get location area from event.
 *
 * @param int $event_id Event post ID
 * @return string Location area (sanitized)
 */
function apollo_event_get_location_area($event_id)
{
    // Use unified connection manager (MANDATORY).
    $local_id = function_exists('apollo_get_event_local_id') ? apollo_get_event_local_id($event_id) : 0;

    if (! empty($local_id) && is_numeric($local_id)) {
        $local_post = get_post(absint($local_id));
        if ($local_post && $local_post->post_status === 'publish' && $local_post->post_type === 'event_local') {
            $local_city  = get_post_meta($local_id, '_local_city', true);
            $local_state = get_post_meta($local_id, '_local_state', true);
            if ($local_city && $local_state) {
                return sanitize_text_field($local_city . ', ' . $local_state);
            } elseif ($local_city) {
                return sanitize_text_field($local_city);
            } elseif ($local_state) {
                return sanitize_text_field($local_state);
            }
        }
    }

    // Fallback to _event_location (after | separator)
    $location = get_post_meta($event_id, '_event_location', true);
    if (! empty($location) && strpos($location, '|') !== false) {
        list($name, $area) = array_map('trim', explode('|', $location, 2));

        return sanitize_text_field($area);
    }

    return '';
}

/**
 * Internal helper: Get DJ names from event.
 * Uses Apollo_Event_Data_Helper for centralized logic.
 *
 * @param int $event_id Event post ID
 * @return array Array of DJ names (sanitized)
 */
function apollo_event_get_dj_names($event_id)
{
    // Load helper if not already loaded
    if (! class_exists('Apollo_Event_Data_Helper')) {
        require_once plugin_dir_path(__FILE__) . 'helpers/event-data-helper.php';
    }

    return Apollo_Event_Data_Helper::get_dj_lineup($event_id);
}

/**
 * Internal helper: Get banner URL from event.
 * Uses Apollo_Event_Data_Helper for centralized logic.
 *
 * @param int $event_id Event post ID
 * @return string Banner URL
 */
function apollo_event_get_banner_url($event_id)
{
    // Load helper if not already loaded
    if (! class_exists('Apollo_Event_Data_Helper')) {
        require_once plugin_dir_path(__FILE__) . 'helpers/event-data-helper.php';
    }

    return Apollo_Event_Data_Helper::get_banner_url($event_id);
}
