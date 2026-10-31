<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['uid'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require __DIR__ . '/../services/db-connect.php';

$uid = (int) $_SESSION['uid'];

// Verify user permissions server-side
$stmt = $pdo->prepare('SELECT permissions FROM users WHERE uid = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch();
$role = strtolower(trim((string) ($user['permissions'] ?? '')));
if (!in_array($role, ['admin', 'project owner', 'contractor'], true)) {
    echo json_encode(['success' => false, 'error' => 'Forbidden: You do not have permission to create tasks']);
    exit;
}

$fpid = (int) ($_POST['fpid'] ?? 0);
$page = (int) ($_POST['page'] ?? 1);
$posX = isset($_POST['pos_x']) ? (float) $_POST['pos_x'] : null;
$posY = isset($_POST['pos_y']) ? (float) $_POST['pos_y'] : null;

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$taskType = $_POST['task_type'] ?? 'issue';
$roomNumber = trim($_POST['room_number'] ?? '');
$stampInitials = trim($_POST['stamp_initials'] ?? '');
$rootCause = trim($_POST['root_cause'] ?? '');

$startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
$dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
$assignees = array_map('intval', (array) ($_POST['assignees'] ?? []));

// Enforce maximum 2 assignees limit
if (count($assignees) > 2) {
    echo json_encode(['success' => false, 'error' => 'A task can have a maximum of two assignees']);
    exit;
}

if ($fpid <= 0 || $title === '') {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
	$pdo->beginTransaction();
	
    $stmt = $pdo->prepare(
        'INSERT INTO tasks (fpid, uid_creator, uid_last_editor, title, description, status, task_type, room_number, stamp_initials, root_cause, start_date, due_date, pos_x, pos_y, page)
         VALUES (?, ?, ?, ?, ?, "open", ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
		$fpid, $uid, $uid, $title, $description, $taskType, 
        $roomNumber, $stampInitials, $rootCause, $startDate, $dueDate, 
        $posX, $posY, $page
	]);
	
	$tid = (int) $pdo->lastInsertId();

    // Insert task assignees into junction table task_users
    if (count($assignees) > 0) {
        $stmtAssign = $pdo->prepare('INSERT INTO task_users (tid, uid) VALUES (?, ?)');
        foreach ($assignees as $assigneeUid) {
            if ($assigneeUid > 0) {
                $stmtAssign->execute([$tid, $assigneeUid]);
            }
        }
    }

    $pdo->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}