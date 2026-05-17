<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_GET['add']) && isset($_GET['equipment_id']) && isset($_GET['days'])) {
    $equipment_id = intval($_GET['equipment_id']);
    $days = intval($_GET['days']);
    $stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ? AND available = 1");
    $stmt->execute([$equipment_id]);
    $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($equipment) {
        $_SESSION['cart'][$equipment_id] = [
            'id'           => $equipment['id'],
            'name'         => $equipment['name'],
            'description'  => $equipment['description'],
            'price_per_day'=> $equipment['price_per_day'],
            'days'         => $days,
            'total'        => $equipment['price_per_day'] * $days,
            'category'     => $equipment['category']
        ];
    }
    header('Location: cart.php');
    exit;
}

if (isset($_GET['remove']) && isset($_GET['equipment_id'])) {
    unset($_SESSION['cart'][intval($_GET['equipment_id'])]);
    header('Location: cart.php');
    exit;
}

$total = array_sum(array_column($_SESSION['cart'], 'total'));

$cat_icons = [
    'Winter'       => '<path d="M12 2v20M4.93 4.93l14.14 14.14M2 12h20M4.93 19.07l14.14-14.14"/>',
    'Water Sports' => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
    'Camping'      => '<polygon points="3 17 12 3 21 17"/><path d="M3 17h18"/>',
];
$cat_colors = ['Winter' => '#60a5fa', 'Water Sports' => '#34d399', 'Camping' => '#f59e0b'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart – RentVenture</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .page-wrap {
            min-height: 100vh;
            padding: 90px 24px 60px;
            max-width: 1000px;
            margin: 0 auto;
        }
        .page-title {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 8px;
        }
        .page-sub { color: var(--muted2); font-size: 0.9rem; margin-bottom: 40px; }

        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 24px;
        }
        @media(max-width: 768px) { .cart-layout { grid-template-columns: 1fr; } }

        /* Cart Items */
        .cart-items { display: flex; flex-direction: column; gap: 16px; }
        .cart-item {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 18px;
            transition: border-color 0.2s;
        }
        .cart-item:hover { border-color: var(--border2); }
        .item-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .item-info { flex: 1; min-width: 0; }
        .item-name { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1rem; margin-bottom: 4px; }
        .item-desc { color: var(--muted); font-size: 0.82rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .item-meta { color: var(--muted2); font-size: 0.82rem; margin-top: 6px; }
        .item-right { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; flex-shrink: 0; }
        .item-price {
            font-family: 'Syne', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent);
        }
        .remove-btn {
            background: transparent;
            border: 1px solid rgba(255,107,107,0.2);
            color: #ff6b6b;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.78rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .remove-btn:hover { background: rgba(255,107,107,0.1); }

        /* Summary box */
        .cart-summary {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 28px 24px;
            height: fit-content;
            position: sticky;
            top: 80px;
        }
        .summary-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }
        .summary-rows { display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px; }
        .summary-row { display: flex; justify-content: space-between; font-size: 0.88rem; }
        .summary-row .label { color: var(--muted2); }
        .summary-row .val { color: var(--text); font-weight: 500; }
        .summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid var(--border2);
            margin-bottom: 20px;
        }
        .summary-total .label { font-weight: 600; }
        .summary-total .val {
            font-family: 'Syne', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--accent);
        }
        .checkout-btn {
            width: 100%;
            padding: 15px;
            background: var(--accent);
            color: #0a0f1e;
            border: none;
            border-radius: 10px;
            font-family: 'Syne', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            margin-bottom: 12px;
        }
        .checkout-btn:hover { background: #c8df20; transform: translateY(-1px); }
        .continue-link {
            display: block;
            text-align: center;
            color: var(--muted2);
            font-size: 0.85rem;
            text-decoration: none;
            transition: color 0.2s;
        }
        .continue-link:hover { color: var(--text); }

        /* Empty state */
        .empty-cart {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 80px 40px;
            text-align: center;
        }
        .empty-cart svg { color: var(--muted); margin-bottom: 20px; opacity: 0.5; }
        .empty-cart h3 { font-family: 'Syne', sans-serif; font-size: 1.3rem; font-weight: 700; margin-bottom: 8px; }
        .empty-cart p { color: var(--muted2); margin-bottom: 28px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-inner">
            <a href="index.php" class="navbar-logo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polygon points="16.24,7.76 14.12,14.12 7.76,16.24 9.88,9.88 16.24,7.76"/></svg>
                <span>RentVenture</span>
            </a>
            <ul class="navbar-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="index.php#packages">Packages</a></li>
                <li><a href="cart.php" class="active">Cart <span class="cart-badge"><?php echo count($_SESSION['cart']); ?></span></a></li>
            </ul>
            <div class="navbar-actions">
                <span class="user-greeting">Hi, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <a href="logout.php" class="btn-nav btn-nav-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="page-wrap">
        <h1 class="page-title">Shopping Cart</h1>
        <p class="page-sub"><?php echo count($_SESSION['cart']); ?> item(s) in your cart</p>

        <?php if (empty($_SESSION['cart'])): ?>
            <div class="empty-cart">
                <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                <h3>Your cart is empty</h3>
                <p>Browse our packages and add some adventure gear!</p>
                <a href="index.php#packages" class="btn btn-accent">Browse Packages</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-items">
                    <?php foreach ($_SESSION['cart'] as $item):
                        $color = $cat_colors[$item['category']] ?? '#6366f1';
                        $icon = $cat_icons[$item['category']] ?? '<circle cx="12" cy="12" r="10"/>';
                    ?>
                    <div class="cart-item">
                        <div class="item-icon" style="background:<?php echo $color; ?>18;">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="<?php echo $color; ?>" stroke-width="1.8" stroke-linecap="round"><?php echo $icon; ?></svg>
                        </div>
                        <div class="item-info">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="item-desc"><?php echo htmlspecialchars($item['description']); ?></div>
                            <div class="item-meta"><?php echo $item['days']; ?> day<?php echo $item['days'] != 1 ? 's' : ''; ?> × RM<?php echo number_format($item['price_per_day'], 2); ?>/day</div>
                        </div>
                        <div class="item-right">
                            <div class="item-price">RM<?php echo number_format($item['total'], 2); ?></div>
                            <a href="cart.php?remove=1&equipment_id=<?php echo $item['id']; ?>" class="remove-btn">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                Remove
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <div class="summary-title">Order Summary</div>
                    <div class="summary-rows">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                        <div class="summary-row">
                            <span class="label"><?php echo htmlspecialchars($item['name']); ?> (<?php echo $item['days']; ?>d)</span>
                            <span class="val">RM<?php echo number_format($item['total'], 2); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="summary-total">
                        <span class="label">Total</span>
                        <span class="val">RM<?php echo number_format($total, 2); ?></span>
                    </div>
                    <a href="payment.php" class="checkout-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        Proceed to Payment
                    </a>
                    <a href="index.php#packages" class="continue-link">← Continue Shopping</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
