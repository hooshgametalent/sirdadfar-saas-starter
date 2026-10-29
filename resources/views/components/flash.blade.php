@props(['success' => null, 'error' => null, 'fromSession' => true])

@php
    $successMessage = $success ?? ($fromSession ? session('status') : null);
    $errorMessage = $error ?? ($fromSession ? session('error') : null);
@endphp

<div {{ $attributes->class(['space-y-3']) }}>
    <div role="status" aria-live="polite">
        @if (filled($successMessage))
            <p class="mb-4 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ $successMessage }}</p>
        @endif
    </div>
    <div role="alert" aria-live="assertive">
        @if (filled($errorMessage))
            <p class="mb-4 rounded-lg border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-900">{{ $errorMessage }}</p>
        @endif
    </div>
</div>
