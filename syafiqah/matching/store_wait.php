<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../tey/login.php");
    exit;
}

$user_id   = $_SESSION["user_id"];
$user_name = $_SESSION["user_name"];

// Fetch the user's active (non-returned) lost reports with their best claim status
// FIX: Join claims WITHOUT the status filter in the ON clause so we get all claim statuses
$stmt = $conn->prepare("
    SELECT li.*,
           COALESCE(c.status, '') AS claim_status,
           (SELECT COUNT(*) FROM matches WHERE lost_item_id = li.item_id AND status != 'returned') AS match_count
    FROM lost_items li
    LEFT JOIN matches m  ON li.item_id = m.lost_item_id
    LEFT JOIN claims  c  ON m.match_id = c.match_id
    WHERE li.user_id = ? AND li.status != 'returned'
    GROUP BY li.item_id
    ORDER BY li.date_lost DESC, li.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Reports – UTM Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css?v=3">
    <style>
        body {
            background: linear-gradient(rgba(2,6,23,0.5), rgba(15,23,42,0.65)),
                        url('../../LostAndFound_found.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }

        .glass-card {
            background: rgba(255,255,255,0.97) !important;
            backdrop-filter: blur(10px);
        }

        /* Pending item card */
        .pending-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px 20px;
            margin-bottom: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.25s ease;
            animation: fadeInUp 0.4s ease both;
        }
        .pending-card:hover {
            box-shadow: 0 10px 28px rgba(79,70,229,0.12);
            border-color: #c7d2fe;
            transform: translateY(-2px);
        }

        /* Status pill (right side) */
        .status-pill-verified { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .status-pill-claimed  { background: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }
        .status-pill-matched  { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .status-pill-waiting  { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .status-pill {
            font-size: 12px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 50px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* Number badge */
        .item-num {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg,#4f46e5,#7c3aed);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
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
    </style>
</head>
<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="custom-navbar">
        <a href="../../index.php" class="brand">🔍 UTM Lost & Found</a>
        <div class="nav-links">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="display_match.php">🎯 Matches</a>
            <span>Hi, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
            <a href="../../tey/logout.php" class="btn-custom btn-custom-outline" style="padding: 7px 16px; font-size: 12px;">Logout</a>
        </div>
    </nav>

    <div class="app-container" style="max-width: 800px;">

        <!-- Summary bar -->
        <div class="summary-bar">
            <div>
                <strong><?php echo $total; ?></strong> active lost report<?php echo $total != 1 ? 's' : ''; ?> being monitored
            </div>
            <a href="dashboard.php?action=run_matching" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 7px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none;">⚡ Run Matching Scan</a>
        </div>

        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3" style="border-bottom: 2px solid #f1f5f9;">
                <div>
                    <h2 class="m-0" style="border-bottom: none; padding-bottom: 0; font-size: 20px;">⏳ My Pending Reports</h2>
                    <p class="m-0 text-muted" style="font-size: 13px; margin-top: 4px;">Lost items you reported that are actively waiting for a match or under review.</p>
                </div>
                <a href="dashboard.php" class="btn-custom btn-custom-secondary py-1 px-3 flex-shrink-0" style="font-size: 12px;">← Dashboard</a>
            </div>

            <?php if ($total > 0):
                $n = 0;
                while ($row = $result->fetch_assoc()):
                    $n++;
                    $item_status  = $row['status'];       // lost_items.status
                    $claim_status = $row['claim_status'];  // claims.status (can be empty)
                    $match_count  = (int)$row['match_count'];

                    // Determine display state — FIXED LOGIC:
                    // Priority: verified > returned > claimed > matched > waiting
                    if ($item_status === 'returned' || $claim_status === 'returned') {
                        // Should not appear (query filters out returned), but safety fallback
                        $pill = 'status-pill-verified'; $label = '🎉 Returned';
                    } elseif ($claim_status === 'verified') {
                        $pill = 'status-pill-verified'; $label = '✅ Claim Verified';
                    } elseif ($claim_status === 'pending' || $item_status === 'claimed') {
                        $pill = 'status-pill-claimed'; $label = '📋 Claim Under Review';
                    } elseif ($match_count > 0) {
                        $pill = 'status-pill-matched'; $label = "🎯 $match_count Match" . ($match_count > 1 ? 'es' : '') . ' Found';
                    } else {
                        $pill = 'status-pill-waiting'; $label = '⏳ Waiting for Match';
                    }
            ?>
                <div class="pending-card">
                    <!-- Number badge -->
                    <div class="item-num"><?php echo $n; ?></div>

                    <!-- Item details -->
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span style="font-size: 15px; font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($row['item_name']); ?></span>
                            <span style="font-size: 10px; color: #94a3b8; font-weight: 500;">#<?php echo $row['item_id']; ?></span>
                        </div>
                        <p class="m-0" style="font-size: 12px; color: #64748b; line-height: 1.4; max-width: 480px;">
                            <?php echo htmlspecialchars(mb_substr($row['description'], 0, 120)) . (mb_strlen($row['description']) > 120 ? '…' : ''); ?>
                        </p>
                        <small class="d-block mt-1" style="font-size: 11px; color: #94a3b8;">
                            📍 <?php echo htmlspecialchars($row['location_lost']); ?> &nbsp;·&nbsp;
                            📅 <?php echo date('d M Y', strtotime($row['date_lost'])); ?>
                        </small>
                    </div>

                    <!-- Status + action -->
                    <div class="d-flex flex-column align-items-end gap-2">
                        <span class="status-pill <?php echo $pill; ?>"><?php echo $label; ?></span>
                        <?php if ($match_count > 0 && $item_status !== 'claimed' && $claim_status !== 'verified'): ?>
                            <a href="display_match.php" style="font-size: 11px; color: #4f46e5; text-decoration: none; font-weight: 600;">View Matches →</a>
                        <?php elseif ($claim_status === 'pending' || $item_status === 'claimed'): ?>
                            <a href="../../tan/claim_status.php" style="font-size: 11px; color: #0369a1; text-decoration: none; font-weight: 600;">Track Claim →</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>

            <?php else: ?>
                <div class="text-center py-5" style="color: #64748b;">
                    <span style="font-size: 56px; display: block; margin-bottom: 16px;">✨</span>
                    <h3 style="font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">No pending reports</h3>
                    <p style="font-size: 14px; margin-bottom: 24px;">All your lost reports have been matched and returned, or you haven't filed any yet.</p>
                    <a href="../../lee/report_lost.php" class="btn-custom btn-custom-primary py-2 px-4">🔴 Report a Lost Item</a>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4 pt-3" style="border-top: 1px solid #f1f5f9;">
                <a href="dashboard.php" class="btn-custom btn-custom-outline py-2 px-5">← Back to Dashboard</a>
            </div>
        </div>
    </div>

    <footer class="custom-footer mt-5">
        <p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming Project</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/assistant.js"></script>
</body>
</html>
