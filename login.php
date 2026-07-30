<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$err = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ThriftHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <div class="container nav-container">
        <a href="index.php" class="brand">🧥 ThriftHub</a>
        <div class="nav-actions">
            <a href="login.php" class="nav-link active">Sign In</a>
            <a href="register.php" class="nav-link" style="padding: 8px 16px; background: var(--blue); color: white; border-radius: 8px; font-weight: 600;">Register</a>
        </div>
    </div>
</nav>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Welcome Back 👋</h1>
            <p style="color: var(--muted); font-size: 14px;">Sign in to manage your thrift store wallet & listings</p>
        </div>

        <?php if ($err === 'invalid') { ?>
            <div style="background: rgba(248,81,73,0.15); color: #f85149; border: 1px solid rgba(248,81,73,0.3); padding: 12px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; text-align: center;">
                ❌ Invalid username/email or password.
            </div>
        <?php } elseif ($err === 'empty') { ?>
            <div style="background: rgba(248,81,73,0.15); color: #f85149; border: 1px solid rgba(248,81,73,0.3); padding: 12px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; text-align: center;">
                ⚠️ Please fill in all fields.
            </div>
        <?php } ?>

        <form method="POST" action="action.php">
            <input type="hidden" name="action" value="login">

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 13px; color: var(--muted); margin-bottom: 6px;">Username or Email</label>
                <input type="text" name="username" placeholder="e.g. rojal or john_doe" required
                       style="width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; color: white; font-size: 14px;">
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 13px; color: var(--muted); margin-bottom: 6px;">Password</label>
                <input type="password" name="password" placeholder="••••••••" required
                       style="width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; color: white; font-size: 14px;">
            </div>

            <button type="submit" style="width: 100%; padding: 13px; background: var(--blue); color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 15px; cursor: pointer;">
                Sign In to Account
            </button>
        </form>

        <div style="margin-top: 24px; text-align: center; border-top: 1px solid var(--border); padding-top: 20px;">
            <p style="color: var(--muted); font-size: 14px;">
                Don't have an account? <a href="register.php" style="color: var(--blue); font-weight: 600;">Create one here</a>
            </p>
        </div>
    </div>
</div>

<footer class="footer">
    <div class="container">
        ThriftHub &copy; <?= date('Y') ?> - BCA TU 4th Sem Project.
    </div>
</footer>

</body>
</html>
