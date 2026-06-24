<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /tey/login/login.php");
    exit();
}

$claim_id = isset($_GET['claim_id']) ? intval($_GET['claim_id']) : 0;

// If no claim_id, show a friendly message instead of error
if ($claim_id == 0) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Return Item</title>
        <style>
            body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; text-align: center; }
            .container { background: #f9f9f9; padding: 30px; border-radius: 10px; }
            .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px; }
            .btn:hover { background: #0056b3; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>📦 Return an Item</h1>
            <p>No claim selected. Please go to the admin panel to view claims.</p>
            <a href="verify_claim.php" class="btn">Admin Panel</a>
            <a href="/index.php" class="btn">Back to Home</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $claim_id = intval($_POST['claim_id']);
    $sql = "UPDATE claims SET status = 'returned' WHERE claim_id = $claim_id";
    $conn->query($sql);
    $match_sql = "SELECT match_id FROM claims WHERE claim_id = $claim_id";
    $match_result = $conn->query($match_sql);
    $match_row = $match_result->fetch_assoc();
    $match_id = $match_row['match_id'];
    $update_match = "UPDATE matches SET status = 'returned' WHERE match_id = $match_id";
    $conn->query($update_match);
    $success = "✅ Item marked as RETURNED successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Return Item</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; }
        .container { background: #f9f9f9; padding: 30px; border-radius: 10px; text-align: center; }
        .success { color: #28a745; font-size: 28px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px; }
        .btn:hover { background: #0056b3; }
        .item-details { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; text-align: left; }
        input[type="submit"] { background: #28a745; color: white; padding: 12px 40px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        input[type="submit"]:hover { background: #218838; }
    </style>
</head>
<body>
<div class="container">
    <?php if (isset($success)): ?>
        <div class="success">🎉 <?php echo $success; ?></div>
        <p>The item has been successfully returned to the owner.</p>
        <a href="/index.php" class="btn">Back to Home</a>
        <a href="claim_status.php" class="btn">View My Claims</a>
    <?php else: ?>
        <h1>📦 Mark Item as Returned</h1>
        <div class="item-details">
            <p><strong>Claim ID:</strong> <?php echo $claim['claim_id']; ?></p>
            <p><strong>Lost Item:</strong> <?php echo htmlspecialchars($claim['lost_item']); ?></p>
            <p><strong>Found Item:</strong> <?php echo htmlspecialchars($claim['found_item']); ?></p>
            <p><strong>Status:</strong> <?php echo $claim['status']; ?></p>
        </div>
        <p>Confirm that the item has been successfully returned to the owner.</p>
        <form method="POST">
            <input type="hidden" name="claim_id" value="<?php echo $claim_id; ?>">
            <input type="submit" value="✅ Confirm Return">
        </form>
        <br>
        <a href="verify_claim.php" class="btn">Back to Admin</a>
    <?php endif; ?>
</div>
</body>
</html>
