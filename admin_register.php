<?php
session_start();
include 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$success = false;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$stmt->execute();
$admin_count = $stmt->fetchColumn();

if ($admin_count > 0) {
    $message = 'An admin already exists. Please contact them to create additional admin accounts.';
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username         = trim($_POST['username']);
    $email            = trim($_POST['email']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($username) && !empty($email) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->rowCount() == 0) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
                if ($stmt->execute([$username, $email, $hash])) {
                    $success = true;
                    $message = 'Admin account created! You can now log in.';
                } else {
                    $message = 'Registration failed. Please try again.';
                }
            } else {
                $message = 'Username or email already exists.';
            }
        } else {
            $message = 'Passwords do not match.';
        }
    } else {
        $message = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup – RentVenture</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { min-height: 100vh; display: flex; align-items: stretch; }

        .panel-left {
            display: none;
            flex: 1;
            background: linear-gradient(135deg, #0f1e3d 0%, #1a1040 50%, #0a2027 100%);
            padding: 60px;
            position: relative;
            overflow: hidden;
            align-items: center;
            justify-content: center;
        }
        @media(min-width: 900px) { .panel-left { display: flex; } }
        .panel-left::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(232,255,74,0.1) 0%, transparent 70%);
            top: -100px; left: -100px;
        }
        .panel-left::after {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(74,222,128,0.06) 0%, transparent 70%);
            bottom: -80px; right: -80px;
        }
        .panel-content { position: relative; z-index: 1; max-width: 380px; }
        .panel-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 56px; }
        .panel-logo svg { color: var(--accent); }
        .panel-logo span { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.4rem; color: var(--text); }
        .panel-headline { font-family: 'Syne', sans-serif; font-size: clamp(1.8rem, 3vw, 2.6rem); font-weight: 800; line-height: 1.1; margin-bottom: 16px; }
        .panel-headline em { font-style: normal; color: var(--accent); }
        .panel-sub { color: var(--muted2); font-size: 0.95rem; line-height: 1.7; margin-bottom: 44px; }

        /* Warning box on left panel */
        .setup-notice {
            background: rgba(232,255,74,0.06);
            border: 1px solid rgba(232,255,74,0.15);
            border-radius: 12px;
            padding: 18px 20px;
        }
        .setup-notice-title { font-size: 0.82rem; font-weight: 600; color: var(--accent); letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 10px; display: flex; align-items: center; gap: 7px; }
        .setup-notice ul { list-style: none; display: flex; flex-direction: column; gap: 8px; }
        .setup-notice li { display: flex; align-items: flex-start; gap: 8px; font-size: 0.86rem; color: var(--muted2); line-height: 1.5; }
        .notice-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--accent); flex-shrink: 0; margin-top: 6px; }

        .panel-right {
            width: 100%;
            max-width: 500px;
            background: var(--surface);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 48px 40px;
        }

        .mobile-logo { display: flex; align-items: center; gap: 8px; margin-bottom: 32px; }
        @media(min-width: 900px) { .mobile-logo { display: none; } }
        .mobile-logo svg { color: var(--accent); }
        .mobile-logo span { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.1rem; }

        /* One-time badge */
        .one-time-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(251,191,36,0.08);
            border: 1px solid rgba(251,191,36,0.2);
            color: #fbbf24;
            padding: 5px 12px;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .one-time-badge svg { color: #fbbf24; }

        .form-title { font-family: 'Syne', sans-serif; font-size: 1.7rem; font-weight: 800; margin-bottom: 6px; }
        .form-sub { color: var(--muted2); font-size: 0.9rem; margin-bottom: 32px; line-height: 1.5; }

        .alert {
            padding: 13px 16px; border-radius: 10px; font-size: 0.88rem; margin-bottom: 24px;
            display: flex; align-items: flex-start; gap: 10px;
        }
        .alert svg { flex-shrink: 0; margin-top: 1px; }
        .alert-error { background: rgba(255,107,107,0.08); border: 1px solid rgba(255,107,107,0.2); color: var(--error); }
        .alert-success { background: rgba(74,222,128,0.08); border: 1px solid rgba(74,222,128,0.2); color: var(--accent2); }
        .alert-warning { background: rgba(251,191,36,0.08); border: 1px solid rgba(251,191,36,0.2); color: #fbbf24; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        @media(max-width: 480px) { .form-row { grid-template-columns: 1fr; } }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 0.78rem; font-weight: 500; color: var(--muted); margin-bottom: 7px; letter-spacing: 0.05em; text-transform: uppercase; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; padding: 13px 15px;
            background: var(--surface2); border: 1px solid var(--border);
            border-radius: 10px; color: var(--text);
            font-family: 'DM Sans', sans-serif; font-size: 0.95rem;
            outline: none; transition: border-color 0.2s, box-shadow 0.2s;
        }
        input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(232,255,74,0.08); }
        input::placeholder { color: var(--muted); }

        .submit-btn {
            width: 100%; padding: 15px;
            background: var(--accent); color: #0a0f1e;
            border: none; border-radius: 10px;
            font-family: 'Syne', sans-serif; font-size: 0.95rem; font-weight: 700;
            cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            margin-top: 6px;
        }
        .submit-btn:hover { background: #c8df20; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(232,255,74,0.2); }
        .submit-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .form-links { margin-top: 24px; display: flex; flex-direction: column; gap: 10px; align-items: center; }
        .form-links a { color: var(--muted2); text-decoration: none; font-size: 0.88rem; transition: color 0.2s; }
        .form-links a span { color: var(--accent); font-weight: 500; }
        .form-links a:hover { color: var(--text); }
        .divider { width: 100%; height: 1px; background: var(--border); margin: 8px 0; }
    </style>
</head>
<body>
    <!-- Left branding panel -->
    <div class="panel-left">
        <div class="panel-content">
            <div class="panel-logo">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polygon points="16.24,7.76 14.12,14.12 7.76,16.24 9.88,9.88 16.24,7.76"/></svg>
                <span>RentVenture</span>
            </div>
            <h1 class="panel-headline">First-time <em>Admin</em> Setup</h1>
            <p class="panel-sub">This page is only accessible once — when no admin account exists in the system. After setup, this page becomes unavailable.</p>
            <div class="setup-notice">
                <div class="setup-notice-title">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Important
                </div>
                <ul>
                    <li><div class="notice-dot"></div>This admin account will have full system access</li>
                    <li><div class="notice-dot"></div>Use a strong password — this is your master account</li>
                    <li><div class="notice-dot"></div>Additional admins can be added from the Admin Panel later</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Right form panel -->
    <div class="panel-right">
        <div class="mobile-logo">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polygon points="16.24,7.76 14.12,14.12 7.76,16.24 9.88,9.88 16.24,7.76"/></svg>
            <span>RentVenture</span>
        </div>

        <?php if ($admin_count == 0 && !$success): ?>
            <div class="one-time-badge">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                One-time setup
            </div>
        <?php endif; ?>

        <h2 class="form-title">
            <?php echo $success ? 'Setup Complete!' : 'Create Admin Account'; ?>
        </h2>
        <p class="form-sub">
            <?php echo $success
                ? 'Your admin account is ready. You can now log in and manage RentVenture.'
                : 'Set up the first administrator account to manage your rental platform.'; ?>
        </p>

        <?php if ($message): ?>
            <?php
            $alert_class = $success ? 'alert-success' : ($admin_count > 0 ? 'alert-warning' : 'alert-error');
            $icon = $success
                ? '<polyline points="20 6 9 17 4 12"/>'
                : ($admin_count > 0
                    ? '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'
                    : '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>');
            ?>
            <div class="alert <?php echo $alert_class; ?>">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><?php echo $icon; ?></svg>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($admin_count == 0 && !$success): ?>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="admin" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="admin@email.com" required>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Create a strong password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat your password" required>
            </div>
            <button type="submit" class="submit-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Create Admin Account
            </button>
        </form>
        <?php endif; ?>

        <div class="form-links">
            <?php if ($success): ?>
                <a href="login.php">Go to Login <span>→</span></a>
            <?php else: ?>
                <a href="login.php">Already have an account? <span>Sign in</span></a>
            <?php endif; ?>
            <div class="divider"></div>
            <a href="index.php">← Back to Home</a>
        </div>
    </div>
</body>
</html>
