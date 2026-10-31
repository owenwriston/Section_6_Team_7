<!-- Transparent layer over the canvas holding task circles -->
<div class="pin-overlay-layer" id="overlay-<?= $fpid ?>" data-fpid="<?= $fpid ?>">
    <?php $tasks = $tasksByPlan[$fpid] ?? []; ?>
    <?php foreach ($tasks as $t): ?>
        <?php 
        $posX = $t['pos_x'] !== null ? (float) $t['pos_x'] : 50.0;
        $posY = $t['pos_y'] !== null ? (float) $t['pos_y'] : 50.0;
        $page = (int) ($t['page'] ?? 1);
        
		// Populate stamp initials if they exist, otherwise use the task ID number
		$stamp = !empty($t['stamp_initials']) ? htmlspecialchars($t['stamp_initials']) : (int) $t['tid'];
        ?>
		
        <!-- Task Pin Circle Shape with Stamp Initials -->
        <div class="task-pin status-<?= htmlspecialchars($t['status']) ?>"
            data-page="<?= $page ?>"
			data-tid="<?= (int) $t['tid'] ?>"
            data-title="<?= htmlspecialchars($t['title']) ?>"
            data-description="<?= htmlspecialchars((string) $t['description']) ?>"
            data-status="<?= htmlspecialchars($t['status']) ?>"
            data-type="<?= htmlspecialchars($t['task_type']) ?>"
            data-room="<?= htmlspecialchars((string) $t['room_number']) ?>"
            data-stamp="<?= htmlspecialchars((string) $t['stamp_initials']) ?>"
            data-rootcause="<?= htmlspecialchars((string) $t['root_cause']) ?>"
            data-start="<?= htmlspecialchars((string) $t['start_date']) ?>"
            data-due="<?= htmlspecialchars((string) $t['due_date']) ?>"
            data-creator="<?= htmlspecialchars(trim($t['creator_fname'] . ' ' . $t['creator_lname'])) ?>"
            data-createdate="<?= htmlspecialchars($t['create_date']) ?>"
            data-editor="<?= htmlspecialchars(trim($t['editor_fname'] . ' ' . $t['editor_lname'])) ?>"
            data-lastupdate="<?= htmlspecialchars($t['last_update_date']) ?>"
            style="position: absolute; left: <?= $posX ?>%; top: <?= $posY ?>%; transform: translate(-50%, -50%); cursor: pointer;">
            <span><?= $stamp ?></span>
        </div>
    <?php endforeach; ?>
</div>