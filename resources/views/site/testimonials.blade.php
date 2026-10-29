@extends('layouts.app', [
    'title' => 'نظرات مشتریان',
    'description' => 'تجربه صاحبان بار از حمل بار با رانندگان تأییدشده بارراه.',
    'canonical' => route('testimonials', $reviews->currentPage() > 1 ? ['page' => $reviews->currentPage()] : []),
])

@section('content')
    <h1 class="text-2xl font-bold">نظرات مشتریان</h1>
    <p class="mt-2 text-sm text-slate-600">فقط نظراتی نمایش داده می‌شوند که صاحب بار با انتشار آن موافقت کرده و پشتیبانی آن را تأیید کرده است.</p>

    @if ($reviews->isEmpty())
        <p class="mt-6 text-slate-600">هنوز نظری منتشر نشده است.</p>
    @else
        <div class="mt-6 grid gap-4 md:grid-cols-3">
            @foreach ($reviews as $review)
                @include('site.partials.review', ['review' => $review])
            @endforeach
        </div>

        <div class="mt-8">{{ $reviews->links() }}</div>
    @endif
@endsection
