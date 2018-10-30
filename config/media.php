<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Media Model
    |--------------------------------------------------------------------------
    |
    | This is the Media model.
    |
    */
    'media' => 'Viviniko\Media\Models\Media',

    /*
    |--------------------------------------------------------------------------
    | Medias Table
    |--------------------------------------------------------------------------
    |
    | This is the medias table.
    |
    */
    'medias_table' => 'media_medias',

    /*
    |--------------------------------------------------------------------------
    | Default Medias Disk.
    |--------------------------------------------------------------------------
    |
    | This is the medias disk.
    |
    */
    'disk' => 'media',

    'groups' => [
        'default' => [
            'disk' => 'media',
            'dir_format' => 'default',
            'name_format' => '@',
        ]
    ],
];