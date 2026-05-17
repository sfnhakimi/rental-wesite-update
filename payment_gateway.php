<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['cart']))     { header('Location: cart.php');  exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: payment.php'); exit; }

$rental_date = $_POST['rental_date'] ?? '';

$total = 0;
$cart_items = [];
foreach ($_SESSION['cart'] as $item) {
    $total += $item['total'];
    $cart_items[] = $item;
}

// Simulate processing (keep sleep short for demo)
sleep(1);

// 90% success rate simulation
$payment_success = rand(1, 10) > 1;

if ($payment_success) {
    try {
        foreach ($cart_items as $item) {
            $return_date = date('Y-m-d', strtotime($rental_date . ' + ' . ($item['days'] - 1) . ' days'));
            $stmt = $pdo->prepare("INSERT INTO rentals (user_id, equipment_id, rental_date, return_date, total_price) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $item['id'], $rental_date, $return_date, $item['total']]);
        }
        unset($_SESSION['cart']);
        header('Location: payment_success.php');
        exit;
    } catch (Exception $e) {
        error_log("Payment error: " . $e->getMessage());
        $_SESSION['payment_error'] = 'A system error occurred. Please try again.';
        header('Location: payment.php');
        exit;
    }
} else {
    $_SESSION['payment_error'] = 'Payment was declined. Please check your details and try again.';
    header('Location: payment.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing – RentVenture</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* This page is only shown if PHP somehow doesn't redirect (shouldn't happen in practice) */
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .processing-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 56px 48px;
            text-align: center;
            max-width: 440px;
            width: 100%;
        }
        .spinner-wrap { display: flex; justify-content: center; margin-bottom: 32px; }
        .spinner {
            width: 64px; height: 64px;
            border-radius: 50%;
            border: 3px solid var(--border2);
            border-top-color: var(--accent);
            animation: spin 0.9s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .proc-title { font-family: 'Syne', sans-serif; font-size: 1.6rem; font-weight: 800; margin-bottom: 10px; }
        .proc-sub { color: var(--muted2); font-size: 0.9rem; line-height: 1.6; margin-bottom: 32px; }
        .details-box {
            background: var(--surface2);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 24px;
            text-align: left;
        }
        .detail-row { display: flex; justify-content: space-between; align-items: center; padding: 13px 18px; border-bottom: 1px solid var(--border); font-size: 0.88rem; }
        .detail-row:last-child { border-bottom: none; }
        .detail-row .lbl { color: var(--muted2); }
        .detail-row .val { font-weight: 500; }
        .processing-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(251,191,36,0.1); border: 1px solid rgba(251,191,36,0.2);
            color: #fbbf24; padding: 4px 12px; border-radius: 100px; font-size: 0.78rem; font-weight: 600;
        }
        .proc-note { font-size: 0.78rem; color: var(--muted); margin-top: 20px; }
    </style>
</head>
<body>
    <div class="processing-card">
        <div class="spinner-wrap">
            <div class="spinner"></div>
        </div>
        <h1 class="proc-title">Processing Payment</h1>
        <p class="proc-sub">Please wait while we securely verify your booking. Do not close this window.</p>

        <div class="details-box">
            <div class="detail-row">
                <span class="lbl">Amount</span>
                <span class="val">RM<?php echo number_format($total, 2); ?></span>
            </div>
            <div class="detail-row">
                <span class="lbl">Items</span>
                <span class="val"><?php echo count($cart_items); ?> item<?php echo count($cart_items) != 1 ? 's' : ''; ?></span>
            </div>
            <div class="detail-row">
                <span class="lbl">Status</span>
                <span class="processing-badge">
                    <span style="width:6px;height:6px;border-radius:50%;background:#fbbf24;animation:pulse 1.5s infinite"></span>
                    Processing
                </span>
            </div>
        </div>

        <p class="proc-note">You will be redirected automatically once processing is complete.</p>
    </div>

    <style>
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }
    </style>
</body>
</html>
