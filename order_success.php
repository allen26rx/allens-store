<?php
session_start();
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Confirmed — Allens Store</title>
<link rel="stylesheet" href="Dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  .success-wrapper {
    min-height: calc(100vh - 110px);
    background: rgb(219,235,249);
    display: flex; align-items: center; justify-content: center;
    padding: 40px 20px;
  }
  .success-card {
    background: #fff; border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    padding: 50px 40px; max-width: 480px; width: 100%;
    text-align: center;
  }
  .success-icon {
    width: 80px; height: 80px; border-radius: 50%;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 24px; font-size: 2.2rem; color: #fff;
    box-shadow: 0 8px 24px rgba(34,197,94,0.35);
    animation: popIn .4s cubic-bezier(.175,.885,.32,1.275) both;
  }
  @keyframes popIn {
    from { transform: scale(0); opacity: 0; }
    to   { transform: scale(1); opacity: 1; }
  }
  .success-card h2 { font-size: 1.5rem; font-weight: 800; color: #172036; margin-bottom: 10px; }
  .success-card p  { font-size: 13.5px; color: #6b7280; line-height: 1.7; margin-bottom: 24px; }
  .success-ref {
    background: #f4f8ff; border-radius: 8px; padding: 10px 16px;
    font-size: 12px; color: #3b82f6; font-weight: 700;
    margin-bottom: 28px; word-break: break-all;
  }
  .btn-row { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
  .btn-row a {
    padding: 11px 22px; border-radius: 8px; font-size: 13px; font-weight: 700;
    text-decoration: none; transition: .2s; display: flex; align-items: center; gap: 7px;
  }
  .btn-primary-link { background: #172036; color: #fff; }
  .btn-primary-link:hover { background: orange; }
  .btn-outline-link  { background: #fff; color: #172036; border: 1.5px solid #c8d6ed; }
  .btn-outline-link:hover { border-color: #172036; }
  .confetti-bar {
    height: 4px;
    background: linear-gradient(90deg, orange, #3b82f6, #22c55e, orange);
    border-radius: 999px; margin-bottom: 28px;
    background-size: 200%; animation: slide 2s linear infinite;
  }
  @keyframes slide { to { background-position: 200% center; } }
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
      <a href="cart.php">Cart <i class="fa-solid fa-cart-shopping"></i></a>
      <a href="contact.php">Contact</a>
      <a href="about.php">About</a>
      <a href="profile.php">Profile <i class="fa-regular fa-user"></i></a>
    </div>
  </div>
  <div class="bar2">
    <div class="text">Fashion | Phone &amp; Tablets | Health &amp; Beauty | Electronics | Gaming</div>
  </div>

  <div class="success-wrapper">
    <div class="success-card">
      <div class="confetti-bar"></div>
      <div class="success-icon"><i class="fa-solid fa-check"></i></div>
      <h2>Order Confirmed! 🎉</h2>
      <p>
        Thank you for shopping at Allens Store. Your payment was successful
        and your order is being processed. You'll receive a confirmation soon.
      </p>
      <div class="btn-row">
        <a href="orders.php" class="btn-primary-link">
          <i class="fa-solid fa-receipt"></i> View My Orders
        </a>
        <a href="dashboard.php" class="btn-outline-link">
          <i class="fa-solid fa-store"></i> Keep Shopping
        </a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
