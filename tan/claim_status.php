<?php
session_start();
include '../../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../tey/login/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all claims for this user
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Claims - Lost and Found Assistant</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; }
        .container { background: #f9f9f9; padding: 30px; border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 5px; overflow: hidden; }
        th { background: #007bff; color: white; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f1f1f1; }
        .status-pending { color: #ffc107; font-weight: bold; }
        .status-verified { color: #28a745; font-weight: bold; }
        .status-rejected { color: #dc3545; font-weight: bold; }
        .status-returned { color: #17a2b8; font-weight: bold; }
        .btn { display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; }
        .btn:hover { background: #5a6268; }
        .proof-link { color: #007bff; text-decoration: none; }
        .proof-link:hover { text-decoration: underline; }
        .badge { padding: 3px 10px; border-radius: 15px; font-size: 12px; }
        .badge-pending { background: #ffc107; color: #000; }
        .badge-verified { background: #28a745; color: #fff; }
        .badge-rejected { background: #dc3545; color: #fff; }
        .badge-returned { background: #17a2b8; color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 My Claim Status</h1>

        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Claim ID</th>
                        <th>Lost Item</th>
                        <th>Found Item</th>
                        <th>Proof</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo $row['claim_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($row['lost_item']); ?></td>
                            <td><?php echo htmlspecialchars($row['found_item']); ?></td>
                            <td>
                                <?php if ($row['proof_url']): ?>
                                    <a href="<?php echo htmlspecialchars($row['proof_url']); ?>" target="_blank" class="proof-link">📎 View</a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $status = $row['status'];
                                $badge_class = '';
                                if ($status == 'pending') {
                                    echo '<span class="badge badge-pending">⏳ Pending Review</span>';
                                } elseif ($status == 'verified') {
                                    echo '<span class="badge badge-verified">✅ Verified</span>';
                                } elseif ($status == 'rejected') {
                                    echo '<span class="badge badge-rejected">❌ Rejected</span>';
                                } elseif ($status == 'returned') {
                                    echo '<span class="badge badge-returned">🎉 Returned!</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; padding: 40px; background: white; border-radius: 5px;">
                You have not submitted any claims yet.
            </p>
        <?php endif; ?>

        <br>
        <a href="../../index.php" class="btn">⬅ Back to Home</a>
    </div>
</body>
</html>