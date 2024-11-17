<!DOCTYPE html>
<html>

<head>
    {{-- required meta tags --}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    {{-- title --}}
    <title>{{ 'Invoice | ' . $userBs->website_title }}</title>

    {{-- fav icon --}}
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/img/' . $userBs->favicon) }}">

    {{-- styles --}}
    <link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap.min.css') }}">
</head>

<body>
    <div class="my-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="logo text-center">
                        <img src="{{ asset('assets/front/img/user/' . $userBs->logo) }}" alt="Company Logo">
                    </div>

                    <div class="bg-primary mt-4">
                        <h2 class="text-center text-light pt-2">
                            {{ __('ENROLMENT INVOICE') }}
                        </h2>
                    </div>

                    @php
                        $position = $enrolmentInfo->currency_text_position;
                        $currency = $enrolmentInfo->currency_text;
                    @endphp

                    <div class="row">
                        {{-- enrolment details start --}}
                        <div style="width: 50%;float: left;">
                            <div class="mt-4 mb-1">
                                <h4><strong>{{ __('Enrolment Details') }}</strong></h4>
                            </div>

                            <p>
                                <strong>{{ __('Order ID') . ': ' }}</strong>{{ '#' . $enrolmentInfo->order_id }}
                            </p>

                            <p>
                                <strong>{{ __('Enrolment Date') . ': ' }}</strong>{{ date_format($enrolmentInfo->created_at, 'M d, Y') }}
                            </p>

                            <p style="word-break:break-all;">
                                <strong>{{ __('Course') . ': ' }}</strong>{{ $courseInfo->title }}
                            </p>

                            <p>
                                <strong>{{ __('Course Price') . ': ' }}</strong>{{ $position == 'left' ? $currency . ' ' : '' }}{{ is_null($enrolmentInfo->course_price) ? '0.00' : $enrolmentInfo->course_price }}{{ $position == 'right' ? ' ' . $currency : '' }}
                            </p>

                            <p>
                                <strong>{{ __('Discount') . ': ' }}</strong>{{ $position == 'left' ? $currency . ' ' : '' }}{{ is_null($enrolmentInfo->discount) ? '0.00' : $enrolmentInfo->discount }}{{ $position == 'right' ? ' ' . $currency : '' }}
                            </p>

                            <p>
                                <strong>{{ __('Grand Total') . ': ' }}</strong>{{ $position == 'left' ? $currency . ' ' : '' }}{{ is_null($enrolmentInfo->grand_total) ? '0.00' : $enrolmentInfo->grand_total }}{{ $position == 'right' ? ' ' . $currency : '' }}
                            </p>

                            <p>
                                <strong>{{ __('Payment Method') . ': ' }}</strong>{{ is_null($enrolmentInfo->payment_method) ? '-' : $enrolmentInfo->payment_method }}
                            </p>

                            <p>
                                <strong>{{ __('Payment Status') . ': ' }}</strong>
                                @if ($enrolmentInfo->payment_status == 'completed')
                                    {{ __('Completed') }}
                                @elseif ($enrolmentInfo->payment_status == 'pending')
                                    {{ __('Pending') }}
                                @elseif ($enrolmentInfo->payment_status == 'rejected')
                                    {{ __('Rejected') }}
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                        {{-- enrolment details start --}}

                        {{-- billing details start --}}
                        <div style="width: 50%;float: left;">
                            <div class="mt-4 mb-1">
                                <h4><strong>{{ __('Billing Details') }}</strong></h4>
                            </div>

                            <p>
                                <strong>{{ __('Name') . ': ' }}</strong>{{ $enrolmentInfo->billing_first_name . ' ' . $enrolmentInfo->billing_last_name }}
                            </p>

                            <p>
                                <strong>{{ __('Email') . ': ' }}</strong>{{ $enrolmentInfo->billing_email }}
                            </p>

                            <p>
                                <strong>{{ __('Contact Number') . ': ' }}</strong>{{ $enrolmentInfo->billing_contact_number }}
                            </p>

                            <p>
                                <strong>{{ __('Address') . ': ' }}</strong>{{ $enrolmentInfo->billing_address }}
                            </p>

                            <p>
                                <strong>{{ __('City') . ': ' }}</strong>{{ $enrolmentInfo->billing_city }}
                            </p>

                            <p>
                                <strong>{{ __('State') . ': ' }}</strong>{{ is_null($enrolmentInfo->billing_state) ? '-' : $enrolmentInfo->billing_state }}
                            </p>

                            <p>
                                <strong>{{ __('Country') . ': ' }}</strong>{{ $enrolmentInfo->billing_country }}
                            </p>
                        </div>
                        {{-- billing details end --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
