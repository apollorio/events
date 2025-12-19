<?php

// phpcs:ignoreFile
/**
 * Test Local Slugs for Filtering
 *
 * Verifica os slugs dos locais e eventos para garantir correspondência com filtros
 *
 * Usage: wp eval-file wp-content/plugins/apollo-events-manager/test-local-slugs.php
 */

if (! defined('ABSPATH')) {
    require_once '../../../wp-load.php';
}

echo "=== TESTE DE SLUGS DE LOCAIS PARA FILTROS ===\n\n";

// Get all locals
$locals = get_posts(
    [
        'post_type'      => 'event_local',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ]
);

echo '📊 Encontrados ' . count($locals) . " local(is)\n\n";

foreach ($locals as $local) {
    $local_id   = $local->ID;
    $local_name = get_post_meta($local_id, '_local_name', true) ?: $local->post_title;
    $post_name  = $local->post_name;
    $post_title = $local->post_title;

    // Generate slug like template does
    $event_local_slug = $post_name;
    if (empty($event_local_slug)) {
        $event_local_slug = sanitize_title($local_name);
    }
    $event_local_slug_normalized = strtolower(str_replace('-', '', $event_local_slug));

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Local ID: {$local_id}\n";
    echo 'Nome: ' . esc_html($local_name) . "\n";
    echo 'Post Title: ' . esc_html($post_title) . "\n";
    echo 'Post Name (slug): ' . esc_html($post_name) . "\n";
    echo 'Slug gerado: ' . esc_html($event_local_slug) . "\n";
    echo 'Slug normalizado: ' . esc_html($event_local_slug_normalized) . "\n";

    // Find events using this local
    $events_with_local = get_posts(
        [
            'post_type'      => 'event_listing',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                'relation' => 'OR',
                [
                    'key'     => '_event_local_ids',
                    'value'   => $local_id,
                    'compare' => '=',
                ],
                [
                    'key'     => '_event_local_ids',
                    'value'   => serialize(strval($local_id)),
                    'compare' => 'LIKE',
                ],
                [
                    'key'     => '_event_local',
                    'value'   => $local_id,
                    'compare' => '=',
                ],
            ],
        ]
    );

    echo 'Eventos usando este local: ' . count($events_with_local) . "\n";
    if (! empty($events_with_local)) {
        foreach ($events_with_local as $event) {
            $event_title = get_post_meta($event->ID, '_event_title', true) ?: $event->post_title;
            echo "  - Evento ID {$event->ID}: " . esc_html($event_title) . "\n";
        }
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
}//end foreach

echo "\n=== FILTROS NO TEMPLATE ===\n";
echo "Botão filtro: data-slug=\"dedge\" data-filter-type=\"local\"\n";
echo "Para funcionar, o slug do local deve corresponder a \"dedge\" (normalizado)\n";
echo "Ou o slug normalizado deve corresponder a \"dedge\" (sem hífens)\n\n";
