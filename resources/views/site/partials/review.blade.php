<figure class="card">
    <p class="text-amber-500" aria-label="امتیاز {{ \App\Support\Jalali::toPersianDigits($review->rating) }} از ۵">
        <span aria-hidden="true">{{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', 5 - (int) $review->rating) }}</span>
    </p>
    <blockquote class="mt-3 text-sm leading-7 text-slate-700">{{ $review->body }}</blockquote>
    <figcaption class="mt-3 text-sm font-bold">{{ $review->display_name }}</figcaption>
    @if ($review->admin_reply)
        <p class="mt-3 rounded-lg bg-slate-50 p-3 text-xs leading-6 text-slate-600">
            <strong>پاسخ بارراه:</strong> {{ $review->admin_reply }}
        </p>
    @endif
</figure>
