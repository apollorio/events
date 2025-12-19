<?php
// phpcs:ignoreFile
/**
 * Apollo Events Manager - Dashboard Components
 * ShadCN New York Components for Events Dashboard
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Render statistics cards
 *
 * @param array $stats Array of stat data
 * @return void
 */
function apollo_events_render_stat_cards($stats = [])
{
    $defaults = [
        [
            'title'       => __('Total de Eventos', 'apollo-events-manager'),
            'value'       => 0,
            'trend'       => 0,
            'trend_label' => __('vs mês anterior', 'apollo-events-manager'),
            'icon'        => 'calendar',
            'color'       => 'primary',
            'tooltip'     => __('Número total de eventos que você criou', 'apollo-events-manager'),
        ],
        [
            'title'       => __('Visualizações', 'apollo-events-manager'),
            'value'       => 0,
            'trend'       => 0,
            'trend_label' => __('vs mês anterior', 'apollo-events-manager'),
            'icon'        => 'eye',
            'color'       => 'purple',
            'tooltip'     => __('Total de vezes que seus eventos foram vistos (modal + página)', 'apollo-events-manager'),
        ],
        [
            'title'       => __('Cliques em Modal', 'apollo-events-manager'),
            'value'       => 0,
            'trend'       => 0,
            'trend_label' => __('vs mês anterior', 'apollo-events-manager'),
            'icon'        => 'popup',
            'color'       => 'green',
            'tooltip'     => __('Visualizações via popup/modal nos cards de eventos', 'apollo-events-manager'),
        ],
        [
            'title'       => __('Visualizações de Página', 'apollo-events-manager'),
            'value'       => 0,
            'trend'       => 0,
            'trend_label' => __('vs mês anterior', 'apollo-events-manager'),
            'icon'        => 'page',
            'color'       => 'orange',
            'tooltip'     => __('Visitas diretas na página completa do evento', 'apollo-events-manager'),
        ],
    ];

    $stats = ! empty($stats) ? $stats : $defaults;

    $icons = [
        'calendar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'eye'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
        'popup'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6v6H9z"/></svg>',
        'page'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
        'heart'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
        'users'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    ];

    $colors = [
        'primary' => 'hsl(var(--primary))',
        'purple'  => 'hsl(262 83% 58%)',
        'green'   => 'hsl(142 76% 36%)',
        'orange'  => 'hsl(24 95% 53%)',
        'blue'    => 'hsl(217 91% 60%)',
        'red'     => 'hsl(0 84% 60%)',
    ];
    ?>
	<div class="stats-grid">
		<?php
        foreach ($stats as $stat) :
            $icon     = isset($icons[ $stat['icon'] ]) ? $icons[ $stat['icon'] ] : $icons['calendar'];
            $color    = isset($colors[ $stat['color'] ]) ? $colors[ $stat['color'] ] : $colors['primary'];
            $trend_up = $stat['trend'] >= 0;
            $tooltip  = isset($stat['tooltip']) ? $stat['tooltip'] : '';
            ?>
		<div class="stat-card"<?php echo ! empty($tooltip) ? ' data-tooltip="' . esc_attr($tooltip) . '"' : ''; ?>>
			<div class="stat-card-header">
				<span class="stat-card-title"><?php echo esc_html($stat['title']); ?></span>
				<div class="stat-card-icon" style="background: <?php echo $color; ?>1a; color: <?php echo $color; ?>;">
					<?php echo $icon; ?>
				</div>
			</div>
			<div class="stat-card-value">
				<?php echo number_format($stat['value'], 0, ',', '.'); ?>
			</div>
			<?php if (isset($stat['description'])) : ?>
			<p class="stat-card-description"><?php echo esc_html($stat['description']); ?></p>
			<?php endif; ?>
			<?php if (isset($stat['trend'])) : ?>
			<div class="stat-card-trend <?php echo $trend_up ? 'trend-up' : 'trend-down'; ?>">
				<?php if ($trend_up) : ?>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M7 17L17 7M17 7H7M17 7V17"/>
					</svg>
				<?php else : ?>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M7 7L17 17M17 17H7M17 17V7"/>
					</svg>
				<?php endif; ?>
				<span><?php echo $trend_up ? '+' : ''; ?><?php echo $stat['trend']; ?>%</span>
				<span style="color: hsl(var(--muted-foreground));"><?php echo esc_html($stat['trend_label']); ?></span>
			</div>
			<?php endif; ?>
		</div>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Render events data table
 *
 * @param array $events Array of event data
 * @return void
 */
function apollo_events_render_data_table($events = [])
{
    $status_labels = [
        'publish' => [
            'label' => __('Publicado', 'apollo-events-manager'),
            'class' => 'badge-published',
        ],
        'pending' => [
            'label' => __('Pendente', 'apollo-events-manager'),
            'class' => 'badge-pending',
        ],
        'draft' => [
            'label' => __('Rascunho', 'apollo-events-manager'),
            'class' => 'badge-draft',
        ],
        'cancelled' => [
            'label' => __('Cancelado', 'apollo-events-manager'),
            'class' => 'badge-cancelled',
        ],
    ];
    ?>
	<div class="card">
		<div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
			<div>
				<h3 class="card-title"><?php echo esc_html__('Eventos Recentes', 'apollo-events-manager'); ?></h3>
				<p class="card-description"><?php echo esc_html__('Seus eventos mais recentes', 'apollo-events-manager'); ?></p>
			</div>
			<a href="<?php echo esc_url(admin_url('edit.php?post_type=event_listing')); ?>" class="btn btn-outline btn-sm">
				<?php echo esc_html__('Ver Todos', 'apollo-events-manager'); ?>
			</a>
		</div>
		
		<?php if (empty($events)) : ?>
		<div class="card-content" style="text-align: center; padding: 3rem;">
			<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--muted-foreground))" stroke-width="1.5" style="margin: 0 auto 1rem;">
				<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
				<line x1="16" y1="2" x2="16" y2="6"/>
				<line x1="8" y1="2" x2="8" y2="6"/>
				<line x1="3" y1="10" x2="21" y2="10"/>
			</svg>
			<p style="color: hsl(var(--muted-foreground)); margin: 0 0 1rem;"><?php echo esc_html__('Nenhum evento criado ainda', 'apollo-events-manager'); ?></p>
			<a href="<?php echo esc_url(admin_url('post-new.php?post_type=event_listing')); ?>" class="btn btn-primary">
				<?php echo esc_html__('Criar Primeiro Evento', 'apollo-events-manager'); ?>
			</a>
		</div>
		<?php else : ?>
		<div style="overflow-x: auto;">
			<table class="data-table">
				<thead>
					<tr>
						<th><?php echo esc_html__('Evento', 'apollo-events-manager'); ?></th>
						<th><?php echo esc_html__('Data', 'apollo-events-manager'); ?></th>
						<th><?php echo esc_html__('Status', 'apollo-events-manager'); ?></th>
						<th><?php echo esc_html__('Visualizações', 'apollo-events-manager'); ?></th>
						<th style="text-align: right;"><?php echo esc_html__('Ações', 'apollo-events-manager'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
                    foreach ($events as $event) :
                        $status = $status_labels[ $event['status'] ] ?? $status_labels['draft'];
                        ?>
					<tr>
						<td>
							<div style="display: flex; align-items: center; gap: 0.75rem;">
								<?php if (! empty($event['thumbnail'])) : ?>
								<div style="width: 2.5rem; height: 2.5rem; border-radius: var(--radius); overflow: hidden; flex-shrink: 0;">
									<img src="<?php echo esc_url($event['thumbnail']); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
								</div>
								<?php else : ?>
								<div style="width: 2.5rem; height: 2.5rem; border-radius: var(--radius); background: hsl(var(--muted)); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--muted-foreground))" stroke-width="2">
										<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
										<line x1="16" y1="2" x2="16" y2="6"/>
										<line x1="8" y1="2" x2="8" y2="6"/>
									</svg>
								</div>
								<?php endif; ?>
								<div>
									<p style="font-weight: 500; color: hsl(var(--foreground)); margin: 0;">
										<?php echo esc_html($event['title']); ?>
									</p>
									<?php if (! empty($event['venue'])) : ?>
									<p style="font-size: 0.75rem; color: hsl(var(--muted-foreground)); margin: 0.125rem 0 0;">
										<?php echo esc_html($event['venue']); ?>
									</p>
									<?php endif; ?>
								</div>
							</div>
						</td>
						<td>
							<span style="font-size: 0.875rem; color: hsl(var(--foreground));">
								<?php echo esc_html($event['date']); ?>
							</span>
						</td>
						<td>
							<span class="badge <?php echo esc_attr($status['class']); ?>">
								<?php echo esc_html($status['label']); ?>
							</span>
						</td>
						<td>
							<span style="font-weight: 500;"><?php echo number_format($event['views'], 0, ',', '.'); ?></span>
						</td>
						<td>
							<div style="display: flex; justify-content: flex-end; gap: 0.375rem;">
								<a href="<?php echo esc_url($event['permalink']); ?>" class="btn btn-ghost btn-sm" title="<?php echo esc_attr__('Ver', 'apollo-events-manager'); ?>">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
										<circle cx="12" cy="12" r="3"/>
									</svg>
								</a>
								<a href="<?php echo esc_url($event['edit_link']); ?>" class="btn btn-ghost btn-sm" title="<?php echo esc_attr__('Editar', 'apollo-events-manager'); ?>">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
										<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
									</svg>
								</a>
							</div>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render chart container with Chart.js
 *
 * @param array $args Chart configuration
 * @return void
 */
function apollo_events_render_chart($args = [])
{
    $defaults = [
        'id'          => 'chart-' . wp_rand(1000, 9999),
        'title'       => __('Gráfico', 'apollo-events-manager'),
        'description' => '',
        'type'        => 'bar',
        'height'      => 300,
        'data'        => [],
    ];

    $args = wp_parse_args($args, $defaults);
    ?>
	<div class="card">
		<div class="card-header">
			<h3 class="card-title"><?php echo esc_html($args['title']); ?></h3>
			<?php if (! empty($args['description'])) : ?>
			<p class="card-description"><?php echo esc_html($args['description']); ?></p>
			<?php endif; ?>
		</div>
		<div class="card-content">
			<div class="chart-container" style="height: <?php echo intval($args['height']); ?>px;">
				<canvas id="<?php echo esc_attr($args['id']); ?>"></canvas>
			</div>
		</div>
	</div>
	
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		const ctx = document.getElementById('<?php echo esc_js($args['id']); ?>');
		if (ctx && typeof Chart !== 'undefined') {
			new Chart(ctx, {
				type: '<?php echo esc_js($args['type']); ?>',
				data: <?php echo json_encode($args['data']); ?>,
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: <?php echo ! empty($args['data']['datasets']) && count($args['data']['datasets']) > 1 ? 'true' : 'false'; ?>
						},
						tooltip: {
							enabled: true,
							backgroundColor: 'hsl(240 10% 3.9%)',
							titleColor: 'hsl(0 0% 98%)',
							bodyColor: 'hsl(0 0% 98%)',
							borderColor: 'hsl(240 3.7% 15.9%)',
							borderWidth: 1,
							cornerRadius: 6,
							padding: 12,
							displayColors: true,
							callbacks: {
								title: function(context) {
									return context[0].label;
								},
								label: function(context) {
									const label = context.dataset.label || '';
									const value = context.parsed.y || context.parsed;
									return `${label}: ${value.toLocaleString('pt-BR')}`;
								}
							}
						}
					},
					scales: {
						x: {
							grid: { display: false },
							border: { display: false }
						},
						y: {
							beginAtZero: true,
							grid: { color: 'hsl(240 5.9% 90%)' },
							border: { display: false }
						}
					}
				}
			});
		}
	});
	</script>
	<?php
}

/**
 * Render activity feed
 *
 * @param array $activities Array of activity items
 * @return void
 */
function apollo_events_render_activity_feed($activities = [])
{
    ?>
	<div class="card">
		<div class="card-header">
			<h3 class="card-title"><?php echo esc_html__('Atividade Recente', 'apollo-events-manager'); ?></h3>
			<p class="card-description"><?php echo esc_html__('Últimas ações nos seus eventos', 'apollo-events-manager'); ?></p>
		</div>
		<div class="card-content">
			<?php if (empty($activities)) : ?>
			<p style="text-align: center; color: hsl(var(--muted-foreground)); padding: 2rem 0;">
				<?php echo esc_html__('Nenhuma atividade recente', 'apollo-events-manager'); ?>
			</p>
			<?php else : ?>
			<div style="display: flex; flex-direction: column; gap: 1rem;">
				<?php foreach ($activities as $activity) : ?>
				<div style="display: flex; align-items: flex-start; gap: 0.75rem; padding-bottom: 1rem; border-bottom: 1px solid hsl(var(--border));">
					<div style="width: 2rem; height: 2rem; border-radius: 9999px; background: hsl(var(--muted)); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
						<?php echo $activity['icon'] ?? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--muted-foreground))" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>'; ?>
					</div>
					<div style="flex: 1; min-width: 0;">
						<p style="font-size: 0.875rem; color: hsl(var(--foreground)); margin: 0;">
							<?php echo wp_kses_post($activity['message']); ?>
						</p>
						<p style="font-size: 0.75rem; color: hsl(var(--muted-foreground)); margin: 0.25rem 0 0;">
							<?php echo esc_html($activity['time']); ?>
						</p>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
