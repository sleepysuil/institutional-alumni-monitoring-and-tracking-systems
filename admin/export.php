<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Try to locate vendor/autoload.php
$possible_paths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
];

$autoload_found = false;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoload_found = true;
        break;
    }
}

if (!$autoload_found) {
    die('Composer dependencies not found. Please run "composer require dompdf/dompdf phpoffice/phpspreadsheet" from your project root.');
}

// Use statements
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

// Get filters
$report_type = $_GET['report_type'] ?? 'list';
$year = $_GET['year'] ?? '';
$program = $_GET['program'] ?? '';
$format = $_GET['format'] ?? 'pdf';

// Build query
$sql = "SELECT a.*, e.status, e.company, e.position, e.industry 
        FROM alumni a 
        LEFT JOIN employment e ON a.id = e.alumni_id 
        WHERE 1";
$params = [];
if ($year && $year != 'All Years') {
    $sql .= " AND a.graduation_year = ?";
    $params[] = $year;
}
if ($program && $program != 'All Programs') {
    $sql .= " AND a.program = ?";
    $params[] = $program;
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alumni = $stmt->fetchAll();

// Prepare data for export
$data = [];
foreach ($alumni as $a) {
    $data[] = [
        'student_id' => $a['student_id'],
        'name' => $a['first_name'] . ' ' . $a['last_name'],
        'program' => $a['program'],
        'year' => $a['graduation_year'],
        'status' => $a['status'] ?? 'N/A',
        'company' => $a['company'] ?? 'N/A',
        'position' => $a['position'] ?? 'N/A',
        'industry' => $a['industry'] ?? 'N/A',
        'email' => $a['email'],
        'phone' => $a['phone']
    ];
}

// Helper function to convert column index to letter (A, B, C, ...)
function columnLetter($index) {
    $letter = '';
    while ($index > 0) {
        $mod = ($index - 1) % 26;
        $letter = chr(65 + $mod) . $letter;
        $index = floor(($index - $mod) / 26);
    }
    return $letter;
}

// Handle CSV export
if ($format == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="alumni_report.csv"');
    $output = fopen('php://output', 'w');
    // Headers
    fputcsv($output, array_keys($data[0]));
    // Data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Handle Excel export
if ($format == 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Headers
    $col = 1;
    foreach (array_keys($data[0]) as $header) {
        $columnLetter = columnLetter($col);
        $sheet->setCellValue($columnLetter . '1', ucwords(str_replace('_', ' ', $header)));
        $col++;
    }
    
    // Data
    $row = 2;
    foreach ($data as $rowData) {
        $col = 1;
        foreach ($rowData as $value) {
            $columnLetter = columnLetter($col);
            $sheet->setCellValue($columnLetter . $row, $value);
            $col++;
        }
        $row++;
    }
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="alumni_report.xlsx"');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Handle PDF export
if ($format == 'pdf') {
    // Build HTML table
    $html = '<h2>Alumni Report</h2>';
    $html .= '<table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%;">';
    $html .= '<thead><tr>';
    foreach (array_keys($data[0]) as $header) {
        $html .= '<th>' . ucwords(str_replace('_', ' ', $header)) . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . htmlspecialchars($cell) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream("alumni_report.pdf", array("Attachment" => true));
    exit;
}

// If format not recognized
header('Location: reports.php');
exit;
?>