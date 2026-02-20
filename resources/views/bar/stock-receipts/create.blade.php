@extends('layouts.dashboard')

@section('title', 'New Stock Receipt')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-download text-success"></i> Stock Reception</h1>
    <p>Transfer product from Supplier to Warehouse stock</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.stock-receipts.index') }}">Stock Receipts</a></li>
    <li class="breadcrumb-item active">Receiving</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <form method="POST" action="{{ route('bar.stock-receipts.store') }}" id="stockReceiptForm">
      @csrf
      
      <div class="row">
        <!-- Main Form Column -->
        <div class="col-md-9">
          <div class="tile shadow-sm border-0 mb-3">
             <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                <h3 class="tile-title mb-0"><i class="fa fa-truck mr-2 text-primary"></i> Receipt Details</h3>
                <div class="d-flex align-items-center">
                   <div class="mr-3 text-right">
                      <small class="text-muted d-block text-uppercase font-weight-bold">Batch Target (Crates)</small>
                       <div class="input-group input-group-sm" style="width: 200px;">
                          <input type="number" id="batch_target_qty" class="form-control font-weight-bold text-center border-primary" placeholder="Crates" value="0" style="border-radius: 4px;">
                       </div>
                   </div>
                   <div class="text-right">
                      <small class="text-muted d-block text-uppercase font-weight-bold">Status</small>
                      <span id="batch_status_badge" class="badge badge-secondary px-3 py-2">Waiting Data</span>
                   </div>
                </div>
             </div>

             <div class="row">
                <div class="col-md-5">
                   <div class="form-group">
                      <label class="font-weight-bold small text-uppercase">Supplier / Distributor *</label>
                      <select class="form-control select2" name="supplier_id" id="supplier_id" required>
                         <option value="">-- Select Supplier --</option>
                         @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->company_name }}</option>
                         @endforeach
                      </select>
                   </div>
                </div>
                <div class="col-md-3">
                   <div class="form-group">
                      <label class="font-weight-bold small text-uppercase">Receiving Date *</label>
                      <input type="date" class="form-control" name="received_date" id="received_date" value="{{ date('Y-m-d') }}" required>
                   </div>
                </div>
                <div class="col-md-4">
                   <div class="form-group">
                      <label class="font-weight-bold small text-uppercase text-primary">Load Inventory By Group / Distributor</label>
                      <select class="form-control border-primary" id="category_filter" style="border-width: 2px;">
                         <option value="">-- Choose Distributor / Group --</option>
                         @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                         @endforeach
                      </select>
                   </div>
                </div>
             </div>

             <!-- Bulk Price Apply Tool -->
             <div class="tile shadow-sm border-0 mb-3 p-0 overflow-hidden" id="bulk_section_tile">
                <div class="bg-light p-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 text-dark font-weight-bold text-uppercase small"><i class="fa fa-magic mr-1 text-primary"></i> Smart Bulk Pricing Mode</h6>
                        <p class="smallest text-muted mb-0">Apply one price to all loaded items instantly</p>
                    </div>
                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                        <label class="btn btn-outline-secondary btn-sm active" id="bulk_off_label">
                            <input type="radio" name="bulk_toggle" value="off" checked> <i class="fa fa-lock"></i> OFF
                        </label>
                        <label class="btn btn-outline-primary btn-sm" id="bulk_on_label">
                            <input type="radio" name="bulk_toggle" value="on"> <i class="fa fa-bolt"></i> ENABLE BULK
                        </label>
                    </div>
                </div>
                <div class="p-3 bg-white" id="bulk_inputs_container" style="opacity: 0.4; pointer-events: none; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); border-left: 4px solid #ccc;">
                     <div class="row align-items-end">
                        <div class="col-md-11">
                            <div class="row no-gutters">
                                <div class="col-md-2 pr-2">
                                    <label class="smallest font-weight-bold text-uppercase text-muted mb-1">Buy Price</label>
                                    <input type="number" id="bulk_buy_price" class="form-control form-control-sm font-weight-bold border-primary" placeholder="0">
                                </div>
                                <div class="col-md-2 pr-2">
                                    <label class="smallest font-weight-bold text-uppercase text-muted mb-1">Sell (Btl)</label>
                                    <input type="number" id="bulk_sell_price" class="form-control form-control-sm font-weight-bold border-success" placeholder="0">
                                </div>
                                <div class="col-md-2 pr-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                       <label class="smallest font-weight-bold text-uppercase text-muted m-0">Sell (Tot)</label>
                                       <div class="custom-control custom-switch smallest" style="transform: scale(0.8);">
                                          <input type="checkbox" class="custom-control-input" id="bulk_dual_toggle">
                                          <label class="custom-control-label" for="bulk_dual_toggle"></label>
                                       </div>
                                    </div>
                                    <input type="number" id="bulk_sell_tot" class="form-control form-control-sm text-info" placeholder="Tot" disabled>
                                </div>
                                <div class="col-md-3 pr-2">
                                    <label class="smallest font-weight-bold text-uppercase text-muted mb-1">Expiry Date</label>
                                    <input type="date" id="bulk_expiry" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3 pr-2">
                                    <label class="smallest font-weight-bold text-uppercase text-muted mb-1">Discount</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" id="bulk_discount_amount" class="form-control" placeholder="0">
                                        <div class="input-group-append">
                                            <select id="bulk_discount_type" class="custom-select custom-select-sm" style="border-left:0;">
                                                <option value="fixed">TSh</option>
                                                <option value="percent">%</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <button type="button" id="applyBulkPrices" class="btn btn-outline-info btn-sm btn-block" title="Sync All">
                                <i class="fa fa-refresh"></i>
                            </button>
                        </div>
                     </div>
                </div>
             </div>
          </div>

          <!-- Items Table -->
          <div class="tile shadow-sm border-0 p-0 overflow-hidden">
             <div class="bg-dark p-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white"><i class="fa fa-list-ul mr-2 text-success"></i> Stock Entry List</h5>
                <span id="items_badge" class="badge badge-pill badge-primary">0 Items</span>
             </div>
             <div class="table-responsive">
                <table class="table table-hover mb-0" id="itemsTable">
                   <thead class="bg-light">
                       <tr>
                          <th class="border-top-0 px-3 py-2" style="font-size: 0.75rem;">PRODUCT & EXPIRE</th>
                          <th class="border-top-0 px-2 py-2 text-center" width="90" style="font-size: 0.75rem;">QTY</th>
                          <th class="border-top-0 px-2 py-2" width="130" style="font-size: 0.75rem;">BUY PRICE</th>
                          <th class="border-top-0 px-2 py-2" width="150" style="font-size: 0.75rem;">RETAIL PRICE</th>
                          <th class="border-top-0 px-2 py-2" width="150" style="font-size: 0.75rem;">DISCOUNT</th>
                          <th class="border-top-0 px-2 py-2" width="40"></th>
                       </tr>
                   </thead>
                   <tbody id="itemsTableBody">
                      <tr id="emptyTableMsg">
                         <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa fa-cubes fa-3x mb-3 d-block opacity-25"></i>
                            Select a category above to load products into this receipt.
                         </td>
                      </tr>
                   </tbody>
                </table>
             </div>
          </div>
        </div>

        <!-- Summary & Sidebar -->
        <div class="col-md-3" id="sidebar_col">
           <div class="sticky-top" style="top: 20px;">
               <div class="tile shadow-lg border-0 receipt-summary-box">
               <div class="d-flex justify-content-between align-items-start mb-4">
                  <div>
                    <h5 class="mb-0 font-weight-bold">Summary</h5>
                    <small class="opacity-75">Valuation & Profitability</small>
                  </div>
                  <span class="badge badge-light badge-pill px-3 py-1" id="items_badge">0 Items</span>
               </div>

               <div id="summary_content_area">
                  <div class="mb-3 d-flex justify-content-between summary-card-dark p-2" id="projected_summary_row" style="display: none !important;">
                      <span class="smallest font-weight-bold">PROJECTED (Target)</span>
                      <span class="font-weight-bold text-warning h6 mb-0" id="summ_projected">0</span>
                  </div>

                  <div class="row no-gutters mb-4">
                     <div class="col-6 pr-2">
                        <div class="summary-card-light p-2 text-center h-100">
                           <small class="smallest d-block opacity-75">Bulk Pkgs</small>
                           <span class="h4 font-weight-bold mb-0" id="summ_packages">0</span>
                        </div>
                     </div>
                     <div class="col-6 pl-2">
                        <div class="summary-card-light p-2 text-center h-100">
                           <small class="smallest d-block opacity-75">Total Bottles/Pc</small>
                           <span class="h4 font-weight-bold mb-0" id="summ_units">0</span>
                        </div>
                     </div>
                  </div>

                  <div class="mb-2 d-flex justify-content-between smallest px-1">
                     <span class="opacity-75">Gross Purchase</span>
                     <span id="summ_gross">0</span>
                  </div>
                  <div class="mb-3 d-flex justify-content-between smallest text-warning font-weight-bold px-1">
                     <span>Discount Applied (-)</span>
                     <span id="summ_discount">0</span>
                  </div>

                  <div class="summary-card-dark p-3 text-center mb-4 border-left border-warning" style="border-left-width: 4px !important;">
                     <span class="smallest opacity-75 d-block mb-1">Total Buying Cost</span>
                     <div class="h3 font-weight-bold mb-0 text-white" id="summ_cost">0</div>
                     <div class="smallest text-white-50 mt-1">Avg: <span id="summ_unit_cost" class="text-white">0</span> / bottle/pc</div>
                  </div>

                  <div class="summary-card-light p-3 mb-4">
                     <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="smallest opacity-75">Expected Selling</span>
                        <span class="font-weight-bold" id="summ_selling">0</span>
                     </div>
                     <div class="selling-row d-flex justify-content-between align-items-center smallest mb-1">
                        <span class="opacity-75">&#8627; Bottle</span>
                        <span class="text-primary" id="summ_selling_btl">0</span>
                     </div>
                     <div class="selling-row d-flex justify-content-between align-items-center smallest mb-2">
                        <span class="opacity-75">&#8627; Tot/Glass</span>
                        <span class="text-info" id="summ_selling_tot">0</span>
                     </div>
                     <div class="d-flex justify-content-between align-items-center pt-2 border-top border-white-10">
                        <span class="smallest font-weight-bold text-warning">Est. Net Profit</span>
                        <span class="h5 mb-0 font-weight-bold" id="summ_profit">0</span>
                     </div>
                  </div>

                  <div class="row no-gutters">
                      <div class="col-6 pr-1">
                         <div class="summary-card-dark p-2 text-center">
                            <small class="smallest d-block opacity-50 mb-1">Margin</small>
                            <span class="font-weight-bold h5 mb-0" id="summ_margin">0%</span>
                         </div>
                      </div>
                      <div class="col-6 pl-1">
                         <div class="summary-card-dark p-2 text-center">
                            <small class="smallest d-block opacity-50 mb-1">ROI</small>
                            <span class="font-weight-bold h5 mb-0" id="summ_roi">0%</span>
                         </div>
                      </div>
                  </div>
               </div>
            </div>
               <div class="tile shadow-sm border-0 p-3 mb-3 bg-white">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                     <label class="font-weight-bold small text-uppercase text-muted m-0">Items Breakdown</label>
                     <i class="fa fa-list-alt opacity-25"></i>
                  </div>
                  <div id="items_breakdown_list" style="max-height: 220px; overflow-y: auto;" class="mb-3">
                     <!-- Populated by JS -->
                  </div>
                  <div class="border-top pt-3">
                     <label class="font-weight-bold small text-uppercase text-muted mb-1">Internal Note</label>
                     <textarea class="form-control form-control-sm border-0 bg-light" name="notes" rows="2" placeholder="Reference invoice #, condition, etc..." style="border-radius: 8px;"></textarea>
                  </div>
               </div><!-- /breakdown -->

               <div class="tile shadow-sm border-0 p-3 mb-3">
                  <button type="submit" class="btn btn-success btn-block btn-lg shadow rounded-pill py-3 font-weight-bold" id="submitBtn" disabled>
                     <i class="fa fa-check-circle mr-2"></i> POST RECEIPT
                  </button>
                  <p class="text-center small text-muted mt-3 mb-0">Batch data will be added to Warehouse Stock immediately upon posting.</p>
               </div><!-- /submit -->

           </div>
        </div>
    </form>
  </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    
    body { font-family: 'Inter', sans-serif; }
    .truncate-1 { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tile { border-radius: 12px; transition: transform 0.2s; }
    .smallest { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .opacity-50 { opacity: 0.5; }
    .opacity-75 { opacity: 0.75; }
    
    /* Premium Sidebar */
    .receipt-summary-box { 
        background: linear-gradient(135deg, #940000 0%, #d40000 100%); 
        border-radius: 20px; 
        box-shadow: 0 10px 30px rgba(148, 0, 0, 0.2);
        color: white;
        padding: 24px;
        position: relative;
        overflow: hidden;
    }
    .receipt-summary-box::after {
        content: ""; position: absolute; top: -20px; right: -20px; width: 100px; height: 100px;
        background: rgba(255,255,255,0.1); border-radius: 50%;
    }
    .summary-card-light { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); border-radius: 12px; }
    .summary-card-dark { background: rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; }
    
    /* Interactive Table */
    .table thead th { background: #f8f9fa; border: 0; color: #6c757d; font-weight: 600; padding: 15px 10px; }
    .table tbody tr { transition: all 0.2s; }
    .table tbody tr:hover { background: #fef8f8; }
    .form-control-sm { border-radius: 6px; border: 1px solid #e9ecef; }
    .form-control-sm:focus { border-color: #940000; box-shadow: 0 0 0 3px rgba(148, 0, 0, 0.1); }
    
    /* Bulk Bar */
    #bulk_inputs_container { border-radius: 0 0 12px 12px; }
    .bulk-active { opacity: 1 !important; pointer-events: auto !important; border-left-color: #940000 !important; background: #fffcfc !important; box-shadow: 0 5px 15px rgba(148, 0, 0, 0.05); }
    
    /* Badges & Icons */
    .badge-premium { padding: 6px 12px; border-radius: 20px; font-weight: 600; }
    .fa-glass { color: #5bc0de; filter: drop-shadow(0 0 2px rgba(91, 192, 222, 0.5)); }
    .cursor-pointer { cursor: pointer; }
    .transition-3 { transition: all 0.3s; }
    
    /* Custom Input Styling */
    input[type="number"] { font-family: 'Inter', sans-serif; }
    .item-pkg { border-color: #940000; color: #940000; background: #fff9f9; }
</style>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // 1. STATE MANAGEMENT
    let receiptItems = [];

    // 2. SELECTORS
    const categoryFilter = $('#category_filter');
    const itemsTableBody = $('#itemsTableBody');
    const emptyTableMsg = $('#emptyTableMsg');
    const submitBtn = $('#submitBtn');
    const batchTarget = $('#batch_target_qty');
    const bulkRadios = $('input[name="bulk_toggle"]');
    const bulkContainer = $('#bulk_inputs_container');
    const bulkTile = $('#bulk_section_tile');

    // 3. CORE FUNCTIONS

    function isBulkEnabled() {
        return $('input[name="bulk_toggle"]:checked').val() === 'on';
    }

    function cleanNum(val) {
        if(!val) return 0;
        if(typeof val === 'string') val = val.replace(/,/g, '');
        let n = parseFloat(val);
        return isNaN(n) ? 0 : n;
    }

    function updateSummaries() {
        let actualPackages = 0;
        let actualUnits = 0;
        let actualGrossCost = 0;
        let actualDiscount = 0;
        let actualTotalSelling = 0;
        let actualSellingBtl = 0;
        let actualSellingTot = 0;

        $('#items_breakdown_list').empty();

        const isBulk = isBulkEnabled();
        const bulkBuy = cleanNum($('#bulk_buy_price').val());
        const bulkSell = cleanNum($('#bulk_sell_price').val());
        const bulkDiscAmt = cleanNum($('#bulk_discount_amount').val());
        const bulkDiscType = $('#bulk_discount_type').val() || 'fixed';
        const targetQty = cleanNum(batchTarget.val());

        // A. Handle Projection (Show what IS EXPECTED based on Target and Price)
        if(targetQty > 0 && bulkBuy > 0 && isBulk) {
            // Estimate units: assume items in list or default to 1 conversion
            let avgConv = 1;
            if(receiptItems.length > 0) {
                avgConv = receiptItems.reduce((acc, i) => acc + (i.conversion_qty || 1), 0) / receiptItems.length;
            }
            const projectedCost = targetQty * avgConv * bulkBuy;
            $('#summ_projected').text(Math.round(projectedCost).toLocaleString());
            $('#projected_summary_row').show().attr('style', 'display: flex !important;');
        } else {
            $('#projected_summary_row').hide().attr('style', 'display: none !important;');
        }

        // B. Process Loaded Items (ACTUAL quantities in list)
        receiptItems.forEach((item) => {
            const buyPrice = cleanNum(item.buying_price_per_unit);
            const sellPrice = cleanNum(item.selling_price_per_unit);
            const sellTot = cleanNum(item.selling_price_per_tot);
            const discAmt = cleanNum(item.discount_amount);
            const discType = item.discount_type || 'fixed';

            const pkgQty = cleanNum(item.quantity_received);
            const conv = cleanNum(item.conversion_qty) || 1;
            
            const units = pkgQty * conv;
            const lineGross = units * buyPrice;
            
            let lineDisc = 0;
            if (discType === 'percent') {
                lineDisc = (discAmt / 100) * lineGross;
            } else {
                lineDisc = discAmt; 
            }

            const lineNetCost = Math.max(0, lineGross - lineDisc);

            // Only count as "Bulk Package" if conversion > 1 (e.g. CRATE)
            if (conv > 1) {
                actualPackages += pkgQty;
            }
            
            actualUnits += units;
            actualGrossCost += lineGross;
            actualDiscount += lineDisc;

            // Smart Revenue: Calculate BOTH channels independently
            const totalTotsPerUnit = cleanNum(item.total_tots) || 0;
            const isDualSelling = item.is_dual;

            const lineBtlRetail = units * sellPrice;                                   // Bottle potential
            const lineTotRetail = (isDualSelling && sellTot > 0 && totalTotsPerUnit > 0)
                ? (units * totalTotsPerUnit * sellTot) : 0;                            // Tot potential

            // Primary retail = Tot if dual active, else Bottle
            const lineRetail = (lineTotRetail > 0) ? lineTotRetail : lineBtlRetail;

            actualSellingBtl += lineBtlRetail;
            if (lineTotRetail > 0) actualSellingTot += lineTotRetail;
            actualTotalSelling += lineRetail;

            const itemUnitLabel = (item.unit || 'Tot').charAt(0).toUpperCase() + (item.unit || 'Tot').slice(1).toLowerCase();
            const avgCostPerUnit = units > 0 ? lineNetCost / units : 0;
            
            // Only show units count if it differs from pkgQty (i.e. crate/case situation)
            const qtyDisplay = (conv > 1)
                ? `${pkgQty} ${item.packaging} &bull; ${Math.round(units)} Units`
                : `${pkgQty} ${item.packaging}`;

            const totalLineBtlProfit = (sellPrice - avgCostPerUnit) * units;
            const totalLineTotProfit = (isDualSelling && sellTot > 0 && totalTotsPerUnit > 0)
                ? (units * totalTotsPerUnit * (sellTot - (avgCostPerUnit / totalTotsPerUnit))) : 0;

            const profitBtlColor = totalLineBtlProfit >= 0 ? 'text-success' : 'text-danger';
            const profitTotColor = totalLineTotProfit >= 0 ? 'text-success' : 'text-danger';

            const totProfitHtml = (isDualSelling && sellTot > 0)
                ? `<div class="d-flex justify-content-between smallest ${profitTotColor}">
                        <span><i class="fa fa-glass mr-1"></i> ${itemUnitLabel} Profit (Tot):</span>
                        <span class="font-weight-bold">+${Math.round(totalLineTotProfit).toLocaleString()}</span>
                   </div>`
                : '';

            const breakdownHtml = `
                <div class="mb-3 pb-2 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="small font-weight-bold text-dark pr-2" style="max-width: 160px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${item.name}</span>
                        <span class="small font-weight-bold badge badge-light border">Cost: ${Math.round(lineNetCost).toLocaleString()}</span>
                    </div>
                    
                    <div class="smallest text-muted mb-2">
                        <i class="fa fa-tag mr-1"></i> ${qtyDisplay} 
                        ${totalTotsPerUnit > 0 ? `<span class="opacity-50 ml-1">(${totalTotsPerUnit} ${itemUnitLabel}s/btl)</span>` : ''}
                    </div>

                    <div class="bg-light p-1 rounded">
                        <div class="d-flex justify-content-between smallest mb-1 px-1">
                            <span class="text-muted"><i class="fa fa-shopping-cart mr-1"></i> Btl Sales:</span>
                            <span class="font-weight-bold text-dark">${Math.round(lineBtlRetail).toLocaleString()}</span>
                        </div>
                        <div class="d-flex justify-content-between smallest ${profitBtlColor} mb-1 px-1">
                            <span><i class="fa fa-arrow-up mr-1"></i> Btl Profit (Tot):</span>
                            <span class="font-weight-bold">+${Math.round(totalLineBtlProfit).toLocaleString()}</span>
                        </div>

                        ${lineTotRetail > 0 ? `
                        <div class="border-top my-1 mx-1" style="opacity: 0.2"></div>
                        <div class="d-flex justify-content-between smallest mb-1 px-1">
                            <span class="text-muted"><i class="fa fa-glass mr-1"></i> ${itemUnitLabel} Sales:</span>
                            <span class="font-weight-bold text-dark">${Math.round(lineTotRetail).toLocaleString()}</span>
                        </div>
                        ${totProfitHtml}
                        ` : ''}
                    </div>
                </div>
            `;
            $('#items_breakdown_list').append(breakdownHtml);
        });

        if(receiptItems.length === 0) {
            $('#items_breakdown_list').html('<p class="text-center py-4 smallest text-muted">No items in list yet.</p>');
        }

        // C. Update Dashboard UI
        const actualNetCost = Math.max(0, actualGrossCost - actualDiscount);
        const profit = actualTotalSelling - actualNetCost;
        const margin = actualTotalSelling > 0 ? (profit / actualTotalSelling) * 100 : 0;
        const roi = actualNetCost > 0 ? (profit / actualNetCost) * 100 : 0;
        const avgUnitCost = actualUnits > 0 ? (actualNetCost / actualUnits) : 0;

        $('#summ_packages').text(actualPackages.toFixed(1));
        $('#summ_units').text(Math.round(actualUnits));
        $('#summ_gross').text(Math.round(actualGrossCost).toLocaleString());
        $('#summ_discount').text(Math.round(actualDiscount).toLocaleString());
        $('#summ_cost').text(Math.round(actualNetCost).toLocaleString());
        $('#summ_selling').text(Math.round(actualTotalSelling).toLocaleString());
        // Bottle row: always show since we always calculate bottle potential
        $('#summ_selling_btl').text(Math.round(actualSellingBtl).toLocaleString()).closest('.selling-row').show();
        // Tot row: only show when at least one item is in dual/tot mode
        if(actualSellingTot > 0) {
            $('#summ_selling_tot').text(Math.round(actualSellingTot).toLocaleString()).closest('.selling-row').show();
        } else {
            $('#summ_selling_tot').closest('.selling-row').hide();
        }
        $('#summ_profit').text(Math.round(profit).toLocaleString());
        $('#summ_margin').text(margin.toFixed(1) + '%');
        $('#summ_roi').text(roi.toFixed(1) + '%');
        const activeItemsCount = receiptItems.filter(item => cleanNum(item.quantity_received) > 0).length;
        $('#items_badge').text(activeItemsCount + ' Items');

        // D. Batch Status Badge
        const badge = $('#batch_status_badge');
        if(targetQty > 0) {
            const diff = targetQty - actualPackages;
            if(Math.abs(diff) < 0.01) {
                badge.removeClass('badge-secondary badge-warning badge-danger').addClass('badge-success').text('Fully Matched');
            } else if(diff < 0) {
                badge.removeClass('badge-secondary badge-success badge-warning').addClass('badge-danger').text(`Mismatch (+${Math.abs(diff).toFixed(1)})`);
            } else {
                badge.removeClass('badge-secondary badge-success badge-danger').addClass('badge-warning').text(`${diff.toFixed(1)} Pkgs Remaining`);
            }
        } else {
            badge.removeClass('badge-success badge-warning badge-danger').addClass('badge-secondary').text('Set Target');
        }
    }

    function syncBulkToItems() {
        const buy = parseFloat($('#bulk_buy_price').val()) || 0;
        const sell = parseFloat($('#bulk_sell_price').val()) || 0;
        const isDual = $('#bulk_dual_toggle').is(':checked');
        const sellTot = isDual ? cleanNum($('#bulk_sell_tot').val()) : 0;
        const discAmt = parseFloat($('#bulk_discount_amount').val()) || 0;
        const discType = $('#bulk_discount_type').val() || 'fixed';

        receiptItems.forEach(item => {
            const ovr = item.overrides || {};
            if (!ovr.buying_price) item.buying_price_per_unit = buy;
            if (!ovr.selling_price) item.selling_price_per_unit = sell;
            
            // is_dual is a toggle, usually synced unless mixed logic applies
            item.is_dual = isDual;
            
            if (!ovr.selling_tot) item.selling_price_per_tot = sellTot;
            if (!ovr.expiry) item.expiry_date = $('#bulk_expiry').val();
            if (!ovr.discount) {
                item.discount_amount = discAmt;
                item.discount_type = discType;
            }
        });
        
        renderTable();
    }

    function renderTable() {
        if(receiptItems.length === 0) {
            itemsTableBody.html(emptyTableMsg);
            submitBtn.prop('disabled', true);
            return;
        }

        emptyTableMsg.remove();
        itemsTableBody.empty();
        submitBtn.prop('disabled', false);

        const isBulk = isBulkEnabled();

        receiptItems.forEach((item, index) => {
            const isMixed = item.selling_type === 'mixed';
            const isGlassOnly = item.selling_type === 'glass';
            const isBottleOnly = item.selling_type === 'bottle';
            
            // Should stay dual if it's mixed or glass-only
            const showTotInput = isMixed || isGlassOnly;
            const bottlePriceDisabled = isGlassOnly ? 'disabled style="background: #f0f0f0; opacity: 0.6;"' : '';
            const bottlePriceLabel = isGlassOnly ? ' (Disabled)' : 'Btl';

            const tr = $(`
                <tr>
                    <td class="px-3" style="max-width:300px;">
                        <div class="font-weight-bold text-dark mb-1" style="font-size:14px;">${item.name} ${item.packaging} (${item.conversion_qty} Btl/Pc)</div>
                        
                        <div class="d-flex align-items-center mb-1 smallest">
                            <span class="badge badge-light border text-muted mr-2">
                                <i class="fa fa-home"></i> Stock: ${item.existing_quantity || 0} unit(s)
                            </span>
                            ${item.buying_price_per_unit != item.last_known_buy ? `
                                <span class="text-warning font-weight-bold" title="Price changed from previous reception">
                                    <i class="fa fa-info-circle"></i> Price Change
                                </span>
                            ` : ''}
                        </div>

                        <div class="input-group input-group-sm" style="max-width:140px;">
                           <div class="input-group-prepend"><span class="input-group-text p-0 px-1 bg-transparent border-0 opacity-50 smallest">EXP:</span></div>
                           <input type="date" class="form-control form-control-sm item-expiry border-0 p-0 h-auto bg-transparent smallest" data-index="${index}" value="${item.expiry_date || ''}">
                        </div>
                    </td>
                    <td class="px-2">
                        <input type="number" class="form-control font-weight-bold item-pkg" data-index="${index}" value="${item.quantity_received}" min="0" step="0.1" placeholder="0">
                    </td>
                    <td class="px-2">
                        <input type="number" class="form-control item-buy-price" data-index="${index}" value="${item.buying_price_per_unit}" step="0.01">
                        <div class="smallest text-muted mt-1" style="font-size: 10px;">
                            Last: TSh ${Math.round(item.last_known_buy).toLocaleString()}
                        </div>
                    </td>
                    <td class="px-2">
                        <div class="d-flex align-items-center mb-1">
                            <input type="number" class="form-control item-sell-price font-weight-bold text-primary mr-2" data-index="${index}" value="${item.selling_price_per_unit}" step="0.01" placeholder="${bottlePriceLabel}" ${bottlePriceDisabled}>
                            ${isMixed ? `<i class="fa ${item.is_dual ? 'fa-glass text-info' : 'fa-circle-thin opacity-25'} toggle-dual cursor-pointer" data-index="${index}" title="Toggle Retail Mode"></i>` : ''}
                        </div>
                        <div class="input-group input-group-sm tot-input-wrapper" style="visibility: ${showTotInput ? 'visible' : 'hidden'}; opacity: ${showTotInput ? '1' : '0'}; transition: all 0.3s;">
                           <div class="input-group-prepend"><span class="input-group-text p-0 px-1 bg-transparent border-0 opacity-50 smallest">${(item.unit || 'TOT').toUpperCase()}:</span></div>
                           <input type="number" class="form-control form-control-sm item-sell-tot text-info p-0 h-auto bg-transparent border-0 smallest" data-index="${index}" value="${item.selling_price_per_tot || ''}" step="0.01" placeholder="0">
                        </div>
                    </td>
                    <td class="px-2">
                        <div class="input-group">
                           <input type="number" class="form-control item-discount-amount" data-index="${index}" value="${item.discount_amount}" step="0.01">
                           <div class="input-group-append">
                              <select class="custom-select p-0 px-1 item-discount-type" data-index="${index}" style="width: 50px; font-size: 11px;">
                                 <option value="fixed" ${item.discount_type === 'fixed' ? 'selected' : ''}>TSh</option>
                                 <option value="percent" ${item.discount_type === 'percent' ? 'selected' : ''}>%</option>
                              </select>
                           </div>
                        </div>
                    </td>
                    <td class="text-center px-2">
                        <button type="button" class="btn btn-link py-0 text-danger remove-item" data-index="${index}"><i class="fa fa-times-circle"></i></button>
                    </td>
                </tr>
            `);
            itemsTableBody.append(tr);
        });

        updateSummaries();
    }

    // 4. EVENT LISTENERS

    categoryFilter.on('change', function() {
        const category = $(this).val();
        const supplierId = $('#supplier_id').val();
        if(!category) return;
        if(!supplierId) {
            showToast('warning', 'Please select a supplier first.');
            $(this).val('');
            return;
        }

        showToast('info', 'Fetching inventory...', true);
        fetch(`{{ route('bar.products.get-by-category') }}?category=${encodeURIComponent(category)}&supplier_id=${supplierId}`)
            .then(res => res.json())
            .then(data => {
                if(data.length > 0) {
                    data.forEach(item => {
                        if(!receiptItems.some(ri => ri.product_variant_id === item.id)) {
                            // Strictly force configuration based on selling_type from catalog
                            const sellingType = item.selling_type || 'bottle';
                            const isDual = (sellingType === 'mixed' || sellingType === 'glass');

                            receiptItems.push({
                                product_variant_id: item.id,
                                name: item.name,
                                brand: item.brand || (item.product ? item.product.brand : ''),
                                packaging: item.packaging || 'Piece',
                                items_per_package: item.items_per_package || 1,
                                conversion_qty: item.conversion_qty || item.items_per_package || 1,
                                unit: item.unit || 'btl',
                                selling_type: sellingType,
                                is_dual: isDual,
                                buying_price_per_unit: item.buying_price_per_unit || 0,
                                last_known_buy: item.average_buying_price || item.buying_price_per_unit || 0,
                                selling_price_per_unit: item.selling_price_per_unit || 0,
                                total_tots: item.total_tots || 0,
                                selling_price_per_tot: item.selling_price_per_tot || 0,
                                quantity_received: 0,
                                existing_quantity: item.existing_quantity || 0,
                                expiry_date: '',
                                discount_type: 'fixed',
                                discount_amount: 0
                            });
                        }
                    });
                    if(isBulkEnabled()) syncBulkToItems();
                    renderTable();
                    showToast('success', `${data.length} items loaded.`);
                } else {
                    showToast('warning', 'No products found.');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('error', 'Inventory load failed.');
            });
    });

    bulkRadios.on('change', function() {
        const isEnabled = $(this).val() === 'on';
        if(isEnabled) {
            bulkContainer.addClass('bulk-active');
            bulkTile.addClass('border-primary shadow');
            syncBulkToItems();
        } else {
            bulkContainer.removeClass('bulk-active');
            bulkTile.removeClass('border-primary shadow');
        }
        renderTable();
    });

    $('#bulk_buy_price, #bulk_sell_price, #bulk_sell_tot, #bulk_expiry, #bulk_discount_amount, #bulk_discount_type, #batch_target_qty, #bulk_dual_toggle').on('input change', function() {
        if(this.id === 'bulk_dual_toggle') {
           $('#bulk_sell_tot').prop('disabled', !$(this).is(':checked')).focus();
        }
        
        // Restore auto-sync for speed as requested
        if(isBulkEnabled()) {
            syncBulkToItems();
        }
        
        updateSummaries();
    });

    $(document).on('click', '.toggle-dual', function() {
        // if(isBulkEnabled()) return; // Controlled by bulk switch -> Removed to allow item level override
        const idx = $(this).attr('data-index');
        receiptItems[idx].is_dual = !receiptItems[idx].is_dual;
        renderTable();
        updateSummaries();
    });

    $(document).on('input change', '.item-pkg, .item-buy-price, .item-sell-price, .item-sell-tot, .item-expiry, .item-discount-amount, .item-discount-type', function() {
        const idx = $(this).attr('data-index');
        const item = receiptItems[idx];
        if(!item) return;

        // Initialize override tracking if not exists
        if (!item.overrides) item.overrides = {};
        
        if($(this).hasClass('item-pkg')) {
            item.quantity_received = cleanNum($(this).val());
        }
        
        if($(this).hasClass('item-buy-price')) {
            item.buying_price_per_unit = cleanNum($(this).val());
            item.overrides.buying_price = true;
        }
        if($(this).hasClass('item-sell-price')) {
            item.selling_price_per_unit = cleanNum($(this).val());
            item.overrides.selling_price = true;
        }
        if($(this).hasClass('item-sell-tot')) {
            item.selling_price_per_tot = cleanNum($(this).val());
            item.overrides.selling_tot = true;
        }
        if($(this).hasClass('item-expiry')) {
            item.expiry_date = $(this).val();
            item.overrides.expiry = true;
        }
        if($(this).hasClass('item-discount-amount')) {
            item.discount_amount = cleanNum($(this).val());
            item.overrides.discount = true;
        }
        if($(this).hasClass('item-discount-type')) {
            item.discount_type = $(this).val();
            item.overrides.discount = true;
        }
        updateSummaries();
    });

    $(document).on('click', '.remove-item', function() {
        const idx = $(this).attr('data-index');
        receiptItems.splice(idx, 1);
        renderTable();
    });

    $('#applyBulkPrices').on('click', function() {
        syncBulkToItems();
        renderTable();
        showToast('success', 'Prices synced.');
    });



    $('#stockReceiptForm').on('submit', function(e) {
        e.preventDefault();
        if(receiptItems.length === 0) { showToast('error', 'List is empty!'); return; }
        if(!$('#supplier_id').val()) { showToast('error', 'Select supplier.'); return; }

        const myForm = this;
        const btn = $('#submitBtn');
        const oldHtml = btn.html();
        
        const entriesToSubmit = receiptItems.filter(item => cleanNum(item.quantity_received) > 0);
        
        if(entriesToSubmit.length === 0) { 
            showToast('error', 'Please enter quantity for at least one item.');
            btn.prop('disabled', false).html(oldHtml);
            return; 
        }

        // --- STRICT VALIDATION ---
        let validationError = null;
        entriesToSubmit.forEach(item => {
            const buyingPrice = cleanNum(item.buying_price_per_unit);
            const sellingPrice = cleanNum(item.selling_price_per_unit);
            const totPrice = cleanNum(item.selling_price_per_tot);
            
            if (buyingPrice <= 0) {
                validationError = `Set buying price for: ${item.name}`;
                return;
            }

            // Force based on selling type
            if ((item.selling_type === 'bottle' || item.selling_type === 'mixed') && sellingPrice <= 0) {
                validationError = `Set Bottle selling price for: ${item.name}`;
                return;
            }

            if ((item.selling_type === 'glass' || item.selling_type === 'mixed') && totPrice <= 0) {
                validationError = `Set Glass/Portion price for: ${item.name}`;
                return;
            }
        });

        if (validationError) {
            showToast('error', validationError);
            btn.prop('disabled', false).html(oldHtml);
            return;
        }
        // -------------------------

        const formData = new FormData(myForm);
        formData.delete('items');

        entriesToSubmit.forEach((item, index) => {
            formData.append(`items[${index}][product_variant_id]`, item.product_variant_id);
            formData.append(`items[${index}][quantity_received]`, item.quantity_received); 
            formData.append(`items[${index}][buying_price_per_unit]`, item.buying_price_per_unit);
            formData.append(`items[${index}][selling_price_per_unit]`, item.selling_price_per_unit);
            formData.append(`items[${index}][selling_price_per_tot]`, item.selling_price_per_tot || 0);
            formData.append(`items[${index}][expiry_date]`, item.expiry_date || '');
            formData.append(`items[${index}][discount_type]`, item.discount_type);
            formData.append(`items[${index}][discount_amount]`, item.discount_amount);
        });

        fetch(myForm.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if(data.alert_success || data.success) {
                const receiptNum = data.receipt_number;
                Swal.fire({
                    title: 'Stock Updated!',
                    text: data.message || 'Receipt posted successfully.',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonColor: '#940000',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fa fa-print"></i> PRINT RECEIPT',
                    cancelButtonText: 'Go to List'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `{{ url('bar/stock-receipts/print-batch') }}/${receiptNum}?auto_print=1`;
                    } else {
                        window.location.href = "{{ route('bar.stock-receipts.index') }}";
                    }
                });
            } else {
                showToast('error', data.message || 'Error occurred.');
                btn.prop('disabled', false).html(oldHtml);
            }
        })
        .catch(err => {
            console.error(err);
            showToast('error', 'Server error.');
            btn.prop('disabled', false).html(oldHtml);
        });
    });
});

</script>
@endsection

