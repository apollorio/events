<?php

// phpcs:ignoreFile
/**
 * Loader: só ativa apollo-events-manager se apollo-social estiver ativo
 *
 * Usa hook 'plugins_loaded' para garantir que apollo-social esteja carregado
 * antes de verificar dependências.
 */
if (! defined('ABSPATH')) {
    exit;
    // Exit if accessed directly
}

// Verificar dependência usando hook plugins_loaded para garantir ordem correta
add_action(
    'plugins_loaded',
    function () {
        // Verificar se apollo-social está ativo usando múltiplos métodos
        $apollo_social_active = false;

        // Método 1: Verificar função do plugin
        if (function_exists('apollo_social_bootstrap') || class_exists('Apollo\\Plugin')) {
            $apollo_social_active = true;
        }

        // Método 2: Verificar constante do plugin
        if (defined('APOLLO_SOCIAL_PLUGIN_DIR') || defined('APOLLO_SOCIAL_VERSION')) {
            $apollo_social_active = true;
        }

        // Método 3: Verificar se plugin está na lista de plugins ativos (fallback)
        if (! $apollo_social_active && function_exists('is_plugin_active')) {
            $apollo_social_active = is_plugin_active('apollo-social/apollo-social.php');
        }

        if (! $apollo_social_active) {
            add_action(
                'admin_notices',
                function () {
                    echo '<div class="notice notice-error"><p>';
                    echo '<strong>Apollo Events Manager:</strong> ';
                    echo 'O plugin <strong>Apollo Social</strong> precisa estar instalado e ativo. ';
                    echo 'Por favor, ative o Apollo Social primeiro.';
                    echo '</p></div>';
                }
            );

            return;
            // Não carrega o plugin
        }

        // Carregar módulos do apollo-events-manager
        $loader_dir = __DIR__;
        $main_file  = $loader_dir . '/apollo-events-manager.php';

        if (file_exists($main_file)) {
            require_once $main_file;
        } else {
            error_log('Apollo Events Manager: Arquivo principal não encontrado: ' . $main_file);
        }
    },
    20
);
// Prioridade 20: depois de apollo-social (que usa prioridade padrão 10)
