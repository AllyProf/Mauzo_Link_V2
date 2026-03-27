@extends('layouts.dashboard')

@section('title', 'Register Products')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-cube"></i> Smart Product Registration</h1>
    <p>Select from our global library or add custom brands manually</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.products.index') }}">Products</a></li>
    <li class="breadcrumb-item active">Register</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <!-- 1. Selection Mode -->
    <div class="tile shadow-sm border-0 mb-4" style="border-top: 4px solid #940000 !important;">
        <div class="row align-items-center">
            <div class="col-md-7 border-right">
                <h4 class="tile-title text-primary mb-2"><i class="fa fa-magic"></i> Option A: Bulk Library Load</h4>
                <p class="small text-muted mb-3">Load brands by distributor to keep your inventory un-mixed and easy to receive.</p>
                
                <div class="mb-3">
                    <span class="smallest font-weight-bold text-uppercase text-muted d-block border-bottom pb-1 mb-2">Bulk Load Quick Categories</span>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="soft_drinks">Soft Drinks</button>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="energy_drinks">Energy Drinks</button>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="beer">Beer</button>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="whiskey">Whiskey</button>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="gin">Gin</button>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="spirits">Spirits</button>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="wine">Wine</button>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="miscellaneous">Miscellaneous</button>
                </div>
            </div>
            <div class="col-md-5">
                <h4 class="tile-title text-dark mb-2"><i class="fa fa-pencil"></i> Option B: Custom Brand</h4>
                <p class="small text-muted">Enter a custom brand name and add rows manually.</p>
                <div class="input-group">
                    <input type="text" id="customBrandInput" class="form-control" placeholder="Type Brand Name...">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-dark" id="startCustomBtn">Start Manual</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('bar.products.store') }}" id="productForm" enctype="multipart/form-data">
      @csrf
      
      <!-- Brand & Category (Hidden until selected) -->
      <div id="mainFormContainer" class="d-none">
          <div class="tile shadow-sm border-0 mb-4 bg-light">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold text-uppercase text-muted">Active Brand Group</label>
                        <input type="text" name="brand" id="activeBrandName" class="form-control form-control-lg border-primary font-weight-bold" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold text-uppercase text-muted">Core Category</label>
                        <select class="form-control form-control-lg border-primary" name="category" id="activeCategory" required>
                          <option value="Beers">Beers</option>
                          <option value="Spirits">Spirits</option>
                          <option value="Wines">Wines</option>
                          <option value="Soft Drinks">Soft Drinks</option>
                          <option value="Water">Water</option>
                          <option value="Energies">Energies</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <button type="button" class="btn btn-sm btn-link text-danger" onclick="location.reload()"><i class="fa fa-refresh"></i> Reset Selection</button>
                </div>
            </div>
          </div>

          <!-- Variants Table -->
          <div class="tile shadow-sm border-0">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h3 class="tile-title mb-0 text-primary"><i class="fa fa-list mr-2"></i> Registration List</h3>
              <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" id="addVariantRow">
                <i class="fa fa-plus"></i> Add Row
              </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="variantsTable">
                  <thead class="bg-dark text-white text-center">
                    <tr>
                      <th width="50px">PHOTO</th>
                      <th width="25%">VARIANT / FLAVOR NAME *</th>
                      <th width="12%">SIZE/UNIT</th>
                      <th width="18%">PACKAGING</th>
                      <th width="18%">SELL TYPE</th>
                      <th width="14%">CONVERSION</th>
                      <th width="40px"></th>
                    </tr>
                  </thead>
                  <tbody id="variantsBody">
                  </tbody>
                </table>
            </div>

            <div class="tile-footer text-right border-top pt-3 mt-4">
              <a class="btn btn-light btn-lg px-4 mr-2" href="{{ route('bar.products.index') }}">Cancel</a>
              <button class="btn btn-success btn-lg px-5 shadow-sm font-weight-bold" type="submit" style="background-color: #28a745; border-color: #28a745;">
                <i class="fa fa-check-circle mr-2"></i> COMPLETE REGISTRATION
              </button>
            </div>
          </div>
      </div>
    </form>
  </div>
</div>

<style>
  .bulk-load-btn { transition: all 0.2s; border-radius: 20px; font-weight: 600; padding: 6px 15px; }
  .bulk-load-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
  .variant-row:hover { background-color: #f8f9fa; }
  .form-control:focus { box-shadow: none !important; border-color: #940000 !important; }
  .table-sm td { padding: 0.1rem !important; vertical-align: middle; }
  .tile-title { font-size: 1.1rem; font-weight: 700; letter-spacing: 0.5px; }
  .text-primary { color: #940000 !important; }
  .img-preview-container:hover { border-color: #940000 !important; background: #fdf2f2; }
  .smallest { font-size: 0.65rem; }
  .img-preview-container { transition: all 0.2s; }
</style>
@endsection

@section('scripts')
<script>
    const productLibrary = {
        "soft_drinks": {
            brand: "Soft Drinks",
            category: "Soft Drinks",
            flavors: [
                { name: "Serengeti Apple", size: 350, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Campai n", size: 350, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Konyagi Beer", size: 350, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Redbull", size: 250, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Tropical Juice", size: 350, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 12 },
                { name: "Maji Kati", size: 500, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Viceroy 200ml", size: 200, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Soda Kopo (Takeaway)", size: 330, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Dr Frost Ndogo", size: 300, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Heineken (soft drink version)", size: 330, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 }
            ]
        },
        "energy_drinks": {
            brand: "Energy Drinks",
            category: "Energies",
            flavors: [
                { name: "Redbull", size: 250, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Energy Kopo", size: 250, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 }
            ]
        },
        "beer": {
            brand: "Beer",
            category: "Beers",
            flavors: [
                { name: "Serengeti Apple", size: 500, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 25 },
                { name: "Campai n", size: 500, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 25 },
                { name: "Konyagi Beer", size: 500, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 25 },
                { name: "Redd Cane", size: 500, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 25 },
                { name: "Gilbey's", size: 500, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 25 },
                { name: "Gilbey's Ndogo", size: 330, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Bavaria", size: 500, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 25 },
                { name: "Baltika", size: 500, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 25 },
                { name: "Famous Crouse", size: 500, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 25 },
                { name: "Zanzi", size: 500, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 25 }
            ]
        },
        "whiskey": {
            brand: "Whiskey",
            category: "Spirits",
            flavors: [
                { name: "Jack Daniel", size: 750, unit: "ml", sell: "mixed", tots: 25, pkg: "Piece" },
                { name: "Jack Daniel Kubwa", size: 1, unit: "L", sell: "mixed", tots: 33, pkg: "Piece" },
                { name: "Black Label", size: 750, unit: "ml", sell: "mixed", tots: 25, pkg: "Piece" },
                { name: "Red Label", size: 750, unit: "ml", sell: "mixed", tots: 25, pkg: "Piece" },
                { name: "J.B Ndogo", size: 350, unit: "ml", sell: "bottle", pkg: "Piece" },
                { name: "Grants Kubwa", size: 750, unit: "ml", sell: "mixed", tots: 25, pkg: "Piece" },
                { name: "Grants Ndogo", size: 350, unit: "ml", sell: "bottle", pkg: "Piece" },
                { name: "Jagermeister Kubwa", size: 700, unit: "ml", sell: "mixed", tots: 23, pkg: "Piece" },
                { name: "Jagermeister Ndogo", size: 350, unit: "ml", sell: "bottle", pkg: "Piece" },
                { name: "Gordon’s", size: 750, unit: "ml", sell: "mixed", tots: 25, pkg: "Piece" },
                { name: "Gordon’s Ndogo", size: 350, unit: "ml", sell: "bottle", pkg: "Piece" },
                { name: "Gilbey’s", size: 750, unit: "ml", sell: "mixed", tots: 25, pkg: "Piece" },
                { name: "Amrula", size: 750, unit: "ml", sell: "mixed", tots: 25, pkg: "Piece" }
            ]
        },
        "gin": {
            brand: "Gin",
            category: "Spirits",
            flavors: [
                { name: "Gordon's cc", size: 750, unit: "ml", sell: "mixed", tots: 25, pkg: "Piece" },
                { name: "Tanqueray", size: 750, unit: "ml", sell: "mixed", tots: 25, pkg: "Piece" },
                { name: "Campari", size: 750, unit: "ml", sell: "mixed", tots: 25, pkg: "Piece" },
                { name: "Gilbey's", size: 750, unit: "ml", sell: "mixed", tots: 25, pkg: "Piece" }
            ]
        },
        "spirits": {
            brand: "Spirits",
            category: "Spirits",
            flavors: [
                { name: "Jack Daniel", size: 750, unit: "ml", sell: "mixed", tots: 25, pkg: "Piece" },
                { name: "J.B Ndogo", size: 350, unit: "ml", sell: "bottle", pkg: "Piece" },
                { name: "Jagermeister", size: 700, unit: "ml", sell: "mixed", tots: 23, pkg: "Piece" },
                { name: "Famous Crouse", size: 750, unit: "ml", sell: "mixed", tots: 25, pkg: "Piece" },
                { name: "Zanzi", size: 750, unit: "ml", sell: "mixed", tots: 25, pkg: "Piece" },
                { name: "Absolut", size: 750, unit: "ml", sell: "mixed", tots: 25, pkg: "Piece" }
            ]
        },
        "wine": {
            brand: "Wine",
            category: "Wines",
            flavors: [
                { name: "Serengeti Wine", size: 750, unit: "ml", sell: "mixed", tots: 5, pkg: "Piece" }
            ]
        },
        "miscellaneous": {
            brand: "Miscellaneous",
            category: "Soft Drinks",
            flavors: [
                { name: "Viceroy 200ml", size: 200, unit: "ml", sell: "bottle", pkg: "Piece" },
                { name: "Maji Kati", size: 500, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Valuer Ndogo", size: 250, unit: "ml", sell: "bottle", pkg: "Piece" },
                { name: "Maji", size: 1, unit: "L", sell: "bottle", pkg: "Carton", pkg_qty: 12 },
                { name: "Tzee Kb", size: 750, unit: "ml", sell: "bottle", pkg: "Piece" },
                { name: "Tzee n", size: 350, unit: "ml", sell: "bottle", pkg: "Piece" },
                { name: "Jameson", size: 750, unit: "ml", sell: "mixed", tots: 25, pkg: "Piece" },
                { name: "Caminos", size: 750, unit: "ml", sell: "mixed", tots: 25, pkg: "Piece" }
            ]
        },
    };

    let rowCount = 0;

    function addRow(prefilled = null) {
        const body = document.getElementById('variantsBody');
        const index = rowCount;
        
        const tr = document.createElement('tr');
        tr.className = 'variant-row';
        tr.innerHTML = `
            <td class="p-1 text-center align-middle">
                <div class="img-preview-container" style="width: 40px; height: 40px; border: 1px dashed #ccc; border-radius: 4px; position: relative;">
                    <input type="file" name="variants[${index}][image]" class="variant-img-input" style="opacity: 0; position: absolute; width: 100%; height: 100%; cursor: pointer;" accept="image/*">
                    <i class="fa fa-camera text-muted" style="margin-top: 12px;"></i>
                    <img src="" class="preview-img d-none" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px; position: absolute; left: 0; top: 0;">
                </div>
            </td>
            <td class="p-1">
                <input type="text" class="form-control border-0 font-weight-bold variant-name-input" name="variants[${index}][name]" 
                       value="${prefilled ? prefilled.name : ''}" placeholder="e.g. 750ml Premium" required style="border-radius:0;">
            </td>
            <td class="p-1">
                <div class="input-group input-group-sm">
                    <input type="number" class="form-control border-0 text-center px-1" name="variants[${index}][measurement]" value="${prefilled ? prefilled.size : ''}" placeholder="500" required>
                    <select class="form-control border-0 px-0" name="variants[${index}][unit]" required style="background: #f8f9fa;">
                        <option value="ml" ${prefilled && prefilled.unit === 'ml' ? 'selected' : ''}>ml</option>
                        <option value="L" ${prefilled && prefilled.unit === 'L' ? 'selected' : ''}>L</option>
                        <option value="PCS" ${prefilled && prefilled.unit === 'PCS' ? 'selected' : ''}>PCS</option>
                    </select>
                </div>
            </td>
            <td class="p-1">
                <div class="row no-gutters align-items-center">
                    <div class="col-8">
                        <select class="form-control border-0 packaging-select" name="variants[${index}][packaging]" onchange="window.togglePackageUnits(this)" required>
                          <option value="Piece" ${prefilled && prefilled.pkg === 'Piece' ? 'selected' : ''}>Pc/Bottle</option>
                          <option value="Carton" ${prefilled && prefilled.pkg === 'Carton' ? 'selected' : ''}>Carton</option>
                          <option value="Crate" ${prefilled && prefilled.pkg === 'Crate' ? 'selected' : ''}>Crate</option>
                        </select>
                    </div>
                    <div class="col-4 pkg-units-container ${prefilled && prefilled.pkg_qty > 1 ? '' : 'd-none'}">
                        <input type="number" class="form-control border-0 bg-light text-center p-0 font-weight-bold" name="variants[${index}][items_per_package]" value="${prefilled ? prefilled.pkg_qty : 1}">
                    </div>
                </div>
            </td>
            <td class="p-1">
                <select class="form-control border-0 selling-type-select" name="variants[${index}][selling_type]" onchange="window.toggleTots(this)" required>
                    <option value="bottle" ${prefilled && prefilled.sell === 'bottle' ? 'selected' : ''}>Bottle Only</option>
                    <option value="glass" ${prefilled && prefilled.sell === 'glass' ? 'selected' : ''}>Glass/Tots</option>
                    <option value="mixed" ${prefilled && prefilled.sell === 'mixed' ? 'selected' : ''}>Mixed (Both)</option>
                </select>
            </td>
            <td class="p-1 bg-light text-center align-middle">
                <div class="input-group input-group-sm ${prefilled && prefilled.tots > 0 ? '' : 'd-none'} tots-container">
                    <input type="number" class="form-control border-0 text-center font-weight-bold" name="variants[${index}][total_tots]" value="${prefilled ? prefilled.tots : ''}" placeholder="Qty">
                    <div class="input-group-append"><span class="input-group-text border-0 bg-transparent smallest pr-1">tots</span></div>
                </div>
            </td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-outline-danger btn-sm border-0 remove-row" onclick="this.closest('tr').remove()"><i class="fa fa-trash fa-lg"></i></button>
            </td>
        `;
        body.appendChild(tr);
        rowCount++;
    }

    window.togglePackageUnits = function(select) {
        const row = select.closest('tr');
        const container = row.querySelector('.pkg-units-container');
        if (select.value === 'Carton' || select.value === 'Crate') {
            container.classList.remove('d-none');
        } else {
            container.classList.add('d-none');
        }
    }

    window.toggleTots = function(select) {
        const row = select.closest('tr');
        const container = row.querySelector('.tots-container');
        if (select.value === 'glass' || select.value === 'mixed') {
            container.classList.remove('d-none');
        } else {
            container.classList.add('d-none');
        }
    }

    // mode selection
    document.querySelectorAll('.bulk-load-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = productLibrary[this.dataset.brand];
            document.getElementById('mainFormContainer').classList.remove('d-none');
            document.getElementById('activeBrandName').value = data.brand;
            document.getElementById('activeCategory').value = data.category;
            
            document.getElementById('variantsBody').innerHTML = '';
            rowCount = 0;
            data.flavors.forEach(f => addRow(f));
            showToast('success', `Loaded ${data.flavors.length} items from ${data.brand} group.`);
        });
    });

    document.getElementById('startCustomBtn').addEventListener('click', function() {
        const customName = document.getElementById('customBrandInput').value;
        if (!customName) {
            showToast('warning', 'Please enter a brand name first.');
            return;
        }
        document.getElementById('mainFormContainer').classList.remove('d-none');
        document.getElementById('activeBrandName').value = customName;
        document.getElementById('variantsBody').innerHTML = '';
        rowCount = 0;
        addRow();
    });

    document.getElementById('addVariantRow').addEventListener('click', () => addRow());

    // Image Preview Logic
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('variant-img-input')) {
            const file = e.target.files[0];
            const container = e.target.closest('.img-preview-container');
            const preview = container.querySelector('.preview-img');
            const icon = container.querySelector('.fa-camera');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(re) {
                    preview.src = re.target.result;
                    preview.classList.remove('d-none');
                    icon.classList.add('d-none');
                }
                reader.readAsDataURL(file);
            }
        }
    });
</script>
@endsection
