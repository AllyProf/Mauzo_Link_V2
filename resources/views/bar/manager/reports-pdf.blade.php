<!DOCTYPE html>
<html>
<head>
    <title>Financial Report - {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            font-size: 12px;
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
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .report-info {
            margin-bottom: 20px;
            text-align: center;
        }
        .report-info strong {
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            page-break-inside: auto;
        }
        thead {
            display: table-header-group;
        }
        tbody {
            display: table-row-group;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #940000;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        td {
            text-align: right;
        }
        td:first-child {
            text-align: left;
        }
        tfoot th, tfoot td {
            background-color: #f5f5f5;
            font-weight: bold;
            border-top: 2px solid #940000;
        }
        .section-title {
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: bold;
            color: #940000;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        .summary-box {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .summary-box h3 {
            margin-top: 0;
            color: #940000;
            font-size: 14px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .summary-label {
            font-weight: bold;
        }
        .summary-value {
            color: #940000;
        }
        @media print {
            body {
                padding: 10px;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>MAUZOLINK</h1>
        <p>Point of Sale System</p>
        <p><strong>Financial Report</strong></p>
    </div>

    <div class="report-info">
        <strong>Report Period:</strong> {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}<br>
        <strong>Generated:</strong> {{ \Carbon\Carbon::now()->format('M d, Y h:i A') }}
    </div>

    <!-- Summary Box -->
    <div class="summary-box">
        <h3>Summary</h3>
        <div class="summary-row">
            <span class="summary-label">Total Revenue:</span>
            <span class="summary-value">TSh {{ number_format($totalRevenue, 0) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Cash:</span>
            <span class="summary-value">TSh {{ number_format($totalCash, 0) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Mobile Money:</span>
            <span class="summary-value">TSh {{ number_format($totalMobileMoney, 0) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Orders:</span>
            <span class="summary-value">{{ number_format($totalOrders, 0) }}</span>
        </div>
    </div>

    <!-- Daily Revenue Report -->
    <div class="section-title">Daily Revenue Report</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Revenue</th>
                <th>Cash</th>
                <th>Mobile Money</th>
                <th>Orders</th>
            </tr>
        </thead>
        <tbody>
            @foreach($revenueByDay as $day)
            <tr>
                <td>{{ $day['date_formatted'] }}</td>
                <td>TSh {{ number_format($day['revenue'], 0) }}</td>
                <td>TSh {{ number_format($day['cash'], 0) }}</td>
                <td>TSh {{ number_format($day['mobile_money'], 0) }}</td>
                <td>{{ $day['orders_count'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th>Total</th>
                <th>TSh {{ number_format(collect($revenueByDay)->sum('revenue'), 0) }}</th>
                <th>TSh {{ number_format(collect($revenueByDay)->sum('cash'), 0) }}</th>
                <th>TSh {{ number_format(collect($revenueByDay)->sum('mobile_money'), 0) }}</th>
                <th>{{ collect($revenueByDay)->sum('orders_count') }}</th>
            </tr>
        </tfoot>
    </table>

    <!-- Revenue by Waiter -->
    @if($revenueByWaiter->count() > 0)
    <div class="section-title">Revenue by Waiter</div>
    <table>
        <thead>
            <tr>
                <th>Waiter</th>
                <th>Total Revenue</th>
                <th>Bar Sales</th>
                <th>Food Sales</th>
                <th>Orders</th>
            </tr>
        </thead>
        <tbody>
            @foreach($revenueByWaiter as $waiterData)
            <tr>
                <td>
                    <strong>{{ $waiterData['waiter']->full_name }}</strong><br>
                    <small style="color: #666;">{{ $waiterData['waiter']->email }}</small>
                </td>
                <td>TSh {{ number_format($waiterData['total_revenue'], 0) }}</td>
                <td>TSh {{ number_format($waiterData['bar_sales'], 0) }}</td>
                <td>TSh {{ number_format($waiterData['food_sales'], 0) }}</td>
                <td>{{ $waiterData['orders_count'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th>Total</th>
                <th>TSh {{ number_format($revenueByWaiter->sum('total_revenue'), 0) }}</th>
                <th>TSh {{ number_format($revenueByWaiter->sum('bar_sales'), 0) }}</th>
                <th>TSh {{ number_format($revenueByWaiter->sum('food_sales'), 0) }}</th>
                <th>{{ $revenueByWaiter->sum('orders_count') }}</th>
            </tr>
        </tfoot>
    </table>
    @else
    <div style="padding: 20px; text-align: center; color: #666;">
        No waiter revenue data available for the selected period.
    </div>
    @endif

    <div class="footer">
        <p><strong>EmCa Technologies</strong></p>
        <p>Ben Bella Street, Moshi, Tanzania</p>
        <p>Phone: +255 749 719 998 | Email: emca@emca.tech</p>
        <p>www.emca.tech</p>
        <p style="margin-top: 10px; font-size: 0.9em;">
            This is a computer-generated report. No signature required.
        </p>
    </div>
</body>
</html>




