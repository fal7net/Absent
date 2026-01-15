<?php
// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'faleh');
define('DB_PASS', '1122');
define('DB_NAME', 'absentmt');

// إنشاء الاتصال بقاعدة البيانات مع محاولة ثانية
function getDBConnection() {
    $max_attempts = 2;
    $last_error = '';

    for ($attempt = 1; $attempt <= $max_attempts; $attempt++) {
        try {
            // محاولة الاتصال
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                // حفظ الخطأ الأخير
                $last_error = $conn->connect_error;
                
                // إذا لم تكن المحاولة الأخيرة، انتظر ثم حاول مجدداً
                if ($attempt < $max_attempts) {
                    sleep(1); // انتظر ثانية واحدة قبل المحاولة التالية
                    continue;
                }
                
                // إذا كانت المحاولة الأخيرة وفشلت، ارمِ استثناء
                throw new Exception("فشل الاتصال بقاعدة البيانات: " . $last_error);
            }
            
            // إذا نجح الاتصال
            $conn->set_charset("utf8mb4");
            return $conn;

        } catch (Exception $e) {
            $last_error = $e->getMessage();
            if ($attempt < $max_attempts) {
                sleep(1);
                continue;
            }
            // إذا فشلت جميع المحاولات
            die("خطأ في الاتصال بعد " . $max_attempts . " محاولات: " . $last_error);
        }
    }
}

// بدء الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// دالة للتحقق من تسجيل الدخول
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// دالة لتنظيف المدخلات
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// إعدادات عامة
date_default_timezone_set('Asia/Riyadh');


?>
