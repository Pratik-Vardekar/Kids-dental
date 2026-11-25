<!DOCTYPE html>
<html lang="en">

<head>
    <title>Kids-e-dental</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="base-url" content="{{ url('/') }}">
    <!-- Kids-e-Dental Favicon -->
    <link rel="icon" type="image/png" sizes="32x32"
        href="{{ asset('backend/assets/images/kids-dental-favicon.png') }}?v={{ time() }}">
    <link rel="icon" type="image/png" sizes="16x16"
        href="{{ asset('backend/assets/images/kids-dental-favicon.png') }}?v={{ time() }}">
    <link rel="shortcut icon" type="image/png"
        href="{{ asset('backend/assets/images/kids-dental-favicon.png') }}?v={{ time() }}">
    <link rel="apple-touch-icon" sizes="180x180"
        href="{{ asset('backend/assets/images/kids-dental-favicon.png') }}?v={{ time() }}">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.1/dist/fancybox/fancybox.css" />
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/style.css') }}?v=25">
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/search-suggest.css') }}?v=7">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/18.2.1/css/intlTelInput.css" />
    <style>
        /* Cheriks toast — keep inline so layout cannot break if style.css cache is stale */
        #toastContainer {
            top: 1rem;
            right: 1rem;
            left: auto;
            transform: none;
            width: min(380px, calc(100% - 2rem));
            padding: 0;
            z-index: 11000;
        }

        .cheriks-toast {
            width: 100% !important;
            max-width: 100% !important;
            border: 0 !important;
            border-radius: .75rem !important;
            overflow: hidden;
            background: transparent !important;
            box-shadow: 0 8px 24px rgba(43, 42, 42, .18);
        }

        .cheriks-toast .toast-inner {
            display: flex !important;
            align-items: center !important;
            gap: .65rem;
            padding: .85rem 1rem;
            color: #fff !important;
            font-weight: 600;
            font-size: 1rem;
            background: linear-gradient(90deg, #7846f0 0%, #9a6df7 100%) !important;
        }

        .cheriks-toast-icon {
            width: 1.5rem;
            height: 1.5rem;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #fff;
        }

        .cheriks-toast-icon svg {
            width: 1.5rem;
            height: 1.5rem;
            display: block;
        }

        .cheriks-toast-divider {
            width: 1px;
            align-self: stretch;
            min-height: 1.5rem;
            background: rgba(255, 255, 255, .45);
            flex-shrink: 0;
        }

        .cheriks-toast-body {
            flex: 1;
            line-height: 1.35;
            color: #fff !important;
        }

        .cheriks-toast-body a {
            color: #fff;
            text-decoration: underline;
        }

        .cheriks-toast-close {
            width: 1.75rem;
            height: 1.75rem;
            border: 0 !important;
            border-radius: 50% !important;
            background: rgba(255, 255, 255, .2) !important;
            color: #fff !important;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.1rem;
            line-height: 1;
            cursor: pointer;
            padding: 0;
        }

        .cheriks-toast-close:hover {
            background: rgba(255, 255, 255, .35) !important;
        }

        /* Absolute flag sprite paths — relative CDN paths often show a grey box */
        .iti__flag {
            background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/18.2.1/img/flags.png") !important;
        }

        @media (-webkit-min-device-pixel-ratio: 2),
        (min-resolution: 192dpi) {
            .iti__flag {
                background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/18.2.1/img/flags@2x.png") !important;
            }
        }

        .iti {
            width: 100%;
            display: block;
        }

        .iti input.form-control,
        .iti input[type="tel"] {
            width: 100%;
            height: 48px;
        }

        .iti__selected-dial-code {
            color: #2b2a2a;
            font-weight: 500;
        }

        .cheriks-cart-flight {
            position: fixed;
            left: 0;
            top: 0;
            width: 3rem;
            height: 3rem;
            pointer-events: none;
            z-index: 12050;
            transform: translate3d(-50%, -50%, 0);
            will-change: transform, opacity;
            filter: drop-shadow(0 10px 18px rgba(43, 42, 42, .22));
        }

        .cheriks-cart-flight svg {
            width: 100%;
            height: 100%;
            display: block;
            padding: .6rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, .96);
            color: #7846f0;
            box-shadow: 0 0 0 1px rgba(120, 70, 240, .08);
        }

        .cheriks-cart-flight.is-flying {
            animation: cheriksCartFlight 900ms cubic-bezier(.2, .75, .25, 1) forwards;
        }

        @keyframes cheriksCartFlight {
            0% {
                transform: translate3d(-50%, -50%, 0) scale(1) rotate(-14deg);
                opacity: 1;
            }

            55% {
                transform: translate3d(calc(-50% + var(--flight-mid-x)), calc(-50% + var(--flight-mid-y)), 0) scale(.9) rotate(10deg);
                opacity: .96;
            }

            100% {
                transform: translate3d(calc(-50% + var(--flight-x)), calc(-50% + var(--flight-y)), 0) scale(.18) rotate(22deg);
                opacity: 0;
            }
        }

        .headNavBtn.cart-target-flash,
        #headerCartLink.cart-target-flash {
            animation: cartTargetFlash 650ms ease-out;
        }

        @keyframes cartTargetFlash {
            0% {
                transform: translateY(0) scale(1);
                box-shadow: 0 0 0 0 rgba(120, 70, 240, 0);
            }

            45% {
                transform: translateY(-2px) scale(1.08);
                box-shadow: 0 0 0 10px rgba(120, 70, 240, .10);
            }

            100% {
                transform: translateY(0) scale(1);
                box-shadow: 0 0 0 0 rgba(120, 70, 240, 0);
            }
        }

        .hnbBadge.cart-badge-pop {
            animation: cartBadgePop 650ms ease-out;
        }

        @keyframes cartBadgePop {
            0% {
                transform: scale(1);
            }

            35% {
                transform: scale(1.25);
            }

            100% {
                transform: scale(1);
            }
        }

        .cheriks-cart-flight {
            position: fixed;
            left: 0;
            top: 0;
            width: 3rem;
            height: 3rem;
            pointer-events: none;
            z-index: 12050;
            transform: translate3d(-50%, -50%, 0);
            will-change: transform, opacity;
            transform: translate3d(-50%, -50%, 0);
            will-change: transform, opacity;
            filter: drop-shadow(0 10px 18px rgba(43, 42, 42, .22));
        }

        .cheriks-cart-flight svg {
            width: 100%;
            height: 100%;
            display: block;
            padding: .6rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, .96);
            color: #7846f0;
            box-shadow: 0 0 0 1px rgba(120, 70, 240, .08);
        }

        .cheriks-cart-flight.is-flying {
            animation: cheriksCartFlight 900ms cubic-bezier(.2, .75, .25, 1) forwards;
        }

        @keyframes cheriksCartFlight {
            0% {
                transform: translate3d(-50%, -50%, 0) scale(1) rotate(-14deg);
                opacity: 1;
            }

            55% {
                transform: translate3d(calc(-50% + var(--flight-mid-x)), calc(-50% + var(--flight-mid-y)), 0) scale(.9) rotate(10deg);
                opacity: .96;
            }

            100% {
                transform: translate3d(calc(-50% + var(--flight-x)), calc(-50% + var(--flight-y)), 0) scale(.18) rotate(22deg);
                opacity: 0;
            }
        }

        .headNavBtn.cart-target-flash,
        #headerCartLink.cart-target-flash {
            animation: cartTargetFlash 650ms ease-out;
        }

        @keyframes cartTargetFlash {
            0% {
                transform: translateY(0) scale(1);
                box-shadow: 0 0 0 0 rgba(120, 70, 240, 0);
            }

            45% {
                transform: translateY(-2px) scale(1.08);
                box-shadow: 0 0 0 10px rgba(120, 70, 240, .10);
            }

            100% {
                transform: translateY(0) scale(1);
                box-shadow: 0 0 0 0 rgba(120, 70, 240, 0);
            }
        }

        .hnbBadge.cart-badge-pop {
            animation: cartBadgePop 650ms ease-out;
        }

        @keyframes cartBadgePop {
            0% {
                transform: scale(1);
            }

            35% {
                transform: scale(1.25);
            }

            100% {
                transform: scale(1);
            }
        }

        .cheriks-cart-flight.is-flying {
            animation: cheriksCartFlight 900ms cubic-bezier(.2, .75, .25, 1) forwards;
        }

        @keyframes cheriksCartFlight {
            0% {
                transform: translate3d(-50%, -50%, 0) scale(1) rotate(-14deg);
                opacity: 1;
            }

            55% {
                transform: translate3d(calc(-50% + var(--flight-mid-x)), calc(-50% + var(--flight-mid-y)), 0) scale(.9) rotate(10deg);
                opacity: .96;
            }

            100% {
                transform: translate3d(calc(-50% + var(--flight-x)), calc(-50% + var(--flight-y)), 0) scale(.18) rotate(22deg);
                opacity: 0;
            }
        }

        .headNavBtn.cart-target-flash,
        #headerCartLink.cart-target-flash {
            animation: cartTargetFlash 650ms ease-out;
        }

        @keyframes cartTargetFlash {
            0% {
                transform: translateY(0) scale(1);
                box-shadow: 0 0 0 0 rgba(120, 70, 240, 0);
            }

            45% {
                transform: translateY(-2px) scale(1.08);
                box-shadow: 0 0 0 10px rgba(120, 70, 240, .10);
            }

            100% {
                transform: translateY(0) scale(1);
                box-shadow: 0 0 0 0 rgba(120, 70, 240, 0);
            }
        }

        .hnbBadge.cart-badge-pop {
            animation: cartBadgePop 650ms ease-out;
        }

        @keyframes cartBadgePop {
            0% {
                transform: scale(1);
            }

            35% {
                transform: scale(1.25);
            }

            100% {
                transform: scale(1);
            }
        }
    </style>
    @stack('styles')
</head>

<body class="pageBody mw-100" id="sticky-anchor">
    <!-- Begin page -->

    @include('frontend.partials.header')
    @yield('content')
    @include('frontend.partials.footer')



    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
        integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.1/dist/fancybox/fancybox.umd.js"></script>
    <script src="{{ asset('frontend/assets/js/tilt.jquery.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/common.js') }}"></script>
    <script>
        function initSwiperIfExists(selector, options) {
            const el = document.querySelector(selector);
            if (!el) {
                return null;
            }
            return new Swiper(selector, options);
        }

        initSwiperIfExists(".homeBanner", {
            slidesPerView: 1,
            spaceBetween: 0,
            speed: 1000,
            loop: true,
            grabCursor: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false
            },
            navigation: {
                nextEl: ".hbNext",
                prevEl: ".hbPrev"
            },
            parallax: true
        });

        initSwiperIfExists(".marqueStrip", {
            slidesPerView: "auto",
            spaceBetween: 100,
            speed: 6000,
            loop: true,
            grabCursor: false,
            allowTouchMove: false,
            autoplay: {
                delay: 0,
                disableOnInteraction: false
            }
        });

        initSwiperIfExists(".testimSwiper", {
            slidesPerView: 1.1,
            spaceBetween: 20,
            speed: 1000,
            grabCursor: true,
            navigation: {
                nextEl: ".tsNext",
                prevEl: ".tsPrev"
            },
            breakpoints: {
                576: {
                    slidesPerView: 1.2,
                    spaceBetween: 20
                },
                768: {
                    slidesPerView: 1.5,
                    spaceBetween: 20
                },
                992: {
                    slidesPerView: 2.9,
                    spaceBetween: 20
                },
                1400: {
                    slidesPerView: 3.3,
                    spaceBetween: 30
                }
            }
        });

        initSwiperIfExists(".productSlider", {
            slidesPerView: 1.1,
            spaceBetween: 20,
            speed: 1000,
            navigation: {
                nextEl: ".snbNext",
                prevEl: ".snbPrev"
            },
            breakpoints: {
                576: {
                    slidesPerView: 2,
                    spaceBetween: 30
                },
                768: {
                    slidesPerView: 2,
                    spaceBetween: 30
                },
                992: {
                    slidesPerView: 3,
                    spaceBetween: 30
                },
                1400: {
                    slidesPerView: 4,
                    spaceBetween: 30
                }
            }
        });

        const breakpoint = window.matchMedia('(min-width: 576px)');
        let flowerSwiper;

        const breakpointChecker = function() {
            if (breakpoint.matches === true) {
                if (flowerSwiper !== undefined) flowerSwiper.destroy(true, true);
                return;
            } else if (breakpoint.matches === false) {
                return enableSwiper();
            }
        };

        const enableSwiper = function() {
            if (!document.querySelector('.flowerSwiper')) {
                return;
            }
            flowerSwiper = new Swiper('.flowerSwiper', {
                effect: "flip",
                grabCursor: true,
                loop: true,
                speed: 1000,
                flipEffect: {
                    slideShadows: false
                },
                autoplay: {
                    delay: 4000
                },
                pagination: {
                    el: '.swiper-pagination',
                    type: 'bullets',
                    clickable: true
                }
            });
        };
        breakpoint.addListener(breakpointChecker);
        breakpointChecker();

        /* xxxxxxxx */

        if (typeof $.fn.tilt === 'function' && $('.catgBox, .galleryBox').length) {
            $('.catgBox, .galleryBox').tilt({
                maxTilt: 20,
                perspective: 1000,
                reset: true,
                transition: true,
                easing: "cubic-bezier(.03,.98,.52,.99)"
            });
        }

        // Click image → zoom in; click again → zoom out (works even when image already fits screen)
        if (typeof Fancybox !== 'undefined') {
            Fancybox.bind('[data-fancybox]', {
                Carousel: {
                    Zoomable: {
                        Panzoom: {
                            clickAction: 'toggleMax',
                            dblClickAction: 'toggleMax',
                            maxScale: 3,
                            wheelAction: 'zoom',
                        },
                    },
                },
            });
        }
    </script>
    <script>
        $('.mvvBox').tilt({
            maxTilt: 10,
            perspective: 1000,
            reset: true,
            transition: true,
            easing: "cubic-bezier(.03,.98,.52,.99)",
        });

        /* xxxxxxxx */

        $(window).scroll(function() {
            $('.countNo').each(function() {
                oTop = $(this).offset().top - window.innerHeight;
                if ($(window).scrollTop() > oTop) {
                    var $this = $(this),
                        countTo = $this.attr('data-count');
                    $({
                        countNum: $this.text()
                    }).animate({
                        countNum: countTo
                    }, {
                        duration: 1500,
                        easing: 'swing',
                        step: function() {
                            $this.text(Math.floor(this.countNum));
                        },
                        complete: function() {
                            $this.text(this.countNum);
                        }
                    });
                }
            });
        });
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (function() {
            const mapEl = document.getElementById('mapLocation');
            if (!mapEl || typeof L === 'undefined') {
                return;
            }

            const map = L.map('mapLocation').setView([19.085170130445036, 72.8405271288358], 15);

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);
            
            var myIcon = L.icon({
                iconUrl: "{{ asset('backend/assets/images/KidseDental_Logo.png') }}",
                iconSize: [70, 26],
                popupAnchor: [-3, -10],
                shadowUrl: "{{ asset('frontend/assets/images/mapMarkerShadow.png') }}",
                shadowSize: [68, 13],
                shadowAnchor: [34, -13]
            });
            L.marker([19.085170130445036, 72.8405271288358], {
                    icon: myIcon
                })
                .addTo(map)
                .bindPopup(
                    '<b class="d-block text-center txtred">We are here</b>Kids-e-Dental<a href="https://www.google.com/maps/dir/19.176419,72.835546/Cheriks+Innovative+Ventures+Pvt+Ltd,+Chs+Ltd,+Phirozshah+Mehta+Rd,+nr.+Jain+Upashraya,+Railway+Colony,+Santacruz+(West),+Mumbai,+Maharashtra+400054/@19.1316915,72.8044683,17186m/data=!3m1!1e3!4m10!4m9!1m1!4e1!1m5!1m1!1s0x3be7c9292045a675:0xfcf7740cad644447!2m2!1d72.8405164!2d19.0851752!3e0?entry=ttu&g_ep=EgoyMDI2MDYyMi4wIKXMDSoASAFQAw%3D%3D" target="_blank" class="d-block text-center mt-2 fw-semibold" style="font-size:12px">Get Directions</a>'
                );
        })();
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/18.2.1/js/intlTelInput.min.js"></script>
    <script>
        $(document).on('click', '.passwordBtn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $input = $btn.siblings('.form-control').first();
            if (!$input.length) {
                $input = $btn.closest('.position-relative, .col-12').find(
                    'input[type="password"], input[type="text"]').first();
            }
            if (!$input.length) {
                return;
            }
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $btn.addClass('active');
            } else {
                $input.attr('type', 'password');
                $btn.removeClass('active');
            }
        });

        const phoneInput = document.querySelector("#country");
        if (phoneInput && window.intlTelInput) {
            const iti = window.intlTelInput(phoneInput, {
                initialCountry: "in",
                preferredCountries: ["in"],
                separateDialCode: true,
                nationalMode: true,
                formatOnDisplay: false,
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/18.2.1/js/utils.js",
            });

            // Don't allow starting 0 when country dial code is already shown (e.g. +91)
            const cleanPhoneDigits = (value) => (value || "").replace(/\D/g, "").replace(/^0+/, "");

            // If old value came back as +910XXXXXXXXX, show national digits only (no leading 0)
            if (phoneInput.value) {
                let existing = phoneInput.value.replace(/\D/g, "");
                const dial = iti.getSelectedCountryData().dialCode || "91";
                if (existing.startsWith(dial)) {
                    existing = existing.slice(dial.length);
                }
                phoneInput.value = existing.replace(/^0+/, "");
            }

            phoneInput.addEventListener("input", function() {
                const cleaned = cleanPhoneDigits(phoneInput.value);
                if (phoneInput.value !== cleaned) {
                    phoneInput.value = cleaned;
                }
            });

            phoneInput.addEventListener("blur", function() {
                phoneInput.value = cleanPhoneDigits(phoneInput.value);
            });

            const phoneForm = phoneInput.closest("form");
            if (phoneForm) {
                phoneForm.addEventListener("submit", function() {
                    const dialCode = iti.getSelectedCountryData().dialCode;
                    const number = cleanPhoneDigits(phoneInput.value);
                    if (number) {
                        phoneInput.value = "+" + dialCode + number;
                    } else {
                        phoneInput.value = "";
                    }
                });
            }
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const isAuthenticated = @json(auth()->check());
            const loginUrl = @json(route('login'));
            const registerUrl = @json(route('register'));
            const checkoutUrl = @json(route('checkout.shipping', ['source' => 'cart']));
            const toastIcons = {
                success: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>',
                error: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>',
                warning: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>',
                info: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>'
            };

            function showToast(message, type) {
                type = type || 'success';
                const container = document.getElementById('toastContainer');
                if (!container) {
                    return;
                }

                const toastType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'success';
                const toastId = 'toast-' + Date.now();
                const toastIcon = toastIcons[toastType] || toastIcons.success;

                container.insertAdjacentHTML('beforeend',
                    '<div id="' + toastId + '" class="toast cheriks-toast cheriks-toast-' + toastType +
                    ' border-0" role="alert" aria-live="assertive" aria-atomic="true">' +
                    '<div class="toast-inner">' +
                    '<span class="cheriks-toast-icon">' + toastIcon + '</span>' +
                    '<span class="cheriks-toast-divider" aria-hidden="true"></span>' +
                    '<div class="cheriks-toast-body">' + message + '</div>' +
                    '<button type="button" class="cheriks-toast-close" data-bs-dismiss="toast" aria-label="Close">&times;</button>' +
                    '</div>' +
                    '</div>'
                );

                const toastEl = document.getElementById(toastId);
                const toast = new bootstrap.Toast(toastEl, {
                    delay: 1000
                });
                toast.show();
                toastEl.addEventListener('hidden.bs.toast', function() {
                    toastEl.remove();
                });
            }

            function updateCartBadge(count) {
                document.querySelectorAll('.js-cart-count').forEach(function(badge) {
                    badge.textContent = count;
                    badge.style.display = count > 0 ? 'block' : 'none';
                });
            }

            function getCartTarget() {
                return document.getElementById('headerCartLink') || document.querySelector(
                    '.headNavBtn[href*="cart"]');
            }

            function animateCartFlight(sourceElement) {
                const cartTarget = getCartTarget();
                if (!sourceElement || !cartTarget) {
                    return;
                }

                const sourceRect = sourceElement.getBoundingClientRect();
                const targetRect = cartTarget.getBoundingClientRect();
                if (!sourceRect.width || !sourceRect.height || !targetRect.width || !targetRect.height) {
                    return;
                }

                const flight = document.createElement('div');
                flight.className = 'cheriks-cart-flight';
                flight.innerHTML =
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12 22 3l-5 19-5-8-8-2z"/><path d="M12 14 22 3"/></svg>';

                const startX = sourceRect.left + (sourceRect.width / 2);
                const startY = sourceRect.top + (sourceRect.height / 2);
                const endX = targetRect.left + (targetRect.width / 2);
                const endY = targetRect.top + (targetRect.height / 2);
                const deltaX = endX - startX;
                const deltaY = endY - startY;
                const arcLift = Math.max(70, Math.min(180, Math.abs(deltaX) * 0.22));

                flight.style.left = `${startX}px`;
                flight.style.top = `${startY}px`;
                flight.style.setProperty('--flight-x', `${deltaX}px`);
                flight.style.setProperty('--flight-y', `${deltaY}px`);
                flight.style.setProperty('--flight-mid-x', `${deltaX * 0.55}px`);
                flight.style.setProperty('--flight-mid-y', `${deltaY * 0.45 - arcLift}px`);

                document.body.appendChild(flight);
                window.requestAnimationFrame(function() {
                    flight.classList.add('is-flying');
                });

                window.setTimeout(function() {
                    flight.remove();
                }, 1000);
            }

            function flashCartTarget() {
                const cartTarget = getCartTarget();
                if (!cartTarget) {
                    return;
                }

                const badge = cartTarget.querySelector('.hnbBadge');
                cartTarget.classList.remove('cart-target-flash');
                void cartTarget.offsetWidth;
                cartTarget.classList.add('cart-target-flash');

                if (badge) {
                    badge.classList.remove('cart-badge-pop');
                    void badge.offsetWidth;
                    badge.classList.add('cart-badge-pop');
                    window.setTimeout(function() {
                        badge.classList.remove('cart-badge-pop');
                    }, 700);
                }

                window.setTimeout(function() {
                    cartTarget.classList.remove('cart-target-flash');
                }, 700);
            }

            function getCartTarget() {
                return document.getElementById('headerCartLink') || document.querySelector(
                    '.headNavBtn[href*="cart"]');
            }

            function animateCartFlight(sourceElement) {
                const cartTarget = getCartTarget();
                if (!sourceElement || !cartTarget) {
                    return;
                }

                const sourceRect = sourceElement.getBoundingClientRect();
                const targetRect = cartTarget.getBoundingClientRect();
                if (!sourceRect.width || !sourceRect.height || !targetRect.width || !targetRect.height) {
                    return;
                }

                const flight = document.createElement('div');
                flight.className = 'cheriks-cart-flight';
                flight.innerHTML =
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12 22 3l-5 19-5-8-8-2z"/><path d="M12 14 22 3"/></svg>';

                const startX = sourceRect.left + (sourceRect.width / 2);
                const startY = sourceRect.top + (sourceRect.height / 2);
                const endX = targetRect.left + (targetRect.width / 2);
                const endY = targetRect.top + (targetRect.height / 2);
                const deltaX = endX - startX;
                const deltaY = endY - startY;
                const arcLift = Math.max(70, Math.min(180, Math.abs(deltaX) * 0.22));

                flight.style.left = `${startX}px`;
                flight.style.top = `${startY}px`;
                flight.style.setProperty('--flight-x', `${deltaX}px`);
                flight.style.setProperty('--flight-y', `${deltaY}px`);
                flight.style.setProperty('--flight-mid-x', `${deltaX * 0.55}px`);
                flight.style.setProperty('--flight-mid-y', `${deltaY * 0.45 - arcLift}px`);

                document.body.appendChild(flight);
                window.requestAnimationFrame(function() {
                    flight.classList.add('is-flying');
                });

                window.setTimeout(function() {
                    flight.remove();
                }, 1000);
            }

            function flashCartTarget() {
                const cartTarget = getCartTarget();
                if (!cartTarget) {
                    return;
                }

                const badge = cartTarget.querySelector('.hnbBadge');
                cartTarget.classList.remove('cart-target-flash');
                void cartTarget.offsetWidth;
                cartTarget.classList.add('cart-target-flash');

                if (badge) {
                    badge.classList.remove('cart-badge-pop');
                    void badge.offsetWidth;
                    badge.classList.add('cart-badge-pop');
                    window.setTimeout(function() {
                        badge.classList.remove('cart-badge-pop');
                    }, 700);
                }

                window.setTimeout(function() {
                    cartTarget.classList.remove('cart-target-flash');
                }, 700);
            }

            function showLoginRequiredToast(message) {
                showToast(
                    (message || 'Please login first to place your order.') +
                    ' <a href="' + loginUrl + '">Login</a> or <a href="' + registerUrl + '">Register</a>',
                    'warning'
                );
            }

            @if (session('success'))
                showToast(@json(session('success')), 'success');
            @endif

            @if (session('error'))
                showToast(@json(session('error')), 'error');
            @endif

            @if (session('status'))
                showToast(@json(session('status')), 'success');
            @endif

            @if (session('popup'))
                showToast(@json(session('popup.message', 'Success')), @json(session('popup.type', 'success')));
            @endif

            @if ($errors->any() && !session('error'))
                showToast(@json($errors->first()), 'error');
            @endif

            @if (session('login_required'))
                showLoginRequiredToast('Please login first to complete your order.');
            @endif

            document.addEventListener('click', function(event) {
                const addButton = event.target.closest('.js-add-to-cart');
                if (addButton) {
                    event.preventDefault();

                    const productId = addButton.dataset.productId;
                    if (!productId) {
                        return;
                    }

                    const container = addButton.closest(
                        '#productPurchaseBox, .prodItemBox, .bagItem, form, .pibBtns');
                    const qtyInput = container?.querySelector('.numData');
                    const qty = qtyInput ? Math.min(10, Math.max(1, parseInt(qtyInput.value || '1', 10))) :
                        1;

                    const variantInput = container?.querySelector('input[name="variant_id"]') || document.getElementById('selectedVariantId');
                    const isVariable = !!document.getElementById('isVariableProduct');
                    const variantId = variantInput?.value || addButton.dataset.variantId || null;

                    if (isVariable && !variantId) {
                        showToast('Please select both Arch and Size before adding to cart.', 'error');
                        const archBox = document.getElementById('variantArchSelector');
                        if (archBox) {
                            archBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        return;
                    }

                    addButton.disabled = true;

                    fetch(`{{ url('/cart/add') }}/${productId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({
                                qty: qty,
                                variant_id: variantId
                            }),
                        })
                        .then(function(response) {
                            return response.json().then(function(data) {
                                return {
                                    ok: response.ok,
                                    data: data
                                };
                            });
                        })
                        .then(function({
                            ok,
                            data
                        }) {
                            if (ok && data.success) {
                                updateCartBadge(data.cart_count);
                                showToast(data.message || 'Product added to cart successfully.',
                                    'success');
                                return;
                            }

                            showToast(data.message || 'Could not add item to cart.', 'error');
                        })
                        .catch(function() {
                            showToast('Could not add item to cart.', 'error');
                        })
                        .finally(function() {
                            addButton.disabled = false;
                        });

                    return;
                }

                const buyButton = event.target.closest('.js-buy-now');
                if (buyButton) {
                    event.preventDefault();

                    if (!isAuthenticated) {
                        showLoginRequiredToast('Please login first to buy this product.');
                        return;
                    }

                    const buyUrl = buyButton.dataset.buyUrl;
                    const container = buyButton.closest('#productPurchaseBox, form') || document.getElementById('productPurchaseBox');
                    const qtyInput = container?.querySelector('.numData');
                    const qty = qtyInput ? Math.min(10, Math.max(1, parseInt(qtyInput.value || '1', 10))) :
                        1;

                    const variantInput = container?.querySelector('input[name="variant_id"]') || document.getElementById('selectedVariantId');
                    const isVariable = !!document.getElementById('isVariableProduct');
                    const variantId = variantInput?.value || buyButton.dataset.variantId || null;

                    if (isVariable && !variantId) {
                        showToast('Please select both Arch and Size before proceeding.', 'error');
                        const archBox = document.getElementById('variantArchSelector');
                        if (archBox) {
                            archBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        return;
                    }

                    if (buyUrl) {
                        const sep = buyUrl.includes('?') ? '&' : '?';
                        window.location.href = `${buyUrl}${sep}qty=${qty}${variantId ? '&variant_id=' + variantId : ''}`;
                    }

                    return;
                }

                const placeOrderButton = event.target.closest('.js-place-order');
                if (placeOrderButton) {
                    event.preventDefault();

                    if (!isAuthenticated) {
                        showLoginRequiredToast('Please login first to place your order.');
                        return;
                    }

                    window.location.href = checkoutUrl;
                }
            });
        });
    </script>

    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

    @stack('scripts')

    <script src="{{ asset('frontend/assets/js/search-suggest.js') }}?v=8"></script>

</body>

</html>
