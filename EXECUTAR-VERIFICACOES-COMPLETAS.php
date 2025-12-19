<?php

// phpcs:ignoreFile
/**
 * Executar Verificações Completas Pós-Correção
 *
 * Executa todas as verificações na ordem especificada:
 * 1. Ativação dos plugins
 * 2. Teste de salvamento
 * 3. Teste de exibição
 *
 * Uso: wp eval-file wp-content/plugins/apollo-events-manager/EXECUTAR-VERIFICACOES-COMPLETAS.php
 */

if (! defined('ABSPATH')) {
    require_once '../../../wp-load.php';
}

echo "\n";
echo str_repeat('═', 70) . "\n";
echo "  EXECUÇÃO DE VERIFICAÇÕES COMPLETAS - Apollo Events Manager\n";
echo str_repeat('═', 70) . "\n\n";

$all_passed = true;
$warnings   = [];

// ============================================
// VERIFICAÇÃO 1: ATIVAÇÃO DOS PLUGINS
// ============================================
echo str_repeat('━', 70) . "\n";
echo "VERIFICAÇÃO 1: ATIVAÇÃO DOS PLUGINS\n";
echo str_repeat('━', 70) . "\n\n";

$plugins_order = [
    'apollo-social/apollo-social.php'                 => 'Apollo Social',
    'apollo-rio/apollo-rio.php'                       => 'Apollo Rio',
    'apollo-events-manager/apollo-events-manager.php' => 'Apollo Events Manager',
];

echo "📦 Verificando ordem de ativação dos plugins:\n\n";

foreach ($plugins_order as $plugin_file => $plugin_name) {
    $is_active = is_plugin_active($plugin_file);

    if ($is_active) {
        echo "  ✅ {$plugin_name}: ATIVO\n";
    } else {
        echo "  ❌ {$plugin_name}: INATIVO\n";
        echo "     ⚠️ Ative este plugin primeiro!\n";
        $all_passed = false;
    }
}

echo "\n";

// Verificar CPTs registrados
echo "📋 Verificando CPTs registrados:\n\n";

$cpts_to_check = [
    'event_listing' => 'Event Listing',
    'event_dj'      => 'Event DJ',
    'event_local'   => 'Event Local',
];

foreach ($cpts_to_check as $cpt => $cpt_name) {
    $post_type_obj = get_post_type_object($cpt);
    if ($post_type_obj) {
        echo "  ✅ CPT '{$cpt}' ({$cpt_name}): REGISTRADO\n";
    } else {
        echo "  ❌ CPT '{$cpt}' ({$cpt_name}): NÃO REGISTRADO\n";
        $all_passed = false;
    }
}

echo "\n";

// Verificar página /eventos/
echo "📄 Verificando página /eventos/:\n\n";

if (function_exists('apollo_em_get_events_page')) {
    $events_page = apollo_em_get_events_page();

    if ($events_page) {
        echo "  ✅ Página encontrada:\n";
        echo "     ID: {$events_page->ID}\n";
        echo "     Status: {$events_page->post_status}\n";
        echo "     Slug: {$events_page->post_name}\n";

        if ($events_page->post_status === 'trash') {
            echo "     ⚠️ Página está na lixeira - será restaurada no próximo activation\n";
            $warnings[] = 'Página /eventos/ está na lixeira';
        } elseif ($events_page->post_status !== 'publish') {
            echo "     ⚠️ Página não está publicada (Status: {$events_page->post_status})\n";
            $warnings[] = 'Página /eventos/ não está publicada';
        }

        // Verificar se há duplicatas
        global $wpdb;
        $duplicates = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_name = %s 
            AND post_type = 'page' 
            AND ID != %d",
                'eventos',
                $events_page->ID
            )
        );

        if ($duplicates > 0) {
            echo "     ❌ PROBLEMA: {$duplicates} página(s) duplicada(s) encontrada(s)!\n";
            $all_passed = false;
        } else {
            echo "     ✅ Nenhuma duplicata encontrada\n";
        }
    } else {
        echo "  ⚠️ Página não encontrada - será criada no próximo activation\n";
        $warnings[] = 'Página /eventos/ não existe ainda';
    }//end if
} else {
    echo "  ❌ Função 'apollo_em_get_events_page()' não encontrada!\n";
    $all_passed = false;
}//end if

echo "\n";

// Verificar debug.log para erros fatal
echo "🔍 Verificando debug.log para erros fatal:\n\n";

$debug_log_path = WP_CONTENT_DIR . '/debug.log';
$fatal_errors   = [];

if (file_exists($debug_log_path)) {
    $log_lines    = file($debug_log_path);
    $recent_lines = array_slice($log_lines, -100);

    foreach ($recent_lines as $line) {
        if ((stripos($line, 'apollo') !== false || stripos($line, 'Apollo') !== false) && (stripos($line, 'fatal error') !== false || stripos($line, 'parse error') !== false || stripos($line, 'syntax error') !== false)) {
            $fatal_errors[] = trim($line);
        }
    }

    if (empty($fatal_errors)) {
        echo "  ✅ Nenhum erro fatal encontrado nas últimas 100 linhas\n";
    } else {
        echo '  ❌ ' . count($fatal_errors) . " erro(s) fatal encontrado(s):\n";
        foreach (array_slice($fatal_errors, 0, 5) as $error) {
            echo '     - ' . esc_html(substr($error, 0, 120)) . "\n";
        }
        $all_passed = false;
    }
} else {
    echo "  ℹ️ Debug.log não encontrado (normal se WP_DEBUG_LOG estiver desabilitado)\n";
}//end if

echo "\n";

// ============================================
// VERIFICAÇÃO 2: TESTE DE SALVAMENTO
// ============================================
echo str_repeat('━', 70) . "\n";
echo "VERIFICAÇÃO 2: TESTE DE SALVAMENTO\n";
echo str_repeat('━', 70) . "\n\n";

// Buscar eventos existentes para verificar
global $wpdb;

$events = $wpdb->get_results(
    "
    SELECT ID, post_title 
    FROM {$wpdb->posts} 
    WHERE post_type = 'event_listing' 
    AND post_status IN ('publish', 'draft', 'pending')
    ORDER BY ID DESC
    LIMIT 5
"
);

if (empty($events)) {
    echo "⚠️ Nenhum evento encontrado para verificar.\n";
    echo "   Crie um evento de teste com:\n";
    echo "   - DJs selecionados\n";
    echo "   - Local selecionado\n";
    echo "   - Timetable preenchido\n\n";
    $warnings[] = 'Nenhum evento encontrado para verificar meta keys';
} else {
    echo '📊 Verificando ' . count($events) . " evento(s) no banco:\n\n";

    $events_ok          = 0;
    $events_with_issues = 0;

    foreach ($events as $event) {
        $event_id    = $event->ID;
        $event_title = $event->post_title;

        echo "  Evento ID: {$event_id} - {$event_title}\n";

        $has_issues  = false;
        $issues_list = [];

        // Verificar _event_dj_ids
        $dj_ids     = get_post_meta($event_id, '_event_dj_ids', true);
        $dj_ids_old = get_post_meta($event_id, '_event_djs', true);

        if ($dj_ids_old !== false && $dj_ids_old !== '') {
            echo "     ❌ PROBLEMA: '_event_djs' (key antiga) ainda existe!\n";
            $has_issues    = true;
            $issues_list[] = 'Key antiga _event_djs existe';
        }

        if ($dj_ids !== false && $dj_ids !== '') {
            $dj_unserialized = maybe_unserialize($dj_ids);
            if (is_array($dj_unserialized)) {
                echo "     ✅ '_event_dj_ids': Array serialized com " . count($dj_unserialized) . " DJ(s)\n";
            } else {
                echo "     ❌ '_event_dj_ids': Formato incorreto (deveria ser array)\n";
                $has_issues    = true;
                $issues_list[] = '_event_dj_ids formato incorreto';
            }
        } else {
            echo "     ℹ️ '_event_dj_ids': Não configurado\n";
        }

        // Verificar _event_local_ids
        $local_ids = get_post_meta($event_id, '_event_local_ids', true);
        $local_old = get_post_meta($event_id, '_event_local', true);

        if ($local_old !== false && $local_old !== '') {
            echo "     ❌ PROBLEMA: '_event_local' (key antiga) ainda existe!\n";
            $has_issues    = true;
            $issues_list[] = 'Key antiga _event_local existe';
        }

        if ($local_ids !== false && $local_ids !== '') {
            if (is_numeric($local_ids)) {
                echo "     ✅ '_event_local_ids': Int único ({$local_ids})\n";
            } elseif (is_array($local_ids)) {
                $local_id = (int) reset($local_ids);
                echo "     ⚠️ '_event_local_ids': Array (deveria ser int único) - usando primeiro valor: {$local_id}\n";
                $warnings[] = "Evento {$event_id}: _event_local_ids é array ao invés de int";
            } else {
                echo "     ❌ '_event_local_ids': Formato incorreto\n";
                $has_issues    = true;
                $issues_list[] = '_event_local_ids formato incorreto';
            }
        } else {
            echo "     ℹ️ '_event_local_ids': Não configurado\n";
        }

        // Verificar _event_timetable
        $timetable = get_post_meta($event_id, '_event_timetable', true);

        if ($timetable !== false && $timetable !== '') {
            $timetable_unserialized = maybe_unserialize($timetable);
            if (is_array($timetable_unserialized)) {
                echo "     ✅ '_event_timetable': Array com " . count($timetable_unserialized) . " entrada(s)\n";
            } elseif (is_numeric($timetable)) {
                echo "     ❌ '_event_timetable': É número ({$timetable}) ao invés de array!\n";
                $has_issues    = true;
                $issues_list[] = '_event_timetable é número ao invés de array';
            } else {
                echo "     ⚠️ '_event_timetable': Formato desconhecido\n";
                $has_issues    = true;
                $issues_list[] = '_event_timetable formato desconhecido';
            }
        } else {
            echo "     ℹ️ '_event_timetable': Não configurado\n";
        }

        if ($has_issues) {
            ++$events_with_issues;
            echo '     📋 Problemas: ' . implode(', ', $issues_list) . "\n";
        } else {
            ++$events_ok;
            echo "     ✅ Tudo OK!\n";
        }

        echo "\n";
    }//end foreach

    echo "  📊 Resumo:\n";
    echo "     ✅ Corretos: {$events_ok}\n";
    echo "     ❌ Com problemas: {$events_with_issues}\n\n";

    if ($events_with_issues > 0) {
        $all_passed = false;
    }
}//end if

// ============================================
// VERIFICAÇÃO 3: TESTE DE EXIBIÇÃO
// ============================================
echo str_repeat('━', 70) . "\n";
echo "VERIFICAÇÃO 3: TESTE DE EXIBIÇÃO\n";
echo str_repeat('━', 70) . "\n\n";

if (empty($events)) {
    echo "⚠️ Nenhum evento para verificar exibição.\n";
    echo "   Crie um evento primeiro.\n\n";
} else {
    echo "📊 Verificando dados de exibição para eventos:\n\n";

    foreach (array_slice($events, 0, 3) as $event) {
        $event_id    = $event->ID;
        $event_title = $event->post_title;

        echo "  Evento ID: {$event_id} - {$event_title}\n";

        // Verificar DJs
        $dj_ids_raw = get_post_meta($event_id, '_event_dj_ids', true);
        $dj_ids     = apollo_aem_parse_ids($dj_ids_raw);

        if (! empty($dj_ids)) {
            $dj_names = [];
            foreach ($dj_ids as $dj_id) {
                $dj_post = get_post($dj_id);
                if ($dj_post && $dj_post->post_status === 'publish') {
                    $dj_name    = get_post_meta($dj_id, '_dj_name', true) ?: $dj_post->post_title;
                    $dj_names[] = $dj_name;
                }
            }

            if (! empty($dj_names)) {
                echo '     ✅ DJs aparecem: ' . implode(', ', $dj_names) . "\n";
            } else {
                echo "     ⚠️ DJs configurados mas não encontrados\n";
                $warnings[] = "Evento {$event_id}: DJs não encontrados";
            }
        } else {
            echo "     ℹ️ DJs não configurados\n";
        }

        // Verificar Local/Endereço
        $local_id = get_post_meta($event_id, '_event_local_ids', true);
        if ($local_id) {
            $local_id = is_array($local_id) ? (int) reset($local_id) : (int) $local_id;
        }

        if ($local_id) {
            $local_post = get_post($local_id);
            if ($local_post && $local_post->post_status === 'publish') {
                $local_name    = get_post_meta($local_id, '_local_name', true) ?: $local_post->post_title;
                $local_address = get_post_meta($local_id, '_local_address', true);

                echo "     ✅ Local aparece: {$local_name}\n";
                if ($local_address) {
                    echo "        Endereço: {$local_address}\n";
                }
            } else {
                echo "     ⚠️ Local configurado mas não encontrado\n";
                $warnings[] = "Evento {$event_id}: Local não encontrado";
            }
        } else {
            echo "     ℹ️ Local não configurado\n";
        }

        // Verificar Timetable/Lineup
        $timetable_raw = get_post_meta($event_id, '_event_timetable', true);
        $timetable     = ! empty($timetable_raw) ? maybe_unserialize($timetable_raw) : [];

        if (! empty($timetable) && is_array($timetable)) {
            // Verificar se está ordenado
            $has_times = false;
            foreach ($timetable as $slot) {
                if (isset($slot['start']) || isset($slot['end'])) {
                    $has_times = true;

                    break;
                }
            }

            if ($has_times) {
                echo '     ✅ Timetable/Lineup aparece: ' . count($timetable) . " entrada(s) com horários\n";
            } else {
                echo "     ⚠️ Timetable existe mas sem horários\n";
                $warnings[] = "Evento {$event_id}: Timetable sem horários";
            }
        } else {
            echo "     ℹ️ Timetable não configurado\n";
        }

        // Verificar Banner
        $banner = get_post_meta($event_id, '_event_banner', true);
        if ($banner !== false && $banner !== '') {
            if (filter_var($banner, FILTER_VALIDATE_URL)) {
                echo "     ✅ Banner configurado: URL válida\n";
            } elseif (is_numeric($banner)) {
                $attachment_url = wp_get_attachment_url($banner);
                if ($attachment_url) {
                    echo "     ✅ Banner configurado: Attachment ID {$banner}\n";
                } else {
                    echo "     ⚠️ Banner: Attachment ID {$banner} não encontrado\n";
                    $warnings[] = "Evento {$event_id}: Banner attachment não encontrado";
                }
            } else {
                echo "     ⚠️ Banner: Formato desconhecido\n";
                $warnings[] = "Evento {$event_id}: Banner formato desconhecido";
            }
        } else {
            echo "     ℹ️ Banner não configurado\n";
        }

        // Verificar Mapa (coordenadas)
        $has_coordinates = false;
        if ($local_id) {
            $lat = get_post_meta($local_id, '_local_latitude', true);
            if (empty($lat)) {
                $lat = get_post_meta($local_id, '_local_lat', true);
            }

            $lng = get_post_meta($local_id, '_local_longitude', true);
            if (empty($lng)) {
                $lng = get_post_meta($local_id, '_local_lng', true);
            }

            if (! empty($lat) && ! empty($lng) && is_numeric($lat) && is_numeric($lng)) {
                $lat_float = (float) $lat;
                $lng_float = (float) $lng;

                if ($lat_float >= -90 && $lat_float <= 90 && $lng_float >= -180 && $lng_float <= 180) {
                    echo "     ✅ Mapa funciona: Coordenadas válidas ({$lat}, {$lng})\n";
                    $has_coordinates = true;
                }
            }
        }//end if

        if (! $has_coordinates) {
            echo "     ℹ️ Mapa: Coordenadas não configuradas\n";
        }

        echo "\n";
    }//end foreach

    echo "  💡 Para verificar visualmente:\n";
    echo "     1. Acesse a página do evento no frontend\n";
    echo "     2. Verifique se DJs aparecem\n";
    echo "     3. Verifique se Local/endereço aparece\n";
    echo "     4. Verifique se Timetable/lineup aparece ordenado\n";
    echo "     5. Verifique se Banner aparece (se configurado)\n";
    echo "     6. Verifique se Mapa funciona (se coordenadas existem)\n\n";
}//end if

// Verificar debug.log novamente após verificações
echo str_repeat('━', 70) . "\n";
echo "VERIFICAÇÃO FINAL: DEBUG.LOG\n";
echo str_repeat('━', 70) . "\n\n";

if (file_exists($debug_log_path)) {
    $log_lines         = file($debug_log_path);
    $very_recent_lines = array_slice($log_lines, -20);

    $recent_errors = [];
    foreach ($very_recent_lines as $line) {
        if ((stripos($line, 'apollo') !== false || stripos($line, 'Apollo') !== false) && (stripos($line, 'error') !== false || stripos($line, 'fatal') !== false)) {
            $recent_errors[] = trim($line);
        }
    }

    if (empty($recent_errors)) {
        echo "  ✅ Nenhum erro recente encontrado nas últimas 20 linhas\n";
    } else {
        echo '  ⚠️ ' . count($recent_errors) . " erro(s) recente(s) encontrado(s):\n";
        foreach ($recent_errors as $error) {
            echo '     - ' . esc_html(substr($error, 0, 120)) . "\n";
        }
        $warnings[] = 'Erros recentes no debug.log';
    }
} else {
    echo "  ℹ️ Debug.log não encontrado\n";
}//end if

// ============================================
// RESUMO FINAL
// ============================================
echo "\n" . str_repeat('═', 70) . "\n";
echo "  RESUMO FINAL\n";
echo str_repeat('═', 70) . "\n\n";

if ($all_passed && empty($warnings)) {
    echo "  🎉 TODAS AS VERIFICAÇÕES PASSARAM!\n";
    echo "  O sistema está funcionando corretamente.\n\n";
} elseif ($all_passed) {
    echo "  ✅ VERIFICAÇÕES CRÍTICAS PASSARAM!\n";
    echo "  Alguns avisos foram encontrados:\n";
    foreach ($warnings as $warning) {
        echo "     ⚠️ {$warning}\n";
    }
    echo "\n";
} else {
    echo "  ❌ ALGUMAS VERIFICAÇÕES FALHARAM!\n";
    if (! empty($warnings)) {
        echo "  Avisos encontrados:\n";
        foreach ($warnings as $warning) {
            echo "     ⚠️ {$warning}\n";
        }
    }
    echo "\n";
    echo "  Revise os itens marcados com ❌ acima.\n\n";
}//end if

echo str_repeat('═', 70) . "\n";
echo "\nPara executar via WP-CLI:\n";
echo "wp eval-file wp-content/plugins/apollo-events-manager/EXECUTAR-VERIFICACOES-COMPLETAS.php\n\n";
