<?php
include '../config.php';
session_start();

$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
$admin_password = 'admin123';

if (isset($_POST['admin_login'])) {
    $password = $_POST['admin_password'];
    if ($password === $admin_password) {
        $_SESSION['is_admin'] = true;
        $is_admin = true;
    } else {
        $error = "Invalid admin password.";
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['is_admin']);
    $is_admin = false;
}

if ($is_admin && isset($_GET['claim_id']) && isset($_GET['action'])) {
    $claim_id = intval($_GET['claim_id']);
    $action = $_GET['action'];
    $status = '';

    if ($action == 'approve') {
        $status = 'verified';
        $message = "✅ Claim verified! Item can now be returned to owner.";
    } elseif ($action == 'reject') {
        $status = 'rejected';
        $message = "❌ Claim rejected. Owner has been notified.";
    } elseif ($action == 'return') {
        $status = 'returned';
        $message = "🎉 Item marked as RETURNED!";
    }

    if (!empty($status)) {
        $sql = "UPDATE claims SET status = '$status' WHERE claim_id = $claim_id";
        $conn->query($sql);
        if ($action == 'return') {
            $update_match = "UPDATE matches SET status = 'returned' WHERE match_id = 
                             (SELECT match_id FROM claims WHERE claim_id = $claim_id)";
            $conn->query($update_match);
        }
    }
}

$pending_sql = "SELECT c.*, u.name as owner_name, u.email as owner_email, u.phone, 
                       li.item_name as lost_item, fi.item_name as found_item, fi.photo_url
                FROM claims c 
                JOIN users u ON c.owner_id = u.id
                JOIN matches m ON c.match_id = m.match_id
                JOIN lost_items li ON m.lost_item_id = li.item_id
                JOIN found_items fi ON m.found_item_id = fi.item_id
                WHERE c.status = 'pending'
                ORDER BY c.created_at ASC";
$pending_result = $conn->query($pending_sql);

$all_sql = "SELECT c.*, u.name as owner_name, 
                   li.item_name as lost_item, fi.item_name as found_item
            FROM claims c 
            JOIN users u ON c.owner_id = u.id
            JOIN matches m ON c.match_id = m.match_id
            JOIN lost_items li ON m.lost_item_id = li.item_id
            JOIN found_items fi ON m.found_item_id = fi.item_id
            ORDER BY c.created_at DESC
            LIMIT 50";
$all_result = $conn->query($all_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Claim Verification</title>
    <style>
        body { font-family: Arial; max-width: 1200px; margin: 30px auto; padding: 20px; }
        .header { background: #343a40; color: white; padding: 15px 20px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: #ffc107; }
        .container { background: #f9f9f9; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .claim-card { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; border-left: 5px solid #ffc107; }
        .claim-card img { max-width: 150px; border-radius: 5px; }
        .btn-group { margin-top: 15px; }
        .btn { padding: 8px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin-right: 10px; }
        .btn-approve { background: #28a745; color: white; }
        .btn-approve:hover { background: #218838; }
        .btn-reject { background: #dc3545; color: white; }
        .btn-reject:hover { background: #c82333; }
        .btn-return { background: #17a2b8; color: white; }
        .btn-return:hover { background: #138496; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 5px; overflow: hidden; }
        th { background: #343a40; color: white; padding: 10px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        .badge { padding: 3px 10px; border-radius: 15px; font-size: 12px; }
        .badge-pending { background: #ffc107; color: #000; }
        .badge-verified { background: #28a745; color: #fff; }
        .badge-rejected { background: #dc3545; color: #fff; }
        .badge-returned { background: #17a2b8; color: #fff; }
        .login-form { max-width: 400px; margin: 100px auto; padding: 30px; background: white; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .login-form input[type="password"] { width: 100%; padding: 10px; margin: 10px 0; }
        .login-form input[type="submit"] { background: #007bff; color: white; padding: 10px 30px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>

<?php if (!$is_admin): ?>
    <div class="login-form">
        <h2>🔐 Admin Login</h2>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST">
            <label>Admin Password:</label>
            <input type="password" name="admin_password" required>
            <input type="hidden" name="admin_login" value="1">
            <input type="submit" value="Login">
        </form>
        <p style="margin-top: 20px; color: #6c757d; font-size: 14px;">Default password: <strong>admin123</strong></p>
    </div>
    <?php exit(); ?>
<?php endif; ?>

<div class="header">
    <h2>🔐 Admin Claim Verification Dashboard</h2>
    <div>
        <a href="?logout=1" style="color: #ffc107; text-decoration: none;">🚪 Logout</a>
    </div>
</div>

<div class="container">
    <h2>📋 Pending Claims (<?php echo $pending_result->num_rows; ?>)</h2>

    <?php if ($pending_result->num_rows > 0): ?>
        <?php while($row = $pending_result->fetch_assoc()): ?>
            <div class="claim-card">
                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                    <div style="flex: 1;">
                        <p><strong>Claim ID:</strong> <?php echo $row['claim_id']; ?></p>
                        <p><strong>Owner:</strong> <?php echo htmlspecialchars($row['owner_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($row['owner_email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($row['phone']); ?></p>
                        <p><strong>Lost Item:</strong> <?php echo htmlspecialchars($row['lost_item']); ?></p>
                        <p><strong>Found Item:</strong> <?php echo htmlspecialchars($row['found_item']); ?></p>
                        <p><strong>Proof Description:</strong> <?php echo htmlspecialchars($row['proof_description']); ?></p>
                        <?php if ($row['proof_url']): ?>
                            <p><strong>Proof:</strong> <a href="<?php echo htmlspecialchars($row['proof_url']); ?>" target="_blank">📎 View Proof</a></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($row['photo_url']): ?>
                        <div>
                            <img src="<?php echo htmlspecialchars($row['photo_url']); ?>" alt="Found item">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="btn-group">
                    <a href="verify_claim.php?claim_id=<?php echo $row['claim_id']; ?>&action=approve" class="btn btn-approve">✅ Approve</a>
                    <a href="verify_claim.php?claim_id=<?php echo $row['claim_id']; ?>&action=reject" class="btn btn-reject">❌ Reject</a>
                    <a href="verify_claim.php?claim_id=<?php echo $row['claim_id']; ?>&action=return" class="btn btn-return">🎉 Mark Returned</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align: center; padding: 40px; background: white; border-radius: 5px;">✅ No pending claims. All claims have been verified!</p>
    <?php endif; ?>
</div>

<div class="container">
    <h2>📊 All Claims History</h2>
    <table>
        <thead>
            <tr><th>ID</th><th>Owner</th><th>Lost Item</th><th>Found Item</th><th>Status</th><th>Date</th></tr>
        </thead>
        <tbody>
            <?php while($row = $all_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['claim_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['owner_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['lost_item']); ?></td>
                    <td><?php echo htmlspecialchars($row['found_item']); ?></td>
                    <td>
                        <?php 
                        $status = $row['status'];
                        if ($status == 'pending') echo '<span class="badge badge-pending">⏳ Pending</span>';
                        elseif ($status == 'verified') echo '<span class="badge badge-verified">✅ Verified</span>';
                        elseif ($status == 'rejected') echo '<span class="badge badge-rejected">❌ Rejected</span>';
                        elseif ($status == 'returned') echo '<span class="badge badge-returned">🎉 Returned</span>';
                        ?>
                    </td>
                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
