<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice</title>
    <style>
        @page {
            margin: 0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        
        .invoice-container {
            padding: 40px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 40px;
            margin: -40px -40px 30px -40px;
            display: table;
            width: calc(100% + 80px);
        }
        
        .header-content {
            display: table-row;
        }
        
        .logo-section {
            display: table-cell;
            vertical-align: middle;
            width: 40%;
        }
        
        .logo {
            max-height: 50px;
            max-width: 180px;
            background: white;
            padding: 8px;
            border-radius: 5px;
        }
        
        .invoice-title-section {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 60%;
        }
        
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
        }
        
        .invoice-subtitle {
            font-size: 11px;
            opacity: 0.9;
            margin-top: 3px;
        }
        
        .info-section {
            margin-bottom: 30px;
            display: table;
            width: 100%;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-block {
            display: table-cell;
            width: 50%;
            padding-right: 20px;
            vertical-align: top;
        }
        
        .info-block:last-child {
            padding-right: 0;
            padding-left: 20px;
        }
        
        .section-title {
            color: #667eea;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 12px;
            padding-bottom: 5px;
            border-bottom: 2px solid #667eea;
        }
        
        .info-item {
            margin-bottom: 8px;
            font-size: 11px;
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 100px;
        }
        
        .info-value {
            color: #333;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 12px;
            background: #28a745;
            color: white;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .details-section {
            margin-top: 30px;
        }
        
        .details-title {
            color: #667eea;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #667eea;
        }
        
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .details-table thead {
            background: #f8f9fa;
        }
        
        .details-table th {
            padding: 12px;
            text-align: left;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #667eea;
            font-size: 11px;
        }
        
        .details-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 11px;
        }
        
        .summary-section {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .summary-row {
            padding: 8px 0;
            font-size: 12px;
            display: table;
            width: 100%;
        }
        
        .summary-label {
            font-weight: bold;
            color: #555;
            display: table-cell;
            width: 70%;
        }
        
        .summary-value {
            color: #333;
            text-align: right;
            display: table-cell;
            width: 30%;
        }
        
        .summary-total {
            border-top: 2px solid #667eea;
            margin-top: 10px;
            padding-top: 10px;
        }
        
        .summary-total .summary-label,
        .summary-total .summary-value {
            font-size: 16px;
            font-weight: bold;
            color: #667eea;
        }
        
        .discount-highlight {
            color: #28a745;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 40px;
            padding: 20px;
            background: #f8f9fa;
            text-align: center;
            border-top: 3px solid #667eea;
        }
        
        .footer-thanks {
            font-size: 14px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .footer-company {
            font-size: 13px;
            color: #333;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="logo-section">
                    @if($bs->logo)
                        <img src="{{ public_path('assets/front/img/'.$bs->logo) }}" alt="Logo" class="logo">
                    @endif
                </div>
                <div class="invoice-title-section">
                    <div class="invoice-title">INVOICE</div>
                    <div class="invoice-subtitle">Payment Receipt</div>
                </div>
            </div>
        </div>

        <!-- Info Section -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-block">
                    <div class="section-title">Customer Information</div>
                    <div class="info-item">
                        <span class="info-label">Name:</span>
                        <span class="info-value">{{ $member['first_name'] }} {{ $member['last_name'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Username:</span>
                        <span class="info-value">{{ $member['username'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value">{{ $member['email'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone:</span>
                        <span class="info-value">{{ $phone }}</span>
                    </div>
                </div>
                
                <div class="info-block">
                    <div class="section-title">Invoice Details</div>
                    <div class="info-item">
                        <span class="info-label">Order ID:</span>
                        <span class="info-value">#{{ $order_id }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date:</span>
                        <span class="info-value">{{ \Illuminate\Support\Carbon::now()->format('d/m/Y') }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Payment Method:</span>
                        <span class="info-value">{{ $request['payment_method'] }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status:</span>
                        <span class="status-badge">PAID</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Package Details -->
        <div class="details-section">
            <div class="details-title">Package Details</div>
            
            <table class="details-table">
                <thead>
                    <tr>
                        <th style="width: 30%;">Package</th>
                        <th style="width: 18%;">Start Date</th>
                        <th style="width: 18%;">Expiry Date</th>
                        <th style="width: 15%;">Currency</th>
                        <th style="width: 19%; text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>{{ $package_title }}</strong></td>
                        <td>{{ $request['start_date'] }}</td>
                        <td>{{ \Carbon\Carbon::parse($request['expire_date'])->format('Y') == "9999" ? "Lifetime" : $request['expire_date'] }}</td>
                        <td>{{ $base_currency_text }}</td>
                        <td style="text-align: right;"><strong>{{ $amount == 0 ? "Free" : number_format($amount, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Summary -->
        <div class="summary-section">
            @if ($membership->discount > 0)
            <div class="summary-row">
                <div class="summary-label">Package Price:</div>
                <div class="summary-value">{{ $membership->package_price == 0 ? "Free" : number_format($membership->package_price, 2) . ' ' . $base_currency_text }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Discount:</div>
                <div class="summary-value discount-highlight">- {{ number_format($membership->discount, 2) }} {{ $base_currency_text }}</div>
            </div>
            @endif
            
            <div class="summary-row summary-total">
                <div class="summary-label">TOTAL AMOUNT:</div>
                <div class="summary-value">{{ $amount == 0 ? "Free" : number_format($amount, 2) . ' ' . $base_currency_text }}</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-thanks">Thank you for your business!</div>
            <div class="footer-company">{{ $bs->website_title }}</div>
        </div>
    </div>
</body>
</html>
