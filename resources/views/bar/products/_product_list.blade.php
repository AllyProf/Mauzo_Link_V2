@php $lastCategory = null; @endphp
@if($variants->count() > 0)
  <div class="table-responsive">
    <table class="table table-hover table-bordered shadow-sm">
      <thead class="bg-dark text-white text-center">
        <tr>
          <th width="60px">PHOTO</th>
          <th class="text-left">PRODUCT / VARIANT NAME</th>
          <th>CATEGORY</th>
          <th>SIZE</th>
          <th>PACKAGING</th>
          <th>SELL TYPE</th>
          <th>SERVING INFO</th>
          <th width="120px">ACTIONS</th>
        </tr>
      </thead>
      <tbody>
        @foreach($variants as $variant)
          @php
            $product = $variant->product;
            $canEdit = false;
            $canDelete = false;
            if (session('is_staff')) {
              $staff = \App\Models\Staff::find(session('staff_id'));
              if ($staff && $staff->role) {
                $canEdit = $staff->role->hasPermission('products', 'edit');
                $canDelete = $staff->role->hasPermission('products', 'delete');
              }
            } else {
              $user = Auth::user();
              if ($user) {
                $canEdit = $user->hasPermission('products', 'edit') || $user->hasRole('owner');
                $canDelete = $user->hasPermission('products', 'delete') || $user->hasRole('owner');
              }
            }
          @endphp

          @if($product->category !== $lastCategory)
            <tr class="bg-light">
                <td colspan="8" class="py-2 px-3 font-weight-bold text-uppercase text-primary" style="letter-spacing: 1px; font-size: 0.85rem; background: #fdf2f2;">
                    <i class="fa fa-folder-open mr-2"></i> Category: {{ $product->category ?: 'Uncategorized' }}
                </td>
            </tr>
            @php $lastCategory = $product->category; @endphp
          @endif

          <tr class="variant-row-record">
            <td class="text-center align-middle p-1">
              @if($variant->image)
                <img src="{{ asset('storage/' . $variant->image) }}" class="rounded shadow-sm" style="width: 45px; height: 45px; object-fit: cover; border: 1px solid #eee;">
              @elseif($product->image)
                <img src="{{ asset('storage/' . $product->image) }}" class="rounded shadow-sm" style="width: 45px; height: 45px; object-fit: cover; border: 1px solid #eee;">
              @else
                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; border: 1px dashed #ccc;">
                  <i class="fa fa-cube text-muted small"></i>
                </div>
              @endif
            </td>
            <td class="align-middle">
                <div class="font-weight-bold text-dark mb-0" style="font-size: 1rem;">{{ $variant->name ?: $product->name }}</div>
                @if($product->brand && strtolower(trim($product->brand)) !== strtolower(trim($variant->name)))
                    <small class="text-muted text-uppercase font-weight-bold">{{ $product->brand }}</small>
                @endif
            </td>
            <td class="text-center align-middle">
                <span class="badge badge-pill badge-light border px-3">{{ $product->category ?: 'General' }}</span>
            </td>
            <td class="text-center align-middle font-weight-bold">
                {{ $variant->measurement }}{{ $variant->unit }}
            </td>
            <td class="text-center align-middle">
                @if($variant->items_per_package > 1)
                    <span class="badge badge-danger px-3 shadow-sm">{{ $variant->packaging }} ({{ $variant->items_per_package }} units)</span>
                @else
                    <span class="badge badge-secondary px-3">{{ $variant->packaging }}</span>
                @endif
            </td>
            <td class="text-center align-middle">
                @if($variant->selling_type === 'bottle')
                    <span class="text-dark font-weight-bold smallest text-uppercase">Bottle Only</span>
                @elseif($variant->selling_type === 'glass')
                    <span class="text-info font-weight-bold smallest text-uppercase">Shot/Glass Only</span>
                @else
                    <span class="text-success font-weight-bold smallest text-uppercase">Mixed (Both)</span>
                @endif
            </td>
            <td class="text-center align-middle">
                @if($variant->total_tots > 0)
                    <div class="text-primary font-weight-bold">{{ $variant->total_tots }} Servings</div>
                    <small class="text-muted smallest">per bottle/pc</small>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td class="text-center align-middle">
                <div class="btn-group">
                    @if($canEdit)
                        <a href="{{ route('bar.products.edit', $product) }}" class="btn btn-outline-primary btn-sm border-0" title="Edit">
                            <i class="fa fa-pencil fa-lg"></i>
                        </a>
                    @endif
                    @if($canDelete)
                        <form action="{{ route('bar.products.destroy', $product) }}" method="POST" class="d-inline" data-product-name="{{ $variant->name }}">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="btn btn-outline-danger btn-sm border-0 delete-product-btn" title="Delete">
                                <i class="fa fa-trash fa-lg"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="mt-4 d-flex justify-content-center">
    {{ $variants->links() }}
  </div>

  <style>
      .variant-row-record:hover { background: #fdfdfd; }
      .smallest { font-size: 0.75rem; }
      .table td { border-bottom: 1px solid #f1f1f1 !important; }
  </style>
@else
  <div class="text-center py-5 bg-white rounded shadow-sm border">
    <i class="fa fa-cubes fa-4x text-light mb-3"></i>
    <h4 class="font-weight-bold">No Products Found</h4>
    <p class="text-muted">Adjust your filters or add a new product to your inventory.</p>
  </div>
@endif
