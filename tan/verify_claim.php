<?php
session_start();          // FIXED: session_start() MUST be first, before any include
include '../config.php';

$is_admin       = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
$admin_password = 'admin123';
$message        = '';
$error          = '';

// --- Admin login ---
if (isset($_POST['admin_login'])) {
    if ($_POST['admin_password'] === $admin_password) {
        $_SESSION['is_admin'] = true;
        $is_admin = true;
    } else {
        $error = "Invalid admin password. Please try again.";
    }
}

// --- Admin logout ---
if (isset($_GET['logout'])) {
    unset($_SESSION['is_admin']);
    $is_admin = false;
}

// --- Handle claim actions (approve / reject / return) ---
if ($is_admin && isset($_GET['claim_id'], $_GET['action'])) {
    $claim_id = intval($_GET['claim_id']);
    $action   = $_GET['action'];

    if ($action === 'approve') {
        $conn->query("UPDATE claims SET status = 'verified' WHERE claim_id = $claim_id");
        $conn->query("UPDATE matches SET status = 'verified'
                      WHERE match_id = (SELECT match_id FROM claims WHERE claim_id = $claim_id)");
        $message = "✅ Claim #$claim_id verified! The claimant can now collect their item.";

    } elseif ($action === 'reject') {
        $conn->query("UPDATE claims SET status = 'rejected' WHERE claim_id = $claim_id");
        // Reset match and items back to 'pending' so they can be re-matched/claimed
        $conn->query("UPDATE matches SET status = 'pending'
                      WHERE match_id = (SELECT match_id FROM claims WHERE claim_id = $claim_id)");
        $conn->query("UPDATE lost_items SET status = 'pending'
                      WHERE item_id = (SELECT lost_item_id FROM matches
                                       WHERE match_id = (SELECT match_id FROM claims WHERE claim_id = $claim_id))");
        $conn->query("UPDATE found_items SET status = 'pending'
                      WHERE item_id = (SELECT found_item_id FROM matches
                                       WHERE match_id = (SELECT match_id FROM claims WHERE claim_id = $claim_id))");
        $message = "❌ Claim #$claim_id rejected. Items reset to pending for re-matching.";

    } elseif ($action === 'return') {
        // FIXED: Only allow 'return' when claim is already 'verified'
        $check = $conn->query("SELECT status FROM claims WHERE claim_id = $claim_id")->fetch_assoc();
        if ($check && $check['status'] === 'verified') {
            $conn->query("UPDATE claims SET status = 'returned' WHERE claim_id = $claim_id");
            $conn->query("UPDATE matches SET status = 'returned'
                          WHERE match_id = (SELECT match_id FROM claims WHERE claim_id = $claim_id)");
            $conn->query("UPDATE lost_items SET status = 'returned'
                          WHERE item_id = (SELECT lost_item_id FROM matches
                                           WHERE match_id = (SELECT match_id FROM claims WHERE claim_id = $claim_id))");
            $conn->query("UPDATE found_items SET status = 'returned'
                          WHERE item_id = (SELECT found_item_id FROM matches
                                           WHERE match_id = (SELECT match_id FROM claims WHERE claim_id = $claim_id))");
            $message = "🎉 Item #$claim_id has been marked as successfully returned to the owner!";
        } else {
            $message = "⚠️ Cannot mark as returned — the claim must be Verified first.";
        }
    }
}

// --- Fetch stats ---
$stats_pending  = $conn->query("SELECT COUNT(*) FROM claims WHERE status = 'pending'")->fetch_row()[0] ?? 0;
$stats_verified = $conn->query("SELECT COUNT(*) FROM claims WHERE status = 'verified'")->fetch_row()[0] ?? 0;
$stats_returned = $conn->query("SELECT COUNT(*) FROM claims WHERE status = 'returned'")->fetch_row()[0] ?? 0;
$stats_rejected = $conn->query("SELECT COUNT(*) FROM claims WHERE status = 'rejected'")->fetch_row()[0] ?? 0;

// --- Filter & sort ---
$filter_status  = isset($_GET['status']) && in_array($_GET['status'], ['all','pending','verified','rejected','returned']) ? $_GET['status'] : 'all';
$sort_order     = isset($_GET['sort']) && $_GET['sort'] === 'asc' ? 'ASC' : 'DESC';

// --- Fetch pending claims for action cards ---
$pending_result = $conn->query("
    SELECT c.*, u.name AS owner_name, u.email AS owner_email, u.phone,
           li.item_name AS lost_item, fi.item_name AS found_item, fi.photo_url
    FROM claims c
    JOIN users      u  ON c.owner_id    = u.id
    JOIN matches    m  ON c.match_id    = m.match_id
    JOIN lost_items li ON m.lost_item_id  = li.item_id
    JOIN found_items fi ON m.found_item_id = fi.item_id
    WHERE c.status = 'pending'
    ORDER BY c.created_at ASC
");

// --- Fetch verified claims (ready for handover) for action cards ---
$verified_result = $conn->query("
    SELECT c.*, u.name AS owner_name, u.email AS owner_email, u.phone,
           li.item_name AS lost_item, fi.item_name AS found_item, fi.photo_url
    FROM claims c
    JOIN users      u  ON c.owner_id    = u.id
    JOIN matches    m  ON c.match_id    = m.match_id
    JOIN lost_items li ON m.lost_item_id  = li.item_id
    JOIN found_items fi ON m.found_item_id = fi.item_id
    WHERE c.status = 'verified'
    ORDER BY c.created_at ASC
");

// --- Fetch history table ---
$where   = $filter_status !== 'all' ? "WHERE c.status = '" . $conn->real_escape_string($filter_status) . "'" : '';
$all_result = $conn->query("
    SELECT c.*, u.name AS owner_name,
           li.item_name AS lost_item, fi.item_name AS found_item
    FROM claims c
    JOIN users      u  ON c.owner_id    = u.id
    JOIN matches    m  ON c.match_id    = m.match_id
    JOIN lost_items li ON m.lost_item_id  = li.item_id
    JOIN found_items fi ON m.found_item_id = fi.item_id
    $where
    ORDER BY c.created_at $sort_order
    LIMIT 50
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal – Claim Verification | UTM Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=3">
    <style>
        body {
            background: linear-gradient(rgba(2,6,23,0.55), rgba(15,23,42,0.7)),
                        url('../LostAndFound_found.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }

        /* ── Login page ──────────────────────────────────────── */
        .admin-login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            background: rgba(255,255,255,0.97) !important;
            backdrop-filter: blur(16px);
            box-shadow: 0 24px 60px rgba(0,0,0,0.4) !important;
            animation: fadeInUp 0.5s ease both;
        }
        .login-card:hover { transform: translateY(-4px); }

        /* ── Admin dashboard ─────────────────────────────────── */
        .admin-topbar {
            background: rgba(15,23,42,0.95);
            backdrop-filter: blur(20px);
            padding: 14px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.35);
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .admin-topbar .brand {
            font-size: 16px;
            font-weight: 800;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .admin-topbar .topbar-links { display: flex; gap: 16px; align-items: center; }
        .admin-topbar .topbar-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 6px;
        }
        .admin-topbar .topbar-links a:hover { color: white; background: rgba(255,255,255,0.1); }
        .admin-topbar .topbar-links a.logout { color: #fbbf24; font-weight: 700; }

        /* ── Glass cards ─────────────────────────────────────── */
        .glass-card {
            background: rgba(255,255,255,0.97) !important;
            backdrop-filter: blur(10px);
        }

        /* ── Metric cards ────────────────────────────────────── */
        .stat-card {
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(8px);
            border-radius: 16px;
            padding: 20px 18px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.5);
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(0,0,0,0.18); }
        .stat-num { font-size: 32px; font-weight: 900; line-height: 1; letter-spacing: -1px; }
        .stat-lbl { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-top: 5px; }

        /* ── Admin claim card ────────────────────────────────── */
        .admin-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-left: 5px solid var(--warning-color);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }
        .admin-card:hover { box-shadow: 0 10px 28px rgba(0,0,0,0.1); }
        .admin-card.verified-card { border-left-color: #10b981; }

        /* ── Section title ───────────────────────────────────── */
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }

        .meta-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #94a3b8;
            margin-bottom: 3px;
        }
    </style>
</head>
<body>

<?php if (!$is_admin): ?>
<!-- ===== ADMIN LOGIN PAGE ===== -->
<div class="admin-login-wrapper">
    <div class="login-card glass-card text-center">

        <!-- Brand bar -->
        <div style="background: linear-gradient(135deg,#d97706,#f59e0b); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 12px; margin-bottom: 24px; text-align: left;">
            <span style="font-size: 28px;">🔐</span>
            <div>
                <h3 style="font-size: 15px; font-weight: 800; color: white; margin: 0;">Campus Security Portal</h3>
                <p style="font-size: 11px; color: rgba(255,255,255,0.8); margin: 0;">UTM Lost & Found Administration</p>
            </div>
        </div>

        <span style="font-size: 36px; display: block; margin-bottom: 12px;">👮‍♂️</span>
        <h2 style="border-bottom: none; padding-bottom: 0; font-size: 20px;">Admin Login</h2>
        <p class="text-muted" style="font-size: 13px; margin-bottom: 24px;">Enter credentials to manage UTM claims</p>

        <?php if (!empty($error)): ?>
            <div class="alert-custom alert-custom-danger mb-4" style="font-size: 13px;">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group mb-4">
                <label for="admin_password" style="text-align: left;">Admin Security Password</label>
                <input type="password" name="admin_password" id="admin_password" class="form-control" placeholder="••••••••" required>
            </div>
            <input type="hidden" name="admin_login" value="1">
            <button type="submit" class="btn-custom btn-custom-primary w-100 py-2">Log In →</button>
        </form>

        <div class="mt-4 pt-3" style="border-top: 1px solid #e2e8f0;">
            <a href="../index.php" style="font-size: 13px; color: #64748b; text-decoration: none;">🏠 Back to Portal</a>
        </div>
    </div>
</div>
<?php exit(); endif; ?>

<!-- ===== ADMIN DASHBOARD ===== -->

<!-- Admin Topbar -->
<div class="admin-topbar">
    <span class="brand">🔐 UTM Admin — Claim Verification</span>
    <div class="topbar-links">
        <a href="../syafiqah/matching/dashboard.php">📋 User Dashboard</a>
        <a href="../index.php">🏠 Portal Home</a>
        <a href="?logout=1" class="logout">🚪 Logout</a>
    </div>
</div>

<div class="app-container" style="max-width: 1100px; padding-top: 28px;">

    <!-- Success/error message -->
    <?php if (!empty($message)): ?>
        <div class="alert-custom <?php echo str_contains($message, '⚠️') ? 'alert-custom-warning' : (str_contains($message, '❌') ? 'alert-custom-danger' : 'alert-custom-success'); ?> mb-4" style="font-size: 14px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- ===== METRICS ROW ===== -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-num" style="color: #d97706;">⏳ <?php echo $stats_pending; ?></div>
                <div class="stat-lbl">Pending</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-num" style="color: #059669;">✅ <?php echo $stats_verified; ?></div>
                <div class="stat-lbl">Verified</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-num" style="color: #6d28d9;">🎉 <?php echo $stats_returned; ?></div>
                <div class="stat-lbl">Returned</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-num" style="color: #dc2626;">❌ <?php echo $stats_rejected; ?></div>
                <div class="stat-lbl">Rejected</div>
            </div>
        </div>
    </div>

    <!-- ===== PENDING CLAIMS ===== -->
    <div class="glass-card mb-4" style="padding: 28px;">
        <p class="section-title">📋 Pending Claims Review
            <span style="font-size: 13px; font-weight: 500; color: #64748b; margin-left: 8px;">(<?php echo $pending_result->num_rows; ?> awaiting)</span>
        </p>

        <?php if ($pending_result->num_rows > 0):
            while ($row = $pending_result->fetch_assoc()): ?>
            <div class="admin-card">
                <div class="row g-3 align-items-center">
                    <!-- Claimant + Match info -->
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-6 pe-md-4" style="border-right: 1px solid #f1f5f9;">
                                <div class="meta-label">Claimant Details</div>
                                <h4 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0 0 3px;"><?php echo htmlspecialchars($row['owner_name']); ?></h4>
                                <p style="font-size: 12px; color: #64748b; margin: 0;"><?php echo htmlspecialchars($row['owner_email']); ?> · <?php echo htmlspecialchars($row['phone'] ?? '—'); ?></p>
                                <div class="mt-3">
                                    <div class="meta-label">Match Pair</div>
                                    <p style="font-size: 13px; margin: 0;"><strong>Lost:</strong> <?php echo htmlspecialchars($row['lost_item']); ?></p>
                                    <p style="font-size: 13px; margin: 0;"><strong>Found:</strong> <?php echo htmlspecialchars($row['found_item']); ?></p>
                                    <p style="font-size: 11px; color: #94a3b8; margin: 3px 0 0;">Claim #<?php echo $row['claim_id']; ?> · Filed <?php echo date('d M Y', strtotime($row['created_at'])); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6 ps-md-4 mt-3 mt-md-0">
                                <div class="meta-label">Ownership Proof Description</div>
                                <p style="font-size: 13px; color: #334155; line-height: 1.5; margin: 0;"><?php echo htmlspecialchars($row['proof_description']); ?></p>
                                <?php if ($row['proof_url']): ?>
                                    <div class="mt-3">
                                        <div class="meta-label">Uploaded Document</div>
                                        <a href="../<?php echo htmlspecialchars($row['proof_url']); ?>" target="_blank" class="btn-custom btn-custom-outline py-1 px-3 mt-1 d-inline-flex" style="font-size: 12px; gap: 6px;">📎 View Attachment</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- Photo + Actions -->
                    <div class="col-md-3 text-center" style="border-left: 1px solid #f1f5f9;">
                        <?php if ($row['photo_url']): ?>
                            <img src="../<?php echo htmlspecialchars($row['photo_url']); ?>" alt="Item" class="img-fluid rounded mb-3" style="max-height: 90px; object-fit: cover; border: 1px solid #e2e8f0;">
                        <?php endif; ?>
                        <div class="d-flex flex-column gap-2">
                            <!-- Approve -->
                            <a href="verify_claim.php?claim_id=<?php echo $row['claim_id']; ?>&action=approve"
                               class="btn-custom btn-custom-success py-1 w-100" style="font-size: 12px;"
                               onclick="return confirm('Approve this claim?');">✅ Approve Claim</a>
                            <!-- Reject -->
                            <a href="verify_claim.php?claim_id=<?php echo $row['claim_id']; ?>&action=reject"
                               class="btn-custom btn-custom-danger py-1 w-100" style="font-size: 12px;"
                               onclick="return confirm('Reject this claim? This will reset items to pending.');">❌ Reject Claim</a>
                            <!-- NOTE: "Mark Handed Over" only shown for VERIFIED claims (see below section) -->
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; else: ?>
            <div class="text-center py-5" style="color: #64748b; background: #f8fafc; border-radius: 12px;">
                <span style="font-size: 40px; display: block; margin-bottom: 12px;">✅</span>
                <p style="font-weight: 600; margin: 0;">No pending claims — all caught up!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ===== VERIFIED CLAIMS — READY FOR HANDOVER ===== -->
    <?php if ($verified_result->num_rows > 0): ?>
    <div class="glass-card mb-4" style="padding: 28px;">
        <p class="section-title" style="color: #059669; border-color: #a7f3d0;">
            🎉 Verified Claims — Ready for Handover
            <span style="font-size: 13px; font-weight: 500; color: #64748b; margin-left: 8px;">(<?php echo $verified_result->num_rows; ?> ready)</span>
        </p>

        <?php while ($row = $verified_result->fetch_assoc()): ?>
        <div class="admin-card verified-card">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <strong style="font-size: 15px;"><?php echo htmlspecialchars($row['owner_name']); ?></strong>
                        <span class="status-badge status-badge-verified">✅ Verified</span>
                        <span style="font-size: 11px; color: #94a3b8;">Claim #<?php echo $row['claim_id']; ?></span>
                    </div>
                    <p style="font-size: 13px; color: #475569; margin: 0;">
                        <strong>Lost:</strong> <?php echo htmlspecialchars($row['lost_item']); ?>
                        &nbsp;→&nbsp;
                        <strong>Found:</strong> <?php echo htmlspecialchars($row['found_item']); ?>
                    </p>
                    <p style="font-size: 12px; color: #64748b; margin: 3px 0 0;"><?php echo htmlspecialchars($row['owner_email']); ?></p>
                </div>
                <a href="verify_claim.php?claim_id=<?php echo $row['claim_id']; ?>&action=return"
                   class="btn-custom btn-custom-primary" style="font-size: 13px; padding: 10px 20px; white-space: nowrap;"
                   onclick="return confirm('Confirm physical handover for Claim #<?php echo $row['claim_id']; ?>?');">
                    🎉 Mark as Handed Over
                </a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- ===== CLAIMS HISTORY TABLE ===== -->
    <div class="glass-card" style="padding: 28px;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
            <p class="section-title m-0" style="border: none; padding: 0;">📊 Historical Claims Log</p>
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-end mt-3 mt-md-0">
                <div>
                    <label style="font-size: 11px; font-weight: 600; color: #64748b; display: block; margin-bottom: 3px;">Status</label>
                    <select name="status" class="form-select form-select-sm" style="min-width: 130px;">
                        <?php foreach (['all'=>'All Statuses','pending'=>'Pending','verified'=>'Verified','rejected'=>'Rejected','returned'=>'Returned'] as $val => $lbl): ?>
                            <option value="<?php echo $val; ?>" <?php echo $filter_status === $val ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 600; color: #64748b; display: block; margin-bottom: 3px;">Sort</label>
                    <select name="sort" class="form-select form-select-sm" style="min-width: 130px;">
                        <option value="desc" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="asc"  <?php echo $sort_order === 'ASC'  ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                </div>
                <button type="submit" class="btn-custom btn-custom-primary" style="font-size: 13px; padding: 8px 18px; align-self: flex-end;">Apply</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Claim ID</th><th>Claimant</th><th>Lost Item</th><th>Found Item</th><th>Status</th><th>Date Filed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $all_result->fetch_assoc()):
                        $s = $row['status'];
                    ?>
                    <tr>
                        <td><strong>#<?php echo $row['claim_id']; ?></strong></td>
                        <td><span class="fw-bold"><?php echo htmlspecialchars($row['owner_name']); ?></span></td>
                        <td><?php echo htmlspecialchars($row['lost_item']); ?></td>
                        <td><?php echo htmlspecialchars($row['found_item']); ?></td>
                        <td>
                            <?php
                            if ($s === 'pending')  echo '<span class="status-badge status-badge-pending">⏳ Pending</span>';
                            elseif ($s === 'verified') echo '<span class="status-badge status-badge-verified">✅ Verified</span>';
                            elseif ($s === 'rejected') echo '<span class="status-badge status-badge-rejected">❌ Rejected</span>';
                            elseif ($s === 'returned') echo '<span class="status-badge status-badge-returned">🎉 Returned</span>';
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

<footer class="custom-footer mt-5">
    <p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming Project</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
