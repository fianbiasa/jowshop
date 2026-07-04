# TODOLIST — Sales Funnel Platform (jowshop)

Turunan dari [PRD.md](./PRD.md) & [ERD.md](./ERD.md). Dikerjakan berurutan per fase; tiap fase idealnya diakhiri dengan test yang jalan (`php artisan test --compact`) sebelum lanjut fase berikutnya.

Legenda: `[ ]` belum, `[x]` selesai.

---

## Fase 0 — Fondasi Non-Fitur
- [x] Konfirmasi HTTP client: pakai `Illuminate\Support\Facades\Http` untuk Duitku/RajaOngkir/AI provider/Meta CAPI (tanpa SDK tambahan → tidak perlu approval dependency baru).
- [x] Struktur folder mengikuti default Laravel yang sudah ada (`app/Models`, `app/Http/Controllers`, `app/Http/Requests`, `app/Policies`), tidak membuat folder dasar baru.
- [x] Kredensial pihak ketiga (Duitku, RajaOngkir/Komerce, AI provider, Meta CAPI) **disimpan di database** (tabel `*_settings`, terenkripsi) agar bisa dikonfigurasi admin lewat Settings UI saat runtime — bukan lewat `.env`/`config/services.php` yang statis per-deployment.
- [x] `QUEUE_CONNECTION=database` sudah default di `.env.example` — cukup untuk job async (Meta CAPI, email digital delivery) tanpa setup tambahan.

## Fase 1 — Manajemen Produk (Digital & Fisik)
- [x] Migration `products`
- [x] Migration `product_digital_assets`
- [x] Model `Product` (+ enum `type`, `status`) & `ProductDigitalAsset`, relasi
- [x] Factory & Seeder `Product`
- [x] Form Request `StoreProductRequest` / `UpdateProductRequest` (validasi beda field wajib untuk digital vs physical)
- [x] Policy `ProductPolicy`
- [x] Controller CRUD produk (admin) + route
- [x] Halaman Inertia/React: list produk, form create/edit (kondisional field sesuai `type`)
- [x] Upload file digital asset (validasi tipe/ukuran file) — dikerjakan di Fase 9 (satu paket dengan alur digital delivery)
- [x] Test feature: create/update/delete produk digital & fisik (happy + validasi gagal)

## Fase 2 — Data Model Funnel, Salespage, Offer
- [x] Migration `funnels`
- [x] Migration `salespages`
- [x] Migration `funnel_offers` (self-referencing `parent_offer_id`)
- [x] Model `Funnel`, `Salespage`, `FunnelOffer` + relasi (termasuk relasi rekursif offer parent/children)
- [x] Factory & Seeder untuk data contoh (funnel Kopi + bump gula/kental manis, sesuai contoh PRD §5)
- [x] Policy `FunnelPolicy`
- [x] Test unit: struktur pohon `funnel_offers` (resolve children berdasarkan `trigger_condition`)

## Fase 3 — Funnel Builder UI (Admin Dashboard)
- [x] Controller & route admin: CRUD Funnel
- [x] Halaman Inertia: list funnel, detail funnel (Detail, Order Bump, Upsell/Downsell, Pixel Settings dalam satu halaman `edit` — tab Salespage ditunda ke Fase 4)
- [x] UI susun Order Bump/Upsell/Downsell (pilih produk, harga override, diskon, kondisi parent, urutan) via dialog + pohon rekursif
- [x] Preview struktur percabangan offer secara visual (list bertingkat, indentasi per level)
- [x] Publish/unpublish funnel (validasi: harus ada salespage; produk digital wajib punya minimal 1 digital asset)
- [x] Test feature: create funnel lengkap dengan offer chain via UI/controller (17 test: funnel CRUD, publish gating, offer tree CRUD & validasi)

## Fase 4 — Salespage Renderer (Publik) + AI Generator
- [x] Migration & model `AiProviderSetting`, `AiGenerationLog` (dimajukan dari Fase 12 karena jadi prasyarat fitur ini)
- [x] Halaman Settings → AI Providers (tambah/hapus provider, API key ter-enkripsi & hidden dari response, default provider otomatis exclusive)
- [x] Route publik `GET /f/{funnel:slug}` render salespage dari `content` JSON (block renderer di React) — 404 jika funnel/salespage belum published
- [x] Komponen block renderer: headline, subheadline, benefit list, testimonial, FAQ, CTA, guarantee
- [x] Halaman admin: editor salespage (tambah/edit/hapus/reorder block via `useForm`, tidak perlu HTML form native karena struktur data dinamis)
- [x] Service `AiProviderClient` — abstraksi panggil OpenAI/Anthropic/Gemini via `Http` facade (mockable dengan `Http::fake()`)
- [x] Endpoint "Generate Salespage" (admin isi brief → panggil AI → hasil jadi draft `content` blocks, replace salespage)
- [x] Simpan `ai_generation_logs` tiap generate (prompt, token, status) — sukses maupun gagal
- [x] Sanitasi output AI & input manual sebelum disimpan (`ContentBlockSanitizer::sanitize`, strip_tags rekursif di semua leaf string)
- [x] Test feature: 16 test (AI provider settings, generate sukses/gagal/unparsable, manual save + sanitasi, publik 404 handling)

## Fase 5 — Checkout Flow + Order Bump
- [x] Migration `customers`, `addresses`
- [x] Model `Customer`, `Address` + find-or-create by email (`updateOrCreate` saat checkout)
- [x] Migration `visitors`, `funnel_sessions`, `funnel_events` (termasuk `external_event_id` untuk dedup CAPI, `fbp`/`fbc` di visitors — dimajukan dari Fase 10 agar tidak perlu migration alter-table lagi)
- [x] Model `Visitor`, `FunnelSession`, `FunnelEvent`
- [x] Service `VisitorIdentifier` (cookie UUID 5 tahun, capture UTM/referrer/landing_url/device_type saat pertama kali dilihat) + `FunnelTracker` (resolve funnel_session via PHP session, `recordOnce` untuk dedup event)
- [x] Event `salespage_view` tercatat saat render salespage publik
- [x] Halaman checkout publik: form data pembeli + alamat (kondisional produk fisik, validasi stok) — **desain wizard bertahap** (bukan checkbox di 1 halaman): buyer info → offer bump satu-per-satu (sesuai contoh PRD §5) → selesai
- [x] Logic pemilihan bump berikutnya berdasarkan accept/decline (`FunnelOffer::nextOfferFor()`, state "offer saat ini" disimpan di session)
- [x] Event `checkout_view`, `bump_view`, `bump_accepted`, `bump_declined`, `checkout_submitted`, `thankyou_view` tercatat
- [x] Migration `orders`, `order_items`
- [x] Model `Order` (`recalculateTotals()`), `OrderItem` + `CheckoutController`/`CheckoutOfferController` untuk pembuatan order dari cart checkout (main product + bump yang diterima), idempotent terhadap double-submit bump
- [x] Test feature: 19 test — checkout digital/fisik, validasi alamat & stok, resume checkout, chain bump declined→accepted (skenario Kopi persis PRD §5), idempotency, tracking visitor & event lengkap

**Catatan desain**: pembayaran belum terhubung (Fase 6) — halaman "selesai" saat ini hanya konfirmasi pesanan tercatat (`status=pending`), belum redirect ke Duitku. Order Bump didesain sebagai wizard step-by-step (1 offer per halaman, Accept/Decline eksplisit) agar persis meniru alur di PRD §5, bukan checkbox tunggal di halaman checkout.

## Fase 6 — Integrasi Pembayaran (Duitku)
- [x] Migration `payment_settings`, `payments`
- [x] Halaman admin Settings → Payment (merchant code/api key Duitku terenkripsi & `#[Hidden]`, environment sandbox/production; pola re-entry penuh tiap ubah, konsisten dengan AI Providers)
- [x] Service `DuitkuGateway`: `createTransaction()` (POST ke `/inquiry`, signature MD5(merchantCode+orderNumber+amount+apiKey), dapat `paymentUrl` hosted Duitku) + `verifyCallbackSignature()`
- [x] Checkout selesai (buyer info + semua bump terjawab) → `CheckoutController::pay()` membuat `Payment` (status pending) → redirect eksternal ke `paymentUrl` Duitku
- [x] Route webhook `POST /webhooks/duitku` (dikecualikan dari CSRF di `bootstrap/app.php`), signature verification wajib, idempotent (skip jika payment sudah bukan `pending`)
- [x] Update status `Order` & `Payment` sesuai callback (`paid`/`failed`) + event `payment_success`/`payment_failed`; sukses juga menandai `funnel_sessions.status=converted` dan **decrement stok fisik** (sesuai CHECKLIST §1)
- [x] Halaman `checkout/kembali` (return URL) — tampilan status pesanan setelah customer kembali dari Duitku (informational; webhook tetap sumber kebenaran)
- [x] Test feature: 10 test — pay tanpa payment settings (503), redirect ke Duitku (mocked), webhook sukses/gagal/signature invalid/duplikat/unknown-order, decrement stok, funnel session converted

**Catatan jujur**: belum pernah diuji terhadap sandbox Duitku sungguhan secara langsung oleh saya — implementasi mengikuti dokumentasi publik Duitku v2 API. Signature scheme (inquiry: `MD5(merchantCode+merchantOrderId+amount+apiKey)`, callback: `MD5(merchantCode+amount+merchantOrderId+apiKey)`) sudah diverifikasi ulang terhadap dokumentasi resmi dan konsisten.

**Bug ditemukan & diperbaiki**: saat pengguna mencoba checkout dengan kredensial Duitku sungguhan, request ke Duitku gagal dengan `404` — base URL API di `PaymentSetting::baseApiUrl()` ternyata salah (`api-sandbox.duitku.com`/`api-prod.duitku.com`, host yang tidak pernah ada). Diverifikasi ulang lewat dokumentasi resmi Duitku dan source code SDK resmi mereka di GitHub (duitkupg/duitku-android-sdk), lalu diperbaiki jadi `https://sandbox.duitku.com/webapi/api/merchant/v2` (sandbox) dan `https://passport.duitku.com/webapi/api/merchant/v2` (production). Sebelum go-live, **tetap wajib** uji end-to-end dengan akun sandbox asli (lihat Checklist Peluncuran) — perbaikan ini menghilangkan bug yang pasti gagal, bukan jaminan seluruh alur sudah benar tanpa pengujian nyata.

**Perbaikan terkait**: `PaymentGatewayException` (dilempar `DuitkuGateway::createTransaction()`) ternyata tidak pernah ditangkap di pemanggilnya (`CheckoutController::pay()` dan `CheckoutUpsellController::respond()`) — kegagalan apa pun ke Duitku (termasuk bug 404 di atas) selalu berujung crash 500 mentah ke pembeli di tengah proses bayar. Ditangkap sekarang, dicatat via `report()`, dan pembeli melihat halaman 503 dengan pesan jelas ("Pembayaran sedang tidak tersedia, coba lagi beberapa saat lagi") — pola yang konsisten dengan `abort_if($settings === null, 503, ...)` yang sudah ada di kedua controller tersebut. Diuji di kedua controller.

**Bug ditemukan & diperbaiki (kedua)**: dari log browser pengguna, endpoint pencarian tujuan pengiriman (`checkout/destinations`) juga sempat 500 saat provider Komerce tidak terjangkau — ternyata `DuitkuGateway` dan `ShippingRateClient` hanya menangani kegagalan di level **response HTTP** (status 4xx/5xx via `$response->failed()`), bukan kegagalan **koneksi** (timeout/DNS/SSL — `Illuminate\Http\Client\ConnectionException`), yang dilempar Guzzle SEBELUM sempat mendapat response sama sekali sehingga lolos dari pengecekan `failed()`. Kedua service sekarang menangkap `ConnectionException` dan membungkusnya jadi exception domain yang sama (`PaymentGatewayException`/`ShippingRateException`) lewat method baru `fromConnectionFailure()`, plus `timeout(15)` eksplisit di semua panggilan HTTP eksternal (Duitku, Komerce) supaya tidak menggantung tanpa batas. `CheckoutShippingController::store()` yang sebelumnya tidak membungkus `calculateRates()` sama sekali (beda dari `show()` yang sudah benar) sekarang juga menangkap `ShippingRateException` dan menampilkan error tervalidasi ("Tidak bisa menghitung ongkir saat ini") alih-alih crash. Diuji (skenario `Http::failedConnection()`) di ketiga titik: bayar checkout awal, bayar upsell, pencarian tujuan, dan submit pemilihan ongkir.

**Perbaikan kecil (frontend)**: komponen `MetaPixel` memanggil `fbq('init', pixelId)` di setiap kali komponen ter-mount (yaitu setiap kali pindah halaman dalam SPA Inertia — salespage → checkout → dst), memicu warning "Duplicate Pixel ID" dari script Meta sendiri di console browser. Diperbaiki dengan melacak pixel ID yang sudah pernah di-`init` (module-level `Set`) supaya `fbq('init', ...)` hanya dipanggil sekali per pixel ID per page load, event `track` tetap dipanggil setiap saat seperti semula.

## Fase 7 — Upsell/Downsell Post-Purchase
- [x] Route halaman post-purchase `GET/POST /f/{funnel:slug}/upsell/{offer}` (`CheckoutUpsellController`)
- [x] `CheckoutController::return()` (halaman kembali dari Duitku) jadi entry point: kalau order `paid` & chain belum selesai → redirect ke offer post-purchase pertama (stage=upsell, root)
- [x] Decline → arahkan ke downsell terkait (`parent_offer_id` + `trigger_condition=declined`) **tanpa** perlu bayar lagi (redirect langsung)
- [x] Accept → tambah `order_items` baru ke order yang sama; karena Duitku (tanpa token tersimpan) tidak mendukung one-click charge sungguhan, dibuat **payment baru** (`{order_number}-O{offer_id}`) tertaut order yang sama & redirect eksternal ke Duitku lagi untuk selisih harga saja (sesuai mitigasi risiko di PRD §10)
- [x] Event `upsell_view/accepted/declined`, `downsell_view/accepted/declined` tercatat
- [x] Setelah chain offer habis (baik lewat decline maupun accept-yang-terakhir) → halaman `checkout/kembali` final, event `thankyou_view`
- [x] **Perbaikan stok**: `order_items.stock_decremented_at` ditambahkan supaya decrement stok fisik tidak dobel ketika ada >1 payment sukses per order (main + upsell)
- [x] Test feature: 8 test — mulai chain, decline→downsell, decline seluruh chain (tanpa bayar), accept→payment tambahan→redirect Duitku, full chain decline+accept dengan webhook kedua, no-double-stock-decrement lintas 2 payment

**Bug ditemukan & diperbaiki di fase ini**: `FunnelTracker::resolveSession()` hanya me-reuse funnel session kalau statusnya masih `active` — begitu webhook mengubahnya jadi `converted` (setelah pembayaran sukses), request berikutnya (halaman kembali/upsell) malah membuat funnel session BARU, memutus kontinuitas tracking tepat di titik paling penting (pasca-konversi). Ini bug signifikan untuk fitur inti "tracking lengkap per visitor" — sudah diperbaiki + diverifikasi lewat test yang mengecek jumlah funnel_sessions dan urutan event tetap dalam 1 sesi yang sama dari awal sampai thank you.

**Catatan jujur**: alur "one-click" upsell di sini **bukan** one-click murni (tetap redirect ke Duitku per offer yang diterima) karena tidak ada tokenisasi kartu — ini sudah diantisipasi & didokumentasikan sebagai keterbatasan di PRD §10, bukan bug.

## Fase 8 — Pengiriman Produk Fisik (RajaOngkir/Komerce)
- [x] Migration `shipping_settings`, `shipments`; kolom `destination_area_id`/`destination_label` ditambahkan ke `addresses` (untuk kalkulasi ongkir akurat, terpisah dari field alamat manual)
- [x] Halaman admin Settings → Shipping (kredensial provider terenkripsi & `#[Hidden]`, area asal, kurir diaktifkan sebagai daftar dipisah koma)
- [x] Service `ShippingRateClient`: `searchDestination()` (pencarian area tujuan) + `calculateRates()` (hitung ongkir berdasarkan berat total fisik order + tujuan), berbasis API Komerce v1
- [x] Endpoint checkout: pencarian tujuan real-time (`GET checkout/destinations`, autocomplete di halaman checkout, auto-isi provinsi/kota/kecamatan) + **step baru "Pilih Pengiriman"** (`checkout/pengiriman`) setelah chain order bump selesai (agar berat total — termasuk bump fisik — sudah final) dan sebelum ke pembayaran; harga dihitung ulang di server saat submit (anti-tampering)
- [x] Halaman admin Order fisik (`/orders`, list + detail): input/update nomor resi, ubah status shipment
- [x] Notifikasi email (`ShipmentTrackingAvailable`, queued) ke customer saat resi pertama kali diisi — tidak terkirim ulang kalau resi tidak berubah
- [x] Test feature: 20 test — pencarian tujuan, redirect ke step pengiriman untuk produk fisik (dilewati untuk produk digital), kalkulasi ongkir (mock provider), submit ongkir tervalidasi ulang di server, halaman admin order, notifikasi resi (`Notification::fake()`)

**Catatan jujur**: sama seperti Duitku, integrasi Komerce/RajaOngkir ini **belum pernah diuji ke API sungguhan** (tidak ada kredensial). Endpoint & payload mengikuti dokumentasi publik yang stabil, tapi wajib divalidasi dengan API key asli sebelum go-live.

## Fase 9 — Pengiriman Produk Digital
- [x] Controller `ProductDigitalAssetController` (store/destroy) + `StoreProductDigitalAssetRequest` (file **atau** external_url, salah satu wajib; tipe lisensi; maks unduhan) — melengkapi item Fase 1 yang ditunda; file disimpan di disk `local` (privat, bukan `public`) supaya tidak bisa diakses langsung tanpa lewat endpoint download bertoken
- [x] UI di halaman edit produk (`product-digital-assets.tsx`): tabel file yang sudah diunggah + form tambah file/tautan, tampil hanya untuk produk digital
- [x] Migration `order_item_deliveries` (order_item_id unik, download_token unik, license_key, max_downloads, download_count, expires_at, delivered_at)
- [x] Model `OrderItemDelivery` (+ `isExpired()`, `hasReachedDownloadLimit()`) & relasi `OrderItem::delivery()`
- [x] Service `DigitalDeliveryService::generateForOrder()` — generate token unduhan (+ kode lisensi acak kalau `license_type=license_key`) untuk tiap `order_item` digital yang belum punya delivery; idempotent (aman dipanggil ulang oleh webhook duplikat/upsell tambahan), masa berlaku default 30 hari sejak dibuat
- [x] Dipanggil dari `DuitkuWebhookController::handle()` setelah `payment_success` (sejalan dengan pola `decrementPhysicalStock()` yang sudah ada)
- [x] Notifikasi email `DigitalDeliveryAvailable` (queued) berisi link akses per item digital + kode lisensi jika ada
- [x] Endpoint publik `GET unduh/{token}` (halaman akses, daftar file) + `GET unduh/{token}/{asset}` (unduh/redirect sungguhan, enforce `expires_at` & `max_downloads`, increment `download_count`)
- [x] Route publik "Order Lookup" (`GET/POST pesanan-saya`, `GET pesanan-saya/{order:order_number}`) — verifikasi kombinasi email + order number (bukan order number saja), akses per-order disimpan di session setelah verifikasi berhasil
- [x] Test feature: 12 test baru (`ProductDigitalAssetTest`, `DigitalDeliveryTest`, `OrderLookupTest`) — upload/hapus asset, delivery ter-generate setelah paid & tidak dobel saat webhook duplikat, notifikasi terkirim, unduhan sukses/diblokir setelah limit/kedaluwarsa, token tidak valid → 404, lookup dengan kombinasi benar/salah, isolasi akses antar-order

**Catatan desain**: satu `order_item_delivery` mewakili satu `order_item` (barang yang dibeli), bukan satu file — kalau sebuah produk digital punya beberapa file (misal file utama + bonus), semuanya diakses lewat token yang sama di halaman `unduh/{token}`, dan `download_count`/`max_downloads` dihitung gabungan lintas file tersebut (memakai `max_downloads` dari asset pertama produk). Ini cukup untuk kasus umum (1 produk = 1 paket file) tanpa perlu tabel per-file-limit yang lebih rumit. `ProductPolicy::update` mengizinkan semua user terautentikasi (bukan hanya pembuat produk) — konsisten dengan model single-store multi-admin di aplikasi ini, jadi tidak ada test "user lain tidak boleh upload".

## Fase 10 — Meta Conversions API (Server-Side Tracking)
- [x] Migration `meta_capi_settings` (singleton: pixel_id, access_token terenkripsi, test_event_code, is_active), `meta_capi_event_logs` (unique per `funnel_event_id`: event_name, status, response_code, response_body, attempts)
- [x] Kolom `external_event_id` (uuid) di `funnel_events`, `fbp`/`fbc` di `visitors` — sudah dibuat sejak Fase 5, tidak perlu migration alter-table lagi
- [x] Capture cookie `_fbp`/`_fbc` saat visitor pertama masuk salespage, simpan ke `visitors` — sudah dikerjakan di `VisitorIdentifier` (Fase 5), tinggal dipakai saat kirim ke Meta
- [x] Halaman admin Settings → Meta Conversions API (Pixel ID, Access Token, Test Event Code, toggle aktif) — pola sama persis dengan Payment/Shipping settings (re-entry penuh, `#[Hidden]` untuk access_token)
- [x] `FunnelEventType::toMetaStandardEvent()` — mapping event internal ke Standard Event Meta (`salespage_view`→`ViewContent`, `checkout_view`→`InitiateCheckout`, `*_accepted` [bump/upsell/downsell]→`AddToCart`, `payment_success`→`Purchase`); `external_event_id` sudah di-generate di server sejak Fase 5 (`FunnelSession::recordEvent()`), tidak perlu service baru
- [x] Trait `SharesMetaPixelProp` + prop `metaPixel` dikirim ke 3 halaman publik yang render langsung (salespage, checkout, checkout-return) berisi `pixel_id`/`event_name`/`event_id` (+ `value`/`currency` untuk Purchase) — komponen React `MetaPixel` memuat `fbq()` lalu `fbq('track', event, data, {eventID})` dengan `event_id` yang identik dengan yang dikirim CAPI, sesuai spesifikasi dedup resmi Meta
- [x] Service `MetaConversionsApiClient::send()`: POST ke Graph API `https://graph.facebook.com/v19.0/{pixel_id}/events` dengan payload standard (`event_name`, `event_time`, `event_id`, `action_source=website`, `user_data`, `custom_data` opsional), plus `test_event_code` jika diisi
- [x] Hash SHA-256 untuk `em` (email, lowercase+trim) & `ph` (phone, digit saja) sebelum dikirim; sertakan `client_ip_address`, `client_user_agent`, `fbp`, `fbc` (diambil dari `visitors`, bukan dari request — karena job jalan async, request asli sudah tidak tersedia)
- [x] Job `SendMetaConversionEvent` (queued, `$tries=3`, retry via `$this->release(30)` bukan `throw` — supaya kegagalan API Meta tidak pernah bocor jadi exception yang mengganggu response checkout/webhook manapun juga di lingkungan dev yang kebetulan `QUEUE_CONNECTION=sync`)
- [x] Dispatch job (`->afterResponse()`, dieksekusi setelah response terkirim ke browser) dari `FunnelTracker::recordOnce()` untuk semua event yang lewat situ, dan dari `DuitkuWebhookController` untuk `payment_success`/`payment_failed`; job sendiri yang memfilter lewat `toMetaStandardEvent()` — event yang tidak relevan (mis. `bump_view`, `thankyou_view`) di-skip cepat tanpa panggilan API
- [x] Simpan hasil kirim ke `meta_capi_event_logs` (1 baris per `funnel_event_id`, `firstOrCreate` supaya retry/duplicate dispatch tidak membuat baris baru — status/response/attempts di-update tiap percobaan)
- [x] Dukungan `test_event_code` — otomatis disisipkan ke payload kalau diisi di Settings
- [x] Pixel tujuan per funnel: `Funnel::fbPixelId()` (dari `pixel_settings->fb_pixel_id`, field yang sudah ada sejak Fase 3) dipakai kalau funnel punya pixel sendiri, fallback ke `pixel_id` default di Settings — satu `access_token` (System User token) bisa mengirim ke banyak Pixel ID dalam satu Business Manager yang sama
- [x] Test feature: 9 test baru (`MetaConversionsApiTest`) — tidak ada panggilan API tanpa settings aktif, ViewContent/InitiateCheckout/Purchase terkirim dengan `event_id` benar, Pixel per-funnel meng-override default, event non-relevan di-skip, advanced matching (`em`/`ph`) ter-hash SHA-256 dengan benar, respons gagal tercatat status `failed`, dispatch berulang untuk event yang sama tidak membuat baris log dobel; plus 3 test settings page (`MetaCapiSettingTest`)

**Catatan desain**: `AddToCart` (bump/upsell/downsell diterima) saat ini hanya dikirim lewat CAPI (server), **tidak** lewat `fbq()` di browser — karena aksi accept/decline diproses via redirect POST→GET, sehingga tidak ada satu halaman yang secara alami "memiliki" event tersebut untuk di-render sebagai prop seperti `ViewContent`/`InitiateCheckout`/`Purchase`. Ini bukan bug (tidak ada resiko dobel-hitung — hanya satu jalur yang mengirim), hanya berarti Event Match Quality untuk `AddToCart` sedikit lebih rendah dibanding 3 event lain yang dikirim dua jalur (Pixel+CAPI). Bisa disempurnakan nanti dengan flash-based event jika diperlukan.

**Bug ditemukan & diperbaiki (tidak terkait Meta CAPI)**: saat stress-test suite (jalan berulang kali), ditemukan `ProductFactory::physical()` men-generate `stock` dengan `numberBetween(0, 100)` — sekitar 1% kemungkinan menghasilkan `0`, yang secara acak memicu validasi "stok habis" di test manapun yang memakai produk fisik tanpa override stok eksplisit. Ini menyebabkan flaky failure acak (~30% dari full-suite run gagal di salah satu dari beberapa test checkout/shipping, dengan pesan error yang berbeda-beda setiap kali). Diperbaiki dengan mengubah rentang ke `numberBetween(1, 100)` (skenario stok habis sendiri sudah diuji eksplisit lewat override `stock => 0`, jadi tidak kehilangan cakupan). Diverifikasi stabil lewat 10 run berturut-turut setelah perbaikan.

## Fase 11 — Dashboard Analitik & Reporting
- [x] Service `FunnelAnalyticsService::summarize()` — visitor count (distinct `visitor_id` dari `funnel_sessions` yang cocok filter), konversi tiap langkah utama (`salespage_view`→`checkout_view`→`checkout_submitted`→`payment_success`, dihitung dari sesi unik yang mencatat tiap event, persentase relatif terhadap langkah pertama), take rate per offer (`{stage}_view` vs `{stage}_accepted` per `funnel_offer_id`), revenue (total `orders.total` untuk status `paid`/`processing`/`shipped`/`completed`)
- [x] Halaman Inertia dashboard (`DashboardController@index`, menggantikan placeholder starter-kit) — filter funnel (dropdown, default semua funnel), rentang tanggal (`from`/`to`), sumber traffic (`utm_source`, dicocokkan ke `visitors.utm_source`) lewat query string; kartu ringkasan (visitor, pesanan terbayar, revenue) + tabel konversi tiap langkah + tabel take rate offer
- [x] Halaman list Order (`/orders`) — filter status via dropdown (`?status=paid`, dst; `all` = tanpa filter, ditangani di server sebelum validasi enum)
- [x] Test feature: 6 test dashboard (`DashboardAnalyticsTest` — visitor & step count, scoping per funnel, filter UTM, take rate offer, revenue hanya status terbayar) + 2 test filter status order (`OrderManagementTest`)

**Catatan desain**: agregasi dashboard menjalankan query langsung ke `funnel_sessions`/`funnel_events`/`orders` tiap request (2 query per offer untuk take rate) tanpa caching/materialized view — cukup untuk skala single-store MVP. Kalau volume event jadi besar, langkah lanjutan yang wajar adalah cache per kombinasi filter (mis. `Cache::remember` beberapa menit) atau tabel ringkasan harian, bukan mengubah query pattern-nya.

## Fase 12 — Pengaturan AI Provider (Settings) — Selesai (dasar sudah dikerjakan di Fase 4)
- [x] Migration `ai_provider_settings`, `ai_generation_logs`
- [x] Halaman Settings → AI Providers: tambah/hapus provider (edit belum ada — hapus & tambah ulang untuk ganti kredensial, cukup untuk MVP)
- [x] Validasi test-connection sebelum disimpan: `AiProviderSettingController::store()` membuat instance `AiProviderSetting` sementara (tidak di-save) dari kredensial yang diinput, memanggil `AiProviderClient::generate()` dengan prompt kecil ("Balas dengan kata OK saja") — kalau gagal (network error, API key salah, model tidak ditemukan, dst), request ditolak dengan `ValidationException` di field `api_key` berisi pesan dari provider, dan **tidak ada baris yang tersimpan**. Provider baru hanya tersimpan kalau benar-benar bisa dipakai generate.
- [x] Test feature: simpan provider, API key terenkripsi di DB (`assertStringNotContainsString` pada kolom raw), `#[Hidden]` mencegah key balik ke response, plus test baru: koneksi gagal → provider tidak tersimpan & error muncul di `api_key`

## Fase 13 — Testing, Keamanan, Persiapan Rilis
- [x] Rate limiting untuk seluruh endpoint publik — 4 named rate limiter di `AppServiceProvider::configureRateLimiting()`: `public-funnel` (30/menit/IP, seluruh route `f/{funnel:slug}/*` termasuk checkout/offer/upsell/shipping), `public-download` (30/menit/IP, `unduh/*`), `order-lookup` (10/menit/IP — lebih ketat karena rawan enumerasi email+nomor pesanan), `webhooks` (60/menit/IP, `webhooks/duitku`). Limit dipilih generous untuk journey checkout manusia normal (~5-15 request) tapi membatasi bot/scraping otomatis. Diuji (`RateLimitingTest`: request ke-31 di funnel publik & ke-11 di order-lookup mengembalikan 429).
- [x] Review CSRF exemption — dikonfirmasi hanya `webhooks/duitku` yang dikecualikan di `bootstrap/app.php` (`validateCsrfTokens(except: [...])`), tidak ada route lain.
- [x] Review sanitasi HTML — `ContentBlockSanitizer` diterapkan ke seluruh konten salespage (AI-generated maupun manual) sebelum disimpan; seluruh `dangerouslySetInnerHTML` di frontend dicek satu per satu: hanya dipakai untuk label pagination bawaan Laravel dan QR code SVG dari Fortify (keduanya server-generated, bukan input pengguna/AI) — tidak ada celah XSS dari konten yang bisa dikontrol pengguna.
- [x] Verifikasi `.env.example` tidak memuat credential asli — bersih (starter-kit boilerplate); kredensial pihak ketiga (Duitku/Komerce/AI/Meta) memang tidak pernah lewat `.env` sama sekali sejak Fase 0 (disimpan terenkripsi di DB), jadi tidak ada celah di sini secara desain.
- [x] Jalankan full test suite `php artisan test --compact` — 182/182 lulus, stabil di berbagai run berturut-turut.
- [x] Jalankan `vendor/bin/pint --dirty --format agent` di semua PHP yang diubah — bersih.
- [x] Jalankan lint/typecheck frontend (`npm run lint`, `npx tsc --noEmit`, `npm run build`) — bersih.
- [x] Review checklist lengkap di [CHECKLIST.md](./CHECKLIST.md) — diperbarui, gap yang tersisa didokumentasikan secara eksplisit sebagai "belum diuji ke API sungguhan" (Duitku/Komerce/Meta, butuh kredensial produksi) atau "nice-to-have bukan blocker" (preview salespage, peringatan hapus AI provider default, optimasi N+1 dashboard).

---

## Roadmap Setelah MVP (di luar TODOLIST ini, referensi PRD §8)
- Multi-seller/SaaS (billing, tenant isolation)
- A/B testing salespage
- Affiliate/referral program
- Coupon/discount code
- Abandoned cart recovery
