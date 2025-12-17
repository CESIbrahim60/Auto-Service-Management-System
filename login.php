<?php
/**
 * 3WAY Car Service - Login Page (Arabic Only)
 */
require_once 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . getHomePage());
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, username, password, full_name, role, branch FROM users WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['branch'] = $user['branch'];
                
                $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                $stmt = $db->prepare("INSERT INTO activity_log (user_id, action, entity_type, details, ip_address) VALUES (?, 'login', 'user', 'تسجيل دخول', ?)");
                $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '']);
                
                header('Location: ' . getHomePage());
                exit;
            } else {
                $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
            }
        } catch (Exception $e) {
            $error = 'حدث خطأ، يرجى المحاولة مرة أخرى';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - 3WAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1e3a5f;
            --primary-light: #2c5282;
            --accent: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --text-primary: #1f2937;
            --text-secondary: #4b5563;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #0f2744 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container { width: 100%; max-width: 440px; }
        .login-card { background: white; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; }
        .login-header { background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%); padding: 40px; text-align: center; color: white; }
        .logo { display: flex; align-items: center; justify-content: center; gap: 16px; margin-bottom: 16px; }
        .logo-icon { width: 70px; height: 70px; background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); border-radius: 16px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 16px rgba(245,158,11,0.3); }
        .logo-icon i { font-size: 2rem; color: var(--primary); }
        .logo-text h1 { font-size: 2rem; font-weight: 800; }
        .logo-text span { font-size: 1rem; color: var(--accent); }
        .login-title { font-size: 1.1rem; margin-top: 16px; opacity: 0.95; }
        .login-body { padding: 40px; }
        .form-group { margin-bottom: 24px; }
        .form-label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-primary); }
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; top: 50%; transform: translateY(-50%); color: var(--text-secondary); right: 16px; }
        .form-control { width: 100%; padding: 16px; padding-right: 50px; border: 2px solid var(--gray-200); border-radius: 12px; font-size: 1rem; font-family: inherit; transition: all 0.2s; background: var(--gray-50); }
        .form-control:focus { outline: none; border-color: var(--primary); background: white; box-shadow: 0 0 0 4px rgba(30,58,95,0.1); }
        .password-toggle { position: absolute; top: 50%; transform: translateY(-50%); left: 16px; background: none; border: none; color: var(--text-secondary); cursor: pointer; }
        .btn-login { width: 100%; padding: 16px; background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%); color: white; border: none; border-radius: 12px; font-size: 1.1rem; font-weight: 700; font-family: inherit; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 4px 12px rgba(30,58,95,0.3); }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(30,58,95,0.4); }
        .error-message { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: var(--danger); padding: 12px 16px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }
        .footer-text { text-align: center; margin-top: 24px; color: rgba(255,255,255,0.7); font-size: 0.85rem; }
        @media (max-width: 480px) {
            .login-header { padding: 30px 20px; }
            .login-body { padding: 30px 20px; }
            .logo-icon { width: 60px; height: 60px; }
            .logo-text h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <div class="logo-icon"><i class="fas fa-car"></i></div>
                    <div class="logo-text"><h1>3WAY</h1><span>نظام إدارة الخدمات</span></div>
                </div>
                <div class="login-title">نظام إدارة خدمات السيارات</div>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                <div class="error-message"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">اسم المستخدم</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" name="username" class="form-control" placeholder="أدخل اسم المستخدم" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">كلمة المرور</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="password" class="form-control" placeholder="أدخل كلمة المرور" required>
                            <button type="button" class="password-toggle" onclick="togglePassword()"><i class="fas fa-eye" id="toggleIcon"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i>تسجيل الدخول</button>
                </form>
            </div>
        </div>
        <div class="footer-text">© 2025 3WAY - جميع الحقوق محفوظة</div>
    </div>
    <script>
        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (pwd.type === 'password') { pwd.type = 'text'; icon.classList.replace('fa-eye', 'fa-eye-slash'); }
            else { pwd.type = 'password'; icon.classList.replace('fa-eye-slash', 'fa-eye'); }
        }
    </script>
</body>
</html>
