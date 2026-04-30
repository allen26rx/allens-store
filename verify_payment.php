<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

define('PAYSTACK_SECRET_KEY', 'sk_test_5ad0df7bc71e1b90f8461d830890a199cbdd5232');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$reference = $_POST['reference'] ?? '';
$items     = $_POST['items']     ?? '';
$total     = (float)($_POST['total'] ?? 0);

if (!$reference) {
    echo json_encode(['success' => false, 'message' => 'No reference provided.']);
    exit;
}

// ── Verify with Paystack API ──────────────────────────────────────────────────
$url = 'https://api.paystack.co/transaction/verify/' . rawurlencode($reference);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
    'Cache-Control: no-cache',
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo json_encode(['success' => false, 'message' => 'Could not reach Paystack.']);
    exit;
}

$result = json_decode($response, true);

// Check Paystack says payment succeeded
if (!$result['status'] || $result['data']['status'] !== 'success') {
    echo json_encode(['success' => false, 'message' => 'Payment not confirmed by Paystack.']);
    exit;
}

$paid_amount_kobo = $result['data']['amount'];          // in kobo
$paid_amount_naira = $paid_amount_kobo / 100;
$customer_email   = $result['data']['customer']['email'];

// ── Record order in admin table ───────────────────────────────────────────────
try {
    $db   = databaseConnection();

    // Prevent duplicate recording of same reference
    $check = $db->prepare("SELECT id FROM admin WHERE reference = :ref LIMIT 1");
    $check->execute([':ref' => $reference]);
    if ($check->fetch()) {
        // Already recorded — still clear cart and redirect
        $_SESSION['cart'] = [];
        echo json_encode(['success' => true]);
        exit;
    }

    $stmt = $db->prepare("
        INSERT INTO admin (items, totalprice, email, date_of_purchase, reference)
        VALUES (:items, :total, :email, NOW(), :ref)
    ");
    $stmt->execute([
        ':items' => $items,
        ':total' => $paid_amount_naira,
        ':email' => $customer_email,
        ':ref'   => $reference,
    ]);

    // Also record in orders table for user order history
    $stmt2 = $db->prepare("
        INSERT INTO orders (user_id, items, totalprice, email, reference, status, created_at)
        VALUES (:uid, :items, :total, :email, :ref, 'paid', NOW())
    ");
    $stmt2->execute([
        ':uid'   => (int)$_SESSION['id'],
        ':items' => $items,
        ':total' => $paid_amount_naira,
        ':email' => $customer_email,
        ':ref'   => $reference,
    ]);

    // Clear the cart
    $_SESSION['cart'] = [];

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
?>
