<?php

// phpcs:ignoreFile
/**
 * Apollo Events Manager - Pre-Release Tests
 *
 * Execute via: wp eval-file RUN-PRE-RELEASE-TESTS.php
 */

if (! defined('ABSPATH')) {
    die('Direct access not permitted.');
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "🚀 APOLLO EVENTS MANAGER - PRE-RELEASE TESTS\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

$tests_passed = 0;
$tests_failed = 0;
$warnings     = [];

// Test 1: Plugin Activated
echo "📋 Teste 1: Plugin Ativado\n";
if (function_exists('apollo_cfg')) {
    echo "   ✅ Plugin ativado e função apollo_cfg() disponível\n";
    ++$tests_passed;
} else {
    echo "   ❌ Função apollo_cfg() não encontrada\n";
    ++$tests_failed;
}
echo "\n";

// Test 2: Sanitization System
echo "📋 Teste 2: Sistema de Sanitização\n";
if (class_exists('Apollo_Events_Sanitization')) {
    echo "   ✅ Classe Apollo_Events_Sanitization carregada\n";
    ++$tests_passed;
} else {
    echo "   ❌ Classe Apollo_Events_Sanitization não encontrada\n";
    ++$tests_failed;
}

if (function_exists('apollo_get_post_meta')) {
    echo "   ✅ Função apollo_get_post_meta() disponível\n";
    ++$tests_passed;
} else {
    echo "   ❌ Função apollo_get_post_meta() não encontrada\n";
    ++$tests_failed;
}

if (function_exists('apollo_update_post_meta')) {
    echo "   ✅ Função apollo_update_post_meta() disponível\n";
    ++$tests_passed;
} else {
    echo "   ❌ Função apollo_update_post_meta() não encontrada\n";
    ++$tests_failed;
}
echo "\n";

// Test 3: Post Types Registered
echo "📋 Teste 3: Custom Post Types\n";
$cpts = [ 'event_listing', 'event_dj', 'event_local' ];
foreach ($cpts as $cpt) {
    if (post_type_exists($cpt)) {
        echo "   ✅ CPT '{$cpt}' registrado\n";
        ++$tests_passed;
    } else {
        echo "   ❌ CPT '{$cpt}' NÃO registrado\n";
        ++$tests_failed;
    }
}
echo "\n";

// Test 4: Taxonomies Registered
echo "📋 Teste 4: Taxonomias\n";
$taxonomies = [ 'event_listing_category', 'event_listing_type', 'event_sounds' ];
foreach ($taxonomies as $tax) {
    if (taxonomy_exists($tax)) {
        echo "   ✅ Taxonomia '{$tax}' registrada\n";
        ++$tests_passed;
    } else {
        echo "   ❌ Taxonomia '{$tax}' NÃO registrada\n";
        ++$tests_failed;
    }
}
echo "\n";

// Test 5: Página Eventos
echo "📋 Teste 5: Página Eventos\n";
$eventos_page = get_page_by_path('eventos');
if ($eventos_page) {
    echo "   ✅ Página /eventos/ existe (ID: {$eventos_page->ID}, Status: {$eventos_page->post_status})\n";
    ++$tests_passed;

    if ($eventos_page->post_status === 'publish') {
        echo "   ✅ Página está publicada\n";
        ++$tests_passed;
    } else {
        echo "   ⚠️ Página existe mas não está publicada (Status: {$eventos_page->post_status})\n";
        $warnings[] = 'Página /eventos/ não está publicada';
    }
} else {
    echo "   ⚠️ Página /eventos/ não existe (criar via Eventos > Shortcodes)\n";
    $warnings[] = 'Página /eventos/ não encontrada - criar manualmente';
}
echo "\n";

// Test 6: Assets Enqueued
echo "📋 Teste 6: Assets (Leaflet, RemixIcon, uni.css)\n";
if (wp_script_is('leaflet', 'registered')) {
    echo "   ✅ Leaflet.js registrado\n";
    ++$tests_passed;
} else {
    echo "   ⚠️ Leaflet.js não registrado (verificar contexto)\n";
    $warnings[] = 'Leaflet.js deve ser carregado em páginas de eventos';
}

if (wp_style_is('apollo-uni-css', 'registered')) {
    echo "   ✅ uni.css registrado\n";
    ++$tests_passed;
} else {
    echo "   ⚠️ uni.css não registrado (verificar contexto)\n";
    $warnings[] = 'uni.css deve ser carregado em páginas de eventos';
}
echo "\n";

// Test 7: Shortcodes Registered
echo "📋 Teste 7: Shortcodes\n";
$shortcodes = [ 'events', 'apollo_event', 'event_djs', 'single_event_dj' ];
foreach ($shortcodes as $sc) {
    if (shortcode_exists($sc)) {
        echo "   ✅ Shortcode [{$sc}] registrado\n";
        ++$tests_passed;
    } else {
        echo "   ❌ Shortcode [{$sc}] NÃO registrado\n";
        ++$tests_failed;
    }
}
echo "\n";

// Test 8: AJAX Handlers
echo "📋 Teste 8: AJAX Handlers\n";
$ajax_actions = [
    'apollo_get_event_modal',
    'apollo_mod_approve_event',
    'apollo_mod_reject_event',
    'apollo_create_canvas_page',
];

foreach ($ajax_actions as $action) {
    if (has_action("wp_ajax_{$action}") || has_action("wp_ajax_nopriv_{$action}")) {
        echo "   ✅ AJAX handler '{$action}' registrado\n";
        ++$tests_passed;
    } else {
        echo "   ❌ AJAX handler '{$action}' NÃO registrado\n";
        ++$tests_failed;
    }
}
echo "\n";

// Test 9: Database - Check sample event
echo "📋 Teste 9: Verificação de Banco de Dados\n";
$sample_events = get_posts(
    [
        'post_type'      => 'event_listing',
        'posts_per_page' => 1,
        'post_status'    => 'any',
    ]
);

if (! empty($sample_events)) {
    $sample_event = $sample_events[0];
    echo "   ✅ Eventos encontrados no banco (sample: ID {$sample_event->ID})\n";
    ++$tests_passed;

    // Check meta keys
    $start_date = apollo_get_post_meta($sample_event->ID, '_event_start_date', true);
    if ($start_date) {
        echo "   ✅ Meta key '_event_start_date' presente\n";
        ++$tests_passed;
    } else {
        echo "   ⚠️ Meta key '_event_start_date' vazio para evento {$sample_event->ID}\n";
        $warnings[] = "Evento {$sample_event->ID} sem data de início";
    }
} else {
    echo "   ⚠️ Nenhum evento encontrado no banco (criar eventos de teste)\n";
    $warnings[] = 'Banco sem eventos - criar eventos de teste';
}
echo "\n";

// Test 10: Capabilities
echo "📋 Teste 10: Capabilities (Permissions)\n";
$editor_role = get_role('editor');
if ($editor_role && $editor_role->has_cap('edit_event_listings')) {
    echo "   ✅ Editor tem permissão 'edit_event_listings'\n";
    ++$tests_passed;
} else {
    echo "   ⚠️ Editor não tem permissão 'edit_event_listings'\n";
    $warnings[] = 'Permissões de editor podem precisar ajuste';
}
echo "\n";

// Summary
echo "════════════════════════════════════════════════════════════════\n";
echo "📊 RESUMO DOS TESTES\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";
echo "✅ Testes Aprovados: {$tests_passed}\n";
echo "❌ Testes Falhados: {$tests_failed}\n";
echo '⚠️  Avisos: ' . count($warnings) . "\n";
echo "\n";

if (! empty($warnings)) {
    echo "════════════════════════════════════════════════════════════════\n";
    echo "⚠️  AVISOS:\n";
    echo "════════════════════════════════════════════════════════════════\n";
    foreach ($warnings as $i => $warning) {
        echo ($i + 1) . ". {$warning}\n";
    }
    echo "\n";
}

if ($tests_failed === 0) {
    echo "════════════════════════════════════════════════════════════════\n";
    echo "🎉 TODOS OS TESTES CRÍTICOS PASSARAM!\n";
    echo "════════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "✅ Plugin pronto para release\n";

    if (! empty($warnings)) {
        echo "⚠️  Avisos não-críticos devem ser revisados\n";
    }

    echo "\n";
    echo "Próximos passos:\n";
    echo "1. Criar página /eventos/ (Eventos > Shortcodes)\n";
    echo "2. Criar eventos de teste\n";
    echo "3. Testar frontend (/eventos/)\n";
    echo "4. Testar modal de eventos\n";
    echo "5. Testar formulário público\n";
    echo "\n";
} else {
    echo "════════════════════════════════════════════════════════════════\n";
    echo "❌ ALGUNS TESTES FALHARAM!\n";
    echo "════════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "Por favor, corrija os problemas antes do release.\n";
    echo "\n";
}//end if

echo "════════════════════════════════════════════════════════════════\n";
