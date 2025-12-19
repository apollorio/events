<?php
// phpcs:ignoreFile
/**
 * Chat Message Template
 * TODO 115-117: Chat templates with status variants, warp overlay, swipe actions
 *
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

// TODO 115: Status variants (enviado, entregue, lido)
$status = isset($status) ? $status : 'sent';
// sent, delivered, read

?>
<div class="chat-message" 
	data-message-status="<?php echo esc_attr($status); ?>"
	data-swipe-action="true"
	data-motion-message="true">
	<div class="message-content">
		<?php echo wp_kses_post($message_content); ?>
	</div>
	
	<!-- TODO 115: Status indicators -->
	<div class="message-status">
		<?php if ($status === 'sent') : ?>
			<i class="ri-check-line" aria-label="Enviado"></i>
		<?php elseif ($status === 'delivered') : ?>
			<i class="ri-check-double-line" aria-label="Entregue"></i>
		<?php elseif ($status === 'read') : ?>
			<i class="ri-check-double-line read" aria-label="Lido"></i>
		<?php endif; ?>
	</div>
	
	<!-- TODO 117: Swipe actions -->
	<div class="swipe-actions">
		<button class="swipe-reply" data-action="reply">
			<i class="ri-reply-line"></i>
		</button>
		<button class="swipe-forward" data-action="forward">
			<i class="ri-share-forward-line"></i>
		</button>
		<button class="swipe-delete" data-action="delete">
			<i class="ri-delete-bin-line"></i>
		</button>
	</div>
</div>

<!-- TODO 116: Warp overlay -->
<div class="warp-overlay" data-warp-overlay="true" style="display: none;"></div>

