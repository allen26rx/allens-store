<?php
session_start();
require_once 'db_connection.php';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = isset($_POST['product_id']) ? $_POST['product_id'] : '';

    if ($id !== '' && $action === 'remove' && isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
    } elseif ($id !== '' && $action === 'increase' && isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['qty']++;
    } elseif ($id !== '' && $action === 'decrease' && isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['qty']--;
        if ($_SESSION['cart'][$id]['qty'] <= 0) unset($_SESSION['cart'][$id]);
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
    }

    header('Location: cart.php');
    exit;
}

$cart        = $_SESSION['cart'] ?? [];
$total_items = array_sum(array_column($cart, 'qty'));
$subtotal    = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));
$delivery    = $subtotal > 0 ? 3000 : 0;
$grand_total = $subtotal + $delivery;

// Get logged-in user info for Paystack
$user_email = '';
$user_name  = 'Customer';
if (isset($_SESSION['id'])) {
    $db   = databaseConnection();
    $stmt = $db->prepare("SELECT email, first_name, last_name FROM allens WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => (int)$_SESSION['id']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        $user_email = $u['email'];
        $user_name  = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
    }
}

// Build items string passed to verify_payment.php
$items_list = implode(', ', array_map(fn($i) => $i['name'] . ' x' . $i['qty'], $cart));

function fmt($n) { return '₦' . number_format($n); }

define('PAYSTACK_PUBLIC_KEY', 'pk_test_24df39b7005915dcdc891a1f9d43c18a60cbffb4');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cart — Allens Store</title>
<link rel="stylesheet" href="Dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  .paystack-btn {
    width:100%; padding:14px;
    background:linear-gradient(135deg,#172036,#1e3a6e);
    color:#fff; border:none; border-radius:8px;
    font-size:14px; font-weight:800; cursor:pointer;
    transition:all .25s; display:flex; align-items:center;
    justify-content:center; gap:10px;
    font-family:Arial,Helvetica,sans-serif;
    box-shadow:0 6px 20px rgba(23,32,54,0.3); margin-bottom:10px;
  }
  .paystack-btn:hover { background:#172036; transform:translateY(-2px); }
  .paystack-btn .ps-logo {
    background:orange; color:#fff; font-size:10px; font-weight:800;
    padding:2px 7px; border-radius:4px; letter-spacing:.05em;
  }
  .login-prompt {
    background:#fff7ed; border:1.5px solid #fed7aa;
    border-radius:8px; padding:12px 16px; font-size:13px;
    color:#92400e; text-align:center; margin-bottom:10px;
  }
  .login-prompt a { color:#ea580c; font-weight:700; text-decoration:none; }
  .pay-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,0.65); z-index:9999;
    align-items:center; justify-content:center; flex-direction:column; gap:16px;
  }
  .pay-overlay.show { display:flex; }
  .pay-overlay .spinner {
    width:48px; height:48px;
    border:4px solid rgba(255,255,255,0.2);
    border-top-color:orange; border-radius:50%;
    animation:spin .8s linear infinite;
  }
  .pay-overlay p { color:#fff; font-size:14px; font-weight:600; }
  @keyframes spin { to { transform:rotate(360deg); } }
  .trust-badges { display:flex; gap:10px; justify-content:center; margin-top:14px; flex-wrap:wrap; }
  .trust-badges span { font-size:11px; color:#9aabcc; display:flex; align-items:center; gap:4px; }
</style>
</head>
<body>

<div class="pay-overlay" id="pay-overlay">
  <div class="spinner"></div>
  <p>Verifying your payment...</p>
</div>

<div class="container">
  <div class="bar1">
    <div class="logo">Allens <i class="fa-solid fa-store"></i></div>
    <div class="search_box">
      <input type="text" placeholder="Search products...">
      <button><i class="fa-solid fa-magnifying-glass"></i></button>
    </div>
    <div class="nav_buttons">
      <a href="dashboard.php">Products</a>
      <a href="cart.php" class="active">Cart <i class="fa-solid fa-cart-shopping"></i>
        <?php if ($total_items > 0): ?><span class="cart-badge"><?= $total_items ?></span><?php endif; ?>
      </a>
      <a href="contact.php">Contact</a>
      <a href="about.php">About</a>
      <a href="profile.php">Profile <i class="fa-regular fa-user"></i></a>
    </div>
  </div>

  <div class="bar2">
    <div class="text">Fashion | Phone &amp; Tablets | Health &amp; Beauty | Electronics | Gaming</div>
  </div>

  <div class="page-wrapper">
    <div class="page-title">
      <i class="fa-solid fa-cart-shopping"></i> My Cart
      <?php if ($total_items > 0): ?>
        <span style="font-size:14px;color:#6b7280;font-weight:400;">(<?= $total_items ?> item<?= $total_items != 1 ? 's' : '' ?>)</span>
      <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success">
      <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION['message']) ?>
    </div>
    <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <?php if (empty($cart)): ?>
      <div class="empty-cart">
        <i class="fa-solid fa-cart-shopping"></i>
        <p>Your cart is empty.</p>
        <a href="dashboard.php"><i class="fa-solid fa-arrow-left"></i> Continue Shopping</a>
      </div>
    <?php else: ?>
      <div class="cart-layout">
        <div>
          <div class="cart-items-box">
            <div class="cart-items-header">
              <span></span><span>Product</span><span>Quantity</span><span>Subtotal</span><span></span>
            </div>

            <?php foreach ($cart as $id => $item): ?>
            <div class="cart-item">
              <img src="<?= htmlspecialchars($item['img'] ?? '') ?>"
                   alt="<?= htmlspecialchars($item['name']) ?>"
                   onerror="this.src='https://via.placeholder.com/64x64?text=IMG'">
              <div>
                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                <div class="item-unit"><?= fmt($item['price']) ?> each</div>
              </div>
              <div class="qty-col">
                <div class="qty-stepper">
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="product_id" value="<?= $id ?>">
                    <input type="hidden" name="action" value="decrease">
                    <button type="submit">−</button>
                  </form>
                  <span><?= $item['qty'] ?></span>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="product_id" value="<?= $id ?>">
                    <input type="hidden" name="action" value="increase">
                    <button type="submit">+</button>
                  </form>
                </div>
              </div>
              <div class="price-col"><?= fmt($item['price'] * $item['qty']) ?></div>
              <div class="del-col">
                <form method="POST">
                  <input type="hidden" name="product_id" value="<?= $id ?>">
                  <input type="hidden" name="action" value="remove">
                  <button type="submit" class="del-btn"><i class="fa-solid fa-trash-can"></i></button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>

            <form method="POST">
              <input type="hidden" name="action" value="clear">
              <button type="submit" class="clear-btn" onclick="return confirm('Remove all items from cart?')">
                <i class="fa-solid fa-broom"></i> Clear entire cart
              </button>
            </form>
          </div>
        </div>

        <!-- Order Summary -->
        <div>
          <div class="summary-box">
            <h3>Order Summary</h3>

            <?php foreach ($cart as $item): ?>
            <div class="summary-row" style="font-size:12px;">
              <span><?= htmlspecialchars($item['name']) ?> ×<?= $item['qty'] ?></span>
              <span><?= fmt($item['price'] * $item['qty']) ?></span>
            </div>
            <?php endforeach; ?>

            <div class="summary-row" style="margin-top:10px;border-top:1px solid #eaf2ff;padding-top:10px;">
              <span>Subtotal</span><span><?= fmt($subtotal) ?></span>
            </div>
            <div class="summary-row">
              <span>Delivery fee</span><span><?= fmt($delivery) ?></span>
            </div>
            <div class="summary-row total">
              <span>Total</span><span><?= fmt($grand_total) ?></span>
            </div>

            <?php if ($user_email): ?>
            <button class="paystack-btn" id="pay-btn"
                    data-email="<?= htmlspecialchars($user_email) ?>"
                    data-amount="<?= $grand_total * 100 ?>"
                    data-name="<?= htmlspecialchars($user_name) ?>"
                    data-items="<?= htmlspecialchars($items_list) ?>"
                    data-total="<?= $grand_total ?>">
              <i class="fa-solid fa-lock"></i> Pay <?= fmt($grand_total) ?>
              <span class="ps-logo">Paystack</span>
            </button>
            <?php else: ?>
            <div class="login-prompt">
              <i class="fa-solid fa-circle-info"></i>
              Please <a href="login.php">log in</a> to complete your purchase.
            </div>
            <?php endif; ?>

            <a href="dashboard.php" class="continue-link">← Continue Shopping</a>

            <div class="free-delivery">
              <i class="fa-solid fa-truck"></i> Free delivery on orders above ₦50,000
            </div>

            <div class="trust-badges">
              <span><i class="fa-solid fa-shield-halved" style="color:#22c55e;"></i> Secure Payment</span>
              <span><i class="fa-solid fa-lock" style="color:#3b82f6;"></i> SSL Encrypted</span>
              <span><i class="fa-solid fa-rotate-left" style="color:orange;"></i> Easy Returns</span>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<div id="toast"></div>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
const payBtn = document.getElementById('pay-btn');
if (payBtn) {
    payBtn.addEventListener('click', function () {
        const handler = PaystackPop.setup({
            key:      '<?= PAYSTACK_PUBLIC_KEY ?>',
            email:    this.dataset.email,
            amount:   parseInt(this.dataset.amount),
            currency: 'NGN',
            ref:      'ALLENS_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
            metadata: {
                custom_fields: [
                    { display_name:'Customer', variable_name:'customer', value: this.dataset.name },
                    { display_name:'Items',    variable_name:'items',    value: this.dataset.items }
                ]
            },
            callback: function(response) {
                document.getElementById('pay-overlay').classList.add('show');
                const btn = document.getElementById('pay-btn');
                fetch('verify_payment.php', {
                    method:  'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: 'reference=' + encodeURIComponent(response.reference)
                        + '&items='   + encodeURIComponent(btn.dataset.items)
                        + '&total='   + encodeURIComponent(btn.dataset.total)
                })
                .then(r => r.json())
                .then(data => {
                    document.getElementById('pay-overlay').classList.remove('show');
                    if (data.success) {
                        window.location.href = 'order_success.php';
                    } else {
                        alert('Payment verification failed. Please contact support.\nRef: ' + response.reference);
                    }
                })
                .catch(() => {
                    document.getElementById('pay-overlay').classList.remove('show');
                    alert('Network error. Please contact support with ref: ' + response.reference);
                });
            },
            onClose: function() { /* user closed modal */ }
        });
        handler.openIframe();
    });
}
</script>
</body>
</html>
