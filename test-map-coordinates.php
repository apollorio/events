<?php

// phpcs:ignoreFile
/**
 * Test Map Coordinates
 *
 * Verifica se os locais têm coordenadas salvas corretamente
 *
 * Usage: wp eval-file wp-content/plugins/apollo-events-manager/test-map-coordinates.php
 */

if (! defined('ABSPATH')) {
    require_once '../../../wp-load.php';
}

echo "=== TESTE DE COORDENADAS DE MAPA ===\n\n";

// Get all events
$events = get_posts(
    [
        'post_type'      => 'event_listing',
        'post_status'    => 'any',
        'posts_per_page' => -1,
    ]
);

echo '📊 Encontrados ' . count($events) . " evento(s)\n\n";

$events_without_coords = 0;
$events_with_coords    = 0;

foreach ($events as $event) {
    $event_id    = $event->ID;
    $event_title = get_post_meta($event_id, '_event_title', true) ?: $event->post_title;

    // Get local ID
    $local_id = function_exists('apollo_get_primary_local_id')
        ? apollo_get_primary_local_id($event_id)
        : 0;

    if (! $local_id) {
        $local_ids_meta = get_post_meta($event_id, '_event_local_ids', true);
        if (! empty($local_ids_meta)) {
            $local_id = is_array($local_ids_meta) ? (int) reset($local_ids_meta) : (int) $local_ids_meta;
        }
    }

    if (! $local_id) {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Evento ID: {$event_id} - " . esc_html($event_title) . "\n";
        echo "📍 Local: ❌ NENHUM LOCAL CONFIGURADO\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        ++$events_without_coords;

        continue;
    }

    // Get local name
    $local_post = get_post($local_id);
    $local_name = '';
    if ($local_post) {
        $local_name = get_post_meta($local_id, '_local_name', true) ?: $local_post->post_title;
    }

    // Get coordinates
    $local_lat = get_post_meta($local_id, '_local_latitude', true);
    if (empty($local_lat) || $local_lat === '0' || $local_lat === 0) {
        $local_lat = get_post_meta($local_id, '_local_lat', true);
    }

    $local_long = get_post_meta($local_id, '_local_longitude', true);
    if (empty($local_long) || $local_long === '0' || $local_long === 0) {
        $local_long = get_post_meta($local_id, '_local_lng', true);
    }

    // Validate
    $local_lat  = is_numeric($local_lat) ? floatval($local_lat) : '';
    $local_long = is_numeric($local_long) ? floatval($local_long) : '';

    if ($local_lat === 0 || $local_lat === '0') {
        $local_lat = '';
    }
    if ($local_long === 0 || $local_long === '0') {
        $local_long = '';
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Evento ID: {$event_id} - " . esc_html($event_title) . "\n";
    echo "📍 Local ID: {$local_id} - " . esc_html($local_name) . "\n";

    if (! empty($local_lat) && ! empty($local_long)) {
        echo "✅ Coordenadas: Lat={$local_lat}, Lng={$local_long}\n";
        echo "🔗 Google Maps: https://www.google.com/maps?q={$local_lat},{$local_long}\n";
        ++$events_with_coords;
    } else {
        echo "❌ Coordenadas: NÃO ENCONTRADAS\n";
        echo "   Tentativas:\n";
        echo '   - _local_latitude: ' . (get_post_meta($local_id, '_local_latitude', true) ?: 'vazio') . "\n";
        echo '   - _local_lat: ' . (get_post_meta($local_id, '_local_lat', true) ?: 'vazio') . "\n";
        echo '   - _local_longitude: ' . (get_post_meta($local_id, '_local_longitude', true) ?: 'vazio') . "\n";
        echo '   - _local_lng: ' . (get_post_meta($local_id, '_local_lng', true) ?: 'vazio') . "\n";
        ++$events_without_coords;
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
}//end foreach

echo "\n=== RESUMO ===\n";
echo "✅ Eventos com coordenadas: {$events_with_coords}\n";
echo "❌ Eventos sem coordenadas: {$events_without_coords}\n";
echo '📊 Total: ' . count($events) . "\n\n";

if ($events_without_coords > 0) {
    echo "⚠️  Para corrigir:\n";
    echo "   1. Edite o local no WordPress admin\n";
    echo "   2. Preencha os campos 'Latitude' e 'Longitude'\n";
    echo "   3. Ou use o geocodificador automático (se configurado)\n";
}
