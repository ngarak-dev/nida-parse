<?php

namespace NidaParse\Services;

class NidaService
{
    protected LocationLookup $locationLookup;

    public function __construct(?LocationLookup $locationLookup = null)
    {
        $this->locationLookup = $locationLookup ?? new LocationLookup();
    }

    public function getBasicInfo(string $nidaStr, bool $debug = false): ?array
    {
        $parseData = NidaParser::parse($nidaStr);

        if (isset($parseData['error'])) {
            if ($debug) {
                echo "Parse error: {$parseData['error']}\n";
            }
            return null;
        }

        $wardId = $parseData['ward_number'] ?? null;
        if ($wardId === null) {
            if ($debug) {
                echo "Failed to parse ward number from NIN\n";
            }
            return null;
        }

        $locationData = $this->locationLookup->getAdministrativeHierarchy($wardId, $debug);

        $gender = str_starts_with((string) ($parseData['unknown'] ?? ''), '2') ? 'MALE' : 'FEMALE';

        $result = [
            'BIRTHDATE' => $parseData['date'] ?? '',
            'GENDER' => $gender,
        ];

        if ($locationData) {
            $result = array_merge($result, $locationData);
        }

        return $result;
    }
}
