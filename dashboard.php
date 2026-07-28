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
$stmt = $conn->prepare("SELECT full_name, username, email, balance, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$full_name = $user['full_name'];
$balance   = floatval($user['balance']);
$username  = $user['username'];
$email     = $user['email'];
$role      = $user['role'];

// Fetch User Transactions
$txns = [];
$t_stmt = $conn->prepare("SELECT txn_ref, description, amount, type, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
$t_stmt->bind_param("i", $user_id);
$t_stmt->execute();
$t_res = $t_stmt->get_result();
while ($row = $t_res->fetch_assoc()) {
    $txns[] = $row;
}

// Fetch Marketplace Products (from other sellers)
$products = [];
$p_stmt = $conn->prepare("SELECT p.*, c.name as category_name, u.full_name as seller_name FROM products p JOIN categories c ON p.category_id = c.id JOIN users u ON p.seller_id = u.id WHERE p.stock > 0 AND p.seller_id != ? AND p.status = 'active' ORDER BY p.id DESC");
$p_stmt->bind_param("i", $user_id);
$p_stmt->execute();
$p_res = $p_stmt->get_result();
while ($row = $p_res->fetch_assoc()) {
    $products[] = $row;
}

// Fetch User's Own Listed Products
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
$c_res = $conn->query("SELECT * FROM categories ORDER BY name ASC");
while ($row = $c_res->fetch_assoc()) {
    $categories[] = $row;
}

// Handle Notification Alerts
$alert = '';
$atype = '';

if (isset($_GET['ok'])) {
    $ref = htmlspecialchars($_GET['ref'] ?? '');
    if ($page === 'withdraw') {
        $alert = "Withdrawal request successful! Reference: #$ref";
        $atype = 'success';
    } else {
        $alert = "Order placed successfully! Reference: #$ref";
        $atype = 'success';
    }
}

if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    if ($msg === 'welcome') { $alert = "Welcome to ThriftHub! We've credited Rs. 5,000 demo wallet balance to get you started."; $atype = 'success'; }
    elseif ($msg === 'added') { $alert = "Your thrift item has been listed for sale!"; $atype = 'success'; }
    elseif ($msg === 'deleted') { $alert = "Item removed from listings."; $atype = 'success'; }
    elseif ($msg === 'deposited') { $alert = "Demo wallet top-up added successfully!"; $atype = 'success'; }
}

if (isset($_GET['err'])) {
    $err = $_GET['err'];
    if ($err === 'insufficient') $alert = 'Insufficient wallet balance.';
    elseif ($err === 'invalid_amount') $alert = 'Please enter a valid amount.';
    elseif ($err === 'no_product') $alert = 'Please select a valid item.';
    elseif ($err === 'not_found') $alert = 'Item not found.';
    elseif ($err === 'own_item') $alert = 'You cannot buy your own listing.';
    elseif ($err === 'sold_out') $alert = 'This item is already sold out.';
    else $alert = 'An error occurred. Please try again.';
    $atype = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ThriftHub Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>

<!-- Sidebar Navigation -->
<aside class="sidebar">
    <a href="index.php" class="sidebar-logo">🧥 ThriftHub</a>

    <a href="dashboard.php?page=dashboard" class="nav-item <?= $page == 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a>
    <a href="dashboard.php?page=products" class="nav-item <?= $page == 'products' ? 'active' : '' ?>">🛍️ Browse Items</a>
    <a href="dashboard.php?page=add_product" class="nav-item <?= $page == 'add_product' ? 'active' : '' ?>">➕ Sell Thrift Item</a>
    <a href="dashboard.php?page=my_listings" class="nav-item <?= $page == 'my_listings' ? 'active' : '' ?>">📦 My Listings (<?= count($my_products) ?>)</a>
    <a href="dashboard.php?page=withdraw" class="nav-item <?= $page == 'withdraw' ? 'active' : '' ?>">💵 Wallet & Withdraw</a>
    <a href="dashboard.php?page=history" class="nav-item <?= $page == 'history' ? 'active' : '' ?>">📋 Transaction History</a>
    <a href="dashboard.php?page=account" class="nav-item <?= $page == 'account' ? 'active' : '' ?>">👤 My Profile</a>

    <?php if ($role === 'admin') { ?>
        <a href="admin.php" class="nav-item" style="color:var(--blue); border:1px dashed var(--blue); margin-top:10px;">⚙️ Admin Panel</a>
    <?php } ?>

    <div class="sidebar-footer">
        <div class="user-chip">
            <div class="avatar"><?= strtoupper(substr($full_name, 0, 1)) ?></div>
            <div style="overflow:hidden;">
                <strong style="font-size:14px; color:white;"><?= htmlspecialchars($full_name) ?></strong><br>
                <small style="color:var(--muted);">@<?= htmlspecialchars($username) ?></small>
            </div>
        </div>
        <a href="logout.php" class="nav-item logout">🚪 Logout</a>
    </div>
</aside>

<!-- Main Dashboard Content -->
<main class="content">

    <?php if (!empty($alert)) { ?>
        <div class="alert alert-<?= $atype ?>">
            <span><?= $atype === 'success' ? '✅' : '⚠️' ?></span>
            <div><?= htmlspecialchars($alert) ?></div>
        </div>
    <?php } ?>

    <?php if ($page == 'dashboard') { ?>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <div>
                <h1>Welcome back, <?= htmlspecialchars(explode(' ', $full_name)[0]) ?> 👋</h1>
                <p class="muted">Here is an overview of your thrift wallet, sales and recent activity.</p>
            </div>
            <a href="dashboard.php?page=add_product" class="btn btn-green">+ List New Item</a>
        </div>

        <div class="grid3">
            <div class="card">
                <div class="card-label">Available Wallet Balance</div>
                <div class="amount-lg" style="color:#3fb950;">Rs. <?= number_format($balance, 2) ?></div>
                <form method="POST" action="action.php" style="margin-top:12px;">
                    <input type="hidden" name="action" value="deposit">
                    <input type="hidden" name="amount" value="1000">
                    <button type="submit" class="btn btn-outline" style="padding:6px 12px; font-size:12px; width:100%;">+ Top Up Rs. 1,000 (Demo)</button>
                </form>
            </div>

            <div class="card">
                <div class="card-label">My Thrift Listings</div>
                <div class="amount-lg"><?= count($my_products) ?></div>
                <div class="muted" style="margin-top:6px;">Items you're currently selling</div>
            </div>

            <div class="card">
                <div class="card-label">Account Status</div>
                <div style="margin-top:8px;">
                    <span class="badge">✔ Verified Member</span>
                    <?php if ($role === 'admin') { ?><span class="badge badge-amber" style="margin-left:4px;">Admin</span><?php } ?>
                </div>
                <div class="muted" style="margin-top:8px;">Role: <?= ucfirst($role) ?></div>
            </div>
        </div>

        <div class="card">
            <h2>Recent Wallet Activity</h2>
            <?php if (count($txns) == 0) { ?>
                <p class="muted">No transactions yet.</p>
            <?php } else { ?>
                <table>
                    <thead>
                        <tr>
                            <th>REF ID</th>
                            <th>DESCRIPTION</th>
                            <th>DATE</th>
                            <th>STATUS</th>
                            <th>AMOUNT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent = array_slice($txns, 0, 5);
                        foreach ($recent as $t) {
                        ?>
                        <tr>
                            <td class="muted">#<?= htmlspecialchars($t['txn_ref']) ?></td>
                            <td><?= htmlspecialchars($t['description']) ?></td>
                            <td class="muted"><?= date('M d, Y h:i A', strtotime($t['created_at'])) ?></td>
                            <td><span class="badge">Completed</span></td>
                            <td class="<?= $t['type'] == 'credit' ? 'pos' : 'neg' ?>">
                                <?= $t['type'] == 'credit' ? '+' : '-' ?>Rs. <?= number_format($t['amount'], 2) ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>

    <?php } elseif ($page == 'products') { ?>

        <h1>🛍️ Browse Available Thrift Items</h1>
        <p class="muted" style="margin-bottom:20px;">Available Wallet Balance: <strong style="color:#3fb950;">Rs. <?= number_format($balance, 2) ?></strong></p>

        <?php if (count($products) == 0) { ?>
            <div class="card"><p class="muted">No items available right now. Check back soon!</p></div>
        <?php } else { ?>
            <div class="product-grid">
                <?php foreach ($products as $p) {
                    $img = !empty($p['image']) && file_exists(__DIR__ . '/uploads/' . $p['image']) ? 'uploads/' . $p['image'] : 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500';
                ?>
                <div class="product-card">
                    <img src="<?= $img ?>" class="product-img" alt="<?= htmlspecialchars($p['name']) ?>" onerror="this.src='https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500';">
                    <div class="product-body">
                        <div style="font-size:11px; color:var(--blue); font-weight:600; text-transform:uppercase; margin-bottom:4px;"><?= htmlspecialchars($p['category_name']) ?></div>
                        <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="product-price">Rs. <?= number_format($p['price'], 2) ?></div>
                        <div class="product-meta">
                            <span>Condition: <?= htmlspecialchars($p['condition_status']) ?></span>
                            <span>Seller: @<?= htmlspecialchars($p['seller_name']) ?></span>
                        </div>
                        <form method="POST" action="action.php" style="margin-top:auto;">
                            <input type="hidden" name="action" value="purchase">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-green btn-full">⚡ Buy Now</button>
                        </form>
                    </div>
                </div>
                <?php } ?>
            </div>
        <?php } ?>

    <?php } elseif ($page == 'add_product') { ?>

        <h1>➕ List Thrift Item for Sale</h1>
        <p class="muted" style="margin-bottom:24px;">Post your pre-loved item to the ThriftHub marketplace.</p>

        <div class="card" style="max-width:600px;">
            <form method="POST" action="action.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_product">

                <div class="form-group">
                    <label class="form-label">Item Title / Name *</label>
                    <input class="form-input" type="text" name="name" placeholder="e.g. Vintage Leather Jacket" required>
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
                        <label class="form-label">Condition *</label>
                        <select class="form-select" name="condition_status" required>
                            <option value="Brand New">Brand New</option>
                            <option value="Like New">Like New</option>
                            <option value="Gently Used" selected>Gently Used</option>
                            <option value="Well Used">Well Used</option>
                        </select>
                    </div>
                </div>

                <div class="grid2">
                    <div class="form-group">
                        <label class="form-label">Selling Price (Rs.) *</label>
                        <input class="form-input" type="number" step="0.01" name="price" placeholder="e.g. 1500" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Quantity / Stock *</label>
                        <input class="form-input" type="number" name="stock" value="1" min="1" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Product Image</label>
                    <input class="form-input" type="file" name="image" accept="image/*">
                    <small class="muted">Upload a photo of your item (JPG, PNG, WEBP)</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Item Description</label>
                    <textarea class="form-textarea" name="description" placeholder="Describe condition, size, brand, defects or features..."></textarea>
                </div>

                <button type="submit" class="btn btn-green btn-full" style="padding:14px;">Publish Listing</button>
            </form>
        </div>

    <?php } elseif ($page == 'my_listings') { ?>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <h1>📦 My Listed Thrift Items</h1>
            <a href="dashboard.php?page=add_product" class="btn btn-green">+ List New Item</a>
        </div>

        <div class="card">
            <?php if (count($my_products) == 0) { ?>
                <p class="muted">You haven't listed any items yet. <a href="dashboard.php?page=add_product" style="color:var(--blue);">Start selling now &rarr;</a></p>
            <?php } else { ?>
                <table>
                    <thead>
                        <tr>
                            <th>ITEM NAME</th>
                            <th>CATEGORY</th>
                            <th>PRICE</th>
                            <th>CONDITION</th>
                            <th>STATUS</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_products as $p) { ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                            <td class="muted"><?= htmlspecialchars($p['category_name']) ?></td>
                            <td style="color:#3fb950; font-weight:700;">Rs. <?= number_format($p['price'], 2) ?></td>
                            <td><span class="badge badge-amber"><?= htmlspecialchars($p['condition_status']) ?></span></td>
                            <td>
                                <?php if ($p['status'] === 'active') { ?>
                                    <span class="badge">Active</span>
                                <?php } else { ?>
                                    <span class="badge" style="background:rgba(248,81,73,0.15); color:var(--red); border-color:rgba(248,81,73,0.3);">Sold Out</span>
                                <?php } ?>
                            </td>
                            <td>
                                <a href="action.php?action=delete_product&id=<?= $p['id'] ?>" onclick="return confirm('Are you sure you want to remove this item?');" class="btn btn-red" style="padding:4px 10px; font-size:12px;">Delete</a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>

    <?php } elseif ($page == 'withdraw') { ?>

        <h1>💵 Wallet Earnings & Withdrawal</h1>

        <div class="grid2">
            <div class="card">
                <h2>Withdrawal Request</h2>
                <p class="muted" style="margin-bottom:20px;">Transfer earnings to your eSewa, Khalti, or Bank account.</p>

                <div style="background:rgba(88,166,255,0.1); border:1px solid rgba(88,166,255,0.2); padding:16px; border-radius:10px; margin-bottom:20px;">
                    <div style="font-size:12px; color:var(--muted); text-transform:uppercase;">Available Balance</div>
                    <div style="font-size:28px; font-weight:800; color:#3fb950;">Rs. <?= number_format($balance, 2) ?></div>
                </div>

                <form method="POST" action="action.php">
                    <input type="hidden" name="action" value="withdraw">
                    <div class="form-group">
                        <label class="form-label">Withdrawal Amount (Rs.)</label>
                        <input class="form-input" type="number" step="0.01" name="amount" placeholder="e.g. 1000" min="1" max="<?= $balance ?>" required>
                    </div>

                    <button type="submit" class="btn btn-red btn-full" <?= $balance <= 0 ? 'disabled style="opacity:0.5;"' : '' ?>>
                        Confirm Withdrawal Request
                    </button>
                </form>
            </div>

            <div class="card">
                <h2>Demo Top-Up (Testing Tool)</h2>
                <p class="muted" style="margin-bottom:20px;">Add demo balance to test buyer purchases during your BCA project demo.</p>

                <form method="POST" action="action.php">
                    <input type="hidden" name="action" value="deposit">
                    <div class="form-group">
                        <label class="form-label">Top-Up Amount (Rs.)</label>
                        <input class="form-input" type="number" name="amount" value="2000" min="100" required>
                    </div>

                    <button type="submit" class="btn btn-green btn-full">
                        + Add Demo Funds to Wallet
                    </button>
                </form>
            </div>
        </div>

    <?php } elseif ($page == 'history') { ?>

        <h1>📋 Full Transaction History</h1>

        <div class="card">
            <?php if (count($txns) == 0) { ?>
                <p class="muted">No transactions found.</p>
            <?php } else { ?>
                <table>
                    <thead>
                        <tr>
                            <th>REF ID</th>
                            <th>DESCRIPTION</th>
                            <th>DATE & TIME</th>
                            <th>TYPE</th>
                            <th>STATUS</th>
                            <th>AMOUNT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($txns as $t) { ?>
                        <tr>
                            <td class="muted">#<?= htmlspecialchars($t['txn_ref']) ?></td>
                            <td><?= htmlspecialchars($t['description']) ?></td>
                            <td class="muted"><?= date('Y-m-d H:i:s', strtotime($t['created_at'])) ?></td>
                            <td class="muted"><?= ucfirst($t['type']) ?></td>
                            <td><span class="badge">Completed</span></td>
                            <td class="<?= $t['type'] == 'credit' ? 'pos' : 'neg' ?>">
                                <?= $t['type'] == 'credit' ? '+' : '-' ?>Rs. <?= number_format($t['amount'], 2) ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>

    <?php } elseif ($page == 'account') { ?>

        <h1>👤 My Profile & Account Details</h1>

        <div class="grid2">
            <div class="card">
                <h2>User Profile</h2>
                <table style="margin-top:12px;">
                    <tr>
                        <td class="muted" style="border:none; width:140px; padding:10px 0;">Full Name</td>
                        <td style="border:none;"><strong><?= htmlspecialchars($full_name) ?></strong></td>
                    </tr>
                    <tr>
                        <td class="muted" style="border:none; padding:10px 0;">Username</td>
                        <td style="border:none;">@<?= htmlspecialchars($username) ?></td>
                    </tr>
                    <tr>
                        <td class="muted" style="border:none; padding:10px 0;">Email</td>
                        <td style="border:none;"><?= htmlspecialchars($email) ?></td>
                    </tr>
                    <tr>
                        <td class="muted" style="border:none; padding:10px 0;">Account Role</td>
                        <td style="border:none;"><span class="badge"><?= ucfirst($role) ?></span></td>
                    </tr>
                    <tr>
                        <td class="muted" style="border:none; padding:10px 0;">Wallet Balance</td>
                        <td style="border:none; color:#3fb950; font-weight:700;">Rs. <?= number_format($balance, 2) ?></td>
                    </tr>
                </table>
            </div>

            <div class="card">
                <h2>Quick Overview</h2>
                <p class="muted">Listed Items: <strong><?= count($my_products) ?></strong></p>
                <p class="muted" style="margin-top:8px;">Total Transactions Logged: <strong><?= count($txns) ?></strong></p>
                <div style="margin-top:20px;">
                    <a href="logout.php" class="btn btn-red btn-full">Sign Out</a>
                </div>
            </div>
        </div>

    <?php } ?>

</main>

</body>
</html>