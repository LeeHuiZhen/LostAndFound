<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../tey/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$match_id = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;

if ($match_id == 0) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Claim Item - Lost & Found</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../assets/css/style.css">
        <style>
            body { background-color: var(--light-bg); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
            .info-card { max-width: 500px; width: 100%; text-align: center; }
        </style>
    </head>
    <body>
        <div class="info-card glass-card">
            <span style="font-size: 50px;">📝</span>
            <h1 class="mt-3">Claim an Item</h1>
            <p class="text-muted my-3">No match selected. Please go to your dashboard to view active matches.</p>
            <div class="d-flex flex-column gap-2 mt-4">
                <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-primary">View My Dashboard</a>
                <a href="../index.php" class="btn-custom btn-custom-outline">Back to Home</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

$sql = "SELECT m.*, li.item_name as lost_item, li.description as lost_desc, 
               fi.item_name as found_item, fi.description as found_desc, fi.photo_url
        FROM matches m 
        JOIN lost_items li ON m.lost_item_id = li.item_id
        JOIN found_items fi ON m.found_item_id = fi.item_id
        WHERE m.match_id = $match_id AND m.status = 'pending'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Claim Failed - Lost & Found</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../assets/css/style.css">
        <style>
            body { background-color: var(--light-bg); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
            .info-card { max-width: 500px; width: 100%; text-align: center; }
        </style>
    </head>
    <body>
        <div class="info-card glass-card">
            <span style="font-size: 50px;">❌</span>
            <h1 class="mt-3 text-danger">Match Unavailable</h1>
            <p class="text-muted my-3">This match has already been claimed, returned, or does not exist.</p>
            <div class="d-flex flex-column gap-2 mt-4">
                <a href="claim_status.php" class="btn-custom btn-custom-primary">Check My Claim Status</a>
                <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-outline">Back to Dashboard</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

$match = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Verification - Lost & Found Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background-color: var(--light-bg); }
        .item-box { background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 15px; }
    </style>
</head>
<body>
    <nav class="custom-navbar">
        <a href="../index.php" class="brand">🔍 UTM Lost & Found</a>
        <div class="nav-links">
            <a href="../syafiqah/matching/dashboard.php" style="color: var(--text-muted); text-decoration: none; font-size: 14px; font-weight: 500; margin-right: 15px;">📋 Dashboard</a>
            <a href="../tey/logout.php" class="btn-custom btn-custom-outline" style="padding: 6px 16px; font-size: 12px;">🚪 Logout</a>
        </div>
    </nav>

    <div class="app-container" style="max-width: 750px;">
        <div class="glass-card">
            <div class="text-center mb-4">
                <span style="font-size: 44px;">📋</span>
                <h2>Claim Verification</h2>
                <p class="text-muted" style="font-size: 13px;">Please provide proof of ownership to claim your matched item.</p>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="item-box h-100">
                        <h4 style="font-size: 13px; font-weight: 700; color: var(--primary-color); margin-bottom: 8px;">Your Reported Lost Item</h4>
                        <p class="m-0" style="font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($match['lost_item']); ?></p>
                        <p class="text-muted m-0" style="font-size: 12px; line-height: 1.4;"><?php echo htmlspecialchars($match['lost_desc']); ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="item-box h-100">
                        <div class="d-flex gap-3 align-items-center">
                            <div class="flex-grow-1">
                                <h4 style="font-size: 13px; font-weight: 700; color: var(--success-color); margin-bottom: 8px;">Matched Found Item</h4>
                                <p class="m-0" style="font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($match['found_item']); ?></p>
                                <p class="text-muted m-0" style="font-size: 12px; line-height: 1.4;"><?php echo htmlspecialchars($match['found_desc']); ?></p>
                            </div>
                            <?php if ($match['photo_url']): ?>
                                <div style="flex-shrink: 0;">
                                    <img src="../<?php echo htmlspecialchars($match['photo_url']); ?>" alt="Found Item" style="width: 70px; height: 70px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <form action="proof_upload.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">
                
                <div class="form-group">
                    <label for="proof_description">Describe your lost item in detail *</label>
                    <textarea name="proof_description" id="proof_description" class="form-control" rows="4" placeholder="Include unique features, serial numbers, labels, specific scratch marks, receipt details, purchase date, or bag contents to verify ownership..." required></textarea>
                </div>
                
                <div class="form-group mb-4">
                    <label for="proof_file">Upload Proof of Ownership *</label>
                    <input type="file" name="proof_file" id="proof_file" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx" required>
                    <small class="text-muted" style="font-size: 11px; display: block; margin-top: 4px;">Upload a receipt, photo of you with the item, matching serial number tags, packaging box, or ID (Max: 5MB).</small>
                </div>

                <div class="d-flex gap-3">
                    <a href="../syafiqah/matching/display_match.php" class="btn-custom btn-custom-secondary flex-1">Back</a>
                    <button type="submit" class="btn-custom btn-custom-success flex-1">Submit Claim</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="custom-footer mt-5">
        <p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming Project</p>
    </footer>
</body>
</html>