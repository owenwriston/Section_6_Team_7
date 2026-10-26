<?php
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

require __DIR__ . '/../services/db-connect.php';
require __DIR__ . '/../services/add-floorplans.php';

$pid = (int) ($_POST['pid'] ?? 0);

if (!hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''))) {
    $_SESSION['flash_error'] = 'Your session expired. Try again.';
} else {
    $files  = normalize_uploads($_FILES['floorplans'] ?? []);
    $result = add_floorplans($pdo, (int) $_SESSION['uid'], $pid, $files);
    if ($result['error'] !== '') {
        $_SESSION['flash_error'] = $result['error'];
    } else {
        $_SESSION['flash_ok'] = $result['count'] . ' floorplan(s) added.';
    }
}

header('Location: project-view.php?pid=' . $pid);
exit;
