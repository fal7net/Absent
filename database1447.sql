-- جدول الطلاب
CREATE TABLE IF NOT EXISTS students (
    st_num VARCHAR(50) PRIMARY KEY COMMENT 'رقم الهوية',
    st_name VARCHAR(255) NOT NULL COMMENT 'اسم الطالب',
    school INT COMMENT 'رقم المدرسة',
    tel VARCHAR(50) COMMENT 'الهاتف',
    email VARCHAR(255) COMMENT 'البريد الإلكتروني',
    natio VARCHAR(100) COMMENT 'الجنسية',
    alsaf INT COMMENT 'الصف',
    alhai VARCHAR(100) COMMENT 'الحي',
    mobail VARCHAR(50) COMMENT 'الجوال',
    barcode VARCHAR(100) COMMENT 'الباركود',
    dateofregester INT COMMENT 'تاريخ التسجيل',
    timeenter DATETIME COMMENT 'وقت الدخول',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الغياب والحضور
CREATE TABLE IF NOT EXISTS attendance (
    serial INT AUTO_INCREMENT PRIMARY KEY COMMENT 'الرقم التسلسلي',
    dateenter DATETIME NOT NULL COMMENT 'تاريخ الدخول',
    time DATETIME COMMENT 'الوقت',
    st_num VARCHAR(50) NOT NULL COMMENT 'رقم هوية الطالب',
    enter_status INT COMMENT 'حالة الدخول (1=حاضر، 2=غائب، 3=متأخر)',
    time1 DATETIME COMMENT 'وقت إضافي',
    do_action INT COMMENT 'الإجراء',
    enter2 INT COMMENT 'دخول ثانوي',
    sentg INT COMMENT 'إرسال للولي',
    sentt INT COMMENT 'إرسال للمعلم',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (st_num) REFERENCES students(st_num) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_st_num (st_num),
    INDEX idx_dateenter (dateenter)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المخالفات السلوكية (أنواع المخالفات)
CREATE TABLE IF NOT EXISTS behavior_types (
    id_m INT AUTO_INCREMENT PRIMARY KEY COMMENT 'رقم المخالفة',
    name1 TEXT COMMENT 'وصف المخالفة',
    degree INT COMMENT 'درجة المخالفة (سالبة للمخالفات، موجبة للحسنات)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول سجل السلوك (المخالفات المسجلة للطلاب)
CREATE TABLE IF NOT EXISTS behavior_records (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'الرقم التسلسلي',
    st_num VARCHAR(50) NOT NULL COMMENT 'رقم هوية الطالب',
    date1 DATETIME COMMENT 'تاريخ المخالفة',
    id_m INT COMMENT 'رقم نوع المخالفة',
    value1 INT COMMENT 'القيمة',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (st_num) REFERENCES students(st_num) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_m) REFERENCES behavior_types(id_m) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_st_num (st_num),
    INDEX idx_date (date1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المستخدمين (للوحة التحكم)
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    role ENUM('admin', 'teacher') DEFAULT 'teacher',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج مستخدم افتراضي (admin/admin123)
INSERT INTO admin_users (username, password, full_name, role) 
VALUES ('admin', '$2y$10$ruKvh6tfNhh5mddW0Qhum.EJZ3bOwavQwgRhX2yt5RDJNG26.paPS', 'المدير العام', 'admin');
-- كلمة المرور: admin123
