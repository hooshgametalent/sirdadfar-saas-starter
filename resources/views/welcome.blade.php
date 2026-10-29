@extends('layouts.app', ['title' => 'خانه'])

@section('content')
    <section aria-labelledby="welcome-heading" class="rounded-lg bg-white p-8 shadow-sm">
        <h1 id="welcome-heading" class="text-2xl font-bold">به {{ config('app.name') }} خوش آمدید</h1>
        <p class="mt-4 leading-8 text-slate-700">
            پلتفرم مدیریت حمل‌ونقل جاده‌ای بار، ساخته‌شده با لاراول و لایووایر و کاملا روی سرور خود پروژه اجرا می‌شود.
        </p>
    </section>
@endsection
