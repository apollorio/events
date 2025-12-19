<?php
// phpcs:ignoreFile
/**
 * Apollo Events Manager - Dashboard Layout
 * ShadCN New York Dashboard System
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Render the events dashboard layout with ShadCN styling
 *
 * @param array $args Configuration arguments
 * @return void
 */
function apollo_events_render_dashboard_layout($args = [])
{
    $defaults = [
        'title'            => __('Dashboard de Eventos', 'apollo-events-manager'),
        'subtitle'         => __('Gerencie seus eventos e visualize estatísticas', 'apollo-events-manager'),
        'sidebar'          => true,
        'content_callback' => null,
    ];

    $args = wp_parse_args($args, $defaults);
    ?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo('charset'); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<?php wp_head(); ?>
		
		<!-- ShadCN New York CSS Variables -->
		<style>
			:root {
				/* ShadCN New York - Light Theme */
				--background: 0 0% 100%;
				--foreground: 240 10% 3.9%;
				--card: 0 0% 100%;
				--card-foreground: 240 10% 3.9%;
				--popover: 0 0% 100%;
				--popover-foreground: 240 10% 3.9%;
				--primary: 240 5.9% 10%;
				--primary-foreground: 0 0% 98%;
				--secondary: 240 4.8% 95.9%;
				--secondary-foreground: 240 5.9% 10%;
				--muted: 240 4.8% 95.9%;
				--muted-foreground: 240 3.8% 46.1%;
				--accent: 240 4.8% 95.9%;
				--accent-foreground: 240 5.9% 10%;
				--destructive: 0 84.2% 60.2%;
				--destructive-foreground: 0 0% 98%;
				--border: 240 5.9% 90%;
				--input: 240 5.9% 90%;
				--ring: 240 5.9% 10%;
				--radius: 0.5rem;
				
				/* Sidebar Variables */
				--sidebar-width: 16rem;
				--sidebar-width-collapsed: 4rem;
				--sidebar-background: 0 0% 98%;
				--sidebar-foreground: 240 5.3% 26.1%;
				--sidebar-primary: 240 5.9% 10%;
				--sidebar-primary-foreground: 0 0% 98%;
				--sidebar-accent: 240 4.8% 95.9%;
				--sidebar-accent-foreground: 240 5.9% 10%;
				--sidebar-border: 240 5.9% 90%;
				
				/* Chart Colors */
				--chart-1: 12 76% 61%;
				--chart-2: 173 58% 39%;
				--chart-3: 197 37% 24%;
				--chart-4: 43 74% 66%;
				--chart-5: 27 87% 67%;
				
				/* Event Status Colors */
				--status-published: 142 76% 36%;
				--status-pending: 48 96% 53%;
				--status-draft: 240 3.8% 46.1%;
				--status-cancelled: 0 84% 60%;
			}
			
			.dark {
				--background: 240 10% 3.9%;
				--foreground: 0 0% 98%;
				--card: 240 10% 3.9%;
				--card-foreground: 0 0% 98%;
				--popover: 240 10% 3.9%;
				--popover-foreground: 0 0% 98%;
				--primary: 0 0% 98%;
				--primary-foreground: 240 5.9% 10%;
				--secondary: 240 3.7% 15.9%;
				--secondary-foreground: 0 0% 98%;
				--muted: 240 3.7% 15.9%;
				--muted-foreground: 240 5% 64.9%;
				--accent: 240 3.7% 15.9%;
				--accent-foreground: 0 0% 98%;
				--destructive: 0 62.8% 30.6%;
				--destructive-foreground: 0 0% 98%;
				--border: 240 3.7% 15.9%;
				--input: 240 3.7% 15.9%;
				--ring: 240 4.9% 83.9%;
				
				--sidebar-background: 240 5.9% 10%;
				--sidebar-foreground: 240 4.8% 95.9%;
				--sidebar-primary: 224.3 76.3% 48%;
				--sidebar-primary-foreground: 0 0% 100%;
				--sidebar-accent: 240 3.7% 15.9%;
				--sidebar-accent-foreground: 240 4.8% 95.9%;
				--sidebar-border: 240 3.7% 15.9%;
			}
			
			*, *::before, *::after {
				box-sizing: border-box;
			}
			
			body {
				margin: 0;
				padding: 0;
				font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
				font-size: 0.875rem;
				line-height: 1.5;
				background: hsl(var(--background));
				color: hsl(var(--foreground));
				-webkit-font-smoothing: antialiased;
			}
			
			/* Dashboard Layout */
			.apollo-events-dashboard {
				display: flex;
				min-height: 100vh;
			}
			
			/* Sidebar */
			.dashboard-sidebar {
				width: var(--sidebar-width);
				background: hsl(var(--sidebar-background));
				border-right: 1px solid hsl(var(--sidebar-border));
				display: flex;
				flex-direction: column;
				position: fixed;
				top: 0;
				left: 0;
				bottom: 0;
				z-index: 40;
				transition: width 0.2s ease;
			}
			
			.dashboard-sidebar.collapsed {
				width: var(--sidebar-width-collapsed);
			}
			
			.sidebar-header {
				padding: 1rem;
				border-bottom: 1px solid hsl(var(--sidebar-border));
				display: flex;
				align-items: center;
				gap: 0.75rem;
			}
			
			.sidebar-logo {
				width: 2rem;
				height: 2rem;
				border-radius: var(--radius);
				background: hsl(var(--primary));
				color: hsl(var(--primary-foreground));
				display: flex;
				align-items: center;
				justify-content: center;
				font-weight: 700;
			}
			
			.sidebar-brand {
				font-weight: 600;
				color: hsl(var(--foreground));
			}
			
			.sidebar-nav {
				flex: 1;
				padding: 1rem;
				overflow-y: auto;
			}
			
			.nav-group {
				margin-bottom: 1.5rem;
			}
			
			.nav-group-title {
				font-size: 0.75rem;
				font-weight: 500;
				color: hsl(var(--muted-foreground));
				text-transform: uppercase;
				letter-spacing: 0.05em;
				padding: 0 0.75rem;
				margin-bottom: 0.5rem;
			}
			
			.nav-item {
				display: flex;
				align-items: center;
				gap: 0.75rem;
				padding: 0.625rem 0.75rem;
				border-radius: var(--radius);
				color: hsl(var(--sidebar-foreground));
				text-decoration: none;
				font-size: 0.875rem;
				font-weight: 500;
				transition: all 0.15s ease;
			}
			
			.nav-item:hover {
				background: hsl(var(--sidebar-accent));
				color: hsl(var(--sidebar-accent-foreground));
			}
			
			.nav-item.active {
				background: hsl(var(--sidebar-primary));
				color: hsl(var(--sidebar-primary-foreground));
			}
			
			.nav-item svg {
				width: 1.25rem;
				height: 1.25rem;
				flex-shrink: 0;
			}
			
			.nav-badge {
				margin-left: auto;
				padding: 0.125rem 0.5rem;
				border-radius: 9999px;
				background: hsl(var(--primary));
				color: hsl(var(--primary-foreground));
				font-size: 0.625rem;
				font-weight: 600;
			}
			
			.sidebar-footer {
				padding: 1rem;
				border-top: 1px solid hsl(var(--sidebar-border));
			}
			
			.user-menu {
				display: flex;
				align-items: center;
				gap: 0.75rem;
				padding: 0.5rem;
				border-radius: var(--radius);
				cursor: pointer;
				transition: background 0.15s ease;
			}
			
			.user-menu:hover {
				background: hsl(var(--sidebar-accent));
			}
			
			.user-avatar {
				width: 2rem;
				height: 2rem;
				border-radius: 9999px;
				background: hsl(var(--muted));
				overflow: hidden;
			}
			
			.user-avatar img {
				width: 100%;
				height: 100%;
				object-fit: cover;
			}
			
			.user-info {
				flex: 1;
				min-width: 0;
			}
			
			.user-name {
				font-size: 0.875rem;
				font-weight: 500;
				color: hsl(var(--foreground));
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}
			
			.user-email {
				font-size: 0.75rem;
				color: hsl(var(--muted-foreground));
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}
			
			/* Main Content */
			.dashboard-main {
				flex: 1;
				margin-left: var(--sidebar-width);
				min-width: 0;
			}
			
			.dashboard-header {
				position: sticky;
				top: 0;
				z-index: 30;
				background: hsl(var(--background));
				border-bottom: 1px solid hsl(var(--border));
				padding: 0.75rem 1.5rem;
				display: flex;
				align-items: center;
				justify-content: space-between;
			}
			
			.header-left {
				display: flex;
				align-items: center;
				gap: 1rem;
			}
			
			.menu-toggle {
				display: none;
				padding: 0.5rem;
				border: none;
				background: none;
				cursor: pointer;
				color: hsl(var(--foreground));
			}
			
			.breadcrumb {
				display: flex;
				align-items: center;
				gap: 0.5rem;
				font-size: 0.875rem;
			}
			
			.breadcrumb-item {
				color: hsl(var(--muted-foreground));
				text-decoration: none;
			}
			
			.breadcrumb-item:hover {
				color: hsl(var(--foreground));
			}
			
			.breadcrumb-separator {
				color: hsl(var(--muted-foreground));
			}
			
			.breadcrumb-current {
				color: hsl(var(--foreground));
				font-weight: 500;
			}
			
			.header-right {
				display: flex;
				align-items: center;
				gap: 0.5rem;
			}
			
			.header-btn {
				padding: 0.5rem;
				border: none;
				background: none;
				cursor: pointer;
				color: hsl(var(--muted-foreground));
				border-radius: var(--radius);
				transition: all 0.15s ease;
			}
			
			.header-btn:hover {
				background: hsl(var(--accent));
				color: hsl(var(--accent-foreground));
			}
			
			.dashboard-content {
				padding: 1.5rem;
			}
			
			.page-header {
				margin-bottom: 1.5rem;
			}
			
			.page-title {
				font-size: 1.5rem;
				font-weight: 700;
				color: hsl(var(--foreground));
				margin: 0 0 0.25rem;
			}
			
			.page-subtitle {
				font-size: 0.875rem;
				color: hsl(var(--muted-foreground));
				margin: 0;
			}
			
			/* Cards */
			.card {
				background: hsl(var(--card));
				border: 1px solid hsl(var(--border));
				border-radius: var(--radius);
			}
			
			.card-header {
				padding: 1.5rem;
				border-bottom: 1px solid hsl(var(--border));
			}
			
			.card-title {
				font-size: 1rem;
				font-weight: 600;
				color: hsl(var(--foreground));
				margin: 0;
			}
			
			.card-description {
				font-size: 0.875rem;
				color: hsl(var(--muted-foreground));
				margin: 0.25rem 0 0;
			}
			
			.card-content {
				padding: 1.5rem;
			}
			
			.card-footer {
				padding: 1rem 1.5rem;
				border-top: 1px solid hsl(var(--border));
			}
			
			/* Stat Cards */
			.stats-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
				gap: 1rem;
			}
			
			.stat-card {
				background: hsl(var(--card));
				border: 1px solid hsl(var(--border));
				border-radius: var(--radius);
				padding: 1.5rem;
			}
			
			.stat-card-header {
				display: flex;
				align-items: center;
				justify-content: space-between;
				margin-bottom: 0.5rem;
			}
			
			.stat-card-title {
				font-size: 0.875rem;
				font-weight: 500;
				color: hsl(var(--muted-foreground));
			}
			
			.stat-card-icon {
				width: 2.5rem;
				height: 2.5rem;
				border-radius: var(--radius);
				display: flex;
				align-items: center;
				justify-content: center;
			}
			
			.stat-card-value {
				font-size: 2rem;
				font-weight: 700;
				color: hsl(var(--foreground));
				line-height: 1.2;
			}
			
			.stat-card-trend {
				display: flex;
				align-items: center;
				gap: 0.25rem;
				margin-top: 0.5rem;
				font-size: 0.75rem;
			}
			
			.trend-up {
				color: hsl(var(--status-published));
			}
			
			.trend-down {
				color: hsl(var(--status-cancelled));
			}
			
			/* Buttons */
			.btn {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				gap: 0.5rem;
				padding: 0.5rem 1rem;
				font-size: 0.875rem;
				font-weight: 500;
				border-radius: var(--radius);
				border: none;
				cursor: pointer;
				text-decoration: none;
				transition: all 0.15s ease;
			}
			
			.btn-primary {
				background: hsl(var(--primary));
				color: hsl(var(--primary-foreground));
			}
			
			.btn-primary:hover {
				background: hsl(var(--primary) / 0.9);
			}
			
			.btn-secondary {
				background: hsl(var(--secondary));
				color: hsl(var(--secondary-foreground));
			}
			
			.btn-secondary:hover {
				background: hsl(var(--secondary) / 0.8);
			}
			
			.btn-outline {
				background: transparent;
				border: 1px solid hsl(var(--border));
				color: hsl(var(--foreground));
			}
			
			.btn-outline:hover {
				background: hsl(var(--accent));
			}
			
			.btn-ghost {
				background: transparent;
				color: hsl(var(--foreground));
			}
			
			.btn-ghost:hover {
				background: hsl(var(--accent));
			}
			
			.btn-sm {
				padding: 0.375rem 0.75rem;
				font-size: 0.75rem;
			}
			
			/* Tables */
			.data-table {
				width: 100%;
				border-collapse: collapse;
			}
			
			.data-table th {
				padding: 0.75rem 1rem;
				text-align: left;
				font-size: 0.75rem;
				font-weight: 500;
				color: hsl(var(--muted-foreground));
				text-transform: uppercase;
				letter-spacing: 0.05em;
				border-bottom: 1px solid hsl(var(--border));
			}
			
			.data-table td {
				padding: 1rem;
				border-bottom: 1px solid hsl(var(--border));
			}
			
			.data-table tr:last-child td {
				border-bottom: none;
			}
			
			.data-table tr:hover {
				background: hsl(var(--muted) / 0.5);
			}
			
			/* Status Badges */
			.badge {
				display: inline-flex;
				padding: 0.25rem 0.625rem;
				font-size: 0.75rem;
				font-weight: 500;
				border-radius: 9999px;
			}
			
			.badge-published {
				background: hsl(var(--status-published) / 0.1);
				color: hsl(var(--status-published));
			}
			
			.badge-pending {
				background: hsl(var(--status-pending) / 0.1);
				color: hsl(var(--status-pending));
			}
			
			.badge-draft {
				background: hsl(var(--muted));
				color: hsl(var(--muted-foreground));
			}
			
			.badge-cancelled {
				background: hsl(var(--status-cancelled) / 0.1);
				color: hsl(var(--status-cancelled));
			}
			
			/* Charts Container */
			.chart-container {
				position: relative;
				height: 300px;
			}
			
			/* Responsive */
			@media (max-width: 1024px) {
				.dashboard-sidebar {
					transform: translateX(-100%);
				}
				
				.dashboard-sidebar.open {
					transform: translateX(0);
				}
				
				.dashboard-main {
					margin-left: 0;
				}
				
				.menu-toggle {
					display: block;
				}
			}
			
			@media (max-width: 768px) {
				.stats-grid {
					grid-template-columns: 1fr;
				}
				
				.dashboard-content {
					padding: 1rem;
				}
			}
			
			/* Custom Tooltips */
			[data-tooltip] {
				position: relative;
				cursor: help;
			}
			
			[data-tooltip]::before,
			[data-tooltip]::after {
				position: absolute;
				left: 50%;
				transform: translateX(-50%);
				opacity: 0;
				visibility: hidden;
				transition: all 0.2s ease;
				pointer-events: none;
				z-index: 100;
			}
			
			[data-tooltip]::before {
				content: attr(data-tooltip);
				bottom: calc(100% + 8px);
				padding: 8px 12px;
				background: hsl(240 10% 3.9%);
				color: hsl(0 0% 98%);
				font-size: 0.75rem;
				font-weight: 500;
				line-height: 1.4;
				white-space: nowrap;
				border-radius: 6px;
				border: 1px solid hsl(240 3.7% 15.9%);
				box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
			}
			
			[data-tooltip]::after {
				content: '';
				bottom: calc(100% + 4px);
				border: 4px solid transparent;
				border-top-color: hsl(240 10% 3.9%);
			}
			
			[data-tooltip]:hover::before,
			[data-tooltip]:hover::after {
				opacity: 1;
				visibility: visible;
			}
			
			/* Tooltip positions */
			[data-tooltip-position="bottom"]::before {
				bottom: auto;
				top: calc(100% + 8px);
			}
			
			[data-tooltip-position="bottom"]::after {
				bottom: auto;
				top: calc(100% + 4px);
				border-top-color: transparent;
				border-bottom-color: hsl(240 10% 3.9%);
			}
			
			/* Multiline tooltip */
			[data-tooltip-multiline]::before {
				white-space: pre-wrap;
				max-width: 280px;
				text-align: left;
			}
			
			/* Stat card info icons */
			.stat-info-icon {
				width: 16px;
				height: 16px;
				color: hsl(var(--muted-foreground));
				cursor: help;
				opacity: 0.6;
				transition: opacity 0.15s ease;
			}
			
			.stat-info-icon:hover {
				opacity: 1;
			}
		</style>
	</head>
	<body class="apollo-events-dashboard-body">
		<div class="apollo-events-dashboard">
			<?php if ($args['sidebar']) : ?>
				<?php apollo_events_render_sidebar(); ?>
			<?php endif; ?>
			
			<main class="dashboard-main">
				<?php apollo_events_render_header($args); ?>
				
				<div class="dashboard-content">
					<?php
                    if (is_callable($args['content_callback'])) {
                        call_user_func($args['content_callback']);
                    }
    ?>
				</div>
			</main>
		</div>
		
		<?php wp_footer(); ?>
		
		<script>
		// Mobile menu toggle
		document.addEventListener('DOMContentLoaded', function() {
			const menuToggle = document.querySelector('.menu-toggle');
			const sidebar = document.querySelector('.dashboard-sidebar');
			
			if (menuToggle && sidebar) {
				menuToggle.addEventListener('click', function() {
					sidebar.classList.toggle('open');
				});
			}
		});
		</script>
	</body>
	</html>
	<?php
}

/**
 * Render the dashboard sidebar
 */
function apollo_events_render_sidebar()
{
    $current_user = wp_get_current_user();
    $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'overview';

    $nav_items = [
        'main' => [
            'title' => __('Principal', 'apollo-events-manager'),
            'items' => [
                [
                    'id'    => 'overview',
                    'label' => __('Visão Geral', 'apollo-events-manager'),
                    'url'   => add_query_arg('page', 'overview', get_permalink()),
                    'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
                ],
                [
                    'id'    => 'events',
                    'label' => __('Meus Eventos', 'apollo-events-manager'),
                    'url'   => add_query_arg('page', 'events', get_permalink()),
                    'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
                ],
                [
                    'id'    => 'statistics',
                    'label' => __('Estatísticas', 'apollo-events-manager'),
                    'url'   => add_query_arg('page', 'statistics', get_permalink()),
                    'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
                ],
            ],
        ],
        'management' => [
            'title' => __('Gerenciamento', 'apollo-events-manager'),
            'items' => [
                [
                    'id'    => 'favorites',
                    'label' => __('Favoritos', 'apollo-events-manager'),
                    'url'   => add_query_arg('page', 'favorites', get_permalink()),
                    'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
                ],
                [
                    'id'    => 'settings',
                    'label' => __('Configurações', 'apollo-events-manager'),
                    'url'   => add_query_arg('page', 'settings', get_permalink()),
                    'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
                ],
            ],
        ],
    ];
    ?>
	<aside class="dashboard-sidebar">
		<div class="sidebar-header">
			<div class="sidebar-logo">A</div>
			<span class="sidebar-brand">Apollo Events</span>
		</div>
		
		<nav class="sidebar-nav">
			<?php foreach ($nav_items as $group_key => $group) : ?>
				<div class="nav-group">
					<div class="nav-group-title"><?php echo esc_html($group['title']); ?></div>
					<?php foreach ($group['items'] as $item) : ?>
						<a href="<?php echo esc_url($item['url']); ?>" 
							class="nav-item <?php echo $current_page === $item['id'] ? 'active' : ''; ?>">
							<?php echo $item['icon']; ?>
							<span><?php echo esc_html($item['label']); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</nav>
		
		<div class="sidebar-footer">
			<div class="user-menu">
				<div class="user-avatar">
					<?php echo get_avatar($current_user->ID, 32); ?>
				</div>
				<div class="user-info">
					<div class="user-name"><?php echo esc_html($current_user->display_name); ?></div>
					<div class="user-email"><?php echo esc_html($current_user->user_email); ?></div>
				</div>
			</div>
		</div>
	</aside>
	<?php
}

/**
 * Render the dashboard header
 */
function apollo_events_render_header($args)
{
    ?>
	<header class="dashboard-header">
		<div class="header-left">
			<button class="menu-toggle" aria-label="Toggle menu">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<line x1="3" y1="12" x2="21" y2="12"/>
					<line x1="3" y1="6" x2="21" y2="6"/>
					<line x1="3" y1="18" x2="21" y2="18"/>
				</svg>
			</button>
			
			<nav class="breadcrumb">
				<a href="<?php echo esc_url(home_url()); ?>" class="breadcrumb-item">Home</a>
				<span class="breadcrumb-separator">/</span>
				<span class="breadcrumb-current"><?php echo esc_html($args['title']); ?></span>
			</nav>
		</div>
		
		<div class="header-right">
			<button class="header-btn" aria-label="Notificações">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
					<path d="M13.73 21a2 2 0 0 1-3.46 0"/>
				</svg>
			</button>
			
			<a href="<?php echo esc_url(admin_url('post-new.php?post_type=event_listing')); ?>" class="btn btn-primary btn-sm">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<line x1="12" y1="5" x2="12" y2="19"/>
					<line x1="5" y1="12" x2="19" y2="12"/>
				</svg>
				<?php echo esc_html__('Novo Evento', 'apollo-events-manager'); ?>
			</a>
		</div>
	</header>
	<?php
}
