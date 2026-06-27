<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../tey/login.php"); exit;
}

$user_id   = $_SESSION["user_id"];
$user_name = $_SESSION["user_name"];

// Fetch all claims made by this user
$stmt = $conn->prepare("
    SELECT c.*,
           li.item_name AS lost_item_name,
           fi.item_name AS found_item_name,
           fi.location_found
    FROM claims c
    JOIN matches m ON c.match_id = m.match_id
    JOIN lost_items li ON m.lost_item_id = li.item_id
    JOIN found_items fi ON m.found_item_id = fi.item_id
    WHERE c.owner_id = ?
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Claims — UTM Lost &amp; Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style> body { background-color: var(--bg-base); } </style>
</head>
<body>
    <nav class="custom-navbar">
        <a href="../index.php" class="brand">🔍 UTM Lost &amp; Found</a>
        <div class="nav-links">
            <a href="../syafiqah/matching/dashboard.php" style="font-size:13px; color:var(--text-muted);">📋 Dashboard</a>
            <span class="nav-user-badge">👤 <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
            <a href="../tey/logout.php" class="btn-custom btn-custom-secondary" style="padding:7px 16px; font-size:12px;">Logout</a>
        </div>
    </nav>

    <div class="header-hero" style="padding:50px 20px 40px;">
        <h1 style="font-size:32px; margin-bottom:8px;">📑 My Claim History</h1>
        <p>Track the verification status of your submitted ownership claims.</p>
    </div>

    <div class="app-container" style="max-width:860px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-secondary" style="padding:8px 16px; font-size:13px;">← Dashboard</a>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="glass-card animate-fade-up">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Claim ID</th>
                                <th>My Item</th>
                                <th>Matched Item</th>
                                <th>Submitted Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong style="color:var(--primary-light);">#<?php echo $row['claim_id']; ?></strong></td>
                                    <td><strong style="color:var(--text-primary);"><?php echo htmlspecialchars($row['lost_item_name']); ?></strong></td>
                                    <td>
                                        <span style="font-weight:600;"><?php echo htmlspecialchars($row['found_item_name']); ?></span>
                                        <br><small style="color:var(--text-muted);">📍 <?php echo htmlspecialchars($row['location_found']); ?></small>
                                    </td>
                                    <td><span style="font-size:13px;"><?php echo date('d M Y', strtotime($row['created_at'])); ?></span></td>
                                    <td>
                                        <?php
                                        $s = $row['status'];
                                        if ($s == 'pending') {
                                            echo '<span class="status-badge status-badge-pending">⏳ Pending Review</span>';
                                        } elseif ($s == 'verified') {
                                            echo '<span class="status-badge status-badge-verified">✅ Verified</span>';
                                        } elseif ($s == 'rejected') {
                                            echo '<span class="status-badge status-badge-rejected">❌ Rejected</span>';
                                        } elseif ($s == 'returned') {
                                            echo '<span class="status-badge status-badge-returned">🎉 Returned</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="glass-card text-center" style="padding:60px 20px;">
                <span style="font-size:48px; display:block; margin-bottom:16px;">📑</span>
                <h3 style="font-weight:700; margin-bottom:8px;">No Claims Filed Yet</h3>
                <p style="color:var(--text-muted); font-size:14px; margin-bottom:24px;">When you find a potential match on your dashboard, submit a claim to verify ownership.</p>
                <a href="../syafiqah/matching/display_match.php" class="btn-custom btn-custom-primary" style="padding:12px 28px;">🎯 View Potential Matches</a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="custom-footer mt-5"><p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming</p></footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
