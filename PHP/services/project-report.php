<?php
require_once __DIR__ . '/auth-permissions.php';

function report_person(?string $fname, ?string $lname, ?string $email): string
{
    $name = trim((string) $fname . ' ' . (string) $lname);
    return $name !== '' ? $name : (string) $email;
}

//Collect project information
function get_project_report(PDO $pdo, int $uid, ?string $permissions, int $pid): ?array
{
    if (!can_view_project($pdo, $uid, $permissions, $pid)) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT p.pid, p.name, p.location, p.status, p.create_date, p.last_update_date,
                u.fname, u.lname, u.email
           FROM projects p JOIN users u ON u.uid = p.uid_owner
          WHERE p.pid = ?'
    );
    $stmt->execute([$pid]);
    $p = $stmt->fetch();
    if (!$p) {
        return null;
    }
    $report = [
        'project' => [
            'pid'         => (int) $p['pid'],
            'name'        => $p['name'],
            'location'    => $p['location'],
            'status'      => $p['status'],
            'created'     => $p['create_date'],
            'updated'     => $p['last_update_date'],
            'owner'       => report_person($p['fname'], $p['lname'], $p['email']),
            'owner_email' => $p['email'],
        ],
        'members'    => [],
        'floorplans' => [],
        'counts'     => ['floorplans' => 0, 'tasks' => 0, 'attachments' => 0, 'by_status' => []],
    ];

    $stmt = $pdo->prepare(
        'SELECT u.fname, u.lname, u.email, u.company, u.permissions, pu.assigned_date
           FROM project_users pu JOIN users u ON u.uid = pu.uid
          WHERE pu.pid = ? ORDER BY u.lname, u.fname'
    );
    $stmt->execute([$pid]);
    foreach ($stmt->fetchAll() as $m) {
        $report['members'][] = [
            'name'     => report_person($m['fname'], $m['lname'], $m['email']),
            'email'    => $m['email'],
            'company'  => (string) $m['company'],
            'role'     => role_label(normalize_role($m['permissions'])),
            'assigned' => $m['assigned_date'],
        ];
    }

    $stmt = $pdo->prepare('SELECT fpid, upload_date FROM floorplans WHERE pid = ? ORDER BY fpid');
    $stmt->execute([$pid]);
    $plans = [];
    foreach ($stmt->fetchAll() as $i => $f) {
        $plans[(int) $f['fpid']] = [
            'number'   => $i + 1,
            'uploaded' => $f['upload_date'],
            'assigned' => [],
            'tasks'    => [],
        ];
    }

    $stmt = $pdo->prepare(
        'SELECT fu.fpid, u.fname, u.lname, u.email
           FROM floorplan_users fu
           JOIN floorplans f ON f.fpid = fu.fpid
           JOIN users u ON u.uid = fu.uid
          WHERE f.pid = ? ORDER BY u.lname, u.fname'
    );
    $stmt->execute([$pid]);
    foreach ($stmt->fetchAll() as $r) {
        $plans[(int) $r['fpid']]['assigned'][] = report_person($r['fname'], $r['lname'], $r['email']);
    }

    // Tasks
    $stmt = $pdo->prepare(
        'SELECT t.tid, t.fpid, t.title, t.description, t.status, t.task_type, t.room_number,
                t.stamp_initials, t.root_cause, t.start_date, t.due_date,
                t.pos_x, t.pos_y, t.page, t.create_date, t.last_update_date,
                c.fname AS cf, c.lname AS cl, c.email AS ce,
                e.fname AS ef, e.lname AS el, e.email AS ee
           FROM tasks t
           JOIN floorplans f ON f.fpid = t.fpid
           LEFT JOIN users c ON c.uid = t.uid_creator
           LEFT JOIN users e ON e.uid = t.uid_last_editor
          WHERE f.pid = ?
          ORDER BY t.fpid, t.page, t.tid'
    );
    $stmt->execute([$pid]);
    $tasks = [];
    foreach ($stmt->fetchAll() as $t) {
        $tid = (int) $t['tid'];
        $tasks[$tid] = [
            'tid'         => $tid,
            'title'       => $t['title'],
            'description' => (string) $t['description'],
            'status'      => $t['status'],
            'type'        => $t['task_type'],
            'room'        => (string) $t['room_number'],
            'initials'    => (string) $t['stamp_initials'],
            'root_cause'  => (string) $t['root_cause'],
            'start'       => $t['start_date'],
            'due'         => $t['due_date'],
            'x'           => $t['pos_x'],
            'y'           => $t['pos_y'],
            'page'        => (int) $t['page'],
            'created'     => $t['create_date'],
            'updated'     => $t['last_update_date'],
            'creator'     => $t['ce'] !== null ? report_person($t['cf'], $t['cl'], $t['ce']) : '',
            'editor'      => $t['ee'] !== null ? report_person($t['ef'], $t['el'], $t['ee']) : '',
            'assigned'    => [],
            'attachments' => [],
            'fpid'        => (int) $t['fpid'],
        ];
    }

    $stmt = $pdo->prepare(
        'SELECT tu.tid, u.fname, u.lname, u.email
           FROM task_users tu
           JOIN tasks t ON t.tid = tu.tid
           JOIN floorplans f ON f.fpid = t.fpid
           JOIN users u ON u.uid = tu.uid
          WHERE f.pid = ? ORDER BY u.lname, u.fname'
    );
    $stmt->execute([$pid]);
    foreach ($stmt->fetchAll() as $r) {
        $tasks[(int) $r['tid']]['assigned'][] = report_person($r['fname'], $r['lname'], $r['email']);
    }

    $stmt = $pdo->prepare(
        'SELECT a.tid, a.original_name, a.file_path, a.upload_date, u.fname, u.lname, u.email
           FROM attachments a
           JOIN tasks t ON t.tid = a.tid
           JOIN floorplans f ON f.fpid = t.fpid
           LEFT JOIN users u ON u.uid = a.uid_uploader
          WHERE f.pid = ? ORDER BY a.upload_date, a.aid'
    );
    $stmt->execute([$pid]);
    foreach ($stmt->fetchAll() as $a) {
        $name = (string) $a['original_name'] !== '' ? $a['original_name'] : $a['file_path'];
        $tasks[(int) $a['tid']]['attachments'][] = [
            'name'     => $name,
            'uploaded' => $a['upload_date'],
            'by'       => $a['email'] !== null ? report_person($a['fname'], $a['lname'], $a['email']) : '',
        ];
        $report['counts']['attachments']++;
    }

    foreach ($tasks as $t) {
        $plans[$t['fpid']]['tasks'][] = $t;
        $report['counts']['tasks']++;
        $report['counts']['by_status'][$t['status']] = ($report['counts']['by_status'][$t['status']] ?? 0) + 1;
    }
    $report['floorplans']           = array_values($plans);
    $report['counts']['floorplans'] = count($plans);

    return $report;
}
