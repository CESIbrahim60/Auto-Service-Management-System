<?php
/**
 * 3WAY Car Service - Settings (Arabic Only - Manager/Admin)
 */
require_once 'includes/config.php';
requireAccess('settings.php');

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPwd = $_POST['current_password'] ?? '';
    $newPwd = $_POST['new_password'] ?? '';
    $confirmPwd = $_POST['confirm_password'] ?? '';
    
    try {
        if (empty($currentPwd) || empty($newPwd) || empty($confirmPwd)) {
            throw new Exception('يرجى ملء جميع الحقول');
        }
        if ($newPwd !== $confirmPwd) {
            throw new Exception('كلمة المرور الجديدة غير متطابقة');
        }
        if (strlen($newPwd) < 6) {
            throw new Exception('كلمة المرور يجب أن تكون 6 أحرف على الأقل');
        }
        
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!password_verify($currentPwd, $user['password'])) {
            throw new Exception('كلمة المرور الحالية غير صحيحة');
        }
        
        $hashedPwd = password_hash($newPwd, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPwd, $_SESSION['user_id']]);
        
        $message = 'تم تغيير كلمة المرور بنجاح';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get current user info
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();

// Get system stats (admin only)
$stats = [];
if (isAdmin()) {
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
    $stats['total_users'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM customers");
    $stats['total_customers'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM job_orders");
    $stats['total_orders'] = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعدادات - 3WAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <style>
        .settings-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(350px,1fr));gap:24px}
        .settings-card{background:#fff;border-radius:16px;box-shadow:var(--shadow);overflow:hidden}
        .settings-card-header{padding:20px;background:var(--gray-50);border-bottom:1px solid var(--gray-200);display:flex;align-items:center;gap:12px}
        .settings-card-header h3{font-size:1.1rem;color:var(--primary);margin:0}
        .settings-card-header i{color:var(--accent)}
        .settings-card-body{padding:24px}
        .profile-info{text-align:center;padding:20px}
        .profile-avatar{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-light));display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;font-weight:700;margin:0 auto 16px}
        .profile-name{font-size:1.25rem;font-weight:700;color:var(--primary)}
        .profile-role{color:var(--gray-500);margin-top:4px}
        .profile-branch{margin-top:8px;padding:6px 16px;background:var(--gray-100);border-radius:20px;display:inline-block;font-size:.9rem}
        .form-group{margin-bottom:20px}
        .form-group label{display:block;font-weight:600;margin-bottom:8px;color:var(--gray-700)}
        .form-group input{width:100%;padding:12px 16px;border:2px solid var(--gray-200);border-radius:10px;font-family:inherit;font-size:1rem}
        .form-group input:focus{outline:none;border-color:var(--primary)}
        .stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
        .stat-item{text-align:center;padding:16px;background:var(--gray-50);border-radius:10px}
        .stat-value{font-size:1.5rem;font-weight:700;color:var(--primary)}
        .stat-label{font-size:.85rem;color:var(--gray-500);margin-top:4px}
        @media(max-width:640px){.settings-grid{grid-template-columns:1fr}.stats-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>
        <div class="sidebar-overlay"></div>
        <main class="main-content">
            <header class="top-header">
                <div class="page-title">
                    <button class="menu-toggle"><i class="fas fa-bars"></i></button>
                    <i class="fas fa-cog"></i>
                    <h2>الإعدادات</h2>
                </div>
            </header>
            
            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>"><i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i> <?= $message ?></div>
            <?php endif; ?>
            
            <div class="settings-grid">
                <div class="settings-card">
                    <div class="settings-card-header"><i class="fas fa-user"></i><h3>معلومات الحساب</h3></div>
                    <div class="settings-card-body">
                        <div class="profile-info">
                            <div class="profile-avatar"><?= mb_substr($currentUser['full_name'], 0, 1) ?></div>
                            <div class="profile-name"><?= htmlspecialchars($currentUser['full_name']) ?></div>
                            <div class="profile-role"><?= $currentUser['role'] === 'admin' ? 'مدير النظام' : ($currentUser['role'] === 'manager' ? 'مدير' : 'استقبال') ?></div>
                            <div class="profile-branch"><i class="fas fa-building"></i> <?= getBranchName($currentUser['branch']) ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="settings-card">
                    <div class="settings-card-header"><i class="fas fa-lock"></i><h3>تغيير كلمة المرور</h3></div>
                    <div class="settings-card-body">
                        <form method="POST">
                            <input type="hidden" name="change_password" value="1">
                            <div class="form-group">
                                <label>كلمة المرور الحالية</label>
                                <input type="password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label>كلمة المرور الجديدة</label>
                                <input type="password" name="new_password" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label>تأكيد كلمة المرور</label>
                                <input type="password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%"><i class="fas fa-save"></i> حفظ التغييرات</button>
                        </form>
                    </div>
                </div>
                
                <?php if (isAdmin()): ?>
                <div class="settings-card">
                    <div class="settings-card-header"><i class="fas fa-chart-bar"></i><h3>إحصائيات النظام</h3></div>
                    <div class="settings-card-body">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value"><?= $stats['total_users'] ?></div>
                                <div class="stat-label">المستخدمين</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?= $stats['total_customers'] ?></div>
                                <div class="stat-label">العملاء</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?= $stats['total_orders'] ?></div>
                                <div class="stat-label">الطلبات</div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>
