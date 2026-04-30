<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$db     = databaseConnection();
$userId = (int)$_SESSION['id'];

// Fetch user info
$stmt = $db->prepare("SELECT first_name, last_name, email FROM allens WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

// Fetch orders for this user
$stmt = $db->prepare("
    SELECT * FROM orders
    WHERE user_id = :uid
    ORDER BY created_at DESC
");
$stmt->execute([':uid' => $userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cart_count = array_sum(array_column($_SESSION['cart'] ?? [], 'qty'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders — Allens Store</title>
<link rel="stylesheet" href="Dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  .orders-wrapper {
    width: 100%; background: rgb(219,235,249);
    min-height: calc(100vh - 110px); padding: 28px;
  }
  .orders-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 22px; flex-wrap: wrap; gap: 12px;
  }
  .orders-header a {
    font-size: 13px; color: #172036; text-decoration: none; font-weight: 700;
    display: flex; align-items: center; gap: 6px; padding: 8px 14px;
    background: #fff; border-radius: 7px; box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    transition: .2s;
  }
  .orders-header a:hover { background: #172036; color: #fff; }

  /* Stats */
  .order-stats { display:flex; gap:16px; margin-bottom:22px; flex-wrap:wrap; }
  .o-stat {
    background:#fff; border-radius:10px; padding:16px 20px;
    box-shadow:0 4px 12px rgba(0,0,0,0.07); flex:1; min-width:140px;
    display:flex; align-items:center; gap:12px;
  }
  .o-stat-icon { width:38px; height:38px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1rem; }
  .o-stat-icon.blue { background:#eff6ff; color:#3b82f6; }
  .o-stat-icon.green { background:#f0fdf4; color:#16a34a; }
  .o-stat-val { font-size:1.2rem; font-weight:800; color:#172036; line-height:1; }
  .o-stat-label { font-size:10px; color:#6b7280; font-weight:700; text-transform:uppercase; letter-spacing:.05em; margin-top:3px; }

  /* Orders list */
  .orders-card {
    background:#fff; border-radius:12px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08); overflow:hidden;
  }
  .orders-card-header {
    background:#172036; padding:13px 20px;
    display:flex; align-items:center; justify-content:space-between;
  }
  .orders-card-header h3 { font-size:13px; font-weight:800; color:#fff; display:flex; align-items:center; gap:8px; }
  .orders-card-header h3 i { color:orange; }
  .orders-card-header span { font-size:12px; color:rgba(255,255,255,0.5); }

  /* Each order row */
  .order-row {
    border-bottom: 1px solid #eef2fb; padding: 16px 20px;
    display: grid;
    grid-template-columns: auto 1fr auto auto;
    gap: 16px; align-items: center;
    transition: .15s; cursor: pointer;
  }
  .order-row:last-child { border-bottom: none; }
  .order-row:hover { background: #f4f8ff; }

  .order-num {
    background: #eaf2ff; color: #3b82f6; font-size:11px; font-weight:800;
    padding:4px 10px; border-radius:999px; white-space:nowrap;
  }

  .order-items { font-size:13px; font-weight:600; color:#172036; }
  .order-date  { font-size:11px; color:#9aabcc; margin-top:3px; }

  .order-price { font-size:14px; font-weight:800; color:#2563eb; white-space:nowrap; }

  .order-badge {
    font-size:10px; font-weight:800; padding:4px 10px;
    border-radius:999px; text-transform:uppercase; letter-spacing:.05em;
    white-space:nowrap;
  }
  .badge-paid     { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
  .badge-pending  { background:#fff7ed; color:#ea580c; border:1px solid #fed7aa; }
  .badge-cancelled{ background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }

  /* Expanded detail */
  .order-detail {
    display:none; background:#f4f8ff; border-top:1px solid #eef2fb;
    padding:14px 20px; font-size:12.5px; color:#4a5978;
  }
  .order-detail.open { display:block; }
  .order-detail .ref { font-size:11px; color:#9aabcc; margin-top:6px; word-break:break-all; }

  /* Empty state */
  .no-orders { text-align:center; padding:60px 20px; color:#9aabcc; }
  .no-orders i { font-size:3rem; display:block; margin-bottom:12px; }
  .no-orders a { color:orange; font-weight:700; text-decoration:none; }

  @media(max-width:600px) {
    .order-row { grid-template-columns:1fr 1fr; }
    .order-num { display:none; }
  }
</style>
</head>
<body>
<div class="container">

  <div class="bar1">
    <div class="logo">Allens <i class="fa-solid fa-store"></i></div>
    <div class="search_box">
      <input type="text" placeholder="Search products...">
      <button><i class="fa-solid fa-magnifying-glass"></i></button>
    </div>
    <div class="nav_buttons">
      <a href="dashboard.php">Products</a>
      <a href="cart.php">
        Cart <i class="fa-solid fa-cart-shopping"></i>
        <?php if ($cart_count > 0): ?><span class="cart-badge"><?= $cart_count ?></span><?php endif; ?>
      </a>
      <a href="contact.php">Contact</a>
      <a href="about.php">About</a>
      <a href="profile.php" class="active">Profile <i class="fa-regular fa-user"></i></a>
    </div>
  </div>

  <div class="bar2">
    <div class="text">Fashion | Phone &amp; Tablets | Health &amp; Beauty | Electronics | Gaming</div>
  </div>

  <div class="orders-wrapper">

    <div class="orders-header">
      <div class="page-title" style="margin:0;">
        <i class="fa-solid fa-receipt"></i> My Orders
      </div>
      <a href="profile.php"><i class="fa-solid fa-arrow-left"></i> Back to Profile</a>
    </div>

    <!-- Stats -->
    <?php
      $total_spent = array_sum(array_column($orders, 'totalprice'));
      $total_orders = count($orders);
    ?>
    <div class="order-stats">
      <div class="o-stat">
        <div class="o-stat-icon blue"><i class="fa-solid fa-receipt"></i></div>
        <div>
          <div class="o-stat-val"><?= $total_orders ?></div>
          <div class="o-stat-label">Total Orders</div>
        </div>
      </div>
      <div class="o-stat">
        <div class="o-stat-icon green"><i class="fa-solid fa-naira-sign"></i></div>
        <div>
          <div class="o-stat-val">₦<?= number_format($total_spent, 0) ?></div>
          <div class="o-stat-label">Total Spent</div>
        </div>
      </div>
    </div>

    <!-- Orders Table -->
    <div class="orders-card">
      <div class="orders-card-header">
        <h3><i class="fa-solid fa-bag-shopping"></i> Order History</h3>
        <span><?= $total_orders ?> order<?= $total_orders !== 1 ? 's' : '' ?></span>
      </div>

      <?php if (empty($orders)): ?>
        <div class="no-orders">
          <i class="fa-solid fa-bag-shopping"></i>
          <p>You haven't placed any orders yet.</p>
          <a href="dashboard.php"><i class="fa-solid fa-store"></i> Start Shopping</a>
        </div>
      <?php else: ?>
        <?php foreach ($orders as $i => $order): ?>
        <div class="order-row" onclick="toggleDetail(<?= $i ?>)">
          <span class="order-num">#<?= htmlspecialchars($order['id']) ?></span>
          <div>
            <div class="order-items"><?= htmlspecialchars($order['items']) ?></div>
            <div class="order-date">
              <i class="fa-regular fa-calendar" style="margin-right:3px;"></i>
              <?= date('d M Y, g:i A', strtotime($order['created_at'])) ?>
            </div>
          </div>
          <div class="order-price">₦<?= number_format((float)$order['totalprice'], 0) ?></div>
          <span class="order-badge badge-<?= htmlspecialchars($order['status'] ?? 'paid') ?>">
            <?= htmlspecialchars($order['status'] ?? 'paid') ?>
          </span>
        </div>
        <div class="order-detail" id="detail-<?= $i ?>">
          <strong>Items ordered:</strong> <?= htmlspecialchars($order['items']) ?><br>
          <strong>Amount paid:</strong> ₦<?= number_format((float)$order['totalprice'], 0) ?>
          <?php if (!empty($order['reference'])): ?>
          <div class="ref"><i class="fa-solid fa-tag"></i> Ref: <?= htmlspecialchars($order['reference']) ?></div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</div>

<script>
function toggleDetail(i) {
    const el = document.getElementById('detail-' + i);
    el.classList.toggle('open');
}
</script>
</body>
</html>
