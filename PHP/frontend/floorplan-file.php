<?php
session_start();

if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    exit('Log in first.');
}

require __DIR__ . '/../services/db-connect.php';
require __DIR__ . '/../services/auth-permissions.php';
require __DIR__ . '/../services/upload-fp.php';

$uid  = (int) $_SESSION['uid'];
$fpid = (int) ($_GET['fpid'] ?? 0);

$stmt = $pdo->prepare('SELECT permissions FROM users WHERE uid = ?');
$stmt->execute([$uid]);
$me = $stmt->fetch();

$stmt = $pdo->prepare('SELECT pid, file_path FROM floorplans WHERE fpid = ?');
$stmt->execute([$fpid]);
$plan = $stmt->fetch();

if (!$me || !$plan || !can_view_project($pdo, $uid, $me['permissions'], (int) $plan['pid'])) {
    http_response_code(404);
    exit('Not found.');
}

$path = FLOORPLAN_DIR . '/' . basename($plan['file_path']);
if (!is_file($path)) {
    http_response_code(404);
    exit('The floorplan file is missing from the server.');
}

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="floorplan-' . $fpid . '.pdf"');
header('X-Content-Type-Options: nosniff');
readfile($path);
