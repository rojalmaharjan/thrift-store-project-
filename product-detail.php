<?php
session_start();
require 'db.php';

$id = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT p.*, c.name as category_name, u.full_name as seller_name, u.username as seller_username, u.email as seller_email FROM products p JOIN categories c ON p.category_id = c.id JOIN users u ON p.seller_id = u.id WHERE p.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header('Location: products.php');
    exit;
}

$product = $res->fetch_assoc();
$img = !empty($product['image']) && file_exists(__DIR__ . '/uploads/' . $product['image']) ? 'uploads/' . $product['image'] : 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500';

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? 0;
$is_own_item = $is_logged_in && ($user_id == $product['seller_id']);

// Fetch user wallet balance if logged in
$balance = 0;
if ($is_logged_in) {
    $u_stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $u_stmt->bind_param("i", $user_id);
    $u_stmt->execute();
    $balance = floatval($u_stmt->get_result()->fetch_assoc()['balance']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - ThriftHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <div class="container nav-container">
        <a href="index.php" class="brand">🧥 ThriftHub</a>
        <ul class="nav-links">
            <li><a href="index.php" class="nav-link">Home</a></li>
            <li><a href="products.php" class="nav-link active">Browse Marketplace</a></li>
            <?php if ($is_logged_in) { ?>
                <li><a href="dashboard.php" class="nav-link">My Dashboard</a></li>
            <?php } ?>
        </ul>
        <div class="nav-actions">
            <?php if ($is_logged_in) { ?>
                <a href="dashboard.php?page=add_product" class="nav-link" style="background:var(--green); color:white; padding:8px 16px; border-radius:8px; font-weight:600;">+ Sell Thrift Item</a>
            <?php } else { ?>
                <a href="login.php" class="nav-link">Sign In</a>
            <?php } ?>
        </div>
    </div>
</nav>

<div class="container" style="padding: 50px 0;">
    <a href="products.php" style="color:var(--muted); font-size:14px;">&larr; Back to Products</a>

    <div class="detail-grid">
        <div class="detail-img-box">
            <img src="<?= $img ?>" class="detail-img" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.src='https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500';">
        </div>

        <div class="detail-info">
            <div style="font-size:14px; color:var(--blue); font-weight:600; text-transform:uppercase; margin-bottom:8px;">
                <?= htmlspecialchars($product['category_name']) ?>
            </div>
            <h1 style="font-size:32px; font-weight:800; margin-bottom:12px;"><?= htmlspecialchars($product['name']) ?></h1>

            <div style="display:flex; align-items:center; gap:16px; margin-bottom:20px;">
                <span style="font-size:32px; font-weight:800; color:#3fb950;">Rs. <?= number_format($product['price'], 2) ?></span>
                <span style="background:rgba(88,166,255,0.15); color:var(--blue); border:1px solid rgba(88,166,255,0.3); padding:4px 12px; border-radius:20px; font-size:13px; font-weight:600;">
                    Condition: <?= htmlspecialchars($product['condition_status']) ?>
                </span>
            </div>

            <div style="background:var(--surface); border:1px solid var(--border); padding:20px; border-radius:12px; margin-bottom:24px;">
                <h3 style="font-size:15px; margin-bottom:8px; color:var(--white);">Item Description</h3>
                <p style="color:var(--muted); font-size:14px; white-space:pre-line;">
                    <?= !empty($product['description']) ? htmlspecialchars($product['description']) : 'No detailed description provided by the seller.' ?>
                </p>
            </div>

            <div style="background:var(--surface); border:1px solid var(--border); padding:20px; border-radius:12px; margin-bottom:28px;">
                <div style="font-size:13px; color:var(--muted); margin-bottom:6px;">Seller Information</div>
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <div>
                        <strong style="font-size:16px; color:white;"><?= htmlspecialchars($product['seller_name']) ?></strong>
                        <div style="color:var(--muted); font-size:13px;">@<?= htmlspecialchars($product['seller_username']) ?></div>
                    </div>
                    <span style="background:rgba(35,134,54,0.15); color:#3fb950; padding:4px 10px; border-radius:12px; font-size:12px; font-weight:600;">✔ Verified Seller</span>
                </div>
            </div>

            <?php if ($product['stock'] <= 0 || $product['status'] !== 'active') { ?>
                <div style="background:rgba(248,81,73,0.15); color:var(--red); padding:16px; border-radius:10px; text-align:center; font-weight:700; border:1px solid rgba(248,81,73,0.3);">
                    ❌ Sold Out / Unavailable
                </div>
            <?php } elseif ($is_own_item) { ?>
                <div style="background:rgba(210,153,34,0.15); color:var(--amber); padding:16px; border-radius:10px; text-align:center; font-weight:600; border:1px solid rgba(210,153,34,0.3);">
                    ℹ️ You listed this item for sale.
                </div>
            <?php } elseif ($is_logged_in) { ?>
                <form method="POST" action="action.php">
                    <input type="hidden" name="action" value="purchase">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                    <div style="margin-bottom:12px; font-size:13px; color:var(--muted);">
                        Available Wallet Balance: <strong style="color:white;">Rs. <?= number_format($balance, 2) ?></strong>
                    </div>

                    <?php if ($balance < $product['price']) { ?>
                        <div style="color:var(--red); font-size:13px; margin-bottom:12px;">⚠️ Insufficient wallet balance. Please top up your wallet in dashboard.</div>
                    <?php } ?>

                    <button type="submit" <?= $balance < $product['price'] ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>
                            style="width:100%; padding:16px; background:var(--green); color:white; border:none; border-radius:10px; font-size:16px; font-weight:800; cursor:pointer;">
                        ⚡ Buy Now (Rs. <?= number_format($product['price'], 2) ?>)
                    </button>
                </form>
            <?php } else { ?>
                <a href="login.php" style="display:block; text-align:center; padding:16px; background:var(--blue); color:white; border-radius:10px; font-size:16px; font-weight:700;">
                    Sign in to Purchase Item
                </a>
            <?php } ?>
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
