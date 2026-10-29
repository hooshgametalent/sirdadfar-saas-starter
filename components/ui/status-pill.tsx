import type { ShipmentStatus } from "@/lib/mock-data";

const statusClass: Record<ShipmentStatus, string> = {
  "در انتظار بررسی": "neutral",
  "پیشنهاد فعال": "info",
  "تخصیص یافته": "warning",
  "در مسیر": "purple",
  "تحویل شده": "success",
};

export function StatusPill({ status }: { status: ShipmentStatus }) {
  return <span className={`status-pill ${statusClass[status]}`}>{status}</span>;
}
