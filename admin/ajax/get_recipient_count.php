<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';

$recipient_type = $_POST['recipient_type'] ?? 'all';
$params = [];

$sql = "SELECT COUNT(*) as count FROM alumni WHERE 1=1";

if ($recipient_type == 'course' && !empty($_POST['course'])) {
    $sql .= " AND program = ?";
    $params[] = $_POST['course'];
} elseif ($recipient_type == 'year' && !empty($_POST['year'])) {
    $sql .= " AND graduation_year = ?";
    $params[] = $_POST['year'];
} elseif ($recipient_type == 'status' && !empty($_POST['employment_status'])) {
    $sql .= " AND id IN (SELECT alumni_id FROM employment WHERE status = ?)";
    $params[] = $_POST['employment_status'];
} elseif ($recipient_type == 'gender' && !empty($_POST['gender'])) {
    $sql .= " AND gender = ?";
    $params[] = $_POST['gender'];
} elseif ($recipient_type == 'batch' && !empty($_POST['batch_year'])) {
    $sql .= " AND graduation_year = ?";
    $params[] = $_POST['batch_year'];
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$count = $stmt->fetchColumn();

echo json_encode(['count' => $count]);
?>