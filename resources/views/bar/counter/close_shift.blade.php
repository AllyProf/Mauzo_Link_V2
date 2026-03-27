@extends('layouts.dashboard')

@section('title', 'Close Shift & Reconciliation')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile shadow-lg border-0" style="border-radius: 20px;">
            <div class="tile-title-w-btn border-bottom pb-3 mb-4">
                <h3 class="title text-danger"><i class="fa fa-lock"></i> Finalize Shift & Reconcile</h3>
                <div class="text-right">
                    <a href="{{ route('bar.counter.dashboard') }}" class="btn btn-outline-secondary font-weight-bold">
                        <i class="fa fa-arrow-left"></i> BACK TO DASHBOARD
                    </a>
                </div>
            </div>

            <form action="{{ route('bar.counter.shift.close') }}" method="POST">
                @csrf
                
                <div class="bg-light p-4 rounded mb-4 border shadow-xs">
                     <div class="row text-center mb-0">
                         <div class="col-md-3 border-right">
                             <small class="text-muted d-block text-uppercase font-weight-bold smallest">Current Staff</small>
                             <h5 class="mb-0 text-dark font-weight-bold">{{ $staff->full_name }}</h5>
                         </div>
                         <div class="col-md-3 border-right">
                             <small class="text-muted d-block text-uppercase font-weight-bold smallest">Shift Revenue</small>
                             <h4 class="mb-0 text-primary font-weight-bold">TSh {{ number_format($shiftRevenue) }}</h4>
                         </div>
                         <div class="col-md-3 border-right">
                             <small class="text-muted d-block text-uppercase font-weight-bold smallest">Total Orders</small>
                             <h4 class="mb-0 text-dark font-weight-bold">{{ $shiftOrderCount }}</h4>
                         </div>
                         <div class="col-md-3">
                             <small class="text-muted d-block text-uppercase font-weight-bold smallest">Shift Number</small>
                             <h5 class="mb-0 text-muted font-weight-bold">{{ $activeShift->shift_number }}</h5>
                             <small class="smallest opacity-75">Opened: {{ $activeShift->opened_at->format('d M, H:i') }}</small>
                         </div>
                     </div>
                </div>

                <div class="row">
                    <!-- Left: Waiter Breakdown -->
                    <div class="col-md-5">
                        <div class="p-3 bg-white border rounded shadow-xs h-100">
                            <h6 class="font-weight-bold text-uppercase smallest text-muted mb-3 border-bottom pb-2">
                                <i class="fa fa-users text-info"></i> Waiter Performance (Draft)
                            </h6>
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-hover border mb-0">
                                    <thead class="bg-light sticky-top">
                                        <tr>
                                            <th class="smallest">Waiter</th>
                                            <th class="smallest text-center">Orders</th>
                                            <th class="smallest text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($shiftWaiterBreakdown as $w)
                                            <tr>
                                                <td class="smallest font-weight-bold">{{ $w['name'] }}</td>
                                                <td class="smallest text-center">{{ $w['orders'] }}</td>
                                                <td class="smallest text-right font-weight-bold text-dark">TSh {{ number_format($w['amount']) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center py-4 text-muted small">No orders recorded this shift</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Stock Remains -->
                    <div class="col-md-7">
                        <div class="p-3 bg-white border rounded shadow-xs h-100">
                            <h6 class="font-weight-bold text-uppercase smallest text-muted mb-3 border-bottom pb-2">
                                <i class="fa fa-cubes text-info"></i> Remaining Inventory (Closing Stock)
                            </h6>
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-hover border mb-0">
                                    <thead class="bg-light sticky-top">
                                        <tr>
                                            <th class="smallest">Product Item</th>
                                            <th class="smallest text-center">Qty Left</th>
                                            <th class="smallest text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($shiftStockRemains as $s)
                                            <tr>
                                                <td class="smallest">{{ $s['name'] }}</td>
                                                <td class="smallest text-center font-weight-bold">{{ number_format($s['quantity']) }}</td>
                                                <td class="smallest text-center">
                                                    @if($s['quantity'] < 10)
                                                        <span class="badge badge-danger px-1 smallest">LOW</span>
                                                    @else
                                                        <span class="badge badge-success px-1 smallest">OK</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center py-4 text-muted small">No stock items in counter</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-top">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="tile bg-light border p-4 shadow-sm" style="border-radius: 15px;">
                                <h5 class="font-weight-bold text-center mb-4 text-uppercase smallest text-muted border-bottom pb-2">Final Reconciliation</h5>
                                
                                <div class="row align-items-center">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <div class="form-group mb-0">
                                            <label class="font-weight-bold text-danger text-uppercase mb-1 d-block" style="font-size: 11px;">
                                                <i class="fa fa-money"></i> Physical Cash in Drawer
                                            </label>
                                            <div class="input-group input-group-lg">
                                                <div class="input-group-prepend"><span class="input-group-text bg-danger text-white border-danger font-weight-bold">TSh</span></div>
                                                <input type="number" name="closing_balance" class="form-control font-weight-bold border-danger shadow-sm" placeholder="0" required>
                                            </div>
                                            <div class="mt-2 p-2 bg-white rounded border small">
                                                <div class="d-flex justify-content-between text-muted">
                                                    <span>Opening Cash:</span>
                                                    <span>TSh {{ number_format($activeShift->opening_balance) }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between text-success">
                                                    <span>Shift Sales:</span>
                                                    <span>+ TSh {{ number_format($shiftRevenue) }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between font-weight-bold border-top mt-1 pt-1 text-dark">
                                                    <span>Expected Final:</span>
                                                    <span>TSh {{ number_format($activeShift->opening_balance + $shiftRevenue) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label class="font-weight-bold small text-uppercase text-muted mb-1 d-block">
                                                <i class="fa fa-commenting-o"></i> Handover/Closing Remarks
                                            </label>
                                            <textarea name="notes" class="form-control shadow-sm" rows="5" placeholder="Note any variances, broken items, or specific instructions for the next staff..."></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 border-top pt-3 text-center">
                                    <button type="submit" class="btn btn-danger btn-lg px-5 py-3 shadow-lg font-weight-bold" id="btn-verify-close">
                                        <i class="fa fa-power-off"></i> VERIFY & CLOSE SHIFT
                                    </button>
                                    <p class="mt-2 smallest text-muted italic">By clicking verify, you confirm that cash and stock counts are accurate.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#btn-verify-close').on('click', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        
        if (!form[0].checkValidity()) {
            form[0].reportValidity();
            return;
        }
        
        showConfirm(
            'You are about to finalize this shift reconciliation. Please ensure physical cash and stock remains are accurate.',
            'Confirm Shift Closure',
            function() {
                // Show loading state
                Swal.fire({
                    title: 'Closing Shift...',
                    text: 'Please wait while we finalize records',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                form.submit();
            }
        );
    });
});
</script>
@endpush
