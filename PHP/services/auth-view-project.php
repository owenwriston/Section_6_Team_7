<?php
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../services/db-connect.php';
require __DIR__ . '/../services/auth-permissions.php';

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

// Same message for "missing" and "not yours" so project ids can't be probed
if ($pid <= 0 || !can_view_project($pdo, $uid, $me['permissions'], $pid)) {
    http_response_code(404);
    echo 'Project not found. <a href="dashboard.php">Back to dashboard</a>';
    exit;
}

$stmt = $pdo->prepare(
    'SELECT p.name, p.location, p.status, p.create_date, p.last_update_date, u.fname, u.lname
       FROM projects p JOIN users u ON u.uid = p.uid_owner
      WHERE p.pid = ?'
);
$stmt->execute([$pid]);
$project = $stmt->fetch();

$stmt = $pdo->prepare('SELECT fpid, upload_date FROM floorplans WHERE pid = ? ORDER BY fpid');
$stmt->execute([$pid]);
$floorplans = $stmt->fetchAll();

// Prepare SQL query to retrieve task details,
//   including task IDs, floorplan IDs, titles, descriptions, statuses, page nums, and X/Y coordinate pin positions
$stmt = $pdo->prepare(
    'SELECT t.tid, t.fpid, t.title, t.description, t.status, t.page, t.pos_x, t.pos_y
       FROM tasks t JOIN floorplans f ON f.fpid = t.fpid
      WHERE f.pid = ? ORDER BY t.fpid, t.page, t.tid'
);
$stmt->execute([$pid]);
$tasksByPlan = [];
foreach ($stmt->fetchAll() as $t) {
    $tasksByPlan[(int) $t['fpid']][] = $t;
}

$title = $project['name'] !== '' ? $project['name'] : '(Untitled)';