<?php
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../services/db-connect.php';
require __DIR__ . '/../services/auth-permissions.php';
require __DIR__ . '/../services/new-project.php';

$uid = (int) $_SESSION['uid'];

$stmt = $pdo->prepare('SELECT permissions FROM users WHERE uid = ?');
$stmt->execute([$uid]);
$me = $stmt->fetch();

if (!$me) {
    session_destroy();
    header('Location: login.php');
    exit;
}

//prevent unauthorized users from accessing create project, even option if hidden
if (!can_create_project($me['permissions'])) {
    http_response_code(403);
    echo 'Only Admins and Project Owners can create projects. <a href="dashboard.php">Back to dashboard</a>';
    exit;
}

$error    = '';
$form     = ['name' => '', 'location' => ''];
$selected = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //end this session if file is too large
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $error = 'The upload is larger than the server allows. Try fewer or smaller PDFs.';
    } else {
        $form['name']     = trim($_POST['name'] ?? '');
        $form['location'] = trim($_POST['location'] ?? '');
        $selected         = array_map('intval', (array) ($_POST['assignees'] ?? []));

        $files = normalize_uploads($_FILES['floorplans'] ?? []);
        $error = create_project($pdo, $uid, $_POST, $files);

        if ($error === '') {
            header('Location: dashboard.php?created=1');
            exit;
        }
    }
}

// People who can be assigned
$stmt = $pdo->prepare(
    'SELECT uid, fname, lname, email, company FROM users WHERE uid <> ? ORDER BY lname, fname'
);
$stmt->execute([$uid]);
$people = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PunchDash - Create Project</title>
</head>
<body>
    <h1>Create New Project</h1>

    <?php if ($error !== ''): ?>
        <p style="color:#b00020;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="create-project.php" enctype="multipart/form-data">
        <label>Project name
            <input type="text" name="name" value="<?= htmlspecialchars($form['name']) ?>" maxlength="255" required>
        </label><br>
        <label>Location
            <input type="text" name="location" value="<?= htmlspecialchars($form['location']) ?>" maxlength="255" required>
        </label><br>
        <label>Floorplan PDFs
            <input type="file" name="floorplans[]" accept="application/pdf,.pdf" multiple required>
        </label><br>

        <fieldset>
            <legend>Assign people to this project</legend>
            <?php if (count($people) === 0): ?>
                <p>No other users have accounts yet.</p>
            <?php else: ?>
                <?php foreach ($people as $p): ?>
                    <label>
                        <input type="checkbox" name="assignees[]" value="<?= (int) $p['uid'] ?>"
                            <?= in_array((int) $p['uid'], $selected, true) ? 'checked' : '' ?>>
                        <?= htmlspecialchars(trim($p['fname'] . ' ' . $p['lname'])) ?>
                        (<?= htmlspecialchars($p['email']) ?><?= $p['company'] ? ', ' . htmlspecialchars($p['company']) : '' ?>)
                    </label><br>
                <?php endforeach; ?>
            <?php endif; ?>
        </fieldset>

        <button type="submit">Create project</button>
        <a href="dashboard.php">Cancel</a>
    </form>
</body>
</html>
