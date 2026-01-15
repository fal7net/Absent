
<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

$conn = getDBConnection();

// تحديد نطاق التاريخ
$start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');


// Validate date format (YYYY-MM-DD)
function is_valid_date($date) {
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
}

if (!is_valid_date($start_date) || !is_valid_date($end_date)) {
    die('<div style="color:red">Invalid date format.</div>');
}

$date_condition = "WHERE dateenter BETWEEN '$start_date' AND '$end_date'";
$behavior_date_condition = "WHERE br.created_at BETWEEN '$start_date' AND '$end_date'";

// جلب البيانات للإحصائيات
$stats = [];

// إجمالي الطلاب
$stats['total_students'] = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];

// إجمالي سجلات الحضور
$stats['total_attendance'] = $conn->query("SELECT COUNT(*) as count FROM attendance $date_condition")->fetch_assoc()['count'];

// إجمالي المخالفات
$stats['total_behaviors'] = $conn->query("SELECT COUNT(*) as count FROM behavior_records br $behavior_date_condition")->fetch_assoc()['count'];

// إحصائيات الحضور حسب الحالة
$attendance_by_status = $conn->query("
    SELECT 
        enter_status,
        COUNT(*) as count
    FROM attendance
    $date_condition
    GROUP BY enter_status
");

$attendance_data = ['حاضر' => 0, 'غائب' => 0, 'متأخر' => 0];
while ($row = $attendance_by_status->fetch_assoc()) {
    if ($row['enter_status'] == 1) $attendance_data['حاضر'] = $row['count'];
    elseif ($row['enter_status'] == 2) $attendance_data['غائب'] = $row['count'];
    elseif ($row['enter_status'] == 3) $attendance_data['متأخر'] = $row['count'];
}

// أكثر المخالفات شيوعاً
$top_violations = $conn->query("
    SELECT bt.name1, COUNT(*) as count
    FROM behavior_records br
    JOIN behavior_types bt ON br.id_m = bt.id_m
    $behavior_date_condition AND bt.degree < 0
    GROUP BY bt.id_m
    ORDER BY count DESC
    LIMIT 10
");

$violations_data = [];
while ($row = $top_violations->fetch_assoc()) {
    $violations_data[] = [
        'name' => $row['name1'],
        'count' => $row['count']
    ];
}

// الحضور حسب التاريخ
$attendance_by_date = $conn->query("
    SELECT 
        DATE(dateenter) as date,
        SUM(CASE WHEN enter_status = 1 THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN enter_status = 2 THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN enter_status = 3 THEN 1 ELSE 0 END) as late
    FROM attendance
    $date_condition
    GROUP BY DATE(dateenter)
    ORDER BY date
");

$timeline_data = [];
while ($row = $attendance_by_date->fetch_assoc()) {
    $timeline_data[] = $row;
}

// توزيع الطلاب حسب الصف
$students_by_class = $conn->query("
    SELECT alsaf, COUNT(*) as count
    FROM students
    WHERE alsaf IS NOT NULL
    GROUP BY alsaf
    ORDER BY alsaf
");

$class_data = [];
while ($row = $students_by_class->fetch_assoc()) {
    $class_data[] = [
        'class' => $row['alsaf'],
        'count' => $row['count']
    ];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحليلات التفاعلية - نظام الطلاب</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
        .container-fluid {
            max-width: 1400px;
        }
        .header-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }
        .header-title {
            color: #006666;
            font-weight: bold;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: all 0.3s;
            animation: fadeInUp 0.6s ease-out;
        }
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .stat-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #6c757d;
            font-size: 1.1rem;
            margin-top: 10px;
        }
        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .chart-title {
            color: #006666;
            font-weight: bold;
            font-size: 1.5rem;
            margin-bottom: 20px;
            text-align: center;
        }
        .back-btn {
            position: fixed;
            bottom: 30px;
            left: 30px;
            background: white;
            color: #006666;
            border: none;
            border-radius: 50px;
            padding: 15px 30px;
            font-weight: bold;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            transition: all 0.3s;
            z-index: 1000;
        }
        .back-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 30px rgba(0,0,0,0.4);
        }
        canvas {
            max-height: 400px;
        }
        .date-filter-form {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="header-section">
            <h1 class="header-title">
                <i class="fas fa-chart-line"></i>
                لوحة التحليلات التفاعلية
            </h1>
            <p class="text-muted">استكشف البيانات بطريقة أكثر بديهية وفهم الاتجاهات بشكل أفضل</p>
        </div>

        <!-- Date Filter Form -->
        <div class="date-filter-form">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <label for="start_date" class="form-label">من تاريخ:</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-5">
                    <label for="end_date" class="form-label">إلى تاريخ:</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> تصفية</button>
                </div>
            </form>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="color: #667eea;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_students']; ?></div>
                <div class="stat-label">إجمالي الطلاب</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="color: #28a745;">
                    <i class="fa-solid fa-bridge-circle-check"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_attendance']; ?></div>
                <div class="stat-label">عدد الحضور</div>
            </div>
                        <div class="stat-card">
                <div class="stat-icon" style="color: #dc3545;">
                    <i class="fa-solid fa-bridge-circle-xmark"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_students']-$stats['total_attendance']; ?></div>
                <div class="stat-label">عدد الغياب</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="color: #dc3545;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_behaviors']; ?></div>
                <div class="stat-label">عدد المخالفات</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="color: #ffc107;">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-number">
                    <?php 
                    $total = $attendance_data['حاضر'] + $attendance_data['غائب'] + $attendance_data['متأخر'];
                    $percentage = $total > 0 ? round(($attendance_data['حاضر'] / $total) * 100, 1) : 0;
                    echo $percentage . '%';
                    ?>
                </div>
                <div class="stat-label">نسبة الحضور</div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row">
            <div class="col-lg-6">
                <div class="chart-card">
                    <h3 class="chart-title">
                        <i class="fas fa-pie-chart"></i>
                        توزيع حالات الحضور
                    </h3>
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="chart-card">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-bar"></i>
                        توزيع الطلاب حسب الصف
                    </h3>
                    <canvas id="classChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row">
            <div class="col-lg-12">
                <div class="chart-card">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-line"></i>
                        اتجاه الحضور خلال آخر 30 يوم
                    </h3>
                    <canvas id="timelineChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Row 3 -->
        <div class="row">
            <div class="col-lg-12">
                <div class="chart-card">
                    <h3 class="chart-title">
                        <i class="fas fa-exclamation-circle"></i>
                        أكثر المخالفات شيوعاً
                    </h3>
                    <canvas id="violationsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <a href="index.php" class="back-btn">
        <i class="fas fa-arrow-right"></i> العودة للرئيسية
    </a>

    <script>
        // إعداد Chart.js للغة العربية
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";

        // 1. مخطط دائري للحضور
        const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(attendanceCtx, {
            type: 'doughnut',
            data: {
                labels: ['حاضر', 'غائب', 'متأخر'],
                datasets: [{
                    data: [
                        <?php echo $attendance_data['حاضر']; ?>,
                        <?php echo $attendance_data['غائب']; ?>,
                        <?php echo $attendance_data['متأخر']; ?>
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#dc3545',
                        '#ffc107'
                    ],
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 14
                            },
                            padding: 20
                        }
                    }
                }
            }
        });

        // 2. مخطط أعمدة للصفوف
        const classCtx = document.getElementById('classChart').getContext('2d');
        new Chart(classCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return '"الصف ' . $item['class'] . '"'; }, $class_data)); ?>],
                datasets: [{
                    label: 'عدد الطلاب',
                    data: [<?php echo implode(',', array_column($class_data, 'count')); ?>],
                    backgroundColor: 'rgba(0, 89, 128, 1)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // 3. مخطط خطي للحضور عبر الزمن
        const timelineCtx = document.getElementById('timelineChart').getContext('2d');
        new Chart(timelineCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return '"' . $item['date'] . '"'; }, $timeline_data)); ?>],
                datasets: [
                    {
                        label: 'حاضر',
                        data: [<?php echo implode(',', array_column($timeline_data, 'present')); ?>],
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'غائب',
                        data: [<?php echo implode(',', array_column($timeline_data, 'absent')); ?>],
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'متأخر',
                        data: [<?php echo implode(',', array_column($timeline_data, 'late')); ?>],
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // 4. مخطط أفقي للمخالفات
        const violationsCtx = document.getElementById('violationsChart').getContext('2d');
        new Chart(violationsCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return '"' . addslashes(substr($item['name'], 0, 50)) . '..."'; }, $violations_data)); ?>],
                datasets: [{
                    label: 'عدد المخالفات',
                    data: [<?php echo implode(',', array_column($violations_data, 'count')); ?>],
                    backgroundColor: 'rgba(220, 53, 69, 0.8)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
