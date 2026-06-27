<?php
require_once '../config.php';
$email   = isset($_GET['email']) ? $_GET['email'] : '';
$message = '';
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $sql   = "UPDATE users SET is_verified = 1 WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $message = "Account verified successfully! Redirecting to login...";
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
    <title>Verify Account — UTM Lost &amp; Found</title>
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
            background: radial-gradient(ellipse at 30% 50%, rgba(99,102,241,0.15) 0%, transparent 60%),
                        radial-gradient(ellipse at 70% 30%, rgba(139,92,246,0.1) 0%, transparent 50%);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(99,102,241,0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            pointer-events: none;
            opacity: 0.5;
        }
        .verify-card {
            max-width: 460px; width: 100%;
            position: relative; z-index: 1;
            text-align: center;
        }
        .email-icon-wrap {
            width: 88px; height: 88px;
            background: rgba(99,102,241,0.12);
            border: 1px solid rgba(99,102,241,0.25);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 40px;
            margin: 0 auto 28px;
            animation: float 4s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <div class="verify-card glass-card animate-fade-up">
        <div class="email-icon-wrap">📧</div>

        <h2 style="font-size:24px; font-weight:800; margin-bottom:8px;">Verify Your Account</h2>
        <p style="font-size:14px; color:var(--text-muted); margin-bottom:24px;">
            A verification request has been initialized for:<br>
            <strong style="color:var(--primary-light); font-size:15px;"><?php echo htmlspecialchars($email); ?></strong>
        </p>

        <?php if (!empty($message)): ?>
            <div class="alert-custom <?php echo $success ? 'alert-custom-success' : 'alert-custom-danger'; ?>" style="text-align:left; margin-bottom:20px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form action="verify.php" method="post" style="margin-bottom:20px;">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <button type="submit" class="btn-custom btn-custom-primary w-100" style="padding:14px;">
                ✅ Click to Verify My Account
            </button>
        </form>
        <p style="font-size:12px; color:var(--text-muted);">
            This simulates an email verification link click for the project demo.
        </p>
        <?php endif; ?>

        <div style="margin-top:28px; padding-top:20px; border-top:1px solid var(--border);">
            <a href="login.php" style="font-size:13px; color:var(--text-muted);">🚪 Already verified? Sign in →</a>
        </div>
    </div>
</body>
</html>
