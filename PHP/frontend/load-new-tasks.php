<!-- Overlay Form for Task Creation (Visible only to Admin, Owner, Contractor) -->
<?php if ($canCreateTasks): ?>
<div id="create-task-window" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:20px; border-radius:5px; width:300px;">
        <h3>Create New Task</h3>
        <form id="create-task-form">
            <input type="hidden" id="task-fpid" name="fpid">
            <input type="hidden" id="task-page" name="page">
            <input type="hidden" id="task-posx" name="pos_x">
            <input type="hidden" id="task-posy" name="pos_y">
            
            <label>Title <span style="color:red;">*</span>:<br>
				<input type="text" id="task-title" name="title" required style="width:100%;">
			</label><br><br>
            <label>Description:<br><textarea id="task-desc" name="description" style="width:100%;"></textarea></label><br><br>
            
			<label>Task Type:<br>
                <select name="task_type" style="width:100%;">
                    <option value="issue">Issue</option>
                    <option value="planned_work">Planned Work</option>
                    <option value="other">Other</option>
                </select>
            </label><br><br>

            <label>Room Number:<br><input type="text" name="room_number" placeholder="e.g. 101-A" style="width:100%;"></label><br><br>
            <label>Stamp Initials:<br><input type="text" name="stamp_initials" maxlength="10" placeholder="e.g. SW" style="width:100%;"></label><br><br>
            <label>Root Cause:<br><input type="text" name="root_cause" placeholder="Reason for issue" style="width:100%;"></label><br><br>
            
            <label>Start Date:<br><input type="datetime-local" name="start_date" style="width:100%;"></label><br><br>
            <label>Target Due Date:<br><input type="datetime-local" name="due_date" style="width:100%;"></label><br><br>
            
            <label>Assignees (Max 2):<br>
                <select name="assignees[]" multiple size="4" style="width:100%;">
                    <?php foreach ($projectMembers as $pm): ?>
                        <option value="<?= (int) $pm['uid'] ?>">
							<?= htmlspecialchars(trim($pm['fname'] . ' ' . $pm['lname'])) ?> 
							(<?= htmlspecialchars($pm['permissions']) ?>)
						</option>
                    <?php endforeach; ?>
                </select>
            </label><br><br>
			
            <button type="submit">Save Task</button>
            <button type="button" onclick="closeCreateTaskWindow()">Cancel</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Popup Details Window for Viewing Existing Task -->
<div id="view-task-window" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:20px; border-radius:5px; width:400px; max-height:90vh; overflow-y:auto;">
        <h3 id="view-task-title">Task Details</h3>
        <p>
			<strong>Status:</strong>
			<span id="view-task-status"></span>
		</p>
        <p>
			<strong>Type:</strong>
			<span id="view-task-type"></span>
		</p>
        <p>
			<strong>Room Number:</strong>
			<span id="view-task-room"></span>
		</p>
        <p>
			<strong>Stamp Initials:</strong>
			<span id="view-task-stamp"></span>
		</p>
        <p>
			<strong>Root Cause:</strong>
			<span id="view-task-rootcause"></span>
		</p>
        <p>
			<strong>Description:</strong>
			<br>
			<span id="view-task-desc"></span>
		</p>
        <hr>
        <p>
			<strong>Start Date:</strong>
			<span id="view-task-start"></span>
		</p>
        <p>
			<strong>Due Date:</strong>
			<span id="view-task-due"></span>
		</p>
        <p>
			<strong>Created By:</strong>
			<span id="view-task-creator"></span> on <span id="view-task-createdate"></span>
		</p>
        <p>
			<strong>Last Updated By:</strong>
			<span id="view-task-editor"></span> on <span id="view-task-lastupdate"></span>
		</p>
        <button type="button" onclick="closeViewTaskWindow()">Close</button>
    </div>
</div>

<script>
    function closeCreateTaskWindow() {
        document.getElementById('create-task-window').style.display = 'none';
    }
	
	function closeViewTaskWindow() {
		document.getElementById('view-task-window').style.display = 'none';
	}

    document.addEventListener('DOMContentLoaded', function () {
        // Listen for task viewing click events across existing floorplan task pins
        document.querySelectorAll('.task-pin').forEach(function (pin) {
            pin.addEventListener('click', function (e) {
                e.stopPropagation(); // Prevent floorplan click event from firing

                document.getElementById('view-task-title').textContent = '#' + pin.getAttribute('data-tid') + ' - ' + pin.getAttribute('data-title');
                document.getElementById('view-task-status').textContent = pin.getAttribute('data-status');
                document.getElementById('view-task-type').textContent = pin.getAttribute('data-type');
                document.getElementById('view-task-room').textContent = pin.getAttribute('data-room') || 'N/A';
                document.getElementById('view-task-stamp').textContent = pin.getAttribute('data-stamp') || 'N/A';
                document.getElementById('view-task-rootcause').textContent = pin.getAttribute('data-rootcause') || 'N/A';
                document.getElementById('view-task-desc').textContent = pin.getAttribute('data-description') || 'None';
                document.getElementById('view-task-start').textContent = pin.getAttribute('data-start') || 'Not scheduled';
                document.getElementById('view-task-due').textContent = pin.getAttribute('data-due') || 'Not set';
                document.getElementById('view-task-creator').textContent = pin.getAttribute('data-creator') || 'Unknown';
                document.getElementById('view-task-createdate').textContent = pin.getAttribute('data-createdate');
                document.getElementById('view-task-editor').textContent = pin.getAttribute('data-editor') || 'N/A';
                document.getElementById('view-task-lastupdate').textContent = pin.getAttribute('data-lastupdate');

                document.getElementById('view-task-window').style.display = 'flex';
            });
        });
		
		// Listen for task creation click events across the floorplan overlay
		<?php if ($canCreateTasks): ?>
        document.querySelectorAll('.pin-overlay-layer').forEach(function (overlay) {
            overlay.addEventListener('click', function (e) {
                const fpid = overlay.getAttribute('data-fpid');
                const pageNumElem = document.getElementById('page-num-' + fpid);
                const pageNum = pageNumElem ? (parseInt(pageNumElem.textContent, 10) || 1) : 1;

                // Obtain current rectangle dimensions of overlay
                const rect = overlay.getBoundingClientRect();
                
                // Calculate X and Y click position as relative percentage (0-100%)
                const xPercent = ((e.clientX - rect.left) / rect.width) * 100;
                const yPercent = ((e.clientY - rect.top) / rect.height) * 100;

                // Output click coords to browser console
                console.log('Clicked Floorplan ' + fpid + ' at pos_x: ' + xPercent.toFixed(2) + '%, pos_y: ' + yPercent.toFixed(2) + '%');

                document.getElementById('task-fpid').value = fpid;
                document.getElementById('task-page').value = pageNum;
                document.getElementById('task-posx').value = xPercent.toFixed(4);
                document.getElementById('task-posy').value = yPercent.toFixed(4);
                document.getElementById('task-title').value = '';
                document.getElementById('task-desc').value = '';

                document.getElementById('create-task-window').style.display = 'flex';
            });
        });

        // Submit task form via AJAX
        document.getElementById('create-task-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            // Close create-task-window immediately upon submission
            closeCreateTaskWindow();

            // Send task form data to server
            fetch('save-task-to-db.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Reload page to fetch and display newly inserted database record
                } else {
                    alert('Error creating task: ' + (data.error || 'Unknown error'));
                    // Reopen window if database save fails so user can retry
                    document.getElementById('create-task-window').style.display = 'flex';
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('An unexpected server error occurred.');
            });
        });
		<?php endif; ?>
    });
</script>