@extends('layouts.spa')

@section('meta_title', __('home.meta_title'))
@section('meta_description', __('home.meta_description'))
@section('meta_keywords', __('home.meta_keywords'))
@section('og_image', __('home.og_image'))

@section('header_title', __('home.header_title'))

@section('content')
    {{-- Hero Section --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 gradient-animated">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-20 left-20 w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl animate-blob"></div>
            <div class="absolute top-40 right-20 w-96 h-96 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-2000"></div>
            <div class="absolute -bottom-20 left-40 w-96 h-96 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-4000"></div>
        </div>

        <div class="container mx-auto px-4 lg:px-8 py-24 lg:py-32">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left Content -->
                <div class="relative z-10 animate-fade-in-up">
                    {{-- Badge --}}
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/80 backdrop-blur-sm rounded-full shadow-lg mb-6">
                        <span class="text-sm font-semibold text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-pink-500">
                            {{ __('home.hero_badge') }}
                        </span>
                    </div>

                    {{-- Title --}}
                    <h1 class="text-5xl lg:text-6xl font-extrabold text-slate-900 mb-6 leading-tight">
                        {{ __('home.hero_title') }}
                        <span class="block gradient-text">{{ __('home.hero_subtitle') }}</span>
                    </h1>

                    {{-- Description --}}
                    <p class="text-xl text-slate-600 mb-8 leading-relaxed max-w-xl">
                        {{ __('home.hero_description') }}
                    </p>

                    {{-- CTA Buttons --}}
                    <div class="flex flex-wrap gap-4 mb-12">
                        <a href="/services"
                            class="btn-ripple inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-2xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300">
                            {{ __('home.hero_cta_primary') }}
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                        <a href="/about-us"
                            class="inline-flex items-center justify-center px-8 py-4 bg-white text-indigo-600 font-semibold rounded-2xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300">
                            {{ __('home.hero_cta_secondary') }}
                        </a>
                    </div>

                    {{-- Stats --}}
                    <div class="grid grid-cols-4 gap-8">
                        @php
                            $stats = [
                                ['value' => '10K+', 'label' => 'home.stats_customers', 'delay' => '0.1s'],
                                ['value' => '50+', 'label' => 'home.stats_therapists', 'delay' => '0.2s'],
                                ['value' => '20+', 'label' => 'home.stats_services', 'delay' => '0.3s'],
                                ['value' => '4.9', 'label' => 'home.stats_rating', 'delay' => '0.4s'],
                            ];
                        @endphp
                        @foreach($stats as $stat)
                            <div class="text-center animate-fade-in-up" style="animation-delay: {{ $stat['delay'] }}">
                                <div class="text-3xl font-bold gradient-text">{{ $stat['value'] }}</div>
                                <div class="text-sm text-slate-600 mt-1">{{ __($stat['label']) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Right Image -->
                <div class="relative lg:block hidden">
                    <div class="relative z-10 rounded-3xl overflow-hidden shadow-2xl">
                        <img src="https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=800&h=1000&fit=crop" 
                             alt="Spa Massage" 
                             class="w-full h-[600px] object-cover">
                    </div>
                    <div class="absolute -bottom-10 -right-10 w-72 h-72 bg-gradient-to-br from-indigo-400 to-purple-400 rounded-3xl opacity-20 blur-2xl"></div>
                </div>
            </div>
        </div>
    </section>

    {{-- Services Section --}}
    <section class="py-24 bg-white">
        <div class="container mx-auto px-4 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-slate-900 mb-4">{{ __('home.services_title') }}</h2>
                <p class="text-xl text-slate-600 max-w-2xl mx-auto">{{ __('home.services_subtitle') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                @php
                    $services = [
                        [
                            'name' => 'home.service_1_name',
                            'desc' => 'home.service_1_desc',
                            'duration' => 'home.service_1_duration',
                            'price' => 'home.service_1_price',
                            'card_bg' => 'bg-gradient-to-br from-indigo-50 to-indigo-100',
                            'icon_bg' => 'bg-gradient-to-br from-indigo-500 to-purple-600',
                            'border' => 'border-indigo-200',
                            'text_color' => 'text-indigo-600',
                            'icon' => 'M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                            'delay' => '0s'
                        ],
                        [
                            'name' => 'home.service_2_name',
                            'desc' => 'home.service_2_desc',
                            'duration' => 'home.service_2_duration',
                            'price' => 'home.service_2_price',
                            'card_bg' => 'bg-gradient-to-br from-purple-50 to-purple-100',
                            'icon_bg' => 'bg-gradient-to-br from-purple-500 to-pink-600',
                            'border' => 'border-purple-200',
                            'text_color' => 'text-purple-600',
                            'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
                            'delay' => '0.1s'
                        ],
                        [
                            'name' => 'home.service_3_name',
                            'desc' => 'home.service_3_desc',
                            'duration' => 'home.service_3_duration',
                            'price' => 'home.service_3_price',
                            'card_bg' => 'bg-gradient-to-br from-pink-50 to-pink-100',
                            'icon_bg' => 'bg-gradient-to-br from-pink-500 to-rose-600',
                            'border' => 'border-pink-200',
                            'text_color' => 'text-pink-600',
                            'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
                            'delay' => '0.2s'
                        ],
                        [
                            'name' => 'home.service_4_name',
                            'desc' => 'home.service_4_desc',
                            'duration' => 'home.service_4_duration',
                            'price' => 'home.service_4_price',
                            'card_bg' => 'bg-gradient-to-br from-orange-50 to-orange-100',
                            'icon_bg' => 'bg-gradient-to-br from-orange-500 to-amber-600',
                            'border' => 'border-orange-200',
                            'text_color' => 'text-orange-600',
                            'icon' => 'M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z',
                            'delay' => '0.3s'
                        ]
                    ];
                @endphp

                @foreach($services as $service)
                    <div class="card-hover {{ $service['card_bg'] }} rounded-3xl p-8 border {{ $service['border'] }}">
                        <div class="w-16 h-16 {{ $service['icon_bg'] }} rounded-2xl flex items-center justify-center mb-6 float-animation" style="animation-delay: {{ $service['delay'] }}">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $service['icon'] }}" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-900 mb-3">{{ __($service['name']) }}</h3>
                        <p class="text-slate-600 mb-6 leading-relaxed">{{ __($service['desc']) }}</p>
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <div class="text-sm text-slate-500">{{ __($service['duration']) }}</div>
                                <div class="text-2xl font-bold {{ $service['text_color'] }}">{{ __($service['price']) }}</div>
                            </div>
                        </div>
                        <a href="/services" class="block w-full text-center px-6 py-3 bg-white {{ $service['text_color'] }} font-semibold rounded-xl shadow-sm hover:shadow-lg transition-all">
                            {{ __('home.service_book_now') }}
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="text-center mt-12">
                <a href="/services" class="inline-flex items-center gap-2 text-indigo-600 font-semibold text-lg hover:gap-3 transition-all">
                    {{ __('home.view_all') }}
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
            </div>
        </div>
    </section>

    {{-- Features Section --}}
    <section class="py-24 bg-gradient-to-br from-slate-50 to-slate-100">
        <div class="container mx-auto px-4 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-slate-900 mb-4">{{ __('home.features_title') }}</h2>
                <p class="text-xl text-slate-600 max-w-2xl mx-auto">{{ __('home.features_subtitle') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                @php
                    $features = [
                        ['title' => 'home.feature_1_title', 'desc' => 'home.feature_1_desc', 'bg' => 'bg-gradient-to-br from-blue-500 to-cyan-500', 'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
                        ['title' => 'home.feature_2_title', 'desc' => 'home.feature_2_desc', 'bg' => 'bg-gradient-to-br from-green-500 to-emerald-500', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                        ['title' => 'home.feature_3_title', 'desc' => 'home.feature_3_desc', 'bg' => 'bg-gradient-to-br from-purple-500 to-violet-500', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                        ['title' => 'home.feature_4_title', 'desc' => 'home.feature_4_desc', 'bg' => 'bg-gradient-to-br from-amber-500 to-orange-500', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ];
                @endphp
                @foreach($features as $feature)
                    <div class="card-hover bg-white rounded-3xl p-8 shadow-lg text-center">
                        <div class="w-16 h-16 {{ $feature['bg'] }} rounded-2xl flex items-center justify-center mb-6 mx-auto">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $feature['icon'] }}" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">{{ __($feature['title']) }}</h3>
                        <p class="text-slate-600 leading-relaxed">{{ __($feature['desc']) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Process Section --}}
    <section class="py-24 bg-white">
        <div class="container mx-auto px-4 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-slate-900 mb-4">{{ __('home.process_title') }}</h2>
                <p class="text-xl text-slate-600 max-w-2xl mx-auto">{{ __('home.process_subtitle') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                @php
                    $steps = [
                        ['title' => 'home.process_step_1_title', 'desc' => 'home.process_step_1_desc', 'bg' => 'bg-gradient-to-r from-indigo-50 to-purple-50'],
                        ['title' => 'home.process_step_2_title', 'desc' => 'home.process_step_2_desc', 'bg' => 'bg-gradient-to-r from-purple-50 to-pink-50'],
                        ['title' => 'home.process_step_3_title', 'desc' => 'home.process_step_3_desc', 'bg' => 'bg-gradient-to-r from-pink-50 to-rose-50'],
                    ];
                @endphp
                @foreach($steps as $index => $step)
                    <div class="text-center">
                        <div class="w-20 h-20 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-3xl shadow-lg mx-auto mb-6">
                            {{ $index + 1 }}
                        </div>
                        <div class="{{ $step['bg'] }} rounded-2xl p-8">
                            <h3 class="text-xl font-bold text-slate-900 mb-3">{{ __($step['title']) }}</h3>
                            <p class="text-slate-600 leading-relaxed">{{ __($step['desc']) }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Testimonials Section --}}
    <section class="py-24 bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50">
        <div class="container mx-auto px-4 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-slate-900 mb-4">{{ __('home.testimonials_title') }}</h2>
                <p class="text-xl text-slate-600 max-w-2xl mx-auto">{{ __('home.testimonials_subtitle') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                @php
                    $testimonials = [
                        ['name' => 'home.testimonial_1_name', 'role' => 'home.testimonial_1_role', 'content' => 'home.testimonial_1_content', 'initial' => 'L', 'bg' => 'bg-gradient-to-br from-indigo-500 to-purple-600'],
                        ['name' => 'home.testimonial_2_name', 'role' => 'home.testimonial_2_role', 'content' => 'home.testimonial_2_content', 'initial' => 'M', 'bg' => 'bg-gradient-to-br from-purple-500 to-pink-600'],
                        ['name' => 'home.testimonial_3_name', 'role' => 'home.testimonial_3_role', 'content' => 'home.testimonial_3_content', 'initial' => 'H', 'bg' => 'bg-gradient-to-br from-pink-500 to-rose-600'],
                    ];
                @endphp
                @foreach($testimonials as $testimonial)
                    <div class="card-hover bg-white rounded-3xl p-8 shadow-lg">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-16 h-16 {{ $testimonial['bg'] }} rounded-full flex items-center justify-center text-white font-bold text-2xl shrink-0">
                                {{ $testimonial['initial'] }}
                            </div>
                            <div>
                                <div class="font-bold text-slate-900 text-lg">{{ __($testimonial['name']) }}</div>
                                <div class="text-sm text-slate-500">{{ __($testimonial['role']) }}</div>
                            </div>
                        </div>
                        <div class="flex gap-1 mb-4">
                            @for ($i = 0; $i < 5; $i++)
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            @endfor
                        </div>
                        <p class="text-slate-600 leading-relaxed">{{ __($testimonial['content']) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="py-24 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 gradient-animated text-white">
        <div class="container mx-auto px-4 lg:px-8 text-center">
            <h2 class="text-4xl lg:text-5xl font-bold mb-6">{{ __('home.cta_title') }}</h2>
            <p class="text-xl text-indigo-100 mb-12 max-w-2xl mx-auto">{{ __('home.cta_description') }}</p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="/services"
                    class="btn-ripple inline-flex items-center justify-center px-10 py-5 bg-white text-indigo-600 font-bold text-lg rounded-2xl shadow-2xl hover:shadow-3xl transform hover:scale-105 transition-all duration-300">
                    {{ __('home.cta_button') }}
                    <svg class="w-6 h-6 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
            </div>
            <div class="mt-8 text-lg text-indigo-100">{{ __('home.cta_phone') }}</div>
        </div>
    </section>
@endsection