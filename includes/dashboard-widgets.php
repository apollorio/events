<?php
// phpcs:ignoreFile
/**
 * Apollo Events Manager - Dashboard Widgets
 *
 * Widgets for admin dashboard: Reminders, Personal To-Do, Núcleo To-Do, Pre Save-Date Calendar
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Get reminders for current user
 *
 * @return array Reminders list
 */
function apollo_get_user_reminders()
{
    $user_id = get_current_user_id();
    if (! $user_id) {
        return [];
    }

    // Get reminders from user meta
    $reminders = get_user_meta($user_id, '_apollo_reminders', true);
    if (! is_array($reminders)) {
        $reminders = [];
    }

    // Filter out completed reminders
    $active_reminders = array_filter(
        $reminders,
        function ($reminder) {
            return ! isset($reminder['completed']) || ! $reminder['completed'];
        }
    );

    // Sort by date
    usort(
        $active_reminders,
        function ($a, $b) {
            $date_a = isset($a['date']) ? strtotime($a['date']) : 0;
            $date_b = isset($b['date']) ? strtotime($b['date']) : 0;

            return $date_a - $date_b;
        }
    );

    return array_slice($active_reminders, 0, 5);
    // Top 5
}

/**
 * Get personal to-do items for current user
 *
 * @return array To-do list
 */
function apollo_get_personal_todos()
{
    $user_id = get_current_user_id();
    if (! $user_id) {
        return [];
    }

    $todos = get_user_meta($user_id, '_apollo_personal_todos', true);
    if (! is_array($todos)) {
        $todos = [];
    }

    // Filter active todos
    $active_todos = array_filter(
        $todos,
        function ($todo) {
            return ! isset($todo['completed']) || ! $todo['completed'];
        }
    );

    return array_slice($active_todos, 0, 10);
}

/**
 * Get Núcleo to-do items (shared)
 *
 * @return array To-do list
 */
function apollo_get_nucleo_todos()
{
    // Get from options (shared across all users)
    $todos = get_option('_apollo_nucleo_todos', []);
    if (! is_array($todos)) {
        $todos = [];
    }

    // Filter active todos
    $active_todos = array_filter(
        $todos,
        function ($todo) {
            return ! isset($todo['completed']) || ! $todo['completed'];
        }
    );

    return array_slice($active_todos, 0, 10);
}

/**
 * Get Pre Save-Date calendar events
 *
 * @return array Calendar events
 */
function apollo_get_pre_save_date_calendar()
{
    // Check if save_date post type exists
    if (! post_type_exists('save_date')) {
        return [];
    }

    $today      = current_time('Y-m-d');
    $next_month = date('Y-m-d', strtotime('+30 days'));

    $save_dates = get_posts(
        [
            'post_type'      => 'save_date',
            'posts_per_page' => 20,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'     => 'date',
                    'value'   => [ $today, $next_month ],
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE',
                ],
            ],
            'orderby'  => 'meta_value',
            'meta_key' => 'date',
            'order'    => 'ASC',
        ]
    );

    $calendar = [];
    foreach ($save_dates as $save_date) {
        $date = get_post_meta($save_date->ID, 'date', true);
        if ($date) {
            $calendar[] = [
                'id'             => $save_date->ID,
                'title'          => get_the_title($save_date->ID),
                'date'           => $date,
                'date_formatted' => date_i18n('d/m/Y', strtotime($date)),
            ];
        }
    }

    return $calendar;
}

/**
 * Render reminders widget
 */
function apollo_render_reminders_widget()
{
    $reminders = apollo_get_user_reminders();
    ?>
	<div class="apollo-dashboard-widget apollo-reminders-widget">
		<div class="apollo-widget-header">
			<h3>
				<i class="ri-notification-3-line"></i>
				<?php esc_html_e('Reminders', 'apollo-events-manager'); ?>
			</h3>
		</div>
		<div class="apollo-widget-body">
			<?php if (! empty($reminders)) : ?>
				<ul class="apollo-reminders-list">
					<?php foreach ($reminders as $reminder) : ?>
						<li class="apollo-reminder-item">
							<div class="reminder-content">
								<strong><?php echo esc_html($reminder['title'] ?? ''); ?></strong>
								<?php if (isset($reminder['date'])) : ?>
									<span class="reminder-date">
										<i class="ri-calendar-line"></i>
										<?php echo esc_html(date_i18n('d/m/Y', strtotime($reminder['date']))); ?>
									</span>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="apollo-empty-state">
					<i class="ri-inbox-line"></i>
					<?php esc_html_e('No reminders', 'apollo-events-manager'); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Render personal to-do widget
 */
function apollo_render_personal_todo_widget()
{
    $todos = apollo_get_personal_todos();
    ?>
	<div class="apollo-dashboard-widget apollo-todo-widget">
		<div class="apollo-widget-header">
			<h3>
				<i class="ri-checkbox-line"></i>
				<?php esc_html_e('Personal To-Do', 'apollo-events-manager'); ?>
			</h3>
		</div>
		<div class="apollo-widget-body">
			<?php if (! empty($todos)) : ?>
				<ul class="apollo-todo-list">
					<?php foreach ($todos as $index => $todo) : ?>
						<li class="apollo-todo-item">
							<label class="todo-checkbox">
								<input type="checkbox" 
										data-todo-index="<?php echo esc_attr($index); ?>"
										class="apollo-todo-checkbox">
								<span class="todo-text"><?php echo esc_html($todo['text'] ?? ''); ?></span>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="apollo-empty-state">
					<i class="ri-inbox-line"></i>
					<?php esc_html_e('No to-do items', 'apollo-events-manager'); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Render Núcleo to-do widget
 */
function apollo_render_nucleo_todo_widget()
{
    $todos = apollo_get_nucleo_todos();
    ?>
	<div class="apollo-dashboard-widget apollo-nucleo-todo-widget">
		<div class="apollo-widget-header">
			<h3>
				<i class="ri-team-line"></i>
				<?php esc_html_e('Núcleo To-Do', 'apollo-events-manager'); ?>
			</h3>
		</div>
		<div class="apollo-widget-body">
			<?php if (! empty($todos)) : ?>
				<ul class="apollo-todo-list">
					<?php foreach ($todos as $index => $todo) : ?>
						<li class="apollo-todo-item">
							<label class="todo-checkbox">
								<input type="checkbox" 
										data-todo-index="<?php echo esc_attr($index); ?>"
										data-todo-type="nucleo"
										class="apollo-todo-checkbox">
								<span class="todo-text"><?php echo esc_html($todo['text'] ?? ''); ?></span>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="apollo-empty-state">
					<i class="ri-inbox-line"></i>
					<?php esc_html_e('No to-do items', 'apollo-events-manager'); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Render Pre Save-Date Calendar widget
 */
function apollo_render_pre_save_date_calendar_widget()
{
    $calendar = apollo_get_pre_save_date_calendar();
    ?>
	<div class="apollo-dashboard-widget apollo-calendar-widget">
		<div class="apollo-widget-header">
			<h3>
				<i class="ri-calendar-todo-line"></i>
				<?php esc_html_e('Pre Save-Date Calendar', 'apollo-events-manager'); ?>
			</h3>
		</div>
		<div class="apollo-widget-body">
			<?php if (! empty($calendar)) : ?>
				<ul class="apollo-calendar-list">
					<?php foreach ($calendar as $item) : ?>
						<li class="apollo-calendar-item">
							<div class="calendar-date">
								<span class="date-day"><?php echo esc_html(date('d', strtotime($item['date']))); ?></span>
								<span class="date-month"><?php echo esc_html(date_i18n('M', strtotime($item['date']))); ?></span>
							</div>
							<div class="calendar-content">
								<strong><?php echo esc_html($item['title']); ?></strong>
								<span class="calendar-date-full"><?php echo esc_html($item['date_formatted']); ?></span>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="apollo-empty-state">
					<i class="ri-inbox-line"></i>
					<?php esc_html_e('No save-dates in the next 30 days', 'apollo-events-manager'); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
