<?php
include '../../tey/config.php';

$sql = "
SELECT *
FROM matches
WHERE notification_sent = FALSE
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Notifications</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
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
        }

        .notification {
            background: #e0f2fe;
            border-left: 5px solid #0284c7;
            padding: 15px;
            margin-top: 15px;
            border-radius: 8px;
        }

        a {
            display: inline-block;
            background: #4f46e5;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        a:hover {
            background: #4338ca;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Notifications</h1>

    <?php
    if ($result && $result->num_rows > 0)
    {
        while ($row = $result->fetch_assoc())
        {
            echo "<div class='notification'>";
            echo "Match Found! Lost Item ID ";
            echo $row['lost_item_id'];
            echo " matches Found Item ID ";
            echo $row['found_item_id'];
            echo "</div>";
        }
    }
    else
    {
        echo "<p>No notifications available.</p>";
    }
    ?>

    <a href="dashboard.php">Back to Dashboard</a>

</div>

</body>
</html>