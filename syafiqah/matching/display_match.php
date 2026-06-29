<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../tey/login.php");
    exit;
}

$user_id   = $_SESSION["user_id"];
$user_name = $_SESSION["user_name"];

// Fetch matches where the user is the lost item owner OR the found item reporter
$stmt = $conn->prepare("
    SELECT
        m.*,
        li.item_name AS lost_item_name,
        li.description AS lost_desc,
        li.user_id AS lost_owner_id,
        fi.item_name AS found_item_name,
        fi.description AS found_desc,
        fi.user_id AS found_finder_id,
        fi.photo_url AS found_photo,
        fi.location_found,
        fi.date_found
    FROM matches m
    JOIN lost_items li ON m.lost_item_id = li.item_id
    JOIN found_items fi ON m.found_item_id = fi.item_id
    WHERE li.user_id = ? OR fi.user_id = ?
    ORDER BY m.match_score DESC, m.created_at DESC
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_matches = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Match Results – UTM Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css?v=3">
    <style>
        body {
            background: linear-gradient(rgba(2,6,23,0.5), rgba(15,23,42,0.65)),
                        url('../../LostAndFound_found.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }

        /* Glass overlay on cards */
        .glass-card {
            background: rgba(255,255,255,0.97) !important;
            backdrop-filter: blur(10px);
        }

        /* Match card design */
        .match-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            margin-bottom: 18px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            transition: all 0.25s ease;
            animation: fadeInUp 0.4s ease both;
        }
        .match-card:hover {
            box-shadow: 0 12px 32px rgba(79,70,229,0.14);
            transform: translateY(-3px);
            border-color: #c7d2fe;
        }

        /* Match card header */
        .match-header {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Score badge */
        .score-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 50px;
            letter-spacing: 0.3px;
        }
        .score-badge.high  { background: linear-gradient(135deg,#059669,#10b981); }
        .score-badge.med   { background: linear-gradient(135deg,#d97706,#f59e0b); }
        .score-badge.low   { background: linear-gradient(135deg,#64748b,#94a3b8); }

        /* Match body */
        .match-body { padding: 18px 20px; }

        /* Item photo */
        .match-image {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            flex-shrink: 0;
        }
        .match-image-placeholder {
            width: 90px;
            height: 90px;
            background: #f1f5f9;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
            border: 1px solid #e2e8f0;
        }

        /* Item columns */
        .item-col-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .item-col-name { font-size: 14px; font-weight: 700; margin-bottom: 3px; }
        .item-col-desc { font-size: 11px; color: #64748b; line-height: 1.4; }
        .item-col-meta { font-size: 10px; color: #94a3b8; margin-top: 4px; }

        /* VS divider */
        .vs-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
            padding: 0 10px;
        }

        /* Summary bar */
        .summary-bar {
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            padding: 14px 20px;
            color: white;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }
        .summary-bar strong { color: #a5b4fc; }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }
        .empty-state .empty-icon {
            font-size: 56px;
            display: block;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="custom-navbar">
        <a href="../../index.php" class="brand">🔍 UTM Lost & Found</a>
        <div class="nav-links">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="../../tan/claim_status.php">📋 My Claims</a>
            <span>Hi, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
            <a href="../../tey/logout.php" class="btn-custom btn-custom-outline" style="padding: 7px 16px; font-size: 12px;">Logout</a>
        </div>
    </nav>

    <div class="app-container" style="max-width: 900px;">

        <!-- Summary bar -->
        <div class="summary-bar">
            <div>
                <strong><?php echo $total_matches; ?></strong> potential match<?php echo $total_matches != 1 ? 'es' : ''; ?> found for your reports
            </div>
            <a href="dashboard.php?action=run_matching" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 7px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none;">⚡ Re-run Matching</a>
        </div>

        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3" style="border-bottom: 2px solid #f1f5f9;">
                <div>
                    <h2 class="m-0" style="border-bottom: none; padding-bottom: 0; font-size: 20px;">🎯 My Match Results</h2>
                    <p class="m-0 text-muted" style="font-size: 13px; margin-top: 4px;">If you are the lost item owner, click <strong>Claim Item</strong> to file a claim and upload proof.</p>
                </div>
                <a href="dashboard.php" class="btn-custom btn-custom-secondary py-1 px-3 flex-shrink-0" style="font-size: 12px;">← Dashboard</a>
            </div>

            <?php if ($total_matches > 0): ?>
                <?php while ($row = $result->fetch_assoc()):
                    $is_owner  = ($row['lost_owner_id'] == $user_id);
                    $status    = $row['status'];
                    $score     = (int)$row['match_score'];
                    $score_cls = $score >= 70 ? 'high' : ($score >= 45 ? 'med' : 'low');
                    $score_lbl = $score >= 70 ? '🔥 High Match' : ($score >= 45 ? '⚡ Good Match' : '💡 Possible Match');
                ?>
                <div class="match-card">
                    <!-- Card header -->
                    <div class="match-header">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span style="font-size: 13px; font-weight: 700; color: #1e293b;">Match #<?php echo $row['match_id']; ?></span>
                            <span class="score-badge <?php echo $score_cls; ?>"><?php echo $score_lbl; ?> · <?php echo $score; ?>%</span>
                            <?php if ($is_owner): ?>
                                <span style="font-size: 11px; background: #ede9fe; color: #6d28d9; padding: 3px 8px; border-radius: 50px; font-weight: 600;">👤 Your Lost Item</span>
                            <?php else: ?>
                                <span style="font-size: 11px; background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 50px; font-weight: 600;">🤝 Your Found Report</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php
                            if ($status == 'pending')  echo '<span class="status-badge status-badge-pending">⏳ Pending</span>';
                            elseif ($status == 'claimed')  echo '<span class="status-badge status-badge-claimed">📋 Claimed</span>';
                            elseif ($status == 'verified') echo '<span class="status-badge status-badge-verified">✅ Verified</span>';
                            elseif ($status == 'returned') echo '<span class="status-badge status-badge-returned">🎉 Returned</span>';
                            ?>
                        </div>
                    </div>

                    <!-- Card body -->
                    <div class="match-body">
                        <div class="d-flex align-items-start gap-3">
                            <!-- Photo -->
                            <?php if ($row['found_photo']): ?>
                                <img src="../../<?php echo htmlspecialchars($row['found_photo']); ?>" alt="Found item photo" class="match-image">
                            <?php else: ?>
                                <div class="match-image-placeholder">📦</div>
                            <?php endif; ?>

                            <!-- Item comparison -->
                            <div class="flex-grow-1">
                                <div class="row g-2 align-items-stretch">
                                    <!-- Lost item -->
                                    <div class="col-md-5">
                                        <div style="background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 10px; padding: 12px; height: 100%;">
                                            <div class="item-col-label" style="color: #7c3aed;">🔴 Lost Report</div>
                                            <div class="item-col-name"><?php echo htmlspecialchars($row['lost_item_name']); ?></div>
                                            <div class="item-col-desc"><?php echo htmlspecialchars(mb_substr($row['lost_desc'], 0, 100)) . (mb_strlen($row['lost_desc']) > 100 ? '…' : ''); ?></div>
                                        </div>
                                    </div>
                                    <!-- VS -->
                                    <div class="col-md-2 d-flex align-items-center justify-content-center">
                                        <div class="vs-divider flex-column text-center">
                                            <span style="font-size: 16px;">⇄</span>
                                            <span>MATCH</span>
                                        </div>
                                    </div>
                                    <!-- Found item -->
                                    <div class="col-md-5">
                                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px; height: 100%;">
                                            <div class="item-col-label" style="color: #059669;">🟢 Found Report</div>
                                            <div class="item-col-name"><?php echo htmlspecialchars($row['found_item_name']); ?></div>
                                            <div class="item-col-desc"><?php echo htmlspecialchars(mb_substr($row['found_desc'], 0, 100)) . (mb_strlen($row['found_desc']) > 100 ? '…' : ''); ?></div>
                                            <?php if ($row['location_found']): ?>
                                                <div class="item-col-meta">📍 <?php echo htmlspecialchars($row['location_found']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action button row -->
                        <div class="d-flex justify-content-end mt-3 pt-3" style="border-top: 1px solid #f1f5f9;">
                            <?php if ($status === 'pending' && $is_owner): ?>
                                <a href="../../tan/claim_form.php?match_id=<?php echo $row['match_id']; ?>" class="btn-custom btn-custom-success" style="font-size: 13px; padding: 10px 24px;">
                                    🙋 Claim This Item →
                                </a>
                            <?php elseif ($status === 'pending' && !$is_owner): ?>
                                <span class="text-muted" style="font-size: 12px; align-self: center;">⏳ Waiting for the owner to submit a claim…</span>
                            <?php elseif ($status === 'claimed' || $status === 'verified'): ?>
                                <a href="../../tan/claim_status.php" class="btn-custom btn-custom-outline" style="font-size: 13px; padding: 10px 24px;">
                                    📋 Track Claim Status →
                                </a>
                            <?php else: ?>
                                <span style="font-size: 13px; font-weight: 700; color: #059669;">🎉 Item successfully returned!</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>

            <?php else: ?>
                <div class="empty-state">
                    <span class="empty-icon">🔍</span>
                    <h3 style="font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">No matches found yet</h3>
                    <p style="font-size: 14px; margin-bottom: 24px;">Make sure you have active lost/found reports, then run a matching scan from the dashboard.</p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="dashboard.php?action=run_matching" class="btn-custom btn-custom-primary">⚡ Run Matching Scan</a>
                        <a href="../../lee/report_lost.php" class="btn-custom btn-custom-outline">🔴 Report Lost Item</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4 mb-5">
            <a href="dashboard.php" class="btn-custom btn-custom-outline py-2 px-5">← Back to Dashboard</a>
        </div>
    </div>

    <footer class="custom-footer mt-5">
        <p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming Project</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/assistant.js"></script>
</body>
</html>
