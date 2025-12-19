<?php
// phpcs:ignoreFile
/**
 * Apollo Events Manager - Admin Shortcodes Page
 *
 * Lists all registered shortcodes with descriptions
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Register admin page for shortcodes
 */
function apollo_events_register_shortcodes_page()
{
    add_submenu_page(
        'edit.php?post_type=event_listing',
        __('Shortcodes Apollo Events', 'apollo-events-manager'),
        __('Shortcodes', 'apollo-events-manager'),
        'manage_options',
        'apollo-events-shortcodes',
        'apollo_events_render_shortcodes_page'
    );
}
add_action('admin_menu', 'apollo_events_register_shortcodes_page', 20);

/**
 * Render shortcodes admin page
 */
function apollo_events_render_shortcodes_page()
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $shortcodes = apollo_events_get_all_shortcodes();

    ?>
	<div class="wrap">
		<h1>
			<i class="dashicons dashicons-shortcode" style="font-size: 24px; vertical-align: middle;"></i>
			<?php echo esc_html__('Apollo Events - Shortcodes', 'apollo-events-manager'); ?>
		</h1>
		
		<div class="apollo-shortcodes-container" style="margin-top: 20px;">
			
			<!-- Info Box -->
			<div class="notice notice-info" style="margin: 20px 0;">
				<p>
					<strong><?php echo esc_html__('Como usar:', 'apollo-events-manager'); ?></strong>
					<?php echo esc_html__('Copie o shortcode desejado e cole em qualquer página ou post do WordPress.', 'apollo-events-manager'); ?>
				</p>
			</div>
			
			<!-- Create Main Pages Section -->
			<div class="card" style="margin: 20px 0; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
				<h2 style="margin-top: 0;">
					<span class="dashicons dashicons-admin-page" style="vertical-align: middle;"></span>
					<?php echo esc_html__('Páginas Principais', 'apollo-events-manager'); ?>
				</h2>
				<p style="color: #50575e;">
					<?php echo esc_html__('Crie as páginas principais necessárias para o sistema funcionar corretamente.', 'apollo-events-manager'); ?>
				</p>
				
				<div style="margin-top: 15px;">
					<?php
                    $eventos_page = get_page_by_path('eventos');
    if ($eventos_page && $eventos_page->post_status === 'publish') :
        ?>
						<div style="padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 10px;">
							<span class="dashicons dashicons-yes" style="color: #155724; vertical-align: middle;"></span>
							<strong><?php echo esc_html__('Página "Eventos" já existe:', 'apollo-events-manager'); ?></strong>
							<a href="<?php echo esc_url(get_edit_post_link($eventos_page->ID)); ?>" target="_blank">
								<?php echo esc_html(get_the_title($eventos_page->ID)); ?>
							</a>
						</div>
					<?php else : ?>
						<button 
							type="button" 
							class="button button-primary apollo-create-main-page" 
							data-page-slug="eventos"
							data-page-title="Eventos"
							data-shortcode="[events]"
							data-template="pagx_appclean"
						>
							<span class="dashicons dashicons-plus" style="vertical-align: middle;"></span>
							<?php echo esc_html__('Criar Página "Eventos"', 'apollo-events-manager'); ?>
						</button>
						<p class="description" style="margin-top: 5px;">
							<?php echo esc_html__('Cria página /eventos/ com o shortcode [events] em template canvas.', 'apollo-events-manager'); ?>
						</p>
					<?php endif; ?>
				</div>
			</div>
			
			<!-- Form Guide Section -->
			<div class="card" style="margin: 20px 0; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
				<h2 style="margin-top: 0;">
					<span class="dashicons dashicons-edit" style="vertical-align: middle;"></span>
					<?php echo esc_html__('Guia: Formulário Público de Eventos', 'apollo-events-manager'); ?>
				</h2>
				<p style="color: #50575e;">
					<?php echo esc_html__('Como implementar um formulário público para usuários submeterem eventos como draft.', 'apollo-events-manager'); ?>
				</p>
				
				<div style="margin-top: 20px;">
					<h3><?php echo esc_html__('1. Campos Obrigatórios do Formulário', 'apollo-events-manager'); ?></h3>
					<table class="widefat" style="margin-top: 10px;">
						<thead>
							<tr>
								<th style="padding: 10px;"><?php echo esc_html__('Campo', 'apollo-events-manager'); ?></th>
								<th style="padding: 10px;"><?php echo esc_html__('Meta Key', 'apollo-events-manager'); ?></th>
								<th style="padding: 10px;"><?php echo esc_html__('Tipo', 'apollo-events-manager'); ?></th>
								<th style="padding: 10px;"><?php echo esc_html__('Obrigatório', 'apollo-events-manager'); ?></th>
								<th style="padding: 10px;"><?php echo esc_html__('Descrição', 'apollo-events-manager'); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><strong>Título do Evento</strong></td>
								<td><code>post_title</code></td>
								<td>text</td>
								<td>✅ Sim</td>
								<td>Título principal do evento</td>
							</tr>
							<tr>
								<td><strong>Data de Início</strong></td>
								<td><code>_event_start_date</code></td>
								<td>date</td>
								<td>✅ Sim</td>
								<td>Formato: YYYY-MM-DD</td>
							</tr>
							<tr>
								<td><strong>Hora de Início</strong></td>
								<td><code>_event_start_time</code></td>
								<td>time</td>
								<td>✅ Sim</td>
								<td>Formato: HH:MM</td>
							</tr>
							<tr>
								<td><strong>Banner</strong></td>
								<td><code>_event_banner</code></td>
								<td>url</td>
								<td>⚠️ Recomendado</td>
								<td>URL da imagem do banner</td>
							</tr>
							<tr>
								<td><strong>DJs</strong></td>
								<td><code>_event_dj_ids</code></td>
								<td>array</td>
								<td>⚠️ Recomendado</td>
								<td>Array de IDs dos DJs</td>
							</tr>
							<tr>
								<td><strong>Local</strong></td>
								<td><code>_event_local_ids</code></td>
								<td>integer</td>
								<td>⚠️ Recomendado</td>
								<td>ID do local</td>
							</tr>
							<tr>
								<td><strong>Programação (Line-up)</strong></td>
								<td><code>_event_timetable</code></td>
								<td>array</td>
								<td>❌ Opcional</td>
								<td>Array com DJs e horários</td>
							</tr>
							<tr>
								<td><strong>Descrição</strong></td>
								<td><code>post_content</code></td>
								<td>textarea</td>
								<td>❌ Opcional</td>
								<td>Descrição completa do evento</td>
							</tr>
						</tbody>
					</table>
				</div>
				
				<div style="margin-top: 30px;">
					<h3><?php echo esc_html__('2. Exemplo de HTML do Formulário', 'apollo-events-manager'); ?></h3>
					<p style="color: #50575e; margin-bottom: 10px;">
						<?php echo esc_html__('Estrutura básica do formulário HTML:', 'apollo-events-manager'); ?>
					</p>
					<div style="background: #f0f0f1; padding: 15px; border-radius: 4px; margin-top: 10px;">
						<pre style="margin: 0; overflow-x: auto; font-size: 12px;"><code>&lt;form id="apollo-submit-event-form" method="post" action=""&gt;
	&lt;?php 
	// ✅ OBRIGATÓRIO: Nonce para segurança CSRF
	wp_nonce_field('apollo_submit_event', 'apollo_event_nonce'); 
	?&gt;
	
	&lt;!-- ✅ OBRIGATÓRIO: Título do Evento --&gt;
	&lt;label for="event_title"&gt;Título do Evento *&lt;/label&gt;
	&lt;input 
		type="text" 
		id="event_title"
		name="event_title" 
		required 
		placeholder="Ex: Tomorrowland Brasil 2025"
	/&gt;
	
	&lt;!-- ✅ OBRIGATÓRIO: Data de Início --&gt;
	&lt;label for="event_start_date"&gt;Data de Início *&lt;/label&gt;
	&lt;input 
		type="date" 
		id="event_start_date"
		name="event_start_date" 
		required 
	/&gt;
	
	&lt;!-- ✅ OBRIGATÓRIO: Hora de Início --&gt;
	&lt;label for="event_start_time"&gt;Hora de Início *&lt;/label&gt;
	&lt;input 
		type="time" 
		id="event_start_time"
		name="event_start_time" 
		required 
	/&gt;
	
	&lt;!-- ⚠️ RECOMENDADO: Banner (URL) --&gt;
	&lt;label for="event_banner"&gt;URL do Banner&lt;/label&gt;
	&lt;input 
		type="url" 
		id="event_banner"
		name="event_banner" 
		placeholder="https://exemplo.com/banner.jpg"
	/&gt;
	
	&lt;!-- ⚠️ RECOMENDADO: DJs (IDs separados por vírgula OU select múltiplo) --&gt;
	&lt;label for="event_dj_ids"&gt;IDs dos DJs&lt;/label&gt;
	&lt;!-- Opção 1: Input texto com vírgulas --&gt;
	&lt;input 
		type="text" 
		id="event_dj_ids"
		name="event_dj_ids" 
		placeholder="92, 71, 45"
	/&gt;
	&lt;!-- OU Opção 2: Select múltiplo (melhor UX) --&gt;
	&lt;select id="event_djs" name="event_djs[]" multiple&gt;
		&lt;?php
		$djs = get_posts([
			'post_type' => 'event_dj',
			'posts_per_page' => -1,
			'post_status' => 'publish'
		]);
		foreach ($djs as $dj) {
			$dj_name = get_post_meta($dj->ID, '_dj_name', true) ?: $dj->post_title;
			echo '&lt;option value="' . esc_attr($dj->ID) . '"&gt;' . esc_html($dj_name) . '&lt;/option&gt;';
		}
		?&gt;
	&lt;/select&gt;
	
	&lt;!-- ⚠️ RECOMENDADO: Local (ID) --&gt;
	&lt;label for="event_local_id"&gt;Local&lt;/label&gt;
	&lt;select id="event_local_id" name="event_local_id"&gt;
		&lt;option value=""&gt;Selecione um local&lt;/option&gt;
		&lt;?php
		$locals = get_posts([
			'post_type' => 'event_local',
			'posts_per_page' => -1,
			'post_status' => 'publish'
		]);
		foreach ($locals as $local) {
			$local_name = get_post_meta($local->ID, '_local_name', true) ?: $local->post_title;
			echo '&lt;option value="' . esc_attr($local->ID) . '"&gt;' . esc_html($local_name) . '&lt;/option&gt;';
		}
		?&gt;
	&lt;/select&gt;
	
	&lt;!-- ❌ OPCIONAL: Descrição --&gt;
	&lt;label for="event_description"&gt;Descrição do Evento&lt;/label&gt;
	&lt;textarea 
		id="event_description"
		name="event_description" 
		rows="5"
		placeholder="Descreva o evento..."
	&gt;&lt;/textarea&gt;
	
	&lt;!-- ❌ OPCIONAL: Timetable (JSON hidden input) --&gt;
	&lt;input 
		type="hidden" 
		id="apollo_event_timetable" 
		name="apollo_event_timetable" 
		value=""
	/&gt;
	&lt;!-- Use JavaScript para preencher este campo com array JSON --&gt;
	
	&lt;button type="submit"&gt;Enviar Evento como Draft&lt;/button&gt;
&lt;/form&gt;

&lt;script&gt;
// Exemplo: Preencher timetable antes do submit
document.getElementById('apollo-submit-event-form').addEventListener('submit', function() {
	// Se você tem uma tabela de horários, converta para JSON
	var timetable = [
		{dj: 92, from: '22:00', to: '23:00'},
		{dj: 71, from: '23:00', to: '00:00'}
	];
	document.getElementById('apollo_event_timetable').value = JSON.stringify(timetable);
});
&lt;/script&gt;</code></pre>
					</div>
					<div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
						<p style="margin: 0;">
							<strong><?php echo esc_html__('Dica:', 'apollo-events-manager'); ?></strong>
							<?php echo esc_html__('Use selects para DJs e Locais ao invés de inputs de texto. Isso melhora a UX e evita erros de digitação.', 'apollo-events-manager'); ?>
						</p>
					</div>
				</div>
				
				<div style="margin-top: 30px;">
					<h3><?php echo esc_html__('3. Código PHP para Processar o Submit', 'apollo-events-manager'); ?></h3>
					<p style="color: #50575e; margin-bottom: 10px;">
						<?php echo esc_html__('Adicione este código no functions.php do seu tema ou em um plugin customizado:', 'apollo-events-manager'); ?>
					</p>
					<div style="background: #f0f0f1; padding: 15px; border-radius: 4px; margin-top: 10px;">
						<pre style="margin: 0; overflow-x: auto; font-size: 12px;"><code>&lt;?php
/**
 * Processar submissão pública de eventos
 * Adicione no functions.php ou plugin customizado
 */
add_action('init', 'apollo_process_public_event_submission');

function apollo_process_public_event_submission() {
	// ✅ 1. Verificar nonce (segurança CSRF)
	if (!isset($_POST['apollo_event_nonce']) || 
		!wp_verify_nonce($_POST['apollo_event_nonce'], 'apollo_submit_event')) {
		return;
	}
	
	// ✅ 2. Verificar se usuário está logado
	if (!is_user_logged_in()) {
		wp_die('Você precisa estar logado para enviar eventos.');
	}
	
	// ✅ 3. Sanitizar e validar dados
	$title = sanitize_text_field($_POST['event_title'] ?? '');
	$start_date = sanitize_text_field($_POST['event_start_date'] ?? '');
	$start_time = sanitize_text_field($_POST['event_start_time'] ?? '');
	$banner = esc_url_raw($_POST['event_banner'] ?? '');
	$dj_ids_input = sanitize_text_field($_POST['event_dj_ids'] ?? '');
	$local_id = absint($_POST['event_local_id'] ?? 0);
	$description = wp_kses_post($_POST['event_description'] ?? '');
	
	// ✅ 4. Validar campos obrigatórios
	if (empty($title) || empty($start_date) || empty($start_time)) {
		wp_die('Preencha todos os campos obrigatórios: Título, Data e Hora.');
	}
	
	// ✅ 5. Criar evento como DRAFT (IMPORTANTE!)
	$event_data = [
		'post_title' => $title,
		'post_content' => $description,
		'post_status' => 'draft', // ✅ SEMPRE como draft para moderação
		'post_type' => 'event_listing',
		'post_author' => get_current_user_id(),
	];
	
	$event_id = wp_insert_post($event_data, true);
	
	if (is_wp_error($event_id)) {
		wp_die('Erro ao criar evento: ' . $event_id->get_error_message());
	}
	
	// ✅ 6. Salvar meta keys (use apollo_update_post_meta para sanitização)
	if (function_exists('apollo_update_post_meta')) {
		// Data e Hora de Início
		$start_datetime = $start_date . ' ' . $start_time . ':00';
		apollo_update_post_meta($event_id, '_event_start_date', $start_datetime);
		apollo_update_post_meta($event_id, '_event_start_time', $start_time);
		
		// Banner (URL)
		if (!empty($banner)) {
			apollo_update_post_meta($event_id, '_event_banner', $banner);
		}
		
		// DJs (array de IDs)
		if (!empty($dj_ids_input)) {
			// Se vier como string "92, 71", converter para array
			if (is_string($dj_ids_input)) {
				$dj_array = array_map('absint', explode(',', $dj_ids_input));
			} elseif (is_array($dj_ids_input)) {
				$dj_array = array_map('absint', $dj_ids_input);
			} else {
				$dj_array = [];
			}
			$dj_array = array_filter($dj_array);
			if (!empty($dj_array)) {
				apollo_update_post_meta($event_id, '_event_dj_ids', $dj_array);
			}
		}
		
		// Local (ID único)
		if ($local_id > 0) {
			apollo_update_post_meta($event_id, '_event_local_ids', $local_id);
		}
		
		// Timetable (se fornecido como JSON)
		if (isset($_POST['apollo_event_timetable']) && !empty($_POST['apollo_event_timetable'])) {
			$timetable = json_decode(stripslashes($_POST['apollo_event_timetable']), true);
			if (is_array($timetable) && function_exists('apollo_sanitize_timetable')) {
				$clean_timetable = apollo_sanitize_timetable($timetable);
				if (!empty($clean_timetable)) {
					apollo_update_post_meta($event_id, '_event_timetable', $clean_timetable);
				}
			}
		}
		
		// Campos opcionais adicionais
		if (isset($_POST['event_end_date']) && !empty($_POST['event_end_date'])) {
			$end_date = sanitize_text_field($_POST['event_end_date']);
			$end_time = sanitize_text_field($_POST['event_end_time'] ?? '00:00');
			$end_datetime = $end_date . ' ' . $end_time . ':00';
			apollo_update_post_meta($event_id, '_event_end_date', $end_datetime);
			apollo_update_post_meta($event_id, '_event_end_time', $end_time);
		}
		
		if (isset($_POST['event_video_url']) && !empty($_POST['event_video_url'])) {
			apollo_update_post_meta($event_id, '_event_video_url', esc_url_raw($_POST['event_video_url']));
		}
		
		if (isset($_POST['tickets_ext']) && !empty($_POST['tickets_ext'])) {
			apollo_update_post_meta($event_id, '_tickets_ext', esc_url_raw($_POST['tickets_ext']));
		}
		
		if (isset($_POST['cupom_ario']) && $_POST['cupom_ario'] == '1') {
			apollo_update_post_meta($event_id, '_cupom_ario', '1');
		}
		
		if (isset($_POST['event_location']) && !empty($_POST['event_location'])) {
			apollo_update_post_meta($event_id, '_event_location', sanitize_text_field($_POST['event_location']));
		}
	} else {
		// Fallback se apollo_update_post_meta não estiver disponível
		$start_datetime = $start_date . ' ' . $start_time . ':00';
		update_post_meta($event_id, '_event_start_date', $start_datetime);
		update_post_meta($event_id, '_event_start_time', $start_time);
		
		if (!empty($banner)) {
			update_post_meta($event_id, '_event_banner', $banner);
		}
		
		if (!empty($dj_ids_input)) {
			$dj_array = array_map('absint', explode(',', $dj_ids_input));
			$dj_array = array_filter($dj_array);
			if (!empty($dj_array)) {
				update_post_meta($event_id, '_event_dj_ids', $dj_array);
			}
		}
		
		if ($local_id > 0) {
			update_post_meta($event_id, '_event_local_ids', $local_id);
		}
	}
	
	// ✅ 7. Marcar como submissão do frontend
	update_post_meta($event_id, '_apollo_frontend_submission', '1');
	update_post_meta($event_id, '_apollo_submission_date', current_time('mysql'));
	
	// ✅ 8. Limpar cache de eventos
	if (function_exists('apollo_clear_events_cache')) {
		apollo_clear_events_cache($event_id);
	}
	
	// ✅ 9. Redirecionar com mensagem de sucesso
	$redirect_url = add_query_arg([
		'event_submitted' => '1',
		'event_id' => $event_id
	], wp_get_referer() ?: home_url());
	
	wp_redirect($redirect_url);
	exit;
}
?&gt;</code></pre>
					</div>
					<div style="margin-top: 15px; padding: 10px; background: #e7f3ff; border-left: 4px solid #2271b1; border-radius: 4px;">
						<p style="margin: 0;">
							<strong><?php echo esc_html__('Nota:', 'apollo-events-manager'); ?></strong>
							<?php echo esc_html__('Este código usa apollo_update_post_meta() que aplica sanitização automática. Se a função não estiver disponível, usa update_post_meta() padrão do WordPress.', 'apollo-events-manager'); ?>
						</p>
					</div>
				</div>
				
				<div style="margin-top: 30px;">
					<h3><?php echo esc_html__('4. Campos Adicionais Opcionais', 'apollo-events-manager'); ?></h3>
					<table class="widefat" style="margin-top: 10px;">
						<thead>
							<tr>
								<th style="padding: 10px;">Campo</th>
								<th style="padding: 10px;">Meta Key</th>
								<th style="padding: 10px;">Tipo</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>Data de Término</td>
								<td><code>_event_end_date</code></td>
								<td>datetime</td>
							</tr>
							<tr>
								<td>Hora de Término</td>
								<td><code>_event_end_time</code></td>
								<td>time</td>
							</tr>
							<tr>
								<td>URL do Vídeo</td>
								<td><code>_event_video_url</code></td>
								<td>url</td>
							</tr>
							<tr>
								<td>URL de Ingressos</td>
								<td><code>_tickets_ext</code></td>
								<td>url</td>
							</tr>
							<tr>
								<td>Cupom Apollo</td>
								<td><code>_cupom_ario</code></td>
								<td>text</td>
							</tr>
							<tr>
								<td>Localização (texto)</td>
								<td><code>_event_location</code></td>
								<td>text</td>
							</tr>
							<tr>
								<td>3 Imagens Promo</td>
								<td><code>_3_imagens_promo</code></td>
								<td>array (URLs)</td>
							</tr>
							<tr>
								<td>Imagem Final</td>
								<td><code>_imagem_final</code></td>
								<td>url</td>
							</tr>
						</tbody>
					</table>
				</div>
				
				<div style="margin-top: 30px;">
					<h3><?php echo esc_html__('5. Exemplo Completo com ShadCN/Tailwind', 'apollo-events-manager'); ?></h3>
					<p style="color: #50575e;">
						<?php echo esc_html__('Veja o template completo em:', 'apollo-events-manager'); ?>
						<code>templates/page-cenario-new-event.php</code>
					</p>
					<p style="margin-top: 10px;">
						<a href="<?php echo esc_url(admin_url('plugin-editor.php?file=apollo-events-manager/templates/page-cenario-new-event.php&plugin=apollo-events-manager/apollo-events-manager.php')); ?>" 
							target="_blank" 
							class="button">
							<span class="dashicons dashicons-editor-code" style="vertical-align: middle;"></span>
							<?php echo esc_html__('Ver Template Completo', 'apollo-events-manager'); ?>
						</a>
					</p>
				</div>
				
				<div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
					<h4 style="margin-top: 0;">
						<span class="dashicons dashicons-info" style="vertical-align: middle;"></span>
						<?php echo esc_html__('Importante:', 'apollo-events-manager'); ?>
					</h4>
					<ul style="margin: 10px 0; padding-left: 20px;">
						<li><?php echo esc_html__('Sempre salve eventos como DRAFT no formulário público', 'apollo-events-manager'); ?></li>
						<li><?php echo esc_html__('Use apollo_update_post_meta() para sanitização automática', 'apollo-events-manager'); ?></li>
						<li><?php echo esc_html__('Valide campos obrigatórios antes de salvar', 'apollo-events-manager'); ?></li>
						<li><?php echo esc_html__('Use nonces para segurança (CSRF protection)', 'apollo-events-manager'); ?></li>
						<li><?php echo esc_html__('Combine data e hora: YYYY-MM-DD HH:MM:SS', 'apollo-events-manager'); ?></li>
						<li><?php echo esc_html__('DJs devem ser array de IDs: [92, 71]', 'apollo-events-manager'); ?></li>
						<li><?php echo esc_html__('Local deve ser um único ID (integer)', 'apollo-events-manager'); ?></li>
					</ul>
				</div>
			</div>
			
			<!-- Shortcodes List -->
			<div class="apollo-shortcodes-list">
				<?php foreach ($shortcodes as $shortcode) : ?>
					<div class="apollo-shortcode-card" style="background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
						
						<!-- Shortcode Name -->
						<h2 style="margin-top: 0; margin-bottom: 10px; color: #2271b1;">
							<code style="background: #f0f0f1; padding: 4px 8px; border-radius: 3px; font-size: 16px;">
								[<?php echo esc_html($shortcode['tag']); ?>]
							</code>
						</h2>
						
						<!-- Description -->
						<p style="margin: 10px 0; color: #50575e; font-size: 14px;">
							<?php echo esc_html($shortcode['description']); ?>
						</p>
						
						<!-- Attributes -->
						<?php if (! empty($shortcode['attributes'])) : ?>
							<div style="margin: 15px 0;">
								<strong style="display: block; margin-bottom: 8px; color: #1d2327;">
									<?php echo esc_html__('Atributos disponíveis:', 'apollo-events-manager'); ?>
								</strong>
								<table class="widefat" style="margin-top: 10px;">
									<thead>
										<tr>
											<th style="padding: 10px;"><?php echo esc_html__('Atributo', 'apollo-events-manager'); ?></th>
											<th style="padding: 10px;"><?php echo esc_html__('Tipo', 'apollo-events-manager'); ?></th>
											<th style="padding: 10px;"><?php echo esc_html__('Padrão', 'apollo-events-manager'); ?></th>
											<th style="padding: 10px;"><?php echo esc_html__('Descrição', 'apollo-events-manager'); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($shortcode['attributes'] as $attr) : ?>
											<tr>
												<td style="padding: 10px;">
													<code style="background: #f0f0f1; padding: 2px 6px; border-radius: 2px;">
														<?php echo esc_html($attr['name']); ?>
													</code>
												</td>
												<td style="padding: 10px;">
													<span style="color: #2271b1;">
														<?php echo esc_html($attr['type']); ?>
													</span>
												</td>
												<td style="padding: 10px;">
													<?php if (! empty($attr['default'])) : ?>
														<code style="background: #f0f0f1; padding: 2px 6px; border-radius: 2px;">
															<?php echo esc_html($attr['default']); ?>
														</code>
													<?php else : ?>
														<span style="color: #999;">—</span>
													<?php endif; ?>
												</td>
												<td style="padding: 10px;">
													<?php echo esc_html($attr['description']); ?>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php endif; ?>
						
						<!-- Example -->
						<?php if (! empty($shortcode['example'])) : ?>
							<div style="margin: 15px 0;">
								<strong style="display: block; margin-bottom: 8px; color: #1d2327;">
									<?php echo esc_html__('Exemplo:', 'apollo-events-manager'); ?>
								</strong>
								<div style="background: #f0f0f1; padding: 12px; border-radius: 4px; border-left: 4px solid #2271b1;">
									<code style="font-size: 13px; color: #1d2327;">
										<?php echo esc_html($shortcode['example']); ?>
									</code>
								</div>
							</div>
						<?php endif; ?>
						
						<!-- Copy Button -->
						<div style="margin-top: 15px;">
							<button 
								type="button" 
								class="button button-primary apollo-copy-shortcode" 
								data-shortcode="<?php echo esc_attr($shortcode['example'] ?: '[' . $shortcode['tag'] . ']'); ?>"
								style="margin-right: 10px;"
							>
								<span class="dashicons dashicons-clipboard" style="vertical-align: middle;"></span>
								<?php echo esc_html__('Copiar Shortcode', 'apollo-events-manager'); ?>
							</button>
							
							<button 
								type="button" 
								class="button button-secondary apollo-create-canvas-page" 
								data-shortcode="<?php echo esc_attr($shortcode['tag']); ?>"
								data-shortcode-full="<?php echo esc_attr($shortcode['example'] ?: '[' . $shortcode['tag'] . ']'); ?>"
								data-slug="<?php echo esc_attr(apollo_events_get_shortcode_slug($shortcode['tag'])); ?>"
								style="margin-right: 10px;"
							>
								<span class="dashicons dashicons-admin-page" style="vertical-align: middle;"></span>
								<?php echo esc_html__('Criar Página Canvas', 'apollo-events-manager'); ?>
							</button>
							
							<?php if (! empty($shortcode['docs_url'])) : ?>
								<a 
									href="<?php echo esc_url($shortcode['docs_url']); ?>" 
									target="_blank"
									class="button"
								>
									<?php echo esc_html__('Documentação', 'apollo-events-manager'); ?>
								</a>
							<?php endif; ?>
						</div>
						
					</div>
				<?php endforeach; ?>
			</div>
			
		</div>
	</div>
	
	<script>
	jQuery(document).ready(function($) {
		// Copy shortcode
		$('.apollo-copy-shortcode').on('click', function() {
			var shortcode = $(this).data('shortcode');
			var $button = $(this);
			
			// Create temporary textarea
			var $temp = $('<textarea>');
			$('body').append($temp);
			$temp.val(shortcode).select();
			document.execCommand('copy');
			$temp.remove();
			
			// Show feedback
			var originalText = $button.html();
			$button.html('<span class="dashicons dashicons-yes" style="vertical-align: middle;"></span> Copiado!');
			$button.prop('disabled', true);
			
			setTimeout(function() {
				$button.html(originalText);
				$button.prop('disabled', false);
			}, 2000);
		});
		
		// Create main page (eventos, etc)
		$('.apollo-create-main-page').on('click', function() {
			var $button = $(this);
			var slug = $(this).data('page-slug');
			var title = $(this).data('page-title');
			var shortcode = $(this).data('shortcode');
			var template = $(this).data('template') || 'pagx_appclean';
			
			if (!confirm('Criar página "' + title + '" com slug /' + slug + '/?\n\nShortcode: ' + shortcode + '\nTemplate: ' + template)) {
				return;
			}
			
			// Disable button
			var originalText = $button.html();
			$button.html('<span class="dashicons dashicons-update" style="vertical-align: middle; animation: spin 1s linear infinite;"></span> Criando...');
			$button.prop('disabled', true);
			
			// AJAX request
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'apollo_create_canvas_page',
					shortcode_tag: 'main_page',
					shortcode_full: shortcode,
					slug: slug,
					page_title: title,
					template: template,
					publish: true, // Main pages should be published
					nonce: '<?php echo wp_create_nonce('apollo_create_canvas_page'); ?>'
				},
				success: function(response) {
					if (response.success) {
						$button.html('<span class="dashicons dashicons-yes" style="vertical-align: middle;"></span> Criada!');
						setTimeout(function() {
							if (response.data.edit_url) {
								window.location.href = response.data.edit_url;
							} else {
								location.reload();
							}
						}, 1000);
					} else {
						alert('Erro: ' + (response.data || 'Erro desconhecido'));
						$button.html(originalText);
						$button.prop('disabled', false);
					}
				},
				error: function() {
					alert('Erro ao criar página. Tente novamente.');
					$button.html(originalText);
					$button.prop('disabled', false);
				}
			});
		});
		
		// Create canvas page
		$('.apollo-create-canvas-page').on('click', function() {
			var $button = $(this);
			var shortcodeTag = $(this).data('shortcode');
			var shortcodeFull = $(this).data('shortcode-full');
			var slug = $(this).data('slug');
			
			if (!confirm('Criar página canvas em branco com o shortcode [' + shortcodeTag + ']?\n\nSlug: /' + slug + '/')) {
				return;
			}
			
			// Disable button
			var originalText = $button.html();
			$button.html('<span class="dashicons dashicons-update" style="vertical-align: middle; animation: spin 1s linear infinite;"></span> Criando...');
			$button.prop('disabled', true);
			
			// AJAX request
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'apollo_create_canvas_page',
					shortcode_tag: shortcodeTag,
					shortcode_full: shortcodeFull,
					slug: slug,
					nonce: '<?php echo wp_create_nonce('apollo_create_canvas_page'); ?>'
				},
				success: function(response) {
					if (response.success) {
						$button.html('<span class="dashicons dashicons-yes" style="vertical-align: middle;"></span> Criada!');
						setTimeout(function() {
							if (response.data.edit_url) {
								window.location.href = response.data.edit_url;
							} else {
								$button.html(originalText);
								$button.prop('disabled', false);
							}
						}, 1000);
					} else {
						alert('Erro: ' + (response.data || 'Erro desconhecido'));
						$button.html(originalText);
						$button.prop('disabled', false);
					}
				},
				error: function() {
					alert('Erro ao criar página. Tente novamente.');
					$button.html(originalText);
					$button.prop('disabled', false);
				}
			});
		});
	});
	
	// CSS for spinner animation
	var style = document.createElement('style');
	style.textContent = '@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
	document.head.appendChild(style);
	</script>
	
	<style>
	.apollo-shortcode-card:hover {
		box-shadow: 0 2px 6px rgba(0,0,0,0.15) !important;
		transition: box-shadow 0.2s;
	}
	.apollo-shortcode-card code {
		font-family: 'Courier New', monospace;
	}
	.apollo-create-canvas-page .dashicons-update {
		display: inline-block;
	}
	</style>
	
	<script>
	// Make ajaxurl available
	var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
	</script>
	<?php
}

/**
 * Get slug for shortcode page
 *
 * @param string $shortcode_tag Shortcode tag
 * @return string Page slug
 */
function apollo_events_get_shortcode_slug($shortcode_tag)
{
    $slug_map = [
        'events'                     => 'eventos',
        'apollo_event'               => 'evento',
        'apollo_event_user_overview' => 'meus-eventos',
        'event'                      => 'evento',
        'event_djs'                  => 'djs',
        'event_locals'               => 'locais',
        'event_summary'              => 'resumo-evento',
        'local_dashboard'            => 'dashboard-local',
        'past_events'                => 'eventos-passados',
        'single_event_dj'            => 'dj',
        'single_event_local'         => 'local',
        'apollo_event_card'          => 'card-evento',
        'apollo_event_map'           => 'mapa-evento',
        'apollo_event_lineup'        => 'lineup-evento',
        'apollo_dj_card'             => 'card-dj',
        'apollo_local_card'          => 'card-local',
        'apollo_events_filter'       => 'filtros-eventos',
        // New Tailwind shortcodes
        'apollo_dj_profile'     => 'dj-profile',
        'apollo_user_dashboard' => 'my-apollo',
        'apollo_cena_rio'       => 'cena-rio',
    ];

    return isset($slug_map[ $shortcode_tag ]) ? $slug_map[ $shortcode_tag ] : sanitize_title($shortcode_tag);
}

/**
 * AJAX handler to create canvas page
 */
function apollo_events_ajax_create_canvas_page()
{
    check_ajax_referer('apollo_create_canvas_page', 'nonce');

    if (! current_user_can('edit_pages')) {
        wp_send_json_error('Sem permissão');

        return;
    }

    // SECURITY: Sanitize all inputs with proper unslashing
    $shortcode_tag  = isset($_POST['shortcode_tag']) ? sanitize_text_field(wp_unslash($_POST['shortcode_tag'])) : '';
    $shortcode_full = isset($_POST['shortcode_full']) ? sanitize_text_field(wp_unslash($_POST['shortcode_full'])) : '';
    $slug           = isset($_POST['slug']) ? sanitize_title(wp_unslash($_POST['slug'])) : '';
    $page_title     = isset($_POST['page_title']) ? sanitize_text_field(wp_unslash($_POST['page_title'])) : '';
    $template       = isset($_POST['template']) ? sanitize_text_field(wp_unslash($_POST['template'])) : 'pagx_appclean';
    $publish_raw    = isset($_POST['publish']) ? sanitize_text_field(wp_unslash($_POST['publish'])) : 'false';
    $publish        = ($publish_raw === 'true');

    // SECURITY: Validate template against whitelist
    $valid_templates = [ 'pagx_appclean', 'pagx_app', 'pagx_site', 'default' ];
    if (! in_array($template, $valid_templates, true)) {
        $template = 'pagx_appclean';
    }

    if (empty($slug)) {
        wp_send_json_error('Slug é obrigatório');

        return;
    }

    // Check if page already exists
    $existing_page = get_page_by_path($slug);
    if ($existing_page) {
        wp_send_json_error('Página já existe: /' . $slug . '/');

        return;
    }

    // Get canvas template (check apollo-rio plugin)
    $canvas_template = $template;

    if (defined('APOLLO_PATH')) {
        // Validate template exists
        $valid_templates = [ 'pagx_appclean', 'pagx_app', 'pagx_site' ];
        if (! in_array($canvas_template, $valid_templates)) {
            $canvas_template = 'pagx_appclean';
            // Fallback
        }
    }

    // Use provided title or generate from slug
    $final_title = ! empty($page_title) ? $page_title : ucfirst(str_replace('-', ' ', $slug));

    // Create page
    $page_data = [
        'post_title'   => $final_title,
        'post_name'    => $slug,
        'post_content' => $shortcode_full,
        'post_status'  => $publish ? 'publish' : 'draft',
        'post_type'    => 'page',
        'post_author'  => get_current_user_id(),
    ];

    $page_id = wp_insert_post($page_data);

    if (is_wp_error($page_id)) {
        wp_send_json_error($page_id->get_error_message());

        return;
    }

    // Set canvas template
    if ($canvas_template) {
        update_post_meta($page_id, '_wp_page_template', $canvas_template);
    }

    // Add meta to identify as Apollo canvas page
    update_post_meta($page_id, '_apollo_canvas_page', '1');
    update_post_meta($page_id, '_apollo_shortcode', $shortcode_tag);

    wp_send_json_success(
        [
            'page_id'  => $page_id,
            'edit_url' => get_edit_post_link($page_id, 'raw'),
            'view_url' => get_permalink($page_id),
            'slug'     => $slug,
        ]
    );
}
add_action('wp_ajax_apollo_create_canvas_page', 'apollo_events_ajax_create_canvas_page');

/**
 * Get all registered shortcodes
 *
 * @return array List of shortcodes with metadata
 *
 * NOTE: This function is also defined in admin-apollo-hub.php
 * Using function_exists() to prevent redeclaration errors
 */
if (! function_exists('apollo_events_get_all_shortcodes')) {
    function apollo_events_get_all_shortcodes()
    {
        return [
            [
                'tag'         => 'events',
                'description' => 'Exibe uma lista de eventos com filtros e layout responsivo. Suporta filtros por categoria, local, data e busca. Este é o shortcode principal para exibir eventos.',
                'attributes'  => [
                    [
                        'name'        => 'limit',
                        'type'        => 'integer',
                        'default'     => '50',
                        'description' => 'Número máximo de eventos a exibir',
                    ],
                    [
                        'name'        => 'category',
                        'type'        => 'string',
                        'default'     => '',
                        'description' => 'Slug da categoria de eventos (filtro)',
                    ],
                    [
                        'name'        => 'local',
                        'type'        => 'string',
                        'default'     => '',
                        'description' => 'Slug do local (filtro)',
                    ],
                    [
                        'name'        => 'month',
                        'type'        => 'string',
                        'default'     => '',
                        'description' => 'Mês no formato YYYY-MM (ex: 2025-11)',
                    ],
                    [
                        'name'        => 'layout',
                        'type'        => 'string',
                        'default'     => 'grid',
                        'description' => 'Layout: "grid" ou "list"',
                    ],
                    [
                        'name'        => 'show_filters',
                        'type'        => 'boolean',
                        'default'     => 'true',
                        'description' => 'Exibir filtros (true/false)',
                    ],
                ],
                'example'  => '[events limit="20" category="techno" layout="grid"]',
                'docs_url' => '',
            ],
            [
                'tag'         => 'apollo_event',
                'description' => 'Exibe informações resumidas de um evento específico. Útil para destacar um evento em páginas customizadas.',
                'attributes'  => [
                    [
                        'name'        => 'id',
                        'type'        => 'integer',
                        'default'     => '',
                        'description' => 'ID do evento (obrigatório)',
                    ],
                ],
                'example'  => '[apollo_event id="123"]',
                'docs_url' => '',
            ],
            [
                'tag'         => 'apollo_event_user_overview',
                'description' => 'Exibe uma visão geral dos eventos do usuário logado. Mostra eventos criados, favoritos e estatísticas.',
                'attributes'  => [],
                'example'     => '[apollo_event_user_overview]',
                'docs_url'    => '',
            ],
            [
                'tag'         => 'event',
                'description' => 'Exibe o conteúdo completo de um evento em formato de lightbox/modal. Ideal para popups e modais.',
                'attributes'  => [
                    [
                        'name'        => 'id',
                        'type'        => 'integer',
                        'default'     => '',
                        'description' => 'ID do evento (obrigatório)',
                    ],
                ],
                'example'  => '[event id="123"]',
                'docs_url' => '',
            ],
            [
                'tag'         => 'event_djs',
                'description' => 'Exibe a lista de DJs de um evento específico com informações e links.',
                'attributes'  => [
                    [
                        'name'        => 'event_id',
                        'type'        => 'integer',
                        'default'     => '',
                        'description' => 'ID do evento (obrigatório)',
                    ],
                ],
                'example'  => '[event_djs event_id="123"]',
                'docs_url' => '',
            ],
            [
                'tag'         => 'event_locals',
                'description' => 'Exibe informações sobre o local de um evento, incluindo endereço e mapa.',
                'attributes'  => [
                    [
                        'name'        => 'event_id',
                        'type'        => 'integer',
                        'default'     => '',
                        'description' => 'ID do evento (obrigatório)',
                    ],
                ],
                'example'  => '[event_locals event_id="123"]',
                'docs_url' => '',
            ],
            [
                'tag'         => 'event_summary',
                'description' => 'Exibe um resumo compacto de um evento com informações essenciais.',
                'attributes'  => [
                    [
                        'name'        => 'event_id',
                        'type'        => 'integer',
                        'default'     => '',
                        'description' => 'ID do evento (obrigatório)',
                    ],
                ],
                'example'  => '[event_summary event_id="123"]',
                'docs_url' => '',
            ],
            [
                'tag'         => 'local_dashboard',
                'description' => 'Exibe um dashboard com informações e eventos de um local específico.',
                'attributes'  => [
                    [
                        'name'        => 'local_id',
                        'type'        => 'integer',
                        'default'     => '',
                        'description' => 'ID do local (obrigatório)',
                    ],
                ],
                'example'  => '[local_dashboard local_id="95"]',
                'docs_url' => '',
            ],
            [
                'tag'         => 'past_events',
                'description' => 'Exibe uma lista de eventos passados. Útil para histórico e arquivo.',
                'attributes'  => [
                    [
                        'name'        => 'limit',
                        'type'        => 'integer',
                        'default'     => '10',
                        'description' => 'Número máximo de eventos a exibir',
                    ],
                ],
                'example'  => '[past_events limit="20"]',
                'docs_url' => '',
            ],
            [
                'tag'         => 'single_event_dj',
                'description' => 'Exibe o perfil completo de um DJ com eventos futuros e informações.',
                'attributes'  => [
                    [
                        'name'        => 'dj_id',
                        'type'        => 'integer',
                        'default'     => '',
                        'description' => 'ID do DJ (obrigatório)',
                    ],
                ],
                'example'  => '[single_event_dj dj_id="92"]',
                'docs_url' => '',
            ],
            [
                'tag'         => 'single_event_local',
                'description' => 'Exibe informações completas de um local com eventos futuros e mapa.',
                'attributes'  => [
                    [
                        'name'        => 'local_id',
                        'type'        => 'integer',
                        'default'     => '',
                        'description' => 'ID do local (obrigatório)',
                    ],
                ],
                'example'  => '[single_event_local local_id="95"]',
                'docs_url' => '',
            ],
            [
                'tag'         => 'apollo_event_card',
                'description' => 'Exibe um card individual de evento. Útil para destacar um evento específico.',
                'attributes'  => [
                    [
                        'name'        => 'id',
                        'type'        => 'integer',
                        'default'     => '',
                        'description' => 'ID do evento (obrigatório)',
                    ],
                    [
                        'name'        => 'show_dj',
                        'type'        => 'boolean',
                        'default'     => 'true',
                        'description' => 'Exibir DJs (true/false)',
                    ],
                    [
                        'name'        => 'show_local',
                        'type'        => 'boolean',
                        'default'     => 'true',
                        'description' => 'Exibir local (true/false)',
                    ],
                ],
                'example'  => '[apollo_event_card id="123"]',
                'docs_url' => '',
            ],
            [
                'tag'         => 'apollo_event_map',
                'description' => 'Exibe o mapa do local de um evento específico usando Leaflet.js.',
                'attributes'  => [
                    [
                        'name'        => 'event_id',
                        'type'        => 'integer',
                        'default'     => '',
                        'description' => 'ID do evento (obrigatório)',
                    ],
                    [
                        'name'        => 'height',
                        'type'        => 'string',
                        'default'     => '400px',
                        'description' => 'Altura do mapa (ex: 400px, 50vh)',
                    ],
                    [
                        'name'        => 'zoom',
                        'type'        => 'integer',
                        'default'     => '15',
                        'description' => 'Nível de zoom (1-18)',
                    ],
                ],
                'example'  => '[apollo_event_map event_id="123" height="500px" zoom="16"]',
                'docs_url' => '',
            ],
            [
                'tag'         => 'apollo_event_lineup',
                'description' => 'Exibe a programação (line-up) de um evento com horários dos DJs.',
                'attributes'  => [
                    [
                        'name'        => 'event_id',
                        'type'        => 'integer',
                        'default'     => '',
                        'description' => 'ID do evento (obrigatório)',
                    ],
                    [
                        'name'        => 'format',
                        'type'        => 'string',
                        'default'     => 'list',
                        'description' => 'Formato: "list" ou "timeline"',
                    ],
                ],
                'example'  => '[apollo_event_lineup event_id="123" format="timeline"]',
                'docs_url' => '',
            ],
            [
                'tag'         => 'apollo_dj_card',
                'description' => 'Exibe um card de DJ com informações e eventos futuros.',
                'attributes'  => [
                    [
                        'name'        => 'id',
                        'type'        => 'integer',
                        'default'     => '',
                        'description' => 'ID do DJ (obrigatório)',
                    ],
                    [
                        'name'        => 'show_upcoming',
                        'type'        => 'boolean',
                        'default'     => 'true',
                        'description' => 'Exibir eventos futuros (true/false)',
                    ],
                ],
                'example'  => '[apollo_dj_card id="92" show_upcoming="true"]',
                'docs_url' => '',
            ],
            [
                'tag'         => 'apollo_local_card',
                'description' => 'Exibe um card de local com informações e mapa.',
                'attributes'  => [
                    [
                        'name'        => 'id',
                        'type'        => 'integer',
                        'default'     => '',
                        'description' => 'ID do local (obrigatório)',
                    ],
                    [
                        'name'        => 'show_map',
                        'type'        => 'boolean',
                        'default'     => 'true',
                        'description' => 'Exibir mapa (true/false)',
                    ],
                ],
                'example'  => '[apollo_local_card id="95" show_map="true"]',
                'docs_url' => '',
            ],
            [
                'tag'         => 'apollo_events_filter',
                'description' => 'Exibe apenas os filtros de eventos (sem lista). Útil para páginas customizadas.',
                'attributes'  => [
                    [
                        'name'        => 'style',
                        'type'        => 'string',
                        'default'     => 'default',
                        'description' => 'Estilo: "default" ou "compact"',
                    ],
                ],
                'example'  => '[apollo_events_filter style="compact"]',
                'docs_url' => '',
            ],
            // New Tailwind-based shortcodes
            [
                'tag'         => 'apollo_dj_profile',
                'description' => 'Exibe o perfil completo de um DJ com player SoundCloud, bio, links de música e redes sociais. Template Tailwind moderno com animações.',
                'attributes'  => [
                    [
                        'name'        => 'dj_id',
                        'type'        => 'integer',
                        'default'     => '0',
                        'description' => 'ID do DJ (opcional, usa post atual se não fornecido)',
                    ],
                ],
                'example'  => '[apollo_dj_profile dj_id="92"]',
                'docs_url' => '',
            ],
            [
                'tag'         => 'apollo_user_dashboard',
                'description' => 'Dashboard privado do usuário logado com perfil, estatísticas, eventos favoritos, métricas e tabs. Requer login.',
                'attributes'  => [],
                'example'     => '[apollo_user_dashboard]',
                'docs_url'    => '',
            ],
            [
                'tag'         => 'apollo_cena_rio',
                'description' => 'Calendário mensal interativo da cena carioca com eventos marcados por data, navegação entre meses e status (Confirmado/Previsto).',
                'attributes'  => [],
                'example'     => '[apollo_cena_rio]',
                'docs_url'    => '',
            ],
        ];
    }
} //end if
