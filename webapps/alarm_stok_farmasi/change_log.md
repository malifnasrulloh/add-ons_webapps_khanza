## [v1.1.0] — 18 April 2026, 11:51 WIB
### 🔒 Keamanan / ⚡ Optimasi
- **[AUTH]** Mengimplementasikan sistem validasi login berbasis AES Decrypt sesuai standar SIMKES Khanza.
- **[SECURITY]** Menambahkan `auth.php` sebagai proteksi Zero-Trust pada seluruh halaman UI dan endpoint API.
- **[PDO]** Migrasi total dari ekstensi `mysqli` menjadi *PHP Data Objects* (PDO) untuk seluruh query database.
- **[CONFIG]** Merombak arsitektur `koneksi.php` agar Plug-and-Play mendeteksi otomatis letak `conf.php` dari parent direcories maupun server document root.
- **[UI]** Menambahkan tombol Logout beserta konfirmasi modal bergaya Bootstrap 5.

## [v1.1.1] — 18 April 2026, 12:06 WIB
### 🔒 Keamanan / 🎨 Desain
- **[SECURITY]** Mengaktifkan *Kill-Switch Anti-Tampering* di level server-side (`ob_start`) dan client-side (Obfuscated JS) untuk mengamankan atribusi Copyright dan link Donasi developer.
- **[UI]** Menambahkan footer horizontal dengan gaya *modern glassmorphism* terintegrasi ke seluruh halaman, komplit dengan *Modal Dukungan Donasi* yang mencantumkan pesan, kontak media sosial (WA & Telegram), dan gambar QRIS.
