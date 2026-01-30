@extends('layouts.spa')

@section('meta_title', __('home.meta_title'))
@section('meta_description', __('home.meta_description'))
@section('meta_keywords', __('home.meta_keywords'))
@section('og_image', __('home.og_image'))

@section('header_title', __('home.header_title'))

@section('content')
<style>
    body {
        background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 50%, #90CAF9 100%);
        min-height: 100vh;
    }

    .language-switcher {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .language-switcher a {
        padding: 0.5rem 1rem;
        color: #1976D2;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        border-radius: 0.5rem;
    }

    .language-switcher a:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    .language-switcher a.active {
        background: white;
        color: #1565C0;
    }

    .download-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 2rem;
        background: white;
        color: #1565C0;
        font-weight: 600;
        border-radius: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .download-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    .download-btn svg {
        width: 2rem;
        height: 2rem;
    }

    .hero-content {
        text-align: center;
        padding: 4rem 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .brand-logo {
        width: 120px;
        height: 120px;
        margin: 0 auto 2rem;
    }

    .brand-name {
        font-size: 3rem;
        font-weight: 800;
        color: #1565C0;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
    }

    .tagline {
        font-size: 1.5rem;
        color: #1976D2;
        margin-bottom: 3rem;
        font-weight: 500;
    }

    .download-section {
        display: flex;
        gap: 1.5rem;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 3rem;
    }
</style>


{{-- Hero Section --}}
<section class="hero-content">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;">
        <div class="">
            <img src="{{ asset('images/logo.png') }}" alt="Masa Home Logo" style="width: 100%; height: 100%; object-fit: contain; max-width: 120px;">
        </div>
        <div class="language-switcher">
            <a href="{{ route('locale.switch', 'vi') }}" class="{{ app()->getLocale() == 'vi' ? 'active' : '' }}">VI</a>
            <span style="color: #1976D2;">|</span>
            <a href="{{ route('locale.switch', 'en') }}" class="{{ app()->getLocale() == 'en' ? 'active' : '' }}">EN</a>
            <span style="color: #1976D2;">|</span>
            <a href="{{ route('locale.switch', 'zh') }}" class="{{ app()->getLocale() == 'zh' ? 'active' : '' }}">中文</a>
        </div>
    </div>
</div>

    {{-- Tagline --}}
    <p class="tagline">{{ __('home.hero_tagline') }}</p>

    {{-- Description --}}
    <p style="font-size: 1.25rem; color: #424242; max-width: 800px; margin: 0 auto 3rem; line-height: 1.8;">
        {{ __('home.hero_description') }}
    </p>

    {{-- Download Buttons --}}
    <div class="download-section">
        {{-- Android Download --}}
        <a href="/app.apk" class="download-btn">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.523 15.341c-.538 0-.988.196-1.348.588-.36.392-.54.863-.54 1.413 0 .55.18 1.02.54 1.412.36.392.81.588 1.348.588s.988-.196 1.348-.588c.36-.392.54-.862.54-1.412s-.18-1.021-.54-1.413c-.36-.392-.81-.588-1.348-.588zm-11.046 0c-.538 0-.988.196-1.348.588-.36.392-.54.863-.54 1.413 0 .55.18 1.02.54 1.412.36.392.81.588 1.348.588s.988-.196 1.348-.588c.36-.392.54-.862.54-1.412s-.18-1.021-.54-1.413c-.36-.392-.81-.588-1.348-.588zM12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" />
            </svg>
            <div style="text-align: left;">
                <div style="font-size: 0.75rem; color: #666;">{{ __('home.download_on') }}</div>
                <div style="font-size: 1.125rem;">Google Play</div>
            </div>
        </a>

        {{-- iOS Download --}}
        <a href="https://apps.apple.com/vn/app/masa-home/id6756880834?l=vi" class="download-btn">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z" />
            </svg>
            <div style="text-align: left;">
                <div style="font-size: 0.75rem; color: #666;">{{ __('home.download_on') }}</div>
                <div style="font-size: 1.125rem;">App Store</div>
            </div>
        </a>
    </div>

    {{-- Features Grid --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-top: 5rem; max-width: 900px; margin-left: auto; margin-right: auto;">
        @php
        $features = [
        [
        'icon' => 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z',
        'title' => __('home.feature_professional'),
        ],
        [
        'icon' => 'M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z',
        'title' => __('home.feature_convenient'),
        ],
        [
        'icon' => 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91s4.18 1.39 4.18 3.91c-.01 1.83-1.38 2.83-3.12 3.16z',
        'title' => __('home.feature_affordable'),
        ],
        ];
        @endphp

        @foreach($features as $feature)
        <div style="background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); text-align: center;">
            <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #1976D2, #42A5F5); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                <svg style="width: 32px; height: 32px; color: white;" viewBox="0 0 24 24" fill="currentColor">
                    <path d="{{ $feature['icon'] }}" />
                </svg>
            </div>
            <h3 style="font-size: 1.25rem; font-weight: 700; color: #1565C0;">{{ $feature['title'] }}</h3>
        </div>
        @endforeach
    </div>
</section>
@endsection
