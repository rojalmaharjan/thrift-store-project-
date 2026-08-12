<?php
session_start();
require 'db.php';

// Fetch featured categories
$categories = [];
$c_res = $conn->query("SELECT * FROM categories ORDER BY id ASC");
while ($row = $c_res->fetch_assoc()) {
    $categories[] = $row;
}

// Fetch recent active products
$products = [];
$p_res = $conn->query("SELECT p.*, c.name as category_name, u.full_name as seller_name FROM products p JOIN categories c ON p.category_id = c.id JOIN users u ON p.seller_id = u.id WHERE p.status = 'active' ORDER BY p.id DESC LIMIT 6");
while ($row = $p_res->fetch_assoc()) {
    $products[] = $row;
}

// Platform metrics
$user_count = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'];
$product_count = $conn->query("SELECT COUNT(*) as cnt FROM products")->fetch_assoc()['cnt'];
$order_count = $conn->query("SELECT COUNT(*) as cnt FROM orders")->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ThriftHub - Peer-to-Peer Thrift Store & Vintage Marketplace</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<nav class="navbar">
    <div class="container nav-container">
        <a href="index.php" class="brand">🧥 ThriftHub</a>
        <ul class="nav-links">
            <li><a href="index.php" class="nav-link active">Home</a></li>
            <li><a href="products.php" class="nav-link">Browse Marketplace</a></li>
            <?php if (isset($_SESSION['user_id'])) { ?>
                <li><a href="dashboard.php" class="nav-link">My Dashboard</a></li>
                <?php if (($_SESSION['role'] ?? '') === 'admin') { ?>
                    <li><a href="admin.php" class="nav-link" style="color:var(--blue);">Admin Panel</a></li>
                <?php } ?>
            <?php } ?>
        </ul>
        <div class="nav-actions">
            <?php if (isset($_SESSION['user_id'])) { ?>
                <a href="dashboard.php?page=add_product" class="nav-link" style="background:var(--green); color:white; padding:8px 16px; border-radius:8px; font-weight:600;">+ Sell Thrift Item</a>
                <a href="logout.php" class="nav-link" style="color:var(--red);">Logout</a>
            <?php } else { ?>
                <a href="login.php" class="nav-link">Sign In</a>
                <a href="register.php" class="nav-link" style="background:var(--blue); color:white; padding:8px 16px; border-radius:8px; font-weight:600;">Get Started</a>
            <?php } ?>
        </div>
    </div>
</nav>

<header class="hero">
    <div class="container">
        <div class="hero-badge">♻️ Sustainable & Affordable Fashion</div>
        <h1 class="hero-title">Buy, Sell & Thrift Pre-Loved Treasures</h1>
        <p class="hero-subtitle">
            Join Nepal's premier peer-to-peer thrift community. Turn your unused clothes, gadgets, books & vintage gear into cash!
        </p>

        <form action="products.php" method="GET" class="search-box">
            <input type="text" name="q" class="search-input" placeholder="Search jackets, books, sneakers, cameras...">
            <button type="submit" style="background:var(--blue); color:white; border:none; padding:10px 24px; border-radius:8px; font-weight:700; cursor:pointer;">Search</button>
        </form>

        <div style="display:flex; justify-content:center; gap:40px; margin-top:30px; border-top:1px solid var(--border); padding-top:20px;">
            <div><strong style="font-size:24px; color:var(--blue);"><?= number_format($product_count) ?>+</strong><br><span style="font-size:13px; color:var(--muted);">Listed Items</span></div>
            <div><strong style="font-size:24px; color:#3fb950;"><?= number_format($user_count) ?>+</strong><br><span style="font-size:13px; color:var(--muted);">Active Members</span></div>
            <div><strong style="font-size:24px; color:var(--white);"><?= number_format($order_count) ?>+</strong><br><span style="font-size:13px; color:var(--muted);">Completed Orders</span></div>
        </div>
    </div>
</header>

<section class="categories-section">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Explore Categories</h2>
                <p style="color:var(--muted); font-size:14px;">Find pre-loved items by category</p>
            </div>
            <a href="products.php" style="color:var(--blue); font-weight:600; font-size:14px;">View All &rarr;</a>
        </div>

        <div class="category-grid">
            <?php foreach ($categories as $c) { ?>
                <a href="products.php?cat=<?= $c['id'] ?>" class="category-card">
                    <div class="category-icon"><?= $c['icon'] ?></div>
                    <div class="category-name"><?= htmlspecialchars($c['name']) ?></div>
                </a>
            <?php } ?>
        </div>
    </div>
</section>

<section class="products-section">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Fresh Thrift Listings 🛍️</h2>
                <p style="color:var(--muted); font-size:14px;">Recently uploaded items from verified sellers</p>
            </div>
            <a href="products.php" style="color:var(--blue); font-weight:600; font-size:14px;">Explore All Items &rarr;</a>
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;">
            <?php foreach ($products as $p) {
                $img = !empty($p['image']) && file_exists(__DIR__ . '/uploads/' . $p['image']) ? 'uploads/' . $p['image'] : 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500';
            ?>
                <div class="product-card-public">
                    <div class="product-image-wrap">
                        <img src="<?= $img ?>" class="product-img-main" alt="<?= htmlspecialchars($p['name']) ?>" onerror="this.src='https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500';">
                        <span class="condition-badge"><?= htmlspecialchars($p['condition_status']) ?></span>
                    </div>
                    <div class="product-card-body">
                        <div style="font-size:12px; color:var(--blue); margin-bottom:4px; font-weight:600;"><?= htmlspecialchars($p['category_name']) ?></div>
                        <h3 class="product-title"><?= htmlspecialchars($p['name']) ?></h3>
                        <div class="product-price-tag">Rs. <?= number_format($p['price'], 2) ?></div>
                        <div class="product-footer">
                            <span>Seller: @<?= htmlspecialchars($p['seller_name']) ?></span>
                            <a href="product-detail.php?id=<?= $p['id'] ?>" style="color:var(--blue); font-weight:700;">View Details &rarr;</a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <p><strong>ThriftHub</strong> - Peer-to-Peer Thrift Store Platform</p>
        <p style="margin-top:6px;">Developed for BCA TU 4th Semester Web Technology Project &copy; <?= date('Y') ?></p> 
    </div>
</footer>

</body>
</html>
