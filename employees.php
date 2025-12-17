<?php
/**
 * 3WAY Car Service - Employees Management & Monitoring (Admin Only)
 */
require_once 'includes/config.php';
requireAccess('employees.php');

$db = Database::getInstance()->getConnection();

$branchFilter = $_GET['branch'] ?? '';
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');

$whereClause = " WHERE u.role IN ('reception', 'manager', 'admin')";
$params = [];
if ($branchFilter && in_array($branchFilter, ['thumama', 'rawdah'])) {
    $whereClause .= " AND u.branch = ?";
    $params[] = $branchFilter;
}

$query = "SELECT u.id, u.username, u.full_name, u.role, u.branch, u.is_active, u.last_login, u.created_at,
    (SELECT COUNT(*) FROM job_orders WHERE created_by = u.id AND DATE(created_at) BETWEEN ? AND ?) as orders_created,
    (SELECT COUNT(*) FROM job_orders WHERE created_by = u.id AND status IN ('completed', 'delivered') AND DATE(created_at) BETWEEN ? AND ?) as orders_completed,
    (SELECT COALESCE(SUM(estimated_cost), 0) FROM job_orders WHERE created_by = u.id AND DATE(created_at) BETWEEN ? AND ?) as total_revenue
    FROM users u $whereClause ORDER BY orders_created DESC";

$stmt = $db->prepare($query);
$stmt->execute(array_merge([$dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo], $params));
$employees = $stmt->fetchAll();

$totalCreated = array_sum(array_column($employees, 'orders_created'));
$totalCompleted = array_sum(array_column($employees, 'orders_completed'));
$totalRevenue = array_sum(array_column($employees, 'total_revenue'));

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add') {
            $username = sanitize($_POST['username']);
            $fullName = sanitize($_POST['full_name']);
            $role = sanitize($_POST['role']);
            $branch = sanitize($_POST['user_branch']);
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($fullName) || empty($password)) {
                throw new Exception('يرجى ملء جميع الحقول المطلوبة');
            }
            
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) throw new Exception('اسم المستخدم موجود مسبقاً');
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, full_name, role, branch) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashedPassword, $fullName, $role, $branch]);
            $message = 'تم إضافة المستخدم بنجاح';
            $messageType = 'success';
        } elseif ($action === 'toggle') {
            $userId = intval($_POST['user_id']);
            $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND id != ?");
            $stmt->execute([$userId, $_SESSION['user_id']]);
            $message = 'تم تحديث حالة المستخدم';
            $messageType = 'success';
        } elseif ($action === 'delete') {
            $userId = intval($_POST['user_id']);
            if ($userId == $_SESSION['user_id']) throw new Exception('لا يمكنك حذف حسابك');
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $message = 'تم حذف المستخدم';
            $messageType = 'success';
        }
        header("Location: employees.php?branch=$branchFilter&from=$dateFrom&to=$dateTo");
        exit;
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

$roleLabels = ['admin' => 'مدير النظام', 'manager' => 'مدير', 'reception' => 'استقبال'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الموظفين - 3WAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <style>
        .filters { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; align-items: flex-end; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-group label { font-weight: 600; font-size: 0.85rem; color: #4b5563; }
        .filter-group select, .filter-group input { padding: 10px 14px; border: 2px solid #e5e7eb; border-radius: 8px; font-family: inherit; min-width: 150px; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 25px; }
        .stat-box { background: #fff; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .stat-box .value { font-size: 2rem; font-weight: 800; color: #1e3a5f; }
        .stat-box .label { color: #6b7280; font-size: 0.9rem; margin-top: 4px; }
        .employees-table { width: 100%; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .employees-table th, .employees-table td { padding: 16px; text-align: right; border-bottom: 1px solid #e5e7eb; }
        .employees-table th { background: #f9fafb; font-weight: 700; color: #1e3a5f; }
        .employees-table tr:hover { background: #f9fafb; }
        .employee-name { display: flex; align-items: center; gap: 12px; }
        .employee-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #1e3a5f, #2c5282); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge.admin { background: #fef3c7; color: #d97706; }
        .badge.manager { background: #dbeafe; color: #2563eb; }
        .badge.reception { background: #d1fae5; color: #059669; }
        .badge.active { background: #d1fae5; color: #059669; }
        .badge.inactive { background: #fee2e2; color: #dc2626; }
        .progress-bar { width: 100%; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; }
        .progress-bar .fill { height: 100%; background: linear-gradient(90deg, #1e3a5f, #3b82f6); border-radius: 4px; }
        .action-btns { display: flex; gap: 8px; }
        .btn-icon { width: 32px; height: 32px; border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .btn-icon.edit { background: #dbeafe; color: #2563eb; }
        .btn-icon.delete { background: #fee2e2; color: #dc2626; }
        .btn-icon:hover { transform: scale(1.1); }
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px; }
        .modal.active { display: flex; }
        .modal-content { background: #fff; border-radius: 16px; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
        .modal-header { padding: 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { font-size: 1.2rem; color: #1e3a5f; }
        .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280; }
        .modal-body { padding: 20px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 6px; color: #374151; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 10px; font-family: inherit; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #1e3a5f; }
        @media (max-width: 768px) {
            .employees-table { display: block; overflow-x: auto; }
            .filters { flex-direction: column; }
            .filter-group select, .filter-group input { width: 100%; }
        }
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
                    <i class="fas fa-user-tie"></i>
                    <h2>إدارة الموظفين</h2>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> إضافة موظف</button>
                </div>
            </header>
            
            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>"><i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i> <?= $message ?></div>
            <?php endif; ?>
            
            <form class="filters" method="GET">
                <div class="filter-group">
                    <label>الفرع</label>
                    <select name="branch" onchange="this.form.submit()">
                        <option value="">جميع الفروع</option>
                        <option value="thumama" <?= $branchFilter === 'thumama' ? 'selected' : '' ?>>فرع الثمامة</option>
                        <option value="rawdah" <?= $branchFilter === 'rawdah' ? 'selected' : '' ?>>فرع الروضة</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>من تاريخ</label>
                    <input type="date" name="from" value="<?= $dateFrom ?>">
                </div>
                <div class="filter-group">
                    <label>إلى تاريخ</label>
                    <input type="date" name="to" value="<?= $dateTo ?>">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> تصفية</button>
            </form>
            
            <div class="stats-row">
                <div class="stat-box">
                    <div class="value"><?= count($employees) ?></div>
                    <div class="label">عدد الموظفين</div>
                </div>
                <div class="stat-box">
                    <div class="value"><?= number_format($totalCreated) ?></div>
                    <div class="label">طلبات تم إنشاؤها</div>
                </div>
                <div class="stat-box">
                    <div class="value"><?= number_format($totalCompleted) ?></div>
                    <div class="label">طلبات مكتملة</div>
                </div>
                <div class="stat-box">
                    <div class="value"><?= number_format($totalRevenue) ?></div>
                    <div class="label">إجمالي الإيرادات</div>
                </div>
            </div>
            
            <table class="employees-table">
                <thead>
                    <tr>
                        <th>الموظف</th>
                        <th>الفرع</th>
                        <th>الصلاحية</th>
                        <th>طلبات أنشأها</th>
                        <th>طلبات أكملها</th>
                        <th>الإيرادات</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $emp): ?>
                    <?php $percentage = $totalCreated > 0 ? ($emp['orders_created'] / $totalCreated) * 100 : 0; ?>
                    <tr>
                        <td>
                            <div class="employee-name">
                                <div class="employee-avatar"><?= mb_substr($emp['full_name'], 0, 1) ?></div>
                                <div>
                                    <strong><?= htmlspecialchars($emp['full_name']) ?></strong>
                                    <small style="display:block;color:#6b7280;">@<?= $emp['username'] ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= getBranchName($emp['branch']) ?></td>
                        <td><span class="badge <?= $emp['role'] ?>"><?= $roleLabels[$emp['role']] ?></span></td>
                        <td>
                            <strong><?= $emp['orders_created'] ?></strong>
                            <div class="progress-bar" style="margin-top:4px;"><div class="fill" style="width:<?= $percentage ?>%"></div></div>
                        </td>
                        <td><strong><?= $emp['orders_completed'] ?></strong></td>
                        <td><?= number_format($emp['total_revenue']) ?> ريال</td>
                        <td><span class="badge <?= $emp['is_active'] ? 'active' : 'inactive' ?>"><?= $emp['is_active'] ? 'نشط' : 'معطل' ?></span></td>
                        <td>
                            <div class="action-btns">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="user_id" value="<?= $emp['id'] ?>">
                                    <button type="submit" class="btn-icon edit" title="تبديل الحالة"><i class="fas fa-power-off"></i></button>
                                </form>
                                <?php if ($emp['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= $emp['id'] ?>">
                                    <button type="submit" class="btn-icon delete" title="حذف"><i class="fas fa-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
    
    <!-- Add Employee Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> إضافة موظف جديد</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label>اسم المستخدم *</label>
                        <input type="text" name="username" required placeholder="أدخل اسم المستخدم">
                    </div>
                    <div class="form-group">
                        <label>الاسم الكامل *</label>
                        <input type="text" name="full_name" required placeholder="أدخل الاسم الكامل">
                    </div>
                    <div class="form-group">
                        <label>كلمة المرور *</label>
                        <input type="password" name="password" required placeholder="أدخل كلمة المرور">
                    </div>
                    <div class="form-group">
                        <label>الصلاحية *</label>
                        <select name="role" required>
                            <option value="reception">استقبال</option>
                            <option value="manager">مدير</option>
                            <option value="admin">مدير النظام</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>الفرع *</label>
                        <select name="user_branch" required>
                            <option value="thumama">فرع الثمامة</option>
                            <option value="rawdah">فرع الروضة</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%"><i class="fas fa-save"></i> حفظ</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="assets/js/app.js?v=<?= time() ?>"></script>
    <script>
        function openModal() { document.getElementById('addModal').classList.add('active'); }
        function closeModal() { document.getElementById('addModal').classList.remove('active'); }
        document.getElementById('addModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
    </script>
</body>
</html>
