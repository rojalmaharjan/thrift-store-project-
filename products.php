<?php
session_start();
require 'db.php';

$search = trim($_GET['q'] ?? '');
$cat_id = intval($_GET['cat'] ?? 0);
$cond = trim($_GET['cond'] ?? '');
$sort = trim($_GET['sort'] ?? 'newest');

// Build SQL Query
$sql = "SELECT p.*, c.name as category_name, u.full_name as seller_name FROM products p JOIN categories c ON p.category_id = c.id JOIN users u ON p.seller_id = u.id WHERE p.status = 'active'";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

if ($cat_id > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $cat_id;
    $types .= "i";
}

if (!empty($cond)) {
    $sql .= " AND p.condition_status = ?";
    $params[] = $cond;
    $types .= "s";
}

if ($sort === 'price_asc') {
    $sql .= " ORDER BY p.price ASC";
} elseif ($sort === 'price_desc') {
    $sql .= " ORDER BY p.price DESC";
} else {
    $sql .= " ORDER BY p.id DESC";
}

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$products = [];
while ($row = $res->fetch_assoc()) {
    $products[] = $row;
}

// Fetch categories for filter dropdown
$categories = [];
$c_res = $conn->query("SELECT * FROM categories ORDER BY name ASC");
while ($row = $c_res->fetch_assoc()) {
    $categories[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Marketplace - ThriftHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <div class="container nav-container">
        <a href="index.php" class="brand">🧥 ThriftHub</a>
        <ul class="nav-links">
            <li><a href="index.php" class="nav-link">Home</a></li>
            <li><a href="products.php" class="nav-link active">Browse Marketplace</a></li>
            <?php if (isset($_SESSION['user_id'])) { ?>
                <li><a href="dashboard.php" class="nav-link">My Dashboard</a></li>
            <?php } ?>
        </ul>
        <div class="nav-actions">
            <?php if (isset($_SESSION['user_id'])) { ?>
                <a href="dashboard.php?page=add_product" class="nav-link" style="background:var(--green); color:white; padding:8px 16px; border-radius:8px; font-weight:600;">+ Sell Thrift Item</a>
            <?php } else { ?>
                <a href="login.php" class="nav-link">Sign In</a>
            <?php } ?>
        </div>
    </div>
</nav>

<div class="container" style="padding: 40px 0;">
    <div style="margin-bottom: 30px;">
        <h1>🛍️ Thrift Store Marketplace</h1>
        <p style="color:var(--muted); font-size:14px;">Explore pre-loved clothing, electronics, books & vintage collectibles.</p>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="products.php" style="background:var(--surface); border:1px solid var(--border); padding:20px; border-radius:14px; margin-bottom:36px; display:grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap:16px; align-items:end;">
        <div>
            <label style="display:block; font-size:12px; color:var(--muted); margin-bottom:6px;">Search Keyword</label>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search product name..." style="width:100%; padding:10px; background:var(--bg); border:1px solid var(--border); border-radius:8px; color:white;">
        </div>

        <div>
            <label style="display:block; font-size:12px; color:var(--muted); margin-bottom:6px;">Category</label>
            <select name="cat" style="width:100%; padding:10px; background:var(--bg); border:1px solid var(--border); border-radius:8px; color:white;">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $c) { ?>
                    <option value="<?= $c['id'] ?>" <?= $cat_id == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php } ?>
            </select>
        </div>

        <div>
            <label style="display:block; font-size:12px; color:var(--muted); margin-bottom:6px;">Condition</label>
            <select name="cond" style="width:100%; padding:10px; background:var(--bg); border:1px solid var(--border); border-radius:8px; color:white;">
                <option value="">Any Condition</option>
                <option value="Brand New" <?= $cond === 'Brand New' ? 'selected' : '' ?>>Brand New</option>
                <option value="Like New" <?= $cond === 'Like New' ? 'selected' : '' ?>>Like New</option>
                <option value="Gently Used" <?= $cond === 'Gently Used' ? 'selected' : '' ?>>Gently Used</option>
                <option value="Well Used" <?= $cond === 'Well Used' ? 'selected' : '' ?>>Well Used</option>
            </select>
        </div>

        <div>
            <label style="display:block; font-size:12px; color:var(--muted); margin-bottom:6px;">Sort By</label>
            <select name="sort" style="width:100%; padding:10px; background:var(--bg); border:1px solid var(--border); border-radius:8px; color:white;">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
            </select>
        </div>

        <button type="submit" style="padding:11px 20px; background:var(--blue); color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Filter</button>
    </form>

    <!-- Product Grid -->
    <?php if (count($products) === 0) { ?>
        <div style="text-align:center; padding:60px; background:var(--surface); border:1px solid var(--border); border-radius:14px;">
            <h3>🔍 No thrift items found</h3>
            <p style="color:var(--muted); margin-top:8px;">Try adjusting your search query or filter options.</p>
        </div>
    <?php } else { ?>
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
                            <a href="product-detail.php?id=<?= $p['id'] ?>" style="color:var(--blue); font-weight:700;">View & Buy &rarr;</a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>

<footer class="footer">
    <div class="container">
        ThriftHub &copy; <?= date('Y') ?> - BCA TU 4th Sem Project.
    </div>
</footer>

</body>
</html>
