<?php

// phpcs:ignoreFile
/**
 * Test Script: Verificar Meta Keys no Banco de Dados
 *
 * Execute via WP-CLI: wp eval-file apollo-events-manager/test-meta-keys.php
 * Ou acesse via browser (apenas para desenvolvimento local)
 */

// Security check
if (! defined('ABSPATH')) {
    // Se executado via WP-CLI, definir ABSPATH
    if (php_sapi_name() === 'cli') {
        require_once __DIR__ . '/../../../../wp-load.php';
    } else {
        die('Direct access not allowed');
    }
}

// Verificar se estamos em ambiente de desenvolvimento
if (! defined('WP_DEBUG') || ! WP_DEBUG) {
    die('Este script só pode ser executado em modo de debug');
}

echo "=== TESTE DE META KEYS - Apollo Events Manager ===\n\n";

// Buscar eventos recentes
$events = get_posts(
    [
        'post_type'      => 'event_listing',
        'posts_per_page' => 5,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'post_status'    => 'any',
    ]
);

if (empty($events)) {
    echo "⚠️ Nenhum evento encontrado no banco de dados.\n";
    echo "Crie um evento primeiro para testar.\n";
    exit;
}

echo '📊 Encontrados ' . count($events) . " evento(s) para análise:\n\n";

foreach ($events as $event) {
    $event_id = $event->ID;
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Evento ID: {$event_id} - " . esc_html($event->post_title) . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // Verificar DJs
    $dj_ids     = get_post_meta($event_id, '_event_dj_ids', true);
    $dj_ids_old = get_post_meta($event_id, '_event_djs', true);

    echo "🎵 DJs:\n";
    if (! empty($dj_ids)) {
        $dj_array = maybe_unserialize($dj_ids);
        if (is_array($dj_array)) {
            echo '  ✅ _event_dj_ids: ' . json_encode($dj_array) . "\n";
            echo '     Tipo: ' . gettype($dj_array[0] ?? null) . "\n";
        } else {
            echo '  ⚠️ _event_dj_ids existe mas não é array: ' . var_export($dj_ids, true) . "\n";
        }
    } else {
        echo "  ❌ _event_dj_ids: NÃO ENCONTRADO\n";
    }

    if (! empty($dj_ids_old)) {
        echo '  ⚠️ _event_djs (ANTIGO): ' . var_export($dj_ids_old, true) . " - DEVE SER REMOVIDO\n";
    }
    echo "\n";

    // Verificar Local
    $local_ids = get_post_meta($event_id, '_event_local_ids', true);
    $local_old = get_post_meta($event_id, '_event_local', true);

    echo "📍 Local:\n";
    if (! empty($local_ids)) {
        if (is_numeric($local_ids)) {
            echo '  ✅ _event_local_ids: ' . intval($local_ids) . " (int único)\n";
        } elseif (is_array($local_ids)) {
            echo '  ⚠️ _event_local_ids é array: ' . json_encode($local_ids) . " - DEVE SER INT\n";
        } else {
            echo '  ⚠️ _event_local_ids tipo inesperado: ' . gettype($local_ids) . ' = ' . var_export($local_ids, true) . "\n";
        }
    } else {
        echo "  ❌ _event_local_ids: NÃO ENCONTRADO\n";
    }

    if (! empty($local_old)) {
        echo '  ⚠️ _event_local (ANTIGO): ' . var_export($local_old, true) . " - DEVE SER REMOVIDO\n";
    }
    echo "\n";

    // Verificar Timetable
    $timetable     = get_post_meta($event_id, '_event_timetable', true);
    $timetable_old = get_post_meta($event_id, '_timetable', true);

    echo "🕒 Timetable:\n";
    if (! empty($timetable)) {
        $timetable_array = maybe_unserialize($timetable);
        if (is_array($timetable_array)) {
            echo '  ✅ _event_timetable: ' . json_encode($timetable_array) . "\n";
        } else {
            echo '  ⚠️ _event_timetable existe mas não é array: ' . var_export($timetable, true) . "\n";
        }
    } else {
        echo "  ❌ _event_timetable: NÃO ENCONTRADO\n";
    }

    if (! empty($timetable_old)) {
        echo '  ⚠️ _timetable (ANTIGO): ' . var_export($timetable_old, true) . " - DEVE SER REMOVIDO\n";
    }
    echo "\n";

    // Resumo
    echo "📋 Resumo:\n";
    $issues = [];
    if (empty($dj_ids)) {
        $issues[] = 'DJs não configurados';
    }
    if (empty($local_ids)) {
        $issues[] = 'Local não configurado';
    }
    if (empty($timetable)) {
        $issues[] = 'Timetable não configurado';
    }
    if (! empty($dj_ids_old)) {
        $issues[] = 'Meta key antiga _event_djs encontrada';
    }
    if (! empty($local_old)) {
        $issues[] = 'Meta key antiga _event_local encontrada';
    }
    if (! empty($timetable_old)) {
        $issues[] = 'Meta key antiga _timetable encontrada';
    }

    if (empty($issues)) {
        echo "  ✅ Tudo OK! Meta keys corretas e sem keys antigas.\n";
    } else {
        echo "  ⚠️ Problemas encontrados:\n";
        foreach ($issues as $issue) {
            echo '     - ' . $issue . "\n";
        }
    }
    echo "\n\n";
}//end foreach

echo "=== FIM DO TESTE ===\n";
echo "\nPara executar via WP-CLI:\n";
echo "wp eval-file apollo-events-manager/test-meta-keys.php\n";
