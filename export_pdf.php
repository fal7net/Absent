<?php
require_once 'config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$st_num = isset($_GET['st_num']) ? cleanInput($_GET['st_num']) : '';
$student = null;
$attendance_records = [];
$behavior_records = [];

if ($st_num) {
    $conn = getDBConnection();
    
    // جلب بيانات الطالب
    $stmt = $conn->prepare("SELECT * FROM students WHERE st_num = ?");
    $stmt->bind_param("s", $st_num);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    
    if ($student) {
        // جلب سجل الحضور والغياب
        $stmt = $conn->prepare("SELECT * FROM attendance WHERE st_num = ? ORDER BY dateenter DESC LIMIT 50");
        $stmt->bind_param("s", $st_num);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $attendance_records[] = $row;
        }
        $stmt->close();
        
        // جلب سجل السلوك
        $stmt = $conn->prepare("
            SELECT br.*, bt.name1 as behavior_name, bt.degree 
            FROM behavior_records br 
            LEFT JOIN behavior_types bt ON br.id_m = bt.id_m 
            WHERE br.st_num = ? 
            ORDER BY br.date1 DESC 
            LIMIT 50
        ");
        $stmt->bind_param("s", $st_num);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $behavior_records[] = $row;
        }
        $stmt->close();
    }
    
    $conn->close();
}

if (!$student) {
    die('لم يتم العثور على الطالب');
}

// حساب الإحصائيات
$total_degree = 0;
foreach ($behavior_records as $record) {
    $total_degree += $record['degree'] ?? 0;
}

// إنشاء محتوى HTML
$html = '
<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            direction: rtl;
            text-align: right;
            /* زيادة حجم الخط قليلاً لتحسين القراءة في PDF */
            font-size: 10pt; 
        }
        .header {
            background: #667eea;
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .student-info {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .info-row {
            margin-bottom: 10px;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background: #667eea;
            color: white;
            padding: 10px;
            text-align: center;
        }
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .section-title {
            color: #667eea;
            font-weight: bold;
            margin: 20px 0 10px 0;
            font-size: 18px;
        }
        .badge-present {
            background: #d4edda;
            color: #155724;
            padding: 3px 10px;
            border-radius: 3px;
        }
        .badge-absent {
            background: #f8d7da;
            color: #721c24;
            padding: 3px 10px;
            border-radius: 3px;
        }
        .badge-late {
            background: #fff3cd;
            color: #856404;
            padding: 3px 10px;
            border-radius: 3px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>تقرير الطالب</h1>
        <p>نظام متابعة الغياب والسلوك</p>
    </div>
    
    <div class="student-info">
        <h2>بيانات الطالب</h2>
        <div class="info-row">
            <span class="label">الاسم الكامل:</span>
            <span>' . htmlspecialchars($student['st_name']) . '</span>
        </div>
        <div class="info-row">
            <span class="label">رقم الهوية:</span>
            <span>' . htmlspecialchars($student['st_num']) . '</span>
        </div>
        <div class="info-row">
            <span class="label">الصف:</span>
            <span>' . ($student['alsaf'] ? $student['alsaf'] : 'غير محدد') . '</span>
        </div>
        <div class="info-row">
            <span class="label">رقم الجوال:</span>
            <span>' . ($student['mobail'] ? htmlspecialchars($student['mobail']) : 'غير متوفر') . '</span>
        </div>
        <div class="info-row">
            <span class="label">إجمالي سجلات الحضور:</span>
            <span>' . count($attendance_records) . '</span>
        </div>
        <div class="info-row">
            <span class="label">إجمالي سجلات السلوك:</span>
            <span>' . count($behavior_records) . '</span>
        </div>
        <div class="info-row">
            <span class="label">مجموع درجات السلوك:</span>
            <span>' . $total_degree . '</span>
        </div>
    </div>
    
    <h3 class="section-title">سجل الحضور والغياب</h3>
    <table>
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>الوقت</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>';

if (count($attendance_records) > 0) {
    foreach ($attendance_records as $record) {
        $status = $record['enter_status'];
        $status_text = '';
        if ($status == 1) {
            $status_text = '<span class="badge-present">حاضر</span>';
        } elseif ($status == 2) {
            $status_text = '<span class="badge-absent">غائب</span>';
        } elseif ($status == 3) {
            $status_text = '<span class="badge-late">متأخر</span>';
        } else {
            $status_text = 'غير محدد';
        }
        
        $html .= '<tr>
            <td>' . date('Y-m-d', strtotime($record['dateenter'])) . '</td>
            <td>' . ($record['time'] ? date('H:i', strtotime($record['time'])) : '-') . '</td>
            <td>' . $status_text . '</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="3">لا توجد سجلات</td></tr>';
}

$html .= '</tbody>
    </table>
    
    <h3 class="section-title">سجل السلوك والمخالفات</h3>
    <table>
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>نوع المخالفة / الحسنة</th>
                <th>الدرجة</th>
            </tr>
        </thead>
        <tbody>';

if (count($behavior_records) > 0) {
    foreach ($behavior_records as $record) {
        $html .= '<tr>
            <td>' . ($record['date1'] ? date('Y-m-d', strtotime($record['date1'])) : '-') . '</td>
            <td style="text-align: right; padding-right: 10px;">' . htmlspecialchars($record['behavior_name'] ?? 'غير محدد') . '</td>
            <td>' . ($record['degree'] ?? 0) . '</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="3">لا توجد سجلات</td></tr>';
}

$html .= '</tbody>
    </table>
    
    <div class="footer">
        <p>تم إنشاء هذا التقرير في: ' . date('Y-m-d H:i:s') . '</p>
        <p>نظام متابعة الغياب والسلوك</p>
    </div>
</body>
</html>';

// إنشاء PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// تنزيل الملف
$filename = 'student_report_' . $st_num . '_' . date('Y-m-d') . '.pdf';
$dompdf->stream($filename, array('Attachment' => 1));
?>
