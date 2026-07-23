<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

$user_id = $_SESSION['user_id'];
$page = $_GET['page'] ?? 'dashboard';

$result = $conn->query("SELECT full_name, balance, username FROM users WHERE id = $user_id");
$user = $result->fetch_assoc();
$full_name = $user['full_name'];
$balance = $user['balance'];
$username = $user['username'];

$txns = [];
$result = $conn->query("SELECT txn_ref, description, amount, type, created_at FROM transactions WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 50");
while ($row = $result->fetch_assoc()) {
    $txns[] = $row;
}

$products = [];
$result = $conn->query("SELECT id, name, price, stock, seller_id FROM products WHERE stock > 0 AND seller_id != $user_id ORDER BY id DESC");
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$my_products = [];
$result = $conn->query("SELECT id, name, price, stock FROM products WHERE seller_id = $user_id ORDER BY id DESC");
while ($row = $result->fetch_assoc()) {
    $my_products[] = $row;
}

$alert = '';
$atype = '';

if (isset($_GET['ok'])) {
    $ref = $_GET['ref'];
    if ($page === 'withdraw') {
        $alert = "Withdrawal successful! Reference: #$ref";
        $atype = 'success';
    } elseif ($page === 'orders') {
        $alert = "Purchase successful! Reference: #$ref";
        $atype = 'success';
    }
}

if (isset($_GET['err'])) {
    if ($_GET['err'] == 'insufficient') $alert = 'Insufficient wallet balance.';
    elseif ($_GET['err'] == 'invalid_amount') $alert = 'Please enter a valid amount.';
    elseif ($_GET['err'] == 'no_product') $alert = 'Please select an item.';
    elseif ($_GET['err'] == 'not_found') $alert = 'Item not found.';
    elseif ($_GET['err'] == 'own_item') $alert = 'You cannot buy your own listing.';
    elseif ($_GET['err'] == 'sold_out') $alert = 'This item is already sold out.';
    else $alert = 'An error occurred.';
    $atype = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ThriftHub Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    </head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">🧥 ThriftHub</div>

    <a href="dashboard.php?page=dashboard" class="nav-item <?= $page == 'dashboard' ? 'active' : '' ?>">🏠 Dashboard</a>
    <a href="dashboard.php?page=products" class="nav-item <?= $page == 'products' ? 'active' : '' ?>">🛍️ Browse Items</a>
    <a href="dashboard.php?page=withdraw" class="nav-item <?= $page == 'withdraw' ? 'active' : '' ?>">💵 Withdraw Earnings</a>
    <a href="dashboard.php?page=history" class="nav-item <?= $page == 'history' ? 'active' : '' ?>">📋 History</a>
    <a href="dashboard.php?page=account" class="nav-item <?= $page == 'account' ? 'active' : '' ?>">👤 My Account</a>

    <div class="sidebar-footer">
        <div class="user-chip">
            <div class="avatar"><?= strtoupper(substr($full_name, 0, 1)) ?></div>
            <div>
                <strong><?= $full_name ?></strong><br>
                <small style="color:var(--muted);">@<?= $username ?></small>
            </div>
        </div>
        <a href="logout.php" class="nav-item logout">🚪 Logout</a>
    </div>
</aside>

<main class="content">

    <?php if ($alert != '') { ?>
        <div class="alert alert-<?= $atype ?>"><?= $alert ?></div>
    <?php } ?>

    <?php if ($page == 'dashboard') { ?>

        <h1>Welcome back, <?= explode(' ', $full_name)[0] ?> 👋</h1>

        <div class="grid3">
            <div class="card">
                <div class="card-label">Wallet Balance</div>
                <div class="amount-lg">Rs. <?= number_format($balance, 2) ?></div>
                <div class="muted" style="margin-top:6px;">Use this to buy items</div>
            </div>
            <div class="card">
                <div class="card-label">My Listings</div>
                <div class="amount-lg"><?= count($my_products) ?></div>
                <div class="muted" style="margin-top:6px;">Items you're selling</div>
            </div>
            <div class="card">
                <div class="card-label">Account Status</div>
                <div style="margin-top:8px;"><span class="badge">✔ Active</span></div>
                <div class="muted" style="margin-top:6px;">Verified member</div>
            </div>
        </div>

        <div class="card">
            <h2>Recent Activity</h2>
            <?php if (count($txns) == 0) { ?>
                <p class="muted">No transactions yet.</p>
            <?php } else { ?>
                <table>
                    <thead>
                        <tr>
                            <th>REF</th>
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
                            <td class="muted">#<?= $t['txn_ref'] ?></td>
                            <td><?= $t['description'] ?></td>
                            <td class="muted"><?= date('Y-m-d', strtotime($t['created_at'])) ?></td>
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

        <h1>🛍️ Browse Thrift Items</h1>
        <p class="muted" style="margin-bottom:20px;">Wallet balance: Rs. <?= number_format($balance, 2) ?></p>

        <?php if (count($products) == 0) { ?>
            <div class="card"><p class="muted">No items available right now.</p></div>
        <?php } else { ?>
            <div class="product-grid">
                <?php foreach ($products as $p) { ?>
                <div class="product-card">
                    <div class="product-name"><?= $p['name'] ?></div>
                    <div class="product-price">Rs. <?= number_format($p['price'], 2) ?></div>
                    <div class="product-stock"><?= $p['stock'] ?> left</div>
                    <form method="POST" action="action.php">
                        <input type="hidden" name="action" value="purchase">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-green btn-full">Buy Now</button>
                    </form>
                </div>
                <?php } ?>
            </div>
        <?php } ?>

    <?php } elseif ($page == 'withdraw') { ?>

        <h1>💵 Withdraw Earnings</h1>

        <div class="card" style="max-width:460px;">
            <h2>Withdrawal Request</h2>
            <p class="muted" style="margin-bottom:20px;">Available balance: Rs. <?= number_format($balance, 2) ?></p>
            <form method="POST" action="action.php">
                <input type="hidden" name="action" value="withdraw">
                <div class="form-group">
                    <label class="form-label">Amount (Rs.)</label>
                    <input class="form-input" type="number" name="amount" placeholder="e.g. 1000" min="1" required>
                </div>
                <button type="submit" class="btn btn-red">Withdraw</button>
            </form>
        </div>

    <?php } elseif ($page == 'history') { ?>

        <h1>📋 Transaction History</h1>

        <div class="card">
            <?php if (count($txns) == 0) { ?>
                <p class="muted">No transactions yet.</p>
            <?php } else { ?>
                <table>
                    <thead>
                        <tr>
                            <th>REF ID</th>
                            <th>DESCRIPTION</th>
                            <th>DATE & TIME</th>
                            <th>STATUS</th>
                            <th>AMOUNT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($txns as $t) { ?>
                        <tr>
                            <td class="muted">#<?= $t['txn_ref'] ?></td>
                            <td><?= $t['description'] ?></td>
                            <td class="muted"><?= $t['created_at'] ?></td>
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

        <h1>👤 My Account</h1>

        <div class="card" style="max-width:420px;">
            <div class="card-label">Account Details</div>
            <table style="margin-top:12px;">
                <tr>
                    <td class="muted" style="border:none; width:140px; padding:8px 0;">Full Name</td>
                    <td style="border:none;"><?= $full_name ?></td>
                </tr>
                <tr>
                    <td class="muted" style="border:none; padding:8px 0;">Username</td>
                    <td style="border:none;">@<?= $username ?></td>
                </tr>
                <tr>
                    <td class="muted" style="border:none; padding:8px 0;">Wallet Balance</td>
                    <td style="border:none; color:#3fb950;">Rs. <?= number_format($balance, 2) ?></td>
                </tr>
            </table>
        </div>

        <div class="card">
            <h2>My Listings</h2>
            <?php if (count($my_products) == 0) { ?>
                <p class="muted">You haven't listed any items yet.</p>
            <?php } else { ?>
                <table>
                    <thead>
                        <tr>
                            <th>ITEM</th>
                            <th>PRICE</th>
                            <th>STOCK</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_products as $p) { ?>
                        <tr>
                            <td><?= $p['name'] ?></td>
                            <td>Rs. <?= number_format($p['price'], 2) ?></td>
                            <td class="muted"><?= $p['stock'] ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>

    <?php } ?>

</main>

</body>
</html>