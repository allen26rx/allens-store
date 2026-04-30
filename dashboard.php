<?php
session_start();
require_once '../ax/db_connection.php';   // reach into ax folder for the shared DB

$cart        = $_SESSION['cart'] ?? [];
$total_items = array_sum(array_column($cart, 'qty'));

// Load inventory products added via admin (ax/inventory.php)
try {
    $db         = databaseConnection();
    $dbProducts = $db->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $dbProducts = []; // fail silently so the rest of the dashboard still works
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Allens Store</title>
<link rel="stylesheet" href="Dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php 


 
 if (isset($_SESSION['message'])): ?>
<div id="toast" class="show"><?= htmlspecialchars($_SESSION['message']) ?></div>
<?php unset($_SESSION['message']); ?>
<?php endif; ?>

<div class="container">

    <!-- NAVBAR -->
    <div class="bar1">
        <div class="logo">
            Allens <i class="fa-solid fa-store"></i>
        </div>

        <div class="search_box">
            <input type="text" placeholder="Search products...">
            <button><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>

        <div class="nav_buttons">
            <a href="dashboard.php" class="active">Products</a>
            <a href="cart.php">
                Cart <i class="fa-solid fa-cart-shopping"></i>
                <span id="cart-badge" class="cart-badge <?= $total_items > 0 ? '' : 'hidden' ?>">
                    <?= $total_items > 0 ? $total_items : '' ?>
                </span>
            </a>
            <a href="contact.php">Contact</a>
            <a href="about.php">About</a>
            <a href="profile.php">Profile <i class="fa-regular fa-user"></i></a>
        </div>
    </div>

    <!-- CATEGORY BAR -->
    <div class="bar2">
        <div class="text">
            Fashion | Phone &amp; Tablets | Health &amp; Beauty | Electronics | Gaming
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="bar3">

        <div class="grp">
            <marquee><strong>Order from Allens and stand a chance to get 30% off</strong></marquee>
        </div>

        <!-- ================= ROW 1 ================= -->
        <div class="grp1">
            <div class="pic1">
                <img src="https://img.kwcdn.com/product/fmket/39502f00a8a339fdcec27d4e8420efff.jpg" alt="Men's Casual Shirt">
                <h6>Men's Casual Shirt</h6>
                <p>₦7,500</p>
                <button class="cart-btn" data-id="1">Add to Cart</button>
            </div>
            <div class="pic2">
                <img src="https://img.kwcdn.com/product/fancy/dacae3aa-2caf-42cb-b5e8-4d5e5352e010.jpg" alt="Round-Neck T-Shirt">
                <h6>Round-Neck T-Shirt</h6>
                <p>₦6,000</p>
                <button class="cart-btn" data-id="2">Add to Cart</button>
            </div>
            <div class="pic3">
                <img src="https://img.kwcdn.com/product/fancy/4bd33f3f-e8a0-45d0-a513-b236edbbf84e.jpg" alt="Casual Sports Pants">
                <h6>Casual Sports Pants</h6>
                <p>₦12,000</p>
                <button class="cart-btn" data-id="3">Add to Cart</button>
            </div>
            <div class="pic4">
                <img src="https://img.kwcdn.com/product/fmket/f083be53b50a313a348b59777633734a.jpg" alt="Graphic Shirt">
                <h6>Graphic Shirt</h6>
                <p>₦8,000</p>
                <button class="cart-btn" data-id="4">Add to Cart</button>
            </div>
        </div>

        <!-- ================= ROW 2 ================= -->
        <div class="grp2">
            <div class="pic5">
                <img src="https://img.kwcdn.com/product/fancy/888d5f63-c992-468c-93d1-70d3e6ab0896.jpg" alt="Hair Clipper">
                <h6>Hair Clipper</h6>
                <p>₦10,000</p>
                <button class="cart-btn" data-id="5">Add to Cart</button>
            </div>
            <div class="pic6">
                <img src="https://img.kwcdn.com/product/fancy/31cdf11d-afa6-48b3-9243-f69b9f4953b8.jpg" alt="Mini Projector">
                <h6>Mini Projector</h6>
                <p>₦55,000</p>
                <button class="cart-btn" data-id="6">Add to Cart</button>
            </div>
            <div class="pic7">
                <img src="https://img.kwcdn.com/product/open/bcef397e2a104f6b87168db2a7e4066a-goods.jpeg" alt="Wireless Earbuds">
                <h6>Wireless Earbuds</h6>
                <p>₦10,000</p>
                <button class="cart-btn" data-id="7">Add to Cart</button>
            </div>
            <div class="pic8">
                <img src="https://img.kwcdn.com/product/fancy/9fdb2a0a-df87-42e8-a121-4d29e894f964.jpg" alt="Air Cooler">
                <h6>Air Cooler</h6>
                <p>₦126,000</p>
                <button class="cart-btn" data-id="8">Add to Cart</button>
            </div>
        </div>

        <!-- ================= ROW 3 ================= -->
        <div class="grp3">
            <div class="pic9">
                <img src="https://img.kwcdn.com/product/open/1ddff1e4f587454ca2c66870b3e92c81-goods.jpeg" alt="Business Shoes">
                <h6>Business Shoes</h6>
                <p>₦35,000</p>
                <button class="cart-btn" data-id="9">Add to Cart</button>
            </div>
            <div class="pic10">
                <img src="https://img.kwcdn.com/product/fancy/bc3db0a6-9ffa-4df6-b29a-3564c7071bad.jpg" alt="Casual Shoes">
                <h6>Casual Shoes</h6>
                <p>₦26,000</p>
                <button class="cart-btn" data-id="10">Add to Cart</button>
            </div>
            <div class="pic11">
                <img src="https://img.kwcdn.com/product/fancy/f2bc57b0-5498-4fda-a9dc-4c331af6f13f.jpg" alt="Dress Shoes">
                <h6>Dress Shoes</h6>
                <p>₦45,000</p>
                <button class="cart-btn" data-id="11">Add to Cart</button>
            </div>
            <div class="pic12">
                <img src="https://img.kwcdn.com/product/fancy/cab81cc3-1502-46c3-a9bb-a945e429af01.jpg" alt="High-Top Sneakers">
                <h6>High-Top Sneakers</h6>
                <p>₦30,300</p>
                <button class="cart-btn" data-id="12">Add to Cart</button>
            </div>
        </div>

        <!-- ================= NEW ARRIVALS (from Inventory) ================= -->
        <?php if (!empty($dbProducts)): ?>
        <div class="grp" style="background:#172036;color:#fff;font-size:13px;font-weight:700;padding:10px 25px;letter-spacing:.03em;">
            <i class="fa-solid fa-star" style="color:orange;margin-right:8px;"></i> New Arrivals
        </div>
        <div class="grp1">
            <?php foreach ($dbProducts as $p): ?>
            <div class="pic1" style="position:relative;">
                <span style="position:absolute;top:8px;right:8px;background:orange;color:#fff;
                      font-size:10px;font-weight:800;padding:2px 8px;border-radius:999px;z-index:1;">NEW</span>
                <img src="<?= htmlspecialchars($p['image']) ?>"
                     alt="<?= htmlspecialchars($p['name']) ?>"
                     onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">
                <h6><?= htmlspecialchars($p['name']) ?></h6>
                <p>₦<?= number_format((float)$p['price'], 0) ?></p>
                <button class="cart-btn" data-id="db_<?= (int)$p['id'] ?>">Add to Cart</button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div><!-- /bar3 -->
</div><!-- /container -->

<div id="toast"></div>

<script>
  const badge = document.getElementById('cart-badge');

  function updateBadge(count) {
    badge.textContent = count;
    if (count > 0) {
      badge.classList.remove('hidden');
      badge.classList.add('pop');
      setTimeout(() => badge.classList.remove('pop'), 350);
    } else {
      badge.classList.add('hidden');
    }
  }

  const toast = document.getElementById('toast');

  function showToast(msg, type = '') {
    toast.textContent = msg;
    toast.className   = 'show' + (type ? ' ' + type : '');
    clearTimeout(toast._t);
    toast._t = setTimeout(() => { toast.className = ''; }, 3200);
  }

  // Auto-dismiss session toast on load
  if (toast.classList.contains('show') && toast.textContent.trim()) {
    setTimeout(() => { toast.className = ''; }, 3200);
  }

  // AJAX Add to Cart
  document.querySelectorAll('.cart-btn').forEach(btn => {
    btn.addEventListener('click', async function () {
      const id   = this.dataset.id;
      const orig = this.textContent;
      this.textContent = 'Adding...';
      this.disabled    = true;

      try {
        const res  = await fetch('add_cart.php', {
          method:  'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
          body:    'product_id=' + encodeURIComponent(id),
        });
        const data = await res.json();

        if (data.success) {
          updateBadge(data.cart_count);
          showToast('✓ ' + data.product + ' added to cart!');
          this.textContent = '✓ Added!';
          setTimeout(() => { this.textContent = orig; this.disabled = false; }, 1500);
        } else {
          showToast(data.message || 'Something went wrong.', 'error');
          this.textContent = orig; this.disabled = false;
        }
      } catch {
        showToast('Network error. Please try again.', 'error');
        this.textContent = orig; this.disabled = false;
      }
    });
  });
</script>
</body>
</html>
