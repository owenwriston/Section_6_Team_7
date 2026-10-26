<?php
require_once __DIR__ . '/auth-permissions.php';

//Everyone assigned to a project through project_users.
function get_project_members(PDO $pdo, int $pid): array
{
    $stmt = $pdo->prepare(
        'SELECT u.uid, u.fname, u.lname, u.email, u.company, u.permissions, pu.assigned_date
           FROM project_users pu
           JOIN users u ON u.uid = pu.uid
          WHERE pu.pid = ?
          ORDER BY u.lname, u.fname'
    );
    $stmt->execute([$pid]);
    return $stmt->fetchAll();
}

//add users who are already not part of the project
function get_addable_users(PDO $pdo, int $pid, array $roles): array
{
    if (count($roles) === 0) {
        return [];
    }
    $marks = implode(',', array_fill(0, count($roles), '?'));
    $stmt  = $pdo->prepare(
        "SELECT u.uid, u.fname, u.lname, u.email, u.company, u.permissions
           FROM users u
          WHERE LOWER(TRIM(u.permissions)) IN ($marks)
            AND u.uid NOT IN (SELECT uid FROM project_users WHERE pid = ?)
            AND u.uid NOT IN (SELECT uid_owner FROM projects WHERE pid = ?)
          ORDER BY u.lname, u.fname"
    );
    $stmt->execute(array_merge($roles, [$pid, $pid]));
    return $stmt->fetchAll();
}

function clean_ids(array $raw): array
{
    return array_values(array_unique(array_filter(array_map('intval', $raw), fn($id) => $id > 0)));
}

function load_member_targets(PDO $pdo, int $actorUid, int $pid, array $ids): array
{
    $stmt = $pdo->prepare('SELECT permissions FROM users WHERE uid = ?');
    $stmt->execute([$actorUid]);
    $actor = $stmt->fetch();
    if (!$actor) {
        return ['Your account could not be found. Log in again.', [], 0, []];
    }

    $allowed = manageable_member_roles($pdo, $actorUid, $actor['permissions'], $pid);
    if (count($allowed) === 0) {
        return ["You can't manage members of this project.", [], 0, []];
    }

    $stmt = $pdo->prepare('SELECT uid_owner FROM projects WHERE pid = ?');
    $stmt->execute([$pid]);
    $owner = (int) $stmt->fetchColumn();

    $marks = implode(',', array_fill(0, count($ids), '?'));
    $stmt  = $pdo->prepare("SELECT uid, email, permissions FROM users WHERE uid IN ($marks)");
    $stmt->execute($ids);
    $targets = [];
    foreach ($stmt->fetchAll() as $r) {
        $targets[(int) $r['uid']] = $r;
    }
    return ['', $allowed, $owner, $targets];
}

Adds users to a project
function add_project_members(PDO $pdo, int $actorUid, int $pid, array $uids): array
{
    $ids = clean_ids($uids);
    if (count($ids) === 0) {
        return ['error' => 'Select at least one user to add.', 'count' => 0];
    }

    [$error, $allowed, $owner, $targets] = load_member_targets($pdo, $actorUid, $pid, $ids);
    if ($error !== '') {
        return ['error' => $error, 'count' => 0];
    }

    $marks = implode(',', array_fill(0, count($ids), '?'));
    $stmt  = $pdo->prepare("SELECT uid FROM project_users WHERE pid = ? AND uid IN ($marks)");
    $stmt->execute(array_merge([$pid], $ids));
    $already = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    $toAdd = [];
    foreach ($ids as $id) {
        if (!isset($targets[$id])) {
            return ['error' => 'A selected user no longer exists. Reload the page and try again.', 'count' => 0];
        }
        $email = $targets[$id]['email'];
        $role  = normalize_role($targets[$id]['permissions']);
        if ($id === $owner) {
            return ['error' => "$email owns this project already.", 'count' => 0];
        }
        if (!in_array($role, $allowed, true)) {
            return ['error' => "$email: you can only add " . implode(' or ', $allowed) . 's. Users with no role must be given one in Manage Users first.', 'count' => 0];
        }
        if (!in_array($id, $already, true)) {
            $toAdd[] = $id;
        }
    }

    $pdo->beginTransaction();
    try {
        $ins = $pdo->prepare('INSERT INTO project_users (pid, uid) VALUES (?, ?)');
        foreach ($toAdd as $id) {
            $ins->execute([$pid, $id]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    return ['error' => '', 'count' => count($toAdd)];
}

//removes users from a project, and clears their floorplan/task assignments
function remove_project_members(PDO $pdo, int $actorUid, int $pid, array $uids): array
{
    $ids = clean_ids($uids);
    if (count($ids) === 0) {
        return ['error' => 'Select at least one member to remove.', 'count' => 0];
    }

    [$error, $allowed, $owner, $targets] = load_member_targets($pdo, $actorUid, $pid, $ids);
    if ($error !== '') {
        return ['error' => $error, 'count' => 0];
    }

    $marks = implode(',', array_fill(0, count($ids), '?'));
    $stmt  = $pdo->prepare("SELECT uid FROM project_users WHERE pid = ? AND uid IN ($marks)");
    $stmt->execute(array_merge([$pid], $ids));
    $members = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    foreach ($ids as $id) {
        if (!isset($targets[$id]) || !in_array($id, $members, true)) {
            return ['error' => 'A selected user is not a member of this project. Reload the page and try again.', 'count' => 0];
        }
        $role = normalize_role($targets[$id]['permissions']);
        if ($id === $owner || !in_array($role, $allowed, true)) {
            return ['error' => $targets[$id]['email'] . ": you can't remove this member.", 'count' => 0];
        }
    }

    $pdo->beginTransaction();
    try {
        foreach ($ids as $id) {
            $pdo->prepare(
                'DELETE FROM task_users WHERE uid = ? AND tid IN
                   (SELECT t.tid FROM tasks t JOIN floorplans f ON f.fpid = t.fpid WHERE f.pid = ?)'
            )->execute([$id, $pid]);
            $pdo->prepare(
                'DELETE FROM floorplan_users WHERE uid = ? AND fpid IN
                   (SELECT fpid FROM floorplans WHERE pid = ?)'
            )->execute([$id, $pid]);
            $pdo->prepare('DELETE FROM project_users WHERE pid = ? AND uid = ?')->execute([$pid, $id]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    return ['error' => '', 'count' => count($ids)];
}
