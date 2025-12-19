<?php
// phpcs:ignoreFile
/**
 * Apollo Events Manager - Admin Settings
 * Configurações administrativas do plugin
 */

if (! defined('ABSPATH')) {
    exit;
}

class Apollo_Events_Admin_Settings
{
    public function __construct()
    {
        add_action('admin_menu', [ $this, 'add_settings_page' ]);
        add_action('admin_init', [ $this, 'register_settings' ]);
    }

    /**
     * Adiciona página de configurações
     */
    public function add_settings_page()
    {
        add_submenu_page(
            'edit.php?post_type=event_listing',
            'Configurações Apollo Events',
            'Configurações',
            'manage_options',
            'apollo-events-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Registra configurações
     */
    public function register_settings()
    {
        // Seção de Páginas
        add_settings_section(
            'apollo_events_pages',
            'Configurações de Páginas',
            [ $this, 'render_pages_section' ],
            'apollo-events-settings'
        );

        // Auto-create eventos page
        register_setting(
            'apollo_events_settings',
            'apollo_events_auto_create_eventos_page',
            [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => false,
            ]
        );

        add_settings_field(
            'apollo_events_auto_create_eventos_page',
            'Criar Página "Eventos" Automaticamente',
            [ $this, 'render_auto_create_page_field' ],
            'apollo-events-settings',
            'apollo_events_pages'
        );

        // Seção de Imagens
        add_settings_section(
            'apollo_events_images',
            'Configurações de Imagens',
            [ $this, 'render_images_section' ],
            'apollo-events-settings'
        );

        // Fallback Banner URL
        register_setting(
            'apollo_events_settings',
            'apollo_events_fallback_banner_url',
            [
                'type'              => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'default'           => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070',
            ]
        );

        add_settings_field(
            'apollo_events_fallback_banner_url',
            'URL do Banner Fallback',
            [ $this, 'render_fallback_banner_field' ],
            'apollo-events-settings',
            'apollo_events_images'
        );

        // Usar Loading Animation
        register_setting(
            'apollo_events_settings',
            'apollo_events_use_loading_animation',
            [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => true,
            ]
        );

        add_settings_field(
            'apollo_events_use_loading_animation',
            'Usar Animação de Loading',
            [ $this, 'render_loading_animation_field' ],
            'apollo-events-settings',
            'apollo_events_images'
        );
    }

    /**
     * Renderiza seção de páginas
     */
    public function render_pages_section()
    {
        echo '<p>Configure criação automática de páginas principais.</p>';
    }

    /**
     * Renderiza campo de auto-create page
     */
    public function render_auto_create_page_field()
    {
        $value = get_option('apollo_events_auto_create_eventos_page', false);
        ?>
			<label>
				<input type="checkbox" 
						name="apollo_events_auto_create_eventos_page" 
						value="1" 
					<?php checked($value, true); ?>>
				Criar página "Eventos" automaticamente na ativação do plugin
			</label>
			<p class="description">
				Quando desativado, você pode criar a página manualmente em <strong>Eventos > Shortcodes</strong>.<br>
				<strong>Recomendado:</strong> Deixar desativado e criar manualmente para ter controle total.
			</p>
			<?php
    }

    /**
     * Renderiza seção de imagens
     */
    public function render_images_section()
    {
        echo '<p>Configure URLs de imagens padrão e animações de loading.</p>';
    }

    /**
     * Renderiza campo de fallback banner
     */
    public function render_fallback_banner_field()
    {
        $value = get_option('apollo_events_fallback_banner_url', 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070');
        ?>
		<input type="url" 
				name="apollo_events_fallback_banner_url" 
				value="<?php echo esc_attr($value); ?>" 
				class="regular-text"
				placeholder="https://images.unsplash.com/photo-...">
		<p class="description">
			URL da imagem a ser usada quando um evento não tiver banner definido.<br>
			<strong>Recomendado:</strong> Use imagens de alta qualidade (mínimo 1920x1080).
		</p>
		<?php
    }

    /**
     * Renderiza campo de loading animation
     */
    public function render_loading_animation_field()
    {
        $value = get_option('apollo_events_use_loading_animation', true);
        ?>
		<label>
			<input type="checkbox" 
					name="apollo_events_use_loading_animation" 
					value="1" 
					<?php checked($value, true); ?>>
			Usar animação de loading enquanto imagens carregam (ao invés de imagem fallback)
		</label>
		<p class="description">
			Quando ativado, exibe uma animação moderna enquanto as imagens dos eventos carregam.
		</p>
		<?php
    }

    /**
     * Renderiza página de configurações
     */
    public function render_settings_page()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        // Salvar configurações
        if (isset($_GET['settings-updated'])) {
            add_settings_error('apollo_events_messages', 'apollo_events_message', 'Configurações salvas!', 'updated');
        }

        settings_errors('apollo_events_messages');
        ?>
		<div class="wrap">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
			
			<form action="options.php" method="post">
				<?php
                settings_fields('apollo_events_settings');
        do_settings_sections('apollo-events-settings');
        submit_button('Salvar Configurações');
        ?>
			</form>
			
			<div class="card" style="max-width: 800px; margin-top: 20px;">
				<h2>Preview do Banner Fallback</h2>
				<?php
        $fallback_url  = get_option('apollo_events_fallback_banner_url', 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=2070');
        $use_animation = get_option('apollo_events_use_loading_animation', true);
        ?>
				<p><strong>URL atual:</strong> <code><?php echo esc_html($fallback_url); ?></code></p>
				<?php if ($use_animation) : ?>
					<p style="color: #2271b1;"><i class="dashicons dashicons-update"></i> Usando animação de loading</p>
				<?php else : ?>
					<img src="<?php echo esc_url($fallback_url); ?>" 
						alt="Preview" 
						style="max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 4px;">
				<?php endif; ?>
			</div>
		</div>
		<?php
    }
}

// Inicializar settings
new Apollo_Events_Admin_Settings();
