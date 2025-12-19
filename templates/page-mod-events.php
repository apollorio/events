<?php
// phpcs:ignoreFile
/**
 * Template Name: Mod Events
 * Description: Página de moderação de eventos para editores aprovarem/rejeitarem rascunhos
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../includes/helpers/event-data-helper.php';

// Verificar se usuário tem permissão de editor
if (! current_user_can('edit_posts') && ! current_user_can('edit_event_listings')) {
    wp_die(__('Você não tem permissão para acessar esta página.', 'apollo-events-manager'));
}

// Processar ações via POST (fallback se JS desabilitado)
$success_message = '';
if (isset($_POST['apollo_mod_action']) && wp_verify_nonce($_POST['apollo_mod_nonce'], 'apollo_mod_events')) {
    $action   = sanitize_text_field($_POST['apollo_mod_action']);
    $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;

    if ($event_id > 0) {
        if ($action === 'approve') {
            $result = wp_update_post(
                [
                    'ID'          => $event_id,
                    'post_status' => 'publish',
                ]
            );

            if ($result && ! is_wp_error($result)) {
                apollo_update_post_meta($event_id, '_apollo_mod_approved', '1');
                apollo_update_post_meta($event_id, '_apollo_mod_approved_date', current_time('mysql'));
                apollo_update_post_meta($event_id, '_apollo_mod_approved_by', get_current_user_id());
                apollo_delete_post_meta($event_id, '_apollo_mod_rejected');
                $success_message = __('Evento aprovado e publicado com sucesso!', 'apollo-events-manager');
            }
        } elseif ($action === 'reject') {
            apollo_update_post_meta($event_id, '_apollo_mod_rejected', '1');
            apollo_update_post_meta($event_id, '_apollo_mod_rejected_date', current_time('mysql'));
            apollo_update_post_meta($event_id, '_apollo_mod_rejected_by', get_current_user_id());
            $success_message = __('Evento rejeitado. Removido da lista de moderação.', 'apollo-events-manager');
        }//end if
    }//end if
}//end if

// Carregar ShadCN/Tailwind
if (function_exists('apollo_shadcn_init')) {
    apollo_shadcn_init();
}

get_header();

// Buscar eventos draft futuros
$draft_events = get_posts(
    [
        'post_type'      => 'event_listing',
        'post_status'    => 'draft',
        'posts_per_page' => -1,
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => '_event_start_date',
                'value'   => date('Y-m-d'),
                'compare' => '>=',
                'type'    => 'DATE',
            ],
            [
                'key'     => '_apollo_mod_rejected',
                'compare' => 'NOT EXISTS',
            ],
        ],
        'orderby'  => 'meta_value',
        'meta_key' => '_event_start_date',
        'order'    => 'ASC',
    ]
);
?>

<div class="min-h-screen bg-gradient-to-br from-background via-background to-muted/20 py-8 px-4">
	<div class="max-w-6xl mx-auto">
		
		<!-- Header -->
		<div class="mb-8">
			<h1 class="text-4xl font-bold tracking-tight mb-2 flex items-center gap-3">
				<i class="ri-shield-check-line"></i>
				Moderação de Eventos
			</h1>
			<p class="text-muted-foreground">
				Revise e aprove ou rejeite eventos pendentes de publicação
			</p>
		</div>

		<!-- Stats Cards -->
		<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
			<div class="bg-card border rounded-lg p-4 shadow-sm">
				<div class="flex items-center justify-between">
					<div>
						<p class="text-sm text-muted-foreground">Pendentes</p>
						<p class="text-2xl font-bold"><?php echo count($draft_events); ?></p>
					</div>
					<i class="ri-file-list-3-line text-3xl text-muted-foreground"></i>
				</div>
			</div>
			<div class="bg-card border rounded-lg p-4 shadow-sm">
				<div class="flex items-center justify-between">
					<div>
						<p class="text-sm text-muted-foreground">Aguardando Revisão</p>
						<p class="text-2xl font-bold"><?php echo count($draft_events); ?></p>
					</div>
					<i class="ri-time-line text-3xl text-muted-foreground"></i>
				</div>
			</div>
			<div class="bg-card border rounded-lg p-4 shadow-sm">
				<div class="flex items-center justify-between">
					<div>
						<p class="text-sm text-muted-foreground">Futuros</p>
						<p class="text-2xl font-bold"><?php echo count($draft_events); ?></p>
					</div>
					<i class="ri-calendar-event-line text-3xl text-muted-foreground"></i>
				</div>
			</div>
		</div>

		<?php if (! empty($success_message)) : ?>
		<div class="mb-6 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 flex items-start gap-3">
			<i class="ri-checkbox-circle-line text-green-600 dark:text-green-400 text-xl"></i>
			<div>
				<p class="font-medium text-green-900 dark:text-green-100"><?php echo esc_html($success_message); ?></p>
			</div>
		</div>
		<?php endif; ?>

		<?php if (empty($draft_events)) : ?>
		<!-- Empty State -->
		<div class="bg-card border rounded-lg p-12 text-center">
			<i class="ri-checkbox-circle-line text-6xl text-muted-foreground mb-4"></i>
			<h2 class="text-2xl font-semibold mb-2">Nenhum evento pendente</h2>
			<p class="text-muted-foreground">
				Todos os eventos foram revisados ou não há eventos futuros aguardando aprovação.
			</p>
		</div>
		<?php else : ?>
		
		<!-- Events List -->
		<div class="space-y-4">
			<?php
            foreach ($draft_events as $event) :
                $event_id         = $event->ID;
                $event_title      = apollo_get_post_meta($event_id, '_event_title', true) ?: $event->post_title;
                $event_start_date = apollo_get_post_meta($event_id, '_event_start_date', true);
                $event_start_time = apollo_get_post_meta($event_id, '_event_start_time', true);
                $event_location   = apollo_get_post_meta($event_id, '_event_location', true);
                $event_banner     = Apollo_Event_Data_Helper::get_banner_url($event_id);
                $local            = Apollo_Event_Data_Helper::get_local_data($event_id);
                $local_name       = $local ? $local['name'] : '';

                if (empty($local_name) && ! empty($event_location)) {
                    $local_name = $event_location;
                }

                // Get DJs
                $dj_names = Apollo_Event_Data_Helper::get_dj_lineup($event_id);

                // Format date
                $formatted_date = '';
                if ($event_start_date) {
                    $date_obj = DateTime::createFromFormat('Y-m-d', $event_start_date);
                    if ($date_obj) {
                        $formatted_date = $date_obj->format('d/m/Y');
                        if ($event_start_time) {
                            $formatted_date .= ' às ' . date('H:i', strtotime($event_start_time));
                        }
                    }
                }

                // Author info
                $author          = get_userdata($event->post_author);
                $author_name     = $author ? $author->display_name : 'Desconhecido';
                $submission_date = apollo_get_post_meta($event_id, '_apollo_submission_date', true) ?: $event->post_date;
                ?>
			
			<div class="bg-card border rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow" data-event-id="<?php echo esc_attr($event_id); ?>">
				<div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-6">
					
					<!-- Event Image -->
					<div class="md:col-span-1">
						<?php if ($event_banner) : ?>
						<img 
							src="<?php echo esc_url($event_banner); ?>" 
							alt="<?php echo esc_attr($event_title); ?>"
							class="w-full h-32 object-cover rounded-md"
							onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'200\'%3E%3Crect fill=\'%23e2e8f0\' width=\'400\' height=\'200\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'14\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3ESem imagem%3C/text%3E%3C/svg%3E'"
						>
						<?php else : ?>
						<div class="w-full h-32 bg-muted rounded-md flex items-center justify-center">
							<i class="ri-image-line text-4xl text-muted-foreground"></i>
						</div>
						<?php endif; ?>
					</div>
					
					<!-- Event Details -->
					<div class="md:col-span-2 space-y-3">
						<div>
							<h3 class="text-xl font-semibold mb-1">
								<a href="<?php echo get_edit_post_link($event_id); ?>" target="_blank" class="hover:text-primary">
									<?php echo esc_html($event_title); ?>
								</a>
							</h3>
							<p class="text-sm text-muted-foreground">
								Criado por <strong><?php echo esc_html($author_name); ?></strong> em 
								<?php echo date_i18n('d/m/Y H:i', strtotime($submission_date)); ?>
							</p>
						</div>
						
						<div class="flex flex-wrap gap-4 text-sm">
							<?php if ($formatted_date) : ?>
							<div class="flex items-center gap-2">
								<i class="ri-calendar-line text-muted-foreground"></i>
								<span><?php echo esc_html($formatted_date); ?></span>
							</div>
							<?php endif; ?>
							
							<?php if ($local_name) : ?>
							<div class="flex items-center gap-2">
								<i class="ri-map-pin-2-line text-muted-foreground"></i>
								<span><?php echo esc_html($local_name); ?></span>
							</div>
							<?php endif; ?>
							
							<?php if (! empty($dj_names)) : ?>
							<div class="flex items-center gap-2">
								<i class="ri-disc-line text-muted-foreground"></i>
								<span><?php echo esc_html(implode(', ', array_slice($dj_names, 0, 3))); ?><?php echo count($dj_names) > 3 ? '...' : ''; ?></span>
							</div>
							<?php endif; ?>
						</div>
						
						<?php if ($event->post_content) : ?>
						<p class="text-sm text-muted-foreground line-clamp-2">
							<?php echo wp_trim_words(strip_tags($event->post_content), 20); ?>
						</p>
						<?php endif; ?>
					</div>
					
					<!-- Actions -->
					<div class="md:col-span-1 flex flex-col gap-3 justify-center">
						<form method="post" class="apollo-mod-form" data-event-id="<?php echo esc_attr($event_id); ?>">
							<?php wp_nonce_field('apollo_mod_events', 'apollo_mod_nonce'); ?>
							<input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
							<input type="hidden" name="apollo_mod_action" value="approve">
							
							<button 
								type="submit" 
								class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md font-medium transition-colors flex items-center justify-center gap-2"
								onclick="return confirm('Tem certeza que deseja aprovar este evento?');"
							>
								<i class="ri-check-line"></i>
								Aprovar
							</button>
						</form>
						
						<form method="post" class="apollo-mod-form" data-event-id="<?php echo esc_attr($event_id); ?>">
							<?php wp_nonce_field('apollo_mod_events', 'apollo_mod_nonce'); ?>
							<input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
							<input type="hidden" name="apollo_mod_action" value="reject">
							
							<button 
								type="submit" 
								class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md font-medium transition-colors flex items-center justify-center gap-2"
								onclick="return confirm('Tem certeza que deseja rejeitar este evento? Ele será removido da lista mas permanecerá como rascunho.');"
							>
								<i class="ri-close-line"></i>
								Rejeitar
							</button>
						</form>
						
						<a 
							href="<?php echo get_edit_post_link($event_id); ?>" 
							target="_blank"
							class="w-full px-4 py-2 bg-muted hover:bg-muted/80 text-foreground rounded-md font-medium transition-colors flex items-center justify-center gap-2 text-sm"
						>
							<i class="ri-edit-line"></i>
							Editar
						</a>
					</div>
				</div>
			</div>
			
			<?php endforeach; ?>
		</div>
		
		<?php endif; ?>
	</div>
</div>

<script>
(function() {
	'use strict';
	
	// Handle form submissions with AJAX for better UX
	const forms = document.querySelectorAll('.apollo-mod-form');
	
	forms.forEach(function(form) {
		form.addEventListener('submit', function(e) {
			e.preventDefault();
			
			const formData = new FormData(form);
			const eventId = form.dataset.eventId;
			const action = formData.get('apollo_mod_action');
			const button = form.querySelector('button[type="submit"]');
			const originalText = button.innerHTML;
			
			// Disable button
			button.disabled = true;
			button.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Processando...';
			
			// Determine AJAX action based on form action
			const action = formData.get('apollo_mod_action');
			formData.append('action', action === 'approve' ? 'apollo_mod_approve_event' : 'apollo_mod_reject_event');
			
			fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					// Remove event card with animation
					const eventCard = document.querySelector(`[data-event-id="${eventId}"]`);
					if (eventCard) {
						eventCard.style.transition = 'opacity 0.3s, transform 0.3s';
						eventCard.style.opacity = '0';
						eventCard.style.transform = 'translateX(-20px)';
						
						setTimeout(() => {
							eventCard.remove();
							
							// Check if list is empty
							const remainingEvents = document.querySelectorAll('[data-event-id]');
							if (remainingEvents.length === 0) {
								location.reload();
							}
						}, 300);
					}
				} else {
					alert('Erro: ' + (data.data || 'Falha ao processar ação'));
					button.disabled = false;
					button.innerHTML = originalText;
				}
			})
			.catch(error => {
				console.error('Error:', error);
				alert('Erro ao processar. Tente novamente.');
				button.disabled = false;
				button.innerHTML = originalText;
			});
		});
	});
})();
</script>

<?php
get_footer();
