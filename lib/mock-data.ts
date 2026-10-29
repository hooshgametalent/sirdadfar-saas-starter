export type ShipmentStatus = "در انتظار بررسی" | "پیشنهاد فعال" | "تخصیص یافته" | "در مسیر" | "تحویل شده";

export type Shipment = {
    id: string;
    reference: string;
    route: string;
    cargo: string;
    vehicle: string;
    customer: string;
    pickupDate: string;
    price: string;
    status: ShipmentStatus;
};

export const shipments: Shipment[] = [
    { id: "1", reference: "BAR-24085", route: "تهران ← اصفهان", cargo: "مصالح ساختمانی · ۸ تن", vehicle: "کامیون تک", customer: "شرکت عمران سپهر", pickupDate: "۱۴ آبان ۱۴۰۵", price: "۲۸,۵۰۰,۰۰۰ تومان", status: "پیشنهاد فعال" },
    { id: "2", reference: "BAR-24084", route: "کرج ← رشت", cargo: "لوازم خانگی · ۲ تن", vehicle: "خاور مسقف", customer: "فاطمه احمدی", pickupDate: "۱۳ آبان ۱۴۰۵", price: "۱۲,۸۰۰,۰۰۰ تومان", status: "تخصیص یافته" },
    { id: "3", reference: "BAR-24083", route: "بندرعباس ← تهران", cargo: "کالای تجاری · ۱۸ تن", vehicle: "تریلی چادری", customer: "بازرگانی پارس", pickupDate: "۱۲ آبان ۱۴۰۵", price: "۶۴,۰۰۰,۰۰۰ تومان", status: "در مسیر" },
    { id: "4", reference: "BAR-24082", route: "مشهد ← نیشابور", cargo: "مواد غذایی · ۱ تن", vehicle: "نیسان", customer: "فروشگاه زنجیره‌ای مهر", pickupDate: "۱۱ آبان ۱۴۰۵", price: "۵,۴۰۰,۰۰۰ تومان", status: "تحویل شده" },
];

export const drivers = [
    { name: "علی رضایی", vehicle: "تریلی چادری · ۲۴ تن", city: "تهران", score: "۴.۹", trips: 86, status: "فعال" },
    { name: "محمد کریمی", vehicle: "کامیون تک · ۱۰ تن", city: "اصفهان", score: "۴.۸", trips: 54, status: "فعال" },
    { name: "حسین امینی", vehicle: "نیسان · ۲ تن", city: "کرج", score: "۴.۷", trips: 38, status: "در انتظار تأیید" },
];

export const reviews = [
    { name: "شرکت بازرگانی پارس", score: "۵", text: "پیگیری دقیق و تحویل بار در زمان توافق‌شده بود. راننده و پشتیبانی همکاری بسیار خوبی داشتند.", route: "بندرعباس به تهران", date: "۱۰ آبان ۱۴۰۵", published: true },
    { name: "زهرا محمدی", score: "۵", text: "برای جابه‌جایی لوازم منزل از نیسان استفاده کردم. روند ثبت سفارش ساده و راننده خوش‌قول بود.", route: "تهران به کرج", date: "۹ آبان ۱۴۰۵", published: true },
    { name: "صنایع آریا", score: "۴", text: "بار سالم تحویل شد. خواهشمندیم زمان اطلاع‌رسانی برای حرکت خودرو دقیق‌تر شود.", route: "قم به اصفهان", date: "۸ آبان ۱۴۰۵", published: false },
];

export const articleIdeas = [
    { title: "راهنمای انتخاب خودرو برای حمل بار تهران به اصفهان", source: "داده مسیرهای پرتکرار", state: "پیش‌نویس" },
    { title: "برای بار ۲ تنی نیسان مناسب‌تر است یا خاور؟", source: "سفارش‌ها و پرسش‌های مشتریان", state: "نیازمند بازبینی" },
    { title: "تجربه موفق حمل کالای تجاری از بندرعباس به تهران", source: "رضایت مشتری با مجوز انتشار", state: "آماده انتشار" },
];
