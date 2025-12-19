<?php
/**
 * FILE: apollo-events-manager/templates/portal-discover.php
 * PHASE 4: Migrated Events Discovery Portal
 * - Uses ViewModel data transformation
 * - Loads approved shared partials
 * - Matches approved HTML design exactly
 * - Maintains all existing contracts
 */

defined( 'ABSPATH' ) || exit;

// Load helper (maintains existing contract)
require_once plugin_dir_path( __FILE__ ) . '../includes/helpers/event-data-helper.php';

// Create ViewModel for events listing
$viewModel = Apollo_ViewModel_Factory::create_from_data(
	Apollo_Event_Data_Helper::get_cached_event_ids( true ),
	'events_listing'
);

// Get template data from ViewModel
$template_data = $viewModel->get_template_data();

// Load shared partials
$template_loader = new Apollo_Template_Loader();
$template_loader->load_partial( 'assets' );
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
	<title><?php echo esc_html( $template_data['page_title'] ); ?> - Apollo::rio</title>
	<link rel="icon" href="https://assets.apollo.rio.br/img/neon-green.webp" type="image/webp">
	<?php $template_loader->load_partial( 'assets' ); ?>
</head>

<body class="apollo-canvas-mode">
	<?php
	// Load header navigation partial
	$template_loader->load_partial(
		'header-nav',
		array(
			'current_user'     => $template_data['current_user'],
			'navigation_links' => $template_data['navigation_links'],
		)
	);
	?>

	<main class="main-container">
		<div class="event-manager-shortcode-wrapper discover-events-now-shortcode">
			<?php
			// Load hero section partial
			$template_loader->load_partial(
				'hero-section',
				array(
					'title'            => $template_data['hero_title'],
					'subtitle'         => $template_data['hero_subtitle'],
					'background_image' => $template_data['hero_background'],
				)
			);
			?>

			<!-- Filters Section -->
			<div class="filters-and-search" data-tooltip="<?php esc_attr_e( 'Filtros e busca de eventos', 'apollo-events-manager' ); ?>">
				<!-- Period Filters -->
				<div class="apollo-period-filters" role="group" aria-label="<?php esc_attr_e( 'Filtros por período', 'apollo-events-manager' ); ?>">
					<?php foreach ( $template_data['period_filters'] as $filter ) : ?>
						<a href="<?php echo esc_url( $filter['url'] ); ?>"
							class="apollo-period-filter <?php echo $filter['active'] ? 'active' : ''; ?>"
							data-period="<?php echo esc_attr( $filter['slug'] ); ?>">
							<?php echo esc_html( $filter['label'] ); ?>
						</a>
					<?php endforeach; ?>
				</div>

				<!-- Category and Tag Filters -->
				<div class="menutags event_types" role="group" aria-label="Filtros de eventos">
					<?php foreach ( $template_data['category_filters'] as $filter ) : ?>
						<?php if ( $filter['type'] === 'link' ) : ?>
							<a href="<?php echo esc_url( $filter['url'] ); ?>"
								class="menutag event-category <?php echo $filter['active'] ? 'active' : ''; ?>"
								data-slug="<?php echo esc_attr( $filter['slug'] ); ?>">
								<?php echo esc_html( $filter['label'] ); ?>
							</a>
						<?php else : ?>
							<button type="button"
									class="menutag event-category <?php echo $filter['active'] ? 'active' : ''; ?>"
									data-slug="<?php echo esc_attr( $filter['slug'] ); ?>">
								<?php echo esc_html( $filter['label'] ); ?>
							</button>
						<?php endif; ?>
					<?php endforeach; ?>

					<!-- Date Picker -->
					<div class="date-chip" id="eventDatePicker">
						<button type="button" class="date-arrow" id="datePrev" aria-label="Mês anterior">‹</button>
						<span class="date-display" id="dateDisplay" aria-live="polite"><?php echo esc_html( $template_data['current_month'] ); ?></span>
						<button type="button" class="date-arrow" id="dateNext" aria-label="Próximo mês">›</button>
					</div>

					<!-- Layout Toggle -->
					<button type="button" class="layout-toggle" id="aprio-event-toggle-layout"
							title="Alternar layout" aria-pressed="false" data-layout="card">
						<i class="ri-building-3-fill"></i>
						<span class="visually-hidden">Alternar layout</span>
					</button>
				</div>
			</div>

			<!-- Search Bar -->
			<div class="controls-bar" id="apollo-controls-bar">
				<form class="box-search" role="search" id="eventSearchForm">
					<label for="eventSearchInput" class="visually-hidden"><?php esc_html_e( 'Procurar', 'apollo-events-manager' ); ?></label>
					<i class="ri-search-line"></i>
					<input type="text" name="search_keywords" id="eventSearchInput"
							placeholder="<?php esc_attr_e( 'Buscar eventos...', 'apollo-events-manager' ); ?>"
							inputmode="search" autocomplete="off">
					<input type="hidden" name="post_type" value="event_listing">
				</form>
			</div>

			<p class="afasta-2b"></p>

			<?php
			// Render event sections using ViewModel data
			foreach ( $template_data['event_sections'] as $section ) :
				if ( empty( $section['events'] ) ) {
					continue;
				}
				?>
				<section class="apollo-events-section apollo-events-section--<?php echo esc_attr( $section['slug'] ); ?>">
					<?php if ( $section['show_title'] ) : ?>
						<h2 class="apollo-section-title">
							<i class="<?php echo esc_attr( $section['icon'] ); ?>"></i>
							<?php echo esc_html( $section['title'] ); ?>
						</h2>
					<?php endif; ?>

					<div class="apollo-events-grid <?php echo esc_attr( $section['grid_class'] ); ?>">
						<?php
						foreach ( $section['events'] as $event_data ) :
							// Use event card partial with ViewModel data
							$template_loader->load_partial( 'event-card', $event_data );
						endforeach;
						?>
					</div>
				</section>
			<?php endforeach; ?>

			<!-- Banner Section -->
			<?php if ( $template_data['banner'] ) : ?>
				<section class="banner-ario-1-wrapper" style="margin-top:80px;">
					<img src="<?php echo esc_url( $template_data['banner']['image'] ); ?>"
						class="ban-ario-1-img"
						alt="<?php echo esc_attr( $template_data['banner']['title'] ); ?>">
					<div class="ban-ario-1-content">
						<h3 class="ban-ario-1-subtit"><?php echo esc_html( $template_data['banner']['subtitle'] ); ?></h3>
						<h2 class="ban-ario-1-titl"><?php echo esc_html( $template_data['banner']['title'] ); ?></h2>
						<p class="ban-ario-1-txt"><?php echo esc_html( $template_data['banner']['excerpt'] ); ?></p>
						<a href="<?php echo esc_url( $template_data['banner']['url'] ); ?>" class="ban-ario-1-btn">
							<?php echo esc_html( $template_data['banner']['cta_text'] ); ?>
							<i class="ri-arrow-right-long-line"></i>
						</a>
					</div>
				</section>
			<?php endif; ?>
		</div>

		<!-- Event Modal -->
		<div id="apollo-event-modal" class="apollo-event-modal" aria-hidden="true"></div>
	</main>

	<!-- Dark Mode Toggle -->
	<div class="dark-mode-toggle" id="darkModeToggle" role="button" aria-label="Alternar modo escuro">
		<i class="ri-sun-line"></i>
		<i class="ri-moon-line"></i>
	</div>

	<?php
	// Load bottom bar partial if needed
	if ( $template_data['show_bottom_bar'] ) {
		$template_loader->load_partial( 'bottom-bar', $template_data['bottom_bar_data'] );
	}
	?>

	<style>
		/* Mobile-First Responsive Grid */
		.apollo-events-grid {
			display: grid;
			grid-template-columns: 1fr;
			gap: 1.5rem;
			width: 100%;
		}

		/* Tablet: 2 columns */
		@media (min-width: 768px) {
			.apollo-events-grid {
				grid-template-columns: repeat(2, 1fr);
				gap: 2rem;
			}
		}

		/* Desktop: 3 columns */
		@media (min-width: 1024px) {
			.apollo-events-grid {
				grid-template-columns: repeat(3, 1fr);
				gap: 2rem;
			}
		}

		/* Large Desktop: 4 columns */
		@media (min-width: 1440px) {
			.apollo-events-grid {
				grid-template-columns: repeat(4, 1fr);
			}
		}

		/* Filter Styles */
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

		/* Section Styles */
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

		/* No Events State */
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

	<?php wp_footer(); ?>
</body>
</html>
