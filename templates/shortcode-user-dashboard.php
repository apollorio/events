<?php
/**
 * Template: User Dashboard Shortcode
 * PHASE 5: Migrated to ViewModel Architecture
 * Matches approved design: social - feed main.html
 * Uses ViewModel data transformation and shared partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if user is logged in
if ( ! is_user_logged_in() ) {
	echo '<div class="login-required-message">';
	echo '<p>' . esc_html__( 'Você precisa fazer login para acessar o dashboard.', 'apollo-events-manager' ) . '</p>';
	echo '<a href="' . esc_url( wp_login_url() ) . '" class="button button-primary">' . esc_html__( 'Fazer Login', 'apollo-events-manager' ) . '</a>';
	echo '</div>';
	return;
}

// Get current user
$current_user = wp_get_current_user();

// Create ViewModel for user dashboard
$viewModel     = Apollo_ViewModel_Factory::create_from_data( $current_user, 'user_dashboard' );
$template_data = $viewModel->get_user_dashboard_data();

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

		/* Profile header */
		.profile-header {
			padding: 2rem;
			text-align: center;
			background: linear-gradient(135deg, var(--primary, #007bff), var(--secondary, #6c757d));
			color: white;
		}

		.profile-avatar {
			width: 80px;
			height: 80px;
			border-radius: 50%;
			margin: 0 auto 1rem;
			border: 3px solid white;
			object-fit: cover;
		}

		.profile-name {
			font-size: 1.5rem;
			font-weight: 700;
			margin-bottom: 0.5rem;
		}

		.profile-role {
			opacity: 0.9;
			font-size: 0.875rem;
		}

		/* Stats section */
		.stats-section {
			padding: 2rem;
			border-bottom: 1px solid var(--border-color, #e0e2e4);
		}

		.stats-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
			gap: 1rem;
		}

		.stat-card {
			text-align: center;
			padding: 1rem;
			background: var(--bg-surface, #f5f5f5);
			border-radius: var(--radius-main, 12px);
		}

		.stat-number {
			font-size: 2rem;
			font-weight: 700;
			color: var(--primary, #007bff);
			display: block;
		}

		.stat-label {
			font-size: 0.875rem;
			opacity: 0.7;
			margin-top: 0.25rem;
		}

		/* Dashboard sections */
		.dashboard-section {
			padding: 2rem;
			border-bottom: 1px solid var(--border-color, #e0e2e4);
		}

		.section-title {
			font-size: 1.25rem;
			font-weight: 600;
			margin-bottom: 1rem;
		}

		/* Recent events */
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

		.event-info h4 {
			margin: 0;
			font-size: 1rem;
			font-weight: 600;
		}

		.event-meta {
			margin: 0;
			font-size: 0.875rem;
			opacity: 0.7;
		}

		/* Quick actions */
		.actions-section {
			padding: 2rem;
		}

		.actions-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
			gap: 1rem;
		}

		.action-button {
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 0.5rem;
			padding: 1.5rem 1rem;
			background: var(--bg-surface, #f5f5f5);
			border-radius: var(--radius-main, 12px);
			text-decoration: none;
			color: var(--text-primary, #333);
			transition: all 0.2s ease;
		}

		.action-button:hover {
			background: var(--primary, #007bff);
			color: white;
			transform: translateY(-2px);
		}

		.action-icon {
			font-size: 2rem;
			opacity: 0.7;
		}

		.action-button:hover .action-icon {
			opacity: 1;
		}

		.action-label {
			font-size: 0.875rem;
			font-weight: 500;
			text-align: center;
		}

		/* Mobile responsive adjustments */
		@media (max-width: 888px) {
			.profile-header,
			.stats-section,
			.dashboard-section,
			.actions-section {
				padding: 1.5rem;
			}

			.profile-name {
				font-size: 1.25rem;
			}

			.stats-grid {
				grid-template-columns: repeat(2, 1fr);
			}
		}
	</style>
</head>

<body>
	<!-- Header Navigation -->
	<?php $template_loader->load_partial( 'header-nav', $template_data['header_nav'] ); ?>

	<div class="mobile-container">
		<!-- Profile Header -->
		<section class="profile-header">
			<?php if ( $template_data['profile']['avatar'] ) : ?>
				<img src="<?php echo esc_url( $template_data['profile']['avatar'] ); ?>"
					alt="<?php echo esc_attr( $template_data['profile']['name'] ); ?>"
					class="profile-avatar">
			<?php else : ?>
				<div class="profile-avatar" style="background: var(--bg-surface); display: flex; align-items: center; justify-content: center;">
					<i class="ri-user-line" style="font-size: 2rem; opacity: 0.7;"></i>
				</div>
			<?php endif; ?>

			<h1 class="profile-name"><?php echo esc_html( $template_data['profile']['name'] ); ?></h1>
			<?php if ( $template_data['profile']['role'] ) : ?>
				<p class="profile-role"><?php echo esc_html( $template_data['profile']['role'] ); ?></p>
			<?php endif; ?>
		</section>

		<!-- Stats Section -->
		<?php if ( ! empty( $template_data['stats'] ) ) : ?>
			<section class="stats-section">
				<div class="stats-grid">
					<?php foreach ( $template_data['stats'] as $stat ) : ?>
						<div class="stat-card">
							<span class="stat-number"><?php echo esc_html( $stat['value'] ); ?></span>
							<span class="stat-label"><?php echo esc_html( $stat['label'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<!-- Recent Events Section -->
		<?php if ( ! empty( $template_data['recent_events'] ) ) : ?>
			<section class="dashboard-section">
				<h2 class="section-title">Eventos Recentes</h2>
				<div class="events-list">
					<?php foreach ( $template_data['recent_events'] as $event ) : ?>
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
								<p class="event-meta"><?php echo esc_html( $event['date'] ); ?> • <?php echo esc_html( $event['status'] ); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<!-- Quick Actions Section -->
		<section class="actions-section">
			<h2 class="section-title">Ações Rápidas</h2>
			<div class="actions-grid">
				<?php if ( ! empty( $template_data['actions'] ) ) : ?>
					<?php foreach ( $template_data['actions'] as $action ) : ?>
						<a href="<?php echo esc_url( $action['url'] ); ?>" class="action-button">
							<i class="<?php echo esc_attr( $action['icon'] ); ?> action-icon"></i>
							<span class="action-label"><?php echo esc_html( $action['label'] ); ?></span>
						</a>
					<?php endforeach; ?>
				<?php else : ?>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=event_listing' ) ); ?>" class="action-button">
						<i class="ri-add-circle-line action-icon"></i>
						<span class="action-label">Criar Evento</span>
					</a>
					<a href="<?php echo esc_url( home_url( '/eventos' ) ); ?>" class="action-button">
						<i class="ri-search-line action-icon"></i>
						<span class="action-label">Explorar Eventos</span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>" class="action-button">
						<i class="ri-settings-line action-icon"></i>
						<span class="action-label">Configurações</span>
					</a>
					<a href="<?php echo esc_url( wp_logout_url() ); ?>" class="action-button">
						<i class="ri-logout-box-line action-icon"></i>
						<span class="action-label">Sair</span>
					</a>
				<?php endif; ?>
			</div>
		</section>

		<!-- Bottom Bar -->
		<?php if ( $template_data['bottom_bar'] ) : ?>
			<?php $template_loader->load_partial( 'bottom-bar', $template_data['bottom_bar'] ); ?>
		<?php endif; ?>
	</div>

	<?php wp_footer(); ?>
</body>
</html>
