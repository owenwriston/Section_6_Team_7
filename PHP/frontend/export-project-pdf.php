<?php
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../services/db-connect.php';
require __DIR__ . '/../services/project-report.php';
require __DIR__ . '/../services/project-pdf.php';

$uid = (int) $_SESSION['uid'];
$pid = (int) ($_GET['pid'] ?? 0);

$stmt = $pdo->prepare('SELECT permissions FROM users WHERE uid = ?');
$stmt->execute([$uid]);
$me = $stmt->fetch();
if (!$me) {
    session_destroy();
    header('Location: login.php');
    exit;
}

//prevent probing of other projects
$report = $pid > 0 ? get_project_report($pdo, $uid, $me['permissions'], $pid) : null;
if ($report === null) {
    http_response_code(404);
    echo 'Project not found. <a href="dashboard.php">Back to dashboard</a>';
    exit;
}

$pdf = build_project_pdf($report);

//give safe file name
$base = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $report['project']['name']);
$base = trim($base, '_') !== '' ? trim($base, '_') : 'project-' . $pid;

while (ob_get_level() > 0) {
    ob_end_clean();
}
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $base . '-report.pdf"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, no-store');
echo $pdf;
exit;
