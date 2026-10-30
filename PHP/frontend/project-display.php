<?php
require __DIR__ . '/../services/auth-view-project.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PunchDash - <?= htmlspecialchars($title) ?></title>
	
	<!-- Task Pin CSS stylesheet for styling pins on floorplan -->
    <link rel="stylesheet" href="style-of-floorplan-viewer.css">
	
    <!-- Mozilla PDF.js CDN library for client-side HTML canvas rendering -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
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
            <?php $fpid = (int) $f['fpid']; ?>
            <h3>Floorplan <?= $i + 1 ?></h3>
            <p>
                <a href="floorplan-file.php?fpid=<?= $fpid ?>" target="_blank" rel="noopener">Open PDF</a>
                (uploaded <?= htmlspecialchars($f['upload_date']) ?>)
            </p>

            <?php require __DIR__ . '/floorplan-display.php'; ?>
            <?php require __DIR__ . '/task-element-display.php'; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php require __DIR__ . '/load-new-tasks.php'; ?>
</body>
</html>