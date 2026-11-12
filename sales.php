<?php
session_start();

if (!isset($_SESSION["user_id"], $_SESSION["user_name"])) {
    header("Location: index.php");
    exit;
}

require 'dbcon.php';

$adminName = $_SESSION["user_name"];

function clean($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

function peso($amount) {
    return "₱" . number_format((float)$amount, 2);
}

$message = $_GET["message"] ?? "";

// Record a sale
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["record_sale"])) {
    $productId = filter_input(INPUT_POST, "product_id", FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT);

    if (!$productId || !$quantity || $quantity < 1) {
        header("Location: sales.php?message=" . urlencode("Please select a product and enter a valid quantity."));
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Lock the product row while processing the sale
        $stmt = $pdo->prepare(
            "SELECT id, name, price, stock
             FROM products WHERE id = ? FOR UPDATE"
        );
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new RuntimeException("Product not found.");
        }

        if ((int)$product["stock"] <= 0) {
            throw new RuntimeException("This product is out of stock.");
        }

        if ($quantity > (int)$product["stock"]) {
            throw new RuntimeException(
                "Not enough stock. Available quantity: " . $product["stock"]
            );
        }

        $unitPrice = (float)$product["price"];
        $total = $unitPrice * $quantity;

        // Save a snapshot of the product name and price
        $stmt = $pdo->prepare(
            "INSERT INTO sales
             (product_id, product_name, quantity, unit_price, total, sold_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $product["id"],
            $product["name"],
            $quantity,
            $unitPrice,
            $total
        ]);

        // Deduct sold quantity from inventory
        $stmt = $pdo->prepare(
            "UPDATE products SET stock = stock - ? WHERE id = ?"
        );
        $stmt->execute([$quantity, $productId]);

        $pdo->commit();

        header("Location: sales.php?message=" . urlencode("Sale recorded successfully!"));
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $errorMessage = $e instanceof RuntimeException
            ? $e->getMessage()
            : "Unable to record the sale. Check your sales table structure.";

        header("Location: sales.php?message=" . urlencode($errorMessage));
        exit;
    }
}

// Load products that can be sold
$stmt = $pdo->query(
    "SELECT id, name, price, stock
     FROM products
     WHERE stock > 0
     ORDER BY name"
);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sales summary
$totalRevenue = (float)$pdo->query(
    "SELECT COALESCE(SUM(total), 0) FROM sales"
)->fetchColumn();

$totalTransactions = (int)$pdo->query(
    "SELECT COUNT(*) FROM sales"
)->fetchColumn();

$totalUnits = (int)$pdo->query(
    "SELECT COALESCE(SUM(quantity), 0) FROM sales"
)->fetchColumn();

// Recent sales
$stmt = $pdo->query(
    "SELECT id, product_name, quantity, unit_price, total, sold_at
     FROM sales
     ORDER BY sold_at DESC, id DESC
     LIMIT 100"
);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Management | ProductMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="sidebar.css">
    <script src="script.js" defer></script>
</head>
<style>
     *{
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    }

    body {
        font-family: Arial, Helvetica, sans-serif;
        background: #f5f7fb;
        color: #1e293b;
    }

    .layout {
        display: flex;
        min-height: 100vh;
    }

    /* SIDEBAR */
    .sidebar {
        width: 250px;
        background: #111c3b;
        color: white;
        padding: 25px 16px;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 38px;
    }

    .brand-icon {
        background: #586df0;
        padding: 11px 14px;
        border-radius: 10px;
        font-size: 22px;
        font-weight: bold;
    }

    .brand h2 {
        font-size: 20px;
    }

    .brand small {
        display: block;
        margin-top: 5px;
        font-size: 9px;
        color: #9ba9c9;
        letter-spacing: 1px;
    }

    .menu-title,
    .eyebrow {
        font-size: 10px;
        letter-spacing: 1.5px;
        color: #98a6c5;
        margin-bottom: 14px;
        font-weight: bold;
    }

    .navigation {
        display: grid;
        gap: 7px;
    }

    .navigation a {
        display: flex;
        gap: 13px;
        align-items: center;
        color: #bdc8df;
        padding: 13px;
        text-decoration: none;
        border-radius: 9px;
        transition: 0.2s;
    }

    .navigation a:hover,
    .navigation a.active {
        background: #29375d;
        color: white;
    }

    .navigation a.active {
        border-left: 3px solid #8293ff;
    }

    .sidebar-footer {
        margin-top: auto;
        padding-top: 22px;
        border-top: 1px solid #2b3757;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 12px;
    }

    .sidebar-footer small {
        display: block;
        color: #9ba9c9;
        margin-top: 5px;
    }

    .online-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: #34d399;
    }

    /* MAIN CONTENT */
    .main-content {
        flex: 1;
        min-width: 0;
        padding: 0 30px;
    }

    .topbar {
        min-height: 95px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e5eaf2;
        margin-bottom: 25px;
    }

    .eyebrow {
        color: #7e8ba2;
        margin-bottom: 7px;
    }

    .topbar h1 {
        font-size: 26px;
    }

    .admin {
        display: flex;
        align-items: center;
        gap: 11px;
        font-size: 12px;
    }

    .admin small {
        display: block;
        color: #8995a8;
        margin-top: 5px;
    }

    .admin form {
        height: auto;
        align-items: flex-start;
        padding: 6px 0 0;
        background: transparent;
    }

    .admin .logout-button {
        padding: 0;
        background: transparent;
        color: #6478f5;
        font-size: 11px;
        letter-spacing: 0;
    }

    .admin .logout-button:hover {
        background: transparent;
        color: #243b83;
        text-decoration: underline;
    }

    .avatar {
        width: 39px;
        height: 39px;
        border-radius: 50%;
        background: #e0e7ff;
        color: #3b4fc1;
        display: grid;
        place-items: center;
        font-weight: bold;
    }

    /* WELCOME BANNER */
    .welcome {
        background: linear-gradient(120deg, #243b83, #6478f5);
        padding: 28px;
        border-radius: 16px;
        color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 22px;
    }

    .welcome p {
        color: #c6d1ff;
        font-size: 10px;
        letter-spacing: 1.5px;
        margin-bottom: 10px;
    }

    .welcome h2 {
        font-size: 23px;
        margin-bottom: 9px;
    }

    .welcome span {
        font-size: 12px;
        color: #e0e7ff;
    }

    .button {
        display: inline-block;
        text-decoration: none;
        text-align: center;
        padding: 11px 15px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: bold;
        cursor: pointer;
    }

    .light-button {
        color: #3047b6;
        background: white;
        white-space: nowrap;
    }

    /* STATISTICS */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .stat-card {
        background: white;
        border: 1px solid #e8edf4;
        padding: 20px;
        border-radius: 14px;
        min-width: 0;
    }

    .stat-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        color: #718096;
        font-size: 12px;
    }

    .icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: grid;
        place-items: center;
        font-size: 17px;
        font-weight: bold;
    }

    .purple {
        background: #eeeaff;
        color: #7660dc;
    }

    .blue {
        background: #e8efff;
        color: #4b6ee8;
    }

    .green {
        background: #e2f8ef;
        color: #159b72;
    }

    .orange {
        background: #fff0dd;
        color: #d68a1c;
    }

    .stat-card h2 {
        font-size: 27px;
        margin: 15px 0 8px;
        overflow-wrap: anywhere;
    }

    .stat-card .revenue {
        font-size: 23px;
    }

    .stat-card p {
        color: #8a96a9;
        font-size: 11px;
    }

    /* CONTENT PANELS */
    .content-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(280px, 1fr);
        gap: 18px;
        margin-bottom: 25px;
    }

    .panel {
        background: white;
        border: 1px solid #e8edf4;
        border-radius: 14px;
        padding: 21px;
        min-width: 0;
    }

    .panel + .panel {
        margin-top: 24px;
    }

    .panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 20px;
    }

    .panel-header h3 {
        font-size: 15px;
        margin-bottom: 6px;
    }

    .panel-header p {
        color: #8a96a9;
        font-size: 11px;
    }

    .panel-header > a {
        text-decoration: none;
        color: #5268e8;
        font-size: 11px;
        font-weight: bold;
        white-space: nowrap;
    }

    /* TABLE */
    .table-container {
        width: 100%;
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        white-space: nowrap;
        font-size: 12px;
    }

    th {
        background: #f8fafc;
        color: #8792a5;
        text-align: left;
        font-size: 10px;
        padding: 13px 10px;
    }

    td {
        padding: 15px 10px;
        border-bottom: 1px solid #edf0f5;
        color: #667286;
    }

    td strong {
        color: #26334b;
    }

    .amount {
        font-weight: bold;
        color: #26334b;
    }

    .empty {
        text-align: center;
        padding: 25px;
        color: #9aa4b4;
        font-size: 12px;
    }

    /* INVENTORY ALERTS */
    .alert-count {
        padding: 6px 9px;
        border-radius: 7px;
        background: #fff1e8;
        color: #d97706;
        font-weight: bold;
    }

    .alert-list {
        display: grid;
        gap: 16px;
        margin-bottom: 20px;
    }

    .alert-item {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .product-icon {
        display: grid;
        place-items: center;
        width: 35px;
        height: 35px;
        border-radius: 9px;
        background: #f1f3ff;
        color: #6574e9;
    }

    .product-details {
        flex: 1;
        min-width: 0;
    }

    .product-details strong {
        display: block;
        font-size: 12px;
        overflow-wrap: anywhere;
    }

    .product-details small {
        display: block;
        color: #96a0b0;
        margin-top: 5px;
        font-size: 10px;
    }

    .badge {
        font-size: 10px;
        font-weight: bold;
        padding: 6px 8px;
        border-radius: 6px;
        white-space: nowrap;
    }

    .badge.low {
        background: #fff3df;
        color: #b7791f;
    }

    .badge.out {
        background: #ffe8e8;
        color: #c24141;
    }

    .outline-button {
        border: 1px solid #dce3ee;
        color: #344256;
        background: white;
    }

    .full-button {
        width: 100%;
    }

    footer {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 0 25px;
        color: #9aa4b4;
        font-size: 10px;
    }

    /* RESPONSIVE DESIGN */
    @media (max-width: 1150px) {
        .stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .content-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 760px) {
        .layout {
            display: block;
        }

        .sidebar {
            width: 100%;
            padding: 16px;
        }

        .sidebar-footer,
        .menu-title {
            display: none;
        }

        .brand {
            margin-bottom: 18px;
        }

        .navigation {
            display: flex;
            overflow-x: auto;
        }

        .navigation a {
            white-space: nowrap;
            padding: 10px;
        }

        .main-content {
            padding: 0 14px;
        }

        .topbar {
            min-height: 80px;
        }

        .welcome {
            align-items: flex-start;
            flex-direction: column;
        }

        .stats-grid {
            gap: 10px;
        }

        .stat-card {
            padding: 14px;
        }

        .stat-card h2 {
            font-size: 22px;
        }

        footer {
            flex-direction: column;
        }
    }

    @media print {
        .sidebar,
        .topbar,
        .welcome,
        footer {
            display: none;
        }

        .main-content {
            padding: 0;
        }
    }

    /* Sales and Reports summary cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: #ffffff;
        padding: 24px;
        border-radius: 12px;
        border-left: 4px solid #2563eb;
        box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
    }

    .stat-card p {
        margin: 0 0 12px;
        color: #64748b;
        font-size: 14px;
    }

    .stat-card h2 {
        margin: 0;
        color: #0f172a;
        font-size: 25px;
        overflow-wrap: anywhere;
    }

    /* Sale form */
   .sale-form {
    display: grid ;
    grid-template-columns: 2fr 1fr ;
    column-gap: 25px;
    align-items: start ;
}

.sale-form > .form-group {
    display: flex ;
    flex-direction: column ;
    justify-content: flex-start ;
    align-self: start ;
    margin: 0 ;
    padding: 0 ;
    min-width: 0;
}

.sale-form > .form-group label {
    display: block ;
    height: 30px ;
    line-height: 30px ;
    margin: 0 0 8px ;
    padding: 0 ;
    text-align: center;
}

.sale-form > .form-group select,
.sale-form > .form-group input {
    width: 100% ;
    height: 50px ;
    margin: 0 ;
    box-sizing: border-box;
}

.sale-form > .form-group small {
    margin-top: 10px ;
    text-align: center;
}

@media (max-width: 900px) {
    .sale-form {
        grid-template-columns: 1fr 
;
    }
}

    .sale-form select,
    .sale-form input,
    .report-filter input {
        box-sizing: border-box;
        width: 100%;
        padding: 12px;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        background: #ffffff;
        color: #0f172a;
        font-size: 14px;
    }

    .sale-form select:focus,
    .sale-form input:focus,
    .report-filter input:focus {
        outline: 2px solid #93c5fd;
        border-color: #2563eb;
    }

    .sale-form small {
        color: #64748b;
        margin-top: 5px;
    }

    .sale-total {
        grid-column: 1 / -1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #eff6ff;
        padding: 18px;
        border-radius: 8px;
        color: #1e40af;
    }

    .sale-total strong {
        font-size: 24px;
    }

    .sale-form > button {
        justify-self: start;
    }

    /* Report date filters */
    .report-filter {
        display: flex;
        flex-wrap: wrap;
        align-items: end;
        gap: 16px;
    }

    .report-filter .form-group {
        flex: 1;
        min-width: 180px;
    }

    /* Responsive layout */
    @media (max-width: 900px) {
        .stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .sale-form {
            grid-template-columns: 1fr;
        }

        .sale-total {
            grid-column: auto;
        }
    }

    @media (max-width: 600px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .stat-card {
            padding: 18px;
        }

        .report-filter {
            flex-direction: column;
            align-items: stretch;
        }

        .report-filter .form-group {
            width: 100%;
        }
    }

    /* Print only the page content, not the sidebar or buttons */
    @media print {
        .sidebar,
        .report-filter,
        .sale-form,
        .section-heading button {
            display: none 
    ;
        }

        .main-content {
            margin: 0 
    ;
            padding: 0 
    ;
        }

        .panel,
        .stat-card {
            box-shadow: none;
            break-inside: avoid;
        }
    }

    /* Match the management pages sidebar */
    .sidebar {
        width: 250px;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        background: #0f172a;
        color: #ffffff;
        padding: 25px 18px;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        gap: 10px;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.12);
        z-index: 1000;
    }

    .sidebar h2 {
        font-size: 25px;
        font-weight: 700;
        letter-spacing: 1px;
        margin: 5px 0 25px;
        padding: 0 12px;
        color: #ffffff;
    }

    .sidebar h2::before {
        content: "?";
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        margin-right: 10px;
        border-radius: 9px;
        background: #2563eb;
        color: #ffffff;
        font-size: 23px;
        vertical-align: middle;
    }

    .sidebar a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        margin: 2px 0;
        border-radius: 9px;
        color: #cbd5e1;
        text-decoration: none;
        font-size: 15px;
        font-weight: 500;
        transition: background 0.25s ease, color 0.25s ease, transform 0.25s ease;
    }

    .sidebar a::before {
        display: inline-block;
        width: 20px;
        text-align: center;
        font-size: 17px;
    }

    .sidebar a[href="dashboard.php"]::before { content: "?"; }
    .sidebar a[href="product.php"]::before { content: "?"; }
    .sidebar a[href="suppliers.php"]::before { content: "?"; }
    .sidebar a[href="sales.php"]::before { content: "$"; }
    .sidebar a[href="reports.php"]::before { content: "?"; }

    .sidebar a:hover {
        background: #1e293b;
        color: #ffffff;
        transform: translateX(4px);
    }

    .sidebar a.active {
        background: #2563eb;
        color: #ffffff;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    }

    .sidebar a.active:hover {
        background: #1d4ed8;
        transform: translateX(3px);
    }

    .main-content {
        margin-left: 250px;
        padding: 30px;
        min-height: 100vh;
        box-sizing: border-box;
        background: #f8fafc;
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 200px;
            padding: 20px 12px;
        }

        .sidebar h2 {
            font-size: 20px;
            padding: 0 5px;
        }

        .sidebar a {
            padding: 12px 10px;
            font-size: 14px;
        }

        .main-content {
            margin-left: 200px;
            padding: 20px;
        }
    }

    @media (max-width: 480px) {
        .sidebar {
            width: 75px;
            padding: 20px 8px;
            align-items: center;
        }

        .sidebar h2 {
            font-size: 0;
            padding: 0;
            margin-bottom: 20px;
        }

        .sidebar h2::before { margin-right: 0; }

        .sidebar a {
            justify-content: center;
            width: 100%;
            box-sizing: border-box;
            padding: 14px 5px;
            font-size: 0;
        }

        .sidebar a::before { font-size: 21px; }

        .main-content {
            margin-left: 75px;
            padding: 15px;
        }
    }
</style>
<body>

<div class="layout">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">S</div>
            <div>
                <h2>Blessed Pastore Sari-sari Store</h2>
                <small>PRODUCT MANAGEMENT</small>
            </div>
        </div>
        <p class="menu-title">WORKSPACE</p>
        <nav class="navigation">
            <a href="dashboard.php"><span>▦</span> Dashboard</a>
            <a href="product.php"><span>□</span> Products</a>
            <a href="suppliers.php"><span>♧</span> Suppliers</a>
            <a href="sales.php" class="active"><span>↗</span> Sales</a>
            <a href="reports.php"><span>▤</span> Sales Reports</a>
        </nav>
        <div class="sidebar-footer">
            <span class="online-dot"></span>
            <div><strong>System status</strong><small>Database connected</small></div>
        </div>
    </aside>

<main class="main-content">
    <header class="topbar">
        <div>
            <h1>Sales Management</h1>
            <p>Record transactions and monitor your sales.</p>
        </div>

        <div class="admin">
                <div class="avatar">AD</div>
                <div>
                    <strong><?= clean($adminName) ?></strong>
                    <small>Administrator</small>
                    <form method="post" action="logout.php">
                        <button class="logout-button" type="submit">Sign out</button>
                    </form>
                </div>
            </div>
    </header>

    <?php if ($message !== ""): ?>
        <div class="notice"><?= clean($message) ?></div>
    <?php endif; ?>

    <section class="stats-grid">
        <div class="stat-card">
            <p>Total Revenue</p>
            <h2><?= peso($totalRevenue) ?></h2>
        </div>
        <div class="stat-card">
            <p>Transactions</p>
            <h2><?= number_format($totalTransactions) ?></h2>
        </div>
        <div class="stat-card">
            <p>Units Sold</p>
            <h2><?= number_format($totalUnits) ?></h2>
        </div>
    </section>

    <section class="panel">
        <h2>Record New Sale</h2>

        <?php if (empty($products)): ?>
            <p>There are no products in stock. Add products or restock inventory before recording a sale.</p>
        <?php else: ?>
            <form method="POST" action="sales.php" class="sale-form">
                <div class="form-group">
                    <label for="product_id">Select Product</label>
                    <select id="product_id" name="product_id" required>
                        <option value="">Choose a product</option>
                        <?php foreach ($products as $product): ?>
                            <option
                                value="<?= (int)$product["id"] ?>"
                                data-price="<?= clean($product["price"]) ?>"
                                data-stock="<?= (int)$product["stock"] ?>">
                                <?= clean($product["name"]) ?>
                                — <?= peso($product["price"]) ?>
                                (Stock: <?= (int)$product["stock"] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity"
                           min="1" step="1" value="1" required>
                    <small id="stock-info">Select a product to see available stock.</small>
                </div>

                <div class="sale-total">
                    <span>Estimated Total</span>
                    <strong id="sale-total">₱0.00</strong>
                </div>

                <button type="submit" name="record_sale" class="btn-primary">
                    Record Sale
                </button>
            </form>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="section-heading">
            <h2>Sales History</h2>
            <button type="button" class="btn-secondary" onclick="window.print()">
                Print Sales
            </button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                        <th>Date &amp; Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($sales)): ?>
                    <tr><td colspan="6">No sales recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td><?= (int)$sale["id"] ?></td>
                            <td><?= clean($sale["product_name"]) ?></td>
                            <td><?= (int)$sale["quantity"] ?></td>
                            <td><?= peso($sale["unit_price"]) ?></td>
                            <td><?= peso($sale["total"]) ?></td>
                            <td><?= clean($sale["sold_at"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</div>

<script>
const productSelect = document.getElementById("product_id");
const quantityInput = document.getElementById("quantity");
const stockInfo = document.getElementById("stock-info");
const totalDisplay = document.getElementById("sale-total");

function updateSaleTotal() {
    if (!productSelect) return;

    const option = productSelect.options[productSelect.selectedIndex];
    const price = Number(option?.dataset.price || 0);
    const stock = Number(option?.dataset.stock || 0);

    quantityInput.max = stock;
    stockInfo.textContent = option?.value
        ? `Available stock: ${stock}`
        : "Select a product to see available stock.";

    const quantity = Math.max(0, Number(quantityInput.value || 0));
    totalDisplay.textContent = (price * quantity).toLocaleString("en-PH", {
        style: "currency",
        currency: "PHP"
    });
}

productSelect?.addEventListener("change", updateSaleTotal);
quantityInput?.addEventListener("input", updateSaleTotal);
updateSaleTotal();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
