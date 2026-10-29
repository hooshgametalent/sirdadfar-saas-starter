"use client";

import { FormEvent, useState } from "react";

const vehicleTypes = ["وانت‌بار", "نیسان", "خاور مسقف", "کامیون تک", "کامیون جفت", "تریلی کفی", "تریلی چادری", "یخچالی"];

export function NewShipmentForm() {
  const [submitted, setSubmitted] = useState(false);
  const [form, setForm] = useState({ origin: "", destination: "", cargo: "", vehicle: "", weight: "", pickupDate: "", description: "" });
  function update(name: keyof typeof form, value: string) { setForm((current) => ({ ...current, [name]: value })); }
  function handleSubmit(event: FormEvent<HTMLFormElement>) { event.preventDefault(); setSubmitted(true); }
  if (submitted) return <section className="success-state"><span>✓</span><h2>درخواست حمل ثبت شد</h2><p>در نسخه عملیاتی، درخواست پس از بررسی اپراتور برای رانندگان واجد شرایط ارسال می‌شود.</p><button className="secondary-button" onClick={() => setSubmitted(false)} type="button">ثبت درخواست جدید</button></section>;
  return <form className="shipment-form" onSubmit={handleSubmit}>
    <section className="panel form-section"><div className="panel-heading"><div><h2>مسیر حمل</h2><p>مبدأ و مقصد بار را وارد کنید.</p></div></div><div className="form-grid"><label>شهر مبدأ<input required value={form.origin} onChange={(e) => update("origin", e.target.value)} placeholder="مثلاً تهران" /></label><label>شهر مقصد<input required value={form.destination} onChange={(e) => update("destination", e.target.value)} placeholder="مثلاً اصفهان" /></label><label>تاریخ بارگیری<input required type="text" value={form.pickupDate} onChange={(e) => update("pickupDate", e.target.value)} placeholder="مثلاً ۱۵ آبان ۱۴۰۵" /></label><label>نوع خودرو<select required value={form.vehicle} onChange={(e) => update("vehicle", e.target.value)}><option value="">انتخاب کنید</option>{vehicleTypes.map((vehicle) => <option key={vehicle}>{vehicle}</option>)}</select></label></div></section>
    <section className="panel form-section"><div className="panel-heading"><div><h2>مشخصات بار</h2><p>این اطلاعات به انتخاب راننده و برآورد قیمت کمک می‌کند.</p></div></div><div className="form-grid"><label>نوع بار<input required value={form.cargo} onChange={(e) => update("cargo", e.target.value)} placeholder="مثلاً مصالح ساختمانی" /></label><label>وزن تقریبی<input required value={form.weight} onChange={(e) => update("weight", e.target.value)} placeholder="مثلاً ۸ تن" /></label><label className="span-two">توضیحات و شرایط ویژه<textarea rows={4} value={form.description} onChange={(e) => update("description", e.target.value)} placeholder="نیاز به بارگیری، بیمه، بار حساس یا هر توضیح ضروری دیگر" /></label></div></section>
    <div className="form-actions"><button className="secondary-button" type="button">ذخیره پیش‌نویس</button><button className="primary-button" type="submit">ثبت و ارسال برای بررسی</button></div>
  </form>;
}
