<?php
include '../../config.php';

$sql = "
SELECT *
FROM lost_items
WHERE status='pending'
";

$result = mysqli_query($conn, $sql);
?>

<h1>Pending Lost Reports</h1>

<?php

if(mysqli_num_rows($result) > 0)
{
    while($row = mysqli_fetch_assoc($result))
    {
        echo "<p>";
        echo $row['item_name'];
        echo " - Waiting for match";
        echo "</p>";
    }
}
else
{
    echo "No pending reports.";
}
?>