# ChemistLab + Database MySQL

## Isi folder
| File | Fungsi |
|---|---|
| `index.html` | Game (HTML + CSS + JS dalam satu file) |
| `api.php` | API PHP: daftar, login, auto-login, simpan & muat data |
| `config.php` | Pengaturan koneksi database |
| `database.sql` | Membuat database `chemistlab` + tabel `users` dan `users_inventory` |

## Cara menjalankan (XAMPP)
1. Salin folder ini ke `C:\xampp\htdocs\chemistlab\`.
2. Nyalakan **Apache** dan **MySQL** di XAMPP.
3. Buka phpMyAdmin, pilih **Impor**, lalu pilih `database.sql`.
   (Kalau tabel `users` dan `users_inventory` sudah kamu buat seperti di screenshot, langkah ini boleh dilewati.)
4. Buka `http://localhost/chemistlab/`.
5. Kalau password MySQL-mu tidak kosong, ubah `config.php`.

## Cara kerja
- Akun baru dibuat lewat tombol **Daftar** di halaman login (username minimal 3 karakter, kata sandi minimal 4 karakter). Data masuk ke tabel `users` dan kata sandi disimpan sebagai hash.
- Setelah mendaftar, pemain harus **Masuk** dulu. Login hanya bisa untuk akun yang sudah terdaftar.
- **Tabel `users`** menyimpan `level` dan `coins`.
- **Tabel `users_inventory`** menyimpan satu baris per kode di kolom `item_code`:

| Kode | Arti |
|---|---|
| `gems:12` | jumlah permata |
| `hint:3` | jatah hint |
| `own:stir` | barang yang dibeli (Pengaduk Cepat) |
| `avatar:4` | avatar yang dimiliki |
| `avsel:4` | avatar yang sedang dipakai |
| `disc:7:2` | senyawa ke-7 sudah dibuat 2 kali |
| `master:7` | senyawa ke-7 lulus ujian (bar Compendium penuh) |
| `exam:1` | ujian blok 1 (level 1-5) sudah lulus |
| `done:5` | level ke-6 sudah diselesaikan |

- Data disimpan otomatis setiap ada perubahan dan dimuat dari database saat login, jadi akun yang sama bisa dibuka dari device lain dengan isi yang sama.
- Untuk membuka dari HP/laptop lain di jaringan yang sama, pakai `http://IP-KOMPUTER/chemistlab/` (cek IP dengan `ipconfig`, dan izinkan Apache di firewall).
- Jika `api.php` tidak bisa diakses (misalnya `index.html` dibuka langsung tanpa server), game otomatis memakai mode offline (localStorage).

## Catatan keamanan
Data (coins, level, dll.) dikirim dari browser, jadi pemain yang paham teknis masih bisa mengubahnya. Untuk game belajar ini cukup, tetapi kalau mau dibuat kompetitif, perhitungan hadiah sebaiknya dipindah ke server.
