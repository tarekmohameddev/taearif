{{-- resources/views/contracts/perfex_view.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $contract->subject ?? 'Contract Title' }}</title>

    {{-- Include Perfex & Tailwind & FontAwesome & Bootstrap CSS (kept as from the demo) --}}
    <link rel="shortcut icon" id="favicon" href="https://taearifdev.com/assets/front/img/672a403b4a9cb.png">
    <link rel="apple-touch-icon" id="favicon-apple-touch-icon" href="https://taearifdev.com/assets/front/img/672a403b4a9cb.png">
    <link rel="stylesheet" type="text/css" id="bootstrap-css" href="https://perfexcrm.com/demo/assets/plugins/bootstrap/css/bootstrap.min.css?v=3.2.1">
    <link rel="stylesheet" type="text/css" id="inter-font" href="https://perfexcrm.com/demo/assets/plugins/inter/inter.css?v=3.2.1">
    <link rel="stylesheet" type="text/css" id="datatables-css" href="https://perfexcrm.com/demo/assets/plugins/datatables/datatables.min.css?v=3.2.1">
    <link rel="stylesheet" type="text/css" id="fontawesome-css" href="https://perfexcrm.com/demo/assets/plugins/font-awesome/css/fontawesome.min.css?v=3.2.1">
    <link rel="stylesheet" type="text/css" id="fontawesome-brands" href="https://perfexcrm.com/demo/assets/plugins/font-awesome/css/brands.min.css?v=3.2.1">
    <link rel="stylesheet" type="text/css" id="fontawesome-solid" href="https://perfexcrm.com/demo/assets/plugins/font-awesome/css/solid.min.css?v=3.2.1">
    <link rel="stylesheet" type="text/css" id="fontawesome-regular" href="https://perfexcrm.com/demo/assets/plugins/font-awesome/css/regular.min.css?v=3.2.1">
    <link rel="stylesheet" type="text/css" id="datetimepicker-css" href="https://perfexcrm.com/demo/assets/plugins/datetimepicker/jquery.datetimepicker.min.css?v=3.2.1">
    <link rel="stylesheet" type="text/css" id="bootstrap-select-css" href="https://perfexcrm.com/demo/assets/plugins/bootstrap-select/css/bootstrap-select.min.css?v=3.2.1">
    <link rel="stylesheet" type="text/css" id="lightbox-css" href="https://perfexcrm.com/demo/assets/plugins/lightbox/css/lightbox.min.css?v=3.2.1">
    <link rel="stylesheet" type="text/css" id="colorpicker-css" href="https://perfexcrm.com/demo/assets/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css?v=3.2.1">
    <link rel="stylesheet" type="text/css" id="tailwind-css" href="https://perfexcrm.com/demo/assets/builds/tailwind.css?v=3.2.1">
    <link rel="stylesheet" type="text/css" id="theme-css" href="https://perfexcrm.com/demo/assets/themes/perfex/css/style.min.css?v=3.2.1">

    <meta name="robots" content="noindex">
</head>

<body class="customers chrome contract contract-view identity-confirmation">
    {{-- jQuery first (Perfex uses it heavily) --}}
    <script src="https://perfexcrm.com/demo/assets/plugins/jquery/jquery.min.js"></script>

    {{-- Perfex CSRF logic (remove or replace with your own if needed) --}}
    <script>
        var csrfData = {
            "formatted": {
                "csrf_token_name": "{{ csrf_token() }}"
            },
            "token_name": "csrf_token_name",
            "hash": "{{ csrf_token() }}"
        };

        function csrf_jquery_ajax_setup() {
            $.ajaxSetup({
                data: csrfData.formatted
            });
            $(document).ajaxError(function(event, request) {
                if (request.status === 419) {
                    alert('Page expired, please refresh!');
                }
            });
        }
        $(function() {
            csrf_jquery_ajax_setup();
        });
    </script>

    <div id="wrapper">
        <div id="content">
            <div class="container">
                <div class="row">
                    {{-- Optional row if you want something top-level here --}}
                </div>
            </div>

            <div class="container">
                <div class="row">
                    <div class="mtop15 preview-top-wrapper">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mbot30">
                                    <div class="contract-html-logo">
                                        {{-- Example: your company logo --}}
                                        <a href="{{ url('/') }}" class="logo img-responsive">
                                            <img src="https://taearifdev.com/assets/front/img/user/6727bcb973b51.png" class="img-responsive" alt="Perfex CRM">
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                        </div>

                        <!-- Sticky Top Bar -->
                        <div class="top" data-sticky data-sticky-class="preview-sticky-header">
                            <div class="container preview-sticky-container">
                                <div class="sm:tw-flex sm:tw-justify-between -tw-mx-4">
                                    <div class="sm:tw-self-end tw-inline-flex">
                                        {{-- Contract Title & Type --}}
                                        <h4 class="tw-my-0 tw-font-bold contract-html-subject">
                                            {{ $contract->subject }}<br />
                                            <small>{{ $contract->contract_type }}</small>
                                        </h4>
                                    </div>
                                    <div class="tw-flex tw-items-end tw-space-x-2 tw-mt-3 sm:tw-mt-0">
                                        {{-- Download Form --}}
                                        <form action="{{ route('contracts.download', $contract->id) }}" method="get" accept-charset="utf-8">
                                            <button type="submit" class="btn btn-default action-button contract-html-pdf">
                                                <i class="fa-regular fa-file-pdf"></i>
                                                تحميل PDF
                                            </button>
                                        </form>

                                        {{-- Sign Button (Only show if not signed) --}}
                                        @if(!$contract->is_signed)
                                        <button type="button" id="accept_action" class="btn btn-success action-button" data-toggle="modal" data-target="#identityConfirmationModal">
                                            <i class="fa-solid fa-signature"></i>
                                            Sign
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Sticky Top Bar -->
                    </div>

                    <!-- Main Row: Left = Contract Content, Right = Summary & Discussion -->
                    <div class="row">
                        <div class="col-md-8 contract-left">
                            <div class="panel_s tw-mt-6 sm:tw-mt-8">
                                <div class="panel-body tc-content contract-html-content">
                                    {{-- Contract Description --}}
                                    {!! $contract->description !!}

                                    <br><br>
                                    <p>
                                        <strong>حالة  التوقيع:</strong>
                                        @if($contract->is_signed)
                                        <span class="badge bg-success">تم التوقيع</span>
                                        @else
                                        <span class="badge bg-warning">لم يتم التوقيع</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 contract-right">
                            <div class="inner tw-mt-8 contract-html-tabs">
                                <ul class="nav nav-tabs nav-tabs-flat mbot15" role="tablist">
                                    <li role="presentation" class="active">
                                        <a href="#summary" aria-controls="summary" role="tab" data-toggle="tab" class="tw-flex tw-justify-center tw-space-x-1">
                                            <i class="fa-regular fa-file-lines" aria-hidden="true"></i>
                                            <span>ملخص</span>
                                        </a>
                                    </li>
                                    <li role="presentation">
                                        <a href="#discussion" aria-controls="discussion" role="tab" data-toggle="tab" class="tw-flex tw-justify-center tw-space-x-1 hidden">
                                            <i class="fa-regular fa-comment" aria-hidden="true"></i>
                                            <span>Discussion</span>
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <!-- SUMMARY TAB -->
                                    <div role="tabpanel" class="tab-pane active" id="summary">
                                        <address class="contract-html-company-info tw-text-normal hidden">
                                            {{-- Example company info or dynamic content --}}
                                            <b style="color:black" class="company-name-formatted">Perfex Ltd</b>
                                            <br /><br />
                                        </address>
                                        <div class="row mtop20">
                                            <div class="col-md-12 contract-value">
                                                <h4 class="bold tw-mb-3">
                                                    قيمة العقد:
                                                    <br>
                                                    ${{ number_format($contract->contract_value, 2) }}
                                                </h4>
                                            </div>
                                            <div class="tw-text-normal col-md-5 text-muted contract-number"># العقد رقم </div>
                                            <div class="tw-text-normal col-md-7 contract-number tw-text-neutral-700">
                                                {{ $contract->id }}
                                            </div>
                                            <div class="tw-text-normal col-md-5 text-muted contract-start-date">تاريخ البدء</div>
                                            <div class="tw-text-normal col-md-7 contract-start-date tw-text-neutral-700">
                                                {{ $contract->start_date }}
                                            </div>
                                            <div class="tw-text-normal col-md-5 text-muted contract-end-date">تاريخ الانتهاء</div>
                                            <div class="tw-text-normal col-md-7 contract-end-date tw-text-neutral-700">
                                                {{ $contract->end_date ?? 'N/A' }}
                                            </div>
                                            <div class="tw-text-normal col-md-5 text-muted contract-type">نوع العقد</div>
                                            <div class="tw-text-normal col-md-7 contract-type tw-text-neutral-700">
                                                {{ $contract->contract_type }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- DISCUSSION TAB -->
                                    <div role="tabpanel" class="tab-pane" id="discussion">
                                        <form action="#" method="post" accept-charset="utf-8">
                                            @csrf
                                            <div class="contract-comment">
                                                <textarea name="content" rows="4" class="form-control"></textarea>
                                                <button type="submit" class="btn btn-primary mtop10 pull-right" data-loading-text="Please wait...">Add Comment
                                                </button>
                                            </div>
                                        </form>
                                        <div class="clearfix"></div>

                                        {{-- Display existing comments --}}
                                        <div class="contract_comment mtop10 mbot20" data-commentid="#">
                                            {{-- Example user avatar --}}
                                            <img src="{{ $comment->user->avatar_url ?? 'https://via.placeholder.com/40' }}" class="staff-profile-image-small media-object img-circle pull-left mright10" />
                                            <div class="media-body valign-middle">
                                                <div class="mtop5">
                                                    <b> ## </b>
                                                    <small class="mtop10 text-muted">
                                                    </small>
                                                </div>
                                                <br>
                                                <br><br>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Optional Footer Buttons: Back, Edit, Delete -->
                            <div class="mtop15 preview-top-wrapper" style="margin:20px 0 20px 0 ;">
                                <a href="{{ route('contracts.index') }}" class="btn btn-secondary mb-3">Back to Contracts</a>
                                <a href="{{ route('contracts.edit', $contract->id) }}" class="btn btn-default">Edit Contract</a>
                                <form action="{{ route('contracts.destroy', $contract->id) }}" method="POST" class="d-inline" style="margin-top: 20px;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this contract?')">
                                        Delete Contract
                                    </button>
                                </form>
                            </div>
                            <img src="{{ asset('storage/'.$contract->signature_path) }}" alt="Signature">

                        </div>
                    </div>
                </div>
            </div>

            <!-- The signature modal, only shown if user clicks "Sign" -->
            <div class="modal fade" tabindex="-1" role="dialog" id="identityConfirmationModal">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        {{-- Example form for e-signing --}}
                        <form action="{{ route('contracts.sign', $contract->id) }}" id="identityConfirmationForm" class="form-horizontal" method="post" accept-charset="utf-8">
                            @csrf
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title">Signature &amp; Confirmation Of Identity</h4>
                            </div>
                            <div class="modal-body">

                                {{-- Your sign logic can store first name, last name, email, etc. --}}
                                <div id="identity_fields">
                                    <div class="form-group">
                                        <label for="signed_name" class="control-label col-sm-2">
                                            <span class="text-left inline-block full-width">
                                             signed name
                                            </span>
                                        </label>
                                        <div class="col-sm-10">
                                            <input type="text" name="signed_name" id="signed_name" class="form-control" required>
                                        </div>
                                    </div>

                                    <p class="bold" id="signatureLabel">Signature</p>
                                    <div class="signature-pad--body">
                                        <canvas id="signature" height="130" width="550"></canvas>
                                    </div>
                                    <input type="text" name="signature" id="signatureInput" style="width:1px; height:1px; border:0px;" tabindex="-1">
                                    <div class="dispay-block">
                                        <button type="button" class="btn btn-default btn-xs clear" tabindex="-1" data-action="clear">Clear</button>
                                        <button type="button" class="btn btn-default btn-xs" tabindex="-1" data-action="undo">Undo</button>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <p class="text-left text-muted e-sign-legal-text">
                                    By clicking on "Sign", I consent to be legally bound
                                    by this electronic representation of my signature.
                                </p>
                                <hr>
                                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                <button type="submit" data-loading-text="Please wait..." autocomplete="off" data-form="#identityConfirmationForm" class="btn btn-success">Sign</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- /signature modal -->
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <span class="copyright-footer">
                        2025 Copyright tearif
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Perfex / Bootstrap / JS dependencies (from the original snippet) -->
    <script type="text/javascript" src="https://perfexcrm.com/demo/assets/plugins/bootstrap/js/bootstrap.min.js?v=3.2.1"></script>
    <script type="text/javascript" src="https://perfexcrm.com/demo/assets/plugins/datatables/datatables.min.js?v=3.2.1"></script>
    <script type="text/javascript" src="https://perfexcrm.com/demo/assets/plugins/jquery-validation/jquery.validate.min.js?v=3.2.1"></script>
    <script type="text/javascript" src="https://perfexcrm.com/demo/assets/builds/bootstrap-select.min.js?v=3.2.1"></script>
    <script type="text/javascript" src="https://perfexcrm.com/demo/assets/plugins/datetimepicker/jquery.datetimepicker.full.min.js?v=3.2.1"></script>
    <script type="text/javascript" src="https://perfexcrm.com/demo/assets/plugins/Chart.js/Chart.min.js?v=3.2.1"></script>
    <script type="text/javascript" src="https://perfexcrm.com/demo/assets/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js?v=3.2.1"></script>
    <script type="text/javascript" src="https://perfexcrm.com/demo/assets/plugins/lightbox/js/lightbox.min.js?v=3.2.1"></script>
    <script type="text/javascript" src="https://perfexcrm.com/demo/assets/builds/common.js?v=3.2.1"></script>
    <script type="text/javascript" src="https://perfexcrm.com/demo/assets/themes/perfex/js/global.min.js?v=3.2.1"></script>
    <script type="text/javascript" src="https://perfexcrm.com/demo/assets/plugins/sticky/sticky.js?v=3.2.1"></script>
    <script type="text/javascript" src="https://perfexcrm.com/demo/assets/plugins/signature-pad/signature_pad.min.js?v=3.2.1" id="signature-pad"></script>

    <script>
        // Perfex sticky header
        $(function() {
            new Sticky('[data-sticky]');

            // Make tables responsive
            $(".contract-left table").wrap("<div class='table-responsive'></div>");

            // Lightbox for images
            $('.contract-html-content img').wrap(function() {
                return '<a href="' + $(this).attr('src') + '" data-lightbox="contract"></a>';
            });
        });

        // Signature Pad Script (similar to Perfex)
        $(function() {
            var canvas = document.getElementById("signature");
            var clearButton = document.querySelector("[data-action=clear]");
            var undoButton = document.querySelector("[data-action=undo]");
            var signaturePad = new SignaturePad(canvas, {
                maxWidth: 2,
                onEnd: function() {
                    updateSignatureInput();
                }
            });

            // Clears the signature
            clearButton.addEventListener("click", function() {
                signaturePad.clear();
                updateSignatureInput();
            });

            // Undo last stroke
            undoButton.addEventListener("click", function() {
                var data = signaturePad.toData();
                if (data) {
                    data.pop(); // remove last dot or line
                    signaturePad.fromData(data);
                    updateSignatureInput();
                }
            });

            function updateSignatureInput() {
                var input = document.getElementById('signatureInput');
                var label = $('#signatureLabel');
                label.removeClass('text-danger');

                if (signaturePad.isEmpty()) {
                    label.addClass('text-danger');
                    input.value = '';
                    return;
                }

                // Convert signature to base64
                var base64 = signaturePad.toDataURL();
                base64 = base64.split(',')[1];
                input.value = base64;
            }

            // On form submit, ensure signature is set (if required)
            $('#identityConfirmationForm').submit(function(e) {
                if (signaturePad.isEmpty()) {
                    e.preventDefault();
                    alert('Please sign before submitting!');
                }
            });
        });
    </script>
</body>

</html>
