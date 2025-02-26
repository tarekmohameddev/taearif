<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap');

        .auth-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .auth-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .auth-switcher {
            background: #f8f9fa;
            border-radius: 0.75rem;
            padding: 0.25rem;
            display: inline-flex;
            margin-bottom: 1.5rem;
        }

        .auth-switcher button {
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .auth-switcher button.active {
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            color: #0d6efd;
        }

        .verification-inputs {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            direction: ltr;
        }

        .verification-inputs input {
            width: 3rem;
            height: 3rem;
            text-align: center;
            font-size: 1.25rem;
            font-weight: 600;
            border-radius: 0.5rem;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
            border-color: #0d6efd;
        }

        .input-group-text {
            background: transparent;
        }

        .btn-primary {
            padding: 0.75rem 1rem;
            font-weight: 500;
        }

        .loading .spinner-border {
            width: 1.25rem;
            height: 1.25rem;
            margin-left: 0.5rem;
        }

        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 0.5rem;
            background: #e9ecef;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s, background-color 0.3s;
        }

        .strength-weak { background-color: #dc3545; width: 33.33%; }
        .strength-medium { background-color: #ffc107; width: 66.66%; }
        .strength-strong { background-color: #198754; width: 100%; }

        .modal.fade .modal-dialog {
            transform: scale(0.95);
            transition: transform 0.3s ease-out;
        }

        .modal.show .modal-dialog {
            transform: scale(1);
        }

        .toast-container {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1060;
        }

        /* RTL specific adjustments */
        .input-group > :not(:first-child):not(.dropdown-menu):not(.valid-tooltip):not(.valid-feedback):not(.invalid-tooltip):not(.invalid-feedback) {
            margin-right: -1px;
            margin-left: 0;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            border-top-left-radius: 0.375rem;
            border-bottom-left-radius: 0.375rem;
        }

        .input-group:not(.has-validation) > :not(:last-child):not(.dropdown-toggle):not(.dropdown-menu):not(.form-floating) {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            border-top-right-radius: 0.375rem;
            border-bottom-right-radius: 0.375rem;
        }

        .btn-close {
            margin: 0;
        }

        .me-2 {
            margin-left: 0.5rem !important;
            margin-right: 0 !important;
        }

        .ms-1 {
            margin-right: 0.25rem !important;
            margin-left: 0 !important;
        }

        .ms-2 {
            margin-right: 0.5rem !important;
            margin-left: 0 !important;
        }
    </style>
    
    <div class="container text-center">
        <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#authModal">
        حجز الوحدة
        </button>
    </div>

    <!-- Auth Modal -->
    <div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 px-4 pt-4">
                    <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 pt-0">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold mb-2" id="authTitle">مرحباً بك</h2>
                        <p class="text-muted" id="authSubtitle">أدخل بريدك الإلكتروني أو رقم هاتفك للمتابعة</p>
                    </div>

                    <!-- Main Form -->
                    <form id="authForm" novalidate>
                        <!-- Initial Step -->
                        <div id="initialStep">
                            <div class="text-center mb-4">
                                <div class="auth-switcher d-inline-flex">
                                    <button type="button" class="active" data-type="email">
                                        <i class="bi bi-envelope ms-2"></i>البريد الإلكتروني
                                    </button>
                                    <button type="button" data-type="phone">
                                        <i class="bi bi-phone ms-2"></i>الهاتف
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="identifier" placeholder="أدخل بريدك الإلكتروني">
                                </div>
                                <div class="invalid-feedback">يرجى إدخال بريد إلكتروني صحيح</div>
                            </div>
                        </div>

                        <!-- Verification Step -->
                        <div id="verifyStep" class="d-none">
                            <div class="verification-inputs mb-4">
                                <input type="text" class="form-control" maxlength="1" pattern="\d">
                                <input type="text" class="form-control" maxlength="1" pattern="\d">
                                <input type="text" class="form-control" maxlength="1" pattern="\d">
                                <input type="text" class="form-control" maxlength="1" pattern="\d">
                                <input type="text" class="form-control" maxlength="1" pattern="\d">
                                <input type="text" class="form-control" maxlength="1" pattern="\d">
                            </div>

                            <div class="text-center mb-4">
                                <small class="text-muted">لم يصلك الرمز؟</small>
                                <button type="button" class="btn btn-link btn-sm p-0 me-1" id="resendCode">إعادة الإرسال</button>
                                <div class="text-muted mt-1 small" id="resendTimer" style="display: none;">
                                    إعادة الإرسال متاحة خلال <span>30</span> ثانية
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-link p-0" id="backButton">
                                    <i class="bi bi-arrow-right ms-1"></i>رجوع
                                </button>
                                <button type="button" class="btn btn-link p-0" id="switchMethodButton">
                                    استخدم الهاتف بدلاً من ذلك
                                </button>
                            </div>
                        </div>

                        <!-- Registration Step -->
                        <div id="registerStep" class="d-none">
                            <div class="mb-3">
                                <input type="text" class="form-control" id="fullName" placeholder="الاسم الكامل">
                                <div class="invalid-feedback">يرجى إدخال الاسم الكامل</div>
                            </div>
                            <div class="mb-3">
                                <div class="input-group">
                                    <input type="password" class="form-control" id="newPassword" placeholder="كلمة المرور">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength">
                                    <div class="password-strength-bar"></div>
                                </div>
                                <div class="mt-2 small text-muted">
                                    يجب أن تحتوي كلمة المرور على 8 أحرف على الأقل، وتتضمن أحرفاً كبيرة وصغيرة وأرقاماً ورموزاً
                                </div>
                            </div>
                            <button type="button" class="btn btn-link p-0 mb-3" id="backToVerify">
                                <i class="bi bi-arrow-right ms-1"></i>رجوع
                            </button>
                        </div>

                        <!-- Login Step -->
                        <div id="loginStep" class="d-none">
                            <div class="mb-3">
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" placeholder="كلمة المرور">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="rememberMe">
                                    <label class="form-check-label" for="rememberMe">تذكرني</label>
                                </div>
                                <button type="button" class="btn btn-link p-0" id="forgotPassword">نسيت كلمة المرور؟</button>
                            </div>
                            <button type="button" class="btn btn-link p-0 mb-3" id="backToInitial">
                                <i class="bi bi-arrow-right ms-1"></i>رجوع
                            </button>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="submitButton">
                            <span class="button-text">متابعة</span>
                            <span class="loading d-none">
                                <span class="spinner-border spinner-border-sm"></span>
                                جارٍ المعالجة...
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container"></div>

    <script>
        
        document.addEventListener('DOMContentLoaded', function() {

            const getBaseUrl = () => {
            const { protocol, host, pathname } = window.location;

            // Check if it's a subdomain (e.g., tenant.example.com)
                console.log(host);
            if (host === "127.0.0.1:8000" || host === "localhost:8000") {
                const pathSegments = pathname.split('/').filter(Boolean); 
                return `${protocol}//${host}/${pathSegments[0]}`;  // Assume /tenant for local development
            }

            if (host.split('.').length > 2) {  
                return `${protocol}//${host}`; 
            } 

            // Check if it's a subdirectory (e.g., example.com/tenant)
            const pathSegments = pathname.split('/').filter(Boolean); 
            if (pathSegments.length > 0) {  
                return `${protocol}//${host}/${pathSegments[0]}`;  
            } 

            // Default case (e.g., example.com)
            return `${protocol}//${host}`; 
        };

            // API Configuration
            const API_BASE_URL = getBaseUrl();
            const API_ENDPOINTS = {
                CHECK_USER: '/user/check-user',
                SEND_OTP: '/user/send-otp',
                VERIFY_OTP: '/user/verify-otp',
                REGISTER: '/user/register-customer',
                LOGIN: '/user/login-customer',
                FORGOT_PASSWORD: '/user/forgot-password-customer'
            };

            const state = {
                isEmail: true,
                step: 'initial',
                loading: false,
                resendTimer: null,
                userData: null,
                token: null
            };

            const elements = {
                modal: new bootstrap.Modal(document.getElementById('authModal')),
                form: document.getElementById('authForm'),
                title: document.getElementById('authTitle'),
                subtitle: document.getElementById('authSubtitle'),
                steps: {
                    initial: document.getElementById('initialStep'),
                    verify: document.getElementById('verifyStep'),
                    register: document.getElementById('registerStep'),
                    login: document.getElementById('loginStep')
                },
                submitButton: document.getElementById('submitButton'),
                identifier: document.getElementById('identifier'),
                fullName: document.getElementById('fullName'),
                newPassword: document.getElementById('newPassword'),
                password: document.getElementById('password'),
                togglePassword: document.getElementById('togglePassword'),
                toggleNewPassword: document.getElementById('toggleNewPassword'),
                backButton: document.getElementById('backButton'),
                backToVerify: document.getElementById('backToVerify'),
                backToInitial: document.getElementById('backToInitial'),
                switchMethodButton: document.getElementById('switchMethodButton'),
                resendCode: document.getElementById('resendCode'),
                resendTimer: document.getElementById('resendTimer')
            };

            // API Functions
            async function apiRequest(endpoint, method = 'POST', data = null) {
                try {
                    const headers = {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    };

                    if (state.token) {
                        headers['Authorization'] = `Bearer ${state.token}`;
                    }
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (csrfToken) {
                        headers['X-CSRF-TOKEN'] = csrfToken;
                    }
                    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
                        method,
                        headers,
                        body: data ? JSON.stringify(data) : null
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.message || 'حدث خطأ ما');
                    }

                    return result;
                } catch (error) {
                    throw new Error(error.message || 'حدث خطأ في الاتصال بالخادم');
                }
            }

            // Auth type switcher
            document.querySelector('.auth-switcher').addEventListener('click', (e) => {
                if (e.target.closest('button')) {
                    const buttons = document.querySelectorAll('.auth-switcher button');
                    buttons.forEach(btn => btn.classList.remove('active'));
                    e.target.closest('button').classList.add('active');

                    state.isEmail = e.target.closest('button').dataset.type === 'email';
                    updateIdentifierInput();
                }
            });

            // Handle form submission
            elements.form.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (state.loading) return;

                setLoading(true);

                try {
                    switch (state.step) {
                        case 'initial':
                            const identifier = elements.identifier.value;
                            if (!validateIdentifier(identifier)) {
                                elements.identifier.classList.add('is-invalid');
                                throw new Error(state.isEmail ? 'بريد إلكتروني غير صالح' : 'رقم هاتف غير صالح');
                            }

                            // Check if user exists
                            const checkResult = await apiRequest(API_ENDPOINTS.CHECK_USER, 'POST', {
                                identifier,
                                type: state.isEmail ? 'email' : 'phone'
                            });

                            state.userData = { identifier };

                            if (checkResult.exists) {
                                setStep('login');
                            } else {
                                // Send OTP for new user
                                await apiRequest(API_ENDPOINTS.SEND_OTP, 'POST', {
                                    identifier,
                                    type: state.isEmail ? 'email' : 'phone'
                                });
                                setStep('verify');
                                startResendTimer();
                            }
                            break;

                        case 'verify':
                            const code = Array.from(document.querySelectorAll('.verification-inputs input'))
                                .map(input => input.value)
                                .join('');

                            if (code.length !== 6 || !/^\d+$/.test(code)) {
                                throw new Error('يرجى إدخال رمز التحقق الصحيح');
                            }

                            // Verify OTP
                            const verifyResult = await apiRequest(API_ENDPOINTS.VERIFY_OTP, 'POST', {
                                identifier: state.userData.identifier,
                                code,
                                type: state.isEmail ? 'email' : 'phone'
                            });

                            if (verifyResult.verified) {
                                setStep('register');
                            } else {
                                throw new Error('رمز التحقق غير صحيح');
                            }
                            break;

                        case 'register':
                            const fullName = elements.fullName.value;
                            const password = elements.newPassword.value;

                            if (!fullName) {
                                elements.fullName.classList.add('is-invalid');
                                throw new Error('يرجى إدخال الاسم الكامل');
                            }

                            if (!validatePassword(password)) {
                                throw new Error('كلمة المرور غير قوية بما فيه الكفاية');
                            }

                            // Register new user
                            const registerResult = await apiRequest(API_ENDPOINTS.REGISTER, 'POST', {
                                name: fullName,
                                password,
                                ...state.userData
                            });

                            state.token = registerResult.token;
                            showToast('تم إنشاء حسابك بنجاح!', 'success');
                            elements.modal.hide();
                            setTimeout(() => window.location.reload(), 3000);
                            break;

                        case 'login':
                            const loginPassword = elements.password.value;
                            if (!loginPassword) {
                                throw new Error('يرجى إدخال كلمة المرور');
                            }

                            // Login user
                            const loginResult = await apiRequest(API_ENDPOINTS.LOGIN, 'POST', {
                                email: state.userData.identifier,
                                password: loginPassword,
                                remember: document.getElementById('rememberMe').checked
                            });

                            state.token = loginResult.token;
                            showToast('تم تسجيل الدخول بنجاح!', 'success');
                            elements.modal.hide();
                            setTimeout(() => window.location.reload(), 3000);
                            break;
                    }
                } catch (error) {
                    showToast(error.message, 'error');
                } finally {
                    setLoading(false);
                }
            });

            // Navigation
            elements.backButton.addEventListener('click', () => setStep('initial'));
            elements.backToVerify.addEventListener('click', () => setStep('verify'));
            elements.backToInitial.addEventListener('click', () => setStep('initial'));

            elements.switchMethodButton.addEventListener('click', () => {
                state.isEmail = !state.isEmail;
                updateIdentifierInput();
                setStep('initial');
            });

            // Forgot password
            document.getElementById('forgotPassword')?.addEventListener('click', async () => {
                try {
                    await apiRequest(API_ENDPOINTS.FORGOT_PASSWORD, 'POST', {
                        identifier: state.userData.identifier
                    });
                    showToast('تم إرسال رابط إعادة تعيين كلمة المرور', 'info');
                } catch (error) {
                    showToast(error.message, 'error');
                }
            });

            // Resend code
            elements.resendCode?.addEventListener('click', async function() {
                if (this.disabled) return;
                
                try {
                    await apiRequest(API_ENDPOINTS.SEND_OTP, 'POST', {
                        identifier: state.userData.identifier,
                        type: state.isEmail ? 'email' : 'phone'
                    });
                    showToast('تم إعادة إرسال رمز التحقق!', 'success');
                    startResendTimer();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            });

            // Password visibility toggles
            [elements.togglePassword, elements.toggleNewPassword].forEach(toggle => {
                toggle?.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const type = input.type === 'password' ? 'text' : 'password';
                    input.type = type;
                    this.innerHTML = `<i class="bi bi-eye${type === 'password' ? '' : '-slash'}"></i>`;
                });
            });

            // Password strength indicator
            elements.newPassword?.addEventListener('input', function() {
                const password = this.value;
                const strengthBar = document.querySelector('.password-strength-bar');
                
                const hasLength = password.length >= 8;
                const hasUpper = /[A-Z]/.test(password);
                const hasLower = /[a-z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                const hasSpecial = /[^A-Za-z0-9]/.test(password);
                
                const strength = [hasLength, hasUpper, hasLower, hasNumber, hasSpecial]
                    .filter(Boolean).length;

                strengthBar.className = 'password-strength-bar';
                if (strength > 3) {
                    strengthBar.classList.add('strength-strong');
                } else if (strength > 2) {
                    strengthBar.classList.add('strength-medium');
                } else if (strength > 0) {
                    strengthBar.classList.add('strength-weak');
                }
            });

            // Verification code inputs
            const verificationInputs = document.querySelectorAll('.verification-inputs input');
            verificationInputs.forEach((input, index) => {
                input.addEventListener('input', () => {
                    if (input.value && index < verificationInputs.length - 1) {
                        verificationInputs[index + 1].focus();
                    }
                });

                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !input.value && index > 0) {
                        verificationInputs[index - 1].focus();
                    }
                });
            });

            function startResendTimer() {
                const button = elements.resendCode;
                const timer = elements.resendTimer;
                const timeSpan = timer.querySelector('span');
                let seconds = 30;

                button.disabled = true;
                timer.style.display = 'block';
                
                if (state.resendTimer) clearInterval(state.resendTimer);
                
                state.resendTimer = setInterval(() => {
                    seconds--;
                    timeSpan.textContent = seconds;
                    
                    if (seconds <= 0) {
                        clearInterval(state.resendTimer);
                        button.disabled = false;
                        timer.style.display = 'none';
                    }
                }, 1000);
            }

            function validateIdentifier(value) {
                if (state.isEmail) {
                    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                }
                return /^(?:\+966|0)5\d{8}$/.test(value);
            }

            function validatePassword(password) {
                return password.length >= 8 &&
                    /[A-Z]/.test(password) &&
                    /[a-z]/.test(password) &&
                    /[0-9]/.test(password) &&
                    /[^A-Za-z0-9]/.test(password);
            }

            function updateIdentifierInput() {
                const input = elements.identifier;
                input.type = state.isEmail ? 'email' : 'tel';
                input.placeholder = state.isEmail ? 'أدخل بريدك الإلكتروني' : 'أدخل رقم هاتفك';
                input.value = '';
                input.classList.remove('is-invalid');
                const icon = input.previousElementSibling.querySelector('i');
                icon.className = `bi bi-${state.isEmail ? 'envelope' : 'phone'}`;
            }

            function setStep(step) {
                state.step = step;
                Object.entries(elements.steps).forEach(([name, element]) => {
                    element.classList.toggle('d-none', name !== step);
                });

                // Update header text
                switch (step) {
                    case 'initial':
                        elements.title.textContent = 'مرحباً بك';
                        elements.subtitle.textContent = 'أدخل بريدك الإلكتروني أو رقم هاتفك للمتابعة';
                        elements.submitButton.querySelector('.button-text').textContent = 'متابعة';
                        break;
                    case 'verify':
                        elements.title.textContent = 'تحقق من حسابك';
                        elements.subtitle.textContent = `أدخل رمز التحقق المرسل إلى ${state.isEmail ? 'بريدك الإلكتروني' : 'هاتفك'}`;
                        elements.submitButton.querySelector('.button-text').textContent = 'تحقق';
                        break;
                    case 'register':
                        elements.title.textContent = 'إكمال التسجيل';
                        elements.subtitle.textContent = 'أدخل بياناتك لإكمال التسجيل';
                        elements.submitButton.querySelector('.button-text').textContent = 'إنشاء الحساب';
                        break;
                    case 'login':
                        elements.title.textContent = 'مرحباً بعودتك';
                        elements.subtitle.textContent = 'أدخل كلمة المرور للمتابعة';
                        elements.submitButton.querySelector('.button-text').textContent = 'تسجيل الدخول';
                        break;
                }
            }

            function setLoading(loading) {
                state.loading = loading;
                const button = elements.submitButton;
                button.disabled = loading;
                button.querySelector('.button-text').classList.toggle('d-none', loading);
                button.querySelector('.loading').classList.toggle('d-none', !loading);
            }

            function showToast(message, type = 'info') {
                const toast = document.createElement('div');
                toast.className = 'toast';
                toast.setAttribute('role', 'alert');
                toast.innerHTML = `
                    <div class="toast-body d-flex align-items-center">
                        <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} ms-2"></i>
                        ${message}
                    </div>
                `;
                
                document.querySelector('.toast-container').appendChild(toast);
                const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
                bsToast.show();
                
                toast.addEventListener('hidden.bs.toast', () => toast.remove());
            }

            // Phone number formatting
            elements.identifier.addEventListener('input', function(e) {
                if (!state.isEmail) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 0) {
                        if (value.length <= 3) {
                            value = `(${value}`;
                        } else if (value.length <= 6) {
                            value = `(${value.slice(0, 3)}) ${value.slice(3)}`;
                        } else {
                            value = `(${value.slice(0, 3)}) ${value.slice(3, 6)}-${value.slice(6, 10)}`;
                        }
                    }
                    e.target.value = value;
                }
            });
        });
    </script>