<?php
// phpcs:ignoreFile
/**
 * Template Name: Cenario New Event
 * Description: Formulário completo de submissão de eventos usando ShadCN/Tailwind
 *
 * Este template fornece um formulário completo equivalente ao metabox do backend
 * para permitir submissão de eventos no frontend.
 */

if (! defined('ABSPATH')) {
    exit;
}

// Verificar se usuário está logado
if (! is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

// Processar submissão
$submitted       = false;
$error_message   = '';
$success_message = '';

if (isset($_POST['apollo_submit_new_event']) && wp_verify_nonce($_POST['apollo_new_event_nonce'], 'apollo_new_event_submit')) {
    $result = apollo_process_new_event_submission();

    if (is_wp_error($result)) {
        $error_message = $result->get_error_message();
    } else {
        $submitted       = true;
        $success_message = __('Evento salvo como rascunho! Você pode editá-lo no painel administrativo.', 'apollo-events-manager');
    }
}

// Carregar ShadCN/Tailwind
if (function_exists('apollo_shadcn_init')) {
    apollo_shadcn_init();
}

get_header();
?>

<div class="min-h-screen bg-gradient-to-br from-background via-background to-muted/20 py-8 px-4">
	<div class="max-w-4xl mx-auto">
		
		<!-- Header -->
		<div class="mb-8 text-center">
			<h1 class="text-4xl font-bold tracking-tight mb-2">
				<i class="ri-calendar-event-line mr-2"></i>
				Criar Novo Evento
			</h1>
			<p class="text-muted-foreground">
				Preencha todos os campos abaixo. O evento será salvo como rascunho para revisão.
			</p>
		</div>

		<?php if ($submitted && ! empty($success_message)) : ?>
		<div class="mb-6 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 flex items-start gap-3">
			<i class="ri-checkbox-circle-line text-green-600 dark:text-green-400 text-xl"></i>
			<div>
				<p class="font-medium text-green-900 dark:text-green-100"><?php echo esc_html($success_message); ?></p>
			</div>
		</div>
		<?php endif; ?>

		<?php if (! empty($error_message)) : ?>
		<div class="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 flex items-start gap-3">
			<i class="ri-error-warning-line text-red-600 dark:text-red-400 text-xl"></i>
			<div>
				<p class="font-medium text-red-900 dark:text-red-100"><?php echo esc_html($error_message); ?></p>
			</div>
		</div>
		<?php endif; ?>

		<form method="post" id="apollo-new-event-form" class="space-y-6" data-apollo-form="true">
			<?php wp_nonce_field('apollo_new_event_submit', 'apollo_new_event_nonce'); ?>
			
			<!-- Card: Informações Básicas -->
			<div class="bg-card border rounded-lg p-6 shadow-sm">
				<h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
					<i class="ri-information-line"></i>
					Informações Básicas
				</h2>
				
				<div class="space-y-4">
					<div>
						<label for="event_title" class="block text-sm font-medium mb-2">
							Título do Evento <span class="text-red-500">*</span>
						</label>
						<input 
							type="text" 
							id="event_title" 
							name="event_title" 
							required
							class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
							placeholder="Ex: Tomorrowland Brasil 2025"
						>
					</div>

					<div>
						<label for="event_description" class="block text-sm font-medium mb-2">
							Descrição do Evento
						</label>
						<textarea 
							id="event_description" 
							name="event_description" 
							rows="4"
							data-char-counter="500"
							class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
							placeholder="Descreva o evento, line-up, atrações especiais..."
						></textarea>
					</div>
				</div>
			</div>

			<!-- Card: Data e Horário -->
			<div class="bg-card border rounded-lg p-6 shadow-sm">
				<h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
					<i class="ri-calendar-line"></i>
					Data e Horário
				</h2>
				
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<label for="event_start_date" class="block text-sm font-medium mb-2">
							Data de Início <span class="text-red-500">*</span>
						</label>
						<input 
							type="date" 
							id="event_start_date" 
							name="event_start_date" 
							required
							min="<?php echo date('Y-m-d'); ?>"
							class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
						>
					</div>

					<div>
						<label for="event_start_time" class="block text-sm font-medium mb-2">
							Hora de Início
						</label>
						<input 
							type="time" 
							id="event_start_time" 
							name="event_start_time"
							class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
						>
					</div>

					<div>
						<label for="event_end_date" class="block text-sm font-medium mb-2">
							Data de Término
						</label>
						<input 
							type="date" 
							id="event_end_date" 
							name="event_end_date"
							min="<?php echo date('Y-m-d'); ?>"
							class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
						>
					</div>

					<div>
						<label for="event_end_time" class="block text-sm font-medium mb-2">
							Hora de Término
						</label>
						<input 
							type="time" 
							id="event_end_time" 
							name="event_end_time"
							class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
						>
					</div>
				</div>
			</div>

			<!-- Card: DJs e Line-up -->
			<div class="bg-card border rounded-lg p-6 shadow-sm">
				<h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
					<i class="ri-disc-line"></i>
					DJs e Line-up
				</h2>
				
				<div class="space-y-4">
					<div>
						<label for="event_djs" class="block text-sm font-medium mb-2">
							Selecionar DJs
						</label>
						<select 
							multiple 
							id="event_djs" 
							name="event_djs[]"
							size="8"
							class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
						>
							<?php
                            $all_djs = get_posts(
                                [
                                    'post_type'      => 'event_dj',
                                    'posts_per_page' => -1,
                                    'orderby'        => 'title',
                                    'order'          => 'ASC',
                                    'post_status'    => 'publish',
                                ]
                            );

foreach ($all_djs as $dj) {
    $dj_name = apollo_get_post_meta($dj->ID, '_dj_name', true) ?: $dj->post_title;
    printf(
        '<option value="%d">%s</option>',
        esc_attr($dj->ID),
        esc_html($dj_name)
    );
}
?>
						</select>
						<p class="mt-2 text-sm text-muted-foreground">
							Segure Ctrl/Cmd para selecionar múltiplos DJs
						</p>
					</div>

					<!-- Timetable -->
					<div>
						<label class="block text-sm font-medium mb-2">
							Horários do Line-up
						</label>
						<div id="timetable-container" class="border rounded-md p-4 bg-muted/30">
							<table id="timetable-table" class="w-full hidden">
								<thead>
									<tr class="border-b">
										<th class="text-left py-2 px-3">DJ</th>
										<th class="text-left py-2 px-3">Início</th>
										<th class="text-left py-2 px-3">Término</th>
									</tr>
								</thead>
								<tbody id="timetable-rows">
									<!-- Dynamic rows -->
								</tbody>
							</table>
							<p id="timetable-empty" class="text-sm text-muted-foreground text-center py-4">
								Selecione DJs acima para configurar os horários
							</p>
						</div>
						<input type="hidden" id="apollo_event_timetable" name="apollo_event_timetable" value="">
					</div>
				</div>
			</div>

			<!-- Card: Local -->
			<div class="bg-card border rounded-lg p-6 shadow-sm">
				<h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
					<i class="ri-map-pin-2-line"></i>
					Local do Evento
				</h2>
				
				<div class="space-y-4">
					<div>
						<label for="event_local" class="block text-sm font-medium mb-2">
							Selecionar Local
						</label>
						<select 
							id="event_local" 
							name="event_local"
							class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
						>
							<option value="">Selecione um local</option>
							<?php
$all_locals = get_posts(
    [
        'post_type'      => 'event_local',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    ]
);

foreach ($all_locals as $local) {
    $local_name = apollo_get_post_meta($local->ID, '_local_name', true) ?: $local->post_title;
    printf(
        '<option value="%d">%s</option>',
        esc_attr($local->ID),
        esc_html($local_name)
    );
}
?>
						</select>
					</div>

					<div>
						<label for="event_location" class="block text-sm font-medium mb-2">
							Localização Alternativa (texto)
						</label>
						<input 
							type="text" 
							id="event_location" 
							name="event_location"
							class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
							placeholder="Nome do local | Área"
						>
						<p class="mt-1 text-sm text-muted-foreground">
							Use se nenhum local foi selecionado acima
						</p>
					</div>

					<div>
						<label for="event_country" class="block text-sm font-medium mb-2">
							País
						</label>
						<input 
							type="text" 
							id="event_country" 
							name="event_country"
							value="Brasil"
							class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
						>
					</div>
				</div>
			</div>

			<!-- Card: Mídia -->
			<div class="bg-card border rounded-lg p-6 shadow-sm">
				<h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
					<i class="ri-image-line"></i>
					Mídia
				</h2>
				
				<div class="space-y-4">
					<div>
						<label for="event_banner" class="block text-sm font-medium mb-2">
							Banner do Evento (URL)
						</label>
						<input 
							type="url" 
							id="event_banner" 
							name="event_banner"
							class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
							placeholder="https://..."
						>
						<p class="mt-1 text-sm text-muted-foreground">
							URL da imagem principal do evento
						</p>
					</div>

					<div>
						<label for="event_video_url" class="block text-sm font-medium mb-2">
							Vídeo Promocional (URL)
						</label>
						<input 
							type="url" 
							id="event_video_url" 
							name="event_video_url"
							class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
							placeholder="https://www.youtube.com/watch?v=..."
						>
						<p class="mt-1 text-sm text-muted-foreground">
							YouTube, Vimeo ou outro vídeo promocional
						</p>
					</div>
				</div>
			</div>

			<!-- Card: Ingressos e Promoção -->
			<div class="bg-card border rounded-lg p-6 shadow-sm">
				<h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
					<i class="ri-ticket-line"></i>
					Ingressos e Promoção
				</h2>
				
				<div class="space-y-4">
					<div>
						<label for="tickets_ext" class="block text-sm font-medium mb-2">
							Link de Ingressos (URL Externa)
						</label>
						<input 
							type="url" 
							id="tickets_ext" 
							name="tickets_ext"
							class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
							placeholder="https://sympla.com.br/..."
						>
					</div>

					<div class="flex items-center gap-2">
						<input 
							type="checkbox" 
							id="cupom_ario" 
							name="cupom_ario" 
							value="1"
							class="w-4 h-4"
						>
						<label for="cupom_ario" class="text-sm font-medium">
							Evento tem cupom Apollo::Rio
						</label>
					</div>
				</div>
			</div>

			<!-- Submit Button -->
			<div class="flex justify-end gap-4">
				<button 
					type="submit" 
					name="apollo_submit_new_event"
					class="px-6 py-3 bg-primary text-primary-foreground rounded-md font-medium hover:bg-primary/90 transition-colors flex items-center gap-2"
				>
					<i class="ri-save-line"></i>
					Salvar como Rascunho
				</button>
			</div>
		</form>
	</div>
</div>

<script>
(function() {
	'use strict';
	
	let timetableData = [];
	
	// Rebuild timetable when DJs change
	function rebuildTimetable() {
		const select = document.getElementById('event_djs');
		const table = document.getElementById('timetable-table');
		const empty = document.getElementById('timetable-empty');
		const rows = document.getElementById('timetable-rows');
		
		const selectedDJs = Array.from(select.selectedOptions);
		rows.innerHTML = '';
		
		if (selectedDJs.length === 0) {
			table.classList.add('hidden');
			empty.classList.remove('hidden');
			timetableData = [];
			updateTimetableHidden();
			return;
		}
		
		table.classList.remove('hidden');
		empty.classList.add('hidden');
		
		selectedDJs.forEach(function(option) {
			const djID = option.value;
			const djName = option.text;
			
			// Find existing time data
			let existingStart = '';
			let existingEnd = '';
			const existing = timetableData.find(item => item.dj == djID);
			if (existing) {
				existingStart = existing.from || existing.start || '';
				existingEnd = existing.to || existing.end || '';
			}
			
			const row = document.createElement('tr');
			row.className = 'border-b';
			row.setAttribute('data-dj-id', djID);
			
			row.innerHTML = `
				<td class="py-2 px-3 font-medium">${djName}</td>
				<td class="py-2 px-3">
					<input 
						type="time" 
						class="timetable-start w-full px-2 py-1 border rounded text-sm" 
						data-dj-id="${djID}" 
						value="${existingStart}"
					>
				</td>
				<td class="py-2 px-3">
					<input 
						type="time" 
						class="timetable-end w-full px-2 py-1 border rounded text-sm" 
						data-dj-id="${djID}" 
						value="${existingEnd}"
					>
				</td>
			`;
			
			rows.appendChild(row);
		});
		
		updateTimetableData();
	}
	
	function updateTimetableData() {
		timetableData = [];
		const rows = document.querySelectorAll('#timetable-rows tr');
		
		rows.forEach(function(row) {
			const djID = row.getAttribute('data-dj-id');
			const startInput = row.querySelector('.timetable-start');
			const endInput = row.querySelector('.timetable-end');
			
			const start = startInput ? startInput.value : '';
			const end = endInput ? endInput.value : '';
			
			const entry = {
				dj: parseInt(djID, 10)
			};
			
			if (start) {
				entry.from = start;
				entry.to = end || start;
			}
			
			timetableData.push(entry);
		});
		
		updateTimetableHidden();
	}
	
	function updateTimetableHidden() {
		const hidden = document.getElementById('apollo_event_timetable');
		if (hidden) {
			hidden.value = JSON.stringify(timetableData);
		}
	}
	
	// Event listeners
	const djSelect = document.getElementById('event_djs');
	if (djSelect) {
		djSelect.addEventListener('change', rebuildTimetable);
	}
	
	const timetableContainer = document.getElementById('timetable-container');
	if (timetableContainer) {
		timetableContainer.addEventListener('change', function(e) {
			if (e.target.classList.contains('timetable-start') || e.target.classList.contains('timetable-end')) {
				updateTimetableData();
			}
		});
	}
	
	// Save timetable before form submit
	const form = document.getElementById('apollo-new-event-form');
	if (form) {
		form.addEventListener('submit', function() {
			updateTimetableData();
		});
	}
})();
</script>

<?php
get_footer();

/**
 * Process new event submission
 */
function apollo_process_new_event_submission()
{
    // Security check
    if (! isset($_POST['apollo_new_event_nonce']) || ! wp_verify_nonce($_POST['apollo_new_event_nonce'], 'apollo_new_event_submit')) {
        return new WP_Error('security', __('Falha na verificação de segurança.', 'apollo-events-manager'));
    }

    // Required fields
    if (empty($_POST['event_title']) || empty($_POST['event_start_date'])) {
        return new WP_Error('required', __('Título e data de início são obrigatórios.', 'apollo-events-manager'));
    }

    $current_user_id = get_current_user_id();
    if (! $current_user_id) {
        return new WP_Error('auth', __('Você precisa estar logado para criar eventos.', 'apollo-events-manager'));
    }

    // Prepare post data - SAVE AS DRAFT
    $post_data = [
        'post_title'   => sanitize_text_field($_POST['event_title']),
        'post_content' => isset($_POST['event_description']) ? wp_kses_post($_POST['event_description']) : '',
        'post_status'  => 'draft',
        // ✅ SAVE AS DRAFT
                    'post_type' => 'event_listing',
        'post_author'           => $current_user_id,
    ];

    $event_id = wp_insert_post($post_data, true);

    if (is_wp_error($event_id)) {
        return $event_id;
    }

    // Save meta fields
    if (isset($_POST['event_start_date'])) {
        apollo_update_post_meta($event_id, '_event_start_date', sanitize_text_field($_POST['event_start_date']));
    }
    if (isset($_POST['event_start_time'])) {
        apollo_update_post_meta($event_id, '_event_start_time', sanitize_text_field($_POST['event_start_time']));
    }
    if (isset($_POST['event_end_date'])) {
        apollo_update_post_meta($event_id, '_event_end_date', sanitize_text_field($_POST['event_end_date']));
    }
    if (isset($_POST['event_end_time'])) {
        apollo_update_post_meta($event_id, '_event_end_time', sanitize_text_field($_POST['event_end_time']));
    }

    // Save DJs
    if (isset($_POST['event_djs']) && is_array($_POST['event_djs'])) {
        $djs = array_map('absint', $_POST['event_djs']);
        $djs = array_filter($djs);
        if (! empty($djs)) {
            apollo_update_post_meta($event_id, '_event_dj_ids', $djs);
        }
    }

    // Save Local
    if (isset($_POST['event_local']) && ! empty($_POST['event_local'])) {
        apollo_update_post_meta($event_id, '_event_local_ids', absint($_POST['event_local']));
    }

    // Save Timetable
    if (isset($_POST['apollo_event_timetable']) && ! empty($_POST['apollo_event_timetable'])) {
        $timetable = json_decode(stripslashes($_POST['apollo_event_timetable']), true);
        if (is_array($timetable) && function_exists('apollo_sanitize_timetable')) {
            $clean_timetable = apollo_sanitize_timetable($timetable);
            if (! empty($clean_timetable)) {
                apollo_update_post_meta($event_id, '_event_timetable', $clean_timetable);
            }
        }
    }

    // Save media
    if (isset($_POST['event_banner']) && ! empty($_POST['event_banner'])) {
        apollo_update_post_meta($event_id, '_event_banner', esc_url_raw($_POST['event_banner']));
    }
    if (isset($_POST['event_video_url']) && ! empty($_POST['event_video_url'])) {
        apollo_update_post_meta($event_id, '_event_video_url', esc_url_raw($_POST['event_video_url']));
    }

    // Save location
    if (isset($_POST['event_location']) && ! empty($_POST['event_location'])) {
        apollo_update_post_meta($event_id, '_event_location', sanitize_text_field($_POST['event_location']));
    }
    if (isset($_POST['event_country']) && ! empty($_POST['event_country'])) {
        apollo_update_post_meta($event_id, '_event_country', sanitize_text_field($_POST['event_country']));
    }

    // Save tickets
    if (isset($_POST['tickets_ext']) && ! empty($_POST['tickets_ext'])) {
        apollo_update_post_meta($event_id, '_tickets_ext', esc_url_raw($_POST['tickets_ext']));
    }
    if (isset($_POST['cupom_ario']) && $_POST['cupom_ario'] == '1') {
        apollo_update_post_meta($event_id, '_cupom_ario', '1');
    }

    // Mark as frontend submission
    apollo_update_post_meta($event_id, '_apollo_frontend_submission', '1');
    apollo_update_post_meta($event_id, '_apollo_submission_date', current_time('mysql'));

    return $event_id;
}
