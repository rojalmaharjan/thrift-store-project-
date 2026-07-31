# ThriftHub - Peer-to-Peer Online Thrift Store Platform
> **BCA TU 4th Semester Web Technology Project**  
> Developed for Tribhuvan University (TU) Bachelor in Computer Application (BCA) 4th Semester evaluation.

---

## 📌 Project Overview
**ThriftHub** is a full-stack web application that allows users to buy, sell, and thrift pre-loved clothing, electronics, books, vintage collectibles, and accessories. Designed with a peer-to-peer (P2P) economic model, it features a built-in virtual wallet, automated transaction logging, item inventory management, and an administration console.

---

## 🚀 Key Features

### 1. Storefront & Catalog
- **Dynamic Marketplace (`index.php`, `products.php`)**: Live keyword search, category filtering (Clothing, Footwear, Vintage, Books, Electronics, Accessories), condition filtering (Like New, Gently Used, etc.), and price sorting.
- **Product Detail & Direct Buy (`product-detail.php`)**: Interactive item preview showing seller information, item condition badges, and 1-click checkout with instant wallet deduction.

### 2. User Authentication & Wallet System
- **User Registration & Login (`login.php`, `register.php`)**: Password hashing using PHP's `password_hash()` and session state management.
- **Instant Demo Wallet (`action.php`)**: Every registered user gets credited with **Rs. 5,000** demo balance upon registration for effortless project testing.
- **Wallet Deposits & Withdrawals (`dashboard.php`)**: Simulates eSewa/Khalti/Bank withdrawals with unique reference ID generation (`TXNxxxxxx`).

### 3. P2P Seller Inventory Management
- **List Item for Sale (`dashboard.php?page=add_product`)**: Image upload handler saving files to `uploads/` directory, custom pricing, stock management, and condition tags.
- **My Listings (`dashboard.php?page=my_listings`)**: Sellers can view active/sold inventory and remove listings.

### 4. Admin Management Console (`admin.php`)
- System analytics dashboard showing:
  - Total Registered Users
  - Total Listed Products
  - Completed Orders Volume
  - System-wide Financial Transaction Log
- User management table and product moderation controls.

---

## 🛠️ Technology Stack
- **Frontend**: HTML5, Vanilla CSS3 (Custom Design System with Dark/Light Glassmorphism aesthetics), JavaScript (ES6)
- **Backend**: PHP 8.x (Procedural + OOP with MySQLi prepared statements)
- **Database**: MySQL 8.x / MariaDB
- **Web Server**: Apache (XAMPP Server environment)

---

## 🔑 Pre-Configured Test Credentials

For evaluation and viva presentation:

| Role | Username | Password | Notes |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `admin123` | Full access to Admin Panel (`admin.php`) |
| **Seller Demo** | `rojal` | `rojal123` | Pre-loaded with listings & transaction history |
| **Buyer Demo** | `john_doe` | `password123` | Pre-loaded wallet balance |

---

## 💻 Step-by-Step Installation & Setup Guide (XAMPP)

1. **Copy Project Folder to XAMPP**:
   Place the `thrift1` folder inside your XAMPP `htdocs` directory:
   `C:\xampp\htdocs\thrift1\`

2. **Start Apache & MySQL in XAMPP**:
   Open **XAMPP Control Panel** and click **Start** for both **Apache** and **MySQL**.

3. **Import Database (`thrift_db`)**:
   - Open your browser and navigate to: `http://localhost/phpmyadmin/`
   - Click **Import** tab at the top.
   - Choose the file: `C:\xampp\htdocs\thrift1\database.sql`.
   - Click **Go** at the bottom to create tables and insert seed data.

4. **Run Project in Browser**:
   Open browser and go to:  
   `http://localhost/thrift1/index.php` or `http://localhost/thrift1/login.php`

---

## 📁 File Architecture Summary

```
thrift1/
├── action.php           # Central controller handling POST actions (login, register, buy, list, withdraw)
├── admin.php            # Admin administration console & system metrics
├── database.sql         # Full SQL schema & seed script for thrift_db
├── db.php               # MySQLi database connection script with XAMPP auto-check
├── dashboard.css        # Dashboard dark mode CSS design tokens
├── dashboard.php        # User dashboard (Overview, Browse, Sell, Wallet, History, Profile)
├── index.php            # Public storefront landing page with hero banner & category grid
├── login.php            # User & Admin authentication login page
├── logout.php           # Session destruction & signout script
├── product-detail.php   # Detailed product view with buy modal
├── products.php         # Marketplace catalog with live filter & search
├── register.php         # User registration form with initial wallet funding
├── style.css            # Public storefront custom styling framework
├── README.md            # BCA TU project documentation
└── uploads/             # Directory for storing uploaded product image files
```
