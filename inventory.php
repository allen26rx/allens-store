<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$db      = databaseConnection();
$success = '';
$error   = '';

// ── Ensure products table exists ──────────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    price       DECIMAL(12,2) NOT NULL,
    image       VARCHAR(500) NOT NULL DEFAULT '',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Ensure uploads folder exists
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Build the base URL for this folder so images work from any other folder
// e.g. http://localhost/ax/uploads/prod_xxx.jpg
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'];
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$baseUrl   = $protocol . '://' . $host . $scriptDir . '/uploads/';

// ── ADD PRODUCT ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name  = trim($_POST['name']  ?? '');
    $price = trim($_POST['price'] ?? '');

    if (!$name || !is_numeric($price) || $price < 0) {
        $error = 'Please enter a valid product name and price.';
    } else {
        $imagePath = '';

        if (!empty($_FILES['image']['tmp_name'])) {
            $ext       = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed   = ['jpg','jpeg','png','webp','gif'];
            if (!in_array($ext, $allowed)) {
                $error = 'Only JPG, PNG, WEBP, GIF images are allowed.';
            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $error = 'Image must be under 5MB.';
            } else {
                $filename  = 'prod_' . uniqid() . '.' . $ext;
                $dest      = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $imagePath = $baseUrl . $filename; // full URL — works from any folder
                } else {
                    $error = 'Image upload failed. Check folder permissions.';
                }
            }
        }

        if (!$error) {
            $stmt = $db->prepare("INSERT INTO products (name, price, image) VALUES (:name, :price, :image)");
            $stmt->execute([':name' => htmlspecialchars($name), ':price' => (float)$price, ':image' => $imagePath]);
            $success = "Product \"$name\" added successfully!";
        }
    }
}

// ── EDIT PRODUCT ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id    = (int)($_POST['product_id'] ?? 0);
    $name  = trim($_POST['name']  ?? '');
    $price = trim($_POST['price'] ?? '');

    if (!$id || !$name || !is_numeric($price) || $price < 0) {
        $error = 'Invalid data. Please check fields.';
    } else {
        // Check for new image
        $fetch  = $db->prepare("SELECT image FROM products WHERE id = :id");
        $fetch->execute([':id' => $id]);
        $oldImg = $fetch->fetchColumn();
        $imagePath = $oldImg;

        if (!empty($_FILES['image']['tmp_name'])) {
            $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp','gif'];
            if (!in_array($ext, $allowed)) {
                $error = 'Only JPG, PNG, WEBP, GIF images are allowed.';
            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $error = 'Image must be under 5MB.';
            } else {
                $filename = 'prod_' . uniqid() . '.' . $ext;
                $dest     = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    // Remove old file (only if it's a local path not a full URL)
                    if ($oldImg && !str_starts_with($oldImg, 'http') && file_exists(__DIR__ . '/' . $oldImg)) {
                        unlink(__DIR__ . '/' . $oldImg);
                    }
                    $imagePath = $baseUrl . $filename; // full URL — works from any folder
                } else {
                    $error = 'Image upload failed.';
                }
            }
        }

        if (!$error) {
            $stmt = $db->prepare("UPDATE products SET name=:name, price=:price, image=:image WHERE id=:id");
            $stmt->execute([':name' => htmlspecialchars($name), ':price' => (float)$price, ':image' => $imagePath, ':id' => $id]);
            $success = "Product updated successfully!";
        }
    }
}

// ── DELETE PRODUCT ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['product_id'] ?? 0);
    if ($id) {
        $fetch = $db->prepare("SELECT image FROM products WHERE id=:id");
        $fetch->execute([':id' => $id]);
        $img = $fetch->fetchColumn();
        if ($img && file_exists(__DIR__ . '/' . $img)) {
            unlink(__DIR__ . '/' . $img);
        }
        $db->prepare("DELETE FROM products WHERE id=:id")->execute([':id' => $id]);
        $success = 'Product deleted.';
    }
}

// ── FETCH ALL PRODUCTS ────────────────────────────────────────────────────────
$products = $db->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory — Allens Store Admin</title>
<link rel="stylesheet" href="Dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  .admin-content { width:100%; background:rgb(219,235,249); min-height:calc(100vh - 110px); padding:28px; }

  /* ── Top action bar ── */
  .inv-topbar {
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:22px; flex-wrap:wrap; gap:12px;
  }
  .btn-add {
    padding:10px 22px; background:linear-gradient(135deg,#3b82f6,#2563eb);
    color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:700;
    cursor:pointer; transition:.25s; display:flex; align-items:center; gap:8px;
    font-family:Arial,Helvetica,sans-serif;
  }
  .btn-add:hover { background:#172036; transform:translateY(-2px); box-shadow:0 6px 16px rgba(37,99,235,.3); }

  /* ── Product Grid ── */
  .inv-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(210px, 1fr));
    gap:20px;
  }

  .inv-card {
    background:#fff; border-radius:10px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    overflow:hidden; transition:.25s;
    display:flex; flex-direction:column;
  }
  .inv-card:hover { transform:translateY(-4px); box-shadow:0 10px 24px rgba(0,0,0,0.13); }

  .inv-card img {
    width:100%; height:190px; object-fit:cover;
    border-radius:0; border-bottom:1px solid #eef2fb;
  }
  .inv-card-no-img {
    width:100%; height:190px; background:#eaf2ff;
    display:flex; align-items:center; justify-content:center;
    color:#93b4d8; font-size:3rem; border-bottom:1px solid #eef2fb;
  }

  .inv-card-body { padding:12px 14px; flex:1; display:flex; flex-direction:column; }
  .inv-card-body h6 { font-size:13px; font-weight:700; color:#172036; margin-bottom:4px; }
  .inv-card-body p  { font-size:14px; font-weight:800; color:#2563eb; margin-bottom:auto; }

  .inv-card-actions {
    display:flex; gap:8px; margin-top:12px;
  }
  .btn-edit {
    flex:1; padding:8px; border:none; border-radius:6px;
    background:#172036; color:#fff; font-size:12px; font-weight:700;
    cursor:pointer; transition:.2s; font-family:Arial,Helvetica,sans-serif;
    display:flex; align-items:center; justify-content:center; gap:5px;
  }
  .btn-edit:hover { background:#10192b; }
  .btn-del {
    padding:8px 12px; border:none; border-radius:6px;
    background:#fff; color:#b91c1c; border:1.5px solid #fca5a5;
    font-size:12px; font-weight:700; cursor:pointer; transition:.2s;
    font-family:Arial,Helvetica,sans-serif;
  }
  .btn-del:hover { background:#b91c1c; color:#fff; border-color:#b91c1c; }

  .empty-inv { text-align:center; padding:60px; color:#9aabcc; font-size:13px; }
  .empty-inv i { display:block; font-size:3rem; margin-bottom:12px; }

  /* ── Modal Overlay ── */
  .modal-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,0.55); z-index:999;
    align-items:center; justify-content:center; padding:16px;
  }
  .modal-overlay.open { display:flex; }

  .modal {
    background:#fff; border-radius:12px; width:100%; max-width:480px;
    box-shadow:0 20px 60px rgba(0,0,0,0.25); overflow:hidden;
    animation:slideUp .25s ease;
  }
  @keyframes slideUp {
    from { transform:translateY(30px); opacity:0; }
    to   { transform:translateY(0);    opacity:1; }
  }

  .modal-header {
    background:#172036; padding:14px 20px;
    display:flex; align-items:center; justify-content:space-between;
  }
  .modal-header h3 { font-size:14px; font-weight:800; color:#fff; display:flex; align-items:center; gap:8px; }
  .modal-header h3 i { color:orange; }
  .modal-close {
    background:none; border:none; color:rgba(255,255,255,0.6);
    font-size:18px; cursor:pointer; transition:.2s; padding:4px;
  }
  .modal-close:hover { color:#fff; }

  .modal-body { padding:22px 20px; }

  /* Image preview */
  .img-upload-area {
    border:2px dashed #c8d6ed; border-radius:8px;
    padding:20px; text-align:center; cursor:pointer;
    transition:.2s; margin-bottom:14px; position:relative;
  }
  .img-upload-area:hover { border-color:#3b82f6; background:#f4f8ff; }
  .img-upload-area input[type="file"] {
    position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%;
  }
  .img-preview {
    width:100%; height:150px; object-fit:cover;
    border-radius:6px; display:none; margin-bottom:8px;
  }
  .img-placeholder { color:#93b4d8; }
  .img-placeholder i { font-size:2rem; display:block; margin-bottom:6px; }
  .img-placeholder span { font-size:12px; }

  /* Delete confirm */
  #delete-modal .modal-body { text-align:center; padding:30px 24px; }
  #delete-modal .modal-body i { font-size:2.8rem; color:#f97316; margin-bottom:12px; display:block; }
  #delete-modal .modal-body p { font-size:14px; color:#172036; font-weight:600; margin-bottom:6px; }
  #delete-modal .modal-body small { color:#6b7280; font-size:12px; }
  .del-actions { display:flex; gap:10px; margin-top:20px; justify-content:center; }
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
      <a href="record.php"><i class="fa-solid fa-receipt"></i> Records</a>
      <a href="inventory.php" class="active"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a>
      <!-- <a href="dashboard.php"><i class="fa-solid fa-store"></i> Store</a> -->
      <a href="login.php" onclick="return confirm('Log out?')"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
  </div>

  <div class="bar2">
    <div class="text">Admin Panel &nbsp;|&nbsp; Sales Records &nbsp;|&nbsp; Inventory Management &nbsp;|&nbsp; Store Overview</div>
  </div>

  <div class="admin-content">

    <?php if ($success): ?>
    <div class="alert alert-success" style="margin-bottom:18px;">
      <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error" style="margin-bottom:18px;">
      <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="inv-topbar">
      <div class="page-title" style="margin-bottom:0;">
        <i class="fa-solid fa-boxes-stacked"></i> Inventory
        <span style="font-size:12px;background:#eaf2ff;color:#3b82f6;padding:3px 10px;border-radius:999px;font-weight:700;"><?= count($products) ?> products</span>
      </div>
      <button class="btn-add" onclick="openAddModal()">
        <i class="fa-solid fa-plus"></i> Add Product
      </button>
    </div>

    <!-- Product Grid -->
    <?php if (empty($products)): ?>
      <div class="empty-inv">
        <i class="fa-solid fa-boxes-stacked"></i>
        No products yet. Click <strong>Add Product</strong> to get started.
      </div>
    <?php else: ?>
    <div class="inv-grid">
      <?php foreach ($products as $p): ?>
      <div class="inv-card">
        <?php if ($p['image'] && file_exists(__DIR__ . '/' . $p['image'])): ?>
          <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
        <?php elseif ($p['image']): ?>
          <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
          <div class="inv-card-no-img" style="display:none;"><i class="fa-solid fa-image"></i></div>
        <?php else: ?>
          <div class="inv-card-no-img"><i class="fa-solid fa-image"></i></div>
        <?php endif; ?>

        <div class="inv-card-body">
          <h6><?= htmlspecialchars($p['name']) ?></h6>
          <p>₦<?= number_format((float)$p['price'], 0) ?></p>
          <div class="inv-card-actions">
            <button class="btn-edit" onclick='openEditModal(<?= json_encode($p) ?>)'>
              <i class="fa-solid fa-pen"></i> Edit
            </button>
            <button class="btn-del" onclick="openDeleteModal(<?= (int)$p['id'] ?>, <?= htmlspecialchars(json_encode($p['name'])) ?>)">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</div><!-- /container -->


<!-- ══════════════════════════════════════════
     ADD PRODUCT MODAL
══════════════════════════════════════════ -->
<div class="modal-overlay" id="add-modal">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fa-solid fa-plus"></i> Add New Product</h3>
      <button class="modal-close" onclick="closeModal('add-modal')">&times;</button>
    </div>
    <div class="modal-body">
      <form method="POST" enctype="multipart/form-data" id="add-form">
        <input type="hidden" name="action" value="add">

        <!-- Image Upload -->
        <div class="img-upload-area" id="add-upload-area">
          <input type="file" name="image" accept="image/*" id="add-image-input" onchange="previewImage(this,'add-preview','add-placeholder')">
          <img id="add-preview" class="img-preview" src="" alt="Preview">
          <div class="img-placeholder" id="add-placeholder">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <span>Click to upload product image<br><small style="color:#b0bcd8;">(JPG, PNG, WEBP — max 5MB)</small></span>
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group full">
            <label>Product Name</label>
            <input type="text" name="name" placeholder="e.g. Men's Casual Shirt" required>
          </div>
          <div class="form-group full">
            <label>Price (₦)</label>
            <input type="number" name="price" placeholder="e.g. 7500" min="0" step="0.01" required>
          </div>
        </div>

        <div class="form-actions" style="margin-top:18px;">
          <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Product</button>
          <button type="button" class="btn-secondary" onclick="closeModal('add-modal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════
     EDIT PRODUCT MODAL
══════════════════════════════════════════ -->
<div class="modal-overlay" id="edit-modal">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fa-solid fa-pen"></i> Edit Product</h3>
      <button class="modal-close" onclick="closeModal('edit-modal')">&times;</button>
    </div>
    <div class="modal-body">
      <form method="POST" enctype="multipart/form-data" id="edit-form">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="product_id" id="edit-product-id">

        <!-- Image Upload -->
        <div class="img-upload-area" id="edit-upload-area">
          <input type="file" name="image" accept="image/*" id="edit-image-input" onchange="previewImage(this,'edit-preview','edit-placeholder')">
          <img id="edit-preview" class="img-preview" src="" alt="Preview">
          <div class="img-placeholder" id="edit-placeholder">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <span>Click to replace image<br><small style="color:#b0bcd8;">(Leave empty to keep current)</small></span>
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group full">
            <label>Product Name</label>
            <input type="text" name="name" id="edit-name" placeholder="Product name" required>
          </div>
          <div class="form-group full">
            <label>Price (₦)</label>
            <input type="number" name="price" id="edit-price" placeholder="e.g. 7500" min="0" step="0.01" required>
          </div>
        </div>

        <div class="form-actions" style="margin-top:18px;">
          <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
          <button type="button" class="btn-secondary" onclick="closeModal('edit-modal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════
     DELETE CONFIRM MODAL
══════════════════════════════════════════ -->
<div class="modal-overlay" id="delete-modal">
  <div class="modal" style="max-width:380px;">
    <div class="modal-header">
      <h3><i class="fa-solid fa-triangle-exclamation"></i> Confirm Delete</h3>
      <button class="modal-close" onclick="closeModal('delete-modal')">&times;</button>
    </div>
    <div class="modal-body">
      <form method="POST" id="delete-form">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="product_id" id="delete-product-id">
        <i class="fa-solid fa-trash-can"></i>
        <p id="delete-product-name">Delete this product?</p>
        <small>This will permanently remove the product and its image from the store.</small>
        <div class="del-actions">
          <button type="submit" class="btn-danger" style="cursor:pointer;font-family:Arial,Helvetica,sans-serif;">
            <i class="fa-solid fa-trash"></i> Yes, Delete
          </button>
          <button type="button" class="btn-secondary" onclick="closeModal('delete-modal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>


<script>
  // ── Modal helpers ──────────────────────────────────────────────────────────
  function openModal(id)  {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
  }

  // Close on backdrop click
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) closeModal(overlay.id);
    });
  });

  // ── Add modal ──────────────────────────────────────────────────────────────
  function openAddModal() {
    document.getElementById('add-form').reset();
    document.getElementById('add-preview').style.display   = 'none';
    document.getElementById('add-placeholder').style.display = 'block';
    openModal('add-modal');
  }

  // ── Edit modal ─────────────────────────────────────────────────────────────
  function openEditModal(product) {
    document.getElementById('edit-product-id').value = product.id;
    document.getElementById('edit-name').value        = product.name;
    document.getElementById('edit-price').value       = product.price;
    document.getElementById('edit-image-input').value = '';

    const preview = document.getElementById('edit-preview');
    const placeholder = document.getElementById('edit-placeholder');

    if (product.image) {
      preview.src            = product.image;
      preview.style.display  = 'block';
      placeholder.style.display = 'none';
    } else {
      preview.style.display     = 'none';
      placeholder.style.display = 'block';
    }
    openModal('edit-modal');
  }

  // ── Delete modal ───────────────────────────────────────────────────────────
  function openDeleteModal(id, name) {
    document.getElementById('delete-product-id').value = id;
    document.getElementById('delete-product-name').textContent = 'Delete "' + name + '"?';
    openModal('delete-modal');
  }

  // ── Image preview ──────────────────────────────────────────────────────────
  function previewImage(input, previewId, placeholderId) {
    const preview     = document.getElementById(previewId);
    const placeholder = document.getElementById(placeholderId);
    const file        = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      preview.src           = e.target.result;
      preview.style.display = 'block';
      placeholder.style.display = 'none';
    };
    reader.readAsDataURL(file);
  }

  // ── Auto-open modal if there was a form error (keeps modal open after POST) ──
  <?php if ($error && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
  <?php $act = $_POST['action'] ?? ''; ?>
  <?php if ($act === 'add'): ?>
  window.addEventListener('DOMContentLoaded', () => openAddModal());
  <?php elseif ($act === 'edit'): ?>
  window.addEventListener('DOMContentLoaded', () => openModal('edit-modal'));
  <?php endif; ?>
  <?php endif; ?>
</script>


</body>
</html>
