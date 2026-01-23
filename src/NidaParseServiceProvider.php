<?php

namespace NidaParse;

use Illuminate\Support\ServiceProvider;

class NidaParseServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/nida-parse.php',
            'nida-parse'
        );

        // Register services as singletons
        $this->app->singleton(\NidaParse\Services\LocationLookup::class, function ($app) {
            $csvDir = config('nida-parse.csv_directory');
            $combinedPath = config('nida-parse.combined_csv_path');
            return new \NidaParse\Services\LocationLookup($csvDir, $combinedPath);
        });

        $this->app->singleton(\NidaParse\Services\NidaService::class, function ($app) {
            return new \NidaParse\Services\NidaService(
                $app->make(\NidaParse\Services\LocationLookup::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish config file
        $this->publishes([
            __DIR__ . '/../config/nida-parse.php' => config_path('nida-parse.php'),
        ], 'nida-parse-config');

        // Publish CSV files to storage
        $this->publishes([
            __DIR__ . '/../resources/location_files_code' => storage_path('app/location_files_code'),
            __DIR__ . '/../resources/tanzania_locations_combined.csv' => storage_path('app/tanzania_locations_combined.csv'),
        ], 'nida-parse-csv');

        // Load helper function
        if (file_exists(__DIR__ . '/Helpers/nida_helper.php')) {
            require_once __DIR__ . '/Helpers/nida_helper.php';
        }
    }
}
