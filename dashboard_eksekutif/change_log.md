## [v1.0.0] — 10 Januari 2025, 08:00 WIB
### 🚀 Inisialisasi Arsitektur
- **[INIT]** Kick-off proyek pembuatan Dashboard Eksekutif Dashboard Keuangan.
- **[UI/UX]** Perancangan kerangka dasar antarmuka (Layout & Navigasi) berbasis Bootstrap 5.
- **[AUTH]** Implementasi modul login eksekutif dengan keamanan enkripsi sersi dan validasi brute-force.

## [v1.0.1] — 20 Januari 2025, 09:30 WIB
### 🚀 Penambahan
- **[KEUANGAN]** Modul **Laporan Kas** pertama kali diaktifkan. Memberikan visibilitas saldo kas masuk dan keluar secara real-time.

## [v1.1.0] — 05 Februari 2025, 11:20 WIB
### 🚀 Penambahan
- **[KEUANGAN]** Modul **Laporan Tunai** dirilis. Memonitor setoran kas harian per shift kasir untuk mencegah diskrepansi sirkulasi uang di loket.

## [v1.2.0] — 20 Februari 2025, 14:15 WIB
### 🚀 Penambahan
- **[KEUANGAN]** Penambahan fitur **Detail Billing**. Kini dashboard sanggup menampilkan rincian item-item transaksi per nota pasien secara rinci.

## [v1.3.0] — 10 Maret 2025, 10:45 WIB
### 🚀 Penambahan
- **[BIAYA]** Pengembangan modul **Billing Ralan (Rawat Jalan)**. Memetakan pendapatan per poliklinik dan memantau pergerakan omzet harian poli eksekutif.

## [v1.4.0] — 25 Maret 2025, 16:30 WIB
### 🚀 Penambahan
- **[BIAYA]** Peluncuran modul **Billing Ranap (Rawat Inap)**. Menyajikan analisa beban biaya pengobatan per kelas kamar dan monitoring deposit pasien aktif.

## [v1.5.0] — 15 April 2025, 13:00 WIB
### 🚀 Penambahan
- **[KEUANGAN]** Modul **Laporan Piutang** diaktifkan. Menyediakan rekapitulasi tagihan yang belum tertagih dari berbagai penjamin (asuransi/perusahaan) dalam satu tampilan summary.

## [v3.6.0] — 30 Maret 2026, 11:30 WIB
### 🚀 Optimasi & 🔒 Keamanan
- **[KEMANAN]** Implementasi prinsip **Zero-Trust Security**. Menutup celah SQL Injection, menambahkan proteksi *Brute-Force Login* (lockout otomatis), enkripsi Session Ketat, serta menyuntikkan *HTTP Security Headers* lapis ganda.
- **[SISTEM API]** Peluncuran sistem *Auth Guard* otomatis di seluruh 37+ _endpoint_ API menggunakan directive `auto_prepend_file`, memblokir akses publik secara sepihak.
- **[UI/UX]** Rilis **Premium Theme Engine**. Pengguna kini dapat memilih 3 gaya visual: _Bright Bootstrap_ (Klasik), _Glass Solid_ (Gelap Nyaman), hingga _Glass Animated_ (Gradien Dinamis Futuristik) yang langsung mensinkronisasikan warna seluruh grafik *Chart.js* tanpa menyegarkan halaman.
- **[COPYRIGHT]** Penerapan mekanisme Proteksi Anti-Pembajakan mutlak (Rule #17) dengan kombinasi Server-Side (*ob_start*) ganda dan Client-Side Obfuscation, menjamin perlindungan kekayaan intelektual kreator secara penuh.

## [v3.7.0] — 30 Maret 2026, 18:25 WIB
### 🚀 Penambahan / 🔥 Optimasi
- **[DOKUMENTASI]** Peluncuran **Buku Sakti v2.0** yang terintegrasi langsung ke dalam Sidebar Dashboard. 
- **[MODAL]** Implementasi *Interactive Documentation Modal* dengan teknologi *Dynamic Markdown Rendering* (Regex-based).
- **[UI/UX]** Fitur **Module Accordion**: Panduan kini tersaji secara collapsible per kategori (Keuangan, Farmasi, HRD, dll) untuk navigasi yang lebih efisien.
- **[MEDIA]** Penambahan 50+ tangkapan layar (screenshots) High-Resolution ke dalam panduan sebagai referensi visual langkah-demi-langkah.
- **[FITUR]** Implementasi fungsi **Print Dokumentasi** yang ringkas dan ramah printer untuk kebutuhan manual fisik.

## [v3.7.1] — 31 Maret 2026, 07:58 WIB
### 🛠️ Perbaikan
- **[UI/UX]** Perbaikan tombol hamburger sidebar toggle yang tidak responsif di Desktop dan Mobile. Fix meliputi: penambahan type="button" eksplisit, peningkatan z-index navbar ke 1040/1050, dan penggunaan window.addEventListener('load') + e.stopPropagation() untuk memastikan event listener terpasang setelah semua resource selesai dimuat.
- **[DEPLOY]** Perbaikan api/.htaccess: Mengganti path absolut Windows hardcoded dengan path relatif agar API Auth Guard berjalan di server Linux Ubuntu.

## [v1.4.0] — 05 April 2026, 20:32 WIB
### 🚀 Penambahan / 🔒 Keamanan
- **[AUTH]** Migrasi otentikasi: Dashboard kini menggunakan Native Khanza Database Authorization. Akses kini mutlak dikontrol lewat field `harian_menejemen` dan `bulanan_menejemen` pada tabel user (Dulu menggunakan tabel 'roles').
- **[UI]** Manage Users UI telah dirubah fungsinya untuk memperbolehkan Super Admin langsung mengubah kolom harian_menejemen & bulanan_menejemen via pencarian nama dan update database.

## [v1.4.1] — 05 April 2026, 20.36 WIB
### 🛠️ Perbaikan / 🚀 Optimasi
- **[AUTH]** Perbaikan bug tombol 'Cabut' yang tidak berfungsi pada user non-pegawai dengan menggunakan validasi AES_DECRYPT di sisi database.
- **[UI]** Optimasi Select2: Mematikan interceptor AJAX loading global (global:false) saat pencarian nama pegawai agar tidak muncul overlay yang mengganggu pengetikan.

## [v1.4.2] — 26 April 2026, 02:00 WIB
### 🛠️ Perbaikan / 🚀 Penambahan
- **[BPJS]** Perbaikan pada menu `laporan_antrol_bpjs.php` terkait penambahan data-data penting untuk menunjang penelusuran data anomali task ID. Penambahan mencakup data jam praktek, kuota praktek, serta detail nomor SEP, nomor booking, dan nama poli.

## [v1.5.0] — 26 April 2026, 02.05 WIB
### 🚀 Fitur Detektif & 📊 Optimasi Audit Akuntansi
- **[AKUNTANSI]** Implementasi fitur **Audit Trail Drill-Down** lapis ganda pada modul `akuntansi_cashflow.php` dan `akuntansi_keuangan.php`. Direksi kini bisa menelusuri transaksi buku besar dan rincian mutasi harian langsung dari klik angka di tabel ringkasan.
- **[UI/UX]** Perombakan visual tabel akuntansi untuk kenyamanan membaca dataset masif (Anti-Kejang Mata). Penerapan *font monospace*, *color-coded columns* (Biru untuk Debet, Hijau untuk Kredit, Ungu untuk Saldo), serta pemudaran nilai nol/strip.
- **[BUGFIX]** Menuntaskan *nested layout bug* (jarak 260px) yang merusak responsivitas sidebar di 5 modul akuntansi.
## [v1.5.0] — 27 April 2026, 08.31 WIB
### ✨ Penambahan
- **[Indikator Ranap]** Menambahkan tab Laporan Per Kelas dengan pengelompokan khusus untuk Intensive, Isolasi, dan Bed Bayi untuk mencegah double perhitungan sesuai standar Kemenkes.
## [v1.5.1] — 27 April 2026, 08.34 WIB
### 🎨 Desain
- **[Indikator Ranap]** Memperjelas keterangan kriteria filtering pada bagian Catatan tab Per Kelas untuk memudahkan dokumentasi teknis.

## [v1.5.2] — 02 Mei 2026, 13:41 WIB
### ✨ Penambahan
- **[SISTEM]** Implementasi `brain.md` sebagai memori kolektif agen AI. Berisi pemahaman komprehensif tentang arsitektur, keamanan (Zero-Trust), otentikasi Khanza Style (AES), migrasi hak akses native, dan standarisasi API sistem dashboard_eksekutif.

## [v1.6.0] — 02 Mei 2026, 14:00 WIB
### ✨ Penambahan
- **[AKUNTANSI]** Rilis fitur Accounting Executive Summary dengan visualisasi KPI Scorecard, Revenue vs Expense Trend (12 bulan), dan Net Profit Margin Chart.

## [v1.6.1] — 02 Mei 2026, 14:15 WIB
### ✨ Penambahan
- **[AKUNTANSI]** Peluncuran fitur OpEx Deep Dive yang mendemonstrasikan Pareto Chart untuk membedah 15 *cost center* tertinggi (Top Expenses). Membantu Direksi mengidentifikasi efisiensi biaya secara cepat.

## [v1.6.2] — 02 Mei 2026, 14:30 WIB
### ✨ Penambahan
- **[AKUNTANSI]** Implementasi modul Analisis Rasio Keuangan (Financial Ratios). Otomatis mengkalkulasi Current Ratio, Debt to Equity Ratio (DER), Net Profit Margin, ROA, dan ROE untuk mengevaluasi kesehatan likuiditas, solvabilitas, dan profitabilitas RS.

## [v1.6.3] — 02 Mei 2026, 14:45 WIB
### ✨ Penambahan
- **[AKUNTANSI]** Rilis Neraca Saldo Visual dengan Heatmap Aktivitas Rekening. Menyediakan ringkasan saldo yang diperkaya dengan visualisasi volume transaksi (progress bar), mempercepat deteksi akun paling aktif.

## [v1.6.4] — 02 Mei 2026, 15:00 WIB
### ✨ Penambahan
- **[AKUNTANSI]** Fitur Analisis Komparatif (Period-over-Period) diluncurkan. Direksi dapat dengan mudah membandingkan realisasi Pendapatan, Biaya, dan Laba Bersih secara *Month-over-Month* (MoM) maupun *Year-over-Year* (YoY).

## [v1.6.5] — 02 Mei 2026, 15:15 WIB
### ✨ Penambahan / UX
- **[AKUNTANSI]** Interaktivitas Grafik Arus Kas: Pengguna kini dapat mengklik segmen Donut Chart pada menu Cash Flow untuk memfilter tabel rincian transaksi secara instan (Misal: klik "Kas Masuk" akan menyembunyikan "Kas Keluar" dan sebaliknya).

## [v1.6.6] — 02 Mei 2026, 15:30 WIB
### ✨ Penambahan
- **[AKUNTANSI]** Rilis Laporan Arus Kas Langsung (Direct Cash Flow). Modul baru ini menyajikan visualisasi Inflow vs Outflow dengan *Top 10* sumber penerimaan dan tujuan pengeluaran kas terbesar secara fungsional berdasarkan *opposing accounts* dalam jurnal transaksi.

## [v1.6.7] — 02 Mei 2026, 18:25 WIB
### 🐛 Perbaikan
- **[UI/UX]** Memperbaiki bug "teks putih pada background putih" pada modal Riwayat Pengembangan Sistem (Changelog) saat menggunakan tema Glassmorphism (Dark Mode). Penyesuaian CSS dilakukan pada `includes/header.php` agar warna teks dan background timeline beradaptasi secara dinamis dengan mode gelap.

## [v1.6.9] — 08 Mei 2026, 11:45 WIB
### ✨ Penambahan / 🔒 Keamanan
- **[LAPORAN]** Penambahan filter Poliklinik pada Laporan Kunjungan Pasien untuk Rawat Jalan dan Rawat Inap (Asal Poli).
- **[FILTER]** Dropdown Poliklinik dan Penjamin kini hanya menampilkan data aktif (`status = '1'`).
- **[KEAMANAN]** Refaktor database logic secara mutlak dari MySQLi ke eksekusi PDO dengan pola Prepared Statements (Sesuai Aturan Zero-Trust & Remediasi Keamanan). Migrasi meliputi:
  1. Modul laporan kunjungan & API grafiknya.
  2. `api/data_detail_operasi.php`
  3. `api/get_erm_satu_sehat.php`
  4. `api/hitung_estimasi_ralan.php`
  5. `api_absensi.php`
  6. `api/data_kunjungan_ralan.php`
  7. 14 file API tambahan: `ajax_pegawai.php`, `akuntansi_jurnal_detail.php`, `data_dashboard.php`, `data_dead_stock.php`, `data_demografi.php`, `data_hutang_obat.php`, `data_indikator_per_bangsal.php`, `data_indikator_per_kelas.php`, `data_indikator_ranap.php`, `data_jasa_medis.php`, `data_laporan_tindakan.php`, `data_stok_farmasi.php`, `data_waktu_tunggu.php`, `db_migrate_jadwal.php`.
- **[UI/UX]** Penambahan alert informasi pada tab Rawat Inap yang menjelaskan kriteria filter poliklinik berdasarkan Asal Poli/IGD.
## [v1.5.2] — 05 Juni 2026, 15.25 WIB
### 🐛 Perbaikan
- **[Dead Stock]** Memperbaiki bug kritis di mana obat fast moving tetap muncul di laporan dead stock akibat kesalahan referensi enum posisi = 'Keluar'. Logika diubah menggunakan pendeteksian pengeluaran fisik keluar > 0.

## [v1.5.3] — 18 Juni 2026, 11:45 WIB
### ⚡ Optimasi / 🔒 Keamanan
- **[SISTEM]** Melakukan upgrade pada `includes/functions.php` agar fungsi-fungsi inti (`getShiftTimes`, `cariIsiAngka`, `cariIsi`) mendukung koneksi database PDO dan MySQLi secara polimorfik (Polymorphic DB Helpers).
- **[DOKUMENTASI]** Membuat laporan analisis arsitektur dan kualitas kode `onboarding_analysis.md` yang merangkum diagram data flow, identifikasi N+1 query bottleneck, dan strategi refaktoring.

## [v1.5.4] — 18 Juni 2026, 12:10 WIB
### 🐛 Perbaikan / ✨ Penambahan
- **[EXCEL]** Memperbaiki penanganan kolom Plafon, Est. Biaya, dan Selisih saat diexport ke Excel pada `kunjungan_ranap.php` (serta kolom Biaya Obat dan Total Tagihan pada `kunjungan_ralan.php`). Logika diubah untuk membaca nilai tekstual langsung dari visual DOM node (`node.textContent`) untuk menghindari interferensi ID rawat dari tag HTML skeleton loader.
- **[DPJP]** Meng-upgrade resolusi kolom DPJP/Dokter pada list rawat inap (`api/data_kunjungan_ranap.php` dan `api/hitung_estimasi_ranap.php`) agar mengambil 1 DPJP terakhir dari tabel `dpjp_ranap` (diurutkan berdasarkan `kd_dokter DESC`), dengan fallback otomatis ke dokter utama di tabel `reg_periksa` jika DPJP kosong.

## [v1.5.5] — 18 Juni 2026, 12:15 WIB
### 🔒 Keamanan / ⚙️ Refaktor
- **[COPYRIGHT]** Mendesain ulang perlindungan hak cipta (kill-switch) server-side pada `includes/header.php`. Callback `ob_start` dipindahkan ke dalam method `__destruct` kelas `ThemeEngineManager` secara stealthy (menghilangkan argumen string yang mencolok di `ob_start()`). Integrasi ini diperkuat dengan *compile-time structural dependency check* (`renderThemeMeta()`) di dalam tag `<head>` HTML yang akan menolak rendering (Fatal PHP Error) apabila didelete atau dinonaktifkan oleh pembajak.

## [v1.5.6] — 18 Juni 2026, 12:30 WIB
### 🚀 Penambahan / 📊 Optimasi Kinerja
- **[WIDGETS]** Integrasi 31 metrik/indikator dari dashboard lama sebagai *Collapsible Metrics Block* di `dashboard.php` yang terbagi ke dalam 6 tab terstruktur. Menggunakan *on-demand AJAX loading* ke `/api/data_additional_widgets.php` agar beban query database tidak memperlambat waktu pemuatan awal halaman eksekutif.
- **[CHARTS]** Penambahan 2 grafik bar baru: *Top 10 Penggunaan Farmasi* dan *Top 10 Booking Pendaftaran Online*. Menggunakan ChartJS yang disuplai datanya dari pengembangan API `/api/data_dashboard.php`.
- **[PREMIUM KPI]** Pengenalan 3 metrik ringkasan eksekutif premium yang dimuat secara dinamis: Kepatuhan Bridging SatuSehat (MOH), Estimasi Total Nilai Aset Dead Stock Gudang Farmasi, dan Rata-rata Waktu Tunggu Layanan Poli Hari Ini, seluruhnya diproses aman menggunakan query PDO Prepared Statements.
