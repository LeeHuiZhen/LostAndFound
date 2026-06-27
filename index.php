<?php
session_start();
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("Location: syafiqah/matching/dashboard.php");
    exit;
}
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTM Campus Lost & Found Assistant</title>
    <meta name="description" content="Digitalizing lost-and-found recovery for UTM campus students and faculty. Rapid keyword matching, secure proof verification.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background-color: var(--bg-base); overflow-x: hidden; }

        /* ===== LANDING HERO ===== */
        .landing-hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 80px 20px 60px;
            text-align: center;
        }

        .landing-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(99,102,241,0.2) 0%, transparent 55%),
                radial-gradient(ellipse at 80% 20%, rgba(139,92,246,0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 60% 80%, rgba(16,185,129,0.08) 0%, transparent 40%);
            pointer-events: none;
        }

        .landing-hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(99,102,241,0.1) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
            opacity: 0.6;
        }

        .hero-content { position: relative; z-index: 1; max-width: 760px; }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(99,102,241,0.12);
            border: 1px solid rgba(99,102,241,0.25);
            border-radius: var(--r-full);
            padding: 6px 16px;
            font-size: 12px;
            font-weight: 600;
            color: var(--primary-light);
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 24px;
        }

        .hero-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 64px;
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -2px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fff 0%, #c7d2fe 60%, var(--primary-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 18px;
            color: var(--text-muted);
            line-height: 1.7;
            max-width: 560px;
            margin: 0 auto 40px;
        }

        .hero-cta {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 60px;
        }

        .hero-stats {
            display: flex;
            gap: 40px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero-stat {
            text-align: center;
        }
        .hero-stat h3 {
            font-size: 28px;
            font-weight: 800;
            background: var(--grad-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-stat p { font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

        /* ===== CARDS SECTION ===== */
        .portal-cards {
            padding: 80px 20px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .section-label {
            text-align: center;
            margin-bottom: 50px;
        }
        .section-label h2 {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: -0.8px;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        .section-label p { font-size: 15px; color: var(--text-muted); }

        .portal-card {
            background: var(--glass-bg);
            border: 1px solid var(--border);
            border-radius: var(--r-xl);
            padding: 40px 36px;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .portal-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: var(--grad-primary);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .portal-card.student::before { background: var(--grad-primary); }
        .portal-card.admin::before   { background: var(--grad-success); }

        .portal-card:hover {
            border-color: var(--glass-border-hover);
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg), var(--shadow-glow-sm);
        }

        .portal-card:hover::before { opacity: 1; }

        .portal-card .card-icon-wrap {
            width: 72px; height: 72px;
            border-radius: var(--r-lg);
            display: flex; align-items: center; justify-content: center;
            font-size: 32px;
            margin-bottom: 24px;
        }

        .portal-card.student .card-icon-wrap { background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.2); }
        .portal-card.admin .card-icon-wrap   { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.2); }

        .portal-card h3 { font-size: 22px; font-weight: 700; color: var(--text-primary); margin-bottom: 12px; }
        .portal-card p  { font-size: 14px; color: var(--text-muted); line-height: 1.7; flex-grow: 1; margin-bottom: 28px; }

        /* ===== HOW IT WORKS ===== */
        .how-it-works {
            padding: 80px 20px;
            max-width: 900px;
            margin: 0 auto;
        }

        .step-card {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            padding: 24px;
            background: var(--glass-bg);
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            margin-bottom: 16px;
            transition: all 0.2s;
        }
        .step-card:hover { border-color: var(--glass-border-hover); background: var(--glass-bg-hover); }

        .step-num {
            width: 40px; height: 40px; flex-shrink: 0;
            border-radius: 50%;
            background: var(--grad-primary);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 16px; font-weight: 700; color: #fff;
            box-shadow: var(--shadow-glow-sm);
        }

        .step-card h4 { font-size: 15px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px; }
        .step-card p  { font-size: 13px; color: var(--text-muted); margin: 0; line-height: 1.6; }

        /* ===== LANDING NAVBAR ===== */
        .landing-nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 64px;
            background: rgba(7,11,20,0.75);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            z-index: 1000;
        }

        @media (max-width: 768px) {
            .hero-title { font-size: 40px; letter-spacing: -1px; }
            .landing-nav { padding: 0 20px; }
            .hero-stats { gap: 24px; }
        }
    </style>
</head>
<body>

    <!-- ===== FIXED NAV ===== -->
    <nav class="landing-nav">
        <a href="index.php" style="font-family:'Space Grotesk',sans-serif; font-size:17px; font-weight:700; color:var(--text-primary); display:flex; align-items:center; gap:10px;">
            🔍 UTM Lost &amp; Found
        </a>
        <div style="display:flex; align-items:center; gap:12px;">
            <a href="tey/login.php" class="btn-custom btn-custom-outline" style="padding:8px 20px; font-size:13px;">Sign In</a>
            <a href="tan/verify_claim.php" class="btn-custom btn-custom-secondary" style="padding:8px 20px; font-size:13px;">Admin</a>
        </div>
    </nav>

    <!-- ===== HERO ===== -->
    <section class="landing-hero">
        <div class="hero-content animate-fade-up">
            <div class="hero-eyebrow">
                <span class="glow-dot"></span>
                UTM Skudai Campus · Digital Recovery System
            </div>
            <h1 class="hero-title">Never Lose What<br>Matters Again</h1>
            <p class="hero-subtitle">
                AI-powered lost &amp; found recovery for UTM students and faculty. Instant keyword matching, secure ownership verification, and real-time alerts.
            </p>
            <div class="hero-cta">
                <a href="tey/login.php" class="btn-custom btn-custom-primary" style="padding:14px 36px; font-size:15px;">
                    🚀 Get Started — It's Free
                </a>
                <a href="tan/verify_claim.php" class="btn-custom btn-custom-outline" style="padding:14px 28px; font-size:15px;">
                    🔐 Admin Portal
                </a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <h3>24/7</h3>
                    <p>AI Matching Engine</p>
                </div>
                <div class="hero-stat" style="border-left:1px solid var(--border); padding-left:40px;">
                    <h3>100%</h3>
                    <p>Secure Claims</p>
                </div>
                <div class="hero-stat" style="border-left:1px solid var(--border); padding-left:40px;">
                    <h3>Real-Time</h3>
                    <p>Email Alerts</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== PORTAL CARDS ===== -->
    <section class="portal-cards">
        <div class="section-label">
            <h2>Choose Your Portal</h2>
            <p>Two dedicated access points designed for their specific users</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="portal-card student">
                    <div class="card-icon-wrap">🎓</div>
                    <h3>Students & Staff</h3>
                    <p>Report lost or found items, run the intelligent matching engine, review potential item matches, and submit ownership proof claims — all from one workspace dashboard.</p>
                    <a href="tey/login.php" class="btn-custom btn-custom-primary w-100">Sign In / Register →</a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="portal-card admin">
                    <div class="card-icon-wrap">👮</div>
                    <h3>Campus Security</h3>
                    <p>Access the secure administrator portal to review submitted proof documents, verify or reject ownership claims, and log physical item handovers with a full audit trail.</p>
                    <a href="tan/verify_claim.php" class="btn-custom btn-custom-success w-100">Admin Portal →</a>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== HOW IT WORKS ===== -->
    <section class="how-it-works">
        <div class="section-label">
            <h2>How It Works</h2>
            <p>From reporting to reunion in four simple steps</p>
        </div>
        <div class="step-card">
            <div class="step-num">1</div>
            <div>
                <h4>Report Your Item</h4>
                <p>Sign in and file a Lost or Found report with a photo, description, and interactive campus map pin. Our Vision AI tags the item automatically.</p>
            </div>
        </div>
        <div class="step-card">
            <div class="step-num">2</div>
            <div>
                <h4>Matching Engine Runs</h4>
                <p>The system scores every lost-found pair using name, tags, location, and description similarity. Matches above 40% are instantly linked and you receive an alert.</p>
            </div>
        </div>
        <div class="step-card">
            <div class="step-num">3</div>
            <div>
                <h4>Submit Ownership Proof</h4>
                <p>When a match is found, you describe the item in detail and upload a receipt, photo, or serial number — sent securely to campus security for review.</p>
            </div>
        </div>
        <div class="step-card">
            <div class="step-num">4</div>
            <div>
                <h4>Collect Your Item</h4>
                <p>Once verified, you receive a notification to collect the item from the UTM Security Office. The system logs the handover and closes the case.</p>
            </div>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="custom-footer">
        <p>🔍 UTM Campus Lost &amp; Found Assistant &copy; 2026 | Web Programming Final Project</p>
        <div class="team-credits">
            <span><strong>Tey</strong> — User Authentication</span>
            <span><strong>Lee</strong> — Report Module</span>
            <span><strong>Syafiqah</strong> — Matching &amp; Notifications</span>
            <span><strong>Tan</strong> — Claim Verification</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/assistant.js"></script>
</body>
</html>
