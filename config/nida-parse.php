<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CSV Directory Path
    |--------------------------------------------------------------------------
    |
    | The directory path where the location CSV files are stored.
    | Defaults to storage/app/location_files_code after publishing.
    |
    */
    'csv_directory' => storage_path('app/location_files_code'),

    /*
    |--------------------------------------------------------------------------
    | Combined CSV Path
    |--------------------------------------------------------------------------
    |
    | Optional path to a single combined Tanzania CSV file.
    | If set, this will be used instead of per-region CSV files.
    |
    */
    'combined_csv_path' => null,
];
