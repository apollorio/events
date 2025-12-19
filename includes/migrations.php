<?php

// phpcs:ignoreFile
/**
 * Apollo Events Manager - Upgrade helpers
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('apollo_aem_upgrade_210')) {
    /**
     * Upgrade legacy meta keys to the 2.1.0 schema.
     *
     * @param bool $force When true, ignores the completion flag option.
     *
     * @return array{skipped:bool, migrated:int, inspected:int} Summary stats.
     */
    function apollo_aem_upgrade_210($force = false)
    {
        $already_ran = (bool) get_option('apollo_aem_upgraded_210');
        if ($already_ran && ! $force) {
            return [
                'skipped'   => true,
                'migrated'  => 0,
                'inspected' => 0,
            ];
        }

        $query = new WP_Query(
            [
                'post_type'      => 'event_listing',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'meta_query'     => [
                    'relation' => 'OR',
                    [
                        'key'     => '_event_djs',
                        'compare' => 'EXISTS',
                    ],
                    [
                        'key'     => '_event_local',
                        'compare' => 'EXISTS',
                    ],
                    [
                        'key'     => '_timetable',
                        'compare' => 'EXISTS',
                    ],
                ],
            ]
        );

        $inspected = 0;
        $migrated  = 0;

        foreach ($query->posts as $event_id) {
            ++$inspected;
            $changed = false;

            $dj_legacy = get_post_meta($event_id, '_event_djs', true);
            $dj_target = get_post_meta($event_id, '_event_dj_ids', true);
            if ($dj_legacy && empty($dj_target)) {
                $dj_ids = array_map('intval', (array) $dj_legacy);
                update_post_meta($event_id, '_event_dj_ids', $dj_ids);
                $changed = true;
            }

            $loc_legacy = get_post_meta($event_id, '_event_local', true);
            $loc_target = get_post_meta($event_id, '_event_local_ids', true);
            if ($loc_legacy && empty($loc_target)) {
                $loc_ids = array_map('intval', (array) $loc_legacy);
                update_post_meta($event_id, '_event_local_ids', $loc_ids);
                $changed = true;
            }

            $legacy_timetable = get_post_meta($event_id, '_timetable', true);
            $timetable_target = get_post_meta($event_id, '_event_timetable', true);
            if ($legacy_timetable && empty($timetable_target)) {
                $clean_timetable = apollo_sanitize_timetable(
                    (array) $legacy_timetable
                );

                if (! empty($clean_timetable)) {
                    update_post_meta(
                        $event_id,
                        '_event_timetable',
                        $clean_timetable
                    );
                    $changed = true;
                }
            }

            if ($dj_legacy) {
                delete_post_meta($event_id, '_event_djs');
                $changed = true;
            }

            if ($loc_legacy) {
                delete_post_meta($event_id, '_event_local');
                $changed = true;
            }

            if ($legacy_timetable) {
                delete_post_meta($event_id, '_timetable');
                $changed = true;
            }

            if ($changed) {
                ++$migrated;
            }
        }//end foreach

        wp_reset_postdata();

        update_option('apollo_aem_upgraded_210', 1, false);

        return [
            'skipped'   => false,
            'migrated'  => $migrated,
            'inspected' => $inspected,
        ];
    }
}//end if

add_action(
    'admin_init',
    static function () {
        if (! is_admin() || wp_doing_ajax()) {
            return;
        }

        $result = apollo_aem_upgrade_210(false);

        if (! $result['skipped'] && defined('WP_DEBUG') && WP_DEBUG) {
            error_log(
                sprintf(
                    'Apollo Events Manager upgraded %d of %d events.',
                    $result['migrated'],
                    $result['inspected']
                )
            );
        }
    },
    12
);

add_action(
    'apollo_aem_version_upgrade',
    static function ($from_version, $to_version) {
        $target_reached = version_compare((string) $to_version, '2.1.0', '>=');
        $from_before    = $from_version === null || version_compare((string) $from_version, '2.1.0', '<');

        if ($target_reached && $from_before) {
            delete_option('apollo_aem_upgraded_210');
        }
    },
    10,
    2
);

add_action(
    'plugins_loaded',
    static function () {
        if (! class_exists('WP_CLI')) {
            return;
        }

        $cli_class = '\\WP_CLI';

        call_user_func(
            [ $cli_class, 'add_command' ],
            'apollo migrate_meta',
            static function ($args, $assoc_args) use ($cli_class) {
                $force = ! empty($assoc_args['force']);
                $stats = apollo_aem_upgrade_210((bool) $force);

                if ($stats['skipped']) {
                    call_user_func(
                        [ $cli_class, 'warning' ],
                        'Migration already completed. Use --force to rerun.'
                    );

                    return;
                }

                call_user_func(
                    [ $cli_class, 'success' ],
                    sprintf(
                        'Migrated %d of %d events.',
                        $stats['migrated'],
                        $stats['inspected']
                    )
                );
            },
            [
                'shortdesc' => 'Normalize event meta keys to the 2.1.0 schema.',
                'synopsis'  => [
                    [
                        'type'        => 'assoc',
                        'name'        => 'force',
                        'optional'    => true,
                        'description' => 'Ignore completion flag and run again.',
                    ],
                ],
            ]
        );
    },
    20
);
