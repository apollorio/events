<?php
/**
 * Template: Single DJ Profile (CPT: event_dj)
 * PHASE 5: Migrated to ViewModel Architecture
 * Matches approved design: eventos - evento - single.html
 * Uses ViewModel data transformation and shared partials
 */

defined( 'ABSPATH' ) || exit;


// Check if we have a valid DJ profile
if ( ! have_posts() || get_post_type() !== 'event_dj' ) {
	status_header( 404 );
	nocache_headers();
	include get_404_template();
	exit;
}

// Get the current DJ post
the_post();
global $post;

// Create ViewModel for DJ profile
$viewModel     = Apollo_ViewModel_Factory::create_from_data( $post, 'dj_profile' );
$template_data = $viewModel->get_dj_profile_data();

// Load shared partials
$template_loader = new Apollo_Template_Loader();
$template_loader->load_partial( 'assets' );
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5, user-scalable=yes">
	<title><?php echo esc_html( $template_data['title'] ); ?> - DJ Profile - Apollo::rio</title>
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
			position: relative;
			width: 100%;
			height: 75vh;
			overflow: hidden;
		}

		.hero-media {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		.hero-overlay {
			position: absolute;
			bottom: 0;
			left: 0;
			right: 0;
			background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
			padding: 2rem;
			color: white;
		}

		.hero-title {
			font-size: 2rem;
			font-weight: 700;
			margin-bottom: 0.5rem;
		}

		.hero-subtitle {
			opacity: 0.9;
		}

		/* DJ details */
		.dj-details {
			padding: 2rem;
		}

		.dj-description {
			margin-bottom: 2rem;
			line-height: 1.6;
		}

		.dj-meta {
			display: grid;
			gap: 1rem;
			margin-bottom: 2rem;
		}

		.meta-item {
			display: flex;
			align-items: center;
			gap: 0.75rem;
		}

		.meta-item i {
			color: var(--primary, #007bff);
			font-size: 1.25rem;
		}

		/* Music section */
		.music-section {
			padding: 2rem;
			border-top: 1px solid var(--border-color, #e0e2e4);
		}

		.music-title {
			font-size: 1.5rem;
			font-weight: 600;
			margin-bottom: 1rem;
		}

		.music-player {
			background: var(--bg-surface, #f5f5f5);
			border-radius: var(--radius-main, 12px);
			padding: 1rem;
			margin-bottom: 1rem;
		}

		/* Upcoming events section */
		.events-section {
			padding: 2rem;
			border-top: 1px solid var(--border-color, #e0e2e4);
		}

		.events-title {
			font-size: 1.5rem;
			font-weight: 600;
			margin-bottom: 1rem;
		}

		.events-grid {
			display: grid;
			gap: 1rem;
		}

		.event-card {
			display: flex;
			align-items: center;
			gap: 1rem;
			padding: 1rem;
			background: var(--bg-surface, #f5f5f5);
			border-radius: var(--radius-main, 12px);
		}

		.event-date {
			font-size: 0.875rem;
			opacity: 0.7;
		}

		.event-info h4 {
			margin: 0;
			font-size: 1rem;
			font-weight: 600;
		}

		.event-venue {
			margin: 0;
			font-size: 0.875rem;
			opacity: 0.7;
		}

		/* Gallery section */
		.gallery-section {
			padding: 2rem;
			border-top: 1px solid var(--border-color, #e0e2e4);
		}

		.gallery-title {
			font-size: 1.5rem;
			font-weight: 600;
			margin-bottom: 1rem;
		}

		.gallery-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
			gap: 1rem;
		}

		.gallery-item img {
			width: 100%;
			height: 150px;
			object-fit: cover;
			border-radius: var(--radius-main, 12px);
		}

		/* Mobile responsive adjustments */
		@media (max-width: 888px) {
			.hero-section {
				height: 60vh;
			}

			.hero-title {
				font-size: 1.5rem;
			}

			.dj-details,
			.music-section,
			.events-section,
			.gallery-section {
				padding: 1.5rem;
			}
		}
	</style>
</head>

<body>
	<div class="mobile-container">
		<!-- Hero Section -->
		<?php if ( $template_data['hero'] ) : ?>
			<section class="hero-section">
				<?php if ( $template_data['hero']['media_url'] ) : ?>
					<img src="<?php echo esc_url( $template_data['hero']['media_url'] ); ?>"
						alt="<?php echo esc_attr( $template_data['hero']['title'] ); ?>"
						class="hero-media">
				<?php endif; ?>

				<div class="hero-overlay">
					<h1 class="hero-title"><?php echo esc_html( $template_data['hero']['title'] ); ?></h1>
					<?php if ( $template_data['hero']['subtitle'] ) : ?>
						<p class="hero-subtitle"><?php echo esc_html( $template_data['hero']['subtitle'] ); ?></p>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<!-- DJ Details -->
		<section class="dj-details">
			<?php if ( $template_data['details']['bio'] ) : ?>
				<div class="dj-description">
					<?php echo wp_kses_post( $template_data['details']['bio'] ); ?>
				</div>
			<?php endif; ?>

			<div class="dj-meta">
				<?php if ( $template_data['details']['roles'] ) : ?>
					<div class="meta-item">
						<i class="ri-user-star-line"></i>
						<span><?php echo esc_html( $template_data['details']['roles'] ); ?></span>
					</div>
				<?php endif; ?>

				<?php if ( $template_data['details']['location'] ) : ?>
					<div class="meta-item">
						<i class="ri-map-pin-2-line"></i>
						<span><?php echo esc_html( $template_data['details']['location'] ); ?></span>
					</div>
				<?php endif; ?>

				<?php if ( $template_data['details']['genres'] ) : ?>
					<div class="meta-item">
						<i class="ri-music-2-line"></i>
						<span><?php echo esc_html( $template_data['details']['genres'] ); ?></span>
					</div>
				<?php endif; ?>
			</div>
		</section>

		<!-- Music Section -->
		<?php if ( ! empty( $template_data['music'] ) ) : ?>
			<section class="music-section">
				<h2 class="music-title">Música</h2>
				<?php foreach ( $template_data['music'] as $track ) : ?>
					<div class="music-player">
						<div style="display: flex; align-items: center; gap: 1rem;">
							<button class="play-btn" style="background: none; border: none; color: var(--primary); font-size: 1.5rem;">
								<i class="ri-play-circle-line"></i>
							</button>
							<div>
								<h4 style="margin: 0; font-size: 1rem;"><?php echo esc_html( $track['title'] ); ?></h4>
								<p style="margin: 0; font-size: 0.875rem; opacity: 0.7;"><?php echo esc_html( $track['platform'] ); ?></p>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</section>
		<?php endif; ?>

		<!-- Upcoming Events Section -->
		<?php if ( ! empty( $template_data['upcoming_events'] ) ) : ?>
			<section class="events-section">
				<h2 class="events-title">Próximos Eventos</h2>
				<div class="events-grid">
					<?php foreach ( $template_data['upcoming_events'] as $event ) : ?>
						<div class="event-card">
							<div class="event-date">
								<?php echo esc_html( $event['date'] ); ?>
							</div>
							<div class="event-info">
								<h4><?php echo esc_html( $event['title'] ); ?></h4>
								<p class="event-venue"><?php echo esc_html( $event['venue'] ); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<!-- Gallery Section -->
		<?php if ( ! empty( $template_data['gallery'] ) ) : ?>
			<section class="gallery-section">
				<h2 class="gallery-title">Galeria</h2>
				<div class="gallery-grid">
					<?php foreach ( $template_data['gallery'] as $image ) : ?>
						<div class="gallery-item">
							<img src="<?php echo esc_url( $image['url'] ); ?>"
								alt="<?php echo esc_attr( $image['alt'] ); ?>"
								loading="lazy">
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<!-- Bottom Bar -->
		<?php if ( $template_data['bottom_bar'] ) : ?>
			<?php $template_loader->load_partial( 'bottom-bar', $template_data['bottom_bar'] ); ?>
		<?php endif; ?>
	</div>

	<?php wp_footer(); ?>

	<script>
		// Initialize music players
		document.addEventListener('DOMContentLoaded', function() {
			const playButtons = document.querySelectorAll('.play-btn');

			playButtons.forEach(button => {
				button.addEventListener('click', function() {
					const icon = this.querySelector('i');
					if (icon.classList.contains('ri-play-circle-line')) {
						icon.classList.remove('ri-play-circle-line');
						icon.classList.add('ri-pause-circle-line');
					} else {
						icon.classList.remove('ri-pause-circle-line');
						icon.classList.add('ri-play-circle-line');
					}
				});
			});
		});
	</script>
</body>
</html>

<?php wp_reset_postdata(); ?>
