<?php
// phpcs:ignoreFile
/**
 * FASE 3: Event Card Template
 * Mobile-first responsive design with Apollo design tokens
 *
 * Variables available:
 * - $id (event ID)
 * - $date_info (from Apollo_Event_Data_Helper::parse_event_date)
 * - $local (from Apollo_Event_Data_Helper::get_local_data)
 * - $djs (from Apollo_Event_Data_Helper::get_dj_lineup)
 * - $banner (from Apollo_Event_Data_Helper::get_banner_url)
 * - $post (WP_Post object)
 * - $cat_slug
 * - $tags (array of term objects)
 */

if (! defined('ABSPATH')) {
    exit;
}

// FASE 3: Garantir que variáveis estão definidas
// Bug fix: Priorizar $post->ID se disponível (quando vem de get_posts), senão usar get_the_ID()
if (! isset($id)) {
    if (isset($post) && is_object($post) && isset($post->ID)) {
        $id = $post->ID;
    } else {
        $id = get_the_ID();
    }
}
if (! isset($post)) {
    $post = get_post($id);
}
if (! isset($date_info)) {
    require_once plugin_dir_path(__FILE__) . '../includes/helpers/event-data-helper.php';
    $date_info = Apollo_Event_Data_Helper::parse_event_date(
        apollo_get_post_meta($id, '_event_start_date', true)
    );
}
if (! isset($local)) {
    $local = Apollo_Event_Data_Helper::get_local_data($id);
}
if (! isset($djs)) {
    $djs = Apollo_Event_Data_Helper::get_dj_lineup($id);
}
if (! isset($banner)) {
    $banner = Apollo_Event_Data_Helper::get_banner_url($id);
}
if (! isset($tags)) {
    $tags = wp_get_post_terms($id, 'event_sounds');
    $tags = is_wp_error($tags) ? [] : $tags;
}
if (! isset($cat_slug)) {
    $cats     = wp_get_post_terms($id, 'event_listing_category');
    $cat_slug = ! is_wp_error($cats) && $cats ? $cats[0]->slug : 'general';
}

// FASE 3: Verificar se é evento recomendado
$is_featured = apollo_get_post_meta($id, '_event_featured', true) === '1';
?>

<article class="apollo-event-card" 
		data-event-id="<?php echo esc_attr($id); ?>"
		data-category="<?php echo esc_attr($cat_slug); ?>"
		data-local-slug="<?php echo esc_attr($local ? $local['slug'] : ''); ?>"
		data-month-str="<?php echo esc_attr($date_info['month_pt'] ?? ''); ?>"
		data-event-start-date="<?php echo esc_attr($date_info['iso_date'] ?? ''); ?>"
		<?php
        if ($is_featured) :
            ?>
			data-featured="true"<?php endif; ?>>
	
	<a href="<?php echo esc_url(get_permalink($id)); ?>" class="apollo-event-card__link" aria-label="<?php echo esc_attr($post->post_title); ?>">
		
		<!-- FASE 3: Card Image with Date Badge -->
		<div class="apollo-event-card__media">
			<?php if ($banner) : ?>
				<img src="<?php echo esc_url($banner); ?>" 
					alt="<?php echo esc_attr($post->post_title); ?>" 
					class="apollo-event-card__image"
					loading="lazy" 
					decoding="async">
			<?php else : ?>
				<div class="apollo-event-card__placeholder">
					<i class="ri-calendar-event-line"></i>
				</div>
			<?php endif; ?>
			
			<!-- Date Badge -->
			<?php if (! empty($date_info['day'])) : ?>
				<div class="apollo-event-card__date-badge">
					<span class="apollo-event-card__date-day"><?php echo esc_html($date_info['day']); ?></span>
					<span class="apollo-event-card__date-month"><?php echo esc_html($date_info['month_pt'] ?? ''); ?></span>
				</div>
			<?php endif; ?>
			
			<!-- Featured Badge -->
			<?php if ($is_featured) : ?>
				<div class="apollo-event-card__featured-badge">
					<i class="ri-star-fill"></i>
					<span>Recomendado</span>
				</div>
			<?php endif; ?>
			
			<!-- P0-6: Favorite Button -->
			<?php
            $current_user_id = get_current_user_id();
$user_favorites              = $current_user_id ? get_user_meta($current_user_id, 'apollo_favorites', true) : [];
$is_favorited                = is_array($user_favorites) && isset($user_favorites['event_listing']) && in_array($id, $user_favorites['event_listing'], true);
$favorites_count             = max(0, (int) apollo_get_post_meta($id, '_favorites_count', true));
?>
			<button class="apollo-event-card__favorite" 
					data-apollo-favorite 
					data-event-id="<?php echo esc_attr($id); ?>"
					data-favorited="<?php echo $is_favorited ? 'true' : 'false'; ?>"
					aria-label="<?php echo esc_attr($is_favorited ? 'Remover dos favoritos' : 'Adicionar aos favoritos'); ?>"
					title="<?php echo esc_attr($is_favorited ? 'Remover dos favoritos' : 'Adicionar aos favoritos'); ?>">
				<i class="<?php echo $is_favorited ? 'ri-heart-fill' : 'ri-heart-line'; ?>"></i>
			</button>
			
			<!-- Sound Tags -->
			<?php if (! empty($tags)) : ?>
				<div class="apollo-event-card__tags">
					<?php foreach (array_slice($tags, 0, 3) as $tag) : ?>
						<span class="apollo-event-card__tag"><?php echo esc_html($tag->name); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		
		<!-- FASE 3: Card Content -->
		<div class="apollo-event-card__content">
			<h3 class="apollo-event-card__title"><?php echo esc_html($post->post_title); ?></h3>
			
			<!-- DJ Lineup -->
			<?php if (! empty($djs)) : ?>
				<div class="apollo-event-card__detail apollo-event-card__detail--dj">
					<i class="ri-sound-module-fill" aria-hidden="true"></i>
					<span><?php echo wp_kses_post(Apollo_Event_Data_Helper::format_dj_display($djs)); ?></span>
				</div>
			<?php endif; ?>
			
			<!-- Local -->
			<?php if ($local && ! empty($local['name'])) : ?>
				<div class="apollo-event-card__detail apollo-event-card__detail--location">
					<i class="ri-map-pin-2-line" aria-hidden="true"></i>
					<span class="apollo-event-card__location-name"><?php echo esc_html($local['name']); ?></span>
					<?php if (! empty($local['region'])) : ?>
						<span class="apollo-event-card__location-area"><?php echo esc_html($local['region']); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		
		<!-- FASE 3: Card Footer (Hover indicator) -->
		<div class="apollo-event-card__footer">
			<span class="apollo-event-card__cta">
				Ver detalhes
				<i class="ri-arrow-right-line"></i>
			</span>
		</div>
	</a>
</article>

<style>
/* FASE 3: Design Tokens Apollo */
:root {
	--apollo-font-primary: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
	--apollo-text-size: 1rem;
	--apollo-text-regular: 400;
	--apollo-text-small: 0.875rem;
	--apollo-radius-card: 0.75rem;
	--apollo-radius-main: 0.5rem;
	--apollo-transition-main: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
	--apollo-shadow-card: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
	--apollo-shadow-card-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
	--apollo-color-primary: hsl(var(--primary, 222.2 47.4% 11.2%));
	--apollo-color-text: hsl(var(--foreground, 222.2 84% 4.9%));
	--apollo-color-text-muted: hsl(var(--muted-foreground, 215.4 16.3% 46.9%));
	--apollo-color-border: hsl(var(--border, 214.3 31.8% 91.4%));
	--apollo-color-card: hsl(var(--card, 0 0% 100%));
}

/* FASE 3: Event Card - Mobile First */
.apollo-event-card {
	width: 100%;
	background: var(--apollo-color-card);
	border: 1px solid var(--apollo-color-border);
	border-radius: var(--apollo-radius-card);
	overflow: hidden;
	transition: var(--apollo-transition-main);
	box-shadow: var(--apollo-shadow-card);
	position: relative;
	display: flex;
	flex-direction: column;
}

.apollo-event-card:hover {
	transform: translateY(-4px);
	box-shadow: var(--apollo-shadow-card-hover);
	border-color: var(--apollo-color-primary);
}

.apollo-event-card__link {
	display: flex;
	flex-direction: column;
	text-decoration: none;
	color: inherit;
	height: 100%;
}

/* Media Section */
.apollo-event-card__media {
	position: relative;
	width: 100%;
	padding-top: 56.25%; /* 16:9 Aspect Ratio */
	overflow: hidden;
	background: hsl(var(--muted, 210 40% 96.1%));
}

.apollo-event-card__image {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	object-fit: cover;
	transition: transform 0.3s ease;
}

.apollo-event-card:hover .apollo-event-card__image {
	transform: scale(1.05);
}

.apollo-event-card__placeholder {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	display: flex;
	align-items: center;
	justify-content: center;
	color: var(--apollo-color-text-muted);
	font-size: 3rem;
}

/* Date Badge */
.apollo-event-card__date-badge {
	position: absolute;
	top: 0.75rem;
	left: 0.75rem;
	background: rgba(0, 0, 0, 0.8);
	backdrop-filter: blur(8px);
	color: #fff;
	border-radius: var(--apollo-radius-main);
	padding: 0.5rem 0.75rem;
	display: flex;
	flex-direction: column;
	align-items: center;
	min-width: 3rem;
	z-index: 2;
}

.apollo-event-card__date-day {
	font-size: 1.25rem;
	font-weight: 700;
	line-height: 1.2;
	display: block;
}

.apollo-event-card__date-month {
	font-size: var(--apollo-text-small);
	text-transform: uppercase;
	opacity: 0.9;
	display: block;
}

/* Featured Badge */
.apollo-event-card__featured-badge {
	position: absolute;
	top: 0.75rem;
	right: 0.75rem;
	background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
	color: #fff;
	border-radius: var(--apollo-radius-main);
	padding: 0.375rem 0.75rem;
	display: flex;
	align-items: center;
	gap: 0.25rem;
	font-size: var(--apollo-text-small);
	font-weight: 600;
	z-index: 2;
	box-shadow: 0 2px 8px rgba(255, 107, 53, 0.3);
}

.apollo-event-card__featured-badge i {
	font-size: 0.875rem;
}

/* Tags */
.apollo-event-card__tags {
	position: absolute;
	bottom: 0.75rem;
	left: 0.75rem;
	right: 0.75rem;
	display: flex;
	flex-wrap: wrap;
	gap: 0.5rem;
	z-index: 2;
}

.apollo-event-card__tag {
	background: rgba(0, 0, 0, 0.7);
	backdrop-filter: blur(8px);
	color: #fff;
	padding: 0.25rem 0.5rem;
	border-radius: calc(var(--apollo-radius-main) / 2);
	font-size: 0.75rem;
	font-weight: 500;
}

/* Content Section */
.apollo-event-card__content {
	padding: 1rem;
	flex-grow: 1;
	display: flex;
	flex-direction: column;
	gap: 0.5rem;
}

.apollo-event-card__title {
	font-family: var(--apollo-font-primary);
	font-size: var(--apollo-text-size);
	font-weight: 600;
	line-height: 1.4;
	margin: 0;
	color: var(--apollo-color-text);
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	overflow: hidden;
}

.apollo-event-card__detail {
	display: flex;
	align-items: flex-start;
	gap: 0.5rem;
	font-size: var(--apollo-text-small);
	color: var(--apollo-color-text-muted);
	line-height: 1.5;
}

.apollo-event-card__detail i {
	flex-shrink: 0;
	margin-top: 0.125rem;
	font-size: 1rem;
}

.apollo-event-card__detail span {
	flex: 1;
}

.apollo-event-card__location-area {
	opacity: 0.7;
	margin-left: 0.25rem;
}

/* Footer */
.apollo-event-card__footer {
	padding: 0.75rem 1rem;
	border-top: 1px solid var(--apollo-color-border);
	background: hsl(var(--muted, 210 40% 96.1%) / 0.5);
}

.apollo-event-card__cta {
	display: flex;
	align-items: center;
	justify-content: space-between;
	font-size: var(--apollo-text-small);
	font-weight: 500;
	color: var(--apollo-color-primary);
	transition: var(--apollo-transition-main);
}

.apollo-event-card__cta i {
	transition: transform 0.3s ease;
}

.apollo-event-card:hover .apollo-event-card__cta i {
	transform: translateX(4px);
}

/* FASE 3: Responsividade - Tablet (768px+) */
@media (min-width: 768px) {
	.apollo-event-card {
		/* Cards em 2 colunas no tablet */
	}
}

/* FASE 3: Responsividade - Desktop (1024px+) */
@media (min-width: 1024px) {
	.apollo-event-card {
		/* Cards em 3 colunas no desktop */
	}
}

/* Dark Mode Support */
.dark .apollo-event-card {
	--apollo-color-card: hsl(var(--card, 222.2 84% 4.9%));
	--apollo-color-text: hsl(var(--foreground, 210 40% 98%));
	--apollo-color-text-muted: hsl(var(--muted-foreground, 215 20.2% 65.1%));
	--apollo-color-border: hsl(var(--border, 217.2 32.6% 17.5%));
}
</style>


