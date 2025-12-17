<?php
/**
 * 3WAY Car Service - 404 Error Page
 */
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - الصفحة غير موجودة</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        .error-container {
            text-align: center;
            max-width: 500px;
        }
        .error-icon {
            width: 150px;
            height: 150px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }
        .error-icon i {
            font-size: 4rem;
            color: #f59e0b;
        }
        .error-code {
            font-size: 6rem;
            font-weight: 800;
            color: #fff;
            line-height: 1;
            margin-bottom: 10px;
        }
        .error-title {
            font-size: 1.5rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 20px;
        }
        .error-message {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            color: rgba(255,255,255,0.8);
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        .error-message i {
            color: #f59e0b;
            margin-left: 8px;
        }
        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 32px;
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            color: #1e3a5f;
            text-decoration: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(245,158,11,0.3);
        }
        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(245,158,11,0.4);
        }
        .contact-info {
            margin-top: 40px;
            color: rgba(255,255,255,0.6);
            font-size: 0.9rem;
        }
        .contact-info a {
            color: #f59e0b;
            text-decoration: none;
        }
        @media (max-width: 480px) {
            .error-code { font-size: 4rem; }
            .error-title { font-size: 1.2rem; }
            .error-icon { width: 120px; height: 120px; }
            .error-icon i { font-size: 3rem; }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="error-code">404</div>
        <h1 class="error-title">الصفحة غير موجودة</h1>
        <div class="error-message">
            <i class="fas fa-headset"></i>
            يمكنك التواصل مع المطور لحل المشكلة
        </div>
        <a href="<?= isset($_SESSION['user_id']) ? getHomePage() : 'login.php' ?>" class="btn-home">
            <i class="fas fa-home"></i>
            العودة للصفحة الرئيسية
        </a>
        <div class="contact-info">
            <p>إذا استمرت المشكلة، يرجى التواصل مع الدعم الفني</p>
        </div>
    </div>
</body>
</html>
