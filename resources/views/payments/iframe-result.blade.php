<!doctype html>
<html lang="ar" dir="rtl">
<head>
    @php
        $statusMessage = [
            'success' => 'تمت العملية بنجاح',
            'failed' => 'فشلت عملية الدفع',
            'cancelled' => 'تم إلغاء عملية الدفع',
            'pending' => 'جاري تأكيد الدفع',
        ][$payload['status']];
        $statusDetail = [
            'success' => 'تم تأكيد عملية الدفع وإضافة الرصيد إلى حسابك.',
            'failed' => 'تعذر تأكيد عملية الدفع. لم تتم إضافة أي رصيد.',
            'cancelled' => 'تم إلغاء العملية ولم يتم خصم أو إضافة أي رصيد.',
            'pending' => 'لم يكتمل تأكيد العملية بعد. يمكنك متابعة الحالة من لوحة التحكم.',
        ][$payload['status']];
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $statusMessage }}</title>
    <style>
        :root {
            color: #4F9E8E;
            background: #FFFFFF;
        }

        html,
        body {
            min-height: 100%;
            margin: 0;
            color: #4F9E8E;
            background: #FFFFFF;
            font-family: Tahoma, Arial, sans-serif;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .result {
            width: min(90%, 420px);
            padding: 32px 20px;
            color: #4F9E8E;
            background: #FFFFFF;
            text-align: center;
        }

        .icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 20px;
            color: #4F9E8E;
        }

        .icon svg {
            width: 100%;
            height: 100%;
            color: #4F9E8E;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
        }

        h1,
        p,
        small {
            color: #4F9E8E;
        }

        h1 {
            margin: 0 0 12px;
            font-size: 24px;
        }

        p {
            margin: 8px 0;
            font-size: 17px;
        }

        small {
            display: block;
            margin-top: 18px;
            font-size: 13px;
            overflow-wrap: anywhere;
        }
    </style>
</head>
<body>
    <main class="result" role="status" aria-live="polite">
        <div class="icon" aria-hidden="true">
            @if ($payload['status'] === 'success')
                <svg viewBox="0 0 64 64"><circle cx="32" cy="32" r="27"></circle><path d="m20 33 8 8 17-19"></path></svg>
            @elseif ($payload['status'] === 'pending')
                <svg viewBox="0 0 64 64"><circle cx="32" cy="32" r="27"></circle><path d="M32 17v16l10 6"></path></svg>
            @elseif ($payload['status'] === 'cancelled')
                <svg viewBox="0 0 64 64"><circle cx="32" cy="32" r="27"></circle><path d="M22 32h20"></path></svg>
            @else
                <svg viewBox="0 0 64 64"><circle cx="32" cy="32" r="27"></circle><path d="m23 23 18 18m0-18L23 41"></path></svg>
            @endif
        </div>

        <h1>{{ $statusMessage }}</h1>
        <p>{{ $statusDetail }}</p>

        @if ($payload['status'] === 'success' && array_key_exists('credits_added', $payload))
            <p>تمت إضافة {{ $payload['credits_added'] }} رصيد</p>
        @endif

        @if (!empty($payload['transaction_id']))
            <small>رقم العملية: {{ $payload['transaction_id'] }}</small>
        @endif
    </main>

    <script>
        window.addEventListener('load', function () {
            var payload = {!! json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};
            var targetOrigin = {!! json_encode($targetOrigin, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};

            window.parent.postMessage(payload, targetOrigin);
        });
    </script>
</body>
</html>
