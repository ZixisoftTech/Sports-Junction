<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\PriceUnit;
use App\Models\MoqUnit;
use App\Models\Supplier;
use App\Models\Product;
use App\Helpers\AuditLogger;
use ZipArchive;

class BulkProductImporter
{
    public static function getTempDir(): string
    {
        $candidateDirs = [
            dirname(__DIR__, 2) . '/storage/tmp_uploads',
            dirname(__DIR__, 2) . '/storage/tmp',
            sys_get_temp_dir() . '/sj_bulk_uploads',
            sys_get_temp_dir(),
        ];

        foreach ($candidateDirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
                @chmod($dir, 0777);
            }
            if (is_dir($dir) && is_writable($dir)) {
                return $dir;
            }
        }

        return dirname(__DIR__, 2) . '/storage/tmp';
    }

    public static function ensureTempDir(): void
    {
        self::getTempDir();
    }

    /**
     * Download or stream the bulk template
     */
    public static function downloadTemplate(string $format = 'xlsx', bool $isAdmin = false): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        $headers = [
            'Product Name *',
            'Categories * (comma-separated names or IDs)',
            'Subcategories (comma-separated names or IDs)',
            'SKU',
            'Brand Name',
            'Min Price ($) *',
            'Max Price ($) *',
            'Price Unit (e.g. Piece, Set, Box)',
            'MOQ *',
            'MOQ Unit (e.g. Pieces, Sets, Boxes)',
            'Available Quantity',
            'Short Description',
            'Detailed Description',
            'Material',
            'Colors (comma-separated)',
            'Sizes (comma-separated)',
            'Weight',
            'Customization Details',
            'Custom Logo (Yes/No)',
            'Custom Color (Yes/No)',
            'Custom Packaging (Yes/No)',
            'OEM Available (Yes/No)',
            'Lead Time',
            'Export Packaging',
            'Sample Available (Yes/No)',
            'Sample Price ($)',
            'Primary Image (URL or image filename)',
            'Gallery Images (comma-separated URLs)',
        ];

        if ($isAdmin) {
            $headers[] = 'Supplier ID or Company Name';
        }

        $allCats = Category::allActive();
        $sampleCat1 = !empty($allCats[0]['name']) ? $allCats[0]['name'] : 'Football & Inflatables';
        $sampleCat2 = !empty($allCats[1]['name']) ? $allCats[1]['name'] : 'Sports & Fitness';

        $allSubs = Subcategory::allActive();
        $sampleSub1 = !empty($allSubs[0]['name']) ? $allSubs[0]['name'] : '';
        $sampleSub2 = !empty($allSubs[1]['name']) ? $allSubs[1]['name'] : '';

        $sampleRow = [
            'Professional Match PU Football (Size 5)',
            $sampleCat1,
            $sampleSub1,
            'SJ-FB-PRO-01',
            'Apex Pro Sports',
            '12.50',
            '18.00',
            'Piece',
            '100',
            'Pieces',
            '5000',
            'FIFA standard hand-stitched thermal bonded match football with textured outer grip',
            'High-grade PU synthetic leather outer shell with high-rebound butyl bladder, multi-ply polyester lamination, suitable for international match standards.',
            'Synthetic PU Leather',
            'White/Blue, White/Red, Neon Yellow',
            'Size 5, Size 4',
            '420g - 440g',
            'Custom logo heat-transfer, color pantone customization, barcode packaging',
            'Yes',
            'Yes',
            'Yes',
            'Yes',
            '15 - 25 Days',
            '50 units per reinforced corrugated master carton with polybag wrap (Carton: 60x40x40cm, GW: 22kg)',
            'Yes',
            '25.00',
            'https://images.unsplash.com/photo-1614632537423-1e6c2e7e0aab?w=800',
            'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?w=800,https://images.unsplash.com/photo-1551958219-acbc608c6377?w=800',
        ];

        if ($isAdmin) {
            $sampleRow[] = '1';
        }

        $sampleRow2 = [
            'Commercial Heavy-Duty Hex Dumbbell Set',
            $sampleCat2,
            $sampleSub2,
            'SJ-DB-HEX-50',
            'IronPeak Fitness',
            '45.00',
            '65.00',
            'Set',
            '20',
            'Sets',
            '1200',
            'Premium cast-iron solid hex dumbbells with anti-roll virgin rubber coating',
            'Knurled chrome steel ergonomic handles with virgin rubber coated cast iron heads to protect gym floors and reduce impact noise.',
            'Cast Iron & Virgin Rubber',
            'Black/Chrome',
            '2.5kg to 50kg pairs',
            'Pairs ranging 5kg to 100kg total',
            'Laser engraved logo on rubber heads',
            'Yes',
            'No',
            'Yes',
            'Yes',
            '20 - 30 Days',
            'Individually boxed in heavy-duty cardboard with EPE foam cushioning, palletized for sea freight',
            'Yes',
            '75.00',
            'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?w=800',
            'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?w=800',
        ];
        if ($isAdmin) {
            $sampleRow2[] = '1';
        }

        $filename = 'Sports_Junction_Bulk_Product_Upload_Template';

        if (strtolower($format) === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, $headers);
            fputcsv($out, $sampleRow);
            fputcsv($out, $sampleRow2);
            fclose($out);
            exit;
        }

        // Generate native XLSX with safe temp path resolution
        $tempFile = self::getSafeTempFile('sj_xlsx_', 'xlsx');
        $success = self::createXlsxFile($tempFile, $headers, [$sampleRow, $sampleRow2]);

        if (!$success || !file_exists($tempFile) || filesize($tempFile) === 0) {
            // Safe fallback to CSV if ZipArchive fails
            @unlink($tempFile);
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, $headers);
            fputcsv($out, $sampleRow);
            fputcsv($out, $sampleRow2);
            fclose($out);
            exit;
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Content-Length: ' . filesize($tempFile));
        header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
        header('Pragma: public');
        readfile($tempFile);
        @unlink($tempFile);
        exit;
    }

    /**
     * Download reference data (Categories, Subcategories, Countries, Price Units, MOQ Units, and Suppliers)
     */
    public static function downloadReferenceData(string $format = 'xlsx', bool $isAdmin = false): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        $pdo = \App\Core\Database::getConnection();

        // 1. Categories
        $catsStmt = $pdo->query("SELECT id, name, slug FROM categories WHERE status = 'ACTIVE' ORDER BY name ASC");
        $categories = $catsStmt->fetchAll(\PDO::FETCH_ASSOC);

        // 2. Subcategories with parent category
        $subsStmt = $pdo->query("
            SELECT s.id, s.name, s.slug, s.category_id, c.name AS parent_category_name
            FROM subcategories s
            LEFT JOIN categories c ON c.id = s.category_id
            WHERE s.status = 'ACTIVE'
            ORDER BY c.name ASC, s.name ASC
        ");
        $subcategories = $subsStmt->fetchAll(\PDO::FETCH_ASSOC);

        // 3. Countries
        $countriesStmt = $pdo->query("SELECT id, iso_alpha2 AS code, name FROM countries WHERE status = 'ACTIVE' ORDER BY name ASC");
        $countries = $countriesStmt->fetchAll(\PDO::FETCH_ASSOC);

        // 4. Price Units
        $priceUnitsStmt = $pdo->query("SELECT id, name, symbol FROM price_units WHERE status = 'ACTIVE' ORDER BY name ASC");
        $priceUnits = $priceUnitsStmt->fetchAll(\PDO::FETCH_ASSOC);

        // 5. MOQ Units
        $moqUnitsStmt = $pdo->query("SELECT id, name, symbol FROM moq_units WHERE status = 'ACTIVE' ORDER BY name ASC");
        $moqUnits = $moqUnitsStmt->fetchAll(\PDO::FETCH_ASSOC);

        // 6. Suppliers (Admin only)
        $suppliers = [];
        if ($isAdmin) {
            $supStmt = $pdo->query("SELECT id, company_name, email FROM suppliers WHERE status = 'ACTIVE' ORDER BY company_name ASC");
            $suppliers = $supStmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        $sheets = [
            [
                'name'    => 'Categories',
                'headers' => ['Category ID', 'Category Name', 'Category Slug'],
                'rows'    => array_map(fn($c) => [$c['id'], $c['name'], $c['slug']], $categories),
            ],
            [
                'name'    => 'Subcategories',
                'headers' => ['Subcategory ID', 'Subcategory Name', 'Subcategory Slug', 'Parent Category ID', 'Parent Category Name'],
                'rows'    => array_map(fn($s) => [$s['id'], $s['name'], $s['slug'], $s['category_id'], $s['parent_category_name'] ?? ''], $subcategories),
            ],
            [
                'name'    => 'Countries',
                'headers' => ['Country ID', 'Country Code', 'Country Name'],
                'rows'    => array_map(fn($co) => [$co['id'], $co['code'], $co['name']], $countries),
            ],
            [
                'name'    => 'Price Units',
                'headers' => ['Unit ID', 'Unit Name', 'Symbol'],
                'rows'    => array_map(fn($pu) => [$pu['id'], $pu['name'], $pu['symbol'] ?? ''], $priceUnits),
            ],
            [
                'name'    => 'MOQ Units',
                'headers' => ['Unit ID', 'Unit Name', 'Symbol'],
                'rows'    => array_map(fn($mu) => [$mu['id'], $mu['name'], $mu['symbol'] ?? ''], $moqUnits),
            ],
        ];

        if ($isAdmin) {
            $sheets[] = [
                'name'    => 'Suppliers',
                'headers' => ['Supplier ID', 'Company Name', 'Contact Email'],
                'rows'    => array_map(fn($sp) => [$sp['id'], $sp['company_name'], $sp['email']], $suppliers),
            ];
        }

        $filename = 'Sports_Junction_Import_Reference_Data';

        if (strtolower($format) === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            foreach ($sheets as $s) {
                fputcsv($out, ['=== ' . strtoupper($s['name']) . ' ===']);
                fputcsv($out, $s['headers']);
                foreach ($s['rows'] as $r) {
                    fputcsv($out, $r);
                }
                fputcsv($out, []);
            }
            fclose($out);
            exit;
        }

        // Generate Multi-sheet XLSX
        $tempFile = self::getSafeTempFile('sj_ref_', 'xlsx');
        $success = self::createMultiSheetXlsxFile($tempFile, $sheets);

        if (!$success || !file_exists($tempFile) || filesize($tempFile) === 0) {
            @unlink($tempFile);
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            foreach ($sheets as $s) {
                fputcsv($out, ['=== ' . strtoupper($s['name']) . ' ===']);
                fputcsv($out, $s['headers']);
                foreach ($s['rows'] as $r) {
                    fputcsv($out, $r);
                }
                fputcsv($out, []);
            }
            fclose($out);
            exit;
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Content-Length: ' . filesize($tempFile));
        header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
        header('Pragma: public');
        readfile($tempFile);
        @unlink($tempFile);
        exit;
    }

    /**
     * Create Multi-Sheet OpenXML XLSX file
     */
    public static function createMultiSheetXlsxFile(string $targetPath, array $sheets): bool
    {
        $zip = new \ZipArchive();
        if ($zip->open($targetPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $numSheets = count($sheets);

        // [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' . "\n" .
            '    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' . "\n" .
            '    <Default Extension="xml" ContentType="application/xml"/>' . "\n" .
            '    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' . "\n";
        for ($i = 1; $i <= $numSheets; $i++) {
            $contentTypes .= '    <Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' . "\n";
        }
        $contentTypes .= '    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . "\n" .
            '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // _rels/.rels
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n" .
            '    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' . "\n" .
            '</Relationships>';
        $zip->addFromString('_rels/.rels', $rootRels);

        // xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n";
        for ($i = 1; $i <= $numSheets; $i++) {
            $wbRels .= '    <Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>' . "\n";
        }
        $wbRels .= '    <Relationship Id="rId' . ($numSheets + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' . "\n" .
            '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // xl/workbook.xml
        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n" .
            '    <sheets>' . "\n";
        foreach ($sheets as $idx => $s) {
            $sheetNum = $idx + 1;
            $safeSheetName = htmlspecialchars(substr((string)$s['name'], 0, 31), ENT_XML1, 'UTF-8');
            $workbookXml .= '        <sheet name="' . $safeSheetName . '" sheetId="' . $sheetNum . '" r:id="rId' . $sheetNum . '"/>' . "\n";
        }
        $workbookXml .= '    </sheets>' . "\n" .
            '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbookXml);

        // xl/styles.xml
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="2">
        <font><name val="Calibri"/><sz val="11"/><color rgb="FF000000"/></font>
        <font><b/><name val="Calibri"/><sz val="11"/><color rgb="FFFFFFFF"/></font>
    </fonts>
    <fills count="3">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF00288E"/></patternFill></fill>
    </fills>
    <borders count="1">
        <border><left/><right/><top/><bottom/></border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="2">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
    </cellXfs>
</styleSheet>';
        $zip->addFromString('xl/styles.xml', $stylesXml);

        // xl/worksheets/sheet{i}.xml
        foreach ($sheets as $idx => $s) {
            $sheetNum = $idx + 1;
            $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
                '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n" .
                '    <sheetData>' . "\n";

            // Header row
            $sheetXml .= '        <row r="1" ht="26" customHeight="1">' . "\n";
            foreach ($s['headers'] as $colIdx => $hVal) {
                $colLetter = self::indexToColumnLetter($colIdx);
                $escaped = htmlspecialchars((string)$hVal, ENT_XML1, 'UTF-8');
                $sheetXml .= '            <c r="' . $colLetter . '1" s="1" t="inlineStr"><is><t>' . $escaped . '</t></is></c>' . "\n";
            }
            $sheetXml .= '        </row>' . "\n";

            // Data rows
            $rowNum = 2;
            foreach ($s['rows'] as $r) {
                $sheetXml .= '        <row r="' . $rowNum . '" ht="20" customHeight="1">' . "\n";
                foreach ($r as $colIdx => $cellVal) {
                    $colLetter = self::indexToColumnLetter($colIdx);
                    $escaped = htmlspecialchars((string)$cellVal, ENT_XML1, 'UTF-8');
                    $sheetXml .= '            <c r="' . $colLetter . $rowNum . '" t="inlineStr"><is><t>' . $escaped . '</t></is></c>' . "\n";
                }
                $sheetXml .= '        </row>' . "\n";
                $rowNum++;
            }

            $sheetXml .= '    </sheetData>' . "\n" .
                '</worksheet>';
            $zip->addFromString('xl/worksheets/sheet' . $sheetNum . '.xml', $sheetXml);
        }

        $zip->close();
        return true;
    }

    /**
     * Safely resolve and create a temporary file path guaranteed to be a writable string
     */
    public static function getSafeTempFile(string $prefix = 'sj_tmp_', string $ext = 'tmp'): string
    {
        $ext = ltrim($ext, '.');
        $candidateDirs = [
            dirname(__DIR__, 2) . '/storage/tmp',
            dirname(__DIR__, 2) . '/storage/cache',
            sys_get_temp_dir(),
            '/tmp',
        ];

        foreach ($candidateDirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
                @chmod($dir, 0777);
            }
            if (is_dir($dir) && is_writable($dir)) {
                $temp = @tempnam($dir, $prefix);
                if ($temp !== false && is_string($temp) && !empty($temp)) {
                    if ($ext !== 'tmp') {
                        $targetWithExt = $temp . '.' . $ext;
                        @rename($temp, $targetWithExt);
                        return $targetWithExt;
                    }
                    return $temp;
                }
                $uniquePath = rtrim($dir, '/') . '/' . $prefix . bin2hex(random_bytes(8)) . '.' . $ext;
                $handle = @fopen($uniquePath, 'wb');
                if ($handle !== false) {
                    fclose($handle);
                    @chmod($uniquePath, 0666);
                    return $uniquePath;
                }
            }
        }

        // Project root fallback if storage is unavailable
        $rootFallback = dirname(__DIR__, 2) . '/' . $prefix . bin2hex(random_bytes(8)) . '.' . $ext;
        $handle = @fopen($rootFallback, 'wb');
        if ($handle !== false) {
            fclose($handle);
        }
        return $rootFallback;
    }

    /**
     * Create OpenXML XLSX binary file using ZipArchive
     */
    public static function createXlsxFile(string $targetPath, array $headers, array $rows): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($targetPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        // [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // _rels/.rels
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
        $zip->addFromString('_rels/.rels', $rootRels);

        // xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // xl/workbook.xml
        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Products" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbookXml);

        // xl/styles.xml
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="2">
        <font><name val="Calibri"/><sz val="11"/><color rgb="FF000000"/></font>
        <font><b/><name val="Calibri"/><sz val="11"/><color rgb="FFFFFFFF"/></font>
    </fonts>
    <fills count="3">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF00288E"/></patternFill></fill>
    </fills>
    <borders count="1">
        <border><left/><right/><top/><bottom/></border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="2">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
    </cellXfs>
</styleSheet>';
        $zip->addFromString('xl/styles.xml', $stylesXml);

        // xl/worksheets/sheet1.xml
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <sheetData>';

        // Header Row (style 1)
        $sheetXml .= '<row r="1" ht="28" customHeight="1">';
        foreach ($headers as $colIdx => $headerVal) {
            $colLetter = self::indexToColumnLetter($colIdx);
            $cellRef = $colLetter . '1';
            $escaped = htmlspecialchars((string)$headerVal, ENT_XML1, 'UTF-8');
            $sheetXml .= '<c r="' . $cellRef . '" s="1" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
        }
        $sheetXml .= '</row>';

        // Data Rows
        $rowNum = 2;
        foreach ($rows as $row) {
            $sheetXml .= '<row r="' . $rowNum . '" ht="22" customHeight="1">';
            foreach ($row as $colIdx => $cellVal) {
                $colLetter = self::indexToColumnLetter($colIdx);
                $cellRef = $colLetter . $rowNum;
                $escaped = htmlspecialchars((string)$cellVal, ENT_XML1, 'UTF-8');
                $sheetXml .= '<c r="' . $cellRef . '" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
            }
            $sheetXml .= '</row>';
            $rowNum++;
        }

        $sheetXml .= '    </sheetData>
</worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        $zip->close();
        return true;
    }

    private static function indexToColumnLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr(($index % 26) + 65) . $letter;
            $index = intdiv($index, 26) - 1;
        }
        return $letter;
    }

    /**
     * Parse and validate an uploaded spreadsheet (CSV or XLSX)
     */
    public static function parseAndValidate(string $filePath, ?int $supplierId = null, bool $isAdmin = false, ?int $adminSelectedSupplierId = null): array
    {
        self::ensureTempDir();
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $rawRows = [];
        if ($ext === 'csv' || $ext === 'txt') {
            $rawRows = self::parseCsv($filePath);
        } elseif ($ext === 'xlsx') {
            $rawRows = self::parseXlsx($filePath);
        } else {
            // Try CSV fallback
            $rawRows = self::parseCsv($filePath);
            if (empty($rawRows)) {
                $rawRows = self::parseXlsx($filePath);
            }
        }

        if (empty($rawRows) || count($rawRows) < 2) {
            return [
                'success' => false,
                'message' => 'The uploaded file is empty or missing headers and data rows.',
                'total_rows' => 0,
                'valid_rows_count' => 0,
                'error_rows_count' => 0,
                'errors' => [],
                'preview_rows' => [],
            ];
        }

        $headerRow = array_shift($rawRows);
        $normalizedHeaders = self::normalizeHeaders($headerRow);

        // Preload master caches for fast lookup
        $allCategories = Category::allActive();
        $allSubcategories = Subcategory::all();
        $allPriceUnits = PriceUnit::allActive();
        $allMoqUnits = MoqUnit::allActive();
        $allSuppliers = Supplier::all();

        // Build category lookup maps (by ID and by slug/name lowercased)
        $catById = [];
        $catByName = [];
        foreach ($allCategories as $cat) {
            $catById[(int)$cat['id']] = $cat;
            $catByName[strtolower(trim($cat['name']))] = $cat;
            $catByName[strtolower(trim($cat['slug']))] = $cat;
        }

        // Build subcategory lookup maps
        $subById = [];
        $subByName = [];
        foreach ($allSubcategories as $sub) {
            $subById[(int)$sub['id']] = $sub;
            $subByName[strtolower(trim($sub['name']))] = $sub;
            $subByName[strtolower(trim($sub['slug']))] = $sub;
        }

        // Build unit maps
        $priceUnitByName = [];
        foreach ($allPriceUnits as $pu) {
            $priceUnitByName[strtolower(trim($pu['name']))] = (int)$pu['id'];
            if (!empty($pu['symbol'])) {
                $priceUnitByName[strtolower(trim($pu['symbol']))] = (int)$pu['id'];
            }
        }

        $moqUnitByName = [];
        foreach ($allMoqUnits as $mu) {
            $moqUnitByName[strtolower(trim($mu['name']))] = (int)$mu['id'];
            if (!empty($mu['symbol'])) {
                $moqUnitByName[strtolower(trim($mu['symbol']))] = (int)$mu['id'];
            }
        }

        $supplierMap = [];
        foreach ($allSuppliers as $sup) {
            $supplierMap[(int)$sup['id']] = $sup;
            $supplierMap[strtolower(trim($sup['company_name']))] = $sup;
            $supplierMap[strtolower(trim($sup['email']))] = $sup;
        }

        $validRecords = [];
        $errors = [];
        $seenSkusInFile = [];

        foreach ($rawRows as $idx => $row) {
            $rowNum = $idx + 2; // Row in Excel (Row 1 is headers)
            $rowErrors = [];

            // Skip entirely blank rows
            $hasData = false;
            foreach ($row as $val) {
                if (trim((string)$val) !== '') {
                    $hasData = true;
                    break;
                }
            }
            if (!$hasData) {
                continue;
            }

            $mapped = [];
            foreach ($normalizedHeaders as $colIdx => $fieldKey) {
                $mapped[$fieldKey] = trim((string)($row[$colIdx] ?? ''));
            }

            // 1. Product Name (Required)
            $name = $mapped['name'] ?? $mapped['title'] ?? $mapped['product_name'] ?? '';
            if (empty($name)) {
                $rowErrors[] = 'Product Name is required.';
            }

            // 2. Categories (Required, supports multiple comma-separated)
            $catRaw = $mapped['categories'] ?? $mapped['category'] ?? '';
            $catItems = array_filter(array_map('trim', preg_split('/[,;|]/', (string)$catRaw)));
            $resolvedCategoryIds = [];
            $resolvedCategoryNames = [];

            if (empty($catItems)) {
                $rowErrors[] = 'At least one Category is required.';
            } else {
                foreach ($catItems as $ci) {
                    if (is_numeric($ci) && isset($catById[(int)$ci])) {
                        $resolvedCategoryIds[] = (int)$ci;
                        $resolvedCategoryNames[] = $catById[(int)$ci]['name'];
                    } else {
                        $lower = strtolower($ci);
                        if (isset($catByName[$lower])) {
                            $resolvedCategoryIds[] = (int)$catByName[$lower]['id'];
                            $resolvedCategoryNames[] = $catByName[$lower]['name'];
                        } else {
                            $rowErrors[] = "Unknown Category '{$ci}'. Please specify an active category name or ID.";
                        }
                    }
                }
            }
            $resolvedCategoryIds = array_values(array_unique($resolvedCategoryIds));

            // 3. Subcategories (Optional, multiple comma-separated, validated against selected category)
            $subRaw = $mapped['subcategories'] ?? $mapped['subcategory'] ?? '';
            $subItems = array_filter(array_map('trim', preg_split('/[,;|]/', (string)$subRaw)));
            $resolvedSubcategoryIds = [];
            $resolvedSubcategoryNames = [];

            foreach ($subItems as $si) {
                $sub = null;
                if (is_numeric($si) && isset($subById[(int)$si])) {
                    $sub = $subById[(int)$si];
                } else {
                    $lower = strtolower($si);
                    if (isset($subByName[$lower])) {
                        $sub = $subByName[$lower];
                    }
                }

                if ($sub) {
                    $parentCatId = (int)$sub['category_id'];
                    if (!empty($resolvedCategoryIds) && !in_array($parentCatId, $resolvedCategoryIds, true)) {
                        $parentCatName = $catById[$parentCatId]['name'] ?? "Category #{$parentCatId}";
                        $rowErrors[] = "Subcategory '{$sub['name']}' belongs to '{$parentCatName}' and cannot be used with the selected Category/Categories.";
                    } else {
                        $resolvedSubcategoryIds[] = (int)$sub['id'];
                        $resolvedSubcategoryNames[] = $sub['name'];
                    }
                } else {
                    $rowErrors[] = "Unknown Subcategory '{$si}'.";
                }
            }
            $resolvedSubcategoryIds = array_values(array_unique($resolvedSubcategoryIds));

            // 4. SKU Validation
            $sku = $mapped['sku'] ?? null;
            if (!empty($sku)) {
                $skuUpper = strtoupper($sku);
                if (isset($seenSkusInFile[$skuUpper])) {
                    $rowErrors[] = "Duplicate SKU '{$sku}' found in file (already on row {$seenSkusInFile[$skuUpper]}).";
                } else {
                    $seenSkusInFile[$skuUpper] = $rowNum;
                    // Check against DB
                    $existingSku = Product::fetch("SELECT id, title FROM products WHERE UPPER(sku) = :sku LIMIT 1", [':sku' => $skuUpper]);
                    if ($existingSku) {
                        $rowErrors[] = "SKU '{$sku}' already exists in database (assigned to Product #{$existingSku['id']}).";
                    }
                }
            }

            // 5. Wholesale Price
            $minPrice = $mapped['min_price'] ?? $mapped['price'] ?? '';
            $maxPrice = $mapped['max_price'] ?? $minPrice;

            if ($minPrice === '' || !is_numeric($minPrice) || (float)$minPrice <= 0) {
                $rowErrors[] = 'Min Price must be a valid positive number.';
            }
            $minPriceVal = (float)$minPrice;
            $maxPriceVal = is_numeric($maxPrice) ? (float)$maxPrice : $minPriceVal;
            if ($maxPriceVal < $minPriceVal) {
                $rowErrors[] = 'Max Price cannot be less than Min Price.';
            }

            // 6. Price Unit
            $priceUnitStr = strtolower($mapped['price_unit'] ?? 'piece');
            $priceUnitId = $priceUnitByName[$priceUnitStr] ?? 1;

            // 7. MOQ
            $moq = $mapped['moq'] ?? $mapped['moq_quantity'] ?? '1';
            if ($moq === '' || !is_numeric($moq) || (int)$moq < 1) {
                $rowErrors[] = 'MOQ must be an integer greater than or equal to 1.';
            }
            $moqVal = max(1, (int)$moq);

            // 8. MOQ Unit
            $moqUnitStr = strtolower($mapped['moq_unit'] ?? 'pieces');
            $moqUnitId = $moqUnitByName[$moqUnitStr] ?? 1;

            // 9. Available Quantity
            $availQty = (int)($mapped['available_quantity'] ?? 0);

            // 10. Supplier Assignment
            $targetSupplierId = 1;
            if (!$isAdmin) {
                // Sellers can ONLY import for their own supplier account.
                // Any supplier in the file is ignored to prevent privilege escalation.
                $targetSupplierId = (int)$supplierId;
            } else {
                $supKey = $mapped['supplier'] ?? $mapped['supplier_id'] ?? '';
                if (!empty($supKey)) {
                    if (is_numeric($supKey) && isset($supplierMap[(int)$supKey])) {
                        $targetSupplierId = (int)$supKey;
                    } elseif (isset($supplierMap[strtolower($supKey)])) {
                        $targetSupplierId = (int)$supplierMap[strtolower($supKey)]['id'];
                    } else {
                        $rowErrors[] = "Supplier '{$supKey}' not found in database.";
                        $targetSupplierId = 0;
                    }
                } elseif (!empty($adminSelectedSupplierId) && isset($supplierMap[(int)$adminSelectedSupplierId])) {
                    $targetSupplierId = (int)$adminSelectedSupplierId;
                } else {
                    $rowErrors[] = "Supplier is required for Super Admin imports. Please select a supplier from the dropdown or include the Supplier ID/Name in the spreadsheet.";
                    $targetSupplierId = 0;
                }
            }

            // 11. Customization and boolean flags
            $customLogo = self::parseBool($mapped['custom_logo'] ?? 'no');
            $customColor = self::parseBool($mapped['custom_color'] ?? 'no');
            $customPackaging = self::parseBool($mapped['custom_packaging'] ?? 'no');
            $oemAvailable = self::parseBool($mapped['oem_available'] ?? 'no');
            $sampleAvailable = self::parseBool($mapped['sample_available'] ?? 'no');
            $samplePrice = !empty($mapped['sample_price']) && is_numeric($mapped['sample_price']) ? (float)$mapped['sample_price'] : null;

            // 12. Images
            $primaryImage = $mapped['primary_image'] ?? '';
            $galleryImagesRaw = $mapped['gallery_images'] ?? '';
            $galleryImages = array_filter(array_map('trim', preg_split('/[,;|]/', (string)$galleryImagesRaw)));

            if (!empty($rowErrors)) {
                $errors[] = [
                    'row'     => $rowNum,
                    'product' => $name ?: 'Unnamed Row #' . $rowNum,
                    'errors'  => $rowErrors,
                    'message' => implode('; ', $rowErrors),
                ];
            } else {
                $validRecords[] = [
                    'row_num'               => $rowNum,
                    'supplier_id'           => $targetSupplierId,
                    'name'                  => $name,
                    'title'                 => $name,
                    'sku'                   => $sku,
                    'brand_name'            => $mapped['brand_name'] ?? null,
                    'category_ids'          => $resolvedCategoryIds,
                    'category_id'           => $resolvedCategoryIds[0] ?? 1,
                    'category_names'        => $resolvedCategoryNames,
                    'subcategory_ids'       => $resolvedSubcategoryIds,
                    'subcategory_id'        => $resolvedSubcategoryIds[0] ?? null,
                    'subcategory_names'     => $resolvedSubcategoryNames,
                    'min_price'             => $minPriceVal,
                    'max_price'             => $maxPriceVal,
                    'price_unit_id'         => $priceUnitId,
                    'moq'                   => $moqVal,
                    'moq_quantity'          => $moqVal,
                    'moq_unit_id'           => $moqUnitId,
                    'available_quantity'    => $availQty,
                    'short_description'     => $mapped['short_description'] ?? null,
                    'description'           => $mapped['description'] ?? $mapped['detailed_description'] ?? null,
                    'material'              => $mapped['material'] ?? null,
                    'colors'                => $mapped['colors'] ?? null,
                    'size'                  => $mapped['size'] ?? $mapped['sizes'] ?? null,
                    'weight'                => $mapped['weight'] ?? null,
                    'customization_details' => $mapped['customization_details'] ?? null,
                    'custom_logo'           => $customLogo,
                    'custom_color'          => $customColor,
                    'custom_packaging'      => $customPackaging,
                    'oem_available'         => $oemAvailable,
                    'lead_time'             => !empty($mapped['lead_time']) ? $mapped['lead_time'] : '10 - 20 Business Days',
                    'export_packaging'      => $mapped['export_packaging'] ?? null,
                    'sample_available'      => $sampleAvailable,
                    'sample_price'          => $samplePrice,
                    'primary_image'         => $primaryImage,
                    'gallery_images'        => $galleryImages,
                ];
            }
        }

        // Generate file token for commitment
        $fileToken = bin2hex(random_bytes(16));
        $cachePayload = [
            'uploader_supplier_id' => $supplierId,
            'is_admin'             => $isAdmin,
            'created_at'           => time(),
            'valid_records'        => $validRecords,
        ];
        file_put_contents(self::getTempDir() . '/' . $fileToken . '.json', json_encode($cachePayload));

        // Sample preview rows (up to 10)
        $previewRows = array_slice($validRecords, 0, 10);

        return [
            'success'          => true,
            'file_token'       => $fileToken,
            'total_rows'       => count($rawRows),
            'valid_rows_count' => count($validRecords),
            'error_rows_count' => count($errors),
            'stats'            => [
                'total'  => count($rawRows),
                'valid'  => count($validRecords),
                'errors' => count($errors),
            ],
            'errors'           => $errors,
            'preview_rows'     => $previewRows,
        ];
    }

    /**
     * Commit parsed valid records into the database
     */
    public static function commit(string $fileToken, ?int $supplierId = null, bool $isAdmin = false, int $userId = 1): array
    {
        self::ensureTempDir();
        $cacheFile = self::getTempDir() . '/' . basename($fileToken) . '.json';
        if (!file_exists($cacheFile)) {
            return [
                'success' => false,
                'message' => 'Upload session expired or invalid. Please re-upload your Excel file.',
            ];
        }

        $payload = json_decode((string)file_get_contents($cacheFile), true);
        if (!$payload || empty($payload['valid_records'])) {
            return [
                'success' => false,
                'message' => 'No valid records found in upload cache.',
            ];
        }

        $validRecords = $payload['valid_records'];
        $importedCount = 0;
        $failedCount = 0;
        $importedIds = [];
        $commitErrors = [];

        foreach ($validRecords as $rec) {
            try {
                // Determine approval and publish statuses
                if ($isAdmin) {
                    $rec['approval_status'] = 'APPROVED';
                    $rec['status'] = 'PUBLISHED';
                } else {
                    $rec['approval_status'] = 'PENDING_APPROVAL';
                    $rec['status'] = 'DRAFT';
                    $rec['supplier_id'] = (int)$supplierId; // Enforce strict supplier isolation
                }

                $images = [];
                if (!empty($rec['primary_image'])) {
                    $images[] = $rec['primary_image'];
                }
                if (!empty($rec['gallery_images'])) {
                    foreach ($rec['gallery_images'] as $gImg) {
                        if (!in_array($gImg, $images, true)) {
                            $images[] = $gImg;
                        }
                    }
                }

                $newId = Product::create($rec, $images);
                $importedIds[] = $newId;
                $importedCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                $commitErrors[] = "Row " . ($rec['row_num'] ?? '?') . " (" . ($rec['name'] ?? 'Product') . "): " . $e->getMessage();
            }
        }

        // Always remove the cache file immediately to prevent duplicate imports
        @unlink($cacheFile);

        $actionUser = $isAdmin ? 'Super Admin' : 'Supplier #' . ($supplierId ?? $userId);
        if ($importedCount > 0) {
            AuditLogger::log('BULK_IMPORT', 'Product', $importedIds[0] ?? 0, 'Bulk Product Import', [
                'imported_count' => $importedCount,
                'failed_count'   => $failedCount,
                'product_ids'    => $importedIds,
            ], "{$actionUser} bulk imported {$importedCount} products via spreadsheet.");
        }

        if ($importedCount === 0 && $failedCount > 0) {
            return [
                'success'        => false,
                'imported_count' => 0,
                'failed_count'   => $failedCount,
                'imported_ids'   => [],
                'errors'         => $commitErrors,
                'message'        => 'Failed to import products: ' . implode('; ', array_slice($commitErrors, 0, 3)),
            ];
        }

        return [
            'success'        => true,
            'imported_count' => $importedCount,
            'failed_count'   => $failedCount,
            'imported_ids'   => $importedIds,
            'errors'         => $commitErrors,
            'message'        => "Successfully imported {$importedCount} product(s) into catalog." . ($failedCount > 0 ? " ({$failedCount} rows had errors)" : ''),
        ];
    }

    /**
     * Parse CSV file
     */
    private static function parseCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) return [];

        $rows = [];
        $firstLine = fgets($handle);
        rewind($handle);

        // Detect delimiter: comma, semicolon, tab
        $delimiters = [',', ';', "\t"];
        $bestDelimiter = ',';
        $maxCount = 0;
        foreach ($delimiters as $d) {
            $count = substr_count((string)$firstLine, $d);
            if ($count > $maxCount) {
                $maxCount = $count;
                $bestDelimiter = $d;
            }
        }

        while (($data = fgetcsv($handle, 0, $bestDelimiter, '"', '\\')) !== false) {
            // Strip BOM from first cell if present
            if (empty($rows) && isset($data[0])) {
                $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$data[0]);
            }
            $rows[] = $data;
        }
        fclose($handle);
        return $rows;
    }

    /**
     * Parse OpenXML XLSX file using ZipArchive and SimpleXML
     */
    private static function parseXlsx(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return [];
        }

        // Shared strings
        $sharedStrings = [];
        $sharedXmlContent = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXmlContent !== false) {
            $sXml = simplexml_load_string($sharedXmlContent);
            if ($sXml && isset($sXml->si)) {
                foreach ($sXml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } elseif (isset($si->r)) {
                        $t = '';
                        foreach ($si->r as $r) {
                            $t .= (string)$r->t;
                        }
                        $sharedStrings[] = $t;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // Worksheet
        $sheetXmlContent = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXmlContent === false) {
            // Check other sheet names
            for ($i = 1; $i <= 5; $i++) {
                $sheetXmlContent = $zip->getFromName("xl/worksheets/sheet{$i}.xml");
                if ($sheetXmlContent !== false) break;
            }
        }

        if ($sheetXmlContent === false) {
            $zip->close();
            return [];
        }

        $wXml = simplexml_load_string($sheetXmlContent);
        $zip->close();

        if (!$wXml || !isset($wXml->sheetData->row)) {
            return [];
        }

        $rows = [];
        foreach ($wXml->sheetData->row as $r) {
            $rowArr = [];
            foreach ($r->c as $c) {
                $cellRef = (string)$c['r']; // e.g. A1, B2
                $colIndex = self::columnLetterToIndex(preg_replace('/[0-9]/', '', $cellRef));
                $cellType = (string)$c['t'];

                $val = '';
                if ($cellType === 's') {
                    // Shared string
                    $sIndex = (int)$c->v;
                    $val = $sharedStrings[$sIndex] ?? '';
                } elseif ($cellType === 'inlineStr' && isset($c->is->t)) {
                    $val = (string)$c->is->t;
                } elseif (isset($c->v)) {
                    $val = (string)$c->v;
                }

                $rowArr[$colIndex] = $val;
            }

            // Fill missing gaps in sparse row
            if (!empty($rowArr)) {
                $maxKey = max(array_keys($rowArr));
                $denseRow = [];
                for ($k = 0; $k <= $maxKey; $k++) {
                    $denseRow[$k] = $rowArr[$k] ?? '';
                }
                $rows[] = $denseRow;
            }
        }

        return $rows;
    }

    private static function columnLetterToIndex(string $letter): int
    {
        $letter = strtoupper($letter);
        $len = strlen($letter);
        $idx = 0;
        for ($i = 0; $i < $len; $i++) {
            $idx = $idx * 26 + (ord($letter[$i]) - 64);
        }
        return $idx - 1;
    }

    /**
     * Map fuzzy spreadsheet header names to internal field keys
     */
    private static function normalizeHeaders(array $headers): array
    {
        $map = [];
        foreach ($headers as $idx => $h) {
            $hClean = strtolower(trim((string)$h));
            $hClean = preg_replace('/[^a-z0-9_]/', '', str_replace([' ', '-', '/'], '_', $hClean));

            if (str_contains($hClean, 'product_name') || str_contains($hClean, 'title') || $hClean === 'name') {
                $map[$idx] = 'name';
            } elseif (str_contains($hClean, 'subcategory') || str_contains($hClean, 'subcategories')) {
                $map[$idx] = 'subcategories';
            } elseif (str_contains($hClean, 'category') || str_contains($hClean, 'categories')) {
                $map[$idx] = 'categories';
            } elseif ($hClean === 'sku' || str_contains($hClean, 'item_code') || str_contains($hClean, 'product_sku')) {
                $map[$idx] = 'sku';
            } elseif (str_contains($hClean, 'brand')) {
                $map[$idx] = 'brand_name';
            } elseif (str_contains($hClean, 'min_price') || str_contains($hClean, 'price_min') || str_contains($hClean, 'min_wholesale')) {
                $map[$idx] = 'min_price';
            } elseif (str_contains($hClean, 'max_price') || str_contains($hClean, 'price_max') || str_contains($hClean, 'max_wholesale')) {
                $map[$idx] = 'max_price';
            } elseif (str_contains($hClean, 'price_unit') || $hClean === 'unit') {
                $map[$idx] = 'price_unit';
            } elseif (str_contains($hClean, 'moq_unit')) {
                $map[$idx] = 'moq_unit';
            } elseif (str_contains($hClean, 'moq') || str_contains($hClean, 'min_order')) {
                $map[$idx] = 'moq';
            } elseif (str_contains($hClean, 'available_quantity') || str_contains($hClean, 'stock') || str_contains($hClean, 'inventory')) {
                $map[$idx] = 'available_quantity';
            } elseif (str_contains($hClean, 'short_description') || str_contains($hClean, 'summary')) {
                $map[$idx] = 'short_description';
            } elseif (str_contains($hClean, 'description') || str_contains($hClean, 'detail')) {
                $map[$idx] = 'description';
            } elseif (str_contains($hClean, 'material')) {
                $map[$idx] = 'material';
            } elseif (str_contains($hClean, 'color')) {
                $map[$idx] = 'colors';
            } elseif (str_contains($hClean, 'size')) {
                $map[$idx] = 'size';
            } elseif (str_contains($hClean, 'weight')) {
                $map[$idx] = 'weight';
            } elseif (str_contains($hClean, 'customization')) {
                $map[$idx] = 'customization_details';
            } elseif (str_contains($hClean, 'custom_logo') || str_contains($hClean, 'logo')) {
                $map[$idx] = 'custom_logo';
            } elseif (str_contains($hClean, 'custom_color')) {
                $map[$idx] = 'custom_color';
            } elseif (str_contains($hClean, 'export_packaging') || str_contains($hClean, 'packaging_info') || str_contains($hClean, 'export_package')) {
                $map[$idx] = 'export_packaging';
            } elseif (str_contains($hClean, 'custom_packaging') || str_contains($hClean, 'packaging')) {
                $map[$idx] = 'custom_packaging';
            } elseif (str_contains($hClean, 'oem')) {
                $map[$idx] = 'oem_available';
            } elseif (str_contains($hClean, 'lead_time')) {
                $map[$idx] = 'lead_time';
            } elseif (str_contains($hClean, 'sample_price')) {
                $map[$idx] = 'sample_price';
            } elseif (str_contains($hClean, 'sample')) {
                $map[$idx] = 'sample_available';
            } elseif (str_contains($hClean, 'primary_image') || str_contains($hClean, 'main_image') || str_contains($hClean, 'cover_image')) {
                $map[$idx] = 'primary_image';
            } elseif (str_contains($hClean, 'gallery') || str_contains($hClean, 'images')) {
                $map[$idx] = 'gallery_images';
            } elseif (str_contains($hClean, 'supplier')) {
                $map[$idx] = 'supplier';
            } else {
                $map[$idx] = $hClean;
            }
        }
        return $map;
    }

    private static function parseBool(mixed $val): int
    {
        if (is_numeric($val)) return (int)$val > 0 ? 1 : 0;
        $str = strtolower(trim((string)$val));
        return in_array($str, ['yes', 'y', 'true', '1', 'available', 'enabled'], true) ? 1 : 0;
    }
}
