<?php
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../services/db-connect.php';
require __DIR__ . '/../services/auth-permissions.php';
require __DIR__ . '/../services/edit-permissions.php';

$uid = (int) $_SESSION['uid'];

$stmt = $pdo->prepare('SELECT permissions FROM users WHERE uid = ?');
$stmt->execute([$uid]);
$me = $stmt->fetch();

if (!$me) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Server-side check: only Admins and Project Owners may open this page
if (!can_manage_users($me['permissions'])) {
    http_response_code(403);
    echo 'Only Admins and Project Owners can manage users. <a href="dashboard.php">Back to dashboard</a>';
    exit;
}

// CSRF token: stops other websites from submitting role changes for you
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $error = 'Your session expired. Reload the page and try again.';
    } else {
        $result = update_user_permissions($pdo, $uid, (array) ($_POST['role'] ?? []));
        if ($result['error'] === '') {
            header('Location: manage-users.php?updated=' . $result['count']);
            exit;
        }
        $error = $result['error'];
    }
}

$users = $pdo->query(
    'SELECT uid, fname, lname, email, company, `com-role` AS com_role, permissions
       FROM users ORDER BY lname, fname'
)->fetchAll();

$myRoles = assignable_roles($me['permissions']);
$updated = isset($_GET['updated']) ? (int) $_GET['updated'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PunchDash - Manage Users</title>
</head>
<body>
    <h1>Manage Users</h1>
    <p><a href="dashboard.php">Back to dashboard</a></p>

    <?php if ($error !== ''): ?>
        <p style="color:#b00020;"><?= htmlspecialchars($error) ?></p>
    <?php elseif ($updated !== null): ?>
        <p style="color:#2e7d32;">
            <?= $updated === 0 ? 'No changes to save.' : $updated . ($updated === 1 ? ' role updated.' : ' roles updated.') ?>
        </p>
    <?php endif; ?>

    <form method="post" action="manage-users.php">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

        <table border="1" cellpadding="6">
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Company</th>
                <th>Current role</th>
                <th>New role</th>
            </tr>
            <?php foreach ($users as $u): ?>
                <?php
                $fullName = trim($u['fname'] . ' ' . $u['lname']);
                $current  = normalize_role($u['permissions']);
                $editable = can_edit_user($me['permissions'], $u['permissions'], $uid, (int) $u['uid']);
                ?>
                <tr>
                    <td><?= htmlspecialchars($fullName) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars((string) $u['company']) ?></td>
                    <td><?= htmlspecialchars(role_label($current)) ?></td>
                    <td>
                        <?php if ($editable): ?>
                            <select name="role[<?= (int) $u['uid'] ?>]" aria-label="Role for <?= htmlspecialchars($fullName) ?>">
                                <?php foreach ($myRoles as $r): ?>
                                    <option value="<?= htmlspecialchars($r) ?>" <?= $current === $r ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(role_label($r)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ((int) $u['uid'] === $uid): ?>
                            You
                        <?php else: ?>
                            Admins only
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <p><button type="submit">Save changes</button></p>
    </form>
</body>
</html>
