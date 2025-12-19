<?php
/**
 * Template: Cena Rio Calendar/Agenda
 * PHASE 5: Migrated to ViewModel Architecture
 * Matches approved design: social - cena-rio - agenda.html
 * Uses ViewModel data transformation and shared partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Create ViewModel for calendar/agenda
$viewModel     = Apollo_ViewModel_Factory::create_from_data( null, 'calendar_agenda' );
$template_data = $viewModel->get_calendar_agenda_data();

// Load shared partials
$template_loader = new Apollo_Template_Loader();
$template_loader->load_partial( 'assets' );
$template_loader->load_partial( 'header-nav' );
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5, user-scalable=yes">
	<title><?php echo esc_html( $template_data['title'] ); ?> - Apollo::rio</title>
	<link rel="icon" href="https://assets.apollo.rio.br/img/neon-green.webp" type="image/webp">
	<?php $template_loader->load_partial( 'assets' ); ?>

	<style>
		/* Mobile-first responsive container */
		.mobile-container {
			width: 100%;
			min-height: 100vh;
			background: var(--bg-main, #fff);
		}

		@media (min-width: 888px) {
			body {
				display: flex;
				justify-content: center;
				align-items: flex-start;
				min-height: 100vh;
				padding: 5rem 0 0rem;
				background: var(--bg-surface, #f5f5f5);
			}
			.mobile-container {
				max-width: 500px;
				width: 100%;
				background: var(--bg-main, #fff);
				box-shadow: 0 0 60px rgba(0,0,0,0.1);
				border-radius: 2rem;
				overflow: hidden;
			}
		}

		/* Hero section */
		.hero-section {
			padding: 2rem;
			text-align: center;
			background: linear-gradient(135deg, var(--primary, #007bff), var(--secondary, #6c757d));
			color: white;
		}

		.hero-title {
			font-size: 2rem;
			font-weight: 700;
			margin-bottom: 0.5rem;
		}

		.hero-subtitle {
			opacity: 0.9;
			font-size: 1.1rem;
		}

		/* Calendar navigation */
		.calendar-nav {
			padding: 1rem 2rem;
			border-bottom: 1px solid var(--border-color, #e0e2e4);
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.nav-button {
			background: none;
			border: none;
			color: var(--primary, #007bff);
			font-size: 1.25rem;
			padding: 0.5rem;
			border-radius: var(--radius-main, 12px);
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.nav-button:hover {
			background: var(--bg-surface, #f5f5f5);
		}

		.current-month {
			font-size: 1.25rem;
			font-weight: 600;
		}

		/* Calendar grid */
		.calendar-grid {
			padding: 2rem;
		}

		.calendar-header {
			display: grid;
			grid-template-columns: repeat(7, 1fr);
			gap: 0.5rem;
			margin-bottom: 1rem;
		}

		.day-name {
			text-align: center;
			font-weight: 600;
			color: var(--text-secondary, #666);
			font-size: 0.875rem;
		}

		.calendar-days {
			display: grid;
			grid-template-columns: repeat(7, 1fr);
			gap: 0.5rem;
		}

		.calendar-day {
			aspect-ratio: 1;
			border-radius: var(--radius-main, 12px);
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: flex-start;
			padding: 0.5rem;
			position: relative;
			min-height: 80px;
		}

		.calendar-day.empty {
			background: transparent;
		}

		.calendar-day.has-events {
			background: var(--bg-surface, #f5f5f5);
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.calendar-day.has-events:hover {
			background: var(--primary, #007bff);
			color: white;
		}

		.day-number {
			font-weight: 600;
			font-size: 0.875rem;
			margin-bottom: 0.25rem;
		}

		.event-indicator {
			width: 6px;
			height: 6px;
			border-radius: 50%;
			background: var(--primary, #007bff);
			margin-top: auto;
		}

		.calendar-day.has-events:hover .event-indicator {
			background: white;
		}

		/* Events list section */
		.events-section {
			padding: 2rem;
			border-top: 1px solid var(--border-color, #e0e2e4);
		}

		.events-title {
			font-size: 1.5rem;
			font-weight: 600;
			margin-bottom: 1rem;
		}

		.events-list {
			display: grid;
			gap: 1rem;
		}

		.event-item {
			display: flex;
			align-items: center;
			gap: 1rem;
			padding: 1rem;
			background: var(--bg-surface, #f5f5f5);
			border-radius: var(--radius-main, 12px);
		}

		.event-thumb {
			width: 60px;
			height: 60px;
			border-radius: var(--radius-main, 12px);
			object-fit: cover;
			flex-shrink: 0;
		}

		.event-info {
			flex: 1;
		}

		.event-info h4 {
			margin: 0;
			font-size: 1rem;
			font-weight: 600;
		}

		.event-meta {
			margin: 0.25rem 0 0 0;
			font-size: 0.875rem;
			opacity: 0.7;
		}

		.event-time {
			font-size: 0.875rem;
			color: var(--primary, #007bff);
			font-weight: 500;
		}

		/* No events message */
		.no-events {
			text-align: center;
			padding: 3rem 2rem;
			color: var(--text-secondary, #666);
		}

		.no-events i {
			font-size: 3rem;
			opacity: 0.3;
			margin-bottom: 1rem;
		}

		/* Mobile responsive adjustments */
		@media (max-width: 888px) {
			.hero-section,
			.calendar-nav,
			.calendar-grid,
			.events-section {
				padding: 1.5rem;
			}

			.hero-title {
				font-size: 1.5rem;
			}

			.calendar-nav {
				padding: 1rem 1.5rem;
			}

			.calendar-header,
			.calendar-days {
				gap: 0.25rem;
			}

			.calendar-day {
				min-height: 60px;
				padding: 0.25rem;
			}

			.day-number {
				font-size: 0.75rem;
			}
		}
	</style>
</head>

<body>
	<!-- Header Navigation -->
	<?php $template_loader->load_partial( 'header-nav', $template_data['header_nav'] ); ?>

	<div class="mobile-container">
		<!-- Hero Section -->
		<section class="hero-section">
			<h1 class="hero-title"><?php echo esc_html( $template_data['hero']['title'] ); ?></h1>
			<?php if ( $template_data['hero']['subtitle'] ) : ?>
				<p class="hero-subtitle"><?php echo esc_html( $template_data['hero']['subtitle'] ); ?></p>
			<?php endif; ?>
		</section>

		<!-- Calendar Navigation -->
		<div class="calendar-nav">
			<button class="nav-button" id="prev-month">
				<i class="ri-arrow-left-s-line"></i>
			</button>
			<div class="current-month" id="current-month">
				<?php echo esc_html( $template_data['calendar']['current_month'] ); ?>
			</div>
			<button class="nav-button" id="next-month">
				<i class="ri-arrow-right-s-line"></i>
			</button>
		</div>

		<!-- Calendar Grid -->
		<div class="calendar-grid">
			<div class="calendar-header">
				<?php foreach ( $template_data['calendar']['weekdays'] as $weekday ) : ?>
					<div class="day-name"><?php echo esc_html( $weekday ); ?></div>
				<?php endforeach; ?>
			</div>

			<div class="calendar-days" id="calendar-days">
				<?php foreach ( $template_data['calendar']['days'] as $day ) : ?>
					<div class="calendar-day <?php echo $day['has_events'] ? 'has-events' : 'empty'; ?>"
						data-date="<?php echo esc_attr( $day['date'] ); ?>">
						<?php if ( $day['day_number'] ) : ?>
							<div class="day-number"><?php echo esc_html( $day['day_number'] ); ?></div>
							<?php if ( $day['has_events'] ) : ?>
								<div class="event-indicator"></div>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Events List Section -->
		<section class="events-section">
			<h2 class="events-title" id="events-title">
				<?php echo esc_html( $template_data['events']['title'] ); ?>
			</h2>

			<?php if ( ! empty( $template_data['events']['list'] ) ) : ?>
				<div class="events-list" id="events-list">
					<?php foreach ( $template_data['events']['list'] as $event ) : ?>
						<div class="event-item">
							<?php if ( $event['thumbnail'] ) : ?>
								<img src="<?php echo esc_url( $event['thumbnail'] ); ?>"
									alt="<?php echo esc_attr( $event['title'] ); ?>"
									class="event-thumb">
							<?php else : ?>
								<div class="event-thumb" style="background: var(--bg-surface); display: flex; align-items: center; justify-content: center;">
									<i class="ri-calendar-event-line" style="font-size: 1.5rem; opacity: 0.5;"></i>
								</div>
							<?php endif; ?>

							<div class="event-info">
								<h4><?php echo esc_html( $event['title'] ); ?></h4>
								<p class="event-meta"><?php echo esc_html( $event['venue'] ); ?> • <?php echo esc_html( $event['location'] ); ?></p>
								<div class="event-time"><?php echo esc_html( $event['time'] ); ?></div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="no-events">
					<i class="ri-calendar-event-line"></i>
					<p>Nenhum evento encontrado para esta data.</p>
				</div>
			<?php endif; ?>
		</section>

		<!-- Bottom Bar -->
		<?php if ( $template_data['bottom_bar'] ) : ?>
			<?php $template_loader->load_partial( 'bottom-bar', $template_data['bottom_bar'] ); ?>
		<?php endif; ?>
	</div>

	<?php wp_footer(); ?>

	<script>
		// Calendar interaction
		document.addEventListener('DOMContentLoaded', function() {
			const calendarDays = document.querySelectorAll('.calendar-day.has-events');
			const eventsList = document.getElementById('events-list');
			const eventsTitle = document.getElementById('events-title');

			calendarDays.forEach(day => {
				day.addEventListener('click', function() {
					const date = this.dataset.date;

					// Remove previous selection
					calendarDays.forEach(d => d.classList.remove('selected'));
					this.classList.add('selected');

					// Update events list (in production, this would be an AJAX call)
					eventsTitle.textContent = `Eventos em ${new Date(date).toLocaleDateString('pt-BR')}`;

					// For now, just show a loading state
					if (eventsList) {
						eventsList.innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--text-secondary);">Carregando eventos...</div>';
					}
				});
			});
		});
	</script>
</body>
</html>
