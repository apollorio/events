<?php

// phpcs:ignoreFile
/**
 * Verificação de Debug.log
 *
 * Analisa o debug.log em busca de erros relacionados ao Apollo
 *
 * Uso: wp eval-file wp-content/plugins/apollo-events-manager/VERIFICAR-DEBUG-LOG.php
 */

if (! defined('ABSPATH')) {
    require_once '../../../wp-load.php';
}

echo "\n";
echo str_repeat('═', 70) . "\n";
echo "  VERIFICAÇÃO DE DEBUG.LOG\n";
echo str_repeat('═', 70) . "\n\n";

$debug_log_path = WP_CONTENT_DIR . '/debug.log';

if (! file_exists($debug_log_path)) {
    echo "ℹ️ Arquivo debug.log não encontrado.\n";
    echo "   Caminho esperado: {$debug_log_path}\n\n";

    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        echo "⚠️ WP_DEBUG_LOG está ativo mas arquivo não existe.\n";
        echo "   Isso pode significar que ainda não houve erros.\n\n";
    } else {
        echo "ℹ️ WP_DEBUG_LOG está desabilitado.\n";
        echo "   Para habilitar, adicione ao wp-config.php:\n";
        echo "   define('WP_DEBUG', true);\n";
        echo "   define('WP_DEBUG_LOG', true);\n\n";
    }
    exit;
}

echo "✅ Arquivo encontrado: {$debug_log_path}\n\n";

// Obter informações do arquivo
$file_size          = filesize($debug_log_path);
$file_size_mb       = round($file_size / 1024 / 1024, 2);
$last_modified      = filemtime($debug_log_path);
$last_modified_date = date('Y-m-d H:i:s', $last_modified);

echo "📊 Informações do arquivo:\n";
echo "   Tamanho: {$file_size_mb} MB\n";
echo "   Última modificação: {$last_modified_date}\n";
echo '   (' . human_time_diff($last_modified) . " atrás)\n\n";

// Ler últimas 200 linhas
$log_lines    = file($debug_log_path);
$total_lines  = count($log_lines);
$recent_lines = array_slice($log_lines, -200);

echo "📋 Analisando últimas 200 linhas (de {$total_lines} total)...\n\n";

// Categorizar erros
$apollo_errors   = [];
$apollo_warnings = [];
$apollo_fatal    = [];
$apollo_notices  = [];
$other_errors    = [];

foreach ($recent_lines as $line_num => $line) {
    $line = trim($line);
    if (empty($line)) {
        continue;
    }

    // Verificar se é relacionado ao Apollo
    $is_apollo = (
        stripos($line, 'apollo') !== false || stripos($line, 'Apollo') !== false || stripos($line, 'APOLLO') !== false
    );

    if (! $is_apollo) {
        // Verificar erros gerais críticos
        if (stripos($line, 'fatal error') !== false || stripos($line, 'parse error') !== false || stripos($line, 'syntax error') !== false) {
            $other_errors[] = [
                'line' => $total_lines - 200 + $line_num + 1,
                'text' => $line,
            ];
        }

        continue;
    }

    $line_info = [
        'line' => $total_lines - 200 + $line_num + 1,
        'text' => $line,
    ];

    // Categorizar por tipo
    if (stripos($line, 'fatal') !== false) {
        $apollo_fatal[] = $line_info;
    } elseif (stripos($line, 'error') !== false && stripos($line, 'warning') === false) {
        $apollo_errors[] = $line_info;
    } elseif (stripos($line, 'warning') !== false) {
        $apollo_warnings[] = $line_info;
    } elseif (stripos($line, 'notice') !== false || stripos($line, 'deprecated') !== false) {
        $apollo_notices[] = $line_info;
    } else {
        // Outras mensagens do Apollo (logs informativos)
        // Não categorizar como erro
    }
}//end foreach

// Exibir resultados
echo str_repeat('━', 70) . "\n";
echo "ERROS RELACIONADOS AO APOLLO:\n";
echo str_repeat('━', 70) . "\n\n";

if (empty($apollo_fatal) && empty($apollo_errors) && empty($apollo_warnings)) {
    echo "✅ Nenhum erro encontrado nas últimas 200 linhas!\n\n";
} else {
    if (! empty($apollo_fatal)) {
        echo "❌ ERROS FATAL (CRÍTICO):\n";
        foreach (array_slice($apollo_fatal, 0, 10) as $error) {
            echo "   Linha {$error['line']}: " . esc_html(substr($error['text'], 0, 100)) . "\n";
        }
        if (count($apollo_fatal) > 10) {
            echo '   ... e mais ' . (count($apollo_fatal) - 10) . " erro(s) fatal(is)\n";
        }
        echo "\n";
    }

    if (! empty($apollo_errors)) {
        echo "❌ ERROS:\n";
        foreach (array_slice($apollo_errors, 0, 10) as $error) {
            echo "   Linha {$error['line']}: " . esc_html(substr($error['text'], 0, 100)) . "\n";
        }
        if (count($apollo_errors) > 10) {
            echo '   ... e mais ' . (count($apollo_errors) - 10) . " erro(s)\n";
        }
        echo "\n";
    }

    if (! empty($apollo_warnings)) {
        echo "⚠️ AVISOS:\n";
        foreach (array_slice($apollo_warnings, 0, 10) as $warning) {
            echo "   Linha {$warning['line']}: " . esc_html(substr($warning['text'], 0, 100)) . "\n";
        }
        if (count($apollo_warnings) > 10) {
            echo '   ... e mais ' . (count($apollo_warnings) - 10) . " aviso(s)\n";
        }
        echo "\n";
    }
}//end if

if (! empty($apollo_notices)) {
    echo "ℹ️ NOTICES/DEPRECATED:\n";
    echo '   ' . count($apollo_notices) . " notice(s) encontrado(s) (normal, não crítico)\n\n";
}

// Erros gerais críticos
if (! empty($other_errors)) {
    echo str_repeat('━', 70) . "\n";
    echo "OUTROS ERROS CRÍTICOS (não relacionados ao Apollo):\n";
    echo str_repeat('━', 70) . "\n\n";

    foreach (array_slice($other_errors, 0, 5) as $error) {
        echo "   Linha {$error['line']}: " . esc_html(substr($error['text'], 0, 100)) . "\n";
    }
    if (count($other_errors) > 5) {
        echo '   ... e mais ' . (count($other_errors) - 5) . " erro(s)\n";
    }
    echo "\n";
}

// Resumo
echo str_repeat('═', 70) . "\n";
echo "  RESUMO\n";
echo str_repeat('═', 70) . "\n\n";

echo 'Erros Fatal: ' . count($apollo_fatal) . "\n";
echo 'Erros: ' . count($apollo_errors) . "\n";
echo 'Avisos: ' . count($apollo_warnings) . "\n";
echo 'Notices: ' . count($apollo_notices) . "\n";
echo 'Outros erros críticos: ' . count($other_errors) . "\n\n";

if (empty($apollo_fatal) && empty($apollo_errors)) {
    echo "✅ NENHUM ERRO CRÍTICO ENCONTRADO!\n";
    echo "O sistema está funcionando corretamente.\n\n";
} elseif (empty($apollo_fatal)) {
    echo "⚠️ ALGUNS ERROS ENCONTRADOS (não fatais)\n";
    echo "Revise os erros acima.\n\n";
} else {
    echo "❌ ERROS FATAL ENCONTRADOS!\n";
    echo "Corrija os erros fatal antes de continuar.\n\n";
}

echo "Para executar via WP-CLI:\n";
echo "wp eval-file wp-content/plugins/apollo-events-manager/VERIFICAR-DEBUG-LOG.php\n\n";
