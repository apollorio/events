<?php
// phpcs:ignoreFile
/**
 * Notifications List Template
 * TODO 118-121: Notifications system with animations, desktop popup, mobile list
 *
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

// TODO 118: Notifications list style
// TODO 119: Entry animations
// TODO 120: Desktop popup
// TODO 121: Mobile list

?>
<div class="apollo-notifications" 
	data-notifications="true"
	data-device-type="<?php echo wp_is_mobile() ? 'mobile' : 'desktop'; ?>">
	
	<!-- Desktop: Popup notifications -->
	<?php if (! wp_is_mobile()) : ?>
		<div class="notifications-popup" data-popup-notifications="true">
			<?php foreach ($notifications as $index => $notification) : ?>
				<div class="notification-item" 
					data-motion-notification="true"
					data-index="<?php echo esc_attr($index); ?>"
					data-type="<?php echo esc_attr($notification['type']); ?>">
					<div class="notification-icon">
						<?php if ($notification['type'] === 'mention') : ?>
							<i class="ri-at-line"></i>
						<?php elseif ($notification['type'] === 'like') : ?>
							<i class="ri-heart-fill"></i>
						<?php elseif ($notification['type'] === 'comment') : ?>
							<i class="ri-chat-3-line"></i>
						<?php elseif ($notification['type'] === 'follow') : ?>
							<i class="ri-user-add-line"></i>
						<?php endif; ?>
					</div>
					<div class="notification-content">
						<p><?php echo esc_html($notification['message']); ?></p>
						<span class="notification-time"><?php echo esc_html($notification['time']); ?></span>
					</div>
					<button class="notification-dismiss" data-action="dismiss">
						<i class="ri-close-line"></i>
					</button>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	
	<!-- Mobile: List -->
	<?php if (wp_is_mobile()) : ?>
		<div class="notifications-list" data-list-notifications="true">
			<div class="notifications-header">
				<h2>Notificações</h2>
				<button class="mark-all-read" data-action="mark-all-read">
					Marcar todas como lidas
				</button>
			</div>
			
			<div class="notifications-items">
				<?php foreach ($notifications as $index => $notification) : ?>
					<div class="notification-item" 
						data-motion-notification="true"
						data-index="<?php echo esc_attr($index); ?>"
						data-read="<?php echo esc_attr($notification['read'] ? 'true' : 'false'); ?>">
						<!-- Same content as desktop -->
						<div class="notification-icon">
							<?php if ($notification['type'] === 'mention') : ?>
								<i class="ri-at-line"></i>
							<?php elseif ($notification['type'] === 'like') : ?>
								<i class="ri-heart-fill"></i>
							<?php elseif ($notification['type'] === 'comment') : ?>
								<i class="ri-chat-3-line"></i>
							<?php elseif ($notification['type'] === 'follow') : ?>
								<i class="ri-user-add-line"></i>
							<?php endif; ?>
						</div>
						<div class="notification-content">
							<p><?php echo esc_html($notification['message']); ?></p>
							<span class="notification-time"><?php echo esc_html($notification['time']); ?></span>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</div>

