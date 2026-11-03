<?php
session_start();

//redirect to login page if user not logged in
if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../services/db-connect.php';
require __DIR__ . '/../services/display-projs.php';
require __DIR__ . '/../services/auth-permissions.php';

$uid = (int) $_SESSION['uid'];

//destroy session if uid does not exist
$stmt = $pdo->prepare('SELECT fname, lname, permissions FROM users WHERE uid = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

//CSRF token
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

//error mesage for delelte project
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

// Filters from the query string (validated inside the service)
$filters       = normalize_project_filters($_GET);
$filtersActive = project_filters_active($filters);
$projects      = get_user_projects($pdo, $uid, $user['permissions'], $filters);

$canDeleteAny = false;
foreach ($projects as $p) {
    if (can_delete_project($user['permissions'], $uid, (int) $p['uid_owner'])) {
        $canDeleteAny = true;
        break;
    }
}
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

    <?php if ($flashError !== ''): ?>
        <p style="color:#b00020;"><?= htmlspecialchars($flashError) ?></p>
    <?php elseif (isset($_GET['created'])): ?>
        <p style="color:#2e7d32;">Project created.</p>
    <?php elseif (isset($_GET['deleted'])): ?>
        <p style="color:#2e7d32;">Project deleted.</p>
    <?php endif; ?>

    <h2>Your Projects</h2>

    <?php if (can_create_project($user['permissions'])): ?>
        <p><a href="create-project.php">Create New Project</a></p>
    <?php endif; ?>

    <?php if (can_manage_users($user['permissions'])): ?>
        <p><a href="manage-users.php">Manage Users</a></p>
    <?php endif; ?>

    <form method="get" action="dashboard.php">
        <label>Search
            <input type="search" name="q" value="<?= htmlspecialchars($filters['q']) ?>" maxlength="100"
                   placeholder="Name or location">
        </label>
        <label>Status
            <select name="status">
                <option value="">All</option>
                <?php foreach (PROJECT_STATUSES as $st): ?>
                    <option value="<?= $st ?>" <?= $filters['status'] === $st ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $st))) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Show
            <select name="scope">
                <option value="all"      <?= $filters['scope'] === 'all' ? 'selected' : '' ?>>All my projects</option>
                <option value="owned"    <?= $filters['scope'] === 'owned' ? 'selected' : '' ?>>Projects I own</option>
                <option value="assigned" <?= $filters['scope'] === 'assigned' ? 'selected' : '' ?>>Assigned to me</option>
            </select>
        </label>
        <label>Sort by
            <select name="sort">
                <option value="recent" <?= $filters['sort'] === 'recent' ? 'selected' : '' ?>>Recently updated</option>
                <option value="oldest" <?= $filters['sort'] === 'oldest' ? 'selected' : '' ?>>Oldest first</option>
                <option value="name"   <?= $filters['sort'] === 'name' ? 'selected' : '' ?>>Name (A-Z)</option>
                <option value="status" <?= $filters['sort'] === 'status' ? 'selected' : '' ?>>Status</option>
            </select>
        </label>
        <button type="submit">Apply</button>
        <?php if ($filtersActive || $filters['sort'] !== 'recent'): ?>
            <a href="dashboard.php">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (count($projects) === 0): ?>
        <p><?= $filtersActive ? 'No projects match these filters.' : 'You have no projects yet.' ?></p>
    <?php else: ?>
        <p><?= count($projects) ?> project<?= count($projects) === 1 ? '' : 's' ?><?= $filtersActive ? ' found' : '' ?></p>
        <table border="1" cellpadding="6">
            <tr>
                <th>Name</th>
                <th>Location</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($projects as $p): ?>
                <?php
                $title  = $p['name'] !== '' ? $p['name'] : '(Untitled)';
                $canDel = can_delete_project($user['permissions'], $uid, (int) $p['uid_owner']);
                $confirm = 'Delete "' . $title . '"? This permanently deletes its floorplans, tasks and attachments and cannot be undone.';
                ?>
                <tr>
                    <td><a href="project-display.php?pid=<?= (int) $p['pid'] ?>"><?= htmlspecialchars($title) ?></a></td>
                    <td><?= htmlspecialchars($p['location']) ?></td>
                    <td><?= htmlspecialchars(str_replace('_', ' ', $p['status'])) ?></td>
                    <td>
                        <a href="export-project-pdf.php?pid=<?= (int) $p['pid'] ?>">Export PDF</a>
                        <?php if ($canDel): ?>
                            <form method="post" action="delete-project.php" style="display:inline;"
                                  onsubmit="return confirm(<?= htmlspecialchars(json_encode($confirm), ENT_QUOTES) ?>);">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                                <input type="hidden" name="pid" value="<?= (int) $p['pid'] ?>">
                                <button type="submit">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>
