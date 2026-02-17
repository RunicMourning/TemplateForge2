<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Core | Admin Login</title>
    
    <link href="<?php echo htmlspecialchars(get_admin_theme_css_url($settings), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bs-body-bg);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        .login-box {
            background: var(--bs-body-bg);
            padding: 0;
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
            border: 1px solid var(--bs-border-color);
        }
        .login-header {
            padding: 2.5rem 2.5rem 1rem;
            text-align: center;
        }
        .login-body {
            padding: 1rem 2.5rem 2.5rem;
        }
        .login-footer {
            padding: 1.5rem;
            background: color-mix(in srgb, var(--bs-body-bg) 88%, var(--bs-body-color) 12%);
            border-top: 1px solid var(--bs-border-color);
            text-align: center;
        }
        .gradient-bar {
            height: 5px;
            background: linear-gradient(90deg, #6610f2, #0d6efd);
        }
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid var(--bs-border-color);
            background-color: var(--bs-body-bg);
        }
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
            border-color: #0d6efd;
        }
        .btn-primary {
            padding: 0.75rem;
            border-radius: 0.75rem;
            font-weight: 600;
            background: linear-gradient(135deg, #0d6efd 0%, #004dc7 100%);
            border: none;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(13, 110, 253, 0.3);
        }
        .tracking-widest { letter-spacing: 0.15em; }
    </style>
</head>
<body>

<div class="login-box">
    <div class="gradient-bar"></div>
    
    <div class="login-header">
        <div class="bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px;">
            <i class="bi bi-cpu-fill fs-3"></i>
        </div>
        <h4 class="fw-bold text-dark mb-1">Admin Console</h4>
        <p class="small text-muted text-uppercase tracking-widest mb-0">System Authentication</p>
    </div>
    
    <div class="login-body">
        <?php if(isset($error)): ?>
            <div class="alert alert-danger border-0 small py-2 text-center mb-4 rounded-3">
                <i class="bi bi-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <?php echo csrf_input('admin_login'); ?>
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted px-3"><i class="bi bi-person"></i></span>
                    <input type="text" name="user" class="form-control border-start-0 ps-0" placeholder="Enter username" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold text-muted">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted px-3"><i class="bi bi-shield-lock"></i></span>
                    <input type="password" name="pass" class="form-control border-start-0 ps-0" placeholder="Enter password" required>
                </div>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100 mb-2">
                Log In to Core <i class="bi bi-arrow-right-short ms-1"></i>
            </button>
        </form>
    </div>

    <div class="login-footer">
        <a href="../" class="text-decoration-none small text-muted hover-primary">
            <i class="bi bi-house-door me-1"></i> Return to Homepage
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>