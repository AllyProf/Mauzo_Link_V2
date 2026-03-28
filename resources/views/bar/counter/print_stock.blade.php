<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Verification Sheet - {{ date('d M Y') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #940000;
            --primary-dark: #7a0000;
            --dark: #1a1a1a;
            --light: #f8f9fa;
            --border: #e0e0e0;
        }

        body {
            font-family: 'Century Gothic', 'Inter', system-ui, sans-serif;
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
            grid-template-columns: 2fr 1fr;
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

        .info-label { color: #666; font-weight: 500; }
        .info-value { font-weight: 700; color: #000; }

        .stock-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .stock-table th {
            background: #333;
            color: white;
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }

        .stock-table td {
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

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .physical-box {
            border: 1px solid #000;
            height: 25px;
            width: 100%;
            background: white;
        }

        .report-footer {
            margin-top: 35px;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 15px;
            font-size: 11px;
            color: #888;
        }

        @media print {
            .app-bar, .no-print { display: none !important; }
            body { background: white; }
            .container { width: 100% !important; max-width: none !important; margin: 0; box-shadow: none; border: none; }
            .report-content { padding: 0.5in; }
            .stock-table th { background: #000 !important; color: white !important; -webkit-print-color-adjust: exact; }
            .card-header { background: #940000 !important; color: white !important; -webkit-print-color-adjust: exact; }
            .physical-box { border: 1.5px solid #000 !important; }
        }
    </style>
</head>
<body onload="if(window.location.search.includes('print')) window.print()">

    <div class="app-bar">
        <div>
            <span style="font-weight: 800; font-size: 16px; letter-spacing: 1px;">MIGLOP <span style="color: var(--primary);">INVESTMENT</span></span>
        </div>
        <div>
            <button onclick="window.print()" class="btn">
                <i class="fa fa-print" style="margin-right: 5px;"></i> Print Stock Sheet
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
                    <h1>MIGLOP INVESTMENT</h1>
                    <p>Miglop Investment Arusha Branch</p>
                    <p>Plot No. 123, Opposite Main Market, Tanzania</p>
                    <p>Tel: +255 677 155 155 | info@miglop.com</p>
                </div>
                <div class="shift-meta">
                    <span class="badge">Official Stock Sheet</span>
                    <h2>SHIFT OPENING</h2>
                    <p>{{ date('d M Y') }}</p>
                </div>
            </div>

            <div class="section-title">Physical Stock Verification Sheet</div>

            <div class="info-grid">
                <div class="info-card">
                    <div class="card-header">Officer Information</div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Verification Officer</span>
                            <span class="info-value">{{ strtoupper($staff->full_name) }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Department</span>
                            <span class="info-value">BAR / COUNTER</span>
                        </div>
                    </div>
                </div>
                <div class="info-card">
                    <div class="card-header">Session Details</div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Printed At</span>
                            <span class="info-value">{{ date('H:i') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Sheet Status</span>
                            <span class="info-value">DRAFT / PENDING</span>
                        </div>
                    </div>
                </div>
            </div>

            <table class="stock-table">
                <thead>
                    <tr>
                        <th width="40%">PRODUCT ITEM</th>
                        <th width="15%">SIZE</th>
                        <th width="20%" class="text-center">SYSTEM STOCK</th>
                        <th width="25%" class="text-center">PHYSICAL COUNT</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $currentCat = null; 
                        $sortedVariants = collect($variants)->sortBy('category');
                    @endphp
                    @foreach($sortedVariants as $v)
                        @if($v['category'] !== $currentCat)
                            <tr class="category-row">
                                <td colspan="4">{{ strtoupper($v['category']) }}</td>
                            </tr>
                            @php $currentCat = $v['category']; @endphp
                        @endif
                        <tr>
                            <td style="padding-left: 20px; font-weight: 600;">{{ $v['product_name'] }} ({{ $v['variant_name'] }})</td>
                            <td class="text-center">{{ $v['measurement'] }}</td>
                            <td class="text-center font-weight-bold" style="color: var(--primary);">{{ $v['formatted_quantity'] }}</td>
                            <td><div class="physical-box"></div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top: 40px; border-top: 2px solid var(--primary); padding-top: 20px;">
                <div style="display: flex; justify-content: space-between;">
                    <div style="width: 45%;">
                        <div style="border-bottom: 1px solid #000; height: 30px; margin-bottom: 5px;"></div>
                        <div style="font-size: 11px; font-weight: 700;">OFFICER SIGNATURE / COUNTER STAFF</div>
                    </div>
                    <div style="width: 45%;">
                        <div style="border-bottom: 1px solid #000; height: 30px; margin-bottom: 5px;"></div>
                        <div style="font-size: 11px; font-weight: 700;">SUPERVISOR SIGNATURE / MANAGER</div>
                    </div>
                </div>
            </div>

            <div class="report-footer">
                <p>MIGLOP INVESTMENT Arusha Branch — Shift Opening Stock Statement</p>
                <p>Stock Verification Record — Generate at {{ date('H:i') }} | &copy; {{ date('Y') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
