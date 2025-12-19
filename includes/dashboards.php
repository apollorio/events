<?php
// phpcs:ignoreFile
/**
 * Apollo Events Manager - Dashboards for Events and DJs
 *
 * Creates dedicated dashboard pages for Events and DJs management
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

class Apollo_Events_Dashboards
{
    public function __construct()
    {
        add_action('admin_menu', [ $this, 'add_dashboard_menus' ]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueue_dashboard_assets' ]);
    }

    /**
     * Add dashboard menu items
     */
    public function add_dashboard_menus()
    {
        // Dashboard for Events
        add_submenu_page(
            'edit.php?post_type=event_listing',
            __('Dashboard de Eventos', 'apollo-events-manager'),
            __('Dashboard', 'apollo-events-manager'),
            'edit_event_listings',
            'apollo-events-dashboard',
            [ $this, 'render_events_dashboard' ]
        );

        // Dashboard for DJs
        add_submenu_page(
            'edit.php?post_type=event_dj',
            __('Dashboard de DJs', 'apollo-events-manager'),
            __('Dashboard', 'apollo-events-manager'),
            'edit_posts',
            'apollo-dj-dashboard',
            [ $this, 'render_dj_dashboard' ]
        );
    }

    /**
     * Enqueue dashboard assets
     */
    public function enqueue_dashboard_assets($hook)
    {
        if (strpos($hook, 'apollo-events-dashboard') === false && strpos($hook, 'apollo-dj-dashboard') === false) {
            return;
        }

        wp_enqueue_style(
            'apollo-dashboards',
            APOLLO_APRIO_URL . 'assets/dashboards.css',
            [],
            APOLLO_APRIO_VERSION
        );

        wp_enqueue_script(
            'apollo-dashboards',
            APOLLO_APRIO_URL . 'assets/dashboards.js',
            [ 'jquery' ],
            APOLLO_APRIO_VERSION,
            true
        );

        wp_localize_script(
            'apollo-dashboards',
            'apolloDashboards',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('apollo_dashboards_nonce'),
            ]
        );
    }

    /**
     * Render Events Dashboard
     */
    public function render_events_dashboard()
    {
        if (! current_user_can('edit_event_listings')) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'apollo-events-manager'));
        }

        // Get stats
        $total_events     = wp_count_posts('event_listing');
        $published_events = $total_events->publish ?? 0;
        $draft_events     = $total_events->draft   ?? 0;
        $pending_events   = $total_events->pending ?? 0;

        // Get upcoming events
        $upcoming_events = get_posts(
            [
                'post_type'      => 'event_listing',
                'post_status'    => 'publish',
                'posts_per_page' => 10,
                'meta_query'     => [
                    [
                        'key'     => '_event_start_date',
                        'value'   => date('Y-m-d'),
                        'compare' => '>=',
                    ],
                ],
                'meta_key' => '_event_start_date',
                'orderby'  => 'meta_value',
                'order'    => 'ASC',
            ]
        );

        // Get recent events
        $recent_events = get_posts(
            [
                'post_type'      => 'event_listing',
                'post_status'    => 'any',
                'posts_per_page' => 10,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ]
        );

        ?>
		<div class="wrap apollo-dashboard-wrap">
			<h1><?php echo esc_html__('Dashboard de Eventos', 'apollo-events-manager'); ?></h1>
			
			<div class="apollo-stats-grid">
				<div class="apollo-stat-card">
					<div class="stat-icon">
						<span class="dashicons dashicons-calendar-alt"></span>
					</div>
					<div class="stat-content">
						<h3><?php echo number_format_i18n($published_events); ?></h3>
						<p><?php echo esc_html__('Eventos Publicados', 'apollo-events-manager'); ?></p>
					</div>
				</div>
				
				<div class="apollo-stat-card">
					<div class="stat-icon">
						<span class="dashicons dashicons-edit"></span>
					</div>
					<div class="stat-content">
						<h3><?php echo number_format_i18n($draft_events); ?></h3>
						<p><?php echo esc_html__('Rascunhos', 'apollo-events-manager'); ?></p>
					</div>
				</div>
				
				<div class="apollo-stat-card">
					<div class="stat-icon">
						<span class="dashicons dashicons-clock"></span>
					</div>
					<div class="stat-content">
						<h3><?php echo number_format_i18n($pending_events); ?></h3>
						<p><?php echo esc_html__('Aguardando Aprovação', 'apollo-events-manager'); ?></p>
					</div>
				</div>
			</div>
			
			<div class="apollo-dashboard-sections">
				<div class="apollo-section">
					<h2><?php echo esc_html__('Próximos Eventos', 'apollo-events-manager'); ?></h2>
					<?php if (! empty($upcoming_events)) : ?>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php echo esc_html__('Evento', 'apollo-events-manager'); ?></th>
									<th><?php echo esc_html__('Data', 'apollo-events-manager'); ?></th>
									<th><?php echo esc_html__('Local', 'apollo-events-manager'); ?></th>
									<th><?php echo esc_html__('Ações', 'apollo-events-manager'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
                                foreach ($upcoming_events as $event) :
                                    $event_id    = $event->ID;
                                    $event_title = get_post_meta($event_id, '_event_title', true) ?: $event->post_title;
                                    $event_date  = get_post_meta($event_id, '_event_start_date', true);
                                    $local_id    = get_post_meta($event_id, '_event_local_ids', true);
                                    $local_id    = is_array($local_id) ? (int) reset($local_id) : (int) $local_id;
                                    $local_name  = '';
                                    if ($local_id) {
                                        $local_post = get_post($local_id);
                                        if ($local_post) {
                                            $local_name = get_post_meta($local_id, '_local_name', true) ?: $local_post->post_title;
                                        }
                                    }
                                    ?>
									<tr>
										<td><strong><?php echo esc_html($event_title); ?></strong></td>
										<td><?php echo $event_date ? esc_html(date_i18n('d/m/Y', strtotime($event_date))) : '-'; ?></td>
										<td><?php echo esc_html($local_name ?: '-'); ?></td>
										<td>
											<a href="<?php echo esc_url(get_edit_post_link($event_id)); ?>" class="button button-small"><?php echo esc_html__('Editar', 'apollo-events-manager'); ?></a>
											<a href="<?php echo esc_url(get_permalink($event_id)); ?>" class="button button-small" target="_blank"><?php echo esc_html__('Ver', 'apollo-events-manager'); ?></a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p><?php echo esc_html__('Nenhum evento futuro encontrado.', 'apollo-events-manager'); ?></p>
					<?php endif; ?>
				</div>
				
				<div class="apollo-section">
					<h2><?php echo esc_html__('Eventos Recentes', 'apollo-events-manager'); ?></h2>
					<?php if (! empty($recent_events)) : ?>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php echo esc_html__('Evento', 'apollo-events-manager'); ?></th>
									<th><?php echo esc_html__('Status', 'apollo-events-manager'); ?></th>
									<th><?php echo esc_html__('Data de Criação', 'apollo-events-manager'); ?></th>
									<th><?php echo esc_html__('Ações', 'apollo-events-manager'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
                                foreach ($recent_events as $event) :
                                    $event_id    = $event->ID;
                                    $event_title = get_post_meta($event_id, '_event_title', true) ?: $event->post_title;
                                    ?>
									<tr>
										<td><strong><?php echo esc_html($event_title); ?></strong></td>
										<td>
											<span class="status-<?php echo esc_attr($event->post_status); ?>">
												<?php echo esc_html(ucfirst($event->post_status)); ?>
											</span>
										</td>
										<td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($event->post_date))); ?></td>
										<td>
											<a href="<?php echo esc_url(get_edit_post_link($event_id)); ?>" class="button button-small"><?php echo esc_html__('Editar', 'apollo-events-manager'); ?></a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p><?php echo esc_html__('Nenhum evento encontrado.', 'apollo-events-manager'); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
    }

    /**
     * Render DJ Dashboard
     */
    public function render_dj_dashboard()
    {
        if (! current_user_can('edit_posts')) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'apollo-events-manager'));
        }

        // Get stats
        $total_djs     = wp_count_posts('event_dj');
        $published_djs = $total_djs->publish ?? 0;
        $draft_djs     = $total_djs->draft   ?? 0;

        // Get recent DJs
        $recent_djs = get_posts(
            [
                'post_type'      => 'event_dj',
                'post_status'    => 'any',
                'posts_per_page' => 20,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ]
        );

        ?>
		<div class="wrap apollo-dashboard-wrap">
			<h1><?php echo esc_html__('Dashboard de DJs', 'apollo-events-manager'); ?></h1>
			
			<div class="apollo-stats-grid">
				<div class="apollo-stat-card">
					<div class="stat-icon">
						<span class="dashicons dashicons-admin-users"></span>
					</div>
					<div class="stat-content">
						<h3><?php echo number_format_i18n($published_djs); ?></h3>
						<p><?php echo esc_html__('DJs Publicados', 'apollo-events-manager'); ?></p>
					</div>
				</div>
				
				<div class="apollo-stat-card">
					<div class="stat-icon">
						<span class="dashicons dashicons-edit"></span>
					</div>
					<div class="stat-content">
						<h3><?php echo number_format_i18n($draft_djs); ?></h3>
						<p><?php echo esc_html__('Rascunhos', 'apollo-events-manager'); ?></p>
					</div>
				</div>
			</div>
			
			<div class="apollo-dashboard-sections">
				<div class="apollo-section">
					<h2><?php echo esc_html__('DJs Recentes', 'apollo-events-manager'); ?></h2>
					<?php if (! empty($recent_djs)) : ?>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php echo esc_html__('DJ', 'apollo-events-manager'); ?></th>
									<th><?php echo esc_html__('Status', 'apollo-events-manager'); ?></th>
									<th><?php echo esc_html__('Data de Criação', 'apollo-events-manager'); ?></th>
									<th><?php echo esc_html__('Ações', 'apollo-events-manager'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
                                foreach ($recent_djs as $dj) :
                                    $dj_id   = $dj->ID;
                                    $dj_name = get_post_meta($dj_id, '_dj_name', true) ?: $dj->post_title;
                                    ?>
									<tr>
										<td><strong><?php echo esc_html($dj_name); ?></strong></td>
										<td>
											<span class="status-<?php echo esc_attr($dj->post_status); ?>">
												<?php echo esc_html(ucfirst($dj->post_status)); ?>
											</span>
										</td>
										<td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($dj->post_date))); ?></td>
										<td>
											<a href="<?php echo esc_url(get_edit_post_link($dj_id)); ?>" class="button button-small"><?php echo esc_html__('Editar', 'apollo-events-manager'); ?></a>
											<a href="<?php echo esc_url(get_permalink($dj_id)); ?>" class="button button-small" target="_blank"><?php echo esc_html__('Ver', 'apollo-events-manager'); ?></a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p><?php echo esc_html__('Nenhum DJ encontrado.', 'apollo-events-manager'); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
    }
}

// Initialize
new Apollo_Events_Dashboards();
