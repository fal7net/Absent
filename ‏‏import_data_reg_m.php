<?php
require_once 'config.php';

echo "بدء استيراد البيانات من ملفات CSV...\n\n";

$conn = getDBConnection();
if (!$conn) {
    die("خطأ: فشل الاتصال بقاعدة البيانات\n");
}

// 4. استيراد سجلات السلوك
echo "استيراد سجلات السلوك...\n";
$filePath = 'reg_m.csv';
if (!file_exists($filePath)) {
    die("خطأ: الملف reg_m.csv غير موجود\n");
}
$file = fopen($filePath, 'r');
if (!$file) {
    die("خطأ: لا يمكن فتح الملف reg_m.csv\n");
}
$header = fgetcsv($file);

$count = 0;
while (($data = fgetcsv($file)) !== FALSE) {
    $st_num = $data[1];
    
    // Check if the student number exists in the students table
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE st_num = ?");
    $checkStmt->bind_param("s", $st_num);
    $checkStmt->execute();
    $checkStmt->bind_result($exists);
    $checkStmt->fetch();
    $checkStmt->close();
    
    if ($exists == 0) {
        echo "خطأ: رقم الطالب $st_num غير موجود في قاعدة البيانات\n";
        continue;
    }
    $date1 = $data[2];
    $id_m = $data[3];
    $value1 = $data[4] ?: 0;
    
    // تحويل التاريخ إلى صيغة MySQL
    if ($date1) {
        $date1 = date('Y-m-d H:i:s', strtotime($date1));
    }
    
    $stmt = $conn->prepare("INSERT INTO behavior_records (st_num, date1, id_m, value1) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        echo "خطأ في إعداد الاستعلام: " . $conn->error . "\n";
        continue;
    }
    $stmt->bind_param("ssii", $st_num, $date1, $id_m, $value1);
    if (!$stmt->execute()) {
        echo "خطأ في تنفيذ الاستعلام: " . $stmt->error . "\n";
        $stmt->close();
        continue;
    }
    $stmt->close();
    $count++;
}
fclose($file);
echo "تم استيراد $count سجل سلوك\n\n";

$conn->close();

echo "اكتمل الاستيراد بنجاح!\n";
?>
