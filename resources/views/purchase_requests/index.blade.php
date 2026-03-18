@extends('layouts.dashboard')

@section('title', 'Purchase Requests')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    .table-sm th, .table-sm td {
        padding: 6px !important;
        font-size: 13px;
    }
    /* Manually support modal-xl for Bootstrap 4.1.1 */
    @media (min-width: 1200px) {
        .modal-xl {
            max-width: 80% !important;
        }
    }
    .product-select-container { width: 100%; min-width: 200px; }
</style>

<div class="app-title">
    <div>
        <h1><i class="fa fa-shopping-cart"></i> Purchase Requests</h1>
        <p>Submit and process business purchasing needs</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
        <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item">Purchase Requests</li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="tile-title-w-btn">
                <h3 class="title">All Requests</h3>
                <p>
                    <button class="btn btn-primary icon-btn" data-toggle="modal" data-target="#newRequestModal">
                        <i class="fa fa-plus"></i> New Purchase Request
                    </button>
                </p>
            </div>

            @php
                $totalPending = $requests->where('status', 'pending')->sum('estimated_amount');
                $totalApproved = $requests->where('status', 'approved')->sum('estimated_amount');
                $totalIssued = $requests->where('status', 'issued')->where('processed_at', '>=', now()->startOfDay())->sum('issued_amount');
            @endphp

            <div class="row mt-2 mb-4">
                <div class="col-md-4">
                    <div class="widget-small info coloured-icon shadow-sm"><i class="icon fa fa-hourglass-start fa-3x"></i>
                        <div class="info">
                            <h5>Total Pending</h5>
                            <p><b>TSh {{ number_format($totalPending) }}</b></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="widget-small primary coloured-icon shadow-sm"><i class="icon fa fa-check-circle fa-3x"></i>
                        <div class="info">
                            <h5>Total Approved</h5>
                            <p><b>TSh {{ number_format($totalApproved) }}</b></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="widget-small success coloured-icon shadow-sm"><i class="icon fa fa-money fa-3x"></i>
                        <div class="info">
                            <h5 style="color: #000;">Issued Today</h5>
                            <p style="color: #000;"><b>TSh {{ number_format($totalIssued) }}</b></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Date</th>
                            <th>Requester</th>
                            <th>Items Needed</th>
                            <th>Est. Amount</th>
                            <th>Issued Amount</th>
                            <th>Status</th>
                            <th>Processed By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $request)
                            <tr>
                                <td>{{ $request->request_number }}</td>
                                <td>{{ $request->created_at->format('M d, Y') }}</td>
                                <td>{{ $request->requester->full_name }}</td>
                                <td title="{{ $request->items_list }}">
                                    @php $itemLines = explode("\n", $request->items_list); @endphp
                                    @foreach($itemLines as $item)
                                        <div class="text-nowrap small">{{ $item }}</div>
                                        @if($loop->index >= 1 && count($itemLines) > 2) 
                                            <div class="text-muted small">...and {{ count($itemLines) - 2 }} more</div>
                                            @break 
                                        @endif
                                    @endforeach
                                </td>
                                <td>TSh {{ number_format($request->estimated_amount) }}</td>
                                <td>
                                    @if($request->issued_amount)
                                        TSh {{ number_format($request->issued_amount) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $request->progressStatusClasses() }}">
                                        {{ ucfirst($request->status) }}
                                    </span>
                                </td>
                                <td>{{ $request->processor->full_name ?? '-' }}</td>
                                <td>
                                    <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#viewRequestModal{{ $request->id }}">
                                        <i class="fa fa-eye"></i> View
                                    </button>
                                    
                                    @if($isPowerUser && in_array($request->status, ['pending', 'approved']))
                                        <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editRequestModal{{ $request->id }}">
                                            <i class="fa fa-pencil"></i> Edit
                                        </button>
                                    @endif
                                    
                                    @if($isPowerUser && $request->isPending())
                                        <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#processModal{{ $request->id }}">
                                            <i class="fa fa-check"></i> Process
                                        </button>
                                    @elseif($isPowerUser && $request->status === 'approved')
                                        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#issueModal{{ $request->id }}">
                                            <i class="fa fa-money"></i> Issue Amount
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            
                            <!-- Edit Modal -->
                            <div class="modal fade" id="editRequestModal{{ $request->id }}" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <form action="{{ route('purchase-requests.update', $request->id) }}" method="POST">
                                        @csrf
                                        <div class="modal-content">
                                            <div class="modal-header bg-warning">
                                                <h5 class="modal-title">Edit Purchase Request: {{ $request->request_number }}</h5>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>Items & quantities (Adjust if needed)</label>
                                                    <textarea name="items_list" class="form-control" rows="6" required>{{ $request->items_list }}</textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label>Total Estimated Amount (TSh)</label>
                                                    <input type="number" name="estimated_amount" class="form-control" value="{{ intval($request->estimated_amount) }}" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Notes</label>
                                                    <textarea name="notes" class="form-control" rows="2">{{ $request->notes }}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-warning">Save Changes</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- View Modal -->
                            <div class="modal fade" id="viewRequestModal{{ $request->id }}" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Request Details: {{ $request->request_number }}</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Requester:</strong> {{ $request->requester->full_name }}</p>
                                            <hr>
                                            <p><strong>Items Requested:</strong></p>
                                            <div class="p-3 bg-light rounded border small">
                                                {!! nl2br(e($request->items_list)) !!}
                                            </div>
                                            <hr>
                                            <p><strong>Total Estimated:</strong> TSh {{ number_format($request->estimated_amount) }}</p>
                                            @if($request->notes)
                                                <p><strong>Notes:</strong> {{ $request->notes }}</p>
                                            @endif
                                            @if($request->reason)
                                                <p class="text-danger"><strong>Reason:</strong> {{ $request->reason }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Process Modal -->
                            <div class="modal fade" id="processModal{{ $request->id }}" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <form action="{{ route('purchase-requests.process', $request->id) }}" method="POST">
                                        @csrf
                                        <div class="modal-content">
                                            <div class="modal-header bg-info text-white">
                                                <h5 class="modal-title">Process Request</h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body text-center">
                                                <div class="btn-group btn-group-toggle mb-4" data-toggle="buttons">
                                                    <label class="btn btn-outline-success active">
                                                        <input type="radio" name="action" value="approve" checked> Approve
                                                    </label>
                                                    <label class="btn btn-outline-danger">
                                                        <input type="radio" name="action" value="reject"> Reject
                                                    </label>
                                                </div>
                                                <textarea name="reason" class="form-control" placeholder="Optional notes or rejection reason..."></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-info">Submit</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Issue Modal -->
                            <div class="modal fade" id="issueModal{{ $request->id }}" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <form action="{{ route('purchase-requests.process', $request->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="action" value="issue">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">Issue Funds</h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>Amount to Issue (TSh)</label>
                                                    <input type="number" name="issued_amount" class="form-control" value="{{ intval($request->estimated_amount) }}" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-primary">Confirm & Issue</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        @empty
                            <tr><td colspan="9" class="text-center py-4">No requests found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $requests->links() }}
            </div>
        </div>
    </div>
</div>

<!-- New Request Modal -->
<div class="modal fade" id="newRequestModal" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <form action="{{ route('purchase-requests.store') }}" method="POST" id="requestForm">
            @csrf
            <div class="modal-content shadow-lg border-primary">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa fa-plus-circle"></i> Create New Purchase Request</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body px-4">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="itemsTable">
                            <thead class="bg-light text-uppercase small font-weight-bold">
                                <tr>
                                    <th width="20%">Category</th>
                                    <th width="30%">Item Name</th>
                                    <th width="12%">In Stock</th>
                                    <th width="15%">Qty Needed</th>
                                    <th width="18%">Price/Crate (TSh)</th>
                                    <th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <tr class="item-row">
                                    <td>
                                        <select class="form-control category-select">
                                            <option value="">-- All Categories --</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category }}">{{ $category }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="items[0][product_id]" class="form-control select2 product-select" required>
                                            <option value="">-- Choose Item --</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" data-category="{{ $product->category_name }}" data-stock="{{ $product->available_stock }}">
                                                    {{ $product->product->name }} - {{ $product->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-center"><span class="stock-badge badge badge-info">0</span></td>
                                    <td>
                                        <input type="number" name="items[0][quantity]" class="form-control qty-input font-weight-bold" min="0.01" step="0.01" required placeholder="0.00">
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][price_per_unit]" class="form-control price-input text-success font-weight-bold" required placeholder="0.00">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-row" style="display:none;"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="row align-items-center mb-4">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-outline-primary" id="addRow">
                                <i class="fa fa-plus"></i> Add Another Row
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            <div class="p-3 bg-dark text-white rounded shadow-sm">
                                <h4 class="mb-0">GRAND TOTAL: <span class="text-warning">TSh <span id="grandTotalText">0</span></span></h4>
                                <input type="hidden" name="estimated_amount" id="grandTotalInput" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <label class="font-weight-bold">Additional Staff Notes</label>
                        <textarea name="notes" class="form-control border-primary" rows="2" placeholder="Explain why these items are needed..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-lg px-5">Submit Request to Accountant</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // We still have $products available here
        const productsData = @json($products);

        function updateDisabledOptions() {
            let selectedIds = [];
            $('.product-select').each(function() {
                const val = $(this).val();
                if (val) selectedIds.push(val);
            });

            $('.product-select').each(function() {
                const currentVal = $(this).val();
                $(this).find('option').each(function() {
                    const optVal = $(this).val();
                    if (optVal && optVal !== currentVal && selectedIds.includes(optVal)) {
                        $(this).prop('disabled', true);
                    } else {
                        $(this).prop('disabled', false);
                    }
                });
                // Note: select2 doesn't always reflect disabled options automatically, need to trigger change or re-init if problematic
            });
        }

        function initRow(row) {
            const productSelect = row.find('.product-select');
            const categorySelect = row.find('.category-select');
            const stockBadge = row.find('.stock-badge');
            
            productSelect.select2({
                dropdownParent: $('#newRequestModal'),
                width: '100%'
            });

            categorySelect.change(function() {
                const category = $(this).val();
                productSelect.empty().append('<option value="">-- Choose Item --</option>');
                
                const filtered = productsData.filter(p => !category || p.category_name === category);
                filtered.forEach(p => {
                    productSelect.append(`<option value="${p.id}" data-category="${p.category_name}" data-stock="${p.available_stock}">${p.product.name} - ${p.name}</option>`);
                });
                
                updateDisabledOptions();
                productSelect.trigger('change');
            });

            productSelect.on('change', function() {
                const stock = $(this).find(':selected').data('stock') ?? 0;
                stockBadge.text(stock);
                updateDisabledOptions();
                calculateTotal();
            });

            row.find('.qty-input, .price-input').on('input', calculateTotal);
        }

        function calculateTotal() {
            let grandTotal = 0;
            $('.item-row').each(function() {
                const qty = parseFloat($(this).find('.qty-input').val()) || 0;
                const price = parseFloat($(this).find('.price-input').val()) || 0;
                grandTotal += (qty * price);
            });
            
            $('#grandTotalText').text(grandTotal.toLocaleString());
            $('#grandTotalInput').val(grandTotal);
        }

        initRow($('.item-row'));

        let fullRowIdx = 1;
        $('#addRow').click(function() {
            const newRow = $('.item-row:first').clone();
            newRow.find('.select2-container').remove();
            newRow.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').show();
            newRow.find('.category-select').val('');
            newRow.find('.product-select').attr('name', `items[${fullRowIdx}][product_id]`).val('');
            newRow.find('.qty-input').attr('name', `items[${fullRowIdx}][quantity]`).val('');
            newRow.find('.price-input').attr('name', `items[${fullRowIdx}][price_per_unit]`).val('');
            newRow.find('.stock-badge').text('0');
            newRow.find('.remove-row').show();
            
            $('#itemsBody').append(newRow);
            initRow(newRow);
            fullRowIdx++;
        });

        $(document).on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            calculateTotal();
        });
    });
</script>
@endpush
@endsection
