
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

$uid = (int) $_SESSION['uid'];
$tid = filter_var(
    $_POST['tid'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$tid || $tid <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid task ID'
    ]);
    exit;
}

try {
    // Verify the user's role.
    $stmt = $pdo->prepare(
        'SELECT permissions FROM users WHERE uid = ?'
    );
    $stmt->execute([$uid]);

    $role = strtolower(trim(
        (string) $stmt->fetchColumn()
    ));

    if (!in_array(
        $role,
        ['admin', 'project owner', 'contractor'],
        true
    )) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Permission denied'
        ]);
        exit;
    }

    $pdo->beginTransaction();

    // Find the task and its project.
    $stmt = $pdo->prepare(
        'SELECT t.tid, f.pid
         FROM tasks t
         JOIN floorplans f ON f.fpid = t.fpid
         WHERE t.tid = ?
         FOR UPDATE'
    );
    $stmt->execute([$tid]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        throw new RuntimeException('Task not found');
    }

    // Administrators may delete tasks across projects.
    // Other permitted roles must belong to the project.
    if ($role !== 'admin') {
        $stmt = $pdo->prepare(
            'SELECT 1
             FROM project_users
             WHERE pid = ? AND uid = ?
             LIMIT 1'
        );
        $stmt->execute([(int) $task['pid'], $uid]);

        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            throw new RuntimeException(
                'You do not have access to this project'
            );
        }
    }

    // Collect media paths before the database removes them.
    $stmt = $pdo->prepare(
        'SELECT file_path FROM attachments WHERE tid = ?'
    );
    $stmt->execute([$tid]);
    $filePaths = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Cascading foreign keys delete attachment and
    // task_users database records automatically.
    $stmt = $pdo->prepare(
        'DELETE FROM tasks WHERE tid = ?'
    );
    $stmt->execute([$tid]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Task deletion failed');
    }

    $pdo->commit();

    // Remove files only after the database deletion succeeds.
    // Restrict cleanup to our known upload directory.
    $uploadDir = realpath(
        __DIR__ . '/../uploads/task-media'
    );

    $cleanupFailed = false;

    if ($uploadDir !== false) {
        foreach ($filePaths as $path) {
            $filename = basename((string) $path);

            // Only accept generated upload filenames.
            if (!preg_match(
                '/^[a-f0-9]{32}\.(jpg|jpeg|png|gif|webp|mp4|webm|mov)$/i',
                $filename
            )) {
                continue;
            }

            $fullPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

            if (is_file($fullPath) && !unlink($fullPath)) {
                $cleanupFailed = true;
                error_log('Unable to remove attachment: ' . $fullPath);
            }
        }
    } elseif (!empty($filePaths)) {
        $cleanupFailed = true;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Task deleted successfully',
        'cleanup_warning' => $cleanupFailed
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Task deletion failed: ' . $e->getMessage());

    if (http_response_code() === 200) {
        http_response_code(500);
    }

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
