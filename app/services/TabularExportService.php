<?php
declare(strict_types=1);

class TabularExportService
{
    private const XLSX_MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    private const XLS_MIME = 'application/vnd.ms-excel';
    private const CSV_MIME = 'text/csv; charset=utf-8';

    public function download(string $filenameBase, string $type, array $headers, callable $producer, array $document = []): int
    {
        $safeBase = safe_download_filename($filenameBase, 'relatorio');
        $normalizedType = strtolower(trim($type)) === 'xlsx' ? 'xlsx' : 'csv';

        if ($normalizedType === 'csv') {
            $this->sendHeaders($safeBase . '.csv', self::CSV_MIME);
            return $this->streamCsv($headers, $producer);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'fbexp_');
        if ($tempPath === false) {
            http_response_code(500);
            exit;
        }

        $targetPath = $tempPath . (class_exists('ZipArchive') ? '.xlsx' : '.xls');
        @rename($tempPath, $targetPath);

        try {
            $effective = class_exists('ZipArchive') ? 'xlsx' : 'xlsxml';
            $count = $this->writeWorkbookFile($targetPath, $effective, $headers, $producer, $document);
            $downloadName = $safeBase . ($effective === 'xlsx' ? '.xlsx' : '.xls');
            $mime = $effective === 'xlsx' ? self::XLSX_MIME : self::XLS_MIME;
            $this->sendHeaders($downloadName, $mime);
            readfile($targetPath);
            return $count;
        } finally {
            @unlink($targetPath);
        }
    }

    public function writeWorkbookFromArray(string $targetPath, string $format, array $headers, array $rows, array $document = []): int
    {
        return $this->writeWorkbookFile(
            $targetPath,
            $format,
            $headers,
            static function (callable $writeRow) use ($rows): int {
                $count = 0;
                foreach ($rows as $row) {
                    $writeRow($row);
                    $count++;
                }
                return $count;
            },
            $document
        );
    }

    public function writeWorkbookFile(string $targetPath, string $format, array $headers, callable $producer, array $document = []): int
    {
        $normalizedFormat = strtolower(trim($format));
        if ($normalizedFormat === 'csv') {
            return $this->writeCsvFile($targetPath, $headers, $producer);
        }
        if ($normalizedFormat === 'xlsxml') {
            return $this->writeExcelXmlFile($targetPath, $headers, $producer, $document);
        }
        if ($normalizedFormat === 'xlsx') {
            return $this->writeXlsxFile($targetPath, $headers, $producer, $document);
        }

        throw new InvalidArgumentException('Formato de exportacao invalido: ' . $format);
    }

    private function sendHeaders(string $filename, string $contentType): void
    {
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Accel-Buffering: no');
        header('Cache-Control: no-store, no-cache, must-revalidate');
    }

    private function streamCsv(array $headers, callable $producer): int
    {
        $out = fopen('php://output', 'w');
        if ($out === false) {
            http_response_code(500);
            exit;
        }

        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $this->normalizeRow($headers, count($headers)));
        $count = (int)$producer(function (array $row) use ($out, $headers): void {
            fputcsv($out, $this->normalizeRow($row, count($headers)));
        });
        fclose($out);
        return $count;
    }

    private function writeCsvFile(string $targetPath, array $headers, callable $producer): int
    {
        $out = fopen($targetPath, 'wb');
        if ($out === false) {
            throw new RuntimeException('Nao foi possivel criar arquivo CSV temporario.');
        }

        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $this->normalizeRow($headers, count($headers)));
        $count = (int)$producer(function (array $row) use ($out, $headers): void {
            fputcsv($out, $this->normalizeRow($row, count($headers)));
        });
        fclose($out);
        return $count;
    }

    private function writeXlsxFile(string $targetPath, array $headers, callable $producer, array $document = []): int
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('ZipArchive indisponivel para gerar XLSX.');
        }

        $columnCount = max(1, count($headers));
        $sheetName = $this->sanitizeSheetName((string)($document['sheet_name'] ?? 'Exportacao'));
        $title = $this->cleanCellValue((string)($document['title'] ?? 'Exportacao FBControl'));
        $subtitle = $this->cleanCellValue((string)($document['subtitle'] ?? 'Arquivo gerado pelo sistema.'));
        $metaRows = $this->normalizeMetaRows($document['meta'] ?? [], $columnCount);
        $rowsPart = tempnam(sys_get_temp_dir(), 'fbexp_rows_');
        if ($rowsPart === false) {
            throw new RuntimeException('Nao foi possivel preparar area temporaria do XLSX.');
        }

        $rowsHandle = fopen($rowsPart, 'wb');
        if ($rowsHandle === false) {
            @unlink($rowsPart);
            throw new RuntimeException('Nao foi possivel abrir area temporaria do XLSX.');
        }

        $widths = [];
        $currentRow = 1;
        $mergeCells = [];

        $this->writeXmlRow($rowsHandle, $currentRow++, [$title], [1], $widths);
        $mergeCells[] = 'A1:' . $this->columnLetter($columnCount) . '1';
        $this->writeXmlRow($rowsHandle, $currentRow++, [$subtitle], [2], $widths);
        $mergeCells[] = 'A2:' . $this->columnLetter($columnCount) . '2';
        $currentRow++;

        foreach ($metaRows as $meta) {
            $this->writeXmlRow($rowsHandle, $currentRow, [$meta['label'], $meta['value']], [3, 4], $widths);
            if ($columnCount > 2) {
                $mergeCells[] = 'B' . $currentRow . ':' . $this->columnLetter($columnCount) . $currentRow;
            }
            $currentRow++;
        }

        if (!empty($metaRows)) {
            $currentRow++;
        }

        $headerRowNumber = $currentRow;
        $headerStyles = array_fill(0, $columnCount, 5);
        $this->writeXmlRow($rowsHandle, $currentRow++, $headers, $headerStyles, $widths);

        $bodyStyles = array_fill(0, $columnCount, 6);
        $count = (int)$producer(function (array $row) use ($rowsHandle, $bodyStyles, $columnCount, &$widths, &$currentRow): void {
            $normalized = $this->normalizeRow($row, $columnCount);
            $this->writeXmlRow($rowsHandle, $currentRow++, $normalized, $bodyStyles, $widths);
        });

        fclose($rowsHandle);

        $zip = new ZipArchive();
        if ($zip->open($targetPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($rowsPart);
            throw new RuntimeException('Nao foi possivel abrir arquivo XLSX.');
        }

        $lastDataRow = max($headerRowNumber, $currentRow - 1);
        $sheetXml = $this->buildWorksheetXml($columnCount, $widths, $headerRowNumber, $lastDataRow, $mergeCells, $rowsPart);

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('docProps/app.xml', $this->appPropsXml());
        $zip->addFromString('docProps/core.xml', $this->corePropsXml($title));
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        @unlink($rowsPart);
        return $count;
    }

    private function writeExcelXmlFile(string $targetPath, array $headers, callable $producer, array $document = []): int
    {
        $columnCount = max(1, count($headers));
        $title = $this->cleanCellValue((string)($document['title'] ?? 'Exportacao FBControl'));
        $subtitle = $this->cleanCellValue((string)($document['subtitle'] ?? 'Arquivo gerado pelo sistema.'));
        $metaRows = $this->normalizeMetaRows($document['meta'] ?? [], $columnCount);
        $sheetName = $this->sanitizeSheetName((string)($document['sheet_name'] ?? 'Exportacao'));

        $widths = [];
        $currentRow = 0;
        $headerRowNumber = 0;
        $rowsXml = '';

        $rowsXml .= $this->excelXmlRow(++$currentRow, [['value' => $title, 'style' => 'Title']], $widths);
        $rowsXml .= $this->excelXmlRow(++$currentRow, [['value' => $subtitle, 'style' => 'Subtitle']], $widths);
        $currentRow++;

        foreach ($metaRows as $meta) {
            $rowsXml .= $this->excelXmlRow(++$currentRow, [
                ['value' => $meta['label'], 'style' => 'MetaLabel'],
                ['value' => $meta['value'], 'style' => 'MetaValue'],
            ], $widths);
        }

        if (!empty($metaRows)) {
            $currentRow++;
        }

        $headerRowNumber = ++$currentRow;
        $headerCells = [];
        foreach ($headers as $header) {
            $headerCells[] = ['value' => $header, 'style' => 'Header'];
        }
        $rowsXml .= $this->excelXmlRow($headerRowNumber, $headerCells, $widths);

        $count = (int)$producer(function (array $row) use (&$rowsXml, &$currentRow, &$widths, $columnCount): void {
            $normalized = $this->normalizeRow($row, $columnCount);
            $cells = [];
            foreach ($normalized as $value) {
                $cells[] = ['value' => $value, 'style' => 'Body'];
            }
            $rowsXml .= $this->excelXmlRow(++$currentRow, $cells, $widths);
        });

        $columnsXml = '';
        for ($i = 1; $i <= $columnCount; $i++) {
            $width = $widths[$i] ?? 18;
            $columnsXml .= '<Column ss:AutoFitWidth="0" ss:Width="' . (string)($width * 6.2) . '"/>';
        }

        $xml = '<?xml version="1.0"?>'
            . '<?mso-application progid="Excel.Sheet"?>'
            . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'
            . ' xmlns:o="urn:schemas-microsoft-com:office:office"'
            . ' xmlns:x="urn:schemas-microsoft-com:office:excel"'
            . ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"'
            . ' xmlns:html="http://www.w3.org/TR/REC-html40">'
            . $this->excelXmlStyles()
            . '<Worksheet ss:Name="' . $this->xmlAttr($sheetName) . '">'
            . '<Table>' . $columnsXml . $rowsXml . '</Table>'
            . '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">'
            . '<FreezePanes/><FrozenNoSplit/><SplitHorizontal>' . (string)$headerRowNumber . '</SplitHorizontal><TopRowBottomPane>' . (string)$headerRowNumber . '</TopRowBottomPane>'
            . '<Panes><Pane><Number>3</Number></Pane></Panes>'
            . '<PageSetup><Layout x:Orientation="Landscape"/></PageSetup>'
            . '</WorksheetOptions>'
            . '</Worksheet>'
            . '</Workbook>';

        file_put_contents($targetPath, $xml);
        return $count;
    }

    private function buildWorksheetXml(int $columnCount, array $widths, int $headerRowNumber, int $lastDataRow, array $mergeCells, string $rowsPartPath): string
    {
        $colsXml = '';
        for ($i = 1; $i <= $columnCount; $i++) {
            $width = $widths[$i] ?? 18;
            $colsXml .= '<col min="' . $i . '" max="' . $i . '" width="' . $width . '" customWidth="1"/>';
        }

        $mergeXml = '';
        if (!empty($mergeCells)) {
            $mergeXml .= '<mergeCells count="' . count($mergeCells) . '">';
            foreach ($mergeCells as $ref) {
                $mergeXml .= '<mergeCell ref="' . $ref . '"/>';
            }
            $mergeXml .= '</mergeCells>';
        }

        $sheet = fopen('php://temp', 'w+b');
        fwrite($sheet, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>');
        fwrite($sheet, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">');
        fwrite($sheet, '<sheetViews><sheetView workbookViewId="0"><pane ySplit="' . $headerRowNumber . '" topLeftCell="A' . ($headerRowNumber + 1) . '" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>');
        fwrite($sheet, '<sheetFormatPr defaultRowHeight="18"/>');
        fwrite($sheet, '<cols>' . $colsXml . '</cols>');
        fwrite($sheet, '<sheetData>');

        $rowsHandle = fopen($rowsPartPath, 'rb');
        if ($rowsHandle !== false) {
            stream_copy_to_stream($rowsHandle, $sheet);
            fclose($rowsHandle);
        }

        fwrite($sheet, '</sheetData>');
        if ($lastDataRow >= $headerRowNumber) {
            fwrite($sheet, '<autoFilter ref="A' . $headerRowNumber . ':' . $this->columnLetter($columnCount) . $lastDataRow . '"/>');
        }
        fwrite($sheet, $mergeXml);
        fwrite($sheet, '<pageMargins left="0.35" right="0.35" top="0.55" bottom="0.55" header="0.2" footer="0.2"/>');
        fwrite($sheet, '<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/>');
        fwrite($sheet, '</worksheet>');
        rewind($sheet);
        $xml = stream_get_contents($sheet);
        fclose($sheet);
        return (string)$xml;
    }

    private function writeXmlRow($handle, int $rowNumber, array $cells, array $styles, array &$widths): void
    {
        fwrite($handle, '<row r="' . $rowNumber . '">');
        foreach ($cells as $index => $value) {
            $column = $index + 1;
            $style = $styles[$index] ?? 6;
            $cellRef = $this->columnLetter($column) . $rowNumber;
            $clean = $this->cleanCellValue($value);
            $this->updateColumnWidth($widths, $column, $clean);
            fwrite($handle, $this->xlsxCellXml($cellRef, $clean, $style));
        }
        fwrite($handle, '</row>');
    }

    private function xlsxCellXml(string $ref, $value, int $style): string
    {
        if (is_int($value) || is_float($value)) {
            return '<c r="' . $ref . '" s="' . $style . '"><v>' . $value . '</v></c>';
        }

        $normalized = $this->cleanCellValue($value);
        if ($normalized !== '' && preg_match('/^-?(?:\d+|\d+\.\d+)$/', $normalized) === 1 && !$this->looksLikeTextNumber($normalized)) {
            return '<c r="' . $ref . '" s="' . $style . '"><v>' . $normalized . '</v></c>';
        }

        return '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . $this->xmlText($normalized) . '</t></is></c>';
    }

    private function looksLikeTextNumber(string $value): bool
    {
        return preg_match('/^0\d+$/', $value) === 1
            || preg_match('/^\d{2}:\d{2}$/', $value) === 1
            || preg_match('/^\d{4}-\d{2}-\d{2}/', $value) === 1;
    }

    private function normalizeMetaRows(array $meta, int $columnCount): array
    {
        $rows = [];
        foreach ($meta as $label => $value) {
            $labelText = $this->cleanCellValue((string)$label);
            $valueText = $this->cleanCellValue(is_array($value) ? implode(', ', $value) : (string)$value);
            if ($labelText === '' || $valueText === '') {
                continue;
            }
            $rows[] = [
                'label' => $labelText,
                'value' => $valueText,
            ];
        }

        $rows[] = [
            'label' => 'Gerado em',
            'value' => date('d/m/Y H:i'),
        ];

        return $rows;
    }

    private function normalizeRow(array $row, int $columnCount): array
    {
        $normalized = array_values($row);
        $normalized = array_slice($normalized, 0, $columnCount);
        while (count($normalized) < $columnCount) {
            $normalized[] = '';
        }
        return array_map([$this, 'cleanCellValue'], $normalized);
    }

    private function updateColumnWidth(array &$widths, int $column, $value): void
    {
        $length = max(6, min(42, mb_strlen((string)$value, 'UTF-8') + 2));
        if (!isset($widths[$column]) || $length > $widths[$column]) {
            $widths[$column] = $length;
        }
    }

    private function columnLetter(int $index): string
    {
        $letters = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $index = (int)(($index - $mod - 1) / 26);
        }
        return $letters;
    }

    private function cleanCellValue($value): string
    {
        $text = normalize_mojibake((string)$value);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';
        return trim($text);
    }

    private function sanitizeSheetName(string $name): string
    {
        $clean = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', normalize_mojibake($name)) ?? 'Exportacao';
        $clean = trim($clean);
        if ($clean === '') {
            $clean = 'Exportacao';
        }
        return mb_substr($clean, 0, 31, 'UTF-8');
    }

    private function xmlText(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function xmlAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(string $sheetName): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->xmlAttr($sheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function appPropsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>FBControl</Application>'
            . '</Properties>';
    }

    private function corePropsXml(string $title): string
    {
        $created = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>' . $this->xmlText($title) . '</dc:title>'
            . '<dc:creator>FBControl</dc:creator>'
            . '<cp:lastModifiedBy>FBControl</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $created . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $created . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="5">'
            . '<font><sz val="11"/><color rgb="FF24313F"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><sz val="10"/><color rgb="FF5F6D7A"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FF24313F"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
            . '</fonts>'
            . '<fills count="6">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF15384C"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF3F6F8"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF06A2A"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFDF2EA"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="3">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFE1E7EC"/></left><right style="thin"><color rgb="FFE1E7EC"/></right><top style="thin"><color rgb="FFE1E7EC"/></top><bottom style="thin"><color rgb="FFE1E7EC"/></bottom><diagonal/></border>'
            . '<border><bottom style="medium"><color rgb="FFF06A2A"/></bottom></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="7">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="4" fillId="4" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function excelXmlStyles(): string
    {
        return '<Styles>'
            . '<Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="11" ss:Color="#24313F"/></Style>'
            . '<Style ss:ID="Title"><Font ss:FontName="Calibri" ss:Size="16" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#15384C" ss:Pattern="Solid"/></Style>'
            . '<Style ss:ID="Subtitle"><Font ss:FontName="Calibri" ss:Size="10" ss:Color="#5F6D7A"/><Interior ss:Color="#F3F6F8" ss:Pattern="Solid"/></Style>'
            . '<Style ss:ID="MetaLabel"><Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#24313F"/><Interior ss:Color="#FDF2EA" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E1E7EC"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E1E7EC"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E1E7EC"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E1E7EC"/></Borders></Style>'
            . '<Style ss:ID="MetaValue"><Font ss:FontName="Calibri" ss:Size="11" ss:Color="#24313F"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E1E7EC"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E1E7EC"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E1E7EC"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E1E7EC"/></Borders></Style>'
            . '<Style ss:ID="Header"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#F06A2A" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#F06A2A"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#F06A2A"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#F06A2A"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#F06A2A"/></Borders></Style>'
            . '<Style ss:ID="Body"><Alignment ss:Vertical="Top" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E1E7EC"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E1E7EC"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E1E7EC"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E1E7EC"/></Borders></Style>'
            . '</Styles>';
    }

    private function excelXmlRow(int $rowNumber, array $cells, array &$widths): string
    {
        $xml = '<Row ss:Index="' . $rowNumber . '">';
        foreach ($cells as $index => $cell) {
            $value = $this->cleanCellValue($cell['value'] ?? '');
            $style = (string)($cell['style'] ?? 'Body');
            $column = $index + 1;
            $this->updateColumnWidth($widths, $column, $value);
            $xml .= '<Cell ss:StyleID="' . $this->xmlAttr($style) . '"><Data ss:Type="' . ($this->isNumericCell($value) ? 'Number' : 'String') . '">'
                . $this->xmlText($value)
                . '</Data></Cell>';
        }
        $xml .= '</Row>';
        return $xml;
    }

    private function isNumericCell(string $value): bool
    {
        return $value !== ''
            && preg_match('/^-?(?:\d+|\d+\.\d+)$/', $value) === 1
            && !$this->looksLikeTextNumber($value);
    }
}
