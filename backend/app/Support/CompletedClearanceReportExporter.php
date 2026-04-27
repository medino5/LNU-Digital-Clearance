<?php

namespace App\Support;

use App\Models\Clearance;
use App\Models\Semester;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

class CompletedClearanceReportExporter
{
    /**
     * @param  Collection<int, Clearance>  $clearances
     */
    public function export(Semester $semester, Collection $clearances): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive is required to create Excel reports.');
        }

        if (! class_exists(\XMLWriter::class)) {
            throw new RuntimeException('PHP XMLWriter is required to create Excel reports.');
        }

        $clearances = $clearances
            ->sortBy([
                ['program_code', 'asc'],
                ['student_name', 'asc'],
                ['completed_at', 'asc'],
            ])
            ->values();

        $workbook = $this->buildWorkbookData($semester, $clearances);
        $tempPath = tempnam(sys_get_temp_dir(), 'clearance-report-');

        if ($tempPath === false) {
            throw new RuntimeException('Unable to create a temporary report file.');
        }

        $xlsxPath = $tempPath . '.xlsx';

        if (file_exists($xlsxPath)) {
            @unlink($xlsxPath);
        }

        @unlink($tempPath);

        $zip = new ZipArchive();

        if ($zip->open($xlsxPath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('Unable to create the Excel report archive.');
        }

        $sheetCount = count($workbook['sheets']);

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml($sheetCount));
        $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $zip->addFromString('docProps/app.xml', $this->appPropertiesXml(array_column($workbook['sheets'], 'name')));
        $zip->addFromString('docProps/core.xml', $this->corePropertiesXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($workbook['sheets']));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml($sheetCount));
        $zip->addFromString('xl/styles.xml', $this->stylesXml());

        foreach ($workbook['sheets'] as $index => $sheet) {
            $zip->addFromString(
                'xl/worksheets/sheet' . ($index + 1) . '.xml',
                $this->worksheetXml($sheet['rows'])
            );
        }

        $zip->close();

        return $xlsxPath;
    }

    /**
     * @param  Collection<int, Clearance>  $clearances
     * @return array{
     *     sheets: array<int, array{name:string, rows: array<int, array<int, array{type:string, value:mixed}>>}>
     * }
     */
    protected function buildWorkbookData(Semester $semester, Collection $clearances): array
    {
        $academicYear = $semester->displayAcademicYear() ?? 'Not set';
        $generatedAt = now()->format('M d, Y h:i A');
        $programGroups = $clearances->groupBy('program_code')->sortKeys();

        $summaryRows = [
            [$this->stringCell('Completed Clearance Report')],
            [$this->stringCell('Semester'), $this->stringCell($semester->label)],
            [$this->stringCell('Academic Year'), $this->stringCell($academicYear)],
            [$this->stringCell('Generated At'), $this->stringCell($generatedAt)],
            [],
            [
                $this->stringCell('Program Code'),
                $this->stringCell('Program Name'),
                $this->stringCell('Completed Clearances'),
            ],
        ];

        foreach ($programGroups as $programCode => $programClearances) {
            /** @var Clearance $firstClearance */
            $firstClearance = $programClearances->first();

            $summaryRows[] = [
                $this->stringCell((string) $programCode),
                $this->stringCell($firstClearance->program_name),
                $this->numberCell($programClearances->count()),
            ];
        }

        $summaryRows[] = [];
        $summaryRows[] = [
            $this->stringCell('Total Completed Clearances'),
            $this->numberCell($clearances->count()),
        ];

        $sheets = [
            [
                'name' => 'Summary',
                'rows' => $summaryRows,
            ],
        ];

        $usedSheetNames = ['Summary'];

        foreach ($programGroups as $programCode => $programClearances) {
            /** @var Clearance $firstClearance */
            $firstClearance = $programClearances->first();
            $sheetName = $this->uniqueSheetName((string) $programCode, $usedSheetNames);
            $usedSheetNames[] = $sheetName;

            $rows = [[
                $this->stringCell('Student ID'),
                $this->stringCell('Student Name'),
                $this->stringCell('Program Code'),
                $this->stringCell('Program Name'),
                $this->stringCell('Year Level'),
                $this->stringCell('Reference Number'),
                $this->stringCell('Completed Date/Time'),
            ]];

            foreach ($programClearances as $clearance) {
                $rows[] = [
                    $this->stringCell($clearance->student_id_number),
                    $this->stringCell($clearance->student_name),
                    $this->stringCell($clearance->program_code),
                    $this->stringCell($clearance->program_name),
                    $this->numberCell((int) $clearance->year_level),
                    $this->stringCell($clearance->reference_number ?? ''),
                    $this->stringCell(optional($clearance->completed_at)->format('M d, Y h:i A') ?? ''),
                ];
            }

            $sheets[] = [
                'name' => $sheetName,
                'rows' => $rows,
            ];
        }

        return ['sheets' => $sheets];
    }

    /**
     * @param  array<int, array{name:string, rows: array<int, array<int, array{type:string, value:mixed}>>}>  $sheets
     */
    protected function workbookXml(array $sheets): string
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8', 'yes');
        $xml->startElement('workbook');
        $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xml->writeAttribute('xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $xml->startElement('sheets');

        foreach ($sheets as $index => $sheet) {
            $xml->startElement('sheet');
            $xml->writeAttribute('name', $sheet['name']);
            $xml->writeAttribute('sheetId', (string) ($index + 1));
            $xml->writeAttribute('r:id', 'rId' . ($index + 1));
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endElement();

        return $xml->outputMemory();
    }

    protected function workbookRelationshipsXml(int $sheetCount): string
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8', 'yes');
        $xml->startElement('Relationships');
        $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/package/2006/relationships');

        for ($index = 1; $index <= $sheetCount; $index++) {
            $xml->startElement('Relationship');
            $xml->writeAttribute('Id', 'rId' . $index);
            $xml->writeAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet');
            $xml->writeAttribute('Target', 'worksheets/sheet' . $index . '.xml');
            $xml->endElement();
        }

        $xml->startElement('Relationship');
        $xml->writeAttribute('Id', 'rId' . ($sheetCount + 1));
        $xml->writeAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles');
        $xml->writeAttribute('Target', 'styles.xml');
        $xml->endElement();

        $xml->endElement();

        return $xml->outputMemory();
    }

    protected function rootRelationshipsXml(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
XML;
    }

    protected function contentTypesXml(int $sheetCount): string
    {
        $overrides = '';

        for ($index = 1; $index <= $sheetCount; $index++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $index . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
  {$overrides}
</Types>
XML;
    }

    /**
     * @param  array<int, string>  $sheetNames
     */
    protected function appPropertiesXml(array $sheetNames): string
    {
        $sheetCount = count($sheetNames);
        $titles = collect($sheetNames)
            ->map(fn (string $name) => '<vt:lpstr>' . htmlspecialchars($name, ENT_XML1) . '</vt:lpstr>')
            ->implode('');

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>Digital Clearance</Application>
  <DocSecurity>0</DocSecurity>
  <ScaleCrop>false</ScaleCrop>
  <HeadingPairs>
    <vt:vector size="2" baseType="variant">
      <vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant>
      <vt:variant><vt:i4>{$sheetCount}</vt:i4></vt:variant>
    </vt:vector>
  </HeadingPairs>
  <TitlesOfParts>
    <vt:vector size="{$sheetCount}" baseType="lpstr">{$titles}</vt:vector>
  </TitlesOfParts>
  <Company></Company>
  <LinksUpToDate>false</LinksUpToDate>
  <SharedDoc>false</SharedDoc>
  <HyperlinksChanged>false</HyperlinksChanged>
  <AppVersion>1.0</AppVersion>
</Properties>
XML;
    }

    protected function corePropertiesXml(): string
    {
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:creator>Digital Clearance</dc:creator>
  <cp:lastModifiedBy>Digital Clearance</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">{$timestamp}</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">{$timestamp}</dcterms:modified>
</cp:coreProperties>
XML;
    }

    protected function stylesXml(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="1">
    <font>
      <sz val="11"/>
      <color theme="1"/>
      <name val="Calibri"/>
      <family val="2"/>
    </font>
  </fonts>
  <fills count="2">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
  </fills>
  <borders count="1">
    <border><left/><right/><top/><bottom/><diagonal/></border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
  </cellXfs>
  <cellStyles count="1">
    <cellStyle name="Normal" xfId="0" builtinId="0"/>
  </cellStyles>
</styleSheet>
XML;
    }

    /**
     * @param  array<int, array<int, array{type:string, value:mixed}>>  $rows
     */
    protected function worksheetXml(array $rows): string
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8', 'yes');
        $xml->startElement('worksheet');
        $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xml->startElement('sheetData');

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 1;

            $xml->startElement('row');
            $xml->writeAttribute('r', (string) $excelRow);

            foreach ($row as $columnIndex => $cell) {
                $cellReference = $this->columnName($columnIndex + 1) . $excelRow;

                if ($cell['type'] === 'number') {
                    $xml->startElement('c');
                    $xml->writeAttribute('r', $cellReference);
                    $xml->startElement('v');
                    $xml->text((string) $cell['value']);
                    $xml->endElement();
                    $xml->endElement();
                    continue;
                }

                $xml->startElement('c');
                $xml->writeAttribute('r', $cellReference);
                $xml->writeAttribute('t', 'inlineStr');
                $xml->startElement('is');
                $xml->startElement('t');
                $xml->text((string) $cell['value']);
                $xml->endElement();
                $xml->endElement();
                $xml->endElement();
            }

            $xml->endElement();
        }

        $xml->endElement();
        $xml->endElement();

        return $xml->outputMemory();
    }

    protected function uniqueSheetName(string $preferred, array $usedNames): string
    {
        $base = trim(preg_replace('/[\\\\\\/?*\\[\\]:]/', '', $preferred) ?? '');
        $base = $base !== '' ? $base : 'Sheet';
        $base = mb_substr($base, 0, 31);

        $candidate = $base;
        $suffix = 1;

        while (in_array($candidate, $usedNames, true)) {
            $suffixLabel = ' ' . $suffix;
            $candidate = mb_substr($base, 0, 31 - mb_strlen($suffixLabel)) . $suffixLabel;
            $suffix++;
        }

        return $candidate;
    }

    protected function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    /**
     * @return array{type:string, value:string}
     */
    protected function stringCell(?string $value): array
    {
        return [
            'type' => 'string',
            'value' => $value ?? '',
        ];
    }

    /**
     * @return array{type:string, value:int}
     */
    protected function numberCell(int $value): array
    {
        return [
            'type' => 'number',
            'value' => $value,
        ];
    }
}
