<?php

namespace App\Modules\Catalog\Infrastructure\Import;

use App\Modules\Catalog\Domain\Contracts\ProductImportReaderInterface;
use App\Modules\Catalog\Domain\Exceptions\ProductImportException;

final class XlsxProductImportReader implements ProductImportReaderInterface
{
    public function rows(string $path, ?string $extension = null): iterable
    {
        $extension = strtolower($extension ?: pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['csv', 'txt'], true)) {
            yield from $this->csvRows($path);

            return;
        }

        if ($extension !== 'xlsx') {
            throw ProductImportException::unreadableFile();
        }

        yield from $this->xlsxRows($path);
    }

    private function csvRows(string $path): iterable
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw ProductImportException::unreadableFile();
        }

        try {
            $headers = fgetcsv($handle);
            if ($headers === false) {
                throw ProductImportException::unreadableFile();
            }

            $headers = $this->headers($headers);
            while (($values = fgetcsv($handle)) !== false) {
                if ($this->isBlank($values)) {
                    continue;
                }

                yield $this->row($headers, $values);
            }
        } finally {
            fclose($handle);
        }
    }

    private function xlsxRows(string $path): iterable
    {
        $sharedStrings = $this->sharedStrings($path);
        $contents = $this->zipEntry($path, 'xl/worksheets/sheet1.xml');
        if ($contents === null) {
            throw ProductImportException::unreadableFile();
        }

        $xml = simplexml_load_string($contents);
        if ($xml === false) {
            throw ProductImportException::unreadableFile();
        }

        $namespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $sheet = $xml->children($namespace)->sheetData;
        $headers = null;

        foreach ($sheet->children($namespace)->row as $row) {
            $values = [];
            foreach ($row->children($namespace)->c as $cell) {
                $attributes = $cell->attributes();
                $reference = (string) ($attributes['r'] ?? '');
                $column = preg_replace('/\d+/', '', $reference) ?: '';
                $cellType = (string) ($attributes['t'] ?? '');
                $value = $cellType === 'inlineStr'
                    ? (string) $cell->children($namespace)->is->t
                    : (string) $cell->children($namespace)->v;
                if ($cellType === 's') {
                    $value = $sharedStrings[(int) $value] ?? $value;
                }
                $values[$this->columnNumber($column)] = $value;
            }

            if ($headers === null) {
                $headers = $this->headers($values);

                continue;
            }

            if (! $this->isBlank($values)) {
                yield $this->row($headers, $values);
            }
        }
    }

    private function sharedStrings(string $path): array
    {
        $contents = $this->zipEntry($path, 'xl/sharedStrings.xml');
        if ($contents === null) {
            return [];
        }

        $xml = simplexml_load_string($contents);
        if ($xml === false) {
            return [];
        }

        $namespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $shared = [];
        foreach ($xml->children($namespace)->si as $item) {
            $item = $item->children($namespace);
            $shared[] = (string) ($item->t ?: implode('', array_map('strval', iterator_to_array($item->r->t ?? []))));
        }

        return $shared;
    }

    private function zipEntry(string $path, string $entry): ?string
    {
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            return null;
        }

        $contents = $zip->getFromName($entry);
        $zip->close();

        return $contents === false ? null : $contents;
    }

    private function headers(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $header) {
            $header = strtolower(trim((string) $header));
            $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? '';
            $normalized[] = trim($header, '_');
        }

        return $normalized;
    }

    private function row(array $headers, array $values): array
    {
        $values = array_values($values);

        return array_combine($headers, array_pad($values, count($headers), null)) ?: [];
    }

    private function columnNumber(string $column): int
    {
        $number = 0;
        foreach (str_split($column) as $letter) {
            $number = ($number * 26) + ord($letter) - 64;
        }

        return max(0, $number - 1);
    }

    private function isBlank(array $values): bool
    {
        return count(array_filter($values, static fn ($value): bool => trim((string) $value) !== '')) === 0;
    }
}
