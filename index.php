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
        body { font-family: Arial, sans-serif; background: #f4f7fc; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 40px; }
        .header p { font-size: 18px; opacity: 0.9; margin-top: 10px; }
        .container { max-width: 1200px; margin: 40px auto; padding: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; }
        .card { background: white; padding: 30px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .card:hover { transform: translateY(-5px); }
        .card .icon { font-size: 60px; margin-bottom: 15px; }
        .card h3 { font-size: 20px; color: #333; }
        .card p { color: #666; margin: 10px 0 20px; }
        .btn { display: inline-block; padding: 10px 25px; background: #667eea; color: white; text-decoration: none; border-radius: 25px; font-weight: bold; transition: background 0.3s; }
        .btn:hover { background: #5a67d8; }
        .btn-login { background: #28a745; }
        .btn-login:hover { background: #218838; }
        .btn-claim { background: #17a2b8; }
        .btn-claim:hover { background: #138496; }
        .footer { text-align: center; padding: 20px; color: #666; background: white; margin-top: 40px; }
        .user-info { background: white; padding: 15px 30px; border-radius: 10px; display: inline-block; margin-top: 10px; }
        .user-info a { color: #ffc107; text-decoration: none; margin-left: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔍 Lost and Found Assistant</h1>
        <p>Find what you've lost. Return what you've found.</p>
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>!
                <a href="/tey/login/logout.php">Logout</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="container">
        <div class="card">
            <div class="icon">🔐</div>
            <h3>Login / Register</h3>
            <p>Create an account or login to access the system</p>
            <a href="/tey/login/login.php" class="btn btn-login">Login</a>
            <a href="/tey/login/register.php" class="btn">Register</a>
        </div>

        <div class="card">
            <div class="icon">📝</div>
            <h3>Report Item</h3>
            <p>Report a lost or found item on campus</p>
            <a href="/lee/report/report_lost.php" class="btn">Report Lost</a>
            <a href="/lee/report/report_found.php" class="btn btn-login">Report Found</a>
        </div>

        <div class="card">
            <div class="icon">🔍</div>
            <h3>Check Matches</h3>
            <p>View matching items and notifications</p>
            <a href="/syafiqah/matching/dashboard.php" class="btn">Dashboard</a>
        </div>

        <div class="card">
            <div class="icon">📋</div>
            <h3>Claim Verification</h3>
            <p>Submit a claim for a matched item</p>
            <a href="/tan/claim/claim_status.php" class="btn btn-claim">My Claims</a>
            <a href="/tan/claim/verify_claim.php" class="btn">Admin</a>
        </div>
    </div>

    <div class="footer">
        <p>Lost and Found Assistant &copy; 2026 | Group WP Project</p>
        <p>Sub-paths: tey/login | lee/report | syafiqah/matching | tan/claim</p>
    </div>
</body>
</html>