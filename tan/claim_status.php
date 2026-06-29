<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../tey/login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
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
    <title>My Claims – UTM Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(rgba(2, 6, 23, 0.45), rgba(15, 23, 42, 0.55)),
                        url('../LostAndFound_found.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }
        .claim-row-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: var(--shadow-sm);
            transition: all 0.25s ease;
            animation: fadeInUp 0.5s ease both;
        }
        .claim-row-card:hover {
            box-shadow: var(--shadow-md);
            border-color: #c7d2fe;
        }
        .claim-action-hint {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border: 1px solid #a7f3d0;
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            font-size: 12px;
            color: #065f46;
            font-weight: 600;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="custom-navbar">
        <a href="../index.php" class="brand">🔍 UTM Lost & Found</a>
        <div class="nav-links">
            <a href="../syafiqah/matching/dashboard.php">📊 Dashboard</a>
            <span>Hi, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
            <a href="../tey/logout.php" class="btn-custom btn-custom-outline" style="padding: 7px 16px; font-size: 12px;">Logout</a>
        </div>
    </nav>

    <!-- ===== HERO ===== -->
    <div class="header-hero" style="padding: 50px 20px;">
        <h1>📋 My Claim Status</h1>
        <p>Track your ownership proof submissions and monitor verification progress.</p>
    </div>

    <div class="app-container" style="max-width: 900px;">

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()):
                $status = $row['status'];
                // Timeline: submitted, under_review, verified, returned
                $step1 = 'done'; // always submitted
                $step2 = in_array($status, ['verified','returned']) ? 'done' : ($status == 'pending' ? 'active' : 'rejected');
                $step3 = $status == 'returned' ? 'done' : ($status == 'verified' ? 'active' : '');
                $step4 = $status == 'returned' ? 'done' : '';
            ?>
            <div class="claim-row-card">
                <!-- TIMELINE STEPPER -->
                <div class="claim-timeline">
                    <div class="timeline-step done">
                        <div class="timeline-dot done">✓</div>
                        <div class="timeline-label">Submitted</div>
                    </div>
                    <div class="timeline-step <?php echo $step2; ?>">
                        <div class="timeline-dot <?php echo $step2; ?>">
                            <?php echo $step2 == 'done' ? '✓' : ($step2 == 'rejected' ? '✕' : '⏳'); ?>
                        </div>
                        <div class="timeline-label">Under Review</div>
                    </div>
                    <div class="timeline-step <?php echo $step3; ?>">
                        <div class="timeline-dot <?php echo $step3; ?>">
                            <?php echo $step3 == 'done' ? '✓' : ($step3 == 'active' ? '✅' : '—'); ?>
                        </div>
                        <div class="timeline-label">Verified</div>
                    </div>
                    <div class="timeline-step <?php echo $step4; ?>">
                        <div class="timeline-dot <?php echo $step4; ?>">
                            <?php echo $step4 == 'done' ? '🎉' : '—'; ?>
                        </div>
                        <div class="timeline-label">Collected</div>
                    </div>
                </div>

                <!-- CLAIM DETAILS -->
                <div class="row align-items-center g-3">
                    <div class="col-md-2 text-center">
                        <?php if ($row['photo_url']): ?>
                            <img src="../<?php echo htmlspecialchars($row['photo_url']); ?>" alt="Item" style="width: 70px; height: 70px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                        <?php else: ?>
                            <div style="width: 70px; height: 70px; background: #f1f5f9; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto;">📦</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-7">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <strong style="font-size: 15px;">Claim #<?php echo $row['claim_id']; ?></strong>
                            <?php
                            if ($status == 'pending')  echo '<span class="status-badge status-badge-pending">⏳ Under Review</span>';
                            elseif ($status == 'verified') echo '<span class="status-badge status-badge-verified">✅ Verified</span>';
                            elseif ($status == 'rejected') echo '<span class="status-badge status-badge-rejected">❌ Rejected</span>';
                            elseif ($status == 'returned') echo '<span class="status-badge status-badge-returned">🎉 Returned</span>';
                            ?>
                        </div>
                        <p class="m-0 text-muted" style="font-size: 13px;">
                            <strong>Lost:</strong> <?php echo htmlspecialchars($row['lost_item']); ?> 
                            &nbsp;→&nbsp; 
                            <strong>Matched with:</strong> <?php echo htmlspecialchars($row['found_item']); ?>
                        </p>
                        <p class="m-0 text-muted mt-1" style="font-size: 11px;">
                            Filed on <?php echo date('d M Y, g:i A', strtotime($row['created_at'])); ?>
                        </p>

                        <?php if ($status == 'verified'): ?>
                            <div class="claim-action-hint">
                                🎉 Your claim is approved! Please proceed to the <strong>Campus Security Office</strong> to collect your item. Bring your student ID.
                            </div>
                        <?php elseif ($status == 'rejected'): ?>
                            <div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: var(--radius-sm); padding: 10px 14px; font-size: 12px; color: #991b1b; font-weight: 600; margin-top: 10px;">
                                ❌ Claim was rejected. Please resubmit with stronger proof of ownership.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3 text-end">
                        <?php if ($status == 'pending'): ?>
                            <div style="font-size: 12px; color: var(--text-muted); text-align: center;">
                                Waiting for admin review...
                            </div>
                        <?php elseif ($status == 'returned'): ?>
                            <div style="font-size: 13px; font-weight: 700; color: var(--success-color); text-align: center;">🎉 Item Collected!</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>

        <?php else: ?>
            <div class="glass-card text-center py-5">
                <span style="font-size: 56px; display: block; margin-bottom: 16px;">📋</span>
                <h3 style="font-size: 20px; font-weight: 700; color: var(--text-main);">No Claims Yet</h3>
                <p class="text-muted" style="font-size: 14px; max-width: 400px; margin: 10px auto 24px;">When you find a match for your lost item, submit a claim with proof of ownership. It will appear here.</p>
                <a href="../syafiqah/matching/display_match.php" class="btn-custom btn-custom-primary py-2 px-4">Check My Matches</a>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-outline py-2 px-5">← Return to Dashboard</a>
        </div>
    </div>

    <footer class="custom-footer mt-5">
        <p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming Project</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/assistant.js"></script>
</body>
</html>
