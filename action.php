<?php
session_start();
include("db.php");

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";

if($action == "login"){
    $uname = $_POST['username'];
    $pwd = $_POST['password'];
    if($uname == "" || $pwd == ""){ header("Location: login.php?err=empty"); exit; }

    $result = mysqli_query($conn, "SELECT * FROM users WHERE username='$uname' OR email='$uname'");
    $row = mysqli_fetch_assoc($result);

    if($row && password_verify($pwd, $row['password'])){
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];
        header($row['role'] == "admin" ? "Location: admin.php" : "Location: dashboard.php");
        exit;
    }
    header("Location: login.php?err=invalid"); exit;
}

if($action == "register"){
    $full_name = $_POST['full_name'];
    $uname = $_POST['username'];
    $email = $_POST['email'];
    $pwd = $_POST['password'];
    if($full_name == "" || $uname == "" || $email == "" || $pwd == ""){
        header("Location: register.php?err=empty"); exit;
    }

    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$uname' OR email='$email'");
    if(mysqli_num_rows($check) > 0){ header("Location: register.php?err=exists"); exit; }

    $hash_pwd = password_hash($pwd, PASSWORD_DEFAULT);
    $balance = 5000;
    $ok = mysqli_query($conn, "INSERT INTO users (full_name, username, email, password, balance, role) VALUES ('$full_name','$uname','$email','$hash_pwd','$balance','user')");

    if($ok){
        $new_id = mysqli_insert_id($conn);
        $_SESSION['user_id'] = $new_id;
        $_SESSION['username'] = $uname;
        $_SESSION['role'] = "user";

        $ref = "TXN".rand(100000,999999);
        mysqli_query($conn, "INSERT INTO transactions (user_id, txn_ref, description, amount, type) VALUES ('$new_id','$ref','Welcome bonus credited to wallet','$balance','credit')");

        header("Location: dashboard.php?msg=welcome"); exit;
    }
    header("Location: register.php?err=failed"); exit;
}

if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];

if($action == "purchase"){
    $product_id = $_POST['product_id'];
    $result = mysqli_query($conn, "SELECT * FROM products WHERE id='$product_id'");
    $product = mysqli_fetch_assoc($result);

    if(!$product){ header("Location: dashboard.php?page=products&err=not_found"); exit; }
    if($product['seller_id'] == $user_id){ header("Location: dashboard.php?page=products&err=own_item"); exit; }
    if($product['stock'] <= 0 || $product['status'] != "active"){ header("Location: dashboard.php?page=products&err=sold_out"); exit; }

    $buyer = mysqli_fetch_assoc(mysqli_query($conn, "SELECT balance FROM users WHERE id='$user_id'"));
    $price = $product['price'];
    if($buyer['balance'] < $price){ header("Location: dashboard.php?page=products&err=insufficient"); exit; }

    mysqli_query($conn, "UPDATE users SET balance = balance - $price WHERE id='$user_id'");
    mysqli_query($conn, "UPDATE users SET balance = balance + $price WHERE id='".$product['seller_id']."'");

    $new_stock = $product['stock'] - 1;
    $status = $new_stock <= 0 ? "sold" : "active";
    mysqli_query($conn, "UPDATE products SET stock='$new_stock', status='$status' WHERE id='$product_id'");

    $ord_ref = "ORD".rand(100000,999999);
    mysqli_query($conn, "INSERT INTO orders (order_ref, buyer_id, seller_id, product_id, amount, status) VALUES ('$ord_ref','$user_id','".$product['seller_id']."','$product_id','$price','Completed')");

    $t1 = "TXN".rand(100000,999999);
    mysqli_query($conn, "INSERT INTO transactions (user_id, txn_ref, description, amount, type) VALUES ('$user_id','$t1','Purchased thrift item: ".$product['name']."','$price','debit')");

    $t2 = "TXN".rand(100000,999999);
    mysqli_query($conn, "INSERT INTO transactions (user_id, txn_ref, description, amount, type) VALUES ('".$product['seller_id']."','$t2','Sold thrift item: ".$product['name']."','$price','credit')");

    header("Location: dashboard.php?page=history&ok=1&ref=".$ord_ref); exit;
}

if($action == "add_product"){
    $name = $_POST['name'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $condition_status = $_POST['condition_status'];
    $stock = $_POST['stock'];
    $description = $_POST['description'];
    if($name == "" || $price <= 0){ header("Location: dashboard.php?page=add_product&err=invalid_input"); exit; }

    $image_name = "default_item.jpg";
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, array("jpg","jpeg","png","webp"))){
            $image_name = "item_".time().rand(1000,9999).".".$ext;
            move_uploaded_file($_FILES['image']['tmp_name'], "uploads/".$image_name);
        }
    }

    $ok = mysqli_query($conn, "INSERT INTO products (seller_id, category_id, name, description, price, condition_status, stock, image, status) VALUES ('$user_id','$category_id','$name','$description','$price','$condition_status','$stock','$image_name','active')");
    header($ok ? "Location: dashboard.php?page=my_listings&msg=added" : "Location: dashboard.php?page=add_product&err=db_error");
    exit;
}

if($action == "withdraw"){
    $amount = $_POST['amount'];
    if($amount <= 0){ header("Location: dashboard.php?page=withdraw&err=invalid_amount"); exit; }

    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT balance FROM users WHERE id='$user_id'"));
    if($row['balance'] < $amount){ header("Location: dashboard.php?page=withdraw&err=insufficient"); exit; }

    mysqli_query($conn, "UPDATE users SET balance = balance - $amount WHERE id='$user_id'");
    $ref = "TXN".rand(100000,999999);
    mysqli_query($conn, "INSERT INTO transactions (user_id, txn_ref, description, amount, type) VALUES ('$user_id','$ref','Withdrawal request to eSewa / Bank','$amount','debit')");

    header("Location: dashboard.php?page=withdraw&ok=1&ref=".$ref); exit;
}

if($action == "deposit"){
    $amount = $_POST['amount'];
    if($amount <= 0){ header("Location: dashboard.php?page=dashboard&err=invalid_amount"); exit; }

    mysqli_query($conn, "UPDATE users SET balance = balance + $amount WHERE id='$user_id'");
    $ref = "TXN".rand(100000,999999);
    mysqli_query($conn, "INSERT INTO transactions (user_id, txn_ref, description, amount, type) VALUES ('$user_id','$ref','Wallet top-up (Demo Deposit)','$amount','credit')");

    header("Location: dashboard.php?page=dashboard&msg=deposited"); exit;
}

if($action == "delete_product"){
    $product_id = $_GET['id'];
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : "user";
    $sql = $role == "admin" ? "DELETE FROM products WHERE id='$product_id'" : "DELETE FROM products WHERE id='$product_id' AND seller_id='$user_id'";
    mysqli_query($conn, $sql);
    header("Location: dashboard.php?page=my_listings&msg=deleted"); exit;
}

header("Location: dashboard.php");
exit;
?>