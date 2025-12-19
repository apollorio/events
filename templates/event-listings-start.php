<?php
// phpcs:ignoreFile
/**
 * Event Listings Start Wrapper
 * MATCHES ORIGINAL TEMPLATE STRUCTURE
 */

// Get taxonomies
$sounds_terms = get_terms(
    [
        'taxonomy'   => 'event_sounds',
        'hide_empty' => false,
    ]
);
$sounds_terms = is_wp_error($sounds_terms) ? [] : $sounds_terms;
?>

<div class="discover-events-now-shortcode event-manager-shortcode-wrapper">
	<!-- Hero Section -->
	<section class="hero-section ap-section" 
			data-ap-tooltip="<?php esc_attr_e('Seção principal do portal de eventos', 'apollo-events-manager'); ?>">
		<h1 class="title-page ap-h1" 
			data-ap-tooltip="<?php esc_attr_e('Título da página', 'apollo-events-manager'); ?>">
			<?php esc_html_e('Descubra os Próximos Eventos', 'apollo-events-manager'); ?>
		</h1>
		<p class="subtitle-page ap-text-secondary"
			data-ap-tooltip="<?php esc_attr_e('Descrição do portal Apollo', 'apollo-events-manager'); ?>">
			Um novo <mark>hub digital que conecta cultura,</mark> tecnologia e experiências em tempo real... 
			<mark>O futuro da cultura carioca começa aqui!</mark>
		</p>
	</section>

	<!-- Filters and Search -->
	<div class="filters-and-search ap-flex ap-items-center ap-gap-3"
		data-ap-tooltip="<?php esc_attr_e('Filtros de eventos', 'apollo-events-manager'); ?>">
		<div class="event_types menutags ap-flex ap-gap-2">
			<button class="event-category menutag ap-btn ap-btn-ghost active" 
					data-slug="all"
					data-ap-tooltip="<?php esc_attr_e('Mostrar todos os eventos', 'apollo-events-manager'); ?>">
				<span class="xxall" id="xxall" style="opacity:1"><?php esc_html_e('Todos', 'apollo-events-manager'); ?></span>
			</button>
			
			<?php
            // Get first 4 sounds for filter buttons
            $max_buttons = 4;
$count                   = 0;
foreach ($sounds_terms as $sound) {
    if ($count >= $max_buttons) {
        break;
    }
    printf(
        '<button class="event-category menutag ap-btn ap-btn-ghost" data-slug="%s" data-ap-tooltip="%s">%s</button>',
        esc_attr($sound->slug),
        esc_attr(sprintf(__('Filtrar por %s', 'apollo-events-manager'), $sound->name)),
        esc_html($sound->name)
    );
    ++$count;
}
?>
			
			<!-- Date Picker -->
			<div class="date-chip ap-flex ap-items-center ap-gap-1" 
				id="eventDatePicker"
				data-ap-tooltip="<?php esc_attr_e('Navegue entre os meses', 'apollo-events-manager'); ?>">
				<button class="date-arrow ap-btn ap-btn-icon ap-btn-sm ap-btn-ghost" 
						id="datePrev" 
						type="button" 
						aria-label="<?php esc_attr_e('Mês anterior', 'apollo-events-manager'); ?>"
						data-ap-tooltip="<?php esc_attr_e('Ver mês anterior', 'apollo-events-manager'); ?>">‹</button>
				<span class="date-display ap-font-semibold" id="dateDisplay" aria-live="polite">
					<?php echo date_i18n('M'); ?>
				</span>
				<button class="date-arrow ap-btn ap-btn-icon ap-btn-sm ap-btn-ghost" 
						id="dateNext" 
						type="button" 
						aria-label="<?php esc_attr_e('Próximo mês', 'apollo-events-manager'); ?>"
						data-ap-tooltip="<?php esc_attr_e('Ver próximo mês', 'apollo-events-manager'); ?>">›</button>
			</div>
			
			<!-- Layout Toggle -->
			<button class="layout-toggle ap-btn ap-btn-icon ap-btn-ghost" 
					id="aprio-event-toggle-layout" 
					type="button" 
					aria-pressed="true" 
					data-layout="list"
					data-ap-tooltip="<?php esc_attr_e('Alternar entre visualização em grade e lista', 'apollo-events-manager'); ?>">
				<i class="ri-list-check-2" aria-hidden="true"></i>
				<span class="visually-hidden"><?php esc_html_e('Alternar layout', 'apollo-events-manager'); ?></span>
			</button>
		</div>
		
		<!-- Search Bar -->
		<div class="controls-bar ap-flex ap-items-center" 
			id="apollo-controls-bar"
			data-ap-tooltip="<?php esc_attr_e('Barra de busca', 'apollo-events-manager'); ?>">
			<form class="box-search ap-flex ap-items-center ap-gap-2" id="eventSearchForm" role="search">
				<label class="visually-hidden" for="eventSearchInput"><?php esc_html_e('Procurar', 'apollo-events-manager'); ?></label>
				<i class="ri-search-line" aria-hidden="true"></i>
				<input name="search_keywords" 
						autocomplete="off" 
						id="eventSearchInput" 
						inputmode="search" 
						placeholder=""
						class="ap-form-input"
						data-ap-tooltip="<?php esc_attr_e('Digite para buscar eventos por nome, DJ ou local', 'apollo-events-manager'); ?>">
				<input name="post_type" type="hidden" value="event_listing">
			</form>
		</div>
	</div>
	
	<!-- Event Listings Container -->
	<div class="event_listings ap-event-grid" 
		data-ap-tooltip="<?php esc_attr_e('Lista de eventos - use os filtros acima para refinar', 'apollo-events-manager'); ?>">
		<?php
        // FASE 1: Query centralizada com validações rigorosas
        require_once plugin_dir_path(__FILE__) . '../includes/helpers/event-data-helper.php';

$event_ids = Apollo_Event_Data_Helper::get_cached_event_ids(true);

if (empty($event_ids)) {
    echo '<p class="no-events-found">Nenhum evento encontrado.</p>';
} else {
    // Carregar posts completos mantendo ordem do cache
    $events = get_posts(
        [
            'post_type'   => 'event_listing',
            'post_status' => 'publish',
            // SEMPRE apenas publicados
                                                'post__in' => $event_ids,
            'orderby'                                      => 'post__in',
            // Manter ordem do cache (já ordenada por data)
                                                'posts_per_page' => count($event_ids),
            'update_post_meta_cache'                             => true,
            'update_post_term_cache'                             => true,
            'no_found_rows'                                      => true,
        ]
    );

    if (! empty($events)) :
        foreach ($events as $post) :
            setup_postdata($post);
            $event_id = $post->ID;

            // FASE 1: Resetar variáveis para garantir desacoplamento completo
            $start_date = null;
            $date_info  = null;

            // Buscar data SEMPRE da meta correta (nunca usar post_date)
            $start_date = apollo_get_post_meta($event_id, '_event_start_date', true);
            $date_info  = Apollo_Event_Data_Helper::parse_event_date($start_date);

            // Check layout preference (grid or list)
            $layout_mode = 'grid';
            // Default
            if (isset($_COOKIE['apollo_events_layout'])) {
                $layout_mode = sanitize_text_field($_COOKIE['apollo_events_layout']);
            }

            // Use appropriate template based on layout
            if ($layout_mode === 'list') {
                // List view template
                $list_view_path = defined('APOLLO_APRIO_PATH')
                ? APOLLO_APRIO_PATH . 'templates/event-list-view.php'
                : plugin_dir_path(__FILE__) . 'event-list-view.php';

                if (file_exists($list_view_path)) {
                    include $list_view_path;
                } else {
                    // Fallback to card view if list template doesn't exist
                    $event_card_path = defined('APOLLO_APRIO_PATH')
                    ? APOLLO_APRIO_PATH . 'templates/event-card.php'
                    : plugin_dir_path(__FILE__) . 'event-card.php';
                    if (file_exists($event_card_path)) {
                        include $event_card_path;
                    }
                }
            } else {
                // Grid view template (default)
                $event_card_path = defined('APOLLO_APRIO_PATH')
                ? APOLLO_APRIO_PATH . 'templates/event-card.php'
                : plugin_dir_path(__FILE__) . 'event-card.php';

                if (file_exists($event_card_path)) {
                    include $event_card_path;
                } else {
                    echo '<!-- ERROR: event-card.php template not found -->';
                }
            }//end if
        endforeach;
    wp_reset_postdata();
    else :
        echo '<p class="no-events-found">Nenhum evento encontrado.</p>';
    endif;
}//end if
?>