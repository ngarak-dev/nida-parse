<?php

use NidaParse\Services\LocationLookup;
use NidaParse\Services\NidaService;

if (!function_exists('get_basic_info')) {
    /**
     * Convenience function: parse and lookup by NIN only.
     * 
     * @param string $nin The NIN/NIDA string to parse
     * @param string|null $csvDir Optional path to the CSV directory (overrides default)
     * @param bool $debug Enable debug prints
     * @return array|null Dictionary of basic info or null on failure
     */
    function get_basic_info(string $nin, ?string $csvDir = null, bool $debug = false): ?array
    {
        $lookup = $csvDir ? new LocationLookup($csvDir) : new LocationLookup();
        $service = new NidaService($lookup);
        return $service->getBasicInfo($nin, $debug);
    }
}
