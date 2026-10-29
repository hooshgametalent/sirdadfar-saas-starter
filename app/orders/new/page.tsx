import { AppShell } from "@/components/layout/app-shell";
import { NewShipmentForm } from "@/components/orders/new-shipment-form";

export default function NewOrderPage() {
  return <AppShell title="ثبت درخواست حمل" description="اطلاعات بار را وارد کنید تا برای رانندگان واجد شرایط ارسال شود."><NewShipmentForm /></AppShell>;
}
