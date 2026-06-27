<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../tey/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

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
    <title>My Claims - UTM Lost & Found Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background-color: var(--light-bg); }
    </style>
</head>
<body>
    <nav class="custom-navbar">
        <a href="../index.php" class="brand">🔍 UTM Lost & Found</a>
        <div class="nav-links">
            <a href="../syafiqah/matching/dashboard.php" style="color: var(--text-muted); text-decoration: none; font-size: 14px; font-weight: 500; margin-right: 15px;">📋 Dashboard</a>
            <a href="../tey/logout.php" class="btn-custom btn-custom-outline" style="padding: 6px 16px; font-size: 12px;">🚪 Logout</a>
        </div>
    </nav>

    <div class="app-container" style="max-width: 900px;">
        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2" style="border-bottom: 2px solid #f1f5f9;">
                <h2 class="m-0">📋 My Claim Status</h2>
                <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-secondary py-1 px-3" style="font-size: 12px;">⬅ Dashboard</a>
            </div>

            <p class="text-muted mb-4" style="font-size: 14px;">
                Track the progress of your submitted claims. UTM Campus Security will verify your proofs. Once verified, you can collect the item from the security office.
            </p>

            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Claim ID</th>
                                <th>Lost Item</th>
                                <th>Matched Found Item</th>
                                <th>Status</th>
                                <th>Date Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $row['claim_id']; ?></strong></td>
                                    <td><span class="fw-bold"><?php echo htmlspecialchars($row['lost_item']); ?></span></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if ($row['photo_url']): ?>
                                                <img src="../<?php echo htmlspecialchars($row['photo_url']); ?>" alt="Found photo" style="width: 35px; height: 35px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                            <?php endif; ?>
                                            <span><?php echo htmlspecialchars($row['found_item']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $status = $row['status'];
                                        if ($status == 'pending') echo '<span class="status-badge status-badge-pending">⏳ Pending</span>';
                                        elseif ($status == 'verified') echo '<span class="status-badge status-badge-verified">✅ Verified</span>';
                                        elseif ($status == 'rejected') echo '<span class="status-badge status-badge-rejected">❌ Rejected</span>';
                                        elseif ($status == 'returned') echo '<span class="status-badge status-badge-returned">🎉 Returned</span>';
                                        ?>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <span style="font-size: 40px;">📋</span>
                    <p class="mt-3 m-0">You have not submitted any claims yet.</p>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-outline py-2 px-4">⬅ Return to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>