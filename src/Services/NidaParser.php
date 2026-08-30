<?php

namespace NidaParse\Services;

class NidaParser
{
    private const DASHED_PATTERN = '/^\d{8}-\d{5}-\d{5}-\d{2}$/';
    private const COMPACT_PATTERN = '/^\d{20}$/';

    /**
     * Parse NIDA / NIN strings into components.
     * 
     * Supports dashed format YYYYMMDD-LLLLL-SSSSS-XX and compact YYYYMMDDWWWWWSSSSSXX.
     */
    public static function parse(string $nidaStr): array
    {
        $s = trim($nidaStr);

        if ($s === '') {
            return ['error' => 'NIN input cannot be empty'];
        }

        if (strpos($s, '-') !== false) {
            if (!preg_match(self::DASHED_PATTERN, $s)) {
                return ['error' => 'Invalid dashed format: expected YYYYMMDD-LLLLL-SSSSS-XX'];
            }

            $parts = explode('-', $s);
            [$rawDate, $wardNumber, $seqNumber, $unknown] = $parts;
        } else {
            if (!preg_match(self::COMPACT_PATTERN, $s)) {
                return ['error' => 'Invalid compact format: expected exactly 20 digits'];
            }

            $rawDate = substr($s, 0, 8);
            $wardNumber = substr($s, 8, 5);
            $seqNumber = substr($s, 13, 5);
            $unknown = substr($s, 18, 2);
        }

        $year = (int) substr($rawDate, 0, 4);
        $month = (int) substr($rawDate, 4, 2);
        $day = (int) substr($rawDate, 6, 2);
        if (!checkdate($month, $day, $year)) {
            return ['error' => 'Invalid birth date segment in NIN'];
        }

        $formattedDate = substr($rawDate, 0, 4) . '-' . substr($rawDate, 4, 2) . '-' . substr($rawDate, 6, 2);

        return [
            'date' => $formattedDate,
            'ward_number' => $wardNumber,
            'seq_number' => $seqNumber,
            'unknown' => $unknown,
        ];
    }
}
