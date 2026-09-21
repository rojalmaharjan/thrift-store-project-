<?php
// actions.php
// This file handles all the form actions of the Thrift Marketplace project
// (login, register, purchase, add product, withdraw, deposit, delete product)

session_start();
include("db.php");   // database connection ($conn)

// get the action from the form or url
if(isset($_REQUEST['action'])){
    $action = $_REQUEST['action'];
}
else{
    $action = "";
}


// ================= LOGIN =================
if($action == "login"){

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // check empty fields
    if($username == "" || $password == ""){
        header("Location: login.php?err=empty");
        exit;
    }

    // user can login with username or email
    $sql = "SELECT * FROM users WHERE username='$username' OR email='$username'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    // check password
    if($row && password_verify($password, $row['password'])){

        // save user info in session
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];

        // admin goes to admin page, normal user goes to dashboard
        if($row['role'] == "admin"){
            header("Location: admin.php");
        }
        else{
            header("Location: dashboard.php");
        }
        exit;
    }
    else{
        header("Location: login.php?err=invalid");
        exit;
    }
}


// ================= REGISTER =================
if($action == "register"){

    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // check empty fields
    if($full_name == "" || $username == "" || $email == "" || $password == ""){
        header("Location: register.php?err=empty");
        exit;
    }

    // check if username or email already exists
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' OR email='$email'");
    if(mysqli_num_rows($check) > 0){
        header("Location: register.php?err=exists");
        exit;
    }

    // hide the password using hash
    $hash_password = password_hash($password, PASSWORD_DEFAULT);

    // every new user gets Rs. 5000 as welcome bonus
    $balance = 5000;

    $sql = "INSERT INTO users (full_name, username, email, password, balance, role)
            VALUES ('$full_name', '$username', '$email', '$hash_password', '$balance', 'user')";
    $insert = mysqli_query($conn, $sql);

    if($insert){
        $new_id = mysqli_insert_id($conn);

        // login the user automatically
        $_SESSION['user_id'] = $new_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = "user";

        // save welcome bonus in transactions table
        $ref = "TXN" . rand(100000, 999999);
        mysqli_query($conn, "INSERT INTO transactions (user_id, txn_ref, description, amount, type)
                             VALUES ('$new_id', '$ref', 'Welcome bonus credited to wallet', '$balance', 'credit')");

        header("Location: dashboard.php?msg=welcome");
        exit;
    }
    else{
        header("Location: register.php?err=failed");
        exit;
    }
}


// ================= LOGIN CHECK =================
// all actions below need the user to be logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];


// ================= PURCHASE PRODUCT =================
if($action == "purchase"){

    $product_id = (int) $_POST['product_id'];
    $delivery_address = mysqli_real_escape_string($conn, trim($_POST['delivery_address']));
    $phone_number = mysqli_real_escape_string($conn, trim($_POST['phone_number']));

    // get the product
    $result = mysqli_query($conn, "SELECT * FROM products WHERE id='$product_id'");
    $product = mysqli_fetch_assoc($result);

    // product not found
    if(!$product){
        header("Location: dashboard.php?page=products&err=not_found");
        exit;
    }

    // user cannot buy his own product
    if($product['seller_id'] == $user_id){
        header("Location: dashboard.php?page=products&err=own_item");
        exit;
    }

    // check stock
    if($product['stock'] <= 0 || $product['status'] != "active"){
        header("Location: dashboard.php?page=products&err=sold_out");
        exit;
    }

    $price = $product['price'];
    $seller_id = $product['seller_id'];
    $product_name = mysqli_real_escape_string($conn, $product['name']);

    // check buyer balance
    $user_result = mysqli_query($conn, "SELECT balance FROM users WHERE id='$user_id'");
    $user = mysqli_fetch_assoc($user_result);

    if($user['balance'] < $price){
        header("Location: dashboard.php?page=products&err=insufficient");
        exit;
    }

    // reduce the stock by 1
    $new_stock = $product['stock'] - 1;
    if($new_stock <= 0){
        $status = "sold";
    }
    else{
        $status = "active";
    }
    mysqli_query($conn, "UPDATE products SET stock='$new_stock', status='$status' WHERE id='$product_id'");

    // cut money from buyer and add money to seller
    mysqli_query($conn, "UPDATE users SET balance = balance - $price WHERE id='$user_id'");
    mysqli_query($conn, "UPDATE users SET balance = balance + $price WHERE id='$seller_id'");

    // save the order
    $order_ref = "ORD" . rand(100000, 999999);
    mysqli_query($conn, "INSERT INTO orders (order_ref, buyer_id, seller_id, product_id, amount, status)
                         VALUES ('$order_ref', '$user_id', '$seller_id', '$product_id', '$price', 'Completed')");

    // transaction for buyer (debit)
    $buyer_desc = "Purchased thrift item: " . $product_name;
    if($delivery_address != ""){
        $buyer_desc = $buyer_desc . " (Addr: " . $delivery_address . ")";
    }
    $txn1 = "TXN" . rand(100000, 999999);
    mysqli_query($conn, "INSERT INTO transactions (user_id, txn_ref, description, amount, type)
                         VALUES ('$user_id', '$txn1', '$buyer_desc', '$price', 'debit')");

    // transaction for seller (credit)
    $seller_desc = "Sold thrift item: " . $product_name;
    $txn2 = "TXN" . rand(100000, 999999);
    mysqli_query($conn, "INSERT INTO transactions (user_id, txn_ref, description, amount, type)
                         VALUES ('$seller_id', '$txn2', '$seller_desc', '$price', 'credit')");

    header("Location: dashboard.php?page=history&ok=1&ref=" . $order_ref);
    exit;
}


// ================= ADD PRODUCT =================
if($action == "add_product"){

    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $price = (float) $_POST['price'];
    $category_id = (int) $_POST['category_id'];
    $condition_status = mysqli_real_escape_string($conn, $_POST['condition_status']);
    $stock = (int) $_POST['stock'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    // default image if user does not upload any
    $image_name = "default_item.jpg";

    // image upload
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = array("jpg", "jpeg", "png", "webp");

        // only allow image files
        if(in_array($ext, $allowed)){
            $image_name = "item_" . time() . rand(1000, 9999) . "." . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image_name);
        }
    }

    $sql = "INSERT INTO products (seller_id, category_id, name, description, price, condition_status, stock, image, status)
            VALUES ('$user_id', '$category_id', '$name', '$description', '$price', '$condition_status', '$stock', '$image_name', 'active')";
    $insert = mysqli_query($conn, $sql);

    if($insert){
        header("Location: dashboard.php?page=my_listings&msg=added");
    }
    else{
        header("Location: dashboard.php?page=add_product&err=db_error");
    }
    exit;
}


// ================= WITHDRAW MONEY =================
if($action == "withdraw"){

    $amount = (float) $_POST['amount'];

    // amount must be greater than 0
    if($amount <= 0){
        header("Location: dashboard.php?page=withdraw&err=invalid_amount");
        exit;
    }

    // check current balance
    $result = mysqli_query($conn, "SELECT balance FROM users WHERE id='$user_id'");
    $row = mysqli_fetch_assoc($result);

    if($row['balance'] < $amount){
        header("Location: dashboard.php?page=withdraw&err=insufficient");
        exit;
    }

    // cut the balance
    mysqli_query($conn, "UPDATE users SET balance = balance - $amount WHERE id='$user_id'");

    // save transaction
    $ref = "TXN" . rand(100000, 999999);
    mysqli_query($conn, "INSERT INTO transactions (user_id, txn_ref, description, amount, type)
                         VALUES ('$user_id', '$ref', 'Withdrawal request to eSewa / Bank', '$amount', 'debit')");

    header("Location: dashboard.php?page=withdraw&ok=1&ref=" . $ref);
    exit;
}


// ================= DEPOSIT MONEY (demo) =================
if($action == "deposit"){

    $amount = (float) $_POST['amount'];

    if($amount <= 0){
        header("Location: dashboard.php?page=dashboard&err=invalid_amount");
        exit;
    }

    // add the balance
    mysqli_query($conn, "UPDATE users SET balance = balance + $amount WHERE id='$user_id'");

    // save transaction
    $ref = "TXN" . rand(100000, 999999);
    mysqli_query($conn, "INSERT INTO transactions (user_id, txn_ref, description, amount, type)
                         VALUES ('$user_id', '$ref', 'Wallet top-up (Demo Deposit)', '$amount', 'credit')");

    header("Location: dashboard.php?page=dashboard&msg=deposited");
    exit;
}


// ================= DELETE PRODUCT =================
if($action == "delete_product"){

    $product_id = (int) $_GET['id'];

    // get role from session
    if(isset($_SESSION['role'])){
        $role = $_SESSION['role'];
    }
    else{
        $role = "user";
    }

    // admin can delete any product, user can delete only his own product
    if($role == "admin"){
        $sql = "DELETE FROM products WHERE id='$product_id'";
    }
    else{
        $sql = "DELETE FROM products WHERE id='$product_id' AND seller_id='$user_id'";
    }
    mysqli_query($conn, $sql);

    header("Location: dashboard.php?page=my_listings&msg=deleted");
    exit;
}


// if no action matched, go back to dashboard
header("Location: dashboard.php");
exit;
?>