@extends('layouts.dashboard')

@section('title', 'Products')

@section('content')
<style>
  :root {
    --primary-gradient: linear-gradient(135deg, #940000 0%, #610000 100%);
    --card-shadow: 0 10px 20px rgba(0,0,0,0.05), 0 6px 6px rgba(0,0,0,0.06);
    --card-hover-shadow: 0 15px 30px rgba(0,0,0,0.12), 0 10px 10px rgba(0,0,0,0.08);
  }

  .product-card {
    border: none;
    border-radius: 16px;
    overflow: hidden;
    background: #fff;
    transition: all 0.3s cubic-bezier(.25,.8,.25,1);
    box-shadow: var(--card-shadow);
    height: 100%;
  }

  .product-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--card-hover-shadow);
  }

  .product-img-container {
    height: 220px;
    position: relative;
    overflow: hidden;
    background: #f8f9fa;
  }

  .product-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
  }

  .product-card:hover .product-img {
    transform: scale(1.1);
  }

  .status-badge-overlay {
    position: absolute;
    top: 15px;
    right: 15px;
    z-index: 2;
  }

  .category-badge-overlay {
    position: absolute;
    bottom: 15px;
    left: 15px;
    z-index: 2;
  }

  .product-details {
    padding: 1.5rem;
    font-family: "Century Gothic", sans-serif !important;
  }

  .product-title {
    font-weight: 700;
    font-size: 1.25rem;
    color: #333;
    margin-bottom: 0.5rem;
    line-height: 1.2;
  }

  .product-meta {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
  }

  .product-meta i {
    width: 1.25rem;
    color: #940000;
  }

  .variant-tags {
    margin-top: 1rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .variant-badge {
    background: #f1f3f5;
    color: #495057;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
  }

  .product-actions {
    padding: 1rem 1.5rem;
    background: #fcfcfc;
    border-top: 1px solid #eee;
    display: flex;
    gap: 0.5rem;
  }

  .category-header {
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #444;
    position: relative;
    padding-bottom: 10px;
  }

  .category-header::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    height: 3px;
    width: 50px;
    background: var(--primary-gradient);
    border-radius: 3px;
  }

  .btn-premium {
    background: var(--primary-gradient);
    color: white;
    border: none;
    border-radius: 10px;
    padding: 0.6rem 1.2rem;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .btn-premium:hover {
    color: white;
    filter: brightness(1.1);
    box-shadow: 0 5px 15px rgba(148, 0, 0, 0.3);
  }

  .bg-glass {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.3);
  }
  
  .gap-2 {
    gap: 0.5rem;
  }
</style>

<div class="app-title">
  <div>
    <h1><i class="fa fa-cube text-primary"></i> Products</h1>
    <p>Inventory Intelligence & Product Management</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Products</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="card bg-transparent border-0 mb-4">
      <div class="card-body p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h3 class="mb-0 font-weight-bold">Product Showcase</h3>
          @php
            $canCreate = false;
            if (session('is_staff')) {
              $staff = \App\Models\Staff::find(session('staff_id'));
              if ($staff && $staff->role) {
                $canCreate = $staff->role->hasPermission('products', 'create');
                if (!$canCreate) {
                  $roleName = strtolower(trim($staff->role->name ?? ''));
                  if (in_array($roleName, ['stock keeper', 'stockkeeper', 'counter', 'bar counter'])) {
                    $canCreate = true;
                  }
                }
              }
            } else {
              $user = Auth::user();
              if ($user) {
                $canCreate = $user->hasPermission('products', 'create') || $user->hasRole('owner');
              }
            }
          @endphp
          @if($canCreate)
            <a href="{{ route('bar.products.create') }}" class="btn-premium">
              <i class="fa fa-plus-circle"></i> Create New Product
            </a>
          @endif
        </div>

        <!-- Filter & Search Bar -->
        <div class="row mb-4">
          <div class="col-md-12">
            <div class="card shadow-sm border-0 rounded-lg">
              <div class="card-body p-4">
                <form id="filterForm" action="{{ route('bar.products.index') }}" method="GET">
                  <div class="row align-items-end">
                    <div class="col-lg-5 mb-3 mb-lg-0">
                      <label class="font-weight-bold small text-uppercase text-muted">Search Inventory</label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text bg-light border-0"><i class="fa fa-search"></i></span>
                        </div>
                        <input type="text" id="searchInput" name="search" class="form-control bg-light border-0" placeholder="Product name or brand..." value="{{ $search ?? '' }}">
                      </div>
                    </div>
                    <div class="col-lg-4 mb-3 mb-lg-0">
                      <label class="font-weight-bold small text-uppercase text-muted">Filter Category</label>
                      <select id="categoryFilter" name="category" class="form-control bg-light border-0">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                          <option value="{{ $cat }}" {{ ($category ?? '') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-lg-3">
                      <div class="d-flex">
                        <button type="button" id="resetFilters" class="btn btn-light btn-block py-2 ml-2" title="Reset All Filters">
                          <i class="fa fa-refresh mr-2"></i> Reset
                        </button>
                      </div>
                    </div>
                  </div>
                </form>
                
                <div class="mt-4">
                  <label class="font-weight-bold small text-uppercase text-muted d-block mb-3">Quick Navigation</label>
                  <div class="d-flex flex-wrap quick-nav-tags" style="gap: 10px;">
                    <button type="button" data-category="" class="btn btn-sm px-3 py-2 btn-category {{ !($category ?? '') ? 'btn-primary shadow-sm' : 'btn-light text-muted border-0' }}" style="border-radius: 50px;">
                      All Products
                    </button>
                    @foreach($categories as $cat)
                      <button type="button" data-category="{{ $cat }}" 
                         class="btn btn-sm px-3 py-2 btn-category {{ ($category ?? '') == $cat ? 'btn-primary shadow-sm' : 'btn-light text-muted border-0' }}" style="border-radius: 50px;">
                        {{ $cat }}
                      </button>
                    @endforeach
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div id="products-loader" style="display: none;">
          <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
              <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-3 text-muted font-italic">Filtering your inventory...</p>
          </div>
        </div>

        <div id="products-container">
          @include('bar.products._product_list')
        </div>
            @if($canCreate)
              <a href="{{ route('bar.products.create') }}" class="btn-premium mt-3">
                <i class="fa fa-plus-circle"></i> Add Your First Product
              </a>
            @endif
          </div>
      </div>
    </div>
  </div>

@endsection

<!-- Product Details Modal -->
<div class="modal fade" id="productDetailsModal" tabindex="-1" role="dialog" aria-labelledby="productDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="productDetailsModalLabel">Product Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="productDetailsContent">
        <div class="text-center">
          <i class="fa fa-spinner fa-spin fa-2x"></i>
          <p>Loading product details...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script type="text/javascript">
  $(document).ready(function() {
    let searchTimer;
    
    // Real-time Search with Debounce
    $('#searchInput').on('keyup', function() {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function() {
        fetchProducts();
      }, 500); // 500ms delay
    });

    // Real-time Category Filter
    $('#categoryFilter').on('change', function() {
      // Sync Quick Nav badges
      const cat = $(this).val();
      updateQuickNavBadges(cat);
      fetchProducts();
    });

    // Quick Navigation Click
    $('.btn-category').on('click', function() {
      const cat = $(this).data('category');
      
      // Update select dropdown
      $('#categoryFilter').val(cat);
      
      // Update UI active state
      updateQuickNavBadges(cat);
      
      fetchProducts();
    });

    // Reset Filters
    $('#resetFilters').on('click', function() {
      $('#searchInput').val('');
      $('#categoryFilter').val('');
      updateQuickNavBadges('');
      fetchProducts();
    });

    function updateQuickNavBadges(activeCat) {
      $('.btn-category').each(function() {
        if ($(this).data('category') == activeCat) {
          $(this).removeClass('btn-light text-muted border-0').addClass('btn-primary shadow-sm');
        } else {
          $(this).addClass('btn-light text-muted border-0').removeClass('btn-primary shadow-sm');
        }
      });
    }

    function fetchProducts() {
      const search = $('#searchInput').val();
      const category = $('#categoryFilter').val();
      const container = $('#products-container');
      const loader = $('#products-loader');

      loader.show();
      container.css('opacity', '0.5');

      $.ajax({
        url: '{{ route("bar.products.index") }}',
        method: 'GET',
        data: {
          search: search,
          category: category
        },
        success: function(response) {
          loader.hide();
          container.html(response).css('opacity', '1');
          
          // Re-update browser URL without reload (optional but good for UX)
          const newUrl = window.location.pathname + '?search=' + encodeURIComponent(search) + '&category=' + encodeURIComponent(category);
          window.history.pushState({ path: newUrl }, '', newUrl);
        },
        error: function() {
          loader.hide();
          container.css('opacity', '1');
          Swal.fire({
            icon: 'error',
            title: 'Search Failed',
            text: 'There was an error updating the product list.',
            confirmButtonColor: '#940000'
          });
        }
      });
    }

    // Pagination AJAX
    $(document).on('click', '.pagination a', function(e) {
      e.preventDefault();
      const url = $(this).attr('href');
      const container = $('#products-container');
      const loader = $('#products-loader');

      loader.show();
      container.css('opacity', '0.5');

      $.ajax({
        url: url,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        success: function(response) {
          loader.hide();
          container.html(response).css('opacity', '1');
          $('html, body').animate({ scrollTop: $('#filterForm').offset().top - 50 }, 500);
        }
      });
    });

    $(document).on('click', '.view-product', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const productId = $(this).data('product-id');
      const modal = $('#productDetailsModal');
      const content = $('#productDetailsContent');
      
      if (!productId) {
        console.error('Product ID not found');
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Product ID not found.',
          confirmButtonColor: '#940000'
        });
        return;
      }
      
      console.log('Viewing product:', productId);
      
      // Show modal with loading state
      modal.modal('show');
      content.html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading product details...</p></div>');
      
      // Fetch product details
      $.ajax({
        url: '{{ url("/bar/products") }}/' + productId,
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        success: function(response) {
          if (response.product) {
            const product = response.product;
            let html = '<div class="product-modal-details">';
            
            // Header Section with Image and Key Info
            html += '<div class="row mb-4 align-items-center">';
            html += '<div class="col-md-4 mb-3 mb-md-0">';
            if (product.image) {
              html += '<img src="{{ asset("storage") }}/' + product.image + '" class="img-fluid rounded-lg shadow-sm" style="max-height: 200px; width: 100%; object-fit: cover;">';
            } else {
              html += '<div class="bg-light rounded-lg d-flex align-items-center justify-content-center" style="height: 180px;"><i class="fa fa-cube fa-4x text-muted opacity-25"></i></div>';
            }
            html += '</div>';
            
            html += '<div class="col-md-8">';
            html += '<h3 class="font-weight-bold mb-1">' + escapeHtml(product.name) + '</h3>';
            html += '<p class="text-primary font-weight-bold mb-3">' + escapeHtml(product.category || 'General Category') + '</p>';
            
            html += '<div class="row">';
            html += '<div class="col-6 mb-2"><small class="text-muted d-block">Brand</small><span class="font-weight-bold">' + escapeHtml(product.brand || 'N/A') + '</span></div>';
            html += '<div class="col-6 mb-2"><small class="text-muted d-block">Status</small>' + (product.is_active ? '<span class="text-success font-weight-bold">● Active</span>' : '<span class="text-danger font-weight-bold">● Inactive</span>') + '</div>';
            html += '<div class="col-12"><small class="text-muted d-block">Primary Supplier</small><span class="font-weight-bold">' + escapeHtml(product.supplier ? product.supplier.company_name : 'N/A') + '</span></div>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            
            // Description Section
            if (product.description) {
              html += '<div class="mb-4 p-3 bg-light rounded-lg">';
              html += '<h6 class="font-weight-bold text-uppercase small text-muted mb-2">Description</h6>';
              html += '<p class="mb-0">' + escapeHtml(product.description) + '</p>';
              html += '</div>';
            }
            
            // Variants Table Section
            if (product.variants && product.variants.length > 0) {
              html += '<h6 class="font-weight-bold text-uppercase small text-muted mb-3">Product Variants & Pricing</h6>';
              html += '<div class="table-responsive rounded-lg border">';
              html += '<table class="table table-hover mb-0">';
              html += '<thead class="bg-light"><tr>';
              html += '<th class="border-0">Measurement</th>';
              html += '<th class="border-0 text-center">Packaging</th>';
              html += '<th class="border-0 text-right">Shot Price</th>';
              html += '<th class="border-0 text-center">Status</th>';
              html += '</tr></thead>';
              html += '<tbody>';
              
              product.variants.forEach(function(variant) {
                html += '<tr>';
                html += '<td class="align-middle"><strong>' + escapeHtml(variant.measurement) + '</strong></td>';
                html += '<td class="align-middle text-center">' + escapeHtml(variant.packaging) + '</td>';
                html += '<td class="align-middle text-right">';
                if (variant.can_sell_in_tots) {
                  html += '<span class="text-success font-weight-bold">TSh ' + number_format(variant.selling_price_per_tot) + '</span>';
                  html += '<br><small class="text-muted">(' + variant.total_tots + ' shots/bottle)</small>';
                } else {
                  html += '<span class="text-muted">-</span>';
                }
                html += '</td>';
                html += '<td class="align-middle text-center">' + (variant.is_active ? '<span class="badge badge-pill badge-success">Active</span>' : '<span class="badge badge-pill badge-danger">Inactive</span>') + '</td>';
                html += '</tr>';
              });
              
              html += '</tbody></table></div>';
            } else {
              html += '<div class="alert alert-info border-0 shadow-sm"><i class="fa fa-info-circle mr-2"></i> No variants configured for this product.</div>';
            }
            
            html += '</div>';
            content.html(html);
          } else {
            content.html('<div class="alert alert-danger">Failed to load product details.</div>');
          }
        },
        error: function(xhr) {
          console.error('Error loading product:', xhr);
          let errorMsg = 'Failed to load product details.';
          if (xhr.responseJSON && xhr.responseJSON.error) {
            errorMsg = xhr.responseJSON.error;
          } else if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMsg = xhr.responseJSON.message;
          } else if (xhr.status === 403) {
            errorMsg = 'You do not have permission to view this product.';
          } else if (xhr.status === 404) {
            errorMsg = 'Product not found.';
          }
          content.html('<div class="alert alert-danger">' + escapeHtml(errorMsg) + '</div>');
        }
      });
    });

    function number_format(number) {
      return parseFloat(number).toLocaleString('en-US');
    }

    function escapeHtml(text) {
      if (!text) return '';
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Delete product with SweetAlert confirmation (for table view)
    $(document).on('click', '.delete-product-btn', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const form = $(this).closest('form');
      const productName = form.data('product-name') || 'this product';
      const productId = form.attr('action').split('/').pop();
      
      confirmDelete(productName, form);
    });
    
    // Delete product with SweetAlert confirmation (for card view)
    $(document).on('click', '.delete-product-btn-card', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const productId = $(this).data('product-id');
      const productName = $(this).data('product-name') || 'this product';
      
      confirmDeleteCard(productName, productId);
    });
    
    function confirmDelete(productName, form) {
      Swal.fire({
        title: 'Are you sure?',
        html: `You are about to delete <strong>${escapeHtml(productName)}</strong>.<br><br>This action cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading state
          Swal.fire({
            title: 'Deleting...',
            text: 'Please wait while we delete the product.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            willOpen: () => {
              Swal.showLoading();
            }
          });
          
          // Submit the form
          form.submit();
        }
      });
    }
    
    function confirmDeleteCard(productName, productId) {
      Swal.fire({
        title: 'Are you sure?',
        html: `You are about to delete <strong>${escapeHtml(productName)}</strong>.<br><br>This action cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading state
          Swal.fire({
            title: 'Deleting...',
            text: 'Please wait while we delete the product.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            willOpen: () => {
              Swal.showLoading();
            }
          });
          
          // Create and submit form via AJAX
          $.ajax({
            url: '{{ url("/bar/products") }}/' + productId,
            method: 'POST',
            data: {
              _token: '{{ csrf_token() }}',
              _method: 'DELETE'
            },
            success: function(response) {
              Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: 'Product has been deleted successfully.',
                confirmButtonColor: '#940000'
              }).then(() => {
                location.reload();
              });
            },
            error: function(xhr) {
              let errorMsg = 'Failed to delete product.';
              if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
              }
              Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: errorMsg,
                confirmButtonColor: '#940000'
              });
            }
          });
        }
      });
    }
  });
</script>
@endpush
