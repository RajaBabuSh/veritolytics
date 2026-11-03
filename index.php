<?php
/**
 * Veritolytics - Public Homepage & Landing Portal (World-Class Edition)
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = APP_NAME . ' | ' . APP_TAGLINE;
$db = get_db();

// Fetch sample active surveys for public showcase
$featuredSurveys = $db->query("
    SELECT title, category, reward_points, reward_usd, duration_minutes, quota, completed_count, provider 
    FROM surveys 
    WHERE status = 'active' 
    ORDER BY reward_points DESC 
    LIMIT 3
")->fetchAll();

// Fetch sample rewards
$featuredRewards = $db->query("
    SELECT title, category, points_required, cash_equivalent_usd, icon, delivery_type 
    FROM rewards 
    WHERE is_active = 1 
    ORDER BY points_required ASC 
    LIMIT 4
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- ==============================================================================
     1. HERO SECTION (HIGH IMPACT FINTECH + RESEARCH IDENTITY)
     ============================================================================== -->
<section id="home" class="py-5 py-lg-6 bg-gradient-brand position-relative overflow-hidden">
    <div class="container py-lg-5 position-relative" style="z-index: 2;">
        <div class="row align-items-center g-5">
            <div class="col-lg-7 text-center text-lg-start">
                <!-- Trust Pill Badge -->
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill bg-white border mb-3 shadow-xs">
                    <span class="badge bg-warning text-dark rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.7rem;">+500 PTS</span>
                    <span class="small text-muted fw-semibold">Instant $5.00 Welcome Bonus on Registration</span>
                </div>

                <!-- Main Hero Headline -->
                <h1 class="display-3 fw-bolder tracking-tight text-dark mb-3 lh-sm">
                    Your Voice. Your Insights. <br>
                    <span class="text-gradient-hero">Your Real Impact.</span>
                </h1>

                <!-- Supporting Description -->
                <p class="lead text-muted mb-4 pe-lg-4 leading-relaxed" style="font-size: 1.15rem; max-width: 620px;">
                    Veritolytics is an elite market research community connecting verified respondents with corporate decision-makers, universities, and tech innovators. Share your genuine perspective and earn direct PayPal cash transfers and digital gift cards.
                </p>

                <!-- CTAs -->
                <div class="d-flex flex-column flex-sm-row align-items-center justify-content-center justify-content-lg-start gap-3 mb-5">
                    <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-brand-primary btn-lg rounded-pill px-4 py-3 fw-bold shadow-lg">
                        <span>Get Started Free</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                    <a href="#rewards" class="btn btn-brand-ghost btn-lg rounded-pill px-4 py-3 fw-semibold">
                        <i class="bi bi-gift me-1"></i>
                        <span>Explore Rewards</span>
                    </a>
                </div>

                <!-- Trust Micro-Badges -->
                <div class="row g-3 pt-3 border-top text-muted small">
                    <div class="col-6 col-sm-3 d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check text-success fs-5"></i>
                        <span>100% Free Always</span>
                    </div>
                    <div class="col-6 col-sm-3 d-flex align-items-center gap-2">
                        <i class="bi bi-lightning-charge-fill text-warning fs-5"></i>
                        <span>100 pts = $1.00 USD</span>
                    </div>
                    <div class="col-6 col-sm-3 d-flex align-items-center gap-2">
                        <i class="bi bi-wallet2 text-info fs-5"></i>
                        <span>Payout from $10.00</span>
                    </div>
                    <div class="col-6 col-sm-3 d-flex align-items-center gap-2">
                        <i class="bi bi-lock-fill text-primary fs-5"></i>
                        <span>Anonymized Data</span>
                    </div>
                </div>
            </div>

            <!-- Hero Live Rewards Preview Widget (Fintech Style) -->
            <div class="col-lg-5">
                <div class="card card-preview-live p-4 p-md-4">
                    <!-- Widget Header -->
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="pulse-indicator"></span>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark small">Live Panel Opportunities</h6>
                                <small class="text-muted" style="font-size: 0.725rem;">Real-Time Research Matching</small>
                            </div>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 small">
                            Active Cohorts
                        </span>
                    </div>

                    <!-- Active Study 1 -->
                    <div class="survey-preview-item mb-2.5">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small" style="font-size: 0.7rem;">
                                <?= e($featuredSurveys[0]['category'] ?? 'Technology') ?>
                            </span>
                            <span class="fw-bold text-warning small">
                                <i class="bi bi-coin me-1"></i>+<?= number_format($featuredSurveys[0]['reward_points'] ?? 250) ?> pts (<?= format_currency($featuredSurveys[0]['reward_usd'] ?? 2.50) ?>)
                            </span>
                        </div>
                        <h6 class="fw-bold text-dark mb-1 small">
                            <?= e($featuredSurveys[0]['title'] ?? 'Smart Home Ecosystems & Connected IoT 2026') ?>
                        </h6>
                        <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 0.725rem;">
                            <span><i class="bi bi-clock me-1"></i>~<?= $featuredSurveys[0]['duration_minutes'] ?? 8 ?> mins</span>
                            <span>Quota: <?= $featuredSurveys[0]['completed_count'] ?? 84 ?> / <?= $featuredSurveys[0]['quota'] ?? 250 ?></span>
                        </div>
                    </div>

                    <!-- Active Study 2 -->
                    <?php if (!empty($featuredSurveys[1])): ?>
                        <div class="survey-preview-item mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge bg-purple bg-opacity-10 text-purple border rounded-pill small" style="font-size: 0.7rem; color: #7C3AED; background-color: #F5F3FF; border-color: #DDD6FE;">
                                    <?= e($featuredSurveys[1]['category']) ?>
                                </span>
                                <span class="fw-bold text-warning small">
                                    <i class="bi bi-coin me-1"></i>+<?= number_format($featuredSurveys[1]['reward_points']) ?> pts (<?= format_currency($featuredSurveys[1]['reward_usd']) ?>)
                                </span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1 small text-truncate">
                                <?= e($featuredSurveys[1]['title']) ?>
                            </h6>
                            <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 0.725rem;">
                                <span><i class="bi bi-clock me-1"></i>~<?= $featuredSurveys[1]['duration_minutes'] ?> mins</span>
                                <span>Direct Credit</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Welcome Bonus Banner Inside Card -->
                    <div class="welcome-bonus-banner mb-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-warning text-dark p-1.5 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                    <i class="bi bi-gift-fill" style="font-size: 0.85rem;"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark small">New Member Welcome Bonus</div>
                                    <small class="text-muted" style="font-size: 0.725rem;">Instant 500 Points Deposit on Sign Up</small>
                                </div>
                            </div>
                            <span class="badge bg-warning text-dark fw-bold rounded-pill" style="font-size: 0.75rem;">$5.00 USD</span>
                        </div>
                    </div>

                    <!-- Payout Methods Row -->
                    <div class="d-flex align-items-center justify-content-between text-muted small mb-3 px-1" style="font-size: 0.725rem;">
                        <span class="text-muted">Disbursed via:</span>
                        <div class="d-flex gap-2 text-dark fw-semibold">
                            <span><i class="bi bi-paypal text-primary me-1"></i>PayPal</span>
                            <span><i class="bi bi-amazon text-warning me-1"></i>Amazon</span>
                            <span><i class="bi bi-apple me-1"></i>Apple</span>
                            <span><i class="bi bi-credit-card-2-front text-info me-1"></i>Visa</span>
                        </div>
                    </div>

                    <!-- Direct Card CTA -->
                    <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-brand-primary w-100 rounded-pill py-2.5 fw-bold shadow-sm">
                        <span>Join Panel & Claim 500 Pts</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================================================
     2. WHY CHOOSE VERITOLYTICS (KEY DIFFERENTIATORS)
     ============================================================================== -->
<section id="why-us" class="py-5 my-lg-4">
    <div class="container py-lg-3">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-semibold mb-2">
                Why Veritolytics
            </span>
            <h2 class="fw-bold text-dark mb-2">Engineered For Quality & Respect</h2>
            <p class="text-muted">Unlike legacy survey mills, Veritolytics treats your time as an invaluable research asset.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-3 col-sm-6">
                <div class="pillar-card">
                    <div class="d-inline-flex p-3 rounded-4 bg-primary bg-opacity-10 text-primary mb-3">
                        <i class="bi bi-percent fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">100 Pts = $1.00 USD</h5>
                    <p class="text-muted small mb-0 leading-relaxed">
                        A fixed, predictable points formula. No artificial point conversions, surprise deductions, or disappearing balances.
                    </p>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6">
                <div class="pillar-card">
                    <div class="d-inline-flex p-3 rounded-4 bg-purple bg-opacity-10 text-purple mb-3" style="color: #7C3AED;">
                        <i class="bi bi-cpu-fill fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Smart Cohort Matching</h5>
                    <p class="text-muted small mb-0 leading-relaxed">
                        Our demographic profiling algorithms match you beforehand, virtually eliminating frustrating late-survey disqualifications.
                    </p>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6">
                <div class="pillar-card">
                    <div class="d-inline-flex p-3 rounded-4 bg-success bg-opacity-10 text-success mb-3">
                        <i class="bi bi-send-check-fill fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Fast 24-48h Payouts</h5>
                    <p class="text-muted small mb-0 leading-relaxed">
                        Redeem starting at 1,000 points ($10.00 USD). Cash is sent directly to your PayPal or digital gift card wallet within 1-2 business days.
                    </p>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6">
                <div class="pillar-card">
                    <div class="d-inline-flex p-3 rounded-4 bg-warning bg-opacity-10 text-warning mb-3">
                        <i class="bi bi-shield-check fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Strict Anonymity</h5>
                    <p class="text-muted small mb-0 leading-relaxed">
                        GDPR and CCPA compliant. Individual answers are aggregated into statistical data and never sold to third-party telemarketers.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================================================
     3. HOW IT WORKS (3 SIMPLE STEPS)
     ============================================================================== -->
<section id="how-it-works" class="py-5 bg-white border-top border-bottom">
    <div class="container py-lg-4">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-semibold mb-2">
                Simple 3-Step Process
            </span>
            <h2 class="fw-bold text-dark mb-2">How Veritolytics Works</h2>
            <p class="text-muted">From registration to digital payout in three seamless steps.</p>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-lg-4 col-md-6">
                <div class="step-card">
                    <div class="step-number-pill">1</div>
                    <h5 class="fw-bold text-dark mb-2">Sign Up in 30 Seconds</h5>
                    <p class="text-muted small mb-3 leading-relaxed">
                        Create your free member account and instantly unlock 500 Welcome Points. Complete your demographics profile to access top-tier invitations.
                    </p>
                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill small">
                        +500 Points Instant Bonus
                    </span>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="step-card">
                    <div class="step-number-pill">2</div>
                    <h5 class="fw-bold text-dark mb-2">Answer Targeted Surveys</h5>
                    <p class="text-muted small mb-3 leading-relaxed">
                        Participate in 5 to 15-minute studies on consumer technology, finance, retail brands, and entertainment from any computer, tablet, or phone.
                    </p>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small">
                        Matched to Your Profile
                    </span>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="step-card">
                    <div class="step-number-pill">3</div>
                    <h5 class="fw-bold text-dark mb-2">Cash Out Real Value</h5>
                    <p class="text-muted small mb-3 leading-relaxed">
                        Reach 1,000 points ($10.00 USD) and choose your preferred reward: PayPal direct cash, Amazon.com codes, Apple cards, or Virtual Visa.
                    </p>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small">
                        Fast 24-48h Delivery
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================================================
     4. REAL REWARDS & REDEMPTION METHODS
     ============================================================================== -->
<section id="rewards" class="py-5 my-lg-4">
    <div class="container py-lg-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-5">
            <div>
                <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-3 py-1 fw-bold mb-2">
                    Verified Digital Catalog
                </span>
                <h2 class="fw-bold text-dark mb-1">Exchange Points For Real Rewards</h2>
                <p class="text-muted small mb-0">Genuine payouts with a guaranteed 100 points = $1.00 USD fixed valuation.</p>
            </div>
            <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-brand-primary rounded-pill px-4 py-2 fw-semibold">
                Start Earning Points →
            </a>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredRewards as $rew): ?>
                <div class="col-xl-3 col-sm-6">
                    <div class="card card-vy border-0 shadow-sm p-4 h-100 text-center d-flex flex-column justify-content-between card-vy-hover">
                        <div>
                            <div class="mx-auto rounded-circle bg-light text-primary d-flex align-items-center justify-content-center mb-3 shadow-xs" style="width: 60px; height: 60px; font-size: 1.65rem;">
                                <i class="bi <?= e($rew['icon']) ?>"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1"><?= e($rew['title']) ?></h6>
                            <span class="badge bg-light text-muted border small rounded-pill mb-2"><?= e($rew['category']) ?></span>
                            <small class="text-muted d-block" style="font-size: 0.725rem;"><?= e($rew['delivery_type']) ?></small>
                        </div>

                        <div class="pt-3 border-top mt-3">
                            <span class="fw-bold text-success fs-4 d-block mb-1"><?= format_currency($rew['cash_equivalent_usd']) ?></span>
                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill small fw-bold">
                                <?= number_format($rew['points_required']) ?> Points
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ==============================================================================
     5. FEATURED SURVEY CATEGORIES
     ============================================================================== -->
<section id="categories" class="py-5 bg-white border-top border-bottom">
    <div class="container py-lg-4">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-semibold mb-2">
                Diverse Research Sectors
            </span>
            <h2 class="fw-bold text-dark mb-2">What Topics Will You Influence?</h2>
            <p class="text-muted">Top organizations rely on Veritolytics panel members for quantitative feedback across diverse sectors.</p>
        </div>

        <div class="row g-3">
            <div class="col-lg-4 col-md-6">
                <div class="category-tile">
                    <div class="category-icon-box bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-laptop"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Technology & AI</h6>
                    <p class="text-muted small mb-3">Consumer software, artificial intelligence apps, hardware ergonomics, and developer tools.</p>
                    <div class="mt-auto d-flex justify-content-between align-items-center small text-muted pt-2 border-top">
                        <span>Typical Reward:</span>
                        <strong class="text-primary">+200 - 500 pts</strong>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="category-tile">
                    <div class="category-icon-box bg-success bg-opacity-10 text-success">
                        <i class="bi bi-cart3"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Consumer Goods & Grocery</h6>
                    <p class="text-muted small mb-3">Eco-friendly packaging, household brands, dietary trends, and supermarket shopping habits.</p>
                    <div class="mt-auto d-flex justify-content-between align-items-center small text-muted pt-2 border-top">
                        <span>Typical Reward:</span>
                        <strong class="text-success">+150 - 350 pts</strong>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="category-tile">
                    <div class="category-icon-box bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-bank"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Fintech & Digital Banking</h6>
                    <p class="text-muted small mb-3">Mobile wallets, contactless payments, peer-to-peer transfers, investment apps, and credit cards.</p>
                    <div class="mt-auto d-flex justify-content-between align-items-center small text-muted pt-2 border-top">
                        <span>Typical Reward:</span>
                        <strong class="text-warning-emphasis">+250 - 600 pts</strong>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="category-tile">
                    <div class="category-icon-box bg-purple bg-opacity-10 text-purple" style="color: #7C3AED;">
                        <i class="bi bi-film"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Media & Entertainment</h6>
                    <p class="text-muted small mb-3">Video streaming platforms, ad tier preferences, gaming habits, podcasts, and digital subscriptions.</p>
                    <div class="mt-auto d-flex justify-content-between align-items-center small text-muted pt-2 border-top">
                        <span>Typical Reward:</span>
                        <strong class="text-purple" style="color: #7C3AED;">+120 - 300 pts</strong>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="category-tile">
                    <div class="category-icon-box bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-heart-pulse"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Health & Wellness</h6>
                    <p class="text-muted small mb-3">Fitness trackers, mental wellness applications, nutrition, telehealth, and preventative care.</p>
                    <div class="mt-auto d-flex justify-content-between align-items-center small text-muted pt-2 border-top">
                        <span>Typical Reward:</span>
                        <strong class="text-danger">+200 - 450 pts</strong>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="category-tile">
                    <div class="category-icon-box bg-info bg-opacity-10 text-info">
                        <i class="bi bi-briefcase"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Workplace & Hybrid Trends</h6>
                    <p class="text-muted small mb-3">Remote work software, team collaboration apps, corporate wellness, and professional tools.</p>
                    <div class="mt-auto d-flex justify-content-between align-items-center small text-muted pt-2 border-top">
                        <span>Typical Reward:</span>
                        <strong class="text-info">+180 - 400 pts</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================================================
     6. MEMBER BENEFITS & COMMUNITY TRUST
     ============================================================================== -->
<section class="py-5 bg-light-section position-relative">
    <div class="container py-lg-4 position-relative" style="z-index: 2;">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-semibold mb-2">
                    Research Impact
                </span>
                <h2 class="fw-bold text-dark mb-3">Your Perspective Shapes Tomorrow's Products</h2>
                <p class="text-muted leading-relaxed mb-4">
                    Before companies launch new digital services or store products, they need real, unbiased data. By participating in Veritolytics studies, you become an active co-creator in global product evolution while earning tangible, verified financial rewards.
                </p>

                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 bg-white border rounded-4 shadow-sm">
                            <span class="fs-2 fw-bold text-warning d-block">100%</span>
                            <span class="text-muted small">Verified Payout Rate</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-white border rounded-4 shadow-sm">
                            <span class="fs-2 fw-bold text-success d-block">24-48h</span>
                            <span class="text-muted small">Average Processing Time</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card bg-white text-dark rounded-4 p-4 p-md-5 border-0 shadow-lg">
                    <h5 class="fw-bold mb-3 text-dark">Member Guarantee</h5>
                    <div class="space-y-3">
                        <div class="d-flex align-items-start gap-3">
                            <i class="bi bi-check-circle-fill text-success fs-5 mt-0.5"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Never Any Hidden Fees</h6>
                                <p class="text-muted small mb-0">Joining and redeeming is completely free. We never charge processing or withdrawal fees.</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <i class="bi bi-check-circle-fill text-success fs-5 mt-0.5"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Flexible Device Support</h6>
                                <p class="text-muted small mb-0">Complete studies at your convenience on smartphone, tablet, laptop, or desktop.</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <i class="bi bi-check-circle-fill text-success fs-5 mt-0.5"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Responsive Human Support</h6>
                                <p class="text-muted small mb-0">Our dedicated operations helpdesk and intelligent assistant ensure fast answers to all inquiry tickets.</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-top mt-4 text-center">
                        <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-brand-primary w-100 rounded-pill py-2.5 fw-bold shadow-sm">
                            Join Now & Get 500 Points Free
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================================================
     7. FREQUENTLY ASKED QUESTIONS (FAQ)
     ============================================================================== -->
<section id="faq" class="py-5 my-lg-4">
    <div class="container py-lg-3">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-semibold mb-2">
                Got Questions?
            </span>
            <h2 class="fw-bold text-dark mb-2">Frequently Asked Questions</h2>
            <p class="text-muted">Everything you need to know about points, survey eligibility, and digital cashouts.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion accordion-flush rounded-4 overflow-hidden border shadow-sm" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqHeading1">
                            <button class="accordion-button fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="true" aria-controls="faqCollapse1">
                                How much is each Veritolytics point worth?
                            </button>
                        </h2>
                        <div id="faqCollapse1" class="accordion-collapse collapse show" aria-labelledby="faqHeading1" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small leading-relaxed">
                                We maintain a transparent <strong>100 points = $1.00 USD</strong> fixed conversion rate. For example, a 250-point survey is worth $2.50 USD, and a 1,000-point redemption equals a $10.00 cash or gift card transfer. Your points never expire as long as your account remains active.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqHeading2">
                            <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
                                What is the minimum payout threshold?
                            </button>
                        </h2>
                        <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faqHeading2" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small leading-relaxed">
                                You can cash out as soon as your balance reaches <strong>1,000 points ($10.00 USD)</strong>. Because new members receive 500 Welcome Points immediately upon registration, you are already halfway to your first payout on your very first day!
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqHeading3">
                            <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">
                                How fast are PayPal transfers and gift card codes delivered?
                            </button>
                        </h2>
                        <div id="faqCollapse3" class="accordion-collapse collapse" aria-labelledby="faqHeading3" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small leading-relaxed">
                                Cashout requests undergo an automated security audit and are disbursed digitally within <strong>1 to 2 business days</strong> directly to your designated PayPal address or email inbox.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqHeading4">
                            <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse4" aria-expanded="false" aria-controls="faqCollapse4">
                                Why do other survey websites screen out respondents, and how does Veritolytics solve this?
                            </button>
                        </h2>
                        <div id="faqCollapse4" class="accordion-collapse collapse" aria-labelledby="faqHeading4" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small leading-relaxed">
                                Traditional websites invite respondents blindly and screen them out halfway through. Veritolytics uses an upfront demographic profile to match your background directly with research quotas before you begin, drastically minimizing disqualifications.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqHeading5">
                            <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse5" aria-expanded="false" aria-controls="faqCollapse5">
                                Is my personal information kept confidential?
                            </button>
                        </h2>
                        <div id="faqCollapse5" class="accordion-collapse collapse" aria-labelledby="faqHeading5" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small leading-relaxed">
                                Yes. Veritolytics operates in full compliance with ESOMAR, GDPR, and CCPA standards. Your responses are anonymized and aggregated with other panelists. We never sell your personal contact details to marketers.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================================================
     8. CONTACT & SUPPORT INQUIRY DESK
     ============================================================================== -->
<section id="contact" class="py-5 bg-light border-top">
    <div class="container py-lg-4">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card card-vy border-0 shadow-lg p-4 p-md-5">
                    <div class="text-center mb-4">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-semibold mb-2">
                            Support & Inquiries
                        </span>
                        <h3 class="fw-bold text-dark">Have an Inquiry or Question?</h3>
                        <p class="text-muted small">Our research operations team is standing by to help with accounts, payments, or partnerships.</p>
                    </div>

                    <form action="<?= BASE_URL ?>/submit_ticket.php" method="POST">
                        <?= csrf_field() ?>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Full Name *</label>
                                <input type="text" name="name" class="form-control rounded-3" placeholder="Jane Doe" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Email Address *</label>
                                <input type="email" name="email" class="form-control rounded-3" placeholder="jane@example.com" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Inquiry Department *</label>
                            <select name="subject" class="form-select rounded-3" required>
                                <option value="Member Support & Points">Member Support & Points</option>
                                <option value="Reward Redemption Question">Reward Redemption Question</option>
                                <option value="Corporate Research Partnership">Corporate Research Partnership</option>
                                <option value="General Inquiry">General Question</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-muted">Message Details *</label>
                            <textarea name="message" class="form-control rounded-3" rows="4" placeholder="How can our research desk assist you?" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-brand-primary w-100 py-2.5 fw-bold shadow-sm">
                            <i class="bi bi-send-fill me-1"></i> Send Message to Operations Desk
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
