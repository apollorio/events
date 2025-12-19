<?php

// phpcs:ignoreFile
/**
 * Script de Verificação: Meta Keys e Activation Hooks
 *
 * Verifica se as meta keys estão corretas no banco de dados
 * e se os activation hooks estão funcionando corretamente
 *
 * Uso: wp eval-file wp-content/plugins/apollo-events-manager/verify-meta-keys-activation.php
 */

if (! defined('ABSPATH')) {
    require_once '../../../wp-load.php';
}

echo "=== VERIFICAÇÃO DE META KEYS E ACTIVATION HOOKS ===\n\n";

// ============================================
// 1. VERIFICAR META KEYS NO BANCO
// ============================================
echo "📊 VERIFICAÇÃO DE META KEYS NO BANCO:\n";
echo str_repeat('━', 60) . "\n\n";

global $wpdb;

// Buscar todos os eventos
$events = $wpdb->get_results(
    "
    SELECT ID, post_title 
    FROM {$wpdb->posts} 
    WHERE post_type = 'event_listing' 
    AND post_status IN ('publish', 'draft', 'pending')
    ORDER BY ID DESC
    LIMIT 20
"
);

if (empty($events)) {
    echo "⚠️ Nenhum evento encontrado no banco.\n\n";
} else {
    echo '📋 Encontrados ' . count($events) . " evento(s) para análise:\n\n";

    $correct_count = 0;
    $issues_count  = 0;

    foreach ($events as $event) {
        $event_id    = $event->ID;
        $event_title = $event->post_title;

        echo str_repeat('━', 60) . "\n";
        echo "Evento ID: {$event_id} - {$event_title}\n";
        echo str_repeat('━', 60) . "\n";

        $has_issues = false;

        // Verificar _event_dj_ids
        $dj_ids     = get_post_meta($event_id, '_event_dj_ids', true);
        $dj_ids_old = get_post_meta($event_id, '_event_djs', true);

        if (! empty($dj_ids_old)) {
            echo "❌ PROBLEMA: '_event_djs' (key antiga) ainda existe!\n";
            echo '   Valor: ' . print_r($dj_ids_old, true) . "\n";
            $has_issues = true;
        }

        if ($dj_ids !== false && $dj_ids !== '') {
            $dj_ids_unserialized = maybe_unserialize($dj_ids);
            if (is_array($dj_ids_unserialized)) {
                echo "✅ '_event_dj_ids': " . count($dj_ids_unserialized) . ' DJ(s) - ' . implode(', ', $dj_ids_unserialized) . "\n";
            } elseif (is_numeric($dj_ids)) {
                echo "⚠️ '_event_dj_ids': Valor numérico único - " . $dj_ids . " (deveria ser array)\n";
                $has_issues = true;
            } else {
                echo "⚠️ '_event_dj_ids': Formato desconhecido - " . print_r($dj_ids, true) . "\n";
                $has_issues = true;
            }
        } else {
            echo "ℹ️ '_event_dj_ids': Não configurado\n";
        }

        // Verificar _event_local_ids
        $local_ids = get_post_meta($event_id, '_event_local_ids', true);
        $local_old = get_post_meta($event_id, '_event_local', true);

        if (! empty($local_old)) {
            echo "❌ PROBLEMA: '_event_local' (key antiga) ainda existe!\n";
            echo '   Valor: ' . print_r($local_old, true) . "\n";
            $has_issues = true;
        }

        if ($local_ids !== false && $local_ids !== '') {
            if (is_numeric($local_ids)) {
                echo "✅ '_event_local_ids': " . $local_ids . " (int único)\n";
            } elseif (is_array($local_ids)) {
                $local_id = (int) reset($local_ids);
                echo "⚠️ '_event_local_ids': Array - " . print_r($local_ids, true) . " (deveria ser int único)\n";
                echo '   Usando primeiro valor: ' . $local_id . "\n";
                $has_issues = true;
            } else {
                echo "⚠️ '_event_local_ids': Formato desconhecido - " . print_r($local_ids, true) . "\n";
                $has_issues = true;
            }
        } else {
            echo "ℹ️ '_event_local_ids': Não configurado\n";
        }

        // Verificar _event_timetable
        $timetable     = get_post_meta($event_id, '_event_timetable', true);
        $timetable_old = get_post_meta($event_id, '_timetable', true);

        if ($timetable !== false && $timetable !== '') {
            $timetable_unserialized = maybe_unserialize($timetable);
            if (is_array($timetable_unserialized)) {
                echo "✅ '_event_timetable': Array com " . count($timetable_unserialized) . " entrada(s)\n";
                if (! empty($timetable_unserialized)) {
                    foreach ($timetable_unserialized as $idx => $slot) {
                        if (is_array($slot)) {
                            $dj    = isset($slot['dj']) ? $slot['dj'] : 'N/A';
                            $start = isset($slot['start']) ? $slot['start'] : 'N/A';
                            $end   = isset($slot['end']) ? $slot['end'] : 'N/A';
                            echo "   Slot {$idx}: DJ={$dj}, {$start}-{$end}\n";
                        }
                    }
                }
            } elseif (is_numeric($timetable)) {
                echo "❌ PROBLEMA: '_event_timetable' é número (" . $timetable . ") ao invés de array!\n";
                $has_issues = true;
            } else {
                echo "⚠️ '_event_timetable': Formato desconhecido - " . print_r($timetable, true) . "\n";
                $has_issues = true;
            }
        } else {
            echo "ℹ️ '_event_timetable': Não configurado\n";
            if (! empty($timetable_old)) {
                echo "   ⚠️ '_timetable' (legacy) existe como fallback\n";
            }
        }//end if

        // Resumo do evento
        if ($has_issues) {
            echo "📋 Resumo: ⚠️ Problemas encontrados\n";
            ++$issues_count;
        } else {
            echo "📋 Resumo: ✅ Tudo OK! Meta keys corretas e sem keys antigas.\n";
            ++$correct_count;
        }

        echo "\n";
    }//end foreach

    echo str_repeat('━', 60) . "\n";
    echo "📊 RESUMO GERAL:\n";
    echo "   ✅ Corretos: {$correct_count}\n";
    echo "   ⚠️ Com problemas: {$issues_count}\n";
    echo '   📋 Total analisados: ' . count($events) . "\n\n";
}//end if

// ============================================
// 2. VERIFICAR ACTIVATION HOOKS
// ============================================
echo "\n" . str_repeat('━', 60) . "\n";
echo "🔧 VERIFICAÇÃO DE ACTIVATION HOOKS:\n";
echo str_repeat('━', 60) . "\n\n";

// Verificar apollo-events-manager
echo "1. apollo-events-manager:\n";
if (function_exists('apollo_em_get_events_page')) {
    echo "   ✅ Função 'apollo_em_get_events_page()' existe\n";

    $events_page = apollo_em_get_events_page();
    if ($events_page) {
        echo "   ✅ Página de eventos encontrada (ID: {$events_page->ID}, Status: {$events_page->post_status})\n";
        if ($events_page->post_status === 'trash') {
            echo "   ⚠️ Página está na lixeira - será restaurada no próximo activation\n";
        }
    } else {
        echo "   ℹ️ Página de eventos não encontrada - será criada no próximo activation\n";
    }
} else {
    echo "   ❌ Função 'apollo_em_get_events_page()' NÃO existe!\n";
}

// Verificar rewrite rules flush
$last_flush = get_transient('apollo_rewrite_rules_last_flush');
if ($last_flush) {
    $time_ago    = time() - $last_flush;
    $minutes_ago = round($time_ago / 60);
    echo "   ✅ Último flush de rewrite rules: {$minutes_ago} minuto(s) atrás\n";
} else {
    echo "   ℹ️ Nenhum flush de rewrite rules registrado ainda\n";
}

echo "\n";

// Verificar apollo-social
echo "2. apollo-social:\n";
$last_flush_social = get_transient('apollo_social_rewrite_rules_last_flush');
if ($last_flush_social) {
    $time_ago    = time() - $last_flush_social;
    $minutes_ago = round($time_ago / 60);
    echo "   ✅ Último flush de rewrite rules: {$minutes_ago} minuto(s) atrás\n";
} else {
    echo "   ℹ️ Nenhum flush de rewrite rules registrado ainda\n";
}

echo "\n";

// Verificar apollo-rio
echo "3. apollo-rio:\n";
if (function_exists('apollo_activate')) {
    echo "   ✅ Função 'apollo_activate()' existe\n";
} else {
    echo "   ℹ️ Função 'apollo_activate()' não encontrada (pode estar em outro nome)\n";
}

// Verificar se plugins estão ativos
echo "\n" . str_repeat('━', 60) . "\n";
echo "📦 STATUS DOS PLUGINS:\n";
echo str_repeat('━', 60) . "\n\n";

$plugins_to_check = [
    'apollo-events-manager/apollo-events-manager.php' => 'Apollo Events Manager',
    'apollo-social/apollo-social.php'                 => 'Apollo Social',
    'apollo-rio/apollo-rio.php'                       => 'Apollo Rio',
];

foreach ($plugins_to_check as $plugin_file => $plugin_name) {
    if (is_plugin_active($plugin_file)) {
        echo "✅ {$plugin_name}: ATIVO\n";
    } else {
        echo "❌ {$plugin_name}: INATIVO\n";
    }
}

echo "\n" . str_repeat('━', 60) . "\n";
echo "=== FIM DA VERIFICAÇÃO ===\n";
echo "\nPara executar via WP-CLI:\n";
echo "wp eval-file wp-content/plugins/apollo-events-manager/verify-meta-keys-activation.php\n";
