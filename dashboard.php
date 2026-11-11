<?php
include 'dbcon.php';

// Dashboard statistics
$totalProducts = $pdo->query(
    "SELECT COUNT(*) FROM products"
)->fetchColumn();

$totalStock = $pdo->query(
    "SELECT COALESCE(SUM(stock), 0) FROM products"
)->fetchColumn();

$totalRevenue = $pdo->query(
    "SELECT COALESCE(SUM(total), 0) FROM sales"
)->fetchColumn();

$totalTransactions = $pdo->query(
    "SELECT COUNT(*) FROM sales"
)->fetchColumn();

// Low-stock products
$lowStock = $pdo->query(
    "SELECT * FROM products
     WHERE stock <= low_stock_threshold
     ORDER BY stock ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// Recent sales
$recentSales = $pdo->query(
    "SELECT * FROM sales
     ORDER BY sold_at DESC
     LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

function peso($amount) {
    return "₱" . number_format((float)$amount, 2);
}

function clean($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>StockWise | Dashboard</title>

    <link rel="stylesheet" href="style.css">
</head>
<style>
    * {
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
</style>

<body>

<div class="layout">

    <!-- SIDEBAR -->
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
            <a href="dashboard.php" class="active">
                <span>▦</span> Dashboard
            </a>

            <a href="product.php">
                <span>□</span> Products
            </a>

            <a href="suppliers.php">
                <span>♧</span> Suppliers
            </a>

            <a href="sales.php">
                <span>↗</span> Sales
            </a>

            <a href="reports.php">
                <span>▤</span> Sales Reports
            </a>
        </nav>

        <div class="sidebar-footer">
            <span class="online-dot"></span>
            <div>
                <strong>System status</strong>
                <small>Database connected</small>
            </div>
        </div>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <header class="topbar">
            <div>
                <p class="eyebrow">INVENTORY WORKSPACE</p>
                <h1>Dashboard</h1>
            </div>

            <div class="admin">
                <div class="avatar">AD</div>
                <div>
                    <strong>Administrator</strong>
                    <small>Store Manager</small>
                </div>
            </div>
        </header>

        <!-- WELCOME SECTION -->
        <section class="welcome">
            <div>
                <p>OVERVIEW</p>
                <h2>Welcome back, Administrator!</h2>
                <span>
                    Here's what's happening with your store today.
                </span>
            </div>

            <a href="sales.php" class="button light-button">
                + Record a Sale
            </a>
        </section>

        <!-- STATISTICS CARDS -->
        <section class="stats-grid">

            <div class="stat-card">
                <div class="stat-heading">
                    <span>Total Products</span>
                    <span class="icon purple">□</span>
                </div>

                <h2>
                    <?= number_format((int)$totalProducts) ?>
                </h2>

                <p>Products in your catalog</p>
            </div>

            <div class="stat-card">
                <div class="stat-heading">
                    <span>Units in Stock</span>
                    <span class="icon blue">▤</span>
                </div>

                <h2>
                    <?= number_format((int)$totalStock) ?>
                </h2>

                <p>Total inventory quantity</p>
            </div>

            <div class="stat-card">
                <div class="stat-heading">
                    <span>Total Sales Revenue</span>
                    <span class="icon green">₱</span>
                </div>

                <h2 class="revenue">
                    <?= peso($totalRevenue) ?>
                </h2>

                <p>Revenue from recorded sales</p>
            </div>

            <div class="stat-card">
                <div class="stat-heading">
                    <span>Transactions</span>
                    <span class="icon orange">↗</span>
                </div>

                <h2>
                    <?= number_format((int)$totalTransactions) ?>
                </h2>

                <p>Recorded sales transactions</p>
            </div>

        </section>

        <!-- RECENT SALES AND STOCK ALERTS -->
        <section class="content-grid">

            <!-- RECENT SALES -->
            <div class="panel">

                <div class="panel-header">
                    <div>
                        <h3>Recent Sales</h3>
                        <p>Latest recorded transactions</p>
                    </div>

                    <a href="reports.php">View Reports →</a>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Date</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if (empty($recentSales)): ?>

                            <tr>
                                <td colspan="5" class="empty">
                                    No sales recorded yet.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($recentSales as $sale): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?= clean($sale["product_name"]) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= clean($sale["sku"]) ?>
                                    </td>

                                    <td>
                                        <?= (int)$sale["quantity"] ?>
                                    </td>

                                    <td class="amount">
                                        <?= peso($sale["total"]) ?>
                                    </td>

                                    <td>
                                        <?= date(
                                            "M j, Y",
                                            strtotime($sale["sold_at"])
                                        ) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- INVENTORY ALERTS -->
            <div class="panel">

                <div class="panel-header">
                    <div>
                        <h3>Inventory Alerts</h3>
                        <p>Products needing attention</p>
                    </div>

                    <span class="alert-count">
                        <?= count($lowStock) ?>
                    </span>
                </div>

                <div class="alert-list">

                    <?php if (empty($lowStock)): ?>

                        <p class="empty">
                            All products have sufficient stock.
                        </p>

                    <?php else: ?>

                        <?php foreach (
                            array_slice($lowStock, 0, 6) as $product
                        ): ?>

                            <div class="alert-item">

                                <div class="product-icon">□</div>

                                <div class="product-details">
                                    <strong>
                                        <?= clean($product["name"]) ?>
                                    </strong>

                                    <small>
                                        <?= clean($product["sku"]) ?>
                                    </small>
                                </div>

                                <?php if ((int)$product["stock"] === 0): ?>

                                    <span class="badge out">
                                        Out of stock
                                    </span>

                                <?php else: ?>

                                    <span class="badge low">
                                        <?= (int)$product["stock"] ?>
                                        left
                                    </span>

                                <?php endif; ?>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

                <a href="product.php"
                   class="button outline-button full-button">
                    Manage Inventory
                </a>

            </div>

        </section>

        <footer>
            <span>
                © <?= date("Y") ?> StockWise Product Management System
            </span>

            <span>HTML · CSS · JavaScript · PHP · MySQL</span>
        </footer>

    </main>

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
  const search = document.getElementById('tableSearch');
  if (search) {
    search.addEventListener('input', () => {
      const table = document.querySelector('table[id$="Table"]');
      if (!table) return;
      const term = search.value.toLowerCase().trim();
      table.querySelectorAll('tbody tr').forEach(row => {
        row.hidden = !row.innerText.toLowerCase().includes(term);
      });
    });
  }

  const product = document.getElementById('saleProduct');
  const quantity = document.getElementById('saleQuantity');
  const priceEl = document.getElementById('salePrice');
  const stockEl = document.getElementById('saleStock');
  const totalEl = document.getElementById('saleTotal');
  const saleForm = document.getElementById('saleForm');
  const peso = n => '₱' + Number(n || 0).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});

  function updateSaleEstimate() {
    if (!product || !quantity) return;
    const option = product.options[product.selectedIndex];
    const price = Number(option?.dataset.price || 0);
    const stock = Number(option?.dataset.stock || 0);
    const qty = Math.max(0, Number(quantity.value || 0));
    priceEl.textContent = peso(price);
    stockEl.textContent = option?.value ? stock + ' units' : '—';
    totalEl.textContent = peso(price * qty);
    quantity.max = stock || 1;
    quantity.setCustomValidity(option?.value && qty > stock ? 'Quantity exceeds available stock.' : '');
  }
  if (product) {
    product.addEventListener('change', updateSaleEstimate);
    quantity.addEventListener('input', updateSaleEstimate);
    updateSaleEstimate();
  }
  if (saleForm) {
    saleForm.addEventListener('submit', event => {
      const option = product.options[product.selectedIndex];
      const stock = Number(option?.dataset.stock || 0);
      const qty = Number(quantity.value || 0);
      if (!option?.value) {
        event.preventDefault();
        alert('Please select a product.');
      } else if (qty < 1 || qty > stock) {
        event.preventDefault();
        alert(stock === 0 ? 'This product is out of stock.' : `Only ${stock} unit(s) are available.`);
      } else if (!confirm(`Record sale of ${qty} × ${option.text.split(' — ')[0]}?`)) {
        event.preventDefault();
      }
    });
  }

  document.querySelectorAll('form[data-validate]').forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
      }
    });
  });
});

function exportTableCSV(tableId, filename) {
  const table = document.getElementById(tableId);
  if (!table) return;
  const rows = [...table.querySelectorAll('tr')].filter(row => !row.hidden);
  const csv = rows.map(row => [...row.querySelectorAll('th,td')].map(cell => {
    const value = cell.innerText.trim().replace(/"/g, '""');
    return `"${value}"`;
  }).join(',')).join('\r\n');
  const blob = new Blob(['\ufeff' + csv], {type:'text/csv;charset=utf-8;'});
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
}

</script>

</body>
</html>