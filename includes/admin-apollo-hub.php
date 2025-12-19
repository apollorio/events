<?php
// phpcs:ignoreFile
/**
 * Apollo Events Manager - Admin Hub Page
 *
 * Central documentation and settings page with tabs:
 * - Introduction (How to Use)
 * - Shortcodes
 * - Placeholders
 * - Forms
 * - User Roles
 * - Meta Keys
 * - Settings
 *
 * @package Apollo_Events_Manager
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Register the Apollo Hub menu
 */
function apollo_events_register_hub_page()
{
    add_menu_page(
        __('Apollo Events', 'apollo-events-manager'),
        __('Apollo Events', 'apollo-events-manager'),
        'manage_options',
        'apollo-events-hub',
        'apollo_events_render_hub_page',
        'dashicons-calendar-alt',
        26
    );

    // Event Management Submenus
    add_submenu_page(
        'apollo-events-hub',
        __('Todos os Eventos', 'apollo-events-manager'),
        __('Todos os Eventos', 'apollo-events-manager'),
        'edit_posts',
        'edit.php?post_type=event_listing',
        null
    );

    add_submenu_page(
        'apollo-events-hub',
        __('Adicionar Novo Evento', 'apollo-events-manager'),
        __('Adicionar Novo Evento', 'apollo-events-manager'),
        'edit_posts',
        'post-new.php?post_type=event_listing',
        null
    );

    add_submenu_page(
        'apollo-events-hub',
        __('Categorias de Eventos', 'apollo-events-manager'),
        __('Categorias', 'apollo-events-manager'),
        'manage_categories',
        'edit-tags.php?taxonomy=event_listing_category&post_type=event_listing',
        null
    );

    add_submenu_page(
        'apollo-events-hub',
        __('Tipos de Eventos', 'apollo-events-manager'),
        __('Tipos', 'apollo-events-manager'),
        'manage_categories',
        'edit-tags.php?taxonomy=event_listing_type&post_type=event_listing',
        null
    );

    // Dashboard & Analytics Submenus
    add_submenu_page(
        'apollo-events-hub',
        __('Dashboard', 'apollo-events-manager'),
        __('Dashboard', 'apollo-events-manager'),
        'view_apollo_event_stats',
        'apollo-events-dashboard',
        'apollo_events_render_dashboard_tab'
    );

    add_submenu_page(
        'apollo-events-hub',
        __('User Overview', 'apollo-events-manager'),
        __('User Overview', 'apollo-events-manager'),
        'view_apollo_event_stats',
        'apollo-events-user-overview',
        'apollo_events_render_user_overview_tab'
    );

    // Existing Submenus
    add_submenu_page(
        'apollo-events-hub',
        __('Documentação', 'apollo-events-manager'),
        __('Documentação', 'apollo-events-manager'),
        'manage_options',
        'apollo-events-hub',
        'apollo_events_render_hub_page'
    );

    add_submenu_page(
        'apollo-events-hub',
        __('Shortcodes', 'apollo-events-manager'),
        __('Shortcodes', 'apollo-events-manager'),
        'manage_options',
        'apollo-events-shortcodes',
        'apollo_events_render_shortcodes_tab'
    );

    add_submenu_page(
        'apollo-events-hub',
        __('Meta Keys', 'apollo-events-manager'),
        __('Meta Keys', 'apollo-events-manager'),
        'manage_options',
        'apollo-events-metakeys',
        'apollo_events_render_metakeys_tab'
    );

    add_submenu_page(
        'apollo-events-hub',
        __('User Roles', 'apollo-events-manager'),
        __('User Roles', 'apollo-events-manager'),
        'manage_options',
        'apollo-events-roles',
        'apollo_events_render_roles_tab'
    );

    add_submenu_page(
        'apollo-events-hub',
        __('Settings', 'apollo-events-manager'),
        __('Settings', 'apollo-events-manager'),
        'manage_options',
        'apollo-events-settings',
        'apollo_events_render_settings_tab'
    );
}
add_action('admin_menu', 'apollo_events_register_hub_page', 5);

/**
 * Enqueue admin styles
 */
function apollo_events_hub_admin_styles($hook)
{
    if (strpos($hook, 'apollo-events') === false) {
        return;
    }

    wp_enqueue_style(
        'apollo-events-hub-admin',
        plugins_url('assets/css/admin-hub.css', __DIR__),
        [],
        '1.0.0'
    );
}
add_action('admin_enqueue_scripts', 'apollo_events_hub_admin_styles');

/**
 * Render main hub page with tabs
 */
function apollo_events_render_hub_page()
{
    $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'intro';
    ?>
	<div class="wrap apollo-hub-wrap">
		<h1>
			<span class="dashicons dashicons-calendar-alt"></span>
			<?php esc_html_e('Apollo Events Manager', 'apollo-events-manager'); ?>
		</h1>

		<nav class="nav-tab-wrapper apollo-hub-tabs">
			<a href="?page=apollo-events-hub&tab=intro"
				class="nav-tab <?php echo $current_tab === 'intro' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-info"></span>
				<?php esc_html_e('Introdução', 'apollo-events-manager'); ?>
			</a>
			<a href="?page=apollo-events-hub&tab=shortcodes"
				class="nav-tab <?php echo $current_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-shortcode"></span>
				<?php esc_html_e('Shortcodes', 'apollo-events-manager'); ?>
			</a>
			<a href="?page=apollo-events-hub&tab=placeholders"
				class="nav-tab <?php echo $current_tab === 'placeholders' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-editor-code"></span>
				<?php esc_html_e('Placeholders', 'apollo-events-manager'); ?>
			</a>
			<a href="?page=apollo-events-hub&tab=forms"
				class="nav-tab <?php echo $current_tab === 'forms' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-feedback"></span>
				<?php esc_html_e('Forms', 'apollo-events-manager'); ?>
			</a>
			<a href="?page=apollo-events-hub&tab=roles"
				class="nav-tab <?php echo $current_tab === 'roles' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-groups"></span>
				<?php esc_html_e('User Roles', 'apollo-events-manager'); ?>
			</a>
			<a href="?page=apollo-events-hub&tab=metakeys"
				class="nav-tab <?php echo $current_tab === 'metakeys' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-database"></span>
				<?php esc_html_e('Meta Keys', 'apollo-events-manager'); ?>
			</a>
			<a href="?page=apollo-events-hub&tab=settings"
				class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-admin-settings"></span>
				<?php esc_html_e('Settings', 'apollo-events-manager'); ?>
			</a>
		</nav>

		<div class="apollo-hub-content">
			<?php
            switch ($current_tab) {
                case 'shortcodes':
                    apollo_events_render_shortcodes_content();

                    break;
                case 'placeholders':
                    apollo_events_render_placeholders_content();

                    break;
                case 'forms':
                    apollo_events_render_forms_content();

                    break;
                case 'roles':
                    apollo_events_render_roles_content();

                    break;
                case 'metakeys':
                    apollo_events_render_metakeys_content();

                    break;
                case 'settings':
                    apollo_events_render_settings_content();

                    break;
                default:
                    apollo_events_render_intro_content();
            }//end switch
    ?>
		</div>
	</div>
	<?php
}

/**
 * TAB: Introduction
 */
function apollo_events_render_intro_content()
{
    ?>
	<div class="apollo-hub-section">
		<h2><?php esc_html_e('Bem-vindo ao Apollo Events Manager', 'apollo-events-manager'); ?></h2>

		<div class="apollo-intro-grid">
			<div class="apollo-intro-card">
				<h3><span class="dashicons dashicons-calendar"></span> Eventos</h3>
				<p>Gerencie eventos com data, hora, local, DJs e timetable completo.</p>
				<ul>
					<li>CPT: <code>event_listing</code></li>
					<li>Página: <a href="<?php echo admin_url('edit.php?post_type=event_listing'); ?>">Ver Eventos</a></li>
				</ul>
			</div>

			<div class="apollo-intro-card">
				<h3><span class="dashicons dashicons-admin-users"></span> DJs</h3>
				<p>Cadastre DJs com bio, redes sociais, SoundCloud e media kit.</p>
				<ul>
					<li>CPT: <code>event_dj</code></li>
					<li>Página: <a href="<?php echo admin_url('edit.php?post_type=event_dj'); ?>">Ver DJs</a></li>
				</ul>
			</div>

			<div class="apollo-intro-card">
				<h3><span class="dashicons dashicons-location"></span> Locais</h3>
				<p>Cadastre casas noturnas, clubs e espaços de eventos.</p>
				<ul>
					<li>CPT: <code>event_local</code></li>
					<li>Página: <a href="<?php echo admin_url('edit.php?post_type=event_local'); ?>">Ver Locais</a></li>
				</ul>
			</div>

			<div class="apollo-intro-card">
				<h3><span class="dashicons dashicons-heart"></span> Favoritos</h3>
				<p>Sistema de favoritos para eventos, DJs e locais.</p>
				<ul>
					<li>Shortcode: <code>[favorite_button]</code></li>
					<li>Shortcode: <code>[user_favorites]</code></li>
				</ul>
			</div>
		</div>

		<h3><?php esc_html_e('Como Começar', 'apollo-events-manager'); ?></h3>
		<ol class="apollo-steps">
			<li>
				<strong>Cadastre Locais:</strong>
				Vá em <a href="<?php echo admin_url('post-new.php?post_type=event_local'); ?>">Locais → Adicionar Novo</a>
			</li>
			<li>
				<strong>Cadastre DJs:</strong>
				Vá em <a href="<?php echo admin_url('post-new.php?post_type=event_dj'); ?>">DJs → Adicionar Novo</a>
			</li>
			<li>
				<strong>Crie um Evento:</strong>
				Vá em <a href="<?php echo admin_url('post-new.php?post_type=event_listing'); ?>">Eventos → Adicionar Novo</a>
			</li>
			<li>
				<strong>Exiba na página:</strong>
				Use o shortcode <code>[events]</code> em qualquer página
			</li>
		</ol>

		<div class="apollo-notice apollo-notice-info">
			<h4>📚 Documentação Completa</h4>
			<p>Use as abas acima para ver todos os shortcodes, placeholders, formulários e configurações disponíveis.</p>
		</div>
	</div>
	<?php
}

/**
 * TAB: Shortcodes
 */
function apollo_events_render_shortcodes_content()
{
    $shortcodes = apollo_events_get_all_shortcodes();
    ?>
	<div class="apollo-hub-section">
		<h2><?php esc_html_e('Shortcodes Disponíveis', 'apollo-events-manager'); ?></h2>
		<p><?php esc_html_e('Copie e cole estes shortcodes em qualquer página ou post.', 'apollo-events-manager'); ?></p>

		<?php foreach ($shortcodes as $category => $items) : ?>
		<div class="apollo-shortcode-category">
			<h3><?php echo esc_html($category); ?></h3>
			<table class="widefat apollo-shortcode-table">
				<thead>
					<tr>
						<th><?php esc_html_e('Shortcode', 'apollo-events-manager'); ?></th>
						<th><?php esc_html_e('Descrição', 'apollo-events-manager'); ?></th>
						<th><?php esc_html_e('Atributos', 'apollo-events-manager'); ?></th>
						<th><?php esc_html_e('Copiar', 'apollo-events-manager'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($items as $shortcode) : ?>
					<tr>
						<td><code><?php echo esc_html($shortcode['code']); ?></code></td>
						<td><?php echo esc_html($shortcode['description']); ?></td>
						<td>
							<?php if (! empty($shortcode['attributes'])) : ?>
								<small><?php echo esc_html($shortcode['attributes']); ?></small>
							<?php else : ?>
								<small>—</small>
							<?php endif; ?>
						</td>
						<td>
							<button class="button button-small apollo-copy-btn"
									data-copy="<?php echo esc_attr($shortcode['code']); ?>">
								<span class="dashicons dashicons-clipboard"></span>
							</button>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endforeach; ?>
	</div>

	<script>
	document.querySelectorAll('.apollo-copy-btn').forEach(btn => {
		btn.addEventListener('click', function() {
			const text = this.dataset.copy;
			navigator.clipboard.writeText(text).then(() => {
				this.innerHTML = '<span class="dashicons dashicons-yes"></span>';
				setTimeout(() => {
					this.innerHTML = '<span class="dashicons dashicons-clipboard"></span>';
				}, 1500);
			});
		});
	});
	</script>
	<?php
}

/**
 * TAB: Placeholders
 */
function apollo_events_render_placeholders_content()
{
    $placeholders = apollo_events_get_all_placeholders();
    ?>
	<div class="apollo-hub-section">
		<h2><?php esc_html_e('Placeholders Disponíveis', 'apollo-events-manager'); ?></h2>
		<p><?php esc_html_e('Use estes placeholders em templates e emails.', 'apollo-events-manager'); ?></p>

		<?php foreach ($placeholders as $category => $items) : ?>
		<div class="apollo-placeholder-category">
			<h3><?php echo esc_html($category); ?></h3>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e('Placeholder', 'apollo-events-manager'); ?></th>
						<th><?php esc_html_e('Descrição', 'apollo-events-manager'); ?></th>
						<th><?php esc_html_e('Exemplo', 'apollo-events-manager'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($items as $placeholder) : ?>
					<tr>
						<td><code><?php echo esc_html($placeholder['code']); ?></code></td>
						<td><?php echo esc_html($placeholder['description']); ?></td>
						<td><small><?php echo esc_html($placeholder['example']); ?></small></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * TAB: Forms
 */
function apollo_events_render_forms_content()
{
    $forms = apollo_events_get_all_forms();
    ?>
	<div class="apollo-hub-section">
		<h2><?php esc_html_e('Formulários Disponíveis', 'apollo-events-manager'); ?></h2>
		<p><?php esc_html_e('Formulários de submissão e cadastro disponíveis no plugin.', 'apollo-events-manager'); ?></p>

		<?php foreach ($forms as $form) : ?>
		<div class="apollo-form-card">
			<h3><?php echo esc_html($form['name']); ?></h3>
			<p><?php echo esc_html($form['description']); ?></p>

			<div class="apollo-form-details">
				<div class="apollo-form-detail">
					<strong><?php esc_html_e('Shortcode:', 'apollo-events-manager'); ?></strong>
					<code><?php echo esc_html($form['shortcode']); ?></code>
					<button class="button button-small apollo-copy-btn"
							data-copy="<?php echo esc_attr($form['shortcode']); ?>">
						<span class="dashicons dashicons-clipboard"></span>
					</button>
				</div>

				<div class="apollo-form-detail">
					<strong><?php esc_html_e('CPT Destino:', 'apollo-events-manager'); ?></strong>
					<code><?php echo esc_html($form['cpt']); ?></code>
				</div>

				<div class="apollo-form-detail">
					<strong><?php esc_html_e('Permissão:', 'apollo-events-manager'); ?></strong>
					<?php echo esc_html($form['permission']); ?>
				</div>
			</div>

			<details class="apollo-form-fields">
				<summary><?php esc_html_e('Campos do Formulário', 'apollo-events-manager'); ?></summary>
				<table class="widefat">
					<thead>
						<tr>
							<th><?php esc_html_e('Campo', 'apollo-events-manager'); ?></th>
							<th><?php esc_html_e('Tipo', 'apollo-events-manager'); ?></th>
							<th><?php esc_html_e('Obrigatório', 'apollo-events-manager'); ?></th>
							<th><?php esc_html_e('Meta Key', 'apollo-events-manager'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($form['fields'] as $field) : ?>
						<tr>
							<td><?php echo esc_html($field['label']); ?></td>
							<td><code><?php echo esc_html($field['type']); ?></code></td>
							<td><?php echo $field['required'] ? '✅' : '—'; ?></td>
							<td><code><?php echo esc_html($field['meta_key']); ?></code></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</details>
		</div>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * TAB: User Roles
 */
function apollo_events_render_roles_content()
{
    $roles = apollo_events_get_all_roles();
    ?>
	<div class="apollo-hub-section">
		<h2><?php esc_html_e('Controle de Permissões', 'apollo-events-manager'); ?></h2>
		<p><?php esc_html_e('Gerencie quem pode fazer o quê no sistema Apollo Events.', 'apollo-events-manager'); ?></p>

		<div class="apollo-roles-matrix">
			<table class="widefat apollo-roles-table">
				<thead>
					<tr>
						<th><?php esc_html_e('Capability', 'apollo-events-manager'); ?></th>
						<th><?php esc_html_e('Administrator', 'apollo-events-manager'); ?></th>
						<th><?php esc_html_e('Editor', 'apollo-events-manager'); ?></th>
						<th><?php esc_html_e('Author', 'apollo-events-manager'); ?></th>
						<th><?php esc_html_e('Subscriber', 'apollo-events-manager'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($roles['capabilities'] as $cap => $info) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html($info['label']); ?></strong>
							<br><small><code><?php echo esc_html($cap); ?></code></small>
						</td>
						<td class="<?php echo $info['admin'] ? 'cap-yes' : 'cap-no'; ?>">
							<?php echo $info['admin'] ? '✅' : '❌'; ?>
						</td>
						<td class="<?php echo $info['editor'] ? 'cap-yes' : 'cap-no'; ?>">
							<?php echo $info['editor'] ? '✅' : '❌'; ?>
						</td>
						<td class="<?php echo $info['author'] ? 'cap-yes' : 'cap-no'; ?>">
							<?php echo $info['author'] ? '✅' : '❌'; ?>
						</td>
						<td class="<?php echo $info['subscriber'] ? 'cap-yes' : 'cap-no'; ?>">
							<?php echo $info['subscriber'] ? '✅' : '❌'; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div class="apollo-notice apollo-notice-warning">
			<h4>⚠️ Editar Permissões</h4>
			<p>Apenas <strong>Administradores</strong> podem editar permissões de roles.
				Use o plugin <a href="https://wordpress.org/plugins/user-role-editor/" target="_blank">User Role Editor</a>
				para customizar capabilities.</p>
		</div>

		<h3><?php esc_html_e('Custom Roles Apollo', 'apollo-events-manager'); ?></h3>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php esc_html_e('Role', 'apollo-events-manager'); ?></th>
					<th><?php esc_html_e('Slug', 'apollo-events-manager'); ?></th>
					<th><?php esc_html_e('Descrição', 'apollo-events-manager'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($roles['custom_roles'] as $role) : ?>
				<tr>
					<td><strong><?php echo esc_html($role['name']); ?></strong></td>
					<td><code><?php echo esc_html($role['slug']); ?></code></td>
					<td><?php echo esc_html($role['description']); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * TAB: Meta Keys
 */
function apollo_events_render_metakeys_content()
{
    $metakeys = apollo_events_get_all_metakeys();
    ?>
	<div class="apollo-hub-section">
		<h2><?php esc_html_e('Meta Keys do Sistema', 'apollo-events-manager'); ?></h2>
		<p><?php esc_html_e('Todas as meta keys utilizadas pelos CPTs do Apollo Events.', 'apollo-events-manager'); ?></p>

		<?php foreach ($metakeys as $cpt => $keys) : ?>
		<div class="apollo-metakeys-category">
			<h3><?php echo esc_html($cpt); ?></h3>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e('Meta Key', 'apollo-events-manager'); ?></th>
						<th><?php esc_html_e('Tipo', 'apollo-events-manager'); ?></th>
						<th><?php esc_html_e('Descrição', 'apollo-events-manager'); ?></th>
						<th><?php esc_html_e('Exemplo', 'apollo-events-manager'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($keys as $key) : ?>
					<tr>
						<td><code><?php echo esc_html($key['key']); ?></code></td>
						<td><code><?php echo esc_html($key['type']); ?></code></td>
						<td><?php echo esc_html($key['description']); ?></td>
						<td><small><?php echo esc_html($key['example']); ?></small></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * TAB: Settings
 */
function apollo_events_render_settings_content()
{
    // Handle form submission
    if (isset($_POST['apollo_events_settings_nonce']) && wp_verify_nonce($_POST['apollo_events_settings_nonce'], 'apollo_events_save_settings')) {

        // Save settings
        update_option('apollo_events_per_page', intval($_POST['apollo_events_per_page'] ?? 12));
        update_option('apollo_events_map_enabled', isset($_POST['apollo_events_map_enabled']) ? 1 : 0);
        update_option('apollo_events_map_visual_zoom', isset($_POST['apollo_events_map_visual_zoom']) ? 1 : 0);
        update_option('apollo_events_favorites_enabled', isset($_POST['apollo_events_favorites_enabled']) ? 1 : 0);
        update_option('apollo_events_analytics_enabled', isset($_POST['apollo_events_analytics_enabled']) ? 1 : 0);
        update_option('apollo_events_submission_enabled', isset($_POST['apollo_events_submission_enabled']) ? 1 : 0);
        update_option('apollo_events_submission_mod', isset($_POST['apollo_events_submission_mod']) ? 1 : 0);

        // Map defaults (OSM)
        $zoom = isset($_POST['event_manager_osm_default_zoom']) ? absint(wp_unslash($_POST['event_manager_osm_default_zoom'])) : 14;
        if ($zoom < 8 || $zoom > 24) {
            $zoom = 14;
        }
        update_option('event_manager_osm_default_zoom', $zoom);

        $tile_style      = isset($_POST['event_manager_osm_tile_style']) ? sanitize_key(wp_unslash($_POST['event_manager_osm_tile_style'])) : 'default';
        $allowed_styles  = [ 'default', 'light', 'dark' ];
        $tile_style_safe = in_array($tile_style, $allowed_styles, true) ? $tile_style : 'default';
        update_option('event_manager_osm_tile_style', $tile_style_safe);

        echo '<div class="notice notice-success"><p>' . esc_html__('Configurações salvas!', 'apollo-events-manager') . '</p></div>';
    }

    // Get current settings
    $per_page           = get_option('apollo_events_per_page', 12);
    $map_enabled        = get_option('apollo_events_map_enabled', 1);
    $map_visual_zoom    = get_option('apollo_events_map_visual_zoom', 0);
    $favorites_enabled  = get_option('apollo_events_favorites_enabled', 1);
    $analytics_enabled  = get_option('apollo_events_analytics_enabled', 1);
    $submission_enabled = get_option('apollo_events_submission_enabled', 1);
    $submission_mod     = get_option('apollo_events_submission_mod', 1);
    $osm_zoom           = (int) get_option('event_manager_osm_default_zoom', 14);
    $osm_zoom           = ($osm_zoom < 8 || $osm_zoom > 24) ? 14 : $osm_zoom;
    $osm_tile_style     = get_option('event_manager_osm_tile_style', 'default');
    $osm_allowed_styles = [ 'default', 'light', 'dark' ];
    if (! in_array($osm_tile_style, $osm_allowed_styles, true)) {
        $osm_tile_style = 'default';
    }
    ?>
	<div class="apollo-hub-section">
		<h2><?php esc_html_e('Configurações Gerais', 'apollo-events-manager'); ?></h2>

		<form method="post" action="">
			<?php wp_nonce_field('apollo_events_save_settings', 'apollo_events_settings_nonce'); ?>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="apollo_events_per_page">
							<?php esc_html_e('Eventos por página', 'apollo-events-manager'); ?>
						</label>
					</th>
					<td>
						<input type="number"
								id="apollo_events_per_page"
								name="apollo_events_per_page"
								value="<?php echo esc_attr($per_page); ?>"
								min="1"
								max="100"
								class="small-text">
						<p class="description">
							<?php esc_html_e('Número de eventos exibidos por página no grid.', 'apollo-events-manager'); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e('Funcionalidades', 'apollo-events-manager'); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="checkbox"
										name="apollo_events_map_enabled"
										value="1"
										<?php checked($map_enabled, 1); ?>>
								<?php esc_html_e('Habilitar Mapa (Leaflet)', 'apollo-events-manager'); ?>
							</label>
							<br>
							<label>
								<input type="checkbox"
										name="apollo_events_map_visual_zoom"
										value="1"
										<?php checked($map_visual_zoom, 1); ?>>
								<?php esc_html_e('Ativar zoom visual do mapa', 'apollo-events-manager'); ?>
							</label>
							<p class="description" style="margin-top:4px;">
								<?php esc_html_e('Aplica um zoom visual (recorte cinematográfico) no mapa do evento, sem alterar o zoom real do Leaflet.', 'apollo-events-manager'); ?>
							</p>
							<br>
							<label>
								<input type="checkbox"
										name="apollo_events_favorites_enabled"
										value="1"
										<?php checked($favorites_enabled, 1); ?>>
								<?php esc_html_e('Habilitar Sistema de Favoritos', 'apollo-events-manager'); ?>
							</label>
							<br>
							<label>
								<input type="checkbox"
										name="apollo_events_analytics_enabled"
										value="1"
										<?php checked($analytics_enabled, 1); ?>>
								<?php esc_html_e('Habilitar Analytics de Views', 'apollo-events-manager'); ?>
							</label>
						</fieldset>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="event_manager_osm_default_zoom">
							<?php esc_html_e('Zoom padrão do mapa (OSM)', 'apollo-events-manager'); ?>
						</label>
					</th>
					<td>
						<select id="event_manager_osm_default_zoom" name="event_manager_osm_default_zoom">
							<?php
                            $zoom_options = [ 8, 10, 12, 14, 16, 18, 20, 22, 24 ];
    foreach ($zoom_options as $zoom_value) {
        printf(
            '<option value="%1$d" %2$s>%1$02d</option>',
            $zoom_value,
            selected($osm_zoom, $zoom_value, false)
        );
    }
    ?>
						</select>
						<p class="description">
							<?php esc_html_e('Defina o nível de zoom padrão para os mapas dos eventos.', 'apollo-events-manager'); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="event_manager_osm_tile_style">
							<?php esc_html_e('Estilo do mapa (OSM)', 'apollo-events-manager'); ?>
						</label>
					</th>
					<td>
						<select id="event_manager_osm_tile_style" name="event_manager_osm_tile_style">
							<option value="default" <?php selected($osm_tile_style, 'default'); ?>><?php esc_html_e('OSM padrão', 'apollo-events-manager'); ?></option>
							<option value="light" <?php selected($osm_tile_style, 'light'); ?>><?php esc_html_e('Claro / minimalista', 'apollo-events-manager'); ?></option>
							<option value="dark" <?php selected($osm_tile_style, 'dark'); ?>><?php esc_html_e('Escuro', 'apollo-events-manager'); ?></option>
						</select>
						<p class="description">
							<?php esc_html_e('Escolha o estilo visual das tiles do mapa.', 'apollo-events-manager'); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e('Submissão de Eventos', 'apollo-events-manager'); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="checkbox"
										name="apollo_events_submission_enabled"
										value="1"
										<?php checked($submission_enabled, 1); ?>>
								<?php esc_html_e('Permitir submissão pública de eventos', 'apollo-events-manager'); ?>
							</label>
							<br>
							<label>
								<input type="checkbox"
										name="apollo_events_submission_mod"
										value="1"
										<?php checked($submission_mod, 1); ?>>
								<?php esc_html_e('Exigir moderação antes de publicar', 'apollo-events-manager'); ?>
							</label>
						</fieldset>
					</td>
				</tr>
			</table>

			<?php submit_button(__('Salvar Configurações', 'apollo-events-manager')); ?>
		</form>
	</div>
	<?php
}

// ============================================
// DATA FUNCTIONS
// ============================================

/**
 * Get all shortcodes data
 *
 * NOTE: This function may also be defined in admin-shortcodes-page.php
 * Using function_exists() to prevent redeclaration errors
 */
if (! function_exists('apollo_events_get_all_shortcodes')) {
    function apollo_events_get_all_shortcodes()
    {
        return [
            'Eventos' => [
                [
                    'code'        => '[events]',
                    'description' => 'Lista de eventos com filtros',
                    'attributes'  => 'per_page, show_filters, show_map',
                ],
                [
                    'code'        => '[event id="123"]',
                    'description' => 'Exibe um evento específico',
                    'attributes'  => 'id',
                ],
                [
                    'code'        => '[event_summary id="123"]',
                    'description' => 'Resumo compacto do evento',
                    'attributes'  => 'id',
                ],
                [
                    'code'        => '[past_events]',
                    'description' => 'Lista de eventos passados',
                    'attributes'  => 'per_page',
                ],
                [
                    'code'        => '[upcoming_events]',
                    'description' => 'Próximos eventos',
                    'attributes'  => 'per_page, days',
                ],
                [
                    'code'        => '[related_events]',
                    'description' => 'Eventos relacionados',
                    'attributes'  => 'per_page',
                ],
            ],
            'DJs' => [
                [
                    'code'        => '[event_djs]',
                    'description' => 'Lista de DJs',
                    'attributes'  => 'per_page',
                ],
                [
                    'code'        => '[single_event_dj id="123"]',
                    'description' => 'Página single de DJ',
                    'attributes'  => 'id',
                ],
                [
                    'code'        => '[apollo_dj_profile]',
                    'description' => 'Perfil do DJ atual',
                    'attributes'  => '',
                ],
            ],
            'Locais' => [
                [
                    'code'        => '[event_locals]',
                    'description' => 'Lista de locais',
                    'attributes'  => 'per_page',
                ],
                [
                    'code'        => '[single_event_local id="123"]',
                    'description' => 'Página single de local',
                    'attributes'  => 'id',
                ],
                [
                    'code'        => '[local_dashboard]',
                    'description' => 'Dashboard do local',
                    'attributes'  => '',
                ],
            ],
            'Formulários' => [
                [
                    'code'        => '[submit_event_form]',
                    'description' => 'Formulário de submissão de evento',
                    'attributes'  => '',
                ],
                [
                    'code'        => '[submit_dj_form]',
                    'description' => 'Formulário de cadastro de DJ',
                    'attributes'  => '',
                ],
                [
                    'code'        => '[submit_local_form]',
                    'description' => 'Formulário de cadastro de local',
                    'attributes'  => '',
                ],
                [
                    'code'        => '[apollo_public_event_form]',
                    'description' => 'Formulário público de evento',
                    'attributes'  => '',
                ],
            ],
            'Dashboards' => [
                [
                    'code'        => '[event_dashboard]',
                    'description' => 'Dashboard de eventos do usuário',
                    'attributes'  => '',
                ],
                [
                    'code'        => '[dj_dashboard]',
                    'description' => 'Dashboard do DJ',
                    'attributes'  => '',
                ],
                [
                    'code'        => '[my_apollo_dashboard]',
                    'description' => 'Dashboard completo My Apollo',
                    'attributes'  => '',
                ],
                [
                    'code'        => '[apollo_user_dashboard]',
                    'description' => 'Dashboard geral do usuário',
                    'attributes'  => '',
                ],
            ],
            'Favoritos' => [
                [
                    'code'        => '[favorite_button]',
                    'description' => 'Botão de favoritar',
                    'attributes'  => 'post_id',
                ],
                [
                    'code'        => '[favorite_count]',
                    'description' => 'Contador de favoritos',
                    'attributes'  => 'post_id',
                ],
                [
                    'code'        => '[user_favorites]',
                    'description' => 'Lista de favoritos do usuário',
                    'attributes'  => '',
                ],
                [
                    'code'        => '[user_favorite_count]',
                    'description' => 'Total de favoritos do usuário',
                    'attributes'  => '',
                ],
                [
                    'code'        => '[apollo_bookmarks]',
                    'description' => 'Bookmarks salvos',
                    'attributes'  => '',
                ],
            ],
            'Autenticação' => [
                [
                    'code'        => '[apollo_register]',
                    'description' => 'Formulário de registro',
                    'attributes'  => '',
                ],
                [
                    'code'        => '[apollo_login]',
                    'description' => 'Formulário de login',
                    'attributes'  => 'redirect',
                ],
            ],
        ];
    }

    /**
     * Get all placeholders data
     */
    function apollo_events_get_all_placeholders()
    {
        return [
            'Evento' => [
                [
                    'code'        => '{event_title}',
                    'description' => 'Título do evento',
                    'example'     => 'Festa Techno',
                ],
                [
                    'code'        => '{event_date}',
                    'description' => 'Data do evento',
                    'example'     => '25/12/2025',
                ],
                [
                    'code'        => '{event_time}',
                    'description' => 'Horário do evento',
                    'example'     => '23:00',
                ],
                [
                    'code'        => '{event_location}',
                    'description' => 'Local do evento',
                    'example'     => 'Club XYZ',
                ],
                [
                    'code'        => '{event_address}',
                    'description' => 'Endereço completo',
                    'example'     => 'Rua ABC, 123',
                ],
                [
                    'code'        => '{event_description}',
                    'description' => 'Descrição do evento',
                    'example'     => 'Lorem ipsum...',
                ],
                [
                    'code'        => '{event_banner}',
                    'description' => 'URL do banner',
                    'example'     => 'https://...',
                ],
                [
                    'code'        => '{event_url}',
                    'description' => 'URL do evento',
                    'example'     => 'https://site.com/evento/...',
                ],
                [
                    'code'        => '{event_djs}',
                    'description' => 'Lista de DJs',
                    'example'     => 'DJ A, DJ B',
                ],
            ],
            'DJ' => [
                [
                    'code'        => '{dj_name}',
                    'description' => 'Nome do DJ',
                    'example'     => 'Marta Supernova',
                ],
                [
                    'code'        => '{dj_tagline}',
                    'description' => 'Tagline do DJ',
                    'example'     => 'Electro from Rio',
                ],
                [
                    'code'        => '{dj_bio}',
                    'description' => 'Biografia',
                    'example'     => 'Lorem ipsum...',
                ],
                [
                    'code'        => '{dj_soundcloud}',
                    'description' => 'URL SoundCloud',
                    'example'     => 'https://soundcloud.com/...',
                ],
                [
                    'code'        => '{dj_instagram}',
                    'description' => 'Handle Instagram',
                    'example'     => '@djname',
                ],
                [
                    'code'        => '{dj_avatar}',
                    'description' => 'URL da foto',
                    'example'     => 'https://...',
                ],
            ],
            'Local' => [
                [
                    'code'        => '{local_name}',
                    'description' => 'Nome do local',
                    'example'     => 'Club XYZ',
                ],
                [
                    'code'        => '{local_address}',
                    'description' => 'Endereço',
                    'example'     => 'Rua ABC, 123',
                ],
                [
                    'code'        => '{local_city}',
                    'description' => 'Cidade',
                    'example'     => 'Rio de Janeiro',
                ],
                [
                    'code'        => '{local_capacity}',
                    'description' => 'Capacidade',
                    'example'     => '500',
                ],
                [
                    'code'        => '{local_map_lat}',
                    'description' => 'Latitude',
                    'example'     => '-22.9068',
                ],
                [
                    'code'        => '{local_map_lng}',
                    'description' => 'Longitude',
                    'example'     => '-43.1729',
                ],
            ],
            'Usuário' => [
                [
                    'code'        => '{user_name}',
                    'description' => 'Nome do usuário',
                    'example'     => 'João Silva',
                ],
                [
                    'code'        => '{user_email}',
                    'description' => 'Email',
                    'example'     => 'joao@email.com',
                ],
                [
                    'code'        => '{user_avatar}',
                    'description' => 'Avatar URL',
                    'example'     => 'https://...',
                ],
            ],
        ];
    }

    /**
     * Get all forms data
     */
    function apollo_events_get_all_forms()
    {
        return [
            [
                'name'        => 'Submissão de Evento',
                'description' => 'Formulário para usuários submeterem novos eventos para aprovação.',
                'shortcode'   => '[submit_event_form]',
                'cpt'         => 'event_listing',
                'permission'  => 'Usuários logados (subscriber+)',
                'fields'      => [
                    [
                        'label'    => 'Título do Evento',
                        'type'     => 'text',
                        'required' => true,
                        'meta_key' => 'post_title',
                    ],
                    [
                        'label'    => 'Data do Evento',
                        'type'     => 'date',
                        'required' => true,
                        'meta_key' => '_event_start_date',
                    ],
                    [
                        'label'    => 'Horário',
                        'type'     => 'time',
                        'required' => true,
                        'meta_key' => '_event_start_time',
                    ],
                    [
                        'label'    => 'Local',
                        'type'     => 'select',
                        'required' => true,
                        'meta_key' => '_event_local_ids',
                    ],
                    [
                        'label'    => 'DJs',
                        'type'     => 'multiselect',
                        'required' => false,
                        'meta_key' => '_event_dj_ids',
                    ],
                    [
                        'label'    => 'Banner',
                        'type'     => 'file',
                        'required' => false,
                        'meta_key' => '_event_banner',
                    ],
                    [
                        'label'    => 'Descrição',
                        'type'     => 'textarea',
                        'required' => false,
                        'meta_key' => 'post_content',
                    ],
                ],
            ],
            [
                'name'        => 'Cadastro de DJ',
                'description' => 'Formulário para cadastrar novo DJ no sistema.',
                'shortcode'   => '[submit_dj_form]',
                'cpt'         => 'event_dj',
                'permission'  => 'Usuários logados (subscriber+)',
                'fields'      => [
                    [
                        'label'    => 'Nome Artístico',
                        'type'     => 'text',
                        'required' => true,
                        'meta_key' => '_dj_name',
                    ],
                    [
                        'label'    => 'Tagline',
                        'type'     => 'text',
                        'required' => false,
                        'meta_key' => '_dj_tagline',
                    ],
                    [
                        'label'    => 'Bio',
                        'type'     => 'textarea',
                        'required' => false,
                        'meta_key' => '_dj_bio',
                    ],
                    [
                        'label'    => 'Foto',
                        'type'     => 'file',
                        'required' => false,
                        'meta_key' => '_dj_image',
                    ],
                    [
                        'label'    => 'SoundCloud',
                        'type'     => 'url',
                        'required' => false,
                        'meta_key' => '_dj_soundcloud',
                    ],
                    [
                        'label'    => 'Instagram',
                        'type'     => 'text',
                        'required' => false,
                        'meta_key' => '_dj_instagram',
                    ],
                    [
                        'label'    => 'Roles',
                        'type'     => 'text',
                        'required' => false,
                        'meta_key' => '_dj_roles',
                    ],
                ],
            ],
            [
                'name'        => 'Cadastro de Local',
                'description' => 'Formulário para cadastrar novo local/venue.',
                'shortcode'   => '[submit_local_form]',
                'cpt'         => 'event_local',
                'permission'  => 'Usuários logados (subscriber+)',
                'fields'      => [
                    [
                        'label'    => 'Nome do Local',
                        'type'     => 'text',
                        'required' => true,
                        'meta_key' => '_local_name',
                    ],
                    [
                        'label'    => 'Endereço',
                        'type'     => 'text',
                        'required' => true,
                        'meta_key' => '_local_address',
                    ],
                    [
                        'label'    => 'Cidade',
                        'type'     => 'text',
                        'required' => true,
                        'meta_key' => '_local_city',
                    ],
                    [
                        'label'    => 'Capacidade',
                        'type'     => 'number',
                        'required' => false,
                        'meta_key' => '_local_capacity',
                    ],
                    [
                        'label'    => 'Latitude',
                        'type'     => 'text',
                        'required' => false,
                        'meta_key' => '_local_map_lat',
                    ],
                    [
                        'label'    => 'Longitude',
                        'type'     => 'text',
                        'required' => false,
                        'meta_key' => '_local_map_lng',
                    ],
                ],
            ],
        ];
    }

    /**
     * Get all roles data
     */
    function apollo_events_get_all_roles()
    {
        return [
            'capabilities' => [
                'edit_events' => [
                    'label'      => 'Editar Eventos',
                    'admin'      => true,
                    'editor'     => true,
                    'author'     => true,
                    'subscriber' => false,
                ],
                'publish_events' => [
                    'label'      => 'Publicar Eventos',
                    'admin'      => true,
                    'editor'     => true,
                    'author'     => false,
                    'subscriber' => false,
                ],
                'delete_events' => [
                    'label'      => 'Excluir Eventos',
                    'admin'      => true,
                    'editor'     => true,
                    'author'     => false,
                    'subscriber' => false,
                ],
                'edit_djs' => [
                    'label'      => 'Editar DJs',
                    'admin'      => true,
                    'editor'     => true,
                    'author'     => true,
                    'subscriber' => false,
                ],
                'publish_djs' => [
                    'label'      => 'Publicar DJs',
                    'admin'      => true,
                    'editor'     => true,
                    'author'     => false,
                    'subscriber' => false,
                ],
                'edit_locals' => [
                    'label'      => 'Editar Locais',
                    'admin'      => true,
                    'editor'     => true,
                    'author'     => true,
                    'subscriber' => false,
                ],
                'publish_locals' => [
                    'label'      => 'Publicar Locais',
                    'admin'      => true,
                    'editor'     => true,
                    'author'     => false,
                    'subscriber' => false,
                ],
                'manage_event_settings' => [
                    'label'      => 'Gerenciar Configurações',
                    'admin'      => true,
                    'editor'     => false,
                    'author'     => false,
                    'subscriber' => false,
                ],
                'view_event_analytics' => [
                    'label'      => 'Ver Analytics',
                    'admin'      => true,
                    'editor'     => true,
                    'author'     => false,
                    'subscriber' => false,
                ],
                'moderate_events' => [
                    'label'      => 'Moderar Eventos',
                    'admin'      => true,
                    'editor'     => true,
                    'author'     => false,
                    'subscriber' => false,
                ],
            ],
            'custom_roles' => [
                [
                    'name'        => 'Apollo Moderator',
                    'slug'        => 'apollo',
                    'description' => 'Pode moderar eventos, DJs e locais pendentes',
                ],
                [
                    'name'        => 'CENA-RIO User',
                    'slug'        => 'cena_role',
                    'description' => 'Usuário da indústria - pode submeter eventos',
                ],
                [
                    'name'        => 'CENA-RIO Moderator',
                    'slug'        => 'cena_moderator',
                    'description' => 'Moderador interno do CENA-RIO',
                ],
            ],
        ];
    }
} //end if

/**
 * Get all meta keys data
 *
 * NOTE: This function may also be defined in admin-metakeys-page.php
 * Using function_exists() to prevent redeclaration errors
 */
if (! function_exists('apollo_events_get_all_metakeys')) {
    function apollo_events_get_all_metakeys()
    {
        return [
            'Evento (event_listing)' => [
                [
                    'key'         => '_event_start_date',
                    'type'        => 'date',
                    'description' => 'Data de início',
                    'example'     => '2025-12-25',
                ],
                [
                    'key'         => '_event_end_date',
                    'type'        => 'date',
                    'description' => 'Data de término',
                    'example'     => '2025-12-26',
                ],
                [
                    'key'         => '_event_start_time',
                    'type'        => 'time',
                    'description' => 'Horário de início',
                    'example'     => '23:00',
                ],
                [
                    'key'         => '_event_end_time',
                    'type'        => 'time',
                    'description' => 'Horário de término',
                    'example'     => '06:00',
                ],
                [
                    'key'         => '_event_banner',
                    'type'        => 'int|url',
                    'description' => 'Banner do evento',
                    'example'     => '123 ou URL',
                ],
                [
                    'key'         => '_event_local_ids',
                    'type'        => 'string',
                    'description' => 'IDs dos locais',
                    'example'     => '1,2,3',
                ],
                [
                    'key'         => '_event_dj_ids',
                    'type'        => 'string',
                    'description' => 'IDs dos DJs',
                    'example'     => '10,20,30',
                ],
                [
                    'key'         => '_event_timetable',
                    'type'        => 'json',
                    'description' => 'Timetable JSON',
                    'example'     => '[{"dj":1,"time":"23:00"}]',
                ],
                [
                    'key'         => '_event_ticket_url',
                    'type'        => 'url',
                    'description' => 'URL de ingressos',
                    'example'     => 'https://...',
                ],
                [
                    'key'         => '_event_price',
                    'type'        => 'string',
                    'description' => 'Preço',
                    'example'     => 'R$ 50,00',
                ],
                [
                    'key'         => '_apollo_cena_status',
                    'type'        => 'string',
                    'description' => 'Status CENA-RIO',
                    'example'     => 'expected|confirmed',
                ],
            ],
            'DJ (event_dj)' => [
                [
                    'key'         => '_dj_name',
                    'type'        => 'string',
                    'description' => 'Nome artístico',
                    'example'     => 'Marta Supernova',
                ],
                [
                    'key'         => '_dj_tagline',
                    'type'        => 'string',
                    'description' => 'Tagline',
                    'example'     => 'Electro from Rio',
                ],
                [
                    'key'         => '_dj_roles',
                    'type'        => 'string',
                    'description' => 'Roles (vírgula)',
                    'example'     => 'DJ, Producer',
                ],
                [
                    'key'         => '_dj_bio',
                    'type'        => 'text',
                    'description' => 'Biografia completa',
                    'example'     => 'Lorem ipsum...',
                ],
                [
                    'key'         => '_dj_bio_excerpt',
                    'type'        => 'text',
                    'description' => 'Bio resumida',
                    'example'     => 'Lorem...',
                ],
                [
                    'key'         => '_dj_image',
                    'type'        => 'int|url',
                    'description' => 'Foto principal',
                    'example'     => '123',
                ],
                [
                    'key'         => '_dj_soundcloud',
                    'type'        => 'url',
                    'description' => 'URL SoundCloud',
                    'example'     => 'https://soundcloud.com/...',
                ],
                [
                    'key'         => '_dj_soundcloud_track',
                    'type'        => 'url',
                    'description' => 'Track em destaque',
                    'example'     => 'https://soundcloud.com/...',
                ],
                [
                    'key'         => '_dj_track_title',
                    'type'        => 'string',
                    'description' => 'Título da track',
                    'example'     => 'Mix Set 2025',
                ],
                [
                    'key'         => '_dj_spotify',
                    'type'        => 'url',
                    'description' => 'URL Spotify',
                    'example'     => 'https://spotify.com/...',
                ],
                [
                    'key'         => '_dj_youtube',
                    'type'        => 'url',
                    'description' => 'URL YouTube',
                    'example'     => 'https://youtube.com/...',
                ],
                [
                    'key'         => '_dj_instagram',
                    'type'        => 'string',
                    'description' => 'Handle Instagram',
                    'example'     => '@djname',
                ],
                [
                    'key'         => '_dj_twitter',
                    'type'        => 'string',
                    'description' => 'Handle Twitter',
                    'example'     => '@djname',
                ],
                [
                    'key'         => '_dj_tiktok',
                    'type'        => 'string',
                    'description' => 'Handle TikTok',
                    'example'     => '@djname',
                ],
                [
                    'key'         => '_dj_facebook',
                    'type'        => 'url',
                    'description' => 'URL Facebook',
                    'example'     => 'https://facebook.com/...',
                ],
                [
                    'key'         => '_dj_media_kit_url',
                    'type'        => 'url',
                    'description' => 'URL Media Kit',
                    'example'     => 'https://drive.google.com/...',
                ],
                [
                    'key'         => '_dj_rider_url',
                    'type'        => 'url',
                    'description' => 'URL Rider Técnico',
                    'example'     => 'https://drive.google.com/...',
                ],
                [
                    'key'         => '_dj_original_project_1',
                    'type'        => 'string',
                    'description' => 'Projeto 1',
                    'example'     => 'Apollo::rio',
                ],
                [
                    'key'         => '_dj_original_project_2',
                    'type'        => 'string',
                    'description' => 'Projeto 2',
                    'example'     => 'Dismantle',
                ],
                [
                    'key'         => '_dj_original_project_3',
                    'type'        => 'string',
                    'description' => 'Projeto 3',
                    'example'     => '',
                ],
                [
                    'key'         => '_dj_more_platforms',
                    'type'        => 'string',
                    'description' => 'Outras plataformas',
                    'example'     => 'Bandcamp, Beatport',
                ],
            ],
            'Local (event_local)' => [
                [
                    'key'         => '_local_name',
                    'type'        => 'string',
                    'description' => 'Nome do local',
                    'example'     => 'Club XYZ',
                ],
                [
                    'key'         => '_local_address',
                    'type'        => 'string',
                    'description' => 'Endereço',
                    'example'     => 'Rua ABC, 123',
                ],
                [
                    'key'         => '_local_city',
                    'type'        => 'string',
                    'description' => 'Cidade',
                    'example'     => 'Rio de Janeiro',
                ],
                [
                    'key'         => '_local_state',
                    'type'        => 'string',
                    'description' => 'Estado',
                    'example'     => 'RJ',
                ],
                [
                    'key'         => '_local_country',
                    'type'        => 'string',
                    'description' => 'País',
                    'example'     => 'Brasil',
                ],
                [
                    'key'         => '_local_zipcode',
                    'type'        => 'string',
                    'description' => 'CEP',
                    'example'     => '22000-000',
                ],
                [
                    'key'         => '_local_capacity',
                    'type'        => 'int',
                    'description' => 'Capacidade',
                    'example'     => '500',
                ],
                [
                    'key'         => '_local_map_lat',
                    'type'        => 'float',
                    'description' => 'Latitude',
                    'example'     => '-22.9068',
                ],
                [
                    'key'         => '_local_map_lng',
                    'type'        => 'float',
                    'description' => 'Longitude',
                    'example'     => '-43.1729',
                ],
                [
                    'key'         => '_local_map_zoom',
                    'type'        => 'int',
                    'description' => 'Zoom do mapa',
                    'example'     => '15',
                ],
                [
                    'key'         => '_local_image',
                    'type'        => 'int|url',
                    'description' => 'Foto do local',
                    'example'     => '456',
                ],
                [
                    'key'         => '_local_website',
                    'type'        => 'url',
                    'description' => 'Website',
                    'example'     => 'https://clubxyz.com',
                ],
                [
                    'key'         => '_local_instagram',
                    'type'        => 'string',
                    'description' => 'Instagram',
                    'example'     => '@clubxyz',
                ],
            ],
        ];
    }
} //end if

// Render functions for standalone pages
function apollo_events_render_shortcodes_tab()
{
    apollo_events_render_hub_page();
}

function apollo_events_render_metakeys_tab()
{
    $_GET['tab'] = 'metakeys';
    apollo_events_render_hub_page();
}

function apollo_events_render_roles_tab()
{
    $_GET['tab'] = 'roles';
    apollo_events_render_hub_page();
}

function apollo_events_render_settings_tab()
{
    $_GET['tab'] = 'settings';
    apollo_events_render_hub_page();
}

function apollo_events_render_dashboard_tab()
{
    // Call the main plugin's dashboard method
    if (class_exists('Apollo_Events_Manager')) {
        $plugin = Apollo_Events_Manager::get_instance();
        if (method_exists($plugin, 'render_analytics_dashboard')) {
            $plugin->render_analytics_dashboard();
        } else {
            echo '<div class="wrap"><h1>Dashboard</h1><p>Dashboard functionality not available.</p></div>';
        }
    }
}

function apollo_events_render_user_overview_tab()
{
    // Call the main plugin's user overview method
    if (class_exists('Apollo_Events_Manager_Plugin')) {
        $plugin = new Apollo_Events_Manager_Plugin();
        if (method_exists($plugin, 'render_user_overview')) {
            $plugin->render_user_overview();
        } else {
            echo '<div class="wrap"><h1>User Overview</h1><p>User overview functionality not available.</p></div>';
        }
    } else {
        echo '<div class="wrap"><h1>User Overview</h1><p>Plugin not loaded.</p></div>';
    }
}
