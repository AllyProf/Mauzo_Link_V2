@extends('layouts.dashboard')

@section('title', $pageTitle ?? 'Suppliers')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-truck"></i> {{ $pageTitle ?? 'Suppliers' }}</h1>
    <p>{{ $pageDescription ?? 'Manage your suppliers' }}</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">{{ $pageTitle ?? 'Suppliers' }}</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">{{ $type === 'food' ? 'Food Suppliers' : ($type === 'beverage' ? 'Beverage Suppliers' : 'All Suppliers') }}</h3>
        @php
          $createType = ($type && $type !== 'all') ? $type : 'general';
        @endphp
        <a href="{{ route('bar.suppliers.create', ['type' => $createType]) }}" class="btn btn-primary">
          <i class="fa fa-plus"></i> Add {{ $type === 'food' ? 'Food ' : ($type === 'beverage' ? 'Beverage ' : '') }}Supplier
        </a>
      </div>

      @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          {{ session('success') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      @endif

      @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          {{ session('error') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      @endif

      <div class="tile-body">
        @if($suppliers->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="suppliersTable">
              <thead>
                <tr>
                  <th>Company Name</th>
                  <th>Type</th>
                  <th>Contact Person</th>
                  <th>Phone</th>
                  <th>Email</th>
                  <th>City</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($suppliers as $supplier)
                  <tr>
                    <td><strong>{{ $supplier->company_name }}</strong></td>
                    <td>
                      <span class="badge {{ $supplier->supplier_type === 'food' ? 'badge-info' : ($supplier->supplier_type === 'beverage' ? 'badge-primary' : 'badge-secondary') }}">
                        {{ ucfirst($supplier->supplier_type ?? 'general') }}
                      </span>
                    </td>
                    <td>{{ $supplier->contact_person ?? 'N/A' }}</td>
                    <td>{{ $supplier->phone }}</td>
                    <td>{{ $supplier->email ?? 'N/A' }}</td>
                    <td>{{ $supplier->city ?? 'N/A' }}</td>
                    <td>
                      <span class="badge {{ $supplier->is_active ? 'badge-success' : 'badge-danger' }}">
                        {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </td>
                    <td>
                      <a href="{{ route('bar.suppliers.show', $supplier) }}" class="btn btn-info btn-sm">
                        <i class="fa fa-eye"></i> View
                      </a>
                      <a href="{{ route('bar.suppliers.edit', $supplier) }}" class="btn btn-warning btn-sm">
                        <i class="fa fa-pencil"></i> Edit
                      </a>
                      <form action="{{ route('bar.suppliers.destroy', $supplier) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this supplier?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                          <i class="fa fa-trash"></i> Delete
                        </button>
                      </form>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="mt-3">
            {{ $suppliers->links() }}
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No suppliers registered yet. 
            <a href="{{ route('bar.suppliers.create') }}">Add your first supplier</a> to get started.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<!-- Data table plugin-->
<script type="text/javascript" src="{{ asset('js/plugins/jquery.dataTables.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/plugins/dataTables.bootstrap.min.js') }}"></script>
<script type="text/javascript">
  $(document).ready(function() {
    $('#suppliersTable').DataTable({
      "paging": false,
      "info": false,
      "searching": true,
    });
  });
</script>
@endpush
