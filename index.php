<?php
// Include config
require_once __DIR__ . '/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost and Found Assistant</title>
    <style>
        /* ===== RESET ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ===== HEADER / HERO ===== */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 20px 50px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: -10%;
            width: 120%;
            height: 100px;
            background: #f0f2f5;
            border-radius: 50% 50% 0 0 / 100% 100% 0 0;
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .hero p {
            font-size: 20px;
            opacity: 0.9;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .hero .user-info {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            padding: 12px 30px;
            border-radius: 50px;
            display: inline-block;
            margin-top: 10px;
            position: relative;
            z-index: 1;
        }

        .hero .user-info a {
            color: #ffd700;
            text-decoration: none;
            margin-left: 15px;
            font-weight: bold;
        }

        .hero .user-info a:hover {
            text-decoration: underline;
        }

        /* ===== STATISTICS ===== */
        .stats {
            max-width: 1000px;
            margin: -30px auto 40px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            position: relative;
            z-index: 2;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-card .label {
            font-size: 14px;
            color: #888;
            margin-top: 5px;
        }

        /* ===== QUICK ACTIONS ===== */
        .quick-actions {
            max-width: 600px;
            margin: 0 auto 40px;
            padding: 0 20px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
        }

        .quick-actions .btn-quick {
            padding: 12px 25px;
            background: white;
            color: #333;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .quick-actions .btn-quick:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-color: #667eea;
        }

        .quick-actions .btn-quick.primary {
            background: #667eea;
            color: white;
        }

        .quick-actions .btn-quick.primary:hover {
            background: #5a67d8;
            border-color: #5a67d8;
        }

        .quick-actions .btn-quick.success {
            background: #28a745;
            color: white;
        }

        .quick-actions .btn-quick.success:hover {
            background: #218838;
            border-color: #218838;
        }

        .quick-actions .btn-quick.teal {
            background: #17a2b8;
            color: white;
        }

        .quick-actions .btn-quick.teal:hover {
            background: #138496;
            border-color: #138496;
        }

        /* ===== MODULE CARDS ===== */
        .container {
            max-width: 1200px;
            margin: 0 auto 40px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 30px;
            flex: 1;
        }

        .card {
            background: white;
            padding: 35px 25px 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .card:hover::before {
            transform: scaleX(1);
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }

        .card .icon {
            font-size: 50px;
            margin-bottom: 15px;
            display: inline-block;
            transition: transform 0.4s ease;
        }

        .card:hover .icon {
            transform: scale(1.1) rotate(-5deg);
        }

        .card h3 {
            font-size: 20px;
            color: #333;
            margin-bottom: 10px;
        }

        .card p {
            color: #888;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .card .btn-group {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
        }

        .btn {
            display: inline-block;
            padding: 10px 22px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5a67d8;
            transform: scale(1.02);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
            transform: scale(1.02);
        }

        .btn-teal {
            background: #17a2b8;
            color: white;
        }
        .btn-teal:hover {
            background: #138496;
            transform: scale(1.02);
        }

        .btn-outline {
            background: transparent;
            color: #667eea;
            border-color: #667eea;
        }
        .btn-outline:hover {
            background: #667eea;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: scale(1.02);
        }

        /* ===== FOOTER ===== */
        .footer {
            background: white;
            padding: 30px 20px;
            text-align: center;
            color: #888;
            border-top: 1px solid #eee;
            margin-top: auto;
        }

        .footer .team {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-top: 10px;
        }

        .footer .team span {
            font-size: 14px;
        }

        .footer .team strong {
            color: #333;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .hero h1 { font-size: 32px; }
            .hero p { font-size: 16px; }
            .hero { padding: 40px 20px 40px; }
            .stats { margin-top: -20px; }
            .stat-card .number { font-size: 24px; }
            .container { grid-template-columns: 1fr; max-width: 400px; }
            .quick-actions .btn-quick { font-size: 14px; padding: 10px 18px; }
        }

        @media (max-width: 480px) {
            .hero h1 { font-size: 26px; }
            .card { padding: 25px 20px; }
            .btn { font-size: 13px; padding: 8px 16px; }
        }
    </style>
</head>
<body>

    <!-- ===== HERO SECTION ===== -->
    <div class="hero">
        <h1>🔍 Lost and Found Assistant</h1>
        <p>Find what you've lost. Return what you've found.</p>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-info">
                👋 Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>!
                <a href="/tey/logout.php">🚪 Logout</a>
            </div>
        <?php else: ?>
            <div class="user-info">
                👋 Welcome, Guest!
                <a href="/tey/login.php">🔐 Login</a>
                <a href="/tey/register.php">📝 Register</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- ===== STATISTICS ===== -->
    <div class="stats">
        <?php
        // Get real stats from database
        $lost_count = 0;
        $found_count = 0;
        $match_count = 0;
        $return_count = 0;

        if (isset($conn)) {
            $lost_result = $conn->query("SELECT COUNT(*) as count FROM lost_items");
            if ($lost_result) { $lost_count = $lost_result->fetch_assoc()['count']; }

            $found_result = $conn->query("SELECT COUNT(*) as count FROM found_items");
            if ($found_result) { $found_count = $found_result->fetch_assoc()['count']; }

            $match_result = $conn->query("SELECT COUNT(*) as count FROM matches");
            if ($match_result) { $match_count = $match_result->fetch_assoc()['count']; }

            $return_result = $conn->query("SELECT COUNT(*) as count FROM claims WHERE status = 'returned'");
            if ($return_result) { $return_count = $return_result->fetch_assoc()['count']; }
        }
        ?>
        <div class="stat-card">
            <div class="number"><?php echo $lost_count; ?></div>
            <div class="label">📦 Lost Items</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $found_count; ?></div>
            <div class="label">✅ Found Items</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $match_count; ?></div>
            <div class="label">🔗 Matches Found</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $return_count; ?></div>
            <div class="label">🎉 Items Returned</div>
        </div>
    </div>

    <!-- ===== QUICK ACTIONS ===== -->
    <div class="quick-actions">
        <a href="/lee/report_lost.php" class="btn-quick success">📝 Report Lost</a>
        <a href="/lee/report_found.php" class="btn-quick primary">📦 Report Found</a>
        <a href="/tan/claim_status.php" class="btn-quick teal">📋 My Claims</a>
        <a href="/syafiqah/matching/dashboard.php" class="btn-quick">🔍 Check Matches</a>
    </div>

    <!-- ===== MODULE CARDS ===== -->
    <div class="container">
        <!-- Card 1: Login / Register -->
        <div class="card">
            <div class="icon">🔐</div>
            <h3>Login / Register</h3>
            <p>Create an account or login to access the full system</p>
            <div class="btn-group">
                <a href="/tey/login.php" class="btn btn-success">Login</a>
                <a href="/tey/register.php" class="btn btn-outline">Register</a>
            </div>
        </div>

        <!-- Card 2: Report Item -->
        <div class="card">
            <div class="icon">📝</div>
            <h3>Report Item</h3>
            <p>Report a lost or found item on campus instantly</p>
            <div class="btn-group">
                <a href="/lee/report_lost.php" class="btn btn-outline">Report Lost</a>
                <a href="/lee/report_found.php" class="btn btn-success">Report Found</a>
            </div>
        </div>

        <!-- Card 3: Check Matches -->
        <div class="card">
            <div class="icon">🔍</div>
            <h3>Check Matches</h3>
            <p>View matching items and get notifications</p>
            <div class="btn-group">
                <a href="/syafiqah/matching/dashboard.php" class="btn btn-primary">Dashboard</a>
            </div>
        </div>

        <!-- Card 4: Claim Verification -->
        <div class="card">
            <div class="icon">📋</div>
            <h3>Claim Verification</h3>
            <p>Submit a claim for a matched item</p>
            <div class="btn-group">
                <a href="/tan/claim_status.php" class="btn btn-teal">My Claims</a>
                <a href="/tan/verify_claim.php" class="btn btn-secondary">Admin</a>
            </div>
        </div>
    </div>

    <!-- ===== FOOTER ===== -->
    <div class="footer">
        <p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming Project</p>
        <div class="team">
            <span><strong>👨‍💻 Tey</strong> Login Module</span>
            <span><strong>👩‍💻 Lee</strong> Report Module</span>
            <span><strong>👩‍💻 Syafiqah</strong> Matching Module</span>
            <span><strong>👩‍💻 Tan</strong> Claim Module</span>
        </div>
        <p style="margin-top: 10px; font-size: 12px;">
            📍 Sub-paths: /tey/login | /lee/report | /syafiqah/matching | /tan/claim
        </p>
    </div>

</body>
</html>
