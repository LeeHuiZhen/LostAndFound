<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../tey/login.php"); exit;
}

$user_id   = $_SESSION["user_id"];
$user_name = $_SESSION["user_name"];

// Security Fix #5: correlated subquery to avoid duplicate rows when one lost item has multiple matches
$stmt = $conn->prepare("
    SELECT li.*,
           (SELECT c.status FROM claims c
            JOIN matches m ON c.match_id = m.match_id
            WHERE m.lost_item_id = li.item_id AND c.status = 'verified' LIMIT 1) AS claim_status
    FROM lost_items li
    WHERE li.user_id = ? AND li.status != 'returned'
    ORDER BY li.date_lost DESC, li.created_at DESC
");
$stmt->bind_param("i", $user_id); $stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Reports — UTM Lost &amp; Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style> body { background-color: var(--bg-base); } </style>
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
        <h1 style="font-size:32px; margin-bottom:8px;">⏳ My Pending Reports</h1>
        <p>Active lost reports waiting for a matching found item. The engine scans 24/7.</p>
    </div>

    <div class="app-container" style="max-width:760px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <a href="dashboard.php" class="btn-custom btn-custom-secondary" style="padding:8px 16px; font-size:13px;">← Dashboard</a>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()):
                $s  = $row['status'];
                $cs = $row['claim_status'] ?? '';
            ?>
                <div class="pending-item-card animate-fade-up">
                    <div style="flex:1; min-width:0;">
                        <h4 style="font-size:16px; font-weight:700; color:var(--text-primary); margin:0 0 6px;">
                            <?php echo htmlspecialchars($row['item_name']); ?>
                        </h4>
                        <p style="font-size:13px; color:var(--text-muted); margin:0 0 8px; line-height:1.5; max-width:480px;">
                            <?php echo htmlspecialchars($row['description']); ?>
                        </p>
                        <small style="font-size:11px; color:var(--text-muted);">
                            📍 <?php echo htmlspecialchars($row['location_lost']); ?>
                            &nbsp;·&nbsp;
                            <?php echo date('d M Y', strtotime($row['date_lost'])); ?>
                        </small>
                    </div>
                    <div style="flex-shrink:0; margin-left:16px;">
                        <?php
                        if ($cs == 'verified')   echo '<span class="status-badge status-badge-verified">✅ Verified</span>';
                        elseif ($s == 'claimed') echo '<span class="status-badge status-badge-claimed">📋 Claimed</span>';
                        else                     echo '<span class="status-badge status-badge-pending">⏳ Waiting</span>';
                        ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="glass-card text-center" style="padding:60px 20px;">
                <span style="font-size:48px; display:block; margin-bottom:16px;">⏳</span>
                <h3 style="font-weight:700; margin-bottom:8px;">No Pending Reports</h3>
                <p style="color:var(--text-muted); font-size:14px; margin-bottom:24px;">All your lost reports have been matched, or you haven't reported anything yet.</p>
                <a href="../../lee/report_lost.php" class="btn-custom btn-custom-primary" style="padding:12px 28px;">📝 Report a Lost Item</a>
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

