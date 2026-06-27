<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../tey/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$user_name = $_SESSION["user_name"];

// Handle acknowledgment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acknowledge_match_id'])) {
    $match_id = intval($_POST['acknowledge_match_id']);
    $update_sql = "UPDATE matches SET notification_sent = 1 WHERE match_id = ?";
    $up_stmt = $conn->prepare($update_sql);
    $up_stmt->bind_param("i", $match_id);
    $up_stmt->execute();
    $up_stmt->close();
}

$sql = "
SELECT 
    m.*, 
    li.item_name AS lost_item_name, 
    fi.item_name AS found_item_name,
    fi.location_found,
    fi.date_found
FROM matches m
JOIN lost_items li ON m.lost_item_id = li.item_id
JOIN found_items fi ON m.found_item_id = fi.item_id
WHERE li.user_id = ? AND m.notification_sent = 0
ORDER BY m.created_at DESC
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
    <title>Match Alerts - Lost & Found Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background-color: var(--light-bg); }
        .notification-card {
            background-color: #f0f9ff;
            border-left: 6px solid var(--info-color);
            border-radius: var(--radius-md);
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
        }
        .notification-card:hover {
            transform: translateX(4px);
            box-shadow: var(--shadow-md);
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
                <h2 class="m-0">🔔 Match Alerts</h2>
                <a href="dashboard.php" class="btn-custom btn-custom-secondary py-1 px-3" style="font-size: 12px;">⬅ Dashboard</a>
            </div>

            <p class="text-muted mb-4" style="font-size: 14px;">
                You will receive notifications here when the system links one of your active lost reports to a found item.
            </p>

            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="notification-card">
                        <div>
                            <h4 style="font-size: 15px; font-weight: 700; color: #0369a1; margin-bottom: 5px;">🎉 Potential Match Found!</h4>
                            <p class="m-0" style="font-size: 13px; color: var(--text-main);">
                                Your lost <strong><?php echo htmlspecialchars($row['lost_item_name']); ?></strong> matches found report: 
                                <strong style="color: var(--primary-color);"><?php echo htmlspecialchars($row['found_item_name']); ?></strong>
                            </p>
                            <small class="text-muted" style="font-size: 11px; display: block; margin-top: 5px;">
                                Found at: <strong><?php echo htmlspecialchars($row['location_found']); ?></strong> on <?php echo date('d M Y', strtotime($row['date_found'])); ?> (Score: <?php echo $row['match_score']; ?>%)
                            </small>
                        </div>
                        <div class="d-flex gap-2 ms-3">
                            <a href="../../tan/claim_form.php?match_id=<?php echo $row['match_id']; ?>" class="btn-custom btn-custom-success py-1 px-3" style="font-size: 11px; border-radius: 12px;">Claim</a>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="acknowledge_match_id" value="<?php echo $row['match_id']; ?>">
                                <button type="submit" class="btn-custom btn-custom-outline py-1 px-3" style="font-size: 11px; border-radius: 12px; border-color: #cbd5e1; color: var(--text-muted);">Dismiss</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <span style="font-size: 40px;">🔔</span>
                    <p class="mt-3 m-0">No active match alerts.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>