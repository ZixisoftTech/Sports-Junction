<?php
declare(strict_types=1);

namespace App\Helpers;

class Exporter
{
    /**
     * Prevent CSV Formula Injection by prepending single quote if value starts with formula characters.
     */
    public static function sanitizeCellValue(mixed $val): string
    {
        if ($val === null) {
            return '';
        }
        $str = (string)$val;
        $str = trim($str);

        // Check if value starts with dangerous formula symbols: =, +, -, @, \t, \r
        if (preg_match('/^[=\+\-@\t\r]/', $str)) {
            $str = "'" . $str;
        }

        return $str;
    }

    public static function exportCsv(string $filename, array $headers, array $rows): void
    {
        // Clean output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Output UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Header row
        fputcsv($output, $headers);

        // Data rows
        foreach ($rows as $row) {
            $sanitizedRow = array_map([self::class, 'sanitizeCellValue'], $row);
            fputcsv($output, $sanitizedRow);
        }

        fclose($output);
        exit;
    }

    public static function exportExcelXml(string $filename, array $headers, array $rows): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<?mso-application progid=\"Excel.Sheet\"?>\n";
        echo "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"
                xmlns:o=\"urn:schemas-microsoft-com:office:office\"
                xmlns:x=\"urn:schemas-microsoft-com:office:excel\"
                xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\"
                xmlns:html=\"http://www.w3.org/TR/REC-html40\">\n";
        echo " <Styles>
                <Style ss:ID=\"Default\" ss:Name=\"Normal\">
                    <Alignment ss:Vertical=\"Center\"/>
                    <Borders/>
                    <Font ss:FontName=\"Segoe UI\" ss:Size=\"10\"/>
                </Style>
                <Style ss:ID=\"Header\">
                    <Alignment ss:Horizontal=\"Center\" ss:Vertical=\"Center\"/>
                    <Borders>
                        <Border ss:Position=\"Bottom\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\"/>
                    </Borders>
                    <Font ss:FontName=\"Segoe UI\" ss:Size=\"10\" ss:Bold=\"1\" ss:Color=\"#FFFFFF\"/>
                    <Interior ss:Color=\"#1E3A8A\" ss:Pattern=\"Solid\"/>
                </Style>
               </Styles>\n";
        echo " <Worksheet ss:Name=\"Records\">\n";
        echo "  <Table>\n";

        // Header
        echo "   <Row ss:Height=\"24\" ss:StyleID=\"Header\">\n";
        foreach ($headers as $h) {
            $val = htmlspecialchars((string)$h, ENT_XML1, 'UTF-8');
            echo "    <Cell ss:StyleID=\"Header\"><Data ss:Type=\"String\">{$val}</Data></Cell>\n";
        }
        echo "   </Row>\n";

        // Rows
        foreach ($rows as $row) {
            echo "   <Row ss:Height=\"20\">\n";
            foreach ($row as $cell) {
                $val = self::sanitizeCellValue($cell);
                $encoded = htmlspecialchars($val, ENT_XML1, 'UTF-8');
                echo "    <Cell><Data ss:Type=\"String\">{$encoded}</Data></Cell>\n";
            }
            echo "   </Row>\n";
        }

        echo "  </Table>\n";
        echo " </Worksheet>\n";
        echo "</Workbook>\n";
        exit;
    }

    public static function exportPdfView(string $title, array $headers, array $rows): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/html; charset=UTF-8');
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title><?= htmlspecialchars($title) ?> — Sports Junction</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #fff; color: #1e293b; padding: 25px; }
                .report-header { border-bottom: 3px solid #1E3A8A; padding-bottom: 15px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; }
                .report-header h2 { color: #1E3A8A; font-weight: 700; margin: 0; font-size: 22px; }
                .report-header p { margin: 2px 0 0; color: #64748b; font-size: 13px; }
                .badge-status { padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 11px; }
                .badge-active { background-color: #dcfce7; color: #15803d; }
                .badge-inactive { background-color: #fee2e2; color: #b91c1c; }
                table thead th { background-color: #1E3A8A !important; color: #fff !important; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
                table tbody td { font-size: 12px; vertical-align: middle; }
                @media print {
                    .no-print { display: none !important; }
                    body { padding: 0; }
                }
            </style>
        </head>
        <body>
            <div class="report-header">
                <div>
                    <h2><?= htmlspecialchars($title) ?></h2>
                    <p>Sports Junction — India's Sports Manufacturing Marketplace | Generated: <?= date('d M Y, h:i A') ?></p>
                </div>
                <div class="no-print">
                    <button onclick="window.print()" class="btn btn-sm btn-primary">Print / Save as PDF</button>
                    <button onclick="window.close()" class="btn btn-sm btn-outline-secondary">Close</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <?php foreach ($headers as $h): ?>
                                <th><?= htmlspecialchars($h) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="<?= count($headers) ?>" class="text-center text-muted py-4">No records found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <?php foreach ($row as $cell): ?>
                                        <td>
                                            <?php if ($cell === 'ACTIVE'): ?>
                                                <span class="badge-status badge-active">ACTIVE</span>
                                            <?php elseif ($cell === 'INACTIVE'): ?>
                                                <span class="badge-status badge-inactive">INACTIVE</span>
                                            <?php else: ?>
                                                <?= htmlspecialchars((string)$cell) ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-muted small text-end">
                Total Records: <?= count($rows) ?>
            </div>
            <script>
                // Auto trigger print prompt
                window.addEventListener('load', () => {
                    setTimeout(() => { window.print(); }, 400);
                });
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}
