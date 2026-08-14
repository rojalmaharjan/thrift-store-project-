<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

$user_id = $_SESSION['user_id'];
$page = $_GET['page'] ?? 'dashboard';

// Fetch User Data
$stmt = $conn->prepare("SELECT full_name, username, email, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$full_name  = $user['full_name'];
$username   = $user['username'];
$email      = $user['email'];
$role       = $user['role'];
$created_at = $user['created_at'];

// Fetch User Orders / Purchases
$my_orders = [];
$o_stmt = $conn->prepare("SELECT o.*, p.name as product_name, p.image as product_image, s.full_name as seller_name, s.username as seller_username FROM orders o JOIN products p ON o.product_id = p.id JOIN users s ON o.seller_id = s.id WHERE o.buyer_id = ? ORDER BY o.created_at DESC");
$o_stmt->bind_param("i", $user_id);
$o_stmt->execute();
$o_res = $o_stmt->get_result();
while ($row = $o_res->fetch_assoc()) {
    $my_orders[] = $row;
}

// Fetch User Sales (Orders where user is seller)
$my_sales = [];
$s_stmt = $conn->prepare("SELECT o.*, p.name as product_name, b.full_name as buyer_name, b.username as buyer_username FROM orders o JOIN products p ON o.product_id = p.id JOIN users b ON o.buyer_id = b.id WHERE o.seller_id = ? ORDER BY o.created_at DESC");
$s_stmt->bind_param("i", $user_id);
$s_stmt->execute();
$s_res = $s_stmt->get_result();
while ($row = $s_res->fetch_assoc()) {
    $my_sales[] = $row;
}

// Fetch Marketplace Apparel Items (from other sellers) by Category ID
$cat_id = $_GET['cat_id'] ?? null;
if ($cat_id) {
    $p_stmt = $conn->prepare("SELECT p.*, c.name as category_name, u.full_name as seller_name, u.username as seller_username FROM products p JOIN categories c ON p.category_id = c.id JOIN users u ON p.seller_id = u.id WHERE p.stock > 0 AND p.seller_id != ? AND p.status = 'active' AND c.id = ? ORDER BY p.id DESC");
    $p_stmt->bind_param("ii", $user_id, $cat_id);
} else {
    $p_stmt = $conn->prepare("SELECT p.*, c.name as category_name, u.full_name as seller_name, u.username as seller_username FROM products p JOIN categories c ON p.category_id = c.id JOIN users u ON p.seller_id = u.id WHERE p.stock > 0 AND p.seller_id != ? AND p.status = 'active' ORDER BY p.id DESC");
    $p_stmt->bind_param("i", $user_id);
}
$p_stmt->execute();
$products = [];
$p_res = $p_stmt->get_result();
while ($row = $p_res->fetch_assoc()) {
    $products[] = $row;
}

// Fetch User's Own Listed Wardrobe Items
$my_products = [];
$mp_stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.seller_id = ? ORDER BY p.id DESC");
$mp_stmt->bind_param("i", $user_id);
$mp_stmt->execute();
$mp_res = $mp_stmt->get_result();
while ($row = $mp_res->fetch_assoc()) {
    $my_products[] = $row;
}

// Fetch Categories for product upload form
$categories = [];
$c_res = $conn->query("SELECT * FROM categories ORDER BY id ASC");
while ($row = $c_res->fetch_assoc()) {
    $categories[] = $row;
}

// Dashboard metrics
$active_listings = 0;
$sold_listings = 0;
foreach ($my_products as $p) {
    if ($p['status'] === 'active') { $active_listings++; } else { $sold_listings++; }
}

$purchases_count = count($my_orders);
$sales_count     = count($my_sales);
$eco_score       = $active_listings + $sold_listings + $purchases_count;

// Handle Notification Alerts
$alert = '';
$atype = '';

if (isset($_GET['ok'])) {
    $ref = htmlspecialchars($_GET['ref'] ?? '');
    $alert = "Streetwear item acquired! Order Reference: #$ref.";
    $atype = 'success';
}

if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    if ($msg === 'welcome') { $alert = "Welcome to ThriftHub. Start listing your vintage streetwear or browsing drops."; $atype = 'success'; }
    elseif ($msg === 'added') { $alert = "Your vintage garment drop is live!"; $atype = 'success'; }
    elseif ($msg === 'deleted') { $alert = "Garment listing removed."; $atype = 'success'; }
}

if (isset($_GET['err'])) {
    $err = $_GET['err'];
    if ($err === 'no_product') $alert = 'Please select a valid clothing item.';
    elseif ($err === 'not_found') $alert = 'Item not found.';
    elseif ($err === 'own_item') $alert = 'You cannot purchase your own drop.';
    elseif ($err === 'sold_out') $alert = 'This drop is sold out.';
    else $alert = 'An error occurred. Please try again.';
    $atype = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ThriftHub // Urban Streetwear & Vintage Closet</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>

<!-- Sidebar Navigation -->
<aside class="sidebar">
    <a href="index.php" class="sidebar-logo">
        Thrift<em>HUB</em>
    </a>

    <a href="dashboard.php?page=dashboard" class="nav-item <?= $page == 'dashboard' ? 'active' : '' ?>"> Dashboard </a>
    <a href="dashboard.php?page=products" class="nav-item <?= $page == 'products' ? 'active' : '' ?>">Marketplace</a>
    <a href="dashboard.php?page=add_product" class="nav-item <?= $page == 'add_product' ? 'active' : '' ?>">Listing</a>
    <a href="dashboard.php?page=my_listings" class="nav-item <?= $page == 'my_listings' ? 'active' : '' ?>">My listing (<?= count($my_products) ?>)</a>
    <a href="dashboard.php?page=history" class="nav-item <?= $page == 'history' ? 'active' : '' ?>"> ORDER LOG</a>
    <a href="dashboard.php?page=account" class="nav-item <?= $page == 'account' ? 'active' : '' ?>"> My PROFILE</a>

    <?php if ($role === 'admin') { ?>
        <a href="admin.php" class="nav-item" style="color:var(--crimson); border:1px dashed var(--crimson); margin-top:14px;">⚙️ ADMIN SYSTEM</a>
    <?php } ?>

    <div class="sidebar-footer">
        <div class="user-chip">
            <div class="avatar"><?= strtoupper(substr($full_name, 0, 1)) ?></div>
            <div style="overflow:hidden;">
                <strong style="font-size:14px; color:var(--white);"><?= htmlspecialchars($full_name) ?></strong><br>
                <small style="color:var(--muted);">@<?= htmlspecialchars($username) ?></small>
            </div>
        </div>
        <a href="logout.php" class="nav-item logout">🚪 SIGN OUT</a>
    </div>
</aside>

<!-- Main Dashboard Content -->
<main class="content">

    <?php if (!empty($alert)) { ?>
        <div class="alert alert-<?= $atype ?>">
            <span><?= $atype === 'success' ? 'Go' : 'Error' ?></span>
            <div><?= htmlspecialchars($alert) ?></div>
        </div>
    <?php } ?>

    <?php if ($page == 'dashboard') { ?>

        <div class="card">
            <h2 style="margin-bottom:16px;">Dashboard</h2>
            <div class="actions-row">
                <a href="dashboard.php?page=add_product" class="btn btn-crimson"> LIST GARMENT</a>
                <a href="dashboard.php?page=products" class="btn btn-outline">  SHOP MARKETPLACE</a>
                <a href="dashboard.php?page=my_listings" class="btn btn-outline">  MANAGE WARDROBE</a>
                <a href="dashboard.php?page=history" class="btn btn-outline">  ORDER LOG</a>
            </div>
        </div>

        <div class="card" style="padding:0; overflow:hidden; border:1px solid var(--border);">
            <div class="hero-banner-wrap">
                <img src="th2.png" alt="Streetwear Fashion Vibe">
                <div class="hero-banner-overlay">
                    <div></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
                <h2 style="margin:0;">RECENT DROPS & ORDER ACTIVITY</h2>
                <a href="dashboard.php?page=history" style="color:var(--crimson); font-weight:800; font-size:12px; text-decoration:none; letter-spacing:1px; text-transform:uppercase;">VIEW ALL ACTIVITY &rarr;</a>
            </div>
            <?php 
            $recent_activity = array_merge(
                array_map(function($o){ $o['act_type'] = 'Bought'; return $o; }, $my_orders),
                array_map(function($s){ $s['act_type'] = 'Sold'; return $s; }, $my_sales)
            );
            usort($recent_activity, function($a, $b){ return strtotime($b['created_at']) - strtotime($a['created_at']); });
            $recent_activity = array_slice($recent_activity, 0, 5);

            if (count($recent_activity) == 0) { ?>
                <p class="muted">No streetwear activity logged yet. Your purchases and clothing sales will appear here.</p>
            <?php } else { ?>
                <table>
                    <thead>
                        <tr>
                            <th>ORDER REF</th>
                            <th>GARMENT ITEM</th>
                            <th>TYPE</th>
                            <th>DATE</th>
                            <th>STATUS</th>
                            <th>PRICE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_activity as $act) { ?>
                        <tr>
                            <td class="muted">#<?= htmlspecialchars($act['order_ref']) ?></td>
                            <td><strong style="color:var(--white);"><?= htmlspecialchars($act['product_name']) ?></strong></td>
                            <td>
                                <?php if ($act['act_type'] === 'Bought') { ?>
                                    <span class="badge badge-purple">ACQUIRED</span>
                                <?php } else { ?>
                                    <span class="badge badge-amber">🏷️ SOLD DROP</span>
                                <?php } ?>
                            </td>
                            <td class="muted"><?= date('M d, Y h:i A', strtotime($act['created_at'])) ?></td>
                            <td><span class="badge">COMPLETED</span></td>
                            <td style="color:var(--cream); font-weight:800; font-family:'Outfit', sans-serif;">Rs. <?= number_format($act['amount'], 2) ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>

    <?php } elseif ($page == 'products') { ?>

        <div style="margin-bottom:28px;">
            <h1> URBAN STREETWEAR DROPS</h1>
            <p class="muted">Authentic pre-loved vintage, oversized hoodies, denim, and sneakers for the bold.</p>
        </div>

        <!-- Clothing Filter Tags using actual database Category IDs -->
        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:28px;">
            <a href="dashboard.php?page=products" class="btn <?= !$cat_id ? 'btn-crimson' : 'btn-outline' ?>" style="padding:8px 16px; font-size:12px;">ALL APPAREL</a>
            <a href="dashboard.php?page=products&cat_id=1" class="btn <?= $cat_id == 1 ? 'btn-crimson' : 'btn-outline' ?>" style="padding:8px 16px; font-size:12px;">🧥 CLOTHING & OUTERWEAR</a>
            <a href="dashboard.php?page=products&cat_id=2" class="btn <?= $cat_id == 2 ? 'btn-crimson' : 'btn-outline' ?>" style="padding:8px 16px; font-size:12px;">👟 FOOTWEAR & SNEAKERS</a>
        </div>

        <?php if (count($products) == 0) { ?>
            <div class="card"><p class="muted">No drops found in this category right now. List your vintage clothes!</p></div>
        <?php } else { ?>
            <div class="product-grid">
                <?php foreach ($products as $p) {
                    // Define 3 simple images matched to your exact database categories
                    $category_images = [
                        'Clothing & Outerwear' => [
                            'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=600',
                            'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=600',
                            'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=600'
                        ],
                        'Footwear & Sneakers' => [
                            'https://images.unsplash.com/photo-1542272604-787c3835535d?w=600',
                            'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=600',
                            'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=600'
                        ]
                    ];

                    $cat_name = $p['category_name'];
                    $images_for_cat = isset($category_images[$cat_name]) ? $category_images[$cat_name] : [
                        'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=600',
                        'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=600',
                        'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=600'
                    ];
                    $assigned_image = $images_for_cat[$p['id'] % count($images_for_cat)];

                    $img = !empty($p['image']) && file_exists(__DIR__ . '/uploads/' . $p['image']) 
                        ? 'uploads/' . $p['image'] 
                        : $assigned_image;
                ?>
                <div class="product-card">
                    <div class="product-img-wrap">
                        <img src="<?= $img ?>" class="product-img" alt="<?= htmlspecialchars($p['name']) ?>" onerror="this.src='https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=600';">
                        <span class="condition-pill"><?= htmlspecialchars($p['condition_status']) ?></span>
                        <span class="size-pill">STREETWEAR</span>
                    </div>
                    <div class="product-body">
                        <div class="product-category"><?= htmlspecialchars($p['category_name']) ?></div>
                        <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                        
                        <div class="product-price-row">
                            <div class="product-price">Rs. <?= number_format($p['price'], 2) ?></div>
                        </div>

                        <div class="product-seller">
                            <span>👤 CURATOR:</span>
                            <strong style="color:var(--white);">@<?= htmlspecialchars($p['seller_username']) ?></strong>
                        </div>

                        <!-- Purchase Form without Optional Text -->
                        <form method="POST" action="action.php" style="margin-top:auto; display:flex; flex-direction:column; gap:8px;">
                            <input type="hidden" name="action" value="purchase">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            
                            <input type="text" name="delivery_address" class="form-input" placeholder="Delivery Address" style="font-size:12px; padding:8px;">
                            <input type="text" name="phone_number" class="form-input" placeholder="Contact Phone Number" style="font-size:12px; padding:8px;">

                            <button type="submit" class="btn btn-crimson btn-full"> ACQUIRE DROP</button>
                        </form>
                    </div>
                </div>
                <?php } ?>
            </div>
        <?php } ?>

    <?php } elseif ($page == 'add_product') { ?>

        <div style="margin-bottom:28px;">
            <h1> LIST GARMENT FOR SALE</h1>
            <p class="muted">Post your pre-loved streetwear or vintage apparel to the community.</p>
        </div>

        <div class="card" style="max-width:680px;">
            <form method="POST" action="action.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_product">

                <div class="form-group">
                    <label class="form-label">Garment Title / Item Name *</label>
                    <input class="form-input" type="text" name="name" placeholder="e.g. Vintage Oversized Crimson Hoodie">
                </div>

                <div class="grid2">
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select class="form-select" name="category_id" required>
                            <?php foreach ($categories as $c) { ?>
                                <option value="<?= $c['id'] ?>"><?= $c['icon'] ?> <?= htmlspecialchars($c['name']) ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Garment Condition *</label>
                        <select class="form-select" name="condition_status" required>
                            <option value="Brand New">Brand New with Tags</option>
                            <option value="Like New">Like New (Worn 1-2 times)</option>
                            <option value="Gently Used" selected>Gently Used (Good Vintage)</option>
                            <option value="Well Used">Well Used (Streetwear Distressed)</option>
                        </select>
                    </div>
                </div>

                <div class="grid2">
                    <div class="form-group">
                        <label class="form-label">Drop Price (Rs.) *</label>
                        <input class="form-input" type="number" step="0.01" name="price" placeholder="e.g. 2400">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Available Stock *</label>
                        <input class="form-input" type="number" name="stock" value="1" min="1" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Apparel Photo Upload</label>
                    <input class="form-input" type="file" name="image" id="apparel_image" accept="image/*" onchange="previewImage(event)">
                    <small class="muted" style="margin-top:6px; display:block;">Upload a clear photo of the garment (JPG, PNG, WEBP)</small>
                    <div id="image_preview_box" style="display:none; margin-top:14px;">
                        <img id="image_preview" src="" alt="Preview" style="max-height:180px; border-radius:12px; border:1px solid var(--crimson);">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Garment Description & Fit Details</label>
                    <textarea class="form-textarea" name="description" placeholder="Specify fit size (S, M, L, XL, OS), brand (Nike, Stussy, Thrifted), fabric weight, condition notes or flaws..."></textarea>
                </div>

                <button type="submit" class="btn btn-crimson btn-full" style="padding:15px;">🚀 PUBLISH STREETWEAR DROP</button>
            </form>
        </div>

        <script>
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function() {
                var output = document.getElementById('image_preview');
                output.src = reader.result;
                document.getElementById('image_preview_box').style.display = 'block';
            };
            if(event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            }
        }
        </script>

    <?php } elseif ($page == 'my_listings') { ?>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:28px;">
            <div>
                <h1>📦 MY WARDROBE DROPS</h1>
                <p class="muted">Manage the clothing items you've posted for sale.</p>
            </div>
            <a href="dashboard.php?page=add_product" class="btn btn-crimson">+ LIST NEW DROP</a>
        </div>

        <div class="card">
            <?php if (count($my_products) == 0) { ?>
                <p class="muted">Your wardrobe is empty! <a href="dashboard.php?page=add_product" style="color:var(--crimson); font-weight:800;">List your first drop now &rarr;</a></p>
            <?php } else { ?>
                <table>
                    <thead>
                        <tr>
                            <th>GARMENT ITEM</th>
                            <th>CATEGORY</th>
                            <th>PRICE</th>
                            <th>CONDITION</th>
                            <th>STATUS</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_products as $p) {
                            $category_images = [
                                'Clothing & Outerwear' => ['https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=200'],
                                'Footwear & Sneakers' => ['https://images.unsplash.com/photo-1542272604-787c3835535d?w=200']
                            ];
                            $cat_name = $p['category_name'];
                            $images_for_cat = isset($category_images[$cat_name]) ? $category_images[$cat_name] : ['https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=200'];
                            $assigned_image = $images_for_cat[$p['id'] % count($images_for_cat)];
                            
                            $img = !empty($p['image']) && file_exists(__DIR__ . '/uploads/' . $p['image']) ? 'uploads/' . $p['image'] : $assigned_image;
                        ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:14px;">
                                    <img src="<?= $img ?>" style="width:44px; height:44px; object-fit:cover; border-radius:10px; border:1px solid var(--border);" onerror="this.src='https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=200';">
                                    <strong style="color:var(--white);"><?= htmlspecialchars($p['name']) ?></strong>
                                </div>
                            </td>
                            <td class="muted"><?= htmlspecialchars($p['category_name']) ?></td>
                            <td style="color:var(--cream); font-weight:800; font-family:'Outfit', sans-serif;">Rs. <?= number_format($p['price'], 2) ?></td>
                            <td><span class="badge badge-amber"><?= htmlspecialchars($p['condition_status']) ?></span></td>
                            <td>
                                <?php if ($p['status'] === 'active') { ?>
                                    <span class="badge">ACTIVE DROP</span>
                                <?php } else { ?>
                                    <span class="badge badge-amber">SOLD OUT</span>
                                <?php } ?>
                            </td>
                            <td>
                                <a href="action.php?action=delete_product&id=<?= $p['id'] ?>" onclick="return confirm('Are you sure you want to remove this garment?');" class="btn btn-red" style="padding:5px 12px; font-size:11px;">DELETE</a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>

    <?php } elseif ($page == 'history') { ?>

        <div style="margin-bottom:28px;">
            <h1>🛒 ORDER & SALES LOG</h1>
            <p class="muted">Complete record of clothing items you have acquired or sold.</p>
        </div>

        <div class="card">
            <?php 
            $all_activity = array_merge(
                array_map(function($o){ $o['act_type'] = 'Bought'; return $o; }, $my_orders),
                array_map(function($s){ $s['act_type'] = 'Sold'; return $s; }, $my_sales)
            );
            usort($all_activity, function($a, $b){ return strtotime($b['created_at']) - strtotime($a['created_at']); });

            if (count($all_activity) == 0) { ?>
                <p class="muted">No order activity logged yet.</p>
            <?php } else { ?>
                <table>
                    <thead>
                        <tr>
                            <th>ORDER REF</th>
                            <th>GARMENT ITEM</th>
                            <th>TYPE</th>
                            <th>PARTY</th>
                            <th>DATE</th>
                            <th>STATUS</th>
                            <th>PRICE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_activity as $act) { ?>
                        <tr>
                            <td class="muted">#<?= htmlspecialchars($act['order_ref']) ?></td>
                            <td><strong style="color:var(--white);"><?= htmlspecialchars($act['product_name']) ?></strong></td>
                            <td>
                                <?php if ($act['act_type'] === 'Bought') { ?>
                                    <span class="badge badge-purple">🛍️ ACQUIRED</span>
                                <?php } else { ?>
                                    <span class="badge badge-amber">🏷️ SOLD</span>
                                <?php } ?>
                            </td>
                            <td class="muted">
                                <?= $act['act_type'] === 'Bought' ? 'Seller: @' . htmlspecialchars($act['seller_username']) : 'Buyer: @' . htmlspecialchars($act['buyer_username']) ?>
                            </td>
                            <td class="muted"><?= date('Y-m-d H:i', strtotime($act['created_at'])) ?></td>
                            <td><span class="badge">COMPLETED</span></td>
                            <td style="color:var(--cream); font-weight:800; font-family:'Outfit', sans-serif;">Rs. <?= number_format($act['amount'], 2) ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>

    <?php } elseif ($page == 'account') { ?>

        <div style="margin-bottom:28px;">
            <h1>👤 MY PROFILE</h1>
            <p class="muted">Your verified curator profile and sustainability stats.</p>
        </div>

        <div class="grid2">
            <div class="card">
                <h2>CURATOR DETAILS</h2>
                <table style="margin-top:14px;">
                    <tr>
                        <td class="muted" style="border:none; width:140px; padding:10px 0;">FULL NAME</td>
                        <td style="border:none;"><strong style="color:var(--white);"><?= htmlspecialchars($full_name) ?></strong></td>
                    </tr>
                    <tr>
                        <td class="muted" style="border:none; padding:10px 0;">USERNAME</td>
                        <td style="border:none;">@<?= htmlspecialchars($username) ?></td>
                    </tr>
                    <tr>
                        <td class="muted" style="border:none; padding:10px 0;">EMAIL</td>
                        <td style="border:none;"><?= htmlspecialchars($email) ?></td>
                    </tr>
                    <tr>
                        <td class="muted" style="border:none; padding:10px 0;">ROLE</td>
                        <td style="border:none;"><span class="badge badge-amber"><?= strtoupper(htmlspecialchars($role)) ?></span></td>
                    </tr>
                    <tr>
                        <td class="muted" style="border:none; padding:10px 0;">MEMBER SINCE</td>
                        <td style="border:none;"><?= date('F d, Y', strtotime($created_at)) ?></td>
                    </tr>
                </table>
            </div>

            <div class="card">
                <h2>WARDROBE METRICS</h2>
                <div style="margin-bottom:16px;">
                    <p class="muted">Active Wardrobe Drops: <strong style="color:var(--white);"><?= $active_listings ?></strong></p>
                    <p class="muted" style="margin-top:8px;">Garments Rehomed / Sold: <strong style="color:var(--white);"><?= $sales_count ?></strong></p>
                    <p class="muted" style="margin-top:8px;">Streetwear Purchases: <strong style="color:var(--white);"><?= $purchases_count ?></strong></p>
                    <p class="muted" style="margin-top:8px;">Sustainability Score: <strong style="color:var(--cream);"><?= $eco_score ?> Garments Recycled </strong></p>
                </div>
                <div style="margin-top:28px;">
                    <a href="logout.php" class="btn btn-red btn-full">SIGN OUT OF ACCOUNT</a>
                </div>
            </div>
        </div>

    <?php } ?>

</main>

</body>
</html>