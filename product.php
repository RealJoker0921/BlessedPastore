<?php
session_start();

if (!isset($_SESSION["user_id"], $_SESSION["user_name"])) {
    header("Location: index.php");
    exit;
}

require 'dbcon.php';

$adminName = $_SESSION["user_name"];

// Add or update product
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST["id"] ?? "";
    $name = trim($_POST["name"] ?? "");
    $sku = trim($_POST["sku"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $price = (float) ($_POST["price"] ?? 0);
    $stock = (int) ($_POST["stock"] ?? 0);
    $lowStock = (int) ($_POST["low_stock_threshold"] ?? 5);

    if ($name !== "" && $sku !== "" && $price >= 0
        && $stock >= 0 && $lowStock >= 0) {
        try {
            if ($id !== "") {
                $stmt = $pdo->prepare(
                    "UPDATE products
                     SET name = ?, sku = ?, category = ?,
                         price = ?, stock = ?, low_stock_threshold = ?
                     WHERE id = ?"
                );

                $stmt->execute([
                    $name, $sku, $category, $price,
                    $stock, $lowStock, $id
                ]);
                $message = "Product updated successfully!";
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO products
                     (name, sku, category, price, stock, low_stock_threshold)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );

                $stmt->execute([
                    $name, $sku, $category, $price,
                    $stock, $lowStock
                ]);
                $message = "Product added successfully!";
            }
        } catch (PDOException $e) {
            $message = "Unable to save product. Check that the SKU is unique.";
        }
    } else {
        $message = "Please enter valid product information.";
    }

    header("Location: product.php?message=" . urlencode($message));
    exit;
}

// Delete product
if (isset($_GET["delete"])) {
    $id = filter_input(INPUT_GET, "delete", FILTER_VALIDATE_INT);

    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Product deleted successfully!";
        } catch (PDOException $e) {
            $message = "Cannot delete this product because it may be linked to a sale.";
        }
    } else {
        $message = "Invalid product ID.";
    }

    header("Location: product.php?message=" . urlencode($message));
    exit;
}

// Load product for editing
$editProduct = null;

if (isset($_GET["edit"])) {
    $id = filter_input(INPUT_GET, "edit", FILTER_VALIDATE_INT);

    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Search products
$search = trim($_GET["search"] ?? "");

$stmt = $pdo->prepare(
    "SELECT * FROM products
     WHERE name LIKE ? OR sku LIKE ? OR category LIKE ?
     ORDER BY id DESC"
);

$keyword = "%" . $search . "%";
$stmt->execute([$keyword, $keyword, $keyword]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

function clean($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

function peso($amount) {
    return "₱" . number_format((float)$amount, 2);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="sidebar.css">
    
</head>
<style>
   .panel {
    background: #ffffff;
    padding: 24px;
    margin-bottom: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .panel h2 {
        margin-top: 0;
        color: #172554;
    }

    .product-form {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .form-group label {
        font-size: 14px;
        font-weight: 600;
        color: #334155;
    }

    .form-group input {
        width: 100%;
        box-sizing: border-box;
        padding: 11px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        font-size: 14px;
    }

    .form-group input:focus,
    .search-form input:focus {
        outline: 2px solid #93c5fd;
        border-color: #2563eb;
    }

    .form-actions {
        grid-column: 1 / -1;
        display: flex;
        gap: 10px;
    }

    .btn-primary,
    .btn-secondary,
    .btn-edit,
    .btn-delete {
        display: inline-block;
        padding: 9px 13px;
        border: none;
        border-radius: 6px;
        text-decoration: none;
        font-size: 13px;
        cursor: pointer;
    }

    .btn-primary {
        background: #2563eb;
        color: white;
    }

    .btn-secondary {
        background: #e2e8f0;
        color: #1e293b;
    }

    .btn-edit {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .btn-delete {
        background: #fee2e2;
        color: #b91c1c;
    }

    .section-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .search-form {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .search-form input {
        min-width: 220px;
        padding: 10px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
    }

    .table-container {
        width: 100%;
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        white-space: nowrap;
    }

    th,
    td {
        padding: 13px 12px;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
    }

    th {
        background: #f1f5f9;
        color: #334155;
        font-size: 13px;
    }

    td {
        font-size: 14px;
        color: #475569;
    }

    .badge {
        display: inline-block;
        padding: 5px 9px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge.out {
        background: #fee2e2;
        color: #b91c1c;
    }

    .badge.low {
        background: #fef3c7;
        color: #92400e;
    }

    .badge.in {
        background: #dcfce7;
        color: #166534;
    }

    .action-buttons {
        display: flex;
        gap: 6px;
    }

    .notice {
        background: #dcfce7;
        color: #166534;
        padding: 12px 16px;
        margin-bottom: 20px;
        border-radius: 8px;
    }

    @media (max-width: 900px) {
        .product-form {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 600px) {
        .product-form {
            grid-template-columns: 1fr;
        }

        .form-actions {
            flex-wrap: wrap;
        }
    }
    /* Sidebar */
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

    /* Logo / Brand */
    .sidebar h2 {
        font-size: 25px;
        font-weight: 700;
        letter-spacing: 1px;
        margin: 5px 0 25px;
        padding: 0 12px;
        color: #ffffff;
    }

    .sidebar h2::before {
        content: "▦";
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

    /* Navigation links */
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
        transition: background 0.25s ease,
                    color 0.25s ease,
                    transform 0.25s ease;
    }

    /* Simple navigation icons */
    .sidebar a::before {
        display: inline-block;
        width: 20px;
        text-align: center;
        font-size: 17px;
    }

    .sidebar a[href="dashboard.php"]::before {
        content: "▦";
    }

    .sidebar a[href="product.php"]::before {
        content: "□";
    }

    .sidebar a[href="suppliers.php"]::before {
        content: "♧";
    }

    .sidebar a[href="sales.php"]::before {
        content: "$";
    }

    .sidebar a[href="reports.php"]::before {
        content: "▥";
    }

    /* Hover effect */
    .sidebar a:hover {
        background: #1e293b;
        color: #ffffff;
        transform: translateX(4px);
    }

    /* Active navigation link */
    .sidebar a.active {
        background: #2563eb;
        color: #ffffff;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    }

    /* Keep active link blue on hover */
    .sidebar a.active:hover {
        background: #1d4ed8;
        transform: translateX(3px);
    }

    /* Main content offset */
    .main-content {
        margin-left: 250px;
        padding: 30px;
        min-height: 100vh;
        box-sizing: border-box;
        background: #f8fafc;
    }
    .topbar {
    min-height: 95px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e5eaf2;
    margin-bottom: 25px;
    }
    .topbar h1 {
        margin: 0 0 8px;
        color: #0f172a;
        font-size: 28px;
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
        display: flex;
        flex-direction: column;
        justify-content: center;
        height: auto;
        align-items: flex-start;
        padding: 6px 0 0;
        background: transparent;
        text-align: center;
    }

    .admin .logout-button {
        padding: 0;
        border: none;
        border-radius: 22px;
        background: transparent;
        color: #6478f5;
        font-size: 11px;
        font-weight: bold;
        letter-spacing: 0;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition: transform 0.25s ease,
                    background 0.25s ease,
                    box-shadow 0.25s ease;
    }

    .admin .logout-button:hover {
        background: transparent;
        color: #243b83;
        text-decoration: underline;
        transform: translateY(-2px);
        box-shadow: 0 7px 16px rgba(62, 75, 180, 0.2);
    }

    .admin .logout-button:active {
        transform: translateY(0) scale(0.96);
        box-shadow: none;
    }

    .admin .logout-button:focus-visible {
        outline: 2px solid #2639a8;
        outline-offset: 3px;
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

    /* Responsive design */
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

        .sidebar h2::before {
            margin-right: 0;
        }

        .sidebar a {
            justify-content: center;
            width: 100%;
            box-sizing: border-box;
            padding: 14px 5px;
            font-size: 0;
        }

        .sidebar a::before {
            font-size: 21px;
        }

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
            <a href="product.php" class="active"><span>□</span> Products</a>
            <a href="suppliers.php"><span>♧</span> Suppliers</a>
            <a href="sales.php"><span>↗</span> Sales</a>
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
            <h1>Product Management</h1>
            <p>Manage your store's products and inventory.</p>
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

    <?php if (isset($_GET["message"])): ?>
        <div class="notice">
            <?= clean($_GET["message"]) ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <h2><?= $editProduct ? "Edit Product" : "Add New Product" ?></h2>

        <form method="POST" action="product.php" class="product-form">
            <input type="hidden" name="id"
                   value="<?= clean($editProduct["id"] ?? "") ?>">

            <div class="form-group">
                <label for="name">Product Name</label>
                <input id="name" name="name" type="text"
                       placeholder="Enter product name"
                       value="<?= clean($editProduct["name"] ?? "") ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="sku">SKU</label>
                <input id="sku" name="sku" type="text"
                       placeholder="e.g. PROD-001"
                       value="<?= clean($editProduct["sku"] ?? "") ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <input id="category" name="category" type="text"
                       placeholder="e.g. Grocery"
                       value="<?= clean($editProduct["category"] ?? "") ?>">
            </div>

            <div class="form-group">
                <label for="price">Price (₱)</label>
                <input id="price" name="price" type="number"
                       min="0" step="0.01"
                       value="<?= clean($editProduct["price"] ?? "0.00") ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="stock">Stock Quantity</label>
                <input id="stock" name="stock" type="number" min="0"
                       value="<?= clean($editProduct["stock"] ?? "0") ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="low_stock_threshold">Low-Stock Alert Level</label>
                <input id="low_stock_threshold" name="low_stock_threshold"
                       type="number" min="0"
                       value="<?= clean($editProduct["low_stock_threshold"] ?? "5") ?>"
                       required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <?= $editProduct ? "Update Product" : "Add Product" ?>
                </button>

                <?php if ($editProduct): ?>
                    <a href="product.php" class="btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="section-heading">
            <h2>Product List</h2>

            <form method="GET" action="product.php" class="search-form">
                <input type="search" name="search"
                       placeholder="Search name, SKU, or category"
                       value="<?= clean($search) ?>">
                <button type="submit" class="btn-primary">Search</button>
                <a href="product.php" class="btn-secondary">Reset</a>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$products): ?>
                    <tr>
                        <td colspan="8">No products found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= clean($product["name"]) ?></td>
                            <td><?= clean($product["sku"]) ?></td>
                            <td><?= clean($product["category"]) ?></td>
                            <td><?= peso($product["price"]) ?></td>
                            <td><?= clean($product["stock"]) ?></td>
                            <td>
                                <?php if ((int)$product["stock"] === 0): ?>
                                    <span class="badge out">Out of Stock</span>
                                <?php elseif ((int)$product["stock"] <= (int)$product["low_stock_threshold"]): ?>
                                    <span class="badge low">Low Stock</span>
                                <?php else: ?>
                                    <span class="badge in">In Stock</span>
                                <?php endif; ?>
                            </td>
                            <td class="action-buttons">
                                <a class="btn-edit"
                                   href="product.php?edit=<?= (int)$product["id"] ?>">
                                    Edit
                                </a>

                                <button type="button" class="btn-delete"
                                        onclick="confirmDelete(<?= (int)$product["id"] ?>, '<?= clean($product["name"]) ?>')">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>