<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rental_date = $_POST['rental_date'];
    $return_date = $_POST['return_date'];
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));

    $total = 0;
    $cart_items = [];
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['total'];
        $cart_items[] = $item;
    }

    foreach ($cart_items as $item) {
        $stmt = $pdo->prepare("INSERT INTO rentals (user_id, equipment_id, rental_date, return_date, total_price, status, created_at) VALUES (?, ?, ?, ?, ?, 'approved', NOW())");
        $stmt->execute([$_SESSION['user_id'], $item['id'], $rental_date, $return_date, $item['total']]);
    }

    $booking_ref = 'RV-' . strtoupper(substr(md5(uniqid()), 0, 8));
    unset($_SESSION['cart']);

    $stmt = $pdo->prepare("SELECT r.*, e.name as equipment_name FROM rentals r JOIN equipment e ON r.equipment_id = e.id WHERE r.user_id = ? ORDER BY r.created_at DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $rental = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    header('Location: payment.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed – RentVenture</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .page-wrap { min-height: 100vh; padding: 90px 24px 60px; display: flex; align-items: center; justify-content: center; }
        .success-card { width: 100%; max-width: 560px; }

        /* Animated checkmark */
        .check-wrap { display: flex; justify-content: center; margin-bottom: 32px; }
        .check-circle {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: rgba(74,222,128,0.12);
            border: 2px solid rgba(74,222,128,0.3);
            display: flex; align-items: center; justify-content: center;
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        @keyframes popIn {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .check-circle svg { color: var(--accent2); }

        .success-title {
            font-family: 'Syne', sans-serif;
            font-size: 2rem; font-weight: 800;
            text-align: center; margin-bottom: 8px;
        }
        .success-sub {
            text-align: center; color: var(--muted2);
            font-size: 0.95rem; margin-bottom: 32px; line-height: 1.6;
        }

        /* Booking ref pill */
        .booking-ref {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: rgba(232,255,74,0.07);
            border: 1px solid rgba(232,255,74,0.2);
            border-radius: 100px;
            padding: 10px 20px;
            margin-bottom: 32px;
        }
        .ref-label { font-size: 0.78rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.06em; }
        .ref-code { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; color: var(--accent); letter-spacing: 0.05em; }

        /* Details box */
        .details-box {
            background: var(--surface2);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 28px;
        }
        .details-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            font-family: 'Syne', sans-serif;
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--muted2);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: var(--muted2); }
        .detail-val { font-weight: 500; color: var(--text); }
        .detail-val.accent { color: var(--accent); font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 700; }

        /* Status badge */
        .status-paid {
            background: rgba(74,222,128,0.15);
            color: var(--accent2);
            border: 1px solid rgba(74,222,128,0.25);
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 0.78rem;
            font-weight: 600;
        }

        /* Actions */
        .actions { display: flex; gap: 12px; }
        .actions a { flex: 1; text-align: center; padding: 13px 20px; border-radius: 10px; font-weight: 600; text-decoration: none; font-size: 0.9rem; transition: all 0.2s; }
        .act-primary { background: var(--accent); color: #0a0f1e; }
        .act-primary:hover { background: #c8df20; }
        .act-secondary { background: var(--surface2); color: var(--muted2); border: 1px solid var(--border2); }
        .act-secondary:hover { color: var(--text); background: var(--surface); }

        .confetti-line {
            display: flex;
            justify-content: center;
            gap: 4px;
            margin-bottom: 24px;
        }
        .conf-dot { width: 8px; height: 8px; border-radius: 50%; animation: bounce 1s infinite; }
        .conf-dot:nth-child(2) { animation-delay: 0.15s; }
        .conf-dot:nth-child(3) { animation-delay: 0.3s; }
        @keyframes bounce { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-6px)} }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-inner">
            <a href="index.php" class="navbar-logo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polygon points="16.24,7.76 14.12,14.12 7.76,16.24 9.88,9.88 16.24,7.76"/></svg>
                <span>RentVenture</span>
            </a>
            <div class="navbar-actions">
                <span class="user-greeting">Hi, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <a href="logout.php" class="btn-nav btn-nav-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="page-wrap">
        <div class="success-card">
            <div class="confetti-line">
                <div class="conf-dot" style="background:#e8ff4a"></div>
                <div class="conf-dot" style="background:#4ade80"></div>
                <div class="conf-dot" style="background:#e8ff4a"></div>
            </div>

            <div class="check-wrap">
                <div class="check-circle">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
            </div>

            <h1 class="success-title">Booking Confirmed!</h1>
            <p class="success-sub">Your adventure gear is reserved. We look forward to seeing you at pickup, <?php echo $name; ?>!</p>

            <div class="booking-ref">
                <span class="ref-label">Booking Reference</span>
                <span class="ref-code"><?php echo $booking_ref; ?></span>
            </div>

            <div class="details-box">
                <div class="details-header">Rental Summary</div>
                <div class="detail-row">
                    <span class="detail-label">Equipment</span>
                    <span class="detail-val"><?php echo htmlspecialchars($rental['equipment_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Pickup Date</span>
                    <span class="detail-val"><?php echo date('d M Y', strtotime($rental['rental_date'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Return Date</span>
                    <span class="detail-val"><?php echo date('d M Y', strtotime($rental['return_date'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email Sent To</span>
                    <span class="detail-val"><?php echo $email; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Paid</span>
                    <span class="detail-val accent">RM<?php echo number_format($rental['total_price'], 2); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="status-paid">Confirmed</span>
                </div>
            </div>

            <div class="actions">
                <a href="index.php" class="act-primary">Back to Home</a>
                <a href="index.php#packages" class="act-secondary">Rent More Gear</a>
            </div>
        </div>
    </div>
</body>
</html>
