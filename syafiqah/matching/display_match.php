<?php
session_start();
// Fix database connection path
require_once '../../config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../tey/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$user_name = $_SESSION["user_name"];

// Fetch matches where the logged-in user is either the owner of the lost item or the reporter of the found item
$sql = "
SELECT 
    m.*, 
    li.item_name AS lost_item_name, 
    li.description AS lost_desc,
    li.user_id AS lost_owner_id,
    fi.item_name AS found_item_name,
    fi.description AS found_desc,
    fi.user_id AS found_finder_id,
    fi.photo_url AS found_photo
FROM matches m
JOIN lost_items li ON m.lost_item_id = li.item_id
JOIN found_items fi ON m.found_item_id = fi.item_id
WHERE li.user_id = ? OR fi.user_id = ?
ORDER BY m.match_score DESC, m.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Potential Match Results - Lost & Found Assistant</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body {
            background-color: var(--light-bg);
        }
        .match-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
        }
        .match-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }
        .match-header {
            background: #f8fafc;
            border-bottom: 1px solid var(--border-color);
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .match-body {
            padding: 20px;
        }
        .match-image {
            max-width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
        }
    </style>
</head>
<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="custom-navbar">
        <a href="../../index.php" class="brand">
            🔍 UTM Lost & Found
        </a>
        <div class="nav-links">
            <a href="dashboard.php" style="color: var(--text-muted); text-decoration: none; font-size: 14px; font-weight: 500; margin-right: 15px;">📋 Dashboard</a>
            <span style="font-size: 14px; font-weight: 500; color: var(--text-muted); margin-right: 15px;">
                Welcome, <strong style="color: var(--primary-color);"><?php echo htmlspecialchars($user_name); ?></strong>
            </span>
            <a href="../../tey/logout.php" class="btn-custom btn-custom-outline" style="padding: 6px 16px; font-size: 12px;">🚪 Logout</a>
        </div>
    </nav>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="app-container" style="max-width: 850px;">
        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2" style="border-bottom: 2px solid #f1f5f9;">
                <h2 class="m-0" style="border-bottom: none; padding-bottom: 0;">🎯 My Match Results</h2>
                <a href="dashboard.php" class="btn-custom btn-custom-secondary py-1 px-3" style="font-size: 12px;">⬅ Dashboard</a>
            </div>

            <p class="text-muted mb-4" style="font-size: 14px;">
                Below are the potential matches calculated by our system. If you are the owner of the lost item, click **Claim Item** to file a claim and upload proof.
            </p>

            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                    $is_owner = ($row['lost_owner_id'] == $user_id);
                ?>
                    <div class="match-card">
                        <div class="match-header">
                            <div>
                                <span class="fw-bold text-dark">Match ID: #<?php echo $row['match_id']; ?></span>
                                <span class="ms-3 badge bg-primary"><?php echo $row['match_score']; ?>% Match Score</span>
                            </div>
                            <div>
                                <?php 
                                $status = $row['status'];
                                if ($status == 'pending') echo '<span class="status-badge status-badge-pending">⏳ Pending</span>';
                                elseif ($status == 'claimed') echo '<span class="status-badge status-badge-claimed">📋 Claimed</span>';
                                elseif ($status == 'verified') echo '<span class="status-badge status-badge-verified">✅ Verified</span>';
                                elseif ($status == 'returned') echo '<span class="status-badge status-badge-returned">🎉 Returned</span>';
                                ?>
                            </div>
                        </div>
                        <div class="match-body">
                            <div class="row align-items-center g-3">
                                <div class="col-md-2 text-center text-md-start">
                                    <?php if ($row['found_photo']): ?>
                                        <!-- Note: photo_url is stored relative to root (e.g. uploads/item.jpg), so we prepend ../../ to access it from syafiqah/matching/ -->
                                        <img src="../../<?php echo htmlspecialchars($row['found_photo']); ?>" alt="Found item photo" class="match-image">
                                    <?php else: ?>
                                        <div class="match-image d-flex align-items-center justify-content-center bg-light text-muted">
                                            <span style="font-size: 11px;">No Image</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-7">
                                    <div class="row">
                                        <div class="col-6 border-end">
                                            <h5 style="font-size: 13px; font-weight: 700; color: var(--primary-color); margin-bottom: 5px;">My Lost Report</h5>
                                            <p class="m-0" style="font-size: 13px; font-weight: 600;"><?php echo htmlspecialchars($row['lost_item_name']); ?></p>
                                            <p class="text-muted m-0" style="font-size: 11px; line-height: 1.4;"><?php echo htmlspecialchars($row['lost_desc']); ?></p>
                                        </div>
                                        <div class="col-6">
                                            <h5 style="font-size: 13px; font-weight: 700; color: var(--success-color); margin-bottom: 5px;">Matched Found Report</h5>
                                            <p class="m-0" style="font-size: 13px; font-weight: 600;"><?php echo htmlspecialchars($row['found_item_name']); ?></p>
                                            <p class="text-muted m-0" style="font-size: 11px; line-height: 1.4;"><?php echo htmlspecialchars($row['found_desc']); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center text-md-end">
                                    <?php if ($status == 'pending'): ?>
                                        <?php if ($is_owner): ?>
                                            <a href="../../tan/claim_form.php?match_id=<?php echo $row['match_id']; ?>" class="btn-custom btn-custom-success py-2 w-100" style="font-size: 12px;">
                                                🙋‍♂️ Claim Item
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 11px; display: block; text-align: center;">Waiting for owner claim</span>
                                        <?php endif; ?>
                                    <?php elseif ($status == 'claimed' || $status == 'verified'): ?>
                                        <a href="../../tan/claim_status.php" class="btn-custom btn-custom-outline w-100 py-2" style="font-size: 12px;">
                                            📋 View Claim Status
                                        </a>
                                    <?php else: ?>
                                        <span class="text-success fw-bold" style="font-size: 13px; display: block; text-align: center;">🎉 Handed Over!</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <span style="font-size: 40px;">🔍</span>
                    <p class="mt-3 m-0">No matches found for your reports yet.</p>
                    <p style="font-size: 12px;">Make sure you have active reports, or run a matching scan from the dashboard.</p>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="dashboard.php" class="btn-custom btn-custom-outline py-2 px-4">⬅ Back to Dashboard</a>
            </div>
        </div>
    </div>

    <!-- ===== FOOTER ===== -->
    <footer class="custom-footer mt-5">
        <p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming Project</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
