<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Apparix - Step <?php echo $step; ?></title>
    <link rel="icon" href="/favicon.ico">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%);
            min-height: 100vh;
            color: #1f2937;
        }
        .installer {
            max-width: 700px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .installer-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .installer-logo {
            margin-bottom: 8px;
        }
        .installer-logo img {
            max-width: 200px;
            height: auto;
        }
        .installer-subtitle {
            color: #6b7280;
        }
        .steps-indicator {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 32px;
        }
        .step-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #e5e7eb;
            transition: all 0.2s;
        }
        .step-dot.active { background: #FF68C5; transform: scale(1.2); }
        .step-dot.completed { background: #10b981; }
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 32px;
            margin-bottom: 24px;
        }
        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .card-description {
            color: #6b7280;
            margin-bottom: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: #FF68C5;
            box-shadow: 0 0 0 3px rgba(255,104,197,0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-help {
            font-size: 0.8rem;
            color: #9ca3af;
            margin-top: 4px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #FF68C5;
            color: white;
        }
        .btn-primary:hover { background: #ff4db8; }
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        .btn-secondary:hover { background: #e5e7eb; }
        .btn-lg { padding: 16px 32px; font-size: 1.1rem; }
        .btn-block { width: 100%; }
        .form-actions {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-top: 32px;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .requirements-list {
            list-style: none;
        }
        .requirements-list li {
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .req-name { font-weight: 500; }
        .req-status { font-size: 0.9rem; }
        .req-passed { color: #10b981; }
        .req-failed { color: #ef4444; }
        .theme-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        .theme-card {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .theme-card:hover { border-color: #FF68C5; }
        .theme-card.selected { border-color: #FF68C5; background: #fdf2f8; }
        .theme-preview {
            height: 80px;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        .theme-name { font-weight: 600; margin-bottom: 4px; }
        .theme-desc { font-size: 0.8rem; color: #6b7280; }
        .skip-link {
            color: #9ca3af;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .skip-link:hover { color: #6b7280; }
        @media (max-width: 640px) {
            .form-row { grid-template-columns: 1fr; }
            .theme-grid { grid-template-columns: 1fr; }
            .form-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="installer">
        <div class="installer-header">
            <div class="installer-logo">
                <img src="/assets/images/apparix-logo.png" alt="Apparix">
            </div>
            <div class="installer-subtitle">E-Commerce Platform Installation</div>
        </div>

        <div class="steps-indicator">
            <?php for ($i = 1; $i <= 7; $i++): ?>
                <div class="step-dot <?php echo $i < $step ? 'completed' : ($i === $step ? 'active' : ''); ?>"></div>
            <?php endfor; ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php include $viewFile; ?>
    </div>
</body>
</html>
