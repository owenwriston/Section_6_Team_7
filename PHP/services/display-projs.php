<?php
//returns the projects a user can see, Admins see every project
function get_user_projects(PDO $pdo, int $uid, ?string $permissions = null): array
{
    if (strtolower(trim((string) $permissions)) === 'admin') {
        return $pdo->query(
            'SELECT pid, uid_owner, name, location, status
               FROM projects ORDER BY last_update_date DESC'
        )->fetchAll();
    }

    $stmt = $pdo->prepare(
        'SELECT DISTINCT p.pid, p.uid_owner, p.name, p.location, p.status, p.last_update_date
           FROM projects p
           LEFT JOIN project_users pu ON pu.pid = p.pid
          WHERE p.uid_owner = ? OR pu.uid = ?
          ORDER BY p.last_update_date DESC'
    );
    $stmt->execute([$uid, $uid]);
    return $stmt->fetchAll();
}
