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
        $tel = cleanInput($_POST['tel']);
        $mobail = cleanInput($_POST['mobail']);
        $alsaf = cleanInput($_POST['alsaf']);
        $alhai = cleanInput($_POST['alhai']);
	    
        if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == '1') {
            // تحديث
            $stmt = $conn->prepare("UPDATE students SET st_name=?, tel=?, mobail=?, alsaf=?, alhai=? WHERE st_num=?");
            $stmt->bind_param("ssisss", $st_name, $tel, $mobail, $alsaf, $alhai, $st_num);
	        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">تم تحديث بيانات الطالب بنجاح</div>';
            $action = 'list';
        }
        } else {
            // إضافة جديد
            $stmt = $conn->prepare("INSERT INTO students (st_num, st_name, tel, mobail, alsaf, alhai) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $st_num, $st_name, $tel, $mobail, $alsaf, $alhai);
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
    $stmt = $conn->prepare("SELECT st_num, st_name, school, tel, mobail, alsaf, alhai FROM students WHERE st_num = ?");
        $stmt->bind_param("s", $st_num);
        $stmt->execute();
        
        // استخدام bind_result كبديل لـ get_result()
        $stmt->bind_result($st_num_res, $st_name_res, $school_res, $tel_res, $mobail_res, $alsaf_res, $alhai_res);
        $stmt->fetch();
        $stmt->close();
	    
	    if ($st_num_res) {
	        $edit_student = [
	            'st_num' => $st_num_res,
	            'st_name' => $st_name_res,
	            'school' => $school_res, // العودة إلى school
	            'tel' => $tel_res,
	            'mobail' => $mobail_res,
	            'alsaf' => $alsaf_res,
	            'alhai' => $alhai_res
	        ];
	    }
}

// جلب قائمة الطلاب
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
// إعدادات تعدد الصفحات
$records_per_page = 50;
$page_no = isset($_GET["page_no"]) ? (int)$_GET["page_no"] : 1;
$offset = ($page_no - 1) * $records_per_page;

// جلب قائمة الطلاب
$search = isset($_GET["search"]) ? cleanInput($_GET["search"]) : "";

$sql_count = "SELECT COUNT(*) FROM students";
$sql_select = "SELECT * FROM students";
$where_clause = "";
$params = [];
$param_types = "";

if ($search) {
    $where_clause = " WHERE st_num LIKE ? OR st_name LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types = "ss";
}

// جلب العدد الكلي للسجلات
$stmt_count = $conn->prepare($sql_count . $where_clause);
if ($search) {
    $stmt_count->bind_param($param_types, ...$params);
}
$stmt_count->execute();
$stmt_count->bind_result($total_records);
$stmt_count->fetch();
$stmt_count->close();

$total_pages = ceil($total_records / $records_per_page);

// جلب الطلاب للصفحة الحالية مع الترتيب
$sql_select .= $where_clause . " ORDER BY alsaf ASC, st_name ASC LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;
$param_types .= "ii";

$stmt = $conn->prepare($sql_select);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();

$students = [];
$result = $stmt->get_result(); // استخدام get_result() هنا لأننا نعود للإصدار 5
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
$stmt->close();

include 'header.php';
?>

<div class="content-header">
    <h3><i class="fas fa-users"></i> إدارة الطلاب</h3>
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
                    
	                    <div class="col-md-6 mb-3">		                        <label class="form-label">الصف</label>
		                        <select name="alsaf" class="form-control">
                                    <option value="">اختر الصف</option>
                                    <option value="725">الأول المتوسط (725)</option>
                                    <option value="825">الثاني المتوسط (825)</option>
                                    <option value="925">الثالث المتوسط (925)</option>
                                </select><?php echo $edit_student ? $edit_student['school'] : ''; ?>">
	                    </div>
                    
	                    <div class="col-md-6 mb-3">
		                        <label class="form-label">الصف</label>
		                        <select name="alsaf" class="form-control">
                                    <option value="">اختر الصف</option>
                                    <option value="725" <?php echo ($edit_student && $edit_student['alsaf'] == '725') ? 'selected' : ''; ?>>الأول المتوسط (725)</option>
                                    <option value="825" <?php echo ($edit_student && $edit_student['alsaf'] == '825') ? 'selected' : ''; ?>>الثاني المتوسط (825)</option>
                                    <option value="925" <?php echo ($edit_student && $edit_student['alsaf'] == '925') ? 'selected' : ''; ?>>الثالث المتوسط (925)</option>
                                </select>
	                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الهاتف</label>
                        <input type="text" name="tel" class="form-control" 
                               value="<?php echo $edit_student ? $edit_student['tel'] : ''; ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الجوال</label>
                        <input type="text" name="mobail" class="form-control" 
                               value="<?php echo $edit_student ? $edit_student['mobail'] : ''; ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الحي</label>
                        <input type="text" name="alhai" class="form-control" 
                               value="<?php echo $edit_student ? $edit_student['alhai'] : ''; ?>">
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
                        <?php foreach ($students as $student): ?>
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
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

            <!-- تعدد الصفحات -->
            <nav aria-label="Page navigation example" class="mt-3">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page_no <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page_no=<?php echo $page_no - 1; ?><?php echo $search ? '&search=' . $search : ''; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page_no == $i) ? 'active' : ''; ?>"><a class="page-link" href="?page_no=<?php echo $i; ?><?php echo $search ? '&search=' . $search : ''; ?>"><?php echo $i; ?></a></li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page_no >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page_no=<?php echo $page_no + 1; ?><?php echo $search ? '&search=' . $search : ''; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>

<?php
$conn->close();
include 'footer.php';
?>
