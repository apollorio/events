<?php
/**
 * Template: Single Event (CPT: event_listing)
 * Project: Apollo::rio
 * VERSION: FINAL FIXED - Matches HTML Template Exactly
 */

defined('ABSPATH') || exit;

if (!have_posts()) {
    status_header(404);
    nocache_headers();
    include get_404_template();
    exit;
}

/**
 * ------------------------------------------------------------------------
 * ENQUEUE (Leaflet)
 * ------------------------------------------------------------------------
 */
add_action('wp_enqueue_scripts', function () {
    if (!wp_style_is('leaflet', 'enqueued')) {
        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
    }
    if (!wp_script_is('leaflet', 'enqueued')) {
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);
    }
}, 20);

/**
 * ------------------------------------------------------------------------
 * HELPERS
 * ------------------------------------------------------------------------
 */
function apollo_youtube_id_from_url($url) {
    $url = trim((string) $url);
    if ($url === '') return '';
    if (preg_match('~^[a-zA-Z0-9_-]{10,15}$~', $url)) return $url;
    $patterns = [
        '~youtube\.com/watch\?v=([^&]+)~',
        '~youtu\.be/([^?&/]+)~',
        '~youtube\.com/embed/([^?&/]+)~',
        '~youtube\.com/shorts/([^?&/]+)~',
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $url, $m)) return $m[1];
    }
    return '';
}

function apollo_build_youtube_embed_url($youtube_url_or_id) {
    $vid = apollo_youtube_id_from_url($youtube_url_or_id);
    if ($vid === '') return '';
    $params = [
        'autoplay' => '1', 'mute' => '1', 'controls' => '0', 'loop' => '1',
        'playlist' => $vid, 'playsinline' => '1', 'modestbranding' => '1',
        'rel' => '0', 'fs' => '0', 'disablekb' => '1', 'iv_load_policy' => '3',
        'origin' => home_url(),
    ];
    return 'https://www.youtube-nocookie.com/embed/' . rawurlencode($vid) . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

function apollo_initials($name) {
    $name = trim((string) $name);
    if ($name === '') return 'DJ';
    $parts = preg_split('/\s+/', $name);
    $letters = '';
    foreach ($parts as $p) {
        $letters .= mb_substr($p, 0, 1);
        if (mb_strlen($letters) >= 2) break;
    }
    return mb_strtoupper($letters);
}

// Start The Loop
while (have_posts()) : the_post();
    $event_id = get_the_ID();
    $event_title = get_the_title();
    $content = get_the_content();

    // 1. DATA: Basic Event Info
    $event_date_display = get_post_meta($event_id, '_event_date_display', true);
    $event_start_time   = get_post_meta($event_id, '_event_start_time', true);
    $event_end_time     = get_post_meta($event_id, '_event_end_time', true);
    $event_city         = get_post_meta($event_id, '_event_city', true);
    $event_address      = get_post_meta($event_id, '_event_address', true);

    // 2. DATA: Media (Video/Image)
    $youtube_url   = get_post_meta($event_id, '_event_youtube_url', true) ?: get_post_meta($event_id, '_event_video_url', true);
    $youtube_embed = apollo_build_youtube_embed_url($youtube_url);
    $featured_img  = get_the_post_thumbnail_url($event_id, 'full') ?: 'https://assets.apollo.rio.br/img/default-event.jpg';
    $final_image   = get_post_meta($event_id, '_event_final_image', true);

    // 3. DATA: Venue Logic (Event Meta -> Local CPT -> Fallbacks)
    $venue_name = ''; 
    $venue_address_full = '';
    $venue_images = [];
    $lat = null; $lng = null;

    // Try finding linked Local CPT
    $venue_id = (int) (get_post_meta($event_id, '_event_venue_id', true) ?: get_post_meta($event_id, '_event_local_id', true));
    if (!$venue_id) {
        $local_ids = get_post_meta($event_id, '_event_local_ids', true);
        if (is_array($local_ids) && !empty($local_ids)) $venue_id = (int) $local_ids[0];
    }

    if ($venue_id) {
        // We have a linked venue
        $venue_post = get_post($venue_id);
        $venue_name = get_post_meta($venue_id, '_local_name', true) ?: $venue_post->post_title;
        $venue_addr = get_post_meta($venue_id, '_local_address', true);
        $venue_city_local = get_post_meta($venue_id, '_local_city', true);
        $venue_address_full = trim(implode(', ', array_filter([$venue_addr, $venue_city_local])));
        
        // Coords
        $lat = get_post_meta($venue_id, '_local_latitude', true) ?: get_post_meta($venue_id, '_local_lat', true) ?: get_post_meta($venue_id, '_venue_lat', true);
        $lng = get_post_meta($venue_id, '_local_longitude', true) ?: get_post_meta($venue_id, '_local_lng', true) ?: get_post_meta($venue_id, '_venue_lng', true);

        // Images: Check Event Specific Venue Gallery FIRST, then Venue CPT Gallery
        $evt_venue_gallery = get_post_meta($event_id, '_event_venue_gallery', true); // Custom meta on event
        if(is_array($evt_venue_gallery) && !empty($evt_venue_gallery)) {
             $venue_images = $evt_venue_gallery;
        } else {
             // Fallback to Venue CPT images
             $local_gallery = get_post_meta($venue_id, '_local_gallery', true);
             if (is_array($local_gallery)) {
                foreach($local_gallery as $img_id) {
                    $url = wp_get_attachment_url($img_id);
                    if($url) $venue_images[] = $url;
                }
             }
        }
    } 
    
    // Fallbacks if no venue CPT
    if (!$venue_name) $venue_name = $event_city ?: 'Local Secreto';
    if (!$venue_address_full) $venue_address_full = $event_address;
    if (!$lat) $lat = get_post_meta($event_id, '_event_lat', true);
    if (!$lng) $lng = get_post_meta($event_id, '_event_lng', true);
    
    // Normalize coordinates
    if ($lat) $lat = (float) str_replace(',', '.', (string)$lat);
    if ($lng) $lng = (float) str_replace(',', '.', (string)$lng);

    // Limit Venue Images
    $venue_images = array_slice($venue_images, 0, 5);

    // 4. DATA: Lineup
    $dj_slots = [];
    $raw_slots = get_post_meta($event_id, '_event_dj_slots', true);
    if (is_array($raw_slots)) {
        foreach ($raw_slots as $slot) {
            if (empty($slot['dj_id'])) continue;
            $dj_slots[] = [
                'id' => $slot['dj_id'],
                'start' => $slot['start'] ?? '',
                'end' => $slot['end'] ?? ''
            ];
        }
    }
    // Fallback: simple DJ list
    if (empty($dj_slots)) {
        $dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
        if (is_array($dj_ids)) {
            foreach($dj_ids as $did) $dj_slots[] = ['id' => $did, 'start'=>'', 'end'=>''];
        }
    }

    // 5. DATA: Tickets & Interested
    $tickets_url = get_post_meta($event_id, '_event_ticket_url', true);
    $guestlist_url = get_post_meta($event_id, '_event_guestlist_url', true);
    $coupon_code = get_post_meta($event_id, '_event_coupon_code', true) ?: 'APOLLO';
    
    // Interested Users
    $interested_ids = get_post_meta($event_id, '_apollo_interested_user_ids', true) ?: [];
    if(!is_array($interested_ids)) $interested_ids = [];
    $total_interested = count($interested_ids);
    $visible_interested = array_slice($interested_ids, 0, 10);
    $hidden_interested_count = max(0, $total_interested - 10);

    // 6. DATA: Tags & Promo Gallery
    $tags = get_the_terms($event_id, 'event_listing_type');
    $promo_images = get_post_meta($event_id, '_event_promo_gallery', true) ?: [];
    $promo_images = array_slice($promo_images, 0, 5);
    
    // Sound Tags
    $sounds = [];
    $sound_terms = get_the_terms($event_id, 'event_sounds');
    if(is_array($sound_terms)) {
        foreach($sound_terms as $t) $sounds[] = $t->name;
    }
    // Infinite loop filler
    if(count($sounds) > 0 && count($sounds) < 8) {
        $orig = $sounds;
        while(count($sounds) < 8) $sounds = array_merge($sounds, $orig);
    }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5, user-scalable=yes">
    <title><?php echo esc_html($event_title); ?> - Apollo::rio</title>
    <link rel="icon" href="https://assets.apollo.rio.br/img/neon-green.webp" type="image/webp">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css">
    
    <?php wp_head(); ?>

    <style>
        * { -webkit-tap-highlight-color: transparent; corner-shape:squircle; box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --font-primary: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
            --bg-main: #fff; --bg-surface: #f5f5f5;
            --text-primary: rgba(19, 21, 23, .85); --text-secondary: rgba(19, 21, 23, .7);
            --border-color: #e0e2e4; --border-color-2: #e0e2e454;
            --radius-main: 12px; --radius-card: 16px;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --card-shadow-light: rgba(0, 0, 0, 0.05);
        }
        i {margin:1px 0 -1px 0}  
        html, body {
            font-family: var(--font-primary); font-size: 15px; color: var(--text-secondary);
            background-color: #f2f2f2;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg'%3E%3Cdefs%3E%3Cpattern id='a' width='20' height='20' patternTransform='scale(1.8)' patternUnits='userSpaceOnUse'%3E%3Crect width='100%25' height='100%25' fill='none'/%3E%3Cpath fill='none' stroke='rgba(0, 0, 0, 0.02)' d='M10 0v20M0 10h20'/%3E%3C/pattern%3E%3C/defs%3E%3Crect width='800%25' height='800%25' fill='url(%23a)'/%3E%3C/svg%3E");
            -webkit-font-smoothing: antialiased; scroll-behavior: smooth;
        }
        @media (min-width: 888px) {
            body { display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; padding: 5rem 0 0rem; }
            .mobile-container { max-width: 500px; width: 100%; background: #fff; box-shadow: 0 0 60px rgba(0,0,0,0.1); border-radius: 2rem; overflow: hidden; }
        }
        @media (max-width: 888px) { 
            .mobile-container { width: 100%; min-height: 100vh; } 
        }
        /* Hero Section */
        .hero-media { position: relative; width: 100%; height: 75vh; overflow: hidden; background: #000; border-radius: 0px; }
        .video-cover {
            position: absolute; top: 50%; left: 50%; width: 100vw; height: 56.25vw; min-height: 100vh;
            min-width: 177.77vh; transform: translate(-50%, -50%); overflow: hidden;
        }
        .video-cover iframe, .video-cover img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; pointer-events: none; object-fit: cover; }
        .hero-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(to bottom, rgba(0,0,0,0.3) 0%, transparent 30%, transparent 70%, rgba(0,0,0,0.8) 100%);
            z-index: 1; user-select: none;
        }
        .hero-content { position: absolute; bottom: 0; left: 0; width: 100%; padding: 2rem 1.5rem; z-index: 2; color: white; }
        .event-tag-pill {
            display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.22rem .4rem 0.19rem .3rem;
            background: linear-gradient(17deg, rgba(255,255,255,0.3), transparent); backdrop-filter: blur(15px);
            border-radius: 0.65rem; border:1px solid #ffffff1a; font-size: 0.54rem; font-weight: 500;
            text-transform: uppercase; letter-spacing: 0.2em; margin-bottom: 0.3rem; user-select: none;
        }
        .event-tag-pill i { font-size: 0.95rem; margin: 0 -2px 0 2px; }
        .hero-title { font-size: clamp(3.2rem, 8vw, 3.2rem); font-weight: 900; line-height: 1; margin: 0.5rem 0; text-shadow: 0 4px 20px rgba(0,0,0,0.5); user-select: none; }
        .hero-meta { display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.8rem; font-weight: 400; padding: 0px 0.2em 2em 0.2em; margin:0 auto; }
        .hero-meta-item { display: flex; align-items: center; gap: 0.5rem; user-select: none; }
        .hero-meta-item i { opacity:0.5; font-size:1.12rem; }
        /* Event Body */
        .event-body { background: var(--bg-main); border-radius: 2rem 2rem 0 0; margin-top: -2.5rem; position: relative; z-index: 3; padding: 10px 0px; }
        /* Quick Actions */
        .quick-actions { display: grid; grid-template-columns: repeat(4, auto); gap: 0.33rem; padding: 2rem 1.5rem; }
        .quick-action { display: flex; flex-direction: column; align-items: center; gap: 0rem; text-decoration: none; color: var(--text-secondary); transition: var(--transition); }
        .quick-action:hover { color: var(--text-primary); }
        .quick-action-icon {
            width: 60px; height: 60px; background: transparent; border: 1px solid var(--border-color);
            border-radius: 1rem; display: flex; justify-content: center; align-items: center; cursor: pointer; transition: var(--transition);
        }
        .quick-action-icon:hover { background: var(--bg-surface); transform: translateY(-4px); box-shadow: 0 4px 12px var(--card-shadow-light); }
        .quick-action-icon i { font-size: 1.5rem; color: var(--text-primary); }
        .quick-action-label { font-size: 0.65rem; color:#ddddddcc; font-weight: 400; text-transform: uppercase; letter-spacing: 1.2px; text-align: center; }
        /* RSVP Row */
        .rsvp-row { padding: 1.2rem 2rem 4.5rem; justify-content:right; display: flex; position: relative; }
        .avatars-explosion { display: flex; position:absolute; right:115px; flex-wrap: wrap; margin-bottom: 1rem; }
        .avatar {
            width: 42px; height: 42px; border-radius: 49%; corner-shape:squircle; border: 0px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15); margin-left: -12px; transition: transform 0.3s ease; background-size: cover; background-position: center;
        }
        .avatar:first-child { margin-left: 0; }
        .avatar:hover { transform: scale(1.15) translateY(-4px); z-index: 10; }
        .avatar-count {
            width: 42px; height: 42px; border-radius: 50%; background: var(--text-primary); color: white;
            display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 500; border: 3px solid #fff; margin-left: -15px;
        }
        .interested-text { display: flex; position:absolute; right:-70px; top:10px; align-items: center; gap: 0.5rem; font-size: 0.9rem; color: var(--text-secondary); font-weight: 500; user-select: none; }
        /* Section Styles */
        .section { padding: 2rem 1.5rem; user-select: none; }
        .section-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.75rem; color: var(--text-primary); }
        .section-title i { font-size: 1.75rem; opacity: 0.7; }
        .info-card { background: var(--bg-surface); border-radius: var(--radius-card); padding: 1.5rem; margin-bottom: 1rem; }
        .info-text { font-size: 1rem; line-height: 1.6; color: var(--text-secondary); }
        /* Marquee */
        .music-tags-marquee { overflow: hidden; white-space: nowrap; margin-top: 1rem; mask-image: linear-gradient(90deg, transparent, #000 20%, #000 80%, transparent); -webkit-mask-image: linear-gradient(90deg, transparent, #000 20%, #000 80%, transparent); }
        .music-tags-track { display: inline-flex; gap: 1rem; animation: marquee 20s linear infinite; }
        @keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }
        .music-tag { padding: 0.4rem 0.8rem; background: transparent; border-radius: 999px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; color: var(--text-secondary); border: 1px solid var(--border-color); }
        /* Promo Gallery */
        .promo-gallery-slider { position: relative; overflow: hidden; border-radius: var(--radius-card); height: 300px; }
        .promo-track { display: flex; transition: transform 0.5s ease; height: 100%; }
        .promo-slide { flex-shrink: 0; padding: 0.6rem; border-radius: 8px; width: 100%; height: 100%; }
        .promo-slide img { padding: 1rem; border-radius: 8px; width: 100%; height: 100%; object-fit: cover; }
        .promo-controls { position: absolute; bottom: 1rem; right: 3rem; display: flex; gap: 0.5rem; z-index: 10; text-shadow: 2px 2px 13px black; }
        .promo-prev, .promo-next {
            width: 25px; height: 25px; border-radius: 50%; background: #eeeeee1a; backdrop-filter: blur(10px);
            border: none; color: #eeeeee80; cursor: pointer; display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; transition: var(--transition); text-shadow: 2px 2px 13px black;
        }
        .promo-prev:hover, .promo-next:hover { background: rgba(0,0,0,0.7); }
        /* Lineup */
        .lineup-list { display: flex; flex-direction: column; gap: 1rem; }
        .lineup-card {
            background: var(--bg-surface); border-radius: var(--radius-card); display: flex; align-items: center;
            gap: 1rem; transition: var(--transition); min-height:85px; padding:0 .5rem;
        }
        .lineup-card:hover { box-shadow: 0 4px 12px var(--card-shadow-light); }
        .lineup-avatar-img { width: auto; height: 100%; max-height:65px; border-radius: 12px; object-fit: cover; flex-shrink: 0; }
        .lineup-avatar-fallback {
            width: 64px; height: 64px; border-radius: 12px; background: var(--bg-surface); border: 0px solid var(--border-color);
            display: flex; align-items: center; justify-content: center; font-size: 1.75rem; color: var(--text-primary); font-weight: 700; flex-shrink: 0;
        }
        .lineup-info { flex: 1; margin-left:16px; }
        .lineup-name { font-size: 1.125rem; font-weight: 700; margin-bottom: 0.25rem; }
        .dj-link { color: var(--text-primary); text-decoration: none; transition: color 0.3s ease; }
        .dj-link:hover { color: var(--text-secondary); }
        .lineup-time { font-size: 0.875rem; color: var(--text-secondary); display: flex; align-items: center; gap: 0.5rem; }
        /* Venue Images Slider */
        .local-images-slider { position: relative; overflow: hidden; border-radius: var(--radius-card); height: 350px; background: #000; margin-bottom: 1.5rem; z-index:15; }
        .local-images-track { display: flex; transition: transform 0.5s ease; height: 100%; }
        .local-image { flex-shrink: 0; width: 100%; height: 100%; }
        .local-image img { width: 100%; height: 100%; object-fit: cover; }
        .slider-nav { position: absolute; bottom: 1rem; left: 50%; transform: translateX(-50%); display: flex; gap: 0.5rem; z-index: 10; }
        .slider-dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.5); cursor: pointer; transition: var(--transition); }
        .slider-dot.active { background: white; width: 24px; border-radius: 4px; }
        /* Route Controls */
        .route-controls { display: flex; gap: 0px; margin-top: 1.5rem; }
        .route-input {
            height: 48px; border-radius: 12px 0px 0px 12px; background-color: transparent; border: 1px solid var(--border-color); border-right: none;
            display: flex; align-items: center; padding: 0 20px; gap: 10px; flex: 1; transition: var(--transition);
        }
        .route-input:focus-within { border-color: var(--text-primary); border-right: none; box-shadow: 0 0 0 2px var(--border-color-2); }
        .route-input input { background: none; border: none; flex: 1; font-size: 0.87rem; color: var(--text-primary); outline: none; }
        .route-input i { font-size: 1.8rem; opacity: 0.5; }
        .route-button {
            height: 48px; padding: 6px 18px; border-radius:12px; text-transform: uppercase; background:rgb(240, 240, 240);
            color: #13131380; border: 1px solid rgb(224, 226, 228); border-left: none; font-weight: 600; font-size:1.2rem;
            cursor: pointer; transition: var(--transition); display: flex; align-items: center; gap: 8px; white-space: nowrap;
            transition: color .5s ease-in-out, filter .5s ease-in-out;
        }
        .route-button:hover { background: var(--text-secondary); color:#fdfdfd; filter: brightness(1.08); }
        .route-button:hover:active { background:#fff; color:#000; filter:brightness(1.8); transform:scale(1.02); }
        /* Tickets */
        .ticket-card {
            background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-card);
            padding: 1rem; margin-top:1.45rem; text-decoration: none; display: flex; flex-direction: row; align-items: center;
            justify-content: flex-start; text-align: left; transition: var(--transition); filter:contrast(0.8); user-select: none;
        }
        .ticket-card:hover { transform: translateY(4px); box-shadow: 0 10px 30px var(--card-shadow-light); border: 1px solid #3434341a; filter:contrast(1) brightness(1.02); }
        .disabled { cursor: default; pointer-events: none; touch-action: none; transition: none; opacity:.5; filter:contrast(1) brightness(1); }
        .disabled:hover{filter:contrast(1) brightness(1);}
        .disabled.ticket-name{opacity:.4}
        .ticket-icon { width: 50px; height: 50px; border-radius: 50%; background: var(--bg-surface); border: 2px solid var(--border-color); display: flex; align-items: center; justify-content: center; margin-right: 1rem; }
        .ticket-icon i { font-size: 1.75rem; color: var(--text-primary); }
        .ticket-info { display: flex; flex-direction: column; align-items: flex-start; }
        .ticket-name { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.25rem; color: var(--text-primary); }
        .ticket-cta { font-size: 0.875rem; font-weight: 600; color: var(--text-secondary); }
        /* Coupon */
        .apollo-coupon-detail {
            background: rgba(247, 148, 27, 0.08); border: 1px dashed var(--border-color); border-radius: 0px 0px 12px 12px;
            padding: .5rem .6rem 0.3rem 0.6rem; display: flex; margin: -0.2rem auto 0.2rem auto; align-items: center;
            gap: 0.84rem; font-size: 0.78rem; width:96%; corner-shape:squircle; letter-spacing: .5px;
        }
        .apollo-coupon-detail span { margin:auto; text-align:center}
        .apollo-coupon-detail .ri-coupon-3-line { font-size: .99rem; padding: .3rem 0rem; color: var(--text-secondary); transform:rotate(145deg); }
        .apollo-coupon-detail strong { font-size: 0.75rem; position:relative; display:inline-flex; font-weight: 800; text-align: center; margin:auto; color: var(--text-primary); letter-spacing: 1px; }
        .copy-code-mini {
            background: transparent; border: none; color: var(--text-secondary); border-radius: 3px; width: 17px; height: 17px;
            display: flex; align-items: center; justify-content: center; cursor: pointer; margin-left: auto; transition: var(--transition); transform:rotate(0deg);
        }
        .copy-code-mini:hover { background: var(--text-secondary); }
        /* Bottom Bar */
        .bottom-bar {
            position: fixed; bottom: 0; left: 50%; transform: translateX(-50%); width: 100%; max-width: 500px;
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); padding: 1rem 1.5rem;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.1); z-index: 100; display: flex; gap: 0.75rem; border-radius: 22px 22px 0 0;
        }
        .bottom-btn {
            flex: 1; padding: 1rem; border-radius: var(--radius-main); border: 1px solid var(--border-color); font-weight: 700;
            font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem;
            transition: var(--transition); text-decoration: none; background: var(--bg-main); color: var(--text-primary);
        }
        .bottom-btn:active { transform: scale(0.97); }
        .bottom-btn:hover { background: var(--bg-surface); border-color: var(--text-secondary); }
        .bottom-btn.primary { background: var(--text-primary); color: white; border-color: var(--text-primary); }
        .bottom-btn.primary:hover { background: var(--text-secondary); border-color: var(--text-secondary); }
        /* Responsive */
        @media (max-width: 768px) {
            .hero-media { height: 60vh; }
            .quick-actions { grid-template-columns: repeat(4, auto); gap: 0rem; padding: 0.1rem 1rem 0 1rem; margin: 1.5rem auto 0px auto; }
            .rsvp-row { padding: 2rem 2rem 2rem; margin:0.7rem auto; }
            .tickets-grid { grid-template-columns: 1fr; }
            .interested-text { right:-55px;}
            .avatars-explosion { right:70px;}
            .promo-gallery-slider { height: 200px; }
            .local-images-slider { height: 250px; }
            .avatar { width: 36px; height: 36px; }
            .lineup-avatar-img, .lineup-avatar-fallback { width: 30%; height: 60px; font-size: 1.5rem; }
        }
        /* Extra Utilities */
        .secondary-image { border-radius: var(--radius-card); overflow: hidden; }
        .secondary-image img { width: 100%; height: auto; display: block; }
    </style>
</head>
<body>
    <div class="mobile-container">
    
        <div class="hero-media">
             <div class="video-cover">
                <?php if ($youtube_embed): ?>
                <iframe src="<?php echo esc_url($youtube_embed); ?>" allow="autoplay; fullscreen" allowfullscreen frameborder="0"></iframe>
                <?php else: ?>
                <img src="<?php echo esc_url($featured_img); ?>" alt="Event Cover">
                <?php endif; ?>
             </div>
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <?php if ($tags && !is_wp_error($tags)): foreach($tags as $tag): 
                    $icon = 'ri-price-tag-3-fill';
                    if(strpos($tag->slug, 'novidade') !== false) $icon = 'ri-fire-fill';
                    elseif(strpos($tag->slug, 'recomenda') !== false) $icon = 'ri-award-fill';
                    elseif(strpos($tag->slug, 'destaque') !== false) $icon = 'ri-verified-badge-fill';
                ?>
                <span class="event-tag-pill"><i class="<?php echo $icon; ?>"></i> <?php echo esc_html($tag->name); ?></span>
                <?php endforeach; endif; ?>
                <h1 class="hero-title"><?php echo esc_html($event_title); ?></h1>
                <div class="hero-meta">
                    <?php if($event_date_display): ?>
                    <div class="hero-meta-item">
                        <i class="ri-calendar-line"></i> 
                        <span><?php echo esc_html($event_date_display); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if($event_start_time): ?>
                    <div class="hero-meta-item">
                        <i class="ri-time-line"></i> 
                        <span id="Hora"><?php echo esc_html($event_start_time); ?> <?php if($event_end_time) echo '- ' . esc_html($event_end_time); ?></span><font style="opacity:.7;font-weight:300; font-size:.81rem; vertical-align: bottom;">(GMT-03h00)</font>
                    </div>
                    <?php endif; ?>
                    <div class="hero-meta-item">
                        <i class="ri-map-pin-line"></i> 
                        <span><?php echo esc_html($venue_name); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="event-body">
        
            <div class="quick-actions">
                <a href="#route_TICKETS" class="quick-action">
                    <div class="quick-action-icon"><i class="ri-ticket-2-line"></i></div>
                    <span class="quick-action-label">TICKETS</span>
                </a>
                <a href="#route_LINE" class="quick-action">
                    <div class="quick-action-icon"><i class="ri-draft-line"></i></div>
                    <span class="quick-action-label">Line-up</span>
                </a>
                <a href="#route_ROUTE" class="quick-action">
                    <div class="quick-action-icon"><i class="ri-treasure-map-line"></i></div>
                    <span class="quick-action-label">ROUTE</span>
                </a>
                <a href="#" class="quick-action" id="favoriteTrigger">
                    <div class="quick-action-icon"><i class="ri-rocket-line"></i></div>
                    <span class="quick-action-label">Interesse</span>
                </a>
            </div>
         
            <div class="rsvp-row">
                <div class="avatars-explosion" id="apolloAvatarsContainer">
                    <?php foreach($visible_interested as $uid): 
                        $u_avatar = get_avatar_url($uid); 
                    ?>
                    <div class="avatar" style="background-image: url('<?php echo esc_url($u_avatar); ?>')"></div>
                    <?php endforeach; ?>
                    
                    <div class="avatar-count" id="avatarCount" style="<?php echo ($hidden_interested_count > 0) ? '' : 'display:none;'; ?>">+<?php echo $hidden_interested_count; ?></div>
                    <p class="interested-text" style="margin: 0 8px 0px 20px;">
                        <i class="ri-bar-chart-2-fill"></i> <span id="result"><?php echo $total_interested; ?></span>
                    </p>
                </div>
            </div>
          
            <section class="section">
                <h2 class="section-title"><i class="ri-brain-ai-3-fill"></i> Info</h2>
                <div class="info-card">
                    <p class="info-text"><?php echo apply_filters('the_content', $content); ?></p>
                </div>
                <?php if(!empty($sounds)): ?>
                <div class="music-tags-marquee">
                    <div class="music-tags-track">
                       <?php foreach($sounds as $s): ?>
                       <span class="music-tag"><?php echo esc_html($s); ?></span>
                       <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </section>

            <?php if(!empty($promo_images)): ?>
            <div class="promo-gallery-slider">
                <div class="promo-track" id="promoTrack">
                    <?php foreach($promo_images as $img): ?>
                    <div class="promo-slide"><img src="<?php echo esc_url($img); ?>" alt="Promo"></div>
                    <?php endforeach; ?>
                </div>
                <div class="promo-controls">
                     <button class="promo-prev"><i class="ri-arrow-left-s-line"></i></button>
                     <button class="promo-next"><i class="ri-arrow-right-s-line"></i></button>
                </div>
            </div>
            </section>
            <?php endif; ?>

            <section class="section" id="route_LINE"><h2 class="section-title"><i class="ri-disc-line"></i> Line-up</h2>
                <div class="lineup-list">
                <?php if(!empty($dj_slots)): foreach($dj_slots as $slot): 
                    $dj_id = $slot['id'];
                    $dj_post = get_post($dj_id);
                    if(!$dj_post) continue;
                    $dj_name = $dj_post->post_title;
                    $dj_perm = get_permalink($dj_id);
                    $dj_img = get_the_post_thumbnail_url($dj_id, 'medium');
                ?>
                    <div class="lineup-card">
                        <?php if($dj_img): ?>
                        <img src="<?php echo esc_url($dj_img); ?>" alt="<?php echo esc_attr($dj_name); ?>" class="lineup-avatar-img">
                        <?php else: ?>
                        <div class="lineup-avatar-fallback"><?php echo apollo_initials($dj_name); ?></div>
                        <?php endif; ?>
                        
                        <div class="lineup-info">
                            <h3 class="lineup-name"><a href="<?php echo esc_url($dj_perm); ?>" target="_blank" class="dj-link"><?php echo esc_html($dj_name); ?></a></h3>
                            <div class="lineup-time"><i class="ri-time-line"></i><span><?php echo esc_html($slot['start'] . ' - ' . $slot['end']); ?></span></div>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                    <p style="opacity:0.6; padding:1rem;">Line-up a confirmar.</p>
                <?php endif; ?>
                </div>
            </section>

            <section class="section" id="route_ROUTE">
                <h2 class="section-title"><i class="ri-map-pin-2-line"></i> <?php echo esc_html($venue_name); ?></h2>
                <p style="margin:0.5rem 0 1.5rem 0; font-size:0.95rem;"><?php echo esc_html($venue_address_full); ?></p>
                
                <?php if(!empty($venue_images)): ?>
                <div class="local-images-slider">
                    <div class="local-images-track" id="localTrack">
                        <?php foreach($venue_images as $vimg): ?>
                        <div class="local-image"><img src="<?php echo esc_url($vimg); ?>" alt="Venue"></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="slider-nav" id="localDots"></div>
                </div>
                <?php endif; ?>

                <div class="map-view" style="margin:00px auto 0px auto; z-index:0; width:100%; height:285px;border-radius:12px;background-image:url('https://img.freepik.com/premium-vector/city-map-scheme-background-flat-style-vector-illustration_833641-2300.jpg'); background-size: cover;background-repeat: no-repeat;background-position: center center; position:relative;">
                     <?php if($lat && $lng): ?>
                     <div id="apolloMap" style="width:100%; height:100%; border-radius:12px;"></div>
                     <?php endif; ?>
                </div>
           
                <div class="route-controls" style="transform:translateY(-80px); padding:0 0.5rem;">
                     <div class="route-input glass">
                     <i class="ri-map-pin-line"></i>
                    <input type="text" id="origin-input" placeholder="Seu endereço de partida">
                    </div>
                    <button id="route-btn" class="route-button"><i class="ri-send-plane-line"></i></button>
                </div>
            </section>
        
            <section class="section" id="route_TICKETS">
                <h2 class="section-title"><i class="ri-ticket-2-line" style="margin:2px 0 -2px 0"></i> Acessos</h2>
                <div class="tickets-grid">
                    <a href="<?php echo $tickets_url ? esc_url($tickets_url).'?ref=apollo.rio.br' : '#'; ?>" class="ticket-card <?php echo $tickets_url ? '' : 'disabled'; ?>" target="_blank">
                        <div class="ticket-icon"><i class="ri-ticket-line"></i></div>
                        <div class="ticket-info">
                            <h3 class="ticket-name"><span id="changingword" style="opacity: 1;">Biglietti</span></h3>
                            <span class="ticket-cta"><?php echo $tickets_url ? 'Acessar Bilheteria Digital →' : 'Em breve'; ?></span>
                        </div>
                    </a>
                  
                    <?php if($tickets_url): ?>
                    <div class="apollo-coupon-detail">
                        <i class="ri-coupon-3-line"></i>
                        <span>Verifique se o cupom <strong><?php echo esc_html($coupon_code); ?></strong> está ativo com desconto</span>
                        <button class="copy-code-mini" onclick="copyPromoCode()">
                        <i class="ri-file-copy-fill"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                  
                    <a href="<?php echo $guestlist_url ? esc_url($guestlist_url) : '#'; ?>" class="ticket-card <?php echo $guestlist_url ? '' : 'disabled'; ?>">
                        <div class="ticket-icon"><i class="ri-list-check"></i></div>
                        <div class="ticket-info">
                            <h3 class="ticket-name">Lista Amiga</h3>
                            <span class="ticket-cta"><?php echo $guestlist_url ? 'Ver Lista Amiga →' : 'Indisponível'; ?></span>
                        </div>
                    </a>
                </div>
            </section>

            <?php if($final_image): ?>
            <section class="section">
                <div class="secondary-image">
                <img src="<?php echo esc_url($final_image); ?>" alt="Event Final">
                </div>
            </section>
            <?php endif; ?>

            <div style="height:120px;"></div>

        </div><div class="bottom-bar">
            <a href="#route_TICKETS" class="bottom-btn primary" id="bottomTicketBtn">
                <i class="ri-ticket-fill"></i>
                <span id="changingword2">Tickets</span>
            </a>
            <button class="bottom-btn secondary" id="bottomShareBtn">
                <i class="ri-share-forward-line"></i>
            </button>
        </div>

    </div><script>
        'use strict';
        // Promo Gallery Slider
        const promoTrack = document.getElementById('promoTrack');
        const promoSlides = promoTrack?.children.length || 0;
        let currentPromo = 0;
        document.querySelector('.promo-prev')?.addEventListener('click', () => {
            currentPromo = (currentPromo - 1 + promoSlides) % promoSlides;
            if(promoTrack) promoTrack.style.transform = `translateX(-${currentPromo * 100}%)`;
        });
        document.querySelector('.promo-next')?.addEventListener('click', () => {
            currentPromo = (currentPromo + 1) % promoSlides;
            if(promoTrack) promoTrack.style.transform = `translateX(-${currentPromo * 100}%)`;
        });

        // Venue Images Slider
        const localTrack = document.getElementById('localTrack');
        const localDots = document.getElementById('localDots');
        if (localTrack && localTrack.children.length > 0) {
            const slides = localTrack.children;
            const slideCount = slides.length;
            let currentSlide = 0;
            // Create dots
            for(let i = 0; i < slideCount; i++) {
                const dot = document.createElement('div');
                dot.classList.add('slider-dot');
                if(i === 0) dot.classList.add('active');
                dot.addEventListener('click', () => goToSlide(i));
                localDots.appendChild(dot);
            }
            function goToSlide(index) {
                currentSlide = index;
                localTrack.style.transition = 'transform 0.5s ease';
                localTrack.style.transform = `translateX(-${index * 100}%)`;
                updateDots();
            }
            function updateDots() {   
                document.querySelectorAll('.slider-dot').forEach((dot, i) => {
                    dot.classList.toggle('active', i === currentSlide);
                });
            }
            // Auto-advance
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
                    goToSlide(currentSlide);
                }
            }, 4000);
        }

        // Route Button
        const routeBtn = document.getElementById('route-btn');
        if (routeBtn) { 
            routeBtn.addEventListener('click', () => {
                const originInput = document.getElementById('origin-input');
                const origin = originInput.value;
                const dest = "<?php echo esc_js($venue_address_full); ?>";
                if (origin) {
                    window.open(`https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(dest)}`, '_blank');
                } else {
                    originInput.placeholder = 'Por favor, insira um endereço!';
                    setTimeout(() => { originInput.placeholder = 'Seu endereço de partida'; }, 2000);
                }
            });
        }

        // Copy Promo Code
        function copyPromoCode() {
            const code = '<?php echo esc_js($coupon_code); ?>'; 
            navigator.clipboard.writeText(code).then(() => {
                const btn = event.target.closest('.copy-code-mini');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="ri-check-line"></i>';
                setTimeout(() => { btn.innerHTML = originalHTML; }, 2000);
            });
        }

        // Share Function
        document.getElementById('bottomShareBtn')?.addEventListener('click', () => {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo esc_js($event_title); ?>',
                    text: 'Confere esse evento no Apollo::rio!',
                    url: window.location.href
                });
            } else {
                navigator.clipboard.writeText(window.location.href);
                alert('Link copiado!');
            }
        });

        // Bottom Ticket Scroll
        document.getElementById('bottomTicketBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            document.getElementById('route_TICKETS').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        // Changing Word Animation
        (function(){
            var words = ['Entradas','Ingressos','Billets','Ticket','Acessos','Biglietti'], i = 0;
            var elem = document.getElementById('changingword');
            var elem2 = document.getElementById('changingword2');
            
            function change(el) {
               if(!el) return;
               el.textContent = words[i];
            }
            if(elem) elem.textContent = words[0];
            
            setInterval(function(){
                i = (i+1) % words.length;
                if(elem) { elem.style.opacity = 0; setTimeout(()=>{ elem.textContent=words[i]; elem.style.opacity=1; }, 300); }
                if(elem2) { elem2.textContent=words[i]; }
            }, 4000);
        })();

        // Leaflet Map Init
        <?php if($lat && $lng): ?>
        document.addEventListener("DOMContentLoaded", function() {
            if(typeof L !== 'undefined') {
                var map = L.map('apolloMap').setView([<?php echo $lat; ?>, <?php echo $lng; ?>], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);
                L.marker([<?php echo $lat; ?>, <?php echo $lng; ?>]).addTo(map);
            }
        });
        <?php endif; ?>
        
        // Favorite/Interest Logic (Visual Only - Backend AJAX hook required for persistence)
        document.getElementById('favoriteTrigger').addEventListener('click', function(event) {
          event.preventDefault();
          const iconContainer = this.querySelector('.quick-action-icon');
          const icon = iconContainer.querySelector('i');
          const avatarsContainer = document.querySelector('.avatars-explosion');
          const countEl = document.getElementById('avatarCount');
          const resultEl = document.getElementById('result');
          
          if (icon.classList.contains('ri-rocket-line')) {
            iconContainer.classList.add('fly-away');
            setTimeout(() => {
              iconContainer.classList.remove('fly-away');
              icon.className = 'ri-ai-agent-fill fade-in';
              iconContainer.style.borderColor = 'rgba(0,0,0,0.2)';
            }, 1500);
            
            // Increment
            let cur = parseInt(resultEl.textContent) || 0;
            resultEl.textContent = cur + 1;
          } else {
             icon.className = 'ri-rocket-line fade-in';
             // Decrement
             let cur = parseInt(resultEl.textContent) || 0;
             if(cur > 0) resultEl.textContent = cur - 1;
          }
        });
    </script>  
</body>
</html>
<?php endwhile; ?>