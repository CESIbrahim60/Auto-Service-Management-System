# 3WAY Thomama - Car Service Management System
# نظام إدارة خدمات السيارات - 3WAY ثمامة

A comprehensive, bilingual (Arabic/English) car service management system designed for reception staff with easy-to-use interface.

نظام شامل ثنائي اللغة (عربي/إنجليزي) لإدارة خدمات السيارات مصمم لموظفي الاستقبال بواجهة سهلة الاستخدام.

---

## Features | المميزات

### ✅ Job Order Management | إدارة أوامر التشغيل
- Create new job orders with step-by-step wizard
- Track customer information and visit source
- Record car details and pre-existing conditions
- Select multiple services (body work, paint, PDR, polish, etc.)
- Assign technicians and estimate costs
- Upload before/after photos

### ✅ Dashboard | لوحة التحكم
- Real-time statistics overview
- Today's orders count
- Revenue tracking
- Service category statistics

### ✅ Order Management | إدارة الطلبات
- View all orders with filtering
- Search by order number, customer, phone
- Filter by status (pending, in progress, completed, delivered)
- Quick status updates
- Export to Excel

### ✅ Reports | التقارير
- Service breakdown statistics
- Visit source analytics
- Revenue reports
- Completion rate tracking
- Date range filtering

### ✅ Bilingual Support | دعم ثنائي اللغة
- Full Arabic/English interface
- RTL/LTR layout switching
- Persistent language preference

---

## Installation | التثبيت

### Requirements | المتطلبات
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled

### Steps | الخطوات

1. **Upload files to your web server**
   ```bash
   # Upload all files to your web directory
   # e.g., /var/www/html/3way/
   ```

2. **Create the database**
   ```bash
   mysql -u root -p < database.sql
   ```

3. **Configure database connection**
   Edit `includes/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', '3way_car_service');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

4. **Set permissions**
   ```bash
   chmod 755 -R /path/to/3way/
   chmod 777 -R /path/to/3way/uploads/
   ```

5. **Access the system**
   Open in browser: `http://yourdomain.com/3way/`

---

## File Structure | هيكل الملفات

```
3way-car-service/
├── api/                    # API endpoints
│   ├── save_order.php
│   ├── update_status.php
│   └── delete_order.php
├── assets/
│   ├── css/
│   │   └── style.css       # Main stylesheet
│   ├── js/
│   │   └── app.js          # Main JavaScript
│   └── images/
├── includes/
│   └── config.php          # Configuration & translations
├── uploads/                # Uploaded photos
├── index.php               # Dashboard
├── order_new.php           # New order form
├── orders.php              # Orders list
├── reports.php             # Reports & analytics
├── database.sql            # Database schema
└── README.md
```

---

## Default Login | بيانات الدخول الافتراضية

- **Username:** admin
- **Password:** admin123

⚠️ **Important:** Change the default password after first login!

---

## Services Supported | الخدمات المدعومة

### Body Work | أعمال الهيكل والسمكرة
- Body repair (سمكرة)
- Parts installation (فتح وتركيب أجزاء)
- Collision repair (إصلاح صدمات)

### Paint Work | أعمال الدهان
- Single part paint (دهان قطعة واحدة)
- Multiple parts paint (دهان أكثر من قطعة)
- Full spray (رش كامل)

### PDR | شفط طعجات بدون بوية
- Single dent (طعجة واحدة)
- Multiple dents (عدة طعجات)

### Polish & Protection | التلميع والحماية
- Exterior polish (تلميع خارجي)
- Interior polish (تلميع داخلي)
- Lights polish (تلميع الأنوار)
- Scratch treatment (معالجة الخدوش)
- Nano ceramic (حماية نانو سيراميك)
- PPF protection (حماية PPF)

### Additional Services | خدمات إضافية
- Wash (غسيل)
- Deep interior cleaning (تنظيف داخلي عميق)

---

## Visit Sources Tracking | تتبع مصادر الزيارة

Track where customers come from:
- TikTok Ads | إعلانات تيك توك
- Instagram Ads | إعلانات إنستجرام
- Snapchat | سناب شات
- Google Search | بحث جوجل
- Friend Referral | توصية من صديق
- Google Maps | خرائط جوجل
- YouTube | يوتيوب
- Direct Visit | زيارة مباشرة
- Twitter/X | تويتر

---

## Browser Support | المتصفحات المدعومة

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

---

## Mobile Responsive | متوافق مع الجوال

The system is fully responsive and works on:
- Desktop computers
- Tablets
- Mobile phones

---

## Support | الدعم

For technical support, contact your system administrator.

---

## License | الترخيص

This software is proprietary to 3WAY Thomama.

---

**Version:** 1.0.0  
**Last Updated:** 2025
