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
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    transition: all 0.3s ease;
    box-shadow: var(--card-shadow);
    height: 100%;
    opacity: 1;
  }

  /* Prevent flash */
  #products-container {
    transition: opacity 0.2s ease-in-out;
  }

  .loading-skeleton {
    opacity: 0.5 !important;
    pointer-events: none;
    filter: blur(2px);
  }

  .product-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--card-hover-shadow);
  }

  .product-img-container {
    height: 180px;
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
    padding: 1rem;
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
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255, 255, 255, 0.3);
  }

  .search-input-group:focus-within {
    border-color: #940000 !important;
    box-shadow: 0 0 10px rgba(148, 0, 0, 0.1) !important;
  }

  .btn-category {
    font-size: 0.85rem !important;
    letter-spacing: 0.3px;
    border: 1.5px solid #eee !important;
  }

  .btn-category:hover {
    border-color: #940000 !important;
    color: #940000 !important;
    background: #fff !important;
  }

  .btn-category.active {
    background: var(--primary-gradient) !important;
    color: white !important;
    border-color: transparent !important;
    box-shadow: 0 4px 10px rgba(148, 0, 0, 0.2) !important;
  }

  .btn-category i {
    transition: transform 0.3s ease;
  }

  .btn-category.active i {
    transform: scale(1.1);
  }

  .search-input-group input::placeholder {
    color: #adb5bd;
    font-size: 0.9rem;
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
    <div class="tile shadow-sm border-0 mb-4" style="border-top: 4px solid #940000 !important;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
        <div>
          <h3 class="tile-title mb-1 text-primary"><i class="fa fa-th-list mr-2"></i> Product Inventory</h3>
          <p class="text-muted small mb-0">Total visibility of your bar stock, packaging, and serving metrics.</p>
        </div>
        @if($canCreate)
          <a href="{{ route('bar.products.create') }}" class="btn btn-primary btn-lg px-4 shadow-sm font-weight-bold" style="background-color: #940000; border-color: #940000;">
            <i class="fa fa-plus-circle mr-2"></i> ADD NEW PRODUCT
          </a>
        @endif
      </div>

      <div class="bg-light p-3 rounded-lg mb-4">
        <form id="filterForm" action="{{ route('bar.products.index') }}" method="GET">
          <div class="row align-items-end">
            <div class="col-lg-6 mb-3 mb-lg-0">
              <div class="form-group mb-0">
                <label class="small font-weight-bold text-uppercase text-muted">Search Inventory</label>
                <div class="input-group search-input-group shadow-sm border rounded">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-white border-0"><i class="fa fa-search text-primary"></i></span>
                  </div>
                  <input type="text" id="searchInput" name="search" class="form-control border-0" placeholder="Search product name, brand or variant..." value="{{ $search ?? '' }}">
                </div>
              </div>
            </div>
            <div class="col-lg-3 mb-3 mb-lg-0">
              <div class="form-group mb-0">
                <label class="small font-weight-bold text-uppercase text-muted">Core Category</label>
                <select id="categoryFilter" name="category" class="form-control border shadow-sm">
                  <option value="">All Categories</option>
                  @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ ($category ?? '') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-lg-3 text-right">
                <button type="button" id="resetFilters" class="btn btn-outline-secondary btn-sm"><i class="fa fa-refresh mr-1"></i> Clear Filters</button>
            </div>
          </div>
        </form>
      </div>

      <div class="category-tabs-wrapper mb-4 border-bottom">
        <ul class="nav nav-tabs border-0" id="categoryTabs" role="tablist">
          <li class="nav-item">
            <a class="nav-link {{ !($category ?? '') ? 'active' : '' }} category-tab" href="#" data-category="">
              <i class="fa fa-th-large mr-2"></i> ALL PRODUCTS
            </a>
          </li>
          @foreach($categories as $cat)
            <li class="nav-item">
              <a class="nav-link {{ ($category ?? '') == $cat ? 'active' : '' }} category-tab" href="#" data-category="{{ $cat }}">
                @php
                  $icon = 'fa-cube';
                  if(str_contains(strtolower($cat), 'beer')) $icon = 'fa-beer';
                  if(str_contains(strtolower($cat), 'spirit')) $icon = 'fa-glass';
                  if(str_contains(strtolower($cat), 'wine')) $icon = 'fa-flask';
                  if(str_contains(strtolower($cat), 'soda') || str_contains(strtolower($cat), 'drink')) $icon = 'fa-coffee';
                  if(str_contains(strtolower($cat), 'water')) $icon = 'fa-tint';
                  if(str_contains(strtolower($cat), 'energy')) $icon = 'fa-bolt';
                @endphp
                <i class="fa {{ $icon }} mr-2"></i> {{ strtoupper($cat) }}
              </a>
            </li>
          @endforeach
        </ul>
      </div>

      <div id="products-loader" style="display: none;">
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
          </div>
        </div>
      </div>

      <div id="products-container">
        @include('bar.products._product_list')
      </div>
    </div>
  </div>
</div>

<style>
  .nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    font-weight: 700;
    font-size: 0.85rem;
    padding: 12px 25px;
    letter-spacing: 0.5px;
    transition: all 0.2s;
    border-bottom: 3px solid transparent;
  }
  .nav-tabs .nav-link:hover {
    color: #940000;
    border-bottom: 3px solid #eee;
  }
  .nav-tabs .nav-link.active {
    color: #940000 !important;
    background: transparent !important;
    border-bottom: 3px solid #940000 !important;
  }
  .category-tabs-wrapper {
    overflow-x: auto;
    white-space: nowrap;
    scrollbar-width: none; /* Firefox */
  }
  .category-tabs-wrapper::-webkit-scrollbar { display: none; } /* Safari/Chrome */
</style>

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
      updateTabState('');
      fetchProducts();
    });

    // Category Tab Click
    $(document).on('click', '.category-tab', function(e) {
      e.preventDefault();
      const cat = $(this).data('category');
      $('#categoryFilter').val(cat);
      updateTabState(cat);
      fetchProducts();
    });

    function updateTabState(activeCat) {
      $('.category-tab').removeClass('active');
      $(`.category-tab[data-category="${activeCat}"]`).addClass('active');
    }

    function fetchProducts() {
      const search = $('#searchInput').val();
      const category = $('#categoryFilter').val();
      const container = $('#products-container');
      const loader = $('#products-loader');

      container.addClass('loading-skeleton');
      
      $.ajax({
        url: '{{ route("bar.products.index") }}',
        method: 'GET',
        data: {
          search: search,
          category: category
        },
        success: function(response) {
          container.html(response);
          container.removeClass('loading-skeleton');
          
          const newUrl = window.location.pathname + '?search=' + encodeURIComponent(search) + '&category=' + encodeURIComponent(category);
          window.history.pushState({ path: newUrl }, '', newUrl);
        },
        error: function() {
          container.removeClass('loading-skeleton');
          showToast('error', 'Filtering failed. Please try again.');
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
              html += '<thead class="bg-light text-primary"><tr>';
              html += '<th class="border-0">Variant Name</th>';
              html += '<th class="border-0">Size</th>';
              html += '<th class="border-0 text-center">Packaging</th>';
              html += '<th class="border-0 text-right">Shot Price</th>';
              html += '<th class="border-0 text-center">Status</th>';
              html += '</tr></thead>';
              html += '<tbody>';
              
              product.variants.forEach(function(variant) {
                html += '<tr>';
                html += '<td class="align-middle"><strong>' + escapeHtml(variant.name || 'N/A') + '</strong></td>';
                html += '<td class="align-middle">' + escapeHtml(variant.measurement) + ' ' + (variant.unit || '') + '</td>';
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
