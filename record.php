<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$db = databaseConnection();

// Filters
$search    = trim($_GET['search'] ?? '');
$dateFrom  = $_GET['date_from'] ?? '';
$dateTo    = $_GET['date_to']   ?? '';

// Build query
$where  = [];
$params = [];

if ($search) {
    $where[]          = "(items LIKE :search OR email LIKE :search2)";
    $params[':search']  = "%$search%";
    $params[':search2'] = "%$search%";
}
if ($dateFrom) {
    $where[]           = "DATE(date_of_purchase) >= :dfrom";
    $params[':dfrom']  = $dateFrom;
}
if ($dateTo) {
    $where[]           = "DATE(date_of_purchase) <= :dto";
    $params[':dto']    = $dateTo;
}

$sql   = "SELECT * FROM admin" . ($where ? " WHERE " . implode(" AND ", $where) : "") . " ORDER BY date_of_purchase DESC";
$stmt  = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totals
$totalRevSql   = "SELECT SUM(totalprice) FROM admin" . ($where ? " WHERE " . implode(" AND ", $where) : "");
$totalRevStmt  = $db->prepare($totalRevSql);
$totalRevStmt->execute($params);
$filteredRev   = $totalRevStmt->fetchColumn() ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sales Records — Allens Store Admin</title>
<link rel="stylesheet" href="Dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  .admin-content { width:100%; background:rgb(219,235,249); min-height:calc(100vh - 110px); padding:28px; }

  .filter-card {
    background:#fff; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.08);
    padding:18px 20px; margin-bottom:22px;
  }
  .filter-card form { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
  .filter-group { display:flex; flex-direction:column; gap:5px; }
  .filter-group label { font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; }
  .filter-group input {
    padding:8px 11px; border:1.5px solid #c8d6ed; border-radius:6px;
    font-size:13px; color:#172036; outline:none; transition:.2s;
    font-family:Arial,Helvetica,sans-serif;
  }
  .filter-group input:focus { border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.1); }
  .filter-actions { display:flex; gap:8px; }

  .summary-strip {
    display:flex; gap:16px; margin-bottom:22px; flex-wrap:wrap;
  }
  .ss-card {
    background:#fff; border-radius:10px; padding:16px 20px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08); flex:1; min-width:160px;
    display:flex; align-items:center; gap:14px;
  }
  .ss-icon { width:40px; height:40px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
  .ss-icon.blue   { background:#eff6ff; color:#3b82f6; }
  .ss-icon.green  { background:#f0fdf4; color:#16a34a; }
  .ss-val   { font-size:1.25rem; font-weight:800; color:#172036; line-height:1; }
  .ss-label { font-size:11px; color:#6b7280; margin-top:3px; font-weight:600; text-transform:uppercase; letter-spacing:.05em; }

  .records-card { background:#fff; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.08); overflow:hidden; }
  .rc-header {
    background:#172036; padding:13px 18px;
    display:flex; align-items:center; justify-content:space-between;
  }
  .rc-header h3 { font-size:13px; font-weight:800; color:#fff; display:flex; align-items:center; gap:8px; }
  .rc-header h3 i { color:orange; }
  .rc-header span { font-size:12px; color:rgba(255,255,255,0.55); }

  .admin-table { width:100%; border-collapse:collapse; font-size:13px; }
  .admin-table thead tr { background:#f4f8ff; }
  .admin-table thead th {
    padding:10px 16px; text-align:left; font-size:11px; font-weight:700;
    color:#6b7280; text-transform:uppercase; letter-spacing:.05em; border-bottom:1px solid #eef2fb;
  }
  .admin-table tbody tr { border-bottom:1px solid #eef2fb; transition:.15s; }
  .admin-table tbody tr:last-child { border-bottom:none; }
  .admin-table tbody tr:hover { background:#f4f8ff; }
  .admin-table td { padding:12px 16px; color:#172036; font-weight:600; vertical-align:middle; }
  .admin-table td.price  { color:#2563eb; font-weight:800; }
  .admin-table td.email  { color:#6b7280; font-weight:500; font-size:12px; }
  .admin-table td.date   { color:#9aabcc; font-size:12px; }

  .row-id { background:#eaf2ff; color:#3b82f6; font-size:11px; font-weight:800; padding:3px 8px; border-radius:999px; }
  .no-data { text-align:center; padding:50px 20px; color:#9aabcc; font-size:13px; }
  .no-data i { display:block; font-size:2.5rem; margin-bottom:10px; }

  @media(max-width:700px){
    .admin-table thead { display:none; }
    .admin-table td { display:block; padding:6px 16px; }
    .admin-table tr { border-bottom:2px solid #eef2fb; padding:8px 0; display:block; }
  }
</style>
</head>
<body>
<div class="container">

  <!-- NAVBAR -->
  <div class="bar1">
    <div class="logo">Allens <i class="fa-solid fa-store"></i> <span style="font-size:11px;background:orange;padding:2px 8px;border-radius:999px;margin-left:6px;font-weight:700;">ADMIN</span></div>
    <div class="search_box">
      <input type="text" placeholder="Search...">
      <button><i class="fa-solid fa-magnifying-glass"></i></button>
    </div>
    <div class="nav_buttons">
      <a href="admin.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
      <a href="record.php" class="active"><i class="fa-solid fa-receipt"></i> Records</a>
      <a href="inventory.php"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a>
      <a href="dashboard.php"><i class="fa-solid fa-store"></i> Store</a>
      <a href="login.php" onclick="return confirm('Log out?')"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
  </div>

  <div class="bar2">
    <div class="text">Admin Panel &nbsp;|&nbsp; Sales Records &nbsp;|&nbsp; Inventory Management &nbsp;|&nbsp; Store Overview</div>
  </div>

  <div class="admin-content">

    <div class="page-title">
      <i class="fa-solid fa-receipt"></i> Sales Records
    </div>

    <!-- Filters -->
    <div class="filter-card">
      <form method="GET" action="record.php">
        <div class="filter-group">
          <label>Search item / email</label>
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="e.g. Shirt or user@mail.com" style="width:220px;">
        </div>
        <div class="filter-group">
          <label>From Date</label>
          <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
        </div>
        <div class="filter-group">
          <label>To Date</label>
          <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
        </div>
        <div class="filter-actions">
          <button type="submit" class="btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
          <a href="record.php" class="btn-secondary" style="text-decoration:none;display:flex;align-items:center;gap:6px;"><i class="fa-solid fa-rotate-left"></i> Reset</a>
        </div>
      </form>
    </div>

    <!-- Summary -->
    <div class="summary-strip">
      <div class="ss-card">
        <div class="ss-icon blue"><i class="fa-solid fa-receipt"></i></div>
        <div>
          <div class="ss-val"><?= number_format(count($orders)) ?></div>
          <div class="ss-label"><?= ($search || $dateFrom || $dateTo) ? 'Filtered Orders' : 'Total Orders' ?></div>
        </div>
      </div>
      <div class="ss-card">
        <div class="ss-icon green"><i class="fa-solid fa-naira-sign"></i></div>
        <div>
          <div class="ss-val">₦<?= number_format((float)$filteredRev, 0) ?></div>
          <div class="ss-label"><?= ($search || $dateFrom || $dateTo) ? 'Filtered Revenue' : 'Total Revenue' ?></div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="records-card">
      <div class="rc-header">
        <h3><i class="fa-solid fa-table-list"></i> All Sales</h3>
        <span><?= count($orders) ?> record<?= count($orders) !== 1 ? 's' : '' ?></span>
      </div>

      <?php if (empty($orders)): ?>
        <p class="no-data">
          <i class="fa-solid fa-inbox"></i>
          No records found<?= ($search || $dateFrom || $dateTo) ? ' for your filter.' : '.' ?>
        </p>
      <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Item(s)</th>
            <th>Customer Email</th>
            <th>Total Price</th>
            <th>Date of Purchase</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $row): ?>
          <tr>
            <td><span class="row-id">#<?= htmlspecialchars($row['id']) ?></span></td>
            <td><?= htmlspecialchars($row['items']) ?></td>
            <td class="email"><i class="fa-regular fa-envelope" style="margin-right:5px;"></i><?= htmlspecialchars($row['email']) ?></td>
            <td class="price">₦<?= number_format((float)$row['totalprice'], 0) ?></td>
            <td class="date"><i class="fa-regular fa-calendar" style="margin-right:5px;"></i><?= htmlspecialchars($row['date_of_purchase']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

  </div>
</div>
</body>
</html>
