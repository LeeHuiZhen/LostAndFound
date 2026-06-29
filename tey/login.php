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
        $sql = "SELECT id, name, password FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $name, $hashed_password);
                $stmt->fetch();
                if (password_verify($password, $hashed_password)) {
                    $_SESSION["loggedin"]  = true;
                    $_SESSION["user_id"]   = $id;
                    $_SESSION["user_name"] = $name;
                    header("Location: ../index.php");
                    exit;
                } else {
                    $error = "Invalid password. Please try again.";
                }
            } else {
                $error = "No account found with that email address.";
            }
            $stmt->close();
        }
    } else {
        $error = "Please enter both email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In – UTM Lost & Found Assistant</title>
    <meta name="description" content="Sign in to the UTM Campus Lost and Found portal.">
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
        

        .login-card {
            max-width: 430px;
            width: 100%;
            background: rgba(255, 255, 255, 0.96) !important;
            border: 1px solid rgba(255, 255, 255, 0.6) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.4) !important;
            animation: fadeInUp 0.6s ease both;
        }

        .login-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 32px 72px rgba(0, 0, 0, 0.5) !important;
        }

        .brand-bar {
            background: var(--primary-gradient);
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }

        .brand-bar span { font-size: 28px; }

        .brand-bar div h3 {
            font-size: 15px;
            font-weight: 800;
            color: white;
            margin: 0;
            letter-spacing: -0.3px;
        }

        .brand-bar div p {
            font-size: 11px;
            color: rgba(255,255,255,0.75);
            margin: 0;
        }

        .divider-text {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .divider-text::before,
        .divider-text::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }
    </style>
</head>
<body>

    <div class="login-card glass-card">

        <!-- Brand bar at top of card -->
        <div class="brand-bar">
            <span>🔍</span>
            <div>
                <h3>UTM Lost & Found</h3>
                <p>Campus Item Recovery Portal</p>
            </div>
        </div>

        <div class="text-center mb-4">
            <span style="font-size: 38px;">🔐</span>
            <h2 class="mt-2" style="border-bottom: none; padding-bottom: 0; font-size: 22px;">Welcome Back</h2>
            <p class="text-muted" style="font-size: 13px;">Sign in to access your workspace</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-custom alert-custom-danger mb-4" style="font-size: 13px;">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="username@utm.my" required>
            </div>
            <div class="form-group mb-4">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-custom btn-custom-primary w-100 py-2" style="font-size: 15px;">
                Sign In →
            </button>
        </form>

        <div class="divider-text">or</div>

        <div class="text-center">
            <p class="mb-2" style="font-size: 13px; color: var(--text-muted);">
                Don't have an account?
                <a href="register.php" style="color: var(--primary-color); font-weight: 700; text-decoration: none;">Sign up now</a>
            </p>
            <a href="../index.php" style="font-size: 12px; color: var(--text-muted); text-decoration: none;">🏠 Back to Home</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
