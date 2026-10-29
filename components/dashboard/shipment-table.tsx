import Link from "next/link";
import { shipments } from "@/lib/mock-data";
import { StatusPill } from "@/components/ui/status-pill";

export function ShipmentTable({ compact = false }: { compact?: boolean }) {
  const data = compact ? shipments.slice(0, 4) : shipments;
  return <section className="panel table-panel">
    <div className="panel-heading"><div><h2>{compact ? "آخرین درخواست‌های حمل" : "فهرست سفارش‌ها"}</h2><p>{compact ? "سفارش‌های ثبت‌شده در چند روز اخیر" : "پیگیری، تأیید و تخصیص سفارش‌های حمل"}</p></div>{compact && <Link className="text-link" href="/orders">مشاهده همه ←</Link>}</div>
    <div className="table-wrap"><table><thead><tr><th>شناسه</th><th>مسیر و بار</th><th>خودرو</th><th>صاحب بار</th><th>مبلغ پیشنهادی</th><th>وضعیت</th></tr></thead><tbody>{data.map((shipment) => <tr key={shipment.id}><td><strong>{shipment.reference}</strong><small>{shipment.pickupDate}</small></td><td><strong>{shipment.route}</strong><small>{shipment.cargo}</small></td><td>{shipment.vehicle}</td><td>{shipment.customer}</td><td className="price">{shipment.price}</td><td><StatusPill status={shipment.status} /></td></tr>)}</tbody></table></div>
  </section>;
}
