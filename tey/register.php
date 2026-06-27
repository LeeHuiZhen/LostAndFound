<?php
require_once '../config.php';
$error = ''; $success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $password = trim($_POST['password']);

    if (!empty($name) && !empty($email) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $error = "This email is already registered. Please sign in instead.";
                } else {
                    $insert_sql = "INSERT INTO users (name, email, phone, password, is_verified) VALUES (?, ?, ?, ?, 0)";
                    if ($insert_stmt = $conn->prepare($insert_sql)) {
                        $insert_stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);
                        if ($insert_stmt->execute()) {
                            $success = "Account created! Redirecting to verification...";
                            header("refresh:2; url=verify.php?email=" . urlencode($email));
                        } else {
                            $error = "Something went wrong. Please try again.";
                        }
                        $insert_stmt->close();
                    }
                }
            }
            $stmt->close();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — UTM Lost &amp; Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background-color: var(--bg-base); overflow-x: hidden; }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <!-- ===== BRAND PANEL ===== -->
        <div class="auth-brand-panel">
            <div class="auth-brand-content">
                <div class="auth-brand-icon">📝</div>
                <h1>Join UTM L&amp;F</h1>
                <p>Create a free account to report items, get matched instantly, and help others recover what they've lost on campus.</p>
                <div class="auth-brand-features">
                    <div class="feat">
                        <span class="feat-icon">🗺️</span>
                        <span>Interactive campus map to pin locations</span>
                    </div>
                    <div class="feat">
                        <span class="feat-icon">🤖</span>
                        <span>Auto-tagging with Vision AI</span>
                    </div>
                    <div class="feat">
                        <span class="feat-icon">🔔</span>
                        <span>Instant match notifications</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== FORM PANEL ===== -->
        <div class="auth-form-panel" style="width:500px;">
            <div class="auth-form-inner animate-fade-up">
                <div style="margin-bottom:32px;">
                    <a href="../index.php" style="font-size:13px; color:var(--text-muted);">← Back to Home</a>
                </div>
                <h2>Create Account</h2>
                <p class="auth-subtitle">Sign up to report and track items on campus</p>

                <?php if (!empty($error)): ?>
                    <div class="alert-custom alert-custom-danger">⚠️ <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="alert-custom alert-custom-success">✅ <?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form action="register.php" method="post">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="Ahmad Razif" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="your@utm.my" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" name="phone" id="phone" class="form-control" placeholder="012-3456789">
                    </div>
                    <div class="form-group" style="margin-bottom:28px;">
                        <label for="password">Password *</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Choose a strong password" required>
                    </div>
                    <button type="submit" class="btn-custom btn-custom-primary w-100" style="padding:14px;">
                        🚀 Create Account
                    </button>
                </form>

                <p class="auth-link" style="margin-top:24px;">
                    Already have an account? <a href="login.php">Sign in →</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
