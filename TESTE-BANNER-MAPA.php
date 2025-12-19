<?php

// phpcs:ignoreFile
/**
 * Teste Específico: Banner e Mapa
 *
 * Verifica se banners aparecem corretamente e se mapas funcionam
 * quando coordenadas existem
 *
 * Uso: wp eval-file wp-content/plugins/apollo-events-manager/TESTE-BANNER-MAPA.php
 */

if (! defined('ABSPATH')) {
    require_once '../../../wp-load.php';
}

echo "\n";
echo str_repeat('═', 70) . "\n";
echo "  TESTE: BANNER E MAPA\n";
echo str_repeat('═', 70) . "\n\n";

global $wpdb;

// Buscar eventos com banner e/ou coordenadas
$events = $wpdb->get_results(
    "
    SELECT p.ID, p.post_title,
           pm_banner.meta_value as banner,
           pm_local.meta_value as local_id
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm_banner ON p.ID = pm_banner.post_id AND pm_banner.meta_key = '_event_banner'
    LEFT JOIN {$wpdb->postmeta} pm_local ON p.ID = pm_local.post_id AND pm_local.meta_key = '_event_local_ids'
    WHERE p.post_type = 'event_listing' 
    AND p.post_status = 'publish'
    AND (pm_banner.meta_value IS NOT NULL OR pm_local.meta_value IS NOT NULL)
    ORDER BY p.ID DESC
    LIMIT 10
"
);

if (empty($events)) {
    echo "⚠️ Nenhum evento com banner ou local encontrado.\n";
    echo "   Crie eventos com banner e local para testar.\n\n";
    exit;
}

echo '📊 Encontrados ' . count($events) . " evento(s) para teste:\n\n";

$banner_ok     = 0;
$banner_issues = 0;
$map_ok        = 0;
$map_issues    = 0;

foreach ($events as $event) {
    $event_id    = $event->ID;
    $event_title = $event->post_title;

    echo str_repeat('━', 70) . "\n";
    echo "Evento ID: {$event_id} - {$event_title}\n";
    echo str_repeat('━', 70) . "\n";

    // ============================================
    // TESTE DE BANNER
    // ============================================
    echo "\n📸 BANNER:\n";

    $banner = get_post_meta($event_id, '_event_banner', true);

    if ($banner === false || $banner === '') {
        echo "   ℹ️ Banner não configurado\n";
    } else {
        echo '   Valor encontrado: ' . substr($banner, 0, 80) . (strlen($banner) > 80 ? '...' : '') . "\n";

        // Verificar se é URL válida
        if (filter_var($banner, FILTER_VALIDATE_URL)) {
            echo "   ✅ É URL válida: {$banner}\n";

            // Testar se URL é acessível (opcional - pode ser lento)
            $response = wp_remote_head($banner, [ 'timeout' => 5 ]);
            if (! is_wp_error($response)) {
                $status_code = wp_remote_retrieve_response_code($response);
                if ($status_code >= 200 && $status_code < 400) {
                    echo "   ✅ URL acessível (HTTP {$status_code})\n";
                    ++$banner_ok;
                } else {
                    echo "   ⚠️ URL retornou código HTTP {$status_code}\n";
                    ++$banner_issues;
                }
            } else {
                echo '   ⚠️ Não foi possível verificar URL: ' . $response->get_error_message() . "\n";
                ++$banner_issues;
            }
        } elseif (is_numeric($banner)) {
            echo "   ℹ️ É attachment ID: {$banner}\n";
            $attachment_url = wp_get_attachment_url($banner);
            if ($attachment_url) {
                echo "   ✅ Attachment encontrado: {$attachment_url}\n";
                ++$banner_ok;
            } else {
                echo "   ❌ Attachment ID {$banner} não encontrado\n";
                ++$banner_issues;
            }
        } else {
            echo "   ⚠️ Formato desconhecido (nem URL nem attachment ID)\n";
            ++$banner_issues;
        }//end if
    }//end if

    // ============================================
    // TESTE DE MAPA (COORDENADAS)
    // ============================================
    echo "\n🗺️  MAPA (COORDENADAS):\n";

    // Obter local ID
    $local_id = get_post_meta($event_id, '_event_local_ids', true);
    if ($local_id) {
        $local_id = is_array($local_id) ? (int) reset($local_id) : (int) $local_id;
    }

    // Fallback para key antiga
    if (! $local_id) {
        $local_id = get_post_meta($event_id, '_event_local', true);
        $local_id = $local_id ? (int) $local_id : 0;
    }

    if (! $local_id) {
        echo "   ℹ️ Local não configurado\n";
    } else {
        echo "   Local ID: {$local_id}\n";

        // Buscar coordenadas
        $lat = get_post_meta($local_id, '_local_latitude', true);
        if (empty($lat)) {
            $lat = get_post_meta($local_id, '_local_lat', true);
        }

        $lng = get_post_meta($local_id, '_local_longitude', true);
        if (empty($lng)) {
            $lng = get_post_meta($local_id, '_local_lng', true);
        }

        // Fallback para coordenadas do evento
        if (empty($lat)) {
            $lat = get_post_meta($event_id, '_event_latitude', true);
        }
        if (empty($lng)) {
            $lng = get_post_meta($event_id, '_event_longitude', true);
        }

        if (empty($lat) || empty($lng)) {
            echo "   ❌ Coordenadas não encontradas\n";
            echo '      Latitude: ' . ($lat ?: 'N/A') . "\n";
            echo '      Longitude: ' . ($lng ?: 'N/A') . "\n";
            ++$map_issues;
        } else {
            // Validar coordenadas
            $lat_float = (float) $lat;
            $lng_float = (float) $lng;

            if ($lat_float >= -90 && $lat_float <= 90 && $lng_float >= -180 && $lng_float <= 180) {
                echo "   ✅ Coordenadas válidas:\n";
                echo "      Latitude: {$lat} ({$lat_float})\n";
                echo "      Longitude: {$lng} ({$lng_float})\n";

                // Verificar se está no Brasil (aproximadamente)
                if ($lat_float >= -35 && $lat_float <= 5 && $lng_float >= -75 && $lng_float <= -30) {
                    echo "      ✅ Coordenadas estão dentro do Brasil\n";
                } else {
                    echo "      ⚠️ Coordenadas estão fora do Brasil (pode estar correto)\n";
                }

                // Gerar URL do Google Maps
                $maps_url = "https://www.google.com/maps?q={$lat},{$lng}";
                echo "      Link: {$maps_url}\n";

                ++$map_ok;
            } else {
                echo "   ❌ Coordenadas inválidas:\n";
                echo "      Latitude: {$lat} (deve estar entre -90 e 90)\n";
                echo "      Longitude: {$lng} (deve estar entre -180 e 180)\n";
                ++$map_issues;
            }//end if
        }//end if

        // Informações adicionais do local
        $local_name    = get_post_meta($local_id, '_local_name', true);
        $local_address = get_post_meta($local_id, '_local_address', true);

        if ($local_name || $local_address) {
            echo "\n   📍 Informações do Local:\n";
            if ($local_name) {
                echo "      Nome: {$local_name}\n";
            }
            if ($local_address) {
                echo "      Endereço: {$local_address}\n";
            }
        }
    }//end if

    echo "\n";
}//end foreach

// ============================================
// RESUMO
// ============================================
echo str_repeat('═', 70) . "\n";
echo "  RESUMO DO TESTE\n";
echo str_repeat('═', 70) . "\n\n";

echo "📸 BANNER:\n";
echo "   ✅ Funcionando: {$banner_ok}\n";
echo "   ❌ Com problemas: {$banner_issues}\n\n";

echo "🗺️  MAPA:\n";
echo "   ✅ Funcionando: {$map_ok}\n";
echo "   ❌ Com problemas: {$map_issues}\n\n";

if ($banner_ok > 0 && $map_ok > 0) {
    echo "✅ TESTE PASSOU: Banner e mapa estão funcionando!\n\n";
} elseif ($banner_ok > 0) {
    echo "⚠️ TESTE PARCIAL: Banner funciona, mas mapa precisa de coordenadas.\n\n";
} elseif ($map_ok > 0) {
    echo "⚠️ TESTE PARCIAL: Mapa funciona, mas banner precisa ser configurado.\n\n";
} else {
    echo "❌ TESTE FALHOU: Configure banners e coordenadas para os eventos.\n\n";
}

echo "Para executar via WP-CLI:\n";
echo "wp eval-file wp-content/plugins/apollo-events-manager/TESTE-BANNER-MAPA.php\n\n";
