<?php
require_once __DIR__ . '/auth-permissions.php';

//changes user role and stores in DB
function update_user_permission(PDO $pdo, int $actorUid, int $targetUid, string $newRole): string
{
    $stmt = $pdo->prepare('SELECT uid, permissions FROM users WHERE uid IN (?, ?)');
    $stmt->execute([$actorUid, $targetUid]);
    $rows = [];
    foreach ($stmt->fetchAll() as $r) {
        $rows[(int) $r['uid']] = $r;
    }

    if (!isset($rows[$actorUid])) {
        return 'Your account could not be found. Log in again.';
    }
    if (!isset($rows[$targetUid])) {
        return 'That user no longer exists.';
    }

    $actor  = $rows[$actorUid]['permissions'];
    $target = $rows[$targetUid]['permissions'];

    if (!can_edit_user($actor, $target, $actorUid, $targetUid)) {
        return "You can't change this user's role.";
    }

    $role = normalize_role($newRole);
    if (!in_array($role, assignable_roles($actor), true)) {
        return "You can't assign that role. Only Admins can add Project Owners and Admins.";
    }

    $pdo->prepare('UPDATE users SET permissions = ? WHERE uid = ?')
        ->execute([$role === '' ? null : $role, $targetUid]);

    return '';
}

function update_user_permissions(PDO $pdo, int $actorUid, array $roles): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', array_keys($roles)), fn($id) => $id > 0)));
    if (count($ids) === 0) {
        return ['error' => '', 'count' => 0];
    }

    $marks = implode(',', array_fill(0, count($ids), '?'));
    $stmt  = $pdo->prepare("SELECT uid, email, permissions FROM users WHERE uid IN ($marks)");
    $stmt->execute($ids);
    $current = [];
    foreach ($stmt->fetchAll() as $r) {
        $current[(int) $r['uid']] = $r;
    }

    $count = 0;
    $pdo->beginTransaction();
    try {
        foreach ($ids as $id) {
            if (!isset($current[$id])) {
                $pdo->rollBack();
                return ['error' => 'A user in the list no longer exists. Reload the page and try again.', 'count' => 0];
            }
            $newRole = normalize_role((string) $roles[$id]);
            if ($newRole === normalize_role($current[$id]['permissions'])) {
                continue;   // nothing changed for this user
            }
            $error = update_user_permission($pdo, $actorUid, $id, $newRole);
            if ($error !== '') {
                $pdo->rollBack();
                return ['error' => $current[$id]['email'] . ': ' . $error . ' No changes were saved.', 'count' => 0];
            }
            $count++;
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    return ['error' => '', 'count' => $count];
}
