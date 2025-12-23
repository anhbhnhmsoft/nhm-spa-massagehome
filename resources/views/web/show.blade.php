@extends('layouts.spa')

@section('meta_title', $page->meta_title ?? $page->title)
@section('meta_description', $page->meta_description)
@section('meta_keywords', $page->meta_keywords)
@section('og_image', $page->og_image)

@section('header_title', $page->title)

@section('content')
    <section class="py-24 bg-white">
        <div class="container mx-auto px-4 lg:px-8">
    <div class="px-6 py-4 animate-fade-in">
        @if ($page->og_image)
            <div class="relative w-full h-56 mb-8 rounded-3xl overflow-hidden shadow-xl shadow-indigo-100/50">
                <img src="{{ $page->og_image }}" alt="{{ $page->title }}" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-linear-to-t from-black/20 to-transparent"></div>
            </div>
        @endif

        <article class="prose prose-slate prose-indigo max-w-none">
            {!! $page->content !!}
        </article>
    </div>
    </div>
    </section>

    <style>
        @keyframes fadeInUp {   
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Premium Typography styling for content */
        .prose h2 {
            font-weight: 800;
            font-size: 1.75rem;
            letter-spacing: -0.025em;
            color: #1e293b;
            margin-top: 2rem;
        }

        .prose p {
            line-height: 1.8;
            color: #475569;
            font-weight: 400;
            font-size: 1.05rem;
        }
    </style>
@endsection
