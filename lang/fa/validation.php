<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Persian error messages used by the validator.
    |
    */

    'accepted' => ':attribute باید پذیرفته شود.',
    'active_url' => ':attribute یک آدرس معتبر نیست.',
    'after' => ':attribute باید تاریخی بعد از :date باشد.',
    'after_or_equal' => ':attribute باید تاریخی برابر یا بعد از :date باشد.',
    'alpha' => ':attribute فقط می‌تواند شامل حروف باشد.',
    'alpha_dash' => ':attribute فقط می‌تواند شامل حروف، اعداد، خط تیره و زیرخط باشد.',
    'alpha_num' => ':attribute فقط می‌تواند شامل حروف و اعداد باشد.',
    'array' => ':attribute باید یک آرایه باشد.',
    'before' => ':attribute باید تاریخی قبل از :date باشد.',
    'before_or_equal' => ':attribute باید تاریخی برابر یا قبل از :date باشد.',
    'between' => [
        'array' => ':attribute باید بین :min و :max مورد باشد.',
        'file' => ':attribute باید بین :min و :max کیلوبایت باشد.',
        'numeric' => ':attribute باید بین :min و :max باشد.',
        'string' => ':attribute باید بین :min و :max کاراکتر باشد.',
    ],
    'boolean' => ':attribute باید درست یا نادرست باشد.',
    'confirmed' => 'تکرار :attribute با مقدار آن مطابقت ندارد.',
    'current_password' => 'رمز عبور فعلی اشتباه است.',
    'date' => ':attribute یک تاریخ معتبر نیست.',
    'date_format' => ':attribute با قالب :format مطابقت ندارد.',
    'decimal' => ':attribute باید :decimal رقم اعشار داشته باشد.',
    'different' => ':attribute و :other باید متفاوت باشند.',
    'digits' => ':attribute باید :digits رقم باشد.',
    'digits_between' => ':attribute باید بین :min و :max رقم باشد.',
    'distinct' => ':attribute مقدار تکراری دارد.',
    'email' => ':attribute باید یک ایمیل معتبر باشد.',
    'enum' => ':attribute انتخاب شده معتبر نیست.',
    'exists' => ':attribute انتخاب شده معتبر نیست.',
    'file' => ':attribute باید یک فایل باشد.',
    'filled' => ':attribute باید مقدار داشته باشد.',
    'gt' => [
        'array' => ':attribute باید بیش از :value مورد داشته باشد.',
        'file' => ':attribute باید بزرگ‌تر از :value کیلوبایت باشد.',
        'numeric' => ':attribute باید بزرگ‌تر از :value باشد.',
        'string' => ':attribute باید بیش از :value کاراکتر باشد.',
    ],
    'gte' => [
        'array' => ':attribute باید :value مورد یا بیشتر داشته باشد.',
        'file' => ':attribute باید بزرگ‌تر یا برابر :value کیلوبایت باشد.',
        'numeric' => ':attribute باید بزرگ‌تر یا برابر :value باشد.',
        'string' => ':attribute باید :value کاراکتر یا بیشتر باشد.',
    ],
    'image' => ':attribute باید یک تصویر باشد.',
    'in' => ':attribute انتخاب شده معتبر نیست.',
    'integer' => ':attribute باید یک عدد صحیح باشد.',
    'lt' => [
        'array' => ':attribute باید کمتر از :value مورد داشته باشد.',
        'file' => ':attribute باید کوچک‌تر از :value کیلوبایت باشد.',
        'numeric' => ':attribute باید کوچک‌تر از :value باشد.',
        'string' => ':attribute باید کمتر از :value کاراکتر باشد.',
    ],
    'lte' => [
        'array' => ':attribute نباید بیش از :value مورد داشته باشد.',
        'file' => ':attribute باید کوچک‌تر یا برابر :value کیلوبایت باشد.',
        'numeric' => ':attribute باید کوچک‌تر یا برابر :value باشد.',
        'string' => ':attribute باید :value کاراکتر یا کمتر باشد.',
    ],
    'max' => [
        'array' => ':attribute نباید بیش از :max مورد داشته باشد.',
        'file' => ':attribute نباید بزرگ‌تر از :max کیلوبایت باشد.',
        'numeric' => ':attribute نباید بزرگ‌تر از :max باشد.',
        'string' => ':attribute نباید بیش از :max کاراکتر باشد.',
    ],
    'mimes' => ':attribute باید یکی از قالب‌های :values باشد.',
    'mimetypes' => ':attribute باید یکی از قالب‌های :values باشد.',
    'min' => [
        'array' => ':attribute باید حداقل :min مورد داشته باشد.',
        'file' => ':attribute باید حداقل :min کیلوبایت باشد.',
        'numeric' => ':attribute باید حداقل :min باشد.',
        'string' => ':attribute باید حداقل :min کاراکتر باشد.',
    ],
    'not_in' => ':attribute انتخاب شده معتبر نیست.',
    'not_regex' => 'قالب :attribute معتبر نیست.',
    'numeric' => ':attribute باید یک عدد باشد.',
    'password' => [
        'letters' => ':attribute باید حداقل یک حرف داشته باشد.',
        'mixed' => ':attribute باید حداقل یک حرف بزرگ و یک حرف کوچک داشته باشد.',
        'numbers' => ':attribute باید حداقل یک عدد داشته باشد.',
        'symbols' => ':attribute باید حداقل یک نماد داشته باشد.',
        'uncompromised' => ':attribute در نشت اطلاعات دیده شده است. لطفا :attribute دیگری انتخاب کنید.',
    ],
    'present' => ':attribute باید وجود داشته باشد.',
    'prohibited' => 'وارد کردن :attribute مجاز نیست.',
    'regex' => 'قالب :attribute معتبر نیست.',
    'required' => 'وارد کردن :attribute الزامی است.',
    'required_if' => 'وقتی :other برابر :value است، وارد کردن :attribute الزامی است.',
    'required_unless' => 'وارد کردن :attribute الزامی است مگر اینکه :other در :values باشد.',
    'required_with' => 'وقتی :values وارد شده، وارد کردن :attribute الزامی است.',
    'required_without' => 'وقتی :values وارد نشده، وارد کردن :attribute الزامی است.',
    'same' => ':attribute و :other باید یکسان باشند.',
    'size' => [
        'array' => ':attribute باید شامل :size مورد باشد.',
        'file' => ':attribute باید :size کیلوبایت باشد.',
        'numeric' => ':attribute باید برابر :size باشد.',
        'string' => ':attribute باید :size کاراکتر باشد.',
    ],
    'string' => ':attribute باید متن باشد.',
    'timezone' => ':attribute باید یک منطقه زمانی معتبر باشد.',
    'unique' => ':attribute قبلا ثبت شده است.',
    'uploaded' => 'بارگذاری :attribute ناموفق بود.',
    'url' => ':attribute باید یک آدرس معتبر باشد.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'mobile' => [
            'regex' => 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'name' => 'نام',
        'mobile' => 'شماره موبایل',
        'email' => 'ایمیل',
        'password' => 'رمز عبور',
        'password_confirmation' => 'تکرار رمز عبور',
        'current_password' => 'رمز عبور فعلی',
        'role' => 'نقش',
        'company_name' => 'نام شرکت',
        'origin' => 'مبدا',
        'destination' => 'مقصد',
        'origin_city_id' => 'شهر مبدا',
        'destination_city_id' => 'شهر مقصد',
        'cargo_type' => 'نوع بار',
        'weight' => 'وزن',
        'weight_kg' => 'وزن (کیلوگرم)',
        'vehicle_type' => 'نوع خودرو',
        'loading_date' => 'تاریخ بارگیری',
        'price' => 'قیمت',
        'amount' => 'مبلغ',
        'description' => 'توضیحات',
        'plate_number' => 'شماره پلاک',
        'national_code' => 'کد ملی',
        'document' => 'مدرک',
        'file' => 'فایل',
        'image' => 'تصویر',
        'title' => 'عنوان',
        'body' => 'متن',
        'message' => 'پیام',
        'terms' => 'قوانین و مقررات',
        'origin_province_id' => 'استان مبدأ',
        'destination_province_id' => 'استان مقصد',
        'origin_address' => 'نشانی مبدأ',
        'destination_address' => 'نشانی مقصد',
        'volume_m3' => 'حجم',
        'pickup_date' => 'تاریخ بارگیری',
        'pickup_window_start' => 'ساعت شروع بارگیری',
        'pickup_window_end' => 'ساعت پایان بارگیری',
        'vehicle_type_id' => 'نوع خودرو',
        'special_requirements' => 'شرایط ویژه',
        'special_requirements.*' => 'شرایط ویژه',
        'offered_price' => 'قیمت پیشنهادی',
        'notes' => 'توضیحات',
        'cancelReason' => 'دلیل لغو',
        'rating' => 'امتیاز',
        'reviewBody' => 'متن نظر',
        'displayName' => 'نام نمایشی',
        'publishConsent' => 'رضایت انتشار',
        'vehicle_model' => 'مدل خودرو',
        'capacity_kg' => 'ظرفیت',
        'document_type' => 'نوع مدرک',
        'offerPrice' => 'مبلغ پیشنهادی',
        'offerMessage' => 'توضیح پیشنهاد',
        'offerOrderId' => 'سفارش',
    ],

];
