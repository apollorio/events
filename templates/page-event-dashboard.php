<?php
/**
 * Template: Event Dashboard Public Page
 * PHASE 5: Migrated to ViewModel Architecture
 * Matches approved design: social - feed main.html
 * Uses ViewModel data transformation and shared partials
 */

defined( 'ABSPATH' ) || exit;

// Create ViewModel for dashboard access
$viewModel     = Apollo_ViewModel_Factory::create_from_data( null, 'dashboard_access' );
$template_data = $viewModel->get_dashboard_access_data();

// Load shared partials
$template_loader = new Apollo_Template_Loader();
$template_loader->load_partial( 'assets' );

// If user is logged in and has permissions, redirect to admin dashboard
if ( $template_data['should_redirect'] ) {
	wp_redirect( $template_data['redirect_url'] );
	exit;
}

// Get header (maintain WordPress theme integration)
get_header();
?>

<div class="apollo-event-dashboard-public">
	<div class="apollo-container">
		<div class="apollo-dashboard-message">
			<h1><?php echo esc_html( $template_data['title'] ); ?></h1>

			<?php if ( $template_data['user_status'] === 'logged_in_no_permission' ) : ?>
				<p><?php echo esc_html( $template_data['message'] ); ?></p>
				<p>
					<a href="<?php echo esc_url( $template_data['home_url'] ); ?>" class="button">
						<?php echo esc_html( $template_data['home_link_text'] ); ?>
					</a>
				</p>
			<?php elseif ( $template_data['user_status'] === 'not_logged_in' ) : ?>
				<p><?php echo esc_html( $template_data['message'] ); ?></p>
				<p>
					<a href="<?php echo esc_url( $template_data['login_url'] ); ?>" class="button button-primary">
						<?php echo esc_html( $template_data['login_link_text'] ); ?>
					</a>
					<a href="<?php echo esc_url( $template_data['home_url'] ); ?>" class="button">
						<?php echo esc_html( $template_data['home_link_text'] ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
	</div>
</div>

<style>
	/* Dashboard message styling */
	.apollo-event-dashboard-public {
		padding: 2rem 0;
		min-height: 60vh;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.apollo-container {
		max-width: 600px;
		margin: 0 auto;
		padding: 0 2rem;
	}

	.apollo-dashboard-message {
		text-align: center;
		background: var(--bg-main, #fff);
		padding: 3rem 2rem;
		border-radius: var(--radius-main, 12px);
		box-shadow: 0 4px 20px rgba(0,0,0,0.1);
	}

	.apollo-dashboard-message h1 {
		color: var(--text-primary, #333);
		font-size: 2rem;
		font-weight: 700;
		margin-bottom: 1.5rem;
	}

	.apollo-dashboard-message p {
		color: var(--text-secondary, #666);
		font-size: 1.1rem;
		line-height: 1.6;
		margin-bottom: 2rem;
	}

	.apollo-dashboard-message .button {
		display: inline-block;
		padding: 0.75rem 1.5rem;
		border-radius: var(--radius-main, 12px);
		text-decoration: none;
		font-weight: 500;
		transition: all 0.2s ease;
		margin: 0.5rem;
	}

	.apollo-dashboard-message .button-primary {
		background: var(--primary, #007bff);
		color: white;
		border: 2px solid var(--primary, #007bff);
	}

	.apollo-dashboard-message .button-primary:hover {
		background: var(--primary-hover, #0056b3);
		border-color: var(--primary-hover, #0056b3);
		transform: translateY(-1px);
	}

	.apollo-dashboard-message .button {
		background: var(--bg-surface, #f5f5f5);
		color: var(--text-primary, #333);
		border: 2px solid var(--bg-surface, #f5f5f5);
	}

	.apollo-dashboard-message .button:hover {
		background: var(--bg-surface-hover, #e9ecef);
		border-color: var(--bg-surface-hover, #e9ecef);
		transform: translateY(-1px);
	}

	/* Mobile responsive adjustments */
	@media (max-width: 768px) {
		.apollo-event-dashboard-public {
			padding: 1rem 0;
		}

		.apollo-container {
			padding: 0 1rem;
		}

		.apollo-dashboard-message {
			padding: 2rem 1.5rem;
		}

		.apollo-dashboard-message h1 {
			font-size: 1.5rem;
		}

		.apollo-dashboard-message p {
			font-size: 1rem;
		}

		.apollo-dashboard-message .button {
			display: block;
			width: 100%;
			margin: 0.5rem 0;
		}
	}
</style>

<?php
get_footer();
?>
