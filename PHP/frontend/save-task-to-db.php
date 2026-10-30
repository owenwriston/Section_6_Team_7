<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['uid'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require __DIR__ . '/../services/db-connect.php';

$uid = (int) $_SESSION['uid'];
$fpid = (int) ($_POST['fpid'] ?? 0);
$page = (int) ($_POST['page'] ?? 1);
$posX = isset($_POST['pos_x']) ? (float) $_POST['pos_x'] : null;
$posY = isset($_POST['pos_y']) ? (float) $_POST['pos_y'] : null;
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($fpid <= 0 || $title === '') {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO tasks (fpid, uid_creator, title, description, status, pos_x, pos_y, page)
         VALUES (?, ?, ?, ?, "open", ?, ?, ?)'
    );
    $stmt->execute([$fpid, $uid, $title, $description, $posX, $posY, $page]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}