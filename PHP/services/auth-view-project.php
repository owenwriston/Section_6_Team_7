<?php
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../services/db-connect.php';
require __DIR__ . '/../services/auth-permissions.php';

$uid = (int) $_SESSION['uid'];
$pid = (int) ($_GET['pid'] ?? 0);

$stmt = $pdo->prepare('SELECT uid, lname, fname, permissions FROM users WHERE uid = ?');
$stmt->execute([$uid]);
$me = $stmt->fetch();
if (!$me) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Same message for "missing" and "not yours" so project ids can't be probed
if ($pid <= 0 || !can_view_project($pdo, $uid, $me['permissions'], $pid)) {
    http_response_code(404);
    echo 'Project not found. <a href="dashboard.php">Back to dashboard</a>';
    exit;
}

$stmt = $pdo->prepare(
    'SELECT p.name, p.location, p.status, p.create_date, p.last_update_date, u.fname, u.lname
       FROM projects p JOIN users u ON u.uid = p.uid_owner
      WHERE p.pid = ?'
);
$stmt->execute([$pid]);
$project = $stmt->fetch();

$stmt = $pdo->prepare('SELECT fpid, upload_date FROM floorplans WHERE pid = ? ORDER BY fpid');
$stmt->execute([$pid]);
$floorplans = $stmt->fetchAll();

// Determine role-based permissions for current project
$userRole = strtolower(trim((string) $me['permissions']));
$canCreateTasks = in_array($userRole, ['admin', 'project owner', 'contractor'], true);

// Fetch all project members to populate assignee selection options
$stmt = $pdo->prepare(
    'SELECT u.uid, u.fname, u.lname, u.permissions 
       FROM users u 
       JOIN project_users pu ON pu.uid = u.uid 
      WHERE pu.pid = ?'
);
$stmt->execute([$pid]);
$projectMembers = $stmt->fetchAll();

// Prepare SQL query to retrieve all task details
$stmt = $pdo->prepare(
    'SELECT t.tid, t.fpid, t.uid_creator, t.uid_last_editor, t.title, t.description, t.status, 
			t.task_type, t.room_number, t.stamp_initials, t.root_cause, t.start_date, t.due_date,
			t.page, t.pos_x, t.pos_y, t.create_date, t.last_update_date,
			u_c.fname AS creator_fname, u_c.lname AS creator_lname,
			u_e.fname as editor_fname, u_e.lname AS editor_lname,
			GROUP_CONCAT(tu.uid) AS assigned_uids
       FROM tasks t 
	   JOIN floorplans f ON f.fpid = t.fpid
  LEFT JOIN users u_c ON u_c.uid = t.uid_creator
  LEFT JOIN users u_e ON u_e.uid = t.uid_last_editor
  LEFT JOIN task_users tu ON tu.tid = t.tid
      WHERE f.pid = ? 
	  GROUP BY t.tid
	  ORDER BY t.fpid, t.page, t.tid'
);
$stmt->execute([$pid]);

$tasksByPlan = [];
foreach ($stmt->fetchAll() as $t) {
	// Initialize an empty list for assigned user IDs
	$assignedArray = [];
	
	// Check if MySQL returned a list of assigned user IDs 
	if (!empty($t['assigned_uids'])) {
        // Split the comma-separated text string into individual text pieces
        $rawUserIds = explode(',', $t['assigned_uids']);
        
        // Convert each string ID into an integer ID
        foreach ($rawUserIds as $idText) {
            $assignedArray[] = (int) $idText;
        }
    }
    
    // Subcontractors can only view tasks assigned to them
    if ($userRole === 'subcontractor' && !in_array($uid, $assignedArray, true)) {
        continue; // skip this task and move onto the next text
    }
    
	// Save assigned user IDs to task
    $t['assigned_list'] = $assignedArray;
    $tasksByPlan[(int) $t['fpid']][] = $t;
}

$title = $project['name'] !== '' ? $project['name'] : '(Untitled)';