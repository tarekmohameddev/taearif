<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="utf-8" />
    <title>عقد #{{ $contract->id }}</title>
    <style>
        body {
            font-family: DejaVuSans, sans-serif; /* for Arabic or universal glyphs */
            direction: rtl; /* Ensure text direction is right-to-left */
            text-align: right; /* Align text to the right */
        }
        .signature-block {
            margin-top: 50px;
        }
        .signature-info {
            text-align: right;
        }
        /* Example styling—adjust as needed */
    </style>
</head>
<body>
    <h1> عقد  #{{ $contract->id }}</h1>
    <p>{{ $contract->description }}</p>

    <p>التوقيع المصرح به ______________</p>

    @if($contract->is_signed)
        <!-- Show signature on the right side, plus name, date, IP, etc. -->
        <div class="signature-block">
            <div class="signature-info">
                <strong>توقيع (العميل)</strong><br>
                اسم الموقع: {{ $contract->signed_name }}<br>
                تاريخ التوقيع: {{ $contract->signed_date }}<br>
                IP Address: {{ $contract->signed_ip }}
            </div>

            <!-- Display the signature image from storage -->
            <br><br>
            <img src="{{ public_path('storage/'.$contract->signature_path) }}" alt="Signature">

        </div>
    @else
        <p style="color:red;">لم يتم التوقيع بعد</p>
    @endif

</body>
</html>
