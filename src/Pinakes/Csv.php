<?php declare(strict_types=1);

namespace App\Pinakes;

class Csv {
    public static function read_file(string $path, string $separator = ",", string $enclosure = '"', string $escape = "\\"): array {
        $rows = array_map(fn ($row) => str_getcsv($row, $separator, $enclosure, $escape), file($path));

        $header = array_shift($rows);

        $result = [];
        foreach ($rows as $row) {
            $result[] = array_combine($header, $row);
        }
        return $result;
    }
}