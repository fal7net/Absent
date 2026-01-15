<?php
require_once '../config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$conn = getDBConnection();
$message = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_type = null;

// معالجة الحذف
if (isset($_GET['delete'])) {
    $id_m = cleanInput($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM behavior_types WHERE id_m = ?");
    $stmt->bind_param("i", $id_m);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">تم حذف النوع بنجاح</div>';
    }
    $stmt->close();
}

// معالجة الإضافة والتحرير
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name1 = cleanInput($_POST['name1']);
    $degree = cleanInput($_POST['degree']);
    
    if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == '1') {
        $id_m = cleanInput($_POST['id_m']);
        $stmt = $conn->prepare("UPDATE behavior_types SET name1=?, degree=? WHERE id_m=?");
        $stmt->bind_param("sii", $name1, $degree, $id_m);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">تم تحديث النوع بنجاح</div>';
            $action = 'list';
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO behavior_types (name1, degree) VALUES (?, ?)");
        $stmt->bind_param("si", $name1, $degree);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">تم إضافة النوع بنجاح</div>';
            $action = 'list';
        }
    }
    $stmt->close();
}

// جلب بيانات النوع للتحرير
if ($action == 'edit' && isset($_GET['id_m'])) {
    $id_m = cleanInput($_GET['id_m']);
    $stmt = $conn->prepare("SELECT * FROM behavior_types WHERE id_m = ?");
    $stmt->bind_param("i", $id_m);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_type = $result->fetch_assoc();
    $stmt->close();
}

// جلب قائمة الأنواع
$types_result = $conn->query("SELECT * FROM behavior_types ORDER BY id_m");

include 'header.php';
?>

<div class="content-header">
    <h3><i class="fas fa-list"></i> إدارة أنواع المخالفات والحسنات</h3>
</div>

<?php echo $message; ?>

<?php if ($action == 'add' || $action == 'edit'): ?>
    <!-- نموذج الإضافة/التحرير -->
    <div class="card">
        <div class="card-header">
            <h5><?php echo $action == 'edit' ? 'تحرير نوع المخالفة/الحسنة' : 'إضافة نوع جديد'; ?></h5>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="edit_mode" value="1">
                    <input type="hidden" name="id_m" value="<?php echo $edit_type['id_m']; ?>">
                <?php endif; ?>
                
                <div class="mb-3">
                    <label class="form-label">وصف المخالفة/الحسنة *</label>
                    <textarea name="name1" class="form-control" rows="3" required><?php echo $edit_type ? htmlspecialchars($edit_type['name1']) : ''; ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">الدرجة *</label>
                    <input type="number" name="degree" class="form-control" 
                           value="<?php echo $edit_type ? $edit_type['degree'] : ''; ?>"
                           required>
                    <small class="form-text text-muted">
                        القيم السالبة للمخالفات (مثل: -1، -2، -3)، القيم الموجبة للتميز السلوكي (مثل: +1، +2، +3)
                    </small>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ
                    </button>
                    <a href="behavior_types.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- قائمة الأنواع -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>قائمة أنواع المخالفات السلوكية و التميز السلوكي</h5>
            <a href="behavior_types.php?action=add" class="btn btn-light">
                <i class="fas fa-plus"></i> إضافة نوع
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>الرقم</th>
                            <th>الوصف</th>
                            <th>الدرجة</th>
                            <th>النوع</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($type = $types_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $type['id_m']; ?></td>
                                <td style="max-width: 400px;"><?php echo htmlspecialchars($type['name1']); ?></td>
                                <td>
                                    <strong><?php echo $type['degree']; ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $degree = $type['degree'];
                                    if ($degree > 0) {
                                        echo '<span class="badge bg-success">متميز</span>';
                                    } elseif ($degree < 0) {
                                        echo '<span class="badge bg-danger">مخالف</span>';
                                    } else {
                                        echo '<span class="badge bg-secondary">محايد</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="behavior_types.php?action=edit&id_m=<?php echo $type['id_m']; ?>" 
                                       class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> تحرير
                                    </a>
                                    <a href="behavior_types.php?delete=<?php echo $type['id_m']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('هل أنت متأكد من حذف هذا النوع؟')">
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
