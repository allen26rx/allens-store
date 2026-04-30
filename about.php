<?php
session_start();
require_once 'db_connection.php';
$cart        = $_SESSION['cart'] ?? [];
$total_items = array_sum(array_column($cart, 'qty'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About Us — Allens Store</title>
<link rel="stylesheet" href="Dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  /* ── Hero Banner ── */
  .about-hero {
    width: 100%;
    background: linear-gradient(135deg, #172036 0%, #1e3a6e 100%);
    padding: 64px 40px;
    text-align: center;
    color: #fff;
  }
  .about-hero h1 {
    font-size: 2.2rem;
    font-weight: 800;
    margin-bottom: 12px;
    letter-spacing: -0.02em;
  }
  .about-hero h1 span { color: orange; }
  .about-hero p {
    font-size: 15px;
    color: rgba(255,255,255,0.6);
    max-width: 520px;
    margin: 0 auto;
    line-height: 1.7;
  }
  .about-hero .hero-divider {
    width: 48px; height: 4px;
    background: orange; border-radius: 999px;
    margin: 20px auto 0;
  }

  /* ── Page Wrapper override ── */
  .about-wrapper {
    width: 100%;
    background: rgb(219,235,249);
    padding: 40px 28px 70px;
    min-height: calc(100vh - 200px);
  }

  /* ── Section Card ── */
  .about-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    overflow: hidden;
    margin-bottom: 24px;
  }
  .about-card-header {
    background: #172036;
    padding: 14px 22px;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .about-card-header h3 {
    font-size: 13px;
    font-weight: 800;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
  }
  .about-card-header h3 i { color: orange; }
  .about-card-body {
    padding: 28px 26px;
  }

  /* ── Story Section ── */
  .story-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 32px;
    align-items: center;
  }
  @media(max-width: 680px) { .story-grid { grid-template-columns: 1fr; } }

  .story-text h2 {
    font-size: 1.4rem;
    font-weight: 800;
    color: #172036;
    margin-bottom: 14px;
    line-height: 1.3;
  }
  .story-text h2 span { color: orange; }
  .story-text p {
    font-size: 13.5px;
    color: #4a5978;
    line-height: 1.8;
    margin-bottom: 14px;
  }

  .story-image-box {
    background: #eaf2ff;
    border-radius: 12px;
    min-height: 240px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 10px;
    color: #93b4d8;
    border: 2px dashed #c8d6ed;
  }
  .story-image-box i { font-size: 3rem; }
  .story-image-box span { font-size: 12px; font-weight: 600; }

  /* ── Stats Row ── */
  .stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
  }
  .stat-pill {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.07);
    padding: 22px 16px;
    text-align: center;
    border-top: 3px solid orange;
    transition: .2s;
  }
  .stat-pill:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.11); }
  .stat-pill .stat-num {
    font-size: 1.9rem;
    font-weight: 800;
    color: #172036;
    line-height: 1;
    margin-bottom: 6px;
  }
  .stat-pill .stat-num span { color: orange; }
  .stat-pill .stat-desc {
    font-size: 11px;
    color: #6b7280;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
  }

  /* ── Values Grid ── */
  .values-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 18px;
  }
  .value-item {
    background: #f4f8ff;
    border-radius: 10px;
    padding: 22px 18px;
    border-left: 4px solid orange;
    transition: .2s;
  }
  .value-item:hover { background: #eaf2ff; transform: translateX(3px); }
  .value-item .vi-icon {
    width: 40px; height: 40px;
    background: #fff;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; color: orange;
    margin-bottom: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
  }
  .value-item h4 {
    font-size: 13px;
    font-weight: 800;
    color: #172036;
    margin-bottom: 6px;
  }
  .value-item p {
    font-size: 12px;
    color: #6b7280;
    line-height: 1.6;
    margin: 0;
  }

  /* ── Team Grid ── */
  .team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
  }
  .team-card {
    background: #f4f8ff;
    border-radius: 12px;
    padding: 24px 16px;
    text-align: center;
    transition: .2s;
  }
  .team-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
  .team-avatar {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, #172036, #1e3a6e);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 14px;
    font-size: 1.6rem; color: orange;
    border: 3px solid #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
  }
  .team-card h4 { font-size: 13px; font-weight: 800; color: #172036; margin-bottom: 4px; }
  .team-card span { font-size: 11px; color: #6b7280; font-weight: 600; }

  /* ── Placeholder text hint ── */
  .placeholder-hint {
    font-size: 12px;
    color: #b0bcd8;
    font-style: italic;
    margin-top: 6px;
  }
</style>
</head>
<body>
<div class="container">

  <!-- NAVBAR -->
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
        <?php if ($total_items > 0): ?>
          <span class="cart-badge"><?= $total_items ?></span>
        <?php endif; ?>
      </a>
      <a href="contact.php">Contact</a>
      <a href="about.php" class="active">About</a>
      <a href="profile.php">Profile <i class="fa-regular fa-user"></i></a>
    </div>
  </div>

  <!-- CATEGORY BAR -->
  <div class="bar2">
    <div class="text">Fashion | Phone &amp; Tablets | Health &amp; Beauty | Electronics | Gaming</div>
  </div>

  <!-- HERO -->
  <div class="about-hero">
    <h1>About <span>Allens Store</span></h1>
    <p><!-- TYPE YOUR TAGLINE / SHORT DESCRIPTION HERE --></p>
    <div class="hero-divider"></div>
  </div>

  <div class="about-wrapper">

    <!-- ── STATS ── -->
    <div class="stats-row">
      <div class="stat-pill">
        <div class="stat-num"><!-- 0 --><span>+</span></div>
        <div class="stat-desc">Happy Customers</div>
      </div>
      <div class="stat-pill">
        <div class="stat-num"><!-- 0 --><span>+</span></div>
        <div class="stat-desc">Products Listed</div>
      </div>
      <div class="stat-pill">
        <div class="stat-num"><!-- 0 --><span>+</span></div>
        <div class="stat-desc">Orders Delivered</div>
      </div>
      <div class="stat-pill">
        <div class="stat-num"><!-- 0 --></div>
        <div class="stat-desc">Years in Business</div>
      </div>
    </div>

    <!-- ── OUR STORY ── -->
    <div class="about-card">
      <div class="about-card-header">
        <h3><i class="fa-solid fa-book-open"></i> Our Story</h3>
      </div>
      <div class="about-card-body">
        <div class="story-grid">
          <div class="story-text">
            <h2>We Are <span>Allens Store</span></h2>
            <!-- TYPE YOUR STORY PARAGRAPHS BELOW -->
            <p><!-- Paragraph 1: How the store started... --></p>
            <p><!-- Paragraph 2: What drives you... --></p>
            <p><!-- Paragraph 3: Where you are today... --></p>
          </div>
          <div class="story-image-box">
            <!-- REPLACE THIS BOX WITH AN <img> TAG IF YOU HAVE A STORE/BRAND PHOTO -->
            <!-- <i class="fa-solid fa-store"></i> -->
            <span>Allens stores is owned by Pharmacist Adamu Allen Adakole 
              which was founded in 2026, <span> <br>allens stores provides all  items
              to be  available for our loyal customers</span>and also ensure all products are of high quality and excellent durability 
              for optimiun use 
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── OUR VALUES ── -->
    <div class="about-card">
      <div class="about-card-header">
        <h3><i class="fa-solid fa-star"></i> Our Values</h3>
      </div>
      <div class="about-card-body">
        <div class="values-grid">
          <div class="value-item">
            <div class="vi-icon"><i class="fa-solid fa-medal"></i></div>
            <h4>Quality</h4>
            <p><!-- Describe your quality commitment --></p>
          </div>
          <div class="value-item">
            <div class="vi-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <h4>Trust</h4>
            <p><!-- Describe how customers can trust you --></p>
          </div>
          <div class="value-item">
            <div class="vi-icon"><i class="fa-solid fa-truck-fast"></i></div>
            <h4>Fast Delivery</h4>
            <p><!-- Describe your delivery promise --></p>
          </div>
          <div class="value-item">
            <div class="vi-icon"><i class="fa-solid fa-headset"></i></div>
            <h4>Support</h4>
            <p><!-- Describe your customer support --></p>
          </div>
        </div>
      </div>
    </div>

    <!-- ── MEET THE TEAM ── -->
    <div class="about-card">
      <div class="about-card-header">
        <h3><i class="fa-solid fa-users"></i> Meet the Team</h3>
      </div>
      <div class="about-card-body">
        <div class="team-grid">
          <!-- DUPLICATE THESE TEAM CARDS AS NEEDED -->
          <div class="team-card">
            <div class="team-avatar"><i class="fa-solid fa-user"></i></div>
            <h4>Pharm.Adamu Allen </h4>
            <span>CEO/Founder/COO</span>
          </div>
          <div class="team-card">
            <div class="team-avatar"><i class="fa-solid fa-user"></i></div>
            <h4><!-- Name --></h4>
            <span><!-- Role / Title --></span>
          </div>
          <div class="team-card">
            <div class="team-avatar"><i class="fa-solid fa-user"></i></div>
            <h4><!-- Name --></h4>
            <span><!-- Role / Title --></span>
          </div>
          <div class="team-card">
            <div class="team-avatar"><i class="fa-solid fa-user"></i></div>
            <h4><!-- Name --></h4>
            <span><!-- Role / Title --></span>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /about-wrapper -->
</div><!-- /container -->
</body>
</html>
