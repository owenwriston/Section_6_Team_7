<?php
require_once __DIR__ . '/upload-fp.php';

//validates create project form for db
function create_project(PDO $pdo, int $ownerUid, array $in, array $files): string
{
    $name     = trim($in['name'] ?? '');
    $location = trim($in['location'] ?? '');

    if ($name === '' || $location === '') {
        return 'Project name and location are required.';
    }
    if (mb_strlen($name) > 255 || mb_strlen($location) > 255) {
        return 'Name and location must be 255 characters or fewer.';
    }

    $error = validate_floorplans($files);
    if ($error !== '') {
        return $error;
    }

    $assignees = array_values(array_unique(array_filter(
        array_map('intval', (array) ($in['assignees'] ?? [])),
        fn($id) => $id > 0 && $id !== $ownerUid
    )));

    if ($assignees) {
        $marks = implode(',', array_fill(0, count($assignees), '?'));
        $stmt  = $pdo->prepare("SELECT COUNT(*) FROM users WHERE uid IN ($marks)");
        $stmt->execute($assignees);
        if ((int) $stmt->fetchColumn() !== count($assignees)) {
            return 'One of the selected people no longer has an account. Reload the page and try again.';
        }
    }

    try {
        $saved = store_floorplans($files);
    } catch (RuntimeException $e) {
        return 'Could not save the uploaded files. Try again.';
    }

    try {
        $pdo->beginTransaction();

        $pdo->prepare('INSERT INTO projects (uid_owner, name, location) VALUES (?, ?, ?)')
            ->execute([$ownerUid, $name, $location]);
        $pid = (int) $pdo->lastInsertId();

        $insUser = $pdo->prepare('INSERT INTO project_users (pid, uid) VALUES (?, ?)');
        foreach ($assignees as $uid) {
            $insUser->execute([$pid, $uid]);
        }

        $insPlan = $pdo->prepare('INSERT INTO floorplans (pid, file_path) VALUES (?, ?)');
        foreach ($saved as $path) {
            $insPlan->execute([$pid, $path]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        delete_stored_floorplans($saved);   // do not leave orphan files behind
        throw $e;
    }

    return '';
}
