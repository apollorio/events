<?php
// phpcs:ignoreFile
/**
 * FILE: apollo-events-manager/templates/portal-discover.php
 * Optimized Events Discovery Portal
 * - Removed 200+ lines of duplicated DJ/Local logic (now in Helper)
 * - Better caching strategy with proper TTL
 * - Consolidated meta prefetching
 * - Proper error boundaries
 */

defined('ABSPATH') || exit;

// Load helper (only place that does)
require_once plugin_dir_path(__FILE__) . '../includes/helpers/event-data-helper.php';

// ============================================================
// STEP 1: Disable WordPress cruft
// ============================================================
add_filter('show_admin_bar', '__return_false', 999);
remove_action('wp_head', '_admin_bar_bump_cb');

add_action(
    'wp_enqueue_scripts',
    function () {
        global $wp_styles, $wp_scripts;

        $allowed_styles = [
            'remixicon',
            'apollo-shadcn-components',
            'apollo-event-modal-css',
            'leaflet-css',
            'apollo-infinite-scroll-css',
            'apollo-uni-css',
            'apollo-social-pwa',
        ];
        $allowed_scripts = [
            'jquery-core',
            'jquery-migrate',
            'apollo-loading-animation',
            'apollo-base-js',
            'leaflet',
            'apollo-events-portal',
            'apollo-motion-event-card',
            'apollo-motion-modal',
            'apollo-infinite-scroll',
            'apollo-motion-dashboard',
            'apollo-motion-context-menu',
            'apollo-character-counter',
            'apollo-form-validation',
            'apollo-image-modal',
            'apollo-events-favorites',
            'framer-motion',
            'apollo-social-pwa',
        ];

        if (is_object($wp_styles)) {
            foreach ($wp_styles->queue as $handle) {
                if (! in_array($handle, $allowed_styles, true)) {
                    wp_dequeue_style($handle);
                    wp_deregister_style($handle);
                }
            }
        }

        if (is_object($wp_scripts)) {
            foreach ($wp_scripts->queue as $handle) {
                if (! in_array($handle, $allowed_scripts, true)) {
                    wp_dequeue_script($handle);
                    wp_deregister_script($handle);
                }
            }
        }
    },
    999
);

array_map(
    function ($action) {
        remove_action('wp_head', $action);
    },
    [
        'wp_generator',
        'wlwmanifest_link',
        'rsd_link',
        'wp_shortlink_wp_head',
        'adjacent_posts_rel_link_wp_head',
        'feed_links',
        'feed_links_extra',
        'rest_output_link_wp_head',
        'wp_oembed_add_discovery_links',
        'print_emoji_detection_script',
    ]
);
remove_action('wp_print_styles', 'print_emoji_styles');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="format-detection" content="telephone=no">
	<title>Discover Events - Apollo::rio</title>
	<link rel="icon" href="https://assets.apollo.rio.br/img/neon-green.webp" type="image/webp">
	<link href="https://assets.apollo.rio.br/uni.css?ver=<?php echo date('Y-m'); ?>" rel="stylesheet">
	<?php wp_head(); ?>
</head>
<body class="apollo-canvas-mode">

<!-- FIXED HEADER -->
<header class="site-header">
	<div class="menu-h-apollo-blur"></div>
	<a href="<?php echo home_url('/'); ?>" class="menu-apollo-logo"></a>
	<nav class="main-nav">
		<a class="a-hover off"><span id="agoraH"><?php echo esc_html(apollo_get_placeholder('APOLLO_PLACEHOLDER_CURRENT_TIME')); ?></span> RJ</a>
		<a href="<?php echo home_url('/eventos/'); ?>" class="ario-eve" title="Portal de Eventos">
			Eventos<i class="ri-arrow-right-up-line"></i>
		</a>
		<div class="menu-h-lista">
			<?php
            if (is_user_logged_in()) :
                $user = wp_get_current_user();
                ?>
				<button class="menu-h-apollo-button caption" id="userMenuTrigger">
					<?php echo esc_html($user->display_name); ?>
				</button>
				<div class="list">
					<div class="item ok"><i class="ri-global-line"></i> Explorer</div>
					<hr>
					<div class="item ok"><i class="ri-fingerprint-2-fill"></i> My Apollo</div>
					<div class="item ok"><a href="<?php echo wp_logout_url(home_url()); ?>"><i class="ri-logout-box-r-line"></i> Logout</a></div>
				</div>
			<?php else : ?>
				<button class="menu-h-apollo-button caption" id="userMenuTrigger">Login</button>
				<div class="list">
					<div class="item ok"><i class="ri-global-line"></i> Explorer</div>
					<hr>
					<div class="item ok"><i class="ri-fingerprint-2-fill"></i> My Apollo</div>
				</div>
			<?php endif; ?>
		</div>
	</nav>
</header>

<main class="main-container">
	<div class="event-manager-shortcode-wrapper discover-events-now-shortcode">
		<!-- HERO -->
		<section class="hero-section">
			<h1 class="title-page">Descubra os Próximos Eventos</h1>
			<p class="subtitle-page">Um novo <mark>hub digital que conecta cultura,</mark> tecnologia e experiências em tempo real... <mark>O futuro da cultura carioca começa aqui!</mark></p>
		</section>

		<!-- FASE 3: FILTERS -->
		<div class="filters-and-search">
			<!-- FASE 3: Filtros por Período -->
			<div class="apollo-period-filters" role="group" aria-label="Filtros por período">
				<?php
                $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'all';
$periods                = [
    'all'       => 'Todos',
    'today'     => 'Hoje',
    'weekend'   => 'Este FDS',
    'next7days' => 'Próximos 7 dias',
];
foreach ($periods as $slug => $label) :
    $is_active = $period === $slug;
    $url       = add_query_arg('period', $slug, remove_query_arg([ 'local', 'style' ]));
    ?>
					<a href="<?php echo esc_url($url); ?>" 
						class="apollo-period-filter <?php echo $is_active ? 'active' : ''; ?>"
						data-period="<?php echo esc_attr($slug); ?>">
						<?php echo esc_html($label); ?>
					</a>
				<?php endforeach; ?>
			</div>
			
			<div class="menutags event_types" role="group" aria-label="Filtros de eventos">
				<button type="button" class="menutag event-category active" data-slug="all" aria-pressed="true">
					<span class="xxall">Todos</span>
				</button>
				<?php
                // Categories
                $cats = get_terms(
                    [
                        'taxonomy'   => 'event_listing_category',
                        'hide_empty' => false,
                    ]
                );
if (! is_wp_error($cats)) {
    foreach ($cats as $cat) {
        printf(
            '<button type="button" class="menutag event-category" data-slug="%s">%s</button>',
            esc_attr($cat->slug),
            esc_html($cat->name)
        );
    }
}

// FASE 3: Estilos de som
$sounds = get_terms(
    [
        'taxonomy'   => 'event_sounds',
        'hide_empty' => false,
    ]
);
if (! is_wp_error($sounds)) {
    foreach (array_slice($sounds, 0, 5) as $sound) {
        $is_active = isset($_GET['style']) && $_GET['style'] === $sound->slug;
        $url       = add_query_arg('style', $sound->slug, remove_query_arg('period'));
        printf(
            '<a href="%s" class="menutag event-category %s" data-slug="%s">%s</a>',
            esc_url($url),
            $is_active ? 'active' : '',
            esc_attr($sound->slug),
            esc_html($sound->name)
        );
    }
}

// Locals
$locals = get_posts(
    [
        'post_type'      => 'event_local',
        'posts_per_page' => 5,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]
);
foreach ($locals as $local) {
    $name      = apollo_get_post_meta($local->ID, '_local_name', true) ?: $local->post_title;
    $is_active = isset($_GET['local']) && $_GET['local'] === $local->post_name;
    $url       = add_query_arg('local', $local->post_name, remove_query_arg([ 'period', 'style' ]));
    printf(
        '<a href="%s" class="menutag event-category event-local-filter %s" data-slug="%s" data-filter-type="local">%s</a>',
        esc_url($url),
        $is_active ? 'active' : '',
        esc_attr($local->post_name),
        esc_html($name)
    );
}
?>
				
				<!-- DATE PICKER -->
				<div class="date-chip" id="eventDatePicker">
					<button type="button" class="date-arrow" id="datePrev" aria-label="Mês anterior">‹</button>
					<span class="date-display" id="dateDisplay" aria-live="polite"><?php echo date_i18n('M'); ?></span>
					<button type="button" class="date-arrow" id="dateNext" aria-label="Próximo mês">›</button>
				</div>
				
				<!-- LAYOUT TOGGLE -->
				<button type="button" class="layout-toggle" id="aprio-event-toggle-layout" 
						title="Alternar layout" aria-pressed="false" data-layout="card">
					<i class="ri-building-3-fill"></i>
					<span class="visually-hidden">Alternar layout</span>
				</button>
			</div>
		</div>

		<!-- SEARCH -->
		<div class="controls-bar" id="apollo-controls-bar">
			<form class="box-search" role="search" id="eventSearchForm">
				<label for="eventSearchInput" class="visually-hidden">Procurar</label>
				<i class="ri-search-line"></i>
				<input type="text" name="search_keywords" id="eventSearchInput" 
						placeholder="" inputmode="search" autocomplete="off">
				<input type="hidden" name="post_type" value="event_listing">
			</form>
		</div>

		<p class="afasta-2b"></p>

		<?php
        // FASE 3: Obter eventos base
        $event_ids = Apollo_Event_Data_Helper::get_cached_event_ids(true);

// FASE 3: Aplicar filtros
$period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'all';
if ($period !== 'all') {
    $event_ids = Apollo_Event_Data_Helper::filter_events_by_period($event_ids, $period);
}

$local_filter = isset($_GET['local']) ? sanitize_text_field($_GET['local']) : '';
$style_filter = isset($_GET['style']) ? sanitize_text_field($_GET['style']) : '';

// FASE 3: Seções especiais (apenas se não houver filtros ativos)
$show_sections = empty($local_filter) && empty($style_filter) && $period === 'all';

if ($show_sections) :
    // Seção: Recomendados
    $featured_ids = Apollo_Event_Data_Helper::get_featured_events($event_ids);
    if (! empty($featured_ids)) :
        ?>
		<section class="apollo-events-section apollo-events-section--featured">
			<h2 class="apollo-section-title">
				<i class="ri-star-fill"></i>
				Recomendados
			</h2>
			<div class="apollo-events-grid">
				<?php
        $featured_events = get_posts(
            [
                'post_type'              => 'event_listing',
                'post_status'            => 'publish',
                'post__in'               => array_slice($featured_ids, 0, 6),
                'orderby'                => 'post__in',
                'posts_per_page'         => 6,
                'update_post_meta_cache' => true,
                'update_post_term_cache' => true,
            ]
        );
        foreach ($featured_events as $post) {
            setup_postdata($post);
            // Bug fix: Configurar contexto global para get_the_ID() funcionar
            include plugin_dir_path(__FILE__) . 'event-card.php';
        }
        wp_reset_postdata();
        ?>
			</div>
		</section>
				<?php
    endif;

    // Seção: Hoje
    $today_ids = Apollo_Event_Data_Helper::filter_events_by_period($event_ids, 'today');
    if (! empty($today_ids)) :
        ?>
		<section class="apollo-events-section apollo-events-section--today">
			<h2 class="apollo-section-title">
				<i class="ri-calendar-check-line"></i>
				Hoje
			</h2>
			<div class="apollo-events-grid">
				<?php
        $today_events = get_posts(
            [
                'post_type'              => 'event_listing',
                'post_status'            => 'publish',
                'post__in'               => $today_ids,
                'orderby'                => 'post__in',
                'posts_per_page'         => 6,
                'update_post_meta_cache' => true,
                'update_post_term_cache' => true,
            ]
        );
        foreach ($today_events as $post) {
            setup_postdata($post);
            // Bug fix: Configurar contexto global para get_the_ID() funcionar
            include plugin_dir_path(__FILE__) . 'event-card.php';
        }
        wp_reset_postdata();
        ?>
			</div>
		</section>
				<?php
    endif;

    // Seção: Este FDS
    $weekend_ids = Apollo_Event_Data_Helper::filter_events_by_period($event_ids, 'weekend');
    if (! empty($weekend_ids)) :
        ?>
		<section class="apollo-events-section apollo-events-section--weekend">
			<h2 class="apollo-section-title">
				<i class="ri-calendar-event-line"></i>
				Este Fim de Semana
			</h2>
			<div class="apollo-events-grid">
				<?php
        $weekend_events = get_posts(
            [
                'post_type'              => 'event_listing',
                'post_status'            => 'publish',
                'post__in'               => array_slice($weekend_ids, 0, 6),
                'orderby'                => 'post__in',
                'posts_per_page'         => 6,
                'update_post_meta_cache' => true,
                'update_post_term_cache' => true,
            ]
        );
        foreach ($weekend_events as $post) {
            setup_postdata($post);
            // Bug fix: Configurar contexto global para get_the_ID() funcionar
            include plugin_dir_path(__FILE__) . 'event-card.php';
        }
        wp_reset_postdata();
        ?>
			</div>
		</section>
				<?php
    endif;
endif;
?>
		
		<!-- FASE 3: EVENT GRID - Todos os eventos (ou filtrados) -->
		<section class="apollo-events-section apollo-events-section--all">
			<?php if ($show_sections) : ?>
			<h2 class="apollo-section-title">
				<i class="ri-calendar-line"></i>
				Todos os Eventos
			</h2>
			<?php endif; ?>
			
			<div class="apollo-events-grid event_listings card-view">
				<?php
        if (empty($event_ids)) {
            echo '<div class="no-events-found" role="alert"><i class="ri-calendar-event-line"></i><p>Nenhum evento encontrado.</p></div>';
        } else {
            // Aplicar filtros adicionais
            $filtered_ids = $event_ids;

            if ($local_filter) {
                $filtered_ids = array_filter(
                    $filtered_ids,
                    function ($id) use ($local_filter) {
                        $local = Apollo_Event_Data_Helper::get_local_data($id);

                        return $local && $local['slug'] === $local_filter;
                    }
                );
            }

            if ($style_filter) {
                $filtered_ids = array_filter(
                    $filtered_ids,
                    function ($id) use ($style_filter) {
                        $tags = wp_get_post_terms($id, 'event_sounds');
                        if (is_wp_error($tags)) {
                            return false;
                        }
                        foreach ($tags as $tag) {
                            if ($tag->slug === $style_filter) {
                                return true;
                            }
                        }

                        return false;
                    }
                );
            }

            if (empty($filtered_ids)) {
                echo '<div class="no-events-found" role="alert"><i class="ri-calendar-event-line"></i><p>Nenhum evento encontrado com os filtros selecionados.</p></div>';
            } else {
                $events = get_posts(
                    [
                        'post_type'              => 'event_listing',
                        'post_status'            => 'publish',
                        'post__in'               => $filtered_ids,
                        'orderby'                => 'post__in',
                        'posts_per_page'         => count($filtered_ids),
                        'update_post_meta_cache' => true,
                        'update_post_term_cache' => true,
                        'no_found_rows'          => true,
                    ]
                );

                update_meta_cache('post', $filtered_ids);
                update_post_term_cache(wp_list_pluck($events, 'ID'), 'event_listing_category');

                foreach ($events as $post) {
                    include plugin_dir_path(__FILE__) . 'event-card.php';
                }
                wp_reset_postdata();
            }//end if
        }//end if
?>
			</div>
		</section>

		<!-- BANNER -->
		<?php
        $latest = get_posts(
            [
                'post_type'      => 'post',
                'posts_per_page' => 1,
                'post_status'    => 'publish',
                'orderby'        => 'date',
                'order'          => 'DESC',
            ]
        );
if ($latest) :
    $post       = $latest[0];
    $banner_img = get_the_post_thumbnail_url($post->ID, 'full') ?: 'https://images.unsplash.com/photo-1506157786151-b8491531f063?q=80&w=2070';
    $excerpt    = wp_trim_words($post->post_excerpt ?: $post->post_content, 30, '...');
    ?>
		<section class="banner-ario-1-wrapper" style="margin-top:80px;">
			<img src="<?php echo esc_url($banner_img); ?>" class="ban-ario-1-img" alt="<?php echo esc_attr($post->post_title); ?>">
			<div class="ban-ario-1-content">
				<h3 class="ban-ario-1-subtit">Extra! Extra!</h3>
				<h2 class="ban-ario-1-titl"><?php echo esc_html($post->post_title); ?></h2>
				<p class="ban-ario-1-txt"><?php echo esc_html($excerpt); ?></p>
				<a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="ban-ario-1-btn">
					Saiba Mais <i class="ri-arrow-right-long-line"></i>
				</a>
			</div>
		</section>
		<?php endif; ?>
	</div>

	<div id="apollo-event-modal" class="apollo-event-modal" aria-hidden="true"></div>
</main>

<!-- DARK MODE TOGGLE -->
<div class="dark-mode-toggle" id="darkModeToggle" role="button" aria-label="Alternar modo escuro">
	<i class="ri-sun-line"></i>
	<i class="ri-moon-line"></i>
</div>

<style>
/* FASE 3: Responsividade Mobile-First e Estilos do Portal */
.apollo-period-filters {
	display: flex;
	gap: 0.5rem;
	margin-bottom: 1rem;
	flex-wrap: wrap;
	padding: 0.75rem;
	background: hsl(var(--muted, 210 40% 96.1%) / 0.5);
	border-radius: var(--apollo-radius-main, 0.5rem);
}

.apollo-period-filter {
	padding: 0.5rem 1rem;
	background: hsl(var(--card, 0 0% 100%));
	border: 1px solid hsl(var(--border, 214.3 31.8% 91.4%));
	border-radius: var(--apollo-radius-main, 0.5rem);
	text-decoration: none;
	color: hsl(var(--foreground, 222.2 84% 4.9%));
	font-size: var(--apollo-text-small, 0.875rem);
	font-weight: 500;
	transition: var(--apollo-transition-main, all 0.3s cubic-bezier(0.4, 0, 0.2, 1));
	display: inline-flex;
	align-items: center;
	gap: 0.5rem;
}

.apollo-period-filter:hover {
	background: hsl(var(--muted, 210 40% 96.1%));
	border-color: hsl(var(--primary, 222.2 47.4% 11.2%));
}

.apollo-period-filter.active {
	background: hsl(var(--primary, 222.2 47.4% 11.2%));
	color: hsl(var(--primary-foreground, 210 40% 98%));
	border-color: hsl(var(--primary, 222.2 47.4% 11.2%));
}

.apollo-events-section {
	margin-bottom: 3rem;
}

.apollo-section-title {
	font-family: var(--apollo-font-primary, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif);
	font-size: 1.5rem;
	font-weight: 700;
	margin-bottom: 1.5rem;
	display: flex;
	align-items: center;
	gap: 0.75rem;
	color: hsl(var(--foreground, 222.2 84% 4.9%));
}

.apollo-section-title i {
	font-size: 1.5rem;
	color: hsl(var(--primary, 222.2 47.4% 11.2%));
}

/* FASE 3: Grid Responsivo Mobile-First */
.apollo-events-grid {
	display: grid;
	grid-template-columns: 1fr; /* Mobile: 1 coluna */
	gap: 1.5rem;
	width: 100%;
}

/* Tablet: 2 colunas */
@media (min-width: 768px) {
	.apollo-events-grid {
		grid-template-columns: repeat(2, 1fr);
		gap: 2rem;
	}
}

/* Desktop: 3 colunas */
@media (min-width: 1024px) {
	.apollo-events-grid {
		grid-template-columns: repeat(3, 1fr);
		gap: 2rem;
	}
}

/* Large Desktop: 4 colunas */
@media (min-width: 1440px) {
	.apollo-events-grid {
		grid-template-columns: repeat(4, 1fr);
	}
}

.no-events-found {
	text-align: center;
	padding: 3rem 1rem;
	color: hsl(var(--muted-foreground, 215.4 16.3% 46.9%));
}

.no-events-found i {
	font-size: 3rem;
	display: block;
	margin-bottom: 1rem;
	opacity: 0.5;
}

.no-events-found p {
	font-size: 1.125rem;
	margin: 0;
}
</style>

<script src="https://assets.apollo.rio.br/base.js?ver=<?php echo date('Y-m'); ?>"></script>
<?php wp_footer(); ?>
</body>
</html>
