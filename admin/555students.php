<?php
require_once '../config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$conn = getDBConnection();
$message = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_student = null;

// معالجة الحذف
if (isset($_GET['delete'])) {
    $st_num = cleanInput($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM students WHERE st_num = ?");
    $stmt->bind_param("s", $st_num);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">تم حذف الطالب بنجاح</div>';
    }
    $stmt->close();
}

// معالجة الإضافة والتحرير
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $st_num = cleanInput($_POST['st_num']);
        $st_name = cleanInput($_POST['st_name']);
        $school = cleanInput($_POST['school']); // العودة إلى school
        $tel = cleanInput($_POST['tel']);
        $mobail = cleanInput($_POST['mobail']);
        $alsaf = cleanInput($_POST['alsaf']);
        $alhai = cleanInput($_POST['alhai']);
    
        if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == '1') {
            // تحديث
            $stmt = $conn->prepare("UPDATE students SET st_name=?, school=?, tel=?, mobail=?, alsaf=?, alhai=? WHERE st_num=?");
            $stmt->bind_param("sissiis", $st_name, $school, $tel, $mobail, $alsaf, $alhai, $st_num);
            if ($stmt->execute()) {
            $message = '<div class="alert alert-success">تم تحديث بيانات الطالب بنجاح</div>';
            $action = 'list';
        }
        } else {
            // إضافة جديد
            $stmt = $conn->prepare("INSERT INTO students (st_num, st_name, school, tel, mobail, alsaf, alhai) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssissis", $st_num, $st_name, $school, $tel, $mobail, $alsaf, $alhai);
            if ($stmt->execute()) {
            $message = '<div class="alert alert-success">تم إضافة الطالب بنجاح</div>';
            $action = 'list';
        } else {
            $message = '<div class="alert alert-danger">خطأ: ' . $stmt->error . '</div>';
        }
    }
    $stmt->close();
}

// جلب بيانات الطالب للتحرير
if ($action == 'edit' && isset($_GET['st_num'])) {
    $st_num = cleanInput($_GET['st_num']);
    $stmt = $conn->prepare("SELECT * FROM students WHERE st_num = ?");
    $stmt->bind_param("s", $st_num);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_student = $result->fetch_assoc();
    $stmt->close();
}

// جلب قائمة الطلاب
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
if ($search) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE st_num LIKE ? OR st_name LIKE ? ORDER BY st_name");
    $search_param = "%$search%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $students_result = $stmt->get_result();
    $stmt->close();
} else {
    $students_result = $conn->query("SELECT * FROM students ORDER BY st_name LIMIT 100");
}

include 'header.php';
?>

<div class="content-header">
    <h3><i class="fas fa-users"></i> إدارة الطلاب - القسم المتوسط</h3>
</div>

<?php echo $message; ?>

<?php if ($action == 'add' || $action == 'edit'): ?>
    <!-- نموذج الإضافة/التحرير -->
    <div class="card">
        <div class="card-header">
            <h5><?php echo $action == 'edit' ? 'تحرير بيانات الطالب' : 'إضافة طالب جديد'; ?></h5>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="edit_mode" value="1">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">رقم الهوية *</label>
                        <input type="text" name="st_num" class="form-control" 
                               value="<?php echo $edit_student ? $edit_student['st_num'] : ''; ?>"
                               <?php echo $action == 'edit' ? 'readonly' : ''; ?>
                               required pattern="[0-9]{10}" maxlength="10">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">اسم الطالب *</label>
                        <input type="text" name="st_name" class="form-control" 
                               value="<?php echo $edit_student ? $edit_student['st_name'] : ''; ?>"
                               required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الفصل :</label>
                        		 <select name="school" class="form-control">
                                    <option value="">اختر الفصل</option>
                                    <option value="1" <?php echo ($edit_student && $edit_student['school'] == '1') ? 'selected' : ''; ?>>1</option>
                                    <option value="2" <?php echo ($edit_student && $edit_student['school'] == '2') ? 'selected' : ''; ?>>2</option>
                                    <option value="3" <?php echo ($edit_student && $edit_student['school'] == '3') ? 'selected' : ''; ?>>3</option>
                                    <option value="4" <?php echo ($edit_student && $edit_student['school'] == '4') ? 'selected' : ''; ?>>4</option>
                                </select>
                    </div>
                    
	                    <div class="col-md-6 mb-3">
		                        <label class="form-label">الصف :</label>
		                        <select name="alsaf" class="form-control">
                                    <option value="">اختر الصف</option>
                                    <option value="725" <?php echo ($edit_student && $edit_student['alsaf'] == '725') ? 'selected' : ''; ?>>الأول المتوسط</option>
                                    <option value="825" <?php echo ($edit_student && $edit_student['alsaf'] == '825') ? 'selected' : ''; ?>>الثاني المتوسط</option>
                                    <option value="925" <?php echo ($edit_student && $edit_student['alsaf'] == '925') ? 'selected' : ''; ?>>الثالث المتوسط</option>
                                </select>
	                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الهاتف :</label>
                        <input type="text" name="tel" class="form-control" value="<?php echo $edit_student ? $edit_student['tel'] : ''; ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الجوال :</label>
                        <input type="text" name="mobail" class="form-control"  value="<?php echo $edit_student ? $edit_student['mobail'] : ''; ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الحي :</label>
                        <input type="text" name="alhai" class="form-control"  value="<?php echo $edit_student ? $edit_student['alhai'] : ''; ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ملاحظات هامة للطالب :</label>
                        <input type="textarea" name="barcode" class="form-control" 
                               value="<?php echo $edit_student ? $edit_student['barcode'] : ''; ?>">
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ
                    </button>
                    <a href="students.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- قائمة الطلاب -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>قائمة الطلاب</h5>
            <a href="students.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة طالب
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
                        <a href="students.php" class="btn btn-secondary">
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
                            <th>رقم الهوية</th>
                            <th>الاسم</th>
                            <th>الفصل</th>
                            <th>الصف</th>
                            <th>الجوال</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($student = $students_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['st_num']); ?></td>
                                <td><?php echo htmlspecialchars($student['st_name']); ?></td>
                                <td><?php echo $student['school'] ?? '-'; ?></td>
                                <td>
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

                                </td>
                                <td><?php echo htmlspecialchars($student['mobail']) ?? '-'; ?></td>
                                <td>
                                    <a href="students.php?action=edit&st_num=<?php echo $student['st_num']; ?>" 
                                       class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> تحرير
                                    </a>
                                    <a href="../student_report.php?st_num=<?php echo $student['st_num']; ?>" 
                                       class="btn btn-sm btn-info" target="_blank">
                                        <i class="fas fa-eye"></i> عرض
                                    </a>
                                    <a href="students.php?delete=<?php echo $student['st_num']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('هل أنت متأكد من حذف هذا الطالب؟')">
                                        <i class="fas fa-trash"></i> حذف
                                    </a>
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
