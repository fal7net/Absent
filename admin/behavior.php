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
    $id = cleanInput($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM behavior_records WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">تم حذف السجل بنجاح</div>';
    }
    $stmt->close();
}

// معالجة الإضافة
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $st_num = cleanInput($_POST['st_num']);
    $date1 = cleanInput($_POST['date1']);
    $id_m = cleanInput($_POST['id_m']);
    
    // جلب قيمة الدرجة من جدول behavior_types
    $stmt = $conn->prepare("SELECT degree FROM behavior_types WHERE id_m = ?");
    $stmt->bind_param("i", $id_m);
    $stmt->execute();
    $result = $stmt->get_result();
    $behavior_type = $result->fetch_assoc();
    $value1 = $behavior_type['degree'];
    $stmt->close();
    
    $stmt = $conn->prepare("INSERT INTO behavior_records (st_num, date1, id_m, value1) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $st_num, $date1, $id_m, $value1);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">تم إضافة السجل بنجاح</div>';
        $action = 'list';
    } else {
        $message = '<div class="alert alert-danger">خطأ: ' . $stmt->error . '</div>';
    }
    $stmt->close();
}

// جلب قائمة السجلات
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
if ($search) {
    $stmt = $conn->prepare("
        SELECT br.*, s.st_name, bt.name1 as behavior_name, bt.degree 
        FROM behavior_records br 
        LEFT JOIN students s ON br.st_num = s.st_num 
        LEFT JOIN behavior_types bt ON br.id_m = bt.id_m 
        WHERE br.st_num LIKE ? OR s.st_name LIKE ?
        ORDER BY br.date1 DESC 
        LIMIT 100
    ");
    $search_param = "%$search%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $records_result = $stmt->get_result();
    $stmt->close();
} else {
    $records_result = $conn->query("
        SELECT br.*, s.st_name, bt.name1 as behavior_name, bt.degree 
        FROM behavior_records br 
        LEFT JOIN students s ON br.st_num = s.st_num 
        LEFT JOIN behavior_types bt ON br.id_m = bt.id_m 
        ORDER BY br.date1 DESC 
        LIMIT 100
    ");
}
	// جلب بيانات السجل للتحرير
	if ($action == 'edit' && isset($_GET['id'])) {
	    $id = cleanInput($_GET['id']);
	    $stmt = $conn->prepare("SELECT * FROM behavior_records WHERE id = ?");
	    $stmt->bind_param("i", $id);
	    $stmt->execute();
	    
	    // استخدام bind_result كبديل لـ get_result()
	    $stmt->bind_result($id_res, $st_num_res, $date1_res, $id_m_res, $value1_res, $created_at_res, $updated_at_res);
	    $stmt->fetch();
	    $stmt->close();
	    
	    if ($id_res) {
	        $edit_record = [
	            'id' => $id_res,
	            'st_num' => $st_num_res,
	            'date1' => $date1_res,
	            'id_m' => $id_m_res,
	            'value1' => $value1_res
	        ];
	    }
	}

// جلب قائمة الطلاب
$students_result = $conn->query("SELECT st_num, st_name FROM students ORDER BY st_name");

// جلب قائمة أنواع المخالفات
$behavior_types_result = $conn->query("SELECT * FROM behavior_types ORDER BY degree, name1");

include 'header.php';
?>

<div class="content-header">
    <h3><i class="fas fa-user-check"></i> إدارة السلوك والمخالفات</h3>
</div>

	<?php echo $message; ?>
	
	<?php if ($action == 'add' || $action == 'edit'): ?>
	    <!-- نموذج الإضافة/التحرير -->
	    <div class="card">
	        <div class="card-header">
	            <h5><?php echo $action == 'edit' ? 'تحرير سجل السلوك' : 'تسجيل مخالفة/حسنة جديدة'; ?></h5>
	        </div>
        <div class="card-body">
	            <form method="POST" action="">
	                <?php if ($action == 'edit'): ?>
	                    <input type="hidden" name="id" value="<?php echo $edit_record['id']; ?>">
	                <?php endif; ?>
	                <div class="row">
	                    <div class="col-md-6 mb-3">
	                        <label class="form-label">الطالب *</label>
	                        <select name="st_num" class="form-control" required>
	                            <option value="">اختر الطالب...</option>
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
	                        <input type="date" name="date1" class="form-control" 
	                               value="<?php echo $edit_record ? date('Y-m-d', strtotime($edit_record['date1'])) : date('Y-m-d'); ?>" required>
	                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label class="form-label">نوع المخالفة السلوكية - التميز السلوكي *</label>
	                        <select name="id_m" class="form-control" required>
	                            <option value="">اختر النوع...</option>
	                            <?php 
	                            $behavior_types_result->data_seek(0); // إعادة المؤشر للبداية
	                            while ($type = $behavior_types_result->fetch_assoc()): 
	                                $badge_class = $type['degree'] > 0 ? 'success' : ($type['degree'] < 0 ? 'danger' : 'secondary');
	                            ?>
	                                <option value="<?php echo $type['id_m']; ?>"
	                                    <?php echo ($edit_record && $edit_record['id_m'] == $type['id_m']) ? 'selected' : ''; ?>>
	                                    [<?php echo $type['degree']; ?>] <?php echo htmlspecialchars($type['name1']); ?>
	                                </option>
	                            <?php endwhile; ?>
	                        </select>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ
                    </button>
                    <a href="behavior.php" class="btn btn-secondary">
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
            <h5>سجلات السلوك والمخالفات</h5>
            <a href="behavior.php?action=add" class="btn btn-light">
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
                        <a href="behavior.php" class="btn btn-secondary">
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
                            <th>رقم الهوية</th>
                            <th>اسم الطالب</th>
                            <th>نوع المخالفة السلوكية - التميز السلوكي</th>
                            <th>الدرجة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($record = $records_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $record['date1'] ? date('Y-m-d', strtotime($record['date1'])) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($record['st_num']); ?></td>
                                <td><?php echo htmlspecialchars($record['st_name'] ?? 'غير معروف'); ?></td>
                                <td style="max-width: 300px;">
                                    <?php echo htmlspecialchars($record['behavior_name'] ?? 'غير محدد'); ?>
                                </td>
                                <td>
                                    <?php
                                    $degree = $record['degree'] ?? 0;
                                    if ($degree > 0) {
                                        echo '<span class="badge bg-success">+' . $degree . '</span>';
                                    } elseif ($degree < 0) {
                                        echo '<span class="badge bg-danger">' . $degree . '</span>';
                                    } else {
                                        echo '<span class="badge bg-secondary">0</span>';
                                    }
                                    ?>
                                </td>
                                <td>
	                                    <a href="behavior.php?action=edit&id=<?php echo $record['id']; ?>" 
	                                       class="btn btn-sm btn-warning">
	                                        <i class="fas fa-edit"></i> تحرير
	                                    </a>
	                                    <a href="behavior.php?delete=<?php echo $record['id']; ?>" 
	                                       class="btn btn-sm btn-danger"
	                                       onclick="return confirm('هل أنت متأكد من حذف هذا السجل؟')">
	                                        <i class="fas fa-trash"></i> حذف
	                                    </a>
	                                </td>
                                </td>
                            </tr>
                        <?php endwhile; ?>
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
