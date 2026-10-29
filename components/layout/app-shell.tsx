import type { ReactNode } from "react";
import { Sidebar } from "@/components/layout/sidebar";
import { Topbar } from "@/components/layout/topbar";

export function AppShell({
  children,
  title,
  description,
}: {
  children: ReactNode;
  title: string;
  description: string;
}) {
  return (
    <div className="app-shell">
      <Sidebar />
      <div className="main-area">
        <Topbar />
        <main className="page-content">
          <div className="page-heading">
            <div>
              <p className="eyebrow">پنل عملیات بارراه</p>
              <h1>{title}</h1>
              <p className="muted">{description}</p>
            </div>
          </div>
          {children}
        </main>
      </div>
    </div>
  );
}
