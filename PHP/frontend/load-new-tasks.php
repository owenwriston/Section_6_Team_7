<!-- Overlay Form for Task Creation -->
<div id="create-task-window" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:20px; border-radius:5px; width:300px;">
        <h3>Create New Task</h3>
        <form id="create-task-form">
            <input type="hidden" id="task-fpid" name="fpid">
            <input type="hidden" id="task-page" name="page">
            <input type="hidden" id="task-posx" name="pos_x">
            <input type="hidden" id="task-posy" name="pos_y">
            
            <label>Title:<br><input type="text" id="task-title" name="title" required style="width:100%;"></label><br><br>
            <label>Description:<br><textarea id="task-desc" name="description" style="width:100%;"></textarea></label><br><br>
            
            <button type="submit">Save Task</button>
            <button type="button" onclick="closeTaskWindow()">Cancel</button>
        </form>
    </div>
</div>

<script>
    function closeTaskWindow() {
        document.getElementById('create-task-window').style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Listen for task click events across floorplan overlays
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

                // Output click coordinates to browser developer console
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

            // Hide the popup window immediately upon submission
            closeTaskWindow();

            // UPDATED: Endpoint changed to save-task-to-db.php
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
                    // Re-open window if database save fails so user can retry
                    document.getElementById('create-task-window').style.display = 'flex';
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('An unexpected server error occurred.');
            });
        });
    });
</script>