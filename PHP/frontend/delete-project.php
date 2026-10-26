<?php
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

require __DIR__ . '/../services/db-connect.php';
require __DIR__ . '/../services/delete-project.php';

if (!hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''))) {
    $_SESSION['flash_error'] = 'Your session expired. Reload the page and try again.';
} else {
    $error = delete_project($pdo, (int) $_SESSION['uid'], (int) ($_POST['pid'] ?? 0));
    if ($error === '') {
        header('Location: dashboard.php?deleted=1');
        exit;
    }
    $_SESSION['flash_error'] = $error;
}

header('Location: dashboard.php');
exit;
