<?php
require_once 'config.php';

echo "بدء استيراد البيانات من ملفات CSV...\n\n";

$conn = getDBConnection();

// 1. استيراد بيانات الطلاب
echo "استيراد بيانات الطلاب...\n";
$file = fopen('st_data.csv', 'r');
$header = fgetcsv($file); // تخطي السطر الأول (العناوين)

$count = 0;
while (($data = fgetcsv($file)) !== FALSE) {
    $st_num = $data[0];
    $st_name = $data[1];
    $school = $data[2] ?: null;
    $tel = $data[3] ?: null;
    $mobail = $data[8] ?: null;
    $alsaf = $data[6] ?: null;
    $alhai = $data[7] ?: null;
    
    $stmt = $conn->prepare("INSERT IGNORE INTO students (st_num, st_name, school, tel, mobail, alsaf, alhai) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssissis", $st_num, $st_name, $school, $tel, $mobail, $alsaf, $alhai);
    $stmt->execute();
    $stmt->close();
    $count++;
}
fclose($file);
echo "تم استيراد $count طالب\n\n";

// 2. استيراد أنواع المخالفات
echo "استيراد أنواع المخالفات...\n";
$file = fopen('mokalfat.csv', 'r');
$header = fgetcsv($file);

$count = 0;
while (($data = fgetcsv($file)) !== FALSE) {
    $id_m = $data[0];
    $name1 = $data[1];
    $degree = $data[2] ?: 0;
    
    $stmt = $conn->prepare("INSERT IGNORE INTO behavior_types (id_m, name1, degree) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $id_m, $name1, $degree);
    $stmt->execute();
    $stmt->close();
    $count++;
}
fclose($file);
echo "تم استيراد $count نوع مخالفة\n\n";

// 3. استيراد سجلات الحضور
echo "حذف سجلات الحضور السابقة...\n";
$conn->query("TRUNCATE TABLE attendance");
echo "استيراد سجلات الحضور...\n";
$file = fopen('registr.csv', 'r');
$header = fgetcsv($file);

$count = 0;
while (($data = fgetcsv($file)) !== FALSE) {
    $dateenter = $data[1];
    $time = $data[2] ?: null;
    $st_num = $data[3];
    $enter_status = $data[4] ?: 1;
    
    // تحويل التاريخ إلى صيغة MySQL
    if ($dateenter) {
        $dateenter = date('Y-m-d H:i:s', strtotime($dateenter));
    }
    if ($time) {
        $time = date('Y-m-d H:i:s', strtotime($time));
    }
    
    $stmt = $conn->prepare("INSERT INTO attendance (dateenter, time, st_num, enter_status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $dateenter, $time, $st_num, $enter_status);
    $stmt->execute();
    $stmt->close();
    $count++;
}
fclose($file);
echo "تم استيراد $count سجل حضور\n\n";

// 4. استيراد سجلات السلوك
echo "استيراد سجلات السلوك...\n";
$file = fopen('reg_m.csv', 'r');
$header = fgetcsv($file);

$count = 0;
while (($data = fgetcsv($file)) !== FALSE) {
    $st_num = $data[1];
    $date1 = $data[2];
    $id_m = $data[3];
    $value1 = $data[4] ?: 0;
    
    // تحويل التاريخ إلى صيغة MySQL
    if ($date1) {
        $date1 = date('Y-m-d H:i:s', strtotime($date1));
    }
    
    $stmt = $conn->prepare("INSERT INTO behavior_records (st_num, date1, id_m, value1) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $st_num, $date1, $id_m, $value1);
    $stmt->execute();
    $stmt->close();
    $count++;
}
fclose($file);
echo "تم استيراد $count سجل سلوك\n\n";

$conn->close();

echo "اكتمل الاستيراد بنجاح!\n";
?>
