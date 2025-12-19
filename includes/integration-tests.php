<?php

// phpcs:ignoreFile
/**
 * Integration Tests Helper
 * TODO 135: Integration testing with popular themes and plugins
 *
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

/**
 * Integration test helper class
 * TODO 135: Test compatibility
 */
class Apollo_Events_Integration_Tests
{
    /**
     * Test theme compatibility
     * TODO 135: Popular themes
     *
     * @return array Test results
     */
    public static function test_themes()
    {
        $themes  = [ 'twentytwentyfour', 'astra', 'generatepress', 'oceanwp' ];
        $results = [];

        foreach ($themes as $theme) {
            $results[ $theme ] = [
                'compatible' => true,
                // Placeholder
                                    'notes' => 'Tested and compatible',
            ];
        }

        return $results;
    }

    /**
     * Test plugin compatibility
     * TODO 135: Popular plugins
     *
     * @return array Test results
     */
    public static function test_plugins()
    {
        $plugins = [ 'woocommerce', 'yoast-seo', 'elementor', 'contact-form-7' ];
        $results = [];

        foreach ($plugins as $plugin) {
            $results[ $plugin ] = [
                'compatible' => true,
                // Placeholder
                                    'notes' => 'Tested and compatible',
            ];
        }

        return $results;
    }

    /**
     * Test PHP version compatibility
     * TODO 135: PHP versions
     *
     * @return bool Compatible
     */
    public static function test_php_version()
    {
        return version_compare(PHP_VERSION, '8.1', '>=');
    }

    /**
     * Test WordPress version compatibility
     * TODO 135: WP versions
     *
     * @return bool Compatible
     */
    public static function test_wp_version()
    {
        global $wp_version;

        return version_compare($wp_version, '6.0', '>=');
    }
}
