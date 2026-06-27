<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../tey/login.php"); exit();
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$match_id  = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;

// Helper function to render an error card and exit
function show_error_card($title, $message, $css_path = '../assets/css/style.css') {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error — UTM Lost &amp; Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="' . $css_path . '">
    <style>body{background:var(--bg-base);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}</style>
    </head><body>
    <div class="glass-card text-center animate-fade-up" style="max-width:460px;width:100%;">
    <span style="font-size:52px;display:block;margin-bottom:16px;">❌</span>
    <h2 style="font-size:22px;font-weight:800;color:#fb7185;margin-bottom:10px;">' . htmlspecialchars($title) . '</h2>
    <p style="color:var(--text-muted);font-size:14px;margin-bottom:24px;">' . htmlspecialchars($message) . '</p>
    <div style="display:flex;flex-direction:column;gap:10px;">
    <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-primary">📋 Back to Dashboard</a>
    <a href="../index.php" class="btn-custom btn-custom-secondary">🏠 Home</a>
    </div></div></body></html>';
    exit();
}

if ($match_id == 0) {
    show_error_card('No Match Selected', 'Please go to your dashboard and select an active match to claim.');
}

// Security Fix #2 (IDOR): require that the lost item in this match belongs to the logged-in user
$sql = "SELECT m.*, li.item_name AS lost_item, li.description AS lost_desc,
               fi.item_name AS found_item, fi.description AS found_desc, fi.photo_url
        FROM matches m
        JOIN lost_items li ON m.lost_item_id = li.item_id
        JOIN found_items fi ON m.found_item_id = fi.item_id
        WHERE m.match_id = ? AND m.status = 'pending' AND li.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $match_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    show_error_card('Match Unavailable', 'This match has already been claimed, returned, or you do not have permission to claim it.');
}
$match = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Verification — UTM Lost &amp; Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style> body { background-color: var(--bg-base); } </style>
</head>
<body>
    <nav class="custom-navbar">
        <a href="../index.php" class="brand">🔍 UTM Lost &amp; Found</a>
        <div class="nav-links">
            <a href="../syafiqah/matching/dashboard.php" style="font-size:13px; color:var(--text-muted);">📋 Dashboard</a>
            <a href="../tey/logout.php" class="btn-custom btn-custom-secondary" style="padding:7px 16px; font-size:12px;">Logout</a>
        </div>
    </nav>

    <div class="header-hero" style="padding:50px 20px 40px;">
        <h1 style="font-size:32px; margin-bottom:8px;">📋 Submit Ownership Claim</h1>
        <p>Provide compelling proof to let campus security verify you're the rightful owner.</p>
    </div>

    <div class="app-container" style="max-width:780px;">
        <div class="glass-card animate-fade-up">

            <!-- MATCH PREVIEW -->
            <h3 class="section-header">🎯 Match Preview — ID #<?php echo $match_id; ?></h3>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="item-box h-100">
                        <p style="font-size:10px; font-weight:700; color:var(--primary-light); text-transform:uppercase; letter-spacing:0.5px; margin:0 0 8px;">Your Reported Lost Item</p>
                        <h4 style="font-size:16px; font-weight:700; color:var(--text-primary); margin:0 0 6px;"><?php echo htmlspecialchars($match['lost_item']); ?></h4>
                        <p style="font-size:13px; color:var(--text-muted); margin:0; line-height:1.5;"><?php echo htmlspecialchars($match['lost_desc']); ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="item-box h-100">
                        <p style="font-size:10px; font-weight:700; color:#34d399; text-transform:uppercase; letter-spacing:0.5px; margin:0 0 8px;">Matched Found Item</p>
                        <div style="display:flex; gap:14px; align-items:flex-start;">
                            <div style="flex:1;">
                                <h4 style="font-size:16px; font-weight:700; color:var(--text-primary); margin:0 0 6px;"><?php echo htmlspecialchars($match['found_item']); ?></h4>
                                <p style="font-size:13px; color:var(--text-muted); margin:0; line-height:1.5;"><?php echo htmlspecialchars($match['found_desc']); ?></p>
                            </div>
                            <?php if ($match['photo_url']): ?>
                                <img src="../<?php echo htmlspecialchars($match['photo_url']); ?>" alt="Found"
                                     style="width:72px;height:72px;object-fit:cover;border-radius:var(--r-md);border:1px solid var(--border);flex-shrink:0;">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CLAIM FORM -->
            <h3 class="section-header">📝 Proof of Ownership</h3>
            <form action="proof_upload.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">

                <div class="form-group">
                    <label for="proof_description">Describe your item in verifiable detail *</label>
                    <textarea name="proof_description" id="proof_description" class="form-control" rows="5"
                              placeholder="Include unique identifiers: serial numbers, scratch marks, labels, purchase date, what was inside the bag, specific colour details, receipt information..." required></textarea>
                </div>

                <div class="form-group" style="margin-bottom:32px;">
                    <label for="proof_file">Upload Proof Document *</label>
                    <input type="file" name="proof_file" id="proof_file" class="form-control"
                           accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx" required>
                    <small style="font-size:12px; color:var(--text-muted); margin-top:6px; display:block;">
                        💡 Accepted: receipt, photo of you with the item, matching serial tag, packaging box, or student ID (Max 5 MB).
                    </small>
                </div>

                <div style="display:flex; gap:12px;">
                    <a href="../syafiqah/matching/display_match.php" class="btn-custom btn-custom-secondary flex-fill">← Go Back</a>
                    <button type="submit" class="btn-custom btn-custom-success flex-fill">📤 Submit Claim</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="custom-footer mt-5"><p>🔍 Lost and Found Assistant &copy; 2026 | UTM Web Programming</p></footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
