<?php
include '../config.php';
include '../lee/upload.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../tey/login.php"); exit();
}

$user_id   = $_SESSION['user_id'];
$success   = false;
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $match_id         = intval($_POST['match_id']);
    $proof_description = trim($_POST['proof_description']);

    // Security Fix #2 (IDOR): verify this match belongs to the logged-in user before saving
    $chk = $conn->prepare("
        SELECT m.match_id FROM matches m
        JOIN lost_items li ON m.lost_item_id = li.item_id
        WHERE m.match_id = ? AND li.user_id = ?
    ");
    $chk->bind_param("ii", $match_id, $user_id);
    $chk->execute(); $chk->store_result();

    if ($chk->num_rows == 0) {
        $error_msg = "Access denied — you are not the owner of this match.";
        $chk->close();
    } else {
        $chk->close();

        // Upload proof file through the secure handler
        $proof_url = handleUpload($_FILES['proof_file']);

        if ($proof_url === false) {
            $error_msg = "Proof file upload failed. Only images up to 5 MB are accepted.";
        } else {
            $sql = "INSERT INTO claims (match_id, owner_id, proof_description, proof_url, status)
                    VALUES (?, ?, ?, ?, 'pending')";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("iiss", $match_id, $user_id, $proof_description, $proof_url);
                if ($stmt->execute()) {
                    // Update match status to 'claimed'
                    $conn->query("UPDATE matches SET status = 'claimed' WHERE match_id = $match_id");
                    $success = true;
                } else {
                    $error_msg = "Database error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Submission — UTM Lost &amp; Found</title>
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
    </style>
</head>
<body>
    <div class="glass-card text-center animate-fade-up" style="max-width:500px; width:100%; position:relative; z-index:1;">
        <?php if ($success): ?>
            <span style="font-size:64px; display:block; margin-bottom:20px; animation:float 3s ease-in-out infinite;">📤</span>
            <h2 style="font-size:26px; font-weight:800; color:#34d399; margin-bottom:10px;">Claim Submitted!</h2>
            <p style="color:var(--text-muted); font-size:14px; margin-bottom:28px;">Your proof of ownership has been sent to campus security for review. You'll be notified once it's verified.</p>

            <div style="background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.2); border-radius:var(--r-md); padding:16px; text-align:left; margin-bottom:28px; font-size:13px; color:#6ee7b7;">
                ✅ Claim saved successfully<br>
                ⏳ Security review: usually within 24 hours<br>
                📧 You'll receive an alert upon decision
            </div>

            <a href="claim_status.php" class="btn-custom btn-custom-success w-100 mb-3" style="padding:13px;">📋 Track Claim Status</a>
            <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-secondary w-100">🏠 Back to Dashboard</a>

        <?php else: ?>
            <span style="font-size:64px; display:block; margin-bottom:20px;">❌</span>
            <h2 style="font-size:26px; font-weight:800; color:#fb7185; margin-bottom:10px;">Submission Failed</h2>
            <p style="color:var(--text-muted); font-size:14px; margin-bottom:28px;">
                <?php echo htmlspecialchars($error_msg ?: 'An unexpected error occurred.'); ?>
            </p>
            <a href="javascript:history.back()" class="btn-custom btn-custom-primary w-100 mb-3" style="padding:13px;">← Go Back &amp; Retry</a>
            <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-secondary w-100">🏠 Dashboard</a>
        <?php endif; ?>
    </div>
</body>
</html>
