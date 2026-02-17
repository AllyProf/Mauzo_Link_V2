@php $lastCategory = null; @endphp
@if($products->count() > 0)
  <div class="row">
    @foreach($products as $product)
      @php
        $canView = false;
        $canEdit = false;
        $canDelete = false;
        if (session('is_staff')) {
          $staff = \App\Models\Staff::find(session('staff_id'));
          if ($staff && $staff->role) {
            $canView = $staff->role->hasPermission('products', 'view');
            $canEdit = $staff->role->hasPermission('products', 'edit');
            $canDelete = $staff->role->hasPermission('products', 'delete');
            if (!$canDelete) {
              $roleName = strtolower(trim($staff->role->name ?? ''));
              if (in_array($roleName, ['stock keeper', 'stockkeeper', 'counter', 'bar counter'])) {
                $canDelete = true;
              }
            }
          }
        } else {
          $user = Auth::user();
          if ($user) {
            $canView = $user->hasPermission('products', 'view') || $user->hasRole('owner');
            $canEdit = $user->hasPermission('products', 'edit') || $user->hasRole('owner');
            $canDelete = $user->hasPermission('products', 'delete') || $user->hasRole('owner');
          }
        }
      @endphp

      @if($product->category !== $lastCategory)
        <div class="col-12 mt-4 mb-3">
          <h4 class="category-header">{{ $product->category ?: 'Uncategorized' }}</h4>
        </div>
        @php $lastCategory = $product->category; @endphp
      @endif

      <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
        <div class="product-card">
          <div class="product-img-container">
            <div class="status-badge-overlay">
              <span class="badge badge-pill {{ $product->is_active ? 'badge-success' : 'badge-danger' }} shadow-sm px-3 py-2">
                {{ $product->is_active ? '● Active' : '○ Inactive' }}
              </span>
            </div>
            <div class="category-badge-overlay">
              <span class="badge badge-pill bg-glass px-3 py-2 text-dark font-weight-bold">
                <i class="fa fa-folder-o mr-1"></i> {{ $product->category ?: 'General' }}
              </span>
            </div>
            @if($product->image)
              <img src="{{ asset('storage/' . $product->image) }}" class="product-img" alt="{{ $product->name }}">
            @else
              <div class="d-flex align-items-center justify-content-center bg-light h-100">
                <i class="fa fa-cube fa-5x text-muted opacity-25"></i>
              </div>
            @endif
          </div>
          
          <div class="product-details">
            <h5 class="product-title">{{ $product->name }}</h5>
            
            <div class="product-meta">
              <i class="fa fa-tag"></i>
              <span>Brand: <strong>{{ $product->brand ?: 'N/A' }}</strong></span>
            </div>
            
            <div class="product-meta">
              <i class="fa fa-truck"></i>
              <span>Supplier: <strong>{{ $product->supplier->company_name ?? 'N/A' }}</strong></span>
            </div>

            <div class="variant-tags">
              @foreach($product->variants->take(3) as $variant)
                <span class="variant-badge">
                  {{ $variant->name ?: $variant->measurement . ' ' . $variant->packaging }}
                </span>
              @endforeach
              @if($product->variants->count() > 3)
                <span class="variant-badge bg-primary text-white">+{{ $product->variants->count() - 3 }} more</span>
              @endif
              @if($product->variants->count() == 0)
                <span class="text-muted small italic">No variants defined</span>
              @endif
            </div>
          </div>

          <div class="product-actions mt-auto">
            @if($canView)
              <button type="button" class="btn btn-outline-info btn-sm view-product flex-grow-1" data-product-id="{{ $product->id }}">
                <i class="fa fa-eye"></i> Details
              </button>
            @endif
            @if($canEdit)
              <a href="{{ route('bar.products.edit', $product) }}" class="btn btn-outline-warning btn-sm flex-grow-1">
                <i class="fa fa-pencil"></i> Edit
              </a>
            @endif
            @if($canDelete)
              <button type="button" class="btn btn-outline-danger btn-sm delete-product-btn-card px-3" data-product-id="{{ $product->id }}" data-product-name="{{ $product->name }}">
                <i class="fa fa-trash"></i>
              </button>
            @endif
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="mt-5 d-flex justify-content-center">
    {{ $products->links() }}
  </div>
@else
  <div class="text-center py-5 bg-white rounded-lg shadow-sm">
    <div class="mb-4">
      <i class="fa fa-cubes fa-5x text-light"></i>
    </div>
    <h3 class="font-weight-bold">No Products Found</h3>
    <p class="text-muted mx-auto" style="max-width: 500px;">
      We couldn't find any products matching your search or filters. Try adjusting your criteria.
    </p>
  </div>
@endif
