<?php
// phpcs:ignoreFile
/**
 * Template: Moderação de Eventos
 *
 * Página restrita a editores para moderar eventos pendentes
 *
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . '../includes/helpers/event-data-helper.php';

// Security: only editors and above
if (! current_user_can('edit_others_posts')) {
    wp_die(__('Você não tem permissão para acessar esta página.', 'apollo-events-manager'));
}

get_header();

// Get pending events
$pending_events = new WP_Query(
    [
        'post_type'      => 'event_listing',
        'post_status'    => 'pending',
        'posts_per_page' => 50,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]
);

// Get draft events
$draft_events = new WP_Query(
    [
        'post_type'      => 'event_listing',
        'post_status'    => 'draft',
        'posts_per_page' => 50,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]
);
?>

<div class="apollo-mod-page min-h-screen bg-background py-8 px-4">
	<div class="max-w-7xl mx-auto">
		
		<!-- Header -->
		<div class="mb-8">
			<h1 class="text-4xl font-bold tracking-tight mb-2 flex items-center gap-3">
				<i class="ri-shield-check-line"></i>
				Moderação de Eventos
			</h1>
			<p class="text-muted-foreground">
				Revise e aprove eventos pendentes ou edite rascunhos.
			</p>
		</div>

		<!-- Tabs -->
		<div class="tabs-container" data-motion-tabs="true">
			<!-- Tabs Nav -->
			<div class="tabs-nav flex gap-4 border-b-2 border-border pb-0 mb-6 relative">
				<button class="tab-trigger active" data-tab-trigger="pending" style="padding: 1rem 1.5rem; border: none; background: none; cursor: pointer; font-weight: 600; color: #007cba;">
					Pendentes (<?php echo esc_html($pending_events->found_posts); ?>)
				</button>
				<button class="tab-trigger" data-tab-trigger="draft" style="padding: 1rem 1.5rem; border: none; background: none; cursor: pointer; font-weight: 600; color: #666;">
					Rascunhos (<?php echo esc_html($draft_events->found_posts); ?>)
				</button>
				<button class="tab-trigger" data-tab-trigger="published" style="padding: 1rem 1.5rem; border: none; background: none; cursor: pointer; font-weight: 600; color: #666;">
					Publicados Recentes
				</button>
			</div>

			<!-- Pending Tab -->
			<div class="tab-panel" data-tab-panel="pending" style="display: block;">
				<?php if ($pending_events->have_posts()) : ?>
				<div class="events-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
					<?php
                    while ($pending_events->have_posts()) :
                        $pending_events->the_post();
                        $event_id     = get_the_ID();
                        $event_title  = get_the_title();
                        $event_banner = Apollo_Event_Data_Helper::get_banner_url($event_id);
                        $event_start  = apollo_get_post_meta($event_id, '_event_start_date', true);
                        $author       = get_the_author();
                        ?>
					<div class="event-mod-card bg-card border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-all" data-event-id="<?php echo esc_attr($event_id); ?>">
						<div class="card-image h-40 bg-muted relative">
							<?php if ($event_banner) : ?>
							<img src="<?php echo esc_url($event_banner); ?>" alt="<?php echo esc_attr($event_title); ?>" class="w-full h-full object-cover">
							<?php endif; ?>
							<div class="absolute top-2 right-2 bg-yellow-500 text-white px-2 py-1 rounded text-xs font-medium">
								Pendente
							</div>
						</div>
						<div class="card-content p-4">
							<h3 class="font-semibold text-lg mb-1"><?php echo esc_html($event_title); ?></h3>
							<p class="text-sm text-muted-foreground mb-2">
								<i class="ri-user-line"></i> <?php echo esc_html($author); ?>
							</p>
							<?php if ($event_start) : ?>
							<p class="text-sm text-muted-foreground mb-3">
								<i class="ri-calendar-line"></i> <?php echo esc_html(date_i18n('d/m/Y', strtotime($event_start))); ?>
							</p>
							<?php endif; ?>
							
							<div class="flex gap-2">
								<button onclick="apolloModApprove(<?php echo esc_js($event_id); ?>)" class="flex-1 px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition text-sm">
									<i class="ri-check-line"></i> Aprovar
								</button>
								<button onclick="apolloModReject(<?php echo esc_js($event_id); ?>)" class="flex-1 px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition text-sm">
									<i class="ri-close-line"></i> Rejeitar
								</button>
								<a href="<?php echo esc_url(get_edit_post_link($event_id)); ?>" class="px-3 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition text-sm">
									<i class="ri-edit-line"></i>
								</a>
							</div>
						</div>
					</div>
						<?php
                    endwhile;
				    wp_reset_postdata();
				    ?>
				</div>
				<?php else : ?>
				<div class="text-center py-12">
					<i class="ri-checkbox-circle-line text-6xl text-muted-foreground mb-4"></i>
					<p class="text-muted-foreground">Nenhum evento pendente para moderar.</p>
				</div>
				<?php endif; ?>
			</div>

			<!-- Draft Tab -->
			<div class="tab-panel" data-tab-panel="draft" style="display: none;">
				<?php if ($draft_events->have_posts()) : ?>
				<div class="events-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
					<?php
				    while ($draft_events->have_posts()) :
				        $draft_events->the_post();
				        $event_id     = get_the_ID();
				        $event_title  = get_the_title();
				        $event_banner = Apollo_Event_Data_Helper::get_banner_url($event_id);
				        $author       = get_the_author();
				        ?>
					<div class="event-mod-card bg-card border rounded-lg overflow-hidden shadow-sm">
						<div class="card-image h-40 bg-muted">
							<?php if ($event_banner) : ?>
							<img src="<?php echo esc_url($event_banner); ?>" alt="<?php echo esc_attr($event_title); ?>" class="w-full h-full object-cover">
							<?php endif; ?>
						</div>
						<div class="card-content p-4">
							<h3 class="font-semibold text-lg mb-1"><?php echo esc_html($event_title); ?></h3>
							<p class="text-sm text-muted-foreground mb-3">
								<i class="ri-user-line"></i> <?php echo esc_html($author); ?>
							</p>
							
							<a href="<?php echo esc_url(get_edit_post_link($event_id)); ?>" class="block w-full text-center px-3 py-2 bg-primary text-white rounded hover:bg-primary/90 transition">
								<i class="ri-edit-line"></i> Editar Rascunho
							</a>
						</div>
					</div>
						<?php
				    endwhile;
				    wp_reset_postdata();
				    ?>
				</div>
				<?php else : ?>
				<div class="text-center py-12">
					<p class="text-muted-foreground">Nenhum rascunho encontrado.</p>
				</div>
				<?php endif; ?>
			</div>

			<!-- Published Tab -->
			<div class="tab-panel" data-tab-panel="published" style="display: none;">
				<p class="text-muted-foreground">Eventos publicados recentes serão exibidos aqui...</p>
			</div>
		</div>
	</div>
</div>

<script>
function apolloModApprove(eventId) {
	if (!confirm('Aprovar este evento para publicação?')) return;
	
	jQuery.ajax({
		url: '<?php echo admin_url('admin-ajax.php'); ?>',
		type: 'POST',
		data: {
			action: 'apollo_mod_approve_event',
			event_id: eventId,
			nonce: '<?php echo wp_create_nonce('apollo_mod_approve'); ?>'
		},
		success: function(response) {
			if (response.success) {
				location.reload();
			} else {
				alert('Erro: ' + (response.data || 'Erro desconhecido'));
			}
		}
	});
}

function apolloModReject(eventId) {
	if (!confirm('Rejeitar este evento? Ele será movido para rascunho.')) return;
	
	jQuery.ajax({
		url: '<?php echo admin_url('admin-ajax.php'); ?>',
		type: 'POST',
		data: {
			action: 'apollo_mod_reject_event',
			event_id: eventId,
			nonce: '<?php echo wp_create_nonce('apollo_mod_reject'); ?>'
		},
		success: function(response) {
			if (response.success) {
				location.reload();
			} else {
				alert('Erro: ' + (response.data || 'Erro desconhecido'));
			}
		}
	});
}
</script>

<?php get_footer(); ?>
