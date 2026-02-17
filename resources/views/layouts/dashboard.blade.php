<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="description" content="MauzoLink - Point of Sale System for Business">
    <title>@yield('title', 'Dashboard') - MauzoLink</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Main CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/admin.css') }}">
    <!-- Font-icon css-->
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
      /* Prevent Flash of Unstyled Content (FOUC) */
      body {
        visibility: hidden;
        opacity: 0;
      }
      body.loaded {
        visibility: visible;
        opacity: 1;
        transition: opacity 0.3s ease-in-out;
      }
      :root {
        --primary: #940000;
        --secondary: #000000;
        --font-family: "Century Gothic", sans-serif;
      }
      body {
        font-family: var(--font-family);
      }
      .menu-separator {
        padding: 8px 20px;
        margin-top: 8px;
        margin-bottom: 4px;
        border-top: 1px solid #e0e0e0;
        list-style: none;
        pointer-events: none;
        cursor: default;
      }
      .menu-separator a {
        pointer-events: none;
        cursor: default;
      }
      .menu-separator-content {
        display: flex;
        align-items: center;
        color: #666;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.7;
        pointer-events: none;
        cursor: default;
      }
      .menu-separator-content i {
        margin-right: 8px;
        font-size: 11px;
        color: #666;
      }
      .menu-separator-label {
        color: #666;
      }
      /* Submenu spacing and indentation */
      .treeview-menu {
        padding-left: 0;
      }
      .treeview-menu > li {
        margin-bottom: 4px;
        padding-left: 20px;
      }
      .treeview-menu > li:last-child {
        margin-bottom: 0;
      }
      .treeview-item {
        padding-left: 8px;
        display: flex;
        align-items: center;
      }
      .treeview-item .icon {
        margin-right: 8px;
        width: 16px;
        text-align: center;
      }
      .btn-primary, .widget-small.primary.coloured-icon {
        background-color: #940000;
        border-color: #940000;
      }
      .btn-primary:hover {
        background-color: #7a0000;
        border-color: #7a0000;
      }
      .app-header__logo {
        color: #fff;
      }
      .app-sidebar__user {
        background: linear-gradient(135deg, #940000 0%, #7a0000 100%);
      }
    </style>
  </head>
  <body class="app sidebar-mini">
    @php
      // Get dashboard URL based on user type
      $dashboardUrl = route('dashboard');
      if (session('is_staff') && session('staff_role_id')) {
        $staffRole = \App\Models\Role::find(session('staff_role_id'));
        if ($staffRole) {
          $roleSlug = \Illuminate\Support\Str::slug($staffRole->name);
          $dashboardUrl = route('dashboard.role', ['role' => $roleSlug]);
        }
      }
    @endphp
    <!-- Navbar-->
    <header class="app-header">
      <a class="app-header__logo" href="{{ $dashboardUrl }}">MauzoLink</a>
      <!-- Sidebar toggle button-->
      <a class="app-sidebar__toggle" href="#" data-toggle="sidebar" aria-label="Hide Sidebar"></a>
      <!-- Navbar Right Menu-->
      <ul class="app-nav">
        <li class="app-search">
          <input class="app-search__input" type="search" placeholder="Search">
          <button class="app-search__button"><i class="fa fa-search"></i></button>
        </li>
        <!--Notification Menu-->
        <li class="dropdown">
          <a class="app-nav__item" href="#" data-toggle="dropdown" aria-label="Show notifications">
            <i class="fa fa-bell-o fa-lg"></i>
          </a>
          <ul class="app-notification dropdown-menu dropdown-menu-right">
            <li class="app-notification__title">You have 4 new notifications.</li>
            <div class="app-notification__content">
              <li>
                <a class="app-notification__item" href="javascript:;">
                  <span class="app-notification__icon">
                    <span class="fa-stack fa-lg">
                      <i class="fa fa-circle fa-stack-2x text-primary"></i>
                      <i class="fa fa-envelope fa-stack-1x fa-inverse"></i>
                    </span>
                  </span>
                  <div>
                    <p class="app-notification__message">New order received</p>
                    <p class="app-notification__meta">2 min ago</p>
                  </div>
                </a>
              </li>
              <li>
                <a class="app-notification__item" href="javascript:;">
                  <span class="app-notification__icon">
                    <span class="fa-stack fa-lg">
                      <i class="fa fa-circle fa-stack-2x text-danger"></i>
                      <i class="fa fa-hdd-o fa-stack-1x fa-inverse"></i>
                    </span>
                  </span>
                  <div>
                    <p class="app-notification__message">Low stock alert</p>
                    <p class="app-notification__meta">5 min ago</p>
                  </div>
                </a>
              </li>
              <li>
                <a class="app-notification__item" href="javascript:;">
                  <span class="app-notification__icon">
                    <span class="fa-stack fa-lg">
                      <i class="fa fa-circle fa-stack-2x text-success"></i>
                      <i class="fa fa-money fa-stack-1x fa-inverse"></i>
                    </span>
                  </span>
                  <div>
                    <p class="app-notification__message">Transaction complete</p>
                    <p class="app-notification__meta">2 days ago</p>
                  </div>
                </a>
              </li>
            </div>
            <li class="app-notification__footer"><a href="#">See all notifications.</a></li>
          </ul>
        </li>
        <!-- User Menu-->
        <li class="dropdown">
          <a class="app-nav__item" href="#" data-toggle="dropdown" aria-label="Open Profile Menu">
            <i class="fa fa-user fa-lg"></i>
          </a>
          <ul class="dropdown-menu settings-menu dropdown-menu-right">
            <li><a class="dropdown-item" href="{{ route('settings.index') }}"><i class="fa fa-cog fa-lg"></i> Settings</a></li>
            <li><a class="dropdown-item" href="#"><i class="fa fa-user fa-lg"></i> Profile</a></li>
            <li>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item" style="border: none; background: none; width: 100%; text-align: left; padding: 0.5rem 1.5rem;">
                  <i class="fa fa-sign-out fa-lg"></i> Logout
                </button>
              </form>
            </li>
          </ul>
        </li>
      </ul>
    </header>
    <!-- Sidebar menu-->
    <div class="app-sidebar__overlay" data-toggle="sidebar"></div>
    <aside class="app-sidebar">
      <div class="app-sidebar__user">
        @if(session('is_staff'))
          <img class="app-sidebar__user-avatar" src="https://ui-avatars.com/api/?name={{ urlencode(session('staff_name')) }}&background=940000&color=fff" alt="Staff Image">
          <div>
            <p class="app-sidebar__user-name">{{ session('staff_name') }}</p>
            <p class="app-sidebar__user-designation">
              @php
                $staffRole = \App\Models\Role::find(session('staff_role_id'));
              @endphp
              {{ $staffRole ? $staffRole->name : 'Staff' }}
            </p>
          </div>
        @elseif(Auth::check())
          <img class="app-sidebar__user-avatar" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=940000&color=fff" alt="User Image">
          <div>
            <p class="app-sidebar__user-name">{{ Auth::user()->name }}</p>
            <p class="app-sidebar__user-designation">
              @php
                $userRoles = Auth::user()->userRoles()->get();
              @endphp
              @if($userRoles->count() > 0)
                {{ $userRoles->first()->name }}
              @else
                {{ Auth::user()->email }}
              @endif
            </p>
          </div>
        @endif
      </div>
      <ul class="app-menu">
        @if(session('is_staff'))
          {{-- Staff Menu - Show only what their role allows --}}
          @php
            $staffRole = \App\Models\Role::with('permissions')->find(session('staff_role_id'));
            $owner = \App\Models\User::find(session('staff_user_id'));
            $menuService = new \App\Services\MenuService();
            $staffMenus = $menuService->getStaffMenus($staffRole, $owner);
            
            // Calculate pending stock transfers for badge notification
            $pendingTransfersCount = 0;
            if ($owner && (strtolower($staffRole->name ?? '') === 'stock keeper' || $staffRole->hasPermission('inventory', 'view'))) {
              $pendingTransfersCount = \App\Models\StockTransfer::where('user_id', $owner->id)
                ->where('status', 'pending')
                ->count();
            }
          @endphp
          @if($staffMenus && $staffMenus->count() > 0)
            @php
              $commonMenuSlugs = ['dashboard', 'sales', 'products', 'customers', 'staff', 'reports', 'settings'];
              $currentBusinessType = null;
              $hasShownCommonMenus = false;
              $hasShownGeneralHeader = false;
              $processedMenuIds = [];
            @endphp
            @foreach($staffMenus as $menu)
              @php
                // Skip if already processed
                if (in_array($menu->id, $processedMenuIds)) {
                  continue;
                }
                $processedMenuIds[] = $menu->id;
                
                // Format icon
                $menuIcon = $menu->icon ?? 'fa-circle';
                if (strpos($menuIcon, 'fa ') === false) {
                  $menuIcon = 'fa ' . (strpos($menuIcon, 'fa-') === 0 ? $menuIcon : 'fa-' . $menuIcon);
                }
                
                // Generate full URL
                $menu->full_url = $menu->route ? route($menu->route) : '#';
                
                // Check if this is a common menu or business-specific menu
                $isCommonMenu = in_array($menu->slug, $commonMenuSlugs);
                $isBusinessSpecific = isset($menu->business_type_id) && !$isCommonMenu;
                $isPlaceholder = isset($menu->is_placeholder) && $menu->is_placeholder;

                // Show General Header at the very beginning
                $showGeneralHeader = false;
                if (!$hasShownGeneralHeader && $isCommonMenu) {
                  $showGeneralHeader = true;
                  $hasShownGeneralHeader = true;
                }
                
                // Track if we've shown common menus
                if ($isCommonMenu) {
                  $hasShownCommonMenus = true;
                }
                
                // Show business type separator if this is a new business type
                $showSeparator = false;
                
                if ($isBusinessSpecific && isset($menu->business_type_id)) {
                  if ($currentBusinessType !== $menu->business_type_id) {
                    $currentBusinessType = $menu->business_type_id;
                    $showSeparator = true;
                  }
                }
              @endphp

              {{-- Main Navigation Header --}}
              @if($showGeneralHeader)
                <li class="menu-separator">
                  <div class="menu-separator-content">
                    <i class="fa fa-navicon"></i>
                    <span class="menu-separator-label">Main Navigation</span>
                  </div>
                </li>
              @endif
              
              {{-- Business Modules Header (if switching from common to business-specific for the first time) --}}
              @if($showSeparator && $hasShownCommonMenus && $currentBusinessType !== null && !isset($hasShownBusinessHeader))
                @php $hasShownBusinessHeader = true; @endphp
                <li class="menu-separator" style="border-top: 2px solid #666; margin-top: 15px;">
                  <div class="menu-separator-content">
                    <i class="fa fa-briefcase"></i>
                    <span class="menu-separator-label" style="color: #666; font-weight: 800;">Business Modules</span>
                  </div>
                </li>
              @endif

              
              {{-- Business Type Separator (show before first business-specific menu or placeholder) --}}
              @if($showSeparator && $hasShownCommonMenus && ($isBusinessSpecific || $isPlaceholder))
                <li class="menu-separator">
                  <div class="menu-separator-content">
                    <i class="fa {{ $menu->business_type_icon ?? 'fa-building' }}"></i>
                    <span class="menu-separator-label">{{ $menu->business_type_name }}</span>
                  </div>
                </li>
              @endif
              
              @if($menu->children && $menu->children->count() > 0)
                <li class="treeview {{ request()->routeIs($menu->route ?? '') || ($menu->children && $menu->children->contains(function($child) { return request()->routeIs($child->route ?? ''); })) ? 'is-expanded' : '' }}">
                  <a class="app-menu__item" href="javascript:void(0);" data-toggle="treeview">
                    <i class="app-menu__icon {{ $menuIcon }}"></i>
                    <span class="app-menu__label">{{ $menu->name }}</span>
                    @if(strtolower($menu->name ?? '') === 'stock transfers' && $pendingTransfersCount > 0)
                      <span class="badge badge-danger" style="margin-left: 8px;">{{ $pendingTransfersCount }}</span>
                    @endif
                    <i class="treeview-indicator fa fa-angle-right"></i>
                  </a>
                  <ul class="treeview-menu">
                    @foreach($menu->children as $child)
                      @php
                        $childIcon = $child->icon ?? 'fa-circle-o';
                        if (strpos($childIcon, 'fa ') === false) {
                          $childIcon = 'fa ' . (strpos($childIcon, 'fa-') === 0 ? $childIcon : 'fa-' . $childIcon);
                        }
                        $child->full_url = $child->route ? route($child->route) : '#';
                      @endphp
                      <li>
                        <a class="treeview-item {{ request()->routeIs($child->route ?? '') ? 'active' : '' }}" href="{{ $child->full_url }}">
                          <i class="icon {{ $childIcon }}"></i> {{ $child->name }}
                          @if(strtolower($child->name ?? '') === 'all transfers' && $pendingTransfersCount > 0)
                            <span class="badge badge-danger" style="margin-left: 8px;">{{ $pendingTransfersCount }}</span>
                          @endif
                        </a>
                      </li>
                    @endforeach
                  </ul>
                </li>
              @else
                <li>
                  <a class="app-menu__item {{ request()->routeIs($menu->route ?? '') ? 'active' : '' }}" href="{{ $menu->full_url }}">
                    <i class="app-menu__icon {{ $menuIcon }}"></i>
                    <span class="app-menu__label">{{ $menu->name }}</span>
                  </a>
                </li>
              @endif
            @endforeach
          @else
            {{-- Fallback: Show only Dashboard if no menus available --}}
            <li>
              <a class="app-menu__item {{ request()->routeIs('dashboard*') ? 'active' : '' }}" href="{{ $dashboardUrl }}">
                <i class="app-menu__icon fa fa-dashboard"></i>
                <span class="app-menu__label">Dashboard</span>
              </a>
            </li>
          @endif
        @elseif(Auth::check() && Auth::user()->isAdmin())
        <li>
          <a class="app-menu__item {{ request()->routeIs('admin.dashboard.*') ? 'active' : '' }}" href="{{ route('admin.dashboard.index') }}">
            <i class="app-menu__icon fa fa-dashboard"></i>
            <span class="app-menu__label">Admin Dashboard</span>
          </a>
        </li>
        @elseif(Auth::check())
        {{-- Show only Dashboard menu during configuration --}}
        @if(request()->routeIs('business-configuration.*'))
          <li>
            <a class="app-menu__item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
              <i class="app-menu__icon fa fa-dashboard"></i>
              <span class="app-menu__label">Dashboard</span>
            </a>
          </li>
        @else
          {{-- Show full dynamic menu after configuration --}}
          @php
            $menuService = new \App\Services\MenuService();
            $menus = $menuService->getUserMenus(Auth::user());
          @endphp
          
          @if($menus && $menus->count() > 0)
            @php
              $commonMenuSlugs = ['dashboard', 'sales', 'products', 'customers', 'staff', 'reports', 'settings'];
              $currentBusinessType = null;
              $hasShownCommonMenus = false;
              $hasShownGeneralHeader = false;
              $businessTypeNames = ['Bar', 'Restaurant', 'Pharmacy', 'Retail Store', 'Supermarket', 'Hotel', 'Cafe', 'Bakery', 'Clothing Store', 'Electronics Store', 'General Store'];
              $businessTypeSlugs = ['bar', 'restaurant', 'pharmacy', 'retail-store', 'supermarket', 'hotel', 'cafe', 'bakery', 'clothing-store', 'electronics-store', 'general-store'];
              $processedMenuIds = [];
            @endphp
            @foreach($menus as $menu)
              @php
                // Skip duplicates by ID
                if (in_array($menu->id, $processedMenuIds)) {
                  continue;
                }
                $processedMenuIds[] = $menu->id;
                
                // Skip menu items that are just business type names or slugs
                if (in_array($menu->name, $businessTypeNames) || in_array($menu->slug ?? '', $businessTypeSlugs)) {
                  continue;
                }
                
                $children = isset($menu->children) ? $menu->children : collect();
                $hasChildren = $children && $children->count() > 0;
                $isCommonMenu = in_array($menu->slug, $commonMenuSlugs);
                $isBusinessSpecific = isset($menu->business_type_id) && !$isCommonMenu;
                $isPlaceholder = isset($menu->is_placeholder) && $menu->is_placeholder;
                
                // Show General Header
                $showGeneralHeader = false;
                if (!$hasShownGeneralHeader && $isCommonMenu) {
                  $showGeneralHeader = true;
                  $hasShownGeneralHeader = true;
                }

                // Track if we've shown common menus
                if ($isCommonMenu) {
                  $hasShownCommonMenus = true;
                }
                
                // Show business type separator if this is a new business type
                $showSeparator = false;
                if (($isBusinessSpecific || $isPlaceholder) && isset($menu->business_type_id)) {
                  if ($currentBusinessType !== $menu->business_type_id) {
                    $currentBusinessType = $menu->business_type_id;
                    $showSeparator = true;
                  }
                }
                
                // Format icon - add 'fa' prefix if not present
                $menuIcon = $menu->icon ?? 'fa-circle';
                if (strpos($menuIcon, 'fa ') === false) {
                  $menuIcon = 'fa ' . (strpos($menuIcon, 'fa-') === 0 ? $menuIcon : 'fa-' . $menuIcon);
                }
              @endphp
              
              {{-- Main Navigation Header --}}
              @if($showGeneralHeader)
                <li class="menu-separator">
                  <div class="menu-separator-content">
                    <i class="fa fa-navicon"></i>
                    <span class="menu-separator-label">Main Navigation</span>
                  </div>
                </li>
              @endif

              {{-- Business Modules Header --}}
              @if($showSeparator && $hasShownCommonMenus && $currentBusinessType !== null && !isset($hasShownBusinessHeaderUser))
                @php $hasShownBusinessHeaderUser = true; @endphp
                <li class="menu-separator" style="border-top: 2px solid #666; margin-top: 15px;">
                  <div class="menu-separator-content">
                    <i class="fa fa-briefcase"></i>
                    <span class="menu-separator-label" style="color: #666; font-weight: 800;">Business Modules</span>
                  </div>
                </li>
              @endif

              
              {{-- Business Type Separator (show before first business-specific menu or placeholder) --}}
              @if($showSeparator && $hasShownCommonMenus && ($isBusinessSpecific || $isPlaceholder))
                <li class="menu-separator">
                  <div class="menu-separator-content">
                    <i class="fa {{ $menu->business_type_icon ?? 'fa-building' }}"></i>
                    <span class="menu-separator-label">{{ $menu->business_type_name }}</span>
                  </div>
                </li>
              @endif
              
              {{-- Skip placeholder menus (they're just for separators) --}}
              @if($isPlaceholder)
                {{-- Placeholder menu - separator already shown above --}}
              @elseif($hasChildren)
                <li class="treeview {{ request()->routeIs($menu->route ?? '') || ($menu->children && $menu->children->contains(function($child) { return request()->routeIs($child->route ?? ''); })) ? 'is-expanded' : '' }}">
                  <a class="app-menu__item" href="javascript:void(0);" data-toggle="treeview">
                    <i class="app-menu__icon {{ $menuIcon }}"></i>
                    <span class="app-menu__label">{{ $menu->name }}</span>
                    <i class="treeview-indicator fa fa-angle-right"></i>
                  </a>
                  <ul class="treeview-menu">
                    @foreach($children as $child)
                      @php
                        $childIcon = $child->icon ?? 'fa-circle-o';
                        if (strpos($childIcon, 'fa ') === false) {
                          $childIcon = 'fa ' . (strpos($childIcon, 'fa-') === 0 ? $childIcon : 'fa-' . $childIcon);
                        }
                        // Generate full URL for child menu
                        $childFullUrl = $child->route ? route($child->route) : '#';
                        // Check if child has its own children (nested menus)
                        $childHasChildren = isset($child->children) && $child->children && $child->children->count() > 0;
                      @endphp
                      @if($childHasChildren)
                        <li class="treeview {{ request()->routeIs($child->route ?? '') || ($child->children && $child->children->contains(function($grandchild) { return request()->routeIs($grandchild->route ?? ''); })) ? 'is-expanded' : '' }}">
                          <a class="treeview-item" href="javascript:void(0);" data-toggle="treeview">
                            <i class="icon {{ $childIcon }}"></i> {{ $child->name }}
                            <i class="treeview-indicator fa fa-angle-right"></i>
                          </a>
                          <ul class="treeview-menu">
                            @foreach($child->children as $grandchild)
                              @php
                                $grandchildIcon = $grandchild->icon ?? 'fa-circle-o';
                                if (strpos($grandchildIcon, 'fa ') === false) {
                                  $grandchildIcon = 'fa ' . (strpos($grandchildIcon, 'fa-') === 0 ? $grandchildIcon : 'fa-' . $grandchildIcon);
                                }
                                $grandchildFullUrl = $grandchild->route ? route($grandchild->route) : '#';
                              @endphp
                              <li>
                                <a class="treeview-item {{ request()->routeIs($grandchild->route ?? '') ? 'active' : '' }}" href="{{ $grandchildFullUrl }}">
                                  <i class="icon {{ $grandchildIcon }}"></i> {{ $grandchild->name }}
                                </a>
                              </li>
                            @endforeach
                          </ul>
                        </li>
                      @else
                        <li>
                          <a class="treeview-item {{ request()->routeIs($child->route ?? '') ? 'active' : '' }}" href="{{ $childFullUrl }}">
                            <i class="icon {{ $childIcon }}"></i> {{ $child->name }}
                            @if(strtolower($child->name ?? '') === 'all transfers' && $pendingTransfersCount > 0)
                              <span class="badge badge-danger" style="margin-left: 8px;">{{ $pendingTransfersCount }}</span>
                            @endif
                          </a>
                        </li>
                      @endif
                    @endforeach
                  </ul>
                </li>
              @else
                <li>
                  <a class="app-menu__item {{ request()->routeIs($menu->route ?? '') ? 'active' : '' }}" href="{{ $menu->full_url }}">
                    <i class="app-menu__icon {{ $menuIcon }}"></i>
                    <span class="app-menu__label">{{ $menu->name }}</span>
                  </a>
                </li>
              @endif
            @endforeach
          @else
            {{-- Fallback to default menu if no configuration --}}
            <li>
              <a class="app-menu__item {{ request()->routeIs('dashboard*') ? 'active' : '' }}" href="{{ $dashboardUrl }}">
                <i class="app-menu__icon fa fa-dashboard"></i>
                <span class="app-menu__label">Dashboard</span>
              </a>
            </li>
          @endif
        @endif
        @endif
        {{-- Only show Payments & Invoices if NOT on configuration page and NOT staff --}}
        @if(Auth::check() && !Auth::user()->isAdmin() && !request()->routeIs('business-configuration.*') && !session('is_staff'))
        <li>
          <a class="app-menu__item {{ request()->routeIs('payments.*') || request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('payments.history') }}">
            <i class="app-menu__icon fa fa-credit-card"></i>
            <span class="app-menu__label">Payments & Invoices</span>
          </a>
        </li>
        @endif
        @if(Auth::check() && Auth::user()->isAdmin())
        <li>
          <a class="app-menu__item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
            <i class="app-menu__icon fa fa-users"></i>
            <span class="app-menu__label">Users</span>
          </a>
        </li>
        <li>
          <a class="app-menu__item {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}" href="{{ route('admin.subscriptions.index') }}">
            <i class="app-menu__icon fa fa-list"></i>
            <span class="app-menu__label">Subscriptions</span>
          </a>
        </li>
        <li>
          <a class="app-menu__item {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}" href="{{ route('admin.plans.index') }}">
            <i class="app-menu__icon fa fa-credit-card"></i>
            <span class="app-menu__label">Plans</span>
          </a>
        </li>
        <li>
          <a class="app-menu__item {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">
            <i class="app-menu__icon fa fa-money"></i>
            <span class="app-menu__label">Payments</span>
          </a>
        </li>
        <li>
          <a class="app-menu__item {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}" href="{{ route('admin.analytics.index') }}">
            <i class="app-menu__icon fa fa-bar-chart"></i>
            <span class="app-menu__label">Analytics</span>
          </a>
        </li>
        @endif
        {{-- Settings is now included in dynamic menus, no need for hardcoded version --}}
      </ul>
    </aside>
    <main class="app-content">
      @yield('content')
    </main>
    <!-- Essential javascripts for application to work-->
    <script src="{{ asset('js/admin/jquery-3.2.1.min.js') }}"></script>
    <script src="{{ asset('js/admin/popper.min.js') }}"></script>
    <script src="{{ asset('js/admin/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/admin/main.js') }}"></script>
    <!-- The javascript plugin to display page loading on top-->
    <script src="{{ asset('js/admin/plugins/pace.min.js') }}"></script>
    <script>
      // Prevent FOUC - Show content when page is ready
      (function() {
        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.add('loaded');
          });
        } else {
          document.body.classList.add('loaded');
        }
      })();
    </script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
      // ============================================================================
      // DUAL NOTIFICATION SYSTEM
      // ============================================================================
      
      // 1. TOAST NOTIFICATIONS (Non-intrusive, auto-dismiss)
      // ────────────────────────────────────────────────────────────────────────────
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer);
          toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
      });

      /**
       * Show a non-intrusive toast notification
       * @param {string} type - 'success', 'error', 'warning', 'info'
       * @param {string} message - The notification message
       * @param {string|null} title - Optional title
       * @param {number} duration - Duration in milliseconds (default: 3000)
       */
      function showToast(type, message, title = null, duration = 3000) {
        const toastConfig = {
          icon: type,
          title: title || message,
          timer: duration
        };
        
        // If both title and message are provided, show message as text
        if (title && message && title !== message) {
          toastConfig.title = title;
          toastConfig.text = message;
        }
        
        Toast.fire(toastConfig);
      }

      // 2. SWEETALERT MODAL POPUPS (Attention-grabbing, requires user action)
      // ────────────────────────────────────────────────────────────────────────────
      
      /**
       * Show a SweetAlert modal dialog
       * @param {string} type - 'success', 'error', 'warning', 'info', 'question'
       * @param {string} message - The alert message
       * @param {string|null} title - Optional title
       * @param {object} options - Additional SweetAlert options
       */
      function showAlert(type, message, title = null, options = {}) {
        const defaultOptions = {
          icon: type,
          title: title || (type.charAt(0).toUpperCase() + type.slice(1)),
          text: message,
          confirmButtonColor: '#940000',
          cancelButtonColor: '#6c757d'
        };
        
        Swal.fire({...defaultOptions, ...options});
      }

      /**
       * Show a confirmation dialog with Yes/No buttons
       * @param {string} message - The confirmation message
       * @param {string} title - The dialog title
       * @param {function} onConfirm - Callback function when confirmed
       * @param {function} onCancel - Optional callback when cancelled
       */
      function showConfirm(message, title = 'Are you sure?', onConfirm, onCancel = null) {
        Swal.fire({
          title: title,
          text: message,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#940000',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Yes',
          cancelButtonText: 'No'
        }).then((result) => {
          if (result.isConfirmed && onConfirm) {
            onConfirm();
          } else if (result.isDismissed && onCancel) {
            onCancel();
          }
        });
      }

      // 3. SESSION MESSAGE INTEGRATION
      // ────────────────────────────────────────────────────────────────────────────
      // By default, session messages use TOAST notifications (non-intrusive)
      // To use modal alerts instead, change showToast() to showAlert()
      
      @if(session('success'))
        showToast('success', '{{ session('success') }}', 'Success!');
      @endif
      
      @if(session('error'))
        showToast('error', '{{ session('error') }}', 'Error!');
      @endif
      
      @if(session('warning'))
        showToast('warning', '{{ session('warning') }}', 'Warning!');
      @endif
      
      @if(session('info'))
        showToast('info', '{{ session('info') }}', 'Info');
      @endif

      // For critical session messages that need modal alerts, use 'alert_success', 'alert_error', etc.
      @if(session('alert_success'))
        showAlert('success', '{{ session('alert_success') }}', 'Success!');
      @endif
      
      @if(session('alert_error'))
        showAlert('error', '{{ session('alert_error') }}', 'Error!');
      @endif
      
      @if(session('alert_warning'))
        showAlert('warning', '{{ session('alert_warning') }}', 'Warning!');
      @endif
      
      @if(session('alert_info'))
        showAlert('info', '{{ session('alert_info') }}', 'Info');
      @endif
    </script>
    @yield('scripts')
    @stack('scripts')
  </body>
</html>
