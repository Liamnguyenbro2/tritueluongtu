<?php

namespace App\Services;

use DateTimeInterface;
use RuntimeException;
use ZipArchive;

class SimpleXlsxExporter
{
    public function build(string $worksheetName, array $rows): string
    {
        [$normalizedRows, $columnWidths] = $this->normalizeRows($rows);

        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');

        if ($tempFile === false) {
            throw new RuntimeException('Cannot create temporary xlsx file.');
        }

        $zip = new ZipArchive();

        if ($zip->open($tempFile, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Cannot open temporary xlsx archive.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $zip->addFromString('docProps/app.xml', $this->appPropertiesXml());
        $zip->addFromString('docProps/core.xml', $this->corePropertiesXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($worksheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($normalizedRows, $columnWidths));
        $zip->close();

        return $tempFile;
    }

    private function contentTypesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
    <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
    <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
XML;
    }

    private function rootRelationshipsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
XML;
    }

    private function appPropertiesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
    <Application>Codex</Application>
</Properties>
XML;
    }

    private function corePropertiesXml(): string
    {
        $now = now()->toAtomString();

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <dc:creator>Codex</dc:creator>
    <cp:lastModifiedBy>Codex</cp:lastModifiedBy>
    <dcterms:created xsi:type="dcterms:W3CDTF">{$now}</dcterms:created>
    <dcterms:modified xsi:type="dcterms:W3CDTF">{$now}</dcterms:modified>
</cp:coreProperties>
XML;
    }

    private function workbookXml(string $worksheetName): string
    {
        $safeSheetName = $this->xml($worksheetName);

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="{$safeSheetName}" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>
XML;
    }

    private function workbookRelationshipsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;
    }

    private function stylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="2">
        <font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>
        <font><b/><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>
    </fonts>
    <fills count="1">
        <fill><patternFill patternType="none"/></fill>
    </fills>
    <borders count="1">
        <border><left/><right/><top/><bottom/><diagonal/></border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="2">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>
    </cellXfs>
    <cellStyles count="1">
        <cellStyle name="Normal" xfId="0" builtinId="0"/>
    </cellStyles>
</styleSheet>
XML;
    }

    private function worksheetXml(array $rows, array $columnWidths): string
    {
        $sheetRows = [];

        foreach ($rows as $rowIndex => $columns) {
            $cells = [];

            foreach (array_values($columns) as $columnIndex => $value) {
                $cellReference = $this->columnName($columnIndex + 1).($rowIndex + 1);
                $cells[] = $this->cellXml($cellReference, $value, $rowIndex === 0 ? 1 : 0);
            }

            $sheetRows[] = '<row r="'.($rowIndex + 1).'">'.implode('', $cells).'</row>';
        }

        $cols = $this->worksheetColumnsXml($columnWidths);

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    {$cols}
    <sheetData>
        {$this->implodeXml($sheetRows)}
    </sheetData>
</worksheet>
XML;
    }

    private function cellXml(string $reference, mixed $value, int $styleId = 0): string
    {
        if (is_int($value) || is_float($value)) {
            return '<c r="'.$reference.'" s="'.$styleId.'"><v>'.$value.'</v></c>';
        }

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('d/m/Y H:i:s');
        }

        return '<c r="'.$reference.'" s="'.$styleId.'" t="inlineStr"><is><t>'.$this->xml((string) $value).'</t></is></c>';
    }

    private function normalizeRows(array $rows): array
    {
        if ($rows === []) {
            return [[], []];
        }

        $firstRow = $rows[0];
        $isAssociative = is_array($firstRow) && array_keys($firstRow) !== range(0, count($firstRow) - 1);

        if ($isAssociative) {
            $headers = array_keys($firstRow);
            $normalizedRows = [array_values($headers)];

            foreach ($rows as $row) {
                $normalizedRows[] = array_map(
                    fn (string $key) => $row[$key] ?? '',
                    $headers
                );
            }
        } else {
            $normalizedRows = array_map(
                fn ($row) => is_array($row) ? array_values($row) : [(string) $row],
                $rows
            );
        }

        return [$normalizedRows, $this->calculateColumnWidths($normalizedRows)];
    }

    private function calculateColumnWidths(array $rows): array
    {
        $widths = [];

        foreach ($rows as $row) {
            foreach (array_values($row) as $index => $value) {
                if ($value instanceof DateTimeInterface) {
                    $value = $value->format('d/m/Y H:i:s');
                }

                $length = function_exists('mb_strwidth')
                    ? mb_strwidth((string) $value, 'UTF-8')
                    : strlen((string) $value);

                $widths[$index] = max($widths[$index] ?? 10, min($length + 4, 60));
            }
        }

        return $widths;
    }

    private function worksheetColumnsXml(array $columnWidths): string
    {
        if ($columnWidths === []) {
            return '';
        }

        $columns = [];

        foreach ($columnWidths as $index => $width) {
            $position = $index + 1;
            $columns[] = '<col min="'.$position.'" max="'.$position.'" width="'.$width.'" customWidth="1"/>';
        }

        return '<cols>'.implode('', $columns).'</cols>';
    }

    private function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function implodeXml(array $rows): string
    {
        return implode('', $rows);
    }
}
