<?php
require_once '../config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$conn = getDBConnection();
$message = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_record = null;

// معالجة الحذف
if (isset($_GET['delete'])) {
    $serial = cleanInput($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM attendance WHERE serial = ?");
    $stmt->bind_param("i", $serial);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">تم حذف السجل بنجاح</div>';
    }
    $stmt->close();
}

// معالجة الإضافة
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $st_num = cleanInput($_POST['st_num']);
    $dateenter = cleanInput($_POST['dateenter']);
    $time = cleanInput($_POST['time']);
    $enter_status = (int)cleanInput($_POST['enter_status']);
    $serial = isset($_POST['serial']) && $_POST['serial'] ? (int)cleanInput($_POST['serial']) : null;
    
    if ($serial) {
        // تحديث
        $stmt = $conn->prepare("UPDATE attendance SET st_num=?, dateenter=?, time=?, enter_status=? WHERE serial=?");
        $stmt->bind_param("sssii", $st_num, $dateenter, $time, $enter_status, $serial);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">تم تحديث السجل بنجاح</div>';
            $action = 'list';
        }
    } else {
        // إضافة جديد
        $stmt = $conn->prepare("INSERT INTO attendance (st_num, dateenter, time, enter_status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $st_num, $dateenter, $time, $enter_status);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">تم إضافة السجل بنجاح</div>';
            $action = 'list';
        }
    }
    
    if ($stmt->error) {
        $message = '<div class="alert alert-danger">خطأ: ' . $stmt->error . '</div>';
    }
    $stmt->close();
}

// جلب قائمة السجلات
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
if ($search) {
    $stmt = $conn->prepare("
        SELECT a.*, s.st_name 
        FROM attendance a 
        LEFT JOIN students s ON a.st_num = s.st_num 
        WHERE a.st_num LIKE ? OR s.st_name LIKE ?
        ORDER BY a.dateenter DESC 
        LIMIT 100
    ");
    $search_param = "%$search%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $records_result = $stmt->get_result();
    $stmt->close();
} else {
    $records_result = $conn->query("
        SELECT a.*, s.st_name 
        FROM attendance a 
        LEFT JOIN students s ON a.st_num = s.st_num 
        ORDER BY a.dateenter DESC 
        LIMIT 100
    ");
}

	// جلب بيانات السجل للتحرير
	if ($action == 'edit' && isset($_GET['serial'])) {
	    $serial = (int)cleanInput($_GET['serial']);
	    $stmt = $conn->prepare("SELECT * FROM attendance WHERE serial = ?");
	    $stmt->bind_param("i", $serial);
	    $stmt->execute();
	    
	    // استخدام bind_result كبديل لـ get_result()
	    $stmt->bind_result($serial_res, $st_num_res, $dateenter_res, $time_res, $enter_status_res, $created_at_res, $updated_at_res);
	    $stmt->fetch();
	    $stmt->close();
	    
	    if ($serial_res) {
	        $edit_record = [
	            'serial' => $serial_res,
	            'st_num' => $st_num_res,
	            'dateenter' => $dateenter_res,
	            'time' => $time_res,
	            'enter_status' => $enter_status_res,
	            'created_at_res' => $created_at_res,
	            'updated_at_res' => $updated_at_res
	        ];
	    }
	}
	
	// جلب قائمة الطلاب للقائمة المنسدلة
	$students_result = $conn->query("SELECT st_num, st_name FROM students ORDER BY st_name");
	
	include 'header.php';
?>

<div class="content-header">
    <h3><i class="fas fa-calendar-check"></i> إدارة الحضور والغياب</h3>
</div>

	<?php echo $message; ?>
	
	<?php if ($action == 'add' || $action == 'edit'): ?>
	    <!-- نموذج الإضافة/التحرير -->
	    <div class="card">
	        <div class="card-header">
	            <h5><?php echo $action == 'edit' ? 'تحرير سجل الحضور/الغياب' : 'تسجيل حضور/غياب جديد'; ?></h5>
	        </div>
	        <div class="card-body">
	            <form method="POST" action="">
	                <?php if ($action == 'edit'): ?>
	                    <input type="hidden" name="serial" value="<?php echo isset($edit_record['serial']) ? $edit_record['serial'] : ''; ?>">
	                <?php endif; ?>
	                <div class="row">
	                    <div class="col-md-6 mb-3">
	                        <label class="form-label">الطالب *</label>
	                        <select name="st_num" class="form-control select2" id="student_select" required>
	                            <option value="">ابحث عن الطالب برقم الهوية أو الاسم...</option>
	                            <?php 
	                            $students_result->data_seek(0);
	                            while ($student = $students_result->fetch_assoc()): 
	                            ?>
	                                <option value="<?php echo $student['st_num']; ?>"
	                                    <?php echo ($edit_record && $edit_record['st_num'] == $student['st_num']) ? 'selected' : ''; ?>>
	                                    <?php echo htmlspecialchars($student['st_name']) . ' - ' . $student['st_num']; ?>
	                                </option>
	                            <?php endwhile; ?>
	                        </select>
	                    </div>
                    
	                    <div class="col-md-6 mb-3">
	                        <label class="form-label">التاريخ *</label>
	                        <input type="date" name="dateenter" class="form-control" 
	                               value="<?php echo $edit_record ? date('Y-m-d', strtotime($edit_record['dateenter'])) : date('Y-m-d'); ?>" required>
	                    </div>
                    
	                    <div class="col-md-6 mb-3">
	                        <label class="form-label">الوقت</label>
	                        <input type="time" name="time" class="form-control" 
	                               value="<?php echo $edit_record ? date('H:i', strtotime($edit_record['time'])) : date('H:i'); ?>">
	                    </div>
                    
	                    <div class="col-md-6 mb-3">
	                        <label class="form-label">الحالة *</label>
	                        <select name="enter_status" class="form-control" required>
	                            <option value="1" <?php echo ($edit_record && $edit_record['enter_status'] == 1) ? 'selected' : ''; ?>>حاضر</option>
	                            <option value="2" <?php echo ($edit_record && $edit_record['enter_status'] == 2) ? 'selected' : ''; ?>>غائب</option>
	                            <option value="3" <?php echo ($edit_record && $edit_record['enter_status'] == 3) ? 'selected' : ''; ?>>متأخر</option>
	                        </select>
	                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ
                    </button>
                    <a href="attendance.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- قائمة السجلات -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>سجلات الحضور والغياب</h5>
	            <a href="attendance.php?action=add" class="btn btn-primary">
	                <i class="fas fa-plus"></i> إضافة سجل
	            </a>
        </div>
        <div class="card-body">
            <!-- بحث -->
            <form method="GET" class="mb-3">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" 
                           placeholder="بحث برقم الهوية أو الاسم..."
                           value="<?php echo $search; ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> بحث
                    </button>
                    <?php if ($search): ?>
                        <a href="attendance.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> إلغاء
                        </a>
                    <?php endif; ?>
                </div>
            </form>
            
            <!-- الجدول -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>الوقت</th>
                            <th>رقم الهوية</th>
                            <th>اسم الطالب</th>
                            <th>الحالة</th>
	                            <th>الإجراءات</th>
	                        </tr>
	                    </thead>
	                    <tbody>
							<?php 
							// إصلاح التوافق مع الخادم
							$records = [];
							if ($records_result) {
								while ($row = $records_result->fetch_assoc()) {
									$records[] = $row;
								}
							}
							foreach ($records as $record):
							?>
							<tr>
								<td><?php echo date('Y-m-d', strtotime($record['dateenter'])); ?></td>
								<td><?php echo $record['time'] ? date('H:i', strtotime($record['time'])) : '-'; ?></td>
								<td><?php echo htmlspecialchars($record['st_num']); ?></td>
								<td><?php echo htmlspecialchars($record['st_name'] ?? 'غير معروف'); ?></td>
								<td>
									<?php
									$status = $record['enter_status'];
									if ($status == 1) {
										echo '<span class="badge bg-success">حاضر</span>';
									} elseif ($status == 2) {
										echo '<span class="badge bg-danger">غائب</span>';
									} elseif ($status == 3) {
										echo '<span class="badge bg-warning">متأخر</span>';
									} else {
										echo '<span class="badge bg-secondary">غير محدد</span>';
									}
									?>
									</td>
									<td>
										<a href="attendance.php?action=edit&serial=<?php echo $record['serial']; ?>" 
										   class="btn btn-sm btn-warning">
											<i class="fas fa-edit"></i> تحرير
										</a>
										<a href="attendance.php?delete=<?php echo $record['serial']; ?>" 
										   class="btn btn-sm btn-danger"
										   onclick="return confirm('هل أنت متأكد من حذف هذا السجل؟')">
											<i class="fas fa-trash"></i> حذف
										</a>
									</td>
							</tr>
					 <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

	<?php
	$conn->close();
	include 'footer.php';
	?>
	
	<!-- تضمين مكتبة Select2 للبحث المتقدم -->
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	
	<script>
	    $(document).ready(function() {
	        $('#student_select').select2({
	            placeholder: "ابحث عن الطالب برقم الهوية أو الاسم...",
	            allowClear: true,
	            width: '100%'
	        });
	    });
	</script>
