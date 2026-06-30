<?php
session_start();               // FIXED: session_start() must be before any output or includes
include '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../tey/login.php");
    exit();
}

$user_id   = (int)$_SESSION['user_id'];   // cast to int for safe SQL
$user_name = $_SESSION['user_name'];

// FIXED: Use prepared statement instead of raw $user_id interpolation
$stmt = $conn->prepare("
    SELECT c.*, m.match_id,
           li.item_name AS lost_item, li.description AS lost_desc, li.photo_url AS lost_photo_url,
           fi.item_name AS found_item, fi.description AS found_desc, fi.photo_url AS found_photo_url
    FROM claims c
    JOIN matches m     ON c.match_id = m.match_id
    JOIN lost_items li ON m.lost_item_id = li.item_id
    JOIN found_items fi ON m.found_item_id = fi.item_id
    WHERE c.owner_id = ?
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total  = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Claim Status – UTM Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=3">
    <style>
        body {
            background: linear-gradient(rgba(2,6,23,0.5), rgba(15,23,42,0.65)),
                        url('../LostAndFound_found.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }

        /* Hero override to be transparent over the photo background */
        .header-hero {
            background: rgba(15,23,42,0.4) !important;
            backdrop-filter: blur(4px);
            animation: none;
        }

        /* Claim card */
        .claim-row-card {
            background: rgba(255,255,255,0.98);
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 18px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            transition: all 0.25s ease;
            animation: fadeInUp 0.5s ease both;
        }
        .claim-row-card:hover {
            box-shadow: 0 16px 36px rgba(79,70,229,0.14);
            border-color: #c7d2fe;
            transform: translateY(-3px);
        }

        /* Action hint boxes */
        .hint-success {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border: 1px solid #a7f3d0;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            color: #065f46;
            font-weight: 600;
            margin-top: 12px;
        }
        .hint-danger {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border: 1px solid #fca5a5;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            color: #991b1b;
            font-weight: 600;
            margin-top: 12px;
        }
        .hint-info {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border: 1px solid #bae6fd;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            color: #075985;
            font-weight: 600;
            margin-top: 12px;
        }

        /* Item thumbnail */
        .item-thumb {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            flex-shrink: 0;
        }
        .item-thumb-placeholder {
            width: 72px;
            height: 72px;
            background: #f1f5f9;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="custom-navbar">
        <a href="../index.php" class="brand">🔍 UTM Lost & Found</a>
        <div class="nav-links">
            <a href="../syafiqah/matching/dashboard.php">📊 Dashboard</a>
            <a href="../syafiqah/matching/display_match.php">🎯 Matches</a>
            <span>Hi, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
            <a href="../tey/logout.php" class="btn-custom btn-custom-outline" style="padding: 7px 16px; font-size: 12px;">Logout</a>
        </div>
    </nav>

    <!-- ===== HERO ===== -->
    <div class="header-hero" style="padding: 50px 20px;">
        <h1>📋 My Claim Status</h1>
        <p>Track your ownership proof submissions and monitor verification progress.</p>
    </div>

    <div class="app-container" style="max-width: 920px;">

        <?php if ($total > 0): ?>
            <?php while ($row = $result->fetch_assoc()):
                $status = $row['status'];

                // Timeline step states — FIXED LOGIC:
                // pending   → Submitted ✓ | Under Review (active) | Verified — | Collected —
                // verified  → Submitted ✓ | Under Review ✓         | Verified (active) | Collected —
                // rejected  → Submitted ✓ | Under Review (rejected) | — | —
                // returned  → Submitted ✓ | Under Review ✓          | Verified ✓ | Collected ✓
                $step1 = 'done';
                $step2 = match($status) {
                    'verified', 'returned' => 'done',
                    'pending'              => 'active',
                    'rejected'             => 'rejected',
                    default                => 'active'
                };
                $step3 = match($status) {
                    'returned' => 'done',
                    'verified' => 'active',
                    default    => ''
                };
                $step4 = ($status === 'returned') ? 'done' : '';

                $icon2 = match($step2) {
                    'done'     => '✓',
                    'rejected' => '✕',
                    'active'   => '⏳',
                    default    => '—'
                };
                $icon3 = match($step3) {
                    'done'   => '✓',
                    'active' => '✅',
                    default  => '—'
                };
            ?>
            <div class="claim-row-card">

                <!-- TIMELINE STEPPER -->
                <div class="claim-timeline mb-3">
                    <div class="timeline-step done">
                        <div class="timeline-dot done">✓</div>
                        <div class="timeline-label">Submitted</div>
                    </div>
                    <div class="timeline-step <?php echo $step2; ?>">
                        <div class="timeline-dot <?php echo $step2; ?>"><?php echo $icon2; ?></div>
                        <div class="timeline-label"><?php echo $step2 === 'rejected' ? 'Rejected' : 'Under Review'; ?></div>
                    </div>
                    <div class="timeline-step <?php echo $step3; ?>">
                        <div class="timeline-dot <?php echo $step3; ?>"><?php echo $icon3; ?></div>
                        <div class="timeline-label">Verified</div>
                    </div>
                    <div class="timeline-step <?php echo $step4; ?>">
                        <div class="timeline-dot <?php echo $step4; ?>"><?php echo $step4 === 'done' ? '🎉' : '—'; ?></div>
                        <div class="timeline-label">Collected</div>
                    </div>
                </div>

                <!-- CLAIM DETAILS -->
                <div class="d-flex align-items-start gap-3">
                    <!-- Thumbnail -->
                    <div class="d-flex gap-2 flex-shrink-0">
                        <?php if ($row['lost_photo_url']): ?>
                            <img src="../<?php echo htmlspecialchars($row['lost_photo_url']); ?>" alt="Lost photo" class="item-thumb" title="Lost Item Photo">
                        <?php endif; ?>
                        <?php if ($row['found_photo_url']): ?>
                            <img src="../<?php echo htmlspecialchars($row['found_photo_url']); ?>" alt="Found photo" class="item-thumb" title="Found Item Photo">
                        <?php endif; ?>
                        <?php if (!$row['lost_photo_url'] && !$row['found_photo_url']): ?>
                            <div class="item-thumb-placeholder">📦</div>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                            <strong style="font-size: 15px;">Claim #<?php echo $row['claim_id']; ?></strong>
                            <?php
                            if ($status === 'pending')  echo '<span class="status-badge status-badge-pending">⏳ Under Review</span>';
                            elseif ($status === 'verified') echo '<span class="status-badge status-badge-verified">✅ Verified</span>';
                            elseif ($status === 'rejected') echo '<span class="status-badge status-badge-rejected">❌ Rejected</span>';
                            elseif ($status === 'returned') echo '<span class="status-badge status-badge-returned">🎉 Returned</span>';
                            ?>
                        </div>

                        <p class="m-0" style="font-size: 13px; color: #475569;">
                            <strong>Lost:</strong> <?php echo htmlspecialchars($row['lost_item']); ?>
                            &nbsp;<span style="color:#94a3b8;">→</span>&nbsp;
                            <strong>Matched with:</strong> <?php echo htmlspecialchars($row['found_item']); ?>
                        </p>
                        <p class="m-0 mt-1" style="font-size: 11px; color: #94a3b8;">
                            Filed on <?php echo date('d M Y, g:i A', strtotime($row['created_at'])); ?>
                        </p>

                        <!-- Contextual hints -->
                        <?php if ($status === 'verified'): ?>
                            <div class="hint-success">
                                🎉 Your claim is <strong>approved!</strong> Proceed to the <strong>Campus Security Office</strong> to collect your item. Bring your student ID.
                            </div>
                        <?php elseif ($status === 'rejected'): ?>
                            <div class="hint-danger">
                                ❌ Claim was <strong>rejected</strong>. Please resubmit with stronger proof of ownership (e.g., receipt, unique marking photos).
                                <a href="../syafiqah/matching/display_match.php" style="color: #991b1b; display: block; margin-top: 6px;">→ Go to Matches to re-claim</a>
                            </div>
                        <?php elseif ($status === 'pending'): ?>
                            <div class="hint-info">
                                ⏳ Your claim is being reviewed by Campus Security. You will see the status update here once processed.
                            </div>
                        <?php elseif ($status === 'returned'): ?>
                            <div class="hint-success">🎉 Item successfully collected! Thank you for using the UTM Lost & Found system.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>

        <?php else: ?>
            <div class="glass-card text-center py-5" style="background: rgba(255,255,255,0.97) !important;">
                <span style="font-size: 56px; display: block; margin-bottom: 16px;">📋</span>
                <h3 style="font-size: 20px; font-weight: 700; color: #1e293b;">No Claims Yet</h3>
                <p class="text-muted" style="font-size: 14px; max-width: 420px; margin: 10px auto 24px;">
                    When you find a match for your lost item, click "Claim Item" to submit proof of ownership. It will appear here.
                </p>
                <a href="../syafiqah/matching/display_match.php" class="btn-custom btn-custom-primary py-2 px-4">🎯 Check My Matches</a>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4 mb-5">
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
