<?php
session_start();
require_once 'db_connection.php';

// ── Hardcoded products (original 12) ─────────────────────────────────────────
$products = [
    1  => ['name' => "Men's Casual Shirt",   'price' => 7500,   'img' => 'https://img.kwcdn.com/product/fmket/39502f00a8a339fdcec27d4e8420efff.jpg'],
    2  => ['name' => 'Round-Neck T-Shirt',    'price' => 6000,   'img' => 'https://img.kwcdn.com/product/fancy/dacae3aa-2caf-42cb-b5e8-4d5e5352e010.jpg'],
    3  => ['name' => 'Casual Sports Pants',   'price' => 12000,  'img' => 'https://img.kwcdn.com/product/fancy/4bd33f3f-e8a0-45d0-a513-b236edbbf84e.jpg'],
    4  => ['name' => 'Graphic Shirt',         'price' => 8000,   'img' => 'https://img.kwcdn.com/product/fmket/f083be53b50a313a348b59777633734a.jpg'],
    5  => ['name' => 'Hair Clipper',          'price' => 10000,  'img' => 'https://img.kwcdn.com/product/fancy/888d5f63-c992-468c-93d1-70d3e6ab0896.jpg'],
    6  => ['name' => 'Mini Projector',        'price' => 55000,  'img' => 'https://img.kwcdn.com/product/fancy/31cdf11d-afa6-48b3-9243-f69b9f4953b8.jpg'],
    7  => ['name' => 'Wireless Earbuds',      'price' => 10000,  'img' => 'https://img.kwcdn.com/product/open/bcef397e2a104f6b87168db2a7e4066a-goods.jpeg'],
    8  => ['name' => 'Air Cooler',            'price' => 126000, 'img' => 'https://img.kwcdn.com/product/fancy/9fdb2a0a-df87-42e8-a121-4d29e894f964.jpg'],
    9  => ['name' => 'Business Shoes',        'price' => 35000,  'img' => 'https://img.kwcdn.com/product/open/1ddff1e4f587454ca2c66870b3e92c81-goods.jpeg'],
    10 => ['name' => 'Casual Shoes',          'price' => 26000,  'img' => 'https://img.kwcdn.com/product/fancy/bc3db0a6-9ffa-4df6-b29a-3564c7071bad.jpg'],
    11 => ['name' => 'Dress Shoes',           'price' => 45000,  'img' => 'https://img.kwcdn.com/product/fancy/f2bc57b0-5498-4fda-a9dc-4c331af6f13f.jpg'],
    12 => ['name' => 'High-Top Sneakers',     'price' => 30300,  'img' => 'https://img.kwcdn.com/product/fancy/cab81cc3-1502-46c3-a9bb-a945e429af01.jpg'],
];

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function send($success, $message, $count = 0, $product = '', $is_ajax = true) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        if (!$success) http_response_code(400);
        echo json_encode([
            'success'    => $success,
            'message'    => $message,
            'cart_count' => $count,
            'product'    => $product,
        ]);
        exit;
    }
    $_SESSION['message'] = $message;
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $raw = $_POST['product_id'] ?? '';

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    // ── DB inventory product: id looks like "db_5" ────────────────────────────
    if (strpos($raw, 'db_') === 0) {
        $dbId = (int) substr($raw, 3);

        if ($dbId <= 0) {
            send(false, 'Invalid product.', 0, '', $is_ajax);
        }

        $db   = databaseConnection();
        $stmt = $db->prepare("SELECT id, name, price, image FROM products WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $dbId]);
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            send(false, 'Product not found.', 0, '', $is_ajax);
        }

        $cartKey = 'db_' . $dbId;
        // Image is stored as a full URL in DB so it works from any folder
        $imgSrc  = !empty($row['image']) ? $row['image'] : '';

        if (isset($_SESSION['cart'][$cartKey])) {
            $_SESSION['cart'][$cartKey]['qty']++;
        } else {
            $_SESSION['cart'][$cartKey] = [
                'name'  => $row['name'],
                'price' => (float) $row['price'],
                'img'   => $imgSrc,
                'qty'   => 1,
            ];
        }

        $total = array_sum(array_column($_SESSION['cart'], 'qty'));
        send(true, $row['name'] . ' added!', $total, $row['name'], $is_ajax);

    // ── Hardcoded product: plain integer ID ───────────────────────────────────
    } else {
        $id = (int) $raw;

        if (!isset($products[$id])) {
            send(false, 'Invalid product.', 0, '', $is_ajax);
        }

        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['qty']++;
        } else {
            $_SESSION['cart'][$id] = [
                'name'  => $products[$id]['name'],
                'price' => $products[$id]['price'],
                'img'   => $products[$id]['img'],
                'qty'   => 1,
            ];
        }

        $total = array_sum(array_column($_SESSION['cart'], 'qty'));
        send(true, $products[$id]['name'] . ' added!', $total, $products[$id]['name'], $is_ajax);
    }
}

header('Location: dashboard.php');
exit;
?>
