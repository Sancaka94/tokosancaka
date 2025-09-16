<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('customer.dashboard') }}">
        <div class="sidebar-brand-icon">
            <i class="fas fa-shipping-fast"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Sancaka Express</div>
    </a>
    <!-- Divider -->
    <hr class="sidebar-divider my-0">
    <!-- Nav Item - Dashboard -->
    <li class="nav-item {{ request()->routeIs('customer.dashboard') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('customer.dashboard') }}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>
    <!-- Divider -->
    <hr class="sidebar-divider">
    <!-- Heading -->
    <div class="sidebar-heading">
        Menu Utama
    </div>
    <!-- Nav Item - Menu lainnya -->
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="fas fa-fw fa-box"></i>
            <span>Pesanan Saya</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="fas fa-fw fa-user-circle"></i>
            <span>Profil Saya</span>
        </a>
    </li>
    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">
    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>