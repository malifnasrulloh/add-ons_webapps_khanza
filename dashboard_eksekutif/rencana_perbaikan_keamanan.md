# Rencana Induk Perbaikan Keamanan (Security Remediation Master Plan)

**Dokumen ini ditujukan untuk Agen AI (AI Coder/Agent). Harap baca dan pahami seluruh instruksi ini sebelum memulai proses refactoring kode pada aplikasi.**

## 1. Ringkasan Eksekutif (Executive Summary)
Berdasarkan hasil pemindaian kode pada *codebase* `dashboard_eksekutif` (terutama di direktori `/api/`), ditemukan banyak kerentanan kritis tipe **SQL Injection (CWE-89)** pada file-file *legacy*. Meskipun beberapa modul baru (Akuntansi) telah mematuhi prinsip *Zero-Trust Security* dan menggunakan ekstensi PDO, mayoritas modul pelaporan lainnya masih menggunakan `MySQLi` dengan pola penggabungan string (String Concatenation) yang sangat berbahaya.

Sesuai dengan `.antigravityrules` Aturan #0 dan #11:
- Sistem **WAJIB** kebal terhadap SQL Injection.
- Semua operasi database **WAJIB 100% menggunakan ekstensi PDO (PHP Data Objects)** dengan *Prepared Statements*.

## 2. Identifikasi Kerentanan Utama

### A. SQL Injection (Kritis)
Ditemukan lebih dari 100 instansi penggunaan metode `$koneksi->query()` atau `$conn->query()` yang menggabungkan variabel input secara langsung (rentan SQLi).
**Contoh Temuan (Bukti Nyata):**
- `api/data_kunjungan_ralan.php`: `$q = $conn->query("SELECT biaya_reg FROM reg_periksa WHERE no_rawat='$no_rawat'");`
- `api/data_detail_operasi.php`: `$q = $koneksi->query("SELECT kd_dokter, nm_dokter FROM dokter WHERE kd_dokter IN ($ids)");`
- `api/get_erm_satu_sehat.php`: `$q = $koneksi->query("SELECT p.no_rkm_medis... WHERE rp.no_rawat='$no_rawat'");`
- `api/hitung_estimasi_ralan.php`: `$q = $koneksi->query("SELECT SUM(total) as val FROM detail_pemberian_obat WHERE no_rawat='$no_rawat'");`
- `api/api_absensi.php`: `$q_add = $koneksi->query("SELECT $col_h as shift FROM jadwal_tambahan WHERE id='$id_peg' AND tahun='$thn' AND (bulan='$bln' OR bulan='".(int)$bln."')");`

**Mengapa ini berbahaya?**
Jika `$no_rawat`, `$ids`, atau `$id_peg` berasal dari parameter `$_GET` atau `$_POST` tanpa sanitasi mutlak, penyerang dapat menyuntikkan *payload* SQL (misal: `1' OR '1'='1`) untuk mencuri data atau mem-bypass logika.

### B. Otentikasi dan CSRF (Peringatan)
Walaupun `api/auth_guard.php` sudah memblokir akses tanpa *session*, banyak *endpoint* POST/Mutasi data (seperti simpan/hapus jika ada) yang tidak memeriksa keberadaan atau validitas **CSRF Token**, sehingga rentan terhadap serangan *Cross-Site Request Forgery*.

## 3. Rencana Aksi (Action Plan) untuk AI Agent

Tugas Anda adalah melakukan *refactoring* secara bertahap pada file-file yang terinfeksi. Gunakan langkah-langkah berikut:

### Langkah 1: Migrasi Koneksi
- Pastikan setiap file `api/*.php` yang memanggil `config/koneksi.php` sudah menggunakan koneksi PDO yang tersedia, yaitu variabel `$koneksi_pdo`.

### Langkah 2: Ganti `query()` menjadi `prepare()` + `execute()`
- Cari pola `$koneksi->query(...)` atau `$conn->query(...)`.
- Ubah menjadi format PDO *Prepared Statement*.
- **Pola Salah (Lama):**
  ```php
  $q = $koneksi->query("SELECT biaya_reg FROM reg_periksa WHERE no_rawat='$no_rawat'");
  $row = $q->fetch_assoc(); // atau sejenisnya
  ```
- **Pola Benar (Baru):**
  ```php
  $stmt = $koneksi_pdo->prepare("SELECT biaya_reg FROM reg_periksa WHERE no_rawat = ?");
  $stmt->execute([$no_rawat]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  ```

### Langkah 3: Penanganan Klausul `IN (...)`
- Untuk query dengan klausa `IN` (misal di `data_detail_operasi.php`), Anda **TIDAK BOLEH** langsung menggabungkan array ke dalam query.
- Hitung jumlah elemen, buat *placeholders* `?`, lalu *execute* dengan array values.
- **Contoh Benar:**
  ```php
  // $ids_array adalah array dari nilai
  $placeholders = str_repeat('?,', count($ids_array) - 1) . '?';
  $stmt = $koneksi_pdo->prepare("SELECT kd_dokter, nm_dokter FROM dokter WHERE kd_dokter IN ($placeholders)");
  $stmt->execute($ids_array);
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  ```

### Langkah 4: Validasi Dinamis (Nama Kolom/Tabel)
- PDO *Prepared Statements* **TIDAK BISA** digunakan untuk mengikat (*bind*) nama kolom atau nama tabel.
- Jika ada file yang menggunakan nama kolom/tabel dinamis (misal `api/api_absensi.php` menggunakan `$col_h` atau `api/data_kunjungan_ralan.php` menggunakan `$col` dan `$tbl`), lakukan sanitasi ketat (White-listing) sebelum variabel tersebut digabungkan ke query.

### Langkah 5: Penerapan Perlindungan Lintas Situs (CSRF)
- Jika *endpoint* API menangani perubahan data (INSERT/UPDATE/DELETE) via metode POST, pastikan untuk memvalidasi token CSRF (`$_POST['csrf_token']` atau Header HTTP) melawan `$_SESSION['csrf_token']` menggunakan `hash_equals()`.

## 4. Persetujuan & Eksekusi
Agen AI tidak boleh merusak fungsionalitas UI/UX saat ini (respons JSON harus tetap sama). Segala bentuk *error handling* pada operasi PDO harus menggunakan Blok `try { ... } catch (PDOException $e) { ... }` dan merespons dengan JSON HTTP 500 generik tanpa membocorkan pesan *error* database ke *frontend*.

Lakukan eksekusi secara berurutan dan laporkan progres pembaruan pada `change_log.md` setelah selesai.
