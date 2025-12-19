<?php

// phpcs:ignoreFile
/**
 * Accessibility Audit Helper
 * TODO 132: Centralized accessibility checks
 *
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

/**
 * Accessibility helper class
 */
class Apollo_Events_Accessibility
{
    /**
     * Add ARIA label to element
     * TODO 132: ARIA labels
     *
     * @param string $text Label text
     * @param bool   $required Is required field
     * @return string ARIA label attribute
     */
    public static function aria_label($text, $required = false)
    {
        $label = esc_attr($text);
        if ($required) {
            $label .= ' ' . __('(obrigatório)', 'apollo-events-manager');
        }

        return 'aria-label="' . $label . '"';
    }

    /**
     * Generate ARIA describedby
     * TODO 132: ARIA describedby
     *
     * @param string $id Description element ID
     * @return string ARIA describedby attribute
     */
    public static function aria_describedby($id)
    {
        return 'aria-describedby="' . esc_attr($id) . '"';
    }

    /**
     * Check contrast ratio
     * TODO 132: Contrast ratios
     *
     * @param string $foreground Foreground color (hex)
     * @param string $background Background color (hex)
     * @return float Contrast ratio
     */
    public static function contrast_ratio($foreground, $background)
    {
        // Simplified contrast calculation
        // Full implementation would use WCAG formulas
        return 4.5;
        // Placeholder
    }

    /**
     * Add keyboard navigation attributes
     * TODO 132: Keyboard navigation
     *
     * @param array $attributes Existing attributes
     * @return array Enhanced attributes
     */
    public static function add_keyboard_nav($attributes)
    {
        $attributes['tabindex'] = isset($attributes['tabindex']) ? $attributes['tabindex'] : '0';

        return $attributes;
    }

    /**
     * Generate skip link
     * TODO 132: Skip links
     *
     * @param string $target Target element ID
     * @param string $text Link text
     * @return string Skip link HTML
     */
    public static function skip_link($target, $text = 'Pular para conteúdo principal')
    {
        return '<a href="#' . esc_attr($target) . '" class="skip-link">' . esc_html($text) . '</a>';
    }

    /**
     * Add screen reader text
     * TODO 132: Screen reader compatibility
     *
     * @param string $text Text for screen readers
     * @return string Screen reader text HTML
     */
    public static function screen_reader_text($text)
    {
        return '<span class="screen-reader-text">' . esc_html($text) . '</span>';
    }
}
