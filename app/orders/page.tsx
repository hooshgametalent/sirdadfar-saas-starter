import Link from "next/link";
import { AppShell } from "@/components/layout/app-shell";
import { ShipmentTable } from "@/components/dashboard/shipment-table";

export default function OrdersPage() {
  return (
    <AppShell
      title="سفارش‌های حمل"
      description="تمام درخواست‌ها را بررسی کنید، راننده تخصیص دهید و وضعیت حمل را پیگیری کنید."
    >
      <div className="toolbar">
        <div className="search-box">
          ⌕<input aria-label="جستجوی سفارش" placeholder="جستجو با شماره سفارش، نام یا مسیر" />
        </div>
        <div className="filter-group">
          <button className="filter-button" type="button">
            همه وضعیت‌ها⌄
          </button>
          <button className="filter-button" type="button">
            همه خودروها⌄
          </button>
        </div>
        <Link className="primary-button" href="/orders/new">
          ＋ ثبت درخواست حمل
        </Link>
      </div>
      <div className="orders-summary">
        <span>
          نمایش <strong>۴</strong> سفارش از مجموع <strong>۱۲۸</strong> سفارش فعال
        </span>
        <button className="text-link" type="button">
          خروجی اکسل
        </button>
      </div>
      <ShipmentTable />
    </AppShell>
  );
}
