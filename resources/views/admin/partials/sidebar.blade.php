 <div class="sidenav-menu">
    <!-- Brand Logo -->
    <a href="{{ route('admin.dashboard') }}" class="logo">
        <span class="logo logo-light">
            <span class="logo-lg"><img src="{{asset('backend/assets/images/logo.png')}}" alt="logo" /></span>
            <span class="logo-sm"><img src="{{asset('backend/assets/images/favicon.jpg')}}"alt="small logo" /></span>
        </span>

        <span class="logo logo-dark">
            <span class="logo-lg"><img src="{{asset('backend/assets/images/logo.png')}}" alt="dark logo" /></span>
            <span class="logo-sm"><img src="{{asset('backend/assets/images/favicon.jpg')}}" alt="small logo" /></span>
        </span>
    </a>

    <!-- Sidebar Hover Menu Toggle Button -->
    <button class="button-on-hover">
        <span class="btn-on-hover-icon"></span>
    </button>

    <!-- Full Sidebar Menu Close Button -->
    <button class="button-close-offcanvas">
        <i class="ti ti-menu-4 align-middle"></i>
    </button>

    <div class="scrollbar" data-simplebar="">
        <div id="user-profile-settings" class="sidenav-user" style="background: url(assets/images/user-bg-pattern.svg)">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="#!" class="link-reset">
                        <img src="{{asset('backend/assets/images/users/user-1.jpg')}}" alt="user-image" class="rounded-circle mb-2 avatar-md" />
                        <span class="sidenav-user-name fw-bold">David Dev</span>
                        <span class="fs-12 fw-semibold" data-lang="user-role">Art Director</span>
                    </a>
                </div>
                <div>
                    <a class="dropdown-toggle drop-arrow-none link-reset sidenav-user-set-icon" data-bs-toggle="dropdown" data-bs-offset="0,12" href="#!" aria-haspopup="false" aria-expanded="false">
                        <i class="ti ti-settings fs-24 align-middle ms-1"></i>
                    </a>

                    <div class="dropdown-menu">
                        <!-- Header -->
                        <div class="dropdown-header noti-title">
                            <h6 class="text-overflow m-0">Welcome back!</h6>
                        </div>

                        <!-- My Profile -->
                        <a href="#!" class="dropdown-item">
                            <i class="ti ti-user-circle me-1 fs-lg align-middle"></i>
                            <span class="align-middle">Profile</span>
                        </a>

                        <!-- Settings -->
                        <a href="javascript:void(0);" class="dropdown-item">
                            <i class="ti ti-settings-2 me-1 fs-lg align-middle"></i>
                            <span class="align-middle">Account Settings</span>
                        </a>

                        <!-- Lock -->
                        <a href="auth-lock-screen.html" class="dropdown-item">
                            <i class="ti ti-lock me-1 fs-lg align-middle"></i>
                            <span class="align-middle">Lock Screen</span>
                        </a>

                        <!-- Logout -->
                        <a href="javascript:void(0);" class="dropdown-item text-danger fw-semibold">
                            <i class="ti ti-logout me-1 fs-lg align-middle"></i>
                            <span class="align-middle">Log Out</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!--- Sidenav Menu -->
        <div id="sidenav-menu">
          <ul class="side-nav">
    
    <li class="side-nav-title mt-2" data-lang="main">Main</li>

    <!-- Dashboard -->
    <li class="side-nav-item">
        <a href="{{ route('admin.dashboard') }}" class="side-nav-link">
            <span class="menu-icon"><i class="ti ti-dashboard"></i></span>
            <span class="menu-text">Dashboard</span>
        </a>
    </li>


<li class="side-nav-item">

    <a data-bs-toggle="collapse"
        href="#categoryMenu"
        class="side-nav-link">

        <span class="menu-icon">
            <i class="ti ti-category"></i>
        </span>

        <span class="menu-text">
            Categories
        </span>

        <span class="menu-arrow"></span>

    </a>

    <div class="collapse" id="categoryMenu">

        <ul class="sub-menu">

            <!-- Categories -->
            <li class="side-nav-item">
                <a href="{{ route('category.index') }}"
                    class="side-nav-link">

                    <span class="menu-text">
                        View Categories
                    </span>

                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('category.create') }}"
                    class="side-nav-link">

                    <span class="menu-text">
                        Add Category
                    </span>

                </a>
            </li>

            <!-- Sub Categories -->
            <li class="side-nav-item">
                <a href="{{ route('subcategory.index') }}"
                    class="side-nav-link">

                    <span class="menu-text">
                        View Sub Categories
                    </span>

                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('subcategory.create') }}"
                    class="side-nav-link">

                    <span class="menu-text">
                        Add Sub Category
                    </span>

                </a>
            </li>

        </ul>

    </div>

</li>

<li class="side-nav-item">

    <a data-bs-toggle="collapse"
        href="#productMenu"
        class="side-nav-link">

        <span class="menu-icon">
            <i class="ti ti-package"></i>
        </span>

        <span class="menu-text">
            Products
        </span>

        <span class="menu-arrow"></span>

    </a>

    <div class="collapse" id="productMenu">

        <ul class="sub-menu">

            <li class="side-nav-item">

                <a href="{{ route('product.index') }}"
                    class="side-nav-link">

                    <span class="menu-text">
                        View Products
                    </span>

                </a>

            </li>

            <li class="side-nav-item">

                <a href="{{ route('product.create') }}"
                    class="side-nav-link">

                    <span class="menu-text">
                        Add Product
                    </span>

                </a>

            </li>

        </ul>

    </div>

</li>

    <!-- Slider -->
   <li class="side-nav-item">

    <a data-bs-toggle="collapse"
        href="#sliderMenu"
        class="side-nav-link">

        <span class="menu-icon">
            <i class="ti ti-slideshow"></i>
        </span>

        <span class="menu-text">
            Sliders
        </span>

        <span class="menu-arrow"></span>

    </a>

    <div class="collapse {{ request()->is('admin/slider*') ? 'show' : '' }}"
        id="sliderMenu">

        <ul class="sub-menu">

            <li class="side-nav-item">

                <a href="{{ route('slider.index') }}"
                    class="side-nav-link">

                    <span class="menu-text">
                        View Sliders
                    </span>

                </a>

            </li>

            <li class="side-nav-item">

                <a href="{{ route('slider.create') }}"
                    class="side-nav-link">

                    <span class="menu-text">
                        Add Slider
                    </span>

                </a>

            </li>

        </ul>

    </div>

</li>
<li class="side-nav-item">

    <a href="{{ route('user.index') }}"
        class="side-nav-link">

        <span class="menu-icon">
            <i class="ti ti-users"></i>
        </span>

        <span class="menu-text">
            Users
        </span>

    </a>

</li>
<li class="side-nav-item">

    <a data-bs-toggle="collapse"
        href="#couponMenu"
        class="side-nav-link">

        <span class="menu-icon">
            <i class="ti ti-discount-2"></i>
        </span>

        <span class="menu-text">
            Coupons
        </span>

        <span class="menu-arrow"></span>

    </a>

    <div class="collapse"
        id="couponMenu">

        <ul class="sub-menu">

            <li class="side-nav-item">

                <a href="{{ route('coupon.index') }}"
                    class="side-nav-link">

                    <span class="menu-text">
                        View
                    </span>

                </a>

            </li>

            <li class="side-nav-item">

                <a href="{{ route('coupon.create') }}"
                    class="side-nav-link">

                    <span class="menu-text">
                        Add
                    </span>

                </a>

            </li>

        </ul>

    </div>

</li>
<li class="side-nav-item">

    <a data-bs-toggle="collapse"
        href="#orderMenu"
        class="side-nav-link">

        <span class="menu-icon">
            <i class="ti ti-shopping-cart"></i>
        </span>

        <span class="menu-text">
            Orders
        </span>

        <span class="menu-arrow"></span>

    </a>

    <div class="collapse {{ request()->is('admin/order*') ? 'show' : '' }}"
        id="orderMenu">

        <ul class="sub-menu">

            <li class="side-nav-item">

                <a href="{{ route('order.index') }}"
                    class="side-nav-link">

                    <span class="menu-text">
                        View Orders
                    </span>

                </a>

            </li>

        </ul>

    </div>

</li>
{{-- Blogs --}}
<li class="side-nav-item">

    <a data-bs-toggle="collapse"
        href="#blogMenu"
        class="side-nav-link">

        <span class="menu-icon">
            <i class="ti ti-article"></i>
        </span>

        <span class="menu-text">
            Blogs
        </span>

        <span class="menu-arrow"></span>

    </a>

    <div class="collapse {{ request()->is('admin/blog*') ? 'show' : '' }}"
        id="blogMenu">

        <ul class="sub-menu">

            <li class="side-nav-item">

                <a href="{{ route('blog.index') }}"
                    class="side-nav-link">

                    <span class="menu-text">
                        View Blogs
                    </span>

                </a>

            </li>

            <li class="side-nav-item">

                <a href="{{ route('blog.create') }}"
                    class="side-nav-link">

                    <span class="menu-text">
                        Add Blog
                    </span>

                </a>

            </li>

        </ul>

    </div>

</li>
<li class="side-nav-item">

    <a data-bs-toggle="collapse"
        href="#reviewMenu"
        class="side-nav-link">

        <span class="menu-icon">
            <i class="ti ti-star"></i>
        </span>

        <span class="menu-text">
            Reviews
        </span>

        <span class="menu-arrow"></span>

    </a>

    <div class="collapse"
        id="reviewMenu">

        <ul class="sub-menu">

            <li class="side-nav-item">

                <a href="{{ route('review.index') }}"
                    class="side-nav-link">

                    <span class="menu-text">
                        View Reviews
                    </span>

                </a>

            </li>

        </ul>

    </div>

</li>
{{-- Property Sections --}}
{{-- <li class="side-nav-item">

    <a href="{{ url('admin.property-section.edit') }}"
       class="side-nav-link">

        <span class="menu-icon">
            <i class="ti ti-home"></i>
        </span>

        <span class="menu-text">
            Property Sections
        </span>

    </a>

</li> --}}
<!-- Newsletter -->
{{-- <li class="side-nav-item">

    <a href="{{ url('admin.newsletters.index') }}"
       class="side-nav-link">

        <span class="menu-icon">
            <i class="ti ti-mail"></i>
        </span>

        <span class="menu-text">
            Newsletter
        </span>

    </a>

</li> --}}
    <!-- Benefits -->
    <!-- <li class="side-nav-item">
        <a data-bs-toggle="collapse" href="#benefitsMenu" class="side-nav-link">
            <span class="menu-icon"><i class="ti ti-gift"></i></span>
            <span class="menu-text">Benefits</span>
            <span class="menu-arrow"></span>
        </a>
        <div class="collapse" id="benefitsMenu">
            <ul class="sub-menu">
                <li class="side-nav-item">
                    <a href="/benefits" class="side-nav-link">
                        <span class="menu-text">View</span>
                    </a>
                </li>
                <li class="side-nav-item">
                    <a href="/benefits/create" class="side-nav-link">
                        <span class="menu-text">Add</span>
                    </a>
                </li>
            </ul>
        </div>
    </li> -->

</ul>
        </div>
    </div>
</div>