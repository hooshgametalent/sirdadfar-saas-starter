import { AppShell } from "@/components/layout/app-shell";

const settings = [
  ["نقش‌ها و سطح دسترسی", "تعریف دسترسی مدیر، اپراتور، صاحب بار، راننده و تولیدکننده محتوا", "مدیریت نقش‌ها"],
  ["انواع خودرو", "مدیریت ظرفیت، ابعاد بارگیر و شرایط سرویس‌دهی", "مدیریت خودروها"],
  ["قوانین قیمت‌گذاری", "نرخ پایه، کمیسیون، تخفیف و هزینه‌های خدمات ویژه", "تنظیم قیمت‌ها"],
  ["مدارک رانندگان", "مدارک موردنیاز، اعتبارسنجی و دوره نگهداری امن فایل‌ها", "تنظیم مدارک"],
  ["اعلان‌ها", "الگوهای پیامک و اعلان‌های وضعیت سفارش", "مدیریت اعلان‌ها"],
];
export default function AdminPage() {
 return <AppShell title="مدیریت و تنظیمات" description="تنظیمات عملیاتی، دسترسی کاربران و استانداردهای سرویس را مدیریت کنید."><section className="panel settings-panel"><div className="panel-heading"><div><h2>تنظیمات پلتفرم</h2><p>تغییرات این بخش در نسخه عملیاتی باید در گزارش رویدادها ثبت شود.</p></div></div><div className="settings-list">{settings.map(([title, description, action]) => <div className="setting-row" key={title}><span className="setting-icon">⚙</span><div><strong>{title}</strong><p>{description}</p></div><button className="secondary-button" type="button">{action}</button></div>)}</div></section></AppShell>;
}
