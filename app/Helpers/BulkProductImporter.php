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
    private const TEMP_DIR = __DIR__ . '/../../storage/tmp_uploads';

    public static function ensureTempDir(): void
    {
        if (!is_dir(self::TEMP_DIR)) {
            @mkdir(self::TEMP_DIR, 0777, true);
        }
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
            'Sample Available (Yes/No)',
            'Sample Price ($)',
            'Primary Image (URL or image filename)',
            'Gallery Images (comma-separated URLs)',
        ];

        if ($isAdmin) {
            $headers[] = 'Supplier ID or Company Name';
        }

        $sampleRow = [
            'Professional Match PU Football (Size 5)',
            'Footballs & Soccer, Sports Bags & Accessories',
            'Match Balls, Training Cones',
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
            'Fitness Equipment, Training & Gym Wear',
            'Free Weights, Resistance Bands',
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

        // Generate native XLSX
        $tempFile = tempnam(sys_get_temp_dir(), 'sj_xlsx_');
        self::createXlsxFile($tempFile, $headers, [$sampleRow, $sampleRow2]);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Content-Length: ' . filesize($tempFile));
        header('Cache-Control: max-age=0');
        readfile($tempFile);
        @unlink($tempFile);
        exit;
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
    public static function parseAndValidate(string $filePath, ?int $supplierId = null, bool $isAdmin = false): array
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

            // 3. Subcategories (Optional, multiple comma-separated)
            $subRaw = $mapped['subcategories'] ?? $mapped['subcategory'] ?? '';
            $subItems = array_filter(array_map('trim', preg_split('/[,;|]/', (string)$subRaw)));
            $resolvedSubcategoryIds = [];
            $resolvedSubcategoryNames = [];

            foreach ($subItems as $si) {
                if (is_numeric($si) && isset($subById[(int)$si])) {
                    $resolvedSubcategoryIds[] = (int)$si;
                    $resolvedSubcategoryNames[] = $subById[(int)$si]['name'];
                } else {
                    $lower = strtolower($si);
                    if (isset($subByName[$lower])) {
                        $resolvedSubcategoryIds[] = (int)$subByName[$lower]['id'];
                        $resolvedSubcategoryNames[] = $subByName[$lower]['name'];
                    } else {
                        // Subcategory not found warning
                        $rowErrors[] = "Unknown Subcategory '{$si}'.";
                    }
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
            if ($supplierId !== null && $supplierId > 0) {
                $targetSupplierId = $supplierId;
            } elseif ($isAdmin) {
                $supKey = $mapped['supplier'] ?? $mapped['supplier_id'] ?? '';
                if (!empty($supKey)) {
                    if (is_numeric($supKey) && isset($supplierMap[(int)$supKey])) {
                        $targetSupplierId = (int)$supKey;
                    } elseif (isset($supplierMap[strtolower($supKey)])) {
                        $targetSupplierId = (int)$supplierMap[strtolower($supKey)]['id'];
                    } else {
                        $rowErrors[] = "Supplier '{$supKey}' not found in database.";
                    }
                } else {
                    $targetSupplierId = 1; // Default platform supplier
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
        file_put_contents(self::TEMP_DIR . '/' . $fileToken . '.json', json_encode($cachePayload));

        // Sample preview rows (up to 10)
        $previewRows = array_slice($validRecords, 0, 10);

        return [
            'success'          => true,
            'file_token'       => $fileToken,
            'total_rows'       => count($rawRows),
            'valid_rows_count' => count($validRecords),
            'error_rows_count' => count($errors),
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
        $cacheFile = self::TEMP_DIR . '/' . basename($fileToken) . '.json';
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
        $importedIds = [];

        Product::beginTransaction();
        try {
            foreach ($validRecords as $rec) {
                // Determine approval and publish statuses
                if ($isAdmin) {
                    $rec['approval_status'] = 'APPROVED';
                    $rec['status'] = 'PUBLISHED';
                } else {
                    $rec['approval_status'] = 'PENDING_APPROVAL';
                    $rec['status'] = 'DRAFT';
                    $rec['supplier_id'] = $supplierId; // Enforce strict supplier isolation
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

                // Multi-category & Multi-subcategory syncing
                if (!empty($rec['category_ids'])) {
                    Product::syncCategories($newId, $rec['category_ids'], $rec['category_id']);
                }
                if (!empty($rec['subcategory_ids'])) {
                    Product::syncSubcategories($newId, $rec['subcategory_ids'], $rec['subcategory_id']);
                }

                $importedIds[] = $newId;
                $importedCount++;
            }

            Product::commit();
            @unlink($cacheFile);

            $actionUser = $isAdmin ? 'Super Admin' : 'Supplier #' . ($supplierId ?? $userId);
            AuditLogger::log('BULK_IMPORT', 'Product', $importedIds[0] ?? 0, 'Bulk Product Import', [
                'imported_count' => $importedCount,
                'product_ids'    => $importedIds,
            ], "{$actionUser} bulk imported {$importedCount} products via spreadsheet.");

            return [
                'success'        => true,
                'message'        => "Successfully imported {$importedCount} product(s) into catalog.",
                'imported_count' => $importedCount,
                'imported_ids'   => $importedIds,
            ];
        } catch (\Throwable $e) {
            Product::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to commit products to database: ' . $e->getMessage(),
            ];
        }
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

        while (($data = fgetcsv($handle, 0, $bestDelimiter)) !== false) {
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
