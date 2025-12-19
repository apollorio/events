<?php
// phpcs:ignoreFile
/**
 * Template: Local/Venue Card
 * Component-based card for venue listings
 * Shows next events for each venue
 *
 * Variables available:
 * - $local_id
 * - $local_name
 * - $local_region
 * - $local_address
 * - $local_photo
 * - $local_link
 * - $local_capacity
 * - $next_events (array)
 *
 * @package Apollo_Events_Manager
 */

defined('ABSPATH') || exit;

// Get additional data if not set
if (! isset($local_id)) {
    $local_id = get_the_ID();
}
if (! isset($local_name)) {
    $local_name = get_the_title($local_id);
}
if (! isset($local_photo)) {
    $local_photo = get_the_post_thumbnail_url($local_id, 'large');
}
if (! isset($local_link)) {
    $local_link = get_permalink($local_id);
}
if (! isset($local_region)) {
    $local_region = apollo_get_post_meta($local_id, '_local_region', true);
}
if (! isset($local_address)) {
    $local_address = apollo_get_post_meta($local_id, '_local_address', true);
}
if (! isset($local_capacity)) {
    $local_capacity = apollo_get_post_meta($local_id, '_local_capacity', true);
}

// Default placeholder image
if (! $local_photo) {
    $local_photo = 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=800';
}

?>

<div class="apollo-local-card shadcn-card" data-local-id="<?php echo absint($local_id); ?>">
	<a href="<?php echo esc_url($local_link); ?>" class="card-link">
		<div class="card-image">
			<img src="<?php echo esc_url($local_photo); ?>" 
				alt="<?php echo esc_attr($local_name); ?>"
				loading="lazy">
			<div class="image-overlay"></div>
		</div>
		
		<div class="card-content">
			<div class="local-header">
				<h3 class="local-name"><?php echo esc_html($local_name); ?></h3>
				
				<?php if ($local_region) : ?>
					<p class="local-region">
						<i class="ri-map-pin-line"></i>
						<?php echo esc_html($local_region); ?>
					</p>
				<?php endif; ?>
				
				<?php if ($local_capacity) : ?>
					<p class="local-capacity">
						<i class="ri-group-line"></i>
						<?php
                        printf(
                            esc_html__('Capacidade: %s pessoas', 'apollo-events-manager'),
                            esc_html(number_format_i18n($local_capacity))
                        );
				    ?>
					</p>
				<?php endif; ?>
			</div>
			
			<?php if (! empty($next_events)) : ?>
				<div class="local-next-events">
					<h4 class="events-title">
						<i class="ri-calendar-event-line"></i>
						<?php esc_html_e('Próximos Eventos', 'apollo-events-manager'); ?>
					</h4>
					<ul class="events-list">
					<?php foreach ($next_events as $event) : ?>
						<li class="event-item">
							<a href="<?php echo esc_url($event['link']); ?>" 
								class="event-link"
								onclick="event.stopPropagation();">
								<span class="event-title"><?php echo esc_html($event['title']); ?></span>
								<span class="event-date">
									<i class="ri-time-line"></i>
									<?php echo esc_html(date_i18n('d/m', strtotime($event['date']))); ?>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
					</ul>
				</div>
			<?php else : ?>
				<div class="no-events">
					<i class="ri-calendar-line"></i>
					<?php
                    // Show the generic “no events” placeholder when this venue has no upcoming events.
                    echo esc_html(apollo_get_placeholder('APOLLO_PLACEHOLDER_NO_EVENTS'));
			    ?>
				</div>
			<?php endif; ?>
		</div>
		
		<div class="card-footer">
			<span class="view-venue">
				<?php esc_html_e('Ver Local', 'apollo-events-manager'); ?>
				<i class="ri-arrow-right-line"></i>
			</span>
		</div>
	</a>
</div>

<style>
/* ShadCN-inspired Local Card Styles */
.apollo-local-card.shadcn-card {
	position: relative;
	display: flex;
	flex-direction: column;
	background: var(--apollo-card-bg, #ffffff);
	border: 1px solid var(--apollo-border, #e5e7eb);
	border-radius: 0.75rem;
	overflow: hidden;
	transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
	box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.apollo-local-card.shadcn-card:hover {
	transform: translateY(-4px);
	box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
	border-color: var(--apollo-primary, #3b82f6);
}

.apollo-local-card .card-link {
	text-decoration: none;
	color: inherit;
	display: flex;
	flex-direction: column;
	height: 100%;
}

.apollo-local-card .card-image {
	position: relative;
	width: 100%;
	height: 200px;
	overflow: hidden;
}

.apollo-local-card .card-image img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	transition: transform 0.3s;
}

.apollo-local-card:hover .card-image img {
	transform: scale(1.05);
}

.apollo-local-card .image-overlay {
	position: absolute;
	inset: 0;
	background: linear-gradient(to top, rgba(0,0,0,0.3), transparent);
}

.apollo-local-card .card-content {
	padding: 1.5rem;
	flex-grow: 1;
	display: flex;
	flex-direction: column;
	gap: 1rem;
}

.apollo-local-card .local-header {
	display: flex;
	flex-direction: column;
	gap: 0.5rem;
}

.apollo-local-card .local-name {
	font-size: 1.25rem;
	font-weight: 700;
	margin: 0;
	color: var(--apollo-text, #1f2937);
	line-height: 1.4;
}

.apollo-local-card .local-region,
.apollo-local-card .local-capacity {
	display: flex;
	align-items: center;
	gap: 0.5rem;
	font-size: 0.875rem;
	color: var(--apollo-text-muted, #6b7280);
	margin: 0;
}

.apollo-local-card .local-next-events {
	background: var(--apollo-card-footer, #f9fafb);
	border: 1px solid var(--apollo-border, #e5e7eb);
	border-radius: 0.5rem;
	padding: 1rem;
}

.apollo-local-card .events-title {
	display: flex;
	align-items: center;
	gap: 0.5rem;
	font-size: 0.875rem;
	font-weight: 600;
	color: var(--apollo-text, #1f2937);
	margin: 0 0 0.75rem 0;
}

.apollo-local-card .events-list {
	list-style: none;
	margin: 0;
	padding: 0;
	display: flex;
	flex-direction: column;
	gap: 0.5rem;
}

.apollo-local-card .event-item {
	margin: 0;
	padding: 0;
}

.apollo-local-card .event-link {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 0.5rem;
	background: var(--apollo-bg, #ffffff);
	border: 1px solid var(--apollo-border, #e5e7eb);
	border-radius: 0.375rem;
	text-decoration: none;
	color: inherit;
	transition: all 0.2s;
}

.apollo-local-card .event-link:hover {
	background: var(--apollo-primary, #3b82f6);
	border-color: var(--apollo-primary, #3b82f6);
	color: white;
}

.apollo-local-card .event-title {
	font-size: 0.875rem;
	font-weight: 500;
	flex-grow: 1;
}

.apollo-local-card .event-date {
	display: flex;
	align-items: center;
	gap: 0.25rem;
	font-size: 0.75rem;
	font-weight: 600;
	opacity: 0.8;
}

.apollo-local-card .no-events {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 0.5rem;
	padding: 1.5rem;
	background: var(--apollo-card-footer, #f9fafb);
	border: 1px dashed var(--apollo-border, #e5e7eb);
	border-radius: 0.5rem;
	color: var(--apollo-text-muted, #6b7280);
	font-size: 0.875rem;
}

.apollo-local-card .card-footer {
	padding: 1rem 1.5rem;
	background: var(--apollo-card-footer, #f9fafb);
	border-top: 1px solid var(--apollo-border, #e5e7eb);
	display: flex;
	align-items: center;
	justify-content: flex-end;
}

.apollo-local-card .view-venue {
	display: flex;
	align-items: center;
	gap: 0.25rem;
	font-size: 0.875rem;
	font-weight: 600;
	color: var(--apollo-primary, #3b82f6);
	transition: gap 0.2s;
}

.apollo-local-card:hover .view-venue {
	gap: 0.5rem;
}

/* Grid Layout */
.apollo-locals-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
	gap: 1.5rem;
	margin: 2rem 0;
}

/* List Layout */
.apollo-locals-list .apollo-local-card {
	display: grid;
	grid-template-columns: 300px 1fr auto;
	align-items: stretch;
}

.apollo-locals-list .apollo-local-card .card-content {
	border-left: 1px solid var(--apollo-border, #e5e7eb);
	border-right: 1px solid var(--apollo-border, #e5e7eb);
}

.apollo-locals-list .apollo-local-card .card-footer {
	border-left: none;
	border-top: none;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
	.apollo-local-card.shadcn-card {
		--apollo-card-bg: #1f2937;
		--apollo-border: #374151;
		--apollo-text: #f9fafb;
		--apollo-text-muted: #9ca3af;
		--apollo-card-footer: #111827;
		--apollo-bg: #1f2937;
	}
}

/* Responsive */
@media (max-width: 640px) {
	.apollo-locals-grid {
		grid-template-columns: 1fr;
	}
	
	.apollo-locals-list .apollo-local-card {
		grid-template-columns: 1fr;
	}
	
	.apollo-locals-list .apollo-local-card .card-content {
		border-left: none;
		border-right: none;
		border-top: 1px solid var(--apollo-border, #e5e7eb);
		border-bottom: 1px solid var(--apollo-border, #e5e7eb);
	}
}
</style>
