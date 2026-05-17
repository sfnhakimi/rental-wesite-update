<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php'); exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve_rental'])) {
        $rental_id = intval($_POST['rental_id']);
        $stmt = $pdo->prepare("UPDATE rentals SET status = 'approved' WHERE id = ?");
        $message = $stmt->execute([$rental_id]) ? 'Rental approved successfully!' : 'Failed to approve rental.';
    }

    if (isset($_POST['add_admin'])) {
        $uname = trim($_POST['admin_username']);
        $uemail = trim($_POST['admin_email']);
        $upass = $_POST['admin_password'];
        $uconf = $_POST['admin_confirm_password'];
        if (!empty($uname) && !empty($uemail) && !empty($upass)) {
            if ($upass === $uconf) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$uname, $uemail]);
                if ($stmt->rowCount() == 0) {
                    $hash = password_hash($upass, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
                    $message = $stmt->execute([$uname, $uemail, $hash]) ? 'Admin created successfully!' : 'Failed to create admin.';
                } else { $message = 'Username or email already exists.'; }
            } else { $message = 'Passwords do not match.'; }
        } else { $message = 'Please fill all fields.'; }
    }

    if (isset($_POST['add_equipment'])) {
        $name = trim($_POST['name']);
        $desc = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $cat = trim($_POST['category']);
        if (!empty($name) && !empty($desc) && $price > 0 && !empty($cat)) {
            $stmt = $pdo->prepare("INSERT INTO equipment (name, description, price_per_day, category) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $desc, $price, $cat]);
            $message = 'Equipment added successfully!';
        } else { $message = 'Please fill all fields correctly.'; }
    }

    if (isset($_POST['delete_equipment'])) {
        $eid = intval($_POST['equipment_id']);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE equipment_id = ? AND status IN ('pending','approved')");
        $stmt->execute([$eid]);
        if ($stmt->fetchColumn() > 0) {
            $message = 'Cannot delete equipment with active rentals.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM equipment WHERE id = ?");
            $message = $stmt->execute([$eid]) ? 'Equipment deleted successfully!' : 'Failed to delete.';
        }
    }
}

$stmt = $pdo->query("SELECT * FROM equipment ORDER BY id DESC");
$equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT r.*, u.username, e.name as equipment_name FROM rentals r JOIN users u ON r.user_id = u.id JOIN equipment e ON r.equipment_id = e.id ORDER BY r.created_at DESC");
$rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

$revenue = array_sum(array_column($rentals, 'total_price'));
$pending_count = count(array_filter($rentals, fn($r) => $r['status'] == 'pending'));
$is_success = strpos($message, 'successfully') !== false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – RentVenture</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #0a0f1e;
            --surface: #111827;
            --surface2: #1c2536;
            --sidebar-bg: #0d1424;
            --accent: #e8ff4a;
            --accent2: #4ade80;
            --indigo: #818cf8;
            --text: #f0f4ff;
            --muted: #6b7a99;
            --muted2: #a0b0cc;
            --border: rgba(255,255,255,0.07);
            --border2: rgba(255,255,255,0.12);
            --error: #ff6b6b;
        }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }

        /* ── Sidebar ── */
        .sidebar {
            width: 240px; flex-shrink: 0;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; bottom: 0;
            z-index: 50;
            transition: transform 0.3s ease;
        }
        .sidebar-logo {
            padding: 24px 20px;
            display: flex; align-items: center; gap: 10px;
            border-bottom: 1px solid var(--border);
        }
        .logo-icon { width: 32px; height: 32px; border-radius: 8px; background: rgba(232,255,74,0.12); display: flex; align-items: center; justify-content: center; }
        .logo-icon svg { color: var(--accent); }
        .logo-text { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.1rem; }
        .logo-sub { font-size: 0.7rem; color: var(--muted); }

        .sidebar-nav { padding: 16px 12px; flex: 1; display: flex; flex-direction: column; gap: 4px; }
        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 14px; border-radius: 10px;
            color: var(--muted2); font-size: 0.88rem;
            text-decoration: none; cursor: pointer;
            transition: all 0.2s; border: none; background: none; width: 100%; text-align: left;
        }
        .nav-item:hover { background: var(--surface2); color: var(--text); }
        .nav-item.active { background: rgba(232,255,74,0.08); color: var(--accent); }
        .nav-item svg { flex-shrink: 0; }
        .nav-section { font-size: 0.68rem; color: var(--muted); letter-spacing: 0.08em; text-transform: uppercase; padding: 12px 14px 6px; }
        .nav-badge { margin-left: auto; background: var(--accent); color: #0a0f1e; font-size: 0.65rem; font-weight: 700; padding: 1px 6px; border-radius: 100px; }

        .sidebar-footer { padding: 16px 12px; border-top: 1px solid var(--border); display: flex; flex-direction: column; gap: 4px; }

        /* ── Main ── */
        .main { margin-left: 240px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 40;
        }
        .topbar-title { font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 700; }
        .topbar-sub { font-size: 0.8rem; color: var(--muted); margin-top: 2px; }
        .topbar-user { display: flex; align-items: center; gap: 12px; }
        .user-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: rgba(232,255,74,0.1);
            border: 1px solid rgba(232,255,74,0.2);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif; font-weight: 700; font-size: 0.8rem; color: var(--accent);
        }
        .user-name { font-size: 0.88rem; font-weight: 500; }
        .user-role { font-size: 0.75rem; color: var(--muted); }
        .mobile-menu-btn { display: none; padding: 8px; border: 1px solid var(--border2); border-radius: 8px; background: none; color: var(--text); cursor: pointer; }
        @media(max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .mobile-menu-btn { display: flex; align-items: center; justify-content: center; }
        }

        .content { padding: 32px; }

        /* ── Alert ── */
        .alert {
            display: flex; align-items: center; gap: 10px;
            padding: 14px 18px; border-radius: 10px; font-size: 0.9rem; margin-bottom: 28px;
        }
        .alert-success { background: rgba(74,222,128,0.08); border: 1px solid rgba(74,222,128,0.2); color: var(--accent2); }
        .alert-error { background: rgba(255,107,107,0.08); border: 1px solid rgba(255,107,107,0.2); color: var(--error); }
        .alert svg { flex-shrink: 0; }

        /* ── Stat cards ── */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 32px; }
        .stat-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 14px; padding: 20px 22px;
            display: flex; align-items: center; gap: 16px;
            transition: border-color 0.2s;
        }
        .stat-card:hover { border-color: var(--border2); }
        .stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .stat-body {}
        .stat-label { font-size: 0.75rem; color: var(--muted); margin-bottom: 4px; }
        .stat-val { font-family: 'Syne', sans-serif; font-size: 1.5rem; font-weight: 800; }

        /* ── Section panels ── */
        .panel {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 14px; overflow: hidden; margin-bottom: 24px;
        }
        .panel-header {
            padding: 18px 24px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .panel-title { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; }
        .panel-body { padding: 24px; }

        /* ── Add forms (collapsible) ── */
        .add-form-wrap { border-bottom: 1px solid var(--border); padding: 20px 24px; display: none; }
        .add-form-wrap.open { display: block; }
        .add-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; align-items: end; }
        .add-form input, .add-form select {
            width: 100%; padding: 11px 14px;
            background: var(--surface2); border: 1px solid var(--border2);
            border-radius: 9px; color: var(--text);
            font-family: 'DM Sans', sans-serif; font-size: 0.9rem;
            outline: none; transition: border-color 0.2s;
        }
        .add-form input:focus, .add-form select:focus { border-color: var(--accent); }
        .add-form input::placeholder { color: var(--muted); }
        .add-form select option { background: var(--surface2); }
        .add-form-label { font-size: 0.75rem; color: var(--muted); margin-bottom: 5px; display: block; }

        /* ── Tables ── */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th {
            padding: 12px 16px; text-align: left;
            font-size: 0.72rem; font-weight: 600; letter-spacing: 0.07em;
            text-transform: uppercase; color: var(--muted);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        td {
            padding: 14px 16px; font-size: 0.86rem; color: var(--muted2);
            border-bottom: 1px solid var(--border);
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,0.015); }
        td.bold { color: var(--text); font-weight: 500; }
        .truncate-cell { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        /* ── Badges ── */
        .badge { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 100px; font-size: 0.72rem; font-weight: 600; white-space: nowrap; }
        .badge-pending { background: rgba(251,191,36,0.12); color: #fbbf24; }
        .badge-approved { background: rgba(74,222,128,0.12); color: var(--accent2); }
        .badge-returned { background: rgba(129,140,248,0.12); color: var(--indigo); }
        .badge-available { background: rgba(74,222,128,0.12); color: var(--accent2); }
        .badge-unavailable { background: rgba(255,107,107,0.12); color: var(--error); }
        .badge-cat { background: rgba(232,255,74,0.08); color: var(--accent); border: 1px solid rgba(232,255,74,0.15); }

        /* ── Buttons ── */
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 9px; font-size: 0.85rem; font-weight: 500; cursor: pointer; border: none; text-decoration: none; transition: all 0.18s; font-family: 'DM Sans', sans-serif; }
        .btn-accent { background: var(--accent); color: #0a0f1e; }
        .btn-accent:hover { background: #c8df20; }
        .btn-ghost { background: var(--surface2); color: var(--muted2); border: 1px solid var(--border2); }
        .btn-ghost:hover { color: var(--text); }
        .btn-approve { background: rgba(74,222,128,0.1); color: var(--accent2); border: 1px solid rgba(74,222,128,0.2); padding: 5px 12px; font-size: 0.78rem; }
        .btn-approve:hover { background: rgba(74,222,128,0.2); }
        .btn-delete { background: rgba(255,107,107,0.08); color: var(--error); border: 1px solid rgba(255,107,107,0.15); padding: 5px 8px; }
        .btn-delete:hover { background: rgba(255,107,107,0.15); }
        .btn-sm { padding: 7px 14px; font-size: 0.82rem; }

        /* overlay for mobile sidebar */
        .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 49; }
        .overlay.show { display: block; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polygon points="16.24,7.76 14.12,14.12 7.76,16.24 9.88,9.88 16.24,7.76"/></svg>
            </div>
            <div>
                <div class="logo-text">RentVenture</div>
                <div class="logo-sub">Admin Panel</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">Overview</div>
            <button class="nav-item active" onclick="scrollTo('dashboard')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </button>

            <div class="nav-section">Manage</div>
            <button class="nav-item" onclick="scrollTo('rentals')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Rentals
                <?php if ($pending_count > 0): ?>
                    <span class="nav-badge"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </button>
            <button class="nav-item" onclick="scrollTo('equipment')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                Equipment
            </button>
            <button class="nav-item" onclick="scrollTo('admins')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Admins
            </button>
        </nav>

        <div class="sidebar-footer">
            <a href="index.php" class="nav-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Back to Site
            </a>
            <a href="logout.php" class="nav-item" style="color:var(--error)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
    </aside>

    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>

    <!-- Main -->
    <div class="main">
        <div class="topbar">
            <div>
                <div class="topbar-title" id="section-title">Dashboard</div>
                <div class="topbar-sub">Manage your rental business</div>
            </div>
            <div class="topbar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?></div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                    <div class="user-role">Administrator</div>
                </div>
                <button class="mobile-menu-btn" onclick="toggleSidebar()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
            </div>
        </div>

        <div class="content">
            <?php if ($message): ?>
            <div class="alert <?php echo $is_success ? 'alert-success' : 'alert-error'; ?>">
                <?php if ($is_success): ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <?php else: ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?php endif; ?>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid" id="dashboard">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(129,140,248,.12)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#818cf8" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                    </div>
                    <div class="stat-body">
                        <div class="stat-label">Total Equipment</div>
                        <div class="stat-val"><?php echo count($equipment); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(74,222,128,.1)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <div class="stat-body">
                        <div class="stat-label">Total Rentals</div>
                        <div class="stat-val"><?php echo count($rentals); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(251,191,36,.1)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div class="stat-body">
                        <div class="stat-label">Pending</div>
                        <div class="stat-val"><?php echo $pending_count; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(232,255,74,.08)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e8ff4a" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div class="stat-body">
                        <div class="stat-label">Revenue</div>
                        <div class="stat-val" style="font-size:1.2rem">RM<?php echo number_format($revenue, 0); ?></div>
                    </div>
                </div>
            </div>

            <!-- Rentals -->
            <div class="panel" id="rentals">
                <div class="panel-header">
                    <div class="panel-title">Rental Management</div>
                    <?php if ($pending_count > 0): ?>
                        <span class="badge badge-pending"><?php echo $pending_count; ?> pending</span>
                    <?php endif; ?>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Equipment</th>
                                <th>Pickup</th>
                                <th>Return</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rentals)): ?>
                            <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--muted)">No rentals yet</td></tr>
                            <?php else: ?>
                            <?php foreach ($rentals as $r): ?>
                            <tr>
                                <td class="bold"><?php echo $r['id']; ?></td>
                                <td class="bold"><?php echo htmlspecialchars($r['username']); ?></td>
                                <td><?php echo htmlspecialchars($r['equipment_name']); ?></td>
                                <td><?php echo date('d M Y', strtotime($r['rental_date'])); ?></td>
                                <td><?php echo date('d M Y', strtotime($r['return_date'])); ?></td>
                                <td class="bold">RM<?php echo number_format($r['total_price'], 2); ?></td>
                                <td><span class="badge badge-<?php echo $r['status']; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                                <td>
                                    <?php if ($r['status'] == 'pending'): ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="rental_id" value="<?php echo $r['id']; ?>">
                                        <button type="submit" name="approve_rental" class="btn btn-approve">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                            Approve
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <span style="color:var(--muted);font-size:0.8rem">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Equipment -->
            <div class="panel" id="equipment">
                <div class="panel-header">
                    <div class="panel-title">Equipment Management</div>
                    <button class="btn btn-accent btn-sm" onclick="toggleForm('eq-form')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add Equipment
                    </button>
                </div>

                <div class="add-form-wrap" id="eq-form">
                    <form method="POST">
                        <div class="add-form">
                            <div>
                                <label class="add-form-label">Equipment Name</label>
                                <input type="text" name="name" placeholder="e.g. Ice Axe" required>
                            </div>
                            <div>
                                <label class="add-form-label">Description</label>
                                <input type="text" name="description" placeholder="Short description" required>
                            </div>
                            <div>
                                <label class="add-form-label">Price per Day (RM)</label>
                                <input type="number" name="price" step="0.01" min="0.01" placeholder="0.00" required>
                            </div>
                            <div>
                                <label class="add-form-label">Category</label>
                                <select name="category" required>
                                    <option value="">Select category</option>
                                    <option value="Winter">Winter</option>
                                    <option value="Water Sports">Water Sports</option>
                                    <option value="Camping">Camping</option>
                                </select>
                            </div>
                            <div style="display:flex;gap:8px;align-items:flex-end">
                                <button type="submit" name="add_equipment" class="btn btn-accent" style="width:100%">Save</button>
                                <button type="button" class="btn btn-ghost" onclick="toggleForm('eq-form')">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Price/Day</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($equipment)): ?>
                            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted)">No equipment added yet</td></tr>
                            <?php else: ?>
                            <?php foreach ($equipment as $item): ?>
                            <tr>
                                <td class="bold"><?php echo $item['id']; ?></td>
                                <td class="bold"><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="truncate-cell"><?php echo htmlspecialchars($item['description']); ?></td>
                                <td>RM<?php echo number_format($item['price_per_day'], 2); ?></td>
                                <td><span class="badge badge-cat"><?php echo htmlspecialchars($item['category']); ?></span></td>
                                <td><span class="badge badge-<?php echo $item['available'] ? 'available' : 'unavailable'; ?>"><?php echo $item['available'] ? 'Available' : 'Unavailable'; ?></span></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Delete this equipment?')">
                                        <input type="hidden" name="equipment_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="delete_equipment" class="btn btn-delete">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Admin -->
            <div class="panel" id="admins">
                <div class="panel-header">
                    <div class="panel-title">Admin Management</div>
                    <button class="btn btn-ghost btn-sm" onclick="toggleForm('admin-form')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add Admin
                    </button>
                </div>

                <div class="add-form-wrap" id="admin-form">
                    <form method="POST">
                        <div class="add-form">
                            <div>
                                <label class="add-form-label">Username</label>
                                <input type="text" name="admin_username" placeholder="admin_name" required>
                            </div>
                            <div>
                                <label class="add-form-label">Email</label>
                                <input type="email" name="admin_email" placeholder="admin@email.com" required>
                            </div>
                            <div>
                                <label class="add-form-label">Password</label>
                                <input type="password" name="admin_password" placeholder="••••••••" required>
                            </div>
                            <div>
                                <label class="add-form-label">Confirm Password</label>
                                <input type="password" name="admin_confirm_password" placeholder="••••••••" required>
                            </div>
                            <div style="display:flex;gap:8px;align-items:flex-end">
                                <button type="submit" name="add_admin" class="btn btn-accent" style="width:100%">Create Admin</button>
                                <button type="button" class="btn btn-ghost" onclick="toggleForm('admin-form')">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="panel-body" style="color:var(--muted2);font-size:0.9rem;line-height:1.6">
                    Use the form above to create additional admin accounts. Admins have full access to manage rentals, equipment, and users.
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleForm(id) {
            document.getElementById(id).classList.toggle('open');
        }
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('overlay').classList.toggle('show');
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('overlay').classList.remove('show');
        }
        function scrollTo(id) {
            document.getElementById(id).scrollIntoView({ behavior: 'smooth', block: 'start' });
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            event.currentTarget.classList.add('active');
            document.getElementById('section-title').textContent = event.currentTarget.textContent.trim().replace(/\d+$/,'').trim();
            if (window.innerWidth <= 768) closeSidebar();
        }
    </script>
</body>
</html>
