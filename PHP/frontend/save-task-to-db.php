<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['uid'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
    ]);
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
    echo json_encode([
        'success' => false,
        'error' => 'Forbidden: You do not have permission to create tasks'
    ]);
    exit;
}

// ==========================================
// TASK FORM DATA
// ==========================================

$fpid = (int) ($_POST['fpid'] ?? 0);
$page = (int) ($_POST['page'] ?? 1);

$posX = isset($_POST['pos_x'])
    ? (float) $_POST['pos_x']
    : null;

$posY = isset($_POST['pos_y'])
    ? (float) $_POST['pos_y']
    : null;

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');

$taskType = $_POST['task_type'] ?? 'issue';
$roomNumber = trim($_POST['room_number'] ?? '');
$stampInitials = trim($_POST['stamp_initials'] ?? '');
$rootCause = trim($_POST['root_cause'] ?? '');

$startDate = !empty($_POST['start_date'])
    ? $_POST['start_date']
    : null;

$dueDate = !empty($_POST['due_date'])
    ? $_POST['due_date']
    : null;

$assignees = array_map(
    'intval',
    (array) ($_POST['assignees'] ?? [])
);

// ==========================================
// VALIDATION
// ==========================================

// Maximum two assignees
if (count($assignees) > 2) {
    echo json_encode([
        'success' => false,
        'error' => 'A task can have a maximum of two assignees'
    ]);
    exit;
}

// Required fields
if ($fpid <= 0 || $title === '') {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields'
    ]);
    exit;
}

// ==========================================
// MEDIA UPLOAD CONFIGURATION
// ==========================================

// Store paths for cleanup if something fails
$uploadedPaths = [];

// Directory for uploaded photos/videos
$uploadDir = __DIR__ . '/../uploads/task-media/';

// Allowed media types
$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    'video/mp4' => 'mp4',
    'video/webm' => 'webm',
    'video/quicktime' => 'mov'
];

// Maximum 20 MB per file
$maxFileSize = 20 * 1024 * 1024;

// Maximum 10 attachments per task
$maxFiles = 10;

// ==========================================
// CREATE TASK AND SAVE ATTACHMENTS
// ==========================================

try {

    // Validate uploaded files before creating the task
    $validatedFiles = [];

    if (isset($_FILES['media'])) {

        $files = $_FILES['media'];

        if (!is_array($files['name'])) {
            throw new Exception('Invalid media upload format');
        }

        $fileCount = count($files['name']);

        if ($fileCount > $maxFiles) {
            throw new Exception('Maximum 10 attachments per task');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);

        for ($i = 0; $i < $fileCount; $i++) {

            $error = $files['error'][$i];

            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($error !== UPLOAD_ERR_OK) {
                throw new Exception('File upload failed');
            }

            $tmpPath = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            $originalName = basename($files['name'][$i]);

            if ($fileSize <= 0 || $fileSize > $maxFileSize) {
                throw new Exception(
                    'Invalid file size: ' . $originalName
                );
            }

            if (!is_uploaded_file($tmpPath)) {
                throw new Exception('Invalid uploaded file');
            }

            $mimeType = $finfo->file($tmpPath);

            if (!isset($allowedTypes[$mimeType])) {
                throw new Exception(
                    'Unsupported file type: ' . $originalName
                );
            }

            if (strlen($originalName) > 255) {
                throw new Exception('Filename is too long');
            }

            $validatedFiles[] = [
                'tmp_path' => $tmpPath,
                'original_name' => $originalName,
                'extension' => $allowedTypes[$mimeType]
            ];
        }
    }

    // Start database transaction
    $pdo->beginTransaction();

    // ==========================================
    // INSERT TASK
    // ==========================================

    $stmt = $pdo->prepare(
        'INSERT INTO tasks (
            fpid,
            uid_creator,
            uid_last_editor,
            title,
            description,
            status,
            task_type,
            room_number,
            stamp_initials,
            root_cause,
            start_date,
            due_date,
            pos_x,
            pos_y,
            page
        )
        VALUES (
            ?, ?, ?, ?, ?, "open", ?, ?, ?, ?, ?, ?, ?, ?, ?
        )'
    );

    $stmt->execute([
        $fpid,
        $uid,
        $uid,
        $title,
        $description,
        $taskType,
        $roomNumber,
        $stampInitials,
        $rootCause,
        $startDate,
        $dueDate,
        $posX,
        $posY,
        $page
    ]);

    // Get newly created task ID
    $tid = (int) $pdo->lastInsertId();

    // ==========================================
    // INSERT TASK ASSIGNEES
    // ==========================================

    if (count($assignees) > 0) {

        $stmtAssign = $pdo->prepare(
            'INSERT INTO task_users (tid, uid)
             VALUES (?, ?)'
        );

        foreach ($assignees as $assigneeUid) {

            if ($assigneeUid > 0) {
                $stmtAssign->execute([
                    $tid,
                    $assigneeUid
                ]);
            }
        }
    }

    // ==========================================
    // SAVE PHOTOS AND VIDEOS
    // ==========================================

    if (count($validatedFiles) > 0) {

        // Create upload directory if needed
        if (!is_dir($uploadDir)) {

            if (!mkdir($uploadDir, 0755, true) &&
                !is_dir($uploadDir)) {

                throw new Exception(
                    'Unable to create upload directory'
                );
            }
        }

        // Prepare attachment database insert
        $stmtMedia = $pdo->prepare(
            'INSERT INTO attachments (
                tid,
                uid_uploader,
                file_path,
                original_name
            )
            VALUES (?, ?, ?, ?)'
        );

        foreach ($validatedFiles as $file) {

            // Generate unique filename
            $newFilename =
                bin2hex(random_bytes(16)) .
                '.' .
                $file['extension'];

            // Full destination path
            $destination = $uploadDir . $newFilename;

            // Move uploaded file to server
            if (!move_uploaded_file(
                $file['tmp_path'],
                $destination
            )) {

                throw new Exception(
                    'Unable to save uploaded file'
                );
            }

            // Remember file for cleanup
            $uploadedPaths[] = $destination;

            // Relative path stored in database
            $relativePath =
                'uploads/task-media/' . $newFilename;

            // Insert attachment record
            $stmtMedia->execute([
                $tid,
                $uid,
                $relativePath,
                $file['original_name']
            ]);
        }
    }

    // ==========================================
    // COMMIT TRANSACTION
    // ==========================================

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'tid' => $tid,
        'attachments_saved' => count($validatedFiles)
    ]);

} catch (Throwable $e) {

    // Roll back database changes
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Delete files created during failed upload
    foreach ($uploadedPaths as $path) {

        if (is_file($path)) {
            unlink($path);
        }
    }

    // Log detailed error on server
    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'Unable to save task or attachments'
    ]);
}