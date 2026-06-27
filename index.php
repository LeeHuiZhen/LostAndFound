<?php
session_start();
require_once 'config.php';

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("Location: syafiqah/matching/dashboard.php");
    exit;
}

// Fetch live stats from database for the hero section
$total_lost   = $conn->query("SELECT COUNT(*) FROM lost_items")->fetch_row()[0] ?? 0;
$total_found  = $conn->query("SELECT COUNT(*) FROM found_items")->fetch_row()[0] ?? 0;
$total_returned = $conn->query("SELECT COUNT(*) FROM matches WHERE status='returned'")->fetch_row()[0] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTM Campus Lost & Found Assistant</title>
    <meta name="description" content="The official UTM Campus Lost and Found digital assistant. Report lost items, match found possessions, and coordinate secure handovers.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { display: block; }
        .cta-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 32px 28px;
            height: 100%;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .cta-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-hover);
            border-color: #c7d2fe;
        }
        .cta-icon {
            font-size: 44px;
            margin-bottom: 16px;
            display: block;
            animation: float 3s ease-in-out infinite;
        }
        .how-step {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            padding: 20px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .how-step:last-child { border-bottom: none; }
        .step-num {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: white;
            font-weight: 800;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
    </style>
</head>
<body>

    <!-- ===== DARK NAVBAR ===== -->
    <nav class="custom-navbar">
        <a href="index.php" class="brand">🔍 UTM Lost & Found</a>
        <div class="nav-links">
            <a href="tan/verify_claim.php">🔐 Admin Portal</a>
            <a href="tey/login.php" class="btn-custom btn-custom-primary" style="padding: 8px 20px; font-size: 13px;">Sign In</a>
        </div>
    </nav>

    <!-- ===== FULL HERO ===== -->
    <section class="landing-hero">
        <div class="landing-hero-content">
            <div class="landing-hero-badge">
                🎓 UTM Campus Digital Platform
            </div>
            <h1>Lost Something on <span class="gradient-word">Campus?</span></h1>
            <p>Our AI-powered platform helps you report, match, and recover lost items across UTM Skudai. From matric cards to laptops — we've got you covered.</p>
            <div class="landing-hero-actions">
                <a href="tey/login.php" class="btn-hero-primary">🙋 Get Started</a>
                <a href="tan/verify_claim.php" class="btn-hero-outline">🔐 Admin Portal</a>
            </div>

            <!-- LIVE STATS -->
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_lost; ?></div>
                    <div class="stat-label">Lost Reports</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_found; ?></div>
                    <div class="stat-label">Found Reports</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_returned; ?></div>
                    <div class="stat-label">Items Returned</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FEATURES ===== -->
    <section class="features-section">
        <div class="app-container" style="margin: 0 auto; padding: 0 20px;">
            <div class="text-center mb-5">
                <h2 style="font-size: 32px; font-weight: 800; color: var(--text-main); letter-spacing: -0.5px;">How it works</h2>
                <p class="text-muted" style="font-size: 16px; margin-top: 10px;">Three simple steps to recover your lost item</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">📝</div>
                        <h3>1. File a Report</h3>
                        <p>Sign in with your UTM email and submit a detailed lost or found item report with photos and map location in under 2 minutes.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">⚡</div>
                        <h3>2. Auto-Matching</h3>
                        <p>Our keyword and location scoring engine automatically cross-references all reports to find the best potential matches for your item.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">🎉</div>
                        <h3>3. Secure Handover</h3>
                        <p>Submit proof of ownership. Campus Security verifies your claim and coordinates a safe handover of your item at the security office.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CTA SECTION ===== -->
    <section style="background: var(--light-bg); padding: 80px 20px;">
        <div class="app-container" style="margin: 0 auto; padding: 0 20px; max-width: 800px;">
            <div class="text-center mb-5">
                <h2 style="font-size: 32px; font-weight: 800; color: var(--text-main); letter-spacing: -0.5px;">Choose Your Access</h2>
                <p class="text-muted" style="font-size: 16px; margin-top: 10px;">Students and Campus Security have separate portals</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="cta-card">
                        <span class="cta-icon">🙋‍♂️</span>
                        <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 10px;">For Students & Staff</h3>
                        <p class="text-muted" style="font-size: 13px; line-height: 1.6; flex-grow: 1;">Sign in with your UTM email to report lost or found items, run the matching engine, view results, and submit ownership proof claims.</p>
                        <a href="tey/login.php" class="btn-custom btn-custom-primary mt-4 w-100">Sign In / Register</a>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="cta-card">
                        <span class="cta-icon">👮‍♂️</span>
                        <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 10px;">For Campus Security</h3>
                        <p class="text-muted" style="font-size: 13px; line-height: 1.6; flex-grow: 1;">Access the secure admin portal to review submitted proofs of ownership, verify claims, and log physical item handovers to verified owners.</p>
                        <a href="tan/verify_claim.php" class="btn-custom btn-custom-secondary mt-4 w-100">Admin Portal</a>
                    </div>
                </div>
            </div>

            <div class="alert-custom alert-custom-info mt-4 text-start">
                💡 <strong>Quick tip:</strong> Once logged in, you will be taken to your personal <strong>Workspace Dashboard</strong> — your control panel for filing reports, viewing matches, and checking claim statuses.
            </div>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="custom-footer">
        <p>🔍 UTM Campus Lost & Found Assistant &copy; 2026 | Web Programming Final Project</p>
        <div class="team-credits">
            <span><strong>Tey</strong> — User Authentication</span>
            <span><strong>Lee</strong> — Reporting Module</span>
            <span><strong>Syafiqah</strong> — Matching & Notifications</span>
            <span><strong>Tan</strong> — Claim Verification</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/assistant.js"></script>
</body>
</html>
