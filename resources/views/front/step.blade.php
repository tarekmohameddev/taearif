
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعاريف - أنشئ موقعك الاحترافي</title>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts for Arabic -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #000000;
            --primary-hover: #333333;
            --primary-light: rgba(0, 0, 0, 0.1);
            --secondary: #3B82F6;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            text-align: right;
            background-color: #f8f9fa;
        }
        
        .signup-container {
            max-width: 550px;
            margin: 0 auto;
            padding: 2rem;
            background-color: #fff;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover, .btn-primary:focus {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .form-control {
            border-radius: 0.375rem;
            padding: 0.625rem 0.75rem;
            transition: all 0.2s ease;
            text-align: right;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(0, 0, 0, 0.1);
        }
        
        .social-btn {
            border: 1px solid #E5E7EB;
            color: #374151;
            background-color: #fff;
            transition: all 0.2s ease;
        }
        
        .social-btn:hover {
            background-color: #F3F4F6;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .custom-checkbox .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .input-group-text {
            background-color: #F3F4F6;
            border-left: none;
            border-right: 1px solid #ced4da;
        }
        
        .subdomain-input {
            border-right: none;
            border-left: 1px solid #ced4da;
            direction: ltr; /* Force LTR for subdomain input */
            text-align: left;
        }
        
        .subdomain-input:focus {
            box-shadow: none;
            border-color: #ced4da;
        }
        
        .form-text {
            font-size: 0.75rem;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .required::after {
            content: "*";
            color: #dc3545;
            margin-right: 0.25rem;
        }
        
        .progress-container {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .progress {
            height: 0.5rem;
            background-color: #e9ecef;
        }
        
        .progress-bar {
            background-color: var(--primary);
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            transform: translateY(-50%);
        }
        
        .progress-step {
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 50%;
            background-color: #e9ecef;
            border: 3px solid #fff;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            color: #fff;
            font-weight: bold;
        }
        
        .progress-step.active {
            background-color: var(--primary);
        }
        
        .progress-step.completed {
            background-color: var(--primary);
        }
        
        .availability-indicator {
            position: absolute;
            left: 3rem;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .password-strength {
            height: 5px;
            transition: all 0.3s ease;
            border-radius: 5px;
            margin-top: 0.5rem;
        }
        
        .strength-weak {
            background-color: #ef4444;
            width: 30%;
        }
        
        .strength-medium {
            background-color: #f59e0b;
            width: 60%;
        }
        
        .strength-strong {
            background-color: #10b981;
            width: 100%;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* RTL specific adjustments */
        .dropdown-menu {
            text-align: right;
        }
        
        .form-check .form-check-input {
            float: right;
            margin-left: 0;
            margin-right: -1.5em;
        }
        
        .form-check-label {
            padding-right: 1.5em;
            padding-left: 0;
        }
        
        .me-1, .me-2, .me-3, .me-4, .me-5 {
            margin-left: 0.25rem !important;
            margin-right: 0 !important;
        }
        
        .ms-1, .ms-2, .ms-3, .ms-4, .ms-5 {
            margin-right: 0.25rem !important;
            margin-left: 0 !important;
        }
        
        .me-auto {
            margin-left: auto !important;
            margin-right: 0 !important;
        }
        
        .ms-auto {
            margin-right: auto !important;
            margin-left: 0 !important;
        }
        
        .pe-1, .pe-2, .pe-3, .pe-4, .pe-5 {
            padding-left: 0.25rem !important;
            padding-right: 0 !important;
        }
        
        .ps-1, .ps-2, .ps-3, .ps-4, .ps-5 {
            padding-right: 0.25rem !important;
            padding-left: 0 !important;
        }
        
        /* Phone input styling */
        .phone-input-container {
            position: relative;
        }
        
        .phone-flag {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            gap: 5px;
            z-index: 10;
        }
        
        .phone-flag img {
            width: 24px;
            height: auto;
            border-radius: 2px;
        }
        
        .phone-input {
            padding-right: 80px !important;
        }
        
        .flag-code {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .logo-container img {
            width: 40px;
            height: 40px;
            margin-left: 10px;
        }
        
        .logo-container span {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        /* Validation feedback styles */
        .validation-feedback {
            display: none;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        
        .validation-feedback.show {
            display: block;
        }
        
        .validation-feedback ul {
            padding-right: 1.25rem;
            margin-bottom: 0;
        }
        
        .validation-feedback li {
            margin-bottom: 0.25rem;
        }
        
        .validation-feedback li.valid {
            color: #10b981;
        }
        
        .validation-feedback li.invalid {
            color: #ef4444;
        }
        
        .validation-feedback li i {
            margin-left: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="signup-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="">
                            <img src="https://taearif.com/assets/front/img/67276fba9d424.png" alt="شعار تعاريف" >
                        </div>
                        
                        <div class="dropdown d-none">
                            <button class="btn btn-link text-dark dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-globe"></i> العربية
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="#">العربية</a></li>
                                <li><a class="dropdown-item" href="#">English</a></li>
                            </ul>
                        </div>
                    </div>

                    <h1 class="fs-2 fw-bold mb-2 text-center">سجل وابدأ خطتك المجانية</h1>
                    <p class="text-muted mb-4 text-center">أنشئ موقعك في أقل من 5 دقائق</p>
                    
                    <p class="text-muted small mb-4 text-center">
                        لديك حساب بالفعل؟ <a href="#" class="text-primary text-decoration-none">تسجيل الدخول</a>
                    </p>
                    
                    <form action="{{ route('front.membership.checkout') }}"  class="mb-4" id="signupForm" method="post" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="price" value="0">
                            <input type="hidden" name="first_name" value="test">
                            <input type="hidden" name="last_name" value="test">
                            <input type="hidden" name="company_name" value="test">
                            <input type="hidden" name="country" value="test">
                            <input type="hidden" name="is_receipt" value="0" id="is_receipt">
                            <input type="hidden" name="address" value="test">
                            <input type="hidden" name="city" value="test">
                            <input type="hidden" name="district" value="test">
                            <input type="hidden" name="country" value="test">
                            <input type="hidden" name="package_type" value="{{ $status }}">
                            <input type="hidden" name="package_id" value="{{ $id }}">
                            <input type="hidden" name="trial_days" id="trial_days" value="{{ $package->trial_days }}">
                            <input type="hidden" name="start_date" value="{{ \Carbon\Carbon::today()->format('d-m-Y') }}">
                            <input type="hidden" name="status" value="{{ $status }}">
                            <input type="hidden" name="id" value="{{ $id }}">

                            @if ($status === 'trial')
                            <input type="hidden" name="expire_date"
                                value="{{ \Carbon\Carbon::today()->addDay($package->trial_days)->format('d-m-Y') }}">
                            @else
                            @if ($package->term === 'monthly')
                                <input type="hidden" name="expire_date"
                                value="{{ \Carbon\Carbon::today()->addMonth()->format('d-m-Y') }}">
                            @elseif($package->term === 'lifetime')
                                <input type="hidden" name="expire_date" value="{{ \Carbon\Carbon::maxValue()->format('d-m-Y') }}">
                            @else
                                <input type="hidden" name="expire_date"
                                value="{{ \Carbon\Carbon::today()->addYear()->format('d-m-Y') }}">
                            @endif
                            @endif

                        <div class="mb-3">
                            <label for="fullName" class="form-label required">الاسم الكامل</label>
                            <input type="text" class="form-control" id="fullName" placeholder="أدخل اسمك الكامل" required>
                            <div class="form-text">اسمك الشخصي.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label required">البريد الإلكتروني</label>
                            <input type="email" class="form-control" name="email" id="email" placeholder="you@example.com" required>
                            <div class="form-text">سنرسل رابط تأكيد للتحقق من بريدك الإلكتروني.</div>
                            @error('email')
                                    <p class="text-danger mb-2 mt-2">{{ $message }}</p>
                             @enderror
                            <div class="validation-feedback" id="emailValidation">
                                <ul>
                                    <li id="validation-email-format" class="invalid">
                                        <i class="bi bi-x-circle"></i> يجب أن يكون بتنسيق بريد إلكتروني صحيح
                                    </li>
                                    <li id="validation-email-at" class="invalid">
                                        <i class="bi bi-x-circle"></i> يجب أن يحتوي على علامة @ 
                                    </li>
                                    <li id="validation-email-domain" class="invalid">
                                        <i class="bi bi-x-circle"></i> يجب أن يحتوي على اسم نطاق صالح (مثل gmail.com)
                                    </li>
                                    <li id="validation-email-length" class="invalid">
                                        <i class="bi bi-x-circle"></i> يجب أن يكون الطول بين 5 و 50 حرفًا
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label required">رقم الهاتف</label>
                            <div class="phone-input-container">
                                <div class="phone-flag">
                                    <img src="https://flagcdn.com/sa.svg" alt="علم السعودية">
                                    <span class="flag-code">+966</span>
                                </div>
                                <input type="tel" class="form-control phone-input" name="phone" id="phone" placeholder="5XXXXXXXX" required>
                            </div>
                            @if ($errors->has('phone'))
                                    <span class="error">
                                        <strong>{{ $errors->first('phone') }}</strong>
                                    </span>
                            @endif
                            <div class="form-text">
                                <i class="bi bi-info-circle ms-1"></i>
                                أدخل رقم هاتفك بدون صفر في البداية (مثال: 5XXXXXXXX)
                            </div>
                            <div class="invalid-feedback" id="phoneError">
                                يرجى إدخال رقم هاتف سعودي صحيح
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subdomain" class="form-label required">اختر عنوان موقعك</label>
                            <div class="input-group">
                                <span class="input-group-text">.taearif.com</span>
                                <input type="text" class="form-control subdomain-input" name="username" id="subdomain" placeholder="yoursite" required>
                                <div class="availability-indicator" id="subdomainStatus"></div>
                            </div>
                            <div class="form-text">
                                <i class="bi bi-info-circle ms-1"></i>
                                اختر اسمًا فريدًا لموقعك باللغة الإنجليزية فقط.
                            </div>
                            <div class="validation-feedback" id="subdomainValidation">
                                <ul>
                                    <li id="validation-english" class="invalid">
                                        <i class="bi bi-x-circle"></i> يجب استخدام الحروف الإنجليزية فقط
                                    </li>
                                    <li id="validation-length" class="invalid">
                                        <i class="bi bi-x-circle"></i> يجب أن يكون الطول بين 3 و 30 حرفًا
                                    </li>
                                    <li id="validation-chars" class="invalid">
                                        <i class="bi bi-x-circle"></i> يُسمح فقط بالحروف والأرقام والشرطات (-)
                                    </li>
                                    <li id="validation-start-end" class="invalid">
                                        <i class="bi bi-x-circle"></i> يجب أن يبدأ وينتهي بحرف أو رقم
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label required">إنشاء كلمة مرور</label>
                            <div class="position-relative">
                                <input type="password" class="form-control"  name="password" id="password" placeholder="8 أحرف على الأقل" required>
                                <button type="button" class="btn btn-link position-absolute start-0 top-50 translate-middle-y text-muted" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="passwordStrength"></div>
                            <div class="form-text mt-2">
                                <i class="bi bi-shield-lock ms-1"></i>
                                يجب أن تتكون كلمة المرور من 8 أحرف على الأقل وتتضمن مزيجًا من الحروف والأرقام والرموز.
                            </div>
                        </div>
                        
                        <div class="mb-4 form-check custom-checkbox">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label small" for="terms">
                                لقد قرأت ووافقت على <a href="#" class="text-primary text-decoration-none">شروط الخدمة</a> و <a href="#" class="text-primary text-decoration-none">سياسة الخصوصية</a>
                            </label>
                        </div>
                        
                        <div class="mb-4">
                                @if ($bs->is_recaptcha == 1)
                                    <div class="d-block mb-4">
                                        {!! NoCaptcha::renderJs() !!}
                                        {!! NoCaptcha::display() !!}
                                        @if ($errors->has('g-recaptcha-response'))
                                            @php
                                                $errmsg = $errors->first('g-recaptcha-response');
                                            @endphp
                                            <p class="text-danger mb-0 mt-2">{{ __("$errmsg") }}</p>
                                        @endif
                                    </div>
                                @endif
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="bi bi-rocket-takeoff ms-2"></i>
                            إنشاء موقعك
                        </button>
                        
                        <div class="form-text text-center mt-2">
                            <i class="bi bi-shield-check ms-1"></i>
                            معلوماتك آمنة ولن يتم مشاركتها أبدًا.
                        </div>
                    </form>

                    <div class="text-center position-relative my-4">
                        <hr>
                        <span class="position-absolute top-50 start-50 translate-middle px-3 bg-white text-muted small">أو تابع باستخدام</span>
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-12">
                            <button class="btn social-btn w-100 d-flex align-items-center justify-content-center gap-2">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Google_%22G%22_logo.svg/36px-Google_%22G%22_logo.svg.png" alt="جوجل" width="20" height="20">
                                <span class="small">جوجل</span>
                            </button>
                        </div>
                        <div class="col-6 d-none">
                            <button class="btn social-btn w-100 d-flex align-items-center justify-content-center gap-2">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/5/51/Facebook_f_logo_%282019%29.svg" alt="فيسبوك" width="20" height="20">
                                <span class="small">فيسبوك</span>
                            </button>
                        </div>
                    </div>

                    <div class="text-center text-muted small">
                        تحتاج مساعدة؟ <a href="#" class="text-primary text-decoration-none">اتصل بالدعم</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password visibility toggle
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#password');

            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.querySelector('i').classList.toggle('bi-eye');
                this.querySelector('i').classList.toggle('bi-eye-slash');
            });
            
            // Password strength indicator
            const passwordStrength = document.querySelector('#passwordStrength');
            
            password.addEventListener('input', function() {
                const value = this.value;
                
                // Remove previous classes
                passwordStrength.classList.remove('strength-weak', 'strength-medium', 'strength-strong');
                
                if (value.length === 0) {
                    passwordStrength.style.display = 'none';
                    return;
                }
                
                passwordStrength.style.display = 'block';
                
                // Simple password strength check
                if (value.length < 8) {
                    passwordStrength.classList.add('strength-weak');
                } else if (value.length >= 8 && value.length < 12) {
                    passwordStrength.classList.add('strength-medium');
                } else {
                    passwordStrength.classList.add('strength-strong');
                }
            });
            
            // Subdomain validation and availability check
            const subdomain = document.querySelector('#subdomain');
            const subdomainStatus = document.querySelector('#subdomainStatus');
            const subdomainValidation = document.querySelector('#subdomainValidation');
            
            // Validation rules
            const validationEnglish = document.querySelector('#validation-english');
            const validationLength = document.querySelector('#validation-length');
            const validationChars = document.querySelector('#validation-chars');
            const validationStartEnd = document.querySelector('#validation-start-end');
            
            subdomain.addEventListener('input', function() {
                const value = this.value.trim();
                
                // Show validation feedback when user starts typing
                if (value.length > 0) {
                    subdomainValidation.classList.add('show');
                } else {
                    subdomainValidation.classList.remove('show');
                    subdomainStatus.innerHTML = '';
                    return;
                }
                
                // Validation rules
                const englishOnly = /^[a-zA-Z0-9\-]+$/.test(value);
                const validLength = value.length >= 3 && value.length <= 30;
                const validChars = /^[a-zA-Z0-9\-]+$/.test(value);
                const validStartEnd = /^[a-zA-Z0-9].*[a-zA-Z0-9]$/.test(value) || value.length === 1;
                
                // Update validation feedback
                updateValidationItem(validationEnglish, englishOnly);
                updateValidationItem(validationLength, validLength);
                updateValidationItem(validationChars, validChars);
                updateValidationItem(validationStartEnd, validStartEnd);
                
                // Check if all validations pass
                const allValid = englishOnly && validLength && validChars && validStartEnd;
                
                if (allValid) {
                    // Simulate checking availability
                    setTimeout(() => {
                        if (value === 'taearif' || value === 'admin' || value === 'test') {
                            subdomainStatus.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i>';
                            subdomain.classList.add('is-invalid');
                            subdomain.classList.remove('is-valid');
                        } else {
                            subdomainStatus.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
                            subdomain.classList.remove('is-invalid');
                            subdomain.classList.add('is-valid');
                        }
                    }, 500);
                } else {
                    subdomainStatus.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i>';
                    subdomain.classList.add('is-invalid');
                    subdomain.classList.remove('is-valid');
                }
            });
            
            // Email validation
            const email = document.querySelector('#email');
            const emailValidation = document.querySelector('#emailValidation');

            // Validation rules
            const validationEmailFormat = document.querySelector('#validation-email-format');
            const validationEmailAt = document.querySelector('#validation-email-at');
            const validationEmailDomain = document.querySelector('#validation-email-domain');
            const validationEmailLength = document.querySelector('#validation-email-length');

            email.addEventListener('input', function() {
                const value = this.value.trim();
                
                // Show validation feedback when user starts typing
                if (value.length > 0) {
                    emailValidation.classList.add('show');
                } else {
                    emailValidation.classList.remove('show');
                    this.classList.remove('is-invalid');
                    this.classList.remove('is-valid');
                    return;
                }
                
                // Validation rules
                const hasAtSymbol = value.includes('@');
                const validFormat = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                const validDomain = hasAtSymbol && value.split('@')[1].includes('.') && value.split('@')[1].length > 3;
                const validLength = value.length >= 5 && value.length <= 50;
                
                // Update validation feedback
                updateValidationItem(validationEmailFormat, validFormat);
                updateValidationItem(validationEmailAt, hasAtSymbol);
                updateValidationItem(validationEmailDomain, validDomain);
                updateValidationItem(validationEmailLength, validLength);
                
                // Check if all validations pass
                const allValid = validFormat && hasAtSymbol && validDomain && validLength;
                
                if (allValid) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                }
            });
            
            function updateValidationItem(element, isValid) {
                if (isValid) {
                    element.classList.remove('invalid');
                    element.classList.add('valid');
                    element.querySelector('i').classList.remove('bi-x-circle');
                    element.querySelector('i').classList.add('bi-check-circle');
                } else {
                    element.classList.remove('valid');
                    element.classList.add('invalid');
                    element.querySelector('i').classList.remove('bi-check-circle');
                    element.querySelector('i').classList.add('bi-x-circle');
                }
            }
            
            // Saudi phone number validation
            const phone = document.querySelector('#phone');
            const phoneError = document.querySelector('#phoneError');
            
            phone.addEventListener('input', function() {
                const value = this.value.trim();
                const saudiPhoneRegex = /^5[0-9]{8}$/;
                
                if (value.length === 0) {
                    this.classList.remove('is-invalid');
                    return;
                }
                
                if (saudiPhoneRegex.test(value)) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                    
                    if (value.startsWith('0')) {
                        phoneError.textContent = 'يرجى إدخال الرقم بدون صفر في البداية';
                    } else if (value.length !== 9) {
                        phoneError.textContent = 'رقم الهاتف السعودي يجب أن يتكون من 9 أرقام';
                    } else if (!value.startsWith('5')) {
                        phoneError.textContent = 'رقم الهاتف السعودي يجب أن يبدأ بـ 5';
                    } else {
                        phoneError.textContent = 'يرجى إدخال رقم هاتف سعودي صحيح';
                    }
                }
            });
            
            // Form submission
            const form = document.querySelector('#signupForm');

form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validate form
    if (!form.checkValidity()) {
        e.stopPropagation();
        form.classList.add('was-validated');
        return;
    }
    
    // Validate email
    const emailValue = email.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (!emailRegex.test(emailValue) || emailValue.length < 5 || emailValue.length > 50) {
        email.classList.add('is-invalid');
        emailValidation.classList.add('show');
        return;
    }
    
    // Validate phone number
    const phoneValue = phone.value.trim();
    const saudiPhoneRegex = /^5[0-9]{8}$/;
    
    if (!saudiPhoneRegex.test(phoneValue)) {
        phone.classList.add('is-invalid');
        return;
    }
    
    // Validate subdomain
    const subdomainValue = subdomain.value.trim();
    const subdomainRegex = /^[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]$/;
    
    if (!subdomainRegex.test(subdomainValue) || subdomainValue.length < 3 || subdomainValue.length > 30) {
        subdomain.classList.add('is-invalid');
        subdomainValidation.classList.add('show');
        return;
    }
    
    // Simulate form submission
    const submitButton = this.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span> جاري إنشاء موقعك...';
    
    // Simulate API call
    setTimeout(() => {
        // Redirect to dashboard or next step
        alert('تم إنشاء الحساب بنجاح! جاري التحويل إلى لوحة التحكم...');
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="bi bi-rocket-takeoff ms-2"></i> إنشاء موقعك';
        
        // Submit the form after validation
        form.removeEventListener('submit', arguments.callee);
        form.submit();
    }, 2000);
    });
        });
    </script>
</body>
</html>

