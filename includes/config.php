<?php

// phpcs:ignoreFile
// Block direct access
defined('ABSPATH') || exit;

return [
    'cpt' => [
        'event' => 'event_listing',
        'dj'    => 'event_dj',
        'local' => 'event_local',
    ],
    'tax' => [
        'category' => 'event_listing_category',
        'type'     => 'event_listing_type',
        'tag'      => 'event_listing_tag',
        'sounds'   => 'event_sounds',
    ],
    'meta' => [
        'event' => [
            '_event_title',
            '_event_banner',
            '_event_video_url',
            '_event_start_date',
            '_event_end_date',
            '_event_start_time',
            '_event_end_time',
            '_tickets_ext',
            '_event_description',
            '_event_type',
            '_event_category',
            '_event_location',
            '_3_imagens_promo',
            '_imagem_final',
            'timetable',
            'cupom_ario',
            '_dj_name',
        ],
        'local' => [ '_local_latitude', '_local_longitude', '_local_address' ],
    ],
];
