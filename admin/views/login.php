<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login &mdash; <?php echo htmlspecialchars($settings['site_name'] ?? 'TemplatForge'); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/admin/admin.css">
    <link rel="stylesheet" href="/admin/admin-ui.css">
    <link rel="stylesheet" href="/admin/admin-components.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--a-bg);
            padding: 1.5rem;
        }

        .login-wrap {
            width: 100%;
            max-width: 420px;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            background: rgba(79,126,248,0.12);
            border: 1px solid rgba(79,126,248,0.25);
            border-radius: 14px;
            font-size: 1.6rem;
            color: var(--a-accent);
            margin-bottom: 0.85rem;
        }

        .login-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: rgba(255,255,255,0.9);
            margin-bottom: 0.2rem;
        }

        .login-sub {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.35);
        }

        .login-card {
            background: #1a1d27;
            border: 1px solid #2a2d3e;
            border-radius: var(--a-radius-lg);
            overflow: hidden;
            box-shadow: 0 24px 64px rgba(0,0,0,0.4);
        }

        .login-card-accent {
            height: 3px;
            background: linear-gradient(90deg, var(--a-accent), #a78bfa);
        }

        .login-card-body { padding: 2rem; }

        /* Input overrides for dark login surface */
        .login-card label,
        .login-card .form-label {
            color: rgba(255,255,255,0.5);
        }

        .login-card input[type="text"],
        .login-card input[type="password"] {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.9);
        }

        .login-card input[type="text"]:focus,
        .login-card input[type="password"]:focus {
            border-color: var(--a-accent);
            background: rgba(255,255,255,0.07);
            box-shadow: 0 0 0 3px rgba(79,126,248,0.15);
        }

        .login-card input::placeholder {
            color: rgba(255,255,255,0.2);
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.78rem;
        }

        .login-footer a {
            color: rgba(255,255,255,0.25);
            text-decoration: none;
            transition: color 0.15s;
        }

        .login-footer a:hover { color: rgba(255,255,255,0.55); }
    </style>
</head>
<body>

<div class="login-wrap">
    <div class="login-logo">
        <div class="login-logo-mark"><i class="bi bi-intersect"></i></div>
        <div class="login-title"><?php echo htmlspecialchars($settings['site_name'] ?? 'TemplatForge'); ?></div>
        <div class="login-sub">Admin Portal</div>
    </div>

    <div class="login-card">
        <div class="login-card-accent"></div>
        <div class="login-card-body">

            <?php if (!empty($error)): ?>
            <div class="alert alert-danger mb-3">
                <i class="bi bi-shield-exclamation"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="index.php">
                <?php echo csrf_input('admin_login'); ?>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="user" placeholder="Enter username"
                           required autocomplete="username">
                </div>
                <div class="form-group" style="margin-bottom:1.5rem;">
                    <label>Password</label>
                    <input type="password" name="pass" placeholder="Enter password"
                           required autocomplete="current-password">
                </div>
                <button type="submit" name="login" class="btn btn-primary w-full">
                    Sign In <i class="bi bi-arrow-right-short"></i>
                </button>
            </form>

        </div>
    </div>

    <div class="login-footer">
        <a href="../index.php">
            <i class="bi bi-arrow-left" style="margin-right:0.3rem;"></i>Back to site
        </a>
    </div>
</div>

</body>
</html>
