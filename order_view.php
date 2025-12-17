<?php
/**
 * 3WAY Car Service - View Order (Arabic Only)
 */
require_once 'includes/config.php';
requireAccess('order_view.php');

$db = Database::getInstance()->getConnection();
$orderId = intval($_GET['id'] ?? 0);
if (!$orderId) { header('Location: orders.php'); exit; }

$stmt = $db->prepare("SELECT j.*, c.name as customer_name, c.phone as customer_phone, u.full_name as created_by_name 
                      FROM job_orders j 
                      LEFT JOIN customers c ON j.customer_id = c.id 
                      LEFT JOIN users u ON j.created_by = u.id 
                      WHERE j.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) { header('Location: orders.php'); exit; }

// Check access restrictions
if (isReception()) {
    // Reception can only view their own orders
    if ($order['created_by'] != $_SESSION['user_id']) {
        header('Location: 404.php');
        exit;
    }
} else {
    // Manager can only view orders from their branch
    $userBranch = getBranchFilter();
    if ($userBranch && $order['branch'] !== $userBranch) {
        header('Location: 404.php');
        exit;
    }
}

$stmt = $db->prepare("SELECT * FROM order_photos WHERE order_id = ? ORDER BY photo_type, uploaded_at");
$stmt->execute([$orderId]);
$media = $stmt->fetchAll(PDO::FETCH_ASSOC);
$beforeMedia = array_filter($media, fn($m) => $m['photo_type'] === 'before');
$afterMedia = array_filter($media, fn($m) => $m['photo_type'] === 'after');

global $statusLabels, $sourceLabels;

$services = [];
if ($order['service_body_repair']) $services[] = 'سمكرة';
if ($order['service_parts_install']) $services[] = 'فتح وتركيب';
if ($order['service_collision_repair']) $services[] = 'إصلاح صدمات';
if ($order['service_single_paint']) $services[] = 'دهان قطعة';
if ($order['service_multi_paint']) $services[] = 'دهان متعدد';
if ($order['service_full_spray']) $services[] = 'رش كامل';
if ($order['service_single_dent']) $services[] = 'PDR طعجة';
if ($order['service_multi_dents']) $services[] = 'PDR متعدد';
if ($order['service_exterior_polish']) $services[] = 'تلميع خارجي';
if ($order['service_interior_polish']) $services[] = 'تلميع داخلي';
if ($order['service_nano_ceramic']) $services[] = 'نانو سيراميك';
if ($order['service_ppf']) $services[] = 'PPF';
if ($order['service_wash']) $services[] = 'غسيل';

$conditions = [];
if ($order['has_dents']) $conditions[] = 'طعجات';
if ($order['has_scratches']) $conditions[] = 'خدوش';
if ($order['has_paint_erosion']) $conditions[] = 'تآكل دهان';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $order['order_number'] ?> - 3WAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <style>
        .order-header{background:#fff;border-radius:16px;padding:24px;margin-bottom:24px;box-shadow:var(--shadow);display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:20px}
        .order-number{font-size:1.5rem;font-weight:700;color:var(--primary)}
        .order-date{color:var(--gray-500);margin-top:4px}
        .status-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:20px;font-weight:600}
        .status-badge.pending{background:rgba(245,158,11,.1);color:#d97706}
        .status-badge.in_progress{background:rgba(59,130,246,.1);color:#2563eb}
        .status-badge.completed{background:rgba(16,185,129,.1);color:#059669}
        .status-badge.delivered{background:rgba(139,92,246,.1);color:#7c3aed}
        .detail-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px}
        .detail-card{background:#fff;border-radius:16px;box-shadow:var(--shadow);overflow:hidden}
        .detail-card-header{padding:16px 20px;background:var(--gray-50);border-bottom:1px solid var(--gray-200);font-weight:700;display:flex;align-items:center;gap:10px;color:var(--primary)}
        .detail-card-header i{color:var(--accent)}
        .detail-card-body{padding:20px}
        .detail-row{display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--gray-100)}
        .detail-row:last-child{border-bottom:none}
        .detail-label{color:var(--gray-500)}
        .detail-value{font-weight:600}
        .service-tags{display:flex;flex-wrap:wrap;gap:8px}
        .service-tag{padding:6px 12px;background:var(--primary);color:#fff;border-radius:20px;font-size:.85rem}
        .condition-tag{padding:6px 12px;background:rgba(245,158,11,.1);color:#d97706;border-radius:20px;font-size:.85rem}
        .customer-avatar{width:60px;height:60px;background:linear-gradient(135deg,var(--primary),var(--primary-light));border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;font-weight:700}
        .cost-highlight{font-size:2rem;font-weight:700;color:var(--success);text-align:center;padding:20px}
        .media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;margin-top:12px}
        .media-item{position:relative;border-radius:10px;overflow:hidden;background:var(--gray-100)}
        .media-preview{aspect-ratio:1;cursor:pointer;position:relative}
        .media-preview img,.media-preview video{width:100%;height:100%;object-fit:cover}
        .media-item .play-icon{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.3);color:#fff;font-size:2rem;pointer-events:none}
        .download-btn{display:flex;align-items:center;justify-content:center;gap:6px;padding:8px;background:var(--primary);color:#fff;text-decoration:none;font-size:.85rem;font-weight:600;transition:background .2s}
        .download-btn:hover{background:var(--primary-light)}
        .lightbox{position:fixed;inset:0;background:rgba(0,0,0,.9);z-index:9999;display:none;align-items:center;justify-content:center}
        .lightbox.active{display:flex}
        .lightbox img,.lightbox video{max-width:90%;max-height:90%;border-radius:8px}
        .lightbox-close{position:absolute;top:20px;left:20px;color:#fff;font-size:2rem;cursor:pointer;background:none;border:none}
        @media(max-width:640px){.order-header{flex-direction:column}.detail-grid{grid-template-columns:1fr}}
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
                    <a href="orders.php" style="color:var(--gray-500);margin-left:10px"><i class="fas fa-arrow-right"></i></a>
                    <h2>تفاصيل الطلب</h2>
                </div>
                <div class="header-actions">
                    <a href="edit_order.php?id=<?= $orderId ?>" class="btn btn-outline"><i class="fas fa-edit"></i> تعديل</a>
                    <a href="print_order.php?id=<?= $orderId ?>" target="_blank" class="btn btn-primary"><i class="fas fa-print"></i> طباعة</a>
                </div>
            </header>
            
            <div class="order-header">
                <div>
                    <div class="order-number"><?= $order['order_number'] ?></div>
                    <div class="order-date"><i class="fas fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
                    <div style="margin-top:8px;font-size:.85rem;color:var(--gray-500)">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($order['created_by_name'] ?? 'غير معروف') ?>
                        | <i class="fas fa-building"></i> <?= getBranchName($order['branch']) ?>
                    </div>
                </div>
                <div>
                    <span class="status-badge <?= $order['status'] ?>"><?= $statusLabels[$order['status']] ?? $order['status'] ?></span>
                </div>
            </div>
            
            <div class="detail-grid">
                <div class="detail-card">
                    <div class="detail-card-header"><i class="fas fa-user"></i> بيانات العميل</div>
                    <div class="detail-card-body">
                        <div style="display:flex;align-items:center;gap:16px;margin-bottom:16px">
                            <div class="customer-avatar"><?= mb_substr($order['customer_name'], 0, 1) ?></div>
                            <div>
                                <h4 style="margin:0"><?= htmlspecialchars($order['customer_name']) ?></h4>
                                <p style="margin:4px 0;color:var(--gray-500)"><i class="fas fa-phone"></i> <?= $order['customer_phone'] ?></p>
                            </div>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">مصدر الزيارة</span>
                            <span class="detail-value"><?= $sourceLabels[$order['visit_source']] ?? '-' ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-card-header"><i class="fas fa-car"></i> بيانات السيارة</div>
                    <div class="detail-card-body">
                        <div class="detail-row"><span class="detail-label">النوع</span><span class="detail-value"><?= htmlspecialchars($order['car_type']) ?></span></div>
                        <div class="detail-row"><span class="detail-label">الموديل</span><span class="detail-value"><?= $order['car_model'] ?: '-' ?></span></div>
                        <div class="detail-row"><span class="detail-label">اللون</span><span class="detail-value"><?= $order['car_color'] ?: '-' ?></span></div>
                        <div class="detail-row"><span class="detail-label">اللوحة</span><span class="detail-value"><?= $order['plate_number'] ?: '-' ?></span></div>
                    </div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-card-header"><i class="fas fa-tools"></i> الخدمات</div>
                    <div class="detail-card-body">
                        <?php if ($services): ?>
                        <div class="service-tags"><?php foreach ($services as $s): ?><span class="service-tag"><?= $s ?></span><?php endforeach; ?></div>
                        <?php else: ?>
                        <p style="color:var(--gray-400)">لا توجد خدمات محددة</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-card-header"><i class="fas fa-calculator"></i> التكلفة</div>
                    <div class="detail-card-body">
                        <div class="cost-highlight"><?= number_format($order['estimated_cost']) ?> ريال</div>
                        <div class="detail-row"><span class="detail-label">الوقت المتوقع</span><span class="detail-value"><?= $order['expected_completion_time'] ?: '-' ?></span></div>
                        <div class="detail-row"><span class="detail-label">موعد التسليم</span><span class="detail-value"><?= $order['delivery_date'] ? date('d/m/Y', strtotime($order['delivery_date'])) : '-' ?></span></div>
                    </div>
                </div>
                
                <?php if (!empty($beforeMedia) || !empty($afterMedia)): ?>
                <div class="detail-card" style="grid-column:1/-1">
                    <div class="detail-card-header"><i class="fas fa-images"></i> الصور والفيديو</div>
                    <div class="detail-card-body">
                        <?php if (!empty($beforeMedia)): ?>
                        <h4 style="margin-bottom:12px;color:var(--primary)"><i class="fas fa-arrow-down" style="color:var(--accent)"></i> قبل العمل</h4>
                        <div class="media-grid">
                            <?php foreach ($beforeMedia as $m): ?>
                            <div class="media-item">
                                <div class="media-preview" onclick="openLightbox('<?= $m['file_path'] ?>', '<?= $m['media_type'] ?>')">
                                    <?php if ($m['media_type'] === 'video'): ?>
                                    <video src="<?= $m['file_path'] ?>"></video>
                                    <div class="play-icon"><i class="fas fa-play"></i></div>
                                    <?php else: ?>
                                    <img src="<?= $m['file_path'] ?>" alt="">
                                    <?php endif; ?>
                                </div>
                                <a href="api/download_media.php?id=<?= $m['id'] ?>" class="download-btn" title="تحميل">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($afterMedia)): ?>
                        <h4 style="margin:24px 0 12px;color:var(--primary)"><i class="fas fa-arrow-up" style="color:var(--success)"></i> بعد العمل</h4>
                        <div class="media-grid">
                            <?php foreach ($afterMedia as $m): ?>
                            <div class="media-item">
                                <div class="media-preview" onclick="openLightbox('<?= $m['file_path'] ?>', '<?= $m['media_type'] ?>')">
                                    <?php if ($m['media_type'] === 'video'): ?>
                                    <video src="<?= $m['file_path'] ?>"></video>
                                    <div class="play-icon"><i class="fas fa-play"></i></div>
                                    <?php else: ?>
                                    <img src="<?= $m['file_path'] ?>" alt="">
                                    <?php endif; ?>
                                </div>
                                <a href="api/download_media.php?id=<?= $m['id'] ?>" class="download-btn" title="تحميل">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <div class="lightbox" id="lightbox">
        <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
        <img id="lightboxImg" src="" style="display:none">
        <video id="lightboxVideo" src="" controls style="display:none"></video>
    </div>
    
    <script src="assets/js/app.js?v=<?= time() ?>"></script>
    <script>
        function openLightbox(src, type) {
            const lb = document.getElementById('lightbox');
            const img = document.getElementById('lightboxImg');
            const vid = document.getElementById('lightboxVideo');
            
            if (type === 'video') {
                img.style.display = 'none';
                vid.style.display = 'block';
                vid.src = src;
            } else {
                vid.style.display = 'none';
                img.style.display = 'block';
                img.src = src;
            }
            lb.classList.add('active');
        }
        
        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('active');
            document.getElementById('lightboxVideo').pause();
        }
        
        document.getElementById('lightbox').addEventListener('click', function(e) {
            if (e.target === this) closeLightbox();
        });
    </script>
</body>
</html>
