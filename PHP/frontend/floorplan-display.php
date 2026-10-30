<!-- Page Navigation Controls -->
<div class="pdf-controls" style="margin-bottom: 10px;">
    <button type="button" id="prev-page-<?= $fpid ?>">Previous Page</button>
    <span>Page: <span id="page-num-<?= $fpid ?>">1</span> / <span id="page-count-<?= $fpid ?>">-</span></span>
    <button type="button" id="next-page-<?= $fpid ?>">Next Page</button>
</div>

<!-- Interactive PDF viewer with task pin creation -->
<div class="floorplan-canvas-container" id="container-<?= $fpid ?>">
    <!-- Canvas where Mozilla PDF.js draws the PDF page -->
    <canvas id="canvas-<?= $fpid ?>"></canvas>
    
    <?php require __DIR__ . '/load-existing-tasks.php'; ?>
</div>

<!-- PDF rendering and page navigation logic -->
<script>
    (function () {
        // Initialize current floorplan ID, relative PDF URL, DOM reference to HTML canvas element, and DOM reference to overlay layer div 
        const fpid = <?= $fpid ?>;
        const pdfUrl = 'floorplan-file.php?fpid=' + fpid;
        const canvas = document.getElementById('canvas-' + fpid);
        const overlay = document.getElementById('overlay-' + fpid);
        
        let pdfDoc = null;
        let pageNum = 1;

        // Configure worker path for Mozilla PDF.js library
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        function renderPage(num) {
            pdfDoc.getPage(num).then(function (page) {
                // Initialize the zoom scale factor for rendering
                const scale = 1.2;
                
                // Calculate viewport dimensions at specified scale
                const viewport = page.getViewport({ scale: scale });
                
                // Obtain 2D rendering context from HTML canvas
                const context = canvas.getContext('2d');

                // Match canvas height and width to PDF viewport height and width
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                // Render PDF directly into canvas context
                page.render({
                    canvasContext: context,
                    viewport: viewport
                });

                document.getElementById('page-num-' + fpid).textContent = num;

                // Show only pins belonging to the active page
                const pins = overlay.querySelectorAll('.task-pin');
                pins.forEach(pin => {
                    const pinPage = parseInt(pin.getAttribute('data-page') || '1', 10);
                    pin.style.display = (pinPage === num) ? 'flex' : 'none';
                });
            });
        }

        // Download PDF binary data via PDF.js promise
        pdfjsLib.getDocument(pdfUrl).promise.then(function (pdf) {
            pdfDoc = pdf;
            document.getElementById('page-count-' + fpid).textContent = pdf.numPages;
            renderPage(pageNum);
        }).catch(function (error) { // Handle errors for PDF loading failures
            // Log PDF loading error to browser console
            console.error('Error loading floorplan PDF ' + fpid + ':', error);
        });

        document.getElementById('prev-page-' + fpid).addEventListener('click', function () {
            if (pageNum <= 1) return;
            pageNum--;
            renderPage(pageNum);
        });

        document.getElementById('next-page-' + fpid).addEventListener('click', function () {
            if (!pdfDoc || pageNum >= pdfDoc.numPages) return;
            pageNum++;
            renderPage(pageNum);
        });
    })();
</script>