<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../tey/login.php"); exit;
}

$user_id   = $_SESSION["user_id"];
$user_name = $_SESSION["user_name"];

$matching_alert      = '';
$matching_alert_type = 'success';

// ===== 1. RUN MATCHING ENGINE =====
if (isset($_GET['action']) && $_GET['action'] == 'run_matching') {
    $lost_items  = $found_items = [];
    $r = $conn->query("SELECT * FROM lost_items WHERE status = 'pending'");
    if ($r) while ($row = $r->fetch_assoc()) $lost_items[] = $row;
    $r = $conn->query("SELECT * FROM found_items WHERE status = 'pending'");
    if ($r) while ($row = $r->fetch_assoc()) $found_items[] = $row;

    $new_m = $upd_m = 0;

    foreach ($lost_items as $lost) {
        foreach ($found_items as $found) {
            $score = 0;
            $ln = strtolower(trim($lost['item_name'])); $fn = strtolower(trim($found['item_name']));
            if ($ln === $fn) { $score += 40; } else {
                $cw = array_diff(array_intersect(explode(' ',$ln), explode(' ',$fn)), ['the','a','of','in','at','on','with','utm','item','card']);
                if (!empty($cw)) $score += 25;
            }
            $t1 = array_map('trim', explode(',', strtolower($lost['tags'])));
            $t2 = array_map('trim', explode(',', strtolower($found['tags'])));
            $ct = array_intersect($t1, $t2);
            if (!empty($ct)) $score += min(count($ct) * 15, 30);
            $ll = strtolower($lost['location_lost']); $fl = strtolower($found['location_found']);
            foreach (['library','cafeteria','n28','n24','block','lab','elevator','classroom','hall'] as $kw) {
                if (strpos($ll,$kw)!==false && strpos($fl,$kw)!==false) { $score += 20; break; }
            }
            similar_text(strtolower($lost['description']), strtolower($found['description']), $pct);
            $score += ($pct > 50) ? 10 : (($pct > 25) ? 5 : 0);

            if ($score >= 40) {
                $chk = $conn->prepare("SELECT match_id FROM matches WHERE lost_item_id = ? AND found_item_id = ?");
                $chk->bind_param("ii", $lost['item_id'], $found['item_id']);
                $chk->execute(); $chk->store_result();
                if ($chk->num_rows > 0) {
                    $chk->bind_result($mid); $chk->fetch(); $chk->close();
                    $u = $conn->prepare("UPDATE matches SET match_score = ? WHERE match_id = ?");
                    $u->bind_param("ii", $score, $mid); $u->execute(); $u->close();
                    $upd_m++;
                } else {
                    $chk->close();
                    $ins = $conn->prepare("INSERT INTO matches (lost_item_id, found_item_id, match_score, status, notification_sent) VALUES (?, ?, ?, 'pending', 0)");
                    $ins->bind_param("iii", $lost['item_id'], $found['item_id'], $score);
                    $ins->execute(); $ins->close();
                    $new_m++;
                }
            }
        }
    }
    $total = $new_m + $upd_m;
    if ($total > 0) {
        $matching_alert = "⚡ Matching scan complete — <strong>$new_m</strong> new matches found, <strong>$upd_m</strong> scores updated.";
    } else {
        $matching_alert = "⚡ Matching scan complete — no new potential matches at this time.";
        $matching_alert_type = 'info';
    }
}

// ===== 2. DISMISS NOTIFICATION =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dismiss_match_id'])) {
    $mid = intval($_POST['dismiss_match_id']);
    $s = $conn->prepare("UPDATE matches SET notification_sent = 1 WHERE match_id = ?");
    $s->bind_param("i", $mid); $s->execute(); $s->close();
}

// ===== 3. FETCH NOTIFICATIONS =====
$notif_stmt = $conn->prepare("
    SELECT m.*, li.item_name AS lost_item_name, fi.item_name AS found_item_name, fi.location_found, fi.date_found
    FROM matches m
    JOIN lost_items li ON m.lost_item_id = li.item_id
    JOIN found_items fi ON m.found_item_id = fi.item_id
    WHERE li.user_id = ? AND m.notification_sent = 0
    ORDER BY m.created_at DESC
");
$notif_stmt->bind_param("i", $user_id); $notif_stmt->execute();
$notifications = $notif_stmt->get_result();

// ===== 4. METRICS =====
$lost_count   = $conn->query("SELECT COUNT(*) FROM lost_items WHERE user_id=$user_id AND status='pending'")->fetch_row()[0];
$found_count  = $conn->query("SELECT COUNT(*) FROM found_items WHERE user_id=$user_id AND status='pending'")->fetch_row()[0];
$match_count  = $conn->query("SELECT COUNT(*) FROM matches m JOIN lost_items li ON m.lost_item_id=li.item_id WHERE li.user_id=$user_id AND m.status='pending'")->fetch_row()[0];
$claims_count = $conn->query("SELECT COUNT(*) FROM claims WHERE owner_id=$user_id")->fetch_row()[0];

// ===== 5. RECENT REPORTS — fix: correlated subquery prevents duplicate rows =====
$lost_stmt = $conn->prepare("
    SELECT li.*,
           (SELECT c.status FROM claims c
            JOIN matches m ON c.match_id = m.match_id
            WHERE m.lost_item_id = li.item_id AND c.status = 'verified' LIMIT 1) AS claim_status
    FROM lost_items li
    WHERE li.user_id = ?
    ORDER BY li.date_lost DESC, li.created_at DESC
    LIMIT 5
");
$lost_stmt->bind_param("i", $user_id); $lost_stmt->execute();
$recent_lost = $lost_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — UTM Lost &amp; Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background-color: var(--bg-base); }
        .quick-action-btn {
            display: flex; align-items: center; gap: 12px;
            padding: 14px 18px;
            background: var(--glass-bg);
            border: 1px solid var(--border);
            border-radius: var(--r-md);
            color: var(--text-secondary);
            font-size: 14px; font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            width: 100%;
        }
        .quick-action-btn:hover { background: var(--glass-bg-hover); border-color: var(--glass-border-hover); color: var(--text-primary); transform: translateX(4px); }
        .quick-action-btn .qa-icon { font-size: 20px; width:36px; height:36px; display:flex; align-items:center; justify-content:center; border-radius:var(--r-sm); flex-shrink:0; }
        .qa-lost .qa-icon  { background: rgba(244,63,94,0.12); }
        .qa-found .qa-icon { background: rgba(16,185,129,0.12); }
        .qa-scan .qa-icon  { background: rgba(99,102,241,0.12); }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="custom-navbar">
        <a href="../../index.php" class="brand">🔍 UTM Lost &amp; Found</a>
        <div class="nav-links">
            <span class="nav-user-badge">👤 <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
            <a href="../../tey/logout.php" class="btn-custom btn-custom-secondary" style="padding:7px 16px; font-size:12px;">Logout</a>
        </div>
    </nav>

    <!-- HERO -->
    <div class="header-hero" style="padding:50px 20px 40px;">
        <h1 style="font-size:34px; margin-bottom:8px;">📊 My Workspace</h1>
        <p>Your central hub for tracking, matching, and reclaiming campus items.</p>
    </div>

    <div class="app-container">

        <!-- MATCHING ENGINE ALERT -->
        <?php if (!empty($matching_alert)): ?>
            <div class="alert-custom alert-custom-<?php echo $matching_alert_type; ?> alert-dismissible fade show mb-4" style="font-size:14px;">
                <?php echo $matching_alert; ?>
            </div>
        <?php endif; ?>

        <!-- ACTIVE MATCH NOTIFICATIONS -->
        <?php if ($notifications && $notifications->num_rows > 0): ?>
            <div class="mb-4">
                <h3 class="section-header">🔔 Active Match Alerts</h3>
                <?php while ($notif = $notifications->fetch_assoc()): ?>
                    <div class="notif-banner">
                        <div style="font-size:13px; color:var(--text-secondary);">
                            <strong style="color:#22d3ee;">🎉 New Match!</strong>
                            Your <strong style="color:var(--text-primary);"><?php echo htmlspecialchars($notif['lost_item_name']); ?></strong>
                            was matched with
                            <strong style="color:var(--text-primary);"><?php echo htmlspecialchars($notif['found_item_name']); ?></strong>
                            at <?php echo htmlspecialchars($notif['location_found']); ?>
                            <span class="status-badge status-badge-claimed" style="margin-left:8px;"><?php echo $notif['match_score']; ?>% match</span>
                        </div>
                        <div style="display:flex; gap:8px; flex-shrink:0;">
                            <a href="../../tan/claim_form.php?match_id=<?php echo $notif['match_id']; ?>" class="btn-custom btn-custom-success" style="padding:7px 14px; font-size:12px;">Claim</a>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="dismiss_match_id" value="<?php echo $notif['match_id']; ?>">
                                <button type="submit" class="btn-custom btn-custom-secondary" style="padding:7px 14px; font-size:12px;">Dismiss</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <!-- METRICS + QUICK ACTIONS -->
        <div class="row g-4 mb-4">
            <!-- METRICS -->
            <div class="col-lg-8">
                <div class="glass-card h-100" style="padding:26px;">
                    <h3 style="font-size:15px; font-weight:700; color:var(--text-primary); margin-bottom:20px;">📈 My Activity</h3>
                    <div class="row g-3">
                        <div class="col-6 col-sm-3">
                            <div class="metric-card">
                                <div class="metric-icon">📋</div>
                                <h4><?php echo $lost_count; ?></h4>
                                <small>Lost Reports</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="metric-card">
                                <div class="metric-icon">🤝</div>
                                <h4 class="green"><?php echo $found_count; ?></h4>
                                <small>Found Reports</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="metric-card">
                                <div class="metric-icon">🎯</div>
                                <h4 class="yellow"><?php echo $match_count; ?></h4>
                                <small>Matches</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="metric-card">
                                <div class="metric-icon">📑</div>
                                <h4 class="cyan"><?php echo $claims_count; ?></h4>
                                <small>Claims Filed</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="col-lg-4">
                <div class="glass-card h-100" style="padding:26px;">
                    <h3 style="font-size:15px; font-weight:700; color:var(--text-primary); margin-bottom:16px;">⚡ Quick Actions</h3>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <a href="../../lee/report_lost.php" class="quick-action-btn qa-lost">
                            <span class="qa-icon">🔴</span>
                            Report Lost Item
                        </a>
                        <a href="../../lee/report_found.php" class="quick-action-btn qa-found">
                            <span class="qa-icon">🟢</span>
                            Report Found Item
                        </a>
                        <a href="dashboard.php?action=run_matching" class="quick-action-btn qa-scan">
                            <span class="qa-icon">⚡</span>
                            Run Matching Scan
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODULE NAV -->
        <div class="glass-card mb-4">
            <h3 class="section-header">📁 My Portal Modules</h3>
            <div class="row g-3">
                <div class="col-md-4">
                    <div style="background:var(--glass-bg); border:1px solid var(--border); border-radius:var(--r-md); padding:22px; text-align:center; transition:all 0.2s; height:100%;"
                         onmouseover="this.style.borderColor='var(--glass-border-hover)'" onmouseout="this.style.borderColor='var(--border)'">
                        <div style="font-size:32px; margin-bottom:10px;">🎯</div>
                        <h4 style="font-size:14px; font-weight:700; margin-bottom:6px;">View Matches</h4>
                        <p style="font-size:12px; color:var(--text-muted); margin-bottom:16px; min-height:34px;">Inspect items matched by our scoring engine</p>
                        <a href="display_match.php" class="btn-custom btn-custom-primary w-100" style="padding:8px; font-size:12px;">Go to Matches →</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background:var(--glass-bg); border:1px solid var(--border); border-radius:var(--r-md); padding:22px; text-align:center; transition:all 0.2s; height:100%;"
                         onmouseover="this.style.borderColor='var(--glass-border-hover)'" onmouseout="this.style.borderColor='var(--border)'">
                        <div style="font-size:32px; margin-bottom:10px;">📋</div>
                        <h4 style="font-size:14px; font-weight:700; margin-bottom:6px;">My Claim History</h4>
                        <p style="font-size:12px; color:var(--text-muted); margin-bottom:16px; min-height:34px;">Track ownership verifications and collections</p>
                        <a href="../../tan/claim_status.php" class="btn-custom btn-custom-success w-100" style="padding:8px; font-size:12px;">Go to Claims →</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background:var(--glass-bg); border:1px solid var(--border); border-radius:var(--r-md); padding:22px; text-align:center; transition:all 0.2s; height:100%;"
                         onmouseover="this.style.borderColor='var(--glass-border-hover)'" onmouseout="this.style.borderColor='var(--border)'">
                        <div style="font-size:32px; margin-bottom:10px;">⏳</div>
                        <h4 style="font-size:14px; font-weight:700; margin-bottom:6px;">Pending Reports</h4>
                        <p style="font-size:12px; color:var(--text-muted); margin-bottom:16px; min-height:34px;">Monitor your unmatched active lost reports</p>
                        <a href="store_wait.php" class="btn-custom btn-custom-secondary w-100" style="padding:8px; font-size:12px;">Go to Pending →</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECENT REPORTS TABLE -->
        <div class="glass-card">
            <h3 class="section-header">📋 Recent Lost Reports</h3>
            <?php if ($recent_lost && $recent_lost->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead><tr>
                            <th>#ID</th><th>Item Name</th><th>Location Lost</th><th>Date</th><th>Status</th>
                        </tr></thead>
                        <tbody>
                            <?php while ($row = $recent_lost->fetch_assoc()): ?>
                                <tr>
                                    <td><strong style="color:var(--primary-light);">#<?php echo $row['item_id']; ?></strong></td>
                                    <td><strong style="color:var(--text-primary);"><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
                                    <td style="font-size:13px;"><?php echo htmlspecialchars($row['location_lost']); ?></td>
                                    <td style="font-size:13px; white-space:nowrap;"><?php echo date('d M Y', strtotime($row['date_lost'])); ?></td>
                                    <td>
                                        <?php
                                        $s  = $row['status'];
                                        $cs = $row['claim_status'] ?? '';
                                        if ($s == 'returned')       echo '<span class="status-badge status-badge-returned">🎉 Returned</span>';
                                        elseif ($cs == 'verified')  echo '<span class="status-badge status-badge-verified">✅ Verified</span>';
                                        elseif ($s == 'claimed')    echo '<span class="status-badge status-badge-claimed">📋 Claimed</span>';
                                        else                         echo '<span class="status-badge status-badge-pending">⏳ Pending</span>';
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align:center; padding:40px; color:var(--text-muted);">
                    <p style="margin:0; font-size:14px;">No lost reports yet. Use the shortcuts above to file one.</p>
                </div>
            <?php endif; ?>
        </div>

        <div style="text-align:center; margin-top:40px;">
            <a href="../../index.php" class="btn-custom btn-custom-outline" style="padding:11px 32px;">🏠 Back to Home</a>
        </div>
    </div>

    <footer class="custom-footer mt-5"><p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming</p></footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/assistant.js"></script>
</body>
</html>
