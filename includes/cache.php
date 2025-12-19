<?php

// phpcs:ignoreFile

if (! defined('ABSPATH')) {
    exit;
}

function aem_events_transient_key()
{
    return 'apollo_events:list:futuro';
}

/**
 * Limpa todos os caches e transients relacionados a eventos
 * Função centralizada para garantir limpeza completa
 *
 * @param int|null $event_id ID do evento (opcional, para cache específico)
 * @return void
 */
function apollo_clear_events_cache($event_id = null)
{
    // Limpar transients específicos conhecidos
    delete_transient(aem_events_transient_key());
    delete_transient('apollo_events_portal_cache');
    delete_transient('apollo_events_home_cache');

    // Limpar transients baseados em data (últimos 7 dias para segurança)
    for ($i = 0; $i < 7; $i++) {
        $date = date('Ymd', strtotime("-{$i} days"));
        delete_transient('apollo_upcoming_event_ids_' . $date);
    }

    // Limpar cache do WordPress Object Cache (grupo apollo_events)
    if (function_exists('wp_cache_flush_group')) {
        wp_cache_flush_group('apollo_events');
    }

    // Limpar cache de queries específicas (padrões comuns)
    $common_atts = [
        [],
        // Default
                    [ 'limit' => 10 ],
        [ 'limit'    => 20 ],
        [ 'limit'    => 50 ],
        [ 'category' => 'all' ],
    ];

    foreach ($common_atts as $atts) {
        $cache_key = 'apollo_events_shortcode_' . md5(serialize($atts));
        wp_cache_delete($cache_key, 'apollo_events');
    }

    // Limpar cache do post específico se fornecido
    if ($event_id && is_numeric($event_id)) {
        clean_post_cache($event_id);
    }

    // Log para debug (apenas se WP_DEBUG estiver ativo)
    if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
        error_log(sprintf('[Apollo Cache] Cache limpo para evento %s', $event_id ?: 'todos'));
    }
}

/**
 * Limpa cache quando evento é salvo/atualizado
 */
add_action(
    'save_post_event_listing',
    function ($post_id) {
        // Ignorar autosaves e revisões
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        // Limpar todos os caches relacionados
        apollo_clear_events_cache($post_id);
    },
    20
);

/**
 * Limpa cache quando DJ é salvo/atualizado
 * DJs afetam eventos que os referenciam
 */
add_action(
    'save_post_event_dj',
    function ($post_id) {
        // Ignorar autosaves e revisões
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        // Limpar cache de eventos (DJs são exibidos nos eventos)
        apollo_clear_events_cache();
    },
    20
);

/**
 * Limpa cache quando Local é salvo/atualizado
 * Locais afetam eventos que os referenciam
 */
add_action(
    'save_post_event_local',
    function ($post_id) {
        // Ignorar autosaves e revisões
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        // Limpar cache do post do local
        clean_post_cache($post_id);

        // Encontrar todos os eventos que referenciam este local
        $events_with_local = get_posts(
            [
                'post_type'      => 'event_listing',
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'meta_query'     => [
                    'relation' => 'OR',
                    [
                        'key'     => '_event_local_ids',
                        'value'   => $post_id,
                        'compare' => '=',
                    ],
                    [
                        'key'     => '_event_local_ids',
                        'value'   => serialize(strval($post_id)),
                        'compare' => 'LIKE',
                    ],
                    [
                        'key'     => '_event_local',
                        'value'   => $post_id,
                        'compare' => '=',
                    ],
                ],
            ]
        );

        // Limpar cache de cada evento encontrado
        foreach ($events_with_local as $event) {
            clean_post_cache($event->ID);
        }

        // Limpar todos os caches relacionados a eventos
        apollo_clear_events_cache();

        // Log para debug
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            $events_count = count($events_with_local);
            error_log(sprintf('[Apollo Cache] Local %d atualizado - Cache limpo para %d evento(s) relacionado(s)', $post_id, $events_count));
        }
    },
    20
);

/**
 * Limpa cache quando evento é deletado
 */
add_action(
    'delete_post',
    function ($post_id) {
        $post_type = get_post_type($post_id);
        if (in_array($post_type, [ 'event_listing', 'event_dj', 'event_local' ])) {
            apollo_clear_events_cache();
        }
    },
    20
);

/**
 * Limpa cache quando evento é restaurado da lixeira
 */
add_action(
    'untrash_post',
    function ($post_id) {
        $post_type = get_post_type($post_id);
        if ($post_type === 'event_listing') {
            apollo_clear_events_cache($post_id);
        }
    },
    20
);
