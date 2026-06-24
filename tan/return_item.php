<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /tey/login.php");
    exit();
}

$claim_id = isset($_GET['claim_id']) ? intval($_GET['claim_id']) : 0;

if ($claim_id > 0) {
    $sql = "SELECT c.*, m.match_id, li.item_name as lost_item, fi.item_name as found_item
            FROM claims c
            JOIN matches m ON c.match_id = m.match_id
            JOIN lost_items li ON m.lost_item_id = li.item_id
            JOIN found_items fi ON m.found_item_id = fi.item_id
            WHERE c.claim_id = $claim_id";
    $result = $conn->query($sql);
    $claim = $result->fetch_assoc();
}

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
    <?php elseif ($claim_id > 0 && isset($claim)): ?>
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
        <a href="claim_status.php" class="btn">Back to Claims</a>
    <?php else: ?>
        <h1>Invalid Claim</h1>
        <p>The claim you are trying to return does not exist.</p>
        <a href="claim_status.php" class="btn">Back to Claims</a>
    <?php endif; ?>
</div>
</body>
</html>
