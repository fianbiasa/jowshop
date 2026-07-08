import { usePage } from '@inertiajs/react';
import LegalPageLayout from '@/components/legal-page-layout';

export default function Privacy() {
    const { branding } = usePage().props;

    return (
        <LegalPageLayout title="Kebijakan Privasi">
            <p>
                {branding.siteName} menghargai privasi kamu. Kebijakan ini
                menjelaskan data apa saja yang kami kumpulkan dan bagaimana
                data tersebut digunakan.
            </p>

            <h2>Data yang Kami Kumpulkan</h2>
            <p>
                Saat kamu melakukan pemesanan, kami mengumpulkan informasi
                seperti nama, email, alamat pengiriman, dan nomor telepon
                untuk keperluan pemrosesan pesanan dan pengiriman.
            </p>

            <h2>Penggunaan Data</h2>
            <p>
                Data kamu digunakan semata-mata untuk memproses pesanan,
                mengirimkan konfirmasi/notifikasi terkait pesanan, dan
                meningkatkan kualitas layanan kami. Kami tidak menjual data
                pribadi kamu ke pihak ketiga.
            </p>

            <h2>Berbagi Data dengan Pihak Ketiga</h2>
            <p>
                Data pengiriman dibagikan dengan mitra kurir untuk keperluan
                pengantaran produk. Data pembayaran diproses melalui
                penyedia pembayaran pihak ketiga dan tidak disimpan langsung
                oleh {branding.siteName}.
            </p>

            <h2>Keamanan Data</h2>
            <p>
                Kami menerapkan langkah-langkah wajar untuk melindungi data
                kamu dari akses yang tidak sah.
            </p>
        </LegalPageLayout>
    );
}
