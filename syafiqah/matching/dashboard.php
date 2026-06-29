<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../tey/login.php");
    exit;
}

$user_id   = $_SESSION["user_id"];
$user_name = $_SESSION["user_name"];

$matching_alert      = '';
$matching_alert_type = 'success';

// ===== 1. HANDLE ACTION: RUN MATCHING ENGINE =====
if (isset($_GET['action']) && $_GET['action'] == 'run_matching') {
    $lost_result  = $conn->query("SELECT * FROM lost_items WHERE status = 'pending'");
    $lost_items   = [];
    if ($lost_result) { while ($row = $lost_result->fetch_assoc()) { $lost_items[] = $row; } }

    $found_result = $conn->query("SELECT * FROM found_items WHERE status = 'pending'");
    $found_items  = [];
    if ($found_result) { while ($row = $found_result->fetch_assoc()) { $found_items[] = $row; } }

    $new_matches_count     = 0;
    $updated_matches_count = 0;

    foreach ($lost_items as $lost) {
        foreach ($found_items as $found) {
            $score = 0;
            $lost_name  = strtolower(trim($lost['item_name']));
            $found_name = strtolower(trim($found['item_name']));
            if ($lost_name === $found_name) {
                $score += 40;
            } else {
                $lost_words  = explode(' ', $lost_name);
                $found_words = explode(' ', $found_name);
                $common_words = array_diff(array_intersect($lost_words, $found_words), ['the','a','of','in','at','on','with','utm','item','card']);
                if (!empty($common_words)) { $score += 25; }
            }
            $lost_tags  = array_map('trim', explode(',', strtolower($lost['tags'])));
            $found_tags = array_map('trim', explode(',', strtolower($found['tags'])));
            $common_tags = array_intersect($lost_tags, $found_tags);
            if (!empty($common_tags)) { $score += min(count($common_tags) * 15, 30); }

            $lost_loc  = strtolower($lost['location_lost']);
            $found_loc = strtolower($found['location_found']);
            foreach (['library','cafeteria','n28','n24','block','lab','elevator','classroom','hall'] as $word) {
                if (strpos($lost_loc, $word) !== false && strpos($found_loc, $word) !== false) { $score += 20; break; }
            }
            similar_text(strtolower($lost['description']), strtolower($found['description']), $pct);
            if ($pct > 50) { $score += 10; } elseif ($pct > 25) { $score += 5; }

            if ($score >= 40) {
                $stmt = $conn->prepare("SELECT match_id FROM matches WHERE lost_item_id = ? AND found_item_id = ?");
                $stmt->bind_param("ii", $lost['item_id'], $found['item_id']);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($match_id); $stmt->fetch(); $stmt->close();
                    $up = $conn->prepare("UPDATE matches SET match_score = ? WHERE match_id = ?");
                    $up->bind_param("ii", $score, $match_id); $up->execute(); $up->close();
                    $updated_matches_count++;
                } else {
                    $stmt->close();
                    $in = $conn->prepare("INSERT INTO matches (lost_item_id, found_item_id, match_score, status, notification_sent) VALUES (?, ?, ?, 'pending', 0)");
                    $in->bind_param("iii", $lost['item_id'], $found['item_id'], $score); $in->execute(); $in->close();
                    $new_matches_count++;
                }
            }
        }
    }
    $total_actions = $new_matches_count + $updated_matches_count;
    if ($total_actions > 0) {
        $matching_alert = "⚡ Matching Engine completed! Found <strong>$new_matches_count</strong> new matches and updated <strong>$updated_matches_count</strong> scores.";
    } else {
        $matching_alert = "⚡ Matching Engine completed. No new potential matches found at this time.";
        $matching_alert_type = 'info';
    }
}

// ===== 2. HANDLE ACTION: DISMISS NOTIFICATION =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dismiss_match_id'])) {
    $match_id = intval($_POST['dismiss_match_id']);
    $up = $conn->prepare("UPDATE matches SET notification_sent = 1 WHERE match_id = ?");
    $up->bind_param("i", $match_id); $up->execute(); $up->close();
}

// ===== 3. FETCH NOTIFICATIONS =====
// FIXED: If item verified or claim already, do not show in Active Match Alerts (only show 'pending' matches)
$notif_stmt = $conn->prepare("
    SELECT m.*, li.item_name AS lost_item_name, fi.item_name AS found_item_name, fi.location_found, fi.date_found
    FROM matches m
    JOIN lost_items li ON m.lost_item_id = li.item_id
    JOIN found_items fi ON m.found_item_id = fi.item_id
    WHERE li.user_id = ? AND m.notification_sent = 0 AND m.status = 'pending'
    ORDER BY m.created_at DESC");
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notifications = $notif_stmt->get_result();

// ===== 4. FETCH METRICS =====
$lost_count   = $conn->query("SELECT COUNT(*) FROM lost_items WHERE user_id = $user_id AND status = 'pending'")->fetch_row()[0];
$found_count  = $conn->query("SELECT COUNT(*) FROM found_items WHERE user_id = $user_id AND status = 'pending'")->fetch_row()[0];
$match_count  = $conn->query("SELECT COUNT(*) FROM matches m JOIN lost_items li ON m.lost_item_id = li.item_id WHERE li.user_id = $user_id AND m.status = 'pending'")->fetch_row()[0];
$claims_count = $conn->query("SELECT COUNT(*) FROM claims WHERE owner_id = $user_id")->fetch_row()[0];

// ===== 5. RECENT LOST =====
$lost_stmt = $conn->prepare("
    SELECT li.*, c.status AS claim_status FROM lost_items li
    LEFT JOIN matches m ON li.item_id = m.lost_item_id
    LEFT JOIN claims c ON m.match_id = c.match_id AND c.status = 'verified'
    WHERE li.user_id = ? ORDER BY li.date_lost DESC, li.created_at DESC LIMIT 5");
$lost_stmt->bind_param("i", $user_id); $lost_stmt->execute();
$recent_lost = $lost_stmt->get_result();

// ===== 6. RECENT FOUND =====
$found_stmt = $conn->prepare("
    SELECT fi.*, c.status AS claim_status FROM found_items fi
    LEFT JOIN matches m ON fi.item_id = m.found_item_id
    LEFT JOIN claims c ON m.match_id = c.match_id AND c.status = 'verified'
    WHERE fi.user_id = ? ORDER BY fi.date_found DESC, fi.created_at DESC LIMIT 5");
$found_stmt->bind_param("i", $user_id); $found_stmt->execute();
$recent_found = $found_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Workspace – UTM Lost & Found Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css?v=3">
    <style>
        body {
            background: linear-gradient(rgba(15,23,42,0.65), rgba(15,23,42,0.8)),
                        url('../../LostAndFound_dashboard.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            color: #ffffff; /* Contrast text on dark bg */
        }

        /* Glass cards over photo background */
        .glass-card {
            background: rgba(255,255,255,0.96) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            color: #1e293b; /* Dark text inside card for readability */
        }

        .custom-table {
            background: rgba(255,255,255,0.98) !important;
            color: #1e293b;
        }

        /* Metric cards */
        .metric-card {
            background: rgba(255,255,255,0.96) !important;
            backdrop-filter: blur(8px);
            color: #1e293b;
        }

        /* Notification banner */
        .notif-banner {
            background: rgba(240,249,255,0.97) !important;
            border-left: 5px solid #06b6d4;
            backdrop-filter: blur(8px);
            color: #1e293b;
        }

        /* Report row hover */
        .report-actions { opacity: 0; transition: opacity 0.18s ease; }
        .report-row:hover .report-actions,
        .report-row.actions-visible .report-actions { opacity: 1; }

        /* Module nav cards */
        .module-nav-card {
            background: rgba(255,255,255,0.95);
            border: 1px solid rgba(255,255,255,0.5);
            border-radius: 16px;
            padding: 24px 18px;
            text-align: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(8px);
            color: #1e293b;
        }
        .module-nav-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(79,70,229,0.18);
            border-color: #c7d2fe;
        }
        .module-nav-icon {
            font-size: 32px;
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 14px;
        }

        /* Quick action buttons */
        .quick-btn {
            display: block;
            padding: 12px 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
        }
        .quick-btn-red   { background: linear-gradient(135deg,#ef4444,#dc2626); color: white; box-shadow: 0 4px 12px rgba(239,68,68,0.3); }
        .quick-btn-green { background: linear-gradient(135deg,#10b981,#059669); color: white; box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
        .quick-btn-blue  { background: linear-gradient(135deg,#4f46e5,#7c3aed); color: white; box-shadow: 0 4px 12px rgba(79,70,229,0.3); }
        .quick-btn:hover { transform: translateY(-2px); filter: brightness(1.05); color: white; }

        .section-title {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
            margin-bottom: 18px;
        }

        .action-icon {
            background: none; border: none; cursor: pointer; font-size: 16px;
            padding: 4px; border-radius: 6px; transition: background 0.15s;
        }
        .action-icon:hover { background: #f1f5f9; }

        /* Transparent hero over background */
        .page-hero {
            padding: 48px 20px;
            text-align: center;
        }
        .page-hero h1 {
            font-size: 36px;
            font-weight: 900;
            color: #ffffff;
            margin: 0 0 10px;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }
        .page-hero p {
            font-size: 16px;
            color: rgba(255,255,255,0.9);
            margin: 0;
            text-shadow: 0 1px 2px rgba(0,0,0,0.5);
        }
    </style>
</head>
<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="custom-navbar">
        <a href="../../index.php" class="brand">🔍 UTM Lost & Found</a>
        <div class="nav-links">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="display_match.php">🎯 Matches</a>
            <a href="../../tan/claim_status.php">📋 My Claims</a>
            <span>Hi, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
            <a href="../../tey/logout.php" class="btn-custom btn-custom-outline" style="padding: 7px 16px; font-size: 12px;">Logout</a>
        </div>
    </nav>

    <!-- ===== HERO (Transparent, white text) ===== -->
    <div class="page-hero">
        <h1>📊 My Workspace</h1>
        <p>Your central campus hub for tracking, matching, and reclaiming lost items.</p>
    </div>

    <div class="app-container" style="max-width: 1100px;">

        <!-- MATCHING ENGINE ALERT -->
        <?php if (!empty($matching_alert)): ?>
        <div class="alert-custom alert-custom-<?php echo $matching_alert_type; ?> mb-4" role="alert">
            <?php echo $matching_alert; ?>
        </div>
        <?php endif; ?>

        <!-- NOTIFICATION ALERTS -->
        <?php if ($notifications && $notifications->num_rows > 0): ?>
        <div class="mb-4">
            <div class="section-title" style="color: white; border-color: rgba(255,255,255,0.3);">🔔 Active Match Alerts</div>
            <?php while ($notif = $notifications->fetch_assoc()): ?>
            <div class="notif-banner mb-2 rounded p-3 d-flex align-items-center justify-content-between">
                <div style="font-size: 13px;">
                    <strong style="color: #0369a1;">🎉 New Match!</strong>
                    Your lost item <strong><?php echo htmlspecialchars($notif['lost_item_name']); ?></strong>
                    was matched with: <strong><?php echo htmlspecialchars($notif['found_item_name']); ?></strong>
                    at <?php echo htmlspecialchars($notif['location_found']); ?>
                    <span class="status-badge status-badge-claimed ms-2"><?php echo $notif['match_score']; ?>% Match</span>
                </div>
                <div class="d-flex gap-2 ms-3 flex-shrink-0">
                    <a href="../../tan/claim_form.php?match_id=<?php echo $notif['match_id']; ?>" class="btn-custom btn-custom-success py-1 px-3" style="font-size: 11px;">Claim Now</a>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="dismiss_match_id" value="<?php echo $notif['match_id']; ?>">
                        <button type="submit" class="btn-custom btn-custom-secondary py-1 px-3" style="font-size: 11px;">Dismiss</button>
                    </form>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <!-- METRICS + QUICK ACTIONS -->
        <div class="row g-4 mb-4">
            <!-- Metric Cards -->
            <div class="col-lg-8">
                <div class="glass-card h-100" style="padding: 24px;">
                    <p class="section-title">📊 My Activity Metrics</p>
                    <div class="row g-3">
                        <div class="col-6 col-sm-3">
                            <div class="metric-card flex-column text-center" style="padding: 18px 10px; gap: 6px;">
                                <div class="metric-icon metric-icon-blue" style="margin: 0 auto;">🔴</div>
                                <div class="metric-value"><?php echo $lost_count; ?></div>
                                <div class="metric-label">Lost Reports</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="metric-card flex-column text-center" style="padding: 18px 10px; gap: 6px;">
                                <div class="metric-icon metric-icon-green" style="margin: 0 auto;">🟢</div>
                                <div class="metric-value"><?php echo $found_count; ?></div>
                                <div class="metric-label">Found Reports</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="metric-card flex-column text-center" style="padding: 18px 10px; gap: 6px;">
                                <div class="metric-icon metric-icon-amber" style="margin: 0 auto;">🎯</div>
                                <div class="metric-value"><?php echo $match_count; ?></div>
                                <div class="metric-label">Matches</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="metric-card flex-column text-center" style="padding: 18px 10px; gap: 6px;">
                                <div class="metric-icon metric-icon-cyan" style="margin: 0 auto;">📋</div>
                                <div class="metric-value"><?php echo $claims_count; ?></div>
                                <div class="metric-label">Filed Claims</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="glass-card h-100 d-flex flex-column" style="padding: 24px;">
                    <p class="section-title">⚡ Quick Actions</p>
                    <div class="d-flex flex-column gap-2 flex-grow-1 justify-content-center">
                        <a href="../../lee/report_lost.php" class="quick-btn quick-btn-red">🔴 Report Lost Item</a>
                        <a href="../../lee/report_found.php" class="quick-btn quick-btn-green">🟢 Report Found Item</a>
                        <a href="dashboard.php?action=run_matching" class="quick-btn quick-btn-blue">⚡ Run Matching Scan</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODULE NAVIGATION GRID -->
        <div class="glass-card mb-4" style="padding: 28px;">
            <p class="section-title">📁 My Portal Modules</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="module-nav-card">
                        <div class="module-nav-icon" style="background: linear-gradient(135deg,#ede9fe,#ddd6fe);">🎯</div>
                        <h4 style="font-size: 15px; font-weight: 700; margin-bottom: 6px;">View Matches</h4>
                        <p class="text-muted" style="font-size: 11px; margin-bottom: 14px; min-height: 32px;">Inspect items matched by our scoring scanner</p>
                        <a href="display_match.php" class="btn-custom btn-custom-primary w-100" style="font-size: 12px; padding: 8px 16px;">Go to Matches</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="module-nav-card">
                        <div class="module-nav-icon" style="background: linear-gradient(135deg,#d1fae5,#a7f3d0);">📋</div>
                        <h4 style="font-size: 15px; font-weight: 700; margin-bottom: 6px;">My Claim History</h4>
                        <p class="text-muted" style="font-size: 11px; margin-bottom: 14px; min-height: 32px;">Track ownership verifications and collections</p>
                        <a href="../../tan/claim_status.php" class="btn-custom btn-custom-success w-100" style="font-size: 12px; padding: 8px 16px;">Go to Claims</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="module-nav-card">
                        <div class="module-nav-icon" style="background: linear-gradient(135deg,#fef3c7,#fde68a);">⏳</div>
                        <h4 style="font-size: 15px; font-weight: 700; margin-bottom: 6px;">Pending Reports</h4>
                        <p class="text-muted" style="font-size: 11px; margin-bottom: 14px; min-height: 32px;">Monitor your unmatched active lost reports</p>
                        <a href="store_wait.php" class="btn-custom btn-custom-secondary w-100" style="font-size: 12px; padding: 8px 16px;">Go to Pending</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECENT REPORTS TABLE -->
        <div class="glass-card" style="padding: 28px;">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
                <p class="section-title mb-0" style="border-bottom: none; padding-bottom: 0;">🔴 Recent Lost Reports</p>
                <input type="text" id="filterLost" class="form-control form-control-sm w-auto" placeholder="🔍 Filter by Name, Location or Status..." style="border-radius: 20px; font-size: 13px;">
            </div>
            <div class="table-responsive mb-4">
                <table class="custom-table">
                    <!-- FIXED COLUMN ALIGNMENT -->
                    <colgroup>
                        <col style="width: 80px;">
                        <col style="width: 25%;">
                        <col style="width: 25%;">
                        <col style="width: 15%;">
                        <col style="width: 15%;">
                        <col style="width: 120px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>ID</th><th>Item Name</th><th>Location Lost</th><th>Date</th><th>Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($recent_lost && $recent_lost->num_rows > 0):
                        while ($row = $recent_lost->fetch_assoc()):
                            $status       = $row['status'];
                            $claim_status = $row['claim_status'] ?? '';
                            $display_status = 'Pending'; $status_badge = 'status-badge-pending';
                            if ($status == 'returned')          { $display_status = 'Returned'; $status_badge = 'status-badge-returned'; }
                            elseif ($claim_status == 'verified'){ $display_status = 'Verified'; $status_badge = 'status-badge-verified'; }
                            elseif ($status == 'claimed')       { $display_status = 'Claimed';  $status_badge = 'status-badge-claimed'; }
                            $can_edit = ($display_status !== 'Verified' && $display_status !== 'Returned');
                    ?>
                        <tr class="report-row">
                            <td><strong>#<?php echo $row['item_id']; ?></strong></td>
                            <td><span class="fw-bold"><?php echo htmlspecialchars($row['item_name']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['location_lost']); ?></td>
                            <td><?php echo date('d M Y', strtotime($row['date_lost'])); ?></td>
                            <td><span class="status-badge <?php echo $status_badge; ?>"><?php echo $display_status; ?></span></td>
                            <td class="text-end">
                                <div class="report-actions d-flex justify-content-end gap-1">
                                    <?php if ($can_edit): ?>
                                    <a href="../../lee/report_lost.php?edit=1&id=<?php echo $row['item_id']; ?>" class="action-icon" title="Edit">✏️</a>
                                    <?php endif; ?>
                                    <form action="../../lee/delete_report.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="type" value="lost">
                                        <input type="hidden" name="id" value="<?php echo $row['item_id']; ?>">
                                        <button class="action-icon" onclick="return confirm('Delete this report?');" title="Delete">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No lost reports yet. Use Quick Actions above to file one.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center mt-5 mb-3" style="border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
                <p class="section-title mb-0" style="border-bottom: none; padding-bottom: 0;">🟢 Recent Found Reports</p>
                <input type="text" id="filterFound" class="form-control form-control-sm w-auto" placeholder="🔍 Filter by Name, Location or Status..." style="border-radius: 20px; font-size: 13px;">
            </div>
            <div class="table-responsive">
                <table class="custom-table">
                    <!-- FIXED COLUMN ALIGNMENT -->
                    <colgroup>
                        <col style="width: 80px;">
                        <col style="width: 25%;">
                        <col style="width: 25%;">
                        <col style="width: 15%;">
                        <col style="width: 15%;">
                        <col style="width: 120px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>ID</th><th>Item Name</th><th>Location Found</th><th>Date</th><th>Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($recent_found && $recent_found->num_rows > 0):
                        while ($row = $recent_found->fetch_assoc()):
                            $status       = $row['status'];
                            $claim_status = $row['claim_status'] ?? '';
                            $display_status = 'Pending'; $status_badge = 'status-badge-pending';
                            if ($status == 'returned')          { $display_status = 'Returned'; $status_badge = 'status-badge-returned'; }
                            elseif ($claim_status == 'verified'){ $display_status = 'Verified'; $status_badge = 'status-badge-verified'; }
                            elseif ($status == 'claimed')       { $display_status = 'Claimed';  $status_badge = 'status-badge-claimed'; }
                            $can_edit = ($display_status !== 'Verified' && $display_status !== 'Returned');
                    ?>
                        <tr class="report-row">
                            <td><strong>#<?php echo $row['item_id']; ?></strong></td>
                            <td><span class="fw-bold"><?php echo htmlspecialchars($row['item_name']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['location_found']); ?></td>
                            <td><?php echo date('d M Y', strtotime($row['date_found'])); ?></td>
                            <td><span class="status-badge <?php echo $status_badge; ?>"><?php echo $display_status; ?></span></td>
                            <td class="text-end">
                                <div class="report-actions d-flex justify-content-end gap-1">
                                    <?php if ($can_edit): ?>
                                    <a href="../../lee/report_found.php?edit=1&id=<?php echo $row['item_id']; ?>" class="action-icon" title="Edit">✏️</a>
                                    <?php endif; ?>
                                    <form action="../../lee/delete_report.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="type" value="found">
                                        <input type="hidden" name="id" value="<?php echo $row['item_id']; ?>">
                                        <button class="action-icon" onclick="return confirm('Delete this report?');" title="Delete">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No found reports yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-center mt-5 mb-4">
            <a href="../../index.php" class="btn-custom btn-custom-outline py-2 px-5">🏠 Return to Home</a>
        </div>
    </div>

    <footer class="custom-footer mt-5">
        <p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming Project</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Table row hover actions
        document.querySelectorAll('.report-row').forEach(row => {
            row.addEventListener('click', () => row.classList.toggle('actions-visible'));
        });
        document.querySelectorAll('.report-actions').forEach(a => {
            a.addEventListener('click', e => e.stopPropagation());
        });

        // Table Filtering Logic
        function setupFilter(inputId, tableSelector) {
            const input = document.getElementById(inputId);
            if (!input) return;
            input.addEventListener('keyup', function() {
                const filter = this.value.toLowerCase();
                const rows = document.querySelectorAll(tableSelector + ' tbody tr.report-row');
                
                rows.forEach(row => {
                    // Collect text from relevant columns (Item Name, Location, Status)
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            });
        }
        
        setupFilter('filterLost', 'table:nth-of-type(1)'); // Lost table
        setupFilter('filterFound', '.mt-5 + .table-responsive table'); // Found table
    </script>
    <script src="../../assets/js/assistant.js"></script>
</body>
</html>
