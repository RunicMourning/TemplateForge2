<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login &mdash; <?php echo htmlspecialchars($settings['site_name'] ?? 'TemplatForge'); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/admin/admin.css">
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
            max-width: 400px;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            background: rgba(79,126,248,0.12);
            border: 1px solid rgba(79,126,248,0.25);
            border-radius: 14px;
            font-size: 1.5rem;
            color: var(--a-accent);
            margin-bottom: 0.75rem;
        }

        .login-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: rgba(255,255,255,0.9);
            margin-bottom: 0.25rem;
        }

        .login-sub {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.35);
        }

        .login-card {
            background: #1a1d27;
            border: 1px solid #2a2d3e;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 24px 64px rgba(0,0,0,0.4);
        }

        .login-card-accent {
            height: 3px;
            background: linear-gradient(90deg, var(--a-accent), #a78bfa);
        }

        .login-card-body { padding: 2rem; }

        .login-card label {
            color: rgba(255,255,255,0.45);
        }

        .login-card input {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.9);
        }

        .login-card input:focus {
            border-color: var(--a-accent);
            background: rgba(255,255,255,0.07);
        }

        .login-card input::placeholder { color: rgba(255,255,255,0.2); }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.78rem;
            color: rgba(255,255,255,0.2);
        }
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
                    <input type="text" name="user" placeholder="Enter username" required autocomplete="username">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Password</label>
                    <input type="password" name="pass" placeholder="Enter password" required autocomplete="current-password">
                </div>
                <button type="submit" name="login" class="btn btn-primary w-full">
                    Sign In <i class="bi bi-arrow-right-short"></i>
                </button>
            </form>
        </div>
    </div>

    <div class="login-footer">
        <a href="../index.php" style="color: rgba(255,255,255,0.25);">
            <i class="bi bi-arrow-left" style="margin-right:0.3rem;"></i>Back to site
        </a>
    </div>
</div>

</body>
</html>
