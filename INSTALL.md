# دليل التثبيت السريع

## الخطوات الأساسية

### 1. إنشاء قاعدة البيانات

```bash
# تسجيل الدخول إلى MySQL
sudo mysql -u root

# تنفيذ الأوامر التالية داخل MySQL
CREATE DATABASE student_attendance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'student_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON student_attendance.* TO 'student_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# استيراد هيكل قاعدة البيانات
mysql -u student_user -p student_attendance < database.sql
```

### 2. تحديث إعدادات الاتصال

عدّل ملف `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'student_user');
define('DB_PASS', 'strong_password_here');
define('DB_NAME', 'student_attendance');
```

### 3. استيراد البيانات

```bash
php import_data.php
```

### 4. إعداد صلاحيات الملفات

```bash
# للخادم Apache
sudo chown -R www-data:www-data /var/www/html/student_attendance_system
sudo chmod -R 755 /var/www/html/student_attendance_system

# للخادم Nginx
sudo chown -R www-data:www-data /var/www/html/student_attendance_system
sudo chmod -R 755 /var/www/html/student_attendance_system
```

### 5. تفعيل الموقع

افتح المتصفح وانتقل إلى:
```
http://localhost/student_attendance_system
```

أو إذا كنت تستخدم خادم محلي بـ PHP:
```bash
cd /path/to/student_attendance_system
php -S localhost:8000
```

ثم افتح: `http://localhost:8000`

## بيانات الدخول الافتراضية

- **اسم المستخدم**: admin
- **كلمة المرور**: admin123

**مهم جداً**: غيّر كلمة المرور فوراً بعد أول تسجيل دخول!

## استكشاف الأخطاء

### خطأ: "فشل الاتصال بقاعدة البيانات"
- تأكد من تشغيل خدمة MySQL: `sudo systemctl status mysql`
- تأكد من صحة بيانات الاتصال في `config.php`
- تأكد من إنشاء قاعدة البيانات والمستخدم

### خطأ: "Class 'Dompdf\Dompdf' not found"
- نفذ: `php composer.phar install`

### الصفحات لا تعمل
- تأكد من تفعيل mod_rewrite في Apache
- تأكد من صلاحيات الملفات

## الدعم

للمزيد من المعلومات، راجع ملف `README.md`
