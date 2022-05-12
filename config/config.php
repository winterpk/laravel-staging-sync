<?php

/**
 * Staging sync config variables
 *
 */
return [
    'active' => env('STAGING_SYNC_ACTIVE', false),
    'dumpFilePath' => env('STAGING_SYNC_DUMPFILE_PATH', storage_path('laravel-staging-sync'))
];
