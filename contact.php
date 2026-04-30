<?php
session_start();
require_once 'db_connection.php';
$cart        = $_SESSION['cart'] ?? [];
$total_items = array_sum(array_column($cart, 'qty'));

$sent  = false;
$error = '';

// ── Handle contact form submission ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'contact') {
    $fname   = trim($_POST['first_name'] ?? '');
    $lname   = trim($_POST['last_name']  ?? '');
    $email   = trim($_POST['email']      ?? '');
    $subject = trim($_POST['subject']    ?? '');
    $message = trim($_POST['message']    ?? '');

    if (!$fname || !$lname || !$email || !$subject || !$message) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // ── TO DO: add your mail() or SMTP logic here ──────────────────────
        // Example: mail('you@youremail.com', $subject, $message, "From: $email");
        // ───────────────────────────────────────────────────────────────────
        $sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us — Allens Store</title>
<link rel="stylesheet" href="Dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  /* ── Hero ── */
  .contact-hero {
    width: 100%;
    background: linear-gradient(135deg, #172036 0%, #1e3a6e 100%);
    padding: 56px 40px;
    text-align: center;
    color: #fff;
  }
  .contact-hero h1 {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 10px;
    letter-spacing: -0.02em;
  }
  .contact-hero h1 span { color: orange; }
  .contact-hero p {
    font-size: 14px;
    color: rgba(255,255,255,0.6);
    max-width: 480px;
    margin: 0 auto;
    line-height: 1.7;
  }
  .contact-hero .hero-divider {
    width: 48px; height: 4px;
    background: orange; border-radius: 999px;
    margin: 18px auto 0;
  }

  /* ── Wrapper ── */
  .contact-wrapper {
    width: 100%;
    background: rgb(219,235,249);
    padding: 40px 28px 70px;
    min-height: calc(100vh - 200px);
  }

  /* ── Layout ── */
  .contact-layout {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 24px;
    align-items: start;
  }
  @media(max-width: 860px) { .contact-layout { grid-template-columns: 1fr; } }

  /* ── Card ── */
  .contact-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    overflow: hidden;
  }
  .contact-card-header {
    background: #172036;
    padding: 14px 22px;
  }
  .contact-card-header h3 {
    font-size: 13px;
    font-weight: 800;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
  }
  .contact-card-header h3 i { color: orange; }
  .contact-card-body { padding: 26px 24px; }

  /* ── Info items ── */
  .info-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px 0;
    border-bottom: 1px solid #eef2fb;
  }
  .info-item:last-child { border-bottom: none; }
  .info-icon {
    width: 42px; height: 42px;
    border-radius: 10px;
    background: #eaf2ff;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; color: #3b82f6;
    flex-shrink: 0;
  }
  .info-text h4 {
    font-size: 12px;
    font-weight: 800;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 4px;
  }
  .info-text p {
    font-size: 13.5px;
    font-weight: 700;
    color: #172036;
    margin: 0;
    line-height: 1.5;
  }
  .info-text a {
    font-size: 13.5px;
    font-weight: 700;
    color: #2563eb;
    text-decoration: none;
    transition: .2s;
  }
  .info-text a:hover { color: #172036; }

  /* ── Social icons ── */
  .social-row {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eef2fb;
    flex-wrap: wrap;
  }
  .social-btn {
    width: 40px; height: 40px;
    border-radius: 8px;
    background: #eaf2ff;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; color: #172036;
    text-decoration: none;
    transition: .2s;
  }
  .social-btn:hover { background: #172036; color: orange; transform: translateY(-2px); }

  /* ── Map placeholder ── */
  .map-placeholder {
    background: #eaf2ff;
    border: 2px dashed #c8d6ed;
    border-radius: 10px;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 8px;
    color: #93b4d8;
    margin-top: 20px;
  }
  .map-placeholder i { font-size: 2.2rem; }
  .map-placeholder span { font-size: 12px; font-weight: 600; }

  /* ── Hours table ── */
  .hours-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 9px 0;
    border-bottom: 1px solid #eef2fb;
    font-size: 13px;
  }
  .hours-row:last-child { border-bottom: none; }
  .hours-row .day { color: #6b7280; font-weight: 600; }
  .hours-row .time { color: #172036; font-weight: 700; }
  .hours-row .closed { color: #b91c1c; font-weight: 700; }

  /* ── Success state ── */
  .success-box {
    text-align: center;
    padding: 50px 30px;
  }
  .success-box i { font-size: 3.5rem; color: #16a34a; display: block; margin-bottom: 16px; }
  .success-box h3 { font-size: 1.2rem; font-weight: 800; color: #172036; margin-bottom: 8px; }
  .success-box p { font-size: 13px; color: #6b7280; margin-bottom: 20px; }
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
      <a href="contact.php" class="active">Contact</a>
      <a href="about.php">About</a>
      <a href="profile.php">Profile <i class="fa-regular fa-user"></i></a>
    </div>
  </div>

  <!-- CATEGORY BAR -->
  <div class="bar2">
    <div class="text">Fashion | Phone &amp; Tablets | Health &amp; Beauty | Electronics | Gaming</div>
  </div>

  <!-- HERO -->
  <div class="contact-hero">
    <h1>Contact <span>Us</span></h1>
    <p><!-- TYPE A SHORT LINE E.G. "We'd love to hear from you. Reach out any time." --></p>
    <div class="hero-divider"></div>
  </div>

  <div class="contact-wrapper">

    <?php if ($error): ?>
    <div class="alert alert-error" style="margin-bottom:20px;">
      <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="contact-layout">

      <!-- ── LEFT: Contact Form ── -->
      <div class="contact-card">
        <div class="contact-card-header">
          <h3><i class="fa-solid fa-paper-plane"></i> Send Us a Message</h3>
        </div>
        <div class="contact-card-body">

          <?php if ($sent): ?>
          <div class="success-box">
            <i class="fa-solid fa-circle-check"></i>
            <h3>Message Sent!</h3>
            <p>Thank you for reaching out. We'll get back to you as soon as possible.</p>
            <a href="contact.php" class="btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
              <i class="fa-solid fa-rotate-left"></i> Send Another
            </a>
          </div>

          <?php else: ?>
          <form method="POST" action="contact.php">
            <input type="hidden" name="form" value="contact">
            <div class="form-grid">

              <div class="form-group">
                <label for="first_name">First Name <span style="color:#b91c1c;">*</span></label>
                <input type="text" id="first_name" name="first_name"
                       value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                       placeholder="Your first name" required>
              </div>

              <div class="form-group">
                <label for="last_name">Last Name <span style="color:#b91c1c;">*</span></label>
                <input type="text" id="last_name" name="last_name"
                       value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                       placeholder="Your last name" required>
              </div>

              <div class="form-group full">
                <label for="email">Email Address <span style="color:#b91c1c;">*</span></label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="you@example.com" required>
              </div>

              <div class="form-group full">
                <label for="subject">Subject <span style="color:#b91c1c;">*</span></label>
                <input type="text" id="subject" name="subject"
                       value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"
                       placeholder="What is this regarding?" required>
              </div>

              <div class="form-group full">
                <label for="message">Message <span style="color:#b91c1c;">*</span></label>
                <textarea id="message" name="message" rows="6"
                          placeholder="Write your message here..."
                          required style="resize:vertical;"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
              </div>

            </div>
            <div class="form-actions" style="margin-top:18px;">
              <button type="submit" class="btn-primary">
                <i class="fa-solid fa-paper-plane"></i> Send Message
              </button>
              <button type="reset" class="btn-secondary">
                <i class="fa-solid fa-rotate-left"></i> Clear
              </button>
            </div>
          </form>
          <?php endif; ?>

        </div>
      </div>

      <!-- ── RIGHT COLUMN ── -->
      <div style="display:flex;flex-direction:column;gap:20px;">

        <!-- Contact Info -->
        <div class="contact-card">
          <div class="contact-card-header">
            <h3><i class="fa-solid fa-address-book"></i> Contact Information</h3>
          </div>
          <div class="contact-card-body" style="padding:16px 20px;">

            <div class="info-item">
              <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
              <div class="info-text">
                <h4>Address</h4>
                <p><!-- TYPE YOUR ADDRESS HERE --></p>
              </div>
            </div>

            <div class="info-item">
              <div class="info-icon"><i class="fa-solid fa-phone"></i></div>
              <div class="info-text">
                <h4>Phone</h4>
                <a href="tel:+234"><!-- TYPE YOUR PHONE NUMBER --></a>
              </div>
            </div>

            <div class="info-item">
              <div class="info-icon"><i class="fa-solid fa-envelope"></i></div>
              <div class="info-text">
                <h4>Email</h4>
                <a href="mailto:"><!-- TYPE YOUR EMAIL --></a>
              </div>
            </div>

            <div class="info-item">
              <div class="info-icon"><i class="fa-solid fa-globe"></i></div>
              <div class="info-text">
                <h4>Website</h4>
                <a href="#"><!-- TYPE YOUR WEBSITE --></a>
              </div>
            </div>

            <!-- Social Media -->
            <div class="social-row">
              <a href="#" class="social-btn" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
              <a href="https://instagram.com/khid_allen" target="_blank" class="social-btn" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
              <a href="https://x.com/fw_gLf" target="_blank" class="social-btn" title="Twitter/X"><i class="fa-brands fa-x-twitter"></i></a>
              <a href="https://wa.me/2349056241248" target="_blank" class ="social-btn" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
              <a href="#" class="social-btn" title="TikTok"><i class="fa-brands fa-tiktok"></i></a>
            </div>

            <!-- Map Placeholder -->
            <div class="map-placeholder">
              <!-- REPLACE THIS WITH A GOOGLE MAPS EMBED <iframe> -->
              <i class="fa-solid fa-map-location-dot"></i>
              <span>Embed your Google Map here</span>
            </div>

          </div>
        </div>

        <!-- Business Hours -->
        <div class="contact-card">
          <div class="contact-card-header">
            <h3><i class="fa-regular fa-clock"></i> Business Hours</h3>
          </div>
          <div class="contact-card-body" style="padding:16px 20px;">
            <!-- EDIT DAYS AND TIMES AS NEEDED -->
            <div class="hours-row">
              <span class="day">Monday</span>
              <span class="time"><!-- e.g. 9:00 AM – 6:00 PM --></span>
            </div>
            <div class="hours-row">
              <span class="day">Tuesday</span>
              <span class="time"><!-- --></span>
            </div>
            <div class="hours-row">
              <span class="day">Wednesday</span>
              <span class="time"><!-- --></span>
            </div>
            <div class="hours-row">
              <span class="day">Thursday</span>
              <span class="time"><!-- --></span>
            </div>
            <div class="hours-row">
              <span class="day">Friday</span>
              <span class="time"><!-- --></span>
            </div>
            <div class="hours-row">
              <span class="day">Saturday</span>
              <span class="time"><!-- --></span>
            </div>
            <div class="hours-row">
              <span class="day">Sunday</span>
              <span class="closed"><!-- Closed / Open --></span>
            </div>
          </div>
        </div>

      </div><!-- /right col -->
    </div><!-- /contact-layout -->
  </div><!-- /contact-wrapper -->
</div><!-- /container -->
</body>
</html>
