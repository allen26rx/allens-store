<?php
session_start();
require_once 'db_connection.php';

/* ─────────────────────────────────────────────
   GUARD: must be logged in
   Expects login.php to store the user's ID as:
       $_SESSION['user_id'] = $row['id'];
   after a successful login query.
───────────────────────────────────────────── */
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$db      = databaseConnection();
$userId  = (int) $_SESSION['id'];
$success = '';
$error   = '';
$tab     = $_GET['tab'] ?? 'details';

/* ─────────────────────────────────────────────
   LOGOUT
───────────────────────────────────────────── */
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}

/* ─────────────────────────────────────────────
   UPDATE PERSONAL DETAILS → saves to DB
───────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'details') {
    $first   = trim($_POST['first_name'] ?? '');
    $last    = trim($_POST['last_name']  ?? '');
    $phone   = trim($_POST['phone_number']      ?? '');
    $address = trim($_POST['address']    ?? '');
    $city    = trim($_POST['city']       ?? '');
    $state   = trim($_POST['state']      ?? '');

    if (!$first || !$last) {
        $error = 'First name and last name are required.';
    } elseif ($phone && !preg_match('/^[\+\d\s\-\(\)]{7,20}$/', $phone)) {
        $error = 'Please enter a valid phone number.';
    } else {
        // Detect whether the table uses first_name/last_name or a single name column
        $colCheck = $db->prepare("SELECT * FROM allens WHERE id = :id LIMIT 1");
        $colCheck->execute([':id' => $userId]);
        $cols = array_keys($colCheck->fetch(PDO::FETCH_ASSOC) ?: []);

        if (in_array('first_name', $cols)) {
            $stmt = $db->prepare("
                UPDATE allens
                   SET first_name = :first,
                       last_name  = :last,
                       phone_number= :phone,
                       address    = :address,
                       city       = :city,
                       state      = :state
                 WHERE id = :id
            ");
            $stmt->execute([
                ':first'   => htmlspecialchars($first),
                ':last'    => htmlspecialchars($last),
                ':phone'   => htmlspecialchars($phone),
                ':address' => htmlspecialchars($address),
                ':city'    => htmlspecialchars($city),
                ':state'   => htmlspecialchars($state),
                ':id'      => $userId,
            ]);
        } else {
            // Single 'name' column
            $stmt = $db->prepare("
                UPDATE allens
                   SET name    = :name,
                       phone_number   = :phone,
                       address = :address,
                       city    = :city,
                       state   = :state
                 WHERE id = :id
            ");
            $stmt->execute([
                ':name'    => htmlspecialchars(trim("$first $last")),
                ':phone'   => htmlspecialchars($phone),
                ':address' => htmlspecialchars($address),
                ':city'    => htmlspecialchars($city),
                ':state'   => htmlspecialchars($state),
                ':id'      => $userId,
            ]);
        }
        $success = 'Your details have been updated successfully!';
    }
    $tab = 'details';
}

/* ─────────────────────────────────────────────
   CHANGE PASSWORD → saves to DB
───────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'password') {
    $current = $_POST['current_password']  ?? '';
    $new     = $_POST['new_password']      ?? '';
    $confirm = $_POST['confirm_password']  ?? '';

    // Fetch current hash from DB to verify
    $stmt = $db->prepare("SELECT password FROM allens WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current || !$new || !$confirm) {
        $error = 'All password fields are required.';
    } elseif (!$row || !password_verify($current, $row['password'])) {
        $error = 'Your current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE allens SET password = :hash WHERE id = :id");
        $stmt->execute([':hash' => $hash, ':id' => $userId]);
        $success = 'Password changed successfully!';
    }
    $tab = 'password';
}

/* ─────────────────────────────────────────────
   FETCH CURRENT USER FROM DB
   (runs after any updates so data is fresh)
───────────────────────────────────────────── */
$stmt = $db->prepare("SELECT * FROM allens WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: profile.php');
    exit;
}

// Support both a single 'name' column OR separate 'first_name'/'last_name' columns
if (!empty($user['first_name']) || !empty($user['last_name'])) {
    $full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
} else {
    $full_name = $user['name'] ?? $_SESSION['name'] ?? 'User';
    $parts = explode(' ', $full_name, 2);
    $user['first_name'] = $parts[0] ?? $full_name;
    $user['last_name']  = $parts[1] ?? '';
}

/* ─────────────────────────────────────────────
   DISPLAY HELPERS
───────────────────────────────────────────── */
$cart_count = array_sum(array_column($_SESSION['cart'] ?? [], 'qty'));
$initials   = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
$full_name  = trim($user['first_name'] . ' ' . $user['last_name']);

// Format joined date nicely if created_at column exists
$joined = '';
if (!empty($user['created_at'])) {
    $joined = date('F Y', strtotime($user['created_at']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile — Allens Store</title>
<link rel="stylesheet" href="Dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
        <?php if ($cart_count > 0): ?>
          <span class="cart-badge"><?= $cart_count ?></span>
        <?php endif; ?>
      </a>
      <a href="cart.php">Contact</a>
      <a href="about.php">About</a>
      <a href="profile.php" class="active">Profile <i class="fa-regular fa-user"></i></a>
    </div>
  </div>

  <!-- CATEGORY BAR -->
  <div class="bar2">
    <div class="text">Fashion | Phone &amp; Tablets | Health &amp; Beauty | Electronics | Gaming</div>
  </div>

  <!-- PROFILE CONTENT -->
  <div class="page-wrapper">

    <div class="page-title">
      <i class="fa-solid fa-user-circle"></i> My Profile
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success">
      <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-error">
      <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="profile-layout">

      <!-- ===== SIDEBAR ===== -->
      <aside class="profile-sidebar">
        <div class="profile-avatar-box">
          <div class="avatar-circle"><?= $initials ?></div>
          <div class="profile-name"><?= htmlspecialchars($full_name) ?></div>
          <div class="profile-email"><?= htmlspecialchars($user['email']) ?></div>
        </div>

        <ul class="sidebar-menu">
          <li>
            <a href="profile.php?tab=details" class="<?= $tab === 'details' ? 'active' : '' ?>">
              <i class="fa-solid fa-id-card"></i> Personal Details
            </a>
          </li>
          <li>
            <a href="profile.php?tab=password" class="<?= $tab === 'password' ? 'active' : '' ?>">
              <i class="fa-solid fa-lock"></i> Change Password
            </a>
          </li>
          <li>
            <a href="cart.php">
              <i class="fa-solid fa-cart-shopping"></i> My Cart
              <?php if ($cart_count > 0): ?>
                <span class="cart-badge" style="margin-left:auto"><?= $cart_count ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li>
            <a href="dashboard.php">
              <i class="fa-solid fa-store"></i> Back to Shop
            </a>
          </li>
           
          <li>
            <a href="orders.php">
             <i class="fa-solid fa-clock-rotate-left"></i> Order history
            </a>
          </li>




          <li class="logout-item">
            <a href="login.php" id="logout-link">
              <i class="fa-solid fa-right-from-bracket"></i> Log Out
            </a>
          </li>
        </ul>
      </aside>

      <!-- ===== MAIN PANELS ===== -->
      <div class="profile-main">

        <!-- Account overview (always shown) -->
        <div class="profile-card">
          <div class="profile-card-header">
            <h3><i class="fa-solid fa-circle-info"></i> Account Overview</h3>
          </div>
          <div class="profile-card-body">
            <div class="info-row">
              <span>Full Name</span><span><?= htmlspecialchars($full_name) ?></span>
            </div>
            <div class="info-row">
              <span>Email</span><span><?= htmlspecialchars($user['email']) ?></span>
            </div>
            <div class="info-row">
              <span>Phone</span>
              <span><?= $user['phone_number'] ? htmlspecialchars($user['phone_number']) : '—' ?></span>
            </div>
            <div class="info-row">
              <span>Location</span>
              <span><?php
                $loc = array_filter([$user['city'] ?? '', $user['state'] ?? '']);
                echo $loc ? htmlspecialchars(implode(', ', $loc)) : '—';
              ?></span>
            </div>
            <?php if ($joined): ?>
            <div class="info-row">
              <span>Member Since</span><span><?= htmlspecialchars($joined) ?></span>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($tab === 'details'): ?>
        <!-- ── PERSONAL DETAILS FORM ── -->
        <div class="profile-card">
          <div class="profile-card-header">
            <h3><i class="fa-solid fa-pen-to-square"></i> Edit Personal Details</h3>
          </div>
          <div class="profile-card-body">
            <form method="POST" action="profile.php?tab=details" id="details-form">
              <input type="hidden" name="form" value="details">
              <div class="form-grid">

                <div class="form-group">
                  <label for="first_name">First Name</label>
                  <input type="text" id="first_name" name="first_name"
                         value="<?= htmlspecialchars($user['first_name']) ?>" required>
                  <span class="field-error" id="err-first">First name is required</span>
                </div>

                <div class="form-group">
                  <label for="last_name">Last Name</label>
                  <input type="text" id="last_name" name="last_name"
                         value="<?= htmlspecialchars($user['last_name']) ?>" required>
                  <span class="field-error" id="err-last">Last name is required</span>
                </div>

                <div class="form-group full">
                  <label for="email">Email Address</label>
                  <input type="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                  <span class="field-hint">Email address cannot be changed here.</span>
                </div>

                <div class="form-group">
                  <label for="phone_number">Phone Number</label>
                  <input type="tel" id="phone_number" name="phone"
                         value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>"
                         placeholder="+234 800 000 0000">
                </div>

                <div class="form-group">
                  <label for="city">City</label>
                  <input type="text" id="city" name="city"
                         value="<?= htmlspecialchars($user['city'] ?? '') ?>"
                         placeholder="e.g. Lagos">
                </div>

                <div class="form-group">
                  <label for="state">State</label>
                  <select id="state" name="state">
                    <option value="">— Select State —</option>
                     <?php
                    $states = ['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue',
                               'Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT',
                               'Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi',
                               'Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo',
                               'Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'];
                    foreach ($states as $s):
                      $sel = ($user['state'] ?? '') === $s ? 'selected' : '';
                    ?>
                    <option value="<?= $s ?>" <?= $sel ?>><?= $s ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-group full">
                  <label for="address">Delivery Address</label>
                  <input type="text" id="address" name="address"
                         value="<?= htmlspecialchars($user['address'] ?? '') ?>"
                         placeholder="Street address, landmark...">
                </div> 

              </div>
              <div class="form-actions">
                <button type="submit" class="btn-primary">
                  <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
                <button type="reset" class="btn-secondary">
                  <i class="fa-solid fa-rotate-left"></i> Reset
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- ── CHANGE PASSWORD FORM ── -->
        <?php elseif ($tab === 'password'): ?>
        <div class="profile-card">
          <div class="profile-card-header">
            <h3><i class="fa-solid fa-lock"></i> Change Password</h3>
          </div>
          <div class="profile-card-body">
            <form method="POST" action="profile.php?tab=password" id="pwd-form">
              <input type="hidden" name="form" value="password">
              <div class="form-grid">

                <div class="form-group full">
                  <label for="current_password">Current Password</label>
                  <input type="password" id="current_password" name="current_password"
                         placeholder="Enter your current password" autocomplete="current-password">
                </div>

                <div class="form-group">
                  <label for="new_password">New Password</label>
                  <input type="password" id="new_password" name="new_password"
                         placeholder="Min. 8 characters" autocomplete="new-password">
                  <div class="strength-bar">
                    <div class="strength-fill" id="strength-fill"></div>
                  </div>
                  <span class="field-hint" id="strength-label">Enter a new password</span>
                </div>

                <div class="form-group">
                  <label for="confirm_password">Confirm New Password</label>
                  <input type="password" id="confirm_password" name="confirm_password"
                         placeholder="Repeat new password" autocomplete="new-password">
                  <span class="field-error" id="err-match">Passwords do not match</span>
                </div>

              </div>
              <div class="form-actions">
                <button type="submit" class="btn-primary">
                  <i class="fa-solid fa-key"></i> Update Password
                </button>
              </div>
            </form>
          </div>
        </div>
        <?php endif; ?>

        <!-- ── DANGER ZONE ── -->
        <div class="profile-card danger-border">
          <div class="profile-card-header danger-header">
            <h3><i class="fa-solid fa-triangle-exclamation"></i> Danger Zone</h3>
          </div>
          <div class="profile-card-body">
            <p style="font-size:13px;color:#6b7280;margin-bottom:14px;">
              Logging out will end your session. Your cart items will be saved for when you return.
            </p>
            <a href="login.php" id="logout-btn" class="btn-danger">
              <i class="fa-solid fa-right-from-bracket"></i> Log Out of Account
            </a>
          </div>
        </div>

      </div><!-- /profile-main -->
    </div><!-- /profile-layout -->
  </div><!-- /page-wrapper -->
</div><!-- /container -->

<div id="toast"></div>

<script>
  // Confirm logout
  document.querySelectorAll('#logout-link, #logout-btn').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm('Are you sure you want to log out?')) e.preventDefault();
    });
  });

  // Password strength meter
  const newPwd     = document.getElementById('new_password');
  const confirmPwd = document.getElementById('confirm_password');
  const fill       = document.getElementById('strength-fill');
  const lbl        = document.getElementById('strength-label');
  const errMatch   = document.getElementById('err-match');

  if (newPwd) {
    newPwd.addEventListener('input', () => {
      const v = newPwd.value;
      let s = 0;
      if (v.length >= 8)           s++;
      if (/[A-Z]/.test(v))         s++;
      if (/[0-9]/.test(v))         s++;
      if (/[^A-Za-z0-9]/.test(v))  s++;
      const levels = [
        { w:'0%',   bg:'#e5e7eb', t:'Enter a new password' },
        { w:'25%',  bg:'#ef4444', t:'Weak' },
        { w:'50%',  bg:'#f97316', t:'Fair' },
        { w:'75%',  bg:'#eab308', t:'Good' },
        { w:'100%', bg:'#22c55e', t:'Strong ✓' },
      ];
      fill.style.width      = levels[s].w;
      fill.style.background = levels[s].bg;
      lbl.textContent       = levels[s].t;
      lbl.style.color       = levels[s].bg;
    });
  }

  if (confirmPwd) {
    confirmPwd.addEventListener('input', () => {
      if (newPwd.value && confirmPwd.value && newPwd.value !== confirmPwd.value) {
        errMatch.style.display = 'block';
      } else {
        errMatch.style.display = 'none';
      }
    });
  }

  // Client-side validation for details form
  const detailsForm = document.getElementById('details-form');
  if (detailsForm) {
    detailsForm.addEventListener('submit', e => {
      let ok = true;
      const f  = document.getElementById('first_name');
      const l  = document.getElementById('last_name');
      const ef = document.getElementById('err-first');
      const el = document.getElementById('err-last');
      if (!f.value.trim()) { ef.style.display='block'; f.focus(); ok=false; } else ef.style.display='none';
      if (!l.value.trim()) { el.style.display='block'; if(ok) l.focus(); ok=false; } else el.style.display='none';
      if (!ok) e.preventDefault();
    });
  }

  // Toast for success/error
  const toast = document.getElementById('toast');
  <?php if ($success): ?>
  toast.textContent = '<?= addslashes($success) ?>';
  toast.className = 'show success';
  setTimeout(() => { toast.className = ''; }, 3500);
  <?php elseif ($error): ?>
  toast.textContent = '<?= addslashes($error) ?>';
  toast.className = 'show error';
  setTimeout(() => { toast.className = ''; }, 3500);
  <?php endif; ?>
</script>
</body>
</html>
