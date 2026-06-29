<?php
session_start();
// Fix database connection path
require_once '../../config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../tey/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$user_name = $_SESSION["user_name"];

$matching_alert = '';
$matching_alert_type = 'success';

// ===== 1. HANDLE ACTION: RUN MATCHING ENGINE =====
if (isset($_GET['action']) && $_GET['action'] == 'run_matching') {
    // Fetch all pending lost items
    $lost_query = "SELECT * FROM lost_items WHERE status = 'pending'";
    $lost_result = $conn->query($lost_query);
    $lost_items = [];
    if ($lost_result) {
        while ($row = $lost_result->fetch_assoc()) {
            $lost_items[] = $row;
        }
    }

    // Fetch all pending found items
    $found_query = "SELECT * FROM found_items WHERE status = 'pending'";
    $found_result = $conn->query($found_query);
    $found_items = [];
    if ($found_result) {
        while ($row = $found_result->fetch_assoc()) {
            $found_items[] = $row;
        }
    }

    $new_matches_count = 0;
    $updated_matches_count = 0;

    foreach ($lost_items as $lost) {
        foreach ($found_items as $found) {
            $score = 0;
            
            // Name Match (max 40 pts)
            $lost_name = strtolower(trim($lost['item_name']));
            $found_name = strtolower(trim($found['item_name']));
            if ($lost_name === $found_name) {
                $score += 40;
            } else {
                $lost_words = explode(' ', $lost_name);
                $found_words = explode(' ', $found_name);
                $common_words = array_intersect($lost_words, $found_words);
                $trivial = ['the', 'a', 'of', 'in', 'at', 'on', 'with', 'utm', 'item', 'card'];
                $common_words = array_diff($common_words, $trivial);
                if (!empty($common_words)) {
                    $score += 25;
                }
            }

            // Tag Match (max 30 pts)
            $lost_tags = array_map('trim', explode(',', strtolower($lost['tags'])));
            $found_tags = array_map('trim', explode(',', strtolower($found['tags'])));
            $common_tags = array_intersect($lost_tags, $found_tags);
            if (!empty($common_tags)) {
                $score += min(count($common_tags) * 15, 30);
            }

            // Location Match (max 20 pts)
            $lost_loc = strtolower($lost['location_lost']);
            $found_loc = strtolower($found['location_found']);
            $loc_keywords = ['library', 'cafeteria', 'n28', 'n24', 'block', 'lab', 'elevator', 'classroom', 'hall'];
            foreach ($loc_keywords as $word) {
                if (strpos($lost_loc, $word) !== false && strpos($found_loc, $word) !== false) {
                    $score += 20;
                    break; 
                }
            }

            // Description Similarity (max 10 pts)
            similar_text(strtolower($lost['description']), strtolower($found['description']), $pct);
            if ($pct > 50) {
                $score += 10;
            } elseif ($pct > 25) {
                $score += 5;
            }

            // Save match if combined score is 40 or higher
            if ($score >= 40) {
                $check_sql = "SELECT match_id FROM matches WHERE lost_item_id = ? AND found_item_id = ?";
                $stmt = $conn->prepare($check_sql);
                $stmt->bind_param("ii", $lost['item_id'], $found['item_id']);
                $stmt->execute();
                $stmt->store_result();
                
                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($match_id);
                    $stmt->fetch();
                    $stmt->close();
                    
                    $update_sql = "UPDATE matches SET match_score = ? WHERE match_id = ?";
                    $up_stmt = $conn->prepare($update_sql);
                    $up_stmt->bind_param("ii", $score, $match_id);
                    $up_stmt->execute();
                    $up_stmt->close();
                    
                    $updated_matches_count++;
                } else {
                    $stmt->close();
                    $insert_sql = "INSERT INTO matches (lost_item_id, found_item_id, match_score, status, notification_sent) VALUES (?, ?, ?, 'pending', 0)";
                    $in_stmt = $conn->prepare($insert_sql);
                    $in_stmt->bind_param("iii", $lost['item_id'], $found['item_id'], $score);
                    $in_stmt->execute();
                    $in_stmt->close();
                    
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
    $update_sql = "UPDATE matches SET notification_sent = 1 WHERE match_id = ?";
    $up_stmt = $conn->prepare($update_sql);
    $up_stmt->bind_param("i", $match_id);
    $up_stmt->execute();
    $up_stmt->close();
}

// ===== 3. FETCH ACTIVE NOTIFICATION ALERTS =====
$notif_sql = "
SELECT m.*, li.item_name AS lost_item_name, fi.item_name AS found_item_name, fi.location_found, fi.date_found
FROM matches m
JOIN lost_items li ON m.lost_item_id = li.item_id
JOIN found_items fi ON m.found_item_id = fi.item_id
WHERE li.user_id = ? AND m.notification_sent = 0
ORDER BY m.created_at DESC
";
$notif_stmt = $conn->prepare($notif_sql);
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notifications = $notif_stmt->get_result();

// ===== 4. FETCH METRICS =====
$lost_count = $conn->query("SELECT COUNT(*) FROM lost_items WHERE user_id = $user_id AND status = 'pending'")->fetch_row()[0];
$found_count = $conn->query("SELECT COUNT(*) FROM found_items WHERE user_id = $user_id AND status = 'pending'")->fetch_row()[0];
$match_count = $conn->query("SELECT COUNT(*) FROM matches m JOIN lost_items li ON m.lost_item_id = li.item_id WHERE (li.user_id = $user_id) AND m.status = 'pending'")->fetch_row()[0];
$claims_count = $conn->query("SELECT COUNT(*) FROM claims WHERE owner_id = $user_id")->fetch_row()[0];

// ===== 5. FETCH RECENT LOST REPORTS =====
$lost_reports_sql = "
SELECT li.*, c.status AS claim_status
FROM lost_items li
LEFT JOIN matches m ON li.item_id = m.lost_item_id
LEFT JOIN claims c ON m.match_id = c.match_id AND c.status = 'verified'
WHERE li.user_id = ?
ORDER BY li.date_lost DESC, li.created_at DESC
LIMIT 5
";
$lost_stmt = $conn->prepare($lost_reports_sql);
$lost_stmt->bind_param("i", $user_id);
$lost_stmt->execute();
$recent_lost = $lost_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - UTM Lost & Found Assistant</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
    body { 
        background: linear-gradient(rgba(248, 250, 252, 0.9), rgba(248, 250, 252, 0.93)), 
                    url('../../LostAndFound_dashboard.png') no-repeat center center fixed; 
        background-size: cover;
        min-height: 100vh;
    }
    
    .glass-card, .custom-table, .notif-banner {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(4px);
    }

    .section-header { 
        border-bottom: 2px solid #e2e8f0; 
        padding-bottom: 8px; 
        margin-bottom: 20px; 
        font-weight: 700; 
        font-size: 18px; 
        color: #1e293b; 
    }
    
    .notif-banner { 
        background-color: #f0f9ff; 
        border-left: 5px solid var(--info-color); 
        padding: 15px; 
        border-radius: var(--radius-sm); 
        margin-bottom: 15px; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        box-shadow: var(--shadow-sm); 
    }
</style>
</head>
<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="custom-navbar">
        <a href="../../index.php" class="brand">
            🔍 UTM Lost & Found
        </a>
        <div class="nav-links">
            <span style="font-size: 14px; font-weight: 500; color: var(--text-muted); margin-right: 15px;">
                Welcome, <strong style="color: var(--primary-color);"><?php echo htmlspecialchars($user_name); ?></strong>!
            </span>
            <a href="../../tey/logout.php" class="btn-custom btn-custom-outline" style="padding: 6px 16px; font-size: 12px;">🚪 Logout</a>
        </div>
    </nav>

    <!-- ===== HEADER / HERO ===== -->
    <div class="header-hero" style="padding: 40px 20px;">
        <h1>📊 Student Workspace</h1>
        <p>Your central campus hub for tracking, matching, and reclaiming items.</p>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="app-container">
        
        <!-- ===== RUN ENGINE STATUS ALERTS ===== -->
        <?php if (!empty($matching_alert)): ?>
            <div class="alert alert-<?php echo $matching_alert_type; ?> alert-custom alert-custom-<?php echo $matching_alert_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $matching_alert; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="padding: 1.25rem;"></button>
            </div>
        <?php endif; ?>

        <!-- ===== ACTIVE MATCH ALERTS (NOTIFICATIONS) ===== -->
        <?php if ($notifications && $notifications->num_rows > 0): ?>
            <div class="mb-4">
                <h3 class="section-header">🔔 Active Match Alerts</h3>
                <?php while ($notif = $notifications->fetch_assoc()): ?>
                    <div class="notif-banner">
                        <div style="font-size: 13px;">
                            <strong style="color: #0369a1;">🎉 New Match!</strong> Your lost item <strong><?php echo htmlspecialchars($notif['lost_item_name']); ?></strong> was matched with found report: <strong><?php echo htmlspecialchars($notif['found_item_name']); ?></strong> at <?php echo htmlspecialchars($notif['location_found']); ?> (Score: <?php echo $notif['match_score']; ?>%).
                        </div>
                        <div class="d-flex gap-2 ms-3">
                            <a href="../../tan/claim_form.php?match_id=<?php echo $notif['match_id']; ?>" class="btn-custom btn-custom-success py-1 px-3" style="font-size: 11px;">Claim</a>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="dismiss_match_id" value="<?php echo $notif['match_id']; ?>">
                                <button type="submit" class="btn-custom btn-custom-outline py-1 px-3" style="font-size: 11px; border-color: #cbd5e1; color: var(--text-muted);">Dismiss</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <!-- ===== QUICK ACTIONS & METRICS ===== -->
        <div class="row g-4 mb-4">
            <!-- Metrics Summary Cards -->
            <div class="col-lg-8">
                <div class="glass-card h-100" style="padding: 25px;">
                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 20px; color: var(--text-main);">📊 My Activity Metrics</h3>
                    <div class="row g-3">
                        <div class="col-6 col-sm-3">
                            <div class="p-3 border rounded bg-light text-center">
                                <h4 class="m-0 text-primary fw-bold"><?php echo $lost_count; ?></h4>
                                <small class="text-muted" style="font-size: 10px;">Lost Reports</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="p-3 border rounded bg-light text-center">
                                <h4 class="m-0 text-success fw-bold"><?php echo $found_count; ?></h4>
                                <small class="text-muted" style="font-size: 10px;">Found Reports</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="p-3 border rounded bg-light text-center">
                                <h4 class="m-0 text-warning fw-bold"><?php echo $match_count; ?></h4>
                                <small class="text-muted" style="font-size: 10px;">Calculated Matches</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="p-3 border rounded bg-light text-center">
                                <h4 class="m-0 text-info fw-bold"><?php echo $claims_count; ?></h4>
                                <small class="text-muted" style="font-size: 10px;">Filed Claims</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Action Shortcuts -->
            <div class="col-lg-4">
                <div class="glass-card h-100 d-flex flex-column justify-content-between" style="padding: 25px;">
                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 15px; color: var(--text-main);">⚡ Quick Shortcuts</h3>
                    <div class="d-flex flex-column gap-2">
                        <a href="../../lee/report_lost.php" class="btn-custom btn-custom-primary text-center">🔴 Report Lost Item</a>
                        <a href="../../lee/report_found.php" class="btn-custom btn-custom-success text-center">🟢 Report Found Item</a>
                        <a href="dashboard.php?action=run_matching" class="btn-custom btn-custom-outline text-center" style="border-color: var(--primary-color);">⚡ Execute Matching Scan</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== MODULE NAVIGATION NAVIGATION GRID ===== -->
        <div class="glass-card mb-4">
            <h3 class="section-header">📁 My Portal Modules</h3>
            
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="p-3 border rounded bg-white text-center">
                        <span style="font-size: 30px;">🎯</span>
                        <h4 style="font-size: 15px; font-weight: 700; margin: 10px 0 5px;">View Matches</h4>
                        <p class="text-muted" style="font-size: 11px; min-height: 33px;">Inspect items matched by our scoring scanner</p>
                        <a href="display_match.php" class="btn-custom btn-custom-primary py-1 px-3 w-100" style="font-size: 12px; border-radius: 12px;">Go to Matches</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded bg-white text-center">
                        <span style="font-size: 30px;">📋</span>
                        <h4 style="font-size: 15px; font-weight: 700; margin: 10px 0 5px;">My Claim History</h4>
                        <p class="text-muted" style="font-size: 11px; min-height: 33px;">Track ownership verifications and collections</p>
                        <a href="../../tan/claim_status.php" class="btn-custom btn-custom-success py-1 px-3 w-100" style="font-size: 12px; border-radius: 12px;">Go to Claims</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded bg-white text-center">
                        <span style="font-size: 30px;">⏳</span>
                        <h4 style="font-size: 15px; font-weight: 700; margin: 10px 0 5px;">Pending Reports</h4>
                        <p class="text-muted" style="font-size: 11px; min-height: 33px;">Monitor your unmatched active lost reports</p>
                        <a href="store_wait.php" class="btn-custom btn-custom-secondary py-1 px-3 w-100" style="font-size: 12px; border-radius: 12px;">Go to Pending</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== RECENT REPORTS LIST ===== -->
        <div class="glass-card">
            <h3 class="section-header">📋 Recent Reports Log</h3>
            
            <?php if ($recent_lost && $recent_lost->num_rows > 0): ?>
                <div class="table-responsive bg-white">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Report ID</th>
                                <th>Item Name</th>
                                <th>Location Lost</th>
                                <th>Date Lost</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $recent_lost->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $row['item_id']; ?></strong></td>
                                    <td><span class="fw-bold"><?php echo htmlspecialchars($row['item_name']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['location_lost']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['date_lost'])); ?></td>
                                    <td>
                                        <?php 
                                        $status = $row['status'];
                                        $claim_status = isset($row['claim_status']) ? $row['claim_status'] : '';
                                        if ($status == 'returned') {
                                            echo '<span class="status-badge status-badge-returned">🎉 Returned</span>';
                                        } elseif ($claim_status == 'verified') {
                                            echo '<span class="status-badge status-badge-verified">✅ Verified</span>';
                                        } elseif ($status == 'claimed') {
                                            echo '<span class="status-badge status-badge-claimed">📋 Claimed</span>';
                                        } else {
                                            echo '<span class="status-badge status-badge-pending">⏳ Pending</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4 text-muted bg-light border rounded">
                    <p class="m-0" style="font-size: 13px;">No lost reports found. Use the shortcuts above to file a report.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-5">
            <a href="../../index.php" class="btn-custom btn-custom-outline py-2 px-5">🏠 Return to Main Landing Page</a>
        </div>

    </div>

    <!-- ===== FOOTER ===== -->
    <footer class="custom-footer mt-5">
        <p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming Project</p>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Conversational Chatbot Widget (Botpress Mockup) -->
    <script src="../../assets/js/assistant.js"></script>
</body>
</html>
