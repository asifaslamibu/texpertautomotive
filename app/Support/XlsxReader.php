<?php

namespace App\Support;

use RuntimeException;
use XMLReader;
use ZipArchive;

/**
 * Minimal streaming .xlsx reader (no extra Composer package needed).
 *
 * Reads cell values only — formulas yield their cached result, styles are ignored.
 * Rows are streamed with XMLReader, so very large sheets stay light on memory.
 */
class XlsxReader
{
    private ZipArchive $zip;

    private array $strings = [];

    /** sheet name => path inside the zip */
    private array $sheets = [];

    public function __construct(string $path)
    {
        $this->zip = new ZipArchive;
        if ($this->zip->open($path) !== true) {
            throw new RuntimeException('Could not open the Excel file.');
        }

        $this->loadSharedStrings();
        $this->loadSheets();
    }

    /** @return string[] sheet names in workbook order */
    public function sheetNames(): array
    {
        return array_keys($this->sheets);
    }

    /**
     * Yield each row as a 0-indexed array of strings (gaps filled with '').
     *
     * @return \Generator<int, array<int, string>>
     */
    public function rows(string $sheet): \Generator
    {
        if (! isset($this->sheets[$sheet])) {
            throw new RuntimeException("Sheet \"{$sheet}\" not found.");
        }

        $xml = new XMLReader;
        $xml->XML($this->zip->getFromName($this->sheets[$sheet]), null, LIBXML_COMPACT | LIBXML_PARSEHUGE);

        $row = null;
        $col = 0;
        $type = null;
        $value = '';

        while ($xml->read()) {
            if ($xml->nodeType === XMLReader::ELEMENT) {
                switch ($xml->localName) {
                    case 'row':
                        $row = [];
                        if ($xml->isEmptyElement) {
                            yield $row;
                            $row = null;
                        }
                        break;
                    case 'c':
                        $col = self::columnIndex((string) $xml->getAttribute('r'));
                        $type = $xml->getAttribute('t');
                        $value = '';
                        if ($xml->isEmptyElement) {
                            $row[$col] = '';
                        }
                        break;
                    case 'v':
                    case 't': // inline strings: <is><t>…</t></is>
                        $value .= $xml->readString();
                        break;
                }
            } elseif ($xml->nodeType === XMLReader::END_ELEMENT) {
                if ($xml->localName === 'c' && $row !== null) {
                    $row[$col] = $type === 's' ? ($this->strings[(int) $value] ?? '') : $value;
                } elseif ($xml->localName === 'row' && $row !== null) {
                    $filled = $row ? array_fill(0, max(array_keys($row)) + 1, '') : [];
                    yield array_replace($filled, $row);
                    $row = null;
                }
            }
        }

        $xml->close();
    }

    /** "AB12" → 27 (0-indexed column). */
    private static function columnIndex(string $ref): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($ref));
        $n = 0;
        foreach (str_split($letters) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return max(0, $n - 1);
    }

    private function loadSharedStrings(): void
    {
        $data = $this->zip->getFromName('xl/sharedStrings.xml');
        if ($data === false) {
            return;
        }

        $xml = new XMLReader;
        $xml->XML($data, null, LIBXML_COMPACT | LIBXML_PARSEHUGE);
        $current = null;
        while ($xml->read()) {
            if ($xml->nodeType === XMLReader::ELEMENT && $xml->localName === 'si') {
                $current = '';
            } elseif ($xml->nodeType === XMLReader::ELEMENT && $xml->localName === 't' && $current !== null) {
                $current .= $xml->readString();
            } elseif ($xml->nodeType === XMLReader::END_ELEMENT && $xml->localName === 'si') {
                $this->strings[] = $current;
                $current = null;
            }
        }
        $xml->close();
    }

    private function loadSheets(): void
    {
        $rels = [];
        $relXml = simplexml_load_string((string) $this->zip->getFromName('xl/_rels/workbook.xml.rels'));
        foreach ($relXml->Relationship ?? [] as $rel) {
            $target = ltrim((string) $rel['Target'], '/');
            $rels[(string) $rel['Id']] = str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
        }

        $book = simplexml_load_string((string) $this->zip->getFromName('xl/workbook.xml'));
        foreach ($book->sheets->sheet ?? [] as $sheet) {
            $rid = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            if (isset($rels[$rid])) {
                $this->sheets[trim((string) $sheet['name'])] = $rels[$rid];
            }
        }
    }
}
