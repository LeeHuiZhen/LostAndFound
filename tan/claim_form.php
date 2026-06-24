<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /tey/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$match_id = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;

// If no match_id, show a friendly message instead of error
if ($match_id == 0) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Claim Item</title>
        <style>
            body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; text-align: center; }
            .container { background: #f9f9f9; padding: 30px; border-radius: 10px; }
            .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px; }
            .btn:hover { background: #0056b3; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>📝 Claim an Item</h1>
            <p>No match selected. Please go to your dashboard to view matches.</p>
            <a href="claim_status.php" class="btn">View My Claims</a>
            <a href="/index.php" class="btn">Back to Home</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Get match details
$sql = "SELECT m.*, li.item_name as lost_item, li.description as lost_desc, 
               fi.item_name as found_item, fi.description as found_desc, fi.photo_url
        FROM matches m 
        JOIN lost_items li ON m.lost_item_id = li.item_id
        JOIN found_items fi ON m.found_item_id = fi.item_id
        WHERE m.match_id = $match_id AND m.status = 'pending'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Match not found or already claimed.");
}

$match = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Claim Item</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; }
        .container { background: #f9f9f9; padding: 30px; border-radius: 10px; }
        .item-details { background: #fff; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ddd; }
        .item-details img { max-width: 200px; border-radius: 5px; }
        label { font-weight: bold; display: block; margin-top: 15px; }
        input[type="text"], textarea, input[type="file"] { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px; }
        input[type="submit"] { background: #28a745; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin-top: 20px; }
        input[type="submit"]:hover { background: #218838; }
        .btn-back { display: inline-block; background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px; }
        .btn-back:hover { background: #5a6268; }
    </style>
</head>
<body>
<div class="container">
    <h1>📝 Claim Verification</h1>
    <p>You are claiming an item. Please provide proof of ownership.</p>

    <div class="item-details">
        <h3>Item Details</h3>
        <p><strong>Lost Item:</strong> <?php echo htmlspecialchars($match['lost_item']); ?></p>
        <p><strong>Found Item:</strong> <?php echo htmlspecialchars($match['found_item']); ?></p>
        <p><strong>Found Item Description:</strong> <?php echo htmlspecialchars($match['found_desc']); ?></p>
        <?php if ($match['photo_url']): ?>
            <img src="<?php echo htmlspecialchars($match['photo_url']); ?>" alt="Found item photo">
        <?php endif; ?>
    </div>

    <form action="proof_upload.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">
        <label for="proof_description">Describe your lost item (include unique features, color, brand, etc.):</label>
        <textarea name="proof_description" id="proof_description" rows="4" required></textarea>
        <label for="proof_file">Upload proof of ownership (receipt, photo, serial number, etc.):</label>
        <input type="file" name="proof_file" id="proof_file" required>
        <br>
        <input type="submit" value="Submit Claim">
    </form>
    <br>
    <a href="claim_status.php" class="btn-back">View My Claims</a>
    <a href="/index.php" class="btn-back">Back to Home</a>
</div>
</body>
</html>
