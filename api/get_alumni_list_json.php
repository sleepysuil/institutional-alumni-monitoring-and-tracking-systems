<?php
require_once '../config/db.php';
$search = $_GET['search'] ?? '';
$stmt = $pdo->prepare("SELECT a.*, e.status FROM alumni a LEFT JOIN employment e ON a.id = e.alumni_id WHERE a.first_name LIKE ? OR a.last_name LIKE ? OR a.student_id LIKE ? OR a.email LIKE ?");
$term = "%$search%";
$stmt->execute([$term, $term, $term, $term]);
echo json_encode($stmt->fetchAll());
?>