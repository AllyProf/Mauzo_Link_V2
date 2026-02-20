@extends('layouts.dashboard')

@section('title', 'Warehouse Inventory')

@section('content')
<div class="row">
    <div class="col-md-12">
        <!-- HEADER & SEARCH -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
            <div class="mb-3 mb-md-0 pt-2">
                <h3 class="font-weight-extra-bold text-dark mb-1">Warehouse Inventory</h3>
                <p class="text-muted mb-0"><i class="fa fa-info-circle text-primary"></i> Browse and request stock from warehouse to counter</p>
            </div>
            <div class="d-flex align-items-center">
                <div class="input-group shadow-sm mr-2" style="width: 320px; border-radius: 20px; overflow: hidden;">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white border-right-0"><i class="fa fa-search text-muted"></i></span>
                    </div>
                    <input type="text" id="inventorySearch" class="form-control border-left-0 px-2" placeholder="Search by name or brand..." style="font-size: 14px; border-radius: 0 20px 20px 0;">
                </div>
                <!-- BATCH ACTION BUTTON -->
                <button type="button" id="btnBatchTransfer" class="btn btn-primary btn-sm shadow-sm mr-2 d-none" style="border-radius: 20px; padding: 8px 20px; font-weight: 700;">
                    <i class="fa fa-shopping-cart mr-1"></i> BATCH REQUEST (<span id="batchCount">0</span>)
                </button>
                <a href="{{ route('bar.stock-transfers.index') }}" class="btn btn-outline-dark btn-sm shadow-sm" style="border-radius: 20px; padding: 8px 15px;">
                    <i class="fa fa-history mr-1"></i> My History
                </a>
            </div>
        </div>

        <!-- QUICK FILTER TABS (HORIZONTAL SCROLLABLE) -->
        <div class="category-tabs-wrapper mb-4">
            <div class="d-flex align-items-center overflow-auto no-scrollbar py-2" id="categoryContainer">
                <button class="btn btn-category active filter-pill" data-filter="all">
                    <i class="fa fa-th-large mr-2"></i> ALL ITEMS
                </button>
                @foreach($quickFilters as $label)
                    @php 
                        $slug = Str::slug($label);
                    @endphp
                    <button class="btn btn-category filter-pill" data-filter="{{ $slug }}">
                        {{ strtoupper($label) }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- INVENTORY GRID -->
        <div class="row" id="inventoryGrid">
            @forelse($inventoryItems as $item)
                @php
                    $isMixed = ($item['category'] == 'MIXED');
                    $imgUrl = $item['image'] ? asset('storage/' . $item['image']) : asset('default_images/default_drink.jpg');
                @endphp
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4 product-card-wrapper" 
                     data-category="{{ Str::slug($item['category']) }}" 
                     data-brand="{{ Str::slug($item['brand']) }}"
                     data-name="{{ strtolower($item['variant_name']) }} {{ strtolower($item['product_name']) }} {{ strtolower($item['measurement']) }} {{ strtolower($item['brand'] ?? '') }}">
                    
                    <div class="card border-0 shadow-sm h-100 overflow-hidden inventory-card">
                        <!-- Top Image Sec -->
                        <div class="bg-light text-center p-2" style="height: 160px; overflow: hidden; position: relative;">
                            <img src="{{ $imgUrl }}" alt="{{ $item['variant_name'] }}" style="height: 100%; width: 100%; object-fit: contain;">
                            @if($isMixed)
                                <span class="badge badge-warning position-absolute" style="top: 10px; right: 10px; font-size: 10px;">MIXED</span>
                            @endif
                        </div>

                        <div class="card-body p-3 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge badge-primary-light smallest px-2 py-1">{{ $item['brand'] }}</span>
                                <span class="badge badge-success px-2 py-1" style="font-size: 10px; border-radius: 4px;">
                                    <i class="fa fa-archive"></i> {{ $item['warehouse_packages'] }} {{ Str::plural(Str::singular($item['packaging']), $item['warehouse_packages']) }}
                                </span>
                            </div>

                            <h6 class="font-weight-extra-bold text-dark mb-1" style="font-size: 14px; line-height: 1.2;">
                                {{ $item['variant_name'] }} 
                                @php
                                    $ms = $item['measurement'];
                                    $displayMs = $ms;
                                    if (is_numeric($ms)) {
                                        $displayMs = $ms > 10 ? $ms . 'ml' : $ms . 'L';
                                    }
                                @endphp
                                <span class="text-primary small">({{ $displayMs }})</span>
                            </h6>
                            
                            @if(strtolower(trim($item['product_name'])) != strtolower(trim($item['brand'])))
                                <div class="smallest text-muted mb-2 font-weight-bold opacity-75">
                                    {{ $item['product_name'] }}
                                </div>
                            @endif

                            <!-- Stock & Price Info -->
                            <div class="p-2 rounded mb-2 bg-light border-left border-info" style="border-left-width: 3px !important;">
                                <div class="d-flex justify-content-between align-items-center smallest mb-1">
                                    <b class="text-dark">
                                        {{ number_format($item['warehouse_quantity']) }} 
                                        {{ Str::plural('Bottle', $item['warehouse_quantity']) }}
                                    </b>
                                </div>
                                <div class="d-flex justify-content-between align-items-center smallest">
                                    <span class="text-muted">Bottle Sell Price:</span>
                                    <b class="text-success font-weight-bold">TSh {{ number_format($item['selling_price']) }}</b>
                                </div>
                            </div>

                            @if($item['can_sell_in_tots'])
                                <div class="p-2 rounded mb-3 bg-light border-left border-warning" style="border-left-width: 3px !important; background-color: #fff9f0 !important;">
                                    <div class="d-flex justify-content-between align-items-center smallest mb-1">
                                        <span class="text-muted">Portion Yield:</span>
                                        <b class="text-dark">{{ $item['total_tots_per_unit'] }} Glasses/Bottle</b>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center smallest">
                                        <span class="text-muted">Portion Price:</span>
                                        <b class="text-warning font-weight-bold">TSh {{ number_format($item['selling_price_per_tot']) }}</b>
                                    </div>
                                </div>
                            @endif

                            <!-- Transfer Form -->
                            <div class="mt-auto">
                                <div class="input-group input-group-sm mb-1 shadow-xs">
                                    <div class="input-group-prepend">
                                        <button type="button" class="btn btn-light border btn-qty-minus"><i class="fa fa-minus"></i></button>
                                    </div>
                                    <input type="number" name="quantity_requested" class="form-control text-center font-weight-bold q-field border-left-0 border-right-0" value="1" min="1" max="{{ $item['warehouse_packages'] }}">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-light border btn-qty-plus"><i class="fa fa-plus"></i></button>
                                        <span class="input-group-text px-2 smallest font-weight-bold bg-white text-uppercase" style="font-size: 9px; min-width: 50px;">{{ Str::singular($item['packaging']) }}</span>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-outline-primary btn-sm btn-block p-1 font-weight-bold smallest shadow-sm mt-1 btn-add-batch" 
                                        data-variant-id="{{ $item['variant_id'] }}"
                                        data-name="{{ $item['variant_name'] }} ({{ $displayMs }})"
                                        data-items-per-package="{{ $item['items_per_package'] }}"
                                        data-sell-price="{{ $item['selling_price'] }}"
                                        data-buy-price="{{ $item['average_buying_price'] }}"
                                        data-packaging="{{ $item['packaging'] }}"
                                        data-unit="{{ strtolower($item['unit_label'] ?? '') == 'ml' ? 'Bottle' : ($item['unit_label'] ?? 'Bottle') }}"
                                        data-can-sell-tots="{{ $item['can_sell_in_tots'] ? 1 : 0 }}"
                                        data-tots-per-unit="{{ $item['total_tots_per_unit'] }}"
                                        data-tot-price="{{ $item['selling_price_per_tot'] }}"
                                        style="border-radius: 6px;">
                                    <i class="fa fa-plus-circle mr-1"></i> ADD TO BATCH
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="tile text-center py-5 border-0 shadow-sm" style="border-radius: 20px; background: #fff;">
                        <i class="fa fa-folder-open-o fa-5x text-light mb-3"></i>
                        <h4 class="text-muted">No stock available currently</h4>
                        <a href="{{ route('bar.stock-receipts.create') }}" class="btn btn-primary mt-3 px-4 shadow-sm" style="border-radius: 25px;">
                            <i class="fa fa-plus"></i> New Stock Receipt
                        </a>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- HIDDEN FORM FOR BATCH SUBMISSION -->
<div class="d-none">
    <form id="batchTransferForm" action="{{ route('bar.stock-transfers.store') }}" method="POST">
        @csrf
        <div id="batchInputs"></div>
        <textarea name="notes" id="batchNotes"></textarea>
    </form>
</div>
@endsection

@push('styles')
<style>
    .font-weight-extra-bold { font-weight: 800; }
    .smallest { font-size: 11px; }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .italic { font-style: italic; }
    
    .btn-category {
        border-radius: 30px;
        padding: 10px 22px;
        font-size: 11px;
        font-weight: 700;
        margin-right: 8px;
        background: #fff;
        color: #555;
        border: 1px solid #eeeff1;
        transition: all 0.3s ease;
        white-space: nowrap;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    
    .btn-category:hover { background: #f8f9fa; color: #000; }
    
    .btn-category.active {
        background: #000 !important;
        color: #fff !important;
        border-color: #000 !important;
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(0,0,0,0.15) !important;
    }

    .inventory-card {
        border-radius: 12px;
        border: 1px solid #f1f3f5;
        transition: all 0.3s ease;
    }

    .inventory-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.08) !important;
        border-color: #e9ecef;
    }

    .badge-primary-light { background: #e7f1ff; color: #007bff; }
    .line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }

    .q-field:focus { border-color: #ced4da; box-shadow: none; }

    .swipe-fade {
        animation: swipeFadeIn 0.4s ease-out forwards;
    }

    @keyframes swipeFadeIn {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .btn-qty-minus, .btn-qty-plus {
        background: #f8f9fa;
        color: #333;
        padding: 0 10px;
    }
    .btn-qty-minus:hover, .btn-qty-plus:hover { background: #e9ecef; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function() {
    let batchItems = [];

    // 1. TABS FILTERING
    $('.filter-pill').on('click', function() {
        const filter = $(this).data('filter');
        $('.filter-pill').removeClass('active');
        $(this).addClass('active');

        if(filter === 'all') {
            $('.product-card-wrapper').show().addClass('swipe-fade');
        } else {
            $('.product-card-wrapper').hide();
            $(`.product-card-wrapper[data-category="${filter}"], .product-card-wrapper[data-brand="${filter}"]`).show().addClass('swipe-fade');
        }
    });

    // 2. LIVE SEARCH
    $('#inventorySearch').on('input', function() {
        const term = $(this).val().toLowerCase();
        $('.product-card-wrapper').each(function() {
            const searchData = $(this).data('name');
            if(searchData.indexOf(term) > -1) {
                $(this).show().addClass('swipe-fade');
            } else {
                $(this).hide();
            }
        });
    });

    // Qty buttons
    $(document).on('click', '.btn-qty-plus', function() {
        const input = $(this).closest('.input-group').find('input');
        const max = parseInt(input.attr('max'));
        const val = parseInt(input.val());
        if(val < max) input.val(val + 1);
    });

    $(document).on('click', '.btn-qty-minus', function() {
        const input = $(this).closest('.input-group').find('input');
        const val = parseInt(input.val());
        if(val > 1) input.val(val - 1);
    });

    // 3. BATCH ADD LOGIC
    $('.btn-add-batch').on('click', function() {
        const $card = $(this).closest('.card-body');
        const qty = parseInt($card.find('.q-field').val());
        const variantId = $(this).data('variant-id');
        const name = $(this).data('name');
        const itemsPerPkg = $(this).data('items-per-package');
        const sellPrice = $(this).data('sell-price');
        const buyPrice = $(this).data('buy-price');
        const packaging = $(this).data('packaging');
        const unit = $(this).data('unit');
        const canSellTots = $(this).data('can-sell-tots');
        const totsPerUnit = $(this).data('tots-per-unit');
        const totPrice = $(this).data('tot-price');

        // Check if already in batch
        const existingIndex = batchItems.findIndex(i => i.variantId === variantId);
        if (existingIndex > -1) {
            batchItems[existingIndex].qty += qty;
        } else {
            batchItems.push({ 
                variantId, name, qty, itemsPerPkg, sellPrice, buyPrice, 
                packaging, unit, canSellTots, totsPerUnit, totPrice 
            });
        }

        updateBatchUI();
        
        // Visual feedback
        const originalHtml = $(this).html();
        $(this).html('<i class="fa fa-check"></i> ADDED').removeClass('btn-outline-primary').addClass('btn-success');
        setTimeout(() => {
            $(this).html(originalHtml).removeClass('btn-success').addClass('btn-outline-primary');
        }, 1000);
    });

    function updateBatchUI() {
        const count = batchItems.length;
        if (count > 0) {
            $('#btnBatchTransfer').removeClass('d-none');
            $('#batchCount').text(count);
        } else {
            $('#btnBatchTransfer').addClass('d-none');
        }
    }

    // 4. BATCH CONFIRMATION & SUBMIT
    $('#btnBatchTransfer').on('click', function() {
        let itemsHtml = '';
        let totalRevenue = 0;
        
        batchItems.forEach((item, index) => {
            const totalUnits = item.qty * item.itemsPerPkg;
            const bottleRev = totalUnits * item.sellPrice;
            const glassRev = (item.canSellTots && item.totPrice > 0) ? (totalUnits * item.totsPerUnit * item.totPrice) : 0;
            
            // Calculate the total based on the TRUE highest potential yield
            const primaryRev = Math.max(bottleRev, glassRev);
            totalRevenue += primaryRev;
            
            // Helper for pluralization in JS
            const pkgLabel = item.qty > 1 ? item.packaging + 's' : item.packaging;
            const unitLabel = totalUnits > 1 ? item.unit + 's' : item.unit;

            let revLines = '';
            if (glassRev > 0) {
                revLines = `
                    <div class="d-flex justify-content-between py-1 border-top smallest">
                        <span class="text-muted">Bottle Revenue:</span>
                        <b class="text-dark">TSh ${bottleRev.toLocaleString()}</b>
                    </div>
                    <div class="d-flex justify-content-between smallest">
                        <span class="text-muted font-weight-bold">Glass/Tot Revenue:</span>
                        <b class="text-success">TSh ${glassRev.toLocaleString()}</b>
                    </div>
                `;
            } else {
                revLines = `
                    <div class="d-flex justify-content-between py-1 border-top smallest">
                        <span class="text-muted">Bottle Revenue:</span>
                        <b class="text-success">TSh ${bottleRev.toLocaleString()}</b>
                    </div>
                `;
            }

            itemsHtml += `
                <div class="mb-3 p-2 rounded border bg-white shadow-xs">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="font-weight-bold text-dark" style="font-size: 14px;">${item.name}</div>
                        <button type="button" class="btn btn-link btn-sm p-0 text-danger" onclick="window.removeBatchItem(${index})"><i class="fa fa-times-circle"></i></button>
                    </div>
                    <div class="smallest text-muted mb-2">
                        ${item.qty} ${pkgLabel} (${totalUnits} ${unitLabel})
                    </div>
                    ${revLines}
                </div>
            `;
        });

        Swal.fire({
            title: 'Confirm Batch Transfer',
            html: `
                <div class="p-2 bg-light rounded border mb-3" id="batchPreviewList" style="max-height: 350px; overflow-y: auto;">
                    ${itemsHtml}
                </div>
                <div class="rounded p-3 bg-dark text-white shadow-sm">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="smallest opacity-75">ESTIMATED TOTAL REVENUE:</span>
                        <h5 class="mb-0 font-weight-bold text-success">TSh ${totalRevenue.toLocaleString()}</h5>
                    </div>
                    <div class="smallest opacity-50 text-right" style="font-size: 9px;">*Based on highest potential yield (Glass/Tot)</div>
                </div>
                <div class="mt-3">
                    <textarea id="swalNotes" class="form-control" placeholder="Add optional notes for this batch..." style="font-size: 12px; border-radius: 8px;"></textarea>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'CONFIRM & SEND REQUEST',
            cancelButtonText: 'CANCEL',
            width: '480px'
        }).then((result) => {
            if (result.isConfirmed) {
                const notes = $('#swalNotes').val();
                submitBatch(notes);
            }
        });
    });

    window.removeBatchItem = function(index) {
        batchItems.splice(index, 1);
        updateBatchUI();
        if (batchItems.length > 0) {
            $('#btnBatchTransfer').click(); // Re-open modal
        } else {
            Swal.close();
        }
    };

    function submitBatch(notes) {
        Swal.fire({
            title: 'Submitting Requests...',
            text: 'Please wait while we process your batch transfer.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const items = batchItems.map(item => ({
            product_variant_id: item.variantId,
            quantity_requested: item.qty
        }));

        $.ajax({
            url: "{{ route('bar.stock-transfers.batch-store') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                items: items,
                notes: notes
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = "{{ route('bar.stock-transfers.index') }}?batch_success=1";
                } else {
                    Swal.fire('Error', response.error || 'Failed to process batch.', 'error');
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.error || 'Failed to process batch transfer. Please check stock levels.';
                Swal.fire('Error', errorMsg, 'error');
            }
        });
    }
});
</script>
@endpush
