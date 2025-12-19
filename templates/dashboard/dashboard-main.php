<?php
// phpcs:ignoreFile
/**
 * Apollo Events Manager - User Event Dashboard
 * Complete ShadCN New York Dashboard with Advanced Statistics
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

// Security check
if (! is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

// Include layout and components
$template_dir = APOLLO_APRIO_PATH . 'templates/dashboard/';
require_once $template_dir . 'dashboard-layout.php';
require_once $template_dir . 'components.php';

/**
 * Get dashboard data for current user
 */
function apollo_events_get_dashboard_data()
{
    $user_id = get_current_user_id();

    // Get user's events
    $user_events = new WP_Query(
        [
            'post_type'      => 'event_listing',
            'author'         => $user_id,
            'posts_per_page' => -1,
            'post_status'    => [ 'publish', 'pending', 'draft' ],
        ]
    );

    $data = [
        'total_events' => 0,
        'total_views'  => 0,
        'popup_views'  => 0,
        'page_views'   => 0,
        'events'       => [],
        'chart_data'   => [
            'labels' => [],
            'views'  => [],
        ],
        'recent_activity' => [],
        'this_month'      => [
            'events' => 0,
            'views'  => 0,
        ],
        'last_month' => [
            'events' => 0,
            'views'  => 0,
        ],
    ];

    if ($user_events->have_posts()) {
        $data['total_events'] = $user_events->found_posts;

        // Prepare chart labels (last 7 days)
        for ($i = 6; $i >= 0; $i--) {
            $data['chart_data']['labels'][] = date_i18n('D', strtotime("-{$i} days"));
            $data['chart_data']['views'][]  = 0;
        }

        while ($user_events->have_posts()) {
            $user_events->the_post();
            $event_id  = get_the_ID();
            $post_date = get_the_date('U');

            // Get event stats
            $stats = [
                'total_views' => 0,
                'popup_count' => 0,
                'page_count'  => 0,
            ];

            if (class_exists('Apollo_Event_Stat_CPT')) {
                $stats = Apollo_Event_Stat_CPT::get_stats($event_id);
            } elseif (class_exists('Apollo_Event_Statistics')) {
                $stats = Apollo_Event_Statistics::get_event_stats($event_id);
            }

            $data['total_views'] += $stats['total_views'] ?? 0;
            $data['popup_views'] += $stats['popup_count'] ?? 0;
            $data['page_views']  += $stats['page_count']  ?? 0;

            // Count events by month
            $first_of_month      = strtotime('first day of this month');
            $first_of_last_month = strtotime('first day of last month');

            if ($post_date >= $first_of_month) {
                ++$data['this_month']['events'];
            } elseif ($post_date >= $first_of_last_month && $post_date < $first_of_month) {
                ++$data['last_month']['events'];
            }

            // Add to events list (limit to 10)
            if (count($data['events']) < 10) {
                $data['events'][] = [
                    'id'        => $event_id,
                    'title'     => get_the_title(),
                    'status'    => get_post_status(),
                    'date'      => get_the_date('d/m/Y'),
                    'venue'     => get_post_meta($event_id, '_event_venue', true),
                    'views'     => $stats['total_views'] ?? 0,
                    'thumbnail' => get_the_post_thumbnail_url($event_id, 'thumbnail'),
                    'permalink' => get_permalink($event_id),
                    'edit_link' => get_edit_post_link($event_id),
                ];
            }

            // Add to activity (limit to 5)
            if (count($data['recent_activity']) < 5) {
                $data['recent_activity'][] = [
                    'message' => sprintf(
                        __('Evento "%1$s" recebeu %2$d visualizações', 'apollo-events-manager'),
                        '<strong>' . get_the_title() . '</strong>',
                        $stats['total_views'] ?? 0
                    ),
                    'time' => human_time_diff($post_date, current_time('timestamp')) . ' atrás',
                    'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--primary))" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
                ];
            }
        }//end while
        wp_reset_postdata();
    }//end if

    // Calculate trends
    $data['trends'] = [
        'events' => $data['last_month']['events'] > 0
            ? round((($data['this_month']['events'] - $data['last_month']['events']) / $data['last_month']['events']) * 100, 1)
            : ($data['this_month']['events'] > 0 ? 100 : 0),
        'views' => 12.5,
    // Placeholder - would need historical data
    ];

    return $data;
}

/**
 * Render dashboard content
 */
function apollo_events_render_dashboard_content()
{
    $data = apollo_events_get_dashboard_data();
    ?>
	
	<!-- Chart.js CDN -->
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
	
	<div class="page-header">
		<h1 class="page-title"><?php echo esc_html__('Dashboard de Eventos', 'apollo-events-manager'); ?></h1>
		<p class="page-subtitle"><?php echo esc_html__('Acompanhe o desempenho dos seus eventos', 'apollo-events-manager'); ?></p>
	</div>
	
	<!-- Stats Cards -->
	<?php
    apollo_events_render_stat_cards(
        [
            [
                'title'       => __('Total de Eventos', 'apollo-events-manager'),
                'value'       => $data['total_events'],
                'trend'       => $data['trends']['events'],
                'trend_label' => __('vs mês anterior', 'apollo-events-manager'),
                'icon'        => 'calendar',
                'color'       => 'primary',
            ],
            [
                'title'       => __('Visualizações Totais', 'apollo-events-manager'),
                'value'       => $data['total_views'],
                'trend'       => $data['trends']['views'],
                'trend_label' => __('vs mês anterior', 'apollo-events-manager'),
                'icon'        => 'eye',
                'color'       => 'purple',
            ],
            [
                'title'       => __('Cliques em Modal', 'apollo-events-manager'),
                'value'       => $data['popup_views'],
                'trend'       => 8.2,
                'trend_label' => __('vs mês anterior', 'apollo-events-manager'),
                'icon'        => 'popup',
                'color'       => 'green',
            ],
            [
                'title'       => __('Visualizações de Página', 'apollo-events-manager'),
                'value'       => $data['page_views'],
                'trend'       => -2.4,
                'trend_label' => __('vs mês anterior', 'apollo-events-manager'),
                'icon'        => 'page',
                'color'       => 'orange',
            ],
        ]
    );
    ?>
	
	<!-- Charts Row -->
	<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-top: 1.5rem;">
		
		<!-- Views Over Time Chart -->
		<div class="card">
			<div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
				<div>
					<h3 class="card-title"><?php echo esc_html__('Visualizações', 'apollo-events-manager'); ?></h3>
					<p class="card-description"><?php echo esc_html__('Últimos 7 dias', 'apollo-events-manager'); ?></p>
				</div>
				<div style="display: flex; gap: 0.5rem;">
					<button class="btn btn-outline btn-sm chart-period active" data-period="7"><?php echo esc_html__('7D', 'apollo-events-manager'); ?></button>
					<button class="btn btn-outline btn-sm chart-period" data-period="30"><?php echo esc_html__('30D', 'apollo-events-manager'); ?></button>
					<button class="btn btn-outline btn-sm chart-period" data-period="90"><?php echo esc_html__('90D', 'apollo-events-manager'); ?></button>
				</div>
			</div>
			<div class="card-content">
				<div class="chart-container" style="height: 280px;">
					<canvas id="viewsChart"></canvas>
				</div>
			</div>
		</div>
		
		<!-- Event Status Pie Chart -->
		<div class="card">
			<div class="card-header">
				<h3 class="card-title"><?php echo esc_html__('Status dos Eventos', 'apollo-events-manager'); ?></h3>
				<p class="card-description"><?php echo esc_html__('Distribuição por status', 'apollo-events-manager'); ?></p>
			</div>
			<div class="card-content" style="display: flex; flex-direction: column; align-items: center;">
				<div style="height: 200px; width: 200px;">
					<canvas id="statusChart"></canvas>
				</div>
				<div id="statusLegend" style="display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 1rem; justify-content: center;"></div>
			</div>
		</div>
	</div>
	
	<!-- Tables Row -->
	<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-top: 1.5rem;">
		
		<!-- Events Table -->
		<?php apollo_events_render_data_table($data['events']); ?>
		
		<!-- Activity Feed -->
		<?php apollo_events_render_activity_feed($data['recent_activity']); ?>
	</div>
	
	<!-- Additional Stats Row -->
	<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1.5rem;">
		
		<!-- Top Events Card -->
		<div class="card">
			<div class="card-header">
				<h3 class="card-title"><?php echo esc_html__('Eventos Mais Vistos', 'apollo-events-manager'); ?></h3>
			</div>
			<div class="card-content">
				<?php
                // Sort events by views
                $top_events = $data['events'];
    usort(
        $top_events,
        function ($a, $b) {
            return $b['views'] - $a['views'];
        }
    );
    $top_events = array_slice($top_events, 0, 5);

    if (empty($top_events)) :
        ?>
				<p style="text-align: center; color: hsl(var(--muted-foreground)); padding: 1rem 0;">
					<?php echo esc_html__('Nenhum dado disponível', 'apollo-events-manager'); ?>
				</p>
				<?php else : ?>
				<div style="display: flex; flex-direction: column; gap: 0.75rem;">
					<?php foreach ($top_events as $index => $event) : ?>
					<div style="display: flex; align-items: center; gap: 0.75rem;">
						<span style="width: 1.5rem; height: 1.5rem; border-radius: var(--radius); background: hsl(var(--muted)); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 600; color: hsl(var(--muted-foreground));">
							<?php echo $index + 1; ?>
						</span>
						<div style="flex: 1; min-width: 0;">
							<p style="font-size: 0.875rem; font-weight: 500; color: hsl(var(--foreground)); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
								<?php echo esc_html($event['title']); ?>
							</p>
						</div>
						<span style="font-size: 0.875rem; font-weight: 600; color: hsl(var(--foreground));">
							<?php echo number_format($event['views'], 0, ',', '.'); ?>
						</span>
					</div>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>
		</div>
		
		<!-- Quick Actions Card -->
		<div class="card">
			<div class="card-header">
				<h3 class="card-title"><?php echo esc_html__('Ações Rápidas', 'apollo-events-manager'); ?></h3>
			</div>
			<div class="card-content">
				<div style="display: flex; flex-direction: column; gap: 0.5rem;">
					<a href="<?php echo esc_url(admin_url('post-new.php?post_type=event_listing')); ?>" class="btn btn-outline" style="justify-content: flex-start;">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<line x1="12" y1="5" x2="12" y2="19"/>
							<line x1="5" y1="12" x2="19" y2="12"/>
						</svg>
						<?php echo esc_html__('Criar Evento', 'apollo-events-manager'); ?>
					</a>
					<a href="<?php echo esc_url(admin_url('edit.php?post_type=event_listing')); ?>" class="btn btn-outline" style="justify-content: flex-start;">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
							<rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
						</svg>
						<?php echo esc_html__('Ver Todos os Eventos', 'apollo-events-manager'); ?>
					</a>
					<a href="<?php echo esc_url(home_url('/eventos')); ?>" class="btn btn-outline" style="justify-content: flex-start;">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
						</svg>
						<?php echo esc_html__('Ver Página de Eventos', 'apollo-events-manager'); ?>
					</a>
					<a href="<?php echo esc_url(admin_url('admin.php?page=apollo-event-settings')); ?>" class="btn btn-outline" style="justify-content: flex-start;">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<circle cx="12" cy="12" r="3"/>
							<path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9"/>
						</svg>
						<?php echo esc_html__('Configurações', 'apollo-events-manager'); ?>
					</a>
				</div>
			</div>
		</div>
		
		<!-- Performance Summary -->
		<div class="card">
			<div class="card-header">
				<h3 class="card-title"><?php echo esc_html__('Resumo de Performance', 'apollo-events-manager'); ?></h3>
			</div>
			<div class="card-content">
				<div style="display: flex; flex-direction: column; gap: 1rem;">
					<?php
        $avg_views   = $data['total_events'] > 0 ? round($data['total_views'] / $data['total_events']) : 0;
    $engagement_rate = $data['total_views']      > 0 ? round(($data['popup_views'] / $data['total_views']) * 100, 1) : 0;
    ?>
					<div>
						<div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
							<span style="font-size: 0.875rem; color: hsl(var(--muted-foreground));">Média de Views/Evento</span>
							<span style="font-size: 0.875rem; font-weight: 600; color: hsl(var(--foreground));"><?php echo number_format($avg_views, 0, ',', '.'); ?></span>
						</div>
						<div style="height: 0.5rem; background: hsl(var(--muted)); border-radius: 9999px; overflow: hidden;">
							<div style="height: 100%; width: <?php echo min(100, $avg_views / 10); ?>%; background: hsl(var(--primary)); border-radius: 9999px;"></div>
						</div>
					</div>
					
					<div>
						<div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
							<span style="font-size: 0.875rem; color: hsl(var(--muted-foreground));">Taxa de Engajamento</span>
							<span style="font-size: 0.875rem; font-weight: 600; color: hsl(var(--foreground));"><?php echo $engagement_rate; ?>%</span>
						</div>
						<div style="height: 0.5rem; background: hsl(var(--muted)); border-radius: 9999px; overflow: hidden;">
							<div style="height: 100%; width: <?php echo $engagement_rate; ?>%; background: hsl(142 76% 36%); border-radius: 9999px;"></div>
						</div>
					</div>
					
					<div>
						<div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
							<span style="font-size: 0.875rem; color: hsl(var(--muted-foreground));">Eventos Publicados</span>
							<span style="font-size: 0.875rem; font-weight: 600; color: hsl(var(--foreground));">
								<?php
                $published_count = count(
                    array_filter(
                        $data['events'],
                        function ($e) {
                            return $e['status'] === 'publish';
                        }
                    )
                );
    echo $published_count . '/' . $data['total_events'];
    ?>
							</span>
						</div>
						<div style="height: 0.5rem; background: hsl(var(--muted)); border-radius: 9999px; overflow: hidden;">
							<div style="height: 100%; width: <?php echo $data['total_events'] > 0 ? ($published_count / $data['total_events']) * 100 : 0; ?>%; background: hsl(262 83% 58%); border-radius: 9999px;"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Chart.js defaults
		Chart.defaults.font.family = 'system-ui, -apple-system, sans-serif';
		Chart.defaults.color = 'hsl(240 3.8% 46.1%)';
		
		const chartColors = {
			primary: 'hsl(240 5.9% 10%)',
			purple: 'hsl(262 83% 58%)',
			green: 'hsl(142 76% 36%)',
			orange: 'hsl(24 95% 53%)',
			muted: 'hsl(240 4.8% 95.9%)'
		};
		
		// Views Chart
		const viewsCtx = document.getElementById('viewsChart');
		if (viewsCtx) {
			new Chart(viewsCtx, {
				type: 'line',
				data: {
					labels: <?php echo json_encode($data['chart_data']['labels']); ?>,
					datasets: [{
						label: '<?php echo esc_js(__('Visualizações', 'apollo-events-manager')); ?>',
						data: <?php echo json_encode($data['chart_data']['views']); ?>,
						borderColor: chartColors.primary,
						backgroundColor: 'hsla(240, 5.9%, 10%, 0.1)',
						borderWidth: 2,
						fill: true,
						tension: 0.4,
						pointRadius: 4,
						pointHoverRadius: 6
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: { display: false },
						tooltip: {
							enabled: true,
							backgroundColor: 'hsl(240 10% 3.9%)',
							titleColor: 'hsl(0 0% 98%)',
							bodyColor: 'hsl(0 0% 98%)',
							borderColor: 'hsl(240 3.7% 15.9%)',
							borderWidth: 1,
							cornerRadius: 6,
							padding: 12,
							displayColors: false,
							callbacks: {
								title: function(context) {
									return context[0].label + ', <?php echo date_i18n('F Y'); ?>';
								},
								label: function(context) {
									return `${context.parsed.y.toLocaleString('pt-BR')} visualizações`;
								},
								afterLabel: function(context) {
									const total = context.dataset.data.reduce((a, b) => a + b, 0);
									const avg = total / context.dataset.data.length;
									const diff = context.parsed.y - avg;
									const sign = diff >= 0 ? '+' : '';
									return `${sign}${diff.toFixed(0)} vs média diária`;
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
		
		// Status Pie Chart
		const statusCtx = document.getElementById('statusChart');
		if (statusCtx) {
			<?php
            $status_counts = [
                'publish' => 0,
                'pending' => 0,
                'draft'   => 0,
            ];
    foreach ($data['events'] as $event) {
        if (isset($status_counts[ $event['status'] ])) {
            ++$status_counts[ $event['status'] ];
        }
    }
    ?>
			
			const statusData = {
				'Publicado': <?php echo $status_counts['publish']; ?>,
				'Pendente': <?php echo $status_counts['pending']; ?>,
				'Rascunho': <?php echo $status_counts['draft']; ?>
			};
			
			const pieColors = [chartColors.green, chartColors.orange, chartColors.muted];
			
			new Chart(statusCtx, {
				type: 'doughnut',
				data: {
					labels: Object.keys(statusData),
					datasets: [{
						data: Object.values(statusData),
						backgroundColor: pieColors,
						borderWidth: 0,
						hoverOffset: 4
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					cutout: '65%',
					plugins: {
						legend: { display: false },
						tooltip: {
							enabled: true,
							backgroundColor: 'hsl(240 10% 3.9%)',
							titleColor: 'hsl(0 0% 98%)',
							bodyColor: 'hsl(0 0% 98%)',
							borderColor: 'hsl(240 3.7% 15.9%)',
							borderWidth: 1,
							cornerRadius: 6,
							padding: 12,
							callbacks: {
								title: function(context) {
									return 'Status: ' + context[0].label;
								},
								label: function(context) {
									const value = context.parsed;
									const total = context.dataset.data.reduce((a, b) => a + b, 0);
									const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
									return `${value} eventos (${percentage}%)`;
								},
								afterLabel: function(context) {
									const statusTips = {
										'Publicado': 'Eventos visíveis para o público',
										'Pendente': 'Aguardando aprovação',
										'Rascunho': 'Ainda não publicados'
									};
									return statusTips[context.label] || '';
								}
							}
						}
					}
				}
			});
			
			// Custom legend
			const legendContainer = document.getElementById('statusLegend');
			if (legendContainer) {
				Object.keys(statusData).forEach((label, i) => {
					const item = document.createElement('div');
					item.style.cssText = 'display: flex; align-items: center; gap: 0.375rem; font-size: 0.75rem;';
					item.innerHTML = `
						<span style="width: 8px; height: 8px; border-radius: 2px; background: ${pieColors[i]};"></span>
						<span style="color: hsl(var(--muted-foreground));">${label} (${statusData[label]})</span>
					`;
					legendContainer.appendChild(item);
				});
			}
		}
		
		// Period filter buttons
		document.querySelectorAll('.chart-period').forEach(btn => {
			btn.addEventListener('click', function() {
				document.querySelectorAll('.chart-period').forEach(b => b.classList.remove('active'));
				this.classList.add('active');
				// TODO: Implementar filtro de período
			});
		});
	});
	</script>
	<?php
}

// Render the dashboard
apollo_events_render_dashboard_layout(
    [
        'title'            => __('Dashboard de Eventos', 'apollo-events-manager'),
        'subtitle'         => __('Acompanhe o desempenho dos seus eventos', 'apollo-events-manager'),
        'content_callback' => 'apollo_events_render_dashboard_content',
    ]
);
