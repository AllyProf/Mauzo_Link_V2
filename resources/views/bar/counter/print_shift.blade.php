<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift Report #{{ $shift->shift_number }} - Mauzo Link POS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #940000;
            --primary-dark: #7a0000;
            --dark: #1a1a1a;
            --light: #f8f9fa;
            --border: #e0e0e0;
            --success: #1b5e20;
            --danger: #b71c1c;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
            line-height: 1.4;
        }

        @media screen {
            .app-bar {
                background: var(--dark);
                padding: 10px 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                position: sticky;
                top: 0;
                z-index: 1000;
                color: white;
            }

            .container {
                max-width: 850px;
                margin: 20px auto;
                background: white;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
                border-radius: 4px;
            }
        }

        .btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-back { background: #555; }

        .report-content { padding: 40px; }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .company-info h1 {
            margin: 0;
            font-size: 22px;
            color: var(--primary);
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .company-info p { margin: 2px 0; font-size: 13px; color: #555; }

        .shift-meta { text-align: right; }
        .shift-meta .badge {
            background: var(--primary);
            color: white;
            padding: 3px 10px;
            border-radius: 2px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .shift-meta h2 { margin: 8px 0 2px; font-size: 16px; color: var(--dark); }
        .shift-meta p { margin: 0; font-size: 12px; color: #777; }

        .section-title {
            text-align: center;
            font-size: 16px;
            text-transform: uppercase;
            font-weight: 800;
            color: var(--dark);
            margin: 25px 0;
            background: #fff8f8;
            padding: 10px;
            border: 1px solid #ffeaea;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }

        .info-card {
            border: 1px solid var(--border);
            border-radius: 4px;
        }

        .card-header {
            background: var(--primary);
            color: white;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .card-body { padding: 12px; }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 12px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 3px;
        }

        .info-row:last-child { border: none; margin: 0; }
        .info-label { color: #666; font-weight: 500; }
        .info-value { font-weight: 700; color: #000; }

        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .breakdown-table th {
            background: #333;
            color: white;
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }

        .breakdown-table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border);
            font-size: 12px;
        }

        .category-row {
            background: #f9f9f9;
            font-weight: 800;
            font-size: 11px !important;
            color: var(--primary);
            text-transform: uppercase;
        }

        .text-right { text-align: right; }

        .grand-total-row {
            background: var(--dark);
            color: white;
            font-weight: 700;
            font-size: 13px !important;
        }

        .grand-total-row td { border: none; padding: 15px 12px; }

        .diff-pos { color: var(--success); font-weight: 700; }
        .diff-neg { color: var(--danger); font-weight: 700; }

        .notes-section {
            margin-top: 25px;
            padding: 12px;
            background: #fffafa;
            border: 1px solid #ffe5e5;
            border-radius: 4px;
        }

        .notes-section h4 { margin: 0 0 5px; font-size: 13px; color: var(--primary); }
        .notes-section p { margin: 0; font-size: 12px; color: #444; }

        .report-footer {
            margin-top: 35px;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 15px;
            font-size: 11px;
            color: #888;
            letter-spacing: 0.5px;
        }

        @media print {
            .app-bar, .no-print { display: none !important; }
            body { background: white; }
            .container { width: 100% !important; max-width: none !important; margin: 0; box-shadow: none; border: none; }
            .report-content { padding: 0.5in; }
            .breakdown-table th { background: #000 !important; color: white !important; -webkit-print-color-adjust: exact; }
            .card-header { background: #940000 !important; color: white !important; -webkit-print-color-adjust: exact; }
            .grand-total-row { background: #000 !important; color: white !important; -webkit-print-color-adjust: exact; }
            .section-title { background: #fafafa !important; border: 1px solid #eee !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body onload="if(window.location.search.includes('print')) window.print()">

    <div class="app-bar">
        <div>
            <span style="font-weight: 800; font-size: 16px; letter-spacing: 1px;">MAUZO<span style="color: var(--primary);">LINK</span> POS</span>
        </div>
        <div>
            <button onclick="window.print()" class="btn">
                <i class="fa fa-print" style="margin-right: 5px;"></i> Print Report
            </button>
            <button onclick="window.history.back()" class="btn btn-back" style="margin-left: 10px;">
                <i class="fa fa-arrow-left" style="margin-right: 5px;"></i> Back
            </button>
        </div>
    </div>

    <div class="container">
        <div class="report-content">
            <div class="report-header">
                <div class="company-info">
                    <h1>MAUZO LINK POS</h1>
                    <p>Mauzo Link Arusha Branch</p>
                    <p>Plot No. 123, Opposite Main Market, Tanzania</p>
                    <p>Tel: +255 677 155 155 | Info@mauzolink.com</p>
                </div>
                <div class="shift-meta">
                    <span class="badge">Official Shift Report</span>
                    <h2>#{{ $shift->shift_number }}</h2>
                    <p>{{ now()->format('d M Y') }}</p>
                    <p style="font-size: 10px; opacity: 0.6; margin-top: 5px;">ID: {{ $shift->id }}</p>
                </div>
            </div>

            <div class="section-title">Cash & Revenue Reconciliation Report</div>

            <div class="info-grid">
                <!-- Staff Info -->
                <div class="info-card">
                    <div class="card-header">Officer Information</div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Officer Name</span>
                            <span class="info-value">{{ strtoupper($shift->staff->full_name) }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Current Role</span>
                            <span class="info-value">{{ strtoupper($shift->staff->role->name ?? 'Staff') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Shift Status</span>
                            <span class="info-value" style="color: {{ $shift->status === 'closed' ? 'var(--danger)' : 'var(--success)' }}">{{ strtoupper($shift->status) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Timeline Info -->
                <div class="info-card">
                    <div class="card-header">Session Timeline</div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Started At</span>
                            <span class="info-value">{{ $shift->opened_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Finished At</span>
                            <span class="info-value">{{ $shift->closed_at ? $shift->closed_at->format('d/m/Y H:i') : 'IN PROGRESS' }}</span>
                        </div>
                        <div class="info-row">
                            @php
                                $start = $shift->opened_at;
                                $end = $shift->closed_at ?: now();
                                $duration = $start->diff($end);
                            @endphp
                            <span class="info-label">Duration</span>
                            <span class="info-value">{{ $duration->h + ($duration->days * 24) }}h {{ $duration->i }}m</span>
                        </div>
                    </div>
                </div>
            </div>

            <table class="breakdown-table">
                <thead>
                    <tr>
                        <th width="45%">REVENUE CATEGORY / PLATFORM</th>
                        <th class="text-right">EXPECTED (TSH)</th>
                        <th class="text-right">ACTUAL (TSH)</th>
                        <th class="text-right">VARIANCE</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Cash Collections -->
                    <tr class="category-row">
                        <td colspan="4">Cash Collections</td>
                    </tr>
                    @php
                       if ($shift->status === 'open') {
                           $totalSalesCash = $orders->sum(function($o) {
                               return $o->orderPayments->where('payment_method', 'cash')->sum('amount');
                           });
                           $totalSalesDigital = $orders->sum(function($o) {
                               return $o->orderPayments->where('payment_method', '!=', 'cash')->sum('amount');
                           });
                       } else {
                           $totalSalesCash = $shift->total_sales_cash;
                           $totalSalesDigital = $shift->total_sales_digital;
                       }
                       
                       $cashHandover = $handover ? ($handover->payment_breakdown['cash'] ?? 0) : ($shift->status == 'open' ? $totalSalesCash : $shift->closing_balance);
                       $cashVariance = $cashHandover - $totalSalesCash;
                    @endphp
                    <tr>
                        <td style="padding-left: 25px; font-weight: 600;">CASH Collection</td>
                        <td class="text-right">{{ number_format($totalSalesCash) }}</td>
                        <td class="text-right">{{ number_format($cashHandover) }}</td>
                        <td class="text-right {{ $cashVariance < 0 ? 'diff-neg' : ($cashVariance > 0 ? 'diff-pos' : '') }}">
                            {{ $cashVariance > 0 ? '+' : '' }}{{ number_format($cashVariance) }}
                        </td>
                    </tr>

                    <!-- Specific Platform Breakdown -->
                    <tr class="category-row">
                        <td colspan="4">Payment Platform Breakdown</td>
                    </tr>
                    @if($handover && isset($handover->payment_breakdown))
                        @foreach($handover->payment_breakdown as $platform => $amount)
                            @if($platform != 'cash' && $amount > 0)
                            <tr>
                                <td style="padding-left: 25px;">{{ strtoupper(str_replace('_', ' ', $platform)) }} Collections</td>
                                <td class="text-right">{{ number_format($amount) }}</td>
                                <td class="text-right">{{ number_format($amount) }}</td>
                                <td class="text-right">0</td>
                            </tr>
                            @endif
                        @endforeach
                    @else
                        <!-- Fallback if handover not yet submitted -->
                        <tr>
                            <td colspan="4" style="text-align: center; color: #999; padding: 20px;">
                                Handover pending. Specific platform totals will appear here once submitted.
                            </td>
                        </tr>
                    @endif

                    <!-- Grand Total -->
                    @php 
                        $actualTotal = $handover ? $handover->amount : ($cashHandover + $totalSalesDigital);
                        $expectedTotal = $totalSalesCash + $totalSalesDigital;
                    @endphp
                    <tr class="grand-total-row">
                        <td>GRAND TOTAL SHIFT REVENUE</td>
                        <td class="text-right">TSh {{ number_format($expectedTotal) }}</td>
                        <td class="text-right">TSh {{ number_format($actualTotal) }}</td>
                        <td class="text-right {{ $actualTotal < $expectedTotal ? 'diff-neg' : ($actualTotal > $expectedTotal ? 'diff-pos' : '') }}">
                            {{ $actualTotal > $expectedTotal ? '+' : '' }}{{ number_format($actualTotal - $expectedTotal) }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div style="margin-top: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Waiter Order Volume -->
                <div class="info-card">
                    <div class="card-header">Waiter Sales Volume</div>
                    <div class="card-body">
                        @php
                           $waiterVolume = $orders->groupBy(function($order) {
                               return $order->waiter->full_name ?? 'Counter';
                           });
                        @endphp
                        @foreach($waiterVolume as $name => $staffOrders)
                        <div class="info-row">
                            <span class="info-label">{{ strtoupper($name) }}</span>
                            <span class="info-value">{{ count($staffOrders) }} ORDERS</span>
                        </div>
                        @endforeach
                        <div class="info-row" style="margin-top: 10px; border-top: 2px solid #333; padding-top: 5px;">
                            <span class="info-label" style="color: #000; font-weight: 800;">TOTAL SHIFT ORDERS</span>
                            <span class="info-value">{{ count($orders) }}</span>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <div class="card-header">Accountability Check</div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Current Performance Status</span>
                            <span class="info-value" style="color: {{ $actualTotal >= $expectedTotal ? 'var(--success)' : 'var(--danger)' }}">
                                {{ $actualTotal >= $expectedTotal ? 'COMPLIANT' : 'SHORTAGE ALERT' }}
                            </span>
                        </div>
                        @if($handover)
                        <div class="info-row">
                            <span class="info-label">Handover Ref</span>
                            <span class="info-value">#{{ $handover->id }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($shift->notes)
            <div class="notes-section" style="margin-top: 20px;">
                <h4>Auditor Remarks</h4>
                <p>{{ $shift->notes }}</p>
            </div>
            @endif

            <div class="report-footer">
                <p>Mauzo Link POS Arusha Branch — Shift Statement of Accounts</p>
                <p>Confidential Financial Report — {{ now()->format('H:i') }} | &copy; {{ date('Y') }}</p>
            </div>
        </div>
    </div>

</body>
</html>

