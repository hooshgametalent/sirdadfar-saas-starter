@extends('layouts.app', [
    'title' => 'تماس با ما',
    'description' => 'راه‌های ارتباط با پشتیبانی بارراه برای صاحبان بار و رانندگان.',
    'canonical' => route('contact'),
])

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <h1 class="text-2xl font-bold">تماس با ما</h1>
        <p class="leading-8 text-slate-700">کارشناسان بارراه برای پاسخ به پرسش‌های صاحبان بار و رانندگان در دسترس هستند.</p>

        <dl class="card grid gap-4 text-sm sm:grid-cols-2">
            @if ($phone)
                <div>
                    <dt class="text-slate-500">تلفن پشتیبانی</dt>
                    <dd class="mt-1" dir="ltr"><a href="tel:{{ $phone }}" class="link">{{ $phone }}</a></dd>
                </div>
            @endif
            @if ($email)
                <div>
                    <dt class="text-slate-500">ایمیل</dt>
                    <dd class="mt-1" dir="ltr"><a href="mailto:{{ $email }}" class="link">{{ $email }}</a></dd>
                </div>
            @endif
            @if ($address)
                <div class="sm:col-span-2">
                    <dt class="text-slate-500">نشانی</dt>
                    <dd class="mt-1">{{ $address }}</dd>
                </div>
            @endif
            @if (! $phone && ! $email && ! $address)
                <div class="sm:col-span-2">
                    <dt class="text-slate-500">پشتیبانی</dt>
                    <dd class="mt-1">اطلاعات تماس به زودی در این صفحه قرار می‌گیرد. کاربران ثبت‌نام‌شده می‌توانند از طریق پنل کاربری پیگیری کنند.</dd>
                </div>
            @endif
        </dl>

        <p class="text-sm text-slate-600">
            پاسخ بسیاری از پرسش‌ها را در صفحه <a href="{{ route('faq') }}" class="link">سوالات متداول</a> پیدا می‌کنید.
        </p>
    </div>
@endsection
