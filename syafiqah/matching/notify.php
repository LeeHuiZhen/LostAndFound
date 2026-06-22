<?php
include '../../config.php';

$sql = "
SELECT *
FROM matches
WHERE notification_sent = FALSE
";

$result = mysqli_query($conn, $sql);
?>

<h1>Notifications</h1>

<?php

if(mysqli_num_rows($result) > 0)
{
    while($row = mysqli_fetch_assoc($result))
    {
        echo "<p>";
        echo "Match Found! ";
        echo "Lost Item ID ";
        echo $row['lost_item_id'];
        echo " matches Found Item ID ";
        echo $row['found_item_id'];
        echo "</p>";
    }
}
else
{
    echo "No notifications.";
}
?>