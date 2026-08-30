<?php

namespace NidaParse\Services;

class LocationLookup
{
    /**
     * Directory with per-region CSVs (11.csv, 21.csv, ...).
     */
    protected string $csvDir;

    /**
     * Optional path to a single, combined Tanzania CSV
     * (e.g. storage/app/tanzania_locations_combined.csv).
     */
    protected ?string $combinedPath = null;
    /**
     * Cache ward lookup results (including misses) for this instance.
     *
     * @var array<string, array|null>
     */
    protected array $lookupCache = [];

    /**
     * In-memory index for the combined CSV keyed by WARDCODE.
     *
     * @var array<string, array>
     */
    protected array $combinedWardIndex = [];
    protected bool $combinedWardIndexLoaded = false;

    public function __construct(?string $csvDir = null, ?string $combinedPath = null)
    {
        // Default to package resources if no path provided
        if ($csvDir === null) {
            $csvDir = $this->getDefaultCsvDir();
        }
        $this->csvDir = $csvDir;

        // If a combined file was provided, use it.
        if ($combinedPath !== null) {
            $this->combinedPath = $combinedPath;
        } else {
            // Otherwise, auto-detect the default combined file if it exists.
            // First try Laravel storage path (for published assets)
            $auto = storage_path('app/tanzania_locations_combined.csv');
            if (file_exists($auto)) {
                $this->combinedPath = $auto;
            } else {
                // Fall back to package resources
                $packagePath = __DIR__ . '/../../resources/tanzania_locations_combined.csv';
                if (file_exists($packagePath)) {
                    $this->combinedPath = $packagePath;
                }
            }
        }
    }

    /**
     * Get the default CSV directory path from package resources
     */
    protected function getDefaultCsvDir(): string
    {
        // First try Laravel storage path (for published assets)
        $storagePath = storage_path('app/location_files_code');
        if (is_dir($storagePath)) {
            return $storagePath;
        }

        // Fall back to package resources
        $packagePath = __DIR__ . '/../../resources/location_files_code';
        if (is_dir($packagePath)) {
            return $packagePath;
        }

        // Last resort: return storage path anyway
        return $storagePath;
    }

    protected function csvPathForPrefix(string $prefix): string
    {
        return $this->csvDir . DIRECTORY_SEPARATOR . $prefix . '.csv';
    }

    /**
     * Scan a CSV file (with canonical headers) for a given ward code.
     *
     * This is primarily used for the combined Tanzania CSV where the
     * columns are normalized to:
     * REGION, REGIONCODE, DISTRICT, DISTRICTCODE, WARD, WARDCODE, STREET, PLACES
     */
    protected function scanCanonicalCsv(string $file, string $wardStr, bool $debug = false): ?array
    {
        if ($debug) {
            echo "Looking for ward {$wardStr} in combined file: {$file}\n";
        }

        $index = $this->loadCombinedWardIndex($file, $debug);
        if ($index === null) {
            if ($debug) {
                echo "Combined CSV index unavailable for file: {$file}\n";
            }
            return null;
        }

        if (array_key_exists($wardStr, $index)) {
            $result = $index[$wardStr];
            if ($debug) {
                echo "Found row in combined file: " . json_encode($result) . "\n";
            }
            return $result;
        }

        if ($debug) {
            echo "No matching ward code {$wardStr} found in combined file {$file}\n";
        }

        return null;
    }

    /**
     * Build a ward index from the canonical combined CSV once per instance.
     *
     * @return array<string, array>|null
     */
    protected function loadCombinedWardIndex(string $file, bool $debug = false): ?array
    {
        if ($this->combinedWardIndexLoaded) {
            return $this->combinedWardIndex;
        }

        if (!file_exists($file)) {
            return null;
        }

        try {
            $handle = fopen($file, 'r');
            if ($handle === false) {
                if ($debug) {
                    echo "Error opening combined CSV file: {$file}\n";
                }
                return null;
            }

            $headers = fgetcsv($handle);
            if ($headers === false) {
                fclose($handle);
                return null;
            }

            $indexMap = [];
            foreach ($headers as $idx => $name) {
                $upper = strtoupper(trim((string) $name));
                if ($upper !== '') {
                    $indexMap[$upper] = $idx;
                }
            }

            $buildResult = [];
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) === 0) {
                    continue;
                }

                $get = function (string $name) use ($indexMap, $row): string {
                    $upper = strtoupper($name);
                    if (!array_key_exists($upper, $indexMap)) {
                        return '';
                    }

                    $idx = $indexMap[$upper];
                    return isset($row[$idx]) ? trim((string) $row[$idx]) : '';
                };

                $wardCode = $get('WARDCODE');
                if ($wardCode === '' || array_key_exists($wardCode, $buildResult)) {
                    continue;
                }

                $buildResult[$wardCode] = [
                    'REGION'       => $get('REGION'),
                    'REGIONCODE'   => $get('REGIONCODE'),
                    'DISTRICT'     => $get('DISTRICT'),
                    'DISTRICTCODE' => $get('DISTRICTCODE'),
                    'WARD'         => $get('WARD'),
                    'WARDCODE'     => $wardCode,
                    'STREET'       => $get('STREET'),
                    'PLACES'       => $get('PLACES'),
                ];
            }

            fclose($handle);
            $this->combinedWardIndex = $buildResult;
            $this->combinedWardIndexLoaded = true;
            return $this->combinedWardIndex;
        } catch (\Exception $e) {
            if ($debug) {
                echo "Error reading combined CSV: " . $e->getMessage() . "\n";
            }
            return null;
        }
    }

    public function getAdministrativeHierarchy(string $wardId, bool $debug = false): ?array
    {
        $wardStr = trim((string) $wardId);

        if ($wardStr === '') {
            if ($debug) {
                echo "ward_id is empty\n";
            }
            return null;
        }

        if (array_key_exists($wardStr, $this->lookupCache)) {
            if ($debug) {
                echo "Returning cached lookup result for ward {$wardStr}\n";
            }
            return $this->lookupCache[$wardStr];
        }

        // 1) Prefer the combined Tanzania file if available.
        if ($this->combinedPath !== null) {
            $result = $this->scanCanonicalCsv($this->combinedPath, $wardStr, $debug);
            if ($result !== null) {
                $this->lookupCache[$wardStr] = $result;
                return $result;
            }

            // If not found in the combined file, fall back to the legacy per-prefix CSVs.
            if ($debug) {
                echo "Falling back to per-region CSVs for ward {$wardStr}\n";
            }
        }

        // 2) Legacy behavior: look up by 2-digit prefix and scan that region file.
        if (strlen($wardStr) < 2) {
            if ($debug) {
                echo "ward_id must have at least 2 characters to select a CSV file\n";
            }
            $this->lookupCache[$wardStr] = null;
            return null;
        }

        $prefix = substr($wardStr, 0, 2);
        $csvFilename = $this->csvPathForPrefix($prefix);

        if ($debug) {
            echo "Looking for CSV file: {$csvFilename}\n";
        }

        if (!file_exists($csvFilename)) {
            if ($debug) {
                echo "CSV file not found: {$csvFilename}\n";
            }
            $this->lookupCache[$wardStr] = null;
            return null;
        }

        try {
            $handle = fopen($csvFilename, 'r');
            if ($handle === false) {
                if ($debug) {
                    echo "Error opening CSV file: {$csvFilename}\n";
                }
                return null;
            }

            // Read header row
            $headers = fgetcsv($handle);
            if ($headers === false) {
                fclose($handle);
                return null;
            }

            while (($row = fgetcsv($handle)) !== false) {
                $rowData = array_combine($headers, $row);
                if ($rowData === false) {
                    continue;
                }

                $matchedKey = null;

                // Scan all columns for a match
                foreach ($rowData as $k => $v) {
                    if ($v === null || $v === '') {
                        continue;
                    }

                    $vStr = trim((string) $v);
                    if ($vStr === $wardStr) {
                        $matchedKey = $k;
                        break;
                    } else {
                        try {
                            if ((int) $vStr === (int) $wardStr) {
                                $matchedKey = $k;
                                break;
                            }
                        } catch (\Exception $e) {
                            // Ignore conversion errors
                        }
                    }
                }

                if ($matchedKey !== null) {
                    $wardcodeVal = trim(
                        $rowData['WARDCODE'] ??
                        $rowData['PostCode'] ??
                        $rowData['POSTCODE'] ??
                        $rowData['wardcode'] ??
                        $rowData[$matchedKey] ??
                        ''
                    );

                    $result = [
                        'REGION' => trim($rowData['REGION'] ?? $rowData['Region'] ?? ''),
                        'REGIONCODE' => trim($rowData['REGIONCODE'] ?? $rowData['REGION_CODE'] ?? $rowData['POSTCODE'] ?? ''),
                        'DISTRICT' => trim($rowData['DISTRICT'] ?? $rowData['District'] ?? ''),
                        'DISTRICTCODE' => trim($rowData['DISTRICTCODE'] ?? $rowData['DISTRICT_CODE'] ?? ''),
                        'WARD' => trim($rowData['WARD'] ?? $rowData['Ward'] ?? ''),
                        'WARDCODE' => $wardcodeVal,
                        'STREET' => trim($rowData['STREET'] ?? $rowData['Street'] ?? ''),
                        'PLACES' => trim($rowData['PLACES'] ?? $rowData['Places'] ?? ''),
                    ];

                    if ($debug) {
                        echo "Found row (matched column {$matchedKey}) in regional file: " . json_encode($result) . "\n";
                    }

                    fclose($handle);
                    $this->lookupCache[$wardStr] = $result;
                    return $result;
                }
            }

            fclose($handle);

            if ($debug) {
                echo "No matching ward code {$wardStr} found in {$csvFilename}\n";
            }
            $this->lookupCache[$wardStr] = null;
            return null;

        } catch (\Exception $e) {
            if ($debug) {
                echo "Error reading CSV: " . $e->getMessage() . "\n";
            }
            $this->lookupCache[$wardStr] = null;
            return null;
        }
    }
}
