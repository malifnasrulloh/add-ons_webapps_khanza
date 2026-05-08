# 🚀 Release Notes: Modul Akuntansi & Reporting (Dashboard Eksekutif v3.6.0+)

Halo rekan-rekan penggiat SIMKES Khanza! 👋
Kami dengan bangga merilis pembaruan masif untuk **Modul Akuntansi & Reporting** di Dashboard Eksekutif. Pembaruan ini difokuskan pada **Akurasi Tingkat Dewa**, **Transparansi Audit**, dan **Kemudahan Penelusuran Data (Drill-Down)**.

Kini, manajemen rumah sakit dan direktur bisa melakukan audit keuangan hanya dengan bermodalkan klik-klik di dashboard, tanpa perlu buka database sama sekali!

Berikut adalah fitur-fitur unggulan pada pembaruan kali ini:

---

## 🌟 1. Helicopter View: Fitur Drill-Down 4 Level (Audit Transparan)
Selamat tinggal proses pencarian data manual! Kami telah membangun sistem *Drill-Down* (klik untuk melihat rincian) yang sangat intuitif, mengadaptasi gaya aplikasi ERP modern.

Alur penelusuran dari atas hingga ke akar transaksi:
- **Level 1 (Dashboard Utama)**: Tampilan rekapitulasi (Rasio Keuangan, Cashflow, Neraca Saldo).
- **Level 2 (Buku Besar)**: Klik pada baris akun mana saja, maka akan muncul Modal/Pop-up rincian **Buku Besar**, lengkap dengan Saldo Awal, Mutasi Debet/Kredit, dan Saldo Akhir pada periode tersebut.
- **Level 3 (Detail Jurnal)**: Klik pada baris di Buku Besar, maka akan muncul **Detail Jurnal** yang memperlihatkan rincian transaksi harian yang membentuk angka tersebut (Pastikan Debet & Kredit Balance!).
- **Level 4 (Trace No Bukti)**: Lacak nomor bukti transaksi untuk dicocokkan dengan fisik kuitansi kasir.

## 🎯 2. Optimalisasi Akurasi Matematika (Zero Error Margin)
Pernah mendapati angka mutasi di laporan tiba-tiba bengkak jadi **Miliaran** atau **Triliunan** padahal rumah sakit belum sebesar itu? Itu adalah *Integer Overflow Bug* akibat duplikasi baris saat melakukan `LEFT JOIN` pada database!

Pada rilis ini, kami telah:
- **Merombak Total Logic Query SQL**: Memisahkan perhitungan `Saldo Awal` dan `Mutasi` dalam dua sub-query yang terisolasi. Hasilnya? Akurasi absolut hingga ke satuan perak terakhir (Zero Margin of Error).
- **Presisi Waktu (00:00:00 s/d 23:59:59)**: Menyempurnakan filter rentang tanggal (*Between*) agar tidak ada satupun transaksi di penghujung malam yang terlewat atau tidak masuk hitungan.
- **Performa Kilat**: Penggunaan query langsung ke tabel `rekeningtahun`, `jurnal`, dan `detailjurnal` dengan index yang tepat membuat loading data jutaan baris menjadi hitungan detik.

## ⚖️ 3. Keselarasan Data Laba-Rugi & Rasio Keuangan
Modul *Akuntansi Rasio* kini telah tersinkronisasi penuh dengan perhitungan di Buku Besar. 
Total Beban (Expense) dan Pendapatan kini terkalibrasi secara otomatis dari histori jurnal asli, sehingga direktur dapat melihat rasio profitabilitas yang sesungguhnya dan siap dipertanggungjawabkan dalam Rapat Evaluasi.

---

## 🎁 BONUS: Superadmin DB Migration (Modul Absensi)
Bagi RS yang memodifikasi jadwal pegawainya di Khanza, seringkali **Laporan Absensi** menjadi *error* atau nge-blank ketika ada pegawai yang jadwalnya `"Libur"` atau `"Cuti"`.

Kami menyertakan **Fitur Migrasi Schema DB (One-Click)** khusus untuk Superadmin di menu Laporan Absensi:
- Tidak perlu lagi buka *HeidiSQL* atau *PhpMyAdmin* untuk memperbaiki *database*.
- Cukup buka **Laporan Absensi**, klik tombol **"Migrasi Schema DB"**, dan sistem otomatis akan melakukan `ALTER TABLE` pada `jadwal_pegawai`, `jadwal_tambahan`, `rekap_presensi`, dan `temporary_presensi` untuk menambahkan nilai ENUM `'Libur'` dan `'Cuti'`.
- Sangat aman, panel ini dilindungi proteksi Session dan hanya muncul untuk Role Super Admin!

---

### 💡 Cara Update & Penggunaan
1. Timpa/Replace folder `api` dan file `.php` terkait di server dashboard Anda.
2. Login ke dashboard menggunakan akun dengan hak akses **Menejemen** (Centang di Khanza).
3. Buka menu **Akuntansi** (Neraca Saldo / Cashflow / Rasio Keuangan).
4. Arahkan *mouse* ke tabel. Baris yang bisa diklik akan memiliki efek *hover* (berubah warna). Klik baris tersebut untuk mencoba fitur *Helicopter View*!

Terima kasih atas dukungannya. Mari terus jadikan Khanza lebih canggih, presisi, dan transparan! 🚀

---
*Dikembangkan oleh Ichsan Leonhart & Antigravity AI*
