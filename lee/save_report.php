<?php
include '../config.php';
include 'upload.php';
include 'vision_api.php';
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../tey/login.php"); exit;
}

$success   = false;
$error_msg = '';
$tags      = '';
$item_name = '';
$type      = '';
$location  = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id     = $_SESSION['user_id'];
    $item_name   = trim($_POST['item_name']);
    $description = trim($_POST['description']);
    $location    = trim($_POST['location']);
    $date        = $_POST['date'];
    $type        = $_POST['type'];

    $photo_url = handleUpload($_FILES['item_photo']);

    if ($photo_url === false) {
        $error_msg = "Photo upload failed. Only JPG/PNG/GIF images under 5 MB are accepted.";
    } else {
        $tags      = getVisionTags($item_name . " " . $description, "../" . $photo_url);
        $table     = ($type == 'lost') ? 'lost_items'    : 'found_items';
        $col_loc   = ($type == 'lost') ? 'location_lost' : 'location_found';
        $col_date  = ($type == 'lost') ? 'date_lost'     : 'date_found';

        $sql = "INSERT INTO $table (user_id, item_name, description, $col_loc, $col_date, photo_url, tags, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("issssss", $user_id, $item_name, $description, $location, $date, $photo_url, $tags);

            if ($stmt->execute()) {
                $success      = true;
                $new_item_id  = $conn->insert_id;

                // ===== REAL-TIME AUTO-MATCHING =====
                $opp_table    = ($type == 'lost') ? 'found_items'    : 'lost_items';
                $opp_col_loc  = ($type == 'lost') ? 'location_found' : 'location_lost';

                $opp_result = $conn->query("SELECT * FROM $opp_table WHERE status = 'pending'");
                if ($opp_result && $opp_result->num_rows > 0) {
                    while ($opp = $opp_result->fetch_assoc()) {
                        $score = 0;

                        // Name (max 40)
                        $n1 = strtolower(trim($item_name));
                        $n2 = strtolower(trim($opp['item_name']));
                        if ($n1 === $n2) { $score += 40; } else {
                            $w1 = explode(' ', $n1); $w2 = explode(' ', $n2);
                            $cw = array_diff(array_intersect($w1, $w2), ['the','a','of','in','at','on','with','utm','item','card']);
                            if (!empty($cw)) $score += 25;
                        }
                        // Tags (max 30)
                        $t1 = array_map('trim', explode(',', strtolower($tags)));
                        $t2 = array_map('trim', explode(',', strtolower($opp['tags'])));
                        $ct = array_intersect($t1, $t2);
                        if (!empty($ct)) $score += min(count($ct) * 15, 30);

                        // Location (max 20)
                        $l1 = strtolower($location); $l2 = strtolower($opp[$opp_col_loc]);
                        foreach (['library','cafeteria','n28','n24','block','lab','elevator','classroom','hall'] as $kw) {
                            if (strpos($l1, $kw) !== false && strpos($l2, $kw) !== false) { $score += 20; break; }
                        }
                        // Description (max 10)
                        similar_text(strtolower($description), strtolower($opp['description']), $pct);
                        $score += ($pct > 50) ? 10 : (($pct > 25) ? 5 : 0);

                        if ($score >= 40) {
                            $lost_id  = ($type == 'lost') ? $new_item_id : $opp['item_id'];
                            $found_id = ($type == 'lost') ? $opp['item_id'] : $new_item_id;

                            $chk = $conn->prepare("SELECT match_id FROM matches WHERE lost_item_id = ? AND found_item_id = ?");
                            $chk->bind_param("ii", $lost_id, $found_id);
                            $chk->execute(); $chk->store_result();

                            if ($chk->num_rows == 0) {
                                $chk->close();
                                $ins = $conn->prepare("INSERT INTO matches (lost_item_id, found_item_id, match_score, status, notification_sent) VALUES (?, ?, ?, 'pending', 0)");
                                $ins->bind_param("iii", $lost_id, $found_id, $score);
                                $ins->execute(); $ins->close();

                                // Log simulated email alert
                                $lu = $conn->prepare("SELECT u.name, u.email FROM lost_items li JOIN users u ON li.user_id = u.id WHERE li.item_id = ?");
                                $lu->bind_param("i", $lost_id); $lu->execute();
                                $lu_res = $lu->get_result()->fetch_assoc(); $lu->close();

                                if ($lu_res) {
                                    $log  = str_repeat("=", 72) . "\n";
                                    $log .= "AUTOMATED EMAIL ALERT — UTM Lost & Found\n";
                                    $log .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
                                    $log .= "To: {$lu_res['email']} ({$lu_res['name']})\n";
                                    $log .= "Subject: 🎉 Match Found — Score: {$score}%\n";
                                    $log .= str_repeat("=", 72) . "\n\n";
                                    file_put_contents("../email_alerts.log", $log, FILE_APPEND);
                                }
                            } else { $chk->close(); }
                        }
                    }
                }
            } else {
                $error_msg = "Database Error: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error_msg = "Statement preparation failed: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Status — UTM Lost &amp; Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: var(--bg-base);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 40% 40%, rgba(<?php echo $success ? '16,185,129' : '244,63,94'; ?>,0.12) 0%, transparent 60%);
            pointer-events: none;
        }
        .result-card { max-width: 520px; width: 100%; position: relative; z-index:1; }
        .result-icon {
            font-size: 60px;
            animation: float 3s ease-in-out infinite;
            display: block;
            text-align: center;
            margin-bottom: 20px;
        }
        .tag-pill {
            display: inline-block;
            background: rgba(99,102,241,0.12);
            border: 1px solid rgba(99,102,241,0.25);
            color: var(--primary-light);
            border-radius: var(--r-full);
            padding: 3px 12px;
            font-size: 12px;
            margin: 3px;
        }
    </style>
</head>
<body>
    <div class="result-card glass-card text-center animate-fade-up">
        <?php if ($success): ?>
            <span class="result-icon">✅</span>
            <h2 style="font-size:26px; font-weight:800; color:#34d399; margin-bottom:6px;">Report Submitted!</h2>
            <p style="color:var(--text-muted); margin-bottom:24px;">Your report has been recorded and the matching engine has run.</p>

            <div style="background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.2); border-radius:var(--r-md); padding:16px; text-align:left; margin-bottom:24px; font-size:14px;">
                <p style="margin:0 0 8px;"><strong style="color:var(--text-muted); font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">Item</strong><br>
                <span style="color:var(--text-primary); font-weight:600;"><?php echo htmlspecialchars($item_name); ?></span></p>
                <p style="margin:0 0 8px;"><strong style="color:var(--text-muted); font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">Type</strong><br>
                <span style="color:var(--text-primary);"><?php echo ($type=='lost') ? '🔴 Lost Item' : '🟢 Found Item'; ?></span></p>
                <p style="margin:0 0 8px;"><strong style="color:var(--text-muted); font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">Location</strong><br>
                <span style="color:var(--text-primary);"><?php echo htmlspecialchars($location); ?></span></p>
                <p style="margin:0;"><strong style="color:var(--text-muted); font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">Auto-Tags</strong><br>
                <?php foreach (explode(',', $tags) as $tag): ?>
                    <span class="tag-pill"><?php echo htmlspecialchars(trim($tag)); ?></span>
                <?php endforeach; ?></p>
            </div>

            <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-success w-100" style="padding:13px;">
                📋 Go to My Workspace Dashboard
            </a>

        <?php else: ?>
            <span class="result-icon">❌</span>
            <h2 style="font-size:26px; font-weight:800; color:#fb7185; margin-bottom:6px;">Submission Failed</h2>
            <p style="color:var(--text-muted); margin-bottom:24px;">An error occurred while saving your report.</p>

            <div style="background:rgba(244,63,94,0.08); border:1px solid rgba(244,63,94,0.2); border-radius:var(--r-md); padding:16px; text-align:left; margin-bottom:24px; font-size:13px; color:#fda4af;">
                <?php echo htmlspecialchars($error_msg ?: 'No data was submitted. Please go back and fill the form.'); ?>
            </div>

            <div style="display:flex; gap:10px;">
                <a href="javascript:history.back()" class="btn-custom btn-custom-primary flex-fill">← Go Back &amp; Retry</a>
                <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-secondary flex-fill">🏠 Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
