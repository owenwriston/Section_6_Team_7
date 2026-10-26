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

$stmt = $pdo->prepare('SELECT permissions FROM users WHERE uid = ?');
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

$stmt = $pdo->prepare(
    'SELECT t.fpid, t.title, t.description, t.status, t.page
       FROM tasks t JOIN floorplans f ON f.fpid = t.fpid
      WHERE f.pid = ? ORDER BY t.fpid, t.page, t.tid'
);
$stmt->execute([$pid]);
$tasksByPlan = [];
foreach ($stmt->fetchAll() as $t) {
    $tasksByPlan[(int) $t['fpid']][] = $t;
}

$title = $project['name'] !== '' ? $project['name'] : '(Untitled)';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PunchDash - <?= htmlspecialchars($title) ?></title>
</head>
<body>
    <p><a href="dashboard.php">&larr; Back to dashboard</a></p>

    <h1><?= htmlspecialchars($title) ?></h1>
    <p>
        Location: <?= htmlspecialchars($project['location']) ?><br>
        Status: <?= htmlspecialchars(str_replace('_', ' ', $project['status'])) ?><br>
        Owner: <?= htmlspecialchars(trim($project['fname'] . ' ' . $project['lname'])) ?><br>
        Created: <?= htmlspecialchars($project['create_date']) ?>
    </p>

    <?php if (count(manageable_member_roles($pdo, $uid, $me['permissions'], $pid)) > 0): ?>
        <p><a href="load-project-members.php?pid=<?= $pid ?>">Manage members</a></p>
    <?php endif; ?>

    <h2>Floorplans</h2>
    <?php if (count($floorplans) === 0): ?>
        <p>This project has no floorplans.</p>
    <?php else: ?>
        <?php foreach ($floorplans as $i => $f): ?>
            <h3>Floorplan <?= $i + 1 ?></h3>
            <p>
                <a href="floorplan-file.php?fpid=<?= (int) $f['fpid'] ?>" target="_blank" rel="noopener">Open PDF</a>
                (uploaded <?= htmlspecialchars($f['upload_date']) ?>)
            </p>

            <?php $tasks = $tasksByPlan[(int) $f['fpid']] ?? []; ?>
            <?php if (count($tasks) === 0): ?>
                <p>No tasks on this floorplan yet.</p>
            <?php else: ?>
                <table border="1" cellpadding="6">
                    <tr><th>Task</th><th>Status</th><th>Page</th><th>Description</th></tr>
                    <?php foreach ($tasks as $t): ?>
                        <tr>
                            <td><?= htmlspecialchars($t['title']) ?></td>
                            <td><?= htmlspecialchars(str_replace('_', ' ', $t['status'])) ?></td>
                            <td><?= (int) $t['page'] ?></td>
                            <td><?= htmlspecialchars((string) $t['description']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
