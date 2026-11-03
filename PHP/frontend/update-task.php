
<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require __DIR__ . '/../services/db-connect.php';

$uid = (int) $_SESSION['uid'];

try {
    // Verify permissions
    $stmt = $pdo->prepare(
        'SELECT permissions FROM users WHERE uid = ?'
    );
    $stmt->execute([$uid]);

    $role = strtolower(trim((string) $stmt->fetchColumn()));

    if (!in_array($role, ['admin', 'project owner', 'contractor'], true)) {
        http_response_code(403);
        throw new Exception('Permission denied');
    }

    // Read form data
    $tid = (int) ($_POST['tid'] ?? 0);
    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $status = (string) ($_POST['status'] ?? 'open');
    $taskType = (string) ($_POST['task_type'] ?? 'issue');
    $roomNumber = trim((string) ($_POST['room_number'] ?? ''));
    $stampInitials = trim((string) ($_POST['stamp_initials'] ?? ''));
    $rootCause = trim((string) ($_POST['root_cause'] ?? ''));

    $startDate = !empty($_POST['start_date'])
        ? $_POST['start_date']
        : null;

    $dueDate = !empty($_POST['due_date'])
        ? $_POST['due_date']
        : null;

    // Validate
    if ($tid <= 0 || $title === '') {
        http_response_code(400);
        throw new Exception('Task ID and title are required');
    }

    if (!in_array($status, ['open', 'in_review', 'pending', 'closed'], true)) {
        http_response_code(400);
        throw new Exception('Invalid task status');
    }

    if (!in_array($taskType, ['issue', 'planned_work', 'other'], true)) {
        http_response_code(400);
        throw new Exception('Invalid task type');
    }

    // Check task exists and user is authorized to edit it
    $stmt = $pdo->prepare(
        'SELECT t.tid
        FROM tasks t
        JOIN floorplans f ON f.fpid = t.fpid
        JOIN projects p ON p.pid = f.pid
        WHERE t.tid = ?
        AND (
            (? = "admin")
            OR p.uid_owner = ?
            OR EXISTS (
                SELECT 1 FROM project_users pu
                WHERE pu.pid = p.pid AND pu.uid = ?
            )
        )'
    );

    $stmt->execute([$tid, $role, $uid, $uid]);

    if (!$stmt->fetch()) {
        http_response_code(403);
        throw new Exception('Task not found or access denied');
    }

    // Update the task
    $stmt = $pdo->prepare(
        'UPDATE tasks SET
            title = ?,
            description = ?,
            status = ?,
            task_type = ?,
            room_number = ?,
            stamp_initials = ?,
            root_cause = ?,
            start_date = ?,
            due_date = ?,
            uid_last_editor = ?,
            last_update_date = NOW()
         WHERE tid = ?'
    );

    $stmt->execute([
        $title,
        $description,
        $status,
        $taskType,
        $roomNumber,
        $stampInitials,
        $rootCause,
        $startDate,
        $dueDate,
        $uid,
        $tid
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Task updated successfully',
        'tid' => $tid
    ]);

} catch (Throwable $e) {
    if (http_response_code() < 400) {
        http_response_code(500);
    }

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
