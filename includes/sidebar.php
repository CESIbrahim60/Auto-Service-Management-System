<?php
/**
 * 3WAY Car Service - Sidebar Component (Arabic Only)
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #f59e0b, #fbbf24); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-car" style="color: #1e3a5f; font-size: 1.5rem;"></i>
            </div>
            <div>
                <h1>3WAY</h1>
                <span>نظام إدارة الخدمات</span>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">القائمة الرئيسية</div>
            
            <?php if (isManager()): ?>
            <a href="index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i>
                <span>لوحة التحكم</span>
            </a>
            <?php endif; ?>
            
            <a href="order_new.php" class="nav-link <?= $currentPage === 'order_new.php' ? 'active' : '' ?>">
                <i class="fas fa-plus-circle"></i>
                <span>طلب جديد</span>
            </a>
            
            <a href="orders.php" class="nav-link <?= $currentPage === 'orders.php' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>الطلبات</span>
            </a>
        </div>
        
        <?php if (isManager()): ?>
        <div class="nav-section">
            <div class="nav-section-title">الإدارة</div>
            
            <?php if (isAdmin()): ?>
            <a href="reports.php" class="nav-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>
                <span>التقارير</span>
            </a>
            <?php endif; ?>
            
            <a href="customers.php" class="nav-link <?= $currentPage === 'customers.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>العملاء</span>
            </a>
            
            <?php if (isAdmin()): ?>
            <a href="employees.php" class="nav-link <?= $currentPage === 'employees.php' ? 'active' : '' ?>">
                <i class="fas fa-user-tie"></i>
                <span>الموظفين</span>
            </a>
            <?php endif; ?>
            
            <a href="settings.php" class="nav-link <?= $currentPage === 'settings.php' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                <span>الإعدادات</span>
            </a>
        </div>
        <?php endif; ?>
        
        <div class="nav-section">
            <div class="nav-section-title">الحساب</div>
            <div class="user-info">
                <div class="user-avatar"><?= mb_substr($_SESSION['full_name'] ?? 'م', 0, 1) ?></div>
                <div class="user-details">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></span>
                    <span class="user-role"><?= $_SESSION['role'] === 'admin' ? 'مدير النظام' : ($_SESSION['role'] === 'manager' ? 'مدير' : 'استقبال') ?></span>
                </div>
            </div>
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>تسجيل الخروج</span>
            </a>
        </div>
    </nav>
</aside>
