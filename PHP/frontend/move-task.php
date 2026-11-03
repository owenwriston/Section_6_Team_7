
<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require __DIR__ . '/../services/db-connect.php';

$uid = (int) $_SESSION['uid'];
$tid = filter_input(INPUT_POST, 'tid', FILTER_VALIDATE_INT);
$fpid = filter_input(INPUT_POST, 'fpid', FILTER_VALIDATE_INT);

$x = filter_input(INPUT_POST, 'pos_x', FILTER_VALIDATE_FLOAT);
$y = filter_input(INPUT_POST, 'pos_y', FILTER_VALIDATE_FLOAT);

if (!$tid || !$fpid || $x === false || $x === null ||
    $y === false || $y === null ||
    !is_finite($x) || !is_finite($y) ||
    $x < 0 || $x > 100 || $y < 0 || $y > 100) {

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid task position']);
    exit;
}

try {
    // Verify that the user can move tasks
    $stmt = $pdo->prepare(
        'SELECT permissions FROM users WHERE uid = ?'
    );
    $stmt->execute([$uid]);

    $role = strtolower(trim((string) $stmt->fetchColumn()));

    if (!in_array($role, ['admin', 'project owner', 'contractor'], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit;
    }

    // Verify that the task belongs to the specified floorplan
    // and that the user is authorized for the project.
    $stmt = $pdo->prepare(
        'SELECT t.tid
         FROM tasks t
         JOIN floorplans f ON f.fpid = t.fpid
         JOIN projects p ON p.pid = f.pid
         WHERE t.tid = ?
           AND t.fpid = ?
           AND (
               ? = "admin"
               OR p.uid_owner = ?
               OR EXISTS (
                   SELECT 1
                   FROM project_users pu
                   WHERE pu.pid = p.pid AND pu.uid = ?
               )
           )'
    );

    $stmt->execute([$tid, $fpid, $role, $uid, $uid]);

    if (!$stmt->fetchColumn()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Task not found or access denied'
        ]);
        exit;
    }

    // Save the new coordinates without changing the PDF page
    $stmt = $pdo->prepare(
        'UPDATE tasks
         SET pos_x = ?, pos_y = ?, uid_last_editor = ?
         WHERE tid = ? AND fpid = ?'
    );

    $stmt->execute([$x, $y, $uid, $tid, $fpid]);

    echo json_encode([
        'success' => true,
        'tid' => $tid,
        'pos_x' => $x,
        'pos_y' => $y
    ]);

} catch (Throwable $e) {
    error_log('Move task error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to save task position'
    ]);
}
