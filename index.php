<?php

session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: tey/login.php");
    exit;
}
?>
    
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost & Found System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { display: flex; min-height: 100vh; background: #f4f6f9; }
        .sidebar { width: 250px; background: #2c3e50; color: white; padding: 20px; }
        .sidebar h2 { text-align: center; margin-bottom: 30px; }
        .sidebar a { display: block; color: #bdc3c7; padding: 15px; text-decoration: none; border-radius: 5px; }
        .sidebar a:hover { background: #34495e; color: white; }
        .main-content { flex: 1; padding: 30px; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>L&F System</h2>
        <a href="index.php">Dashboard</a>
        <a href="lee/report_lost.php">Report Item</a>
        <a href="syafiqah/matching/dashboard.php">View Matches</a>
        <a href="tan/claim_status.php">My Claims</a>
        <a href="tey/logout.php" style="color: #e74c3c;">Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION["user_name"]); ?></h1>
        </div>
        <p>Use the sidebar to navigate through your modules.</p>
    </div>

</body>
</html>
