<?php
// phpcs:ignoreFile
/**
 * Template: DJ Card
 * UNI.CSS REFACTORED - Uses .ap-* classes from uni.css
 *
 * Variables available:
 * - $dj_id
 * - $dj_name
 * - $dj_bio
 * - $dj_photo
 * - $dj_link
 * - $instagram
 * - $soundcloud
 *
 * @package Apollo_Events_Manager
 * @version 3.0.0 (UNI.CSS Refactor)
 */

defined('ABSPATH') || exit;

// Get event data if not set
if (! isset($dj_id)) {
    $dj_id = get_the_ID();
}
if (! isset($dj_name)) {
    $dj_name = get_the_title($dj_id);
}
if (! isset($dj_photo)) {
    $dj_photo = get_the_post_thumbnail_url($dj_id, 'medium');
}
if (! isset($dj_link)) {
    $dj_link = get_permalink($dj_id);
}
if (! isset($instagram)) {
    $instagram = apollo_get_post_meta($dj_id, '_dj_instagram', true);
}
if (! isset($soundcloud)) {
    $soundcloud = apollo_get_post_meta($dj_id, '_dj_soundcloud', true);
}
if (! isset($dj_bio)) {
    $dj_bio = get_the_excerpt($dj_id);
}

// Get genres
$genres     = wp_get_post_terms($dj_id, 'event_sounds', [ 'fields' => 'names' ]);
$genres_str = ! empty($genres) && ! is_wp_error($genres) ? implode(', ', array_slice($genres, 0, 3)) : '';

// Get upcoming events count
$upcoming_events = new WP_Query(
    [
        'post_type'      => 'event_listing',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => '_event_dj_ids',
                'value'   => '"' . $dj_id . '"',
                'compare' => 'LIKE',
            ],
            [
                'key'     => '_event_start_date',
                'value'   => current_time('Y-m-d'),
                'compare' => '>=',
                'type'    => 'DATE',
            ],
        ],
    ]
);

$events_count = 0;
if (! is_wp_error($upcoming_events)) {
    $events_count = $upcoming_events->found_posts;
    wp_reset_postdata();
}

?>

<article class="ap-card ap-card-dj" 
		data-dj-id="<?php echo absint($dj_id); ?>"
		data-ap-tooltip="<?php echo esc_attr(sprintf(__('DJ: %s', 'apollo-events-manager'), $dj_name)); ?>">
	
	<a href="<?php echo esc_url($dj_link); ?>" 
		class="ap-card-link"
		data-ap-tooltip="<?php echo esc_attr(sprintf(__('Ver perfil de %s', 'apollo-events-manager'), $dj_name)); ?>">
		
		<!-- Card Header with Avatar -->
		<div class="ap-card-header ap-card-dj__header"
			data-ap-tooltip="<?php esc_attr_e('Foto do DJ', 'apollo-events-manager'); ?>">
			<?php if ($dj_photo) : ?>
				<div class="ap-avatar ap-avatar-xl ap-card-dj__avatar">
					<img src="<?php echo esc_url($dj_photo); ?>" 
						alt="<?php echo esc_attr($dj_name); ?>"
						loading="lazy">
				</div>
			<?php else : ?>
				<div class="ap-avatar ap-avatar-xl ap-card-dj__avatar ap-avatar-placeholder"
					data-ap-tooltip="<?php esc_attr_e('Sem foto de perfil', 'apollo-events-manager'); ?>">
					<i class="ri-user-music-line"></i>
				</div>
			<?php endif; ?>
			
			<?php if ($events_count > 0) : ?>
				<span class="ap-badge ap-badge-primary ap-card-dj__badge"
						data-ap-tooltip="<?php echo esc_attr(sprintf(__('%d eventos próximos', 'apollo-events-manager'), $events_count)); ?>">
					<?php printf(_n('%d evento', '%d eventos', $events_count, 'apollo-events-manager'), $events_count); ?>
				</span>
			<?php endif; ?>
		</div>
		
		<!-- Card Body -->
		<div class="ap-card-body"
			data-ap-tooltip="<?php esc_attr_e('Informações do DJ', 'apollo-events-manager'); ?>">
			<h3 class="ap-card-title ap-card-dj__name"
				data-ap-tooltip="<?php echo esc_attr(sprintf(__('Nome artístico: %s', 'apollo-events-manager'), $dj_name)); ?>">
				<?php echo esc_html($dj_name); ?>
			</h3>
			
			<?php if ($genres_str) : ?>
				<p class="ap-text-muted ap-text-sm ap-flex ap-items-center ap-gap-2 ap-card-dj__genres"
					data-ap-tooltip="<?php echo esc_attr(sprintf(__('Gêneros: %s', 'apollo-events-manager'), $genres_str)); ?>">
					<i class="ri-music-2-line"></i>
					<?php echo esc_html($genres_str); ?>
				</p>
			<?php endif; ?>
			
			<?php if ($dj_bio) : ?>
				<p class="ap-text-secondary ap-text-sm ap-card-dj__bio"
					data-ap-tooltip="<?php esc_attr_e('Biografia resumida', 'apollo-events-manager'); ?>">
					<?php echo esc_html(wp_trim_words($dj_bio, 15)); ?>
				</p>
			<?php endif; ?>
		</div>
		
		<!-- Card Footer -->
		<div class="ap-card-footer ap-flex ap-justify-between ap-items-center">
			<?php if ($instagram || $soundcloud) : ?>
				<div class="ap-flex ap-gap-2 ap-card-dj__social"
					data-ap-tooltip="<?php esc_attr_e('Redes sociais', 'apollo-events-manager'); ?>">
					<?php if ($instagram) : ?>
						<a href="<?php echo esc_url($instagram); ?>" 
							target="_blank" 
							rel="noopener noreferrer" 
							class="ap-btn ap-btn-icon ap-btn-sm ap-btn-ghost"
							onclick="event.stopPropagation();"
							title="Instagram"
							aria-label="<?php esc_attr_e('Abrir Instagram em nova aba', 'apollo-events-manager'); ?>"
							data-ap-tooltip="<?php esc_attr_e('Ver Instagram', 'apollo-events-manager'); ?>">
							<i class="ri-instagram-line"></i>
						</a>
					<?php endif; ?>
					
					<?php if ($soundcloud) : ?>
						<a href="<?php echo esc_url($soundcloud); ?>" 
							target="_blank" 
							rel="noopener noreferrer" 
							class="ap-btn ap-btn-icon ap-btn-sm ap-btn-ghost"
							onclick="event.stopPropagation();"
							title="SoundCloud"
							aria-label="<?php esc_attr_e('Abrir SoundCloud em nova aba', 'apollo-events-manager'); ?>"
							data-ap-tooltip="<?php esc_attr_e('Ouvir no SoundCloud', 'apollo-events-manager'); ?>">
							<i class="ri-soundcloud-line"></i>
						</a>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<div></div>
			<?php endif; ?>
			
			<span class="ap-text-sm ap-font-semibold ap-flex ap-items-center ap-gap-1 ap-card-dj__action"
					style="color: var(--ap-color-primary);"
					data-ap-tooltip="<?php esc_attr_e('Clique para ver perfil completo', 'apollo-events-manager'); ?>">
				<?php esc_html_e('Ver Perfil', 'apollo-events-manager'); ?>
				<i class="ri-arrow-right-line"></i>
			</span>
		</div>
	</a>
</article>
