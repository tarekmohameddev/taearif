
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
                        url('https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&q=80&w=1920');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .deposit-btn {
            transition: all 0.3s ease;
            border-width: 2px;
        }

        .deposit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .modal.fade .modal-dialog {
            transform: scale(0.8);
            transition: transform 0.3s ease-out;
        }

        .modal.show .modal-dialog {
            transform: scale(1);
        }

        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }

        .proceed-btn {
            transition: all 0.3s ease;
        }

        .proceed-btn:hover {
            transform: translateY(-1px);
         
        }

        .step-number {
            width: 30px;
            height: 30px;
            background-color: #28a745;
            color: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            font-weight: bold;
        }

        @media (max-width: 576px) {
            .modal-dialog {
                margin: 0.5rem;
            }
            
            .modal-body {
                padding: 1rem;
            }
        }
    </style>

            <button class="btn btn-light btn-lg deposit-btn px-5 py-3" 
                    data-bs-toggle="modal" 
                    data-bs-target="#depositModal">
                دفع العربون
            </button>

    <!-- Deposit Modal -->
    <div class="modal fade" id="depositModal" tabindex="-1" aria-labelledby="depositModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fs-4" id="depositModalLabel">دفع عربون العقار</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="step-number">١</span>
                            <h6 class="mb-0">عملية دفع آمنة</h6>
                        </div>
                        <p class="text-muted me-5">سيتم تحويلك إلى شريكنا المصرفي الآمن لإتمام دفع العربون.</p>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="step-number">٢</span>
                            <h6 class="mb-0">حجز العقار</h6>
                        </div>
                        <p class="text-muted me-5">بمجرد تأكيد العربون، سنقوم بحجز العقار حصرياً لك.</p>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="step-number">٣</span>
                            <h6 class="mb-0">مساعدة شخصية</h6>
                        </div>
                        <p class="text-muted me-5">سيتواصل معك مندوبنا خلال ٢٤ ساعة لإرشادك خلال الخطوات التالية.</p>
                    </div>

                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle ms-2"></i>
                        العربون قابل للاسترداد بالكامل خلال ٧٢ ساعة من الدفع.
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('user.pay.deposit') }}" class="btn btn-primary proceed-btn px-4">
                        المتابعة للدفع
                    </a>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>