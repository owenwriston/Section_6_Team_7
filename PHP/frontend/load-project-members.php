<?php
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../services/db-connect.php';
require __DIR__ . '/../services/auth-permissions.php';
require __DIR__ . '/../services/edit-project-members.php';

$uid = (int) $_SESSION['uid'];
$pid = (int) ($_POST['pid'] ?? $_GET['pid'] ?? 0);

$stmt = $pdo->prepare('SELECT permissions FROM users WHERE uid = ?');
$stmt->execute([$uid]);
$me = $stmt->fetch();
if (!$me) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if ($pid <= 0 || !can_view_project($pdo, $uid, $me['permissions'], $pid)) {
    http_response_code(404);
    echo 'Project not found. <a href="dashboard.php">Back to dashboard</a>';
    exit;
}

// Server-side check: who may manage members of THIS project
$roles = manageable_member_roles($pdo, $uid, $me['permissions'], $pid);
if (count($roles) === 0) {
    http_response_code(403);
    echo "You can't manage members of this project. <a href=\"project-display.php?pid=$pid\">Back to project</a>";
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $error = 'Your session expired. Reload the page and try again.';
    } else {
        $uids   = (array) ($_POST['uids'] ?? []);
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            $r = add_project_members($pdo, $uid, $pid, $uids);
            $param = 'added';
        } elseif ($action === 'remove') {
            $r = remove_project_members($pdo, $uid, $pid, $uids);
            $param = 'removed';
        } else {
            $r = ['error' => 'Unknown action.', 'count' => 0];
            $param = '';
        }
        if ($r['error'] === '') {
            header("Location: load-project-members.php?pid=$pid&$param=" . $r['count']);
            exit;
        }
        $error = $r['error'];
    }
}

$stmt = $pdo->prepare(
    'SELECT p.name, u.fname, u.lname
       FROM projects p JOIN users u ON u.uid = p.uid_owner WHERE p.pid = ?'
);
$stmt->execute([$pid]);
$project = $stmt->fetch();
$title   = $project['name'] !== '' ? $project['name'] : '(Untitled)';

$members = get_project_members($pdo, $pid);
$addable = get_addable_users($pdo, $pid, $roles);

$removable = 0;
foreach ($members as $m) {
    if (in_array(normalize_role($m['permissions']), $roles, true)) {
        $removable++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PunchDash - Members of <?= htmlspecialchars($title) ?></title>
</head>
<body>
    <p><a href="project-display.php?pid=<?= $pid ?>">&larr; Back to project</a></p>

    <h1>Members of <?= htmlspecialchars($title) ?></h1>
    <p>Owner: <?= htmlspecialchars(trim($project['fname'] . ' ' . $project['lname'])) ?></p>

    <?php if ($error !== ''): ?>
        <p style="color:#b00020;"><?= htmlspecialchars($error) ?></p>
    <?php elseif (isset($_GET['added'])): ?>
        <p style="color:#2e7d32;"><?= (int) $_GET['added'] === 0 ? 'Nobody new was added.' : (int) $_GET['added'] . ' added.' ?></p>
    <?php elseif (isset($_GET['removed'])): ?>
        <p style="color:#2e7d32;"><?= (int) $_GET['removed'] . ' removed.' ?></p>
    <?php endif; ?>

    <h2>Current members</h2>
    <?php if (count($members) === 0): ?>
        <p>No one has been added to this project yet.</p>
    <?php else: ?>
        <form method="post" action="load-project-members.php"
              onsubmit="return confirm('Remove the selected members? They will lose access to this project and its task assignments.');">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
            <input type="hidden" name="pid" value="<?= $pid ?>">
            <input type="hidden" name="action" value="remove">
            <table border="1" cellpadding="6">
                <tr>
                    <?php if ($removable > 0): ?><th>Remove</th><?php endif; ?>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Company</th>
                    <th>Role</th>
                </tr>
                <?php foreach ($members as $m): ?>
                    <?php
                    $role    = normalize_role($m['permissions']);
                    $canRemove = in_array($role, $roles, true);
                    $name    = trim($m['fname'] . ' ' . $m['lname']);
                    ?>
                    <tr>
                        <?php if ($removable > 0): ?>
                            <td>
                                <?php if ($canRemove): ?>
                                    <input type="checkbox" name="uids[]" value="<?= (int) $m['uid'] ?>"
                                           aria-label="Remove <?= htmlspecialchars($name) ?>">
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($name) ?></td>
                        <td><?= htmlspecialchars($m['email']) ?></td>
                        <td><?= htmlspecialchars((string) $m['company']) ?></td>
                        <td><?= htmlspecialchars(role_label($role)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php if ($removable > 0): ?>
                <p><button type="submit">Remove selected</button></p>
            <?php endif; ?>
        </form>
    <?php endif; ?>

    <h2>Add members</h2>
    <p>You can add: <?= htmlspecialchars(implode(', ', array_map(fn($r) => role_label($r) . 's', $roles))) ?>.</p>
    <?php if (count($addable) === 0): ?>
        <p>No one else is available to add.</p>
    <?php else: ?>
        <form method="post" action="load-project-members.php">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
            <input type="hidden" name="pid" value="<?= $pid ?>">
            <input type="hidden" name="action" value="add">
            <?php foreach ($addable as $a): ?>
                <label>
                    <input type="checkbox" name="uids[]" value="<?= (int) $a['uid'] ?>">
                    <?= htmlspecialchars(trim($a['fname'] . ' ' . $a['lname'])) ?>
                    (<?= htmlspecialchars($a['email']) ?>,
                    <?= htmlspecialchars(role_label(normalize_role($a['permissions']))) ?>)
                </label><br>
            <?php endforeach; ?>
            <p><button type="submit">Add selected</button></p>
        </form>
    <?php endif; ?>
</body>
</html>
