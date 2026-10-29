import Link from "next/link";

export function Topbar() {
  return (
    <header className="topbar">
      <div className="breadcrumb">
        بارراه <span>/</span> مدیریت حمل‌ونقل
      </div>
      <div className="topbar-actions">
        <button aria-label="اعلان‌ها" className="icon-button" type="button">
          ♢<span className="notification-dot" />
        </button>
        <Link className="primary-button" href="/orders/new">
          <span>＋</span> ثبت درخواست حمل
        </Link>
      </div>
    </header>
  );
}
