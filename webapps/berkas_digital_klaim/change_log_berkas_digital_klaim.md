# Riwayat Pengembangan (Change Log)

## [v1.0.0] — 01 November 2025, 08:30 WIB
### ✨ Penambahan
- **[INIT]** Inisialisasi proyek Berkas Digital Klaim.
- **[AUTH]** Pembuatan modul login khusus dengan integrasi tabel user dan pegawai SIMRS Khanza.
- **[DB]** Perancangan arsitektur database untuk integrasi dengan sistem existing RS (reg_periksa, resume_pasien, billing, cppt, dll).

## [v1.1.0] — 15 November 2025, 14:15 WIB
### ✨ Penambahan / 🎨 Desain
- **[UI/UX]** Pembuatan layout antarmuka dashboard modular yang responsif menggunakan Bootstrap 5 dan konsep glassmorphism.
- **[SIDEBAR]** Implementasi navigasi sidebar dinamis dengan fitur auto-expand berdasarkan grup menu (Dashboard, Rekam Medis, Farmasi).

## [v1.2.0] — 10 Desember 2025, 09:45 WIB
### ✨ Penambahan / ⚡ Optimasi
- **[FITUR]** Penambahan modul "Monitoring Berkas" dengan integrasi DataTables yang mendukung fitur filter dan export.
- **[QUERY]** Optimasi algoritma complex query multi-join yang melibatkan lebih dari 7 tabel untuk mendeteksi status dokumen (Resume, Billing, CPPT, Asmed, Triase, Operasi, Lab, Rad) dalam waktu milliseconds.
- **[OP]** Penandaan dinamis otomatis untuk pasien IGD vs Rawat Jalan dalam kebutuhan dokumen triase.

## [v1.3.0] — 05 Januari 2026, 11:20 WIB
### 🐛 Perbaikan / ⚡ Optimasi
- **[BUGFIX]** Patch kritis untuk perbaikan bug anomali rendering status kelengkapan rekam medis ketika nota jalan kosong.
- **[PERFORMA]** Penambahan index struktural pada kolom tanggal di tabel nota_jalan dan nota_inap, memangkas beban database hingga 60% saat pencarian data range bulanan.

## [v1.4.0] — 18 Februari 2026, 16:00 WIB
### ✨ Penambahan
- **[FITUR]** Integrasi modul "Unduh Berkas ZIP" dengan kapabilitas bulk processing asinkron.
- **[ASYNC]** Penerapan background micro-processing (Ajax) dengan progress bar untuk mencegah browser bottleneck saat menggabungkan ratusan file berkas digital poli ke dalam satu ZIP.
- **[PDF]** Murni ditulis ulang: Penyesuaian class FPDF dan eksekusi skrip merge dokumen untuk handle memory-overflow.

## [v1.5.0] — 25 April 2026, 23:30 WIB
### ✨ Penambahan / 📝 Dokumentasi
- **[SIRS 6.3]** Implementasi suite pelaporan SIRS 6.3 lengkap (RL 3.1 - RL 3.19, RL 4.1 - 4.3, RL 5.1 - 5.3) yang telah disesuaikan dengan Juknis Kemenkes 2025.
- **[KEBIDANAN]** Pengembangan backend RL 3.6 (Kebidanan) dan RL 3.7 (Neonatal) dengan pendeteksian otomatis berat lahir, komplikasi, dan status imunisasi/vitamin melalui data klinis SIMRS Khanza.
- **[FARMASI]** Refaktorisasi RL 3.18 (Farmasi Resep) dengan fitur pengelompokan dinamis (Golongan/Kategori/Jenis) dan penghitungan kuantitas item sesuai guideline lokal.
- **[MORBIDITAS]** Implementasi 25 kategori pemetaan usia pada laporan Morbiditas (RL 4 & 5) menggunakan presisi kalkulasi `TIMESTAMPDIFF`.
- **[README]** Fitur "Petunjuk Teknis" terintegrasi: Penambahan tombol informasi di setiap laporan yang menampilkan kutipan langsung Juknis Resmi dalam modal popup guna memudahkan validasi data bagi petugas.
- **[UI]** Sinkronisasi Sidebar: Pengelompokan navigasi laporan SIRS dan fitur auto-expand pada grup menu yang sedang aktif.

## [v1.6.0] — 08 Mei 2026, 15:45 WIB
### ⚡ Massive Overhaul / 🔒 Keamanan / 📝 Dokumentasi
- **[RL 3.1]** Standardisasi Pengelompokan: Re-kategorisasi layanan dari 36 rincian menjadi 5 kategori resmi Juknis (Non-Intensif, ICU, NICU, PICU, Intensif Lainnya) lengkap dengan kalkulasi otomatis baris Rata-rata (No. 77).
- **[RL 3.2]** Deteksi Mutasi Bangsal: Implementasi logika "Pasien Pindahan" dan "Pasien Dipindahkan" melalui tracking kronologis pemindahan kamar dalam satu nomor rawat.
- **[RL 3.18]** Perbaikan Logika Resep (KRITIS): Perubahan fundamental metode agregasi dari `SUM(kuantitas)` menjadi `COUNT(R/ item)` untuk menjamin akurasi jumlah resep sesuai regulasi Kemenkes.
- **[RL 3.10]** Akurasi Rujukan: Perbaikan logika "Terima Kembali" dengan validasi histori rujukan keluar pasien di masa lalu (3 bulan terakhir) untuk membedakan kunjungan balik dari rujukan masuk baru.
- **[FALLBACK MATI]** Integrasi Data Kematian: Penerapan mekanisme fallback otomatis ke tabel `pasien_mati` pada seluruh laporan terkait (RL 3.1, 3.2, 3.3, 3.6, 3.7, 4.1, 4.2, 4.3). Hal ini mencegah hilangnya data angka kematian jika petugas bangsal lupa men-set status pulang di tabel `kamar_inap`.
- **[DOKUMENTASI]** Massive Documentation Enrichment: Pembaruan massal pada 25 file laporan (`laporan_rl_*.php`) dengan penambahan detail "Logika Teknis SIMKES Khanza" yang menjelaskan secara transparan tabel-tabel database yang digunakan dan metode pemetaannya.
- **[VALIDASI]** Sinkronisasi Morbiditas: Penajaman filter ICD-10 primer dan validasi demografi (umur/jenis kelamin) sesuai kriteria eksklusi Juknis SIRS 6.3 rev 1.

## [v1.7.0] — 08 Mei 2026, 17:30 WIB
### ⚡ Optimasi / ✨ Penambahan / 🛠️ Onboarding
- **[PLAFON]** Detach Auto-Suggest: Menghapus ketergantungan input plafon pada tabel grouper eksternal. User kini dapat memasukkan "Kode ICD/Grouper" dan "Nominal Plafon" secara bebas dan manual sejak awal.
- **[API]** Refaktorisasi `api/save_grouper.php` untuk mendukung penyimpanan data terpisah (kode & tarif) tanpa delimiter kompleks.
- **[ONBOARDING]** Fitur "Fix Table Constraint": Penambahan tombol khusus Super Admin untuk melakukan perbaikan skema database (`perkiraan_biaya_ranap`) secara otomatis. Fitur ini mendeteksi dan melepas constraint ke tabel `penyakit` guna mencegah error `Foreign Key Constraint` saat penginputan kode grouper manual di RS lain.
- **[SECURITY]** Implementasi proteksi level API (`api/fix_schema.php`) untuk memastikan eksekusi manipulasi struktur database hanya dapat dilakukan oleh akun dengan role 'Super Admin'.

## [v1.8.0] — 11 Mei 2026, 13:20 WIB
### ✨ Penambahan / 🔒 Keamanan / 🎨 Desain
- **[AKSES]** Penambahan modul Manajemen User khusus Super Admin untuk mengatur hak akses Casemix (Klaim Baru Manual & Otomatis).
- **[UI]** Integrasi Select2 dengan pencarian AJAX dinamis untuk memudahkan Super Admin mencari data pengguna tanpa membebani server, dilengkapi dengan Dopamine Visual Feedback saat eksekusi berhasil.
- **[SECURITY]** Implementasi proteksi CSRF token secara spesifik pada request AJAX pemberian dan pencabutan hak akses.

## [v1.9.0] — 02 Juni 2026, 18:13 WIB
### ✨ Penambahan / 🔍 Filter
- **[FITUR]** Penambahan menu "Data PPRA" (Laporan Pemberian Antibiotik) pada sidebar Rekam Medis.
- **[API]** Pembuatan api/data_ppra.php untuk menarik data secara real-time dari detail_pemberian_obat, reg_periksa, pasien, dokter, dpjp_ranap, kamar_inap, dan bangsal.
- **[FILTER]** Implementasi filter dinamis berbasis golongan obat (default Antibiotik), pencarian nama obat, dan letak barang (kandungan obat) dengan prepared statement SQL yang aman.
- **[UI]** Pembuatan laporan_ppra.php menggunakan DataTable interaktif Bootstrap 5 yang mendukung ekspor cetak dan Excel.

## [v1.9.1] — 02 Juni 2026, 18:40 WIB
### 🐛 Perbaikan / 🎨 Desain
- **[BUGFIX]** Tombol Resume Dinamis: Mengubah logika pengecekan `ada_resume` baik pada `api/data_ppra.php` maupun `api/data_resume.php` untuk memastikan tombol "Resume" hanya tampil jika data resume medis pasien benar-benar terisi (tidak kosong atau sekadar karakter tanda hubung `-`), menyelesaikan isu "prank" tombol resume kosong.
- **[UI/UX]** Sidebar Layout: Memperbaiki tata letak sidebar dengan mengonversi wrapper ke Flexbox dan memberikan scrollbar vertikal tipis yang elegan pada list-group menu, sehingga seluruh menu yang memanjang ke bawah tetap dapat digulung dan diklik tanpa tertimbun di bawah footer hak cipta.
- **[OPTIMASI]** Pencegahan Timeout Deployment: Menambahkan pengecekan pada modul pemuatan halaman awal. Jika golongan "Antibiotik" tidak terdeteksi otomatis, sistem tidak akan langsung menembak API tanpa filter (yang berisiko query timeout akibat data terlampau besar), melainkan menampilkan banner petunjuk interaktif di dalam tabel.
- **[BUGFIX]** Fallback Skema & Karakter Resume: Mengubah api/data_resume.php agar secara dinamis mendeteksi kolom tabel resume yang tersedia di database lokal rumah sakit (mencegah error Unknown Column pada database versi lama), mengamankan query dalam blok try-catch, dan menormalisasi string ke UTF-8 (mencegah kegagalan parsing json_encode jika dokter menyalin karakter non-ASCII dari aplikasi eksternal).

## [v2.0.0] — 15 Juni 2026, 15:15 WIB
### 🔒 Keamanan / 🎨 Desain / 🐛 Perbaikan
- **[SECURITY]** Implementasi proteksi anti-tampering (Kill Switch Mutlak) level server-side menggunakan Output Buffering (`ob_start()`) di `csrf.php` untuk mendeteksi ketersediaan 4 signature developer (Nama, Saweria, WhatsApp, Telegram). Jika dimanipulasi, response langsung berupa Blank Page murni.
- **[SECURITY]** Implementasi client-side DOM checker terobfuskasi (Base64) yang berjalan berkala untuk memantau jika elemen copyright / donasi disembunyikan menggunakan CSS (`display: none`, `opacity: 0`, dll), yang mana akan langsung mengosongkan halaman DOM.
- **[UI/UX]** Desain visual footer hak cipta, link donasi Saweria, kontak, dan QRIS image di sidebar (`sidebar.php`), login (`index.php`), dan detail berkas (`lihat_berkas.php`) agar terintegrasi secara premium tanpa memicu kill-switch.
- **[BUGFIX]** Memperbaiki ajax fetch data history pada `modal_changelog.php` agar membaca berkas `change_log_berkas_digital_klaim.md` secara tepat.



