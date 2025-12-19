<?php
/**
 * Check if Apollo CPTs are registered correctly
 * Access via: /wp-content/plugins/apollo-events-manager/check-cpts.php
 */

// Prevent direct access without WP
if ( ! defined( 'ABSPATH' ) ) {
	require_once '../../../wp-load.php';
}

echo '<h1>Apollo Events Manager - CPT Check</h1>';

// Check if post types exist
$post_types = array( 'event_listing', 'event_dj', 'event_local' );
$taxonomies = array( 'event_listing_category', 'event_listing_type', 'event_sounds' );

echo '<h2>Custom Post Types:</h2>';
echo '<ul>';
foreach ( $post_types as $pt ) {
	$obj = get_post_type_object( $pt );
	if ( $obj ) {
		echo "<li><strong>{$pt}</strong>: ✅ Registered - show_in_menu: " . ( $obj->show_in_menu ? 'true' : 'false' ) . '</li>';
	} else {
		echo "<li><strong>{$pt}</strong>: ❌ NOT REGISTERED</li>";
	}
}
echo '</ul>';

echo '<h2>Taxonomies:</h2>';
echo '<ul>';
foreach ( $taxonomies as $tax ) {
	$obj = get_taxonomy( $tax );
	if ( $obj ) {
		echo "<li><strong>{$tax}</strong>: ✅ Registered</li>";
	} else {
		echo "<li><strong>{$tax}</strong>: ❌ NOT REGISTERED</li>";
	}
}
echo '</ul>';

echo '<h2>Debug Info:</h2>';
echo '<p>Current URL: ' . $_SERVER['REQUEST_URI'] . '</p>';
echo '<p>Plugin Path: ' . plugin_dir_path( __FILE__ ) . '</p>';
echo '<p>APOLLO_DEBUG: ' . ( defined( 'APOLLO_DEBUG' ) ? 'true' : 'false' ) . '</p>';

// Manual flush option
if ( isset( $_GET['flush'] ) ) {
	flush_rewrite_rules( false );
	echo '<p><strong>Rewrite rules flushed!</strong></p>';
	echo "<p><a href='?'>← Back</a></p>";
} else {
	echo "<p><a href='?flush=1'>Flush Rewrite Rules</a></p>";
}

