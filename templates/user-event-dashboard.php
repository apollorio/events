<?php
// phpcs:ignoreFile
/**
 * User Event Dashboard Template (ShadCN New York)
 *
 * Shows statistics for user's own events
 * Modern ShadCN design with tooltips
 *
 * @package Apollo_Events_Manager
 * @version 0.2.0
 */

defined('ABSPATH') || exit;

// Security check
if (! is_user_logged_in()) {
    echo '<p>' . esc_html__('Você precisa estar logado para ver esta página.', 'apollo-events-manager') . '</p>';

    return;
}

$current_user_id = get_current_user_id();

// Get user's events
$user_events = new WP_Query(
    [
        'post_type'      => 'event_listing',
        'author'         => $current_user_id,
        'posts_per_page' => -1,
        'post_status'    => [ 'publish', 'pending', 'draft' ],
    ]
);

$total_events = $user_events->found_posts;
$total_views  = 0;
$total_popup  = 0;
$total_page   = 0;
$events_data  = [];

if ($user_events->have_posts()) {
    while ($user_events->have_posts()) {
        $user_events->the_post();
        $event_id = get_the_ID();

        // Use CPT if available, fallback to meta
        if (class_exists('Apollo_Event_Stat_CPT')) {
            $stats = Apollo_Event_Stat_CPT::get_stats($event_id);
        } elseif (class_exists('Apollo_Event_Statistics')) {
            $stats = Apollo_Event_Statistics::get_event_stats($event_id);

            $total_views += $stats['total_views'];
            $total_popup += $stats['popup_count'];
            $total_page  += $stats['page_count'];

            $events_data[] = [
                'id'     => $event_id,
                'title'  => get_the_title(),
                'status' => get_post_status(),
                'stats'  => $stats,
                'date'   => get_the_date('Y-m-d H:i:s'),
            ];
        }
    }//end while
    wp_reset_postdata();
}//end if

// Calculate trends (placeholder - would need historical data)
$avg_views_per_event = $total_events > 0 ? round($total_views / $total_events) : 0;
$engagement_rate     = $total_views  > 0 ? round(($total_popup / $total_views) * 100) : 0;
?>

<style>
/* ShadCN New York Theme Variables */
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
	--chart-1: 220 70% 50%;
	--chart-2: 160 60% 45%;
	--chart-3: 30 80% 55%;
	--chart-4: 280 65% 60%;
	--chart-5: 340 75% 55%;
}

.apollo-user-dashboard {
	max-width: 1200px;
	margin: 0 auto;
	padding: 2rem;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.apollo-user-dashboard h1 {
	font-size: 1.875rem;
	font-weight: 600;
	color: hsl(var(--foreground));
	margin-bottom: 0.5rem;
}

.apollo-user-dashboard .subtitle {
	color: hsl(var(--muted-foreground));
	margin-bottom: 2rem;
}

/* Stats Grid */
.stats-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
	gap: 1.5rem;
	margin: 2rem 0;
}

.stat-card {
	background: hsl(var(--card));
	border: 1px solid hsl(var(--border));
	border-radius: var(--radius);
	padding: 1.5rem;
	transition: all 0.2s ease;
	position: relative;
}

.stat-card:hover {
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
	transform: translateY(-2px);
}

.stat-card-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 0.75rem;
}

.stat-card-title {
	font-size: 0.875rem;
	font-weight: 500;
	color: hsl(var(--muted-foreground));
}

.stat-card-icon {
	width: 40px;
	height: 40px;
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
}

.stat-card-description {
	font-size: 0.75rem;
	color: hsl(var(--muted-foreground));
	margin-top: 0.5rem;
}

.stat-card-trend {
	display: flex;
	align-items: center;
	gap: 0.25rem;
	font-size: 0.75rem;
	margin-top: 0.5rem;
}

.stat-card-trend.trend-up {
	color: hsl(160 60% 45%);
}

.stat-card-trend.trend-down {
	color: hsl(var(--destructive));
}

/* Cards */
.card {
	background: hsl(var(--card));
	border: 1px solid hsl(var(--border));
	border-radius: var(--radius);
	overflow: hidden;
}

.card-header {
	padding: 1.5rem 1.5rem 0;
}

.card-title {
	font-size: 1.125rem;
	font-weight: 600;
	color: hsl(var(--foreground));
	margin: 0 0 0.25rem 0;
}

.card-description {
	font-size: 0.875rem;
	color: hsl(var(--muted-foreground));
	margin: 0;
}

.card-content {
	padding: 1.5rem;
}

/* Table */
.data-table {
	width: 100%;
	border-collapse: collapse;
}

.data-table th,
.data-table td {
	padding: 0.75rem 1rem;
	text-align: left;
	border-bottom: 1px solid hsl(var(--border));
}

.data-table th {
	font-size: 0.75rem;
	font-weight: 500;
	color: hsl(var(--muted-foreground));
	text-transform: uppercase;
	letter-spacing: 0.05em;
	background: hsl(var(--muted) / 0.5);
}

.data-table tbody tr:hover {
	background: hsl(var(--muted) / 0.5);
}

.data-table td {
	font-size: 0.875rem;
	color: hsl(var(--foreground));
}

.data-table a {
	color: hsl(var(--primary));
	text-decoration: none;
	font-weight: 500;
}

.data-table a:hover {
	text-decoration: underline;
}

/* Badges */
.badge {
	display: inline-flex;
	align-items: center;
	padding: 0.25rem 0.625rem;
	font-size: 0.75rem;
	font-weight: 500;
	border-radius: 9999px;
}

.badge-published {
	background: hsl(160 60% 45% / 0.1);
	color: hsl(160 60% 35%);
}

.badge-pending {
	background: hsl(30 80% 55% / 0.1);
	color: hsl(30 80% 40%);
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
	border: 1px solid transparent;
	cursor: pointer;
	transition: all 0.2s;
	text-decoration: none;
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
	border-color: hsl(var(--border));
	color: hsl(var(--foreground));
}

.btn-outline:hover {
	background: hsl(var(--accent));
}

.btn-sm {
	padding: 0.25rem 0.75rem;
	font-size: 0.75rem;
}

/* Empty State */
.empty-state {
	text-align: center;
	padding: 4rem 2rem;
}

.empty-state-icon {
	width: 64px;
	height: 64px;
	margin: 0 auto 1.5rem;
	background: hsl(var(--muted));
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	color: hsl(var(--muted-foreground));
}

.empty-state-title {
	font-size: 1.125rem;
	font-weight: 600;
	color: hsl(var(--foreground));
	margin-bottom: 0.5rem;
}

.empty-state-description {
	color: hsl(var(--muted-foreground));
	margin-bottom: 1.5rem;
}

/* Tooltip */
[data-tooltip] {
	position: relative;
	cursor: help;
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
	white-space: nowrap;
	z-index: 50;
	box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
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
</style>

<div class="apollo-user-dashboard">
	<h1><?php echo esc_html__('Meus Eventos', 'apollo-events-manager'); ?></h1>
	<p class="subtitle"><?php echo esc_html__('Acompanhe o desempenho dos seus eventos', 'apollo-events-manager'); ?></p>
	
	<!-- Stats Grid -->
	<div class="stats-grid">
		<div class="stat-card" data-tooltip="<?php echo esc_attr__('Número total de eventos que você criou na plataforma Apollo Events', 'apollo-events-manager'); ?>">
			<div class="stat-card-header">
				<span class="stat-card-title"><?php echo esc_html__('Total de Eventos', 'apollo-events-manager'); ?></span>
				<div class="stat-card-icon" style="background: hsl(var(--chart-1) / 0.1); color: hsl(var(--chart-1));">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
						<line x1="16" y1="2" x2="16" y2="6"/>
						<line x1="8" y1="2" x2="8" y2="6"/>
						<line x1="3" y1="10" x2="21" y2="10"/>
					</svg>
				</div>
			</div>
			<div class="stat-card-value"><?php echo esc_html($total_events); ?></div>
			<p class="stat-card-description"><?php echo esc_html__('Eventos criados por você', 'apollo-events-manager'); ?></p>
		</div>
		
		<div class="stat-card" data-tooltip="<?php echo esc_attr__('Soma de todas as visualizações (modal + página) de seus eventos', 'apollo-events-manager'); ?>">
			<div class="stat-card-header">
				<span class="stat-card-title"><?php echo esc_html__('Visualizações Totais', 'apollo-events-manager'); ?></span>
				<div class="stat-card-icon" style="background: hsl(var(--chart-4) / 0.1); color: hsl(var(--chart-4));">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
						<circle cx="12" cy="12" r="3"/>
					</svg>
				</div>
			</div>
			<div class="stat-card-value"><?php echo number_format($total_views, 0, ',', '.'); ?></div>
			<p class="stat-card-description"><?php printf(esc_html__('Média de %s por evento', 'apollo-events-manager'), $avg_views_per_event); ?></p>
		</div>
		
		<div class="stat-card" data-tooltip="<?php echo esc_attr__('Visualizações rápidas via modal popup - usuários que viram o resumo do evento', 'apollo-events-manager'); ?>">
			<div class="stat-card-header">
				<span class="stat-card-title"><?php echo esc_html__('Visualizações Modal', 'apollo-events-manager'); ?></span>
				<div class="stat-card-icon" style="background: hsl(var(--chart-2) / 0.1); color: hsl(var(--chart-2));">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
						<line x1="8" y1="21" x2="16" y2="21"/>
						<line x1="12" y1="17" x2="12" y2="21"/>
					</svg>
				</div>
			</div>
			<div class="stat-card-value"><?php echo number_format($total_popup, 0, ',', '.'); ?></div>
			<p class="stat-card-description"><?php echo esc_html__('Aberturas de modal popup', 'apollo-events-manager'); ?></p>
		</div>
		
		<div class="stat-card" data-tooltip="<?php echo esc_attr__('Visualizações completas na página do evento - usuários que acessaram todos os detalhes', 'apollo-events-manager'); ?>">
			<div class="stat-card-header">
				<span class="stat-card-title"><?php echo esc_html__('Visualizações Página', 'apollo-events-manager'); ?></span>
				<div class="stat-card-icon" style="background: hsl(var(--chart-3) / 0.1); color: hsl(var(--chart-3));">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
						<polyline points="14 2 14 8 20 8"/>
						<line x1="16" y1="13" x2="8" y2="13"/>
						<line x1="16" y1="17" x2="8" y2="17"/>
					</svg>
				</div>
			</div>
			<div class="stat-card-value"><?php echo number_format($total_page, 0, ',', '.'); ?></div>
			<p class="stat-card-description"><?php printf(esc_html__('Taxa de engajamento: %s%%', 'apollo-events-manager'), $engagement_rate); ?></p>
		</div>
	</div>
	
	<!-- Events Table -->
	<?php if (! empty($events_data)) : ?>
	<div class="card">
		<div class="card-header">
			<h3 class="card-title"><?php echo esc_html__('Detalhes dos Eventos', 'apollo-events-manager'); ?></h3>
			<p class="card-description"><?php echo esc_html__('Estatísticas individuais de cada evento', 'apollo-events-manager'); ?></p>
		</div>
		<div class="card-content" style="padding: 0;">
			<table class="data-table">
				<thead>
					<tr>
						<th data-tooltip="<?php echo esc_attr__('Nome do evento', 'apollo-events-manager'); ?>"><?php echo esc_html__('Evento', 'apollo-events-manager'); ?></th>
						<th data-tooltip="<?php echo esc_attr__('Status atual de publicação', 'apollo-events-manager'); ?>"><?php echo esc_html__('Status', 'apollo-events-manager'); ?></th>
						<th data-tooltip="<?php echo esc_attr__('Total de visualizações (modal + página)', 'apollo-events-manager'); ?>"><?php echo esc_html__('Total', 'apollo-events-manager'); ?></th>
						<th data-tooltip="<?php echo esc_attr__('Visualizações via modal popup', 'apollo-events-manager'); ?>"><?php echo esc_html__('Modal', 'apollo-events-manager'); ?></th>
						<th data-tooltip="<?php echo esc_attr__('Visualizações na página do evento', 'apollo-events-manager'); ?>"><?php echo esc_html__('Página', 'apollo-events-manager'); ?></th>
						<th><?php echo esc_html__('Ações', 'apollo-events-manager'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($events_data as $event) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url(get_permalink($event['id'])); ?>" target="_blank">
								<?php echo esc_html($event['title']); ?>
							</a>
						</td>
						<td>
							<?php
                            $status_classes = [
                                'publish' => 'badge-published',
                                'pending' => 'badge-pending',
                                'draft'   => 'badge-draft',
                            ];
					    $status_labels = [
					        'publish' => __('Publicado', 'apollo-events-manager'),
					        'pending' => __('Pendente', 'apollo-events-manager'),
					        'draft'   => __('Rascunho', 'apollo-events-manager'),
					    ];
					    $class = $status_classes[ $event['status'] ] ?? 'badge-draft';
					    $label = $status_labels[ $event['status'] ]  ?? $event['status'];
					    ?>
							<span class="badge <?php echo esc_attr($class); ?>"><?php echo esc_html($label); ?></span>
						</td>
						<td><strong><?php echo number_format($event['stats']['total_views'], 0, ',', '.'); ?></strong></td>
						<td><?php echo number_format($event['stats']['popup_count'], 0, ',', '.'); ?></td>
						<td><?php echo number_format($event['stats']['page_count'], 0, ',', '.'); ?></td>
						<td>
							<a href="<?php echo esc_url(get_edit_post_link($event['id'])); ?>" class="btn btn-outline btn-sm">
								<?php echo esc_html__('Editar', 'apollo-events-manager'); ?>
							</a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php else : ?>
	<div class="card">
		<div class="card-content empty-state">
			<div class="empty-state-icon">
				<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
					<line x1="16" y1="2" x2="16" y2="6"/>
					<line x1="8" y1="2" x2="8" y2="6"/>
					<line x1="3" y1="10" x2="21" y2="10"/>
				</svg>
			</div>
			<h3 class="empty-state-title"><?php echo esc_html__('Nenhum evento encontrado', 'apollo-events-manager'); ?></h3>
			<p class="empty-state-description"><?php echo esc_html__('Você ainda não criou nenhum evento. Comece agora!', 'apollo-events-manager'); ?></p>
			<a href="<?php echo esc_url(admin_url('post-new.php?post_type=event_listing')); ?>" class="btn btn-primary">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<line x1="12" y1="5" x2="12" y2="19"/>
					<line x1="5" y1="12" x2="19" y2="12"/>
				</svg>
				<?php echo esc_html__('Criar Primeiro Evento', 'apollo-events-manager'); ?>
			</a>
		</div>
	</div>
	<?php endif; ?>
</div>
