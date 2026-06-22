<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lost & Found Portal</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; }
        .nav-box { border: 1px solid #ddd; padding: 20px; margin: 10px; display: inline-block; width: 200px; border-radius: 8px; }
        a { text-decoration: none; color: #007bff; font-weight: bold; }
    </style>
</head>
<body>

    <h1>Lost & Found Portal</h1>

    <?php if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true): ?>
        <p>Please <a href="tey/login.php">Login</a> to continue.</p>
    <?php else: ?>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION["user_name"]); ?>!</p>
        
        <div class="nav-box">
            <h3>Lee's Module</h3>
            <p><a href="lee/report_lost.php">Report Lost</a></p>
            <p><a href="lee/report_found.php">Report Found</a></p>
        </div>

        <div class="nav-box">
            <h3>Syafiqah's Module</h3>
            <p><a href="syafiqah/matching/dashboard.php">Matching Dashboard</a></p>
        </div>

        <div class="nav-box">
            <h3>Tan's Module</h3>
            <p><a href="tan/claim_status.php">My Claims</a></p>
        </div>

        <br><br>
        <a href="tey/logout.php">Logout</a>
    <?php endif; ?>

</body>
</html>