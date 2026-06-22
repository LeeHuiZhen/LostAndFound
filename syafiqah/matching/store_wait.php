<?php
include '../../tey/config.php';

$sql = "
SELECT *
FROM lost_items
WHERE status='pending'
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pending Reports</title>

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

        .item {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border: 1px solid #ddd;
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

    <h1>Pending Lost Reports</h1>

    <?php
    if ($result && $result->num_rows > 0)
    {
        while ($row = $result->fetch_assoc())
        {
            echo "<div class='item'>";
            echo $row['item_name'];
            echo " - Waiting for a match";
            echo "</div>";
        }
    }
    else
    {
        echo "<p>No pending reports available.</p>";
    }
    ?>

    <a href="dashboard.php">Back to Dashboard</a>

</div>

</body>
</html>