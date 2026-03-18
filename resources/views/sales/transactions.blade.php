@extends('layouts.dashboard')

@section('title', 'Transactions')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-exchange"></i> Transactions History</h1>
    <p>View all sales transactions</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Transactions</li>
  </ul>
</div>

<!-- Summary Cards -->
<div class="row">
    <div class="col-md-6 col-lg-6">
        <div class="widget-small primary coloured-icon"><i class="icon fa fa-money fa-3x"></i>
            <div class="info">
                <h4>Total Sales Value</h4>
                <p><b>TSh {{ number_format($totalTotal) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-6">
        <div class="widget-small info coloured-icon"><i class="icon fa fa-check-circle fa-3x"></i>
            <div class="info">
                <h4>Total Paid Value</h4>
                <p><b>TSh {{ number_format($totalPaid) }}</b></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-title-w-btn">
        <h3 class="title">Filter Transactions</h3>
      </div>
      <div class="tile-body">
        <form action="{{ route('sales.transactions') }}" method="GET" class="row">
          <div class="col-md-3 mb-2">
            <label>Payment Method</label>
            <select name="method" class="form-control">
              <option value="all" {{ request('method') == 'all' ? 'selected' : '' }}>All</option>
              <option value="cash" {{ request('method') == 'cash' ? 'selected' : '' }}>Cash</option>
              <option value="mobile_money" {{ request('method') == 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
              <option value="credit_card" {{ request('method') == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
            </select>
          </div>
          <div class="col-md-3 mb-2">
            <label>From Date</label>
            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
          </div>
          <div class="col-md-3 mb-2">
            <label>To Date</label>
            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
          </div>
          <div class="col-md-3 mb-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-filter"></i> Filter</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover table-bordered" id="transactionsTable">
            <thead>
              <tr>
                <th>Txn Date</th>
                <th>Order #</th>
                <th>Total Value</th>
                <th>Amount Paid</th>
                <th>Payment Method</th>
                <th>Status</th>
                <th>Reference</th>
              </tr>
            </thead>
            <tbody>
              @forelse($transactions as $txn)
                <tr>
                  <td>{{ $txn->updated_at->format('M d, Y H:i') }}</td>
                  <td><strong>{{ $txn->order_number }}</strong></td>
                  <td class="font-weight-bold">TSh {{ number_format($txn->total_amount) }}</td>
                  <td class="text-success font-weight-bold">TSh {{ number_format($txn->paid_amount) }}</td>
                  <td>
                    @if($txn->payment_method == 'cash')
                      <span class="badge badge-success"><i class="fa fa-money"></i> Cash</span>
                    @elseif($txn->payment_method == 'mobile_money')
                      <span class="badge badge-info"><i class="fa fa-mobile"></i> Mobile Money</span>
                      @if($txn->mobile_money_number)
                        <br><small>{{ $txn->mobile_money_number }}</small>
                      @endif
                    @elseif($txn->payment_method == 'credit_card')
                      <span class="badge badge-primary"><i class="fa fa-credit-card"></i> Card</span>
                    @else
                      <span class="badge badge-secondary">{{ $txn->payment_method ? ucfirst(str_replace('_', ' ', $txn->payment_method)) : 'N/A' }}</span>
                    @endif
                  </td>
                  <td>
                    @if($txn->payment_status == 'paid')
                      <span class="badge badge-success">Completed</span>
                    @elseif($txn->payment_status == 'partial')
                      <span class="badge badge-warning">Partial</span>
                    @else
                      <span class="badge badge-secondary">{{ ucfirst($txn->payment_status) }}</span>
                    @endif
                  </td>
                  <td>
                    {{ $txn->transaction_reference ?? 'N/A' }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">
                    <i class="fa fa-exchange fa-3x mb-2 d-block"></i>
                    No transactions found matching your criteria.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          {{ $transactions->appends(request()->query())->links() }}
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
