<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class Csv
{
    /**
     * Stream rows as a CSV download. Cells that a spreadsheet would treat
     * as a formula (=, +, -, @, tab, CR) are prefixed with a quote to
     * prevent CSV/formula injection from customer-controlled fields.
     *
     * @param  iterable<array<int, mixed>>  $rows
     */
    public static function download(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel renders RM/names correctly
            fputcsv($handle, $header);

            foreach ($rows as $row) {
                fputcsv($handle, array_map([self::class, 'sanitize'], $row));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function sanitize(mixed $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
