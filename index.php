<?php
session_start();
require_once 'config.php';

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("Location: syafiqah/matching/dashboard.php");
    exit;
}

// Fetch live stats from database
$total_lost     = $conn->query("SELECT COUNT(*) FROM lost_items")->fetch_row()[0] ?? 0;
$total_found    = $conn->query("SELECT COUNT(*) FROM found_items")->fetch_row()[0] ?? 0;
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
    <!-- ?v=3 busts the browser/server cache so the new CSS is always loaded -->
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <style>
        /* ===== CRITICAL INLINE STYLES (fallback if CDN CSS is slow) ===== */
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap');

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Outfit', sans-serif;
            background: #f1f5f9;
        }

        /* ── Navbar ─────────────────────────────────────────────── */
        .custom-navbar {
            background: rgba(15, 23, 42, 0.97);
            backdrop-filter: blur(20px);
            padding: 14px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 24px rgba(0,0,0,0.35);
        }

        .custom-navbar .brand {
            font-size: 18px;
            font-weight: 800;
            background: linear-gradient(135deg, #818cf8, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
        }

        .custom-navbar .nav-links { display: flex; align-items: center; gap: 10px; }

        .custom-navbar .nav-links a:not(.btn-hero-nav) {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 8px;
        }

        .custom-navbar .nav-links a:not(.btn-hero-nav):hover {
            color: white;
            background: rgba(255,255,255,0.08);
        }

        .btn-hero-nav {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white !important;
            font-weight: 700 !important;
            font-size: 13px !important;
            padding: 8px 20px !important;
            border-radius: 50px !important;
            text-decoration: none !important;
            -webkit-text-fill-color: white !important;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            box-shadow: 0 4px 14px rgba(79,70,229,0.35);
        }

        .btn-hero-nav:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79,70,229,0.45);
        }

        /* ── Hero Section ───────────────────────────────────────── */
        .hero-section {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 45%, #312e81 100%);
            min-height: 88vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 60px 20px;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 25% 45%, rgba(99,102,241,0.25) 0%, transparent 50%),
                radial-gradient(circle at 75% 20%, rgba(167,139,250,0.18) 0%, transparent 45%),
                radial-gradient(circle at 55% 80%, rgba(79,70,229,0.12) 0%, transparent 40%);
            pointer-events: none;
        }

        .hero-inner {
            position: relative;
            z-index: 2;
            max-width: 700px;
            width: 100%;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.18);
            backdrop-filter: blur(8px);
            border-radius: 50px;
            padding: 7px 18px;
            font-size: 13px;
            color: rgba(255,255,255,0.88);
            font-weight: 600;
            margin-bottom: 28px;
            letter-spacing: 0.3px;
        }

        .hero-title {
            font-size: 54px;
            font-weight: 900;
            color: white;
            letter-spacing: -2px;
            line-height: 1.1;
            margin-bottom: 20px;
        }

        .hero-title .accent {
            background: linear-gradient(135deg, #a5b4fc, #c4b5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-sub {
            font-size: 17px;
            color: rgba(255,255,255,0.72);
            line-height: 1.7;
            margin-bottom: 36px;
        }

        .hero-btns {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-white {
            background: white;
            color: #4f46e5;
            font-weight: 700;
            font-size: 15px;
            padding: 14px 32px;
            border-radius: 50px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }

        .btn-white:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 32px rgba(0,0,0,0.3);
            color: #4338ca;
        }

        .btn-glass {
            background: rgba(255,255,255,0.1);
            color: white;
            font-weight: 700;
            font-size: 15px;
            padding: 14px 32px;
            border-radius: 50px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 2px solid rgba(255,255,255,0.35);
            transition: all 0.25s;
        }

        .btn-glass:hover {
            background: rgba(255,255,255,0.18);
            border-color: rgba(255,255,255,0.65);
            color: white;
            transform: translateY(-2px);
        }

        /* ── Stats Bar ──────────────────────────────────────────── */
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 48px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 18px;
            padding: 22px 36px;
            margin-top: 48px;
        }

        .stat-num {
            font-size: 30px;
            font-weight: 900;
            color: white;
            letter-spacing: -1px;
            line-height: 1;
        }

        .stat-lbl {
            font-size: 11px;
            color: rgba(255,255,255,0.55);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-top: 5px;
        }

        /* ── How it works ───────────────────────────────────────── */
        .features-section {
            background: white;
            padding: 80px 20px;
        }

        .feature-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 30px 24px;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
        }

        .feature-card:hover {
            background: white;
            box-shadow: 0 20px 40px rgba(79,70,229,0.12);
            transform: translateY(-6px);
            border-color: #c7d2fe;
        }

        .feature-icon {
            font-size: 40px;
            width: 72px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #ede9fe, #ddd6fe);
        }

        .feature-card h3 {
            font-size: 17px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .feature-card p {
            font-size: 13px;
            color: #64748b;
            line-height: 1.6;
        }

        /* ── CTA Section ────────────────────────────────────────── */
        .cta-section {
            background: #f1f5f9;
            padding: 80px 20px;
        }

        .cta-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 32px 28px;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }

        .cta-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(79,70,229,0.15);
            border-color: #c7d2fe;
        }

        .cta-icon {
            font-size: 44px;
            margin-bottom: 16px;
            display: block;
        }

        .info-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 16px 20px;
            font-size: 13px;
            color: #0c4a6e;
            line-height: 1.6;
        }

        /* ── Footer ─────────────────────────────────────────────── */
        .site-footer {
            background: #0f172a;
            padding: 36px 20px;
            text-align: center;
        }

        .site-footer p {
            font-size: 13px;
            color: rgba(255,255,255,0.4);
        }

        .team-credits {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px 25px;
            margin-top: 14px;
            font-size: 12px;
        }

        .team-credits span { color: rgba(255,255,255,0.35); }
        .team-credits strong { color: #a5b4fc; }

        /* ── Responsive ─────────────────────────────────────────── */
        @media (max-width: 768px) {
            .hero-title { font-size: 34px; letter-spacing: -1px; }
            .hero-sub   { font-size: 15px; }
            .stats-bar  { flex-direction: column; gap: 20px; padding: 20px; }
            .custom-navbar { padding: 12px 16px; }
        }
    </style>
</head>
<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="custom-navbar">
        <a href="index.php" class="brand">🔍 UTM Lost & Found</a>
        <div class="nav-links">
            <a href="tan/verify_claim.php">🔐 Admin Portal</a>
            <a href="tey/login.php" class="btn-hero-nav">Sign In</a>
        </div>
    </nav>

    <!-- ===== HERO SECTION ===== -->
    <section class="hero-section">
        <div class="hero-inner">

            <div class="hero-badge">🎓 UTM Campus Digital Platform</div>

            <h1 class="hero-title">
                Lost Something on <span class="accent">Campus?</span>
            </h1>

            <p class="hero-sub">
                Our AI-powered platform helps you report, match, and recover lost items across UTM Skudai. From matric cards to laptops — we've got you covered.
            </p>

            <div class="hero-btns">
                <a href="tey/login.php" class="btn-white">🙋 Get Started</a>
                <a href="tan/verify_claim.php" class="btn-glass">🔐 Admin Portal</a>
            </div>

            <!-- Live Stats Bar -->
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-num"><?php echo $total_lost; ?></div>
                    <div class="stat-lbl">Lost Reports</div>
                </div>
                <div class="stat-item">
                    <div class="stat-num"><?php echo $total_found; ?></div>
                    <div class="stat-lbl">Found Reports</div>
                </div>
                <div class="stat-item">
                    <div class="stat-num"><?php echo $total_returned; ?></div>
                    <div class="stat-lbl">Items Returned</div>
                </div>
            </div>

        </div>
    </section>

    <!-- ===== FEATURES SECTION ===== -->
    <section class="features-section">
        <div style="max-width: 1000px; margin: 0 auto; padding: 0 20px;">
            <div class="text-center mb-5">
                <h2 style="font-size: 32px; font-weight: 800; color: #1e293b; letter-spacing: -0.5px;">How it works</h2>
                <p style="font-size: 16px; color: #64748b; margin-top: 10px;">Three simple steps to recover your lost item</p>
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
                        <p>Submit proof of ownership. Campus Security verifies your claim and coordinates a safe handover at the security office.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CTA SECTION ===== -->
    <section class="cta-section">
        <div style="max-width: 800px; margin: 0 auto; padding: 0 20px;">
            <div class="text-center mb-5">
                <h2 style="font-size: 32px; font-weight: 800; color: #1e293b; letter-spacing: -0.5px;">Choose Your Access</h2>
                <p style="font-size: 16px; color: #64748b; margin-top: 10px;">Students and Campus Security have separate portals</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="cta-card">
                        <span class="cta-icon">🙋‍♂️</span>
                        <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 10px; color: #1e293b;">For Students & Staff</h3>
                        <p style="font-size: 13px; color: #64748b; line-height: 1.6; flex-grow: 1;">Sign in with your UTM email to report lost or found items, run the matching engine, view results, and submit ownership proof claims.</p>
                        <a href="tey/login.php" style="display: block; background: linear-gradient(135deg,#4f46e5,#7c3aed); color: white; text-align: center; padding: 13px; border-radius: 50px; font-weight: 700; text-decoration: none; margin-top: 20px; font-size: 14px;">Sign In / Register</a>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="cta-card">
                        <span class="cta-icon">👮‍♂️</span>
                        <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 10px; color: #1e293b;">For Campus Security</h3>
                        <p style="font-size: 13px; color: #64748b; line-height: 1.6; flex-grow: 1;">Access the secure admin portal to review submitted proof documents, verify claims, and log physical item handovers to verified owners.</p>
                        <a href="tan/verify_claim.php" style="display: block; background: #475569; color: white; text-align: center; padding: 13px; border-radius: 50px; font-weight: 700; text-decoration: none; margin-top: 20px; font-size: 14px;">Admin Portal</a>
                    </div>
                </div>
            </div>

            <div class="info-box mt-4">
                💡 <strong>Quick tip:</strong> Once logged in, you will be taken to your personal <strong>Workspace Dashboard</strong> — your control panel for filing reports, viewing matches, and checking claim statuses.
            </div>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="site-footer">
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
