<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap_cli.php';

$assert = static function (string $label, bool $ok, string $detail = ''): void {
    echo ($ok ? '[OK] ' : '[FAIL] ') . $label;
    if ($detail !== '') {
        echo ' - ' . $detail;
    }
    echo PHP_EOL;
    if (!$ok) {
        exit(1);
    }
};

$service = new TabularExportService();
$headers = ['data', 'restaurante', 'uh', 'pax', 'status'];
$rows = [
    ['2026-06-16', 'Restaurante IX\'u', '0401', 6, 'Reservada'],
    ['2026-06-16', 'Restaurante Giardino', '0412', 4, 'Finalizada'],
];
$document = [
    'title' => 'Reservas tematicas',
    'subtitle' => 'Teste automatico de exportacao',
    'sheet_name' => 'Tematicos',
    'meta' => [
        'Data' => '16/06/2026',
        'Restaurante' => 'IX\'u',
    ],
];

$xlsxPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fbcontrol_export_test.xlsx';
@unlink($xlsxPath);
$service->writeWorkbookFromArray($xlsxPath, 'xlsx', $headers, $rows, $document);
$assert('xlsx_created', is_file($xlsxPath) && filesize($xlsxPath) > 0, basename($xlsxPath));

$zip = new ZipArchive();
$openCode = $zip->open($xlsxPath);
$assert('xlsx_zip_open', $openCode === true, 'code=' . (string)$openCode);
$sheetXml = (string)$zip->getFromName('xl/worksheets/sheet1.xml');
$stylesXml = (string)$zip->getFromName('xl/styles.xml');
$zip->close();
$assert('xlsx_has_sheet', $sheetXml !== '', 'sheet1.xml');
$assert('xlsx_has_styles', $stylesXml !== '', 'styles.xml');
$assert('xlsx_contains_title', strpos($sheetXml, 'Reservas tematicas') !== false, 'title');
$assert('xlsx_contains_row_value', strpos($sheetXml, 'Restaurante IX') !== false && strpos($sheetXml, '0401') !== false, 'sample row');
@unlink($xlsxPath);

$xmlPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fbcontrol_export_test.xls';
@unlink($xmlPath);
$service->writeWorkbookFromArray($xmlPath, 'xlsxml', $headers, $rows, $document);
$xmlContent = is_file($xmlPath) ? (string)file_get_contents($xmlPath) : '';
$assert('xlsxml_created', $xmlContent !== '', basename($xmlPath));
$assert('xlsxml_contains_workbook', strpos($xmlContent, '<Workbook') !== false, 'SpreadsheetML');
$assert('xlsxml_contains_title', strpos($xmlContent, 'Reservas tematicas') !== false, 'title');
@unlink($xmlPath);

$csvPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fbcontrol_export_test.csv';
@unlink($csvPath);
$service->writeWorkbookFromArray($csvPath, 'csv', $headers, $rows, $document);
$csvContent = is_file($csvPath) ? (string)file_get_contents($csvPath) : '';
$assert('csv_created', $csvContent !== '', basename($csvPath));
$assert('csv_contains_header', strpos($csvContent, "data,restaurante,uh,pax,status") !== false, 'header');
@unlink($csvPath);

echo PHP_EOL . 'Resultado: OK' . PHP_EOL;
