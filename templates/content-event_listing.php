<?php

// phpcs:ignoreFile
/**
 * Template for event listing content - AJAX filtering
 *
 * ✅ CONSOLIDATED: This template now simply includes event-card.php
 * to avoid code duplication. All logic is centralized in event-card.php
 */

/* phpcs:disable Generic.Files.LineLength, WordPress.Files.FileHeader */

// ✅ SIMPLY INCLUDE event-card.php - NO CODE DUPLICATION
// event-card.php handles all the logic and HTML rendering
$event_card_path = defined('APOLLO_APRIO_PATH')
    ? APOLLO_APRIO_PATH . 'templates/event-card.php'
    : plugin_dir_path(__FILE__) . 'event-card.php';

if (file_exists($event_card_path)) {
    include $event_card_path;
} else {
    // Fallback if event-card.php doesn't exist
    echo '<!-- ERROR: event-card.php template not found at: ' . esc_html($event_card_path) . ' -->';
}

/* phpcs:enable Generic.Files.LineLength, WordPress.Files.FileHeader */
