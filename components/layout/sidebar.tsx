"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

const navItems = [
  { href: "/", label: "داشبورد", icon: "▦" },
  { href: "/orders", label: "سفارش‌های حمل", icon: "▤" },
  { href: "/drivers", label: "رانندگان و ناوگان", icon: "♧" },
  { href: "/content", label: "محتوا و رضایت‌ها", icon: "✦" },
  { href: "/admin", label: "مدیریت و تنظیمات", icon: "⚙" },
];

export function Sidebar() {
  const pathname = usePathname();
  return (
    <aside className="sidebar">
      <Link className="brand" href="/">
        <span className="brand-mark">ب</span>
        <span><strong>بارراه</strong><small>مدیریت هوشمند حمل بار</small></span>
      </Link>
      <nav aria-label="ناوبری اصلی">
        {navItems.map((item) => {
          const active = item.href === "/" ? pathname === "/" : pathname.startsWith(item.href);
          return <Link className={`nav-item ${active ? "active" : ""}`} href={item.href} key={item.href}><span>{item.icon}</span>{item.label}</Link>;
        })}
      </nav>
      <div className="sidebar-support">
        <span className="support-icon">؟</span>
        <div><strong>نیاز به پشتیبانی دارید؟</strong><small>کارشناسان بارراه پاسخ‌گوی شما هستند.</small></div>
      </div>
      <button className="profile-card" type="button"><span className="avatar">م</span><span><strong>مریم احمدی</strong><small>مدیر عملیات</small></span><span className="chevron">⌄</span></button>
    </aside>
  );
}
