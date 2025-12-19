<?php
// phpcs:ignoreFile
/**
 * Apollo Events Manager - Admin Meta Keys Page
 *
 * Lists all meta keys used by the plugin with descriptions and examples
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Register admin page for meta keys
 */
function apollo_events_register_metakeys_page()
{
    add_submenu_page(
        'edit.php?post_type=event_listing',
        __('Meta Keys Apollo Events', 'apollo-events-manager'),
        __('Meta Keys', 'apollo-events-manager'),
        'manage_options',
        'apollo-events-metakeys',
        'apollo_events_render_metakeys_page'
    );
}
add_action('admin_menu', 'apollo_events_register_metakeys_page', 21);

/**
 * Render meta keys admin page
 */
function apollo_events_render_metakeys_page()
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $meta_keys = apollo_events_get_all_metakeys();

    ?>
	<div class="wrap">
		<h1>
			<span class="dashicons dashicons-admin-settings" style="font-size: 24px; vertical-align: middle;"></span>
			<?php echo esc_html__('Apollo Events - Meta Keys', 'apollo-events-manager'); ?>
		</h1>
		
		<div class="apollo-metakeys-container" style="margin-top: 20px;">
			
			<!-- Info Box -->
			<div class="notice notice-info" style="margin: 20px 0;">
				<p>
					<strong><?php echo esc_html__('Como usar:', 'apollo-events-manager'); ?></strong>
					<?php echo esc_html__('Use estas meta keys com get_post_meta() ou apollo_get_post_meta() para acessar dados dos eventos, DJs e locais.', 'apollo-events-manager'); ?>
				</p>
			</div>
			
			<?php foreach ($meta_keys as $post_type => $keys) : ?>
				<div class="card" style="margin: 20px 0; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
					<h2 style="margin-top: 0; margin-bottom: 15px; color: #2271b1;">
						<span class="dashicons dashicons-<?php echo esc_attr($keys['icon']); ?>" style="vertical-align: middle;"></span>
						<?php echo esc_html($keys['title']); ?>
						<span style="font-size: 14px; font-weight: normal; color: #666;">
							(<?php echo esc_html($post_type); ?>)
						</span>
					</h2>
					
					<p style="color: #50575e; margin-bottom: 15px;">
						<?php echo esc_html($keys['description']); ?>
					</p>
					
					<table class="widefat" style="margin-top: 10px;">
						<thead>
							<tr>
								<th style="padding: 10px; width: 20%;"><?php echo esc_html__('Meta Key', 'apollo-events-manager'); ?></th>
								<th style="padding: 10px; width: 15%;"><?php echo esc_html__('Tipo', 'apollo-events-manager'); ?></th>
								<th style="padding: 10px; width: 10%;"><?php echo esc_html__('Obrigatório', 'apollo-events-manager'); ?></th>
								<th style="padding: 10px; width: 35%;"><?php echo esc_html__('Descrição', 'apollo-events-manager'); ?></th>
								<th style="padding: 10px; width: 20%;"><?php echo esc_html__('Exemplo', 'apollo-events-manager'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($keys['meta_keys'] as $meta_key) : ?>
								<tr>
									<td style="padding: 10px;">
										<code style="background: #f0f0f1; padding: 4px 8px; border-radius: 3px; font-size: 13px;">
											<?php echo esc_html($meta_key['key']); ?>
										</code>
									</td>
									<td style="padding: 10px;">
										<span style="color: #2271b1; font-weight: 500;">
											<?php echo esc_html($meta_key['type']); ?>
										</span>
									</td>
									<td style="padding: 10px; text-align: center;">
										<?php if (! empty($meta_key['required'])) : ?>
											<span style="color: #d63638;">✅</span>
										<?php else : ?>
											<span style="color: #999;">—</span>
										<?php endif; ?>
									</td>
									<td style="padding: 10px;">
										<?php echo esc_html($meta_key['description']); ?>
									</td>
									<td style="padding: 10px;">
										<?php if (! empty($meta_key['example'])) : ?>
											<code style="background: #f0f0f1; padding: 2px 6px; border-radius: 2px; font-size: 11px;">
												<?php echo esc_html($meta_key['example']); ?>
											</code>
										<?php else : ?>
											<span style="color: #999;">—</span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					
					<!-- Code Example -->
					<div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-radius: 4px;">
						<strong style="display: block; margin-bottom: 8px;">
							<?php echo esc_html__('Exemplo de uso:', 'apollo-events-manager'); ?>
						</strong>
						<pre style="margin: 0; overflow-x: auto; font-size: 12px;"><code>&lt;?php
// Usando apollo_get_post_meta() (recomendado - com sanitização)
$value = apollo_get_post_meta($post_id, '<?php echo esc_html($keys['meta_keys'][0]['key']); ?>', true);

// Ou usando get_post_meta() padrão do WordPress
$value = get_post_meta($post_id, '<?php echo esc_html($keys['meta_keys'][0]['key']); ?>', true);
?&gt;</code></pre>
					</div>
				</div>
			<?php endforeach; ?>
			
			<!-- User Meta Keys -->
			<div class="card" style="margin: 20px 0; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
				<h2 style="margin-top: 0; margin-bottom: 15px; color: #2271b1;">
					<span class="dashicons dashicons-admin-users" style="vertical-align: middle;"></span>
					<?php echo esc_html__('User Meta Keys', 'apollo-events-manager'); ?>
				</h2>
				
				<p style="color: #50575e; margin-bottom: 15px;">
					<?php echo esc_html__('Meta keys para dados de usuários (user_meta).', 'apollo-events-manager'); ?>
				</p>
				
				<table class="widefat" style="margin-top: 10px;">
					<thead>
						<tr>
							<th style="padding: 10px; width: 20%;"><?php echo esc_html__('Meta Key', 'apollo-events-manager'); ?></th>
							<th style="padding: 10px; width: 15%;"><?php echo esc_html__('Tipo', 'apollo-events-manager'); ?></th>
							<th style="padding: 10px; width: 35%;"><?php echo esc_html__('Descrição', 'apollo-events-manager'); ?></th>
							<th style="padding: 10px; width: 30%;"><?php echo esc_html__('Exemplo', 'apollo-events-manager'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
                        $user_meta_keys = apollo_events_get_user_metakeys();
    foreach ($user_meta_keys as $meta_key) :
        ?>
							<tr>
								<td style="padding: 10px;">
									<code style="background: #f0f0f1; padding: 4px 8px; border-radius: 3px; font-size: 13px;">
										<?php echo esc_html($meta_key['key']); ?>
									</code>
								</td>
								<td style="padding: 10px;">
									<span style="color: #2271b1; font-weight: 500;">
										<?php echo esc_html($meta_key['type']); ?>
									</span>
								</td>
								<td style="padding: 10px;">
									<?php echo esc_html($meta_key['description']); ?>
								</td>
								<td style="padding: 10px;">
									<?php if (! empty($meta_key['example'])) : ?>
										<code style="background: #f0f0f1; padding: 2px 6px; border-radius: 2px; font-size: 11px;">
											<?php echo esc_html($meta_key['example']); ?>
										</code>
									<?php else : ?>
										<span style="color: #999;">—</span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				
				<div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-radius: 4px;">
					<strong style="display: block; margin-bottom: 8px;">
						<?php echo esc_html__('Exemplo de uso:', 'apollo-events-manager'); ?>
					</strong>
					<pre style="margin: 0; overflow-x: auto; font-size: 12px;"><code>&lt;?php
$user_id = get_current_user_id();
$bio = get_user_meta($user_id, 'bio_full', true);
$tagline = get_user_meta($user_id, '_apollo_tagline', true);
?&gt;</code></pre>
				</div>
			</div>
			
		</div>
	</div>
	<?php
}

/**
 * Get all meta keys organized by post type
 *
 * @return array Meta keys organized by post type
 *
 * NOTE: This function may also be defined in admin-apollo-hub.php
 * Using function_exists() to prevent redeclaration errors
 */
if (! function_exists('apollo_events_get_all_metakeys')) {
    function apollo_events_get_all_metakeys()
    {
        return [
            'event_listing' => [
                'title'       => __('Event Meta Keys', 'apollo-events-manager'),
                'description' => __('Meta keys para eventos (event_listing CPT).', 'apollo-events-manager'),
                'icon'        => 'calendar-alt',
                'meta_keys'   => [
                    [
                        'key'         => '_event_start_date',
                        'type'        => 'datetime',
                        'required'    => true,
                        'description' => __('Data e hora de início do evento (formato: YYYY-MM-DD HH:MM:SS)', 'apollo-events-manager'),
                        'example'     => '2025-11-15 22:00:00',
                    ],
                    [
                        'key'         => '_event_end_date',
                        'type'        => 'datetime',
                        'required'    => false,
                        'description' => __('Data e hora de término do evento', 'apollo-events-manager'),
                        'example'     => '2025-11-16 06:00:00',
                    ],
                    [
                        'key'         => '_event_start_time',
                        'type'        => 'time',
                        'required'    => true,
                        'description' => __('Horário de início (formato: HH:MM:SS)', 'apollo-events-manager'),
                        'example'     => '22:00:00',
                    ],
                    [
                        'key'         => '_event_end_time',
                        'type'        => 'time',
                        'required'    => false,
                        'description' => __('Horário de término', 'apollo-events-manager'),
                        'example'     => '06:00:00',
                    ],
                    [
                        'key'         => '_event_banner',
                        'type'        => 'url/attachment_id',
                        'required'    => false,
                        'description' => __('URL do banner ou ID do attachment', 'apollo-events-manager'),
                        'example'     => 'https://exemplo.com/banner.jpg',
                    ],
                    [
                        'key'         => '_event_video_url',
                        'type'        => 'url',
                        'required'    => false,
                        'description' => __('URL do vídeo (YouTube/Vimeo)', 'apollo-events-manager'),
                        'example'     => 'https://youtube.com/watch?v=...',
                    ],
                    [
                        'key'         => '_event_location',
                        'type'        => 'string',
                        'required'    => false,
                        'description' => __('Localização em texto (fallback se não houver local)', 'apollo-events-manager'),
                        'example'     => 'Rio de Janeiro, RJ',
                    ],
                    [
                        'key'         => '_event_dj_ids',
                        'type'        => 'array',
                        'required'    => false,
                        'description' => __('Array de IDs dos DJs do evento', 'apollo-events-manager'),
                        'example'     => '[92, 71, 45]',
                    ],
                    [
                        'key'         => '_event_local_ids',
                        'type'        => 'integer',
                        'required'    => false,
                        'description' => __('ID do local (event_local CPT)', 'apollo-events-manager'),
                        'example'     => '95',
                    ],
                    [
                        'key'         => '_event_timetable',
                        'type'        => 'array',
                        'required'    => false,
                        'description' => __('Programação com DJs e horários (array serializado)', 'apollo-events-manager'),
                        'example'     => '[{dj: 92, from: "22:00", to: "23:00"}]',
                    ],
                    [
                        'key'         => '_tickets_ext',
                        'type'        => 'url',
                        'required'    => false,
                        'description' => __('URL externa de ingressos', 'apollo-events-manager'),
                        'example'     => 'https://sympla.com.br/...',
                    ],
                    [
                        'key'         => '_cupom_ario',
                        'type'        => 'string',
                        'required'    => false,
                        'description' => __('Flag de cupom Apollo (0 ou 1)', 'apollo-events-manager'),
                        'example'     => '1',
                    ],
                    [
                        'key'         => '_3_imagens_promo',
                        'type'        => 'array',
                        'required'    => false,
                        'description' => __('Array com 3 URLs de imagens promocionais', 'apollo-events-manager'),
                        'example'     => '["url1.jpg", "url2.jpg", "url3.jpg"]',
                    ],
                    [
                        'key'         => '_imagem_final',
                        'type'        => 'url/attachment_id',
                        'required'    => false,
                        'description' => __('URL ou ID da imagem final', 'apollo-events-manager'),
                        'example'     => 'https://exemplo.com/final.jpg',
                    ],
                    [
                        'key'         => '_favorites_count',
                        'type'        => 'integer',
                        'required'    => false,
                        'description' => __('Contador de favoritos', 'apollo-events-manager'),
                        'example'     => '42',
                    ],
                    [
                        'key'         => '_apollo_event_views_total',
                        'type'        => 'integer',
                        'required'    => false,
                        'description' => __('Total de visualizações do evento', 'apollo-events-manager'),
                        'example'     => '1234',
                    ],
                    [
                        'key'         => '_event_gestao',
                        'type'        => 'array',
                        'required'    => false,
                        'description' => __('Array de user IDs dos co-autores', 'apollo-events-manager'),
                        'example'     => '[1, 5, 10]',
                    ],
                ],
            ],
            'event_dj' => [
                'title'       => __('DJ Meta Keys', 'apollo-events-manager'),
                'description' => __('Meta keys para DJs (event_dj CPT).', 'apollo-events-manager'),
                'icon'        => 'microphone',
                'meta_keys'   => [
                    [
                        'key'         => '_dj_name',
                        'type'        => 'string',
                        'required'    => false,
                        'description' => __('Nome artístico do DJ', 'apollo-events-manager'),
                        'example'     => 'DJ Alpha',
                    ],
                    [
                        'key'         => '_dj_tagline',
                        'type'        => 'string',
                        'required'    => false,
                        'description' => __('Tagline/frase do DJ', 'apollo-events-manager'),
                        'example'     => 'Techno & House Producer',
                    ],
                    [
                        'key'         => '_dj_roles',
                        'type'        => 'string',
                        'required'    => false,
                        'description' => __('Papéis do DJ (ex: Producer, DJ, Label Owner)', 'apollo-events-manager'),
                        'example'     => 'Producer, DJ',
                    ],
                    [
                        'key'         => '_dj_bio_excerpt',
                        'type'        => 'string',
                        'required'    => false,
                        'description' => __('Resumo da biografia', 'apollo-events-manager'),
                        'example'     => 'Breve descrição...',
                    ],
                    [
                        'key'         => '_dj_bio_full',
                        'type'        => 'text',
                        'required'    => false,
                        'description' => __('Biografia completa', 'apollo-events-manager'),
                        'example'     => 'Biografia completa do DJ...',
                    ],
                    [
                        'key'         => '_dj_soundcloud_track',
                        'type'        => 'url',
                        'required'    => false,
                        'description' => __('URL da track do SoundCloud', 'apollo-events-manager'),
                        'example'     => 'https://soundcloud.com/...',
                    ],
                    [
                        'key'         => '_dj_track_title',
                        'type'        => 'string',
                        'required'    => false,
                        'description' => __('Título da track destacada', 'apollo-events-manager'),
                        'example'     => 'Live Set @ Tomorrowland',
                    ],
                    [
                        'key'         => '_dj_mediakit_url',
                        'type'        => 'url',
                        'required'    => false,
                        'description' => __('URL do media kit', 'apollo-events-manager'),
                        'example'     => 'https://exemplo.com/mediakit.pdf',
                    ],
                    [
                        'key'         => '_dj_music_links',
                        'type'        => 'json',
                        'required'    => false,
                        'description' => __('JSON com links de música (Spotify, Bandcamp, etc)', 'apollo-events-manager'),
                        'example'     => '{"spotify": "...", "bandcamp": "..."}',
                    ],
                    [
                        'key'         => '_dj_social_links',
                        'type'        => 'json',
                        'required'    => false,
                        'description' => __('JSON com links sociais (Instagram, Facebook, etc)', 'apollo-events-manager'),
                        'example'     => '{"instagram": "...", "facebook": "..."}',
                    ],
                    [
                        'key'         => '_dj_asset_links',
                        'type'        => 'json',
                        'required'    => false,
                        'description' => __('JSON com links de assets (Media Kit, Rider, etc)', 'apollo-events-manager'),
                        'example'     => '{"mediakit": "...", "rider": "..."}',
                    ],
                    [
                        'key'         => '_dj_projects',
                        'type'        => 'array',
                        'required'    => false,
                        'description' => __('Array de projetos do DJ', 'apollo-events-manager'),
                        'example'     => '["Project 1", "Project 2"]',
                    ],
                    [
                        'key'         => '_dj_website',
                        'type'        => 'url',
                        'required'    => false,
                        'description' => __('Website oficial', 'apollo-events-manager'),
                        'example'     => 'https://djalpha.com',
                    ],
                    [
                        'key'         => '_dj_instagram',
                        'type'        => 'string',
                        'required'    => false,
                        'description' => __('Instagram handle', 'apollo-events-manager'),
                        'example'     => '@djalpha',
                    ],
                    [
                        'key'         => '_dj_facebook',
                        'type'        => 'url',
                        'required'    => false,
                        'description' => __('Facebook URL', 'apollo-events-manager'),
                        'example'     => 'https://facebook.com/djalpha',
                    ],
                    [
                        'key'         => '_dj_soundcloud',
                        'type'        => 'url',
                        'required'    => false,
                        'description' => __('SoundCloud URL', 'apollo-events-manager'),
                        'example'     => 'https://soundcloud.com/djalpha',
                    ],
                ],
            ],
            'event_local' => [
                'title'       => __('Local Meta Keys', 'apollo-events-manager'),
                'description' => __('Meta keys para locais/venues (event_local CPT).', 'apollo-events-manager'),
                'icon'        => 'location',
                'meta_keys'   => [
                    [
                        'key'         => '_local_name',
                        'type'        => 'string',
                        'required'    => false,
                        'description' => __('Nome do local', 'apollo-events-manager'),
                        'example'     => 'Lapa 40 Graus',
                    ],
                    [
                        'key'         => '_local_description',
                        'type'        => 'text',
                        'required'    => false,
                        'description' => __('Descrição do local', 'apollo-events-manager'),
                        'example'     => 'Casa noturna na Lapa...',
                    ],
                    [
                        'key'         => '_local_address',
                        'type'        => 'string',
                        'required'    => false,
                        'description' => __('Endereço completo', 'apollo-events-manager'),
                        'example'     => 'Rua da Lapa, 123, Rio de Janeiro',
                    ],
                    [
                        'key'         => '_local_city',
                        'type'        => 'string',
                        'required'    => false,
                        'description' => __('Cidade', 'apollo-events-manager'),
                        'example'     => 'Rio de Janeiro',
                    ],
                    [
                        'key'         => '_local_state',
                        'type'        => 'string',
                        'required'    => false,
                        'description' => __('Estado', 'apollo-events-manager'),
                        'example'     => 'RJ',
                    ],
                    [
                        'key'         => '_local_latitude',
                        'type'        => 'float',
                        'required'    => false,
                        'description' => __('Latitude para mapa', 'apollo-events-manager'),
                        'example'     => '-22.9068',
                    ],
                    [
                        'key'         => '_local_longitude',
                        'type'        => 'float',
                        'required'    => false,
                        'description' => __('Longitude para mapa', 'apollo-events-manager'),
                        'example'     => '-43.1729',
                    ],
                    [
                        'key'         => '_local_website',
                        'type'        => 'url',
                        'required'    => false,
                        'description' => __('Website do local', 'apollo-events-manager'),
                        'example'     => 'https://lapa40graus.com.br',
                    ],
                    [
                        'key'         => '_local_facebook',
                        'type'        => 'url',
                        'required'    => false,
                        'description' => __('Facebook URL', 'apollo-events-manager'),
                        'example'     => 'https://facebook.com/lapa40graus',
                    ],
                    [
                        'key'         => '_local_instagram',
                        'type'        => 'string',
                        'required'    => false,
                        'description' => __('Instagram handle', 'apollo-events-manager'),
                        'example'     => '@lapa40graus',
                    ],
                ],
            ],
        ];
    }

    /**
     * Get user meta keys
     *
     * @return array User meta keys
     */
    function apollo_events_get_user_metakeys()
    {
        return [
            [
                'key'         => 'bio_full',
                'type'        => 'text',
                'description' => __('Biografia completa do usuário', 'apollo-events-manager'),
                'example'     => 'Biografia do usuário...',
            ],
            [
                'key'         => '_apollo_tagline',
                'type'        => 'string',
                'description' => __('Tagline do usuário', 'apollo-events-manager'),
                'example'     => 'Producer & DJ',
            ],
            [
                'key'         => '_apollo_location',
                'type'        => 'string',
                'description' => __('Localização do usuário', 'apollo-events-manager'),
                'example'     => 'Rio de Janeiro, RJ',
            ],
            [
                'key'         => '_apollo_roles',
                'type'        => 'string',
                'description' => __('Papéis do usuário (ex: Producer, DJ, Label Owner)', 'apollo-events-manager'),
                'example'     => 'Producer, DJ',
            ],
            [
                'key'         => 'location',
                'type'        => 'string',
                'description' => __('Localização (campo padrão WordPress)', 'apollo-events-manager'),
                'example'     => 'Rio de Janeiro',
            ],
            [
                'key'         => 'roles_display',
                'type'        => 'string',
                'description' => __('Papéis para exibição', 'apollo-events-manager'),
                'example'     => 'Producer, DJ',
            ],
            [
                'key'         => 'membership',
                'type'        => 'string',
                'description' => __('Tipo de membro/comunidade', 'apollo-events-manager'),
                'example'     => 'Membro Premium',
            ],
            [
                'key'         => 'favorite_events',
                'type'        => 'array',
                'description' => __('Array de IDs de eventos favoritados', 'apollo-events-manager'),
                'example'     => '[123, 456, 789]',
            ],
        ];
    }
} //end if
