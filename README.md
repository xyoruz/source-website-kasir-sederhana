# XAILLA STORE SPERPAT - Sistem Kasir & Inventaris

XAILLA STORE SPERPAT adalah aplikasi manajemen toko (Point of Sale) berbasis web yang dirancang khusus untuk toko suku cadang (sparepart). Sistem ini mengintegrasikan manajemen stok barang, input faktur supplier, hingga pelaporan laba rugi harian secara otomatis.

## ✨ Fitur Utama

* **🛒 Transaksi Kasir Real-time**: Mendukung dua tipe harga: **Ecer (Umum)** dan **Member (Khusus)**.
* **👥 Manajemen Member**: Fitur validasi nama member saat transaksi dan penambahan member baru secara otomatis melalui antarmuka kasir.
* **📦 Input Faktur (Restock)**: Fitur input barang masuk dalam jumlah banyak sekaligus, lengkap dengan riwayat supplier dan nomor faktur.
* **📜 Laporan Harian Otomatis**: Generate ID Laporan unik setiap hari (Format: `ID-YYYYMMDD`) yang merangkum total omzet dan laba bersih.
* **✏️ Kelola Produk**: Kemudahan dalam memperbarui nama barang, stok, harga modal, hingga harga jual dalam satu dasbor.
* **🌓 Mode Gelap/Terang**: Antarmuka modern yang mendukung perpindahan tema secara instan.
* **📄 Cetak Struk**: Opsi cetak nota setelah transaksi berhasil.

## 🚀 Teknologi yang Digunakan

* **Backend**: PHP
* **Database**: MySQL/MariaDB
* **Frontend**: HTML5, CSS3 (Modern UI dengan Glassmorphism), JavaScript (Vanilla JS & AJAX)
* **Icons/UI**: CSS Variables & Custom System Fonts

## 🛠️ Instalasi

1.  **Clone Repositori**
    ```bash
    git clone [https://github.com/username-anda/xailla-store.git](https://github.com/username-anda/xailla-store.git)
    ```

2.  **Konfigurasi Database**
    * Buat database baru di phpMyAdmin (contoh: `db_xailla`).
    * Impor file `.sql` (jika tersedia) atau pastikan tabel-tabel berikut ada: `produk`, `laporan`, `member`, `history_faktur`, dan `admin`.
    * Sesuaikan konfigurasi koneksi pada file `koneksi.php`.

3.  **Jalankan di Server Lokal**
    * Pindahkan folder ke `htdocs` (XAMPP) atau `www` (Laragon).
    * Akses melalui browser di `http://localhost/xailla-store`.

## 📸 Tampilan Antarmuka

> *Tambahkan screenshot aplikasi Anda di sini untuk menarik perhatian pengguna.*

## 📂 Struktur File Utama

* `index.php`: Pusat logika aplikasi, handler AJAX, dan antarmuka utama.
* `koneksi.php`: Pengaturan koneksi ke database MySQL.
* `login.php`: Sistem autentikasi admin.
* `logout.php`: Mengakhiri sesi pengguna.

## 🤝 Kontribusi

Kontribusi selalu terbuka! Jika Anda memiliki ide fitur baru atau menemukan bug, silakan buat *Pull Request* atau buka *Issue*.

---
Developed by **XAILLA STORE**
