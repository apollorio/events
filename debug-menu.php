<?php
/**
 * Debug Menu Registration
 */

// Prevent direct access without WP
if ( ! defined( 'ABSPATH' ) ) {
	require_once '../../../wp-load.php';
}

echo '<h1>🐛 Debug - Menu Registration</h1>';

// Force admin menu loading
if ( ! did_action( 'admin_menu' ) ) {
	echo '<p>🔄 Forcing admin_menu action...</p>';
	do_action( 'admin_menu' );
	echo '<p>✅ admin_menu action completed</p>';
}

// Check if function exists
if ( function_exists( 'apollo_events_register_hub_page' ) ) {
	echo '<p>✅ Function <code>apollo_events_register_hub_page</code> exists</p>';

	// Try to call it manually
	echo '<p>🔄 Calling function manually...</p>';
	apollo_events_register_hub_page();
	echo '<p>✅ Function called</p>';
} else {
	echo '<p>❌ Function <code>apollo_events_register_hub_page</code> does NOT exist</p>';
}

// Check global menu
global $menu, $submenu;
echo '<h2>📋 Current Menu Structure:</h2>';
echo '<pre>';
echo "ALL Menu Items with 'apollo' or 'event':\n";
foreach ( $menu as $key => $item ) {
	if ( isset( $item[0] ) && ( strpos( strtolower( $item[0] ), 'apollo' ) !== false || strpos( strtolower( $item[0] ), 'event' ) !== false ) ) {
		echo "[$key] => " . $item[0] . ' (' . $item[2] . ")\n";
	}
}

echo "\nALL Submenu Items:\n";
foreach ( $submenu as $parent_slug => $sub_items ) {
	if ( strpos( $parent_slug, 'apollo' ) !== false || strpos( $parent_slug, 'event' ) !== false ) {
		echo "Parent: $parent_slug\n";
		foreach ( $sub_items as $item ) {
			echo '  - ' . $item[0] . ' (' . $item[2] . ")\n";
		}
		echo "\n";
	}
}

// Check if our specific menu exists
echo "\nSpecific checks:\n";
$found_apollo_events = false;
foreach ( $menu as $item ) {
	if ( isset( $item[2] ) && $item[2] === 'apollo-events-hub' ) {
		echo "✅ Found 'apollo-events-hub' menu: " . $item[0] . "\n";
		$found_apollo_events = true;
		break;
	}
}
if ( ! $found_apollo_events ) {
	echo "❌ 'apollo-events-hub' menu NOT found in \$menu\n";
}

if ( isset( $submenu['apollo-events-hub'] ) ) {
	echo "✅ Found submenu for 'apollo-events-hub'\n";
} else {
	echo "❌ No submenu for 'apollo-events-hub'\n";
}

echo '</pre>';

// Check hooks
echo '<h2>🔗 Active Hooks:</h2>';
global $wp_filter;
if ( isset( $wp_filter['admin_menu'] ) ) {
	echo '<p>✅ admin_menu hook has callbacks</p>';
	foreach ( $wp_filter['admin_menu']->callbacks as $priority => $callbacks ) {
		foreach ( $callbacks as $callback ) {
			if ( is_array( $callback['function'] ) && isset( $callback['function'][1] ) ) {
				$function_name = $callback['function'][1];
			} elseif ( is_string( $callback['function'] ) ) {
				$function_name = $callback['function'];
			} else {
				$function_name = 'unknown';
			}
			echo "<small>- Priority $priority: $function_name</small><br>";
		}
	}
} else {
	echo '<p>❌ No admin_menu hooks found</p>';
}

echo '<hr>';
echo "<p><a href='" . admin_url() . "'>← Back to Admin</a></p>";
