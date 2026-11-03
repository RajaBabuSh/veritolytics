<?php
/**
 * Veritolytics - Public Footer
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
?>
</main>

<footer class="site-footer bg-white pt-5 pb-4 mt-5 border-top">
    <div class="container">
        <div class="row g-4 justify-content-between mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center mb-3">
                    <img src="<?= BASE_URL ?>/assets/images/veritopinion-logo.png" alt="Veritopinion" class="brand-logo-img" style="height: 42px;">
                </div>
                <p class="text-muted small leading-relaxed mb-3">
                    <?= APP_TAGLINE ?> An enterprise quantitative research panel connecting verified global respondents with corporate, academic, and consumer insights initiatives.
                </p>
                <div class="d-flex gap-2 text-muted fs-5">
                    <a href="#" class="text-muted text-decoration-none hover-primary"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="text-muted text-decoration-none hover-primary"><i class="bi bi-linkedin"></i></a>
                    <a href="#" class="text-muted text-decoration-none hover-primary"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-muted text-decoration-none hover-primary"><i class="bi bi-instagram"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-3 col-6">
                <h6 class="fw-bold text-dark mb-3">Platform</h6>
                <ul class="list-unstyled space-y-2 small">
                    <li class="mb-2"><a href="<?= BASE_URL ?>/#home" class="text-muted text-decoration-none">Home</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/#why-us" class="text-muted text-decoration-none">Why Veritopinion</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/#how-it-works" class="text-muted text-decoration-none">How It Works</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/#rewards" class="text-muted text-decoration-none">Rewards Catalog</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/#categories" class="text-muted text-decoration-none">Categories</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/auth/register.php" class="text-primary fw-semibold text-decoration-none">Join Panel (+500 pts)</a></li>
                </ul>
            </div>

            <div class="col-lg-2 col-md-3 col-6">
                <h6 class="fw-bold text-dark mb-3">Trust & Security</h6>
                <ul class="list-unstyled space-y-2 small">
                    <li class="mb-2"><a href="<?= BASE_URL ?>/#faq" class="text-muted text-decoration-none">Privacy Shield</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/#faq" class="text-muted text-decoration-none">Terms of Service</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/#faq" class="text-muted text-decoration-none">Data Ethics Policy</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/admin/login.php" class="text-muted text-decoration-none"><i class="bi bi-shield-lock me-1"></i> Admin Portal</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold text-dark mb-3">Panel Operations Desk</h6>
                <p class="text-muted small mb-2">Have a question regarding rewards, quotas, or verification?</p>
                <div class="footer-support-card p-3 rounded-4 border text-muted small">
                    <div class="d-flex align-items-center gap-2 mb-1.5">
                        <i class="bi bi-envelope text-primary"></i>
                        <span class="text-dark fw-semibold">support@veritopinion.com</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-clock text-warning"></i>
                        <span>Mon - Fri • 9:00 AM - 6:00 PM EST</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-top pt-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-muted">
            <div>
                &copy; <?= date('Y') ?> <?= APP_NAME ?> Inc. All rights reserved.
            </div>
            <div class="text-center text-md-end" style="font-size: 0.775rem;">
                Fixed Valuation: 100 Points = $1.00 USD. Minimum redemption threshold is 1,000 Points ($10.00 USD).
            </div>
        </div>
    </div>
</footer>

<!-- Include Floating Customer Support Chatbot Widget -->
<?php require_once __DIR__ . '/chatbot.php'; ?>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Global Application Script -->
<script>
    window.BASE_URL = <?= json_encode(BASE_URL) ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>

</body>
</html>
