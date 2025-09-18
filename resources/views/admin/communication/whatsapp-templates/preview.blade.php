<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معاينة قالب واتس اب - {{$whatsappTemplate->name}}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .preview-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .whatsapp-message {
            background: #e7f3ff;
            border-radius: 18px;
            padding: 15px 20px;
            margin: 20px 0;
            border: 1px solid #d1ecf1;
            position: relative;
        }
        .whatsapp-message::before {
            content: '';
            position: absolute;
            top: 0;
            left: -8px;
            width: 0;
            height: 0;
            border: 8px solid transparent;
            border-right-color: #e7f3ff;
        }
        .template-info {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .character-count {
            font-size: 0.9em;
            color: #6c757d;
        }
        .character-count.warning {
            color: #dc3545;
        }
        .character-count.success {
            color: #28a745;
        }
        .back-btn {
            background: #25d366;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        .back-btn:hover {
            background: #128c7e;
            color: white;
        }
        .template-content {
            white-space: pre-wrap;
            line-height: 1.6;
            font-size: 16px;
        }
        .badge {
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <a href="{{route('admin.whatsapp-templates.show', $whatsappTemplate)}}" class="back-btn">
            <i class="fas fa-arrow-right"></i> رجوع للقالب
        </a>

        <div class="template-info">
            <div class="row">
                <div class="col-md-8">
                    <h2><i class="fas fa-eye"></i> معاينة القالب</h2>
                    <h4>{{$whatsappTemplate->name}}</h4>
                    @if($whatsappTemplate->description)
                        <p class="text-muted">{{$whatsappTemplate->description}}</p>
                    @endif
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge badge-info">{{$whatsappTemplate->type_label}}</span>
                    <span class="badge badge-secondary">{{$whatsappTemplate->language_label}}</span>
                    <br>
                    <span class="character-count {{$whatsappTemplate->character_count > 1600 ? 'warning' : 'success'}}">
                        <i class="fas fa-font"></i> {{$whatsappTemplate->character_count}} / 1600 حرف
                    </span>
                </div>
            </div>
        </div>

        <div class="template-info">
            <h5><i class="fas fa-mobile-alt"></i> كيف سيظهر في واتس اب:</h5>
            <div class="whatsapp-message">
                <div class="template-content">{{$whatsappTemplate->preview_content}}</div>
            </div>
        </div>

        <div class="template-info">
            <h5><i class="fas fa-code"></i> المحتوى الأصلي:</h5>
            <div class="bg-light p-3 rounded">
                <code class="template-content">{{$whatsappTemplate->content}}</code>
            </div>
        </div>

        <div class="template-info">
            <h5><i class="fas fa-info-circle"></i> معلومات القالب:</h5>
            <div class="row">
                <div class="col-md-6">
                    <strong>اسم القالب:</strong> {{$whatsappTemplate->name}}<br>
                    <strong>النوع:</strong> {{$whatsappTemplate->type_label}}<br>
                    <strong>اللغة:</strong> {{$whatsappTemplate->language_label}}
                </div>
                <div class="col-md-6">
                    <strong>عدد الأحرف:</strong> {{$whatsappTemplate->character_count}}<br>
                    <strong>الحالة:</strong> 
                    @if($whatsappTemplate->status)
                        <span class="badge badge-success">نشط</span>
                    @else
                        <span class="badge badge-danger">غير نشط</span>
                    @endif<br>
                    <strong>تاريخ الإنشاء:</strong> {{$whatsappTemplate->created_at->format('Y-m-d H:i')}}
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="{{route('admin.whatsapp-templates.edit', $whatsappTemplate)}}" class="btn btn-warning">
                <i class="fas fa-edit"></i> تعديل القالب
            </a>
            <a href="{{route('admin.whatsapp-templates.index')}}" class="btn btn-secondary">
                <i class="fas fa-list"></i> جميع القوالب
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
