import type { ReactNode } from "react";

export function MetricCard({ label, value, change, icon, tone = "blue" }: { label: string; value: string; change: string; icon: ReactNode; tone?: "blue" | "purple" | "green" | "orange" }) {
  return <article className="metric-card">
    <div className={`metric-icon ${tone}`}>{icon}</div>
    <div className="metric-content"><p>{label}</p><strong>{value}</strong><span className="metric-change">↗ {change}</span></div>
  </article>;
}
