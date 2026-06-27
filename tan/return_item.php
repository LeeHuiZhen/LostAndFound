<?php
session_start();
require_once '../config.php';

// Security Fix #3: Enforce strict admin access control gate
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("HTTP/1.1 403 Forbidden");
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Access Denied</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>body{background:var(--bg-base);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}</style>
    </head><body>
    <div class="glass-card text-center animate-fade-up" style="max-width:460px;width:100%;">
    <span style="font-size:52px;display:block;margin-bottom:16px;">🚫</span>
    <h2 style="font-size:22px;font-weight:800;color:#fb7185;margin-bottom:10px;">Access Denied</h2>
    <p style="color:var(--text-muted);font-size:14px;margin-bottom:24px;">Only authorized campus security administrators are permitted to access this page.</p>
    <a href="../index.php" class="btn-custom btn-custom-primary">🏠 Back to Home</a>
    </div></body></html>';
    exit;
}

$claim_id  = isset($_GET['claim_id']) ? intval($_GET['claim_id']) : 0;
$success   = false;
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $claim_id = intval($_POST['claim_id']);

    // Fetch details to update the items and match statuses
    $stmt = $conn->prepare("
        SELECT c.match_id, m.lost_item_id, m.found_item_id
        FROM claims c
        JOIN matches m ON c.match_id = m.match_id
        WHERE c.claim_id = ?
    ");
    $stmt->bind_param("i", $claim_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $match_id = $row['match_id'];
        $lost_id  = $row['lost_item_id'];
        $found_id = $row['found_item_id'];

        $conn->begin_transaction();
        try {
            // Update claim status to returned
            $u1 = $conn->prepare("UPDATE claims SET status = 'returned' WHERE claim_id = ?");
            $u1->bind_param("i", $claim_id);
            $u1->execute();

            // Update match status to returned
            $u2 = $conn->prepare("UPDATE matches SET status = 'returned' WHERE match_id = ?");
            $u2->bind_param("i", $match_id);
            $u2->execute();

            // Update lost item status to returned
            $u3 = $conn->prepare("UPDATE lost_items SET status = 'returned' WHERE item_id = ?");
            $u3->bind_param("i", $lost_id);
            $u3->execute();

            // Update found item status to returned
            $u4 = $conn->prepare("UPDATE found_items SET status = 'returned' WHERE item_id = ?");
            $u4->bind_param("i", $found_id);
            $u4->execute();

            $conn->commit();
            $success = true;
        } catch (Exception $e) {
            $conn->rollback();
            $error_msg = "Transaction failed: " . $e->getMessage();
        }
    } else {
        $error_msg = "Claim not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handover Confirmation — Admin Portal</title>
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
        }
    </style>
</head>
<body>
    <div class="glass-card text-center animate-fade-up" style="max-width:500px; width:100%;">
        <?php if ($success): ?>
            <span style="font-size:64px; display:block; margin-bottom:20px; animation:float 3s ease-in-out infinite;">🎉</span>
            <h2 style="font-size:26px; font-weight:800; color:#34d399; margin-bottom:10px;">Handover Logged!</h2>
            <p style="color:var(--text-muted); font-size:14px; margin-bottom:28px;">The item has been successfully logged as returned. All related item and match records have been closed.</p>
            <a href="verify_claim.php" class="btn-custom btn-custom-success w-100">📋 Return to Admin Portal</a>
        <?php else: ?>
            <span style="font-size:52px; display:block; margin-bottom:16px;">🤝</span>
            <h2 style="font-size:24px; font-weight:800; color:var(--text-primary); margin-bottom:8px;">Confirm Handover</h2>
            <p style="color:var(--text-muted); font-size:14px; margin-bottom:24px;">Confirming this action will log the physical item as returned to the owner and mark the case as resolved.</p>

            <?php if (!empty($error_msg)): ?>
                <div class="alert-custom alert-custom-danger text-start"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="claim_id" value="<?php echo $claim_id; ?>">
                <div style="display:flex; gap:12px;">
                    <a href="verify_claim.php" class="btn-custom btn-custom-secondary flex-fill">Cancel</a>
                    <button type="submit" class="btn-custom btn-custom-primary flex-fill">✅ Confirm &amp; Log Handover</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
