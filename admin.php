<?php
session_start();
require_once 'db_connection.php';

// Simple admin guard — reuse session id or add your own admin check
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$db = databaseConnection();

// Quick stats for dashboard cards
$totalSales  = $db->query("SELECT COUNT(*) FROM admin")->fetchColumn();
$totalRev    = $db->query("SELECT SUM(totalprice) FROM admin")->fetchColumn() ?? 0;
// $totalProds  = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$recentOrders = $db->query("SELECT * FROM admin ORDER BY date_of_purchase DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel — Allens Store</title>
<link rel="stylesheet" href="Dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  /* ── Admin-specific styles ── */
  .admin-content { width:100%; background:rgb(219,235,249); min-height:calc(100vh - 110px); padding:28px; }

  .admin-welcome {
    background:linear-gradient(135deg,#172036,#1e3a6e);
    border-radius:12px; padding:28px 32px; color:#fff;
    margin-bottom:28px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px;
  }
  .admin-welcome h2 { font-size:1.4rem; font-weight:800; margin-bottom:4px; }
  .admin-welcome p  { font-size:13px; color:rgba(255,255,255,0.6); }
  .admin-welcome .badge {
    background:orange; color:#fff; font-size:11px; font-weight:800;
    padding:4px 12px; border-radius:999px; letter-spacing:.05em;
  }

  .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:18px; margin-bottom:28px; }

  .stat-card {
    background:#fff; border-radius:10px; padding:20px 18px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    display:flex; align-items:center; gap:16px; transition:.2s;
  }
  .stat-card:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,0.12); }
  .stat-icon {
    width:46px; height:46px; border-radius:10px;
    display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0;
  }
  .stat-icon.blue   { background:#eff6ff; color:#3b82f6; }
  .stat-icon.green  { background:#f0fdf4; color:#16a34a; }
  .stat-icon.orange { background:#fff7ed; color:#ea580c; }
  .stat-val  { font-size:1.4rem; font-weight:800; color:#172036; line-height:1; }
  .stat-label{ font-size:11px; color:#6b7280; margin-top:4px; font-weight:600; text-transform:uppercase; letter-spacing:.05em; }

  .quick-links { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:18px; margin-bottom:28px; }

  .quick-card {
    background:#fff; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.08);
    padding:26px 22px; text-align:center; transition:.25s; text-decoration:none;
    display:flex; flex-direction:column; align-items:center; gap:12px;
  }
  .quick-card:hover { transform:translateY(-4px); box-shadow:0 10px 24px rgba(0,0,0,0.13); }
  .quick-card .qc-icon { width:56px; height:56px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; }
  .quick-card.rec .qc-icon { background:#eff6ff; color:#3b82f6; }
  .quick-card.inv .qc-icon { background:#fff7ed; color:#ea580c; }
  .quick-card h4 { font-size:15px; font-weight:800; color:#172036; }
  .quick-card p  { font-size:12px; color:#6b7280; }

  .recent-table-card { background:#fff; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.08); overflow:hidden; }
  .rtc-header {
    background:#172036; padding:13px 18px;
    display:flex; align-items:center; justify-content:space-between;
  }
  .rtc-header h3 { font-size:13px; font-weight:800; color:#fff; display:flex; align-items:center; gap:8px; }
  .rtc-header h3 i { color:orange; }
  .rtc-header a { font-size:12px; color:rgba(255,255,255,0.6); text-decoration:none; transition:.2s; }
  .rtc-header a:hover { color:orange; }

  .admin-table { width:100%; border-collapse:collapse; font-size:13px; }
  .admin-table thead tr { background:#f4f8ff; }
  .admin-table thead th { padding:10px 16px; text-align:left; font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; border-bottom:1px solid #eef2fb; }
  .admin-table tbody tr { border-bottom:1px solid #eef2fb; transition:.15s; }
  .admin-table tbody tr:last-child { border-bottom:none; }
  .admin-table tbody tr:hover { background:#f4f8ff; }
  .admin-table td { padding:11px 16px; color:#172036; font-weight:600; }
  .admin-table td.price { color:#2563eb; font-weight:800; }
  .admin-table td.email { color:#6b7280; font-weight:500; }
  .no-data { text-align:center; padding:40px; color:#9aabcc; font-size:13px; }
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
      <a href="admin.php" class="active"><i class="fa-solid fa-gauge"></i> Dashboard</a>
      <a href="record.php"><i class="fa-solid fa-receipt"></i> Records</a>
      <a href="inventory.php"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a>
      <a href="dashboard.php"><i class="fa-solid fa-store"></i> Store</a>
      <a href="login.php" onclick="return confirm('Log out?')"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
  </div>

  <!-- CATEGORY BAR -->
  <div class="bar2">
    <div class="text">Admin Panel &nbsp;|&nbsp; Sales Records &nbsp;|&nbsp; Inventory Management &nbsp;|&nbsp; Store Overview</div>
  </div>

  <div class="admin-content">

    <!-- Welcome Banner -->
    <div class="admin-welcome">
      <div>
        <h2><i class="fa-solid fa-shield-halved" style="color:orange;margin-right:8px;"></i>Admin Dashboard</h2>
        <p>Welcome back. Here's what's happening at Allens Store today.</p>
      </div>
      <span class="badge">ADMIN ACCESS</span>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-receipt"></i></div>
        <div>
          <div class="stat-val"><?= number_format((int)$totalSales) ?></div>
          <div class="stat-label">Total Orders</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-naira-sign"></i></div>
        <div>
          <div class="stat-val">₦<?= number_format((float)$totalRev, 0) ?></div>
          <div class="stat-label">Total Revenue</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-boxes-stacked"></i></div>
        <div>
          <!-- <div class="stat-val"><?= number_format((int)$totalProds) ?></div> -->
          <div class="stat-label">Products Listed</div>
        </div>
      </div>
    </div>

    <!-- Quick Links -->
    <div class="quick-links">
      <a href="record.php" class="quick-card rec">
        <div class="qc-icon"><i class="fa-solid fa-receipt"></i></div>
        <h4>Sales Records</h4>
        <p>View all orders, customers and purchase history</p>
      </a>
      <a href="inventory.php" class="quick-card inv">
        <div class="qc-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
        <h4>Inventory</h4>
        <p>Add products, upload images and edit prices</p>
      </a>
    </div>

    <!-- Recent Orders -->
    <div class="recent-table-card">
      <div class="rtc-header">
        <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Orders</h3>
        <a href="record.php">View all <i class="fa-solid fa-arrow-right"></i></a>
      </div>
      <?php if (empty($recentOrders)): ?>
        <p class="no-data"><i class="fa-solid fa-inbox" style="display:block;font-size:2rem;margin-bottom:8px;"></i>No orders yet.</p>
      <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th>#</th><th>Item</th><th>Email</th><th>Total Price</th><th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentOrders as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['id']) ?></td>
            <td><?= htmlspecialchars($row['items']) ?></td>
            <td class="email"><?= htmlspecialchars($row['email']) ?></td>
            <td class="price">₦<?= number_format((float)$row['totalprice'], 0) ?></td>
            <td><?= htmlspecialchars($row['date_of_purchase']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

  </div><!-- /admin-content -->
</div><!-- /container -->
</body>
</html>
