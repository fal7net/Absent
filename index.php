<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام متابعة الغياب والسلوك</title>
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
        .main-container {
            max-width: 900px;
            margin: 50px auto;
        }
        .search-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            animation: fadeInUp 0.6s ease-out;
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
        .header-title {
            text-align: center;
            color: #006666;
            font-weight: bold;
            margin-bottom: 30px;
            font-size: 2.5rem;
        }
        .header-subtitle {
            text-align: center;
            color: #6c757d;
            margin-bottom: 40px;
        }
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        .search-input {
            border-radius: 50px;
            padding: 15px 60px 15px 25px;
            border: 2px solid #e0e0e0;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        .search-input:focus {
            border-color: #006666;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .search-btn {
            position: absolute;
            left: 5px;
            top: 50%;
            transform: translateY(-50%);
            border-radius: 50px;
            padding: 10px 30px;
            background: linear-gradient(135deg, #005555 0%, #006666 100%);
            border: none;
            color: white;
            font-weight: bold;
        }
        .search-btn:hover {
            background: linear-gradient(135deg, #006666 0%, #005555 100%);
            transform: translateY(-50%) scale(1.05);
        }
        .admin-link {
            text-align: center;
            margin-top: 30px;
        }
        .admin-link a {
            color: #006666;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        .admin-link a:hover {
            color: #005555;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }
        .feature-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .feature-icon {
            font-size: 2.5rem;
            color: #006666;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="search-card">
            <h1 class="header-title">
                <i class="fas fa-graduation-cap"></i>
                نظام متابعة الغياب والسلوك
            </h1>
            <p class="header-subtitle">ابحث عن سجل الطالب باستخدام رقم الهوية</p>
            
            <form action="student_report.php" method="GET">
                <div class="search-box">
                    <input type="text" 
                           name="st_num" 
                           class="form-control search-input" 
                           placeholder="أدخل رقم الهوية الوطنية..." 
                           required
                           pattern="[0-9]{10}"
                           title="يرجى إدخال رقم هوية صحيح (10 أرقام)"
                           maxlength="10">
                    <button type="submit" class="btn search-btn">
                        <i class="fas fa-search"></i> بحث
                    </button>
                </div>
            </form>

            <div class="features">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h5>سجل الحضور</h5>
                    <p class="text-muted small">متابعة الحضور والغياب</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h5>سجل السلوك</h5>
                    <p class="text-muted small">متابعة المخالفات والحسنات</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <h5>تصدير PDF</h5>
                    <p class="text-muted small">طباعة وتصدير التقارير</p>
                </div>
            </div>

            <div class="admin-link">
                <i class="fas fa-chart-line"></i>
                <a href="analytics.php" style="display: block; margin-bottom: 15px;">عرض التحليلات التفاعلية</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
