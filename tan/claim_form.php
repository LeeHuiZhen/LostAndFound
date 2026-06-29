<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../tey/login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$match_id  = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;

// --- Helper function to render error messages with full layout ---
function render_error_page($title, $icon, $message, $btn1_text, $btn1_url, $btn2_text, $btn2_url, $user_name) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title; ?> – UTM Lost & Found</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../assets/css/style.css?v=3">
        <style>
            body {
                background: linear-gradient(rgba(15,23,42,0.65), rgba(15,23,42,0.8)),
                            url('../LostAndFound_found.png') no-repeat center center fixed;
                background-size: cover;
                min-height: 100vh;
                color: #ffffff;
            }
            .glass-card {
                background: rgba(255,255,255,0.96) !important;
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                color: #1e293b;
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
                <a href="claim_status.php">📋 My Claims</a>
                <span>Hi, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
                <a href="../tey/logout.php" class="btn-custom btn-custom-outline" style="padding: 7px 16px; font-size: 12px;">Logout</a>
            </div>
        </nav>

        <div class="d-flex align-items-center justify-content-center" style="min-height: calc(100vh - 180px); padding: 40px 20px;">
            <div class="glass-card text-center" style="max-width: 500px; width: 100%; padding: 32px;">
                <span style="font-size: 56px; display: block; margin-bottom: 16px;"><?php echo $icon; ?></span>
                <h2 style="border-bottom: none; padding-bottom: 0; font-size: 22px; font-weight: 800;"><?php echo $title; ?></h2>
                <p class="text-muted my-3" style="font-size: 14px; line-height: 1.6;"><?php echo $message; ?></p>
                <div class="d-flex flex-column gap-2 mt-4">
                    <a href="<?php echo $btn1_url; ?>" class="btn-custom btn-custom-primary py-2"><?php echo $btn1_text; ?></a>
                    <a href="<?php echo $btn2_url; ?>" class="btn-custom btn-custom-outline py-2"><?php echo $btn2_text; ?></a>
                </div>
            </div>
        </div>

        <footer class="custom-footer">
            <p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming Project</p>
        </footer>
    </body>
    </html>
    <?php
    exit();
}

// 1. No match selected fallback
if ($match_id == 0) {
    render_error_page(
        "Claim an Item",
        "📝",
        "No match was selected. Please go to your matches page to choose the item you want to claim.",
        "View My Matches", "../syafiqah/matching/display_match.php",
        "Back to Dashboard", "../syafiqah/matching/dashboard.php",
        $user_name
    );
}

// Fetch the match details
$sql = "SELECT m.*, li.item_name as lost_item, li.description as lost_desc, 
               fi.item_name as found_item, fi.description as found_desc, fi.photo_url,
               fi.location_found, fi.date_found
        FROM matches m 
        JOIN lost_items li ON m.lost_item_id = li.item_id
        JOIN found_items fi ON m.found_item_id = fi.item_id
        WHERE m.match_id = $match_id AND m.status = 'pending'";
$result = $conn->query($sql);

// 2. Match unavailable fallback
if ($result->num_rows == 0) {
    render_error_page(
        "Match Unavailable",
        "❌",
        "This match has already been claimed, returned, or does not exist anymore.",
        "Check My Claim Status", "claim_status.php",
        "Back to Dashboard", "../syafiqah/matching/dashboard.php",
        $user_name
    );
}

$match = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Claim – UTM Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=3">
    <style>
        body {
            background: linear-gradient(rgba(15,23,42,0.65), rgba(15,23,42,0.8)),
                        url('../LostAndFound_found.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            color: #ffffff; /* Contrast text on dark bg */
        }
        
        .glass-card {
            background: rgba(255,255,255,0.96) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            color: #1e293b; /* Dark text inside card for readability */
        }

        .match-preview {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 16px;
            align-items: stretch;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-md);
            padding: 20px;
            margin-bottom: 28px;
            color: #1e293b;
        }
        @media (max-width: 768px) {
            .match-preview {
                grid-template-columns: 1fr;
            }
            .match-arrow {
                transform: rotate(90deg);
                margin: 10px 0;
            }
        }
        .match-item-panel {
            padding: 16px;
            background: white;
            border-radius: var(--radius-sm);
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .match-arrow {
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4f46e5;
            font-weight: 700;
        }
        .score-badge {
            display: inline-block;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            font-size: 12px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 50px;
            margin-top: 10px;
            align-self: flex-start;
        }

        /* Transparent hero over background */
        .page-hero {
            padding: 48px 20px;
            text-align: center;
        }
        .page-hero h1 {
            font-size: 36px;
            font-weight: 900;
            color: #ffffff;
            margin: 0 0 10px;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }
        .page-hero p {
            font-size: 16px;
            color: rgba(255,255,255,0.9);
            margin: 0;
            text-shadow: 0 1px 2px rgba(0,0,0,0.5);
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
            <a href="claim_status.php">📋 My Claims</a>
            <span>Hi, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
            <a href="../tey/logout.php" class="btn-custom btn-custom-outline" style="padding: 7px 16px; font-size: 12px;">Logout</a>
        </div>
    </nav>

    <!-- ===== HERO ===== -->
    <div class="page-hero">
        <h1>📋 Submit Ownership Claim</h1>
        <p>Provide proof that this found item belongs to you. Campus Security will review and verify.</p>
    </div>

    <div class="app-container" style="max-width: 760px;">
        <div class="glass-card" style="padding: 32px;">

            <!-- MATCH PREVIEW -->
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 16px; color: #1e293b;">Your Matched Item Pair</h3>
            <div class="match-preview">
                <div class="match-item-panel" style="background: #faf5ff; border-color: #e9d5ff;">
                    <div>
                        <p class="m-0" style="font-size: 11px; font-weight: 700; color: #7c3aed; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">🔴 Your Lost Report</p>
                        <p class="m-0 fw-bold" style="font-size: 15px; color: #1e293b;"><?php echo htmlspecialchars($match['lost_item']); ?></p>
                        <p class="m-0 text-muted mt-2" style="font-size: 12px; line-height: 1.5;"><?php echo htmlspecialchars($match['lost_desc']); ?></p>
                    </div>
                </div>

                <div class="match-arrow">⇆</div>

                <div class="match-item-panel" style="background: #f0fdf4; border-color: #bbf7d0;">
                    <div>
                        <p class="m-0" style="font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">🟢 Matched Found Item</p>
                        <p class="m-0 fw-bold" style="font-size: 15px; color: #1e293b;"><?php echo htmlspecialchars($match['found_item']); ?></p>
                        <p class="m-0 text-muted mt-2" style="font-size: 12px; line-height: 1.5;"><?php echo htmlspecialchars($match['found_desc']); ?></p>
                        <?php if ($match['photo_url']): ?>
                            <img src="../<?php echo htmlspecialchars($match['photo_url']); ?>" alt="Item Photo" style="width: 100%; max-height: 120px; object-fit: cover; border-radius: 6px; margin-top: 10px; border: 1px solid #e2e8f0;">
                        <?php endif; ?>
                    </div>
                    <div class="score-badge"><?php echo $match['match_score']; ?>% Match Score</div>
                </div>
            </div>

            <!-- PROOF FORM -->
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 16px; color: #1e293b;">Proof of Ownership</h3>

            <div class="alert-custom alert-custom-info mb-4" style="font-size: 13px;">
                💡 <strong>Tips for a successful claim:</strong> Include serial numbers, purchase receipts, detailed descriptions of unique marks, scratches, stickers, or photos of you with the item.
            </div>

            <form action="proof_upload.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">

                <div class="form-group mb-3">
                    <label for="proof_description" class="fw-bold">Describe your item in detail *</label>
                    <textarea name="proof_description" id="proof_description" class="form-control" rows="5"
                        placeholder="Include: unique features (color, brand, model number), any stickers or markings, contents inside a bag, purchase date and location, serial number..." required></textarea>
                </div>

                <div class="form-group mb-4">
                    <label for="proof_file" class="fw-bold">Upload Proof Document *</label>
                    <input type="file" name="proof_file" id="proof_file" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx" required>
                    <small class="text-muted" style="font-size: 11px; display: block; margin-top: 6px;">📎 Accepted: receipt, ID photo with item, packaging, serial number tag. Max 5MB.</small>
                </div>

                <div class="d-flex gap-3">
                    <a href="../syafiqah/matching/display_match.php" class="btn-custom btn-custom-secondary flex-fill text-center py-2" style="text-decoration: none;">← Back</a>
                    <button type="submit" class="btn-custom btn-custom-success flex-fill py-2">Submit Claim ✓</button>
                </div>
            </form>

        </div>

        <div class="text-center mt-4 mb-5">
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
