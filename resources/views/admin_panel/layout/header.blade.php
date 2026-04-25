<!DOCTYPE html>
<html class="no-js" lang="zxx">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Zare Bootstrap 4 Admin Template">
    <title>Home</title>

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/owl.theme.default.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/themify-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ionicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/et-line.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/flag-icon.min.css') }}">
    <script src="{{ asset('assets/js/modernizr-2.8.3.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/metisMenu.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/slicknav.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/am-charts/css/am-charts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/charts/morris-bundle/morris.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/charts/c3charts/c3.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/data-table/css/jquery.dataTables.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/data-table/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/data-table/css/responsive.bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/data-table/css/responsive.jqueryui.min.css') }}">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    {{-- Online Links --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/brands.min.css"
        integrity="sha512-58P9Hy7II0YeXLv+iFiLCv1rtLW47xmiRpC1oFafeKNShp8V5bKV/ciVtYqbk2YfxXQMt58DjNfkXFOn62xE+g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/brands.min.css"
        integrity="sha512-58P9Hy7II0YeXLv+iFiLCv1rtLW47xmiRpC1oFafeKNShp8V5bKV/ciVtYqbk2YfxXQMt58DjNfkXFOn62xE+g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <div class="container-scroller">
        <nav class="rt_nav_header horizontal-layout col-lg-12 col-12 p-0">
            <div class="top_nav flex-grow-1">
                <div class="container d-flex flex-row h-100 align-items-center">
                    <div class="text-center rt_nav_wrapper d-flex align-items-center">
                        {{-- <a class="nav_logo rt_logo" href="index.html"><img src="{{asset('assets/images/WIJDAN-removebg-preview.png')}}" alt="logo" /></a> --}}
                        {{-- <a class="nav_logo nav_logo_mob" href="index.html"><img src="{{asset('assets/images/WIJDAN-removebg-preview.png')}}" alt="logo"/></a> --}}
                    </div>
                    <div class="nav_wrapper_main d-flex align-items-center justify-content-between flex-grow-1">
                        <ul class="navbar-nav navbar-nav-right mr-0 ml-auto">
                            <!-- Notification Bell -->
                            <!-- Notification Bell -->
                            <li class="nav-item dropdown" id="notificationLi">
                                <a class="nav-link count-indicator dropdown-toggle" id="notificationDropdown"
                                    href="#" data-toggle="dropdown">
                                    <i class="fas fa-bell mx-0"></i>
                                    <span class="badge badge-danger notification-badge"
                                        style="display: none; position: absolute; top: 0px; right: 0px; font-size: 10px; padding: 3px 5px;">0</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list"
                                    aria-labelledby="notificationDropdown" style="width: 300px;">
                                    <p class="mb-0 font-weight-normal float-left dropdown-header">Notifications</p>
                                    <div id="notificationList" style="max-height: 300px; overflow-y: auto;">
                                        <!-- Items will be injected here -->
                                        <p class="text-center p-3 text-muted">Loading...</p>
                                    </div>
                                    {{-- <a href="{{ route('notifications.index') }}" class="dropdown-item text-center text-primary">View all</a> --}}
                                </div>
                            </li>

                            <style>
                                /* CSS Hover Fallback */
                                #notificationLi:hover .dropdown-menu {
                                    display: block;
                                    animation: fadeIn 0.3s;
                                }

                                @keyframes fadeIn {
                                    from {
                                        opacity: 0;
                                    }

                                    to {
                                        opacity: 1;
                                    }
                                }
                            </style>

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    // Poll every 30s
                                    fetchNotifications();
                                    setInterval(fetchNotifications, 30000);
                                });

                                function fetchNotifications() {
                                    if (typeof $ === 'undefined') return; // Safety check

                                    $.get("{{ route('notifications.fetch') }}", function(data) {
                                        // Update Badge
                                        if (data.count > 0) {
                                            $('.notification-badge').text(data.count).show();
                                        } else {
                                            $('.notification-badge').hide();
                                        }

                                        // Update List
                                        let html = '';
                                        if (data.notifications.length === 0) {
                                            html = '<p class="text-center p-3 text-muted">No new notifications</p>';
                                        } else {
                                            data.notifications.forEach(n => {
                                                let iconClass = 'bg-info';
                                                let icon = 'fa-info';

                                                if (n.type === 'sale_return') {
                                                    iconClass = 'bg-warning';
                                                    icon = 'fa-undo';
                                                }

                                                html += `
                                                <a class="dropdown-item preview-item" href="${n.action_url || '#'}">
                                                    <div class="d-flex align-items-center">
                                                        <div class="preview-thumbnail me-3">
                                                            <div class="preview-icon ${iconClass} rounded-circle d-flex align-items-center justify-content-center" style="width:30px; height:30px;">
                                                                <i class="fas ${icon} text-white" style="font-size:12px;"></i>
                                                            </div>
                                                        </div>
                                                        <div class="preview-item-content flex-grow-1 ms-2">
                                                            <h6 class="preview-subject font-weight-normal mb-1" style="font-size:13px;">${n.title}</h6>
                                                            <p class="font-weight-light small-text mb-0 text-muted" style="font-size:11px; white-space: normal;">
                                                                ${n.message.substring(0, 50)}...
                                                            </p>
                                                             <p class="font-weight-light small-text mb-0 text-muted mt-1" style="font-size:10px">
                                                                ${new Date(n.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                `;
                                            });
                                        }
                                        $('#notificationList').html(html);
                                    });
                                }
                            </script>

                            <li class="nav-item nav-profile dropdown">
                                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown"
                                    id="profileDropdown">
                                    <span class="profile_name">{{ Auth::user()->name }} <i
                                            class="feather ft-chevron-down"></i></span>
                                    <img src="assets/images/user.jpg" alt="profile" />
                                </a>
                                <div class="dropdown-menu dropdown-menu-right navbar-dropdown pt-2"
                                    aria-labelledby="profileDropdown">
                                    <a class="dropdown-item">
                                        <i class="ti-user text-dark mr-3"></i> Profile
                                    </a>
                                    <a class="dropdown-item">
                                        <i class="ti-settings text-dark mr-3"></i> Account Settings
                                    </a>
                                    <span role="separator" class="divider"></span>
                                    {{-- <a class="dropdown-item"> --}}
                                    {{-- <i class="ti-power-off text-dark mr-3"></i> --}}
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="ti-power-off text-dark mr-3"></i> Logout
                                        </button>
                                    </form>
                                    {{-- </a> --}}
                                </div>
                            </li>
                            <!--==================================*
                                 End Profile Menu
                        *====================================-->
                        </ul>
                        <!--=========================*
                               Mobile Menu
                   *===========================-->
                        <button class="navbar-toggler align-self-center" type="button" data-toggle="minimize">
                            <span class="feather ft-menu text-white"></span>
                        </button>
                        <!--=========================*
                           End Mobile Menu
                   *===========================-->
                    </div>
                </div>
            </div>
            <div class="nav-bottom">
                <div class="container">
                    <ul class="nav page-navigation">
                        <!--=========================*
                              Home
                    *===========================-->
                        <li class="nav-item">
                            <a href="{{ url('/home') }}" class="nav-link"><i
                                    class="menu_icon feather ft-home"></i><span
                                    class="menu-title">Dashboard</span></a>

                        </li>
                        <!--=========================*
                              UI Features
                    *===========================-->
                        <li class="nav-item mega-menu">
                            <a href="#" class="nav-link"><i class="menu_icon ti-layout-slider"></i><span
                                    class="menu-title">Management</span><i class="menu-arrow"></i></a>
                            <div class="submenu">
                                <div class="col-group-wrapper row">
                                    <div class="col-group col-md-4 mb-mob-0">
                                        <div class="row">
                                            <div class="col-12">
                                                <!--=========================*
                                                      Basic Elements
                                                *===========================-->
                                                <p class="category-heading">Product Managment</p>
                                                <div class="submenu-item">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <ul>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ route('Category.home') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>Category</span></a>
                                                                </li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ route('subcategory.home') }}"><i
                                                                            class="menu_icon ti-id-badge"></i><span>Sub
                                                                            Category</span></a></li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ url('warehouse') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>warehouse</span></a>
                                                                </li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ url('vendor') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>vendor</span></a>
                                                                </li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ url('customer') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>customer</span></a>
                                                                </li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ url('zone') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>zone</span></a>
                                                                </li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ url('sales-officers') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>Sales
                                                                            Officer</span></a></li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ url('transport') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>Transport</span></a>
                                                                </li>
                                                                {{-- <li class="nav-item"><a class="nav-link" href="accordion.html"><i class="menu_icon ti-layout-accordion-separated"></i><span>Accordion</span></a></li>
                                                                <li class="nav-item"><a class="nav-link" href="buttons.html"><i class="menu_icon icon-focus"></i><span>Buttons</span></a></li>
                                                                <li class="nav-item"><a class="nav-link" href="badges.html"><i class="menu_icon icon-ribbon"></i><span>Badges</span></a></li> --}}

                                                            </ul>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <ul>

                                                                {{-- <li class="nav-item"><a class="nav-link" href="carousel.html"><i class="menu_icon ti-layout-slider"></i><span>Carousels</span></a></li>
                                                                <li class="nav-item"><a class="nav-link" href="dropdown.html"><i class="menu_icon icon-layers"></i><span>Dropdown</span></a></li>
                                                                <li class="nav-item"><a class="nav-link" href="tabs.html"><i class="menu_icon ti-layout-tab"></i><span>Tabs</span></a></li> --}}

                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-group col-md-4">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="submenu-item pt-5 mt-2 pt-mob-0 mt-mob-0">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <ul>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ route('Brand.home') }}"><i
                                                                            class="menu_icon ti-smallcap"></i><span>Brands</span></a>
                                                                </li>
                                                                {{-- <li class="nav-item"><a class="nav-link" href="list-group.html"><i class="menu_icon ti-list"></i><span>List Group</span></a></li>
                                                                <li class="nav-item"><a class="nav-link" href="modals.html"><i class="menu_icon ti-layers-alt"></i><span>Modals</span></a></li>
                                                                <li class="nav-item"><a class="nav-link" href="pagination.html"><i class="menu_icon ion-android-more-horizontal"></i><span>Pagination</span></a></li> --}}
                                                            </ul>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <ul>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ route('Unit.home') }}"><i
                                                                            class="menu_icon ion-ios-photos"></i><span>Units</span></a>
                                                                </li>
                                                                {{-- <li class="nav-item"><a class="nav-link" href="progressbar.html"><i class="menu_icon ion-ios-settings-strong"></i><span>Progressbar</span></a></li> --}}
                                                                {{-- <li class="nav-item"><a class="nav-link" href="grid.html"><i class="menu_icon ti-layout-grid4"></i><span>Grid</span></a></li> --}}
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!--=========================*
                                          Icons
                                *===========================-->
                                    <div class="col-group col-md-4">
                                        <p class="category-heading">Products</p>
                                        <ul class="submenu-item">
                                            {{-- <li class="nav-item"><a class="nav-link" href="font-awesome.html"><i class="menu_icon ti-flag-alt"></i> <span>Font Awesome</span></a></li> --}}
                                            {{-- <li class="nav-item"><a class="nav-link" href="themify.html"><i class="menu_icon ti-themify-favicon"></i><span>Themify</span></a></li> --}}
                                            {{-- <li class="nav-item"><a class="nav-link" href="ionicons.html"><i class="menu_icon ion-ionic"></i><span>Ionicons V2</span></a></li> --}}
                                            @if (auth()->user()->can('View Product') || auth()->user()->email === 'admin@admin.com')
                                                <li class="nav-item"><a class="nav-link"
                                                        href="{{ route('product') }}"><i
                                                            class="menu_icon icon-basket"></i><span>Products</span></a>
                                                </li>
                                            @endif
                                            <li class="nav-item"><a class="nav-link"
                                                    href="{{ route('Purchase.home') }}"><i
                                                        class="menu_icon icon-basket"></i><span>Purchase</span></a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @if (auth()->user()->email === 'admin@admin.com')
                            <li class="nav-item">
                                <a href="#" class="nav-link"><i
                                        class="menu_icon feather ft-clipboard"></i><span class="menu-title">User
                                        Managment</span><i class="menu-arrow"></i></a>
                                <div class="submenu">
                                    <ul class="submenu-item">
                                        <li class="nav-item"><a class="nav-link"
                                                href="{{ route('users.index') }}"><i
                                                    class="fa-solid fa-users mr-2"></i><span>Users</span></a></li>
                                        <li class="nav-item"><a class="nav-link"
                                                href="{{ route('roles.index') }}"><i
                                                    class="fa-solid fa-user-lock mr-2"></i><span>Roles</span></a></li>
                                        <li class="nav-item"><a class="nav-link"
                                                href="{{ route('permissions.index') }}"><i
                                                    class="fa-solid fa-user-lock mr-2"></i><span>Permissions</span></a>
                                        </li>
                                        <li class="nav-item"><a class="nav-link"
                                                href="{{ route('branch.index') }}"><i
                                                    class="fa-solid fa-code-branch mr-2"></i><span>Branches</span></a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                        @endif

                        <li class="nav-item">
                            <a href="#" class="nav-link"><i class="menu_icon feather ft-clipboard"></i><span
                                    class="menu-title">Report</span><i class="menu-arrow"></i></a>
                            <div class="submenu">
                                <ul class="submenu-item">
                                    <li class="nav-item"><a class="nav-link" href="{{ route('report.item_stock') }}"><i class="fa-solid fa-boxes-stacked mr-2"></i><span>Item Stock</span></a></li>
                                    <li class="nav-item"><a class="nav-link" href="{{ route('report.purchase') }}"><i class="fa-solid fa-cart-shopping mr-2"></i><span>Purchase Report</span></a></li>
                                    <li class="nav-item"><a class="nav-link" href="{{ route('report.sale') }}"><i class="fa-solid fa-file-invoice-dollar mr-2"></i><span>Sale Report</span></a></li>
                                    <li class="nav-item"><a class="nav-link" href="{{ route('report.customer.ledger') }}"><i class="fa-solid fa-book mr-2"></i><span>Customer Ledger</span></a></li>
                                    <li class="nav-item"><a class="nav-link" href="{{ route('report.vendor.ledger') }}"><i class="fa-solid fa-truck mr-2"></i><span>Vendor Ledger</span></a></li>
                                    <li class="nav-item"><a class="nav-link" href="{{ route('report.profit_loss') }}"><i class="fa-solid fa-chart-line mr-2"></i><span>Profit & Loss</span></a></li>
                                </ul>
                            </div>
                        </li>

                        <!-- Settings -->
                        @if (auth()->user()->email === 'admin@admin.com')
                            <li class="nav-item">
                                <a href="{{ route('settings.index') }}" class="nav-link"><i
                                        class="menu_icon feather ft-settings"></i><span
                                        class="menu-title">Settings</span></a>
                            </li>
                        @endif

                    </ul>
                </div>
            </div>
        </nav>
