<?php
session_start();
include 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: index.php');
            exit;
        } else {
            $message = 'Invalid username or password.';
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
    <title>Login – RentVenture</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #0a0f1e;
            --surface: #111827;
            --surface2: #1c2536;
            --accent: #e8ff4a;
            --accent2: #4ade80;
            --text: #f0f4ff;
            --muted: #6b7a99;
            --border: rgba(255,255,255,0.07);
            --error: #ff6b6b;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: stretch;
        }

        /* Left panel */
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
            background: radial-gradient(circle, rgba(232,255,74,0.12) 0%, transparent 70%);
            top: -100px; left: -100px;
        }
        .panel-left::after {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(74,222,128,0.08) 0%, transparent 70%);
            bottom: -80px; right: -80px;
        }

        .panel-content {
            position: relative;
            z-index: 1;
            max-width: 400px;
        }

        .panel-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 60px;
        }
        .panel-logo svg { color: var(--accent); }
        .panel-logo span {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--text);
        }

        .panel-headline {
            font-family: 'Syne', sans-serif;
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 20px;
        }
        .panel-headline em {
            font-style: normal;
            color: var(--accent);
        }

        .panel-sub {
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 50px;
        }

        .panel-features { display: flex; flex-direction: column; gap: 16px; }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #a0b0cc;
            font-size: 0.9rem;
        }
        .feature-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--accent);
            flex-shrink: 0;
        }

        /* Right panel */
        .panel-right {
            width: 100%;
            max-width: 500px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 40px 40px;
            background: var(--surface);
        }
        @media(min-width: 900px) { .panel-right { padding: 60px 60px; } }

        .form-header { margin-bottom: 40px; }
        .form-header .mobile-logo {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 32px;
        }
        @media(min-width: 900px) { .form-header .mobile-logo { display: none; } }
        .mobile-logo span {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.2rem;
        }
        .mobile-logo svg { color: var(--accent); }

        .form-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .form-subtitle { color: var(--muted); font-size: 0.9rem; }

        .error-msg {
            background: rgba(255,107,107,0.1);
            border: 1px solid rgba(255,107,107,0.3);
            color: var(--error);
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.88rem;
            margin-bottom: 24px;
        }

        .form-group { margin-bottom: 20px; }
        label {
            display: block;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--muted);
            margin-bottom: 8px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(232,255,74,0.1);
        }
        input::placeholder { color: var(--muted); }

        .btn-primary {
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
            transition: transform 0.15s, box-shadow 0.15s;
            margin-top: 8px;
            letter-spacing: 0.02em;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(232,255,74,0.3);
        }
        .btn-primary:active { transform: translateY(0); }

        .form-links {
            margin-top: 28px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
        }
        .form-links a {
            color: var(--muted);
            text-decoration: none;
            font-size: 0.88rem;
            transition: color 0.2s;
        }
        .form-links a span { color: var(--accent); font-weight: 500; }
        .form-links a:hover { color: var(--text); }

        .divider {
            width: 100%; height: 1px;
            background: var(--border);
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="panel-left">
        <div class="panel-content">
            <div class="panel-logo">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polygon points="16.24,7.76 14.12,14.12 7.76,16.24 9.88,9.88 16.24,7.76"/></svg>
                <span>RentVenture</span>
            </div>
            <h1 class="panel-headline">Gear up for your next <em>adventure</em></h1>
            <p class="panel-sub">Premium equipment rentals for every kind of explorer. Winter, water, wilderness — we've got you covered.</p>
            <div class="panel-features">
                <div class="feature-item"><div class="feature-dot"></div>Top-quality adventure equipment</div>
                <div class="feature-item"><div class="feature-dot"></div>Flexible rental periods</div>
                <div class="feature-item"><div class="feature-dot"></div>Affordable daily rates in RM</div>
                <div class="feature-item"><div class="feature-dot"></div>Fast & secure checkout</div>
            </div>
        </div>
    </div>

    <div class="panel-right">
        <div class="form-header">
            <div class="mobile-logo">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polygon points="16.24,7.76 14.12,14.12 7.76,16.24 9.88,9.88 16.24,7.76"/></svg>
                <span>RentVenture</span>
            </div>
            <h2 class="form-title">Welcome back</h2>
            <p class="form-subtitle">Sign in to your account to continue</p>
        </div>

        <?php if ($message): ?>
            <div class="error-msg"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn-primary">Sign In</button>
        </form>

        <div class="form-links">
            <a href="register.php">Don't have an account? <span>Sign up free</span></a>
            <div class="divider"></div>
            <a href="index.php">← Back to Home</a>
        </div>
    </div>
</body>
</html>
