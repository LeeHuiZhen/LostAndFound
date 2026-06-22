<?php
include '../../tey/config.php';

$sql = "
SELECT
l.item_id AS lost_id,
f.item_id AS found_id
FROM lost_items l
JOIN found_items f
ON l.item_name = f.item_name
WHERE l.status='pending'
AND f.status='pending'
";

$result = $conn->query($sql);

$count = 0;

if ($result && $result->num_rows > 0)
{
    while ($row = $result->fetch_assoc())
    {
        $count++;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Matching Engine</title>

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
            text-align: center;
        }

        h1 {
            color: #333;
        }

        p {
            font-size: 18px;
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
    <h1>Matching Engine</h1>

    <p>
        <?php echo $count; ?> potential match(es) found.
    </p>

    <a href="dashboard.php">Back to Dashboard</a>
</div>

</body>
</html>