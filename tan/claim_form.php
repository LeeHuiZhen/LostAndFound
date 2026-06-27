<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../tey/login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$match_id  = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;

// No match selected fallback
if ($match_id == 0) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Claim Item – Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>body { background: var(--light-bg); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }</style>
</head>
<body>
    <div class="glass-card text-center" style="max-width: 480px; width: 100%;">
        <span style="font-size: 52px; display: block; margin-bottom: 16px;">📝</span>
        <h2 style="border-bottom: none; padding-bottom: 0;">Claim an Item</h2>
        <p class="text-muted my-3">No match was selected. Please go to your matches page to choose the item you want to claim.</p>
        <div class="d-flex flex-column gap-2 mt-4">
            <a href="../syafiqah/matching/display_match.php" class="btn-custom btn-custom-primary">View My Matches</a>
            <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-outline">Dashboard</a>
        </div>
    </div>
</body>
</html>
<?php exit(); }

// Fetch the match details
$sql = "SELECT m.*, li.item_name as lost_item, li.description as lost_desc, 
               fi.item_name as found_item, fi.description as found_desc, fi.photo_url,
               fi.location_found, fi.date_found
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
    <meta charset="UTF-8"><title>Match Unavailable – Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>body { background: var(--light-bg); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }</style>
</head>
<body>
    <div class="glass-card text-center" style="max-width: 480px; width: 100%;">
        <span style="font-size: 52px; display: block; margin-bottom: 16px;">❌</span>
        <h2 style="border-bottom: none; padding-bottom: 0; color: var(--danger-color);">Match Unavailable</h2>
        <p class="text-muted my-3">This match has already been claimed, returned, or does not exist anymore.</p>
        <div class="d-flex flex-column gap-2 mt-4">
            <a href="claim_status.php" class="btn-custom btn-custom-primary">Check My Claim Status</a>
            <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-outline">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
<?php exit(); }

$match = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Claim – UTM Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: var(--light-bg); }
        .match-preview {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 16px;
            align-items: center;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 20px;
            margin-bottom: 28px;
        }
        .match-item-panel {
            padding: 16px;
            background: white;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
        }
        .match-arrow {
            font-size: 24px;
            text-align: center;
            color: var(--primary-color);
        }
        .score-badge {
            display: inline-block;
            background: var(--primary-gradient);
            color: white;
            font-size: 12px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 50px;
            margin-top: 6px;
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
        <h1>📋 Submit Ownership Claim</h1>
        <p>Provide proof that this found item belongs to you. Security will review and verify.</p>
    </div>

    <div class="app-container" style="max-width: 760px;">
        <div class="glass-card">

            <!-- MATCH PREVIEW -->
            <h3>Your Matched Item Pair</h3>
            <div class="match-preview">
                <div class="match-item-panel">
                    <p class="m-0" style="font-size: 11px; font-weight: 700; color: var(--primary-color); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">🔴 Your Lost Report</p>
                    <p class="m-0 fw-bold" style="font-size: 15px; color: var(--text-main);"><?php echo htmlspecialchars($match['lost_item']); ?></p>
                    <p class="m-0 text-muted" style="font-size: 12px; margin-top: 5px; line-height: 1.5;"><?php echo htmlspecialchars($match['lost_desc']); ?></p>
                </div>

                <div class="match-arrow">⇆</div>

                <div class="match-item-panel">
                    <p class="m-0" style="font-size: 11px; font-weight: 700; color: var(--success-color); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">🟢 Matched Found Item</p>
                    <p class="m-0 fw-bold" style="font-size: 15px; color: var(--text-main);"><?php echo htmlspecialchars($match['found_item']); ?></p>
                    <p class="m-0 text-muted" style="font-size: 12px; margin-top: 5px; line-height: 1.5;"><?php echo htmlspecialchars($match['found_desc']); ?></p>
                    <?php if ($match['photo_url']): ?>
                        <img src="../<?php echo htmlspecialchars($match['photo_url']); ?>" alt="Item Photo" style="width: 100%; max-height: 120px; object-fit: cover; border-radius: 6px; margin-top: 10px; border: 1px solid var(--border-color);">
                    <?php endif; ?>
                    <div class="score-badge"><?php echo $match['match_score']; ?>% Match Score</div>
                </div>
            </div>

            <!-- PROOF FORM -->
            <h3>Proof of Ownership</h3>

            <div class="alert-custom alert-custom-info mb-4" style="font-size: 13px;">
                💡 <strong>Tips for a successful claim:</strong> Include serial numbers, purchase receipts, detailed descriptions of unique marks, scratches, stickers, or photos of you with the item.
            </div>

            <form action="proof_upload.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">

                <div class="form-group">
                    <label for="proof_description">Describe your item in detail *</label>
                    <textarea name="proof_description" id="proof_description" class="form-control" rows="5"
                        placeholder="Include: unique features (color, brand, model number), any stickers or markings, contents inside a bag, purchase date and location, serial number..." required></textarea>
                </div>

                <div class="form-group mb-4">
                    <label for="proof_file">Upload Proof Document *</label>
                    <input type="file" name="proof_file" id="proof_file" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx" required>
                    <small class="text-muted" style="font-size: 11px; display: block; margin-top: 6px;">📎 Accepted: receipt, ID photo with item, packaging, serial number tag. Max 5MB.</small>
                </div>

                <div class="d-flex gap-3">
                    <a href="../syafiqah/matching/display_match.php" class="btn-custom btn-custom-secondary flex-fill text-center">← Back</a>
                    <button type="submit" class="btn-custom btn-custom-success flex-fill">Submit Claim ✓</button>
                </div>
            </form>

        </div>

        <div class="text-center mt-4">
            <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-outline py-2 px-5">← Dashboard</a>
        </div>
    </div>

    <footer class="custom-footer mt-5">
        <p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming Project</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/assistant.js"></script>
</body>
</html>
