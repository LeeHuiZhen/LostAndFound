<?php
include '../../config.php';

$sql = "
SELECT *
FROM matches
";

$result = mysqli_query($conn, $sql);
?>

<h1>Matching Results</h1>

<table border="1" cellpadding="10">
<tr>
    <th>Match ID</th>
    <th>Lost Item ID</th>
    <th>Found Item ID</th>
    <th>Score</th>
    <th>Status</th>
</tr>

<?php
while($row = mysqli_fetch_assoc($result))
{
?>
<tr>
    <td><?= $row['match_id']; ?></td>
    <td><?= $row['lost_item_id']; ?></td>
    <td><?= $row['found_item_id']; ?></td>
    <td><?= $row['match_score']; ?></td>
    <td><?= $row['status']; ?></td>
</tr>
<?php
}
?>

</table>