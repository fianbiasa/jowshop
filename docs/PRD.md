# PRD — Sales Funnel Platform (jowshop)

Status: Draft v1
Tanggal: 2026-07-02
Pemilik: Owner/Admin tunggal (single-store), didesain agar bisa berkembang ke multi-seller/SaaS di masa depan.

---

## 1. Ringkasan

Platform funnel jualan (mirip ClickFunnels/Kartra versi ringkas) yang memungkinkan owner membangun **funnel penjualan lengkap** per produk: Salespage → Checkout (dengan Order Bump) → Upsell/Downsell (post-purchase) → Thank You Page. Seluruh perjalanan visitor — dari pertama kali mendarat di salespage sampai menjadi pembeli — **dilacak penuh (full funnel tracking)** dan dianalisis di dashboard.

Aplikasi mendukung **produk digital** (file/lisensi, delivery otomatis) dan **produk fisik** (ongkir via integrasi kurir, resi pengiriman).

Fitur pembeda: **Salespage Generator berbasis AI**, di mana admin memasukkan API key AI miliknya sendiri (BYO key, multi-provider) di halaman Settings untuk generate copy salespage.

---

## 2. Latar Belakang & Masalah yang Diselesaikan

Saat ini funnel builder populer (ClickFunnels, Kartra) berbayar mahal dalam USD dan kurang cocok untuk kebutuhan lokal (payment gateway Indonesia, ongkir kurir lokal). Owner butuh satu platform terintegrasi untuk:

- Membuat salespage tanpa perlu developer/designer setiap kali (dibantu AI).
- Menaikkan Average Order Value (AOV) lewat Order Bump & Upsell/Downsell otomatis.
- Melihat data konversi tiap tahap funnel secara presisi (bukan cuma "total sales"), agar bisa dioptimasi (A/B test headline, ganti harga bump, dll di masa depan).
- Menjual produk digital dan fisik dalam satu sistem yang sama.

---

## 3. Terminologi / Glossary

| Istilah | Definisi |
|---|---|
| **Funnel** | Rangkaian halaman & penawaran untuk satu alur penjualan: Salespage → Checkout → Order Bump → Upsell/Downsell → Thank You. |
| **Salespage** | Landing page penjualan produk utama funnel. |
| **Order Bump** | Penawaran produk tambahan yang muncul **di halaman checkout** (biasanya checkbox), sebelum pembayaran ditekan. |
| **Upsell** | Penawaran produk tambahan **setelah checkout berhasil** (one-click, tanpa input kartu ulang), di halaman terpisah. |
| **Downsell** | Penawaran alternatif (biasanya lebih murah/berbeda) yang muncul ketika visitor **menolak** offer sebelumnya (bump/upsell). |
| **Offer** | Istilah umum untuk Order Bump, Upsell, atau Downsell — di sistem ini disatukan sebagai `funnel_offers` dengan percabangan (branching) accept/decline. |
| **Visitor** | Satu individu unik yang mengunjungi funnel, diidentifikasi lewat cookie/UUID sebelum ia melakukan pembelian apapun. |
| **Funnel Session** | Satu kali visitor "melewati" sebuah funnel dari awal (page view) sampai keluar/selesai. |
| **Funnel Event** | Satu kejadian tercatat dalam funnel session (view, accept, decline, purchase, dsb) — inilah dasar tracking lengkap. |
| **Conversion Rate per Step** | Persentase visitor yang lanjut dari satu tahap funnel ke tahap berikutnya. |
| **Take Rate** | Persentase pembeli yang menerima sebuah offer (bump/upsell/downsell) tertentu. |
| **Meta Conversions API (CAPI)** | API server-side dari Meta/Facebook untuk mengirim event konversi (PageView, Purchase, dll) langsung dari server, melengkapi Pixel browser agar data tidak hilang karena ad-blocker/ITP. |
| **Event Deduplication** | Mekanisme Meta mencocokkan event Pixel (browser) & CAPI (server) yang merepresentasikan kejadian sama via `event_id` identik, agar tidak dihitung dobel di Ads Manager. |

---

## 4. Skala & Model Kepemilikan

- **MVP: Single-store.** Semua produk & funnel dimiliki satu owner (bisa multi-user staff via `users` table yang sudah ada, tapi tidak ada konsep "toko lain").
- **Prinsip desain:** hindari asumsi hardcode "hanya ada 1 pengguna" di level query/logic inti (misal selalu tambahkan `created_by`/`user_id` pada entitas utama). Ini memudahkan migrasi ke multi-tenant/SaaS nanti tanpa rombak skema besar-besaran. **Tidak** membangun billing/plan/subscription SaaS di MVP — itu di luar cakupan.

---

## 5. Contoh Alur Funnel (Referensi)

Produk utama: **Kopi**

1. Visitor membuka salespage Kopi → `page_view` tercatat, visitor dapat UUID (cookie).
2. Klik "Beli Sekarang" → masuk Checkout.
3. Di Checkout, muncul Order Bump: **"Tambah Gula? +Rp5.000"**
   - **Terima** → gula masuk keranjang, event `bump_accepted` tercatat, checkout lanjut.
   - **Tolak** → event `bump_declined` tercatat → sistem tampilkan Order Bump kedua: **"Tambah Kental Manis? +Rp7.000"**
     - Terima/tolak tercatat sama seperti di atas.
4. Visitor submit pembayaran → `Order` dibuat, `Payment` diproses via Duitku.
5. Pembayaran sukses → redirect ke halaman **Upsell 1**: "Upgrade ke Kopi 1kg? +Rp50.000"
   - Terima → `Order Item` baru ditambahkan ke order yang sama (one-click, tanpa isi ulang pembayaran).
   - Tolak → tampil **Downsell 1**: "Kopi 250gr aja? +Rp20.000"
6. Selesai semua offer → **Thank You Page**, dan (untuk produk digital) link download/lisensi dikirim; (untuk produk fisik) alamat & ongkir sudah diproses di checkout, siap difulfillment.

Setiap langkah di atas = satu baris `funnel_events`, terhubung ke satu `funnel_sessions`, terhubung ke satu `visitors`. Dashboard bisa menjawab: "Dari 1.000 visitor salespage Kopi, berapa % checkout, berapa % ambil bump gula, berapa % ambil upsell 1kg, dst."

---

## 6. Fitur & Requirement Fungsional

### 6.1 Manajemen Produk (Digital & Fisik)
- CRUD produk dengan tipe `digital` atau `physical`.
- Produk digital: upload file (atau link eksternal), opsional lisensi/serial key, batas jumlah download, expiry link.
- Produk fisik: berat, dimensi, stok, SKU.
- Harga, deskripsi, gambar/galeri produk.
- Status: draft/published/archived.

### 6.2 Funnel Builder
- Owner membuat Funnel, memilih produk utama.
- Susun tahapan: Salespage → Checkout → Order Bump(s) → Upsell/Downsell chain → Thank You.
- Offer (bump/upsell/downsell) disusun sebagai pohon percabangan: tiap offer punya `parent_offer_id` + kondisi (`accepted`/`declined`) yang menentukan kapan offer itu muncul. Mendukung chain sepanjang apapun (bukan cuma 1 bump, 1 upsell).
- Funnel punya slug/URL publik sendiri, status draft/published.
- Pengaturan tracking pixel per funnel (Facebook Pixel, TikTok Pixel, Google Analytics/Ads) — agar tracking tidak hanya internal tapi juga sinkron ke platform iklan.

### 6.3 Salespage (Manual + AI Generator)
- Editor salespage berbasis blok/section (headline, subheadline, benefit list, gambar/video, testimoni, FAQ, garansi, CTA).
- **AI Generator**: admin isi brief produk (nama, benefit, target audiens, tone) → AI (via provider yang dikonfigurasi di Settings) generate draft copy per section → admin bisa edit manual sebelum publish.
- Riwayat generate AI disimpan (log prompt, provider, model, estimasi token/biaya) untuk audit & agar bisa regenerate/iterate.
- SEO meta (title, description, OG image) per salespage.

### 6.4 Checkout & Order Bump
- Form checkout: data pembeli (nama, email, telp), alamat (khusus produk fisik), pemilihan metode pembayaran.
- Order Bump tampil sebagai checkbox/section tambahan sebelum tombol bayar, mendukung multi-bump berantai (accept/decline branching seperti contoh Kopi di atas).
- Kalkulasi ongkir otomatis (produk fisik) saat alamat diisi, via integrasi kurir.
- Validasi stok (produk fisik) saat checkout.

### 6.5 Upsell & Downsell (Post-Purchase)
- Setelah pembayaran pertama sukses, tampilkan halaman Upsell one-click (tanpa input ulang metode bayar — charge ke transaksi/metode yang sama jika didukung gateway, atau tambahkan sebagai order terpisah yang terhubung bila one-click charge tidak didukung Duitku untuk metode tsb).
- Decline upsell → tampilkan downsell terkait (sesuai percabangan offer).
- Semua accept/decline tercatat sebagai funnel event & (jika accept) sebagai order item baru.

### 6.6 Visitor & Funnel Tracking (Fitur Inti)
- Setiap visitor baru dapat UUID tersimpan di cookie first-party.
- Capture UTM params (`utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`), referrer, device/browser, landing page pertama.
- Funnel session dibuat tiap visitor masuk ke sebuah funnel; funnel event dicatat di setiap titik keputusan (view, submit, accept, decline, payment status, thank you view).
- Saat visitor menjadi pembeli, visitor_id ditautkan ke `customers` & `orders` — sehingga riwayat lengkap 1 visitor (anonim → leads → buyer) bisa ditelusuri.

### 6.7 Pembayaran — Duitku
- Integrasi Duitku untuk metode: Virtual Account, e-wallet (OVO/DANA/ShopeePay/LinkAja), QRIS, kartu kredit, retail (Alfamart/Indomaret) — sesuai metode yang diaktifkan admin.
- Callback/webhook Duitku diverifikasi signature-nya (jangan percaya payload tanpa validasi hash).
- Status pembayaran: pending, paid, expired, failed — order & funnel event mengikuti status ini.
- Idempotent: webhook yang terkirim dobel tidak boleh memproses order dua kali.

### 6.8 Pengiriman Produk Fisik — Integrasi Kurir (RajaOngkir/Komerce)
- Kalkulasi ongkir real-time berdasarkan berat produk + kota/kecamatan asal-tujuan.
- Admin pilih kurir yang diaktifkan (JNE, J&T, SiCepat, dst — sesuai yang didukung provider).
- Setelah order fisik diproses, admin input nomor resi (manual atau via API create order jika didukung) → status shipment terupdate, bisa dikirim info resi ke pembeli.

### 6.9 Pengiriman Produk Digital
- Setelah pembayaran sukses, sistem otomatis generate link download aman (token, expirable) dan/atau lisensi/serial key.
- Email otomatis ke pembeli berisi akses produk digital.
- Halaman "cek pesanan saya" (akses via email + order id, tanpa perlu akun) untuk re-download.

### 6.10 Pengaturan AI Provider (BYO API Key)
- Halaman Settings → AI Providers: admin tambah 1+ provider (OpenAI, Anthropic, Google Gemini, dst), input API key (disimpan terenkripsi), pilih model default, tandai provider aktif/default.
- API key **tidak pernah** ditampilkan penuh setelah disimpan (masking), hanya bisa diganti/dihapus.
- Digunakan oleh fitur Salespage Generator (dan berpotensi fitur AI lain di masa depan: generate deskripsi produk, subject email, dsb).

### 6.11 Dashboard Analitik
- Funnel overview: jumlah visitor, conversion rate per tahap (visitor→checkout→purchase), take rate tiap offer (bump/upsell/downsell), revenue breakdown (main product vs bump vs upsell).
- Filter berdasarkan rentang tanggal, per funnel, per sumber traffic (UTM).
- Daftar order dengan status pembayaran & fulfillment (digital/fisik).
- Export data dasar (CSV) — nice to have, bukan blocker MVP.

### 6.12 Pengaturan Umum & Keamanan
- Settings: Payment (Duitku credentials), Shipping (RajaOngkir/Komerce credentials), AI Providers, Meta Conversions API, info toko (nama, alamat asal untuk ongkir).
- Semua credential sensitif (API key, merchant key, access token) disimpan terenkripsi (`encrypted` cast Laravel), tidak pernah di-log dalam bentuk plain text.

### 6.13 Meta Conversions API (Server-Side Tracking)
Agar tracking konversi ke Meta/Facebook **valid dan akurat** (tidak bocor karena ad-blocker/ITP/Safari, dan Event Match Quality tinggi), sistem mengirim setiap event penting **dua jalur sekaligus** — Browser Pixel (client-side, sudah ada di `funnels.pixel_settings.fb_pixel_id`) **dan** Conversions API (server-side) — dengan **deduplikasi** memakai `event_id` yang sama persis di kedua jalur, sesuai spesifikasi resmi Meta.

- Halaman Settings → Meta Conversions API: input **Pixel ID**, **Access Token** (System User token dari Meta Business Manager, disimpan terenkripsi), opsional **Test Event Code** (untuk validasi live di Events Manager sebelum go-live), toggle aktif/nonaktif.
- Setiap `funnel_events` yang relevan untuk iklan dipetakan ke **Standard Event** Meta:
  | Funnel Event (internal) | Meta Standard Event |
  |---|---|
  | `salespage_view` | `PageView` / `ViewContent` |
  | `checkout_view` | `InitiateCheckout` |
  | `bump_accepted` / `upsell_accepted` / `downsell_accepted` | `AddToCart` (offer diterima → nilai transaksi bertambah) |
  | `payment_success` | `Purchase` (dengan `value` & `currency` dari total order berjalan) |
- Setiap event yang dikirim (baik dari browser `fbq()` maupun dari server) memakai **`event_id` yang sama** (disimpan di `funnel_events.external_event_id`) — ini kunci deduplikasi resmi Meta agar 1 kejadian nyata tidak dihitung 2x di Ads Manager.
- Pengiriman server-side menyertakan **Advanced Matching**: email & telepon pembeli di-hash SHA-256 sebelum dikirim (`em`, `ph`), plus `client_ip_address`, `client_user_agent`, `fbp`/`fbc` cookie (dicapture saat visitor pertama mendarat, disimpan di `visitors`), dan `event_source_url`.
- Pengiriman ke Meta dilakukan **async via queue** (job terpisah) — kegagalan/lambatnya API Meta **tidak boleh** memblokir atau memperlambat proses checkout/pembayaran pembeli.
- Kegagalan kirim ke Meta dicatat (status + response) untuk observability, dengan retry terbatas (bukan retry tanpa batas yang bisa membanjiri queue).
- Saat `test_event_code` diisi, event dikirim dengan flag test agar admin bisa memverifikasi kesesuaian data langsung di Meta Events Manager > Test Events sebelum melepas ke traffic produksi.

---

## 7. Non-Functional Requirements

- **Keamanan**: verifikasi signature webhook (Duitku), enkripsi credential/API key at rest, rate limiting pada endpoint checkout & webhook, sanitasi HTML hasil generate AI sebelum disimpan/ditampilkan (cegah XSS).
- **Performa**: funnel event write harus ringan/cepat (tidak boleh memperlambat rendering salespage) — pertimbangkan queue/async untuk event non-kritis.
- **Reliabilitas pembayaran**: pemrosesan webhook harus idempotent & aman dari race condition (gunakan DB transaction/locking saat update status order).
- **Auditabilitas**: log AI generation (prompt, provider, biaya perkiraan) untuk kontrol biaya.
- **Portabilitas skema**: desain tabel menghindari hardcode single-tenant di level constraint (lihat §4), agar migrasi ke multi-tenant lebih mudah.

---

## 8. Di Luar Cakupan (Out of Scope) — MVP

- Multi-seller/SaaS (billing, plan, tenant isolation penuh) — dicatat sebagai kemungkinan roadmap, bukan requirement sekarang.
- A/B testing salespage otomatis (split traffic + statistical significance).
- Affiliate/referral program.
- Coupon/discount code global (bisa menyusul di fase lanjutan).
- Abandoned cart recovery (email/WA follow-up otomatis).
- Multi-currency/multi-bahasa.
- Native mobile app.

---

## 9. Metrik Keberhasilan

- Setiap visitor yang membuka salespage bisa ditelusuri penuh perjalanannya (100% funnel event coverage untuk visitor yang minimal mencapai checkout).
- Dashboard mampu menampilkan take rate tiap order bump/upsell/downsell secara akurat dan real-time (delay < beberapa menit).
- Checkout → pembayaran Duitku → order fulfillment (digital/fisik) berjalan end-to-end tanpa intervensi manual (kecuali input resi pengiriman fisik).
- Admin bisa generate draft salespage baru dari brief singkat dalam < 1 menit menggunakan AI provider pilihannya sendiri.

---

## 10. Risiko & Asumsi

| Risiko/Asumsi | Mitigasi |
|---|---|
| One-click upsell charge mungkin tidak didukung semua metode Duitku (mis. VA tidak bisa di-charge ulang otomatis) | Untuk metode yang tidak mendukung one-click charge, upsell/downsell dibuat sebagai transaksi pembayaran baru yang tetap tertaut ke order asal (bukan blocker, tapi bukan "one-click" murni). |
| Kredensial pihak ketiga (Duitku, RajaOngkir/Komerce, AI provider) adalah data sensitif | Simpan terenkripsi, jangan pernah expose ke frontend/log. |
| Volume funnel event bisa besar pada traffic tinggi | Pertimbangkan queue/batching untuk insert event, indexing yang tepat pada `funnel_sessions`/`funnel_events`. |
| Scope "single-store vs SaaS" bisa berubah pikiran owner | Skema sudah menyisipkan `created_by`/owner reference di entitas utama agar tidak perlu migrasi besar jika berubah. |
