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
                    <span class="smallest font-weight-bold text-uppercase text-muted d-block border-bottom pb-1 mb-2">Soft Drinks & Water Distributors</span>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="bonite_soda">Bonite (Coca-Cola)</button>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="sbc_soda">SBC (Pepsi)</button>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="azam_soda">Azam Bakhresa</button>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="drinking_water">Drinking Water</button>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="energy_soft">Energizers</button>
                </div>

                <div class="mb-3">
                    <span class="smallest font-weight-bold text-uppercase text-muted d-block border-bottom pb-1 mb-2">Beer Distributors (Brewers)</span>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="tbl_beers">TBL (Kili/Safari/Castle)</button>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="sbl_beers">SBL (Serengeti/Guinness)</button>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="heineken_others">Heineken & Premium</button>
                </div>

                <div class="mb-0">
                    <span class="smallest font-weight-bold text-uppercase text-muted d-block border-bottom pb-1 mb-2">Spirits & Wine Portfolio</span>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="konyagi_spirits">Konyagi / TBL Spirits</button>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="diageo_spirits">Diageo / SBL Portfolio</button>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="premium_whisky">Premium Whiskeys</button>
                    <button type="button" class="btn btn-outline-primary btn-sm m-1 bulk-load-btn" data-brand="wine_portfolio">Wine Collection</button>
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
        "bonite_soda": {
            brand: "Bonite (Coca-Cola)",
            category: "SODA",
            flavors: [
                { name: "Coca-Cola Classic", size: 350, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 24 },
                { name: "Coke Zero", size: 350, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 24 },
                { name: "Sprite", size: 350, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 24 },
                { name: "Fanta Orange", size: 350, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 24 },
                { name: "Fanta Pineapple", size: 350, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 24 },
                { name: "Fanta Blackcurrant", size: 350, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 24 },
                { name: "Stoney Tangawizi", size: 350, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 24 },
                { name: "Novida Pineapple", size: 300, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Krest Tonic Water", size: 350, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 24 }
            ]
        },
        "sbc_soda": {
            brand: "SBC (Pepsi)",
            category: "SODA",
            flavors: [
                { name: "Pepsi Cola", size: 350, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 24 },
                { name: "Mirinda Orange", size: 350, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 24 },
                { name: "Mirinda Fruity", size: 350, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 24 },
                { name: "7Up", size: 350, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 24 },
                { name: "Mountain Dew", size: 350, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 24 }
            ]
        },
        "azam_soda": {
            brand: "Azam Bakhresa",
            category: "SODA",
            flavors: [
                { name: "Azam Cola", size: 350, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 24 },
                { name: "Azam Orange", size: 350, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 24 },
                { name: "Azam Malt", size: 350, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 24 },
                { name: "Azam Embe", size: 350, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 24 }
            ]
        },
        "drinking_water": {
            brand: "Drinking Water",
            category: "WATER",
            flavors: [
                { name: "M/Water Big", size: 1.5, unit: "L", sell: "bottle", pkg: "Carton", pkg_qty: 12 },
                { name: "M/Water Small", size: 500, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Hill Sparkling Water", size: 750, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 12 },
                { name: "Sayona Twist", size: 350, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 24 }
            ]
        },
        "energy_soft": {
            brand: "Energizers",
            category: "ENERGIES",
            flavors: [
                { name: "Red Bull Energy", size: 250, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Mo Energy", size: 250, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Dragon Energy", size: 500, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Monster Energy", size: 500, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 }
            ]
        },
        "tbl_beers": {
            brand: "TBL (Tanzania Breweries)",
            category: "BEER/LAGER",
            flavors: [
                { name: "Kilimanjaro Lager", size: 500, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 25 },
                { name: "Safari Lager", size: 500, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 25 },
                { name: "Castle Lager", size: 500, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 25 },
                { name: "Castle Lite Bottle", size: 500, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 25 },
                { name: "Balimi Extra", size: 500, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 25 },
                { name: "Flying Fish Can", size: 330, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Brutal Fruit Apple", size: 330, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 }
            ]
        },
        "sbl_beers": {
            brand: "SBL (Serengeti Breweries)",
            category: "BEER/LAGER",
            flavors: [
                { name: "Serengeti Lager", size: 500, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 25 },
                { name: "Serengeti Lite", size: 500, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 25 },
                { name: "Guinness Smooth Bottle", size: 500, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 25 },
                { name: "Serengeti Lemon", size: 330, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Pilsner Lager", size: 500, unit: "ml", sell: "bottle", pkg: "Crate", pkg_qty: 25 }
            ]
        },
        "heineken_others": {
            brand: "Premium Imports",
            category: "CAN BEER",
            flavors: [
                { name: "Heineken Can", size: 330, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Desperado Can", size: 330, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 },
                { name: "Windhoek Can", size: 440, unit: "ml", sell: "bottle", pkg: "Carton", pkg_qty: 24 }
            ]
        },
        "konyagi_spirits": {
            brand: "Konyagi / TBL Spirits",
            category: "SPIRITS",
            flavors: [
                { name: "Konyagi Premium Gin", size: 500, unit: "ml", sell: "mixed", tots: 16 },
                { name: "Konyagi Small", size: 250, unit: "ml", sell: "bottle" },
                { name: "Safari Gin", size: 750, unit: "ml", sell: "mixed", tots: 25 },
                { name: "Valeur Brandy", size: 750, unit: "ml", sell: "mixed", tots: 25 },
                { name: "Zanzi Cream", size: 750, unit: "ml", sell: "mixed", tots: 25 }
            ]
        },
        "diageo_spirits": {
            brand: "Diageo / SBL Portfolio",
            category: "BRANDY/WHISKY/RUM/GIN",
            flavors: [
                { name: "Johnnie Walker Black Label", size: 750, unit: "ml", sell: "mixed", tots: 25 },
                { name: "Johnnie Walker Red Label", size: 750, unit: "ml", sell: "mixed", tots: 25 },
                { name: "Smirnoff Vodka", size: 750, unit: "ml", sell: "mixed", tots: 25 },
                { name: "Gilbeys Gin", size: 750, unit: "ml", sell: "mixed", tots: 25 },
                { name: "Chrome Gin", size: 750, unit: "ml", sell: "mixed", tots: 25 }
            ]
        },
        "premium_whisky": {
            brand: "Global Premium Spirits",
            category: "BRANDY/WHISKY/RUM/GIN",
            flavors: [
                { name: "Hennesy V.S.O.P", size: 750, unit: "ml", sell: "mixed", tots: 25 },
                { name: "Jack Daniel No.7", size: 750, unit: "ml", sell: "mixed", tots: 25 },
                { name: "Jaggermaster", size: 700, unit: "ml", sell: "mixed", tots: 23 },
                { name: "Absolute Vodka", size: 750, unit: "ml", sell: "mixed", tots: 25 },
                { name: "Amarula Cream", size: 750, unit: "ml", sell: "mixed", tots: 25 }
            ]
        },
        "wine_portfolio": {
            brand: "Wine Collection",
            category: "WINE BY BOTTLE",
            flavors: [
                { name: "Dodoma Red (Dry)", size: 750, unit: "ml", sell: "mixed", tots: 5 },
                { name: "4th Street Red Sweet", size: 750, unit: "ml", sell: "mixed", tots: 5 },
                { name: "Robertson Sweet Red", size: 750, unit: "ml", sell: "bottle" },
                { name: "Lions Hill Selection", size: 750, unit: "ml", sell: "mixed", tots: 5 },
                { name: "Drostdney Hoff Claret", size: 700, unit: "ml", sell: "mixed", tots: 5 }
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
