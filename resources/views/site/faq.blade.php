@extends('layouts.app', [
    'title' => 'سوالات متداول',
    'description' => 'پاسخ پرسش‌های رایج درباره ثبت سفارش حمل بار، قیمت‌گذاری، تأیید رانندگان و تسویه حساب در بارراه.',
    'canonical' => route('faq'),
])

@section('content')
    <div class="mx-auto max-w-3xl">
        <h1 class="text-2xl font-bold">سوالات متداول</h1>

        @if ($faqs->isEmpty())
            <p class="mt-6 text-slate-600">هنوز پرسشی ثبت نشده است.</p>
        @else
            <div class="mt-6 space-y-3">
                @foreach ($faqs as $faq)
                    <details class="card group">
                        <summary class="cursor-pointer list-none font-bold focus-visible:outline-2 focus-visible:outline-teal-600">
                            <span aria-hidden="true" class="me-2 inline-block transition group-open:rotate-90">‹</span>
                            {{ $faq->question }}
                        </summary>
                        <p class="mt-3 leading-8 text-slate-700">{{ $faq->answer }}</p>
                    </details>
                @endforeach
            </div>
        @endif
    </div>
@endsection
