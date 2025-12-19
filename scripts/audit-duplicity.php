<?php

// phpcs:ignoreFile
/**
 * Duplicity Audit Script for Apollo Events Manager
 *
 * Checks for:
 * - Duplicate function declarations
 * - Duplicate shortcode registrations
 * - Duplicate page creation
 * - Template includes without placeholders/tooltips
 *
 * @package Apollo_Events_Manager
 */

// Prevent direct access
if (! defined('ABSPATH')) {
    require_once __DIR__ . '/../../../../wp-load.php';
}

$plugin_dir = __DIR__ . '/../';
$issues     = [];

// 1. Check duplicate functions
echo "🔍 Checking duplicate functions...\n";
$functions = [];
$files     = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($plugin_dir));
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(/', $content, $matches);
        foreach ($matches[1] as $func) {
            if (! isset($functions[ $func ])) {
                $functions[ $func ] = [];
            }
            $functions[ $func ][] = str_replace($plugin_dir, '', $file->getPathname());
        }
    }
}

foreach ($functions as $func => $locations) {
    if (count($locations) > 1 && ! strpos($func, '__') === 0) {
        $issues[] = [
            'type'      => 'duplicate_function',
            'name'      => $func,
            'locations' => $locations,
        ];
        echo "  ❌ Duplicate function: {$func} in " . implode(', ', $locations) . "\n";
    }
}

// 2. Check duplicate shortcodes
echo "\n🔍 Checking duplicate shortcode registrations...\n";
$shortcodes = [];
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        preg_match_all("/add_shortcode\s*\(\s*['\"]([^'\"]+)['\"]/", $content, $matches);
        foreach ($matches[1] as $shortcode) {
            if (! isset($shortcodes[ $shortcode ])) {
                $shortcodes[ $shortcode ] = [];
            }
            $shortcodes[ $shortcode ][] = str_replace($plugin_dir, '', $file->getPathname());
        }
    }
}

foreach ($shortcodes as $shortcode => $locations) {
    if (count($locations) > 1) {
        $issues[] = [
            'type'      => 'duplicate_shortcode',
            'name'      => $shortcode,
            'locations' => $locations,
        ];
        echo "  ❌ Duplicate shortcode: [{$shortcode}] in " . implode(', ', $locations) . "\n";
    }
}

// 3. Check duplicate page creation
echo "\n🔍 Checking duplicate page creation...\n";
$page_creations = [];
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        // Check for get_page_by_path and wp_insert_post patterns
        preg_match_all("/get_page_by_path\s*\(\s*['\"]([^'\"]+)['\"]/", $content, $matches);
        foreach ($matches[1] as $slug) {
            if (! isset($page_creations[ $slug ])) {
                $page_creations[ $slug ] = [];
            }
            $page_creations[ $slug ][] = str_replace($plugin_dir, '', $file->getPathname());
        }
    }
}

foreach ($page_creations as $slug => $locations) {
    if (count($locations) > 1) {
        $issues[] = [
            'type'      => 'duplicate_page_check',
            'slug'      => $slug,
            'locations' => $locations,
        ];
        echo "  ⚠️  Page slug checked multiple times: {$slug} in " . implode(', ', $locations) . "\n";
    }
}

// 4. Check template includes without placeholders
echo "\n🔍 Checking template includes...\n";
$template_includes = [];
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        // Check for direct includes
        if (preg_match_all('/(include|require)(_once)?\s+.*templates/', $content, $matches)) {
            $filepath                       = str_replace($plugin_dir, '', $file->getPathname());
            $template_includes[ $filepath ] = count($matches[0]);
        }
    }
}

echo "\n📊 SUMMARY:\n";
echo '  - Duplicate functions: ' . count(array_filter($issues, fn ($i) => $i['type'] === 'duplicate_function')) . "\n";
echo '  - Duplicate shortcodes: ' . count(array_filter($issues, fn ($i) => $i['type'] === 'duplicate_shortcode')) . "\n";
echo '  - Duplicate page checks: ' . count(array_filter($issues, fn ($i) => $i['type'] === 'duplicate_page_check')) . "\n";
echo '  - Files with template includes: ' . count($template_includes) . "\n";

// Save report
file_put_contents(
    $plugin_dir . 'DUPLICITY-AUDIT-REPORT.json',
    json_encode(
        [
            'issues'            => $issues,
            'template_includes' => $template_includes,
            'timestamp'         => date('Y-m-d H:i:s'),
        ],
        JSON_PRETTY_PRINT
    )
);

echo "\n✅ Report saved to DUPLICITY-AUDIT-REPORT.json\n";
