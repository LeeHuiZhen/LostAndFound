<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../tey/login.php"); exit;
}

$user_id   = $_SESSION["user_id"];
$user_name = $_SESSION["user_name"];

// Handle acknowledge (dismiss) notification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acknowledge_match_id'])) {
    $mid = intval($_POST['acknowledge_match_id']);
    $s = $conn->prepare("UPDATE matches SET notification_sent = 1 WHERE match_id = ?");
    $s->bind_param("i", $mid); $s->execute(); $s->close();
}

$stmt = $conn->prepare("
    SELECT m.*, li.item_name AS lost_item_name, fi.item_name AS found_item_name,
           fi.location_found, fi.date_found
    FROM matches m
    JOIN lost_items li ON m.lost_item_id = li.item_id
    JOIN found_items fi ON m.found_item_id = fi.item_id
    WHERE li.user_id = ? AND m.notification_sent = 0
    ORDER BY m.created_at DESC
");
$stmt->bind_param("i", $user_id); $stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Alerts — UTM Lost &amp; Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style> body { background-color: var(--bg-base); } </style>
</head>
<body>
    <nav class="custom-navbar">
        <a href="../../index.php" class="brand">🔍 UTM Lost &amp; Found</a>
        <div class="nav-links">
            <a href="dashboard.php" style="font-size:13px; color:var(--text-muted);">📋 Dashboard</a>
            <a href="../../tey/logout.php" class="btn-custom btn-custom-secondary" style="padding:7px 16px; font-size:12px;">Logout</a>
        </div>
    </nav>

    <div class="header-hero" style="padding:50px 20px 40px;">
        <h1 style="font-size:32px; margin-bottom:8px;">🔔 Match Alerts</h1>
        <p>You receive notifications here when our engine links one of your lost reports to a found item.</p>
    </div>

    <div class="app-container" style="max-width:760px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <a href="dashboard.php" class="btn-custom btn-custom-secondary" style="padding:8px 16px; font-size:13px;">← Dashboard</a>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="notification-card animate-fade-up">
                    <div>
                        <h4 style="font-size:14px; font-weight:700; color:#22d3ee; margin-bottom:6px;">🎉 Potential Match Found!</h4>
                        <p style="margin:0; font-size:13px; color:var(--text-secondary);">
                            Your lost <strong style="color:var(--text-primary);"><?php echo htmlspecialchars($row['lost_item_name']); ?></strong>
                            matches found report:
                            <strong style="color:var(--primary-light);"><?php echo htmlspecialchars($row['found_item_name']); ?></strong>
                        </p>
                        <small style="font-size:11px; color:var(--text-muted); display:block; margin-top:6px;">
                            📍 Found at: <strong><?php echo htmlspecialchars($row['location_found']); ?></strong>
                            on <?php echo date('d M Y', strtotime($row['date_found'])); ?>
                            &nbsp;·&nbsp;
                            <span class="status-badge status-badge-claimed"><?php echo $row['match_score']; ?>% match</span>
                        </small>
                    </div>
                    <div style="display:flex; gap:8px; flex-shrink:0;">
                        <a href="../../tan/claim_form.php?match_id=<?php echo $row['match_id']; ?>"
                           class="btn-custom btn-custom-success" style="padding:8px 14px; font-size:12px;">Claim</a>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="acknowledge_match_id" value="<?php echo $row['match_id']; ?>">
                            <button type="submit" class="btn-custom btn-custom-secondary" style="padding:8px 14px; font-size:12px;">Dismiss</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="glass-card text-center" style="padding:60px 20px;">
                <span style="font-size:48px; display:block; margin-bottom:16px;">🔔</span>
                <h3 style="font-weight:700; margin-bottom:8px;">No Active Alerts</h3>
                <p style="color:var(--text-muted); font-size:14px;">Check back after running a matching scan from your dashboard.</p>
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
