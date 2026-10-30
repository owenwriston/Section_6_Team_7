<?php $tasks = $tasksByPlan[$fpid] ?? []; ?>
<?php if (count($tasks) === 0): ?>
    <p>No tasks on this floorplan yet. Click anywhere on the floorplan to add one.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr><th>Task ID</th><th>Title</th><th>Status</th><th>Page</th><th>Description</th></tr>
        <?php foreach ($tasks as $t): ?>
            <tr>
                <td><?= (int) $t['tid'] ?></td>
                <td><?= htmlspecialchars($t['title']) ?></td>
                <td><?= htmlspecialchars(str_replace('_', ' ', $t['status'])) ?></td>
                <td><?= (int) $t['page'] ?></td>
                <td><?= htmlspecialchars((string) $t['description']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>