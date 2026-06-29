<?php
include '../config.php';
session_start();

$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
$admin_password = 'admin123';
$message = '';
$error = '';

if (isset($_POST['admin_login'])) {
    $password = $_POST['admin_password'];
    if ($password === $admin_password) {
        $_SESSION['is_admin'] = true;
        $is_admin = true;
    } else {
        $error = "Invalid admin password. Please try again.";
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
        $message = "✅ Claim verified successfully! Claimant can now collect the item.";
    } elseif ($action == 'reject') {
        $status = 'rejected';
        $message = "❌ Claim rejected. Owner will see the status update.";
    } elseif ($action == 'return') {
        $status = 'returned';
        $message = "🎉 Item successfully marked as RETURNED & handed over!";
    }

    if (!empty($status)) {
        // 1. Update claim status
        $sql = "UPDATE claims SET status = '$status' WHERE claim_id = $claim_id";
        $conn->query($sql);
        
        if ($action == 'approve') {
            // Update match status to 'verified'
            $update_match = "UPDATE matches SET status = 'verified' WHERE match_id = 
                             (SELECT match_id FROM claims WHERE claim_id = $claim_id)";
            $conn->query($update_match);
        } elseif ($action == 'return') {
            // 2. Update match status to returned
            $update_match = "UPDATE matches SET status = 'returned' WHERE match_id = 
                             (SELECT match_id FROM claims WHERE claim_id = $claim_id)";
            $conn->query($update_match);
            
            // 3. Update corresponding lost item status to returned
            $update_lost = "UPDATE lost_items SET status = 'returned' WHERE item_id = 
                            (SELECT lost_item_id FROM matches WHERE match_id = 
                             (SELECT match_id FROM claims WHERE claim_id = $claim_id))";
            $conn->query($update_lost);
            
            // 4. Update corresponding found item status to returned
            $update_found = "UPDATE found_items SET status = 'returned' WHERE item_id = 
                             (SELECT found_item_id FROM matches WHERE match_id = 
                              (SELECT match_id FROM claims WHERE claim_id = $claim_id))";
            $conn->query($update_found);
        } elseif ($action == 'reject') {
            // If rejected, reset match and item statuses to 'pending' so they can be matched again
            $update_match = "UPDATE matches SET status = 'pending' WHERE match_id = 
                             (SELECT match_id FROM claims WHERE claim_id = $claim_id)";
            $conn->query($update_match);
            
            $update_lost = "UPDATE lost_items SET status = 'pending' WHERE item_id = 
                            (SELECT lost_item_id FROM matches WHERE match_id = 
                             (SELECT match_id FROM claims WHERE claim_id = $claim_id))";
            $conn->query($update_lost);
            
            $update_found = "UPDATE found_items SET status = 'pending' WHERE item_id = 
                             (SELECT found_item_id FROM matches WHERE match_id = 
                              (SELECT match_id FROM claims WHERE claim_id = $claim_id))";
            $conn->query($update_found);
        }
    }
}

// Fetch stats for admin metrics
$stats_pending = $conn->query("SELECT COUNT(*) FROM claims WHERE status = 'pending'")->fetch_row()[0];
$stats_verified = $conn->query("SELECT COUNT(*) FROM claims WHERE status = 'verified'")->fetch_row()[0];
$stats_returned = $conn->query("SELECT COUNT(*) FROM claims WHERE status = 'returned'")->fetch_row()[0];

// Fetch pending claims
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

// Fetch historical claims
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Claim Verification</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(rgba(2, 6, 23, 0.45), rgba(15, 23, 42, 0.55)),
                        url('../LostAndFound_found.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }
        .admin-header {
            background: #1e293b;
            color: white;
            padding: 20px 30px;
            border-radius: 0 0 16px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-md);
            margin-bottom: 30px;
        }
        .admin-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
            border-left: 5px solid var(--warning-color);
            transition: all 0.2s ease;
        }
        .admin-card:hover {
            box-shadow: var(--shadow-md);
        }
        .admin-login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
    </style>
</head>
<body>

<?php if (!$is_admin): ?>
    <!-- ===== ADMIN LOGIN ===== -->
    <div class="admin-login-wrapper">
        <div class="glass-card text-center" style="max-width: 400px; width: 100%;">
            <span style="font-size: 50px;">🔐</span>
            <h2 class="mt-2" style="border-bottom: none; padding-bottom: 0;">Admin Portal</h2>
            <p class="text-muted" style="font-size: 13px;">Enter credentials to manage UTM claims</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-custom alert-custom-danger py-2 px-3 mb-3" style="font-size: 13px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group mb-4">
                    <label for="admin_password" class="text-start">Admin Security Password</label>
                    <input type="password" name="admin_password" id="admin_password" class="form-control" placeholder="••••••••" required>
                </div>
                <input type="hidden" name="admin_login" value="1">
                <button type="submit" class="btn-custom btn-custom-primary w-100 py-2">Log In</button>
            </form>
            
            <div class="mt-4 pt-3 border-top">
                <p class="text-muted m-0" style="font-size: 12px;">Default Password: <strong>admin123</strong></p>
                <a href="../index.php" class="d-inline-block mt-3" style="font-size: 13px; color: var(--text-muted); text-decoration: none;">🏠 Back to Portal</a>
            </div>
        </div>
    </div>
    <?php exit(); ?>
<?php endif; ?>

    <!-- ===== ADMIN NAVBAR ===== -->
    <div class="app-container" style="max-width: 1100px; margin-top: 0; padding-top: 0;">
        <div class="admin-header">
            <h2 class="m-0" style="font-size: 18px; font-weight: 700; color: white;">🔐 UTM Claim Verification Dashboard</h2>
            <div class="d-flex gap-3">
                <a href="../syafiqah/matching/dashboard.php" style="color: #cbd5e1; text-decoration: none; font-size: 14px; font-weight: 500;">📋 User Dashboard</a>
                <a href="../index.php" style="color: #cbd5e1; text-decoration: none; font-size: 14px; font-weight: 500;">🏠 Portal Home</a>
                <a href="?logout=1" style="color: var(--warning-color); text-decoration: none; font-size: 14px; font-weight: 600;">🚪 Logout</a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-custom alert-custom-success mb-4" style="font-size: 14px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- ===== METRICS ROW ===== -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="p-3 border rounded bg-white text-center shadow-sm">
                    <span style="font-size: 24px;">⏳</span>
                    <h3 class="m-0 text-warning font-weight-bold mt-1"><?php echo $stats_pending; ?></h3>
                    <small class="text-muted uppercase font-weight-bold" style="font-size: 11px;">Pending Claims</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 border rounded bg-white text-center shadow-sm">
                    <span style="font-size: 24px;">✅</span>
                    <h3 class="m-0 text-success font-weight-bold mt-1"><?php echo $stats_verified; ?></h3>
                    <small class="text-muted uppercase font-weight-bold" style="font-size: 11px;">Verified Claims</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 border rounded bg-white text-center shadow-sm">
                    <span style="font-size: 24px;">🎉</span>
                    <h3 class="m-0 text-info font-weight-bold mt-1"><?php echo $stats_returned; ?></h3>
                    <small class="text-muted uppercase font-weight-bold" style="font-size: 11px;">Returned Items</small>
                </div>
            </div>
        </div>

        <!-- ===== PENDING CLAIMS CARD LIST ===== -->
        <div class="glass-card mb-5">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 20px;">📋 Pending Claims Review (<?php echo $pending_result->num_rows; ?>)</h3>

            <?php if ($pending_result->num_rows > 0): ?>
                <?php while($row = $pending_result->fetch_assoc()): ?>
                    <div class="admin-card">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-9">
                                <div class="row">
                                    <div class="col-md-6 border-end">
                                        <p class="m-0" style="font-size: 11px; color: var(--text-muted);">CLAIMANT DETAILS</p>
                                        <h4 class="m-0" style="font-size: 16px; font-weight: 700; color: var(--text-main);"><?php echo htmlspecialchars($row['owner_name']); ?></h4>
                                        <p class="m-0" style="font-size: 13px; color: var(--text-muted);"><?php echo htmlspecialchars($row['owner_email']); ?> | <?php echo htmlspecialchars($row['phone']); ?></p>
                                        
                                        <p class="m-0 mt-3" style="font-size: 11px; color: var(--text-muted);">MATCH PAIR</p>
                                        <p class="m-0" style="font-size: 13px;"><strong>Lost:</strong> <?php echo htmlspecialchars($row['lost_item']); ?></p>
                                        <p class="m-0" style="font-size: 13px;"><strong>Found:</strong> <?php echo htmlspecialchars($row['found_item']); ?></p>
                                    </div>
                                    <div class="col-md-6 ps-md-4">
                                        <p class="m-0" style="font-size: 11px; color: var(--text-muted);">OWNERSHIP PROOF DESCRIPTION</p>
                                        <p class="m-0 text-dark" style="font-size: 13px; line-height: 1.4;"><?php echo htmlspecialchars($row['proof_description']); ?></p>
                                        
                                        <?php if ($row['proof_url']): ?>
                                            <p class="m-0 mt-3" style="font-size: 13px;">
                                                <strong>Uploaded Attachment:</strong> <br>
                                                <!-- We prepend ../ because proof_url is saved relative to project root -->
                                                <a href="../<?php echo htmlspecialchars($row['proof_url']); ?>" target="_blank" class="btn-custom btn-custom-outline py-1 px-3 mt-1 d-inline-flex" style="font-size: 11px;">📎 View Uploaded Document</a>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 border-start text-center">
                                <?php if ($row['photo_url']): ?>
                                    <!-- Prepend ../ because photo_url is stored relative to project root -->
                                    <img src="../<?php echo htmlspecialchars($row['photo_url']); ?>" alt="Item photo" class="img-fluid rounded mb-3 shadow-sm" style="max-height: 100px; object-fit: cover;">
                                <?php endif; ?>
                                
                                <div class="d-flex flex-column gap-2">
                                    <a href="verify_claim.php?claim_id=<?php echo $row['claim_id']; ?>&action=approve" class="btn-custom btn-custom-success py-1 w-100" style="font-size: 12px;">✅ Approve Claim</a>
                                    <a href="verify_claim.php?claim_id=<?php echo $row['claim_id']; ?>&action=reject" class="btn-custom btn-custom-danger py-1 w-100" style="font-size: 12px;">❌ Reject Claim</a>
                                    <a href="verify_claim.php?claim_id=<?php echo $row['claim_id']; ?>&action=return" class="btn-custom btn-custom-primary py-1 w-100" style="font-size: 12px;">🎉 Mark Handed Over</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5 text-muted bg-light border rounded">
                    <span style="font-size: 36px;">✅</span>
                    <p class="mt-2 m-0">No pending claims for review. All caught up!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ===== CLAIMS HISTORY TABLE ===== -->
        <div class="glass-card">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 20px;">📊 Historical Claims Log</h3>
            
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Claim ID</th>
                            <th>Claimant</th>
                            <th>Lost Item</th>
                            <th>Found Item</th>
                            <th>Status</th>
                            <th>Date Filed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $all_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo $row['claim_id']; ?></strong></td>
                                <td><span class="fw-bold"><?php echo htmlspecialchars($row['owner_name']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['lost_item']); ?></td>
                                <td><?php echo htmlspecialchars($row['found_item']); ?></td>
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
        </div>
    </div>

    <!-- ===== FOOTER ===== -->
    <footer class="custom-footer mt-5">
        <p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming Project</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
