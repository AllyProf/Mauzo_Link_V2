@extends('layouts.dashboard')

@section('title', 'Warehouse Inventory')

@section('content')
<div class="app-title">
    <div>
        <h1><i class="fa fa-exchange"></i> Warehouse Inventory</h1>
        <p>Browse and request stock from warehouse to counter</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
        <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item">Warehouse Inventory</li>
    </ul>
</div>

<!-- STATISTICS WIDGETS -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="widget-small primary coloured-icon"><i class="icon fa fa-cubes fa-3x"></i>
            <div class="info">
                <h4>Items</h4>
                <p><b>{{ $stats['total_items'] }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="widget-small info coloured-icon"><i class="icon fa fa-archive fa-3x"></i>
            <div class="info">
                <h4>Packages</h4>
                <p><b>{{ number_format($stats['total_packages']) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="widget-small warning coloured-icon"><i class="icon fa fa-flask fa-3x"></i>
            <div class="info">
                <h4>Pieces</h4>
                <p><b>{{ number_format($stats['total_quantity']) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="widget-small danger coloured-icon"><i class="icon fa fa-money fa-3x"></i>
            <div class="info">
                <h4>Est. Value</h4>
                <p><b>{{ number_format($stats['total_value']) }}</b></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="tile-title mb-0">Available Items</h3>
                <div class="d-flex align-items-center">
                    <!-- VIEW TOGGLE -->
                    <div class="btn-group mr-3" role="group">
                        <button type="button" class="btn btn-sm btn-outline-secondary active view-btn" data-view="grid">
                            <i class="fa fa-th"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary view-btn" data-view="list">
                            <i class="fa fa-list"></i>
                        </button>
                    </div>

                    <button type="button" id="btnBatchTransfer" class="btn btn-primary d-none mr-2">
                        <i class="fa fa-shopping-cart"></i> REQUEST BATCH (<span id="batchCount">0</span>)
                    </button>
                    <a href="{{ route('bar.stock-transfers.index') }}" class="btn btn-secondary">
                        <i class="fa fa-history"></i> My History
                    </a>
                </div>
            </div>

            <!-- SEARCH & QUICK FILTERS -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="control-label font-weight-bold">Search Products</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-search"></i></span>
                            </div>
                            <input type="text" id="inventorySearch" class="form-control" placeholder="Search...">
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <label class="control-label font-weight-bold">Quick Filters (Categories & Brands)</label>
                    <div class="category-tabs-wrapper">
                        <div class="d-flex align-items-center overflow-auto no-scrollbar py-1" id="categoryContainer">
                            <button class="btn btn-sm btn-outline-primary active filter-pill mr-1 mb-1" data-filter="all" data-filter-type="category">
                                ALL ITEMS
                            </button>
                            @foreach($categories as $label)
                                <button class="btn btn-sm btn-outline-primary filter-pill mr-1 mb-1" data-filter="{{ Str::slug($label) }}" data-filter-type="category">
                                    {{ strtoupper($label) }}
                                </button>
                            @endforeach
                            <div class="mx-2 border-right h-100"></div>
                            @foreach($brands as $label)
                                <button class="btn btn-sm btn-outline-info filter-pill mr-1 mb-1" data-filter="{{ Str::slug($label) }}" data-filter-type="brand">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <!-- INVENTORY GRID (Default) -->
            <div class="row mt-4" id="inventoryGrid">
                @forelse($inventoryItems as $item)
                    @php
                        $ms = $item['measurement'];
                        $displayMs = is_numeric($ms) ? ($ms > 10 ? $ms . 'ml' : $ms . 'L') : $ms;
                        $unitLabel = ($item['unit_label'] == 'ml' || strtolower($item['unit_label'] ?? '') == 'bottle' || strtolower($item['unit_label'] ?? '') == 'btl') ? 'btl' : (strtolower($item['unit_label'] ?? '') == 'piece' || strtolower($item['unit_label'] ?? '') == 'pcs' ? 'pcs' : 'btl/pcs');
                    @endphp
                    <div class="col-md-4 mb-4 product-card-wrapper" 
                         data-category="{{ Str::slug($item['category']) }}" 
                         data-brand="{{ Str::slug($item['brand']) }}"
                         data-name="{{ strtolower($item['display_title']) }} {{ strtolower($item['product_name']) }} {{ strtolower($item['brand'] ?? '') }}">
                        
                        <div class="tile p-3 h-100 mb-0 shadow-sm border-0 inventory-item-card transition-all">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="flex-grow-1 pr-2">
                                    <h6 class="font-weight-bold text-primary mb-1 line-clamp-1" title="{{ $item['display_title'] }}">{{ $item['display_title'] }}</h6>
                                    <p class="text-muted smallest mb-0">{{ $item['brand'] }} • {{ $item['category'] }}</p>
                                </div>
                                <span class="badge badge-secondary px-2 py-1 smallest">{{ $displayMs }}</span>
                            </div>

                            <div class="bg-light p-2 rounded mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Stock Level:</span>
                                    <strong class="text-dark">{{ number_format($item['warehouse_quantity']) }} {{ $unitLabel }}</strong>
                                </div>
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Packages:</span>
                                    <strong class="text-info">{{ $item['warehouse_packages'] }} {{ $item['packaging'] }}</strong>
                                </div>
                            </div>

                            <div class="row no-gutters mb-3 text-center bg-white rounded border py-2">
                                <div class="col-6 border-right">
                                    <div class="smallest text-muted">Price / {{ $unitLabel }}</div>
                                    <div class="font-weight-bold text-dark">Tsh {{ number_format($item['selling_price']) }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="smallest text-muted">Portion Sale</div>
                                    @if($item['can_sell_in_tots'])
                                        <div class="font-weight-bold text-warning">Tsh {{ number_format($item['selling_price_per_tot']) }}</div>
                                    @else
                                        <div class="smallest italic text-muted">N/A</div>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <div class="input-group input-group-sm mb-2 shadow-sm">
                                    <div class="input-group-prepend">
                                        <button class="btn btn-light border btn-qty-minus" type="button"><i class="fa fa-minus"></i></button>
                                    </div>
                                    <input type="number" class="form-control text-center q-field font-weight-bold" value="1" min="1" max="{{ $item['warehouse_packages'] }}">
                                    <div class="input-group-append">
                                        <button class="btn btn-light border btn-qty-plus" type="button"><i class="fa fa-plus"></i></button>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary btn-block btn-sm btn-add-batch font-weight-bold shadow-sm" 
                                        data-variant-id="{{ $item['variant_id'] }}"
                                        data-name="{{ $item['display_title'] }} ({{ $displayMs }})"
                                        data-items-per-package="{{ $item['items_per_package'] }}"
                                        data-sell-price="{{ $item['selling_price'] }}"
                                        data-buy-price="{{ $item['average_buying_price'] }}"
                                        data-packaging="{{ $item['packaging'] }}"
                                        data-unit="{{ $unitLabel }}"
                                        data-can-sell-tots="{{ $item['can_sell_in_tots'] ? 1 : 0 }}"
                                        data-tots-per-unit="{{ $item['total_tots_per_unit'] }}"
                                        data-tot-price="{{ $item['selling_price_per_tot'] }}">
                                    <i class="fa fa-plus-circle mr-1"></i> REQUEST STOCK
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <i class="fa fa-folder-open-o fa-5x text-muted mb-3"></i>
                        <h4 class="text-muted">No stock available currently</h4>
                    </div>
                @endforelse
            </div>

            <!-- INVENTORY LIST (Hidden by default) -->
            <div id="inventoryList" class="table-responsive d-none mt-4">
                <table class="table table-hover table-bordered" id="listTable">
                    <thead class="bg-light">
                        <tr>
                            <th>Product Name</th>
                            <th>Brand / Category</th>
                            <th>Measure</th>
                            <th>Available Stock</th>
                            <th>Price / Unit</th>
                            <th>Quantity</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($inventoryItems as $item)
                        @php
                            $ms = $item['measurement'];
                            $displayMs = is_numeric($ms) ? ($ms > 10 ? $ms . 'ml' : $ms . 'L') : $ms;
                            $unitLabel = ($item['unit_label'] == 'ml' || strtolower($item['unit_label'] ?? '') == 'bottle' || strtolower($item['unit_label'] ?? '') == 'btl') ? 'btl' : (strtolower($item['unit_label'] ?? '') == 'piece' || strtolower($item['unit_label'] ?? '') == 'pcs' ? 'pcs' : 'btl/pcs');
                        @endphp
                        <tr class="product-card-wrapper" 
                            data-category="{{ Str::slug($item['category']) }}" 
                            data-brand="{{ Str::slug($item['brand']) }}"
                            data-name="{{ strtolower($item['display_title']) }} {{ strtolower($item['product_name']) }} {{ strtolower($item['brand'] ?? '') }}">
                            <td>
                                <strong class="text-primary">{{ $item['display_title'] }}</strong>
                            </td>
                            <td>{{ $item['brand'] }} <br><small class="text-muted">{{ $item['category'] }}</small></td>
                            <td><span class="badge badge-secondary">{{ $displayMs }}</span></td>
                            <td>
                                <strong class="text-dark">{{ $item['warehouse_packages'] }} {{ $item['packaging'] }}</strong><br>
                                <small class="text-muted">{{ number_format($item['warehouse_quantity']) }} {{ $unitLabel }} total</small>
                            </td>
                            <td>Tsh {{ number_format($item['selling_price']) }}</td>
                            <td style="width: 140px;">
                                <div class="input-group input-group-sm shadow-sm">
                                    <div class="input-group-prepend">
                                        <button class="btn btn-light border btn-qty-minus" type="button"><i class="fa fa-minus"></i></button>
                                    </div>
                                    <input type="number" class="form-control text-center q-field font-weight-bold" value="1" min="1" max="{{ $item['warehouse_packages'] }}">
                                    <div class="input-group-append">
                                        <button class="btn btn-light border btn-qty-plus" type="button"><i class="fa fa-plus"></i></button>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="btn btn-primary btn-sm btn-add-batch shadow-sm font-weight-bold" 
                                        data-variant-id="{{ $item['variant_id'] }}"
                                        data-name="{{ $item['display_title'] }} ({{ $displayMs }})"
                                        data-items-per-package="{{ $item['items_per_package'] }}"
                                        data-sell-price="{{ $item['selling_price'] }}"
                                        data-buy-price="{{ $item['average_buying_price'] }}"
                                        data-packaging="{{ $item['packaging'] }}"
                                        data-unit="{{ $unitLabel }}"
                                        data-can-sell-tots="{{ $item['can_sell_in_tots'] ? 1 : 0 }}"
                                        data-tots-per-unit="{{ $item['total_tots_per_unit'] }}"
                                        data-tot-price="{{ $item['selling_price_per_tot'] }}">
                                    <i class="fa fa-plus-circle mr-1"></i> REQUEST
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
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
    
    .inventory-item-card {
        border-radius: 15px;
        border: 1px solid #f0f0f0;
        background: #fff;
    }

    .inventory-item-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
        border-color: #007bff22;
    }

    .transition-all { transition: all 0.3s ease; }
    
    .filter-pill {
        border-radius: 20px;
        padding: 6px 16px;
        font-weight: 600;
        font-size: 11px;
        transition: all 0.2s;
    }

    .filter-pill.active {
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        transform: scale(1.05);
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function() {
    let batchItems = [];
    // 1. VIEW TOGGLE
    $('.view-btn').on('click', function() {
        const view = $(this).data('view');
        $('.view-btn').removeClass('active');
        $(this).addClass('active');

        if (view === 'grid') {
            $('#inventoryGrid').removeClass('d-none');
            $('#inventoryList').addClass('d-none');
        } else {
            $('#inventoryGrid').addClass('d-none');
            $('#inventoryList').removeClass('d-none');
        }
    });

    // 2. TABS FILTERING
    let activeCategory = 'all';
    let activeBrand = 'all';

    $('.filter-pill').on('click', function() {
        const filter = $(this).data('filter');
        const type = $(this).data('filter-type');
        
        if (type === 'category') {
            activeCategory = filter;
            $('#categoryContainer .filter-pill[data-filter-type="category"]').removeClass('active');
        } else {
            activeBrand = filter;
            $('#categoryContainer .filter-pill[data-filter-type="brand"]').removeClass('active');
        }
        
        $(this).addClass('active');
        applyFilters();
    });

    function applyFilters() {
        const searchTerm = $('#inventorySearch').val().toLowerCase();
        
        $('.product-card-wrapper').each(function() {
            const itemCat = $(this).data('category');
            const itemBrand = $(this).data('brand');
            const itemName = $(this).data('name');
            
            const matchesCat = (activeCategory === 'all' || itemCat === activeCategory);
            const matchesBrand = (activeBrand === 'all' || itemBrand === activeBrand);
            const matchesSearch = itemName.indexOf(searchTerm) > -1;
            
            if (matchesCat && matchesBrand && matchesSearch) {
                $(this).show().addClass('swipe-fade');
            } else {
                $(this).hide();
            }
        });
    }

    // 2. LIVE SEARCH
    $('#inventorySearch').on('input', function() {
        applyFilters();
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
    $(document).on('click', '.btn-add-batch', function() {
        const row = $(this).closest('.product-card-wrapper');
        const qty = parseInt(row.find('.q-field').val());
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
        $(this).html('<i class="fa fa-check"></i>').addClass('btn-success').removeClass('btn-primary');
        setTimeout(() => {
            $(this).html(originalHtml).addClass('btn-primary').removeClass('btn-success');
        }, 800);
    });

    // 4. QUANTITY SYNC (Sync between Grid and List if both visible or when switching)
    $(document).on('change keyup', '.q-field', function() {
        const val = $(this).val();
        const row = $(this).closest('.product-card-wrapper');
        const variantId = row.find('.btn-add-batch').data('variant-id');
        $(`.product-card-wrapper:has(.btn-add-batch[data-variant-id="${variantId}"]) .q-field`).not(this).val(val);
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
            
            const pkgLabel = item.qty > 1 ? item.packaging + 's' : item.packaging;
            const unitLabel = item.unit;

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
