import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "بارراه | مدیریت هوشمند حمل بار",
  description: "پلتفرم مدیریت حمل‌ونقل جاده‌ای ایران",
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="fa" dir="rtl">
      <body>{children}</body>
    </html>
  );
}
