import { AppShell } from "@/components/layout/app-shell";
import { drivers } from "@/lib/mock-data";

export default function DriversPage() {
  return (
    <AppShell
      title="رانندگان و ناوگان"
      description="مدارک، خودروها و عملکرد رانندگان همکار را مدیریت کنید."
    >
      <section className="metrics-grid compact-metrics">
        <article className="mini-metric">
          <span>رانندگان تأییدشده</span>
          <strong>۴۸۶</strong>
        </article>
        <article className="mini-metric">
          <span>در انتظار بررسی مدارک</span>
          <strong>۱۲</strong>
        </article>
        <article className="mini-metric">
          <span>خودروهای فعال</span>
          <strong>۵۷۱</strong>
        </article>
      </section>
      <section className="panel table-panel">
        <div className="panel-heading">
          <div>
            <h2>رانندگان اخیر</h2>
            <p>مدیریت وضعیت فعالیت و اطلاعات ناوگان</p>
          </div>
          <button className="primary-button" type="button">
            ＋ افزودن راننده
          </button>
        </div>
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>راننده</th>
                <th>خودرو</th>
                <th>شهر فعالیت</th>
                <th>امتیاز</th>
                <th>سفرهای موفق</th>
                <th>وضعیت</th>
              </tr>
            </thead>
            <tbody>
              {drivers.map((driver) => (
                <tr key={driver.name}>
                  <td>
                    <span className="avatar small">{driver.name[0]}</span>
                    <strong>{driver.name}</strong>
                  </td>
                  <td>{driver.vehicle}</td>
                  <td>{driver.city}</td>
                  <td className="score">★ {driver.score}</td>
                  <td>{driver.trips} سفر</td>
                  <td>
                    <span
                      className={`status-pill ${driver.status === "فعال" ? "success" : "warning"}`}
                    >
                      {driver.status}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </AppShell>
  );
}
