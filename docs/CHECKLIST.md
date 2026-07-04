# CHECKLIST — Sales Funnel Platform (jowshop)

Checklist verifikasi fungsional & non-fungsional. Dipakai untuk QA tiap fase di [TODOLIST.md](./TODOLIST.md) dan sebagai gate sebelum rilis. Berbeda dari TODOLIST (daftar kerja), file ini adalah **daftar "apakah benar-benar berfungsi"**.

---

## 1. Produk (Digital & Fisik)
- [x] Produk digital bisa dibuat tanpa field fisik (berat/stok) wajib diisi.
- [x] Produk fisik **wajib** isi berat & stok sebelum bisa dipublish.
- [x] Produk digital bisa upload file ATAU pakai link eksternal, minimal salah satu — divalidasi di `StoreProductDigitalAssetRequest` (`required_without` dua arah), diuji.
- [x] Produk berstatus `draft` tidak muncul/tidak bisa diakses di salespage publik.
- [x] File digital tersimpan di disk `local` (privat), bukan `public` — tidak bisa diakses langsung lewat URL tebakan, hanya lewat endpoint unduh bertoken.
- [x] Stok produk fisik berkurang saat order `paid` (via webhook Duitku), bukan saat checkout submit (hindari stok "terkunci" oleh checkout yang tidak dibayar) — diuji.

## 2. Funnel & Offer (Order Bump / Upsell / Downsell)
- [x] Funnel `draft` tidak bisa diakses via URL publik (salespage & checkout sama-sama 404).
- [x] Order Bump muncul **sebelum** submit pembayaran — diimplementasikan sebagai step wizard terpisah (1 offer per halaman, tombol Accept/Decline eksplisit) alih-alih checkbox di 1 halaman, sesuai skenario di PRD §5.
- [x] Menolak bump pertama menampilkan bump kedua sesuai `trigger_condition=declined` (skenario gula → kental manis) — diuji end-to-end.
- [x] Bump yang diterima otomatis masuk `order_items` dengan `offer_type=bump`.
- [x] Upsell tampil **setelah** pembayaran pertama sukses, bukan sebelum — dijaga via `abort_unless(order->status === Paid)` di `CheckoutUpsellController`, diuji (order belum bayar → 404).
- [x] Menolak upsell menampilkan downsell yang benar sesuai percabangan (`parent_offer_id`) — diuji end-to-end (skenario upsell 1kg ditolak → downsell 250gr).
- [x] Menerima upsell/downsell menambah `order_items` ke order yang sama — diuji (total order bertambah sesuai harga offer).
- [ ] Menerima upsell/downsell charge **one-click murni** tanpa redirect ulang ke gateway — **tidak** diimplementasikan (butuh tokenisasi kartu yang di luar cakupan); sebagai gantinya dibuat payment baru tertaut order yang sama, redirect ke Duitku lagi hanya untuk selisih harga. Diketahui & didokumentasikan di PRD §10, bukan celah yang belum disadari.
- [x] Chain offer yang habis (tidak ada offer berikutnya) mengarahkan ke halaman selesai, bukan error/blank page.
- [x] Harga di `order_items.unit_price` adalah snapshot saat transaksi (`FunnelOffer::effectivePrice()` dihitung sekali saat accept, bukan dirujuk ulang ke harga produk/offer live).

## 3. Visitor & Funnel Tracking (Inti Fitur)
- [x] Visitor baru mendapat `uuid` unik tersimpan di cookie first-party (5 tahun) saat pertama kali buka salespage.
- [x] Visitor yang sama (cookie sama) tidak membuat `visitors` baru saat kunjungan ulang — `last_seen_at` ter-update.
- [x] UTM params (`utm_source`, dst) tercapture dengan benar dari query string landing page pertama.
- [x] Setiap funnel yang dibuka visitor menghasilkan 1 `funnel_sessions` (per browser session, resume otomatis via PHP session selama belum selesai/kedaluwarsa).
- [x] Event tercatat berurutan & lengkap untuk 1 sesi penuh: `salespage_view` → `checkout_view` → (`bump_*`) → `checkout_submitted` → `payment_pending` → `payment_success`/`payment_failed` → (`upsell_*`/`downsell_*`) → `thankyou_view` — **satu funnel_session yang sama** dari awal sampai akhir (lihat catatan bug fix di TODOLIST Fase 7: session sempat pecah jadi 2 baris sebelum diperbaiki).
- [x] Event tidak pernah tercatat dobel untuk aksi yang sama (`FunnelTracker::recordOnce`, diuji: refresh salespage 2x hanya 1 `salespage_view`).
- [x] Setelah visitor jadi pembeli, `visitors.customer_id` tertaut ke `customers` yang benar — riwayat dari anonim ke buyer bisa ditelusuri via 1 query (diuji).
- [x] Dashboard funnel menampilkan angka conversion rate & take rate yang **konsisten** dengan raw count di `funnel_events` — dihitung langsung dari `funnel_events`/`funnel_sessions` tiap request (`FunnelAnalyticsService`), tidak ada tabel ringkasan terpisah yang bisa basi, diuji.

## 4. Checkout & Pembayaran (Duitku)
- [x] Checkout produk fisik mewajibkan alamat lengkap sebelum lanjut bayar; checkout produk digital tidak menampilkan form alamat sama sekali (diuji).
- [x] Validasi stok produk fisik saat checkout — order ditolak jika `stock <= 0`.
- [x] Ongkir terkalkulasi otomatis sebelum total akhir ditampilkan (produk fisik) — sudah ada sejak Fase 8, lihat detail di §5.
- [x] Order dibuat dengan status `pending` sebelum redirect eksternal ke `paymentUrl` Duitku.
- [x] Webhook Duitku **ditolak** (403) jika signature/hash tidak valid — diuji, payment/order tidak berubah.
- [x] Webhook Duitku yang terkirim dua kali tidak memproses order dua kali (guard: skip jika `payment.status` sudah bukan `pending`) — diuji termasuk stok tidak dobel berkurang.
- [x] Status `payments` & `orders` konsisten (webhook mengubah keduanya dalam 1 request yang sama; `paid` payment ⇔ `paid` order).
- [x] Kredensial Duitku (`merchant_code`, `api_key`) tersimpan terenkripsi (`encrypted` cast) & `#[Hidden]`, tidak pernah muncul di response API.
- [x] Route webhook Duitku (`webhooks/duitku`) adalah satu-satunya route yang dikecualikan dari CSRF (`bootstrap/app.php`) — route lain tetap terproteksi.
- [x] Kegagalan request ke Duitku (down, salah konfigurasi, timeout/DNS/koneksi gagal, dll) saat mau bayar/upsell menampilkan halaman 503 dengan pesan jelas ke pembeli, bukan crash 500 mentah — `PaymentGatewayException` ditangkap di `CheckoutController::pay()` & `CheckoutUpsellController::respond()`, mencakup baik kegagalan response HTTP maupun kegagalan koneksi (`ConnectionException`), dicatat via `report()` untuk diagnosis admin, semua panggilan punya `timeout(15)` eksplisit — diuji.
- [ ] **Belum diuji end-to-end terhadap sandbox Duitku sungguhan** — base URL API yang salah sudah ditemukan & diperbaiki (lihat TODOLIST Fase 6, "Bug ditemukan & diperbaiki"), signature scheme sudah diverifikasi ulang terhadap dokumentasi resmi, tapi alur penuh (create transaction → bayar → callback) tetap wajib divalidasi dengan kredensial sandbox asli sebelum go-live.

## 5. Pengiriman Fisik
- [x] Kalkulasi ongkir menggunakan berat **total** semua item fisik dalam order (termasuk order bump fisik) — step "Pilih Pengiriman" sengaja ditempatkan **setelah** chain order bump selesai, bukan di form buyer-info awal, supaya berat sudah final (`Order::totalPhysicalWeightGrams()`).
- [x] Admin bisa input/update nomor resi, status shipment berubah sesuai (`pending`→`processing`→`shipped`→`delivered`) — diuji.
- [x] Customer mendapat notifikasi email saat resi tersedia (queued, tidak terkirim ulang kalau resi tidak berubah) — diuji dengan `Notification::fake()`.
- [x] Kredensial RajaOngkir/Komerce tersimpan terenkripsi (`encrypted` cast) & `#[Hidden]` dari response API.
- [x] Harga ongkir yang tersimpan **dihitung ulang di server** saat submit (bukan dipercaya dari input client) — mencegah manipulasi harga; opsi yang tidak ada di hasil kalkulasi ulang ditolak.
- [x] Kegagalan provider Komerce (down, timeout/koneksi gagal) saat pencarian tujuan menampilkan hasil kosong (bukan crash) dan saat submit ongkir menampilkan error tervalidasi ("Tidak bisa menghitung ongkir saat ini") — diuji, mencakup kegagalan response HTTP maupun `ConnectionException`.
- [ ] **Belum diuji terhadap API Komerce/RajaOngkir sungguhan** — endpoint/header sudah diverifikasi ulang terhadap dokumentasi resmi Komerce dan cocok, tapi tetap wajib divalidasi dengan API key asli sebelum go-live.

## 6. Pengiriman Digital
- [x] Link download / lisensi hanya tergenerate **setelah** `payment_success`, tidak sebelumnya — `DigitalDeliveryService::generateForOrder()` dipanggil dari `DuitkuWebhookController` hanya saat `$isPaid`, diuji.
- [x] Link download expired setelah `expires_at` terlewati — akses ditolak dengan pesan jelas (403, "Tautan unduhan telah kedaluwarsa"), bukan error 500 — diuji.
- [x] `download_count` bertambah tiap unduhan dan diblokir setelah `max_downloads` tercapai (jika diset) — diuji (403 setelah limit tercapai).
- [x] Halaman "Order Lookup" (email + order number) tidak bocor data order milik orang lain — verifikasi kombinasi email+order number lewat `whereHas('customer', ...)`, akses per-order disimpan di session hanya untuk order yang barusan diverifikasi (memverifikasi 1 order tidak otomatis membuka order lain milik customer yang sama) — diuji.
- [x] Webhook yang terpanggil dua kali (duplikat, atau pembayaran tambahan untuk upsell digital) tidak membuat delivery dobel untuk item yang sama — diuji.
- [x] Token unduhan acak (48 karakter) tidak bisa ditebak; token tidak valid → 404, bukan bocor info order — diuji.

## 7. Salespage & AI Generator
- [x] Hasil generate AI melewati sanitasi sebelum disimpan/dirender (tidak ada script/HTML berbahaya lolos ke halaman publik) — `ContentBlockSanitizer`, diuji dengan payload `<script>`/`<b>`.
- [x] Setiap panggilan AI generate tercatat di `ai_generation_logs` (prompt, provider, token) — sukses maupun gagal.
- [x] Generate salespage gagal (API key salah/expired/limit habis/response tak terparsing) menampilkan error yang jelas ke admin (flash toast), bukan crash, dan tidak menimpa salespage yang sudah ada.
- [x] Salespage yang belum `published_at` tidak bisa diakses publik meski funnel-nya `published` (diuji: 404).
- [ ] Editor block: tambah/edit/hapus/reorder sudah ada; belum ada preview visual sebelum publish (nice-to-have, bukan blocker).

## 8. Pengaturan AI Provider
- [x] API key AI provider tersimpan terenkripsi (`encrypted` cast) dan `#[Hidden]` dari serialisasi API — tidak pernah dikembalikan ke response.
- [ ] Menghapus/menonaktifkan provider yang sedang jadi default salespage generator menampilkan peringatan (bukan silent failure saat generate berikutnya) — belum diimplementasikan.
- [x] Bisa ditambahkan lebih dari 1 provider dan berpindah provider default (menambah provider baru dengan `is_default=true` otomatis meng-unset default lama).
- [x] Menambahkan provider dengan API key/model yang salah **ditolak saat disimpan** (test-connection dengan prompt kecil sebelum `create()`), bukan baru ketahuan gagal saat admin generate salespage nanti — diuji.

## 9. Meta Conversions API
- [x] `external_event_id` untuk 1 kejadian nyata (ViewContent/InitiateCheckout/Purchase) **identik** antara payload yang dikirim browser Pixel (`MetaPixel` React component, prop `metaPixel.event_id`) dan job server-side CAPI (`FunnelEvent::external_event_id`) — sama-sama diambil dari baris `funnel_events` yang sama, diuji lewat assertion pada payload yang dikirim job. Verifikasi visual di Meta Events Manager kolom "Deduplicated" tetap wajib dilakukan manual sebelum go-live (butuh Pixel ID & akun Meta sungguhan).
- [x] Email & telepon pembeli dikirim ke Meta dalam bentuk **hash SHA-256**, tidak pernah plain text — diuji (`test_payment_success_sends_purchase_event_with_hashed_advanced_matching`).
- [x] Kegagalan/timeout API Meta **tidak** membuat checkout/pembayaran gagal atau lambat — job di-dispatch `->afterResponse()` (baru jalan setelah response terkirim ke browser) dan gagal-kirim di-retry lewat `$this->release()`, bukan `throw`, supaya tidak pernah muncul sebagai exception di request checkout/webhook manapun.
- [x] `access_token` Meta tersimpan terenkripsi (`encrypted` cast) & `#[Hidden]`, tidak muncul di response API — diuji.
- [x] Event `Purchase` yang dikirim memiliki `value` & `currency` yang sama dengan `orders.total` pada order terkait — diuji.
- [x] Saat `test_event_code` diisi, otomatis disisipkan ke payload CAPI — verifikasi kemunculannya di Meta Events Manager > Test Events tetap butuh akun sungguhan sebelum dianggap siap produksi (belum bisa diuji dari sini).
- [x] `meta_capi_event_logs` mencatat kegagalan kirim (status `failed` + response code/body) sehingga bisa diaudit, bukan silent-fail — diuji, 1 baris per `funnel_event_id` (retry meng-update baris yang sama, tidak menduplikasi).
- [x] Event yang tidak relevan untuk iklan (mis. `bump_view`, `thankyou_view`, `payment_pending`) tidak memicu panggilan API sama sekali — diuji.
- [ ] **Belum diuji terhadap Graph API Meta sungguhan** — sama seperti Duitku/Komerce, wajib divalidasi dengan Pixel ID & Access Token asli (plus `test_event_code`) sebelum go-live.

## 10. Dashboard Analitik
- [x] Angka visitor, conversion rate, dan take rate bisa difilter per funnel, rentang tanggal, dan sumber traffic (UTM), hasilnya akurat — diuji.
- [x] Revenue breakdown (main product vs bump vs upsell vs downsell) — dihitung dari `order_items.unit_price * quantity` per `offer_type`, jumlah semua baris breakdown sama dengan total revenue keseluruhan karena sumber datanya identik (`orders` berstatus `paid`/`processing`/`shipped`/`completed`) — diuji.
- [x] Dashboard tidak crash / tidak menampilkan angka salah saat funnel belum punya data sama sekali — semua agregasi memakai default `0`/`0.00` lewat `?? 0`, bukan exception saat koleksi kosong (halaman list Order & filter status juga diuji untuk kasus `all`).
- [ ] N+1 query pada tabel take rate offer (2 query per offer) belum dioptimasi jadi 1 query gabungan — lihat catatan desain di TODOLIST Fase 11 (baru relevan kalau jumlah offer per funnel jadi besar).

## 11. Keamanan & Non-Fungsional
- [x] Semua endpoint checkout & webhook punya rate limiting — `public-funnel` (30/menit), `public-download` (30/menit), `order-lookup` (10/menit), `webhooks` (60/menit), semua per-IP — diuji, request ke-N+1 setelah limit terlampaui mengembalikan 429.
- [x] Tidak ada credential (Duitku, RajaOngkir/Komerce, AI provider, Meta CAPI) yang ter-commit di `.env.example` dengan value asli — dicek, `.env.example` hanya boilerplate starter-kit dan memang tidak pernah menyimpan credential pihak ketiga (semua di DB terenkripsi).
- [x] Test suite penuh (`php artisan test --compact`) hijau sebelum merge — 182/182, diverifikasi stabil di banyak run berturut-turut (bukan cuma sekali).
- [x] `vendor/bin/pint --dirty --format agent` dijalankan pada semua file PHP yang diubah.
- [ ] Tidak ada N+1 query pada halaman dashboard analitik & list order — dashboard punya N+1 yang **diketahui & didokumentasikan** (2 query per offer untuk take rate, lihat CHECKLIST §10); list order tidak ada N+1 (eager load `customer`,`funnel`). Belum diverifikasi dengan query log/Telescope sungguhan.
- [x] Semua form publik (checkout, alamat) tervalidasi server-side — `StoreCheckoutRequest`/`StoreOrderShippingRequest`/dst semuanya FormRequest dengan validasi penuh, tidak mengandalkan validasi HTML5/frontend saja (diuji berulang kali sepanjang Fase 5-8 lewat skenario payload tidak lengkap → `assertSessionHasErrors`).

---

## Checklist Peluncuran (Sebelum Live ke Traffic Nyata)
- [ ] Ganti seluruh kredensial (Duitku, shipping, AI) dari sandbox/test ke production.
- [ ] Uji 1 transaksi end-to-end nyata dengan nominal kecil (produk digital) dan 1 transaksi produk fisik.
- [ ] Verifikasi webhook Duitku production benar-benar sampai ke server (bukan hanya sandbox).
- [ ] Verifikasi email pengiriman (digital delivery, notifikasi resi) tidak masuk folder spam.
- [ ] Pasang tracking pixel funnel (FB/TikTok/GA4) dan verifikasi event benar-benar terkirim ke platform terkait.
- [ ] Verifikasi Meta Events Manager menunjukkan Event Match Quality baik & event Pixel+CAPI ter-deduplikasi (bukan dobel), gunakan `test_event_code` sebelum lepas ke traffic nyata.
- [ ] Backup database terjadwal aktif sebelum menerima traffic nyata.
