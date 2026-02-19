<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$total = $pdo->query("SELECT COUNT(*) FROM alumni")->fetchColumn();
$employed = $pdo->query("SELECT COUNT(*) FROM employment WHERE status IN ('Employed','Self-Employed')")->fetchColumn();
$rate = $total ? round(($employed/$total)*100,1) : 0;
$jobs = $pdo->query("SELECT COUNT(*) FROM job_postings WHERE status='active'")->fetchColumn();

echo json_encode(['total_alumni'=>$total, 'employment_rate'=>$rate, 'active_jobs'=>$jobs]);
?>