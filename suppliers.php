<?php
include "dbcon.php";

// Add or update supplier
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST["id"] ?? "";
    $name = trim($_POST["company_name"] ?? "");
    $contactPerson = trim($_POST["contact_person"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");

    if ($name === "") {
        $message = "Supplier name is required.";
    } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {
        try {
            if ($id !== "") {
                $stmt = $pdo->prepare(
                    "UPDATE suppliers
                     SET name = ?, contact_person = ?, email = ?,
                         phone = ?, address = ?
                     WHERE id = ?"
                );

                $stmt->execute([
                    $name, $contactPerson, $email, $phone, $address, $id
                ]);

                $message = "Supplier updated successfully!";
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO suppliers
                     (company_name, contact_person, email, phone, address)
                     VALUES (?, ?, ?, ?, ?)"
                );

                $stmt->execute([
                    $name, $contactPerson, $email, $phone, $address
                ]);

                $message = "Supplier added successfully!";
            }
        } catch (PDOException $e) {
            $message = "Unable to save supplier. Please check your database.";
        }
    }

    header("Location: suppliers.php?message=" . urlencode($message));
    exit;
}

// Delete supplier
if (isset($_GET["delete"])) {
    $id = filter_input(INPUT_GET, "delete", FILTER_VALIDATE_INT);

    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Supplier deleted successfully!";
        } catch (PDOException $e) {
            $message = "This supplier cannot be deleted because it may be linked to products.";
        }
    } else {
        $message = "Invalid supplier ID.";
    }

    header("Location: suppliers.php?message=" . urlencode($message));
    exit;
}

// Get supplier for editing
$editSupplier = null;

if (isset($_GET["edit"])) {
    $id = filter_input(INPUT_GET, "edit", FILTER_VALIDATE_INT);

    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        $editSupplier = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Search suppliers
$search = trim($_GET["search"] ?? "");

$stmt = $pdo->prepare(
    "SELECT * FROM suppliers
     WHERE company_name LIKE ?
        OR contact_person LIKE ?
        OR email LIKE ?
        OR phone LIKE ?
     ORDER BY id DESC"
);

$keyword = "%" . $search . "%";
$stmt->execute([$keyword, $keyword, $keyword, $keyword]);
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Management | ProductMS</title>
    
</head>
<style>
    /* Main page heading */
    .topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .topbar h1 {
        margin: 0 0 8px;
        color: #0f172a;
        font-size: 28px;
    }

    .topbar p {
        margin: 0;
        color: #64748b;
    }

    /* Supplier form and list panels */
    .panel {
        background: #ffffff;
        padding: 24px;
        margin-bottom: 24px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
    }

    .panel h2 {
        margin: 0 0 20px;
        color: #172554;
        font-size: 20px;
    }

    /* Reuse form styles */
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
        align-items: center;
    }

    /* Buttons */
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
        color: #ffffff;
    }

    .btn-primary:hover {
        background: #1d4ed8;
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

    .btn-delete:hover {
        background: #fecaca;
    }

    /* Search and table */
    .section-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .section-heading h2 {
        margin-bottom: 0;
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
        color: #475569;
        font-size: 14px;
    }

    tbody tr:hover {
        background: #f8fafc;
    }

    .action-buttons {
        display: flex;
        gap: 6px;
    }

    /* Feedback message */
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

        .section-heading {
            align-items: stretch;
        }

        .search-form input {
            min-width: 0;
            width: 100%;
            box-sizing: border-box;
        }

        .panel {
            padding: 16px;
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

<div class="sidebar">
    <h2>ProductMS</h2>
    <a href="dashboard.php">Dashboard</a>
    <a href="product.php">Products</a>
    <a href="suppliers.php" class="active">Suppliers</a>
    <a href="sales.php">Sales</a>
    <a href="reports.php">Reports</a>
</div>

<main class="main-content">

    <header class="topbar">
        <div>
            <h1>Supplier Management</h1>
            <p>Manage your suppliers and their contact information.</p>
        </div>
    </header>

    <?php if (isset($_GET["message"])): ?>
        <div class="notice">
            <?= clean($_GET["message"]) ?>
        </div>
    <?php endif; ?>

    <!-- Supplier Form -->
    <section class="panel">
        <h2>
            <?= $editSupplier ? "Edit Supplier" : "Add New Supplier" ?>
        </h2>

        <form method="POST" action="suppliers.php" class="product-form">

            <input type="hidden" name="id"
                   value="<?= clean($editSupplier["id"] ?? "") ?>">

            <div class="form-group">
                <label for="company_name">Supplier Name </label>
                <input type="text" id="company_name" name="company_name"
                       placeholder="Enter supplier name"
                       value="<?= clean($editSupplier["company_name"] ?? "") ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="contact_person">Contact Person</label>
                <input type="text" id="contact_person"
                       name="contact_person"
                       placeholder="Enter contact person"
                       value="<?= clean($editSupplier["contact_person"] ?? "") ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       placeholder="supplier@example.com"
                       value="<?= clean($editSupplier["email"] ?? "") ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone"
                       placeholder="Enter phone number"
                       value="<?= clean($editSupplier["phone"] ?? "") ?>">
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address"
                       placeholder="Enter supplier address"
                       value="<?= clean($editSupplier["address"] ?? "") ?>">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <?= $editSupplier ? "Update Supplier" : "Add Supplier" ?>
                </button>

                <?php if ($editSupplier): ?>
                    <a href="suppliers.php" class="btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <!-- Supplier List -->
    <section class="panel">
        <div class="section-heading">
            <h2>Supplier List</h2>

            <form method="GET" action="suppliers.php" class="search-form">
                <input type="search" name="search"
                       placeholder="Search suppliers..."
                       value="<?= clean($search) ?>">

                <button type="submit" class="btn-primary">Search</button>
                <a href="suppliers.php" class="btn-secondary">Reset</a>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Supplier Name</th>
                        <th>Contact Person</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($suppliers)): ?>
                        <tr>
                            <td colspan="7">No suppliers found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr>
                                <td><?= clean($supplier["company_name"]) ?></td>
                                <td><?= clean($supplier["contact_person"]) ?></td>
                                <td><?= clean($supplier["email"]) ?></td>
                                <td><?= clean($supplier["phone"]) ?></td>
                                <td><?= clean($supplier["address"]) ?></td>
                                <td class="action-buttons">
                                    <a class="btn-edit"
                                       href="suppliers.php?edit=<?= (int)$supplier["id"] ?>">
                                        Edit
                                    </a>

                                    <button type="button" class="btn-delete"
                                        onclick="confirmSupplierDelete(
                                            <?= (int)$supplier["id"] ?>,
                                            <?= htmlspecialchars(
                                                json_encode($supplier["company_name"]),
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        )">
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
<script>
    function confirmSupplierDelete(id, name) {
    const confirmed = confirm(
        `Are you sure you want to delete supplier "${name}"?`);

        if (confirmed) {
            window.location.href = `suppliers.php?delete=${id}`;
        }
    }   
</script>
</body>
</html>