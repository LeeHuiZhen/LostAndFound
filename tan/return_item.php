<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../tey/login.php");
    exit();
}

$user_name = $_SESSION['user_name'];
$claim_id = isset($_GET['claim_id']) ? intval($_GET['claim_id']) : 0;
$success = false;

// If no claim_id, show a friendly message instead of error
if ($claim_id == 0 && $_SERVER['REQUEST_METHOD'] != 'POST') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Return Item - Lost & Found</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../assets/css/style.css">
        <style>
            body { background-color: var(--light-bg); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
            .info-card { max-width: 500px; width: 100%; text-align: center; }
        </style>
    </head>
    <body>
        <div class="info-card glass-card">
            <span style="font-size: 50px;">📦</span>
            <h1 class="mt-3" style="border-bottom: none; padding-bottom: 0;">Return an Item</h1>
            <p class="text-muted my-3">No claim selected. Please go to the admin panel to manage active claims.</p>
            <div class="d-flex flex-column gap-2 mt-4">
                <a href="verify_claim.php" class="btn-custom btn-custom-primary">Admin Panel</a>
                <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-outline">Back to Dashboard</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

if ($claim_id > 0) {
    $sql = "SELECT c.*, m.match_id, li.item_name as lost_item, fi.item_name as found_item
            FROM claims c
            JOIN matches m ON c.match_id = m.match_id
            JOIN lost_items li ON m.lost_item_id = li.item_id
            JOIN found_items fi ON m.found_item_id = fi.item_id
            WHERE c.claim_id = $claim_id";
    $result = $conn->query($sql);

    if ($result->num_rows == 0) {
        die("Claim not found.");
    }

    $claim = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $claim_id = intval($_POST['claim_id']);
    
    // 1. Update claim status to returned
    $sql = "UPDATE claims SET status = 'returned' WHERE claim_id = $claim_id";
    $conn->query($sql);
    
    // 2. Update match status to returned
    $update_match = "UPDATE matches SET status = 'returned' WHERE match_id = 
                     (SELECT match_id FROM claims WHERE claim_id = $claim_id)";
    $conn->query($update_match);
    
    // 3. Update lost_item status to returned
    $update_lost = "UPDATE lost_items SET status = 'returned' WHERE item_id = 
                    (SELECT lost_item_id FROM matches WHERE match_id = 
                     (SELECT match_id FROM claims WHERE claim_id = $claim_id))";
    $conn->query($update_lost);
    
    // 4. Update found_item status to returned
    $update_found = "UPDATE found_items SET status = 'returned' WHERE item_id = 
                     (SELECT found_item_id FROM matches WHERE match_id = 
                      (SELECT match_id FROM claims WHERE claim_id = $claim_id))";
    $conn->query($update_found);
    
    $success = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Return - Lost & Found Assistant</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: var(--light-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .return-card {
            max-width: 550px;
            width: 100%;
        }
        .detail-box {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 15px;
            text-align: left;
        }
    </style>
</head>
<body>

    <div class="return-card glass-card text-center">
        <?php if ($success): ?>
            <div class="mb-4">
                <span style="font-size: 50px;">🎉</span>
                <h2 class="mt-2 text-success" style="border-bottom: none; padding-bottom: 0;">Item Handed Over!</h2>
                <p class="text-muted">The return has been successfully logged.</p>
            </div>
            
            <div class="alert alert-success alert-custom alert-custom-success py-3 px-4 mb-4 text-start">
                <strong>Status:</strong> <span class="badge bg-success">🎉 Returned</span><br>
                <small class="text-muted mt-2 d-block">The claim, match, and original reports have all been updated to Returned status and closed.</small>
            </div>

            <div class="d-flex flex-column gap-2">
                <a href="verify_claim.php" class="btn-custom btn-custom-primary py-2">
                    🔐 Go back to Admin Panel
                </a>
                <a href="../syafiqah/matching/dashboard.php" class="btn-custom btn-custom-outline py-2">
                    📋 Return to Dashboard
                </a>
            </div>
        <?php else: ?>
            <div class="mb-4">
                <span style="font-size: 50px;">📦</span>
                <h2 class="mt-2" style="border-bottom: none; padding-bottom: 0;">Mark Item as Returned</h2>
                <p class="text-muted">Confirm that the item has been handed back to its owner.</p>
            </div>
            
            <div class="detail-box mb-4">
                <p class="m-0 mb-1" style="font-size: 13px;"><strong>Claim ID:</strong> #<?php echo $claim['claim_id']; ?></p>
                <p class="m-0 mb-1" style="font-size: 13px;"><strong>Lost Item:</strong> <?php echo htmlspecialchars($claim['lost_item']); ?></p>
                <p class="m-0 mb-1" style="font-size: 13px;"><strong>Found Item:</strong> <?php echo htmlspecialchars($claim['found_item']); ?></p>
                <p class="m-0" style="font-size: 13px;"><strong>Current Status:</strong> <span class="badge bg-warning text-dark"><?php echo $claim['status']; ?></span></p>
            </div>

            <p class="text-muted mb-4" style="font-size: 14px;">
                By confirming, you verify that physical verification is complete and the item has been returned to the claimant. This action closes out the report.
            </p>

            <form method="POST" class="mb-3">
                <input type="hidden" name="claim_id" value="<?php echo $claim_id; ?>">
                <button type="submit" class="btn-custom btn-custom-success w-100 py-2">
                    ✅ Confirm Handover & Return
                </button>
            </form>

            <a href="verify_claim.php" class="btn-custom btn-custom-secondary w-100 py-2">
                Cancel
            </a>
        <?php endif; ?>
    </div>

</body>
</html>
