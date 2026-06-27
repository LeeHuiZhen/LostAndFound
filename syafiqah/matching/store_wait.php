<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../tey/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$user_name = $_SESSION["user_name"];

$sql = "
SELECT *
FROM lost_items
WHERE user_id = ? AND status = 'pending'
ORDER BY date_lost DESC, created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Reports - Lost & Found Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background-color: var(--light-bg); }
        .pending-item-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
        }
        .pending-item-card:hover {
            box-shadow: var(--shadow-md);
            border-color: #cbd5e1;
        }
    </style>
</head>
<body>
    <nav class="custom-navbar">
        <a href="../../index.php" class="brand">🔍 UTM Lost & Found</a>
        <div class="nav-links">
            <a href="dashboard.php" style="color: var(--text-muted); text-decoration: none; font-size: 14px; font-weight: 500; margin-right: 15px;">📋 Dashboard</a>
            <a href="../../tey/logout.php" class="btn-custom btn-custom-outline" style="padding: 6px 16px; font-size: 12px;">🚪 Logout</a>
        </div>
    </nav>

    <div class="app-container" style="max-width: 750px;">
        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2" style="border-bottom: 2px solid #f1f5f9;">
                <h2 class="m-0">⏳ My Pending Reports</h2>
                <a href="dashboard.php" class="btn-custom btn-custom-secondary py-1 px-3" style="font-size: 12px;">⬅ Dashboard</a>
            </div>

            <p class="text-muted mb-4" style="font-size: 14px;">
                These are reported lost items waiting for a matching found report. The system scans active reports automatically.
            </p>

            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="pending-item-card">
                        <div>
                            <h4 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin: 0 0 5px 0;">
                                <?php echo htmlspecialchars($row['item_name']); ?>
                            </h4>
                            <p class="text-muted m-0" style="font-size: 13px; line-height: 1.4; max-width: 500px;">
                                <?php echo htmlspecialchars($row['description']); ?>
                            </p>
                            <small class="text-muted mt-2 d-block" style="font-size: 11px;">
                                Lost at: <strong><?php echo htmlspecialchars($row['location_lost']); ?></strong> on <?php echo date('d M Y', strtotime($row['date_lost'])); ?>
                            </small>
                        </div>
                        <div class="text-end ms-3">
                            <span class="status-badge status-badge-pending">⏳ Waiting for Match</span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <span style="font-size: 40px;">⏳</span>
                    <p class="mt-3 m-0">No active pending reports.</p>
                    <a href="../../lee/report_lost.php" class="btn-custom btn-custom-primary mt-3 py-1 px-4" style="font-size: 12px; border-radius: 12px;">Report a Lost Item</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>