<?php
session_start();
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("Location: ../index.php");
    exit;
}
require_once '../config.php';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        // Security Fix: fetch is_verified along with credentials
        $sql = "SELECT id, name, password, is_verified FROM users WHERE email = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $name, $hashed_password, $is_verified);
                $stmt->fetch();

                if (password_verify($password, $hashed_password)) {
                    // Security Fix: block unverified accounts
                    if ($is_verified == 0) {
                        $error = "Your account has not been verified yet. Please check your email or use the verification link.";
                    } else {
                        $_SESSION["loggedin"]  = true;
                        $_SESSION["user_id"]   = $id;
                        $_SESSION["user_name"] = $name;
                        header("Location: ../index.php");
                        exit;
                    }
                } else {
                    $error = "Incorrect password. Please try again.";
                }
            } else {
                $error = "No account found with that email address.";
            }
            $stmt->close();
        }
    } else {
        $error = "Please enter both your email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — UTM Lost &amp; Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background-color: var(--bg-base); overflow: hidden; }
        @media (max-width: 900px) { body { overflow: auto; } }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <!-- ===== BRAND PANEL ===== -->
        <div class="auth-brand-panel">
            <div class="auth-brand-content">
                <div class="auth-brand-icon">🔍</div>
                <h1>Welcome Back</h1>
                <p>Sign in to access your personal workspace and track your lost or found items on campus.</p>
                <div class="auth-brand-features">
                    <div class="feat">
                        <span class="feat-icon">⚡</span>
                        <span>Real-time AI matching engine</span>
                    </div>
                    <div class="feat">
                        <span class="feat-icon">🔒</span>
                        <span>Secure proof-of-ownership verification</span>
                    </div>
                    <div class="feat">
                        <span class="feat-icon">📍</span>
                        <span>Interactive campus map reporting</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== FORM PANEL ===== -->
        <div class="auth-form-panel">
            <div class="auth-form-inner animate-fade-up">
                <div style="margin-bottom:32px;">
                    <a href="../index.php" style="font-size:13px; color:var(--text-muted);">← Back to Home</a>
                </div>
                <h2>Sign In</h2>
                <p class="auth-subtitle">Access the UTM Lost &amp; Found Portal</p>

                <?php if (!empty($error)): ?>
                    <div class="alert-custom alert-custom-danger" style="margin-bottom:20px;">
                        ⚠️ <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="post">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control"
                               placeholder="your@utm.my" required autocomplete="email">
                    </div>
                    <div class="form-group" style="margin-bottom:28px;">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control"
                               placeholder="••••••••" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn-custom btn-custom-primary w-100" style="padding:14px;">
                        🔑 Sign In
                    </button>
                </form>

                <div class="auth-divider"><span>or</span></div>

                <p class="auth-link">
                    Don't have an account?
                    <a href="register.php">Create one free →</a>
                </p>
                <p class="auth-link" style="margin-top:10px;">
                    <a href="verify.php" style="color:var(--text-muted); font-weight:400;">Need to verify your account?</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
