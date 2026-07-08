import { usePage } from '@inertiajs/react';
import LegalPageLayout from '@/components/legal-page-layout';

export default function Terms() {
    const { branding } = usePage().props;

    return (
        <LegalPageLayout title="Syarat & Ketentuan">
            <p>
                Dengan mengakses dan menggunakan {branding.siteName}, kamu
                setuju untuk terikat dengan syarat dan ketentuan berikut.
                Mohon baca dengan saksama sebelum melakukan transaksi.
            </p>

            <h2>Penggunaan Layanan</h2>
            <p>
                {branding.siteName} menyediakan platform untuk membangun dan
                menjalankan funnel penjualan, termasuk salespage, checkout,
                dan pengiriman produk. Kamu bertanggung jawab atas keakuratan
                informasi yang diberikan saat melakukan pemesanan.
            </p>

            <h2>Pesanan & Pembayaran</h2>
            <p>
                Setiap pesanan dianggap sah setelah pembayaran berhasil
                dikonfirmasi. Harga, ongkos kirim, dan ketersediaan produk
                dapat berubah sewaktu-waktu tanpa pemberitahuan sebelumnya.
            </p>

            <h2>Pembatasan Tanggung Jawab</h2>
            <p>
                {branding.siteName} tidak bertanggung jawab atas kerugian
                tidak langsung yang timbul dari penggunaan layanan ini,
                termasuk namun tidak terbatas pada keterlambatan pengiriman
                oleh pihak ketiga (kurir).
            </p>

            <h2>Perubahan Ketentuan</h2>
            <p>
                Kami dapat memperbarui syarat dan ketentuan ini dari waktu ke
                waktu. Perubahan berlaku sejak dipublikasikan di halaman ini.
            </p>
        </LegalPageLayout>
    );
}
