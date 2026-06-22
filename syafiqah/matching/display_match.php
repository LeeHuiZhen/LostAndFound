<?php
include '../../tey/config.php';

$sql = "SELECT * FROM matches";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Matching Results</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 40px;
        }

        .container {
            max-width: 900px;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        th {
            background: #4f46e5;
            color: white;
            padding: 12px;
        }

        td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
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
    <h1>Matching Results</h1>

    <table>
        <tr>
            <th>Match ID</th>
            <th>Lost Item ID</th>
            <th>Found Item ID</th>
            <th>Score</th>
            <th>Status</th>
        </tr>

        <?php
        if ($result && $result->num_rows > 0)
        {
            while ($row = $result->fetch_assoc())
            {
                echo "<tr>";
                echo "<td>".$row['match_id']."</td>";
                echo "<td>".$row['lost_item_id']."</td>";
                echo "<td>".$row['found_item_id']."</td>";
                echo "<td>".$row['match_score']."</td>";
                echo "<td>".$row['status']."</td>";
                echo "</tr>";
            }
        }
        ?>
    </table>

    <a href="dashboard.php">Back to Dashboard</a>
</div>

</body>
</html>