<!-- Overlay Form for Task Creation (Visible only to Admin, Owner, Contractor) -->
<?php if ($canCreateTasks): ?>
<div id="create-task-window" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:20px; border-radius:5px; width:300px; max-height:90vh; overflow-y:auto;">
        <h3>Create New Task</h3>
        <form id="create-task-form" enctype="multipart/form-data">
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

            <!-- Media Upload Section -->
            <label for="task-media">Photos / Videos:</label><br>

            <input
                type="file"
                id="task-media"
                name="media[]"
                accept="image/*,video/*"
                multiple
            >

            <div id="task-media-preview"
                style="display:flex; flex-wrap:wrap; gap:10px; margin-top:10px;">
            </div>

            <br><br>
			
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
        <!-- Saved Task Attachments -->
        <hr>

        <h4>Photos / Videos</h4>

        <div
            id="view-task-media"
            style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:15px;"
        >
            <p>No attachments.</p>
        </div>

        <?php if ($canCreateTasks): ?>
            <button
                type="button"
                id="edit-task-button"
                style="background:#0d6efd; color:white; border:none; padding:8px 12px; border-radius:5px; cursor:pointer; margin-right:10px;"
            >
                Edit Task
            </button>
            <button
                type="button"
                id="delete-task-button"
                style="background:#dc3545; color:white; border:none; padding:8px 12px; border-radius:5px; cursor:pointer; margin-right:10px;"
            >
                Delete Task
            </button>
        <?php endif; ?>

        <button type="button" onclick="closeViewTaskWindow()">Close</button>
    </div>
</div>

<!-- Popup for Editing an Existing Task -->
<?php if ($canCreateTasks): ?>
<div id="edit-task-window"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1100; align-items:center; justify-content:center;">

    <div style="background:white; padding:20px; border-radius:5px; width:400px; max-width:90vw; max-height:90vh; overflow-y:auto;">

        <h3>Edit Task</h3>

        <form id="edit-task-form">
            <input type="hidden" name="tid" id="edit-task-tid">

            <label>Title:<br>
                <input type="text" name="title" id="edit-task-title" required style="width:100%;">
            </label><br><br>

            <label>Status:<br>
                <select name="status" id="edit-task-status" style="width:100%;">
                    <option value="open">Open</option>
                    <option value="in_review">In Review</option>
                    <option value="pending">Pending</option>
                    <option value="closed">Closed</option>
                </select>
            </label><br><br>

            <label>Type:<br>
                <select name="task_type" id="edit-task-type" style="width:100%;">
                    <option value="issue">Issue</option>
                    <option value="planned_work">Planned Work</option>
                    <option value="other">Other</option>
                </select>
            </label><br><br>

            <label>Description:<br>
                <textarea name="description" id="edit-task-description" style="width:100%;"></textarea>
            </label><br><br>

            <label>Room Number:<br>
                <input type="text" name="room_number" id="edit-task-room" style="width:100%;">
            </label><br><br>

            <label>Stamp Initials:<br>
                <input type="text" name="stamp_initials" id="edit-task-stamp" maxlength="10" style="width:100%;">
            </label><br><br>

            <label>Root Cause:<br>
                <input type="text" name="root_cause" id="edit-task-rootcause" style="width:100%;">
            </label><br><br>

            <label>Start Date:<br>
                <input type="datetime-local" name="start_date" id="edit-task-start" style="width:100%;">
            </label><br><br>

            <label>Due Date:<br>
                <input type="datetime-local" name="due_date" id="edit-task-due" style="width:100%;">
            </label><br><br>

            <button type="submit">Save Changes</button>
            <button type="button" id="cancel-edit-task">Cancel</button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    function closeCreateTaskWindow() {
        document.getElementById('create-task-window').style.display = 'none';
    }
	
	function closeViewTaskWindow() {
		document.getElementById('view-task-window').style.display = 'none';
	}

    document.addEventListener('DOMContentLoaded', function () {
        // Media preview functionality
        const mediaInput = document.getElementById('task-media');
        const mediaPreview = document.getElementById('task-media-preview');

        // Store all selected media files
        let selectedMediaFiles = [];

        // Store temporary preview URLs for cleanup
        let mediaPreviewURLs = [];

        // Update the actual file input with selected files
        function updateMediaInput() {
            const dataTransfer = new DataTransfer();

            selectedMediaFiles.forEach(function (file) {
                dataTransfer.items.add(file);
            });

            mediaInput.files = dataTransfer.files;
        }

        // Display previews for selected media
        function renderMediaPreviews() {

            // Release previously created preview URLs
            mediaPreviewURLs.forEach(function (url) {
                URL.revokeObjectURL(url);
            });

            mediaPreviewURLs = [];

            // Clear existing previews
            mediaPreview.innerHTML = '';

            // Loop through selected files
            selectedMediaFiles.forEach(function (file, index) {

                // Create container for each media file
                const container = document.createElement('div');

                container.style.width = '120px';
                container.style.display = 'flex';
                container.style.flexDirection = 'column';
                container.style.gap = '5px';

                // Create temporary URL for preview
                const fileURL = URL.createObjectURL(file);
                mediaPreviewURLs.push(fileURL);

                // Display image preview
                if (file.type.startsWith('image/')) {

                    const img = document.createElement('img');

                    img.src = fileURL;
                    img.alt = file.name;
                    img.style.width = '100%';
                    img.style.height = '90px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '5px';

                    container.appendChild(img);

                // Display video preview
                } else if (file.type.startsWith('video/')) {

                    const video = document.createElement('video');

                    video.src = fileURL;
                    video.controls = true;
                    video.preload = 'metadata';
                    video.style.width = '100%';
                    video.style.height = '90px';

                    container.appendChild(video);

                } else {

                    // Display filename for unsupported preview types
                    const fileText = document.createElement('span');

                    fileText.textContent = file.name;
                    container.appendChild(fileText);
                }

                // Display filename
                const fileName = document.createElement('small');

                fileName.textContent = file.name;
                fileName.style.overflowWrap = 'anywhere';

                container.appendChild(fileName);

                // Create Remove button
                const removeButton = document.createElement('button');

                removeButton.type = 'button';
                removeButton.textContent = 'Remove';
                removeButton.style.width = '100%';
                removeButton.style.cursor = 'pointer';

                // Remove only the selected file
                removeButton.addEventListener('click', function () {

                    selectedMediaFiles.splice(index, 1);

                    updateMediaInput();
                    renderMediaPreviews();
                });

                container.appendChild(removeButton);

                // Add preview container to popup
                mediaPreview.appendChild(container);
            });
        }

        // Handle file selection
        if (mediaInput && mediaPreview) {

            mediaInput.addEventListener('change', function (event) {

                const newFiles = Array.from(event.target.files);

                // Add newly selected files without duplicates
                newFiles.forEach(function (file) {

                    const alreadySelected = selectedMediaFiles.some(function (existingFile) {
                        return existingFile.name === file.name &&
                            existingFile.size === file.size &&
                            existingFile.lastModified === file.lastModified;
                    });

                    if (!alreadySelected &&
                        (file.type.startsWith('image/') ||
                        file.type.startsWith('video/'))) {

                        selectedMediaFiles.push(file);
                    }
                });

                // Update the file input and previews
                updateMediaInput();
                renderMediaPreviews();
            });
        }

        // Reset media when creating another task
        function resetTaskMedia() {

            selectedMediaFiles = [];

            if (mediaInput && mediaPreview) {
                updateMediaInput();
                renderMediaPreviews();
            }
        }

        // Track the task currently open in the details popup
        let selectedTaskId = null;


        // Save changes made to an existing task
        const editTaskForm = document.getElementById('edit-task-form');

        if (editTaskForm) {
            editTaskForm.addEventListener('submit', async function (e) {
                e.preventDefault();

                const saveButton = editTaskForm.querySelector('[type="submit"]');

                if (saveButton) {
                    saveButton.disabled = true;
                    saveButton.textContent = 'Saving...';
                }

                try {
                    const formData = new FormData(editTaskForm);

                    const response = await fetch('update-task.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        throw new Error(data.error || 'Unable to update task');
                    }

                    alert('Task updated successfully!');
                    window.location.reload();

                } catch (error) {
                    alert('Error updating task: ' + error.message);

                } finally {
                    if (saveButton) {
                        saveButton.disabled = false;
                        saveButton.textContent = 'Save Changes';
                    }
                }
            });
        }


        // Open the edit popup with the selected task's current values
        const editTaskButton = document.getElementById('edit-task-button');

        if (editTaskButton) {
            editTaskButton.addEventListener('click', function () {
                if (!selectedTaskId) {
                    alert('No task selected.');
                    return;
                }

                const pin = document.querySelector(
                    '.task-pin[data-tid="' + selectedTaskId + '"]'
                );

                if (!pin) {
                    alert('Unable to find the selected task.');
                    return;
                }

                document.getElementById('edit-task-tid').value = selectedTaskId;
                document.getElementById('edit-task-title').value = pin.dataset.title || '';
                document.getElementById('edit-task-status').value = pin.dataset.status || 'open';
                document.getElementById('edit-task-type').value = pin.dataset.type || 'issue';
                document.getElementById('edit-task-description').value = pin.dataset.description || '';
                document.getElementById('edit-task-room').value = pin.dataset.room || '';
                document.getElementById('edit-task-stamp').value = pin.dataset.stamp || '';
                document.getElementById('edit-task-rootcause').value = pin.dataset.rootcause || '';

                const formatDate = value => value ? value.replace(' ', 'T').slice(0, 16) : '';

                document.getElementById('edit-task-start').value = formatDate(pin.dataset.start);
                document.getElementById('edit-task-due').value = formatDate(pin.dataset.due);

                document.getElementById('view-task-window').style.display = 'none';
                document.getElementById('edit-task-window').style.display = 'flex';
            });
        }

        // Close the edit popup without saving
        const cancelEditButton = document.getElementById('cancel-edit-task');

        if (cancelEditButton) {
            cancelEditButton.addEventListener('click', function () {
                document.getElementById('edit-task-window').style.display = 'none';
            });
        }

        const deleteTaskButton = document.getElementById('delete-task-button');

        if (deleteTaskButton) {
            deleteTaskButton.addEventListener('click', function () {

                if (!selectedTaskId) {
                    alert('No task selected.');
                    return;
                }

                const confirmed = confirm(
                    'Are you sure you want to delete Task #' + selectedTaskId +
                    '?\n\nThis action cannot be undone.'
                );

                if (!confirmed) {
                    return;
                }

                // Send the selected task ID to the PHP deletion endpoint
                const formData = new FormData();
                formData.append('tid', selectedTaskId);

                deleteTaskButton.disabled = true;
                deleteTaskButton.textContent = 'Deleting...';

                fetch('delete-task.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(async response => {
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        throw new Error(data.error || 'Unable to delete task');
                    }

                    return data;
                })
                .then(data => {
                    alert('Task #' + selectedTaskId + ' deleted successfully!');

                    if (data.cleanup_warning) {
                        alert('Task deleted, but some uploaded files could not be removed.');
                    }

                    window.location.reload();
                })
                .catch(error => {
                    alert('Error deleting task: ' + error.message);
                })
                .finally(() => {
                    deleteTaskButton.disabled = false;
                    deleteTaskButton.textContent = 'Delete Task';
                });
            });
        }

        // Prevent task details or task creation from opening after a drag
        let suppressPinClick = false;

        // Allow existing task pins to be dragged around their floorplan
        <?php if ($canCreateTasks): ?>
        document.querySelectorAll('.task-pin').forEach(function (pin) {

            pin.style.touchAction = 'none';

            let drag = null;

            pin.addEventListener('pointerdown', function (e) {
                if (e.button !== 0 || !e.isPrimary) return;

                const overlay = pin.closest('.pin-overlay-layer');
                if (!overlay) return;

                const rect = overlay.getBoundingClientRect();
                if (!rect.width || !rect.height) return;

                drag = {
                    pointerId: e.pointerId,
                    startX: e.clientX,
                    startY: e.clientY,
                    originalLeft: pin.style.left,
                    originalTop: pin.style.top,
                    overlay: overlay,
                    moved: false
                };

                pin.setPointerCapture(e.pointerId);
            });

            pin.addEventListener('pointermove', function (e) {
                if (!drag || e.pointerId !== drag.pointerId) return;

                // Require a small movement so ordinary clicks still work
                if (!drag.moved &&
                    Math.hypot(e.clientX - drag.startX,
                            e.clientY - drag.startY) < 6) {
                    return;
                }

                drag.moved = true;

                const rect = drag.overlay.getBoundingClientRect();

                const x = Math.max(0, Math.min(100,
                    ((e.clientX - rect.left) / rect.width) * 100
                ));

                const y = Math.max(0, Math.min(100,
                    ((e.clientY - rect.top) / rect.height) * 100
                ));

                pin.style.left = x.toFixed(4) + '%';
                pin.style.top = y.toFixed(4) + '%';
                pin.style.cursor = 'grabbing';
            });

            pin.addEventListener('pointerup', function (e) {
                if (!drag || e.pointerId !== drag.pointerId) return;

                const finishedDrag = drag;
                drag = null;
                pin.style.cursor = 'pointer';

                if (!finishedDrag.moved) return;

                // Stop the release from opening the task creation form
                e.stopPropagation();

                // Stop the subsequent click from opening task details
                suppressPinClick = true;

                const tid = pin.getAttribute('data-tid');
                const fpid = finishedDrag.overlay.getAttribute('data-fpid');

                const formData = new FormData();
                formData.append('tid', tid);
                formData.append('fpid', fpid);
                formData.append('pos_x', parseFloat(pin.style.left));
                formData.append('pos_y', parseFloat(pin.style.top));

                fetch('move-task.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(async response => {
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        throw new Error(data.error || 'Unable to move task');
                    }

                    console.log('Task #' + tid + ' position saved.');
                })
                .catch(error => {
                    // Restore the previous position if saving fails
                    pin.style.left = finishedDrag.originalLeft;
                    pin.style.top = finishedDrag.originalTop;
                    alert('Error moving task: ' + error.message);
                });
            });

            pin.addEventListener('pointercancel', function (e) {
                if (!drag || e.pointerId !== drag.pointerId) return;

                pin.style.left = drag.originalLeft;
                pin.style.top = drag.originalTop;
                pin.style.cursor = 'pointer';
                drag = null;
            });
        });
        <?php endif; ?>


        document.querySelectorAll('.task-pin').forEach(function (pin) {
            pin.addEventListener('click', function (e) {
                if (suppressPinClick) {
                    e.stopImmediatePropagation();
                    e.stopPropagation();
                    suppressPinClick = false;
                }
            }, true);
        });

        // Listen for task viewing click events across existing floorplan task pins
        document.querySelectorAll('.task-pin').forEach(function (pin) {
            pin.addEventListener('click', function (e) {

                e.stopPropagation(); // Prevent floorplan click event from firing

                // Get the ID of the task that was clicked
                const taskId = pin.getAttribute('data-tid');

                selectedTaskId = taskId;

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

                // Load saved photos and videos for this task
                const mediaContainer = document.getElementById('view-task-media');

                mediaContainer.textContent = 'Loading attachments...';

                fetch('get-task-attachments.php?tid=' + encodeURIComponent(taskId))
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Prevent an older request from replacing another task's media
                        if (mediaContainer.dataset.taskId !== taskId) {
                            return;
                        }

                        mediaContainer.replaceChildren();

                        if (!data.success) {
                            mediaContainer.textContent = data.error || 'Unable to load attachments.';
                            return;
                        }

                        if (!data.attachments || data.attachments.length === 0) {
                            mediaContainer.textContent = 'No attachments.';
                            return;
                        }

                        // Display saved images and videos
                        data.attachments.forEach(attachment => {

                            const filename = attachment.original_name || 'Attachment';

                            // Convert the stored path into a browser-accessible URL
                            const fileURL = '../' + attachment.file_path;

                            // Create a container for each attachment
                            const item = document.createElement('div');

                            item.style.width = '150px';
                            item.style.display = 'flex';
                            item.style.flexDirection = 'column';
                            item.style.gap = '5px';

                            // Determine whether the attachment is an image or video
                            const extension = filename.split('.').pop().toLowerCase();

                            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {

                                const img = document.createElement('img');

                                img.src = fileURL;
                                img.alt = filename;
                                img.style.width = '100%';
                                img.style.height = '110px';
                                img.style.objectFit = 'cover';
                                img.style.borderRadius = '5px';
                                img.style.cursor = 'pointer';

                                // Open full-size image in a new tab
                                img.addEventListener('click', function () {
                                    window.open(fileURL, '_blank', 'noopener,noreferrer');
                                });

                                item.appendChild(img);

                            } else if (['mp4', 'webm', 'mov'].includes(extension)) {

                                const video = document.createElement('video');

                                video.src = fileURL;
                                video.controls = true;
                                video.preload = 'metadata';
                                video.style.width = '100%';
                                video.style.borderRadius = '5px';

                                item.appendChild(video);

                            }

                            // Display original filename
                            const fileName = document.createElement('small');

                            fileName.textContent = filename;
                            fileName.style.overflowWrap = 'anywhere';

                            item.appendChild(fileName);

                            mediaContainer.appendChild(item);
                        });
                    })
                    .catch(error => {
                        console.error('Attachment loading error:', error);

                        if (mediaContainer.dataset.taskId === taskId) {
                            mediaContainer.textContent = 'Unable to load attachments.';
                        }
                    });

                mediaContainer.dataset.taskId = taskId;
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

                // Clear media from previous task
                resetTaskMedia();

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