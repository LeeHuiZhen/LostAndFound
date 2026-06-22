<?php
include '../../config.php';

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

$result = mysqli_query($conn, $sql);

$count = 0;

while ($row = mysqli_fetch_assoc($result))
{
    $lost_id = $row['lost_id'];
    $found_id = $row['found_id'];

    $insert = "
    INSERT INTO matches
    (lost_item_id, found_item_id, match_score)
    VALUES
    ($lost_id, $found_id, 100)
    ";

    mysqli_query($conn, $insert);

    $count++;
}

echo "<h1>Matching Engine</h1>";
echo "$count match(es) created.";
?>