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
    <title>Create Account - ThriftHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <div class="container nav-container">
        <a href="index.php" class="brand"> ThriftHub</a>
        <div class="nav-actions">
            <a href="login.php" class="nav-link">Sign In</a>
            <a href="register.php" class="nav-link active" style="padding: 8px 16px; background: var(--blue); color: white; border-radius: 8px; font-weight: 600;">Register</a>
        </div>
    </div>
</nav>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Create Account </h1>
            <p style="color: var(--muted); font-size: 14px;">Join ThriftHub and get Rs. 5,000 demo balance instantly!</p>
        </div>

        <?php if ($err === 'exists') { ?>
            <div style="background: rgba(248,81,73,0.15); color: #f85149; border: 1px solid rgba(248,81,73,0.3); padding: 12px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; text-align: center;">
                 Username or email address is already registered.
            </div>
        <?php } elseif ($err === 'empty') { ?>
            <div style="background: rgba(248,81,73,0.15); color: #f85149; border: 1px solid rgba(248,81,73,0.3); padding: 12px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; text-align: center;">
                 Please fill out all required fields.
            </div>
        <?php } ?>

        <form method="POST" action="action.php">
            <input type="hidden" name="action" value="register">

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; color: var(--muted); margin-bottom: 6px;">Full Name</label>
                <input type="text" name="full_name" placeholder="e.g. Rojal Maharjan" required
                       style="width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; color: white; font-size: 14px;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; color: var(--muted); margin-bottom: 6px;">Username</label>
                <input type="text" name="username" placeholder="e.g. rojal_m" required
                       style="width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; color: white; font-size: 14px;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; color: var(--muted); margin-bottom: 6px;">Email Address</label>
                <input type="email" name="email" placeholder="rojal@example.com" required
                       style="width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; color: white; font-size: 14px;">
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 13px; color: var(--muted); margin-bottom: 6px;">Password</label>
                <input type="password" name="password" placeholder="Create password" required
                       style="width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; color: white; font-size: 14px;">
            </div>

            <button type="submit" style="width: 100%; padding: 13px; background: var(--green); color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 15px; cursor: pointer;">
                Register & Get Rs. 5,000 Wallet
            </button>
        </form>

        <div style="margin-top: 24px; text-align: center; border-top: 1px solid var(--border); padding-top: 20px;">
            <p style="color: var(--muted); font-size: 14px;">
                Already have an account? <a href="login.php" style="color: var(--blue); font-weight: 600;">Sign in here</a>
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
