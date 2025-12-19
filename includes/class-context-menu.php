<?php
// phpcs:ignoreFile
/**
 * Context Menu Class
 *
 * Implements right-click context menu for events with Motion.dev animations
 *
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

class Apollo_Context_Menu
{
    /**
     * Initialize context menu
     */
    public static function init()
    {
        add_action('wp_footer', [ __CLASS__, 'render_context_menu' ]);
        add_action('admin_footer', [ __CLASS__, 'render_context_menu' ]);
    }

    /**
     * Render context menu HTML
     */
    public static function render_context_menu()
    {
        // Only render on event pages
        if (! is_singular('event_listing') && ! is_post_type_archive('event_listing') && ! is_page('eventos')) {
            return;
        }

        $is_admin = current_user_can('edit_posts');
        ?>
		<div id="apollo-context-menu" class="apollo-context-menu" data-motion-context-menu="true" style="display: none;">
			<div class="context-menu-content">
				<?php if ($is_admin) : ?>
				<!-- Admin Menu -->
				<button class="context-menu-item" data-action="copy-url">
					<i class="ri-link"></i>
					<span><?php echo esc_html__('Copiar URL', 'apollo-events-manager'); ?></span>
				</button>
				<button class="context-menu-item" data-action="copy-title">
					<i class="ri-file-copy-line"></i>
					<span><?php echo esc_html__('Copiar Título', 'apollo-events-manager'); ?></span>
				</button>
				<div class="context-menu-separator"></div>
				<button class="context-menu-item" data-action="edit">
					<i class="ri-edit-line"></i>
					<span><?php echo esc_html__('Editar', 'apollo-events-manager'); ?></span>
				</button>
				<button class="context-menu-item" data-action="duplicate">
					<i class="ri-file-copy-2-line"></i>
					<span><?php echo esc_html__('Duplicar', 'apollo-events-manager'); ?></span>
				</button>
				<div class="context-menu-separator"></div>
				<button class="context-menu-item danger" data-action="delete">
					<i class="ri-delete-bin-line"></i>
					<span><?php echo esc_html__('Excluir', 'apollo-events-manager'); ?></span>
				</button>
				<?php else : ?>
				<!-- User/Guest Menu -->
				<button class="context-menu-item" data-action="copy-url">
					<i class="ri-link"></i>
					<span><?php echo esc_html__('Copiar URL', 'apollo-events-manager'); ?></span>
				</button>
				<button class="context-menu-item" data-action="share">
					<i class="ri-share-line"></i>
					<span><?php echo esc_html__('Compartilhar', 'apollo-events-manager'); ?></span>
				</button>
				<?php endif; ?>
			</div>
		</div>
		
		<style>
		.apollo-context-menu {
			position: fixed;
			z-index: 99999;
			background: #fff;
			border-radius: 8px;
			box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
			padding: 0.5rem 0;
			min-width: 200px;
			opacity: 0;
			transform: scale(0.95);
			transition: opacity 0.2s ease, transform 0.2s ease;
		}
		
		.apollo-context-menu.is-open {
			opacity: 1;
			transform: scale(1);
		}
		
		.context-menu-content {
			display: flex;
			flex-direction: column;
		}
		
		.context-menu-item {
			display: flex;
			align-items: center;
			gap: 0.75rem;
			padding: 0.75rem 1rem;
			border: none;
			background: none;
			cursor: pointer;
			font-size: 0.9rem;
			text-align: left;
			width: 100%;
			transition: background 0.15s ease;
		}
		
		.context-menu-item:hover {
			background: rgba(0, 124, 186, 0.1);
		}
		
		.context-menu-item.danger:hover {
			background: rgba(220, 38, 38, 0.1);
			color: #dc2626;
		}
		
		.context-menu-separator {
			height: 1px;
			background: rgba(0, 0, 0, 0.1);
			margin: 0.25rem 0;
		}
		
		@media (prefers-color-scheme: dark) {
			.apollo-context-menu {
				background: #1a1a1a;
				box-shadow: 0 4px 24px rgba(0, 0, 0, 0.5);
			}
			
			.context-menu-item:hover {
				background: rgba(255, 255, 255, 0.1);
			}
			
			.context-menu-separator {
				background: rgba(255, 255, 255, 0.1);
			}
		}
		</style>
		<?php
    }
}

// Initialize
Apollo_Context_Menu::init();
