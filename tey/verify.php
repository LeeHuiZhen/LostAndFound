<?php
require_once '../config.php';
$email = isset($_GET['email']) ? $_GET['email'] : '';
$message = '';
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    
    // Auto-verify mock system for project demonstration purposes
    $sql = "UPDATE users SET is_verified = 1 WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $message = "Account successfully verified! Redirecting to login...";
            $success = true;
            header("refresh:2; url=login.php");
        } else {
            $message = "Error updating verification status.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account - UTM Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .verify-card { max-width: 440px; width: 100%; }
    </style>
</head>
<body>
    <div class="verify-card glass-card text-center">
        <div class="mb-4">
            <span style="font-size: 50px;">📧</span>
            <h2 class="mt-2" style="border-bottom: none; padding-bottom: 0;">Verify Account</h2>
            <p class="text-muted" style="font-size: 13px;">Complete your registration process</p>
        </div>

        <p class="mb-4" style="font-size: 14px; line-height: 1.5; color: var(--text-muted);">
            A verification gateway has been initialized for:<br>
            <strong style="color: var(--primary-color); font-size: 15px;"><?php echo htmlspecialchars($email); ?></strong>
        </p>

        <?php if(!empty($message)): ?>
            <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?> alert-custom alert-custom-<?php echo $success ? 'success' : 'danger'; ?> py-2 px-3 mb-4" style="font-size: 13px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form action="verify.php" method="post">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <button type="submit" class="btn-custom btn-custom-primary w-100 py-2">
                Click to Simulate Email Link Click
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="login.php" style="font-size: 13px; color: var(--text-muted); text-decoration: none;">🚪 Go to Login Page</a>
        </div>
    </div>
</body>
</html>