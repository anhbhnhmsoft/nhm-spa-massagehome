@extends('layouts.spa')

@section('meta_title', __('home.meta_title'))
@section('meta_description', __('home.meta_description'))
@section('meta_keywords', __('home.meta_keywords'))
@section('og_image', __('home.og_image'))

@section('header_title', __('home.header_title'))

@section('content')
<div class="flex flex-col items-center justify-center min-h-screen bg-gray-50 text-gray-800">
    <div class="bg-white p-8 rounded-2xl shadow-xl max-w-sm w-full text-center border border-gray-100">
        <h1 class="text-2xl font-bold mb-4 text-gray-900">@lang('affiliate_view.download_app')</h1>
        <p class="mb-6 text-gray-600 text-sm">@lang('affiliate_view.scan_instruction')</p>

        <div class="mb-6 flex justify-center">
            <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-inner">
                <img src="/images/logo.png" alt="" />
            </div>
        </div>

        <div class="flex justify-between gap-2">
            <div
                class="text-xs text-gray-400 break-all bg-gray-50 p-3 rounded-lg border border-gray-200 group-hover:bg-gray-100">
                <a href="{{ $chplay }}">
                    <img src="/images/ggplay1.png" class="group-hover:scale-110 transition-all duration-300"
                        alt="">
                </a>
            </div>
            <div
                class="text-xs text-gray-400 break-all bg-gray-50 p-3 rounded-lg border border-gray-200 group-hover:bg-gray-100">
                <a href="{{ $appstore }}">
                    <img src="/images/appstore1.png" class="group-hover:scale-110 transition-all duration-300"
                        alt="">
                </a>
            </div>
        </div>
    </div>
</div>
@endsection