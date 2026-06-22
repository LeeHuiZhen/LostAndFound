<?php
include '../../tey/config.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Matching Dashboard</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 40px;
        }

        .container {
            max-width: 700px;
            background: white;
            margin: auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        a {
            display: block;
            background: #4f46e5;
            color: white;
            text-decoration: none;
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        a:hover {
            background: #4338ca;
        }
    </style>
</head>

<body>

<div class="container">
    <h1>Matching & Notification Dashboard</h1>

    <a href="match_engine.php">Run Matching Engine</a>

    <a href="display_match.php">View Matching Results</a>

    <a href="notify.php">View Notifications</a>

    <a href="store_wait.php">Pending Reports</a>
</div>

</body>
</html>