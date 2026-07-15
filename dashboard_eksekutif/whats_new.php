<?php
session_start();
require_once 'config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$page_title = "What's New: AI Advisor Integrations";
include 'includes/header.php';
?>

<style>
    /* Premium Glassmorphism & UI Presentation */
    :root {
        --present-bg: #0f172a;
        --present-card-bg: rgba(30, 41, 59, 0.7);
        --present-border: rgba(255, 255, 255, 0.1);
        --text-primary: #f8fafc;
        --text-secondary: #94a3b8;
    }

    .present-header {
        background: linear-gradient(135deg, #1e3a8a, #0f172a);
        border-radius: 16px;
        padding: 30px;
        border: 1px solid var(--present-border);
        margin-bottom: 30px;
    }

    .present-card {
        background: var(--present-card-bg);
        border: 1px solid var(--present-border);
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 40px;
        transition: all 0.3s ease;
    }
    .present-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        border-color: #3b82f6;
    }

    .card-banner {
        height: 280px;
        overflow: hidden;
        position: relative;
        border-bottom: 1px solid var(--present-border);
        background: #020617;
    }
    .card-banner img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0.85;
        transition: transform 0.5s ease;
    }
    .present-card:hover .card-banner img {
        transform: scale(1.05);
        opacity: 1;
    }

    .badge-ai {
        position: absolute;
        top: 20px;
        left: 20px;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        color: white;
        padding: 6px 16px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4);
    }

    .sample-box {
        background: rgba(15, 23, 42, 0.6);
        border-left: 4px solid #8b5cf6;
        border-radius: 0 12px 12px 0;
        padding: 15px;
        margin-top: 15px;
    }

    .prompt-text {
        font-family: 'Courier New', Courier, monospace;
        color: #f1f5f9;
        font-size: 0.85rem;
    }

    .output-box {
        background: rgba(15, 23, 42, 0.4);
        border: 1px dashed rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        padding: 15px;
        font-size: 0.85rem;
        color: #e2e8f0;
        line-height: 1.5;
        max-height: 250px;
        overflow-y: auto;
    }

    /* Print Optimization styles */
    @media print {
        body {
            background: white !important;
            color: black !important;
        }
        #sidebarMenu, header, .present-header .btn, .no-print {
            display: none !important;
        }
        main {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        .present-card {
            background: white !important;
            border: 2px solid #ccc !important;
            color: black !important;
            page-break-inside: avoid;
            margin-bottom: 50px;
            box-shadow: none !important;
        }
        .card-banner {
            height: auto !important;
            max-height: 250px;
            border-bottom: 2px solid #ccc !important;
        }
        .card-banner img {
            filter: grayscale(100%);
        }
        .sample-box, .output-box {
            background: #f8fafc !important;
            border-color: #333 !important;
            color: black !important;
        }
        .prompt-text {
            color: #333 !important;
        }
        .text-light {
            color: black !important;
        }
        .badge-ai {
            background: #333 !important;
            color: white !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="pt-3 pb-2 mb-4 border-bottom no-print">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h2"><i class="fas fa-bullhorn text-warning me-2"></i> What's New: Generative AI & Reports</h1>
        <button onclick="window.print()" class="btn btn-primary fw-bold shadow-sm">
            <i class="fas fa-print me-2"></i> Cetak Laporan Presentasi (PDF)
        </button>
    </div>
</div>

<div class="present-header text-light shadow mb-5">
    <div class="row align-items-center">
        <div class="col-md-9">
            <h3 class="fw-bold text-white mb-2"><i class="fas fa-brain text-info me-2"></i> Executive Showoff: Integrasi AI Advisor & Modul Pelaporan Baru</h3>
            <p class="mb-0 text-secondary">
                Laporan komprehensif penambahan kecerdasan buatan dinamis (Generative AI) dan perluasan fitur analisis data di seluruh modul dashboard eksekutif untuk memperkuat pengambilan keputusan pimpinan dan direksi.
            </p>
        </div>
        <div class="col-md-3 text-end no-print">
            <button onclick="window.print()" class="btn btn-outline-light btn-lg fw-bold w-100">
                <i class="fas fa-file-pdf me-2"></i> Save to PDF
            </button>
        </div>
    </div>
</div>

<!-- GRID OF AI ADVISOR MODULES -->
<div class="row">
    
    <!-- 1. AI RECEIVABLES ADVISOR -->
    <div class="col-12 mb-5">
        <div class="present-card text-light shadow-sm">
            <div class="row g-0">
                <div class="col-lg-5 card-banner">
                    <img src="dokumentasi/9. Laporan harian Piutang.jpg" alt="Laporan Harian Piutang">
                    <span class="badge-ai">Keuangan & Kolektibilitas</span>
                </div>
                <div class="col-lg-7 p-4">
                    <h4 class="fw-bold text-primary mb-2"><i class="fas fa-money-bill-wave me-2"></i> AI Receivables & Billing Collector Advisor</h4>
                    <p class="text-secondary small">
                        <strong>Fungsi Utama:</strong> Menganalisis komposisi penuaan piutang (Aging Receivables) berdasarkan berbagai kriteria penjamin (BPJS, Asuransi Swasta, dan Perusahaan Mitra) secara dinamis, mengidentifikasi piutang macet yang ter-pending cukup lama, serta merekomendasikan skala prioritas aksi penagihan klaim.
                    </p>
                    <div class="small">
                        <strong>Contoh Use Case:</strong> Tim billing mengunggah rekap piutang overdue > 90 hari, AI melokalisasi sengketa klaim asuransi swasta tertentu, dan memberikan format surat klarifikasi taktis.
                    </div>
                    <div class="sample-box mt-3">
                        <span class="text-warning small fw-bold"><i class="fas fa-terminal me-1"></i> Contoh Prompt Pimpinan:</span>
                        <p class="prompt-text mb-0">"Ulas komposisi piutang tertunda di atas. Asuransi mana yang memiliki risiko sengketa (dispute) terlama, dan bagaimana rekomendasi taktis agar klaim tersebut cair dalam 14 hari kerja?"</p>
                    </div>
                    <div class="mt-3">
                        <span class="text-info small fw-bold"><i class="fas fa-robot me-1"></i> Output Analisis AI:</span>
                        <div class="output-box mt-1">
                            <p><strong>## Analisis Aging Receivables & Likuiditas Penagihan</strong></p>
                            <p>Berdasarkan audit data piutang berjalan, ditemukan konsentrasi piutang tertunda pada penjamin <strong>Asuransi Sehat Utama</strong> senilai <strong>Rp 245.000.000</strong> dengan umur > 90 hari.</p>
                            <p><strong>Rekomendasi Taktis Pencairan Cepat:</strong></p>
                            <ul>
                                <li>Lakukan rekonsiliasi berkas klaim fisik (berfokus pada lampiran syarat SEP dan resume medis) karena data menunjukkan tingkat dispute administrasi tinggi di modul ini.</li>
                                <li>Ajukan restrukturisasi atau penjadwalan pembayaran bertahap dengan pihak penjamin.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. AI CASH FLOW ADVISOR -->
    <div class="col-12 mb-5">
        <div class="present-card text-light shadow-sm">
            <div class="row g-0">
                <div class="col-lg-5 card-banner">
                    <img src="dokumentasi/57. Arus kas.jpg" alt="Laporan Arus Kas">
                    <span class="badge-ai">Likuiditas & Akuntansi</span>
                </div>
                <div class="col-lg-7 p-4">
                    <h4 class="fw-bold text-primary mb-2"><i class="fas fa-chart-line me-2"></i> AI Cash Flow & Liquidity Advisor</h4>
                    <p class="text-secondary small">
                        <strong>Fungsi Utama:</strong> Memproyeksikan arus kas masuk dan keluar (Aktivitas Operasional, Investasi, Pendanaan), mendeteksi tren penurunan likuiditas kasir (*cash deficit warning*), serta memandu alokasi belanja modal (*capital expenditure*) agar terjaga kestabilan neraca.
                    </p>
                    <div class="small">
                        <strong>Contoh Use Case:</strong> Direktur Keuangan menginput rencana belanja alat kesehatan (Ventilator baru) di bulan depan, AI memproyeksikan apakah saldo kas akhir bulan tetap aman dari defisit operasional.
                    </div>
                    <div class="sample-box mt-3">
                        <span class="text-warning small fw-bold"><i class="fas fa-terminal me-1"></i> Contoh Prompt Pimpinan:</span>
                        <p class="prompt-text mb-0">"Uraikan tren saldo kas bersih 3 bulan terakhir. Jika kami mencairkan dana cadangan Rp 500 Juta untuk renovasi ruang VIP minggu ini, apakah arus kas operasional bulan depan terganggu?"</p>
                    </div>
                    <div class="mt-3">
                        <span class="text-info small fw-bold"><i class="fas fa-robot me-1"></i> Output Analisis AI:</span>
                        <div class="output-box mt-1">
                            <p><strong>## Analisis Kelayakan Proyeksi Saldo Kas</strong></p>
                            <p>Analisis arus kas MoM menunjukkan margin keuntungan operasional bersih stabil di angka <strong>12.4%</strong>. Namun, penarikan langsung senilai Rp 500 Juta untuk renovasi ruang VIP akan menekan cash ratio ke batas kritis <strong>0.85</strong>.</p>
                            <p><strong>Rekomendasi Strategis:</strong> Tunda alokasi renovasi hingga minggu ke-3 saat pencairan klaim BPJS termin kedua masuk, atau pecah termin pembayaran kontraktor menjadi 3 termin (30%-40%-30%).</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. AI BPJS COMPLIANCE ADVISOR -->
    <div class="col-12 mb-5">
        <div class="present-card text-light shadow-sm">
            <div class="row g-0">
                <div class="col-lg-5 card-banner">
                    <img src="dokumentasi/32. Dashboard laporan Antrol.jpg" alt="Laporan Antrian Online">
                    <span class="badge-ai">Kepatuhan & Operasional</span>
                </div>
                <div class="col-lg-7 p-4">
                    <h4 class="fw-bold text-primary mb-2"><i class="fas fa-clock me-2"></i> AI BPJS Queue Compliance Advisor</h4>
                    <p class="text-secondary small">
                        <strong>Fungsi Utama:</strong> Mengaudit kepatuhan pengiriman dan pemenuhan waktu tunggu antrean (*Task 3 s.d Task 7*) BPJS secara berkala per dokter dan poliklinik, serta melacak anomali penundaan pelayanan resep obat dan pemeriksaan dokter.
                    </p>
                    <div class="small">
                        <strong>Contoh Use Case:</strong> Mengidentifikasi poli dengan keterlambatan pendaftaran tertinggi (Task 3) dan merumuskan alokasi ulang dokter jaga untuk memangkas antrean.
                    </div>
                    <div class="sample-box mt-3">
                        <span class="text-warning small fw-bold"><i class="fas fa-terminal me-1"></i> Contoh Prompt Pimpinan:</span>
                        <p class="prompt-text mb-0">"Analisis data keterlambatan antrean BPJS di atas. Mengapa poli Anak memiliki durasi tunggu Task 5 (pembagian obat) terpanjang, dan apa solusi pemecahannya?"</p>
                    </div>
                    <div class="mt-3">
                        <span class="text-info small fw-bold"><i class="fas fa-robot me-1"></i> Output Analisis AI:</span>
                        <div class="output-box mt-1">
                            <p><strong>## Audit Kepatuhan Waktu Tunggu BPJS (Poli Anak)</strong></p>
                            <p>Data menunjukkan rata-rata waktu tunggu penyiapan resep (Task 5) di Poli Anak mencapai <strong>48 menit</strong> (melebihi target nasional 30 menit). Hal ini disebabkan oleh tingginya volume resep puyer racikan dibanding sediaan sirup.</p>
                            <p><strong>Langkah Rekomendasi:</strong></p>
                            <ul>
                                <li>Terapkan formularium khusus untuk resep obat anak siap pakai.</li>
                                <li>Sediakan asisten apoteker khusus (*dedicated runner*) di depo farmasi rawat jalan saat jam sibuk (09:00 - 12:00).</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. AI DEMOGRAPHIC & MARKETING ADVISOR -->
    <div class="col-12 mb-5">
        <div class="present-card text-light shadow-sm">
            <div class="row g-0">
                <div class="col-lg-5 card-banner">
                    <img src="dokumentasi/28. Dashboard analitics pemetaan kunjungan pasien.jpg" alt="Laporan Peta Kunjungan">
                    <span class="badge-ai">Pemasaran & Demografi</span>
                </div>
                <div class="col-lg-7 p-4">
                    <h4 class="fw-bold text-primary mb-2"><i class="fas fa-map-marked-alt me-2"></i> AI Patient Demographics & Marketing Advisor</h4>
                    <p class="text-secondary small">
                        <strong>Fungsi Utama:</strong> Menganalisis kepadatan spasial asal pasien (kabupaten, kecamatan, kelurahan) dihubungkan dengan jenis kelamin, status rawat jalan/rawat inap, serta poliklinik tujuan untuk merumuskan wilayah target promosi kesehatan atau CSR.
                    </p>
                    <div class="small">
                        <strong>Contoh Use Case:</strong> Melacak kelurahan dengan kunjungan pasien rawat inap terendah untuk sasaran kegiatan bakti sosial dan sosialisasi program rujukan puskesmas.
                    </div>
                    <div class="sample-box mt-3">
                        <span class="text-warning small fw-bold"><i class="fas fa-terminal me-1"></i> Contoh Prompt Pimpinan:</span>
                        <p class="prompt-text mb-0">"Petakan area dengan kontribusi pasien terendah namun berdekatan dengan kompetitor. Strategi CSR atau promosi apa yang sebaiknya kita kerahkan ke kelurahan tersebut?"</p>
                    </div>
                    <div class="mt-3">
                        <span class="text-info small fw-bold"><i class="fas fa-robot me-1"></i> Output Analisis AI:</span>
                        <div class="output-box mt-1">
                            <p><strong>## Analisis Wilayah Pemasaran Potensial</strong></p>
                            <p>Ditemukan bahwa <strong>Kelurahan Campaka</strong> berkontribusi sangat rendah (< 2% kunjungan) meskipun letaknya hanya 3 km dari RS. Wilayah ini didominasi oleh pasien wanita usia produktif.</p>
                            <p><strong>Rekomendasi Pemasaran:</strong> Selenggarakan program CSR pemeriksaan USG kandungan gratis bekerja sama dengan bidan desa setempat di Kelurahan Campaka untuk memperkenalkan poli Obgyn RS kita.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. AI PHARMACY STOCK & ASSET ADVISOR -->
    <div class="col-12 mb-5">
        <div class="present-card text-light shadow-sm">
            <div class="row g-0">
                <div class="col-lg-5 card-banner">
                    <img src="dokumentasi/40. Dashboard Stok Berjalan Farmasi.jpg" alt="Laporan Stok Farmasi">
                    <span class="badge-ai">Logistik & Aset Farmasi</span>
                </div>
                <div class="col-lg-7 p-4">
                    <h4 class="fw-bold text-primary mb-2"><i class="fas fa-capsules me-2"></i> AI Pharmacy Stock & Purchase Advisor</h4>
                    <p class="text-secondary small">
                        <strong>Fungsi Utama:</strong> Melacak sisa stok obat aktif, mengidentifikasi kelompok obat mati (*dead stock / slow moving*) yang berisiko kadaluarsa, mendeteksi obat kritis depo yang mendekati batas minimal stok, serta menyusun rencana pemesanan otomatis (*reorder point*).
                    </p>
                    <div class="small">
                        <strong>Contoh Use Case:</strong> Menghitung potensi kerugian modal dari 25 item obat slow moving dan membuat usulan skema retur ke distributor sebelum waktu ED (Expired Date) kurang dari 6 bulan.
                    </div>
                    <div class="sample-box mt-3">
                        <span class="text-warning small fw-bold"><i class="fas fa-terminal me-1"></i> Contoh Prompt Pimpinan:</span>
                        <p class="prompt-text mb-0">"Tampilkan data dead stock dengan nilai rupiah terbesar. Bagaimana opsi penyelamatan modal agar obat tersebut tidak terbuang sia-sia karena expired?"</p>
                    </div>
                    <div class="mt-3">
                        <span class="text-info small fw-bold"><i class="fas fa-robot me-1"></i> Output Analisis AI:</span>
                        <div class="output-box mt-1">
                            <p><strong>## Audit Dead Stock & Penyelamatan Aset Farmasi</strong></p>
                            <p>Terdapat obat <strong>Antibiotik Ceftriaxone Injeksi</strong> sebanyak 120 vial dengan nilai aset <strong>Rp 18.000.000</strong> yang tidak mengalami mutasi keluar selama 90 hari terakhir.</p>
                            <p><strong>Opsi Penyelamatan Modal:</strong></p>
                            <ul>
                                <li>Hubungi distributor utama untuk pengajuan retur obat dengan opsi penukaran item fast moving lain.</li>
                                <li>Koordinasikan dengan Komite Medik untuk mendorong dokter spesialis bedah memprioritaskan obat tersebut sebagai profilaksis operasional sesuai protokol klinis RS.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. AI SATU SEHAT ADVISOR -->
    <div class="col-12 mb-5">
        <div class="present-card text-light shadow-sm">
            <div class="row g-0">
                <div class="col-lg-5 card-banner">
                    <img src="dokumentasi/30. Dashboard Kepatuhan Pengiriman aliran data satu sehat.jpg" alt="Laporan Kepatuhan Satu Sehat">
                    <span class="badge-ai">Integrasi & RME Kemenkes</span>
                </div>
                <div class="col-lg-7 p-4">
                    <h4 class="fw-bold text-primary mb-2"><i class="fas fa-satellite-dish me-2"></i> AI Satu Sehat Advisor</h4>
                    <p class="text-secondary small">
                        <strong>Fungsi Utama:</strong> Mengaudit kepatuhan sinkronisasi Rekam Medis Elektronik (RME) dengan platform Satu Sehat Kemenkes, melacak anomali kegagalan kirim per modul (Encounter, Condition, Observation, dll), dan mendeteksi kesalahan format NIK dokter/pasien.
                    </p>
                    <div class="small">
                        <strong>Contoh Use Case:</strong> Mengidentifikasi kegagalan pengiriman modul Condition (Diagnosa) sebesar 100% dan merumuskan perbaikan skema ICD-10 FHIR mapper.
                    </div>
                    <div class="sample-box mt-3">
                        <span class="text-warning small fw-bold"><i class="fas fa-terminal me-1"></i> Contoh Prompt Pimpinan:</span>
                        <p class="prompt-text mb-0">"Uraikan mengapa persentase pengiriman modul diagnosa dan resep sangat rendah. Apa aksi perbaikan sistem yang harus segera dilakukan divisi IT?"</p>
                    </div>
                    <div class="mt-3">
                        <span class="text-info small fw-bold"><i class="fas fa-robot me-1"></i> Output Analisis AI:</span>
                        <div class="output-box mt-1">
                            <p><strong>## Laporan Audit Sinkronisasi Satu Sehat Kemenkes</strong></p>
                            <p>Tingkat kegagalan kirim modul <strong>Diagnosa/Condition</strong> mencapai <strong>100%</strong> karena ketidakcocokan format kode ICD-10 dengan skema FHIR Resource. Untuk modul <strong>Resep</strong>, kegagalan disebabkan NIK pasien yang tidak terdaftar di database Dukcapil.</p>
                            <p><strong>Rekomendasi IT Action Plan:</strong> Perbaiki fungsi mapping XML ke JSON schema Kemenkes di file middleware bridge dan tambahkan filter pencatatan NIK wajib 16 digit numerik pada form pendaftaran.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 7. AI WORKFORCE ATTENDANCE ADVISOR -->
    <div class="col-12 mb-5">
        <div class="present-card text-light shadow-sm">
            <div class="row g-0">
                <div class="col-lg-5 card-banner">
                    <img src="dokumentasi/19. Dashboard analitics Absensi.jpg" alt="Laporan Absensi & Lembur">
                    <span class="badge-ai">Kepegawaian & Anggaran</span>
                </div>
                <div class="col-lg-7 p-4">
                    <h4 class="fw-bold text-primary mb-2"><i class="fas fa-user-clock me-2"></i> AI HR & Workforce Attendance Advisor</h4>
                    <p class="text-secondary small">
                        <strong>Fungsi Utama:</strong> Menganalisis tingkat kehadiran dan kedisiplinan pegawai, mendeteksi risiko kelelahan (*Fatigue Risk*) akibat shift malam berurutan, melacak biaya pengajuan lembur per departemen dari tabel `pengajuan_lembur`, serta mengevaluasi efisiensi anggaran lemburan.
                    </p>
                    <div class="small">
                        <strong>Contoh Use Case:</strong> Menghitung total anggaran lemburan perawat IGD yang melonjak tinggi dan memetakan jadwal libur/cuti guna menyeimbangkan beban kerja staf agar terhindar dari fatigue medis.
                    </div>
                    <div class="sample-box mt-3">
                        <span class="text-warning small fw-bold"><i class="fas fa-terminal me-1"></i> Contoh Prompt Pimpinan:</span>
                        <p class="prompt-text mb-0">"Ulas beban biaya lemburan dan risiko kelelahan staf bulan ini. Departemen mana yang paling berisiko mengalami kelelahan kerja tinggi, dan bagaimana solusinya?"</p>
                    </div>
                    <div class="mt-3">
                        <span class="text-info small fw-bold"><i class="fas fa-robot me-1"></i> Output Analisis AI:</span>
                        <div class="output-box mt-1">
                            <p><strong>## Analisis Fatigue & Beban Anggaran Lembur Staf</strong></p>
                            <p>Ditemukan <strong>8 orang perawat IGD</strong> yang memiliki pola shift malam berturut-turut >= 3 kali (Tingkat Risiko Kelelahan: <strong>Tinggi</strong>). Pada saat yang sama, pengajuan biaya lembur IGD menyumbang <strong>42%</strong> dari total denda & pengeluaran lembur RS bulan ini.</p>
                            <p><strong>Rekomendasi Manajemen SDM:</strong> Lakukan rotasi jadwal dinas malam silang antar bangsal rawat inap untuk meratakan beban kerja fisik perawat, dan perketat syarat persetujuan lembur terencana (*scheduled overtime*) di tingkat kepala ruangan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 8. AI CASEMIX COST AUDITOR & EMR INSPECTOR -->
    <div class="col-12 mb-5">
        <div class="present-card text-light shadow-sm">
            <div class="row g-0">
                <div class="col-lg-5 card-banner">
                    <img src="dokumentasi/4. Plafon Ranap.jpg" alt="Casemix & MPP Cost Auditor">
                    <span class="badge-ai">Casemix & MPP Audit</span>
                </div>
                <div class="col-lg-7 p-4">
                    <h4 class="fw-bold text-primary mb-2"><i class="fas fa-file-invoice-dollar me-2"></i> AI Casemix Cost Auditor & MPP EMR Inspector</h4>
                    <p class="text-secondary small">
                        <strong>Fungsi Utama:</strong> Replikasi fitur MPP Casemix Cost Auditor dan EMR Inspector secara independen. Menganalisis deviasi/selisih Plafon INACBG vs Estimasi Riil Biaya pasien aktif rawat inap secara real-time, mendeteksi kelengkapan dokumen klaim BPJS (Resume Medis, Triase, ADIME, Asesmen, Laporan Operasi), serta menganalisis volume pemeriksaan penunjang (Lab & Radiologi) dari badge counter interaktif.
                    </p>
                    <div class="small">
                        <strong>Contoh Use Case:</strong> Manajer Pelayanan Pasien (MPP) memantau pasien aktif BPJS yang tagihannya sudah melampaui plafon INACBG, mendeteksi kelalaian pengisian SOAP dokter, dan melakukan intervensi efisiensi sebelum closing billing.
                    </div>
                    <div class="sample-box mt-3">
                        <span class="text-warning small fw-bold"><i class="fas fa-terminal me-1"></i> Contoh Prompt Pimpinan:</span>
                        <p class="prompt-text mb-0">"Ulas pasien aktif yang billing sementaranya melebihi plafon INACBG. Apa faktor pemicu pembengkakan biaya terbesar, dan bagaimana rekomendasi kendali mutu & kendali biaya bagi MPP?"</p>
                    </div>
                    <div class="mt-3">
                        <span class="text-info small fw-bold"><i class="fas fa-robot me-1"></i> Output Analisis AI:</span>
                        <div class="output-box mt-1">
                            <p><strong>## Audit Casemix & Efisiensi Cost Over-Limit</strong></p>
                            <p>Ditemukan pasien <strong>Ny. Aminah (No. Rawat: 2026/07/01-0004)</strong> memiliki estimasi biaya riil sebesar <strong>Rp 15.200.000</strong>, melampaui plafon INACBG sebesar <strong>Rp 12.000.000</strong> (Selisih Over: <strong>-Rp 3.200.000</strong>).</p>
                            <p><strong>Faktor Pemicu Utama:</strong> Pengulangan pemeriksaan laboratorium darah lengkap sebanyak 4 kali dan perpanjangan hari rawat (Length of Stay) akibat penundaan rilis resume medis oleh DPJP.</p>
                            <p><strong>Rekomendasi MPP & Casemix:</strong></p>
                            <ul>
                                <li>Lakukan utilisasi review atas order lab berulang yang tidak sesuai clinical pathway.</li>
                                <li>Minta DPJP segera melengkapi Resume Medis elektronik melalui integrasi EMR Inspector agar berkas klaim BPJS siap saji.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="alert alert-success d-flex align-items-center mt-5 mb-5 no-print" role="alert">
    <i class="fas fa-check-circle fa-2x me-3"></i>
    <div>
        <h6 class="fw-bold mb-1">Seluruh Integrasi AI Di Atas Siap Didemonstrasikan Secara Live!</h6>
        Silakan klik tombol **"Cetak Laporan Presentasi (PDF)"** di bagian atas halaman ini untuk mencetak dokumen cetak/fisik secara instan untuk diserahkan ke Manajemen.
    </div>
</div>

<?php include 'includes/footer.php'; ?>
