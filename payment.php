<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['cart'])) { header('Location: cart.php'); exit; }

$total = 0;
$cart_items = [];
foreach ($_SESSION['cart'] as $item) {
    $total += $item['total'];
    $cart_items[] = $item;
}

$error_message = '';
if (isset($_SESSION['payment_error'])) {
    $error_message = $_SESSION['payment_error'];
    unset($_SESSION['payment_error']);
}

$max_days = max(array_column($cart_items, 'days'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment – RentVenture</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .page-wrap { min-height: 100vh; padding: 90px 24px 60px; }
        .page-inner { max-width: 960px; margin: 0 auto; }

        .steps { display: flex; align-items: center; justify-content: center; margin-bottom: 48px; }
        .step { display: flex; align-items: center; gap: 8px; font-size: 0.82rem; }
        .step-num { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.78rem; flex-shrink: 0; }
        .step.done .step-num { background: var(--accent2); color: #0a0f1e; }
        .step.active .step-num { background: var(--accent); color: #0a0f1e; }
        .step.pending .step-num { background: var(--surface2); color: var(--muted); border: 1px solid var(--border2); }
        .step.done .step-label { color: var(--accent2); }
        .step.active .step-label { color: var(--text); font-weight: 600; }
        .step.pending .step-label { color: var(--muted); }
        .step-line { width: 48px; height: 1px; background: var(--border2); margin: 0 10px; }
        .step-line.done { background: var(--accent2); }

        .page-title { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; margin-bottom: 6px; }
        .page-sub { color: var(--muted2); font-size: 0.9rem; margin-bottom: 36px; }

        .layout { display: grid; grid-template-columns: 1fr 360px; gap: 24px; align-items: start; }
        @media(max-width: 768px) { .layout { grid-template-columns: 1fr; } }

        .form-box { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 32px 28px; }
        .summary-box { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 28px 24px; position: sticky; top: 80px; }

        .form-section-title { font-family: 'Syne', sans-serif; font-size: 0.95rem; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; color: var(--text); }
        .fsec-icon { width: 32px; height: 32px; border-radius: 8px; background: rgba(232,255,74,.1); display: flex; align-items: center; justify-content: center; }
        .fsec-icon svg { color: var(--accent); }
        .form-divider { height: 1px; background: var(--border); margin: 28px 0; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media(max-width: 500px) { .form-row { grid-template-columns: 1fr; } }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 0.78rem; font-weight: 500; color: var(--muted); margin-bottom: 8px; letter-spacing: 0.05em; text-transform: uppercase; }
        input[type="text"], input[type="email"], input[type="date"] {
            width: 100%; padding: 13px 16px;
            background: var(--surface2); border: 1px solid var(--border);
            border-radius: 10px; color: var(--text);
            font-family: 'DM Sans', sans-serif; font-size: 0.95rem;
            outline: none; transition: border-color 0.2s, box-shadow 0.2s;
        }
        input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(232,255,74,0.08); }
        input::placeholder { color: var(--muted); }
        .input-hint { font-size: 0.75rem; color: var(--muted); margin-top: 5px; }

        .error-box { background: rgba(255,107,107,0.08); border: 1px solid rgba(255,107,107,0.25); color: #ff6b6b; padding: 12px 16px; border-radius: 10px; font-size: 0.88rem; margin-bottom: 24px; }

        .submit-btn {
            width: 100%; padding: 16px; background: var(--accent); color: #0a0f1e;
            border: none; border-radius: 10px; font-family: 'Syne', sans-serif;
            font-size: 1rem; font-weight: 700; cursor: pointer;
            transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 8px;
        }
        .submit-btn:hover { background: #c8df20; transform: translateY(-1px); box-shadow: 0 6px 24px rgba(232,255,74,0.2); }
        .back-link { display: inline-flex; align-items: center; gap: 6px; color: var(--muted2); font-size: 0.88rem; text-decoration: none; margin-top: 16px; transition: color 0.2s; }
        .back-link:hover { color: var(--text); }

        .summary-title { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border); }
        .summary-items { display: flex; flex-direction: column; gap: 14px; margin-bottom: 20px; }
        .si { display: flex; align-items: center; gap: 12px; }
        .si-icon { width: 38px; height: 38px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.1rem; }
        .si-info { flex: 1; }
        .si-name { font-size: 0.85rem; font-weight: 500; }
        .si-days { font-size: 0.75rem; color: var(--muted); margin-top: 2px; }
        .si-price { font-size: 0.9rem; font-weight: 600; }
        .summary-divider { height: 1px; background: var(--border); margin: 16px 0; }
        .summary-total { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .summary-total .lbl { font-weight: 600; }
        .summary-total .val { font-family: 'Syne', sans-serif; font-size: 1.6rem; font-weight: 800; color: var(--accent); }
        .secure-note { display: flex; align-items: center; gap: 8px; font-size: 0.78rem; color: var(--muted); background: var(--surface2); padding: 10px 14px; border-radius: 8px; }
        .secure-note svg { color: var(--accent2); flex-shrink: 0; }
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
                <li><a href="cart.php">Cart</a></li>
            </ul>
            <div class="navbar-actions">
                <span class="user-greeting">Hi, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <a href="logout.php" class="btn-nav btn-nav-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="page-wrap">
        <div class="page-inner">

            <div class="steps">
                <div class="step done">
                    <div class="step-num"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
                    <span class="step-label">Cart</span>
                </div>
                <div class="step-line done"></div>
                <div class="step active">
                    <div class="step-num">2</div>
                    <span class="step-label">Details</span>
                </div>
                <div class="step-line"></div>
                <div class="step pending">
                    <div class="step-num">3</div>
                    <span class="step-label">Confirm</span>
                </div>
            </div>

            <h1 class="page-title">Rental Details</h1>
            <p class="page-sub">Fill in your information to complete the booking</p>

            <?php if ($error_message): ?>
                <div class="error-box"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="layout">
                <div class="form-box">
                    <form method="POST" action="payment_success.php">

                        <div class="form-section-title">
                            <div class="fsec-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                            Rental Period
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="rental_date">Pickup Date</label>
                                <input type="date" id="rental_date" name="rental_date" required min="<?php echo date('Y-m-d'); ?>">
                                <p class="input-hint">Earliest available: today</p>
                            </div>
                            <div class="form-group">
                                <label for="return_date">Return Date</label>
                                <input type="date" id="return_date" name="return_date" required>
                                <p class="input-hint" id="return-hint">Select pickup date first</p>
                            </div>
                        </div>

                        <div class="form-divider"></div>

                        <div class="form-section-title">
                            <div class="fsec-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
                            Your Information
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" placeholder="Ahmad bin Salleh" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" placeholder="you@email.com" required>
                            </div>
                        </div>

                        <button type="submit" class="submit-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                            Confirm &amp; Complete Booking
                        </button>
                    </form>
                    <a href="cart.php" class="back-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                        Back to Cart
                    </a>
                </div>

                <div class="summary-box">
                    <div class="summary-title">Order Summary</div>
                    <div class="summary-items">
                        <?php
                        $cat_emoji = ['Winter'=>'❄️','Water Sports'=>'🌊','Camping'=>'⛺'];
                        $cat_bg = ['Winter'=>'rgba(96,165,250,.15)','Water Sports'=>'rgba(52,211,153,.15)','Camping'=>'rgba(245,158,11,.15)'];
                        foreach ($cart_items as $item):
                            $cat = $item['category'];
                        ?>
                        <div class="si">
                            <div class="si-icon" style="background:<?php echo $cat_bg[$cat] ?? 'rgba(99,102,241,.15)'; ?>"><?php echo $cat_emoji[$cat] ?? '📦'; ?></div>
                            <div class="si-info">
                                <div class="si-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="si-days"><?php echo $item['days']; ?>d × RM<?php echo number_format($item['price_per_day'],2); ?>/day</div>
                            </div>
                            <div class="si-price">RM<?php echo number_format($item['total'],2); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="summary-divider"></div>
                    <div class="summary-total">
                        <span class="lbl">Total</span>
                        <span class="val">RM<?php echo number_format($total,2); ?></span>
                    </div>
                    <div class="secure-note">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Secure booking — no payment card needed for this demo.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const maxDays = <?php echo (int)$max_days; ?>;
        document.getElementById('rental_date').addEventListener('change', function() {
            if (!this.value) return;
            const rental = new Date(this.value);
            const auto = new Date(rental);
            auto.setDate(rental.getDate() + maxDays);
            const minR = new Date(rental);
            minR.setDate(rental.getDate() + 1);
            document.getElementById('return_date').min = minR.toISOString().split('T')[0];
            document.getElementById('return_date').value = auto.toISOString().split('T')[0];
            document.getElementById('return-hint').textContent = 'Auto-set (' + maxDays + ' days from pickup)';
        });
    </script>
</body>
</html>
