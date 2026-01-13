<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('meta_title', 'Massage Home')</title>
    <meta name="description" content="@yield('meta_description', 'Premium Massage and Spa Services at Home')">
    <meta name="keywords" content="@yield('meta_keywords', 'massage, spa, home service')">

    <!-- Open Graph -->
    <meta property="og:title" content="@yield('meta_title', 'Massage Home')">
    <meta property="og:description" content="@yield('meta_description', 'Premium Massage and Spa Services at Home')">
    <meta property="og:image" content="@yield('og_image', asset('images/default-og.jpg'))">
    <meta property="og:type" content="website">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('styles')
</head>

<body class="antialiased text-slate-900 bg-white">
    <!-- Header -->
    <header class="sticky top-0 z-50 glass border-b border-slate-200">
        <div class="container mx-auto px-4 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    @yield('header_left')
                    <a href="/" class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </div>
                        <span class="text-2xl font-extrabold gradient-text">
                            @yield('header_title', 'Massage Home')
                        </span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex items-center gap-8">
                    <a href="/" class="text-sm font-semibold {{ Request::is('/') ? 'text-indigo-600' : 'text-slate-600 hover:text-indigo-600' }} transition-colors">
                        Trang chủ
                    </a>
                    <a href="/about-us" class="text-sm font-semibold {{ Request::is('about-us') ? 'text-indigo-600' : 'text-slate-600 hover:text-indigo-600' }} transition-colors">
                        Về chúng tôi
                    </a>
                    <a href="/services" class="text-sm font-semibold {{ Request::is('services') ? 'text-indigo-600' : 'text-slate-600 hover:text-indigo-600' }} transition-colors">
                        Dịch vụ
                    </a>
                    <a href="/contact" class="text-sm font-semibold {{ Request::is('contact') ? 'text-indigo-600' : 'text-slate-600 hover:text-indigo-600' }} transition-colors">
                        Liên hệ
                    </a>
                </nav>

                <!-- CTA Buttons -->
                <div class="hidden md:flex items-center gap-4">
                    @yield('header_right')
                    <a href="/booking" class="btn-ripple px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-semibold rounded-xl hover:shadow-lg transition-all">
                        Đặt lịch ngay
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <button class="md:hidden p-2 rounded-lg hover:bg-slate-100 transition-colors" onclick="toggleMobileMenu()">
                    <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobileMenu" class="hidden md:hidden border-t border-slate-200 bg-white">
            <div class="container mx-auto px-4 py-4 space-y-3">
                <a href="/" class="block px-4 py-2 text-sm font-semibold {{ Request::is('/') ? 'text-indigo-600 bg-indigo-50' : 'text-slate-600' }} rounded-lg hover:bg-slate-50 transition-colors">
                    Trang chủ
                </a>
                <a href="/about-us" class="block px-4 py-2 text-sm font-semibold {{ Request::is('about-us') ? 'text-indigo-600 bg-indigo-50' : 'text-slate-600' }} rounded-lg hover:bg-slate-50 transition-colors">
                    Về chúng tôi
                </a>
                <a href="/services" class="block px-4 py-2 text-sm font-semibold {{ Request::is('services') ? 'text-indigo-600 bg-indigo-50' : 'text-slate-600' }} rounded-lg hover:bg-slate-50 transition-colors">
                    Dịch vụ
                </a>
                <a href="/contact" class="block px-4 py-2 text-sm font-semibold {{ Request::is('contact') ? 'text-indigo-600 bg-indigo-50' : 'text-slate-600' }} rounded-lg hover:bg-slate-50 transition-colors">
                    Liên hệ
                </a>
                <a href="/booking" class="block px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-semibold rounded-xl text-center">
                    Đặt lịch ngay
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-white">
        <div class="container mx-auto px-4 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
                <!-- Company Info -->
                <div>
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </div>
                        <span class="text-xl font-bold">Massage Home</span>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed mb-4">
                        Dịch vụ spa và massage chuyên nghiệp tại nhà, mang đến sự thư giãn tuyệt vời cho bạn.
                    </p>
                    <div class="flex gap-3">
                        <a href="#" class="w-10 h-10 bg-slate-800 hover:bg-indigo-600 rounded-lg flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-slate-800 hover:bg-indigo-600 rounded-lg flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-slate-800 hover:bg-indigo-600 rounded-lg flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-bold mb-6">Liên kết nhanh</h3>
                    <ul class="space-y-3">
                        <li><a href="/" class="text-slate-400 hover:text-white text-sm transition-colors">Trang chủ</a></li>
                        <li><a href="/about-us" class="text-slate-400 hover:text-white text-sm transition-colors">Về chúng tôi</a></li>
                        <li><a href="/services" class="text-slate-400 hover:text-white text-sm transition-colors">Dịch vụ</a></li>
                        <li><a href="/pricing" class="text-slate-400 hover:text-white text-sm transition-colors">Bảng giá</a></li>
                        <li><a href="/contact" class="text-slate-400 hover:text-white text-sm transition-colors">Liên hệ</a></li>
                    </ul>
                </div>

                <!-- Services -->
                <div>
                    <h3 class="text-lg font-bold mb-6">Dịch vụ</h3>
                    <ul class="space-y-3">
                        <li><a href="/services/massage-body" class="text-slate-400 hover:text-white text-sm transition-colors">Massage Body</a></li>
                        <li><a href="/services/massage-foot" class="text-slate-400 hover:text-white text-sm transition-colors">Massage Foot</a></li>
                        <li><a href="/services/spa-therapy" class="text-slate-400 hover:text-white text-sm transition-colors">Spa Therapy</a></li>
                        <li><a href="/services/hot-stone" class="text-slate-400 hover:text-white text-sm transition-colors">Hot Stone</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h3 class="text-lg font-bold mb-6">Liên hệ</h3>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-indigo-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="text-slate-400 text-sm">123 Đường ABC, Quận XYZ, TP.HCM</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span class="text-slate-400 text-sm">1900 xxxx</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-slate-400 text-sm">contact@massagehome.vn</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-t border-slate-800 mt-12 pt-8 text-center">
                <p class="text-slate-400 text-sm">
                    © {{ date('Y') }} Massage Home. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }
    </script>

    @yield('scripts')
</body>

</html>
