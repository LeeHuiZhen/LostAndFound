<?php

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
        
        /* Sidebar */
        .sidebar { width: 250px; background: #2c3e50; color: white; padding: 20px; }
        .sidebar h2 { text-align: center; margin-bottom: 30px; font-size: 1.2rem; }
        .sidebar a { display: block; color: #bdc3c7; padding: 15px; text-decoration: none; border-radius: 5px; transition: 0.3s; }
        .sidebar a:hover { background: #34495e; color: white; }
        
        /* Main Content */
        .main-content { flex: 1; padding: 30px; }
        .header { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 30px; }
        
        /* Dashboard Cards */
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); text-align: center; }
        .card h3 { margin-bottom: 15px; color: #34495e; }
        .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; }
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

        <div class="grid">
            <div class="card">
                <h3>Report Lost/Found</h3>
                <p>Register a lost or found item to the system.</p><br>
                <a href="lee/report_lost.php" class="btn">Get Started</a>
            </div>
            <div class="card">
                <h3>Check Matches</h3>
                <p>See items that have been matched for you.</p><br>
                <a href="syafiqah/matching/dashboard.php" class="btn">View Matches</a>
            </div>
            <div class="card">
                <h3>Manage Claims</h3>
                <p>Track your claims and verify ownership.</p><br>
                <a href="tan/claim_status.php" class="btn">View Claims</a>
            </div>
        </div>
    </div>

</body>
</html>
