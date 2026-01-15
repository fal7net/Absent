<?php
require_once 'config.php';

$st_num = isset($_GET['st_num']) ? cleanInput($_GET['st_num']) : '';
$student = null;
$attendance_records = [];
$behavior_records = [];
$error = '';

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
        
        // جلب سجل السلوك مع تفاصيل المخالفات
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
    } else {
        $error = "لم يتم العثور على طالب بهذا الرقم";
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الطالب - <?php echo $student ? $student['st_name'] : 'غير موجود'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: "Tajawal", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #FFFFFF 0%, #FFFFFF 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .report-container {
            max-width: 1200px;
            margin: 20px auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
        }
        .student-header {
            background: linear-gradient(135deg, #006666 0%, #006666 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .student-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .info-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
        }
        .info-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .section-title {
            color: #006666;
            font-weight: bold;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 3px solid #005555;
        }
        .table-container {
            overflow-x: auto;
            margin-bottom: 30px;
        }
        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .custom-table thead {
            background: linear-gradient(135deg, #005555 0%, #006666 100%);
            color: white;
        }
        .custom-table th {
            padding: 15px;
            text-align: center;
            font-weight: bold;
        }
        .custom-table td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
        }
        .custom-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        .status-present {
            background-color: #d4edda;
            color: #155724;
        }
        .status-absent {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-late {
            background-color: #fff3cd;
            color: #856404;
        }
        .behavior-positive {
            background-color: #d4edda;
            color: #155724;
        }
        .behavior-negative {
            background-color: #f8d7da;
            color: #721c24;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        .btn-custom {
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: bold;
            border: none;
            transition: all 0.3s;
        }
        .btn-print {
            background: linear-gradient(135deg, #005555 0%, #006666 100%);
            color: white;
        }
        .btn-print:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-back {
            background: #6c757d;
            color: white;
        }
        .btn-back:hover {
            background: #FF0000;
            transform: scale(1.05);
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-size: 1.1rem;
        }
        .statistics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            border: 1px solid #006666;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #ff0000;
        }
        .stat-label {
            font-size: 14px;
            font-weight: bold;
            color: #006666;
            margin-top: 10px;
        }
        @media print {
            .action-buttons, .btn-back, .no-print {
                display: none !important;
            }
            body {
                background: white;
            }
            .report-container {
                box-shadow: none;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <?php if ($error): ?>
            <div class="alert alert-danger text-center">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error; ?>
            </div>
            <div class="text-center">
                <a href="index.php" class="btn btn-custom btn-back">
                    <i class="fas fa-arrow-right"></i> العودة للبحث
                </a>
            </div>
        <?php elseif ($student): ?>
            <!-- عرض معلومات الطالب -->
            <div class="student-header">
                <h2><i class="fas fa-user-graduate"></i> بيانات الطالب</h2>
                <div class="student-info">
                    <div class="info-item">
                        <div class="info-label">اسم الطالب :</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['st_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">رقم الهوية :</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['st_num']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">الصف :</div>
                        <div class="info-value">
                                     <?php 
                                        if ($student['alsaf'] == '725') {
                                            echo 'الأول المتوسط';
                                        } elseif ($student['alsaf'] == '825') {
                                            echo 'الثاني المتوسط';
                                        } elseif ($student['alsaf'] == '925') {
                                            echo 'الثالث المتوسط';
                                        } else {
                                            echo '-';
                                        }
                                    ?>  
                                 -    <?php echo $student['school'] ?? '-'; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">رقم الجوال :</div>
                        <div class="info-value"><?php echo $student['mobail'] ? htmlspecialchars($student['mobail']) : 'غير متوفر'; ?></div>
                    </div>
                </div>
            </div>

            <!-- الإحصائيات -->
            <?php
            $total_attendance = count($attendance_records);
            $present_count = 0;
            $absent_count = 0;
            $late_count = 0;
            foreach ($attendance_records as $record) {
                if ($record['enter_status'] == 1) $present_count++;
                if ($record['enter_status'] == 2) $absent_count++;
                if ($record['enter_status'] == 3) $late_count++;
            }
            
            $total_behavior = count($behavior_records);
            $positive_behavior = 0;
            $negative_behavior = 0;
            $total_degree = 0;
            foreach ($behavior_records as $record) {
                $degree = $record['degree'] ?? 0;
                $total_degree += $degree;
                if ($degree > 0) $positive_behavior++;
                if ($degree < 0) $negative_behavior++;
            }
            ?>
            
            <h3 class="section-title"><i class="fas fa-chart-pie"></i> إحصائيات الحضور والغياب</h3>
            <div class="statistics">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_attendance; ?></div>
                    <div class="stat-label">إجمالي التسجيلات</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $present_count; ?></div>
                    <div class="stat-label">إحصائية الحضور</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $absent_count; ?></div>
                    <div class="stat-label">إحصائية الغياب</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $late_count; ?></div>
                    <div class="stat-label">إحصائية التأخر</div>
                </div>
            </div>


            <!-- سجل الحضور والغياب -->
            <h3 class="section-title">
                <i class="fas fa-calendar-check"></i> كشف الحضور والغياب
            </h3>
            <div class="table-container">
                <?php if (count($attendance_records) > 0): ?>
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>الوقت</th>
                                <th>الحالة</th>
                                <th>ملاحظات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance_records as $record): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d', strtotime($record['dateenter'])); ?></td>
                                    <td><?php echo $record['time'] ? date('H:i', strtotime($record['time'])) : '-'; ?></td>
                                    <td>
                                        <?php
                                        $status = $record['enter_status'];
                                        if ($status == 1) {
                                            echo '<span class="status-badge status-present">حاضر</span>';
                                        } elseif ($status == 2) {
                                            echo '<span class="status-badge status-absent">غائب</span>';
                                        } elseif ($status == 3) {
                                            echo '<span class="status-badge status-late">متأخر</span>';
                                        } else {
                                            echo '<span class="status-badge">غير محدد</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $record['do_action'] ? 'تم اتخاذ إجراء' : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>لا توجد سجلات حضور لهذا الطالب</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- احصائيات السلوك -->          
            <h3 class="section-title"><i class="fas fa-chart-line"></i> إحصائيات السلوك والمخالفات</h3>
            <div class="statistics">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_behavior; ?></div>
                    <div class="stat-label">إجمالي سجلات السلوك</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $positive_behavior; ?></div>
                    <div class="stat-label">إحصائيات السلوك المتميز</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $negative_behavior; ?></div>
                    <div class="stat-label">إحصائيات المخالفات</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_degree; ?></div>
                    <div class="stat-label">مجموع درجات السلوك</div>
                </div>
            </div>

            <!-- سجل السلوك -->
            <h3 class="section-title">
                <i class="fas fa-user-check"></i> كشف السلوك ( المخالف - المتميز )
            </h3>
            <div class="table-container">
                <?php if (count($behavior_records) > 0): ?>
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>بيان السلوك المخالف - المتميز</th>
                                <th>الدرجة</th>
                                <th>النوع</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($behavior_records as $record): ?>
                                <tr>
                                    <td><?php echo $record['date1'] ? date('Y-m-d', strtotime($record['date1'])) : '-'; ?></td>
                                    <td style="text-align: right; padding-right: 20px;">
                                        <?php echo htmlspecialchars($record['behavior_name'] ?? 'غير محدد'); ?>
                                    </td>
                                    <td>
                                        <strong><?php echo $record['degree'] ?? 0; ?></strong>
                                    </td>
                                    <td>
                                        <?php
                                        $degree = $record['degree'] ?? 0;
                                        if ($degree > 0) {
                                            echo '<span class="status-badge behavior-positive">متميز</span>';
                                        } elseif ($degree < 0) {
                                            echo '<span class="status-badge behavior-negative">مخالف</span>';
                                        } else {
                                            echo '<span class="status-badge">محايد</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>لا توجد سجلات سلوك لهذا الطالب</p>
                    </div>
                <?php endif; ?>
            </div>

            
            <!-- أزرار الإجراءات -->
            <div class="action-buttons no-print">
                <button onclick="window.print()" class="btn btn-custom btn-print">
                    <i class="fas fa-print"></i> طباعة التقرير
                </button>
                <a href="export_pdf.php?st_num=<?php echo $student['st_num']; ?>" class="btn btn-custom btn-print">
                    <i class="fas fa-file-pdf"></i> تصدير PDF
                </a>
                <a href="index.php" class="btn btn-custom btn-back">
                    <i class="fas fa-arrow-right"></i> العودة للبحث
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
