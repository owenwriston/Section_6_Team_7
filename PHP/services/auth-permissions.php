<?php
// Backend service: role checks based on the users.permissions column.
// Roles are stored as these lowercase values. A NULL/empty value means "no role yet".
const CREATE_PROJECT_ROLES = ['admin', 'project owner'];
const MANAGE_USER_ROLES    = ['admin', 'project owner'];

function normalize_role(?string $permissions): string
{
    return strtolower(trim((string) $permissions));
}

function role_label(string $role): string
{
    return $role === '' ? 'No role' : ucwords($role);
}

function can_create_project(?string $permissions): bool
{
    return in_array(normalize_role($permissions), CREATE_PROJECT_ROLES, true);
}

// Who may open the Manage Users page
function can_manage_users(?string $permissions): bool
{
    return in_array(normalize_role($permissions), MANAGE_USER_ROLES, true);
}

// Roles the actor is allowed to hand out ('' = remove the role).
// Only Admins can make someone a Project Owner (or another Admin).
function assignable_roles(?string $actorPermissions): array
{
    switch (normalize_role($actorPermissions)) {
        case 'admin':
            return ['admin', 'project owner', 'contractor', 'subcontractor', ''];
        case 'project owner':
            return ['contractor', 'subcontractor', ''];
        default:
            return [];
    }
}

// May the actor change this particular user's role at all?
function can_edit_user(?string $actorPermissions, ?string $targetPermissions, int $actorUid, int $targetUid): bool
{
    if ($actorUid === $targetUid) {
        return false;   // nobody edits their own role (prevents locking out the last Admin)
    }
    $actor  = normalize_role($actorPermissions);
    $target = normalize_role($targetPermissions);

    if ($actor === 'admin') {
        return true;
    }
    if ($actor === 'project owner') {
        // Project Owners cannot touch Admins or other Project Owners
        return !in_array($target, ['admin', 'project owner'], true);
    }
    return false;
}

// Admins can delete any project; a Project Owner can delete only projects they own.
function can_delete_project(?string $permissions, int $actorUid, int $ownerUid): bool
{
    $role = normalize_role($permissions);
    if ($role === 'admin') {
        return true;
    }
    return $role === 'project owner' && $actorUid === $ownerUid;
}

// Admins can open any project. Everyone else needs to own it or be assigned to it.
function can_view_project(PDO $pdo, int $uid, ?string $permissions, int $pid): bool
{
    if (normalize_role($permissions) === 'admin') {
        $stmt = $pdo->prepare('SELECT 1 FROM projects WHERE pid = ?');
        $stmt->execute([$pid]);
        return (bool) $stmt->fetchColumn();
    }
    $stmt = $pdo->prepare(
        'SELECT 1 FROM projects p
           LEFT JOIN project_users pu ON pu.pid = p.pid AND pu.uid = ?
          WHERE p.pid = ? AND (p.uid_owner = ? OR pu.uid IS NOT NULL)
          LIMIT 1'
    );
    $stmt->execute([$uid, $pid, $uid]);
    return (bool) $stmt->fetchColumn();
}

// Roles of the people this actor may add to / remove from a project's member list.
// Admin: Contractors and Subcontractors on any project.
// Project Owner: Contractors and Subcontractors, only on projects they own.
// Contractor: Subcontractors only, and only on projects they are assigned to.
// Anyone else: nobody (empty list).
function manageable_member_roles(PDO $pdo, int $actorUid, ?string $actorPermissions, int $pid): array
{
    $stmt = $pdo->prepare('SELECT uid_owner FROM projects WHERE pid = ?');
    $stmt->execute([$pid]);
    $owner = $stmt->fetchColumn();
    if ($owner === false) {
        return [];   // no such project
    }

    switch (normalize_role($actorPermissions)) {
        case 'admin':
            return ['contractor', 'subcontractor'];
        case 'project owner':
            return ((int) $owner === $actorUid) ? ['contractor', 'subcontractor'] : [];
        case 'contractor':
            return can_view_project($pdo, $actorUid, $actorPermissions, $pid) ? ['subcontractor'] : [];
        default:
            return [];
    }
}
