<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// This file outputs personal data, so it must only be reachable by admins.
// It uses $_SESSION['role'] when your login sets it; otherwise it falls back to
// "logged in and not an alumni account". Adjust here if your session keys differ.
$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;
$isAdmin = $role !== null
    ? $role === 'admin'
    : (isset($_SESSION['user_id']) && empty($_SESSION['alumni_id']));
if (!$isAdmin) {
    http_response_code(403);
    die('Access denied.');
}

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

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Dompdf\Dompdf;
use Dompdf\Options;

// ---- Filters ----
$year    = $_GET['year'] ?? '';
$program = $_GET['program'] ?? '';
$search  = trim($_GET['search'] ?? '');
$status  = $_GET['status'] ?? '';
$format  = $_GET['format'] ?? 'pdf';
if (!in_array($format, ['csv', 'excel', 'pdf'], true)) $format = 'pdf';

$sql = "SELECT a.*, e.status, e.company, e.position, e.industry, e.salary_range
        FROM alumni a
        LEFT JOIN employment e ON a.id = e.alumni_id
        WHERE 1";
$params = [];
if ($year && $year != 'All Years') { $sql .= " AND a.graduation_year = ?"; $params[] = (int)$year; }
if ($program && $program != 'All Programs') { $sql .= " AND a.program = ?"; $params[] = $program; }
if ($search !== '') {
    $sql .= " AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.student_id LIKE ? OR e.company LIKE ? OR e.position LIKE ?)";
    for ($i = 0; $i < 5; $i++) $params[] = "%$search%";
}
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) { $sql .= " AND a.created_at >= ?"; $params[] = $start_date . ' 00:00:00'; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date))   { $sql .= " AND a.created_at <= ?"; $params[] = $end_date . ' 23:59:59'; }
if ($status === 'No Record') {
    $sql .= " AND e.status IS NULL";
} elseif (in_array($status, ['Employed', 'Self-Employed', 'Unemployed', 'Pursuing Higher Education'], true)) {
    $sql .= " AND e.status = ?"; $params[] = $status;
}
$sql .= " ORDER BY a.graduation_year DESC, a.last_name, a.first_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alumni = $stmt->fetchAll();

// Fixed headers so an empty result still exports a valid file (the old code crashed on $data[0])
$headers = ['student_id', 'name', 'program', 'year', 'status', 'company', 'position', 'industry', 'salary_range', 'email', 'phone'];
$data = [];
foreach ($alumni as $a) {
    $data[] = [
        'student_id'   => $a['student_id'],
        'name'         => $a['first_name'] . ' ' . $a['last_name'],
        'program'      => $a['program'],
        'year'         => $a['graduation_year'],
        'status'       => $a['status'] ?? 'N/A',
        'company'      => $a['company'] ?? 'N/A',
        'position'     => $a['position'] ?? 'N/A',
        'industry'     => $a['industry'] ?? 'N/A',
        'salary_range' => $a['salary_range'] ?? 'N/A',
        'email'        => $a['email'],
        'phone'        => $a['phone'] ?? '',
    ];
}
$report_type = in_array($_GET['report_type'] ?? 'list', ['list', 'employment', 'industry'], true) ? $_GET['report_type'] : 'list';
$report_title = 'Alumni Report';
if ($report_type !== 'list') {
    // Summary reports: one row per category, matching the preview in reports.php
    $field = $report_type === 'industry' ? 'industry' : 'status';
    $none  = $report_type === 'industry' ? 'Unspecified' : 'No Record';
    $counts = [];
    foreach ($data as $row) {
        $k = ($row[$field] === 'N/A' || $row[$field] === '') ? $none : $row[$field];
        $counts[$k] = ($counts[$k] ?? 0) + 1;
    }
    arsort($counts);
    $sum = array_sum($counts);
    $headers = ['category', 'count', 'percentage'];
    $data = [];
    foreach ($counts as $k => $n) {
        $data[] = ['category' => $k, 'count' => $n, 'percentage' => ($sum ? round($n / $sum * 100, 1) : 0) . '%'];
    }
    $report_title = $report_type === 'industry' ? 'Industry Distribution' : 'Employment Status Report';
}
$label = fn($h) => ucwords(str_replace('_', ' ', $h));
$filename = ($report_type === 'list' ? 'alumni_report' : $report_type . '_report') . '_' . date('Y-m-d');

// Stop spreadsheet apps from running cell text as a formula (CSV injection)
function csvSafe($v) {
    $v = (string)$v;
    if ($v !== '' && preg_match('/^[=+\-@\t\r]/', $v) && !preg_match('/^\+[\d\s\-()]+$/', $v)) return "'" . $v;
    return $v;
}

if (ob_get_length()) ob_end_clean();

// ---- CSV ----
if ($format == 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows ₱ and accents correctly
    fputcsv($out, array_map($label, $headers));
    foreach ($data as $row) fputcsv($out, array_map('csvSafe', $row));
    fclose($out);
    exit;
}

// ---- Excel ----
if ($format == 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle(substr(preg_replace('/[^A-Za-z0-9 ]/', '', $report_title), 0, 30));

    foreach ($headers as $i => $h) {
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . '1', $label($h));
    }
    $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(count($headers)) . '1')->getFont()->setBold(true);

    $r = 2;
    foreach ($data as $row) {
        $c = 1;
        foreach ($headers as $h) {
            // Explicit string type: values are never interpreted as formulas
            $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($c++) . $r, (string)$row[$h], DataType::TYPE_STRING);
        }
        $r++;
    }
    foreach (range(1, count($headers)) as $c) {
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
    }
    $sheet->freezePane('A2');

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
    (new Xlsx($spreadsheet))->save('php://output');
    exit;
}

// ---- PDF ----
$filters = [];
if ($year && $year != 'All Years') $filters[] = 'Year: ' . $year;
if ($program && $program != 'All Programs') $filters[] = 'Program: ' . $program;
if ($status !== '') $filters[] = 'Status: ' . $status;
if ($search !== '') $filters[] = 'Search: ' . $search;
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) $filters[] = 'From: ' . $start_date;
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) $filters[] = 'To: ' . $end_date;

$html  = '<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; }
    h2 { margin: 0 0 4px; }
    .meta { color: #555; margin-bottom: 10px; }
    table { border-collapse: collapse; width: 100%; }
    th { background: #388087; color: #fff; text-align: left; }
    th, td { border: 1px solid #999; padding: 4px; }
    tr:nth-child(even) td { background: #f3f7f7; }
</style>';
$html .= '<h2>' . htmlspecialchars($report_title) . '</h2>';
$html .= '<div class="meta">Generated ' . date('M j, Y g:i A') . ' &bull; ' . count($data) . ' record(s)'
       . ($filters ? ' &bull; ' . htmlspecialchars(implode(' | ', $filters)) : '') . '</div>';
$html .= '<table><thead><tr>';
foreach ($headers as $h) $html .= '<th>' . htmlspecialchars($label($h)) . '</th>';
$html .= '</tr></thead><tbody>';
if (!$data) {
    $html .= '<tr><td colspan="' . count($headers) . '" style="text-align:center">No records match the selected filters.</td></tr>';
}
foreach ($data as $row) {
    $html .= '<tr>';
    foreach ($headers as $h) $html .= '<td>' . htmlspecialchars((string)$row[$h]) . '</td>';
    $html .= '</tr>';
}
$html .= '</tbody></table>';

$options = new Options();
$options->set('isRemoteEnabled', false); // no need to fetch remote files; safer
$options->set('defaultFont', 'DejaVu Sans'); // supports the peso sign
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream($filename . '.pdf', ['Attachment' => true]);
exit;