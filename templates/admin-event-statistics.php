<?php
// phpcs:ignoreFile
/**
 * Admin Event Statistics Dashboard
 *
 * Displays event statistics with Motion.dev animations
 *
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

// Security check
if (! current_user_can('manage_options')) {
    wp_die(__('Você não tem permissão para acessar esta página.', 'apollo-events-manager'));
}

// Get all events with statistics
$events_query = new WP_Query(
    [
        'post_type'      => 'event_listing',
        'posts_per_page' => 50,
        'post_status'    => 'publish',
        'orderby'        => 'meta_value_num',
        'meta_key'       => '_apollo_event_stats',
        'order'          => 'DESC',
    ]
);

$total_views = 0;
$total_popup = 0;
$total_page  = 0;
$events_data = [];

if ($events_query->have_posts()) {
    while ($events_query->have_posts()) {
        $events_query->the_post();
        $event_id = get_the_ID();
        // Use CPT if available
        if (class_exists('Apollo_Event_Stat_CPT')) {
            $stats = Apollo_Event_Stat_CPT::get_stats($event_id);
        } else {
            $stats = Apollo_Event_Statistics::get_event_stats($event_id);
        }

        $total_views += $stats['total_views'];
        $total_popup += $stats['popup_count'];
        $total_page  += $stats['page_count'];

        if ($stats['total_views'] > 0) {
            $events_data[] = [
                'id'    => $event_id,
                'title' => get_the_title(),
                'stats' => $stats,
            ];
        }
    }//end while
    wp_reset_postdata();
}//end if
?>

<div class="wrap apollo-statistics-dashboard">
	<h1><?php echo esc_html__('Estatísticas de Eventos', 'apollo-events-manager'); ?></h1>
	
	<!-- Summary Cards with Motion.dev animations -->
	<div class="apollo-stats-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
		
		<!-- Total Views Card -->
		<div class="apollo-stat-card" data-motion-counter="true" style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
			<div style="font-size: 0.875rem; color: #666; margin-bottom: 0.5rem;">
				<?php echo esc_html__('Total de Visualizações', 'apollo-events-manager'); ?>
			</div>
			<div class="stat-number" data-value="<?php echo esc_attr($total_views); ?>" style="font-size: 2.5rem; font-weight: bold; color: #007cba;">
				<?php echo number_format($total_views, 0, ',', '.'); ?>
			</div>
		</div>
		
		<!-- Popup Views Card -->
		<div class="apollo-stat-card" data-motion-counter="true" style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
			<div style="font-size: 0.875rem; color: #666; margin-bottom: 0.5rem;">
				<?php echo esc_html__('Visualizações em Modal', 'apollo-events-manager'); ?>
			</div>
			<div class="stat-number" data-value="<?php echo esc_attr($total_popup); ?>" style="font-size: 2.5rem; font-weight: bold; color: #9b51e0;">
				<?php echo number_format($total_popup, 0, ',', '.'); ?>
			</div>
		</div>
		
		<!-- Page Views Card -->
		<div class="apollo-stat-card" data-motion-counter="true" style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
			<div style="font-size: 0.875rem; color: #666; margin-bottom: 0.5rem;">
				<?php echo esc_html__('Visualizações em Página', 'apollo-events-manager'); ?>
			</div>
			<div class="stat-number" data-value="<?php echo esc_attr($total_page); ?>" style="font-size: 2.5rem; font-weight: bold; color: #00a32a;">
				<?php echo number_format($total_page, 0, ',', '.'); ?>
			</div>
		</div>
		
		<!-- Events Count Card -->
		<div class="apollo-stat-card" data-motion-counter="true" style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
			<div style="font-size: 0.875rem; color: #666; margin-bottom: 0.5rem;">
				<?php echo esc_html__('Total de Eventos', 'apollo-events-manager'); ?>
			</div>
			<div class="stat-number" data-value="<?php echo esc_attr(count($events_data)); ?>" style="font-size: 2.5rem; font-weight: bold; color: #f15a24;">
				<?php echo number_format(count($events_data), 0, ',', '.'); ?>
			</div>
		</div>
	</div>
	
	<!-- Line Graph: Views Over Time (TODO 98) -->
	<?php
    // Get aggregate daily data from all events with CPT
    $aggregate_daily = [];
if (class_exists('Apollo_Event_Stat_CPT')) {
    $all_stats = Apollo_Event_Stat_CPT::get_all_stats();
    foreach ($all_stats as $event_id => $event_stats) {
        if (! empty($event_stats['daily_views'])) {
            foreach ($event_stats['daily_views'] as $date => $counts) {
                if (! isset($aggregate_daily[ $date ])) {
                    $aggregate_daily[ $date ] = [
                        'page'  => 0,
                        'popup' => 0,
                        'total' => 0,
                    ];
                }
                $aggregate_daily[ $date ]['page']  += $counts['page'];
                $aggregate_daily[ $date ]['popup'] += $counts['popup'];
                $aggregate_daily[ $date ]['total'] += $counts['total'];
            }
        }
    }
}
?>
	
	<?php if (! empty($aggregate_daily)) : ?>
	<div class="apollo-graph-container" style="background: #fff; border-radius: 12px; padding: 2rem; margin: 2rem 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
		<h2 style="margin: 0 0 1.5rem; font-size: 1.3rem; color: #333;">
			<i class="ri-line-chart-line"></i>
			<?php echo esc_html__('Visualizações ao Longo do Tempo (Últimos 90 Dias)', 'apollo-events-manager'); ?>
		</h2>
		<div id="apollo-views-graph" style="min-height: 300px;"></div>
		
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			if (typeof apolloLineGraph === 'function' && typeof apolloFormatGraphData === 'function') {
				const dailyData = <?php echo json_encode($aggregate_daily); ?>;
				const graphData = apolloFormatGraphData(dailyData, 'total');
				
				if (graphData && graphData.length > 0) {
					apolloLineGraph('apollo-views-graph', graphData, {
						height: 300,
						strokeColor: '#007cba',
						fillColor: 'rgba(0, 124, 186, 0.1)',
						strokeWidth: 3,
						animate: true,
						showDots: true,
						showGrid: true
					});
				} else {
					document.getElementById('apollo-views-graph').innerHTML = '<p style="text-align:center;padding:2rem;color:#666;">Aguardando dados de visualizações...</p>';
				}
			} else {
				document.getElementById('apollo-views-graph').innerHTML = '<p style="text-align:center;padding:2rem;color:#666;">Carregando gráfico...</p>';
			}
		});
		</script>
	</div>
	<?php endif; ?>
	
	<!-- Events Table -->
	<div class="apollo-stats-table" style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 2rem;">
		<h2 style="margin-top: 0;"><?php echo esc_html__('Estatísticas por Evento', 'apollo-events-manager'); ?></h2>
		
		<?php if (! empty($events_data)) : ?>
		<table class="wp-list-table widefat fixed striped" style="margin-top: 1rem;">
			<thead>
				<tr>
					<th><?php echo esc_html__('Evento', 'apollo-events-manager'); ?></th>
					<th><?php echo esc_html__('Total', 'apollo-events-manager'); ?></th>
					<th><?php echo esc_html__('Modal', 'apollo-events-manager'); ?></th>
					<th><?php echo esc_html__('Página', 'apollo-events-manager'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($events_data as $event) : ?>
				<tr>
					<td>
						<strong>
							<a href="<?php echo esc_url(get_permalink($event['id'])); ?>" target="_blank">
								<?php echo esc_html($event['title']); ?>
							</a>
						</strong>
					</td>
					<td><strong><?php echo number_format($event['stats']['total_views'], 0, ',', '.'); ?></strong></td>
					<td><?php echo number_format($event['stats']['popup_count'], 0, ',', '.'); ?></td>
					<td><?php echo number_format($event['stats']['page_count'], 0, ',', '.'); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php else : ?>
		<p><?php echo esc_html__('Nenhuma estatística disponível ainda.', 'apollo-events-manager'); ?></p>
		<?php endif; ?>
	</div>
</div>

<style>
.apollo-stat-card {
	transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.apollo-stat-card:hover {
	transform: translateY(-4px);
	box-shadow: 0 4px 16px rgba(0,0,0,0.15) !important;
}

.stat-number {
	animation: countUp 0.8s ease-out;
}

@keyframes countUp {
	from {
		opacity: 0;
		transform: translateY(10px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}
</style>

<script>
(function() {
	'use strict';
	
	// Animate counters on load
	document.addEventListener('DOMContentLoaded', function() {
		const counters = document.querySelectorAll('[data-motion-counter="true"] .stat-number');
		
		counters.forEach(function(counter) {
			const finalValue = parseInt(counter.getAttribute('data-value') || counter.textContent.replace(/\D/g, ''), 10);
			if (isNaN(finalValue)) return;
			
			let current = 0;
			const duration = 1000; // 1 second
			const increment = finalValue / (duration / 16); // 60fps
			
			const timer = setInterval(function() {
				current += increment;
				if (current >= finalValue) {
					current = finalValue;
					clearInterval(timer);
				}
				counter.textContent = Math.floor(current).toLocaleString('pt-BR');
			}, 16);
		});
	});
})();
</script>
