<?php

namespace NidaParse\Services;

class NidaParser
{
    /**
     * Parse NIDA / NIN strings into components.
     * 
     * Supports dashed format YYYYMMDD-LLLLL-SSSSS-XX and compact YYYYMMDDWWWWWSSSSSXX.
     */
    public static function parse(string $nidaStr): array
    {
        $s = trim($nidaStr);
        
        if (strpos($s, '-') !== false) {
            $parts = explode('-', $s);
            if (count($parts) !== 4) {
                return ['error' => 'Invalid dashed format: expected 4 segments'];
            }
            [$rawDate, $wardNumber, $seqNumber, $unknown] = $parts;
        } else {
            if (strlen($s) < 20) {
                return ['error' => 'Invalid compact format: too short'];
            }
            $rawDate = substr($s, 0, 8);
            $wardNumber = substr($s, 8, 5);
            $seqNumber = substr($s, 13, 5);
            $unknown = substr($s, 18, 2);
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
