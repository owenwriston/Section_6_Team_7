<?php
function get_user_projects(PDO $pdo, int $uid): array
{
    $stmt = $pdo->prepare(
        'SELECT DISTINCT p.pid, p.name, p.location, p.status
           FROM projects p
           LEFT JOIN project_users pu ON pu.pid = p.pid
          WHERE p.uid_owner = ? OR pu.uid = ?
          ORDER BY p.last_update_date DESC'
    );
    $stmt->execute([$uid, $uid]);
    return $stmt->fetchAll();
}
