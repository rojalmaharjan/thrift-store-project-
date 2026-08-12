<?php
session_start();
include("db.php");

$role = isset($_SESSION['role']) ? $_SESSION['role'] : "";
if(!isset($_SESSION['user_id']) || $role != "admin"){
    header("Location: login.php");
    exit;
}

$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users"))['cnt'];
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM products"))['cnt'];
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders"))['cnt'];

$vol_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as sum FROM transactions WHERE type='credit'"));
$total_volume = $vol_row['sum'] ? $vol_row['sum'] : 0; 

$users = array();
$u_res = mysqli_query($conn, "SELECT id, full_name, username, email, balance, role, created_at FROM users ORDER BY id DESC");
while($row = mysqli_fetch_assoc($u_res)){
    $users[] = $row;
}

$all_products = array();
$p_res = mysqli_query($conn, "SELECT p.*, u.full_name as seller_name, c.name as category_name FROM products p JOIN users u ON p.seller_id=u.id JOIN categories c ON p.category_id=c.id ORDER BY p.id DESC");
while($row = mysqli_fetch_assoc($p_res)){
    $all_products[] = $row;
}

$all_orders = array();
$o_res = mysqli_query($conn, "SELECT o.*, b.full_name as buyer_name, s.full_name as seller_name, p.name as product_name FROM orders o JOIN users b ON o.buyer_id=b.id JOIN users s ON o.seller_id=s.id JOIN products p ON o.product_id=p.id ORDER BY o.id DESC LIMIT 20");
while($row = mysqli_fetch_assoc($o_res)){
    $all_orders[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - ThriftHub</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="dashboard.css">
</head>
<body>

<aside class="sidebar">
    <a href="index.php" class="sidebar-logo">Admin Panel</a>
    <a href="admin.php" class="nav-item active">System Metrics</a>
    <a href="dashboard.php" class="nav-item">Switch to User View</a>
    <a href="index.php" class="nav-item">Storefront</a>

    <div class="sidebar-footer">
        <div class="user-chip">
            <div class="avatar">A</div>
            <div>
                <strong style="color:white; font-size:14px;">Administrator</strong><br>
                <small>System Controller</small>
            </div>
        </div>
        <a href="logout.php" class="nav-item logout">Logout</a>
    </div>
</aside>

<main class="content">

<div style="margin-bottom:24px;">
    <h1>System Administration Console</h1>
    <p class="muted">Platform metrics, user management, order auditing, and content moderation.</p>
</div>

<div class="grid3" style="grid-template-columns: repeat(4, 1fr);">
    <div class="card">
        <div class="card-label">Total Users</div>
        <div class="amount-lg"><?php echo number_format($total_users); ?></div>
        <div class="muted">Registered accounts</div>
    </div>
    <div class="card">
        <div class="card-label">Total Products</div>
        <div class="amount-lg"><?php echo number_format($total_products); ?></div>
        <div class="muted">Thrift listings</div>
    </div>
    <div class="card">
        <div class="card-label">Completed Orders</div>
        <div class="amount-lg"><?php echo number_format($total_orders); ?></div>
        <div class="muted">Successful transactions</div>
    </div>
    <div class="card">
        <div class="card-label">System Volume</div>
        <div class="amount-lg" style="font-size:24px;">Rs. <?php echo number_format($total_volume, 2); ?></div>
        <div class="muted">Total credits processed</div>
    </div>
</div>

<div class="card">
    <h2>Registered System Users (<?php echo count($users); ?>)</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>FULL NAME</th><th>USERNAME</th><th>EMAIL</th>
                <th>BALANCE</th><th>ROLE</th><th>JOINED</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($users as $u){ ?>
            <tr>
                <td class="muted">#<?php echo $u['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></td>
                <td class="muted">@<?php echo htmlspecialchars($u['username']); ?></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td>Rs. <?php echo number_format($u['balance'], 2); ?></td>
                <td>
                    <?php if($u['role'] == "admin"){ ?>
                        <span class="badge badge-amber"><?php echo ucfirst($u['role']); ?></span>
                    <?php }else{ ?>
                        <span class="badge"><?php echo ucfirst($u['role']); ?></span>
                    <?php } ?>
                </td>
                <td class="muted"><?php echo date('Y-m-d', strtotime($u['created_at'])); ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Marketplace Listings (<?php echo count($all_products); ?>)</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>PRODUCT NAME</th><th>CATEGORY</th><th>SELLER</th>
                <th>PRICE</th><th>STOCK</th><th>STATUS</th><th>ACTION</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($all_products as $p){ ?>
            <tr>
                <td class="muted">#<?php echo $p['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                <td class="muted"><?php echo htmlspecialchars($p['category_name']); ?></td>
                <td class="muted"><?php echo htmlspecialchars($p['seller_name']); ?></td>
                <td>Rs. <?php echo number_format($p['price'], 2); ?></td>
                <td class="muted"><?php echo $p['stock']; ?></td>
                <td>
                    <?php if($p['status'] == "active"){ ?>
                        <span class="badge"><?php echo ucfirst($p['status']); ?></span>
                    <?php }else{ ?>
                        <span class="badge badge-amber"><?php echo ucfirst($p['status']); ?></span>
                    <?php } ?>
                </td>
                <td>
                    <a href="action.php?action=delete_product&id=<?php echo $p['id']; ?>" onclick="return confirm('Admin Delete: Are you sure?');" class="btn btn-red" style="padding:4px 10px; font-size:12px;">Delete</a>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Recent Platform Orders</h2>
    <?php if(count($all_orders) == 0){ ?>
        <p class="muted">No orders placed yet.</p>
    <?php }else{ ?>
        <table>
            <thead>
                <tr>
                    <th>ORDER REF</th><th>PRODUCT</th><th>BUYER</th><th>SELLER</th>
                    <th>AMOUNT</th><th>DATE</th><th>STATUS</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($all_orders as $o){ ?>
                <tr>
                    <td class="muted">#<?php echo htmlspecialchars($o['order_ref']); ?></td>
                    <td><strong><?php echo htmlspecialchars($o['product_name']); ?></strong></td>
                    <td class="muted"><?php echo htmlspecialchars($o['buyer_name']); ?></td>
                    <td class="muted"><?php echo htmlspecialchars($o['seller_name']); ?></td>
                    <td>Rs. <?php echo number_format($o['amount'], 2); ?></td>
                    <td class="muted"><?php echo date('Y-m-d H:i', strtotime($o['created_at'])); ?></td>
                    <td><span class="badge">Completed</span></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    <?php } ?>
</div>

</main>

</body>
</html>