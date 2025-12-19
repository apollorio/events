<?php
// phpcs:ignoreFile
/**
 * Event Listings End Wrapper
 * UNI.CSS REFACTORED - Uses .ap-* classes
 *
 * @package Apollo_Events_Manager
 * @version 3.0.0
 */
?>
	
	</div><!-- .event_listings .ap-event-grid -->
	
	<!-- Banner Section (Latest Blog Post) -->
	<?php
    $latest_post = get_posts(
        [
            'post_type'      => 'post',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
        ]
    );

if ($latest_post) :
    $post       = $latest_post[0];
    $banner_img = get_the_post_thumbnail_url($post->ID, 'full');
    if (! $banner_img) {
        $banner_img = 'https://images.unsplash.com/photo-1506157786151-b8491531f063?q=80&w=2070&auto=format&fit=crop';
    }
    ?>
	<section class="banner-ario-1-wrapper ap-card ap-card-bordered" 
			style="margin-top:80px"
			data-ap-tooltip="<?php esc_attr_e('Destaque do blog Apollo', 'apollo-events-manager'); ?>">
		<img src="<?php echo esc_url($banner_img); ?>" 
			alt="<?php echo esc_attr($post->post_title); ?>" 
			class="ban-ario-1-img"
			loading="lazy"
			data-ap-tooltip="<?php echo esc_attr($post->post_title); ?>">
		<div class="ban-ario-1-content ap-flex ap-flex-col ap-gap-3">
			<h3 class="ban-ario-1-subtit ap-text-sm ap-font-bold ap-text-muted"
				data-ap-tooltip="<?php esc_attr_e('Novidades do blog', 'apollo-events-manager'); ?>">
				<?php esc_html_e('Extra! Extra!', 'apollo-events-manager'); ?>
			</h3>
			<h2 class="ban-ario-1-titl ap-h3"
				data-ap-tooltip="<?php esc_attr_e('Título do post', 'apollo-events-manager'); ?>">
				<?php echo esc_html($post->post_title); ?>
			</h2>
			<p class="ban-ario-1-txt ap-text-secondary"
				data-ap-tooltip="<?php esc_attr_e('Resumo do post', 'apollo-events-manager'); ?>">
				<?php echo esc_html(wp_trim_words($post->post_content, 40, '...')); ?>
			</p>
			<a class="ban-ario-1-btn ap-btn ap-btn-primary" 
				href="<?php echo get_permalink($post->ID); ?>"
				data-ap-tooltip="<?php esc_attr_e('Clique para ler o post completo', 'apollo-events-manager'); ?>">
				<?php esc_html_e('Saiba Mais', 'apollo-events-manager'); ?> <i class="ri-arrow-right-long-line"></i>
			</a>
		</div>
	</section>
	<?php endif; ?>
	
</div><!-- .discover-events-now-shortcode -->

<!-- Dark Mode Toggle -->
<button class="dark-mode-toggle ap-btn ap-btn-icon" 
		id="darkModeToggle" 
		aria-label="<?php esc_attr_e('Alternar modo escuro', 'apollo-events-manager'); ?>" 
		type="button"
		data-ap-tooltip="<?php esc_attr_e('Alternar entre tema claro e escuro', 'apollo-events-manager'); ?>">
	<i class="ri-sun-line"></i>
	<i class="ri-moon-line"></i>
</button>