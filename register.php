<?php
session_start();
include 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($username) && !empty($email) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->rowCount() == 0) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                if ($stmt->execute([$username, $email, $hashed_password])) {
                    $success = true;
                    $message = 'Account created! You can now log in.';
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
    <title>Sign Up – RentVenture</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #0a0f1e;
            --surface: #111827;
            --surface2: #1c2536;
            --accent: #e8ff4a;
            --text: #f0f4ff;
            --muted: #6b7a99;
            --border: rgba(255,255,255,0.07);
            --error: #ff6b6b;
            --success: #4ade80;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: stretch;
        }

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
        .panel-content { position: relative; z-index: 1; max-width: 400px; }
        .panel-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 60px; }
        .panel-logo svg { color: var(--accent); }
        .panel-logo span { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.5rem; }
        .panel-headline { font-family: 'Syne', sans-serif; font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; line-height: 1.1; margin-bottom: 20px; }
        .panel-headline em { font-style: normal; color: var(--accent); }
        .panel-sub { color: var(--muted); font-size: 1rem; line-height: 1.6; margin-bottom: 50px; }
        .panel-features { display: flex; flex-direction: column; gap: 16px; }
        .feature-item { display: flex; align-items: center; gap: 12px; color: #a0b0cc; font-size: 0.9rem; }
        .feature-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); flex-shrink: 0; }

        .panel-right {
            width: 100%;
            max-width: 520px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 40px;
            background: var(--surface);
        }
        @media(min-width: 900px) { .panel-right { padding: 60px; } }

        .form-header { margin-bottom: 36px; }
        .mobile-logo { display: flex; align-items: center; gap: 8px; margin-bottom: 28px; }
        @media(min-width: 900px) { .mobile-logo { display: none; } }
        .mobile-logo span { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.2rem; }
        .mobile-logo svg { color: var(--accent); }
        .form-title { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 700; margin-bottom: 8px; }
        .form-subtitle { color: var(--muted); font-size: 0.9rem; }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.88rem;
            margin-bottom: 24px;
        }
        .alert-error { background: rgba(255,107,107,0.1); border: 1px solid rgba(255,107,107,0.3); color: var(--error); }
        .alert-success { background: rgba(74,222,128,0.1); border: 1px solid rgba(74,222,128,0.3); color: var(--success); }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media(max-width: 500px) { .form-row { grid-template-columns: 1fr; } }

        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 0.82rem; font-weight: 500; color: var(--muted); margin-bottom: 8px; letter-spacing: 0.04em; text-transform: uppercase; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; padding: 13px 16px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(232,255,74,0.1); }
        input::placeholder { color: var(--muted); }

        .btn-primary {
            width: 100%; padding: 15px;
            background: var(--accent); color: #0a0f1e;
            border: none; border-radius: 10px;
            font-family: 'Syne', sans-serif; font-size: 0.95rem; font-weight: 700;
            cursor: pointer; transition: transform 0.15s, box-shadow 0.15s;
            margin-top: 8px; letter-spacing: 0.02em;
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(232,255,74,0.3); }

        .form-links { margin-top: 24px; display: flex; flex-direction: column; gap: 10px; align-items: center; }
        .form-links a { color: var(--muted); text-decoration: none; font-size: 0.88rem; transition: color 0.2s; }
        .form-links a span { color: var(--accent); font-weight: 500; }
        .form-links a:hover { color: var(--text); }
        .divider { width: 100%; height: 1px; background: var(--border); margin: 16px 0; }

        .password-hint { font-size: 0.78rem; color: var(--muted); margin-top: 6px; }
    </style>
</head>
<body>
    <div class="panel-left">
        <div class="panel-content">
            <div class="panel-logo">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polygon points="16.24,7.76 14.12,14.12 7.76,16.24 9.88,9.88 16.24,7.76"/></svg>
                <span>RentVenture</span>
            </div>
            <h1 class="panel-headline">Join thousands of <em>explorers</em> today</h1>
            <p class="panel-sub">Create your free account and start renting premium gear for your next adventure in minutes.</p>
            <div class="panel-features">
                <div class="feature-item"><div class="feature-dot"></div>Free account, no hidden fees</div>
                <div class="feature-item"><div class="feature-dot"></div>Rent & return with ease</div>
                <div class="feature-item"><div class="feature-dot"></div>Secure payment processing</div>
                <div class="feature-item"><div class="feature-dot"></div>Track all your rentals</div>
            </div>
        </div>
    </div>

    <div class="panel-right">
        <div class="form-header">
            <div class="mobile-logo">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polygon points="16.24,7.76 14.12,14.12 7.76,16.24 9.88,9.88 16.24,7.76"/></svg>
                <span>RentVenture</span>
            </div>
            <h2 class="form-title">Create account</h2>
            <p class="form-subtitle">Fill in your details to get started</p>
        </div>

        <?php if ($message): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-error'; ?>">
                <?php echo htmlspecialchars($message); ?>
                <?php if ($success): ?> <a href="login.php" style="color:inherit;font-weight:600;margin-left:4px;">Go to login →</a><?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="your_username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="you@email.com" required>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Create a strong password" required>
                <p class="password-hint">At least 8 characters recommended</p>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat your password" required>
            </div>
            <button type="submit" class="btn-primary">Create Account</button>
        </form>
        <?php endif; ?>

        <div class="form-links">
            <a href="login.php">Already have an account? <span>Sign in</span></a>
            <div class="divider"></div>
            <a href="index.php">← Back to Home</a>
        </div>
    </div>
</body>
</html>
