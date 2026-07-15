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
- **[AUTH]** Migrasi otentikasi: Dashboard kini menggunakan Native Khanza Database Authorization. Akses kini mutlak dikontrol lewat field `harian_menejemen` and `bulanan_menejemen` pada tabel user (Dulu menggunakan tabel 'roles').
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
- **[AKUNTANSI]** Implementasi fitur **Audit Trail Drill-Down** lapis ganda pada modul `akuntansi_cashflow.php` and `akuntansi_keuangan.php`. Direksi kini bisa menelusuri transaksi buku besar dan rincian mutasi harian langsung dari klik angka di tabel ringkasan.
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
- **[COPYRIGHT]** Mendesain ulang perlindungan hak cipta (kill-switch) server-side pada `includes/header.php`. Callback `ob_start` dipindahkan ke dalam method `__destruct` kelas `ThemeEngineManager` secara stealthy (menghilangkan argumen string yang mencolok di `ob_start()`). Integrasi ini diperkuat dengan *compile-time structural dependency check* (`renderThemeMeta()`) di dalam tag `<head>` HTML yang akan menolak rendering (Fatal PHP Error) apabila didelete or dinonaktifkan oleh pembajak.

## [v1.5.6] — 18 Juni 2026, 12:30 WIB
### 🚀 Penambahan / 📊 Optimasi Kinerja
- **[WIDGETS]** Integrasi 31 metrik/indikator dari dashboard lama sebagai *Collapsible Metrics Block* di `dashboard.php` yang terbagi ke dalam 6 tab terstruktur. Menggunakan *on-demand AJAX loading* ke `/api/data_additional_widgets.php` agar beban query database tidak memperlambat waktu pemuatan awal halaman eksekutif.
- **[CHARTS]** Penambahan 2 grafik bar baru: *Top 10 Penggunaan Farmasi* dan *Top 10 Booking Pendaftaran Online*. Menggunakan ChartJS yang disuplai datanya dari pengembangan API `/api/data_dashboard.php`.
- **[PREMIUM KPI]** Pengenalan 3 metrik ringkasan eksekutif premium yang dimuat secara dinamis: Kepatuhan Bridging SatuSehat (MOH), Estimasi Total Nilai Aset Dead Stock Gudang Farmasi, dan Rata-rata Waktu Tunggu Layanan Poli Hari Ini, seluruhnya diproses aman menggunakan query PDO Prepared Statements.

## [v1.7.0] — 04 Juli 2026, 13:12 WIB
### ✨ Penambahan / 🔒 Keamanan
- **[LLM]** Integrasi penerjemah query SQL berbasis AI LLM pada modul Laporan Audit Trail dan penambahan menu Pengaturan LLM khusus untuk Super Admin.
- **[KEAMANAN]** Proteksi endpoint API penerjemah `/api/translate_audit.php` and `/api/config_llm.php` agar hanya dapat diakses oleh akun Super Admin secara ter-otentikasi.

## [v1.8.0] — 04 Juli 2026, 13:30 WIB
### ✨ Penambahan / 🎨 Desain
- **[LLM CONFIG]** Fitur **Ambil Model** otomatis berbasis AJAX dari endpoint `/v1/models` yang terintegrasi menggunakan datalist dinamis pada `setting_llm.php`.
- **[LLM AUDIT]** Fitur **Analisis Kolektif AI** pada `laporan_audit_trail.php` untuk merangkum dan menerjemahkan hingga **500 baris query log audit** sekaligus ke dalam Laporan Naratif Eksekutif komprehensif.
- **[CHAT ASSISTANT]** **AI Chat Assistant Panel** terintegrasi pada Dashboard Audit Trail untuk diskusi dua arah dan tanya jawab interaktif atas temuan log audit.
- **[EXPORT]** Fitur **Ekspor Laporan ke Word (.doc)** client-side native untuk mengunduh dokumen laporan final AI beserta riwayat diskusi.

## [v1.8.1] — 04 Juli 2026, 13:32 WIB
### ⚡ Optimasi / 🔒 Keamanan
- **[LLM FALLBACK]** Implementasi **Auto-switching Model Cadangan** (Fallback Chain) secara dinamis apabila model utama kehabisan token (Rate Limit/HTTP 429).
- **[LLM CONFIG]** Integrasi input **Model Cadangan** pada form Pengaturan LLM untuk penentuan prioritas fallback secara aman.

## [v1.8.2] — 04 Juli 2026, 13:48 WIB
### ⚡ Optimasi / 🐛 Perbaikan
- **[LLM API]** Penambahan opsi `'stream' => false` secara eksplisit pada payload cURL untuk mencegah response bertipe streaming secara default dari API Gateway.
- **[LLM API]** Implementasi parser fallback cerdas untuk **Server-Sent Events (SSE) Stream** (`data: {...}`) guna memastikan kompatibilitas penuh terhadap proxy gateway yang memaksa mode streaming.

## [v1.9.0] — 05 Juli 2026, 09:40 WIB
### ✨ Penambahan / 🎨 Desain
- **[LLM ARCHITECTURE]** Modularisasi system prompt. Menghilangkan ketergantungan prompt sistem global di `setting_llm.php` and memindikannya ke tingkat halaman terkait.
- **[LLM PROMPT TUNING]** Fitur **"Tune Prompt"** (Collapsible Textarea) pada `laporan_audit_trail.php` yang memungkinkan pengguna memodifikasi default prompt secara dinamis sebelum memicu analisis log.
- **[LLM API]** Backend `translate_audit.php` diperbarui untuk menerima, memprioritaskan, dan memproses `custom_prompt` secara fleksibel baik pada analisis kolektif maupun penerjemahan per baris.

## [v1.9.1] — 05 Juli 2026, 09:46 WIB
### ⚡ Optimasi / 🐛 Perbaikan
- **[TIMEOUT FIX]** Peningkatan batas waktu eksekusi skrip PHP (`set_time_limit(120)`) dan timeout cURL (`CURLOPT_TIMEOUT, 120`) untuk mencegah terputusnya respons LLM di tengah jalan pada batch data besar.
- **[NETWORK KEEPALIVE]** Penambahan `CURLOPT_TCP_KEEPALIVE` and `CURLOPT_CONNECTTIMEOUT` untuk mencegah terputusnya koneksi terowongan/tunnel (`abc-tunnel.us`) saat model melakukan kalkulasi tokens yang lambat.

## [v2.0.0] — 05 Juli 2026, 09:50 WIB
### ✨ Penambahan / ⚡ Optimasi
- **[LLM STREAMING]** Implementasi native **Server-Sent Events (SSE)** via `CURLOPT_WRITEFUNCTION` di PHP dan `ReadableStream` di `fetch()` API JS, sehingga antarmuka laporan AI dan Chat Assistant kini tampil progresif secara real-time seperti ChatGPT, menghapus waktu tunggu panjang (blank screen).
- **[TRUNCATION FIX]** Penghapusan batasan token sempit. Meningkatkan kapasitas batas model menjadi `max_tokens: 4096` and mengizinkan waktu memori skrip hingga `300` detik, memastikan analisis kolektif 500 baris log tidak pernah lagi terpotong di tengah kalimat.
- **[STREAM FALLBACK]** Sistem asinkron tetap mempertahankan fungsi auto-fallback model tanpa konflik, menunda streaming (HTTP header flushing) secara cerdas hingga memastikan model utama merespons tanpa error batas token (HTTP 200).

## [v2.1.0] — 05 Juli 2026, 10:15 WIB
### ✨ Penambahan / 🏢 Desain
- **[LLM GATEWAY]** Pembuatan backend universal `api/ai_analyzer.php` untuk memproses seluruh integrasi kecerdasan buatan dinamis di berbagai modul aplikasi, menyederhanakan serialization data mentah tabular secara asinkron.
- **[LLM FINANCE]** Integrasi modul **AI CFO Advisor** pada `akuntansi_dashboard.php` mencakup panel analisis performa keuangan (total pendapatan, pengeluaran, profit, margin, kas), prompt tuning terdesentralisasi, export laporan Word, dan AI Chat Assistant dual-context.

## [v2.2.0] — 05 Juli 2026, 10:20 WIB
### ✨ Penambahan / 📈 Operasional
- **[LLM RANAP]** Integrasi modul **AI Operations Advisor** pada `laporan_indikator_ranap.php` untuk menganalisis efisiensi tempat tidur rawat inap (BOR, ALOS, TOI, BTO, NDR, GDR) secara global, per bangsal, dan per kelas kamar.
- **[LLM TAT]** Integrasi modul **AI TAT Advisor** pada `laporan_waktu_tunggu.php` untuk mengurai alur antrean pelayanan pasien rawat jalan dari pendaftaran, periksa poli, penyiapan obat farmasi, hingga pembayaran kasir.
- **[SERIALIZER FIX]** Peningkatan fungsi `serialize_raw_data()` di `api/ai_analyzer.php` yang mendukung pendeteksian otomatis data bertingkat (nested array/JSON) sehingga tidak ada angka nominal finansial or operasional yang terlewat oleh LLM.

## [v2.3.0] — 05 Juli 2026, 10:25 WIB
### ✨ Penambahan / 📦 Inventaris & Supply Chain
- **[LLM DEAD STOCK]** Integrasi modul **AI Dead Stock Advisor** pada `laporan_dead_stock.php` untuk menganalisis aset obat/alkes mati yang mengendap di depo farmasi, lengkap dengan chat diskusi rencana retur/penyelamatan modal.
- **[LLM PROFIT FARMASI]** Integrasi modul **AI Profit Advisor** pada `laporan_proyeksi_keuntungan.php` untuk menganalisis laba penjualan obat (pasien BPJS/asuransi vs apotek bebas), profit margin, dan usulan strategi pricing obat.

## [v2.4.0] — 05 Juli 2026, 10:30 WIB
### ✨ Penambahan / 👥 Kepatuhan & SDM & Demografi
- **[LLM DOKTER]** Integrasi modul **AI SDM Medis Advisor** pada `laporan_kinerja_dokter.php` untuk menganalisis volume pelayanan (ralan/ranap) dan kontribusi billing per dokter spesialis/umum.
- **[LLM ERM AUDIT]** Integrasi modul **AI ERM Audit Advisor** pada `laporan_audit_erm_full.php` untuk menganalisis skor kepatuhan pengisian form EMR (Triase, SOAP, Resume, ADIME) serta kepatuhan dokter dan unit pelayanan secara dinamis.
- **[LLM PENYAKIT]** Integrasi modul **AI Morbiditas Advisor** pada `laporan_penyakit.php` untuk menganalisis tren demografi morbiditas (10 besar penyakit terbanyak), perbandingan umur dan jenis kelamin pasien.

## [v2.5.0] — 05 Juli 2026, 12:00 WIB
### ✨ Penambahan / 🤖 AI Advisor Eksekutif
- **[LLM PIUTANG]** Integrasi modul **AI Receivables & Billing Collector Advisor** pada `laporan_piutang_detail.php` untuk menganalisis umur piutang per penjamin dan memandu prioritas penagihan piutang macet.
- **[LLM CASH FLOW]** Integrasi modul **AI CASH FLOW & LIQUIDITY ADVISOR** pada `akuntansi_cashflow.php` & `akuntansi_cashflow_direct.php` untuk memproyeksikan likuiditas kas, mendeteksi warning defisit, dan menimbang timing belanja modal.
- **[LLM ANTROL]** Integrasi modul **AI BPJS Queue Compliance Advisor** pada `laporan_antrol_bpjs.php` untuk mengaudit pencapaian waktu tunggu Task 3-7 BPJS per poliklinik dan menghindari sanksi disinsentif.
- **[LLM DEMOGRAFI]** Integrasi modul **AI Patient Demographics & Marketing Advisor** pada `laporan_demografi.php` untuk menganalisis kepadatan spatial asal pasien vs jenis penjamin guna merancang program promosi / CSR tertarget.
- **[LLM FARMASI]** Integrasi modul **AI Pharmacy Stock & Purchase Advisor** pada `laporan_stok_farmasi.php` untuk melacak stok aktif, mengidentifikasi obat kritis/kritis depo, serta mengoptimalkan pengadaan agar modal tidak mengendap.
- **[LLM SATU SEHAT]** Integrasi modul **AI Satu Sehat Advisor** pada `laporan_satu_sehat.php` & `api/get_erm_satu_sehat.php` untuk mengaudit tingkat sinkronisasi data rekam medis elektronik (Encounter, Condition, TTV, Resep, Lab, Radiologi) dengan Kemenkes.
- **[LLM ABSENSI]** Integrasi modul **AI Workforce Advisor** pada `laporan_absensi.php` & `api/api_absensi.php` dengan penambahan pelaporan **Risiko Kelelahan (Fatigue)** dan **Biaya Lembur Staf**, serta analisis kepatuhan kedisiplinan eksekutif.

## [v2.6.0] — 05 Juli 2026, 19:10 WIB
### ✨ Penambahan / 🏥 Casemix & MPP Audit Integration
- **[CASEMIX AI & MPP]** Replikasi 100% fitur MPP Casemix Cost Auditor dan EMR Inspector pada `kunjungan_ranap.php` & `kunjungan_ralan.php` secara mandiri tanpa tergantung pada aplikasi Edokter.
- **[API LOKAL]** Pembuatan paket endpoint independen di `api/riwayat/` (`view_riwayat.php`, `view_lab.php`, `view_rad.php`, `modal_klaim_viewer.php`, `ajax_riwayat_*.php`, `ajax_ai_resume_suggest.php`, `llm_helper.php`, `auth_guard.php`) yang terhubung langsung ke `config/koneksi.php`.
- **[DATATABLES ENHANCEMENT]** Penambahan kolom Diagnosa Awal/Akhir, Counter Badge Lab & Radiologi Interaktif, Dokumen Klaim BPJS (Resume Medis, Triase, Asesmen, Operasi), serta indikator Lama Rawat Inap.
- **[UI/UX DARK MODE]** Penyesuaian penuh gaya visual Glassmorphism Dark Mode (`.theme-glass-solid` & `.theme-glass-animated`) pada modal Riwayat Pasien, grafik TTV ApexCharts, serta penambahan proteksi kontras lembar cetak dokumen BPJS (teks hitam mutlak di atas kertas putih bersih).

## [v2.7.0] — 07 Juli 2026, 12:32 WIB
### ✨ Penambahan / 🐛 Perbaikan / 🎨 Desain
- **[LLM KUNJUNGAN]** Integrasi panel AI Patient Volume & Marketing Advisor pada `laporan_kunjungan.php` untuk analisis tren demografi dan volume pasien.
- **[ANTROL BPJS]** Penambahan indikator visual baris merah transparan dan warning badge `Delay T3->T4` untuk mendeteksi keterlambatan pelayanan dokter (Task 3 ke Task 4) di atas 60 menit pada `laporan_antrol_bpjs.php`.
- **[PENUNJANG MEDIS]** Rilis modul baru `laporan_penunjang.php` untuk dashboard analisis Laboratorium & Radiologi lengkap dengan KPI scorecard pendapatan, grafik Chart.js Top 10, data detail, serta AI Diagnostics Advisor.
- **[PENGADAAN FARMASI]** Rilis modul baru `laporan_pengadaan.php` untuk dashboard pengadaan farmasi & vendor analytics lengkap dengan KPI scorecard belanja/utang, pareto belanja suplier, serta AI Procurement Advisor.
- **[SIDEBAR]** Registrasi menu "Penunjang Medis" dan "Pengadaan & Vendor" pada konfigurasi menu `config/sidebar_menu.json`.

## [v2.7.1] — 07 Juli 2026, 12:53 WIB
### ⚡ Optimasi / 🐛 Perbaikan / 🏢 Concurrency
- **[SESSION CONCURRENCY]** Menyelesaikan bug kritis *UI lockup / concurrent tab hanging* di mana tab baru tidak merespons jika user memicu analisis AI di tab lain. 
- **[FIX]** Penerapan `session_write_close()` sedini mungkin setelah validasi hak akses pada API-API berdurasi panjang, membebaskan read-write lock session file sehingga tab browser lain dapat berjalan secara asinkronus tanpa hambatan. Perbaikan mencakup:
  1. `api/ai_analyzer.php` (Universal AI Advisor Gateway)
  2. `api/translate_audit.php` (AI Audit Trail Advisor)
  3. `api/riwayat/ajax_ai_resume_suggest.php` (AI Resume Medis Suggester)

## [v2.7.2] — 07 Juli 2026, 14:36 WIB
### ✨ Penambahan / 🎨 Desain
- **[ANALISA LENGKAP]** Peningkatan Laporan Analisa Data Lengkap (`laporan_analisa_lengkap.php`) dengan menambahkan kolom baru **Komponen Obat** dan **Komponen Tindakan** sebelum kolom Total Biaya.
- **[API LOKAL]** Modifikasi kueri pada `api/data_analisa_lengkap.php` untuk mengkalkulasi komponen obat (termasuk potongan retur obat) dan komponen tindakan (jasa medis, laborat, radiologi, operasi, registrasi) secara terpisah per transaksi pasien.
- **[UI/UX]** Menambahkan kalkulasi rekapitulasi total halaman footer untuk masing-masing komponen biaya baru dan pembaruan format ekspor Excel/Print agar sinkron.

## [v2.7.3] — 07 Juli 2026, 15:19 WIB
### ✨ Penambahan / 🎨 Desain
- **[PROYEKSI PROFIT]** Peningkatan Laporan Proyeksi Keuntungan Obat (`laporan_proyeksi_keuntungan.php`) dengan menyertakan filter jenis kunjungan **Rawat Jalan** and **Rawat Inap** secara terpadu.
- **[API LOKAL]** Modifikasi `api/data_proyeksi_keuntungan.php` untuk mendukung subquery penentuan **Asal Resep** (nama poliklinik jika ralan / nama bangsal jika ranap) dan **Dokter Peresep** (melalui resep_obat dengan fallback attending doctor).
- **[UI/UX TABBED LAYOUT]** Merombak layout tabel penjualan dari side-by-side menjadi tabbed layout card untuk efisiensi ruang kerja eksekutif, dan sinkronisasi data sample baru ini dengan AI Profit Advisor.

## [v2.8.0] — 07 Juli 2026, 15:30 WIB
### ✨ Penambahan / 📊 Optimasi Kinerja
- **[RUJUKAN MASUK]** Peluncuran modul baru `laporan_rujukan.php` untuk Dashboard Analisis Rujukan Masuk (Incoming Referrals Marketing Tracker) lengkap dengan visualisasi KPI, grafik Chart.js Top 10 Faskes, dan AI Market Advisor.
- **[GIZI PASIEN]** Peluncuran modul baru `laporan_gizi.php` untuk Dashboard Manajemen Gizi & Diet Pasien Ranap (Clinical Inpatient Nutrition Auditor) lengkap dengan visualisasi KPI porsi makanan, grafik diet klinis, dan AI Dietetics Advisor.
- **[API LOKAL]** Pembuatan backend API `api/data_rujukan.php` dan `api/data_gizi.php` menggunakan kueri PDO Prepared Statements yang aman dan teroptimasi, dilengkapi `session_write_close()` untuk performa asinkronus yang bebas hambatan.
- **[SIDEBAR]** Pendaftaran kedua modul pelaporan baru di dalam `config/sidebar_menu.json` pada kelompok Statistik & Indikator.

## [v2.8.1] — 07 Juli 2026, 16:40 WIB
### 🐛 Perbaikan
- **[LLM AUDIT]** Perbaikan bug pagination pada `laporan_audit_trail.php` di mana data log audit yang dikirim ke AI terbatasi hanya pada halaman aktif. Ekstraksi data dirubah dari manipulasi visual DOM node (`this.node()`) ke pembacaan memory cache DataTables (`this.data()`) dan diparsing dinamis via jQuery, sehingga 100% data log pada seluruh halaman datatable ter-filter berhasil dikirim ke AI secara utuh.

## [v2.8.2] — 07 Juli 2026, 16:51 WIB
### ⚡ Optimasi / 🐛 Perbaikan
- **[LLM ASSISTANT]** Menghapus seluruh pembatasan slicing data client-side (.slice(0, N)) di 19 file laporan dan menaikkan limit pemrosesan data backend api/ai_analyzer.php menjadi 10.000 baris. Perubahan ini memastikan AI Assistant menerima data 100% lengkap dari filter yang terpilih dan menjawab pertanyaan statistik/agregatif secara presisi di seluruh modul dashboard eksekutif.

## [v2.8.3] — 07 Juli 2026, 17:05 WIB
### ⚡ Optimasi / 🐛 Perbaikan
- **[ANTI-TIMEOUT]** Implementasi mekanisme **SSE Keepalive Heartbeat** pada `api/ai_analyzer.php` menggunakan `curl_multi` untuk mencegah error HTTP 524 (Cloudflare/Proxy timeout) saat AI memproses konteks data berukuran besar. Backend kini mengirimkan SSE headers dan event `thinking` secara instan sebelum memanggil LLM, lalu mengirimkan komentar SSE heartbeat (`: ping`) setiap 20 detik selama fase prefill LLM berlangsung.
- **[UX INFORMATIF]** Penambahan komponen UI `buildThinkingHTML()` pada 24 modul laporan yang terintegrasi AI. Saat AI sedang membaca konteks data besar, UI menampilkan progress bar animasi, pesan deskriptif, dan peringatan untuk dataset di atas 500 baris — sehingga user mengetahui proses sedang berjalan dan tidak mengira aplikasi hang.
