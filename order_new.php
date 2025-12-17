<?php
/**
 * 3WAY Car Service - New Order Form (Arabic Only)
 */
require_once 'includes/config.php';
requireAccess('order_new.php');

// Get user's branch - if not admin, they can only create orders for their branch
$userBranch = getBranchFilter();
$canSelectBranch = canViewAllBranches();

global $sourceLabels;
$visitSources = [
    ['key' => 'tiktok', 'icon' => 'fab fa-tiktok', 'color' => '#000'],
    ['key' => 'instagram', 'icon' => 'fab fa-instagram', 'color' => '#E4405F'],
    ['key' => 'snapchat', 'icon' => 'fab fa-snapchat', 'color' => '#FFFC00'],
    ['key' => 'google_search', 'icon' => 'fab fa-google', 'color' => '#4285F4'],
    ['key' => 'google_maps', 'icon' => 'fas fa-map-marker-alt', 'color' => '#34A853'],
    ['key' => 'youtube', 'icon' => 'fab fa-youtube', 'color' => '#FF0000'],
    ['key' => 'twitter', 'icon' => 'fab fa-twitter', 'color' => '#1DA1F2'],
    ['key' => 'friend_referral', 'icon' => 'fas fa-user-friends', 'color' => '#6366f1'],
    ['key' => 'direct_visit', 'icon' => 'fas fa-store', 'color' => '#10b981'],
    ['key' => 'returning_customer', 'icon' => 'fas fa-redo', 'color' => '#f59e0b'],
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلب جديد - 3WAY</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <style>
        .wizard-steps{display:flex;justify-content:center;gap:8px;margin-bottom:30px;flex-wrap:wrap}
        .step{display:flex;align-items:center;gap:8px;padding:12px 20px;background:#f3f4f6;border-radius:25px;color:#6b7280;font-weight:600;transition:all .3s;cursor:pointer}
        .step.active{background:#1e3a5f;color:#fff}
        .step.completed{background:#10b981;color:#fff}
        .step-number{width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-weight:700}
        .form-step{display:none}
        .form-step.active{display:block}
        .card{background:#fff;border-radius:16px;box-shadow:0 4px 6px -1px rgba(0,0,0,.1);padding:24px;margin-bottom:20px}
        .card-title{font-size:1.2rem;font-weight:700;color:#1e3a5f;margin-bottom:20px;display:flex;align-items:center;gap:10px}
        .card-title i{color:#f59e0b}
        .form-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px}
        .form-group{margin-bottom:16px}
        .form-label{display:block;font-weight:600;margin-bottom:8px;color:#374151}
        .form-label.required::after{content:' *';color:#ef4444}
        .form-control{width:100%;padding:14px 16px;border:2px solid #e5e7eb;border-radius:12px;font-size:1rem;font-family:inherit;transition:all .2s}
        .form-control:focus{outline:none;border-color:#1e3a5f;box-shadow:0 0 0 3px rgba(30,58,95,.1)}
        .branch-select{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:20px}
        .branch-option{padding:20px;border:2px solid #e5e7eb;border-radius:12px;text-align:center;cursor:pointer;transition:all .2s}
        .branch-option:hover{border-color:#1e3a5f}
        .branch-option.selected{border-color:#1e3a5f;background:rgba(30,58,95,.05)}
        .branch-option input{display:none}
        .branch-option i{font-size:2rem;color:#1e3a5f;margin-bottom:8px;display:block}
        .branch-option span{font-weight:600;color:#1e3a5f}
        .source-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:12px}
        .source-item{padding:16px 12px;border:2px solid #e5e7eb;border-radius:12px;text-align:center;cursor:pointer;transition:all .2s}
        .source-item:hover{border-color:#1e3a5f}
        .source-item.selected{border-color:#1e3a5f;background:rgba(30,58,95,.05)}
        .source-item input{display:none}
        .source-item i{font-size:1.8rem;margin-bottom:8px;display:block}
        .source-item span{font-size:.8rem;color:#4b5563;display:block}
        .checkbox-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px}
        .checkbox-item{display:flex;align-items:center;gap:10px;padding:12px;background:#f9fafb;border-radius:10px;cursor:pointer;border:2px solid transparent;transition:all .2s}
        .checkbox-item:hover{background:#f3f4f6}
        .checkbox-item.checked{border-color:#1e3a5f;background:rgba(30,58,95,.05)}
        .checkbox-item input{width:18px;height:18px;accent-color:#1e3a5f}
        .service-section{margin-bottom:24px}
        .service-section-title{font-weight:700;margin-bottom:12px;display:flex;align-items:center;gap:8px}
        .service-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff}
        .service-icon.body{background:#ef4444}
        .service-icon.paint{background:#3b82f6}
        .service-icon.pdr{background:#10b981}
        .service-icon.polish{background:#8b5cf6}
        .upload-area{border:2px dashed #d1d5db;border-radius:12px;padding:40px 20px;text-align:center;cursor:pointer;transition:all .2s;background:#f9fafb}
        .upload-area:hover,.upload-area.dragover{border-color:#1e3a5f;background:rgba(30,58,95,.02)}
        .upload-area i{font-size:3rem;color:#9ca3af;margin-bottom:12px}
        .upload-area p{color:#4b5563;font-weight:600}
        .upload-area small{color:#9ca3af}
        .media-preview{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;margin-top:16px}
        .media-item{position:relative;aspect-ratio:1;border-radius:10px;overflow:hidden;background:#f3f4f6}
        .media-item img,.media-item video{width:100%;height:100%;object-fit:cover}
        .media-item .remove{position:absolute;top:6px;right:6px;width:26px;height:26px;background:rgba(239,68,68,.9);border:none;border-radius:50%;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center}
        .media-item .video-icon{position:absolute;bottom:6px;left:6px;background:rgba(0,0,0,.7);color:#fff;padding:4px 8px;border-radius:6px;font-size:.7rem}
        .form-nav{display:flex;justify-content:space-between;gap:16px;margin-top:24px}
        .technician-field{display:flex;align-items:center;gap:12px;margin-bottom:12px}
        .technician-field i{width:36px;height:36px;border-radius:8px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;color:#1e3a5f}
        .technician-field input{flex:1}
        @media(max-width:640px){.step span:not(.step-number){display:none}.source-grid{grid-template-columns:repeat(3,1fr)}.form-nav{flex-direction:column}.form-nav .btn{width:100%}}
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
                    <i class="fas fa-plus-circle"></i>
                    <h2>طلب جديد</h2>
                </div>
            </header>
            
            <!-- Wizard Steps -->
            <div class="wizard-steps">
                <div class="step active" data-step="1"><span class="step-number">1</span><span>بيانات العميل</span></div>
                <div class="step" data-step="2"><span class="step-number">2</span><span>بيانات السيارة</span></div>
                <div class="step" data-step="3"><span class="step-number">3</span><span>الخدمات</span></div>
                <div class="step" data-step="4"><span class="step-number">4</span><span>التكلفة والصور</span></div>
            </div>
            
            <form id="orderForm" action="api/save_order.php" method="POST" enctype="multipart/form-data">
                <!-- Step 1: Customer Info -->
                <div class="form-step active" data-step="1">
                    <div class="card">
                        <div class="card-title"><i class="fas fa-building"></i> الفرع</div>
                        <?php if ($canSelectBranch): ?>
                        <div class="branch-select">
                            <label class="branch-option selected" onclick="selectBranch(this)">
                                <input type="radio" name="branch" value="thumama" checked>
                                <i class="fas fa-map-marker-alt"></i>
                                <span>فرع الثمامة</span>
                            </label>
                            <label class="branch-option" onclick="selectBranch(this)">
                                <input type="radio" name="branch" value="rawdah">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>فرع الروضة</span>
                            </label>
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="branch" value="<?= $userBranch ?>">
                        <div style="padding:20px;text-align:center;background:var(--gray-50);border-radius:12px">
                            <i class="fas fa-map-marker-alt" style="font-size:2rem;color:var(--primary);margin-bottom:10px;display:block"></i>
                            <span style="font-size:1.2rem;font-weight:700;color:var(--primary)"><?= getBranchName($userBranch) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card">
                        <div class="card-title"><i class="fas fa-user"></i> بيانات العميل</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label required">اسم العميل</label>
                                <input type="text" name="customer_name" class="form-control" required placeholder="أدخل اسم العميل">
                            </div>
                            <div class="form-group">
                                <label class="form-label required">رقم الجوال</label>
                                <input type="tel" name="phone" class="form-control" required placeholder="05xxxxxxxx">
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-title"><i class="fas fa-bullhorn"></i> مصدر الزيارة</div>
                        <div class="source-grid">
                            <?php foreach ($visitSources as $source): ?>
                            <label class="source-item" onclick="selectSource(this)">
                                <input type="radio" name="visit_source" value="<?= $source['key'] ?>">
                                <i class="<?= $source['icon'] ?>" style="color:<?= $source['color'] ?>"></i>
                                <span><?= $sourceLabels[$source['key']] ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-nav">
                        <div></div>
                        <button type="button" class="btn btn-primary btn-lg" onclick="nextStep()">التالي <i class="fas fa-arrow-left"></i></button>
                    </div>
                </div>
                
                <!-- Step 2: Car Info -->
                <div class="form-step" data-step="2">
                    <div class="card">
                        <div class="card-title"><i class="fas fa-car"></i> بيانات السيارة</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label required">نوع السيارة</label>
                                <input type="text" name="car_type" class="form-control" required placeholder="مثال: تويوتا كامري">
                            </div>
                            <div class="form-group">
                                <label class="form-label">الموديل</label>
                                <input type="text" name="car_model" class="form-control" placeholder="مثال: 2024">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">اللون</label>
                                <input type="text" name="car_color" class="form-control" placeholder="مثال: أبيض">
                            </div>
                            <div class="form-group">
                                <label class="form-label">رقم اللوحة</label>
                                <input type="text" name="plate_number" class="form-control" placeholder="أدخل رقم اللوحة">
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-title"><i class="fas fa-clipboard-check"></i> حالة السيارة قبل الاستلام</div>
                        <div class="checkbox-grid">
                            <label class="checkbox-item"><input type="checkbox" name="has_dents"><span>طعجات</span></label>
                            <label class="checkbox-item"><input type="checkbox" name="has_scratches"><span>خدوش</span></label>
                            <label class="checkbox-item"><input type="checkbox" name="has_paint_erosion"><span>تآكل دهان</span></label>
                            <label class="checkbox-item"><input type="checkbox" name="has_previous_polish"><span>تلميع سابق</span></label>
                            <label class="checkbox-item"><input type="checkbox" name="has_exterior_mods"><span>تعديلات خارجية</span></label>
                        </div>
                        <div class="form-group" style="margin-top:16px">
                            <label class="form-label">ملاحظات إضافية</label>
                            <textarea name="condition_details" class="form-control" rows="3" placeholder="أي ملاحظات عن حالة السيارة..."></textarea>
                        </div>
                    </div>
                    
                    <div class="form-nav">
                        <button type="button" class="btn btn-outline btn-lg" onclick="prevStep()"><i class="fas fa-arrow-right"></i> السابق</button>
                        <button type="button" class="btn btn-primary btn-lg" onclick="nextStep()">التالي <i class="fas fa-arrow-left"></i></button>
                    </div>
                </div>
                
                <!-- Step 3: Services -->
                <div class="form-step" data-step="3">
                    <div class="card">
                        <div class="card-title"><i class="fas fa-tools"></i> الخدمات المطلوبة</div>
                        
                        <div class="service-section">
                            <div class="service-section-title"><span class="service-icon body"><i class="fas fa-hammer"></i></span> أعمال الهيكل والسمكرة</div>
                            <div class="checkbox-grid">
                                <label class="checkbox-item"><input type="checkbox" name="service_body_repair"><span>سمكرة</span></label>
                                <label class="checkbox-item"><input type="checkbox" name="service_parts_install"><span>فتح وتركيب أجزاء</span></label>
                                <label class="checkbox-item"><input type="checkbox" name="service_collision_repair"><span>إصلاح صدمات</span></label>
                            </div>
                        </div>
                        
                        <div class="service-section">
                            <div class="service-section-title"><span class="service-icon paint"><i class="fas fa-paint-roller"></i></span> أعمال الدهان</div>
                            <div class="checkbox-grid">
                                <label class="checkbox-item"><input type="checkbox" name="service_single_paint"><span>دهان قطعة واحدة</span></label>
                                <label class="checkbox-item"><input type="checkbox" name="service_multi_paint"><span>دهان أكثر من قطعة</span></label>
                                <label class="checkbox-item"><input type="checkbox" name="service_full_spray"><span>رش كامل</span></label>
                            </div>
                        </div>
                        
                        <div class="service-section">
                            <div class="service-section-title"><span class="service-icon pdr"><i class="fas fa-magnet"></i></span> PDR شفط طعجات</div>
                            <div class="checkbox-grid">
                                <label class="checkbox-item"><input type="checkbox" name="service_single_dent"><span>طعجة واحدة</span></label>
                                <label class="checkbox-item"><input type="checkbox" name="service_multi_dents"><span>عدة طعجات</span></label>
                            </div>
                        </div>
                        
                        <div class="service-section">
                            <div class="service-section-title"><span class="service-icon polish"><i class="fas fa-sparkles"></i></span> التلميع والحماية</div>
                            <div class="checkbox-grid">
                                <label class="checkbox-item"><input type="checkbox" name="service_exterior_polish"><span>تلميع خارجي</span></label>
                                <label class="checkbox-item"><input type="checkbox" name="service_interior_polish"><span>تلميع داخلي</span></label>
                                <label class="checkbox-item"><input type="checkbox" name="service_lights_polish"><span>تلميع الأنوار</span></label>
                                <label class="checkbox-item"><input type="checkbox" name="service_scratch_treatment"><span>معالجة الخدوش</span></label>
                                <label class="checkbox-item"><input type="checkbox" name="service_nano_ceramic"><span>نانو سيراميك</span></label>
                                <label class="checkbox-item"><input type="checkbox" name="service_ppf"><span>PPF حماية</span></label>
                                <label class="checkbox-item"><input type="checkbox" name="service_wash"><span>غسيل</span></label>
                                <label class="checkbox-item"><input type="checkbox" name="service_deep_cleaning"><span>تنظيف عميق</span></label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-nav">
                        <button type="button" class="btn btn-outline btn-lg" onclick="prevStep()"><i class="fas fa-arrow-right"></i> السابق</button>
                        <button type="button" class="btn btn-primary btn-lg" onclick="nextStep()">التالي <i class="fas fa-arrow-left"></i></button>
                    </div>
                </div>
                
                <!-- Step 4: Cost & Media -->
                <div class="form-step" data-step="4">
                    <div class="card">
                        <div class="card-title"><i class="fas fa-calculator"></i> التكلفة والوقت</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">التكلفة التقديرية (ريال)</label>
                                <input type="number" name="estimated_cost" class="form-control" min="0" step="0.01" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label class="form-label">الوقت المتوقع</label>
                                <input type="text" name="expected_completion_time" class="form-control" placeholder="مثال: 3 أيام">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">موعد التسليم</label>
                            <input type="datetime-local" name="delivery_date" class="form-control">
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-title"><i class="fas fa-user-hard-hat"></i> الفنيين المسؤولين</div>
                        <div class="technician-field"><i class="fas fa-hammer"></i><input type="text" name="body_technician" class="form-control" placeholder="سمكري"></div>
                        <div class="technician-field"><i class="fas fa-paint-roller"></i><input type="text" name="paint_technician" class="form-control" placeholder="دهان"></div>
                        <div class="technician-field"><i class="fas fa-magnet"></i><input type="text" name="pdr_technician" class="form-control" placeholder="فني PDR"></div>
                        <div class="technician-field"><i class="fas fa-sparkles"></i><input type="text" name="polish_technician" class="form-control" placeholder="فني تلميع"></div>
                        <div class="technician-field" style="margin-top:16px;padding-top:16px;border-top:2px solid #e5e7eb"><i class="fas fa-user-tie"></i><input type="text" name="branch_manager" class="form-control" placeholder="مدير الفرع"></div>
                    </div>
                    
                    <div class="card">
                        <div class="card-title"><i class="fas fa-camera"></i> رفع الصور والفيديو</div>
                        <div class="upload-area" id="uploadArea" onclick="document.getElementById('mediaInput').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>اضغط هنا أو اسحب الملفات</p>
                            <small>يمكنك رفع صور وفيديو (JPG, PNG, MP4, MOV) - حد أقصى 50MB</small>
                            <input type="file" name="media[]" id="mediaInput" multiple accept="image/*,video/*" style="display:none">
                        </div>
                        <div class="media-preview" id="mediaPreview"></div>
                    </div>
                    
                    <div class="form-nav">
                        <button type="button" class="btn btn-outline btn-lg" onclick="prevStep()"><i class="fas fa-arrow-right"></i> السابق</button>
                        <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-check"></i> حفظ الطلب</button>
                    </div>
                </div>
            </form>
        </main>
    </div>
    
    <div class="toast-container"></div>
    <script src="assets/js/app.js?v=<?= time() ?>"></script>
    <script>
        let currentStep = 1;
        const totalSteps = 4;
        let selectedFiles = [];
        
        function updateSteps() {
            document.querySelectorAll('.step').forEach(s => {
                const n = parseInt(s.dataset.step);
                s.classList.remove('active', 'completed');
                if (n === currentStep) s.classList.add('active');
                else if (n < currentStep) s.classList.add('completed');
            });
            document.querySelectorAll('.form-step').forEach(s => {
                s.classList.toggle('active', parseInt(s.dataset.step) === currentStep);
            });
        }
        
        function nextStep() {
            const step = document.querySelector(`.form-step[data-step="${currentStep}"]`);
            const required = step.querySelectorAll('[required]');
            let valid = true;
            required.forEach(f => {
                if (!f.value.trim()) { valid = false; f.style.borderColor = '#ef4444'; f.focus(); }
                else f.style.borderColor = '#e5e7eb';
            });
            if (!valid) { showToast('يرجى ملء جميع الحقول المطلوبة', 'error'); return; }
            if (currentStep < totalSteps) { currentStep++; updateSteps(); window.scrollTo({top:0,behavior:'smooth'}); }
        }
        
        function prevStep() { if (currentStep > 1) { currentStep--; updateSteps(); window.scrollTo({top:0,behavior:'smooth'}); } }
        
        function selectBranch(el) {
            document.querySelectorAll('.branch-option').forEach(b => b.classList.remove('selected'));
            el.classList.add('selected');
            el.querySelector('input').checked = true;
        }
        
        function selectSource(el) {
            document.querySelectorAll('.source-item').forEach(s => s.classList.remove('selected'));
            el.classList.add('selected');
            el.querySelector('input').checked = true;
        }
        
        document.querySelectorAll('.checkbox-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (e.target.tagName !== 'INPUT') {
                    const cb = this.querySelector('input');
                    cb.checked = !cb.checked;
                }
                this.classList.toggle('checked', this.querySelector('input').checked);
            });
        });
        
        // Media upload handling
        const uploadArea = document.getElementById('uploadArea');
        const mediaInput = document.getElementById('mediaInput');
        const mediaPreview = document.getElementById('mediaPreview');
        
        uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.classList.add('dragover'); });
        uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
        uploadArea.addEventListener('drop', e => { e.preventDefault(); uploadArea.classList.remove('dragover'); handleFiles(e.dataTransfer.files); });
        mediaInput.addEventListener('change', e => handleFiles(e.target.files));
        
        function handleFiles(files) {
            const allowed = ['image/jpeg','image/png','image/gif','image/webp','video/mp4','video/webm','video/quicktime','video/x-msvideo'];
            Array.from(files).forEach(file => {
                if (selectedFiles.length >= 20) { showToast('الحد الأقصى 20 ملف', 'error'); return; }
                if (!allowed.includes(file.type)) { showToast('نوع الملف غير مدعوم', 'error'); return; }
                if (file.size > 50 * 1024 * 1024) { showToast('حجم الملف كبير جداً', 'error'); return; }
                selectedFiles.push(file);
                addPreview(file, selectedFiles.length - 1);
            });
            updateInput();
        }
        
        function addPreview(file, index) {
            const div = document.createElement('div');
            div.className = 'media-item';
            div.dataset.index = index;
            
            const isVideo = file.type.startsWith('video/');
            const url = URL.createObjectURL(file);
            
            if (isVideo) {
                div.innerHTML = `<video src="${url}"></video><span class="video-icon"><i class="fas fa-play"></i></span><button type="button" class="remove" onclick="removeMedia(${index})"><i class="fas fa-times"></i></button>`;
            } else {
                div.innerHTML = `<img src="${url}"><button type="button" class="remove" onclick="removeMedia(${index})"><i class="fas fa-times"></i></button>`;
            }
            mediaPreview.appendChild(div);
        }
        
        function removeMedia(index) {
            selectedFiles = selectedFiles.filter((_, i) => i !== index);
            mediaPreview.innerHTML = '';
            selectedFiles.forEach((f, i) => addPreview(f, i));
            updateInput();
        }
        
        function updateInput() {
            const dt = new DataTransfer();
            selectedFiles.forEach(f => dt.items.add(f));
            mediaInput.files = dt.files;
        }
        
        // Form submission
        document.getElementById('orderForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...';
            
            fetch(this.action, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('تم إنشاء الطلب بنجاح', 'success');
                    setTimeout(() => window.location.href = 'order_view.php?id=' + data.order_id, 1500);
                } else {
                    showToast(data.message || 'حدث خطأ', 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check"></i> حفظ الطلب';
                }
            })
            .catch(() => {
                showToast('حدث خطأ في الاتصال', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> حفظ الطلب';
            });
        });
    </script>
</body>
</html>
