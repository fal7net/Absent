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

// أكثر الطلاب غياباً
$most_absent = $conn->query("
    SELECT s.st_num, s.st_name, COUNT(*) as absent_count 
    FROM attendance a 
    JOIN students s ON a.st_num = s.st_num 
    WHERE a.enter_status = 2 
    GROUP BY s.st_num 
    ORDER BY absent_count DESC 
    LIMIT 20
");

// أكثر الطلاب مخالفات
$most_violations = $conn->query("
    SELECT s.st_num, s.st_name, COUNT(*) as violation_count, SUM(bt.degree) as total_degree
    FROM behavior_records br 
    JOIN students s ON br.st_num = s.st_num 
    JOIN behavior_types bt ON br.id_m = bt.id_m 
    WHERE bt.degree < 0
    GROUP BY s.st_num 
    ORDER BY violation_count DESC 
    LIMIT 20
");

include 'header.php';
?>

<div class="content-header">
    <h3><i class="fas fa-chart-bar"></i> التقارير والإحصائيات</h3>
</div>

<!-- الإحصائيات العامة -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h2 class="text-primary"><?php echo $total_students; ?></h2>
                <p class="mb-0">إجمالي الطلاب</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h2 class="text-success"><?php echo $total_attendance; ?></h2>
                <p class="mb-0">عدد سجلات الحضور</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h2 class="text-danger"><?php echo $total_behaviors; ?></h2>
                <p class="mb-0">عدد سجلات السلوك</p>
            </div>
        </div>
    </div>
</div>

<!-- أكثر الطلاب غياباً -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-user-times"></i> أكثر الطلاب غياباً</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>الترتيب</th>
                        <th>رقم الهوية</th>
                        <th>اسم الطالب</th>
                        <th>عدد أيام الغياب</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    while ($student = $most_absent->fetch_assoc()): 
                    ?>
                        <tr>
                            <td><?php echo $rank++; ?></td>
                            <td><?php echo htmlspecialchars($student['st_num']); ?></td>
                            <td><?php echo htmlspecialchars($student['st_name']); ?></td>
                            <td><span class="badge bg-danger"><?php echo $student['absent_count']; ?></span></td>
                            <td>
                                <a href="../student_report.php?st_num=<?php echo $student['st_num']; ?>" 
                                   class="btn btn-sm btn-info" target="_blank">
                                    <i class="fas fa-eye"></i> عرض التقرير
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- أكثر الطلاب مخالفات -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-exclamation-triangle"></i> أكثر الطلاب مخالفات</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>الترتيب</th>
                        <th>رقم الهوية</th>
                        <th>اسم الطالب</th>
                        <th>عدد المخالفات</th>
                        <th>مجموع الدرجات</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    while ($student = $most_violations->fetch_assoc()): 
                    ?>
                        <tr>
                            <td><?php echo $rank++; ?></td>
                            <td><?php echo htmlspecialchars($student['st_num']); ?></td>
                            <td><?php echo htmlspecialchars($student['st_name']); ?></td>
                            <td><span class="badge bg-warning"><?php echo $student['violation_count']; ?></span></td>
                            <td><span class="badge bg-danger"><?php echo $student['total_degree']; ?></span></td>
                            <td>
                                <a href="../student_report.php?st_num=<?php echo $student['st_num']; ?>" 
                                   class="btn btn-sm btn-info" target="_blank">
                                    <i class="fas fa-eye"></i> عرض التقرير
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$conn->close();
include 'footer.php';
?>
