<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
    ]);
    exit;
}

require __DIR__ . '/../services/db-connect.php';

$tid = filter_input(INPUT_GET, 'tid', FILTER_VALIDATE_INT);

if (!$tid || $tid <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid task ID'
    ]);
    exit;
}

try {
    // Confirm that the current user belongs to the task's project.
    $access = $pdo->prepare(
        'SELECT 1
        FROM tasks t
        JOIN floorplans f ON f.fpid = t.fpid
        JOIN users u ON u.uid = ?
        LEFT JOIN project_users pu
            ON pu.pid = f.pid AND pu.uid = u.uid
        WHERE t.tid = ?
        AND (
            LOWER(TRIM(u.permissions)) = "admin"
            OR pu.uid IS NOT NULL
        )
        LIMIT 1'
    );

    $access->execute([
        (int) $_SESSION['uid'],
        $tid
    ]);

    if (!$access->fetchColumn()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Task not found or access denied'
        ]);
        exit;
    }

    // Retrieve all attachments belonging to the selected task.
    $stmt = $pdo->prepare(
        'SELECT aid, file_path, original_name
         FROM attachments
         WHERE tid = ?
         ORDER BY upload_date ASC, aid ASC'
    );

    $stmt->execute([$tid]);

    echo json_encode([
        'success' => true,
        'attachments' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch (Throwable $e) {
    error_log('Attachment retrieval failed: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'Unable to retrieve attachments'
    ]);
}