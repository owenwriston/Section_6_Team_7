<?php
//display user projects w/ filters

const PROJECT_STATUSES = ['active', 'on_hold', 'completed', 'archived'];
const PROJECT_SCOPES   = ['all', 'owned', 'assigned'];
const PROJECT_SORTS    = [
    'recent' => 'p.last_update_date DESC, p.pid DESC',
    'oldest' => 'p.create_date ASC, p.pid ASC',
    'name'   => 'p.name ASC, p.pid ASC',
    'status' => 'p.status ASC, p.name ASC, p.pid ASC',
];

// Cleans raw $_GET values into a safe filter set. Anything unrecognised falls back to a default.
function normalize_project_filters(array $raw): array
{
    // Ignore anything that is not a plain string (e.g. ?q[]=x)
    $str    = fn($k, $default) => (isset($raw[$k]) && is_string($raw[$k])) ? $raw[$k] : $default;
    $q      = trim($str('q', ''));
    $status = $str('status', '');
    $scope  = $str('scope', 'all');
    $sort   = $str('sort', 'recent');

    return [
        'q'      => mb_substr($q, 0, 100),
        'status' => in_array($status, PROJECT_STATUSES, true) ? $status : '',
        'scope'  => in_array($scope, PROJECT_SCOPES, true) ? $scope : 'all',
        'sort'   => array_key_exists($sort, PROJECT_SORTS) ? $sort : 'recent',
    ];
}

// =True when filter differs from default
function project_filters_active(array $f): bool
{
    return $f['q'] !== '' || $f['status'] !== '' || $f['scope'] !== 'all';
}

function get_user_projects(PDO $pdo, int $uid, ?string $permissions = null, array $filters = []): array
{
    $f = normalize_project_filters($filters);

    $where  = [];
    $params = [];

    if (strtolower(trim((string) $permissions)) !== 'admin') {
        $where[]  = '(p.uid_owner = ? OR EXISTS (SELECT 1 FROM project_users pu WHERE pu.pid = p.pid AND pu.uid = ?))';
        $params[] = $uid;
        $params[] = $uid;
    }

    //Text search
    if ($f['q'] !== '') {
        $like     = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $f['q']) . '%';
        $where[]  = "(p.name LIKE ? ESCAPE '!' OR p.location LIKE ? ESCAPE '!')";
        $params[] = $like;
        $params[] = $like;
    }

    if ($f['status'] !== '') {
        $where[]  = 'p.status = ?';
        $params[] = $f['status'];
    }

    if ($f['scope'] === 'owned') {
        $where[]  = 'p.uid_owner = ?';
        $params[] = $uid;
    } elseif ($f['scope'] === 'assigned') {
        $where[]  = 'EXISTS (SELECT 1 FROM project_users pu2 WHERE pu2.pid = p.pid AND pu2.uid = ?)';
        $params[] = $uid;
    }

    $sql = 'SELECT p.pid, p.uid_owner, p.name, p.location, p.status, p.last_update_date
              FROM projects p'
         . (count($where) ? ' WHERE ' . implode(' AND ', $where) : '')
         . ' ORDER BY ' . PROJECT_SORTS[$f['sort']];

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
