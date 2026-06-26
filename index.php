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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f7fc; min-height: 100vh; display: flex; flex-direction: column; }
        
        /* ===== HEADER ===== */
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 50px 20px; text-align: center; }
        .header h1 { font-size: 44px; }
        .header p { font-size: 18px; opacity: 0.9; margin-top: 10px; }

        /* ===== MODULE CARDS ===== */
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; flex: 1; }
        
        .card { background: white; padding: 35px 25px 30px; border-radius: 20px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.08); transition: all 0.3s ease; }
        .card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.12); }
        .card .icon { font-size: 50px; margin-bottom: 15px; display: inline-block; }
        .card:hover .icon { transform: scale(1.1); }
        .card h3 { font-size: 20px; color: #333; margin-bottom: 10px; }
        .card p { color: #888; font-size: 14px; margin-bottom: 20px; line-height: 1.6; }
        
        .btn-group { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; }
        .btn { display: inline-block; padding: 10px 22px; border-radius: 50px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s; border: 2px solid transparent; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5a67d8; transform: scale(1.02); }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; transform: scale(1.02); }
        .btn-teal { background: #17a2b8; color: white; }
        .btn-teal:hover { background: #138496; transform: scale(1.02); }
        .btn-outline { background: transparent; color: #667eea; border-color: #667eea; }
        .btn-outline:hover { background: #667eea; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; transform: scale(1.02); }

        /* ===== FOOTER ===== */
        .footer { background: white; padding: 20px; text-align: center; color: #888; border-top: 1px solid #eee; margin-top: auto; }
        .footer .team { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; margin-top: 10px; font-size: 14px; }
        .footer .team strong { color: #333; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .header h1 { font-size: 28px; }
            .header p { font-size: 16px; }
            .container { grid-template-columns: 1fr; max-width: 400px; }
        }
        @media (max-width: 480px) {
            .header h1 { font-size: 22px; }
            .card { padding: 25px 20px; }
            .btn { font-size: 13px; padding: 8px 16px; }
        }
    </style>
</head>
<body>

    <!-- ===== HEADER ===== -->
    <div class="header">
        <h1>🔍 Lost and Found Assistant</h1>
        <p>Find what you've lost. Return what you've found.</p>
    </div>

    <!-- ===== MODULE CARDS ===== -->
    <div class="container">
        <!-- Card 1: Login / Register -->
        <div class="card">
            <div class="icon">🔐</div>
            <h3>Login / Register</h3>
            <p>Create an account or login to access the system</p>
            <div class="btn-group">
                <a href="/tey/login.php" class="btn btn-success">Login</a>
                <a href="/tey/register.php" class="btn btn-outline">Register</a>
            </div>
        </div>

        <!-- Card 2: Report Item -->
        <div class="card">
            <div class="icon">📝</div>
            <h3>Report Item</h3>
            <p>Report a lost or found item on campus</p>
            <div class="btn-group">
                <a href="/lee/report_lost.php" class="btn btn-success">Report Lost</a>
                <a href="/lee/report_found.php" class="btn btn-success">Report Found</a>
            </div>
        </div>

        <!-- Card 3: Check Matches -->
        <div class="card">
            <div class="icon">🔍</div>
            <h3>Check Matches</h3>
            <p>View matching items and notifications</p>
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
            <span><strong>Tey</strong> Login Module</span>
            <span><strong>Lee</strong> Report Module</span>
            <span><strong>Syafiqah</strong> Matching Module</span>
            <span><strong>Tan</strong> Claim Module</span>
        </div>
    </div>

</body>
</html>
