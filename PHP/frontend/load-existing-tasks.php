<!-- Transparent layer over the canvas holding task circles -->
<div class="pin-overlay-layer" id="overlay-<?= $fpid ?>" data-fpid="<?= $fpid ?>">
    <?php $tasks = $tasksByPlan[$fpid] ?? []; ?>
    <?php foreach ($tasks as $t): ?>
        <?php 
        $posX = $t['pos_x'] !== null ? (float) $t['pos_x'] : 50.0;
        $posY = $t['pos_y'] !== null ? (float) $t['pos_y'] : 50.0;
        $page = (int) ($t['page'] ?? 1);
        ?>
        <!-- Task Pin Circle Shape -->
        <div class="task-pin status-<?= htmlspecialchars($t['status']) ?>"
             data-page="<?= $page ?>"
             style="position: absolute; left: <?= $posX ?>%; top: <?= $posY ?>%; transform: translate(-50%, -50%); cursor: pointer;"
             title="<?= htmlspecialchars($t['title']) ?>"
             onclick="event.stopPropagation(); alert('Task: <?= htmlspecialchars(addslashes($t['title'])) ?>\nStatus: <?= htmlspecialchars($t['status']) ?>');">
            <span><?= (int) $t['tid'] ?></span>
        </div>
    <?php endforeach; ?>
</div>