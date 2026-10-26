<?php
require_once __DIR__ . '/auth-permissions.php';
require_once __DIR__ . '/upload-fp.php';

const ATTACHMENT_DIR = __DIR__ . '/../storage/attachments';

//delete project
function delete_project(PDO $pdo, int $actorUid, int $pid): string
{
    $stmt = $pdo->prepare('SELECT permissions FROM users WHERE uid = ?');
    $stmt->execute([$actorUid]);
    $actor = $stmt->fetch();
    if (!$actor) {
        return 'Your account could not be found. Log in again.';
    }

    $stmt = $pdo->prepare('SELECT uid_owner FROM projects WHERE pid = ?');
    $stmt->execute([$pid]);
    $project = $stmt->fetch();
    if (!$project) {
        return 'That project no longer exists.';
    }

    if (!can_delete_project($actor['permissions'], $actorUid, (int) $project['uid_owner'])) {
        return "You can't delete this project. Admins can delete any project, and Project Owners can delete their own.";
    }

    // Remember the files now; the rows disappear with the project
    $stmt = $pdo->prepare('SELECT file_path FROM floorplans WHERE pid = ?');
    $stmt->execute([$pid]);
    $planFiles = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare(
        'SELECT a.file_path FROM attachments a
           JOIN tasks t ON t.tid = a.tid
           JOIN floorplans f ON f.fpid = t.fpid
          WHERE f.pid = ?'
    );
    $stmt->execute([$pid]);
    $attachFiles = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM projects WHERE pid = ?')->execute([$pid]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    // Files last, so a failed delete never leaves a project pointing at missing files
    delete_stored_floorplans(array_map('basename', $planFiles));
    foreach ($attachFiles as $f) {
        @unlink(ATTACHMENT_DIR . '/' . basename($f));
    }
    return '';
}
