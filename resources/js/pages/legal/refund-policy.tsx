import { Link, usePage } from '@inertiajs/react';
import LegalPageLayout from '@/components/legal-page-layout';
import { contact } from '@/routes/legal';
import { create as orderLookupCreate } from '@/routes/order-lookup';

export default function RefundPolicy() {
    const { branding } = usePage().props;

    return (
        <LegalPageLayout title="Kebijakan Refund & Pengembalian Dana">
            <p>
                Kami ingin kamu puas dengan setiap pembelian di{' '}
                {branding.siteName}. Halaman ini menjelaskan secara lengkap
                kapan refund (pengembalian dana) berlaku, kapan tidak
                berlaku, serta bagaimana cara mengajukannya. Mohon baca
                dengan saksama sebelum melakukan pemesanan, karena tidak
                semua kondisi memenuhi syarat untuk refund.
            </p>

            <h2>Ketentuan Umum</h2>
            <p>
                Setiap permintaan refund akan dievaluasi berdasarkan
                ketentuan pada halaman ini serta deskripsi produk yang
                tercantum di halaman penjualan (salespage) saat pemesanan
                dibuat. Dengan menyelesaikan checkout, kamu dianggap telah
                membaca dan menyetujui kebijakan ini.
            </p>

            <h2>Produk Fisik — Kerusakan, Cacat, atau Salah Kirim</h2>
            <p>
                Jika produk fisik yang kamu terima rusak, cacat produksi,
                tidak sesuai deskripsi, atau salah kirim, kamu berhak
                mengajukan refund atau penggantian produk. Laporkan
                selambat-lambatnya 1x24 jam sejak paket diterima, disertai
                foto/video unboxing yang menunjukkan kondisi paket dan
                produk secara jelas. Video unboxing tanpa jeda (unedited)
                sangat membantu proses verifikasi dan mempercepat
                persetujuan klaim.
            </p>

            <h2>Produk Digital</h2>
            <p>
                Produk digital (e-book, template, file unduhan, akses
                anggota, dan sejenisnya) bersifat final setelah tautan
                unduh dikirim, karena sifatnya yang dapat langsung
                digandakan. Refund untuk produk digital hanya berlaku bila:
                file tidak dapat diakses/rusak, isi file tidak sesuai
                dengan deskripsi di salespage, atau kamu menerima produk
                yang salah. Laporkan kendala tersebut selambat-lambatnya
                3x24 jam sejak tautan unduh diterima.
            </p>

            <h2>Pembatalan Pesanan</h2>
            <p>
                Pesanan dapat dibatalkan secara penuh tanpa biaya selama
                status pesanan belum diproses/dikemas oleh kami. Setelah
                pesanan berstatus diproses atau dikirim, pembatalan tidak
                dapat dilakukan dan permintaan harus mengikuti alur refund
                pasca-penerimaan sesuai ketentuan di atas.
            </p>

            <h2>Kondisi yang Tidak Ditanggung Refund</h2>
            <p>
                Kami tidak dapat memproses refund untuk kondisi berikut:
                berubah pikiran (change of mind) setelah pesanan diproses;
                produk fisik yang sudah dipakai, dicuci, atau kemasannya
                rusak akibat kelalaian pembeli; kerusakan akibat kesalahan
                penggunaan atau pengiriman yang salah alamat karena data
                yang kamu masukkan sendiri saat checkout; laporan yang
                diajukan setelah batas waktu pada ketentuan di atas; serta
                produk digital yang sudah berhasil diunduh/diakses tanpa
                bukti kerusakan atau ketidaksesuaian.
            </p>

            <h2>Cara Mengajukan Refund</h2>
            <p>
                Ikuti langkah berikut untuk mengajukan klaim refund:
            </p>
            <ol className="list-decimal space-y-1 pl-5">
                <li>
                    Siapkan nomor pesanan kamu. Kamu bisa mengeceknya lagi
                    lewat halaman{' '}
                    <Link href={orderLookupCreate()} className="underline">
                        Cek Pesanan
                    </Link>
                    .
                </li>
                <li>
                    Hubungi kami melalui{' '}
                    <Link href={contact()} className="underline">
                        halaman kontak
                    </Link>{' '}
                    sebelum batas waktu pelaporan berakhir, sertakan nomor
                    pesanan, alasan refund, dan bukti pendukung (foto/video).
                </li>
                <li>
                    Tim kami akan meninjau bukti dan memverifikasi klaim,
                    umumnya dalam 1–3 hari kerja.
                </li>
                <li>
                    Jika klaim disetujui, kami akan memproses refund atau
                    penggantian produk sesuai ketentuan pada halaman ini.
                </li>
            </ol>

            <h2>Metode & Estimasi Waktu Pengembalian Dana</h2>
            <p>
                Dana yang disetujui untuk dikembalikan akan ditransfer ke
                metode pembayaran asal (transfer bank, e-wallet, QRIS,
                atau virtual account) yang diproses melalui payment
                gateway kami. Estimasi waktu proses berkisar 3–14 hari
                kerja tergantung metode pembayaran dan kebijakan masing-
                masing penyedia layanan pembayaran. Biaya administrasi
                atau biaya transaksi dari penyedia pembayaran, bila ada,
                bukan tanggung jawab kami dan dapat memengaruhi jumlah
                dana yang dikembalikan.
            </p>

            <h2>Biaya Pengiriman untuk Retur Produk Fisik</h2>
            <p>
                Jika retur disebabkan oleh kesalahan kami (produk cacat,
                rusak saat pengiriman, atau salah kirim), biaya pengiriman
                retur ditanggung oleh kami. Jika retur disebabkan oleh
                alasan lain di luar kesalahan kami, biaya pengiriman
                retur menjadi tanggung jawab pembeli.
            </p>

            <h2>Pesanan dengan Order Bump, Upsell, atau Bundel</h2>
            <p>
                Untuk pesanan yang terdiri dari beberapa produk (order
                bump, upsell, atau paket bundel), refund hanya berlaku
                untuk item yang memenuhi syarat sesuai ketentuan di atas,
                bukan untuk keseluruhan pesanan, kecuali seluruh pesanan
                dibatalkan sebelum diproses.
            </p>

            <h2>Perubahan Kebijakan</h2>
            <p>
                Kami dapat memperbarui kebijakan refund ini dari waktu ke
                waktu. Perubahan berlaku sejak dipublikasikan di halaman
                ini dan tidak berlaku surut terhadap klaim yang sudah
                diajukan sebelumnya.
            </p>

            <h2>Kontak</h2>
            <p>
                Ada pertanyaan tentang kebijakan refund ini atau ingin
                mengajukan klaim? Hubungi kami di{' '}
                <Link href={contact()} className="underline">
                    halaman kontak
                </Link>
                {branding.email && (
                    <>
                        {' '}
                        atau langsung ke{' '}
                        <a
                            href={`mailto:${branding.email}`}
                            className="underline"
                        >
                            {branding.email}
                        </a>
                    </>
                )}
                .
            </p>
        </LegalPageLayout>
    );
}
