import { AppShell } from "@/components/layout/app-shell";
import { MetricCard } from "@/components/dashboard/metric-card";
import { ShipmentTable } from "@/components/dashboard/shipment-table";

export default function HomePage() {
  return (
    <AppShell
      title="سلام مریم، روز خوبی داشته باشید"
      description="در یک نگاه، وضعیت عملیات حمل‌ونقل امروز را دنبال کنید."
    >
      <section className="metrics-grid">
        <MetricCard
          label="سفارش‌های فعال"
          value="۱۲۸"
          change="۱۲٪ نسبت به ماه قبل"
          icon="▤"
          tone="blue"
        />
        <MetricCard
          label="رانندگان فعال"
          value="۴۸۶"
          change="۸٪ نسبت به ماه قبل"
          icon="♧"
          tone="purple"
        />
        <MetricCard
          label="درآمد این ماه"
          value="۴۸۶ میلیون"
          change="۱۸٪ نسبت به ماه قبل"
          icon="⌁"
          tone="green"
        />
        <MetricCard
          label="رضایت مشتریان"
          value="۴.۸ از ۵"
          change="بر اساس ۲۳۶ نظر"
          icon="★"
          tone="orange"
        />
      </section>
      <section className="dashboard-grid">
        <ShipmentTable compact />
        <aside className="panel activity-panel">
          <div className="panel-heading">
            <div>
              <h2>فعالیت‌های امروز</h2>
              <p>آخرین رخدادهای عملیاتی</p>
            </div>
          </div>
          <ul className="activity-list">
            <li>
              <span className="activity-icon blue">+</span>
              <div>
                <strong>درخواست جدید ثبت شد</strong>
                <small>بار تهران به اصفهان · ۸ تن</small>
              </div>
              <time>۱۰ دقیقه پیش</time>
            </li>
            <li>
              <span className="activity-icon purple">♧</span>
              <div>
                <strong>راننده جدید تأیید شد</strong>
                <small>حسین امینی · نیسان</small>
              </div>
              <time>۳۵ دقیقه پیش</time>
            </li>
            <li>
              <span className="activity-icon green">✓</span>
              <div>
                <strong>بار با موفقیت تحویل شد</strong>
                <small>BAR-24082 · مشهد به نیشابور</small>
              </div>
              <time>۱ ساعت پیش</time>
            </li>
            <li>
              <span className="activity-icon orange">★</span>
              <div>
                <strong>نظر مشتری ثبت شد</strong>
                <small>امتیاز ۵ از ۵ برای سفارش BAR-24080</small>
              </div>
              <time>۲ ساعت پیش</time>
            </li>
          </ul>
        </aside>
      </section>
    </AppShell>
  );
}
