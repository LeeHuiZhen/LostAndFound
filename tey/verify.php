<?php
require_once '../config.php';
$email   = isset($_GET['email']) ? $_GET['email'] : '';
$message = '';
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $sql = "UPDATE users SET is_verified = 1 WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $message = "Account successfully verified! Redirecting to login...";
            $success = true;
            header("refresh:2; url=login.php");
        } else {
            $message = "Error updating verification status. Please try again.";
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
    <title>Verify Account – UTM Lost & Found</title>
    <meta name="description" content="Verify your UTM Lost and Found account to complete registration.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(rgba(15, 23, 42, 0.5), rgba(15, 23, 42, 0.7)),
                        url('../LostAndFound_background.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .verify-card {
            max-width: 440px;
            width: 100%;
            background: rgba(255, 255, 255, 0.96) !important;
            border: 1px solid rgba(255, 255, 255, 0.6) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.4) !important;
            animation: fadeInUp 0.6s ease both;
            text-align: center;
        }

        .verify-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 32px 72px rgba(0, 0, 0, 0.5) !important;
        }

        .email-display {
            background: linear-gradient(135deg, #ede9fe, #ddd6fe);
            border: 1px solid #c4b5fd;
            border-radius: var(--radius-sm);
            padding: 12px 18px;
            margin: 16px 0 24px;
            font-size: 14px;
            font-weight: 700;
            color: var(--secondary-color);
            word-break: break-all;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 24px;
        }

        .step-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--border-color);
        }

        .step-dot.active {
            background: var(--primary-color);
            width: 24px;
            border-radius: 4px;
        }

        .step-dot.done { background: var(--success-color); }
    </style>
</head>
<body>

    <div class="verify-card glass-card">

        <!-- Step indicator: Register → Verify → Login -->
        <div class="step-indicator">
            <div class="step-dot done"></div>
            <div class="step-dot active"></div>
            <div class="step-dot"></div>
        </div>

        <span style="font-size: 50px; display: block; margin-bottom: 14px;">📧</span>
        <h2 style="border-bottom: none; padding-bottom: 0; font-size: 22px;">Verify Your Account</h2>
        <p class="text-muted" style="font-size: 13px; margin-top: 6px;">Complete your registration to access the portal</p>

        <p style="font-size: 13px; color: var(--text-muted); margin-top: 16px; margin-bottom: 4px;">Verifying account for:</p>
        <div class="email-display">📩 <?php echo htmlspecialchars($email); ?></div>

        <?php if (!empty($message)): ?>
            <div class="alert-custom alert-custom-<?php echo $success ? 'success' : 'danger'; ?> mb-4" style="font-size: 13px;">
                <?php echo $success ? '✅ ' : '⚠️ '; ?><?php echo htmlspecialchars($message); ?>
            </div>
        <?php else: ?>
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px; line-height: 1.6;">
                In a production system, a link would be sent to your email. For this demonstration, click the button below to simulate clicking the verification link.
            </p>
        <?php endif; ?>

        <form action="verify.php" method="post">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <button type="submit" class="btn-custom btn-custom-primary w-100 py-2" style="font-size: 14px;">
                ✓ Simulate Email Verification Click
            </button>
        </form>

        <div class="text-center mt-4" style="padding-top: 16px; border-top: 1px solid var(--border-color);">
            <a href="login.php" style="font-size: 12px; color: var(--text-muted); text-decoration: none;">🚪 Go directly to Login</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
