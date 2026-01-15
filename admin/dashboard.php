<?php
require_once '../config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$conn = getDBConnection();

// إحصائيات عامة
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_attendance = $conn->query("SELECT COUNT(*) as count FROM attendance")->fetch_assoc()['count'];
$total_behaviors = $conn->query("SELECT COUNT(*) as count FROM behavior_records")->fetch_assoc()['count'];
$total_behavior_types = $conn->query("SELECT COUNT(*) as count FROM behavior_types")->fetch_assoc()['count'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم الرئيسية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: "Tajawal", Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
        }
        .sidebar {
            background: linear-gradient(135deg, #005555 0%, #006666 100%);
            min-height: 100vh;
            color: white;
            padding: 20px;
            position: fixed;
            width: 250px;
            right: 0;
            top: 0;
        }
        .sidebar-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 20px;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
        }
        .sidebar-menu li {
            margin-bottom: 10px;
        }
        .sidebar-menu a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 12px 15px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255,255,255,0.2);
        }
        .main-content {
            margin-right: 270px;
            padding: 30px;
        }
        .top-bar {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .stat-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #006666;
        }
        .stat-label {
            color: #6c757d;
            font-size: 1rem;
        }
        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .action-btn {
            display: block;
            padding: 15px;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #003333 0%, #006666 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s;
        }
        .action-btn:hover {
            transform: translateX(-5px);
            color: white;
        }
    </style>
</head>
<body>
    <!-- الشريط الجانبي -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-graduation-cap"></i> نظام الطلاب</h4>
            <p class="mb-0 small">مرحباً، <?php echo htmlspecialchars($_SESSION['admin_name']); ?></p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> الرئيسية</a></li>
            <li><a href="students.php"><i class="fas fa-users"></i> إدارة الطلاب</a></li>
            <li><a href="attendance.php"><i class="fas fa-calendar-check"></i> إدارة الغياب</a></li>
            <li><a href="behavior.php"><i class="fas fa-user-check"></i> إدارة السلوك</a></li>
            <li><a href="behavior_types.php"><i class="fas fa-list"></i> أنواع المخالفات</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> التقارير</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h3><i class="fas fa-dashboard"></i> لوحة التحكم الرئيسية - القسم المتوسط</h3>
            <div>
                <a href="../index.php" class="btn btn-outline-primary" target="_blank">
                    <i class="fas fa-external-link-alt"></i> عرض الموقع القسم المتوسط
                </a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="color: #006666;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $total_students; ?></div>
                <div class="stat-label">إجمالي عدد الطلاب</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="color: #28a745;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-number"><?php echo $total_attendance; ?></div>
                <div class="stat-label">إجمالي سجلات الحضور</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="color: #dc3545;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-number"><?php echo $total_behaviors; ?></div>
                <div class="stat-label">إجمالي سجلات السلوك</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="color: #ffc107;">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-number"><?php echo $total_behavior_types; ?></div>
                <div class="stat-label">أنواع المخالفات المضافة</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h4 class="mb-4"><i class="fas fa-bolt"></i> إجراءات سريعة</h4>
            <div class="row">
                <div class="col-md-6">
                    <a href="students.php?action=add" class="action-btn">
                        <i class="fas fa-user-plus"></i> إضافة طالب جديد
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="attendance.php?action=add" class="action-btn">
                        <i class="fas fa-calendar-plus"></i> تسجيل حضور - غياب
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="behavior.php?action=add" class="action-btn">
                        <i class="fas fa-plus-circle"></i> تسجيل مخالفة - تميز
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="reports.php" class="action-btn">
                        <i class="fas fa-file-alt"></i> عرض التقارير
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
