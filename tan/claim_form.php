<?php
// Get the correct path to config.php
include '../config.php';
if (file_exists($config_path)) {
    require_once $config_path;
} else {
    die("ERROR: config.php not found at: " . $config_path);
}

if (!isset($conn) || $conn->connect_error) {
    die("ERROR: Database connection failed.");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /tey/login/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT c.*, m.match_id, 
               li.item_name as lost_item, li.description as lost_desc,
               fi.item_name as found_item, fi.description as found_desc, fi.photo_url
        FROM claims c 
        JOIN matches m ON c.match_id = m.match_id
        JOIN lost_items li ON m.lost_item_id = li.item_id
        JOIN found_items fi ON m.found_item_id = fi.item_id
        WHERE c.owner_id = $user_id
        ORDER BY c.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Claims</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial; background: #f4f7fc; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #333; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #007bff; color: white; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f1f1f1; }
        .badge { display: inline-block; padding: 3px 12px; border-radius: 15px; font-size: 12px; font-weight: bold; }
        .badge-pending { background: #ffc107; color: #000; }
        .badge-verified { background: #28a745; color: #fff; }
        .badge-rejected { background: #dc3545; color: #fff; }
        .badge-returned { background: #17a2b8; color: #fff; }
        .btn { display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .btn:hover { background: #5a6268; }
    </style>
</head>
<body>
<div class="container">
    <h1>📋 My Claim Status</h1>
    <?php if ($result && $result->num_rows > 0): ?>
    <table>
        <tr>
            <th>Claim ID</th>
            <th>Lost Item</th>
            <th>Found Item</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><strong><?php echo $row['claim_id']; ?></strong></td>
            <td><?php echo htmlspecialchars($row['lost_item']); ?></td>
            <td><?php echo htmlspecialchars($row['found_item']); ?></td>
            <td><?php 
                $status = $row['status'];
                if ($status == 'pending') echo '<span class="badge badge-pending">⏳ Pending</span>';
                elseif ($status == 'verified') echo '<span class="badge badge-verified">✅ Verified</span>';
                elseif ($status == 'rejected') echo '<span class="badge badge-rejected">❌ Rejected</span>';
                elseif ($status == 'returned') echo '<span class="badge badge-returned">🎉 Returned</span>';
            ?></td>
            <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
        <p style="text-align:center; padding:40px; color:#666;">You have not submitted any claims yet.</p>
    <?php endif; ?>
    <br>
    <a href="/index.php" class="btn">⬅ Back to Home</a>
</div>
</body>
</html>
