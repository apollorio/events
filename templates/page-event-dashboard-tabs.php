<?php
// phpcs:ignoreFile
/**
 * Event Dashboard with ShadCN New York Tabs
 *
 * Public-facing event dashboard with smooth tab transitions and advanced statistics
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0 - ShadCN New York + Chart.js
 */

defined('ABSPATH') || exit;

if (! is_user_logged_in()) {
    echo '<p>' . esc_html__('Você precisa estar logado para ver esta página.', 'apollo-events-manager') . '</p>';

    return;
}

$current_user_id = get_current_user_id();

// Get user's event count
$user_events_count = count_user_posts($current_user_id, 'event_listing');

// Get favorites count
$user_favorites  = get_user_meta($current_user_id, 'apollo_favorites', true);
$favorites_count = is_array($user_favorites) ? count($user_favorites) : 0;

// Get total views for user's events
$user_events_query = new WP_Query(
    [
        'post_type'      => 'event_listing',
        'author'         => $current_user_id,
        'posts_per_page' => -1,
    ]
);

$total_user_views = 0;
$popup_views      = 0;
$page_views       = 0;
$events_data      = [];
$views_by_day     = array_fill(0, 7, 0);
$chart_labels     = [];

for ($i = 6; $i >= 0; $i--) {
    $chart_labels[] = date_i18n('D', strtotime("-{$i} days"));
}

if ($user_events_query->have_posts()) {
    while ($user_events_query->have_posts()) {
        $user_events_query->the_post();
        $event_id = get_the_ID();

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

        $total_user_views += $stats['total_views'] ?? 0;
        $popup_views      += $stats['popup_count'] ?? 0;
        $page_views       += $stats['page_count']  ?? 0;

        $events_data[] = [
            'id'        => $event_id,
            'title'     => get_the_title(),
            'status'    => get_post_status(),
            'views'     => $stats['total_views'] ?? 0,
            'popup'     => $stats['popup_count'] ?? 0,
            'page'      => $stats['page_count']  ?? 0,
            'date'      => get_the_date('d/m/Y'),
            'permalink' => get_permalink($event_id),
            'edit_link' => get_edit_post_link($event_id),
            'thumbnail' => get_the_post_thumbnail_url($event_id, 'thumbnail'),
        ];

        // Simulate views by day (in production, this would come from real data)
        $rand_day = rand(0, 6);
        $views_by_day[ $rand_day ] += ($stats['total_views'] ?? 0);
    }//end while
    wp_reset_postdata();
}//end if

// Sort by views for top events
$top_events = $events_data;
usort(
    $top_events,
    function ($a, $b) {
        return $b['views'] - $a['views'];
    }
);
$top_events = array_slice($top_events, 0, 5);

// Calculate engagement rate
$engagement_rate = $total_user_views > 0 ? round(($popup_views / $total_user_views) * 100, 1) : 0;
?>

<!-- ShadCN CSS Variables -->
<style>
	:root {
		--background: 0 0% 100%;
		--foreground: 240 10% 3.9%;
		--card: 0 0% 100%;
		--card-foreground: 240 10% 3.9%;
		--popover: 0 0% 100%;
		--popover-foreground: 240 10% 3.9%;
		--primary: 240 5.9% 10%;
		--primary-foreground: 0 0% 98%;
		--secondary: 240 4.8% 95.9%;
		--secondary-foreground: 240 5.9% 10%;
		--muted: 240 4.8% 95.9%;
		--muted-foreground: 240 3.8% 46.1%;
		--accent: 240 4.8% 95.9%;
		--accent-foreground: 240 5.9% 10%;
		--destructive: 0 84.2% 60.2%;
		--destructive-foreground: 0 0% 98%;
		--border: 240 5.9% 90%;
		--input: 240 5.9% 90%;
		--ring: 240 5.9% 10%;
		--radius: 0.5rem;
		--status-published: 142 76% 36%;
		--status-pending: 48 96% 53%;
		--status-draft: 240 3.8% 46.1%;
	}
	
	.apollo-dashboard-tabs {
		font-family: system-ui, -apple-system, sans-serif;
		max-width: 1400px;
		margin: 0 auto;
		padding: 1.5rem;
	}
	
	.dashboard-header {
		margin-bottom: 2rem;
	}
	
	.dashboard-title {
		font-size: 1.875rem;
		font-weight: 700;
		color: hsl(var(--foreground));
		margin: 0 0 0.25rem;
	}
	
	.dashboard-subtitle {
		font-size: 0.875rem;
		color: hsl(var(--muted-foreground));
		margin: 0;
	}
	
	/* Tabs */
	.tabs-container {
		margin-bottom: 1.5rem;
	}
	
	.tabs-list {
		display: inline-flex;
		background: hsl(var(--muted));
		padding: 0.25rem;
		border-radius: var(--radius);
		gap: 0.25rem;
	}
	
	.tab-trigger {
		padding: 0.5rem 1rem;
		font-size: 0.875rem;
		font-weight: 500;
		border: none;
		background: transparent;
		color: hsl(var(--muted-foreground));
		border-radius: calc(var(--radius) - 2px);
		cursor: pointer;
		transition: all 0.15s ease;
	}
	
	.tab-trigger:hover {
		color: hsl(var(--foreground));
	}
	
	.tab-trigger.active {
		background: hsl(var(--background));
		color: hsl(var(--foreground));
		box-shadow: 0 1px 3px rgba(0,0,0,0.1);
	}
	
	.tab-panel {
		display: none;
		animation: fadeInTab 0.3s ease-out;
	}
	
	.tab-panel.active {
		display: block;
	}
	
	@keyframes fadeInTab {
		from { opacity: 0; transform: translateY(8px); }
		to { opacity: 1; transform: translateY(0); }
	}
	
	/* Stats Grid */
	.stats-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		gap: 1rem;
		margin-bottom: 1.5rem;
	}
	
	.stat-card {
		background: hsl(var(--card));
		border: 1px solid hsl(var(--border));
		border-radius: var(--radius);
		padding: 1.5rem;
		position: relative;
		cursor: help;
		transition: all 0.2s ease;
	}
	
	.stat-card:hover {
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
		transform: translateY(-2px);
	}
	
	.stat-card-header {
		display: flex;
		align-items: flex-start;
		justify-content: space-between;
	}
	
	.stat-card-title {
		font-size: 0.875rem;
		font-weight: 500;
		color: hsl(var(--muted-foreground));
	}
	
	.stat-card-icon {
		width: 2.5rem;
		height: 2.5rem;
		border-radius: var(--radius);
		display: flex;
		align-items: center;
		justify-content: center;
	}
	
	.stat-card-value {
		font-size: 2rem;
		font-weight: 700;
		color: hsl(var(--foreground));
		line-height: 1.2;
		margin-top: 0.25rem;
	}
	
	.stat-card-description {
		font-size: 0.75rem;
		color: hsl(var(--muted-foreground));
		margin: 0.25rem 0 0;
	}
	
	.stat-card-trend {
		display: flex;
		align-items: center;
		gap: 0.25rem;
		margin-top: 0.5rem;
		font-size: 0.75rem;
	}
	
	.trend-up { color: hsl(var(--status-published)); }
	.trend-down { color: hsl(var(--destructive)); }
	
	/* Tooltip styles */
	[data-tooltip] {
		position: relative;
	}
	
	[data-tooltip]:hover::after {
		content: attr(data-tooltip);
		position: absolute;
		bottom: calc(100% + 8px);
		left: 50%;
		transform: translateX(-50%);
		background: hsl(var(--popover));
		color: hsl(var(--popover-foreground));
		padding: 0.5rem 0.75rem;
		border-radius: var(--radius);
		font-size: 0.75rem;
		white-space: normal;
		max-width: 280px;
		text-align: center;
		line-height: 1.4;
		z-index: 50;
		box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
		border: 1px solid hsl(var(--border));
		pointer-events: none;
	}
	
	[data-tooltip]:hover::before {
		content: '';
		position: absolute;
		bottom: calc(100% + 4px);
		left: 50%;
		transform: translateX(-50%);
		border: 4px solid transparent;
		border-top-color: hsl(var(--border));
		z-index: 51;
	}
	
	/* Cards */
	.card {
		background: hsl(var(--card));
		border: 1px solid hsl(var(--border));
		border-radius: var(--radius);
	}
	
	.card-header {
		padding: 1.5rem;
		border-bottom: 1px solid hsl(var(--border));
	}
	
	.card-title {
		font-size: 1rem;
		font-weight: 600;
		color: hsl(var(--foreground));
		margin: 0;
	}
	
	.card-description {
		font-size: 0.875rem;
		color: hsl(var(--muted-foreground));
		margin: 0.25rem 0 0;
	}
	
	.card-content {
		padding: 1.5rem;
	}
	
	/* Grid layouts */
	.charts-grid {
		display: grid;
		grid-template-columns: 2fr 1fr;
		gap: 1rem;
		margin-bottom: 1.5rem;
	}
	
	.tables-grid {
		display: grid;
		grid-template-columns: 2fr 1fr;
		gap: 1rem;
	}
	
	@media (max-width: 1024px) {
		.charts-grid, .tables-grid {
			grid-template-columns: 1fr;
		}
	}
	
	/* Data Table */
	.data-table {
		width: 100%;
		border-collapse: collapse;
	}
	
	.data-table th {
		padding: 0.75rem 1rem;
		text-align: left;
		font-size: 0.75rem;
		font-weight: 500;
		color: hsl(var(--muted-foreground));
		text-transform: uppercase;
		letter-spacing: 0.05em;
		border-bottom: 1px solid hsl(var(--border));
	}
	
	.data-table td {
		padding: 1rem;
		border-bottom: 1px solid hsl(var(--border));
	}
	
	.data-table tr:last-child td {
		border-bottom: none;
	}
	
	.data-table tr:hover {
		background: hsl(var(--muted) / 0.5);
	}
	
	/* Badges */
	.badge {
		display: inline-flex;
		padding: 0.25rem 0.625rem;
		font-size: 0.75rem;
		font-weight: 500;
		border-radius: 9999px;
	}
	
	.badge-published {
		background: hsl(var(--status-published) / 0.1);
		color: hsl(var(--status-published));
	}
	
	.badge-pending {
		background: hsl(var(--status-pending) / 0.1);
		color: hsl(var(--status-pending));
	}
	
	.badge-draft {
		background: hsl(var(--muted));
		color: hsl(var(--muted-foreground));
	}
	
	/* Buttons */
	.btn {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 0.5rem;
		padding: 0.5rem 1rem;
		font-size: 0.875rem;
		font-weight: 500;
		border-radius: var(--radius);
		border: none;
		cursor: pointer;
		text-decoration: none;
		transition: all 0.15s ease;
	}
	
	.btn-primary {
		background: hsl(var(--primary));
		color: hsl(var(--primary-foreground));
	}
	
	.btn-primary:hover {
		background: hsl(var(--primary) / 0.9);
	}
	
	.btn-outline {
		background: transparent;
		border: 1px solid hsl(var(--border));
		color: hsl(var(--foreground));
	}
	
	.btn-outline:hover {
		background: hsl(var(--accent));
	}
	
	.btn-ghost {
		background: transparent;
		color: hsl(var(--foreground));
		padding: 0.375rem;
	}
	
	.btn-ghost:hover {
		background: hsl(var(--accent));
	}
	
	.btn-sm {
		padding: 0.375rem 0.75rem;
		font-size: 0.75rem;
	}
	
	/* Chart Container */
	.chart-container {
		position: relative;
		height: 280px;
	}
	
	/* Top Events List */
	.top-events-list {
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
	}
	
	.top-event-item {
		display: flex;
		align-items: center;
		gap: 0.75rem;
	}
	
	.top-event-rank {
		width: 1.5rem;
		height: 1.5rem;
		border-radius: var(--radius);
		background: hsl(var(--muted));
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 0.75rem;
		font-weight: 600;
		color: hsl(var(--muted-foreground));
	}
	
	.top-event-title {
		flex: 1;
		font-size: 0.875rem;
		font-weight: 500;
		color: hsl(var(--foreground));
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}
	
	.top-event-views {
		font-size: 0.875rem;
		font-weight: 600;
		color: hsl(var(--foreground));
	}
	
	/* Progress Bars */
	.progress-bar {
		height: 0.5rem;
		background: hsl(var(--muted));
		border-radius: 9999px;
		overflow: hidden;
	}
	
	.progress-bar-fill {
		height: 100%;
		border-radius: 9999px;
		transition: width 0.3s ease;
	}
</style>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="apollo-dashboard-tabs">
	<!-- Header -->
	<div class="dashboard-header">
		<h1 class="dashboard-title"><?php echo esc_html__('Dashboard de Eventos', 'apollo-events-manager'); ?></h1>
		<p class="dashboard-subtitle"><?php echo esc_html__('Acompanhe o desempenho dos seus eventos', 'apollo-events-manager'); ?></p>
	</div>
	
	<!-- Tabs Navigation -->
	<div class="tabs-container">
		<div class="tabs-list">
			<button class="tab-trigger active" data-tab="overview"><?php echo esc_html__('Visão Geral', 'apollo-events-manager'); ?></button>
			<button class="tab-trigger" data-tab="events"><?php echo esc_html__('Meus Eventos', 'apollo-events-manager'); ?></button>
			<button class="tab-trigger" data-tab="favorites"><?php echo esc_html__('Favoritos', 'apollo-events-manager'); ?></button>
			<button class="tab-trigger" data-tab="statistics"><?php echo esc_html__('Estatísticas', 'apollo-events-manager'); ?></button>
		</div>
	</div>
	
	<!-- Overview Tab -->
	<div class="tab-panel active" data-panel="overview">
		<!-- Stats Cards -->
		<div class="stats-grid">
			<div class="stat-card" data-tooltip="<?php echo esc_attr__('Total de eventos que você criou como organizador/produtor na plataforma Apollo Events', 'apollo-events-manager'); ?>">
				<div class="stat-card-header">
					<span class="stat-card-title"><?php echo esc_html__('Meus Eventos', 'apollo-events-manager'); ?></span>
					<div class="stat-card-icon" style="background: hsl(var(--primary) / 0.1); color: hsl(var(--primary));">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
						</svg>
					</div>
				</div>
				<div class="stat-card-value"><?php echo esc_html($user_events_count); ?></div>
				<p class="stat-card-description"><?php echo esc_html__('Eventos criados por você', 'apollo-events-manager'); ?></p>
				<div class="stat-card-trend trend-up">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17L17 7M17 7H7M17 7V17"/></svg>
					<span>+12%</span>
					<span style="color: hsl(var(--muted-foreground));">vs mês anterior</span>
				</div>
			</div>
			
			<div class="stat-card" data-tooltip="<?php echo esc_attr__('Soma de todas as visualizações (modal + página) de seus eventos', 'apollo-events-manager'); ?>">
				<div class="stat-card-header">
					<span class="stat-card-title"><?php echo esc_html__('Visualizações', 'apollo-events-manager'); ?></span>
					<div class="stat-card-icon" style="background: hsl(262 83% 58% / 0.1); color: hsl(262 83% 58%);">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
						</svg>
					</div>
				</div>
				<div class="stat-card-value"><?php echo number_format($total_user_views, 0, ',', '.'); ?></div>
				<p class="stat-card-description"><?php echo esc_html__('Modal + Página combinados', 'apollo-events-manager'); ?></p>
				<div class="stat-card-trend trend-up">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17L17 7M17 7H7M17 7V17"/></svg>
					<span>+8.5%</span>
					<span style="color: hsl(var(--muted-foreground));">vs mês anterior</span>
				</div>
			</div>
			
			<div class="stat-card" data-tooltip="<?php echo esc_attr__('Eventos salvos na lista de favoritos, marcados como Ir ou Talvez', 'apollo-events-manager'); ?>">
				<div class="stat-card-header">
					<span class="stat-card-title"><?php echo esc_html__('Favoritos', 'apollo-events-manager'); ?></span>
					<div class="stat-card-icon" style="background: hsl(0 84% 60% / 0.1); color: hsl(0 84% 60%);">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
						</svg>
					</div>
				</div>
				<div class="stat-card-value"><?php echo esc_html($favorites_count); ?></div>
				<p class="stat-card-description"><?php echo esc_html__('Eventos na sua lista', 'apollo-events-manager'); ?></p>
				<div class="stat-card-trend trend-up">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17L17 7M17 7H7M17 7V17"/></svg>
					<span>+5</span>
					<span style="color: hsl(var(--muted-foreground));">esta semana</span>
				</div>
			</div>
			
			<div class="stat-card" data-tooltip="<?php echo esc_attr__('Proporção de usuários que interagiram com seus eventos (modal views / total views)', 'apollo-events-manager'); ?>">
				<div class="stat-card-header">
					<span class="stat-card-title"><?php echo esc_html__('Engajamento', 'apollo-events-manager'); ?></span>
					<div class="stat-card-icon" style="background: hsl(var(--status-published) / 0.1); color: hsl(var(--status-published));">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
						</svg>
					</div>
				</div>
				<div class="stat-card-value"><?php echo $engagement_rate; ?>%</div>
				<p class="stat-card-description"><?php echo esc_html__('Taxa de interação', 'apollo-events-manager'); ?></p>
				<div class="stat-card-trend trend-up">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17L17 7M17 7H7M17 7V17"/></svg>
					<span>+2.1%</span>
					<span style="color: hsl(var(--muted-foreground));">vs mês anterior</span>
				</div>
			</div>
		</div>
		
		<!-- Charts Row -->
		<div class="charts-grid">
			<!-- Views Chart -->
			<div class="card">
				<div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
					<div>
						<h3 class="card-title"><?php echo esc_html__('Visualizações', 'apollo-events-manager'); ?></h3>
						<p class="card-description"><?php echo esc_html__('Últimos 7 dias', 'apollo-events-manager'); ?></p>
					</div>
					<div style="display: flex; gap: 0.5rem;">
						<button class="btn btn-sm btn-outline period-btn active" data-period="7">7D</button>
						<button class="btn btn-sm btn-outline period-btn" data-period="30">30D</button>
						<button class="btn btn-sm btn-outline period-btn" data-period="90">90D</button>
					</div>
				</div>
				<div class="card-content">
					<div class="chart-container">
						<canvas id="viewsChart"></canvas>
					</div>
				</div>
			</div>
			
			<!-- Top Events -->
			<div class="card">
				<div class="card-header">
					<h3 class="card-title"><?php echo esc_html__('Top Eventos', 'apollo-events-manager'); ?></h3>
					<p class="card-description"><?php echo esc_html__('Por visualizações', 'apollo-events-manager'); ?></p>
				</div>
				<div class="card-content">
					<?php if (empty($top_events)) : ?>
					<p style="text-align: center; color: hsl(var(--muted-foreground)); padding: 2rem 0;">
						<?php echo esc_html__('Nenhum evento ainda', 'apollo-events-manager'); ?>
					</p>
					<?php else : ?>
					<div class="top-events-list">
						<?php foreach ($top_events as $index => $event) : ?>
						<div class="top-event-item">
							<span class="top-event-rank"><?php echo $index + 1; ?></span>
							<span class="top-event-title"><?php echo esc_html($event['title']); ?></span>
							<span class="top-event-views"><?php echo number_format($event['views'], 0, ',', '.'); ?></span>
						</div>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		
		<!-- Recent Events Table -->
		<div class="card">
			<div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
				<div>
					<h3 class="card-title"><?php echo esc_html__('Eventos Recentes', 'apollo-events-manager'); ?></h3>
					<p class="card-description"><?php echo esc_html__('Seus últimos eventos criados', 'apollo-events-manager'); ?></p>
				</div>
				<a href="<?php echo esc_url(admin_url('post-new.php?post_type=event_listing')); ?>" class="btn btn-primary btn-sm">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
					</svg>
					<?php echo esc_html__('Novo Evento', 'apollo-events-manager'); ?>
				</a>
			</div>
			<?php if (empty($events_data)) : ?>
			<div class="card-content" style="text-align: center; padding: 3rem;">
				<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--muted-foreground))" stroke-width="1.5" style="margin: 0 auto 1rem;">
					<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
				</svg>
				<p style="color: hsl(var(--muted-foreground)); margin-bottom: 1rem;"><?php echo esc_html__('Nenhum evento criado ainda', 'apollo-events-manager'); ?></p>
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
							<th><?php echo esc_html__('Views', 'apollo-events-manager'); ?></th>
							<th><?php echo esc_html__('Modal', 'apollo-events-manager'); ?></th>
							<th style="text-align: right;"><?php echo esc_html__('Ações', 'apollo-events-manager'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
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
                        ];

			    foreach (array_slice($events_data, 0, 8) as $event) :
			        $status = $status_labels[ $event['status'] ] ?? $status_labels['draft'];
			        ?>
						<tr>
							<td>
								<div style="display: flex; align-items: center; gap: 0.75rem;">
									<?php if (! empty($event['thumbnail'])) : ?>
									<div style="width: 2.5rem; height: 2.5rem; border-radius: var(--radius); overflow: hidden;">
										<img src="<?php echo esc_url($event['thumbnail']); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
									</div>
									<?php else : ?>
									<div style="width: 2.5rem; height: 2.5rem; border-radius: var(--radius); background: hsl(var(--muted)); display: flex; align-items: center; justify-content: center;">
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--muted-foreground))" stroke-width="2">
											<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
										</svg>
									</div>
									<?php endif; ?>
									<span style="font-weight: 500; color: hsl(var(--foreground));"><?php echo esc_html($event['title']); ?></span>
								</div>
							</td>
							<td style="color: hsl(var(--muted-foreground));"><?php echo esc_html($event['date']); ?></td>
							<td><span class="badge <?php echo esc_attr($status['class']); ?>"><?php echo esc_html($status['label']); ?></span></td>
							<td style="font-weight: 500;"><?php echo number_format($event['views'], 0, ',', '.'); ?></td>
							<td><?php echo number_format($event['popup'], 0, ',', '.'); ?></td>
							<td>
								<div style="display: flex; justify-content: flex-end; gap: 0.375rem;">
									<a href="<?php echo esc_url($event['permalink']); ?>" class="btn btn-ghost" title="Ver">
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
											<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
										</svg>
									</a>
									<a href="<?php echo esc_url($event['edit_link']); ?>" class="btn btn-ghost" title="Editar">
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
	</div>
	
	<!-- Events Tab -->
	<div class="tab-panel" data-panel="events">
		<?php
        $template_path = APOLLO_APRIO_PATH . 'templates/user-event-dashboard.php';
if (file_exists($template_path)) {
    include $template_path;
}
?>
	</div>
	
	<!-- Favorites Tab -->
	<div class="tab-panel" data-panel="favorites">
		<div class="card">
			<div class="card-header">
				<h3 class="card-title"><?php echo esc_html__('Meus Favoritos', 'apollo-events-manager'); ?></h3>
				<p class="card-description"><?php echo esc_html__('Eventos que você salvou', 'apollo-events-manager'); ?></p>
			</div>
			<div class="card-content">
				<?php if (empty($user_favorites) || ! is_array($user_favorites)) : ?>
				<div style="text-align: center; padding: 3rem;">
					<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--muted-foreground))" stroke-width="1.5" style="margin: 0 auto 1rem;">
						<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
					</svg>
					<p style="color: hsl(var(--muted-foreground));"><?php echo esc_html__('Você ainda não favoritou nenhum evento', 'apollo-events-manager'); ?></p>
					<a href="<?php echo esc_url(home_url('/eventos')); ?>" class="btn btn-primary" style="margin-top: 1rem;">
						<?php echo esc_html__('Explorar Eventos', 'apollo-events-manager'); ?>
					</a>
				</div>
				<?php else : ?>
				<p style="color: hsl(var(--muted-foreground));"><?php echo esc_html__('Você tem', 'apollo-events-manager'); ?> <?php echo count($user_favorites); ?> <?php echo esc_html__('eventos favoritados', 'apollo-events-manager'); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
	
	<!-- Statistics Tab -->
	<div class="tab-panel" data-panel="statistics">
		<div class="stats-grid">
			<div class="stat-card">
				<div class="stat-card-title"><?php echo esc_html__('Total Views', 'apollo-events-manager'); ?></div>
				<div class="stat-card-value"><?php echo number_format($total_user_views, 0, ',', '.'); ?></div>
			</div>
			<div class="stat-card">
				<div class="stat-card-title"><?php echo esc_html__('Views em Modal', 'apollo-events-manager'); ?></div>
				<div class="stat-card-value"><?php echo number_format($popup_views, 0, ',', '.'); ?></div>
			</div>
			<div class="stat-card">
				<div class="stat-card-title"><?php echo esc_html__('Views em Página', 'apollo-events-manager'); ?></div>
				<div class="stat-card-value"><?php echo number_format($page_views, 0, ',', '.'); ?></div>
			</div>
			<div class="stat-card">
				<div class="stat-card-title"><?php echo esc_html__('Taxa de Engajamento', 'apollo-events-manager'); ?></div>
				<div class="stat-card-value"><?php echo $engagement_rate; ?>%</div>
			</div>
		</div>
		
		<div class="charts-grid" style="margin-top: 1.5rem;">
			<div class="card">
				<div class="card-header">
					<h3 class="card-title"><?php echo esc_html__('Distribuição de Views', 'apollo-events-manager'); ?></h3>
				</div>
				<div class="card-content">
					<div class="chart-container">
						<canvas id="statsBarChart"></canvas>
					</div>
				</div>
			</div>
			
			<div class="card">
				<div class="card-header">
					<h3 class="card-title"><?php echo esc_html__('Tipo de Visualização', 'apollo-events-manager'); ?></h3>
				</div>
				<div class="card-content" style="display: flex; flex-direction: column; align-items: center;">
					<div style="height: 200px; width: 200px;">
						<canvas id="statsPieChart"></canvas>
					</div>
					<div id="statsPieLegend" style="display: flex; gap: 1rem; margin-top: 1rem;"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Tab functionality
	const tabTriggers = document.querySelectorAll('.tab-trigger');
	const tabPanels = document.querySelectorAll('.tab-panel');
	
	tabTriggers.forEach(trigger => {
		trigger.addEventListener('click', function() {
			const tabId = this.dataset.tab;
			
			tabTriggers.forEach(t => t.classList.remove('active'));
			tabPanels.forEach(p => p.classList.remove('active'));
			
			this.classList.add('active');
			document.querySelector(`[data-panel="${tabId}"]`).classList.add('active');
		});
	});
	
	// Chart.js
	Chart.defaults.font.family = 'system-ui, -apple-system, sans-serif';
	Chart.defaults.color = 'hsl(240 3.8% 46.1%)';
	
	const chartColors = {
		primary: 'hsl(240 5.9% 10%)',
		purple: 'hsl(262 83% 58%)',
		green: 'hsl(142 76% 36%)',
		orange: 'hsl(24 95% 53%)'
	};
	
	// Views Chart
	const viewsCtx = document.getElementById('viewsChart');
	if (viewsCtx) {
		new Chart(viewsCtx, {
			type: 'line',
			data: {
				labels: <?php echo json_encode($chart_labels); ?>,
				datasets: [{
					label: 'Visualizações',
					data: <?php echo json_encode($views_by_day); ?>,
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
					x: { grid: { display: false }, border: { display: false } },
					y: { beginAtZero: true, grid: { color: 'hsl(240 5.9% 90%)' }, border: { display: false } }
				}
			}
		});
	}
	
	// Stats Bar Chart
	const statsBarCtx = document.getElementById('statsBarChart');
	if (statsBarCtx) {
		new Chart(statsBarCtx, {
			type: 'bar',
			data: {
				labels: <?php echo json_encode($chart_labels); ?>,
				datasets: [{
					label: 'Views',
					data: <?php echo json_encode($views_by_day); ?>,
					backgroundColor: chartColors.primary,
					borderRadius: 4,
					barThickness: 24
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
								return context[0].label;
							},
							label: function(context) {
								return `${context.parsed.y.toLocaleString('pt-BR')} visualizações`;
							},
							afterLabel: function(context) {
								const total = context.dataset.data.reduce((a, b) => a + b, 0);
								const percentage = total > 0 ? ((context.parsed.y / total) * 100).toFixed(1) : 0;
								return `${percentage}% do total semanal`;
							}
						}
					}
				},
				scales: {
					x: { grid: { display: false }, border: { display: false } },
					y: { beginAtZero: true, grid: { color: 'hsl(240 5.9% 90%)' }, border: { display: false } }
				}
			}
		});
	}
	
	// Stats Pie Chart
	const statsPieCtx = document.getElementById('statsPieChart');
	if (statsPieCtx) {
		const pieData = {
			'Modal': <?php echo $popup_views; ?>,
			'Página': <?php echo $page_views; ?>
		};
		const pieColors = [chartColors.purple, chartColors.green];
		
		new Chart(statsPieCtx, {
			type: 'doughnut',
			data: {
				labels: Object.keys(pieData),
				datasets: [{
					data: Object.values(pieData),
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
								return 'Tipo: ' + context[0].label;
							},
							label: function(context) {
								const value = context.parsed;
								const total = context.dataset.data.reduce((a, b) => a + b, 0);
								const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
								return `${value.toLocaleString('pt-BR')} visualizações (${percentage}%)`;
							},
							afterLabel: function(context) {
								const tips = {
									'Modal': 'Visualizações via popup/modal',
									'Página': 'Visualizações na página do evento'
								};
								return tips[context.label] || '';
							}
						}
					}
				}
			}
		});
		
		// Custom legend
		const legendContainer = document.getElementById('statsPieLegend');
		if (legendContainer) {
			Object.keys(pieData).forEach((label, i) => {
				const item = document.createElement('div');
				item.style.cssText = 'display: flex; align-items: center; gap: 0.375rem; font-size: 0.75rem;';
				item.innerHTML = `<span style="width: 8px; height: 8px; border-radius: 2px; background: ${pieColors[i]};"></span><span style="color: hsl(var(--muted-foreground));">${label}</span>`;
				legendContainer.appendChild(item);
			});
		}
	}
	
	// Period filter
	document.querySelectorAll('.period-btn').forEach(btn => {
		btn.addEventListener('click', function() {
			document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
			this.classList.add('active');
		});
	});
});
</script>

