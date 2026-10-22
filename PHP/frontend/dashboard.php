<?php
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../services/db-connect.php';
require __DIR__ . '/../services/display-projs.php';
require __DIR__ . '/../services/auth-permissions.php';

$uid = (int) $_SESSION['uid'];

//exit if user no longer exists
$stmt = $pdo->prepare('SELECT fname, lname, permissions FROM users WHERE uid = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$projects = get_user_projects($pdo, $uid);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PunchDash - Dashboard</title>
</head>
<body>
    <h1>PunchDash</h1>

    <p>
        Welcome, <?= htmlspecialchars(trim($user['fname'] . ' ' . $user['lname'])) ?>
    </p>

    <form method="post" action="logout.php">
        <button type="submit">Log Out</button>
    </form>

    <h2>Your Projects</h2>

    <?php if (can_create_project($user['permissions'])): ?>
        <p><a href="create-project.php">Create New Project</a></p>
    <?php endif; ?>

    <?php if (count($projects) === 0): ?>
        <p>You have no projects yet.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>Name</th>
                <th>Location</th>
                <th>Status</th>
            </tr>
            <?php foreach ($projects as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name'] !== '' ? $p['name'] : '(Untitled)') ?></td>
                    <td><?= htmlspecialchars($p['location']) ?></td>
                    <td><?= htmlspecialchars(str_replace('_', ' ', $p['status'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>
