<?php
/**
 * Veritolytics - Admin Portal Footer
 * Pure Core PHP 8.2+ Architecture
 */
require_once __DIR__ . '/../config/app.php';
?>
    </div> <!-- End .portal-main -->
</div> <!-- End .portal-wrapper -->

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    window.BASE_URL = <?= json_encode(BASE_URL) ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>

</body>
</html>
