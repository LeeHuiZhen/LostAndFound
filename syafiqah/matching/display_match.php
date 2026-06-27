<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../tey/login.php"); exit;
}

$user_id   = $_SESSION["user_id"];
$user_name = $_SESSION["user_name"];

$stmt = $conn->prepare("
    SELECT m.*,
           li.item_name AS lost_item_name, li.description AS lost_desc, li.user_id AS lost_owner_id,
           fi.item_name AS found_item_name, fi.description AS found_desc, fi.user_id AS found_finder_id,
           fi.photo_url AS found_photo
    FROM matches m
    JOIN lost_items li ON m.lost_item_id = li.item_id
    JOIN found_items fi ON m.found_item_id = fi.item_id
    WHERE li.user_id = ? OR fi.user_id = ?
    ORDER BY m.match_score DESC, m.created_at DESC
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Results — UTM Lost &amp; Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background-color: var(--bg-base); }
        .score-badge {
            display: inline-flex; align-items: center; justify-content: center;
            width: 52px; height: 52px; border-radius: 50%;
            background: conic-gradient(var(--primary) calc(var(--pct) * 1%), rgba(99,102,241,0.1) 0%);
            position: relative; flex-shrink: 0;
        }
        .score-badge::after {
            content: attr(data-score) '%';
            position: absolute; inset: 5px;
            background: var(--bg-surface); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 800; color: var(--primary-light);
        }
    </style>
</head>
<body>
    <nav class="custom-navbar">
        <a href="../../index.php" class="brand">🔍 UTM Lost &amp; Found</a>
        <div class="nav-links">
            <a href="dashboard.php" style="font-size:13px; color:var(--text-muted);">📋 Dashboard</a>
            <span class="nav-user-badge">👤 <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
            <a href="../../tey/logout.php" class="btn-custom btn-custom-secondary" style="padding:7px 16px; font-size:12px;">Logout</a>
        </div>
    </nav>

    <div class="header-hero" style="padding:50px 20px 40px;">
        <h1 style="font-size:32px; margin-bottom:8px;">🎯 My Match Results</h1>
        <p>Potential matches calculated by our scoring engine — ranked by confidence.</p>
    </div>

    <div class="app-container" style="max-width:900px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <a href="dashboard.php" class="btn-custom btn-custom-secondary" style="padding:8px 16px; font-size:13px;">← Dashboard</a>
            <?php if ($result): ?>
                <span style="font-size:13px; color:var(--text-muted);"><?php echo $result->num_rows; ?> match(es) found</span>
            <?php endif; ?>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()):
                $is_owner = ($row['lost_owner_id'] == $user_id);
                $status   = $row['status'];
                $score    = $row['match_score'];
            ?>
                <div class="match-card animate-fade-up">
                    <div class="match-header">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <!-- Score ring -->
                            <div class="score-badge" data-score="<?php echo $score; ?>"
                                 style="--pct:<?php echo min($score,100); ?>"></div>
                            <div>
                                <span style="font-weight:700; color:var(--text-primary); font-size:14px;">Match #<?php echo $row['match_id']; ?></span>
                                <br><small style="color:var(--text-muted); font-size:11px;">Confidence score</small>
                            </div>
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
                    <div class="match-body">
                        <div class="row align-items-center g-3">
                            <!-- Photo -->
                            <div class="col-md-2 text-center">
                                <?php if ($row['found_photo']): ?>
                                    <img src="../../<?php echo htmlspecialchars($row['found_photo']); ?>"
                                         alt="Found item" class="match-image">
                                <?php else: ?>
                                    <div class="match-image d-flex align-items-center justify-content-center"
                                         style="background:var(--glass-bg); color:var(--text-muted);">
                                        <span style="font-size:10px;">No Photo</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <!-- Item details -->
                            <div class="col-md-7">
                                <div class="row g-0">
                                    <div class="col-6" style="padding-right:16px; border-right:1px solid var(--border);">
                                        <p style="font-size:10px; color:var(--primary-light); text-transform:uppercase; letter-spacing:0.5px; margin:0 0 4px; font-weight:600;">My Lost Report</p>
                                        <p style="font-size:14px; font-weight:700; color:var(--text-primary); margin:0 0 6px;"><?php echo htmlspecialchars($row['lost_item_name']); ?></p>
                                        <p style="font-size:12px; color:var(--text-muted); margin:0; line-height:1.5;"><?php echo htmlspecialchars(substr($row['lost_desc'],0,100)) . (strlen($row['lost_desc'])>100?'...':''); ?></p>
                                    </div>
                                    <div class="col-6" style="padding-left:16px;">
                                        <p style="font-size:10px; color:#34d399; text-transform:uppercase; letter-spacing:0.5px; margin:0 0 4px; font-weight:600;">Matched Found Report</p>
                                        <p style="font-size:14px; font-weight:700; color:var(--text-primary); margin:0 0 6px;"><?php echo htmlspecialchars($row['found_item_name']); ?></p>
                                        <p style="font-size:12px; color:var(--text-muted); margin:0; line-height:1.5;"><?php echo htmlspecialchars(substr($row['found_desc'],0,100)) . (strlen($row['found_desc'])>100?'...':''); ?></p>
                                    </div>
                                </div>
                            </div>
                            <!-- Action -->
                            <div class="col-md-3 text-end">
                                <?php if ($status == 'pending'): ?>
                                    <?php if ($is_owner): ?>
                                        <a href="../../tan/claim_form.php?match_id=<?php echo $row['match_id']; ?>"
                                           class="btn-custom btn-custom-success w-100" style="font-size:13px; padding:10px;">
                                            🙋 Claim Item
                                        </a>
                                    <?php else: ?>
                                        <span style="font-size:12px; color:var(--text-muted); display:block; text-align:center;">Waiting for owner</span>
                                    <?php endif; ?>
                                <?php elseif (in_array($status, ['claimed','verified'])): ?>
                                    <a href="../../tan/claim_status.php" class="btn-custom btn-custom-outline w-100" style="font-size:13px; padding:10px;">
                                        📋 View Status
                                    </a>
                                <?php else: ?>
                                    <span style="font-size:13px; color:#a78bfa; font-weight:700; display:block; text-align:center;">🎉 Handed Over!</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="glass-card text-center" style="padding:60px 20px;">
                <span style="font-size:48px; display:block; margin-bottom:16px;">🔍</span>
                <h3 style="font-weight:700; margin-bottom:8px;">No Matches Found Yet</h3>
                <p style="color:var(--text-muted); font-size:14px; margin-bottom:24px;">Make sure you have active reports, then run a matching scan.</p>
                <a href="dashboard.php?action=run_matching" class="btn-custom btn-custom-primary" style="padding:12px 28px;">⚡ Run Matching Scan Now</a>
            </div>
        <?php endif; ?>

        <div style="text-align:center; margin-top:32px;">
            <a href="dashboard.php" class="btn-custom btn-custom-outline">← Back to Dashboard</a>
        </div>
    </div>

    <footer class="custom-footer mt-5"><p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming</p></footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
