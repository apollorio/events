<?php
/**
 * Fix Capabilities for Apollo Events Manager
 * Run this once to ensure admin has all necessary permissions
 */

// Prevent direct access without WP
if ( ! defined( 'ABSPATH' ) ) {
	require_once '../../../wp-load.php';
}

$current_user = wp_get_current_user();
echo '<h1>🔧 Apollo Events Manager - Capability Fix</h1>';
echo '<p><strong>Current User:</strong> ' . $current_user->display_name . '</p>';
echo '<p><strong>User ID:</strong> ' . $current_user->ID . '</p>';
echo '<p><strong>Roles:</strong> ' . implode( ', ', $current_user->roles ) . '</p>';

// Check and add capabilities
$admin_role = get_role( 'administrator' );
if ( $admin_role ) {
	$event_listing_capabilities = array(
		'edit_event_listing',
		'read_event_listing',
		'delete_event_listing',
		'edit_event_listings',
		'edit_others_event_listings',
		'publish_event_listings',
		'read_private_event_listings',
		'delete_event_listings',
		'delete_private_event_listings',
		'delete_published_event_listings',
		'delete_others_event_listings',
		'edit_private_event_listings',
		'edit_published_event_listings',
	);

	echo '<h2>Adding Capabilities to Administrator:</h2>';
	echo '<ul>';
	foreach ( $event_listing_capabilities as $cap ) {
		if ( ! $admin_role->has_cap( $cap ) ) {
			$admin_role->add_cap( $cap );
			echo "<li>✅ Added: <strong>{$cap}</strong></li>";
		} else {
			echo "<li>ℹ️ Already has: <strong>{$cap}</strong></li>";
		}
	}
	echo '</ul>';

	// Add taxonomy capabilities
	$tax_caps = array(
		'manage_categories',
		'edit_event_listing_category',
		'edit_event_listing_type',
		'edit_event_listing_tag',
		'edit_event_sounds',
	);

	echo '<h2>Taxonomy Capabilities:</h2>';
	echo '<ul>';
	foreach ( $tax_caps as $cap ) {
		if ( ! $admin_role->has_cap( $cap ) ) {
			$admin_role->add_cap( $cap );
			echo "<li>✅ Added: <strong>{$cap}</strong></li>";
		} else {
			echo "<li>ℹ️ Already has: <strong>{$cap}</strong></li>";
		}
	}
	echo '</ul>';
}

// Test current user capabilities
echo '<h2>Testing Current User Capabilities:</h2>';
echo '<ul>';
$test_caps = array(
	'edit_event_listings',
	'edit_posts',
	'manage_options',
	'manage_categories',
);
foreach ( $test_caps as $cap ) {
	$has_cap = current_user_can( $cap );
	echo "<li><strong>{$cap}:</strong> " . ( $has_cap ? '✅ YES' : '❌ NO' ) . '</li>';
}
echo '</ul>';

echo '<hr>';
echo '<p><strong>✅ Capabilities Fixed!</strong></p>';
echo "<p><a href='" . admin_url( 'edit.php?post_type=event_listing' ) . "' target='_blank'>Test: View All Events</a></p>";
echo "<p><a href='" . admin_url( 'post-new.php?post_type=event_listing' ) . "' target='_blank'>Test: Add New Event</a></p>";
echo "<p><a href='" . admin_url() . "'>← Back to Admin</a></p>";

