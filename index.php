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
    <title>ThriftHub - Urban Streetwear & Vintage Apparel Marketplace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@700;800;900&family=Plus+Jakarta+Sans:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <div class="container nav-container">
        <a href="index.php" class="brand">THRIFT<em>HUB</em></a>
        <ul class="nav-links">
            <li><a href="index.php" class="nav-link active">HOME</a></li>
            <li><a href="products.php" class="nav-link">BROWSE DROPS</a></li>
            <?php if (isset($_SESSION['user_id'])) { ?>
                <li><a href="dashboard.php" class="nav-link">DASHBOARD</a></li>
                <?php if (($_SESSION['role'] ?? '') === 'admin') { ?>
                    <li><a href="admin.php" class="nav-link" style="color:var(--crimson);">ADMIN PANEL</a></li>
                <?php } ?>
            <?php } ?>
        </ul>
        <div class="nav-actions">
            <?php if (isset($_SESSION['user_id'])) { ?>
                <a href="dashboard.php?page=add_product" class="nav-link" style="background:var(--crimson); color:white; padding:10px 18px; border-radius:8px; font-weight:800; font-size:12px;">+ DROP GARMENT</a>
                <a href="logout.php" class="nav-link" style="color:var(--crimson);">LOGOUT</a>
            <?php } else { ?>
                <a href="login.php" class="nav-link">SIGN IN</a>
                <a href="register.php" class="nav-link" style="background:var(--crimson); color:white; padding:10px 18px; border-radius:8px; font-weight:800; font-size:12px;">JOIN MARKETPLACE</a>
            <?php } ?>
        </div>
    </div>
</nav>

<header class="hero">
    <div class="container">
        <div class="hero-badge">HIP-HOP // CULTURE // CLOTHING // EST. 2026</div>
        <h1 class="hero-title">FOR THE BOLD — URBAN THRIFT DROPS</h1>
        <p class="hero-subtitle">
            "We don't design clothing. We design a vocabulary." Discover authentic pre-loved vintage, oversized outerwear & streetwear apparel.
        </p>

        <form action="products.php" method="GET" class="search-box">
            <input type="text" name="q" class="search-input" placeholder="Search jackets, hoodies, denim, vintage tees...">
            <button type="submit" style="background:linear-gradient(135deg, var(--crimson), var(--crimson-dark)); color:white; border:none; padding:12px 26px; border-radius:8px; font-weight:800; text-transform:uppercase; cursor:pointer; letter-spacing:0.5px;">SEARCH</button>
        </form>

        <div style="display:flex; justify-content:center; gap:40px; margin-top:34px; border-top:1px solid var(--border); padding-top:24px;">
            <div><strong style="font-family:'Outfit', sans-serif; font-size:26px; color:var(--cream);"><?= number_format($product_count) ?>+</strong><br><span style="font-size:11px; font-weight:800; color:var(--muted); letter-spacing:1px; text-transform:uppercase;">Listed Drops</span></div>
            <div><strong style="font-family:'Outfit', sans-serif; font-size:26px; color:var(--crimson);"><?= number_format($user_count) ?>+</strong><br><span style="font-size:11px; font-weight:800; color:var(--muted); letter-spacing:1px; text-transform:uppercase;">Curators & Members</span></div>
            <div><strong style="font-family:'Outfit', sans-serif; font-size:26px; color:var(--white);"><?= number_format($order_count) ?>+</strong><br><span style="font-size:11px; font-weight:800; color:var(--muted); letter-spacing:1px; text-transform:uppercase;">Rehomed Garments</span></div>
        </div>
    </div>
</header>

<section class="categories-section">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">CATEGORIES // CURATED COLLECTION</h2>
                <p style="color:var(--muted); font-size:14px;">Find pre-loved streetwear and vintage apparel by category</p>
            </div>
            <a href="products.php" style="color:var(--crimson); font-weight:800; font-size:12px; letter-spacing:1px;">VIEW ALL DROPS</a>
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
                <h2 class="section-title">FRESH THRIFT DROPS</h2>
                <p style="color:var(--muted); font-size:14px;">Recently uploaded garments from verified streetwear curators</p>
            </div>
            <a href="products.php" style="color:var(--crimson); font-weight:800; font-size:12px; letter-spacing:1px;">EXPLORE ALL DROPS &rarr;</a>
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;">
            <?php foreach ($products as $p) {
                $img = !empty($p['image']) && file_exists(__DIR__ . '/uploads/' . $p['image']) ? 'uploads/' . $p['image'] : 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=500';
            ?>
                <div class="product-card-public">
                    <div class="product-image-wrap">
                        <img src="<?= $img ?>" class="product-img-main" alt="<?= htmlspecialchars($p['name']) ?>" onerror="this.src='https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500';">
                        <span class="condition-badge"><?= htmlspecialchars($p['condition_status']) ?></span>
                    </div>
                    <div class="product-card-body">
                        <div style="font-size:11px; color:var(--crimson); margin-bottom:4px; font-weight:800; text-transform:uppercase; letter-spacing:1px;"><?= htmlspecialchars($p['category_name']) ?></div>
                        <h3 class="product-title"><?= htmlspecialchars($p['name']) ?></h3>
                        <div class="product-price-tag">Rs. <?= number_format($p['price'], 2) ?></div>
                        <div class="product-footer">
                            <span>CURATOR: @<?= htmlspecialchars($p['seller_name']) ?></span>
                            <a href="product-detail.php?id=<?= $p['id'] ?>" style="color:var(--crimson); font-weight:800; letter-spacing:0.5px;">VIEW DROP &rarr;</a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <p><strong>THRIFTHUB</strong> — Urban Streetwear & Vintage Apparel Marketplace</p>
        <p style="margin-top:6px; font-size:13px;">Designed for the Bold &copy; <?= date('Y') ?></p> 
    </div>
</footer>

</body>
</html>