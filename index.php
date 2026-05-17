<?php
session_start();
include 'config.php';

$query = "SELECT * FROM equipment WHERE available = 1";
$params = [];
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $query .= " AND category = ?";
    $params[] = $_GET['category'];
}
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT DISTINCT category FROM equipment WHERE available = 1 ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

$user_rentals = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT r.*, e.name as equipment_name FROM rentals r JOIN equipment e ON r.equipment_id = e.id WHERE r.user_id = ? ORDER BY r.created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $user_rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$category_icons = [
    'Winter'      => '<path d="M12 2v20M4.93 4.93l14.14 14.14M2 12h20M4.93 19.07l14.14-14.14"/>',
    'Water Sports'=> '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
    'Camping'     => '<polygon points="3 17 12 3 21 17"/><path d="M3 17h18"/>',
];
$category_colors = [
    'Winter'      => '#60a5fa',
    'Water Sports'=> '#34d399',
    'Camping'     => '#f59e0b',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentVenture – Adventure Equipment Rentals</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── Hero ── */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            background: var(--bg);
        }
        .hero-bg {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 60% 40%, rgba(99,102,241,0.15) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 20% 80%, rgba(232,255,74,0.07) 0%, transparent 50%);
        }
        .hero-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
        }
        .hero-inner {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 120px 24px 80px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }
        @media(max-width: 768px) {
            .hero-inner { grid-template-columns: 1fr; padding: 100px 24px 60px; }
            .hero-visual { display: none; }
        }
        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(232,255,74,0.08);
            border: 1px solid rgba(232,255,74,0.2);
            color: var(--accent);
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 24px;
        }
        .hero-eyebrow-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--accent);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        .hero h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(2.5rem, 6vw, 4.2rem);
            font-weight: 800;
            line-height: 1.05;
            margin-bottom: 24px;
        }
        .hero h1 em { font-style: normal; color: var(--accent); }
        .hero-sub {
            color: var(--muted2);
            font-size: 1.05rem;
            line-height: 1.7;
            margin-bottom: 40px;
            max-width: 440px;
        }
        .hero-cta { display: flex; gap: 14px; flex-wrap: wrap; }

        /* Visual side */
        .hero-visual {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .hero-card-stack { position: relative; width: 320px; height: 380px; }
        .stack-card {
            position: absolute;
            width: 260px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
        }
        .stack-card:nth-child(1) { top: 0; left: 0; transform: rotate(-6deg); opacity: 0.5; }
        .stack-card:nth-child(2) { top: 20px; left: 30px; transform: rotate(-2deg); opacity: 0.75; }
        .stack-card:nth-child(3) { top: 40px; left: 60px; transform: rotate(1deg); box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
        .stack-card .card-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 16px;
        }
        .stack-card h3 { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1rem; margin-bottom: 6px; }
        .stack-card p { color: var(--muted); font-size: 0.8rem; margin-bottom: 14px; }
        .stack-price { font-family: 'Syne', sans-serif; font-size: 1.4rem; font-weight: 800; color: var(--accent); }
        .stack-price span { font-size: 0.8rem; font-weight: 400; color: var(--muted); }

        /* Stats row */
        .stats-row {
            background: var(--surface);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            padding: 32px 24px;
        }
        .stats-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 32px;
            text-align: center;
        }
        .stat-num {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--accent);
        }
        .stat-label { color: var(--muted2); font-size: 0.85rem; margin-top: 4px; }

        /* Packages */
        .packages-section { padding: 100px 24px; }
        .packages-header {
            max-width: 1200px;
            margin: 0 auto 60px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 24px;
        }
        .filter-bar { display: flex; gap: 8px; flex-wrap: wrap; }
        .filter-btn {
            padding: 8px 18px;
            border-radius: 100px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid var(--border2);
            color: var(--muted2);
            background: transparent;
        }
        .filter-btn:hover, .filter-btn.active {
            background: var(--accent);
            color: #0a0f1e;
            border-color: var(--accent);
            font-weight: 600;
        }

        /* Equipment grid */
        .equipment-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }
        .eq-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
        }
        .eq-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            border-color: var(--border2);
        }
        .eq-card-top {
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .eq-card-top svg { opacity: 0.9; }
        .eq-card-badge {
            position: absolute;
            top: 14px; left: 14px;
        }
        .eq-card-body { padding: 24px; }
        .eq-card-name { font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 700; margin-bottom: 8px; }
        .eq-card-desc { color: var(--muted2); font-size: 0.85rem; line-height: 1.6; margin-bottom: 20px; }
        .eq-card-footer { display: flex; align-items: center; justify-content: space-between; }
        .eq-price {
            font-family: 'Syne', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--accent);
        }
        .eq-price span { font-size: 0.8rem; font-weight: 400; color: var(--muted); }
        .rent-btn {
            padding: 10px 20px;
            background: var(--accent);
            color: #0a0f1e;
            border: none;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .rent-btn:hover { background: #c8df20; transform: translateY(-1px); }
        .rent-btn-ghost {
            background: transparent;
            color: var(--muted2);
            border: 1px solid var(--border2);
        }
        .rent-btn-ghost:hover { background: var(--surface2); color: var(--text); }

        /* My Rentals */
        .rentals-section {
            background: var(--surface);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            padding: 80px 24px;
        }
        .rentals-inner { max-width: 1200px; margin: 0 auto; }
        .rentals-table-wrap { overflow-x: auto; margin-top: 32px; }
        table { width: 100%; border-collapse: collapse; }
        th {
            padding: 12px 16px;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
        }
        td {
            padding: 16px;
            font-size: 0.88rem;
            color: var(--muted2);
            border-bottom: 1px solid var(--border);
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,0.02); }
        td.bold { color: var(--text); font-weight: 500; }

        /* About */
        .about-section { padding: 100px 24px; }
        .about-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
        }
        @media(max-width: 768px) { .about-inner { grid-template-columns: 1fr; gap: 40px; } }
        .info-list { margin-top: 32px; display: flex; flex-direction: column; gap: 0; }
        .info-row {
            display: grid;
            grid-template-columns: 130px 1fr;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }
        .info-row:first-child { border-top: 1px solid var(--border); }
        .info-label { color: var(--muted); }
        .info-val { color: var(--muted2); }

        .about-map-placeholder {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            height: 320px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: var(--muted);
        }
        .about-map-placeholder svg { color: var(--accent); opacity: 0.6; }

        /* Days modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
            z-index: 200;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: var(--surface);
            border: 1px solid var(--border2);
            border-radius: 16px;
            padding: 36px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.6);
        }
        .modal h3 {
            font-family: 'Syne', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .modal p { color: var(--muted2); font-size: 0.88rem; margin-bottom: 24px; }
        .days-input {
            width: 100%;
            padding: 14px;
            background: var(--surface2);
            border: 1px solid var(--border2);
            border-radius: 10px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 1rem;
            outline: none;
            margin-bottom: 16px;
        }
        .days-input:focus { border-color: var(--accent); }
        .modal-actions { display: flex; gap: 10px; }
        .modal-actions button { flex: 1; padding: 12px; border-radius: 10px; font-weight: 600; cursor: pointer; border: none; font-family: 'DM Sans', sans-serif; font-size: 0.9rem; }
        .modal-cancel { background: var(--surface2); color: var(--muted2); }
        .modal-confirm { background: var(--accent); color: #0a0f1e; }
        .modal-confirm:hover { background: #c8df20; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-inner">
            <a href="index.php" class="navbar-logo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polygon points="16.24,7.76 14.12,14.12 7.76,16.24 9.88,9.88 16.24,7.76"/></svg>
                <span>RentVenture</span>
            </a>
            <ul class="navbar-links">
                <li><a href="#" class="active">Home</a></li>
                <li><a href="#packages">Packages</a></li>
                <li><a href="#about">About</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                <li>
                    <a href="cart.php">
                        Cart
                        <span class="cart-badge"><?php echo count($_SESSION['cart'] ?? []); ?></span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <div class="navbar-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="user-greeting">Hi, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <a href="admin.php" class="btn-nav btn-nav-ghost">Admin</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn-nav btn-nav-danger">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn-nav btn-nav-ghost">Login</a>
                    <a href="register.php" class="btn-nav btn-nav-accent">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-grid"></div>
        <div class="hero-inner">
            <div class="hero-content">
                <div class="hero-eyebrow">
                    <div class="hero-eyebrow-dot"></div>
                    Now open in Perak
                </div>
                <h1>Gear Up for Your Next <em>Adventure</em></h1>
                <p class="hero-sub">Premium quality travel equipment rentals at affordable prices. Winter, water sports, camping — we've got everything you need.</p>
                <div class="hero-cta">
                    <a href="#packages" class="btn btn-accent">Browse Packages</a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="btn btn-outline">Create Free Account</a>
                    <?php else: ?>
                    <a href="cart.php" class="btn btn-outline">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        View Cart (<?php echo count($_SESSION['cart'] ?? []); ?>)
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-card-stack">
                    <div class="stack-card">
                        <div class="card-icon" style="background:rgba(96,165,250,0.15);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2"><path d="M12 2v20M4.93 4.93l14.14 14.14M2 12h20M4.93 19.07l14.14-14.14"/></svg>
                        </div>
                        <h3>Snow Tent</h3>
                        <p>Insulated for extreme cold</p>
                        <div class="stack-price">RM40 <span>/day</span></div>
                    </div>
                    <div class="stack-card">
                        <div class="card-icon" style="background:rgba(52,211,153,0.15);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                        </div>
                        <h3>Action Camera</h3>
                        <p>Waterproof adventure cam</p>
                        <div class="stack-price">RM30 <span>/day</span></div>
                    </div>
                    <div class="stack-card">
                        <div class="card-icon" style="background:rgba(245,158,11,0.15);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><polygon points="3 17 12 3 21 17"/><path d="M3 17h18"/></svg>
                        </div>
                        <h3>2-Person Tent</h3>
                        <p>Lightweight camping tent</p>
                        <div class="stack-price">RM35 <span>/day</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stats-inner">
            <div>
                <div class="stat-num">12+</div>
                <div class="stat-label">Equipment Types</div>
            </div>
            <div>
                <div class="stat-num">3</div>
                <div class="stat-label">Adventure Categories</div>
            </div>
            <div>
                <div class="stat-num">2015</div>
                <div class="stat-label">Est. Since</div>
            </div>
            <div>
                <div class="stat-num">RM5</div>
                <div class="stat-label">Starting From / Day</div>
            </div>
        </div>
    </div>

    <!-- Packages -->
    <section id="packages" class="packages-section">
        <div class="packages-header">
            <div>
                <p class="section-label">Equipment</p>
                <h2 class="section-title">Choose Your Gear</h2>
                <p class="section-sub">We've got everything for any type of adventure at great daily rates.</p>
            </div>
            <div class="filter-bar">
                <a href="index.php#packages" class="filter-btn <?php echo !isset($_GET['category']) ? 'active' : ''; ?>">All</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="index.php?category=<?php echo urlencode($cat); ?>#packages"
                       class="filter-btn <?php echo (isset($_GET['category']) && $_GET['category'] == $cat) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($equipment)): ?>
            <div style="max-width:1200px;margin:0 auto;text-align:center;padding:80px 0;color:var(--muted);">
                <p style="font-size:1.1rem;margin-bottom:16px;">No equipment in this category.</p>
                <a href="index.php#packages" class="btn btn-outline">View All</a>
            </div>
        <?php else: ?>
        <div class="equipment-grid">
            <?php foreach ($equipment as $item):
                $cat = $item['category'];
                $iconPath = $category_icons[$cat] ?? '<circle cx="12" cy="12" r="10"/>';
                $color = $category_colors[$cat] ?? '#6366f1';
                $bgColor = str_replace('#', '', $color);
            ?>
            <div class="eq-card">
                <div class="eq-card-top" style="background: linear-gradient(135deg, <?php echo $color; ?>18, <?php echo $color; ?>08);">
                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="<?php echo $color; ?>" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <?php echo $iconPath; ?>
                    </svg>
                    <div class="eq-card-badge">
                        <span class="badge badge-category"><?php echo htmlspecialchars($cat); ?></span>
                    </div>
                </div>
                <div class="eq-card-body">
                    <h3 class="eq-card-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                    <p class="eq-card-desc"><?php echo htmlspecialchars($item['description']); ?></p>
                    <div class="eq-card-footer">
                        <div class="eq-price">RM<?php echo number_format($item['price_per_day'], 2); ?> <span>/day</span></div>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button class="rent-btn" onclick="openRentModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                                Rent Now
                            </button>
                        <?php else: ?>
                            <a href="login.php" class="rent-btn rent-btn-ghost">Login to Rent</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- My Rentals (logged-in users) -->
    <?php if (isset($_SESSION['user_id'])): ?>
    <section class="rentals-section">
        <div class="rentals-inner">
            <p class="section-label">Account</p>
            <h2 class="section-title" style="font-size:1.8rem;">My Rentals</h2>

            <?php if (empty($user_rentals)): ?>
                <div style="margin-top:32px;padding:48px;background:var(--surface2);border-radius:12px;text-align:center;color:var(--muted);">
                    <p style="margin-bottom:16px;">No rentals yet. Start by browsing our equipment above!</p>
                    <a href="#packages" class="btn btn-accent btn-sm">Browse Packages</a>
                </div>
            <?php else: ?>
                <div class="rentals-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Rental Date</th>
                                <th>Return Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_rentals as $r): ?>
                            <tr>
                                <td class="bold"><?php echo htmlspecialchars($r['equipment_name']); ?></td>
                                <td><?php echo htmlspecialchars($r['rental_date']); ?></td>
                                <td><?php echo htmlspecialchars($r['return_date']); ?></td>
                                <td class="bold">RM<?php echo number_format($r['total_price'], 2); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $r['status']; ?>">
                                        <?php echo ucfirst($r['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- About -->
    <section id="about" class="about-section">
        <div class="about-inner">
            <div>
                <p class="section-label">Who We Are</p>
                <h2 class="section-title">Helping explorers since 2015</h2>
                <p class="section-sub">RentVenture is Malaysia's trusted adventure gear rental service. We make quality equipment accessible so you can focus on the journey.</p>
                <div class="info-list">
                    <div class="info-row">
                        <span class="info-label">Company</span>
                        <span class="info-val">Jom Rentals LLC</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Address</span>
                        <span class="info-val">Jalan Dato Seri Kamaruddin, Institut Kemahiran Mara Lumut, 32040 Seri Manjung, Perak</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-val">info@rentventure.com</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone</span>
                        <span class="info-val">(303) 555-0123</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Hours</span>
                        <span class="info-val">Mon–Fri: 9am–7pm<br>Sat: 10am–5pm &nbsp; Sun: 12pm–5pm</span>
                    </div>
                </div>
            </div>
            <div>
                <div class="about-map-placeholder">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <p style="font-size:0.9rem;text-align:center;max-width:200px;line-height:1.5;">Institut Kemahiran Mara Lumut, Seri Manjung, Perak</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-inner">
            <div class="footer-brand">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#e8ff4a" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polygon points="16.24,7.76 14.12,14.12 7.76,16.24 9.88,9.88 16.24,7.76"/></svg>
                    <span style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.1rem;">RentVenture</span>
                </div>
                <p>Quality adventure gear rentals for every kind of explorer in Malaysia.</p>
            </div>
            <div class="footer-col">
                <h4>Navigate</h4>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#packages">Packages</a></li>
                    <li><a href="#about">About Us</a></li>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Sign Up</a></li>
                    <?php else: ?>
                    <li><a href="cart.php">My Cart</a></li>
                    <li><a href="logout.php">Logout</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Contact</h4>
                <ul>
                    <li><a href="mailto:info@rentventure.com">info@rentventure.com</a></li>
                    <li><a href="tel:3035550123">(303) 555-0123</a></li>
                    <li><a href="#about">Find Us</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">&copy; 2025 RentVenture LLC. All rights reserved.</div>
    </footer>

    <!-- Rent Modal -->
    <div class="modal-overlay" id="rent-modal">
        <div class="modal">
            <h3>Rent <span id="modal-item-name"></span></h3>
            <p>How many days would you like to rent this item? (Max 30 days)</p>
            <input type="number" class="days-input" id="modal-days" min="1" max="30" value="1" placeholder="Number of days">
            <div class="modal-actions">
                <button class="modal-cancel" onclick="closeRentModal()">Cancel</button>
                <button class="modal-confirm" onclick="confirmRent()">Add to Cart</button>
            </div>
        </div>
    </div>

    <script>
        let currentEquipmentId = null;

        function openRentModal(id, name) {
            currentEquipmentId = id;
            document.getElementById('modal-item-name').textContent = name;
            document.getElementById('modal-days').value = 1;
            document.getElementById('rent-modal').classList.add('open');
            document.getElementById('modal-days').focus();
        }

        function closeRentModal() {
            document.getElementById('rent-modal').classList.remove('open');
            currentEquipmentId = null;
        }

        function confirmRent() {
            const days = parseInt(document.getElementById('modal-days').value);
            if (days > 0 && days <= 30) {
                window.location.href = 'cart.php?add=1&equipment_id=' + currentEquipmentId + '&days=' + days;
            } else {
                document.getElementById('modal-days').style.borderColor = 'var(--error)';
                setTimeout(() => document.getElementById('modal-days').style.borderColor = '', 1000);
            }
        }

        document.getElementById('rent-modal').addEventListener('click', function(e) {
            if (e.target === this) closeRentModal();
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeRentModal();
            if (e.key === 'Enter' && document.getElementById('rent-modal').classList.contains('open')) confirmRent();
        });
    </script>
</body>
</html>
