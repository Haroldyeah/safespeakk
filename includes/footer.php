        </div>
    </main>
                <?php if (isLoggedIn() && !$is_landing_page): ?>
                </div> <!-- .content-wrapper -->
            </div> <!-- .app-layout -->
    <?php endif; ?>
    
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
              
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 text-muted">
                        SafeSpeak Anti-Bullying Reporting System | 
                        © <?php echo date('Y'); ?> All Rights Reserved
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
    
    <!-- Global Image Viewer Modal (reusable) -->
    <div class="modal fade" id="globalImageViewer" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-3" id="globalImageViewerBody">
                    <img id="globalImageViewerImg" src="" alt="Image" style="max-width:100%; height:auto; border-radius:8px;"> 
                </div>
            </div>
        </div>
    </div>

    <script>
    // Helper to show image in the global viewer
    function showGlobalImage(src, title = 'View Image') {
        const modalEl = document.getElementById('globalImageViewer');
        const imgEl = document.getElementById('globalImageViewerImg');
        const titleEl = modalEl.querySelector('.modal-title');
        imgEl.src = src;
        imgEl.onload = function() {
            // center and ensure sizing
            imgEl.style.maxHeight = (window.innerHeight * 0.75) + 'px';
        };
        titleEl.textContent = title;
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
    </script>
    
    <!-- Jotform chat widget -->
    <script src='https://cdn.jotfor.ms/agent/embedjs/0198ee8bbb907ac0a5571be31f71d3f764d2/embed.js'></script>
</body>
</html>
