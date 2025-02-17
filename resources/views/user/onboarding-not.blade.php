<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء موقع جديد</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            height: 100vh;
            background-color: #f8f9fa;
            overflow: hidden; /* Prevent scrolling */
        }
        .left-section {
            background: linear-gradient(to right, #004e92, #000428);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px; /* Reduced padding */
        }
        .right-section {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px; /* Reduced padding */
        }
        .form-container {
            max-width: 360px; /* Slightly smaller */
            width: 100%;
            padding: 10px; /* Reduced padding */
        }
        .btn-primary {
            background-color: #004e92;
            border: none;
            padding: 10px; /* Smaller buttons */
        }
        .btn-primary:hover {
            background-color: #003366;
        }
        /* .btn-secondary {
            padding: 8px;
        } */
        .logo {
            width: 45px;
            margin-bottom: 5px;
        }
        .preview-image img {
            max-width: 120px; /* Smaller previews */
            height: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 5px;
        }
        .upload-btn {
            background-color: white;
            border: 2px dashed #8c9998;
            color: #0E9384;
            padding: 0.8rem; /* Smaller padding */
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            border-radius: 10px;
            font-size: 14px;
        }
        .upload-btn i {
            font-size: 20px;
            margin-bottom: 3px;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row vh-100">
        <!-- Left Section (Preview / Illustration) -->
        <div class="col-md-6 d-none d-md-flex left-section">
            <div class="text-center">
                <h3 class="mb-3">كُن جزءًا من المستقبل الرقمي</h3>
                <img src="/images/images/courses-2.jpg" class="img-fluid" alt="Website Preview">
            </div>
        </div>

        <!-- Right Section (Form) -->
        <div class="col-md-6 right-section">
            <div class="form-container">
                <div class="text-center">
                    <img src="/img/logo/logo.png" alt="Logo" class="logo">
                    <h4>أنشئ موقعًا جديدًا</h4>
                </div>

                <form action="onboarding_complete.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-2">
                        <label class="form-label">اسم المشروع *</label>
                        <input type="text" name="company_name" class="form-control" placeholder="اكتب اسم مشروعك" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">مجال العمل *</label>
                        <select name="industry_type" class="form-select" required>
                            <option value="">اختر مجال العمل</option>
                            <option value="General Website">موقع عام</option>
                            <option value="Lawyer">محامي</option>
                            <option value="Real Estate Company">شركة عقارات</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">وصف قصير</label>
                        <textarea name="short_description" class="form-control" maxlength="255"></textarea>
                    </div>

                    <!-- Logo Upload Field -->
                    <div class="mb-2 text-end">
                        <label class="form-label">تحميل الشعار (اختياري)</label>
                        <div class="form-group text-center">
                            <div class="preview-image">
                                <img id="logoPreview" src="/assets/admin/img/noimage.jpg" alt="Logo Preview" class="img-thumbnail">
                            </div>
                            <input type="file" id="logoUpload" name="logo" class="d-none" accept="image/*" onchange="previewImage(event, 'logoPreview')">
                            <button type="button" class="upload-btn mt-1" onclick="document.getElementById('logoUpload').click()">
                                <i class="bi bi-upload"></i>
                                <span>أرفع صورة</span>
                            </button>
                        </div>
                    </div>

                    <!-- Icon Upload Field -->
                    <div class="mb-2 text-end">
                        <label class="form-label">تحميل الأيقونة (اختياري)</label>
                        <div class="form-group text-center">
                            <div class="preview-image">
                                <img id="iconPreview" src="/assets/admin/img/noimage.jpg" alt="Icon Preview" class="img-thumbnail">
                            </div>
                            <input type="file" id="iconUpload" name="icon" class="d-none" accept="image/*" onchange="previewImage(event, 'iconPreview')">
                            <button type="button" class="upload-btn mt-1" onclick="document.getElementById('iconUpload').click()">
                                <i class="bi bi-upload"></i>
                                <span>أرفع صورة</span>
                            </button>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">لون الموقع الأساسي *</label>
                        <input type="color" name="primary_color" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">أنشئ موقعك</button>
                    <a href="skip_onboarding.php" class="btn btn-secondary w-100 mt-2">تخطي</a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function previewImage(event, previewId) {
        var reader = new FileReader();
        reader.onload = function(){
            var output = document.getElementById(previewId);
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>

</body>
</html>
