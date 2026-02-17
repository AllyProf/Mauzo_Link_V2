<!DOCTYPE html>
<html>
<head>
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #940000;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #940000;
            margin: 0;
        }
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .bill-to, .invoice-details {
            width: 48%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #940000;
            color: white;
        }
        .total {
            text-align: right;
            font-size: 1.2em;
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>MAUZOLINK</h1>
        <p>Point of Sale System</p>
        <p>Invoice</p>
    </div>

    <div class="invoice-info">
        <div class="bill-to">
            <h3>Bill To:</h3>
            <p>
                <strong>{{ $invoice->user->name }}</strong><br>
                {{ $invoice->user->business_name }}<br>
                {{ $invoice->user->email }}<br>
                {{ $invoice->user->phone }}<br>
                {{ $invoice->user->address }}, {{ $invoice->user->city }}
            </p>
        </div>
        <div class="invoice-details">
            <h3>Invoice Details:</h3>
            <p>
                <strong>Invoice #:</strong> {{ $invoice->invoice_number }}<br>
                <strong>Issue Date:</strong> {{ $invoice->issued_at->format('F d, Y') }}<br>
                <strong>Due Date:</strong> {{ $invoice->due_date->format('F d, Y') }}<br>
                <strong>Status:</strong> 
                @if($invoice->status === 'pending')
                    Pending Payment
                @elseif($invoice->status === 'pending_verification' || ($invoice->status === 'paid' && !$invoice->verified_at))
                    Awaiting Verification
                @elseif($invoice->status === 'verified')
                    Verified
                @else
                    {{ ucfirst($invoice->status) }}
                @endif
            </p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>{{ $invoice->plan->name }}</strong><br>
                    <small>{{ $invoice->plan->description }}</small>
                </td>
                <td style="text-align: right;">{{ $invoice->formatted_amount }}</td>
            </tr>
            <tr>
                <td class="total">Total:</td>
                <td class="total" style="text-align: right;">{{ $invoice->formatted_amount }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p><strong>EmCa Technologies</strong></p>
        <p>Ben Bella Street, Moshi, Tanzania</p>
        <p>Phone: +255 749 719 998 | Email: emca@emca.tech</p>
        <p>www.emca.tech</p>
        <p style="margin-top: 20px; font-size: 0.8em;">
            Reg. No: 181103264 | TIN: 181-103-264 | License: BL01408832024-2500004066
        </p>
    </div>
</body>
</html>

