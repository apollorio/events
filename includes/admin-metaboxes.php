<?php
// phpcs:ignoreFile
/**
 * Apollo Events Manager - Admin Meta Boxes
 * Enhanced event editing with correct CPT structure
 *
 * CPTs: event_listing, event_dj, event_local
 * No organizer, no venue - only DJs and Local
 */

defined('ABSPATH') || exit;

class Apollo_Events_Admin_Metaboxes
{
    public function __construct()
    {
        add_action('add_meta_boxes', [ $this, 'register_metaboxes' ]);
        add_action('save_post_event_listing', [ $this, 'save_metabox_data' ], 20, 2);
        add_action('save_post_event_dj', [ $this, 'save_dj_meta' ], 20, 2);
        add_action('save_post_event_local', [ $this, 'save_local_meta' ], 20, 2);
        add_action('admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ]);

        // AJAX handlers
        add_action('wp_ajax_apollo_add_new_dj', [ $this, 'ajax_add_new_dj' ]);
        add_action('wp_ajax_apollo_add_new_local', [ $this, 'ajax_add_new_local' ]);
    }

    /**
     * Register meta boxes
     */
    public function register_metaboxes()
    {
        add_meta_box(
            'apollo_event_details',
            __('Apollo Event Details', 'apollo-events-manager'),
            [ $this, 'render_event_details_metabox' ],
            'event_listing',
            'normal',
            'high'
        );

        add_meta_box(
            'apollo_dj_details',
            __('Apollo DJ Details', 'apollo-events-manager'),
            [ $this, 'render_dj_metabox' ],
            'event_dj',
            'normal',
            'high'
        );

        add_meta_box(
            'apollo_local_details',
            __('Apollo Local Details', 'apollo-events-manager'),
            [ $this, 'render_local_metabox' ],
            'event_local',
            'normal',
            'high'
        );

        // FASE 2: Metabox de Gestão
        add_meta_box(
            'apollo_event_gestao',
            __('Gestão', 'apollo-events-manager'),
            [ $this, 'render_gestao_metabox' ],
            'event_listing',
            'side',
            'default'
        );
    }

    /**
     * Enqueue admin scripts
     * TODO 102: Enhanced with ShadCN components
     */
    public function enqueue_admin_scripts($hook)
    {
        global $post_type;

        $supported = [ 'event_listing', 'event_dj', 'event_local' ];
        if (! in_array($post_type, $supported, true)) {
            return;
        }

        // ShadCN components for admin
        wp_enqueue_style('apollo-shadcn-admin', APOLLO_APRIO_URL . 'assets/css/apollo-shadcn-components.css', [], APOLLO_APRIO_VERSION);
        wp_enqueue_style('remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', [], '4.7.0');
        wp_enqueue_style('apollo-admin-metabox', APOLLO_APRIO_URL . 'assets/admin-metabox.css', [ 'apollo-shadcn-admin' ], APOLLO_APRIO_VERSION);

        if ($post_type === 'event_listing') {
            // Motion.dev for smooth animations
            wp_enqueue_script('framer-motion', 'https://cdn.jsdelivr.net/npm/framer-motion@11.0.0/dist/framer-motion.js', [], '11.0.0', true);

            // WordPress Media Uploader
            wp_enqueue_media();

            wp_enqueue_script(
                'apollo-admin-metabox',
                APOLLO_APRIO_URL . 'assets/admin-metabox.js',
                [ 'jquery', 'jquery-ui-dialog', 'media-upload', 'media-views' ],
                APOLLO_APRIO_VERSION,
                true
            );

            wp_localize_script(
                'apollo-admin-metabox',
                'apolloAdmin',
                [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce'    => wp_create_nonce('apollo_admin_nonce'),
                    'i18n'     => [
                        'dj_exists'    => __('DJ %1$s já está registrado com slug %2$s', 'apollo-events-manager'),
                        'local_exists' => __('Local %1$s já está registrado com slug %2$s', 'apollo-events-manager'),
                        'enter_name'   => __('Por favor, digite um nome', 'apollo-events-manager'),
                    ],
                ]
            );

            wp_enqueue_style('wp-jquery-ui-dialog');
        }//end if
    }

    /**
     * Render DJ metabox
     */
    public function render_dj_metabox($post)
    {
        wp_nonce_field('apollo_dj_meta_save', 'apollo_dj_meta_nonce');

        // Nome e Bio usam post_title e post_content nativos do WordPress
        $dj_meta = [
            '_dj_image'              => apollo_get_post_meta($post->ID, '_dj_image', true),
            '_dj_banner'             => apollo_get_post_meta($post->ID, '_dj_banner', true),
            '_dj_website'            => apollo_get_post_meta($post->ID, '_dj_website', true),
            '_dj_instagram'          => apollo_get_post_meta($post->ID, '_dj_instagram', true),
            '_dj_facebook'           => apollo_get_post_meta($post->ID, '_dj_facebook', true),
            '_dj_soundcloud'         => apollo_get_post_meta($post->ID, '_dj_soundcloud', true),
            '_dj_bandcamp'           => apollo_get_post_meta($post->ID, '_dj_bandcamp', true),
            '_dj_spotify'            => apollo_get_post_meta($post->ID, '_dj_spotify', true),
            '_dj_youtube'            => apollo_get_post_meta($post->ID, '_dj_youtube', true),
            '_dj_mixcloud'           => apollo_get_post_meta($post->ID, '_dj_mixcloud', true),
            '_dj_beatport'           => apollo_get_post_meta($post->ID, '_dj_beatport', true),
            '_dj_resident_advisor'   => apollo_get_post_meta($post->ID, '_dj_resident_advisor', true),
            '_dj_twitter'            => apollo_get_post_meta($post->ID, '_dj_twitter', true),
            '_dj_tiktok'             => apollo_get_post_meta($post->ID, '_dj_tiktok', true),
            '_dj_original_project_1' => apollo_get_post_meta($post->ID, '_dj_original_project_1', true),
            '_dj_original_project_2' => apollo_get_post_meta($post->ID, '_dj_original_project_2', true),
            '_dj_original_project_3' => apollo_get_post_meta($post->ID, '_dj_original_project_3', true),
            '_dj_set_url'            => apollo_get_post_meta($post->ID, '_dj_set_url', true),
            '_dj_media_kit_url'      => apollo_get_post_meta($post->ID, '_dj_media_kit_url', true),
            '_dj_rider_url'          => apollo_get_post_meta($post->ID, '_dj_rider_url', true),
            '_dj_mix_url'            => apollo_get_post_meta($post->ID, '_dj_mix_url', true),
        ];

        ?>
		<div class="apollo-metabox-container">
			<div class="apollo-field-group">
				<h3><?php _e('Identidade do DJ', 'apollo-events-manager'); ?></h3>
				
				<p class="description" style="margin-bottom:15px;padding:10px;background:#f0f6fc;border-left:4px solid #2271b1;border-radius:4px;">
					<strong><?php _e('Nome e Bio:', 'apollo-events-manager'); ?></strong> 
					<?php _e('Use o campo "Título" acima para o nome do DJ e o "Editor" para a bio/descrição.', 'apollo-events-manager'); ?>
				</p>

				<div class="apollo-field">
					<label for="apollo_dj_image"><?php _e('Imagem/Avatar (URL ou Attachment ID)', 'apollo-events-manager'); ?></label>
					<input type="text" id="apollo_dj_image" name="apollo_dj[_dj_image]" class="widefat" value="<?php echo esc_attr($dj_meta['_dj_image']); ?>" placeholder="<?php esc_attr_e('URL da imagem ou ID de mídia', 'apollo-events-manager'); ?>">
					<p class="description"><?php _e('URL completa da imagem ou ID do attachment do WordPress', 'apollo-events-manager'); ?></p>
				</div>

				<div class="apollo-field">
					<label for="apollo_dj_banner"><?php _e('Banner (URL ou Attachment ID)', 'apollo-events-manager'); ?></label>
					<input type="text" id="apollo_dj_banner" name="apollo_dj[_dj_banner]" class="widefat" value="<?php echo esc_attr($dj_meta['_dj_banner']); ?>" placeholder="<?php esc_attr_e('URL do banner ou ID de mídia', 'apollo-events-manager'); ?>">
					<p class="description"><?php _e('Banner adicional do DJ (usado como fallback se imagem principal não estiver disponível)', 'apollo-events-manager'); ?></p>
				</div>
			</div>

			<div class="apollo-field-group">
				<h3><?php _e('Redes & Plataformas', 'apollo-events-manager'); ?></h3>

				<?php
                $social_fields = [
                    '_dj_website'          => __('Site oficial', 'apollo-events-manager'),
                    '_dj_soundcloud'       => __('SoundCloud', 'apollo-events-manager'),
                    '_dj_spotify'          => __('Spotify', 'apollo-events-manager'),
                    '_dj_youtube'          => __('YouTube', 'apollo-events-manager'),
                    '_dj_mixcloud'         => __('Mixcloud', 'apollo-events-manager'),
                    '_dj_beatport'         => __('Beatport', 'apollo-events-manager'),
                    '_dj_bandcamp'         => __('Bandcamp', 'apollo-events-manager'),
                    '_dj_resident_advisor' => __('Resident Advisor', 'apollo-events-manager'),
                    '_dj_instagram'        => __('Instagram (URL ou @handle)', 'apollo-events-manager'),
                    '_dj_facebook'         => __('Facebook', 'apollo-events-manager'),
                    '_dj_twitter'          => __('Twitter / X', 'apollo-events-manager'),
                    '_dj_tiktok'           => __('TikTok', 'apollo-events-manager'),
                ];

        foreach ($social_fields as $meta_key => $label) :
            ?>
					<div class="apollo-field">
						<label for="<?php echo esc_attr($meta_key); ?>"><?php echo esc_html($label); ?></label>
						<input type="text" id="<?php echo esc_attr($meta_key); ?>" name="apollo_dj[<?php echo esc_attr($meta_key); ?>]" class="widefat" value="<?php echo esc_attr($dj_meta[ $meta_key ]); ?>">
					</div>
				<?php endforeach; ?>
			</div>

			<div class="apollo-field-group">
				<h3><?php _e('Conteúdo Profissional', 'apollo-events-manager'); ?></h3>

				<div class="apollo-field-grid">
					<?php
            for ($i = 1; $i <= 3; $i++) :
                $key = '_dj_original_project_' . $i;
                ?>
						<div class="apollo-field">
							<label for="<?php echo esc_attr($key); ?>"><?php printf(__('Projeto Original %d', 'apollo-events-manager'), $i); ?></label>
							<input type="text" id="<?php echo esc_attr($key); ?>" name="apollo_dj[<?php echo esc_attr($key); ?>]" class="widefat" value="<?php echo esc_attr($dj_meta[ $key ]); ?>">
						</div>
					<?php endfor; ?>
				</div>

				<div class="apollo-field">
					<label for="_dj_set_url"><?php _e('URL de DJ Set (SoundCloud, YouTube... )', 'apollo-events-manager'); ?></label>
					<input type="text" id="_dj_set_url" name="apollo_dj[_dj_set_url]" class="widefat" value="<?php echo esc_attr($dj_meta['_dj_set_url']); ?>">
				</div>

				<div class="apollo-field">
					<label for="_dj_media_kit_url"><?php _e('URL do Media Kit', 'apollo-events-manager'); ?></label>
					<input type="text" id="_dj_media_kit_url" name="apollo_dj[_dj_media_kit_url]" class="widefat" value="<?php echo esc_attr($dj_meta['_dj_media_kit_url']); ?>">
				</div>

				<div class="apollo-field">
					<label for="_dj_rider_url"><?php _e('URL do Rider', 'apollo-events-manager'); ?></label>
					<input type="text" id="_dj_rider_url" name="apollo_dj[_dj_rider_url]" class="widefat" value="<?php echo esc_attr($dj_meta['_dj_rider_url']); ?>">
				</div>

				<div class="apollo-field">
					<label for="_dj_mix_url"><?php _e('URL de Mix / Playlist', 'apollo-events-manager'); ?></label>
					<input type="text" id="_dj_mix_url" name="apollo_dj[_dj_mix_url]" class="widefat" value="<?php echo esc_attr($dj_meta['_dj_mix_url']); ?>">
				</div>
			</div>
		</div>
		<?php
    }

    /**
     * Render Local metabox
     */
    public function render_local_metabox($post)
    {
        wp_nonce_field('apollo_local_meta_save', 'apollo_local_meta_nonce');

        // Nome e Descrição usam post_title e post_content nativos do WordPress
        $local_meta = [
            '_local_address'   => apollo_get_post_meta($post->ID, '_local_address', true),
            '_local_city'      => apollo_get_post_meta($post->ID, '_local_city', true),
            '_local_state'     => apollo_get_post_meta($post->ID, '_local_state', true),
            '_local_latitude'  => apollo_get_post_meta($post->ID, '_local_latitude', true),
            '_local_longitude' => apollo_get_post_meta($post->ID, '_local_longitude', true),
            '_local_website'   => apollo_get_post_meta($post->ID, '_local_website', true),
            '_local_instagram' => apollo_get_post_meta($post->ID, '_local_instagram', true),
            '_local_facebook'  => apollo_get_post_meta($post->ID, '_local_facebook', true),
        ];

        $local_images = [];
        for ($i = 1; $i <= 5; $i++) {
            $local_images[ $i ] = apollo_get_post_meta($post->ID, '_local_image_' . $i, true);
        }

        ?>
		<div class="apollo-metabox-container">
			<div class="apollo-field-group">
				<h3><?php _e('Identidade do Local', 'apollo-events-manager'); ?></h3>
				
				<p class="description" style="margin-bottom:15px;padding:10px;background:#f0f6fc;border-left:4px solid #2271b1;border-radius:4px;">
					<strong><?php _e('Nome e Descrição:', 'apollo-events-manager'); ?></strong> 
					<?php _e('Use o campo "Título" acima para o nome do local e o "Editor" para a descrição.', 'apollo-events-manager'); ?>
				</p>
			</div>

			<div class="apollo-field-group">
				<h3><?php _e('Endereço e Coordenadas', 'apollo-events-manager'); ?></h3>

				<div class="apollo-field">
					<label for="apollo_local_address"><?php _e('Endereço completo', 'apollo-events-manager'); ?></label>
					<input type="text" id="apollo_local_address" name="apollo_local[_local_address]" class="widefat" value="<?php echo esc_attr($local_meta['_local_address']); ?>" placeholder="<?php esc_attr_e('Rua, número, complemento', 'apollo-events-manager'); ?>">
				</div>

				<div class="apollo-field-grid">
					<div class="apollo-field">
						<label for="apollo_local_city"><?php _e('Cidade', 'apollo-events-manager'); ?></label>
						<input type="text" id="apollo_local_city" name="apollo_local[_local_city]" class="widefat" value="<?php echo esc_attr($local_meta['_local_city']); ?>">
					</div>
					<div class="apollo-field">
						<label for="apollo_local_state"><?php _e('Estado', 'apollo-events-manager'); ?></label>
						<input type="text" id="apollo_local_state" name="apollo_local[_local_state]" class="widefat" value="<?php echo esc_attr($local_meta['_local_state']); ?>" placeholder="<?php esc_attr_e('Ex: RJ', 'apollo-events-manager'); ?>">
					</div>
				</div>

				<div class="apollo-field-grid">
					<div class="apollo-field">
						<label for="apollo_local_latitude"><?php _e('Latitude', 'apollo-events-manager'); ?></label>
						<input type="text" id="apollo_local_latitude" name="apollo_local[_local_latitude]" class="widefat" value="<?php echo esc_attr($local_meta['_local_latitude']); ?>" placeholder="-22.9068">
					</div>
					<div class="apollo-field">
						<label for="apollo_local_longitude"><?php _e('Longitude', 'apollo-events-manager'); ?></label>
						<input type="text" id="apollo_local_longitude" name="apollo_local[_local_longitude]" class="widefat" value="<?php echo esc_attr($local_meta['_local_longitude']); ?>" placeholder="-43.1729">
					</div>
				</div>
				<p class="description"><?php _e('Informe latitude/longitude caso deseje substituir o geocoding automático.', 'apollo-events-manager'); ?></p>
			</div>

			<div class="apollo-field-group">
				<h3><?php _e('Redes & Contato', 'apollo-events-manager'); ?></h3>

				<div class="apollo-field">
					<label for="apollo_local_website"><?php _e('Website', 'apollo-events-manager'); ?></label>
					<input type="text" id="apollo_local_website" name="apollo_local[_local_website]" class="widefat" value="<?php echo esc_attr($local_meta['_local_website']); ?>">
				</div>
				<div class="apollo-field">
					<label for="apollo_local_instagram"><?php _e('Instagram (URL ou @handle)', 'apollo-events-manager'); ?></label>
					<input type="text" id="apollo_local_instagram" name="apollo_local[_local_instagram]" class="widefat" value="<?php echo esc_attr($local_meta['_local_instagram']); ?>">
				</div>
				<div class="apollo-field">
					<label for="apollo_local_facebook"><?php _e('Facebook', 'apollo-events-manager'); ?></label>
					<input type="text" id="apollo_local_facebook" name="apollo_local[_local_facebook]" class="widefat" value="<?php echo esc_attr($local_meta['_local_facebook']); ?>">
				</div>
			</div>

			<div class="apollo-field-group">
				<h3><?php _e('Galeria de Imagens (máx. 5)', 'apollo-events-manager'); ?></h3>
				<p class="description"><?php _e('Use URLs ou IDs de mídia do WordPress. O template usa até 5 imagens.', 'apollo-events-manager'); ?></p>
				<?php for ($i = 1; $i <= 5; $i++) : ?>
					<div class="apollo-field">
						<label for="apollo_local_image_<?php echo esc_attr($i); ?>"><?php printf(__('Imagem %d', 'apollo-events-manager'), $i); ?></label>
						<input type="text" id="apollo_local_image_<?php echo esc_attr($i); ?>" name="apollo_local_images[<?php echo esc_attr($i); ?>]" class="widefat" value="<?php echo esc_attr($local_images[ $i ]); ?>">
					</div>
				<?php endfor; ?>
			</div>
		</div>
		<?php
    }

    /**
     * Render event details metabox
     */
    public function render_event_details_metabox($post)
    {
        wp_nonce_field('apollo_event_meta_save', 'apollo_event_meta_nonce');

        // Get current values
        $current_djs = apollo_get_post_meta($post->ID, '_event_dj_ids', true);
        $current_djs = maybe_unserialize($current_djs);
        $current_djs = is_array($current_djs) ? array_map('intval', $current_djs) : [];

        // Use unified connection manager
        $local_id = 0;
        if (class_exists('Apollo_Local_Connection')) {
            $connection = Apollo_Local_Connection::get_instance();
            $local_id   = $connection->get_local_id($post->ID);
        } else {
            // Fallback to direct meta access
            $local_id = apollo_get_post_meta($post->ID, '_event_local_ids', true);
            if (is_array($local_id)) {
                $local_id = ! empty($local_id) ? absint($local_id[0]) : 0;
            } else {
                $local_id = absint($local_id);
            }
        }

        $current_local = $local_id > 0 ? [ $local_id ] : [];

        $current_timetable_raw = apollo_get_post_meta($post->ID, '_event_timetable', true);
        $current_timetable     = apollo_sanitize_timetable($current_timetable_raw);
        $timetable_json        = ! empty($current_timetable) ? wp_json_encode($current_timetable) : '';

        $event_video_url = apollo_get_post_meta($post->ID, '_event_video_url', true);

        // Get event dates
        $event_start_date = apollo_get_post_meta($post->ID, '_event_start_date', true);
        $event_end_date   = apollo_get_post_meta($post->ID, '_event_end_date', true);
        $event_start_time = apollo_get_post_meta($post->ID, '_event_start_time', true);
        $event_end_time   = apollo_get_post_meta($post->ID, '_event_end_time', true);

        // Get additional event fields
        $event_title    = apollo_get_post_meta($post->ID, '_event_title', true);
        $event_banner   = apollo_get_post_meta($post->ID, '_event_banner', true);
        $event_location = apollo_get_post_meta($post->ID, '_event_location', true);
        $event_country  = apollo_get_post_meta($post->ID, '_event_country', true);
        $tickets_ext    = apollo_get_post_meta($post->ID, '_tickets_ext', true);
        $cupom_ario     = apollo_get_post_meta($post->ID, '_cupom_ario', true);
        $promo_images   = apollo_get_post_meta($post->ID, '_3_imagens_promo', true);
        $promo_images   = is_array($promo_images) ? $promo_images : (is_string($promo_images) ? maybe_unserialize($promo_images) : []);
        if (! is_array($promo_images)) {
            $promo_images = [];
        }
        $final_image = apollo_get_post_meta($post->ID, '_imagem_final', true);

        ?>
		<div class="apollo-metabox-container">
			
			<!-- ===== DATE & TIME SECTION ===== -->
			<div class="apollo-field-group">
				<h3><?php _e('Data e Horário do Evento', 'apollo-events-manager'); ?></h3>
				
				<div class="apollo-field">
					<label for="apollo_event_start_date"><?php _e('Data de Início:', 'apollo-events-manager'); ?></label>
					<input 
						type="date" 
						name="apollo_event_start_date" 
						id="apollo_event_start_date" 
						class="widefat" 
						value="<?php echo esc_attr($event_start_date); ?>"
						required
					>
				</div>
				
				<div class="apollo-field">
					<label for="apollo_event_start_time"><?php _e('Hora de Início:', 'apollo-events-manager'); ?></label>
					<input 
						type="time" 
						name="apollo_event_start_time" 
						id="apollo_event_start_time" 
						class="widefat" 
						value="<?php echo esc_attr($event_start_time); ?>"
					>
				</div>
				
				<div class="apollo-field">
					<label for="apollo_event_end_date"><?php _e('Data de Término (opcional):', 'apollo-events-manager'); ?></label>
					<input 
						type="date" 
						name="apollo_event_end_date" 
						id="apollo_event_end_date" 
						class="widefat" 
						value="<?php echo esc_attr($event_end_date); ?>"
					>
				</div>
				
				<div class="apollo-field">
					<label for="apollo_event_end_time"><?php _e('Hora de Término (opcional):', 'apollo-events-manager'); ?></label>
					<input 
						type="time" 
						name="apollo_event_end_time" 
						id="apollo_event_end_time" 
						class="widefat" 
						value="<?php echo esc_attr($event_end_time); ?>"
					>
				</div>
			</div>
			
			<!-- ===== DJS SECTION ===== -->
			<div class="apollo-field-group" data-motion-group="true">
				<h3><?php _e('DJs e Line-up', 'apollo-events-manager'); ?></h3>
				
				<div class="apollo-field">
					<label for="apollo_event_djs"><?php _e('DJs (múltipla seleção):', 'apollo-events-manager'); ?></label>
					<div class="apollo-field-controls">
						<!-- Enhanced DJ Selector with Search -->
						<div class="apollo-enhanced-select" data-motion-select="true">
							<div class="apollo-select-search">
								<input 
									type="text" 
									id="apollo_dj_search" 
									class="apollo-search-input" 
									placeholder="<?php esc_attr_e('Buscar DJ...', 'apollo-events-manager'); ?>"
									autocomplete="off"
								>
								<i class="ri-search-line"></i>
							</div>
							<div class="apollo-select-list" id="apollo_dj_list">
								<?php
                                $all_djs = get_posts(
                                    [
                                        'post_type'      => 'event_dj',
                                        'posts_per_page' => -1,
                                        'orderby'        => 'title',
                                        'order'          => 'ASC',
                                        'post_status'    => 'publish',
                                    ]
                                );

        foreach ($all_djs as $dj) {
            $dj_name     = $dj->post_title;
            $is_selected = in_array($dj->ID, $current_djs, true);
            ?>
									<label class="apollo-select-item <?php echo $is_selected ? 'selected' : ''; ?>" data-dj-id="<?php echo $dj->ID; ?>" data-dj-name="<?php echo esc_attr(strtolower($dj_name)); ?>">
										<input 
											type="checkbox" 
											name="apollo_event_djs[]" 
											value="<?php echo $dj->ID; ?>"
											<?php checked($is_selected); ?>
										>
										<span class="apollo-select-item-label"><?php echo esc_html($dj_name); ?></span>
										<?php if ($is_selected) : ?>
											<i class="ri-check-line apollo-check-icon"></i>
										<?php endif; ?>
									</label>
									<?php
        }
        ?>
							</div>
							<div class="apollo-select-footer">
								<span class="apollo-selected-count" id="apollo_dj_selected_count">0 selecionados</span>
							</div>
						</div>
						<!-- Hidden select for form submission (backup) -->
						<select multiple="multiple" name="apollo_event_djs[]" id="apollo_event_djs" class="widefat" style="display:none;">
							<?php
                            foreach ($all_djs as $dj) {
                                $dj_name   = $dj->post_title;
                                $is_active = in_array($dj->ID, $current_djs, true);
                                if ($is_active) {
                                    printf('<option value="%d" selected>%s</option>', $dj->ID, esc_html($dj_name));
                                }
                            }
        ?>
						</select>
						<button type="button" class="button button-secondary" id="apollo_add_new_dj">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php _e('Adicionar novo DJ', 'apollo-events-manager'); ?>
						</button>
						<p class="description">
							<?php _e('Busque e selecione múltiplos DJs. Use o botão para adicionar um DJ novo ao banco de dados.', 'apollo-events-manager'); ?>
						</p>
					</div>
				</div>
				
				<!-- TIMETABLE DYNAMIC ROWS -->
				<div class="apollo-field">
					<label><?php _e('Timetable (Horários):', 'apollo-events-manager'); ?></label>
					<p class="description" style="margin-bottom:10px;">
						<?php _e('Arraste as linhas para reordenar ou use as setas. A ordem define a sequência de apresentação dos DJs.', 'apollo-events-manager'); ?>
					</p>
					<div id="apollo_timetable_container">
						<table class="widefat striped" id="apollo_timetable_table" style="display:none;">
							<thead>
								<tr>
									<th width="5%"><?php _e('#', 'apollo-events-manager'); ?></th>
									<th width="5%"><?php _e('Ordem', 'apollo-events-manager'); ?></th>
									<th width="35%"><?php _e('DJ', 'apollo-events-manager'); ?></th>
									<th width="25%"><?php _e('Começa às', 'apollo-events-manager'); ?></th>
									<th width="25%"><?php _e('Termina às', 'apollo-events-manager'); ?></th>
									<th width="5%"><?php _e('Ações', 'apollo-events-manager'); ?></th>
								</tr>
							</thead>
							<tbody id="apollo_timetable_rows" class="apollo-sortable-timetable">
								<!-- Dynamic rows inserted by JS -->
							</tbody>
						</table>
						<p id="apollo_timetable_empty" style="color:#999;padding:20px;background:#f9f9f9;border-radius:4px;">
							<?php _e('Selecione DJs acima primeiro. Os horários serão ordenados automaticamente ao salvar.', 'apollo-events-manager'); ?>
						</p>
						<button type="button" class="button" id="apollo_refresh_timetable" style="margin-top:10px;">
							<span class="dashicons dashicons-update"></span>
							<?php _e('Atualizar Timetable', 'apollo-events-manager'); ?>
						</button>
					</div>
				</div>
				
				<!-- Store timetable data as JSON -->
				<input type="hidden"
					name="apollo_event_timetable"
					id="apollo_event_timetable"
					value="<?php echo esc_attr($timetable_json); ?>">
			</div>
			
			<!-- ===== LOCAL SECTION ===== -->
			<div class="apollo-field-group" data-motion-group="true">
				<h3><?php _e('Local do Evento', 'apollo-events-manager'); ?> <span class="apollo-required" style="color:#d63638;">*</span></h3>
				<p class="description" style="margin-bottom:15px;color:#646970;">
					<strong><?php _e('Obrigatório:', 'apollo-events-manager'); ?></strong> <?php _e('Todo evento deve estar conectado a um local.', 'apollo-events-manager'); ?>
				</p>
				
				<div class="apollo-field">
					<label for="apollo_event_local"><?php _e('Local - Seleção obrigatória:', 'apollo-events-manager'); ?></label>
					<div class="apollo-field-controls">
						<!-- Enhanced Local Selector with Search -->
						<div class="apollo-enhanced-select apollo-single-select" data-motion-select="true">
							<div class="apollo-select-search">
								<input 
									type="text" 
									id="apollo_local_search" 
									class="apollo-search-input" 
									placeholder="<?php esc_attr_e('Buscar local...', 'apollo-events-manager'); ?>"
									autocomplete="off"
								>
								<i class="ri-search-line"></i>
							</div>
							<div class="apollo-select-list apollo-single-list" id="apollo_local_list">
								<?php
            $all_locals = get_posts(
                [
                    'post_type'      => 'event_local',
                    'posts_per_page' => -1,
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                    'post_status'    => 'publish',
                ]
            );

        if (empty($all_locals)) {
            echo '<p class="description" style="color:#d63638;padding:10px;background:#fff3cd;border-left:4px solid #d63638;">';
            echo '<strong>' . esc_html__('Atenção:', 'apollo-events-manager') . '</strong> ';
            echo esc_html__('Nenhum local (venue) cadastrado. Por favor, crie um local antes de salvar o evento.', 'apollo-events-manager');
            echo '</p>';
        } else {
            foreach ($all_locals as $local) {
                $local_name  = $local->post_title;
                $is_selected = in_array($local->ID, $current_local, true);
                ?>
										<label class="apollo-select-item <?php echo $is_selected ? 'selected' : ''; ?>" data-local-id="<?php echo $local->ID; ?>" data-local-name="<?php echo esc_attr(strtolower($local_name)); ?>">
											<input 
												type="radio" 
												name="apollo_event_local" 
												value="<?php echo $local->ID; ?>"
												<?php checked($is_selected); ?>
												required
											>
											<span class="apollo-select-item-label"><?php echo esc_html($local_name); ?></span>
											<?php if ($is_selected) : ?>
												<i class="ri-check-line apollo-check-icon"></i>
											<?php endif; ?>
										</label>
										<?php
            }
        }//end if
        ?>
							</div>
						</div>
						<!-- Hidden select for form submission (backup) - REQUIRED -->
						<select name="apollo_event_local" id="apollo_event_local" class="widefat" style="display:none;" required>
							<option value=""><?php _e('-- Selecione um local (obrigatório) --', 'apollo-events-manager'); ?></option>
							<?php
                            if (! empty($all_locals)) {
                                foreach ($all_locals as $local) {
                                    $local_name = $local->post_title;
                                    $is_active  = in_array($local->ID, $current_local, true);
                                    printf(
                                        '<option value="%d"%s>%s</option>',
                                        $local->ID,
                                        $is_active ? ' selected="selected"' : '',
                                        esc_html($local_name)
                                    );
                                }
                            }
        ?>
						</select>
						<button type="button" class="button button-secondary" id="apollo_add_new_local">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php _e('Adicionar novo Local', 'apollo-events-manager'); ?>
						</button>
						<p class="description">
							<?php _e('Busque e selecione um local. O local será geocodificado automaticamente ao salvar.', 'apollo-events-manager'); ?>
						</p>
					</div>
				</div>
			</div>
			
			<!-- ===== MEDIA SECTION ===== -->
			<div class="apollo-field-group">
				<h3><?php _e('Mídia', 'apollo-events-manager'); ?></h3>
				
				<div class="apollo-field">
					<label for="apollo_event_video_url"><?php _e('Event Video URL:', 'apollo-events-manager'); ?></label>
					<input 
						type="url" 
						name="apollo_event_video_url" 
						id="apollo_event_video_url" 
						class="widefat" 
						placeholder="https://www.youtube.com/watch?v=..."
						value="<?php echo esc_attr($event_video_url); ?>"
					>
					<p class="description">
						<?php _e('YouTube, Vimeo ou outro vídeo promocional (será exibido no hero da página do evento)', 'apollo-events-manager'); ?>
					</p>
				</div>
				
				<div class="apollo-field">
					<label for="apollo_event_banner"><?php _e('Event Banner (URL):', 'apollo-events-manager'); ?></label>
					<input 
						type="url" 
						name="apollo_event_banner" 
						id="apollo_event_banner" 
						class="widefat" 
						placeholder="https://..."
						value="<?php echo esc_attr($event_banner); ?>"
					>
					<p class="description">
						<?php _e('URL da imagem principal do evento (ou use Featured Image)', 'apollo-events-manager'); ?>
					</p>
				</div>
			</div>
			
			<!-- ===== TICKETING & PROMO SECTION ===== -->
			<div class="apollo-field-group">
				<h3><?php _e('Ingressos e Promoção', 'apollo-events-manager'); ?></h3>
				
				<div class="apollo-field">
					<label for="apollo_tickets_ext"><?php _e('Link de Ingressos (URL Externa):', 'apollo-events-manager'); ?></label>
					<input 
						type="url" 
						name="apollo_tickets_ext" 
						id="apollo_tickets_ext" 
						class="widefat" 
						placeholder="https://sympla.com.br/..."
						value="<?php echo esc_attr($tickets_ext); ?>"
					>
					<p class="description">
						<?php _e('Link para compra de ingressos (Sympla, Eventbrite, etc)', 'apollo-events-manager'); ?>
					</p>
				</div>
				
				<div class="apollo-field">
					<label for="apollo_cupom_ario"><?php _e('Cupom Apollo:', 'apollo-events-manager'); ?></label>
					<input 
						type="text" 
						name="apollo_cupom_ario" 
						id="apollo_cupom_ario" 
						class="widefat" 
						placeholder="Ex: APOLLO, APOLLO2025"
						value="<?php echo esc_attr($cupom_ario); ?>"
					>
					<p class="description">
						<?php _e('Código do cupom de desconto Apollo (deixe vazio se não houver)', 'apollo-events-manager'); ?>
					</p>
				</div>
			</div>
			
			<!-- ===== LOCATION TEXT SECTION ===== -->
			<div class="apollo-field-group">
				<h3><?php _e('Localização Adicional', 'apollo-events-manager'); ?></h3>
				
				<div class="apollo-field">
					<label for="apollo_event_location"><?php _e('Localização (texto):', 'apollo-events-manager'); ?></label>
					<input 
						type="text" 
						name="apollo_event_location" 
						id="apollo_event_location" 
						class="widefat" 
						placeholder="Nome do local | Área"
						value="<?php echo esc_attr($event_location); ?>"
					>
					<p class="description">
						<?php _e('Texto alternativo de localização (usado se nenhum Local post selecionado acima)', 'apollo-events-manager'); ?>
					</p>
				</div>
				
				<div class="apollo-field">
					<label for="apollo_event_country"><?php _e('País:', 'apollo-events-manager'); ?></label>
					<input 
						type="text" 
						name="apollo_event_country" 
						id="apollo_event_country" 
						class="widefat" 
						value="<?php echo esc_attr($event_country ?: 'Brasil'); ?>"
					>
				</div>
			</div>
			
			<!-- ===== PROMO IMAGES SECTION ===== -->
			<div class="apollo-field-group">
				<h3><?php _e('Imagens Promocionais', 'apollo-events-manager'); ?></h3>
				
				<div class="apollo-field">
					<label><?php _e('3 Imagens Promo (URLs):', 'apollo-events-manager'); ?></label>
					<div id="apollo_promo_images_container" class="apollo-multi-image-input">
						<?php
                        // Ensure we have exactly 3 slots
                        for ($i = 0; $i < 3; $i++) {
                            $image_url = isset($promo_images[ $i ]) ? esc_url($promo_images[ $i ]) : '';
                            ?>
							<div class="apollo-image-input-row" data-motion-input="true">
								<input 
									type="url" 
									name="apollo_3_imagens_promo[]" 
									class="widefat apollo-image-url-input" 
									placeholder="https://..."
									value="<?php echo $image_url; ?>"
								>
								<button type="button" class="button apollo-upload-image-btn" data-index="<?php echo $i; ?>">
									<span class="dashicons dashicons-upload"></span>
								</button>
								<?php if (! empty($image_url)) : ?>
									<button type="button" class="button apollo-preview-image-btn" data-url="<?php echo esc_attr($image_url); ?>">
										<span class="dashicons dashicons-visibility"></span>
									</button>
								<?php endif; ?>
							</div>
							<?php
                        }//end for
        ?>
					</div>
					<p class="description">
						<?php _e('Adicione até 3 URLs de imagens promocionais do evento', 'apollo-events-manager'); ?>
					</p>
				</div>
				
				<div class="apollo-field">
					<label for="apollo_imagem_final"><?php _e('Imagem Final (URL):', 'apollo-events-manager'); ?></label>
					<div class="apollo-field-controls">
						<input 
							type="url" 
							name="apollo_imagem_final" 
							id="apollo_imagem_final" 
							class="widefat" 
							placeholder="https://..."
							value="<?php echo esc_url($final_image); ?>"
						>
						<button type="button" class="button apollo-upload-image-btn" data-target="apollo_imagem_final">
							<span class="dashicons dashicons-upload"></span>
							<?php _e('Upload', 'apollo-events-manager'); ?>
						</button>
						<?php if (! empty($final_image)) : ?>
							<button type="button" class="button apollo-preview-image-btn" data-url="<?php echo esc_attr($final_image); ?>">
								<span class="dashicons dashicons-visibility"></span>
								<?php _e('Preview', 'apollo-events-manager'); ?>
							</button>
						<?php endif; ?>
					</div>
					<p class="description">
						<?php _e('Imagem final exibida no final da página do evento', 'apollo-events-manager'); ?>
					</p>
				</div>
			</div>
			
		</div>
		
		<!-- ===== ADD NEW DJ DIALOG ===== -->
		<div id="apollo_add_dj_dialog" style="display:none;" title="<?php esc_attr_e('Adicionar novo DJ', 'apollo-events-manager'); ?>">
			<form id="apollo_add_dj_form">
				<p>
					<label for="new_dj_name"><?php _e('Nome do DJ:', 'apollo-events-manager'); ?></label>
					<input type="text" name="new_dj_name" id="new_dj_name" class="widefat" placeholder="<?php esc_attr_e('Ex: Marta Supernova', 'apollo-events-manager'); ?>">
				</p>
				<p class="description">
					<?php _e('O sistema verificará automaticamente se o DJ já existe (ignorando maiúsculas/minúsculas)', 'apollo-events-manager'); ?>
				</p>
				<div id="apollo_dj_form_message" style="display:none;margin-top:10px;"></div>
			</form>
		</div>
		
		<!-- ===== ADD NEW LOCAL DIALOG ===== -->
		<div id="apollo_add_local_dialog" style="display:none;" title="<?php esc_attr_e('Adicionar novo Local', 'apollo-events-manager'); ?>">
			<form id="apollo_add_local_form">
				<p>
					<label for="new_local_name"><?php _e('Nome do Local:', 'apollo-events-manager'); ?></label>
					<input type="text" name="new_local_name" id="new_local_name" class="widefat" placeholder="<?php esc_attr_e('Ex: D-Edge', 'apollo-events-manager'); ?>">
				</p>
				<p>
					<label for="new_local_address"><?php _e('Endereço:', 'apollo-events-manager'); ?></label>
					<input type="text" name="new_local_address" id="new_local_address" class="widefat" placeholder="<?php esc_attr_e('Rua, número', 'apollo-events-manager'); ?>">
				</p>
				<p>
					<label for="new_local_city"><?php _e('Cidade:', 'apollo-events-manager'); ?></label>
					<input type="text" name="new_local_city" id="new_local_city" class="widefat" placeholder="<?php esc_attr_e('Ex: Rio de Janeiro', 'apollo-events-manager'); ?>">
				</p>
				<p class="description">
					<?php _e('O sistema verificará duplicados e fará geocoding automático com OpenStreetMap', 'apollo-events-manager'); ?>
				</p>
				<div id="apollo_local_form_message" style="display:none;margin-top:10px;"></div>
			</form>
		</div>
		
		<?php
    }

    /**
     * Save event metabox data
     * CRITICAL: This function saves DJs, Local, and all event metadata
     */
    public function save_metabox_data($post_id, $post)
    {
        // Security checks
        if (! isset($_POST['apollo_event_meta_nonce']) || ! wp_verify_nonce($_POST['apollo_event_meta_nonce'], 'apollo_event_meta_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        // Debug snapshot to troubleshoot meta not saving in admin.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $posted_djs   = isset($_POST['apollo_event_djs']) ? wp_unslash($_POST['apollo_event_djs']) : null;
            $posted_local = isset($_POST['apollo_event_local']) ? wp_unslash($_POST['apollo_event_local']) : null;
            error_log(sprintf('[Apollo Events] save_metabox_data event_id=%d djs_posted=%s local_posted=%s', $post_id, wp_json_encode($posted_djs), wp_json_encode($posted_local)));
        }

        // Initialize favorites count if not set
        $favorites_count = apollo_get_post_meta($post_id, '_favorites_count', true);
        if ($favorites_count === '' || $favorites_count === false) {
            apollo_update_post_meta($post_id, '_favorites_count', 0);
        }

        // FASE 2: Salvar gestão
        if (isset($_POST['apollo_event_gestao']) && is_array($_POST['apollo_event_gestao'])) {
            $gestao = array_map('absint', $_POST['apollo_event_gestao']);
            $gestao = array_filter($gestao);
            // Remove zeros
            apollo_update_post_meta($post_id, '_event_gestao', $gestao);
        } else {
            // Se não foi enviado, limpar gestão
            apollo_update_post_meta($post_id, '_event_gestao', []);
        }

        // ✅ SAVE DJs - CRITICAL
        $djs_selected = isset($_POST['apollo_event_djs']) && is_array($_POST['apollo_event_djs'])
            ? array_map('absint', $_POST['apollo_event_djs'])
            : [];

        $djs_selected = array_filter($djs_selected);
        // Remove zeros

        if (! empty($djs_selected)) {
            // Normalize to array and save
            apollo_update_post_meta($post_id, '_event_dj_ids', $djs_selected);

            // Debug log for admins
            if (current_user_can('administrator') && defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('[Apollo Events] Event %d: Saved DJ IDs: %s', $post_id, implode(', ', $djs_selected)));
            }
        } else {
            // Clear if empty
            apollo_delete_post_meta($post_id, '_event_dj_ids');
            if (current_user_can('administrator') && defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('[Apollo Events] Event %d: Cleared DJ IDs', $post_id));
            }
        }

        // ✅ SAVE LOCAL - CRITICAL
        $local_selected = isset($_POST['apollo_event_local']) ? absint($_POST['apollo_event_local']) : 0;

        if ($local_selected > 0) {
            // Save as single integer (not array) - consistent with database structure
            // Use unified connection manager
            if (class_exists('Apollo_Local_Connection')) {
                $connection = Apollo_Local_Connection::get_instance();
                $connection->set_local_id($post_id, $local_selected);
            } else {
                // Fallback to direct meta update
                apollo_update_post_meta($post_id, '_event_local_ids', $local_selected);
            }

            if (current_user_can('administrator') && defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('[Apollo Events] Event %d: Saved Local ID: %d', $post_id, $local_selected));
            }
        } else {
            // Clear if empty
            apollo_delete_post_meta($post_id, '_event_local_ids');
            if (current_user_can('administrator') && defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('[Apollo Events] Event %d: Cleared Local ID', $post_id));
            }
        }//end if

        // ✅ SAVE TIMETABLE
        $timetable_json = isset($_POST['apollo_event_timetable']) ? sanitize_text_field($_POST['apollo_event_timetable']) : '';
        if (! empty($timetable_json)) {
            $timetable = json_decode(stripslashes($timetable_json), true);
            if (is_array($timetable) && ! empty($timetable)) {
                // Sanitize timetable before saving
                $clean_timetable = apollo_sanitize_timetable($timetable);
                if (! empty($clean_timetable)) {
                    apollo_update_post_meta($post_id, '_event_timetable', $clean_timetable);
                } else {
                    // If sanitization removes all entries but we have DJs, save empty array to preserve structure
                    apollo_update_post_meta($post_id, '_event_timetable', []);
                }
            } else {
                apollo_delete_post_meta($post_id, '_event_timetable');
            }
        } else {
            // Don't delete timetable if empty - might have DJs without times
            // Only delete if explicitly empty
        }

        // Save dates
        if (isset($_POST['apollo_event_start_date'])) {
            apollo_update_post_meta($post_id, '_event_start_date', sanitize_text_field($_POST['apollo_event_start_date']));
        }
        if (isset($_POST['apollo_event_end_date'])) {
            apollo_update_post_meta($post_id, '_event_end_date', sanitize_text_field($_POST['apollo_event_end_date']));
        }
        if (isset($_POST['apollo_event_start_time'])) {
            apollo_update_post_meta($post_id, '_event_start_time', sanitize_text_field($_POST['apollo_event_start_time']));
        }
        if (isset($_POST['apollo_event_end_time'])) {
            apollo_update_post_meta($post_id, '_event_end_time', sanitize_text_field($_POST['apollo_event_end_time']));
        }

        // Save media
        if (isset($_POST['apollo_event_video_url'])) {
            $video_url = esc_url_raw($_POST['apollo_event_video_url']);
            if (! empty($video_url)) {
                apollo_update_post_meta($post_id, '_event_video_url', $video_url);
            } else {
                apollo_delete_post_meta($post_id, '_event_video_url');
            }
        }
        if (isset($_POST['apollo_event_banner'])) {
            $banner = esc_url_raw($_POST['apollo_event_banner']);
            if (! empty($banner)) {
                apollo_update_post_meta($post_id, '_event_banner', $banner);
            } else {
                apollo_delete_post_meta($post_id, '_event_banner');
            }
        }

        // Save other fields
        if (isset($_POST['apollo_event_location'])) {
            $location = sanitize_text_field($_POST['apollo_event_location']);
            if (! empty($location)) {
                apollo_update_post_meta($post_id, '_event_location', $location);
            } else {
                apollo_delete_post_meta($post_id, '_event_location');
            }
        }
        if (isset($_POST['apollo_event_country'])) {
            apollo_update_post_meta($post_id, '_event_country', sanitize_text_field($_POST['apollo_event_country']));
        }
        if (isset($_POST['apollo_tickets_ext'])) {
            $tickets = esc_url_raw($_POST['apollo_tickets_ext']);
            if (! empty($tickets)) {
                apollo_update_post_meta($post_id, '_tickets_ext', $tickets);
            } else {
                apollo_delete_post_meta($post_id, '_tickets_ext');
            }
        }
        // Save Cupom Apollo (text field)
        if (isset($_POST['apollo_cupom_ario'])) {
            $cupom = sanitize_text_field($_POST['apollo_cupom_ario']);
            if (! empty($cupom)) {
                apollo_update_post_meta($post_id, '_cupom_ario', $cupom);
            } else {
                apollo_delete_post_meta($post_id, '_cupom_ario');
            }
        }

        // Save 3 Imagens Promo (array of URLs)
        if (isset($_POST['apollo_3_imagens_promo']) && is_array($_POST['apollo_3_imagens_promo'])) {
            $promo_images = [];
            foreach ($_POST['apollo_3_imagens_promo'] as $image_url) {
                $clean_url = esc_url_raw(trim($image_url));
                if (! empty($clean_url)) {
                    $promo_images[] = $clean_url;
                }
            }
            if (! empty($promo_images)) {
                apollo_update_post_meta($post_id, '_3_imagens_promo', $promo_images);
            } else {
                apollo_delete_post_meta($post_id, '_3_imagens_promo');
            }
        } else {
            apollo_delete_post_meta($post_id, '_3_imagens_promo');
        }

        // Save Imagem Final (URL)
        if (isset($_POST['apollo_imagem_final'])) {
            $final_image = esc_url_raw($_POST['apollo_imagem_final']);
            if (! empty($final_image)) {
                apollo_update_post_meta($post_id, '_imagem_final', $final_image);
            } else {
                apollo_delete_post_meta($post_id, '_imagem_final');
            }
        }

        // CRITICAL: Limpar cache após salvar (garante que mudanças apareçam imediatamente)
        clean_post_cache($post_id);

        // CRITICAL: Limpar todos os caches relacionados usando função centralizada
        if (function_exists('apollo_clear_events_cache')) {
            apollo_clear_events_cache($post_id);
        } else {
            // Fallback: limpar transients conhecidos diretamente
            delete_transient('apollo_events_portal_cache');
            delete_transient('apollo_events_home_cache');
            // CRITICAL: Clear both old and new cache keys
            delete_transient('apollo_upcoming_event_ids_' . date('Ymd'));
            delete_transient('apollo_all_event_ids_' . date('Ymd'));
            // Clear cache for all dates (past 7 days)
            for ($i = 0; $i < 7; $i++) {
                $date_key = date('Ymd', strtotime("-$i days"));
                delete_transient('apollo_upcoming_event_ids_' . $date_key);
                delete_transient('apollo_all_event_ids_' . $date_key);
            }

            // Limpar cache do grupo apollo_events
            if (function_exists('wp_cache_flush_group')) {
                wp_cache_flush_group('apollo_events');
            }
        }//end if
    }

    /**
     * Save DJ meta
     */
    public function save_dj_meta($post_id, $post)
    {
        if (! isset($_POST['apollo_dj_meta_nonce']) || ! wp_verify_nonce($_POST['apollo_dj_meta_nonce'], 'apollo_dj_meta_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        $data = isset($_POST['apollo_dj']) && is_array($_POST['apollo_dj']) ? wp_unslash($_POST['apollo_dj']) : [];

        // Bio agora usa post_content nativo, não precisa de textarea separado
        $textarea_keys = [];

        foreach ($data as $meta_key => $value) {
            $value = is_string($value) ? trim($value) : '';

            if ($value === '') {
                apollo_delete_post_meta($post_id, $meta_key);

                continue;
            }

            if (in_array($meta_key, $textarea_keys, true)) {
                $clean = wp_kses_post($value);
            } elseif (strpos($meta_key, '_url') !== false || in_array($meta_key, [ '_dj_website', '_dj_soundcloud', '_dj_spotify', '_dj_youtube', '_dj_mixcloud', '_dj_beatport', '_dj_bandcamp', '_dj_resident_advisor', '_dj_facebook' ], true)) {
                $clean = esc_url_raw($value);
            } else {
                $clean = sanitize_text_field($value);
            }

            apollo_update_post_meta($post_id, $meta_key, $clean);
        }

        // Ensure empty non-submitted fields are cleared
        // NOTA: _dj_name e _dj_bio removidos - usar post_title e post_content
        $expected_meta = [
            '_dj_image',
            '_dj_banner',
            '_dj_website',
            '_dj_instagram',
            '_dj_facebook',
            '_dj_soundcloud',
            '_dj_bandcamp',
            '_dj_spotify',
            '_dj_youtube',
            '_dj_mixcloud',
            '_dj_beatport',
            '_dj_resident_advisor',
            '_dj_twitter',
            '_dj_tiktok',
            '_dj_original_project_1',
            '_dj_original_project_2',
            '_dj_original_project_3',
            '_dj_set_url',
            '_dj_media_kit_url',
            '_dj_rider_url',
            '_dj_mix_url',
        ];

        foreach ($expected_meta as $meta_key) {
            if (! array_key_exists($meta_key, $data)) {
                apollo_delete_post_meta($post_id, $meta_key);
            }
        }
    }

    /**
     * Save Local meta
     */
    public function save_local_meta($post_id, $post)
    {
        if (! isset($_POST['apollo_local_meta_nonce']) || ! wp_verify_nonce($_POST['apollo_local_meta_nonce'], 'apollo_local_meta_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        $data = isset($_POST['apollo_local']) && is_array($_POST['apollo_local']) ? wp_unslash($_POST['apollo_local']) : [];

        // Descrição agora usa post_content nativo
        $textareas = [];
        $url_keys  = [ '_local_website' ];

        foreach ($data as $meta_key => $value) {
            $value = is_string($value) ? trim($value) : '';

            if ($value === '') {
                apollo_delete_post_meta($post_id, $meta_key);

                continue;
            }

            if (in_array($meta_key, $textareas, true)) {
                $clean = wp_kses_post($value);
            } elseif (in_array($meta_key, $url_keys, true)) {
                $clean = esc_url_raw($value);
            } else {
                $clean = sanitize_text_field($value);
            }

            apollo_update_post_meta($post_id, $meta_key, $clean);
        }

        // Latitude/Longitude legacy mirrors
        $lat = isset($data['_local_latitude']) ? trim($data['_local_latitude']) : '';
        $lng = isset($data['_local_longitude']) ? trim($data['_local_longitude']) : '';

        if ($lat !== '') {
            apollo_update_post_meta($post_id, '_local_lat', sanitize_text_field($lat));
        } else {
            apollo_delete_post_meta($post_id, '_local_lat');
        }

        if ($lng !== '') {
            apollo_update_post_meta($post_id, '_local_lng', sanitize_text_field($lng));
        } else {
            apollo_delete_post_meta($post_id, '_local_lng');
        }

        // Social fields not posted should be cleared
        // NOTA: _local_name e _local_description removidos - usar post_title e post_content
        $expected_local_meta = [
            '_local_address',
            '_local_city',
            '_local_state',
            '_local_latitude',
            '_local_longitude',
            '_local_website',
            '_local_instagram',
            '_local_facebook',
        ];

        foreach ($expected_local_meta as $meta_key) {
            if (! array_key_exists($meta_key, $data)) {
                apollo_delete_post_meta($post_id, $meta_key);
            }
        }

        $image_data = isset($_POST['apollo_local_images']) && is_array($_POST['apollo_local_images']) ? wp_unslash($_POST['apollo_local_images']) : [];
        for ($i = 1; $i <= 5; $i++) {
            $key   = '_local_image_' . $i;
            $value = isset($image_data[ $i ]) ? trim((string) $image_data[ $i ]) : '';
            if ($value === '') {
                apollo_delete_post_meta($post_id, $key);
            } else {
                apollo_update_post_meta($post_id, $key, sanitize_text_field($value));
            }
        }
    }

    /**
     * AJAX: Add new DJ (with duplicate check)
     */
    public function ajax_add_new_dj()
    {
        check_ajax_referer('apollo_admin_nonce', 'nonce');

        if (! current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');

            return;
        }

        // SECURITY: Sanitize input with proper unslashing
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';

        if (empty($name)) {
            wp_send_json_error(__('Por favor, digite um nome', 'apollo-events-manager'));
        }

        // Normalize for comparison (case-insensitive)
        $normalized = mb_strtolower(trim($name), 'UTF-8');

        // Check duplicates
        $existing = get_posts(
            [
                'post_type'      => 'event_dj',
                'posts_per_page' => -1,
                'post_status'    => 'any',
            ]
        );

        foreach ($existing as $dj) {
            $existing_title = mb_strtolower(trim($dj->post_title), 'UTF-8');

            if ($existing_title === $normalized) {
                wp_send_json_error(
                    sprintf(
                        __('DJ %1$s já está registrado com slug %2$s', 'apollo-events-manager'),
                        $dj->post_title,
                        $dj->post_name
                    )
                );
            }
        }

        // Create new DJ
        $new_dj_id = wp_insert_post(
            [
                'post_type'   => 'event_dj',
                'post_title'  => $name,
                'post_status' => 'publish',
            ]
        );

        if (is_wp_error($new_dj_id)) {
            wp_send_json_error(__('Erro ao criar DJ', 'apollo-events-manager'));
        }

        // Nome usa post_title nativo, não precisa de meta separado

        wp_send_json_success(
            [
                'id'   => $new_dj_id,
                'name' => $name,
                'slug' => get_post($new_dj_id)->post_name,
            ]
        );
    }

    /**
     * AJAX: Add new Local (with duplicate check)
     */
    public function ajax_add_new_local()
    {
        check_ajax_referer('apollo_admin_nonce', 'nonce');

        if (! current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');

            return;
        }

        // SECURITY: Sanitize inputs with proper unslashing
        $name    = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $address = isset($_POST['address']) ? sanitize_text_field(wp_unslash($_POST['address'])) : '';
        $city    = isset($_POST['city']) ? sanitize_text_field(wp_unslash($_POST['city'])) : '';

        if (empty($name)) {
            wp_send_json_error(__('Por favor, digite um nome', 'apollo-events-manager'));
        }

        // Normalize for comparison
        $normalized = mb_strtolower(trim($name), 'UTF-8');

        // Check duplicates
        $existing = get_posts(
            [
                'post_type'      => 'event_local',
                'posts_per_page' => -1,
                'post_status'    => 'any',
            ]
        );

        foreach ($existing as $local) {
            $existing_title = mb_strtolower(trim($local->post_title), 'UTF-8');

            if ($existing_title === $normalized) {
                wp_send_json_error(
                    sprintf(
                        __('Local %1$s já está registrado com slug %2$s', 'apollo-events-manager'),
                        $local->post_title,
                        $local->post_name
                    )
                );
            }
        }

        // Create new Local
        $new_local_id = wp_insert_post(
            [
                'post_type'   => 'event_local',
                'post_title'  => $name,
                'post_status' => 'publish',
            ]
        );

        if (is_wp_error($new_local_id)) {
            wp_send_json_error(__('Erro ao criar Local', 'apollo-events-manager'));
        }

        // Nome usa post_title nativo, não precisa de meta separado
        // Salva apenas address e city que são campos separados
        if ($address) {
            apollo_update_post_meta($new_local_id, '_local_address', $address);
        }
        if ($city) {
            apollo_update_post_meta($new_local_id, '_local_city', $city);
        }

        // Auto-geocode will trigger on save_post hook

        wp_send_json_success(
            [
                'id'   => $new_local_id,
                'name' => $name,
                'slug' => get_post($new_local_id)->post_name,
            ]
        );
    }

    /**
     * FASE 2: Render metabox de Colaboradores/Co-autores
     */
    public function render_gestao_metabox($post)
    {
        wp_nonce_field('apollo_event_meta_save', 'apollo_event_meta_nonce');

        $gestao = apollo_get_post_meta($post->ID, '_event_gestao', true);
        $gestao = is_array($gestao) ? array_map('absint', $gestao) : [];

        // Buscar todos os usuários (limitado para performance)
        $all_users = get_users(
            [
                'orderby' => 'display_name',
                'order'   => 'ASC',
                'number'  => 500,
            ]
        );

        ?>
		<div class="apollo-gestao-metabox">
			<p class="description" style="margin-bottom: 15px;">
				<?php esc_html_e('Selecione usuários que podem visualizar e editar este evento.', 'apollo-events-manager'); ?>
			</p>
			
			<select 
				multiple 
				name="apollo_event_gestao[]" 
				id="apollo_event_gestao" 
				class="widefat" 
				size="8"
				style="width: 100%;"
			>
				<?php
                foreach ($all_users as $user) {
                    $selected = in_array($user->ID, $gestao) ? 'selected' : '';
                    $display  = $user->display_name . ' (' . $user->user_email . ')';
                    echo '<option value="' . esc_attr($user->ID) . '" ' . $selected . '>' . esc_html($display) . '</option>';
                }
        ?>
			</select>
			
			<p class="description" style="margin-top: 10px;">
				<i class="ri-information-line"></i> 
				<?php esc_html_e('Segure Ctrl (Windows) ou Cmd (Mac) para selecionar múltiplos usuários.', 'apollo-events-manager'); ?>
			</p>
			
			<?php if (! empty($gestao)) : ?>
				<div style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
					<strong><?php esc_html_e('Gestão atual:', 'apollo-events-manager'); ?></strong>
					<ul style="margin: 5px 0 0 0; padding-left: 20px;">
						<?php
                foreach ($gestao as $user_id) {
                    $user = get_user_by('ID', $user_id);
                    if ($user) {
                        echo '<li>' . esc_html($user->display_name) . '</li>';
                    }
                }
			    ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
		<?php
    }
}

// Initialize only in admin
if (is_admin()) {
    new Apollo_Events_Admin_Metaboxes();
}
